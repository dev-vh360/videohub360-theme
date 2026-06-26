# VideoHub360 Agora Token Generation

This directory contains the official Agora Token Builder library for generating secure tokens for VideoHub360 livestreams.

## What's Included

- **Official Agora Token Builder**: Downloaded from https://github.com/AgoraIO/Tools/tree/master/DynamicKey/AgoraDynamicKey/php
- **RtcTokenBuilder.php**: Main class for generating RTC tokens
- **AccessToken.php**: Core token functionality
- **Additional Libraries**: Supporting files for complete token generation

## Integration

The VideoHub360 plugin automatically uses this library when:

1. A user clicks "Join Livestream" on an Agora-powered video
2. The frontend makes an AJAX request to `vh360_generate_agora_token`
3. The backend validates access, resolves the approved role, and generates a real token
4. The frontend receives the token and joins the Agora channel with the server-approved role

## Security Architecture

### App Certificate

- The App Certificate is required for production token generation.
- The browser never receives the App Certificate.
- If the certificate is not configured and token requirement is enabled (default), the endpoint returns an error instead of allowing tokenless access.

### Role Authorization

- `role=host` submitted by the browser is an **untrusted hint only**.
- The server independently decides whether the current visitor may publish using `resolve_agora_token_access()`.
- A normal audience user cannot receive a publisher token by manually posting `role=host`.
- The server-approved role is returned in the JSON response and the frontend treats it as authoritative.

### Host Passcode

- The actual stored passcode is **never sent to the browser** — not in the localized JS config, not in HTML, not in debug data, not in AJAX responses.
- The frontend only receives a boolean (`hostPasscodeRequired`) indicating whether a passcode is needed.
- When a user requests presenter access, they submit the passcode to the server which validates it before issuing a host token.
- Passcodes are stored hashed (using `wp_hash_password`) and validated with `wp_check_password` or `hash_equals` for legacy plain-text values.

### Membership and Appointment Access

- Membership access is enforced inside the token endpoint using `vh360_post_requires_membership()`, `vh360_user_has_active_membership()`, and `vh360_user_has_membership_plan()` — the same helpers used by the single-video template.
- Appointment room access is enforced using `vh360_can_user_join_appointment_room()` before token generation.
- Logged-out users cannot receive tokens for membership-protected livestreams.

### Rate Limiting

- All token requests are rate-limited (20 per minute per user/IP).
- Presenter/host access attempts (role=host or passcode submitted) are subject to a stricter limit (5 per minute) to prevent brute-force attacks.

### Tokenless Mode

- Tokenless mode is **for local testing only**.
- When the `vh360_agora_require_tokens` option is enabled (the default), the endpoint will return an error if no App Certificate is configured rather than allowing tokenless access.
- Tokenless mode can only be enabled by a site administrator explicitly disabling the token requirement in settings.

## Production Security Notes

- The browser may request host access, but the server decides the approved Agora role.
- The frontend must never receive the host passcode or App Certificate.
- Publishing is allowed only after a server-approved host token is issued and applied.
- `startPublishing()` must not elevate users to host by itself — it only publishes after authorization is already complete.
- A dedicated frontend flag (`hasServerApprovedPublishToken`) tracks whether the host token was successfully applied. `currentRole = 'host'` or `isPresenter = true` alone are not sufficient to publish.
- Tokenless mode is for local testing only and should remain disabled in production.

## Setup Required

To enable real token generation:

1. Go to [Agora Console](https://console.agora.io/)
2. Create or select your project
3. Copy the App ID and App Certificate
4. Add them to **VideoHub360 → Settings → Agora Settings**
5. Save settings

## Production Checklist

- [ ] App Certificate configured in settings
- [ ] `vh360_agora_require_tokens` is enabled (default)
- [ ] Host passcode (if used) is set via the admin UI (stored hashed)
- [ ] Membership plans configured if access restriction is needed

## Library Source

This library is automatically included with VideoHub360. Original source:
https://github.com/AgoraIO/Tools/tree/master/DynamicKey/AgoraDynamicKey/php

## Support

For VideoHub360-specific issues, contact the VideoHub360 team.
For Agora Token Builder issues, refer to the official Agora documentation.
