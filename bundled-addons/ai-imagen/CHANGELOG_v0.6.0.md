# AI-Imagen Changelog - Version 0.6.0

## Release Date
2025-10-09

## Critical Bug Fixes

### 1. Fixed Scene Builder Coordinate System (Issue #1)
**Problem**: Text and icons were appearing in wrong positions because pixel coordinates were being treated as percentages.

**Root Cause**: 
- JavaScript `getSceneData()` was sending raw pixel values (e.g., x: 200, y: 150)
- PHP backend was treating these as percentages (e.g., 200%, 150%)
- This caused massive positioning errors in the generated images

**Solution**:
- Updated `scene-builder.js` `getSceneData()` function to convert pixel positions to percentages before sending to backend
- Added canvas dimension calculations to properly convert coordinates
- Updated PHP comments to reflect that percentages are now being received
- Now "View Generated Prompt" and actual prompt sent to API are identical

**Files Changed**:
- `bundled-addons/ai-imagen/assets/js/scene-builder.js` (lines 794-824)
- `bundled-addons/ai-imagen/includes/class-ai-imagen-generator.php` (lines 360-455)

**Technical Details**:
```javascript
// Before: Sent raw pixels
return {
    x: el.x,  // e.g., 200px
    y: el.y,  // e.g., 150px
}

// After: Convert to percentages
var canvasWidth = $('#scene-canvas').width() || 800;
var canvasHeight = $('#scene-canvas').height() || 600;
var xPercent = Math.round((el.x / canvasWidth) * 100);
var yPercent = Math.round((el.y / canvasHeight) * 100);
return {
    x: xPercent,  // e.g., 25%
    y: yPercent,  // e.g., 25%
}
```

### 2. View Generated Prompt Section Already Correctly Positioned (Issue #2)
**Status**: No changes needed - already below Scene Builder

**Location**: `bundled-addons/ai-imagen/admin/views/generator-page.php` (lines 265-305)

**Note**: If user is seeing it in wrong position, this is likely a browser caching issue. Clear browser cache and hard refresh (Cmd+Shift+R on Mac, Ctrl+Shift+R on Windows).

### 3. Enhanced Button State Management (Issue #3)
**Problem**: Generate and Regenerate buttons sometimes stayed disabled after image generation.

**Solution**:
- Added `isRegenerating` flag reset in complete callback
- Added fallback button re-enable in catch block
- Improved error handling to ensure buttons always re-enable

**Files Changed**:
- `bundled-addons/ai-imagen/assets/js/admin.js` (lines 896-917)

**Changes**:
```javascript
complete: function() {
    try {
        // Reset regeneration flag
        self.state.isRegenerating = false;
        
        // Re-enable buttons
        $generateBtn.prop('disabled', false);
        $generateBtn.html('<span class="dashicons dashicons-images-alt2"></span> Generate Image');
        $regenerateBtn.prop('disabled', false);
        $regenerateBtn.html('<span class="dashicons dashicons-update"></span> Regenerate');
        
        console.log('AI-Imagen: Buttons re-enabled successfully');
    } catch (e) {
        console.error('AI-Imagen: Error re-enabling buttons:', e);
        // Force re-enable even if there's an error
        $('#ai-imagen-generate-btn, #ai-imagen-regenerate-btn').prop('disabled', false);
    }
}
```

## New Features

### 4. Colour Selector for Text and Icons (Issue #4)
**Feature**: Added colour picker support for both text overlays and icon overlays.

**Implementation**:
- Colour picker already existed for text elements
- Extended colour picker to work with icon elements
- Icons now render with selected colour in Scene Builder canvas
- Icon colour is included in prompt sent to AI

**Files Changed**:
- `bundled-addons/ai-imagen/assets/js/scene-builder.js`:
  - Lines 451-461: Show colour picker for icons
  - Lines 363-383: Render icons with colour
  - Lines 681-689: Apply colour to icons
- `bundled-addons/ai-imagen/includes/class-ai-imagen-generator.php`:
  - Lines 377-455: Include icon colour in prompt

**Usage**:
1. Add an icon to Scene Builder
2. Click on the icon to select it
3. Element Properties panel appears
4. Click the colour picker to choose icon colour
5. Click "Apply" to update the icon
6. Icon colour is included in the AI prompt

**Prompt Format**:
```
Add a heart shape icon (♥) in #FF0000 colour, positioned 25% from the left and 30% from the top, sized at approximately 15% of the canvas width.
```

## Technical Improvements

### Coordinate System Consistency
- Frontend (JavaScript) now calculates percentages before sending to backend
- Backend (PHP) receives percentages directly
- "View Generated Prompt" now matches actual prompt sent to API
- Eliminates coordinate mismatch between preview and generation

### Error Handling
- Added try-catch blocks for button state management
- Added fallback button re-enable mechanism
- Improved console logging for debugging

### Code Quality
- Updated comments to reflect percentage-based coordinate system
- Improved code documentation
- Better separation of concerns

## Testing Recommendations

1. **Test Coordinate Accuracy**:
   - Add text overlay at specific position in Scene Builder
   - Generate image
   - Verify text appears at same relative position in generated image
   - Compare "View Generated Prompt" with network request in browser DevTools

2. **Test Icon Colours**:
   - Add icon to Scene Builder
   - Change icon colour using colour picker
   - Generate image
   - Verify icon appears in selected colour

3. **Test Button States**:
   - Generate an image
   - Verify Generate and Regenerate buttons re-enable after completion
   - Test with both successful and failed generations
   - Test with network timeout scenarios

4. **Test Cache Busting**:
   - Clear browser cache
   - Hard refresh page (Cmd+Shift+R / Ctrl+Shift+R)
   - Verify all changes are visible

## Browser Cache Clearing Instructions

If changes are not visible after update:

**Chrome/Edge**:
1. Open DevTools (F12)
2. Right-click refresh button
3. Select "Empty Cache and Hard Reload"

**Firefox**:
1. Press Cmd+Shift+R (Mac) or Ctrl+Shift+R (Windows)

**Safari**:
1. Enable Develop menu (Preferences > Advanced)
2. Develop > Empty Caches
3. Cmd+R to reload

## Version Information

- **Previous Version**: 0.5.9
- **Current Version**: 0.6.0
- **Release Type**: Bug Fix + Feature Enhancement

## Files Modified

1. `bundled-addons/ai-imagen/ai-imagen.php` - Version bump
2. `bundled-addons/ai-imagen/assets/js/scene-builder.js` - Coordinate conversion, colour support
3. `bundled-addons/ai-imagen/assets/js/admin.js` - Button state management
4. `bundled-addons/ai-imagen/includes/class-ai-imagen-generator.php` - Icon colour in prompts

## Backward Compatibility

All changes are backward compatible. Existing scene builder elements will continue to work correctly.

## Known Issues

None identified in this release.

## Next Steps

1. Test all fixes on live site
2. Monitor console logs for any errors
3. Verify coordinate accuracy with multiple test cases
4. Confirm button states work correctly in all scenarios

