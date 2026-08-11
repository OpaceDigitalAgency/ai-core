# 📊 AI‑Stats Source Audit & Replacement Guide

This document lists all plugin data sources with replacements for empty or invalid endpoints.
Use it to update your plugin configuration and ensure each source returns usable data.

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
