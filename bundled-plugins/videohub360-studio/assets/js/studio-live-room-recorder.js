(function (window, document) {
    'use strict';
    var config = window.vh360StudioLiveRoomRecorder || {};
    var button, recorder, localRecorder, localChunks = [], appointmentBlob = null, appointmentDownloadPending = false, stopPromise = null, appointmentStopPromise = null, downloadPanel = null, compositor, mixer, startedAt = 0, timer, activeJobId = 0, starting = false, activeState = 'idle';

    function rest(path, options) { return window.VH360StudioRecordingClient.api(config.restRoot, config.nonce, path, options); }
    function setLabel(text) { if (button) { button.textContent = text; } }
    function setError(message) { if (button) { button.title = message || ''; } ensureStatus().textContent = message || ''; window.console && console.error('VH360 recording failed', message); }
    function ensureStatus() { var el = document.getElementById('vh360-studio-live-room-recorder-status'); if (!el && button && button.parentElement) { el = document.createElement('div'); el.id = 'vh360-studio-live-room-recorder-status'; el.className = 'vh360-studio-recorder-status'; el.setAttribute('aria-live', 'polite'); button.parentElement.appendChild(el); } return el || { textContent: '' }; }
    function ensureDownloadPanel() { if (downloadPanel && document.body.contains(downloadPanel)) { return downloadPanel; } var host = document.querySelector('.videohub360-live-room') || document.querySelector('.vh360-live-room-player') || document.body; downloadPanel = document.createElement('div'); downloadPanel.className = 'vh360-studio-private-download-panel'; downloadPanel.setAttribute('aria-live', 'polite'); host.appendChild(downloadPanel); return downloadPanel; }
    function showIndicator(show) { document.querySelectorAll('.vh360-studio-recording-indicator').forEach(function (el) { el.classList.toggle('vh360-hidden', !show); }); }
    function elapsed() { var s = Math.floor((Date.now() - startedAt) / 1000); return String(Math.floor(s / 60)).padStart(2, '0') + ':' + String(s % 60).padStart(2, '0'); }
    function tick() { setLabel((config.recordingPurpose === 'appointment_session' ? '● Private Recording ' : '● Recording ') + elapsed()); }
    function hasDesktopSupport() { return !/Mobi|Android|iPhone|iPad|iPod/i.test(navigator.userAgent) && window.MediaRecorder && HTMLCanvasElement.prototype.captureStream && (window.AudioContext || window.webkitAudioContext); }
    function joined() { return !!(window.vh360AgoraState && window.vh360AgoraState.isJoined && window.vh360AgoraState.isJoined()); }
    function qualityBits() { var preset = config.qualityPresetSettings || {}; return { videoBitsPerSecond: Number(preset.video_bitrate || preset.videoBitsPerSecond || 6000000), audioBitsPerSecond: Number(preset.audio_bitrate || preset.audioBitsPerSecond || 160000) }; }
    function buildStream() {
        compositor = new window.VH360LiveRoomCompositor({ width: 1920, height: 1080, fps: 30 });
        mixer = new window.VH360LiveRoomAudioMixer();
        return Promise.resolve(mixer.resume ? mixer.resume() : null).then(function () {
            var canvasStream = compositor.start();
            mixer.stream().getAudioTracks().forEach(function (track) { canvasStream.addTrack(track); });
            return canvasStream;
        });
    }
    function cleanupMedia() { if (compositor) { compositor.stop(); } if (mixer) { mixer.stop(); } compositor = null; mixer = null; }
    function notifyRecordingState(kind) { if (window.sendDataStreamMessage) { window.sendDataStreamMessage({ type: kind, roomId: config.postId, recordingPurpose: config.recordingPurpose }); } }
    function setActiveRecordingState(state) { activeState = state; showIndicator(state === 'recording' || state === 'starting' || state === 'stopping'); }
    function hasUnsavedRecordingData() { return starting || !!stopPromise || !!(recorder && recorder.hasUnsavedData && recorder.hasUnsavedData()) || !!(localRecorder && localRecorder.state === 'recording') || localChunks.length > 0 || appointmentDownloadPending || !!appointmentBlob; }
    function beforeUnload(event) { if (hasUnsavedRecordingData()) { event.preventDefault(); event.returnValue = ''; return ''; } }

    function start() {
        if (starting || activeState === 'recording') { return; }
        if (!joined()) { setError('Join the Live Room before recording.'); return; }
        if (config.recordingPurpose === 'appointment_session' && !window.confirm('Record this appointment privately?\n\nEveryone in the appointment will be shown a recording notice. The recording will be saved to this device and will not be published as a replay or uploaded by VideoHub360.')) { return; }
        starting = true; if (button) { button.disabled = true; } setLabel('Starting…'); setActiveRecordingState('starting');
        var preparedStream;
        buildStream().then(function (stream) {
            preparedStream = stream;
            return rest('/live-rooms/' + config.postId + '/recordings', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: '{}' });
        }).then(function (response) {
            activeJobId = response.job && response.job.id ? Number(response.job.id) : 0;
            if (!activeJobId) { throw new Error('Recording job was not created.'); }
            startedAt = Date.now();
            if (config.recordingPurpose === 'appointment_session') { return startLocalPrivate(response, preparedStream); }
            return startProviderBacked(response, preparedStream);
        }).catch(function (error) {
            cleanupFailedStartup(error);
        }).finally(function () { starting = false; if (button) { button.disabled = false; } });
    }


    function cleanupFailedStartup(error) {
        setActiveRecordingState('failed'); cleanupMedia(); setLabel(config.recordingPurpose === 'appointment_session' ? 'Record Privately' : 'Record'); setError(error && (error.message || error.code) || error);
        if (!activeJobId) { return; }
        if (config.recordingPurpose === 'appointment_session') {
            rest('/live-rooms/' + config.postId + '/recordings/' + activeJobId + '/local-private', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ state: 'failed', error_message: error && error.message || 'Recording startup failed.' }) }).catch(function (cleanupError) { window.console && console.warn('Unable to fail private appointment recording.', cleanupError); });
        } else {
            rest('/jobs/' + activeJobId + '/cancel', { method: 'POST', headers: { 'Content-Type': 'application/json' } }).catch(function (cleanupError) { window.console && console.warn('Unable to cancel recording job.', cleanupError); });
        }
        activeJobId = 0;
    }

    function startProviderBacked(response, stream) {
        recorder = new window.VH360StudioRecordingClient.RecordingClient({ restRoot: config.restRoot, nonce: config.nonce, jobId: activeJobId, stream: stream, mimeType: window.VH360StudioRecordingClient.supportedMimeType(), bits: qualityBits(), onStatus: function (state) { setActiveRecordingState(state); if (state === 'uploading') { setLabel('Uploading…'); } if (state === 'processing') { setLabel('Processing…'); } }, onError: function (error) { setError(error && error.message || error); } });
        return recorder.start().then(function () { setActiveRecordingState('recording'); notifyRecordingState('live_room_recording_started'); timer = setInterval(tick, 1000); tick(); window.addEventListener('beforeunload', beforeUnload); return response; });
    }

    function startLocalPrivate(response, stream) {
        var mimeType = window.VH360StudioRecordingClient.supportedMimeType();
        var localOptions = Object.assign({}, qualityBits()); if (mimeType) { localOptions.mimeType = mimeType; }
        localRecorder = new MediaRecorder(stream, localOptions);
        localChunks = [];
        localRecorder.ondataavailable = function (event) { if (event.data && event.data.size) { localChunks.push(event.data); } };
        localRecorder.start(5000);
        setActiveRecordingState('recording'); notifyRecordingState('live_room_recording_started'); timer = setInterval(tick, 1000); tick(); window.addEventListener('beforeunload', beforeUnload);
        return response;
    }

    function stop() {
        if (starting) { return Promise.resolve(); }
        if (config.recordingPurpose === 'appointment_session') { return stopLocalPrivate(); }
        return stopProviderBacked();
    }

    function stopProviderBacked() {
        if (!recorder) { return Promise.resolve(); }
        if (recorder.publishStarted) { return Promise.resolve(); }
        if (stopPromise) { return stopPromise; }
        if (button) { button.disabled = true; } setLabel('Stopping…'); clearInterval(timer); setActiveRecordingState('stopping');
        stopPromise = recorder.secureLocalRecordingData().then(function (result) {
            notifyRecordingState('live_room_recording_stopped');
            var ready = recorder && recorder.publishIsReady && recorder.publishIsReady(result);
            setActiveRecordingState(ready ? 'ready' : 'processing'); setLabel(ready ? 'Replay Ready' : 'Processing…');
            window.removeEventListener('beforeunload', beforeUnload); cleanupMedia();
            monitorProviderProcessing(result);
            return result;
        }).catch(function (error) {
            setActiveRecordingState('failed'); setLabel(recorder && recorder.failed && recorder.failed.size ? 'Retry Uploads' : 'Retry Finalization'); setError(error && (error.message || error.code) || error); throw error;
        }).finally(function () { stopPromise = null; if (button) { button.disabled = false; } });
        return stopPromise;
    }

    function monitorProviderProcessing(initialResult) {
        if (!recorder) { return; }
        if (recorder.publishIsReady(initialResult)) { setLabel('Replay Ready'); activeState = 'ready'; recorder = null; return; }
        recorder.pollPublishingStatus(5000, 10 * 60 * 1000).then(function (result) {
            if (recorder && recorder.publishIsReady(result)) { setActiveRecordingState('ready'); setLabel('Replay Ready'); recorder = null; }
        }).catch(function (error) { setActiveRecordingState('failed'); setLabel('Replay Failed'); setError(error && (error.message || error.code) || error); });
    }

    function stopLocalPrivate() {
        if (appointmentStopPromise) { return appointmentStopPromise; }
        if (!localRecorder || localRecorder.state === 'inactive') { return appointmentBlob ? Promise.resolve({ state: 'download_pending' }) : Promise.resolve(); }
        if (button) { button.disabled = true; } setLabel('Stopping…'); clearInterval(timer); setActiveRecordingState('stopping');
        appointmentStopPromise = new Promise(function (resolve, reject) { localRecorder.addEventListener('stop', resolve, { once: true }); try { localRecorder.stop(); } catch (error) { reject(error); } }).then(function () {
            var duration = Math.max(0, Math.round((Date.now() - startedAt) / 1000));
            setLabel('Preparing File…'); setActiveRecordingState('preparing_download');
            return rest('/live-rooms/' + config.postId + '/recordings/' + activeJobId + '/local-private', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ state: 'preparing_download', duration_seconds: duration, mime_type: localRecorder.mimeType || 'video/webm' }) }).then(function () {
                appointmentBlob = new Blob(localChunks, { type: localRecorder.mimeType || 'video/webm' });
                appointmentDownloadPending = true;
                prepareAppointmentDownload(appointmentBlob, duration);
                return { state: 'preparing_download' };
            });
        }).then(function () { setActiveRecordingState('download_pending'); notifyRecordingState('live_room_recording_stopped'); setLabel('Download Prepared'); cleanupMedia(); return { state: 'download_pending' }; }).catch(function (error) { setActiveRecordingState('failed'); setLabel('Retry Private Stop'); setError(error && (error.message || error.code) || error); if (activeJobId) { rest('/live-rooms/' + config.postId + '/recordings/' + activeJobId + '/local-private', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ state: 'failed', error_message: error && error.message || 'Local file preparation failed.' }) }).catch(function (cleanupError) { window.console && console.warn('Unable to mark private appointment recording failed.', cleanupError); }); } throw error; }).finally(function () { appointmentStopPromise = null; localRecorder = null; if (button) { button.disabled = false; } });
        return appointmentStopPromise;
    }


    function prepareAppointmentDownload(blob, duration) {
        var filename = window.VH360StudioRecordingClient.appointmentFilename(blob.type);
        var download = document.createElement('button');
        download.type = 'button';
        download.className = 'vh360-studio-private-download-btn';
        download.textContent = 'Download Recording';
        download.addEventListener('click', function () {
            window.VH360StudioRecordingClient.downloadBlob(blob, filename);
            download.disabled = true;
            download.textContent = 'Closing Recording…';
            finalizeAppointmentDownload(blob, duration, download);
        });
        var panel = ensureDownloadPanel();
        panel.innerHTML = '<strong>Private appointment recording prepared.</strong> Please download the file before leaving this page.';
        panel.appendChild(download);
    }


    function finalizeAppointmentDownload(blob, duration, download) {
        rest('/live-rooms/' + config.postId + '/recordings/' + activeJobId + '/local-private', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ state: 'download_ready', duration_seconds: duration || 0, mime_type: blob.type }) }).then(function () {
            localChunks = [];
            appointmentBlob = null;
            appointmentDownloadPending = false;
            activeJobId = 0;
            setActiveRecordingState('idle');
            setLabel('Record Privately');
            window.removeEventListener('beforeunload', beforeUnload);
            download.remove(); if (downloadPanel) { downloadPanel.remove(); downloadPanel = null; }
        }).catch(function (error) {
            download.disabled = false;
            download.textContent = 'Retry Close Recording';
            setError((error && error.message) || 'File download was initiated, but the recording session still needs to be closed server-side.');
        });
    }

    function init() {
        button = document.getElementById('vh360-studio-live-room-record');
        function restoreState(state) { showIndicator(!!(state.recording_active || state.active && state.state === 'recording')); if (!button) { return; } activeJobId = state.job_id ? Number(state.job_id) : activeJobId; if (state.replay_ready) { setActiveRecordingState('ready'); setLabel('Replay Ready'); button.disabled = true; } else if (state.replay_processing || state.state === 'processing' || state.state === 'uploading') { setActiveRecordingState('processing'); setLabel('Processing…'); button.disabled = true; } else if (state.state === 'preparing_download') { setActiveRecordingState('download_pending'); setLabel('Download Prepared'); button.disabled = true; setError('A private recording download is pending on another page or was interrupted. Close or fail that session before starting a new one.'); } else if (state.recording_active) { setActiveRecordingState('recording'); setLabel(config.recordingPurpose === 'appointment_session' ? '● Private Recording' : '● Recording'); } }
        rest('/live-rooms/' + config.postId + '/recording').then(restoreState).catch(function (error) { window.console && console.warn('Unable to restore recording state.', error); });
        window.setInterval(function () { rest('/live-rooms/' + config.postId + '/recording').then(function (state) { showIndicator(!!(state.recording_active || state.active && state.state === 'recording')); }).catch(function (error) { window.console && console.warn('Unable to refresh recording state.', error); }) }, 15000);
        window.addEventListener('vh360:agora-data-message', function (event) { var data = event.detail || {}; if (data.type === 'live_room_recording_started' || data.type === 'live_room_recording_stopped') { rest('/live-rooms/' + config.postId + '/recording').then(function (state) { showIndicator(!!(state.recording_active || state.active && state.state === 'recording')); }).catch(function (error) { window.console && console.warn('Unable to refresh recording state.', error); }) } });
        if (!button) { return; }
        if (!hasDesktopSupport()) { setLabel(config.desktopOnlyMessage || 'Recording unavailable'); return; }
        button.classList.remove('vh360-hidden'); button.addEventListener('click', function () { if (activeState === 'recording' || activeState === 'failed' && recorder) { stop().catch(function (error) { setError(error && (error.message || error.code) || error); }); } else if (activeState === 'ready') { button.disabled = true; } else if (activeState !== 'processing' && activeState !== 'download_pending') { start(); } });
        if (window.vh360AgoraLifecycle) { window.vh360AgoraLifecycle.registerBeforeLeave(stop); window.vh360AgoraLifecycle.registerBeforeEnd(stop); }
    }
    document.addEventListener('DOMContentLoaded', init);
})(window, document);
