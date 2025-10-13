What you can use now (Google Cloud options)

BigQuery “Google Trends” public dataset (Marketplace) – immediately query the Top 25 overall / Top 25 Rising queries from the last 30 days (no scraping; free tier covers light use). Use a service account and standard SQL; this is the fastest way to wire real trends into your plugin today. 

Google Help

Quick path to wire it

In Google Cloud, subscribe to Google Search Trends in Marketplace, which exposes it in BigQuery public datasets. 
Google Cloud

Query it from your server (PHP client / REST) and cache results for 12–24h. (BigQuery public datasets + free tier: first 1TB queries/month free.) 
Google Cloud

Why 22/28 sources are empty — and how to fix each class

You’ve nailed the root causes: wrong types, homepage URLs, missing dataset codes/keys. Below are copy-paste corrections with working endpoint patterns you can put into your adapters so they return data.

1) Trends / Industry news feeds (RSS/Atom) — mark as RSS

Google Search Status feed (Atom): https://status.search.google.com/feed.atom (live ranking/system notes). Cache 1–6h. 
Google for Developers

Search Engine Land (RSS): https://searchengineland.com/feed (frequent SEO/PPC updates). Cache 6–12h.
(These must be parsed as XML→JSON; do not label as API.)

2) Google Trends (workable today) — mark as API via BigQuery

BigQuery Trends dataset (Top 25 / Rising, last 30 days): subscribe in Marketplace, then query via BigQuery API. Cache 6–24h. 
Google Help


(Optional later) Trends API (alpha) when Google approves access. 

Google Trends Dataset (via BigQuery public data / Marketplace)
You already have this. It exposes Top 25 + Rising queries (global / by country / region) over the past 30 days, with historical backfill. 
Google Help

Google Cloud


Use it in your “Industry Trend” mode to inject fresh trending keywords.
Query via:

SELECT * 
FROM `bigquery-public-data.google_trends.top_terms`
WHERE refresh_date = DATE_SUB(CURRENT_DATE(), INTERVAL 1 DAY)

Google for Developers

3) ONS (UK stats) — mark as API and point to specific series

List datasets: https://api.beta.ons.gov.uk/v1/datasets (discover codes). 
ONS Developer Hub

Example (Retail internet sales % – series J4MC): Use the time-series page to identify series, then pull via ONS API endpoints for that dataset (or CSV from the series page). Cache weekly/daily as needed. 
Office for National Statistics

(Key fix: store dataset/series IDs like J4MC; don’t point at the HTML page.)

BICS overview (to choose micro-indicators; use ONS API docs for actual pulls). 
Office for National Statistics


4) Eurostat — mark as API with dataset codes

Getting started + JSON patterns: …/statistics/1.0/data/{DATASET}?time=YYYY&geo=GB&format=JSON (e.g., nama_10_gdp). Cache weekly. 
European Commission

fgeerolf.com


(Key fix: save the dataset code like nama_10_gdp in your source config.)

5) World Bank — mark as API with indicator codes

Indicators API (≈16k indicators). Example:
https://api.worldbank.org/v2/country/GBR/indicator/NY.GDP.MKTP.CD?format=json
(GDP current US$ for UK). Cache monthly/quarterly. 
World Bank Data Help Desk

World Bank Data Help Desk


(Key fix: store indicator codes like NY.GDP.MKTP.CD.)

6) Companies House — mark as API (key required)

Company search (trend proxy: new incorporations by term/area/SIC you care about):
GET https://api.company-information.service.gov.uk/search/companies?q=software&items_per_page=50 (HTTP auth using API key). Cache 24h. 
Developer Specifications

developer.company-information.service.gov.uk


(Key fix: add API key + auth header; never point at homepage.)

7) Web performance — mark as API

CrUX API (field CWV by origin): POST https://chromeuxreport.googleapis.com/v1/records:queryRecord with { "origin": "https://yourdomain.com" }. Cache 24h. (Documented by Google; you already had this in your list.)

PageSpeed Insights API (lab + field): https://www.googleapis.com/pagespeedonline/v5/runPagespeed?url=… Cache 24h. (Official Google API docs cover this; ensure key + quotas.)

8) Birmingham / WMCA / Opendatasoft — mark as API with V2.1 pattern

Most council/observatory portals run on Opendatasoft. Use the Explore API v2.1:

GET https://{portal-domain}/api/explore/v2.1/catalog/datasets/{dataset-id}/records?limit=50&order_by=published_at%20DESC


Cache 24h and store dataset IDs per source. (You had the portal homepage before; switch to /api/explore/v2.1/… endpoints.)
(Use the ONS/WMCA/Birmingham dataset pages to identify the dataset-id you want.)

Practical checklist to stop the empties

Fix the source types

RSS/Atom → XML parser; API → JSON client; HTML → scraper (last resort).

Replace homepage URLs with concrete data endpoints

ONS: dataset/series IDs (e.g., J4MC). 
Office for National Statistics


Eurostat: dataset codes (e.g., nama_10_gdp). 
European Commission

World Bank: indicator codes (e.g., NY.GDP.MKTP.CD). 
World Bank Data Help Desk

Companies House: /search/companies with API key. 
Developer Specifications

Trends: BigQuery dataset (subscribe + query). 
Google Help


Add minimal per-source adapter logic

Build one working adapter per class (RSS, ONS, Eurostat, World Bank, CH, CrUX, PSI, Opendatasoft).

Each returns a normalised candidate {title, source, url, published_at, blurb_seed, tags, confidence}.

Validate in admin (manual-first)

Click Fetch & Preview → confirm at least 6–10 candidates appear.

If a source yields zero: log the HTTP response + reason (404, 401, empty rows).

Bottom line

Yes, the Google Trends API is alpha and gated; you applied correctly. 
Google for Developers

Yes, you can use Google Cloud today via BigQuery’s Google Trends dataset (Top 25 / Rising), which is the quickest reliable route to live trend signals in your modules. 
Google Help


Fix the empties by switching to specific data endpoints + correct types + keys (examples above). Once each adapter returns at least 3–5 items, your manual Fetch → Preview → Publish flow will finally show varied, relevant blurbs.