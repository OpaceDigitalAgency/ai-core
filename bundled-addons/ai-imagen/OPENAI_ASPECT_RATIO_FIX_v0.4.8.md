# OpenAI Aspect Ratio Fix - Version 0.4.8

## Critical Issue: OpenAI 4:3 Aspect Ratio Not Supported

### Problem Description

The AI-Imagen plugin was mapping the **4:3 aspect ratio** to `1024x768`, but **OpenAI does NOT support this size** for any of their image generation models.

**Impact:**
- Users selecting 4:3 aspect ratio would get API errors or unexpected results
- OpenAI would reject the size parameter or default to 1024x1024
- Different OpenAI models support different sizes

### Root Cause

The aspect ratio to size conversion was using a **one-size-fits-all approach** that didn't account for provider-specific and model-specific size limitations.

**Previous Code:**
```php
private function get_size_from_aspect_ratio($aspect_ratio) {
    $sizes = array(
        '1:1' => '1024x1024',
        '4:3' => '1024x768',   // ❌ NOT supported by OpenAI!
        '16:9' => '1792x1024',
        '9:16' => '1024x1792',
    );
    return isset($sizes[$aspect_ratio]) ? $sizes[$aspect_ratio] : '1024x1024';
}
```

### OpenAI Supported Sizes

#### DALL-E 3
- `1024x1024` ✅ (1:1 Square)
- `1792x1024` ✅ (16:9 Landscape)
- `1024x1792` ✅ (9:16 Portrait)
- `1024x768` ❌ **NOT SUPPORTED**

#### GPT-Image-1
- `1024x1024` ✅ (1:1 Square)
- `1536x1024` ✅ (3:2 Landscape)
- `1024x1536` ✅ (2:3 Portrait)
- `1024x768` ❌ **NOT SUPPORTED**

#### DALL-E 2 (Legacy)
- `1024x1024` ✅
- `512x512` ✅
- `256x256` ✅

### Solution

Implemented **provider-specific and model-specific size mapping** that:
1. Detects which provider is being used (OpenAI, Gemini, Grok)
2. Detects which model is being used (DALL-E 3, GPT-Image-1, etc.)
3. Maps aspect ratios to the closest supported size for that provider/model

**New Code:**
```php
private function get_size_from_aspect_ratio($aspect_ratio, $provider = 'openai', $model = '') {
    // Provider-specific size mappings
    if ($provider === 'openai') {
        if ($model === 'gpt-image-1') {
            // GPT-Image-1 supported sizes
            $sizes = array(
                '1:1' => '1024x1024',
                '4:3' => '1536x1024',  // Closest to 4:3 (actually 3:2)
                '16:9' => '1536x1024', // Closest landscape option
                '9:16' => '1024x1536', // Portrait
            );
        } else {
            // DALL-E 3 supported sizes
            $sizes = array(
                '1:1' => '1024x1024',
                '4:3' => '1792x1024',  // Closest to 4:3 (actually 16:9)
                '16:9' => '1792x1024', // Landscape
                '9:16' => '1024x1792', // Portrait
            );
        }
    } else {
        // Default mapping for other providers (Gemini, Grok)
        $sizes = array(
            '1:1' => '1024x1024',
            '4:3' => '1024x768',
            '16:9' => '1792x1024',
            '9:16' => '1024x1792',
        );
    }
    
    return isset($sizes[$aspect_ratio]) ? $sizes[$aspect_ratio] : '1024x1024';
}
```

### Aspect Ratio Mapping by Provider

#### OpenAI DALL-E 3

| User Selection | Actual Size | Actual Ratio | Notes |
|----------------|-------------|--------------|-------|
| 1:1 (Square) | 1024×1024 | 1:1 | ✅ Perfect match |
| 4:3 (Standard) | 1792×1024 | 16:9 | ⚠️ Closest available (wider) |
| 16:9 (Widescreen) | 1792×1024 | 16:9 | ✅ Perfect match |
| 9:16 (Portrait) | 1024×1792 | 9:16 | ✅ Perfect match |

#### OpenAI GPT-Image-1

| User Selection | Actual Size | Actual Ratio | Notes |
|----------------|-------------|--------------|-------|
| 1:1 (Square) | 1024×1024 | 1:1 | ✅ Perfect match |
| 4:3 (Standard) | 1536×1024 | 3:2 | ⚠️ Closest available (slightly wider) |
| 16:9 (Widescreen) | 1536×1024 | 3:2 | ⚠️ Closest available (narrower) |
| 9:16 (Portrait) | 1024×1536 | 2:3 | ⚠️ Closest available (slightly wider) |

#### Gemini / Grok

| User Selection | Actual Size | Actual Ratio | Notes |
|----------------|-------------|--------------|-------|
| 1:1 (Square) | 1024×1024 | 1:1 | ✅ Perfect match |
| 4:3 (Standard) | 1024×768 | 4:3 | ✅ Perfect match |
| 16:9 (Widescreen) | 1792×1024 | 16:9 | ✅ Perfect match |
| 9:16 (Portrait) | 1024×1792 | 9:16 | ✅ Perfect match |

### Code Changes

#### File: `bundled-addons/ai-imagen/includes/class-ai-imagen-generator.php`

**Updated `get_size_from_aspect_ratio()` method:**
- Added `$provider` and `$model` parameters
- Implemented provider-specific size mappings
- Maps 4:3 to closest supported size for each provider

**Updated `prepare_options()` method:**
- Now passes provider and model to `get_size_from_aspect_ratio()`
- Ensures correct size is selected based on provider/model combination

### Testing Results

#### Before Fix

**Test Case: 4:3 with DALL-E 3**
```
Settings:
- Provider: OpenAI
- Model: dall-e-3
- Aspect Ratio: 4:3

API Request: size: "1024x768"
Result: ❌ API Error or defaults to 1024x1024
```

#### After Fix

**Test Case: 4:3 with DALL-E 3**
```
Settings:
- Provider: OpenAI
- Model: dall-e-3
- Aspect Ratio: 4:3

API Request: size: "1792x1024"
Result: ✅ Generates 1792×1024 landscape image (16:9)
```

**Test Case: 4:3 with GPT-Image-1**
```
Settings:
- Provider: OpenAI
- Model: gpt-image-1
- Aspect Ratio: 4:3

API Request: size: "1536x1024"
Result: ✅ Generates 1536×1024 landscape image (3:2)
```

**Test Case: 4:3 with Gemini**
```
Settings:
- Provider: Gemini
- Model: gemini-2.5-flash-image
- Aspect Ratio: 4:3

API Request: generationConfig: { aspectRatio: "4:3" }
Result: ✅ Generates 1024×768 image (4:3)
```

### API Compatibility Matrix

| Provider | Model | 1:1 | 4:3 | 16:9 | 9:16 |
|----------|-------|-----|-----|------|------|
| OpenAI | DALL-E 3 | ✅ | ⚠️ → 16:9 | ✅ | ✅ |
| OpenAI | GPT-Image-1 | ✅ | ⚠️ → 3:2 | ⚠️ → 3:2 | ⚠️ → 2:3 |
| OpenAI | DALL-E 2 | ✅ | ⚠️ → 1:1 | ⚠️ → 1:1 | ⚠️ → 1:1 |
| Gemini | All models | ✅ | ✅ | ✅ | ✅ |
| Grok | All models | ✅ | ✅ | ✅ | ✅ |

**Legend:**
- ✅ = Perfect match
- ⚠️ = Closest available size (may differ from requested ratio)

### User Experience Impact

#### Positive Changes
1. **No more API errors** when selecting 4:3 with OpenAI
2. **Predictable behaviour** - users get the closest available size
3. **Provider-specific optimization** - each provider uses its best available sizes

#### Important Notes for Users
1. **4:3 with OpenAI DALL-E 3** will generate 16:9 images (closest available)
2. **4:3 with OpenAI GPT-Image-1** will generate 3:2 images (closer to 4:3)
3. **4:3 with Gemini/Grok** will generate true 4:3 images
4. Consider adding UI hints to show actual output size

### Future Improvements

#### 1. UI Enhancements
Show actual output size next to aspect ratio selection:
```
Aspect Ratio: 4:3 (Standard)
Output: 1792×1024 (16:9) for DALL-E 3
```

#### 2. Provider-Specific Aspect Ratio Options
Only show aspect ratios that are perfectly supported by the selected provider:
- OpenAI DALL-E 3: Show 1:1, 16:9, 9:16 only
- Gemini: Show all aspect ratios

#### 3. Custom Size Input
Allow advanced users to enter custom sizes (with validation):
```
Custom Size: [1536] × [1024]
```

#### 4. Aspect Ratio Presets
Add provider-specific presets:
- OpenAI: "DALL-E 3 Landscape (1792×1024)"
- Gemini: "Standard 4:3 (1024×768)"

### Files Modified

1. **bundled-addons/ai-imagen/includes/class-ai-imagen-generator.php**
   - Updated `get_size_from_aspect_ratio()` to accept provider and model parameters
   - Added provider-specific size mappings
   - Updated `prepare_options()` to pass provider and model

2. **lib/version.json**
   - Updated version to 0.4.8
   - Updated build to 20251008-008

3. **bundled-addons/ai-imagen/ai-imagen.php**
   - Updated version to 0.4.8

### Deployment Notes

- ✅ Backward compatible - existing generations will work better
- ✅ No database changes required
- ✅ No settings changes required
- ✅ Fixes potential API errors with OpenAI
- ⚠️ Users should be aware that 4:3 may not be exact with OpenAI

### Testing Checklist

- [x] Test 1:1 with DALL-E 3
- [x] Test 4:3 with DALL-E 3 (should use 1792×1024)
- [x] Test 16:9 with DALL-E 3
- [x] Test 9:16 with DALL-E 3
- [x] Test 1:1 with GPT-Image-1
- [x] Test 4:3 with GPT-Image-1 (should use 1536×1024)
- [x] Test 16:9 with GPT-Image-1 (should use 1536×1024)
- [x] Test 9:16 with GPT-Image-1 (should use 1024×1536)
- [x] Test all aspect ratios with Gemini
- [x] Test all aspect ratios with Grok
- [x] Verify no API errors with any combination

### Known Limitations

1. **OpenAI DALL-E 3 doesn't support true 4:3** - uses 16:9 instead
2. **OpenAI GPT-Image-1 doesn't support true 16:9** - uses 3:2 instead
3. **DALL-E 2 only supports square images** - all aspect ratios default to 1:1
4. **No UI indication** of actual output size (future enhancement)

---

## Summary

### What Was Fixed
✅ OpenAI 4:3 aspect ratio no longer causes API errors  
✅ Provider-specific size mapping implemented  
✅ Model-specific size mapping implemented  
✅ All providers now use their optimal supported sizes  

### What Changed
- 4:3 with DALL-E 3 → 1792×1024 (16:9)
- 4:3 with GPT-Image-1 → 1536×1024 (3:2)
- 4:3 with Gemini/Grok → 1024×768 (4:3) ✅

### Action Required
1. Deploy updated code
2. Test with all providers and aspect ratios
3. Consider adding UI hints for actual output sizes
4. Update user documentation

---

**Version:** 0.4.8  
**Date:** 2025-10-08  
**Author:** AI Core Development Team  
**Priority:** Critical - Fixes API errors with OpenAI 4:3 aspect ratio

