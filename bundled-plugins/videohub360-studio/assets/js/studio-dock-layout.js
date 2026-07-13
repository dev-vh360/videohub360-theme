(function () {
    'use strict';

    const CONFIG = {
        storageKey: 'vh360_studio_lower_dock_layout_v1',
        desktopBreakpoint: 1025,
        resizerWidth: 16,
        keyboardStep: 20,
        keyboardLargeStep: 50,
        order: ['scenes', 'sources', 'audio', 'stream'],
        minimumWidths: { scenes: 160, sources: 200, audio: 220, stream: 240 },
        defaultBaseWidths: { scenes: 220, sources: 260, audio: 260, stream: 280 },
    };

    const storage = {
        get(key) { try { return window.localStorage.getItem(key); } catch (error) { return null; } },
        set(key, value) { try { window.localStorage.setItem(key, value); } catch (error) {} },
        remove(key) { try { window.localStorage.removeItem(key); } catch (error) {} },
    };

    function finite(value) { return Number.isFinite(value) && value >= 0; }
    function sumWidths(widths) { return CONFIG.order.reduce((total, key) => total + (finite(widths[key]) ? widths[key] : 0), 0); }

    function normalizeWidths(desiredWidths, availableWidth) {
        if (!finite(availableWidth)) { return null; }
        const minimumTotal = sumWidths(CONFIG.minimumWidths);
        if (availableWidth < minimumTotal) { return null; }
        const widths = {};
        CONFIG.order.forEach((key) => { widths[key] = Math.max(CONFIG.minimumWidths[key], finite(desiredWidths[key]) ? desiredWidths[key] : CONFIG.minimumWidths[key]); });
        let total = sumWidths(widths);
        if (total > availableWidth) {
            let overflow = total - availableWidth;
            let guard = 0;
            while (overflow > 0.01 && guard < 12) {
                guard += 1;
                const shrinkable = CONFIG.order.filter((key) => widths[key] > CONFIG.minimumWidths[key]);
                if (!shrinkable.length) { break; }
                const shrinkTotal = shrinkable.reduce((totalShrink, key) => totalShrink + widths[key] - CONFIG.minimumWidths[key], 0);
                shrinkable.forEach((key) => {
                    const room = widths[key] - CONFIG.minimumWidths[key];
                    const amount = Math.min(room, overflow * (room / shrinkTotal));
                    widths[key] -= amount;
                });
                overflow = sumWidths(widths) - availableWidth;
            }
        } else if (total < availableWidth) {
            const remaining = availableWidth - total;
            const flexible = CONFIG.order.filter((key) => widths[key] > CONFIG.minimumWidths[key]);
            const base = flexible.length ? flexible : CONFIG.order;
            const flexibleTotal = base.reduce((acc, key) => acc + widths[key], 0) || base.length;
            base.forEach((key) => { widths[key] += remaining * (widths[key] / flexibleTotal); });
        }
        const rounded = {};
        let used = 0;
        CONFIG.order.forEach((key, index) => {
            if (index === CONFIG.order.length - 1) { rounded[key] = Math.max(CONFIG.minimumWidths[key], Math.round(availableWidth - used)); }
            else { rounded[key] = Math.max(CONFIG.minimumWidths[key], Math.round(widths[key])); used += rounded[key]; }
        });
        return rounded;
    }

    function Controller(grid) {
        this.grid = grid;
        this.config = window.vh360StudioDashboard || {};
        this.strings = (this.config.strings && this.config.strings.lowerDockLayout) || {};
        this.panels = {};
        this.resizers = Array.from(grid.querySelectorAll('[data-studio-dock-resizer]'));
        CONFIG.order.forEach((key) => { this.panels[key] = grid.querySelector(`[data-studio-dock-panel="${key}"]`); });
        this.widths = null;
        this.savedRatios = this.readSavedRatios();
        this.drag = null;
        this.frame = null;
        this.boundPointerMove = this.onPointerMove.bind(this);
        this.boundPointerEnd = this.endDrag.bind(this);
        this.boundSchedule = this.scheduleLayout.bind(this);
    }

    Controller.prototype.init = function () {
        this.resizers.forEach((resizer) => {
            resizer.addEventListener('pointerdown', (event) => this.onPointerDown(event, resizer));
            resizer.addEventListener('keydown', (event) => this.onKeydown(event, resizer));
            resizer.addEventListener('dblclick', () => this.resetLayout());
            resizer.addEventListener('lostpointercapture', this.boundPointerEnd);
        });
        if ('ResizeObserver' in window) { this.observer = new ResizeObserver(this.boundSchedule); this.observer.observe(this.grid); }
        window.addEventListener('resize', this.boundSchedule);
        window.addEventListener('orientationchange', this.boundSchedule);
        window.addEventListener('blur', this.boundPointerEnd);
        this.scheduleLayout();
    };

    Controller.prototype.availableWidth = function () { return Math.floor(this.grid.getBoundingClientRect().width) - (this.resizers.length * CONFIG.resizerWidth); };
    Controller.prototype.isDesktop = function () { return window.matchMedia(`(min-width: ${CONFIG.desktopBreakpoint}px)`).matches; };

    Controller.prototype.readSavedRatios = function () {
        try {
            const parsed = JSON.parse(storage.get(CONFIG.storageKey));
            if (!parsed || parsed.version !== 1 || !parsed.ratios) { return null; }
            const ratios = {}; let total = 0;
            CONFIG.order.forEach((key) => { ratios[key] = Number(parsed.ratios[key]); total += ratios[key]; });
            if (!CONFIG.order.every((key) => Number.isFinite(ratios[key]) && ratios[key] > 0) || total <= 0) { return null; }
            CONFIG.order.forEach((key) => { ratios[key] /= total; });
            return ratios;
        } catch (error) { return null; }
    };

    Controller.prototype.defaultWidths = function (available) {
        const widths = Object.assign({}, CONFIG.defaultBaseWidths);
        const extra = Math.max(0, available - sumWidths(widths));
        widths.audio += extra / 2;
        widths.stream += extra / 2;
        return widths;
    };

    Controller.prototype.desiredWidths = function (available) {
        if (this.widths) { return this.widths; }
        if (this.savedRatios) {
            return CONFIG.order.reduce((out, key) => { out[key] = available * this.savedRatios[key]; return out; }, {});
        }
        return this.defaultWidths(available);
    };

    Controller.prototype.scheduleLayout = function () {
        if (this.drag || this.frame) { return; }
        this.frame = window.requestAnimationFrame(() => { this.frame = null; this.applyLayout(); });
    };

    Controller.prototype.applyLayout = function (desired) {
        const available = this.availableWidth();
        const widths = this.isDesktop() ? normalizeWidths(desired || this.desiredWidths(available), available) : null;
        this.grid.classList.toggle('is-lower-docks-stacked', !widths);
        if (!widths) { CONFIG.order.forEach((key) => this.grid.style.removeProperty(`--vh360-studio-dock-${key}-width`)); return; }
        this.widths = widths;
        CONFIG.order.forEach((key) => this.grid.style.setProperty(`--vh360-studio-dock-${key}-width`, `${widths[key]}px`));
        this.updateAria();
    };

    Controller.prototype.persist = function () {
        if (!this.widths) { return; }
        const total = sumWidths(this.widths);
        const ratios = {};
        CONFIG.order.forEach((key) => { ratios[key] = this.widths[key] / total; });
        storage.set(CONFIG.storageKey, JSON.stringify({ version: 1, ratios }));
        this.savedRatios = ratios;
    };

    Controller.prototype.adjustPair = function (resizer, delta, persist) {
        if (!this.widths) { this.applyLayout(); }
        if (!this.widths) { return; }
        const left = resizer.dataset.leftDock;
        const right = resizer.dataset.rightDock;
        const pairTotal = this.widths[left] + this.widths[right];
        const leftWidth = Math.min(pairTotal - CONFIG.minimumWidths[right], Math.max(CONFIG.minimumWidths[left], this.widths[left] + delta));
        const next = Object.assign({}, this.widths);
        next[left] = leftWidth;
        next[right] = pairTotal - leftWidth;
        this.applyLayout(next);
        if (persist) { this.persist(); }
    };

    Controller.prototype.onPointerDown = function (event, resizer) {
        if (this.grid.classList.contains('is-lower-docks-stacked')) { return; }
        event.preventDefault();
        if (!this.widths) {
            this.applyLayout();
        }
        if (!this.widths) {
            return;
        }
        const left = resizer.dataset.leftDock;
        const right = resizer.dataset.rightDock;
        this.drag = { resizer, pointerId: event.pointerId, startX: event.clientX, left, right, leftWidth: this.widths[left], rightWidth: this.widths[right] };
        this.grid.classList.add('is-resizing-lower-docks');
        if (resizer.setPointerCapture) { resizer.setPointerCapture(event.pointerId); }
        document.addEventListener('pointermove', this.boundPointerMove);
        document.addEventListener('pointerup', this.boundPointerEnd);
        document.addEventListener('pointercancel', this.boundPointerEnd);
    };

    Controller.prototype.onPointerMove = function (event) {
        if (!this.drag || event.pointerId !== this.drag.pointerId) { return; }
        const delta = event.clientX - this.drag.startX;
        const total = this.drag.leftWidth + this.drag.rightWidth;
        const leftWidth = Math.min(total - CONFIG.minimumWidths[this.drag.right], Math.max(CONFIG.minimumWidths[this.drag.left], this.drag.leftWidth + delta));
        const next = Object.assign({}, this.widths);
        next[this.drag.left] = leftWidth;
        next[this.drag.right] = total - leftWidth;
        this.applyLayout(next);
    };

    Controller.prototype.endDrag = function () {
        if (!this.drag) { return; }
        const resizer = this.drag.resizer;
        const pointerId = this.drag.pointerId;
        this.drag = null;
        this.grid.classList.remove('is-resizing-lower-docks');
        if (resizer && resizer.releasePointerCapture) { try { resizer.releasePointerCapture(pointerId); } catch (error) {} }
        document.removeEventListener('pointermove', this.boundPointerMove);
        document.removeEventListener('pointerup', this.boundPointerEnd);
        document.removeEventListener('pointercancel', this.boundPointerEnd);
        this.persist();
    };

    Controller.prototype.onKeydown = function (event, resizer) {
        if (['ArrowLeft', 'ArrowRight', 'Home', 'End'].indexOf(event.key) === -1) { return; }
        event.preventDefault();
        const left = resizer.dataset.leftDock;
        const right = resizer.dataset.rightDock;
        const total = this.widths[left] + this.widths[right];
        let delta = event.key === 'ArrowLeft' ? -(event.shiftKey ? CONFIG.keyboardLargeStep : CONFIG.keyboardStep) : (event.shiftKey ? CONFIG.keyboardLargeStep : CONFIG.keyboardStep);
        if (event.key === 'Home') { delta = CONFIG.minimumWidths[left] - this.widths[left]; }
        if (event.key === 'End') { delta = (total - CONFIG.minimumWidths[right]) - this.widths[left]; }
        this.adjustPair(resizer, delta, true);
    };

    Controller.prototype.resetLayout = function () {
        storage.remove(CONFIG.storageKey);
        this.savedRatios = null;
        this.widths = null;
        this.applyLayout();
    };

    Controller.prototype.panelName = function (key) {
        const heading = this.panels[key] && this.panels[key].querySelector('.vh360-studio-dock-header h3');
        return heading ? heading.textContent.trim() : key;
    };

    Controller.prototype.updateAria = function () {
        this.resizers.forEach((resizer) => {
            const left = resizer.dataset.leftDock;
            const right = resizer.dataset.rightDock;
            const total = this.widths[left] + this.widths[right];
            resizer.setAttribute('aria-valuemin', String(CONFIG.minimumWidths[left]));
            resizer.setAttribute('aria-valuemax', String(Math.round(total - CONFIG.minimumWidths[right])));
            resizer.setAttribute('aria-valuenow', String(Math.round(this.widths[left])));
            const template = this.strings.valueText || '%s %d pixels, %s %d pixels';
            resizer.setAttribute('aria-valuetext', template.replace('%s', this.panelName(left)).replace('%d', Math.round(this.widths[left])).replace('%s', this.panelName(right)).replace('%d', Math.round(this.widths[right])));
        });
    };

    function initAll() { document.querySelectorAll('[data-studio-dock-layout]').forEach((grid) => new Controller(grid).init()); }
    if (document.readyState === 'loading') { document.addEventListener('DOMContentLoaded', initAll); } else { initAll(); }
}());
