# AI-Stats Manual Workflow Guide

**Version:** 0.2.0  
**Last Updated:** 2025-10-13

## Overview

The AI-Stats plugin has been evolved from a static single-source scraper into a **multi-feed, data-driven content engine** that operates in **manual mode**. This allows full visibility and control while testing how new APIs and RSS feeds perform, verifying that each data source returns reliable results, and fine-tuning prompt logic before any automatic scheduling is re-enabled.

## Key Features

### Multi-Source Data Fetching
- **60+ Data Sources** across 6 modes
- **RSS Feeds**: Search Engine Land, Moz, Google Search Status, etc.
- **APIs**: ONS, Companies House, CrUX, UK Bank Holidays, etc.
- **HTML Scraping**: WordStream benchmarks, Mailchimp benchmarks (fallback)

### Manual Control Workflow
1. **Fetch & Preview** – Gather 3–12 items from multiple sources
2. **Select & Curate** – Tick include/exclude for each item
3. **Generate Draft** – AI on/off toggle for content generation
4. **Publish** – Store as active module with full audit trail

### LLM Integration
- **LLM ON**: Uses AI-Core to generate 2–3 concise, fact-based bullets
- **LLM OFF**: Publishes curated bullets with citations (no AI)
- **Provider Choice**: OpenAI, Anthropic, Gemini, or xAI Grok

## How to Use

### Step 1: Configure Settings

Navigate to **AI-Stats → Settings** and configure:

1. **Active Mode**: Choose from 6 content modes
2. **API Keys** (optional):
   - Google API Key (for CrUX data)
   - Companies House API Key (for UK company data)
   - CrUX Test URL (defaults to your site)
3. **Preferred AI Provider**: Select OpenAI, Anthropic, Gemini, or Grok
4. **Auto-Update**: Keep disabled for manual mode

### Step 2: Open Fetch & Preview Modal

1. Go to **AI-Stats → Dashboard**
2. Click **"Generate Now"** or **"Generate First Content"**
3. The Fetch & Preview modal will open

### Step 3: Configure Fetch Parameters

In the modal:

1. **Mode**: Select content mode (Statistics, Birmingham, Trends, etc.)
2. **Keywords**: Enter comma-separated keywords (e.g., "SEO, web design, Birmingham")
3. **LLM Toggle**: Check to use AI, uncheck for raw bullets
4. Click **"Fetch & Preview"**

### Step 4: Review & Select Candidates

The system will fetch 6–12 items from multiple sources and display them in a table:

- **Title**: Headline or summary
- **Source**: Data source name (ONS, Google, etc.)
- **Age**: How recent the data is
- **Score**: Relevance score (freshness + authority + confidence)

**Actions:**
- Check/uncheck items to include/exclude
- Use "Select All" to toggle all items
- Review blurb seeds (preview text)

### Step 5: Generate Draft

Click **"Generate Draft"** to create content:

**With LLM ON:**
- Sends selected items to AI-Core
- Generates 2–3 concise bullets (≤22 words each)
- Includes source citations
- Uses British English
- Shows token usage and model used

**With LLM OFF:**
- Creates HTML list from selected items
- Includes title, blurb, source, and link
- No AI processing
- No token usage

### Step 6: Review Draft

The draft preview shows:
- Generated HTML content
- Sources used
- Model and token count (if LLM was used)

**Actions:**
- Click **"Publish"** to make it live
- Click **"Back to Selection"** to modify selection

### Step 7: Publish Module

Click **"Publish"** to:
- Deactivate previous content for this mode
- Store new module in database
- Update shortcode output
- Save full audit trail (sources, items, LLM settings)

## Content Modes

### 1. Statistical Authority Injector
**Purpose:** Inject authoritative business statistics with citations  
**Sources:** ONS, Eurostat, World Bank, Companies House  
**Update:** Weekly  
**Example:** "90% of businesses see 200% ROI from SEO within 12 months [Source: HubSpot]"

### 2. Birmingham Business Stats
**Purpose:** Local Birmingham business statistics and data  
**Sources:** Birmingham City Observatory, WMCA, ONS Regional, Birmingham.gov.uk  
**Update:** Weekly  
**Example:** "Join 12,847 Birmingham businesses growing online [Source: Birmingham Chamber]"

### 3. Industry Trend Micro-Module
**Purpose:** Latest SEO and web design industry trends  
**Sources:** Search Engine Land, SEJ, Google Search Status, Moz, Smashing Magazine, CrUX  
**Update:** Hourly/Daily  
**Example:** "Core update active; volatility likely [Source: Google Search Status]"

### 4. Service + Benefit Semantic Expander
**Purpose:** Benefit-focused service descriptions  
**Sources:** HubSpot Marketing, Think with Google, WordStream, Mailchimp  
**Update:** Weekly  
**Example:** "Email marketing delivers £42 ROI for every £1 spent [Source: Mailchimp]"

### 5. Seasonal Service Angle Rotator
**Purpose:** Seasonal variations of service offerings  
**Sources:** UK Bank Holidays, Calendarific, Google Trends Daily  
**Update:** Monthly  
**Example:** "Upcoming UK bank holiday: Early May Bank Holiday on 5 May 2025 [Source: GOV.UK]"

### 6. Service Process Micro-Step Enhancer
**Purpose:** Detailed process descriptions demonstrating expertise  
**Sources:** Nielsen Norman Group, UX Collective, Smashing Magazine UX  
**Update:** Weekly  
**Example:** "User testing with 5 participants uncovers 85% of usability issues [Source: NN/g]"

## Data Source Registry

The plugin uses a **centralised source registry** that maps data sources to modes:

- **Type**: RSS, API, or HTML
- **Name**: Source identifier
- **URL**: Endpoint or feed URL
- **Update Frequency**: Hourly, daily, weekly, monthly, quarterly
- **Tags**: Category tags (uk_macro, seo, birmingham, etc.)

### Adding Custom Sources

Custom sources can be added programmatically:

```php
$registry = AI_Stats_Source_Registry::get_instance();
$registry->add_source('trends', array(
    'type' => 'RSS',
    'name' => 'Custom SEO Blog',
    'url' => 'https://example.com/feed',
    'update' => 'daily',
    'tags' => array('seo', 'custom'),
));
```

## Candidate Scoring

Candidates are scored based on:

1. **Freshness** (0–50 points):
   - < 1 day: 50 points
   - < 7 days: 30 points
   - < 30 days: 10 points

2. **Source Authority** (0–30 points):
   - Authoritative sources (ONS, GOV.UK, Google, Eurostat, Companies House): 30 points
   - Other sources: 0 points

3. **Confidence** (0–20 points):
   - Based on source type and data quality
   - RSS: 0.85, API: 0.90–1.0, HTML: 0.70

**Total Score**: 0–100 points (higher is better)

## Shortcode Usage

Display the active module using the shortcode:

```
[ai_stats_module]
```

**Optional Parameters:**
- `mode="statistics"` – Override active mode
- `style="inline"` – Display style (inline, box, sidebar)

**Example:**
```
[ai_stats_module mode="birmingham" style="box"]
```

## Database Schema

### ai_stats_content
Stores generated modules with metadata:

- `mode`: Content mode
- `content_type`: Always "module"
- `content`: Generated HTML
- `metadata`: JSON with llm, model, tokens, items, sources_used
- `sources`: Legacy sources array
- `generated_at`: Timestamp
- `is_active`: Active flag

### Metadata Structure

```json
{
  "llm": "on",
  "model": "gpt-4o-mini",
  "tokens": 245,
  "sources_used": [
    {"name": "ONS", "url": "https://..."},
    {"name": "Google", "url": "https://..."}
  ],
  "items": [
    {
      "title": "...",
      "source": "ONS",
      "url": "...",
      "published_at": "2025-10-13T10:00:00Z",
      "tags": ["uk_macro", "statistics"],
      "blurb_seed": "...",
      "geo": "GB",
      "confidence": 0.95,
      "score": 95
    }
  ]
}
```

## Caching

- **Fetch Cache**: 10 minutes (manual testing mode)
- **Content Cache**: 24 hours (configurable in settings)
- **Cache Key**: Based on source URL and mode

## Troubleshooting

### No Candidates Found
- Check keywords are relevant to the mode
- Verify API keys are configured (if using API sources)
- Check source URLs are accessible
- Review cache (may need to wait 10 minutes for refresh)

### AI Generation Failed
- Verify AI-Core is installed and configured
- Check API keys in AI-Core settings
- Ensure preferred provider is set correctly
- Review token limits and quotas

### Module Not Displaying
- Check shortcode is correct
- Verify module is published (check database)
- Clear WordPress cache
- Check `is_active` flag in database

## Best Practices

1. **Start with LLM OFF** to verify data quality
2. **Review all candidates** before generating
3. **Test different keywords** for better results
4. **Use authoritative sources** (ONS, GOV.UK, Google)
5. **Keep modules fresh** (regenerate weekly/monthly)
6. **Monitor token usage** when using LLM
7. **Save drafts** before publishing (future feature)

## Future Enhancements

- **Save as Draft** functionality
- **Revert to Previous** active module
- **Scheduled Auto-Generation** (when ready)
- **Custom Source UI** (add/edit/remove sources)
- **Bulk Operations** (generate multiple modes)
- **A/B Testing** (compare LLM on/off performance)

## Support

For issues or questions:
1. Check this guide
2. Review AI-Core documentation
3. Check WordPress debug log
4. Contact support with error details

---

**Remember:** This is a manual workflow designed for quality control and testing. Once you're confident in the data sources and prompts, you can re-enable automatic scheduling in future versions.

