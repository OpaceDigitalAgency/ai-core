# Consolidated AI-Stats & Plugin Optimisation Sources

## Plugin Optimisation Advice

Expanded List of Dynamic Sources for AI‑Stats Plugin Modes
To help make the AI‑Stats plugin more useful, the following tables group a wide range of frequently updated APIs and feeds by plugin mode. Each source has a short description and a direct link so your AI agent can fetch fresh data. These sources include official statistics, open government datasets, real‑time business registrations, industry news feeds and holiday calendars. They are organised so you can tailor prompts and scrape tasks to match the service you provide.
1 Statistical Authority Injector – business statistics with citations
This mode benefits from authoritative datasets. UK, European and global statistics are available via open APIs, many of which follow the SDMX standard. These services allow queries by country, region, date and indicator. Some require an API key; others are open.
2 Birmingham Business Stats – local statistics and data
Local datasets can be sourced from ONS, Nomis and Birmingham’s own data portals. Where APIs aren’t available, Opendatasoft provides endpoints for datasets within the Birmingham City Observatory.
3 Industry Trend Micro‑Module – latest SEO & web design trends
This mode thrives on real‑time industry news and algorithm updates. RSS feeds are ideal; they update hourly or daily and are easy to parse. Combine several feeds to broaden coverage.
4 Service + Benefit Semantic Expander – benefit‑focused descriptions
For generating benefit‑led service blurbs, draw upon marketing research, advertising spend reports and case studies. The sources below provide data that can inspire persuasive benefits and highlight value.
5 Seasonal Service Angle Rotator – seasonal variations
Seasonal content needs up‑to‑date calendars for holidays, observances and local events. Combining national and international holiday APIs with event listings will help rotate service angles throughout the year.
6 Service Process Micro‑Step Enhancer – process and expertise
To generate detailed process descriptions, harvest short tips and step‑by‑step guides from respected UX, design and project management blogs. RSS feeds supply bite‑sized insights on design, usability and teamwork.
How to use these sources
Build a source registry: store these APIs/feeds in a structured JSON file with tags (e.g. uk_stats, global_stats, seo_news, ux_process) and refresh cadence. This makes it easy for your agent to select appropriate sources for each mode.
Query and cache: fetch several items from each source, cache them with timestamps and tags, and rotate through them. For APIs that allow filtering, pre‑filter by geography or industry to ensure relevance.
Construct prompts: when generating a module, supply the top 2–3 most relevant data points or news items and ask the AI to summarise them succinctly. Include the source name in the prompt so the AI can reference it in the output.
Monitor for changes: periodically review the feeds and APIs for structural changes (e.g. new parameters or endpoints) and update your registry accordingly.
These expanded sources should provide a rich pool of fresh data for all six AI‑Stats modes. By mixing authoritative statistics with timely industry news and process tips, you can generate micro‑modules that feel both informed and relevant.
 ONS developer Hub - Introduction 
 API - Nomis - Official Census and Labour Market Statistics
 Developer Hub Home
 About the Indicators API Documentation – World Bank Data Help Desk
 API
 Web services - Eurostat
 St. Louis Fed Web Services: FRED® API Overview
 API Page
 UNSD API Catalogue
 API documentation - Data.gov.uk
 Huwise's Explore API Reference Documentation
 searchengineland.com
 Search Engine Journal
 Subscribe To The Search Engine Roundtable
 How to Use the Google Search Status Dashboard | Google Search Central  |  Support  |  Google for Developers
 Google Trends API Alpha | Google Search Central  |  Documentation  |  Google for Developers
  Google Ads Developer Blog: Subscribe to the blog 
 Add an RSS feed – Sendible Support
 www.gov.uk
 date.nager.at
 Global Holiday Calendar API for National and Religious Holidays - Calendarific
 www.nngroup.com
 Articles on Smashing Magazine — For Web Designers And Developers
 RSS Feeds for Reading UX News. I always wondered what is the best way… | by fernandocomet | Prototypr

---

## AI-Stats Data Sources

# AI-Stats Data Source Registry

A curated list of **dynamic, regularly updating feeds and APIs** grouped by topic for use with the AI-Stats add-on.  
All sources listed provide structured or semi-structured data suitable for automated retrieval.

---

## 🧠 Search, SEO & Digital Marketing

| Source | Type | URL |
|--------|------|-----|
| Google Search Status Dashboard | RSS/API | https://status.search.google.com/feed.atom |
| Google Search Central Blog | RSS | https://developers.google.com/search/blog/rss.xml |
| Search Engine Land | RSS | https://feeds.searchengineland.com/searchengineland |
| Search Engine Roundtable | RSS | https://feeds.seroundtable.com/SearchEngineRoundtable |
| Moz Blog | RSS | https://moz.com/blog/rss |
| Ahrefs Blog | RSS | https://ahrefs.com/blog/feed/ |
| Semrush Blog | RSS | https://www.semrush.com/blog/feed/ |
| Yoast SEO Blog | RSS | https://yoast.com/feed/ |
| Smashing Magazine SEO | RSS | https://www.smashingmagazine.com/category/seo/feed/ |
| Backlinko Blog | RSS | https://backlinko.com/blog/feed |

---

## ⚙️ Web Performance & UX

| Source | Type | URL |
|--------|------|-----|
| Chrome UX Report (CrUX) API | JSON API | https://chromeuxreport.googleapis.com/v1/records:queryRecord |
| Google PageSpeed Insights API | JSON API | https://www.googleapis.com/pagespeedonline/v5/runPagespeed |
| HTTP Archive Dataset | BigQuery / CSV | https://httparchive.org/downloads/ |
| Web Almanac | API / JSON | https://almanac.httparchive.org/en/latest/ |
| Core Web Vitals API Docs | Docs | https://web.dev/vitals/ |

---

## 📈 Marketing Benchmarks & Spend

| Source | Type | URL |
|--------|------|-----|
| IAB UK Digital Adspend | Report / CSV | https://www.iabuk.com/adspend |
| IPA Bellwether Report | Quarterly Report | https://ipa.co.uk/research-publications/bellwether-report |
| WordStream Google Ads Benchmarks | Static Page | https://www.wordstream.com/blog/ws/google-ads-industry-benchmarks |
| Mailchimp Email Benchmarks | JSON / Web | https://mailchimp.com/resources/email-marketing-benchmarks/ |
| Klaviyo Email Benchmarks | Web | https://www.klaviyo.com/resources/email-marketing-benchmarks |

---

## 🇬🇧 UK Economy & Business Data

| Source | Type | URL |
|--------|------|-----|
| ONS API | JSON API | https://api.ons.gov.uk/ |
| ONS Retail Sales Index | CSV / JSON | https://www.ons.gov.uk/businessindustryandtrade/retailindustry/datasets/retailsalesindexinternet |
| ONS Business Demography | CSV / JSON | https://www.ons.gov.uk/businessindustryandtrade/business/activitysizeandlocation/datasets/businessdemographyannualenterprisestatistics |
| ONS BICS Survey | CSV / JSON | https://www.ons.gov.uk/businessindustryandtrade/business/businessservices/datasets/businessinsightsandimpactontheukandlocaleconomy |
| Ofcom Online Nation Report | PDF / CSV | https://www.ofcom.org.uk/research-and-data/internet-and-on-demand-research/online-nation |
| Ofcom Connected Nations | PDF / CSV | https://www.ofcom.org.uk/research-and-data/multi-sector-research/infrastructure-research/connected-nations |

---

## 🏙️ Birmingham & West Midlands Regional Data

| Source | Type | URL |
|--------|------|-----|
| Birmingham City Observatory | API / Open Data | https://data.birmingham.gov.uk/ |
| Greater Birmingham Chambers Economic Review | PDF / RSS | https://www.greaterbirminghamchambers.com/resources/research-and-reports/ |
| WMCA Economic Data Dashboard | CSV / JSON | https://www.wmca.org.uk/what-we-do/economy-and-innovation/economic-data/ |
| Birmingham.gov.uk News Feed | RSS | https://www.birmingham.gov.uk/rss/news |

---

## 💼 Business & Company Activity

| Source | Type | URL |
|--------|------|-----|
| Companies House API | JSON API | https://developer.company-information.service.gov.uk/ |
| FAME / Bureau van Dijk (Private) | API | https://www.bvdinfo.com/en-gb/our-products/data/national/fame |
| Crunchbase | REST API | https://data.crunchbase.com/docs |

---

## 🛍️ Ecommerce & Consumer Trends

| Source | Type | URL |
|--------|------|-----|
| IMRG UK Online Retail Index | Data Feed | https://www.imrg.org/data-and-reports/ |
| eMarketer Retail & Ecommerce | RSS | https://www.emarketer.com/rss |
| Statista Ecommerce Dataset (Paid) | Dataset | https://www.statista.com/study/42335/e-commerce-worldwide-statista-dossier/ |
| Retail Economics Reports | Web | https://www.retaileconomics.co.uk/library |

---

## 📰 General & Marketing Trends

| Source | Type | URL |
|--------|------|-----|
| Google Trends Daily Search Trends | RSS / JSON | https://trends.google.com/trends/trendingsearches/daily/rss?geo=GB |
| Think with Google (UK) | RSS | https://www.thinkwithgoogle.com/intl/en-gb/feed/ |
| Marketing Week | RSS | https://www.marketingweek.com/feed/ |
| Campaign UK | RSS | https://www.campaignlive.co.uk/news/rss |

---

## 🕓 Local Business / SME Insights

| Source | Type | URL |
|--------|------|-----|
| Federation of Small Businesses (FSB) | Web | https://www.fsb.org.uk/resources.html |
| British Business Bank | News / Data | https://www.british-business-bank.co.uk/news-and-events/ |
| Gov.UK Business Statistics | Open Data | https://www.gov.uk/government/statistics |

---

## ⚡ Developer / Tech Trend Signals (Optional)

| Source | Type | URL |
|--------|------|-----|
| GitHub Trending | JSON API | https://github.com/trending |
| Stack Overflow Insights | Blog / RSS | https://stackoverflow.blog/feed/ |
| Product Hunt Trending | RSS | https://www.producthunt.com/feed |

---

## ⚙️ Suggested Fetch Schedule

| Cadence | Example Sources |
|----------|-----------------|
| Hourly | Search Status, RSS Feeds (SEO/Trends) |
| Daily | Google Trends, CrUX API, Moz/Ahrefs Feeds |
| Weekly | ONS, ONS Retail Index, WordStream Benchmarks |
| Monthly | IAB Adspend, IPA Bellwether, Ofcom Reports |
| Quarterly | Regional (GBCC, WMCA) Reports |

---

*Last updated: 2025-10-13*
