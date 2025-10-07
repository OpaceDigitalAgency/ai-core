# AI-Imagen Changelog

## Version 0.4.0 - 2025-10-07

### Fixed Issues

#### 1. OpenAI "Invalid value: 'standard'" Error
**Problem:** When using the `gpt-image-1` model, the API returned an error: "Invalid value: 'standard'. Supported values are: 'hd'."

**Root Cause:** The `gpt-image-1` model does not support the `quality` parameter at all, but the code was always sending it.

**Solution:** Modified `lib/src/Providers/OpenAIImageProvider.php` to only include the `quality` parameter for DALL-E models (dall-e-2, dall-e-3), not for gpt-image-1.

**Files Changed:**
- `lib/src/Providers/OpenAIImageProvider.php` (lines 59-78)

**Code Changes:**
```php
// Before: Always sent quality parameter
$payload = [
    'model' => $model,
    'prompt' => trim($prompt),
    'n' => $options['n'] ?? 1,
    'size' => $options['size'] ?? '1024x1024',
    'quality' => $options['quality'] ?? 'standard'
];

// After: Only send quality for DALL-E models
$payload = [
    'model' => $model,
    'prompt' => trim($prompt),
    'n' => $options['n'] ?? 1,
    'size' => $options['size'] ?? '1024x1024'
];

// Add quality parameter only for DALL-E models (not gpt-image-1)
if ($model !== 'gpt-image-1') {
    $payload['quality'] = $options['quality'] ?? 'standard';
    $payload['response_format'] = $options['response_format'] ?? 'url';
}
```

#### 2. Gemini Imagen Models 404 Errors
**Problem:** The `imagen-3.0-generate-001` and `imagen-3.0-fast-generate-001` models were returning 404 errors.

**Root Cause:** These legacy Imagen models may not be available or require different API endpoints than the current implementation supports.

**Solution:** Removed the legacy Imagen models from the available models list, keeping only the working `gemini-2.5-flash-image` models.

**Files Changed:**
- `bundled-addons/ai-imagen/includes/class-ai-imagen-generator.php` (lines 112-120)

**Code Changes:**
```php
// Before: Included legacy Imagen models
$image_models = array(
    'gemini-2.5-flash-image',
    'gemini-2.5-flash-image-preview',
    'imagen-3.0-generate-001',
    'imagen-3.0-fast-generate-001',
);

// After: Only working models
$image_models = array(
    'gemini-2.5-flash-image',
    'gemini-2.5-flash-image-preview',
);
```

#### 3. Scene Builder Prompt Not Visible
**Problem:** When adding elements to the scene builder, users couldn't see what prompt text would be generated from their scene layout.

**Solution:** Added a new "Scene Description" preview panel that displays in real-time what text will be appended to the user's prompt based on the scene elements.

**Files Changed:**
- `bundled-addons/ai-imagen/assets/js/scene-builder.js` (multiple locations)
- `bundled-addons/ai-imagen/assets/css/generator.css` (lines 87-115)

**Features Added:**
- New `updateScenePromptPreview()` method that updates whenever elements are added, moved, resized, or deleted
- Visual preview panel showing the exact text that will be added to the prompt
- Styled with blue background to make it clearly visible
- Auto-hides when no elements are present

#### 4. Element Properties Panel Vanishing
**Problem:** The properties panel would sometimes disappear or flicker when selecting elements.

**Solution:** 
- Added null checks and default values for all properties
- Changed from `slideDown()` to conditional showing to prevent flickering
- Ensured properties panel stays visible when already shown
- Rounded position/size values for cleaner display

**Files Changed:**
- `bundled-addons/ai-imagen/assets/js/scene-builder.js` (lines 403-432)

**Code Changes:**
```javascript
// Added default values and null checks
$('#element-content').val(this.selectedElement.content || '');
$('#element-font-size').val(this.selectedElement.fontSize || 16);
$('#element-x').val(Math.round(this.selectedElement.x) || 0);

// Prevent flickering
if (!$props.is(':visible')) {
    $props.slideDown();
}
```

#### 5. Resize Not Reflecting in Scene Builder
**Problem:** When resizing elements, the changes weren't being properly tracked and the scene description wasn't updating.

**Solution:**
- Added `updateScenePromptPreview()` call after resize operations complete
- Rounded width/height values in properties panel for consistency
- Ensured resize updates are reflected in both the element and properties panel

**Files Changed:**
- `bundled-addons/ai-imagen/assets/js/scene-builder.js` (lines 513-548)

#### 6. WordPress Media Uploader Not Working
**Problem:** When clicking "Add Logo" or "Add Image", the WordPress media uploader modal wouldn't open, showing an error that `wp.media` was undefined.

**Root Cause:** The WordPress media library scripts weren't being enqueued on the AI-Imagen pages.

**Solution:** Added `wp_enqueue_media()` and proper script dependencies to ensure the WordPress media uploader is available.

**Files Changed:**
- `bundled-addons/ai-imagen/ai-imagen.php` (lines 259-328)

**Code Changes:**
```php
// Added media uploader enqueue
wp_enqueue_media();

// Added proper dependencies
wp_enqueue_script(
    'ai-imagen-scene-builder',
    AI_IMAGEN_PLUGIN_URL . 'assets/js/scene-builder.js',
    array('jquery', 'ai-imagen-generator', 'media-upload', 'media-views'),
    AI_IMAGEN_VERSION,
    true
);
```

#### 7. Icon Picker Modal Styling
**Problem:** The icon picker modal needed proper styling to match WordPress admin design patterns.

**Solution:** Added comprehensive CSS for the icon picker modal with:
- Proper overlay and modal positioning
- Grid layout for icons
- Hover effects and transitions
- Responsive design for mobile devices
- WordPress-style close button

**Files Changed:**
- `bundled-addons/ai-imagen/assets/css/generator.css` (lines 302-428)

### Improvements

1. **Better Scene Description Generation**
   - Scene descriptions now update in real-time
   - Position percentages are calculated relative to canvas size
   - Text overlays include font size, colour, and weight information
   - Icons and images include positioning details

2. **Enhanced User Experience**
   - Visual feedback for all scene builder operations
   - Clearer indication of what will be sent to the AI
   - Smoother animations and transitions
   - Better error handling

3. **Code Quality**
   - Added comprehensive comments
   - Improved null safety
   - Better separation of concerns
   - More maintainable code structure

### Testing Recommendations

1. **OpenAI Models:**
   - Test `gpt-image-1` with various prompts
   - Test `dall-e-3` with quality settings (standard, hd)
   - Test `dall-e-2` with different sizes

2. **Gemini Models:**
   - Test `gemini-2.5-flash-image` with various prompts
   - Verify `gemini-2.5-flash-image-preview` works correctly

3. **Scene Builder:**
   - Add text elements and verify positioning
   - Upload logos/images via media library
   - Select icons from the picker
   - Resize and move elements
   - Verify scene description updates in real-time
   - Test with multiple elements
   - Test delete and clear all functions

4. **Cross-Browser Testing:**
   - Chrome/Edge
   - Firefox
   - Safari
   - Mobile browsers

### Known Limitations

1. **Scene Builder AI Understanding:**
   - The AI may not perfectly interpret positioning instructions
   - Complex layouts may require prompt refinement
   - Text overlay positioning is approximate

2. **Model Availability:**
   - Some models may have regional restrictions
   - API rate limits apply per provider
   - Model availability may change over time

### Migration Notes

- No database changes required
- No settings migration needed
- Existing generated images are not affected
- Users should clear browser cache to see CSS updates

### Version Bump

- Updated from version 0.3.9 to 0.4.0
- Updated version constant in main plugin file
- Updated version in plugin header


