(function () {
    function activateTab(wrapper, tab) {
        var target = tab.getAttribute('aria-controls');
        wrapper.querySelectorAll('[data-vh360-pricing-tab]').forEach(function (button) {
            var active = button === tab;
            button.classList.toggle('is-active', active);
            button.setAttribute('aria-selected', active ? 'true' : 'false');
            button.setAttribute('tabindex', active ? '0' : '-1');
        });
        wrapper.querySelectorAll('.vh360-pricing-panel').forEach(function (panel) {
            var active = panel.id === target;
            panel.classList.toggle('is-active', active);
            panel.hidden = !active;
        });
    }

    document.addEventListener('click', function (event) {
        var tab = event.target.closest('[data-vh360-pricing-tab]');
        if (!tab) {
            return;
        }
        var wrapper = tab.closest('.vh360-pricing-toggle');
        if (wrapper) {
            activateTab(wrapper, tab);
        }
    });

    document.addEventListener('keydown', function (event) {
        var tab = event.target.closest('[data-vh360-pricing-tab]');
        if (!tab || ['ArrowLeft', 'ArrowRight', 'Home', 'End'].indexOf(event.key) === -1) {
            return;
        }
        var wrapper = tab.closest('.vh360-pricing-toggle');
        if (!wrapper) {
            return;
        }
        var tabs = Array.prototype.slice.call(wrapper.querySelectorAll('[data-vh360-pricing-tab]'));
        var index = tabs.indexOf(tab);
        if (index < 0) {
            return;
        }
        event.preventDefault();
        if (event.key === 'Home') {
            index = 0;
        } else if (event.key === 'End') {
            index = tabs.length - 1;
        } else {
            index += event.key === 'ArrowRight' ? 1 : -1;
            if (index < 0) {
                index = tabs.length - 1;
            }
            if (index >= tabs.length) {
                index = 0;
            }
        }
        tabs[index].focus();
        activateTab(wrapper, tabs[index]);
    });
})();
