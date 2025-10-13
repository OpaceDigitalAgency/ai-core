# AI-Stats v0.2.9 Deployment Instructions

**Version:** 0.2.9  
**Date:** 2025-10-13  
**Critical:** This update requires cache clearing to take effect

---

## Pre-Deployment Checklist

- [ ] Backup current WordPress database
- [ ] Backup current plugin files
- [ ] Note current version number (should be 0.2.8)
- [ ] Verify you have admin access to WordPress

---

## Deployment Steps

### Step 1: Upload Updated Files

Upload the following modified files to your WordPress installation:

```
bundled-addons/ai-stats/ai-stats.php
bundled-addons/ai-stats/includes/class-ai-stats-source-registry.php
bundled-addons/ai-stats/includes/class-ai-stats-adapters.php
```

### Step 2: Clear Plugin Cache

**Option A: Using WordPress Admin (Recommended)**

1. Log in to WordPress admin
2. Navigate to **AI-Stats → Debug**
3. Click the **"System"** tab
4. Click the **"Clear All Caches"** button
5. Wait for confirmation message

**Option B: Using WP-CLI**

```bash
wp option delete ai_stats_source_registry
wp transient delete --all
```

**Option C: Using the Clear Cache Script**

1. Navigate to: `https://yourdomain.com/wp-content/plugins/ai-core/bundled-addons/ai-stats/clear-cache.php`
2. Wait for the script to complete
3. Verify the success message

**Option D: Using Database Query**

```sql
DELETE FROM wp_options WHERE option_name = 'ai_stats_source_registry';
DELETE FROM wp_options WHERE option_name LIKE '_transient_ai_stats_%';
DELETE FROM wp_options WHERE option_name LIKE '_transient_timeout_ai_stats_%';
```

### Step 3: Verify Installation

1. Navigate to **AI-Stats → Debug**
2. Check the **"System"** tab
3. Verify version shows **0.2.9**
4. Check source counts:
   - Statistical Authority Injector: 5 sources
   - Birmingham Business Stats: 10 sources
   - Industry Trend Micro-Module: 39 sources
   - Service + Benefit Semantic Expander: 12 sources
   - Seasonal Service Angle Rotator: 3 sources
   - Service Process Micro-Step Enhancer: 10 sources
   - **Total: 79 sources**

### Step 4: Test Data Sources

1. Navigate to **AI-Stats → Debug**
2. Click the **"Data Sources"** tab
3. Click **"Test All Sources"** button
4. Wait for all tests to complete
5. Verify the following sources now show **"Success"**:
   - ✅ ONS API
   - ✅ Nomis API
   - ✅ Eurostat
   - ✅ World Bank
   - ✅ Companies House
   - ✅ Birmingham City Observatory API
   - ✅ ONS Regional
   - ✅ BBC Technology
   - ✅ ComputerWeekly
   - ✅ All RSS feeds

### Step 5: Test Content Generation

1. Navigate to **AI-Stats → Settings**
2. Select **"Statistical Authority Injector"** mode
3. Click **"Test Generate"**
4. Verify content is generated successfully
5. Repeat for other modes:
   - Birmingham Business Stats
   - Industry Trend Micro-Module
   - Service + Benefit Semantic Expander

---

## Post-Deployment Verification

### Check Error Logs

```bash
# Check WordPress debug log
tail -f wp-content/debug.log | grep "AI-Stats"

# Check PHP error log
tail -f /var/log/php/error.log | grep "AI-Stats"
```

### Verify API Endpoints

Test the new API endpoints manually:

```bash
# Test ONS Beta API
curl "https://api.beta.ons.gov.uk/v1/timeseries/J4MC/dataset/DRSI/data"

# Test Nomis API
curl "https://www.nomisweb.co.uk/api/v01/dataset/NM_1_1.data.json?geography=2092957697&date=latest&measures=20100"

# Test Birmingham City Observatory API
curl "https://www.cityobservatory.birmingham.gov.uk/api/explore/v2.1/catalog/datasets?limit=10"
```

### Monitor Performance

1. Check page load times
2. Monitor API response times in Debug tab
3. Verify no timeout errors
4. Check memory usage

---

## Rollback Procedure

If issues occur, rollback to v0.2.8:

1. Restore backed-up files
2. Clear cache using any method above
3. Verify version shows 0.2.8
4. Test data sources

---

## Common Issues & Solutions

### Issue: Sources Still Showing "Failed"

**Solution:**
1. Clear browser cache
2. Clear WordPress object cache
3. Delete `ai_stats_source_registry` option again
4. Refresh the Debug page

### Issue: Version Still Shows 0.2.8

**Solution:**
1. Verify files were uploaded correctly
2. Check file permissions (should be 644)
3. Clear PHP opcache if enabled
4. Restart PHP-FPM if using it

### Issue: New Sources Not Appearing

**Solution:**
1. Delete `ai_stats_source_registry` option
2. Navigate to AI-Stats → Debug
3. The registry will rebuild automatically
4. Refresh the page

### Issue: API Timeout Errors

**Solution:**
1. Check server firewall settings
2. Verify outbound HTTPS connections are allowed
3. Increase PHP timeout in wp-config.php:
   ```php
   define('WP_HTTP_TIMEOUT', 60);
   ```

---

## Support & Troubleshooting

### Enable Debug Mode

Add to `wp-config.php`:

```php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);
define('WP_DEBUG_DISPLAY', false);
```

### Check Source Registry

```php
// Get current registry
$registry = AI_Stats_Source_Registry::get_instance();
$sources = $registry->get_all_sources();
print_r($sources);
```

### Force Registry Refresh

```php
// Force rebuild
$registry = AI_Stats_Source_Registry::get_instance();
$registry->refresh_registry();
```

---

## What Changed in v0.2.9

### Added
- ✅ Nomis API handler for UK labour market statistics
- ✅ Birmingham City Observatory API handler for regional data
- ✅ BBC Technology RSS feed
- ✅ ComputerWeekly RSS feed

### Updated
- ✅ ONS API endpoint to beta version
- ✅ Companies House API endpoint
- ✅ Wired feed to UK edition
- ✅ BBC Business feed to HTTPS

### Removed
- ❌ Statista RSS feeds (not publicly accessible)
- ❌ Tech City News / UKTN (no RSS feed)
- ❌ Essential Retail (site repurposed)
- ❌ Retail Gazette (failed to fetch)
- ❌ Marketing Week (failed to fetch)
- ❌ Campaign UK (failed to fetch)
- ❌ The Drum (failed to fetch)
- ❌ Smart Insights (failed to fetch)
- ❌ Econsultancy (failed to fetch)

---

## Next Steps After Deployment

1. Monitor data source performance for 24 hours
2. Check generated content quality
3. Review error logs for any issues
4. Consider adding alternative sources for removed feeds
5. Update documentation with new source information

---

## Contact

For issues or questions:
- Check the error logs first
- Review the UPDATES_v0.2.9.md document
- Contact Opace Digital Agency support

---

**End of Deployment Instructions**

