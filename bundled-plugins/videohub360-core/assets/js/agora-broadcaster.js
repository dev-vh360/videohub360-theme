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
            let currentLocalContainer = config.localContainer || root.querySelector('[data-agora-local-preview]') || root;
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
                recoveryPromise: null,
                renewalFailures: 0,
                active: true,
                stopping: false,
                generation: 0,
                pendingSdkPromises: new Set(),
                currentFacingMode: (config.videoConfig && config.videoConfig.facingMode) || 'user',
                currentDeviceId: '',
                receiveRemoteParticipants: !!config.receiveRemoteParticipants,
                remoteParticipants: new Map(),
                autoplayFailureBound: false,
                autoplayPreviousCallback: null,
                autoplayWrapper: null
            };

            function makeCancellationError() {
                const error = new Error('Broadcaster operation cancelled.');
                error.name = 'VH360BroadcasterOperationCancelled';
                error.isOperationCancelled = true;
                return error;
            }

            function isOperationCancelled(error) {
                return Boolean(error && (error.isOperationCancelled || error.name === 'VH360BroadcasterOperationCancelled'));
            }

            function trackSdkPromise(promise) {
                state.pendingSdkPromises.add(promise);
                promise.finally(function () {
                    state.pendingSdkPromises.delete(promise);
                }).catch(function () {});
                return promise;
            }

            function currentGeneration() {
                return state.generation;
            }

            function isOperationCurrent(generation) {
                return state.active && !state.stopping && generation === state.generation;
            }

            function assertOperationCurrent(generation) {
                if (!isOperationCurrent(generation)) {
                    throw makeCancellationError();
                }
            }



            function normalizeRemoteUid(uid) {
                return uid === null || typeof uid === 'undefined' ? '' : String(uid);
            }

            function localUidString() {
                return normalizeRemoteUid(state.uid);
            }

            function stopRemoteVideoPlayback(record) {
                if (!record) {
                    return;
                }
                if (record.videoTrack && typeof record.videoTrack.stop === 'function') {
                    record.videoTrack.stop();
                }
                record.videoPlaying = false;
                record.videoPlaybackTrack = null;
                record.videoContainer = null;
            }

            function stopRemoteAudioPlayback(record) {
                if (!record) {
                    return;
                }
                if (record.audioTrack && typeof record.audioTrack.stop === 'function') {
                    record.audioTrack.stop();
                }
                record.audioPlaying = false;
                record.audioPlaybackTrack = null;
            }

            function stopRemotePlayback(record) {
                stopRemoteVideoPlayback(record);
                stopRemoteAudioPlayback(record);
            }

            function clearRemoteParticipants() {
                state.remoteParticipants.forEach(function (record) {
                    stopRemotePlayback(record);
                });
                state.remoteParticipants.clear();
            }

            function resetRemoteSubscriptions(emitReset) {
                state.remoteParticipants.forEach(function (record) {
                    stopRemotePlayback(record);
                });
                state.remoteParticipants.clear();
                if (emitReset) {
                    emit(root, 'remote-participants-reset', {});
                }
            }

            function remoteRecord(uid, user) {
                const key = normalizeRemoteUid(uid);
                let record = state.remoteParticipants.get(key);
                if (!record) {
                    record = {
                        uid: key,
                        user: user || null,
                        audioTrack: null,
                        videoTrack: null,
                        audioPublished: false,
                        videoPublished: false,
                        audioPlaying: false,
                        videoPlaying: false,
                        videoQuality: 'auto',
                        videoContainer: null,
                        videoPlaybackTrack: null,
                        audioPlaybackTrack: null,
                        dualStreamEnabled: false,
                        subscriptionState: {}
                    };
                    state.remoteParticipants.set(key, record);
                }
                if (user) {
                    record.user = user;
                }
                return record;
            }

            function remoteDetail(record, mediaType) {
                return {
                    uid: record.uid,
                    mediaType: mediaType || '',
                    audioAvailable: !!record.audioTrack,
                    videoAvailable: !!record.videoTrack,
                    audioTrack: record.audioTrack || null,
                    videoTrack: record.videoTrack || null
                };
            }

            async function handleRemoteUserPublished(user, mediaType) {
                const generation = currentGeneration();
                if (!state.receiveRemoteParticipants || !user || !mediaType || !state.client) {
                    return;
                }
                const uid = normalizeRemoteUid(user.uid);
                if (!uid || uid === localUidString() || !isOperationCurrent(generation)) {
                    return;
                }
                const record = remoteRecord(uid, user);
                const trackKey = mediaType + 'Track';
                const currentTrack = mediaType === 'video' ? user.videoTrack : user.audioTrack;
                if (record.subscriptionState[mediaType] === 'subscribing') {
                    return;
                }
                if (record.subscriptionState[mediaType] === 'subscribed' && record[trackKey] && (!currentTrack || record[trackKey] === currentTrack)) {
                    return;
                }
                record.subscriptionState[mediaType] = 'subscribing';
                try {
                    await trackSdkPromise(state.client.subscribe(user, mediaType));
                    if (!isOperationCurrent(generation) || !state.receiveRemoteParticipants) {
                        const lateTrack = mediaType === 'video' ? user.videoTrack : user.audioTrack;
                        if (lateTrack && typeof lateTrack.stop === 'function') {
                            lateTrack.stop();
                        }
                        throw makeCancellationError();
                    }
                    record.user = user;
                    if (mediaType === 'video') {
                        if (record.videoTrack && record.videoTrack !== user.videoTrack) {
                            stopRemoteVideoPlayback(record);
                        }
                        record.videoTrack = user.videoTrack || null;
                        record.videoPublished = !!record.videoTrack;
                    } else {
                        if (record.audioTrack && record.audioTrack !== user.audioTrack) {
                            stopRemoteAudioPlayback(record);
                        }
                        record.audioTrack = user.audioTrack || null;
                        record.audioPublished = !!record.audioTrack;
                    }
                    record.subscriptionState[mediaType] = 'subscribed';
                    emit(root, 'remote-participant-published', remoteDetail(record, mediaType));
                    emit(root, mediaType === 'video' ? 'remote-video-published' : 'remote-audio-published', remoteDetail(record, mediaType));
                } catch (error) {
                    record.subscriptionState[mediaType] = 'error';
                    if (!isOperationCancelled(error)) {
                        emit(root, 'remote-subscription-error', { uid: uid, mediaType: mediaType, error: error });
                    }
                }
            }

            function handleRemoteUserUnpublished(user, mediaType) {
                if (!state.receiveRemoteParticipants || !user || !mediaType) {
                    return;
                }
                const uid = normalizeRemoteUid(user.uid);
                const record = state.remoteParticipants.get(uid);
                if (!record) {
                    return;
                }
                if (mediaType === 'video') {
                    stopRemoteVideoPlayback(record);
                    record.videoTrack = null;
                    record.videoPublished = false;
                } else {
                    stopRemoteAudioPlayback(record);
                    record.audioTrack = null;
                    record.audioPublished = false;
                }
                record.subscriptionState[mediaType] = 'unpublished';
                emit(root, 'remote-track-unpublished', remoteDetail(record, mediaType));
            }

            function handleRemoteUserLeft(user) {
                if (!state.receiveRemoteParticipants || !user) {
                    return;
                }
                const uid = normalizeRemoteUid(user.uid);
                const record = state.remoteParticipants.get(uid);
                if (!record) {
                    return;
                }
                stopRemotePlayback(record);
                state.remoteParticipants.delete(uid);
                emit(root, 'remote-participant-left', { uid: uid });
            }

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

            function playPreview(generation) {
                if (typeof generation === 'number' && !isOperationCurrent(generation)) {
                    return;
                }
                if (currentLocalContainer && state.videoTrack && typeof state.videoTrack.play === 'function') {
                    try {
                        state.videoTrack.play(currentLocalContainer, { mirror: mirrorForFacing(state.currentFacingMode) });
                    } catch (error) {
                        emit(root, 'local-preview-error', { error: error });
                        throw error;
                    }
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
                const generation = currentGeneration();
                assertOperationCurrent(generation);
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
                            const audioTrack = await window.AgoraRTC.createMicrophoneAudioTrack(config.audioConfig || {});
                            if (!isOperationCurrent(generation)) {
                                stopAndMaybeClose(audioTrack, true);
                                throw makeCancellationError();
                            }
                            state.audioTrack = audioTrack;
                            state.audioTrackOwnsSource = true;
                        }
                        bindTrackLifecycle(state.audioTrack, 'audio', config.initialAudioMediaStreamTrack ? (config.audioSource || 'studio-mix') : 'microphone');
                    }
                    if (!state.videoTrack) {
                        if (config.initialVideoMediaStreamTrack && typeof window.AgoraRTC.createCustomVideoTrack === 'function') {
                            state.videoTrack = window.AgoraRTC.createCustomVideoTrack({ mediaStreamTrack: config.initialVideoMediaStreamTrack });
                            state.videoTrackOwnsSource = false;
                        } else {
                            const videoTrack = await window.AgoraRTC.createCameraVideoTrack(config.videoConfig || {});
                            if (!isOperationCurrent(generation)) {
                                stopAndMaybeClose(videoTrack, true);
                                throw makeCancellationError();
                            }
                            state.videoTrack = videoTrack;
                            state.videoTrackOwnsSource = true;
                        }
                        bindTrackLifecycle(state.videoTrack, 'video', config.initialVideoMediaStreamTrack ? (config.initialVideoSource || 'program') : 'camera');
                    }
                    assertOperationCurrent(generation);
                    updateCurrentDeviceFromTrack();
                    playPreview(generation);
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


            function handleAutoplayFailed() {
                if (state.receiveRemoteParticipants && isOperationCurrent(currentGeneration())) {
                    state.remoteParticipants.forEach(function (record) {
                        if (record.audioTrack) {
                            record.audioPlaying = false;
                            record.audioPlaybackTrack = null;
                        }
                    });
                    emit(root, 'remote-audio-blocked', {});
                }
            }

            function bindAutoplayFailure() {
                if (!state.receiveRemoteParticipants || state.autoplayFailureBound || !window.AgoraRTC) {
                    return;
                }
                state.autoplayPreviousCallback = typeof window.AgoraRTC.onAutoplayFailed === 'function' ? window.AgoraRTC.onAutoplayFailed : null;
                state.autoplayWrapper = function () {
                    if (state.autoplayPreviousCallback) {
                        state.autoplayPreviousCallback.apply(window.AgoraRTC, arguments);
                    }
                    handleAutoplayFailed();
                };
                window.AgoraRTC.onAutoplayFailed = state.autoplayWrapper;
                state.autoplayFailureBound = true;
            }

            function unbindAutoplayFailure() {
                if (!state.autoplayFailureBound || !window.AgoraRTC || !state.autoplayWrapper) {
                    return;
                }
                if (window.AgoraRTC.onAutoplayFailed === state.autoplayWrapper) {
                    window.AgoraRTC.onAutoplayFailed = state.autoplayPreviousCallback;
                }
                state.autoplayFailureBound = false;
                state.autoplayWrapper = null;
                state.autoplayPreviousCallback = null;
            }

            async function syncRemotePublishedUsers(generation) {
                if (!state.receiveRemoteParticipants || !state.client || !Array.isArray(state.client.remoteUsers)) {
                    return;
                }
                for (const user of state.client.remoteUsers) {
                    assertOperationCurrent(generation);
                    if (!user || normalizeRemoteUid(user.uid) === localUidString()) {
                        continue;
                    }
                    if (user.hasVideo || user.videoTrack) {
                        await handleRemoteUserPublished(user, 'video');
                    }
                    if (user.hasAudio || user.audioTrack) {
                        await handleRemoteUserPublished(user, 'audio');
                    }
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
                        if (!isOperationCancelled(error)) {
                            emit(root, 'token-renewal-error', { error: error, expired: false });
                        }
                    });
                });
                state.client.on('token-privilege-did-expire', function () {
                    recoverExpiredToken().catch(function (error) {
                        if (!isOperationCancelled(error)) {
                            emit(root, 'token-recovery-error', { error: error });
                        }
                    });
                });
                if (state.receiveRemoteParticipants) {
                    state.client.on('user-joined', function (user) {
                        if (user && normalizeRemoteUid(user.uid) !== localUidString()) {
                            emit(root, 'remote-user-joined', { uid: normalizeRemoteUid(user.uid) });
                        }
                    });
                    state.client.on('user-published', function (user, mediaType) {
                        handleRemoteUserPublished(user, mediaType).catch(function () {});
                    });
                    state.client.on('user-unpublished', handleRemoteUserUnpublished);
                    state.client.on('user-left', handleRemoteUserLeft);
                }
            }

            function scheduleRenewal(expiresAt, generation) {
                window.clearTimeout(state.renewalTimer);
                if (typeof generation === 'number' && !isOperationCurrent(generation)) {
                    return;
                }
                if (!state.active || state.stopping) {
                    return;
                }
                state.expiresAt = Number(expiresAt) || 0;
                if (!state.expiresAt) {
                    return;
                }
                const delay = Math.max(30000, (state.expiresAt * 1000) - Date.now() - 300000);
                state.renewalTimer = window.setTimeout(function () {
                    renewTokenWithRetry().catch(function (error) {
                        if (!isOperationCancelled(error)) {
                            emit(root, 'token-renewal-error', { error: error, expired: false });
                        }
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

            async function fetchFreshToken(generation) {
                assertOperationCurrent(generation);
                const response = await requestServerToken();
                assertOperationCurrent(generation);
                const nextToken = response && response.token ? response.token : '';
                if (!nextToken) {
                    throw new Error('Token refresh did not return a token.');
                }
                state.token = nextToken;
                scheduleRenewal(response.expiresAt, generation);
                return response;
            }

            async function renewToken(options) {
                const settings = options || {};
                const generation = currentGeneration();
                assertOperationCurrent(generation);
                if (state.renewalPromise) {
                    return state.renewalPromise;
                }
                state.renewalPromise = (async function () {
                    emit(root, 'token-renewal-start', { expired: !!settings.expired });
                    const response = await fetchFreshToken(generation);
                    if (!state.client || typeof state.client.renewToken !== 'function') {
                        throw new Error('Live client cannot renew tokens.');
                    }
                    assertOperationCurrent(generation);
                    await state.client.renewToken(response.token);
                    assertOperationCurrent(generation);
                    state.renewalFailures = 0;
                    emit(root, 'token-renewed', { expiresAt: response.expiresAt || 0, expired: !!settings.expired });
                    return response;
                })();
                try {
                    return await state.renewalPromise;
                } catch (error) {
                    if (!isOperationCancelled(error)) {
                        state.renewalFailures += 1;
                        emit(root, 'token-renewal-error', { error: error, expired: !!settings.expired, failures: state.renewalFailures });
                    }
                    throw error;
                } finally {
                    state.renewalPromise = null;
                }
            }

            async function renewTokenWithRetry() {
                const generation = currentGeneration();
                let lastError = null;
                for (let attempt = 0; attempt < 3; attempt += 1) {
                    try {
                        return await renewToken({ expired: false });
                    } catch (error) {
                        if (isOperationCancelled(error)) {
                            throw error;
                        }
                        lastError = error;
                        await sleep(1000 * Math.pow(2, attempt));
                        assertOperationCurrent(generation);
                    }
                }
                throw lastError || new Error('Token renewal failed.');
            }

            async function recoverExpiredToken() {
                if (state.recoveryPromise) {
                    return state.recoveryPromise;
                }
                const generation = currentGeneration();
                assertOperationCurrent(generation);
                state.recoveryPromise = (async function () {
                    if (state.renewalPromise) {
                        await state.renewalPromise.catch(function () {});
                    }
                    let lastError = null;
                    for (let attempt = 0; attempt < 3; attempt += 1) {
                        try {
                            assertOperationCurrent(generation);
                            emit(root, 'token-recovery-start', { attempt: attempt + 1 });
                            const response = await fetchFreshToken(generation);
                            await rejoinAndRepublish(response.token, generation);
                            state.renewalFailures = 0;
                            emit(root, 'token-recovered', { attempt: attempt + 1, expiresAt: response.expiresAt || 0 });
                            return true;
                        } catch (error) {
                            if (isOperationCancelled(error)) {
                                throw error;
                            }
                            lastError = error;
                            emit(root, 'token-recovery-error', { error: error, attempt: attempt + 1 });
                            await sleep(1000 * Math.pow(2, attempt));
                        }
                    }
                    throw lastError || new Error('Token recovery failed.');
                })();
                try {
                    return await state.recoveryPromise;
                } finally {
                    state.recoveryPromise = null;
                }
            }

            async function createClientIfNeeded(clientMode, generation) {
                assertOperationCurrent(generation);
                if (!state.client) {
                    state.client = window.AgoraRTC.createClient({ mode: clientMode, codec: 'vp8' });
                    bindClientEvents();
                    bindAutoplayFailure();
                }
                assertOperationCurrent(generation);
                if (clientMode === 'live' && typeof state.client.setClientRole === 'function') {
                    await state.client.setClientRole('host');
                    assertOperationCurrent(generation);
                }
            }

            async function connect(connectionConfig) {
                const generation = currentGeneration();
                assertOperationCurrent(generation);
                const merged = Object.assign({}, config, connectionConfig || {});
                await prepareMedia();
                assertOperationCurrent(generation);
                state.appId = merged.appId;
                state.channelName = merged.channelName;
                state.uid = Number(merged.uid);
                state.token = merged.token || null;
                state.clientMode = merged.clientMode === 'rtc' ? 'rtc' : 'live';
                config.videoId = merged.videoId || config.videoId;

                try {
                    await createClientIfNeeded(state.clientMode, generation);
                    await trackSdkPromise(state.client.join(state.appId, state.channelName, state.token, state.uid));
                    if (!isOperationCurrent(generation)) {
                        await state.client.leave().catch(function () {});
                        state.joined = false;
                        throw makeCancellationError();
                    }
                    state.joined = true;
                    const publishTracks = [state.audioTrack, state.videoTrack].filter(Boolean);
                    await trackSdkPromise(state.client.publish(publishTracks));
                    if (!isOperationCurrent(generation)) {
                        await state.client.unpublish(publishTracks).catch(function () {});
                        state.published = false;
                        await state.client.leave().catch(function () {});
                        state.joined = false;
                        throw makeCancellationError();
                    }
                    state.published = true;
                    await syncRemotePublishedUsers(generation);
                    scheduleRenewal(merged.expiresAt, generation);
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

            async function rejoinAndRepublish(token, generation) {
                assertOperationCurrent(generation);
                if (!state.audioTrack || !state.videoTrack) {
                    throw new Error('Cannot rejoin without prepared local tracks.');
                }
                emit(root, 'token-rejoin-start', {});
                resetRemoteSubscriptions(true);
                if (state.client && state.joined) {
                    if (state.published) {
                        await state.client.unpublish().catch(function () {});
                        state.published = false;
                    }
                    await state.client.leave().catch(function () {});
                    state.joined = false;
                }
                assertOperationCurrent(generation);
                await createClientIfNeeded(state.clientMode, generation);
                await trackSdkPromise(state.client.join(state.appId, state.channelName, token || state.token || null, state.uid));
                if (!isOperationCurrent(generation)) {
                    await state.client.leave().catch(function () {});
                    state.joined = false;
                    throw makeCancellationError();
                }
                state.joined = true;
                const publishTracks = [state.audioTrack, state.videoTrack].filter(Boolean);
                await trackSdkPromise(state.client.publish(publishTracks));
                if (!isOperationCurrent(generation)) {
                    await state.client.unpublish(publishTracks).catch(function () {});
                    state.published = false;
                    await state.client.leave().catch(function () {});
                    state.joined = false;
                    throw makeCancellationError();
                }
                state.published = true;
                await syncRemotePublishedUsers(generation);
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
                const generation = currentGeneration();
                assertOperationCurrent(generation);
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
                        assertOperationCurrent(generation);
                        state.currentFacingMode = targetFacing;
                        updateCurrentDeviceFromTrack();
                        playPreview(generation);
                        emit(root, 'camera-switched', getCurrentCameraState());
                        return true;
                    }
                } catch (error) {
                    if (!isOperationCurrent(generation)) {
                        return false;
                    }
                    // Continue to device-id fallback.
                }

                try {
                    const devices = await enumerateVideoInputs();
                    assertOperationCurrent(generation);
                    const next = devices.find(function (device) {
                        return device.deviceId && device.deviceId !== previousDevice;
                    });
                    if (next && typeof state.videoTrack.setDevice === 'function') {
                        await state.videoTrack.setDevice(next.deviceId);
                        assertOperationCurrent(generation);
                        state.currentFacingMode = targetFacing;
                        updateCurrentDeviceFromTrack();
                        state.currentDeviceId = next.deviceId;
                        playPreview(generation);
                        emit(root, 'camera-switched', getCurrentCameraState());
                        return true;
                    }
                } catch (error) {
                    if (!isOperationCurrent(generation)) {
                        return false;
                    }
                    // Continue to replacement-track fallback.
                }

                let replacement = null;
                try {
                    replacement = await window.AgoraRTC.createCameraVideoTrack(Object.assign({}, config.videoConfig || {}, { facingMode: targetFacing }));
                    if (!isOperationCurrent(generation)) {
                        stopAndMaybeClose(replacement, true);
                        return false;
                    }
                    bindTrackLifecycle(replacement, 'video', 'camera');
                    if (state.client && state.published) {
                        await state.client.unpublish(previousTrack).catch(function () {});
                        assertOperationCurrent(generation);
                        try {
                            await trackSdkPromise(state.client.publish(replacement));
                            if (!isOperationCurrent(generation)) {
                                await state.client.unpublish(replacement).catch(function () {});
                                stopAndMaybeClose(replacement, true);
                                throw makeCancellationError();
                            }
                        } catch (publishError) {
                            if (isOperationCurrent(generation)) {
                                await state.client.publish(previousTrack).catch(function () {});
                            }
                            throw publishError;
                        }
                    }
                    state.videoTrack = replacement;
                    state.currentFacingMode = targetFacing;
                    assertOperationCurrent(generation);
                    updateCurrentDeviceFromTrack();
                    playPreview(generation);
                    stopAndMaybeClose(previousTrack, true);
                    emit(root, 'camera-switched', getCurrentCameraState());
                    return true;
                } catch (error) {
                    stopAndMaybeClose(replacement, true);
                    if (!isOperationCurrent(generation)) {
                        return false;
                    }
                    state.videoTrack = previousTrack;
                    state.currentFacingMode = previousFacing;
                    state.currentDeviceId = previousDevice;
                    playPreview(generation);
                    emit(root, 'camera-switch-error', { error: error, facingMode: targetFacing });
                    throw error;
                }
            }


            function setLocalPreviewContainer(container) {
                const generation = currentGeneration();
                if (!container || !isOperationCurrent(generation)) {
                    return false;
                }
                if (!state.videoTrack || typeof state.videoTrack.play !== 'function') {
                    return false;
                }
                try {
                    if (typeof state.videoTrack.stop === 'function') {
                        state.videoTrack.stop();
                    }
                    currentLocalContainer = container;
                    playPreview(generation);
                    emit(root, 'local-preview-attached', getCurrentCameraState());
                    return true;
                } catch (error) {
                    return false;
                }
            }

            function playRemoteVideo(uid, container) {
                const record = state.remoteParticipants.get(normalizeRemoteUid(uid));
                if (!record || !record.videoTrack || !container || !isOperationCurrent(currentGeneration())) {
                    return false;
                }
                if (record.videoPlaying && record.videoPlaybackTrack === record.videoTrack && record.videoContainer === container) {
                    return true;
                }
                try {
                    if (record.videoPlaying && record.videoTrack && typeof record.videoTrack.stop === 'function') {
                        record.videoTrack.stop();
                    }
                    record.videoTrack.play(container);
                    record.videoPlaying = true;
                    record.videoPlaybackTrack = record.videoTrack;
                    record.videoContainer = container;
                    return true;
                } catch (error) {
                    record.videoPlaying = false;
                    emit(root, 'remote-video-error', { uid: record.uid, error: error });
                    return false;
                }
            }

            function stopRemoteVideo(uid) {
                const record = state.remoteParticipants.get(normalizeRemoteUid(uid));
                if (record && record.videoTrack) {
                    stopRemoteVideoPlayback(record);
                    return true;
                }
                return false;
            }

            function playRemoteAudio(uid) {
                const record = state.remoteParticipants.get(normalizeRemoteUid(uid));
                if (!record || !record.audioTrack || !isOperationCurrent(currentGeneration())) {
                    return false;
                }
                if (record.audioPlaying && record.audioPlaybackTrack === record.audioTrack) {
                    return true;
                }
                try {
                    record.audioTrack.play();
                    record.audioPlaying = true;
                    record.audioPlaybackTrack = record.audioTrack;
                    return true;
                } catch (error) {
                    record.audioPlaying = false;
                    record.audioPlaybackTrack = null;
                    emit(root, 'remote-audio-blocked', { uid: record.uid, error: error });
                    return false;
                }
            }

            function stopRemoteAudio(uid) {
                const record = state.remoteParticipants.get(normalizeRemoteUid(uid));
                if (record && record.audioTrack) {
                    stopRemoteAudioPlayback(record);
                    return true;
                }
                return false;
            }

            async function setRemoteVideoQuality(uid, quality) {
                const record = state.remoteParticipants.get(normalizeRemoteUid(uid));
                if (!record || !record.videoTrack || !record.dualStreamEnabled || !state.client || typeof state.client.setRemoteVideoStreamType !== 'function') {
                    return false;
                }
                const streamType = quality === 'high' ? 0 : 1;
                try {
                    await state.client.setRemoteVideoStreamType(record.uid, streamType);
                    record.videoQuality = quality || 'low';
                    return true;
                } catch (error) {
                    emit(root, 'remote-video-quality-error', { uid: record.uid, error: error });
                    return false;
                }
            }

            function getRemoteParticipants() {
                return Array.from(state.remoteParticipants.values()).map(function (record) {
                    return remoteDetail(record);
                });
            }

            async function stop() {
                state.active = false;
                state.stopping = true;
                state.generation += 1;
                window.clearTimeout(state.renewalTimer);
                state.renewalTimer = 0;
                if (state.pendingSdkPromises.size) {
                    await Promise.allSettled(Array.from(state.pendingSdkPromises));
                }
                if (state.client && state.published) {
                    await state.client.unpublish().catch(function () {});
                }
                state.published = false;
                if (state.client && (state.joined || state.client.connectionState === 'CONNECTED' || state.client.connectionState === 'CONNECTING')) {
                    await state.client.leave().catch(function () {});
                }
                state.joined = false;
                clearRemoteParticipants();
                unbindAutoplayFailure();
                stopAndMaybeClose(state.audioTrack, state.audioTrackOwnsSource);
                stopAndMaybeClose(state.videoTrack, state.videoTrackOwnsSource);
                state.audioTrack = null;
                state.videoTrack = null;
                state.recoveryPromise = null;
                state.renewalPromise = null;
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
                setLocalPreviewContainer: setLocalPreviewContainer,
                attachLocalPreview: setLocalPreviewContainer,
                playRemoteVideo: playRemoteVideo,
                stopRemoteVideo: stopRemoteVideo,
                playRemoteAudio: playRemoteAudio,
                stopRemoteAudio: stopRemoteAudio,
                setRemoteVideoQuality: setRemoteVideoQuality,
                getRemoteParticipants: getRemoteParticipants,
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
