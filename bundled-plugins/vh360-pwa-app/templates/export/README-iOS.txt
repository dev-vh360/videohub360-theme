VH360 PWA & App - iOS Wrapper Export Pack
==========================================

WHAT THIS PACKAGE CONTAINS
---------------------------
This export package prepares your Progressive Web App (PWA) for iOS app store
submission through a wrapper application. This is NOT a native iOS app - it's a
data export that helps you create a wrapper using external tools.

INCLUDED FILES:
- manifest.json: Your current PWA manifest snapshot
- icons/: All uploaded icons organized by size
- metadata-ios.json: App Store metadata in structured JSON format
- README-iOS.txt: This file with setup instructions

REQUIREMENTS
------------
To create an iOS app wrapper, you will need:

1. Apple Developer Program membership ($99/year)
   Sign up at: https://developer.apple.com/programs/

2. macOS computer with Xcode installed
   Download from: https://apps.apple.com/app/xcode/id497799835

3. Capacitor (recommended) or similar PWA-to-native tool
   Documentation: https://capacitorjs.com/

STEP-BY-STEP INSTRUCTIONS
--------------------------

Option A: Using Capacitor (Recommended)
1. Install Node.js (https://nodejs.org/) if not already installed
2. Create a new directory for your project
3. Run: npm install @capacitor/core @capacitor/cli @capacitor/ios
4. Run: npx cap init
5. Configure your app using the manifest.json data from this export
6. Copy icons to appropriate iOS asset catalog locations
7. Run: npx cap add ios
8. Run: npx cap open ios
9. Configure signing in Xcode with your Apple Developer account
10. Build and archive your app in Xcode
11. Submit to App Store Connect

Option B: Using PWABuilder
1. Visit https://www.pwabuilder.com/
2. Enter your PWA URL
3. Follow the wizard to generate an iOS package
4. Download and open the generated Xcode project
5. Configure signing and submit to App Store

IMPORTANT NOTES
---------------
- This export does NOT automatically create or submit an iOS app
- App Store submission happens entirely outside of WordPress
- You are responsible for following Apple's App Store Review Guidelines:
  https://developer.apple.com/app-store/review/guidelines/
- There are NO GUARANTEES that Apple will approve your app
- You must handle app updates, certificates, and provisioning profiles yourself
- Digital Asset Links verification is required for some features

METADATA USAGE
--------------
The metadata-ios.json file contains the app information you configured in WordPress.
Use this data when filling out your App Store Connect listing:
- app_title: Use as your App Store display name
- short_description: Use as subtitle (30 characters max)
- full_description: Use in your App Store description
- category: Select matching category in App Store Connect
- privacy_policy: Link to your privacy policy
- support_email: Your support contact email
- keywords: Use for App Store keyword optimization (100 characters max)

TROUBLESHOOTING
---------------
Common issues:

1. Build errors in Xcode:
   - Ensure all required icons are present and correctly sized
   - Check that bundle identifier is unique and properly formatted
   - Verify code signing is configured correctly

2. App Store rejection:
   - Review Apple's guidelines carefully
   - Ensure your PWA provides real value beyond just wrapping a website
   - Add native features where appropriate
   - Provide clear in-app functionality

3. Icon issues:
   - iOS requires specific icon sizes for different devices
   - Use the icon assets from this export as starting points
   - Generate a complete icon set using tools like:
     https://appicon.co/ or https://makeappicon.com/

SUPPORT AND RESOURCES
---------------------
- Capacitor Documentation: https://capacitorjs.com/docs
- Apple Developer Portal: https://developer.apple.com/
- App Store Connect: https://appstoreconnect.apple.com/
- PWABuilder: https://www.pwabuilder.com/
- Apple Human Interface Guidelines: https://developer.apple.com/design/

DISCLAIMER
----------
VH360 PWA & App provides this export as a convenience tool. We do not provide
support for native app development, App Store submission, or Apple Developer
Program issues. This plugin prepares data only - actual app creation and
submission is your responsibility.

For WordPress plugin support, visit the plugin documentation or support forum.

Export Date: This file was generated from your WordPress installation.
Check metadata-ios.json for the exact export timestamp.
