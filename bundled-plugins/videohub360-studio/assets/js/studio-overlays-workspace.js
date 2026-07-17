(function () {
    'use strict';

var VH360StorageCompat = window.VH360Storage || (function(){
  var memory = {};
  function persistentAllowed(){ return !window.VH360ConsentExpected; }
  return {
    getPreference: function(key, def){ if(!persistentAllowed()) { return Object.prototype.hasOwnProperty.call(memory, key) ? memory[key] : def; } try { var value = window['localStorage'].getItem(key); return value === null ? def : value; } catch (e) { return def; } },
    setPreference: function(key, value){ memory[key] = value; if(!persistentAllowed()) { return; } try { window['localStorage'].setItem(key, value); } catch (e) {} },
    removePreference: function(key){ delete memory[key]; if(!persistentAllowed()) { return; } try { window['localStorage'].removeItem(key); } catch (e) {} },
    registerPreferenceKey: function(){}
  };
})();

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

    const SLOT_BY_MODULE = {
        'lower-thirds': 'lowerThird',
        bible: 'bible',
        countdown: 'countdown',
    };

    const API_BY_MODULE = {
        'lower-thirds': 'VH360StudioLowerThirds',
        bible: 'VH360StudioBible',
        countdown: 'VH360StudioCountdown',
    };

    const storage = {
        get(key) {
            try {
                return VH360StorageCompat.getPreference(key);
            } catch (error) {
                return null;
            }
        },
        set(key, value) {
            try {
                VH360StorageCompat.setPreference(key, value);
            } catch (error) {}
        },
        remove(key) {
            try {
                VH360StorageCompat.removePreference(key);
            } catch (error) {}
        },
    };

    function clamp(value, min, max) {
        return Math.min(Math.max(value, min), max);
    }

    function allowedOrdered(values, allowed) {
        if (!Array.isArray(values)) {
            return [];
        }
        const selected = values.reduce((accumulator, value) => {
            accumulator[value] = true;
            return accumulator;
        }, {});
        return allowed.filter((value) => selected[value]);
    }

    function Controller(workspace) {
        this.workspace = workspace;
        this.root = workspace.closest('[data-vh360-studio-dashboard]') || document;
        this.config = window.vh360StudioDashboard || {};
        this.strings = this.config.strings || {};
        this.allowedModules = allowedOrdered(
            (this.config.overlayTools && this.config.overlayTools.allowedModules) || CONFIG.modules,
            CONFIG.modules
        );
        this.enabledModules = allowedOrdered(
            (this.config.overlayTools && this.config.overlayTools.enabledModules) || [],
            this.allowedModules
        );
        this.previousEnabledModules = this.enabledModules.slice();

        this.resizer = workspace.querySelector('[data-overlays-resizer]');
        this.dock = workspace.querySelector('[data-overlays-dock]');
        this.body = workspace.querySelector('[data-overlays-body]');
        this.collapseButton = workspace.querySelector('[data-overlays-collapse]');
        this.collapseLabel = workspace.querySelector('[data-overlays-collapse-label]');
        this.status = workspace.querySelector('[data-overlays-status]');
        this.empty = workspace.querySelector('[data-overlays-empty]');
        this.moduleTabsWrap = workspace.querySelector('[data-overlays-module-tabs]');
        this.sectionTabsWrap = workspace.querySelector('[data-overlays-section-tabs]');
        this.moduleTabs = Array.from(workspace.querySelectorAll('[data-overlays-module-tab]'));
        this.sectionTabs = Array.from(workspace.querySelectorAll('[data-overlays-section-tab]'));
        this.panels = Array.from(workspace.querySelectorAll('[data-overlays-panel]'));

        this.modal = this.root.querySelector('[data-overlay-tools-modal]');
        this.checkboxes = this.modal ? Array.from(this.modal.querySelectorAll('[data-overlay-tool-checkbox]')) : [];
        this.saveButton = this.modal ? this.modal.querySelector('[data-save-overlay-tools]') : null;
        this.modalStatus = this.modal ? this.modal.querySelector('[data-overlay-tools-status]') : null;
        this.lastModalTrigger = null;
        this.saving = false;

        this.width = this.savedWidth();
        this.collapsed = storage.get(CONFIG.collapsedKey) === 'true';
        this.activeSection = this.savedSection();
        this.activeModule = this.resolveInitialModule();
        this.isStacked = false;
        this.drag = null;
        this.dragPointerId = null;

        this.boundPointerDown = this.onPointerDown.bind(this);
        this.boundPointerMove = this.onPointerMove.bind(this);
        this.boundPointerEnd = this.endDrag.bind(this);
        this.boundResizerKeydown = this.onResizerKeydown.bind(this);
        this.boundResizerDblclick = this.onResizerDblclick.bind(this);
        this.boundCollapseClick = this.onCollapseClick.bind(this);
        this.boundWindowResize = this.refreshLayout.bind(this);
        this.boundWindowBlur = this.endDrag.bind(this);
    }

    Controller.prototype.savedWidth = function () {
        const parsed = parseInt(storage.get(CONFIG.widthKey), 10);
        return Number.isFinite(parsed) ? clamp(parsed, CONFIG.minWidth, CONFIG.maxWidth) : CONFIG.defaultWidth;
    };

    Controller.prototype.savedSection = function () {
        const section = storage.get(CONFIG.sectionKey);
        return CONFIG.sections.indexOf(section) !== -1 ? section : CONFIG.sections[0];
    };

    Controller.prototype.resolveInitialModule = function () {
        const savedModule = storage.get(CONFIG.moduleKey);
        if (this.enabledModules.indexOf(savedModule) !== -1) {
            return savedModule;
        }
        return this.enabledModules[0] || null;
    };

    Controller.prototype.init = function () {
        this.bindEvents();
        this.applyWidth(this.width, false, false);
        this.applyCollapsed(this.collapsed, false, false);
        this.applyEnabledModules(this.enabledModules, { announce: false, dispatch: false });
        this.enabledModules.forEach((module) => this.callModuleApi(module, 'init'));
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

        if (this.collapseButton) {
            this.collapseButton.addEventListener('click', this.boundCollapseClick);
        }

        this.moduleTabs.forEach((tab) => {
            tab.addEventListener('click', () => this.selectModule(tab.dataset.module, true));
            tab.addEventListener('keydown', (event) => this.onModuleTabKeydown(event, tab));
        });

        this.sectionTabs.forEach((tab) => {
            tab.addEventListener('click', () => this.selectSection(tab.dataset.section, true));
            tab.addEventListener('keydown', (event) => this.onSectionTabKeydown(event, tab));
        });

        this.root.querySelectorAll('[data-open-overlay-tools]').forEach((button) => {
            button.addEventListener('click', (event) => this.openToolsModal(event.currentTarget));
        });

        if (this.modal) {
            this.modal.querySelectorAll('[data-close-overlay-tools]').forEach((button) => {
                button.addEventListener('click', () => this.closeToolsModal());
            });
            this.modal.addEventListener('keydown', (event) => {
                if (event.key === 'Escape') {
                    this.closeToolsModal();
                }
            });
        }

        if (this.saveButton) {
            this.saveButton.addEventListener('click', () => this.saveTools());
        }

        window.addEventListener('resize', this.boundWindowResize);
        window.addEventListener('orientationchange', this.boundWindowResize);
        window.addEventListener('blur', this.boundWindowBlur);
    };

    Controller.prototype.observe = function () {
        if (!('ResizeObserver' in window)) {
            return;
        }

        this.workspaceObserver = new ResizeObserver(() => this.refreshLayout());
        this.workspaceObserver.observe(this.workspace);
    };

    Controller.prototype.refreshLayout = function () {
        const width = this.workspace.getBoundingClientRect().width;
        const stacked = width < CONFIG.stackedBreakpoint;
        if (stacked !== this.isStacked) {
            this.isStacked = stacked;
            this.workspace.classList.toggle('is-overlays-stacked', stacked);
            this.dispatchLayoutChange();
        }
    };


    Controller.prototype.applyWidth = function (width, persist, notify) {
        this.width = clamp(parseInt(width, 10) || CONFIG.defaultWidth, CONFIG.minWidth, CONFIG.maxWidth);
        this.workspace.style.setProperty('--vh360-studio-overlays-width', `${this.width}px`);
        if (this.resizer) {
            this.resizer.setAttribute('aria-valuenow', String(this.width));
        }
        if (persist) {
            storage.set(CONFIG.widthKey, String(this.width));
        }
        if (notify !== false) {
            this.dispatchLayoutChange();
        }
    };

    Controller.prototype.applyCollapsed = function (collapsed, persist, notify) {
        this.collapsed = Boolean(collapsed);
        this.workspace.classList.toggle('is-overlays-collapsed', this.collapsed);
        if (this.collapseButton) {
            this.collapseButton.setAttribute('aria-expanded', String(!this.collapsed));
        }
        if (this.collapseLabel) {
            this.collapseLabel.textContent = this.collapsed ? this.text('labelExpand') : this.text('labelCollapse');
        }
        if (persist) {
            storage.set(CONFIG.collapsedKey, String(this.collapsed));
        }
        if (notify !== false) {
            this.announce(this.collapsed ? this.text('messageCollapsed') : this.text('messageExpanded'));
            this.dispatchLayoutChange();
        }
        this.refreshLayout();
    };

    Controller.prototype.onCollapseClick = function () {
        this.applyCollapsed(!this.collapsed, true);
    };

    Controller.prototype.onPointerDown = function (event) {
        if (this.collapsed || this.isStacked) {
            return;
        }
        event.preventDefault();
        this.drag = { startX: event.clientX, startWidth: this.width };
        this.dragPointerId = event.pointerId;
        this.workspace.classList.add('is-resizing-overlays');
        if (this.resizer && this.resizer.setPointerCapture) {
            this.resizer.setPointerCapture(event.pointerId);
        }
        document.addEventListener('pointermove', this.boundPointerMove);
        document.addEventListener('pointerup', this.boundPointerEnd);
        document.addEventListener('pointercancel', this.boundPointerEnd);
    };

    Controller.prototype.onPointerMove = function (event) {
        if (!this.drag) {
            return;
        }
        const delta = event.clientX - this.drag.startX;
        this.applyWidth(this.drag.startWidth - delta, false);
    };

    Controller.prototype.endDrag = function () {
        if (!this.drag) {
            return;
        }
        this.drag = null;
        this.workspace.classList.remove('is-resizing-overlays');
        if (this.resizer && this.dragPointerId !== null && this.resizer.releasePointerCapture) {
            try {
                this.resizer.releasePointerCapture(this.dragPointerId);
            } catch (error) {}
        }
        this.dragPointerId = null;
        storage.set(CONFIG.widthKey, String(this.width));
        document.removeEventListener('pointermove', this.boundPointerMove);
        document.removeEventListener('pointerup', this.boundPointerEnd);
        document.removeEventListener('pointercancel', this.boundPointerEnd);
    };

    Controller.prototype.onResizerKeydown = function (event) {
        if (['ArrowLeft', 'ArrowRight', 'Home', 'End'].indexOf(event.key) === -1) {
            return;
        }
        event.preventDefault();
        const increment = event.shiftKey ? 50 : 20;
        let width = this.width;
        if (event.key === 'ArrowLeft') {
            width += increment;
        }
        if (event.key === 'ArrowRight') {
            width -= increment;
        }
        if (event.key === 'Home') {
            width = CONFIG.minWidth;
        }
        if (event.key === 'End') {
            width = CONFIG.maxWidth;
        }
        this.applyWidth(width, true);
    };

    Controller.prototype.onResizerDblclick = function () {
        this.applyWidth(CONFIG.defaultWidth, true);
    };

    Controller.prototype.visibleModuleTabs = function () {
        return this.moduleTabs.filter((tab) => !tab.hidden && this.enabledModules.indexOf(tab.dataset.module) !== -1);
    };

    Controller.prototype.onModuleTabKeydown = function (event, tab) {
        if (['ArrowLeft', 'ArrowRight', 'Home', 'End'].indexOf(event.key) === -1) {
            return;
        }
        event.preventDefault();
        const tabs = this.visibleModuleTabs();
        if (!tabs.length) {
            return;
        }
        const current = Math.max(0, tabs.indexOf(tab));
        let next = current;
        if (event.key === 'ArrowLeft') {
            next = (current - 1 + tabs.length) % tabs.length;
        }
        if (event.key === 'ArrowRight') {
            next = (current + 1) % tabs.length;
        }
        if (event.key === 'Home') {
            next = 0;
        }
        if (event.key === 'End') {
            next = tabs.length - 1;
        }
        tabs[next].focus();
        this.selectModule(tabs[next].dataset.module, true);
    };

    Controller.prototype.onSectionTabKeydown = function (event, tab) {
        if (['ArrowLeft', 'ArrowRight', 'Home', 'End'].indexOf(event.key) === -1 || !this.activeModule) {
            return;
        }
        event.preventDefault();
        const current = Math.max(0, this.sectionTabs.indexOf(tab));
        let next = current;
        if (event.key === 'ArrowLeft') {
            next = (current - 1 + this.sectionTabs.length) % this.sectionTabs.length;
        }
        if (event.key === 'ArrowRight') {
            next = (current + 1) % this.sectionTabs.length;
        }
        if (event.key === 'Home') {
            next = 0;
        }
        if (event.key === 'End') {
            next = this.sectionTabs.length - 1;
        }
        this.sectionTabs[next].focus();
        this.selectSection(this.sectionTabs[next].dataset.section, true);
    };

    Controller.prototype.resolveModuleAfterDisable = function (previousModules, nextModules) {
        if (this.activeModule && nextModules.indexOf(this.activeModule) !== -1) {
            return this.activeModule;
        }
        if (!nextModules.length) {
            return null;
        }
        const previousIndex = previousModules.indexOf(this.activeModule);
        if (previousIndex === -1) {
            return nextModules[0];
        }
        for (let index = previousIndex + 1; index < previousModules.length; index++) {
            if (nextModules.indexOf(previousModules[index]) !== -1) {
                return previousModules[index];
            }
        }
        for (let index = previousIndex - 1; index >= 0; index--) {
            if (nextModules.indexOf(previousModules[index]) !== -1) {
                return previousModules[index];
            }
        }
        return nextModules[0];
    };

    Controller.prototype.applyEnabledModules = function (modules, options) {
        const settings = Object.assign({ announce: true, dispatch: true }, options || {});
        const previousModules = this.enabledModules.slice();
        const nextModules = allowedOrdered(modules, this.allowedModules);
        this.enabledModules = nextModules;
        this.activeModule = this.resolveModuleAfterDisable(previousModules, nextModules);

        const empty = nextModules.length === 0;
        if (this.empty) {
            this.empty.hidden = !empty;
        }
        if (this.sectionTabsWrap) {
            this.sectionTabsWrap.hidden = empty;
        }
        if (this.moduleTabsWrap) {
            this.moduleTabsWrap.hidden = empty;
        }

        this.moduleTabs.forEach((tab) => {
            const enabled = nextModules.indexOf(tab.dataset.module) !== -1;
            tab.hidden = !enabled;
            this.setTabState(tab, false);
        });

        if (empty) {
            storage.remove(CONFIG.moduleKey);
            this.sectionTabs.forEach((tab) => {
                tab.hidden = true;
                tab.removeAttribute('aria-controls');
                this.setTabState(tab, false);
            });
            this.panels.forEach((panel) => {
                panel.hidden = true;
            });
            if (settings.announce) {
                this.announce(this.strings.overlayToolsNoneEnabled || 'No overlay tools enabled.');
            }
        } else {
            this.sectionTabs.forEach((tab) => {
                tab.hidden = false;
            });
            this.selectSection(this.activeSection, false, false);
            this.selectModule(this.activeModule, false, false);
            if (settings.announce) {
                this.announce(this.strings.overlayToolsSaved || 'Overlay tools saved.');
            }
        }

        if (settings.dispatch) {
            this.dispatch('vh360:studio-overlays:preference-change', {
                enabledModules: nextModules.slice(),
                previousModules,
            });
        }
    };

    Controller.prototype.selectModule = function (module, persist, notify) {
        const nextModule = this.enabledModules.indexOf(module) !== -1 ? module : (this.enabledModules[0] || null);
        this.activeModule = nextModule;
        this.moduleTabs.forEach((tab) => {
            const selected = Boolean(nextModule && tab.dataset.module === nextModule);
            this.setTabState(tab, selected);
            if (nextModule) {
                tab.setAttribute('aria-controls', this.panelId(tab.dataset.module, this.activeSection));
            }
        });
        this.updatePanels();
        if (persist && nextModule) {
            storage.set(CONFIG.moduleKey, nextModule);
        }
        if (notify !== false && nextModule) {
            this.dispatch('vh360:studio-overlays:module-change', { module: nextModule, section: this.activeSection });
            this.announce(this.format(this.text('messageModuleChange'), this.moduleLabel(nextModule)));
        }
    };

    Controller.prototype.selectSection = function (section, persist, notify) {
        const nextSection = CONFIG.sections.indexOf(section) !== -1 ? section : CONFIG.sections[0];
        this.activeSection = nextSection;
        this.sectionTabs.forEach((tab) => {
            const selected = tab.dataset.section === nextSection;
            this.setTabState(tab, selected);
            if (this.activeModule) {
                tab.setAttribute('aria-controls', this.panelId(this.activeModule, tab.dataset.section));
            }
        });
        this.updatePanels();
        if (persist) {
            storage.set(CONFIG.sectionKey, nextSection);
        }
        if (notify !== false && this.activeModule) {
            this.dispatch('vh360:studio-overlays:section-change', { module: this.activeModule, section: nextSection });
            this.announce(this.format(this.text('messageSectionChange'), this.sectionLabel(nextSection)));
        }
    };

    Controller.prototype.updatePanels = function () {
        const activePanelId = this.activeModule ? this.panelId(this.activeModule, this.activeSection) : '';
        this.panels.forEach((panel) => {
            panel.hidden = !activePanelId || panel.id !== activePanelId || this.enabledModules.indexOf(panel.dataset.module) === -1;
        });
    };

    Controller.prototype.panelId = function (module, section) {
        return module ? `vh360-overlays-panel-${module}-${section}` : '';
    };

    Controller.prototype.setTabState = function (tab, selected) {
        tab.setAttribute('aria-selected', String(selected));
        tab.tabIndex = selected ? 0 : -1;
    };

    Controller.prototype.openToolsModal = function (trigger) {
        this.lastModalTrigger = trigger;
        this.checkboxes.forEach((checkbox) => {
            checkbox.checked = this.enabledModules.indexOf(checkbox.value) !== -1;
        });
        if (this.modalStatus) {
            this.modalStatus.hidden = true;
            this.modalStatus.textContent = '';
        }
        if (this.modal) {
            this.modal.hidden = false;
            (this.checkboxes[0] || this.saveButton || this.modal).focus();
        }
    };

    Controller.prototype.closeToolsModal = function () {
        if (this.saving) {
            return;
        }
        if (this.modal) {
            this.modal.hidden = true;
        }
        if (this.lastModalTrigger && this.lastModalTrigger.focus) {
            this.lastModalTrigger.focus();
        }
    };

    Controller.prototype.checkedModules = function () {
        return allowedOrdered(
            this.checkboxes.filter((checkbox) => checkbox.checked).map((checkbox) => checkbox.value),
            this.allowedModules
        );
    };

    Controller.prototype.removedModulesWithActiveOverlays = function (nextModules) {
        const engine = window.VH360StudioOverlayEngine;
        if (!engine || typeof engine.getState !== 'function') {
            return [];
        }
        const state = engine.getState();
        return this.enabledModules
            .filter((module) => nextModules.indexOf(module) === -1)
            .filter((module) => {
                const slot = SLOT_BY_MODULE[module];
                return Boolean((state.preview && state.preview[slot]) || (state.program && state.program[slot]));
            });
    };

    Controller.prototype.saveTools = function () {
        if (this.saving) {
            return;
        }

        const nextModules = this.checkedModules();
        const activeRemovedModules = this.removedModulesWithActiveOverlays(nextModules);
        if (activeRemovedModules.length && !window.confirm(this.strings.overlayToolsConfirmDisable || 'Active overlays will be removed. Continue?')) {
            return;
        }

        this.setSaving(true);
        this.saveEnabledModules(nextModules)
            .then((savedModules) => {
                this.clearActiveRemovedModules(activeRemovedModules);
                this.enabledModules
                    .filter((module) => savedModules.indexOf(module) === -1)
                    .forEach((module) => this.callModuleApi(module, 'destroy'));
                savedModules
                    .filter((module) => this.enabledModules.indexOf(module) === -1)
                    .forEach((module) => this.callModuleApi(module, 'init'));
                this.applyEnabledModules(savedModules, { announce: true, dispatch: true });
                this.setSaving(false);
                this.closeToolsModal();
            })
            .catch(() => {
                this.setSaving(false);
                if (this.modalStatus) {
                    this.modalStatus.hidden = false;
                    this.modalStatus.textContent = this.strings.overlayToolsSaveFailed || 'Overlay tools could not be saved. Please try again.';
                }
            });
    };

    Controller.prototype.saveEnabledModules = function (modules) {
        const restRoot = (this.config.restRoot || '').replace(/\/$/, '');
        return window.fetch(`${restRoot}/overlay-tools`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
                'X-WP-Nonce': this.config.nonce || '',
            },
            body: JSON.stringify({ enabled_modules: modules }),
        })
            .then((response) => response.json().then((json) => {
                if (!response.ok) {
                    throw new Error(json.message || 'REST request failed.');
                }
                return json;
            }))
            .then((json) => allowedOrdered(json.enabled_modules || [], this.allowedModules));
    };

    Controller.prototype.setSaving = function (saving) {
        this.saving = Boolean(saving);
        if (this.saveButton) {
            this.saveButton.disabled = this.saving;
        }
    };

    Controller.prototype.clearActiveRemovedModules = function (modules) {
        const engine = window.VH360StudioOverlayEngine;
        if (!engine) {
            return;
        }
        modules.forEach((module) => {
            const slot = SLOT_BY_MODULE[module];
            if (slot && typeof engine.clearPreview === 'function') {
                engine.clearPreview(slot);
            }
            if (slot && typeof engine.clearProgram === 'function') {
                engine.clearProgram(slot);
            }
        });
    };

    Controller.prototype.callModuleApi = function (module, method) {
        const api = window[API_BY_MODULE[module]];
        if (api && typeof api[method] === 'function') {
            api[method]();
        }
    };

    Controller.prototype.dispatchLayoutChange = function () {
        this.dispatch('vh360:studio-overlays:layout-change', {
            width: this.width,
            collapsed: this.collapsed,
            stacked: this.isStacked,
        });
    };

    Controller.prototype.dispatch = function (name, detail) {
        this.workspace.dispatchEvent(new CustomEvent(name, { bubbles: true, detail }));
    };

    Controller.prototype.text = function (key) {
        if (!this.dock) {
            return key;
        }
        return this.dock.dataset[key] || key;
    };

    Controller.prototype.format = function (template, value) {
        return String(template || '').replace('%s', value);
    };

    Controller.prototype.moduleLabel = function (module) {
        const tab = this.moduleTabs.find((item) => item.dataset.module === module);
        return tab ? tab.textContent.trim() : module;
    };

    Controller.prototype.sectionLabel = function (section) {
        const tab = this.sectionTabs.find((item) => item.dataset.section === section);
        return tab ? tab.textContent.trim() : section;
    };

    Controller.prototype.announce = function (message) {
        if (this.status) {
            this.status.textContent = message || '';
        }
    };

    function initAll() {
        document.querySelectorAll('[data-overlays-workspace]').forEach((workspace) => {
            if (!workspace.vh360OverlaysController) {
                workspace.vh360OverlaysController = new Controller(workspace);
                workspace.vh360OverlaysController.init();
            }
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }
}());
