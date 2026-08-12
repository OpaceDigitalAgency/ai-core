<?php
/**
 * AI-Pulse Admin Interface
 *
 * @package AI_Pulse
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin interface class
 */
class AI_Pulse_Admin {

    /**
     * Initialise admin
     */
    public static function init() {
        add_action('admin_menu', array(__CLASS__, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array(__CLASS__, 'enqueue_admin_assets'));
    }

    /**
     * Add admin menu
     */
    public static function add_admin_menu() {
        add_submenu_page(
            'ai-core',
            'AI-Pulse',
            'AI-Pulse',
            'manage_options',
            'ai-pulse',
            array(__CLASS__, 'render_admin_page')
        );
    }

    /**
     * Enqueue admin assets
     *
     * @param string $hook Current admin page hook
     */
    public static function enqueue_admin_assets($hook) {
        if ($hook !== 'ai-core_page_ai-pulse') {
            return;
        }

        wp_enqueue_style(
            'ai-pulse-admin',
            AI_PULSE_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            AI_PULSE_VERSION
        );

        wp_enqueue_script(
            'ai-pulse-admin',
            AI_PULSE_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            AI_PULSE_VERSION,
            true
        );

        wp_localize_script('ai-pulse-admin', 'aiPulseAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_pulse_admin'),
            'modes' => AI_Pulse_Modes::get_all_modes()
        ));
    }

    /**
     * Render admin page
     */
    public static function render_admin_page() {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorised access');
        }

        // Handle form submissions
        if (isset($_POST['ai_pulse_action'])) {
            check_admin_referer('ai_pulse_settings');
            self::handle_form_submission();
        }

        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'test';

        include AI_PULSE_PLUGIN_DIR . 'admin/views/settings-page.php';
    }

    /**
     * Handle form submission
     */
    private static function handle_form_submission() {
        $action = sanitize_text_field($_POST['ai_pulse_action']);

        switch ($action) {
            case 'save_settings':
                self::save_general_settings();
                break;
            case 'save_keyword':
                self::save_keyword();
                break;
            case 'delete_keyword':
                self::delete_keyword();
                break;
            case 'save_schedule':
                self::save_schedule_settings();
                break;
            case 'create_tables':
                check_admin_referer('ai_pulse_create_tables');
                AI_Pulse_Database::create_tables();
                add_settings_error('ai_pulse', 'tables_created', 'Database tables created successfully', 'success');
                break;
        }
    }

    /**
     * Save general settings
     */
    private static function save_general_settings() {
        $settings = array(
            'default_period' => sanitize_text_field($_POST['default_period']),
            'default_location' => sanitize_text_field($_POST['default_location']),
            'cache_duration' => intval($_POST['cache_duration']),
            'enable_debug' => isset($_POST['enable_debug']),
        );

        foreach ($settings as $key => $value) {
            AI_Pulse_Settings::set($key, $value);
        }

        add_settings_error('ai_pulse', 'settings_saved', 'Settings saved successfully', 'success');
    }

    /**
     * Save keyword
     */
    private static function save_keyword() {
        $keyword = sanitize_text_field($_POST['keyword']);
        $modes = isset($_POST['modes']) ? array_map('sanitize_text_field', $_POST['modes']) : array('SUMMARY');
        $period = sanitize_text_field($_POST['period']);

        AI_Pulse_Settings::save_keyword($keyword, array(
            'modes' => $modes,
            'period' => $period
        ));

        add_settings_error('ai_pulse', 'keyword_saved', 'Keyword saved successfully', 'success');
    }

    /**
     * Delete keyword
     */
    private static function delete_keyword() {
        $keyword = sanitize_text_field($_POST['keyword']);
        AI_Pulse_Settings::delete_keyword($keyword);
        add_settings_error('ai_pulse', 'keyword_deleted', 'Keyword deleted successfully', 'success');
    }

    /**
     * Save schedule settings
     */
    private static function save_schedule_settings() {
        $settings = array(
            'update_interval' => sanitize_text_field($_POST['update_interval']),
            'start_time' => sanitize_text_field($_POST['start_time']),
            'gradual_rollout_enabled' => isset($_POST['gradual_rollout_enabled']),
            'delay_between_requests' => intval($_POST['delay_between_requests']),
        );

        foreach ($settings as $key => $value) {
            AI_Pulse_Settings::set($key, $value);
        }

        // Reschedule
        AI_Pulse_Scheduler::schedule();

        add_settings_error('ai_pulse', 'schedule_saved', 'Schedule settings saved and updated', 'success');
    }
}

