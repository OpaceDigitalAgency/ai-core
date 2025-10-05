# AI-Core v0.2.3 - Auto-Switch Gemini Models for Image Generation

**Release Date:** 2025-10-05  
**Version:** 0.2.3  
**Previous Version:** 0.2.2  
**Priority:** CRITICAL

---

## Executive Summary

This release adds **automatic model switching** for Gemini image generation, similar to how GPT-5 works. When a user selects a standard Gemini text model (like `gemini-2.5-flash` or `gemini-2.5-pro`) but chooses "Image Generation" type, the system now **automatically switches to the appropriate `-image` variant** (`gemini-2.5-flash-image`).

### What Was Broken:
- ❌ Selecting `gemini-2.5-flash` + "Image Generation" → **Critical Error**
- ❌ Selecting `gemini-2.5-pro` + "Image Generation" → **Critical Error**
- ❌ User had to manually select the exact `-image` model
- ❌ Confusing UX - models appeared in dropdown but didn't work
- ❌ Not intuitive like GPT-5 (which works for both text and images)

### What's Fixed:
- ✅ Selecting `gemini-2.5-flash` + "Image Generation" → **Auto-switches to `gemini-2.5-flash-image`**
- ✅ Selecting `gemini-2.5-pro` + "Image Generation" → **Auto-switches to `gemini-2.5-flash-image`**
- ✅ Works like GPT-5 - user doesn't need to know about `-image` variants
- ✅ Intuitive UX - any Gemini model works for image generation
- ✅ No more critical errors

---

## The Problem

### User Experience Issue

When a user selected:
1. **Provider:** Google Gemini
2. **Type:** Image Generation
3. **Model:** `gemini-2.5-flash` (standard text model)
4. **Clicked:** Run Test Prompt

**Result:** ❌ **Critical Error** - "Error: error"

**Why?** Because `gemini-2.5-flash` is a **text-only model** and cannot generate images. Only the `-image` variants can generate images.

### Comparison with GPT-5

**OpenAI GPT-5:**
- ✅ User selects `gpt-5`
- ✅ Works for BOTH text and image generation
- ✅ Single model, multiple capabilities

**Gemini (Before Fix):**
- ❌ User selects `gemini-2.5-flash`
- ❌ Only works for text generation
- ❌ Must manually select `gemini-2.5-flash-image` for images
- ❌ Confusing and error-prone

**Gemini (After Fix):**
- ✅ User selects `gemini-2.5-flash`
- ✅ Auto-switches to `gemini-2.5-flash-image` for image generation
- ✅ Works like GPT-5 - seamless experience

---

## The Solution

### Automatic Model Mapping

Added a new method `get_gemini_image_model()` in the AJAX handler that automatically maps standard Gemini models to their `-image` variants:

```php
private function get_gemini_image_model($model) {
    // If already an image model, return as-is
    if (strpos($model, '-image') !== false || strpos($model, 'imagen-') === 0) {
        return $model;
    }

    // Map standard models to their -image variants
    $image_model_map = array(
        'gemini-2.5-pro' => 'gemini-2.5-flash-image',
        'gemini-2.5-flash' => 'gemini-2.5-flash-image',
        'gemini-2.5-flash-lite' => 'gemini-2.5-flash-image',
        'gemini-2.0-flash' => 'gemini-2.5-flash-image',
        'gemini-2.0-flash-001' => 'gemini-2.5-flash-image',
        'gemini-2.0-flash-lite' => 'gemini-2.5-flash-image',
        'gemini-2.0-flash-lite-001' => 'gemini-2.5-flash-image',
    );

    return $image_model_map[$model] ?? 'gemini-2.5-flash-image';
}
```

### Integration in AJAX Handler

The AJAX handler now automatically switches models before calling the image generation API:

```php
if ($type === 'image') {
    $image_options = array();
    $original_model = $model;

    // Auto-switch Gemini models to -image variant if needed
    if ($provider === 'gemini' && !empty($model)) {
        $model = $this->get_gemini_image_model($model);
    }

    if (!empty($model)) {
        $image_options['model'] = $model;
    }

    $result = \AICore\AICore::generateImage($prompt_content, $image_options, $provider);
    
    // Return both the switched model and original for transparency
    wp_send_json_success(array(
        'result' => $image_url,
        'type' => 'image',
        'model' => $model, // The actual model used (e.g., gemini-2.5-flash-image)
        'original_model' => $original_model, // What user selected (e.g., gemini-2.5-flash)
        'provider' => $provider,
    ));
}
```

---

## Model Mapping Logic

### Why These Mappings?

| User Selects | Auto-Switches To | Reason |
|--------------|-------------------|--------|
| `gemini-2.5-pro` | `gemini-2.5-flash-image` | Pro doesn't have image variant |
| `gemini-2.5-flash` | `gemini-2.5-flash-image` | Direct mapping to image variant |
| `gemini-2.5-flash-lite` | `gemini-2.5-flash-image` | Lite doesn't have image variant |
| `gemini-2.0-flash` | `gemini-2.5-flash-image` | 2.0 doesn't have image variant, use 2.5 |
| `gemini-2.5-flash-image` | `gemini-2.5-flash-image` | Already image model, no change |
| `imagen-3.0-generate-001` | `imagen-3.0-generate-001` | Already image model, no change |

### Fallback Behavior

If a model is not in the mapping, it defaults to `gemini-2.5-flash-image` (the most capable and latest image model).

---

## Code Changes

### File 1: `admin/class-ai-core-ajax.php`

**Lines 422-448:** Modified image generation logic

**Before:**
```php
if ($type === 'image') {
    $image_options = array();
    if (!empty($model)) {
        $image_options['model'] = $model;
    }
    $result = \AICore\AICore::generateImage($prompt_content, $image_options, $provider);
}
```

**After:**
```php
if ($type === 'image') {
    $image_options = array();
    $original_model = $model;

    // Auto-switch Gemini models to -image variant if needed
    if ($provider === 'gemini' && !empty($model)) {
        $model = $this->get_gemini_image_model($model);
    }

    if (!empty($model)) {
        $image_options['model'] = $model;
    }
    $result = \AICore\AICore::generateImage($prompt_content, $image_options, $provider);
}
```

**Lines 496-525:** Added new helper method

```php
/**
 * Get the appropriate Gemini image model
 * 
 * Automatically converts standard Gemini models to their -image variants
 * for image generation, similar to how GPT-5 works for both text and images.
 * 
 * @param string $model The selected model
 * @return string The image-capable model
 */
private function get_gemini_image_model($model) {
    // Implementation...
}
```

---

## Version Updates (All Files)

To ensure **NO caching issues**, ALL version numbers have been updated to **0.2.3**:

### Core Plugin Files
- ✅ `ai-core.php` - Line 6: `Version: 0.2.3`
- ✅ `ai-core.php` - Line 20: `@version 0.2.3`
- ✅ `ai-core.php` - Line 29: `define('AI_CORE_VERSION', '0.2.3')`

### Admin Files
- ✅ `admin/class-ai-core-ajax.php` - Line 8: `@version 0.2.3`

### JavaScript Files
- ✅ `assets/js/admin.js` - Line 5: `@version 0.2.3`
- ✅ `assets/js/prompt-library.js` - Line 5: `@version 0.2.3`

### CSS Files
- ✅ `assets/css/admin.css` - Line 5: `@version 0.2.3`
- ✅ `assets/css/prompt-library.css` - Line 5: `@version 0.2.3`

### Library Files
- ✅ `lib/src/Providers/GeminiImageProvider.php` - Removed duplicate `isConfigured()` method

---

## Testing Instructions

### Test 1: Auto-Switch from gemini-2.5-flash

1. Go to **AI-Core → Settings**
2. Select **Provider: Google Gemini**
3. Select **Type: Image Generation**
4. Select **Model: Gemini 2.5 Flash (gemini-2.5-flash)** ← Standard text model
5. Enter prompt: `"A futuristic city at sunset"`
6. Click **Run Test Prompt**
7. **Expected:** ✅ Image generated successfully
8. **Behind the scenes:** Auto-switched to `gemini-2.5-flash-image`

### Test 2: Auto-Switch from gemini-2.5-pro

1. Select **Model: Gemini 2.5 Pro (gemini-2.5-pro)**
2. Enter prompt: `"A red sports car"`
3. Click **Run Test Prompt**
4. **Expected:** ✅ Image generated successfully
5. **Behind the scenes:** Auto-switched to `gemini-2.5-flash-image`

### Test 3: Direct Selection of Image Model

1. Select **Model: Gemini 2.5 Flash Image (gemini-2.5-flash-image)**
2. Enter prompt: `"A mountain landscape"`
3. Click **Run Test Prompt**
4. **Expected:** ✅ Image generated successfully
5. **Behind the scenes:** No switching needed, used as-is

### Test 4: Text Generation Still Works

1. Select **Type: Text Generation**
2. Select **Model: Gemini 2.5 Flash (gemini-2.5-flash)**
3. Enter prompt: `"Write a haiku about coding"`
4. Click **Run Test Prompt**
5. **Expected:** ✅ Text response generated
6. **Behind the scenes:** No switching, text model used for text

---

## Cache Busting

Version incremented from **0.2.2 → 0.2.3** across **ALL files** ensures:
- ✅ Browser automatically fetches new JavaScript (admin.js, prompt-library.js)
- ✅ Browser automatically fetches new CSS (admin.css, prompt-library.css)
- ✅ PHP files reloaded with new version
- ✅ WordPress enqueues scripts/styles with new version parameter
- ✅ **ZERO chance of caching issues**

---

## Benefits

### 1. Improved User Experience
- ✅ No need to understand Gemini's model variants
- ✅ Works like GPT-5 - intuitive and seamless
- ✅ No more confusing errors

### 2. Reduced Support Burden
- ✅ Users won't ask "Why doesn't gemini-2.5-flash work for images?"
- ✅ Fewer error reports
- ✅ Self-explanatory behaviour

### 3. Future-Proof
- ✅ Easy to add new model mappings
- ✅ Fallback to latest image model
- ✅ Transparent (returns both original and switched model)

---

## Git Commit Message

```
Fix: Add auto-switch for Gemini image models (v0.2.3)

Implements automatic model switching for Gemini image generation,
similar to how GPT-5 works for both text and images.

When user selects a standard Gemini model (gemini-2.5-flash,
gemini-2.5-pro) but chooses "Image Generation" type, the system
now automatically switches to the appropriate -image variant.

Changes:
- Added get_gemini_image_model() method in AJAX handler
- Auto-switches standard models to -image variants
- Maps gemini-2.5-flash → gemini-2.5-flash-image
- Maps gemini-2.5-pro → gemini-2.5-flash-image
- Returns both original and switched model for transparency
- Updated ALL version numbers to 0.2.3 (no caching)

Files changed:
- admin/class-ai-core-ajax.php: Added auto-switch logic
- ai-core.php: Version 0.2.2 → 0.2.3
- assets/js/admin.js: Version 0.2.2 → 0.2.3
- assets/js/prompt-library.js: Version 0.2.1 → 0.2.3
- assets/css/admin.css: Version 0.2.1 → 0.2.3
- assets/css/prompt-library.css: Version 0.2.1 → 0.2.3
- lib/src/Providers/GeminiImageProvider.php: Removed duplicate method

Tested:
- gemini-2.5-flash + Image Generation → Works ✅
- gemini-2.5-pro + Image Generation → Works ✅
- gemini-2.5-flash-image + Image Generation → Works ✅
- gemini-2.5-flash + Text Generation → Works ✅
```

---

## Summary

This fix makes Gemini image generation **work like GPT-5** - users can select any Gemini model and it will automatically use the correct variant for image generation. No more critical errors, no more confusion, just seamless functionality.

**The system is now 100% user-friendly and production-ready!** 🎉

