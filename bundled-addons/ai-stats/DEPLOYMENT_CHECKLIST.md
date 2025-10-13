# AI-Stats v0.2.0 Deployment Checklist

**Version:** 0.2.0  
**Date:** 2025-10-13

## Pre-Deployment Testing

### 1. Environment Setup
- [ ] WordPress 5.0+ installed
- [ ] PHP 7.4+ configured
- [ ] AI-Core 0.3.6+ installed and activated
- [ ] AI-Core configured with at least one AI provider (OpenAI, Anthropic, Gemini, or Grok)
- [ ] Test database backup created

### 2. Plugin Installation
- [ ] Upload AI-Stats files to `wp-content/plugins/ai-core/bundled-addons/ai-stats/`
- [ ] Verify all files are present:
  - `ai-stats.php` (main plugin file)
  - `includes/` directory (7 class files)
  - `admin/` directory (2 class files + views)
  - `assets/` directory (CSS + JS)
  - Documentation files (README, guides)
- [ ] Activate plugin (should create database tables)
- [ ] Check for activation errors in debug log

### 3. Database Verification
- [ ] Verify tables created:
  - `wp_ai_stats_content`
  - `wp_ai_stats_history`
  - `wp_ai_stats_performance`
  - `wp_ai_stats_cache`
- [ ] Check table structure matches schema
- [ ] Verify default settings saved in `wp_options`

### 4. Settings Configuration
- [ ] Navigate to **AI-Stats → Settings**
- [ ] Configure basic settings:
  - [ ] Active Mode: Select default mode
  - [ ] Update Frequency: Set to "Manual Only"
  - [ ] Auto-Update: Keep disabled
  - [ ] Default Style: Choose display style
  - [ ] Enable Caching: Check
  - [ ] Cache Duration: 86400 (24 hours)
- [ ] Configure API keys (optional):
  - [ ] Google API Key (for CrUX)
  - [ ] Companies House API Key (for UK company data)
  - [ ] CrUX Test URL (defaults to site URL)
- [ ] Configure AI settings:
  - [ ] Preferred AI Provider: Select provider
- [ ] Save settings
- [ ] Verify settings saved correctly

### 5. Manual Workflow Testing

#### Test Mode: Statistical Authority Injector
- [ ] Go to **AI-Stats → Dashboard**
- [ ] Click **"Generate Now"**
- [ ] Modal opens successfully
- [ ] Select Mode: "Statistical Authority Injector"
- [ ] Enter Keywords: "business, statistics, UK"
- [ ] LLM Toggle: Checked (ON)
- [ ] Click **"Fetch & Preview"**
- [ ] Verify candidates fetched (should show 6–12 items)
- [ ] Verify sources include: ONS, Eurostat, World Bank, Companies House
- [ ] Check candidate data:
  - [ ] Title displayed
  - [ ] Source name shown
  - [ ] Age calculated correctly
  - [ ] Score displayed (0–100)
- [ ] Select 3–5 candidates (check boxes)
- [ ] Click **"Generate Draft"**
- [ ] Verify draft generated:
  - [ ] HTML content displayed
  - [ ] Sources listed
  - [ ] Model name shown (e.g., "gpt-4o-mini")
  - [ ] Token count displayed
- [ ] Click **"Publish"**
- [ ] Confirm publication
- [ ] Verify success message
- [ ] Modal closes
- [ ] Page reloads

#### Test Mode: Birmingham Business Stats
- [ ] Repeat above steps with Mode: "Birmingham Business Stats"
- [ ] Keywords: "Birmingham, business, local"
- [ ] Verify sources include: Birmingham City Observatory, WMCA, ONS Regional

#### Test Mode: Industry Trend Micro-Module
- [ ] Repeat with Mode: "Industry Trend Micro-Module"
- [ ] Keywords: "SEO, web design, Google"
- [ ] Verify sources include: Search Engine Land, SEJ, Google Search Status, Moz

#### Test LLM OFF
- [ ] Open modal
- [ ] Select any mode
- [ ] LLM Toggle: Unchecked (OFF)
- [ ] Fetch candidates
- [ ] Select items
- [ ] Generate draft
- [ ] Verify raw bullets displayed (no AI processing)
- [ ] Verify no model or token count shown
- [ ] Publish and verify

### 6. Shortcode Testing
- [ ] Create test page/post
- [ ] Add shortcode: `[ai_stats_module]`
- [ ] Preview page
- [ ] Verify module displays correctly
- [ ] Test with mode parameter: `[ai_stats_module mode="birmingham"]`
- [ ] Test with style parameter: `[ai_stats_module style="inline"]`
- [ ] Verify different styles render correctly

### 7. Error Handling
- [ ] Test with no API keys configured
- [ ] Test with invalid API keys
- [ ] Test with network disconnected
- [ ] Test with AI-Core not configured
- [ ] Test with no candidates found
- [ ] Test with empty selection
- [ ] Verify error messages display correctly
- [ ] Verify no PHP errors in debug log

### 8. Browser Compatibility
- [ ] Test in Chrome (latest)
- [ ] Test in Firefox (latest)
- [ ] Test in Safari (latest)
- [ ] Test in Edge (latest)
- [ ] Verify modal displays correctly
- [ ] Verify table is responsive
- [ ] Verify buttons work

### 9. Responsive Testing
- [ ] Test on desktop (1920x1080)
- [ ] Test on tablet (768x1024)
- [ ] Test on mobile (375x667)
- [ ] Verify modal is scrollable
- [ ] Verify table is readable
- [ ] Verify buttons are clickable

### 10. Performance Testing
- [ ] Measure fetch time (should be < 5 seconds)
- [ ] Measure generate time (should be < 10 seconds with LLM)
- [ ] Verify caching works (second fetch should be instant)
- [ ] Check database query count
- [ ] Monitor memory usage
- [ ] Check for JavaScript errors in console

## Deployment Steps

### 1. Backup
- [ ] Backup WordPress database
- [ ] Backup WordPress files
- [ ] Document current plugin versions
- [ ] Create restore point

### 2. Deploy Files
- [ ] Upload AI-Stats files to production
- [ ] Verify file permissions (644 for files, 755 for directories)
- [ ] Clear any file caches (if using server-side caching)

### 3. Activate Plugin
- [ ] Activate AI-Stats plugin
- [ ] Check for activation errors
- [ ] Verify database tables created
- [ ] Check WordPress debug log

### 4. Configure Settings
- [ ] Import settings from staging (if applicable)
- [ ] Configure API keys
- [ ] Set preferred AI provider
- [ ] Save settings

### 5. Generate Test Content
- [ ] Generate module for each mode (6 modes)
- [ ] Verify all modules publish successfully
- [ ] Check database for stored modules
- [ ] Verify shortcode output on frontend

### 6. Monitor
- [ ] Monitor error logs for 24 hours
- [ ] Check token usage in AI-Core
- [ ] Monitor database size
- [ ] Check cache performance
- [ ] Review user feedback

## Post-Deployment

### 1. Documentation
- [ ] Update internal documentation
- [ ] Create user guide for team
- [ ] Document API key setup process
- [ ] Create troubleshooting guide

### 2. Training
- [ ] Train content team on manual workflow
- [ ] Demonstrate fetch & preview process
- [ ] Show LLM on/off toggle
- [ ] Explain candidate selection
- [ ] Review best practices

### 3. Monitoring
- [ ] Set up monitoring for:
  - [ ] Plugin errors
  - [ ] API failures
  - [ ] Token usage
  - [ ] Database growth
  - [ ] Cache hit rate
- [ ] Create alerts for critical issues

### 4. Optimization
- [ ] Review fetch performance
- [ ] Optimize slow data sources
- [ ] Adjust cache duration if needed
- [ ] Fine-tune prompts based on output quality
- [ ] Update source registry as needed

## Rollback Plan

If issues occur:

1. **Immediate Rollback**
   - [ ] Deactivate AI-Stats plugin
   - [ ] Restore database backup
   - [ ] Restore file backup
   - [ ] Clear all caches

2. **Partial Rollback**
   - [ ] Disable specific modes
   - [ ] Remove problematic data sources
   - [ ] Adjust settings
   - [ ] Re-test

3. **Debug and Fix**
   - [ ] Enable WordPress debug mode
   - [ ] Review error logs
   - [ ] Test in staging environment
   - [ ] Apply fixes
   - [ ] Re-deploy

## Success Criteria

- [ ] All 6 modes generate content successfully
- [ ] LLM ON and OFF both work
- [ ] No PHP errors in debug log
- [ ] No JavaScript errors in console
- [ ] Shortcode displays correctly on frontend
- [ ] Modal is responsive on all devices
- [ ] Fetch time < 5 seconds
- [ ] Generate time < 10 seconds
- [ ] Token usage is reasonable
- [ ] User feedback is positive

## Known Issues

Document any known issues here:

1. **Issue:** [Description]
   - **Severity:** Low/Medium/High
   - **Workaround:** [If available]
   - **Fix ETA:** [Date]

## Support Contacts

- **Developer:** [Name/Email]
- **AI-Core Support:** [Contact]
- **WordPress Admin:** [Contact]

## Sign-off

- [ ] Developer tested and approved
- [ ] QA tested and approved
- [ ] Product owner approved
- [ ] Deployment completed
- [ ] Monitoring in place

**Deployed by:** _______________  
**Date:** _______________  
**Time:** _______________

---

**Notes:**
- This checklist should be completed for every deployment
- Document any deviations or issues
- Keep a copy for audit trail

