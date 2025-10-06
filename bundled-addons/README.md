# AI-Core Bundled Add-ons

This directory contains add-on plugins that are bundled with AI-Core and can be installed directly from the AI-Core Add-ons page.

## Installation Process

When a user clicks "Install Now" on the AI-Core Add-ons page:

1. The add-on is copied from this `bundled-addons` directory to the WordPress `wp-content/plugins/` directory
2. The user can then activate the add-on with one click
3. The add-on automatically uses the API keys configured in AI-Core

## Bundled Add-ons

### AI-Imagen (v1.0.0)
AI-powered image generation plugin with Scene Builder functionality.

**Features:**
- Multi-provider support (OpenAI, Gemini, xAI Grok)
- Scene Builder with text positioning
- 4 workflow types (Just Start, Use Case, Role, Style)
- 36+ prompt templates
- Media library integration
- Statistics tracking

**Directory:** `ai-imagen/`

## For Developers

To add a new bundled add-on:

1. Create a new directory in `bundled-addons/` with your plugin slug
2. Add your plugin files following WordPress plugin structure
3. Update `admin/class-ai-core-addons.php` to include your add-on in the `get_addons()` method
4. Set `'bundled' => true` in the add-on array
5. Specify the `'plugin_file'` path (e.g., 'your-plugin/your-plugin.php')

## Requirements

- Bundled add-ons must follow WordPress plugin standards
- Add-ons should check for AI-Core availability using `function_exists('ai_core')`
- Add-ons should use AI-Core's API keys rather than requiring separate configuration
- Add-ons must be GPL v2 or later compatible

## Notes

- This directory is included in the AI-Core plugin distribution
- Users can install these add-ons without downloading separately
- Add-ons can also be distributed independently on WordPress.org
- The bundled version ensures compatibility with the current AI-Core version

