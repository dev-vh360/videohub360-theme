(function () {
    'use strict';

    const root = document.querySelector('[data-vh360-studio-dashboard]');
    if (!root || !window.vh360StudioDashboard) {
        return;
    }

    const config = window.vh360StudioDashboard;
    const strings = config.strings || {};
    function getStudioString(key, fallback) {
        return typeof strings[key] === 'string' && strings[key] ? strings[key] : fallback;
    }
    const supportLabels = config.supportLabels || {};
    const CHUNK_UPLOAD_CONCURRENCY = 2;
    const CAMERA_STORAGE_KEY = 'vh360_studio_camera_device_id';
    const CAMERA_SOURCES_STORAGE_KEY = 'vh360_studio_camera_sources';
    const MIC_STORAGE_KEY = 'vh360_studio_microphone_device_id';
    const AUDIO_INPUTS_STORAGE_KEY = 'vh360_studio_audio_inputs';
    const MAX_AUDIO_INPUTS = 8;
    const programRenderLayers = new Map();
    const state = {
        cameraSources: new Map(),
        primaryCameraSourceId: '',
        cameraSourceCounter: 0,
        availableVideoInputDevices: [],
        cameraSourceSaveTimer: null,
        screenStream: null,
        audioInputs: new Map(),
        primaryAudioInputId: '',
        audioInputCounter: 0,
        availableAudioInputDevices: [],
        audioInputSaveTimer: null,
        audioInputTestRequestId: 0,
        audioInputTestStream: null,
        studioTearingDown: false,
        lastRecordingAudioSummary: null,
        recordingPersistentWarnings: [],
        liveAudioWarningActive: false,
        lastAgoraConnectionState: '',
        deviceReadinessRequestId: 0,
        audioContext: null,
        analyser: null,
        meterFrame: null,
        audioMixer: null,
        support: {},
        activeJobId: null,
        browserSessionId: '',
        recorder: null,
        recordingStream: null,
        selectedMimeType: '',
        currentStorageProvider: '',
        chunkIndex: 0,
        pendingUploads: new Set(),
        uploadQueue: [],
        activeUploadCount: 0,
        uploadedChunks: new Set(),
        failedChunks: new Map(),
        directUploadParts: new Map(),
        directUploadBytes: 0,
        directUploadAvailable: true,
        recordingStartedAt: 0,
        recordingStoppedAt: null,
        recordingDurationSeconds: 0,
        recordingStopPromise: null,
        recordingStopRequested: false,
        durationTimer: null,
        finalChunkCount: 0,
        currentJobStatus: '',
        broadcastVideoId: null,
        broadcastSession: null,
        viewerPermalink: '',
        heartbeatTimer: null,
        previewSource: null,
        previewRequestId: 0,
        selectedSceneSource: '',
        programSource: null,
        programStream: null,
        mediaSources: new Map(),
        mediaSourceModalMode: 'upload',
        localMediaSourceCounter: 0,
        programCanvas: null,
        programContext: null,
        programOutputStream: null,
        programAnimationFrame: null,
        programHiddenTimer: null,
        programHiddenFrameRate: 8,
        programStatusMessage: 'Program active',
        tabProtectionWarningPending: false,
        studioDocumentHidden: false,
        hiddenActiveCameraSourceIds: new Set(),
        hiddenActiveAudioInputIds: new Set(),
        hiddenScreenWasActive: false,
        pageStoredInBackForwardCache: false,
        visibilityRestorePromise: null,
        isStudioWindow: false,
        programFrameRate: 30,
        programWidth: 1920,
        programHeight: 1080,
        recordingCanvasLocked: false,
        recordingCanvasWidth: 0,
        recordingCanvasHeight: 0,
        transitioning: false,
        broadcastStarting: false,
        broadcastReady: false,
        broadcastEnding: false,
        programSwitching: false,
        liveAudioMuted: false,
        liveVideoMuted: false,
        lastAgoraConnectionState: '',
        mediaControlsExpanded: false,
        lastRestError: '',
        mediaSourceUploadActive: false,
        cameraSourceModalTrigger: null,
        mediaSourceModalTrigger: null,
        publishPollTimer: null,
        publishPollAttempts: 0,
        publishStatusCheckInFlight: false,
        publishInFlight: false,
        currentPublishResult: null,
        directUploadInProgress: false,
        stopFailed: false,
        serverStopConfirmed: false,
        serverStopConfirming: false,
        serverStopPromise: null,
        finalizeInProgress: false,
        featuredImageId: 0,
        featuredImageUrl: '',
        clearFeaturedImage: false,
    };

    const els = {
        status: root.querySelector('[data-studio-status]'),
        supportChecks: root.querySelector('[data-support-checks]'),
        readinessSummary: root.querySelector('[data-readiness-summary]'),
        readinessHeading: root.querySelector('[data-readiness-heading]'),
        readinessMessage: root.querySelector('[data-readiness-message]'),
        readinessIssues: root.querySelector('[data-readiness-issues]'),
        diagnosticsButton: root.querySelector('[data-open-studio-diagnostics]'),
        diagnosticsModal: root.querySelector('[data-studio-diagnostics-modal]'),
        cameraPreviewContainer: root.querySelector('[data-camera-preview-container]'),
        screenPreview: root.querySelector('[data-screen-preview]'),
        previewOverlayCanvas: root.querySelector('[data-preview-overlay-canvas]'),
        programCanvas: root.querySelector('[data-program-canvas]'),
        programEmpty: root.querySelector('[data-program-empty]'),
        sceneList: root.querySelector('[data-scene-list]'),
        mediaPreview: root.querySelector('[data-media-preview]'),
        toggleMediaControls: root.querySelector('[data-toggle-media-controls]'),
        mediaPlaybackControls: root.querySelector('[data-media-playback-controls]'),
        mediaPlayPause: root.querySelector('[data-media-play-pause]'),
        mediaPlayPauseLabel: root.querySelector('[data-media-play-pause-label]'),
        mediaRestart: root.querySelector('[data-media-restart]'),
        mediaLoop: root.querySelector('[data-media-loop]'),
        mediaSeek: root.querySelector('[data-media-seek]'),
        mediaTime: root.querySelector('[data-media-time]'),
        transitionCut: root.querySelector('[data-transition-cut]'),
        transitionFade: root.querySelector('[data-transition-fade]'),
        transitionDuration: root.querySelector('[data-transition-duration]'),
        cameraSelect: root.querySelector('[data-camera-select]'),
        micSelect: root.querySelector('[data-mic-select]'),
        micMeter: root.querySelector('[data-mic-meter]'),
        audioMixer: root.querySelector('[data-audio-mixer]'),
        audioInputChannels: root.querySelector('[data-audio-input-channels]'),
        addAudioInput: root.querySelector('[data-add-audio-input]'),
        refreshDevices: root.querySelector('[data-refresh-devices]'),
        deviceStatus: root.querySelector('[data-device-status]'),
        activeDevices: root.querySelector('[data-active-devices]'),
        testCamera: root.querySelector('[data-test-camera]'),
        testMicrophone: root.querySelector('[data-test-microphone]'),
        mixerMeters: root.querySelectorAll('[data-mixer-meter]'),
        mixerStatuses: root.querySelectorAll('[data-mixer-status]'),
        qualitySelect: root.querySelector('[data-quality-select]'),
        qualityDetails: root.querySelector('[data-quality-details]'),
        selectedMediaControls: root.querySelector('[data-selected-media-controls]'),
        selectedMediaName: root.querySelector('[data-selected-media-name]'),
        mediaFitMode: root.querySelector('[data-media-fit-mode]'),
        mediaScale: root.querySelector('[data-media-scale]'),
        mediaScaleValue: root.querySelector('[data-media-scale-value]'),
        mediaPositionX: root.querySelector('[data-media-position-x]'),
        mediaPositionXValue: root.querySelector('[data-media-position-x-value]'),
        mediaPositionY: root.querySelector('[data-media-position-y]'),
        mediaPositionYValue: root.querySelector('[data-media-position-y-value]'),
        mediaResetTransform: root.querySelector('[data-media-reset-transform]'),
        programResolutionDetails: root.querySelector('[data-program-resolution-details]'),
        jobResult: root.querySelector('[data-job-result]'),
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
        recordingProgressLabel: root.querySelector('[data-recording-progress-label]'),
        recordingSummaryStatus: root.querySelector('[data-recording-summary-status]'),
        replayRawUrl: root.querySelector('[data-replay-raw-url]'),
        publishReplay: root.querySelector('[data-publish-replay]'),
        checkReplayStatus: root.querySelector('[data-check-replay-status]'),
        publishingStatus: root.querySelector('[data-publishing-status]'),
        replayLinkWrap: root.querySelector('[data-replay-link-wrap]'),
        replayLink: root.querySelector('[data-replay-link]'),
        broadcastTitle: root.querySelector('[data-broadcast-title]'),
        broadcastDescription: root.querySelector('[data-broadcast-description]'),
        coverImageId: root.querySelector('[data-cover-image-id]'),
        coverImagePreview: root.querySelector('[data-cover-image-preview]'),
        coverImagePreviewImg: root.querySelector('[data-cover-image-preview-img]'),
        selectCoverImage: root.querySelector('[data-select-cover-image]'),
        coverImageFile: root.querySelector('[data-cover-image-file]'),
        removeCoverImage: root.querySelector('[data-remove-cover-image]'),
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
        programEndLive: root.querySelector('[data-program-end-live]'),
        toggleMic: root.querySelector('[data-studio-toggle-mic]'),
        toggleVideo: root.querySelector('[data-studio-toggle-video]'),
        programLiveStatus: root.querySelector('[data-studio-program-live-status]'),
        broadcastStatus: root.querySelector('[data-broadcast-status]'),
        agoraLocalPreview: root.querySelector('[data-agora-local-preview]'),
        viewerLinkWrap: root.querySelector('[data-viewer-link-wrap]'),
        openViewerLink: root.querySelector('[data-open-viewer-link]'),
        copyViewerLink: root.querySelector('[data-copy-viewer-link]'),
        studioFullscreen: root.querySelector('[data-studio-fullscreen]'),
        openStudioWindow: root.querySelector('[data-open-studio-window]'),
        onAirNotice: root.querySelector('[data-on-air-notice]'),
        programDiagnostics: root.querySelector('[data-program-diagnostics]'),
        copyViewerFeedback: root.querySelector('[data-copy-viewer-feedback]'),
        sourceMenuToggle: root.querySelector('[data-toggle-source-menu]'),
        sourceMenu: root.querySelector('[data-source-menu]'),
        openCameraSource: root.querySelector('[data-open-camera-source]'),
        openLocalMediaSource: root.querySelector('[data-open-local-media-source]'),
        openUploadMediaSource: root.querySelector('[data-open-upload-media-source]'),
        deleteSelectedSourceScene: root.querySelector('[data-delete-selected-source-scene]'),
        cameraSourceModal: root.querySelector('[data-camera-source-modal]'),
        closeCameraSourceModal: root.querySelectorAll('[data-close-camera-source-modal]'),
        cameraSourceDevice: root.querySelector('[data-camera-source-device]'),
        cameraSourceName: root.querySelector('[data-camera-source-name]'),
        cameraSourceStatus: root.querySelector('[data-camera-source-status]'),
        addCameraSource: root.querySelector('[data-add-camera-source]'),
        selectedCameraControls: root.querySelector('[data-selected-camera-controls]'),
        selectedCameraName: root.querySelector('[data-selected-camera-name]'),
        selectedCameraStatus: root.querySelector('[data-selected-camera-status]'),
        mediaSourceModal: root.querySelector('[data-media-source-modal]'),
        mediaSourceModalTitle: root.querySelector('[data-media-source-modal-title]'),
        mediaSourceModalHelp: root.querySelector('[data-media-source-modal-help]'),
        closeMediaSourceModal: root.querySelectorAll('[data-close-media-source-modal]'),
        persistentMediaSourceInput: root.querySelector('[data-persistent-media-source-input]'),
        persistentMediaSourceName: root.querySelector('[data-persistent-media-source-name]'),
        persistentMediaSourceStatus: root.querySelector('[data-persistent-media-source-status]'),
        importMediaSource: root.querySelector('[data-import-media-source]'),
        operatorCanvasSupport: root.querySelector('[data-operator-canvas-support]'),
        operatorProgramSource: root.querySelector('[data-operator-program-source]'),
        operatorActiveJob: root.querySelector('[data-operator-active-job]'),
        operatorLastRestError: root.querySelector('[data-operator-last-rest-error]'),
        operatorRecordingFormat: root.querySelector('[data-operator-recording-format]'),
    };

    function defaultMediaTransform() {
        return {
            fitMode: 'fit',
            scale: 100,
            x: 0,
            y: 0,
        };
    }

    function setShellClass(className, active) {
        root.classList.toggle(className, Boolean(active));
    }

    function studioDebugLog() {
        if (window.__VH360_STUDIO_DEBUG === true && window.console && typeof window.console.info === 'function') {
            window.console.info.apply(window.console, arguments);
        }
    }

    function isMediaSource(sourceId) {
        return typeof sourceId === 'string' && sourceId.indexOf('media:') === 0;
    }

    function mediaIdFromSourceId(sourceId) {
        return isMediaSource(sourceId) ? sourceId.replace('media:', '') : '';
    }

    function getMediaSource(sourceId) {
        return state.mediaSources.get(mediaIdFromSourceId(sourceId)) || null;
    }

    function isCameraSource(sourceId) {
        return typeof sourceId === 'string' && sourceId.indexOf('camera:') === 0;
    }

    function cameraIdFromSourceId(sourceId) {
        return isCameraSource(sourceId) ? sourceId.replace('camera:', '') : '';
    }

    function getCameraSource(sourceId) {
        return state.cameraSources.get(cameraIdFromSourceId(sourceId)) || null;
    }

    function primaryCameraSource() {
        return state.cameraSources.get(state.primaryCameraSourceId) || Array.from(state.cameraSources.values()).find((source) => source.isPrimary) || null;
    }

    function primaryCameraSourceId() {
        const source = primaryCameraSource();
        return source ? source.sourceId : '';
    }

    function getSelectedCameraSource() {
        return isCameraSource(state.selectedSceneSource) ? getCameraSource(state.selectedSceneSource) : null;
    }

    function getActiveCameraSource() {
        if (isCameraSource(state.selectedSceneSource)) { return getCameraSource(state.selectedSceneSource); }
        if (isCameraSource(state.previewSource)) { return getCameraSource(state.previewSource); }
        return primaryCameraSource();
    }

    function hasLiveVideoTrack(stream) {
        return Boolean(stream && stream.getVideoTracks && stream.getVideoTracks().some((track) => track.readyState !== 'ended'));
    }

    function hasProgramOutput() {
        return Boolean(state.programSource && (state.programStream || isMediaSource(state.programSource) || (isCameraSource(state.programSource) && hasLiveVideoTrack((getCameraSource(state.programSource) || {}).stream))));
    }

    function updateOperatorStatus() {
        if (els.operatorCanvasSupport) {
            els.operatorCanvasSupport.textContent = state.support.canvasCapture && state.support.canvasContext ? 'Supported' : 'Unsupported';
        }
        if (els.operatorProgramSource) {
            els.operatorProgramSource.textContent = state.programSource ? sourceLabel(state.programSource) : 'None';
        }
        if (els.operatorActiveJob) {
            els.operatorActiveJob.textContent = state.activeJobId || '—';
        }
        if (els.operatorLastRestError) {
            els.operatorLastRestError.textContent = state.lastRestError || 'None';
        }
        if (els.operatorRecordingFormat) {
            const recordingMimeType = state.selectedMimeType || preferredMimeType();
            els.operatorRecordingFormat.textContent = recordingFormatLabel(recordingMimeType);
            els.operatorRecordingFormat.dataset.statusType = isWebmMime(recordingMimeType) ? 'warning' : 'success';
        }
    }

    function sourceLabel(sourceId) {
        if (sourceId === 'screen') {
            return getStudioString('screenShareLabel', 'Screen Share');
        }

        if (isCameraSource(sourceId)) {
            const cameraSource = getCameraSource(sourceId);
            return (cameraSource && cameraSource.label) || getStudioString('cameraSummaryLabel', 'Camera');
        }

        const mediaSource = getMediaSource(sourceId);
        if (mediaSource) {
            return mediaSource.name || 'Media Source';
        }

        return 'Source';
    }

    async function getSourceStream(sourceId) {
        if (sourceId === 'screen') {
            if (!state.screenStream || !state.screenStream.getVideoTracks().length || state.screenStream.getVideoTracks()[0].readyState === 'ended') {
                await startScreenPreview(false);
            }
            return state.screenStream;
        }
        if (isCameraSource(sourceId)) {
            return ensureCameraSourceStream(sourceId);
        }
        if (isMediaSource(sourceId)) {
            const mediaSource = getMediaSource(sourceId);
            if (mediaSource) {
                return null;
            }
        }
        throw new Error('Unknown source: ' + sourceId);
    }

    async function setPreviewSource(sourceId, options = {}) {
        const requestId = options.requestId || ++state.previewRequestId;
        const isCurrentPreviewRequest = () => requestId === state.previewRequestId;
        if (isMediaSource(sourceId)) {
            const mediaSource = getMediaSource(sourceId);

            if (!mediaSource) {
                setStatus('Media source is no longer available.', 'warning');
                return;
            }

            if (!isCurrentPreviewRequest()) { return false; }
            state.previewSource = sourceId;

            if (mediaSource.type === 'video') {
                mediaSource.element.play().catch(() => {});
            }

            renderSourceState();
            setStatus(sourceLabel(sourceId) + ' staged in Preview.', 'success');
            return true;
        }

        const stream = await getSourceStream(sourceId);
        if (!stream) {
            return false;
        }
        if (!isCurrentPreviewRequest()) {
            return false;
        }
        if (isCameraSource(sourceId)) {
            const cameraSource = getCameraSource(sourceId);
            const ready = await ensureCameraElementPlaying(cameraSource);
            if (!ready) {
                setStatus((cameraSource && cameraSource.error) || getStudioString('cameraPlaybackNotReady', 'Camera video is not ready yet.'), 'warning');
                renderSourceState();
                return false;
            }
            if (!isCurrentPreviewRequest()) {
                return false;
            }
        }
        state.previewSource = sourceId;
        renderSourceState();
        setStatus(sourceLabel(sourceId) + ' staged in Preview.', 'success');
        return true;
    }

    async function commitPreviewToProgram(transitionType) {
        if (!state.previewSource) {
            setStatus('Choose a Preview source before using Cut or Fade.', 'warning');
            return;
        }
        if (state.transitioning || state.programSwitching) {
            return;
        }
        const nextSource = state.previewSource;
        if (state.programSource === nextSource && hasProgramOutput()) {
            setStatus(sourceLabel(nextSource) + ' is already in Program.', 'info');
            renderTransitionButtons();
            return;
        }
        state.programSwitching = true;
        renderTransitionButtons();
        state.transitioning = transitionType === 'fade';
        const duration = Math.max(0, Math.min(2000, Number(els.transitionDuration && els.transitionDuration.value) || 300));
        root.style.setProperty('--vh360-studio-transition-duration', duration + 'ms');
        if (state.transitioning) {
            root.classList.add('is-transitioning', 'is-fading');
        }
        try {
            let stream = null;

            if (isMediaSource(nextSource)) {
                const mediaSource = getMediaSource(nextSource);

                if (!mediaSource) {
                    throw new Error('Media source is no longer available.');
                }

                if (mediaSource.type === 'video') {
                    await mediaSource.element.play().catch(() => {});
                }
            } else {
                stream = await getSourceStream(nextSource);
                if (isCameraSource(nextSource)) {
                    const cameraSource = getCameraSource(nextSource);
                    const ready = await ensureCameraElementPlaying(cameraSource);
                    if (!ready) {
                        throw new Error((cameraSource && cameraSource.error) || getStudioString('cameraPlaybackNotReady', 'Camera video is not ready yet.'));
                    }
                }
            }

            state.programSource = nextSource;
            state.programStream = stream;
            ensureProgramCompositor();
            renderProgramState();
            setStatus(sourceLabel(state.programSource) + ' sent to Program.', 'success');
            if (transitionType === 'fade') {
                await new Promise((resolve) => window.setTimeout(resolve, duration));
            }
        } catch (error) {
            setStatus((error && error.message) || 'Program source could not be changed.', 'error');
        } finally {
            state.transitioning = false;
            state.programSwitching = false;
            root.classList.remove('is-transitioning', 'is-fading');
            renderSourceState();
            renderTransitionButtons();
        }
    }

    function sourceType(sourceId) {
        if (isCameraSource(sourceId)) { return 'camera'; }
        if (sourceId === 'screen') { return 'screen'; }
        if (isMediaSource(sourceId)) { return 'media'; }
        return '';
    }

    function sourceSummary(sourceId) {
        return {
            sourceId: sourceId || '',
            sourceType: sourceType(sourceId),
            hasOutput: sourceId === state.previewSource ? Boolean(sourceId) : hasProgramOutput(),
        };
    }

    function dispatchStudioEvent(name, detail) {
        root.dispatchEvent(new CustomEvent(name, { bubbles: true, detail }));
    }

    function dispatchPreviewSourceChange() {
        const detail = sourceSummary(state.previewSource);
        detail.hasOutput = Boolean(state.previewSource);
        dispatchStudioEvent('vh360:studio:preview-source-change', detail);
    }

    function dispatchProgramSourceChange() {
        const detail = sourceSummary(state.programSource);
        detail.hasOutput = hasProgramOutput();
        dispatchStudioEvent('vh360:studio:program-source-change', detail);
    }

    function dispatchProgramResolutionChange() {
        dispatchStudioEvent('vh360:studio:program-resolution-change', {
            width: state.programWidth || 1920,
            height: state.programHeight || 1080,
            fps: state.programFrameRate || 30,
        });
    }

    function fallbackPreviewSource(endedSource) {
        if (endedSource === 'screen') {
            const camera = Array.from(state.cameraSources.values()).find((source) => hasLiveVideoTrack(source.stream));
            return camera ? camera.sourceId : null;
        }
        if (isCameraSource(endedSource) && state.screenStream) {
            return 'screen';
        }
        if (isCameraSource(endedSource)) {
            const camera = Array.from(state.cameraSources.values()).find((source) => source.sourceId !== endedSource && hasLiveVideoTrack(source.stream));
            return camera ? camera.sourceId : null;
        }
        return null;
    }

    function renderPreviewState() {
        root.dataset.previewSource = state.previewSource || '';
        root.classList.toggle('is-preview-source-camera', isCameraSource(state.previewSource));
        root.classList.toggle('is-preview-source-screen', state.previewSource === 'screen');
        root.classList.toggle('is-preview-source-media', isMediaSource(state.previewSource));

        if (els.cameraPreviewContainer) {
            state.cameraSources.forEach((source) => {
                ensureCameraElementAttached(source);
                if (!source.element) { return; }
                const active = source.sourceId === state.previewSource;
                source.element.classList.toggle('is-preview-active', active);
                source.element.setAttribute('aria-hidden', active ? 'false' : 'true');
            });
        }

        if (els.mediaPreview) {
            els.mediaPreview.innerHTML = '';

            if (isMediaSource(state.previewSource)) {
                const mediaSource = getMediaSource(state.previewSource);
                if (mediaSource && mediaSource.element) {
                    els.mediaPreview.appendChild(mediaSource.element);
                    renderPreviewMediaTransform();
                }
            }
        }

        renderMediaPlaybackControls();
        renderSelectedMediaControls();
        dispatchPreviewSourceChange();
    }

    function renderSourceState() {
        renderPreviewState();
        root.querySelectorAll('[data-scene-source]').forEach((button) => {
            const active = button.dataset.sceneSource === state.previewSource;
            button.classList.toggle('is-active', active);
            if (active) {
                button.setAttribute('aria-current', 'true');
            } else {
                button.removeAttribute('aria-current');
            }
        });
        renderSceneControls();
        renderMediaPlaybackControls();
        renderSelectedMediaControls();
        renderSelectedCameraControls();
        updateOperatorStatus();
        renderProgramLiveControls();
    }

    function ensureCameraElementAttached(source) {
        if (!source || source.removed || source.detached || !source.element || !els.cameraPreviewContainer) { return; }
        if (source.element.parentNode !== els.cameraPreviewContainer) {
            els.cameraPreviewContainer.appendChild(source.element);
        }
    }

    function isCameraElementDrawable(source) {
        const element = source && source.element;
        return Boolean(
            element &&
            !element.paused &&
            !element.ended &&
            element.readyState >= 2 &&
            element.videoWidth > 0 &&
            element.videoHeight > 0
        );
    }

    function waitForCameraElementData(element, timeoutMs = 2500) {
        if (!element) { return Promise.resolve(false); }
        if (element.readyState >= 2 && element.videoWidth > 0 && element.videoHeight > 0) {
            return Promise.resolve(true);
        }
        return new Promise((resolve) => {
            let settled = false;
            const done = () => {
                if (settled) { return; }
                settled = true;
                cleanupListeners();
                resolve(element.readyState >= 2 && element.videoWidth > 0 && element.videoHeight > 0);
            };
            const cleanupListeners = () => {
                window.clearTimeout(timer);
                ['loadedmetadata', 'loadeddata', 'canplay', 'playing'].forEach((eventName) => {
                    element.removeEventListener(eventName, done);
                });
            };
            const timer = window.setTimeout(done, timeoutMs);
            ['loadedmetadata', 'loadeddata', 'canplay', 'playing'].forEach((eventName) => {
                element.addEventListener(eventName, done, { once: true });
            });
        });
    }

    async function ensureCameraElementPlaying(source, options = {}) {
        if (!source || !source.element || !hasLiveVideoTrack(source.stream)) { return false; }
        ensureCameraElementAttached(source);
        if (source.element.srcObject !== source.stream) {
            source.element.srcObject = source.stream;
        }
        try {
            if (source.element.paused || source.element.ended || source.element.readyState < 2 || !source.element.videoWidth || !source.element.videoHeight) {
                await source.element.play();
            }
            const ready = await waitForCameraElementData(source.element, options.timeoutMs || 2500);
            if (ready && isCameraElementDrawable(source)) {
                source.error = source.error === getStudioString('cameraPlaybackNotReady', 'Camera video is not ready yet.') ? '' : source.error;
                return true;
            }
            source.error = getStudioString('cameraPlaybackNotReady', 'Camera video is not ready yet.');
            return false;
        } catch (error) {
            source.error = (error && error.message) || getStudioString('cameraPlaybackFailed', 'Camera playback could not resume.');
            setDeviceStatus(source.label + ': ' + source.error, 'warning');
            return false;
        }
    }

    function selectSceneSource(sourceId) {
        state.selectedSceneSource = sourceId || '';
        if (!getActiveMediaSource()) {
            state.mediaControlsExpanded = false;
        }
        renderSourceState();
        renderSceneControls();
        renderMediaPlaybackControls();
        renderSelectedMediaControls();
    }

    function getSelectedMediaSource() {
        if (!isMediaSource(state.selectedSceneSource)) {
            return null;
        }

        return getMediaSource(state.selectedSceneSource);
    }

    function getActiveMediaSource() {
        if (isMediaSource(state.previewSource)) {
            return getMediaSource(state.previewSource);
        }

        if (isMediaSource(state.selectedSceneSource)) {
            return getMediaSource(state.selectedSceneSource);
        }

        return null;
    }

    function getActiveVideoMediaSource() {
        const source = getActiveMediaSource();
        return source && source.type === 'video' ? source : null;
    }

    function formatMediaTime(seconds) {
        if (!Number.isFinite(seconds) || seconds < 0) {
            seconds = 0;
        }

        const total = Math.floor(seconds);
        const minutes = Math.floor(total / 60);
        const remainder = total % 60;

        return String(minutes).padStart(2, '0') + ':' + String(remainder).padStart(2, '0');
    }

    function renderMediaPlaybackControls() {
        const source = getActiveVideoMediaSource();
        const video = source && source.element;

        if (els.mediaPlaybackControls) {
            els.mediaPlaybackControls.hidden = !video;
        }

        if (!video) {
            return;
        }

        if (els.mediaLoop) {
            els.mediaLoop.checked = Boolean(video.loop);
        }

        if (els.mediaPlayPauseLabel) {
            els.mediaPlayPauseLabel.textContent = video.paused ? 'Play' : 'Pause';
        }

        if (els.mediaSeek) {
            const duration = Number.isFinite(video.duration) && video.duration > 0 ? video.duration : 0;
            els.mediaSeek.disabled = !duration;
            els.mediaSeek.value = duration ? Math.round((video.currentTime / duration) * 1000) : 0;
        }

        if (els.mediaTime) {
            const duration = Number.isFinite(video.duration) && video.duration > 0 ? video.duration : 0;
            els.mediaTime.textContent = formatMediaTime(video.currentTime || 0) + ' / ' + formatMediaTime(duration);
        }
    }

    function renderSelectedMediaControls() {
        const source = getActiveMediaSource();
        const hasMedia = Boolean(source);

        if (els.toggleMediaControls) {
            els.toggleMediaControls.hidden = !hasMedia;
            els.toggleMediaControls.disabled = !hasMedia;
            els.toggleMediaControls.setAttribute(
                'aria-expanded',
                hasMedia && state.mediaControlsExpanded ? 'true' : 'false'
            );
        }

        if (!hasMedia) {
            state.mediaControlsExpanded = false;

            if (els.selectedMediaControls) {
                els.selectedMediaControls.hidden = true;
            }

            return;
        }

        if (els.selectedMediaControls) {
            els.selectedMediaControls.hidden = !state.mediaControlsExpanded;
        }

        const transform = source.transform || defaultMediaTransform();
        source.transform = transform;

        if (els.selectedMediaName) {
            els.selectedMediaName.textContent = source.name || 'Media Source';
        }

        if (els.mediaFitMode) {
            els.mediaFitMode.value = transform.fitMode || 'fit';
        }

        if (els.mediaScale) {
            els.mediaScale.value = transform.scale;
        }

        if (els.mediaScaleValue) {
            els.mediaScaleValue.textContent = transform.scale + '%';
        }

        if (els.mediaPositionX) {
            els.mediaPositionX.value = transform.x;
        }

        if (els.mediaPositionXValue) {
            els.mediaPositionXValue.textContent = String(transform.x);
        }

        if (els.mediaPositionY) {
            els.mediaPositionY.value = transform.y;
        }

        if (els.mediaPositionYValue) {
            els.mediaPositionYValue.textContent = String(transform.y);
        }

        if (els.programResolutionDetails) {
            els.programResolutionDetails.textContent = 'Program canvas: ' + state.programWidth + '×' + state.programHeight + ' at ' + state.programFrameRate + 'fps.';
        }
    }

    function getPreviewStageResolution() {
        return {
            width: state.programWidth || 1920,
            height: state.programHeight || 1080,
        };
    }

    function getRecordingOutputSize() {
        if (isRecordingCanvasLocked() && state.recordingCanvasWidth && state.recordingCanvasHeight) {
            return {
                width: state.recordingCanvasWidth,
                height: state.recordingCanvasHeight,
                fps: state.programFrameRate || 30,
            };
        }

        const preset = getSelectedPreset();
        const resolution = preset && preset.resolution ? preset.resolution : {};

        return {
            width: Number(resolution.width) || 1920,
            height: Number(resolution.height) || 1080,
            fps: Number(preset && preset.fps) || 30,
        };
    }

    function isRecordingCanvasLocked() {
        return Boolean(state.recordingCanvasLocked);
    }

    function lockRecordingCanvasSize() {
        const outputSize = getRecordingOutputSize();

        state.recordingCanvasLocked = true;
        state.recordingCanvasWidth = outputSize.width;
        state.recordingCanvasHeight = outputSize.height;
        state.programWidth = outputSize.width;
        state.programHeight = outputSize.height;
        state.programFrameRate = outputSize.fps;

        if (state.programCanvas) {
            state.programCanvas.width = state.programWidth;
            state.programCanvas.height = state.programHeight;
        }

        renderPreviewMediaTransform();
        renderSelectedMediaControls();
        dispatchProgramResolutionChange();
    }

    function unlockRecordingCanvasSize() {
        state.recordingCanvasLocked = false;
        state.recordingCanvasWidth = 0;
        state.recordingCanvasHeight = 0;
    }

    function renderPreviewMediaTransform() {
        if (!els.mediaPreview) {
            return;
        }

        const source = isMediaSource(state.previewSource) ? getMediaSource(state.previewSource) : null;
        const element = source && source.element;

        if (!element) {
            return;
        }

        const transform = source.transform || defaultMediaTransform();
        const stage = getPreviewStageResolution();
        const natural = getMediaNaturalSize(element, source.type, stage.width, stage.height);
        const rect = calculateMediaDrawRect(
            natural.width,
            natural.height,
            stage.width,
            stage.height,
            transform
        );

        element.dataset.fitMode = transform.fitMode || 'fit';
        element.style.left = (rect.x / stage.width * 100) + '%';
        element.style.top = (rect.y / stage.height * 100) + '%';
        element.style.width = (rect.width / stage.width * 100) + '%';
        element.style.height = (rect.height / stage.height * 100) + '%';
    }

    function renderCameraScenes() {
        if (!els.sceneList) { return; }
        els.sceneList.querySelectorAll('[data-camera-scene], [data-screen-scene]').forEach((item) => item.remove());
        const before = els.sceneList.firstChild;
        const cameras = Array.from(state.cameraSources.values()).filter((source) => !source.removed).sort((a, b) => (a.isPrimary === b.isPrimary ? a.id.localeCompare(b.id) : (a.isPrimary ? -1 : 1)));
        cameras.forEach((source) => {
            const item = document.createElement('li');
            item.dataset.cameraScene = 'true';
            const button = document.createElement('button');
            button.type = 'button';
            button.dataset.sceneSource = source.sourceId;
            button.textContent = source.label || getStudioString('cameraSummaryLabel', 'Camera');
            button.classList.toggle('is-unavailable', source.unavailable || source.status === 'disconnected');
            item.appendChild(button);
            els.sceneList.insertBefore(item, before);
        });
        const screenItem = document.createElement('li');
        screenItem.dataset.screenScene = 'true';
        const screenButton = document.createElement('button');
        screenButton.type = 'button';
        screenButton.dataset.sceneSource = 'screen';
        screenButton.textContent = getStudioString('screenShareLabel', 'Screen Share');
        screenItem.appendChild(screenButton);
        els.sceneList.insertBefore(screenItem, before);
    }

    function renderSelectedCameraControls() {
        const source = getActiveCameraSource();
        const show = Boolean(source && isCameraSource(state.selectedSceneSource));
        if (els.selectedCameraControls) { els.selectedCameraControls.hidden = !show; }
        if (els.cameraSelect) {
            els.cameraSelect.disabled = !show || source.connecting || !(state.availableVideoInputDevices || []).length;
        }
        if (els.testCamera) {
            els.testCamera.disabled = !show;
        }
        if (!show) {
            if (els.selectedCameraName) { els.selectedCameraName.value = ''; }
            if (els.selectedCameraStatus) {
                els.selectedCameraStatus.textContent = '';
                els.selectedCameraStatus.dataset.statusType = 'info';
            }
            return;
        }
        if (els.selectedCameraName) { els.selectedCameraName.value = source.label || ''; }
        if (els.selectedCameraStatus) {
            els.selectedCameraStatus.textContent = cameraSourceStatusLabel(source.status) + (source.error ? ' · ' + source.error : '');
            els.selectedCameraStatus.dataset.statusType = source.status === 'active' ? 'success' : (source.status === 'error' || source.status === 'unavailable' || source.status === 'disconnected' ? 'warning' : 'info');
        }
        if (els.cameraSelect && source) {
            fillDeviceSelect(els.cameraSelect, state.availableVideoInputDevices || [], getStudioString('cameraSummaryLabel', 'Camera'), source.deviceId);
            preserveSelectedCameraDeviceOption(els.cameraSelect, source);
        }
    }

    function renderSceneControls() {
        if (!els.deleteSelectedSourceScene) {
            return;
        }

        const selectedMedia = getSelectedMediaSource();
        const selectedCamera = getSelectedCameraSource();
        els.deleteSelectedSourceScene.disabled = !(selectedMedia || (selectedCamera && !selectedCamera.isPrimary));
    }

    function renderTransitionButtons() {
        const blocked = state.broadcastStarting || state.broadcastEnding || state.programSwitching || (state.broadcastSession && !state.broadcastReady) || state.lastAgoraConnectionState === 'RECONNECTING' || state.lastAgoraConnectionState === 'DISCONNECTED';
        if (els.transitionCut) {
            els.transitionCut.disabled = blocked;
        }
        if (els.transitionFade) {
            els.transitionFade.disabled = blocked;
        }
    }

    function ensureProgramCompositor() {
        if (!els.programCanvas) {
            return null;
        }
        if (!state.programCanvas) {
            state.programCanvas = els.programCanvas;
            state.programCanvas.width = state.programWidth;
            state.programCanvas.height = state.programHeight;
            state.programContext = state.support.canvasContext ? state.programCanvas.getContext('2d') : null;
        }
        if (!state.programOutputStream && typeof state.programCanvas.captureStream === 'function') {
            state.programOutputStream = state.programCanvas.captureStream(state.programFrameRate);
        }
        updateProgramCompositorLoop();
        return state.programOutputStream;
    }

    function stopProgramCompositor(options = {}) {
        stopVisibleProgramLoop();
        stopHiddenProgramLoop();
        if (options.stopTracks && state.programOutputStream) {
            state.programOutputStream.getTracks().forEach((track) => track.stop());
        }
        if (options.clearStream) {
            state.programOutputStream = null;
        }
    }

    function isOnAirOrRecording() {
        return Boolean(state.broadcastSession || isRecordingActive());
    }

    function getProgramVideoTrack() {
        return state.programOutputStream && state.programOutputStream.getVideoTracks ? state.programOutputStream.getVideoTracks()[0] : null;
    }

    function requestProgramFrameIfSupported() {
        const programTrack = getProgramVideoTrack();
        if (programTrack && typeof programTrack.requestFrame === 'function') {
            programTrack.requestFrame();
        }
    }

    function stopVisibleProgramLoop() {
        if (state.programAnimationFrame) {
            window.cancelAnimationFrame(state.programAnimationFrame);
            state.programAnimationFrame = null;
        }
    }

    function stopHiddenProgramLoop() {
        if (state.programHiddenTimer) {
            window.clearTimeout(state.programHiddenTimer);
            state.programHiddenTimer = null;
        }
    }

    function updateProgramCompositorLoop() {
        if (!state.programContext || !state.programCanvas) {
            stopVisibleProgramLoop();
            stopHiddenProgramLoop();
            return;
        }
        if (document.hidden && isOnAirOrRecording()) {
            stopVisibleProgramLoop();
            if (!state.programHiddenTimer) {
                drawProgramFrame({ hiddenFallback: true });
            }
            setProgramDiagnostics(getStudioString('studioHiddenBackgroundWarning', 'Studio is hidden. Browser background limits may reduce the Program frame rate.'));
            return;
        }
        stopHiddenProgramLoop();
        if (!state.programAnimationFrame) {
            drawProgramFrame();
        }
        setProgramDiagnostics(state.programStatusMessage || 'Program active');
    }

    function scheduleNextProgramFrame(hiddenFallback) {
        if (hiddenFallback) {
            if (document.hidden && isOnAirOrRecording()) {
                state.programHiddenTimer = window.setTimeout(() => drawProgramFrame({ hiddenFallback: true }), Math.round(1000 / state.programHiddenFrameRate));
            } else {
                state.programHiddenTimer = null;
                updateProgramCompositorLoop();
            }
            return;
        }
        if (!document.hidden || !isOnAirOrRecording()) {
            state.programAnimationFrame = window.requestAnimationFrame(drawProgramFrame);
        } else {
            state.programAnimationFrame = null;
            updateProgramCompositorLoop();
        }
    }

    function drawProgramRenderLayers(context, frame) {
        Array.from(programRenderLayers.values())
            .sort((a, b) => a.order - b.order)
            .forEach((layer) => {
                context.save();
                try {
                    layer.draw(context, frame);
                } catch (error) {
                    console.error('Studio overlay layer failed:', layer.id, error);
                } finally {
                    context.restore();
                }
            });
    }

    function drawProgramFrame(options = {}) {
        const hiddenFallback = Boolean(options.hiddenFallback);
        if (!state.programContext || !state.programCanvas) {
            stopVisibleProgramLoop();
            stopHiddenProgramLoop();
            return;
        }
        const context = state.programContext;
        const width = state.programCanvas.width;
        const height = state.programCanvas.height;
        context.fillStyle = '#020617';
        context.fillRect(0, 0, width, height);
        if (state.liveVideoMuted) {
            context.fillStyle = '#e5e7eb';
            context.font = '700 ' + Math.max(20, Math.round(width * 0.035)) + 'px sans-serif';
            context.textAlign = 'center';
            context.textBaseline = 'middle';
            context.fillText('Program video off', width / 2, height / 2);
            if (hiddenFallback) { requestProgramFrameIfSupported(); }
            scheduleNextProgramFrame(hiddenFallback);
            return;
        }
        if (isCameraSource(state.programSource)) {
            const cameraSource = getCameraSource(state.programSource);
            if (isCameraElementDrawable(cameraSource)) {
                drawVideoContain(context, cameraSource.element, width, height, true);
            } else if (cameraSource && !cameraSource.playbackRecoveryPending) {
                cameraSource.playbackRecoveryPending = true;
                ensureCameraElementPlaying(cameraSource)
                    .then((ready) => {
                        if (!ready) {
                            setProgramDiagnostics((cameraSource.label || sourceLabel(cameraSource.sourceId)) + ': ' + (cameraSource.error || getStudioString('cameraPlaybackNotReady', 'Camera video is not ready yet.')));
                        }
                    })
                    .finally(() => { cameraSource.playbackRecoveryPending = false; });
            }
        } else if (state.programSource === 'screen') {
            if (els.screenPreview && els.screenPreview.readyState >= 2) {
                drawVideoContain(context, els.screenPreview, width, height, false);
            }
        } else if (isMediaSource(state.programSource)) {
            const mediaSource = getMediaSource(state.programSource);

            if (mediaSource && mediaSource.type === 'image' && mediaSource.element.complete) {
                drawMediaElement(context, mediaSource.element, width, height, mediaSource.transform || defaultMediaTransform(), 'image');
            }

            if (mediaSource && mediaSource.type === 'video' && mediaSource.element.readyState >= 2) {
                drawMediaElement(context, mediaSource.element, width, height, mediaSource.transform || defaultMediaTransform(), 'video');
            }
        }
        drawProgramRenderLayers(context, {
            width,
            height,
            now: performance.now(),
            hiddenFallback,
            programSourceActive: hasProgramOutput(),
        });
        if (hiddenFallback) {
            requestProgramFrameIfSupported();
        }
        scheduleNextProgramFrame(hiddenFallback);
    }

    function getMediaNaturalSize(element, type, fallbackWidth, fallbackHeight) {
        if (type === 'image') {
            return {
                width: element.naturalWidth || fallbackWidth,
                height: element.naturalHeight || fallbackHeight,
            };
        }

        return {
            width: element.videoWidth || fallbackWidth,
            height: element.videoHeight || fallbackHeight,
        };
    }

    function calculateMediaDrawRect(sourceWidth, sourceHeight, canvasWidth, canvasHeight, transform) {
        const fitMode = transform.fitMode || 'fit';
        let drawWidth = canvasWidth;
        let drawHeight = canvasHeight;

        if (fitMode === 'stretch') {
            drawWidth = canvasWidth;
            drawHeight = canvasHeight;
        } else if (fitMode === 'original') {
            drawWidth = sourceWidth;
            drawHeight = sourceHeight;
        } else {
            const scale = fitMode === 'fill'
                ? Math.max(canvasWidth / sourceWidth, canvasHeight / sourceHeight)
                : Math.min(canvasWidth / sourceWidth, canvasHeight / sourceHeight);

            drawWidth = sourceWidth * scale;
            drawHeight = sourceHeight * scale;
        }

        if (fitMode === 'custom') {
            const baseScale = Math.min(canvasWidth / sourceWidth, canvasHeight / sourceHeight);
            const customScale = (Number(transform.scale) || 100) / 100;
            drawWidth = sourceWidth * baseScale * customScale;
            drawHeight = sourceHeight * baseScale * customScale;
        } else if (fitMode !== 'stretch') {
            const customScale = (Number(transform.scale) || 100) / 100;
            drawWidth *= customScale;
            drawHeight *= customScale;
        }

        const offsetX = (Number(transform.x) || 0) / 100 * canvasWidth;
        const offsetY = (Number(transform.y) || 0) / 100 * canvasHeight;

        return {
            x: (canvasWidth - drawWidth) / 2 + offsetX,
            y: (canvasHeight - drawHeight) / 2 + offsetY,
            width: drawWidth,
            height: drawHeight,
        };
    }

    function drawMediaElement(context, element, canvasWidth, canvasHeight, transform, type) {
        const size = getMediaNaturalSize(element, type, canvasWidth, canvasHeight);
        const rect = calculateMediaDrawRect(size.width, size.height, canvasWidth, canvasHeight, transform || defaultMediaTransform());

        context.drawImage(element, rect.x, rect.y, rect.width, rect.height);
    }

    function drawImageContain(context, image, width, height) {
        const imageWidth = image.naturalWidth || width;
        const imageHeight = image.naturalHeight || height;
        const scale = Math.min(width / imageWidth, height / imageHeight);
        const drawWidth = imageWidth * scale;
        const drawHeight = imageHeight * scale;
        const x = (width - drawWidth) / 2;
        const y = (height - drawHeight) / 2;

        context.drawImage(image, x, y, drawWidth, drawHeight);
    }

    function drawVideoContain(context, video, width, height, cover) {
        const videoWidth = video.videoWidth || width;
        const videoHeight = video.videoHeight || height;
        const scale = cover ? Math.max(width / videoWidth, height / videoHeight) : Math.min(width / videoWidth, height / videoHeight);
        const drawWidth = videoWidth * scale;
        const drawHeight = videoHeight * scale;
        const x = (width - drawWidth) / 2;
        const y = (height - drawHeight) / 2;
        context.drawImage(video, x, y, drawWidth, drawHeight);
    }


    function isBroadcastLiveReady() {
        return Boolean(state.broadcastSession && state.broadcastReady && !state.broadcastStarting && !state.broadcastEnding);
    }

    function renderProgramLiveControls() {
        const liveReady = isBroadcastLiveReady();
        const disabled = !liveReady;

        if (els.toggleMic) {
            els.toggleMic.disabled = disabled;
            els.toggleMic.textContent = state.liveAudioMuted ? 'Unmute' : 'Mute';
            els.toggleMic.setAttribute('aria-pressed', state.liveAudioMuted ? 'true' : 'false');
        }
        if (els.toggleVideo) {
            els.toggleVideo.disabled = disabled;
            els.toggleVideo.textContent = state.liveVideoMuted ? 'Video On' : 'Video Off';
            els.toggleVideo.setAttribute('aria-pressed', state.liveVideoMuted ? 'true' : 'false');
        }
        if (els.programEndLive) {
            els.programEndLive.disabled = !state.broadcastSession || state.broadcastEnding;
        }
        if (els.programLiveStatus) {
            if (!state.broadcastSession) {
                els.programLiveStatus.textContent = 'Not live';
            } else if (!state.broadcastReady) {
                els.programLiveStatus.textContent = state.broadcastEnding ? 'Ending live…' : 'Connecting…';
            } else {
                const statuses = ['Live'];
                if (state.liveAudioMuted) { statuses.push('Mic muted'); }
                if (state.liveVideoMuted) { statuses.push('Video off'); }
                els.programLiveStatus.textContent = statuses.join(' · ');
            }
        }
        root.classList.toggle('is-program-video-muted', state.liveVideoMuted);
        renderOnAirTabProtection();
    }

    function resetProgramLiveControlState() {
        state.liveAudioMuted = false;
        if (state.audioMixer) {
            setMasterAudioMuted(false);
        }
        state.liveVideoMuted = false;
        renderProgramLiveControls();
    }

    async function toggleLiveAudio() {
        if (!isBroadcastLiveReady()) {
            setBroadcastStatus(getStudioString('liveMicControlsAfterConnect', 'Live microphone controls are available after the broadcast connects.'), 'warning');
            return;
        }
        if (!state.broadcastSession || typeof state.broadcastSession.muteAudio !== 'function') {
            setBroadcastStatus(getStudioString('liveAudioControlsUnavailable', 'Live audio controls are unavailable for this broadcast session.'), 'error');
            return;
        }
        const nextMuted = !state.liveAudioMuted;
        try {
            setMasterAudioMuted(nextMuted);
            const result = await state.broadcastSession.muteAudio(nextMuted);
            if (result === false || result === null) {
                throw new Error(getStudioString('liveMicrophoneTrackUnavailable', 'Live microphone track is unavailable.'));
            }
            state.liveAudioMuted = nextMuted;
            renderProgramLiveControls();
            setBroadcastStatus(nextMuted ? getStudioString('liveMicrophoneMuted', 'Live microphone muted.') : getStudioString('liveMicrophoneUnmuted', 'Live microphone unmuted.'), 'success');
        } catch (error) {
            renderProgramLiveControls();
            setBroadcastStatus((error && error.message) || getStudioString('liveMicrophoneUpdateFailed', 'Live microphone could not be updated. Check that the microphone track is available.'), 'error');
        }
    }

    async function toggleLiveVideo() {
        if (!isBroadcastLiveReady()) {
            setBroadcastStatus('Live video controls are available after the broadcast connects.', 'warning');
            return;
        }
        if (!state.broadcastSession || typeof state.broadcastSession.muteVideo !== 'function') {
            setBroadcastStatus('Live video controls are unavailable for this broadcast session.', 'error');
            return;
        }
        const nextMuted = !state.liveVideoMuted;
        try {
            const result = await state.broadcastSession.muteVideo(nextMuted);
            if (result === false || result === null) {
                throw new Error('Program video track is unavailable.');
            }
            state.liveVideoMuted = nextMuted;
            renderProgramLiveControls();
            setBroadcastStatus(nextMuted ? 'Program video is off for live viewers and recordings.' : 'Program video is back on.', 'success');
        } catch (error) {
            renderProgramLiveControls();
            setBroadcastStatus((error && error.message) || 'Program video could not be updated. Check that the Program video track is available.', 'error');
        }
    }

    function renderProgramState() {
        const active = hasProgramOutput();

        if (els.programCanvas) {
            els.programCanvas.classList.toggle('vh360-studio-program-canvas--screen', state.programSource === 'screen');
        }
        root.classList.toggle('is-program-active', active);
        root.classList.toggle('is-program-source-camera', isCameraSource(state.programSource));
        root.classList.toggle('is-program-source-screen', state.programSource === 'screen');
        root.classList.toggle('is-program-source-media', isMediaSource(state.programSource));
        if (els.programEmpty) {
            els.programEmpty.hidden = active;
        }
        updateProgramAudioRouting();
        updateOperatorStatus();
        dispatchProgramSourceChange();
    }

    function isSourceProtected(sourceId) {
        return state.programSource === sourceId && (state.broadcastSession || isRecordingActive());
    }

    function warnProtectedSource(sourceId) {
        setStatus('This source is currently in Program. Send another source to Program before stopping it.', 'warning');
        if (state.broadcastSession) {
            setBroadcastStatus('This source is currently in Program. Send another source to Program before stopping it.', 'warning');
        }
    }


    function createMixerChannel(context, destination, id, label) {
        const input = context.createGain();
        const gain = context.createGain();
        const analyser = context.createAnalyser();
        analyser.fftSize = 256;
        input.connect(gain);
        gain.connect(analyser);
        analyser.connect(destination);
        return { id, label, input, gain, analyser, source: null, stream: null, element: null, sourceId: '', muted: false, volume: 1, connected: false, unavailable: true };
    }

    function normalizeMixerVolume(value, fallback = 1) {
        const parsed = Number(value);
        if (!Number.isFinite(parsed)) { return fallback; }
        return Math.max(0, Math.min(1.5, parsed));
    }

    function hasLiveAudioTrack(stream) {
        return !!(stream && typeof stream.getAudioTracks === 'function' && stream.getAudioTracks().some((track) => track.readyState === 'live'));
    }

    function audioInputStatusLabel(status) {
        const labels = {
            off: getStudioString('audioStatusOff', 'Off'),
            connecting: getStudioString('audioStatusConnecting', 'Connecting'),
            active: getStudioString('audioStatusActive', 'Active'),
            muted: getStudioString('audioStatusMuted', 'Muted'),
            unavailable: getStudioString('audioStatusUnavailable', 'Unavailable'),
            disconnected: getStudioString('audioStatusDisconnected', 'Disconnected'),
            'permission-required': getStudioString('audioStatusPermissionRequired', 'Permission required'),
            error: getStudioString('audioStatusError', 'Error'),
            removed: getStudioString('audioStatusRemoved', 'Removed'),
        };
        return labels[status] || labels.off;
    }

    function generateAudioInputId(usedIds) {
        let id = '';
        do {
            state.audioInputCounter += 1;
            id = 'audio-input-' + state.audioInputCounter;
        } while (usedIds && usedIds.has(id));
        if (usedIds) { usedIds.add(id); }
        return id;
    }

    function audioInputNumericSuffix(id) {
        const match = /^audio-input-(\d+)$/.exec(String(id || ''));
        return match ? Number(match[1]) : 0;
    }

    function sanitizeAudioInputLabel(label, index) {
        const cleaned = String(label || '').trim().slice(0, 48);
        if (cleaned) { return cleaned; }
        return index === 0 ? getStudioString('primaryAudioInputLabel', 'Mic/Aux') : getStudioString('audioInputFallbackLabel', 'Audio Input') + ' ' + (index + 1);
    }

    function sanitizeAudioDeviceId(deviceId) {
        const value = String(deviceId || '').trim();
        return value.length > 256 ? '' : value;
    }

    function normalizeStoredAudioInputs(rawInputs) {
        const legacyMicId = sanitizeAudioDeviceId(storageGet(MIC_STORAGE_KEY));
        const source = Array.isArray(rawInputs) ? rawInputs : [];
        const usedIds = new Set();
        let maxSuffix = 0;
        let primaryClaimedIndex = -1;
        const validated = [];

        source.slice(0, MAX_AUDIO_INPUTS).forEach((raw) => {
            if (!raw || typeof raw !== 'object') { return; }
            let id = String(raw.id || '');
            if (!/^audio-input-\d+$/.test(id) || usedIds.has(id)) {
                id = generateAudioInputId(usedIds);
            } else {
                usedIds.add(id);
            }
            maxSuffix = Math.max(maxSuffix, audioInputNumericSuffix(id));
            const index = validated.length;
            if (raw.isPrimary === true && primaryClaimedIndex === -1) {
                primaryClaimedIndex = index;
            }
            validated.push({
                id,
                label: sanitizeAudioInputLabel(raw.label, index),
                deviceId: sanitizeAudioDeviceId(raw.deviceId),
                deviceLabel: String(raw.deviceLabel || '').trim().slice(0, 60),
                isPrimary: false,
                muted: raw.muted === true,
                volume: normalizeMixerVolume(raw.volume, 1),
                status: 'off',
            });
        });

        if (!validated.length) {
            const id = usedIds.has('audio-input-1') ? generateAudioInputId(usedIds) : 'audio-input-1';
            usedIds.add(id);
            validated.push({
                id,
                label: getStudioString('primaryAudioInputLabel', 'Mic/Aux'),
                deviceId: legacyMicId,
                deviceLabel: '',
                isPrimary: true,
                muted: false,
                volume: 1,
                status: 'off',
            });
            maxSuffix = Math.max(maxSuffix, audioInputNumericSuffix(id));
            primaryClaimedIndex = 0;
        }

        const primaryIndex = primaryClaimedIndex >= 0 ? primaryClaimedIndex : 0;
        validated.forEach((input, index) => { input.isPrimary = index === primaryIndex; });
        state.audioInputCounter = Math.max(state.audioInputCounter, maxSuffix);
        return validated;
    }

    function audioInputDefaults(input = {}) {
        const id = input.id || generateAudioInputId();
        state.audioInputCounter = Math.max(state.audioInputCounter, audioInputNumericSuffix(id));
        const status = ['off', 'connecting', 'active', 'muted', 'permission-required', 'unavailable', 'disconnected', 'error', 'removed'].includes(input.status) ? input.status : 'off';
        return {
            id,
            label: sanitizeAudioInputLabel(input.label, input.isPrimary ? 0 : state.audioInputs.size),
            deviceId: sanitizeAudioDeviceId(input.deviceId),
            deviceLabel: String(input.deviceLabel || '').trim().slice(0, 60),
            stream: null,
            mixerChannelId: id,
            isPrimary: !!input.isPrimary,
            connected: false,
            muted: !!input.muted,
            volume: normalizeMixerVolume(input.volume, 1),
            error: '',
            unavailable: status === 'unavailable',
            connecting: status === 'connecting',
            status,
            removed: false,
            startRequestId: 0,
        };
    }

    function primaryAudioInput() {
        return state.audioInputs.get(state.primaryAudioInputId) || Array.from(state.audioInputs.values()).find((input) => input.isPrimary) || state.audioInputs.values().next().value || null;
    }

    function enforcePrimaryAudioInputInvariant() {
        let primary = null;
        state.audioInputs.forEach((input) => {
            if (!primary && input.isPrimary) {
                primary = input;
            } else {
                input.isPrimary = false;
            }
        });
        if (!primary) {
            primary = state.audioInputs.values().next().value || createAudioInputSource({ id: 'audio-input-1', label: getStudioString('primaryAudioInputLabel', 'Mic/Aux'), isPrimary: true }, { skipPersist: true });
            primary.isPrimary = true;
        }
        state.primaryAudioInputId = primary.id;
        const primarySelect = els.micSelect;
        if (primarySelect && primarySelect.value !== primary.deviceId) { primarySelect.value = primary.deviceId; }
        storageSet(MIC_STORAGE_KEY, primary.deviceId || '');
        return primary;
    }

    function saveAudioInputConfiguration() {
        enforcePrimaryAudioInputInvariant();
        const config = Array.from(state.audioInputs.values()).slice(0, MAX_AUDIO_INPUTS).map((input) => ({
            id: input.id,
            label: input.label,
            deviceId: input.deviceId,
            deviceLabel: String(input.deviceLabel || '').trim().slice(0, 60),
            isPrimary: !!input.isPrimary,
            volume: normalizeMixerVolume(input.volume, 1),
            muted: input.muted === true,
        }));
        storageSet(AUDIO_INPUTS_STORAGE_KEY, JSON.stringify(config));
        const primary = primaryAudioInput();
        storageSet(MIC_STORAGE_KEY, primary ? primary.deviceId : '');
    }

    function scheduleAudioInputConfigurationSave() {
        if (state.audioInputSaveTimer) { window.clearTimeout(state.audioInputSaveTimer); }
        state.audioInputSaveTimer = window.setTimeout(() => {
            state.audioInputSaveTimer = null;
            saveAudioInputConfiguration();
        }, 250);
    }

    function flushAudioInputConfigurationSave() {
        if (state.audioInputSaveTimer) {
            window.clearTimeout(state.audioInputSaveTimer);
            state.audioInputSaveTimer = null;
            saveAudioInputConfiguration();
        }
    }

    function createAudioInputSource(config = {}, options = {}) {
        if (!options.restoring && state.audioInputs.size >= MAX_AUDIO_INPUTS) { return null; }
        const input = audioInputDefaults(config);
        if (input.isPrimary || !state.primaryAudioInputId) {
            state.audioInputs.forEach((existing) => { existing.isPrimary = false; });
            state.primaryAudioInputId = input.id;
            input.isPrimary = true;
        }
        state.audioInputs.set(input.id, input);
        enforcePrimaryAudioInputInvariant();
        if (state.audioMixer) { ensureAudioInputMixerChannel(input.id); }
        if (!options.skipPersist && !options.restoring) { saveAudioInputConfiguration(); }
        updateAddAudioInputAvailability();
        return input;
    }

    function ensureAudioInputMixerChannel(inputId) {
        const input = state.audioInputs.get(inputId);
        const mixer = ensureStudioAudioMixer();
        if (!input || !mixer) { return null; }
        let channel = mixer.channels[input.mixerChannelId];
        if (!channel) {
            channel = createMixerChannel(mixer.context, mixer.masterGain, input.mixerChannelId, input.label);
            mixer.channels[input.mixerChannelId] = channel;
        }
        channel.label = input.label;
        channel.volume = input.volume;
        channel.muted = input.muted;
        applyMixerChannelGain(channel);
        return channel;
    }

    function removeMixerChannel(channelId) {
        const mixer = state.audioMixer;
        const channel = mixer && mixer.channels[channelId];
        if (!channel) { return; }
        disconnectMixerChannel(channelId);
        ['input', 'gain', 'analyser'].forEach((key) => {
            if (channel[key] && typeof channel[key].disconnect === 'function') {
                try { channel[key].disconnect(); } catch (error) {}
            }
        });
        delete mixer.channels[channelId];
    }

    function ensureStudioAudioMixer() {
        if (state.audioMixer && state.audioMixer.context && state.audioMixer.destination) {
            return state.audioMixer;
        }
        const AudioContextClass = window.AudioContext || window.webkitAudioContext;
        if (!AudioContextClass || !window.MediaStream) {
            return null;
        }
        const context = new AudioContextClass();
        const destination = context.createMediaStreamDestination();
        const masterGain = context.createGain();
        const masterAnalyser = context.createAnalyser();
        masterAnalyser.fftSize = 256;
        masterGain.connect(masterAnalyser);
        masterAnalyser.connect(destination);
        const mixer = {
            id: 'studio-mix-' + Date.now(),
            context,
            destination,
            masterGain,
            masterAnalyser,
            meterFrame: null,
            channels: {},
        };
        ['screen', 'media'].forEach((id) => {
            mixer.channels[id] = createMixerChannel(context, masterGain, id, id === 'screen' ? getStudioString('screenShareLabel', 'Screen Share') : getStudioString('mediaAssetLabel', 'Media/Asset'));
        });
        state.audioMixer = mixer;
        state.audioInputs.forEach((input) => ensureAudioInputMixerChannel(input.id));
        startMixerMeters();
        updateMixerUi();
        return mixer;
    }

    function disconnectMixerChannel(channelId, expectedSourceId) {
        const mixer = state.audioMixer;
        const channel = mixer && mixer.channels[channelId];
        if (!channel || expectedSourceId && channel.sourceId !== expectedSourceId) { return; }
        if (channel.element && channelId === 'media') {
            channel.element.muted = true;
        }
        if (channel.source && typeof channel.source.disconnect === 'function') {
            try { channel.source.disconnect(); } catch (error) {}
        }
        channel.source = null;
        channel.stream = null;
        channel.element = null;
        channel.sourceId = '';
        channel.connected = false;
        channel.unavailable = true;
        updateMixerUi();
    }

    function setMixerChannelStream(channelId, stream, options = {}) {
        const mixer = ensureStudioAudioMixer();
        if (!mixer || !mixer.channels[channelId]) { return; }
        const channel = mixer.channels[channelId];
        const sourceId = options.sourceId || channelId;
        if (channel.stream === stream && channel.sourceId === sourceId && channel.source) { return; }
        disconnectMixerChannel(channelId);
        channel.stream = stream || null;
        channel.sourceId = sourceId;
        const audioTracks = stream && stream.getAudioTracks ? stream.getAudioTracks().filter((track) => track.readyState !== 'ended') : [];
        channel.connected = !!audioTracks.length;
        channel.unavailable = !channel.connected;
        if (audioTracks.length) {
            channel.source = mixer.context.createMediaStreamSource(new MediaStream(audioTracks));
            channel.source.connect(channel.input);
            audioTracks.forEach((track) => track.addEventListener('ended', () => {
                const currentChannel = state.audioMixer && state.audioMixer.channels[channelId];
                if (!currentChannel || currentChannel.stream !== stream || currentChannel.sourceId !== sourceId) { return; }
                disconnectMixerChannel(channelId, sourceId);
            }, { once: true }));
        }
        applyMixerChannelGain(channel);
        updateMixerUi();
    }

    function recreateMediaElementForMixer(sourceId, staleElement) {
        const mediaSource = sourceId ? getMediaSource(sourceId) : null;
        if (!mediaSource || mediaSource.type !== 'video' || !staleElement) { return staleElement; }

        const wasPaused = staleElement.paused;
        const currentTime = Number.isFinite(staleElement.currentTime) ? staleElement.currentTime : 0;
        const parent = staleElement.parentNode;
        const replacement = createMediaElement(mediaSource);
        replacement.loop = staleElement.loop;
        replacement.muted = true;
        mediaSource.element = replacement;

        if (parent) {
            parent.replaceChild(replacement, staleElement);
        }

        staleElement.pause();
        staleElement.removeAttribute('src');
        if (typeof staleElement.load === 'function') {
            staleElement.load();
        }

        replacement.addEventListener('loadedmetadata', () => {
            if (currentTime > 0 && Number.isFinite(replacement.duration) && currentTime < replacement.duration) {
                replacement.currentTime = currentTime;
            }
            if (!wasPaused) {
                replacement.play().catch(() => {});
            }
        }, { once: true });

        return replacement;
    }

    function mediaElementMixerSource(element, mixer, sourceId) {
        if (!element) { return null; }
        if (element._vh360MixerSource && element._vh360MixerContextId === mixer.id) {
            return element._vh360MixerSource;
        }

        if (element._vh360MixerSource && element._vh360MixerContextId !== mixer.id) {
            element = recreateMediaElementForMixer(sourceId, element);
        }

        if (element._vh360MixerSource && element._vh360MixerContextId === mixer.id) {
            return element._vh360MixerSource;
        }

        element._vh360MixerSource = mixer.context.createMediaElementSource(element);
        element._vh360MixerContextId = mixer.id;
        return element._vh360MixerSource;
    }

    function setMixerChannelElement(channelId, element, sourceId = '') {
        const mixer = ensureStudioAudioMixer();
        if (!mixer || !mixer.channels[channelId] || !element || element.tagName !== 'VIDEO') { return; }
        const channel = mixer.channels[channelId];
        if (element._vh360MixerSource && element._vh360MixerContextId !== mixer.id) {
            element = recreateMediaElementForMixer(sourceId, element);
        }
        if (!element || element.tagName !== 'VIDEO') { return; }
        if (channel.element === element && channel.sourceId === sourceId && channel.source) { return; }
        disconnectMixerChannel(channelId);
        channel.element = element;
        channel.sourceId = sourceId;
        channel.source = mediaElementMixerSource(element, mixer, sourceId);
        if (!channel.source) { return; }
        channel.source.connect(channel.input);
        element.muted = false;
        channel.connected = true;
        channel.unavailable = false;
        applyMixerChannelGain(channel);
        updateMixerUi();
    }

    function updateProgramAudioRouting() {
        if (state.programSource === 'screen') {
            setMixerChannelStream('screen', state.screenStream, { sourceId: 'screen' });
        } else {
            disconnectMixerChannel('screen');
        }

        if (isMediaSource(state.programSource)) {
            const mediaSource = getMediaSource(state.programSource);
            if (mediaSource && mediaSource.type === 'video' && mediaSource.element) {
                setMixerChannelElement('media', mediaSource.element, mediaSource.sourceId);
            } else {
                disconnectMixerChannel('media');
            }
        } else {
            disconnectMixerChannel('media');
        }
    }

    function applyMixerChannelGain(channel) {
        if (!channel || !channel.gain) { return; }
        channel.gain.gain.value = channel.muted ? 0 : normalizeMixerVolume(channel.volume, 1);
    }

    function setMasterAudioMuted(muted) {
        const mixer = ensureStudioAudioMixer();
        if (!mixer) { return; }
        state.liveAudioMuted = !!muted;
        mixer.masterGain.gain.value = state.liveAudioMuted ? 0 : 1;
        updateMixerUi();
    }

    function getStudioMixedAudioTrack() {
        const mixer = ensureStudioAudioMixer();
        if (!mixer) { return null; }
        if (mixer.context && mixer.context.state === 'suspended' && typeof mixer.context.resume === 'function') {
            mixer.context.resume().catch(() => {});
        }
        return mixer.destination.stream.getAudioTracks().find((track) => track.readyState !== 'ended') || null;
    }

    function getStudioMixedAudioStream() {
        const track = getStudioMixedAudioTrack();
        return track ? new MediaStream([track]) : null;
    }

    function syncMixerMuteButton(button, channel, mutedOverride) {
        if (!button) { return; }
        const muted = typeof mutedOverride === 'boolean' ? mutedOverride : !!(channel && channel.muted);
        const label = channel && channel.label ? channel.label : button.dataset.mixerMute || 'audio';
        const action = muted ? getStudioString('unmuteAudioInputAction', 'Unmute') + ' ' : getStudioString('muteAudioInputAction', 'Mute') + ' ';
        button.setAttribute('aria-pressed', muted ? 'true' : 'false');
        button.setAttribute('aria-label', action + label);
        const srText = button.querySelector('.screen-reader-text');
        if (srText) { srText.textContent = action + label; }
    }

    function audioInputDeviceInfo(input) {
        if (!input || !input.deviceId) { return null; }
        return (state.availableAudioInputDevices || []).find((device) => device.deviceId === input.deviceId) || null;
    }

    function getDuplicateAudioDeviceAssignments() {
        const groups = new Map();
        state.audioInputs.forEach((input) => {
            if (!input.deviceId) { return; }
            const device = audioInputDeviceInfo(input);
            const keys = ['id:' + input.deviceId];
            if (device && device.groupId) { keys.push('group:' + device.groupId); }
            keys.forEach((key) => {
                if (!groups.has(key)) { groups.set(key, []); }
                groups.get(key).push(input.id);
            });
        });
        const duplicates = new Map();
        groups.forEach((ids, key) => {
            const uniqueIds = Array.from(new Set(ids));
            if (uniqueIds.length > 1) { duplicates.set(key, uniqueIds); }
        });
        return duplicates;
    }

    function duplicateIdsForInput(input) {
        if (!input || !input.deviceId) { return []; }
        const device = audioInputDeviceInfo(input);
        const duplicates = getDuplicateAudioDeviceAssignments();
        const ids = new Set(duplicates.get('id:' + input.deviceId) || []);
        if (device && device.groupId) {
            (duplicates.get('group:' + device.groupId) || []).forEach((id) => ids.add(id));
        }
        return Array.from(ids);
    }

    function duplicateWarningForInput(input) {
        if (!input || !input.deviceId) { return ''; }
        const duplicateIds = duplicateIdsForInput(input);
        const otherNames = duplicateIds.filter((id) => id !== input.id).map((id) => {
            const other = state.audioInputs.get(id);
            return other ? other.label : '';
        }).filter(Boolean);
        if (!otherNames.length) { return ''; }
        return getStudioString('deviceAlsoSelectedPrefix', 'Also selected on') + ' ' + otherNames.join(', ') + '.';
    }

    function audioInputSummaryFromState() {
        const results = Array.from(state.audioInputs.values()).map((input) => {
            const status = audioInputStatus(input);
            const normalizedStatus = status === 'muted' ? 'active' : status;
            return {
                id: input.id,
                status: normalizedStatus,
                error: input.error || null,
            };
        });
        const active = results.filter((result) => result.status === 'active').length;
        const failed = results.filter((result) => ['failed', 'unavailable', 'permission-required', 'disconnected', 'error', 'stale'].includes(result.status)).length;
        const skipped = results.filter((result) => ['removed', 'off'].includes(result.status)).length;
        return { active, failed, skipped, total: results.length, results };
    }

    function microphoneWarningFromSummary(summary, context) {
        if (!summary || !summary.total) { return ''; }
        if (summary.active === 0) {
            return getStudioString('noMicrophoneInputsActive', 'No microphone inputs are active. Studio will continue without microphone audio.');
        }
        if (summary.failed > 0) {
            return formatAudioInputSummary(summary, context || 'recording');
        }
        return '';
    }

    function updateRecordingAudioInputWarnings() {
        if (!state.recordingStartedAt && !state.recordingStoppedAt) { return; }
        const audioWarning = microphoneWarningFromSummary(audioInputSummaryFromState(), 'recording');
        const webmWarning = state.selectedMimeType && isWebmMime(state.selectedMimeType) ? webmFallbackWarning() : '';
        state.recordingPersistentWarnings = [audioWarning, webmWarning].filter(Boolean);
        if (els.recordingStatus && els.recordingStatus.dataset.statusType === 'error') { return; }
        if (isRecordingActive() || state.recordingStoppedAt && !state.serverStopConfirmed) {
            updateRecordingOperationStatus(state.recordingStoppedAt ? strings.uploadingChunk : strings.recordingActive, 'info');
        }
    }

    function updateLiveAudioInputWarning() {
        if (!state.broadcastSession) { return; }
        if (state.lastAgoraConnectionState && state.lastAgoraConnectionState !== 'CONNECTED') { return; }
        if (els.broadcastStatus && els.broadcastStatus.dataset.statusType === 'error') { return; }
        const warning = microphoneWarningFromSummary(audioInputSummaryFromState(), 'live');
        if (warning) {
            state.liveAudioWarningActive = true;
            setBroadcastStatus(warning, 'warning');
            return;
        }
        if (state.liveAudioWarningActive) {
            state.liveAudioWarningActive = false;
            setBroadcastStatus(strings.liveStarted, 'success');
        }
    }

    function updateAddAudioInputAvailability() {
        if (!els.addAudioInput) { return; }
        const atLimit = state.audioInputs.size >= MAX_AUDIO_INPUTS;
        els.addAudioInput.disabled = atLimit;
        els.addAudioInput.setAttribute('aria-disabled', atLimit ? 'true' : 'false');
        els.addAudioInput.title = atLimit ? getStudioString('audioInputLimitReached', 'Studio supports up to 8 audio inputs in this phase.') : '';
    }

    function audioInputStatus(input) {
        if (!input) { return 'off'; }
        if (input.removed) { return 'removed'; }
        if (input.connecting || input.status === 'connecting') { return 'connecting'; }
        if (hasLiveAudioTrack(input.stream)) { return input.muted ? 'muted' : 'active'; }
        if (['permission-required', 'unavailable', 'disconnected', 'error', 'off'].includes(input.status)) { return input.status; }
        if (input.unavailable) { return 'unavailable'; }
        return 'off';
    }

    function setAudioInputStatus(input, status, errorMessage) {
        if (!input) { return; }
        input.status = status || 'off';
        input.connected = input.status === 'active' || input.status === 'muted';
        input.connecting = input.status === 'connecting';
        input.unavailable = input.status === 'unavailable';
        input.error = errorMessage || '';
        updateMixerUi();
    }

    function updateAudioInputAccessibility(inputId) {
        const input = state.audioInputs.get(inputId);
        const strip = els.audioInputChannels && els.audioInputChannels.querySelector('[data-audio-input-id="' + inputId + '"]');
        if (!input || !strip) { return; }
        const label = input.label || getStudioString('audioInputFallbackLabel', 'Audio Input');
        const status = audioInputStatusLabel(audioInputStatus(input));
        const name = strip.querySelector('[data-audio-input-name]');
        if (name) { name.setAttribute('aria-label', label + ' ' + getStudioString('audioInputNameLabel', 'name')); }
        const select = strip.querySelector('[data-audio-input-device]');
        if (select) { select.setAttribute('aria-label', label + ' ' + getStudioString('audioInputDeviceLabel', 'device')); }
        const gain = strip.querySelector('[data-mixer-gain]');
        if (gain) { gain.setAttribute('aria-label', label + ' ' + getStudioString('audioInputGainLabel', 'gain')); }
        const meter = strip.querySelector('.vh360-studio-meter');
        if (meter) { meter.setAttribute('aria-label', label + ' ' + getStudioString('audioInputLevelLabel', 'level')); }
        const mute = strip.querySelector('[data-mixer-mute]');
        if (mute) { syncMixerMuteButton(mute, { label, muted: !!input.muted }); }
        const remove = strip.querySelector('[data-remove-audio-input]');
        if (remove) { remove.setAttribute('aria-label', getStudioString('removeAudioInputLabel', 'Remove') + ' ' + label); }
        const statusEl = strip.querySelector('[data-mixer-status]');
        if (statusEl) { statusEl.setAttribute('aria-label', label + ' ' + getStudioString('audioInputStatusLabel', 'status') + ': ' + status); }
    }

    function focusOrHighlightMixerChannel(inputId) {
        const strip = els.audioInputChannels && els.audioInputChannels.querySelector('[data-audio-input-id="' + inputId + '"]');
        if (!strip) { return; }
        strip.classList.add('is-test-highlighted');
        const target = strip.querySelector('[data-audio-input-device], [data-audio-input-name], [data-mixer-gain]');
        if (target && typeof target.focus === 'function') { target.focus({ preventScroll: false }); }
        window.setTimeout(() => { strip.classList.remove('is-test-highlighted'); }, 2500);
    }

    function formatAudioInputSummary(summary, context) {
        if (!summary) { return ''; }
        if (summary.active > 0 && summary.failed > 0) {
            return (context === 'live' ? getStudioString('livePartialAudioInputs', 'Live will start with {active} audio input(s). {failed} configured input(s) are unavailable.') : getStudioString('recordingPartialAudioInputs', 'Recording will start with {active} audio input(s). {failed} configured input(s) are unavailable.')).replace('{active}', summary.active).replace('{failed}', summary.failed);
        }
        if (summary.active === 0) {
            return getStudioString('noMicrophoneInputsActive', 'No microphone inputs are active. Studio will continue without microphone audio.');
        }
        return getStudioString('audioInputsActiveSummary', '{active} audio input(s) active.').replace('{active}', summary.active);
    }

    function nextAudioInputDisplayName(input) {
        const base = getStudioString('audioInputFallbackLabel', 'Audio Input');
        const used = new Set(Array.from(state.audioInputs.values()).filter((item) => !input || item.id !== input.id).map((item) => item.label));
        const preferredNumber = Math.max(2, audioInputNumericSuffix(input && input.id));
        let number = preferredNumber;
        while (used.has(base + ' ' + number)) {
            number += 1;
        }
        return base + ' ' + number;
    }

    function populateAudioInputDeviceSelect(select, input) {
        if (!select || !input) { return; }
        const current = input.deviceId || '';
        select.innerHTML = '';
        const defaultOption = document.createElement('option');
        defaultOption.value = '';
        defaultOption.textContent = input.isPrimary ? getStudioString('defaultMicrophone', 'Default microphone') : getStudioString('chooseAudioDevice', 'Choose audio device');
        select.appendChild(defaultOption);
        state.availableAudioInputDevices.forEach((device, index) => {
            const option = document.createElement('option');
            option.value = device.deviceId;
            option.dataset.deviceLabel = device.label || '';
            option.textContent = device.label || getStudioString('microphoneFallbackLabel', 'Microphone') + ' ' + (index + 1);
            if (device.deviceId === current) { option.selected = true; }
            select.appendChild(option);
        });
        if (current && !state.availableAudioInputDevices.some((device) => device.deviceId === current)) {
            const missing = document.createElement('option');
            missing.value = current;
            missing.dataset.deviceLabel = input.deviceLabel || '';
            const live = hasLiveAudioTrack(input.stream);
            const stateLabel = live ? audioInputStatusLabel('active') : audioInputStatusLabel('unavailable');
            missing.textContent = getStudioString('deviceStateOption', '{device} ({status})').replace('{device}', input.deviceLabel || getStudioString('savedMicrophoneLabel', 'Saved microphone')).replace('{status}', stateLabel);
            missing.selected = true;
            select.appendChild(missing);
        }
        select.disabled = !state.availableAudioInputDevices.length && !current;
    }

    function createAudioInputStrip(input) {
        const strip = document.createElement('div');
        strip.className = 'vh360-studio-audio-channel vh360-studio-audio-channel--input';
        strip.dataset.audioInputId = input.id;
        strip.dataset.mixerChannel = input.id;

        const status = document.createElement('span');
        status.className = 'vh360-studio-audio-status';
        status.dataset.mixerStatus = input.id;
        status.textContent = audioInputStatusLabel('off');
        strip.appendChild(status);

        const name = document.createElement('input');
        name.className = 'vh360-studio-audio-name-field';
        name.type = 'text';
        name.value = input.label;
        name.maxLength = 48;
        name.dataset.audioInputName = input.id;
        name.setAttribute('aria-label', input.label + ' ' + getStudioString('audioInputNameLabel', 'name'));
        strip.appendChild(name);

        if (input.isPrimary) {
            const badge = document.createElement('span');
            badge.className = 'vh360-studio-audio-primary';
            badge.textContent = getStudioString('primaryAudioInputBadge', 'Primary');
            strip.appendChild(badge);
        }

        const select = document.createElement('select');
        select.className = 'vh360-studio-audio-device-select';
        select.dataset.audioInputDevice = input.id;
        select.setAttribute('aria-label', input.label + ' ' + getStudioString('audioInputDeviceLabel', 'device'));
        populateAudioInputDeviceSelect(select, input);
        strip.appendChild(select);
        const warning = document.createElement('p');
        warning.className = 'vh360-studio-audio-warning';
        warning.dataset.audioInputWarning = input.id;
        warning.setAttribute('aria-live', 'polite');
        warning.hidden = true;
        strip.appendChild(warning);

        const body = document.createElement('div');
        body.className = 'vh360-studio-audio-strip-body';
        const gainLabel = document.createElement('label');
        gainLabel.className = 'vh360-studio-audio-gain';
        const gainSr = document.createElement('span');
        gainSr.className = 'screen-reader-text';
        gainSr.textContent = input.label + ' ' + getStudioString('audioInputGainLabel', 'gain');
        const gain = document.createElement('input');
        gain.type = 'range';
        gain.min = '0';
        gain.max = '150';
        gain.value = String(Math.round(input.volume * 100));
        gain.dataset.mixerGain = input.id;
        gainLabel.appendChild(gainSr);
        gainLabel.appendChild(gain);
        const meter = document.createElement('div');
        meter.className = 'vh360-studio-meter';
        meter.setAttribute('aria-label', input.label + ' ' + getStudioString('audioInputLevelLabel', 'level'));
        const meterFill = document.createElement('span');
        meterFill.dataset.mixerMeter = input.id;
        if (input.isPrimary) { meterFill.dataset.micMeter = ''; }
        meter.appendChild(meterFill);
        body.appendChild(gainLabel);
        body.appendChild(meter);
        strip.appendChild(body);

        const actions = document.createElement('div');
        actions.className = 'vh360-studio-audio-actions';
        const mute = document.createElement('button');
        mute.type = 'button';
        mute.className = 'vh360-studio-audio-mute';
        mute.dataset.mixerMute = input.id;
        mute.setAttribute('aria-pressed', input.muted ? 'true' : 'false');
        mute.innerHTML = '<span class="screen-reader-text"></span>';
        actions.appendChild(mute);
        if (!input.isPrimary) {
            const remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'vh360-studio-audio-remove';
            remove.dataset.removeAudioInput = input.id;
            remove.textContent = getStudioString('removeAudioInput', 'Remove');
            actions.appendChild(remove);
        }
        strip.appendChild(actions);
        return strip;
    }

    function renderAudioInputChannels() {
        if (!els.audioInputChannels) { return; }
        const existing = new Map(Array.from(els.audioInputChannels.querySelectorAll('[data-audio-input-id]')).map((el) => [el.dataset.audioInputId, el]));
        state.audioInputs.forEach((input) => {
            let strip = existing.get(input.id);
            if (!strip) {
                strip = createAudioInputStrip(input);
                els.audioInputChannels.appendChild(strip);
            }
            existing.delete(input.id);
            const status = audioInputStatus(input);
            const duplicateWarning = duplicateWarningForInput(input);
            const warningMessage = [input.error, duplicateWarning].filter(Boolean).join(' ');
            strip.classList.toggle('is-primary', !!input.isPrimary);
            strip.classList.toggle('is-unavailable', status === 'unavailable' || status === 'error' || status === 'permission-required');
            strip.classList.toggle('has-duplicate-device', !!duplicateWarning);
            strip.dataset.audioInputStatus = status;
            const statusEl = strip.querySelector('[data-mixer-status]');
            if (statusEl) {
                statusEl.textContent = audioInputStatusLabel(status);
                statusEl.dataset.statusState = status;
            }
            const name = strip.querySelector('[data-audio-input-name]');
            if (name && document.activeElement !== name) { name.value = input.label; }
            populateAudioInputDeviceSelect(strip.querySelector('[data-audio-input-device]'), input);
            const warning = strip.querySelector('[data-audio-input-warning]');
            if (warning) {
                warning.textContent = warningMessage;
                warning.hidden = !warningMessage;
            }
            updateAudioInputAccessibility(input.id);
        });
        existing.forEach((el) => el.remove());
        updateAddAudioInputAvailability();
    }

    function updateMixerUi() {
        const mixer = state.audioMixer;
        if (mixer) {
            Object.keys(mixer.channels).forEach((id) => {
                const channel = mixer.channels[id];
                const input = state.audioInputs.get(id);
                let statusKey = channel.muted ? 'muted' : channel.connected ? 'active' : 'off';
                if (input) {
                    statusKey = channel.muted && hasLiveAudioTrack(input.stream) ? 'muted' : audioInputStatus(input);
                }
                root.querySelectorAll('[data-mixer-status="' + id + '"]').forEach((el) => {
                    el.textContent = audioInputStatusLabel(statusKey);
                    el.dataset.statusState = statusKey;
                });
                root.querySelectorAll('[data-mixer-mute="' + id + '"]').forEach((button) => { syncMixerMuteButton(button, channel); });
            });
            root.querySelectorAll('[data-mixer-status="master"]').forEach((el) => { el.textContent = state.liveAudioMuted ? audioInputStatusLabel('muted') : audioInputStatusLabel('active'); });
        }
        renderAudioInputChannels();
    }

    function startMixerMeters() {
        const mixer = state.audioMixer;
        if (!mixer || mixer.meterFrame) { return; }
        const draw = () => {
            Object.keys(mixer.channels).forEach((id) => {
                const channel = mixer.channels[id];
                const data = channel.meterData || (channel.meterData = new Uint8Array(channel.analyser.frequencyBinCount));
                channel.analyser.getByteFrequencyData(data);
                const average = data.reduce((sum, value) => sum + value, 0) / (data.length || 1);
                const level = Math.min(100, Math.round((average / 255) * 100)) + '%';
                root.querySelectorAll('[data-mixer-meter="' + id + '"]').forEach((el) => {
                    el.style.setProperty('--vh360-meter-level', level);
                    el.style.height = level;
                });
            });
            const masterData = mixer.masterMeterData || (mixer.masterMeterData = new Uint8Array(mixer.masterAnalyser.frequencyBinCount));
            mixer.masterAnalyser.getByteFrequencyData(masterData);
            const masterAverage = masterData.reduce((sum, value) => sum + value, 0) / (masterData.length || 1);
            const masterLevel = Math.min(100, Math.round((masterAverage / 255) * 100)) + '%';
            root.querySelectorAll('[data-mixer-meter="master"]').forEach((el) => {
                el.style.setProperty('--vh360-meter-level', masterLevel);
                el.style.height = masterLevel;
            });
            mixer.meterFrame = window.requestAnimationFrame(draw);
        };
        draw();
    }


    function teardownStudioAudioMixer() {
        const mixer = state.audioMixer;
        if (!mixer) { return; }

        if (mixer.meterFrame) {
            window.cancelAnimationFrame(mixer.meterFrame);
            mixer.meterFrame = null;
        }

        Object.keys(mixer.channels || {}).forEach((id) => {
            const channel = mixer.channels[id];
            if (!channel) { return; }
            if (channel.element && id === 'media') {
                channel.element.muted = true;
            }
            ['source', 'input', 'gain', 'analyser'].forEach((nodeKey) => {
                const node = channel[nodeKey];
                if (node && typeof node.disconnect === 'function') {
                    try { node.disconnect(); } catch (error) {}
                }
            });
            channel.source = null;
            channel.stream = null;
            channel.element = null;
            channel.sourceId = '';
            channel.connected = false;
            channel.unavailable = true;
        });

        state.mediaSources.forEach((source) => {
            if (source && source.element && source.element._vh360MixerContextId === mixer.id) {
                const replacement = recreateMediaElementForMixer(source.sourceId, source.element);
                if (replacement) {
                    replacement.muted = true;
                    delete replacement._vh360MixerSource;
                    delete replacement._vh360MixerContextId;
                }
            }
        });

        ['masterGain', 'masterAnalyser'].forEach((nodeKey) => {
            const node = mixer[nodeKey];
            if (node && typeof node.disconnect === 'function') {
                try { node.disconnect(); } catch (error) {}
            }
        });

        if (mixer.context && typeof mixer.context.close === 'function' && mixer.context.state !== 'closed') {
            mixer.context.close().catch(() => {});
        }

        state.audioMixer = null;
        root.querySelectorAll('[data-mixer-meter]').forEach((el) => {
            el.style.setProperty('--vh360-meter-level', '0%');
            el.style.height = '0%';
        });
        root.querySelectorAll('[data-mixer-status]').forEach((el) => { el.textContent = el.dataset.mixerStatus === 'master' ? audioInputStatusLabel('active') : audioInputStatusLabel('off'); });
        root.querySelectorAll('[data-mixer-mute]').forEach((button) => { syncMixerMuteButton(button, null, false); });
    }

    function stopAudioInput(inputId) {
        const input = state.audioInputs.get(inputId);
        if (!input) { return; }
        input.startRequestId++;
        stopStream(input.stream);
        input.stream = null;
        input.connected = false;
        input.connecting = false;
        input.status = input.removed ? 'removed' : 'off';
        disconnectMixerChannel(input.mixerChannelId);
        refreshAudioInputDiagnostics();
    }

    async function startAudioInput(inputId, options = {}) {
        const input = state.audioInputs.get(inputId);
        if (!input) { return null; }
        if (input.removed || state.studioTearingDown) { return null; }
        if (!input.isPrimary && !input.deviceId) {
            stopAudioInput(inputId);
            const current = state.audioInputs.get(inputId);
            if (current) {
                current.error = '';
                current.unavailable = false;
                current.status = 'off';
            }
            refreshAudioInputDiagnostics();
            return null;
        }
        if (!state.support.getUserMedia) {
            input.error = getStudioString('microphoneCaptureUnavailable', 'Microphone capture is unavailable in this browser.');
            input.status = 'error';
            disconnectMixerChannel(input.mixerChannelId);
            return null;
        }
        if (input.stream && !options.force) {
            const micAudio = input.stream.getAudioTracks().find((track) => track.readyState !== 'ended');
            if (micAudio) {
                setMixerChannelStream(input.mixerChannelId, input.stream, { sourceId: input.id });
                return input.stream;
            }
        }
        stopStream(input.stream);
        input.stream = null;
        input.connected = false;
        input.connecting = true;
        input.status = 'connecting';
        input.error = '';
        const requestId = ++input.startRequestId;
        const requestedDeviceId = input.deviceId;
        ensureAudioInputMixerChannel(input.id);
        updateMixerUi();
        try {
            const stream = await navigator.mediaDevices.getUserMedia({
                audio: input.deviceId ? { deviceId: { exact: input.deviceId } } : true,
                video: false,
            });
            const latest = state.audioInputs.get(inputId);
            if (!latest || latest.removed || state.studioTearingDown || latest.startRequestId !== requestId || latest.deviceId !== requestedDeviceId) {
                stopStream(stream);
                if (latest && latest.startRequestId === requestId && latest.connecting) {
                    latest.connecting = false;
                    latest.status = 'off';
                    refreshAudioInputDiagnostics();
                }
                return null;
            }
            latest.stream = stream;
            latest.connected = true;
            latest.connecting = false;
            latest.status = latest.muted ? 'muted' : 'active';
            latest.error = '';
            latest.unavailable = false;
            const track = stream.getAudioTracks()[0];
            if (track) {
                const settings = typeof track.getSettings === 'function' ? track.getSettings() : {};
                if (!latest.deviceId && settings.deviceId) {
                    latest.deviceId = sanitizeAudioDeviceId(settings.deviceId);
                    if (latest.isPrimary) { storageSet(MIC_STORAGE_KEY, latest.deviceId); }
                }
                const matchedDevice = latest.deviceId ? state.availableAudioInputDevices.find((device) => device.deviceId === latest.deviceId) : null;
                latest.deviceLabel = matchedDevice && matchedDevice.label ? matchedDevice.label : track.label || latest.deviceLabel;
                track.addEventListener('ended', () => {
                    const current = state.audioInputs.get(inputId);
                    if (!current || current.stream !== stream) { return; }
                    current.connected = false;
                    current.stream = null;
                    current.unavailable = true;
                    current.status = 'disconnected';
                    current.error = getStudioString('audioInputDisconnectedDetail', 'This audio device disconnected. Reconnect it or choose another device.');
                    disconnectMixerChannel(current.mixerChannelId, current.id);
                    refreshAudioInputDiagnostics();
                    setDeviceStatus(getStudioString('audioInputDisconnected', 'Audio input disconnected.') + ' ' + current.label, 'warning');
                }, { once: true });
            }
            setMixerChannelStream(latest.mixerChannelId, stream, { sourceId: latest.id });
            saveAudioInputConfiguration();
            await populateDevices({ reason: options.reason || 'microphone-start' }).catch(() => {});
            refreshAudioInputDiagnostics();
            return stream;
        } catch (error) {
            const latest = state.audioInputs.get(inputId);
            if (!latest || latest.removed || state.studioTearingDown || latest.startRequestId !== requestId) { return null; }
            latest.connected = false;
            latest.connecting = false;
            latest.error = friendlyMediaError(error);
            latest.unavailable = true;
            latest.status = error && (error.name === 'NotAllowedError' || error.name === 'PermissionDeniedError') ? 'permission-required' : 'error';
            disconnectMixerChannel(latest.mixerChannelId);
            if (!options.retriedDefaultMicrophone && latest.deviceId && ['NotFoundError', 'DevicesNotFoundError', 'OverconstrainedError', 'ConstraintNotSatisfiedError'].includes(error && error.name)) {
                const failedMicId = latest.deviceId;
                if (!latest.isPrimary) {
                    latest.status = 'unavailable';
                    latest.error = getStudioString('selectedAudioDeviceUnavailable', 'Selected audio device is unavailable. Choose another device for this input.');
                    saveAudioInputConfiguration();
                    refreshAudioInputDiagnostics();
                    setDeviceStatus(latest.label + ': ' + latest.error, 'warning');
                    return null;
                }
                latest.deviceId = '';
                storageSet(MIC_STORAGE_KEY, '');
                setDeviceStatus(getStudioString('primaryMicrophoneFallback', 'The selected microphone is no longer available. Studio will retry with the default microphone…'), 'warning');
                await populateDevices({ reason: 'microphone-retry', keepDefaultMicrophone: true }).catch(() => {});
                studioDebugLog('[VH360 Studio] Retrying microphone input with default device after selected microphone failed', { failedMicId, errorName: error && error.name });
                return startAudioInput(inputId, Object.assign({}, options, { retriedDefaultMicrophone: true }));
            }
            refreshAudioInputDiagnostics();
            throw error;
        }
    }

    async function ensureAudioInputStreams() {
        if (!state.audioInputs.size) { createAudioInputSource({ id: 'audio-input-1', label: getStudioString('primaryAudioInputLabel', 'Mic/Aux'), isPrimary: true }); }
        const results = await Promise.all(Array.from(state.audioInputs.keys()).map(async (id) => {
            const input = state.audioInputs.get(id);
            if (!input || input.removed) { return { id, status: 'removed', error: null }; }
            if (!input.isPrimary && !input.deviceId) {
                stopAudioInput(id);
                return { id, status: 'off', error: null };
            }
            if (hasLiveAudioTrack(input.stream)) { return { id, status: 'active', error: null }; }
            try {
                const stream = await startAudioInput(id);
                const latest = state.audioInputs.get(id);
                if (hasLiveAudioTrack(stream) || hasLiveAudioTrack(latest && latest.stream)) {
                    return { id, status: 'active', error: null };
                }
                return { id, status: latest && latest.status === 'permission-required' ? 'permission-required' : latest && latest.status === 'unavailable' ? 'unavailable' : 'failed', error: latest && latest.error ? latest.error : null };
            } catch (error) {
                const latest = state.audioInputs.get(id);
                return { id, status: latest && latest.status === 'permission-required' ? 'permission-required' : 'failed', error };
            }
        }));
        const active = results.filter((result) => result.status === 'active').length;
        const failed = results.filter((result) => ['failed', 'unavailable', 'permission-required', 'stale'].includes(result.status)).length;
        const skipped = results.filter((result) => ['removed', 'off'].includes(result.status)).length;
        if (failed && active) {
            setDeviceStatus(formatAudioInputSummary({ active, failed, skipped, total: results.length, results }), 'warning');
        }
        return { active, failed, skipped, total: results.length, results };
    }

    async function buildRecordingStreamFromProgram() {
        const tracks = [];
        const programOutput = ensureProgramCompositor() || state.programStream;
        if (programOutput) {
            programOutput.getVideoTracks().forEach((track) => {
                if (track.readyState !== 'ended') {
                    tracks.push(track);
                }
            });
        }
        const audioSummary = await ensureAudioInputStreams().catch((error) => ({ active: 0, failed: state.audioInputs.size || 1, skipped: 0, total: state.audioInputs.size || 1, results: [], error }));
        state.lastRecordingAudioSummary = audioSummary;
        if (audioSummary.active === 0) {
            setRecordingStatus(getStudioString('noMicrophoneInputsActive', 'No microphone inputs are active. Studio will continue without microphone audio.'), 'warning');
        } else if (audioSummary.failed > 0) {
            setRecordingStatus(formatAudioInputSummary(audioSummary, 'recording'), 'warning');
        }
        const mixedAudioTrack = getStudioMixedAudioTrack();
        if (mixedAudioTrack) {
            tracks.push(mixedAudioTrack);
        }
        studioDebugLog('[VH360 Studio] MediaRecorder mixed audio diagnostics', {
            mixerId: state.audioMixer ? state.audioMixer.id : '',
            audioTrackId: mixedAudioTrack ? mixedAudioTrack.id : '',
            matchesAgora: state.broadcastSession && typeof state.broadcastSession.getAudioTrackId === 'function' ? state.broadcastSession.getAudioTrackId() === (mixedAudioTrack && mixedAudioTrack.id) : null,
        });
        return tracks.length ? new MediaStream(tracks) : null;
    }

    function setProgramDiagnostics(message) {
        state.programStatusMessage = message || 'Program active';
        if (els.programDiagnostics) {
            els.programDiagnostics.textContent = state.programStatusMessage;
        }
        updateOperatorStatus();
    }

    function renderOnAirTabProtection() {
        const active = isOnAirOrRecording();
        if (els.onAirNotice) {
            els.onAirNotice.hidden = !active;
        }
        updateProgramCompositorLoop();
    }

    function studioWindowUrl() {
        const url = new URL(window.location.href);
        url.searchParams.set('vh360_studio_window', '1');
        return url.toString();
    }

    function applyStudioWindowMode() {
        const params = new URLSearchParams(window.location.search);
        state.isStudioWindow = params.get('vh360_studio_window') === '1';
        root.classList.toggle('is-studio-window', state.isStudioWindow);
        root.dataset.studioWindowMode = state.isStudioWindow ? '1' : '0';
        if (document.body) {
            document.body.classList.toggle('vh360-studio-window-mode', state.isStudioWindow);
        }
    }

    function openStudioWindow() {
        if (isOnAirOrRecording()) {
            setBroadcastStatus('Open Studio Window before going live. Your current live session cannot be moved to a new browser window. Keep this Studio tab visible while broadcasting.', 'warning');
            setStatus('Open Studio Window before going live. Keep this active Studio tab visible while broadcasting or recording.', 'warning');
            return;
        }

        const features = 'popup=yes,width=1440,height=900,noopener';
        const opened = window.open(studioWindowUrl(), 'vh360StudioWindow', features);
        if (opened) {
            try { opened.opener = null; } catch (error) {}
            return;
        }
        setStatus('Popup blocking prevented Studio Window from opening. Allow popups for this site and try again.', 'warning');
    }

    function captureHiddenStudioState() {
        state.hiddenActiveCameraSourceIds = new Set();
        state.hiddenActiveAudioInputIds = new Set();

        state.cameraSources.forEach((source) => {
            if (hasLiveVideoTrack(source.stream)) {
                state.hiddenActiveCameraSourceIds.add(source.sourceId);
            }
        });

        state.audioInputs.forEach((input) => {
            if (hasLiveAudioTrack(input.stream)) {
                state.hiddenActiveAudioInputIds.add(input.id);
            }
        });

        state.hiddenScreenWasActive = hasLiveVideoTrack(state.screenStream);
    }

    async function restartCameraAfterVisibility(source) {
        if (!source || !state.hiddenActiveCameraSourceIds.has(source.sourceId) || hasLiveVideoTrack(source.stream) || !source.deviceId || source.removed) {
            return null;
        }

        try {
            return await startCameraSource(source.sourceId, {
                force: true,
                deviceId: source.deviceId,
                deviceLabel: source.deviceLabel,
                preserveOnFailure: true,
            });
        } catch (error) {
            source.connected = false;
            source.connecting = false;
            source.unavailable = true;
            source.status = 'disconnected';
            source.error = (error && error.message) || getStudioString('cameraVisibilityRestartFailed', 'A previously active camera could not restart after Studio became visible.');
            setDeviceStatus((source.label || sourceLabel(source.sourceId)) + ': ' + source.error, 'warning');
            renderSourceState();
            return null;
        }
    }

    async function restartAudioInputAfterVisibility(input) {
        if (!input || input.removed || !state.hiddenActiveAudioInputIds.has(input.id) || hasLiveAudioTrack(input.stream)) {
            return null;
        }

        try {
            const stream = await startAudioInput(input.id, {
                force: true,
                reason: 'visibility-restore',
            });
            const current = state.audioInputs.get(input.id);
            if (hasLiveAudioTrack(stream) || hasLiveAudioTrack(current && current.stream)) {
                return stream;
            }
            return null;
        } catch (error) {
            const current = state.audioInputs.get(input.id);

            if (current) {
                current.connected = false;
                current.connecting = false;
                current.unavailable = true;
                current.status = 'disconnected';
                current.error = (error && error.message) || getStudioString('audioVisibilityRestartFailed', 'A previously active audio input could not restart after Studio became visible.');
                disconnectMixerChannel(current.mixerChannelId, current.id);
            }

            return null;
        }
    }

    async function restoreScreenPlaybackAfterVisibility() {
        if (!state.hiddenScreenWasActive && !state.screenStream) {
            return;
        }

        if (hasLiveVideoTrack(state.screenStream)) {
            if (els.screenPreview) {
                if (els.screenPreview.srcObject !== state.screenStream) {
                    els.screenPreview.srcObject = state.screenStream;
                }
                await els.screenPreview.play().catch(() => {});
            }
            setShellClass('is-screen-active', true);
            updateProgramAudioRouting();
            return;
        }

        if (state.screenStream) {
            await handleScreenShareEnded();
        }
    }

    async function resumeStudioAudioContext() {
        const context = state.audioMixer && state.audioMixer.context ? state.audioMixer.context : null;

        if (context && context.state === 'suspended' && typeof context.resume === 'function') {
            try {
                await context.resume();
            } catch (error) {
                setStatus(getStudioString('audioContextResumeFailed', 'Studio audio could not resume automatically. Interact with the Studio window and try again.'), 'warning');
            }
        }
    }

    function requestFreshProgramFrame() {
        const track = state.programOutputStream && state.programOutputStream.getVideoTracks ? state.programOutputStream.getVideoTracks()[0] : null;
        if (state.programContext && state.programCanvas && !state.programAnimationFrame && !state.programHiddenTimer) {
            drawProgramFrame({ hiddenFallback: document.hidden && isOnAirOrRecording() });
        }
        if (track && typeof track.requestFrame === 'function') {
            track.requestFrame();
        }
    }

    async function restoreStudioMediaAfterVisibility() {
        const cameraRecoveries = [];
        const audioRecoveries = [];

        state.cameraSources.forEach((source) => {
            if (hasLiveVideoTrack(source.stream)) {
                cameraRecoveries.push(ensureCameraElementPlaying(source));
                return;
            }

            if (state.hiddenActiveCameraSourceIds.has(source.sourceId)) {
                cameraRecoveries.push(restartCameraAfterVisibility(source));
            }
        });

        state.audioInputs.forEach((input) => {
            if (state.hiddenActiveAudioInputIds.has(input.id) && !hasLiveAudioTrack(input.stream)) {
                audioRecoveries.push(restartAudioInputAfterVisibility(input));
            }
        });

        await Promise.allSettled(cameraRecoveries);
        await Promise.allSettled(audioRecoveries);
        await restoreScreenPlaybackAfterVisibility();
        await resumeStudioAudioContext();

        ensureProgramCompositor();
        updateProgramCompositorLoop();
        requestFreshProgramFrame();

        renderSourceState();
        renderProgramState();
        refreshAudioInputDiagnostics();

        state.tabProtectionWarningPending = false;
        setProgramDiagnostics(getStudioString('studioVisibilityRestored', 'Studio media and Program output restored.'));
    }

    function queueStudioVisibilityRestore() {
        if (state.visibilityRestorePromise) {
            return state.visibilityRestorePromise;
        }

        state.visibilityRestorePromise = restoreStudioMediaAfterVisibility()
            .finally(() => {
                state.visibilityRestorePromise = null;
            });

        return state.visibilityRestorePromise;
    }

    function handleStudioVisibilityChange() {
        if (document.hidden) {
            captureHiddenStudioState();
            state.studioDocumentHidden = true;
            state.tabProtectionWarningPending = isOnAirOrRecording();
            updateProgramCompositorLoop();

            if (isOnAirOrRecording()) {
                setProgramDiagnostics(getStudioString('studioHiddenBackgroundWarning', 'Studio is hidden. Browser background limits may reduce the Program frame rate.'));
            }

            return;
        }

        state.studioDocumentHidden = false;
        queueStudioVisibilityRestore().catch(() => {});
    }


    root.querySelectorAll('[data-open-studio-diagnostics]').forEach((button) => {
        button.addEventListener('click', () => {
            if (els.diagnosticsModal) {
                els.diagnosticsModal.hidden = false;
                const close = els.diagnosticsModal.querySelector('[data-close-studio-diagnostics]');
                if (close) { close.focus(); }
            }
        });
    });
    root.querySelectorAll('[data-close-studio-diagnostics]').forEach((button) => {
        button.addEventListener('click', () => {
            if (els.diagnosticsModal) { els.diagnosticsModal.hidden = true; }
        });
    });
    if (els.diagnosticsModal) {
        els.diagnosticsModal.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') { els.diagnosticsModal.hidden = true; }
        });
    }
    function setStatus(message, type) {
        if (!els.status) {
            return;
        }
        els.status.textContent = message;
        els.status.dataset.statusType = type || 'info';
        updateOperatorStatus();
    }

    function requiredSupportError(context) {
        const missing = [];
        if (!state.support.secureContext) { missing.push('secure browser context'); }
        if (!state.support.mediaDevices) { missing.push('media devices API'); }
        if (!state.support.getUserMedia) { missing.push('camera and microphone access'); }
        if (!state.support.canvasContext) { missing.push('canvas drawing support'); }
        if ((context === 'live' || (context === 'recording' && hasProgramOutput())) && !state.support.canvasCapture) {
            missing.push('Program canvas output');
        }
        if (context === 'recording') {
            if (!state.support.mediaRecorder) { missing.push('browser recorder support'); }
            if (!preferredMimeType()) { missing.push('a supported recording format'); }
        }
        return missing.length ? 'Studio cannot ' + (context === 'live' ? 'go live' : 'start recording') + ' because this browser is missing: ' + missing.join(', ') + '.' : '';
    }

    function isStudioFullscreen() {
        return document.fullscreenElement === root;
    }

    async function toggleStudioFullscreen() {
        if (!document.fullscreenEnabled) {
            setStatus('Fullscreen is not supported in this browser.', 'warning');
            return;
        }

        try {
            if (isStudioFullscreen()) {
                await document.exitFullscreen();
            } else {
                // True popout should be a later dedicated Studio route/window, not active DOM relocation.
                await root.requestFullscreen();
            }
        } catch (error) {
            setStatus('Unable to toggle fullscreen mode.', 'error');
        }
    }

    function updateFullscreenButton() {
        if (!els.studioFullscreen) {
            return;
        }

        els.studioFullscreen.textContent = isStudioFullscreen() ? 'Exit Fullscreen' : 'Fullscreen Studio';
    }

    function hasActiveViewerLink() {
        return Boolean(state.broadcastSession && state.viewerPermalink);
    }

    function updateViewerLinkControls() {
        const active = hasActiveViewerLink();

        if (els.viewerLinkWrap) {
            els.viewerLinkWrap.hidden = !active;
        }

        if (els.openViewerLink) {
            els.openViewerLink.disabled = !active;
        }

        if (els.copyViewerLink) {
            els.copyViewerLink.disabled = !active;
        }

        if (els.copyViewerFeedback) {
            els.copyViewerFeedback.hidden = true;
            els.copyViewerFeedback.textContent = '';
        }
    }

    function openViewerLink() {
        if (!hasActiveViewerLink()) {
            setBroadcastStatus('The public viewer link is available after the livestream starts.', 'warning');
            updateViewerLinkControls();
            return;
        }

        const viewerWindow = window.open(state.viewerPermalink, '_blank');

        if (viewerWindow) {
            viewerWindow.opener = null;
        } else {
            setBroadcastStatus('Popup blocking prevented the viewer page from opening. Allow popups for this site and try again.', 'warning');
        }
    }

    async function copyViewerLink() {
        if (!hasActiveViewerLink()) {
            setBroadcastStatus('The public viewer link is available after the livestream starts.', 'warning');
            updateViewerLinkControls();
            return;
        }

        try {
            await copyTextToClipboard(state.viewerPermalink);

            if (els.copyViewerFeedback) {
                els.copyViewerFeedback.textContent = 'Copied';
                els.copyViewerFeedback.hidden = false;
                window.setTimeout(() => {
                    if (els.copyViewerFeedback) {
                        els.copyViewerFeedback.hidden = true;
                    }
                }, 2200);
            }
        } catch (error) {
            if (els.copyViewerFeedback) {
                els.copyViewerFeedback.textContent = 'Copy failed';
                els.copyViewerFeedback.hidden = false;
            }

            setBroadcastStatus('Unable to copy viewer link. Open the viewer page and copy it from the address bar.', 'warning');
        }
    }

    async function copyTextToClipboard(text) {
        if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
            await navigator.clipboard.writeText(text);
            return;
        }
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.setAttribute('readonly', 'readonly');
        textarea.style.position = 'fixed';
        textarea.style.left = '-9999px';
        document.body.appendChild(textarea);
        textarea.select();
        try {
            if (!document.execCommand('copy')) {
                throw new Error('Copy command was not available.');
            }
        } finally {
            textarea.remove();
        }
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
        const mimeCandidates = recordingMimeCandidates();
        const mimeTypes = mediaRecorder
            ? mimeCandidates.filter((type) => window.MediaRecorder.isTypeSupported(type))
            : [];
        const canvas = document.createElement('canvas');
        const canvasContext = Boolean(canvas && typeof canvas.getContext === 'function' && canvas.getContext('2d'));
        const canvasCapture = Boolean(window.HTMLCanvasElement && window.HTMLCanvasElement.prototype && typeof window.HTMLCanvasElement.prototype.captureStream === 'function');
        const fallbackCopy = typeof document.queryCommandSupported === 'function' ? document.queryCommandSupported('copy') : typeof document.execCommand === 'function';

        state.support = {
            mediaDevices,
            getUserMedia: mediaDevices && typeof navigator.mediaDevices.getUserMedia === 'function',
            enumerateDevices: mediaDevices && typeof navigator.mediaDevices.enumerateDevices === 'function',
            getDisplayMedia: mediaDevices && typeof navigator.mediaDevices.getDisplayMedia === 'function',
            deviceChange: mediaDevices && 'ondevicechange' in navigator.mediaDevices,
            mediaRecorder,
            secureContext: window.isSecureContext || window.location.hostname === 'localhost',
            mimeTypes,
            canvasContext,
            canvasCapture,
            clipboardCopy: Boolean(navigator.clipboard && typeof navigator.clipboard.writeText === 'function') || fallbackCopy,
        };
    }

    function readinessIssues() {
        const issues = [];
        if (!state.support.secureContext) {
            issues.push(getStudioString('readinessHttpsRequired', 'Use HTTPS or localhost for Studio recording.'));
        }
        if (!state.support.mediaDevices || !state.support.getUserMedia) {
            issues.push(getStudioString('readinessCameraMicrophoneUnavailable', 'Camera or microphone access is unavailable. Allow browser permissions, then refresh.'));
        }
        if (!state.support.getDisplayMedia) {
            issues.push(getStudioString('readinessScreenShareUnavailable', 'Screen sharing is unavailable in this browser.'));
        }
        if (!state.support.mediaRecorder) {
            issues.push(getStudioString('readinessRecordingUnsupported', 'This browser does not support recording. Try Chrome, Edge, or Safari.'));
        }
        if (!state.support.canvasContext || !state.support.canvasCapture) {
            issues.push(getStudioString('readinessCanvasUnsupported', 'Program canvas recording is unsupported in this browser.'));
        }
        if (!state.support.mimeTypes || !state.support.mimeTypes.length) {
            issues.push(getStudioString('readinessNoRecordingFormat', 'No supported recording format was detected.'));
        }
        return issues;
    }

    function renderReadinessSummary() {
        const issues = readinessIssues();
        if (els.readinessSummary) {
            els.readinessSummary.dataset.statusType = issues.length ? 'warning' : 'neutral';
        }
        if (els.readinessHeading) {
            els.readinessHeading.textContent = issues.length ? getStudioString('readinessNeedsAttention', 'Studio needs attention.') : getStudioString('readinessReadyToGoLive', 'Studio diagnostics');
        }
        if (els.readinessMessage) {
            els.readinessMessage.textContent = issues.length ? getStudioString('readinessResolveItems', 'Resolve the items below, then refresh Studio if needed.') : getStudioString('readinessAllSupported', 'No compatibility issues detected.');
        }
        if (els.diagnosticsButton) {
            els.diagnosticsButton.hidden = !issues.length;
            els.diagnosticsButton.setAttribute('aria-label', issues.length ? getStudioString('readinessNeedsAttention', 'Studio needs attention.') + ' ' + issues.length : '');
        }
        if (els.readinessIssues) {
            els.readinessIssues.innerHTML = '';
            els.readinessIssues.hidden = !issues.length;
            issues.forEach((issue) => {
                const item = document.createElement('li');
                item.textContent = issue;
                els.readinessIssues.appendChild(item);
            });
        }
    }

    function renderSupportChecks() {
        renderReadinessSummary();

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
            ['canvasCapture', state.support.canvasCapture],
            ['canvasContext', state.support.canvasContext],
            ['clipboardCopy', state.support.clipboardCopy],
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
        mimeItem.innerHTML = '<span>' + escapeHtml(supportLabels.mimeTypes || getStudioString('formatsFallbackLabel', 'Formats')) + '</span><strong>' + escapeHtml(state.support.mimeTypes.length ? state.support.mimeTypes.join(', ') : strings.notSupported) + '</strong>';
        els.supportChecks.appendChild(mimeItem);

        const preferred = preferredMimeType();
        if (isWebmMime(preferred)) {
            const warning = document.createElement('li');
            warning.className = 'is-unsupported';
            warning.innerHTML = '<span>' + escapeHtml(getStudioString('recordingFormatLabel', 'Recording format')) + '</span><strong>' + escapeHtml(webmFallbackWarning()) + '</strong>';
            els.supportChecks.appendChild(warning);
        }
    }

    function updateReadinessStatus() {
        if (!state.support.secureContext) {
            setStatus(strings.insecureContext, 'error');
            return;
        }
        if (!state.support.mediaDevices || !state.support.getUserMedia || !state.support.mediaRecorder || !state.support.canvasContext || !state.support.canvasCapture) {
            setStatus(strings.browserUnsupported, 'error');
            return;
        }
        setStatus(strings.ready, 'success');
        updateOperatorStatus();
    }

    function escapeHtml(value) {
        const div = document.createElement('div');
        div.textContent = value || '';
        return div.innerHTML;
    }


    function storageGet(key) {
        try { return window.localStorage ? window.localStorage.getItem(key) || '' : ''; } catch (error) { return ''; }
    }

    function storageSet(key, value) {
        try {
            if (!window.localStorage) { return; }
            if (value) { window.localStorage.setItem(key, value); } else { window.localStorage.removeItem(key); }
        } catch (error) {}
    }

    function createCameraElement(source) {
        const video = document.createElement('video');
        video.className = 'vh360-studio-preview-video vh360-studio-preview-video--camera vh360-studio-camera-source-video';
        video.playsInline = true;
        video.muted = true;
        video.autoplay = true;
        video.dataset.cameraSourceId = source.sourceId;
        video.setAttribute('aria-label', source.label || getStudioString('cameraSummaryLabel', 'Camera'));
        return video;
    }

    function createCameraSource(config = {}, options = {}) {
        const id = config.id || ('camera-input-' + (++state.cameraSourceCounter));
        const numeric = Number(String(id).replace(/\D+/g, '')) || state.cameraSourceCounter;
        state.cameraSourceCounter = Math.max(state.cameraSourceCounter, numeric);
        const source = {
            id,
            sourceId: 'camera:' + id,
            label: config.label || (config.isPrimary ? getStudioString('cameraOnlyLabel', 'Camera Only') : getStudioString('cameraSourceDefaultName', 'Camera {number}').replace('{number}', state.cameraSources.size + 1)),
            deviceId: config.deviceId || '',
            deviceLabel: config.deviceLabel || '',
            stream: null,
            element: null,
            isPrimary: Boolean(config.isPrimary),
            connected: false,
            connecting: false,
            unavailable: false,
            status: 'off',
            error: '',
            removed: false,
            detached: false,
            startRequestId: 0,
        };
        source.element = createCameraElement(source);
        ensureCameraElementAttached(source);
        state.cameraSources.set(id, source);
        if (source.isPrimary || !state.primaryCameraSourceId) {
            state.primaryCameraSourceId = id;
            source.isPrimary = true;
        }
        if (!options.skipPersist) { scheduleCameraSourceConfigurationSave(); }
        return source;
    }

    function normalizeStoredCameraSources(restored) {
        const seen = new Set();
        const normalized = [];
        (Array.isArray(restored) ? restored : []).forEach((item) => {
            if (!item || typeof item !== 'object') { return; }
            const id = String(item.id || cameraIdFromSourceId(item.sourceId || '') || '').replace(/[^a-zA-Z0-9_-]/g, '');
            if (!id || seen.has(id)) { return; }
            seen.add(id);
            normalized.push({
                id,
                label: String(item.label || '').trim(),
                deviceId: String(item.deviceId || ''),
                deviceLabel: String(item.deviceLabel || ''),
                isPrimary: Boolean(item.isPrimary),
            });
        });
        if (normalized.length) {
            const primary = normalized.find((item) => item.isPrimary) || normalized[0];
            normalized.forEach((item) => { item.isPrimary = item === primary; });
        }
        return normalized;
    }

    function enforcePrimaryCameraSourceInvariant() {
        if (!state.cameraSources.size) {
            createCameraSource({ id: 'camera-input-1', label: getStudioString('cameraOnlyLabel', 'Camera Only'), deviceId: storageGet(CAMERA_STORAGE_KEY), isPrimary: true }, { skipPersist: true });
        }
        let primary = Array.from(state.cameraSources.values()).find((source) => source.isPrimary && !source.removed) || Array.from(state.cameraSources.values())[0];
        state.cameraSources.forEach((source) => { source.isPrimary = source === primary; });
        state.primaryCameraSourceId = primary ? primary.id : '';
        storageSet(CAMERA_STORAGE_KEY, primary ? primary.deviceId : '');
    }

    function saveCameraSourceConfiguration() {
        const sources = Array.from(state.cameraSources.values()).filter((source) => !source.removed).map((source) => ({
            id: source.id,
            sourceId: source.sourceId,
            label: source.label,
            deviceId: source.deviceId,
            deviceLabel: source.deviceLabel,
            isPrimary: source.isPrimary,
        }));
        storageSet(CAMERA_SOURCES_STORAGE_KEY, JSON.stringify(sources));
        const primary = primaryCameraSource();
        storageSet(CAMERA_STORAGE_KEY, primary ? primary.deviceId : '');
    }

    function scheduleCameraSourceConfigurationSave() {
        if (state.cameraSourceSaveTimer) { window.clearTimeout(state.cameraSourceSaveTimer); }
        state.cameraSourceSaveTimer = window.setTimeout(() => {
            state.cameraSourceSaveTimer = null;
            saveCameraSourceConfiguration();
        }, 100);
    }

    function flushCameraSourceConfigurationSave() {
        if (state.cameraSourceSaveTimer) {
            window.clearTimeout(state.cameraSourceSaveTimer);
            state.cameraSourceSaveTimer = null;
        }
        saveCameraSourceConfiguration();
    }

    function loadSavedDevicePreferences() {
        let restoredCameras = [];
        try {
            const parsed = JSON.parse(storageGet(CAMERA_SOURCES_STORAGE_KEY) || '[]');
            if (Array.isArray(parsed)) { restoredCameras = parsed; }
        } catch (error) {
            restoredCameras = [];
        }
        const normalizedCameras = normalizeStoredCameraSources(restoredCameras);
        if (normalizedCameras.length) {
            normalizedCameras.forEach((source) => createCameraSource(source, { skipPersist: true }));
        } else {
            createCameraSource({ id: 'camera-input-1', label: getStudioString('cameraOnlyLabel', 'Camera Only'), deviceId: storageGet(CAMERA_STORAGE_KEY), isPrimary: true }, { skipPersist: true });
        }
        enforcePrimaryCameraSourceInvariant();
        saveCameraSourceConfiguration();
        let restored = [];
        try {
            const parsed = JSON.parse(storageGet(AUDIO_INPUTS_STORAGE_KEY) || '[]');
            if (Array.isArray(parsed)) { restored = parsed; }
        } catch (error) {
            studioDebugLog('[VH360 Studio] Audio input configuration was malformed and will be reset', { errorName: error && error.name });
            restored = [];
        }
        normalizeStoredAudioInputs(restored).forEach((input) => {
            createAudioInputSource(input, { restoring: true, skipPersist: true });
        });
        enforcePrimaryAudioInputInvariant();
        saveAudioInputConfiguration();
        renderAudioInputChannels();
    }

    function selectedOptionLabel(select, fallback) {
        if (!select || select.disabled || !select.options.length) { return fallback; }
        const option = select.options[select.selectedIndex >= 0 ? select.selectedIndex : 0];
        return option && option.value ? option.textContent || fallback : fallback;
    }

    function selectedDeviceLabel(select, fallback) {
        if (!select || select.disabled || !select.options.length) { return fallback; }
        const option = select.options[select.selectedIndex >= 0 ? select.selectedIndex : 0];
        if (!option || !option.value) { return fallback; }
        return option.dataset.deviceLabel || option.textContent || fallback;
    }

    function videoDeviceLabelById(deviceId, fallback) {
        if (!deviceId) { return fallback || ''; }
        const device = (state.availableVideoInputDevices || []).find((item) => item.deviceId === deviceId);
        return device && device.label ? device.label : (fallback || '');
    }

    function cameraReadinessValue(fallback) {
        const source = getSelectedCameraSource() || primaryCameraSource();
        if (!source) { return fallback; }
        if (source.deviceLabel) { return source.deviceLabel; }
        if (source.deviceId) { return getStudioString('savedCameraLabel', 'Saved camera'); }
        if (hasLiveVideoTrack(source.stream)) { return source.label || getStudioString('cameraSummaryLabel', 'Camera'); }
        return fallback;
    }

    function updateActiveDeviceSummary() {
        if (!els.activeDevices) { return; }
        const cameraSources = Array.from(state.cameraSources.values()).filter((source) => !source.removed);
        const activeCameras = cameraSources.filter((source) => hasLiveVideoTrack(source.stream)).length;
        const unavailableCameras = cameraSources.filter((source) => ['unavailable', 'disconnected', 'error'].includes(source.status)).length;
        const selectedCamera = getActiveCameraSource();
        const camera = selectedCamera ? (selectedCamera.label + (selectedCamera.deviceLabel ? ': ' + selectedCamera.deviceLabel : '')) : selectedOptionLabel(els.cameraSelect, getStudioString('audioStatusPermissionRequired', 'Permission required'));
        const inputs = Array.from(state.audioInputs.values());
        const activeInputs = inputs.filter((input) => hasLiveAudioTrack(input.stream)).length;
        const unavailableInputs = inputs.filter((input) => ['unavailable', 'disconnected', 'permission-required', 'error'].includes(audioInputStatus(input))).length;
        const offInputs = inputs.filter((input) => audioInputStatus(input) === 'off').length;
        const primary = primaryAudioInput();
        const mic = primary ? (primary.deviceLabel || selectedOptionLabel(els.micSelect, getStudioString('audioStatusPermissionRequired', 'Permission required'))) : getStudioString('audioStatusPermissionRequired', 'Permission required');
        const duplicateCount = getDuplicateAudioDeviceAssignments().size;
        const parts = [
            getStudioString('cameraSummaryLabel', 'Camera') + ': ' + camera,
            getStudioString('cameraSourcesActiveSummary', '{active} camera source(s) active.').replace('{active}', activeCameras),
            getStudioString('primaryMicrophoneSummaryLabel', 'Primary microphone') + ': ' + mic,
            getStudioString('audioInputsActiveSummary', '{active} audio input(s) active.').replace('{active}', activeInputs),
        ];
        if (unavailableInputs) { parts.push(getStudioString('audioInputsUnavailableSummary', '{count} audio input(s) unavailable.').replace('{count}', unavailableInputs)); }
        if (unavailableCameras) { parts.push(getStudioString('cameraSourcesUnavailableSummary', '{count} camera source(s) unavailable.').replace('{count}', unavailableCameras)); }
        if (offInputs) { parts.push(getStudioString('audioInputsOffSummary', '{count} audio input(s) off.').replace('{count}', offInputs)); }
        if (!activeInputs) { parts.push(getStudioString('noMicrophoneInputsActiveShort', 'No microphone inputs active.')); }
        if (duplicateCount) { parts.push(getStudioString('duplicateMicrophoneSelection', 'Duplicate microphone selection warning.')); }
        if (getDuplicateCameraDeviceAssignments().size) { parts.push(getStudioString('duplicateCameraSelection', 'Duplicate camera assignment warning.')); }
        els.activeDevices.textContent = parts.join(' · ');
    }

    function refreshAudioInputDiagnostics() {
        updateMixerUi();
        updateActiveDeviceSummary();
        updateRecordingAudioInputWarnings();
        updateLiveAudioInputWarning();
        renderDeviceReadinessDetails().catch(() => {});
    }


    async function permissionStateLabel(name) {
        if (!navigator.permissions || typeof navigator.permissions.query !== 'function') {
            return getStudioString('permissionStatePrompt', 'Prompt');
        }
        try {
            const status = await navigator.permissions.query({ name });
            if (status.state === 'granted') { return getStudioString('permissionStateAllowed', 'Allowed'); }
            if (status.state === 'denied') { return getStudioString('permissionStateBlocked', 'Blocked'); }
            return getStudioString('permissionStatePrompt', 'Prompt');
        } catch (error) {
            return getStudioString('permissionStatePrompt', 'Prompt');
        }
    }

    async function renderDeviceReadinessDetails() {
        if (!els.supportChecks) { return; }
        const requestId = (state.deviceReadinessRequestId || 0) + 1;
        state.deviceReadinessRequestId = requestId;
        els.supportChecks.querySelectorAll('[data-device-readiness]').forEach((item) => item.remove());
        const cameraPermission = await permissionStateLabel('camera');
        const microphonePermission = await permissionStateLabel('microphone');
        if (state.deviceReadinessRequestId !== requestId) { return; }
        const permissionRequired = getStudioString('audioStatusPermissionRequired', 'Permission required');
        const details = [
            [getStudioString('cameraPermissionReadinessLabel', 'Camera permission'), cameraPermission, cameraPermission === getStudioString('permissionStateBlocked', 'Blocked')],
            [getStudioString('microphonePermissionReadinessLabel', 'Microphone permission'), microphonePermission, microphonePermission === getStudioString('permissionStateBlocked', 'Blocked')],
            [getStudioString('cameraSelectedReadinessLabel', 'Camera selected'), cameraReadinessValue(permissionRequired), cameraReadinessValue(permissionRequired) === permissionRequired],
            [getStudioString('primaryMicrophoneSelectedReadinessLabel', 'Primary microphone selected'), selectedOptionLabel(els.micSelect, permissionRequired), selectedOptionLabel(els.micSelect, permissionRequired) === permissionRequired],
            [getStudioString('configuredCameraSourcesReadinessLabel', 'Configured camera sources'), String(state.cameraSources.size || 1), false],
            [getStudioString('configuredAudioInputsReadinessLabel', 'Configured audio inputs'), String(state.audioInputs.size || 1), false],
        ];
        els.supportChecks.querySelectorAll('[data-device-readiness]').forEach((item) => item.remove());
        details.forEach(([label, value, unsupported]) => {
            const item = document.createElement('li');
            item.dataset.deviceReadiness = 'true';
            item.className = unsupported ? 'is-unsupported' : 'is-supported';
            item.innerHTML = '<span>' + escapeHtml(label) + '</span><strong>' + escapeHtml(value) + '</strong>';
            els.supportChecks.appendChild(item);
        });
    }

    function setDeviceStatus(message, type) {
        if (!els.deviceStatus) { return; }
        els.deviceStatus.textContent = message || '';
        els.deviceStatus.dataset.statusType = type || 'info';
    }

    async function refreshDevices(options = {}) {
        if (els.refreshDevices) { els.refreshDevices.disabled = true; }
        try {
            await populateDevices(options);
        } finally {
            if (els.refreshDevices) { els.refreshDevices.disabled = false; }
        }
    }

    async function populateDevices(options = {}) {
        if (!state.support.enumerateDevices) {
            setDeviceStatus(getStudioString('deviceRefreshUnavailable', 'Device refresh is unavailable in this browser.'), 'warning');
            return;
        }

        const devices = await navigator.mediaDevices.enumerateDevices();
        const cameras = devices.filter((device) => device.kind === 'videoinput');
        const microphones = devices.filter((device) => device.kind === 'audioinput');

        state.availableVideoInputDevices = cameras;
        state.availableAudioInputDevices = microphones;
        let staleDeviceMessage = '';
        state.cameraSources.forEach((source) => {
            const live = hasLiveVideoTrack(source.stream);
            const matchedDevice = source.deviceId ? cameras.find((device) => device.deviceId === source.deviceId) : null;
            if (matchedDevice && matchedDevice.label) { source.deviceLabel = matchedDevice.label; }
            if (source.deviceId && !matchedDevice && !live) {
                source.connected = false;
                source.unavailable = true;
                source.status = 'unavailable';
                source.error = getStudioString('selectedCameraUnavailable', 'Selected camera is unavailable. Choose another device for this source.');
                staleDeviceMessage = staleDeviceMessage ? staleDeviceMessage + ' ' + getStudioString('oneCameraSourceUnavailable', 'One saved camera source is unavailable.') : getStudioString('oneCameraSourceUnavailable', 'One saved camera source is unavailable.');
            } else if (live) {
                source.connected = true;
                source.unavailable = false;
                source.status = 'active';
                source.error = '';
            } else if (!source.connecting) {
                source.connected = false;
                source.unavailable = false;
                if (source.status === 'unavailable' || source.status === 'disconnected') { source.status = 'off'; }
                if (source.error === getStudioString('selectedCameraUnavailable', 'Selected camera is unavailable. Choose another device for this source.')) { source.error = ''; }
            }
        });
        const unavailableAudioDeviceMessage = getStudioString('selectedAudioDeviceUnavailable', 'Selected audio device is unavailable. Choose another device for this input.');
        state.audioInputs.forEach((input) => {
            const live = hasLiveAudioTrack(input.stream);
            const matchedDevice = input.deviceId ? microphones.find((device) => device.deviceId === input.deviceId) : null;
            if (matchedDevice && matchedDevice.label) { input.deviceLabel = matchedDevice.label; }
            if (input.isPrimary && !input.deviceId && !microphones.length && !live) {
                input.connected = false;
                input.unavailable = true;
                input.status = 'unavailable';
                input.error = unavailableAudioDeviceMessage;
            } else if (input.deviceId && !matchedDevice) {
                if (live) {
                    input.connected = true;
                    input.unavailable = false;
                    input.status = input.muted ? 'muted' : 'active';
                    input.error = '';
                } else {
                    input.connected = false;
                    input.unavailable = true;
                    input.status = 'unavailable';
                    input.error = unavailableAudioDeviceMessage;
                    const warning = getStudioString('oneAudioInputUnavailable', 'One saved audio input is unavailable.');
                    staleDeviceMessage = staleDeviceMessage ? staleDeviceMessage + ' ' + warning : warning;
                }
            } else if (!input.connected) {
                input.unavailable = false;
                if (input.status === 'unavailable' || input.status === 'disconnected') { input.status = 'off'; }
                if (input.error === unavailableAudioDeviceMessage) { input.error = ''; }
            } else if (live) {
                input.status = input.muted ? 'muted' : 'active';
            }
        });
        renderCameraScenes();
        renderCameraSourceDialog();
        const selectedCamera = getActiveCameraSource();
        fillDeviceSelect(els.cameraSelect, cameras, getStudioString('cameraSummaryLabel', 'Camera'), selectedCamera ? selectedCamera.deviceId : '');
        preserveSelectedCameraDeviceOption(els.cameraSelect, selectedCamera);
        renderSelectedCameraControls();
        renderSourceState();
        const primary = primaryAudioInput();
        fillDeviceSelect(els.micSelect, microphones, getStudioString('microphoneFallbackLabel', 'Microphone'), primary ? primary.deviceId : '');
        preservePrimaryDefaultMicrophoneOption(els.micSelect, primary);
        preserveSelectedDeviceOption(els.micSelect, primary);

        const primaryCamera = primaryCameraSource();
        if (!options.keepDefaultCamera && primaryCamera && !primaryCamera.deviceId && els.cameraSelect && els.cameraSelect.value) {
            primaryCamera.deviceId = els.cameraSelect.value;
            primaryCamera.deviceLabel = selectedDeviceLabel(els.cameraSelect, '');
            scheduleCameraSourceConfigurationSave();
        }
        if (!options.keepDefaultMicrophone && primary && !primary.deviceId && !primary.connecting && !hasLiveAudioTrack(primary.stream) && els.micSelect && els.micSelect.value) {
            primary.deviceId = els.micSelect.value;
            primary.deviceLabel = selectedDeviceLabel(els.micSelect, '');
            storageSet(MIC_STORAGE_KEY, primary.deviceId);
        }

        const cameraLabel = (cameras.length === 1 ? getStudioString('oneCameraDetected', '1 camera') : getStudioString('multipleCamerasDetected', '{count} cameras').replace('{count}', cameras.length));
        const micLabel = (microphones.length === 1 ? getStudioString('oneMicrophoneDetected', '1 microphone') : getStudioString('multipleMicrophonesDetected', '{count} microphones').replace('{count}', microphones.length));
        refreshAudioInputDiagnostics();
        scheduleAudioInputConfigurationSave();
        if (staleDeviceMessage) {
            setDeviceStatus(staleDeviceMessage, 'warning');
            return;
        }
        if (options.reason === 'manual' && (state.broadcastSession || isRecordingActive())) {
            setDeviceStatus(getStudioString('devicesRefreshedLiveUnchanged', 'Devices refreshed. Current live devices were not changed.'), 'info');
            return;
        }
        if (options.reason === 'devicechange' && cameras.length) {
            setDeviceStatus(getStudioString('deviceChangeNewCamera', 'Device change detected. Cameras and microphones refreshed. New camera detected. Choose it from Sources.'), 'info');
        }
        if (!cameras.length) {
            setStatus(strings.noCameraFound, 'warning');
            setDeviceStatus(getStudioString('noCamerasDetectedDetail', 'No cameras detected. Plug in a camera, grant browser permission, then refresh devices.'), 'warning');
        } else if (!microphones.length) {
            setStatus(strings.noMicrophoneFound, 'warning');
            setDeviceStatus(getStudioString('camerasDetectedNoMicrophones', '{cameraLabel} detected. No microphones detected.').replace('{cameraLabel}', cameraLabel), 'warning');
        } else {
            const statusTemplate = options.reason === 'devicechange' ? getStudioString('devicesDetectedAfterChange', '{cameraLabel} and {micLabel} detected after a device change.') : getStudioString('devicesDetected', '{cameraLabel} and {micLabel} detected.');
            setDeviceStatus(statusTemplate.replace('{cameraLabel}', cameraLabel).replace('{micLabel}', micLabel), 'success');
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
            option.textContent = getStudioString('deviceUnavailableOption', '{device} unavailable').replace('{device}', fallbackLabel);
            select.appendChild(option);
            select.disabled = true;
            return;
        }

        devices.forEach((device, index) => {
            const option = document.createElement('option');
            option.value = device.deviceId;
            option.dataset.deviceLabel = device.label || '';
            option.textContent = device.label || getStudioString('deviceFallbackOption', '{device} {index}').replace('{device}', fallbackLabel).replace('{index}', index + 1);
            if (selectedDeviceId && device.deviceId === selectedDeviceId) {
                option.selected = true;
            }
            select.appendChild(option);
        });
        select.disabled = false;
    }

    function preserveSelectedDeviceOption(select, input) {
        if (!select || !input || !input.deviceId) { return; }
        const hasOption = Array.from(select.options).some((option) => option.value === input.deviceId);
        if (hasOption) { return; }
        const option = document.createElement('option');
        option.value = input.deviceId;
        option.dataset.deviceLabel = input.deviceLabel || '';
        const status = audioInputStatus(input);
        option.textContent = getStudioString('deviceStateOption', '{device} ({status})')
            .replace('{device}', input.deviceLabel || getStudioString('savedMicrophoneLabel', 'Saved microphone'))
            .replace('{status}', audioInputStatusLabel(status === 'active' || status === 'muted' ? 'active' : 'unavailable'));
        option.selected = true;
        select.appendChild(option);
        select.disabled = false;
    }

    function preservePrimaryDefaultMicrophoneOption(select, input) {
        if (!select || !input || !input.isPrimary || input.deviceId) { return; }
        const option = document.createElement('option');
        option.value = '';
        option.dataset.deviceLabel = '';
        const live = hasLiveAudioTrack(input.stream);
        option.textContent = live
            ? getStudioString('deviceStateOption', '{device} ({status})').replace('{device}', getStudioString('defaultMicrophone', 'Default microphone')).replace('{status}', audioInputStatusLabel('active'))
            : getStudioString('defaultMicrophone', 'Default microphone');
        option.selected = true;
        select.insertBefore(option, select.firstChild || null);
        select.value = '';
        select.disabled = false;
    }

    function preserveSelectedCameraDeviceOption(select, source) {
        if (!select || !source || !source.deviceId) { return; }
        if (Array.from(select.options).some((option) => option.value === source.deviceId)) { return; }
        const option = document.createElement('option');
        option.value = source.deviceId;
        option.dataset.deviceLabel = source.deviceLabel || '';
        option.textContent = getStudioString('deviceStateOption', '{device} ({status})')
            .replace('{device}', source.deviceLabel || getStudioString('savedCameraLabel', 'Saved camera'))
            .replace('{status}', cameraSourceStatusLabel(source.status === 'active' ? 'active' : 'unavailable'));
        option.selected = true;
        select.appendChild(option);
        select.disabled = false;
    }

    function cameraSourceStatusLabel(status) {
        const labels = {
            active: getStudioString('cameraSourceStatusActive', 'active'),
            off: getStudioString('cameraSourceStatusOff', 'off'),
            connecting: getStudioString('cameraSourceStatusConnecting', 'connecting'),
            unavailable: getStudioString('cameraSourceStatusUnavailable', 'unavailable'),
            disconnected: getStudioString('cameraSourceStatusDisconnected', 'disconnected'),
            error: getStudioString('cameraSourceStatusError', 'error'),
        };
        return labels[status] || status || labels.off;
    }

    function getDuplicateCameraDeviceAssignments() {
        const assigned = new Map();
        const duplicates = new Set();
        state.cameraSources.forEach((source) => {
            if (!source || source.removed || !source.deviceId) { return; }
            if (assigned.has(source.deviceId)) { duplicates.add(source.deviceId); }
            assigned.set(source.deviceId, source.id);
        });
        return duplicates;
    }

    function isCameraDeviceAssigned(deviceId, exceptSourceId) {
        if (!deviceId) { return false; }
        return Array.from(state.cameraSources.values()).some((source) => !source.removed && source.sourceId !== exceptSourceId && source.deviceId === deviceId);
    }

    async function startCameraSource(sourceId, options = {}) {
        const source = getCameraSource(sourceId);
        if (!source || source.removed) { throw new Error(getStudioString('selectedCameraUnavailable', 'Selected camera unavailable.')); }
        if (!state.support.secureContext) { throw new Error(strings.insecureContext); }
        if (!state.support.getUserMedia) { throw new Error(strings.browserUnsupported); }
        if (!options.force && hasLiveVideoTrack(source.stream)) { return source.stream; }
        if (!options.force && isSourceProtected(source.sourceId)) {
            warnProtectedSource(source.sourceId);
            throw new Error(getStudioString('protectedCameraSource', 'This camera is currently in Program. Send another source to Program before changing it.'));
        }
        const previousState = {
            deviceId: source.deviceId,
            deviceLabel: source.deviceLabel,
            connected: source.connected,
            unavailable: source.unavailable,
            status: source.status,
            error: source.error,
        };
        const requestedDeviceId = Object.prototype.hasOwnProperty.call(options, 'deviceId') ? options.deviceId : source.deviceId;
        const requestId = ++source.startRequestId;
        source.connecting = true;
        source.status = 'connecting';
        source.error = '';
        renderSelectedCameraControls();
        try {
            const preset = getSelectedPreset();
            source.detached = false;
            ensureCameraElementAttached(source);
            const previousStream = source.stream;
            const previousSrcObject = source.element ? source.element.srcObject : null;
            const stream = await navigator.mediaDevices.getUserMedia({ video: buildVideoConstraints(preset, requestedDeviceId), audio: false });
            if (source.removed || requestId !== source.startRequestId) {
                stopStream(stream);
                return null;
            }
            source.stream = stream;
            source.element.srcObject = stream;
            const track = stream.getVideoTracks()[0];
            const settings = track && typeof track.getSettings === 'function' ? track.getSettings() : {};
            const ready = await ensureCameraElementPlaying(source);
            if (!ready) {
                const playbackError = source.error || getStudioString('cameraPlaybackNotReady', 'Camera video is not ready yet.');
                stopStream(stream);
                source.stream = previousStream || null;
                if (source.element) {
                    source.element.srcObject = previousStream || previousSrcObject || null;
                }
                if (previousStream) {
                    await ensureCameraElementPlaying(source, { timeoutMs: 1000 });
                }
                source.error = playbackError;
                throw new Error(source.error || getStudioString('cameraPlaybackNotReady', 'Camera video is not ready yet.'));
            }
            stopStream(previousStream);
            source.deviceId = settings && settings.deviceId ? settings.deviceId : requestedDeviceId || source.deviceId;
            source.deviceLabel = (track && track.label) || options.deviceLabel || videoDeviceLabelById(requestedDeviceId, source.deviceLabel);
            source.connected = true;
            source.connecting = false;
            source.unavailable = false;
            source.status = 'active';
            source.error = '';
            if (track) { track.addEventListener('ended', () => handleCameraSourceEnded(source.sourceId, stream), { once: true }); }
            ensureAudioInputStreams().catch((micError) => setDeviceStatus(friendlyMediaError(micError), 'warning'));
            await populateDevices({ reason: 'camera-start', keepDefaultCamera: true }).catch(() => {});
            if (state.programSource === source.sourceId) { state.programStream = stream; renderProgramState(); }
            scheduleCameraSourceConfigurationSave();
            renderSourceState();
            return stream;
        } catch (error) {
            if (requestId === source.startRequestId) {
                if (options.preserveOnFailure) {
                    source.deviceId = previousState.deviceId;
                    source.deviceLabel = previousState.deviceLabel;
                    source.connected = previousState.connected;
                    source.unavailable = previousState.unavailable;
                    source.status = previousState.status;
                    source.error = previousState.error;
                    source.connecting = false;
                } else {
                    source.connecting = false;
                    source.connected = false;
                    source.status = 'error';
                    source.error = friendlyMediaError(error);
                    source.unavailable = true;
                }
            }
            renderSelectedCameraControls();
            throw error;
        }
    }

    function stopCameraSource(sourceId, options = {}) {
        const source = getCameraSource(sourceId);
        if (!source) { return false; }
        if (!options.force && isSourceProtected(source.sourceId)) {
            warnProtectedSource(source.sourceId);
            return false;
        }
        source.startRequestId += 1;
        stopStream(source.stream);
        source.stream = null;
        if (source.element) { source.element.srcObject = null; }
        if (options.detach && source.element && source.element.parentNode) {
            source.element.parentNode.removeChild(source.element);
        }
        source.detached = Boolean(options.detach);
        source.connected = false;
        source.connecting = false;
        source.status = source.unavailable ? 'unavailable' : 'off';
        if (state.previewSource === source.sourceId) { state.previewSource = fallbackPreviewSource(source.sourceId); }
        if (state.programSource === source.sourceId) { state.programSource = null; state.programStream = null; renderProgramState(); }
        renderSourceState();
        return true;
    }

    async function ensureCameraSourceStream(sourceId) {
        const source = getCameraSource(sourceId);
        if (!source) { throw new Error(getStudioString('selectedCameraUnavailable', 'Selected camera unavailable.')); }
        if (hasLiveVideoTrack(source.stream)) { return source.stream; }
        return startCameraSource(sourceId, { force: true });
    }

    function handleCameraSourceEnded(sourceId, stream) {
        const source = getCameraSource(sourceId);
        if (!source || source.stream !== stream) { return; }
        source.stream = null;
        source.connected = false;
        source.connecting = false;
        source.status = 'disconnected';
        source.unavailable = true;
        source.error = getStudioString('cameraSourceDisconnected', 'Camera source disconnected.');
        if (source.element) { source.element.srcObject = null; }
        if (state.previewSource === sourceId) { state.previewSource = fallbackPreviewSource(sourceId); }
        if (state.programSource === sourceId) {
            state.programSource = null;
            state.programStream = null;
            const fallback = fallbackPreviewSource(sourceId);
            if (state.broadcastSession && fallback) {
                setPreviewSource(fallback)
                    .then((staged) => staged ? commitPreviewToProgram('cut') : null)
                    .catch(() => {});
            }
            renderProgramState();
        }
        setDeviceStatus(source.label + ': ' + source.error, 'warning');
        renderSourceState();
    }

    function stopAllCameraSources(options = {}) {
        state.cameraSources.forEach((source) => stopCameraSource(source.sourceId, Object.assign({}, options, { force: true, detach: Boolean(options.detach) })));
    }

    async function startPreview(updateSelection = true, options = {}) {
        const sourceId = options.sourceId || primaryCameraSourceId();
        try {
            await startCameraSource(sourceId, { force: true, retriedDefaultCamera: options.retriedDefaultCamera });
            setShellClass('is-preview-active', true);
            if (updateSelection) {
                state.previewSource = sourceId;
                renderSourceState();
                setStatus(sourceLabel(sourceId) + ' staged in Preview.', 'success');
            } else {
                setStatus(strings.previewActive, 'success');
            }
        } catch (error) {
            const source = getCameraSource(sourceId);
            const canRetryDefaultCamera = source && source.isPrimary && !options.retriedDefaultCamera && source.deviceId && ['NotReadableError', 'NotFoundError', 'DevicesNotFoundError', 'OverconstrainedError', 'ConstraintNotSatisfiedError'].includes(error && error.name);
            if (canRetryDefaultCamera) {
                source.deviceId = '';
                storageSet(CAMERA_STORAGE_KEY, '');
                setDeviceStatus(getStudioString('selectedCameraUnavailableRetry', 'The selected device is no longer available. Studio will retry with the default camera…'), 'warning');
                await populateDevices({ reason: 'camera-retry', keepDefaultCamera: true }).catch(() => {});
                return startPreview(updateSelection, { retriedDefaultCamera: true, sourceId });
            }
            const message = friendlyMediaError(error);
            setStatus(message, 'error');
            setDeviceStatus(message, 'error');
            await populateDevices({ reason: 'preview-error' }).catch(() => {});
        }
    }

    function stopPreview(options = {}) {
        const sourceId = options.sourceId || primaryCameraSourceId();
        return stopCameraSource(sourceId, options);
    }

    function isMicMeterDrivenByMixer() {
        return Boolean(els.micMeter && els.micMeter.matches('[data-mixer-meter="mic"]'));
    }

    function setupAudioMeter(stream) {
        teardownAudioMeter();
        if (isMicMeterDrivenByMixer()) {
            return;
        }
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
        if (!state.analyser || !els.micMeter || isMicMeterDrivenByMixer()) {
            return;
        }

        const data = new Uint8Array(state.analyser.frequencyBinCount);
        state.analyser.getByteFrequencyData(data);
        const average = data.reduce((sum, value) => sum + value, 0) / data.length;
        const level = Math.min(100, Math.round((average / 255) * 100)) + '%';
        els.micMeter.style.setProperty('--vh360-meter-level', level);
        els.micMeter.style.height = level;
        state.meterFrame = window.requestAnimationFrame(drawMeter);
    }

    function teardownAudioMeter() {
        if (state.meterFrame) {
            window.cancelAnimationFrame(state.meterFrame);
            state.meterFrame = null;
        }
        if (els.micMeter && !isMicMeterDrivenByMixer()) {
            els.micMeter.style.setProperty('--vh360-meter-level', '0%');
            els.micMeter.style.height = '0%';
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

        if (state.screenStream && isSourceProtected('screen')) {
            warnProtectedSource('screen');
            return;
        }

        stopScreenPreview({ force: true });

        try {
            let captureController = null;
            const displayOptions = {
                video: true,
                audio: true,
                surfaceSwitching: 'include',
                selfBrowserSurface: 'exclude',
            };
            if ('CaptureController' in window) {
                captureController = new window.CaptureController();
                displayOptions.controller = captureController;
            }
            state.screenStream = await navigator.mediaDevices.getDisplayMedia(displayOptions);
            updateProgramAudioRouting();
            if (!state.screenStream) {
                throw new Error('Screen Share could not be started.');
            }
            if (captureController && typeof captureController.setFocusBehavior === 'function') {
                try {
                    captureController.setFocusBehavior('focus-capturing-application');
                } catch (error) {}
            }
            state.screenStream.getVideoTracks().forEach((track) => {
                track.addEventListener('ended', () => handleScreenShareEnded(), { once: true });
            });
            if (els.screenPreview) {
                els.screenPreview.srcObject = state.screenStream;
                await els.screenPreview.play().catch(() => {});
            }
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

    function stopScreenPreview(options = {}) {
        if (!options.force && isSourceProtected('screen')) {
            warnProtectedSource('screen');
            return false;
        }
        stopStream(state.screenStream);
        setMixerChannelStream('screen', null);
        state.screenStream = null;
        if (els.screenPreview) {
            els.screenPreview.srcObject = null;
        }
        setShellClass('is-screen-active', false);
        if (state.previewSource === 'screen') {
            state.previewSource = fallbackPreviewSource('screen');
            renderSourceState();
        }
        if (state.programSource === 'screen') {
            state.programSource = null;
            state.programStream = null;
            renderProgramState();
        }
        return true;
    }

    async function handleScreenShareEnded() {
        setMixerChannelStream('screen', null);
        state.screenStream = null;
        if (els.screenPreview) {
            els.screenPreview.srcObject = null;
        }
        setShellClass('is-screen-active', false);
        if (state.previewSource === 'screen') {
            state.previewSource = fallbackPreviewSource('screen');
            renderSourceState();
        }
        if (state.programSource === 'screen') {
            state.programSource = null;
            state.programStream = null;
            renderProgramState();
            setStatus('Screen Share ended. Program source was cleared.', 'warning');
            const fallbackCamera = primaryCameraSource();
            if (state.broadcastSession && fallbackCamera) {
                try {
                    const staged = await setPreviewSource(fallbackCamera.sourceId);
                    if (staged) {
                        await commitPreviewToProgram('cut');
                        setBroadcastStatus('Screen Share ended. Program fell back to Camera.', 'warning');
                    } else {
                        setBroadcastStatus('Screen Share ended. Waiting for the selected Preview source before changing Program.', 'warning');
                    }
                } catch (error) {
                    setBroadcastStatus('Screen Share ended. Choose another source for Program.', 'warning');
                    setDeviceStatus(friendlyMediaError(error), 'warning');
                }
            } else if (state.broadcastSession) {
                setBroadcastStatus('Screen Share ended. Choose another source for Program.', 'warning');
            }
        }
    }

    function createMediaElement(source) {
        if (source.type === 'image') {
            const image = document.createElement('img');
            image.alt = source.name || '';
            image.addEventListener('load', renderPreviewMediaTransform);
            image.src = source.url;
            return image;
        }

        const video = document.createElement('video');
        video.playsInline = true;
        video.muted = true;
        video.volume = 1;
        video.loop = true;
        video.controls = false;
        video.preload = 'metadata';
        video.src = source.url;
        video.addEventListener('loadedmetadata', updateProgramAudioRouting, { once: true });
        video.addEventListener('play', updateProgramAudioRouting);
        ['play', 'pause', 'timeupdate', 'loadedmetadata', 'durationchange', 'ended'].forEach((eventName) => {
            video.addEventListener(eventName, renderMediaPlaybackControls);
        });
        ['loadedmetadata', 'durationchange'].forEach((eventName) => {
            video.addEventListener(eventName, renderPreviewMediaTransform);
        });
        return video;
    }

    function filenameWithoutExtension(filename) {
        return String(filename || '').replace(/\.[^/.]+$/, '').replace(/[-_]+/g, ' ').trim();
    }

    function resetMediaSourceModal() {
        if (els.persistentMediaSourceInput) {
            els.persistentMediaSourceInput.value = '';
        }
        if (els.persistentMediaSourceName) {
            els.persistentMediaSourceName.value = '';
        }
        setMediaSourceModalStatus('', 'info');
    }

    function setMediaSourceModalStatus(message, type) {
        if (!els.persistentMediaSourceStatus) {
            return;
        }
        els.persistentMediaSourceStatus.textContent = message;
        els.persistentMediaSourceStatus.dataset.statusType = type || 'info';
        els.persistentMediaSourceStatus.hidden = !message;
    }

    function toggleSourceMenu() {
        if (!els.sourceMenu || !els.sourceMenuToggle) {
            return;
        }

        const open = els.sourceMenu.hidden;
        els.sourceMenu.hidden = !open;
        els.sourceMenuToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    function closeSourceMenu() {
        if (els.sourceMenu) {
            els.sourceMenu.hidden = true;
        }

        if (els.sourceMenuToggle) {
            els.sourceMenuToggle.setAttribute('aria-expanded', 'false');
        }
    }

    function nextCameraSourceName() {
        return getStudioString('cameraSourceDefaultName', 'Camera {number}').replace('{number}', state.cameraSources.size + 1);
    }

    function firstUnassignedCameraDevice() {
        return (state.availableVideoInputDevices || []).find((device) => device.deviceId && !isCameraDeviceAssigned(device.deviceId)) || null;
    }

    function setCameraSourceModalStatus(message, type) {
        if (!els.cameraSourceStatus) { return; }
        els.cameraSourceStatus.textContent = message || '';
        els.cameraSourceStatus.dataset.statusType = type || 'info';
        els.cameraSourceStatus.hidden = !message;
    }

    function renderCameraSourceDialog() {
        if (!els.cameraSourceDevice) { return; }
        const devices = state.availableVideoInputDevices || [];
        fillDeviceSelect(els.cameraSourceDevice, devices, getStudioString('cameraSummaryLabel', 'Camera'), (firstUnassignedCameraDevice() || {}).deviceId || '');
        Array.from(els.cameraSourceDevice.options).forEach((option) => {
            if (option.value && isCameraDeviceAssigned(option.value)) {
                option.disabled = true;
                option.textContent += ' — ' + getStudioString('deviceAlreadyAssigned', 'already assigned');
            }
        });
        const available = firstUnassignedCameraDevice();
        if (available) { els.cameraSourceDevice.value = available.deviceId; }
        if (els.addCameraSource) { els.addCameraSource.disabled = !available; }
        setCameraSourceModalStatus(
            available ? '' : getStudioString('noUnassignedCamerasAvailable', 'No unassigned cameras available.'),
            available ? 'info' : 'warning'
        );
    }

    function openCameraSourceModal() {
        if (!els.cameraSourceModal) { return; }
        state.cameraSourceModalTrigger = document.activeElement && document.activeElement.focus ? document.activeElement : null;
        setCameraSourceModalStatus('', 'info');
        renderCameraSourceDialog();
        if (els.cameraSourceName) { els.cameraSourceName.value = nextCameraSourceName(); }
        els.cameraSourceModal.hidden = false;
        if (els.cameraSourceDevice && !els.cameraSourceDevice.disabled) { els.cameraSourceDevice.focus(); }
    }

    function closeCameraSourceModal() {
        if (els.cameraSourceModal) { els.cameraSourceModal.hidden = true; }
        if (state.cameraSourceModalTrigger && typeof state.cameraSourceModalTrigger.focus === 'function') {
            state.cameraSourceModalTrigger.focus();
        }
        state.cameraSourceModalTrigger = null;
    }

    function addCameraSourceFromDialog() {
        const deviceId = els.cameraSourceDevice ? els.cameraSourceDevice.value : '';
        if (!deviceId || isCameraDeviceAssigned(deviceId)) {
            setCameraSourceModalStatus(getStudioString('deviceAlreadyAssigned', 'Device already assigned.'), 'warning');
            return;
        }
        const device = (state.availableVideoInputDevices || []).find((item) => item.deviceId === deviceId);
        const source = createCameraSource({
            label: (els.cameraSourceName && els.cameraSourceName.value.trim()) || nextCameraSourceName(),
            deviceId,
            deviceLabel: device ? device.label : '',
            isPrimary: false,
        });
        saveCameraSourceConfiguration();
        closeCameraSourceModal();
        renderCameraScenes();
        selectSceneSource(source.sourceId);
        setStatus(getStudioString('cameraSourceAdded', 'Camera source added.'), 'success');
    }

    function openMediaSourceModal(mode) {
        state.mediaSourceModalMode = mode === 'local' ? 'local' : 'upload';
        state.mediaSourceModalTrigger = document.activeElement && document.activeElement.focus ? document.activeElement : null;
        resetMediaSourceModal();

        if (els.mediaSourceModalTitle) {
            els.mediaSourceModalTitle.textContent = state.mediaSourceModalMode === 'local' ? 'Add Local Media' : 'Upload to Studio';
        }

        if (els.mediaSourceModalHelp) {
            els.mediaSourceModalHelp.textContent = state.mediaSourceModalMode === 'local'
                ? 'Local media is available for this Studio session only and is not uploaded to your media library.'
                : 'Uploaded media will be saved to Studio and remain available until you delete it.';
        }

        if (els.importMediaSource) {
            els.importMediaSource.textContent = state.mediaSourceModalMode === 'local' ? 'Add Local Media' : 'Upload to Studio';
        }

        if (els.mediaSourceModal) {
            els.mediaSourceModal.hidden = false;
        }

        if (els.persistentMediaSourceInput) {
            els.persistentMediaSourceInput.focus();
        }
    }

    function closeMediaSourceModal() {
        if (!els.mediaSourceModal) {
            return;
        }
        if (state.mediaSourceUploadActive) {
            setMediaSourceModalStatus('Media upload is still running. Wait for it to finish before closing.', 'warning');
            return;
        }
        els.mediaSourceModal.hidden = true;
        resetMediaSourceModal();
        if (state.mediaSourceModalTrigger && typeof state.mediaSourceModalTrigger.focus === 'function') {
            state.mediaSourceModalTrigger.focus();
        }
        state.mediaSourceModalTrigger = null;
    }

    function handlePersistentMediaFileSelected() {
        const file = els.persistentMediaSourceInput && els.persistentMediaSourceInput.files && els.persistentMediaSourceInput.files[0];
        if (!file) {
            return;
        }

        if (!file.type || file.type.indexOf('image/') !== 0 && file.type.indexOf('video/') !== 0) {
            setMediaSourceModalStatus('Choose an image or video file.', 'error');
            return;
        }

        if (els.persistentMediaSourceName && !els.persistentMediaSourceName.value.trim()) {
            els.persistentMediaSourceName.value = filenameWithoutExtension(file.name);
        }
        setMediaSourceModalStatus('', 'info');
    }

    async function importSelectedMediaSource() {
        if (state.mediaSourceModalMode === 'local') {
            await importLocalMediaSource();
            return;
        }

        await importPersistentMediaSource();
    }

    async function importLocalMediaSource() {
        const file = els.persistentMediaSourceInput && els.persistentMediaSourceInput.files && els.persistentMediaSourceInput.files[0];
        const displayName = els.persistentMediaSourceName ? els.persistentMediaSourceName.value.trim() : '';

        if (!file) {
            setMediaSourceModalStatus('Choose an image or video file.', 'error');
            return;
        }

        if (!file.type || file.type.indexOf('image/') !== 0 && file.type.indexOf('video/') !== 0) {
            setMediaSourceModalStatus('Choose an image or video file.', 'error');
            return;
        }

        if (!displayName) {
            setMediaSourceModalStatus('Enter a display name before adding this source.', 'error');
            return;
        }

        if (els.importMediaSource) {
            els.importMediaSource.disabled = true;
        }
        state.mediaSourceUploadActive = true;

        try {
            const type = file.type.indexOf('image/') === 0 ? 'image' : 'video';
            const id = 'local-' + (++state.localMediaSourceCounter);
            const url = URL.createObjectURL(file);
            const source = {
                id,
                attachmentId: null,
                sourceId: 'media:' + id,
                type,
                name: displayName,
                url,
                mime: file.type,
                filename: file.name || '',
                element: null,
                persistent: false,
                local: true,
                inScenes: false,
                transform: defaultMediaTransform(),
            };

            source.element = createMediaElement(source);
            state.mediaSources.set(id, source);
            addMediaSourceToScenes(id);

            state.mediaSourceUploadActive = false;
            closeMediaSourceModal();
            selectSceneSource(source.sourceId);
            await setPreviewSource(source.sourceId);
            setStatus('Local media added for this Studio session.', 'success');
        } catch (error) {
            setMediaSourceModalStatus((error && error.message) || 'Local media could not be added.', 'error');
        } finally {
            if (els.importMediaSource) {
                els.importMediaSource.disabled = false;
            }
            state.mediaSourceUploadActive = false;
        }
    }

    async function importPersistentMediaSource() {
        const file = els.persistentMediaSourceInput && els.persistentMediaSourceInput.files && els.persistentMediaSourceInput.files[0];
        const displayName = els.persistentMediaSourceName ? els.persistentMediaSourceName.value.trim() : '';

        if (!file) {
            setMediaSourceModalStatus('Choose an image or video file.', 'error');
            return;
        }

        if (!file.type || file.type.indexOf('image/') !== 0 && file.type.indexOf('video/') !== 0) {
            setMediaSourceModalStatus('Choose an image or video file.', 'error');
            return;
        }

        if (!displayName) {
            setMediaSourceModalStatus('Enter a display name before adding this source.', 'error');
            return;
        }

        const formData = new FormData();
        formData.append('file', file);
        formData.append('display_name', displayName);

        if (els.importMediaSource) {
            els.importMediaSource.disabled = true;
        }
        state.mediaSourceUploadActive = true;
        setMediaSourceModalStatus('Adding media source…', 'info');

        try {
            const response = await api('/media-sources', { method: 'POST', body: formData });
            const source = registerPersistentMediaSource(response.source);
            renderSourceState();
            renderSceneControls();
            state.mediaSourceUploadActive = false;
            closeMediaSourceModal();
            if (source) {
                selectSceneSource(source.sourceId);
                await setPreviewSource(source.sourceId);
            }
            setStatus('Media source added to Studio.', 'success');
        } catch (error) {
            setMediaSourceModalStatus((error && error.message) || 'Media source could not be added.', 'error');
        } finally {
            if (els.importMediaSource) {
                els.importMediaSource.disabled = false;
            }
            state.mediaSourceUploadActive = false;
        }
    }

    async function loadPersistentMediaSources() {
        try {
            const response = await api('/media-sources');
            (response.sources || []).forEach(registerPersistentMediaSource);
            renderSourceState();
            renderSceneControls();
        } catch (error) {
            setStatus((error && error.message) || 'Studio media sources could not be loaded.', 'warning');
        }
    }

    function registerPersistentMediaSource(payload) {
        if (!payload || !payload.id || !payload.url) {
            return null;
        }

        const id = String(payload.id);
        const source = {
            id,
            attachmentId: Number(payload.id),
            sourceId: payload.sourceId || 'media:' + id,
            type: payload.type === 'image' ? 'image' : 'video',
            name: payload.name || 'Studio Media Source',
            url: payload.url,
            mime: payload.mime || '',
            filename: payload.filename || '',
            element: null,
            persistent: true,
            inScenes: false,
            transform: defaultMediaTransform(),
        };
        source.element = createMediaElement(source);
        state.mediaSources.set(id, source);
        addMediaSourceToScenes(id);
        return source;
    }

    function addMediaSourceToScenes(id) {
        const source = state.mediaSources.get(String(id));
        if (!source || !els.sceneList || source.inScenes) {
            return;
        }

        const item = document.createElement('li');
        const button = document.createElement('button');
        item.dataset.mediaSceneId = source.id;
        button.type = 'button';
        button.dataset.sceneSource = source.sourceId;
        button.textContent = source.name;
        item.appendChild(button);
        els.sceneList.appendChild(item);
        source.inScenes = true;
    }

    function removeMediaScene(id) {
        if (!els.sceneList) {
            return;
        }

        const scene = els.sceneList.querySelector('[data-media-scene-id="' + id + '"]');
        if (scene) {
            scene.remove();
        }
    }

    function releaseMediaSource(source) {
        if (source && source.sourceId) {
            disconnectMixerChannel('media', source.sourceId);
        }
        if (source.type === 'video' && source.element) {
            source.element.pause();
        }

        if (source.element) {
            source.element.removeAttribute('src');
            if (typeof source.element.load === 'function') {
                source.element.load();
            }
        }

        if (source.url && !source.persistent) {
            URL.revokeObjectURL(source.url);
        }
    }

    function clearLocalMediaSources() {
        state.mediaSources.forEach((source) => {
            releaseMediaSource(source);
        });

        state.mediaSources.clear();
        state.selectedSceneSource = '';

        if (els.sceneList) {
            els.sceneList.querySelectorAll('[data-media-scene-id]').forEach((scene) => {
                scene.remove();
            });
        }

        if (els.mediaPreview) {
            els.mediaPreview.innerHTML = '';
        }

        renderSourceState();
        renderSceneControls();
    }

    function removeMediaSceneButton(sourceId) {
        if (!els.sceneList) {
            return;
        }

        const mediaId = mediaIdFromSourceId(sourceId);
        els.sceneList.querySelectorAll('[data-media-scene-id]').forEach((item) => {
            if (item.dataset.mediaSceneId === mediaId) {
                item.remove();
            }
        });
    }

    async function deletePersistentMediaSource(sourceId) {
        const source = getMediaSource(sourceId);
        if (!source) {
            return;
        }

        await api('/media-sources/' + encodeURIComponent(source.attachmentId || source.id), { method: 'DELETE' });

        if (state.selectedSceneSource === source.sourceId) {
            state.selectedSceneSource = '';
        }
        if (state.previewSource === source.sourceId) {
            state.previewSource = null;
        }
        if (state.programSource === source.sourceId) {
            state.programSource = null;
            state.programStream = null;
        }

        removeMediaSceneButton(source.sourceId);
        releaseMediaSource(source);
        state.mediaSources.delete(String(source.id));
        if (els.mediaPreview) {
            els.mediaPreview.innerHTML = '';
        }
        renderProgramState();
        renderSourceState();
        renderSceneControls();
        setStatus('Media source deleted from Studio.', 'success');
    }

    async function deleteMediaSource(sourceId) {
        const source = getMediaSource(sourceId);

        if (!source) {
            return;
        }

        if (source.persistent) {
            await deletePersistentMediaSource(sourceId);
        } else {
            deleteLocalMediaSource(sourceId);
        }
    }

    function deleteLocalMediaSource(sourceId) {
        const source = getMediaSource(sourceId);

        if (!source) {
            return;
        }

        if (state.selectedSceneSource === source.sourceId) {
            state.selectedSceneSource = '';
        }

        if (state.previewSource === source.sourceId) {
            state.previewSource = null;
        }

        if (state.programSource === source.sourceId) {
            state.programSource = null;
            state.programStream = null;
        }

        removeMediaSceneButton(source.sourceId);
        releaseMediaSource(source);
        state.mediaSources.delete(String(source.id));

        if (els.mediaPreview) {
            els.mediaPreview.innerHTML = '';
        }

        renderProgramState();
        renderSourceState();
        renderSceneControls();
        setStatus('Local media removed from this Studio session.', 'success');
    }

    async function deleteSelectedSourceScene() {
        const cameraSource = getSelectedCameraSource();
        if (cameraSource) {
            if (cameraSource.isPrimary) {
                setStatus(getStudioString('primaryCameraCannotBeRemoved', 'Primary camera cannot be removed.'), 'warning');
                return;
            }
            if (isSourceProtected(cameraSource.sourceId)) {
                warnProtectedSource(cameraSource.sourceId);
                return;
            }
            if (!window.confirm(getStudioString('removeCameraSourceConfirm', 'Remove this video capture device source?'))) {
                return;
            }
            cameraSource.removed = true;
            stopCameraSource(cameraSource.sourceId, { force: true, detach: true });
            state.cameraSources.delete(cameraSource.id);
            if (state.selectedSceneSource === cameraSource.sourceId) { state.selectedSceneSource = ''; }
            if (state.previewSource === cameraSource.sourceId) { state.previewSource = null; }
            scheduleCameraSourceConfigurationSave();
            renderCameraScenes();
            renderSourceState();
            setStatus(getStudioString('cameraSourceRemoved', 'Camera source removed.'), 'success');
            return;
        }
        const mediaSource = getSelectedMediaSource();

        if (!mediaSource) {
            setStatus('Select a removable source before deleting.', 'warning');
            renderSceneControls();
            return;
        }

        if (isSourceProtected(mediaSource.sourceId)) {
            const message = 'This source is currently in Program. Send another source to Program before removing it.';
            setStatus(message, 'warning');

            if (state.broadcastSession) {
                setBroadcastStatus(message, 'warning');
            }

            return;
        }

        const confirmed = window.confirm(
            mediaSource.persistent
                ? 'Delete this uploaded media source from Studio?'
                : 'Remove this local media source from this Studio session?'
        );

        if (!confirmed) {
            return;
        }

        try {
            await deleteMediaSource(mediaSource.sourceId);
        } catch (error) {
            setStatus((error && error.message) || 'Media source could not be deleted.', 'error');
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
            return getStudioString('cameraMicrophonePermissionBlocked', 'Camera or microphone permission was blocked. Allow access in your browser site settings and macOS Privacy & Security, then refresh devices.');
        }
        if (error.name === 'NotFoundError' || error.name === 'DevicesNotFoundError') {
            return getStudioString('noMatchingCameraMicrophone', 'No matching camera or microphone was found. Check the USB connection and click Refresh Devices.');
        }
        if (error.name === 'NotReadableError') {
            return getStudioString('cameraMicrophoneAlreadyInUse', 'The camera or microphone is already in use by another app. Close OBSBOT Center, Zoom, FaceTime, OBS, or other camera apps, then try again.');
        }
        if (error.name === 'OverconstrainedError' || error.name === 'ConstraintNotSatisfiedError') {
            return getStudioString('selectedDeviceUnavailableRetryDefault', 'The selected device is no longer available. Studio will retry with the default device.');
        }
        return error.message || strings.browserUnsupported;
    }

    function updateQualityDetails() {
        if (!els.qualityDetails || !els.qualitySelect) {
            return;
        }
        const preset = getSelectedPreset();
        const resolution = preset.resolution ? preset.resolution.width + '×' + preset.resolution.height : '';
        const bitrate = preset.video_bitrate ? ' · ~' + Math.round(Number(preset.video_bitrate) / 1000000 * 10) / 10 + ' Mbps video' : '';
        els.qualityDetails.textContent = preset.label + ' · ' + resolution + ' · ' + preset.fps + 'fps' + bitrate + '. Higher quality creates larger files and longer uploads.';
    }



    function applyProgramCanvasResolution(options = {}) {
        const resolution = getRecordingOutputSize();
        const activeOutput = state.broadcastSession || isRecordingActive();

        if ((activeOutput || isRecordingCanvasLocked()) && !options.force) {
            return;
        }

        state.programWidth = resolution.width;
        state.programHeight = resolution.height;
        state.programFrameRate = resolution.fps;

        renderPreviewMediaTransform();

        if (state.programCanvas) {
            state.programCanvas.width = state.programWidth;
            state.programCanvas.height = state.programHeight;
        }

        if (!activeOutput && state.programOutputStream) {
            stopProgramCompositor({ stopTracks: true, clearStream: true });
            ensureProgramCompositor();
        }

        dispatchProgramResolutionChange();
        renderSelectedMediaControls();
    }

    function getSelectedPresetKey() {
        const presets = config.qualityPresets || {};
        const selected = els.qualitySelect ? els.qualitySelect.value : config.defaultQualityPreset;
        return presets[selected] ? selected : (presets[config.defaultQualityPreset] ? config.defaultQualityPreset : Object.keys(presets)[0]);
    }

    function getSelectedPreset() {
        const presets = config.qualityPresets || {};
        return presets[getSelectedPresetKey()] || {};
    }

    function buildVideoConstraints(preset, deviceId) {
        const video = deviceId ? { deviceId: { exact: deviceId } } : {};
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

    function updateRecordingOperationStatus(message, type = 'info') {
        const warnings = Array.isArray(state.recordingPersistentWarnings) ? state.recordingPersistentWarnings.filter(Boolean) : [];
        setRecordingStatus([warnings.join(' '), message].filter(Boolean).join(' '), warnings.length ? 'warning' : type);
    }

    function recordingMimeCandidates() {
        return [
            'video/mp4;codecs="avc3.640028,mp4a.40.2"',
            'video/mp4;codecs="avc3.42E01E,mp4a.40.2"',
            'video/mp4;codecs="avc1.424028,mp4a.40.2"',
            'video/mp4;codecs="avc1.42E01E,mp4a.40.2"',
            'video/mp4;codecs="avc1.640028,mp4a.40.2"',
            'video/mp4',
            'video/webm;codecs=vp9,opus',
            'video/webm;codecs=vp8,opus',
            'video/webm',
        ];
    }

    function preferredMimeType() {
        const supported = state.support.mimeTypes || [];
        return supported[0] || '';
    }

    function isMp4Mime(mimeType) {
        return String(mimeType || '').toLowerCase().indexOf('video/mp4') !== -1;
    }

    function isWebmMime(mimeType) {
        return String(mimeType || '').toLowerCase().indexOf('video/webm') !== -1;
    }

    function recordingExtension(mimeType) {
        return isMp4Mime(mimeType) ? '.mp4' : '.webm';
    }

    function recordingFormatLabel(mimeType) {
        const selected = mimeType || preferredMimeType();
        if (isMp4Mime(selected)) {
            return 'Recording format: MP4';
        }
        if (isWebmMime(selected)) {
            return 'Recording format: WebM fallback';
        }
        return 'Recording format: Not supported';
    }

    function webmFallbackWarning() {
        return 'This browser does not support MP4 recording. Studio will use WebM fallback, which may not play in all Safari versions.';
    }

    async function api(path, options) {
        const response = await window.fetch(config.restRoot.replace(/\/$/, '') + path, Object.assign({ credentials: 'same-origin', headers: { 'X-WP-Nonce': config.nonce } }, options || {}));
        const contentType = response.headers ? response.headers.get('content-type') || '' : '';
        let payload = null;
        let parseFailed = false;
        if (contentType.indexOf('application/json') !== -1) {
            try {
                payload = await response.json();
            } catch (error) {
                parseFailed = true;
            }
        } else {
            const text = await response.text().catch(() => '');
            parseFailed = Boolean(text);
        }
        if (!response.ok || parseFailed) {
            const fallback = 'Studio request failed for ' + path + '. HTTP ' + response.status + '.';
            const message = payload && payload.message ? payload.message : fallback;
            state.lastRestError = message;
            updateOperatorStatus();
            throw new Error(message);
        }
        state.lastRestError = '';
        updateOperatorStatus();
        return payload || {};
    }

    async function ensureSetupJob() {
        if (state.activeJobId) {
            return state.activeJobId;
        }
        const job = await api('/jobs', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': config.nonce }, body: JSON.stringify({ recording_mode: 'browser', source_type: 'studio_setup', source_id: 'studio-recording-' + Date.now(), quality_preset: getSelectedPresetKey() }) });
        state.activeJobId = job.id;
        state.currentStorageProvider = job.storage_provider || '';
        return job.id;
    }

    function isRecordingActive() {
        return state.recorder && state.recorder.state === 'recording';
    }

    function isTerminalJobStatus(status) {
        return ['ready', 'failed', 'cancelled'].indexOf(String(status || '').toLowerCase()) !== -1;
    }

    function hasUnsafeRecordingWork() {
        const jobStatus = String(state.currentJobStatus || '').toLowerCase();
        const hasPendingUploadWork = Boolean(state.pendingUploads.size || state.failedChunks.size);
        const hasDirectUploadMemory = Boolean(state.activeJobId && state.directUploadParts.size && !isTerminalJobStatus(jobStatus));

        return Boolean(
            isRecordingActive() ||
            state.finalizeInProgress ||
            state.directUploadInProgress ||
            ['stopping', 'uploading'].indexOf(jobStatus) !== -1 ||
            (hasPendingUploadWork && !isTerminalJobStatus(jobStatus)) ||
            hasDirectUploadMemory
        );
    }

    function isPublitioDirectMode() {
        const direct = config.publitioDirectUpload || {};
        return Boolean(
            state.currentStorageProvider === 'publitio' &&
            direct.enabled &&
            direct.upload_mode === 'direct_browser' &&
            direct.upload_preset_id
        );
    }

    async function startRecording() {
        const supportError = requiredSupportError('recording');
        if (supportError) {
            setRecordingStatus(supportError, 'error');
            setStatus(supportError, 'error');
            return;
        }
        if (!state.support.mediaRecorder || !preferredMimeType()) {
            setRecordingStatus(strings.recorderUnavailable, 'error');
            return;
        }
        let jobId = null;
        let serverRecordingStarted = false;
        state.recordingPersistentWarnings = [];
        try {
            const preset = getSelectedPreset();
            lockRecordingCanvasSize();
            if (!state.programSource) {
                if (state.previewSource) {
                    state.programSource = state.previewSource;
                } else {
                    const staged = await setPreviewSource(primaryCameraSourceId());
                    if (staged) {
                        state.programSource = state.previewSource;
                    }
                }
            }
            if (isCameraSource(state.programSource)) {
                state.programStream = await getSourceStream(state.programSource);
            } else if (state.programSource === 'screen') {
                state.programStream = await getSourceStream('screen');
            } else if (isMediaSource(state.programSource)) {
                const mediaSource = getMediaSource(state.programSource);
                if (mediaSource && mediaSource.type === 'video') {
                    await mediaSource.element.play().catch(() => {});
                }
                state.programStream = null;
            }
            if (!hasProgramOutput()) {
                throw new Error(getStudioString('chooseProgramSourceBeforeRecording', 'Choose a Program source before recording.'));
            }
            renderProgramState();
            applyProgramCanvasResolution({ force: true });
            ensureProgramCompositor();
            state.recordingStream = await buildRecordingStreamFromProgram();
            if (!state.recordingStream) {
                const staged = await setPreviewSource(primaryCameraSourceId());
                if (staged) {
                    state.programSource = state.previewSource;
                    state.programStream = await getSourceStream(state.programSource);
                    if (!hasProgramOutput()) {
                        throw new Error(getStudioString('chooseProgramSourceBeforeRecording', 'Choose a Program source before recording.'));
                    }
                    renderProgramState();
                    ensureProgramCompositor();
                    state.recordingStream = await buildRecordingStreamFromProgram();
                }
            }
            if (!state.recordingStream) {
                throw new Error(getStudioString('chooseProgramSourceBeforeRecording', 'Choose a Program source before recording.'));
            }
            jobId = await ensureSetupJob();
            const mimeType = preferredMimeType();
            state.chunkIndex = 0;
            state.stopFailed = false;
            state.serverStopConfirmed = false;
            state.serverStopConfirming = false;
            state.serverStopPromise = null;
            state.uploadedChunks.clear();
            state.failedChunks.clear();
            state.pendingUploads.clear();
            state.uploadQueue.length = 0;
            state.activeUploadCount = 0;
            state.directUploadParts.clear();
            state.directUploadBytes = 0;
            state.directUploadAvailable = true;
            state.recordingStartedAt = Date.now();
            state.recordingStoppedAt = null;
            state.recordingDurationSeconds = 0;
            state.recordingStopPromise = null;
            state.recordingStopRequested = false;
            state.finalChunkCount = 0;
            const options = { mimeType: mimeType };
            if (preset.video_bitrate) { options.videoBitsPerSecond = Number(preset.video_bitrate); }
            if (preset.audio_bitrate) { options.audioBitsPerSecond = Number(preset.audio_bitrate); }
            state.recorder = new window.MediaRecorder(state.recordingStream, options);
            state.selectedMimeType = state.recorder.mimeType || mimeType;
            const start = await api('/jobs/' + jobId + '/recording/start', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': config.nonce }, body: JSON.stringify({ mime_type: state.selectedMimeType }) });
            serverRecordingStarted = true;
            state.browserSessionId = start.browser_session_id;
            state.selectedMimeType = start.mime_type || state.selectedMimeType;
            state.recorder.addEventListener('dataavailable', (event) => {
                if (event.data && event.data.size) {
                    const finalMimeType = event.data.type || (state.recorder && state.recorder.mimeType) || state.selectedMimeType;
                    const chunk = event.data.type === finalMimeType ? event.data : new Blob([event.data], { type: finalMimeType });
                    handleRecordingBlob(chunk);
                }
            });
            state.recorder.start((config.uploadSettings && config.uploadSettings.preferred_chunk_duration) || 5000);
            startTimer();
            setRecorderButtons(true, false);
            setShellClass('is-recording', true);
            const recordingAudioSummary = state.lastRecordingAudioSummary;
            const audioWarning = recordingAudioSummary && (recordingAudioSummary.active === 0 || recordingAudioSummary.failed > 0)
                ? formatAudioInputSummary(recordingAudioSummary, 'recording')
                : '';
            const webmWarning = isWebmMime(state.selectedMimeType) ? webmFallbackWarning() : '';
            state.recordingPersistentWarnings = [audioWarning, webmWarning].filter(Boolean);
            if (audioWarning || webmWarning) {
                updateRecordingOperationStatus('', 'warning');
            } else {
                updateRecordingOperationStatus(strings.recordingActive, 'success');
            }
            renderRecordingState();
        } catch (error) {
            if (serverRecordingStarted && jobId) {
                await api('/jobs/' + jobId + '/cancel', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': config.nonce } }).catch(() => {});
                state.activeJobId = null;
                state.browserSessionId = '';
            }
            state.recorder = null;
            unlockRecordingCanvasSize();
            setRecorderButtons(false, false);
            setRecordingStatus(error.message || strings.recorderUnavailable, 'error');
        }
    }


    function maxUploadChunkSize() {
        const configured = Number(config.uploadSettings && config.uploadSettings.max_chunk_size);
        return configured > 0 ? configured : 8 * 1024 * 1024;
    }

    function safeUploadChunkSize() {
        return Math.max(1024 * 1024, maxUploadChunkSize() - (256 * 1024));
    }

    function queueRecordingBlob(blob) {
        const maxSize = safeUploadChunkSize();
        if (!blob || !blob.size) {
            return;
        }
        if (blob.size <= maxSize) {
            queueChunk(blob, state.chunkIndex++);
            return;
        }
        let offset = 0;
        while (offset < blob.size) {
            const end = Math.min(offset + maxSize, blob.size);
            const part = blob.slice(offset, end, blob.type || state.actualMimeType || state.selectedMimeType);
            queueChunk(part, state.chunkIndex++);
            offset = end;
        }
        updateRecordingOperationStatus(getStudioString('largeRecordingSplit', 'Large recording data was split into smaller upload chunks.'), 'info');
    }

    function scheduleChunkUploads() {
        if (!state.activeJobId) {
            return;
        }

        while (state.activeUploadCount < CHUNK_UPLOAD_CONCURRENCY && state.uploadQueue.length) {
            const task = state.uploadQueue.shift();
            if (!task || !state.pendingUploads.has(task.index)) {
                continue;
            }

            state.activeUploadCount += 1;
            Promise.resolve(uploadChunk(task.blob, task.index)).catch(() => {}).finally(() => {
                state.activeUploadCount = Math.max(0, state.activeUploadCount - 1);
                scheduleChunkUploads();
            });
        }
    }

    function handleRecordingBlob(blob) {
        if (isPublitioDirectMode()) {
            rememberDirectUploadPart(blob, state.chunkIndex++);
            renderRecordingState();
            return;
        }

        queueRecordingBlob(blob);
    }

    function queueChunk(blob, index) {
        state.pendingUploads.add(index);
        state.uploadQueue.push({ blob: blob, index: index });
        scheduleChunkUploads();
        renderRecordingState();
    }

    function rememberDirectUploadPart(blob, index) {
        const direct = config.publitioDirectUpload || {};
        if (!isPublitioDirectMode() || !state.directUploadAvailable || !blob || !blob.size) {
            return;
        }
        const maxSize = Number(direct.max_size || 0);
        if (maxSize > 0 && state.directUploadBytes + blob.size > maxSize) {
            state.directUploadAvailable = false;
            state.directUploadParts.clear();
            state.directUploadBytes = 0;
            setRecordingStatus('Fast cloud upload limit was exceeded. Use server relay mode for very long recordings.', 'warning');
            return;
        }
        state.directUploadParts.set(Number(index), blob);
        state.directUploadBytes += blob.size;
    }


    async function sha256Blob(blob) {
        if (!window.crypto || !window.crypto.subtle || !blob || typeof blob.arrayBuffer !== 'function') {
            return '';
        }
        const digest = await window.crypto.subtle.digest('SHA-256', await blob.arrayBuffer());
        return Array.from(new Uint8Array(digest)).map((byte) => byte.toString(16).padStart(2, '0')).join('');
    }

    async function uploadChunk(blob, index) {
        try {
            updateRecordingOperationStatus(state.recordingStoppedAt ? strings.uploadingChunk : strings.recordingActive, 'info');
            const form = new FormData();
            form.append('browser_session_id', state.browserSessionId);
            form.append('chunk_index', String(index));
            const finalMimeType = blob.type || state.selectedMimeType;
            form.append('mime_type', finalMimeType);
            const checksum = await sha256Blob(blob);
            if (checksum) { form.append('chunk_checksum', checksum); }
            form.append('chunk', blob, 'chunk-' + index + recordingExtension(finalMimeType));
            const summary = await api('/jobs/' + state.activeJobId + '/chunks', { method: 'POST', body: form });
            state.pendingUploads.delete(index);
            state.failedChunks.delete(index);
            (summary.received_chunk_indexes || []).forEach((item) => state.uploadedChunks.add(Number(item)));
            if (els.recordingBytes) { els.recordingBytes.textContent = String(summary.received_bytes || 0); }
        } catch (error) {
            state.pendingUploads.delete(index);
            state.failedChunks.set(index, blob);
            setRecordingStatus((error && error.message) || strings.chunkUploadFailed, 'error');
        }
        renderRecordingState();
        if (state.recordingStoppedAt && !state.pendingUploads.size && !state.failedChunks.size && !state.serverStopConfirmed && !state.serverStopConfirming) {
            finishRecordingUploadsAndConfirmStop().catch(() => {});
        }
    }

    async function confirmServerStop() {
        if (!state.activeJobId) {
            return null;
        }
        if (state.serverStopPromise) {
            return state.serverStopPromise;
        }
        state.serverStopConfirming = true;
        state.serverStopPromise = (async () => {
            const duration = Math.max(0, Math.round(state.recordingDurationSeconds || 0));
            const job = await api('/jobs/' + state.activeJobId + '/recording/stop', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': config.nonce }, body: JSON.stringify({ duration_seconds: duration }) });
            state.serverStopConfirmed = true;
            state.stopFailed = false;
            state.currentJobStatus = job.status || 'stopping';
            return job;
        })();
        try {
            return await state.serverStopPromise;
        } finally {
            state.serverStopConfirming = false;
            state.serverStopPromise = null;
        }
    }

    async function retryServerStop() {
        if (!state.activeJobId || !state.stopFailed) {
            return;
        }
        try {
            updateRecordingOperationStatus(getStudioString('retryingServerStopConfirmation', 'Retrying server stop confirmation…'), 'info');
            await finishRecordingUploadsAndConfirmStop();
        } catch (error) {
            state.stopFailed = true;
            state.serverStopConfirmed = false;
            state.currentJobStatus = 'stop_failed';
            setRecordingStatus((error && error.message) || 'Server stop confirmation failed. Retry stop before preparing the replay.', 'error');
        } finally {
            setShellClass('is-recording', false);
            setRecorderButtons(false, false);
            renderRecordingState();
            updateOperatorStatus();
        }
    }

    function freezeRecordingDuration() {
        if (!state.recordingStartedAt) {
            state.recordingDurationSeconds = 0;
            return;
        }
        state.recordingStoppedAt = state.recordingStoppedAt || Date.now();
        state.recordingDurationSeconds = Math.max(0, Math.round((state.recordingStoppedAt - state.recordingStartedAt) / 1000));
        stopTimer();
    }

    async function stopLocalRecording() {
        if (state.recordingStopPromise) {
            return state.recordingStopPromise;
        }
        if (!state.recorder) {
            if (state.recordingStartedAt && !state.recordingStoppedAt) {
                freezeRecordingDuration();
            }
            return null;
        }
        const recorder = state.recorder;
        state.recordingStopRequested = true;
        state.recordingStopPromise = (async () => {
            if (recorder.state !== 'inactive') {
                await new Promise((resolve, reject) => {
                    const timeout = window.setTimeout(resolve, 3000);
                    recorder.addEventListener('stop', () => {
                        window.clearTimeout(timeout);
                        resolve();
                    }, { once: true });
                    try {
                        recorder.stop();
                    } catch (error) {
                        window.clearTimeout(timeout);
                        reject(error);
                    }
                });
            }
            freezeRecordingDuration();
            state.finalChunkCount = state.chunkIndex;
            state.recorder = null;
            unlockRecordingCanvasSize();
            setShellClass('is-recording', false);
            updateRecordingOperationStatus(isPublitioDirectMode() ? getStudioString('recordingStoppedFastCloudReady', 'Recording stopped. Ready for fast cloud upload.') : strings.uploadingChunk, 'info');
            renderRecordingState();
            updateOperatorStatus();
            return true;
        })();
        try {
            return await state.recordingStopPromise;
        } catch (error) {
            state.recordingStopRequested = false;
            state.recordingStopPromise = null;
            setRecordingStatus((error && error.message) || 'Recording could not be stopped cleanly. Try stopping again.', 'error');
            renderRecordingState();
            updateOperatorStatus();
            throw error;
        }
    }

    async function finishRecordingUploadsAndConfirmStop() {
        if (!state.activeJobId || state.serverStopConfirmed) {
            return null;
        }
        if (!state.recordingStoppedAt) {
            await stopLocalRecording();
        }
        if (isPublitioDirectMode()) {
            try {
                const job = await confirmServerStop();
                state.recordingPersistentWarnings = [];
                setRecordingStatus(getStudioString('recordingStoppedFastCloudReady', 'Recording stopped. Ready for fast cloud upload.'), 'success');
                return job;
            } catch (error) {
                state.stopFailed = true;
                state.serverStopConfirmed = false;
                state.currentJobStatus = 'stop_failed';
                setRecordingStatus((error && error.message) || 'Server stop confirmation failed. Retry stop before publishing.', 'error');
                throw error;
            } finally {
                renderRecordingState();
                updateOperatorStatus();
            }
        }
        if (state.failedChunks.size) {
            setRecordingStatus('Some chunks failed to upload. Retry failed chunks before preparing the replay.', 'warning');
            return null;
        }
        await waitForUploads();
        if (state.failedChunks.size) {
            setRecordingStatus('Some chunks failed to upload. Retry failed chunks before preparing the replay.', 'warning');
            return null;
        }
        try {
            const job = await confirmServerStop();
            state.recordingPersistentWarnings = [];
            setRecordingStatus(strings.recordingSaved, 'success');
            return job;
        } catch (error) {
            state.stopFailed = true;
            state.serverStopConfirmed = false;
            state.currentJobStatus = 'stop_failed';
            setRecordingStatus((error && error.message) || 'Server stop confirmation failed. Retry stop before preparing the replay.', 'error');
            throw error;
        } finally {
            setRecorderButtons(false, state.serverStopConfirmed && state.activeJobId && !state.pendingUploads.size && !state.failedChunks.size);
            renderRecordingState();
            updateOperatorStatus();
        }
    }

    async function stopRecording() {
        if (state.stopFailed) {
            await retryServerStop();
            return;
        }
        try {
            await stopLocalRecording();
            await finishRecordingUploadsAndConfirmStop();
        } catch (error) {
            if (!state.recordingStoppedAt) {
                setRecordingStatus((error && error.message) || 'Recording could not be stopped cleanly. Try stopping again.', 'error');
            }
        }
    }

    async function waitForUploads() {
        while (state.pendingUploads.size || state.uploadQueue.length || state.activeUploadCount) {
            await new Promise((resolve) => window.setTimeout(resolve, 250));
        }
    }

    function retryFailedChunks() {
        updateRecordingOperationStatus(strings.uploadRetry, 'info');
        Array.from(state.failedChunks.entries()).forEach(([index, blob]) => {
            if (state.pendingUploads.has(index)) {
                return;
            }
            state.pendingUploads.add(index);
            state.uploadQueue.push({ blob: blob, index: index });
        });
        scheduleChunkUploads();
        renderRecordingState();
    }

    async function finalizeRecording() {
        if (state.finalizeInProgress) {
            return;
        }
        if (state.stopFailed || !state.serverStopConfirmed) {
            setRecordingStatus(getStudioString('confirmServerStopBeforeReplay', 'Confirm server stop before preparing the replay.'), 'warning');
            return;
        }
        if (state.failedChunks.size || state.pendingUploads.size) {
            setRecordingStatus(getStudioString('retryFailedChunksBeforeReplay', 'Retry failed chunks during this session before preparing the replay.'), 'warning');
            return;
        }
        if (!state.finalChunkCount) {
            setRecordingStatus(getStudioString('noRecordingChunksCaptured', 'No recording chunks were captured. Start a new recording and try again.'), 'error');
            return;
        }
        try {
            state.finalizeInProgress = true;
            if (els.recordingFinalizeStatus) { els.recordingFinalizeStatus.textContent = strings.finalizing; }
            if (els.finalizeRecording) { els.finalizeRecording.disabled = true; }
            const job = await api('/jobs/' + state.activeJobId + '/recording/finalize', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': config.nonce }, body: JSON.stringify({ expected_chunks: state.finalChunkCount }) });
            state.currentJobStatus = job.status || 'processing';
            state.currentStorageProvider = job.storage_provider || state.currentStorageProvider;
            setRecordingStatus(getStudioString('replayPreparedReadyToPublish', 'Replay prepared. You can publish it now.'), 'success');
                updatePublishingButtons();
            if (els.recordingFinalizeStatus) { els.recordingFinalizeStatus.textContent = job.status || 'processing'; }
        } catch (error) {
            setRecordingStatus(error.message || strings.chunkUploadFailed, 'error');
            if (els.recordingFinalizeStatus) { els.recordingFinalizeStatus.textContent = error.message || strings.chunkUploadFailed; }
        } finally {
            state.finalizeInProgress = false;
            renderRecordingState();
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
            return;
        }
        els.replayLink.href = url;
        els.replayLink.textContent = 'Open replay';
        els.replayLinkWrap.hidden = false;
    }

    function renderReplayRawUrl(url) {
        if (els.replayRawUrl) {
            els.replayRawUrl.textContent = url || '—';
        }
    }

    function normalizePublishStatus(result) {
        return String((result && (result.job_status || result.status || result.publish_provider_status || '')) || '').toLowerCase();
    }

    function hasPublicReplay(result) {
        const status = normalizePublishStatus(result);
        return Boolean(result && (result.replay_url || result.replay_video_id || 'ready' === status));
    }

    function resolvePublicReplayUrl(result) {
        if (!hasPublicReplay(result)) {
            return '';
        }
        return result.replay_url || result.permalink || '';
    }

    function isPublishFailure(result) {
        const status = normalizePublishStatus(result);
        return Boolean(result && (result.error_message || 'failed' === status || 'publish_failed' === result.publish_provider_status || 'prepare_failed' === result.publish_provider_status || 'replay_post_failed' === result.publish_provider_status));
    }

    function hasProviderProcessingReference(result) {
        const providerStatus = String((result && (result.provider_status || result.publish_provider_status || result.status || '')) || '').toLowerCase();
        return Boolean(result && (result.provider_file_id || result.bunny_video_id || providerStatus.indexOf('processing') !== -1));
    }

    function updatePublishingButtons() {
        const directReady = isPublitioDirectMode() && state.recordingStoppedAt && state.serverStopConfirmed && state.directUploadParts.size && state.directUploadAvailable && state.currentJobStatus !== 'ready';
        const providerProcessing = hasProviderProcessingReference(state.currentPublishResult);
        const canPublish = Boolean(state.activeJobId) && !state.publishInFlight && !providerProcessing && (state.currentJobStatus === 'processing' || directReady);
        if (els.publishReplay) {
            els.publishReplay.hidden = !canPublish;
            els.publishReplay.disabled = !canPublish || state.publishInFlight;
        }
        if (els.checkReplayStatus) {
            const canCheck = Boolean(state.activeJobId) && (providerProcessing || state.currentJobStatus === 'processing' || state.currentJobStatus === 'ready');
            els.checkReplayStatus.hidden = !canCheck;
            els.checkReplayStatus.disabled = !canCheck || state.publishStatusCheckInFlight || state.publishInFlight;
        }
    }

    function stopPublishPolling() {
        if (state.publishPollTimer) {
            window.clearTimeout(state.publishPollTimer);
        }
        state.publishPollTimer = null;
        state.publishPollAttempts = 0;
    }

    function shouldPollPublishStatus(result) {
        const status = result && (result.job_status || result.status || result.publish_provider_status || '');
        if (result && (result.replay_url || result.replay_video_id)) {
            return false;
        }
        return ['pending', 'prepared', 'processing', 'media_attached_waiting_videopress'].indexOf(status) !== -1;
    }

    function startPublishPolling() {
        stopPublishPolling();
        const maxAttempts = 24;
        const poll = async () => {
            if (!state.activeJobId || state.publishPollAttempts >= maxAttempts) {
                if (state.publishPollAttempts >= maxAttempts) {
                    setPublishingStatus(strings.publishPollingTimeout || 'Replay is still processing. You can check status again later.', 'info');
                }
                stopPublishPolling();
                return;
            }
            state.publishPollAttempts += 1;
            const result = await checkPublishingStatus(true);
            if (result && 'busy' === result.status) {
                state.publishPollTimer = window.setTimeout(poll, 5000);
                return;
            }
            if (result && shouldPollPublishStatus(result)) {
                if (state.publishPollAttempts >= maxAttempts) {
                    setPublishingStatus(strings.publishPollingTimeout || 'Replay is still processing. You can check status again later.', 'info');
                    stopPublishPolling();
                    return;
                }
                state.publishPollTimer = window.setTimeout(poll, 5000);
            } else {
                stopPublishPolling();
            }
        };
        state.publishPollTimer = window.setTimeout(poll, 5000);
    }

    function canDirectUploadToPublitio() {
        const direct = config.publitioDirectUpload || {};
        return Boolean(isPublitioDirectMode() && state.recordingStoppedAt && state.serverStopConfirmed && state.directUploadAvailable && state.directUploadParts.size && window.XMLHttpRequest && window.FormData);
    }

    function buildDirectUploadBlob() {
        if (!state.directUploadParts.size) {
            return null;
        }
        const ordered = Array.from(state.directUploadParts.entries()).sort((a, b) => Number(a[0]) - Number(b[0])).map((entry) => entry[1]);
        return new Blob(ordered, { type: state.selectedMimeType || 'video/mp4' });
    }

    function publitioDirectUpload(url, form) {
        return new Promise((resolve, reject) => {
            const xhr = new XMLHttpRequest();
            xhr.open('POST', url);
            xhr.responseType = 'json';
            xhr.upload.onprogress = (event) => {
                if (event.lengthComputable) {
                    setPublishingStatus((strings.publitioDirectUploading || 'Uploading through fast cloud upload…') + ' ' + Math.round((event.loaded / event.total) * 100) + '%', 'info');
                }
            };
            xhr.onload = () => {
                const payload = xhr.response || {};
                if (xhr.status >= 200 && xhr.status < 300) {
                    resolve(payload);
                    return;
                }
                reject(new Error((payload && (payload.message || payload.error || payload.msg)) || 'Fast cloud upload failed.'));
            };
            xhr.onerror = () => reject(new Error('Fast cloud upload failed before completion.'));
            xhr.ontimeout = () => reject(new Error('Fast cloud upload timed out.'));
            xhr.send(form);
        });
    }

    function findPublitioFilePayload(response) {
        if (!response || typeof response !== 'object') {
            return {};
        }
        if (response.file && typeof response.file === 'object') {
            return response.file;
        }
        if (response.data && typeof response.data === 'object') {
            return response.data;
        }
        return response;
    }

    async function publishReplayViaDirectPublitio() {
        const blob = buildDirectUploadBlob();
        if (!blob || !blob.size) {
            throw new Error('Local recording data is unavailable for fast cloud upload.');
        }
        const direct = config.publitioDirectUpload || {};
        if (direct.max_size && blob.size > Number(direct.max_size)) {
            throw new Error('Recording is too large for direct browser upload.');
        }
        const allowed = direct.allowed_mime_types || [];
        const baseMime = (blob.type || state.selectedMimeType || '').split(';')[0].toLowerCase();
        if (allowed.length && allowed.indexOf(baseMime) === -1) {
            throw new Error('Recording type is not allowed for direct browser upload.');
        }

        setPublishingStatus(strings.publitioDirectUploading || 'Uploading through fast cloud upload…', 'info');
        const auth = await api('/jobs/' + state.activeJobId + '/publitio/direct-upload/authorize', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': config.nonce }, body: JSON.stringify({ mime_type: baseMime || blob.type || state.selectedMimeType, file_size: blob.size, duration_seconds: state.recordingDurationSeconds || 0 }) });
        const form = new FormData();
        form.append('file', blob, 'vh360-studio-replay-' + state.activeJobId + recordingExtension(baseMime || blob.type || state.selectedMimeType));
        ['public_id', 'title', 'description', 'tags', 'folder', 'privacy', 'option_download', 'option_hls', 'option_ad'].forEach((key) => {
            if (auth[key]) {
                form.append(key, String(auth[key]));
            }
        });
        const uploaded = await publitioDirectUpload(auth.upload_url, form);
        const file = findPublitioFilePayload(uploaded);
        const fileId = file.id || file.file_id || uploaded.id || uploaded.file_id || '';
        if (!fileId) {
            throw new Error('Cloud upload did not return a valid file reference.');
        }
        setPublishingStatus(strings.publitioDirectVerifying || 'Cloud upload complete. Verifying replay…', 'info');
        return api('/jobs/' + state.activeJobId + '/publitio/direct-upload/complete', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': config.nonce },
            body: JSON.stringify({
                direct_upload_token: auth.direct_upload_token,
                publitio_file_id: fileId,
                playback_url: file.url_preview || file.url_download || file.url || '',
                poster_url: file.url_thumbnail || file.thumbnail_url || '',
                embed_url: file.embed_url || file.url_embed || '',
                file_size: file.size || file.bytes || blob.size,
                mime_type: baseMime || blob.type || state.selectedMimeType
            })
        });
    }

    async function publishReplayViaServerRelay() {
        return api('/jobs/' + state.activeJobId + '/publishing/publish', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': config.nonce } });
    }

    async function publishReplay() {
        if (!state.activeJobId || state.publishInFlight) {
            return;
        }
        state.publishInFlight = true;
        if (els.publishReplay) { els.publishReplay.disabled = true; }
        setPublishingStatus(strings.publishingReplay || 'Publishing replay…', 'info');
        try {
            let result;
            if (canDirectUploadToPublitio()) {
                state.directUploadInProgress = true;
                result = await publishReplayViaDirectPublitio();
                state.directUploadParts.clear();
                state.directUploadBytes = 0;
            } else if (isPublitioDirectMode()) {
                throw new Error('Fast cloud upload is enabled, but the local recording Blob is unavailable or over the upload limit. Retry while the recording is still in this browser, or switch to server relay for the next recording.');
            } else {
                result = await publishReplayViaServerRelay();
            }
            state.currentPublishResult = result;
            const rawPlaybackUrl = result.playback_url || '';
            const publicReplayUrl = resolvePublicReplayUrl(result);
            const published = hasPublicReplay(result);
            const failed = isPublishFailure(result);
            state.currentJobStatus = published ? 'ready' : (failed ? 'failed' : (result.job_status || result.status || result.publish_provider_status || state.currentJobStatus));
            renderReplayRawUrl(rawPlaybackUrl);
            renderReplayLink(publicReplayUrl);
            if (published) {
                setPublishingStatus(strings.publishComplete, 'success');
            } else if (failed) {
                setPublishingStatus(result.error_message || result.message || strings.publishFailed, 'error');
            } else {
                setPublishingStatus(result.message || strings.publishProcessing || 'Replay processing…', 'info');
            }
            if (shouldPollPublishStatus(result)) {
                startPublishPolling();
            }
        } catch (error) {
            stopPublishPolling();
            setPublishingStatus(error.message || strings.publishFailed, 'error');
        } finally {
            state.directUploadInProgress = false;
            state.publishInFlight = false;
            updatePublishingButtons();
        }
    }

    async function checkPublishingStatus(isAutomatic) {
        if (!state.activeJobId) {
            return null;
        }
        if (state.publishStatusCheckInFlight) {
            return { status: 'busy' };
        }
        state.publishStatusCheckInFlight = true;
        updatePublishingButtons();
        if (!isAutomatic) {
            setPublishingStatus(strings.publishStatusChecking, 'info');
        }
        try {
            const result = await api('/jobs/' + state.activeJobId + '/publishing/status', { method: 'GET' });
            state.currentPublishResult = result;
            const rawPlaybackUrl = result.playback_url || '';
            const publicReplayUrl = resolvePublicReplayUrl(result);
            const published = hasPublicReplay(result);
            state.currentJobStatus = published ? 'ready' : (isPublishFailure(result) ? 'failed' : (result.job_status || result.status || result.publish_provider_status || state.currentJobStatus));
            renderReplayRawUrl(rawPlaybackUrl);
            renderReplayLink(publicReplayUrl);
            if (published) {
                setPublishingStatus(strings.publishComplete, 'success');
            } else if (isPublishFailure(result)) {
                setPublishingStatus(result.error_message || result.message || strings.publishFailed, 'error');
            } else if (shouldPollPublishStatus(result)) {
                setPublishingStatus(result.message || strings.publishProcessing || 'Replay processing…', 'info');
            } else {
                setPublishingStatus(result.error_message || result.message || result.publish_provider_status || result.status || 'pending', 'info');
            }
            updatePublishingButtons();
            return result;
        } catch (error) {
            setPublishingStatus(error.message || strings.publishFailed, 'error');
            updatePublishingButtons();
            return { status: 'failed' };
        } finally {
            state.publishStatusCheckInFlight = false;
            updatePublishingButtons();
        }
    }

    async function checkReplayStatus() {
        if (!state.activeJobId) {
            return null;
        }
        return checkPublishingStatus(false);
    }

    function startTimer() {
        stopTimer();
        state.durationTimer = window.setInterval(renderRecordingState, 1000);
    }

    function stopTimer() {
        if (state.durationTimer) { window.clearInterval(state.durationTimer); }
        state.durationTimer = null;
    }

    function recordingSummaryStatus() {
        if (state.stopFailed) {
            return 'Stop confirmation failed';
        }
        if (state.failedChunks.size) {
            return 'Needs attention';
        }
        if (isRecordingActive()) {
            return 'Recording';
        }
        if (state.pendingUploads.size) {
            return 'Uploading chunks';
        }
        if (state.currentJobStatus === 'processing') {
            return 'Replay ready to publish';
        }
        if (state.currentJobStatus === 'ready') {
            return 'Replay published';
        }
        if (state.finalChunkCount && !state.serverStopConfirmed) {
            return 'Confirm stop before replay';
        }
        if (state.finalChunkCount) {
            return 'Ready to prepare replay';
        }
        return 'Ready to record';
    }

    function renderRecordingState() {
        if (els.recordingJobId) { els.recordingJobId.textContent = state.activeJobId || '—'; }
        if (els.recordingMime) { els.recordingMime.textContent = state.selectedMimeType || preferredMimeType() || '—'; }
        updateOperatorStatus();
        if (els.recordingUploaded) { els.recordingUploaded.textContent = String(state.uploadedChunks.size); }
        if (els.recordingPending) { els.recordingPending.textContent = String(state.pendingUploads.size); }
        if (els.recordingFailed) { els.recordingFailed.textContent = String(state.failedChunks.size); }
        if (els.recordingSummaryStatus) { els.recordingSummaryStatus.textContent = recordingSummaryStatus(); }
        if (els.recordingTimer) {
            let seconds = 0;
            if (state.recordingStoppedAt) {
                seconds = Math.max(0, state.recordingDurationSeconds || 0);
            } else if (state.recordingStartedAt) {
                seconds = Math.max(0, Math.floor((Date.now() - state.recordingStartedAt) / 1000));
            }
            els.recordingTimer.textContent = String(Math.floor(seconds / 60)).padStart(2, '0') + ':' + String(seconds % 60).padStart(2, '0');
        }
        const total = Math.max(state.chunkIndex, state.finalChunkCount, 1);
        const progress = Math.round((state.uploadedChunks.size / total) * 100);
        if (els.recordingProgress) {
            els.recordingProgress.value = progress;
        }
        if (els.recordingProgressLabel) {
            els.recordingProgressLabel.textContent = progress + '%';
        }
        const recording = isRecordingActive();
        const stopping = Boolean(state.recordingStopRequested && !state.recordingStoppedAt);
        const hasFailedUploads = Boolean(state.failedChunks.size);
        const canPrepareReplay = Boolean(!isPublitioDirectMode() && state.activeJobId && state.serverStopConfirmed && state.finalChunkCount && !state.pendingUploads.size && !hasFailedUploads && !state.finalizeInProgress && state.currentJobStatus !== 'processing' && state.currentJobStatus !== 'ready');
        setButtonVisibility(els.startRecording, !recording && !stopping && !state.activeJobId, true);
        if (els.stopRecording) { els.stopRecording.textContent = state.stopFailed ? 'Retry stop' : 'Stop recording'; }
        setButtonVisibility(els.stopRecording, recording || stopping || state.stopFailed, !stopping);
        setButtonVisibility(els.retryChunks, hasFailedUploads, hasFailedUploads);
        setButtonVisibility(els.finalizeRecording, canPrepareReplay, canPrepareReplay);
        updatePublishingButtons();
        renderOnAirTabProtection();
    }

    function setRecorderButtons(recording, stopped) {
        const stopping = Boolean(state.recordingStopRequested && !state.recordingStoppedAt);
        setButtonVisibility(els.startRecording, !recording && !stopping && !state.activeJobId, true);
        if (els.stopRecording) { els.stopRecording.textContent = state.stopFailed ? 'Retry stop' : 'Stop recording'; }
        setButtonVisibility(els.stopRecording, recording || stopping || state.stopFailed, !stopping);
        const canPrepareReplay = Boolean(!isPublitioDirectMode() && stopped && state.serverStopConfirmed && state.activeJobId && state.finalChunkCount && !state.pendingUploads.size && !state.failedChunks.size && !state.finalizeInProgress && state.currentJobStatus !== 'processing' && state.currentJobStatus !== 'ready');
        setButtonVisibility(els.finalizeRecording, canPrepareReplay, canPrepareReplay);
        setButtonVisibility(els.retryChunks, Boolean(state.failedChunks.size), Boolean(state.failedChunks.size));
        renderTransitionButtons();
    }

    function cleanup(options = {}) {
        state.studioTearingDown = true;
        flushCameraSourceConfigurationSave();
        flushAudioInputConfigurationSave();
        state.audioInputTestRequestId++;
        stopStream(state.audioInputTestStream);
        state.audioInputTestStream = null;
        if (options.releaseMediaSources === true) {
            if (window.VH360StudioBible && typeof window.VH360StudioBible.destroy === 'function') {
                window.VH360StudioBible.destroy();
            }
            if (window.VH360StudioCountdown && typeof window.VH360StudioCountdown.destroy === 'function') {
                window.VH360StudioCountdown.destroy();
            }
            if (window.VH360StudioOverlayStatus && typeof window.VH360StudioOverlayStatus.destroy === 'function') {
                window.VH360StudioOverlayStatus.destroy();
            }
            if (window.VH360StudioLowerThirds && typeof window.VH360StudioLowerThirds.destroy === 'function') {
                window.VH360StudioLowerThirds.destroy();
            }
            if (window.VH360StudioOverlayEngine && typeof window.VH360StudioOverlayEngine.destroy === 'function') {
                window.VH360StudioOverlayEngine.destroy();
            }
        }
        const canStopProgramOutput = !state.broadcastSession && !isRecordingActive();
        const releaseMediaSources = options.releaseMediaSources === true;

        stopAllCameraSources({ force: true, detach: releaseMediaSources });
        stopScreenPreview({ force: true });
        stopProgramCompositor({ stopTracks: canStopProgramOutput, clearStream: canStopProgramOutput });
        state.audioInputs.forEach((input) => stopAudioInput(input.id));
        teardownStudioAudioMixer();

        if (canStopProgramOutput && releaseMediaSources) {
            clearLocalMediaSources();
            state.previewSource = null;
            state.programSource = null;
            state.programStream = null;
            renderProgramState();
            updateProgramAudioRouting();
            renderSourceState();
            renderSceneControls();
        }
        state.studioTearingDown = false;
    }


    function setCoverImage(attachmentId, imageUrl, options = {}) {
        state.featuredImageId = Number(attachmentId) || 0;
        state.featuredImageUrl = imageUrl || '';
        if (options.clear === true) {
            state.clearFeaturedImage = true;
        } else if (state.featuredImageId || options.clear === false) {
            state.clearFeaturedImage = false;
        }
        if (els.coverImageId) {
            els.coverImageId.value = state.featuredImageId ? String(state.featuredImageId) : '';
        }
        if (els.coverImagePreview && els.coverImagePreviewImg) {
            els.coverImagePreview.hidden = !state.featuredImageUrl;
            els.coverImagePreviewImg.src = state.featuredImageUrl || '';
        }
        if (els.removeCoverImage) {
            els.removeCoverImage.hidden = !state.featuredImageId;
        }
    }

    function selectCoverImage() {
        if (!els.coverImageFile) {
            setBroadcastStatus('Cover image upload is unavailable.', 'error');
            return;
        }
        els.coverImageFile.click();
    }

    async function uploadSelectedCoverImage() {
        const file = els.coverImageFile && els.coverImageFile.files && els.coverImageFile.files[0];
        if (!file) {
            return;
        }

        if (!file.type || file.type.indexOf('image/') !== 0) {
            setBroadcastStatus('Choose a valid image file.', 'error');
            els.coverImageFile.value = '';
            return;
        }

        const formData = new FormData();
        formData.append('file', file);

        if (els.selectCoverImage) {
            els.selectCoverImage.disabled = true;
        }
        setBroadcastStatus('Uploading cover image…', 'info');

        try {
            const response = await api('/cover-image', { method: 'POST', body: formData });
            setCoverImage(response.attachment_id || 0, response.url || response.thumbnail_url || '', { clear: false });
            setBroadcastStatus('Cover image uploaded.', 'success');
        } catch (error) {
            setBroadcastStatus((error && error.message) || 'Cover image upload failed.', 'error');
        } finally {
            if (els.selectCoverImage) {
                els.selectCoverImage.disabled = false;
            }
            if (els.coverImageFile) {
                els.coverImageFile.value = '';
            }
        }
    }


    function setBroadcastStatus(message, type) {
        if (els.broadcastStatus) {
            els.broadcastStatus.textContent = message;
            els.broadcastStatus.dataset.statusType = type || 'info';
        }
    }

    function broadcastPayload() {
        const mode = els.broadcastMode ? els.broadcastMode.value : 'broadcast';
        const featuredImageId = state.featuredImageId || (els.coverImageId ? Number(els.coverImageId.value) || 0 : 0);
        const payload = {
            title: els.broadcastTitle && els.broadcastTitle.value ? els.broadcastTitle.value : 'Studio Livestream',
            description: els.broadcastDescription ? els.broadcastDescription.value : '',
            agora_mode: mode,
            viewer_count: !!(els.broadcastViewerCount && els.broadcastViewerCount.checked),
            chat_enabled: !!(els.broadcastChat && els.broadcastChat.checked),
            agora_everyone_is_host: mode === 'interactive' && !!(els.broadcastEveryoneHost && els.broadcastEveryoneHost.checked),
            require_passcode: mode === 'interactive' && !!(els.broadcastRequirePasscode && els.broadcastRequirePasscode.checked),
            host_passcode: els.broadcastPasscode ? els.broadcastPasscode.value : '',
            quality_preset: getSelectedPresetKey(),
            clear_featured_image: !featuredImageId && state.clearFeaturedImage,
        };
        if (state.broadcastVideoId && Number(state.broadcastVideoId) > 0) {
            payload.video_id = Number(state.broadcastVideoId);
        }
        if (featuredImageId && Number(featuredImageId) > 0) {
            payload.featured_image_id = Number(featuredImageId);
        }
        return payload;
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
        const activeProgramCamera = isCameraSource(state.programSource) ? getCameraSource(state.programSource) : primaryCameraSource();
        if (activeProgramCamera && activeProgramCamera.deviceId) {
            videoConfig.cameraId = activeProgramCamera.deviceId;
        }
        if (Object.keys(encoderConfig).length) {
            videoConfig.encoderConfig = encoderConfig;
        }
        return videoConfig;
    }

    function agoraAudioConfigFromSelection() {
        const primary = primaryAudioInput();
        return primary && primary.deviceId ? { microphoneId: primary.deviceId } : {};
    }

    async function goLive() {
        const supportError = requiredSupportError('live');
        if (supportError) {
            setBroadcastStatus(supportError, 'error');
            setStatus(supportError, 'error');
            return;
        }
        if (!window.VH360AgoraBroadcaster) {
            setBroadcastStatus(strings.broadcastFailed, 'error');
            return;
        }
        if (isRecordingActive()) {
            setBroadcastStatus('Stop browser recording before going live, then start recording again after the live connection is active so Studio can record the active broadcast tracks.', 'error');
            return;
        }
        if (!state.broadcastVideoId && els.broadcastMode && els.broadcastMode.value === 'interactive' && els.broadcastRequirePasscode && els.broadcastRequirePasscode.checked && (!els.broadcastPasscode || !els.broadcastPasscode.value.trim())) {
            setBroadcastStatus('Enter a passcode before enabling passcode access.', 'error');
            return;
        }
        setBroadcastStatus(strings.goingLive, 'info');
        state.broadcastStarting = true;
        state.broadcastReady = false;
        renderTransitionButtons();
        renderProgramLiveControls();
        if (els.goLive) els.goLive.disabled = true;
        try {
            if (!state.programSource) {
                const staged = await setPreviewSource(primaryCameraSourceId());
                if (staged) {
                    await commitPreviewToProgram('cut');
                }
            }
            if (!hasProgramOutput()) {
                throw new Error(getStudioString('chooseProgramSourceBeforeLive', 'Choose a Program source before going live.'));
            }
            setBroadcastStatus('Go Live will use the current Program source.', 'info');
            const audioSummary = await ensureAudioInputStreams().catch((error) => ({ active: 0, failed: state.audioInputs.size || 1, skipped: 0, total: state.audioInputs.size || 1, results: [], error }));
            if (audioSummary.active === 0) {
                setBroadcastStatus(getStudioString('noMicrophoneInputsActive', 'No microphone inputs are active. Studio will continue without microphone audio.'), 'warning');
            } else if (audioSummary.failed > 0) {
                setBroadcastStatus(formatAudioInputSummary(audioSummary, 'live'), 'warning');
            }
            const created = await api('/broadcasts', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': config.nonce }, body: JSON.stringify(broadcastPayload()) });
            const broadcast = created.broadcast || {};
            state.broadcastVideoId = broadcast.videoId;
            state.activeJobId = created.job && created.job.id ? created.job.id : state.activeJobId;
            state.currentStorageProvider = created.job && created.job.storage_provider ? created.job.storage_provider : state.currentStorageProvider;
            state.viewerPermalink = broadcast.viewerPermalink || '';
            if (broadcast.featuredImageId || broadcast.featuredImageUrl) {
                setCoverImage(broadcast.featuredImageId || state.featuredImageId, broadcast.featuredImageUrl || state.featuredImageUrl, { clear: false });
            } else {
                state.clearFeaturedImage = false;
            }
            updateViewerLinkControls();
            const prepared = await api('/broadcasts/' + state.broadcastVideoId + '/prepare', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': config.nonce } });
            studioDebugLog('[VH360 Studio] Studio broadcaster UID', prepared.uid);
            applyProgramCanvasResolution();
            ensureProgramCompositor();
            const session = window.VH360AgoraBroadcaster.create({
                appId: prepared.appId,
                channelName: prepared.channelName,
                token: prepared.token,
                uid: prepared.uid,
                clientMode: prepared.clientMode,
                container: root,
                localContainer: els.agoraLocalPreview,
                audioConfig: agoraAudioConfigFromSelection(),
                initialAudioMediaStreamTrack: getStudioMixedAudioTrack(),
                audioSource: 'studio-mix',
                videoConfig: agoraVideoConfigFromPreset(),
                initialVideoMediaStreamTrack: state.programOutputStream ? state.programOutputStream.getVideoTracks()[0] : null,
                initialVideoSource: state.programSource || '',
            });
            await session.start();
            studioDebugLog('[VH360 Studio] Agora mixed audio diagnostics', { mixerId: state.audioMixer ? state.audioMixer.id : '', audioTrackId: getStudioMixedAudioTrack() ? getStudioMixedAudioTrack().id : '', agoraAudioTrackId: typeof session.getAudioTrackId === 'function' ? session.getAudioTrackId() : '' });
            state.broadcastSession = session;
            state.liveAudioWarningActive = false;
            updateViewerLinkControls();
            state.broadcastReady = true;
            state.broadcastStarting = false;
            renderTransitionButtons();
            renderProgramLiveControls();
            await api('/broadcasts/' + state.broadcastVideoId + '/started', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': config.nonce }, body: JSON.stringify({ job_id: state.activeJobId || 0 }) });
            state.heartbeatTimer = window.setInterval(() => {
                api('/broadcasts/' + state.broadcastVideoId + '/heartbeat', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': config.nonce } }).catch(() => {});
            }, 30000);
            if (els.endLive) els.endLive.disabled = false;
            renderProgramLiveControls();
            setShellClass('is-live', true);
            updateLiveAudioInputWarning();
            if (!state.liveAudioWarningActive) {
                setBroadcastStatus(strings.liveStarted, 'success');
            }
        } catch (error) {
            const failedVideoId = state.broadcastVideoId;
            if (state.broadcastSession) {
                await state.broadcastSession.stop().catch(() => {});
                state.broadcastSession = null;
                state.liveAudioWarningActive = false;
                state.broadcastReady = false;
                state.viewerPermalink = '';
                updateViewerLinkControls();
            }
            if (failedVideoId) {
                await api('/broadcasts/' + failedVideoId + '/end', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': config.nonce } }).catch(() => {});
            }
            if (state.heartbeatTimer) {
                window.clearInterval(state.heartbeatTimer);
                state.heartbeatTimer = null;
            }
            state.broadcastStarting = false;
            state.broadcastReady = false;
            if (els.endLive) els.endLive.disabled = true;
            resetProgramLiveControlState();
            if (els.goLive) els.goLive.disabled = false;
            setShellClass('is-live', false);
            renderTransitionButtons();
            setBroadcastStatus((error && error.message) || strings.broadcastFailed, 'error');
        }
    }

    async function endLive() {
        if (state.broadcastEnding) {
            return;
        }
        state.broadcastEnding = true;
        state.broadcastReady = false;
        renderTransitionButtons();
        renderProgramLiveControls();
        if (state.recorder || state.recordingStopPromise) {
            setBroadcastStatus('Stopping local recording before ending live…', 'info');
            const localStopError = await stopLocalRecording().then(() => null).catch((error) => {
                setRecordingStatus((error && error.message) || strings.chunkUploadFailed, 'error');
                return error || new Error(strings.chunkUploadFailed);
            });
            if (localStopError) {
                state.broadcastEnding = false;
                state.broadcastReady = Boolean(state.broadcastSession);
                renderTransitionButtons();
                renderProgramLiveControls();
                setBroadcastStatus('Recording could not be stopped cleanly. Stop recording before ending live.', 'error');
                return;
            }
            if (state.recordingStoppedAt && !state.serverStopConfirmed) {
                updateRecordingOperationStatus(isPublitioDirectMode() ? getStudioString('recordingStoppedFastCloudReady', 'Recording stopped. Ready for fast cloud upload.') : strings.uploadingChunk, 'info');
                finishRecordingUploadsAndConfirmStop().catch(() => {});
            }
        } else if (state.recordingStoppedAt && !state.serverStopConfirmed) {
            updateRecordingOperationStatus(isPublitioDirectMode() ? getStudioString('recordingStoppedFastCloudReady', 'Recording stopped. Ready for fast cloud upload.') : strings.uploadingChunk, 'info');
            finishRecordingUploadsAndConfirmStop().catch(() => {});
        }
        if (state.heartbeatTimer) {
            window.clearInterval(state.heartbeatTimer);
            state.heartbeatTimer = null;
        }
        if (state.broadcastSession) {
            await state.broadcastSession.stop();
            state.broadcastSession = null;
            state.liveAudioWarningActive = false;
        }
        state.viewerPermalink = '';
        updateViewerLinkControls();
        if (state.broadcastVideoId) {
            try {
                await api('/broadcasts/' + state.broadcastVideoId + '/end', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': config.nonce } });
            } catch (error) {
                state.broadcastEnding = false;
                if (els.goLive) els.goLive.disabled = false;
                if (els.endLive) els.endLive.disabled = true;
                setBroadcastStatus('Local broadcast stopped, but Videohub360 could not mark the public livestream ended: ' + ((error && error.message) || 'Unknown error'), 'error');
                return;
            }
        }
        state.broadcastEnding = false;
        state.broadcastReady = false;
        if (els.goLive) els.goLive.disabled = false;
        if (els.endLive) els.endLive.disabled = true;
        resetProgramLiveControlState();
        setShellClass('is-live', false);
        renderTransitionButtons();
        setBroadcastStatus((state.recordingStoppedAt && !state.serverStopConfirmed) ? 'Live ended. Recording upload is still finishing.' : strings.liveEnded, 'success');
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

    function toggleSelectedMediaControls() {
        const source = getActiveMediaSource();

        if (!source) {
            state.mediaControlsExpanded = false;
            renderSelectedMediaControls();
            return;
        }

        state.mediaControlsExpanded = !state.mediaControlsExpanded;
        renderSelectedMediaControls();
    }

    function toggleSelectedMediaPlayback() {
        const source = getActiveVideoMediaSource();

        if (!source || !source.element) {
            return;
        }

        if (source.element.paused) {
            source.element.play().catch(() => {
                setStatus('Unable to play media. Try selecting it again.', 'warning');
            });
        } else {
            source.element.pause();
        }

        renderMediaPlaybackControls();
    }

    function restartSelectedMedia() {
        const source = getActiveVideoMediaSource();

        if (!source || !source.element) {
            return;
        }

        source.element.currentTime = 0;
        source.element.play().catch(() => {});
        renderMediaPlaybackControls();
    }

    function toggleSelectedMediaLoop() {
        const source = getActiveVideoMediaSource();

        if (!source || !source.element || !els.mediaLoop) {
            return;
        }

        source.element.loop = els.mediaLoop.checked;
        renderMediaPlaybackControls();
    }

    function seekSelectedMedia() {
        const source = getActiveVideoMediaSource();
        const video = source && source.element;

        if (!video || !els.mediaSeek || !Number.isFinite(video.duration) || video.duration <= 0) {
            return;
        }

        video.currentTime = (Number(els.mediaSeek.value) / 1000) * video.duration;
        renderMediaPlaybackControls();
    }

    function updateActiveMediaTransform(partial) {
        const source = getActiveMediaSource();

        if (!source) {
            return;
        }

        source.transform = Object.assign(defaultMediaTransform(), source.transform || {}, partial || {});

        renderSelectedMediaControls();
        renderPreviewMediaTransform();
    }

    function resetActiveMediaTransform() {
        const source = getActiveMediaSource();

        if (!source) {
            return;
        }

        source.transform = defaultMediaTransform();
        renderSelectedMediaControls();
        renderPreviewMediaTransform();
    }


    async function testCameraDevice() {
        if (!state.support.getUserMedia) { setDeviceStatus(getStudioString('cameraTestingUnavailable', 'Camera testing is unavailable in this browser.'), 'error'); return; }
        if (state.broadcastSession || isRecordingActive()) {
            setDeviceStatus(getStudioString('stopLiveRecordingBeforeCameraTest', 'Stop live/recording before testing a different camera.'), 'warning');
            return;
        }
        const source = getSelectedCameraSource();
        if (!source) {
            setDeviceStatus(getStudioString('selectedCameraUnavailable', 'Selected camera unavailable.'), 'error');
            return;
        }
        if (hasLiveVideoTrack(source.stream)) {
            state.previewRequestId++;
            state.previewSource = source.sourceId;
            renderSourceState();
            setDeviceStatus(getStudioString('cameraSourceAlreadyActive', 'Camera source is already active.'), 'info');
            return;
        }
        setDeviceStatus(getStudioString('testingSelectedCamera', 'Testing selected camera…'), 'info');
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ video: buildVideoConstraints(getSelectedPreset(), source.deviceId), audio: false });
            const track = stream.getVideoTracks()[0];
            const label = (track && track.label) || source.deviceLabel || source.label || getStudioString('defaultCamera', 'default camera');
            stopStream(stream);
            setDeviceStatus(getStudioString('cameraTestPassed', 'Camera test passed: {device}.').replace('{device}', label), 'success');
            await populateDevices({ reason: 'camera-test' }).catch(() => {});
        } catch (error) {
            const message = friendlyMediaError(error);
            setDeviceStatus(message, 'error');
            setStatus(message, 'error');
        }
    }

    async function testMicrophoneDevice() {
        if (!state.support.getUserMedia) { setDeviceStatus(getStudioString('microphoneTestingUnavailable', 'Microphone testing is unavailable in this browser.'), 'error'); return; }
        if (state.broadcastSession || isRecordingActive()) {
            setDeviceStatus(getStudioString('stopLiveRecordingBeforeMicTest', 'Stop live/recording before testing a different microphone.'), 'warning');
            return;
        }
        const primary = primaryAudioInput();
        if (!primary) {
            setDeviceStatus(getStudioString('primaryMicrophoneMissing', 'Primary microphone input is missing. Refresh Studio and try again.'), 'error');
            return;
        }
        if (hasLiveAudioTrack(primary.stream)) {
            setDeviceStatus(getStudioString('primaryMicAlreadyActive', 'Primary microphone is already active. Watch the Mic/Aux meter for live levels.'), 'info');
            focusOrHighlightMixerChannel(primary.id);
            return;
        }
        if (state.audioInputTestStream) {
            setDeviceStatus(getStudioString('microphoneTestAlreadyRunning', 'Microphone test is already running.'), 'info');
            return;
        }
        const requestId = ++state.audioInputTestRequestId;
        setDeviceStatus(getStudioString('testingSelectedMicrophone', 'Testing selected microphone. Watch the Mic/Aux meter…'), 'info');
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ audio: primary.deviceId ? { deviceId: { exact: primary.deviceId } } : true, video: false });
            if (state.studioTearingDown || requestId !== state.audioInputTestRequestId || primary.removed || !state.audioInputs.has(primary.id)) {
                stopStream(stream);
                return;
            }
            state.audioInputTestStream = stream;
            const testTrack = stream.getAudioTracks()[0];
            const testDeviceLabel = testTrack && testTrack.label ? testTrack.label : selectedOptionLabel(els.micSelect, getStudioString('defaultMicrophone', 'default microphone'));
            setMixerChannelStream(primary.mixerChannelId, stream, { sourceId: 'microphone-test' });
            focusOrHighlightMixerChannel(primary.id);
            window.setTimeout(() => {
                if (state.audioInputTestRequestId === requestId) {
                    stopStream(stream);
                    state.audioInputTestStream = null;
                    disconnectMixerChannel(primary.mixerChannelId, 'microphone-test');
                }
            }, 3000);
            setDeviceStatus(getStudioString('microphoneTestStarted', 'Microphone test started: {device}.').replace('{device}', testDeviceLabel), 'success');
            await populateDevices({ reason: 'microphone-test', keepDefaultMicrophone: true }).catch(() => {});
            if (els.micSelect && els.micSelect.disabled) {
                setDeviceStatus(getStudioString('microphonePermissionNoDevices', 'Microphone permission was requested, but the browser still reports zero microphones. Check OS privacy settings, USB/audio hardware, and browser device permissions.'), 'warning');
            }
        } catch (error) {
            if (requestId === state.audioInputTestRequestId) { state.audioInputTestStream = null; }
            const message = friendlyMediaError(error);
            setDeviceStatus(message, 'error');
            setStatus(message, 'error');
        }
    }

    function autoAssignableAudioInputDevice() {
        const devices = state.availableAudioInputDevices || [];
        if (!devices.length) { return null; }
        const deviceById = new Map(devices.map((device) => [device.deviceId, device]));
        const assignedIds = new Set();
        const assignedGroups = new Set();
        let hasAmbiguousAssignedInput = false;
        state.audioInputs.forEach((input) => {
            if (!input || input.removed) { return; }
            if (!input.deviceId) {
                if (hasLiveAudioTrack(input.stream)) { hasAmbiguousAssignedInput = true; }
                return;
            }
            assignedIds.add(input.deviceId);
            const assignedDevice = deviceById.get(input.deviceId);
            if (input.deviceId === 'default' || input.deviceId === 'communications' || !assignedDevice || !assignedDevice.groupId) {
                hasAmbiguousAssignedInput = true;
                return;
            }
            assignedGroups.add(assignedDevice.groupId);
        });
        if (hasAmbiguousAssignedInput) { return null; }
        const hardwareDevices = devices.filter((device) => device.deviceId && device.deviceId !== 'default' && device.deviceId !== 'communications');
        return hardwareDevices.find((device) => {
            if (assignedIds.has(device.deviceId)) { return false; }
            if (!device.groupId) { return assignedIds.size === 0; }
            return !assignedGroups.has(device.groupId);
        }) || null;
    }

    function bindEvents() {
        if (els.sceneList) {
            els.sceneList.addEventListener('click', async (event) => {
                const button = event.target.closest('[data-scene-source]');
                if (!button || !els.sceneList.contains(button)) {
                    return;
                }

                const sourceId = button.dataset.sceneSource || '';
                const previousPreviewSource = state.previewSource;
                const previewRequestId = ++state.previewRequestId;
                selectSceneSource(sourceId);
                try {
                    await setPreviewSource(sourceId, { requestId: previewRequestId });
                } catch (error) {
                    if (previewRequestId !== state.previewRequestId) {
                        return;
                    }
                    state.previewSource = previousPreviewSource;
                    if (isCameraSource(sourceId)) {
                        const source = getCameraSource(sourceId);
                        if (source) {
                            source.connecting = false;
                            source.connected = hasLiveVideoTrack(source.stream);
                            source.status = source.connected ? 'active' : 'error';
                            source.unavailable = !source.connected;
                            source.error = friendlyMediaError(error);
                        }
                    }
                    setStatus(friendlyMediaError(error), 'error');
                    setDeviceStatus(friendlyMediaError(error), 'error');
                    renderSourceState();
                }
            });
        }
        els.closeCameraSourceModal.forEach((button) => {
            button.addEventListener('click', closeCameraSourceModal);
        });
        els.closeMediaSourceModal.forEach((button) => {
            button.addEventListener('click', closeMediaSourceModal);
        });
        if (els.persistentMediaSourceInput) {
            els.persistentMediaSourceInput.addEventListener('change', handlePersistentMediaFileSelected);
        }
        if (els.sourceMenuToggle) {
            els.sourceMenuToggle.addEventListener('click', (event) => {
                event.stopPropagation();
                toggleSourceMenu();
            });
        }
        if (els.openCameraSource) {
            els.openCameraSource.addEventListener('click', () => {
                closeSourceMenu();
                openCameraSourceModal();
            });
        }
        if (els.openLocalMediaSource) {
            els.openLocalMediaSource.addEventListener('click', () => {
                closeSourceMenu();
                openMediaSourceModal('local');
            });
        }
        if (els.openUploadMediaSource) {
            els.openUploadMediaSource.addEventListener('click', () => {
                closeSourceMenu();
                openMediaSourceModal('upload');
            });
        }
        document.addEventListener('click', (event) => {
            if (!els.sourceMenu || !els.sourceMenuToggle) {
                return;
            }

            const clickedMenu = els.sourceMenu.contains(event.target);
            const clickedToggle = els.sourceMenuToggle.contains(event.target);

            if (!clickedMenu && !clickedToggle) {
                closeSourceMenu();
            }
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && els.cameraSourceModal && !els.cameraSourceModal.hidden) {
                event.preventDefault();
                closeCameraSourceModal();
                return;
            }
            if (event.key === 'Escape' && els.mediaSourceModal && !els.mediaSourceModal.hidden) {
                event.preventDefault();
                closeMediaSourceModal();
            }
        });
        if (els.addCameraSource) {
            els.addCameraSource.addEventListener('click', addCameraSourceFromDialog);
        }
        if (els.importMediaSource) {
            els.importMediaSource.addEventListener('click', importSelectedMediaSource);
        }
        if (els.deleteSelectedSourceScene) {
            els.deleteSelectedSourceScene.addEventListener('click', deleteSelectedSourceScene);
        }
        if (els.transitionCut) { els.transitionCut.addEventListener('click', () => commitPreviewToProgram('cut')); }
        if (els.transitionFade) { els.transitionFade.addEventListener('click', () => commitPreviewToProgram('fade')); }
        if (els.refreshDevices) {
            els.refreshDevices.addEventListener('click', () => {
                setDeviceStatus(getStudioString('refreshingDevices', 'Refreshing cameras and microphones…'), 'info');
                refreshDevices({ reason: 'manual' }).catch((error) => {
                    const message = (error && error.message) || getStudioString('devicesCouldNotBeRefreshed', 'Devices could not be refreshed.');
                    setDeviceStatus(message, 'error');
                    setStatus(message, 'error');
                });
            });
        }
        if (state.support.deviceChange && navigator.mediaDevices && typeof navigator.mediaDevices.addEventListener === 'function') {
            navigator.mediaDevices.addEventListener('devicechange', () => {
                setDeviceStatus(getStudioString('deviceChangeRefreshing', 'Device change detected. Refreshing cameras and microphones…'), 'info');
                refreshDevices({ reason: 'devicechange' }).catch(() => {});
            });
        }
        if (els.testCamera) { els.testCamera.addEventListener('click', testCameraDevice); }
        if (els.testMicrophone) { els.testMicrophone.addEventListener('click', testMicrophoneDevice); }
        if (els.cameraSelect) {
            els.cameraSelect.addEventListener('change', async () => {
                const source = getSelectedCameraSource();
                if (!source) { return; }
                const previous = source.deviceId;
                const next = els.cameraSelect.value;
                if (isSourceProtected(source.sourceId)) {
                    warnProtectedSource(source.sourceId);
                    els.cameraSelect.value = previous;
                    return;
                }
                if (next && isCameraDeviceAssigned(next, source.sourceId)) {
                    setDeviceStatus(getStudioString('deviceAlreadyAssigned', 'Device already assigned.'), 'warning');
                    els.cameraSelect.value = previous;
                    return;
                }
                const operationId = (source.reassignRequestId || 0) + 1;
                source.reassignRequestId = operationId;
                const previousLabel = source.deviceLabel;
                const requestedDeviceLabel = selectedDeviceLabel(els.cameraSelect, '');
                const previousHasLiveStream = hasLiveVideoTrack(source.stream);
                const previousStatus = previousHasLiveStream ? 'active' : source.status;
                const previousUnavailable = previousHasLiveStream ? false : source.unavailable;
                const previousError = source.error;
                if (!hasLiveVideoTrack(source.stream)) {
                    source.deviceId = next;
                    source.deviceLabel = selectedDeviceLabel(els.cameraSelect, '');
                    source.error = '';
                    source.unavailable = false;
                    source.status = 'off';
                    scheduleCameraSourceConfigurationSave();
                    updateActiveDeviceSummary();
                    renderCameraScenes();
                    renderSourceState();
                    return;
                }
                try {
                    const stream = await startCameraSource(source.sourceId, { force: true, deviceId: next, deviceLabel: requestedDeviceLabel, preserveOnFailure: true });
                    if (operationId !== source.reassignRequestId || !stream || source.stream !== stream) {
                        return;
                    }
                    source.error = '';
                    source.unavailable = false;
                    source.status = hasLiveVideoTrack(source.stream) ? 'active' : 'off';
                    scheduleCameraSourceConfigurationSave();
                    updateActiveDeviceSummary();
                    renderCameraScenes();
                    renderSourceState();
                } catch (error) {
                    if (operationId !== source.reassignRequestId) {
                        return;
                    }
                    source.deviceId = previous;
                    source.deviceLabel = previousLabel;
                    source.status = previousStatus;
                    source.unavailable = previousUnavailable;
                    source.error = previousError;
                    els.cameraSelect.value = previous;
                    setDeviceStatus(friendlyMediaError(error), 'error');
                    renderSelectedCameraControls();
                }
            });
        }
        if (els.selectedCameraName) {
            els.selectedCameraName.addEventListener('change', () => {
                const source = getSelectedCameraSource();
                if (!source) { return; }
                source.label = els.selectedCameraName.value.trim() || source.label;
                if (source.element) { source.element.setAttribute('aria-label', source.label); }
                scheduleCameraSourceConfigurationSave();
                renderCameraScenes();
                renderSourceState();
            });
        }
        if (els.micSelect) {
            els.micSelect.addEventListener('change', () => {
                const primary = primaryAudioInput();
                if (!primary) { return; }
                primary.deviceId = els.micSelect.value;
                primary.deviceLabel = els.micSelect.value ? selectedDeviceLabel(els.micSelect, '') : '';
                primary.error = '';
                primary.status = 'off';
                storageSet(MIC_STORAGE_KEY, primary.deviceId);
                saveAudioInputConfiguration();
                updateActiveDeviceSummary();
                startAudioInput(primary.id, { force: true, reason: 'microphone-change' }).catch((error) => {
                    const message = friendlyMediaError(error);
                    setDeviceStatus(message, 'error');
                    setStatus(message, 'error');
                    refreshAudioInputDiagnostics();
                });
            });
        }
        if (els.addAudioInput) {
            els.addAudioInput.addEventListener('click', () => {
                if (state.audioInputs.size >= MAX_AUDIO_INPUTS) {
                    setDeviceStatus(getStudioString('audioInputLimitReached', 'Studio supports up to 8 audio inputs in this phase.'), 'warning');
                    updateAddAudioInputAvailability();
                    return;
                }
                const device = autoAssignableAudioInputDevice();
                const input = createAudioInputSource({ deviceId: device ? device.deviceId : '', deviceLabel: device && device.label ? device.label : '' });
                if (!input) { return; }
                input.label = nextAudioInputDisplayName(input);
                const channel = ensureAudioInputMixerChannel(input.id);
                if (channel) { channel.label = input.label; }
                saveAudioInputConfiguration();
                renderAudioInputChannels();
                setDeviceStatus(getStudioString('audioInputAdded', 'Audio input added.'), 'success');
                const strip = els.audioInputChannels && els.audioInputChannels.querySelector('[data-audio-input-id="' + input.id + '"]');
                const focusTarget = strip && strip.querySelector('[data-audio-input-device], [data-audio-input-name]');
                if (focusTarget) { focusTarget.focus(); }
                if (input.deviceId) {
                    startAudioInput(input.id, { reason: 'add-audio-input' }).catch((error) => setDeviceStatus(friendlyMediaError(error), 'warning'));
                }
            });
        }
        if (els.audioMixer) {
            els.audioMixer.addEventListener('input', (event) => {
                const input = event.target.closest('[data-mixer-gain]');
                if (!input || !els.audioMixer.contains(input)) { return; }
                const mixer = ensureStudioAudioMixer();
                const channel = mixer && mixer.channels[input.dataset.mixerGain];
                if (!channel) { return; }
                channel.volume = (Number(input.value) || 0) / 100;
                const audioInput = state.audioInputs.get(channel.id);
                if (audioInput) { audioInput.volume = normalizeMixerVolume(channel.volume, 1); scheduleAudioInputConfigurationSave(); }
                applyMixerChannelGain(channel);
            });
            els.audioMixer.addEventListener('change', (event) => {
                const select = event.target.closest('[data-audio-input-device]');
                if (!select || !els.audioMixer.contains(select)) { return; }
                const input = state.audioInputs.get(select.dataset.audioInputDevice);
                if (!input) { return; }
                input.deviceId = select.value;
                input.deviceLabel = select.value ? selectedDeviceLabel(select, '') : '';
                input.error = '';
                input.status = 'off';
                if (input.isPrimary && els.micSelect) { els.micSelect.value = input.deviceId; }
                saveAudioInputConfiguration();
                refreshAudioInputDiagnostics();
                startAudioInput(input.id, { force: true, reason: 'audio-input-device-change' }).catch((error) => {
                    setDeviceStatus(friendlyMediaError(error), 'warning');
                    refreshAudioInputDiagnostics();
                });
            });
            els.audioMixer.addEventListener('blur', (event) => {
                const name = event.target.closest('[data-audio-input-name]');
                if (!name || !els.audioMixer.contains(name)) { return; }
                const input = state.audioInputs.get(name.dataset.audioInputName);
                if (!input) { return; }
                const inputIndex = Array.from(state.audioInputs.keys()).indexOf(input.id);
                input.label = sanitizeAudioInputLabel(name.value, Math.max(0, inputIndex));
                const channel = ensureAudioInputMixerChannel(input.id);
                if (channel) { channel.label = input.label; }
                saveAudioInputConfiguration();
                updateMixerUi();
            }, true);
            els.audioMixer.addEventListener('click', (event) => {
                const remove = event.target.closest('[data-remove-audio-input]');
                if (remove && els.audioMixer.contains(remove)) {
                    const input = state.audioInputs.get(remove.dataset.removeAudioInput);
                    if (input && input.isPrimary) {
                        studioDebugLog('[VH360 Studio] Refusing to remove the primary audio input', { inputId: input.id });
                        return;
                    }
                    if (input && !input.isPrimary) {
                        input.removed = true;
                        input.status = 'removed';
                        stopAudioInput(input.id);
                        removeMixerChannel(input.mixerChannelId);
                        state.audioInputs.delete(input.id);
                        enforcePrimaryAudioInputInvariant();
                        saveAudioInputConfiguration();
                        renderAudioInputChannels();
                        updateActiveDeviceSummary();
                        setDeviceStatus(getStudioString('audioInputRemoved', 'Audio input removed.'), 'success');
                        if (els.addAudioInput) { els.addAudioInput.focus(); }
                    }
                    return;
                }
                const button = event.target.closest('[data-mixer-mute]');
                if (!button || !els.audioMixer.contains(button)) { return; }
                const mixer = ensureStudioAudioMixer();
                const channel = mixer && mixer.channels[button.dataset.mixerMute];
                if (!channel) { return; }
                channel.muted = !channel.muted;
                const input = state.audioInputs.get(channel.id);
                if (input) {
                    input.muted = channel.muted;
                    input.status = channel.muted && hasLiveAudioTrack(input.stream) ? 'muted' : hasLiveAudioTrack(input.stream) ? 'active' : input.status || 'off';
                    saveAudioInputConfiguration();
                }
                updateMixerUi();
                applyMixerChannelGain(channel);
            });
        }
        if (els.qualitySelect) {
            els.qualitySelect.addEventListener('change', () => {
                updateQualityDetails();

                if (state.broadcastSession || isRecordingActive()) {
                    setStatus('Quality changes apply before going live or recording starts.', 'warning');
                    return;
                }

                applyProgramCanvasResolution();
            });
        }
        if (els.toggleMediaControls) { els.toggleMediaControls.addEventListener('click', toggleSelectedMediaControls); }
        if (els.mediaPlayPause) { els.mediaPlayPause.addEventListener('click', toggleSelectedMediaPlayback); }
        if (els.mediaRestart) { els.mediaRestart.addEventListener('click', restartSelectedMedia); }
        if (els.mediaLoop) { els.mediaLoop.addEventListener('change', toggleSelectedMediaLoop); }
        if (els.mediaSeek) { els.mediaSeek.addEventListener('input', seekSelectedMedia); }
        if (els.mediaFitMode) {
            els.mediaFitMode.addEventListener('change', () => {
                updateActiveMediaTransform({ fitMode: els.mediaFitMode.value });
            });
        }
        if (els.mediaScale) {
            els.mediaScale.addEventListener('input', () => {
                updateActiveMediaTransform({ scale: Number(els.mediaScale.value) || 100, fitMode: 'custom' });
            });
        }
        if (els.mediaPositionX) {
            els.mediaPositionX.addEventListener('input', () => {
                updateActiveMediaTransform({ x: Number(els.mediaPositionX.value) || 0, fitMode: 'custom' });
            });
        }
        if (els.mediaPositionY) {
            els.mediaPositionY.addEventListener('input', () => {
                updateActiveMediaTransform({ y: Number(els.mediaPositionY.value) || 0, fitMode: 'custom' });
            });
        }
        if (els.mediaResetTransform) { els.mediaResetTransform.addEventListener('click', resetActiveMediaTransform); }
        if (els.startRecording) { els.startRecording.addEventListener('click', startRecording); }
        if (els.stopRecording) { els.stopRecording.addEventListener('click', stopRecording); }
        if (els.retryChunks) { els.retryChunks.addEventListener('click', retryFailedChunks); }
        if (els.finalizeRecording) { els.finalizeRecording.addEventListener('click', finalizeRecording); }
        if (els.publishReplay) { els.publishReplay.addEventListener('click', publishReplay); }
        if (els.checkReplayStatus) { els.checkReplayStatus.addEventListener('click', checkReplayStatus); }
        if (els.broadcastMode) { els.broadcastMode.addEventListener('change', updateBroadcastRules); }
        if (els.selectCoverImage) { els.selectCoverImage.addEventListener('click', selectCoverImage); }
        if (els.coverImageFile) { els.coverImageFile.addEventListener('change', uploadSelectedCoverImage); }
        if (els.removeCoverImage) { els.removeCoverImage.addEventListener('click', () => setCoverImage(0, '', { clear: true })); }
        if (els.broadcastEveryoneHost) { els.broadcastEveryoneHost.addEventListener('change', updateBroadcastRules); }
        if (els.broadcastRequirePasscode) { els.broadcastRequirePasscode.addEventListener('change', updateBroadcastRules); }
        if (els.goLive) { els.goLive.addEventListener('click', goLive); }
        if (els.endLive) { els.endLive.addEventListener('click', endLive); }
        if (els.programEndLive) { els.programEndLive.addEventListener('click', endLive); }
        if (els.toggleMic) { els.toggleMic.addEventListener('click', toggleLiveAudio); }
        if (els.toggleVideo) { els.toggleVideo.addEventListener('click', toggleLiveVideo); }
        if (els.studioFullscreen) { els.studioFullscreen.addEventListener('click', toggleStudioFullscreen); }
        if (els.openStudioWindow) { els.openStudioWindow.addEventListener('click', openStudioWindow); }
        if (els.openViewerLink) { els.openViewerLink.addEventListener('click', openViewerLink); }
        if (els.copyViewerLink) { els.copyViewerLink.addEventListener('click', copyViewerLink); }
        document.addEventListener('fullscreenchange', updateFullscreenButton);
        updateFullscreenButton();
        root.addEventListener('vh360:agora-broadcaster:track-ended', (event) => {
            const detail = event.detail || {};
            studioDebugLog('[VH360 Studio] Agora local track ended', detail);
            if (state.broadcastEnding || !state.broadcastSession) {
                return;
            }
            if (detail.kind === 'audio') {
                state.liveAudioMuted = true;
                renderProgramLiveControls();
                setBroadcastStatus(getStudioString('liveMicrophoneDisconnected', 'Microphone input ended or was disconnected. Live audio may be unavailable.'), 'warning');
            } else if (detail.kind === 'video') {
                state.liveVideoMuted = true;
                renderProgramLiveControls();
                setBroadcastStatus('Program video output ended. Restart the live broadcast if viewers cannot see video.', 'warning');
            }
        });
        root.addEventListener('vh360:agora-broadcaster:connection-state-change', (event) => {
            const detail = event.detail || {};
            studioDebugLog('[VH360 Studio] Agora connection state changed', detail);
            if (detail.current) {
                state.lastAgoraConnectionState = detail.current;
                state.broadcastReady = Boolean(state.broadcastSession) && detail.current === 'CONNECTED';
                renderTransitionButtons();
                renderProgramLiveControls();
                if (detail.current === 'CONNECTED') {
                    updateLiveAudioInputWarning();
                    if (!state.liveAudioWarningActive) {
                        setBroadcastStatus(getStudioString('liveConnectionState', 'Live connection: {state}{reason}').replace('{state}', detail.current).replace('{reason}', detail.reason ? ' (' + detail.reason + ')' : ''), 'success');
                    }
                } else {
                    setBroadcastStatus(getStudioString('liveConnectionState', 'Live connection: {state}{reason}').replace('{state}', detail.current).replace('{reason}', detail.reason ? ' (' + detail.reason + ')' : ''), 'info');
                }
            }
        });
        window.addEventListener('pagehide', (event) => {
            flushCameraSourceConfigurationSave();
            flushAudioInputConfigurationSave();
            if (event.persisted) {
                state.pageStoredInBackForwardCache = true;
                return;
            }
            endBroadcastKeepalive();
        });
        window.addEventListener('pageshow', (event) => {
            if (event.persisted) {
                state.pageStoredInBackForwardCache = false;
                state.studioDocumentHidden = document.hidden;
                if (!document.hidden) {
                    queueStudioVisibilityRestore().catch(() => {});
                }
            }
        });
        window.addEventListener('beforeunload', (event) => {
            if (hasUnsafeRecordingWork()) {
                event.preventDefault();
                event.returnValue = '';
                return;
            }
            cleanup({ releaseMediaSources: true });
        });
        document.addEventListener('visibilitychange', handleStudioVisibilityChange);
    }

    window.VH360StudioCompositor = {
        registerLayer(id, order, drawCallback) {
            if (!id || typeof drawCallback !== 'function') { return false; }
            programRenderLayers.set(id, { id, order: Number(order) || 50, draw: drawCallback });
            requestFreshProgramFrame();
            return true;
        },
        unregisterLayer(id) {
            programRenderLayers.delete(id);
            requestFreshProgramFrame();
        },
        requestFrame() { requestFreshProgramFrame(); },
        getOutputSize() {
            return { width: state.programWidth || 1920, height: state.programHeight || 1080, fps: state.programFrameRate || 30 };
        },
        hasPreviewSource() { return Boolean(state.previewSource); },
        hasProgramOutput() { return hasProgramOutput(); },
        getSourceSummary() {
            return {
                preview: sourceSummary(state.previewSource),
                program: Object.assign(sourceSummary(state.programSource), { hasOutput: hasProgramOutput() }),
            };
        },
    };

    loadSavedDevicePreferences();
    applyStudioWindowMode();
    detectSupport();
    renderSupportChecks();
    updateReadinessStatus();
    renderDeviceReadinessDetails().catch(() => {});
    updateQualityDetails();
    applyProgramCanvasResolution();
    updatePublishingButtons();
    renderRecordingState();
    updateBroadcastRules();
    renderCameraScenes();
    renderSourceState();
    renderProgramState();
    renderProgramLiveControls();
    setProgramDiagnostics('Program active');
    renderTransitionButtons();
    renderSceneControls();
    updateViewerLinkControls();
    bindEvents();
    loadPersistentMediaSources();
}());
