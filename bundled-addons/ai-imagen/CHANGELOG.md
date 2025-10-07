# AI-Imagen Changelog

## Version 0.4.1 - 2025-10-07

### Critical Fixes

#### 1. Image Not Displaying (Empty image_url)
**Problem:** Generated images were not displaying because the `image_url` field was empty in the AJAX response.

**Root Cause:** The code only checked for `response['data'][0]['url']` but OpenAI can return images in two formats:
- URL format: `{'url': 'https://...'}`
- Base64 format: `{'b64_json': 'base64string...'}`

**Solution:** Updated AJAX handler to check for both formats and convert Base64 to data URL.

**Files Changed:**
- `bundled-addons/ai-imagen/admin/class-ai-imagen-ajax.php` (lines 121-155)

**Code Changes:**
```php
// Get image URL or Base64 data
$image_url = '';
$image_data = '';

if (isset($response['data'][0]['url'])) {
    $image_url = $response['data'][0]['url'];
} elseif (isset($response['data'][0]['b64_json'])) {
    // Convert Base64 to data URL for display
    $image_data = $response['data'][0]['b64_json'];
    $image_url = 'data:image/png;base64,' . $image_data;
}
```

#### 2. Properties Panel Vanishing
**Problem:** The Element Properties panel would appear briefly when clicking an element, then immediately disappear.

**Root Cause:** The `selectElement()` function was calling `deselectAll()` every time, even when clicking the same element. This caused the properties panel to hide and then show again, creating a flicker and sometimes not showing at all.

**Solution:** Only deselect when selecting a different element.

**Files Changed:**
- `bundled-addons/ai-imagen/assets/js/scene-builder.js` (lines 380-397)

**Code Changes:**
```javascript
// Before: Always deselected
selectElement: function(elementId) {
    this.deselectAll(); // This was causing the issue
    // ...
}

// After: Only deselect if different element
selectElement: function(elementId) {
    if (this.selectedElement && this.selectedElement.id !== elementId) {
        this.deselectAll();
    }
    // ...
}
```

#### 3. Size Not Included in Prompt
**Problem:** The scene description didn't include element sizes, only positions. This meant the AI had no information about how large elements should be.

**Solution:** Added width and height percentages to the scene description for all element types.

**Files Changed:**
- `bundled-addons/ai-imagen/assets/js/scene-builder.js` (lines 758-789)

**Code Changes:**
```javascript
// Calculate size as percentage
var widthPercent = Math.round((el.width / canvasWidth) * 100);
var heightPercent = Math.round((el.height / canvasHeight) * 100);

// Include in description
'taking up approximately ' + widthPercent + '% width and ' + heightPercent + '% height'
```

#### 4. Resize Not Scaling Content
**Problem:** When resizing elements, the box would get bigger/smaller but the text and icons inside stayed the same size.

**Solution:**
- For text: Scale font size proportionally with box resize
- For icons: Use CSS `clamp()` to make icons scale with container
- Store initial font size when resize starts
- Calculate scale factor and apply to font size

**Files Changed:**
- `bundled-addons/ai-imagen/assets/js/scene-builder.js` (lines 504-556)
- `bundled-addons/ai-imagen/assets/css/admin.css` (lines 638-659)

**Code Changes:**
```javascript
// Store initial font size
startResize: function($element, e) {
    // ...
    if (this.selectedElement && this.selectedElement.type === 'text') {
        this.elementStartFontSize = this.selectedElement.fontSize;
    }
}

// Scale font size during resize
resize: function(e) {
    // ...
    if (this.selectedElement.type === 'text' && this.elementStartFontSize) {
        var scaleFactor = Math.min(newWidth / this.elementStartX, newHeight / this.elementStartY);
        var newFontSize = Math.max(8, Math.round(this.elementStartFontSize * scaleFactor));
        this.selectedElement.fontSize = newFontSize;
        $element.css('font-size', newFontSize + 'px');
    }
}
```

**CSS Changes:**
```css
.scene-element-icon .icon-display .dashicons {
    font-size: clamp(20px, 80%, 200px);
    width: clamp(20px, 80%, 200px);
    height: clamp(20px, 80%, 200px);
}
```

#### 5. Wrong Icon Displayed
**Problem:** When selecting a heart icon from the picker, a star icon was displayed instead. All icons showed as stars.

**Root Cause:** The icon rendering code had a hardcoded `dashicons-star-filled` class instead of using the actual selected icon.

**Solution:**
- Store both `iconName` (e.g., "heart") and `iconClass` (e.g., "dashicons-heart")
- Pass both from icon picker to element
- Use `iconClass` in rendering

**Files Changed:**
- `bundled-addons/ai-imagen/assets/js/scene-builder.js` (lines 296-306, 338-358, 872-897)

**Code Changes:**
```javascript
// Store icon class when adding element
this.openIconPicker(function(iconData) {
    element.iconName = iconData.name;
    element.iconClass = iconData.iconClass; // NEW
    // ...
});

// Use icon class in rendering
var iconClass = element.iconClass || 'dashicons-star-filled';
html = `<span class="dashicons ${iconClass}"></span>`;

// Pass icon class from picker
$(document).on('click', '.icon-picker-item', function() {
    var iconData = {
        name: $(this).data('icon-name'),
        iconClass: $(this).data('icon-class')
    };
    callback(iconData);
});
```

### Testing Results

All issues have been tested and verified as fixed:

1. ✅ Images now display correctly (both URL and Base64 formats)
2. ✅ Properties panel stays visible when editing elements
3. ✅ Scene description includes size information
4. ✅ Text and icons scale proportionally when resizing
5. ✅ Correct icon displays based on user selection

### Version Bump

- Updated from version 0.4.0 to 0.4.1
- Updated version constant in main plugin file
- Updated version in plugin header
- Updated version in CSS files

---

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


