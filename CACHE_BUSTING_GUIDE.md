# AI-Core Cache Busting Guide

## The Problem

When you update JavaScript or CSS files, browsers may continue to load old cached versions, causing:
- JavaScript errors ("function not found")
- Old UI behaviour
- Missing features
- Confusion about whether changes are deployed

## The Solution: Version-Based Cache Busting

AI-Core uses **version-based cache busting** to force browsers to reload assets when the plugin is updated.

---

## How It Works

### 1. Single Source of Truth: `AI_CORE_VERSION`

All version numbers come from **one place**:

```php
// ai-core.php (line 30)
define('AI_CORE_VERSION', '0.1.8');
```

### 2. All Assets Use This Version

Every script and style is enqueued with `AI_CORE_VERSION`:

```php
// Example from ai-core.php
wp_enqueue_script(
    'ai-core-admin',
    AI_CORE_PLUGIN_URL . 'assets/js/admin.js',
    array('jquery'),
    AI_CORE_VERSION,  // ← This is the cache buster
    true
);

wp_enqueue_style(
    'ai-core-prompt-library',
    AI_CORE_PLUGIN_URL . 'assets/css/prompt-library.css',
    array(),
    AI_CORE_VERSION  // ← This is the cache buster
);
```

### 3. Browser Sees Different URLs

When you change the version from `0.1.7` to `0.1.8`, the browser sees:

**Before:**
```
/wp-content/plugins/ai-core/assets/js/admin.js?ver=0.1.7
/wp-content/plugins/ai-core/assets/css/prompt-library.css?ver=0.1.7
```

**After:**
```
/wp-content/plugins/ai-core/assets/js/admin.js?ver=0.1.8
/wp-content/plugins/ai-core/assets/css/prompt-library.css?ver=0.1.8
```

The browser treats these as **completely different files** and downloads the new versions.

---

## Foolproof Update Checklist

When releasing a new version, update **ALL** of these files:

### ✅ Step 1: Update Main Plugin File
```php
// ai-core.php (line 21)
* @version 0.1.8

// ai-core.php (line 30)
define('AI_CORE_VERSION', '0.1.8');
```

### ✅ Step 2: Update JavaScript Files
```javascript
// assets/js/admin.js (line 5)
* @version 0.1.8

// assets/js/prompt-library.js (line 5)
* @version 0.1.8
```

### ✅ Step 3: Update CSS Files
```css
/* assets/css/prompt-library.css (line 5) */
* @version 0.1.8
```

### ✅ Step 4: Update readme.txt
```
Stable tag: 0.1.8
```

### ✅ Step 5: Update lib/version.json
```json
{
  "version": "0.1.8"
}
```

---

## Why Update File Headers?

The version comments in JavaScript/CSS files (`@version 0.1.8`) serve two purposes:

1. **Documentation** - Developers can see which version of the file they're looking at
2. **Debugging** - When inspecting files in browser DevTools, you can verify you have the latest version

**Example:**
```javascript
// If you see this in DevTools:
/**
 * AI-Core Admin JavaScript
 * @version 0.1.7  ← OLD VERSION!
 */

// But AI_CORE_VERSION is 0.1.8, you know there's a cache issue
```

---

## Testing Cache Busting

### 1. Check Version in WordPress
```php
// Add this to any admin page temporarily:
echo 'AI-Core Version: ' . AI_CORE_VERSION;
```

### 2. Check Browser Network Tab
1. Open DevTools (F12)
2. Go to **Network** tab
3. Reload page
4. Look for asset URLs:
   ```
   admin.js?ver=0.1.8  ← Should match AI_CORE_VERSION
   prompt-library.css?ver=0.1.8  ← Should match AI_CORE_VERSION
   ```

### 3. Check File Contents
1. Open DevTools (F12)
2. Go to **Sources** tab
3. Find `assets/js/admin.js`
4. Check the `@version` comment at the top
5. It should match `AI_CORE_VERSION`

---

## Common Cache Issues & Solutions

### Issue 1: "JavaScript errors after update"

**Symptoms:**
- Console shows "function not found"
- Features don't work
- Old behaviour persists

**Solution:**
1. Hard refresh: **Cmd+Shift+R** (Mac) or **Ctrl+Shift+R** (Windows)
2. Clear browser cache
3. Check Network tab → Verify `?ver=0.1.8` on all assets
4. If still broken, check file headers match `AI_CORE_VERSION`

### Issue 2: "Changes not showing up"

**Symptoms:**
- Code changes don't appear
- Old UI still visible
- Features missing

**Solution:**
1. Verify `AI_CORE_VERSION` was updated in `ai-core.php`
2. Verify file headers were updated
3. Clear WordPress cache (if using caching plugin)
4. Clear CDN cache (if using CDN)
5. Hard refresh browser

### Issue 3: "Different users see different versions"

**Symptoms:**
- Some users see new version
- Some users see old version
- Inconsistent behaviour

**Solution:**
1. Check if caching plugin is active (W3 Total Cache, WP Super Cache, etc.)
2. Clear WordPress cache
3. Check if CDN is caching assets (Cloudflare, etc.)
4. Clear CDN cache
5. Verify `AI_CORE_VERSION` is correct

---

## WordPress Caching Plugins

If you use a caching plugin, you **must** clear its cache after updating:

### W3 Total Cache
```
Performance → Dashboard → Empty All Caches
```

### WP Super Cache
```
Settings → WP Super Cache → Delete Cache
```

### WP Rocket
```
WP Rocket → Clear Cache
```

### LiteSpeed Cache
```
LiteSpeed Cache → Toolbox → Purge All
```

---

## CDN Caching

If you use a CDN (Cloudflare, etc.), you **must** purge its cache:

### Cloudflare
1. Log in to Cloudflare
2. Select your domain
3. Go to **Caching** → **Configuration**
4. Click **Purge Everything**

### Other CDNs
Consult your CDN's documentation for cache purging.

---

## Development Best Practices

### During Development

Use **timestamp-based versioning** for instant cache busting:

```php
// ai-core.php (temporary, for development only)
define('AI_CORE_VERSION', time());  // Changes every second
```

**Remember to change it back before committing!**

### Before Committing

1. Update `AI_CORE_VERSION` to proper semantic version (e.g., `0.1.8`)
2. Update all file headers
3. Test in clean browser (incognito mode)
4. Verify Network tab shows correct version

### Semantic Versioning

Follow semantic versioning: `MAJOR.MINOR.PATCH`

- **MAJOR** (1.0.0) - Breaking changes
- **MINOR** (0.1.0) - New features, backwards compatible
- **PATCH** (0.0.1) - Bug fixes, backwards compatible

Examples:
- `0.1.7` → `0.1.8` - Bug fix (cache busting fix)
- `0.1.8` → `0.2.0` - New feature (prompt library)
- `0.2.0` → `1.0.0` - Breaking change (API redesign)

---

## Automated Version Bumping (Future)

Consider creating a script to automate version updates:

```bash
#!/bin/bash
# bump-version.sh

NEW_VERSION=$1

if [ -z "$NEW_VERSION" ]; then
  echo "Usage: ./bump-version.sh 0.1.8"
  exit 1
fi

# Update ai-core.php
sed -i '' "s/@version [0-9.]*/@version $NEW_VERSION/" ai-core.php
sed -i '' "s/AI_CORE_VERSION', '[0-9.]*'/AI_CORE_VERSION', '$NEW_VERSION'/" ai-core.php

# Update JavaScript files
sed -i '' "s/@version [0-9.]*/@version $NEW_VERSION/" assets/js/*.js

# Update CSS files
sed -i '' "s/@version [0-9.]*/@version $NEW_VERSION/" assets/css/*.css

# Update readme.txt
sed -i '' "s/Stable tag: [0-9.]*/Stable tag: $NEW_VERSION/" readme.txt

# Update lib/version.json
sed -i '' "s/\"version\": \"[0-9.]*\"/\"version\": \"$NEW_VERSION\"/" lib/version.json

echo "✅ Version bumped to $NEW_VERSION"
```

Usage:
```bash
./bump-version.sh 0.1.8
```

---

## Summary

**The Golden Rule:**
> When you update `AI_CORE_VERSION`, update ALL file headers to match.

**Files to Update:**
1. `ai-core.php` (2 places)
2. `assets/js/admin.js`
3. `assets/js/prompt-library.js`
4. `assets/css/prompt-library.css`
5. `readme.txt`
6. `lib/version.json`

**Testing:**
1. Check Network tab → All assets show `?ver=0.1.8`
2. Check file contents → Headers show `@version 0.1.8`
3. Test in incognito mode → No cache issues

**If Issues Persist:**
1. Clear WordPress cache
2. Clear CDN cache
3. Hard refresh browser
4. Check file headers match `AI_CORE_VERSION`

---

**End of Cache Busting Guide**

