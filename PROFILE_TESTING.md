# Profile System Testing Guide

This document outlines test cases for the profile system implementation.

## Test Environment Setup

1. Install WordPress with the Videohub360 Theme
2. Create at least 2 test users (one regular user, one administrator)
3. Create sample videos (post type: 'videohub360' or 'post')
4. Assign videos to different users
5. Enable pretty permalinks (Settings > Permalinks)

## Test Cases

### 1. Profile Display Tests

#### 1.1 View User Profile
- **URL**: `yoursite.com/author/username`
- **Expected**: 
  - Page displays user's avatar
  - Shows display name and username
  - Shows join date
  - Displays user statistics (videos, views)
  - Shows bio if user has one
  - Shows "no bio" message if user doesn't have a bio
  - Lists user's videos in a grid
  - Shows "no videos" message if user has no videos

#### 1.2 Profile with Cover Image
- **Setup**: User has uploaded a cover image
- **Expected**: Cover image displays in header section

#### 1.3 Profile with Social Links
- **Setup**: User has added social media links
- **Expected**: Social links display as clickable buttons/links

#### 1.4 Profile Statistics
- **Expected**:
  - Video count matches actual published videos
  - View count shows total views across all videos
  - Subscriber count displays if > 0
  - Likes count displays if > 0

### 2. Profile Edit Tests

#### 2.1 Access Edit Profile (Owner)
- **Action**: User views their own profile
- **Expected**: "Edit Profile" button is visible

#### 2.2 Access Edit Profile (Admin)
- **Action**: Admin views another user's profile
- **Expected**: "Edit Profile" button is visible

#### 2.3 Access Edit Profile (Other User)
- **Action**: User A views User B's profile
- **Expected**: "Edit Profile" button is NOT visible

#### 2.4 Edit Profile Page Access (Not Logged In)
- **Action**: Non-logged-in user tries to access edit profile page
- **Expected**: Redirected to login page

### 3. Profile Editing Functionality

#### 3.1 Update Basic Information
- **Action**: Edit display name, bio, email, website
- **Expected**: 
  - Fields pre-populate with current data
  - Success message on save
  - Changes reflect on profile page immediately

#### 3.2 Update Social Links
- **Action**: Add/update Twitter, Facebook, YouTube, Instagram links
- **Expected**: 
  - Links save successfully
  - Links display on profile page
  - Links are clickable and open in new tab

#### 3.3 Upload Cover Image (Valid)
- **Action**: Upload JPG/PNG/GIF under 5MB
- **Expected**: 
  - Upload succeeds
  - Success message displays
  - Cover image shows on profile page

#### 3.4 Upload Cover Image (Invalid Type)
- **Action**: Try to upload .exe, .pdf, or other non-image file
- **Expected**: 
  - Error message: "Invalid file type..."
  - Upload rejected

#### 3.5 Upload Cover Image (Too Large)
- **Action**: Try to upload image over 5MB
- **Expected**: 
  - Error message: "File size too large..."
  - Upload rejected

#### 3.6 Form Validation
- **Action**: Submit form with empty display name
- **Expected**: Error message: "Display name is required"

#### 3.7 Email Validation
- **Action**: Submit form with invalid email
- **Expected**: Error message: "Please enter a valid email address"

### 4. Video Grid Tests

#### 4.1 Display User Videos
- **Expected**: 
  - All user's published videos display
  - Uses existing video card component
  - Videos show thumbnails, titles, views, dates
  - Author name is NOT shown (since we're on their profile)

#### 4.2 Video Pagination
- **Setup**: User has more than 12 videos
- **Expected**: 
  - Only 12 videos display per page
  - Pagination controls appear
  - Navigation to page 2 works

#### 4.3 Video Sorting - Latest
- **Action**: Select "Latest" in sort dropdown
- **Expected**: Videos display newest first

#### 4.4 Video Sorting - Most Viewed
- **Action**: Select "Most Viewed" in sort dropdown
- **Expected**: Videos display with highest views first

#### 4.5 Video Sorting - Oldest
- **Action**: Select "Oldest" in sort dropdown
- **Expected**: Videos display oldest first

#### 4.6 Empty Video Grid
- **Setup**: User has no videos
- **Expected**: 
  - Empty state message displays
  - If viewing own profile: "Start creating and sharing..."
  - If viewing other profile: "This user has not uploaded..."

### 5. Responsive Design Tests

#### 5.1 Mobile View (< 480px)
- **Expected**:
  - Cover height reduced
  - Avatar stacks on top of details
  - Stats display in single column
  - Video grid shows 1 column
  - Edit button full width

#### 5.2 Tablet View (481px - 768px)
- **Expected**:
  - Cover height medium
  - Avatar and details side by side
  - Stats in 2 columns
  - Video grid shows 2 columns

#### 5.3 Desktop View (> 768px)
- **Expected**:
  - Full cover height (300px)
  - Full layout as designed
  - Stats in 4 columns
  - Video grid shows 3-4 columns

### 6. Security Tests

#### 6.1 XSS Prevention
- **Action**: Try to inject `<script>alert('xss')</script>` in bio
- **Expected**: Script is escaped, displays as text, does not execute

#### 6.2 CSRF Prevention
- **Action**: Try to submit edit form without nonce
- **Expected**: Security check fails, error message displays

#### 6.3 SQL Injection Prevention
- **Action**: Try SQL injection in URL parameters
- **Expected**: Input is sanitized, no database errors

#### 6.4 File Upload Security
- **Action**: Try to upload malicious file disguised as image
- **Expected**: File validation rejects upload

### 7. Performance Tests

#### 7.1 Profile Page Load
- **Expected**: Page loads in under 2 seconds

#### 7.2 Video Count Calculation
- **Expected**: Uses `count_user_posts()`, not loading all posts

#### 7.3 Asset Loading
- **Expected**: 
  - `profiles.css` loads ONLY on author pages
  - No unnecessary JavaScript loaded

### 8. Integration Tests

#### 8.1 With Videohub360 Plugin
- **Setup**: Videohub360 plugin active
- **Expected**: 
  - Profile functions check plugin functions first
  - Video stats display correctly
  - Everything works normally

#### 8.2 Without Videohub360 Plugin
- **Setup**: Videohub360 plugin not active
- **Expected**: 
  - Profile still works with fallback functions
  - Stats may show zeros if no meta data
  - No fatal errors

#### 8.3 With Gravatar
- **Setup**: User uses Gravatar
- **Expected**: Gravatar displays correctly

#### 8.4 With Custom Avatar
- **Setup**: Custom avatar uploaded
- **Expected**: Custom avatar takes priority over Gravatar

### 9. Breadcrumb Tests

#### 9.1 Breadcrumb Display
- **Expected**: 
  - Shows: Home » Profile » Username
  - Links work correctly
  - Current page not linked

### 10. Helper Function Tests

#### 10.1 vh360_get_user_avatar_url()
- **Test**: Call with valid user ID
- **Expected**: Returns avatar URL string

#### 10.2 vh360_get_user_cover_image()
- **Test**: Call with user who has cover image
- **Expected**: Returns image URL

#### 10.3 vh360_get_user_bio()
- **Test**: Call with user who has bio
- **Expected**: Returns sanitized bio text

#### 10.4 vh360_get_user_join_date()
- **Test**: Call with format 'F Y'
- **Expected**: Returns formatted date like "November 2023"

#### 10.5 vh360_get_user_video_count()
- **Test**: Call with user who has videos
- **Expected**: Returns correct integer count

#### 10.6 vh360_get_user_social_links()
- **Test**: Call with user who has social links
- **Expected**: Returns array with non-empty values only

## Manual Testing Checklist

- [ ] Profile displays correctly for existing users
- [ ] Edit profile form loads and works
- [ ] Cover image uploads successfully
- [ ] File validation prevents invalid uploads
- [ ] Social links save and display
- [ ] Video grid shows user's videos
- [ ] Pagination works with 12+ videos
- [ ] Sort dropdown changes video order
- [ ] Empty states display appropriately
- [ ] Edit button shows only for authorized users
- [ ] Nonce verification prevents CSRF
- [ ] Output is properly escaped
- [ ] Mobile layout works correctly
- [ ] Tablet layout works correctly
- [ ] Desktop layout works correctly
- [ ] profiles.css loads only on author pages
- [ ] Works with Videohub360 plugin
- [ ] Works without Videohub360 plugin
- [ ] No PHP errors or warnings
- [ ] No JavaScript console errors

## Expected Behavior Summary

✅ Every WordPress user has immediate profile at `/author/username`
✅ No database migration or setup required
✅ Users can edit their own profiles
✅ Admins can edit any profile
✅ Responsive design works on all devices
✅ Security best practices implemented
✅ Performance optimized
✅ Graceful error handling
✅ Empty states for missing data
✅ Works with and without plugin

## Common Issues

### Issue: Permalinks not working
**Solution**: Go to Settings > Permalinks and click "Save Changes"

### Issue: Edit button links to wp-admin
**Solution**: Create a page using "Profile Edit" template

### Issue: Videos not showing
**Solution**: Ensure user has published videos with correct post type

### Issue: Cover image won't upload
**Solution**: Check file size (<5MB) and type (JPG/PNG/GIF)
