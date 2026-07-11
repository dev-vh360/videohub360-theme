(function () {
    'use strict';

    const root = document.querySelector('[data-vh360-studio-dashboard]');
    const engine = window.VH360StudioOverlayEngine;
    const compositor = window.VH360StudioCompositor;
    const config = window.vh360StudioDashboard || {};
    const strings = (config.strings && config.strings.countdown) || {};

    if (!root || !engine || !compositor) {
        return;
    }

    const $ = (selector) => root.querySelector(selector);
    const text = (key) => strings[key] || key;
    const listeners = [];
    let refreshTimer = null;
    let lastRuntimeStatus = '';

    const state = {
        presets: [],
        selectedId: 0,
        staged: false,
        previewSource: compositor.hasPreviewSource(),
        programOutput: compositor.hasProgramOutput(),
        destroyed: false,
    };

    const els = {
        preset: $('[data-countdown-preset]'), mode: $('[data-countdown-mode]'), hours: $('[data-countdown-hours]'), minutes: $('[data-countdown-minutes]'), seconds: $('[data-countdown-seconds]'), target: $('[data-countdown-target]'), durationFields: $('[data-countdown-duration-fields]'), targetFields: $('[data-countdown-target-fields]'), label: $('[data-countdown-label]'), message: $('[data-countdown-message]'), previewStatus: $('[data-countdown-preview-status]'), programStatus: $('[data-countdown-program-status]'), remaining: $('[data-countdown-remaining]'), status: $('[data-countdown-status]'), stage: $('[data-countdown-stage]'), clearPreview: $('[data-countdown-clear-preview]'), take: $('[data-countdown-take]'), update: $('[data-countdown-update]'), hide: $('[data-countdown-hide]'), start: $('[data-countdown-start]'), pause: $('[data-countdown-pause]'), resume: $('[data-countdown-resume]'), reset: $('[data-countdown-reset]'), template: $('[data-countdown-template]'), position: $('[data-countdown-position]'), scale: $('[data-countdown-scale]'), accent: $('[data-countdown-accent]'), bg: $('[data-countdown-bg]'), bgOpacity: $('[data-countdown-bg-opacity]'), timerColor: $('[data-countdown-timer-color]'), labelColor: $('[data-countdown-label-color]'), name: $('[data-countdown-name]'), endBehavior: $('[data-countdown-end-behavior]'), messageDuration: $('[data-countdown-message-duration]'), entrance: $('[data-countdown-entrance]'), exit: $('[data-countdown-exit]'), durationMs: $('[data-countdown-duration-ms]'), save: $('[data-countdown-save]'), saveNew: $('[data-countdown-save-new]'), duplicate: $('[data-countdown-duplicate]'), delete: $('[data-countdown-delete]')
    };

    function clone(value) {
        return JSON.parse(JSON.stringify(value));
    }

    function clamp(value, min, max) {
        return Math.min(Math.max(value, min), max);
    }

    function validHex(value, fallback) {
        return /^#[0-9a-f]{6}$/i.test(value || '') ? value : fallback;
    }

    function defaultConfig() {
        return {
            id: 0,
            type: 'countdown',
            name: '',
            content: { label: text('defaultLabel'), endMessage: text('defaultEndMessage') },
            timer: { mode: 'duration', durationSeconds: 600, targetLocalDateTime: '', endBehavior: 'show_message', messageDurationSeconds: 5 },
            style: { template: 'center_card', position: 'top_right', scale: 100, accentColor: '#4f46e5', backgroundColor: '#0f172a', backgroundOpacity: 88, timerColor: '#ffffff', labelColor: '#dbeafe' },
            behavior: { entrance: 'fade', exit: 'fade', durationMs: 300 },
        };
    }

    function rawDurationFields() {
        return {
            hours: Number(els.hours.value),
            minutes: Number(els.minutes.value),
            seconds: Number(els.seconds.value),
        };
    }

    function durationValidation() {
        const raw = rawDurationFields();
        const wholeNumbers = [raw.hours, raw.minutes, raw.seconds].every((value) => Number.isInteger(value));
        const inRange = raw.hours >= 0 && raw.hours <= 23 && raw.minutes >= 0 && raw.minutes <= 59 && raw.seconds >= 0 && raw.seconds <= 59;
        const total = raw.hours * 3600 + raw.minutes * 60 + raw.seconds;
        return { valid: wholeNumbers && inRange && total >= 1 && total <= 86400, total };
    }

    function parseTarget(value) {
        if (!value) {
            return 0;
        }
        const parsed = new Date(value);
        return Number.isNaN(parsed.getTime()) ? 0 : parsed.getTime();
    }

    function validationResult() {
        const currentMode = els.mode.value === 'target_time' ? 'target_time' : 'duration';
        if (currentMode === 'duration') {
            const duration = durationValidation();
            return duration.valid ? { valid: true } : { valid: false, message: text('invalidDuration') };
        }
        const target = parseTarget(els.target.value);
        return target && target > Date.now() ? { valid: true } : { valid: false, message: text('futureTarget') };
    }

    function requireValid() {
        const result = validationResult();
        if (!result.valid) {
            setStatus(result.message, 'warning');
        }
        return result.valid;
    }

    function currentConfig() {
        const defaults = defaultConfig();
        const duration = durationValidation();
        const countdown = defaults;
        countdown.id = state.selectedId;
        countdown.name = (els.name.value || els.label.value || text('untitled')).trim();
        countdown.content.label = (els.label.value || '').trim().slice(0, 120);
        countdown.content.endMessage = (els.message.value || '').trim().slice(0, 160);
        countdown.timer.mode = els.mode.value === 'target_time' ? 'target_time' : 'duration';
        countdown.timer.durationSeconds = duration.valid ? duration.total : Math.max(0, duration.total);
        countdown.timer.targetLocalDateTime = els.target.value || '';
        countdown.timer.endBehavior = ['hold_zero', 'show_message', 'hide'].includes(els.endBehavior.value) ? els.endBehavior.value : 'show_message';
        countdown.timer.messageDurationSeconds = clamp(Number(els.messageDuration.value) || 0, 0, 300);
        countdown.style.template = ['full_screen', 'center_card', 'lower_center', 'corner'].includes(els.template.value) ? els.template.value : 'center_card';
        countdown.style.position = ['top_left', 'top_right', 'bottom_left', 'bottom_right'].includes(els.position.value) ? els.position.value : 'top_right';
        countdown.style.scale = clamp(Number(els.scale.value) || 100, 75, 140);
        countdown.style.accentColor = validHex(els.accent.value, '#4f46e5');
        countdown.style.backgroundColor = validHex(els.bg.value, '#0f172a');
        countdown.style.backgroundOpacity = clamp(Number(els.bgOpacity.value) || 88, 0, 100);
        countdown.style.timerColor = validHex(els.timerColor.value, '#ffffff');
        countdown.style.labelColor = validHex(els.labelColor.value, '#dbeafe');
        countdown.behavior.entrance = els.entrance.value === 'none' ? 'none' : 'fade';
        countdown.behavior.exit = els.exit.value === 'none' ? 'none' : 'fade';
        countdown.behavior.durationMs = clamp(Number(els.durationMs.value) || 0, 0, 2000);
        return countdown;
    }

    function runtimeReady(countdown) {
        const runtime = { status: 'ready', remainingMs: countdown.timer.durationSeconds * 1000, endAtEpochMs: 0, targetEpochMs: 0, pausedRemainingMs: 0, completedAtEpochMs: 0, messageUntilEpochMs: 0 };
        if (countdown.timer.mode === 'target_time') {
            runtime.targetEpochMs = parseTarget(countdown.timer.targetLocalDateTime);
            runtime.remainingMs = Math.max(0, runtime.targetEpochMs - Date.now());
        }
        return runtime;
    }

    function remaining(runtime) {
        if (!runtime) { return 0; }
        if (runtime.status === 'running' && runtime.endAtEpochMs) { return Math.max(0, runtime.endAtEpochMs - Date.now()); }
        if (runtime.status === 'paused') { return Math.max(0, runtime.pausedRemainingMs || 0); }
        return Math.max(0, runtime.remainingMs || 0);
    }

    function setStatus(message, type) {
        els.status.textContent = message || '';
        els.status.dataset.statusType = type || 'info';
    }

    function runtimeLabel(runtime) {
        if (!runtime || !runtime.status) { return text('ready'); }
        return text('runtime' + runtime.status.charAt(0).toUpperCase() + runtime.status.slice(1));
    }

    function applyConfig(countdown) {
        const defaults = defaultConfig();
        countdown = Object.assign(defaults, countdown || {});
        countdown.content = Object.assign(defaults.content, countdown.content || {});
        countdown.timer = Object.assign(defaults.timer, countdown.timer || {});
        countdown.style = Object.assign(defaults.style, countdown.style || {});
        countdown.behavior = Object.assign(defaults.behavior, countdown.behavior || {});
        state.selectedId = Number(countdown.id) || 0;
        els.name.value = countdown.name || '';
        els.label.value = countdown.content.label || '';
        els.message.value = countdown.content.endMessage || '';
        els.mode.value = countdown.timer.mode;
        els.target.value = countdown.timer.targetLocalDateTime || '';
        const duration = Number(countdown.timer.durationSeconds) || 600;
        els.hours.value = Math.floor(duration / 3600);
        els.minutes.value = Math.floor((duration % 3600) / 60);
        els.seconds.value = duration % 60;
        els.endBehavior.value = countdown.timer.endBehavior;
        els.messageDuration.value = countdown.timer.messageDurationSeconds;
        els.template.value = countdown.style.template;
        els.position.value = countdown.style.position;
        els.scale.value = countdown.style.scale;
        els.accent.value = countdown.style.accentColor;
        els.bg.value = countdown.style.backgroundColor;
        els.bgOpacity.value = countdown.style.backgroundOpacity;
        els.timerColor.value = countdown.style.timerColor;
        els.labelColor.value = countdown.style.labelColor;
        els.entrance.value = countdown.behavior.entrance;
        els.exit.value = countdown.behavior.exit;
        els.durationMs.value = countdown.behavior.durationMs;
        if (els.preset) { els.preset.value = state.selectedId ? String(state.selectedId) : ''; }
        modeFields();
        updatePreview();
        render();
    }

    function modeFields() {
        const target = els.mode.value === 'target_time';
        els.durationFields.hidden = target;
        els.targetFields.hidden = !target;
    }

    function updatePreview() {
        if (state.staged && validationResult().valid) {
            const countdown = currentConfig();
            engine.stage('countdown', countdown, { runtime: runtimeReady(countdown) });
        }
    }

    function snapshot() {
        return engine.getState();
    }

    function hasLowerThirdProgram() {
        return Boolean(snapshot().program.lowerThird);
    }

    function conflictWarning() {
        const template = els.template.value;
        if (template === 'full_screen') { return text('fullWarning'); }
        if (template === 'lower_center' && hasLowerThirdProgram()) { return text('lowerWarning'); }
        return '';
    }

    function timerDefinitionChanged(staged, live) {
        const stagedTimer = (staged && staged.timer) || {};
        const liveTimer = (live && live.timer) || {};
        if (stagedTimer.mode !== liveTimer.mode) {
            return true;
        }
        if (stagedTimer.mode === 'target_time') {
            return String(stagedTimer.targetLocalDateTime || '') !== String(liveTimer.targetLocalDateTime || '');
        }
        return Number(stagedTimer.durationSeconds) !== Number(liveTimer.durationSeconds);
    }

    function configForProgramUpdate(staged, live) {
        const merged = clone(staged);
        const liveTimer = (live && live.timer) || {};
        merged.timer = Object.assign({}, merged.timer || {}, {
            mode: liveTimer.mode || merged.timer.mode,
            durationSeconds: liveTimer.durationSeconds || merged.timer.durationSeconds,
            targetLocalDateTime: liveTimer.targetLocalDateTime || '',
        });
        return merged;
    }

    function announceAction(message) {
        const warning = conflictWarning();
        setStatus(warning || message, warning ? 'warning' : 'success');
    }

    function programUpdateStatus(changedTimer) {
        const messages = [];
        const conflict = conflictWarning();
        if (conflict) {
            messages.push(conflict);
        }
        if (changedTimer) {
            messages.push(text('timerResetNote'));
        }
        if (messages.length) {
            setStatus(messages.join(' '), 'warning');
            return;
        }
        setStatus(text('updated'), 'success');
    }

    function render() {
        const stateSnapshot = snapshot();
        const preview = stateSnapshot.preview.countdown;
        const program = stateSnapshot.program.countdown;
        const runtime = program && program.runtime;
        const valid = validationResult().valid;
        els.previewStatus.textContent = preview ? text('stagedShort') : text('notStaged');
        els.programStatus.textContent = program ? runtimeLabel(runtime) : text('notLive');
        els.remaining.textContent = engine.formatCountdown ? engine.formatCountdown(remaining(runtime || (preview && preview.runtime))) : '';
        els.stage.disabled = !state.previewSource || !valid;
        els.clearPreview.disabled = !preview;
        els.take.disabled = !preview || !state.programOutput;
        els.update.disabled = !preview || !program;
        els.hide.disabled = !program;
        els.start.disabled = !program || !runtime || !(runtime.status === 'ready' || runtime.status === 'complete');
        els.pause.disabled = !program || !runtime || runtime.status !== 'running';
        els.resume.disabled = !program || !runtime || runtime.status !== 'paused';
        els.reset.disabled = !program;
        els.delete.disabled = !state.selectedId;
        if (runtime && runtime.status !== lastRuntimeStatus) {
            if (runtime.status === 'complete') { setStatus(text('complete'), 'success'); }
            if (runtime.status === 'message') { setStatus(text('message'), 'success'); }
            lastRuntimeStatus = runtime.status;
        }
        scheduleRefresh(runtime);
    }

    function scheduleRefresh(runtime) {
        if (refreshTimer) { window.clearTimeout(refreshTimer); }
        refreshTimer = null;
        if (runtime && (runtime.status === 'running' || (runtime.status === 'message' && runtime.messageUntilEpochMs))) {
            refreshTimer = window.setTimeout(render, 500);
        }
    }

    async function api(path, options) {
        const response = await fetch((config.restRoot || '') + path, Object.assign({ headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': config.nonce || '' } }, options || {}));
        if (!response.ok) { throw new Error('rest'); }
        return response.json();
    }

    function renderPresets() {
        const value = els.preset.value;
        els.preset.innerHTML = '<option value="">' + text('unsaved') + '</option>';
        state.presets.forEach((preset) => {
            const option = document.createElement('option');
            option.value = String(preset.id);
            option.textContent = preset.name;
            els.preset.appendChild(option);
        });
        els.preset.value = value;
    }

    async function load() {
        setStatus(text('loading'), 'info');
        try {
            const items = await api('/overlays?type=countdown');
            if (state.destroyed) { return; }
            state.presets = items;
            renderPresets();
            setStatus(text('loaded'), 'success');
        } catch (error) {
            if (!state.destroyed) { setStatus(text('loadFailed'), 'warning'); }
        }
    }

    function stage() {
        if (!state.previewSource) { setStatus(text('choosePreview'), 'warning'); return; }
        if (!requireValid()) { return; }
        const countdown = currentConfig();
        state.staged = true;
        engine.stage('countdown', countdown, { runtime: runtimeReady(countdown) });
        announceAction(text('staged'));
        render();
    }

    function take() {
        if (!state.programOutput) { setStatus(text('chooseProgram'), 'warning'); return; }
        if (!requireValid()) { return; }
        const countdown = currentConfig();
        engine.takeToProgram('countdown', { runtime: runtimeReady(countdown) });
        announceAction(text('taken'));
        render();
    }

    function update() {
        if (!requireValid()) { return; }
        const program = snapshot().program.countdown;
        const staged = currentConfig();
        const changedTimer = timerDefinitionChanged(staged, program && program.config);
        engine.updateProgram('countdown', { preserveRuntime: true, config: program ? configForProgramUpdate(staged, program.config) : staged });
        programUpdateStatus(changedTimer);
        render();
    }

    function start() {
        const item = snapshot().program.countdown;
        if (!item) { return; }
        const countdown = item.config;
        const runtime = item.runtime || runtimeReady(countdown);
        if (countdown.timer.mode === 'target_time') {
            const target = parseTarget(countdown.timer.targetLocalDateTime);
            if (!target || target <= Date.now()) { setStatus(text('futureTarget'), 'warning'); return; }
            runtime.targetEpochMs = target;
            runtime.endAtEpochMs = target;
        } else {
            runtime.endAtEpochMs = Date.now() + Math.max(1, runtime.remainingMs || countdown.timer.durationSeconds * 1000);
        }
        runtime.status = 'running';
        engine.setRuntime('program', 'countdown', runtime);
        setStatus(text('started'), 'success');
        render();
    }

    function pause() {
        const runtime = engine.getRuntime('program', 'countdown');
        runtime.pausedRemainingMs = remaining(runtime);
        runtime.remainingMs = runtime.pausedRemainingMs;
        runtime.status = 'paused';
        engine.setRuntime('program', 'countdown', runtime);
        setStatus(text('paused'), 'success');
        render();
    }

    function resume() {
        const item = snapshot().program.countdown;
        const runtime = item.runtime;
        if (item.config.timer.mode === 'duration') { runtime.endAtEpochMs = Date.now() + (runtime.pausedRemainingMs || runtime.remainingMs); }
        runtime.status = 'running';
        engine.setRuntime('program', 'countdown', runtime);
        setStatus(text('resumed'), 'success');
        render();
    }

    function reset() {
        if (!requireValid()) { return; }
        const countdown = currentConfig();
        const runtime = runtimeReady(countdown);
        if (!engine.resetProgram('countdown', countdown, { runtime })) {
            engine.setRuntime('program', 'countdown', runtime);
        }
        setStatus(text('reset'), 'success');
        render();
    }

    function clearPreview() {
        state.staged = false;
        engine.clearPreview('countdown');
        render();
    }

    function hide() {
        engine.hideProgram('countdown');
        setStatus(text('hidden'), 'success');
        render();
    }

    async function save(asNew) {
        if (!requireValid()) { return; }
        const countdown = currentConfig();
        try {
            const item = await api(asNew || !state.selectedId ? '/overlays' : '/overlays/' + state.selectedId, { method: asNew || !state.selectedId ? 'POST' : 'PUT', body: JSON.stringify({ type: 'countdown', name: countdown.name, config: countdown }) });
            if (state.destroyed) { return; }
            const index = state.presets.findIndex((preset) => Number(preset.id) === Number(item.id));
            if (index >= 0) { state.presets[index] = item; } else { state.presets.unshift(item); }
            state.selectedId = item.id;
            renderPresets();
            els.preset.value = String(item.id);
            setStatus(text('saved'), 'success');
        } catch (error) {
            if (!state.destroyed) { setStatus(text('saveFailed'), 'warning'); }
        }
        render();
    }

    async function del() {
        if (!state.selectedId || !confirm(text('confirmDelete'))) { return; }
        const focus = els.preset;
        try {
            await api('/overlays/' + state.selectedId, { method: 'DELETE' });
            if (state.destroyed) { return; }
            state.presets = state.presets.filter((preset) => Number(preset.id) !== Number(state.selectedId));
            state.selectedId = 0;
            renderPresets();
            setStatus(text('deleted'), 'success');
            focus.focus();
        } catch (error) {
            if (!state.destroyed) { setStatus(text('deleteFailed'), 'warning'); }
        }
        render();
    }

    function on(target, event, handler) {
        if (!target) { return; }
        target.addEventListener(event, handler);
        listeners.push({ target, event, handler });
    }

    [els.mode, els.hours, els.minutes, els.seconds, els.target, els.label, els.message, els.template, els.position, els.scale, els.accent, els.bg, els.bgOpacity, els.timerColor, els.labelColor, els.name, els.endBehavior, els.messageDuration, els.entrance, els.exit, els.durationMs].forEach((element) => {
        on(element, 'input', () => { modeFields(); updatePreview(); render(); });
        on(element, 'change', () => { modeFields(); updatePreview(); render(); });
    });

    on(els.preset, 'change', () => {
        if (!els.preset.value) { state.selectedId = 0; render(); return; }
        const preset = state.presets.find((item) => String(item.id) === els.preset.value);
        if (preset) { applyConfig(Object.assign(clone(preset.config), { id: preset.id, name: preset.name })); }
    });

    on(els.stage, 'click', stage);
    on(els.clearPreview, 'click', clearPreview);
    on(els.take, 'click', take);
    on(els.update, 'click', update);
    on(els.hide, 'click', hide);
    on(els.start, 'click', start);
    on(els.pause, 'click', pause);
    on(els.resume, 'click', resume);
    on(els.reset, 'click', reset);
    on(els.save, 'click', () => save(false));
    on(els.saveNew, 'click', () => save(true));
    on(els.duplicate, 'click', () => { state.selectedId = 0; save(true); });
    on(els.delete, 'click', del);
    on(root, 'vh360:studio:preview-source-change', (event) => { state.previewSource = Boolean(event.detail && event.detail.sourceId); render(); });
    on(root, 'vh360:studio:program-source-change', (event) => { state.programOutput = Boolean(event.detail && event.detail.hasOutput); render(); });
    on(root, 'vh360:studio-overlay:program-change', render);
    on(root, 'vh360:studio-overlay:preview-change', render);

    function destroy() {
        state.destroyed = true;
        if (refreshTimer) { window.clearTimeout(refreshTimer); }
        listeners.splice(0).forEach(({ target, event, handler }) => target.removeEventListener(event, handler));
    }

    window.VH360StudioCountdown = { destroy };
    applyConfig(defaultConfig());
    load();
    render();
}());
