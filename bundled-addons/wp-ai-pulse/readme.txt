=== AI-Pulse ===
Contributors: Opace Digital Agency
Tags: ai, trends, seo, content, gemini
Requires at least: 5.8
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Production-ready trend analysis system with Google Gemini Search Grounding for generating crawlable, static HTML content.

== Description ==

AI-Pulse is a production-ready trend analysis system that generates crawlable, static HTML content for service pages using Google's Gemini API with Search Grounding. It provides real-time market intelligence, FAQs, statistics, and strategic insights.

**Key Features:**

* 11 Analysis Modes: SUMMARY, FAQS, STATS, FORECAST, GAPS, LOCAL, WINS, GLOSSARY, PLATFORMS, PULSE, EXPLORER, plus ALL (mega dashboard)
* Google Gemini Search Grounding for accurate, cited information
* Pre-generated static HTML served instantly (no runtime API calls)
* WP Cron scheduling with gradual rollout to prevent rate limiting
* Shortcode system: `[ai_pulse keyword="SEO" mode="SUMMARY" period="weekly"]`
* Clean, modern TrendPulse-inspired admin interface
* Token usage tracking and cost monitoring
* British English content generation

**Requirements:**

* AI-Core plugin (parent plugin)
* Google Gemini API key configured in AI-Core

== Installation ==

1. Install and activate AI-Core plugin
2. Configure Google Gemini API key in AI-Core settings
3. Install AI-Pulse via AI-Core Add-ons page
4. Activate AI-Pulse
5. Configure keywords and scheduling in AI-Pulse settings

== Frequently Asked Questions ==

= What API does AI-Pulse use? =

AI-Pulse exclusively uses Google Gemini API with Search Grounding feature. This requires the `gemini-2.0-flash-exp` model.

= How does scheduling work? =

AI-Pulse uses WordPress Cron to generate content during off-peak hours (default: 3am). Content is pre-generated and stored in the database for instant serving.

= What are the 11 analysis modes? =

* SUMMARY: General trend analysis (5 rising themes)
* FAQS: Common buyer questions with answers
* STATS: Verified market statistics with citations
* FORECAST: Seasonality and demand windows
* GAPS: Opportunity gaps in the market
* LOCAL: Regional trends (Birmingham/West Midlands focus)
* WINS: Anonymised micro-case studies
* GLOSSARY: Trending terminology definitions
* PLATFORMS: Emerging search platforms
* PULSE: B2B buyer intent signals
* EXPLORER: Interactive trend themes
* ALL: Mega dashboard with all modes

= How do I use the shortcode? =

`[ai_pulse keyword="SEO" mode="SUMMARY" period="weekly"]`

Attributes:
* keyword (required): Target keyword
* mode: Analysis mode (default: SUMMARY)
* period: daily, weekly, or monthly (default: weekly)
* location: Override default location
* generate: Set to "true" for on-demand generation

== Changelog ==

= 1.0.0 =
* Initial release
* 11 analysis modes
* Google Gemini Search Grounding integration
* WP Cron scheduling
* Shortcode system
* Admin test interface
* Token usage tracking
* British English content generation

