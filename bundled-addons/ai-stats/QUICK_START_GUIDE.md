# AI-Stats Quick Start Guide

**Version:** 0.1.0  
**Status:** Ready for Testing

---

## Installation (2 Minutes)

### Step 1: Install via AI-Core Add-ons

1. Go to **AI-Core > Add-ons** in WordPress admin
2. Find "AI-Stats" in the list
3. Click **"Install Now"**
4. Click **"Activate"**

✅ Done! AI-Stats is now installed and ready to use.

---

## First-Time Setup (3 Minutes)

### Step 2: Configure Settings

1. Go to **AI-Stats > Settings**
2. Select your preferred **Active Mode** (start with "Statistical Authority Injector")
3. Set **Update Frequency** to "Daily"
4. Check **"Enable automatic content updates"** (optional)
5. Keep other settings as default
6. Click **"Save Settings"**

### Step 3: Generate First Content

1. Go to **AI-Stats > Dashboard**
2. Click **"Generate Now"**
3. Wait 10-30 seconds for AI to generate content
4. Review the generated content in the preview box

---

## Display Content on Your Site (1 Minute)

### Step 4: Add Shortcode

Add this shortcode to any page or post:

```
[ai_stats_module]
```

**That's it!** The dynamic content will now display on your page.

---

## Quick Reference

### Shortcode Options

**Basic (uses current active mode):**
```
[ai_stats_module]
```

**Specific mode:**
```
[ai_stats_module mode="statistics"]
```

**Different style:**
```
[ai_stats_module style="inline"]
```

**Combined:**
```
[ai_stats_module mode="birmingham" style="box"]
```

### Available Modes

1. `statistics` - Business statistics with citations
2. `birmingham` - Birmingham-specific business data
3. `trends` - Latest industry trends
4. `benefits` - Service benefit descriptions
5. `seasonal` - Seasonal service angles
6. `process` - Detailed process descriptions

### Available Styles

- `box` - Highlighted box with border (default)
- `inline` - Inline text with subtle background
- `sidebar` - Sidebar widget style

---

## Testing Checklist

Before going live, test these:

- [ ] Generate content for each mode
- [ ] Test shortcode on a test page
- [ ] Try all 3 display styles
- [ ] Check content updates automatically (if enabled)
- [ ] Verify content looks good on mobile
- [ ] Review generated content for accuracy

---

## Common Use Cases

### Homepage Statistics Box

Add authority to your homepage:

```
[ai_stats_module mode="statistics" style="box"]
```

Example output: "90% of businesses see 200% ROI from SEO within 12 months (Source: HubSpot)"

### Birmingham Local Focus

Show local expertise:

```
[ai_stats_module mode="birmingham" style="box"]
```

Example output: "Join 12,847 Birmingham businesses growing online"

### Service Page Trends

Add industry insights to service pages:

```
[ai_stats_module mode="trends" style="inline"]
```

Example output: "Google's October update prioritises mobile speed - Our sites are optimised for Core Web Vitals"

### Sidebar Widget

Add to sidebar:

```
[ai_stats_module mode="statistics" style="sidebar"]
```

---

## Troubleshooting

### Content Not Generating

**Problem:** "Generate Now" button doesn't work  
**Solution:** 
1. Check AI-Core is configured with at least one API key
2. Go to AI-Core > Settings and verify API key is saved
3. Check browser console for JavaScript errors

### Content Not Displaying

**Problem:** Shortcode shows but no content  
**Solution:**
1. Go to AI-Stats > Dashboard
2. Click "Generate Now" to create initial content
3. Verify content exists in Content Library

### Scraping Errors

**Problem:** "Failed to fetch data" errors  
**Solution:**
1. Check your server can make external HTTP requests
2. Some sources may be temporarily unavailable
3. Try a different mode that uses different sources

---

## Next Steps

### Optimise Your Setup

1. **Test All Modes:** Try each of the 6 modes to see which works best
2. **Set Update Frequency:** Daily for statistics/trends, weekly for benefits/process
3. **Enable Automation:** Let AI-Stats update content automatically
4. **Monitor Performance:** Check Content Library to see what's been generated

### Advanced Features (Coming Soon)

- Performance tracking (impressions, clicks, CTR)
- A/B testing different modes
- Google Search Console integration
- Custom mode builder

---

## Support

**Documentation:** See `README.md` for complete documentation  
**Implementation Details:** See `IMPLEMENTATION_SUMMARY.md` for technical details  
**Issues:** Contact support@opace.agency

---

## Quick Tips

💡 **Start Simple:** Begin with "Statistical Authority Injector" mode  
💡 **Test First:** Generate content manually before enabling automation  
💡 **Review Content:** Always review AI-generated content before going live  
💡 **Use Caching:** Keep caching enabled to reduce API costs  
💡 **Birmingham Focus:** Enable if you're a Birmingham-based business  
💡 **Multiple Shortcodes:** You can use different modes on different pages  

---

## Example Workflow

**Week 1:** Test "Statistical Authority Injector" mode
- Generate content daily
- Monitor which statistics resonate
- Measure impact on engagement

**Week 2:** Switch to "Birmingham Business Stats" mode
- Compare local vs. general statistics
- Track which performs better
- Adjust based on results

**Week 3:** Try "Industry Trend Micro-Module" mode
- Keep content fresh with latest trends
- Show you're up-to-date
- Build authority

**Week 4:** Rotate between modes
- Use different modes for different pages
- Test seasonal angles
- Optimise based on performance

---

## Success Metrics

Track these to measure success:

- **Engagement:** Time on page, scroll depth
- **Trust Signals:** Bounce rate, pages per session
- **Conversions:** Contact form submissions, calls
- **SEO:** Rankings for target keywords
- **Social Proof:** Shares, mentions

---

## Best Practices

✅ **DO:**
- Review generated content before publishing
- Use different modes for different pages
- Enable caching to reduce costs
- Test on staging first
- Monitor performance regularly

❌ **DON'T:**
- Rely solely on automation without review
- Use the same mode everywhere
- Disable caching (increases costs)
- Deploy to production without testing
- Ignore performance metrics

---

## Getting Help

1. **Check Documentation:** README.md has detailed information
2. **Review Examples:** See example outputs in this guide
3. **Test Modes:** Try different modes to find what works
4. **Contact Support:** support@opace.agency for assistance

---

**Ready to get started? Go to AI-Stats > Dashboard and click "Generate Now"!**

