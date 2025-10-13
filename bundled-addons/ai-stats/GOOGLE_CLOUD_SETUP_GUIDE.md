# Google Cloud BigQuery Setup Guide for AI-Stats

**Version:** 0.2.5  
**Date:** October 13, 2025  
**Purpose:** Enable Google Trends data via BigQuery for AI-Stats plugin

---

## Overview

AI-Stats can access Google Trends data through Google Cloud BigQuery's public dataset. This provides:
- **Top 25 trending searches** for any region (US, EU, GB, etc.)
- **Last 30 days of trend data**
- **Free tier coverage** for light usage (first 1TB queries/month free)
- **No web scraping required** - official Google data

---

## Prerequisites

- Google Account (Gmail)
- Credit card (required for Google Cloud, but free tier covers typical usage)
- WordPress admin access to AI-Stats settings

---

## Step-by-Step Setup

### 1. Create Google Cloud Project

1. Go to [Google Cloud Console](https://console.cloud.google.com/)
2. Sign in with your Google Account
3. Click **"Select a project"** dropdown at the top
4. Click **"New Project"**
5. Enter project name (e.g., "AI-Stats-BigQuery")
6. Click **"Create"**
7. **Copy your Project ID** (e.g., `gen-lang-client-0688797223`) - you'll need this later

### 2. Enable BigQuery API

1. In the Google Cloud Console, go to **APIs & Services > Library**
2. Search for **"BigQuery API"**
3. Click on **BigQuery API**
4. Click **"Enable"**
5. Wait for activation (usually instant)

### 3. Create Service Account

1. Go to **IAM & Admin > Service Accounts**
   - Direct link: https://console.cloud.google.com/iam-admin/serviceaccounts
2. Click **"Create Service Account"**
3. Enter details:
   - **Service account name:** `ai-stats-bigquery`
   - **Service account ID:** (auto-generated)
   - **Description:** "Service account for AI-Stats plugin to access BigQuery"
4. Click **"Create and Continue"**

### 4. Grant Permissions

1. In the **"Grant this service account access to project"** section:
   - Click **"Select a role"**
   - Search for and select **"BigQuery Data Viewer"**
   - Click **"Add Another Role"**
   - Search for and select **"BigQuery Job User"**
2. Click **"Continue"**
3. Skip the optional "Grant users access to this service account" section
4. Click **"Done"**

### 5. Create and Download JSON Key

1. Find your newly created service account in the list
2. Click on the service account email
3. Go to the **"Keys"** tab
4. Click **"Add Key" > "Create new key"**
5. Select **"JSON"** format
6. Click **"Create"**
7. The JSON key file will download automatically
8. **Keep this file secure** - it contains credentials

### 6. Configure AI-Stats Plugin

1. Open the downloaded JSON file in a text editor
2. Copy the **entire contents** of the file
3. Go to your WordPress admin: **AI-Stats > Settings**
4. Scroll to **"Google Cloud Integration"** section
5. Fill in the fields:
   - **Google Cloud Project ID:** Paste your Project ID from Step 1
   - **Service Account JSON:** Paste the entire JSON contents
   - **BigQuery Features:** Check ✅ "Enable Google Trends data via BigQuery"
   - **BigQuery Region:** Select **"United Kingdom (GB)"** for UK trends
6. Click **"Test BigQuery Connection"** button
7. You should see: ✅ **"Connection successful!"** with sample trend data
8. Click **"Save Changes"**

---

## Testing the Integration

### Test on Settings Page

1. After saving settings, click **"Test BigQuery Connection"**
2. Expected result:
   ```
   ✅ Connection successful!
   Retrieved 25 trending searches for GB
   Sample: "premier league"
   ```

### Test on Debug Page

1. Go to **AI-Stats > Debug & Diagnostics**
2. Click **"Data Sources"** tab
3. Click **"Test All Sources"**
4. Find **"BigQuery Google Trends"** in the list
5. Status should show: ✅ **Success** with count (e.g., "25 candidates")

### Test in Seasonal Mode

1. Go to **AI-Stats > Dashboard**
2. Select **"Seasonal Service Angle Rotator"** mode
3. Click **"Fetch & Preview"**
4. You should see trending searches from BigQuery in the candidates list

---

## Troubleshooting

### ❌ "Invalid JSON format"
- **Cause:** JSON was not copied correctly
- **Fix:** Re-download the JSON key file and copy the entire contents including `{` and `}`

### ❌ "Failed to get BigQuery access token"
- **Cause:** Service account permissions not set correctly
- **Fix:** Go back to Step 4 and ensure both roles are assigned:
  - BigQuery Data Viewer
  - BigQuery Job User

### ❌ "No data returned from BigQuery"
- **Cause:** Region might not have data or API not enabled
- **Fix:** 
  1. Verify BigQuery API is enabled (Step 2)
  2. Try changing region to "US" temporarily to test
  3. Check Google Cloud Console for any billing alerts

### ❌ "Permission denied"
- **Cause:** Service account doesn't have access to public dataset
- **Fix:** This is rare - the public dataset should be accessible to all. Try creating a new service account.

### ⚠️ "Connection timeout"
- **Cause:** Network or firewall issue
- **Fix:** 
  1. Check your server can access `bigquery.googleapis.com`
  2. Contact your hosting provider if blocked

---

## Cost Information

### Free Tier (Typical Usage)
- **First 1TB of queries per month:** FREE
- **AI-Stats typical usage:** ~10MB per month
- **Estimated cost:** $0.00/month

### If You Exceed Free Tier
- **Cost:** $5 per TB after first 1TB
- **To exceed free tier:** You'd need to run ~100,000 queries/month
- **AI-Stats usage:** Typically 30-100 queries/month

### Monitoring Usage
1. Go to [Google Cloud Console > Billing](https://console.cloud.google.com/billing)
2. View **"Reports"** to see BigQuery usage
3. Set up **Budget Alerts** if concerned

---

## Security Best Practices

### Protect Your JSON Key
- ✅ **DO:** Store in WordPress database (AI-Stats settings)
- ✅ **DO:** Keep the downloaded file in a secure location
- ❌ **DON'T:** Commit to Git/GitHub
- ❌ **DON'T:** Share publicly
- ❌ **DON'T:** Email unencrypted

### Rotate Keys Regularly
1. Create a new key (Step 5)
2. Update AI-Stats settings with new key
3. Delete old key from Google Cloud Console

### Limit Permissions
- Only grant **BigQuery Data Viewer** and **BigQuery Job User**
- Don't grant **Owner** or **Editor** roles

---

## What Data is Accessed?

### Public Dataset
- **Dataset:** `bigquery-public-data.google_trends`
- **Table:** `top_terms`
- **Data:** Top 25 trending search terms per region
- **Update frequency:** Daily
- **No personal data** - only aggregated search trends

### Sample Query
```sql
SELECT term, rank, refresh_date
FROM `bigquery-public-data.google_trends.top_terms`
WHERE refresh_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)
  AND country_code = 'GB'
  AND rank <= 25
ORDER BY refresh_date DESC, rank ASC
LIMIT 25
```

---

## Alternative: Google Trends RSS Feed

If you don't want to set up BigQuery, AI-Stats also supports:
- **Google Trends Daily RSS:** `https://trends.google.com/trends/trendingsearches/daily/rss?geo=GB`
- **Pros:** No setup required, free, instant
- **Cons:** Only today's trends, less data, less reliable

BigQuery is recommended for:
- ✅ More reliable data
- ✅ Historical trends (last 30 days)
- ✅ Better integration with AI-Stats
- ✅ No rate limits

---

## Support

### Need Help?
1. Check the **Debug Page** for detailed error messages
2. Enable **WP_DEBUG** in `wp-config.php` to see detailed logs
3. Check `wp-content/debug.log` for BigQuery-specific errors

### Common Log Messages
```
AI-Stats: BigQuery credentials not configured
→ Fill in Project ID and Service Account JSON

AI-Stats: Invalid BigQuery credentials JSON
→ Check JSON format is valid

AI-Stats: Failed to get BigQuery access token
→ Check service account permissions

AI-Stats: Fetched 25 Google Trends from BigQuery for GB
→ Success! Everything working correctly
```

---

## Summary Checklist

- [ ] Created Google Cloud Project
- [ ] Enabled BigQuery API
- [ ] Created Service Account with correct permissions
- [ ] Downloaded JSON key file
- [ ] Configured AI-Stats settings
- [ ] Tested connection successfully
- [ ] Verified data appears in Debug page
- [ ] Tested in Seasonal mode

**Estimated setup time:** 10-15 minutes

---

**Last updated:** October 13, 2025  
**Plugin version:** 0.2.5  
**Tested with:** WordPress 6.8.1, PHP 7.4+

