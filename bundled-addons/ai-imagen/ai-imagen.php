<?php
/**
 * Plugin Name: AI-Imagen - AI Image Generation
 * Plugin URI: https://opace.agency/ai-imagen
 * Description: Professional AI-powered image generation for WordPress. Create stunning visuals with OpenAI DALL-E, Google Gemini Imagen, and xAI Grok. Seamlessly integrates with AI-Core for unified API management.
 * Version: 0.4.6
 * Author: Opace Digital Agency
 * Author URI: https://opace.agency
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: ai-imagen
 * Domain Path: /languages
 * Requires at least: 5.0
 * Tested up to: 6.8.1
 * Requires PHP: 7.4
 * Network: false
 * Tags: ai, image generation, dall-e, gemini, imagen, grok, openai, google, xai
 *
 * @package AI_Imagen
 * @version 0.4.6
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('AI_IMAGEN_VERSION', '0.4.6');
define('AI_IMAGEN_PLUGIN_FILE', __FILE__);
define('AI_IMAGEN_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AI_IMAGEN_PLUGIN_URL', plugin_dir_url(__FILE__));
define('AI_IMAGEN_PLUGIN_BASENAME', plugin_basename(__FILE__));

/**
 * Main AI-Imagen Plugin Class
 * 
 * Singleton pattern implementation
 */
class AI_Imagen {
    
    /**
     * Plugin instance
     * 
     * @var AI_Imagen
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
     * @return AI_Imagen
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
            return;
        }
        
        $this->ai_core = ai_core();
        
        if (!$this->ai_core->is_configured()) {
            add_action('admin_notices', array($this, 'show_configuration_notice'));
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
                <strong><?php esc_html_e('AI-Imagen:', 'ai-imagen'); ?></strong>
                <?php esc_html_e('This plugin requires AI-Core to be installed and activated.', 'ai-imagen'); ?>
                <a href="<?php echo esc_url(admin_url('plugin-install.php?s=ai-core&tab=search&type=term')); ?>">
                    <?php esc_html_e('Install AI-Core', 'ai-imagen'); ?>
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
        <div class="notice notice-warning is-dismissible">
            <p>
                <strong><?php esc_html_e('AI-Imagen:', 'ai-imagen'); ?></strong>
                <?php esc_html_e('Please configure your AI provider API keys in AI-Core settings.', 'ai-imagen'); ?>
                <a href="<?php echo esc_url(admin_url('admin.php?page=ai-core-settings')); ?>">
                    <?php esc_html_e('Configure Now', 'ai-imagen'); ?>
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
        // Core classes
        require_once AI_IMAGEN_PLUGIN_DIR . 'includes/class-ai-imagen-generator.php';
        require_once AI_IMAGEN_PLUGIN_DIR . 'includes/class-ai-imagen-settings.php';
        require_once AI_IMAGEN_PLUGIN_DIR . 'includes/class-ai-imagen-media.php';
        require_once AI_IMAGEN_PLUGIN_DIR . 'includes/class-ai-imagen-stats.php';
        require_once AI_IMAGEN_PLUGIN_DIR . 'includes/class-ai-imagen-prompts.php';
        
        // Admin classes
        if (is_admin()) {
            require_once AI_IMAGEN_PLUGIN_DIR . 'admin/class-ai-imagen-admin.php';
            require_once AI_IMAGEN_PLUGIN_DIR . 'admin/class-ai-imagen-ajax.php';
        }
    }
    
    /**
     * Initialize WordPress hooks
     * 
     * @return void
     */
    private function init_hooks() {
        // Activation, deactivation, and uninstall hooks
        register_activation_hook(__FILE__, array($this, 'activate'));
        register_deactivation_hook(__FILE__, array($this, 'deactivate'));
        
        // Plugin loaded hook
        add_action('plugins_loaded', array($this, 'plugins_loaded'));
        
        // Admin init
        if (is_admin()) {
            add_action('admin_init', array($this, 'admin_init'));
            add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
            
            // Initialize admin class
            AI_Imagen_Admin::get_instance();
        }
        
        // Add settings link on plugins page
        add_filter('plugin_action_links_' . AI_IMAGEN_PLUGIN_BASENAME, array($this, 'add_action_links'));
    }
    
    /**
     * Plugin activation
     * 
     * @return void
     */
    public function activate() {
        // Set default options
        $default_settings = array(
            'default_quality' => 'standard',
            'default_format' => 'png',
            'default_aspect_ratio' => '1:1',
            'default_background' => 'opaque',
            'auto_save_to_library' => true,
            'generation_limit' => 0, // 0 = unlimited
            'enable_scene_builder' => true,
            'enable_prompt_enhancement' => true,
        );
        
        add_option('ai_imagen_settings', $default_settings);
        add_option('ai_imagen_version', AI_IMAGEN_VERSION);
        
        // Install prompt library templates
        $this->install_prompt_templates();
        
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Plugin deactivation
     * 
     * @return void
     */
    public function deactivate() {
        // Flush rewrite rules
        flush_rewrite_rules();
    }
    
    /**
     * Install prompt library templates
     *
     * @return void
     */
    private function install_prompt_templates() {
        // Always try to install templates (the method itself checks for duplicates)
        // Don't check for ai_core() here as it might not be loaded during activation
        AI_Imagen_Prompts::install_templates();

        // Mark as installed
        update_option('ai_imagen_prompts_installed', true);
    }
    
    /**
     * Plugins loaded hook
     *
     * @return void
     */
    public function plugins_loaded() {
        // Try to install prompts again if they weren't installed during activation
        // This handles the case where AI-Core wasn't loaded during activation
        if (!get_option('ai_imagen_prompts_installed', false)) {
            $this->install_prompt_templates();
        }

        // Load text domain
        load_plugin_textdomain('ai-imagen', false, dirname(AI_IMAGEN_PLUGIN_BASENAME) . '/languages');
    }
    
    /**
     * Admin init hook
     * 
     * @return void
     */
    public function admin_init() {
        // Initialize settings
        AI_Imagen_Settings::get_instance();
    }
    
    /**
     * Enqueue admin scripts and styles
     *
     * @param string $hook Current admin page hook
     * @return void
     */
    public function admin_enqueue_scripts($hook) {
        // Only load on AI-Imagen pages
        if (strpos($hook, 'ai-imagen') === false) {
            return;
        }

        // Enqueue WordPress media uploader
        wp_enqueue_media();

        // Enqueue styles
        wp_enqueue_style(
            'ai-imagen-admin',
            AI_IMAGEN_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            AI_IMAGEN_VERSION
        );

        wp_enqueue_style(
            'ai-imagen-generator',
            AI_IMAGEN_PLUGIN_URL . 'assets/css/generator.css',
            array('ai-imagen-admin'),
            AI_IMAGEN_VERSION
        );

        // Enqueue scripts
        wp_enqueue_script(
            'ai-imagen-admin',
            AI_IMAGEN_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            AI_IMAGEN_VERSION,
            true
        );

        wp_enqueue_script(
            'ai-imagen-generator',
            AI_IMAGEN_PLUGIN_URL . 'assets/js/generator.js',
            array('jquery', 'ai-imagen-admin'),
            AI_IMAGEN_VERSION,
            true
        );

        wp_enqueue_script(
            'ai-imagen-scene-builder',
            AI_IMAGEN_PLUGIN_URL . 'assets/js/scene-builder.js',
            array('jquery', 'ai-imagen-generator', 'media-upload', 'media-views'),
            AI_IMAGEN_VERSION,
            true
        );

        // Localize script
        wp_localize_script('ai-imagen-admin', 'aiImagenData', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_imagen_admin'),
            'version' => AI_IMAGEN_VERSION,
            'plugin_url' => AI_IMAGEN_PLUGIN_URL,
            'strings' => array(
                'generating' => __('Generating image...', 'ai-imagen'),
                'success' => __('Image generated successfully!', 'ai-imagen'),
                'error' => __('Error generating image. Please try again.', 'ai-imagen'),
                'saved' => __('Image saved to media library!', 'ai-imagen'),
                'confirm_delete' => __('Are you sure you want to delete this image?', 'ai-imagen'),
            ),
        ));
    }
    
    /**
     * Add action links to plugins page
     * 
     * @param array $links Existing links
     * @return array Modified links
     */
    public function add_action_links($links) {
        $plugin_links = array(
            '<a href="' . admin_url('admin.php?page=ai-imagen') . '">' . __('Generate', 'ai-imagen') . '</a>',
            '<a href="' . admin_url('admin.php?page=ai-imagen-settings') . '">' . __('Settings', 'ai-imagen') . '</a>',
        );
        
        return array_merge($plugin_links, $links);
    }
    
    /**
     * Get AI-Core instance
     * 
     * @return AI_Core_API|null
     */
    public function get_ai_core() {
        return $this->ai_core;
    }
}

// Initialize plugin
function ai_imagen() {
    return AI_Imagen::get_instance();
}

// Start the plugin
ai_imagen();

