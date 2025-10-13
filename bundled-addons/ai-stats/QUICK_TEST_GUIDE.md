# AI-Stats Quick Test Guide

**Version:** 0.2.1  
**Purpose:** Diagnose why Fetch & Preview returns nothing

## Step 1: Enable Debug Mode

1. Edit `wp-config.php` and add:
```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

2. This will create a `wp-content/debug.log` file with detailed error messages

## Step 2: Access Debug Page

1. Go to **WordPress Admin → AI-Stats → Debug**
2. You should see 4 tabs:
   - Data Sources
   - Test Fetch
   - Configuration
   - Cache Status

## Step 3: Check Configuration

1. Click the **Configuration** tab
2. Verify:
   - ✅ AI-Core Status: Should show "Available"
   - ⚠️ Google API Key: May show "Missing" (optional)
   - ⚠️ Companies House API Key: May show "Missing" (optional)
   - ✅ Preferred AI Provider: Should show your selection

**Note:** Missing API keys are OK for RSS feeds, but required for API sources

## Step 4: View Data Sources

1. Click the **Data Sources** tab
2. You should see 6 sections (one per mode):
   - Statistical Authority Injector
   - Birmingham Business Stats
   - Industry Trend Micro-Module
   - Service + Benefit Semantic Expander
   - Seasonal Service Angle Rotator
   - Service Process Micro-Step Enhancer

3. Each section shows a table with:
   - Source name
   - Type (RSS, API, or HTML)
   - URL
   - Update frequency
   - Tags

4. **Count the sources** - you should see 60+ total across all modes

## Step 5: Test Fetch

1. Click the **Test Fetch** tab
2. Select a mode from the dropdown (start with "Industry Trend Micro-Module")
3. Click **"Fetch Data"**
4. Wait 5-30 seconds (RSS feeds can be slow)

### Expected Results

**Success:**
- Green notice: "X candidates fetched successfully"
- Table showing candidates with:
  - Title
  - Source name
  - Published date
  - Score
  - Tags
- Raw data expandable for each candidate

**Failure:**
- Yellow warning: "No candidates found"
- Possible reasons listed:
  - Data sources not returning data
  - Network connectivity issues
  - API keys not configured
  - Sources are rate-limited

**Error:**
- Red error: "Error: [message]"
- Check `wp-content/debug.log` for details

## Step 6: Check Debug Log

1. Open `wp-content/debug.log` in a text editor
2. Look for lines starting with `AI-Stats:`
3. You should see entries like:
```
AI-Stats: Fetched 5 candidates from Search Engine Land (RSS)
AI-Stats: Fetched 3 candidates from Moz Blog (RSS)
AI-Stats RSS error for Google Search Status: Failed to fetch feed
```

### What to Look For

**Good Signs:**
- "Fetched X candidates from [source]" - Source is working
- Multiple sources returning data

**Bad Signs:**
- "RSS error for [source]: Failed to fetch feed" - RSS feed is down or blocked
- "No items in RSS feed for [source]" - Feed is empty
- "fetch_failed" - Network or parsing error

## Step 7: Test Specific Sources

1. In the **Data Sources** tab, find a source you want to test
2. Click the **"Test"** button next to it
3. This will test just that one source
4. Check the debug log for results

## Step 8: Clear Cache

If you're testing repeatedly:

1. Go to **Cache Status** tab
2. Click **"Clear All Cache"**
3. This removes the 10-minute cache
4. Re-test fetch

## Step 9: Test Main Workflow

1. Go to **AI-Stats → Dashboard**
2. Click **"Generate Now"**
3. Modal should open
4. Select mode: "Industry Trend Micro-Module"
5. Enter keywords: "SEO, Google, web design"
6. Click **"Fetch & Preview"**

### Expected Behavior

**If fetch works:**
- Table appears with candidates
- Checkboxes for selection
- "Generate Draft" button enabled

**If fetch fails:**
- Error message appears
- Should show: "No candidates found. Check Debug page for details."
- Should show: "(Checked X sources)"
- Should show: Link to debug page

## Common Issues & Solutions

### Issue 1: "No candidates found" but debug log shows fetches

**Cause:** Keyword filtering too strict

**Solution:**
- Try without keywords first
- Use broader keywords
- Check that keywords match content in RSS feeds

### Issue 2: RSS errors in debug log

**Cause:** RSS feeds blocked by firewall or rate-limited

**Solution:**
- Check your server can access external URLs
- Try from different network
- Wait and retry (rate limits)
- Some feeds may be permanently down

### Issue 3: "AI-Core is not available"

**Cause:** AI-Core plugin not installed or activated

**Solution:**
- Install AI-Core plugin
- Activate AI-Core plugin
- Configure at least one AI provider in AI-Core settings

### Issue 4: All API sources return empty

**Cause:** API keys not configured

**Solution:**
- Go to **AI-Stats → Settings**
- Scroll to "API Keys (Optional)"
- Add Google API Key (for CrUX)
- Add Companies House API Key (for UK company data)
- Save settings

### Issue 5: Cache hiding changes

**Cause:** 10-minute cache still active

**Solution:**
- Go to **Debug → Cache Status**
- Click "Clear All Cache"
- Or wait 10 minutes

## What Sources Should Work Without API Keys?

### RSS Feeds (Should Work)
- Search Engine Land
- Search Engine Journal
- Moz Blog
- Google Search Status Dashboard
- Smashing Magazine
- Nielsen Norman Group
- UX Collective
- HubSpot Marketing Blog
- Think with Google
- Birmingham City Observatory

### APIs (Require Keys)
- ONS API (may work without key for some endpoints)
- Companies House API (requires key)
- CrUX API (requires Google API key)
- Calendarific (requires key)

### HTML Scraping (Should Work)
- WordStream benchmarks
- Mailchimp benchmarks
- Various statistics pages

## Next Steps

1. **If RSS feeds work:**
   - You should see candidates from Search Engine Land, Moz, etc.
   - Proceed to test LLM generation
   - Configure API keys for additional sources

2. **If nothing works:**
   - Check debug log for specific errors
   - Test network connectivity: `curl https://searchengineland.com/feed`
   - Check WordPress can fetch external URLs
   - Contact support with debug log

3. **If some sources work:**
   - This is normal - not all sources are always available
   - Focus on working sources
   - Add API keys to enable more sources

## Support Information

When reporting issues, please provide:

1. **Debug log excerpt** (last 50 lines with AI-Stats entries)
2. **Configuration tab screenshot** from debug page
3. **Test fetch results** for at least one mode
4. **WordPress version** and **PHP version**
5. **Network environment** (shared hosting, VPS, local, etc.)

---

**Remember:** The debug page is your friend! It shows exactly what's happening with each data source.

