(function () {
    'use strict';

    const root = document.querySelector('[data-vh360-studio-mobile-live]');
    const cfg = window.vh360StudioMobileLive || {};
    const strings = cfg.strings || {};

    if (!root) {
        return;
    }

    try {
        window.localStorage.setItem('vh360StudioMode', 'mobile');
    } catch (error) {}

    const state = {
        name: 'setup',
        session: null,
        videoId: 0,
        pendingCleanupVideoId: 0,
        viewerUrl: '',
        heartbeat: 0,
        durationTimer: 0,
        previewBusy: false,
        goLiveBusy: false,
        endBusy: false,
        pageExitEnding: false,
        mutedAudio: false,
        mutedVideo: false,
        facingMode: 'user',
        wakeLock: null,
        coverId: 0,
        liveStartedAt: 0,
        serverStarted: false,
        broadcastMode: 'broadcast',
        participantController: null,
        participantCount: 0,
        immersive: false,
        participantAudioBlocked: false
    };

    function text(key, fallback) {
        return strings[key] || fallback || key;
    }

    function one(selector) {
        return root.querySelector(selector);
    }

    function all(selector) {
        return Array.prototype.slice.call(root.querySelectorAll(selector));
    }

    async function api(path, options) {
        const response = await window.fetch((cfg.restRoot || '').replace(/\/$/, '') + path, options || {});
        const payload = await response.json().catch(function () { return {}; });
        if (!response.ok) {
            throw new Error(payload.message || text('requestFailed', 'Request failed. Please try again.'));
        }
        return payload;
    }

    function userMediaErrorMessage(error) {
        const name = error && error.name ? error.name : '';
        if (['NotAllowedError', 'PermissionDeniedError', 'SecurityError'].indexOf(name) !== -1) {
            return text('permissionFailed', 'Camera or microphone access failed. Check browser permissions and try again.');
        }
        if (['NotFoundError', 'DevicesNotFoundError', 'OverconstrainedError'].indexOf(name) !== -1) {
            return text('cameraRequired', 'A working camera preview is required before going live.');
        }
        return text('permissionFailed', 'Camera or microphone access failed. Check browser permissions and try again.');
    }

    function setStatus(message) {
        const status = one('[data-mobile-status]');
        if (status) {
            status.textContent = message;
        }
    }

    function setDeviceStatus(kind, message) {
        const el = one('[data-mobile-' + kind + '-status]');
        if (el) {
            el.textContent = message;
        }
    }

    function setImmersive(active) {
        state.immersive = !!active;
        root.classList.toggle('is-immersive', !!active);
        document.body.classList.toggle('vh360-mobile-live-active', !!active);
    }

    function stopParticipantController() {
        if (state.participantController) {
            state.participantController.stop();
            state.participantController = null;
        }
        state.participantCount = 0;
        state.participantAudioBlocked = false;
        root.classList.remove('has-remote-participants');
        setImmersive(false);
        all('[data-mobile-open-participants], [data-mobile-participant-count]').forEach(function (el) { el.hidden = true; });
    }

    function ensureParticipantController() {
        const enabled = state.broadcastMode === 'interactive';
        if (!enabled || !window.VH360StudioMobileParticipants) {
            return null;
        }
        if (!state.participantController) {
            state.participantController = window.VH360StudioMobileParticipants.create({
                root: root,
                session: state.session,
                enabled: true,
                ajaxUrl: cfg.ajaxUrl,
                identityNonce: cfg.identityNonce,
                strings: strings
            });
        } else if (state.participantController.setSession) {
            state.participantController.setSession(state.session);
        }
        return state.participantController;
    }

    function resetPersistentStatuses() {
        setDeviceStatus('camera', text('cameraNotConnected', 'Camera: not connected'));
        setDeviceStatus('microphone', text('microphoneNotConnected', 'Microphone: not connected'));
        setDeviceStatus('connection', text('connectionNotLive', 'Connection: not live'));
        state.serverStarted = false;
        state.facingMode = 'user';
        setReconnectBanner('', false);
        all('[data-mobile-connection]').forEach(function (el) { el.textContent = text('connected', 'Connected'); });
        resetMuteState();
        stopParticipantController();
    }

    function setState(next) {
        state.name = next;
        all('[data-mobile-stage]').forEach(function (stage) {
            stage.classList.toggle('is-active', stage.dataset.mobileStage === next);
        });
        const active = one('[data-mobile-stage="' + next + '"]');
        if (active && typeof active.focus === 'function') {
            window.setTimeout(function () { active.focus({ preventScroll: false }); }, 0);
        }
        updateBusyStates();
    }

    function updateBusyStates() {
        const busy = state.previewBusy || state.goLiveBusy || state.endBusy;
        all('[data-mobile-preview], [data-mobile-go-live], [data-mobile-retry-permissions]').forEach(function (button) {
            button.disabled = busy;
        });
        all('[data-mobile-end-live]').forEach(function (button) {
            button.disabled = state.endBusy || !(state.videoId || state.pendingCleanupVideoId);
        });
    }

    function syncMediaControls() {
        all('[data-mobile-mute-audio]').forEach(function (button) {
            button.textContent = state.mutedAudio ? text('unmuteMic', 'Unmute mic') : text('muteMic', 'Mute mic');
            button.setAttribute('aria-pressed', state.mutedAudio ? 'true' : 'false');
        });
        all('[data-mobile-mute-video]').forEach(function (button) {
            button.textContent = state.mutedVideo ? text('cameraOn', 'Camera on') : text('cameraOff', 'Camera off');
            button.setAttribute('aria-pressed', state.mutedVideo ? 'true' : 'false');
        });
    }

    function resetMuteState() {
        state.mutedAudio = false;
        state.mutedVideo = false;
        syncMediaControls();
    }

    function formPayload() {
        return {
            title: (one('[data-mobile-title]') || {}).value || '',
            description: (one('[data-mobile-description]') || {}).value || '',
            chat_enabled: one('[data-mobile-chat]').checked,
            viewer_count: one('[data-mobile-viewer-count]').checked,
            agora_mode: one('[data-mobile-agora-mode]').value,
            agora_everyone_is_host: one('[data-mobile-everyone-host]').checked,
            require_passcode: one('[data-mobile-require-passcode]').checked,
            host_passcode: one('[data-mobile-host-passcode]').value,
            featured_image_id: state.coverId,
            recording_intent: 'none'
        };
    }

    function validateForm(payload) {
        if (!payload.title.trim()) {
            throw new Error(text('titleRequired', 'Enter a title before going live.'));
        }
        if (payload.agora_mode === 'interactive' && payload.require_passcode && !payload.host_passcode.trim()) {
            throw new Error(text('passcodeRequired', 'Enter a host passcode or turn off passcode access.'));
        }
        if (!state.session) {
            throw new Error(text('cameraRequired', 'A working camera preview is required before going live.'));
        }
    }

    function updateAdvancedControls() {
        const mode = one('[data-mobile-agora-mode]').value;
        const everyone = one('[data-mobile-everyone-host]');
        const requirePasscode = one('[data-mobile-require-passcode]');
        const passcodeField = one('[data-mobile-passcode-field]');
        const interactive = mode === 'interactive';

        everyone.disabled = !interactive;
        requirePasscode.disabled = !interactive;
        if (!interactive) {
            everyone.checked = false;
            requirePasscode.checked = false;
        }
        if (everyone.checked) {
            requirePasscode.checked = false;
        }
        if (requirePasscode.checked) {
            everyone.checked = false;
        }
        passcodeField.hidden = !interactive || !requirePasscode.checked;
    }

    async function uploadCover() {
        const input = one('[data-mobile-cover]');
        if (!input || !input.files || !input.files[0]) {
            return 0;
        }
        const form = new FormData();
        form.append('cover_image', input.files[0]);
        const response = await api('/cover-image', {
            method: 'POST',
            headers: { 'X-WP-Nonce': cfg.nonce },
            body: form
        });
        state.coverId = response.id || response.attachment_id || response.featured_image_id || 0;
        return state.coverId;
    }

    function createSession() {
        state.broadcastMode = (one('[data-mobile-agora-mode]') || {}).value === 'interactive' ? 'interactive' : 'broadcast';
        return window.VH360AgoraBroadcaster.create({
            container: root,
            localContainer: one('[data-agora-local-preview]'),
            videoConfig: cfg.mobileVideoConfig,
            audioConfig: cfg.mobileAudioConfig || {},
            receiveRemoteParticipants: state.broadcastMode === 'interactive',
            renewToken: renewToken
        });
    }

    async function stopSession() {
        stopParticipantController();
        if (state.session) {
            await state.session.stop().catch(function () {});
            state.session = null;
        }
    }

    async function preview() {
        if (state.previewBusy) {
            return;
        }
        state.previewBusy = true;
        updateBusyStates();
        await stopSession();
        setState('requesting_permissions');
        setStatus(text('requestingPermissions', 'Requesting camera and microphone permissions…'));
        try {
            state.session = createSession();
            await state.session.prepareMedia();
            state.facingMode = (state.session.getCurrentCameraState && state.session.getCurrentCameraState().facingMode) || 'user';
            setDeviceStatus('camera', text('cameraConnected', 'Camera: connected'));
            setDeviceStatus('microphone', text('microphoneConnected', 'Microphone: connected'));
            resetMuteState();
            setState('preview_ready');
            setStatus(text('previewReady', 'Camera and microphone preview is ready.'));
        } catch (error) {
            await stopSession();
            const message = userMediaErrorMessage(error);
            one('[data-mobile-error-message]').textContent = message;
            setState('error');
            setStatus(message);
        } finally {
            state.previewBusy = false;
            updateBusyStates();
        }
    }

    async function renewToken() {
        if (!state.videoId) {
            return null;
        }
        return api('/broadcasts/' + state.videoId + '/renew-token', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.nonce }
        });
    }

    function startHeartbeat() {
        stopHeartbeat();
        state.heartbeat = window.setInterval(function () {
            if (state.videoId && (state.name === 'live' || state.name === 'reconnecting')) {
                api('/broadcasts/' + state.videoId + '/heartbeat', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.nonce }
                }).catch(function () {});
            }
        }, 30000);
    }

    function stopHeartbeat() {
        if (state.heartbeat) {
            window.clearInterval(state.heartbeat);
            state.heartbeat = 0;
        }
    }

    function startDuration() {
        state.liveStartedAt = Date.now();
        window.clearInterval(state.durationTimer);
        state.durationTimer = window.setInterval(function () {
            const seconds = Math.floor((Date.now() - state.liveStartedAt) / 1000);
            const minutes = String(Math.floor(seconds / 60)).padStart(2, '0');
            const secs = String(seconds % 60).padStart(2, '0');
            all('[data-mobile-duration]').forEach(function (el) { el.textContent = minutes + ':' + secs; });
        }, 1000);
    }

    function nextAnimationFrame() {
        return new Promise(function (resolve) {
            window.requestAnimationFrame ? window.requestAnimationFrame(resolve) : window.setTimeout(resolve, 16);
        });
    }

    async function requestWakeLock() {
        if (navigator.wakeLock && navigator.wakeLock.request) {
            try {
                state.wakeLock = await navigator.wakeLock.request('screen');
            } catch (error) {}
        }
    }

    async function releaseWakeLock() {
        if (state.wakeLock && state.wakeLock.release) {
            await state.wakeLock.release().catch(function () {});
        }
        state.wakeLock = null;
    }

    function setViewerLinks(url) {
        all('[data-mobile-open-viewer], [data-mobile-open-video]').forEach(function (link) {
            if (url) {
                link.href = url;
            }
        });
    }

    async function bestEffortEnd(videoId, keepalive) {
        if (!videoId) {
            return;
        }
        return api('/broadcasts/' + videoId + '/end', {
            method: 'POST',
            keepalive: !!keepalive,
            headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.nonce },
            body: JSON.stringify({})
        });
    }

    async function rollbackFailedStart(reason) {
        const cleanupVideoId = state.videoId;
        stopHeartbeat();
        window.clearInterval(state.durationTimer);
        await stopSession();
        resetPersistentStatuses();
        if (cleanupVideoId) {
            try {
                await bestEffortEnd(cleanupVideoId, false);
                state.pendingCleanupVideoId = 0;
            } catch (cleanupError) {
                state.pendingCleanupVideoId = cleanupVideoId;
            }
        }
        state.videoId = 0;
        state.viewerUrl = '';
        state.serverStarted = false;
        setState(state.pendingCleanupVideoId ? 'end_failed' : 'error');
        setStatus(reason || text('startFailed', 'The livestream could not start. Devices and server state were cleaned up when possible.'));
    }

    async function goLive() {
        if (state.goLiveBusy || state.pendingCleanupVideoId) {
            setStatus(text('cleanupPending', 'A previous start attempt needs server cleanup before you can start another live.'));
            return;
        }
        state.goLiveBusy = true;
        updateBusyStates();
        state.serverStarted = false;

        try {
            const payload = formPayload();
            state.broadcastMode = payload.agora_mode === 'interactive' ? 'interactive' : 'broadcast';
            validateForm(payload);
            setState('creating_broadcast');
            setStatus(text('creatingBroadcast', 'Creating livestream…'));
            await uploadCover();
            payload.featured_image_id = state.coverId;

            const created = await api('/broadcasts', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.nonce },
                body: JSON.stringify(payload)
            });
            const broadcast = created.broadcast || {};
            state.videoId = broadcast.videoId;
            state.viewerUrl = broadcast.viewerPermalink || '';
            setViewerLinks(state.viewerUrl);

            const prepared = await api('/broadcasts/' + state.videoId + '/prepare', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.nonce }
            });

            const participants = ensureParticipantController();
            if (participants) {
                participants.setBroadcastContext({
                    videoId: state.videoId,
                    channelName: prepared.channelName || prepared.channel_name || '',
                    localUid: prepared.uid || prepared.agoraUid || prepared.agora_uid || ''
                });
                if (typeof participants.bind === 'function') {
                    participants.bind();
                } else {
                    participants.start();
                }
            }

            setState('connecting');
            setStatus(text('connectingLiveService', 'Connecting to the live service…'));
            await state.session.connect(Object.assign({}, prepared, { videoId: state.videoId }));
            await api('/broadcasts/' + state.videoId + '/started', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.nonce },
                body: JSON.stringify({ job_id: 0 })
            });
            state.serverStarted = true;

            setState('live');
            await nextAnimationFrame();
            const livePreview = one('[data-mobile-live-preview]');
            let localPreviewAttached = true;
            if (state.session && typeof state.session.setLocalPreviewContainer === 'function') {
                localPreviewAttached = state.session.setLocalPreviewContainer(livePreview);
            }
            setImmersive(true);
            await nextAnimationFrame();
            if (state.broadcastMode === 'interactive') {
                all('[data-mobile-open-participants], [data-mobile-participant-count]').forEach(function (el) { el.hidden = false; });
                if (state.participantController && typeof state.participantController.activateRendering === 'function') {
                    await state.participantController.activateRendering();
                }
            }
            setStatus(localPreviewAttached ? text('liveStarted', 'You are live. Keep this browser open.') : text('localPreviewFailed', 'The livestream is active, but the local camera preview could not be displayed.'));
            setDeviceStatus('connection', text('connectionConnected', 'Connection: connected'));
            startHeartbeat();
            startDuration();
            requestWakeLock();
        } catch (error) {
            console.error('[VH360 Mobile Live] Start failed', error);
            await rollbackFailedStart(text('startFailed', 'The livestream could not start. Devices and server state were cleaned up when possible.'));
        } finally {
            state.goLiveBusy = false;
            updateBusyStates();
        }
    }

    async function endLive(options) {
        const opts = options || {};
        const videoId = opts.videoId || state.pendingCleanupVideoId || state.videoId;
        if (state.endBusy || !videoId) {
            return;
        }
        if (!opts.skipConfirm && !window.confirm(text('endConfirm', 'End this livestream?'))) {
            return;
        }
        state.endBusy = true;
        updateBusyStates();
        setState('ending');
        setStatus(text('endingLive', 'Ending livestream…'));
        stopHeartbeat();
        window.clearInterval(state.durationTimer);

        try {
            await stopSession();
            await bestEffortEnd(videoId, false);
            state.pendingCleanupVideoId = 0;
            state.videoId = 0;
            resetPersistentStatuses();
            await releaseWakeLock();
            setState('ended');
            setStatus(text('ended', 'Livestream ended.'));
        } catch (error) {
            state.pendingCleanupVideoId = videoId;
            setViewerLinks(state.viewerUrl);
            setState('end_failed');
            setStatus(text('endFailed', 'The local stream stopped, but the server has not confirmed End Live. Retry End Live.'));
        } finally {
            state.endBusy = false;
            updateBusyStates();
        }
    }

    async function returnToSetup() {
        await stopSession();
        resetPersistentStatuses();
        setState('setup');
        setStatus('');
    }

    function setReconnectBanner(message, show) {
        const banner = one('[data-mobile-reconnect-banner]');
        if (banner) {
            banner.textContent = message || '';
            banner.hidden = !show;
        }
    }

    function handleConnectionChange(event) {
        const current = event.detail.current || '';
        all('[data-mobile-connection]').forEach(function (el) { el.textContent = current || text('connected', 'Connected'); });
        setDeviceStatus('connection', text('connectionLabel', 'Connection: ') + (current || text('connected', 'Connected')));
        if (current === 'RECONNECTING') {
            state.name = 'reconnecting';
            setReconnectBanner(text('reconnecting', 'Reconnecting… keep this page open. End Live remains available.'), true);
            setStatus(text('reconnecting', 'Reconnecting… keep this page open. End Live remains available.'));
            return;
        }
        if (current === 'CONNECTED') {
            if (state.serverStarted && state.name === 'reconnecting') {
                state.name = 'live';
                setReconnectBanner('', false);
                setStatus(text('connected', 'Connected'));
            }
            return;
        }
        if (current === 'DISCONNECTED' || current === 'FAILED') {
            setReconnectBanner(text('disconnected', 'Disconnected. Try to reconnect or end the livestream.'), true);
            setStatus(text('disconnected', 'Disconnected. Try to reconnect or end the livestream.'));
        }
    }

    function bindModeChoices() {
        all('[data-studio-mode-choice]').forEach(function (link) {
            link.addEventListener('click', function () {
                try { window.localStorage.setItem('vh360StudioMode', link.getAttribute('data-studio-mode-choice')); } catch (error) {}
            });
        });
    }

    function bindControls() {
        one('[data-mobile-preview]').addEventListener('click', preview);
        one('[data-mobile-retry-permissions]').addEventListener('click', preview);
        one('[data-mobile-go-live]').addEventListener('click', goLive);
        all('[data-mobile-back-setup]').forEach(function (button) { button.addEventListener('click', returnToSetup); });
        all('[data-mobile-end-live]').forEach(function (button) { button.addEventListener('click', function () { endLive(); }); });
        one('[data-mobile-start-another]').addEventListener('click', function () { window.location.href = window.location.href; });

        all('[data-mobile-switch-camera]').forEach(function (button) {
            button.addEventListener('click', async function () {
                if (!state.session) {
                    return;
                }
                const previousFacing = state.facingMode;
                const targetFacing = previousFacing === 'user' ? 'environment' : 'user';
                button.disabled = true;
                try {
                    await state.session.switchCamera(targetFacing);
                    const cameraState = state.session.getCurrentCameraState ? state.session.getCurrentCameraState() : {};
                    state.facingMode = cameraState.facingMode || targetFacing;
                } catch (error) {
                    state.facingMode = previousFacing;
                    setStatus(text('cameraSwitchFailed', 'Camera could not be switched.'));
                } finally {
                    button.disabled = false;
                }
            });
        });

        all('[data-mobile-mute-audio]').forEach(function (button) {
            button.addEventListener('click', async function () {
                const previous = state.mutedAudio;
                const next = !previous;
                button.disabled = true;
                try {
                    if (state.session) {
                        await state.session.muteAudio(next);
                    }
                    state.mutedAudio = next;
                } catch (error) {
                    state.mutedAudio = previous;
                    setStatus(text('audioToggleFailed', 'Microphone state could not be changed.'));
                } finally {
                    syncMediaControls();
                    button.disabled = false;
                }
            });
        });

        all('[data-mobile-mute-video]').forEach(function (button) {
            button.addEventListener('click', async function () {
                const previous = state.mutedVideo;
                const next = !previous;
                button.disabled = true;
                try {
                    if (state.session) {
                        await state.session.muteVideo(next);
                    }
                    state.mutedVideo = next;
                } catch (error) {
                    state.mutedVideo = previous;
                    setStatus(text('videoToggleFailed', 'Camera state could not be changed.'));
                } finally {
                    syncMediaControls();
                    button.disabled = false;
                }
            });
        });

        const openParticipants = one('[data-mobile-open-participants]');
        const closeParticipants = one('[data-mobile-close-participants]');
        if (openParticipants) {
            openParticipants.addEventListener('click', function () {
                if (state.participantController) {
                    state.participantController.openDrawer();
                }
            });
        }
        if (closeParticipants) {
            closeParticipants.addEventListener('click', function () {
                if (state.participantController) {
                    state.participantController.closeDrawer();
                }
            });
        }

        one('[data-mobile-agora-mode]').addEventListener('change', updateAdvancedControls);
        one('[data-mobile-everyone-host]').addEventListener('change', updateAdvancedControls);
        one('[data-mobile-require-passcode]').addEventListener('change', updateAdvancedControls);

        window.addEventListener('online', function () {
            if (state.pendingCleanupVideoId) {
                endLive({ videoId: state.pendingCleanupVideoId, skipConfirm: true });
            }
        });

        window.addEventListener('beforeunload', function (event) {
            if (state.pendingCleanupVideoId || ['creating_broadcast', 'connecting', 'live', 'reconnecting', 'ending', 'end_failed'].indexOf(state.name) !== -1) {
                event.preventDefault();
                event.returnValue = '';
            }
        });

        window.addEventListener('pagehide', function () {
            const cleanupVideoId = state.pendingCleanupVideoId || state.videoId;
            if (state.pageExitEnding || state.endBusy || !cleanupVideoId || ['creating_broadcast', 'connecting', 'live', 'reconnecting', 'ending', 'end_failed'].indexOf(state.name) === -1) {
                return;
            }
            state.pageExitEnding = true;
            const url = (cfg.restRoot || '').replace(/\/$/, '') + '/broadcasts/' + cleanupVideoId + '/end';
            window.fetch(url, {
                method: 'POST',
                keepalive: true,
                headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.nonce },
                body: JSON.stringify({})
            }).catch(function () {
                if (navigator.sendBeacon) {
                    navigator.sendBeacon(url + '?_wpnonce=' + encodeURIComponent(cfg.nonce), new Blob([], { type: 'application/json' }));
                }
            });
        });

        root.addEventListener('vh360:agora-broadcaster:connection-state-change', handleConnectionChange);
        root.addEventListener('vh360:agora-broadcaster:track-ended', function () {
            setStatus(text('trackEnded', 'A media device disconnected. Check camera and microphone permissions.'));
        });
        root.addEventListener('vh360:agora-broadcaster:token-renewal-error', function () {
            setStatus(text('tokenRenewalFailed', 'Live connection renewal failed. The app will retry.'));
        });
        root.addEventListener('vh360:agora-broadcaster:token-recovery-error', function () {
            setStatus(text('tokenRecoveryFailed', 'Live connection recovery failed. Keep this page open or end the livestream.'));
        });
        root.addEventListener('vh360:agora-broadcaster:camera-switch-error', function () {
            setStatus(text('cameraSwitchFailed', 'Camera could not be switched.'));
        });
        root.addEventListener('vh360:agora-broadcaster:local-preview-error', function (event) {
            console.error('[VH360 Mobile Live] Local preview render failed', event.detail && event.detail.error);
            setStatus(text('localPreviewFailed', 'The livestream is active, but the local camera preview could not be displayed.'));
        });
        root.addEventListener('vh360:mobile-participants:count', function (event) {
            state.participantCount = (event.detail && event.detail.count) || 0;
        });
    }

    bindModeChoices();
    bindControls();
    updateAdvancedControls();
    syncMediaControls();
})();
