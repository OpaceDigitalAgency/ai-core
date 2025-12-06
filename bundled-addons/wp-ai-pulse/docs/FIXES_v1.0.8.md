# AI-Pulse v1.0.8 - UX Improvements & Source Grounding Fix

## Release Date
6 December 2025

## Overview
This release addresses critical UX issues, fixes Google Search Grounding source extraction, and adds intelligent keyword capitalisation.

---

## Issues Fixed

### 1. ✅ Google Search Grounding Sources Not Displaying
**Problem:** Sources tab showed "No sources available" even when Google Search Grounding was enabled.

**Root Cause:** The `ResponseNormalizer` class in AI-Core was normalising Gemini responses to OpenAI format but discarding the `groundingMetadata` that contains search sources.

**Solution:**
- Updated `lib/src/Response/ResponseNormalizer.php` to preserve Gemini grounding metadata in `_gemini_grounding` field
- Updated `AI_Pulse_Generator::extract_sources()` to check for preserved metadata first, then fall back to raw format
- Sources now display correctly with proper titles and URLs

**Files Changed:**
- `lib/src/Response/ResponseNormalizer.php` (lines 316-376)
- `bundled-addons/wp-ai-pulse/includes/class-ai-pulse-generator.php` (lines 241-274)

---

### 2. ✅ Smart Keyword Capitalisation
**Problem:** Keywords displayed in lowercase (e.g., "Daily Summary: seo" instead of "Daily Summary: SEO")

**Solution:**
- Added `smart_capitalise_keyword()` method that uses Gemini to intelligently capitalise keywords
- Handles acronyms (SEO, AI, PPC), brand names (ChatGPT, WordPress), and title case
- Implements 30-day transient caching to avoid repeated API calls
- Falls back to simple `ucwords()` if API fails

**Examples:**
- `seo` → `SEO`
- `chatgpt` → `ChatGPT`
- `web design` → `Web Design`
- `search engine optimisation` → `Search Engine Optimisation`

**Files Changed:**
- `bundled-addons/wp-ai-pulse/includes/class-ai-pulse-generator.php` (lines 502-596)

---

### 3. ✅ Test Generation Not Saving to Database
**Problem:** Generated content from test interface didn't appear in Statistics or Content Library tabs.

**Solution:**
- Updated `AI_Pulse_Ajax::test_generate()` to call `AI_Pulse_Database::store_content()`
- Test-generated content now persists and contributes to usage statistics
- Returns `stored_id` in AJAX response for confirmation

**Files Changed:**
- `bundled-addons/wp-ai-pulse/admin/class-ai-pulse-ajax.php` (lines 29-87)

---

### 4. ✅ Modernised UX & Styling
**Improvements:**
- Enhanced loading spinner with gradient background and larger size
- Improved button styling with gradient and hover effects
- Better sources display with icons and hover animations
- Warning message when no sources are available
- Improved usage stats layout
- Dark theme for JSON display
- Auto-scroll to results after generation
- Shows confirmation when content is saved to library

**Files Changed:**
- `bundled-addons/wp-ai-pulse/assets/css/admin.css` (lines 136-444)
- `bundled-addons/wp-ai-pulse/admin/views/tab-test-interface.php` (lines 142-182)

---

## Version Updates

### AI-Pulse Plugin
- **Previous:** 1.0.7
- **Current:** 1.0.8

### AI-Core Plugin
- **Previous:** 0.7.6
- **Current:** 0.7.7 (for ResponseNormalizer fix)

---

## Technical Details

### Cache Busting
All CSS and JS files use `AI_PULSE_VERSION` constant for automatic cache busting:
```php
wp_enqueue_style('ai-pulse-admin', AI_PULSE_PLUGIN_URL . 'assets/css/admin.css', array(), AI_PULSE_VERSION);
```

### Keyword Capitalisation Caching
```php
$cache_key = 'ai_pulse_cap_' . md5(strtolower($keyword));
set_transient($cache_key, $capitalised, 30 * DAY_IN_SECONDS);
```

### Grounding Metadata Preservation
```php
// In ResponseNormalizer
if (isset($response["candidates"][0]["groundingMetadata"])) {
    $normalized_response["_gemini_grounding"] = $response["candidates"][0]["groundingMetadata"];
}
```

---

## Testing Checklist

- [x] Sources display correctly when Google Search Grounding returns results
- [x] Warning message shows when no sources available
- [x] Keywords capitalise correctly (SEO, ChatGPT, etc.)
- [x] Test-generated content appears in Content Library
- [x] Statistics update after test generation
- [x] Loading states display properly
- [x] Auto-scroll to results works
- [x] Cache busting works (CSS/JS updates visible)

---

## Known Limitations

1. **Keyword Capitalisation:** First request for a new keyword will be slightly slower due to LLM call (subsequent requests use cache)
2. **Sources Availability:** Google Search Grounding may not return sources for all queries (depends on Google's search results)

---

## Deployment Notes

1. Update both AI-Core (0.7.7) and AI-Pulse (1.0.8)
2. Clear WordPress transient cache if needed: `wp transient delete --all`
3. Test with a fresh keyword to verify capitalisation works
4. Verify sources display in test interface

---

## Future Enhancements

- Add manual override for keyword capitalisation
- Implement source quality scoring
- Add source preview/summary
- Export generated content to various formats

