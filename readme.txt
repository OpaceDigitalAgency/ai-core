=== AI-Core - Universal AI Integration Hub ===
Contributors: opacewebdesign
Tags: ai, api, integration, automation, content
Requires at least: 6.5
Tested up to: 7.0.4
Requires PHP: 7.4
Stable tag: 0.7.9
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Centralised AI integration hub. Manage OpenAI, Anthropic and Gemini keys in one place and share them across every AI plugin on your site.

== Description ==

AI-Core holds your AI provider credentials once, so every AI plugin on the site can use them without
asking you to paste the same key again. Configure OpenAI, Anthropic Claude or Google Gemini
on the Settings screen, and compatible plugins pick them up automatically.

It is a hub, not a content tool. On its own it gives you key management, a prompt library, model
discovery and usage statistics. The generating is done by the plugins that sit on top of it.

= How it fits together =

`
┌──────────────┐  ┌──────────────┐  ┌──────────────┐
│  AI Scribe   │  │  AI Imagen   │  │  your plugin │
│  (articles)  │  │  (images)    │  │              │
└──────┬───────┘  └──────┬───────┘  └──────┬───────┘
       │                 │                 │
       └────────────┬────┴─────────────────┘
                    │  one shared configuration
┌───────────────────▼────────────────────────────────┐
│  AI-Core                                           │
│                                                    │
│   API keys, encrypted at rest (AES-256-CBC)        │
│   Live model discovery per provider account        │
│   Prompt library with groups, import and export    │
│   Request builders  ·  Response normaliser         │
│   Usage, token and estimated-cost statistics       │
└──────┬───────────────┬────────────────┬────────────┘
       │               │                │
       ▼               ▼                ▼
    OpenAI         Anthropic          Gemini
 text + images     text only       text + images
`

Because the provider layer lives here, a fix or a new model reaches every plugin at once instead of
being repeated in each of them.

= What it does =

* Stores API keys for OpenAI, Anthropic Claude and Google Gemini in one place
* Discovers the models each provider actually offers, by querying the provider, rather than shipping
  a hardcoded list that goes stale
* Tests a key before you rely on it, and tells you plainly when a key is rejected
* Tracks requests, tokens and estimated cost per provider
* Provides a prompt library with groups, import and export
* Exposes a documented PHP API so other plugins can send requests through the same configuration

= Providers and capabilities =

Each provider is reached through its own documented contract rather than a single lowest-common-denominator
request, so structured responses, reasoning controls and image parameters all behave correctly.

* **OpenAI** — text and images. Both the Chat Completions and Responses APIs, with JSON-schema
  structured output and the correct token parameter per model.
* **Anthropic (Claude)** — text only. Anthropic offers no image model, so a Claude-only site has no
  image generation; response schemas are enforced through a tool call, which is Anthropic's mechanism
  for the job.
* **Google Gemini** — text and images, through the v1beta endpoint so preview models are reachable.

Plugins built on AI-Core can therefore let a site mix providers: write with Claude and generate images
with OpenAI or Gemini, or use a single provider for everything.

= Compatible plugins =

* AI-Scribe - AI content creation and SEO optimisation. AI-Scribe 3.0 and later requires AI-Core.

= Who it is for =

Anyone running more than one AI plugin, or building one. If you only use a single plugin that manages
its own key, you do not need this.

== External services ==

AI-Core connects to third-party AI provider APIs so that the plugins built on it can generate text and
images. No request is made unless you have entered a key for that provider and something on your site
asks for a generation. AI-Core makes no calls of its own accord, on a schedule, or in the background.

**OpenAI**
Used when OpenAI is your selected provider, when you test an OpenAI key, and when you refresh the
model list. Sent: your API key, the prompt text supplied by you or by the plugin making the request,
and the model and parameters chosen for that request. Requests go to api.openai.com.
Terms: https://openai.com/policies/terms-of-use
Privacy: https://openai.com/policies/privacy-policy

**Anthropic (Claude)**
Used when Anthropic is your selected provider, when you test an Anthropic key, and when you refresh
the model list. Sent: your API key, the prompt text, and the model and parameters for that request.
Requests go to api.anthropic.com.
Terms: https://www.anthropic.com/legal/consumer-terms
Privacy: https://www.anthropic.com/legal/privacy

**Google Gemini**
Used when Gemini is your selected provider, when you test a Gemini key, and when you refresh the model
list. Sent: your API key, the prompt text, and the model and parameters for that request. Requests go
to generativelanguage.googleapis.com.
Terms: https://ai.google.dev/gemini-api/terms
Privacy: https://policies.google.com/privacy

Each provider bills you directly for the requests AI-Core makes on your behalf. You are responsible
for those charges. AI-Core's Statistics screen shows an estimate based on published per-token pricing,
which will not always match your provider invoice exactly.

== Installation ==

1. Upload the `ai-core` folder to `/wp-content/plugins/`, or install through Plugins > Add New.
2. Activate the plugin.
3. Go to AI-Core > Settings and enter a key for at least one provider.
4. Press Test next to the key to confirm it works before relying on it.
5. Choose a default provider.

Plugins that depend on AI-Core will then find the configuration on their own.

== Frequently Asked Questions ==

= Do I need an API key? =

Yes, at least one. AI-Core does not supply credentials and has no free tier of its own. You obtain a
key directly from OpenAI, Anthropic or Google, and pay that provider for what you use.

= Does AI-Core generate content by itself? =

No. It manages configuration and provides the connection. Install a plugin such as AI-Scribe to
generate anything.

= Can I mix providers? =

Yes. Add as many keys as you like. A plugin built on AI-Core can use one provider for text and another
for images — writing with Claude and generating images with OpenAI or Gemini is a common arrangement,
because Anthropic has no image model.

= Where are my API keys stored? =

In the WordPress options table, in the `ai_core_settings` option, on your own server. They are never
sent anywhere except to the provider they belong to.

Keys are encrypted at rest with AES-256-CBC, using a random initialisation vector per value and a key
derived from your site's WordPress salts. If the encryption cannot be performed the write fails
rather than falling back to storing the key in the clear. Decryption is transparent, so plugins
reading the setting get a usable key without handling any of this themselves.

Because the encryption key comes from your salts, rotating the salts in `wp-config.php` makes stored
keys unreadable and you will need to enter them again.

= Why does my model list look different from someone else's? =

Because the list is fetched from the provider using your key, and providers grant different models to
different accounts. If a model you expect is missing, it is usually not enabled on your account.

= What happens to my data if I uninstall? =

By default your keys, settings, statistics and prompt library are **kept**. AI-Core holds the
credentials other plugins rely on, so deleting it while AI-Scribe is still installed would otherwise
take that plugin's provider configuration with it.

If you want a clean removal, turn off "Persist Settings on Uninstall" in Settings *before* you delete
the plugin. AI-Core then removes its options, its two database tables and its transients when it is
deleted. It never touches data belonging to another plugin.

== Screenshots ==

1. Settings - API keys for each provider, with per-key testing
2. Dashboard - configured providers and recent usage at a glance
3. Prompt Library - prompts organised into groups
4. Statistics - requests, tokens and estimated cost per provider

== Changelog ==

= 0.7.9 =
* Fixed: development mock model lists and live provider model lists now use separate versioned caches.
* Added: Gemini live discovery, defaults and image routing recognise Gemini Image, Imagen 4 and Nano Banana model families.

= 0.7.8 =
* Tested against WordPress 7.0.4.
* Fixed: usage statistics now record generations made by add-ons, and failed requests count towards the error total.
* Fixed: Gemini model lists are ordered newest-first and default to the current mainline model rather than a niche research model.
* Improved: add-on descriptions name the current model families; Refresh Models shows progress; admin screens tidied with a dark mode toggle.

= 0.7.7 =
* Tested against WordPress 7.0.3.
* Fixed: Gemini requests dropped the caller's generationConfig, which is where the response schema lives. Every structured Gemini request therefore came back as prose and could not be decoded by the plugin that asked for it.
* Fixed: structured-output requests to OpenAI had their response format stripped before sending, so a
  schema-enforced request came back as free prose and the calling plugin then failed to decode it.
  This applied to both the Chat Completions and the Responses endpoints.
* Fixed: Anthropic requests never forwarded tool definitions. Because a forced tool call is how Claude
  enforces a response schema, every structured Claude request silently lost its schema.
* Fixed: when Claude did return a tool call, the response normaliser discarded it and returned an
  empty string.
* Fixed: image requests sent quality and response-format parameters that only some OpenAI models
  accept, so generation failed outright on the newer image models. Parameters are now sent per model.
* Prompt library: improved handling of prompt sources and capitalisation.
* Interface refinements across the admin screens.

= 0.7.6 =
* Gemini requests now use the v1beta endpoint, so preview models are reachable

= 0.7.5 =
* Model discovery is now fully dynamic - models are fetched from each provider's API rather than read
  from a hardcoded list. The bundled registry is retained only for pricing and capability metadata.

= 0.7.3 =
* Corrected the OpenAI Responses API `text.format` parameter
* Fixed response extraction so it is consistent across every provider

= 0.6.5 =
* Statistics screen accuracy and presentation improvements

= 0.6.0 =
* Reworked provider abstraction to make adding a provider a smaller change

= 0.5.0 =
* Prompt library: groups, import and export

= 0.2.9 =
* Usage statistics with per-provider breakdown
* Prompt library model selection

= 0.1.0 =
* First release - key management for OpenAI, Anthropic and Gemini

== Upgrade Notice ==

= 0.7.9 =
Gemini image discovery now refreshes correctly after leaving development mock mode and recognises the image families exposed by the account.

= 0.7.8 =
Usage statistics now record add-on activity and failed requests, and Gemini model lists are ordered
newest-first. Required by AI-Scribe 3.1.0.

= 0.7.7 =
Fixes structured output on OpenAI and Anthropic, and image generation on the newer OpenAI image
models. Recommended for anyone running AI-Scribe 3.0 or later.

= 0.7.5 =
Model lists are now fetched from your provider instead of a built-in list. If a model you were using
no longer appears, it is not enabled on your provider account.
