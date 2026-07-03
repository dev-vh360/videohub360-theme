(function () {
    'use strict';

    const root = document.querySelector('[data-vh360-studio-dashboard]');
    if (!root || !window.vh360StudioDashboard) {
        return;
    }

    const config = window.vh360StudioDashboard;
    const strings = config.strings || {};
    const supportLabels = config.supportLabels || {};
    const state = {
        cameraStream: null,
        screenStream: null,
        audioContext: null,
        analyser: null,
        meterFrame: null,
        selectedCameraId: '',
        selectedMicId: '',
        support: {},
        activeJobId: null,
        browserSessionId: '',
        recorder: null,
        recordingStream: null,
        selectedMimeType: '',
        chunkIndex: 0,
        pendingUploads: new Set(),
        uploadedChunks: new Set(),
        failedChunks: new Map(),
        recordingStartedAt: 0,
        durationTimer: null,
        finalChunkCount: 0,
        currentJobStatus: '',
        broadcastVideoId: null,
        broadcastSession: null,
        heartbeatTimer: null,
        previewSource: null,
        programSource: null,
        programStream: null,
        transitioning: false,
    };

    const els = {
        status: root.querySelector('[data-studio-status]'),
        supportChecks: root.querySelector('[data-support-checks]'),
        cameraPreview: root.querySelector('[data-camera-preview]'),
        screenPreview: root.querySelector('[data-screen-preview]'),
        programPreview: root.querySelector('[data-program-preview]'),
        programEmpty: root.querySelector('[data-program-empty]'),
        previewSourceButtons: root.querySelectorAll('[data-preview-source]'),
        sceneSourceButtons: root.querySelectorAll('[data-scene-source]'),
        transitionCut: root.querySelector('[data-transition-cut]'),
        transitionFade: root.querySelector('[data-transition-fade]'),
        transitionDuration: root.querySelector('[data-transition-duration]'),
        cameraSelect: root.querySelector('[data-camera-select]'),
        micSelect: root.querySelector('[data-mic-select]'),
        startPreview: root.querySelector('[data-start-preview]'),
        stopPreview: root.querySelector('[data-stop-preview]'),
        startScreen: root.querySelector('[data-start-screen]'),
        stopScreen: root.querySelector('[data-stop-screen]'),
        micMeter: root.querySelector('[data-mic-meter]'),
        qualitySelect: root.querySelector('[data-quality-select]'),
        storageSelect: root.querySelector('[data-storage-select]'),
        qualityDetails: root.querySelector('[data-quality-details]'),
        createJob: root.querySelector('[data-create-job]'),
        jobResult: root.querySelector('[data-job-result]'),
        recentJobsBody: root.querySelector('[data-recent-jobs-body]'),
        emptyJobs: root.querySelector('[data-empty-jobs]'),
        startRecording: root.querySelector('[data-start-recording]'),
        stopRecording: root.querySelector('[data-stop-recording]'),
        retryChunks: root.querySelector('[data-retry-chunks]'),
        finalizeRecording: root.querySelector('[data-finalize-recording]'),
        recordingStatus: root.querySelector('[data-recording-status]'),
        recordingJobId: root.querySelector('[data-recording-job-id]'),
        recordingMime: root.querySelector('[data-recording-mime]'),
        recordingTimer: root.querySelector('[data-recording-timer]'),
        recordingUploaded: root.querySelector('[data-recording-uploaded]'),
        recordingPending: root.querySelector('[data-recording-pending]'),
        recordingFailed: root.querySelector('[data-recording-failed]'),
        recordingBytes: root.querySelector('[data-recording-bytes]'),
        recordingFinalizeStatus: root.querySelector('[data-recording-finalize-status]'),
        recordingProgress: root.querySelector('[data-recording-progress]'),
        publishReplay: root.querySelector('[data-publish-replay]'),
        checkPublishingStatus: root.querySelector('[data-check-publishing-status]'),
        publishingStatus: root.querySelector('[data-publishing-status]'),
        replayLinkWrap: root.querySelector('[data-replay-link-wrap]'),
        replayLink: root.querySelector('[data-replay-link]'),
        broadcastTitle: root.querySelector('[data-broadcast-title]'),
        broadcastDescription: root.querySelector('[data-broadcast-description]'),
        broadcastMode: root.querySelector('[data-broadcast-mode]'),
        broadcastViewerCount: root.querySelector('[data-broadcast-viewer-count]'),
        broadcastChat: root.querySelector('[data-broadcast-chat]'),
        broadcastEveryoneHost: root.querySelector('[data-broadcast-everyone-host]'),
        broadcastRequirePasscode: root.querySelector('[data-broadcast-require-passcode]'),
        broadcastPasscode: root.querySelector('[data-broadcast-passcode]'),
        broadcastPasscodeWrap: root.querySelector('[data-passcode-wrap]'),
        interactiveOnly: root.querySelectorAll('[data-interactive-only]'),
        goLive: root.querySelector('[data-go-live]'),
        endLive: root.querySelector('[data-end-live]'),
        broadcastStatus: root.querySelector('[data-broadcast-status]'),
        agoraLocalPreview: root.querySelector('[data-agora-local-preview]'),
        viewerLinkWrap: root.querySelector('[data-viewer-link-wrap]'),
        viewerLink: root.querySelector('[data-viewer-link]'),
        copyViewerLink: root.querySelector('[data-copy-viewer-link]'),
    };

    function setShellClass(className, active) {
        root.classList.toggle(className, Boolean(active));
    }



    function sourceLabel(sourceId) {
        return sourceId === 'screen' ? 'Screen Share' : 'Camera';
    }

    async function getSourceStream(sourceId) {
        if (sourceId === 'screen') {
            if (!state.screenStream || !state.screenStream.getVideoTracks().length || state.screenStream.getVideoTracks()[0].readyState === 'ended') {
                await startScreenPreview(false);
            }
            return state.screenStream;
        }
        if (!state.cameraStream || !state.cameraStream.getVideoTracks().length || state.cameraStream.getVideoTracks()[0].readyState === 'ended') {
            await startPreview(false);
        }
        return state.cameraStream;
    }

    async function setPreviewSource(sourceId) {
        const stream = await getSourceStream(sourceId);
        if (!stream) {
            return;
        }
        state.previewSource = sourceId;
        renderSourceState();
        setStatus(sourceLabel(sourceId) + ' staged in Preview.', 'success');
    }

    async function commitPreviewToProgram(transitionType) {
        if (!state.previewSource) {
            setStatus('Choose a Preview source before using Cut or Fade.', 'warning');
            return;
        }
        if (state.transitioning) {
            return;
        }
        state.transitioning = transitionType === 'fade';
        const duration = Math.max(0, Math.min(2000, Number(els.transitionDuration && els.transitionDuration.value) || 300));
        if (state.transitioning) {
            root.classList.add('is-transitioning', 'is-fading');
        }
        try {
            const stream = await getSourceStream(state.previewSource);
            state.programSource = state.previewSource;
            state.programStream = stream;
            renderProgramState();
            await replaceLiveVideoTrack(stream);
            setStatus(sourceLabel(state.programSource) + ' sent to Program.', 'success');
            if (transitionType === 'fade') {
                await new Promise((resolve) => window.setTimeout(resolve, duration));
            }
        } catch (error) {
            setStatus((error && error.message) || 'Program source could not be changed.', 'error');
        } finally {
            state.transitioning = false;
            root.classList.remove('is-transitioning', 'is-fading');
            renderSourceState();
        }
    }

    function renderSourceState() {
        els.previewSourceButtons.forEach((button) => {
            button.classList.toggle('is-active', button.dataset.previewSource === state.previewSource);
        });
        els.sceneSourceButtons.forEach((button) => {
            const active = button.dataset.sceneSource === state.previewSource;
            button.classList.toggle('is-active', active);
            if (active) {
                button.setAttribute('aria-current', 'true');
            } else {
                button.removeAttribute('aria-current');
            }
        });
    }

    function renderProgramState() {
        if (els.programPreview) {
            els.programPreview.srcObject = state.programStream || null;
            els.programPreview.classList.toggle('vh360-studio-program-video--screen', state.programSource === 'screen');
            if (state.programStream) {
                els.programPreview.play().catch(() => {});
            }
        }
        root.classList.toggle('is-program-active', Boolean(state.programStream));
        if (els.programEmpty) {
            els.programEmpty.hidden = Boolean(state.programStream);
        }
    }

    async function replaceLiveVideoTrack(stream) {
        if (!state.broadcastSession || !stream || typeof state.broadcastSession.replaceVideoMediaStreamTrack !== 'function') {
            return;
        }
        const track = stream.getVideoTracks()[0];
        if (track) {
            await state.broadcastSession.replaceVideoMediaStreamTrack(track);
        }
    }

    function setStatus(message, type) {
        if (!els.status) {
            return;
        }
        els.status.textContent = message;
        els.status.dataset.statusType = type || 'info';
    }

    function setJobResult(message, type) {
        if (!els.jobResult) {
            return;
        }
        els.jobResult.textContent = message;
        els.jobResult.dataset.statusType = type || 'info';
    }

    function detectSupport() {
        const mediaDevices = Boolean(navigator.mediaDevices);
        const mediaRecorder = 'MediaRecorder' in window;
        const mimeCandidates = [
            'video/webm;codecs=vp9,opus',
            'video/webm;codecs=vp8,opus',
            'video/webm',
            'video/mp4',
        ];
        const mimeTypes = mediaRecorder
            ? mimeCandidates.filter((type) => window.MediaRecorder.isTypeSupported(type))
            : [];

        state.support = {
            mediaDevices,
            getUserMedia: mediaDevices && typeof navigator.mediaDevices.getUserMedia === 'function',
            enumerateDevices: mediaDevices && typeof navigator.mediaDevices.enumerateDevices === 'function',
            getDisplayMedia: mediaDevices && typeof navigator.mediaDevices.getDisplayMedia === 'function',
            mediaRecorder,
            secureContext: window.isSecureContext || window.location.hostname === 'localhost',
            mimeTypes,
        };
    }

    function renderSupportChecks() {
        if (!els.supportChecks) {
            return;
        }

        const checks = [
            ['mediaDevices', state.support.mediaDevices],
            ['getUserMedia', state.support.getUserMedia],
            ['enumerateDevices', state.support.enumerateDevices],
            ['getDisplayMedia', state.support.getDisplayMedia],
            ['mediaRecorder', state.support.mediaRecorder],
            ['secureContext', state.support.secureContext],
        ];

        els.supportChecks.innerHTML = '';
        checks.forEach(([key, supported]) => {
            const item = document.createElement('li');
            item.className = supported ? 'is-supported' : 'is-unsupported';
            item.innerHTML = '<span>' + escapeHtml(supportLabels[key] || key) + '</span><strong>' + escapeHtml(supported ? strings.supported : strings.notSupported) + '</strong>';
            els.supportChecks.appendChild(item);
        });

        const mimeItem = document.createElement('li');
        mimeItem.className = state.support.mimeTypes.length ? 'is-supported' : 'is-unsupported';
        mimeItem.innerHTML = '<span>' + escapeHtml(supportLabels.mimeTypes || 'Formats') + '</span><strong>' + escapeHtml(state.support.mimeTypes.length ? state.support.mimeTypes.join(', ') : strings.notSupported) + '</strong>';
        els.supportChecks.appendChild(mimeItem);
    }

    function updateReadinessStatus() {
        if (!state.support.secureContext) {
            setStatus(strings.insecureContext, 'error');
            return;
        }
        if (!state.support.mediaDevices || !state.support.getUserMedia || !state.support.mediaRecorder) {
            setStatus(strings.browserUnsupported, 'error');
            return;
        }
        setStatus(strings.ready, 'success');
    }

    function escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = value || '';
        return div.innerHTML;
    }

    async function populateDevices() {
        if (!state.support.enumerateDevices) {
            return;
        }

        const devices = await navigator.mediaDevices.enumerateDevices();
        const cameras = devices.filter((device) => device.kind === 'videoinput');
        const microphones = devices.filter((device) => device.kind === 'audioinput');

        fillDeviceSelect(els.cameraSelect, cameras, 'Camera', state.selectedCameraId);
        fillDeviceSelect(els.micSelect, microphones, 'Microphone', state.selectedMicId);

        if (!state.selectedCameraId && els.cameraSelect && els.cameraSelect.value) {
            state.selectedCameraId = els.cameraSelect.value;
        }
        if (!state.selectedMicId && els.micSelect && els.micSelect.value) {
            state.selectedMicId = els.micSelect.value;
        }

        if (!cameras.length) {
            setStatus(strings.noCameraFound, 'warning');
        } else if (!microphones.length) {
            setStatus(strings.noMicrophoneFound, 'warning');
        }
    }

    function fillDeviceSelect(select, devices, fallbackLabel, selectedDeviceId) {
        if (!select) {
            return;
        }

        select.innerHTML = '';
        if (!devices.length) {
            const option = document.createElement('option');
            option.value = '';
            option.textContent = fallbackLabel + ' unavailable';
            select.appendChild(option);
            select.disabled = true;
            return;
        }

        devices.forEach((device, index) => {
            const option = document.createElement('option');
            option.value = device.deviceId;
            option.textContent = device.label || fallbackLabel + ' ' + (index + 1);
            if (selectedDeviceId && device.deviceId === selectedDeviceId) {
                option.selected = true;
            }
            select.appendChild(option);
        });
        select.disabled = false;
    }

    async function startPreview(updateSelection = true) {
        if (!state.support.secureContext) {
            setStatus(strings.insecureContext, 'error');
            return;
        }
        if (!state.support.getUserMedia) {
            setStatus(strings.browserUnsupported, 'error');
            return;
        }

        stopPreview();

        try {
            const preset = getSelectedPreset();
            const constraints = {
                video: buildVideoConstraints(preset),
                audio: state.selectedMicId ? { deviceId: { exact: state.selectedMicId } } : true,
            };
            state.cameraStream = await navigator.mediaDevices.getUserMedia(constraints);
            if (els.cameraPreview) {
                els.cameraPreview.srcObject = state.cameraStream;
                await els.cameraPreview.play().catch(() => {});
            }
            setupAudioMeter(state.cameraStream);
            await populateDevices();
            setPreviewButtons(true);
            setShellClass('is-preview-active', true);
            if (state.programSource === 'camera') {
                state.programStream = state.cameraStream;
                renderProgramState();
            }
            if (updateSelection) {
                state.previewSource = 'camera';
                renderSourceState();
                setStatus('Camera staged in Preview.', 'success');
            } else {
                setStatus(strings.previewActive, 'success');
            }
        } catch (error) {
            stopPreview();
            setStatus(friendlyMediaError(error), 'error');
        }
    }

    function stopPreview() {
        stopStream(state.cameraStream);
        state.cameraStream = null;
        if (els.cameraPreview) {
            els.cameraPreview.srcObject = null;
        }
        teardownAudioMeter();
        setPreviewButtons(false);
        setShellClass('is-preview-active', false);
        if (state.previewSource === 'camera') {
            state.previewSource = null;
            renderSourceState();
        }
        if (state.programSource === 'camera') {
            state.programSource = null;
            state.programStream = null;
            renderProgramState();
        }
    }

    function setPreviewButtons(active) {
        if (els.startPreview) {
            els.startPreview.disabled = active;
        }
        if (els.stopPreview) {
            els.stopPreview.disabled = !active;
        }
    }

    function setupAudioMeter(stream) {
        teardownAudioMeter();
        const audioTracks = stream.getAudioTracks();
        if (!audioTracks.length || !window.AudioContext && !window.webkitAudioContext) {
            return;
        }

        const AudioContextClass = window.AudioContext || window.webkitAudioContext;
        state.audioContext = new AudioContextClass();
        const source = state.audioContext.createMediaStreamSource(new MediaStream(audioTracks));
        state.analyser = state.audioContext.createAnalyser();
        state.analyser.fftSize = 256;
        source.connect(state.analyser);
        drawMeter();
    }

    function drawMeter() {
        if (!state.analyser || !els.micMeter) {
            return;
        }

        const data = new Uint8Array(state.analyser.frequencyBinCount);
        state.analyser.getByteFrequencyData(data);
        const average = data.reduce((sum, value) => sum + value, 0) / data.length;
        els.micMeter.style.width = Math.min(100, Math.round((average / 255) * 100)) + '%';
        state.meterFrame = window.requestAnimationFrame(drawMeter);
    }

    function teardownAudioMeter() {
        if (state.meterFrame) {
            window.cancelAnimationFrame(state.meterFrame);
            state.meterFrame = null;
        }
        if (els.micMeter) {
            els.micMeter.style.width = '0%';
        }
        if (state.audioContext) {
            state.audioContext.close().catch(() => {});
        }
        state.audioContext = null;
        state.analyser = null;
    }

    async function startScreenPreview(updateSelection = true) {
        if (!state.support.getDisplayMedia) {
            setStatus(strings.screenUnsupported, 'error');
            return;
        }

        stopScreenPreview();

        try {
            state.screenStream = await navigator.mediaDevices.getDisplayMedia({ video: true, audio: false });
            state.screenStream.getVideoTracks().forEach((track) => {
                track.addEventListener('ended', stopScreenPreview, { once: true });
            });
            if (els.screenPreview) {
                els.screenPreview.srcObject = state.screenStream;
                await els.screenPreview.play().catch(() => {});
            }
            setScreenButtons(true);
            setShellClass('is-screen-active', true);
            if (updateSelection) {
                state.previewSource = 'screen';
                renderSourceState();
                setStatus('Screen Share staged in Preview.', 'success');
            } else {
                setStatus(strings.screenPreviewActive, 'success');
            }
        } catch (error) {
            stopScreenPreview();
            setStatus(error && error.name === 'NotAllowedError' ? strings.screenCancelled : friendlyMediaError(error), 'warning');
        }
    }

    function stopScreenPreview() {
        stopStream(state.screenStream);
        state.screenStream = null;
        if (els.screenPreview) {
            els.screenPreview.srcObject = null;
        }
        setScreenButtons(false);
        setShellClass('is-screen-active', false);
        if (state.previewSource === 'screen') {
            state.previewSource = null;
            renderSourceState();
        }
        if (state.programSource === 'screen') {
            state.programSource = null;
            state.programStream = null;
            renderProgramState();
        }
    }

    function setScreenButtons(active) {
        if (els.startScreen) {
            els.startScreen.disabled = active;
        }
        if (els.stopScreen) {
            els.stopScreen.disabled = !active;
        }
    }

    function stopStream(stream) {
        if (!stream) {
            return;
        }
        stream.getTracks().forEach((track) => track.stop());
    }

    function friendlyMediaError(error) {
        if (!error) {
            return strings.browserUnsupported;
        }
        if (error.name === 'NotAllowedError' || error.name === 'SecurityError') {
            return strings.permissionDenied;
        }
        if (error.name === 'NotFoundError' || error.name === 'DevicesNotFoundError') {
            return strings.noCameraFound + ' ' + strings.noMicrophoneFound;
        }
        if (error.name === 'NotReadableError') {
            return strings.cameraBlocked;
        }
        return error.message || strings.browserUnsupported;
    }

    function updateQualityDetails() {
        if (!els.qualityDetails || !els.qualitySelect) {
            return;
        }
        const preset = (config.qualityPresets || {})[els.qualitySelect.value];
        if (!preset) {
            return;
        }
        const resolution = preset.resolution ? preset.resolution.width + '×' + preset.resolution.height : '';
        els.qualityDetails.textContent = preset.label + ' · ' + resolution + ' · ' + preset.fps + 'fps';
    }



    function getSelectedPreset() {
        return (config.qualityPresets || {})[els.qualitySelect ? els.qualitySelect.value : config.defaultQualityPreset] || (config.qualityPresets || {})[config.defaultQualityPreset] || {};
    }

    function buildVideoConstraints(preset) {
        const video = state.selectedCameraId ? { deviceId: { exact: state.selectedCameraId } } : {};
        if (preset.resolution) {
            video.width = { ideal: preset.resolution.width };
            video.height = { ideal: preset.resolution.height };
        }
        if (preset.fps) {
            video.frameRate = { ideal: preset.fps };
        }
        return video;
    }

    function setRecordingStatus(message, type) {
        if (els.recordingStatus) {
            els.recordingStatus.textContent = message || '';
            els.recordingStatus.dataset.statusType = type || 'info';
        }
    }

    function preferredMimeType() {
        const supported = state.support.mimeTypes || [];
        return supported.find((type) => type.indexOf('video/webm') === 0) || supported[0] || '';
    }

    async function api(path, options) {
        const response = await window.fetch(config.restRoot.replace(/\/$/, '') + path, Object.assign({ credentials: 'same-origin', headers: { 'X-WP-Nonce': config.nonce } }, options || {}));
        const payload = await response.json().catch(() => ({}));
        if (!response.ok) {
            throw new Error(payload && payload.message ? payload.message : 'Studio request failed.');
        }
        return payload;
    }

    async function ensureSetupJob() {
        if (state.activeJobId) {
            return state.activeJobId;
        }
        const job = await api('/jobs', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': config.nonce }, body: JSON.stringify({ recording_mode: 'browser', source_type: 'studio_setup', source_id: 'studio-recording-' + Date.now(), quality_preset: els.qualitySelect ? els.qualitySelect.value : config.defaultQualityPreset, storage_provider: els.storageSelect ? els.storageSelect.value : config.recommendedStorageProvider }) });
        state.activeJobId = job.id;
        appendRecentJob(job);
        return job.id;
    }

    function isRecordingActive() {
        return state.recorder && state.recorder.state === 'recording';
    }

    async function startRecording() {
        if (!state.support.mediaRecorder || !preferredMimeType()) {
            setRecordingStatus(strings.recorderUnavailable, 'error');
            return;
        }
        let jobId = null;
        let serverRecordingStarted = false;
        try {
            if (state.programStream) {
                state.recordingStream = state.programStream;
            } else if (state.broadcastSession) {
                state.recordingStream = state.broadcastSession.getLocalMediaStream ? state.broadcastSession.getLocalMediaStream() : null;
                if (!state.recordingStream) {
                    throw new Error('The active broadcast tracks are unavailable for recording.');
                }
            } else if (state.cameraStream) {
                state.recordingStream = state.cameraStream;
            } else {
                await setPreviewSource('camera');
                state.recordingStream = state.programStream || state.cameraStream;
            }
            if (!state.recordingStream) {
                throw new Error('Choose a Program source before recording.');
            }
            jobId = await ensureSetupJob();
            const mimeType = preferredMimeType();
            const start = await api('/jobs/' + jobId + '/recording/start', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': config.nonce }, body: JSON.stringify({ mime_type: mimeType }) });
            serverRecordingStarted = true;
            state.browserSessionId = start.browser_session_id;
            state.selectedMimeType = start.mime_type;
            state.chunkIndex = 0;
            state.uploadedChunks.clear();
            state.failedChunks.clear();
            state.pendingUploads.clear();
            state.recordingStartedAt = Date.now();
            const preset = getSelectedPreset();
            const options = { mimeType: mimeType };
            if (preset.video_bitrate) { options.videoBitsPerSecond = preset.video_bitrate; }
            if (preset.audio_bitrate) { options.audioBitsPerSecond = preset.audio_bitrate; }
            state.recorder = new window.MediaRecorder(state.recordingStream, options);
            state.recorder.addEventListener('dataavailable', (event) => { if (event.data && event.data.size) { queueChunk(event.data, state.chunkIndex++); } });
            state.recorder.start((config.uploadSettings && config.uploadSettings.preferred_chunk_duration) || 5000);
            startTimer();
            setRecorderButtons(true, false);
            setShellClass('is-recording', true);
            setRecordingStatus(strings.recordingActive, 'success');
            renderRecordingState();
        } catch (error) {
            if (serverRecordingStarted && jobId) {
                await api('/jobs/' + jobId + '/cancel', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': config.nonce } }).catch(() => {});
                state.activeJobId = null;
                state.browserSessionId = '';
            }
            state.recorder = null;
            setRecorderButtons(false, false);
            setRecordingStatus(error.message || strings.recorderUnavailable, 'error');
        }
    }

    function queueChunk(blob, index) {
        state.pendingUploads.add(index);
        uploadChunk(blob, index).catch(() => {});
        renderRecordingState();
    }

    async function uploadChunk(blob, index) {
        try {
            setRecordingStatus(strings.uploadingChunk, 'info');
            const form = new FormData();
            form.append('browser_session_id', state.browserSessionId);
            form.append('chunk_index', String(index));
            form.append('mime_type', state.selectedMimeType);
            form.append('chunk', blob, 'chunk-' + index + (state.selectedMimeType === 'video/mp4' ? '.mp4' : '.webm'));
            const summary = await api('/jobs/' + state.activeJobId + '/chunks', { method: 'POST', body: form });
            state.pendingUploads.delete(index);
            state.failedChunks.delete(index);
            (summary.received_chunk_indexes || []).forEach((item) => state.uploadedChunks.add(Number(item)));
            if (els.recordingBytes) { els.recordingBytes.textContent = String(summary.received_bytes || 0); }
        } catch (error) {
            state.pendingUploads.delete(index);
            state.failedChunks.set(index, blob);
            setRecordingStatus(strings.chunkUploadFailed, 'error');
        }
        renderRecordingState();
    }

    async function stopRecording() {
        if (!state.recorder) { return; }
        await new Promise((resolve) => {
            state.recorder.addEventListener('stop', resolve, { once: true });
            state.recorder.stop();
        });
        stopTimer();
        state.finalChunkCount = state.chunkIndex;
        await waitForUploads();
        const duration = Math.max(0, Math.round((Date.now() - state.recordingStartedAt) / 1000));
        await api('/jobs/' + state.activeJobId + '/recording/stop', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': config.nonce }, body: JSON.stringify({ duration_seconds: duration }) });
        state.recorder = null;
        setShellClass('is-recording', false);
        setRecorderButtons(false, true);
        renderRecordingState();
    }

    async function waitForUploads() {
        while (state.pendingUploads.size) {
            await new Promise((resolve) => window.setTimeout(resolve, 250));
        }
    }

    function retryFailedChunks() {
        setRecordingStatus(strings.uploadRetry, 'info');
        Array.from(state.failedChunks.entries()).forEach(([index, blob]) => uploadChunk(blob, index));
    }

    async function finalizeRecording() {
        if (state.failedChunks.size || state.pendingUploads.size) { return; }
        try {
            if (els.recordingFinalizeStatus) { els.recordingFinalizeStatus.textContent = strings.finalizing; }
            const job = await api('/jobs/' + state.activeJobId + '/recording/finalize', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': config.nonce }, body: JSON.stringify({ expected_chunks: state.finalChunkCount }) });
            state.currentJobStatus = job.status || 'processing';
            setRecordingStatus('Recording finalized and temporary file validated. Replay publishing is ready.', 'success');
            appendRecentJob(job);
            updatePublishingButtons();
            if (els.recordingFinalizeStatus) { els.recordingFinalizeStatus.textContent = job.status || 'processing'; }
        } catch (error) {
            setRecordingStatus(error.message || strings.chunkUploadFailed, 'error');
        }
    }


    function setPublishingStatus(message, type) {
        if (!els.publishingStatus) {
            return;
        }
        els.publishingStatus.textContent = message || '';
        els.publishingStatus.dataset.statusType = type || 'info';
    }

    function renderReplayLink(url) {
        if (!els.replayLink || !els.replayLinkWrap) {
            return;
        }
        if (!url) {
            els.replayLinkWrap.hidden = true;
            els.replayLink.removeAttribute('href');
            els.replayLink.textContent = '';
            return;
        }
        els.replayLink.href = url;
        els.replayLink.textContent = url;
        els.replayLinkWrap.hidden = false;
    }


    function selectedStorageProviderId() {
        return els.storageSelect ? els.storageSelect.value : config.recommendedStorageProvider;
    }

    function selectedStorageProvider() {
        const providers = config.storageProviders || {};
        return providers[selectedStorageProviderId()] || {};
    }

    function updatePublishingButtons() {
        const canPublish = Boolean(state.activeJobId) && state.currentJobStatus === 'processing';
        if (els.publishReplay) { els.publishReplay.disabled = !canPublish; }
        if (els.checkPublishingStatus) { els.checkPublishingStatus.disabled = !state.activeJobId; }
    }

    async function publishReplay() {
        if (!state.activeJobId) {
            return;
        }
        const providerId = selectedStorageProviderId();
        const provider = selectedStorageProvider();
        if (providerId === 'local_media' && provider.available === false) {
            setPublishingStatus(strings.localMediaUnavailable, 'error');
            return;
        }
        if (els.publishReplay) { els.publishReplay.disabled = true; }
        setPublishingStatus(providerId === 'local_media' ? 'Publishing replay to WordPress Media Library...' : strings.publishingReplay, 'info');
        try {
            const result = await api('/jobs/' + state.activeJobId + '/publishing/publish', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': config.nonce } });
            state.currentJobStatus = result.job_status || 'ready';
            setPublishingStatus(providerId === 'local_media' ? (result.message || strings.localMediaReady || 'Replay saved to Media Library. Replay post created.') : (result.message || strings.publishComplete), 'success');
            renderReplayLink(result.replay_url || result.playback_url || '');
            appendRecentJob({ id: state.activeJobId, status: state.currentJobStatus, storage_provider: result.provider_id, file_size: result.file_size, mime_type: result.mime_type, publish_provider_status: result.publish_provider_status, replay_video_id: result.replay_video_id });
        } catch (error) {
            setPublishingStatus(error.message || strings.publishFailed, 'error');
        } finally {
            updatePublishingButtons();
        }
    }

    async function checkPublishingStatus() {
        if (!state.activeJobId) {
            return;
        }
        setPublishingStatus(strings.publishStatusChecking, 'info');
        try {
            const result = await api('/jobs/' + state.activeJobId + '/publishing/status', { method: 'GET' });
            state.currentJobStatus = result.job_status || state.currentJobStatus;
            setPublishingStatus((result.publish_provider_status || result.status || 'pending') + (result.videopress_guid ? ' · VideoPress GUID: ' + result.videopress_guid : ''), 'success');
            renderReplayLink(result.replay_url || result.playback_url || '');
        } catch (error) {
            setPublishingStatus(error.message || strings.publishFailed, 'error');
        } finally {
            updatePublishingButtons();
        }
    }

    function startTimer() {
        stopTimer();
        state.durationTimer = window.setInterval(renderRecordingState, 1000);
    }

    function stopTimer() {
        if (state.durationTimer) { window.clearInterval(state.durationTimer); }
        state.durationTimer = null;
    }

    function renderRecordingState() {
        if (els.recordingJobId) { els.recordingJobId.textContent = state.activeJobId || '—'; }
        if (els.recordingMime) { els.recordingMime.textContent = state.selectedMimeType || preferredMimeType() || '—'; }
        if (els.recordingUploaded) { els.recordingUploaded.textContent = String(state.uploadedChunks.size); }
        if (els.recordingPending) { els.recordingPending.textContent = String(state.pendingUploads.size); }
        if (els.recordingFailed) { els.recordingFailed.textContent = String(state.failedChunks.size); }
        if (els.recordingTimer && state.recordingStartedAt) {
            const seconds = Math.max(0, Math.floor((Date.now() - state.recordingStartedAt) / 1000));
            els.recordingTimer.textContent = String(Math.floor(seconds / 60)).padStart(2, '0') + ':' + String(seconds % 60).padStart(2, '0');
        }
        if (els.recordingProgress) {
            const total = Math.max(state.chunkIndex, state.finalChunkCount, 1);
            els.recordingProgress.value = Math.round((state.uploadedChunks.size / total) * 100);
        }
        if (els.retryChunks) { els.retryChunks.disabled = !state.failedChunks.size; }
        if (els.finalizeRecording) { els.finalizeRecording.disabled = !state.finalChunkCount || state.pendingUploads.size || state.failedChunks.size; }
    }

    function setRecorderButtons(recording, stopped) {
        if (els.startRecording) { els.startRecording.disabled = recording; }
        if (els.stopRecording) { els.stopRecording.disabled = !recording; }
        if (els.finalizeRecording) { els.finalizeRecording.disabled = !stopped; }
    }

    async function createSetupJob() {
        if (!els.createJob) {
            return;
        }

        els.createJob.disabled = true;
        setJobResult('', 'info');

        try {
            const response = await window.fetch(config.restRoot.replace(/\/$/, '') + '/jobs', {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': config.nonce,
                },
                body: JSON.stringify({
                    recording_mode: 'browser',
                    source_type: 'studio_setup',
                    source_id: 'studio-setup-' + Date.now(),
                    quality_preset: els.qualitySelect ? els.qualitySelect.value : config.defaultQualityPreset,
                    storage_provider: els.storageSelect ? els.storageSelect.value : config.recommendedStorageProvider,
                }),
            });
            const payload = await response.json();
            if (!response.ok) {
                throw new Error(payload && payload.message ? payload.message : strings.jobCreationFailed);
            }
            state.activeJobId = payload.id;
            setJobResult(strings.setupJobCreated + ' #' + payload.id, 'success');
            appendRecentJob(payload);
            setStatus(strings.setupJobCreated, 'success');
        } catch (error) {
            setJobResult(error.message || strings.jobCreationFailed, 'error');
        } finally {
            els.createJob.disabled = false;
        }
    }

    function appendRecentJob(job) {
        if (!els.recentJobsBody || !job) {
            return;
        }
        const row = document.createElement('tr');
        row.innerHTML = '<td>' + escapeHtml(String(job.id || '')) + '</td>' +
            '<td>' + escapeHtml(job.room_id || '') + '</td>' +
            '<td>' + escapeHtml(job.status || '') + '</td>' +
            '<td>' + escapeHtml(job.storage_provider || '') + '</td>' +
            '<td>' + escapeHtml(job.created_at || '') + '</td>' +
            '<td>' + escapeHtml(job.file_size ? String(job.file_size) : '—') + '</td>' +
            '<td>' + escapeHtml(job.mime_type || '—') + '</td>' +
            '<td>' + escapeHtml(job.assembled_at || '—') + '</td>' +
            '<td>' + escapeHtml(job.temp_expires_at || '—') + '</td>' +
            '<td>' + escapeHtml(job.publish_provider_status || '—') + '</td>' +
            '<td>' + (job.replay_video_id ? escapeHtml(String(job.replay_video_id)) : '—') + '</td>' +
            '<td>' + escapeHtml(job.error_message || '—') + '</td>';
        els.recentJobsBody.prepend(row);
        if (els.emptyJobs) {
            els.emptyJobs.hidden = true;
        }
    }

    function cleanup() {
        stopPreview();
        stopScreenPreview();
    }


    function setBroadcastStatus(message, type) {
        if (els.broadcastStatus) {
            els.broadcastStatus.textContent = message;
            els.broadcastStatus.dataset.statusType = type || 'info';
        }
    }

    function broadcastPayload() {
        const mode = els.broadcastMode ? els.broadcastMode.value : 'broadcast';
        return {
            video_id: state.broadcastVideoId || 0,
            title: els.broadcastTitle && els.broadcastTitle.value ? els.broadcastTitle.value : 'Studio Livestream',
            description: els.broadcastDescription ? els.broadcastDescription.value : '',
            agora_mode: mode,
            viewer_count: !!(els.broadcastViewerCount && els.broadcastViewerCount.checked),
            chat_enabled: !!(els.broadcastChat && els.broadcastChat.checked),
            agora_everyone_is_host: mode === 'interactive' && !!(els.broadcastEveryoneHost && els.broadcastEveryoneHost.checked),
            require_passcode: mode === 'interactive' && !!(els.broadcastRequirePasscode && els.broadcastRequirePasscode.checked),
            host_passcode: els.broadcastPasscode ? els.broadcastPasscode.value : '',
            quality_preset: els.qualitySelect ? els.qualitySelect.value : config.defaultQualityPreset,
            storage_provider: els.storageSelect ? els.storageSelect.value : config.recommendedStorageProvider,
        };
    }

    function updateBroadcastRules() {
        const interactive = els.broadcastMode && els.broadcastMode.value === 'interactive';
        els.interactiveOnly.forEach((el) => { el.hidden = !interactive; });
        if (!interactive) {
            if (els.broadcastEveryoneHost) els.broadcastEveryoneHost.checked = false;
            if (els.broadcastRequirePasscode) els.broadcastRequirePasscode.checked = false;
        }
        if (els.broadcastEveryoneHost && els.broadcastEveryoneHost.checked && els.broadcastRequirePasscode) {
            els.broadcastRequirePasscode.checked = false;
            els.broadcastRequirePasscode.disabled = true;
        } else if (els.broadcastRequirePasscode) {
            els.broadcastRequirePasscode.disabled = false;
        }
        if (els.broadcastRequirePasscode && els.broadcastRequirePasscode.checked && els.broadcastEveryoneHost) {
            els.broadcastEveryoneHost.checked = false;
            els.broadcastEveryoneHost.disabled = true;
        } else if (els.broadcastEveryoneHost) {
            els.broadcastEveryoneHost.disabled = false;
        }
        if (els.broadcastPasscodeWrap) {
            els.broadcastPasscodeWrap.hidden = !interactive || !els.broadcastRequirePasscode || !els.broadcastRequirePasscode.checked;
        }
    }


    function agoraVideoConfigFromPreset() {
        const preset = getSelectedPreset();
        const encoderConfig = {};
        if (preset.resolution) {
            encoderConfig.width = Number(preset.resolution.width);
            encoderConfig.height = Number(preset.resolution.height);
        }
        if (preset.fps) {
            encoderConfig.frameRate = Number(preset.fps);
        }
        const videoConfig = {};
        if (state.selectedCameraId) {
            videoConfig.cameraId = state.selectedCameraId;
        }
        if (Object.keys(encoderConfig).length) {
            videoConfig.encoderConfig = encoderConfig;
        }
        return videoConfig;
    }

    function agoraAudioConfigFromSelection() {
        return state.selectedMicId ? { microphoneId: state.selectedMicId } : {};
    }

    async function goLive() {
        if (!window.VH360AgoraBroadcaster) {
            setBroadcastStatus(strings.broadcastFailed, 'error');
            return;
        }
        if (isRecordingActive()) {
            setBroadcastStatus('Stop browser recording before going live, then start recording again after Agora is live so Studio can record the active broadcast tracks.', 'error');
            return;
        }
        if (!state.broadcastVideoId && els.broadcastMode && els.broadcastMode.value === 'interactive' && els.broadcastRequirePasscode && els.broadcastRequirePasscode.checked && (!els.broadcastPasscode || !els.broadcastPasscode.value.trim())) {
            setBroadcastStatus('Enter a passcode before enabling passcode access.', 'error');
            return;
        }
        setBroadcastStatus(strings.goingLive, 'info');
        if (els.goLive) els.goLive.disabled = true;
        try {
            if (!state.programSource) {
                await setPreviewSource('camera');
                await commitPreviewToProgram('cut');
            }
            setBroadcastStatus('Go Live will use the current Program source.', 'info');
            const created = await api('/broadcasts', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': config.nonce }, body: JSON.stringify(broadcastPayload()) });
            const broadcast = created.broadcast || {};
            state.broadcastVideoId = broadcast.videoId;
            state.activeJobId = created.job && created.job.id ? created.job.id : state.activeJobId;
            if (els.viewerLink && broadcast.viewerPermalink) {
                els.viewerLink.href = broadcast.viewerPermalink;
                els.viewerLink.textContent = broadcast.viewerPermalink;
                if (els.viewerLinkWrap) els.viewerLinkWrap.hidden = false;
            }
            const prepared = await api('/broadcasts/' + state.broadcastVideoId + '/prepare', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': config.nonce } });
            state.broadcastSession = window.VH360AgoraBroadcaster.create({
                appId: prepared.appId,
                channelName: prepared.channelName,
                token: prepared.token,
                uid: prepared.uid,
                clientMode: prepared.clientMode,
                container: root,
                localContainer: els.agoraLocalPreview,
                audioConfig: agoraAudioConfigFromSelection(),
                videoConfig: agoraVideoConfigFromPreset(),
            });
            await state.broadcastSession.start();
            if (state.programStream) {
                await replaceLiveVideoTrack(state.programStream);
            }
            await api('/broadcasts/' + state.broadcastVideoId + '/started', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': config.nonce }, body: JSON.stringify({ job_id: state.activeJobId || 0 }) });
            state.heartbeatTimer = window.setInterval(() => {
                api('/broadcasts/' + state.broadcastVideoId + '/heartbeat', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': config.nonce } }).catch(() => {});
            }, 30000);
            if (els.endLive) els.endLive.disabled = false;
            setShellClass('is-live', true);
            setBroadcastStatus(strings.liveStarted, 'success');
        } catch (error) {
            const failedVideoId = state.broadcastVideoId;
            if (state.broadcastSession) {
                await state.broadcastSession.stop().catch(() => {});
                state.broadcastSession = null;
            }
            if (failedVideoId) {
                await api('/broadcasts/' + failedVideoId + '/end', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': config.nonce } }).catch(() => {});
            }
            if (state.heartbeatTimer) {
                window.clearInterval(state.heartbeatTimer);
                state.heartbeatTimer = null;
            }
            if (els.endLive) els.endLive.disabled = true;
            if (els.goLive) els.goLive.disabled = false;
            setShellClass('is-live', false);
            setBroadcastStatus((error && error.message) || strings.broadcastFailed, 'error');
        }
    }

    async function endLive() {
        if (state.recorder) {
            setBroadcastStatus(strings.recordingActive, 'info');
            await stopRecording().catch((error) => {
                setRecordingStatus((error && error.message) || strings.chunkUploadFailed, 'error');
            });
        }
        if (state.heartbeatTimer) {
            window.clearInterval(state.heartbeatTimer);
            state.heartbeatTimer = null;
        }
        if (state.broadcastSession) {
            await state.broadcastSession.stop();
            state.broadcastSession = null;
        }
        if (state.broadcastVideoId) {
            try {
                await api('/broadcasts/' + state.broadcastVideoId + '/end', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': config.nonce } });
            } catch (error) {
                if (els.goLive) els.goLive.disabled = false;
                if (els.endLive) els.endLive.disabled = true;
                setBroadcastStatus('Local broadcast stopped, but WordPress could not mark the public livestream ended: ' + ((error && error.message) || 'Unknown error'), 'error');
                return;
            }
        }
        if (els.goLive) els.goLive.disabled = false;
        if (els.endLive) els.endLive.disabled = true;
        setShellClass('is-live', false);
        setBroadcastStatus(strings.liveEnded, 'success');
    }


    function endBroadcastKeepalive() {
        if (!state.broadcastVideoId || !state.broadcastSession) {
            return;
        }
        const url = config.restRoot.replace(/\/$/, '') + '/broadcasts/' + state.broadcastVideoId + '/end';
        window.fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            keepalive: true,
            headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': config.nonce },
            body: JSON.stringify({ reason: 'page_unload' })
        }).catch(() => {});
    }

    function bindEvents() {
        els.previewSourceButtons.forEach((button) => {
            button.addEventListener('click', () => setPreviewSource(button.dataset.previewSource));
        });
        els.sceneSourceButtons.forEach((button) => {
            button.addEventListener('click', () => setPreviewSource(button.dataset.sceneSource));
        });
        if (els.transitionCut) { els.transitionCut.addEventListener('click', () => commitPreviewToProgram('cut')); }
        if (els.transitionFade) { els.transitionFade.addEventListener('click', () => commitPreviewToProgram('fade')); }
        if (els.startPreview) {
            els.startPreview.addEventListener('click', startPreview);
        }
        if (els.stopPreview) {
            els.stopPreview.addEventListener('click', () => {
                stopPreview();
                setStatus(strings.ready, 'success');
            });
        }
        if (els.startScreen) {
            els.startScreen.addEventListener('click', startScreenPreview);
        }
        if (els.stopScreen) {
            els.stopScreen.addEventListener('click', () => {
                stopScreenPreview();
                setStatus(strings.ready, 'success');
            });
        }
        if (els.cameraSelect) {
            els.cameraSelect.addEventListener('change', () => {
                state.selectedCameraId = els.cameraSelect.value;
                if (state.cameraStream) {
                    startPreview();
                }
            });
        }
        if (els.micSelect) {
            els.micSelect.addEventListener('change', () => {
                state.selectedMicId = els.micSelect.value;
                if (state.cameraStream) {
                    startPreview();
                }
            });
        }
        if (els.qualitySelect) {
            els.qualitySelect.addEventListener('change', updateQualityDetails);
        }
        if (els.storageSelect) {
            els.storageSelect.addEventListener('change', () => {
                const provider = selectedStorageProvider();
                if (selectedStorageProviderId() === 'local_media') {
                    setJobResult(provider.available === false ? strings.localMediaUnavailable : strings.localMediaWarning, provider.available === false ? 'error' : 'warning');
                }
            });
        }
        if (els.createJob) {
            els.createJob.addEventListener('click', createSetupJob);
        }
        if (els.startRecording) { els.startRecording.addEventListener('click', startRecording); }
        if (els.stopRecording) { els.stopRecording.addEventListener('click', stopRecording); }
        if (els.retryChunks) { els.retryChunks.addEventListener('click', retryFailedChunks); }
        if (els.finalizeRecording) { els.finalizeRecording.addEventListener('click', finalizeRecording); }
        if (els.publishReplay) { els.publishReplay.addEventListener('click', publishReplay); }
        if (els.checkPublishingStatus) { els.checkPublishingStatus.addEventListener('click', checkPublishingStatus); }
        if (els.broadcastMode) { els.broadcastMode.addEventListener('change', updateBroadcastRules); }
        if (els.broadcastEveryoneHost) { els.broadcastEveryoneHost.addEventListener('change', updateBroadcastRules); }
        if (els.broadcastRequirePasscode) { els.broadcastRequirePasscode.addEventListener('change', updateBroadcastRules); }
        if (els.goLive) { els.goLive.addEventListener('click', goLive); }
        if (els.endLive) { els.endLive.addEventListener('click', endLive); }
        if (els.copyViewerLink) { els.copyViewerLink.addEventListener('click', () => { if (els.viewerLink && els.viewerLink.href) navigator.clipboard.writeText(els.viewerLink.href); }); }
        window.addEventListener('pagehide', endBroadcastKeepalive);
        window.addEventListener('beforeunload', (event) => {
            if (isRecordingActive()) {
                event.preventDefault();
                event.returnValue = '';
                return;
            }
            cleanup();
        });
        document.addEventListener('visibilitychange', () => {
            if (document.hidden && !isRecordingActive()) {
                cleanup();
            }
        });
    }

    detectSupport();
    renderSupportChecks();
    updateReadinessStatus();
    updateQualityDetails();
    updatePublishingButtons();
    updateBroadcastRules();
    renderSourceState();
    renderProgramState();
    bindEvents();
}());
