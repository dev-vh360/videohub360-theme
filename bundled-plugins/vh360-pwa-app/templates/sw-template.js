/* global VH360_PWA */

// If push notifications are enabled, we merge OneSignal into this service worker.
// This avoids service-worker scope conflicts (only one SW can control '/').
// IMPORTANT: importScripts must run at initial evaluation time.
try {
  if (VH360_PWA && VH360_PWA.onesignal && VH360_PWA.onesignal.importUrl) {
    importScripts(VH360_PWA.onesignal.importUrl);
  }
} catch (e) {
  // Ignore OneSignal import failures so PWA caching still works.
}

const CACHE_PREFIX = 'vh360-pwa-';
const STATIC_CACHE = `${CACHE_PREFIX}static-${VH360_PWA.cacheVersion}`;
const PAGE_CACHE   = `${CACHE_PREFIX}pages-${VH360_PWA.cacheVersion}`;

function isSameOrigin(url) {
  try {
    const u = new URL(url);
    return u.origin === (new URL(VH360_PWA.homeOrigin)).origin;
  } catch (e) {
    return false;
  }
}

function shouldBypass(request) {
  if (request.method !== 'GET') return true;
  if (!isSameOrigin(request.url)) return true;

  const url = new URL(request.url);
  const p = url.pathname || '';

  // Never cache admin/auth/REST/ajax or highly personalized commerce/community areas.
  if (p.startsWith('/wp-admin') || p.startsWith('/wp-login.php')) return true;
  if (p.includes('/wp-json') || p.includes('/admin-ajax.php')) return true;
  if (/(^|\/)(dashboard|my-account|account|cart|checkout|messages|notifications|login|logout|register|members|settings)(\/|$)/i.test(p)) return true;

  // Avoid preview, nonces, actions.
  const qp = url.searchParams;
  const badKeys = ['_wpnonce','nonce','action','preview','customize_changeset_uuid','customize_theme','loggedout','logout'];
  for (const k of badKeys) {
    if (qp.has(k)) return true;
  }

  return false;
}

function isNavigationRequest(request) {
  return request.mode === 'navigate' || (request.headers.get('accept') || '').includes('text/html');
}

function isStaticAsset(request) {
  const url = new URL(request.url);
  const p = url.pathname || '';
  return /\.(?:css|js|mjs|png|jpg|jpeg|gif|webp|svg|ico|woff|woff2|ttf|otf)$/.test(p);
}

self.addEventListener('install', (event) => {
  event.waitUntil((async () => {
    const cache = await caches.open(STATIC_CACHE);
    const precache = Array.isArray(VH360_PWA.precache) ? VH360_PWA.precache : [];
    await cache.addAll(precache);
    // Keep updates controllable: we won't skipWaiting automatically.
  })());
});

self.addEventListener('activate', (event) => {
  event.waitUntil((async () => {
    const keys = await caches.keys();
    await Promise.all(keys.map((k) => {
      if (k.startsWith(CACHE_PREFIX) && !k.endsWith(VH360_PWA.cacheVersion)) {
        return caches.delete(k);
      }
      return Promise.resolve();
    }));
    await self.clients.claim();
  })());
});

async function cacheFirst(request) {
  const cache = await caches.open(STATIC_CACHE);
  const cached = await cache.match(request, { ignoreSearch: true });
  if (cached) return cached;
  const res = await fetch(request);
  if (res && res.ok) {
    cache.put(request, res.clone());
  }
  return res;
}

async function networkFirstWithFallback(request) {
  const cache = await caches.open(PAGE_CACHE);
  try {
    const res = await fetch(request);
    if (res && res.ok) {
      // Only cache navigations if strategy is not 'safe'.
      if (VH360_PWA.strategy !== 'safe') {
        cache.put(request, res.clone());
      }
    }
    return res;
  } catch (e) {
    const cached = await cache.match(request);
    if (cached) return cached;
    return caches.match(VH360_PWA.offlineUrl);
  }
}

self.addEventListener('fetch', (event) => {
  const req = event.request;
  if (shouldBypass(req)) return;

  if (isStaticAsset(req)) {
    event.respondWith(cacheFirst(req));
    return;
  }

  if (isNavigationRequest(req)) {
    event.respondWith(networkFirstWithFallback(req));
    return;
  }

  // Other GET same-origin: just go to network.
  event.respondWith(fetch(req));
});

self.addEventListener('message', (event) => {
  if (!event.data) return;
  if (event.data.type === 'VH360_PWA_SKIP_WAITING') {
    self.skipWaiting();
  }
});
