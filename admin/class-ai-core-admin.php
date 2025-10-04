<?php
/**
 * AI-Core Admin Class
 * 
 * Handles admin interface and menu pages
 * 
 * @package AI_Core
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AI-Core Admin Class
 * 
 * Manages admin pages and interface
 */
class AI_Core_Admin {
    
    /**
     * Class instance
     * 
     * @var AI_Core_Admin
     */
    private static $instance = null;
    
    /**
     * Get class instance
     * 
     * @return AI_Core_Admin
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
     * Add admin menu
     *
     * @return void
     */
    public function add_admin_menu() {
        // Main menu page
        add_menu_page(
            __('AI-Core', 'ai-core'),
            __('AI-Core', 'ai-core'),
            'manage_options',
            'ai-core',
            array($this, 'render_dashboard_page'),
            'dashicons-admin-generic',
            30
        );
        
        // Dashboard submenu (same as main)
        add_submenu_page(
            'ai-core',
            __('Dashboard', 'ai-core'),
            __('Dashboard', 'ai-core'),
            'manage_options',
            'ai-core',
            array($this, 'render_dashboard_page')
        );
        
        // Settings submenu
        add_submenu_page(
            'ai-core',
            __('Settings', 'ai-core'),
            __('Settings', 'ai-core'),
            'manage_options',
            'ai-core-settings',
            array($this, 'render_settings_page')
        );
        
        // Statistics submenu
        add_submenu_page(
            'ai-core',
            __('Statistics', 'ai-core'),
            __('Statistics', 'ai-core'),
            'manage_options',
            'ai-core-stats',
            array($this, 'render_stats_page')
        );
        
        // Add-ons submenu
        add_submenu_page(
            'ai-core',
            __('Add-ons', 'ai-core'),
            __('Add-ons', 'ai-core'),
            'manage_options',
            'ai-core-addons',
            array($this, 'render_addons_page')
        );
    }
    
    /**
     * Render dashboard page
     *
     * @return void
     */
    public function render_dashboard_page() {
        $api = AI_Core_API::get_instance();
        $configured = $api->is_configured();
        $providers = $api->get_configured_providers();
        $stats = AI_Core_Stats::get_instance()->get_total_stats();
        
        ?>
        <div class="wrap ai-core-dashboard">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="ai-core-welcome-panel">
                <h2><?php esc_html_e('Welcome to AI-Core', 'ai-core'); ?></h2>
                <p><?php esc_html_e('Universal AI Integration Hub for WordPress', 'ai-core'); ?></p>
                
                <?php if (!$configured): ?>
                    <div class="notice notice-warning inline">
                        <p>
                            <strong><?php esc_html_e('Getting Started:', 'ai-core'); ?></strong>
                            <?php esc_html_e('Please configure at least one API key in the Settings page to start using AI-Core.', 'ai-core'); ?>
                        </p>
                        <p>
                            <a href="<?php echo esc_url(admin_url('admin.php?page=ai-core-settings')); ?>" class="button button-primary">
                                <?php esc_html_e('Configure API Keys', 'ai-core'); ?>
                            </a>
                        </p>
                    </div>
                <?php else: ?>
                    <div class="notice notice-success inline">
                        <p>
                            <strong><?php esc_html_e('Status:', 'ai-core'); ?></strong>
                            <?php
                            printf(
                                esc_html(_n('%d provider configured', '%d providers configured', count($providers), 'ai-core')),
                                count($providers)
                            );
                            ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if ($configured): ?>
                <div class="ai-core-stats-overview">
                    <h2><?php esc_html_e('Quick Stats', 'ai-core'); ?></h2>
                    <div class="ai-core-stats-grid">
                        <div class="stat-box">
                            <span class="stat-label"><?php esc_html_e('Total Requests', 'ai-core'); ?></span>
                            <span class="stat-value"><?php echo number_format($stats['requests']); ?></span>
                        </div>
                        <div class="stat-box">
                            <span class="stat-label"><?php esc_html_e('Total Tokens', 'ai-core'); ?></span>
                            <span class="stat-value"><?php echo number_format($stats['tokens']); ?></span>
                        </div>
                        <div class="stat-box">
                            <span class="stat-label"><?php esc_html_e('Configured Providers', 'ai-core'); ?></span>
                            <span class="stat-value"><?php echo count($providers); ?></span>
                        </div>
                        <div class="stat-box">
                            <span class="stat-label"><?php esc_html_e('Models Used', 'ai-core'); ?></span>
                            <span class="stat-value"><?php echo number_format($stats['models_used']); ?></span>
                        </div>
                    </div>
                </div>
                
                <div class="ai-core-providers-status">
                    <h2><?php esc_html_e('Configured Providers', 'ai-core'); ?></h2>
                    <table class="widefat">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Provider', 'ai-core'); ?></th>
                                <th><?php esc_html_e('Status', 'ai-core'); ?></th>
                                <th><?php esc_html_e('Available Models', 'ai-core'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($providers as $provider): 
                                $models = $api->get_available_models($provider);
                                $provider_names = array(
                                    'openai' => 'OpenAI',
                                    'anthropic' => 'Anthropic Claude',
                                    'gemini' => 'Google Gemini',
                                    'grok' => 'xAI Grok'
                                );
                            ?>
                                <tr>
                                    <td><strong><?php echo esc_html($provider_names[$provider] ?? $provider); ?></strong></td>
                                    <td><span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span> <?php esc_html_e('Configured', 'ai-core'); ?></td>
                                    <td><?php echo count($models); ?> <?php esc_html_e('models', 'ai-core'); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
            
            <div class="ai-core-quick-links">
                <h2><?php esc_html_e('Quick Links', 'ai-core'); ?></h2>
                <div class="ai-core-links-grid">
                    <a href="<?php echo esc_url(admin_url('admin.php?page=ai-core-settings')); ?>" class="ai-core-link-box">
                        <span class="dashicons dashicons-admin-settings"></span>
                        <h3><?php esc_html_e('Settings', 'ai-core'); ?></h3>
                        <p><?php esc_html_e('Configure API keys and preferences', 'ai-core'); ?></p>
                    </a>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=ai-core-stats')); ?>" class="ai-core-link-box">
                        <span class="dashicons dashicons-chart-bar"></span>
                        <h3><?php esc_html_e('Statistics', 'ai-core'); ?></h3>
                        <p><?php esc_html_e('View detailed usage statistics', 'ai-core'); ?></p>
                    </a>
                    <a href="<?php echo esc_url(admin_url('admin.php?page=ai-core-addons')); ?>" class="ai-core-link-box">
                        <span class="dashicons dashicons-admin-plugins"></span>
                        <h3><?php esc_html_e('Add-ons', 'ai-core'); ?></h3>
                        <p><?php esc_html_e('Discover plugins that extend AI-Core', 'ai-core'); ?></p>
                    </a>
                </div>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render settings page
     *
     * @return void
     */
    public function render_settings_page() {
        // Handle form submission FIRST
        if (isset($_POST['ai_core_save_settings']) && check_admin_referer('ai_core_settings_save', 'ai_core_settings_nonce')) {
            $settings = $this->save_settings($_POST);

            // Reinitialize AI-Core library with new settings
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
                if (!empty($settings['grok_api_key'])) {
                    $config['grok_api_key'] = $settings['grok_api_key'];
                }
                \AICore\AICore::init($config);
            }

            echo '<div class="notice notice-success is-dismissible"><p>' . esc_html__('Settings saved successfully. You can now test your API connection below.', 'ai-core') . '</p></div>';
        }

        // NOW get the settings (after potential save)
        $settings = get_option('ai_core_settings', array());
        $api = AI_Core_API::get_instance();

        ?>
        <div class="wrap ai-core-settings-page">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>

            <div class="ai-core-settings-intro">
                <p><?php esc_html_e('Configure your AI provider API keys and settings. AI-Core supports multiple providers, allowing you to choose the best model for each task.', 'ai-core'); ?></p>
            </div>

            <form method="post" action="">
                <?php wp_nonce_field('ai_core_settings_save', 'ai_core_settings_nonce'); ?>

                <!-- API Keys Section -->
                <div class="ai-core-settings-section">
                    <h2><?php esc_html_e('API Keys Configuration', 'ai-core'); ?></h2>
                    <p class="description"><?php esc_html_e('Configure your AI provider API keys. At least one API key is required. Your keys are stored securely in the WordPress database.', 'ai-core'); ?></p>

                    <table class="form-table" role="presentation">
                        <tbody>
                            <!-- OpenAI -->
                            <tr>
                                <th scope="row">
                                    <label for="openai_api_key"><?php esc_html_e('OpenAI API Key', 'ai-core'); ?></label>
                                </th>
                                <td>
                                    <div class="ai-core-api-key-field">
                                        <input type="password"
                                               id="openai_api_key"
                                               name="ai_core_settings[openai_api_key]"
                                               value="<?php echo esc_attr($settings['openai_api_key'] ?? ''); ?>"
                                               class="regular-text ai-core-api-key-input"
                                               placeholder="<?php esc_attr_e('sk-...', 'ai-core'); ?>" />
                                        <button type="button" class="button ai-core-test-key" data-provider="openai">
                                            <?php esc_html_e('Test Key', 'ai-core'); ?>
                                        </button>
                                        <span class="ai-core-key-status" id="openai-status"></span>
                                    </div>
                                    <p class="description">
                                        <?php
                                        printf(
                                            esc_html__('Get your API key from %s', 'ai-core'),
                                            '<a href="https://platform.openai.com/api-keys" target="_blank">platform.openai.com</a>'
                                        );
                                        ?>
                                    </p>
                                </td>
                            </tr>

                            <!-- Anthropic -->
                            <tr>
                                <th scope="row">
                                    <label for="anthropic_api_key"><?php esc_html_e('Anthropic API Key', 'ai-core'); ?></label>
                                </th>
                                <td>
                                    <div class="ai-core-api-key-field">
                                        <input type="password"
                                               id="anthropic_api_key"
                                               name="ai_core_settings[anthropic_api_key]"
                                               value="<?php echo esc_attr($settings['anthropic_api_key'] ?? ''); ?>"
                                               class="regular-text ai-core-api-key-input"
                                               placeholder="<?php esc_attr_e('sk-ant-...', 'ai-core'); ?>" />
                                        <button type="button" class="button ai-core-test-key" data-provider="anthropic">
                                            <?php esc_html_e('Test Key', 'ai-core'); ?>
                                        </button>
                                        <span class="ai-core-key-status" id="anthropic-status"></span>
                                    </div>
                                    <p class="description">
                                        <?php
                                        printf(
                                            esc_html__('Get your API key from %s', 'ai-core'),
                                            '<a href="https://console.anthropic.com/settings/keys" target="_blank">console.anthropic.com</a>'
                                        );
                                        ?>
                                    </p>
                                </td>
                            </tr>

                            <!-- Google Gemini -->
                            <tr>
                                <th scope="row">
                                    <label for="gemini_api_key"><?php esc_html_e('Google Gemini API Key', 'ai-core'); ?></label>
                                </th>
                                <td>
                                    <div class="ai-core-api-key-field">
                                        <input type="password"
                                               id="gemini_api_key"
                                               name="ai_core_settings[gemini_api_key]"
                                               value="<?php echo esc_attr($settings['gemini_api_key'] ?? ''); ?>"
                                               class="regular-text ai-core-api-key-input"
                                               placeholder="<?php esc_attr_e('AIza...', 'ai-core'); ?>" />
                                        <button type="button" class="button ai-core-test-key" data-provider="gemini">
                                            <?php esc_html_e('Test Key', 'ai-core'); ?>
                                        </button>
                                        <span class="ai-core-key-status" id="gemini-status"></span>
                                    </div>
                                    <p class="description">
                                        <?php
                                        printf(
                                            esc_html__('Get your API key from %s', 'ai-core'),
                                            '<a href="https://makersuite.google.com/app/apikey" target="_blank">Google AI Studio</a>'
                                        );
                                        ?>
                                    </p>
                                </td>
                            </tr>

                            <!-- xAI Grok -->
                            <tr>
                                <th scope="row">
                                    <label for="grok_api_key"><?php esc_html_e('xAI Grok API Key', 'ai-core'); ?></label>
                                </th>
                                <td>
                                    <div class="ai-core-api-key-field">
                                        <input type="password"
                                               id="grok_api_key"
                                               name="ai_core_settings[grok_api_key]"
                                               value="<?php echo esc_attr($settings['grok_api_key'] ?? ''); ?>"
                                               class="regular-text ai-core-api-key-input"
                                               placeholder="<?php esc_attr_e('xai-...', 'ai-core'); ?>" />
                                        <button type="button" class="button ai-core-test-key" data-provider="grok">
                                            <?php esc_html_e('Test Key', 'ai-core'); ?>
                                        </button>
                                        <span class="ai-core-key-status" id="grok-status"></span>
                                    </div>
                                    <p class="description">
                                        <?php
                                        printf(
                                            esc_html__('Get your API key from %s', 'ai-core'),
                                            '<a href="https://console.x.ai/" target="_blank">console.x.ai</a>'
                                        );
                                        ?>
                                    </p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- General Settings Section -->
                <div class="ai-core-settings-section">
                    <h2><?php esc_html_e('General Settings', 'ai-core'); ?></h2>

                    <table class="form-table" role="presentation">
                        <tbody>
                            <tr>
                                <th scope="row">
                                    <label for="default_provider"><?php esc_html_e('Default Provider', 'ai-core'); ?></label>
                                </th>
                                <td>
                                    <select id="default_provider" name="ai_core_settings[default_provider]" class="regular-text">
                                        <?php
                                        $configured_providers = $api->get_configured_providers();
                                        $provider_labels = array(
                                            'openai' => 'OpenAI',
                                            'anthropic' => 'Anthropic Claude',
                                            'gemini' => 'Google Gemini',
                                            'grok' => 'xAI Grok'
                                        );

                                        if (empty($configured_providers)) {
                                            echo '<option value="">' . esc_html__('No providers configured - add an API key above', 'ai-core') . '</option>';
                                        } else {
                                            $current_default = $settings['default_provider'] ?? '';
                                            // If current default is not configured, use first configured provider
                                            if (!in_array($current_default, $configured_providers)) {
                                                $current_default = $configured_providers[0];
                                            }

                                            foreach ($configured_providers as $provider) {
                                                $label = $provider_labels[$provider] ?? ucfirst($provider);
                                                echo '<option value="' . esc_attr($provider) . '" ' . selected($current_default, $provider, false) . '>' . esc_html($label) . '</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                    <p class="description"><?php esc_html_e('The default AI provider to use when none is specified.', 'ai-core'); ?></p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row"><?php esc_html_e('Usage Statistics', 'ai-core'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox"
                                               name="ai_core_settings[enable_stats]"
                                               value="1"
                                               <?php checked(!empty($settings['enable_stats'])); ?> />
                                        <?php esc_html_e('Enable usage statistics tracking', 'ai-core'); ?>
                                    </label>
                                    <p class="description"><?php esc_html_e('Track API usage, costs, and performance metrics.', 'ai-core'); ?></p>
                                </td>
                            </tr>

                            <tr>
                                <th scope="row"><?php esc_html_e('Model Caching', 'ai-core'); ?></th>
                                <td>
                                    <label>
                                        <input type="checkbox"
                                               name="ai_core_settings[enable_caching]"
                                               value="1"
                                               <?php checked(!empty($settings['enable_caching'])); ?> />
                                        <?php esc_html_e('Cache available models list', 'ai-core'); ?>
                                    </label>
                                    <p class="description"><?php esc_html_e('Improves performance by caching the list of available models.', 'ai-core'); ?></p>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <?php submit_button(__('Save Settings', 'ai-core'), 'primary', 'ai_core_save_settings'); ?>
            </form>

            <!-- Test Interface Section -->
            <div class="ai-core-settings-section ai-core-test-interface">
                <h2><?php esc_html_e('Test AI Connection', 'ai-core'); ?></h2>
                <p class="description"><?php esc_html_e('Test your API configuration with a simple prompt. Make sure to save your settings first.', 'ai-core'); ?></p>

                <table class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <th scope="row">
                                <label for="test_provider"><?php esc_html_e('Provider', 'ai-core'); ?></label>
                            </th>
                            <td>
                                <select id="test_provider" class="regular-text">
                                    <?php
                                    $configured_providers = $api->get_configured_providers();
                                    if (empty($configured_providers)) {
                                        echo '<option value="">' . esc_html__('No providers configured', 'ai-core') . '</option>';
                                    } else {
                                        foreach ($configured_providers as $provider) {
                                            $label = ucfirst($provider);
                                            if ($provider === 'anthropic') $label = 'Anthropic Claude';
                                            if ($provider === 'gemini') $label = 'Google Gemini';
                                            if ($provider === 'grok') $label = 'xAI Grok';
                                            echo '<option value="' . esc_attr($provider) . '">' . esc_html($label) . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row">
                                <label for="test_prompt"><?php esc_html_e('Test Prompt', 'ai-core'); ?></label>
                            </th>
                            <td>
                                <textarea id="test_prompt"
                                          rows="3"
                                          class="large-text"
                                          placeholder="<?php esc_attr_e('Enter a test prompt, e.g., "Say hello in 5 words"', 'ai-core'); ?>">Say hello in exactly 5 words</textarea>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"></th>
                            <td>
                                <button type="button" id="ai-core-test-prompt" class="button button-secondary">
                                    <?php esc_html_e('Send Test Request', 'ai-core'); ?>
                                </button>
                                <span class="ai-core-test-status"></span>
                            </td>
                        </tr>
                        <tr id="test-result-row" style="display: none;">
                            <th scope="row">
                                <label><?php esc_html_e('Response', 'ai-core'); ?></label>
                            </th>
                            <td>
                                <div id="test-result" class="ai-core-test-result"></div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <?php
    }

    /**
     * Save settings
     *
     * @param array $post_data POST data
     * @return array Saved settings
     */
    private function save_settings($post_data) {
        $settings = array();

        if (isset($post_data['ai_core_settings']) && is_array($post_data['ai_core_settings'])) {
            $input = $post_data['ai_core_settings'];

            // Sanitize API keys
            $settings['openai_api_key'] = isset($input['openai_api_key']) ? sanitize_text_field($input['openai_api_key']) : '';
            $settings['anthropic_api_key'] = isset($input['anthropic_api_key']) ? sanitize_text_field($input['anthropic_api_key']) : '';
            $settings['gemini_api_key'] = isset($input['gemini_api_key']) ? sanitize_text_field($input['gemini_api_key']) : '';
            $settings['grok_api_key'] = isset($input['grok_api_key']) ? sanitize_text_field($input['grok_api_key']) : '';

            // Determine which providers are configured
            $configured_providers = array();
            if (!empty($settings['openai_api_key'])) $configured_providers[] = 'openai';
            if (!empty($settings['anthropic_api_key'])) $configured_providers[] = 'anthropic';
            if (!empty($settings['gemini_api_key'])) $configured_providers[] = 'gemini';
            if (!empty($settings['grok_api_key'])) $configured_providers[] = 'grok';

            // Sanitize default provider - use first configured if provided default is not configured
            $default_provider = isset($input['default_provider']) ? sanitize_text_field($input['default_provider']) : '';
            if (empty($default_provider) || !in_array($default_provider, $configured_providers)) {
                $default_provider = !empty($configured_providers) ? $configured_providers[0] : 'openai';
            }
            $settings['default_provider'] = $default_provider;

            $settings['enable_stats'] = !empty($input['enable_stats']);
            $settings['enable_caching'] = !empty($input['enable_caching']);
            $settings['cache_duration'] = 3600;
        }

        update_option('ai_core_settings', $settings);

        return $settings;
    }
    
    /**
     * Render statistics page
     *
     * @return void
     */
    public function render_stats_page() {
        $stats = AI_Core_Stats::get_instance();
        
        ?>
        <div class="wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="ai-core-stats-page">
                <?php echo $stats->format_stats_html(); ?>
                
                <p>
                    <button type="button" class="button" id="ai-core-reset-stats">
                        <?php esc_html_e('Reset Statistics', 'ai-core'); ?>
                    </button>
                </p>
            </div>
        </div>
        <?php
    }
    
    /**
     * Render add-ons page
     *
     * @return void
     */
    public function render_addons_page() {
        $addons = AI_Core_Addons::get_instance();
        $addons->render_addons_page();
    }
}

