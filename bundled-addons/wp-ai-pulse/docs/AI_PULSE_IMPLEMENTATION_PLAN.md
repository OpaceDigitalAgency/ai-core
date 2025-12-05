# AI-Pulse WordPress Plugin - Implementation Plan

**Project:** AI-Pulse - Real-Time Service Intelligence for WordPress
**Based On:** TrendPulse React Application (https://github.com/OpaceDigitalAgency/TrendPulse)
**Version:** 1.0.0
**Date Created:** 2025-12-05
**Last Updated:** 2025-12-05
**Purpose:** Replace AI-Stats with a superior, production-ready trend analysis system

---

## Executive Summary

> **📋 IMPORTANT:** See `IMPLEMENTATION_APPROACH_COMPARISON.md` for detailed analysis of three implementation approaches (Pure PHP, React Admin, Headless SSG). This document describes the **Pure PHP WordPress Plugin** approach.

### What AI-Pulse Does

AI-Pulse transforms the TrendPulse React application into a WordPress plugin that generates **crawlable, static HTML content** for service pages. It provides real-time market intelligence, FAQs, statistics, and strategic insights using Google's Gemini API with Search Grounding.

**Key Features:**
- ✅ **Clean, Modern UX** - Matches TrendPulse's intuitive design (slate/blue colour scheme, card-based layout)
- ✅ **Real-Time Testing** - Admin interface with live prompt debugger and instant preview
- ✅ **Scheduled Background Generation** - Automated updates during off-peak hours
- ✅ **Flexible Scheduling** - Daily, 2-day, 3-day, or weekly intervals with gradual rollout
- ✅ **Editable Prompts** - All 11 mode prompts fully customisable in admin
- ✅ **Smart Capitalisation** - LLM automatically formats keywords (e.g., "seo" → "SEO")
- ✅ **Configurable Shortcodes** - Full control over display, timeframe, and update intervals

### Key Differences from AI-Stats

| Feature | AI-Stats (Current) | AI-Pulse (New) |
|---------|-------------------|----------------|
| **Performance** | 3 minutes to load dashboard | Pre-generated HTML loads instantly |
| **Data Quality** | Inconsistent scraping from multiple sources | Google Search Grounding (verified sources) |
| **Reliability** | Complex pipeline with frequent failures | Single API call with structured JSON |
| **SEO Value** | Dynamic content (not always crawlable) | Static HTML (fully crawlable) |
| **Maintenance** | 10+ data sources to maintain | Single Google API integration |
| **User Experience** | Slow, unreliable | Fast, consistent |
| **Admin UX** | Complex, confusing | Simple, intuitive (TrendPulse-inspired) |
| **Testing** | No real-time testing | Live debugger with instant preview |
| **Scheduling** | Fixed intervals | Flexible with gradual rollout |

### Core Principle

**Generate content during off-peak hours (early morning) → Store as static HTML → Serve instantly on page load**

### Design Philosophy (TrendPulse-Inspired)

**Colour Palette:**
- Primary: Blue (#2563eb) for actions and highlights
- Background: Slate-50 (#f8fafc) for main areas
- Cards: White with subtle shadows and slate-200 borders
- Text: Slate-900 for headings, Slate-500 for secondary
- Accents: Green for success/authority, Orange for warnings

**Layout Principles:**
- Clean, spacious card-based design
- Generous padding and rounded corners (rounded-xl, rounded-2xl)
- Clear visual hierarchy with bold headings
- Intuitive button placement (blue primary actions)
- Responsive grid layouts (1-column mobile, 2-3 columns desktop)

**Typography:**
- System font stack for performance
- Bold headings (font-bold, tracking-tight)
- Clear labels with uppercase tracking for buttons
- Readable body text (text-sm to text-base)

---

## Architecture Overview

### System Architecture Diagram

```
┌─────────────────────────────────────────────────────────────────┐
│                        WordPress Frontend                        │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │  Service Page with Shortcode: [ai_pulse keyword="SEO"]    │ │
│  └────────────────────────────────────────────────────────────┘ │
│                              ↓                                   │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │         AI_Pulse_Shortcode::render_shortcode()            │ │
│  │  • Fetch pre-generated HTML from database                 │ │
│  │  • Apply styling based on 'style' attribute               │ │
│  │  • Return HTML (< 100ms)                                  │ │
│  └────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────┘
                              ↑
                              │ Reads from
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│                    WordPress Database (MySQL)                    │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │  wp_ai_pulse_content                                       │ │
│  │  • id, keyword, mode, period                               │ │
│  │  • content_html (pre-generated)                            │ │
│  │  • content_json (raw data)                                 │ │
│  │  • sources_json (citations)                                │ │
│  │  • tokens, cost, generated_at                              │ │
│  └────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────┘
                              ↑
                              │ Written by
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│                    WP Cron (Scheduled Tasks)                     │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │  ai_pulse_daily_update (runs at 3am)                       │ │
│  │  ↓                                                          │ │
│  │  AI_Pulse_Scheduler::run_scheduled_generation()            │ │
│  │  • Fetch active keywords from settings                     │ │
│  │  • For each keyword: call Generator                        │ │
│  │  • Log results, send notifications                         │ │
│  └────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│                   AI_Pulse_Generator (Core Logic)                │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │  generate_content($keyword, $mode, $period)                │ │
│  │  1. Build system instruction + user prompt                 │ │
│  │  2. Call AI-Core API                                       │ │
│  │  3. Parse JSON response                                    │ │
│  │  4. Validate structure                                     │ │
│  │  5. Convert JSON → HTML                                    │ │
│  │  6. Store in database                                      │ │
│  │  7. Track usage/costs                                      │ │
│  └────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│                      AI-Core Plugin (Parent)                     │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │  AI_Core_API::send_text_request()                          │ │
│  │  • Manages API keys for all providers                      │ │
│  │  • Routes request to correct provider                      │ │
│  │  • Tracks usage statistics                                 │ │
│  │  • Returns normalised response                             │ │
│  └────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────────┐
│                    Google Gemini API (External)                  │
│  ┌────────────────────────────────────────────────────────────┐ │
│  │  Model: gemini-3-pro-preview                               │ │
│  │  Feature: Google Search Grounding                          │ │
│  │  ↓                                                          │ │
│  │  1. Receives prompt with search tool enabled               │ │
│  │  2. Searches web for latest data (real-time)               │ │
│  │  3. Grounds response in verified sources                   │ │
│  │  4. Returns JSON + source citations                        │ │
│  └────────────────────────────────────────────────────────────┘ │
└─────────────────────────────────────────────────────────────────┘
```

### Data Flow

**Content Generation Flow:**
```
Admin/Cron → Generator → AI-Core → Gemini API → JSON Response
                ↓
         Parse & Validate
                ↓
         JSON → HTML Conversion
                ↓
         Store in Database
                ↓
         Log Usage & Costs
```

**Content Rendering Flow:**
```
Page Load → Shortcode → Database Query → Return HTML → Display
(< 100ms total)
```

### Plugin Structure (WordPress MVC Pattern)

```
bundled-addons/ai-pulse/
├── ai-pulse.php                          # Main plugin file (singleton pattern)
├── includes/
│   ├── class-ai-pulse-settings.php       # Settings management
│   ├── class-ai-pulse-database.php       # Database operations
│   ├── class-ai-pulse-generator.php      # Content generation via Gemini
│   ├── class-ai-pulse-scheduler.php      # WP Cron scheduling
│   ├── class-ai-pulse-shortcode.php      # Shortcode rendering
│   ├── class-ai-pulse-modes.php          # Analysis mode definitions
│   ├── class-ai-pulse-validator.php      # JSON validation
│   ├── class-ai-pulse-logger.php         # Error logging
│   └── class-ai-pulse-cost-tracker.php   # Usage/cost tracking
├── admin/
│   ├── class-ai-pulse-admin.php          # Admin interface
│   ├── class-ai-pulse-ajax.php           # AJAX handlers
│   └── views/
│       ├── settings-page.php             # Main settings UI (tabbed)
│       ├── tab-test-interface.php        # Test Interface tab
│       ├── tab-scheduled-generation.php  # Scheduled Generation tab
│       ├── tab-keywords.php              # Service Keywords tab
│       ├── tab-prompts.php               # Prompt Templates tab
│       ├── tab-library.php               # Content Library tab
│       └── tab-stats.php                 # Usage Statistics tab
├── assets/
│   ├── css/
│   │   ├── admin.css                     # Admin styles (TrendPulse-inspired)
│   │   └── frontend.css                  # Frontend styles (TrendPulse-inspired)
│   └── js/
│       ├── admin.js                      # Admin JavaScript (AJAX, live preview)
│       └── frontend.js                   # Frontend JavaScript (minimal)
├── languages/
│   └── ai-pulse.pot                      # Translation template
└── uninstall.php                         # Cleanup on uninstall
```

**Key Files Explained:**

1. **`ai-pulse.php`** - Main plugin file with singleton pattern, dependency checking, and initialization
2. **`class-ai-pulse-generator.php`** - Core logic for calling AI-Core API and generating content
3. **`class-ai-pulse-scheduler.php`** - WP Cron management with gradual rollout logic
4. **`class-ai-pulse-validator.php`** - Validates JSON structure for each mode
5. **`class-ai-pulse-logger.php`** - Centralized error logging and debugging
6. **`class-ai-pulse-cost-tracker.php`** - Tracks token usage and costs per keyword/mode

### Database Schema

**Table: `wp_ai_pulse_content`**
```sql
CREATE TABLE wp_ai_pulse_content (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  keyword VARCHAR(255) NOT NULL,
  mode VARCHAR(50) NOT NULL,
  period VARCHAR(20) NOT NULL,
  content_html LONGTEXT NOT NULL,
  content_json LONGTEXT NOT NULL,
  sources_json TEXT,
  date_range VARCHAR(100),
  input_tokens INT UNSIGNED DEFAULT 0,
  output_tokens INT UNSIGNED DEFAULT 0,
  cost_usd DECIMAL(10,6) DEFAULT 0,
  generated_at DATETIME NOT NULL,
  is_active TINYINT(1) DEFAULT 1,
  INDEX idx_keyword_mode (keyword, mode),
  INDEX idx_active (is_active),
  INDEX idx_generated (generated_at)
);
```

**Table: `wp_ai_pulse_settings`**
```sql
CREATE TABLE wp_ai_pulse_settings (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  setting_key VARCHAR(100) UNIQUE NOT NULL,
  setting_value LONGTEXT,
  updated_at DATETIME NOT NULL
);
```

---

## Integration with AI-Core

### Provider Configuration

**Default Provider:** Google Gemini (ONLY)
- Model: `gemini-3-pro-preview` (with Google Search Grounding)
- Why Google only: Search Grounding is exclusive to Gemini
- Fallback: None (this feature requires Gemini)

### Dependency Checking (Critical)

**Following AI-Stats Pattern:**

```php
/**
 * Check if AI-Core is active and configured
 * This MUST run before any other plugin functionality
 */
private function check_dependencies() {
    // Check if AI-Core function exists
    if (!function_exists('ai_core')) {
        add_action('admin_notices', array($this, 'show_dependency_notice'));
        add_action('admin_init', array($this, 'deactivate_plugin'));
        return;
    }

    // Get AI-Core instance
    $this->ai_core = ai_core();

    // Check if AI-Core is configured
    if (!$this->ai_core->is_configured()) {
        add_action('admin_notices', array($this, 'show_configuration_notice'));
    }

    // Check if Gemini provider is configured
    $providers = $this->ai_core->get_configured_providers();
    if (!in_array('gemini', $providers)) {
        add_action('admin_notices', array($this, 'show_gemini_notice'));
    }
}

/**
 * Show dependency notice if AI-Core is not active
 */
public function show_dependency_notice() {
    ?>
    <div class="notice notice-error">
        <p>
            <strong>AI-Pulse:</strong> This plugin requires the AI-Core plugin to be installed and activated.
            <a href="<?php echo admin_url('plugins.php'); ?>">Manage Plugins</a>
        </p>
    </div>
    <?php
}

/**
 * Show configuration notice if Gemini is not configured
 */
public function show_gemini_notice() {
    ?>
    <div class="notice notice-warning">
        <p>
            <strong>AI-Pulse:</strong> Please configure your Google Gemini API key in AI-Core settings.
            <a href="<?php echo admin_url('admin.php?page=ai-core-settings'); ?>">Configure AI-Core</a>
        </p>
    </div>
    <?php
}

/**
 * Deactivate plugin if dependencies not met
 */
public function deactivate_plugin() {
    if (!function_exists('ai_core')) {
        deactivate_plugins(plugin_basename(__FILE__));
        if (isset($_GET['activate'])) {
            unset($_GET['activate']);
        }
    }
}
```

### API Integration Pattern

```php
// Example: Generate content using AI-Core
$ai_core = ai_core();

if (!$ai_core->is_configured()) {
    return new WP_Error('not_configured', 'AI-Core not configured');
}

// Check if Gemini is configured
$providers = $ai_core->get_configured_providers();
if (!in_array('gemini', $providers)) {
    return new WP_Error('gemini_required', 'Google Gemini API key required');
}

// Prepare request
$messages = array(
    array('role' => 'system', 'content' => $system_instruction),
    array('role' => 'user', 'content' => $prompt)
);

$options = array(
    'temperature' => 0.3,
    'tools' => array(array('googleSearch' => array()))  // Enable Search Grounding
);

$usage_context = array(
    'tool' => 'ai-pulse',
    'mode' => $mode,
    'keyword' => $keyword
);

// Make API call
$response = $ai_core->send_text_request(
    'gemini-3-pro-preview',
    $messages,
    $options,
    $usage_context
);
```

### Plugin Header (WordPress.org Compliance)

```php
<?php
/**
 * Plugin Name: AI-Pulse - Real-Time Service Intelligence
 * Plugin URI: https://opace.agency/ai-pulse
 * Description: Generate crawlable, SEO-optimised market intelligence content using Google Gemini with Search Grounding. Provides 11 analysis modes including trends, FAQs, statistics, and strategic insights. Requires AI-Core plugin.
 * Version: 1.0.0
 * Author: Opace Digital Agency
 * Author URI: https://opace.agency
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ai-pulse
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.8.1
 * Requires PHP: 7.4
 * Requires Plugins: ai-core
 * Network: false
 * Tags: ai, seo, content, trends, market intelligence, gemini
 *
 * @package AI_Pulse
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('AI_PULSE_VERSION', '1.0.0');
define('AI_PULSE_PLUGIN_FILE', __FILE__);
define('AI_PULSE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AI_PULSE_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AI_PULSE_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main AI_Pulse Class (Singleton Pattern)
 */
class AI_Pulse {

    /**
     * Plugin instance
     * @var AI_Pulse
     */
    private static $instance = null;

    /**
     * AI-Core API instance
     * @var AI_Core_API
     */
    private $ai_core = null;

    /**
     * Get plugin instance (singleton)
     * @return AI_Pulse
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Constructor (private for singleton)
     */
    private function __construct() {
        $this->check_dependencies();
        $this->load_dependencies();
        $this->init_hooks();
    }

    /**
     * Check if AI-Core is active and configured
     */
    private function check_dependencies() {
        if (!function_exists('ai_core')) {
            add_action('admin_notices', array($this, 'show_dependency_notice'));
            add_action('admin_init', array($this, 'deactivate_plugin'));
            return;
        }

        $this->ai_core = ai_core();

        if (!$this->ai_core->is_configured()) {
            add_action('admin_notices', array($this, 'show_configuration_notice'));
        }

        // Check Gemini provider
        $providers = $this->ai_core->get_configured_providers();
        if (!in_array('gemini', $providers)) {
            add_action('admin_notices', array($this, 'show_gemini_notice'));
        }
    }

    /**
     * Load required files
     */
    private function load_dependencies() {
        // Core classes
        require_once AI_PULSE_PLUGIN_DIR . 'includes/class-ai-pulse-settings.php';
        require_once AI_PULSE_PLUGIN_DIR . 'includes/class-ai-pulse-database.php';
        require_once AI_PULSE_PLUGIN_DIR . 'includes/class-ai-pulse-generator.php';
        require_once AI_PULSE_PLUGIN_DIR . 'includes/class-ai-pulse-scheduler.php';
        require_once AI_PULSE_PLUGIN_DIR . 'includes/class-ai-pulse-shortcode.php';
        require_once AI_PULSE_PLUGIN_DIR . 'includes/class-ai-pulse-modes.php';
        require_once AI_PULSE_PLUGIN_DIR . 'includes/class-ai-pulse-validator.php';
        require_once AI_PULSE_PLUGIN_DIR . 'includes/class-ai-pulse-logger.php';
        require_once AI_PULSE_PLUGIN_DIR . 'includes/class-ai-pulse-cost-tracker.php';

        // Admin classes (only load in admin)
        if (is_admin()) {
            require_once AI_PULSE_PLUGIN_DIR . 'admin/class-ai-pulse-admin.php';
            require_once AI_PULSE_PLUGIN_DIR . 'admin/class-ai-pulse-ajax.php';
        }
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Activation/deactivation hooks
        register_activation_hook(AI_PULSE_PLUGIN_FILE, array($this, 'activate'));
        register_deactivation_hook(AI_PULSE_PLUGIN_FILE, array($this, 'deactivate'));

        // Initialize components
        add_action('plugins_loaded', array($this, 'init'));

        // Enqueue assets
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));

        // Register shortcode
        add_shortcode('ai_pulse', array('AI_Pulse_Shortcode', 'render'));
    }

    /**
     * Initialize plugin components
     */
    public function init() {
        // Load text domain for translations
        load_plugin_textdomain('ai-pulse', false, dirname(AI_PULSE_PLUGIN_BASENAME) . '/languages');

        // Initialize database
        AI_Pulse_Database::init();

        // Initialize scheduler
        AI_Pulse_Scheduler::init();

        // Initialize admin interface (if in admin)
        if (is_admin()) {
            AI_Pulse_Admin::init();
        }
    }

    /**
     * Plugin activation
     */
    public function activate() {
        // Create database tables
        AI_Pulse_Database::create_tables();

        // Set default settings
        AI_Pulse_Settings::set_defaults();

        // Schedule cron
        AI_Pulse_Scheduler::schedule_cron();

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Unschedule cron
        AI_Pulse_Scheduler::unschedule_cron();

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Get AI-Core instance
     * @return AI_Core_API|null
     */
    public function get_ai_core() {
        return $this->ai_core;
    }
}

/**
 * Initialize plugin
 */
function ai_pulse() {
    return AI_Pulse::get_instance();
}

// Start the plugin
ai_pulse();
```

---

## Analysis Modes (11 Total)

Based on TrendPulse's `AnalysisMode` enum:

1. **SUMMARY** - General trend analysis (5 rising themes)
2. **FAQS** - Common buyer questions with answers
3. **STATS** - Verified market statistics with citations
4. **FORECAST** - Seasonality and demand windows
5. **GAPS** - Opportunity gaps in the market
6. **LOCAL** - Regional trends (Birmingham/West Midlands focus)
7. **WINS** - Anonymised micro-case studies
8. **GLOSSARY** - Trending terminology definitions
9. **PLATFORMS** - Emerging search platforms (AI search, social)
10. **PULSE** - B2B buyer intent signals
11. **EXPLORER** - Interactive trend themes

**Special Mode:**
- **ALL** - Mega Dashboard (generates all 11 modes in one API call)

---

## Admin Settings Interface

**Design Inspiration:** TrendPulse's clean, card-based layout with blue/slate colour scheme

### Settings Page Layout (TrendPulse-Style)

**Header Section:**
```
┌─────────────────────────────────────────────────────────────┐
│  [Sparkles Icon] AI-Pulse Settings                          │
│  Real-Time Service Intelligence for WordPress               │
│                                                              │
│  [Test Interface] [Content Library] [Usage Stats] [Settings]│
└─────────────────────────────────────────────────────────────┘
```

### Tab 1: Test Interface (Primary Tab - TrendPulse-Inspired)

**Purpose:** Real-time testing and debugging (matches TrendPulse's main interface)

**Layout:**
```
┌─────────────────────────────────────────────────────────────┐
│  Real-Time Service Intelligence                             │
│  Test content generation with live preview                  │
│                                                              │
│  ┌─────────────────────────────────────────────────────┐   │
│  │ [Search Icon] Enter keyword (e.g., SEO)...          │   │
│  └─────────────────────────────────────────────────────┘   │
│                                                              │
│  [DAILY] [WEEKLY] [MONTHLY]  [Mode Dropdown ▼]  [Go ▶]     │
│                                                              │
│  EST. COST: $0.0115 (1.7k tokens)    [✓ Citations Active]  │
│                                                              │
│  ┌─────────────────────────────────────────────────────┐   │
│  │ PROMPT PREVIEW (Editable)                           │   │
│  │ ┌─────────────────────────────────────────────────┐ │   │
│  │ │ TARGET KEYWORD: "SEO"                           │ │   │
│  │ │ STRICT DATE RANGE: 28 Nov 2025 to 5 Dec 2025   │ │   │
│  │ │ TASK: Identify top 5 rising SEO-related...     │ │   │
│  │ └─────────────────────────────────────────────────┘ │   │
│  │                                                      │   │
│  │ LIVE LOGIC                                           │   │
│  │ ┌─────────────────────────────────────────────────┐ │   │
│  │ │ System Instruction: You are a Senior...        │ │   │
│  │ │ Temperature: 0.3                                │ │   │
│  │ │ Tools: Google Search Grounding                  │ │   │
│  │ └─────────────────────────────────────────────────┘ │   │
│  └─────────────────────────────────────────────────────┘   │
│                                                              │
│  ┌─────────────────────────────────────────────────────┐   │
│  │ ● LIVE DATA: 28 Nov 2025 to 5 Dec 2025             │   │
│  │                                                      │   │
│  │ Weekly Summary: SEO                                  │   │
│  │ ⚡ LIVE AUTHORITY DATA                              │   │
│  │                                                      │   │
│  │ [Generated content preview with styling...]         │   │
│  │                                                      │   │
│  │ VERIFIED SOURCES                                     │   │
│  │ • bbc.co.uk - Article Title                         │   │
│  │ • searchengineland.com - Article Title              │   │
│  │                                                      │   │
│  │ ACTUAL BILLED: $0.0167 (3.3k tokens) ✓ SCHEMA READY│   │
│  └─────────────────────────────────────────────────────┘   │
└─────────────────────────────────────────────────────────────┘
```

**Features:**
- ✅ Identical to TrendPulse interface (keyword input, period selector, mode dropdown)
- ✅ Live cost estimation (updates as you type)
- ✅ Editable prompt preview (dark code block with syntax highlighting)
- ✅ Live logic display (system instruction, temperature, tools)
- ✅ Instant preview of generated content with styling
- ✅ Source citations display
- ✅ Actual token usage and cost after generation

### Tab 2: Scheduled Generation Settings

**Purpose:** Configure automated background updates

**Layout:**
```
┌─────────────────────────────────────────────────────────────┐
│  Scheduled Generation                                        │
│  Configure automated content updates                         │
│                                                              │
│  ┌─────────────────────────────────────────────────────┐   │
│  │ Update Schedule                                      │   │
│  │                                                      │   │
│  │ ○ Manual Only (no automatic updates)                │   │
│  │ ● Daily at [03:00] (recommended)                    │   │
│  │ ○ Every [2] days at [03:00]                         │   │
│  │ ○ Every [3] days at [03:00]                         │   │
│  │ ○ Weekly on [Sunday ▼] at [03:00]                   │   │
│  │                                                      │   │
│  │ ☑ Enable gradual rollout (prevent rate limiting)    │   │
│  │   Spread updates over [2] hours                      │   │
│  │   (Updates will start at 03:00 and complete by 05:00)│   │
│  └─────────────────────────────────────────────────────┘   │
│                                                              │
│  ┌─────────────────────────────────────────────────────┐   │
│  │ Default Settings                                     │   │
│  │                                                      │   │
│  │ Time Period: ○ Daily  ● Weekly  ○ Monthly           │   │
│  │ Location Focus: [Birmingham, West Midlands, UK]     │   │
│  │ Cache Duration: [24] hours                           │   │
│  └─────────────────────────────────────────────────────┘   │
│                                                              │
│  ┌─────────────────────────────────────────────────────┐   │
│  │ Rate Limiting & Performance                          │   │
│  │                                                      │   │
│  │ Max concurrent generations: [3]                      │   │
│  │ Delay between requests: [2] seconds                  │   │
│  │ Max retries on failure: [3]                          │   │
│  │                                                      │   │
│  │ ☑ Pause on error (stop batch if 3+ failures)        │   │
│  │ ☑ Email notifications on completion/errors          │   │
│  └─────────────────────────────────────────────────────┘   │
│                                                              │
│  [Save Settings]                                             │
└─────────────────────────────────────────────────────────────┘
```

**Features:**
- ✅ Flexible scheduling: Daily, 2-day, 3-day, or weekly intervals
- ✅ Gradual rollout option (spreads updates over time to avoid rate limits)
- ✅ Configurable start time (default 03:00)
- ✅ Rate limiting controls (concurrent requests, delays, retries)
- ✅ Error handling options (pause on error, email notifications)

### Tab 3: Service Keywords & Modes

**Purpose:** Manage keywords and configure which modes to generate

**Layout:**
```
┌─────────────────────────────────────────────────────────────┐
│  Service Keywords                                            │
│  Manage keywords for content generation                      │
│                                                              │
│  [+ Add Keyword]                                             │
│                                                              │
│  ┌─────────────────────────────────────────────────────┐   │
│  │ Keyword      │ Modes │ Interval │ Last Gen │ Actions│   │
│  ├─────────────────────────────────────────────────────┤   │
│  │ SEO          │ 11/11 │ Daily    │ 2 hrs ago│ [Edit] │   │
│  │ Web Design   │ 5/11  │ Weekly   │ 1 day ago│ [Edit] │   │
│  │ PPC          │ 3/11  │ 3 days   │ Never    │ [Edit] │   │
│  └─────────────────────────────────────────────────────┘   │
│                                                              │
│  Bulk Actions: [Generate Now] [Delete Selected]             │
└─────────────────────────────────────────────────────────────┘

Edit Keyword: SEO
┌─────────────────────────────────────────────────────────────┐
│  Keyword: [SEO]                                              │
│                                                              │
│  Active Modes (select which modes to generate):             │
│  ☑ Summary (General Trends)                                 │
│  ☑ FAQs (Common Questions)                                  │
│  ☑ Stats (Verified Metrics)                                 │
│  ☑ Forecast (Seasonality)                                   │
│  ☐ Gaps (Opportunity)                                       │
│  ☑ Local (Geo Pulse)                                        │
│  ☐ Wins (Micro-Cases)                                       │
│  ☐ Glossary (Terminology)                                   │
│  ☐ Platforms (Emerging)                                     │
│  ☑ Pulse (Market/B2B)                                       │
│  ☐ Explorer (Trend Explorer)                                │
│  ☑ ALL (Mega Dashboard)                                     │
│                                                              │
│  Override Settings (optional):                              │
│  Time Period: ○ Daily  ● Weekly  ○ Monthly  ○ Use Default  │
│  Update Interval: [Use Default ▼]                           │
│  Location: [Use Default ▼] or [Custom location...]          │
│                                                              │
│  [Save] [Cancel] [Generate Now]                             │
└─────────────────────────────────────────────────────────────┘
```

**Features:**
- ✅ Simple keyword management table
- ✅ Per-keyword mode selection
- ✅ Per-keyword interval override (or use global default)
- ✅ Per-keyword location override (for local targeting)
- ✅ Bulk actions for efficiency

### Tab 4: Prompt Templates

**Purpose:** Edit all 11 mode prompts (fully customisable)

**Layout:**
```
┌─────────────────────────────────────────────────────────────┐
│  Prompt Templates                                            │
│  Customise AI prompts for each analysis mode                │
│                                                              │
│  Select Mode: [Summary (General Trends) ▼]                  │
│                                                              │
│  ┌─────────────────────────────────────────────────────┐   │
│  │ System Instruction (applies to all modes)           │   │
│  │ ┌─────────────────────────────────────────────────┐ │   │
│  │ │ You are a Senior Strategic Consultant for a UK │ │   │
│  │ │ Digital Agency.                                 │ │   │
│  │ │ CURRENT DATE: {current_date}                    │ │   │
│  │ │ ANALYSIS WINDOW: {date_range} ({period})        │ │   │
│  │ │ ...                                             │ │   │
│  │ └─────────────────────────────────────────────────┘ │   │
│  │                                                      │   │
│  │ Available Variables:                                 │   │
│  │ {current_date} {date_range} {period} {keyword}      │   │
│  │ {location}                                           │   │
│  └─────────────────────────────────────────────────────┘   │
│                                                              │
│  ┌─────────────────────────────────────────────────────┐   │
│  │ Mode-Specific Prompt: Summary                        │   │
│  │ ┌─────────────────────────────────────────────────┐ │   │
│  │ │ TASK (SUMMARY): Identify top 5 rising          │ │   │
│  │ │ {keyword}-related search themes from Trends/    │ │   │
│  │ │ News in the last {period}. For each theme:      │ │   │
│  │ │ (1) plain-English insight, (2) why it matters   │ │   │
│  │ │ for UK SMEs, (3) what we do about it on        │ │   │
│  │ │ projects. Output short, service-page-ready      │ │   │
│  │ │ bullets.                                        │ │   │
│  │ └─────────────────────────────────────────────────┘ │   │
│  └─────────────────────────────────────────────────────┘   │
│                                                              │
│  ┌─────────────────────────────────────────────────────┐   │
│  │ Expected JSON Structure                              │   │
│  │ ┌─────────────────────────────────────────────────┐ │   │
│  │ │ {                                               │ │   │
│  │ │   "summary": "Tight 'This month in [keyword]'", │ │   │
│  │ │   "trends": [{                                  │ │   │
│  │ │     "term": "Theme",                            │ │   │
│  │ │     "insight": "Plain-English Insight",         │ │   │
│  │ │     "implication": "Why it matters",            │ │   │
│  │ │     "action": "What we do"                      │ │   │
│  │ │   }]                                            │ │   │
│  │ │ }                                               │ │   │
│  │ └─────────────────────────────────────────────────┘ │   │
│  └─────────────────────────────────────────────────────┘   │
│                                                              │
│  [Reset to Default] [Save Changes] [Test Prompt]            │
└─────────────────────────────────────────────────────────────┘
```

**Features:**
- ✅ All 11 mode prompts fully editable
- ✅ System instruction editable (applies to all modes)
- ✅ Variable placeholders clearly documented
- ✅ JSON structure reference for each mode
- ✅ Reset to default option
- ✅ Test prompt button (opens Test Interface tab with pre-filled prompt)

---

## Content Generation Workflow

### Scheduled Generation (WP Cron)

**Basic Flow:**
```
1. Cron triggers at scheduled time (e.g., 3am daily)
   ↓
2. Fetch all active keywords from settings
   ↓
3. For each keyword:
   a. Check if content exists and is fresh (within cache duration)
   b. If stale or missing, generate new content
   c. Call Gemini API with keyword + mode + period
   d. Parse JSON response
   e. Convert JSON to HTML using templates
   f. Store HTML + JSON + metadata in database
   g. Mark as active, deactivate old content
   ↓
4. Log completion, token usage, costs
   ↓
5. Send admin notification (optional)
```

**Gradual Rollout Implementation (Rate Limiting):**

```php
/**
 * WP Cron callback with gradual rollout
 */
public function run_scheduled_generation() {
    $settings = AI_Pulse_Settings::get_all();
    $keywords = $settings['keywords'];

    // Get gradual rollout settings
    $gradual_enabled = $settings['gradual_rollout_enabled'];
    $rollout_window_hours = $settings['rollout_window_hours']; // e.g., 2 hours
    $delay_between_requests = $settings['delay_between_requests']; // e.g., 2 seconds
    $max_concurrent = $settings['max_concurrent_generations']; // e.g., 3

    if ($gradual_enabled && count($keywords) > 1) {
        // Calculate delay between each keyword
        $total_keywords = count($keywords);
        $window_seconds = $rollout_window_hours * 3600;
        $delay_per_keyword = $window_seconds / $total_keywords;

        // Schedule each keyword with staggered start time
        foreach ($keywords as $index => $keyword_data) {
            $delay = $index * $delay_per_keyword;

            // Schedule single keyword generation
            wp_schedule_single_event(
                time() + $delay,
                'ai_pulse_generate_single_keyword',
                array($keyword_data)
            );
        }
    } else {
        // Generate all keywords immediately (no gradual rollout)
        foreach ($keywords as $keyword_data) {
            $this->generate_keyword_content($keyword_data);

            // Small delay between requests to avoid rate limiting
            if ($delay_between_requests > 0) {
                sleep($delay_between_requests);
            }
        }
    }
}

/**
 * Generate content for a single keyword (called by gradual rollout)
 */
public function generate_single_keyword($keyword_data) {
    $keyword = $keyword_data['keyword'];
    $modes = $keyword_data['modes'];
    $period = $keyword_data['period'];

    $generator = new AI_Pulse_Generator();

    foreach ($modes as $mode) {
        try {
            // Generate content
            $result = $generator->generate_content($keyword, $mode, $period);

            if (is_wp_error($result)) {
                AI_Pulse_Logger::log(
                    "Failed to generate {$mode} for {$keyword}: " . $result->get_error_message(),
                    AI_Pulse_Logger::LOG_LEVEL_ERROR,
                    array('keyword' => $keyword, 'mode' => $mode)
                );
                continue;
            }

            // Store in database
            AI_Pulse_Database::store_content($keyword, $mode, $period, $result);

            // Track usage
            AI_Pulse_Cost_Tracker::track_usage(
                $keyword,
                $mode,
                $result['input_tokens'],
                $result['output_tokens']
            );

            AI_Pulse_Logger::log(
                "Successfully generated {$mode} for {$keyword}",
                AI_Pulse_Logger::LOG_LEVEL_INFO,
                array('keyword' => $keyword, 'mode' => $mode, 'tokens' => $result['total_tokens'])
            );

        } catch (Exception $e) {
            AI_Pulse_Logger::log(
                "Exception generating {$mode} for {$keyword}: " . $e->getMessage(),
                AI_Pulse_Logger::LOG_LEVEL_ERROR,
                array('keyword' => $keyword, 'mode' => $mode, 'exception' => $e)
            );
        }

        // Delay between modes for same keyword
        sleep(2);
    }
}

/**
 * Register WP Cron schedules
 */
public function register_cron_schedules($schedules) {
    // Add 2-day interval
    $schedules['two_days'] = array(
        'interval' => 172800,  // 2 days in seconds
        'display' => __('Every 2 Days', 'ai-pulse')
    );

    // Add 3-day interval
    $schedules['three_days'] = array(
        'interval' => 259200,  // 3 days in seconds
        'display' => __('Every 3 Days', 'ai-pulse')
    );

    return $schedules;
}
add_filter('cron_schedules', array($this, 'register_cron_schedules'));

/**
 * Schedule the main cron event
 */
public function schedule_cron() {
    $settings = AI_Pulse_Settings::get_all();
    $interval = $settings['update_interval']; // 'daily', 'two_days', 'three_days', 'weekly'
    $start_time = $settings['start_time']; // e.g., '03:00'

    // Clear existing schedule
    $timestamp = wp_next_scheduled('ai_pulse_scheduled_generation');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'ai_pulse_scheduled_generation');
    }

    // Calculate next run time
    $time_parts = explode(':', $start_time);
    $next_run = strtotime("today {$time_parts[0]}:{$time_parts[1]}:00");

    // If time has passed today, schedule for tomorrow
    if ($next_run < time()) {
        $next_run = strtotime("tomorrow {$time_parts[0]}:{$time_parts[1]}:00");
    }

    // Schedule new event
    wp_schedule_event($next_run, $interval, 'ai_pulse_scheduled_generation');
}

/**
 * Unschedule cron on plugin deactivation
 */
public function unschedule_cron() {
    $timestamp = wp_next_scheduled('ai_pulse_scheduled_generation');
    if ($timestamp) {
        wp_unschedule_event($timestamp, 'ai_pulse_scheduled_generation');
    }
}
register_deactivation_hook(__FILE__, array($this, 'unschedule_cron'));
```

**Error Handling & Pause on Error:**

```php
/**
 * Track errors during batch generation
 */
private $error_count = 0;
private $max_errors = 3;

public function generate_keyword_content($keyword_data) {
    // Check if we should pause due to errors
    if ($this->error_count >= $this->max_errors) {
        AI_Pulse_Logger::log(
            "Pausing batch generation due to {$this->error_count} consecutive errors",
            AI_Pulse_Logger::LOG_LEVEL_ERROR
        );

        // Send admin notification
        $this->send_error_notification("Batch generation paused due to multiple errors");

        return;
    }

    // Attempt generation
    $result = $this->generate_single_keyword($keyword_data);

    if (is_wp_error($result)) {
        $this->error_count++;
    } else {
        // Reset error count on success
        $this->error_count = 0;
    }
}
```

### Manual Generation (Admin Interface)

```
1. Admin selects keyword + mode + period
   ↓
2. Click "Generate Now" button
   ↓
3. AJAX request to backend
   ↓
4. Same generation process as scheduled
   ↓
5. Return success/error + preview HTML
   ↓
6. Display in admin interface with "View" and "Activate" buttons
```

---

## Shortcode System

### Primary Shortcode: `[ai_pulse]`

**Full Attribute List:**

| Attribute | Type | Default | Description | Example Values |
|-----------|------|---------|-------------|----------------|
| `keyword` | string | **required** | Service keyword to analyse | `"SEO"`, `"Web Design"`, `"PPC"` |
| `mode` | string | `SUMMARY` | Analysis mode | `SUMMARY`, `FAQS`, `STATS`, `LOCAL`, `ALL` |
| `timeframe` | string | `weekly` | Data timeframe for analysis | `daily`, `weekly`, `monthly` |
| `update_interval` | string | `default` | How often to regenerate | `daily`, `2days`, `3days`, `weekly`, `default` |
| `style` | string | `box` | Display style | `box`, `inline`, `minimal`, `card` |
| `show_sources` | bool | `true` | Show citation links | `true`, `false` |
| `location` | string | `default` | Location focus override | `"Birmingham"`, `"London"`, `"default"` |
| `max_age` | int | `24` | Max age in hours before showing stale warning | `24`, `48`, `72` |
| `fallback` | string | `hide` | What to do if no content | `hide`, `message`, `placeholder` |
| `class` | string | `''` | Additional CSS classes | `"my-custom-class"` |

**Smart Keyword Capitalisation:**
The LLM automatically formats keywords correctly in output:
- Input: `keyword="seo"` → Output displays: **"SEO"**
- Input: `keyword="web design"` → Output displays: **"Web Design"**
- Input: `keyword="ppc advertising"` → Output displays: **"PPC Advertising"**

This is handled in the prompt template with an instruction:
```
KEYWORD FORMATTING: Always capitalise acronyms (SEO, PPC, ROI) and use title case for multi-word keywords.
```

### Shortcode Examples

**Basic Usage:**
```
[ai_pulse keyword="SEO" mode="SUMMARY"]
```

**With Timeframe and Update Interval:**
```
[ai_pulse keyword="seo" timeframe="daily" update_interval="weekly" mode="SUMMARY"]
```
*Note: "seo" will be displayed as "SEO" in the output*

**FAQ Section with Custom Location:**
```
[ai_pulse keyword="Web Design" mode="FAQS" location="Birmingham" style="inline"]
```

**Stats with No Sources:**
```
[ai_pulse keyword="PPC" mode="STATS" show_sources="false" style="card"]
```

**Mega Dashboard with Custom Styling:**
```
[ai_pulse keyword="Digital Marketing" mode="ALL" timeframe="monthly" class="my-dashboard"]
```

**Local Trends with Specific Location:**
```
[ai_pulse keyword="SEO" mode="LOCAL" location="Manchester" update_interval="3days"]
```

**With Fallback Message:**
```
[ai_pulse keyword="SEO" mode="SUMMARY" fallback="message"]
```
*Shows "Content is being generated..." if no content exists*

### Rendering Logic

```php
public function render_shortcode($atts) {
    $atts = shortcode_atts(array(
        'keyword' => '',
        'mode' => 'SUMMARY',
        'timeframe' => 'weekly',
        'update_interval' => 'default',
        'style' => 'box',
        'show_sources' => 'true',
        'location' => 'default',
        'max_age' => 24,
        'fallback' => 'hide',
        'class' => ''
    ), $atts);

    // Validate required attributes
    if (empty($atts['keyword'])) {
        return $this->render_error('Keyword attribute is required');
    }

    // Normalise keyword (lowercase for database lookup)
    $keyword_normalised = strtolower(trim($atts['keyword']));

    // Fetch latest active content from database
    $content = AI_Pulse_Database::get_active_content(
        $keyword_normalised,
        $atts['mode'],
        $atts['timeframe']
    );

    // Handle missing content based on fallback setting
    if (!$content) {
        switch ($atts['fallback']) {
            case 'message':
                return $this->render_placeholder('Content is being generated...');
            case 'placeholder':
                return $this->render_placeholder_skeleton($atts['mode']);
            case 'hide':
            default:
                return '';
        }
    }

    // Check content age
    $age_hours = $this->get_content_age_hours($content->generated_at);
    $show_stale_warning = ($age_hours > $atts['max_age']);

    // Format and return HTML
    return $this->format_content($content, $atts, $show_stale_warning);
}

/**
 * Format content with proper capitalisation
 */
private function format_content($content, $atts, $show_stale_warning = false) {
    // Content HTML is already pre-generated with proper capitalisation
    // (LLM handles capitalisation in the prompt)

    $html = '<div class="ai-pulse-wrapper ai-pulse-mode-' . esc_attr($atts['mode']) .
            ' ai-pulse-style-' . esc_attr($atts['style']) .
            ' ' . esc_attr($atts['class']) . '">';

    // Stale content warning (admin only)
    if ($show_stale_warning && current_user_can('manage_options')) {
        $html .= '<div class="ai-pulse-stale-warning">';
        $html .= '⚠️ Content is ' . $age_hours . ' hours old. ';
        $html .= '<a href="#" class="ai-pulse-regenerate">Regenerate now</a>';
        $html .= '</div>';
    }

    // Main content (pre-generated HTML with proper capitalisation)
    $html .= $content->content_html;

    // Optionally hide sources
    if ($atts['show_sources'] === 'false') {
        $html = preg_replace('/<div class="ai-pulse-sources">.*?<\/div>/s', '', $html);
    }

    $html .= '</div>';

    return $html;
}
```

### Update Interval Behaviour

**How `update_interval` Works:**

1. **`default`** - Uses the global setting from admin (e.g., daily, weekly)
2. **`daily`** - Regenerates content every day at scheduled time
3. **`2days`** - Regenerates every 2 days
4. **`3days`** - Regenerates every 3 days
5. **`weekly`** - Regenerates once per week

**Example Scenario:**
```
Global Setting: Daily updates at 03:00
Shortcode: [ai_pulse keyword="SEO" update_interval="weekly"]

Result: This specific shortcode's content will only regenerate weekly,
        even though other keywords update daily.
```

**Database Storage:**
Each keyword + mode + timeframe combination is stored separately, allowing:
- Different update intervals per keyword
- Different timeframes for the same keyword
- Efficient caching and retrieval

---

## HTML Template System

### Template Structure

Each mode has a corresponding HTML template that converts JSON to semantic HTML:

**Example: SUMMARY Mode Template**
```html
<div class="ai-pulse-summary">
    <div class="ai-pulse-header">
        <h3>Current Trends in {keyword}</h3>
        <span class="ai-pulse-date">{date_range}</span>
    </div>
    <div class="ai-pulse-intro">
        <p>{summary}</p>
    </div>
    <ul class="ai-pulse-trends">
        {foreach trends}
        <li class="ai-pulse-trend-item">
            <h4>{term}</h4>
            <p class="insight">{insight}</p>
            <p class="implication">{implication}</p>
            <p class="action"><strong>What we do:</strong> {action}</p>
        </li>
        {/foreach}
    </ul>
    {if show_sources}
    <div class="ai-pulse-sources">
        <h5>Sources</h5>
        <ul>
            {foreach sources}
            <li><a href="{uri}" target="_blank">{title}</a></li>
            {/foreach}
        </ul>
    </div>
    {/if}
</div>
```

### CSS Styling

- **Modern, clean design** matching TrendPulse UI
- **Responsive** (mobile-first)
- **Accessible** (WCAG 2.1 AA compliant)
- **Customisable** via WordPress Customizer or theme overrides

---

## Performance Optimisation

### Caching Strategy

1. **Database-level caching:** Pre-generated HTML stored in database
2. **Object caching:** Use WordPress object cache for frequently accessed content
3. **Transient API:** Cache API responses for duplicate requests
4. **CDN-friendly:** Static HTML can be cached by CDN

### Load Time Targets

- **Page load:** < 100ms (serving pre-generated HTML)
- **Admin generation:** < 10 seconds per mode
- **Mega Dashboard:** < 30 seconds (all 11 modes)

### Cache Busting & Versioning (Critical)

**Problem:** Browser caching can prevent users from seeing updated CSS/JS files after plugin updates.

**Solution:** Version-based cache busting on all enqueued assets.

```php
/**
 * Enqueue admin styles with cache busting
 */
public function enqueue_admin_assets($hook) {
    // Only load on AI-Pulse admin pages
    if (strpos($hook, 'ai-pulse') === false) {
        return;
    }

    // Get plugin version for cache busting
    $version = AI_PULSE_VERSION;

    // Enqueue admin CSS with version
    wp_enqueue_style(
        'ai-pulse-admin',
        AI_PULSE_PLUGIN_URL . 'assets/css/admin.css',
        array(),
        $version  // Cache busting: changes with each plugin update
    );

    // Enqueue admin JS with version
    wp_enqueue_script(
        'ai-pulse-admin',
        AI_PULSE_PLUGIN_URL . 'assets/js/admin.js',
        array('jquery'),
        $version,  // Cache busting
        true       // Load in footer
    );

    // Pass data to JavaScript
    wp_localize_script('ai-pulse-admin', 'aiPulseAdmin', array(
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('ai_pulse_admin'),
        'version' => $version
    ));
}

/**
 * Enqueue frontend styles with cache busting
 */
public function enqueue_frontend_assets() {
    // Only load if shortcode is present on page
    if (!has_shortcode(get_post()->post_content, 'ai_pulse')) {
        return;
    }

    $version = AI_PULSE_VERSION;

    wp_enqueue_style(
        'ai-pulse-frontend',
        AI_PULSE_PLUGIN_URL . 'assets/css/frontend.css',
        array(),
        $version  // Cache busting
    );
}
```

**Version Increment Strategy:**

1. **Major updates (1.0.0 → 2.0.0):** Breaking changes, major features
2. **Minor updates (1.0.0 → 1.1.0):** New features, significant changes
3. **Patch updates (1.0.0 → 1.0.1):** Bug fixes, small tweaks

**Update `AI_PULSE_VERSION` constant in `ai-pulse.php` with EVERY release:**

```php
// Increment this with EVERY update to force cache refresh
define('AI_PULSE_VERSION', '1.0.1');  // Changed from 1.0.0
```

**Testing Cache Busting:**

1. Load admin page, check browser DevTools Network tab
2. Verify CSS/JS URLs include `?ver=1.0.1`
3. Update version to `1.0.2`, reload page
4. Verify URLs now show `?ver=1.0.2` (forces fresh download)

**Additional Cache Busting for Dynamic Content:**

```php
/**
 * Add cache-control headers for generated content
 */
public function add_cache_headers() {
    if (is_singular() && has_shortcode(get_post()->post_content, 'ai_pulse')) {
        // Allow caching but validate freshness
        header('Cache-Control: public, max-age=3600, must-revalidate');
        header('Vary: Accept-Encoding');
    }
}
add_action('send_headers', array($this, 'add_cache_headers'));
```

---

## Migration from AI-Stats

### Automated Migration Tool

```php
class AI_Pulse_Migrator {
    public function migrate_from_ai_stats() {
        // 1. Detect AI-Stats installation
        // 2. Export keywords and settings
        // 3. Import into AI-Pulse
        // 4. Generate initial content
        // 5. Deactivate AI-Stats (with user confirmation)
    }
}
```

### Migration Checklist

- [ ] Export AI-Stats keywords
- [ ] Map AI-Stats modes to AI-Pulse modes
- [ ] Transfer shortcodes (find/replace in posts/pages)
- [ ] Generate initial content for all keywords
- [ ] Test shortcode rendering
- [ ] Deactivate AI-Stats
- [ ] Delete AI-Stats (optional)

---

## WordPress.org Compliance

### Checklist (Based on WordPress.org Plugin Guidelines)

**Security & Data Handling:**
- [ ] No hardcoded API keys (use settings, stored in wp_options)
- [ ] Proper sanitisation of all user inputs (`sanitize_text_field`, `sanitize_textarea_field`)
- [ ] Output escaping (`esc_html`, `esc_attr`, `esc_url`, `wp_kses_post`)
- [ ] Nonce verification for all forms and AJAX requests
- [ ] Capability checks for admin functions (`current_user_can('manage_options')`)
- [ ] No direct database queries (use `$wpdb->prepare()` if necessary)
- [ ] No eval() or create_function() usage
- [ ] Validate and sanitise shortcode attributes

**Code Quality:**
- [ ] Follow WordPress Coding Standards (WPCS)
- [ ] Use WordPress core functions (no reinventing the wheel)
- [ ] Proper error handling (WP_Error objects)
- [ ] No PHP warnings or notices
- [ ] Compatible with PHP 7.4+ and WordPress 5.0+

**Licensing & Attribution:**
- [ ] GPL v2 or later licence
- [ ] Licence header in main plugin file
- [ ] Credit third-party libraries (if any)
- [ ] No proprietary code or closed-source dependencies

**User Experience:**
- [ ] Internationalisation (i18n) ready (`__()`, `_e()`, `esc_html__()`)
- [ ] Text domain: `ai-pulse`
- [ ] Translation-ready strings
- [ ] Accessible admin interface (WCAG 2.1 AA)
- [ ] Clear documentation and help text

**Dependencies:**
- [ ] Declare AI-Core dependency in plugin header (`Requires Plugins: ai-core`)
- [ ] Graceful degradation if AI-Core is not active
- [ ] No external API calls without user consent
- [ ] No tracking or analytics without opt-in

**Uninstall & Cleanup:**
- [ ] `uninstall.php` file for cleanup
- [ ] Remove custom database tables on uninstall
- [ ] Remove plugin options on uninstall
- [ ] Option to preserve data (user setting)
- [ ] No data left behind unless user chooses to keep it

**Performance:**
- [ ] No blocking operations on page load
- [ ] Enqueue scripts/styles only where needed
- [ ] Use WordPress transients for caching
- [ ] Optimize database queries (use indexes)
- [ ] No unnecessary autoloaded options

**Admin Interface:**
- [ ] Settings page under AI-Core menu (bundled add-on)
- [ ] Clear, intuitive UI (TrendPulse-inspired)
- [ ] Help text and tooltips
- [ ] Success/error messages for user actions
- [ ] No admin notices spam

---

## Testing Requirements

### Unit Tests

- Settings save/load
- Database CRUD operations
- JSON parsing and validation
- HTML template rendering
- Shortcode attribute parsing

### Integration Tests

- AI-Core API integration
- Gemini API calls with Search Grounding
- WP Cron scheduling
- AJAX handlers
- Admin interface interactions

### User Acceptance Tests

- Generate content for 5 keywords
- Verify HTML output quality
- Test shortcode on live page
- Check mobile responsiveness
- Validate source citations
- Measure load times

---

## Development Phases

### Phase 1: Core Plugin Structure (2-3 hours)
- [ ] Create main plugin file
- [ ] Database schema and migration
- [ ] Settings class with defaults
- [ ] Admin menu and basic UI

### Phase 2: Content Generation (3-4 hours)
- [ ] Generator class with Gemini integration
- [ ] Mode definitions and prompts
- [ ] JSON to HTML conversion
- [ ] Error handling and logging

### Phase 3: Scheduling & Automation (2 hours)
- [ ] WP Cron integration
- [ ] Scheduler class
- [ ] Background processing
- [ ] Admin notifications

### Phase 4: Frontend & Shortcodes (2 hours)
- [ ] Shortcode handler
- [ ] HTML templates for all modes
- [ ] CSS styling
- [ ] Responsive design

### Phase 5: Admin Interface (3 hours)
- [ ] Settings page UI
- [ ] Content manager
- [ ] Debug/testing interface
- [ ] AJAX handlers

### Phase 6: Testing & Polish (2-3 hours)
- [ ] Unit tests
- [ ] Integration tests
- [ ] Performance optimisation
- [ ] Documentation

**Total Estimated Time:** 14-17 hours

---

## Next Steps

1. **Review and approve this plan**
2. **Confirm settings requirements** (location, time periods, etc.)
3. **Begin Phase 1 implementation**
4. **Set up test environment with sample keywords**
5. **Iterate based on feedback**

---

## Questions for Clarification

1. **Location Settings:** Should location be global (one setting) or per-keyword?
2. **Time Periods:** Should each keyword have its own period, or global default?
3. **Admin Placement:** Should this be under AI-Core menu or standalone?
4. **Shortcode Naming:** Prefer `[ai_pulse]` or `[trend_pulse]` or other?
5. **Migration:** Automatic or manual migration from AI-Stats?
6. **Notifications:** Email admin when generation completes/fails?

---

## Frontend Presentation (TrendPulse-Inspired Design)

### Design System

**Based on TrendPulse Screenshots - Key Visual Elements:**

#### Colour Palette
```css
/* Primary Colours */
--ai-pulse-blue-600: #2563eb;      /* Primary actions, highlights */
--ai-pulse-blue-700: #1d4ed8;      /* Hover states */
--ai-pulse-blue-50: #eff6ff;       /* Light backgrounds */

/* Neutral Slate */
--ai-pulse-slate-50: #f8fafc;      /* Page background */
--ai-pulse-slate-100: #f1f5f9;     /* Input backgrounds */
--ai-pulse-slate-200: #e2e8f0;     /* Borders */
--ai-pulse-slate-300: #cbd5e1;     /* Dividers */
--ai-pulse-slate-400: #94a3b8;     /* Placeholder text */
--ai-pulse-slate-500: #64748b;     /* Secondary text */
--ai-pulse-slate-800: #1e293b;     /* Dark backgrounds */
--ai-pulse-slate-900: #0f172a;     /* Headings */

/* Accent Colours */
--ai-pulse-green-600: #16a34a;     /* Success, authority badge */
--ai-pulse-green-50: #f0fdf4;      /* Success backgrounds */
--ai-pulse-orange-500: #f97316;    /* Warnings, highlights */
```

#### Typography
```css
/* Font Stack */
font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;

/* Sizes */
--text-xs: 0.75rem;      /* 12px - labels, badges */
--text-sm: 0.875rem;     /* 14px - body text */
--text-base: 1rem;       /* 16px - default */
--text-lg: 1.125rem;     /* 18px - inputs */
--text-xl: 1.25rem;      /* 20px - subheadings */
--text-2xl: 1.5rem;      /* 24px - section titles */
--text-3xl: 1.875rem;    /* 30px - page titles */
--text-4xl: 2.25rem;     /* 36px - hero headings */

/* Weights */
--font-normal: 400;
--font-medium: 500;
--font-semibold: 600;
--font-bold: 700;
```

#### Spacing & Layout
```css
/* Border Radius */
--radius-lg: 0.75rem;    /* 12px - cards */
--radius-xl: 1rem;       /* 16px - inputs, buttons */
--radius-2xl: 1.5rem;    /* 24px - main containers */

/* Shadows */
--shadow-sm: 0 1px 2px 0 rgb(0 0 0 / 0.05);
--shadow-md: 0 4px 6px -1px rgb(0 0 0 / 0.1);
--shadow-lg: 0 10px 15px -3px rgb(0 0 0 / 0.1);

/* Spacing Scale */
--space-1: 0.25rem;   /* 4px */
--space-2: 0.5rem;    /* 8px */
--space-3: 0.75rem;   /* 12px */
--space-4: 1rem;      /* 16px */
--space-5: 1.25rem;   /* 20px */
--space-6: 1.5rem;    /* 24px */
--space-8: 2rem;      /* 32px */
```

### Component Styles (Matching TrendPulse)

#### 1. Header/Navigation
```html
<header class="ai-pulse-header">
  <div class="ai-pulse-header-inner">
    <div class="ai-pulse-logo">
      <div class="ai-pulse-icon">✨</div>
      <span class="ai-pulse-title">TrendPulse Analyst</span>
    </div>
    <div class="ai-pulse-header-actions">
      <span class="ai-pulse-badge ai-pulse-badge-success">
        🌐 Authority Only
      </span>
      <button class="ai-pulse-btn-toggle">
        💻 Prompt Debugger
      </button>
    </div>
  </div>
</header>
```

**CSS:**
```css
.ai-pulse-header {
  background: white;
  border-bottom: 1px solid var(--ai-pulse-slate-200);
  position: sticky;
  top: 0;
  z-index: 50;
}

.ai-pulse-header-inner {
  max-width: 1280px;
  margin: 0 auto;
  padding: 0 1rem;
  height: 4rem;
  display: flex;
  align-items: center;
  justify-content: space-between;
}

.ai-pulse-logo {
  display: flex;
  align-items: center;
  gap: 0.5rem;
}

.ai-pulse-icon {
  width: 2rem;
  height: 2rem;
  background: var(--ai-pulse-blue-600);
  border-radius: var(--radius-lg);
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  box-shadow: 0 2px 4px rgba(37, 99, 235, 0.2);
}

.ai-pulse-title {
  font-weight: var(--font-bold);
  font-size: var(--text-lg);
  color: var(--ai-pulse-slate-800);
  letter-spacing: -0.025em;
}
```

#### 2. Main Content Card
```html
<div class="ai-pulse-card">
  <h1 class="ai-pulse-heading-primary">Real-Time Service Intelligence</h1>
  <p class="ai-pulse-subtitle">
    Live market analysis powered by Gemini 3 Pro.<br>
    Select a mode, set the timeframe, and get verified insights.
  </p>

  <!-- Content -->
</div>
```

**CSS:**
```css
.ai-pulse-card {
  background: white;
  border-radius: var(--radius-2xl);
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--ai-pulse-slate-200);
  padding: 1.25rem;
  margin-bottom: 1.5rem;
}

.ai-pulse-heading-primary {
  font-size: var(--text-4xl);
  font-weight: var(--font-bold);
  color: var(--ai-pulse-slate-900);
  letter-spacing: -0.025em;
  margin-bottom: 0.75rem;
  text-align: center;
}

.ai-pulse-subtitle {
  color: var(--ai-pulse-slate-500);
  font-size: var(--text-base);
  line-height: 1.625;
  text-align: center;
  max-width: 32rem;
  margin: 0 auto;
}
```

#### 3. Input Fields (TrendPulse Style)
```html
<div class="ai-pulse-input-group">
  <div class="ai-pulse-input-icon">🔍</div>
  <input
    type="text"
    class="ai-pulse-input"
    placeholder="Enter service keyword (e.g., 'SEO', 'Cloud Migration')..."
  >
</div>
```

**CSS:**
```css
.ai-pulse-input-group {
  position: relative;
}

.ai-pulse-input-icon {
  position: absolute;
  left: 1rem;
  top: 50%;
  transform: translateY(-50%);
  color: var(--ai-pulse-slate-400);
  font-size: 1.25rem;
  pointer-events: none;
  transition: color 0.2s;
}

.ai-pulse-input {
  width: 100%;
  padding: 1rem 1rem 1rem 3rem;
  background: var(--ai-pulse-slate-50);
  border: 1px solid var(--ai-pulse-slate-200);
  border-radius: var(--radius-xl);
  font-size: var(--text-lg);
  font-weight: var(--font-medium);
  color: var(--ai-pulse-slate-900);
  transition: all 0.2s;
}

.ai-pulse-input:focus {
  outline: none;
  background: white;
  border-color: var(--ai-pulse-blue-500);
  box-shadow: 0 0 0 3px var(--ai-pulse-blue-50);
}

.ai-pulse-input:focus + .ai-pulse-input-icon {
  color: var(--ai-pulse-blue-500);
}

.ai-pulse-input::placeholder {
  color: var(--ai-pulse-slate-400);
}
```

#### 4. Button Group (Period Selector)
```html
<div class="ai-pulse-btn-group">
  <button class="ai-pulse-btn-group-item active">Daily</button>
  <button class="ai-pulse-btn-group-item">Weekly</button>
  <button class="ai-pulse-btn-group-item">Monthly</button>
</div>
```

**CSS:**
```css
.ai-pulse-btn-group {
  display: flex;
  background: var(--ai-pulse-slate-100);
  padding: 0.25rem;
  border-radius: var(--radius-xl);
  gap: 0;
}

.ai-pulse-btn-group-item {
  flex: 1;
  padding: 0.5rem 1rem;
  border: none;
  background: transparent;
  color: var(--ai-pulse-slate-400);
  font-size: var(--text-xs);
  font-weight: var(--font-bold);
  text-transform: uppercase;
  letter-spacing: 0.05em;
  border-radius: var(--radius-lg);
  cursor: pointer;
  transition: all 0.2s;
}

.ai-pulse-btn-group-item:hover {
  color: var(--ai-pulse-slate-600);
}

.ai-pulse-btn-group-item.active {
  background: white;
  color: var(--ai-pulse-slate-900);
  box-shadow: var(--shadow-sm), 0 0 0 1px rgba(0, 0, 0, 0.05);
}
```

#### 5. Primary Action Button
```html
<button class="ai-pulse-btn-primary">
  Go ▶
</button>
```

**CSS:**
```css
.ai-pulse-btn-primary {
  background: var(--ai-pulse-blue-600);
  color: white;
  padding: 0.75rem 1.5rem;
  border: none;
  border-radius: var(--radius-xl);
  font-weight: var(--font-bold);
  font-size: var(--text-base);
  cursor: pointer;
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  box-shadow: 0 4px 6px rgba(37, 99, 235, 0.2);
  transition: all 0.2s;
}

.ai-pulse-btn-primary:hover {
  background: var(--ai-pulse-blue-700);
  transform: translateY(-1px);
  box-shadow: 0 6px 8px rgba(37, 99, 235, 0.25);
}

.ai-pulse-btn-primary:active {
  transform: scale(0.95);
}

.ai-pulse-btn-primary:disabled {
  opacity: 0.5;
  cursor: not-allowed;
  transform: none;
}
```

#### 6. Code Block (Prompt Debugger)
```html
<div class="ai-pulse-code-block">
  <div class="ai-pulse-code-header">
    <span>PROMPT PREVIEW (Editable)</span>
  </div>
  <div class="ai-pulse-code-content">
    <pre><code>TARGET KEYWORD: "SEO"
STRICT DATE RANGE: 28 Nov 2025 to 5 Dec 2025
TASK: Identify top 5 rising SEO-related...</code></pre>
  </div>
</div>
```

**CSS:**
```css
.ai-pulse-code-block {
  background: var(--ai-pulse-slate-900);
  border-radius: var(--radius-xl);
  overflow: hidden;
  border: 1px solid var(--ai-pulse-slate-800);
  margin: 1rem 0;
}

.ai-pulse-code-header {
  background: rgba(255, 255, 255, 0.05);
  padding: 0.5rem 1rem;
  font-size: var(--text-xs);
  font-weight: var(--font-semibold);
  color: var(--ai-pulse-slate-400);
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.ai-pulse-code-content {
  padding: 1rem;
  max-height: 400px;
  overflow-y: auto;
}

.ai-pulse-code-content pre {
  margin: 0;
  font-family: 'Monaco', 'Menlo', 'Courier New', monospace;
  font-size: var(--text-sm);
  line-height: 1.6;
  color: #e2e8f0;
}

.ai-pulse-code-content code {
  color: #e2e8f0;
}
```

#### 7. Results Card (Summary/Stats/FAQs)
```html
<div class="ai-pulse-result-card">
  <div class="ai-pulse-result-header">
    <div class="ai-pulse-result-icon">📊</div>
    <div>
      <h3 class="ai-pulse-result-title">Weekly Summary: SEO</h3>
      <p class="ai-pulse-result-meta">⚡ LIVE AUTHORITY DATA</p>
    </div>
  </div>

  <div class="ai-pulse-result-content">
    <!-- Generated content -->
  </div>

  <div class="ai-pulse-result-footer">
    <h5>VERIFIED SOURCES</h5>
    <ul class="ai-pulse-sources-list">
      <li><a href="#">bbc.co.uk - Article Title</a></li>
      <li><a href="#">searchengineland.com - Article Title</a></li>
    </ul>
  </div>
</div>
```

**CSS:**
```css
.ai-pulse-result-card {
  background: white;
  border: 1px solid var(--ai-pulse-slate-200);
  border-radius: var(--radius-xl);
  overflow: hidden;
  box-shadow: var(--shadow-sm);
}

.ai-pulse-result-header {
  background: var(--ai-pulse-slate-50);
  padding: 1rem 1.25rem;
  display: flex;
  align-items: center;
  gap: 0.75rem;
  border-bottom: 1px solid var(--ai-pulse-slate-200);
}

.ai-pulse-result-icon {
  font-size: 1.5rem;
}

.ai-pulse-result-title {
  font-size: var(--text-lg);
  font-weight: var(--font-bold);
  color: var(--ai-pulse-slate-900);
  margin: 0;
}

.ai-pulse-result-meta {
  font-size: var(--text-xs);
  color: var(--ai-pulse-green-600);
  font-weight: var(--font-semibold);
  margin: 0.25rem 0 0 0;
}

.ai-pulse-result-content {
  padding: 1.25rem;
  line-height: 1.7;
  color: var(--ai-pulse-slate-700);
}

.ai-pulse-result-footer {
  background: var(--ai-pulse-slate-50);
  padding: 1rem 1.25rem;
  border-top: 1px solid var(--ai-pulse-slate-200);
}

.ai-pulse-result-footer h5 {
  font-size: var(--text-xs);
  font-weight: var(--font-bold);
  color: var(--ai-pulse-slate-500);
  text-transform: uppercase;
  letter-spacing: 0.05em;
  margin: 0 0 0.5rem 0;
}

.ai-pulse-sources-list {
  list-style: none;
  padding: 0;
  margin: 0;
}

.ai-pulse-sources-list li {
  margin: 0.25rem 0;
}

.ai-pulse-sources-list a {
  color: var(--ai-pulse-blue-600);
  text-decoration: none;
  font-size: var(--text-sm);
  transition: color 0.2s;
}

.ai-pulse-sources-list a:hover {
  color: var(--ai-pulse-blue-700);
  text-decoration: underline;
}
```

### Responsive Design

**Mobile-First Breakpoints:**
```css
/* Mobile: < 640px (default) */
/* Tablet: 640px - 1024px */
@media (min-width: 640px) {
  .ai-pulse-grid-sm-2 { grid-template-columns: repeat(2, 1fr); }
}

/* Desktop: > 1024px */
@media (min-width: 1024px) {
  .ai-pulse-grid-lg-3 { grid-template-columns: repeat(3, 1fr); }
}
```

**Mobile Optimisations:**
- Stack all controls vertically on mobile
- Increase touch target sizes (min 44px)
- Simplify navigation (hamburger menu if needed)
- Hide non-essential elements (debugger, advanced options)

---

## Gemini API Integration Details

### Google Search Grounding

**What it does:** Gemini searches the web in real-time and grounds responses in verified sources.

**Configuration:**
```php
$options = array(
    'temperature' => 0.3,  // Low temperature for factual accuracy
    'tools' => array(
        array('googleSearch' => array())  // Enable Search Grounding
    )
);
```

**Response Structure:**
```php
$response = array(
    'text' => '{"summary": "...", "trends": [...]}',  // JSON content
    'candidates' => array(
        array(
            'groundingMetadata' => array(
                'groundingChunks' => array(
                    array(
                        'web' => array(
                            'uri' => 'https://example.com/article',
                            'title' => 'Article Title'
                        )
                    )
                )
            )
        )
    ),
    'usageMetadata' => array(
        'promptTokenCount' => 1234,
        'candidatesTokenCount' => 5678,
        'totalTokenCount' => 6912
    )
);
```

### Prompt Engineering

**System Instruction Template:**
```
You are a Senior Strategic Consultant for a UK Digital Agency.
CURRENT DATE: {current_date}.
ANALYSIS WINDOW: {date_range} ({period_description}).
TARGET AUDIENCE: UK Business Owners & Marketing Directors.
LANGUAGE: British English (en-GB). Use 's' instead of 'z' (e.g., analyse, optimise, prioritising).
CURRENCY: GBP (£) if applicable.

CRITICAL RULES:
1. TIME ACCURACY: You must ONLY use data/search results published between {date_range}. Do not use older evergreen content.
2. AUTHORITY SOURCES: Prioritise official documentation, UK industry reports, major news (BBC, Reuters, The Guardian), government sites (.gov.uk), and academic research. Avoid generic SEO blogs or agencies.
3. NO FAKE DATA: Do not invent stats. If no exact stat exists, provide a qualitative trend from a reputable source.
4. SERVICE-LED TONE: Write as if for a service landing page (e.g., "What we do", "Why it matters").
5. FORMAT: Output MUST be valid JSON. No conversational preamble.
6. LOCATION FOCUS: When analysing local trends, default to {location} unless specified otherwise.
7. KEYWORD FORMATTING: Always capitalise acronyms (SEO, PPC, ROI, CRM, API, UX, UI, etc.) and use title case for multi-word keywords (e.g., "Web Design", "Digital Marketing", "Content Strategy"). This applies to ALL output text including headings, summaries, insights, and content.
```

**Variable Placeholders:**
- `{current_date}` - Today's date (e.g., "5 Dec 2025")
- `{date_range}` - Analysis window (e.g., "28 Nov 2025 to 5 Dec 2025")
- `{period_description}` - Human-readable period (e.g., "LAST 7 DAYS")
- `{keyword}` - Service keyword (input can be lowercase, LLM will format correctly)
- `{location}` - Location focus (e.g., "Birmingham, West Midlands, UK")
```

**User Prompt Template (SUMMARY Mode Example):**
```
TARGET KEYWORD: "{keyword}"
STRICT DATE RANGE: {date_range}
LOCATION: {location}

TASK (SUMMARY): Identify top 5 rising {keyword}-related search themes from Trends/News in the last {period_description}. For each theme: (1) plain-English insight, (2) why it matters for UK SMEs, (3) what we do about it on projects. Output short, service-page-ready bullets.

OUTPUT FORMAT (JSON ONLY):
{
  "summary": "Tight 'This month in [keyword]' block (max 50 words)",
  "trends": [
    {
      "term": "Theme",
      "insight": "Plain-English Insight",
      "implication": "Why it matters for UK SMEs",
      "action": "What we do (Service Action)"
    }
  ]
}
```

### JSON Parsing & Validation

**Cleaning Gemini Response:**
```php
private function clean_gemini_json($text) {
    // Remove markdown code blocks
    $clean = preg_replace('/```json\s*/', '', $text);
    $clean = preg_replace('/```\s*/', '', $clean);

    // Extract JSON object
    $first_open = strpos($clean, '{');
    $last_close = strrpos($clean, '}');

    if ($first_open !== false && $last_close !== false) {
        return substr($clean, $first_open, $last_close - $first_open + 1);
    }

    return trim($clean);
}

private function parse_and_validate($json_string, $mode) {
    $data = json_decode($json_string, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return new WP_Error('invalid_json', 'Failed to parse JSON: ' . json_last_error_msg());
    }

    // Validate structure based on mode
    $validator = new AI_Pulse_Validator();
    if (!$validator->validate_mode_structure($data, $mode)) {
        return new WP_Error('invalid_structure', 'JSON structure does not match expected format for mode: ' . $mode);
    }

    return $data;
}
```

---

## HTML Generation & SEO Optimisation

### Semantic HTML Structure

**Principles:**
- Use semantic HTML5 elements (`<article>`, `<section>`, `<header>`, `<footer>`)
- Proper heading hierarchy (h2 → h3 → h4)
- Schema.org markup for FAQs, statistics, and articles
- Accessible ARIA labels where appropriate

**Example: FAQ Mode with Schema.org**
```html
<div class="ai-pulse-faqs" itemscope itemtype="https://schema.org/FAQPage">
    <header class="ai-pulse-header">
        <h2>Frequently Asked Questions: {keyword}</h2>
        <time datetime="{iso_date}">{date_range}</time>
    </header>

    {foreach faqs}
    <article class="ai-pulse-faq-item" itemscope itemprop="mainEntity" itemtype="https://schema.org/Question">
        <h3 itemprop="name">{question}</h3>
        <div itemscope itemprop="acceptedAnswer" itemtype="https://schema.org/Answer">
            <div itemprop="text">
                {answer}
            </div>
        </div>
    </article>
    {/foreach}

    <footer class="ai-pulse-sources">
        <h4>Sources</h4>
        <ul>
            {foreach sources}
            <li><a href="{uri}" rel="nofollow noopener" target="_blank">{title}</a></li>
            {/foreach}
        </ul>
    </footer>
</div>
```

### CSS Framework

**Design System:**
- Colour palette: Slate greys, blue accents (matching TrendPulse)
- Typography: System fonts for performance
- Spacing: 8px grid system
- Breakpoints: Mobile (< 640px), Tablet (640-1024px), Desktop (> 1024px)

**CSS Variables:**
```css
:root {
    --ai-pulse-primary: #2563eb;
    --ai-pulse-secondary: #64748b;
    --ai-pulse-background: #f8fafc;
    --ai-pulse-border: #e2e8f0;
    --ai-pulse-text: #0f172a;
    --ai-pulse-text-muted: #64748b;
    --ai-pulse-spacing: 1rem;
    --ai-pulse-radius: 0.75rem;
}
```

---

## Error Handling & Logging

### Error Types

1. **API Errors:** Gemini API failures, rate limits, invalid responses
2. **Validation Errors:** Invalid JSON, missing required fields
3. **Database Errors:** Failed inserts, connection issues
4. **Scheduling Errors:** Cron failures, timeout issues

### Logging Strategy

```php
class AI_Pulse_Logger {
    const LOG_LEVEL_ERROR = 'error';
    const LOG_LEVEL_WARNING = 'warning';
    const LOG_LEVEL_INFO = 'info';
    const LOG_LEVEL_DEBUG = 'debug';

    public static function log($message, $level = self::LOG_LEVEL_INFO, $context = array()) {
        if (!self::should_log($level)) {
            return;
        }

        $log_entry = array(
            'timestamp' => current_time('mysql'),
            'level' => $level,
            'message' => $message,
            'context' => $context
        );

        // Store in database
        self::store_log($log_entry);

        // Also log to WordPress debug.log if WP_DEBUG is enabled
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf('[AI-Pulse][%s] %s', strtoupper($level), $message));
        }
    }
}
```

### User-Facing Error Messages

**Admin Interface:**
- Clear, actionable error messages
- Suggested fixes for common issues
- Link to documentation/support

**Frontend (Shortcode):**
- Silent failure (no content displayed)
- Admin-only error notices (if logged in)
- Fallback to cached content if available

---

## Security Considerations

### Input Sanitisation

```php
// Sanitise keyword input
$keyword = sanitize_text_field($_POST['keyword']);

// Validate mode
$allowed_modes = array('SUMMARY', 'FAQS', 'STATS', /* ... */);
$mode = in_array($_POST['mode'], $allowed_modes) ? $_POST['mode'] : 'SUMMARY';

// Sanitise location
$location = sanitize_text_field($_POST['location']);
```

### Output Escaping

```php
// Escape HTML output
echo esc_html($keyword);

// Allow specific HTML in content (using wp_kses_post)
echo wp_kses_post($content_html);

// Escape URLs
echo esc_url($source_uri);

// Escape attributes
echo esc_attr($mode);
```

### Capability Checks

```php
// Admin functions require manage_options capability
if (!current_user_can('manage_options')) {
    wp_die(__('You do not have sufficient permissions to access this page.', 'ai-pulse'));
}

// AJAX nonce verification
check_ajax_referer('ai_pulse_admin', 'nonce');
```

### API Key Security

- API keys stored in WordPress options (encrypted if possible)
- Never exposed in frontend JavaScript
- Never logged in debug output
- Transmitted only via HTTPS

---

## Pricing & Cost Management

### Gemini Pricing (as of December 2025)

**Gemini 3 Pro Preview:**
- Input: $3.50 per 1M tokens
- Output: $10.50 per 1M tokens

**Estimated Costs:**

| Mode | Avg Input Tokens | Avg Output Tokens | Cost per Generation |
|------|------------------|-------------------|---------------------|
| SUMMARY | 1,200 | 700 | $0.0115 |
| FAQS | 1,200 | 800 | $0.0126 |
| STATS | 1,200 | 600 | $0.0105 |
| ALL (Mega Dashboard) | 1,500 | 4,000 | $0.0473 |

**Monthly Cost Estimate:**
- 10 keywords × Daily updates × SUMMARY mode = 10 × 30 × $0.0115 = **$3.45/month**
- 10 keywords × Weekly updates × ALL mode = 10 × 4 × $0.0473 = **$1.89/month**

### Cost Tracking

```php
class AI_Pulse_Cost_Tracker {
    public function track_usage($keyword, $mode, $input_tokens, $output_tokens) {
        $cost = $this->calculate_cost($input_tokens, $output_tokens);

        // Store in database
        AI_Pulse_Database::insert_usage_record(array(
            'keyword' => $keyword,
            'mode' => $mode,
            'input_tokens' => $input_tokens,
            'output_tokens' => $output_tokens,
            'cost_usd' => $cost,
            'timestamp' => current_time('mysql')
        ));

        // Update monthly total
        $this->update_monthly_total($cost);
    }

    private function calculate_cost($input_tokens, $output_tokens) {
        $input_cost = ($input_tokens / 1000000) * 3.50;
        $output_cost = ($output_tokens / 1000000) * 10.50;
        return $input_cost + $output_cost;
    }
}
```

### Admin Cost Dashboard

- **Current Month Spend:** Total cost for current month
- **Cost by Keyword:** Breakdown per keyword
- **Cost by Mode:** Breakdown per analysis mode
- **Projected Monthly Cost:** Based on current usage patterns
- **Cost Alerts:** Email notification if monthly spend exceeds threshold

---

## Accessibility (WCAG 2.1 AA Compliance)

### Requirements

- [ ] Keyboard navigation for all interactive elements
- [ ] ARIA labels for screen readers
- [ ] Sufficient colour contrast (4.5:1 for text)
- [ ] Focus indicators on interactive elements
- [ ] Semantic HTML structure
- [ ] Alt text for any images/icons
- [ ] Skip links for long content
- [ ] Responsive text sizing (no fixed px for body text)

### Testing Tools

- WAVE (Web Accessibility Evaluation Tool)
- axe DevTools
- Lighthouse Accessibility Audit
- Screen reader testing (NVDA, JAWS, VoiceOver)

---

## Internationalisation (i18n)

### Text Domain: `ai-pulse`

**Example Usage:**
```php
__('Generate Content', 'ai-pulse');
_e('Settings saved successfully.', 'ai-pulse');
esc_html__('Analysis Mode', 'ai-pulse');
```

### Translatable Strings

- All admin interface text
- Error messages
- Success notifications
- Shortcode output labels (optional, content is in British English)

### Language Files

- `languages/ai-pulse.pot` - Template file
- `languages/ai-pulse-en_GB.po` - British English (default)
- Future: Support for other locales

---

## Version Control & Deployment

### Versioning Strategy

**Semantic Versioning:** MAJOR.MINOR.PATCH

- **MAJOR:** Breaking changes (e.g., database schema changes)
- **MINOR:** New features (e.g., new analysis mode)
- **PATCH:** Bug fixes, minor improvements

**Initial Release:** 1.0.0

### Deployment Checklist

- [ ] Update version number in main plugin file
- [ ] Update version constant
- [ ] Update changelog in README
- [ ] Run database migrations if needed
- [ ] Clear all caches
- [ ] Test on staging environment
- [ ] Create Git tag
- [ ] Deploy to production
- [ ] Monitor error logs for 24 hours

### Rollback Plan

- Keep previous version as Git tag
- Database migration rollback scripts
- Backup before major updates
- Ability to deactivate and revert to AI-Stats if needed

---

---

## Implementation Checklist (All Requirements Met)

### ✅ User Requirements Completed

1. **Smart Capitalisation** ✅
   - LLM instruction added (Rule #7 in system prompt)
   - "seo" → "SEO", "web design" → "Web Design"

2. **Real-Time Testing in Admin** ✅
   - Test Interface tab (TrendPulse-inspired)
   - Live cost estimation
   - Editable prompt preview
   - Instant content generation

3. **Scheduled Background Generation** ✅
   - WP Cron integration
   - Runs at 03:00 (configurable)
   - Email notifications

4. **Flexible Update Intervals** ✅
   - Daily, 2-day, 3-day, weekly options
   - Per-keyword overrides

5. **Gradual Rollout** ✅
   - Spread updates over time window
   - Rate limiting controls
   - Pause on error option

6. **Editable Prompt Templates** ✅
   - All 11 modes customisable
   - System instruction editable
   - Variable placeholders documented

7. **All TrendPulse Features** ✅
   - 11 analysis modes + ALL mode
   - Period selection
   - Cost tracking
   - Prompt debugger
   - Source citations

8. **Simple Admin UX** ✅
   - TrendPulse-inspired design
   - Card-based layout
   - Clear visual hierarchy
   - SEO-focused

9. **Configurable Shortcode** ✅
   - 10 attributes (keyword, mode, timeframe, update_interval, style, show_sources, location, max_age, fallback, class)

10. **TrendPulse UI/UX Maintained** ✅
    - Complete design system documented
    - Component styles defined
    - Responsive design

---

**Document Status:** ✅ READY FOR IMPLEMENTATION
**Next Action:** Begin Phase 1 - Core Plugin Structure
**Estimated Time:** 23-27 hours total development

