# Theme Customization Guide

Complete guide to customizing the Videohub360 Theme using the WordPress Customizer and Color Presets.

## Table of Contents
1. [Quick Start](#quick-start)
2. [Design Token System](#design-token-system)
3. [Color Presets](#color-presets)
4. [Color Customization](#color-customization)
5. [Typography Settings](#typography-settings)
6. [Login Page Content](#login-page-content)
7. [Register Page Content](#register-page-content)
8. [Lost Password Page Content](#lost-password-page-content)
9. [Reset Password Page Content](#reset-password-page-content)
10. [Advanced Tips](#advanced-tips)

---

## Quick Start

### Applying a Color Preset (Fastest Way)

1. Go to **WP Admin → VH360 Theme → Appearance**
2. Find the **Color Presets** section at the top
3. Choose from 5 pre-made schemes:
   - **Default Blue** - Clean, professional blue theme
   - **Vibrant Red** - Bold, energetic red scheme
   - **Fresh Green** - Natural, calming green palette
   - **Royal Purple** - Elegant, creative purple theme
   - **Dark Mode** - Modern dark theme with bright accents
4. Click **Apply** on your chosen preset
5. Done! Your theme colors are instantly updated

### Customizing in WordPress Customizer

1. Go to **WP Admin → Appearance → Customize**
2. Open the new customization panels:
   - **Global Design → Colors** - Customize global color options
   - **Global Design → Typography** - Choose fonts and sizing
   - **Component Overrides → Authentication Pages** - Customize auth page colors (Login, Register, Lost Password, Reset Password)
   - **Component Overrides → Community Menu** - Customize community menu colors
3. Changes preview in real-time
4. Click **Publish** to save

---

## Design Token System

### What Are Design Tokens?

VideoHub360 Theme uses a design token system where all colors are defined once in the Customizer and flow throughout the entire theme. This means:

✅ Change one global color → Updates everywhere automatically  
✅ Consistent design across all pages and components  
✅ Easy to maintain and customize  

### Token Hierarchy

**Global Tokens** (Appearance → Customize → Global Design → Colors)
- Set your brand colors, text colors, backgrounds, etc.
- These apply theme-wide by default

**Component Tokens** (Appearance → Customize → Component Overrides)
- Optional: Override global colors for specific components
- Example: Make the Community Menu have different colors than the rest of your site
- Example: All authentication pages (Login, Register, Lost Password, Reset Password) share a unified color scheme for consistency

### Available Semantic Tokens

The theme uses semantic tokens that provide consistent naming across all components:

#### Surface Tokens (Backgrounds)
- `--surface-1` - Main page background
- `--surface-2` - Card backgrounds
- `--surface-3` - Hover/alternate backgrounds

#### Text Tokens
- `--text-1` - Primary text color
- `--text-2` - Secondary text (metadata, captions)

#### Border Tokens
- `--border-1` - Standard borders

#### Interactive Tokens
- `--ring-1` - Focus outlines

#### Status Colors
- `--success-color` - Success states
- `--error-color` - Error states
- `--warning-color` - Warning states
- `--info-color` - Info states

#### Brand Colors
- `--primary-color` - Primary brand color
- `--secondary-color` - Secondary brand color
- `--accent-color` - Accent color

### Migrating from Previous Versions

If you were using version 1.1.x or earlier with customized colors:

1. Your global colors are preserved automatically
2. Auth page colors are now consolidated - all auth pages (Login, Register, Lost Password, Reset Password) now share the same color scheme for consistency
3. If you had different colors for different auth pages, you'll need to choose one consistent scheme
4. All hardcoded colors have been replaced with design tokens, ensuring your customizations apply theme-wide

---

## Color Presets

### Available Presets

#### Default Blue
Professional and trustworthy. Perfect for corporate or educational sites.
- Primary: `#2563eb` (Blue)
- Secondary: `#1e40af` (Dark Blue)
- Success: `#10b981` (Green)

#### Vibrant Red
Bold and energetic. Great for entertainment or media platforms.
- Primary: `#dc2626` (Red)
- Secondary: `#b91c1c` (Dark Red)
- Light Background: `#fef2f2` (Light Red Tint)

#### Fresh Green
Natural and calming. Ideal for environmental or wellness sites.
- Primary: `#059669` (Green)
- Secondary: `#047857` (Dark Green)
- Light Background: `#f0fdf4` (Light Green Tint)

#### Royal Purple
Elegant and creative. Perfect for artistic or luxury brands.
- Primary: `#7c3aed` (Purple)
- Secondary: `#6d28d9` (Dark Purple)
- Light Background: `#faf5ff` (Light Purple Tint)

#### Dark Mode
Modern and sophisticated. Great for tech or creative platforms.
- Text: `#f9fafb` (Light text on dark backgrounds)
- Background: `#1f2937` (Dark Gray)
- Light Background: `#111827` (Darker Gray)

### How to Apply a Preset

**From Admin Panel:**
1. Navigate to **VH360 Theme → Appearance**
2. Scroll to **Color Presets** section
3. Click **Apply** on your chosen preset
4. See confirmation message
5. Visit your site to see changes

**Note:** After applying a preset, you can further customize individual colors in the Customizer.

---

## Color Customization

The theme features a comprehensive color system with all controls organized in a single, easy-to-use section.

Access: **Appearance → Customize → Colors**

### All Available Colors (28 Total)

All color controls are in one section, organized logically with clear descriptions:

**Brand Colors** - Core identity
**Text & Backgrounds** - Content and layout
**Page Headers** - Template headers
**Navigation** - Menu and links
**Hamburger Menu** - Mobile menu (5 controls)
**Buttons** - Interactive elements (4 controls)
**Status Messages** - Feedback colors
**Footer** - Footer area (4 controls)

### Brand Colors

#### Primary Color
- **Default:** `#2563eb` (Blue)
- **Used for:** Dashboard buttons, CTA buttons, active menu items, primary links
- **Recommendation:** Choose your main brand color

#### Secondary Color
- **Default:** `#1e40af` (Dark Blue)
- **Used for:** Button hover states, secondary UI elements
- **Recommendation:** Use a darker or complementary shade of your primary color

#### Accent Color
- **Default:** `#f59e0b` (Orange)
- **Used for:** Highlights, featured elements, attention-drawing elements
- **Recommendation:** Use a contrasting color to draw attention

### Text & Background Colors


#### Main Text Color
- **Default:** `#1f2937` (Dark Gray)
- **Used for:** Body text, headings, main content text
- **Recommendation:** High contrast with background for readability

#### Light Text Color
- **Default:** `#6b7280` (Medium Gray)
- **Used for:** Secondary text, metadata, descriptions, captions
- **Recommendation:** Lighter than main text but still readable

#### Main Background
- **Default:** `#ffffff` (White)
- **Used for:** Page background, card backgrounds, main content areas
- **Recommendation:** Usually white or very light color

#### Light Background
- **Default:** `#f9fafb` (Light Gray)
- **Used for:** Alternate sections, hover states, subtle backgrounds
- **Recommendation:** Slightly different from main background

#### Border Color
- **Default:** `#e5e7eb` (Light Gray)
- **Used for:** Dividers, card borders, input borders
- **Recommendation:** Subtle color that separates without overwhelming

### Navigation & Header Colors


#### Page Header Background Start
- **Default:** `#667eea` (Purple Blue)
- **Used for:** Template page headers (Activity, Members, Bulletins) - gradient start
- **Note:** Works with gradient end color for smooth transition

#### Page Header Background End
- **Default:** `#764ba2` (Purple)
- **Used for:** Template page headers - gradient end color
- **Note:** Creates gradient effect with start color

#### Hamburger Menu Background
- **Default:** `#ffffff` (White)
- **Used for:** Mobile hamburger menu panel background
- **Note:** NEW - Customize your mobile menu appearance

#### Hamburger Menu Text
- **Default:** `#1f2937` (Dark Gray)
- **Used for:** Hamburger menu item text color
- **Note:** NEW - Ensure good contrast with menu background

#### Hamburger Menu Hover BG
- **Default:** `#f9fafb` (Light Gray)
- **Used for:** Hamburger menu item hover background
- **Note:** NEW - Subtle highlight when hovering menu items

#### Hamburger Menu Active
- **Default:** `#2563eb` (Blue)
- **Used for:** Active/current menu item text color
- **Note:** NEW - Highlights the current page in menu

#### Hamburger Icon Color
- **Default:** `#1f2937` (Dark Gray)
- **Used for:** Hamburger menu toggle icon
- **Note:** NEW - The three-line icon that opens the menu

#### Main Nav Link Color
- **Default:** `#1f2937` (Dark Gray)
- **Used for:** Horizontal navigation menu links on desktop
- **Note:** NEW - Desktop navigation link color

### Button Colors


#### Button Background
- **Default:** `#2563eb` (Blue)
- **Used for:** Primary buttons, CTA buttons, submit buttons
- **Note:** NEW - Dedicated button background control

#### Button Text
- **Default:** `#ffffff` (White)
- **Used for:** Text color inside buttons
- **Note:** NEW - Ensure high contrast with button background

#### Button Hover Background
- **Default:** `#1e40af` (Dark Blue)
- **Used for:** Button background when hovering
- **Note:** NEW - Visual feedback on button interaction

#### Button Hover Text
- **Default:** `#ffffff` (White)
- **Used for:** Button text color when hovering
- **Note:** NEW - Maintains readability on hover

### Status & Alert Colors


#### Success Color
- **Default:** `#10b981` (Green)
- **Used for:** Success messages, confirmations, positive feedback
- **Recommendation:** Green shades typically indicate success

#### Error Color
- **Default:** `#ef4444` (Red)
- **Used for:** Error messages, validation errors, critical alerts
- **Recommendation:** Red shades typically indicate errors

#### Warning Color
- **Default:** `#f59e0b` (Orange)
- **Used for:** Warning messages, caution notices, important alerts
- **Recommendation:** Orange/Yellow shades for warnings

#### Info Color
- **Default:** `#6366f1` (Indigo)
- **Used for:** Info messages, notifications, helpful tips
- **Recommendation:** Blue shades typically indicate information

### Footer Colors


#### Footer Background
- **Default:** `#1f2937` (Dark Gray)
- **Used for:** Footer background color

#### Footer Text
- **Default:** `#f9fafb` (Light Gray)
- **Used for:** Footer text color

#### Footer Links
- **Default:** `#f9fafb` (Light Gray)
- **Used for:** Footer link color

#### Footer Link Hover
- **Default:** `#ffffff` (White)
- **Used for:** Footer link hover color

### Live Preview

All color changes preview instantly in the Customizer! No need to publish to see how they look.

### Color Tips

1. **Consistency:** Use your brand colors consistently across all sections
2. **Contrast:** Ensure sufficient contrast between text and backgrounds (WCAG AA minimum)
3. **Hierarchy:** Use lighter/darker shades to create visual hierarchy
4. **Testing:** Test your color choices on different devices and screen sizes
5. **Accessibility:** Consider colorblind users when choosing status colors

---

## Typography Settings

Access: **Appearance → Customize → Typography**

### Heading Font
Choose a font for all headings (H1-H6).

**Available Fonts:**
- System Font Stack (default) - Fast, no downloads
- Roboto
- Open Sans
- Lato
- Montserrat
- Raleway
- Poppins
- Nunito
- Playfair Display (serif)
- Merriweather (serif)
- PT Sans
- Source Sans Pro

### Body Font
Choose a font for body text, paragraphs, and UI elements.

**Recommendation:** Keep body font simple and readable (like Open Sans or System Font).

### Base Font Size
Control the base font size for your entire site.
- **Range:** 12px - 24px
- **Default:** 16px
- **Recommendation:** 16px for desktop, can go smaller for data-heavy sites

### Line Height
Control spacing between lines of text.
- **Range:** 1.0 - 2.5
- **Default:** 1.6
- **Recommendation:** 1.5-1.6 for body text, 1.2-1.4 for headings

### Performance Note
Google Fonts are only loaded if you select them. The System Font Stack option uses no external resources and is fastest.

---

## Login Page Content

Access: **Appearance → Customize → Login Page Content**

Customize the text shown on your login page template.

### Login Headline
- **Default:** "Welcome Back!"
- **Example:** "Log In to VideoHub", "Welcome to Our Community"

### Login Description
- **Default:** "Sign in to continue to your video platform and connect with your community."
- **Example:** "Access your videos, connect with members, and more."

### Features (3)
Displayed as icon + text combinations on the login page.

**Feature 1**
- **Default:** "Watch Videos"
- **Icon:** 📹

**Feature 2**
- **Default:** "Engage & Comment"
- **Icon:** 💬

**Feature 3**
- **Default:** "Connect with Others"
- **Icon:** 🌐

**Tip:** Keep features short (2-4 words each)

---

## Register Page Content

Access: **Appearance → Customize → Register Page Content**

Customize the text shown on your registration page template.

### Register Headline
- **Default:** "Join {site_name}"
- **Special:** Use `{site_name}` placeholder - automatically replaced with your site name
- **Example:** "Join {site_name} Today", "Become a {site_name} Member"

### Register Description
- **Default:** "Create your account and start your video journey today!"
- **Example:** "Sign up in seconds and unlock full access to our platform."

### Benefits (4)
Displayed as a checklist showing member benefits.

**Benefit 1**
- **Default:** "Upload and share your videos"

**Benefit 2**
- **Default:** "Comment and engage with content"

**Benefit 3**
- **Default:** "Connect with other members"

**Benefit 4**
- **Default:** "Build your profile and community"

**Tip:** Use action verbs and be specific about what members can do.

---

## Lost Password Page Content

Access: **Appearance → Customize → Lost Password Page Content**

Customize the text shown on your lost password request page template.

### Lost Password Headline
- **Default:** "Reset Your Password"
- **Example:** "Forgot Your Password?", "Password Recovery"

### Lost Password Description
- **Default:** "Enter your email address and we'll send you a link to reset your password."
- **Example:** "No worries! Enter your email and we'll help you regain access."

### Features (3)
Displayed with icons to reassure users about the recovery process.

**Feature 1**
- **Default:** "Quick Recovery" with 🔐 icon

**Feature 2**
- **Default:** "Secure Process" with ✉️ icon

**Feature 3**
- **Default:** "Easy Access" with ✓ icon

**Tip:** Focus on security and ease of use to build user confidence.

### Lost Password Page Design

Access: **Appearance → Customize → Lost Password Page Design**

Customize colors for your lost password page with 20+ color controls:

**Layout Colors:**
- Page Background Color
- Form Background Color

**Welcome Section:**
- Welcome Background Gradient Start
- Welcome Background Gradient End
- Welcome Text Color
- Welcome Heading Color
- Welcome Description Color

**Form Colors:**
- Form Title Color
- Label Color
- Input Border Color
- Input Focus Border Color
- Input Text Color
- Input Background Color

**Button Colors:**
- Button Background Gradient Start
- Button Background Gradient End
- Button Text Color

**Link Colors:**
- Link Color
- Link Hover Color

**Message Colors:**
- Error Message Background
- Error Message Text
- Error Message Border
- Success Message Background
- Success Message Text
- Success Message Border

**Utility Colors:**
- Secondary Text Color
- Required Asterisk Color

---

## Reset Password Page Content

Access: **Appearance → Customize → Reset Password Page Content**

Customize the text shown on your reset password page template.

### Reset Password Headline
- **Default:** "Create New Password"
- **Example:** "Set Your New Password", "Password Reset"

### Reset Password Description
- **Default:** "Enter a new password for your account. Make sure it's strong and secure."
- **Example:** "Choose a strong password to protect your account."

### Features (3)
Displayed with icons to highlight security aspects.

**Feature 1**
- **Default:** "Secure Link" with 🔒 icon

**Feature 2**
- **Default:** "One-Time Use" with ⏱️ icon

**Feature 3**
- **Default:** "Instant Access" with ✓ icon

**Tip:** Emphasize security and the simplicity of the process.

### Reset Password Page Design

Access: **Appearance → Customize → Reset Password Page Design**

Customize colors for your reset password page with 20+ color controls:

**Layout Colors:**
- Page Background Color
- Form Background Color

**Welcome Section:**
- Welcome Background Gradient Start
- Welcome Background Gradient End
- Welcome Text Color
- Welcome Heading Color
- Welcome Description Color

**Form Colors:**
- Form Title Color
- Label Color
- Input Border Color
- Input Focus Border Color
- Input Text Color
- Input Background Color

**Button Colors:**
- Button Background Gradient Start
- Button Background Gradient End
- Button Text Color

**Link Colors:**
- Link Color
- Link Hover Color

**Message Colors:**
- Error Message Background
- Error Message Text
- Error Message Border
- Success Message Background
- Success Message Text
- Success Message Border

**Utility Colors:**
- Secondary Text Color
- Required Asterisk Color

---

## Advanced Tips

### Creating Custom Color Schemes

1. Start with a preset that's close to your vision
2. Open the Customizer and adjust individual colors
3. Test contrast for accessibility (use a contrast checker)
4. Consider dark/light theme consistency

### Color Accessibility

Ensure good contrast ratios:
- **Normal text:** 4.5:1 minimum
- **Large text:** 3:1 minimum
- **Interactive elements:** Clear visual distinction

### Typography Best Practices

1. **Limit fonts:** Use 1-2 font families maximum
2. **Pair wisely:** Serif heading + sans-serif body works well
3. **Test readability:** Check on multiple devices
4. **Consider branding:** Match fonts to your brand identity

### Performance Optimization

1. **System fonts:** Fastest option, no external requests
2. **Google Fonts:** Limit to 2 font families if using
3. **Font weights:** Only load weights you'll use (done automatically)

### Form Content Strategy

1. **Be concise:** Keep text scannable
2. **Highlight value:** Focus on benefits, not features
3. **Use placeholders:** The `{site_name}` placeholder personalizes text
4. **Match tone:** Align with your brand voice

### Branding Consistency

All customization options work together:
1. Choose brand colors with presets
2. Select brand fonts
3. Customize form content with brand messaging
4. Test across your site for consistency

---

## Troubleshooting

### Colors Not Updating
- Clear browser cache
- Check if using a caching plugin (clear its cache)
- Ensure you clicked "Publish" in Customizer

### Fonts Not Loading
- Check internet connection
- Try a different browser
- Clear browser cache
- Switch to System Font temporarily

### Preset Not Applying
- Ensure you're logged in as administrator
- Check browser console for errors
- Verify theme is active and up to date

### Text Not Showing on Auth Pages
- Confirm you're viewing the correct template (Login or Register)
- Check theme_mod values in Customizer
- Ensure templates are assigned to pages

---

## Support

For additional help with customization:
1. Check theme documentation
2. Visit support forum
3. Contact theme author

**Version:** 1.1.0+
