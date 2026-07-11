(function () {
    'use strict';

    const root = document.querySelector('[data-vh360-studio-dashboard]');
    const engine = window.VH360StudioOverlayEngine;
    const compositor = window.VH360StudioCompositor;
    const config = window.vh360StudioDashboard || {};
    const strings = (config.strings && config.strings.lowerThirds) || {};
    if (!root || !engine || !compositor) { return; }

    function text(key) { return strings[key] || key; }
    function $(selector) { return root.querySelector(selector); }
    function clone(value) { return JSON.parse(JSON.stringify(value)); }
    function clamp(value, min, max) { return Math.min(Math.max(value, min), max); }
    function validHex(value, fallback) { return /^#[0-9a-f]{6}$/i.test(value || '') ? value : fallback; }

    const els = {
        preset: $('[data-lt-preset-select]'), name: $('[data-lt-name]'), primary: $('[data-lt-primary]'), secondary: $('[data-lt-secondary]'),
        template: $('[data-lt-template]'), position: $('[data-lt-position]'), scale: $('[data-lt-scale]'), accentColor: $('[data-lt-accent-color]'),
        backgroundColor: $('[data-lt-background-color]'), backgroundOpacity: $('[data-lt-background-opacity]'), primaryColor: $('[data-lt-primary-color]'), secondaryColor: $('[data-lt-secondary-color]'),
        entrance: $('[data-lt-entrance]'), exit: $('[data-lt-exit]'), duration: $('[data-lt-duration]'), autoHide: $('[data-lt-auto-hide]'),
        stage: $('[data-lt-stage]'), clearPreview: $('[data-lt-clear-preview]'), take: $('[data-lt-take]'), updateProgram: $('[data-lt-update-program]'), hide: $('[data-lt-hide]'),
        save: $('[data-lt-save]'), saveNew: $('[data-lt-save-new]'), duplicate: $('[data-lt-duplicate]'), delete: $('[data-lt-delete]'),
        status: $('[data-lt-status]'), previewStatus: $('[data-lt-preview-status]'), programStatus: $('[data-lt-program-status]'),
    };

    const state = { presets: [], selectedId: 0, staged: false, previewSource: compositor.hasPreviewSource(), programOutput: compositor.hasProgramOutput(), destroyed: false };
    const listeners = [];

    function defaultConfig() {
        return { id: 0, type: 'lower_third', name: '', content: { primary: '', secondary: '' }, style: { template: 'accent_bar', position: 'bottom_left', scale: 100, accentColor: '#4f46e5', backgroundColor: '#0f172a', backgroundOpacity: 90, primaryColor: '#ffffff', secondaryColor: '#dbeafe' }, behavior: { entrance: 'slide_left', exit: 'fade', durationMs: 300, autoHideSeconds: 0 } };
    }

    function currentConfig() {
        const cfg = defaultConfig();
        cfg.id = Number(state.selectedId) || 0;
        cfg.name = (els.name && els.name.value.trim()) || (els.primary && els.primary.value.trim()) || text('untitled');
        cfg.content.primary = ((els.primary && els.primary.value) || '').trim().substring(0, 120);
        cfg.content.secondary = ((els.secondary && els.secondary.value) || '').trim().substring(0, 160);
        cfg.style.template = ['accent_bar', 'solid_band', 'minimal'].includes(els.template.value) ? els.template.value : 'accent_bar';
        cfg.style.position = ['bottom_left', 'bottom_center', 'bottom_right'].includes(els.position.value) ? els.position.value : 'bottom_left';
        cfg.style.scale = clamp(Number(els.scale.value) || 100, 75, 140);
        cfg.style.accentColor = validHex(els.accentColor.value, '#4f46e5');
        cfg.style.backgroundColor = validHex(els.backgroundColor.value, '#0f172a');
        cfg.style.backgroundOpacity = clamp(Number(els.backgroundOpacity.value) || 0, 0, 100);
        cfg.style.primaryColor = validHex(els.primaryColor.value, '#ffffff');
        cfg.style.secondaryColor = validHex(els.secondaryColor.value, '#dbeafe');
        cfg.behavior.entrance = ['slide_left', 'fade', 'none'].includes(els.entrance.value) ? els.entrance.value : 'slide_left';
        cfg.behavior.exit = ['slide_left', 'fade', 'none'].includes(els.exit.value) ? els.exit.value : 'fade';
        cfg.behavior.durationMs = clamp(Number(els.duration.value) || 0, 0, 2000);
        cfg.behavior.autoHideSeconds = clamp(Number(els.autoHide.value) || 0, 0, 300);
        return cfg;
    }

    function applyConfig(cfg) {
        cfg = Object.assign(defaultConfig(), cfg || {});
        cfg.content = Object.assign(defaultConfig().content, cfg.content || {});
        cfg.style = Object.assign(defaultConfig().style, cfg.style || {});
        cfg.behavior = Object.assign(defaultConfig().behavior, cfg.behavior || {});
        state.selectedId = Number(cfg.id) || 0;
        if (els.name) { els.name.value = cfg.name || ''; }
        if (els.primary) { els.primary.value = cfg.content.primary || ''; }
        if (els.secondary) { els.secondary.value = cfg.content.secondary || ''; }
        els.template.value = cfg.style.template; els.position.value = cfg.style.position; els.scale.value = cfg.style.scale;
        els.accentColor.value = cfg.style.accentColor; els.backgroundColor.value = cfg.style.backgroundColor; els.backgroundOpacity.value = cfg.style.backgroundOpacity;
        els.primaryColor.value = cfg.style.primaryColor; els.secondaryColor.value = cfg.style.secondaryColor; els.entrance.value = cfg.behavior.entrance; els.exit.value = cfg.behavior.exit;
        els.duration.value = cfg.behavior.durationMs; els.autoHide.value = cfg.behavior.autoHideSeconds;
        if (els.preset) { els.preset.value = state.selectedId ? String(state.selectedId) : ''; }
        updateStagedPreview(); renderButtons();
    }

    function setStatus(message, type) {
        if (!els.status) { return; }
        els.status.textContent = message || '';
        els.status.dataset.statusType = type || 'info';
    }

    function renderPresetOptions() {
        if (!els.preset) { return; }
        const selected = els.preset.value;
        els.preset.innerHTML = '<option value="">' + text('unsaved') + '</option>';
        state.presets.forEach((preset) => {
            const option = document.createElement('option');
            option.value = String(preset.id); option.textContent = preset.name;
            els.preset.appendChild(option);
        });
        els.preset.value = selected;
    }

    async function api(path, options) {
        const response = await window.fetch((config.restRoot || '') + path, Object.assign({ headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': config.nonce || '' } }, options || {}));
        if (!response.ok) { throw new Error('REST request failed.'); }
        return response.json();
    }

    async function loadPresets() {
        setStatus(text('loading'), 'info');
        try {
            const presets = await api('/overlays?type=lower_third');
            if (state.destroyed) { return; }
            state.presets = presets;
            renderPresetOptions();
            setStatus(text('loaded'), 'success');
        } catch (error) {
            if (!state.destroyed) { setStatus(text('loadFailed'), 'warning'); }
        }
    }

    function updateStagedPreview() { if (state.staged) { engine.stage('lowerThird', currentConfig()); } }
    function hasPrimary() { return Boolean(els.primary && els.primary.value.trim()); }
    function snapshot() { return engine.getState(); }

    function renderButtons() {
        const snap = snapshot();
        const previewLt = Boolean(snap.preview.lowerThird);
        const programLt = Boolean(snap.program.lowerThird);
        if (els.stage) { els.stage.disabled = !state.previewSource || !hasPrimary(); }
        if (els.take) { els.take.disabled = !previewLt || !state.programOutput; }
        if (els.updateProgram) { els.updateProgram.disabled = !previewLt || !programLt; }
        if (els.hide) { els.hide.disabled = !programLt; }
        if (els.clearPreview) { els.clearPreview.disabled = !previewLt; }
        if (els.delete) { els.delete.disabled = !state.selectedId; }
        if (els.previewStatus) { els.previewStatus.textContent = previewLt ? text('previewStaged') : text('previewNotStaged'); }
        if (els.programStatus) { els.programStatus.textContent = programLt ? text('programLive') : text('programNotLive'); }
    }

    function stage() {
        if (!state.previewSource) { setStatus(text('choosePreview'), 'warning'); return; }
        if (!hasPrimary()) { setStatus(text('enterPrimary'), 'warning'); return; }
        state.staged = true; engine.stage('lowerThird', currentConfig()); setStatus(text('staged'), 'success'); renderButtons();
    }
    function clearPreview() { state.staged = false; engine.clearPreview('lowerThird'); renderButtons(); }
    function take() { if (!state.programOutput) { setStatus(text('chooseProgram'), 'warning'); return; } if (engine.takeToProgram('lowerThird')) { setStatus(text('taken'), 'success'); } renderButtons(); }
    function updateProgram() { if (engine.updateProgram('lowerThird')) { setStatus(text('updated'), 'success'); } renderButtons(); }
    function hide() { if (engine.hideProgram('lowerThird')) { setStatus(text('hidden'), 'success'); } renderButtons(); }

    async function save(asNew) {
        const cfg = currentConfig();
        if (!cfg.content.primary) { setStatus(text('enterPrimary'), 'warning'); return; }
        try {
            const item = await api(asNew || !state.selectedId ? '/overlays' : '/overlays/' + state.selectedId, { method: asNew || !state.selectedId ? 'POST' : 'PUT', body: JSON.stringify({ name: cfg.name, type: 'lower_third', config: cfg }) });
            if (state.destroyed) { return; }
            const index = state.presets.findIndex((preset) => Number(preset.id) === Number(item.id));
            if (index >= 0) { state.presets[index] = item; } else { state.presets.unshift(item); }
            state.selectedId = item.id; renderPresetOptions(); if (els.preset) { els.preset.value = String(item.id); } setStatus(text('saved'), 'success');
        } catch (error) { if (!state.destroyed) { setStatus(text('saveFailed'), 'warning'); } }
        renderButtons();
    }

    async function deletePreset() {
        if (!state.selectedId || !window.confirm(text('confirmDelete'))) { return; }
        const nextFocus = els.preset;
        try {
            await api('/overlays/' + state.selectedId, { method: 'DELETE' });
            if (state.destroyed) { return; }
            state.presets = state.presets.filter((preset) => Number(preset.id) !== Number(state.selectedId));
            state.selectedId = 0; renderPresetOptions(); setStatus(text('deleted'), 'success'); if (nextFocus) { nextFocus.focus(); }
        } catch (error) { if (!state.destroyed) { setStatus(text('deleteFailed'), 'warning'); } }
        renderButtons();
    }

    function on(target, eventName, handler) {
        if (!target) { return; }
        target.addEventListener(eventName, handler);
        listeners.push({ target, eventName, handler });
    }

    const formChangeHandler = () => { updateStagedPreview(); renderButtons(); };
    ['input', 'change'].forEach((eventName) => {
        [els.name, els.primary, els.secondary, els.template, els.position, els.scale, els.accentColor, els.backgroundColor, els.backgroundOpacity, els.primaryColor, els.secondaryColor, els.entrance, els.exit, els.duration, els.autoHide].forEach((el) => {
            on(el, eventName, formChangeHandler);
        });
    });
    on(els.preset, 'change', () => {
        if (!els.preset.value) {
            state.selectedId = 0;
            renderButtons();
            return;
        }
        const preset = state.presets.find((item) => String(item.id) === els.preset.value);
        if (preset) { applyConfig(Object.assign(clone(preset.config), { id: preset.id, name: preset.name })); }
    });
    on(els.stage, 'click', stage);
    on(els.clearPreview, 'click', clearPreview);
    on(els.take, 'click', take);
    on(els.updateProgram, 'click', updateProgram);
    on(els.hide, 'click', hide);
    on(els.save, 'click', () => save(false));
    on(els.saveNew, 'click', () => save(true));
    on(els.duplicate, 'click', () => { state.selectedId = 0; if (els.name && !els.name.value) { els.name.value = text('untitled'); } save(true); });
    on(els.delete, 'click', deletePreset);

    on(root, 'vh360:studio:preview-source-change', (event) => { state.previewSource = Boolean(event.detail && event.detail.sourceId); renderButtons(); });
    on(root, 'vh360:studio:program-source-change', (event) => { state.programOutput = Boolean(event.detail && event.detail.hasOutput); renderButtons(); });
    on(root, 'vh360:studio-overlay:preview-change', renderButtons);
    on(root, 'vh360:studio-overlay:program-change', renderButtons);

    function destroy() {
        state.destroyed = true;
        listeners.splice(0).forEach(({ target, eventName, handler }) => target.removeEventListener(eventName, handler));
    }

    window.VH360StudioLowerThirds = { destroy };

    applyConfig(defaultConfig());
    loadPresets();
    renderButtons();
}());
