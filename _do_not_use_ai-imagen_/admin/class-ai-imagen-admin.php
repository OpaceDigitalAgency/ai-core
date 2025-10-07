<?php
/**
 * AI-Imagen Admin Class
 * 
 * Handles admin interface and menu pages
 * 
 * @package AI_Imagen
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AI-Imagen Admin Class
 */
class AI_Imagen_Admin {
    
    /**
     * Class instance
     * 
     * @var AI_Imagen_Admin
     */
    private static $instance = null;
    
    /**
     * Get class instance
     * 
     * @return AI_Imagen_Admin
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
        $this->init();
    }
    
    /**
     * Initialize admin
     * 
     * @return void
     */
    private function init() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
    }
    
    /**
     * Add admin menu pages
     * 
     * @return void
     */
    public function add_admin_menu() {
        // Main menu page - Generator
        add_menu_page(
            __('AI-Imagen', 'ai-imagen'),
            __('AI-Imagen', 'ai-imagen'),
            'manage_options',
            'ai-imagen',
            array($this, 'render_generator_page'),
            'dashicons-format-image',
            30
        );
        
        // Generator submenu (same as main)
        add_submenu_page(
            'ai-imagen',
            __('Generate Image', 'ai-imagen'),
            __('Generate', 'ai-imagen'),
            'manage_options',
            'ai-imagen',
            array($this, 'render_generator_page')
        );
        
        // Image History
        add_submenu_page(
            'ai-imagen',
            __('Image History', 'ai-imagen'),
            __('History', 'ai-imagen'),
            'manage_options',
            'ai-imagen-history',
            array($this, 'render_history_page')
        );
        
        // Statistics
        add_submenu_page(
            'ai-imagen',
            __('Statistics', 'ai-imagen'),
            __('Statistics', 'ai-imagen'),
            'manage_options',
            'ai-imagen-stats',
            array($this, 'render_stats_page')
        );
        
        // Settings
        add_submenu_page(
            'ai-imagen',
            __('Settings', 'ai-imagen'),
            __('Settings', 'ai-imagen'),
            'manage_options',
            'ai-imagen-settings',
            array($this, 'render_settings_page')
        );
    }
    
    /**
     * Render generator page
     * 
     * @return void
     */
    public function render_generator_page() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'ai-imagen'));
        }
        
        // Check if AI-Core is configured
        if (!function_exists('ai_core') || !ai_core()->is_configured()) {
            $this->render_not_configured_notice();
            return;
        }
        
        // Get available providers
        $generator = AI_Imagen_Generator::get_instance();
        $providers = $generator->get_available_providers();
        
        if (empty($providers)) {
            $this->render_no_providers_notice();
            return;
        }
        
        // Check generation limit
        $media = AI_Imagen_Media::get_instance();
        if ($media->is_limit_reached()) {
            $this->render_limit_reached_notice();
        }
        
        // Include generator view
        include AI_IMAGEN_PLUGIN_DIR . 'admin/views/generator-page.php';
    }
    
    /**
     * Render history page
     * 
     * @return void
     */
    public function render_history_page() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'ai-imagen'));
        }
        
        // Get generated images
        $media = AI_Imagen_Media::get_instance();
        $images = $media->get_generated_images();
        
        // Include history view
        include AI_IMAGEN_PLUGIN_DIR . 'admin/views/history-page.php';
    }
    
    /**
     * Render statistics page
     * 
     * @return void
     */
    public function render_stats_page() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'ai-imagen'));
        }
        
        // Get statistics
        $stats = AI_Imagen_Stats::get_stats();
        $summary = AI_Imagen_Stats::get_summary();
        $chart_data = AI_Imagen_Stats::get_chart_data(30);
        
        // Include stats view
        include AI_IMAGEN_PLUGIN_DIR . 'admin/views/stats-page.php';
    }
    
    /**
     * Render settings page
     * 
     * @return void
     */
    public function render_settings_page() {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.', 'ai-imagen'));
        }
        
        // Include settings view
        include AI_IMAGEN_PLUGIN_DIR . 'admin/views/settings-page.php';
    }
    
    /**
     * Render not configured notice
     * 
     * @return void
     */
    private function render_not_configured_notice() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('AI-Imagen', 'ai-imagen'); ?></h1>
            <div class="notice notice-warning">
                <p>
                    <strong><?php esc_html_e('AI-Core Not Configured', 'ai-imagen'); ?></strong>
                </p>
                <p>
                    <?php esc_html_e('Please configure your AI provider API keys in AI-Core settings before using AI-Imagen.', 'ai-imagen'); ?>
                </p>
                <p>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=ai-core-settings')); ?>" class="button button-primary">
                        <?php esc_html_e('Configure AI-Core', 'ai-imagen'); ?>
                    </a>
                </p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render no providers notice
     * 
     * @return void
     */
    private function render_no_providers_notice() {
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('AI-Imagen', 'ai-imagen'); ?></h1>
            <div class="notice notice-warning">
                <p>
                    <strong><?php esc_html_e('No Image Generation Providers Available', 'ai-imagen'); ?></strong>
                </p>
                <p>
                    <?php esc_html_e('None of your configured AI providers support image generation. Please configure OpenAI, Gemini, or xAI Grok in AI-Core settings.', 'ai-imagen'); ?>
                </p>
                <p>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=ai-core-settings')); ?>" class="button button-primary">
                        <?php esc_html_e('Configure Providers', 'ai-imagen'); ?>
                    </a>
                </p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render limit reached notice
     * 
     * @return void
     */
    private function render_limit_reached_notice() {
        ?>
        <div class="notice notice-error">
            <p>
                <strong><?php esc_html_e('Daily Generation Limit Reached', 'ai-imagen'); ?></strong>
            </p>
            <p>
                <?php esc_html_e('You have reached your daily image generation limit. Please try again tomorrow or increase your limit in settings.', 'ai-imagen'); ?>
            </p>
            <p>
                <a href="<?php echo esc_url(admin_url('admin.php?page=ai-imagen-settings')); ?>" class="button">
                    <?php esc_html_e('Adjust Settings', 'ai-imagen'); ?>
                </a>
            </p>
        </div>
        <?php
    }
}

