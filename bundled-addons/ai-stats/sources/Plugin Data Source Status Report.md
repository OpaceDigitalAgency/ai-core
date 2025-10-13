# 📊 AI‑Stats Source Audit & Replacement Guide

This document lists all plugin data sources with replacements for empty or invalid endpoints.
Use it to update your plugin configuration and ensure each source returns usable data.

**Date:** October 13, 2025


---

## STATISTICAL AUTHORITY INJECTOR

| # | Name | Current URL | Status | Working Replacement |
|---|------|------------|--------|-------------------|
| 1 | ONS API | `https://api.ons.gov.uk/` | ✅ **WORKING** | No change needed |
| 2 | Eurostat | `https://ec.europa.com/eurostat/api/` | ❌ **BROKEN** | `https://ec.europa.eu/eurostat/api/dissemination/statistics/1.0/data/` |
| 3 | World Bank | `https://api.worldbank.org/v2/` | ✅ **WORKING** | No change needed |
| 4 | Companies House | `https://api.company-information.service.gov.uk/` | ✅ **WORKING** | No change needed |

---

## BIRMINGHAM BUSINESS STATS

| # | Name | Current URL | Status | Working Replacement |
|---|------|------------|--------|-------------------|
| 1 | Birmingham City Observatory | `https://data.birmingham.gov.uk/` | ❌ **BROKEN** | **REMOVE** - Domain not resolving |
| 2 | Birmingham.gov.uk News | `https://www.birmingham.gov.uk/rss/news` | ❌ **BROKEN** | **REMOVE** - Returns 403 Forbidden |
| 3 | WMCA Data | `https://www.wmca.org.uk/api/data-and-insight/` | ❌ **EMPTY** | `https://data.wmca.org.uk/api/explore/v2.1/` |
| 4 | ONS Regional | `https://api.ons.gov.uk/` | ✅ **WORKING** | No change needed |

---

## INDUSTRY TREND MICRO-MODULE

| # | Name | Current URL | Status | Working Replacement |
|---|------|------------|--------|-------------------|
| 1 | Search Engine Land | `https://feeds.searchengineland.com/searchengineland` | ❌ **BROKEN** | `https://searchengineland.com/feed` |
| 2 | Search Engine Journal | `https://www.searchenginejournal.com/feed/` | ✅ **WORKING** | No change needed |
| 3 | Google Search Status | `https://status.search.google.com/feed.atom` | ✅ **WORKING** | No change needed |
| 4 | Google Search Central | `https://developers.google.com/search/blog/feed` | ❌ **BROKEN** | `https://feeds.feedburner.com/blogspot/amDG` |
| 5 | Moz Blog | `https://moz.com/blog/rss` | ❌ **BROKEN** | `https://feedpress.me/mozblog` |
| 6 | Smashing Magazine | `https://www.smashingmagazine.com/feed/` | ✅ **WORKING** | No change needed |
| 7 | CrUX API | `https://chromeuxreport.googleapis.com/v1/records:queryRecord` | ❌ **BROKEN** | **REMOVE** - Requires POST with API key |

---

## SERVICE + BENEFIT SEMANTIC EXPANDER

| # | Name | Current URL | Status | Working Replacement |
|---|------|------------|--------|-------------------|
| 1 | HubSpot Marketing | `https://blog.hubspot.com/marketing/rss.xml` | ✅ **WORKING** | No change needed |
| 2 | Think with Google | `https://www.thinkwithgoogle.com/rss/feed/` | ❌ **BROKEN** | **REMOVE** - No RSS feed available |
| 3 | NerdPress Benchmarks | `https://www.nerdpress.net/blog/category/web-performance-benchmarks/` | ❌ **EMPTY** | **REMOVE** - No RSS feed |
| 4 | Mailchimp Benchmarks | `https://mailchimp.com/resources/email-marketing-benchmarks/` | ❌ **EMPTY** | `https://mailchimp.com/feed/` |

---

## SEASONAL SERVICE ANGLE ROTATOR

| # | Name | Current URL | Status | Working Replacement |
|---|------|------------|--------|-------------------|
| 1 | UK Bank Holidays | `https://www.gov.uk/bank-holidays.json` | ✅ **WORKING** | No change needed |
| 2 | Calendarific | `https://calendarific.com/api/v2/` | ❌ **BROKEN** | **REMOVE** - Requires API key |
| 3 | Google Trends Daily | `https://trends.google.com/trends/api/dailytrends` | ❌ **EMPTY** | Use BigQuery: `bigquery-public-data.google_trends` |

---

## SERVICE PROCESS MICRO-STEP ENHANCER

| # | Name | Current URL | Status | Working Replacement |
|---|------|------------|--------|-------------------|
| 1 | Nielsen Norman Group | `https://www.nngroup.com/feed/` | ❌ **BROKEN** | `https://www.nngroup.com/feed/rss/` |
| 2 | UX Collective | `https://uxdesign.cc/feed` | ✅ **WORKING** | No change needed |
| 3 | Smashing Magazine UX | `https://www.smashingmagazine.com/category/ux-design/feed/` | ✅ **WORKING** | No change needed |


---

## ✅ Audit & Replacement Table

| Mode | Original Source | Type (should be) | Replacement / Valid Endpoint | Notes / Instructions |
|------|------------------|------------------|-------------------------------|----------------------|
| **Statistical Authority Injector** | ONS API (homepage) | API | `https://api.beta.ons.gov.uk/v1/datasets/{dataset_id}/editions/{edition}/versions/{version}/observations?geography={geo}&time={time}` | Use ONS “Explore our data” to find dataset IDs. [developer.ons.gov.uk](https://developer.ons.gov.uk/dataset/) |
|  | Eurostat (homepage) | API | `https://ec.europa.eu/eurostat/api/dissemination/statistics/1.0/data/{dataset_code}?geo=UK&time=2025` | Must include dataset code and filters. |
|  | World Bank (homepage) | API | `https://api.worldbank.org/v2/country/GBR/indicator/NY.GDP.MKTP.CD?format=json` | Use known indicator codes. |
|  | Companies House (homepage) | API | `https://api.company-information.service.gov.uk/search/companies?q=software&items_per_page=20` | Requires authentication key. |

| **Birmingham Business Stats** | Birmingham City Observatory | API / JSON | `https://{portal}/api/explore/v2.1/catalog/datasets/{dataset}/records` | Use dataset ID from portal. |
|  | WMCA Data | API / JSON | Use WMCA open data API or CSV/JSON endpoint | Check WMCA data portal. |
|  | ONS Regional | API | Use ONS regional dataset endpoint with filters | Add region parameters. |

| **Industry Trend Micro‑Module** | CrUX API | API | `https://chromeuxreport.googleapis.com/v1/records:queryRecord` | Requires origin param. |
|  | Google Search Status | RSS | `https://status.search.google.com/feed.atom` | Use as signal only (system incidents). |
|  | Search Engine Land | RSS | `https://searchengineland.com/feed` | Confirm RSS working. |
|  | Search Engine Journal | RSS | `https://www.searchenginejournal.com/feed` | Confirm RSS working. |
|  | Smashing Magazine | RSS | `https://www.smashingmagazine.com/feed/` | Category or full feed. |

| **Service + Benefit Semantic Expander** | HubSpot Marketing | RSS | `https://research.hubspot.com/rss.xml` | Use HubSpot research feed. |
|  | Think with Google | RSS | `https://www.thinkwithgoogle.com/intl/en-gb/_rss/` | Global research feed. |
|  | WordStream Benchmarks | HTML | `https://www.wordstream.com/blog/ws/average-ctr` | Scrape or parse table. |
|  | Mailchimp Benchmarks | HTML | `https://mailchimp.com/resources/email-marketing-benchmarks/` | Scrape or parse metrics. |

| **Seasonal Service Angle Rotator** | UK Bank Holidays | API | `https://www.gov.uk/bank-holidays.json` | Valid UK endpoint. |
|  | Holidays.rest | API | `https://api.holidays.rest/v1/holidays` | Requires API key. |
|  | Google Trends Daily | API | Use BigQuery Trends dataset | Use your Cloud project dataset. |

| **Service Process Micro‑Step Enhancer** | Nielsen Norman Group | RSS | `https://www.nngroup.com/articles/feed/` | Confirm RSS live. |
|  | UX Collective | RSS | `https://uxdesign.cc/feed` | Confirm RSS live. |
|  | Smashing UX | RSS | `https://www.smashingmagazine.com/category/ux/feed/` | Confirm category feed. |

---

## 🧩 Instructions

1. Replace each “Empty” source with the URL from the **Replacement** column.
2. Update type to match (API / RSS / HTML).
3. Add required params (dataset code, API key, etc.).
4. Test via curl or browser before enabling.
5. Retest plugin diagnostics — keep only sources returning data.

---

**Tip:** You can import these as JSON objects into your plugin’s data source config for automated updates.

---


## SUMMARY

### ✅ WORKING (No Changes Needed): 9 sources
- ONS API
- World Bank API
- Companies House API
- Search Engine Journal
- Google Search Status
- Smashing Magazine
- HubSpot Marketing
- UX Collective
- Smashing Magazine UX
- UK Bank Holidays

### 🔄 NEEDS URL UPDATE: 5 sources
1. **Eurostat:** Change to `https://ec.europa.eu/eurostat/api/dissemination/statistics/1.0/data/`
2. **Search Engine Land:** Change to `https://searchengineland.com/feed`
3. **Google Search Central:** Change to `https://feeds.feedburner.com/blogspot/amDG`
4. **Moz Blog:** Change to `https://feedpress.me/mozblog`
5. **Nielsen Norman Group:** Change to `https://www.nngroup.com/feed/rss/`

### ❌ REMOVE/REPLACE: 7 sources
1. **Birmingham City Observatory** - Domain not resolving
2. **Birmingham.gov.uk News** - Access forbidden
3. **CrUX API** - Requires POST with API key, not suitable for RSS-style polling
4. **Think with Google** - No RSS feed available
5. **NerdPress Benchmarks** - No RSS feed
6. **Calendarific** - Requires API key
7. **Google Trends Daily** - Use BigQuery instead

### 🆕 SUGGESTED ADDITIONS:
1. **WMCA Data:** `https://data.wmca.org.uk/api/explore/v2.1/`
2. **Mailchimp Blog:** `https://mailchimp.com/feed/`
3. **Google Trends (BigQuery):** `bigquery-public-data.google_trends`

---

## RECOMMENDED ACTIONS

1. **Update URLs** for the 5 sources that have working replacements
2. **Remove** the 7 broken/unsuitable sources
3. **Add** the 3 suggested new sources
4. **Test** all updated URLs before deployment
5. **Monitor** sources monthly for changes

This will give you **12 reliable, working sources** instead of the current mix of working and broken ones.

