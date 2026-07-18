(function (window) {
    'use strict';
    function Compositor(options) {
        options = options || {};
        this.width = options.width || 1920;
        this.height = options.height || 1080;
        this.fps = options.fps || 30;
        this.canvas = document.createElement('canvas');
        this.canvas.width = this.width;
        this.canvas.height = this.height;
        this.ctx = this.canvas.getContext('2d');
        this.timer = null;
    }
    Compositor.prototype.participants = function () { return window.vh360AgoraParticipants instanceof Map ? Array.from(window.vh360AgoraParticipants.values()) : Object.values(window.vh360AgoraParticipants || {}); };
    Compositor.prototype.draw = function () {
        var ctx = this.ctx, parts = this.participants(), count = Math.max(parts.length, 1), cols = Math.ceil(Math.sqrt(count)), rows = Math.ceil(count / cols), w = this.width / cols, h = this.height / rows;
        ctx.fillStyle = '#101827'; ctx.fillRect(0, 0, this.width, this.height);
        parts.forEach(function (p, i) {
            var x = (i % cols) * w, y = Math.floor(i / cols) * h;
            ctx.fillStyle = '#1f2937'; ctx.fillRect(x + 8, y + 8, w - 16, h - 16);
            var video = p.videoElement || p.video || (p.container && p.container.querySelector && p.container.querySelector('video'));
            if (video && video.readyState >= 2 && !video.paused) {
                try { ctx.drawImage(video, x + 8, y + 8, w - 16, h - 16); } catch (e) {}
            } else {
                ctx.fillStyle = '#374151'; ctx.beginPath(); ctx.arc(x + w / 2, y + h / 2 - 20, 54, 0, Math.PI * 2); ctx.fill();
            }
            ctx.fillStyle = 'rgba(0,0,0,.65)'; ctx.fillRect(x + 24, y + h - 68, w - 48, 44);
            ctx.fillStyle = '#fff'; ctx.font = '24px sans-serif'; ctx.fillText(p.displayName || p.name || p.uid || 'Participant', x + 40, y + h - 39);
        });
    };
    Compositor.prototype.start = function () { var self = this; this.draw(); this.timer = setInterval(function () { self.draw(); }, 1000 / this.fps); return this.canvas.captureStream(this.fps); };
    Compositor.prototype.stop = function () { if (this.timer) { clearInterval(this.timer); } };
    window.VH360LiveRoomCompositor = Compositor;
})(window);
