# AI-Imagen

Professional AI image generation add-on for AI-Core WordPress plugin.

## Description

AI-Imagen is a powerful WordPress plugin add-on that brings professional AI image generation capabilities to your site. Built as an extension for the AI-Core plugin, it provides an intuitive interface for creating stunning images using leading AI providers including OpenAI DALL-E, Google Gemini Imagen, and xAI Grok.

## Features

### Multiple AI Providers
- **OpenAI** - DALL-E 3, DALL-E 2, GPT-Image-1
- **Google Gemini** - Imagen models (gemini-2.5-flash-image variants)
- **xAI Grok** - Grok image generation models

### Workflow-Based Interface
Choose from four intuitive workflows:
1. **Just Start** - Jump straight into creating with a simple prompt
2. **By Use Case** - Select from 9 professional use cases
3. **By Role** - Choose from 8 professional roles
4. **By Style** - Pick from 9 visual styles

### 9 Professional Use Cases
- Marketing & Advertising
- Social Media Content
- Product Photography
- Website Design Elements
- Publishing & Editorial
- Presentation Graphics
- Game Development
- Educational Content
- Print-on-Demand

### 8 Professional Roles
- Marketing Manager
- Social Media Manager
- Small Business Owner
- Graphic Designer
- Content Publisher
- Developer
- Educator
- Event Planner

### 9 Visual Styles
- Photorealistic
- Flat & Minimalist
- Cartoon & Anime
- Digital Painting
- Retro & Vintage
- 3D & CGI
- Hand-drawn
- Brand Layouts
- Transparent Assets

### Advanced Features
- **Prompt Library Integration** - Pre-built templates organised by category
- **AI Prompt Enhancement** - Automatically improve prompts with AI
- **Media Library Integration** - Save images directly to WordPress
- **Usage Statistics** - Track usage by provider, model, use case, and style
- **Generation Limits** - Set daily limits to control API costs
- **Quality Options** - Standard and HD quality
- **Multiple Formats** - PNG, JPEG, WebP
- **Aspect Ratios** - 1:1, 4:3, 16:9, 9:16

## Requirements

- WordPress 5.8 or higher
- PHP 7.4 or higher
- AI-Core plugin (version 1.0.0 or higher)
- At least one configured AI provider (OpenAI, Gemini, or xAI Grok)
- Valid API keys for your chosen provider(s)

## Installation

1. Install and activate the AI-Core plugin
2. Configure at least one AI provider in AI-Core settings
3. Upload the `ai-imagen` folder to `/wp-content/plugins/`
4. Activate AI-Imagen through the WordPress 'Plugins' menu
5. Navigate to AI-Imagen in the admin menu
6. Start generating images!

## File Structure

```
ai-imagen/
├── admin/
│   ├── class-ai-imagen-admin.php      # Admin interface
│   ├── class-ai-imagen-ajax.php       # AJAX handlers
│   └── views/
│       ├── generator-page.php         # Main generator interface
│       ├── history-page.php           # Image history
│       ├── settings-page.php          # Settings interface
│       └── stats-page.php             # Statistics dashboard
├── assets/
│   ├── css/
│   │   ├── admin.css                  # Main admin styles
│   │   └── generator.css              # Generator-specific styles
│   └── js/
│       ├── admin.js                   # Main admin JavaScript
│       ├── generator.js               # Generator functionality
│       └── scene-builder.js           # Scene builder (future feature)
├── includes/
│   ├── class-ai-imagen-generator.php  # Core generation logic
│   ├── class-ai-imagen-settings.php   # Settings management
│   ├── class-ai-imagen-media.php      # Media library integration
│   ├── class-ai-imagen-stats.php      # Statistics tracking
│   └── class-ai-imagen-prompts.php    # Prompt library integration
├── ai-imagen.php                      # Main plugin file
├── readme.txt                         # WordPress.org readme
├── uninstall.php                      # Uninstall script
└── README.md                          # This file
```

## Usage

### Quick Start

1. Navigate to **AI-Imagen** in the WordPress admin menu
2. Choose a workflow (Just Start, Use Case, Role, or Style)
3. Enter your prompt or select a template
4. Adjust generation settings (provider, model, quality, aspect ratio)
5. Click **Generate Image**
6. Preview, download, or save to media library

### Workflow Examples

#### Just Start
Perfect for quick image generation:
```
Prompt: "Professional product photo on white background"
```

#### By Use Case
Select "Social Media Content" for optimised social media images:
```
Use Case: Social Media Content
Prompt: "Instagram post with vibrant colours"
```

#### By Role
Choose "Marketing Manager" for campaign-ready images:
```
Role: Marketing Manager
Prompt: "Campaign banner for summer sale"
```

#### By Style
Pick "Photorealistic" for DSLR-quality images:
```
Style: Photorealistic
Prompt: "Product photography with natural lighting"
```

### Prompt Enhancement

Enable AI prompt enhancement in settings to automatically improve your prompts:
```
Original: "cat"
Enhanced: "Professional photograph of a cat, studio lighting, high resolution, sharp focus, detailed fur texture"
```

### Generation Settings

- **Provider**: Choose from configured providers (OpenAI, Gemini, Grok)
- **Model**: Select specific model (auto-filtered to image-capable models)
- **Quality**: Standard or HD
- **Aspect Ratio**: 1:1, 4:3, 16:9, or 9:16
- **Format**: PNG, JPEG, or WebP (provider-dependent)

### Usage Limits

Set daily generation limits in settings to control API costs:
```
Settings > Daily Generation Limit: 50
```

## API Integration

AI-Imagen integrates with AI-Core's unified API system:

```php
// Example: Generate image programmatically
$generator = AI_Imagen_Generator::get_instance();

$params = array(
    'prompt' => 'Professional product photo',
    'provider' => 'openai',
    'model' => 'dall-e-3',
    'quality' => 'hd',
    'aspect_ratio' => '1:1'
);

$response = $generator->generate_image($params);

if (!is_wp_error($response)) {
    $image_url = $response['data'][0]['url'];
}
```

## Statistics Tracking

Track your usage with comprehensive statistics:

- Total generations
- Usage by provider
- Usage by model
- Usage by use case
- Usage by role
- Usage by style
- Daily/weekly/monthly trends

Export statistics as CSV for analysis.

## WordPress.org Compliance

AI-Imagen follows all WordPress.org plugin guidelines:

- ✅ No external dependencies (except AI-Core)
- ✅ Proper sanitisation and escaping
- ✅ Nonce verification for all AJAX requests
- ✅ Capability checks for all admin actions
- ✅ Internationalisation ready
- ✅ GPL-compatible licence
- ✅ No tracking or analytics
- ✅ Clean uninstall

## Development

### Coding Standards

- WordPress Coding Standards
- Object-Oriented Programming (OOP)
- Singleton pattern for main classes
- British English spellings
- Comprehensive inline documentation

### Hooks and Filters

```php
// Filter generated image before saving
add_filter('ai_imagen_before_save', function($image_url, $metadata) {
    // Modify image or metadata
    return $image_url;
}, 10, 2);

// Action after image generation
add_action('ai_imagen_after_generate', function($image_url, $params) {
    // Custom logic after generation
}, 10, 2);
```

## Support

For support, please:
1. Check the FAQ in readme.txt
2. Visit the WordPress.org support forum
3. Contact Opace Digital Agency

## Privacy

AI-Imagen sends prompts to your configured AI provider's API. Please review:
- [OpenAI Privacy Policy](https://openai.com/privacy)
- [Google Privacy Policy](https://policies.google.com/privacy)
- [xAI Privacy Policy](https://x.ai/privacy)

No data is sent to any third parties other than your configured AI provider.

## Licence

GPLv2 or later - https://www.gnu.org/licenses/gpl-2.0.html

## Credits

Developed by **Opace Digital Agency**

## Changelog

### 1.0.0 (2025-01-XX)
- Initial release
- Support for OpenAI, Gemini, and xAI Grok
- 9 use cases, 8 roles, 9 styles
- Prompt library integration
- AI prompt enhancement
- Media library integration
- Usage statistics
- Generation limits
- Multiple quality and format options

