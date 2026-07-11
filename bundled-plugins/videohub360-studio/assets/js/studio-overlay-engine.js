(function () {
    'use strict';

    const root = document.querySelector('[data-vh360-studio-dashboard]');
    const compositor = window.VH360StudioCompositor;
    if (!root || !compositor) { return; }

    const previewCanvas = root.querySelector('[data-preview-overlay-canvas]');
    const slots = ['lowerThird', 'bible', 'countdown'];
    const state = {
        preview: { lowerThird: null, bible: null, countdown: null },
        program: { lowerThird: null, bible: null, countdown: null },
    };
    const subscribers = new Set();
    const renderers = new Map();
    const bibleLayoutCache = new Map();
    let previewContext = previewCanvas ? previewCanvas.getContext('2d') : null;
    let previewFrame = null;
    let outputSize = compositor.getOutputSize ? compositor.getOutputSize() : { width: 1920, height: 1080, fps: 30 };
    let destroyed = false;

    function clone(value) { return value ? JSON.parse(JSON.stringify(value)) : null; }
    function now() { return performance.now(); }
    function clamp(value, min, max) { return Math.min(Math.max(value, min), max); }
    function durationOf(item) { return clamp(Number(item && item.config && item.config.behavior && item.config.behavior.durationMs) || 0, 0, 2000); }
    function isSlot(slot) { return slots.indexOf(slot) !== -1; }
    function normalizeSlot(slot) { return slot === 'lower_third' ? 'lowerThird' : slot; }

    function makeItem(config, phase, runtime) {
        const timestamp = now();
        return { config: clone(config), runtime: clone(runtime) || null, phase, startedAt: timestamp, visibleAt: 0, previousConfig: null, previousRuntime: null };
    }

    function eventDetail(slot, item) {
        return { slot, active: Boolean(item), phase: item ? item.phase : 'hidden', config: item ? clone(item.config) : null, runtime: item ? clone(item.runtime) : null };
    }

    function dispatch(name, detail) { root.dispatchEvent(new CustomEvent(name, { bubbles: true, detail })); }
    function notify() { subscribers.forEach((callback) => callback(getState())); }
    function changed(scope, slot) {
        dispatch(scope === 'preview' ? 'vh360:studio-overlay:preview-change' : 'vh360:studio-overlay:program-change', eventDetail(slot, state[scope][slot]));
        notify();
        requestFrames();
    }
    function transition(name, slot, item) { dispatch(name, eventDetail(slot, item)); }
    function requestProgramFrame() {
        if (!destroyed && compositor.requestFrame) { compositor.requestFrame(); }
    }

    function requestPreviewFrame() {
        if (!destroyed) { schedulePreviewFrame(); }
    }

    function requestFrames() {
        requestProgramFrame();
        requestPreviewFrame();
    }

    function stage(slot, config, options = {}) {
        slot = normalizeSlot(slot);
        if (!isSlot(slot)) { return false; }
        state.preview[slot] = makeItem(config, 'visible', options.runtime);
        state.preview[slot].visibleAt = now();
        changed('preview', slot);
        return true;
    }

    function clearPreview(slot) {
        slot = normalizeSlot(slot);
        if (!isSlot(slot)) { return false; }
        state.preview[slot] = null;
        changed('preview', slot);
        return true;
    }

    function takeToProgram(slot, options = {}) {
        slot = normalizeSlot(slot);
        if (!isSlot(slot) || !state.preview[slot]) { return false; }
        state.program[slot] = makeItem(state.preview[slot].config, 'entering', options.runtime || state.preview[slot].runtime);
        transition('vh360:studio-overlay:transition-start', slot, state.program[slot]);
        changed('program', slot);
        return true;
    }

    function updateProgram(slot, options = {}) {
        slot = normalizeSlot(slot);
        if (!isSlot(slot) || !state.preview[slot] || !state.program[slot]) { return false; }
        const current = state.program[slot];
        const runtime = options.preserveRuntime === false ? (options.runtime || state.preview[slot].runtime) : current.runtime;
        const next = makeItem(options.config || state.preview[slot].config, 'updating', runtime);
        next.previousConfig = clone(current.config);
        next.previousRuntime = clone(current.runtime);
        state.program[slot] = next;
        transition('vh360:studio-overlay:transition-start', slot, next);
        changed('program', slot);
        return true;
    }

    function resetProgram(slot, config, options = {}) {
        slot = normalizeSlot(slot);
        if (!isSlot(slot) || !state.program[slot]) { return false; }
        const current = state.program[slot];
        const next = makeItem(config, options.phase || 'updating', options.runtime);
        if (options.crossfade !== false) {
            next.previousConfig = clone(current.config);
            next.previousRuntime = clone(current.runtime);
            transition('vh360:studio-overlay:transition-start', slot, next);
        } else {
            next.phase = 'visible';
            next.visibleAt = now();
        }
        state.program[slot] = next;
        changed('program', slot);
        return true;
    }

    function hideProgram(slot) {
        slot = normalizeSlot(slot);
        if (!isSlot(slot) || !state.program[slot]) { return false; }
        state.program[slot].phase = 'exiting';
        state.program[slot].startedAt = now();
        transition('vh360:studio-overlay:transition-start', slot, state.program[slot]);
        changed('program', slot);
        return true;
    }

    function clearProgram(slot, options) {
        slot = normalizeSlot(slot);
        if (!isSlot(slot)) { return false; }
        state.program[slot] = null;
        changed('program', slot);
        if (!options || !options.silentTransition) { transition('vh360:studio-overlay:transition-end', slot, null); }
        return true;
    }

    function clearAllProgram(options) { slots.forEach((slot) => clearProgram(slot, options)); }
    function getState() { return clone(state); }
    function subscribe(callback) { subscribers.add(callback); return () => subscribers.delete(callback); }
    function setRuntime(scope, slot, runtime) {
        slot = normalizeSlot(slot);
        if (!state[scope] || !isSlot(slot) || !state[scope][slot]) { return false; }
        state[scope][slot].runtime = clone(runtime);
        changed(scope, slot);
        return true;
    }
    function getRuntime(scope, slot) {
        slot = normalizeSlot(slot);
        return state[scope] && isSlot(slot) && state[scope][slot] ? clone(state[scope][slot].runtime) : null;
    }

    function syncPreviewCanvasSize() {
        if (!previewCanvas) { return; }
        outputSize = compositor.getOutputSize ? compositor.getOutputSize() : outputSize;
        if (previewCanvas.width !== outputSize.width) { previewCanvas.width = outputSize.width; }
        if (previewCanvas.height !== outputSize.height) { previewCanvas.height = outputSize.height; }
    }

    function schedulePreviewFrame() {
        if (destroyed || previewFrame || !previewContext) { return; }
        previewFrame = window.requestAnimationFrame(() => {
            previewFrame = null;
            drawPreview();
        });
    }

    function drawPreview() {
        if (!previewContext || !previewCanvas) { return; }
        syncPreviewCanvasSize();
        previewContext.clearRect(0, 0, previewCanvas.width, previewCanvas.height);
        const frame = { width: previewCanvas.width, height: previewCanvas.height, now: now(), preview: true };
        Array.from(renderers.values()).sort((a, b) => a.order - b.order).forEach((renderer) => {
            const item = state.preview[renderer.slot];
            if (item && typeof renderer.drawPreview === 'function') { renderer.drawPreview(previewContext, item, frame); }
        });
    }

    function rgba(hex, opacity) {
        const value = /^#[0-9a-f]{6}$/i.test(hex || '') ? hex.substring(1) : '0f172a';
        const intValue = parseInt(value, 16);
        return 'rgba(' + ((intValue >> 16) & 255) + ',' + ((intValue >> 8) & 255) + ',' + (intValue & 255) + ',' + clamp(opacity, 0, 100) / 100 + ')';
    }

    function roundRect(context, x, y, width, height, radius) {
        radius = Math.min(radius, width / 2, height / 2);
        if (typeof context.roundRect === 'function') {
            context.beginPath(); context.roundRect(x, y, width, height, radius); return;
        }
        context.beginPath();
        context.moveTo(x + radius, y);
        context.lineTo(x + width - radius, y);
        context.quadraticCurveTo(x + width, y, x + width, y + radius);
        context.lineTo(x + width, y + height - radius);
        context.quadraticCurveTo(x + width, y + height, x + width - radius, y + height);
        context.lineTo(x + radius, y + height);
        context.quadraticCurveTo(x, y + height, x, y + height - radius);
        context.lineTo(x, y + radius);
        context.quadraticCurveTo(x, y, x + radius, y);
    }

    function fontFor(size, weight) {
        return weight + ' ' + size + 'px system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif';
    }

    function ellipsizeText(context, text, maxWidth) {
        if (context.measureText(text).width <= maxWidth) { return text; }
        const ellipsis = '…';
        const chars = Array.from(text);
        let low = 0;
        let high = chars.length;
        let best = ellipsis;
        while (low <= high) {
            const mid = Math.floor((low + high) / 2);
            const candidate = chars.slice(0, mid).join('') + ellipsis;
            if (context.measureText(candidate).width <= maxWidth) {
                best = candidate;
                low = mid + 1;
            } else {
                high = mid - 1;
            }
        }
        return best;
    }

    function fitText(context, text, maxWidth, startSize, minSize, weight) {
        let size = startSize;
        let font = fontFor(size, weight);
        let width = 0;
        do {
            font = fontFor(size, weight);
            context.font = font;
            width = context.measureText(text).width;
            if (width <= maxWidth || size <= minSize) { break; }
            size -= 2;
        } while (size > minSize);
        size = Math.max(size, minSize);
        font = fontFor(size, weight);
        context.font = font;
        const displayText = ellipsizeText(context, text, maxWidth);
        return { size, width: context.measureText(displayText).width, font, text: displayText };
    }

    function geometry(context, config, frame) {
        const style = config.style || {};
        const content = config.content || {};
        const scale = clamp(Number(style.scale) || 100, 75, 140) / 100;
        const safeX = frame.width * 0.05;
        const safeBottom = frame.height * 0.07;
        const maxWidth = frame.width * 0.65;
        const rawPrimary = String(content.primary || '');
        const rawSecondary = String(content.secondary || '');
        const primaryText = fitText(context, rawPrimary, maxWidth * 0.82, frame.height * 0.042 * scale, frame.height * 0.026, '800');
        const secondaryText = rawSecondary ? fitText(context, rawSecondary, maxWidth * 0.82, frame.height * 0.026 * scale, frame.height * 0.018, '600') : { size: 0, width: 0, font: '', text: '' };
        const primary = primaryText.text;
        const secondary = secondaryText.text;
        const primarySize = primaryText.size;
        const secondarySize = secondaryText.size;
        const paddingX = frame.width * 0.018 * scale;
        const paddingY = frame.height * 0.016 * scale;
        const textWidth = Math.max(primaryText.width, secondary ? secondaryText.width : 0);
        let width = Math.min(maxWidth, textWidth + paddingX * 2 + frame.width * 0.018);
        if (style.template === 'solid_band') { width = Math.min(maxWidth, Math.max(width, frame.width * 0.42)); }
        const height = paddingY * 2 + primarySize + (secondary ? secondarySize * 1.25 : 0);
        const y = frame.height - safeBottom - height;
        let x = safeX;
        if (style.position === 'bottom_center') { x = (frame.width - width) / 2; }
        if (style.position === 'bottom_right') { x = frame.width - safeX - width; }
        const travelX = x + width + safeX;
        return { x, y, width, height, paddingX, paddingY, primarySize, secondarySize, primary, secondary, primaryFont: primaryText.font, secondaryFont: secondaryText.font, travelX };
    }

    function drawConfig(context, config, frame, alpha, offsetX) {
        const originalAlpha = context.globalAlpha;
        context.save();
        try {
            const style = config.style || {};
            const g = geometry(context, config, frame);
            context.globalAlpha = originalAlpha * alpha;
            context.translate(offsetX || 0, 0);
            if (style.template !== 'minimal') {
                roundRect(context, g.x, g.y, g.width, g.height, Math.round(frame.height * 0.012));
                context.fillStyle = rgba(style.backgroundColor, Number(style.backgroundOpacity));
                context.fill();
            }
            context.fillStyle = style.accentColor || '#4f46e5';
            if (style.template === 'solid_band') {
                context.fillRect(g.x, g.y, g.width, Math.max(4, frame.height * 0.006), 0);
            } else if (style.template === 'minimal') {
                context.fillRect(g.x, g.y + g.height + 4, Math.min(g.width, frame.width * 0.22), Math.max(3, frame.height * 0.004));
            } else {
                roundRect(context, g.x, g.y, Math.max(6, frame.width * 0.008), g.height, 4);
                context.fill();
            }
            const textX = g.x + g.paddingX + (style.template === 'accent_bar' ? frame.width * 0.012 : 0);
            context.textAlign = 'left';
            context.textBaseline = 'top';
            context.shadowColor = style.template === 'minimal' ? 'rgba(0,0,0,0.75)' : 'transparent';
            context.shadowBlur = style.template === 'minimal' ? 8 : 0;
            context.fillStyle = style.primaryColor || '#ffffff';
            context.font = g.primaryFont;
            context.fillText(g.primary, textX, g.y + g.paddingY);
            if (g.secondary) {
                context.fillStyle = style.secondaryColor || '#dbeafe';
                context.font = g.secondaryFont;
                context.fillText(g.secondary, textX, g.y + g.paddingY + g.primarySize * 1.1);
            }
        } finally {
            context.restore();
        }
    }

    function slideTravel(context, config, frame) {
        return geometry(context, config, frame).travelX;
    }

    function renderLowerThird(context, config, frame, alphaOverride) {
        drawConfig(context, config, frame, alphaOverride == null ? 1 : alphaOverride, 0);
    }

    function drawAnimatedLowerThird(context, item, frame) {
        const duration = durationOf(item);
        const elapsed = frame.now - item.startedAt;
        const progress = duration <= 0 ? 1 : clamp(elapsed / duration, 0, 1);
        let alpha = 1;
        let offsetX = 0;
        const behavior = (item.config && item.config.behavior) || {};
        if (item.phase === 'entering') {
            if (behavior.entrance === 'none') { item.phase = 'visible'; item.visibleAt = frame.now; transition('vh360:studio-overlay:transition-end', 'lowerThird', item); changed('program', 'lowerThird'); }
            if (behavior.entrance === 'fade') { alpha = progress; }
            if (behavior.entrance === 'slide_left') { offsetX = -slideTravel(context, item.config, frame) * (1 - progress); }
            if (behavior.entrance !== 'none' && progress >= 1) { item.phase = 'visible'; item.visibleAt = frame.now; transition('vh360:studio-overlay:transition-end', 'lowerThird', item); changed('program', 'lowerThird'); }
        } else if (item.phase === 'updating') {
            alpha = progress;
            if (item.previousConfig) { drawConfig(context, item.previousConfig, frame, 1 - progress, 0); }
            if (progress >= 1) { item.phase = 'visible'; item.visibleAt = frame.now; item.previousConfig = null; transition('vh360:studio-overlay:transition-end', 'lowerThird', item); changed('program', 'lowerThird'); }
        } else if (item.phase === 'exiting') {
            if (behavior.exit === 'none') { clearProgram('lowerThird', { silentTransition: true }); transition('vh360:studio-overlay:transition-end', 'lowerThird', null); return; }
            if (behavior.exit === 'fade') { alpha = 1 - progress; }
            if (behavior.exit === 'slide_left') { offsetX = -slideTravel(context, item.config, frame) * progress; }
            if (progress >= 1) { clearProgram('lowerThird', { silentTransition: true }); transition('vh360:studio-overlay:transition-end', 'lowerThird', null); return; }
        } else if (item.phase === 'visible' && Number(behavior.autoHideSeconds) > 0 && item.visibleAt && frame.now - item.visibleAt >= Number(behavior.autoHideSeconds) * 1000) {
            hideProgram('lowerThird');
        }
        drawConfig(context, item.config, frame, alpha, offsetX);
    }

    function phaseAlphaAndOffset(context, item, frame, slot) {
        const duration = durationOf(item);
        const elapsed = frame.now - item.startedAt;
        const progress = duration <= 0 ? 1 : clamp(elapsed / duration, 0, 1);
        let alpha = 1;
        let offsetX = 0;
        const behavior = (item.config && item.config.behavior) || {};
        if (item.phase === 'entering') {
            if (behavior.entrance === 'none') { item.phase = 'visible'; item.visibleAt = frame.now; transition('vh360:studio-overlay:transition-end', slot, item); changed('program', slot); }
            if (behavior.entrance === 'fade') { alpha = progress; }
            if (behavior.entrance === 'slide_left') { offsetX = -slideTravel(context, item.config, frame) * (1 - progress); }
            if (behavior.entrance !== 'none' && progress >= 1) { item.phase = 'visible'; item.visibleAt = frame.now; transition('vh360:studio-overlay:transition-end', slot, item); changed('program', slot); }
        } else if (item.phase === 'updating') {
            alpha = progress;
            if (progress >= 1) { item.phase = 'visible'; item.visibleAt = frame.now; item.previousConfig = null; item.previousRuntime = null; transition('vh360:studio-overlay:transition-end', slot, item); changed('program', slot); }
        } else if (item.phase === 'exiting') {
            if (behavior.exit === 'none') { clearProgram(slot, { silentTransition: true }); transition('vh360:studio-overlay:transition-end', slot, null); return null; }
            if (behavior.exit === 'fade') { alpha = 1 - progress; }
            if (behavior.exit === 'slide_left') { offsetX = -slideTravel(context, item.config, frame) * progress; }
            if (progress >= 1) { clearProgram(slot, { silentTransition: true }); transition('vh360:studio-overlay:transition-end', slot, null); return null; }
        }
        return { alpha, offsetX, progress };
    }

    function registerOverlayRenderer(renderer) {
        if (!renderer || !renderer.slot || !renderer.layerId || typeof renderer.drawProgram !== 'function') { return false; }
        renderer.slot = normalizeSlot(renderer.slot);
        renderer.order = Number(renderer.order) || 50;
        renderers.set(renderer.slot, renderer);
        compositor.registerLayer(renderer.layerId, renderer.order, (context, frame) => {
            const item = state.program[renderer.slot];
            if (!item) { return; }
            renderer.drawProgram(context, item, frame);
            const current = state.program[renderer.slot];
            if (current && renderer.needsProgramFrame && renderer.needsProgramFrame(current)) { requestProgramFrame(); }
        });
        requestFrames();
        return true;
    }

    function unregisterOverlayRenderer(slot) {
        slot = normalizeSlot(slot);
        const renderer = renderers.get(slot);
        if (renderer) { compositor.unregisterLayer(renderer.layerId); }
        renderers.delete(slot);
    }

    registerOverlayRenderer({
        slot: 'lowerThird',
        layerId: 'lower-third',
        order: 50,
        drawPreview(context, item, frame) { renderLowerThird(context, item.config, frame, 1); },
        drawProgram(context, item, frame) {
            const motion = phaseAlphaAndOffset(context, item, frame, 'lowerThird');
            if (!motion) { return; }
            if (item.phase === 'updating' && item.previousConfig) { drawConfig(context, item.previousConfig, frame, 1 - motion.progress, 0); }
            if (item.phase === 'visible' && Number(item.config && item.config.behavior && item.config.behavior.autoHideSeconds) > 0 && item.visibleAt && frame.now - item.visibleAt >= Number(item.config.behavior.autoHideSeconds) * 1000) { hideProgram('lowerThird'); }
            drawConfig(context, item.config, frame, motion.alpha, motion.offsetX);
        },
        needsProgramFrame(item) { return item.phase === 'entering' || item.phase === 'updating' || item.phase === 'exiting' || Number(item.config && item.config.behavior && item.config.behavior.autoHideSeconds) > 0; },
    });


    function bibleBox(config, frame) {
        const style = config.style || {};
        const scale = clamp(Number(style.scale) || 100, 75, 140) / 100;
        const safeX = frame.width * 0.05;
        const safeY = frame.height * 0.07;
        const scripture = config.scripture || {};
        let w = frame.width * (style.template === 'scripture_card' ? 0.72 : 0.9);
        let h = frame.height * (style.template === 'lower_band' ? 0.26 : (style.template === 'full_width_panel' ? 0.58 : 0.44));
        if (scripture.attributionRequired && scripture.attribution) {
            const extra = Math.min(0.24, String(scripture.attribution).length / 2200);
            const minHeight = frame.height * (style.template === 'lower_band' ? 0.38 + extra : (style.template === 'scripture_card' ? 0.52 + extra : 0.66 + extra));
            h = Math.min(frame.height * 0.86, Math.max(h, minHeight));
        }
        let x = (frame.width - w) / 2;
        let y = style.position === 'top_center' ? safeY : (style.position === 'center' ? (frame.height - h) / 2 : frame.height - h - safeY);
        return { x, y, w, h, pad: frame.width * 0.025 * scale, scale };
    }

    function bibleWrap(context, text, maxWidth) {
        const words = String(text || '').split(/\s+/).filter(Boolean);
        const lines = [];
        let line = '';
        words.forEach((word) => {
            const test = line ? line + ' ' + word : word;
            if (context.measureText(test).width <= maxWidth || !line) { line = test; }
            else { lines.push(line); line = word; }
        });
        if (line) { lines.push(line); }
        return lines;
    }

    function bibleCacheKey(config, frame) {
        const scripture = config.scripture || {};
        const style = config.style || {};
        return JSON.stringify({ verses: scripture.verses || [], reference: scripture.reference || '', translation: scripture.translationLabel || '', attribution: scripture.attribution || '', attributionRequired: !!scripture.attributionRequired, template: style.template, position: style.position, scale: style.scale, maximumLines: config.pagination && config.pagination.maximumLines, width: frame.width, height: frame.height, showVerseNumbers: style.showVerseNumbers !== false, showReference: style.showReference !== false, showTranslation: style.showTranslation !== false });
    }

    function biblePages(context, config, frame) {
        const cacheKey = bibleCacheKey(config, frame);
        if (bibleLayoutCache.has(cacheKey)) { return clone(bibleLayoutCache.get(cacheKey)); }
        const scripture = config.scripture || {};
        const style = config.style || {};
        const verses = Array.isArray(scripture.verses) ? scripture.verses : [];
        const box = bibleBox(config, frame);
        const maxLines = clamp(Number(config.pagination && config.pagination.maximumLines) || 6, 1, 12);
        const bodySize = Math.max(18, frame.height * 0.035 * box.scale);
        const labelSize = Math.max(12, Math.min(frame.height * 0.022 * box.scale, frame.height * 0.032));
        const lineHeight = bodySize * 1.32;
        const innerWidth = box.w - box.pad * 2;
        context.font = fontFor(bodySize, '700');
        const allLines = [];
        verses.forEach((verse) => {
            const prefix = style.showVerseNumbers !== false ? String(verse.verse || '') + ' ' : '';
            bibleWrap(context, prefix + String(verse.text || ''), innerWidth).forEach((line, index) => allLines.push({ verse: index === 0 ? verse.verse : null, text: line }));
        });
        const reserveLabels = (style.showReference !== false && scripture.reference) || (style.showTranslation !== false && scripture.translationLabel) ? labelSize * 1.8 : 0;
        context.font = fontFor(labelSize * 0.86, '600');
        const attributionLines = scripture.attributionRequired && scripture.attribution ? bibleWrap(context, scripture.attribution, innerWidth) : [];
        const reserveAttribution = attributionLines.length ? attributionLines.length * labelSize * 1.08 + labelSize * 0.35 : 0;
        const availableForScripture = box.h - box.pad * 2 - reserveLabels - reserveAttribution;
        const pageLines = Math.max(1, Math.min(maxLines, Math.floor(availableForScripture / lineHeight)));
        const pages = [];
        for (let i = 0; i < allLines.length; i += pageLines) { pages.push({ lines: allLines.slice(i, i + pageLines), bodySize, labelSize, lineHeight, attributionLines }); }
        const result = pages.length ? pages : [{ lines: [], bodySize, labelSize, lineHeight, attributionLines }];
        bibleLayoutCache.set(cacheKey, clone(result));
        if (bibleLayoutCache.size > 80) { bibleLayoutCache.delete(bibleLayoutCache.keys().next().value); }
        return result;
    }

    function drawBible(context, item, frame, alpha) {
        const config = item.config || {};
        const scripture = config.scripture || {};
        const style = config.style || {};
        const runtime = item.runtime || {};
        const box = bibleBox(config, frame);
        const pages = biblePages(context, config, frame);
        const pageIndex = clamp(Number(runtime.pageIndex) || 0, 0, pages.length - 1);
        item.runtime = Object.assign({}, runtime, { pageIndex, pageCount: pages.length });
        const page = pages[pageIndex];
        context.save();
        try {
            context.globalAlpha *= alpha;
            roundRect(context, box.x, box.y, box.w, box.h, Math.round(frame.height * 0.02));
            context.fillStyle = rgba(style.backgroundColor || '#0f172a', Number(style.backgroundOpacity));
            context.fill();
            context.textAlign = style.textAlign || 'center';
            context.textBaseline = 'top';
            const tx = context.textAlign === 'left' ? box.x + box.pad : (context.textAlign === 'right' ? box.x + box.w - box.pad : box.x + box.w / 2);
            let y = box.y + box.pad;
            context.font = fontFor(page.bodySize, '700');
            context.fillStyle = style.scriptureColor || '#ffffff';
            page.lines.forEach((line) => { context.fillText(line.text, tx, y); y += page.lineHeight; });
            const parts = [];
            if (style.showReference !== false && scripture.reference) { parts.push(scripture.reference); }
            if (style.showTranslation !== false && scripture.translationLabel) { parts.push(scripture.translationLabel); }
            const label = parts.join(' · ');
            let labelY = box.y + box.h - box.pad - page.labelSize;
            if (page.attributionLines && page.attributionLines.length) {
                context.font = fontFor(page.labelSize * 0.86, '600');
                context.fillStyle = style.referenceColor || '#dbeafe';
                labelY -= (page.attributionLines.length - 1) * page.labelSize * 1.08;
                page.attributionLines.forEach((line, index) => { context.fillText(line, tx, labelY + index * page.labelSize * 1.08); });
                labelY -= page.labelSize * 1.35;
            }
            if (label) { context.font = fontFor(page.labelSize, '700'); context.fillStyle = style.referenceColor || '#dbeafe'; context.fillText(ellipsizeText(context, label, box.w - box.pad * 2), tx, labelY); }
        } finally { context.restore(); }
    }

    registerOverlayRenderer({
        slot: 'bible', layerId: 'bible', order: 40,
        drawPreview(context, item, frame) { drawBible(context, item, frame, 1); },
        drawProgram(context, item, frame) {
            const previousConfig = item.previousConfig ? clone(item.previousConfig) : null;
            const previousRuntime = item.previousRuntime ? clone(item.previousRuntime) : null;
            const wasUpdating = item.phase === 'updating';
            const motion = phaseAlphaAndOffset(context, item, frame, 'bible');
            if (!motion) { return; }
            if (wasUpdating && previousConfig) { drawBible(context, { config: previousConfig, runtime: previousRuntime || item.runtime }, frame, 1 - motion.progress); }
            drawBible(context, item, frame, motion.alpha);
        },
        needsProgramFrame(item) { return item.phase === 'entering' || item.phase === 'updating' || item.phase === 'exiting'; },
    });

    function countdownDurationMs(config) {
        const timer = (config && config.timer) || {};
        return Math.max(1000, Math.min(86400000, Number(timer.durationSeconds || 600) * 1000));
    }

    function formatCountdown(ms) {
        const total = Math.max(0, Math.ceil(ms / 1000));
        const hours = Math.floor(total / 3600);
        const minutes = Math.floor((total % 3600) / 60);
        const seconds = total % 60;
        const pad = (value) => String(value).padStart(2, '0');
        return hours > 0 ? pad(hours) + ':' + pad(minutes) + ':' + pad(seconds) : pad(minutes) + ':' + pad(seconds);
    }

    function countdownRemaining(item) {
        const runtime = item.runtime || {};
        if (runtime.status === 'running' && runtime.endAtEpochMs) { return Math.max(0, runtime.endAtEpochMs - Date.now()); }
        if (runtime.status === 'paused') { return Math.max(0, Number(runtime.pausedRemainingMs) || 0); }
        if (runtime.status === 'complete' || runtime.status === 'message') { return 0; }
        return Math.max(0, Number(runtime.remainingMs) || countdownDurationMs(item.config));
    }

    function countdownDisplay(item) {
        const runtime = item.runtime || {};
        const timer = (item.config && item.config.timer) || {};
        if (runtime.status === 'message') { return String((item.config.content && item.config.content.endMessage) || ''); }
        return formatCountdown(countdownRemaining(item));
    }

    function countdownBox(template, style, frame, scale, safeX, safeY, estimatedHeight) {
        let x = safeX;
        let y = safeY;
        let w = frame.width * 0.64;
        let h = estimatedHeight;
        if (template === 'full_screen') {
            return { x: 0, y: 0, w: frame.width, h: frame.height, innerWidth: frame.width * 0.86 };
        }
        if (template === 'lower_center') {
            w = frame.width * 0.78;
            x = (frame.width - w) / 2;
            y = frame.height - safeY - h;
        } else if (template === 'corner') {
            w = frame.width * 0.34;
            const pos = style.position || 'top_right';
            if (pos.indexOf('right') !== -1) { x = frame.width - safeX - w; }
            if (pos.indexOf('bottom') !== -1) { y = frame.height - safeY - h; }
        } else {
            w = frame.width * 0.64;
            x = (frame.width - w) / 2;
            y = (frame.height - h) / 2;
        }
        return { x, y, w, h, innerWidth: Math.max(20, w - frame.width * 0.06 * scale) };
    }

    function drawCountdown(context, item, frame, alpha) {
        const config = item.config || {};
        const style = config.style || {};
        const content = config.content || {};
        const template = style.template || 'center_card';
        const scale = clamp(Number(style.scale) || 100, 75, 140) / 100;
        const safeX = frame.width * 0.05;
        const safeY = frame.height * 0.06;
        const estimatedLabelSize = frame.height * 0.038 * scale;
        const estimatedTimerSize = frame.height * (template === 'corner' ? 0.072 : 0.12) * scale;
        const estimatedHeight = estimatedTimerSize + (String(content.label || '') ? estimatedLabelSize * 1.5 : 0) + frame.height * 0.05 * scale;
        let box = countdownBox(template, style, frame, scale, safeX, safeY, estimatedHeight);
        const labelText = fitText(context, String(content.label || ''), box.innerWidth, estimatedLabelSize, frame.height * 0.022, '700');
        const timerText = fitText(context, countdownDisplay(item), box.innerWidth, estimatedTimerSize, frame.height * 0.04, '900');
        const h = timerText.size + (labelText.text ? labelText.size * 1.5 : 0) + frame.height * 0.05 * scale;
        box = countdownBox(template, style, frame, scale, safeX, safeY, h);
        const x = box.x;
        const y = box.y;
        const w = box.w;
        context.save();
        try {
            context.globalAlpha *= alpha;
            if (template === 'full_screen') { context.fillStyle = rgba(style.backgroundColor, Number(style.backgroundOpacity)); context.fillRect(0, 0, frame.width, frame.height); }
            else { roundRect(context, x, y, w, h, Math.round(frame.height * 0.018)); context.fillStyle = rgba(style.backgroundColor, Number(style.backgroundOpacity)); context.fill(); }
            context.fillStyle = style.accentColor || '#4f46e5';
            context.fillRect(x, y, w, Math.max(4, frame.height * 0.006));
            context.textAlign = 'center';
            context.textBaseline = 'top';
            const centerX = x + w / 2;
            const textGroupHeight = (labelText.text ? labelText.size * 1.45 : 0) + timerText.size;
            let textY = template === 'full_screen' ? (frame.height - textGroupHeight) / 2 : y + h * 0.18;
            if (labelText.text) { context.font = labelText.font; context.fillStyle = style.labelColor || '#dbeafe'; context.fillText(labelText.text, centerX, textY); textY += labelText.size * 1.45; }
            context.font = timerText.font; context.fillStyle = style.timerColor || '#ffffff'; context.fillText(timerText.text, centerX, textY);
        } finally { context.restore(); }
    }

    function updateCountdownRuntime(item) {
        const runtime = item.runtime || {};
        const timer = (item.config && item.config.timer) || {};
        if (item.phase === 'exiting') {
            return;
        }
        if (runtime.status === 'running' && countdownRemaining(item) <= 0) {
            if (timer.endBehavior === 'hide') { runtime.status = 'complete'; runtime.remainingMs = 0; item.runtime = runtime; hideProgram('countdown'); }
            else if (timer.endBehavior === 'show_message') {
                runtime.status = 'message'; runtime.completedAtEpochMs = Date.now(); runtime.messageUntilEpochMs = Number(timer.messageDurationSeconds) > 0 ? Date.now() + Number(timer.messageDurationSeconds) * 1000 : 0; item.runtime = runtime; changed('program', 'countdown');
            } else { runtime.status = 'complete'; runtime.completedAtEpochMs = Date.now(); item.runtime = runtime; changed('program', 'countdown'); }
        }
        if (runtime.status === 'message' && runtime.messageUntilEpochMs && Date.now() >= runtime.messageUntilEpochMs) { runtime.messageUntilEpochMs = 0; runtime.status = 'complete'; item.runtime = runtime; hideProgram('countdown'); }
    }

    registerOverlayRenderer({
        slot: 'countdown', layerId: 'countdown', order: 60,
        drawPreview(context, item, frame) { drawCountdown(context, item, frame, 1); },
        drawProgram(context, item, frame) {
            const wasUpdating = item.phase === 'updating';
            const previousConfig = item.previousConfig ? clone(item.previousConfig) : null;
            const previousRuntime = item.previousRuntime ? clone(item.previousRuntime) : null;
            const motion = phaseAlphaAndOffset(context, item, frame, 'countdown');
            if (!motion) { return; }
            updateCountdownRuntime(item);
            if (wasUpdating && previousConfig) {
                drawCountdown(context, { config: previousConfig, runtime: previousRuntime || item.runtime }, frame, 1 - motion.progress);
            }
            drawCountdown(context, item, frame, motion.alpha);
        },
        needsProgramFrame(item) { const status = item.runtime && item.runtime.status; return item.phase === 'entering' || item.phase === 'updating' || item.phase === 'exiting' || status === 'running' || (status === 'message' && Number(item.runtime.messageUntilEpochMs) > 0); },
    });

    function paginateBible(config, runtime) {
        if (!previewContext) { return { pageIndex: 0, pageCount: 1 }; }
        const frame = { width: outputSize.width || 1920, height: outputSize.height || 1080, now: now(), preview: true };
        const pages = biblePages(previewContext, config || {}, frame);
        const pageIndex = clamp(Number(runtime && runtime.pageIndex) || 0, 0, Math.max(0, pages.length - 1));
        return { pageIndex, pageCount: pages.length };
    }

    function handleProgramResolutionChange(event) {
        outputSize = event.detail || outputSize;
        bibleLayoutCache.clear();
        syncPreviewCanvasSize();
        requestFrames();
    }

    root.addEventListener('vh360:studio:program-resolution-change', handleProgramResolutionChange);
    function handleProgramSourceChange(event) { if (event.detail && event.detail.hasOutput === false) { clearAllProgram({ silentTransition: true }); } }
    root.addEventListener('vh360:studio:program-source-change', handleProgramSourceChange);

    function destroy() {
        destroyed = true;
        if (previewFrame) { window.cancelAnimationFrame(previewFrame); previewFrame = null; }
        root.removeEventListener('vh360:studio:program-resolution-change', handleProgramResolutionChange);
        root.removeEventListener('vh360:studio:program-source-change', handleProgramSourceChange);
        Array.from(renderers.keys()).forEach((slot) => unregisterOverlayRenderer(slot));
        subscribers.clear();
    }

    syncPreviewCanvasSize();
    window.VH360StudioOverlayEngine = { stage, clearPreview, takeToProgram, updateProgram, resetProgram, hideProgram, clearProgram, clearAllProgram, setRuntime, getRuntime, getState, subscribe, destroy, renderLowerThird, formatCountdown, paginateBible };
}());
