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
        this.captureStream = null;
    }
    Compositor.prototype.participants = function () { return window.vh360AgoraParticipants instanceof Map ? Array.from(window.vh360AgoraParticipants.values()) : Object.values(window.vh360AgoraParticipants || {}); };
    Compositor.prototype.layout = function () { var manager = window.vh360LayoutManager || window.viewLayoutManager || (window.vh360 && window.vh360.viewLayoutManager); return { view: manager && manager.currentView ? manager.currentView : 'speaker', pinned: manager && typeof manager.getPinnedParticipantUid === 'function' ? manager.getPinnedParticipantUid() : (manager && manager.pinnedParticipantUid), active: window.vh360AgoraState && window.vh360AgoraState.activeSpeaker ? window.vh360AgoraState.activeSpeaker() : null }; };
    Compositor.prototype.uidFor = function (p) { return p ? String(p.uid || p.agoraUid || '') : ''; };
    Compositor.prototype.findParticipantByUid = function (parts, uid) { var self = this; return uid ? parts.find(function (p) { return self.uidFor(p) === String(uid); }) : null; };
    Compositor.prototype.resolveSpeakerFeaturedParticipant = function (parts, layout) {
        var active = this.findParticipantByUid(parts, layout.active);
        if (active) { return active; }
        return parts.find(function (p) { return p.isOriginalHost; })
            || parts.find(function (p) { return p.isLocal; })
            || parts.find(function (p) { return p.tileElement && p.tileElement.classList && p.tileElement.classList.contains('is-featured'); })
            || parts[0]
            || null;
    };
    Compositor.prototype.resolveFeaturedParticipant = function (parts, layout) {
        if (!parts.length || layout.view === 'gallery') { return null; }
        if (layout.view === 'focus') {
            return this.findParticipantByUid(parts, layout.pinned) || this.resolveSpeakerFeaturedParticipant(parts, layout);
        }
        return this.resolveSpeakerFeaturedParticipant(parts, layout);
    };
    Compositor.prototype.metrics = function () {
        var scale = this.width / 1920;
        return {
            outer: Math.max(8, Math.round(24 * scale)),
            gap: Math.max(6, Math.round(16 * scale)),
            targetThumbW: Math.max(142, Math.round(320 * scale)),
            minFeaturedH: Math.round(this.height * 0.55)
        };
    };
    Compositor.prototype.fitAspectRect = function (x, y, width, height, aspect) {
        var w = width, h = w / aspect;
        if (h > height) { h = height; w = h * aspect; }
        return { x: x + (width - w) / 2, y: y + (height - h) / 2, width: w, height: h };
    };
    Compositor.prototype.calculateThumbnailRail = function (count, availableWidth, maxHeight, metrics) {
        var gap = metrics.gap, aspect = 16 / 9, best = null;
        if (!count) { return { rows: 0, cols: 0, tileWidth: 0, tileHeight: 0, height: 0 }; }
        for (var rows = 1; rows <= count; rows++) {
            var cols = Math.ceil(count / rows);
            var byWidth = (availableWidth - gap * (cols - 1)) / cols;
            var byHeight = (maxHeight - gap * (rows - 1)) / rows * aspect;
            var tileWidth = Math.min(metrics.targetThumbW, byWidth, byHeight);
            var tileHeight = tileWidth / aspect;
            if (tileWidth <= 0 || tileHeight <= 0) { continue; }
            var railHeight = tileHeight * rows + gap * (rows - 1);
            var score = tileWidth * tileHeight;
            if (!best || score > best.score || (score === best.score && rows < best.rows)) { best = { rows: rows, cols: cols, tileWidth: tileWidth, tileHeight: tileHeight, height: railHeight, score: score }; }
        }
        return best || { rows: count, cols: 1, tileWidth: Math.max(1, availableWidth), tileHeight: Math.max(1, availableWidth / (16 / 9)), height: maxHeight };
    };
    Compositor.prototype.buildSpeakerLayout = function (parts, featured) {
        var metrics = this.metrics(), rects = [], rest = parts.filter(function (p) { return p !== featured; });
        if (!featured) { return rects; }
        if (!rest.length) {
            var single = this.fitAspectRect(metrics.outer, metrics.outer, this.width - metrics.outer * 2, this.height - metrics.outer * 2, 16 / 9);
            single.participant = featured; single.role = 'featured'; return [single];
        }
        var availableWidth = this.width - metrics.outer * 2;
        var maxRailHeight = Math.max(metrics.targetThumbW / (16 / 9), this.height - metrics.minFeaturedH - metrics.outer * 2 - metrics.gap);
        maxRailHeight = Math.min(Math.round(this.height * 0.36), maxRailHeight);
        var rail = this.calculateThumbnailRail(rest.length, availableWidth, maxRailHeight, metrics);
        var stageHeight = this.height - metrics.outer * 2 - metrics.gap - rail.height;
        var stage = this.fitAspectRect(metrics.outer, metrics.outer, availableWidth, stageHeight, 16 / 9);
        stage.participant = featured; stage.role = 'featured'; rects.push(stage);
        var railY = metrics.outer + stageHeight + metrics.gap;
        var cols = rail.cols;
        for (var i = 0; i < rest.length; i++) {
            var row = Math.floor(i / cols), col = i % cols, inRow = Math.min(cols, rest.length - row * cols);
            var rowWidth = inRow * rail.tileWidth + (inRow - 1) * metrics.gap;
            rects.push({ participant: rest[i], x: metrics.outer + (availableWidth - rowWidth) / 2 + col * (rail.tileWidth + metrics.gap), y: railY + row * (rail.tileHeight + metrics.gap), width: rail.tileWidth, height: rail.tileHeight, role: 'thumbnail' });
        }
        return rects;
    };
    Compositor.prototype.buildFocusLayout = function (parts, featured) { return this.buildSpeakerLayout(parts, featured); };
    Compositor.prototype.buildGalleryLayout = function (parts) {
        var metrics = this.metrics(), count = parts.length, rects = [], aspect = 16 / 9;
        if (!count) { return rects; }
        var availableWidth = this.width - metrics.outer * 2, availableHeight = this.height - metrics.outer * 2, best = null;
        for (var cols = 1; cols <= count; cols++) {
            var rows = Math.ceil(count / cols);
            var tileWidthFromWidth = (availableWidth - metrics.gap * (cols - 1)) / cols;
            var tileHeightFromWidth = tileWidthFromWidth / aspect;
            var tileHeightFromHeight = (availableHeight - metrics.gap * (rows - 1)) / rows;
            var tileWidthFromHeight = tileHeightFromHeight * aspect;
            var tileWidth = Math.min(tileWidthFromWidth, tileWidthFromHeight);
            var tileHeight = Math.min(tileHeightFromWidth, tileHeightFromHeight);
            if (tileWidth <= 0 || tileHeight <= 0) { continue; }
            var area = tileWidth * tileHeight;
            if (!best || area > best.area || (area === best.area && cols < best.cols)) { best = { cols: cols, rows: rows, tileWidth: tileWidth, tileHeight: tileHeight, area: area }; }
        }
        best = best || { cols: 1, rows: count, tileWidth: availableWidth, tileHeight: availableWidth / aspect };
        var gridWidth = best.cols * best.tileWidth + (best.cols - 1) * metrics.gap;
        var gridHeight = best.rows * best.tileHeight + (best.rows - 1) * metrics.gap;
        var startX = metrics.outer + (availableWidth - gridWidth) / 2, startY = metrics.outer + (availableHeight - gridHeight) / 2;
        parts.forEach(function (p, i) { rects.push({ participant: p, x: startX + (i % best.cols) * (best.tileWidth + metrics.gap), y: startY + Math.floor(i / best.cols) * (best.tileHeight + metrics.gap), width: best.tileWidth, height: best.tileHeight, role: 'gallery' }); });
        return rects;
    };
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
    Compositor.prototype.fitTextWithEllipsis = function (ctx, text, maxWidth) {
        text = String(text || '');
        if (ctx.measureText(text).width <= maxWidth) { return text; }
        var ellipsis = '…', low = 0, high = text.length;
        while (low < high) { var mid = Math.ceil((low + high) / 2); if (ctx.measureText(text.slice(0, mid) + ellipsis).width <= maxWidth) { low = mid; } else { high = mid - 1; } }
        return text.slice(0, low) + ellipsis;
    };
    Compositor.prototype.styleForRole = function (role, w, h) {
        var scale = Math.max(0.45, Math.min(1, w / 320));
        if (role === 'featured') { return { pad: 0, label: Math.max(38, Math.round(h * 0.07)), font: Math.max(20, Math.round(h * 0.032)), textPad: 16, avatar: Math.max(46, Math.round(Math.min(w, h) * 0.08)) }; }
        if (role === 'gallery') { return { pad: Math.max(4, Math.round(8 * scale)), label: Math.max(26, Math.round(h * 0.12)), font: Math.max(13, Math.round(18 * scale)), textPad: Math.max(8, Math.round(12 * scale)), avatar: Math.max(28, Math.round(Math.min(w, h) * 0.12)) }; }
        return { pad: Math.max(3, Math.round(6 * scale)), label: Math.max(20, Math.round(h * 0.18)), font: Math.max(11, Math.round(15 * scale)), textPad: Math.max(6, Math.round(10 * scale)), avatar: Math.max(22, Math.round(Math.min(w, h) * 0.14)) };
    };
    Compositor.prototype.drawParticipant = function (p, rect) {
        var ctx = this.ctx, x = rect.x, y = rect.y, w = rect.width, h = rect.height, style = this.styleForRole(rect.role, w, h), pad = style.pad, video = this.videoFor(p), hasCamera = p.cameraOn !== false && p.videoTrack !== null;
        var viewport = this.fitAspectRect(x + pad, y + pad, Math.max(1, w - pad * 2), Math.max(1, h - pad * 2), 16 / 9);
        ctx.save(); ctx.beginPath(); ctx.rect(viewport.x, viewport.y, viewport.width, viewport.height); ctx.clip();
        ctx.fillStyle = '#101827'; ctx.fillRect(viewport.x, viewport.y, viewport.width, viewport.height);
        if (hasCamera && video && video.readyState >= 2 && video.videoWidth > 0 && video.videoHeight > 0) {
            var scale = Math.max(viewport.width / video.videoWidth, viewport.height / video.videoHeight), dw = video.videoWidth * scale, dh = video.videoHeight * scale;
            try { ctx.drawImage(video, viewport.x + (viewport.width - dw) / 2, viewport.y + (viewport.height - dh) / 2, dw, dh); } catch (e) {}
        } else {
            ctx.fillStyle = '#374151'; ctx.fillRect(viewport.x, viewport.y, viewport.width, viewport.height); ctx.fillStyle = '#6b7280'; ctx.beginPath(); ctx.arc(viewport.x + viewport.width / 2, viewport.y + viewport.height / 2 - style.avatar * 0.25, style.avatar, 0, Math.PI * 2); ctx.fill();
        }
        var labelX = viewport.x + style.textPad, labelW = Math.max(1, viewport.width - style.textPad * 2), labelH = Math.min(style.label, viewport.height), labelY = viewport.y + viewport.height - labelH;
        ctx.fillStyle = 'rgba(0,0,0,.68)'; ctx.fillRect(labelX - style.textPad / 2, labelY, labelW + style.textPad, labelH);
        ctx.fillStyle = '#fff'; ctx.font = style.font + 'px sans-serif'; ctx.textBaseline = 'middle';
        var status = (p.isOriginalHost ? ' · Host' : '') + ((!p.audioTrack || p.audioOn === false) ? (rect.role === 'thumbnail' ? ' · Muted' : ' · Mic off') : '');
        var name = (p.displayName || p.name || p.uid || 'Participant') + status;
        ctx.fillText(this.fitTextWithEllipsis(ctx, name, labelW), labelX, labelY + labelH / 2);
        ctx.restore();
    };
    Compositor.prototype.pruneTrackVideos = function (parts) { var active = new Set(parts.map(function (p) { return String(p.uid || p.agoraUid || ''); })); this.trackVideos.forEach(function (video, id) { if (!active.has(String(id))) { var entry = video.video ? video : { video: video }; try { entry.video.pause(); } catch (e) {} entry.video.srcObject = null; entry.video.remove && entry.video.remove(); this.trackVideos.delete(id); } }, this); };
    Compositor.prototype.draw = function () {
        var ctx = this.ctx, parts = this.participants(), layout = this.layout(), rects;
        this.pruneTrackVideos(parts);
        ctx.fillStyle = '#020617'; ctx.fillRect(0, 0, this.width, this.height);
        if ((layout.view === 'speaker' || layout.view === 'focus') && parts.length) {
            var featured = this.resolveFeaturedParticipant(parts, layout);
            rects = layout.view === 'focus' ? this.buildFocusLayout(parts, featured) : this.buildSpeakerLayout(parts, featured);
        } else { rects = this.buildGalleryLayout(parts); }
        rects.forEach(function (rect) { this.drawParticipant(rect.participant, rect); }, this);
    };
    Compositor.prototype.start = function () { var self = this; this.draw(); this.timer = setInterval(function () { self.draw(); }, 1000 / this.fps); this.captureStream = this.canvas.captureStream(this.fps); return this.captureStream; };
    Compositor.prototype.stop = function () { if (this.timer) { clearInterval(this.timer); } if (this.captureStream) { this.captureStream.getTracks().forEach(function (track) { track.stop(); }); } this.trackVideos.forEach(function (entry) { var video = entry.video ? entry.video : entry; try { video.pause(); } catch (e) {} video.srcObject = null; video.remove && video.remove(); }); this.trackVideos.clear(); this.captureStream = null; };
    window.VH360LiveRoomCompositor = Compositor;
})(window);
