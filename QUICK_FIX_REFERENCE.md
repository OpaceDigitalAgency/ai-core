# Quick Fix Reference - AI-Core v0.1.5

## What Was Fixed

### 1. Modals Not Appearing ✅
**Problem:** Modals had `display: none` hardcoded  
**Solution:** Added `!important` to CSS rules  
**Test:** Click "New Prompt" or "Import" - modals should appear

### 2. Version Showing 0.1.3 ✅
**Problem:** Old version cached  
**Solution:** Bumped to 0.1.5 in all files  
**Test:** Hard refresh (Cmd+Shift+R), check Network tab for `?ver=0.1.5`

### 3. GPT-4o "No Adjustable Parameters" ✅
**Problem:** GPT-4o missing from ModelRegistry  
**Solution:** GPT-4o already existed, just needed proper display  
**Test:** Select GPT-4o → Should show Temperature and Max Tokens

### 4. o1 Models Missing Reasoning Parameter ✅
**Problem:** o1-preview and o1-mini not in registry  
**Solution:** Added both models with max_completion_tokens parameter  
**Test:** Select o1-preview → Should show Max Completion Tokens (no temperature)

### 5. All OpenAI Models Allow Image Generation ✅
**Problem:** Checked provider capabilities instead of model capabilities  
**Solution:** Updated `updateTypeDropdown()` to check model metadata first  
**Test:** 
- GPT-4o → "Image Generation (Not supported by gpt-4o)"
- DALL-E 3 → "Image Generation" (enabled)

### 6. Gemini Says "Not Supported" for Images ✅
**Problem:** Same as #5 - checked provider instead of model  
**Solution:** Same fix - check model capabilities  
**Test:**
- Gemini 2.5 Flash → "Image Generation (Not supported by gemini-2.5-flash)"
- Imagen 3.0 → "Image Generation" (enabled)

### 7. No JSON Download Link Visible ✅
**Problem:** Template links only in Import modal  
**Solution:** Added "JSON Template" and "CSV Template" buttons to main header  
**Test:** Should see buttons next to Import/Export in Prompt Library

---

## What You Need to Do Now

### Step 1: Update Your WordPress Plugin
```bash
# If you're using local development:
cd /path/to/wordpress/wp-content/plugins/ai-core
git pull origin main

# Or manually upload the updated files to your WordPress installation
```

### Step 2: Clear Cache
1. **WordPress Cache:** If using WP Rocket, W3 Total Cache, etc., clear cache
2. **Browser Cache:** Hard refresh (Cmd+Shift+R on Mac, Ctrl+Shift+R on Windows)
3. **Verify:** Check Network tab in DevTools - should see `admin.js?ver=0.1.5`

### Step 3: Test Everything

#### Test Modals
- [ ] Click "New Prompt" → Modal appears
- [ ] Click "Import" → Import modal appears  
- [ ] Click "Export" → JSON downloads
- [ ] Click X or outside modal → Modal closes

#### Test Model Parameters
- [ ] Select OpenAI → GPT-4o → Should show Temperature and Max Tokens
- [ ] Select OpenAI → o1-preview → Should show Max Completion Tokens only
- [ ] Select OpenAI → o1-mini → Should show Max Completion Tokens only
- [ ] Select Gemini → Gemini 2.5 Pro → Should appear in list with parameters

#### Test Image Generation
- [ ] Select OpenAI → GPT-4o → Image Generation disabled with message
- [ ] Select OpenAI → DALL-E 3 → Image Generation enabled
- [ ] Select Gemini → Gemini 2.5 Flash → Image Generation disabled with message
- [ ] Select Gemini → Imagen 3.0 → Image Generation enabled
- [ ] Try generating image with DALL-E 3 → Should work
- [ ] Try generating image with Imagen 3.0 → Should work

#### Test Template Downloads
- [ ] Click "JSON Template" button → prompts-template.json downloads
- [ ] Click "CSV Template" button → prompts-template.csv downloads

---

## If Something Still Doesn't Work

### Modals Still Not Appearing?
1. Open DevTools → Elements tab
2. Find `<div id="ai-core-prompt-modal">`
3. Check if it has `class="ai-core-modal active"`
4. Check computed styles - should show `display: flex`
5. If still `display: none`, check for conflicting CSS

### Version Still Showing 0.1.3?
1. Check Network tab in DevTools
2. Look for `admin.js?ver=0.1.3` or `prompt-library.css?ver=0.1.3`
3. If found, clear browser cache more aggressively:
   - Chrome: Settings → Privacy → Clear browsing data → Cached images and files
   - Firefox: Preferences → Privacy → Clear Data → Cached Web Content
4. Or use Incognito/Private mode to test

### Image Generation Still Broken?
1. Open Console tab in DevTools
2. Check for JavaScript errors
3. Verify `state.modelMeta` contains model capabilities:
   ```javascript
   console.log(state.modelMeta['gpt-4o']);
   // Should show: { capabilities: ['text', 'vision', 'tooluse'], ... }
   
   console.log(state.modelMeta['dall-e-3']);
   // Should show: { capabilities: ['image'], ... }
   ```
4. If metadata is missing, check that ModelRegistry is loading correctly

### Template Links Not Visible?
1. Check that files exist:
   - `wp-content/plugins/ai-core/prompts-template.json`
   - `wp-content/plugins/ai-core/prompts-template.csv`
2. Check browser Console for 404 errors
3. Verify `AI_CORE_PLUGIN_URL` constant is correct

---

## Technical Details

### Files Changed
1. **ai-core.php** - Version 0.1.5
2. **readme.txt** - Stable tag 0.1.5
3. **assets/js/admin.js** - Model capability detection
4. **assets/css/prompt-library.css** - Modal display fix
5. **lib/src/Registry/ModelRegistry.php** - Added models
6. **admin/class-ai-core-prompt-library.php** - Template buttons

### Key Code Changes

**Modal CSS Fix:**
```css
.ai-core-modal {
    display: none !important;  /* ← Added !important */
}
.ai-core-modal.active {
    display: flex !important;  /* ← Added !important */
}
```

**Model Capability Detection:**
```javascript
// BEFORE: Only checked provider
const capabilities = state.providerCapabilities[provider];
const supportsImageGeneration = capabilities && capabilities.image === true;

// AFTER: Checks model first, then provider
if (model) {
    const modelMeta = state.modelMeta[model];
    if (modelMeta && modelMeta.capabilities) {
        supportsImageGeneration = modelMeta.capabilities.includes('image');
    }
}
```

**New Models Added:**
- o1-preview (OpenAI reasoning)
- o1-mini (OpenAI reasoning)
- Gemini 2.5 Pro (Google flagship)
- DALL-E 2 (OpenAI image)
- DALL-E 3 (OpenAI image)
- Imagen 3.0 (Google image)
- Imagen 3.0 Fast (Google image)

---

## Commit Info

**Commit:** 9139fa5  
**Branch:** main  
**Pushed:** 2025-10-05  
**GitHub:** https://github.com/OpaceDigitalAgency/ai-core

---

## Next Steps (Future Improvements)

1. **Auto-switch to image model** - When "Image Generation" is selected, automatically switch to DALL-E 3 or Imagen 3.0
2. **Per-prompt model selection** - Allow selecting specific model for each saved prompt
3. **Streaming support** - Implement streaming for text generation
4. **Cost tracking** - Add per-model cost tracking in usage statistics
5. **Model grouping** - Group models by category (text, reasoning, image) in dropdown

---

**End of Quick Fix Reference**

