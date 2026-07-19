(function (window, document) {
    'use strict';
    var config = window.vh360StudioLiveRoomRecorder || {};
    var button, recorder, localRecorder, localChunks = [], appointmentBlob = null, appointmentDownloadPending = false, stopPromise = null, appointmentStopPromise = null, startPromise = null, startupCancelRequested = false, downloadPanel = null, publishingPanel = null, heartbeatTimer = null, compositor, mixer, startedAt = 0, timer, activeJobId = 0, starting = false, activeState = 'idle';

    function rest(path, options) { return window.VH360StudioRecordingClient.api(config.restRoot, config.nonce, path, options); }
    function setLabel(text) { if (button) { button.textContent = text; } }
    function setError(message) { if (button) { button.title = message || ''; } ensureStatus().textContent = message || ''; window.console && console.error('VH360 recording failed', message); }
    function setStatus(message) { if (button) { button.title = message || ''; } ensureStatus().textContent = message || ''; }
    function ensureStatus() { var el = document.getElementById('vh360-studio-live-room-recorder-status'); if (!el && button && button.parentElement) { el = document.createElement('div'); el.id = 'vh360-studio-live-room-recorder-status'; el.className = 'vh360-studio-recorder-status'; el.setAttribute('aria-live', 'polite'); button.parentElement.appendChild(el); } return el || { textContent: '' }; }
    function ensurePublishingPanel() { if (publishingPanel && document.body.contains(publishingPanel)) { return publishingPanel; } var host = document.querySelector('.videohub360-live-room') || document.querySelector('.vh360-live-room-player') || document.body; publishingPanel = document.createElement('div'); publishingPanel.className = 'vh360-studio-publishing-recovery-panel'; publishingPanel.setAttribute('aria-live', 'polite'); host.appendChild(publishingPanel); return publishingPanel; }
    function ensureDownloadPanel() { if (downloadPanel && document.body.contains(downloadPanel)) { return downloadPanel; } var host = document.querySelector('.videohub360-live-room') || document.querySelector('.vh360-live-room-player') || document.body; downloadPanel = document.createElement('div'); downloadPanel.className = 'vh360-studio-private-download-panel'; downloadPanel.setAttribute('aria-live', 'polite'); host.appendChild(downloadPanel); return downloadPanel; }
    function attachNoticeToPlayer() { var notice = document.querySelector('.vh360-live-room-recording-notice'); var player = document.querySelector('.vh360-live-room-player, .vh360-agora-player, .videohub360-live-room'); if (notice && player && notice.parentElement !== player) { if (window.getComputedStyle(player).position === 'static') { player.style.position = 'relative'; } player.appendChild(notice); } }
    function showIndicator(show) { attachNoticeToPlayer(); document.querySelectorAll('.vh360-studio-recording-indicator').forEach(function (el) { el.classList.toggle('vh360-hidden', !show); }); }
    function elapsed() { var s = Math.floor((Date.now() - startedAt) / 1000); return String(Math.floor(s / 60)).padStart(2, '0') + ':' + String(s % 60).padStart(2, '0'); }
    function tick() { setLabel((config.recordingPurpose === 'appointment_session' ? '● Private Recording ' : '● Recording ') + elapsed()); }
    function hasDesktopSupport() { return !/Mobi|Android|iPhone|iPad|iPod/i.test(navigator.userAgent) && window.MediaRecorder && HTMLCanvasElement.prototype.captureStream && (window.AudioContext || window.webkitAudioContext); }
    function joined() { return !!(window.vh360AgoraState && window.vh360AgoraState.isJoined && window.vh360AgoraState.isJoined()); }
    function qualityBits() { var preset = config.qualityPresetSettings || {}; return { videoBitsPerSecond: Number(preset.video_bitrate || preset.videoBitsPerSecond || 6000000), audioBitsPerSecond: Number(preset.audio_bitrate || preset.audioBitsPerSecond || 160000) }; }
    function buildStream() {
        var preset = config.qualityPresetSettings || {}, resolution = preset.resolution || {}; compositor = new window.VH360LiveRoomCompositor({ width: Number(resolution.width || preset.width || preset.video_width || 1920), height: Number(resolution.height || preset.height || preset.video_height || 1080), fps: Number(preset.fps || preset.frame_rate || 30) });
        mixer = new window.VH360LiveRoomAudioMixer();
        return Promise.resolve(mixer.resume ? mixer.resume() : null).then(function () {
            var canvasStream = compositor.start();
            mixer.stream().getAudioTracks().forEach(function (track) { canvasStream.addTrack(track); });
            return canvasStream;
        });
    }
    function cleanupMedia() { if (compositor) { compositor.stop(); } if (mixer) { mixer.stop(); } compositor = null; mixer = null; }
    function startHeartbeat() { stopHeartbeat(); if (!activeJobId) { return; } var beat = function () { rest('/live-rooms/' + config.postId + '/recordings/' + activeJobId + '/heartbeat', { method: 'POST', headers: { 'Content-Type': 'application/json' } }).catch(function (error) { window.console && console.warn('Unable to update recording heartbeat.', error); }); }; beat(); heartbeatTimer = window.setInterval(beat, 30000); }
    function stopHeartbeat() { if (heartbeatTimer) { window.clearInterval(heartbeatTimer); heartbeatTimer = null; } }
    function closeRecordingHeartbeat() { stopHeartbeat(); }
    function notifyRecordingState(kind) { if (window.sendDataStreamMessage) { window.sendDataStreamMessage({ type: kind, roomId: config.postId, recordingPurpose: config.recordingPurpose }); } }
    function setActiveRecordingState(state) { activeState = state; showIndicator(state === 'recording' || state === 'starting'); }
    function hasUnsavedRecordingData() { return starting || !!stopPromise || !!(recorder && recorder.hasUnsavedData && recorder.hasUnsavedData()) || !!(localRecorder && localRecorder.state === 'recording') || localChunks.length > 0 || appointmentDownloadPending || !!appointmentBlob; }
    function beforeUnload(event) { if (hasUnsavedRecordingData()) { event.preventDefault(); event.returnValue = ''; return ''; } }

    function start() {
        if (starting || activeState === 'recording') { return; }
        if (!joined()) { setError('Join the Live Room before recording.'); return; }
        if (config.recordingPurpose === 'appointment_session' && !window.confirm('Record this appointment privately?\n\nEveryone in the appointment will be shown a recording notice. The recording will be saved to this device and will not be published as a replay or uploaded by VideoHub360.')) { return; }
        starting = true; startupCancelRequested = false; window.addEventListener('beforeunload', beforeUnload); if (button) { button.disabled = true; } setLabel('Starting…'); setActiveRecordingState('starting');
        var preparedStream;
        startPromise = buildStream().then(function (stream) {
            preparedStream = stream;
            return rest('/live-rooms/' + config.postId + '/recordings', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: '{}' });
        }).then(function (response) {
            activeJobId = response.job && response.job.id ? Number(response.job.id) : 0;
            if (!activeJobId) { throw new Error('Recording job was not created.'); }
            if (startupCancelRequested) { throw new Error('Recording startup was cancelled before it completed.'); }
            startedAt = Date.now();
            if (config.recordingPurpose === 'appointment_session') { return startLocalPrivate(response, preparedStream); }
            return startProviderBacked(response, preparedStream);
        }).catch(function (error) {
            return cleanupFailedStartup(error);
        }).finally(function () { starting = false; startPromise = null; startupCancelRequested = false; if (!hasUnsavedRecordingData()) { window.removeEventListener('beforeunload', beforeUnload); } if (button) { button.disabled = false; } });
        return startPromise;
    }


    function cleanupFailedStartup(error) {
        setActiveRecordingState('failed'); cleanupMedia(); setLabel(config.recordingPurpose === 'appointment_session' ? 'Record Privately' : 'Record'); setError(error && (error.message || error.code) || error);
        if (localRecorder && localRecorder.state === 'recording') { try { localRecorder.stop(); } catch (stopError) { window.console && console.warn('Unable to stop failed local recorder.', stopError); } }
        recorder = null; localRecorder = null; localChunks = []; appointmentBlob = null; appointmentDownloadPending = false;
        if (!activeJobId) { return Promise.resolve(); }
        var cleanup;
        if (config.recordingPurpose === 'appointment_session') {
            cleanup = rest('/live-rooms/' + config.postId + '/recordings/' + activeJobId + '/local-private', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ state: 'failed', error_message: error && error.message || 'Recording startup failed.' }) });
        } else {
            cleanup = rest('/jobs/' + activeJobId + '/cancel', { method: 'POST', headers: { 'Content-Type': 'application/json' } });
        }
        return cleanup.catch(function (cleanupError) { setError('Recording startup failed and server cleanup did not complete. Reload before starting another recording.'); window.console && console.warn('Unable to clean up failed recording startup.', cleanupError); }).then(function () { activeJobId = 0; });
    }

    function clearInterruptedRecording() {
        if (!activeJobId) { return Promise.resolve(); }
        if (button) { button.disabled = true; } setLabel('Clearing…');
        var message = config.recordingPurpose === 'appointment_session' ? 'Private appointment recording was interrupted in this browser. Undownloaded local media was lost.' : 'Live Room recording was interrupted in this browser before it could be finalized.';
        var request = config.recordingPurpose === 'appointment_session'
            ? rest('/live-rooms/' + config.postId + '/recordings/' + activeJobId + '/local-private', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ state: 'failed', error_message: message }) })
            : rest('/jobs/' + activeJobId + '/cancel', { method: 'POST', headers: { 'Content-Type': 'application/json' } });
        return request.then(function () {
            closeRecordingHeartbeat(); activeJobId = 0; activeState = 'idle'; setLabel(config.recordingPurpose === 'appointment_session' ? 'Record Privately' : 'Record'); setStatus('Interrupted recording cleared. You can start a new recording.');
            if (button) { button.disabled = false; }
        }).catch(function (error) { if (button) { button.disabled = false; } setError(error && (error.message || error.code) || error); throw error; });
    }


    function handleProviderTerminalUploadFailure(error) {
        clearInterval(timer);
        cleanupMedia();
        closeRecordingHeartbeat();
        notifyRecordingState('live_room_recording_stopped');
        window.removeEventListener('beforeunload', beforeUnload);
        setActiveRecordingState('idle');
        setLabel('Record Again');
        setError(error && (error.message || error.code) || error || 'Recording could not be saved.');
        var jobId = activeJobId;
        recorder = null;
        activeJobId = 0;
        if (jobId) {
            rest('/jobs/' + jobId + '/cancel', { method: 'POST', headers: { 'Content-Type': 'application/json' } }).catch(function (cancelError) {
                window.console && console.warn('Unable to cancel terminally failed recording job; it may already be failed.', cancelError);
            });
        }
        if (button) { button.disabled = false; }
    }

    function startProviderBacked(response, stream) {
        recorder = new window.VH360StudioRecordingClient.RecordingClient({ restRoot: config.restRoot, nonce: config.nonce, jobId: activeJobId, stream: stream, mimeType: window.VH360StudioRecordingClient.supportedMimeType(), bits: qualityBits(), onStatus: function (state) { setActiveRecordingState(state); if (state === 'uploading') { setLabel('Uploading…'); } if (state === 'processing') { setLabel('Processing…'); } }, onError: function (error) { setError(error && error.message || error); }, onServerStoppingConfirmed: function () { notifyRecordingState('live_room_recording_stopped'); }, onTerminalUploadError: handleProviderTerminalUploadFailure });
        return recorder.start().then(function () { startHeartbeat(); setActiveRecordingState('recording'); notifyRecordingState('live_room_recording_started'); timer = setInterval(tick, 1000); tick(); window.addEventListener('beforeunload', beforeUnload); return response; });
    }

    function startLocalPrivate(response, stream) {
        var mimeType = window.VH360StudioRecordingClient.supportedMimeType();
        var localOptions = Object.assign({}, qualityBits()); if (mimeType) { localOptions.mimeType = mimeType; }
        localRecorder = new MediaRecorder(stream, localOptions);
        localChunks = [];
        localRecorder.ondataavailable = function (event) { if (event.data && event.data.size) { localChunks.push(event.data); } };
        localRecorder.start(5000);
        startHeartbeat(); setActiveRecordingState('recording'); notifyRecordingState('live_room_recording_started'); timer = setInterval(tick, 1000); tick(); window.addEventListener('beforeunload', beforeUnload);
        return response;
    }

    function stop() {
        if (starting) { startupCancelRequested = true; return (startPromise || Promise.resolve()).then(function () { return stop(); }).catch(function () { return clearInterruptedRecording(); }); }
        if (activeState === 'interrupted' && activeJobId) { return clearInterruptedRecording(); }
        if (config.recordingPurpose === 'appointment_session') { return stopLocalPrivate(); }
        return stopProviderBacked();
    }

    function isTerminalRecordingError(error) { var code = error && (error.code || error.error || error.error_code); return error && (error.retryable === false || error.data && error.data.retryable === false) || ['vh360_studio_recording_too_large','vh360_studio_chunk_too_large','vh360_studio_invalid_chunk_type','vh360_studio_chunk_type_mismatch','vh360_studio_missing_chunks','vh360_studio_chunk_unreadable','vh360_studio_invalid_chunk_path','vh360_studio_chunk_integrity_failed','vh360_studio_invalid_recording_path','vh360_studio_recording_summary_mismatch','vh360_studio_invalid_recording_size','vh360_studio_invalid_recording_type','vh360_studio_invalid_recording_file'].indexOf(code) !== -1; }

    function stopProviderBacked() {
        if (!recorder) { return Promise.resolve(); }
        if (recorder.publishStarted || recorder.finalized && recorder.failureStage && recorder.failureStage.indexOf('publishing_') === 0) { cleanupMedia(); closeRecordingHeartbeat(); return Promise.resolve(); }
        if (stopPromise) { return stopPromise; }
        if (button) { button.disabled = true; } setLabel('Stopping…'); clearInterval(timer); setActiveRecordingState('stopping');
        stopPromise = recorder.secureLocalRecordingData().then(function (result) {
            var ready = recorder && recorder.publishIsReady && recorder.publishIsReady(result);
            setActiveRecordingState(ready ? 'ready' : 'processing'); setLabel(ready ? 'Replay Ready' : 'Processing…');
            window.removeEventListener('beforeunload', beforeUnload); cleanupMedia(); closeRecordingHeartbeat();
            monitorProviderProcessing(result);
            return result;
        }).catch(function (error) {
            var currentRecorder = recorder;
            var browserDataSecured = currentRecorder && currentRecorder.stopConfirmed && !currentRecorder.hasUnsavedData();
            if (currentRecorder && currentRecorder.finalized || browserDataSecured) { cleanupMedia(); closeRecordingHeartbeat(); window.removeEventListener('beforeunload', beforeUnload); }
            if (isTerminalRecordingError(error)) { cleanupMedia(); closeRecordingHeartbeat(); window.removeEventListener('beforeunload', beforeUnload); recorder = null; setActiveRecordingState('idle'); setLabel('Record Again'); setError(error && (error.message || error.code) || error); return; }
            setActiveRecordingState('failed');
            if (currentRecorder && currentRecorder.failureStage === 'publishing_prepare_failed') { setLabel('Retry Publishing'); showPublishingRecoveryPanel('Publishing preparation failed. You can retry publishing after ending the room.'); }
            else if (currentRecorder && currentRecorder.failureStage === 'publishing_start_failed') { setLabel('Retry Publishing'); showPublishingRecoveryPanel('Replay publishing did not start. You can retry publishing after ending the room.'); }
            else if (currentRecorder && currentRecorder.failureStage === 'provider_failed') { setLabel('Record Again'); showPublishingRecoveryPanel('Replay publishing failed. You can record again because no ready replay was created.'); recorder = null; }
            else { setLabel(currentRecorder && currentRecorder.failed && currentRecorder.failed.size ? 'Retry Uploads' : 'Retry Finalization'); if (browserDataSecured) { showFinalizationRecoveryPanel('Recording assembly failed. You can retry finalization after ending the room because the uploaded chunks remain on the server.'); } }
            setError(error && (error.message || error.code) || error); if (browserDataSecured || currentRecorder && currentRecorder.finalized) { return; } throw error;
        }).finally(function () { stopPromise = null; if (button) { button.disabled = false; } });
        return stopPromise;
    }

    function monitorProviderProcessing(initialResult) {
        if (!recorder) { return; }
        if (recorder.publishIsReady(initialResult)) { setLabel('Replay Ready'); activeState = 'ready'; recorder = null; return; }
        recorder.pollPublishingStatus(5000, 10 * 60 * 1000).then(function (result) {
            if (recorder && recorder.publishIsReady(result)) { setActiveRecordingState('ready'); setLabel('Replay Ready'); closeRecordingHeartbeat(); recorder = null; }
        }).catch(function (error) { if (recorder && recorder.publishIsFailed && recorder.publishIsFailed(error)) { setActiveRecordingState('idle'); setLabel('Record Again'); closeRecordingHeartbeat(); recorder = null; setError(error && (error.message || error.code) || error); } else { setActiveRecordingState('processing'); setLabel('Processing…'); window.console && console.warn('Temporary replay status polling failure.', error); } });
    }

    function stopLocalPrivate() {
        if (appointmentStopPromise) { return appointmentStopPromise; }
        if (!localRecorder || localRecorder.state === 'inactive') { if (appointmentBlob) { prepareAppointmentDownload(appointmentBlob, Math.max(0, Math.round((Date.now() - startedAt) / 1000))); return Promise.resolve({ state: 'download_pending' }); } if (localChunks.length) { return retryPrivateFilePreparation(); } return Promise.resolve(); }
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
        }).then(function () { setActiveRecordingState('download_pending'); notifyRecordingState('live_room_recording_stopped'); setLabel('Download Prepared'); cleanupMedia(); return { state: 'download_pending' }; }).catch(function (error) { setActiveRecordingState('failed'); setLabel('Retry Private Stop'); setError(error && (error.message || error.code) || error); if (activeJobId && !localChunks.length && !appointmentBlob) { rest('/live-rooms/' + config.postId + '/recordings/' + activeJobId + '/local-private', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ state: 'failed', error_message: error && error.message || 'Local file preparation failed.' }) }).catch(function (cleanupError) { window.console && console.warn('Unable to mark private appointment recording failed.', cleanupError); }); } throw error; }).finally(function () { appointmentStopPromise = null; localRecorder = null; if (button) { button.disabled = false; } });
        return appointmentStopPromise;
    }


    function retryPrivateFilePreparation() {
        if (!localChunks.length && !appointmentBlob) { setError('No private recording data remains in this browser to prepare.'); return Promise.resolve(); }
        if (appointmentBlob) { prepareAppointmentDownload(appointmentBlob, Math.max(0, Math.round((Date.now() - startedAt) / 1000))); setActiveRecordingState('download_pending'); setLabel('Download Prepared'); return Promise.resolve(); }
        var duration = Math.max(0, Math.round((Date.now() - startedAt) / 1000));
        var type = localChunks[0] && localChunks[0].type || 'video/webm';
        if (button) { button.disabled = true; } setLabel('Preparing File…');
        return rest('/live-rooms/' + config.postId + '/recordings/' + activeJobId + '/local-private', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ state: 'preparing_download', duration_seconds: duration, mime_type: type }) }).then(function () {
            appointmentBlob = new Blob(localChunks, { type: type }); appointmentDownloadPending = true; prepareAppointmentDownload(appointmentBlob, duration); setActiveRecordingState('download_pending'); notifyRecordingState('live_room_recording_stopped'); setLabel('Download Prepared'); cleanupMedia();
        }).catch(function (error) { setActiveRecordingState('failed'); setLabel('Retry Private Stop'); setError(error && (error.message || error.code) || error); throw error; }).finally(function () { if (button) { button.disabled = false; } });
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
            closeRecordingHeartbeat();
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

    function showPublishingRecoveryPanel(message) {
        var panel = ensurePublishingPanel();
        panel.innerHTML = '<strong>Replay publishing needs attention.</strong> ' + message;
        if ((recorder && (recorder.failureStage === 'publishing_prepare_failed' || recorder.failureStage === 'publishing_start_failed')) || (!recorder && activeJobId)) {
            var retry = document.createElement('button');
            retry.type = 'button';
            retry.textContent = 'Retry Publishing';
            retry.addEventListener('click', function () { (recorder ? retryPublishing() : retryServerPublishing()).catch(function (error) { setError(error && (error.message || error.code) || error); }); });
            panel.appendChild(retry);
        }
    }


    function retryServerFinalization() {
        if (!activeJobId) { return Promise.resolve(); }
        if (button) { button.disabled = true; }
        setLabel('Retrying Finalization…');
        return rest('/jobs/' + activeJobId + '/recording/finalize', { method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({}) }).then(function () {
            return retryServerPublishing();
        }).catch(function (error) {
            setActiveRecordingState('failed');
            setLabel('Retry Finalization');
            showFinalizationRecoveryPanel('Recording assembly failed. You can retry finalization because the uploaded chunks remain on the server.');
            throw error;
        }).finally(function () { if (button) { button.disabled = false; } });
    }

    function showFinalizationRecoveryPanel(message) {
        var panel = ensurePublishingPanel();
        panel.innerHTML = '<strong>Recording finalization needs attention.</strong> ' + message;
        var retry = document.createElement('button');
        retry.type = 'button';
        retry.textContent = 'Retry Finalization';
        retry.addEventListener('click', function () { retryServerFinalization().catch(function (error) { setError(error && (error.message || error.code) || error); }); });
        panel.appendChild(retry);
    }

    function retryServerPublishing() {
        if (!activeJobId) { return Promise.resolve(); }
        if (button) { button.disabled = true; } setLabel('Retrying Publishing…');
        return rest('/jobs/' + activeJobId + '/publishing/prepare', { method: 'POST', headers: { 'Content-Type': 'application/json' } }).then(function () {
            return rest('/jobs/' + activeJobId + '/publishing/publish', { method: 'POST', headers: { 'Content-Type': 'application/json' } });
        }).then(function () { setActiveRecordingState('processing'); setLabel('Processing…'); if (publishingPanel) { publishingPanel.remove(); publishingPanel = null; } }).catch(function (error) { setActiveRecordingState('failed'); setLabel('Retry Publishing'); showPublishingRecoveryPanel('Replay publishing failed. You can retry publishing after ending the room.'); throw error; }).finally(function () { if (button) { button.disabled = false; } });
    }

    function retryPublishing() {
        if (!recorder || !recorder.retryPublishing) { return Promise.resolve(); }
        if (button) { button.disabled = true; } setLabel('Retrying Publishing…');
        return recorder.retryPublishing().then(function (result) {
            var ready = recorder.publishIsReady(result);
            setActiveRecordingState(ready ? 'ready' : 'processing'); setLabel(ready ? 'Replay Ready' : 'Processing…'); if (publishingPanel) { publishingPanel.remove(); publishingPanel = null; } monitorProviderProcessing(result);
        }).catch(function (error) {
            setActiveRecordingState('failed');
            if (recorder && recorder.failureStage === 'publishing_prepare_failed') { setLabel('Retry Publishing'); showPublishingRecoveryPanel('Publishing preparation failed. You can retry publishing after ending the room.'); }
            else { setLabel('Record Again'); recorder = null; }
            throw error;
        }).finally(function () { if (button) { button.disabled = false; } });
    }


    function stopBeforeEnd() {
        return rest('/live-rooms/' + config.postId + '/recording').then(function (state) {
            var activeElsewhere = state && state.active && state.heartbeat_fresh && !recorder && !localRecorder && activeState !== 'recording' && activeState !== 'starting';
            if (activeElsewhere) {
                setActiveRecordingState('recording_elsewhere');
                setLabel('Recording in another tab');
                setStatus('Stop the recording in the original recording tab before ending this Live Room.');
                if (button) { button.disabled = true; }
                throw new Error('Recording is active in another tab. Stop it there before ending the Live Room.');
            }
            return stop();
        }).catch(function (error) {
            if (error && error.message && error.message.indexOf('another tab') !== -1) { throw error; }
            return stop();
        });
    }

    function init() {
        button = document.getElementById('vh360-studio-live-room-record');
        function restoreState(state) {
            showIndicator(!!(state.recording_active || state.active && state.state === 'recording'));
            activeJobId = state.job_id ? Number(state.job_id) : activeJobId;
            if (activeState === 'failed' && recorder) { return; }
            if (state.failure_stage === 'finalization_failed' && state.job_id) { activeJobId = Number(state.job_id); setActiveRecordingState('failed'); setLabel('Retry Finalization'); if (button) { button.disabled = false; } showFinalizationRecoveryPanel('Recording assembly failed. You can retry finalization after reloading because the uploaded chunks remain on the server.'); return; }
            if ((state.failure_stage === 'publishing_prepare_failed' || state.failure_stage === 'publishing_start_failed') && state.job_id) { activeJobId = Number(state.job_id); setActiveRecordingState('failed'); setLabel('Retry Publishing'); if (button) { button.disabled = false; } showPublishingRecoveryPanel('Replay publishing failed. You can retry publishing after ending the room.'); return; }
            var activeElsewhere = state.active && state.heartbeat_fresh && !recorder && !localRecorder && activeState !== 'recording' && activeState !== 'starting';
            if (activeElsewhere) { setActiveRecordingState('recording_elsewhere'); setLabel('Recording in another tab'); setStatus('Recording is active in another browser tab. Stop it there before ending the Live Room.'); if (button) { button.disabled = true; } return; }
            if (!button) { return; }
            if (state.replay_ready) { setActiveRecordingState('ready'); setLabel('Replay Ready'); button.disabled = true; return; }
            if (state.replay_failed || state.state === 'failed') { setActiveRecordingState('idle'); setLabel('Record Again'); button.disabled = false; return; }
            if (state.replay_processing || state.state === 'processing' || state.state === 'uploading') { setActiveRecordingState('processing'); setLabel('Processing…'); button.disabled = true; return; }
            var hasLocalAppointmentRecovery = config.recordingPurpose === 'appointment_session' && (localChunks.length > 0 || !!appointmentBlob || appointmentDownloadPending || activeState === 'download_pending');
            if (state.active && !recorder && !localRecorder && state.recovery_available && !state.heartbeat_fresh && !hasLocalAppointmentRecovery) { setActiveRecordingState('interrupted'); setLabel('Clear Interrupted Recording'); button.disabled = false; setError(config.recordingPurpose === 'appointment_session' ? 'Recording was interrupted in this browser. Any undownloaded private recording data was lost.' : 'Recording was interrupted in this browser. The unsaved browser media cannot be recovered.'); return; }
            if (!state.active && activeState === 'processing') { setActiveRecordingState('idle'); setLabel(config.recordingPurpose === 'appointment_session' ? 'Record Privately' : 'Record'); button.disabled = false; }
        }
        rest('/live-rooms/' + config.postId + '/recording').then(restoreState).catch(function (error) { window.console && console.warn('Unable to restore recording state.', error); });
        window.setInterval(function () { rest('/live-rooms/' + config.postId + '/recording').then(restoreState).catch(function (error) { window.console && console.warn('Unable to refresh recording state.', error); }); }, activeState === 'processing' ? 30000 : 15000);
        window.addEventListener('vh360:agora-data-message', function (event) { var data = event.detail || {}; if (data.type === 'live_room_recording_started' || data.type === 'live_room_recording_stopped') { rest('/live-rooms/' + config.postId + '/recording').then(restoreState).catch(function (error) { window.console && console.warn('Unable to refresh recording state.', error); }) } });
        if (!button) { return; }
        if (!hasDesktopSupport()) { setLabel(config.desktopOnlyMessage || 'Recording unavailable'); return; }
        button.classList.remove('vh360-hidden'); button.addEventListener('click', function () { if (activeState === 'failed' && !recorder && activeJobId && publishingPanel && publishingPanel.textContent.indexOf('finalization') !== -1) { retryServerFinalization().catch(function (error) { setError(error && (error.message || error.code) || error); }); } else if (activeState === 'failed' && ((recorder && (recorder.failureStage === 'publishing_prepare_failed' || recorder.failureStage === 'publishing_start_failed')) || (!recorder && activeJobId))) { (recorder ? retryPublishing() : retryServerPublishing()).catch(function (error) { setError(error && (error.message || error.code) || error); }); } else if (activeState === 'recording' || activeState === 'failed' && recorder) { stop().catch(function (error) { setError(error && (error.message || error.code) || error); }); } else if (activeState === 'failed' && config.recordingPurpose === 'appointment_session' && (localChunks.length || appointmentBlob)) { retryPrivateFilePreparation().catch(function () {}); } else if (activeState === 'interrupted') { clearInterruptedRecording().catch(function () {}); } else if (activeState === 'ready') { button.disabled = true; } else if (activeState !== 'processing' && activeState !== 'download_pending') { start(); } });
        if (window.vh360AgoraLifecycle) { window.vh360AgoraLifecycle.registerBeforeLeave(stop); window.vh360AgoraLifecycle.registerBeforeEnd(stopBeforeEnd); }
    }
    document.addEventListener('DOMContentLoaded', init);
})(window, document);
