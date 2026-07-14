(function (window) {
    'use strict';

    function normalizeUid(uid) {
        return uid === null || typeof uid === 'undefined' ? '' : String(uid);
    }

    function text(strings, key, fallback) {
        return (strings && strings[key]) || fallback || key;
    }

    function safeAvatarUrl(url) {
        try {
            const parsed = new URL(url, window.location.href);
            return parsed.protocol === 'http:' || parsed.protocol === 'https:' ? parsed.href : '';
        } catch (error) {
            return '';
        }
    }

    function initials(name) {
        return (name || '').trim().split(/\s+/).slice(0, 2).map(function (part) {
            return part.charAt(0).toUpperCase();
        }).join('') || '?';
    }

    window.VH360StudioMobileParticipants = {
        create: function createParticipantController(options) {
            const root = options.root;
            const strings = options.strings || {};
            const state = {
                enabled: !!options.enabled,
                session: options.session || null,
                ajaxUrl: options.ajaxUrl || '',
                identityNonce: options.identityNonce || '',
                context: { videoId: 0, channelName: '', localUid: '' },
                started: false,
                participants: new Map(),
                identityCache: new Map(),
                identityQueue: new Set(),
                identityAttempts: new Map(),
                identityTimer: 0,
                audioBlocked: false,
                headphoneNoticeShown: false,
                handlers: []
            };

            const remoteStage = root.querySelector('[data-mobile-remote-stage]');
            const drawer = root.querySelector('[data-mobile-participant-drawer]');
            const drawerList = root.querySelector('[data-mobile-participant-list]');
            const countEls = Array.prototype.slice.call(root.querySelectorAll('[data-mobile-participant-count]'));
            const audioButton = root.querySelector('[data-mobile-enable-participant-audio]');
            const notice = root.querySelector('[data-mobile-participant-notice]');

            function emitCount() {
                const count = state.participants.size;
                const label = count === 1 ? text(strings, 'oneParticipant', 'One participant') : text(strings, 'participantCount', '%d participants').replace('%d', count);
                countEls.forEach(function (el) { el.textContent = count ? label : text(strings, 'noParticipantsYet', 'No participants yet'); });
                root.classList.toggle('has-remote-participants', count > 0);
                root.dispatchEvent(new CustomEvent('vh360:mobile-participants:count', { detail: { count: count } }));
            }

            function ensureTile(uid) {
                const key = normalizeUid(uid);
                let record = state.participants.get(key);
                if (record) {
                    return record;
                }

                const tile = document.createElement('article');
                tile.className = 'vh360-mobile-live__participant-tile';
                tile.setAttribute('data-mobile-participant-uid', key);
                tile.tabIndex = 0;

                const video = document.createElement('div');
                video.className = 'vh360-mobile-live__participant-video';
                video.setAttribute('data-mobile-participant-video', '');

                const placeholder = document.createElement('div');
                placeholder.className = 'vh360-mobile-live__participant-placeholder';
                const avatar = document.createElement('img');
                avatar.alt = '';
                avatar.hidden = true;
                avatar.setAttribute('data-mobile-participant-avatar', '');
                const initial = document.createElement('span');
                initial.textContent = '?';
                placeholder.appendChild(avatar);
                placeholder.appendChild(initial);

                const meta = document.createElement('div');
                meta.className = 'vh360-mobile-live__participant-meta';
                const name = document.createElement('span');
                name.className = 'vh360-mobile-live__tile-name';
                name.setAttribute('data-mobile-participant-name', '');
                name.textContent = text(strings, 'participant', 'Participant');
                const status = document.createElement('span');
                status.className = 'vh360-mobile-live__participant-status';
                status.textContent = text(strings, 'cameraOff', 'Camera off');
                meta.appendChild(name);
                meta.appendChild(status);

                tile.appendChild(video);
                tile.appendChild(placeholder);
                tile.appendChild(meta);
                remoteStage.appendChild(tile);

                const drawerItem = document.createElement('button');
                drawerItem.type = 'button';
                drawerItem.className = 'vh360-mobile-live__participant-row';
                drawerItem.textContent = name.textContent;
                drawerItem.addEventListener('click', function () {
                    selectParticipant(key);
                });

                record = {
                    uid: key,
                    tile: tile,
                    videoContainer: video,
                    placeholder: placeholder,
                    avatar: avatar,
                    initials: initial,
                    name: name,
                    status: status,
                    drawerItem: drawerItem,
                    hasAudio: false,
                    hasVideo: false
                };
                state.participants.set(key, record);
                drawerList.appendChild(drawerItem);
                queueIdentity(key);
                emitCount();
                return record;
            }

            function updateTile(record) {
                record.tile.classList.toggle('has-video', record.hasVideo);
                record.tile.classList.toggle('has-audio', record.hasAudio);
                record.tile.classList.toggle('is-muted', !record.hasAudio);
                record.status.textContent = record.hasVideo ? (record.hasAudio ? text(strings, 'connected', 'Connected') : text(strings, 'microphoneMuted', 'Microphone muted')) : text(strings, 'cameraOff', 'Camera off');
                record.drawerItem.textContent = record.name.textContent + ' — ' + record.status.textContent;
            }

            function applyIdentity(uid, identity) {
                const record = state.participants.get(normalizeUid(uid));
                if (!record || !identity) {
                    return;
                }
                const label = identity.display_name || text(strings, 'participant', 'Participant');
                record.name.textContent = label;
                record.initials.textContent = initials(label);
                const avatarUrl = safeAvatarUrl(identity.avatar_url || '');
                if (avatarUrl) {
                    record.avatar.src = avatarUrl;
                    record.avatar.hidden = false;
                }
                updateTile(record);
            }

            function queueIdentity(uid) {
                const key = normalizeUid(uid);
                if (!key || state.identityCache.has(key)) {
                    return;
                }
                state.identityQueue.add(key);
                window.clearTimeout(state.identityTimer);
                state.identityTimer = window.setTimeout(flushIdentityQueue, 150);
            }

            async function lookupIdentities(keys) {
                const form = new FormData();
                form.append('action', 'vh360_lookup_agora_participant_identities');
                form.append('nonce', state.identityNonce);
                form.append('post_id', state.context.videoId);
                form.append('channel_name', state.context.channelName);
                keys.forEach(function (key) { form.append('uids[]', key); });
                const response = await window.fetch(state.ajaxUrl, { method: 'POST', body: form });
                const payload = await response.json();
                return payload && payload.success && payload.data && payload.data.identities ? payload.data.identities : {};
            }

            function flushIdentityQueue() {
                const keys = Array.from(state.identityQueue).slice(0, 50);
                state.identityQueue.clear();
                state.identityTimer = 0;
                if (!keys.length || !state.started || !state.ajaxUrl || !state.context.videoId) {
                    return;
                }
                lookupIdentities(keys).then(function (identities) {
                    keys.forEach(function (key) {
                        if (identities[key]) {
                            state.identityCache.set(key, identities[key]);
                            applyIdentity(key, identities[key]);
                            return;
                        }
                        const attempts = (state.identityAttempts.get(key) || 0) + 1;
                        state.identityAttempts.set(key, attempts);
                        if (attempts < 3) {
                            state.identityQueue.add(key);
                            window.clearTimeout(state.identityTimer);
                            state.identityTimer = window.setTimeout(flushIdentityQueue, 1200 * attempts);
                        }
                    });
                }).catch(function (error) {
                    console.error('[VH360 Mobile Live] Participant identity lookup failed', error);
                });
            }

            function showAudioFallback(show) {
                state.audioBlocked = !!show;
                if (audioButton) {
                    audioButton.hidden = !show;
                }
            }

            function showHeadphoneNotice() {
                if (!notice || state.headphoneNoticeShown) {
                    return;
                }
                notice.textContent = text(strings, 'headphonesRecommended', 'Headphones are recommended when monitoring participant audio.');
                notice.hidden = false;
                state.headphoneNoticeShown = true;
            }

            function handlePublished(event) {
                if (!state.started || !state.enabled) {
                    return;
                }
                const detail = event.detail || {};
                const uid = normalizeUid(detail.uid);
                if (!uid || uid === normalizeUid(state.context.localUid)) {
                    return;
                }
                const record = ensureTile(uid);
                if (detail.mediaType === 'video' || detail.videoAvailable) {
                    record.hasVideo = true;
                    if (state.session && !state.session.playRemoteVideo(uid, record.videoContainer)) {
                        record.hasVideo = false;
                    }
                }
                if (detail.mediaType === 'audio' || detail.audioAvailable) {
                    record.hasAudio = true;
                    showHeadphoneNotice();
                    if (state.session && !state.session.playRemoteAudio(uid)) {
                        showAudioFallback(true);
                    }
                }
                updateTile(record);
            }

            function handleUnpublished(event) {
                const detail = event.detail || {};
                const record = state.participants.get(normalizeUid(detail.uid));
                if (!record) {
                    return;
                }
                if (detail.mediaType === 'video') {
                    state.session && state.session.stopRemoteVideo(record.uid);
                    record.hasVideo = false;
                }
                if (detail.mediaType === 'audio') {
                    state.session && state.session.stopRemoteAudio(record.uid);
                    record.hasAudio = false;
                }
                updateTile(record);
            }

            function handleLeft(event) {
                const uid = normalizeUid((event.detail || {}).uid);
                const record = state.participants.get(uid);
                if (!record) {
                    return;
                }
                state.session && state.session.stopRemoteVideo(uid);
                state.session && state.session.stopRemoteAudio(uid);
                record.tile.remove();
                record.drawerItem.remove();
                state.participants.delete(uid);
                emitCount();
            }

            function selectParticipant(uid) {
                const key = normalizeUid(uid);
                state.participants.forEach(function (record) {
                    record.tile.classList.toggle('is-selected', record.uid === key);
                    if (state.session) {
                        state.session.setRemoteVideoQuality(record.uid, record.uid === key ? 'high' : 'low');
                    }
                });
            }

            function bind(target, eventName, handler) {
                target.addEventListener(eventName, handler);
                state.handlers.push({ target: target, eventName: eventName, handler: handler });
            }

            function start() {
                if (!state.enabled || state.started) {
                    return;
                }
                state.started = true;
                bind(root, 'vh360:agora-broadcaster:remote-participant-published', handlePublished);
                bind(root, 'vh360:agora-broadcaster:remote-track-unpublished', handleUnpublished);
                bind(root, 'vh360:agora-broadcaster:remote-participant-left', handleLeft);
                bind(root, 'vh360:agora-broadcaster:remote-audio-blocked', function () { showAudioFallback(true); });
                bind(root, 'vh360:agora-broadcaster:remote-video-error', function (event) {
                    console.error('[VH360 Mobile Live] Remote video playback failed', event.detail && event.detail.error);
                    if (notice) {
                        notice.textContent = text(strings, 'participantVideoFailed', 'Interactive participant video could not be displayed.');
                        notice.hidden = false;
                    }
                });
                emitCount();
            }

            function stop() {
                state.handlers.forEach(function (entry) {
                    entry.target.removeEventListener(entry.eventName, entry.handler);
                });
                state.handlers = [];
                window.clearTimeout(state.identityTimer);
                state.identityTimer = 0;
                state.participants.forEach(function (record) {
                    if (state.session) {
                        state.session.stopRemoteVideo(record.uid);
                        state.session.stopRemoteAudio(record.uid);
                    }
                    record.tile.remove();
                    record.drawerItem.remove();
                });
                state.participants.clear();
                state.started = false;
                closeDrawer();
                showAudioFallback(false);
                if (notice) {
                    notice.hidden = true;
                    notice.textContent = '';
                }
                emitCount();
            }

            function openDrawer() {
                if (drawer) {
                    drawer.hidden = false;
                    drawer.setAttribute('aria-hidden', 'false');
                    const close = drawer.querySelector('[data-mobile-close-participants]');
                    if (close) {
                        close.focus();
                    }
                }
            }

            function closeDrawer() {
                if (drawer) {
                    drawer.hidden = true;
                    drawer.setAttribute('aria-hidden', 'true');
                }
            }

            if (audioButton) {
                audioButton.addEventListener('click', function () {
                    let blocked = false;
                    state.participants.forEach(function (record) {
                        if (record.hasAudio && state.session && !state.session.playRemoteAudio(record.uid)) {
                            blocked = true;
                        }
                    });
                    showAudioFallback(blocked);
                });
            }

            return {
                setBroadcastContext: function (context) {
                    state.context = Object.assign({}, state.context, context || {});
                    state.context.localUid = normalizeUid(state.context.localUid);
                },
                setSession: function (session) {
                    state.session = session;
                },
                start: start,
                stop: stop,
                openDrawer: openDrawer,
                closeDrawer: closeDrawer,
                getParticipantCount: function () { return state.participants.size; }
            };
        }
    };
})(window);
