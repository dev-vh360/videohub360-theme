(function () {
    'use strict';
    const root = document.querySelector('[data-vh360-studio-dashboard]');
    const engine = window.VH360StudioOverlayEngine;
    if (!root || !engine) { return; }
    const previewBadge = root.querySelector('[data-preview-overlay-status]');
    const programBadge = root.querySelector('[data-program-overlay-status]');
    const clearButton = root.querySelector('[data-clear-program-overlays]');
    const tabs = Array.from(root.querySelectorAll('[data-overlays-module-tab]'));
    const listeners = [];
    const moduleForSlot = { lowerThird: 'lower-thirds', bible: 'bible', countdown: 'countdown' };
    function count(scope) { return Object.values(scope || {}).filter(Boolean).length; }
    function label(num, suffix) { return num === 1 ? '1 overlay ' + suffix : num + ' overlays ' + suffix; }
    function render() {
        const state = engine.getState();
        const previewCount = count(state.preview);
        const programCount = count(state.program);
        if (previewBadge) { previewBadge.hidden = previewCount === 0; previewBadge.textContent = label(previewCount, 'staged'); }
        if (programBadge) { programBadge.hidden = programCount === 0; programBadge.textContent = label(programCount, 'live'); }
        if (clearButton) { clearButton.hidden = programCount === 0; clearButton.disabled = programCount === 0; }
        tabs.forEach((tab) => {
            const slot = Object.keys(moduleForSlot).find((key) => moduleForSlot[key] === tab.dataset.module);
            const hasPreview = Boolean(slot && state.preview[slot]);
            const hasProgram = Boolean(slot && state.program[slot]);
            tab.toggleAttribute('data-overlay-has-preview', hasPreview && !hasProgram);
            tab.toggleAttribute('data-overlay-has-program', hasProgram);
            tab.setAttribute('aria-label', tab.textContent.trim() + (hasProgram ? ' live overlay active' : (hasPreview ? ' overlay staged' : '')));
        });
    }
    function on(target, event, handler) { if (!target) { return; } target.addEventListener(event, handler); listeners.push({ target, event, handler }); }
    on(clearButton, 'click', () => engine.clearAllProgram());
    const unsubscribe = engine.subscribe(render);
    render();
    window.VH360StudioOverlayStatus = { destroy() { unsubscribe(); listeners.splice(0).forEach(({ target, event, handler }) => target.removeEventListener(event, handler)); } };
}());
