# Lead Capture / Newsletter CTA

Videohub360 Lead Capture is a lightweight display and placement layer for newsletter, lead-generation, and marketing signup calls to action. It lets a site owner show a theme-matched signup CTA as an inline block, popup, floating button, or footer banner and render the actual form through a shortcode or safe embed HTML.

**Videohub360 handles the display and placement of the signup CTA. Your form plugin or email marketing provider handles storing subscribers, managing lists, unsubscribe compliance, and sending marketing emails.**

## What the feature does

- Adds **Customizer → Marketing → Lead Capture** settings.
- Displays a configured CTA on selected frontend locations.
- Supports form/plugin shortcodes such as Contact Form 7, MC4WP, Brevo, ConvertKit, or other providers.
- Supports safe HTML form embeds for providers that supply standard form markup.
- Provides display modes for:
  - Inline block
  - Popup modal
  - Floating button that opens a modal
  - Footer banner
- Supports optional dismissal frequency using browser `localStorage`.
- Can hide the CTA from logged-in users.
- Can exclude specific pages by page ID or slug.

## What the feature does not do

Lead Capture is not a native email marketing platform. It does **not**:

- Store subscriber records in Videohub360.
- Create mailing lists.
- Send bulk newsletters or marketing campaigns.
- Manage unsubscribes, bounces, or compliance workflows.
- Provide campaign analytics.
- Create custom database tables for leads.
- Execute third-party script embeds in the Customizer field.

Script tags are stripped from safe embed HTML in this version. Prefer provider shortcodes or standard HTML form embeds.

## Create a Contact Form 7 signup form

1. Install and activate **Contact Form 7** if it is not already active.
2. Go to **Contact → Add New** in WordPress admin.
3. Create a simple signup form, for example with an email field and submit button.
4. Configure the Mail tab so submissions are sent to the desired address.
5. Save the form and copy the Contact Form 7 shortcode, such as `[contact-form-7 id="123" title="Newsletter Signup"]`.

Contact Form 7 sends form notifications but does not store submitted messages by itself. If you want to save Contact Form 7 submissions inside WordPress, consider installing **Flamingo** as a recommended companion plugin.

## Paste the shortcode into the Customizer

1. Go to **Appearance → Customize → Marketing → Lead Capture**.
2. Enable **Lead Capture CTA**.
3. Set **Form Source** to **Shortcode**.
4. Paste the Contact Form 7 or provider shortcode into **Form Shortcode**.
5. Choose the display mode and locations.
6. Publish the Customizer changes.

If a shortcode is configured but the plugin that provides it is inactive, visitors will not see a broken shortcode. Administrators can see a frontend notice to help diagnose the missing shortcode provider.

## Use a Mailchimp, Brevo, ConvertKit, or MC4WP form

You can use either a shortcode or safe HTML embed depending on the provider/plugin:

- **MC4WP / Mailchimp for WordPress:** create the form in the plugin, then paste the generated shortcode into **Form Shortcode**.
- **Brevo / ConvertKit / similar plugins:** paste the plugin shortcode if one is available.
- **Provider HTML embeds:** choose **Safe embed HTML** and paste the provider's standard HTML form markup.

For newsletters, automations, subscriber storage, list segmentation, unsubscribe compliance, and campaign sending, use a dedicated email marketing platform such as Mailchimp, Brevo, ConvertKit, or another provider.

## Popup dismissal and frequency

When **Hide after dismissal** is enabled, the popup, floating CTA, or footer banner stores a dismissal timestamp in the visitor's browser using `localStorage`. The **Dismissal Frequency (days)** setting controls how long the CTA stays hidden before it can appear again.

Examples:

- `7` days: a dismissed CTA can show again after one week.
- `0` days: a dismissed CTA remains hidden for the current stored dismissal state.

If `localStorage` is unavailable in the browser, the CTA still closes for the current page view but may not persist the dismissal across page loads.

## Hide for logged-in users

Enable **Hide for logged-in users** if the signup CTA should target visitors only. When this option is enabled, authenticated users will not see the Lead Capture CTA.

## Choose display locations

Use the location checkboxes in **Customizer → Marketing → Lead Capture** to decide where the CTA can display:

- Homepage
- Activity Feed
- Members Directory
- Course Catalog
- Single Video
- Single Blog Post
- WooCommerce Shop
- WooCommerce Product
- All Site

The **All Site** option overrides the individual location choices. Use **Excluded Pages** for comma-separated page IDs or slugs where the CTA should never appear.
