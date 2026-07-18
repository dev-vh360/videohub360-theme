(function (window) {
    'use strict';

    function supportedMimeType() {
        var candidates = ['video/mp4;codecs=h264,aac', 'video/mp4', 'video/webm;codecs=vp9,opus', 'video/webm;codecs=vp8,opus', 'video/webm'];
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

    window.VH360StudioRecordingClient = { supportedMimeType: supportedMimeType, downloadBlob: downloadBlob, appointmentFilename: appointmentFilename };
})(window);
