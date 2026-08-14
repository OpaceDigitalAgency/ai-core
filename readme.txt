=== AI Core Integration Hub Prompt Engine ===
Contributors: opacewebdesign
Tags: artificial intelligence, openai, claude, gemini, automation
Requires at least: 6.5
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Manage OpenAI, Anthropic and Gemini credentials once, then share models, prompts and usage data across compatible WordPress plugins.

== Description ==

**Compatibility:** Version 1.0.0 was tested on WordPress 7.0.4 and PHP 8.3. Minimums: WordPress 6.5 and PHP 7.4.

AI-Core gives compatible WordPress plugins one shared connection to AI providers. Add an OpenAI,
Anthropic Claude or Google Gemini key once, test it, and let compatible plugins use the same saved
configuration.

AI-Core is an integration hub, not a content generator. A plugin such as AI-Scribe sends generation
requests through it.

= Why AI-Core was built =

As separate WordPress plugins added AI features, each one risked duplicating provider settings, model
lists, request formats, prompt storage and cost calculations. AI-Core moves that security-sensitive
infrastructure into one maintained plugin. Site administrators get one place to rotate keys, choose
models and review usage, while compatible plugins stay focused on their own workflows.

= How AI-Scribe uses AI-Core =

AI-Scribe owns article planning, writing, SEO metadata, editorial review and WordPress publishing.
AI-Core supplies its encrypted credentials, provider and model selection, normalised generation
requests, reusable prompts, model capabilities and usage records.

AI-Scribe project: https://github.com/OpaceDigitalAgency/ai-scribe-chat-gpt-content-creator

= What AI-Core provides =

* One settings screen for supported provider credentials
* Live model discovery based on the models available to your provider account
* Provider-aware handling for text, images and structured output
* A grouped prompt library with import and export
* Request, token and published-rate cost estimates by provider and model
* A PHP API for compatible WordPress plugins
* Encrypted credential storage and an explicit data-retention choice on uninstall

= Supported providers =

* **OpenAI** - text and image generation through the Chat Completions, Responses and Images APIs
* **Anthropic Claude** - text generation through the Messages API; Anthropic does not provide image generation
* **Google Gemini** - text and image generation through the Gemini API

Model lists are fetched using your key. Providers may make different models available to different
accounts, so two sites can show different choices.

= Dynamic model defaults =

A valid saved choice is always preserved. If it is missing or retired, AI-Core ranks the configured account's live list within the intended family: newest Terra with medium reasoning for OpenAI writing; newest Claude Opus with medium effort for Anthropic writing; newest non-Lite Gemini Flash with medium thinking for Gemini writing; newest GPT Image for OpenAI images; and newest Gemini Flash Image / Nano Banana for Gemini images.

The maintained offline fallbacks are currently `gpt-5.6-terra`, `claude-opus-5`, `gemini-3.6-flash`, `gpt-image-2` and `gemini-3.1-flash-image` (Nano Banana 2). Live discovery takes precedence, so a later model in the same preferred family can become the default without a plugin update. AI-Core never silently replaces an explicit valid choice made by an administrator.

= Compatible plugins =

* **AI-Scribe 3.0 or later** - AI content creation and SEO optimisation

Developers can also use AI-Core's PHP API in their own plugins.

= What comes next =

AI-Imagen is a planned image-generation workflow intended to use AI-Core. It is not included in this
release and has no announced release date. Other compatible plugins can use the same PHP API as the
project grows.

= Security and privacy =

Provider keys are stored in the `ai_core_settings` option on your WordPress site. They are encrypted
at rest with AES-256-CBC, using a random initialisation vector per value and a key derived from the
site's WordPress salts. If encryption fails, AI-Core does not save the key as plain text.

Rotating the salts in `wp-config.php` makes saved keys unreadable. Re-enter them after a salt
rotation.

AI-Core does not include analytics or user tracking. Generation data is sent only when an
administrator tests a provider or a compatible plugin requests a generation.

== External services ==

AI-Core connects to the following third-party services. Each provider bills you directly under the
terms of your account.

**OpenAI**

Used when an administrator tests an OpenAI key or refreshes its model list, and when OpenAI is chosen
for a generation. AI-Core sends the OpenAI API key, requested model, request settings and prompt or
image instructions supplied by the calling plugin. Requests go to `api.openai.com`.

Terms: https://openai.com/policies/terms-of-use
Privacy: https://openai.com/policies/privacy-policy

**Anthropic Claude**

Used when an administrator tests an Anthropic key or refreshes its model list, and when Anthropic is
chosen for a generation. AI-Core sends the Anthropic API key, requested model, request settings and
prompt supplied by the calling plugin. Requests go to `api.anthropic.com`.

Terms: https://www.anthropic.com/legal/consumer-terms
Privacy: https://www.anthropic.com/legal/privacy

**Google Gemini**

Used when an administrator tests a Gemini key or refreshes its model list, and when Gemini is chosen
for a generation. AI-Core sends the Gemini API key, requested model, request settings and prompt or
image instructions supplied by the calling plugin. Requests go to
`generativelanguage.googleapis.com`.

Terms: https://ai.google.dev/gemini-api/terms
Privacy: https://policies.google.com/privacy

**LiteLLM public model catalogue**

Used when AI-Core needs current published pricing for a discovered model and when an administrator
selects Refresh Model Pricing. AI-Core sends only the provider name and model identifier to
`api.litellm.ai`. API keys, prompts, generated content, site details and usage totals are not sent.
Successful results are cached for 12 hours. AI-Core uses its bundled catalogue when no current result
is available and shows Cost unavailable rather than treating an unknown price as zero.

Catalogue and licence: https://github.com/BerriAI/litellm
Privacy: https://www.litellm.ai/privacy

Cost figures shown by AI-Core are published-rate estimates. Free tiers, cached-token rates, batch
pricing, negotiated rates and provider billing changes can make an invoice differ from the estimate.

== Installation ==

1. Upload the `ai-core-integration-hub-prompt-engine` ZIP through Plugins > Add New Plugin > Upload Plugin, or install it from the WordPress.org Plugin Directory after approval.
2. Activate AI-Core.
3. Open AI-Core > Settings.
4. Enter a key for at least one provider and select Test Key.
5. Select a default provider and save.

Compatible plugins can then use the shared configuration.

== Frequently Asked Questions ==

= Does AI-Core include an API key or free AI usage? =

No. Obtain a key directly from OpenAI, Anthropic or Google. The provider charges your account for
usage under its own pricing and terms.

= Does AI-Core generate content by itself? =

No. It manages provider connections, models, prompts and usage data. Install a compatible plugin such
as AI-Scribe to generate content.

= Can I use more than one provider? =

Yes. You can save keys for multiple supported providers. A compatible plugin can use one provider for
text and another for images.

= Why is a model missing? =

AI-Core asks each provider which models your key can access. Availability can differ by account,
region and provider rollout.

= Are API keys visible to browser visitors? =

No. Keys are stored server-side, encrypted at rest, and are not printed into public pages or normal
AI-Core responses. Other trusted server-side WordPress plugins can use AI-Core's PHP API.

= What happens when I uninstall AI-Core? =

AI-Core keeps its data by default because other plugins may depend on it. To remove all AI-Core-owned
credentials, settings, prompt tables, statistics and caches, turn off Persist Settings on Uninstall
before deleting the plugin. AI-Core does not delete another plugin's data.

== Screenshots ==

1. Settings - test provider credentials, refresh models, choose defaults and control retained data.
2. Dashboard - review provider status, usage totals and the main AI-Core tools.
3. Prompt Library - group, import, export and run reusable text or image prompts.
4. Statistics - inspect requests, tokens, errors and estimated costs by provider, tool and model.
5. Add-ons - see AI-Scribe status and the labelled roadmap for AI-Imagen, AI-Stats and AI-Pulse.

== Opace and related links ==

* AI-Scribe: https://github.com/OpaceDigitalAgency/ai-scribe-chat-gpt-content-creator
* AI-Core source and full changelog: https://github.com/OpaceDigitalAgency/ai-core-integration-hub-prompt-engine-wordpress-plugin
* Opace Digital Agency: https://opace.agency/
* Web design and development: https://opace.agency/services/web-design/
* WordPress development: https://opace.agency/services/web-design/wordpress-development/
* AI SEO services: https://opace.agency/services/ai-seo/

== Changelog ==

= 1.0.0 =

* First public release.
* Added shared credential management for OpenAI, Anthropic Claude and Google Gemini.
* Added live model discovery, provider-aware text and image requests, and structured-output handling.
* Added the prompt library and PHP integration API for compatible plugins.
* Added encrypted credential storage, usage statistics and published-rate cost estimates.
* Added explicit retain-or-remove behaviour for AI-Core data on uninstall.
* Added provider-family-aware dynamic defaults while preserving valid saved model choices.
* Tested the release package on WordPress 7.0.4 with PHP 8.3.
* Consolidated all completed pre-release work as the first public 1.0 release.

== Upgrade Notice ==

= 1.0.0 =

First public release with shared provider connections, dynamic model discovery, prompts, structured output and usage records.
