# VideoHub360 Privacy & Consent integration

VideoHub360 Core owns the shared Privacy & Consent system. The theme, Studio, PWA, Memberships, Community, and future bundled features should consume Core APIs instead of reading the consent cookie directly.

## Categories

- `necessary`: always active; authentication, security, account sessions, membership access, checkout/session requirements, core community functionality, and the consent cookie.
- `preferences`: persistent convenience settings such as video quality, layout choices, recent emojis, Studio mode, panel sizes, overlay workspace state, and PWA install-prompt timing.
- `analytics`: optional measurement, including VideoHub360 ad-click aggregate counters.
- `advertising`: personalized advertising, behavioral profiling, retargeting, cross-site advertising, advertising pixels, and future attribution/profile features.

Contextual advertising is separate from personalized advertising. Contextual VideoHub360 pre-roll, mid-roll, post-roll, and Activity Feed creative may display without advertising consent, but optional measurement still requires analytics consent.

## PHP API

Use:

```php
videohub360_has_consent( 'preferences' );
videohub360_has_consent( 'analytics' );
videohub360_has_consent( 'advertising' );
```

Register optional services:

```php
videohub360_register_consent_service(
    'my-service',
    array(
        'category'    => 'advertising',
        'label'       => 'My service',
        'description' => 'Loads optional advertising functionality.',
    )
);
```

Check service consent with `videohub360_has_service_consent( 'my-service' )`.

Hooks:

- `videohub360_consent_categories`
- `videohub360_consent_services`
- `videohub360_consent_state`
- `videohub360_consent_changed`
- `videohub360_consent_granted`
- `videohub360_consent_revoked`
- `videohub360_consent_script_handles`

The consent system is disabled by default. Disabled and Notice Only modes preserve optional behavior so sites may use another consent-management plugin.

## JavaScript API

When enabled, Core exposes `window.VH360Consent` site-wide:

```js
VH360Consent.has('preferences');
VH360Consent.has('analytics');
VH360Consent.has('advertising');
VH360Consent.hasService('activity-feed-ad-slot');
VH360Consent.getState();
VH360Consent.openPreferences();
VH360Consent.savePreferences({ preferences: true, analytics: false, advertising: false });
```

Listen for changes:

```js
document.addEventListener('vh360:consent-changed', function (event) {
  console.log(event.detail);
});
```

Global Privacy Control forces `advertising` false in PHP and JavaScript.

## Preference storage

Use `window.VH360Storage` for nonessential persistent preferences:

```js
VH360Storage.registerPreferenceKey('vh360_quality_prefs');
VH360Storage.setPreference('vh360_quality_prefs', JSON.stringify(value));
const value = VH360Storage.getPreference('vh360_quality_prefs', '{}');
VH360Storage.removePreference('vh360_quality_prefs');
```

With Preferences consent, the wrapper uses `localStorage`. Without Preferences consent, it uses an in-memory runtime store and removes registered VideoHub360 keys when preferences are revoked. Do not use this wrapper for authentication, authorization, entitlement, or access control.

## Script activation

Known optional services can output inert script markup:

```html
<script type="text/plain" data-vh360-consent-category="advertising" data-vh360-src="https://example.com/script.js"></script>
```

Core activates matching inert scripts after consent. It does not scan and rewrite every WordPress script or guarantee that unrelated third-party plugins obey consent. Register known handles through `videohub360_consent_script_handles`.

## Activity Feed advertising

The theme owns the Activity Feed widget area. The Customizer setting `vh360_activity_ad_privacy_type` classifies the ad slot as `contextual` or `personalized`.

- `contextual`: renders the existing widget output normally.
- `personalized`: does not render widget markup in the initial response until Advertising consent is available; Core provides an AJAX endpoint that re-checks consent before returning widget markup.

## PWA and Studio

PWA manifest, service worker, offline fallback, and installed navigation are necessary app functionality and are not gated in this pass. PWA install-prompt timing and persistent dismissal are preferences. Third-party push provider SDK initialization requires Preferences consent, while browser notification permission remains a separate explicit user action.

Studio production functionality, camera/microphone access, livestreaming, recording, scenes, and replay publishing must not depend on Preferences consent. Only nonessential persistent convenience state uses `VH360Storage`.

## Future advertising modules

Future advertising modules must decide whether a feature is contextual or personalized/tracking-based. Contextual display may run without Advertising consent. Measurement must check Analytics consent. Personalized ads, profiling, retargeting, pixels, and attribution services must check Advertising consent or register a service in the advertising category.
