# ai-core Vibe Builder Add-on Specification

This document defines the full design and implementation plan for a **“vibe coding” add-on** to the `ai-core` plugin.  
It enables non-technical WordPress users to generate and install safe, AI-built plugins using simple prompts — with zero setup, accounts, or coding knowledge.

---

## 🧩 Overview

The goal:  
Let anyone log in to WordPress, type **“Create a contact form with an email alert”**, preview the result safely, and install it instantly — just like Canva for WordPress plugins.

Everything heavy or risky (e.g. code generation, linting, packaging) happens in a secure sandbox environment, never directly on the user’s site.

---

## 🧠 Architecture

### Core Components

| Component | Role |
|------------|------|
| **ai-core** | Base framework providing settings, auth, prompt storage, UI components, and API routes. |
| **ai-core-vibe-builder** | New add-on that registers a “Vibe Builder” capability within `ai-core`. |
| **Sandbox Layers** | Safe environments for previewing and generating plugins. |
| **Templates Library** | Blueprints for common plugin types (CPTs, blocks, settings pages, etc.). |
| **Builder Service** | Optional remote endpoint (yours or others’) that handles composer/npm builds, linting, and signing. |

---

## ⚙️ Modes of Operation

Three runtime modes define how the builder works:

| Mode | Description | Infra Required | Ideal For |
|------|--------------|----------------|------------|
| **Lite** | In-browser generation using [WordPress Playground (WASM)](https://developer.wordpress.org/playground/). No servers. | None | Free for everyone |
| **Community** | Uses your hosted builder endpoint (shared API). Users don’t need accounts. | Minimal VPS or Cloudflare Worker | Best UX |
| **BYO (Bring Your Own)** | Advanced users connect their own builder endpoint + public key. | External (self-managed) | Agencies, devs |

---

## 🧩 User Experience (Non-Tech Flow)

### 1. Log in → “Create with AI”
User clicks a friendly button inside WP admin:
> “Describe what you’d like WordPress to do…”

Example:
> _“Add a testimonials carousel block with a settings page.”_

### 2. AI Planning & Build
Progress messages show:
- “Planning plugin structure…”
- “Creating files…”
- “Testing for safety…”

### 3. Preview
Preview appears in a **Playground sandbox** within the same screen.
They can click around safely.

### 4. Safety Check
AI-core runs static analysis (no `eval`, no file ops, etc.)  
Shows a report:
> ✅ “Checked 12 files – 0 unsafe functions detected.”

### 5. Install
Click **Install**, and it installs like any normal plugin.

### 6. Manage & Rebuild
Each creation is saved in a table:
| Name | Date | Status | Preview | Rebuild |
|------|------|---------|----------|----------|
| Contact Form Pro | 03 Oct 2025 | Installed | 🔗 | ♻️ |

---

## 🧰 ai-core-vibe-builder Add-on Structure

**Plugin folder:**
ai-core-vibe-builder/
│
├── ai-core-vibe-builder.php
├── inc/
│ ├── class-ai-vibe-builder.php
│ ├── class-ai-vibe-api.php
│ ├── class-ai-vibe-templates.php
│ └── class-ai-vibe-safety.php
├── build/
│ ├── admin.js (React UI)
│ └── style.css
├── templates/
│ ├── block/
│ ├── settings/
│ ├── shortcode/
│ ├── rest-api/
│ ├── cron/
│ ├── cpt-taxonomy/
│ └── cli/
└── readme.txt

yaml
Copy code

---

## 🧩 Settings UI

Accessible under:
**AI Core → Settings → AI Builder**

| Setting | Description |
|----------|--------------|
| **Mode** | `Lite`, `Community`, or `BYO` |
| **Builder Endpoint** | API URL (readonly for Community) |
| **Public Key** | Used for verifying signed ZIPs |
| **Rate Limit** | Daily quota (e.g. 3 builds/day per user) |

---

## 🔐 Security Model

1. **Never run code on live WP** — all builds happen in sandbox or remote builder.
2. **Signature Verification** — every ZIP is signed using Ed25519.  
3. **Static Scans** — PHPStan, ESLint, token check for banned functions.  
4. **Allow/Deny Lists** — disallow dangerous dependencies (`shell_exec`, `proc_open`, etc.).
5. **Rollback Safety** — every install creates a restore point.

---

### 📡 REST Endpoints

---

### Create Build  
**Endpoint:** `POST /wp-json/ai-core/v1/build`

**Request Example:**
```json
{
  "name": "FAQ Block",
  "slug": "ai-faq-block",
  "templates": ["block","settings"],
  "prompt": "Create an accessible FAQ accordion with schema markup.",
  "options": {"mode": "Lite"}
}
Response Example:
{
  "jobId": "jb_001",
  "status": "building",
  "preview": {"playgroundUrl": "https://playground.example.com"}
}
Poll Build
Endpoint: GET /wp-json/ai-core/v1/build/{id}

Response Example:

{
  "status": "ready",
  "report": {
    "lint": "ok",
    "dangerousFuncs": [],
    "deps": ["wp-scripts"]
  },
  "artefact": {
    "zip_b64": "<base64-encoded-zip>",
    "signature": "<ed25519-signature>",
    "sha256": "a7b3..."
  }
}
Install Build
Endpoint: POST /wp-json/ai-core/v1/install

PHP Example:

add_action('rest_api_init', function () {
  register_rest_route('ai-core/v1', '/install', [
    'methods' => 'POST',
    'permission_callback' => fn() => current_user_can('install_plugins'),
    'callback' => function($req) {
      $zip = base64_decode($req['zip_b64']);
      $sig = base64_decode($req['signature']);
      $pubKey = base64_decode(get_option('ai_core_builder_pubkey'));

      // verify the signature
      if (!sodium_crypto_sign_verify_detached($sig, $zip, $pubKey)) {
        return new WP_Error('bad_sig', 'Signature check failed', ['status'=>403]);
      }

      $tmp = wp_tempnam('ai-builder.zip');
      file_put_contents($tmp, $zip);
      // unzip safely and activate plugin
      return ['ok'=>true];
    }
  ]);
});

# 🧱 Template Library

| Template | Description |
|-----------|-------------|
| **Block (Gutenberg)** | Creates a basic JS/React block with inspector controls. |
| **Settings Page** | Adds admin menu, Options API, and nonce validation. |
| **CPT + Taxonomy** | Registers a post type with REST support and UI labels. |
| **Shortcode** | Adds a PHP shortcode with sanitised output. |
| **REST API** | Adds secure endpoint with nonce/cap checks. |
| **Scheduler (Cron)** | Adds cron task with safe callback. |
| **CLI Command** | Adds WP-CLI command scaffold. |

---

## ⚙️ Modes Implementation

### Mode: Lite (Default)
- Generates plugin code in the browser (JS).  
- Runs in **WordPress Playground** iframe.  
- Allows “Download ZIP” and “Install” locally.  
- Requires no server or API keys.  

### Mode: Community
- Sends the user’s prompt to **your hosted builder endpoint**.  
- Builder handles full code generation, composer/npm builds, linting, and signing.  
- WP validates the signature before installing.  
- You can add usage limits per user.  

### Mode: BYO (Bring Your Own)
- Advanced users enter their own builder endpoint and public key.  
- Plugin validates via `/health` check and signature test.  
- Everything else works the same as Community mode.  

---

## ✅ Safety Checks

| Check | Description |
|--------|--------------|
| **Path traversal** | Block `../` outside plugin dir. |
| **Symlinks** | Disallow symlinks in ZIP. |
| **Banned functions** | Block `exec`, `system`, `shell_exec`, `passthru`. |
| **Escaping** | Warn if output lacks escaping (`esc_html`, `wp_kses`). |
| **Dependencies** | Must match allow-list. |
| **Linting** | Run PHPStan (level 6) and ESLint. |

If issues appear, show user-friendly warnings like:  
> ⚠️ “Your plugin includes risky functions. Please review or rebuild.”

---

## 🧩 UI Components

| UI Area | Description |
|----------|--------------|
| **Prompt Box** | Free text + optional template picker. |
| **Build Log Panel** | Displays AI generation progress (e.g., “✓ Created settings.php”). |
| **Preview Window** | Embedded Playground or sandbox URL. |
| **Safety Report Panel** | Simple badges (✅ Pass / ⚠️ Warn / ❌ Blocked). |
| **Install Button** | Disabled until all checks pass. |
| **History Table** | Lists user’s previous builds with rebuild & preview actions. |

---

## 💡 Rollout Plan

**Phase 1 – Lite Mode (MVP)**  
- In-browser only  
- Preview + Install from Playground  
- Templates: shortcode, block, settings, CPT  

**Phase 2 – Community Builder**  
- Remote builder endpoint (your hosted API)  
- Adds signing, linting, packaging  

**Phase 3 – BYO Mode**  
- Advanced option for devs/agencies  

---

## 🔐 Roo Implementation Prompts

### 1️⃣ Settings UI
```xml
<task>
In ai-core or ai-core-vibe-builder, create a Settings → “AI Builder” panel:
- Mode selector: Lite, Community, BYO.
- Endpoint + Public Key fields (readonly for Community).
- Persist values to ai_core_settings.
</task>
```

### 2️⃣ Lite Mode Implementation
```xml
<task>
Build a React-based “Create with AI” page.
- Prompt input, Template chips, Build button.
- On Generate: create plugin files in-memory (JS).
- Embed WordPress Playground iframe to preview plugin.
- Allow “Download ZIP” and “Install”.
- POST to /wp-json/ai-core/v1/install-lite for safe install.
</task>
```

### 3️⃣ Community Builder Mode
```xml
<task>
Wire POST /wp-json/ai-core/v1/build to remote Builder Endpoint.
- Send prompt/spec JSON.
- Poll /status for progress and logs.
- When ready, verify Ed25519 signature before install.
- Display lint + safety report.
</task>
```

### 4️⃣ BYO Builder Mode
```xml
<task>
Allow users to input their own builder endpoint + public key.
Add “Test Connection” button that checks /health on endpoint and validates a dummy signed artefact.
</task>
```

### 5️⃣ Templates + Static Checks
```xml
<task>
Include 6 ready templates: shortcode, settings, CPT, REST, cron, CLI.
Add static PHP + JS scans:
- Deny dangerous functions
- Enforce escaping
- Warn on unsafe dependencies
Render results as user-friendly report badges.
</task>
```

---

## 🧾 Summary

| Feature | Included |
|----------|-----------|
| Safe local generation (Lite) | ✅ |
| No Cloudflare account required | ✅ |
| Preview before install | ✅ |
| Static & signature checks | ✅ |
| Optional remote builder (Community/BYO) | ✅ |
| Works as ai-core module | ✅ |

---

**Result:**  
A safe, free, AI-driven **plugin builder** add-on that allows anyone to describe, preview, and install new WordPress plugins — no coding, no accounts, and no setup required.
