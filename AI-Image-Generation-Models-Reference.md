# 🧠 AI Image Generation Models — Complete Reference (2025)

This document summarises everything discussed about **AI image generation models** across OpenAI, Google Gemini, xAI (Grok), and Anthropic (Claude) — including which models can actually **generate images**, how to **detect** this in code, and working **PHP examples**.

---

## 📘 Overview

Different AI providers expose **multimodal models** (text, image, audio) but *not all support image generation*. Some can **understand images** but **can’t create** them. This file clarifies exactly which do what, and how to handle them in code.

---

## 🧩 Summary Table — Image Generation Capability

| Provider | Model / Family | Image Generation | Notes |
|-----------|----------------|------------------|--------|
| **OpenAI** | `gpt-image-1` | ✅ Yes | Official OpenAI image generation model replacing DALL·E. |
|  | `dall-e-2`, `dall-e-3` | ✅ Yes | Legacy models still supported via Images API. |
|  | `gpt-4o` | ✅ Yes | Supports text→image generation (multimodal). |
|  | `gpt-5` | ⚠️ Not confirmed | Multimodal for input/output; image gen not yet documented. |
| **Google Gemini** | `gemini-2.5-flash-image` | ✅ Yes | Official model for image generation & editing. |
|  | `gemini-2.5-flash-image-preview` | ✅ Yes | Preview variant; may be deprecated. |
|  | `gemini-2.5-flash` / `gemini-2.5-pro` | ❌ No | Text & image understanding only. Cannot generate images. |
| **xAI (Grok)** | `grok-2-image-1212` | ✅ Yes | Grok model for image generation. |
| **Anthropic (Claude)** | Any (Claude 3, 4, 4.5, etc.) | ❌ No | Claude can see images, not create them. |

---

## 🧠 OpenAI — Image Generation

### ✅ Supported Models
- `gpt-image-1` (primary)
- `dall-e-2`
- `dall-e-3`
- `gpt-4o` (native multimodal support)
- `o-series` models may handle visual reasoning but not confirmed for image generation.

### 📍 Endpoint
```
POST https://api.openai.com/v1/images/generations
```

### 🧾 PHP Example
```php
$ch = curl_init('https://api.openai.com/v1/images/generations');
$body = [
  'model' => 'gpt-image-1',
  'prompt' => 'A golden retriever surfing a wave at sunset'
];
curl_setopt_array($ch, [
  CURLOPT_HTTPHEADER => [
    'Authorization: Bearer ' . getenv('OPENAI_API_KEY'),
    'Content-Type: application/json'
  ],
  CURLOPT_POST => true,
  CURLOPT_POSTFIELDS => json_encode($body),
  CURLOPT_RETURNTRANSFER => true
]);
echo curl_exec($ch);
```

---

## 🧠 Google Gemini — Image Generation

### ✅ Supported Models
- `gemini-2.5-flash-image`
- `gemini-2.5-flash-image-preview`

### ❌ Non-Supported Models
- `gemini-2.5-flash`
- `gemini-2.5-pro`

These two can **understand** images but **cannot create** them.

### 📍 Endpoint
```
POST https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-image:generateContent
```

### 🧾 PHP Example — Text → Image
```php
$apiKey = getenv('GEMINI_API_KEY');
$model  = 'gemini-2.5-flash-image';

$endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";

$body = [
  'contents' => [[
    'parts' => [['text' => 'A futuristic city skyline at dusk']]
  ]]
];

$ch = curl_init($endpoint);
curl_setopt_array($ch, [
  CURLOPT_HTTPHEADER => [
    'Content-Type: application/json',
    'x-goog-api-key: ' . $apiKey,
  ],
  CURLOPT_POST => true,
  CURLOPT_POSTFIELDS => json_encode($body),
  CURLOPT_RETURNTRANSFER => true,
]);
$response = curl_exec($ch);
$data = json_decode($response, true);

foreach ($data['candidates'][0]['content']['parts'] as $part) {
  if (isset($part['inlineData']['data'])) {
    file_put_contents('gemini_image.png', base64_decode($part['inlineData']['data']));
  }
}
```

### 🧾 PHP Example — Image + Text → Image (Editing)
```php
$apiKey = getenv('GEMINI_API_KEY');
$model  = 'gemini-2.5-flash-image';
$endpoint = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";

$imageB64 = base64_encode(file_get_contents('input.jpg'));

$body = [
  'contents' => [[
    'parts' => [
      ['inlineData' => ['mimeType' => 'image/jpeg', 'data' => $imageB64]],
      ['text' => 'Turn this into a painting with impressionist style']
    ]
  ]]
];

$ch = curl_init($endpoint);
curl_setopt_array($ch, [
  CURLOPT_HTTPHEADER => [
    'Content-Type: application/json',
    'x-goog-api-key: ' . $apiKey,
  ],
  CURLOPT_POST => true,
  CURLOPT_POSTFIELDS => json_encode($body),
  CURLOPT_RETURNTRANSFER => true,
]);
$response = curl_exec($ch);
$data = json_decode($response, true);

foreach ($data['candidates'][0]['content']['parts'] as $part) {
  if (isset($part['inlineData']['data'])) {
    file_put_contents('gemini_edit.png', base64_decode($part['inlineData']['data']));
  }
}
```

### 🔍 Detection Logic for Plugins
```php
$apiKey = getenv('GOOGLE_AI_API_KEY');
$list = file_get_contents("https://generativelanguage.googleapis.com/v1beta/models?key={$apiKey}");
$models = json_decode($list, true);
$imageModels = array_filter($models['models'], fn($m) =>
  stripos($m['name'], 'image') !== false
);
```

If the user’s selected model name does **not** include `-image`, disable “Generate Image” in your UI.

---

## 🧠 xAI (Grok) — Image Generation

### ✅ Supported Models
- `grok-2-image-1212`

### 📍 Endpoint
```
POST https://api.x.ai/v1/images/generations
```

### 🧾 PHP Example
```php
$ch = curl_init('https://api.x.ai/v1/images/generations');
$body = [
  'model' => 'grok-2-image-1212',
  'prompt' => 'A futuristic robot exploring Mars'
];
curl_setopt_array($ch, [
  CURLOPT_HTTPHEADER => [
    'Authorization: Bearer ' . getenv('XAI_API_KEY'),
    'Content-Type: application/json'
  ],
  CURLOPT_POST => true,
  CURLOPT_POSTFIELDS => json_encode($body),
  CURLOPT_RETURNTRANSFER => true
]);
echo curl_exec($ch);
```

---

## 🧠 Anthropic (Claude) — No Image Generation

Claude models (e.g., Claude 3, Claude 4, Claude 4.5 Sonnet) can **process** and **understand** images but **cannot generate** them.  
They’re for text-based reasoning and visual comprehension only.

---

## 🧮 Dynamic Detection Logic (Universal)

When building an AI plugin or API router:

```php
function modelSupportsImageGen($provider, $model) {
  $imageModels = [
    'openai' => ['gpt-image-1','dall-e-3','dall-e-2','gpt-4o'],
    'gemini' => ['gemini-2.5-flash-image','gemini-2.5-flash-image-preview'],
    'xai'    => ['grok-2-image-1212'],
    'anthropic' => [] // none
  ];
  $p = strtolower($provider);
  return isset($imageModels[$p]) && in_array($model, $imageModels[$p], true);
}
```

This logic allows automatic UI/feature toggling.

---

## 🧾 Notes for GPT‑5 and Reasoning

- **GPT‑5** uses the **Responses API**, not the old Chat Completions API.
- It has no public `reasoning_level` parameter.
- Use `max_output_tokens` (not `max_tokens` or `max_completion_tokens`).
- Reasoning depth is **auto-managed** — raising output length indirectly increases reasoning.

```json
{
  "model": "gpt-5",
  "input": [{"role": "user", "content": "Say hello"}],
  "max_output_tokens": 1024
}
```

---

## ✅ TL;DR for Implementation

| Task | Model Required | API / Endpoint | Output Type |
|------|----------------|----------------|--------------|
| Text → Image | `gpt-image-1`, `gemini-2.5-flash-image`, `grok-2-image-1212` | Provider-specific “image generation” API | Base64 PNG |
| Image → Image (edit) | `gemini-2.5-flash-image` | `:generateContent` | Base64 PNG |
| Text → Text | Any | Standard Chat/Responses API | Text |
| Image → Text (analysis) | `gpt-4o`, `gemini-2.5-flash`, `claude-4.5` | Standard Chat/Responses/Messages | Text |

---

## 🧾 Key Takeaways

1. ✅ **Use `gemini-2.5-flash-image`** for image generation.  
   `gemini-2.5-flash` and `gemini-2.5-pro` only understand images.  
2. ✅ **OpenAI image models:** `gpt-image-1` and DALL·E 2/3 remain valid.  
3. ✅ **xAI (Grok):** Use `grok-2-image-1212` for image generation.  
4. ❌ **Anthropic (Claude):** No public image generation capability.  
5. ⚙️ Always detect model names dynamically — if it doesn’t include “image”, assume no output images.  
6. 📸 Image results always come as **Base64** in `inlineData.data`.  
7. 🧩 `gemini-2.5-flash-image` replaces older “preview” models — future-proof your integration now.

---

**Updated:** October 2025  
**Author:** ChatGPT (GPT‑5)  
**Purpose:** Avoid confusion around which AI models can generate images and how to handle them programmatically.
