<?php
/**
 * AI-Core Add-ons Class
 * 
 * Handles add-ons library and discovery
 * 
 * @package AI_Core
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AI-Core Add-ons Class
 * 
 * Manages add-ons library
 */
class AI_Core_Addons {
    
    /**
     * Class instance
     * 
     * @var AI_Core_Addons
     */
    private static $instance = null;
    
    /**
     * Get class instance
     * 
     * @return AI_Core_Addons
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
        // Private constructor for singleton
        add_action('wp_ajax_ai_core_install_addon', array($this, 'ajax_install_addon'));
        add_action('wp_ajax_ai_core_activate_addon', array($this, 'ajax_activate_addon'));
        add_action('wp_ajax_ai_core_deactivate_addon', array($this, 'ajax_deactivate_addon'));
    }
    
    /**
     * Get available add-ons
     *
     * @return array List of add-ons
     */
    public function get_addons() {
        // Ensure plugin functions are available
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        return array(
            array(
                'slug' => 'ai-scribe',
                'name' => 'AI-Scribe',
                'description' => 'Professional AI-powered content creation plugin. Generate SEO-optimised articles, blog posts, and content with GPT-4.5, OpenAI o3, Claude Sonnet 4, and more.',
                'author' => 'Opace Digital Agency',
                'version' => '6.5',
                'requires' => 'AI-Core 1.0+',
                'installed' => $this->is_plugin_installed('ai-scribe'),
                'active' => $this->is_plugin_active('ai-scribe'),
                'icon' => 'dashicons-edit',
                'url' => 'https://opace.agency/ai-scribe',
            ),
            array(
                'slug' => 'ai-imagen',
                'name' => 'AI-Imagen',
                'description' => 'AI-powered image generation plugin using OpenAI DALL-E and GPT-Image-1. Generate stunning, high-quality images directly in WordPress with automatic media library integration.',
                'author' => 'Opace Digital Agency',
                'version' => '1.0.0',
                'requires' => 'AI-Core 1.0+',
                'installed' => $this->is_plugin_installed('ai-imagen'),
                'active' => $this->is_plugin_active('ai-imagen'),
                'icon' => 'dashicons-format-image',
                'url' => 'https://opace.agency/ai-imagen',
                'bundled' => true, // This plugin is bundled with AI-Core
                'plugin_file' => 'ai-imagen/ai-imagen.php',
            ),
        );
    }
    
    /**
     * Check if plugin is installed
     * 
     * @param string $slug Plugin slug
     * @return bool True if installed
     */
    private function is_plugin_installed($slug) {
        $plugins = get_plugins();
        
        foreach ($plugins as $plugin_file => $plugin_data) {
            if (strpos($plugin_file, $slug) !== false) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check if plugin is active
     *
     * @param string $slug Plugin slug
     * @return bool True if active
     */
    private function is_plugin_active($slug) {
        // Ensure plugin functions are available
        if (!function_exists('is_plugin_active')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        $plugins = get_plugins();

        foreach ($plugins as $plugin_file => $plugin_data) {
            if (strpos($plugin_file, $slug) !== false) {
                return is_plugin_active($plugin_file);
            }
        }

        return false;
    }
    
    /**
     * Render add-ons page
     *
     * @return void
     */
    public function render_addons_page() {
        $addons = $this->get_addons();
        
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('AI-Core Add-ons', 'ai-core'); ?></h1>
            
            <p class="description">
                <?php esc_html_e('Extend AI-Core functionality with these powerful add-on plugins. All add-ons automatically use your configured API keys from AI-Core.', 'ai-core'); ?>
            </p>
            
            <div class="ai-core-addons-grid">
                <?php foreach ($addons as $addon): ?>
                    <div class="ai-core-addon-card <?php echo $addon['active'] ? 'active' : ''; ?>">
                        <div class="addon-icon">
                            <span class="dashicons <?php echo esc_attr($addon['icon']); ?>"></span>
                        </div>
                        <div class="addon-content">
                            <h3><?php echo esc_html($addon['name']); ?></h3>
                            <p class="addon-description"><?php echo esc_html($addon['description']); ?></p>
                            <div class="addon-meta">
                                <span class="addon-author"><?php echo esc_html__('By', 'ai-core') . ' ' . esc_html($addon['author']); ?></span>
                                <span class="addon-version"><?php echo esc_html__('Version', 'ai-core') . ' ' . esc_html($addon['version']); ?></span>
                            </div>
                            <div class="addon-requires">
                                <span class="dashicons dashicons-info"></span>
                                <?php echo esc_html__('Requires:', 'ai-core') . ' ' . esc_html($addon['requires']); ?>
                            </div>
                        </div>
                        <div class="addon-actions">
                            <?php if ($addon['active']): ?>
                                <span class="button button-disabled">
                                    <span class="dashicons dashicons-yes-alt"></span>
                                    <?php esc_html_e('Active', 'ai-core'); ?>
                                </span>
                            <?php elseif ($addon['installed']): ?>
                                <button type="button" class="button button-primary ai-core-activate-addon" data-slug="<?php echo esc_attr($addon['slug']); ?>" data-plugin-file="<?php echo esc_attr($addon['plugin_file']); ?>">
                                    <span class="dashicons dashicons-update"></span>
                                    <?php esc_html_e('Activate', 'ai-core'); ?>
                                </button>
                            <?php else: ?>
                                <?php if (!empty($addon['bundled'])): ?>
                                    <button type="button" class="button button-primary ai-core-install-addon" data-slug="<?php echo esc_attr($addon['slug']); ?>">
                                        <span class="dashicons dashicons-download"></span>
                                        <?php esc_html_e('Install Now', 'ai-core'); ?>
                                    </button>
                                <?php else: ?>
                                    <a href="<?php echo esc_url($addon['url']); ?>" class="button button-primary" target="_blank">
                                        <?php esc_html_e('Learn More', 'ai-core'); ?>
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="ai-core-addons-info">
                <h2><?php esc_html_e('Developing Add-ons', 'ai-core'); ?></h2>
                <p><?php esc_html_e('AI-Core provides a simple API for developers to create add-on plugins. Your add-ons can access all configured AI providers without requiring users to enter API keys again.', 'ai-core'); ?></p>
                
                <h3><?php esc_html_e('Example Usage', 'ai-core'); ?></h3>
                <pre><code>&lt;?php
// Check if AI-Core is available
if (function_exists('ai_core')) {
    $ai_core = ai_core();
    
    // Check if configured
    if ($ai_core->is_configured()) {
        // Send a text generation request
        $response = $ai_core->send_text_request(
            'gpt-4o',
            array(
                array('role' => 'user', 'content' => 'Hello, AI!')
            ),
            array('max_tokens' => 100)
        );
        
        if (!is_wp_error($response)) {
            echo $response['choices'][0]['message']['content'];
        }
    }
}
?&gt;</code></pre>
                
                <p>
                    <a href="https://opace.agency/ai-core/docs" class="button" target="_blank">
                        <?php esc_html_e('View Documentation', 'ai-core'); ?>
                    </a>
                </p>
            </div>
        </div>
        <?php
    }

    /**
     * AJAX handler for installing add-on
     *
     * @return void
     */
    public function ajax_install_addon() {
        // Check nonce
        check_ajax_referer('ai_core_admin', 'nonce');

        // Check permissions
        if (!current_user_can('install_plugins')) {
            wp_send_json_error(array('message' => __('You do not have permission to install plugins.', 'ai-core')));
        }

        $slug = isset($_POST['slug']) ? sanitize_text_field($_POST['slug']) : '';

        if (empty($slug)) {
            wp_send_json_error(array('message' => __('Invalid plugin slug.', 'ai-core')));
        }

        // Install the plugin
        $result = $this->install_bundled_addon($slug);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success(array(
            'message' => __('Add-on installed successfully!', 'ai-core'),
            'plugin_file' => $result
        ));
    }

    /**
     * AJAX handler for activating add-on
     *
     * @return void
     */
    public function ajax_activate_addon() {
        // Check nonce
        check_ajax_referer('ai_core_admin', 'nonce');

        // Check permissions
        if (!current_user_can('activate_plugins')) {
            wp_send_json_error(array('message' => __('You do not have permission to activate plugins.', 'ai-core')));
        }

        $plugin_file = isset($_POST['plugin_file']) ? sanitize_text_field($_POST['plugin_file']) : '';

        if (empty($plugin_file)) {
            wp_send_json_error(array('message' => __('Invalid plugin file.', 'ai-core')));
        }

        // Activate the plugin
        $result = activate_plugin($plugin_file);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success(array('message' => __('Add-on activated successfully!', 'ai-core')));
    }

    /**
     * AJAX handler for deactivating add-on
     *
     * @return void
     */
    public function ajax_deactivate_addon() {
        // Check nonce
        check_ajax_referer('ai_core_admin', 'nonce');

        // Check permissions
        if (!current_user_can('activate_plugins')) {
            wp_send_json_error(array('message' => __('You do not have permission to deactivate plugins.', 'ai-core')));
        }

        $plugin_file = isset($_POST['plugin_file']) ? sanitize_text_field($_POST['plugin_file']) : '';

        if (empty($plugin_file)) {
            wp_send_json_error(array('message' => __('Invalid plugin file.', 'ai-core')));
        }

        // Deactivate the plugin
        deactivate_plugins($plugin_file);

        wp_send_json_success(array('message' => __('Add-on deactivated successfully!', 'ai-core')));
    }

    /**
     * Install bundled add-on
     *
     * @param string $slug Plugin slug
     * @return string|WP_Error Plugin file path or error
     */
    private function install_bundled_addon($slug) {
        // Get the bundled plugin path
        $source = AI_CORE_PLUGIN_DIR . 'bundled-addons/' . $slug;

        // Check if bundled plugin exists
        if (!is_dir($source)) {
            return new WP_Error('addon_not_found', __('Bundled add-on not found.', 'ai-core'));
        }

        // Get WordPress plugins directory
        $plugins_dir = WP_PLUGIN_DIR;
        $destination = $plugins_dir . '/' . $slug;

        // Check if already installed
        if (is_dir($destination)) {
            return new WP_Error('addon_exists', __('Add-on is already installed.', 'ai-core'));
        }

        // Copy the plugin directory
        require_once ABSPATH . 'wp-admin/includes/file.php';
        WP_Filesystem();

        global $wp_filesystem;

        if (!$wp_filesystem->copy($source, $destination, true, FS_CHMOD_DIR)) {
            // Try using PHP's copy function as fallback
            if (!$this->recursive_copy($source, $destination)) {
                return new WP_Error('copy_failed', __('Failed to copy add-on files.', 'ai-core'));
            }
        }

        // Return the plugin file path
        return $slug . '/' . $slug . '.php';
    }

    /**
     * Recursively copy directory
     *
     * @param string $source Source directory
     * @param string $destination Destination directory
     * @return bool True on success
     */
    private function recursive_copy($source, $destination) {
        if (!is_dir($source)) {
            return false;
        }

        // Create destination directory
        if (!is_dir($destination)) {
            if (!mkdir($destination, 0755, true)) {
                return false;
            }
        }

        // Open source directory
        $dir = opendir($source);
        if (!$dir) {
            return false;
        }

        // Copy files and subdirectories
        while (($file = readdir($dir)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $source_path = $source . '/' . $file;
            $dest_path = $destination . '/' . $file;

            if (is_dir($source_path)) {
                if (!$this->recursive_copy($source_path, $dest_path)) {
                    closedir($dir);
                    return false;
                }
            } else {
                if (!copy($source_path, $dest_path)) {
                    closedir($dir);
                    return false;
                }
            }
        }

        closedir($dir);
        return true;
    }
}

