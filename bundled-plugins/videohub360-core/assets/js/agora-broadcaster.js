(function (window) {
    'use strict';

    function emit(target, name, detail) {
        (target || window).dispatchEvent(new CustomEvent('vh360:agora-broadcaster:' + name, {
            detail: detail || {}
        }));
    }

    function sleep(ms) {
        return new Promise(function (resolve) {
            window.setTimeout(resolve, ms);
        });
    }

    window.VH360AgoraBroadcaster = {
        create: function createBroadcaster(config) {
            const root = config.container || document;
            const localContainer = config.localContainer || root.querySelector('[data-agora-local-preview]') || root;
            const state = {
                client: null,
                audioTrack: null,
                audioTrackOwnsSource: true,
                videoTrack: null,
                videoTrackOwnsSource: true,
                joined: false,
                published: false,
                appId: config.appId || '',
                channelName: config.channelName || '',
                uid: config.uid || 0,
                token: config.token || '',
                clientMode: config.clientMode === 'rtc' ? 'rtc' : 'live',
                expiresAt: config.expiresAt || 0,
                renewalTimer: 0,
                renewalPromise: null,
                renewalFailures: 0,
                currentFacingMode: (config.videoConfig && config.videoConfig.facingMode) || 'user',
                currentDeviceId: ''
            };

            function mediaTrackSettings(track) {
                if (!track || typeof track.getMediaStreamTrack !== 'function') {
                    return {};
                }
                const mediaTrack = track.getMediaStreamTrack();
                return mediaTrack && typeof mediaTrack.getSettings === 'function' ? mediaTrack.getSettings() : {};
            }

            function updateCurrentDeviceFromTrack() {
                const settings = mediaTrackSettings(state.videoTrack);
                if (settings.deviceId) {
                    state.currentDeviceId = settings.deviceId;
                }
                if (settings.facingMode) {
                    state.currentFacingMode = settings.facingMode;
                }
            }

            function mirrorForFacing(facingMode) {
                return facingMode !== 'environment' && config.initialVideoSource !== 'screen';
            }

            function playPreview() {
                if (localContainer && state.videoTrack && typeof state.videoTrack.play === 'function') {
                    state.videoTrack.play(localContainer, { mirror: mirrorForFacing(state.currentFacingMode) });
                }
            }

            function bindTrackLifecycle(track, kind, source) {
                if (!track) {
                    return;
                }
                function notifyEnded(reason) {
                    const detail = { kind: kind, source: source, reason: reason || 'ended' };
                    emit(root, 'track-ended', detail);
                    emit(root, 'track-state-change', Object.assign({}, detail, { state: 'ended' }));
                }
                if (typeof track.on === 'function') {
                    track.on('track-ended', function () { notifyEnded('agora-track-ended'); });
                }
                if (typeof track.getMediaStreamTrack === 'function') {
                    const mediaTrack = track.getMediaStreamTrack();
                    if (mediaTrack && typeof mediaTrack.addEventListener === 'function') {
                        mediaTrack.addEventListener('ended', function () { notifyEnded('media-track-ended'); }, { once: true });
                    }
                }
            }

            function stopAndMaybeClose(track, ownsSource) {
                if (!track) {
                    return;
                }
                if (typeof track.stop === 'function') {
                    track.stop();
                }
                if (ownsSource && typeof track.close === 'function') {
                    track.close();
                }
            }

            async function prepareMedia() {
                if (!window.AgoraRTC) {
                    throw new Error('Agora RTC SDK is unavailable.');
                }
                if (state.audioTrack && state.videoTrack) {
                    return state;
                }
                try {
                    if (!state.audioTrack) {
                        if (config.initialAudioMediaStreamTrack && typeof window.AgoraRTC.createCustomAudioTrack === 'function') {
                            state.audioTrack = window.AgoraRTC.createCustomAudioTrack({ mediaStreamTrack: config.initialAudioMediaStreamTrack });
                            state.audioTrackOwnsSource = false;
                        } else {
                            state.audioTrack = await window.AgoraRTC.createMicrophoneAudioTrack(config.audioConfig || {});
                            state.audioTrackOwnsSource = true;
                        }
                        bindTrackLifecycle(state.audioTrack, 'audio', config.initialAudioMediaStreamTrack ? (config.audioSource || 'studio-mix') : 'microphone');
                    }
                    if (!state.videoTrack) {
                        if (config.initialVideoMediaStreamTrack && typeof window.AgoraRTC.createCustomVideoTrack === 'function') {
                            state.videoTrack = window.AgoraRTC.createCustomVideoTrack({ mediaStreamTrack: config.initialVideoMediaStreamTrack });
                            state.videoTrackOwnsSource = false;
                        } else {
                            state.videoTrack = await window.AgoraRTC.createCameraVideoTrack(config.videoConfig || {});
                            state.videoTrackOwnsSource = true;
                        }
                        bindTrackLifecycle(state.videoTrack, 'video', config.initialVideoMediaStreamTrack ? (config.initialVideoSource || 'program') : 'camera');
                    }
                    updateCurrentDeviceFromTrack();
                    playPreview();
                    emit(root, 'media-prepared', {});
                    return state;
                } catch (error) {
                    stopAndMaybeClose(state.audioTrack, state.audioTrackOwnsSource);
                    stopAndMaybeClose(state.videoTrack, state.videoTrackOwnsSource);
                    state.audioTrack = null;
                    state.videoTrack = null;
                    emit(root, 'media-error', { error: error });
                    throw error;
                }
            }

            function bindClientEvents() {
                if (!state.client || typeof state.client.on !== 'function') {
                    return;
                }
                state.client.on('connection-state-change', function (current, previous, reason) {
                    emit(root, 'connection-state-change', { current: current, previous: previous, reason: reason || '' });
                });
                state.client.on('token-privilege-will-expire', function () {
                    renewTokenWithRetry().catch(function (error) {
                        emit(root, 'token-renewal-error', { error: error, expired: false });
                    });
                });
                state.client.on('token-privilege-did-expire', function () {
                    recoverExpiredToken().catch(function (error) {
                        emit(root, 'token-recovery-error', { error: error });
                    });
                });
            }

            function scheduleRenewal(expiresAt) {
                window.clearTimeout(state.renewalTimer);
                state.expiresAt = Number(expiresAt) || 0;
                if (!state.expiresAt) {
                    return;
                }
                const delay = Math.max(30000, (state.expiresAt * 1000) - Date.now() - 300000);
                state.renewalTimer = window.setTimeout(function () {
                    renewTokenWithRetry().catch(function (error) {
                        emit(root, 'token-renewal-error', { error: error, expired: false });
                    });
                }, delay);
            }

            async function requestServerToken() {
                if (typeof config.renewToken !== 'function') {
                    return null;
                }
                return config.renewToken({
                    videoId: config.videoId,
                    channelName: state.channelName,
                    uid: state.uid
                });
            }

            async function fetchFreshToken() {
                const response = await requestServerToken();
                const nextToken = response && response.token ? response.token : '';
                if (!nextToken) {
                    throw new Error('Token refresh did not return a token.');
                }
                state.token = nextToken;
                scheduleRenewal(response.expiresAt);
                return response;
            }

            async function renewToken(options) {
                const settings = options || {};
                if (state.renewalPromise) {
                    return state.renewalPromise;
                }
                state.renewalPromise = (async function () {
                    emit(root, 'token-renewal-start', { expired: !!settings.expired });
                    const response = await fetchFreshToken();
                    if (!state.client || typeof state.client.renewToken !== 'function') {
                        throw new Error('Live client cannot renew tokens.');
                    }
                    await state.client.renewToken(response.token);
                    state.renewalFailures = 0;
                    emit(root, 'token-renewed', { expiresAt: response.expiresAt || 0, expired: !!settings.expired });
                    return response;
                })();
                try {
                    return await state.renewalPromise;
                } catch (error) {
                    state.renewalFailures += 1;
                    emit(root, 'token-renewal-error', { error: error, expired: !!settings.expired, failures: state.renewalFailures });
                    throw error;
                } finally {
                    state.renewalPromise = null;
                }
            }

            async function renewTokenWithRetry() {
                let lastError = null;
                for (let attempt = 0; attempt < 3; attempt += 1) {
                    try {
                        return await renewToken({ expired: false });
                    } catch (error) {
                        lastError = error;
                        await sleep(1000 * Math.pow(2, attempt));
                    }
                }
                throw lastError || new Error('Token renewal failed.');
            }

            async function recoverExpiredToken() {
                if (state.renewalPromise) {
                    await state.renewalPromise.catch(function () {});
                }
                let lastError = null;
                for (let attempt = 0; attempt < 3; attempt += 1) {
                    try {
                        emit(root, 'token-recovery-start', { attempt: attempt + 1 });
                        const response = await fetchFreshToken();
                        await rejoinAndRepublish(response.token);
                        state.renewalFailures = 0;
                        emit(root, 'token-recovered', { attempt: attempt + 1, expiresAt: response.expiresAt || 0 });
                        return true;
                    } catch (error) {
                        lastError = error;
                        emit(root, 'token-recovery-error', { error: error, attempt: attempt + 1 });
                        await sleep(1000 * Math.pow(2, attempt));
                    }
                }
                throw lastError || new Error('Token recovery failed.');
            }

            async function createClientIfNeeded(clientMode) {
                if (!state.client) {
                    state.client = window.AgoraRTC.createClient({ mode: clientMode, codec: 'vp8' });
                    bindClientEvents();
                }
                if (clientMode === 'live' && typeof state.client.setClientRole === 'function') {
                    await state.client.setClientRole('host');
                }
            }

            async function connect(connectionConfig) {
                const merged = Object.assign({}, config, connectionConfig || {});
                await prepareMedia();
                state.appId = merged.appId;
                state.channelName = merged.channelName;
                state.uid = Number(merged.uid);
                state.token = merged.token || null;
                state.clientMode = merged.clientMode === 'rtc' ? 'rtc' : 'live';
                config.videoId = merged.videoId || config.videoId;

                try {
                    await createClientIfNeeded(state.clientMode);
                    await state.client.join(state.appId, state.channelName, state.token, state.uid);
                    state.joined = true;
                    await state.client.publish([state.audioTrack, state.videoTrack].filter(Boolean));
                    state.published = true;
                    scheduleRenewal(merged.expiresAt);
                    emit(root, 'published', { uid: state.uid, channelName: state.channelName });
                    return state;
                } catch (error) {
                    if (state.client && state.published) {
                        await state.client.unpublish().catch(function () {});
                    }
                    state.published = false;
                    if (state.client && state.joined) {
                        await state.client.leave().catch(function () {});
                    }
                    state.joined = false;
                    stopAndMaybeClose(state.audioTrack, state.audioTrackOwnsSource);
                    stopAndMaybeClose(state.videoTrack, state.videoTrackOwnsSource);
                    state.audioTrack = null;
                    state.videoTrack = null;
                    emit(root, 'connect-error', { error: error });
                    throw error;
                }
            }

            async function rejoinAndRepublish(token) {
                emit(root, 'token-rejoin-start', {});
                if (state.client && state.joined) {
                    if (state.published) {
                        await state.client.unpublish().catch(function () {});
                        state.published = false;
                    }
                    await state.client.leave().catch(function () {});
                    state.joined = false;
                }
                await createClientIfNeeded(state.clientMode);
                await state.client.join(state.appId, state.channelName, token || state.token || null, state.uid);
                state.joined = true;
                await state.client.publish([state.audioTrack, state.videoTrack].filter(Boolean));
                state.published = true;
                emit(root, 'published', { uid: state.uid, channelName: state.channelName, rejoined: true });
            }

            async function start() {
                await prepareMedia();
                return connect(config);
            }

            async function enumerateVideoInputs() {
                if (window.AgoraRTC && typeof window.AgoraRTC.getCameras === 'function') {
                    return window.AgoraRTC.getCameras();
                }
                if (navigator.mediaDevices && typeof navigator.mediaDevices.enumerateDevices === 'function') {
                    const devices = await navigator.mediaDevices.enumerateDevices();
                    return devices.filter(function (device) { return device.kind === 'videoinput'; });
                }
                return [];
            }

            async function switchCamera(preferredFacingMode) {
                if (!state.videoTrack || !state.videoTrackOwnsSource) {
                    return false;
                }
                const targetFacing = preferredFacingMode || (state.currentFacingMode === 'user' ? 'environment' : 'user');
                const previousTrack = state.videoTrack;
                const previousFacing = state.currentFacingMode;
                const previousDevice = state.currentDeviceId;

                try {
                    if (typeof state.videoTrack.setDevice === 'function') {
                        await state.videoTrack.setDevice({ facingMode: targetFacing });
                        state.currentFacingMode = targetFacing;
                        updateCurrentDeviceFromTrack();
                        playPreview();
                        emit(root, 'camera-switched', getCurrentCameraState());
                        return true;
                    }
                } catch (error) {
                    // Continue to device-id fallback.
                }

                try {
                    const devices = await enumerateVideoInputs();
                    const next = devices.find(function (device) {
                        return device.deviceId && device.deviceId !== previousDevice;
                    });
                    if (next && typeof state.videoTrack.setDevice === 'function') {
                        await state.videoTrack.setDevice(next.deviceId);
                        state.currentFacingMode = targetFacing;
                        updateCurrentDeviceFromTrack();
                        state.currentDeviceId = next.deviceId;
                        playPreview();
                        emit(root, 'camera-switched', getCurrentCameraState());
                        return true;
                    }
                } catch (error) {
                    // Continue to replacement-track fallback.
                }

                let replacement = null;
                try {
                    replacement = await window.AgoraRTC.createCameraVideoTrack(Object.assign({}, config.videoConfig || {}, { facingMode: targetFacing }));
                    bindTrackLifecycle(replacement, 'video', 'camera');
                    if (state.client && state.published) {
                        await state.client.unpublish(previousTrack).catch(function () {});
                        try {
                            await state.client.publish(replacement);
                        } catch (publishError) {
                            await state.client.publish(previousTrack).catch(function () {});
                            throw publishError;
                        }
                    }
                    state.videoTrack = replacement;
                    state.currentFacingMode = targetFacing;
                    updateCurrentDeviceFromTrack();
                    playPreview();
                    stopAndMaybeClose(previousTrack, true);
                    emit(root, 'camera-switched', getCurrentCameraState());
                    return true;
                } catch (error) {
                    stopAndMaybeClose(replacement, true);
                    state.videoTrack = previousTrack;
                    state.currentFacingMode = previousFacing;
                    state.currentDeviceId = previousDevice;
                    playPreview();
                    emit(root, 'camera-switch-error', { error: error, facingMode: targetFacing });
                    throw error;
                }
            }

            async function stop() {
                window.clearTimeout(state.renewalTimer);
                if (state.client && state.published) {
                    await state.client.unpublish().catch(function () {});
                }
                state.published = false;
                if (state.client && state.joined) {
                    await state.client.leave().catch(function () {});
                }
                state.joined = false;
                stopAndMaybeClose(state.audioTrack, state.audioTrackOwnsSource);
                stopAndMaybeClose(state.videoTrack, state.videoTrackOwnsSource);
                state.audioTrack = null;
                state.videoTrack = null;
                emit(root, 'ended', {});
            }

            function getCurrentCameraState() {
                return {
                    facingMode: state.currentFacingMode,
                    deviceId: state.currentDeviceId
                };
            }

            return {
                start: start,
                prepareMedia: prepareMedia,
                connect: connect,
                stop: stop,
                renewToken: renewToken,
                switchCamera: switchCamera,
                isReadyToPublish: function () {
                    return Boolean(state.client && state.joined && state.published && (!state.client.connectionState || state.client.connectionState === 'CONNECTED'));
                },
                muteAudio: function (muted) {
                    return state.audioTrack && state.audioTrack.setEnabled(!muted);
                },
                muteVideo: function (muted) {
                    return state.videoTrack && state.videoTrack.setEnabled(!muted);
                },
                getCurrentCameraState: getCurrentCameraState,
                getAudioTrackId: function () {
                    if (state.audioTrack && typeof state.audioTrack.getMediaStreamTrack === 'function') {
                        const track = state.audioTrack.getMediaStreamTrack();
                        return track ? track.id : '';
                    }
                    return '';
                },
                getLocalMediaStream: function () {
                    const tracks = [];
                    if (state.audioTrack && typeof state.audioTrack.getMediaStreamTrack === 'function') {
                        tracks.push(state.audioTrack.getMediaStreamTrack());
                    }
                    if (state.videoTrack && typeof state.videoTrack.getMediaStreamTrack === 'function') {
                        tracks.push(state.videoTrack.getMediaStreamTrack());
                    }
                    return tracks.length ? new MediaStream(tracks) : null;
                },
                getState: function () {
                    return Object.assign({}, state, {
                        connectionState: state.client ? state.client.connectionState || '' : ''
                    });
                }
            };
        }
    };
})(window);
