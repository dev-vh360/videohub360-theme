(function (window) {
    'use strict';

    function emit(target, name, detail) {
        (target || window).dispatchEvent(new CustomEvent('vh360:agora-broadcaster:' + name, { detail: detail || {} }));
    }

    window.VH360AgoraBroadcaster = {
        create: function (config) {
            const state = { client: null, audioTrack: null, videoTrack: null, videoTrackOwnsSource: true, joined: false, published: false };
            const root = config.container || document;
            const localContainer = config.localContainer || root.querySelector('[data-agora-local-preview]') || root;

            function bindTrackLifecycle(track, kind, source) {
                if (!track) { return; }
                const detail = { kind: kind, source: source, reason: 'ended' };
                const notifyEnded = function (reason) {
                    const eventDetail = Object.assign({}, detail, { reason: reason || 'ended' });
                    emit(root, 'track-ended', eventDetail);
                    emit(root, 'track-state-change', Object.assign({}, eventDetail, { state: 'ended' }));
                };
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

            async function start() {
                if (!window.AgoraRTC) {
                    throw new Error('Agora RTC SDK is unavailable.');
                }
                const clientMode = config.clientMode === 'rtc' ? 'rtc' : 'live';
                state.client = window.AgoraRTC.createClient({ mode: clientMode, codec: 'vp8' });
                if (state.client && typeof state.client.on === 'function') {
                    state.client.on('connection-state-change', function (current, previous, reason) {
                        emit(root, 'connection-state-change', {
                            current: current,
                            previous: previous,
                            reason: reason || ''
                        });
                    });
                }
                if (clientMode === 'live' && typeof state.client.setClientRole === 'function') {
                    await state.client.setClientRole('host');
                }
                state.audioTrack = await window.AgoraRTC.createMicrophoneAudioTrack(config.audioConfig || {});
                if (config.initialVideoMediaStreamTrack && typeof window.AgoraRTC.createCustomVideoTrack === 'function') {
                    state.videoTrack = window.AgoraRTC.createCustomVideoTrack({
                        mediaStreamTrack: config.initialVideoMediaStreamTrack
                    });
                    state.videoTrackOwnsSource = false;
                } else {
                    state.videoTrack = await window.AgoraRTC.createCameraVideoTrack(config.videoConfig || {});
                    state.videoTrackOwnsSource = true;
                }
                bindTrackLifecycle(state.audioTrack, 'audio', 'microphone');
                bindTrackLifecycle(state.videoTrack, 'video', config.initialVideoMediaStreamTrack ? 'program' : 'camera');
                if (localContainer) {
                    state.videoTrack.play(localContainer, { mirror: config.initialVideoSource !== 'screen' });
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
                if (state.audioTrack) {
                    state.audioTrack.stop();
                    state.audioTrack.close();
                    state.audioTrack = null;
                }
                if (state.videoTrack) {
                    stopAndMaybeCloseVideoTrack(state.videoTrack, state.videoTrackOwnsSource);
                    state.videoTrack = null;
                    state.videoTrackOwnsSource = true;
                }
                if (state.client && state.joined) {
                    await state.client.leave().catch(function () {});
                }
                state.joined = false;
                state.published = false;
                emit(root, 'ended', {});
            }

            function stopAndMaybeCloseVideoTrack(track, ownsSource) {
                if (!track || !ownsSource) {
                    return;
                }
                if (typeof track.stop === 'function') {
                    track.stop();
                }
                if (typeof track.close === 'function') {
                    track.close();
                }
            }

            function isReadyToPublish() {
                return Boolean(
                    state.client &&
                    state.joined &&
                    state.published &&
                    (!state.client.connectionState || state.client.connectionState === 'CONNECTED')
                );
            }

            return {
                start: start,
                stop: stop,
                isReadyToPublish: isReadyToPublish,
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
                getState: function () {
                    return Object.assign({}, state, {
                        connectionState: state.client ? state.client.connectionState || '' : ''
                    });
                }
            };
        }
    };
})(window);
