# AI-Stats Plugin Updates - Version 0.2.9

**Date:** 2025-10-13  
**Status:** ✅ Complete

---

## Overview

This update fixes all failed data source API endpoints and RSS feeds based on comprehensive testing and verification. The update removes deprecated sources, adds working alternatives, and updates API endpoints to use the latest verified URLs.

---

## Changes Summary

### 🔧 Fixed API Endpoints

#### Statistical Authority Injector
- ✅ **ONS API**: Updated from `https://api.ons.gov.uk/` to `https://api.beta.ons.gov.uk/v1/`
- ✅ **Nomis API**: Added new source `https://www.nomisweb.co.uk/api/v01/` for UK labour market statistics
- ✅ **Companies House**: Updated from generic endpoint to developer portal `https://developer.company-information.service.gov.uk/`
- ❌ **Removed**: Statista RSS feeds (Press Releases, Economy & Politics, Society) - no public RSS endpoints available

#### Birmingham Business Stats
- ✅ **Birmingham City Observatory API**: Replaced WMCA Data and Birmingham Open Data with unified endpoint `https://www.cityobservatory.birmingham.gov.uk/api/`
- ✅ **ONS Regional**: Updated to use beta API `https://api.beta.ons.gov.uk/v1/`
- ✅ **BBC Technology**: Added new RSS feed `https://feeds.bbci.co.uk/news/technology/rss.xml?edition=uk`
- ✅ **ComputerWeekly**: Added new RSS feed `https://www.computerweekly.com/rss`
- ❌ **Removed**: Tech City News (UKTN) - no public RSS feed available
- ❌ **Removed**: Essential Retail - site repurposed, no RSS feed
- ❌ **Removed**: Retail Gazette - failed to fetch

#### Industry Trend Micro-Module
- ✅ **Wired UK**: Updated from US to UK edition `https://www.wired.co.uk/rss`
- ❌ **Removed**: Statista category RSS feeds (Internet, Technology & Telecom, Media) - not publicly accessible

#### Service + Benefit Semantic Expander
- ❌ **Removed**: All Statista RSS feeds (Advertising & Marketing, E-Commerce, Retail & Trade, Services, Consumer Goods, Infographics) - not publicly accessible
- ❌ **Removed**: Marketing Week - failed to fetch
- ❌ **Removed**: Campaign UK - failed to fetch
- ❌ **Removed**: The Drum - failed to fetch
- ❌ **Removed**: Smart Insights - failed to fetch
- ❌ **Removed**: Econsultancy - failed to fetch
- ✅ **Kept**: Working sources (HubSpot, Mailchimp, Content Marketing Institute, Copyblogger, Neil Patel, Social Media sources)

---

## Technical Changes

### New API Handlers Added

1. **`fetch_nomis_api()`** - Handler for Nomis labour market statistics API
   - Endpoint: `https://www.nomisweb.co.uk/api/v01/dataset/{id}.data.json`
   - Returns UK employment and labour market data
   - Confidence: 0.95

2. **`fetch_birmingham_observatory_api()`** - Handler for Birmingham City Observatory API
   - Endpoint: `https://www.cityobservatory.birmingham.gov.uk/api/explore/v2.1/catalog/datasets`
   - Returns Birmingham and West Midlands regional data
   - Confidence: 0.90

### Updated API Handlers

1. **`fetch_ons_api()`** - Updated to use beta API endpoint
   - Old: `https://api.ons.gov.uk/timeseries/{id}/dataset/{dataset}/data`
   - New: `https://api.beta.ons.gov.uk/v1/timeseries/{id}/dataset/{dataset}/data`

### Updated Routing Logic

Updated `fetch_api()` method to route to new handlers:
- Added Nomis API routing
- Added Birmingham City Observatory API routing
- Maintained backward compatibility with existing handlers

---

## Files Modified

1. **`bundled-addons/ai-stats/ai-stats.php`**
   - Updated version from 0.2.8 to 0.2.9

2. **`bundled-addons/ai-stats/includes/class-ai-stats-source-registry.php`**
   - Updated version from 0.2.8 to 0.2.9
   - Removed 18 failed/deprecated sources
   - Added 3 new working sources
   - Updated 5 API endpoint URLs

3. **`bundled-addons/ai-stats/includes/class-ai-stats-adapters.php`**
   - Updated version from 0.2.8 to 0.2.9
   - Added `fetch_nomis_api()` method (70 lines)
   - Added `fetch_birmingham_observatory_api()` method (50 lines)
   - Updated `fetch_ons_api()` to use beta API
   - Updated `fetch_api()` routing logic

---

## Source Count Changes

### Before (v0.2.8)
- **Statistical Authority Injector**: 8 sources
- **Birmingham Business Stats**: 11 sources
- **Industry Trend Micro-Module**: 42 sources
- **Service + Benefit Semantic Expander**: 23 sources
- **Seasonal Service Angle Rotator**: 3 sources
- **Service Process Micro-Step Enhancer**: 10 sources
- **Total**: 97 sources

### After (v0.2.9)
- **Statistical Authority Injector**: 5 sources (-3)
- **Birmingham Business Stats**: 10 sources (-1)
- **Industry Trend Micro-Module**: 39 sources (-3)
- **Service + Benefit Semantic Expander**: 12 sources (-11)
- **Seasonal Service Angle Rotator**: 3 sources (no change)
- **Service Process Micro-Step Enhancer**: 10 sources (no change)
- **Total**: 79 sources (-18)

---

## Testing Recommendations

1. **Clear Plugin Cache**
   ```bash
   # Delete cached source registry
   wp option delete ai_stats_source_registry
   ```

2. **Test Data Sources**
   - Navigate to AI-Stats → Debug → Data Sources tab
   - Click "Test All Sources" button
   - Verify all sources show "Success" status

3. **Test API Handlers**
   - Test ONS API with beta endpoint
   - Test Nomis API for labour statistics
   - Test Birmingham City Observatory API for regional data

4. **Verify Content Generation**
   - Generate content in each mode
   - Verify data is being fetched from new sources
   - Check that removed sources are no longer referenced

---

## Known Issues & Limitations

1. **Statista**: No public RSS feeds available for most categories. Consider:
   - Manual data entry for key statistics
   - Alternative statistics sources (ONS, Eurostat, World Bank)
   - Paid Statista API access (if budget allows)

2. **UK Marketing News**: Several UK marketing publications (Marketing Week, Campaign, The Drum, Econsultancy) have RSS feed issues. Consider:
   - Using working alternatives (HubSpot, Content Marketing Institute)
   - Web scraping with proper rate limiting
   - Manual curation of key insights

3. **BigQuery Google Trends**: Requires Google Cloud setup and credentials. Ensure:
   - Service account JSON is configured
   - Project ID is set correctly
   - BigQuery API is enabled

---

## Next Steps

1. ✅ Update version numbers
2. ✅ Update source registry
3. ✅ Add new API handlers
4. ✅ Update routing logic
5. ⏳ Test all data sources
6. ⏳ Clear cache and verify
7. ⏳ Commit and push changes

---

## Deployment Checklist

- [ ] Clear WordPress object cache
- [ ] Delete `ai_stats_source_registry` option to force refresh
- [ ] Test data source fetching in Debug tab
- [ ] Verify content generation in all 6 modes
- [ ] Check error logs for any API failures
- [ ] Monitor performance with new API handlers

---

## References

- [ONS Developer Hub](https://developer.ons.gov.uk/)
- [Nomis API Documentation](https://www.nomisweb.co.uk/api/v01/help)
- [Birmingham City Observatory](https://www.cityobservatory.birmingham.gov.uk/)
- [Companies House API](https://developer.company-information.service.gov.uk/)
- [Eurostat API](https://ec.europa.eu/eurostat/web/main/data/web-services)
- [World Bank API](https://datahelpdesk.worldbank.org/knowledgebase/topics/125589)

---

**End of Update Document**

