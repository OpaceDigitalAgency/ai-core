# AI-Pulse Repository Structure

This document explains how AI-Pulse is split across two repositories.

---

## Overview

AI-Pulse exists in **two separate versions** for different use cases:

| Version | Repository | Purpose | Deployment |
|---------|------------|---------|------------|
| **WordPress Plugin** | [ai-core](https://github.com/OpaceDigitalAgency/ai-core) | Production SEO tool | WordPress site |
| **React App** | [TrendPulse](https://github.com/OpaceDigitalAgency/TrendPulse) | Standalone demo/testing | Netlify |

---

## Repository 1: ai-core (WordPress Plugin)

**URL:** https://github.com/OpaceDigitalAgency/ai-core

### Structure
```
ai-core-standalone/
├── admin/
│   └── class-ai-core-addons.php          ← Registers AI-Pulse as bundled add-on
├── bundled-addons/
│   ├── ai-imagen/                        ← Other bundled add-on
│   ├── ai-stats/                         ← Other bundled add-on
│   └── wp-ai-pulse/                      ← AI-Pulse WordPress Plugin
│       ├── ai-pulse.php                  ← Main plugin file
│       ├── readme.txt                    ← WordPress.org readme
│       ├── uninstall.php                 ← Cleanup on deletion
│       ├── metadata.json                 ← Add-on metadata
│       ├── includes/                     ← Core classes (8 files)
│       │   ├── class-ai-pulse-database.php
│       │   ├── class-ai-pulse-generator.php
│       │   ├── class-ai-pulse-logger.php
│       │   ├── class-ai-pulse-modes.php
│       │   ├── class-ai-pulse-scheduler.php
│       │   ├── class-ai-pulse-settings.php
│       │   ├── class-ai-pulse-shortcode.php
│       │   └── class-ai-pulse-validator.php
│       ├── admin/                        ← Admin interface
│       │   ├── class-ai-pulse-admin.php
│       │   ├── class-ai-pulse-ajax.php
│       │   └── views/                    ← Admin tabs (7 files)
│       ├── assets/                       ← CSS/JS
│       │   ├── css/
│       │   └── js/
│       ├── docs/                         ← Documentation (*.md files)
│       │   ├── AI_PULSE_IMPLEMENTATION_PLAN.md
│       │   ├── DEPLOYMENT_GUIDE.md
│       │   ├── REPOSITORY_STRUCTURE.md (this file)
│       │   └── [other .md files]
│       └── deploy-react-to-trendpulse.sh ← Helper script
└── .gitignore                            ← Excludes react-app/
```

### What's Included
- ✅ WordPress plugin files
- ✅ Documentation
- ✅ Deployment helper script
- ❌ React app (excluded via .gitignore)

### Installation
1. Clone the ai-core repository
2. Install AI-Core plugin in WordPress
3. Go to AI-Core → Add-ons
4. Install and activate AI-Pulse

---

## Repository 2: TrendPulse (React App)

**URL:** https://github.com/OpaceDigitalAgency/TrendPulse

### Structure
```
TrendPulse/
├── src/
│   ├── components/                       ← React components
│   │   ├── CostWidget.tsx
│   │   ├── FaqResults.tsx
│   │   ├── MegaDashboard.tsx
│   │   ├── StatsResults.tsx
│   │   ├── StrategicResults.tsx
│   │   └── TrendResults.tsx
│   ├── services/                         ← API services
│   │   ├── geminiService.ts              ← Google Gemini integration
│   │   └── mockBigQueryService.ts
│   ├── App.tsx                           ← Main app component
│   └── types.ts                          ← TypeScript types
├── index.html                            ← HTML entry point
├── index.tsx                             ← React entry point
├── package.json                          ← Dependencies
├── tsconfig.json                         ← TypeScript config
├── vite.config.ts                        ← Vite config
├── netlify.toml                          ← Netlify deployment config
├── .env.example                          ← Environment variables template
├── .gitignore                            ← Git ignore rules
└── README.md                             ← React app documentation
```

### What's Included
- ✅ React app source code
- ✅ Netlify configuration
- ✅ Build configuration
- ✅ Environment variable template
- ❌ WordPress plugin files

### Deployment
1. Push to GitHub (TrendPulse repo)
2. Connect to Netlify
3. Set environment variable: `VITE_GOOGLE_API_KEY`
4. Auto-deploys on every push to main

---

## Key Differences

| Feature | WordPress Plugin | React App |
|---------|------------------|-----------|
| **API Key Storage** | AI-Core settings (database) | Environment variable |
| **Scheduling** | WP Cron (automated) | Manual only |
| **Database** | MySQL tables | None (ephemeral) |
| **SEO Output** | Crawlable HTML | Client-side only |
| **Shortcodes** | `[ai_pulse]` | N/A |
| **Installation** | Via AI-Core add-ons | Netlify deployment |
| **Updates** | Plugin updates | Git push (auto-deploy) |

---

## Workflow

### Updating WordPress Plugin

```bash
# In ai-core-standalone directory
cd /path/to/ai-core-standalone

# Make changes to WordPress plugin
vim bundled-addons/wp-ai-pulse/includes/class-ai-pulse-generator.php

# Commit and push
git add bundled-addons/wp-ai-pulse/
git commit -m "Update AI-Pulse: [description]"
git push origin main
```

### Updating React App

```bash
# Run the deployment script
cd /path/to/ai-core-standalone
./bundled-addons/wp-ai-pulse/deploy-react-to-trendpulse.sh

# Or manually:
cd ~/temp-trendpulse
# Make changes
git add .
git commit -m "Update: [description]"
git push origin main
# Netlify auto-deploys
```

---

## .gitignore Rules

### ai-core Repository
```gitignore
# Exclude React app from WordPress plugin
bundled-addons/wp-ai-pulse/react-app/
```

### TrendPulse Repository
```gitignore
node_modules/
dist/
.env
.env.local
.netlify/
```

---

## Quick Reference

| Task | Command |
|------|---------|
| Deploy React app to TrendPulse | `./bundled-addons/wp-ai-pulse/deploy-react-to-trendpulse.sh` |
| Test React app locally | `cd ~/temp-trendpulse && npm run dev` |
| Build React app | `cd ~/temp-trendpulse && npm run build` |
| Update WordPress plugin | `git add bundled-addons/wp-ai-pulse/ && git commit && git push` |

---

## Summary

- **WordPress Plugin** = Production-ready SEO tool in `ai-core` repo
- **React App** = Standalone demo/testing tool in `TrendPulse` repo
- **No conflicts** = Both can be maintained independently
- **Shared logic** = Can be synced manually when needed

