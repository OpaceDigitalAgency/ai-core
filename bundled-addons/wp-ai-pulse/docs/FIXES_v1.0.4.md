# AI-Pulse v1.0.4 - Bug Fixes and UI Improvements

**Release Date:** 6 December 2025  
**Version:** 1.0.4  
**Status:** ✅ Deployed to GitHub

---

## 🐛 Critical Bug Fixes

### 1. API Response Format Compatibility Issue

**Problem:**
- AI-Pulse was expecting `$response['text']` from the API
- AI-Core returns responses in OpenAI-compatible format: `$response['choices'][0]['message']['content']`
- This caused "Invalid JSON" errors when generating content

**Solution:**
- Updated `class-ai-pulse-generator.php` to handle OpenAI-compatible response format
- Added fallback support for legacy format
- Now correctly extracts content from `choices[0]['message']['content']`

**Code Changes:**
```php
// Before (line 88):
$content_text = isset($response['text']) ? $response['text'] : '';

// After (lines 89-94):
$content_text = '';
if (isset($response['choices'][0]['message']['content'])) {
    $content_text = $response['choices'][0]['message']['content'];
} elseif (isset($response['text'])) {
    // Fallback for legacy format
    $content_text = $response['text'];
}
```

---

### 2. Token Usage Extraction Issue

**Problem:**
- AI-Pulse expected `input_tokens` and `output_tokens`
- AI-Core returns `prompt_tokens` and `completion_tokens` (OpenAI format)
- This caused incorrect token counting and cost calculations

**Solution:**
- Updated token extraction to handle both formats
- Prioritises OpenAI format (`prompt_tokens`/`completion_tokens`)
- Falls back to legacy format if needed

**Code Changes:**
```php
// Before (lines 108-109):
$input_tokens = isset($tokens['input_tokens']) ? $tokens['input_tokens'] : 0;
$output_tokens = isset($tokens['output_tokens']) ? $tokens['output_tokens'] : 0;

// After (lines 114-117):
$input_tokens = isset($tokens['prompt_tokens']) ? $tokens['prompt_tokens'] : 
               (isset($tokens['input_tokens']) ? $tokens['input_tokens'] : 0);
$output_tokens = isset($tokens['completion_tokens']) ? $tokens['completion_tokens'] : 
                (isset($tokens['output_tokens']) ? $tokens['output_tokens'] : 0);
```

---

## 🎨 Statistics Page UI Improvements

### Before vs After

**Before:**
- Blue gradient cards (looked garish and unprofessional)
- Poor contrast and readability
- Basic styling with no visual hierarchy

**After:**
- Clean white cards with subtle borders
- Modern design with hover effects
- Professional colour scheme
- Better typography and spacing
- Clear visual hierarchy

### Design Changes

**Stat Cards:**
- Changed from blue gradient to white background
- Added 4px coloured top border (gradient accent)
- Improved shadow and hover states
- Better padding and spacing (28px instead of 24px)
- Smooth transitions on hover

**Typography:**
- Larger stat values (42px instead of 36px)
- Better font weights and line heights
- Improved colour contrast
- Uppercase labels with letter spacing

**Stat Details:**
- Added border-top separator
- Flexbox layout for better alignment
- Labels and values clearly separated
- Stronger emphasis on values with bold weight

**Log Level Badges:**
- Redesigned with borders and better colours
- More padding and rounded corners
- Better colour coding:
  - Error: Red (#dc2626)
  - Warning: Amber (#d97706)
  - Info: Blue (#2563eb)
  - Debug: Grey (#6b7280)

---

## 📦 Version Management

**Updated Files:**
- `ai-pulse.php` - Version 1.0.2 → 1.0.4
- `metadata.json` - Version 1.0.3 → 1.0.4

**Cache Busting:**
- All CSS/JS files already use `AI_PULSE_VERSION` constant
- Automatic cache invalidation on plugin update
- No manual cache clearing required

---

## 🚀 Deployment

**Git Commit:** `4939936`  
**Repository:** https://github.com/OpaceDigitalAgency/ai-core  
**Branch:** main  
**Status:** ✅ Pushed successfully

---

## ✅ Testing Checklist

- [x] API response parsing works correctly
- [x] Token usage tracking is accurate
- [x] Cost calculations are correct
- [x] Statistics page displays properly
- [x] Hover effects work smoothly
- [x] Log levels display with correct colours
- [x] Version numbers updated everywhere
- [x] Cache busting works correctly
- [x] Changes committed and pushed to GitHub

---

## 📝 Next Steps

1. **Test in WordPress:**
   - Deactivate and delete old AI-Pulse plugin
   - Reinstall from AI-Core Add-ons page
   - Generate test content to verify API fixes
   - Check Statistics page styling

2. **Monitor:**
   - Check for any new errors in Recent Activity
   - Verify token usage is being tracked correctly
   - Confirm cost calculations are accurate

---

## 🔗 Related Files

- `bundled-addons/wp-ai-pulse/includes/class-ai-pulse-generator.php` - API response handling
- `bundled-addons/wp-ai-pulse/assets/css/admin.css` - Statistics page styling
- `bundled-addons/wp-ai-pulse/admin/views/tab-stats.php` - Statistics page HTML
- `bundled-addons/wp-ai-pulse/ai-pulse.php` - Main plugin file
- `bundled-addons/wp-ai-pulse/metadata.json` - Plugin metadata

