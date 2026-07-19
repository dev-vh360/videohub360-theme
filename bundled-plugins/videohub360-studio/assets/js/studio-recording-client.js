(function (window) {
    'use strict';

    function api(root, nonce, path, options) {
        options = options || {};
        options.headers = options.headers || {};
        if (nonce) { options.headers['X-WP-Nonce'] = nonce; }
        return fetch(root + path, options).then(function (response) {
            return response.json().catch(function () { return {}; }).then(function (data) {
                if (!response.ok) { throw data; }
                return data;
            });
        });
    }

    function supportedMimeType() {
        var candidates = [
            'video/mp4;codecs="avc3.640028,mp4a.40.2"',
            'video/mp4;codecs="avc3.42E01E,mp4a.40.2"',
            'video/mp4;codecs="avc1.640028,mp4a.40.2"',
            'video/mp4;codecs="avc1.42E01E,mp4a.40.2"',
            'video/mp4;codecs="avc1.424028,mp4a.40.2"',
            'video/mp4;codecs=h264,aac',
            'video/mp4',
            'video/webm;codecs=vp9,opus',
            'video/webm;codecs=vp8,opus',
            'video/webm'
        ];
        if (!window.MediaRecorder) { return ''; }
        return candidates.find(function (type) { return MediaRecorder.isTypeSupported(type); }) || '';
    }

    function downloadBlob(blob, filename) {
        var url = URL.createObjectURL(blob);
        var link = document.createElement('a');
        link.href = url;
        link.download = filename;
        document.body.appendChild(link);
        link.click();
        link.remove();
        setTimeout(function () { URL.revokeObjectURL(url); }, 30000);
    }

    function appointmentFilename(mimeType) {
        var now = new Date();
        var pad = function (n) { return String(n).padStart(2, '0'); };
        var ext = mimeType.indexOf('mp4') !== -1 ? 'mp4' : 'webm';
        return 'appointment-recording-' + now.getFullYear() + '-' + pad(now.getMonth() + 1) + '-' + pad(now.getDate()) + '-' + pad(now.getHours()) + pad(now.getMinutes()) + '.' + ext;
    }

    function sha256Blob(blob) {
        if (!window.crypto || !window.crypto.subtle || !blob || typeof blob.arrayBuffer !== 'function') {
            return Promise.resolve('');
        }
        return blob.arrayBuffer().then(function (buffer) {
            return window.crypto.subtle.digest('SHA-256', buffer);
        }).then(function (digest) {
            return Array.from(new Uint8Array(digest)).map(function (byte) { return byte.toString(16).padStart(2, '0'); }).join('');
        });
    }

    function RecordingClient(options) {
        this.restRoot = options.restRoot;
        this.nonce = options.nonce;
        this.jobId = options.jobId;
        this.stream = options.stream;
        this.mimeType = options.mimeType || supportedMimeType();
        this.bits = options.bits || {};
        this.chunkMs = options.chunkMs || 5000;
        this.chunkIndex = 0;
        this.pending = new Set();
        this.failed = new Map();
        this.uploadQueue = [];
        this.activeUploads = 0;
        this.maxConcurrency = options.maxConcurrency || 2;
        this.maxChunkSize = options.maxChunkSize || 8 * 1024 * 1024;
        this.maxTotalRecordingSize = options.maxTotalRecordingSize || 0;
        this.queuedBytes = 0;
        this.terminalError = null;
        this.terminalHandled = false;
        this.browserSessionId = '';
        this.recorder = null;
        this.startedAt = 0;
        this.stoppedAt = 0;
        this.finalChunkCount = 0;
        this.onStatus = options.onStatus || function () {};
        this.onError = options.onError || function () {};
        this.onServerStoppingConfirmed = options.onServerStoppingConfirmed || function () {};
        this.onTerminalUploadError = options.onTerminalUploadError || function () {};
        this.maxRetries = options.maxRetries || 3;
        this.retryDelayMs = options.retryDelayMs || 750;
        this.stopPromise = null;
        this.finalizePromise = null;
        this.publishStarted = false;
        this.serverStopPromise = null;
        this.stopConfirmed = false;
        this.finalized = false;
        this.failureStage = '';
    }

    RecordingClient.prototype.start = function () {
        var self = this;
        var mediaOptions = this.mimeType ? { mimeType: this.mimeType } : {};
        if (this.bits.videoBitsPerSecond) { mediaOptions.videoBitsPerSecond = Number(this.bits.videoBitsPerSecond); }
        if (this.bits.audioBitsPerSecond) { mediaOptions.audioBitsPerSecond = Number(this.bits.audioBitsPerSecond); }
        this.recorder = new MediaRecorder(this.stream, mediaOptions);
        this.mimeType = this.recorder.mimeType || this.mimeType;
        return api(this.restRoot, this.nonce, '/jobs/' + this.jobId + '/recording/start', {
            method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ mime_type: this.mimeType })
        }).then(function (start) {
            var settings = start.upload_settings || {};
            self.browserSessionId = start.browser_session_id;
            self.mimeType = start.mime_type || self.mimeType;
            self.maxChunkSize = Number(settings.max_chunk_size || start.max_chunk_size || self.maxChunkSize);
            self.maxTotalRecordingSize = Number(settings.max_total_recording_size || start.max_total_recording_size || self.maxTotalRecordingSize || 0);
            self.chunkMs = Number(settings.preferred_chunk_duration || start.preferred_chunk_duration || self.chunkMs);
            self.recorder.addEventListener('dataavailable', function (event) {
                if (event.data && event.data.size) { self.queueBlob(event.data); }
            });
            self.recorder.addEventListener('error', function (event) {
                var recorderError = event && event.error ? event.error : new Error('The browser media recorder stopped unexpectedly.');
                if (!recorderError.code) { recorderError.code = 'vh360_studio_media_recorder_failed'; }
                recorderError.retryable = false;
                self.handleTerminalUploadError(recorderError);
            });
            self.recorder.start(self.chunkMs);
            self.startedAt = Date.now();
            self.onStatus('recording');
            return start;
        });
    };

    RecordingClient.prototype.queueBlob = function (blob) {
        if (this.terminalError) { return; }
        this.queuedBytes += blob.size || 0;
        if (this.maxTotalRecordingSize && this.queuedBytes > this.maxTotalRecordingSize) {
            this.handleTerminalUploadError({ code: 'vh360_studio_recording_too_large', message: 'Recording exceeds the allowed size.' });
            return;
        }
        var offset = 0;
        var type = blob.type || this.mimeType;
        do {
            var end = Math.min(blob.size, offset + this.maxChunkSize);
            var part = blob.slice(offset, end, type);
            var index = this.chunkIndex++;
            this.pending.add(index);
            this.uploadQueue.push({ index: index, blob: part });
            offset = end;
        } while (offset < blob.size);
        this.scheduleUploads();
    };

    RecordingClient.prototype.scheduleUploads = function () {
        var self = this;
        while (this.activeUploads < this.maxConcurrency && this.uploadQueue.length) {
            (function (task) {
                self.activeUploads += 1;
                self.uploadChunkWithRetry(task.blob, task.index, task.attempt || 0).catch(function (error) { self.onError(error); }).finally(function () {
                    self.activeUploads = Math.max(0, self.activeUploads - 1);
                    self.scheduleUploads();
                });
            })(this.uploadQueue.shift());
        }
    };

    RecordingClient.prototype.isTerminalUploadError = function (error) {
        var code = error && (error.code || error.error || error.error_code);
        return ['vh360_studio_recording_too_large','vh360_studio_chunk_too_large','vh360_studio_invalid_chunk_type','vh360_studio_chunk_type_mismatch','vh360_studio_missing_chunks','vh360_studio_chunk_unreadable','vh360_studio_invalid_chunk_path','vh360_studio_chunk_integrity_failed','vh360_studio_invalid_recording_path','vh360_studio_recording_summary_mismatch','vh360_studio_invalid_recording_size','vh360_studio_invalid_recording_type','vh360_studio_invalid_recording_file'].indexOf(code) !== -1 || error && (error.retryable === false || error.data && error.data.retryable === false);
    };

    RecordingClient.prototype.handleTerminalUploadError = function (error) {
        this.terminalError = error || { code: 'vh360_studio_terminal_upload_failed' };
        this.failureStage = 'terminal_upload_failed';
        try { if (this.recorder && this.recorder.state !== 'inactive') { this.recorder.stop(); } } catch (e) {}
        this.uploadQueue = [];
        this.pending.clear();
        this.failed.clear();
        this.onError(this.terminalError);
        if (!this.terminalHandled) { this.terminalHandled = true; this.onTerminalUploadError(this.terminalError); }
    };

    RecordingClient.prototype.uploadChunk = function (blob, index) {
        var self = this;
        return sha256Blob(blob).then(function (checksum) {
            var form = new FormData();
            form.append('browser_session_id', self.browserSessionId);
            form.append('chunk_index', String(index));
            form.append('mime_type', blob.type || self.mimeType);
            if (checksum) { form.append('chunk_checksum', checksum); }
            form.append('chunk', blob, 'chunk-' + index + (self.mimeType.indexOf('mp4') !== -1 ? '.mp4' : '.webm'));
            return api(self.restRoot, self.nonce, '/jobs/' + self.jobId + '/chunks', { method: 'POST', body: form });
        }).then(function () {
            self.pending.delete(index);
            self.failed.delete(index);
        }).catch(function (error) {
            self.pending.delete(index);
            if (self.terminalError) { throw self.terminalError; }
            if (self.isTerminalUploadError(error)) { self.handleTerminalUploadError(error); throw error; }
            self.failed.set(index, blob);
            throw error;
        });
    };



    RecordingClient.prototype.retryDelay = function (attempt) {
        var delay = this.retryDelayMs * Math.pow(2, Math.max(0, attempt));
        return new Promise(function (resolve) { window.setTimeout(resolve, delay); });
    };

    RecordingClient.prototype.uploadChunkWithRetry = function (blob, index, attempt) {
        var self = this;
        return this.uploadChunk(blob, index).catch(function (error) {
            if (self.isTerminalUploadError(error)) { throw error; }
            if (attempt < self.maxRetries) {
                return self.retryDelay(attempt).then(function () { return self.uploadChunkWithRetry(blob, index, attempt + 1); });
            }
            throw error;
        });
    };

    RecordingClient.prototype.retryFailedChunks = function () {
        var self = this;
        Array.from(this.failed.entries()).forEach(function (entry) {
            var index = entry[0];
            var blob = entry[1];
            if (!self.pending.has(index)) {
                self.pending.add(index);
                self.uploadQueue.push({ index: index, blob: blob, attempt: 0 });
            }
        });
        this.scheduleUploads();
        return this.waitForUploads();
    };

    RecordingClient.prototype.waitForUploads = function () {
        var self = this;
        return new Promise(function (resolve) {
            function poll() {
                if (!self.pending.size && !self.uploadQueue.length && !self.activeUploads) { resolve(); return; }
                window.setTimeout(poll, 250);
            }
            poll();
        });
    };

    RecordingClient.prototype.hasUnsavedData = function () {
        return !this.terminalError && (!!(this.recorder && this.recorder.state === 'recording') || this.pending.size > 0 || this.uploadQueue.length > 0 || this.activeUploads > 0 || this.failed.size > 0 || !!this.stopPromise || !!this.finalizePromise && !this.publishStarted);
    };

    RecordingClient.prototype.markServerStopping = function () {
        var self = this;
        if (this.terminalError) { return Promise.reject(this.terminalError); }
        if (this.stopConfirmed) { return Promise.resolve({ status: 'stopping' }); }
        if (this.serverStopPromise) { return this.serverStopPromise; }
        var duration = Math.max(0, Math.round((this.stoppedAt - this.startedAt) / 1000));
        this.serverStopPromise = api(this.restRoot, this.nonce, '/jobs/' + this.jobId + '/recording/stop', {
            method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ duration_seconds: duration })
        }).then(function (stopped) { self.stopConfirmed = true; self.onServerStoppingConfirmed(stopped); return stopped; }).catch(function (error) { self.failureStage = 'stop_failed'; throw error; }).finally(function () { self.serverStopPromise = null; });
        return this.serverStopPromise;
    };

    RecordingClient.prototype.finishStoppedRecording = function () {
        var self = this;
        return this.markServerStopping().then(function () { return self.waitForUploads(); }).then(function () {
            if (self.failed.size) { return self.retryFailedChunks().then(function () { if (self.failed.size) { throw new Error('Some recording chunks failed to upload. Retry is still available while this page remains open.'); } }); }
        });
    };

    RecordingClient.prototype.stop = function () {
        var self = this;
        if (this.terminalError) { return Promise.reject(this.terminalError); }
        if (this.stopPromise) { return this.stopPromise; }
        if (!this.recorder || this.recorder.state === 'inactive') {
            this.stopPromise = this.stoppedAt ? this.finishStoppedRecording() : Promise.resolve();
            return this.stopPromise.finally(function () { self.stopPromise = null; });
        }
        this.onStatus('stopping');
        this.stopPromise = new Promise(function (resolve, reject) {
            self.recorder.addEventListener('stop', resolve, { once: true });
            try { self.recorder.stop(); } catch (error) { reject(error); }
        }).then(function () {
            self.stoppedAt = Date.now();
            self.finalChunkCount = self.chunkIndex;
            return self.finishStoppedRecording();
        }).finally(function () { self.stopPromise = null; });
        return this.stopPromise;
    };

    RecordingClient.prototype.statusValues = function (response) {
        if (!response) { return []; }
        return ['job_status', 'status', 'publish_provider_status', 'provider_status', 'publish_status'].map(function (key) {
            return response[key] ? String(response[key]).toLowerCase() : '';
        }).filter(Boolean);
    };

    RecordingClient.prototype.publishIsReady = function (response) {
        if (response && response.replay_video_id && response.replay_url) { return true; }
        var ready = ['ready', 'published', 'local_media_ready', 'publitio_ready', 'publitio_direct_ready', 'bunny_stream_ready'];
        return this.statusValues(response).some(function (status) { return ready.indexOf(status) !== -1; });
    };

    RecordingClient.prototype.publishIsFailed = function (response) {
        var failed = ['failed', 'error', 'publish_failed', 'upload_failed', 'bunny_stream_failed', 'publitio_failed'];
        return this.statusValues(response).some(function (status) { return failed.indexOf(status) !== -1; });
    };

    RecordingClient.prototype.pollPublishingStatus = function (intervalMs, timeoutMs) {
        var self = this, started = Date.now();
        function poll() {
            return api(self.restRoot, self.nonce, '/jobs/' + self.jobId + '/publishing/status', { method: 'GET' }).then(function (status) {
                if (self.publishIsFailed(status)) { self.onStatus('failed'); throw status; }
                if (self.publishIsReady(status)) { self.onStatus('ready'); return status; }
                if (Date.now() - started > timeoutMs) { return status; }
                self.onStatus('processing');
                return new Promise(function (resolve) { window.setTimeout(resolve, intervalMs); }).then(poll);
            });
        }
        return poll();
    };

    RecordingClient.prototype.secureLocalRecordingData = function () {
        var self = this;
        if (this.finalizePromise) { return this.finalizePromise; }
        this.finalizePromise = this.stop().then(function () {
            if (self.finalized) { return Promise.resolve({ status: 'processing' }); }
            self.onStatus('uploading');
            return api(self.restRoot, self.nonce, '/jobs/' + self.jobId + '/recording/finalize', {
                method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ expected_chunks: self.finalChunkCount })
            }).then(function (finalized) { self.finalized = true; return finalized; }).catch(function (error) { self.failureStage = 'finalization_failed'; throw error; });
        }).then(function () {
            self.onStatus('processing');
            return api(self.restRoot, self.nonce, '/jobs/' + self.jobId + '/publishing/prepare', { method: 'POST', headers: { 'Content-Type': 'application/json' } }).catch(function (error) { self.failureStage = 'publishing_prepare_failed'; throw error; });
        }).then(function () {
            return api(self.restRoot, self.nonce, '/jobs/' + self.jobId + '/publishing/publish', { method: 'POST', headers: { 'Content-Type': 'application/json' } }).catch(function (error) { self.failureStage = 'publishing_start_failed'; throw error; });
        }).then(function (published) {
            if (self.publishIsFailed(published)) { self.failureStage = 'provider_failed'; throw published; }
            self.publishStarted = true;
            self.onStatus(self.publishIsReady(published) ? 'ready' : 'processing');
            return published;
        }).finally(function () { self.finalizePromise = null; });
        return this.finalizePromise;
    };



    RecordingClient.prototype.retryPublishing = function () {
        var self = this;
        if (this.terminalError) { return Promise.reject(this.terminalError); }
        if (!this.finalized) { return this.secureLocalRecordingData(); }
        this.failureStage = '';
        this.onStatus('processing');
        return api(this.restRoot, this.nonce, '/jobs/' + this.jobId + '/publishing/prepare', { method: 'POST', headers: { 'Content-Type': 'application/json' } }).catch(function (error) {
            self.failureStage = 'publishing_prepare_failed';
            throw error;
        }).then(function () {
            return api(self.restRoot, self.nonce, '/jobs/' + self.jobId + '/publishing/publish', { method: 'POST', headers: { 'Content-Type': 'application/json' } }).catch(function (error) {
                self.failureStage = 'publishing_start_failed';
                throw error;
            });
        }).then(function (published) {
            if (self.publishIsFailed(published)) { self.failureStage = 'provider_failed'; throw published; }
            self.publishStarted = true;
            self.onStatus(self.publishIsReady(published) ? 'ready' : 'processing');
            return published;
        });
    };

    RecordingClient.prototype.finalizeAndPublish = function () {
        var self = this;
        return this.secureLocalRecordingData().then(function (published) {
            if (self.publishIsReady(published)) { return published; }
            return self.pollPublishingStatus(5000, 10 * 60 * 1000);
        });
    };

    window.VH360StudioRecordingClient = { supportedMimeType: supportedMimeType, downloadBlob: downloadBlob, appointmentFilename: appointmentFilename, RecordingClient: RecordingClient, api: api, sha256Blob: sha256Blob };
})(window);
