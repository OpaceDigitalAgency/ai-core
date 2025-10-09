# Image & Vision Models Reference (2025)

This document summarises current image / multimodal / vision models from key providers (OpenAI, Google Gemini, Anthropic Claude) including capabilities, parameter sets, and sample API calls. Use this as a reference when enabling image generation / editing in your app.

---

## Table of Contents

- [OpenAI](#openai)  
  - Overview & capabilities  
  - Supported models  
  - Endpoints & parameters  
  - Sample requests (generation, edit, variation)  
- [Google Gemini](#google-gemini)  
  - Overview & capabilities  
  - Supported models & modes  
  - Sample requests for image tasks  
- [Anthropic Claude](#anthropic-claude)  
  - Current state of image generation / vision  
  - What you *can* do  
- [Notes & caveats / versioning guidance](#notes--caveats)  

---

## OpenAI

### Overview & capabilities

- OpenAI now offers a natively multimodal model accessible via API called **`gpt-image-1`**, which accepts both text and image inputs, and can produce image outputs (generation, editing).  
- The standard “Images / Vision” guide in OpenAI docs describes that the API supports three kinds of operations: **Generations**, **Edits** and **Variations**.  
- The image API also supports older models like DALL·E‑2 and DALL·E‑3 (as fallback), though `gpt-image-1` is the flagship going forward.  
- Pricing, quotas, and access may depend on your OpenAI plan / organization.

### Supported models

- `gpt-image-1` — the primary OpenAI image model.  
- `dall-e-2`, `dall-e-3` — older models still supported in the Images API path.  

### Endpoints & parameters

OpenAI’s Images / Vision endpoints typically use the base path:

```
https://api.openai.com/v1/images/<operation>
```

where `<operation>` can be `generations`, `edits`, `variations`.

| Parameter | Required? | Type | Description / options |
|---|---|---|---|
| `model` | Yes | string | One of `gpt-image-1`, or `dall-e-3`, etc. |
| `prompt` | Yes (for generation & edits) | string | The textual description of what to create / modify |
| `n` | Optional | integer | Number of images to generate (1–10) |
| `size` | Optional | string | `"1024x1024"`, `"1024x1536"`, `"1536x1024"` |
| `quality` | Optional | string | `"low"`, `"medium"`, `"high"` (default = medium) |
| `image` | For edit / variation | string | Base64 or data URL image |
| `mask` | For edit | string | Mask image (transparent = editable) |
| `prompt_strength` | Optional | float | How strongly prompt overrides base image |

#### Generation example

```bash
curl https://api.openai.com/v1/images/generations   -H "Authorization: Bearer $OPENAI_API_KEY"   -H "Content-Type: application/json"   -d '{
    "model": "gpt-image-1",
    "prompt": "A serene mountain lake at sunrise, photorealistic",
    "n": 1,
    "size": "1024x1024",
    "quality": "high"
  }'
```

#### Editing example

```bash
curl https://api.openai.com/v1/images/edits   -H "Authorization: Bearer $OPENAI_API_KEY"   -H "Content-Type: application/json"   -d '{
    "model": "gpt-image-1",
    "image": "data:image/png;base64,AAAA…",
    "mask": "data:image/png;base64,BBBB…",
    "prompt": "Remove the tree and add a boat on the lake",
    "n": 1,
    "size": "1024x1024",
    "quality": "medium"
  }'
```

#### Variation example

```bash
curl https://api.openai.com/v1/images/variations   -H "Authorization: Bearer $OPENAI_API_KEY"   -H "Content-Type: application/json"   -d '{
    "model": "dall-e-3",
    "image": "data:image/png;base64,CCCC…",
    "n": 3,
    "size": "1024x1024"
  }'
```

---

## Google Gemini

### Overview & capabilities

- Gemini API supports **image understanding** and **image generation** (in `gemini-2.5-flash` image mode).  
- It allows text + image input, captioning, visual Q&A, and limited image generation.  

### Supported models

- `gemini-2.5-flash` — supports image input/output.  
- `gemini-2.5-flash-lite` — lightweight variant.  

### Example (Python)

```python
from google import genai
client = genai.Client()

uploaded = client.files.upload(file="path/to/photo.jpg")

response = client.models.generate_content(
  model="gemini-2.5-flash",
  contents=[uploaded, "Describe this image poetically."]
)
print(response.text)
```

Image generation mode:

```python
response = client.models.generate_content(
  model="gemini-2.5-flash",
  contents="A fantasy castle floating in the clouds",
  config=genai.types.GenerateContentConfig(response_mime_type="image/png")
)
```

---

## Anthropic Claude

### Overview

- Claude 4 (Sonnet, Opus) supports **vision input**, not generation.  
- You can send images for reasoning, captioning, or Q&A.  
- No text-to-image support yet.

### Example

```json
POST https://api.anthropic.com/v1/messages
Authorization: Bearer $CLAUDE_API_KEY
Content-Type: application/json

{
  "model": "claude-sonnet-4",
  "messages": [
    {
      "role": "user",
      "content": "Describe this image.",
      "image": {"data_url": "data:image/png;base64,AAAA…"}
    }
  ]
}
```

---

## Multimodal / “Omni” Models & Image Capabilities

### OpenAI — GPT-4o (and future GPT-5)

- GPT-4o includes built-in image generation: you no longer need a separate image-only model for many use cases. :contentReference[oaicite:9]{index=9}  
- The `gpt-image-1` model is the backend that enables image creation for multimodal models in OpenAI’s API. :contentReference[oaicite:10]{index=10}  
- Thus, if a user selects GPT-4o (or GPT-5 when supported), treat it as image-capable in your UI. Use the same image endpoints (generations, edits) with `model = "gpt-image-1"` or via a unified multimodal endpoint when available.

### Google Gemini — multimodal image generation

- Only the `gemini-2.5-flash-image` model supports multimodal **output** (i.e. generating images + text). Standard `gemini-2.5-flash` supports image *input* but not output. :contentReference[oaicite:11]{index=11}  
- Gemini models (2.0 and newer) are multimodal by design, supporting image input for captioning, classification, visual QA tasks. :contentReference[oaicite:12]{index=12}  
- For image generation tasks, always use `gemini-2.5-flash-image`. Ensure your UI marks that model appropriately as image-capable.

### Example: choosing a multimodal model in your UI

| Provider | Model name | Supports image output? | Notes |
|---|---|---|---|
| OpenAI | GPT-4o / GPT-5 | ✅ | Use `gpt-image-1` via image endpoints or unified multimodal API |
| Google Gemini | gemini-2.5-flash | ❌ | Supports image *input*, not output |
| Google Gemini | gemini-2.5-flash-image | ✅ | Multimodal output + image generation |

### Sample image generation via multimodal model (OpenAI / Gemini)

**OpenAI (GPT-4o)**  
```bash
curl https://api.openai.com/v1/images/generations \
  -H "Authorization: Bearer $KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "model": "gpt-image-1",
    "prompt": "A futuristic city skyline with floating gardens at sunset",
    "n": 1,
    "size": "1024x1024",
    "quality": "high"
  }'
```

**Gemini (gemini-2.5-flash-image)
```python
response = client.models.generate_content(
  model="gemini-2.5-flash-image",
  contents="A surreal desert landscape with glowing crystals",
  config=genai.types.GenerateContentConfig(response_mime_type="image/png")
)
```

---

## Notes & Caveats

1. **APIs evolve quickly.** Always check provider docs for new model IDs.  
2. **Feature gating:** Not all API keys have image generation rights.  
3. **Fallback logic:** Gracefully degrade to text-only if unavailable.  
4. **Rate limits:** Image ops are resource-heavy — implement safeguards.  
5. **Testing:** Provide a “test image generation” button in your app.

---
