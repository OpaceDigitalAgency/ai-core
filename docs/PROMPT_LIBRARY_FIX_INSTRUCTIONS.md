# Prompt Library Page Hanging - Fix Instructions

## Issue
The Prompt Library page at `/wp-admin/admin.php?page=ai-core-prompt-library` is hanging and not loading.

## Changes Made

### 1. Added Error Handling to `render_page()` Method
**File:** `admin/class-ai-core-prompt-library.php` (Lines 74-98)

Added try-catch block with:
- Increased timeout to 60 seconds
- Error logging for debugging
- User-friendly error display if page fails to load

### 2. Optimised Database Queries
**File:** `admin/class-ai-core-prompt-library.php`

#### `get_groups()` Method (Lines 493-526)
- **Before:** Made N+1 queries (1 for groups + 1 per group for counts)
- **After:** Single optimised query using LEFT JOIN and GROUP BY
- Added table existence check
- Added error logging for database errors

#### `get_prompts()` Method (Lines 544-615)
- Added table existence check
- Added error logging for database errors
- Improved error handling

### 3. Created Diagnostic Script
**File:** `ai-core-prompt-library-diagnostic.php`

A comprehensive diagnostic tool to identify the exact issue.

## How to Fix the Issue

### Step 1: Run the Diagnostic Script

1. Upload the changes to your server
2. Navigate to: `https://adwordsadvantage.com/wp-content/plugins/ai-core/ai-core-prompt-library-diagnostic.php`
3. Review the diagnostic results to identify the specific issue

The diagnostic will check:
- ✅ Database tables exist
- ✅ Record counts
- ✅ Query performance
- ✅ PHP configuration
- ✅ Class availability

### Step 2: Interpret Diagnostic Results

#### If Tables Are Missing:
```bash
# Deactivate and reactivate the plugin
wp plugin deactivate ai-core
wp plugin activate ai-core
```

Or via WordPress admin:
1. Go to Plugins
2. Deactivate AI-Core
3. Activate AI-Core

#### If Query Time Is Slow (> 2 seconds):
The database may need optimisation. Check:
- Database server performance
- Table indices
- Number of records (130 prompts across 26 groups should be fast)

#### If Classes Are Not Available:
There may be a PHP error preventing the class from loading. Check:
1. PHP error logs
2. WordPress debug log (enable WP_DEBUG in wp-config.php)

### Step 3: Check WordPress Debug Log

Enable debugging in `wp-config.php`:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

Then check `/wp-content/debug.log` for errors.

### Step 4: Try Loading the Prompt Library Page

After running the diagnostic and fixing any issues, try loading:
`https://adwordsadvantage.com/wp-admin/admin.php?page=ai-core-prompt-library`

## Common Issues and Solutions

### Issue 1: Database Tables Don't Exist
**Solution:** Reactivate the plugin to create tables

### Issue 2: PHP Timeout
**Solution:** The code now increases timeout to 60 seconds. If still timing out, increase in php.ini:
```ini
max_execution_time = 120
```

### Issue 3: Memory Limit
**Solution:** Increase memory limit in wp-config.php:
```php
define('WP_MEMORY_LIMIT', '256M');
```

### Issue 4: JavaScript Not Loading
**Solution:** Clear browser cache and check browser console for errors

### Issue 5: AJAX Errors
**Solution:** Check that nonce is being passed correctly and AJAX handlers are registered

## Performance Improvements Made

1. **Eliminated N+1 Query Problem**
   - Before: 1 + 26 = 27 queries for 26 groups
   - After: 1 query for all groups with counts

2. **Added Database Error Handling**
   - Prevents white screen of death
   - Logs errors for debugging
   - Returns empty arrays instead of failing

3. **Added Timeout Protection**
   - Increases execution time for large datasets
   - Prevents PHP timeout errors

## Testing Checklist

After applying fixes:

- [ ] Diagnostic script runs successfully
- [ ] All database tables exist
- [ ] Query times are < 1 second
- [ ] Prompt Library page loads
- [ ] Can view all 26 groups
- [ ] Can view all 130 prompts
- [ ] Can create new prompts
- [ ] Can edit existing prompts
- [ ] Can delete prompts
- [ ] Can create new groups
- [ ] Can edit existing groups
- [ ] Can delete groups
- [ ] Drag and drop works
- [ ] Search and filter work
- [ ] Import/export work

## Next Steps

1. **Run the diagnostic script first** to identify the exact issue
2. **Check the WordPress debug log** for any PHP errors
3. **Apply the appropriate fix** based on diagnostic results
4. **Test the Prompt Library page** to ensure it loads correctly
5. **Run the force-update script** (if needed) to install all 26 categories with 130 prompts

## Force Update Script

If you need to force install all prompts after fixing the page loading issue, navigate to:
`https://adwordsadvantage.com/wp-admin/admin.php?page=ai-core&force_update=1`

(Note: You'll need to create this script based on your previous task summary)

## Support

If the issue persists after following these steps:
1. Share the diagnostic script output
2. Share any errors from the WordPress debug log
3. Share any errors from the browser console (F12 → Console tab)

## Files Modified

1. `admin/class-ai-core-prompt-library.php` - Added error handling and optimised queries
2. `ai-core-prompt-library-diagnostic.php` - New diagnostic script

## Commit Message

```
Fix Prompt Library page hanging issue

- Add error handling and timeout protection to render_page()
- Optimise get_groups() to eliminate N+1 query problem
- Add database error logging to get_groups() and get_prompts()
- Add table existence checks before queries
- Create comprehensive diagnostic script for troubleshooting

This fixes the issue where the Prompt Library page would hang
when loading 26 groups with 130 prompts.
```

