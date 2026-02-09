# Authentication Pages Setup Guide

## Overview

This theme includes beautiful, branded login and registration page templates with smart URL detection. No popups, no complex AJAX - just clean, WordPress-native authentication with a modern design.

## Features

✅ **Smart URL Detection** - Automatically finds pages using login/register templates  
✅ **WordPress Native** - Uses built-in WordPress authentication functions  
✅ **Mobile Responsive** - Beautiful on all devices  
✅ **Split-Screen Design** - Modern, professional appearance  
✅ **Security First** - Nonce verification, input sanitization, password validation  
✅ **Flexible** - Works even if pages are renamed or slugs changed  
✅ **Dark Mode Support** - Adapts to user preferences  

## Quick Setup

### Step 1: Create Login Page

1. Go to **Pages → Add New** in WordPress admin
2. Set the title to "Login" (or any name you prefer)
3. Select **Template: Login** from the Page Attributes dropdown
4. Set the slug to `login` (optional but recommended)
5. Click **Publish**

### Step 2: Create Registration Page

1. Go to **Pages → Add New** in WordPress admin
2. Set the title to "Register" (or any name you prefer)
3. Select **Template: Register** from the Page Attributes dropdown
4. Set the slug to `register` (optional but recommended)
5. Click **Publish**

### Step 3: Create Lost Password Page

1. Go to **Pages → Add New** in WordPress admin
2. Set the title to "Lost Password" (or any name you prefer)
3. Select **Template: Lost Password** from the Page Attributes dropdown
4. Set the slug to `lost-password` (optional but recommended)
5. Click **Publish**

### Step 4: Create Reset Password Page

1. Go to **Pages → Add New** in WordPress admin
2. Set the title to "Reset Password" (or any name you prefer)
3. Select **Template: Reset Password** from the Page Attributes dropdown
4. Set the slug to `reset-password` (optional but recommended)
5. Click **Publish**

### Step 5: Done!

That's it! The theme will automatically detect and link to your new pages:
- **Sign In** button → Links to your login page
- **Register** button → Links to your registration page (shown only when logged out)
- **Forgot Password?** link → Links to your lost password page
- Password reset emails → Link to your reset password page

## How It Works

### Smart URL Detection

The theme uses helper functions to automatically find pages with the correct templates:

```php
vh360_get_login_page_url()         // Returns URL to login page or fallback
vh360_get_register_page_url()      // Returns URL to register page or fallback
vh360_get_lost_password_page_url() // Returns URL to lost password page or fallback
vh360_get_reset_password_page_url() // Returns URL to reset password page or fallback
```

**Benefits:**
- Works even if you rename the pages
- Works even if you change the page slugs
- Falls back to custom page slugs if custom pages don't exist
- No hardcoded wp-login.php URLs anywhere

### What Happens If Pages Don't Exist?

The system gracefully falls back to slug-based URLs:
- Login button → Links to `/login/`
- Register button → Links to `/register/`
- Forgot Password link → Links to `/lost-password/`
- Password reset emails → Link to `/reset-password/`

**Note:** WordPress default login (wp-login.php) is completely removed from the user-facing experience. All authentication flows use custom templates.

## Page Features

### Login Page (`template-login.php`)

**Left Side (Welcome Section):**
- Site logo or name
- Welcome message
- Feature highlights:
  - Watch Videos
  - Engage & Comment
  - Connect with Others

**Right Side (Form):**
- Username or Email field
- Password field
- Remember Me checkbox
- Sign In button
- Links to:
  - Forgot Password
  - Create Account (if registration enabled)

**Features:**
- Uses WordPress native `wp_login_form()`
- Automatic redirect to dashboard after login
- Error messages for failed login attempts
- Logout success message support

### Registration Page (`template-register.php`)

**Left Side (Welcome Section):**
- Site logo or name
- Join message with site name
- Member benefits list:
  - Upload and share videos
  - Comment and engage
  - Connect with members
  - Build profile and community

**Right Side (Form):**
- Username field (required)
- Email Address field (required)
- Password field (required, minimum 8 characters)
- Terms of Service checkbox (required)
- Create Account button
- Link to Sign In page

**Features:**
- WordPress native user creation with `wp_insert_user()`
- Automatic login after successful registration
- Comprehensive error handling
- Form value preservation on errors (username and email only)
- Terms of Service validation

### Lost Password Page (`template-lost-password.php`)

**Left Side (Welcome Section):**
- Site logo or name
- Reset password headline
- Recovery features:
  - Quick Recovery
  - Secure Process
  - Easy Access

**Right Side (Form):**
- Username or Email Address field (required)
- Get New Password button
- Links to:
  - Back to Sign In
  - Create Account (if registration enabled)

**Features:**
- WordPress native `retrieve_password()` function
- Email validation
- User/email verification
- Success confirmation message
- Email with reset link sent automatically

### Reset Password Page (`template-reset-password.php`)

**Left Side (Welcome Section):**
- Site logo or name
- New password headline
- Security features:
  - Secure Link
  - One-Time Use
  - Instant Access

**Right Side (Form):**
- New Password field (required, minimum 8 characters)
- Confirm New Password field (required)
- Reset Password button
- Link to Sign In page

**Features:**
- WordPress native `check_password_reset_key()` and `reset_password()` functions
- Reset key validation (expiration and validity checks)
- Password strength validation
- Password match verification
- Success message with link to login
- Invalid/expired key error handling

## Security Features

The authentication pages include multiple security layers:

### Input Validation
- ✅ Nonce verification for CSRF protection
- ✅ POST method validation
- ✅ Required field validation
- ✅ Email format validation
- ✅ Password length validation (minimum 8 characters)
- ✅ Password confirmation matching
- ✅ Reset key validity and expiration checks
- ✅ Terms of Service acceptance validation

### Input Sanitization
- ✅ Username sanitization with `sanitize_user()`
- ✅ Email sanitization with `sanitize_email()`
- ✅ Output escaping with `esc_html()`, `esc_url()`, `esc_attr()`

### Security Best Practices
- ✅ No open redirect vulnerabilities (uses `get_permalink()`)
- ✅ No XSS vulnerabilities (proper escaping)
- ✅ Password whitespace preserved (intentional spaces allowed)
- ✅ Duplicate username/email detection
- ✅ Specific error messages for debugging

## Customization

### Changing the Redirect After Login

Edit `template-login.php` and modify the redirect URL:

```php
$redirect_to = home_url('/dashboard/');  // Change this
```

### Changing the Redirect After Registration

Edit `includes/auth-helpers.php` in the `vh360_handle_registration()` function:

```php
$redirect_to = home_url('/dashboard/');  // Change this
```

### Customizing the Design

The authentication pages use a dedicated stylesheet at `assets/css/auth-pages.css`. You can customize:

- Color gradients
- Typography
- Spacing
- Mobile breakpoints
- Dark mode colors

### Customizing Messages

All messages use WordPress translation functions. You can customize them using:
- Translation files (`.po`/`.mo`)
- Translation plugins
- Direct editing of template files (not recommended)

### Adding Custom Fields

To add custom registration fields:

1. Edit `template-register.php` to add the form field
2. Edit `includes/auth-helpers.php` to process the field
3. Use `update_user_meta()` to save custom data

## Styling

### Color Scheme

The default design uses a purple gradient:
- Primary: `#667eea` to `#764ba2`
- Text: `#1f2937`
- Background: `#ffffff`

### Mobile Responsive

The design automatically adapts:
- **Desktop (968px+)**: Split-screen layout
- **Tablet (577-967px)**: Stacked layout, features hidden
- **Mobile (<576px)**: Compact layout, optimized for small screens

### Dark Mode

The CSS includes dark mode support via `@media (prefers-color-scheme: dark)`:
- Adapts background colors
- Adjusts text contrast
- Maintains readability

## Troubleshooting

### Register Button Not Showing

**Issue**: Register button doesn't appear in header  
**Solution**: Check if user registration is enabled in WordPress:
1. Go to **Settings → General**
2. Check "Anyone can register" option
3. Save changes

### Pages Not Showing in Header Links

**Issue**: Sign In or Register buttons link to WordPress defaults  
**Solution**: Ensure pages are using the correct templates:
1. Edit the page
2. Check **Page Attributes → Template**
3. Save/update the page

### Login/Register Not Working

**Issue**: Form submission doesn't work  
**Solution**: Check these common issues:
1. WordPress is up to date
2. No plugin conflicts
3. Permalinks are flushed (Settings → Permalinks → Save)
4. File permissions are correct

### Style Issues

**Issue**: Pages don't look right  
**Solution**:
1. Clear browser cache
2. Clear WordPress cache (if using caching plugin)
3. Check if CSS file exists: `assets/css/auth-pages.css`
4. Verify CSS is enqueued properly

## File Structure

```
├── template-login.php              # Login page template
├── template-register.php           # Registration page template
├── template-lost-password.php      # Lost password request form
├── template-reset-password.php     # Password reset form
├── includes/
│   ├── auth-helpers.php           # URL detection & form handling
│   ├── customizer/
│   │   └── form-content-controls.php  # Customizer settings for all auth pages
│   └── dynamic-css.php            # CSS variables for auth pages
├── assets/
│   ├── css/
│   │   ├── auth-pages.css         # Authentication pages styles
│   │   └── user-menu.css          # Header buttons styles
│   └── js/
│       └── customizer-preview.js  # Real-time customizer preview
└── template-parts/
    └── components/
        └── user-menu.php          # Header menu
```

## Developer Notes

### Helper Functions

```php
// Get login page URL
vh360_get_login_page_url()

// Get registration page URL  
vh360_get_register_page_url()

// Get lost password page URL
vh360_get_lost_password_page_url()

// Get reset password page URL
vh360_get_reset_password_page_url()

// Registration form handler (hooked to template_redirect)
vh360_handle_registration()

// Lost password form handler (hooked to template_redirect)
vh360_handle_lost_password()

// Reset password form handler (hooked to template_redirect)
vh360_handle_reset_password()
```

### Hooks and Filters

The authentication system uses standard WordPress hooks:
- `template_redirect` - Form submission handling
- `wp_enqueue_scripts` - Asset loading
- `retrieve_password_message` - Custom password reset email URL
- `wp_login_failed` - Failed login redirect
- `authenticate` - Authentication error handling

### Extending Functionality

To extend the registration process, hook into WordPress native actions:
- `user_register` - Fires after user is created
- `wp_login` - Fires after successful login

Example:

```php
add_action('user_register', 'my_custom_registration_handler');
function my_custom_registration_handler($user_id) {
    // Add custom logic here
    update_user_meta($user_id, 'registration_date', current_time('mysql'));
}
```

## Best Practices

1. **Keep URLs Clean**: Use simple slugs like `/login/` and `/register/`
2. **Test Mobile**: Always test on mobile devices
3. **Monitor Errors**: Check for registration errors in WordPress logs
4. **Security**: Keep WordPress and theme updated
5. **Accessibility**: Test with keyboard navigation and screen readers

## Support

For issues or questions:
1. Check this documentation first
2. Review WordPress logs for errors
3. Check browser console for JavaScript errors
4. Verify theme and WordPress are up to date

## Changelog

### Version 1.0.0
- Initial release
- Login page template
- Registration page template
- Smart URL detection
- Security hardening
- Mobile responsive design
- Dark mode support
