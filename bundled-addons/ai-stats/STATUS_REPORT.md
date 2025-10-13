# AI-Stats Plugin - Complete Status Report

**Date:** 2025-10-13  
**Current Version:** 0.2.3  
**Status:** 🟡 PARTIALLY WORKING - NEEDS FURTHER FIXES

---

## 🎯 WHAT WE'RE TRYING TO DO

Create a WordPress plugin that:
1. **Fetches real-time data** from authoritative sources (ONS, RSS feeds, APIs)
2. **Extracts statistics** (numbers, percentages, trends) from that data
3. **Uses AI** (optionally) to analyze content and extract relevant data points
4. **Generates SEO-friendly content modules** with factual, data-driven bullets
5. **Displays on website** via shortcode `[ai_stats_module]`

### Use Case Example:
- User selects "Industry Trends" mode
- Enters keyword "SEO"
- Plugin fetches latest SEO articles from Search Engine Journal, Moz, etc.
- AI extracts statistics like "45% of businesses increased SEO spend in 2024"
- Displays 2-3 factual bullets on the website

---

## ✅ WHAT'S WORKING

### 1. RSS Feed Fetching ✓
- **Industry Trends mode** successfully fetches from:
  - Search Engine Journal
  - Moz Blog
  - Google Search Blog
  - Smashing Magazine
- Returns article titles, URLs, dates
- Extracts content from RSS descriptions

### 2. ONS API Fetching ✓
- **Statistics mode** now fetches from 3 ONS datasets:
  - Retail Sales Index
  - Employment Rate
  - GDP
- Returns actual numerical data (not empty)

### 3. Basic Plugin Infrastructure ✓
- Admin dashboard works
- Mode switching works
- Settings page functional
- Shortcode system in place
- Database tables created
- Caching system operational

### 4. Debug Page ✓
- Shows pipeline visualization
- Lists all registered sources
- Displays fetch results
- Shows filtering and ranking

---

## ❌ WHAT'S NOT WORKING

### 1. **CRITICAL: Most Sources Return Empty** ❌

**Problem:**
- Out of 28 registered sources, only ~6 return data
- Many sources marked as "API" are not actually APIs
- Examples of empty sources:
  - Birmingham City Observatory (not an API)
  - WMCA Data (HTML page, not API)
  - Eurostat (needs specific endpoint)
  - World Bank (needs specific endpoint)
  - Companies House (requires API key)

**Why:**
- Sources were registered with wrong type (API vs HTML vs RSS)
- No specific implementations for each API
- URLs point to homepages, not actual data endpoints

**Impact:** 
- Statistics mode returns very limited data
- Birmingham mode returns almost nothing
- Service+Benefit mode returns nothing

---

### 2. **CRITICAL: AI Enhancement Not Extracting Useful Data** ❌

**Problem:**
- AI returns generic statements like:
  - "AI-driven environments require content that is both human-readable and machine-interpretable"
  - "Effective communication during site migrations is crucial for SEO professionals"
- These are NOT statistics - they're just article summaries
- No actual numbers, percentages, or quantifiable data

**Why:**
- AI prompt is too vague
- Not specifically asking for NUMBERS ONLY
- Processing full article content (too much noise)
- Not validating that output contains actual statistics

**Impact:**
- "Use AI" checkbox wastes API credits
- Output is not useful for SEO content
- Doesn't meet the goal of "data-driven content"

---

### 3. **Model Selection Issue** ⚠️

**Problem:**
- Plugin uses hardcoded `gpt-4o-mini` model
- Doesn't respect AI-Core's default model setting
- User can't see which model is being used

**Why:**
- Code has fallback to hardcoded model
- Not reading from AI-Core settings properly

**Impact:**
- User confusion about which model is being used
- Can't control costs by switching models
- Inconsistent with AI-Core settings

---

### 4. **No Visibility of AI Calls** ⚠️

**Problem:**
- AI calls happen server-side (PHP)
- Not visible in browser network tab
- User can't see what's being sent to AI
- No way to verify AI is actually being called

**Why:**
- AJAX handler calls AI-Core internally
- Response is processed before sending to browser

**Impact:**
- User can't debug AI issues
- Can't verify API credits are being used correctly
- No transparency in what data is sent to AI

---

### 5. **Article Content Extraction Too Expensive** ⚠️

**Problem:**
- Original implementation fetched full article HTML for every RSS item
- This makes 10+ HTTP requests per fetch
- Slow and resource-intensive
- Many sites block scraping

**Status:** PARTIALLY FIXED
- Now extracts statistics from RSS content only
- Doesn't fetch full articles automatically
- Faster and more reliable

---

## 🔧 FIXES APPLIED IN v0.2.3

### Fix 1: Enhanced ONS API ✓
- Now fetches from 3 datasets instead of 1
- Returns actual data with numbers
- Proper error handling

### Fix 2: Improved AI Prompt ✓
- Changed prompt to request NUMBERS ONLY
- Added validation (checks if output contains digits)
- Reduced token usage (200 instead of 300)
- Lower temperature (0.1 instead of 0.3)
- Limits processing to first 5 candidates

### Fix 3: Better RSS Handling ✓
- Extracts statistics from RSS content directly
- Doesn't fetch full articles (too expensive)
- Uses `extract_statistics_from_text()` method
- Looks for percentages, large numbers, growth keywords

### Fix 4: Model Selection Fix ✓
- Now reads from AI-Core settings: `$ai_core_settings['default_model']`
- Falls back to `gpt-4o-mini` only if not set
- Logs which model is being used (when WP_DEBUG enabled)

### Fix 5: Enhanced Debug Logging ✓
- Logs every fetch attempt
- Shows which sources return empty
- Tracks AI processing
- Visible in WordPress debug.log

---

## 🚨 CRITICAL ISSUES REMAINING

### Issue #1: Source Registry Needs Complete Overhaul
**Priority:** HIGH

**Problem:** Most sources are incorrectly configured

**Solution Needed:**
1. Audit all 28 sources
2. Identify which are actually APIs vs HTML pages
3. Remove sources that don't have accessible data
4. Add specific implementations for each real API
5. Update source types (API → HTML where needed)

**Estimated Effort:** 4-6 hours

---

### Issue #2: AI Prompt Needs Fundamental Redesign
**Priority:** HIGH

**Problem:** AI is not extracting statistics, just summarizing

**Solution Needed:**
1. **Two-stage approach:**
   - Stage 1: Extract ONLY sentences with numbers
   - Stage 2: AI validates and formats those sentences
2. **Pre-filter content:**
   - Use regex to find sentences with numbers FIRST
   - Only send those to AI
   - Reduces tokens and improves accuracy
3. **Strict output format:**
   - Require format: `[NUMBER]% - [CONTEXT]`
   - Reject any output without numbers
   - Example: "45% - UK businesses increased digital marketing spend in 2024"

**Estimated Effort:** 2-3 hours

---

### Issue #3: Need Source-Specific Implementations
**Priority:** MEDIUM

**Problem:** Generic handlers don't work for most APIs

**Solution Needed:**
Implement specific handlers for:
- Eurostat API (needs dataset codes)
- World Bank API (needs indicator codes)
- Companies House API (needs API key + search endpoint)
- Birmingham City Observatory (HTML scraping)
- WMCA Data (HTML scraping)

**Estimated Effort:** 6-8 hours

---

### Issue #4: Need Better User Feedback
**Priority:** MEDIUM

**Problem:** User can't see what's happening

**Solution Needed:**
1. Add "AI Processing" indicator in UI
2. Show which model is being used
3. Display token usage after generation
4. Show which sources returned data vs empty
5. Add "View Debug Log" button

**Estimated Effort:** 2-3 hours

---

## 📊 CURRENT DATA SOURCES STATUS

### Working Sources (6/28):
1. ✅ Search Engine Journal (RSS)
2. ✅ Moz Blog (RSS)
3. ✅ Google Search Blog (RSS)
4. ✅ Smashing Magazine (RSS)
5. ✅ ONS API - Retail Sales
6. ✅ ONS API - Employment Rate
7. ✅ ONS API - GDP

### Empty/Broken Sources (22/28):
- ❌ Eurostat (needs implementation)
- ❌ World Bank (needs implementation)
- ❌ Companies House (needs API key)
- ❌ Birmingham City Observatory (wrong type)
- ❌ Birmingham.gov.uk News (RSS might be broken)
- ❌ WMCA Data (wrong type)
- ❌ ONS Regional (needs implementation)
- ❌ Google Search Status (needs implementation)
- ❌ Mozilla Search General (needs implementation)
- ❌ CrUX API (needs API key)
- ❌ UK Bank Holidays (implementation exists but not tested)
- ❌ Google Trends Daily (needs implementation)
- ❌ Eurotrends (needs implementation)
- ❌ Marketing Benchmarks (needs implementation)
- ❌ Think with Google (needs implementation)
- ❌ WordStream Benchmarks (needs implementation)
- ❌ HubSpot Marketing (needs implementation)
- ❌ UK Tax Deadlines (needs implementation)
- ❌ UK Retail Holidays (needs implementation)
- ❌ Hidden Women's Group (needs implementation)
- ❌ Smashing Magazine UX (duplicate?)
- ❌ Broadside Magazine UX (needs implementation)

---

## 🎯 RECOMMENDED NEXT STEPS

### Immediate (Next Session):
1. **Fix AI Extraction** (2-3 hours)
   - Implement two-stage extraction
   - Pre-filter with regex
   - Strict output validation
   - Test with real content

2. **Audit Source Registry** (1-2 hours)
   - Identify working vs broken sources
   - Remove or fix broken sources
   - Update source types
   - Document which need implementation

### Short-term (Next 1-2 days):
3. **Implement Top 5 Priority Sources** (4-6 hours)
   - Eurostat API
   - World Bank API
   - Birmingham City Observatory (HTML)
   - Google Trends
   - HubSpot Marketing Stats

4. **Add User Feedback** (2-3 hours)
   - Show AI processing status
   - Display model and token usage
   - Better error messages
   - Debug log viewer

### Medium-term (Next week):
5. **Complete Source Implementations** (8-10 hours)
   - Implement all remaining sources
   - Add proper error handling
   - Test each source individually
   - Document API requirements

6. **Optimize Performance** (2-3 hours)
   - Reduce unnecessary API calls
   - Improve caching strategy
   - Batch AI processing
   - Add rate limiting

---

## 💰 COST ANALYSIS

### Current AI Usage:
- **Model:** gpt-4o-mini (should be user's default)
- **Tokens per candidate:** ~200-300 tokens
- **Candidates processed:** Up to 5 per fetch
- **Total per fetch:** ~1000-1500 tokens
- **Cost:** ~$0.001-0.002 per fetch (very cheap)

### Concern:
- If processing ALL candidates (12+), cost increases
- If using expensive model (GPT-4), cost increases significantly
- Current limit of 5 candidates is good cost control

---

## 🔍 TESTING RECOMMENDATIONS

### To Test Current State:
1. Enable WP_DEBUG in wp-config.php
2. Go to Debug page
3. Select "Industry Trends" mode
4. Enter keyword "seo"
5. Enable "Use AI" checkbox
6. Click "Run Pipeline Test"
7. Check debug.log file for detailed logs

### What to Look For:
- Which sources return data vs empty
- What AI extracts (should have numbers)
- Token usage
- Processing time
- Error messages

---

## 📝 CONCLUSION

**Current State:** Plugin has solid foundation but needs significant work on:
1. Source implementations (most don't work)
2. AI extraction quality (not getting statistics)
3. User feedback (can't see what's happening)

**Recommendation:** Focus on fixing AI extraction FIRST (biggest impact), then audit and fix sources one by one.

**Estimated Time to Production-Ready:** 15-20 hours of focused development

---

**Next Task Handoff:**
The next developer should focus on:
1. Fixing AI extraction to get ONLY numerical statistics
2. Auditing source registry and removing broken sources
3. Implementing 3-5 high-priority sources properly
4. Adding better user feedback and transparency

