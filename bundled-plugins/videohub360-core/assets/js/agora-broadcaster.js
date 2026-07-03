(function (window) {
    'use strict';

    function emit(target, name, detail) {
        (target || window).dispatchEvent(new CustomEvent('vh360:agora-broadcaster:' + name, { detail: detail || {} }));
    }

    window.VH360AgoraBroadcaster = {
        create: function (config) {
            const state = { client: null, audioTrack: null, videoTrack: null, joined: false, published: false };
            const root = config.container || document;
            const localContainer = config.localContainer || root.querySelector('[data-agora-local-preview]') || root;

            async function start() {
                if (!window.AgoraRTC) {
                    throw new Error('Agora RTC SDK is unavailable.');
                }
                const clientMode = config.clientMode === 'rtc' ? 'rtc' : 'live';
                state.client = window.AgoraRTC.createClient({ mode: clientMode, codec: 'vp8' });
                if (clientMode === 'live' && typeof state.client.setClientRole === 'function') {
                    await state.client.setClientRole('host');
                }
                state.audioTrack = await window.AgoraRTC.createMicrophoneAudioTrack(config.audioConfig || {});
                state.videoTrack = await window.AgoraRTC.createCameraVideoTrack(config.videoConfig || {});
                if (localContainer) {
                    state.videoTrack.play(localContainer);
                }
                await state.client.join(config.appId, config.channelName, config.token || null, Number(config.uid));
                state.joined = true;
                await state.client.publish([state.audioTrack, state.videoTrack]);
                state.published = true;
                emit(root, 'published', { uid: config.uid, channelName: config.channelName });
                return state;
            }

            async function stop() {
                if (state.client && state.published) {
                    await state.client.unpublish().catch(function () {});
                }
                ['audioTrack', 'videoTrack'].forEach(function (key) {
                    if (state[key]) {
                        state[key].stop();
                        state[key].close();
                        state[key] = null;
                    }
                });
                if (state.client && state.joined) {
                    await state.client.leave().catch(function () {});
                }
                state.joined = false;
                state.published = false;
                emit(root, 'ended', {});
            }

            async function replaceVideoMediaStreamTrack(mediaStreamTrack) {
                if (!window.AgoraRTC || !state.client || !state.joined || !mediaStreamTrack) {
                    return false;
                }
                if (typeof window.AgoraRTC.createCustomVideoTrack !== 'function') {
                    throw new Error('Agora custom video tracks are unavailable in this browser.');
                }
                const nextTrack = window.AgoraRTC.createCustomVideoTrack({ mediaStreamTrack: mediaStreamTrack });
                const oldTrack = state.videoTrack;
                const wasPublished = state.published;

                try {
                    if (wasPublished && oldTrack) {
                        await state.client.unpublish(oldTrack);
                    }
                    await state.client.publish(nextTrack);
                    state.videoTrack = nextTrack;
                    state.published = true;
                    if (localContainer && typeof nextTrack.play === 'function') {
                        nextTrack.play(localContainer);
                    }
                    if (oldTrack) {
                        oldTrack.stop();
                        oldTrack.close();
                    }
                    emit(root, 'video-replaced', { uid: config.uid, channelName: config.channelName });
                    return true;
                } catch (error) {
                    nextTrack.stop();
                    nextTrack.close();
                    state.videoTrack = oldTrack;
                    state.published = wasPublished;
                    if (wasPublished && oldTrack) {
                        await state.client.publish(oldTrack).catch(function () {});
                        if (localContainer && typeof oldTrack.play === 'function') {
                            oldTrack.play(localContainer);
                        }
                    }
                    throw error;
                }
            }

            return {
                start: start,
                stop: stop,
                replaceVideoMediaStreamTrack: replaceVideoMediaStreamTrack,
                muteAudio: function (muted) { return state.audioTrack && state.audioTrack.setEnabled(!muted); },
                muteVideo: function (muted) { return state.videoTrack && state.videoTrack.setEnabled(!muted); },
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
                getState: function () { return Object.assign({}, state); }
            };
        }
    };
})(window);
