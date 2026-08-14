<?php
/**
 * Plugin Name: Opace AI Core Integration Hub Prompt Engine
 * Plugin URI: https://opace.agency/services/web-design/wordpress-development/
 * Description: Connect WordPress plugins to OpenAI, Anthropic Claude and Google Gemini with shared credentials, live models, prompts and usage records.
 * Version: 1.0.0
 * Author: Opace Digital Agency
 * Author URI: https://opace.agency
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: opace-ai-core-integration-hub-prompt-engine
 * Domain Path: /languages
 * Requires at least: 6.5
 * Tested up to: 7.0
 * Requires PHP: 7.4
 * Tags: ai, openai, claude, gemini, api, integration, artificial intelligence
 *
 * @package AI_Core
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants. Guarded because an add-on bundling this library can
// already be loaded when AI-Core is activated, which otherwise emits a
// "Constant already defined" warning immediately before the redeclare fatal.
if (!defined('AI_CORE_VERSION')) {
    define('AI_CORE_VERSION', '1.0.0');
}
if (!defined('AI_CORE_PLUGIN_FILE')) {
    define('AI_CORE_PLUGIN_FILE', __FILE__);
    define('AI_CORE_PLUGIN_DIR', plugin_dir_path(__FILE__));
    define('AI_CORE_PLUGIN_URL', plugin_dir_url(__FILE__));
    define('AI_CORE_PLUGIN_BASENAME', plugin_basename(__FILE__));
}

// Minimum PHP version check
if (version_compare(PHP_VERSION, '7.4', '<')) {
    add_action('admin_notices', function() {
        echo '<div class="notice notice-error"><p>';
        echo '<strong>AI-Core:</strong> This plugin requires PHP 7.4 or higher. You are running PHP ' . esc_html(PHP_VERSION);
        echo '</p></div>';
    });
    return;
}

/**
 * Main AI-Core Plugin Class
 * 
 * Handles plugin initialization, activation, and deactivation
 * Provides centralized AI provider management for WordPress
 */
class AI_Core_Plugin {
    
    /**
     * Plugin instance
     * 
     * @var AI_Core_Plugin
     */
    private static $instance = null;
    
    /**
     * Get plugin instance (Singleton pattern)
     * 
     * @return AI_Core_Plugin
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
     * Initialize the plugin
     * 
     * @return void
     */
    private function init() {
        // Load AI-Core library
        $this->load_ai_core_library();
        
        // Load plugin files
        $this->load_includes();
        
        // Initialize hooks
        $this->init_hooks();
    }
    
    /**
     * Load AI-Core library
     *
     * @return void
     */
    private function load_ai_core_library() {
        $ai_core_autoload = AI_CORE_PLUGIN_DIR . 'lib/autoload.php';

        if (file_exists($ai_core_autoload)) {
            require_once $ai_core_autoload;
        } else {
            add_action('admin_notices', function() {
                echo '<div class="notice notice-error"><p>';
                echo '<strong>AI-Core:</strong> Core library not found. Please reinstall the plugin.';
                echo '</p></div>';
            });
        }
    }
    
    /**
     * Load plugin includes
     *
     * @return void
     */
    private function load_includes() {
        // Core functionality
        require_once AI_CORE_PLUGIN_DIR . 'includes/class-ai-core-pricing.php';
        require_once AI_CORE_PLUGIN_DIR . 'includes/class-ai-core-model-defaults.php';
        require_once AI_CORE_PLUGIN_DIR . 'includes/class-ai-core-settings.php';
        require_once AI_CORE_PLUGIN_DIR . 'includes/class-ai-core-api.php';
        require_once AI_CORE_PLUGIN_DIR . 'includes/class-ai-core-validator.php';
        require_once AI_CORE_PLUGIN_DIR . 'includes/class-ai-core-stats.php';

        // Admin functionality
        if (is_admin()) {
            require_once AI_CORE_PLUGIN_DIR . 'admin/class-ai-core-admin.php';
            require_once AI_CORE_PLUGIN_DIR . 'admin/class-ai-core-ajax.php';
            require_once AI_CORE_PLUGIN_DIR . 'admin/class-ai-core-addons.php';
            require_once AI_CORE_PLUGIN_DIR . 'admin/class-ai-core-prompt-library.php';
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

            // Initialize admin class (which will add its own admin_menu hook)
            AI_Core_Admin::get_instance();

            // Initialize add-ons class (registers AJAX handlers)
            AI_Core_Addons::get_instance();
        }
        
        // Add settings link on plugins page
        add_filter('plugin_action_links_' . AI_CORE_PLUGIN_BASENAME, array($this, 'add_action_links'));
    }
    
    /**
     * Plugin activation
     *
     * @return void
     */
    public function activate() {
        // Set default options
        $default_settings = array(
            'openai_api_key' => '',
            'anthropic_api_key' => '',
            'gemini_api_key' => '',
            'default_provider' => 'openai',
            'enable_stats' => true,
            'enable_caching' => true,
            'cache_duration' => 3600,
            'persist_on_uninstall' => true,
            'provider_models' => array(),
            'provider_options' => array(),
        );

        add_option('ai_core_settings', $default_settings);
        add_option('ai_core_stats', array());
        add_option('ai_core_version', AI_CORE_VERSION);

        // Create database tables for Prompt Library
        $this->create_prompt_library_tables();

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Create database tables for Prompt Library
     *
     * @return void
     */
    private function create_prompt_library_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        // Prompt groups table
        $groups_table = $wpdb->prefix . 'ai_core_prompt_groups';
        $groups_sql = "CREATE TABLE {$groups_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL,
            description text,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY name (name)
        ) {$charset_collate};";

        // Prompts table
        $prompts_table = $wpdb->prefix . 'ai_core_prompts';
        $prompts_sql = "CREATE TABLE {$prompts_table} (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            title varchar(255) NOT NULL,
            content longtext NOT NULL,
            group_id bigint(20) unsigned DEFAULT NULL,
            provider varchar(50) DEFAULT '',
            type varchar(50) DEFAULT 'text',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY  (id),
            KEY group_id (group_id),
            KEY type (type),
            KEY provider (provider)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($groups_sql);
        dbDelta($prompts_sql);

        // Add default group if none exists
        // Direct access is required for the plugin-owned custom table during activation.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $count = $wpdb->get_var($wpdb->prepare('SELECT COUNT(*) FROM %i', $groups_table));
        if ($count == 0) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- plugin-owned custom table.
            $wpdb->insert(
                $groups_table,
                array(
                    'name' => __('General', 'opace-ai-core-integration-hub-prompt-engine'),
                    'description' => __('General purpose prompts', 'opace-ai-core-integration-hub-prompt-engine'),
                ),
                array('%s', '%s')
            );
        }
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
     * Plugins loaded hook
     * 
     * @return void
     */
    public function plugins_loaded() {
        // No load_plugin_textdomain() call: WordPress has auto-loaded
        // translations for plugins hosted on wordpress.org since 4.6, and the
        // explicit call is flagged as discouraged by Plugin Check.

        // Initialize AI-Core library with saved settings
        $this->initialize_ai_core();
    }
    
    /**
     * Initialize AI-Core library with saved settings
     * 
     * @return void
     */
    private function initialize_ai_core() {
        $settings = get_option('ai_core_settings', array());
        
        // Initialize AI-Core with all configured API keys
        if (class_exists('AICore\\AICore')) {
            $config = array();
            
            if (!empty($settings['openai_api_key'])) {
                $config['openai_api_key'] = $settings['openai_api_key'];
            }
            
            if (!empty($settings['anthropic_api_key'])) {
                $config['anthropic_api_key'] = $settings['anthropic_api_key'];
            }
            
            if (!empty($settings['gemini_api_key'])) {
                $config['gemini_api_key'] = $settings['gemini_api_key'];
            }
            
            // Initialize AI-Core
            \AICore\AICore::init($config);
        }
    }
    
    /**
     * Admin init hook
     *
     * @return void
     */
    public function admin_init() {
        // Initialize settings
        AI_Core_Settings::get_instance();
    }

    /**
     * Enqueue admin scripts and styles
     *
     * @param string $hook Current admin page hook
     * @return void
     */
    public function admin_enqueue_scripts($hook) {
        // Only load on AI-Core admin pages and AI-Imagen pages (bundled addon)
        if (strpos($hook, 'ai-core') === false && strpos($hook, 'ai-imagen') === false) {
            return;
        }

        // Enqueue styles
        wp_enqueue_style(
            'ai-core-admin',
            AI_CORE_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            AI_CORE_VERSION
        );

        // Apply the stored theme choice before first paint. The preference is
        // shared with AI-Scribe's wizard toggle (localStorage key
        // "ai-scribe-theme"), so both plugins follow one setting; without a
        // stored choice the stylesheet's prefers-color-scheme default stands.
        add_action('admin_head', static function () {
            echo '<script>try{var t=window.localStorage.getItem("ai-scribe-theme");if(t==="dark"||t==="light"){document.documentElement.setAttribute("data-theme",t);}}catch(e){}</script>';
        });

        // Enqueue scripts
        wp_enqueue_script(
            'ai-core-admin',
            AI_CORE_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            AI_CORE_VERSION,
            true
        );

        $settings = get_option('ai_core_settings', array());
        $api = AI_Core_API::get_instance();
        $configured_providers = $api->get_configured_providers();
        $default_provider = $settings['default_provider'] ?? '';
        $provider_labels = array(
            'openai' => __('OpenAI', 'opace-ai-core-integration-hub-prompt-engine'),
            'anthropic' => __('Anthropic Claude', 'opace-ai-core-integration-hub-prompt-engine'),
            'gemini' => __('Google Gemini', 'opace-ai-core-integration-hub-prompt-engine'),
        );

        $provider_models_map = array();
        foreach ($provider_labels as $provider_key => $provider_label) {
            if (!empty($settings[$provider_key . '_api_key'])) {
                $provider_models_map[$provider_key] = $api->get_available_models($provider_key);
            } else {
                $provider_models_map[$provider_key] = array();
            }
        }

        $provider_selected_models = isset($settings['provider_models']) && is_array($settings['provider_models']) ? $settings['provider_models'] : array();
        $provider_options = isset($settings['provider_options']) && is_array($settings['provider_options']) ? $settings['provider_options'] : array();
        $provider_metadata = class_exists('AICore\\Registry\\ModelRegistry') ? \AICore\Registry\ModelRegistry::exportProviderMetadata() : array();

        // Prepare localization data
        $localize_data = array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('ai_core_admin'),
            'strings' => array(
                'testing' => __('Testing...', 'opace-ai-core-integration-hub-prompt-engine'),
                'success' => __('Success!', 'opace-ai-core-integration-hub-prompt-engine'),
                'error' => __('Error', 'opace-ai-core-integration-hub-prompt-engine'),
                'validating' => __('Validating...', 'opace-ai-core-integration-hub-prompt-engine'),
                'rememberToSave' => __('Remember to click Save to store this key.', 'opace-ai-core-integration-hub-prompt-engine'),
                'confirmResetStats' => __('Are you sure you want to reset all usage statistics? This cannot be undone.', 'opace-ai-core-integration-hub-prompt-engine'),
                'pasteKeyToTest' => __('A key is saved for this provider. Paste it again to re-test it.', 'opace-ai-core-integration-hub-prompt-engine'),
                'loadingModels' => __('Loading models...', 'opace-ai-core-integration-hub-prompt-engine'),
                'noModels' => __('No models available', 'opace-ai-core-integration-hub-prompt-engine'),
                'errorLoadingModels' => __('Failed to load models.', 'opace-ai-core-integration-hub-prompt-engine'),
                'placeholderSelectModel' => __('-- Select Model --', 'opace-ai-core-integration-hub-prompt-engine'),
                /* translators: %d: number of models available. */
                'availableModels' => __('Available Models (%d):', 'opace-ai-core-integration-hub-prompt-engine'),
                'missingKey' => __('Enter an API key to load models.', 'opace-ai-core-integration-hub-prompt-engine'),
                'awaitingKey' => __('Waiting for key...', 'opace-ai-core-integration-hub-prompt-engine'),
                'keyTooShort' => __('Continue pasting your key to validate.', 'opace-ai-core-integration-hub-prompt-engine'),
                'saving' => __('Saving key...', 'opace-ai-core-integration-hub-prompt-engine'),
                'saved' => __('Key saved successfully.', 'opace-ai-core-integration-hub-prompt-engine'),
                'alreadySaved' => __('This key is already saved.', 'opace-ai-core-integration-hub-prompt-engine'),
                'enterKeyPlaceholder' => __('Enter your API key', 'opace-ai-core-integration-hub-prompt-engine'),
                'refreshing' => __('Refreshing models...', 'opace-ai-core-integration-hub-prompt-engine'),
                'refreshingPricing' => __('Refreshing pricing...', 'opace-ai-core-integration-hub-prompt-engine'),
                'retentionKeep' => __('Current choice: keep all AI-Core data after deletion.', 'opace-ai-core-integration-hub-prompt-engine'),
                'retentionDelete' => __('Current choice: permanently remove all AI-Core data when deleted.', 'opace-ai-core-integration-hub-prompt-engine'),
                'modelsLoaded' => __('Models updated.', 'opace-ai-core-integration-hub-prompt-engine'),
                'cleared' => __('API key cleared.', 'opace-ai-core-integration-hub-prompt-engine'),
                'connected' => __('Connected', 'opace-ai-core-integration-hub-prompt-engine'),
                'awaiting' => __('Awaiting API Key', 'opace-ai-core-integration-hub-prompt-engine'),
                'addKeyFirst' => __('Add an API key to load models', 'opace-ai-core-integration-hub-prompt-engine'),
                'testSelectProvider' => __('Select a provider first', 'opace-ai-core-integration-hub-prompt-engine'),
                'promptRequired' => __('Please enter a prompt.', 'opace-ai-core-integration-hub-prompt-engine'),
                'providerRequired' => __('Please select a provider.', 'opace-ai-core-integration-hub-prompt-engine'),
                'modelRequired' => __('Please select a model.', 'opace-ai-core-integration-hub-prompt-engine'),
                'runningPrompt' => __('Running prompt...', 'opace-ai-core-integration-hub-prompt-engine'),
                'confirmClear' => __('Are you sure you want to clear this API key?', 'opace-ai-core-integration-hub-prompt-engine'),
                'savedPlaceholder' => __('Saved key (hidden)', 'opace-ai-core-integration-hub-prompt-engine'),
                'clearKey' => __('Clear', 'opace-ai-core-integration-hub-prompt-engine'),
                'testKey' => __('Test Key', 'opace-ai-core-integration-hub-prompt-engine'),
                'noTuningParameters' => __('No adjustable parameters for this model.', 'opace-ai-core-integration-hub-prompt-engine'),
                'selectModelFirst' => __('Select a model to view available settings.', 'opace-ai-core-integration-hub-prompt-engine'),
                'toggleTheme' => __('Toggle dark mode', 'opace-ai-core-integration-hub-prompt-engine'),
            ),
            'providers' => array(
                'configured' => $configured_providers,
                'default' => $default_provider,
                'labels' => $provider_labels,
                'models' => $provider_models_map,
                'selectedModels' => $provider_selected_models,
                'options' => $provider_options,
                'meta' => $provider_metadata,
            ),
        );

        // Localize to ai-core-admin script (always loaded)
        wp_localize_script('ai-core-admin', 'aiCoreAdmin', $localize_data);

        // Enqueue Prompt Library assets on its page
        if ($hook === 'ai-core_page_ai-core-prompt-library') {
            // Enqueue jQuery UI for drag and drop
            wp_enqueue_script('jquery-ui-sortable');
            wp_enqueue_script('jquery-ui-droppable');

            wp_enqueue_style(
                'ai-core-prompt-library',
                AI_CORE_PLUGIN_URL . 'assets/css/prompt-library.css',
                array('ai-core-admin'),
                AI_CORE_VERSION
            );

            wp_enqueue_script(
                'ai-core-prompt-library',
                AI_CORE_PLUGIN_URL . 'assets/js/prompt-library.js',
                array('jquery', 'jquery-ui-sortable', 'jquery-ui-droppable', 'ai-core-admin'),
                AI_CORE_VERSION,
                true
            );
        }
    }
    
    /**
     * Add action links to plugins page
     * 
     * @param array $links Existing links
     * @return array Modified links
     */
    public function add_action_links($links) {
        $settings_link = '<a href="' . admin_url('admin.php?page=ai-core-settings') . '">' . __('Settings', 'opace-ai-core-integration-hub-prompt-engine') . '</a>';
        $addons_link = '<a href="' . admin_url('admin.php?page=ai-core-addons') . '">' . __('Add-ons', 'opace-ai-core-integration-hub-prompt-engine') . '</a>';
        
        array_unshift($links, $settings_link, $addons_link);
        
        return $links;
    }
}

// Initialize the plugin
AI_Core_Plugin::get_instance();
