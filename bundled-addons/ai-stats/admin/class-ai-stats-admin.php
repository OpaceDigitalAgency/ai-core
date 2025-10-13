<?php
/**
 * AI-Stats Admin Class
 *
 * Manages admin interface and pages
 *
 * @package AI_Stats
 * @version 0.2.1
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin management class
 */
class AI_Stats_Admin {
    
    /**
     * Singleton instance
     * 
     * @var AI_Stats_Admin
     */
    private static $instance = null;
    
    /**
     * Get instance
     * 
     * @return AI_Stats_Admin
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
        add_action('admin_menu', array($this, 'add_admin_menu'));
    }
    
    /**
     * Add admin menu pages
     * 
     * @return void
     */
    public function add_admin_menu() {
        // Main menu page
        add_menu_page(
            __('AI-Stats', 'ai-stats'),
            __('AI-Stats', 'ai-stats'),
            'manage_options',
            'ai-stats',
            array($this, 'render_dashboard_page'),
            'dashicons-chart-bar',
            30
        );
        
        // Dashboard submenu
        add_submenu_page(
            'ai-stats',
            __('Dashboard', 'ai-stats'),
            __('Dashboard', 'ai-stats'),
            'manage_options',
            'ai-stats',
            array($this, 'render_dashboard_page')
        );
        
        // Settings submenu
        add_submenu_page(
            'ai-stats',
            __('Settings', 'ai-stats'),
            __('Settings', 'ai-stats'),
            'manage_options',
            'ai-stats-settings',
            array($this, 'render_settings_page')
        );

        // Content Library submenu
        add_submenu_page(
            'ai-stats',
            __('Content Library', 'ai-stats'),
            __('Content Library', 'ai-stats'),
            'manage_options',
            'ai-stats-library',
            array($this, 'render_library_page')
        );

        // Performance submenu
        add_submenu_page(
            'ai-stats',
            __('Performance', 'ai-stats'),
            __('Performance', 'ai-stats'),
            'manage_options',
            'ai-stats-performance',
            array($this, 'render_performance_page')
        );

        // Debug submenu
        add_submenu_page(
            'ai-stats',
            __('Debug & Diagnostics', 'ai-stats'),
            __('Debug', 'ai-stats'),
            'manage_options',
            'ai-stats-debug',
            array($this, 'render_debug_page')
        );
    }
    
    /**
     * Render dashboard page
     * 
     * @return void
     */
    public function render_dashboard_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'ai-stats'));
        }
        
        $settings = get_option('ai_stats_settings', array());
        $active_mode = $settings['active_mode'] ?? 'statistics';
        $modes = AI_Stats_Modes::get_modes();
        $current_content = AI_Stats_Database::get_active_content($active_mode);
        
        include AI_STATS_PLUGIN_DIR . 'admin/views/dashboard-page.php';
    }
    
    /**
     * Render settings page
     * 
     * @return void
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'ai-stats'));
        }
        
        // Handle form submission
        if (isset($_POST['ai_stats_settings_nonce']) && wp_verify_nonce($_POST['ai_stats_settings_nonce'], 'ai_stats_settings')) {
            $this->save_settings();
        }
        
        $settings = get_option('ai_stats_settings', array());
        $modes = AI_Stats_Modes::get_modes();
        
        include AI_STATS_PLUGIN_DIR . 'admin/views/settings-page.php';
    }
    
    /**
     * Render content library page
     * 
     * @return void
     */
    public function render_library_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'ai-stats'));
        }
        
        include AI_STATS_PLUGIN_DIR . 'admin/views/library-page.php';
    }
    
    /**
     * Render performance page
     *
     * @return void
     */
    public function render_performance_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'ai-stats'));
        }

        include AI_STATS_PLUGIN_DIR . 'admin/views/performance-page.php';
    }

    /**
     * Render debug page
     *
     * @return void
     */
    public function render_debug_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'ai-stats'));
        }

        include AI_STATS_PLUGIN_DIR . 'admin/views/debug-page.php';
    }
    
    /**
     * Save settings
     *
     * @return void
     */
    private function save_settings() {
        $new_settings = array(
            'active_mode' => isset($_POST['active_mode']) ? sanitize_text_field($_POST['active_mode']) : 'statistics',
            'update_frequency' => isset($_POST['update_frequency']) ? sanitize_text_field($_POST['update_frequency']) : 'daily',
            'auto_update' => isset($_POST['auto_update']) ? true : false,
            'default_style' => isset($_POST['default_style']) ? sanitize_text_field($_POST['default_style']) : 'box',
            'enable_caching' => isset($_POST['enable_caching']) ? true : false,
            'cache_duration' => isset($_POST['cache_duration']) ? absint($_POST['cache_duration']) : 86400,
            'enable_tracking' => isset($_POST['enable_tracking']) ? true : false,
            'birmingham_focus' => isset($_POST['birmingham_focus']) ? true : false,
            'google_api_key' => isset($_POST['google_api_key']) ? sanitize_text_field($_POST['google_api_key']) : '',
            'companies_house_api_key' => isset($_POST['companies_house_api_key']) ? sanitize_text_field($_POST['companies_house_api_key']) : '',
            'crux_test_url' => isset($_POST['crux_test_url']) ? esc_url_raw($_POST['crux_test_url']) : get_site_url(),
            'preferred_provider' => isset($_POST['preferred_provider']) ? sanitize_text_field($_POST['preferred_provider']) : 'openai',
        );

        $settings = AI_Stats_Settings::get_instance();
        $settings->update($new_settings);

        add_settings_error(
            'ai_stats_settings',
            'settings_updated',
            __('Settings saved successfully.', 'ai-stats'),
            'success'
        );
    }
}

