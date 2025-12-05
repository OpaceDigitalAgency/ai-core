<?php
/**
 * Plugin Name: AI-Stats - Dynamic SEO Content Modules
 * Plugin URI: https://opace.agency/ai-stats
 * Description: Dynamic content generation plugin with 6 switchable modes for SEO enhancement. Automatically generates fresh, data-driven content using real-time web scraping and AI. Seamlessly integrates with AI-Core for unified API management.
 * Version: 0.8.2
 * Author: Opace Digital Agency
 * Author URI: https://opace.agency
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ai-stats
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.8.1
 * Requires PHP: 7.4
 * Network: false
 * Tags: ai, seo, content, statistics, dynamic content, automation
 *
 * @package AI_Stats
 * @version 0.8.2
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('AI_STATS_VERSION', '0.8.2');
define('AI_STATS_PLUGIN_FILE', __FILE__);
define('AI_STATS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AI_STATS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AI_STATS_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main AI-Stats Plugin Class
 * 
 * Singleton pattern implementation
 */
class AI_Stats {
    
    /**
     * Plugin instance
     * 
     * @var AI_Stats
     */
    private static $instance = null;
    
    /**
     * AI-Core API instance
     * 
     * @var AI_Core_API
     */
    private $ai_core = null;
    
    /**
     * Get plugin instance
     * 
     * @return AI_Stats
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Constructor
     */
    private function __construct() {
        $this->check_dependencies();
        $this->load_dependencies();
        $this->init_hooks();
    }
    
    /**
     * Check if AI-Core is active
     *
     * @return void
     */
    private function check_dependencies() {
        if (!function_exists('ai_core')) {
            add_action('admin_notices', array($this, 'show_dependency_notice'));
            // Deactivate this plugin if AI-Core is not available
            add_action('admin_init', array($this, 'deactivate_plugin'));
            return;
        }

        $this->ai_core = ai_core();

        if (!$this->ai_core->is_configured()) {
            add_action('admin_notices', array($this, 'show_configuration_notice'));
        }
    }

    /**
     * Deactivate plugin if dependencies not met
     *
     * @return void
     */
    public function deactivate_plugin() {
        if (!function_exists('ai_core')) {
            deactivate_plugins(AI_STATS_PLUGIN_BASENAME);
        }
    }
    
    /**
     * Show dependency notice
     * 
     * @return void
     */
    public function show_dependency_notice() {
        ?>
        <div class="notice notice-error">
            <p>
                <strong><?php esc_html_e('AI-Stats:', 'ai-stats'); ?></strong>
                <?php esc_html_e('This plugin requires AI-Core to be installed and activated.', 'ai-stats'); ?>
                <a href="<?php echo esc_url(admin_url('plugin-install.php?s=ai-core&tab=search&type=term')); ?>">
                    <?php esc_html_e('Install AI-Core', 'ai-stats'); ?>
                </a>
            </p>
        </div>
        <?php
    }
    
    /**
     * Show configuration notice
     * 
     * @return void
     */
    public function show_configuration_notice() {
        ?>
        <div class="notice notice-warning">
            <p>
                <strong><?php esc_html_e('AI-Stats:', 'ai-stats'); ?></strong>
                <?php esc_html_e('Please configure AI-Core with at least one API key to use this plugin.', 'ai-stats'); ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=ai-core-settings')); ?>">
                    <?php esc_html_e('Configure AI-Core', 'ai-stats'); ?>
                </a>
            </p>
        </div>
        <?php
    }
    
    /**
     * Load plugin dependencies
     *
     * @return void
     */
    private function load_dependencies() {
        // Load core classes
        require_once AI_STATS_PLUGIN_DIR . 'includes/class-ai-stats-settings.php';
        require_once AI_STATS_PLUGIN_DIR . 'includes/class-ai-stats-database.php';
        require_once AI_STATS_PLUGIN_DIR . 'includes/class-ai-stats-source-registry.php';
        require_once AI_STATS_PLUGIN_DIR . 'includes/class-ai-stats-adapters.php';
        require_once AI_STATS_PLUGIN_DIR . 'includes/class-ai-stats-scraper.php';
        require_once AI_STATS_PLUGIN_DIR . 'includes/class-ai-stats-generator.php';
        require_once AI_STATS_PLUGIN_DIR . 'includes/class-ai-stats-modes.php';
        require_once AI_STATS_PLUGIN_DIR . 'includes/class-ai-stats-shortcode.php';
        require_once AI_STATS_PLUGIN_DIR . 'includes/class-ai-stats-scheduler.php';
        
        // Load admin classes
        if (is_admin()) {
            require_once AI_STATS_PLUGIN_DIR . 'admin/class-ai-stats-admin.php';
            require_once AI_STATS_PLUGIN_DIR . 'admin/class-ai-stats-ajax.php';
        }
    }
    
    /**
     * Initialize WordPress hooks
     * 
     * @return void
     */
    private function init_hooks() {
        // Activation/deactivation hooks
        register_activation_hook(AI_STATS_PLUGIN_FILE, array($this, 'activate'));
        register_deactivation_hook(AI_STATS_PLUGIN_FILE, array($this, 'deactivate'));
        
        // Plugin loaded hook
        add_action('plugins_loaded', array($this, 'plugins_loaded'));
        
        // Admin hooks
        if (is_admin()) {
            add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_assets'));
            add_filter('plugin_action_links_' . AI_STATS_PLUGIN_BASENAME, array($this, 'add_action_links'));
        }
        
        // Frontend hooks
        add_action('wp_enqueue_scripts', array($this, 'enqueue_frontend_assets'));
        
        // Initialize components
        add_action('init', array($this, 'init_components'));
    }
    
    /**
     * Plugin activation
     *
     * @return void
     */
    public function activate() {
        // Create database tables
        AI_Stats_Database::create_tables();

        // Get AI-Core default provider and model
        $ai_core_settings = get_option('ai_core_settings', array());
        $default_provider = $ai_core_settings['default_provider'] ?? 'openai';
        $provider_models = $ai_core_settings['provider_models'] ?? array();
        $default_model = $provider_models[$default_provider] ?? '';

        // Set default settings
        $default_settings = array(
            'active_mode' => 'statistics',
            'update_frequency' => 'daily',
            'auto_update' => false,
            'default_style' => 'box',
            'enable_caching' => true,
            'cache_duration' => 86400, // 24 hours
            'enable_tracking' => true,
            'birmingham_focus' => true,
            'preferred_provider' => $default_provider,
            'preferred_model' => $default_model,
            'version' => AI_STATS_VERSION,
        );

        add_option('ai_stats_settings', $default_settings);

        // Schedule cron job
        if (!wp_next_scheduled('ai_stats_daily_update')) {
            wp_schedule_event(time(), 'daily', 'ai_stats_daily_update');
        }
    }
    
    /**
     * Plugin deactivation
     * 
     * @return void
     */
    public function deactivate() {
        // Clear scheduled cron jobs
        wp_clear_scheduled_hook('ai_stats_daily_update');
        wp_clear_scheduled_hook('ai_stats_weekly_update');
    }
    
    /**
     * Plugins loaded hook
     *
     * @return void
     */
    public function plugins_loaded() {
        // Load text domain for translations
        load_plugin_textdomain('ai-stats', false, dirname(AI_STATS_PLUGIN_BASENAME) . '/languages');

        // Check if version has changed and clear registry cache
        $settings = get_option('ai_stats_settings', array());
        $stored_version = isset($settings['version']) ? $settings['version'] : '0.0.0';

        if (version_compare($stored_version, AI_STATS_VERSION, '<')) {
            // Version has been updated - clear the source registry cache
            delete_option('ai_stats_source_registry');

            // Update stored version
            $settings['version'] = AI_STATS_VERSION;
            update_option('ai_stats_settings', $settings);

            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log(sprintf('AI-Stats: Updated from v%s to v%s - cleared source registry cache', $stored_version, AI_STATS_VERSION));
            }
        }
    }
    
    /**
     * Initialize plugin components
     * 
     * @return void
     */
    public function init_components() {
        // Initialize settings
        AI_Stats_Settings::get_instance();
        
        // Initialize shortcode
        AI_Stats_Shortcode::get_instance();
        
        // Initialize scheduler
        AI_Stats_Scheduler::get_instance();
        
        // Initialize admin (if in admin area)
        if (is_admin()) {
            AI_Stats_Admin::get_instance();
            AI_Stats_Ajax::get_instance();
        }
    }
    
    /**
     * Enqueue admin assets
     *
     * @param string $hook Current admin page hook
     * @return void
     */
    public function enqueue_admin_assets($hook) {
        // Only load on AI-Stats pages
        if (strpos($hook, 'ai-stats') === false) {
            return;
        }

        // Cache busting: Use version + file modification time
        $css_version = AI_STATS_VERSION . '.' . filemtime(AI_STATS_PLUGIN_DIR . 'assets/css/admin.css');
        $js_version = AI_STATS_VERSION . '.' . filemtime(AI_STATS_PLUGIN_DIR . 'assets/js/admin.js');

        // Enqueue admin CSS
        wp_enqueue_style(
            'ai-stats-admin',
            AI_STATS_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            $css_version
        );

        // Enqueue admin JS
        wp_enqueue_script(
            'ai-stats-admin',
            AI_STATS_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            $js_version,
            true
        );

        // Localize script with AJAX data
        wp_localize_script('ai-stats-admin', 'aiStatsAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_stats_admin'),
            'version' => AI_STATS_VERSION,
            'strings' => array(
                'confirmReset' => __('Are you sure you want to reset all statistics?', 'ai-stats'),
                'confirmDelete' => __('Are you sure you want to delete this content?', 'ai-stats'),
                'generating' => __('Generating...', 'ai-stats'),
                'success' => __('Success!', 'ai-stats'),
                'error' => __('Error occurred', 'ai-stats'),
            ),
        ));
    }
    
    /**
     * Enqueue frontend assets
     *
     * @return void
     */
    public function enqueue_frontend_assets() {
        // Cache busting: Use version + file modification time
        $css_version = AI_STATS_VERSION . '.' . filemtime(AI_STATS_PLUGIN_DIR . 'assets/css/frontend.css');

        // Enqueue frontend CSS
        wp_enqueue_style(
            'ai-stats-frontend',
            AI_STATS_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            $css_version
        );
    }
    
    /**
     * Add plugin action links
     * 
     * @param array $links Existing action links
     * @return array Modified action links
     */
    public function add_action_links($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=ai-stats-settings') . '">' . __('Settings', 'ai-stats') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }
}

// Initialize plugin
AI_Stats::get_instance();

