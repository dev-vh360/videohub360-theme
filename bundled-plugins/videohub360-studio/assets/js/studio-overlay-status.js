(function () {
    'use strict';

    const root = document.querySelector('[data-vh360-studio-dashboard]');
    const engine = window.VH360StudioOverlayEngine;
    const config = window.vh360StudioDashboard || {};
    const strings = (config.strings && config.strings.overlayStatus) || {};

    if (!root || !engine) {
        return;
    }

    const clearButton = root.querySelector('[data-clear-program-overlays]');
    const tabs = Array.from(root.querySelectorAll('[data-overlays-module-tab]'));
    const listeners = [];
    const moduleForSlot = { lowerThird: 'lower-thirds', bible: 'bible', countdown: 'countdown' };

    function count(scope) {
        return Object.values(scope || {}).filter(Boolean).length;
    }

    function render() {
        const state = engine.getState();
        const programCount = count(state.program);

        if (clearButton) {
            clearButton.hidden = programCount === 0;
            clearButton.disabled = programCount === 0;
        }

        tabs.forEach((tab) => {
            const slot = Object.keys(moduleForSlot).find((key) => moduleForSlot[key] === tab.dataset.module);
            const hasPreview = Boolean(slot && state.preview[slot]);
            const hasProgram = Boolean(slot && state.program[slot]);
            tab.toggleAttribute('data-overlay-has-preview', hasPreview && !hasProgram);
            tab.toggleAttribute('data-overlay-has-program', hasProgram);
            const suffix = hasProgram ? strings.tabProgram : (hasPreview ? strings.tabPreview : '');
            tab.setAttribute('aria-label', tab.textContent.trim() + (suffix ? ' ' + suffix : ''));
        });
    }

    function on(target, event, handler) {
        if (!target) {
            return;
        }
        target.addEventListener(event, handler);
        listeners.push({ target, event, handler });
    }

    on(clearButton, 'click', () => engine.clearAllProgram());
    const unsubscribe = engine.subscribe(render);
    render();

    window.VH360StudioOverlayStatus = {
        destroy() {
            unsubscribe();
            listeners.splice(0).forEach(({ target, event, handler }) => target.removeEventListener(event, handler));
        },
    };
}());
