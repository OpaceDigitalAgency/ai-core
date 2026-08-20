<?php
/**
 * Opace AI Hub Add-ons Class
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
 * Opace AI Hub Add-ons Class
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
        add_action('wp_ajax_ai_core_activate_addon', array($this, 'ajax_activate_addon'));
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
                /* translators: %s: the Opace AI Hub version number this add-on needs, e.g. 0.7.7. */
                'requires' => sprintf(__('Opace AI Hub %s or later', 'opace-ai-prompt-library-api-hub'), AI_CORE_VERSION),
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
                /* translators: %s: the Opace AI Hub version number this add-on needs, e.g. 0.7.7. */
                'requires' => sprintf(__('Opace AI Hub %s or later', 'opace-ai-prompt-library-api-hub'), AI_CORE_VERSION),
                'installed' => $this->is_plugin_installed('ai-imagen'),
                'active' => $this->is_plugin_active('ai-imagen'),
                'icon' => 'dashicons-format-image',
                'url' => 'https://opace.agency/services/web-design/wordpress-development/',
                'bundled' => true,
                // Not verified end to end yet, so it is shown but not installable.
                'available' => false,
                'unavailable_reason' => __('In testing and not yet available to install.', 'opace-ai-prompt-library-api-hub'),
                'plugin_file' => 'ai-imagen/ai-imagen.php',
            ),
            array(
                'slug' => 'ai-stats',
                'name' => 'AI-Stats',
                'description' => 'Dynamic SEO content modules with 6 switchable modes. Generates fresh, data-driven content from real-time web sources and any text model configured here, including the GPT-5, Claude and Gemini 3.x families. Built for authority and trust signals.',
                'author' => 'Opace Digital Agency',
                'version' => '0.8.2',
                /* translators: %s: the Opace AI Hub version number this add-on needs, e.g. 0.7.7. */
                'requires' => sprintf(__('Opace AI Hub %s or later', 'opace-ai-prompt-library-api-hub'), AI_CORE_VERSION),
                'installed' => $this->is_plugin_installed('ai-stats'),
                'active' => $this->is_plugin_active('ai-stats'),
                'icon' => 'dashicons-chart-bar',
                'url' => 'https://opace.agency/services/web-design/wordpress-development/',
                'bundled' => true,
                // Not verified end to end yet, so it is shown but not installable.
                'available' => false,
                'unavailable_reason' => __('In testing and not yet available to install.', 'opace-ai-prompt-library-api-hub'),
                'plugin_file' => 'ai-stats/ai-stats.php',
            ),
            array(
                'slug' => 'wp-ai-pulse',
                'name' => 'AI-Pulse',
                'description' => 'Trend analysis with Google Gemini search grounding, using the current Gemini 3.x models your key grants. Generates crawlable, static HTML content for service pages across 11 analysis modes including trends, FAQs, statistics, forecasts and local insights.',
                'author' => 'Opace Digital Agency',
                'version' => '1.0.8',
                /* translators: %s: the Opace AI Hub version number this add-on needs, e.g. 0.7.7. */
                'requires' => sprintf(__('Opace AI Hub %s or later', 'opace-ai-prompt-library-api-hub'), AI_CORE_VERSION),
                'installed' => $this->is_plugin_installed('wp-ai-pulse'),
                'active' => $this->is_plugin_active('wp-ai-pulse'),
                'icon' => 'dashicons-analytics',
                'url' => 'https://opace.agency/services/web-design/wordpress-development/',
                'bundled' => true,
                // Not verified end to end yet, so it is shown but not installable.
                'available' => false,
                'unavailable_reason' => __('In testing and not yet available to install.', 'opace-ai-prompt-library-api-hub'),
                'plugin_file' => 'wp-ai-pulse/ai-pulse.php',
            ),
        );

        // Plugin headers are the source of truth for a version number: what the
        // site actually has installed first, then the bundled copy. A
        // hand-maintained number here is only ever the last resort.
        foreach ($addons as $index => $addon) {
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
            <h1><?php esc_html_e('Opace AI Hub Add-ons', 'opace-ai-prompt-library-api-hub'); ?></h1>
            
            <p class="description">
                <?php esc_html_e('Extend Opace AI Hub functionality with these powerful add-on plugins. All add-ons automatically use your configured API keys from Opace AI Hub.', 'opace-ai-prompt-library-api-hub'); ?>
            </p>
            
            <h2 class="screen-reader-text"><?php esc_html_e('Available add-ons', 'opace-ai-prompt-library-api-hub'); ?></h2>

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
                                <span class="addon-author"><?php echo esc_html__('By', 'opace-ai-prompt-library-api-hub') . ' ' . esc_html($addon['author']); ?></span>
                                <span class="addon-version"><?php echo esc_html__('Version', 'opace-ai-prompt-library-api-hub') . ' ' . esc_html($addon['version']); ?></span>
                            </div>
                            <div class="addon-requires">
                                <span class="dashicons dashicons-info"></span>
                                <?php echo esc_html__('Requires:', 'opace-ai-prompt-library-api-hub') . ' ' . esc_html($addon['requires']); ?>
                            </div>
                        </div>
                        <div class="addon-actions">
                            <?php if ($addon['active']): ?>
                                <span class="button button-disabled">
                                    <span class="dashicons dashicons-yes-alt"></span>
                                    <?php esc_html_e('Active', 'opace-ai-prompt-library-api-hub'); ?>
                                </span>
                            <?php elseif ($addon['installed']): ?>
                                <button type="button" class="button button-primary ai-core-activate-addon" data-slug="<?php echo esc_attr($addon['slug']); ?>" data-plugin-file="<?php echo esc_attr($addon['plugin_file']); ?>">
                                    <span class="dashicons dashicons-update"></span>
                                    <?php esc_html_e('Activate', 'opace-ai-prompt-library-api-hub'); ?>
                                </button>
                            <?php elseif (isset($addon['available']) && !$addon['available']): ?>
                                <span class="button button-disabled" aria-disabled="true">
                                    <span class="dashicons dashicons-clock"></span>
                                    <?php esc_html_e('Coming soon', 'opace-ai-prompt-library-api-hub'); ?>
                                </span>
                                <p class="addon-unavailable-reason"><?php echo esc_html($addon['unavailable_reason']); ?></p>
                            <?php else: ?>
                                <?php if (!empty($addon['bundled'])): ?>
                                    <span class="button button-disabled" aria-disabled="true">
                                        <span class="dashicons dashicons-external"></span>
                                        <?php esc_html_e('Available separately', 'opace-ai-prompt-library-api-hub'); ?>
                                    </span>
                                    <p class="addon-unavailable-reason"><?php esc_html_e('Not included in this copy of Opace AI Hub.', 'opace-ai-prompt-library-api-hub'); ?></p>
                                <?php else: ?>
                                    <a href="<?php echo esc_url($addon['url']); ?>" class="button button-primary" target="_blank">
                                        <?php esc_html_e('Learn More', 'opace-ai-prompt-library-api-hub'); ?>
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="ai-core-addons-info">
                <h2><?php esc_html_e('Developing Add-ons', 'opace-ai-prompt-library-api-hub'); ?></h2>
                <p><?php esc_html_e('Opace AI Hub provides a simple API for developers to create add-on plugins. Your add-ons can access all configured AI providers without requiring users to enter API keys again.', 'opace-ai-prompt-library-api-hub'); ?></p>
                
                <h3><?php esc_html_e('Example Usage', 'opace-ai-prompt-library-api-hub'); ?></h3>
                <pre><code>&lt;?php
// Check if Opace AI Hub is available
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
                    <?php esc_html_e('Model identifiers come from Opace AI Hub\'s own registry, so any provider and model the site has configured can be named here. Check the Settings screen for the identifiers currently available.', 'opace-ai-prompt-library-api-hub'); ?>
                </p>
            </div>
        </div>
        <?php
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
            wp_send_json_error(array('message' => __('You do not have permission to activate plugins.', 'opace-ai-prompt-library-api-hub')));
        }

        $plugin_file = isset($_POST['plugin_file']) ? sanitize_text_field(wp_unslash($_POST['plugin_file'])) : '';

        if (empty($plugin_file)) {
            wp_send_json_error(array('message' => __('Invalid plugin file.', 'opace-ai-prompt-library-api-hub')));
        }

        // Activate the plugin
        $result = activate_plugin($plugin_file);

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success(array('message' => __('Add-on activated successfully!', 'opace-ai-prompt-library-api-hub')));
    }

}
