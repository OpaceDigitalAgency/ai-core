
# PHP Plugin: Connecting to OpenAI, Anthropic (Claude), xAI (Grok), and Google Gemini — **Models, Endpoints, & Examples**  
_Last updated: 2025-10-05_

> This doc shows how to connect to each provider, highlights request/response differences, and lists **current model IDs** you can use in code. It also includes PHP cURL examples and model‑listing snippets so your plugin can keep itself up to date.

---

## Quick answer: Is the connection “the same” across providers?

**No.** All four use HTTPS + Bearer API keys with JSON, but request shapes and endpoints differ:

- **OpenAI:** Prefer **`/v1/responses`** (new) for GPT‑5, o‑series, GPT‑4.x, etc. Legacy **`/v1/chat/completions`** still works for older code paths.  
- **Anthropic (Claude):** Single **`/v1/messages`** endpoint with a specific **`messages: [...]`** schema and required `anthropic-version` header.  
- **xAI (Grok):** Offers **OpenAI‑compatible** **`/v1/chat/completions`** and **`/v1/responses`**, plus an **Anthropic‑compatible** **`/v1/messages`**.  
- **Google Gemini:** **`models/{model}:generateContent`** (and streaming variants) over Google’s Generative Language (Gemini) API.

---

## Essentials per provider

### 1) OpenAI

- **Base URL:** `https://api.openai.com`  
- **Primary endpoints:**  
  - `POST /v1/responses` (recommended)  
  - `POST /v1/chat/completions` (legacy compatibility)  
  - `GET  /v1/models` (discover enabled models)  
  - `POST /v1/embeddings` (embeddings)  
  - Speech: `POST /v1/audio/speech` (TTS), `POST /v1/audio/transcriptions` (STT)
- **Auth header:** `Authorization: Bearer $OPENAI_API_KEY`

#### OpenAI — PHP example (Responses API, GPT‑5/gpt‑4o/o3)
```php
<?php
$ch = curl_init("https://api.openai.com/v1/responses");
$payload = [
  "model" => "gpt-5",
  "input" => "Summarise this in one sentence: PHP is great for plugins.",
];
curl_setopt_array($ch, [
  CURLOPT_HTTPHEADER => [
    "Authorization: Bearer " . getenv("OPENAI_API_KEY"),
    "Content-Type: application/json"
  ],
  CURLOPT_POST => true,
  CURLOPT_POSTFIELDS => json_encode($payload),
  CURLOPT_RETURNTRANSFER => true,
]);
$resp = curl_exec($ch);
if ($resp === false) { throw new Exception(curl_error($ch)); }
curl_close($ch);
echo $resp;
```

#### OpenAI — PHP example (legacy Chat Completions, 3.5/4‑style)
```php
<?php
$ch = curl_init("https://api.openai.com/v1/chat/completions");
$payload = [
  "model" => "gpt-4.1", // or another chat-completions compatible model
  "messages" => [
    ["role" => "system", "content" => "You are helpful."],
    ["role" => "user", "content" => "Write a PHP hello world"]
  ]
];
curl_setopt_array($ch, [
  CURLOPT_HTTPHEADER => [
    "Authorization: Bearer " . getenv("OPENAI_API_KEY"),
    "Content-Type: application/json"
  ],
  CURLOPT_POST => true,
  CURLOPT_POSTFIELDS => json_encode($payload),
  CURLOPT_RETURNTRANSFER => true,
]);
$resp = curl_exec($ch);
if ($resp === false) { throw new Exception(curl_error($ch)); }
curl_close($ch);
echo $resp;
```

#### OpenAI — list models in PHP
```php
<?php
$ch = curl_init("https://api.openai.com/v1/models");
curl_setopt_array($ch, [
  CURLOPT_HTTPHEADER => ["Authorization: Bearer " . getenv("OPENAI_API_KEY")],
  CURLOPT_RETURNTRANSFER => true,
]);
$resp = curl_exec($ch);
if ($resp === false) { throw new Exception(curl_error($ch)); }
curl_close($ch);
echo $resp;
```

#### OpenAI — model IDs (current, non‑exhaustive but official & widely used)
> Always query `/v1/models` at runtime for what your key can access. These are the **documented model IDs** you’ll most likely need:

- **GPT‑5 family:** `gpt-5`, `gpt-5-mini`, `gpt-5-nano`  
- **Reasoning (o‑series):** `o3`, `o3-pro`, `o3-mini`, `o4-mini`  
- **GPT‑4.x:** `gpt-4.1`, `gpt-4.1-mini`, `gpt-4o`, `chatgpt-4o-latest`, `gpt-4o-mini`  
- **Embeddings:** `text-embedding-3-large`, `text-embedding-3-small`  
- **Speech:** `tts-1`, `tts-1-hd`  
- **Transcription:** `whisper-1`, `gpt-4o-mini-transcribe`, `gpt-4o-transcribe`

(See references section for official model pages.)

---

### 2) Anthropic (Claude)

- **Base URL:** `https://api.anthropic.com`  
- **Primary endpoints:**  
  - `POST /v1/messages`  
  - `GET  /v1/models` (list), `GET /v1/models/{id}`
- **Required headers:**  
  - `x-api-key: $ANTHROPIC_API_KEY`  
  - `anthropic-version: 2023-06-01` (or newer per docs)

#### Claude — PHP example (Messages API)
```php
<?php
$ch = curl_init("https://api.anthropic.com/v1/messages");
$payload = [
  "model" => "claude-sonnet-4-5-20250929",
  "max_tokens" => 1024,
  "messages" => [
    ["role" => "user", "content" => "Give me three bullet points about PHP."]
  ]
];
curl_setopt_array($ch, [
  CURLOPT_HTTPHEADER => [
    "x-api-key: " . getenv("ANTHROPIC_API_KEY"),
    "anthropic-version: 2023-06-01",
    "Content-Type: application/json"
  ],
  CURLOPT_POST => true,
  CURLOPT_POSTFIELDS => json_encode($payload),
  CURLOPT_RETURNTRANSFER => true,
]);
$resp = curl_exec($ch);
if ($resp === false) { throw new Exception(curl_error($ch)); }
curl_close($ch);
echo $resp;
```

#### Claude — list models in PHP
```php
<?php
$ch = curl_init("https://api.anthropic.com/v1/models");
curl_setopt_array($ch, [
  CURLOPT_HTTPHEADER => [
    "x-api-key: " . getenv("ANTHROPIC_API_KEY"),
    "anthropic-version: 2023-06-01"
  ],
  CURLOPT_RETURNTRANSFER => true,
]);
$resp = curl_exec($ch);
if ($resp === false) { throw new Exception(curl_error($ch)); }
curl_close($ch);
echo $resp;
```

#### Claude — model IDs (Claude Docs “Models overview” snapshot IDs)
- **Claude Sonnet 4.5:** `claude-sonnet-4-5-20250929`  
- **Claude Sonnet 4:** `claude-sonnet-4-20250514`  
- **Claude 3.7 Sonnet:** `claude-3-7-sonnet-20250219` (alias: `claude-3-7-sonnet-latest`)  
- **Claude Opus 4.1:** `claude-opus-4-1-20250805`  
- **Claude Opus 4:** `claude-opus-4-20250514`  
- **Claude 3.5 Haiku:** `claude-3-5-haiku-20241022` (alias: `claude-3-5-haiku-latest`)  
- **Claude 3 Haiku:** `claude-3-haiku-20240307`

> Tip: Prefer snapshot IDs in production; aliases like `claude-sonnet-4-5` resolve to the latest snapshot.

---

### 3) xAI (Grok)

- **Base URL:** `https://api.x.ai`  
- **Primary endpoints:**  
  - OpenAI‑style: `POST /v1/chat/completions`, `POST /v1/responses`  
  - Anthropic‑style: `POST /v1/messages`  
  - Listing: `GET /v1/models`, `GET /v1/language-models`, `GET /v1/image-generation-models`

#### Grok — PHP example (OpenAI‑compatible chat)
```php
<?php
$ch = curl_init("https://api.x.ai/v1/chat/completions");
$payload = [
  "model" => "grok-4-fast",
  "messages" => [
    ["role" => "system", "content" => "You are helpful."],
    ["role" => "user", "content" => "Explain webhooks in PHP."]
  ]
];
curl_setopt_array($ch, [
  CURLOPT_HTTPHEADER => [
    "Authorization: Bearer " . getenv("XAI_API_KEY"),
    "Content-Type: application/json"
  ],
  CURLOPT_POST => true,
  CURLOPT_POSTFIELDS => json_encode($payload),
  CURLOPT_RETURNTRANSFER => true,
]);
$resp = curl_exec($ch);
if ($resp === false) { throw new Exception(curl_error($ch)); }
curl_close($ch);
echo $resp;
```

#### Grok — list models in PHP
```php
<?php
$ch = curl_init("https://api.x.ai/v1/models");
curl_setopt_array($ch, [
  CURLOPT_HTTPHEADER => ["Authorization: Bearer " . getenv("XAI_API_KEY")],
  CURLOPT_RETURNTRANSFER => true,
]);
$resp = curl_exec($ch);
if ($resp === false) { throw new Exception(curl_error($ch)); }
curl_close($ch);
echo $resp;
```

#### Grok — model IDs (from xAI Models page)
- `grok-4-fast`  
- `grok-4-fast-reasoning`  
- `grok-4-fast-non-reasoning`  
- `grok-4-0709` (snapshot)  
- `grok-3`  
- `grok-3-mini`  
- `grok-2-vision-1212`  
- `grok-2-image-1212`  
- `grok-code-fast-1`

> xAI also exposes **language‑model** and **image‑generation‑model** listings if you want category‑specific filters.

---

### 4) Google Gemini

- **REST base:** `https://generativelanguage.googleapis.com` (Gemini API)  
- **Primary endpoints (REST):**  
  - `POST /v1/models/{model}:generateContent`  
  - `POST /v1beta/models/{model}:streamGenerateContent` (stream)  
  - `GET  /v1/models` (list)  
- **Auth:** `?key=YOUR_API_KEY` query param or OAuth (server‑to‑server often uses API key)

#### Gemini — PHP example (generateContent)
```php
<?php
$apiKey = getenv("GOOGLE_AI_API_KEY");
$model  = "gemini-2.5-flash";

$url = "https://generativelanguage.googleapis.com/v1/models/{$model}:generateContent?key={$apiKey}";

$payload = [
  "contents" => [[
    "role" => "user",
    "parts" => [["text" => "List 3 PHP security tips."]]
  ]]
];

$ch = curl_init($url);
curl_setopt_array($ch, [
  CURLOPT_HTTPHEADER => ["Content-Type: application/json"],
  CURLOPT_POST => true,
  CURLOPT_POSTFIELDS => json_encode($payload),
  CURLOPT_RETURNTRANSFER => true,
]);
$resp = curl_exec($ch);
if ($resp === false) { throw new Exception(curl_error($ch)); }
curl_close($ch);
echo $resp;
```

#### Gemini — list models in PHP
```php
<?php
$apiKey = getenv("GOOGLE_AI_API_KEY");
$ch = curl_init("https://generativelanguage.googleapis.com/v1/models?key={$apiKey}");
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true,
]);
$resp = curl_exec($ch);
if ($resp === false) { throw new Exception(curl_error($ch)); }
curl_close($ch);
echo $resp;
```

#### Gemini — model IDs (from Google’s Models page)
- **Core:** `gemini-2.5-flash`, `gemini-2.5-flash-preview-09-2025`  
- **Image:** `gemini-2.5-flash-image`, `gemini-2.5-flash-image-preview`  
- **Live/Native‑audio (preview/experimental):**  
  `gemini-2.5-flash-native-audio-preview-09-2025`,  
  `gemini-2.5-flash-preview-native-audio-dialog`,  
  `gemini-2.5-flash-preview-05-20`,  
  `gemini-live-2.5-flash-preview`,  
  `gemini-2.5-flash-exp-native-audio-thinking-dialog`  
- **Lite:** `gemini-2.5-flash-lite`, `gemini-2.5-flash-lite-preview-09-2025`  
- **TTS:** `gemini-2.5-pro-preview-tts`, `gemini-2.5-flash-preview-tts`

> Google’s page also chronicles **previous models** (1.5/2.0), but the above list reflects current 2.5‑series options you’ll likely target.

---

## Side‑by‑side request differences (short)

| Provider | Endpoint you’ll call | Top‑level request field for prompt | How images are sent |
|---|---|---|---|
| OpenAI | `POST /v1/responses` | `input` (string or array of content parts) | As image parts (base64/URL) within `input` |
| OpenAI (legacy) | `POST /v1/chat/completions` | `messages: [...]` | `messages[].content` can include image parts |
| Anthropic | `POST /v1/messages` | `messages: [...]` with role/content objects | `content` parts with `type: "image"` |
| xAI | `POST /v1/chat/completions` or `/v1/responses` | same as OpenAI | same as OpenAI |
| Gemini | `POST models/{{id}}:generateContent` | `contents: [...]` with `role`/`parts` | `parts` can include inline images or file refs |

---

## Keep your plugin in sync

Call **each provider’s list‑models endpoint** at startup (and cache), expose a “refresh models” button in your UI, and allow per‑provider fallbacks.

- OpenAI: `GET https://api.openai.com/v1/models`  
- Anthropic: `GET https://api.anthropic.com/v1/models`  
- xAI: `GET https://api.x.ai/v1/models` (or `/v1/language-models`)  
- Gemini: `GET https://generativelanguage.googleapis.com/v1/models?key=...`

---

## References (official)

- **OpenAI models & endpoints:**  
  Models index (incl. GPT‑5, o3, 4.1, 4o, etc.), Responses API, Chat Completions, Embeddings, TTS, Whisper/STT.  
  - https://platform.openai.com/docs/models  
  - https://platform.openai.com/docs/models/gpt-5  
  - https://platform.openai.com/docs/models/o3  
  - https://platform.openai.com/docs/models/gpt-4o-mini  
  - https://platform.openai.com/docs/models/chatgpt-4o-latest  
  - https://platform.openai.com/docs/models/text-embedding-3-large  
  - https://platform.openai.com/docs/guides/text-to-speech  
  - https://platform.openai.com/docs/guides/speech-to-text  
  - Release notes overview: https://help.openai.com/en/articles/9624314-model-release-notes  
  - GPT‑5 for developers: https://openai.com/index/introducing-gpt-5-for-developers/

- **Anthropic (Claude):**  
  - Models overview (with exact IDs & aliases): https://anthropic.mintlify.app/en/docs/about-claude/models/overview  
  - List Models endpoint: https://anthropic.mintlify.app/en/api/models-list

- **xAI (Grok):**  
  - REST API reference (endpoints & listings): https://docs.x.ai/docs/api-reference  
  - Models page (IDs like `grok-4-fast`, `grok-3`, etc.): https://docs.x.ai/docs/models

- **Google Gemini:**  
  - Models (IDs like `gemini-2.5-flash`, image/live/tts variants): https://ai.google.dev/gemini-api/docs/models  
  - REST reference (Vertex flavour shown for generateContent): https://cloud.google.com/vertex-ai/generative-ai/docs/reference/rest/v1beta1/projects.locations.endpoints/generateContent

---

## Notes on deprecations & variability

- Providers add/retire snapshots. Always prefer **snapshot IDs** (Anthropic) or **stable model IDs** (Google) in production, and **query the list endpoint** before exposing models to users.  
- OpenAI’s **`/v1/responses`** is the forward‑compatible path; older code using **`chat/completions`** may still work but should be modernised over time.  
- xAI intentionally mirrors OpenAI’s shapes; if you’ve written a clean OpenAI layer, xAI is nearly drop‑in.

---

## Minimal PHP interface sketch (per provider adapters)

Structure your plugin with an interface and one adapter per provider. Each adapter exposes: `listModels()`, `complete()`, and optional `embed()`, `speech()`, etc. The code samples above show the request bodies to send.
