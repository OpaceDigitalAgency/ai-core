# AI-Core: Universal AI Integration Hub for WordPress

> **Opace open-source portfolio:** [Browse Opace WordPress plugins, AI Agent Skills and web platforms](https://github.com/OpaceDigitalAgency/OpaceDigitalAgency)

AI-Core is a central WordPress plugin for configuring AI providers once and sharing those connections with compatible add-on plugins. It provides API-key management, provider and model discovery, request normalisation, usage statistics, cost tracking and a developer-facing integration layer.

## Supported providers

- OpenAI
- Anthropic Claude
- Google Gemini
- xAI Grok

## Core capabilities

- Centralised provider and API-key settings
- Text and image-generation integration
- Dynamic provider and model selection
- Shared response normalisation across providers
- Prompt library management
- Usage and estimated-cost statistics
- Add-on discovery and installation interface
- Public PHP API for compatible WordPress plugins

## Requirements

- WordPress 5.0 or later
- A supported provider account and API key
- WordPress administrator access for configuration

The plugin header currently reports version `0.7.7` and WordPress testing through `6.8.1`.

## Installation

1. Download or clone this repository.
2. Place the plugin directory in `wp-content/plugins/`.
3. Activate **AI-Core - Universal AI Integration Hub** in WordPress.
4. Open the AI-Core settings page and configure the providers you intend to use.
5. Test each configured provider before connecting an add-on.

Never commit API keys to this repository or expose them in client-side code.

## Developer documentation

- [Project master document](docs/PROJECT_MASTER.md)
- [Provider and model reference](docs/AI_PROVIDERS_MODELS.md)
- [Testing guide](docs/TESTING_GUIDE.md)
- [WordPress.org compliance report](docs/WORDPRESS_ORG_COMPLIANCE_REPORT.md)
- [Bundled add-ons](bundled-addons/README.md)

## Project status

The source contains newer plugin code than portions of the historical project documentation. The master document records that testing, real-provider integration checks and a WordPress.org compliance audit remain required. Treat this repository as development software and validate it in a staging WordPress installation before production use.

## Licence

The plugin header declares GPL v2 or later.

## Related projects

- [AI Scribe](https://github.com/OpaceDigitalAgency/ai-scribe-chat-gpt-content-creator)
- [Opace Agent Skills](https://github.com/OpaceDigitalAgency/skills)

Maintained by [Opace Digital Agency](https://opace.agency).
