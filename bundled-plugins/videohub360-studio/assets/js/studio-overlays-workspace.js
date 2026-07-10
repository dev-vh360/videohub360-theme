(function () {
    'use strict';

    const CONFIG = {
        defaultWidth: 400,
        minWidth: 320,
        maxWidth: 520,
        stackedBreakpoint: 1280,
        widthKey: 'vh360_studio_overlays_width',
        collapsedKey: 'vh360_studio_overlays_collapsed',
        moduleKey: 'vh360_studio_overlays_active_module',
        sectionKey: 'vh360_studio_overlays_active_section',
        modules: ['lower-thirds', 'bible', 'countdown'],
        sections: ['control', 'customize', 'settings'],
    };

    const storage = {
        get(key) { try { return window.localStorage.getItem(key); } catch (error) { return null; } },
        set(key, value) { try { window.localStorage.setItem(key, value); } catch (error) {} },
    };

    function clamp(value, min, max) { return Math.min(Math.max(value, min), max); }
    function isAllowed(value, allowed) { return allowed.indexOf(value) !== -1; }

    function Controller(workspace) {
        this.workspace = workspace;
        this.root = workspace.closest('[data-vh360-studio-dashboard]') || document;
        this.monitors = workspace.querySelector('[data-overlays-monitors]');
        this.resizer = workspace.querySelector('[data-overlays-resizer]');
        this.dock = workspace.querySelector('[data-overlays-dock]');
        this.body = workspace.querySelector('[data-overlays-body]');
        this.collapseButton = workspace.querySelector('[data-overlays-collapse]');
        this.collapseLabel = workspace.querySelector('[data-overlays-collapse-label]');
        this.status = workspace.querySelector('[data-overlays-status]');
        this.moduleTabs = Array.from(workspace.querySelectorAll('[data-overlays-module-tab]'));
        this.panels = Array.from(workspace.querySelectorAll('[data-overlays-panel]'));
        this.sectionTabs = Array.from(workspace.querySelectorAll('[data-overlays-section-tab]'));
        this.width = this.savedWidth();
        this.collapsed = storage.get(CONFIG.collapsedKey) === 'true';
        this.activeModule = this.savedChoice(CONFIG.moduleKey, CONFIG.modules, CONFIG.modules[0]);
        this.activeSection = this.savedChoice(CONFIG.sectionKey, CONFIG.sections, CONFIG.sections[0]);
        this.isStacked = false;
        this.drag = null;
        this.boundPointerDown = this.onPointerDown.bind(this);
        this.boundPointerMove = this.onPointerMove.bind(this);
        this.boundPointerEnd = this.endDrag.bind(this);
        this.boundResizerKeydown = this.onResizerKeydown.bind(this);
        this.boundResizerDblclick = this.onResizerDblclick.bind(this);
        this.boundCollapseClick = this.onCollapseClick.bind(this);
        this.boundWindowResize = this.refreshLayout.bind(this);
        this.boundWindowBlur = this.endDrag.bind(this);
        this.tabHandlers = [];
        this.dragPointerId = null;
    }

    Controller.prototype.savedWidth = function () {
        const parsed = parseInt(storage.get(CONFIG.widthKey), 10);
        return Number.isFinite(parsed) ? clamp(parsed, CONFIG.minWidth, CONFIG.maxWidth) : CONFIG.defaultWidth;
    };

    Controller.prototype.savedChoice = function (key, allowed, fallback) {
        const value = storage.get(key);
        return isAllowed(value, allowed) ? value : fallback;
    };

    Controller.prototype.init = function () {
        this.applyWidth(this.width, false);
        this.applyCollapsed(this.collapsed, false);
        this.selectModule(this.activeModule, false);
        this.selectSection(this.activeSection, false);
        this.bindEvents();
        this.observe();
        this.refreshLayout();
    };

    Controller.prototype.bindEvents = function () {
        if (this.resizer) {
            this.resizer.addEventListener('pointerdown', this.boundPointerDown);
            this.resizer.addEventListener('keydown', this.boundResizerKeydown);
            this.resizer.addEventListener('dblclick', this.boundResizerDblclick);
            this.resizer.addEventListener('lostpointercapture', this.boundPointerEnd);
        }
        if (this.collapseButton) this.collapseButton.addEventListener('click', this.boundCollapseClick);
        this.moduleTabs.forEach((tab) => this.bindTab(tab, this.moduleTabs, (value) => this.selectModule(value, true)));
        this.sectionTabs.forEach((tab) => this.bindTab(tab, this.sectionTabs, (value) => this.selectSection(value, true)));
        window.addEventListener('resize', this.boundWindowResize);
        window.addEventListener('orientationchange', this.boundWindowResize);
        window.addEventListener('blur', this.boundWindowBlur);
    };

    Controller.prototype.bindTab = function (tab, tabs, callback) {
        const clickHandler = () => callback(tab.dataset.module || tab.dataset.section);
        const keyHandler = (event) => {
            if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
            event.preventDefault();
            const current = tabs.indexOf(tab);
            let next = current;
            if (event.key === 'ArrowLeft') next = (current - 1 + tabs.length) % tabs.length;
            if (event.key === 'ArrowRight') next = (current + 1) % tabs.length;
            if (event.key === 'Home') next = 0;
            if (event.key === 'End') next = tabs.length - 1;
            tabs[next].focus();
            callback(tabs[next].dataset.module || tabs[next].dataset.section);
        };
        tab.addEventListener('click', clickHandler);
        tab.addEventListener('keydown', keyHandler);
        this.tabHandlers.push({ tab, clickHandler, keyHandler });
    };

    Controller.prototype.observe = function () {
        if (!('ResizeObserver' in window)) return;
        this.workspaceObserver = new ResizeObserver(() => this.refreshLayout());
        this.workspaceObserver.observe(this.workspace);
        if (this.monitors) {
            this.monitorObserver = new ResizeObserver(() => this.matchDockHeight());
            this.monitorObserver.observe(this.monitors);
        }
    };

    Controller.prototype.refreshLayout = function () {
        const width = this.workspace.getBoundingClientRect().width;
        const stacked = width < CONFIG.stackedBreakpoint;
        if (stacked !== this.isStacked) {
            this.isStacked = stacked;
            this.workspace.classList.toggle('is-overlays-stacked', stacked);
            this.dispatchLayoutChange();
        }
        this.matchDockHeight();
    };

    Controller.prototype.matchDockHeight = function () {
        if (!this.dock || !this.monitors) return;
        if (this.isStacked) {
            this.dock.style.height = '';
            return;
        }
        this.dock.style.height = `${Math.round(this.monitors.getBoundingClientRect().height)}px`;
    };

    Controller.prototype.applyWidth = function (width, persist, notify) {
        this.width = clamp(parseInt(width, 10) || CONFIG.defaultWidth, CONFIG.minWidth, CONFIG.maxWidth);
        this.workspace.style.setProperty('--vh360-studio-overlays-width', `${this.width}px`);
        if (this.resizer) this.resizer.setAttribute('aria-valuenow', String(this.width));
        if (persist) storage.set(CONFIG.widthKey, String(this.width));
        if (notify !== false) this.dispatchLayoutChange();
    };

    Controller.prototype.applyCollapsed = function (collapsed, persist) {
        this.collapsed = Boolean(collapsed);
        this.workspace.classList.toggle('is-overlays-collapsed', this.collapsed);
        if (this.collapseButton) this.collapseButton.setAttribute('aria-expanded', String(!this.collapsed));
        if (this.collapseLabel) this.collapseLabel.textContent = this.collapsed ? this.text('labelExpand') : this.text('labelCollapse');
        if (persist) storage.set(CONFIG.collapsedKey, String(this.collapsed));
        this.announce(this.collapsed ? this.text('messageCollapsed') : this.text('messageExpanded'));
        this.refreshLayout();
        this.dispatchLayoutChange();
    };

    Controller.prototype.onCollapseClick = function () { this.applyCollapsed(!this.collapsed, true); };

    Controller.prototype.selectModule = function (module, persist) {
        if (!isAllowed(module, CONFIG.modules)) module = CONFIG.modules[0];
        this.activeModule = module;
        this.moduleTabs.forEach((tab) => {
            const selected = tab.dataset.module === module;
            this.setTabState(tab, selected);
            if (selected) tab.setAttribute('aria-controls', this.panelId(module, this.activeSection));
        });
        this.updatePanels();
        if (persist) storage.set(CONFIG.moduleKey, module);
        this.dispatch('vh360:studio-overlays:module-change', { module, section: this.activeSection });
        this.announce(this.format(this.text('messageModuleChange'), this.moduleLabel(module)));
    };

    Controller.prototype.selectSection = function (section, persist) {
        if (!isAllowed(section, CONFIG.sections)) section = CONFIG.sections[0];
        this.activeSection = section;
        this.sectionTabs.forEach((tab) => {
            const selected = tab.dataset.section === section;
            this.setTabState(tab, selected);
            if (selected) tab.setAttribute('aria-controls', this.panelId(this.activeModule, section));
        });
        this.updatePanels();
        if (persist) storage.set(CONFIG.sectionKey, section);
        this.dispatch('vh360:studio-overlays:section-change', { module: this.activeModule, section });
        this.announce(this.format(this.text('messageSectionChange'), this.sectionLabel(section)));
    };

    Controller.prototype.updatePanels = function () {
        const activePanelId = this.panelId(this.activeModule, this.activeSection);
        this.panels.forEach((panel) => { panel.hidden = panel.id !== activePanelId; });
        this.moduleTabs.forEach((tab) => {
            tab.setAttribute('aria-controls', this.panelId(tab.dataset.module, this.activeSection));
        });
        this.sectionTabs.forEach((tab) => {
            tab.setAttribute('aria-controls', this.panelId(this.activeModule, tab.dataset.section));
        });
    };

    Controller.prototype.panelId = function (module, section) { return `vh360-overlays-panel-${module}-${section}`; };

    Controller.prototype.setTabState = function (tab, selected) {
        tab.setAttribute('aria-selected', String(selected));
        tab.tabIndex = selected ? 0 : -1;
    };

    Controller.prototype.onPointerDown = function (event) {
        if (this.collapsed || this.isStacked) return;
        event.preventDefault();
        this.drag = { startX: event.clientX, startWidth: this.width };
        this.workspace.classList.add('is-resizing-overlays');
        this.resizer.setPointerCapture(event.pointerId);
        this.dragPointerId = event.pointerId;
        document.addEventListener('pointermove', this.boundPointerMove);
        document.addEventListener('pointerup', this.boundPointerEnd);
        document.addEventListener('pointercancel', this.boundPointerEnd);
    };

    Controller.prototype.onPointerMove = function (event) {
        if (!this.drag) return;
        this.applyWidth(this.drag.startWidth - (event.clientX - this.drag.startX), false, false);
    };

    Controller.prototype.endDrag = function () {
        if (!this.drag) return;
        this.drag = null;
        this.workspace.classList.remove('is-resizing-overlays');
        if (this.resizer && this.dragPointerId !== null && this.resizer.hasPointerCapture && this.resizer.hasPointerCapture(this.dragPointerId)) {
            this.resizer.releasePointerCapture(this.dragPointerId);
        }
        this.dragPointerId = null;
        storage.set(CONFIG.widthKey, String(this.width));
        document.removeEventListener('pointermove', this.boundPointerMove);
        document.removeEventListener('pointerup', this.boundPointerEnd);
        document.removeEventListener('pointercancel', this.boundPointerEnd);
        this.dispatchLayoutChange();
    };

    Controller.prototype.onResizerDblclick = function () { this.applyWidth(CONFIG.defaultWidth, true); };

    Controller.prototype.onResizerKeydown = function (event) {
        if (!['ArrowLeft', 'ArrowRight', 'Home', 'End'].includes(event.key)) return;
        event.preventDefault();
        const step = event.shiftKey ? 40 : 10;
        if (event.key === 'ArrowLeft') this.applyWidth(this.width + step, true);
        if (event.key === 'ArrowRight') this.applyWidth(this.width - step, true);
        if (event.key === 'Home') this.applyWidth(CONFIG.minWidth, true);
        if (event.key === 'End') this.applyWidth(CONFIG.maxWidth, true);
    };

    Controller.prototype.destroy = function () {
        this.endDrag();
        if (this.workspaceObserver) this.workspaceObserver.disconnect();
        if (this.monitorObserver) this.monitorObserver.disconnect();
        if (this.resizer) {
            this.resizer.removeEventListener('pointerdown', this.boundPointerDown);
            this.resizer.removeEventListener('keydown', this.boundResizerKeydown);
            this.resizer.removeEventListener('dblclick', this.boundResizerDblclick);
            this.resizer.removeEventListener('lostpointercapture', this.boundPointerEnd);
        }
        if (this.collapseButton) this.collapseButton.removeEventListener('click', this.boundCollapseClick);
        this.tabHandlers.forEach(({ tab, clickHandler, keyHandler }) => {
            tab.removeEventListener('click', clickHandler);
            tab.removeEventListener('keydown', keyHandler);
        });
        window.removeEventListener('resize', this.boundWindowResize);
        window.removeEventListener('orientationchange', this.boundWindowResize);
        window.removeEventListener('blur', this.boundWindowBlur);
        document.removeEventListener('pointermove', this.boundPointerMove);
        document.removeEventListener('pointerup', this.boundPointerEnd);
        document.removeEventListener('pointercancel', this.boundPointerEnd);
    };

    Controller.prototype.dispatchLayoutChange = function () {
        this.dispatch('vh360:studio-overlays:layout-change', {
            stacked: this.isStacked,
            collapsed: this.collapsed,
            dockWidth: this.width,
            workspaceWidth: Math.round(this.workspace.getBoundingClientRect().width),
        });
    };

    Controller.prototype.text = function (name) {
        const fallback = {
            labelExpand: 'Expand',
            labelCollapse: 'Collapse',
            messageCollapsed: 'Overlays collapsed.',
            messageExpanded: 'Overlays expanded.',
            messageModuleChange: 'Overlay module changed to %s.',
            messageSectionChange: 'Overlay section changed to %s.',
        };
        return this.dock && this.dock.dataset[name] ? this.dock.dataset[name] : fallback[name];
    };

    Controller.prototype.format = function (template, value) { return template.replace('%s', value); };
    Controller.prototype.moduleLabel = function (module) {
        const tab = this.moduleTabs.find((item) => item.dataset.module === module);
        return tab ? tab.textContent.trim() : module;
    };
    Controller.prototype.sectionLabel = function (section) {
        const tab = this.sectionTabs.find((item) => item.dataset.section === section);
        return tab ? tab.textContent.trim() : section;
    };

    Controller.prototype.dispatch = function (name, detail) { this.workspace.dispatchEvent(new CustomEvent(name, { bubbles: true, detail })); };
    Controller.prototype.announce = function (message) { if (this.status) this.status.textContent = message; };

    function initAll() {
        const controllers = Array.from(document.querySelectorAll('[data-overlays-workspace]')).map((workspace) => {
            const controller = new Controller(workspace);
            controller.init();
            return controller;
        });
        window.VH360StudioOverlaysWorkspace = {
            controllers,
            config: CONFIG,
            destroy() { controllers.forEach((controller) => controller.destroy()); },
        };
    }

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initAll);
    else initAll();
}());
