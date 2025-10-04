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
        add_action('wp_ajax_ai_core_get_prompts', array($this, 'get_prompts'));
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
        
        $validator = AI_Core_Validator::get_instance();
        $models = $validator->get_available_models($provider);
        
        wp_send_json_success(array(
            'models' => $models,
            'count' => count($models)
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
        $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : 'text';

        if (empty($prompt_content)) {
            wp_send_json_error(array('message' => __('Prompt content is required', 'ai-core')));
        }

        $api = AI_Core_API::get_instance();

        try {
            if ($type === 'image') {
                $result = $api->generate_image($prompt_content, $provider);
            } else {
                $result = $api->send_text_request($prompt_content, $provider);
            }

            if (is_wp_error($result)) {
                wp_send_json_error(array('message' => $result->get_error_message()));
            } else {
                wp_send_json_success(array(
                    'result' => $result,
                    'type' => $type,
                ));
            }
        } catch (Exception $e) {
            wp_send_json_error(array('message' => $e->getMessage()));
        }
    }

    /**
     * Get prompts (for loading in Settings page)
     *
     * @return void
     */
    public function get_prompts() {
        check_ajax_referer('ai_core_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'ai-core')));
        }

        $prompt_library = AI_Core_Prompt_Library::get_instance();
        $prompts = $prompt_library->get_prompts();

        wp_send_json_success(array('prompts' => $prompts));
    }
}

// Initialize AJAX handlers
AI_Core_AJAX::get_instance();

