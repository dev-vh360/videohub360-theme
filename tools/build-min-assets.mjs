import { execFile } from 'node:child_process';
import { promisify } from 'node:util';
import { readFile, stat, writeFile } from 'node:fs/promises';
import { existsSync } from 'node:fs';
import path from 'node:path';
import process from 'node:process';

const checkMode = process.argv.includes('--check');
const execFileAsync = promisify(execFile);
let terserMinifyPromise;

const cssSources = [
  "assets/admin/css/theme-admin.css",
  "assets/css/activity-feed.css",
  "assets/css/admin/event-gallery-admin.css",
  "assets/css/auth-pages.css",
  "assets/css/avatar-cropper.css",
  "assets/css/blog-archive.css",
  "assets/css/bulletin-dashboard.css",
  "assets/css/bulletins.css",
  "assets/css/business.css",
  "assets/css/channel.css",
  "assets/css/client.css",
  "assets/css/comments-youtube-style.css",
  "assets/css/community-menu.css",
  "assets/css/community-uploads.css",
  "assets/css/course-author.css",
  "assets/css/dashboard.css",
  "assets/css/direct-messages.css",
  "assets/css/elementor-posts-widget.css",
  "assets/css/elementor-preview.css",
  "assets/css/events-dashboard.css",
  "assets/css/events.css",
  "assets/css/feed-ui-enhancements.css",
  "assets/css/gallery-dashboard.css",
  "assets/css/gallery.css",
  "assets/css/groups.css",
  "assets/css/header-layout.css",
  "assets/css/live-room.css",
  "assets/css/live-rooms.css",
  "assets/css/members-directory.css",
  "assets/css/mobile-bottom-nav.css",
  "assets/css/mobile-orientation-lock.css",
  "assets/css/notifications-dashboard.css",
  "assets/css/notifications.css",
  "assets/css/profile-fields.css",
  "assets/css/profiles.css",
  "assets/css/search-bar-centered.css",
  "assets/css/sidebar-layout.css",
  "assets/css/user-menu.css",
  "assets/css/utilities.css",
  "assets/css/videohub360-integration.css",
  "assets/css/widgets.css",
  "assets/css/woocommerce.css",
  "bundled-plugins/vh360-pwa-app/assets/admin/appstore-admin.css",
  "bundled-plugins/vh360-pwa-app/assets/admin/push-admin.css",
  "bundled-plugins/vh360-pwa-app/assets/admin/push-native-admin.css",
  "bundled-plugins/vh360-pwa-app/assets/admin/push-tokens.css",
  "bundled-plugins/vh360-pwa-app/assets/admin/pwa-admin.css",
  "bundled-plugins/vh360-pwa-app/assets/public/push-subscribe.css",
  "bundled-plugins/vh360-pwa-app/assets/public/pwa-install-ui.css",
  "bundled-plugins/vh360-pwa-app/assets/public/pwa-runtime.css",
  "bundled-plugins/videohub360-core/assets/css/admin-dashboard.css",
  "bundled-plugins/videohub360-core/assets/css/admin-shortcodes.css",
  "bundled-plugins/videohub360-core/assets/css/admin.css",
  "bundled-plugins/videohub360-core/assets/css/chat.css",
  "bundled-plugins/videohub360-core/assets/css/course-admin.css",
  "bundled-plugins/videohub360-core/assets/css/course-mode.css",
  "bundled-plugins/videohub360-core/assets/css/frontend.css",
  "bundled-plugins/videohub360-core/assets/css/hero.css",
  "bundled-plugins/videohub360-core/assets/css/live-player.css",
  "bundled-plugins/videohub360-core/assets/css/moderation.css",
  "bundled-plugins/videohub360-core/assets/css/multi-view-layouts.css",
  "bundled-plugins/videohub360-core/assets/css/reactions-playlists.css",
  "bundled-plugins/videohub360-core/assets/css/simplified-mobile-controls.css",
  "bundled-plugins/videohub360-core/assets/css/single-video.css",
  "bundled-plugins/videohub360-core/assets/css/variables.css",
  "bundled-plugins/videohub360-core/assets/css/videohub360-categories.css",
  "bundled-plugins/videohub360-memberships/assets/admin/giving-admin.css",
  "bundled-plugins/videohub360-memberships/assets/admin/membership-members.css",
  "bundled-plugins/videohub360-memberships/assets/admin/membership-plans.css",
  "bundled-plugins/videohub360-memberships/assets/css/membership-dashboard.css",
  "bundled-plugins/videohub360-memberships/assets/css/membership-gate.css",
  "bundled-plugins/videohub360-memberships/assets/frontend/giving.css",
  "bundled-plugins/videohub360-memberships/assets/frontend/pricing-toggle.css",
  "bundled-plugins/videohub360-starter-sites/admin/assets/css/starter-sites-admin.css",
  "style.css"
];

const jsSources = [
  "assets/admin/js/theme-admin.js",
  "assets/js/activity-feed.js",
  "assets/js/admin/event-gallery-admin.js",
  "assets/js/admin/gallery-admin.js",
  "assets/js/avatar-cropper.js",
  "assets/js/blog-archive.js",
  "assets/js/bulletin-dashboard.js",
  "assets/js/bulletins.js",
  "assets/js/business-booking.js",
  "assets/js/community-menu-toggle.js",
  "assets/js/community.js",
  "assets/js/create-post.js",
  "assets/js/customizer-preview.js",
  "assets/js/customizer.js",
  "assets/js/dashboard.js",
  "assets/js/direct-messages.js",
  "assets/js/events-dashboard.js",
  "assets/js/events.js",
  "assets/js/follow-system.js",
  "assets/js/gallery-dashboard.js",
  "assets/js/gallery-photoswipe.js",
  "assets/js/gallery.js",
  "assets/js/header-navigation.js",
  "assets/js/live-rooms.js",
  "assets/js/members-directory.js",
  "assets/js/mobile-bottom-nav.js",
  "assets/js/notification-preferences.js",
  "assets/js/notifications-dashboard.js",
  "assets/js/notifications.js",
  "assets/js/profile.js",
  "assets/js/push-notifications.js",
  "assets/js/search-bar-centered.js",
  "assets/js/theme.js",
  "assets/js/user-menu.js",
  "assets/js/vh360-mentions.js",
  "assets/js/vh360-pwa-link-same-window.js",
  "assets/js/wp-comments-handler.js",
  "bundled-plugins/vh360-pwa-app/assets/admin/appstore-admin.js",
  "bundled-plugins/vh360-pwa-app/assets/admin/push-admin.js",
  "bundled-plugins/vh360-pwa-app/assets/admin/push-native-admin.js",
  "bundled-plugins/vh360-pwa-app/assets/admin/push-tokens.js",
  "bundled-plugins/vh360-pwa-app/assets/admin/pwa-admin.js",
  "bundled-plugins/vh360-pwa-app/assets/public/push-public.js",
  "bundled-plugins/vh360-pwa-app/assets/public/pwa-device-tools.js",
  "bundled-plugins/vh360-pwa-app/assets/public/pwa-install-ui.js",
  "bundled-plugins/vh360-pwa-app/assets/public/pwa-runtime.js",
  "bundled-plugins/videohub360-core/assets/js/admin-dashboard.js",
  "bundled-plugins/videohub360-core/assets/js/admin-shortcodes.js",
  "bundled-plugins/videohub360-core/assets/js/admin.js",
  "bundled-plugins/videohub360-core/assets/js/agora-player.js",
  "bundled-plugins/videohub360-core/assets/js/chat.js",
  "bundled-plugins/videohub360-core/assets/js/course-admin.js",
  "bundled-plugins/videohub360-core/assets/js/course-term-media.js",
  "bundled-plugins/videohub360-core/assets/js/frontend-core.js",
  "bundled-plugins/videohub360-core/assets/js/hero.js",
  "bundled-plugins/videohub360-core/assets/js/livestream.js",
  "bundled-plugins/videohub360-core/assets/js/moderation.js",
  "bundled-plugins/videohub360-core/assets/js/playlists.js",
  "bundled-plugins/videohub360-core/assets/js/reactions.js",
  "bundled-plugins/videohub360-core/assets/js/runtime.js",
  "bundled-plugins/videohub360-core/assets/js/simplified-mobile-controls.js",
  "bundled-plugins/videohub360-core/assets/js/single-actions.js",
  "bundled-plugins/videohub360-core/assets/js/unified-settings-manager.js",
  "bundled-plugins/videohub360-core/assets/js/video-player.js",
  "bundled-plugins/videohub360-core/assets/js/video-quality-manager.js",
  "bundled-plugins/videohub360-core/assets/js/view-layout-manager.js",
  "bundled-plugins/videohub360-memberships/assets/admin/giving-admin.js",
  "bundled-plugins/videohub360-memberships/assets/admin/membership-members.js",
  "bundled-plugins/videohub360-memberships/assets/admin/membership-plans.js",
  "bundled-plugins/videohub360-memberships/assets/frontend/giving.js",
  "bundled-plugins/videohub360-memberships/assets/frontend/pricing-toggle.js",
  "bundled-plugins/videohub360-memberships/assets/js/membership-manage.js",
  "bundled-plugins/videohub360-starter-sites/admin/assets/js/starter-sites-admin.js"
];

function minPath(source) {
  return source.replace(/\.(css|js)$/u, '.min.$1');
}

function minifyCssText(input) {
  return input
    .replace(/\/\*[\s\S]*?\*\//gu, '')
    .replace(/\s+/gu, ' ')
    .replace(/\s*([{}:;,>+~])\s*/gu, '$1')
    .replace(/;\}\s*/gu, '}')
    .trim();
}

async function minifyCss(source) {
  return minifyCssText(await readFile(source, 'utf8'));
}

async function loadTerserMinify() {
  if (!terserMinifyPromise) {
    terserMinifyPromise = import('terser')
      .then((module) => module.minify)
      .catch(() => null);
  }

  return terserMinifyPromise;
}

function preserveJsSource(input) {
  return input
    .split(/\r?\n/u)
    .map((line) => line.trimEnd())
    .join('\n')
    .trim();
}

async function minifyJs(source) {
  const code = await readFile(source, 'utf8');
  const terserMinify = await loadTerserMinify();

  if (!terserMinify) {
    console.warn(`Terser is not installed; preserving readable JS for ${source}. Run npm install before release builds.`);
    return preserveJsSource(code);
  }

  const result = await terserMinify(code, {
    compress: false,
    mangle: false,
    format: {
      comments: false,
    },
  });

  if (!result.code) {
    throw new Error(`Terser produced empty output for ${source}`);
  }

  return result.code;
}

async function validateJsSyntax(file) {
  await execFileAsync(process.execPath, ['--check', file]);
}

async function handleSource(source, minifier) {
  if (source.includes('.min.')) return { skipped: true, source };
  if (!existsSync(source)) throw new Error(`Missing source asset: ${source}`);

  const target = minPath(source);
  const output = (await minifier(source)).trimEnd() + '\n';

  if (checkMode) {
    if (!existsSync(target)) throw new Error(`Missing minified asset: ${target}`);
    const [current, sourceStat, targetStat] = await Promise.all([readFile(target, 'utf8'), stat(source), stat(target)]);
    if (targetStat.mtimeMs + 1 < sourceStat.mtimeMs) throw new Error(`Stale minified asset: ${target}`);
    if (current !== output) throw new Error(`Outdated minified asset: ${target}`);
    if (target.endsWith('.min.js')) await validateJsSyntax(target);
    return { checked: true, source, target };
  }

  await writeFile(target, output);
  if (target.endsWith('.min.js')) await validateJsSyntax(target);
  return { generated: true, source, target };
}

async function main() {
  const results = [];
  for (const source of cssSources) results.push(await handleSource(source, minifyCss));
  for (const source of jsSources) results.push(await handleSource(source, minifyJs));

  const verb = checkMode ? 'Checked' : 'Generated';
  const count = results.filter((result) => result.checked || result.generated).length;
  console.log(`${verb} ${count} minified assets.`);
  for (const result of results) {
    if (result.target) console.log(`- ${result.target}`);
  }
}

main().catch((error) => {
  console.error(error.message || error);
  process.exit(1);
});
