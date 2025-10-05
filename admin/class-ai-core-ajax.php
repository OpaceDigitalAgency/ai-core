<?php
/**
 * AI-Core AJAX Class
 * 
 * Handles AJAX requests for admin interface
 * 
 * @package AI_Core
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AI-Core AJAX Class
 * 
 * Manages AJAX handlers
 */
class AI_Core_AJAX {
    
    /**
     * Class instance
     * 
     * @var AI_Core_AJAX
     */
    private static $instance = null;
    
    /**
     * Get class instance
     * 
     * @return AI_Core_AJAX
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
     * Initialize AJAX handlers
     *
     * @return void
     */
    private function init() {
        add_action('wp_ajax_ai_core_test_api_key', array($this, 'test_api_key'));
        add_action('wp_ajax_ai_core_get_models', array($this, 'get_models'));
        add_action('wp_ajax_ai_core_get_model_capabilities', array($this, 'get_model_capabilities'));
        add_action('wp_ajax_ai_core_reset_stats', array($this, 'reset_stats'));
        add_action('wp_ajax_ai_core_test_prompt', array($this, 'test_prompt'));
        add_action('wp_ajax_ai_core_save_api_key', array($this, 'save_api_key'));
        add_action('wp_ajax_ai_core_clear_api_key', array($this, 'clear_api_key'));
        // NOTE: ai_core_get_prompts and ai_core_run_prompt are handled by AI_Core_Prompt_Library class
        // Removed duplicate handlers to prevent conflicts
    }

    /**
     * Persist API key immediately
     *
     * @return void
     */
    public function save_api_key() {
        check_ajax_referer('ai_core_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'ai-core')));
        }

        $provider = isset($_POST['provider']) ? sanitize_text_field($_POST['provider']) : '';
        $api_key = isset($_POST['api_key']) ? sanitize_text_field(wp_unslash($_POST['api_key'])) : '';

        if (empty($provider) || empty($api_key)) {
            wp_send_json_error(array('message' => __('Provider and API key are required', 'ai-core')));
        }

        $validator = AI_Core_Validator::get_instance();
        $validation = $validator->validate_api_key($provider, $api_key);

        if (empty($validation['valid'])) {
            $message = $validation['error'] ?? __('API key validation failed', 'ai-core');
            wp_send_json_error(array('message' => $message));
        }

        $settings = get_option('ai_core_settings', array());
        $field = $provider . '_api_key';
        $settings[$field] = $api_key;

        if (empty($settings['default_provider'])) {
            $settings['default_provider'] = $provider;
        }

        if (!isset($settings['provider_models']) || !is_array($settings['provider_models'])) {
            $settings['provider_models'] = array();
        }

        update_option('ai_core_settings', $settings);

        $models = $validator->get_available_models($provider, $api_key, true);

        if (!empty($models)) {
            $preferredModel = \AICore\Registry\ModelRegistry::getPreferredModel($provider, $models);
            if (!isset($settings['provider_models'][$provider]) || empty($settings['provider_models'][$provider])) {
                $settings['provider_models'][$provider] = $preferredModel;
                update_option('ai_core_settings', $settings);
            }
        } else {
            $preferredModel = \AICore\Registry\ModelRegistry::getPreferredModel($provider);
        }

        $parameterSchema = $preferredModel ? \AICore\Registry\ModelRegistry::getParameterSchema($preferredModel) : array();

        wp_send_json_success(array(
            'message' => __('API key saved successfully.', 'ai-core'),
            'provider' => $provider,
            'models' => $models,
            'count' => count($models),
            'default_provider' => $settings['default_provider'],
            'masked_key' => str_repeat('•', max(0, strlen($api_key) - 4)) . substr($api_key, -4),
            'selected_model' => $settings['provider_models'][$provider] ?? '',
            'preferred_model' => $preferredModel,
            'parameters' => $parameterSchema,
            'model_meta' => \AICore\Registry\ModelRegistry::exportProviderMetadata()[$provider] ?? array(),
        ));
    }

    /**
     * Clear stored API key for a provider
     *
     * @return void
     */
    public function clear_api_key() {
        check_ajax_referer('ai_core_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'ai-core')));
        }

        $provider = isset($_POST['provider']) ? sanitize_text_field($_POST['provider']) : '';

        if (empty($provider)) {
            wp_send_json_error(array('message' => __('Provider is required', 'ai-core')));
        }

        $settings = get_option('ai_core_settings', array());
        $field = $provider . '_api_key';

        if (isset($settings[$field])) {
            $settings[$field] = '';
        }

        if (isset($settings['provider_models'][$provider])) {
            unset($settings['provider_models'][$provider]);
        }

        if (isset($settings['provider_options'][$provider])) {
            unset($settings['provider_options'][$provider]);
        }

        if (!empty($settings['default_provider']) && $settings['default_provider'] === $provider) {
            $settings['default_provider'] = $this->get_next_configured_provider($settings);
        }

        update_option('ai_core_settings', $settings);

        $cache_prefix = 'ai_core_models_' . $provider;
        $this->purge_model_cache($cache_prefix);

        wp_send_json_success(array(
            'message' => __('API key removed.', 'ai-core'),
            'provider' => $provider,
            'default_provider' => $settings['default_provider'],
        ));
    }
    
    /**
     * Test API key
     *
     * @return void
     */
    public function test_api_key() {
        check_ajax_referer('ai_core_admin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'ai-core')));
        }
        
        $provider = isset($_POST['provider']) ? sanitize_text_field($_POST['provider']) : '';
        $api_key = isset($_POST['api_key']) ? sanitize_text_field($_POST['api_key']) : '';
        
        if (empty($provider) || empty($api_key)) {
            wp_send_json_error(array('message' => __('Provider and API key are required', 'ai-core')));
        }
        
        $validator = AI_Core_Validator::get_instance();
        $result = $validator->validate_api_key($provider, $api_key);
        
        if ($result['valid']) {
            wp_send_json_success(array(
                'message' => __('API key is valid!', 'ai-core'),
                'provider' => $result['provider'] ?? $provider
            ));
        } else {
            wp_send_json_error(array(
                'message' => $result['error'] ?? __('API key validation failed', 'ai-core')
            ));
        }
    }
    
    /**
     * Get available models for a provider
     *
     * @return void
     */
    public function get_models() {
        check_ajax_referer('ai_core_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'ai-core')));
        }

        $provider = isset($_POST['provider']) ? sanitize_text_field($_POST['provider']) : '';

        if (empty($provider)) {
            wp_send_json_error(array('message' => __('Provider is required', 'ai-core')));
        }

        $api_key = isset($_POST['api_key']) ? sanitize_text_field(wp_unslash($_POST['api_key'])) : '';
        $force_refresh = !empty($_POST['force_refresh']);

        $validator = AI_Core_Validator::get_instance();
        $models = $validator->get_available_models($provider, $api_key ?: null, (bool) $force_refresh);
        $preferredModel = \AICore\Registry\ModelRegistry::getPreferredModel($provider, $models);

        $settings = get_option('ai_core_settings', array());
        $has_saved_key = !empty($settings[$provider . '_api_key']);

        wp_send_json_success(array(
            'models' => $models,
            'count' => count($models),
            'provider' => $provider,
            'has_saved_key' => $has_saved_key,
            'preferred_model' => $preferredModel,
            'parameters' => $preferredModel ? \AICore\Registry\ModelRegistry::getParameterSchema($preferredModel) : array(),
            'model_meta' => \AICore\Registry\ModelRegistry::exportProviderMetadata()[$provider] ?? array(),
        ));
    }

    /**
     * Get model capabilities (supported parameters)
     *
     * @return void
     */
    public function get_model_capabilities() {
        check_ajax_referer('ai_core_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'ai-core')));
        }

        $model = isset($_POST['model']) ? sanitize_text_field($_POST['model']) : '';
        $provider = isset($_POST['provider']) ? sanitize_text_field($_POST['provider']) : '';

        if (empty($model) || empty($provider)) {
            wp_send_json_error(array('message' => __('Model and provider are required', 'ai-core')));
        }

        $capabilities = \AICore\Registry\ModelRegistry::getParameterSchema($model);

        wp_send_json_success(array(
            'model' => $model,
            'provider' => $provider,
            'capabilities' => $capabilities
        ));
    }

    /**
     * Remove cached model entries when clearing keys
     *
     * @param string $cache_prefix Prefix used for model cache transient
     * @return void
     */
    private function purge_model_cache($cache_prefix) {
        global $wpdb;

        $like = $wpdb->esc_like('_transient_' . $cache_prefix);
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                $like . '%'
            )
        );

        $timeout_like = $wpdb->esc_like('_transient_timeout_' . $cache_prefix);
        $wpdb->query(
            $wpdb->prepare(
                "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
                $timeout_like . '%'
            )
        );
    }

    /**
     * Determine next configured provider for defaults
     *
     * @param array $settings Current settings array
     * @return string Provider key or empty string
     */
    private function get_next_configured_provider($settings) {
        foreach (array('openai', 'anthropic', 'gemini', 'grok') as $provider) {
            if (!empty($settings[$provider . '_api_key'])) {
                return $provider;
            }
        }

        return '';
    }
    
    /**
     * Reset statistics
     *
     * @return void
     */
    public function reset_stats() {
        check_ajax_referer('ai_core_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'ai-core')));
        }

        $stats = AI_Core_Stats::get_instance();
        $result = $stats->reset_stats();

        if ($result) {
            wp_send_json_success(array(
                'message' => __('Statistics reset successfully', 'ai-core')
            ));
        } else {
            wp_send_json_error(array(
                'message' => __('Failed to reset statistics', 'ai-core')
            ));
        }
    }

    /**
     * Test prompt (for testing in Settings page)
     *
     * @return void
     */
    public function test_prompt() {
        check_ajax_referer('ai_core_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'ai-core')));
        }

        $prompt_content = isset($_POST['prompt']) ? wp_kses_post($_POST['prompt']) : '';
        $provider = isset($_POST['provider']) ? sanitize_text_field($_POST['provider']) : '';
        $model = isset($_POST['model']) ? sanitize_text_field($_POST['model']) : '';
        $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : 'text';

        if (empty($prompt_content)) {
            wp_send_json_error(array('message' => __('Prompt content is required', 'ai-core')));
        }

        if (empty($provider)) {
            wp_send_json_error(array('message' => __('Provider is required', 'ai-core')));
        }

        if (empty($model) && $type === 'text') {
            $saved_model = $settings['provider_models'][$provider] ?? '';
            if (!empty($saved_model)) {
                $model = $saved_model;
            } else {
                wp_send_json_error(array('message' => __('Model is required for text generation', 'ai-core')));
            }
        }

        // Get settings to check if API keys are configured
        $settings = get_option('ai_core_settings', array());

        // Check if any API key is configured
        $has_key = !empty($settings['openai_api_key']) ||
                   !empty($settings['anthropic_api_key']) ||
                   !empty($settings['gemini_api_key']) ||
                   !empty($settings['grok_api_key']);

        if (!$has_key) {
            wp_send_json_error(array('message' => __('AI-Core is not configured. Please add at least one API key.', 'ai-core')));
        }

        // Initialize AI-Core with current settings
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
        } else {
            wp_send_json_error(array('message' => __('AI-Core library not found.', 'ai-core')));
        }

        try {
            if ($type === 'image') {
                // For image generation - pass the model to the image provider
                $image_options = array();

                // If model is specified, use it (important for Gemini image models)
                if (!empty($model)) {
                    $image_options['model'] = $model;
                }

                $result = \AICore\AICore::generateImage($prompt_content, $image_options, $provider);
                $image_url = $result['url'] ?? $result['data'][0]['url'] ?? '';

                wp_send_json_success(array(
                    'result' => $image_url,
                    'type' => 'image',
                    'model' => $model,
                    'provider' => $provider,
                ));
            } else {
                // For text generation - use the selected model directly
                // Model is now selected by user from dropdown, not hardcoded
                $messages = array(
                    array(
                        'role' => 'user',
                        'content' => $prompt_content
                    )
                );

                $options = array('model' => $model);

                // Only apply provider options that are supported by the specific model
                if (!empty($settings['provider_options'][$provider]) && is_array($settings['provider_options'][$provider])) {
                    // Get the model's parameter schema to check which parameters are supported
                    $modelRegistry = \AICore\Registry\ModelRegistry::class;
                    if (class_exists($modelRegistry)) {
                        $parameterSchema = $modelRegistry::getParameterSchema($model);
                        $supportedParams = array_keys($parameterSchema);

                        // Only merge parameters that the model actually supports
                        foreach ($settings['provider_options'][$provider] as $key => $value) {
                            if (in_array($key, $supportedParams, true)) {
                                $options[$key] = $value;
                            }
                        }
                    } else {
                        // Fallback: merge all options if ModelRegistry not available
                        $options = array_merge($options, $settings['provider_options'][$provider]);
                    }
                }

                $result = \AICore\AICore::sendTextRequest($model, $messages, $options);

                // Use the library's extractContent method to properly extract text from normalized response
                $text_response = \AICore\AICore::extractContent($result);

                wp_send_json_success(array(
                    'result' => $text_response,
                    'type' => 'text',
                    'model' => $model,
                    'provider' => $provider,
                ));
            }
        } catch (\Exception $e) {
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }

    // NOTE: get_prompts() method removed - it's handled by AI_Core_Prompt_Library class
    // This prevents duplicate AJAX handler registration which causes the second handler to never run
}

// Initialize AJAX handlers
AI_Core_AJAX::get_instance();
