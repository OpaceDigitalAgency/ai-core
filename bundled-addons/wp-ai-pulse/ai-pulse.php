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

        // Initialize scheduler
        AI_Pulse_Scheduler::init();

        // Initialize shortcode
        AI_Pulse_Shortcode::init();

        // Initialize admin interface (if in admin)
        if (is_admin()) {
            AI_Pulse_Admin::init();
            AI_Pulse_Ajax::init();
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
        AI_Pulse_Scheduler::schedule();

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Plugin deactivation
     */
    public function deactivate() {
        // Unschedule cron
        AI_Pulse_Scheduler::unschedule();

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
     * Show configuration notice if AI-Core is not configured
     */
    public function show_configuration_notice() {
        ?>
        <div class="notice notice-warning">
            <p>
                <strong>AI-Pulse:</strong> Please configure AI-Core settings to use this plugin.
                <a href="<?php echo admin_url('admin.php?page=ai-core-settings'); ?>">Configure AI-Core</a>
            </p>
        </div>
        <?php
    }

    /**
     * Show Gemini notice if Gemini is not configured
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

    /**
     * Enqueue admin assets
     */
    public function enqueue_admin_assets($hook) {
        // Only load on AI-Pulse admin pages
        if (strpos($hook, 'ai-pulse') === false) {
            return;
        }

        $version = AI_PULSE_VERSION;

        wp_enqueue_style(
            'ai-pulse-admin',
            AI_PULSE_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            $version
        );

        wp_enqueue_script(
            'ai-pulse-admin',
            AI_PULSE_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            $version,
            true
        );

        wp_localize_script('ai-pulse-admin', 'aiPulseAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_pulse_admin'),
        ));
    }

    /**
     * Enqueue frontend assets
     */
    public function enqueue_frontend_assets() {
        // Only load if shortcode is present
        if (!is_singular() || !has_shortcode(get_post()->post_content, 'ai_pulse')) {
            return;
        }

        $version = AI_PULSE_VERSION;

        wp_enqueue_style(
            'ai-pulse-frontend',
            AI_PULSE_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            $version
        );
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

