<?php
/**
 * AI-Core Settings Class
 * 
 * Handles plugin settings management using WordPress Settings API
 * 
 * @package AI_Core
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AI-Core Settings Class
 * 
 * Manages plugin settings and configuration
 */
class AI_Core_Settings {
    
    /**
     * Class instance
     * 
     * @var AI_Core_Settings
     */
    private static $instance = null;
    
    /**
     * Settings group name
     * 
     * @var string
     */
    private $settings_group = 'ai_core_settings_group';
    
    /**
     * Settings page slug
     * 
     * @var string
     */
    private $settings_page = 'ai-core-settings';
    
    /**
     * Option name
     * 
     * @var string
     */
    private $option_name = 'ai_core_settings';
    
    /**
     * Get class instance
     * 
     * @return AI_Core_Settings
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
     * Initialize settings
     *
     * @return void
     */
    private function init() {
        // Register settings immediately since this is already called in admin_init
        $this->register_settings();
    }
    
    /**
     * Register plugin settings
     *
     * @return void
     */
    public function register_settings() {
        // Register settings
        register_setting(
            $this->settings_group,
            $this->option_name,
            array(
                'type' => 'array',
                'sanitize_callback' => array($this, 'sanitize_settings'),
                'default' => $this->get_default_settings(),
                'show_in_rest' => false
            )
        );

        // Add settings sections
        $this->add_settings_sections();

        // Add settings fields
        $this->add_settings_fields();
    }
    
    /**
     * Add settings sections
     * 
     * @return void
     */
    private function add_settings_sections() {
        // API Keys Section
        add_settings_section(
            'ai_core_api_keys_section',
            __('API Keys Configuration', 'ai-core'),
            array($this, 'api_keys_section_callback'),
            $this->settings_page
        );

        // Provider Configuration Section
        add_settings_section(
            'ai_core_provider_section',
            __('Provider Configuration', 'ai-core'),
            array($this, 'provider_section_callback'),
            $this->settings_page
        );
        
        // General Settings Section
        add_settings_section(
            'ai_core_general_section',
            __('General Settings', 'ai-core'),
            array($this, 'general_section_callback'),
            $this->settings_page
        );

        // Test Prompt Section
        add_settings_section(
            'ai_core_test_prompt_section',
            __('Test Prompt', 'ai-core'),
            array($this, 'test_prompt_section_callback'),
            $this->settings_page
        );
    }
    
    /**
     * Add settings fields
     *
     * @return void
     */
    private function add_settings_fields() {
        // OpenAI API Key
        add_settings_field(
            'openai_api_key',
            __('OpenAI API Key', 'ai-core'),
            array($this, 'api_key_field_callback'),
            $this->settings_page,
            'ai_core_api_keys_section',
            array('provider' => 'openai', 'label' => 'OpenAI')
        );
        
        // Anthropic API Key
        add_settings_field(
            'anthropic_api_key',
            __('Anthropic API Key', 'ai-core'),
            array($this, 'api_key_field_callback'),
            $this->settings_page,
            'ai_core_api_keys_section',
            array('provider' => 'anthropic', 'label' => 'Anthropic Claude')
        );
        
        // Gemini API Key
        add_settings_field(
            'gemini_api_key',
            __('Google Gemini API Key', 'ai-core'),
            array($this, 'api_key_field_callback'),
            $this->settings_page,
            'ai_core_api_keys_section',
            array('provider' => 'gemini', 'label' => 'Google Gemini')
        );
        
        // Grok API Key
        add_settings_field(
            'grok_api_key',
            __('xAI Grok API Key', 'ai-core'),
            array($this, 'api_key_field_callback'),
            $this->settings_page,
            'ai_core_api_keys_section',
            array('provider' => 'grok', 'label' => 'xAI Grok')
        );

        // Provider defaults configuration
        add_settings_field(
            'provider_defaults',
            __('Provider Defaults', 'ai-core'),
            array($this, 'provider_settings_field_callback'),
            $this->settings_page,
            'ai_core_provider_section'
        );
        
        // Default Provider
        add_settings_field(
            'default_provider',
            __('Default Provider', 'ai-core'),
            array($this, 'default_provider_field_callback'),
            $this->settings_page,
            'ai_core_general_section'
        );
        
        // Enable Stats
        add_settings_field(
            'enable_stats',
            __('Enable Usage Statistics', 'ai-core'),
            array($this, 'checkbox_field_callback'),
            $this->settings_page,
            'ai_core_general_section',
            array('field' => 'enable_stats', 'label' => 'Track API usage statistics')
        );
        
        // Enable Caching
        add_settings_field(
            'enable_caching',
            __('Enable Model Caching', 'ai-core'),
            array($this, 'checkbox_field_callback'),
            $this->settings_page,
            'ai_core_general_section',
            array('field' => 'enable_caching', 'label' => 'Cache available models list')
        );

        // Persist Settings on Uninstall
        add_settings_field(
            'persist_on_uninstall',
            __('Persist Settings on Uninstall', 'ai-core'),
            array($this, 'checkbox_field_callback'),
            $this->settings_page,
            'ai_core_general_section',
            array('field' => 'persist_on_uninstall', 'label' => 'Keep API keys and settings when plugin is deleted (recommended)')
        );

        // Test Prompt Field
        add_settings_field(
            'test_prompt',
            '',
            array($this, 'test_prompt_field_callback'),
            $this->settings_page,
            'ai_core_test_prompt_section'
        );
    }
    
    /**
     * API keys section callback
     *
     * @return void
     */
    public function api_keys_section_callback() {
        echo '<p>' . esc_html__('Configure your AI provider API keys. At least one API key is required for the plugin to function.', 'ai-core') . '</p>';
        echo '<p style="background: #f0f6fc; border-left: 4px solid #2271b1; padding: 12px; margin: 16px 0;">';
        echo '<span class="dashicons dashicons-info" style="color: #2271b1;"></span> ';
        echo '<strong>' . esc_html__('Auto-Validation:', 'ai-core') . '</strong> ';
        echo esc_html__('API keys are automatically validated and saved when you paste them. No need to click a "Test" button!', 'ai-core');
        echo '</p>';
    }

    /**
     * Provider section callback
     *
     * @return void
     */
    public function provider_section_callback() {
        echo '<p>' . esc_html__('Choose default models and tuning options for each provider. These settings are applied across all AI-Core integrations.', 'ai-core') . '</p>';
    }
    
    /**
     * General section callback
     *
     * @return void
     */
    public function general_section_callback() {
        echo '<p>' . esc_html__('Configure general plugin settings.', 'ai-core') . '</p>';
    }

    /**
     * Test prompt section callback
     *
     * @return void
     */
    public function test_prompt_section_callback() {
        echo '<p>' . esc_html__('Test your AI providers with a prompt. You can load saved prompts from the Prompt Library.', 'ai-core') . '</p>';
    }

    /**
     * Test prompt field callback
     *
     * @return void
     */
    public function test_prompt_field_callback() {
        ?>
        <div class="ai-core-test-prompt-wrapper">
            <div class="ai-core-prompt-loader">
                <label for="ai-core-load-prompt"><?php esc_html_e('Load from Library:', 'ai-core'); ?></label>
                <select id="ai-core-load-prompt" class="regular-text">
                    <option value=""><?php esc_html_e('-- Select a prompt --', 'ai-core'); ?></option>
                </select>
                <button type="button" class="button" id="ai-core-refresh-prompts">
                    <span class="dashicons dashicons-update"></span>
                    <?php esc_html_e('Refresh', 'ai-core'); ?>
                </button>
            </div>

            <div class="ai-core-test-prompt-form">
                <textarea id="ai-core-test-prompt-content" rows="6" class="large-text" placeholder="<?php esc_attr_e('Enter your test prompt here...', 'ai-core'); ?>"></textarea>

                <div class="ai-core-test-prompt-options">
                    <label for="ai-core-test-provider"><?php esc_html_e('Provider:', 'ai-core'); ?></label>
                    <select id="ai-core-test-provider">
                        <option value=""><?php esc_html_e('-- Select Provider --', 'ai-core'); ?></option>
                        <?php
                        // Only show configured providers
                        $api = AI_Core_API::get_instance();
                        $configured_providers = $api->get_configured_providers();
                        $provider_names = array(
                            'openai' => 'OpenAI',
                            'anthropic' => 'Anthropic Claude',
                            'gemini' => 'Google Gemini',
                            'grok' => 'xAI Grok'
                        );
                        foreach ($configured_providers as $provider) {
                            echo '<option value="' . esc_attr($provider) . '">' . esc_html($provider_names[$provider] ?? $provider) . '</option>';
                        }
                        ?>
                    </select>

                    <label for="ai-core-test-model"><?php esc_html_e('Model:', 'ai-core'); ?></label>
                    <select id="ai-core-test-model">
                        <option value=""><?php esc_html_e('-- Select Provider First --', 'ai-core'); ?></option>
                    </select>

                    <label for="ai-core-test-type"><?php esc_html_e('Type:', 'ai-core'); ?></label>
                    <select id="ai-core-test-type">
                        <option value="text"><?php esc_html_e('Text Generation', 'ai-core'); ?></option>
                        <option value="image"><?php esc_html_e('Image Generation', 'ai-core'); ?></option>
                    </select>

                    <button type="button" class="button button-primary" id="ai-core-run-test-prompt">
                        <span class="dashicons dashicons-controls-play"></span>
                        <?php esc_html_e('Run Prompt', 'ai-core'); ?>
                    </button>
                </div>

                <div id="ai-core-test-prompt-result" class="ai-core-test-prompt-result" style="display: none;"></div>
            </div>
        </div>
        <?php
    }
    
    /**
     * API key field callback
     *
     * @param array $args Field arguments
     * @return void
     */
    public function api_key_field_callback($args) {
        $settings = get_option($this->option_name, $this->get_default_settings());
        $provider = $args['provider'];
        $field_name = $provider . '_api_key';
        $value = $settings[$field_name] ?? '';
        $has_saved_key = !empty($value);
        $display_value = $has_saved_key ? '' : '';

        echo '<div class="ai-core-api-key-field" data-provider="' . esc_attr($provider) . '">';

        // Hidden field to store the actual key
        if ($has_saved_key) {
            echo '<input type="hidden" ';
            echo 'id="' . esc_attr($field_name) . '_saved" ';
            echo 'value="' . esc_attr($value) . '" />';
        }

        // Visible input field
        echo '<input type="text" ';
        echo 'id="' . esc_attr($field_name) . '" ';
        echo 'name="' . esc_attr($this->option_name) . '[' . esc_attr($field_name) . ']" ';
        echo 'value="' . esc_attr($display_value) . '" ';
        echo 'class="regular-text ai-core-api-key-input" ';
        echo 'data-has-saved="' . ($has_saved_key ? '1' : '0') . '" ';
        echo 'data-provider="' . esc_attr($provider) . '" ';
        echo 'placeholder="' . esc_attr($has_saved_key ? '••••••••••••••••••••' . substr($value, -4) : __('Enter your API key', 'ai-core')) . '" />';

        echo '<button type="button" class="button ai-core-refresh-models" data-provider="' . esc_attr($provider) . '"' . ($has_saved_key ? '' : ' disabled') . '>';
        echo esc_html__('Refresh Models', 'ai-core');
        echo '</button>';

        if ($has_saved_key) {
            echo '<button type="button" class="button ai-core-clear-key" data-field="' . esc_attr($field_name) . '">';
            echo esc_html__('Clear', 'ai-core');
            echo '</button>';
        }

        echo '<span class="ai-core-key-status" id="' . esc_attr($provider) . '-status"></span>';
        echo '</div>';

        if ($has_saved_key) {
            echo '<p class="description" style="color: #2271b1;">';
            echo '<span class="dashicons dashicons-yes-alt"></span> ';
            echo esc_html__('API key validated and saved. Paste a new key to replace it (auto-validates on entry).', 'ai-core');
            echo '</p>';
        } else {
            echo '<p class="description">';
            printf(
                esc_html__('Get your %s API key from their website. Keys are automatically validated and saved when you paste them.', 'ai-core'),
                esc_html($args['label'])
            );
            echo '</p>';
        }
    }

    /**
     * Provider settings field callback
     *
     * @return void
     */
    public function provider_settings_field_callback() {
        $settings = get_option($this->option_name, $this->get_default_settings());
        $provider_models = $settings['provider_models'] ?? array();
        $provider_options = $settings['provider_options'] ?? array();

        $providers = array(
            'openai' => 'OpenAI',
            'anthropic' => 'Anthropic Claude',
            'gemini' => 'Google Gemini',
            'grok' => 'xAI Grok'
        );

        $api = AI_Core_API::get_instance();

        echo '<div class="ai-core-provider-grid">';

        foreach ($providers as $key => $label) {
            $has_key = !empty($settings[$key . '_api_key']);
            $models = $has_key ? $api->get_available_models($key) : array();
            $selected_model = $provider_models[$key] ?? '';
            $options = $provider_options[$key] ?? array();
            $temperature = isset($options['temperature']) ? floatval($options['temperature']) : 0.7;
            $max_tokens = isset($options['max_tokens']) ? absint($options['max_tokens']) : 4000;

            echo '<div class="ai-core-provider-card" data-provider="' . esc_attr($key) . '" data-has-key="' . ($has_key ? '1' : '0') . '">';
            echo '<div class="ai-core-provider-card__header">';
            echo '<h4>' . esc_html($label) . '</h4>';
            echo '<span class="ai-core-provider-status ' . ($has_key ? 'is-active' : 'is-inactive') . '">';
            echo esc_html($has_key ? __('Connected', 'ai-core') : __('Awaiting API Key', 'ai-core'));
            echo '</span>';
            echo '</div>';

            echo '<div class="ai-core-provider-card__body">';

            echo '<label>' . esc_html__('Default Model', 'ai-core') . '</label>';
            echo '<select class="ai-core-provider-model" data-provider="' . esc_attr($key) . '" name="' . esc_attr($this->option_name) . '[provider_models][' . esc_attr($key) . ']" ' . ($has_key ? '' : 'disabled') . '>';

            if (!$has_key) {
                echo '<option value="">' . esc_html__('Add an API key to load models', 'ai-core') . '</option>';
            } else {
                if (empty($models)) {
                    echo '<option value="">' . esc_html__('Loading models...', 'ai-core') . '</option>';
                } else {
                    echo '<option value="">' . esc_html__('Select a model', 'ai-core') . '</option>';
                    foreach ($models as $model) {
                        echo '<option value="' . esc_attr($model) . '" ' . selected($selected_model, $model, false) . '>' . esc_html($model) . '</option>';
                    }
                }
            }

            echo '</select>';

            echo '<div class="ai-core-provider-tuning">';

            echo '<label>' . esc_html__('Temperature', 'ai-core') . '</label>';
            echo '<input type="number" class="small-text" min="0" max="2" step="0.1" name="' . esc_attr($this->option_name) . '[provider_options][' . esc_attr($key) . '][temperature]" value="' . esc_attr($temperature) . '" />';

            echo '<label>' . esc_html__('Max Tokens', 'ai-core') . '</label>';
            echo '<input type="number" class="small-text" min="1" max="200000" step="100" name="' . esc_attr($this->option_name) . '[provider_options][' . esc_attr($key) . '][max_tokens]" value="' . esc_attr($max_tokens) . '" />';

            echo '</div>'; // tuning

            echo '</div>'; // body

            echo '<div class="ai-core-provider-card__footer">';
            echo '<button type="button" class="button-link ai-core-provider-refresh" data-provider="' . esc_attr($key) . '"' . ($has_key ? '' : ' disabled') . '>' . esc_html__('Refresh models', 'ai-core') . '</button>';
            echo '</div>';

            echo '</div>'; // card
        }

        echo '</div>';
    }
    
    /**
     * Default provider field callback
     *
     * @return void
     */
    public function default_provider_field_callback() {
        $settings = get_option($this->option_name, $this->get_default_settings());
        $value = $settings['default_provider'] ?? 'openai';

        // Get configured providers
        $api = AI_Core_API::get_instance();
        $configured_providers = $api->get_configured_providers();

        $provider_names = array(
            'openai' => 'OpenAI',
            'anthropic' => 'Anthropic Claude',
            'gemini' => 'Google Gemini',
            'grok' => 'xAI Grok'
        );

        echo '<select id="default_provider" name="' . esc_attr($this->option_name) . '[default_provider]">';

        if (empty($configured_providers)) {
            echo '<option value="">' . esc_html__('-- No providers configured --', 'ai-core') . '</option>';
        } else {
            foreach ($configured_providers as $provider_key) {
                $provider_label = $provider_names[$provider_key] ?? $provider_key;
                echo '<option value="' . esc_attr($provider_key) . '" ' . selected($value, $provider_key, false) . '>';
                echo esc_html($provider_label);
                echo '</option>';
            }
        }

        echo '</select>';

        echo '<p class="description">' . esc_html__('Default AI provider for add-on plugins. Only configured providers are shown.', 'ai-core') . '</p>';
    }
    
    /**
     * Checkbox field callback
     *
     * @param array $args Field arguments
     * @return void
     */
    public function checkbox_field_callback($args) {
        $settings = get_option($this->option_name, $this->get_default_settings());
        $field = $args['field'];
        $value = $settings[$field] ?? false;
        
        echo '<label>';
        echo '<input type="checkbox" ';
        echo 'id="' . esc_attr($field) . '" ';
        echo 'name="' . esc_attr($this->option_name) . '[' . esc_attr($field) . ']" ';
        echo 'value="1" ';
        checked($value, true);
        echo '/> ';
        echo esc_html($args['label']);
        echo '</label>';
    }
    
    /**
     * Get default settings
     *
     * @return array Default settings
     */
    private function get_default_settings() {
        return array(
            'openai_api_key' => '',
            'anthropic_api_key' => '',
            'gemini_api_key' => '',
            'grok_api_key' => '',
            'default_provider' => 'openai',
            'enable_stats' => true,
            'enable_caching' => true,
            'cache_duration' => 3600,
            'persist_on_uninstall' => true,
            'provider_models' => array(),
            'provider_options' => array(),
        );
    }
    
    /**
     * Sanitize settings
     *
     * @param array $input Raw input values
     * @return array Sanitized values
     */
    public function sanitize_settings($input) {
        $sanitized = array();
        $existing_settings = get_option($this->option_name, $this->get_default_settings());

        // Sanitize API keys - preserve existing keys if input is empty or unchanged
        $api_keys = array('openai_api_key', 'anthropic_api_key', 'gemini_api_key', 'grok_api_key');
        foreach ($api_keys as $key) {
            if (isset($input[$key]) && !empty($input[$key])) {
                $new_value = sanitize_text_field($input[$key]);
                // Only update if the value has actually changed (not just the masked display)
                if ($new_value !== $existing_settings[$key]) {
                    $sanitized[$key] = $new_value;
                } else {
                    $sanitized[$key] = $existing_settings[$key];
                }
            } else {
                // Preserve existing key if input is empty
                $sanitized[$key] = $existing_settings[$key] ?? '';
            }
        }

        // Sanitize default provider
        $sanitized['default_provider'] = isset($input['default_provider']) ? sanitize_text_field($input['default_provider']) : 'openai';

        // Sanitize checkboxes
        $sanitized['enable_stats'] = isset($input['enable_stats']) && $input['enable_stats'] == '1';
        $sanitized['enable_caching'] = isset($input['enable_caching']) && $input['enable_caching'] == '1';
        $sanitized['persist_on_uninstall'] = isset($input['persist_on_uninstall']) && $input['persist_on_uninstall'] == '1';

        // Sanitize cache duration
        $sanitized['cache_duration'] = isset($input['cache_duration']) ? absint($input['cache_duration']) : 3600;

        // Sanitize provider models selections
        $sanitized['provider_models'] = $existing_settings['provider_models'] ?? array();
        if (isset($input['provider_models']) && is_array($input['provider_models'])) {
            foreach ($input['provider_models'] as $provider => $model) {
                if (!empty($model)) {
                    $sanitized['provider_models'][$provider] = sanitize_text_field($model);
                } else {
                    unset($sanitized['provider_models'][$provider]);
                }
            }
        }

        // Sanitize provider tuning options
        $sanitized['provider_options'] = $existing_settings['provider_options'] ?? array();
        if (isset($input['provider_options']) && is_array($input['provider_options'])) {
            foreach ($input['provider_options'] as $provider => $options) {
                $temperature = isset($options['temperature']) ? floatval($options['temperature']) : 0.7;
                $temperature = max(0.0, min(2.0, $temperature));

                $max_tokens = isset($options['max_tokens']) ? absint($options['max_tokens']) : 4000;
                $max_tokens = max(1, min(200000, $max_tokens));

                $sanitized['provider_options'][$provider] = array(
                    'temperature' => $temperature,
                    'max_tokens' => $max_tokens,
                );
            }
        }

        return $sanitized;
    }
    
    /**
     * Get setting value
     *
     * @param string $key Setting key
     * @param mixed $default Default value
     * @return mixed Setting value
     */
    public function get_setting($key, $default = null) {
        $settings = get_option($this->option_name, $this->get_default_settings());
        return $settings[$key] ?? $default;
    }
}
