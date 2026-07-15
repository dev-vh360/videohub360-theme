<?php
/**
 * The header template
 *
 * @package Videohub360_Theme
 * @since 1.0.0
 */

if (!defined('ABSPATH')) {
    exit;
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no, viewport-fit=cover">
    <script>
    (function () {
        var html = document.documentElement;
        var standalone = window.navigator.standalone === true || (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches);
        if (standalone) {
            html.classList.add('vh360-pwa-standalone');
        }
        window.VH360ScrollContext = window.VH360ScrollContext || (function () {
            var lockState = null;
            function isStandalone() {
                return html.classList.contains('vh360-pwa-standalone') || window.navigator.standalone === true || (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches);
            }
            function isMobile() {
                return window.matchMedia ? window.matchMedia('(max-width: 768px)').matches : window.innerWidth <= 768;
            }
            function hasShellMarkup() {
                return !!(document.querySelector('[data-vh360-pwa-scroll]') && document.querySelector('.vh360-pwa-app-shell'));
            }
            function hasVisibleBottomNav() {
                var nav = document.querySelector('.vh360-mobile-bottom-nav');
                if (!nav) return false;
                var style = window.getComputedStyle(nav);
                return style.display !== 'none' && style.visibility !== 'hidden';
            }
            function isEarlyEligible() {
                return !!(isStandalone() && document.body && document.body.classList.contains('logged-in') && isMobile());
            }
            function isAppShellActive() {
                return !!(isEarlyEligible() && hasShellMarkup() && hasVisibleBottomNav());
            }
            function selectedElementFromClass() {
                return html.classList.contains('vh360-pwa-app-shell-active') ? document.querySelector('[data-vh360-pwa-scroll]') : window;
            }
            function getElement() {
                return selectedElementFromClass() || window;
            }
            function getScrollTopFor(element) {
                return element === window ? (window.scrollY || window.pageYOffset || 0) : element.scrollTop;
            }
            function setScrollTopFor(element, top) {
                if (element === window) {
                    window.scrollTo(0, top || 0);
                } else if (element) {
                    element.scrollTop = top || 0;
                }
            }
            function migrateLockElement(newElement) {
                if (!lockState || lockState.element === newElement) return;
                var oldElement = lockState.element;
                if (oldElement === window) {
                    if (document.body) document.body.style.overflow = lockState.previousOverflow;
                } else if (oldElement) {
                    oldElement.style.overflow = lockState.previousOverflow;
                }
                lockState.element = newElement;
                lockState.previousOverflow = newElement === window ? (document.body ? document.body.style.overflow : '') : newElement.style.overflow;
                if (newElement === window) {
                    if (document.body) document.body.style.overflow = 'hidden';
                } else if (newElement) {
                    newElement.style.overflow = 'hidden';
                }
            }
            function updateActiveClass() {
                var oldActive = html.classList.contains('vh360-pwa-app-shell-active');
                var oldElement = getElement();
                var oldTop = getScrollTopFor(oldElement);
                var nextActive = isAppShellActive() || (!hasShellMarkup() && isEarlyEligible());
                html.classList.toggle('vh360-pwa-app-shell-active', nextActive);
                var newElement = getElement();
                if (oldElement !== newElement) {
                    setScrollTopFor(newElement, oldTop);
                    migrateLockElement(newElement);
                    window.dispatchEvent(new CustomEvent('vh360:scrollcontextchange', { detail: { oldElement: oldElement, newElement: newElement, scrollTop: oldTop } }));
                } else if (oldActive !== nextActive) {
                    window.dispatchEvent(new CustomEvent('vh360:scrollcontextchange', { detail: { oldElement: oldElement, newElement: newElement, scrollTop: oldTop } }));
                }
                if (nextActive && hasShellMarkup() && !isAppShellActive()) {
                    html.classList.remove('vh360-pwa-app-shell-active');
                }
            }
            function getScrollTop() {
                return getScrollTopFor(getElement());
            }
            function getViewportHeight() {
                var el = getElement();
                return el === window ? (window.innerHeight || document.documentElement.clientHeight) : el.clientHeight;
            }
            function getScrollHeight() {
                var el = getElement();
                return el === window ? Math.max(document.body ? document.body.scrollHeight : 0, document.documentElement.scrollHeight) : el.scrollHeight;
            }
            function getElementTop(element) {
                var el = getElement();
                if (!element) return 0;
                if (el === window) {
                    var rect = element.getBoundingClientRect();
                    return rect.top + getScrollTopFor(window);
                }
                return element.getBoundingClientRect().top - el.getBoundingClientRect().top + el.scrollTop;
            }
            function scrollToTop(top, options) {
                var el = getElement();
                var behavior = options && options.behavior;
                if (el === window) {
                    window.scrollTo({ top: top, behavior: behavior || 'auto' });
                } else if (behavior === 'smooth' && el.scrollTo) {
                    el.scrollTo({ top: top, behavior: 'smooth' });
                } else {
                    el.scrollTop = top;
                }
            }
            function scrollElementIntoView(element, offset, options) {
                scrollToTop(Math.max(0, getElementTop(element) - (offset || 0)), options || {});
            }
            function lock(reason) {
                var el = getElement();
                if (!lockState) {
                    lockState = { element: el, previousOverflow: el === window ? (document.body ? document.body.style.overflow : '') : el.style.overflow, reasons: {}, total: 0 };
                    if (el === window) {
                        if (document.body) document.body.style.overflow = 'hidden';
                    } else {
                        el.style.overflow = 'hidden';
                    }
                }
                reason = reason || 'default';
                lockState.reasons[reason] = (lockState.reasons[reason] || 0) + 1;
                lockState.total += 1;
            }
            function unlock(reason) {
                if (!lockState) return;
                reason = reason || 'default';
                if (!lockState.reasons[reason]) return;
                lockState.reasons[reason] -= 1;
                lockState.total = Math.max(0, lockState.total - 1);
                if (lockState.reasons[reason] <= 0) {
                    delete lockState.reasons[reason];
                }
                if (lockState.total > 0) return;
                var el = lockState.element;
                if (el === window) {
                    if (document.body) document.body.style.overflow = lockState.previousOverflow;
                } else if (el) {
                    el.style.overflow = lockState.previousOverflow;
                }
                lockState = null;
            }
            window.addEventListener('pageshow', updateActiveClass);
            window.addEventListener('resize', updateActiveClass);
            if (window.matchMedia) {
                var mq = window.matchMedia('(max-width: 768px)');
                if (mq.addEventListener) mq.addEventListener('change', updateActiveClass);
                else if (mq.addListener) mq.addListener(updateActiveClass);
                var standaloneMq = window.matchMedia('(display-mode: standalone)');
                if (standaloneMq.addEventListener) standaloneMq.addEventListener('change', function () { if (standaloneMq.matches) html.classList.add('vh360-pwa-standalone'); updateActiveClass(); });
                else if (standaloneMq.addListener) standaloneMq.addListener(updateActiveClass);
            }
            function forceUnlockAll() {
                if (!lockState) return;
                var el = lockState.element;
                if (el === window) {
                    if (document.body) document.body.style.overflow = lockState.previousOverflow;
                } else if (el) {
                    el.style.overflow = lockState.previousOverflow;
                }
                lockState = null;
            }
            return { isAppShellActive: isAppShellActive, updateActiveClass: updateActiveClass, getElement: getElement, getEventTarget: getElement, getScrollTop: getScrollTop, getViewportHeight: getViewportHeight, getScrollHeight: getScrollHeight, getElementTop: getElementTop, scrollTo: scrollToTop, scrollElementIntoView: scrollElementIntoView, lock: lock, unlock: unlock, forceUnlockAll: forceUnlockAll };
        }());
    }());
    </script>
    <link rel="profile" href="https://gmpg.org/xfn/11">
    <?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<script>if(window.VH360ScrollContext&&window.VH360ScrollContext.updateActiveClass){window.VH360ScrollContext.updateActiveClass();}</script>

<div class="vh360-pwa-app-shell">
    <div class="vh360-pwa-app-scroll" data-vh360-pwa-scroll>

<?php 
// Show urgent bulletin banner
$urgent_bulletins = vh360_get_urgent_bulletins();
if (!empty($urgent_bulletins)) :
    get_template_part('template-parts/bulletin/banner');
endif;
?>

<div id="page" class="site">
    <a class="skip-link screen-reader-text" href="#primary"><?php esc_html_e('Skip to content', 'videohub360-theme'); ?></a>

    <?php get_template_part('template-parts/header/header-layout'); ?>

    <div class="site-layout-wrapper">
        <?php get_template_part('template-parts/navigation/community-menu'); ?>

        <div id="content" class="site-content">
