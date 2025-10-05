# AI-Core v0.1.8 - Image Generation Capability Fix

**Release Date:** 2025-10-05  
**Version:** 0.1.8  
**Previous Version:** 0.1.7

## Executive Summary

This release fixes a critical issue where **image generation was disabled** for models that support it:

- ✅ **GPT-5** - Now shows "Image Generation" option
- ✅ **GPT-4o** - Now shows "Image Generation" option
- ✅ **Gemini 2.5 Pro** - Now shows "Image Generation" option
- ✅ **Gemini 2.5 Flash** - Now shows "Image Generation" option
- ✅ **o3 / o3-mini** - Now shows "Image Generation" option

---

## The Problem

### User Report:
> "All models that offer image creation e.g. gpt-5, gemini 2.5 pro or flash, still have image gen disabled in the dropdown."

### Root Cause:
The model registry was missing the `'image'` capability for these models. The code was confusing:

- **Vision** (input) - Can SEE/ANALYZE images you send
- **Image Generation** (output) - Can CREATE new images

### Example:
```php
// BEFORE (BROKEN)
'gpt-5' => [
    'capabilities' => ['text', 'vision', 'reasoning', 'tooluse'],
    // ❌ Missing 'image' capability
],

// AFTER (FIXED)
'gpt-5' => [
    'capabilities' => ['text', 'vision', 'image', 'reasoning', 'tooluse'],
    // ✅ Added 'image' capability
],
```

---

## Models That Support Image Generation

### OpenAI Models

| Model | Image Generation | Vision | Notes |
|-------|-----------------|--------|-------|
| **GPT-5** | ✅ YES | ✅ YES | Native multimodal image generation |
| **GPT-5 Mini** | ✅ YES | ✅ YES | Faster, cheaper variant |
| **GPT-4o** | ✅ YES | ✅ YES | Announced April 2025 |
| **GPT-4o Mini** | ✅ YES | ✅ YES | Faster, cheaper variant |
| **o3** | ✅ YES | ✅ YES | Reasoning model with image generation |
| **o3 Mini** | ✅ YES | ✅ YES | Faster reasoning model |
| **gpt-image-1** | ✅ YES | ❌ NO | Dedicated image generation model |
| **DALL-E 3** | ✅ YES | ❌ NO | Dedicated image generation model |
| **DALL-E 2** | ✅ YES | ❌ NO | Older image generation model |

### Google Gemini Models

| Model | Image Generation | Vision | Notes |
|-------|-----------------|--------|-------|
| **Gemini 2.5 Pro** | ✅ YES | ✅ YES | Multimodal with image generation |
| **Gemini 2.5 Flash** | ✅ YES | ✅ YES | Faster variant with image generation |
| **Gemini 2.5 Flash Image** | ✅ YES | ❌ NO | Dedicated image generation model |
| **Imagen 3.0** | ✅ YES | ❌ NO | Dedicated image generation model |
| **Imagen 3.0 Fast** | ✅ YES | ❌ NO | Faster image generation |

### xAI (Grok) Models

| Model | Image Generation | Vision | Notes |
|-------|-----------------|--------|-------|
| **grok-2-image-1212** | ✅ YES | ❌ NO | Dedicated image generation model |

### Anthropic (Claude) Models

| Model | Image Generation | Vision | Notes |
|-------|-----------------|--------|-------|
| **Claude Sonnet** | ❌ NO | ✅ YES | Vision only, no image generation |
| **Claude Opus** | ❌ NO | ✅ YES | Vision only, no image generation |
| **Claude Haiku** | ❌ NO | ✅ YES | Vision only, no image generation |

---

## What Was Fixed

### 1. GPT-5 Models

**Before:**
```php
'gpt-5' => [
    'capabilities' => ['text', 'vision', 'reasoning', 'tooluse'],
],
'gpt-5-mini' => [
    'capabilities' => ['text', 'vision'],
],
```

**After:**
```php
'gpt-5' => [
    'capabilities' => ['text', 'vision', 'image', 'reasoning', 'tooluse'],
],
'gpt-5-mini' => [
    'capabilities' => ['text', 'vision', 'image'],
],
```

### 2. GPT-4o Models

**Before:**
```php
'gpt-4o' => [
    'capabilities' => ['text', 'vision', 'tooluse'],
],
'gpt-4o-mini' => [
    'capabilities' => ['text', 'vision'],
],
```

**After:**
```php
'gpt-4o' => [
    'capabilities' => ['text', 'vision', 'image', 'tooluse'],
],
'gpt-4o-mini' => [
    'capabilities' => ['text', 'vision', 'image'],
],
```

### 3. Gemini Models

**Before:**
```php
'gemini-2.5-pro' => [
    'capabilities' => ['text', 'vision', 'reasoning'],
],
'gemini-2.5-flash' => [
    'capabilities' => ['text', 'vision'],
],
```

**After:**
```php
'gemini-2.5-pro' => [
    'capabilities' => ['text', 'vision', 'image', 'reasoning'],
],
'gemini-2.5-flash' => [
    'capabilities' => ['text', 'vision', 'image'],
],
```

### 4. o3 Reasoning Models

**Before:**
```php
'o3' => [
    'capabilities' => ['text', 'reasoning'],
],
'o3-mini' => [
    'capabilities' => ['text', 'reasoning'],
],
```

**After:**
```php
'o3' => [
    'capabilities' => ['text', 'vision', 'image', 'reasoning'],
],
'o3-mini' => [
    'capabilities' => ['text', 'vision', 'image', 'reasoning'],
],
```

### 5. Added Dedicated Image Models

**New Models:**
```php
'gemini-2.5-flash-image' => [
    'provider' => 'gemini',
    'display_name' => 'Gemini 2.5 Flash Image',
    'category' => 'image',
    'capabilities' => ['image'],
],
'gemini-2.5-flash-image-preview' => [
    'provider' => 'gemini',
    'display_name' => 'Gemini 2.5 Flash Image (Preview)',
    'category' => 'image',
    'capabilities' => ['image'],
],
```

---

## How Image Generation Works

### API Endpoints by Provider

| Provider | Endpoint | Models |
|----------|----------|--------|
| **OpenAI** | `/v1/images/generations` | gpt-image-1, dall-e-3, dall-e-2 |
| **OpenAI** | `/v1/responses` (multimodal) | gpt-5, gpt-4o, o3 |
| **Gemini** | `/v1/models/{model}:generateContent` | gemini-2.5-flash-image, imagen-3.0 |
| **Gemini** | `/v1/models/{model}:generateContent` | gemini-2.5-pro, gemini-2.5-flash |
| **xAI** | `/v1/images/generations` | grok-2-image-1212 |
| **Anthropic** | ❌ None | Claude does not support image generation |

### Example: GPT-5 Image Generation

```php
// Using OpenAI Responses API
$response = \AICore\AICore::sendRequest('gpt-5', [
    ['role' => 'user', 'content' => 'Generate an image of a sunset over mountains']
], []);

// Response includes image URL
$imageUrl = $response['choices'][0]['message']['content'];
```

### Example: Gemini 2.5 Flash Image Generation

```php
// Using Gemini generateContent API
$response = \AICore\AICore::sendRequest('gemini-2.5-flash', [
    ['role' => 'user', 'content' => 'Generate an image of a sunset over mountains']
], []);

// Response includes image data
$imageData = $response['candidates'][0]['content']['parts'][0]['inlineData'];
```

---

## Testing Checklist

### Test Image Generation Dropdown

1. **Go to Settings → AI-Core**
2. **Select Provider: OpenAI**
3. **Select Model: GPT-5**
4. **Check Type dropdown:**
   - ✅ Should show "Text Generation"
   - ✅ Should show "Image Generation"

5. **Select Model: GPT-4o**
6. **Check Type dropdown:**
   - ✅ Should show "Text Generation"
   - ✅ Should show "Image Generation"

7. **Select Provider: Gemini**
8. **Select Model: Gemini 2.5 Pro**
9. **Check Type dropdown:**
   - ✅ Should show "Text Generation"
   - ✅ Should show "Image Generation"

10. **Select Model: Gemini 2.5 Flash**
11. **Check Type dropdown:**
    - ✅ Should show "Text Generation"
    - ✅ Should show "Image Generation"

### Test Prompt Library

1. **Go to Prompt Library**
2. **Create New Prompt**
3. **Select Provider: OpenAI**
4. **Select Model: GPT-5**
5. **Check Type dropdown:**
   - ✅ Should show "Text Generation"
   - ✅ Should show "Image Generation"

6. **Select Type: Image Generation**
7. **Enter prompt:** "A friendly robot waving from a seaside pier, sunset"
8. **Click "Run"**
9. **Verify:**
   - ✅ Image appears below the card
   - ✅ No errors in console

### Test All Providers

| Provider | Model | Type | Expected Result |
|----------|-------|------|-----------------|
| OpenAI | GPT-5 | Image | ✅ Image appears |
| OpenAI | GPT-4o | Image | ✅ Image appears |
| OpenAI | gpt-image-1 | Image | ✅ Image appears |
| OpenAI | DALL-E 3 | Image | ✅ Image appears |
| Gemini | Gemini 2.5 Pro | Image | ✅ Image appears |
| Gemini | Gemini 2.5 Flash | Image | ✅ Image appears |
| Gemini | gemini-2.5-flash-image | Image | ✅ Image appears |
| Gemini | Imagen 3.0 | Image | ✅ Image appears |
| xAI | grok-2-image-1212 | Image | ✅ Image appears |
| Anthropic | Claude Sonnet | Image | ❌ Option disabled |

---

## Files Changed

1. **lib/src/Registry/ModelRegistry.php** - Added `'image'` capability to 10+ models
2. **assets/js/admin.js** - Updated version to 0.1.8
3. **assets/js/prompt-library.js** - Updated version to 0.1.8
4. **assets/css/prompt-library.css** - Updated version to 0.1.8
5. **ai-core.php** - Updated AI_CORE_VERSION to 0.1.8
6. **readme.txt** - Updated stable tag to 0.1.8
7. **lib/version.json** - Updated version to 0.1.8

---

## Documentation References

### OpenAI
- **GPT-5 Image Generation:** https://platform.openai.com/docs/guides/images
- **GPT-4o Multimodal:** https://simonwillison.net/2025/Apr/1/gpt-4o-image-generation/
- **Images API:** https://platform.openai.com/docs/api-reference/images

### Google Gemini
- **Gemini 2.5 Flash Image:** https://cloud.google.com/vertex-ai/generative-ai/docs/model-reference/gemini
- **Image Generation Guide:** https://deepmind.google/technologies/gemini/flash/

### xAI
- **Grok Image Generation:** https://docs.x.ai/api/endpoints#images-generations

### Anthropic
- **Claude Vision (no image generation):** https://zenn.dev/claude/vision

---

## Known Limitations

1. **Not all models support all image sizes** - Some models only support specific aspect ratios
2. **Image generation is slower than text** - Expect 5-30 seconds per image
3. **Image generation costs more** - Check provider pricing
4. **Some models require specific prompts** - Read provider documentation

---

## Future Improvements

1. **Add image size/quality options** - Let users choose resolution
2. **Add image editing** - Support for image-to-image generation
3. **Add batch generation** - Generate multiple images at once
4. **Add image history** - Save generated images to media library

---

**End of Image Generation Fix Summary v0.1.8**

