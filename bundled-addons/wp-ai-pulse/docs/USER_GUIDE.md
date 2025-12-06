# AI-Pulse User Guide

## How AI-Pulse Works

AI-Pulse generates SEO-optimised, crawlable HTML content using Google Gemini with Search Grounding. It provides 11 different analysis modes for market intelligence, trends, FAQs, and more.

---

## Workflow Overview

### 1. **Test Interface** (Manual Generation)
- Generate content on-demand for testing
- Preview results immediately
- Content is saved to database automatically
- Appears in Content Library and Statistics

### 2. **Content Library** (Storage)
- View all generated content
- Each entry shows: keyword, mode, period, tokens, cost
- Delete old content
- Copy shortcodes for use on pages/posts

### 3. **Scheduled Generation** (Automation)
- Configure keywords and modes to auto-generate
- Runs via WP Cron at scheduled times
- Pre-generates content for instant page loads
- Updates existing content automatically

### 4. **Shortcodes** (Display)
- Use `[ai_pulse]` shortcode to display content on pages/posts
- Content loads instantly (pre-generated HTML)
- No API calls on page load = fast performance

---

## Step-by-Step Usage

### Step 1: Generate Test Content

1. Go to **AI-Core → AI-Pulse → Test Interface**
2. Enter a keyword (e.g., "SEO", "Web Design", "Digital Marketing")
3. Select analysis mode (Summary, Trends, FAQ, etc.)
4. Select time period (Daily, Weekly, Monthly)
5. Optionally add location (e.g., "Birmingham, UK")
6. Click **Generate Content**
7. View results in tabs: Preview, Sources, JSON, Usage

**What happens:**
- Content is generated using Gemini with Google Search Grounding
- HTML, JSON, sources, and metadata are saved to database
- You can now use this content via shortcode

---

### Step 2: View in Content Library

1. Go to **AI-Core → AI-Pulse → Content Library**
2. You should see your generated content listed
3. Each row shows:
   - Keyword
   - Mode
   - Period
   - When generated
   - Token usage
   - Cost
   - Actions (View/Delete)

**Troubleshooting:**
- If content doesn't appear, check browser console for errors
- Verify database table exists: `wp_ai_pulse_content`
- Check WordPress debug log for errors

---

### Step 3: Use Shortcode on Pages/Posts

#### Basic Shortcode
```
[ai_pulse keyword="SEO" mode="SUMMARY" period="weekly"]
```

#### With Location
```
[ai_pulse keyword="Web Design" mode="TRENDS" period="monthly" location="Birmingham, UK"]
```

#### Generate On-Demand (Not Recommended for Production)
```
[ai_pulse keyword="SEO" mode="FAQ" period="daily" generate="true"]
```

**Shortcode Attributes:**
- `keyword` (required) - The keyword/topic to analyse
- `mode` (optional, default: SUMMARY) - Analysis mode
- `period` (optional, default: weekly) - Time period
- `location` (optional) - Geographic location
- `generate` (optional, default: false) - Generate on page load (slow!)

**Available Modes:**
- `SUMMARY` - Daily Summary
- `TRENDS` - Market Trends
- `FAQ` - Frequently Asked Questions
- `STATS` - Key Statistics
- `INSIGHTS` - Strategic Insights
- `COMPARISON` - Competitive Analysis
- `FORECAST` - Future Predictions
- `CASE_STUDY` - Success Stories
- `TOOLS` - Recommended Tools
- `CHECKLIST` - Action Checklist
- `GLOSSARY` - Industry Terminology

---

### Step 4: Schedule Automatic Generation

1. Go to **AI-Core → AI-Pulse → Settings**
2. Configure scheduled keywords:
   - Add keywords you want to auto-generate
   - Select modes for each keyword
   - Set time period
   - Set location (optional)
3. Configure schedule:
   - Set generation time (e.g., 2:00 AM)
   - Set frequency (daily, weekly, etc.)
4. Save settings

**What happens:**
- WP Cron runs at scheduled time
- Generates content for all configured keywords
- Updates existing content (deactivates old, stores new)
- Content is ready instantly when shortcode is used

---

## Database Structure

### Table: `wp_ai_pulse_content`

| Column | Type | Description |
|--------|------|-------------|
| id | BIGINT | Unique ID |
| keyword | VARCHAR(255) | Search keyword |
| mode | VARCHAR(50) | Analysis mode |
| period | VARCHAR(20) | Time period |
| content_html | LONGTEXT | Generated HTML |
| content_json | LONGTEXT | Structured JSON data |
| sources_json | TEXT | Google Search sources |
| date_range | VARCHAR(100) | Date range analysed |
| input_tokens | INT | Input tokens used |
| output_tokens | INT | Output tokens used |
| cost_usd | DECIMAL(10,6) | Cost in USD |
| generated_at | DATETIME | Generation timestamp |
| is_active | TINYINT(1) | Active flag (1=active, 0=old) |

**Note:** Only `is_active=1` content is displayed. Old content is deactivated when new content is generated for the same keyword/mode/period combination.

---

## Troubleshooting

### Content Not Appearing in Library

**Check:**
1. Browser console for JavaScript errors
2. Network tab for failed AJAX requests
3. WordPress debug log: `wp-content/debug.log`
4. Database table exists: Run `SHOW TABLES LIKE 'wp_ai_pulse_content'`

**Fix:**
1. Deactivate and reactivate plugin (creates tables)
2. Check file permissions
3. Verify AI-Core is configured with Gemini API key

### Shortcode Shows "No Content Available"

**Reasons:**
1. Content hasn't been generated yet for that keyword/mode/period
2. Content was deleted
3. Database query failed

**Fix:**
1. Generate content via Test Interface first
2. Enable auto-generate missing: Settings → Auto-generate missing content
3. Use `generate="true"` attribute (not recommended for production)

### Sources Not Showing

**Reasons:**
1. Google Search Grounding didn't return sources for that query
2. ResponseNormalizer not preserving metadata (fixed in v1.0.8)

**Fix:**
1. Try different keywords (more specific = better sources)
2. Update to AI-Pulse v1.0.8+ and AI-Core v0.7.7+

---

## Best Practices

1. **Pre-generate content** via scheduled generation (don't use `generate="true"` on live pages)
2. **Use specific keywords** for better Search Grounding results
3. **Set appropriate time periods** (daily for news, monthly for trends)
4. **Monitor token usage** in Statistics tab to control costs
5. **Delete old content** regularly to keep database clean
6. **Use location** for local business content
7. **Test first** in Test Interface before adding to live pages

---

## Performance Tips

- Pre-generated content loads instantly (no API calls)
- Schedule generation during off-peak hours (2-4 AM)
- Use caching plugins for even faster delivery
- Limit scheduled keywords to what you actually use
- Monitor costs in Statistics tab

---

## Support

For issues or questions:
1. Check WordPress debug log
2. Review browser console errors
3. Verify AI-Core configuration
4. Contact Opace Digital Agency

