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
        add_action('wp_ajax_ai_core_reset_stats', array($this, 'reset_stats'));
        add_action('wp_ajax_ai_core_run_prompt', array($this, 'run_prompt'));
        // NOTE: ai_core_get_prompts is handled by AI_Core_Prompt_Library class, not here
        // Removed duplicate handler to prevent conflicts
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

        $settings = get_option('ai_core_settings', array());
        $has_saved_key = !empty($settings[$provider . '_api_key']);

        wp_send_json_success(array(
            'models' => $models,
            'count' => count($models),
            'provider' => $provider,
            'has_saved_key' => $has_saved_key
        ));
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
     * Run prompt (for testing in Settings page)
     *
     * @return void
     */
    public function run_prompt() {
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
            wp_send_json_error(array('message' => __('Model is required for text generation', 'ai-core')));
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
                // For image generation
                $result = \AICore\AICore::generateImage($prompt_content, array(), $provider);
                $image_url = $result['url'] ?? $result['data'][0]['url'] ?? '';

                wp_send_json_success(array(
                    'result' => $image_url,
                    'type' => 'image',
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

                $result = \AICore\AICore::sendTextRequest($model, $messages);

                // Use the library's extractContent method to properly extract text from normalized response
                $text_response = \AICore\AICore::extractContent($result);

                wp_send_json_success(array(
                    'result' => $text_response,
                    'type' => 'text',
                    'model' => $model,
                    'provider' => $provider,
                ));
            }
        } catch (Exception $e) {
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }

    // NOTE: get_prompts() method removed - it's handled by AI_Core_Prompt_Library class
    // This prevents duplicate AJAX handler registration which causes the second handler to never run
}

// Initialize AJAX handlers
AI_Core_AJAX::get_instance();
