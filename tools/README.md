# Diagnostic Tools

This directory contains diagnostic and testing tools for the appointment booking system.

## Tools

### check-availability-settings.php
Visual diagnostic tool for checking a professional's availability settings.

**Usage:**
```
https://yoursite.com/wp-content/themes/videohub360-theme/tools/check-availability-settings.php?user_id=X
```

Replace X with the professional's user ID.

**Requirements:**
- Must be accessed by an administrator
- Displays weekly schedule, slot configuration, and tests slot generation
- Useful for troubleshooting booking issues

### test-slot-generation.php
Command-line test script for slot generation logic.

**Usage:**
```bash
cd /path/to/theme/tools
php test-slot-generation.php
```

**Purpose:**
- Tests the vh360_get_open_appointment_slots() function
- Verifies time format handling
- Useful during development and testing

## Notes

- These tools are for development/debugging purposes only
- Do not use in production environments
- Keep access restricted to administrators
- See `/docs` folder for complete documentation
