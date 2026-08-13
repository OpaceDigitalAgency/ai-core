# AI-Core — Universal AI Integration Hub

> **Opace open-source portfolio:** [Browse Opace WordPress plugins, AI Agent Skills and web platforms](https://github.com/OpaceDigitalAgency/OpaceDigitalAgency)

**One place for your WordPress site's AI provider credentials, model lists, prompts and usage data.**

Version 0.7.9 · Requires WordPress 6.5+ (tested to 7.0.4) · Requires PHP 7.4+ · GPL-2.0-or-later

AI-Core is a hub, not a content tool. It stores API keys, discovers the models your account actually
has, normalises every provider's responses into one shape, and records what you spent. The generating
is done by the plugins that sit on top of it — [AI Scribe](https://github.com/OpaceDigitalAgency/ai-scribe-chat-gpt-content-creator),
AI Imagen, or your own.

---

## How it fits together

```
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
```

Because the provider layer lives here, a fix or a new model reaches every plugin at once instead of
being repeated in each of them.

---

## Providers

Each provider is reached through its own documented contract rather than one lowest-common-denominator
request. That distinction matters: the three APIs disagree about how to enforce a response schema, what
a token limit is called, and which sampling parameters they will even accept.

| Provider | Text | Images | Schema mechanism |
|---|---|---|---|
| **OpenAI** | Chat Completions and Responses APIs | GPT Image, incl. `gpt-image-2` | JSON schema (`response_format` / `text.format`) |
| **Anthropic** | Messages API | none offered | forced tool call |
| **Google Gemini** | v1beta, so preview models are reachable | Gemini image models | `responseSchema` |

A site can therefore mix providers — write with Claude and generate images with OpenAI or Gemini,
which is the usual arrangement, since Anthropic has no image model at all.

---

## Source layout

```
ai-core.php                bootstrap
admin/                     settings, dashboard, prompt library, statistics
lib/src/
  AICore.php               façade used by dependent plugins
  Providers/               OpenAI, Anthropic, Gemini (text + image)
  Registry/ModelRegistry   capabilities, pricing, preferred/image model choice
  Response/                normaliser — one response shape for every provider
  Http/                    request transport
  Interfaces/              provider contracts
```

---

## PHP API

Dependent plugins call the façade rather than a provider directly:

```php
if ( ! class_exists( 'AICore\\AICore' ) ) {
    return; // hub not active
}

AICore\AICore::init( $config );

$response = AICore\AICore::sendTextRequest(
    'claude-opus-5',
    [ [ 'role' => 'user', 'content' => 'Write a headline about tea.' ] ],
    [ 'max_tokens' => 256 ]
);

if ( AICore\AICore::hasError( $response ) ) {
    $error = AICore\AICore::extractError( $response );
} else {
    $text  = AICore\AICore::extractContent( $response );
    $usage = AICore\AICore::extractUsage( $response );
}
```

The response is normalised, so the calling plugin reads the same structure whichever provider served
it — including tool-call output, which is how a Claude structured response arrives.

---

## Security

- Keys are encrypted at rest with AES-256-CBC, a random IV per value, and a key derived from the
  site's WordPress salts.
- Encryption is **fail-closed**: if it cannot encrypt, the write fails rather than storing plain text.
- Decryption is transparent to consumers.
- Rotating the salts in `wp-config.php` makes stored keys unreadable — they must be re-entered.

---

## Uninstall

Keys, settings, statistics and the prompt library are **kept** by default, because other plugins depend
on them. Turn off "Persist Settings on Uninstall" in Settings *before* deleting to remove the options,
both database tables and the transients. AI-Core never touches another plugin's data.

---

## Installation

1. Upload to `/wp-content/plugins/`, or install through Plugins → Add New.
2. Activate.
3. AI-Core → Settings: enter a key for at least one provider and press Test.
4. Choose a default provider.

Dependent plugins find the configuration on their own.

---

## Licence

GPL-2.0-or-later. © [Opace Digital Agency](https://opace.agency/services/web-design/wordpress-development/).

---

## Developer documentation

- [Project master document](docs/PROJECT_MASTER.md)
- [Provider and model reference](docs/AI_PROVIDERS_MODELS.md)
- [Testing guide](docs/TESTING_GUIDE.md)
- [WordPress.org compliance report](docs/WORDPRESS_ORG_COMPLIANCE_REPORT.md)
- [Bundled add-ons](bundled-addons/README.md)
- [Security policy](SECURITY.md)

Never commit API keys to this repository or expose them in client-side code.

## Related projects

- [AI Scribe](https://github.com/OpaceDigitalAgency/ai-scribe-chat-gpt-content-creator) — SEO content creator built on this hub
- [Opace Agent Skills](https://github.com/OpaceDigitalAgency/skills)

Maintained by [Opace Digital Agency](https://opace.agency).
