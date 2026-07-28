(function (window) {
    'use strict';

    const MAX_IDENTITY_ATTEMPTS = 3;

    function normalizeUid(uid) {
        return uid === null || typeof uid === 'undefined' ? '' : String(uid);
    }

    function label(strings, key, fallback) {
        return (strings && strings[key]) || fallback || key;
    }

    function safeAvatarUrl(url) {
        try {
            const parsed = new URL(url || '', window.location.href);
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

    function focusableElements(container) {
        return Array.prototype.slice.call(container.querySelectorAll('button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])')).filter(function (el) {
            return !el.disabled && !el.hidden;
        });
    }

    function microphoneIcon(modifier, pathData) {
        const svg = document.createElementNS('http://www.w3.org/2000/svg', 'svg');
        svg.setAttribute('class', 'vh360-mobile-live__microphone-icon vh360-mobile-live__microphone-icon--' + modifier);
        svg.setAttribute('viewBox', '0 0 24 24');
        svg.setAttribute('focusable', 'false');
        svg.setAttribute('aria-hidden', 'true');
        const path = document.createElementNS('http://www.w3.org/2000/svg', 'path');
        path.setAttribute('d', pathData);
        svg.appendChild(path);
        return svg;
    }

    function microphoneStateElement() {
        const state = document.createElement('span');
        state.className = 'vh360-mobile-live__microphone-state is-muted';
        state.setAttribute('data-mobile-participant-microphone', '');
        state.appendChild(microphoneIcon('on', 'M12 14a3 3 0 0 0 3-3V5a3 3 0 0 0-6 0v6a3 3 0 0 0 3 3Zm5-3a5 5 0 0 1-10 0H5a7 7 0 0 0 6 6.92V21H8v2h8v-2h-3v-3.08A7 7 0 0 0 19 11h-2Z'));
        state.appendChild(microphoneIcon('off', 'm4.27 3 16.73 16.73-1.27 1.27-4.12-4.12A6.9 6.9 0 0 1 13 17.92V21h3v2H8v-2h3v-3.08A7 7 0 0 1 5 11h2a5 5 0 0 0 6.96 4.59l-1.52-1.52A3 3 0 0 1 9 11v-.37L3 4.27 4.27 3ZM12 2a3 3 0 0 1 3 3v6c0 .28-.04.55-.11.8L9 5.91V5a3 3 0 0 1 3-3Zm7 9a6.96 6.96 0 0 1-.84 3.32l-1.5-1.5c.22-.57.34-1.18.34-1.82h2Z'));
        return state;
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
                bound: false,
                renderingActive: false,
                stopped: false,
                selectedUid: '',
                participants: new Map(),
                identityCache: new Map(),
                identityQueue: new Set(),
                identityAttempts: new Map(),
                identityTimer: 0,
                audioBlocked: false,
                headphoneNoticeShown: false,
                focusReturn: null,
                handlers: [],
                uiHandlersBound: false,
                audioRetryGeneration: 0
            };

            const remoteStage = root.querySelector('[data-mobile-remote-stage]');
            const drawer = root.querySelector('[data-mobile-participant-drawer]');
            const drawerList = root.querySelector('[data-mobile-participant-list]');
            const countEls = Array.prototype.slice.call(root.querySelectorAll('[data-mobile-participant-count]'));
            const countValueEls = Array.prototype.slice.call(root.querySelectorAll('[data-mobile-participant-count-value]'));
            const audioButton = root.querySelector('[data-mobile-enable-participant-audio]');
            const notice = root.querySelector('[data-mobile-participant-notice]');
            const openButton = root.querySelector('[data-mobile-open-participants]');

            function emitCount() {
                const count = state.participants.size;
                const accessibleText = count === 0
                    ? label(strings, 'zeroParticipantsLabel', '0 participants')
                    : (count === 1 ? label(strings, 'oneParticipantLabel', '1 participant') : label(strings, 'participantCount', '%d participants').replace('%d', count));
                countValueEls.forEach(function (el) {
                    el.textContent = String(count);
                });
                countEls.forEach(function (el) {
                    el.setAttribute('aria-label', accessibleText);
                });
                root.setAttribute('data-mobile-remote-count', String(count));
                root.classList.toggle('has-remote-participants', count > 0);
                root.classList.toggle('has-one-remote-participant', count === 1);
                root.classList.toggle('has-multiple-remote-participants', count > 1);
                root.classList.toggle('has-large-participant-grid', count >= 5);
                root.dispatchEvent(new CustomEvent('vh360:mobile-participants:count', { detail: { count: count } }));
            }

            function ensureRecord(uid) {
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
                name.className = 'vh360-mobile-live__participant-name';
                name.setAttribute('data-mobile-participant-name', '');
                name.textContent = label(strings, 'participant', 'Participant');
                const microphone = microphoneStateElement();
                meta.appendChild(name);
                meta.appendChild(microphone);

                tile.appendChild(video);
                tile.appendChild(placeholder);
                tile.appendChild(meta);
                remoteStage.appendChild(tile);

                const row = document.createElement('button');
                row.type = 'button';
                row.className = 'vh360-mobile-live__participant-row';
                row.setAttribute('data-mobile-participant-row', key);
                row.addEventListener('click', function () {
                    selectParticipant(key);
                    renderVisibleParticipants();
                });

                const rowAvatar = document.createElement('span');
                rowAvatar.className = 'vh360-mobile-live__participant-row-avatar';
                const rowName = document.createElement('span');
                rowName.className = 'vh360-mobile-live__participant-row-name';
                const rowMicrophone = microphoneStateElement();
                row.appendChild(rowAvatar);
                row.appendChild(rowName);
                row.appendChild(rowMicrophone);
                drawerList.appendChild(row);

                record = {
                    uid: key,
                    tile: tile,
                    videoContainer: video,
                    placeholder: placeholder,
                    avatar: avatar,
                    initials: initial,
                    name: name,
                    microphone: microphone,
                    drawerItem: row,
                    drawerAvatar: rowAvatar,
                    drawerName: rowName,
                    drawerMicrophone: rowMicrophone,
                    hasAudio: false,
                    hasVideo: false,
                    rendered: false,
                    videoTrack: null,
                    audioTrack: null,
                    needsVideoPlay: false,
                    needsAudioPlay: false,
                    requestedVideoQuality: ''
                };
                state.participants.set(key, record);
                if (state.identityCache.has(key)) {
                    const cachedIdentity = state.identityCache.get(key);
                    if (cachedIdentity) {
                        applyIdentity(key, cachedIdentity);
                    }
                } else {
                    queueIdentity(key);
                }
                updateRecord(record);
                emitCount();
                return record;
            }

            function updateRecord(record) {
                const microphoneActive = record.hasAudio === true;
                const microphoneLabel = microphoneActive ? label(strings, 'microphoneOn', 'Microphone on') : label(strings, 'microphoneMuted', 'Microphone muted');
                record.tile.classList.toggle('has-video', record.hasVideo && record.rendered);
                record.tile.classList.toggle('is-video-loading', record.hasVideo && !record.rendered);
                record.tile.classList.toggle('is-muted', !record.hasAudio);
                record.tile.classList.toggle('is-selected', record.uid === state.selectedUid);
                record.tile.hidden = false;
                [record.microphone, record.drawerMicrophone].forEach(function (microphone) {
                    microphone.classList.toggle('is-active', microphoneActive);
                    microphone.classList.toggle('is-muted', !microphoneActive);
                    microphone.setAttribute('aria-label', microphoneLabel);
                });
                record.drawerItem.classList.toggle('is-selected', record.uid === state.selectedUid);
                record.drawerItem.setAttribute('aria-pressed', record.uid === state.selectedUid ? 'true' : 'false');
                record.drawerName.textContent = record.name.textContent;
                record.drawerAvatar.textContent = record.initials.textContent;
            }

            function applyIdentity(uid, identity) {
                const key = normalizeUid(uid);
                state.identityCache.set(key, identity || null);
                const record = state.participants.get(key);
                if (!record || !identity) {
                    return;
                }
                const name = identity.display_name || label(strings, 'participant', 'Participant');
                record.name.textContent = name;
                record.initials.textContent = initials(name);
                const avatarUrl = safeAvatarUrl(identity.avatar_url || '');
                if (avatarUrl) {
                    record.avatar.src = avatarUrl;
                    record.avatar.hidden = false;
                }
                updateRecord(record);
            }

            function scheduleIdentityFlush(delay) {
                if (state.stopped || state.identityTimer) {
                    return;
                }
                state.identityTimer = window.setTimeout(flushIdentityQueue, delay || 150);
            }

            function queueIdentity(uid) {
                const key = normalizeUid(uid);
                if (!key || state.identityCache.has(key)) {
                    return;
                }
                state.identityQueue.add(key);
                scheduleIdentityFlush(150);
            }

            async function lookupIdentities(keys) {
                const form = new FormData();
                form.append('action', 'vh360_lookup_agora_participant_identities');
                form.append('nonce', state.identityNonce);
                form.append('post_id', state.context.videoId);
                form.append('channel_name', state.context.channelName);
                keys.forEach(function (key) { form.append('uids[]', key); });
                const response = await window.fetch(state.ajaxUrl, { method: 'POST', body: form });
                if (!response.ok) {
                    throw new Error('Identity lookup failed.');
                }
                const payload = await response.json();
                if (!payload || !payload.success) {
                    throw new Error('Identity lookup returned no verified data.');
                }
                return payload.data && payload.data.identities ? payload.data.identities : {};
            }

            function retryIdentities(keys) {
                keys.forEach(function (key) {
                    const attempts = (state.identityAttempts.get(key) || 0) + 1;
                    state.identityAttempts.set(key, attempts);
                    if (attempts < MAX_IDENTITY_ATTEMPTS && !state.stopped) {
                        state.identityQueue.add(key);
                    } else if (!state.identityCache.has(key)) {
                        state.identityCache.set(key, null);
                    }
                });
                if (state.identityQueue.size && !state.stopped) {
                    scheduleIdentityFlush(900);
                }
            }

            function flushIdentityQueue() {
                state.identityTimer = 0;
                if (state.stopped || !state.bound || !state.ajaxUrl || !state.context.videoId || !state.identityQueue.size) {
                    return;
                }
                const batch = Array.from(state.identityQueue).slice(0, 50);
                batch.forEach(function (key) { state.identityQueue.delete(key); });
                lookupIdentities(batch).then(function (identities) {
                    if (state.stopped) {
                        return;
                    }
                    const unresolved = [];
                    batch.forEach(function (key) {
                        if (identities[key]) {
                            applyIdentity(key, identities[key]);
                        } else {
                            unresolved.push(key);
                        }
                    });
                    if (unresolved.length) {
                        retryIdentities(unresolved);
                    } else if (state.identityQueue.size) {
                        scheduleIdentityFlush(150);
                    }
                }).catch(function (error) {
                    console.error('[VH360 Mobile Live] Participant identity lookup failed', error);
                    retryIdentities(batch);
                });
            }

            function showAudioFallback(show) {
                state.audioBlocked = !!show;
                if (audioButton) {
                    audioButton.hidden = !show;
                }
            }

            function resetParticipantAudioUi() {
                showAudioFallback(false);
                state.headphoneNoticeShown = false;
                if (notice) {
                    notice.hidden = true;
                    notice.textContent = '';
                }
            }

            function reevaluateParticipantAudioUi() {
                const hasAudio = Array.from(state.participants.values()).some(function (record) {
                    return record.hasAudio;
                });
                const hasBlocked = Array.from(state.participants.values()).some(function (record) {
                    return record.hasAudio && record.needsAudioPlay;
                });
                if (!hasAudio) {
                    resetParticipantAudioUi();
                } else {
                    showAudioFallback(hasBlocked);
                }
            }

            function showHeadphoneNotice() {
                if (!notice || state.headphoneNoticeShown) {
                    return;
                }
                notice.textContent = label(strings, 'headphonesRecommended', 'Headphones are recommended when monitoring participant audio.');
                notice.hidden = false;
                state.headphoneNoticeShown = true;
            }

            async function renderVisibleParticipants() {
                if (!state.renderingActive || !state.session) {
                    return;
                }
                const tasks = [];
                state.participants.forEach(function (record) {
                    if (record.hasVideo) {
                        if (!record.rendered || record.needsVideoPlay) {
                            const ok = state.session.playRemoteVideo(record.uid, record.videoContainer);
                            record.rendered = !!ok;
                            record.needsVideoPlay = !ok;
                        }
                    } else if (record.rendered) {
                        state.session.stopRemoteVideo(record.uid);
                        record.rendered = false;
                    }
                    if (record.hasAudio && record.needsAudioPlay) {
                        showHeadphoneNotice();
                        if (state.session.playRemoteAudio(record.uid)) {
                            record.needsAudioPlay = false;
                        } else {
                            showAudioFallback(true);
                        }
                    }
                    if (record.hasVideo && record.rendered) {
                        const quality = record.uid === state.selectedUid ? 'high' : 'low';
                        if (record.requestedVideoQuality !== quality) {
                            record.requestedVideoQuality = quality;
                            tasks.push(Promise.resolve(state.session.setRemoteVideoQuality(record.uid, quality)).then(function (applied) {
                                if (!applied && record.requestedVideoQuality === quality) {
                                    record.requestedVideoQuality = '';
                                }
                                return applied;
                            }).catch(function () {
                                if (record.requestedVideoQuality === quality) {
                                    record.requestedVideoQuality = '';
                                }
                                return false;
                            }));
                        }
                    }
                    updateRecord(record);
                });
                await Promise.all(tasks);
            }

            function mergeRemoteParticipant(detail) {
                const uid = normalizeUid(detail.uid);
                if (!uid || uid === normalizeUid(state.context.localUid)) {
                    return null;
                }
                const record = ensureRecord(uid);
                if (detail.videoAvailable) {
                    if (record.videoTrack !== detail.videoTrack) {
                        record.videoTrack = detail.videoTrack || {};
                        record.needsVideoPlay = true;
                        record.requestedVideoQuality = '';
                    }
                    record.hasVideo = true;
                }
                if (detail.audioAvailable) {
                    if (record.audioTrack !== detail.audioTrack) {
                        record.audioTrack = detail.audioTrack || {};
                        record.needsAudioPlay = true;
                    }
                    record.hasAudio = true;
                }
                if (!state.selectedUid && record.hasVideo) {
                    state.selectedUid = uid;
                }
                updateRecord(record);
                return record;
            }

            function handlePublished(event) {
                if (!state.bound || !state.enabled) {
                    return;
                }
                mergeRemoteParticipant(event.detail || {});
                renderVisibleParticipants().catch(function (error) {
                    console.error('[VH360 Mobile Live] Participant render failed', error);
                });
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
                    record.rendered = false;
                    record.videoTrack = null;
                    record.needsVideoPlay = false;
                    record.requestedVideoQuality = '';
                }
                if (detail.mediaType === 'audio') {
                    state.session && state.session.stopRemoteAudio(record.uid);
                    record.hasAudio = false;
                    record.audioTrack = null;
                    record.needsAudioPlay = false;
                }
                if (state.selectedUid === record.uid && !record.hasVideo) {
                    state.selectedUid = '';
                }
                updateRecord(record);
                reevaluateParticipantAudioUi();
                renderVisibleParticipants().catch(function () {});
            }

            function clearTransportParticipants() {
                state.participants.forEach(function (record) {
                    if (state.session) {
                        state.session.stopRemoteVideo(record.uid);
                        state.session.stopRemoteAudio(record.uid);
                    }
                    record.tile.remove();
                    record.drawerItem.remove();
                });
                state.participants.clear();
                state.selectedUid = '';
                resetParticipantAudioUi();
                emitCount();
            }

            function handleReset() {
                clearTransportParticipants();
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
                if (state.selectedUid === uid) {
                    state.selectedUid = '';
                }
                emitCount();
                reevaluateParticipantAudioUi();
                renderVisibleParticipants().catch(function () {});
            }

            function selectParticipant(uid) {
                state.selectedUid = normalizeUid(uid);
                const selected = state.participants.get(state.selectedUid);
                if (selected && selected.hasVideo && !selected.rendered) {
                    selected.needsVideoPlay = true;
                }
                state.participants.forEach(updateRecord);
                if (selected && typeof selected.tile.scrollIntoView === 'function') {
                    const reduceMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                    selected.tile.scrollIntoView({
                        block: 'nearest',
                        inline: 'nearest',
                        behavior: reduceMotion ? 'auto' : 'smooth'
                    });
                }
            }

            function isAudioRetryCurrent(generation) {
                return !state.stopped && state.bound && generation === state.audioRetryGeneration;
            }

            async function handleEnableParticipantAudio() {
                if (state.stopped || !state.bound) {
                    return;
                }
                state.audioRetryGeneration += 1;
                const generation = state.audioRetryGeneration;
                audioButton.disabled = true;
                let blocked = false;
                try {
                    if (window.AgoraRTC && typeof window.AgoraRTC.resumeAudioContext === 'function') {
                        await window.AgoraRTC.resumeAudioContext();
                    }
                    if (!isAudioRetryCurrent(generation)) {
                        return;
                    }
                    state.participants.forEach(function (record) {
                        if (!isAudioRetryCurrent(generation)) {
                            return;
                        }
                        if (record.hasAudio && state.session) {
                            record.needsAudioPlay = true;
                            if (state.session.playRemoteAudio(record.uid)) {
                                record.needsAudioPlay = false;
                            } else {
                                blocked = true;
                            }
                        }
                    });
                } catch (error) {
                    console.error('[VH360 Mobile Live] Participant audio retry failed', error);
                    blocked = true;
                } finally {
                    if (isAudioRetryCurrent(generation)) {
                        showAudioFallback(blocked);
                        audioButton.disabled = false;
                    }
                }
            }

            function handleDrawerKeydown(event) {
                if (state.stopped) {
                    return;
                }
                if (event.key === 'Escape') {
                    event.preventDefault();
                    closeDrawer();
                    return;
                }
                if (event.key === 'Tab') {
                    const items = focusableElements(drawer);
                    if (!items.length) {
                        event.preventDefault();
                        return;
                    }
                    const first = items[0];
                    const last = items[items.length - 1];
                    if (event.shiftKey && document.activeElement === first) {
                        event.preventDefault();
                        last.focus();
                    } else if (!event.shiftKey && document.activeElement === last) {
                        event.preventDefault();
                        first.focus();
                    }
                }
            }

            function bindUiHandlers() {
                if (state.uiHandlersBound) {
                    return;
                }
                if (audioButton) {
                    audioButton.addEventListener('click', handleEnableParticipantAudio);
                }
                if (drawer) {
                    drawer.addEventListener('keydown', handleDrawerKeydown);
                }
                state.uiHandlersBound = true;
            }

            function unbindUiHandlers() {
                if (!state.uiHandlersBound) {
                    return;
                }
                if (audioButton) {
                    audioButton.removeEventListener('click', handleEnableParticipantAudio);
                }
                if (drawer) {
                    drawer.removeEventListener('keydown', handleDrawerKeydown);
                }
                state.uiHandlersBound = false;
            }

            function bindEvent(target, eventName, handler) {
                target.addEventListener(eventName, handler);
                state.handlers.push({ target: target, eventName: eventName, handler: handler });
            }

            function bind() {
                if (!state.enabled || state.bound) {
                    return;
                }
                state.stopped = false;
                state.bound = true;
                if (audioButton) {
                    audioButton.disabled = false;
                }
                bindUiHandlers();
                bindEvent(root, 'vh360:agora-broadcaster:remote-participant-published', handlePublished);
                bindEvent(root, 'vh360:agora-broadcaster:remote-track-unpublished', handleUnpublished);
                bindEvent(root, 'vh360:agora-broadcaster:remote-participants-reset', handleReset);
                bindEvent(root, 'vh360:agora-broadcaster:remote-participant-left', handleLeft);
                bindEvent(root, 'vh360:agora-broadcaster:remote-audio-blocked', function () { showAudioFallback(true); });
                bindEvent(root, 'vh360:agora-broadcaster:remote-video-error', function (event) {
                    console.error('[VH360 Mobile Live] Remote video playback failed', event.detail && event.detail.error);
                    if (notice) {
                        notice.textContent = label(strings, 'participantVideoFailed', 'Interactive participant video could not be displayed.');
                        notice.hidden = false;
                    }
                });
                emitCount();
            }

            async function activateRendering() {
                if (!state.enabled) {
                    return;
                }
                bind();
                state.renderingActive = true;
                if (state.session && typeof state.session.getRemoteParticipants === 'function') {
                    state.session.getRemoteParticipants().forEach(function (detail) {
                        mergeRemoteParticipant(detail);
                    });
                }
                await renderVisibleParticipants();
            }

            function stop() {
                state.stopped = true;
                state.audioRetryGeneration += 1;
                if (audioButton) {
                    audioButton.disabled = false;
                    audioButton.hidden = true;
                }
                state.renderingActive = false;
                state.handlers.forEach(function (entry) {
                    entry.target.removeEventListener(entry.eventName, entry.handler);
                });
                state.handlers = [];
                state.bound = false;
                unbindUiHandlers();
                window.clearTimeout(state.identityTimer);
                state.identityTimer = 0;
                state.identityQueue.clear();
                clearTransportParticipants();
                closeDrawer();
                showAudioFallback(false);
                if (notice) {
                    notice.hidden = true;
                    notice.textContent = '';
                }
                emitCount();
            }

            function openDrawer() {
                if (!drawer) {
                    return;
                }
                state.focusReturn = document.activeElement || openButton;
                drawer.hidden = false;
                drawer.setAttribute('aria-hidden', 'false');
                const close = drawer.querySelector('[data-mobile-close-participants]');
                if (close) {
                    close.focus();
                }
                root.dispatchEvent(new CustomEvent('vh360:mobile-participants:drawer-opened'));
            }

            function closeDrawer() {
                if (!drawer) {
                    return;
                }
                drawer.hidden = true;
                drawer.setAttribute('aria-hidden', 'true');
                if (state.focusReturn && typeof state.focusReturn.focus === 'function') {
                    state.focusReturn.focus();
                }
                state.focusReturn = null;
                root.dispatchEvent(new CustomEvent('vh360:mobile-participants:drawer-closed'));
            }


            return {
                setBroadcastContext: function (context) {
                    state.context = Object.assign({}, state.context, context || {});
                    state.context.localUid = normalizeUid(state.context.localUid);
                },
                setSession: function (session) { state.session = session; },
                bind: bind,
                start: bind,
                activateRendering: activateRendering,
                stop: stop,
                openDrawer: openDrawer,
                closeDrawer: closeDrawer,
                getParticipantCount: function () { return state.participants.size; },
                _debugState: state
            };
        }
    };
})(window);
