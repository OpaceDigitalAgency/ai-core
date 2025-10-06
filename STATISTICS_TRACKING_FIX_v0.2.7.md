# Statistics Tracking Fix - Version 0.2.7

## Critical Bug Fix: Statistics Not Being Tracked

### Problem Identified
After implementing the comprehensive statistics system in v0.2.5, users reported that **no statistics were being displayed** even after running prompts from both the Settings page and Prompt Library.

### Root Cause
The AJAX handlers were calling the AI-Core library directly (`\AICore\AICore::sendTextRequest()` and `\AICore\AICore::generateImage()`) instead of using the `AI_Core_API` class which contains the `track_usage()` method. This meant that **statistics were never being tracked** for any API calls made through the admin interface.

**Affected Files:**
- `admin/class-ai-core-ajax.php` - Settings page test prompt handler
- `admin/class-ai-core-prompt-library-ajax.php` - Prompt Library run prompt handler

### Solution Implemented

#### 1. Updated Settings Page Test Prompt Handler
**File:** `admin/class-ai-core-ajax.php`

**Before:**
```php
$result = \AICore\AICore::sendTextRequest($model, $messages, $options);
$text_response = \AICore\AICore::extractContent($result);
```

**After:**
```php
// Use AI_Core_API to ensure statistics tracking
$api = AI_Core_API::get_instance();
$result = $api->send_text_request($model, $messages, $options);

// Check for WP_Error
if (is_wp_error($result)) {
    wp_send_json_error(array('message' => $result->get_error_message()));
}

$text_response = \AICore\AICore::extractContent($result);
```

**For Image Generation:**
```php
// Use AI_Core_API to ensure statistics tracking
$api = AI_Core_API::get_instance();
$result = $api->generate_image($prompt_content, $image_options, $provider);

// Check for WP_Error
if (is_wp_error($result)) {
    wp_send_json_error(array('message' => $result->get_error_message()));
}
```

#### 2. Updated Prompt Library Run Prompt Handler
**File:** `admin/class-ai-core-prompt-library-ajax.php`

**Before:**
```php
$result = \AICore\AICore::sendTextRequest($model, $messages, $options);
$text = $result['choices'][0]['message']['content'] ?? $result['content'][0]['text'] ?? '';
```

**After:**
```php
// Use AI_Core_API to ensure statistics tracking
$api = AI_Core_API::get_instance();
$result = $api->send_text_request($model, $messages, $options);

// Check for WP_Error
if (is_wp_error($result)) {
    wp_send_json_error(array('message' => $result->get_error_message()));
}

// Use the library's extractContent method to properly extract text
$text = \AICore\AICore::extractContent($result);
```

#### 3. Enhanced Image Generation Tracking
**File:** `includes/class-ai-core-api.php`

**Improvement:** Extract actual model name from options or response instead of generic `'image-provider'`:

```php
// Track usage if enabled - use actual model from options or response
$model = $options['model'] ?? $response['model'] ?? 'image-' . $provider;
$this->track_usage($model, $response);
```

#### 4. Image Generation Token Tracking
**File:** `includes/class-ai-core-api.php`

**Enhancement:** Properly detect and track image generation requests:

```php
// Check if this is an image generation request
$is_image = (strpos($model, 'dall-e') !== false || 
             strpos($model, 'imagen') !== false || 
             strpos($model, 'grok-') !== false && strpos($model, 'image') !== false ||
             strpos($model, 'gemini-') !== false && strpos($model, 'image') !== false ||
             strpos($model, 'image-') === 0);

if ($is_image) {
    // For image generation, count as 1 image (represented as 1 output token for cost calculation)
    $output_tokens = 1;
    $total_tokens = 1;
}
```

This ensures that:
- Image generation is properly detected
- Each image counts as 1 "output token" for cost calculation
- Pricing database can calculate per-image costs correctly

### Benefits of the Fix

✅ **Statistics now track correctly** for all API calls  
✅ **Proper error handling** with WP_Error checks  
✅ **Consistent API usage** through `AI_Core_API` class  
✅ **Image generation tracking** with actual model names  
✅ **Cost calculations work** for both text and images  

### Version Updates for Cache Busting

To ensure users get the updated code without caching issues, **all version numbers were updated to 0.2.7**:

**Updated Files:**
- `ai-core.php` - Plugin header, @version, and `AI_CORE_VERSION` constant
- `admin/class-ai-core-ajax.php` - @version in file header
- `assets/css/admin.css` - @version in file header

**Cache Busting:**
- All `wp_enqueue_script()` and `wp_enqueue_style()` calls use `AI_CORE_VERSION` constant
- Browser and WordPress will automatically load new versions

### Testing Instructions

1. **Clear Browser Cache** (or use incognito/private mode)
2. **Navigate to Settings Page:**
   - Go to AI-Core > Settings
   - Enter a test prompt
   - Select a provider and model
   - Click "Test Prompt"
   - Verify response appears

3. **Check Statistics:**
   - Go to AI-Core > Statistics
   - You should now see:
     - Total Usage Summary with request count
     - Usage by Provider
     - Usage by Model with costs

4. **Test Prompt Library:**
   - Go to AI-Core > Prompt Library
   - Run any saved prompt
   - Check Statistics page again
   - Verify new usage appears

5. **Test Image Generation:**
   - Use Settings page with image-capable provider
   - Generate an image
   - Check Statistics for image model tracking

### Expected Results

After running prompts, the Statistics page should display:

**Total Usage Summary:**
- Total Requests: [count]
- Input Tokens: [count]
- Output Tokens: [count]
- Total Tokens: [count]
- Total Cost: $[amount]
- Errors: [count]
- Models Used: [count]
- Providers: [count]

**Usage by Provider:**
Table showing each provider with requests, tokens, and costs

**Usage by Model:**
Detailed table showing each model used with:
- Model name
- Provider
- Requests
- Input/Output/Total tokens
- Cost
- Errors
- Last used timestamp

### Technical Notes

**Why This Happened:**
- The AJAX handlers were implemented before the statistics tracking system
- They used direct library calls for simplicity
- When statistics were added, the AJAX handlers weren't updated to use the API wrapper

**Why This Fix Works:**
- `AI_Core_API` class wraps all library calls
- `track_usage()` method is called automatically after each request
- Proper error handling with WP_Error
- Consistent code path for all API calls

**Architecture Improvement:**
- All API calls now go through `AI_Core_API` class
- Single point of control for tracking, error handling, and future enhancements
- Follows WordPress best practices

### Files Modified

1. **admin/class-ai-core-ajax.php** - Use API class for tracking
2. **admin/class-ai-core-prompt-library-ajax.php** - Use API class for tracking
3. **includes/class-ai-core-api.php** - Enhanced image tracking
4. **ai-core.php** - Version bump to 0.2.7
5. **assets/css/admin.css** - Version bump to 0.2.7

### Commit Message

```
v0.2.7: Fix statistics tracking - AJAX handlers now use API class

CRITICAL BUG FIX: Statistics were not being tracked

PROBLEM:
- Users reported blank statistics page even after running prompts
- AJAX handlers called AI-Core library directly
- Bypassed AI_Core_API class which has track_usage() method
- No statistics were ever recorded

SOLUTION:
- Updated Settings page test prompt handler to use AI_Core_API
- Updated Prompt Library run prompt handler to use AI_Core_API
- Enhanced image generation tracking with actual model names
- Added proper WP_Error handling
- Image generation now counts as 1 output token for cost calculation

BENEFITS:
- Statistics now track correctly for all API calls
- Proper error handling with WP_Error checks
- Consistent API usage through AI_Core_API class
- Image generation tracking with actual model names
- Cost calculations work for both text and images

VERSION UPDATES (cache busting):
- ai-core.php: 0.2.7
- admin/class-ai-core-ajax.php: 0.2.7
- assets/css/admin.css: 0.2.7
- All enqueued assets use AI_CORE_VERSION constant

TESTING:
- Run test prompts from Settings page
- Run prompts from Prompt Library
- Check Statistics page for tracked usage
- Verify costs are calculated correctly
```

---

**Version:** 0.2.7  
**Date:** October 2025  
**Status:** ✅ FIXED - Statistics tracking now works correctly  
**Developed by:** Opace Digital Agency

