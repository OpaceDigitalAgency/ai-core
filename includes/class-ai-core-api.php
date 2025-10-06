<?php
/**
 * AI-Core API Class
 * 
 * Provides public API for add-on plugins to access AI-Core functionality
 * 
 * @package AI_Core
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AI-Core API Class
 * 
 * Public API for add-on plugins
 */
class AI_Core_API {
    
    /**
     * Class instance
     * 
     * @var AI_Core_API
     */
    private static $instance = null;
    
    /**
     * Get class instance
     * 
     * @return AI_Core_API
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
    }
    
    /**
     * Check if AI-Core is configured
     * 
     * @return bool True if at least one API key is configured
     */
    public function is_configured() {
        $settings = get_option('ai_core_settings', array());
        
        return !empty($settings['openai_api_key']) ||
               !empty($settings['anthropic_api_key']) ||
               !empty($settings['gemini_api_key']) ||
               !empty($settings['grok_api_key']);
    }
    
    /**
     * Get configured providers
     * 
     * @return array List of configured provider names
     */
    public function get_configured_providers() {
        $settings = get_option('ai_core_settings', array());
        $providers = array();
        
        if (!empty($settings['openai_api_key'])) {
            $providers[] = 'openai';
        }
        if (!empty($settings['anthropic_api_key'])) {
            $providers[] = 'anthropic';
        }
        if (!empty($settings['gemini_api_key'])) {
            $providers[] = 'gemini';
        }
        if (!empty($settings['grok_api_key'])) {
            $providers[] = 'grok';
        }
        
        return $providers;
    }
    
    /**
     * Get API key for a provider
     * 
     * @param string $provider Provider name
     * @return string|null API key or null if not configured
     */
    public function get_api_key($provider) {
        $settings = get_option('ai_core_settings', array());
        $key_name = $provider . '_api_key';
        
        return $settings[$key_name] ?? null;
    }
    
    /**
     * Get default provider
     * 
     * @return string Default provider name
     */
    public function get_default_provider() {
        $settings = get_option('ai_core_settings', array());
        return $settings['default_provider'] ?? 'openai';
    }
    
    /**
     * Get available models for a provider
     * 
     * @param string $provider Provider name
     * @return array List of available models
     */
    public function get_available_models($provider) {
        $validator = AI_Core_Validator::get_instance();
        return $validator->get_available_models($provider);
    }
    
    /**
     * Send text generation request
     * 
     * @param string $model Model identifier
     * @param array $messages Messages array
     * @param array $options Request options
     * @return array|WP_Error Response or error
     */
    public function send_text_request($model, $messages, $options = array()) {
        if (!$this->is_configured()) {
            return new WP_Error('not_configured', __('AI-Core is not configured. Please add at least one API key.', 'ai-core'));
        }
        
        try {
            if (!class_exists('AICore\\AICore')) {
                return new WP_Error('library_missing', __('AI-Core library not found.', 'ai-core'));
            }
            
            $response = \AICore\AICore::sendTextRequest($model, $messages, $options);
            
            // Track usage if enabled
            $this->track_usage($model, $response);
            
            return $response;
            
        } catch (Exception $e) {
            return new WP_Error('request_failed', $e->getMessage());
        }
    }
    
    /**
     * Generate image
     * 
     * @param string $prompt Image prompt
     * @param array $options Image options
     * @param string $provider Provider name
     * @return array|WP_Error Response or error
     */
    public function generate_image($prompt, $options = array(), $provider = 'openai') {
        if (!$this->is_configured()) {
            return new WP_Error('not_configured', __('AI-Core is not configured. Please add at least one API key.', 'ai-core'));
        }

        try {
            if (!class_exists('AICore\\AICore')) {
                return new WP_Error('library_missing', __('AI-Core library not found.', 'ai-core'));
            }

            $response = \AICore\AICore::generateImage($prompt, $options, $provider);

            // Track usage if enabled - use actual model from options or response
            $model = $options['model'] ?? $response['model'] ?? 'image-' . $provider;
            $this->track_usage($model, $response);

            return $response;

        } catch (Exception $e) {
            return new WP_Error('request_failed', $e->getMessage());
        }
    }
    
    /**
     * Track API usage
     *
     * @param string $model Model used
     * @param array $response API response
     * @return void
     */
    private function track_usage($model, $response) {
        $settings = get_option('ai_core_settings', array());

        if (empty($settings['enable_stats'])) {
            return;
        }

        $stats = get_option('ai_core_stats', array());

        // Detect provider
        $provider = $this->detect_provider_from_model($model);

        // Initialise model stats if not exists
        if (!isset($stats[$model])) {
            $stats[$model] = array(
                'requests' => 0,
                'input_tokens' => 0,
                'output_tokens' => 0,
                'total_tokens' => 0,
                'total_cost' => 0,
                'errors' => 0,
                'last_used' => null,
                'provider' => $provider
            );
        }

        // Update request count and timestamp
        $stats[$model]['requests']++;
        $stats[$model]['last_used'] = current_time('mysql');
        $stats[$model]['provider'] = $provider;

        // Track error
        if (isset($response['error'])) {
            $stats[$model]['errors']++;
            update_option('ai_core_stats', $stats);
            return;
        }

        // Extract token usage
        $input_tokens = 0;
        $output_tokens = 0;
        $total_tokens = 0;

        // Check if this is an image generation request
        $is_image = (strpos($model, 'dall-e') !== false ||
                     strpos($model, 'imagen') !== false ||
                     strpos($model, 'grok-') !== false && strpos($model, 'image') !== false ||
                     strpos($model, 'gemini-') !== false && strpos($model, 'image') !== false ||
                     strpos($model, 'image-') === 0);

        if ($is_image) {
            // For image generation, count as 1 image (represented as 1 output token for cost calculation)
            $output_tokens = 1;
            $total_tokens = 1;
        } elseif (isset($response['usage'])) {
            $usage = $response['usage'];

            // Try different token field names used by different providers
            $input_tokens = $usage['prompt_tokens'] ?? $usage['input_tokens'] ?? 0;
            $output_tokens = $usage['completion_tokens'] ?? $usage['output_tokens'] ?? 0;
            $total_tokens = $usage['total_tokens'] ?? ($input_tokens + $output_tokens);
        }

        // Update token counts
        $stats[$model]['input_tokens'] += $input_tokens;
        $stats[$model]['output_tokens'] += $output_tokens;
        $stats[$model]['total_tokens'] += $total_tokens;

        // Calculate and add cost
        if (class_exists('AI_Core_Pricing')) {
            $pricing = AI_Core_Pricing::get_instance();
            $cost = $pricing->calculate_cost($model, $input_tokens, $output_tokens, $provider);

            if ($cost !== null) {
                $stats[$model]['total_cost'] += $cost;
            }
        }

        update_option('ai_core_stats', $stats);
    }

    /**
     * Detect provider from model name
     *
     * @param string $model Model identifier
     * @return string|null Provider name
     */
    private function detect_provider_from_model($model) {
        $model_lower = strtolower($model);

        if (strpos($model_lower, 'gpt') === 0 || strpos($model_lower, 'o1') === 0 ||
            strpos($model_lower, 'o3') === 0 || strpos($model_lower, 'dall-e') === 0 ||
            strpos($model_lower, 'image-openai') === 0) {
            return 'openai';
        }

        if (strpos($model_lower, 'claude') === 0 || strpos($model_lower, 'image-anthropic') === 0) {
            return 'anthropic';
        }

        if (strpos($model_lower, 'gemini') === 0 || strpos($model_lower, 'imagen') === 0 ||
            strpos($model_lower, 'image-gemini') === 0) {
            return 'gemini';
        }

        if (strpos($model_lower, 'grok') === 0 || strpos($model_lower, 'image-grok') === 0) {
            return 'grok';
        }

        return null;
    }
    
    /**
     * Get usage statistics
     * 
     * @return array Usage statistics
     */
    public function get_stats() {
        return get_option('ai_core_stats', array());
    }
    
    /**
     * Reset usage statistics
     * 
     * @return bool Success status
     */
    public function reset_stats() {
        return update_option('ai_core_stats', array());
    }
}

/**
 * Get AI-Core API instance
 * 
 * @return AI_Core_API
 */
function ai_core() {
    return AI_Core_API::get_instance();
}
