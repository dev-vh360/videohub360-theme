(function () {
    'use strict';

    const root = document.querySelector('[data-vh360-studio-dashboard]');
    if (!root || !window.vh360StudioDashboard) {
        return;
    }

    const config = window.vh360StudioDashboard;
    const strings = config.strings || {};
    const supportLabels = config.supportLabels || {};
    const CHUNK_UPLOAD_CONCURRENCY = 2;
    const state = {
        cameraStream: null,
        screenStream: null,
        micStream: null,
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
        programFrameRate: 30,
        programWidth: 1920,
        programHeight: 1080,
        transitioning: false,
        broadcastStarting: false,
        broadcastReady: false,
        broadcastEnding: false,
        programSwitching: false,
        lastAgoraConnectionState: '',
        mediaControlsExpanded: false,
        lastRestError: '',
        mediaSourceUploadActive: false,
        mediaSourceModalTrigger: null,
        publishPollTimer: null,
        publishPollAttempts: 0,
        publishStatusCheckInFlight: false,
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
        cameraPreview: root.querySelector('[data-camera-preview]'),
        screenPreview: root.querySelector('[data-screen-preview]'),
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
        recentReplaysBody: root.querySelector('[data-recent-replays-body]'),
        recentJobsTechnicalBody: root.querySelector('[data-recent-jobs-technical-body]'),
        emptyReplays: root.querySelector('[data-empty-replays]'),
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
        broadcastStatus: root.querySelector('[data-broadcast-status]'),
        agoraLocalPreview: root.querySelector('[data-agora-local-preview]'),
        viewerLinkWrap: root.querySelector('[data-viewer-link-wrap]'),
        openViewerLink: root.querySelector('[data-open-viewer-link]'),
        copyViewerLink: root.querySelector('[data-copy-viewer-link]'),
        studioFullscreen: root.querySelector('[data-studio-fullscreen]'),
        copyViewerFeedback: root.querySelector('[data-copy-viewer-feedback]'),
        mediaSourceMenuToggle: root.querySelector('[data-toggle-media-source-menu]'),
        mediaSourceMenu: root.querySelector('[data-media-source-menu]'),
        openLocalMediaSource: root.querySelector('[data-open-local-media-source]'),
        openUploadMediaSource: root.querySelector('[data-open-upload-media-source]'),
        deleteSelectedMediaScene: root.querySelector('[data-delete-selected-media-scene]'),
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

    function hasProgramOutput() {
        return Boolean(state.programSource && (state.programStream || isMediaSource(state.programSource)));
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
            return 'Screen Share';
        }

        if (sourceId === 'camera') {
            return 'Camera';
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
        if (!state.cameraStream || !state.cameraStream.getVideoTracks().length || state.cameraStream.getVideoTracks()[0].readyState === 'ended') {
            await startPreview(false);
        }
        return state.cameraStream;
    }

    async function setPreviewSource(sourceId) {
        if (isMediaSource(sourceId)) {
            const mediaSource = getMediaSource(sourceId);

            if (!mediaSource) {
                setStatus('Media source is no longer available.', 'warning');
                return;
            }

            state.previewSource = sourceId;

            if (mediaSource.type === 'video') {
                mediaSource.element.play().catch(() => {});
            }

            renderPreviewState();
            renderSourceState();
            setStatus(sourceLabel(sourceId) + ' staged in Preview.', 'success');
            return;
        }

        const stream = await getSourceStream(sourceId);
        if (!stream) {
            return;
        }
        state.previewSource = sourceId;
        renderPreviewState();
        renderSourceState();
        setStatus(sourceLabel(sourceId) + ' staged in Preview.', 'success');
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

    function fallbackPreviewSource(endedSource) {
        if (endedSource === 'screen' && state.cameraStream) {
            return 'camera';
        }
        if (endedSource === 'camera' && state.screenStream) {
            return 'screen';
        }
        return null;
    }

    function renderPreviewState() {
        root.dataset.previewSource = state.previewSource || '';
        root.classList.toggle('is-preview-source-camera', state.previewSource === 'camera');
        root.classList.toggle('is-preview-source-screen', state.previewSource === 'screen');
        root.classList.toggle('is-preview-source-media', isMediaSource(state.previewSource));

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
        updateOperatorStatus();
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

    function renderSceneControls() {
        if (!els.deleteSelectedMediaScene) {
            return;
        }

        const selectedMedia = getSelectedMediaSource();
        els.deleteSelectedMediaScene.disabled = !selectedMedia;
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
        if (!state.programAnimationFrame) {
            drawProgramFrame();
        }
        return state.programOutputStream;
    }

    function stopProgramCompositor(options = {}) {
        if (state.programAnimationFrame) {
            window.cancelAnimationFrame(state.programAnimationFrame);
            state.programAnimationFrame = null;
        }
        if (options.stopTracks && state.programOutputStream) {
            state.programOutputStream.getTracks().forEach((track) => track.stop());
        }
        if (options.clearStream) {
            state.programOutputStream = null;
        }
    }

    function drawProgramFrame() {
        if (!state.programContext || !state.programCanvas) {
            state.programAnimationFrame = null;
            return;
        }
        const context = state.programContext;
        const width = state.programCanvas.width;
        const height = state.programCanvas.height;
        context.fillStyle = '#020617';
        context.fillRect(0, 0, width, height);
        if (state.programSource === 'camera') {
            if (els.cameraPreview && els.cameraPreview.readyState >= 2) {
                drawVideoContain(context, els.cameraPreview, width, height, true);
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
        state.programAnimationFrame = window.requestAnimationFrame(drawProgramFrame);
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

    function renderProgramState() {
        const active = hasProgramOutput();

        if (els.programCanvas) {
            els.programCanvas.classList.toggle('vh360-studio-program-canvas--screen', state.programSource === 'screen');
        }
        root.classList.toggle('is-program-active', active);
        root.classList.toggle('is-program-source-camera', state.programSource === 'camera');
        root.classList.toggle('is-program-source-screen', state.programSource === 'screen');
        root.classList.toggle('is-program-source-media', isMediaSource(state.programSource));
        if (els.programEmpty) {
            els.programEmpty.hidden = active;
        }
        updateOperatorStatus();
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

    async function ensureMicStream() {
        const existingAudio = state.cameraStream && state.cameraStream.getAudioTracks().find((track) => track.readyState !== 'ended');
        if (existingAudio) {
            return state.cameraStream;
        }
        const micAudio = state.micStream && state.micStream.getAudioTracks().find((track) => track.readyState !== 'ended');
        if (micAudio) {
            return state.micStream;
        }
        if (!state.support.getUserMedia) {
            return null;
        }
        state.micStream = await navigator.mediaDevices.getUserMedia({
            audio: state.selectedMicId ? { deviceId: { exact: state.selectedMicId } } : true,
            video: false,
        });
        return state.micStream;
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
        let audioSource = state.broadcastSession && state.broadcastSession.getLocalMediaStream ? state.broadcastSession.getLocalMediaStream() : null;
        if (!audioSource || !audioSource.getAudioTracks().some((track) => track.readyState !== 'ended')) {
            audioSource = state.cameraStream;
        }
        if (!audioSource || !audioSource.getAudioTracks().some((track) => track.readyState !== 'ended')) {
            audioSource = await ensureMicStream();
        }
        if (audioSource) {
            audioSource.getAudioTracks().forEach((track) => {
                if (track.readyState !== 'ended') {
                    tracks.push(track);
                }
            });
        }
        return tracks.length ? new MediaStream(tracks) : null;
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
            issues.push('Use HTTPS or localhost for Studio recording.');
        }
        if (!state.support.mediaDevices || !state.support.getUserMedia) {
            issues.push('Camera or microphone access is unavailable. Allow browser permissions, then refresh.');
        }
        if (!state.support.getDisplayMedia) {
            issues.push('Screen sharing is unavailable in this browser.');
        }
        if (!state.support.mediaRecorder) {
            issues.push('This browser does not support recording. Try Chrome, Edge, or Safari.');
        }
        if (!state.support.canvasContext || !state.support.canvasCapture) {
            issues.push('Program canvas recording is unsupported in this browser.');
        }
        if (!state.support.mimeTypes || !state.support.mimeTypes.length) {
            issues.push('No supported recording format was detected.');
        }
        return issues;
    }

    function renderReadinessSummary() {
        const issues = readinessIssues();
        if (els.readinessSummary) {
            els.readinessSummary.dataset.statusType = issues.length ? 'warning' : 'success';
        }
        if (els.readinessHeading) {
            els.readinessHeading.textContent = issues.length ? 'Studio needs attention.' : 'Ready to go live.';
        }
        if (els.readinessMessage) {
            els.readinessMessage.textContent = issues.length ? 'Resolve the items below, then refresh Studio if needed.' : 'Camera, microphone, screen share, and recording are supported.';
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
        mimeItem.innerHTML = '<span>' + escapeHtml(supportLabels.mimeTypes || 'Formats') + '</span><strong>' + escapeHtml(state.support.mimeTypes.length ? state.support.mimeTypes.join(', ') : strings.notSupported) + '</strong>';
        els.supportChecks.appendChild(mimeItem);

        const preferred = preferredMimeType();
        if (isWebmMime(preferred)) {
            const warning = document.createElement('li');
            warning.className = 'is-unsupported';
            warning.innerHTML = '<span>' + escapeHtml('Recording format') + '</span><strong>' + escapeHtml(webmFallbackWarning()) + '</strong>';
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

        if (state.cameraStream && isSourceProtected('camera')) {
            warnProtectedSource('camera');
            return;
        }

        stopPreview({ force: true });

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
            setShellClass('is-preview-active', true);
            if (state.programSource === 'camera') {
                state.programStream = state.cameraStream;
                renderProgramState();
            }
            if (updateSelection) {
                state.previewSource = 'camera';
                renderPreviewState();
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

    function stopPreview(options = {}) {
        if (!options.force && isSourceProtected('camera')) {
            warnProtectedSource('camera');
            return false;
        }
        stopStream(state.cameraStream);
        state.cameraStream = null;
        if (els.cameraPreview) {
            els.cameraPreview.srcObject = null;
        }
        teardownAudioMeter();
        setShellClass('is-preview-active', false);
        if (state.previewSource === 'camera') {
            state.previewSource = fallbackPreviewSource('camera');
            renderPreviewState();
            renderSourceState();
        }
        if (state.programSource === 'camera') {
            state.programSource = null;
            state.programStream = null;
            renderProgramState();
        }
        return true;
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

        if (state.screenStream && isSourceProtected('screen')) {
            warnProtectedSource('screen');
            return;
        }

        stopScreenPreview({ force: true });

        try {
            let captureController = null;
            const displayOptions = {
                video: true,
                audio: false,
                surfaceSwitching: 'include',
                selfBrowserSurface: 'exclude',
            };
            if ('CaptureController' in window) {
                captureController = new window.CaptureController();
                displayOptions.controller = captureController;
            }
            state.screenStream = await navigator.mediaDevices.getDisplayMedia(displayOptions);
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
                renderPreviewState();
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
        state.screenStream = null;
        if (els.screenPreview) {
            els.screenPreview.srcObject = null;
        }
        setShellClass('is-screen-active', false);
        if (state.previewSource === 'screen') {
            state.previewSource = fallbackPreviewSource('screen');
            renderPreviewState();
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
        state.screenStream = null;
        if (els.screenPreview) {
            els.screenPreview.srcObject = null;
        }
        setShellClass('is-screen-active', false);
        if (state.previewSource === 'screen') {
            state.previewSource = fallbackPreviewSource('screen');
            renderPreviewState();
            renderSourceState();
        }
        if (state.programSource === 'screen') {
            state.programSource = null;
            state.programStream = null;
            renderProgramState();
            setStatus('Screen Share ended. Program source was cleared.', 'warning');
            if (state.broadcastSession && state.cameraStream) {
                state.previewSource = 'camera';
                await commitPreviewToProgram('cut');
                setBroadcastStatus('Screen Share ended. Program fell back to Camera.', 'warning');
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
        video.loop = true;
        video.controls = false;
        video.preload = 'metadata';
        video.src = source.url;
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

    function toggleMediaSourceMenu() {
        if (!els.mediaSourceMenu || !els.mediaSourceMenuToggle) {
            return;
        }

        const open = els.mediaSourceMenu.hidden;
        els.mediaSourceMenu.hidden = !open;
        els.mediaSourceMenuToggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    }

    function closeMediaSourceMenu() {
        if (els.mediaSourceMenu) {
            els.mediaSourceMenu.hidden = true;
        }

        if (els.mediaSourceMenuToggle) {
            els.mediaSourceMenuToggle.setAttribute('aria-expanded', 'false');
        }
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
                ? 'Local media is available for this Studio session only and is not uploaded to WordPress.'
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
        renderPreviewState();
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

        renderPreviewState();
        renderProgramState();
        renderSourceState();
        renderSceneControls();
        setStatus('Local media removed from this Studio session.', 'success');
    }

    async function deleteSelectedMediaScene() {
        const mediaSource = getSelectedMediaSource();

        if (!mediaSource) {
            setStatus('Select a media scene before deleting.', 'warning');
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
        const preset = getSelectedPreset();
        const resolution = preset.resolution ? preset.resolution.width + '×' + preset.resolution.height : '';
        const bitrate = preset.video_bitrate ? ' · ~' + Math.round(Number(preset.video_bitrate) / 1000000 * 10) / 10 + ' Mbps video' : '';
        els.qualityDetails.textContent = preset.label + ' · ' + resolution + ' · ' + preset.fps + 'fps' + bitrate + '. Higher quality creates larger files and longer uploads.';
    }



    function getPresetResolution() {
        const preset = getSelectedPreset();

        if (preset && preset.resolution && preset.resolution.width && preset.resolution.height) {
            return {
                width: Number(preset.resolution.width) || 1920,
                height: Number(preset.resolution.height) || 1080,
                fps: Number(preset.fps) || 30,
            };
        }

        return {
            width: 1920,
            height: 1080,
            fps: 30,
        };
    }

    function applyProgramCanvasResolution(options = {}) {
        const resolution = getPresetResolution();
        const activeOutput = state.broadcastSession || isRecordingActive();

        if (activeOutput && !options.force) {
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

    function recordingMimeCandidates() {
        return [
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
        appendRecentJob(job);
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
        try {
            if (hasProgramOutput()) {
                applyProgramCanvasResolution();
                state.recordingStream = await buildRecordingStreamFromProgram();
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
            const preset = getSelectedPreset();
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
            if (isWebmMime(state.selectedMimeType)) {
                setRecordingStatus(webmFallbackWarning(), 'warning');
            } else {
                setRecordingStatus(strings.recordingActive, 'success');
            }
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
        setRecordingStatus('Large recording data was split into smaller upload chunks.', 'info');
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
            setRecordingStatus('Direct Publitio upload limit was exceeded. Use server relay mode for very long recordings.', 'warning');
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
            setRecordingStatus(state.recordingStoppedAt ? strings.uploadingChunk : strings.recordingActive, 'info');
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
            setRecordingStatus('Retrying server stop confirmation…', 'info');
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
            setShellClass('is-recording', false);
            setRecordingStatus(isPublitioDirectMode() ? 'Recording stopped. Ready to upload directly to Publitio.' : strings.uploadingChunk, 'info');
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
                setRecordingStatus('Recording stopped. Ready to upload directly to Publitio.', 'success');
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
        setRecordingStatus(strings.uploadRetry, 'info');
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
            setRecordingStatus('Confirm server stop before preparing the replay.', 'warning');
            return;
        }
        if (state.failedChunks.size || state.pendingUploads.size) {
            setRecordingStatus('Retry failed chunks during this session before preparing the replay.', 'warning');
            return;
        }
        if (!state.finalChunkCount) {
            setRecordingStatus('No recording chunks were captured. Start a new recording and try again.', 'error');
            return;
        }
        try {
            state.finalizeInProgress = true;
            if (els.recordingFinalizeStatus) { els.recordingFinalizeStatus.textContent = strings.finalizing; }
            if (els.finalizeRecording) { els.finalizeRecording.disabled = true; }
            const job = await api('/jobs/' + state.activeJobId + '/recording/finalize', { method: 'POST', headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': config.nonce }, body: JSON.stringify({ expected_chunks: state.finalChunkCount }) });
            state.currentJobStatus = job.status || 'processing';
            state.currentStorageProvider = job.storage_provider || state.currentStorageProvider;
            setRecordingStatus('Replay prepared. You can publish it now.', 'success');
            appendRecentJob(job);
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

    function updatePublishingButtons() {
        const directReady = isPublitioDirectMode() && state.recordingStoppedAt && state.serverStopConfirmed && state.directUploadParts.size && state.directUploadAvailable && state.currentJobStatus !== 'ready';
        const canPublish = Boolean(state.activeJobId) && (state.currentJobStatus === 'processing' || directReady);
        if (els.publishReplay) {
            els.publishReplay.hidden = !canPublish;
            els.publishReplay.disabled = !canPublish;
        }
        if (els.checkReplayStatus) {
            const canCheck = Boolean(state.activeJobId);
            els.checkReplayStatus.hidden = !canCheck;
            els.checkReplayStatus.disabled = !canCheck || state.publishStatusCheckInFlight;
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
                    setPublishingStatus((strings.publitioDirectUploading || 'Uploading directly to Publitio…') + ' ' + Math.round((event.loaded / event.total) * 100) + '%', 'info');
                }
            };
            xhr.onload = () => {
                const payload = xhr.response || {};
                if (xhr.status >= 200 && xhr.status < 300) {
                    resolve(payload);
                    return;
                }
                reject(new Error((payload && (payload.message || payload.error || payload.msg)) || 'Publitio direct upload failed.'));
            };
            xhr.onerror = () => reject(new Error('Publitio direct upload failed before completion.'));
            xhr.ontimeout = () => reject(new Error('Publitio direct upload timed out.'));
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
            throw new Error('Local recording data is unavailable for direct upload.');
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

        setPublishingStatus(strings.publitioDirectUploading || 'Uploading directly to Publitio…', 'info');
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
            throw new Error('Publitio direct upload did not return a file ID.');
        }
        setPublishingStatus(strings.publitioDirectVerifying || 'Publitio upload complete. Verifying replay…', 'info');
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
        if (!state.activeJobId) {
            return;
        }
        if (els.publishReplay) { els.publishReplay.disabled = true; }
        setPublishingStatus(strings.publishingReplay, 'info');
        try {
            let result;
            if (canDirectUploadToPublitio()) {
                state.directUploadInProgress = true;
                result = await publishReplayViaDirectPublitio();
            } else if (isPublitioDirectMode()) {
                throw new Error('Direct Publitio upload is enabled, but the local recording Blob is unavailable or over the direct upload limit. Retry while the recording is still in this browser, or switch to server relay for the next recording.');
            } else {
                result = await publishReplayViaServerRelay();
            }
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
                setPublishingStatus(result.message || strings.publishProcessing || 'Replay uploaded to Publitio. Waiting for processing.', 'info');
            }
            appendRecentJob(Object.assign({}, result, { id: result.id || result.job_id || state.activeJobId, status: state.currentJobStatus, replay_url: publicReplayUrl, playback_url: rawPlaybackUrl }));
            if (shouldPollPublishStatus(result)) {
                startPublishPolling();
            }
        } catch (error) {
            stopPublishPolling();
            setPublishingStatus(error.message || strings.publishFailed, 'error');
        } finally {
            state.directUploadInProgress = false;
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
            const rawPlaybackUrl = result.playback_url || '';
            const publicReplayUrl = resolvePublicReplayUrl(result);
            const published = hasPublicReplay(result);
            state.currentJobStatus = published ? 'ready' : (isPublishFailure(result) ? 'failed' : (result.job_status || result.status || result.publish_provider_status || state.currentJobStatus));
            renderReplayRawUrl(rawPlaybackUrl);
            renderReplayLink(publicReplayUrl);
            if (published) {
                setPublishingStatus(strings.publishComplete, 'success');
                appendRecentJob(Object.assign({}, result, { id: result.id || result.job_id || state.activeJobId, status: 'ready', replay_url: publicReplayUrl, playback_url: rawPlaybackUrl }));
            } else if (isPublishFailure(result)) {
                setPublishingStatus(result.error_message || result.message || strings.publishFailed, 'error');
            } else if (shouldPollPublishStatus(result)) {
                setPublishingStatus(result.message || strings.publishProcessing || 'Replay uploaded to Publitio. Waiting for processing.', 'info');
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

    function friendlyJobStatus(job) {
        if (!job) {
            return '—';
        }
        if (job.error_message || job.status === 'failed') {
            return 'Needs attention';
        }
        if (job.replay_video_id || job.replay_url || 'ready' === String(job.status || job.job_status || '').toLowerCase()) {
            return 'Published';
        }
        const status = job.status || job.job_status || '';
        const labels = {
            created: 'Created',
            recording: 'Recording',
            uploading: 'Uploading',
            processing: 'Preparing',
            ready: 'Ready',
            failed: 'Needs attention',
            cancelled: 'Cancelled',
        };
        return labels[status] || (status ? status.charAt(0).toUpperCase() + status.slice(1).replace(/_/g, ' ') : '—');
    }

    function jobRecordingLabel(job) {
        if (job && job.display_title) {
            return job.display_title;
        }
        if (job && job.replay_title) {
            return job.replay_title;
        }
        if (job && job.title) {
            return job.title;
        }

        return 'Studio replay';
    }

    function formatFriendlyDate(value) {
        if (!value) {
            return '—';
        }
        const normalized = String(value).indexOf('T') === -1 ? String(value).replace(' ', 'T') : String(value);
        const date = new Date(normalized);
        if (Number.isNaN(date.getTime())) {
            return value;
        }
        const now = new Date();
        const today = new Date(now.getFullYear(), now.getMonth(), now.getDate());
        const target = new Date(date.getFullYear(), date.getMonth(), date.getDate());
        const diffDays = Math.round((today - target) / 86400000);
        const time = date.toLocaleTimeString(undefined, { hour: 'numeric', minute: '2-digit' });
        if (diffDays === 0) {
            return 'Today ' + time;
        }
        if (diffDays === 1) {
            return 'Yesterday ' + time;
        }
        return date.toLocaleDateString(undefined, { month: 'short', day: 'numeric', year: 'numeric' });
    }

    function setButtonVisibility(button, visible, enabled) {
        if (!button) {
            return;
        }
        button.hidden = !visible;
        button.disabled = !visible || !enabled;
    }

    function findJobRow(tbody, rowId) {
        if (!tbody || !rowId) {
            return null;
        }
        return Array.from(tbody.querySelectorAll('tr')).find((item) => item.dataset.jobId === rowId) || null;
    }

    function recentReplayCellText(row, index) {
        if (!row || !row.children || !row.children[index]) {
            return '';
        }
        return row.children[index].textContent.trim();
    }

    function appendRecentJob(job) {
        if (!job) {
            return;
        }
        const rowId = String(job.id || '');
        const replayUrl = job.replay_url || job.permalink || '';

        if (els.recentReplaysBody) {
            let row = findJobRow(els.recentReplaysBody, rowId) || document.createElement('tr');
            if (rowId) {
                row.dataset.jobId = rowId;
            }
            const replayCell = replayUrl
                ? '<a href="' + escapeHtml(replayUrl) + '" target="_blank" rel="noopener noreferrer">Open replay</a>'
                : '—';
            const existingTitle = recentReplayCellText(row, 0);
            const incomingTitle = jobRecordingLabel(job);
            const finalTitle = incomingTitle && incomingTitle !== 'Studio replay' ? incomingTitle : (existingTitle || incomingTitle || 'Studio replay');
            const existingCreated = recentReplayCellText(row, 2);
            const finalCreated = job.created_at ? formatFriendlyDate(job.created_at) : (existingCreated || '—');
            row.innerHTML = '<td>' + escapeHtml(finalTitle) + '</td>' +
                '<td>' + escapeHtml(friendlyJobStatus(job)) + '</td>' +
                '<td>' + escapeHtml(finalCreated) + '</td>' +
                '<td>' + replayCell + '</td>';
            if (!row.parentNode) {
                els.recentReplaysBody.prepend(row);
            }
            if (els.emptyReplays) {
                els.emptyReplays.hidden = true;
            }
        }

        if (els.recentJobsTechnicalBody) {
            let row = findJobRow(els.recentJobsTechnicalBody, rowId) || document.createElement('tr');
            if (rowId) {
                row.dataset.jobId = rowId;
            }
            const replayCell = replayUrl
                ? '<a href="' + escapeHtml(replayUrl) + '">' + escapeHtml(job.replay_video_id ? String(job.replay_video_id) : 'Open replay') + '</a>'
                : (job.replay_video_id && 'ready' === String(job.status || job.job_status || '').toLowerCase() ? escapeHtml(String(job.replay_video_id)) : '—');
            row.innerHTML = '<td>' + escapeHtml(String(job.id || '')) + '</td>' +
                '<td>' + escapeHtml(job.room_id || '') + '</td>' +
                '<td>' + escapeHtml(job.status || job.job_status || '') + '</td>' +
                '<td>' + escapeHtml(job.created_at || '') + '</td>' +
                '<td>' + escapeHtml(formatBytes(job.file_size)) + '</td>' +
                '<td>' + escapeHtml(job.mime_type || '—') + '</td>' +
                '<td>' + escapeHtml(job.assembled_at || '—') + '</td>' +
                '<td>' + escapeHtml(job.temp_expires_at || '—') + '</td>' +
                '<td>' + escapeHtml(job.publish_provider_status || '—') + '</td>' +
                '<td>' + replayCell + '</td>' +
                '<td>' + escapeHtml(job.error_message || '—') + '</td>';
            if (!row.parentNode) {
                els.recentJobsTechnicalBody.prepend(row);
            }
            if (els.emptyJobs) {
                els.emptyJobs.hidden = true;
            }
        }
    }

    function formatBytes(bytes) {
        const value = Number(bytes);
        if (!value || value < 0) {
            return '—';
        }
        const units = ['B', 'KB', 'MB', 'GB'];
        let size = value;
        let unit = 0;
        while (size >= 1024 && unit < units.length - 1) {
            size /= 1024;
            unit++;
        }
        return (unit === 0 ? String(Math.round(size)) : size.toFixed(size >= 10 ? 1 : 2).replace(/\\.0+$/, '')) + ' ' + units[unit];
    }

    function cleanup(options = {}) {
        const canStopProgramOutput = !state.broadcastSession && !isRecordingActive();
        const releaseMediaSources = options.releaseMediaSources === true;

        stopPreview({ force: true });
        stopScreenPreview({ force: true });
        stopProgramCompositor({ stopTracks: canStopProgramOutput, clearStream: canStopProgramOutput });
        stopStream(state.micStream);
        state.micStream = null;

        if (canStopProgramOutput && releaseMediaSources) {
            clearLocalMediaSources();
            state.previewSource = null;
            state.programSource = null;
            state.programStream = null;
            renderPreviewState();
            renderProgramState();
            renderSourceState();
            renderSceneControls();
        }
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
            setBroadcastStatus('Stop browser recording before going live, then start recording again after Agora is live so Studio can record the active broadcast tracks.', 'error');
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
                videoConfig: agoraVideoConfigFromPreset(),
                initialVideoMediaStreamTrack: state.programOutputStream ? state.programOutputStream.getVideoTracks()[0] : null,
                initialVideoSource: state.programSource || '',
            });
            await session.start();
            state.broadcastSession = session;
            updateViewerLinkControls();
            state.broadcastReady = true;
            state.broadcastStarting = false;
            renderTransitionButtons();
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
                setBroadcastStatus('Recording could not be stopped cleanly. Stop recording before ending live.', 'error');
                return;
            }
            if (state.recordingStoppedAt && !state.serverStopConfirmed) {
                setRecordingStatus(isPublitioDirectMode() ? 'Recording stopped. Ready to upload directly to Publitio.' : strings.uploadingChunk, 'info');
                finishRecordingUploadsAndConfirmStop().catch(() => {});
            }
        } else if (state.recordingStoppedAt && !state.serverStopConfirmed) {
            setRecordingStatus(isPublitioDirectMode() ? 'Recording stopped. Ready to upload directly to Publitio.' : strings.uploadingChunk, 'info');
            finishRecordingUploadsAndConfirmStop().catch(() => {});
        }
        if (state.heartbeatTimer) {
            window.clearInterval(state.heartbeatTimer);
            state.heartbeatTimer = null;
        }
        if (state.broadcastSession) {
            await state.broadcastSession.stop();
            state.broadcastSession = null;
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
                setBroadcastStatus('Local broadcast stopped, but WordPress could not mark the public livestream ended: ' + ((error && error.message) || 'Unknown error'), 'error');
                return;
            }
        }
        state.broadcastEnding = false;
        state.broadcastReady = false;
        if (els.goLive) els.goLive.disabled = false;
        if (els.endLive) els.endLive.disabled = true;
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

    function bindEvents() {
        if (els.sceneList) {
            els.sceneList.addEventListener('click', (event) => {
                const button = event.target.closest('[data-scene-source]');
                if (!button || !els.sceneList.contains(button)) {
                    return;
                }

                const sourceId = button.dataset.sceneSource || '';
                selectSceneSource(sourceId);
                setPreviewSource(sourceId);
            });
        }
        els.closeMediaSourceModal.forEach((button) => {
            button.addEventListener('click', closeMediaSourceModal);
        });
        if (els.persistentMediaSourceInput) {
            els.persistentMediaSourceInput.addEventListener('change', handlePersistentMediaFileSelected);
        }
        if (els.mediaSourceMenuToggle) {
            els.mediaSourceMenuToggle.addEventListener('click', (event) => {
                event.stopPropagation();
                toggleMediaSourceMenu();
            });
        }
        if (els.openLocalMediaSource) {
            els.openLocalMediaSource.addEventListener('click', () => {
                closeMediaSourceMenu();
                openMediaSourceModal('local');
            });
        }
        if (els.openUploadMediaSource) {
            els.openUploadMediaSource.addEventListener('click', () => {
                closeMediaSourceMenu();
                openMediaSourceModal('upload');
            });
        }
        document.addEventListener('click', (event) => {
            if (!els.mediaSourceMenu || !els.mediaSourceMenuToggle) {
                return;
            }

            const clickedMenu = els.mediaSourceMenu.contains(event.target);
            const clickedToggle = els.mediaSourceMenuToggle.contains(event.target);

            if (!clickedMenu && !clickedToggle) {
                closeMediaSourceMenu();
            }
        });
        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && els.mediaSourceModal && !els.mediaSourceModal.hidden) {
                event.preventDefault();
                closeMediaSourceModal();
            }
        });
        if (els.importMediaSource) {
            els.importMediaSource.addEventListener('click', importSelectedMediaSource);
        }
        if (els.deleteSelectedMediaScene) {
            els.deleteSelectedMediaScene.addEventListener('click', deleteSelectedMediaScene);
        }
        if (els.transitionCut) { els.transitionCut.addEventListener('click', () => commitPreviewToProgram('cut')); }
        if (els.transitionFade) { els.transitionFade.addEventListener('click', () => commitPreviewToProgram('fade')); }
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
        if (els.studioFullscreen) { els.studioFullscreen.addEventListener('click', toggleStudioFullscreen); }
        if (els.openViewerLink) { els.openViewerLink.addEventListener('click', openViewerLink); }
        if (els.copyViewerLink) { els.copyViewerLink.addEventListener('click', copyViewerLink); }
        document.addEventListener('fullscreenchange', updateFullscreenButton);
        updateFullscreenButton();
        root.addEventListener('vh360:agora-broadcaster:connection-state-change', (event) => {
            const detail = event.detail || {};
            studioDebugLog('[VH360 Studio] Agora connection state changed', detail);
            if (detail.current) {
                state.lastAgoraConnectionState = detail.current;
                state.broadcastReady = Boolean(state.broadcastSession) && detail.current === 'CONNECTED';
                renderTransitionButtons();
                setBroadcastStatus('Agora connection: ' + detail.current + (detail.reason ? ' (' + detail.reason + ')' : ''), detail.current === 'CONNECTED' ? 'success' : 'info');
            }
        });
        window.addEventListener('pagehide', () => {
            endBroadcastKeepalive();
            if (!isRecordingActive() && !state.broadcastSession) {
                cleanup({ releaseMediaSources: true });
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
        document.addEventListener('visibilitychange', () => {
            if (document.hidden && !isRecordingActive() && !state.broadcastSession) {
                cleanup({ releaseMediaSources: false });
            }
        });
    }

    detectSupport();
    renderSupportChecks();
    updateReadinessStatus();
    updateQualityDetails();
    applyProgramCanvasResolution();
    updatePublishingButtons();
    renderRecordingState();
    updateBroadcastRules();
    renderPreviewState();
    renderSourceState();
    renderProgramState();
    renderTransitionButtons();
    renderSceneControls();
    updateViewerLinkControls();
    bindEvents();
    loadPersistentMediaSources();
}());
