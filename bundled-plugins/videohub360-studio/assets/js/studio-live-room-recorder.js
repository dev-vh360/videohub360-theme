(function (window, document) {
    'use strict';
    var config = window.vh360StudioLiveRoomRecorder || {};
    var button, indicator, recorder, localRecorder, localChunks = [], compositor, mixer, startedAt = 0, timer, activeJobId = 0, starting = false, activeState = 'idle';

    function rest(path, options) { return window.VH360StudioRecordingClient.api(config.restRoot, config.nonce, path, options); }
    function setLabel(text) { if (button) { button.textContent = text; } }
    function setError(message) { if (button) { button.title = message || ''; } window.console && console.error('VH360 recording failed', message); }
    function showIndicator(show) { document.querySelectorAll('.vh360-studio-recording-indicator').forEach(function (el) { el.classList.toggle('vh360-hidden', !show); }); }
    function elapsed() { var s = Math.floor((Date.now() - startedAt) / 1000); return String(Math.floor(s / 60)).padStart(2, '0') + ':' + String(s % 60).padStart(2, '0'); }
    function tick() { setLabel((config.recordingPurpose === 'appointment_session' ? '● Private Recording ' : '● Recording ') + elapsed()); }
    function hasDesktopSupport() { return !/Mobi|Android|iPhone|iPad|iPod/i.test(navigator.userAgent) && window.MediaRecorder && HTMLCanvasElement.prototype.captureStream && (window.AudioContext || window.webkitAudioContext); }
    function joined() { return !window.vh360AgoraState || !window.vh360AgoraState.isJoined || window.vh360AgoraState.isJoined(); }
    function qualityBits() { var preset = config.qualityPresetSettings || {}; return { videoBitsPerSecond: Number(preset.video_bitrate || preset.videoBitsPerSecond || 6000000), audioBitsPerSecond: Number(preset.audio_bitrate || preset.audioBitsPerSecond || 160000) }; }
    function buildStream() {
        compositor = new window.VH360LiveRoomCompositor({ width: 1920, height: 1080, fps: 30 });
        mixer = new window.VH360LiveRoomAudioMixer();
        if (mixer.resume) { mixer.resume(); }
        var canvasStream = compositor.start();
        mixer.stream().getAudioTracks().forEach(function (track) { canvasStream.addTrack(track); });
        return canvasStream;
    }
    function cleanupMedia() { if (compositor) { compositor.stop(); } if (mixer) { mixer.stop(); } compositor = null; mixer = null; }
    function notifyRecordingState(kind) { if (window.sendDataStreamMessage) { window.sendDataStreamMessage({ type: kind, roomId: config.postId, recordingPurpose: config.recordingPurpose }); } }
    function setActiveRecordingState(state) { activeState = state; showIndicator(state === 'recording' || state === 'starting' || state === 'stopping' || state === 'uploading' || state === 'processing'); }
    function beforeUnload(event) { if (activeState !== 'idle' && activeState !== 'ready' && activeState !== 'download_ready' && activeState !== 'failed') { event.preventDefault(); event.returnValue = ''; return ''; } }

    function start() {
        if (starting || activeState === 'recording') { return; }
        if (!joined()) { setError('Join the Live Room before recording.'); return; }
        if (config.recordingPurpose === 'appointment_session' && !window.confirm('Record this appointment privately?\n\nEveryone in the appointment will be shown a recording notice. The recording will be saved to this device and will not be published as a replay or uploaded by VideoHub360.')) { return; }
        starting = true; if (button) { button.disabled = true; } setLabel('Starting…'); setActiveRecordingState('starting');
        rest('/live-rooms/' + config.postId + '/recordings', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: '{}' }).then(function (response) {
            activeJobId = response.job && response.job.id ? Number(response.job.id) : 0;
            if (!activeJobId) { throw new Error('Recording job was not created.'); }
            startedAt = Date.now();
            if (config.recordingPurpose === 'appointment_session') { return startLocalPrivate(response); }
            return startProviderBacked(response);
        }).catch(function (error) {
            setActiveRecordingState('failed'); cleanupMedia(); setLabel(config.recordingPurpose === 'appointment_session' ? 'Record Privately' : 'Record'); setError(error && (error.message || error.code) || error);
        }).finally(function () { starting = false; if (button) { button.disabled = false; } });
    }

    function startProviderBacked(response) {
        recorder = new window.VH360StudioRecordingClient.RecordingClient({ restRoot: config.restRoot, nonce: config.nonce, jobId: activeJobId, stream: buildStream(), mimeType: window.VH360StudioRecordingClient.supportedMimeType(), bits: qualityBits(), onStatus: function (state) { setActiveRecordingState(state); if (state === 'uploading') { setLabel('Uploading…'); } if (state === 'processing') { setLabel('Processing…'); } }, onError: function (error) { setError(error && error.message || error); } });
        return recorder.start().then(function () { setActiveRecordingState('recording'); notifyRecordingState('live_room_recording_started'); timer = setInterval(tick, 1000); tick(); window.addEventListener('beforeunload', beforeUnload); return response; });
    }

    function startLocalPrivate(response) {
        var mimeType = window.VH360StudioRecordingClient.supportedMimeType();
        var localOptions = Object.assign({}, qualityBits()); if (mimeType) { localOptions.mimeType = mimeType; }
        localRecorder = new MediaRecorder(buildStream(), localOptions);
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
        if (button) { button.disabled = true; } setLabel('Stopping…'); clearInterval(timer); setActiveRecordingState('stopping');
        return recorder.stop().then(function () { setLabel('Uploading…'); return recorder.finalizeAndPublish(); }).then(function () { setActiveRecordingState('ready'); notifyRecordingState('live_room_recording_stopped'); setLabel('Replay Ready'); window.removeEventListener('beforeunload', beforeUnload); cleanupMedia(); }).catch(function (error) { setActiveRecordingState('failed'); setLabel('Record'); setError(error && (error.message || error.code) || error); throw error; }).finally(function () { recorder = null; if (button) { button.disabled = false; } });
    }

    function stopLocalPrivate() {
        if (!localRecorder || localRecorder.state === 'inactive') { return Promise.resolve(); }
        if (button) { button.disabled = true; } setLabel('Stopping…'); clearInterval(timer); setActiveRecordingState('stopping');
        return new Promise(function (resolve, reject) { localRecorder.addEventListener('stop', resolve, { once: true }); try { localRecorder.stop(); } catch (error) { reject(error); } }).then(function () {
            var duration = Math.max(0, Math.round((Date.now() - startedAt) / 1000));
            setLabel('Preparing File…'); setActiveRecordingState('preparing_download');
            return rest('/live-rooms/' + config.postId + '/recordings/' + activeJobId + '/local-private', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ state: 'preparing_download', duration_seconds: duration, mime_type: localRecorder.mimeType || 'video/webm' }) }).then(function () {
                var blob = new Blob(localChunks, { type: localRecorder.mimeType || 'video/webm' });
                window.VH360StudioRecordingClient.downloadBlob(blob, window.VH360StudioRecordingClient.appointmentFilename(blob.type));
                localChunks = [];
                return rest('/live-rooms/' + config.postId + '/recordings/' + activeJobId + '/local-private', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ state: 'download_ready', duration_seconds: duration, mime_type: blob.type }) });
            });
        }).then(function () { setActiveRecordingState('download_ready'); notifyRecordingState('live_room_recording_stopped'); setLabel('Download Ready'); window.removeEventListener('beforeunload', beforeUnload); cleanupMedia(); }).catch(function (error) { setActiveRecordingState('failed'); setLabel('Record Privately'); setError(error && (error.message || error.code) || error); if (activeJobId) { rest('/live-rooms/' + config.postId + '/recordings/' + activeJobId + '/local-private', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ state: 'failed', error_message: error && error.message || 'Local file preparation failed.' }) }).catch(function () {}); } throw error; }).finally(function () { localRecorder = null; if (button) { button.disabled = false; } });
    }

    function init() {
        button = document.getElementById('vh360-studio-live-room-record'); indicator = document.querySelector('.vh360-studio-recording-indicator');
        rest('/live-rooms/' + config.postId + '/recording').then(function (state) { showIndicator(!!state.active); }).catch(function () {});
        window.setInterval(function () { rest('/live-rooms/' + config.postId + '/recording').then(function (state) { showIndicator(!!state.active); }).catch(function () {}); }, 15000);
        window.addEventListener('vh360:agora-data-message', function (event) { var data = event.detail || {}; if (data.type === 'live_room_recording_started' || data.type === 'live_room_recording_stopped') { rest('/live-rooms/' + config.postId + '/recording').then(function (state) { showIndicator(!!state.active); }).catch(function () {}); } });
        if (!button) { return; }
        if (!hasDesktopSupport()) { setLabel(config.desktopOnlyMessage || 'Recording unavailable'); return; }
        button.classList.remove('vh360-hidden'); button.addEventListener('click', function () { if (activeState === 'recording') { stop().catch(function () {}); } else { start(); } });
        if (window.vh360AgoraLifecycle) { window.vh360AgoraLifecycle.registerBeforeLeave(stop); window.vh360AgoraLifecycle.registerBeforeEnd(stop); }
    }
    document.addEventListener('DOMContentLoaded', init);
})(window, document);
