/* global VH360_PWA */

// Optional push providers must not be imported by the always-on first-party PWA worker.

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
  const p = decodeURIComponent(url.pathname || '');

  // Never cache admin/auth/REST/ajax or highly personalized commerce/community areas.
  if (p.startsWith('/wp-admin') || p.startsWith('/wp-login.php')) return true;
  if (p.includes('/wp-json') || p.includes('/admin-ajax.php')) return true;
  if (/(^|\/)(dashboard|service|my-account|account|cart|checkout|messages|notifications|login|logout|register|members|settings|billing|subscription|subscriptions|orders|payment-methods)(\/|$)/i.test(p)) return true;
  if (/(^|\/)(live|video|videos|watch)(\/|$)/i.test(p)) return true;
  if (isVh360NetworkOnlyPath(p) || isVh360NetworkOnlyQuery(url)) return true;
  // Player-critical assets must be fetched through the network, never an old SW cache.
  if (/\/(?:videohub360|videohub360-core)\/assets\/(?:js\/(?:frontend-agora|view-layout-manager|livestream|frontend|simplified-mobile-controls|video-quality-manager|unified-settings-manager)\.js|css\/(?:multi-view-layouts|frontend|simplified-mobile-controls)\.css)$/i.test(p)) return true;
  if (/agora|AgoraRTC|frontend-agora|agora-broadcaster/i.test(p)) return true;

  // Avoid preview, nonces, actions.
  const qp = url.searchParams;
  const badKeys = ['_wpnonce','nonce','action','preview','customize_changeset_uuid','customize_theme','loggedout','logout'];
  for (const k of badKeys) {
    if (qp.has(k)) return true;
  }

  return false;
}

function isVh360NetworkOnlyPath(pathname) {
  const path = `/${String(pathname || '').replace(/^\/+|\/+$/g, '')}/`;
  const routes = Array.isArray(VH360_PWA.networkOnlyPaths) ? VH360_PWA.networkOnlyPaths : [];
  return routes.some((route) => {
    const normalized = `/${String(route || '').replace(/^\/+|\/+$/g, '')}/`;
    return normalized !== '//' && (path === normalized || path.startsWith(normalized));
  });
}

function isVh360NetworkOnlyQuery(url) {
  const query = VH360_PWA.networkOnlyQueryVars || {};
  return Object.keys(query).some((key) => query[key] === true
    ? url.searchParams.has(key) && url.searchParams.get(key) !== ''
    : url.searchParams.get(key) === String(query[key]));
}

function isNavigationRequest(request) {
  return request.mode === 'navigate' || (request.headers.get('accept') || '').includes('text/html');
}

function normalizePath(url) {
  const u = new URL(url, VH360_PWA.homeOrigin);
  return u.pathname.replace(/\/+$/, '') || '/';
}

function isFastLaunchRequest(request) {
  if (!VH360_PWA.fastLaunch || !isNavigationRequest(request)) return false;
  const requestPath = normalizePath(request.url);
  const shellPath = normalizePath(VH360_PWA.launchShellUrl || '/vh360-launch.html');
  const launchMode = VH360_PWA.launchMode || 'shell';

  if (launchMode === 'shell') {
    return requestPath === shellPath;
  }

  if (launchMode === 'cached_start') {
    const startPath = normalizePath(VH360_PWA.startUrl || '/');
    return requestPath === startPath && !shouldBypass(request);
  }

  return false;
}

function isStaticAsset(request) {
  const url = new URL(request.url);
  const p = decodeURIComponent(url.pathname || '');
  return /\.(?:css|js|mjs|png|jpg|jpeg|gif|webp|svg|ico|woff|woff2|ttf|otf)$/.test(p);
}

self.addEventListener('install', (event) => {
  event.waitUntil((async () => {
    const cache = await caches.open(STATIC_CACHE);
    const precache = Array.isArray(VH360_PWA.precache) ? VH360_PWA.precache : [];
    await Promise.all(precache.map(async (url) => {
      try {
        await cache.add(url);
      } catch (e) {
        // Ignore individual precache failures so one bad URL does not abort SW install.
      }
    }));
    // Activate updated service workers immediately so versioned CSS/JS is not held behind an old worker.
    await self.skipWaiting();
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
  const cached = await cache.match(request);
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

async function cacheFirstWithBackgroundRefresh(request) {
  const pageCache = await caches.open(PAGE_CACHE);
  const staticCache = await caches.open(STATIC_CACHE);
  const cached = await pageCache.match(request, { ignoreSearch: true }) || await staticCache.match(request, { ignoreSearch: true });
  const refresh = fetch(request).then((res) => {
    if (res && res.ok && (res.type === 'basic' || res.type === 'default')) {
      pageCache.put(request, res.clone());
    }
    return res;
  }).catch(() => null);

  if (cached) {
    refresh.catch(() => null);
    return cached;
  }

  const network = await refresh;
  if (network) return network;
  return caches.match(VH360_PWA.offlineUrl);
}

async function staleWhileRevalidate(request) {
  const cache = await caches.open(PAGE_CACHE);
  const cached = await cache.match(request);
  const network = fetch(request).then((res) => {
    if (res && res.ok && VH360_PWA.strategy !== 'safe') {
      cache.put(request, res.clone());
    }
    return res;
  }).catch(() => null);
  return cached || await network || caches.match(VH360_PWA.offlineUrl);
}

self.addEventListener('fetch', (event) => {
  const req = event.request;
  if (shouldBypass(req)) {
    if (isNavigationRequest(req)) event.respondWith(fetch(req).catch(() => caches.match(VH360_PWA.offlineUrl)));
    return;
  }

  if (isStaticAsset(req)) {
    event.respondWith(cacheFirst(req));
    return;
  }

  if (isNavigationRequest(req)) {
    if (isFastLaunchRequest(req)) {
      event.respondWith(cacheFirstWithBackgroundRefresh(req));
      return;
    }
    if (VH360_PWA.strategy === 'balanced' || VH360_PWA.strategy === 'aggressive') {
      event.respondWith(staleWhileRevalidate(req));
      return;
    }
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
