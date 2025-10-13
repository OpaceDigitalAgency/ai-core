# AI-Stats - Dynamic SEO Content Modules

**Version:** 0.2.0
**Requires:** WordPress 5.0+, PHP 7.4+, AI-Core 0.3.6+
**Author:** Opace Digital Agency

## Overview

AI-Stats is a powerful WordPress plugin that generates dynamic, data-driven content from **60+ authoritative data sources**. It operates in **manual workflow mode**, allowing full control over data fetching, curation, and publishing. The plugin supports both **AI-powered generation** (via AI-Core) and **raw bullet formatting** (no AI), making it ideal for testing data sources and fine-tuning prompts before enabling automatic scheduling.

### What's New in v0.2.0

- ✅ **Multi-Feed Data Engine**: 60+ RSS feeds, APIs, and HTML sources
- ✅ **Manual Workflow**: Fetch → Preview → Select → Generate → Publish
- ✅ **LLM On/Off Toggle**: Choose AI generation or raw bullets
- ✅ **Smart Scoring**: Candidates ranked by freshness, authority, and confidence
- ✅ **Full Audit Trail**: Track sources, items, models, and tokens
- ✅ **6 Content Modes**: Statistics, Birmingham, Trends, Benefits, Seasonal, Process

## Key Features

### 6 Content Modes

1. **Statistical Authority Injector**
   - Injects authoritative business statistics with citations
   - Sources: HubSpot, Statista, industry reports
   - Updates: Daily

2. **Birmingham Business Stats**
   - Local Birmingham business statistics and data
   - Sources: Birmingham Chamber of Commerce, ONS, Birmingham.gov.uk
   - Updates: Daily

3. **Industry Trend Micro-Module**
   - Latest SEO and web design industry trends
   - Sources: Search Engine Land, Moz, Google Search Blog, Smashing Magazine
   - Updates: Daily

4. **Service + Benefit Semantic Expander**
   - Benefit-focused service descriptions
   - Sources: Site content analysis
   - Updates: Weekly

5. **Seasonal Service Angle Rotator**
   - Seasonal variations of service offerings
   - Sources: Calendar data, seasonal trends
   - Updates: Monthly

6. **Service Process Micro-Step Enhancer**
   - Detailed process descriptions demonstrating expertise
   - Sources: Site content analysis
   - Updates: Weekly

### Core Features

- **Real-Time Data Scraping**: Fetches live data from authoritative sources
- **AI-Powered Generation**: Uses AI-Core for intelligent content creation
- **Automatic Updates**: WP Cron scheduling for hands-free operation
- **Shortcode System**: Simple `[ai_stats_module]` shortcode
- **Multiple Styles**: Box, inline, sidebar widget display options
- **Performance Tracking**: Track impressions and clicks (Coming Soon)
- **Content History**: Full history of generated content
- **Cache System**: Intelligent caching to reduce API calls

## Installation

### Via AI-Core Add-ons Page (Recommended)

1. Navigate to **AI-Core > Add-ons**
2. Find "AI-Stats" in the list
3. Click "Install Now"
4. Click "Activate"

### Manual Installation

1. Copy the `ai-stats` folder to `/wp-content/plugins/`
2. Navigate to **Plugins** in WordPress admin
3. Find "AI-Stats" and click "Activate"

## Requirements

- **AI-Core Plugin**: Must be installed and activated
- **API Keys**: At least one AI provider configured in AI-Core (OpenAI, Anthropic, Gemini, or Grok)
- **WordPress**: Version 5.0 or higher
- **PHP**: Version 7.4 or higher

## Quick Start

### 1. Configure AI-Core

Ensure AI-Core is configured with at least one API key:
- Go to **AI-Core > Settings**
- Add your API key(s)
- Save settings

### 2. Select a Mode

1. Go to **AI-Stats > Settings**
2. Choose your preferred content mode
3. Configure update frequency
4. Enable automatic updates (optional)
5. Save settings

### 3. Generate Content

**Option A: Automatic**
- Enable "Automatic content updates" in settings
- Content will generate based on your update frequency

**Option B: Manual**
- Go to **AI-Stats > Dashboard**
- Click "Generate Now"

### 4. Display Content

Add the shortcode to any page or post:

```
[ai_stats_module]
```

**With specific mode:**
```
[ai_stats_module mode="statistics"]
```

**With custom style:**
```
[ai_stats_module style="inline"]
```

## Usage Examples

### Example 1: Homepage Statistics Box

```
[ai_stats_module mode="statistics" style="box"]
```

Output: "90% of businesses see 200% ROI from SEO within 12 months (Source: HubSpot)"

### Example 2: Birmingham Focus

```
[ai_stats_module mode="birmingham" style="box"]
```

Output: "Join 12,847 Birmingham businesses growing online"

### Example 3: Industry Trends

```
[ai_stats_module mode="trends" style="inline"]
```

Output: "Google's October update prioritises mobile speed - Our WooCommerce sites are optimised for Core Web Vitals"

## Settings

### Active Mode
Select which content generation mode to use.

### Update Frequency
- **Daily**: Generate new content every day
- **Weekly**: Generate new content every week
- **Manual**: Only generate when you click "Generate Now"

### Automation
Enable automatic content updates based on your update frequency.

### Default Style
Choose the default display style:
- **Box**: Highlighted box with border
- **Inline**: Inline text with subtle background
- **Sidebar**: Sidebar widget style

### Caching
Enable data caching to reduce external requests and API calls.

### Performance Tracking
Track impressions and clicks on generated content (Coming Soon).

### Birmingham Focus
Prioritise Birmingham-specific data when available.

## Architecture

### Database Tables

1. **ai_stats_content**: Stores generated content
2. **ai_stats_history**: Tracks content history
3. **ai_stats_performance**: Performance metrics
4. **ai_stats_cache**: Scraped data cache

### Data Sources

- **Birmingham Chamber of Commerce**: Local business statistics
- **ONS (Office for National Statistics)**: UK business data
- **Birmingham.gov.uk**: Local government data
- **HubSpot**: Marketing statistics
- **Statista**: Business statistics
- **Search Engine Land**: SEO news
- **Moz**: SEO insights
- **Google Search Blog**: Search updates
- **Smashing Magazine**: Web design trends

### AI Integration

AI-Stats integrates seamlessly with AI-Core:
- Uses configured API keys automatically
- Supports all AI-Core providers (OpenAI, Anthropic, Gemini, Grok)
- Tracks usage through AI-Core statistics
- No separate API configuration needed

## Development

### File Structure

```
ai-stats/
├── ai-stats.php                    # Main plugin file
├── includes/
│   ├── class-ai-stats-database.php # Database management
│   ├── class-ai-stats-scraper.php  # Web scraping
│   ├── class-ai-stats-generator.php # Content generation
│   ├── class-ai-stats-modes.php    # Mode definitions
│   ├── class-ai-stats-settings.php # Settings management
│   ├── class-ai-stats-shortcode.php # Shortcode handler
│   └── class-ai-stats-scheduler.php # WP Cron scheduling
├── admin/
│   ├── class-ai-stats-admin.php    # Admin interface
│   ├── class-ai-stats-ajax.php     # AJAX handlers
│   └── views/                      # Admin page templates
├── assets/
│   ├── css/                        # Stylesheets
│   └── js/                         # JavaScript
└── uninstall.php                   # Clean uninstall
```

### Hooks & Filters

**Actions:**
- `ai_stats_daily_update`: Daily content update
- `ai_stats_weekly_update`: Weekly maintenance

**Filters:**
- `ai_stats_content_before_display`: Modify content before display
- `ai_stats_scraper_sources`: Modify data sources
- `ai_stats_generator_prompt`: Modify AI prompts

## Roadmap

### Version 0.2.0
- [ ] A/B testing functionality
- [ ] Google Search Console integration
- [ ] Advanced performance tracking
- [ ] Content approval queue

### Version 0.3.0
- [ ] Custom mode builder
- [ ] Template system
- [ ] Multi-language support
- [ ] REST API endpoints

### Version 1.0.0
- [ ] WordPress.org submission
- [ ] Complete documentation
- [ ] Video tutorials
- [ ] Premium features

## Support

For support, please:
1. Check the documentation
2. Visit [Opace Digital Agency](https://opace.agency)
3. Contact support@opace.agency

## License

GPLv2 or later

## Credits

Developed by [Opace Digital Agency](https://opace.agency)

## Changelog

### 0.1.0 (2025-10-10)
- Initial release
- 6 content modes implemented
- Real-time web scraping
- AI-powered content generation
- Shortcode system
- Admin dashboard
- Automatic scheduling
- Cache system

