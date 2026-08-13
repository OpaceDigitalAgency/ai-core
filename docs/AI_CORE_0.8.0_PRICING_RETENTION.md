# AI-Core 0.8.0 — pricing and uninstall retention

Date: 2026-08-13

## Scope

- Replace false `$0.0000` totals for unknown model prices with sourced estimates or an explicit unavailable state.
- Refresh discovered-model pricing without shipping a permanently stale hardcoded catalogue.
- Restore a complete user choice to retain or remove all data owned by AI-Core on deletion.

## Implementation

- Exact provider/model lookups use the public LiteLLM model catalogue, cached for 12 hours. A failed lookup is cached for 15 minutes.
- Only provider and model identifiers are sent. API keys, prompts, content and usage are not sent.
- Remote values are validated as finite, non-negative numbers. The bundled catalogue is the offline fallback.
- Statistics are labelled published-rate estimates. Unknown prices show `Cost unavailable`; they are never converted to zero.
- Legacy token/image counters are recalculated when a trustworthy price becomes available.
- The retention setting lists encrypted credentials, provider/model settings, prompt library tables, usage/cost statistics, version/encryption metadata, and model/pricing transients.
- Clean uninstall deletes those AI-Core items only. Data owned by AI-Scribe or other plugins is outside its scope.

## Evidence

- Isolated WordPress `http://localhost:8890`, not the owner's live Chrome session.
- Remote catalogue exact lookups:
  - `openai/gpt-5.2`: input 1.75 and output 14 USD per million tokens.
  - `anthropic/claude-sonnet-4-5`: input 3 and output 15 USD per million tokens.
  - `gemini/gemini-3.6-flash`: input 1.5 and output 7.5 USD per million tokens.
  - `gemini/gemini-3.1-flash-image`: 0.045 USD per output image.
  - deliberately unknown Gemini model: `null`, rendered as unavailable.
- Legacy Gemini 3.6 usage (1,000 input, 500 output) backfilled to `$0.00525`, status `estimated`, source `litellm`.
- Retain uninstall fixture preserved settings, statistics, encryption metadata, pricing transient and prompt table.
- Remove uninstall fixture deleted settings, statistics, version, encryption metadata, pricing transient and both prompt tables.
- Browser fixture at 1440 px passed live retain/remove summary changes, both saved round-trips, no overflow, pricing refresh action, estimated-cost label and unavailable-model disclosure.
- All packaged PHP and JavaScript syntax checks passed; `git diff --check` passed.
- AI-Scribe PHP regression: 440 passed, 0 failed.

## Accuracy boundary

These are published list-price estimates, not provider invoices. Provider free tiers, caching, batch pricing, negotiated rates and future catalogue omissions can differ. No provider model-list API guarantees billing data for every future model, so AI-Core fails closed when a verified price is unavailable.
