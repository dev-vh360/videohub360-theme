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
        this.trackVideos = new Map();
        this.previousFeaturedUid = null;
        this.captureStream = null;
    }
    Compositor.prototype.participants = function () { return window.vh360AgoraParticipants instanceof Map ? Array.from(window.vh360AgoraParticipants.values()) : Object.values(window.vh360AgoraParticipants || {}); };
    Compositor.prototype.layout = function () { var manager = window.vh360LayoutManager || window.viewLayoutManager || (window.vh360 && window.vh360.viewLayoutManager); return { view: manager && manager.currentView ? manager.currentView : 'gallery', pinned: manager && typeof manager.getPinnedParticipantUid === 'function' ? manager.getPinnedParticipantUid() : (manager && manager.pinnedParticipantUid), active: window.vh360AgoraState && window.vh360AgoraState.activeSpeaker ? window.vh360AgoraState.activeSpeaker() : null }; };
    Compositor.prototype.videoFor = function (p) {
        var video = p.videoElement || p.video || (p.videoContainerElement && p.videoContainerElement.querySelector && p.videoContainerElement.querySelector('video')) || (p.tileElement && p.tileElement.querySelector && p.tileElement.querySelector('video')) || (p.container && p.container.querySelector && p.container.querySelector('video'));
        if (video) { return video; }
        if (p.videoTrack && p.videoTrack.getMediaStreamTrack) {
            var id = p.uid || p.agoraUid;
            var track = p.videoTrack.getMediaStreamTrack();
            var cached = this.trackVideos.get(id);
            if (!cached || cached.track !== track) {
                if (cached && cached.video) { try { cached.video.pause(); } catch (e) {} cached.video.srcObject = null; cached.video.remove && cached.video.remove(); }
                var hidden = document.createElement('video'); hidden.muted = true; hidden.playsInline = true; hidden.autoplay = true; hidden.srcObject = new MediaStream([track]); hidden.play().catch(function (error) { window.console && console.warn('Unable to play recording fallback video.', error); }); this.trackVideos.set(id, { video: hidden, track: track });
            }
            return this.trackVideos.get(id).video;
        }
        return null;
    };
    Compositor.prototype.drawParticipant = function (p, x, y, w, h, featured) {
        var ctx = this.ctx, pad = featured ? 0 : 8, video = this.videoFor(p), hasCamera = p.cameraOn !== false && p.videoTrack !== null;
        ctx.save();
        ctx.beginPath();
        ctx.rect(x + pad, y + pad, w - pad * 2, h - pad * 2);
        ctx.clip();
        ctx.fillStyle = '#101827'; ctx.fillRect(x + pad, y + pad, w - pad * 2, h - pad * 2);
        if (hasCamera && video && video.readyState >= 2 && video.videoWidth > 0 && video.videoHeight > 0) {
            var scale = Math.max((w - pad * 2) / video.videoWidth, (h - pad * 2) / video.videoHeight), dw = video.videoWidth * scale, dh = video.videoHeight * scale;
            try { ctx.drawImage(video, x + (w - dw) / 2, y + (h - dh) / 2, dw, dh); } catch (e) {}
        } else {
            ctx.fillStyle = '#374151'; ctx.fillRect(x + pad, y + pad, w - pad * 2, h - pad * 2); ctx.fillStyle = '#6b7280'; ctx.beginPath(); ctx.arc(x + w / 2, y + h / 2 - 20, 54, 0, Math.PI * 2); ctx.fill();
        }
        ctx.fillStyle = 'rgba(0,0,0,.68)'; ctx.fillRect(x + 24, y + h - 72, Math.max(120, w - 48), 48);
        ctx.fillStyle = '#fff'; ctx.font = featured ? '30px sans-serif' : '24px sans-serif';
        var name = (p.displayName || p.name || p.uid || 'Participant') + (p.isOriginalHost ? ' · Host' : '') + ((!p.audioTrack || p.audioOn === false) ? ' · Mic off' : '');
        ctx.fillText(name.slice(0, 60), x + 40, y + h - 40);
        ctx.restore();
    };
    Compositor.prototype.drawGrid = function (parts, x, y, width, height) { var self = this, count = Math.max(parts.length, 1), cols = Math.ceil(Math.sqrt(count)), rows = Math.ceil(count / cols), w = width / cols, h = height / rows; parts.forEach(function (p, i) { self.drawParticipant(p, x + (i % cols) * w, y + Math.floor(i / cols) * h, w, h, false); }); };
    Compositor.prototype.featuredParticipant = function (parts, layout) {
        var uid = layout.view === 'focus' ? layout.pinned : (layout.active || this.previousFeaturedUid);
        var featured = uid ? parts.find(function (p) { return String(p.uid || p.agoraUid) === String(uid); }) : null;
        featured = featured || parts.find(function (p) { return p.isOriginalHost || p.isLocal; }) || parts.find(function (p) { return p.videoTrack || p.audioTrack; }) || parts[0];
        if (featured) { this.previousFeaturedUid = String(featured.uid || featured.agoraUid); }
        return featured;
    };
    Compositor.prototype.pruneTrackVideos = function (parts) { var active = new Set(parts.map(function (p) { return String(p.uid || p.agoraUid || ''); })); this.trackVideos.forEach(function (video, id) { if (!active.has(String(id))) { var entry = video.video ? video : { video: video }; try { entry.video.pause(); } catch (e) {} entry.video.srcObject = null; entry.video.remove && entry.video.remove(); this.trackVideos.delete(id); } }, this); };
    Compositor.prototype.draw = function () {
        var ctx = this.ctx, parts = this.participants(), layout = this.layout();
        this.pruneTrackVideos(parts);
        ctx.fillStyle = '#020617'; ctx.fillRect(0, 0, this.width, this.height);
        if ((layout.view === 'speaker' || layout.view === 'focus') && parts.length) {
            var featured = this.featuredParticipant(parts, layout);
            var rest = parts.filter(function (p) { return p !== featured; });
            var featuredHeight = rest.length ? Math.round(this.height * 0.78) : this.height;
            this.drawParticipant(featured, 0, 0, this.width, featuredHeight, true);
            if (rest.length) {
                this.drawGrid(rest, 0, featuredHeight, this.width, this.height - featuredHeight);
            }
        } else { this.drawGrid(parts, 0, 0, this.width, this.height); }
    };
    Compositor.prototype.start = function () { var self = this; this.draw(); this.timer = setInterval(function () { self.draw(); }, 1000 / this.fps); this.captureStream = this.canvas.captureStream(this.fps); return this.captureStream; };
    Compositor.prototype.stop = function () { if (this.timer) { clearInterval(this.timer); } if (this.captureStream) { this.captureStream.getTracks().forEach(function (track) { track.stop(); }); } this.trackVideos.forEach(function (entry) { var video = entry.video ? entry.video : entry; try { video.pause(); } catch (e) {} video.srcObject = null; video.remove && video.remove(); }); this.trackVideos.clear(); this.captureStream = null; };
    window.VH360LiveRoomCompositor = Compositor;
})(window);
