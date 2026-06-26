VH360 PWA & App - Android Wrapper Export Pack
==============================================

WHAT THIS PACKAGE CONTAINS
---------------------------
This export package prepares your Progressive Web App (PWA) for Google Play Store
submission through a Trusted Web Activity (TWA) wrapper. This is NOT a native
Android app - it's a data export that helps you create a wrapper using external tools.

INCLUDED FILES:
- manifest.json: Your current PWA manifest snapshot
- icons/: All uploaded icons organized by size
- metadata-android.json: Play Store metadata in structured JSON format
- README-Android.txt: This file with setup instructions

REQUIREMENTS
------------
To create an Android app wrapper, you will need:

1. Google Play Console account ($25 one-time registration fee)
   Sign up at: https://play.google.com/console/signup

2. One of the following tools:
   - PWABuilder (recommended for beginners)
   - Bubblewrap CLI (for advanced users)
   - Android Studio (for custom development)

3. Digital Asset Links verification (required for TWA)
   Your website must serve a verification file

STEP-BY-STEP INSTRUCTIONS
--------------------------

Option A: Using PWABuilder (Recommended)
1. Visit https://www.pwabuilder.com/
2. Enter your PWA URL
3. Click "Build My PWA"
4. Select Android as the platform
5. Configure your app settings using data from metadata-android.json
6. Download the generated Android package (AAB file)
7. Upload the AAB file to Google Play Console
8. Complete the Play Store listing with your metadata
9. Submit for review

Option B: Using Bubblewrap CLI
1. Install Node.js (https://nodejs.org/) if not already installed
2. Install Bubblewrap: npm install -g @bubblewrap/cli
3. Run: bubblewrap init --manifest [your-manifest-url]
4. Follow the prompts to configure your app
5. Run: bubblewrap build
6. Sign your APK/AAB with your keystore
7. Upload to Google Play Console

Option C: Using Android Studio
1. Install Android Studio from https://developer.android.com/studio
2. Create a new Android project with TWA template
3. Configure AndroidManifest.xml with your PWA details
4. Add icons to res/drawable folders
5. Configure Digital Asset Links
6. Build signed AAB
7. Upload to Google Play Console

DIGITAL ASSET LINKS SETUP (REQUIRED)
-------------------------------------
For TWA to work properly, you MUST verify domain ownership:

1. Create a file named "assetlinks.json"
2. Place it at: https://yourdomain.com/.well-known/assetlinks.json
3. The file must contain your app's signing certificate fingerprint
4. PWABuilder can generate this file for you automatically
5. Verify it's accessible publicly (no authentication required)

Example assetlinks.json structure:
[{
  "relation": ["delegate_permission/common.handle_all_urls"],
  "target": {
    "namespace": "android_app",
    "package_name": "com.example.yourapp",
    "sha256_cert_fingerprints": ["YOUR_SHA256_FINGERPRINT"]
  }
}]

IMPORTANT NOTES
---------------
- This export does NOT automatically create or submit an Android app
- Play Store submission happens entirely outside of WordPress
- You are responsible for following Google Play's policies:
  https://play.google.com/about/developer-content-policy/
- There are NO GUARANTEES that Google will approve your app
- You must manage signing keys securely (losing them means you cannot update your app)
- TWAs require HTTPS and a valid SSL certificate
- Your PWA must meet quality standards for TWA (Lighthouse scores matter)

METADATA USAGE
--------------
The metadata-android.json file contains the app information you configured in WordPress.
Use this data when filling out your Play Console listing:

- app_title: Use as your app name (max 30 characters)
- short_description: Use as short description (max 80 characters)
- full_description: Use as full description (max 4000 characters)
- category: Select matching category in Play Console
- privacy_policy: Required URL to your privacy policy
- support_email: Your developer contact email (required)
- keywords: Used internally; Play Store doesn't have a keywords field

SIGNING KEYS (CRITICAL)
------------------------
When building your Android app, you'll generate a signing key (keystore):

1. NEVER lose your keystore file or password
2. Store backups in multiple secure locations
3. If lost, you CANNOT update your app (must republish as new app)
4. Consider using Google Play App Signing for additional security
5. Keep your keystore separate from your source code

TROUBLESHOOTING
---------------
Common issues:

1. Digital Asset Links verification fails:
   - Ensure assetlinks.json is at the exact URL (with /.well-known/)
   - Check that the file is publicly accessible (test in incognito)
   - Verify SHA256 fingerprint matches your signing certificate
   - File must be valid JSON (use JSONLint to validate)

2. Build errors:
   - Ensure Node.js and required tools are installed
   - Check that manifest.json is valid
   - Verify all icon sizes are correct
   - Update Bubblewrap/PWABuilder to latest version

3. Play Store rejection:
   - Ensure your PWA is fully functional on mobile
   - Add a privacy policy page
   - Provide clear app functionality description
   - Meet minimum functionality requirements (no "thin" apps)
   - Ensure content complies with Google's policies

4. TWA opens in browser instead of fullscreen:
   - Check Digital Asset Links verification status
   - Use Chrome's TWA verification tool: chrome://flags/#enable-twa-debugging
   - Verify package name and SHA256 match exactly

TESTING YOUR TWA
----------------
Before submitting to Play Store:

1. Test on multiple Android devices
2. Verify offline functionality
3. Check that updates work correctly
4. Test app icon appears correctly on home screen
5. Ensure no browser UI elements appear (address bar, etc.)
6. Test deep linking and navigation

SUPPORT AND RESOURCES
---------------------
- PWABuilder: https://www.pwabuilder.com/
- Bubblewrap Documentation: https://github.com/GoogleChromeLabs/bubblewrap
- Google Play Console: https://play.google.com/console/
- Digital Asset Links Guide: https://developers.google.com/digital-asset-links
- TWA Documentation: https://developer.chrome.com/docs/android/trusted-web-activity/
- Android Studio: https://developer.android.com/studio

QUALITY CHECKLIST
-----------------
Before publishing, ensure your PWA:
- [ ] Loads quickly (< 3 seconds)
- [ ] Works offline or shows meaningful offline page
- [ ] Is responsive on all screen sizes
- [ ] Has valid SSL certificate (HTTPS)
- [ ] Passes Lighthouse PWA audit
- [ ] Has all required icons (192px, 512px minimum)
- [ ] Manifest is properly configured
- [ ] Service worker is registered and functioning

DISCLAIMER
----------
VH360 PWA & App provides this export as a convenience tool. We do not provide
support for native app development, Play Store submission, or Google Play Console
issues. This plugin prepares data only - actual app creation and submission is
your responsibility.

For WordPress plugin support, visit the plugin documentation or support forum.

Export Date: This file was generated from your WordPress installation.
Check metadata-android.json for the exact export timestamp.
