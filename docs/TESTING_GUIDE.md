# AI-Core Testing Guide - Version 0.0.8

## Critical Bug Fixes Testing

This guide provides comprehensive testing procedures for the critical bug fixes implemented in version 0.0.8.

---

## Pre-Testing Setup

### Requirements
1. WordPress installation (5.0+)
2. API keys for at least 2 providers (OpenAI, Anthropic, Gemini, or Grok)
3. Browser with developer console access
4. Fresh plugin installation or cleared cache

### Installation
```bash
cd wp-content/plugins
git clone https://github.com/OpaceDigitalAgency/ai-core.git ai-core
cd ai-core/ai-core-standalone
```

Activate the plugin in WordPress admin.

---

## Test Suite 1: Model Validation Bug Fix

### Objective
Verify that all valid, accessible models work without "Unknown model" errors.

### Test Cases

#### Test 1.1: Older OpenAI Models
**Models to test:** `gpt-3.5-turbo`, `gpt-3.5-turbo-16k`, `gpt-4-0613`

**Steps:**
1. Navigate to AI-Core Settings
2. Enter OpenAI API key
3. Wait for models to load
4. Select `gpt-3.5-turbo` from dropdown
5. Go to Test Prompt section
6. Enter test prompt: "Say hello in 5 words"
7. Click "Run Prompt"

**Expected Result:**
- ✅ Model appears in dropdown
- ✅ No "Unknown model" error
- ✅ Response generated successfully
- ✅ Console shows no errors

**Actual Result:** _____________

---

#### Test 1.2: Latest OpenAI Models
**Models to test:** `gpt-5`, `gpt-5-mini`, `o3`, `o3-mini`, `gpt-4.1`

**Steps:**
1. Select each model from dropdown
2. Run test prompt for each
3. Check console for errors

**Expected Result:**
- ✅ All models work without errors
- ✅ Responses generated successfully

**Actual Result:** _____________

---

#### Test 1.3: Anthropic Models
**Models to test:** `claude-sonnet-4-5-20250929`, `claude-opus-4-1-20250805`, `claude-3-5-haiku-20241022`

**Steps:**
1. Enter Anthropic API key
2. Wait for models to load
3. Test each model with prompt

**Expected Result:**
- ✅ All models work without errors
- ✅ Responses generated successfully

**Actual Result:** _____________

---

#### Test 1.4: Model Not in Static Registry
**Objective:** Test dynamic model registration

**Steps:**
1. Use browser console to manually call API with a model not in ModelRegistry
2. Example: `gpt-4-turbo-2024-04-09` (if not in registry)

**Expected Result:**
- ✅ Model is automatically registered
- ✅ Request succeeds without "Unknown model" error

**Actual Result:** _____________

---

## Test Suite 2: Intelligent Model Sorting

### Objective
Verify that models are sorted with latest/newest first, deprecated last.

### Test Cases

#### Test 2.1: OpenAI Model Order
**Steps:**
1. Enter OpenAI API key
2. Wait for models to load
3. Open model dropdown
4. Note the order of models

**Expected Order (top to bottom):**
1. `gpt-5` or `gpt-5-mini` (if available)
2. `o4-mini` or `o3` models
3. `gpt-4.1` or `gpt-4o` models
4. `gpt-4` models
5. `gpt-3.5-turbo` models
6. Deprecated models (e.g., `gpt-3.5-turbo-0301`) at bottom

**Actual Order:** _____________

---

#### Test 2.2: Anthropic Model Order
**Expected Order:**
1. `claude-sonnet-4-5-*` (latest)
2. `claude-opus-4-1-*`
3. `claude-sonnet-4-*`
4. `claude-3-7-sonnet-*`
5. `claude-3-5-*` models
6. `claude-3-*` models
7. `claude-2-*` models (deprecated)

**Actual Order:** _____________

---

#### Test 2.3: Default Model Selection
**Steps:**
1. Clear all saved settings
2. Enter API key for a provider
3. Note which model is selected by default

**Expected Result:**
- ✅ Highest priority model is selected by default
- ✅ Not an old/deprecated model

**Actual Result:** _____________

---

## Test Suite 3: UI/UX Improvements

### Test Cases

#### Test 3.1: Auto-Validation Info Box
**Steps:**
1. Navigate to AI-Core Settings
2. Look for info box in API Keys section

**Expected Result:**
- ✅ Blue info box visible
- ✅ Contains text: "Auto-Validation: API keys are automatically validated and saved when you paste them. No need to click a 'Test' button!"
- ✅ Info icon displayed

**Actual Result:** _____________

---

#### Test 3.2: Field-Level Help Text
**Steps:**
1. Check help text below each API key field

**Expected Result (when no key saved):**
- ✅ Text: "Get your [Provider] API key from their website. Keys are automatically validated and saved when you paste them."

**Expected Result (when key saved):**
- ✅ Text: "API key validated and saved. Paste a new key to replace it (auto-validates on entry)."
- ✅ Green checkmark icon visible

**Actual Result:** _____________

---

#### Test 3.3: Auto-Save Behaviour
**Steps:**
1. Paste an API key into field
2. Wait 2 seconds (debounce)
3. Observe status messages

**Expected Result:**
- ✅ Status shows "Validating..."
- ✅ Status changes to "API key saved successfully"
- ✅ Models load automatically
- ✅ No need to click Save button

**Actual Result:** _____________

---

## Test Suite 4: Provider-Specific Parameters

### Test Cases

#### Test 4.1: Standard Model Parameters
**Steps:**
1. Select a standard model (e.g., `gpt-4o`)
2. Check available parameters in settings

**Expected Parameters:**
- ✅ Temperature (0-2)
- ✅ Max Tokens (1-128000)

**Actual Result:** _____________

---

#### Test 4.2: O-Series Model Parameters (Future)
**Note:** Full dynamic parameter UI is not yet implemented, but infrastructure is ready.

**Steps:**
1. Open browser console
2. Run: 
```javascript
jQuery.post(ajaxurl, {
    action: 'ai_core_get_model_capabilities',
    nonce: aiCoreAdmin.nonce,
    model: 'o3-mini',
    provider: 'openai'
}, console.log);
```

**Expected Response:**
```json
{
  "success": true,
  "data": {
    "model": "o3-mini",
    "provider": "openai",
    "capabilities": {
      "reasoning_effort": {...},
      "max_completion_tokens": {...}
    }
  }
}
```

**Actual Result:** _____________

---

## Test Suite 5: Cross-Provider Testing

### Test Cases

#### Test 5.1: Multiple Providers Configured
**Steps:**
1. Configure API keys for all 4 providers
2. Verify all appear in dropdowns
3. Test switching between providers in Test Prompt

**Expected Result:**
- ✅ All configured providers appear in dropdowns
- ✅ Model list updates when provider changes
- ✅ Test prompts work for all providers

**Actual Result:** _____________

---

#### Test 5.2: Provider Removal
**Steps:**
1. Configure multiple providers
2. Click "Clear" button for one provider
3. Verify it's removed from dropdowns

**Expected Result:**
- ✅ Provider removed from all dropdowns
- ✅ Default provider switches to another configured provider
- ✅ No errors in console

**Actual Result:** _____________

---

## Test Suite 6: Error Handling

### Test Cases

#### Test 6.1: Invalid API Key
**Steps:**
1. Enter invalid API key
2. Wait for validation

**Expected Result:**
- ✅ Error message displayed
- ✅ Key not saved
- ✅ Models not loaded

**Actual Result:** _____________

---

#### Test 6.2: Network Error
**Steps:**
1. Disconnect internet
2. Try to fetch models

**Expected Result:**
- ✅ Fallback to cached models (if available)
- ✅ Error message if no cache
- ✅ No JavaScript errors

**Actual Result:** _____________

---

## Test Suite 7: Performance Testing

### Test Cases

#### Test 7.1: Model List Caching
**Steps:**
1. Configure API key
2. Wait for models to load
3. Refresh page
4. Note load time

**Expected Result:**
- ✅ Models load faster on second load (from cache)
- ✅ Cache duration respects settings

**Actual Result:** _____________

---

## Regression Testing

### Critical Functionality to Verify

- [ ] Settings save/load correctly
- [ ] Prompt Library CRUD operations work
- [ ] Statistics tracking works
- [ ] Add-ons page loads without errors
- [ ] Uninstall option works correctly
- [ ] No PHP errors in debug.log
- [ ] No JavaScript errors in console

---

## Browser Compatibility

Test in:
- [ ] Chrome/Edge (latest)
- [ ] Firefox (latest)
- [ ] Safari (latest)

---

## Reporting Issues

If any test fails, report with:
1. Test case number
2. Expected vs actual result
3. Browser and version
4. PHP version
5. WordPress version
6. Console errors (if any)
7. Screenshots (if applicable)

---

## Success Criteria

All tests must pass with:
- ✅ No "Unknown model" errors
- ✅ Models sorted correctly (latest first)
- ✅ Auto-validation working
- ✅ Clear UI messaging
- ✅ No console errors
- ✅ No PHP errors

---

## Next Steps After Testing

1. Document any issues found
2. Fix critical bugs
3. Perform WordPress.org compliance audit
4. Prepare for production deployment
5. Update version to 1.0.0 for release

