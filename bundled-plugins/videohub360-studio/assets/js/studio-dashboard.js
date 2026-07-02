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
    };

    const els = {
        status: root.querySelector('[data-studio-status]'),
        supportChecks: root.querySelector('[data-support-checks]'),
        cameraPreview: root.querySelector('[data-camera-preview]'),
        screenPreview: root.querySelector('[data-screen-preview]'),
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
    };

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

    async function startPreview() {
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
            const constraints = {
                video: state.selectedCameraId ? { deviceId: { exact: state.selectedCameraId } } : true,
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
            setStatus(strings.previewActive, 'success');
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

    async function startScreenPreview() {
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
            setStatus(strings.screenPreviewActive, 'success');
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
            '<td>' + escapeHtml(job.created_at || '') + '</td>';
        els.recentJobsBody.prepend(row);
        if (els.emptyJobs) {
            els.emptyJobs.hidden = true;
        }
    }

    function cleanup() {
        stopPreview();
        stopScreenPreview();
    }

    function bindEvents() {
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
        if (els.createJob) {
            els.createJob.addEventListener('click', createSetupJob);
        }
        window.addEventListener('beforeunload', cleanup);
        document.addEventListener('visibilitychange', () => {
            if (document.hidden) {
                cleanup();
            }
        });
    }

    detectSupport();
    renderSupportChecks();
    updateReadinessStatus();
    updateQualityDetails();
    bindEvents();
}());
