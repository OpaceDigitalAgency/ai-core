# Enhanced API Usage Statistics - Version 0.2.5

## Overview
Comprehensive enhancement of the API usage statistics system to provide detailed tracking, cost calculations, and professional display of usage data across all AI providers.

## What Was Missing
The previous statistics system (v0.2.4) only tracked:
- Total requests per model
- Total tokens (not separated)
- Errors
- Last used timestamp

**Critical Missing Features:**
- ❌ No separation of input vs output tokens
- ❌ No cost calculations
- ❌ No pricing data for any models
- ❌ No provider-level statistics
- ❌ Limited display information

## What's New in v0.2.5

### 1. Comprehensive Pricing Database
**New File:** `includes/class-ai-core-pricing.php`

- **Complete pricing data** for all models from all 4 providers (October 2025 pricing)
- **OpenAI:** GPT-4o, GPT-4.5, o1, o3, DALL-E models
- **Anthropic:** Claude Sonnet 4.5, Opus 4.1, Haiku 4, all Claude 3.x models
- **Google Gemini:** Gemini 2.5 Pro/Flash/Flash-Lite, Gemini 2.0, Gemini 1.5, Imagen models
- **xAI Grok:** Grok 4, Grok 3, Grok 2, Grok Code, Grok Image models

**Features:**
- Per-million-token pricing for text models
- Per-image pricing for image generation models
- Long context pricing support (for models with different rates above threshold)
- Automatic provider detection from model names
- Cost calculation method with input/output token separation

### 2. Enhanced Statistics Tracking
**Updated File:** `includes/class-ai-core-api.php`

**New Tracking Fields:**
- `input_tokens` - Separate input token count
- `output_tokens` - Separate output token count
- `total_tokens` - Combined token count
- `total_cost` - Calculated cost in USD
- `provider` - Provider identification
- `requests` - Request count (existing)
- `errors` - Error count (existing)
- `last_used` - Timestamp (existing)

**Improvements:**
- Automatic cost calculation using pricing database
- Support for different token field names across providers
- Provider detection and storage
- Backward compatibility with old stats format

### 3. Enhanced Statistics Display
**Updated File:** `includes/class-ai-core-stats.php`

**New Display Sections:**

#### Total Usage Summary (8 metrics)
- Total Requests
- Input Tokens
- Output Tokens
- Total Tokens
- **Total Cost (USD)**
- Errors
- Models Used
- Providers

#### Usage by Provider Table
- Provider name
- Requests
- Input Tokens
- Output Tokens
- Total Tokens
- **Cost**
- Number of Models

#### Usage by Model Table (Enhanced)
- Model name
- **Provider**
- Requests
- **Input Tokens**
- **Output Tokens**
- Total Tokens
- **Cost**
- Errors
- Last Used

**New Methods:**
- `get_provider_stats()` - Aggregate statistics by provider
- `detect_provider()` - Identify provider from model name

### 4. Improved CSS Styling
**Updated File:** `assets/css/admin.css`

- Professional table styling for statistics
- Highlighted cost columns
- Responsive design for mobile devices
- Consistent colour scheme (#0068b3 blue)
- Better spacing and readability

## Pricing Data Sources

All pricing data was researched from official sources (October 2025):

1. **OpenAI:** https://platform.openai.com/docs/pricing
2. **Anthropic:** https://www.anthropic.com/claude/pricing
3. **Google Gemini:** https://ai.google.dev/gemini-api/docs/pricing
4. **xAI Grok:** https://docs.x.ai/docs/models

## Technical Implementation

### Cost Calculation Formula
```php
// For text models (per million tokens)
$cost = ($input_tokens / 1000000 * $input_price) + 
        ($output_tokens / 1000000 * $output_price);

// For image models (per image)
$cost = $per_image_price * $number_of_images;

// Long context pricing (if applicable)
if ($input_tokens > $threshold) {
    $input_price = $input_long_price;
    $output_price = $output_long_price;
}
```

### Provider Detection
Automatic detection based on model name prefixes:
- `gpt-`, `o1-`, `o3-`, `dall-e-` → OpenAI
- `claude-` → Anthropic
- `gemini-`, `imagen-` → Gemini
- `grok-` → Grok

### Backward Compatibility
- Old statistics with only `tokens` field are still displayed
- Graceful handling of missing pricing data
- No data loss during upgrade

## Files Modified

1. **ai-core.php** - Version bump to 0.2.5, load pricing class
2. **includes/class-ai-core-pricing.php** - NEW: Complete pricing database
3. **includes/class-ai-core-api.php** - Enhanced tracking with costs
4. **includes/class-ai-core-stats.php** - Enhanced display and provider stats
5. **assets/css/admin.css** - Improved statistics styling

## Benefits

### For Users
- ✅ **See exactly how much each model costs**
- ✅ Track spending across all providers
- ✅ Identify most expensive models/providers
- ✅ Monitor token usage patterns
- ✅ Make informed decisions about model selection

### For Developers
- ✅ Accurate cost tracking for billing
- ✅ Detailed usage analytics
- ✅ Provider comparison data
- ✅ Professional statistics display

## Usage

### Viewing Statistics
Navigate to: **AI-Core > Statistics**

The page now displays:
1. **Total Usage Summary** - Overview of all usage
2. **Usage by Provider** - Breakdown by AI provider
3. **Usage by Model** - Detailed per-model statistics

### Cost Information
- All costs displayed in USD
- Formatted to 4 decimal places for accuracy
- Automatically calculated based on latest pricing
- Updated in real-time as API calls are made

## Future Enhancements

Potential improvements for future versions:
- Date range filtering
- Export statistics to CSV
- Cost alerts/budgets
- Usage graphs and charts
- Monthly/weekly summaries
- Cost projections

## Testing Recommendations

1. **Enable Statistics:** Settings > Enable Usage Statistics
2. **Make Test Requests:** Use different models from different providers
3. **View Statistics:** Check AI-Core > Statistics page
4. **Verify Costs:** Compare calculated costs with provider pricing pages
5. **Test All Providers:** OpenAI, Anthropic, Gemini, Grok

## Notes

- Pricing data is current as of October 2025
- Prices may change - update `class-ai-core-pricing.php` as needed
- Image generation costs are per-image, not per-token
- Some models have long-context pricing (different rates above threshold)
- Statistics are stored in WordPress options table (`ai_core_stats`)

## Version History

- **v0.2.4** - Basic statistics (requests, tokens, errors)
- **v0.2.5** - Enhanced statistics with costs, provider breakdown, detailed tracking

---

**Developed by:** Opace Digital Agency  
**Date:** October 2025  
**Plugin:** AI-Core - Universal AI Integration Hub

