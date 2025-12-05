# AI-Pulse Quick Start Guide

## What is AI-Pulse?

AI-Pulse is a WordPress plugin that generates **SEO-optimised, crawlable HTML content** for your service pages using Google's Gemini AI with real-time web search.

**Key Benefits:**
- ✅ **Instant Load Times** - Pre-generated HTML (not 3-minute waits like AI-Stats)
- ✅ **Verified Data** - Google Search Grounding ensures accurate, cited sources
- ✅ **SEO-Friendly** - Fully crawlable static HTML with Schema.org markup
- ✅ **Low Maintenance** - Single API integration (no complex scraping pipelines)
- ✅ **Cost-Effective** - ~$3-5/month for 10 keywords with daily updates

---

## How It Works

```
1. Schedule content generation (e.g., 3am daily)
   ↓
2. Gemini searches the web for latest trends/stats
   ↓
3. AI generates structured JSON with verified sources
   ↓
4. Plugin converts JSON to semantic HTML
   ↓
5. HTML stored in database
   ↓
6. Shortcode renders HTML instantly on page load
```

---

## Installation

### Prerequisites
- WordPress 5.0+
- PHP 7.4+
- **AI-Core plugin** (parent plugin)
- **Google Gemini API key** (required)

### Steps
1. Install and activate AI-Core
2. Configure Google Gemini API key in AI-Core settings
3. Install AI-Pulse (bundled add-on)
4. Activate AI-Pulse
5. Configure settings (keywords, schedule, location)

---

## Basic Configuration

### 1. Add Keywords
Navigate to **AI-Pulse → Settings → Service Keywords**

Add your service keywords:
- SEO
- Web Design
- Digital Marketing
- PPC Advertising
- etc.

### 2. Set Schedule
**AI-Pulse → Settings → General Settings**

- **Update Schedule:** Daily at 3am (recommended)
- **Time Period:** Weekly (for trend analysis)
- **Location Focus:** Birmingham, West Midlands, UK

### 3. Choose Active Modes
Select which analysis modes to generate:
- ✅ SUMMARY (General trends)
- ✅ FAQS (Common questions)
- ✅ STATS (Market statistics)
- ✅ LOCAL (Regional trends)
- ⬜ FORECAST (Seasonality)
- ⬜ GAPS (Opportunities)
- etc.

---

## Using Shortcodes

### Basic Usage
```
[ai_pulse keyword="SEO" mode="SUMMARY"]
```

### With Options
```
[ai_pulse keyword="Web Design" mode="FAQS" style="inline" show_sources="true"]
```

### Mega Dashboard (All Modes)
```
[ai_pulse keyword="Digital Marketing" mode="ALL"]
```

### Shortcode Attributes

| Attribute | Options | Default | Description |
|-----------|---------|---------|-------------|
| `keyword` | Any text | Required | Service keyword to analyse |
| `mode` | SUMMARY, FAQS, STATS, LOCAL, FORECAST, GAPS, WINS, GLOSSARY, PLATFORMS, PULSE, EXPLORER, ALL | SUMMARY | Analysis type |
| `style` | box, inline, minimal | box | Display style |
| `show_sources` | true, false | true | Show citation links |

---

## Analysis Modes Explained

### SUMMARY
**What:** Top 5 rising trends related to your keyword
**Best For:** Service page introductions, blog posts
**Example Output:** "This month in SEO: Core Web Vitals updates, AI search integration..."

### FAQS
**What:** 5 common buyer questions with answers
**Best For:** FAQ sections, Schema.org markup
**Example Output:** "What is local SEO?" → "Local SEO optimises your website..."

### STATS
**What:** 3-4 verified market statistics with citations
**Best For:** Landing pages, case studies
**Example Output:** "73% of UK businesses increased SEO budgets in 2024 (Source: Statista)"

### LOCAL
**What:** Regional trends (Birmingham/West Midlands focus)
**Best For:** Local service pages, GBP optimisation
**Example Output:** "Birmingham businesses searching for 'local SEO' up 45% this quarter"

### ALL (Mega Dashboard)
**What:** All 11 modes in one comprehensive analysis
**Best For:** Main service pages, pillar content
**Output:** Complete market intelligence report

---

## Admin Interface

### Content Manager
**AI-Pulse → Content Manager**

View all generated content:
- Filter by keyword, mode, date
- Preview HTML output
- Activate/deactivate content
- Regenerate on-demand
- View token usage and costs

### Debug & Testing
**AI-Pulse → Debug**

Test content generation:
- Enter keyword + mode
- Preview prompt sent to Gemini
- View raw JSON response
- See HTML conversion
- Check token counts and costs

### Usage Statistics
**AI-Pulse → Statistics**

Monitor API usage:
- Total tokens used (input/output)
- Cost breakdown by keyword/mode
- Monthly spend tracking
- Cost projections

---

## Performance

### Load Times
- **Shortcode rendering:** < 100ms (serving pre-generated HTML)
- **Content generation:** 5-10 seconds per mode
- **Mega Dashboard:** 20-30 seconds (all 11 modes)

### Caching
- Content cached in database (default: 24 hours)
- WordPress object cache support
- CDN-friendly static HTML

---

## Costs

### Gemini API Pricing
- Input: $3.50 per 1M tokens
- Output: $10.50 per 1M tokens

### Estimated Monthly Costs
- **10 keywords, daily updates, SUMMARY mode:** ~$3.45/month
- **10 keywords, weekly updates, ALL mode:** ~$1.89/month
- **20 keywords, daily updates, FAQS mode:** ~$7.56/month

---

## Migration from AI-Stats

### Automatic Migration
1. Go to **AI-Pulse → Settings → Migration**
2. Click "Import from AI-Stats"
3. Review imported keywords and settings
4. Click "Generate Initial Content"
5. Test shortcodes on a sample page
6. Deactivate AI-Stats when satisfied

### Manual Migration
1. Export keywords from AI-Stats
2. Add keywords to AI-Pulse
3. Find/replace shortcodes:
   - Old: `[ai_stats_module mode="statistics"]`
   - New: `[ai_pulse keyword="SEO" mode="STATS"]`
4. Generate content for all keywords
5. Deactivate AI-Stats

---

## Troubleshooting

### Content Not Generating
- ✅ Check Gemini API key is configured in AI-Core
- ✅ Verify WP Cron is running (`wp cron event list`)
- ✅ Check error logs in **AI-Pulse → Debug → Error Logs**
- ✅ Try manual generation in Content Manager

### Shortcode Not Displaying
- ✅ Ensure content has been generated for that keyword/mode
- ✅ Check shortcode syntax (keyword is required)
- ✅ Verify content is marked as "active" in Content Manager
- ✅ Clear WordPress cache

### High API Costs
- ⚙️ Reduce update frequency (weekly instead of daily)
- ⚙️ Use fewer modes (SUMMARY + FAQS only)
- ⚙️ Increase cache duration (48-72 hours)
- ⚙️ Limit number of keywords

---

## Support & Documentation

- **Full Documentation:** `AI_PULSE_IMPLEMENTATION_PLAN.md`
- **GitHub Issues:** https://github.com/OpaceDigitalAgency/ai-core/issues
- **Email Support:** support@opace.agency

---

## Roadmap

### Version 1.1 (Planned)
- [ ] Multi-location support (per-keyword location settings)
- [ ] Custom prompt templates
- [ ] Content versioning and rollback
- [ ] A/B testing for different modes

### Version 1.2 (Planned)
- [ ] Integration with Google Analytics
- [ ] Automated content refresh based on traffic
- [ ] Custom HTML templates (theme overrides)
- [ ] REST API endpoints

---

**Last Updated:** 2025-12-05
**Plugin Version:** 1.0.0
**Status:** Ready for Development

