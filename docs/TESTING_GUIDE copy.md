# AI-Imagen Testing Guide
**Version:** 0.4.0  
**Date:** 2025-10-07

---

## Quick Start Testing

### 1. Test OpenAI gpt-image-1 (Previously Broken)

**Steps:**
1. Go to AI-Imagen > Generate
2. Select Provider: **OpenAI**
3. Select Model: **gpt-image-1**
4. Enter prompt: "A beautiful sunset over mountains"
5. Click "Generate Image"

**Expected Result:**
- ✅ Image generates successfully
- ✅ No "Invalid value: 'standard'" error
- ✅ Image displays in preview area

**What Was Fixed:**
- The quality parameter is no longer sent for gpt-image-1 model

---

### 2. Test Gemini Models (Previously Had 404 Errors)

**Steps:**
1. Go to AI-Imagen > Generate
2. Select Provider: **Gemini**
3. Check available models in dropdown

**Expected Result:**
- ✅ Only see: `gemini-2.5-flash-image` and `gemini-2.5-flash-image-preview`
- ✅ No `imagen-3.0-generate-001` or `imagen-3.0-fast-generate-001`
- ✅ Selected model generates images without 404 errors

**What Was Fixed:**
- Removed non-working legacy Imagen models from the list

---

### 3. Test Scene Builder - Real-Time Preview (New Feature)

**Steps:**
1. Go to AI-Imagen > Generate
2. Scroll to Scene Builder section
3. Click "Add Text"
4. Enter text: "SALE 50% OFF"
5. Move the text element around the canvas
6. Resize the text element

**Expected Result:**
- ✅ Blue "Scene Description" panel appears below canvas
- ✅ Shows text like: "Add a text overlay with the text 'SALE 50% OFF' positioned X% from the left and Y% from the top..."
- ✅ Description updates in real-time as you move/resize
- ✅ Position percentages change as you drag

**What Was Fixed:**
- Added new real-time scene description preview panel
- Updates automatically on all element changes

---

### 4. Test Properties Panel (Previously Vanishing)

**Steps:**
1. In Scene Builder, add a text element
2. Click on the text element to select it
3. Properties panel should appear on the right
4. Change the font size
5. Change the colour
6. Move the element by dragging
7. Resize the element

**Expected Result:**
- ✅ Properties panel appears and stays visible
- ✅ No flickering or disappearing
- ✅ Values update as you drag/resize
- ✅ All fields show rounded numbers (no decimals)
- ✅ Panel remains visible throughout all operations

**What Was Fixed:**
- Added null checks and default values
- Improved animation logic to prevent flickering
- Added rounded values for cleaner display

---

### 5. Test WordPress Media Uploader (Previously Not Working)

**Steps:**
1. In Scene Builder, click "Add Logo" or "Add Image"
2. WordPress media library modal should open
3. Select an existing image or upload a new one
4. Click "Use this image"

**Expected Result:**
- ✅ Media library modal opens correctly
- ✅ No "wp.media is undefined" error in console
- ✅ Can browse existing images
- ✅ Can upload new images
- ✅ Selected image appears in scene builder canvas

**What Was Fixed:**
- Added `wp_enqueue_media()` to properly load WordPress media scripts
- Added correct script dependencies

---

### 6. Test Icon Picker (New Feature)

**Steps:**
1. In Scene Builder, click "Add Icon"
2. Icon picker modal should open
3. Browse through available icons
4. Click on an icon to select it

**Expected Result:**
- ✅ Beautiful modal opens with grid of icons
- ✅ 30+ common icons available (star, heart, checkmark, arrows, etc.)
- ✅ Hover effects work smoothly
- ✅ Selected icon appears in scene builder canvas
- ✅ Modal closes after selection
- ✅ Can close modal with X button or clicking outside

**What Was Fixed:**
- Added comprehensive CSS styling for icon picker
- Professional WordPress-style design

---

### 7. Test Complete Workflow

**Steps:**
1. Go to AI-Imagen > Generate
2. Select Provider: **OpenAI**
3. Select Model: **gpt-image-1**
4. Enter prompt: "Professional product photo of a smartphone"
5. In Scene Builder:
   - Add text: "NEW"
   - Position it in top-left corner
   - Add an icon (star)
   - Position it in top-right corner
6. Observe the Scene Description panel
7. Click "Generate Image"

**Expected Result:**
- ✅ Scene description shows both text and icon positioning
- ✅ Image generates successfully with gpt-image-1
- ✅ No errors in console
- ✅ Image displays in preview
- ✅ Can save to media library

---

## Detailed Testing Scenarios

### Scenario A: Multiple Elements

**Test:**
1. Add 3 text elements with different content
2. Add 2 icons
3. Add 1 logo/image
4. Move them all to different positions
5. Resize some of them

**Verify:**
- Scene description updates for each change
- Properties panel works for each element
- All elements maintain their properties
- Can delete individual elements
- Can clear all elements

---

### Scenario B: Edge Cases

**Test:**
1. Add element at canvas edge (0,0)
2. Add element at canvas bottom-right
3. Resize element to very small size
4. Resize element to very large size
5. Add many elements (10+)

**Verify:**
- Elements stay within bounds
- Minimum size is enforced (20px)
- Scene description handles all positions
- Performance remains good with many elements

---

### Scenario C: Different Models

**Test each model:**
- OpenAI: gpt-image-1, dall-e-3, dall-e-2
- Gemini: gemini-2.5-flash-image, gemini-2.5-flash-image-preview
- Grok: grok-2-image-1212

**Verify:**
- All models generate images successfully
- No parameter errors
- Quality settings work for DALL-E models
- Size settings work correctly

---

## Browser Testing

### Chrome/Edge
- ✅ Test all functionality
- ✅ Check console for errors
- ✅ Verify media uploader works

### Firefox
- ✅ Test all functionality
- ✅ Check console for errors
- ✅ Verify CSS renders correctly

### Safari
- ✅ Test all functionality
- ✅ Check console for errors
- ✅ Verify drag/drop works

### Mobile Browsers
- ✅ Test responsive design
- ✅ Verify touch interactions
- ✅ Check icon picker on small screens

---

## Console Checks

### What to Look For

**No Errors:**
- No "Invalid value: 'standard'" errors
- No "wp.media is undefined" errors
- No 404 errors for Gemini models
- No JavaScript errors

**Expected Console Messages:**
- AJAX requests to `admin-ajax.php`
- Successful responses with image URLs
- Scene builder state updates (if debug enabled)

---

## Performance Testing

### Scene Builder Performance

**Test:**
1. Add 20 elements to scene builder
2. Move them around rapidly
3. Resize multiple elements
4. Delete and re-add elements

**Verify:**
- UI remains responsive
- No lag or stuttering
- Scene description updates smoothly
- Properties panel updates quickly

---

## Regression Testing

### Ensure Nothing Broke

**Test:**
1. Basic image generation without scene builder
2. Prompt enhancement feature
3. Save to media library
4. Image history
5. Settings page
6. Statistics page

**Verify:**
- All existing features still work
- No new errors introduced
- UI remains consistent

---

## API Testing

### OpenAI API

**Test:**
```
Model: gpt-image-1
Prompt: "Test image"
Expected: Success, no quality parameter sent
```

```
Model: dall-e-3
Prompt: "Test image"
Quality: hd
Expected: Success, quality parameter sent
```

### Gemini API

**Test:**
```
Model: gemini-2.5-flash-image
Prompt: "Test image"
Expected: Success, Base64 image returned
```

---

## Troubleshooting

### If Tests Fail

1. **Clear Browser Cache**
   - Hard refresh: Cmd+Shift+R (Mac) or Ctrl+Shift+R (Windows)

2. **Check Console**
   - Open DevTools (F12)
   - Look for JavaScript errors
   - Check Network tab for failed requests

3. **Verify API Keys**
   - Go to AI-Core > Settings
   - Ensure API keys are configured
   - Test API connections

4. **Check WordPress Version**
   - Requires WordPress 5.0+
   - Requires PHP 7.4+

5. **Verify Plugin Version**
   - Should be 0.4.0
   - Check in Plugins page

---

## Success Criteria

### All Tests Pass When:

- ✅ gpt-image-1 generates images without errors
- ✅ Only working Gemini models are shown
- ✅ Scene description preview updates in real-time
- ✅ Properties panel stays visible and doesn't flicker
- ✅ All element changes are reflected immediately
- ✅ WordPress media uploader opens correctly
- ✅ Icon picker works smoothly
- ✅ No console errors
- ✅ All existing features still work
- ✅ Performance is good with multiple elements

---

## Reporting Issues

### If You Find a Bug

**Include:**
1. Browser and version
2. WordPress version
3. Plugin version (should be 0.4.0)
4. Steps to reproduce
5. Expected vs actual behaviour
6. Console errors (if any)
7. Screenshots/video if possible

---

## Test Results Template

```
Date: ___________
Tester: ___________
Browser: ___________

[ ] OpenAI gpt-image-1 works
[ ] Gemini models work (no 404s)
[ ] Scene description preview works
[ ] Properties panel doesn't vanish
[ ] Media uploader works
[ ] Icon picker works
[ ] No console errors
[ ] All existing features work

Notes:
_________________________________
_________________________________
_________________________________
```

---

## Conclusion

This testing guide covers all the fixes implemented in version 0.4.0. Follow these tests to verify that all issues have been resolved and no regressions have been introduced.

**Happy Testing! 🎉**


