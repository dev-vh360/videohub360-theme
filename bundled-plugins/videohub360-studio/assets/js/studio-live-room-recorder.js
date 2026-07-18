(function (window, document) {
    'use strict';
    var config = window.vh360StudioLiveRoomRecorder || {};
    var button, indicator, recorder, chunks = [], compositor, mixer, startedAt = 0, timer;

    function rest(path, options) {
        options = options || {}; options.headers = options.headers || {}; options.headers['X-WP-Nonce'] = config.nonce; options.headers['Content-Type'] = 'application/json';
        return fetch(config.restRoot + path, options).then(function (r) { return r.json().then(function (j) { if (!r.ok) { throw j; } return j; }); });
    }
    function setLabel(text) { if (button) { button.textContent = text; } }
    function showIndicator(show) { if (indicator) { indicator.classList.toggle('vh360-hidden', !show); } }
    function elapsed() { var s = Math.floor((Date.now() - startedAt) / 1000); return String(Math.floor(s / 60)).padStart(2, '0') + ':' + String(s % 60).padStart(2, '0'); }
    function tick() { setLabel((config.recordingPurpose === 'appointment_session' ? '● Private Recording ' : '● Recording ') + elapsed()); }
    function hasDesktopSupport() { return !/Mobi|Android|iPhone|iPad|iPod/i.test(navigator.userAgent) && window.MediaRecorder && HTMLCanvasElement.prototype.captureStream && (window.AudioContext || window.webkitAudioContext); }
    function joined() { return !window.vh360AgoraState || !window.vh360AgoraState.isJoined || window.vh360AgoraState.isJoined(); }
    function buildStream() {
        compositor = new window.VH360LiveRoomCompositor({ width: 1920, height: 1080, fps: 30 });
        mixer = new window.VH360LiveRoomAudioMixer();
        var canvasStream = compositor.start();
        mixer.stream().getAudioTracks().forEach(function (track) { canvasStream.addTrack(track); });
        return canvasStream;
    }
    function start() {
        if (!joined()) { return; }
        if (config.recordingPurpose === 'appointment_session' && !window.confirm('Record this appointment privately?\n\nEveryone in the appointment will be shown a recording notice. The recording will be saved to this device and will not be published as a replay or uploaded by VideoHub360.')) { return; }
        setLabel('Starting…');
        rest('/live-rooms/' + config.postId + '/recordings', { method: 'POST', body: '{}' }).then(function () {
            var mimeType = window.VH360StudioRecordingClient.supportedMimeType();
            recorder = new MediaRecorder(buildStream(), mimeType ? { mimeType: mimeType } : undefined);
            chunks = [];
            recorder.ondataavailable = function (event) { if (event.data && event.data.size) { chunks.push(event.data); } };
            recorder.onstop = function () {
                if (config.recordingPurpose === 'appointment_session') {
                    setLabel('Preparing File…');
                    var blob = new Blob(chunks, { type: recorder.mimeType || 'video/webm' });
                    window.VH360StudioRecordingClient.downloadBlob(blob, window.VH360StudioRecordingClient.appointmentFilename(blob.type));
                    chunks = []; setLabel('Download Ready');
                } else { setLabel('Processing…'); }
                showIndicator(false); if (compositor) { compositor.stop(); } if (mixer) { mixer.stop(); }
            };
            recorder.start(5000); startedAt = Date.now(); showIndicator(true); timer = setInterval(tick, 1000); tick();
        }).catch(function (error) { setLabel('Record'); window.console && console.error('VH360 recording failed', error); });
    }
    function stop() { if (!recorder || recorder.state === 'inactive') { return Promise.resolve(); } setLabel('Stopping…'); clearInterval(timer); return new Promise(function (resolve) { recorder.addEventListener('stop', resolve, { once: true }); recorder.stop(); }); }
    function init() {
        button = document.getElementById('vh360-studio-live-room-record'); indicator = document.querySelector('.vh360-studio-recording-indicator');
        if (!button) { return; }
        if (!hasDesktopSupport()) { setLabel(config.desktopOnlyMessage || 'Recording unavailable'); return; }
        button.classList.remove('vh360-hidden'); button.addEventListener('click', function () { if (recorder && recorder.state === 'recording') { stop(); } else { start(); } });
        if (window.vh360AgoraLifecycle) { window.vh360AgoraLifecycle.registerBeforeLeave(stop); window.vh360AgoraLifecycle.registerBeforeEnd(stop); }
        rest('/live-rooms/' + config.postId + '/recording').then(function (state) { showIndicator(!!state.active); }).catch(function () {});
    }
    document.addEventListener('DOMContentLoaded', init);
})(window, document);
