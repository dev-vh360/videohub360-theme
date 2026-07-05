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
    };

    const els = {
        status: root.querySelector('[data-studio-status]'),
        supportChecks: root.querySelector('[data-support-checks]'),
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
        storageSelect: root.querySelector('[data-storage-select]'),
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
            state.programContext = state.programCanvas.getContext('2d');
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
            await navigator.clipboard.writeText(state.viewerPermalink);

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
        els.mediaSourceModal.hidden = true;
        resetMediaSourceModal();
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
        setMediaSourceModalStatus('Adding media source…', 'info');

        try {
            const response = await api('/media-sources', { method: 'POST', body: formData });
            const source = registerPersistentMediaSource(response.source);
            renderSourceState();
            renderSceneControls();
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
        const preset = (config.qualityPresets || {})[els.qualitySelect.value];
        if (!preset) {
            return;
        }
        const resolution = preset.resolution ? preset.resolution.width + '×' + preset.resolution.height : '';
        els.qualityDetails.textContent = preset.label + ' · ' + resolution + ' · ' + preset.fps + 'fps';
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
        renderTransitionButtons();
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
            state.viewerPermalink = broadcast.viewerPermalink || '';
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
        state.broadcastEnding = true;
        state.broadcastReady = false;
        renderTransitionButtons();
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
        state.viewerPermalink = '';
        updateViewerLinkControls();
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
        state.broadcastEnding = false;
        state.broadcastReady = false;
        if (els.goLive) els.goLive.disabled = false;
        if (els.endLive) els.endLive.disabled = true;
        setShellClass('is-live', false);
        renderTransitionButtons();
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
            if (isRecordingActive()) {
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
