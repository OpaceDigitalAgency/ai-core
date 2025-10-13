# AI-Stats v0.2.1 Fixes

**Date:** 2025-10-13  
**Status:** In Progress

## Critical Gaps Addressed

### 1. Debug & Diagnostics Page ✅
**Problem:** No visibility into data sources, fetch status, or why fetch returns nothing

**Solution:**
- Created `admin/views/debug-page.php` with 4 tabs:
  - **Data Sources**: Shows all 60+ sources with type, URL, tags, update frequency
  - **Test Fetch**: Test any mode and see raw results
  - **Configuration**: View API keys status and AI-Core availability
  - **Cache Status**: View and clear cache
- Added debug menu item to admin
- Shows source count, status, and test buttons for each source

**Files:**
- `admin/views/debug-page.php` (NEW)
- `admin/class-ai-stats-admin.php` (added render_debug_page method)

### 2. Enhanced Error Logging ✅
**Problem:** Errors silently swallowed, no way to diagnose fetch failures

**Solution:**
- Added try/catch in `fetch_from_source()` with WP_DEBUG logging
- Enhanced RSS fetch with detailed error logging
- Added fallback values for missing RSS fields (title, URL, published date)
- Log candidate count for each source when WP_DEBUG is enabled

**Files:**
- `includes/class-ai-stats-adapters.php` (enhanced error handling)

### 3. Better AJAX Error Responses ✅
**Problem:** Generic "no candidates" error with no debug info

**Solution:**
- Return sources_count in error response
- Return debug_url link to debug page
- Return mode and keywords for troubleshooting
- Enhanced JavaScript to show debug link in error message

**Files:**
- `admin/class-ai-stats-ajax.php` (enhanced fetch_candidates)
- `assets/js/admin.js` (enhanced error display)

## Remaining Critical Gaps

### 4. API Implementations (PARTIAL)
**Status:** Some APIs return empty arrays

**Current State:**
- ✅ ONS API: Implemented (returns empty but structure is correct)
- ✅ CrUX API: Implemented (requires Google API key)
- ✅ Bank Holidays API: Implemented
- ⚠️ Companies House API: Returns empty array after auth
- ⚠️ Generic API: Returns empty array (needs specific parsing)

**Next Steps:**
- Implement Companies House search parsing
- Add specific parsers for common API formats (JSON:API, HAL, etc.)
- Test with real API keys

### 5. Keyword Filtering ✅
**Status:** IMPLEMENTED (was already there)

**Location:** `includes/class-ai-stats-adapters.php` line 467-483
- Filters candidates by matching keywords in title or blurb_seed
- Case-insensitive search
- Returns candidates that match ANY keyword

### 6. HTML Extraction ✅
**Status:** IMPLEMENTED (was already there)

**Location:** `includes/class-ai-stats-adapters.php` line 396-426
- Uses DOMDocument and DOMXPath
- Looks for percentage patterns
- Extracts text nodes
- Returns up to 5 candidates per HTML source

### 7. Rollback Functionality ❌
**Status:** NOT IMPLEMENTED

**Specification:** "Revert to previous active" admin action

**Next Steps:**
- Add "History" tab to dashboard showing previous modules
- Add "Revert" button for each historical entry
- Implement revert logic (deactivate current, activate previous)

### 8. Library Page UI ❌
**Status:** STUB ONLY

**Current:** Basic placeholder page exists
**Needed:** Full CRUD interface for saved modules

**Next Steps:**
- List all saved modules (active and inactive)
- Show metadata (mode, generated_at, sources, LLM status)
- Actions: View, Edit, Delete, Activate, Duplicate
- Filters: By mode, by date, by LLM status

### 9. Performance Tracking ❌
**Status:** DATABASE ONLY

**Current:** Tables exist but no recording logic
**Needed:** Track impressions and clicks

**Next Steps:**
- Add impression tracking to shortcode
- Add click tracking to links
- Implement analytics dashboard
- Show performance metrics per mode

### 10. Style Variants (PARTIAL)
**Status:** Only 'inline' implemented in LLM-OFF path

**Current:** LLM-ON uses format_content() which only does inline
**Needed:** Support for 'box', 'cards', 'list' styles

**Next Steps:**
- Implement format_content() variants for each style
- Add CSS for each style variant
- Test with different styles

## Testing Checklist

### Debug Page
- [ ] Navigate to AI-Stats → Debug
- [ ] Verify all sources are listed
- [ ] Test fetch for each mode
- [ ] Check configuration tab shows API keys
- [ ] Test cache clear

### Error Handling
- [ ] Enable WP_DEBUG
- [ ] Fetch with no API keys
- [ ] Check error log for detailed messages
- [ ] Verify error messages show debug link
- [ ] Click debug link and verify it works

### Data Fetching
- [ ] Test each mode in debug page
- [ ] Verify RSS feeds return data
- [ ] Test with API keys configured
- [ ] Test without API keys
- [ ] Verify keyword filtering works

## Known Issues

### RSS Feeds May Be Slow
- Some RSS feeds take 5-10 seconds to fetch
- WordPress fetch_feed() has 30-second timeout
- Consider implementing async fetching in future

### Cache May Hide Issues
- 10-minute cache means changes take time to reflect
- Use "Clear All Cache" button in debug page
- Or wait 10 minutes between tests

### API Keys Required
- Many sources require API keys
- Without keys, those sources return empty
- This is expected behavior
- Debug page shows which keys are missing

## Next Version (v0.2.2) Priorities

1. **Implement Rollback** - High priority for safety
2. **Complete Library Page** - Medium priority for usability
3. **Fix Companies House API** - Medium priority for data quality
4. **Add Style Variants** - Low priority (workaround: use LLM-ON)
5. **Performance Tracking** - Low priority (future feature)

## Deployment Notes

### Before Deploying
1. Test debug page thoroughly
2. Enable WP_DEBUG and check logs
3. Test with and without API keys
4. Verify error messages are helpful

### After Deploying
1. Monitor error logs for fetch failures
2. Check debug page for source status
3. Test each mode and verify data quality
4. Gather user feedback on error messages

---

**Status:** Debug infrastructure complete, ready for testing
**Next:** Test with real WordPress installation and API keys

