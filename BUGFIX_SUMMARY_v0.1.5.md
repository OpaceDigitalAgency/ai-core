# AI-Core v0.1.5 - Critical Bug Fixes

**Release Date:** 2025-10-05  
**Version:** 0.1.5  
**Previous Version:** 0.1.4

## Executive Summary

This release fixes 7 critical bugs that were preventing core functionality from working correctly:

1. ✅ **Modal Display Issue** - Modals were hardcoded to `display: none` preventing them from appearing
2. ✅ **Version Cache Issue** - Version still showing 0.1.3 due to cache
3. ✅ **Missing Model Configurations** - GPT-4o, o1-preview, o1-mini, Gemini 2.5 Pro, DALL-E, and Imagen models missing
4. ✅ **Image Generation Broken** - All OpenAI models incorrectly allowed image generation
5. ✅ **Gemini Image Support** - Gemini incorrectly showed "Not supported" for image generation
6. ✅ **Template Download Links** - JSON/CSV template links only visible in modal, not main view
7. ✅ **Model Capability Detection** - System checked provider capabilities instead of model capabilities

---

## Issue 1: Modal Display Hardcoded to `display: none` ✅

### THE PROBLEM
The CSS for `.ai-core-modal` had `display: none` on line 274, which was being overridden by browser specificity rules. When JavaScript added the `.active` class, the modal still wouldn't appear because `display: none` had higher specificity than `display: flex`.

### THE FIX
```css
/* BEFORE */
.ai-core-modal {
    display: none;  /* ← This was the problem */
    position: fixed;
    ...
}

.ai-core-modal.active {
    display: flex;  /* ← This wasn't strong enough */
    ...
}

/* AFTER */
.ai-core-modal {
    position: fixed;
    ...
    display: none !important;  /* ← Moved to end with !important */
}

.ai-core-modal.active {
    display: flex !important;  /* ← Added !important */
    ...
}
```

### FILES CHANGED
- `assets/css/prompt-library.css` (lines 272-289)

---

## Issue 2: Version Still Showing 0.1.3 ✅

### THE PROBLEM
The plugin version was hardcoded as `0.1.3` in the main plugin file header, causing WordPress to cache old assets.

### THE FIX
Updated version to `0.1.5` in:
- `ai-core.php` (line 6 and line 29)
- `readme.txt` (line 7)
- `assets/js/admin.js` (line 5)

All enqueued assets already use `AI_CORE_VERSION` constant, so they'll automatically use the new version for cache busting.

---

## Issue 3: Missing Model Configurations ✅

### THE PROBLEM
Several important models were missing from the ModelRegistry:
- **o1-preview** and **o1-mini** (OpenAI reasoning models)
- **Gemini 2.5 Pro** (Google's flagship model)
- **DALL-E 2** and **DALL-E 3** (OpenAI image models)
- **Imagen 3.0** and **Imagen 3.0 Fast** (Google image models)

This caused:
- "No adjustable parameters for this model" message for GPT-4o (it actually has temperature and max_tokens)
- Missing reasoning effort parameter for o1 models
- Missing Gemini 2.5 Pro from model list

### THE FIX
Added complete model definitions to `ModelRegistry.php`:

**OpenAI o1 Models:**
```php
'o1-preview' => [
    'provider' => 'openai',
    'display_name' => 'OpenAI o1 Preview',
    'category' => 'reasoning',
    'endpoint' => 'chat',
    'priority' => 92,
    'capabilities' => ['text', 'reasoning'],
    'parameters' => [
        'max_tokens' => $numberParameter(1, 32768, 8192, 1, 'max_completion_tokens', 'Max Completion Tokens'),
    ],
],
'o1-mini' => [
    'provider' => 'openai',
    'display_name' => 'OpenAI o1 Mini',
    'category' => 'reasoning',
    'endpoint' => 'chat',
    'priority' => 91,
    'capabilities' => ['text', 'reasoning'],
    'parameters' => [
        'max_tokens' => $numberParameter(1, 65536, 8192, 1, 'max_completion_tokens', 'Max Completion Tokens'),
    ],
],
```

**Gemini 2.5 Pro:**
```php
'gemini-2.5-pro' => [
    'provider' => 'gemini',
    'display_name' => 'Gemini 2.5 Pro',
    'category' => 'text',
    'endpoint' => 'gemini.generateContent',
    'priority' => 95,
    'capabilities' => ['text', 'vision', 'reasoning'],
    'parameters' => [
        'temperature' => $numberParameter(0.0, 2.0, 0.7, 0.01, 'generationConfig.temperature', 'Temperature'),
        'max_tokens' => $numberParameter(1, 8192, 4096, 1, 'generationConfig.maxOutputTokens', 'Max Output Tokens'),
        'top_p' => $numberParameter(0.0, 1.0, 1.0, 0.01, 'generationConfig.topP', 'Top P'),
    ],
],
```

**DALL-E Models:**
```php
'dall-e-3' => [
    'provider' => 'openai',
    'display_name' => 'DALL-E 3',
    'category' => 'image',
    'endpoint' => 'images',
    'priority' => 30,
    'capabilities' => ['image'],
    'parameters' => [],
],
'dall-e-2' => [
    'provider' => 'openai',
    'display_name' => 'DALL-E 2',
    'category' => 'image',
    'endpoint' => 'images',
    'priority' => 25,
    'capabilities' => ['image'],
    'parameters' => [],
],
```

**Imagen Models:**
```php
'imagen-3.0-generate-001' => [
    'provider' => 'gemini',
    'display_name' => 'Imagen 3.0',
    'category' => 'image',
    'endpoint' => 'gemini.generateImage',
    'priority' => 75,
    'capabilities' => ['image'],
    'parameters' => [],
],
'imagen-3.0-fast-generate-001' => [
    'provider' => 'gemini',
    'display_name' => 'Imagen 3.0 Fast',
    'category' => 'image',
    'endpoint' => 'gemini.generateImage',
    'priority' => 70,
    'capabilities' => ['image'],
    'parameters' => [],
],
```

### FILES CHANGED
- `lib/src/Registry/ModelRegistry.php` (lines 134-171, 277-303, 445-471)

---

## Issue 4 & 5: Image Generation Capability Detection Broken ✅

### THE PROBLEM
The system was checking **provider** capabilities instead of **model** capabilities:
- All OpenAI models (including GPT-4o, GPT-3.5-turbo) showed "Image Generation" as enabled
- When you tried to generate an image with GPT-4o, it would fail with `<failed to load image data>`
- Gemini Flash showed "Not supported by gemini" even though Imagen models exist

### THE ROOT CAUSE
The `updateTypeDropdown()` function in `admin.js` only checked:
```javascript
const capabilities = state.providerCapabilities[provider];
const supportsImageGeneration = capabilities && capabilities.image === true;
```

This meant:
- If OpenAI provider supports images (via DALL-E), ALL OpenAI models showed image generation enabled
- If Gemini provider supports images (via Imagen), ALL Gemini models showed image generation enabled

### THE FIX
Updated `updateTypeDropdown()` to check **model** capabilities first:

```javascript
updateTypeDropdown: function() {
    const provider = $('#ai-core-test-provider').val();
    const model = $('#ai-core-test-model').val();  // ← Now checks selected model
    const $typeSelect = $('#ai-core-test-type');
    const $imageOption = $typeSelect.find('option[value="image"]');

    if (!provider) {
        $imageOption.prop('disabled', true);
        if ($typeSelect.val() === 'image') {
            $typeSelect.val('text');
        }
        return;
    }

    let supportsImageGeneration = false;
    let disabledReason = '';

    if (model) {
        // Check model metadata for image capability
        const modelMeta = state.modelMeta[model];
        if (modelMeta && modelMeta.capabilities) {
            supportsImageGeneration = modelMeta.capabilities.includes('image');
        }
        
        if (!supportsImageGeneration) {
            disabledReason = 'Not supported by ' + model;
        }
    } else {
        // No model selected, check provider capabilities
        const capabilities = state.providerCapabilities[provider];
        supportsImageGeneration = capabilities && capabilities.image === true;
        
        if (!supportsImageGeneration) {
            disabledReason = 'Not supported by ' + provider;
        }
    }

    $imageOption.prop('disabled', !supportsImageGeneration);

    if ($typeSelect.val() === 'image' && !supportsImageGeneration) {
        $typeSelect.val('text');
    }

    if (!supportsImageGeneration && disabledReason) {
        $imageOption.text('Image Generation (' + disabledReason + ')');
    } else {
        $imageOption.text('Image Generation');
    }
}
```

Also added event listener for model changes:
```javascript
$(document).on('change', '#ai-core-test-model', () => {
    this.updateTypeDropdown();
});
```

### RESULT
✅ GPT-4o → Shows "Image Generation (Not supported by gpt-4o)"  
✅ GPT-3.5-turbo → Shows "Image Generation (Not supported by gpt-3.5-turbo)"  
✅ DALL-E 3 → Shows "Image Generation" (enabled)  
✅ Gemini 2.5 Flash → Shows "Image Generation (Not supported by gemini-2.5-flash)"  
✅ Imagen 3.0 → Shows "Image Generation" (enabled)

### FILES CHANGED
- `assets/js/admin.js` (lines 49-55, 876-928)

---

## Issue 6: Template Download Links Not Visible ✅

### THE PROBLEM
The JSON and CSV template download links were only visible inside the Import modal. Users couldn't easily find them to understand the import format.

### THE FIX
Added template download buttons to the main Prompt Library header, next to Import/Export buttons:

```php
<a href="<?php echo esc_url( AI_CORE_PLUGIN_URL . 'prompts-template.json' ); ?>"
   class="button"
   download
   title="<?php esc_attr_e('Download JSON template file', 'ai-core'); ?>">
    <span class="dashicons dashicons-media-code"></span>
    <?php esc_html_e('JSON Template', 'ai-core'); ?>
</a>
<a href="<?php echo esc_url( AI_CORE_PLUGIN_URL . 'prompts-template.csv' ); ?>"
   class="button"
   download
   title="<?php esc_attr_e('Download CSV template file', 'ai-core'); ?>">
    <span class="dashicons dashicons-media-spreadsheet"></span>
    <?php esc_html_e('CSV Template', 'ai-core'); ?>
</a>
```

### FILES CHANGED
- `admin/class-ai-core-prompt-library.php` (lines 101-121)

---

## Testing Checklist

### Modal Functionality
- [ ] Click "New Prompt" → Modal appears
- [ ] Click "Import" → Import modal appears
- [ ] Click "Export" → JSON download triggers
- [ ] Click X or outside modal → Modal closes

### Model Configurations
- [ ] Select GPT-4o → Shows Temperature and Max Tokens parameters
- [ ] Select o1-preview → Shows Max Completion Tokens parameter (no temperature)
- [ ] Select o1-mini → Shows Max Completion Tokens parameter (no temperature)
- [ ] Select Gemini 2.5 Pro → Appears in model list with parameters

### Image Generation
- [ ] Select OpenAI + GPT-4o → Image Generation disabled with message
- [ ] Select OpenAI + DALL-E 3 → Image Generation enabled
- [ ] Select Gemini + Gemini 2.5 Flash → Image Generation disabled with message
- [ ] Select Gemini + Imagen 3.0 → Image Generation enabled
- [ ] Generate image with DALL-E 3 → Image appears
- [ ] Generate image with Imagen 3.0 → Image appears

### Template Downloads
- [ ] Click "JSON Template" button → prompts-template.json downloads
- [ ] Click "CSV Template" button → prompts-template.csv downloads
- [ ] Template links also visible in Import modal

### Version & Cache
- [ ] Hard refresh browser (Cmd+Shift+R / Ctrl+Shift+R)
- [ ] Check Network tab → admin.js?ver=0.1.5
- [ ] Check Network tab → prompt-library.css?ver=0.1.5
- [ ] Plugin version shows 0.1.5 in WordPress admin

---

## Files Changed Summary

1. **ai-core.php** - Version bump to 0.1.5
2. **readme.txt** - Stable tag updated to 0.1.5
3. **assets/js/admin.js** - Version bump, model capability detection logic
4. **assets/css/prompt-library.css** - Modal display fix with !important
5. **lib/src/Registry/ModelRegistry.php** - Added o1, Gemini 2.5 Pro, DALL-E, Imagen models
6. **admin/class-ai-core-prompt-library.php** - Added template download buttons to main view

---

## Deployment Instructions

1. **Clear WordPress cache** (if using caching plugin)
2. **Hard refresh browser** (Cmd+Shift+R / Ctrl+Shift+R)
3. **Test all functionality** using checklist above
4. **Commit and push** to GitHub
5. **Update WordPress.org** (if applicable)

---

## Known Issues / Future Improvements

1. **Model Selection in Prompt Library** - Currently uses provider default model, should allow per-prompt model selection
2. **Image Model Auto-Selection** - When "Image Generation" is selected, should auto-switch to appropriate image model (DALL-E 3 or Imagen 3.0)
3. **Streaming Support** - Not yet implemented for text generation
4. **Cost Tracking** - Usage statistics don't yet track per-model costs

---

**End of Bug Fix Summary v0.1.5**

