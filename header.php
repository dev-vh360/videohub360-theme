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
            var lockCount = 0;
            var previousOverflow = '';
            function isStandalone() {
                return html.classList.contains('vh360-pwa-standalone') || window.navigator.standalone === true || (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches);
            }
            function isMobile() {
                return window.matchMedia ? window.matchMedia('(max-width: 768px)').matches : window.innerWidth <= 768;
            }
            function isAppShellActive() {
                var body = document.body;
                var scroll = document.querySelector('[data-vh360-pwa-scroll]');
                var shell = document.querySelector('.vh360-pwa-app-shell');
                var nav = document.querySelector('.vh360-mobile-bottom-nav');
                var navVisible = false;
                if (nav) {
                    var style = window.getComputedStyle(nav);
                    navVisible = style.display !== 'none' && style.visibility !== 'hidden';
                }
                return !!(isStandalone() && body && body.classList.contains('logged-in') && isMobile() && shell && scroll && nav && navVisible);
            }
            var transferredWindowScroll = false;
            function updateActiveClass() {
                var active = isAppShellActive();
                var wasActive = html.classList.contains('vh360-pwa-app-shell-active');
                html.classList.toggle('vh360-pwa-app-shell-active', active);
                if (active && !wasActive && !transferredWindowScroll) {
                    var scroll = document.querySelector('[data-vh360-pwa-scroll]');
                    var top = window.scrollY || window.pageYOffset || 0;
                    if (scroll && top > 0) {
                        scroll.scrollTop = top;
                    }
                    transferredWindowScroll = true;
                }
            }
            function getElement() {
                updateActiveClass();
                return html.classList.contains('vh360-pwa-app-shell-active') ? document.querySelector('[data-vh360-pwa-scroll]') : window;
            }
            function getScrollTop() {
                var el = getElement();
                return el === window ? (window.scrollY || window.pageYOffset || 0) : el.scrollTop;
            }
            function getViewportHeight() {
                var el = getElement();
                return el === window ? (window.innerHeight || document.documentElement.clientHeight) : el.clientHeight;
            }
            function getScrollHeight() {
                var el = getElement();
                return el === window ? Math.max(document.body ? document.body.scrollHeight : 0, document.documentElement.scrollHeight) : el.scrollHeight;
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
            function lock() {
                var el = getElement();
                lockCount += 1;
                if (lockCount > 1) return;
                if (el === window) {
                    previousOverflow = document.body ? document.body.style.overflow : '';
                    if (document.body) document.body.style.overflow = 'hidden';
                } else {
                    previousOverflow = el.style.overflow;
                    el.style.overflow = 'hidden';
                }
            }
            function unlock() {
                var el = getElement();
                lockCount = Math.max(0, lockCount - 1);
                if (lockCount > 0) return;
                if (el === window) {
                    if (document.body) document.body.style.overflow = previousOverflow;
                } else {
                    el.style.overflow = previousOverflow;
                }
                previousOverflow = '';
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
            return { isAppShellActive: isAppShellActive, updateActiveClass: updateActiveClass, getElement: getElement, getEventTarget: getElement, getScrollTop: getScrollTop, getViewportHeight: getViewportHeight, getScrollHeight: getScrollHeight, scrollTo: scrollToTop, lock: lock, unlock: unlock };
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
