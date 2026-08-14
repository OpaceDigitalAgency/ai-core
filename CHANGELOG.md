# AI-Core changelog

## 1.0.0 — 14 August 2026

First public release. It consolidates all completed provider, model, request, prompt-library,
statistics, pricing, security and data-retention work described below.

The release package was tested on WordPress 7.0.4 with PHP 8.3.

### Provider connections and models

- centralised encrypted credentials for OpenAI, Anthropic Claude and Google Gemini
- added connection tests and live model discovery for the models available to each provider account
- ranked model families by capability and recency while avoiding niche-model defaults
- added provider-aware text and image defaults when a key is saved
- preserved valid saved text and image choices
- selected missing or retired defaults from each provider's current live model list
- preferred current Terra, Claude Opus and non-Lite Gemini Flash families for writing
- defaulted supported writing models to medium reasoning, effort or thinking
- preferred current GPT Image and Gemini Flash Image / Nano Banana families for images
- updated Gemini discovery for current text and image model families
- returned a clear provider-specific error when an API key is rejected

### Requests and structured output

- supported OpenAI Chat Completions, Responses and Images request shapes
- translated OpenAI structured-output settings to the Responses API `text.format` shape
- supported Anthropic tools and forced tool choice, including extraction of tool-use output
- supported Gemini response schemas and nested generation configuration
- normalised response extraction and error handling across providers
- limited image-only parameters to compatible model families

### WordPress administration

- added dashboard, settings, prompt library, statistics and add-ons screens
- added grouped prompt import and export
- added current provider and model status, explicit refresh actions and visible success or error results
- added shared light and dark presentation with accessible contrast improvements
- prevented an add-on Install action when no local or public installation source exists
- removed the dormant local add-on file copier; roadmap add-ons are presented as separate projects only
- replaced placeholder add-on claims with current roadmap information

### Usage, pricing and data ownership

- recorded requests, tokens, tools, errors and published outcomes
- added published-rate cost estimates using current LiteLLM catalogue data with a bundled fallback
- displayed `Cost unavailable` instead of treating an unknown price as zero
- cached successful pricing lookups for 12 hours and exposed a manual refresh action
- added a retain-or-remove uninstall setting, keeping shared data by default
- limited clean uninstall to AI-Core-owned options, tables, transients, cron events and capabilities

### Security, quality and packaging

- encrypted provider credentials at rest and made encryption fail closed
- gated diagnostic logs behind `WP_DEBUG`
- escaped exception messages and hardened database queries
- unslashed and sanitised prompt-library AJAX input at the authenticated request boundary
- added direct-access protection to uninstall handling
- aligned the plugin header, runtime constant and WordPress.org stable tag
- added a deterministic build script for an `opace-ai-core-integration-hub-prompt-engine`-rooted WordPress ZIP
- removed the dormant, untested xAI/Grok implementation so shipped code matches the three documented providers
- reached zero Plugin Check errors in the packaged pre-release baseline

## Pre-public version mapping

- `0.8.0` — dynamic model pricing, fallback behaviour and complete uninstall retention
- `0.7.9` — pricing and retention preparation
- `0.7.8` — WordPress compatibility, current model families and administration refinements
- `0.7.7` — source fixes and user-interface improvements
- `0.7.6` — Gemini preview-model endpoint support
- `0.7.5` — dynamic provider model discovery
- `0.7.3` — OpenAI Responses API format correction and response extraction fixes
- `0.6.5` — statistics accuracy and presentation improvements
- `0.6.0` — provider abstraction refactor
- `0.5.0` — grouped prompt library with import and export
- `0.2.9` — per-provider usage statistics and prompt model selection
- `0.1.0` — initial OpenAI, Anthropic and Gemini key management

These numbers were development candidates. Version 1.0.0 is the first release intended for public
GitHub distribution and WordPress.org review.
