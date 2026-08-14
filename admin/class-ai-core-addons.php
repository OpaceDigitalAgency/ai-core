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

        $addons = array(
            array(
                'slug' => 'ai-scribe',
                'name' => 'AI-Scribe',
                'description' => 'SEO content creator and humaniser. Generate optimised articles and long-form content through an 11-step wizard or a single Express request. Uses whichever provider you have configured here: OpenAI (GPT-5 family), Anthropic Claude (including the Claude 5 family) or Google Gemini (3.x family), with per-section images, editable prompts, and meta for Yoast, Rank Math, AIOSEO and SEOPress.',
                'author' => 'Opace Digital Agency',
                'version' => '3.1.0',
                /* translators: %s: the AI-Core version number this add-on needs, e.g. 0.7.7. */
                'requires' => sprintf(__('AI-Core %s or later', 'ai-core-integration-hub-prompt-engine'), AI_CORE_VERSION),
                'installed' => $this->is_plugin_installed('ai-scribe'),
                'active' => $this->is_plugin_active('ai-scribe'),
                'icon' => 'dashicons-edit',
                'url' => 'https://opace.agency/services/web-design/wordpress-development/',
            ),
            array(
                'slug' => 'ai-imagen',
                'name' => 'AI-Imagen',
                'description' => 'Image generation inside WordPress, with automatic media library integration. Draws on the image models your key grants: OpenAI GPT Image (the successor to DALL-E) and Google\'s Gemini image models, including Gemini 3 Pro Image, Gemini Flash Image and the Imagen family. Model lists come from your own provider account, so newly released image models appear without a plugin update.',
                'author' => 'Opace Digital Agency',
                'version' => '0.6.6',
                /* translators: %s: the AI-Core version number this add-on needs, e.g. 0.7.7. */
                'requires' => sprintf(__('AI-Core %s or later', 'ai-core-integration-hub-prompt-engine'), AI_CORE_VERSION),
                'installed' => $this->is_plugin_installed('ai-imagen'),
                'active' => $this->is_plugin_active('ai-imagen'),
                'icon' => 'dashicons-format-image',
                'url' => 'https://opace.agency/services/web-design/wordpress-development/',
                'bundled' => true,
                // Not verified end to end yet, so it is shown but not installable.
                'available' => false,
                'unavailable_reason' => __('In testing and not yet available to install.', 'ai-core-integration-hub-prompt-engine'),
                'plugin_file' => 'ai-imagen/ai-imagen.php',
            ),
            array(
                'slug' => 'ai-stats',
                'name' => 'AI-Stats',
                'description' => 'Dynamic SEO content modules with 6 switchable modes. Generates fresh, data-driven content from real-time web sources and any text model configured here, including the GPT-5, Claude and Gemini 3.x families. Built for authority and trust signals.',
                'author' => 'Opace Digital Agency',
                'version' => '0.8.2',
                /* translators: %s: the AI-Core version number this add-on needs, e.g. 0.7.7. */
                'requires' => sprintf(__('AI-Core %s or later', 'ai-core-integration-hub-prompt-engine'), AI_CORE_VERSION),
                'installed' => $this->is_plugin_installed('ai-stats'),
                'active' => $this->is_plugin_active('ai-stats'),
                'icon' => 'dashicons-chart-bar',
                'url' => 'https://opace.agency/services/web-design/wordpress-development/',
                'bundled' => true,
                // Not verified end to end yet, so it is shown but not installable.
                'available' => false,
                'unavailable_reason' => __('In testing and not yet available to install.', 'ai-core-integration-hub-prompt-engine'),
                'plugin_file' => 'ai-stats/ai-stats.php',
            ),
            array(
                'slug' => 'wp-ai-pulse',
                'name' => 'AI-Pulse',
                'description' => 'Trend analysis with Google Gemini search grounding, using the current Gemini 3.x models your key grants. Generates crawlable, static HTML content for service pages across 11 analysis modes including trends, FAQs, statistics, forecasts and local insights.',
                'author' => 'Opace Digital Agency',
                'version' => '1.0.8',
                /* translators: %s: the AI-Core version number this add-on needs, e.g. 0.7.7. */
                'requires' => sprintf(__('AI-Core %s or later', 'ai-core-integration-hub-prompt-engine'), AI_CORE_VERSION),
                'installed' => $this->is_plugin_installed('wp-ai-pulse'),
                'active' => $this->is_plugin_active('wp-ai-pulse'),
                'icon' => 'dashicons-analytics',
                'url' => 'https://opace.agency/services/web-design/wordpress-development/',
                'bundled' => true,
                // Not verified end to end yet, so it is shown but not installable.
                'available' => false,
                'unavailable_reason' => __('In testing and not yet available to install.', 'ai-core-integration-hub-prompt-engine'),
                'plugin_file' => 'wp-ai-pulse/ai-pulse.php',
            ),
        );

        // Plugin headers are the source of truth for a version number: what the
        // site actually has installed first, then the bundled copy. A
        // hand-maintained number here is only ever the last resort.
        foreach ($addons as $index => $addon) {
            // The wordpress.org zip excludes bundled-addons/ entirely, so a
            // "bundled" add-on may have no local source on this install. The
            // card must know, or it offers an Install button that can only fail.
            $addons[$index]['source_present'] = !empty($addon['bundled'])
                && is_dir(AI_CORE_PLUGIN_DIR . 'bundled-addons/' . $addon['slug']);

            $version = $this->get_installed_addon_version($addon['slug']);

            if ($version === '' && !empty($addon['bundled'])) {
                $version = $this->get_bundled_addon_version($addon['slug'], $addon['plugin_file']);
            }

            if ($version !== '') {
                $addons[$index]['version'] = $version;
            }
        }

        return $addons;
    }

    /**
     * Read an installed add-on's version from the plugin list
     *
     * @param string $slug Plugin slug
     * @return string Version string, or an empty string when not installed
     */
    private function get_installed_addon_version($slug) {
        $plugins = get_plugins();

        foreach ($plugins as $plugin_file => $plugin_data) {
            if (strpos($plugin_file, $slug) !== false && !empty($plugin_data['Version'])) {
                return trim($plugin_data['Version']);
            }
        }

        return '';
    }

    /**
     * Read a bundled add-on's version straight from its plugin header
     *
     * @param string $slug Plugin slug
     * @param string $plugin_file Plugin file path, relative to the plugins directory
     * @return string Version string, or an empty string when it cannot be read
     */
    private function get_bundled_addon_version($slug, $plugin_file) {
        $basename = basename($plugin_file);
        $path = AI_CORE_PLUGIN_DIR . 'bundled-addons/' . $slug . '/' . $basename;

        if (!is_readable($path)) {
            return '';
        }

        $data = get_file_data($path, array('Version' => 'Version'));

        return isset($data['Version']) ? trim($data['Version']) : '';
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
            <h1><?php esc_html_e('AI-Core Add-ons', 'ai-core-integration-hub-prompt-engine'); ?></h1>
            
            <p class="description">
                <?php esc_html_e('Extend AI-Core functionality with these powerful add-on plugins. All add-ons automatically use your configured API keys from AI-Core.', 'ai-core-integration-hub-prompt-engine'); ?>
            </p>
            
            <h2 class="screen-reader-text"><?php esc_html_e('Available add-ons', 'ai-core-integration-hub-prompt-engine'); ?></h2>

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
                                <span class="addon-author"><?php echo esc_html__('By', 'ai-core-integration-hub-prompt-engine') . ' ' . esc_html($addon['author']); ?></span>
                                <span class="addon-version"><?php echo esc_html__('Version', 'ai-core-integration-hub-prompt-engine') . ' ' . esc_html($addon['version']); ?></span>
                            </div>
                            <div class="addon-requires">
                                <span class="dashicons dashicons-info"></span>
                                <?php echo esc_html__('Requires:', 'ai-core-integration-hub-prompt-engine') . ' ' . esc_html($addon['requires']); ?>
                            </div>
                        </div>
                        <div class="addon-actions">
                            <?php if ($addon['active']): ?>
                                <span class="button button-disabled">
                                    <span class="dashicons dashicons-yes-alt"></span>
                                    <?php esc_html_e('Active', 'ai-core-integration-hub-prompt-engine'); ?>
                                </span>
                            <?php elseif ($addon['installed']): ?>
                                <button type="button" class="button button-primary ai-core-activate-addon" data-slug="<?php echo esc_attr($addon['slug']); ?>" data-plugin-file="<?php echo esc_attr($addon['plugin_file']); ?>">
                                    <span class="dashicons dashicons-update"></span>
                                    <?php esc_html_e('Activate', 'ai-core-integration-hub-prompt-engine'); ?>
                                </button>
                            <?php elseif (isset($addon['available']) && !$addon['available']): ?>
                                <span class="button button-disabled" aria-disabled="true">
                                    <span class="dashicons dashicons-clock"></span>
                                    <?php esc_html_e('Coming soon', 'ai-core-integration-hub-prompt-engine'); ?>
                                </span>
                                <p class="addon-unavailable-reason"><?php echo esc_html($addon['unavailable_reason']); ?></p>
                            <?php else: ?>
                                <?php if (!empty($addon['bundled']) && !empty($addon['source_present'])): ?>
                                    <button type="button" class="button button-primary ai-core-install-addon" data-slug="<?php echo esc_attr($addon['slug']); ?>">
                                        <span class="dashicons dashicons-download"></span>
                                        <?php esc_html_e('Install Now', 'ai-core-integration-hub-prompt-engine'); ?>
                                    </button>
                                <?php elseif (!empty($addon['bundled'])): ?>
                                    <span class="button button-disabled" aria-disabled="true">
                                        <span class="dashicons dashicons-external"></span>
                                        <?php esc_html_e('Available separately', 'ai-core-integration-hub-prompt-engine'); ?>
                                    </span>
                                    <p class="addon-unavailable-reason"><?php esc_html_e('Not included in this copy of AI-Core.', 'ai-core-integration-hub-prompt-engine'); ?></p>
                                <?php else: ?>
                                    <a href="<?php echo esc_url($addon['url']); ?>" class="button button-primary" target="_blank">
                                        <?php esc_html_e('Learn More', 'ai-core-integration-hub-prompt-engine'); ?>
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="ai-core-addons-info">
                <h2><?php esc_html_e('Developing Add-ons', 'ai-core-integration-hub-prompt-engine'); ?></h2>
                <p><?php esc_html_e('AI-Core provides a simple API for developers to create add-on plugins. Your add-ons can access all configured AI providers without requiring users to enter API keys again.', 'ai-core-integration-hub-prompt-engine'); ?></p>
                
                <h3><?php esc_html_e('Example Usage', 'ai-core-integration-hub-prompt-engine'); ?></h3>
                <pre><code>&lt;?php
// Check if AI-Core is available
if (function_exists('ai_core')) {
    $ai_core = ai_core();
    
    // Check if configured
    if ($ai_core->is_configured()) {
        // Send a text generation request
        $response = $ai_core->send_text_request(
            'gpt-5-mini',
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

                <p class="description">
                    <?php esc_html_e('Model identifiers come from AI-Core\'s own registry, so any provider and model the site has configured can be named here. Check the Settings screen for the identifiers currently available.', 'ai-core-integration-hub-prompt-engine'); ?>
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
            wp_send_json_error(array('message' => __('You do not have permission to install plugins.', 'ai-core-integration-hub-prompt-engine')));
        }

        $slug = isset($_POST['slug']) ? sanitize_text_field(wp_unslash($_POST['slug'])) : '';

        if (empty($slug)) {
            wp_send_json_error(array('message' => __('Invalid plugin slug.', 'ai-core-integration-hub-prompt-engine')));
        }

        /*
         * The card is disabled for an add-on that is not released yet, but a
         * disabled control is a UI state, not a permission. The same check has
         * to hold here or the request can simply be replayed by hand.
         */
        foreach ($this->get_addons() as $candidate) {
            if ($candidate['slug'] !== $slug) {
                continue;
            }
            if (isset($candidate['available']) && !$candidate['available']) {
                wp_send_json_error(array('message' => $candidate['unavailable_reason']));
            }
            // Packaged builds ship without bundled-addons/, so there is
            // nothing local to install from.
            if (!empty($candidate['bundled']) && empty($candidate['source_present'])) {
                wp_send_json_error(array('message' => __('This add-on is not included in this copy of AI-Core and is available separately.', 'ai-core-integration-hub-prompt-engine')));
            }
        }

        // Install the plugin
        $result = $this->install_bundled_addon($slug);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success(array(
            'message' => __('Add-on installed successfully!', 'ai-core-integration-hub-prompt-engine'),
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
            wp_send_json_error(array('message' => __('You do not have permission to activate plugins.', 'ai-core-integration-hub-prompt-engine')));
        }

        $plugin_file = isset($_POST['plugin_file']) ? sanitize_text_field(wp_unslash($_POST['plugin_file'])) : '';

        if (empty($plugin_file)) {
            wp_send_json_error(array('message' => __('Invalid plugin file.', 'ai-core-integration-hub-prompt-engine')));
        }

        // Activate the plugin
        $result = activate_plugin($plugin_file);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success(array('message' => __('Add-on activated successfully!', 'ai-core-integration-hub-prompt-engine')));
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
            wp_send_json_error(array('message' => __('You do not have permission to deactivate plugins.', 'ai-core-integration-hub-prompt-engine')));
        }

        $plugin_file = isset($_POST['plugin_file']) ? sanitize_text_field(wp_unslash($_POST['plugin_file'])) : '';

        if (empty($plugin_file)) {
            wp_send_json_error(array('message' => __('Invalid plugin file.', 'ai-core-integration-hub-prompt-engine')));
        }

        // Deactivate the plugin
        deactivate_plugins($plugin_file);

        wp_send_json_success(array('message' => __('Add-on deactivated successfully!', 'ai-core-integration-hub-prompt-engine')));
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
            return new WP_Error('addon_not_found', __('Bundled add-on not found.', 'ai-core-integration-hub-prompt-engine'));
        }

        // Get WordPress plugins directory
        $plugins_dir = WP_PLUGIN_DIR;
        $destination = $plugins_dir . '/' . $slug;

        // Check if already installed
        if (is_dir($destination)) {
            return new WP_Error('addon_exists', __('Add-on is already installed.', 'ai-core-integration-hub-prompt-engine'));
        }

        // Copy the plugin directory
        require_once ABSPATH . 'wp-admin/includes/file.php';
        WP_Filesystem();

        global $wp_filesystem;

        if (!$wp_filesystem->copy($source, $destination, true, FS_CHMOD_DIR)) {
            // Try using PHP's copy function as fallback
            if (!$this->recursive_copy($source, $destination)) {
                return new WP_Error('copy_failed', __('Failed to copy add-on files.', 'ai-core-integration-hub-prompt-engine'));
            }
        }

        // Find the main plugin file in the destination directory
        $plugin_file = $this->find_plugin_file($destination, $slug);

        if (!$plugin_file) {
            // Fallback to slug/slug.php pattern
            $plugin_file = $slug . '/' . $slug . '.php';
        }

        return $plugin_file;
    }

    /**
     * Find the main plugin file in a directory
     *
     * @param string $dir Directory path
     * @param string $slug Plugin slug
     * @return string|false Plugin file path or false
     */
    private function find_plugin_file($dir, $slug) {
        $files = glob($dir . '/*.php');

        foreach ($files as $file) {
            $plugin_data = get_file_data($file, array('Plugin Name' => 'Plugin Name'));
            if (!empty($plugin_data['Plugin Name'])) {
                return $slug . '/' . basename($file);
            }
        }

        return false;
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

        // Create destination directory. wp_mkdir_p() is recursive, applies the
        // site's own FS_CHMOD_DIR and goes through WordPress rather than a raw
        // mkdir() call.
        if (!is_dir($destination)) {
            if (!wp_mkdir_p($destination)) {
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

