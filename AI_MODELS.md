# Multi‑Provider PHP Plugin Guide (OpenAI, Anthropic Claude, xAI Grok, Google Gemini)  
_Last updated: 2025-10-05 07:49 UTC_

> **Goal:** Help you connect a PHP plugin to OpenAI, Claude (Anthropic), Grok (xAI) and Gemini (Google) in a way that’s accurate, future‑proof, and easy to maintain.

---

## 1) Are the connection patterns the same across providers?  
**Broadly yes (HTTP + JSON + Bearer auth), but with important differences:**
- **Base URL** and **endpoints** differ.
- **Message payload shape** differs (e.g., `messages` vs `contents` vs `input`).
- **Headers** can differ (e.g., `anthropic-version` is required for Claude).
- **Model identifiers** and **aliases** differ (date‑stamped snapshots; `-latest` aliases).
- **Tool/function calling**, **vision** inputs, **streaming**, and **batching** are implemented slightly differently.

This guide shows the minimal, **correct** request for each provider and models where behaviour differs. It also includes **runtime “list models”** snippets so you can fetch the *actual* model roster your key can use.

---

## 2) Base URLs, endpoints & authentication (quick reference)

| Provider | Base URL | Primary text endpoint | Alternative / Legacy | Auth |
|---|---|---|---|---|
| **OpenAI** | `https://api.openai.com` | `POST /v1/responses` (recommended) | `POST /v1/chat/completions` (legacy models) | Header: `Authorization: Bearer $OPENAI_API_KEY` |
| **Anthropic (Claude)** | `https://api.anthropic.com` | `POST /v1/messages` | — | Headers: `x-api-key: $ANTHROPIC_API_KEY`, `anthropic-version: 2023-06-01` |
| **xAI (Grok)** | `https://api.x.ai` | `POST /v1/chat/completions` (OpenAI‑compatible) | `POST /v1/responses` also available; `/v1/images/generations` for image gen | Header: `Authorization: Bearer $XAI_API_KEY` |
| **Google Gemini** | `https://generativelanguage.googleapis.com` | `POST /v1beta/models/{{model}}:generateContent` | Streaming: `:streamGenerateContent`; List models: `GET /v1beta/models` | API key in query `?key=...` or OAuth 2 Bearer token |

---

## 3) PHP: minimal chat request examples

> These examples deliberately use plain `curl` to avoid extra dependencies. Feel free to wrap in Guzzle later.

### 3.1 OpenAI — **Responses API** (recommended for GPT‑5 / GPT‑4.1 / GPT‑4o / o‑series)
```php
<?php
$ch = curl_init('https://api.openai.com/v1/responses');
curl_setopt_array($ch, [
  CURLOPT_HTTPHEADER => [
    'Authorization: Bearer ' . getenv('OPENAI_API_KEY'),
    'Content-Type: application/json'
  ],
  CURLOPT_POST => true,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_POSTFIELDS => json_encode([
    'model' => 'gpt-5', // or gpt-4.1, gpt-4o, o3, o4-mini, etc.
    'input' => [
      ['role' => 'user', 'content' => 'Say hello from PHP']
    ]
  ])
]);
$response = curl_exec($ch);
curl_close($ch);
echo $response;
```
**When to use Chat Completions instead:** If you must use older/legacy models that only support `POST /v1/chat/completions`, send `messages` instead of `input` (see §4.1).

---

### 3.2 Anthropic (Claude) — **Messages API**
```php
<?php
$ch = curl_init('https://api.anthropic.com/v1/messages');
curl_setopt_array($ch, [
  CURLOPT_HTTPHEADER => [
    'x-api-key: ' . getenv('ANTHROPIC_API_KEY'),
    'anthropic-version: 2023-06-01',
    'content-type: application/json'
  ],
  CURLOPT_POST => true,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_POSTFIELDS => json_encode([
    'model' => 'claude-sonnet-4-20250514', // or claude-opus-4-20250514, claude-3-7-sonnet-20250219, etc.
    'max_tokens' => 512,
    'messages' => [
      ['role' => 'user', 'content' => [['type' => 'text', 'text' => 'Say hello from PHP']]]
    ]
  ])
]);
$response = curl_exec($ch);
curl_close($ch);
echo $response;
```

---

### 3.3 xAI (Grok) — **OpenAI‑compatible Chat Completions**
```php
<?php
$ch = curl_init('https://api.x.ai/v1/chat/completions');
curl_setopt_array($ch, [
  CURLOPT_HTTPHEADER => [
    'Authorization: Bearer ' . getenv('XAI_API_KEY'),
    'Content-Type: application/json'
  ],
  CURLOPT_POST => true,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_POSTFIELDS => json_encode([
    'model' => 'grok-4', // or grok-4-fast, grok-3, etc.
    'messages' => [
      ['role' => 'user', 'content' => 'Say hello from PHP']
    ]
  ])
]);
$response = curl_exec($ch);
curl_close($ch);
echo $response;
```
xAI also offers `POST /v1/responses` and a separate endpoint for image generation at `/v1/images/generations` (see §5.3).

---

### 3.4 Google Gemini — **Generate Content**
```php
<?php
$apiKey = getenv('GOOGLE_API_KEY');
$url = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-pro:generateContent?key=' . $apiKey;

$body = [
  'contents' => [
    ['role' => 'user', 'parts' => [['text' => 'Say hello from PHP']]]
  ]
];

$ch = curl_init($url);
curl_setopt_array($ch, [
  CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
  CURLOPT_POST => true,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_POSTFIELDS => json_encode($body)
]);
$response = curl_exec($ch);
curl_close($ch);
echo $response;
```

---

## 4) Where models **differ** (payloads & endpoints)

### 4.1 Chat payload shape
- **OpenAI (Responses API):** `input` is a list of role‑based messages.  
- **OpenAI (Chat Completions, legacy):** `messages` (role/content).  
- **Anthropic (Claude):** `messages` but **each content item** is a list of **blocks** (`{type: "text"|"image", ...}`).  
- **xAI:** Like OpenAI Chat Completions by default.  
- **Gemini:** `contents` with `role` and `parts` (text, images, audio, etc.) and a **path‑based** model name in the URL.

**OpenAI (legacy Chat Completions) example (for legacy‑only models):**
```php
<?php
$ch = curl_init('https://api.openai.com/v1/chat/completions');
curl_setopt_array($ch, [
  CURLOPT_HTTPHEADER => [
    'Authorization: Bearer ' . getenv('OPENAI_API_KEY'),
    'Content-Type: application/json'
  ],
  CURLOPT_POST => true,
  CURLOPT_RETURNTRANSFER => true,
  CURLOPT_POSTFIELDS => json_encode([
    'model' => 'gpt-4', // legacy family example
    'messages' => [
      ['role' => 'system', 'content' => 'You are a helpful assistant.'],
      ['role' => 'user', 'content' => 'Hello!']
    ]
  ])
]);
echo curl_exec($ch);
curl_close($ch);
```

### 4.2 Vision input
- **OpenAI (Responses):** send image URLs/base64 inside `input` content items.  
- **Claude:** content block `{type:"image", source:{type:"base64"|"url", ...}}`.  
- **xAI:** via Chat Completions for image understanding (use a Grok vision‑capable model).  
- **Gemini:** add `parts` with `inlineData` (base64 + mime) or `fileData` references.

**Claude vision example (URL image):**
```php
<?php
$body = [
  'model' => 'claude-sonnet-4-20250514',
  'max_tokens' => 512,
  'messages' => [[
    'role' => 'user',
    'content' => [
      ['type' => 'image', 'source' => ['type' => 'url', 'url' => 'https://example.com/cat.jpg']],
      ['type' => 'text', 'text' => 'What is in the image?']
    ]
  ]]
];
```

**Gemini vision example (inline base64):**
```php
<?php
$imgB64 = base64_encode(file_get_contents('cat.jpg'));
$body = [
  'contents' => [[
    'role' => 'user',
    'parts' => [
      ['inlineData' => ['mimeType' => 'image/jpeg', 'data' => $imgB64]],
      ['text' => 'What is in the image?']
    ]
  ]]
];
```

### 4.3 Tool / function calling
- **OpenAI (Responses):** `tools` with JSON Schema (`type: "function"`) and `tool_choice` to control selection.  
- **Claude:** `tools` with `input_schema` (JSON Schema). Tool use returns `tool_use` blocks; you reply with `tool_result`.  
- **xAI:** OpenAI‑compatible function calling on Chat Completions.  
- **Gemini:** `tools.functionDeclarations` plus optional `toolConfig`.

**OpenAI (Responses) tool call (schema‑enforced JSON):**
```php
<?php
$body = [
  'model' => 'gpt-5',
  'input' => [['role' => 'user', 'content' => 'What is the weather in London?']],
  'tools' => [[
    'type' => 'function',
    'function' => [
      'name' => 'getWeather',
      'description' => 'Get weather by city name',
      'parameters' => [
        'type' => 'object',
        'properties' => ['city' => ['type' => 'string']],
        'required' => ['city'],
        'additionalProperties' => false
      ]
    ]
  ]],
  'tool_choice' => 'auto'
];
```

**Claude tool call (request):**
```php
<?php
$body = [
  'model' => 'claude-sonnet-4-20250514',
  'max_tokens' => 512,
  'tools' => [[
    'name' => 'getWeather',
    'description' => 'Get weather by city name',
    'input_schema' => [
      'type' => 'object',
      'properties' => ['city' => ['type' => 'string']],
      'required' => ['city'],
      'additionalProperties' => false
    ]
  ]],
  'messages' => [['role' => 'user', 'content' => [['type' => 'text', 'text' => 'Weather in London?']]]]
];
```

**Gemini function calling (request):**
```php
<?php
$body = [
  'contents' => [[
    'role' => 'user',
    'parts' => [['text' => 'Weather in London?']]
  ]],
  'tools' => [
    'functionDeclarations' => [[
      'name' => 'getWeather',
      'description' => 'Get weather by city name',
      'parameters' => [
        'type' => 'OBJECT',
        'properties' => ['city' => ['type' => 'STRING']],
        'required' => ['city']
      ]
    ]]
  ]
];
```

### 4.4 Reasoning / “thinking” models
- **OpenAI:** “o‑series” (e.g., `o3`, `o3-pro`, `o3-mini`) and GPT‑5 “Thinking” in ChatGPT; use Responses API.  
- **Claude:** enable extended thinking with a `thinking` budget (in SDKs/betas) and expect tool/stop reasons; Messages API.  
- **xAI:** Grok‑4 is a reasoning model; some “fast” models may offer separate reasoning/non‑reasoning variants.  
- **Gemini:** 2.5 Pro/Flash include advanced reasoning; same endpoint.

---

## 5) Model rosters **(as of 5 Oct 2025)**

> You should still **enumerate models at runtime** (see §6) because access is account/region dependent and model lists evolve. Below are current families and representative IDs to use in code.

### 5.1 OpenAI (model **IDs** you’ll pass in code)
- **GPT‑5 family:** `gpt-5` (primary). (Variants like “mini”/“nano” may be account‑specific.)  
- **o‑series (reasoning):** `o3`, `o3-mini`, `o3-pro`, `o4-mini`.  
- **GPT‑4.1 family:** `gpt-4.1`, `gpt-4.1-mini`.  
- **GPT‑4o family:** `gpt-4o`, `gpt-4o-mini` (often aliased by `chatgpt-4o-latest` in apps).  
- **Embeddings:** `text-embedding-3-large`, `text-embedding-3-small`.  
- **Audio / speech (examples):** `gpt-4o-mini-tts`, `gpt-4o-transcribe` (availability depends on account).

> **Legacy Chat Completions‑only models:** older GPT‑4 snapshots and GPT‑3.5‑Turbo variants. Prefer Responses API models when possible.

### 5.2 Anthropic Claude (model **IDs**)
- **Claude 4 (2025 snapshots):** `claude-sonnet-4-20250514`, `claude-opus-4-20250514`  
- **Claude 3.7 (2025 snapshots):** `claude-3-7-sonnet-20250219`, `claude-3-7-haiku-20250219`  
- **Claude 3.5 (2024 snapshot):** e.g., `claude-3.5-haiku-20241022` (may be legacy access)

> Anthropic use **date‑stamped** model IDs. You can also use the moving alias (e.g., `claude-sonnet-4`) but pin a snapshot in production for reproducibility.

### 5.3 xAI Grok (model **IDs**)
- **Reasoning / chat:** `grok-4`, `grok-4-fast`, `grok-4-fast-reasoning`, `grok-3`, `grok-3-mini`  
- **Vision / image understanding:** `grok-2-vision-1212`  
- **Image generation:** `grok-2-image` (use `/v1/images/generations`)  
- **Coding:** `grok-code-fast-1`

> xAI also supports **aliases** (`<model>`, `<model>-latest`, `<model>-<date>`).

### 5.4 Google Gemini (model **IDs**)
- **Reasoning / flagship:** `gemini-2.5-pro`  
- **Fast:** `gemini-2.5-flash-preview-09-2025`, `gemini-2.5-flash-lite`, `gemini-2.5-flash-lite-preview-09-2025`  
- **Image generation:** `gemini-2.5-flash-image` (also `gemini-2.5-flash-image-preview`)  
- **Live / native audio (preview/experimental):** `gemini-2.5-flash-native-audio-preview-09-2025`, `gemini-2.5-flash-exp-native-audio-thinking-dialog`  
- **TTS:** `gemini-2.5-flash-preview-tts`

> Full roster and token limits are published in the Gemini Models page; prefer stable (non‑preview) where possible.

---

## 6) Programmatically **list models** (so you don’t hard‑code)

### 6.1 OpenAI
```php
<?php
$ch = curl_init('https://api.openai.com/v1/models');
curl_setopt_array($ch, [
  CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . getenv('OPENAI_API_KEY')],
  CURLOPT_RETURNTRANSFER => true
]);
echo curl_exec($ch);
curl_close($ch);
```

### 6.2 Anthropic
```php
<?php
$ch = curl_init('https://api.anthropic.com/v1/models');
curl_setopt_array($ch, [
  CURLOPT_HTTPHEADER => [
    'x-api-key: ' . getenv('ANTHROPIC_API_KEY'),
    'anthropic-version: 2023-06-01'
  ],
  CURLOPT_RETURNTRANSFER => true
]);
echo curl_exec($ch);
curl_close($ch);
```

### 6.3 xAI
```php
<?php
$ch = curl_init('https://api.x.ai/v1/models');
curl_setopt_array($ch, [
  CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . getenv('XAI_API_KEY')],
  CURLOPT_RETURNTRANSFER => true
]);
echo curl_exec($ch);
curl_close($ch);
```

### 6.4 Google Gemini
```php
<?php
$apiKey = getenv('GOOGLE_API_KEY');
$ch = curl_init('https://generativelanguage.googleapis.com/v1beta/models?key=' . $apiKey);
curl_setopt_array($ch, [
  CURLOPT_RETURNTRANSFER => true
]);
echo curl_exec($ch);
curl_close($ch);
```

---

## 7) Streaming

- **OpenAI:** add `"stream": true` (SSE) on Responses or Chat Completions.  
- **Anthropic:** Messages API supports streaming; in SDKs use streaming helpers, with REST see event stream docs.  
- **xAI:** `stream: true` on Chat Completions.  
- **Gemini:** use `:streamGenerateContent` endpoint.

---

## 8) Embeddings

- **OpenAI:** `text-embedding-3-large`, `text-embedding-3-small` via `POST /v1/embeddings`.  
- **Gemini:** `:embedText` / `:embedContent` per model family.  
- **Anthropic/xAI:** check provider docs/features; xAI offers a Vector Store & search APIs.

---

## 9) Practical model routing tips (PHP)

- Build a **capabilities map** at start‑up: query each provider’s **list models** and memoise an `id → capabilities` map.  
- Route by **need**: reasoning → OpenAI o‑series / GPT‑5 / Claude Opus 4 / Gemini 2.5 Pro; speed/cost → GPT‑4.1 mini / Gemini 2.5 Flash‑Lite / Grok‑3‑mini / Claude 3.7 Haiku.  
- Pin **snapshots** in production (e.g., `claude-sonnet-4-20250514`).  
- Keep **per‑provider adapters** so the rest of your code uses one internal shape.

---

## 10) References (official docs)

- **OpenAI**
  - Responses API (overview & reference): https://platform.openai.com/docs/api-reference/responses  
  - Migrate to Responses: https://platform.openai.com/docs/guides/migrate-to-responses  
  - Models overview (GPT‑4.1 / 4o / o‑series / embeddings): https://platform.openai.com/docs/models  
  - Model release/help articles (GPT‑5, GPT‑4.1 mini, caps): https://help.openai.com/

- **Anthropic (Claude)**
  - Messages API (reference & examples): https://docs.anthropic.com/api/messages  
  - Model list / snapshots / migration to Claude 4: https://docs.anthropic.com/docs/about-claude/models/migrating-to-claude-4  
  - Vision, tools, extended thinking, batches, prompt caching: https://docs.anthropic.com/

- **xAI (Grok)**
  - REST API reference, models, aliases: https://docs.x.ai/docs/api-reference and https://docs.x.ai/docs/models  
  - OpenAI‑compatible usage & migration: https://docs.x.ai/docs/guides/migration  
  - Image generations endpoint: https://docs.x.ai/docs/guides/image-generations

- **Google Gemini**
  - Models catalogue (IDs, limits, versions): https://ai.google.dev/gemini-api/docs/models  
  - API endpoints (generateContent, stream, list models): https://ai.google.dev/api/all-methods

---

## 11) Licence & notes
- This document uses links to the **canonical provider docs**. Model availability varies by account, region and plan.  
- Always check the **list models** endpoint at runtime in your plugin before showing choices or calling a model.
