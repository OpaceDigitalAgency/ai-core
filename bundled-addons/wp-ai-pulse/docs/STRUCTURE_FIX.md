# AI-Pulse Folder Structure Fix

**Date:** 2025-12-05  
**Issue:** Incorrect bundled add-on folder structure  
**Status:** ✅ RESOLVED

---

## Problem Identified

The AI-Pulse plugin was initially structured incorrectly for bundled add-on installation:

### ❌ Original Structure (WRONG)
```
bundled-addons/
└── ai-pulse/                    ← Wrapper folder
    ├── metadata.json
    ├── *.md files
    └── wp-ai-pulse/             ← Actual plugin folder
        ├── ai-pulse.php
        ├── includes/
        ├── admin/
        └── assets/
```

### Why This Was Wrong

AI-Core's `install_bundled_addon()` method expects:
- **Source:** `bundled-addons/{slug}/`
- **Destination:** `wp-content/plugins/{slug}/`
- **Plugin File:** `{slug}/{main-file}.php`

With the nested structure:
- AI-Core would copy `bundled-addons/ai-pulse/` → `wp-content/plugins/ai-pulse/`
- This would copy the wrapper folder with metadata.json and docs
- The actual plugin files would be in `wp-content/plugins/ai-pulse/wp-ai-pulse/`
- WordPress wouldn't find the plugin

---

## Solution Applied

### ✅ Correct Structure (FIXED)
```
bundled-addons/
└── wp-ai-pulse/                 ← Plugin folder (matches slug)
    ├── ai-pulse.php             ← Main plugin file
    ├── readme.txt
    ├── uninstall.php
    ├── metadata.json            ← Moved here
    ├── *.md files               ← Moved here
    ├── includes/
    ├── admin/
    └── assets/
```

### How Installation Works Now

1. **User clicks Install** in AI-Core → Add-ons
2. **AI-Core copies** `bundled-addons/wp-ai-pulse/` → `wp-content/plugins/wp-ai-pulse/`
3. **WordPress detects** plugin at `wp-content/plugins/wp-ai-pulse/ai-pulse.php`
4. **User clicks Activate** and plugin runs

---

## Changes Made

### 1. Restructured Folders
```bash
# Moved wp-ai-pulse folder up one level
mv bundled-addons/ai-pulse/wp-ai-pulse bundled-addons/wp-ai-pulse-temp

# Moved all documentation files into the plugin folder
mv bundled-addons/ai-pulse/* bundled-addons/wp-ai-pulse-temp/

# Renamed to final location
mv bundled-addons/wp-ai-pulse-temp bundled-addons/wp-ai-pulse

# Removed empty wrapper folder
rm -rf bundled-addons/ai-pulse
```

### 2. Updated AI-Core Installation Logic

**File:** `admin/class-ai-core-addons.php`

Added `find_plugin_file()` method to automatically detect the main plugin file:

```php
private function find_plugin_file($dir, $slug) {
    $files = glob($dir . '/*.php');
    
    foreach ($files as $file) {
        $plugin_data = get_file_data($file, array('Plugin Name' => 'Plugin Name'));
        if (!empty($plugin_data['Plugin Name'])) {
            return $slug . '/' . basename($file);
        }
    }
    
    return false;
}
```

This allows plugins to have different main file names (e.g., `ai-pulse.php` instead of `wp-ai-pulse.php`).

---

## Comparison with Other Add-ons

### AI-Imagen Structure
```
bundled-addons/
└── ai-imagen/
    ├── ai-imagen.php            ← Main file matches folder name
    ├── includes/
    └── admin/
```

### AI-Stats Structure
```
bundled-addons/
└── ai-stats/
    ├── ai-stats.php             ← Main file matches folder name
    ├── includes/
    └── admin/
```

### AI-Pulse Structure (Now Correct)
```
bundled-addons/
└── wp-ai-pulse/
    ├── ai-pulse.php             ← Main file (different from folder)
    ├── includes/
    └── admin/
```

**Note:** The `find_plugin_file()` method now handles both patterns:
- Matching names: `ai-imagen/ai-imagen.php`
- Different names: `wp-ai-pulse/ai-pulse.php`

---

## Registration in AI-Core

**File:** `admin/class-ai-core-addons.php` (lines 105-117)

```php
array(
    'slug' => 'wp-ai-pulse',                    // Folder name in bundled-addons
    'name' => 'AI-Pulse',
    'description' => '...',
    'author' => 'Opace Digital Agency',
    'version' => '1.0.0',
    'requires' => 'AI-Core 1.0+',
    'installed' => $this->is_plugin_installed('wp-ai-pulse'),
    'active' => $this->is_plugin_active('wp-ai-pulse'),
    'icon' => 'dashicons-analytics',
    'url' => 'https://opace.agency/ai-pulse',
    'bundled' => true,
    'plugin_file' => 'wp-ai-pulse/ai-pulse.php',  // Correct path
),
```

---

## Verification

### Before Fix
- ❌ Plugin folder: `bundled-addons/ai-pulse/wp-ai-pulse/`
- ❌ Would install to: `wp-content/plugins/ai-pulse/`
- ❌ WordPress would look for: `ai-pulse/ai-pulse.php` (doesn't exist)

### After Fix
- ✅ Plugin folder: `bundled-addons/wp-ai-pulse/`
- ✅ Installs to: `wp-content/plugins/wp-ai-pulse/`
- ✅ WordPress finds: `wp-ai-pulse/ai-pulse.php` ✓

---

## Installation Ready

The plugin structure is now correct and will install properly via AI-Core's bundled add-on system.

**Status: ✅ READY FOR INSTALLATION**

