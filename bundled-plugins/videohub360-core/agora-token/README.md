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
3. The backend loads `RtcTokenBuilder.php` and generates a real token
4. The frontend receives the token and joins the Agora channel

## Security Features

- **Fresh Tokens**: New token generated for each join request
- **1-Hour Expiry**: Tokens automatically expire for security
- **Role-Based**: Different tokens for hosts vs audience
- **Server-Side Only**: App Certificate never exposed to client

## Setup Required

To enable real token generation:

1. Go to [Agora Console](https://console.agora.io/)
2. Create or select your project
3. Copy the App ID and App Certificate
4. Add them to **VideoHub360 → Settings → Agora Settings**
5. Save settings

## Development Mode

Without an App Certificate configured:
- Setup instructions are provided to users
- Tokenless mode can be used for testing
- All UI functionality still works

## Production Mode

With App Certificate configured:
- Real Agora tokens are generated
- Secure channel access
- Full production features enabled

## Library Source

This library is automatically included with VideoHub360. Original source:
https://github.com/AgoraIO/Tools/tree/master/DynamicKey/AgoraDynamicKey/php

## Support

For VideoHub360-specific issues, contact the VideoHub360 team.
For Agora Token Builder issues, refer to the official Agora documentation.