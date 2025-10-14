# AI-Stats v0.7.3 - Testing Guide

**Date:** 2025-10-14  
**Version:** 0.7.3  
**What Changed:** Fixed Google Trends integration to show keyword-relevant data

---

## 🎯 WHAT WAS FIXED

### Before (v0.7.2) - BROKEN ❌
- Google Trends Demo tab showed random trending searches
- Data was NOT filtered by user keywords
- Results like "is today a federal holiday" - completely useless
- No integration with the pipeline

### After (v0.7.3) - FIXED ✅
- Google Trends integrated into pipeline
- Data IS filtered by user keywords + AI-expanded synonyms
- Results are relevant to user's business/industry
- Proper keyword matching and ranking

---

## 🧪 HOW TO TEST

### Test 1: Verify Demo Tab Removed

1. Go to: `https://adwordsadvantage.com/wp-admin/admin.php?page=ai-stats-debug`
2. **Expected:** You should see 3 tabs:
   - ✅ Pipeline Debug
   - ✅ Data Sources
   - ✅ Configuration
3. **Expected:** NO "Google Trends Demo" tab

---

### Test 2: Test Google Trends in Pipeline (SEO Keyword)

1. Go to: `https://adwordsadvantage.com/wp-admin/admin.php?page=ai-stats-debug#pipeline`
2. Select mode: **Industry Trend Micro-Module**
3. Enter keyword: **SEO**
4. Click **Run Pipeline Test**

**Expected Results:**

**Stage 1 (Fetch from Sources):**
- Should show "BigQuery Google Trends" as one of the sources
- Should show data fetched successfully

**Stage 2 (Normalised Data):**
- Should show trends in format: `Trending: [search term] (#rank)`
- Example: `Trending: Google algorithm update (#5)`

**Stage 3 (Filtered by Keywords):**
- Should show ONLY trends related to SEO
- ✅ Good examples:
  - "Google algorithm update"
  - "search engine optimisation"
  - "SEO best practices"
  - "local SEO"
  - "technical SEO"
- ❌ Should NOT show:
  - "is today a federal holiday"
  - "weather forecast"
  - "celebrity news"

**Stage 4 (Ranked by Score):**
- Trends should be ranked by:
  1. Keyword density (how well they match "SEO" + synonyms)
  2. Freshness (how recent)
  3. Source authority
- Top results should be most relevant to SEO

---

### Test 3: Test Google Trends in Pipeline (Web Design Keyword)

1. Select mode: **Industry Trend Micro-Module**
2. Enter keyword: **web design**
3. Click **Run Pipeline Test**

**Expected Results:**
- Should show trends related to web design
- ✅ Good examples:
  - "responsive design"
  - "UI/UX trends"
  - "website builder"
  - "CSS frameworks"
  - "web accessibility"
- ❌ Should NOT show random trends

---

### Test 4: Test Seasonal Mode

1. Select mode: **Seasonal Service Angle Rotator**
2. Enter keyword: **digital marketing**
3. Click **Run Pipeline Test**

**Expected Results:**
- Should fetch Google Trends data
- Should filter by "digital marketing" + synonyms
- Should show relevant seasonal trends

---

### Test 5: Verify AI Keyword Expansion

1. Select mode: **Industry Trend Micro-Module**
2. Enter keyword: **SEO**
3. Click **Run Pipeline Test**
4. Go to **Stage 3 (Filtered by Keywords)**
5. Look for the keyword expansion details

**Expected:**
- Original keyword: `SEO`
- Expanded keywords should include:
  - search engine optimisation
  - search engine optimization
  - Google ranking
  - organic search
  - SERP
  - meta tags
  - indexing
  - etc.

---

### Test 6: Verify Helpful Notices

**If Google Trends IS configured:**

1. Go to Pipeline Debug tab
2. **Expected:** You should see a blue info box:

```
💡 Google Trends Integration Active

Google Trends data is now integrated into the pipeline for "Industry Trend 
Micro-Module" and "Seasonal Service Angle Rotator" modes.

When you enter a keyword (e.g., "SEO"), the pipeline will:
1. Fetch trending searches from Google Trends (last 30 days)
2. Expand your keyword using AI to include synonyms
3. Filter trends to show only those matching your keyword
4. Rank results by relevance, freshness, and keyword density

Example: If you search for "web design", you'll see trending searches like 
"responsive design", "UI/UX trends", etc. - not random trends like 
"is today a federal holiday".
```

**If Google Trends is NOT configured:**

1. **Expected:** You should see a yellow warning box with setup instructions

---

## 🔍 WHAT TO LOOK FOR

### ✅ GOOD SIGNS (Working Correctly)

1. **Keyword Relevance:**
   - All Google Trends results relate to your keyword
   - No random/unrelated trends appear

2. **AI Expansion:**
   - Keyword expansion shows 10+ related terms
   - Expansion includes synonyms and related concepts

3. **Filtering:**
   - Stage 3 shows fewer results than Stage 2
   - Only relevant results pass through

4. **Ranking:**
   - Most relevant trends appear first
   - Scores reflect keyword density + freshness

### ❌ BAD SIGNS (Something Wrong)

1. **Random Trends:**
   - Seeing "is today a federal holiday"
   - Seeing celebrity news, weather, etc.
   - Trends unrelated to your keyword

2. **No Filtering:**
   - Stage 3 has same count as Stage 2
   - All trends pass through regardless of keyword

3. **No Expansion:**
   - Only original keyword used
   - No synonyms generated

4. **Errors:**
   - BigQuery connection errors
   - Empty results
   - JavaScript console errors

---

## 🐛 TROUBLESHOOTING

### Issue: No Google Trends Data

**Possible Causes:**
1. BigQuery not enabled in settings
2. Invalid Google Cloud credentials
3. No trending data for selected region

**Solution:**
1. Go to **AI-Stats > Settings**
2. Scroll to "Google Cloud Integration"
3. Verify:
   - ✅ "Enable Google Trends data via BigQuery" is checked
   - ✅ Google Cloud Project ID is filled
   - ✅ Service Account JSON is filled
4. Click **Test BigQuery Connection**
5. Should see "Connection successful!"

---

### Issue: All Trends Showing (Not Filtered)

**Possible Causes:**
1. No keyword entered
2. AI keyword expansion failed
3. Filtering logic broken

**Solution:**
1. Make sure you entered a keyword
2. Check browser console for JavaScript errors
3. Check Stage 3 for keyword expansion details
4. If AI expansion failed, check AI-Core configuration

---

### Issue: JavaScript Errors

**Possible Causes:**
1. Browser cache
2. Version mismatch

**Solution:**
1. Hard refresh: `Cmd+Shift+R` (Mac) or `Ctrl+Shift+R` (Windows)
2. Clear browser cache
3. Check browser console for specific errors

---

## 📊 EXPECTED PERFORMANCE

### Data Volume

**Mode: Industry Trend Micro-Module**
- Total sources: ~40
- Google Trends candidates: 25-50
- After filtering (with keyword): 5-15
- After ranking (top 12): 12

**Mode: Seasonal Service Angle Rotator**
- Total sources: 3
- Google Trends candidates: 25-50
- After filtering (with keyword): 3-10
- After ranking (top 12): 3-10

### Response Times

- Fetch from BigQuery: 2-5 seconds
- AI keyword expansion: 1-3 seconds
- Filtering: <1 second
- Ranking: <1 second
- **Total pipeline time: 5-15 seconds**

---

## ✅ SUCCESS CRITERIA

The fix is working correctly if:

1. ✅ Google Trends Demo tab is removed
2. ✅ Google Trends data appears in pipeline
3. ✅ Trends are filtered by keyword
4. ✅ AI keyword expansion works
5. ✅ Only relevant trends appear in results
6. ✅ No random trends like "is today a federal holiday"
7. ✅ Helpful notices appear in UI
8. ✅ No JavaScript errors
9. ✅ No PHP errors

---

## 📝 NOTES FOR TESTING

### Best Keywords to Test

**Good test keywords:**
- SEO (lots of related trends)
- web design (clear industry focus)
- digital marketing (broad but relevant)
- WordPress (specific platform)
- ecommerce (clear business category)

**Poor test keywords:**
- a (too generic)
- the (not a real keyword)
- xyz123 (no related trends)

### Understanding the Results

**Title Format:**
```
Trending: [search term] (#rank)
```

**Example:**
```
Trending: Google algorithm update (#5)
```

This means:
- Search term: "Google algorithm update"
- Rank: #5 in Google Trends for the selected region
- This trend is currently ranked 5th in trending searches

**Metadata:**
- `query`: The actual search term
- `rank`: Position in trending searches (1-25)
- `region`: Geographic region (GB, US, EU)
- `date`: When this trend was recorded

---

## 🚀 NEXT STEPS AFTER TESTING

If everything works:
1. ✅ Mark this version as stable
2. ✅ Update production sites
3. ✅ Monitor for any issues

If issues found:
1. Document the issue
2. Check browser console for errors
3. Check WordPress debug log
4. Report back with details

---

**Happy Testing!** 🎉

