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
     * Get provider settings including default model and options.
     *
     * @param string $provider Provider name
     * @return array {
     *     @type string $provider Provider key.
     *     @type array  $models   Available models for provider.
     *     @type string $model    Selected/default model id.
     *     @type array  $options  Request options (merged with defaults).
     *     @type array  $parameter_schema Parameter metadata for the model.
     * }
     */
    public function get_provider_settings($provider) {
        $settings = get_option('ai_core_settings', array());
        $available_models = $this->get_available_models($provider);

        if (empty($available_models) && class_exists('\\AICore\\Registry\\ModelRegistry')) {
            $available_models = \AICore\Registry\ModelRegistry::getModelsByProvider($provider);
        }

        $selected_model = $settings['provider_models'][$provider] ?? null;

        if (empty($selected_model) && class_exists('\\AICore\\Registry\\ModelRegistry')) {
            $preferred = \AICore\Registry\ModelRegistry::getPreferredModel($provider, $available_models);
            if (!empty($preferred)) {
                $selected_model = $preferred;
            }
        }

        if (empty($selected_model) && !empty($available_models)) {
            $selected_model = $available_models[0];
        }

        $stored_options = $settings['provider_options'][$provider] ?? array();
        $parameter_schema = array();
        $options = array();

        if ($selected_model && class_exists('\\AICore\\Registry\\ModelRegistry')) {
            $parameter_schema = \AICore\Registry\ModelRegistry::getParameterSchema($selected_model);

            foreach ($parameter_schema as $key => $meta) {
                if (isset($stored_options[$key]) && $stored_options[$key] !== '' && $stored_options[$key] !== null) {
                    $options[$key] = $stored_options[$key];
                } elseif (isset($meta['default'])) {
                    $options[$key] = $meta['default'];
                }
            }
        } else {
            $options = $stored_options;
        }

        return array(
            'provider' => $provider,
            'models' => $available_models,
            'model' => $selected_model,
            'options' => $options,
            'parameter_schema' => $parameter_schema,
        );
    }

    /**
     * Get the default model for a provider.
     *
     * @param string $provider Provider name
     * @return string|null Default model id or null if unavailable
     */
    public function get_default_model_for_provider($provider) {
        $settings = $this->get_provider_settings($provider);
        return $settings['model'] ?? null;
    }

    /**
     * Get provider request options for a model.
     *
     * @param string      $provider Provider name
     * @param string|null $model    Optional model id to normalise options for
     * @return array Normalised options array suitable for API requests
     */
    public function get_provider_options($provider, $model = null) {
        $settings = $this->get_provider_settings($provider);
        $options = $settings['options'] ?? array();

        if ($model && class_exists('\\AICore\\Registry\\ModelRegistry')) {
            $schema = \AICore\Registry\ModelRegistry::getParameterSchema($model);
            $normalised = array();

            foreach ($schema as $key => $meta) {
                if (isset($options[$key]) && $options[$key] !== '' && $options[$key] !== null) {
                    $normalised[$key] = $options[$key];
                } elseif (isset($meta['default'])) {
                    $normalised[$key] = $meta['default'];
                }
            }

            return $normalised;
        }

        return $options;
    }
    
    /**
     * Send text generation request
     * 
     * @param string $model Model identifier
     * @param array $messages Messages array
     * @param array $options Request options
     * @param array $usage_context Optional usage metadata (e.g. array('tool' => 'prompt_library'))
     * @return array|WP_Error Response or error
     */
    public function send_text_request($model, $messages, $options = array(), $usage_context = array()) {
        if (!$this->is_configured()) {
            return new WP_Error('not_configured', __('AI-Core is not configured. Please add at least one API key.', 'ai-core'));
        }
        
        try {
            if (!class_exists('AICore\\AICore')) {
                return new WP_Error('library_missing', __('AI-Core library not found.', 'ai-core'));
            }
            
            $response = \AICore\AICore::sendTextRequest($model, $messages, $options);

            // Track usage if enabled
            $this->track_usage($model, $response, $usage_context);
            
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
     * @param array $usage_context Optional usage metadata (e.g. array('tool' => 'prompt_library'))
     * @return array|WP_Error Response or error
     */
    public function generate_image($prompt, $options = array(), $provider = 'openai', $usage_context = array()) {
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
            $this->track_usage($model, $response, $usage_context);

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
     * @param array $usage_context Usage metadata for tool tracking
     * @return void
     */
    private function track_usage($model, $response, $usage_context = array()) {
        $settings = get_option('ai_core_settings', array());

        if (empty($settings['enable_stats'])) {
            return;
        }

        $stats = $this->normalize_stats_structure(get_option('ai_core_stats', array()));

        $model_stats = &$stats['models'];

        // Detect provider
        $provider = $this->detect_provider_from_model($model);

        // Initialise model stats if not exists
        if (!isset($model_stats[$model])) {
            $model_stats[$model] = array(
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
        $model_stats[$model]['requests']++;
        $model_stats[$model]['last_used'] = current_time('mysql');
        $model_stats[$model]['provider'] = $provider;

        // Track error
        if (isset($response['error'])) {
            $model_stats[$model]['errors']++;
            $this->increment_tool_usage($stats['tools'], $usage_context, array(
                'requests' => 1,
                'errors' => 1,
            ));
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
        $model_stats[$model]['input_tokens'] += $input_tokens;
        $model_stats[$model]['output_tokens'] += $output_tokens;
        $model_stats[$model]['total_tokens'] += $total_tokens;

        // Calculate and add cost
        $cost_increment = 0;
        if (class_exists('AI_Core_Pricing')) {
            $pricing = AI_Core_Pricing::get_instance();
            $cost = $pricing->calculate_cost($model, $input_tokens, $output_tokens, $provider);

            if ($cost !== null) {
                $model_stats[$model]['total_cost'] += $cost;
                $cost_increment = $cost;
            }
        }

        $this->increment_tool_usage($stats['tools'], $usage_context, array(
            'requests' => 1,
            'input_tokens' => $input_tokens,
            'output_tokens' => $output_tokens,
            'total_tokens' => $total_tokens,
            'total_cost' => $cost_increment,
        ));

        update_option('ai_core_stats', $stats);
    }

    /**
     * Ensure stats array has models/tools structure
     *
     * @param mixed $stats Raw stats option value
     * @return array Normalized stats structure
     */
    private function normalize_stats_structure($stats) {
        if (!is_array($stats)) {
            $stats = array();
        }

        if (!isset($stats['models']) || !is_array($stats['models'])) {
            $legacy = $stats;
            $stats = array(
                'models' => array(),
                'tools' => array(),
            );

            if (!isset($legacy['models'])) {
                foreach ($legacy as $key => $value) {
                    if (is_array($value) && isset($value['requests'])) {
                        $stats['models'][$key] = $value;
                    }
                }
            }
        }

        if (!isset($stats['tools']) || !is_array($stats['tools'])) {
            $stats['tools'] = array();
        }

        return $stats;
    }

    /**
     * Increment tool-level usage metrics.
     *
     * @param array $tools Reference to tools stats array
     * @param array $usage_context Context data including tool key
     * @param array $increments Metrics to increment
     * @return void
     */
    private function increment_tool_usage(array &$tools, array $usage_context, array $increments) {
        $tool_key = isset($usage_context['tool']) ? $usage_context['tool'] : '';

        if (function_exists('sanitize_key')) {
            $tool_key = sanitize_key($tool_key);
        } else {
            $tool_key = strtolower(preg_replace('/[^a-z0-9_\-]/', '', (string) $tool_key));
        }

        if (empty($tool_key)) {
            return;
        }

        if (!isset($tools[$tool_key])) {
            $tools[$tool_key] = array(
                'requests' => 0,
                'input_tokens' => 0,
                'output_tokens' => 0,
                'total_tokens' => 0,
                'total_cost' => 0,
                'errors' => 0,
                'last_used' => null,
            );
        }

        foreach ($increments as $key => $value) {
            if (!isset($tools[$tool_key][$key])) {
                $tools[$tool_key][$key] = 0;
            }
            if ($key === 'last_used') {
                $tools[$tool_key][$key] = $value;
            } else {
                $tools[$tool_key][$key] += $value;
            }
        }

        $tools[$tool_key]['last_used'] = current_time('mysql');
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
     * @param array $args Optional arguments. Set include_tools => true to receive tool breakdowns.
     * @return array Usage statistics
     */
    public function get_stats($args = array()) {
        $stats = $this->normalize_stats_structure(get_option('ai_core_stats', array()));

        if (!empty($args['include_tools'])) {
            return $stats;
        }

        return $stats['models'];
    }

    /**
     * Reset usage statistics
     * 
     * @return bool Success status
     */
    public function reset_stats() {
        return update_option('ai_core_stats', array(
            'models' => array(),
            'tools' => array(),
        ));
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
