# AI-Pulse Deployment Guide

This document explains how to deploy the two separate versions of AI-Pulse to their respective repositories.

## Repository Structure

### 1. WordPress Plugin → `ai-core` Repository
**Repository:** https://github.com/OpaceDigitalAgency/ai-core  
**Location:** `bundled-addons/wp-ai-pulse/`  
**Excludes:** `react-app/` folder

The WordPress plugin stays in the main AI-Core repository as a bundled add-on.

### 2. React App → `TrendPulse` Repository
**Repository:** https://github.com/OpaceDigitalAgency/TrendPulse  
**Location:** Root of repository  
**Source:** `bundled-addons/wp-ai-pulse/react-app/`

The React app gets its own dedicated repository for Netlify deployment.

---

## Deployment Steps

### Step 1: Prepare WordPress Plugin for ai-core Repo

The WordPress plugin is already in the correct location. Just ensure `react-app/` is excluded from commits:

```bash
# Navigate to ai-core repo root
cd /path/to/ai-core-standalone

# Add react-app to .gitignore (if not already there)
echo "bundled-addons/wp-ai-pulse/react-app/" >> .gitignore

# Stage and commit WordPress plugin files
git add bundled-addons/wp-ai-pulse/
git add admin/class-ai-core-addons.php
git commit -m "Add AI-Pulse WordPress plugin as bundled add-on"

# Push to ai-core repository
git push origin main
```

### Step 2: Create TrendPulse Repository

1. **Create new repository on GitHub:**
   - Go to https://github.com/OpaceDigitalAgency
   - Click "New repository"
   - Name: `TrendPulse`
   - Description: "AI-Pulse React App - Trend Analysis Dashboard with Google Gemini"
   - Visibility: Public or Private (your choice)
   - **Do NOT** initialize with README (we'll push our own)

2. **Copy React app to TrendPulse repo:**

```bash
# Create a temporary directory for TrendPulse
mkdir -p ~/temp-trendpulse
cd ~/temp-trendpulse

# Initialize git
git init
git branch -M main

# Copy React app files
cp -r /path/to/ai-core-standalone/bundled-addons/wp-ai-pulse/react-app/* .
cp -r /path/to/ai-core-standalone/bundled-addons/wp-ai-pulse/react-app/.* . 2>/dev/null || true

# Verify structure
ls -la

# Add remote
git remote add origin https://github.com/OpaceDigitalAgency/TrendPulse.git

# Stage all files
git add .

# Commit
git commit -m "Initial commit: AI-Pulse React app for Netlify deployment"

# Push to GitHub
git push -u origin main
```

### Step 3: Deploy React App to Netlify

1. **Login to Netlify:**
   - Go to https://app.netlify.com
   - Click "Add new site" → "Import an existing project"

2. **Connect GitHub:**
   - Select "GitHub"
   - Authorize Netlify
   - Choose `OpaceDigitalAgency/TrendPulse`

3. **Configure build settings:**
   - **Base directory:** (leave empty - root)
   - **Build command:** `npm run build`
   - **Publish directory:** `dist`
   - **Node version:** 18

4. **Add environment variable:**
   - Go to Site settings → Environment variables
   - Click "Add a variable"
   - Key: `VITE_GOOGLE_API_KEY`
   - Value: Your Google Gemini API key
   - Click "Save"

5. **Deploy:**
   - Click "Deploy site"
   - Wait for build to complete
   - Your app will be live at `https://[random-name].netlify.app`

6. **Optional - Custom domain:**
   - Go to Site settings → Domain management
   - Add custom domain (e.g., `trendpulse.opace.agency`)

---

## Maintaining Both Versions

### WordPress Plugin Updates

```bash
cd /path/to/ai-core-standalone

# Make changes to WordPress plugin
# Edit files in bundled-addons/wp-ai-pulse/

# Commit and push
git add bundled-addons/wp-ai-pulse/
git commit -m "Update AI-Pulse plugin: [description]"
git push origin main
```

### React App Updates

```bash
cd ~/temp-trendpulse  # Or wherever you cloned TrendPulse

# Make changes to React app
# Edit files in src/

# Test locally
npm run dev

# Build and test production
npm run build
npm run preview

# Commit and push
git add .
git commit -m "Update: [description]"
git push origin main

# Netlify will auto-deploy on push
```

### Syncing Changes Between Versions

If you make changes to shared logic (e.g., prompts, analysis modes):

1. **Update WordPress plugin first** (in ai-core repo)
2. **Copy changes to React app:**
   ```bash
   # Example: Sync geminiService logic
   cp /path/to/ai-core-standalone/bundled-addons/wp-ai-pulse/react-app/src/services/geminiService.ts \
      ~/temp-trendpulse/src/services/geminiService.ts
   ```
3. **Commit and push React app changes**

---

## .gitignore Configuration

### ai-core Repository

Add to `.gitignore`:
```
# Exclude React app from WordPress plugin
bundled-addons/wp-ai-pulse/react-app/
bundled-addons/wp-ai-pulse/docs/
```

### TrendPulse Repository

Already has `.gitignore` in `react-app/.gitignore`:
```
node_modules/
dist/
.env
.env.local
.netlify/
```

---

## Quick Reference

| Task | Repository | Command |
|------|------------|---------|
| Update WordPress plugin | ai-core | `git push origin main` |
| Update React app | TrendPulse | `git push origin main` (auto-deploys) |
| Test React locally | TrendPulse | `npm run dev` |
| Build React for production | TrendPulse | `npm run build` |

---

## Troubleshooting

### React app won't build on Netlify

1. Check Node version in `netlify.toml` (should be 18+)
2. Verify `VITE_GOOGLE_API_KEY` is set in Netlify environment variables
3. Check build logs for missing dependencies

### WordPress plugin won't install

1. Verify folder structure: `bundled-addons/wp-ai-pulse/ai-pulse.php`
2. Check `metadata.json` exists
3. Ensure AI-Core is updated with latest `class-ai-core-addons.php`

### Changes not appearing

- **WordPress:** Clear WordPress cache and plugin cache
- **React/Netlify:** Trigger manual deploy or check auto-deploy settings

---

## Next Steps

1. ✅ WordPress plugin is ready in `ai-core` repo
2. ⏳ Create TrendPulse repository and push React app
3. ⏳ Deploy to Netlify
4. ⏳ Test both versions independently

