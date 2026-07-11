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

    function makeItem(config, phase) {
        const timestamp = now();
        return { config: clone(config), phase, startedAt: timestamp, visibleAt: 0, previousConfig: null };
    }

    function eventDetail(slot, item) {
        return { slot, active: Boolean(item), phase: item ? item.phase : 'hidden', config: item ? clone(item.config) : null };
    }

    function dispatch(name, detail) { root.dispatchEvent(new CustomEvent(name, { bubbles: true, detail })); }
    function notify() { subscribers.forEach((callback) => callback(getState())); }
    function changed(scope, slot) {
        dispatch(scope === 'preview' ? 'vh360:studio-overlay:preview-change' : 'vh360:studio-overlay:program-change', eventDetail(slot, state[scope][slot]));
        notify();
        requestFrames();
    }
    function transition(name, slot, item) { dispatch(name, eventDetail(slot, item)); }
    function requestFrames() {
        if (destroyed) { return; }
        if (compositor.requestFrame) { compositor.requestFrame(); }
        schedulePreviewFrame();
    }

    function stage(slot, config) {
        slot = normalizeSlot(slot);
        if (!isSlot(slot)) { return false; }
        state.preview[slot] = makeItem(config, 'visible');
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

    function takeToProgram(slot) {
        slot = normalizeSlot(slot);
        if (!isSlot(slot) || !state.preview[slot]) { return false; }
        state.program[slot] = makeItem(state.preview[slot].config, 'entering');
        transition('vh360:studio-overlay:transition-start', slot, state.program[slot]);
        changed('program', slot);
        return true;
    }

    function updateProgram(slot) {
        slot = normalizeSlot(slot);
        if (!isSlot(slot) || !state.preview[slot] || !state.program[slot]) { return false; }
        const current = state.program[slot];
        const next = makeItem(state.preview[slot].config, 'updating');
        next.previousConfig = clone(current.config);
        state.program[slot] = next;
        transition('vh360:studio-overlay:transition-start', slot, next);
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
        const item = state.preview.lowerThird;
        if (item) { renderLowerThird(previewContext, item.config, { width: previewCanvas.width, height: previewCanvas.height, now: now(), preview: true }, 1); }
        if (item) { schedulePreviewFrame(); }
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

    function fitText(context, text, maxWidth, startSize, minSize, weight) {
        let size = startSize;
        let font = fontFor(size, weight);
        let width = 0;
        do {
            font = fontFor(size, weight);
            context.font = font;
            width = context.measureText(text).width;
            if (width <= maxWidth || size <= minSize) { return { size, width, font }; }
            size -= 2;
        } while (size > minSize);
        font = fontFor(minSize, weight);
        context.font = font;
        return { size: minSize, width: context.measureText(text).width, font };
    }

    function geometry(context, config, frame) {
        const style = config.style || {};
        const content = config.content || {};
        const scale = clamp(Number(style.scale) || 100, 75, 140) / 100;
        const safeX = frame.width * 0.05;
        const safeBottom = frame.height * 0.07;
        const maxWidth = frame.width * 0.65;
        const primary = String(content.primary || '');
        const secondary = String(content.secondary || '');
        const primaryText = fitText(context, primary, maxWidth * 0.82, frame.height * 0.042 * scale, frame.height * 0.026, '800');
        const secondaryText = secondary ? fitText(context, secondary, maxWidth * 0.82, frame.height * 0.026 * scale, frame.height * 0.018, '600') : { size: 0, width: 0, font: '' };
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

    compositor.registerLayer('lower-third', 50, (context, frame) => {
        const item = state.program.lowerThird;
        if (!item) { return; }
        drawAnimatedLowerThird(context, item, frame);
        if (state.program.lowerThird) { requestFrames(); }
    });

    function handleProgramResolutionChange(event) {
        outputSize = event.detail || outputSize;
        syncPreviewCanvasSize();
        requestFrames();
    }

    root.addEventListener('vh360:studio:program-resolution-change', handleProgramResolutionChange);

    function destroy() {
        destroyed = true;
        if (previewFrame) { window.cancelAnimationFrame(previewFrame); previewFrame = null; }
        root.removeEventListener('vh360:studio:program-resolution-change', handleProgramResolutionChange);
        compositor.unregisterLayer('lower-third');
        subscribers.clear();
    }

    syncPreviewCanvasSize();
    window.VH360StudioOverlayEngine = { stage, clearPreview, takeToProgram, updateProgram, hideProgram, clearProgram, clearAllProgram, getState, subscribe, destroy, renderLowerThird };
}());
