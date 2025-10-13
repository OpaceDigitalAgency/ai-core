# Bug Fix: Prompt Library Model Selection v0.2.8

**Date:** 2025-10-06  
**Version:** 0.2.8  
**Status:** ✅ Fixed

---

## 🐛 Problem Summary

The Prompt Library page was not using the correct AI models configured in Settings, causing:

1. **Wrong Model Used**: Prompts were using hardcoded fallback models instead of the user's selected models from Settings
2. **Invalid Model Errors**: Gemini prompts failed with "model not found" errors because it was using `gemini-2.0-flash-exp` (which doesn't exist) instead of the configured model
3. **Statistics Mismatch**: Statistics page showed incorrect models being used (e.g., showing `gpt-4o` when user had `gpt-5` configured)
4. **Inconsistent Behaviour**: Settings page test interface worked correctly, but Prompt Library page did not

---

## 🔍 Root Cause Analysis

### Issue 1: JavaScript Not Passing Model Parameter

**File:** `assets/js/prompt-library.js`

The `runPromptInCard()` and `runPrompt()` functions were only sending `provider` and `type` to the backend, but not the `model` parameter:

```javascript
// BEFORE (Lines 784-792)
$.ajax({
    url: aiCoreAdmin.ajaxUrl,
    type: 'POST',
    data: {
        action: 'ai_core_run_prompt',
        nonce: aiCoreAdmin.nonce,
        prompt: content,
        provider: provider,  // ✅ Sent
        type: type           // ✅ Sent
        // ❌ model: NOT SENT
    },
```

### Issue 2: Backend Using Hardcoded Fallback Models

**File:** `admin/class-ai-core-prompt-library-ajax.php`

When no model was provided, the backend used hardcoded fallback models that didn't match user settings:

```php
// BEFORE (Lines 384-393)
if (empty($model)) {
    $model_map = array(
        'openai' => 'gpt-4o',
        'anthropic' => 'claude-sonnet-4-20250514',
        'gemini' => 'gemini-2.0-flash-exp',  // ❌ This model doesn't exist!
        'grok' => 'grok-2-1212',
    );
    $model = $model_map[$provider] ?? 'gpt-4o';
}
```

### Issue 3: Not Reading User's Saved Model Settings

The backend never checked the `provider_models` setting that stores the user's selected model for each provider.

---

## ✅ Solution Implemented

### Fix 1: JavaScript Now Passes Model Parameter

**File:** `assets/js/prompt-library.js`

Updated three functions to retrieve and pass the selected model from settings:

#### A. `runPromptInCard()` Function (Lines 771-800)

```javascript
// AFTER
runPromptInCard: function($card, content, provider, type, $button) {
    // If no provider specified, use default from settings
    if (!provider || provider === 'default' || provider === '') {
        provider = aiCoreAdmin.providers?.default || 'openai';
    }

    // ✅ Get the selected model for this provider from settings
    let model = '';
    if (aiCoreAdmin.providers?.selectedModels && aiCoreAdmin.providers.selectedModels[provider]) {
        model = aiCoreAdmin.providers.selectedModels[provider];
    }

    $.ajax({
        url: aiCoreAdmin.ajaxUrl,
        type: 'POST',
        data: {
            action: 'ai_core_run_prompt',
            nonce: aiCoreAdmin.nonce,
            prompt: content,
            provider: provider,
            model: model,  // ✅ Now sent!
            type: type
        },
```

#### B. `runPromptFromModal()` Function (Lines 860-887)

```javascript
// AFTER
runPromptFromModal: function(e) {
    e.preventDefault();

    const content = $('#prompt-content').val();
    let provider = $('#prompt-provider').val();
    const type = $('#prompt-type').val();

    if (!content) {
        alert('Please enter prompt content');
        return;
    }

    // If no provider specified, use default from settings
    if (!provider || provider === 'default' || provider === '') {
        provider = aiCoreAdmin.providers?.default || 'openai';
    }

    // ✅ Get the selected model for this provider from settings
    let model = '';
    if (aiCoreAdmin.providers?.selectedModels && aiCoreAdmin.providers.selectedModels[provider]) {
        model = aiCoreAdmin.providers.selectedModels[provider];
    }

    this.runPrompt(content, provider, type, model);  // ✅ Pass model
},
```

#### C. `runPrompt()` Function (Lines 889-906)

```javascript
// AFTER
runPrompt: function(content, provider, type, model) {  // ✅ Accept model parameter
    const $result = $('#ai-core-prompt-result');
    $result.show().html('<div class="loading"><span class="ai-core-spinner"></span> Running prompt...</div>');

    $.ajax({
        url: aiCoreAdmin.ajaxUrl,
        type: 'POST',
        data: {
            action: 'ai_core_run_prompt',
            nonce: aiCoreAdmin.nonce,
            prompt: content,
            provider: provider,
            model: model || '',  // ✅ Now sent!
            type: type
        },
```

### Fix 2: Backend Now Reads User's Saved Model Settings

**File:** `admin/class-ai-core-prompt-library-ajax.php`

Updated the `ajax_run_prompt()` function to use a proper fallback hierarchy:

```php
// AFTER (Lines 384-406)
// Determine model based on provider if not specified
if (empty($model)) {
    // ✅ First, try to get the saved model from settings
    if (!empty($settings['provider_models'][$provider])) {
        $model = $settings['provider_models'][$provider];
    } else {
        // ✅ Fallback to preferred model from ModelRegistry
        if (class_exists('AICore\\Registry\\ModelRegistry')) {
            $model = \AICore\Registry\ModelRegistry::getPreferredModel($provider);
        }
        
        // Final fallback to hardcoded defaults
        if (empty($model)) {
            $model_map = array(
                'openai' => 'gpt-4o',
                'anthropic' => 'claude-sonnet-4-20250514',
                'gemini' => 'gemini-2.5-flash',  // ✅ Fixed to valid model
                'grok' => 'grok-2-1212',
            );
            $model = $model_map[$provider] ?? 'gpt-4o';
        }
    }
}
```

**Fallback Hierarchy:**
1. ✅ **User's saved model** from `provider_models` setting (highest priority)
2. ✅ **ModelRegistry preferred model** (if available)
3. ✅ **Hardcoded defaults** (last resort, now with valid models)

---

## 📋 Files Changed

1. **`ai-core-standalone/assets/js/prompt-library.js`**
   - Updated `runPromptInCard()` to retrieve and pass model from settings
   - Updated `runPromptFromModal()` to retrieve and pass model from settings
   - Updated `runPrompt()` to accept and use model parameter
   - Version updated to 0.2.8

2. **`ai-core-standalone/admin/class-ai-core-prompt-library-ajax.php`**
   - Updated `ajax_run_prompt()` to read user's saved model from settings
   - Implemented proper fallback hierarchy for model selection
   - Fixed hardcoded Gemini model from `gemini-2.0-flash-exp` to `gemini-2.5-flash`

3. **`ai-core-standalone/ai-core.php`**
   - Updated plugin version from 0.2.7 to 0.2.8

---

## ✅ Testing Checklist

- [ ] **Settings Page**: Verify model selection works and is saved correctly
- [ ] **Prompt Library - Run from Card**: Verify prompts use the correct model configured in Settings
- [ ] **Prompt Library - Run from Modal**: Verify test prompts use the correct model
- [ ] **Statistics Page**: Verify correct model is tracked in statistics
- [ ] **Network Tab**: Verify correct model is sent in AJAX requests
- [ ] **Gemini Prompts**: Verify Gemini prompts work without "model not found" errors
- [ ] **OpenAI Prompts**: Verify OpenAI prompts use configured model (e.g., gpt-5 if configured)
- [ ] **Default Provider**: Verify prompts with no provider specified use default provider and its model
- [ ] **Multiple Providers**: Test switching between providers and verify each uses its own configured model

---

## 🎯 Expected Behaviour After Fix

1. **Prompt Library uses Settings models**: When you run a prompt, it should use the model you configured in Settings for that provider
2. **No more invalid model errors**: Gemini prompts should work without "model not found" errors
3. **Statistics show correct models**: The Statistics page should show the actual models being used
4. **Consistent with Settings page**: Prompt Library should behave the same as the Settings test interface
5. **Proper fallback**: If no model is configured, it falls back to ModelRegistry preferred model, then hardcoded defaults

---

## 🔄 Comparison: Before vs After

| Aspect | Before (v0.2.7) | After (v0.2.8) |
|--------|----------------|----------------|
| **Model Source** | Hardcoded fallbacks only | User settings → ModelRegistry → Fallbacks |
| **Gemini Default** | `gemini-2.0-flash-exp` (invalid) | User's configured model or `gemini-2.5-flash` |
| **JavaScript** | Didn't pass model parameter | Passes model from settings |
| **Statistics** | Showed wrong models | Shows correct models |
| **Consistency** | Settings ≠ Prompt Library | Settings = Prompt Library ✅ |

---

## 📝 Notes

- The Prompt Library database table stores only `provider` and `type`, not `model`, because models are provider-specific settings
- The model is retrieved from the `provider_models` setting at runtime
- This matches the behaviour of the Settings page test interface
- Cache busting is handled by the version number increment (0.2.8)

---

## 🚀 Deployment

1. Clear browser cache to load new JavaScript version
2. Test with different providers (OpenAI, Gemini, Anthropic, Grok)
3. Verify statistics tracking shows correct models
4. Confirm no console errors in browser developer tools

