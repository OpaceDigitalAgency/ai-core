<?php
/**
 * Opace AI Hub Pricing Class
 * 
 * Manages pricing data for all AI providers and models
 * Uses a validated, cached remote catalogue with a bundled offline fallback.
 * 
 * @package AI_Core
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Opace AI Hub Pricing Class
 * 
 * Provides pricing information for cost calculations
 */
class AI_Core_Pricing {

    const CATALOGUE_URL = 'https://api.litellm.ai/model_catalog';
    const CACHE_SECONDS = 43200;
    const FAILURE_CACHE_SECONDS = 900;
    
    /**
     * Class instance
     * 
     * @var AI_Core_Pricing
     */
    private static $instance = null;
    
    /**
     * Pricing data for all models (per million tokens in USD)
     * 
     * @var array
     */
    private $pricing_data = array();
    
    /**
     * Get class instance
     * 
     * @return AI_Core_Pricing
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
        $this->init_pricing_data();
    }
    
    /**
     * Initialise pricing data
     * 
     * @return void
     */
    private function init_pricing_data() {
        // OpenAI Pricing (October 2025)
        $this->pricing_data['openai'] = array(
            // GPT-4o models
            'gpt-4o' => array('input' => 2.50, 'output' => 10.00),
            'gpt-4o-2024-11-20' => array('input' => 2.50, 'output' => 10.00),
            'gpt-4o-2024-08-06' => array('input' => 2.50, 'output' => 10.00),
            'gpt-4o-2024-05-13' => array('input' => 5.00, 'output' => 15.00),
            'gpt-4o-mini' => array('input' => 0.15, 'output' => 0.60),
            'gpt-4o-mini-2024-07-18' => array('input' => 0.15, 'output' => 0.60),
            
            // GPT-4.5 models
            'gpt-4.5' => array('input' => 3.00, 'output' => 12.00),
            'gpt-4.5-preview' => array('input' => 3.00, 'output' => 12.00),
            
            // o1 and o3 reasoning models
            'o1' => array('input' => 15.00, 'output' => 60.00),
            'o1-preview' => array('input' => 15.00, 'output' => 60.00),
            'o1-mini' => array('input' => 3.00, 'output' => 12.00),
            'o3' => array('input' => 20.00, 'output' => 80.00),
            'o3-mini' => array('input' => 1.10, 'output' => 4.40),
            
            // GPT-4 Turbo
            'gpt-4-turbo' => array('input' => 10.00, 'output' => 30.00),
            'gpt-4-turbo-2024-04-09' => array('input' => 10.00, 'output' => 30.00),
            'gpt-4-turbo-preview' => array('input' => 10.00, 'output' => 30.00),
            
            // GPT-4
            'gpt-4' => array('input' => 30.00, 'output' => 60.00),
            'gpt-4-0613' => array('input' => 30.00, 'output' => 60.00),
            'gpt-4-32k' => array('input' => 60.00, 'output' => 120.00),
            
            // GPT-3.5 Turbo
            'gpt-3.5-turbo' => array('input' => 0.50, 'output' => 1.50),
            'gpt-3.5-turbo-0125' => array('input' => 0.50, 'output' => 1.50),
            'gpt-3.5-turbo-1106' => array('input' => 1.00, 'output' => 2.00),
            
            // Image generation models (per image)
            'dall-e-3' => array('standard_1024' => 0.040, 'standard_1792' => 0.080, 'hd_1024' => 0.080, 'hd_1792' => 0.120),
            'dall-e-2' => array('1024' => 0.020, '512' => 0.018, '256' => 0.016),
            'gpt-image-1' => array('standard' => 0.040, 'hd' => 0.080),
        );
        
        // Anthropic Pricing (October 2025)
        $this->pricing_data['anthropic'] = array(
            // Claude Sonnet 4.5
            'claude-sonnet-4-5' => array('input' => 3.00, 'output' => 15.00),
            'claude-sonnet-4-5-20250514' => array('input' => 3.00, 'output' => 15.00),
            
            // Claude Sonnet 4
            'claude-sonnet-4' => array('input' => 3.00, 'output' => 15.00),
            'claude-sonnet-4-20250514' => array('input' => 3.00, 'output' => 15.00),
            
            // Claude Opus 4.1
            'claude-opus-4-1' => array('input' => 15.00, 'output' => 75.00),
            'claude-opus-4-1-20250514' => array('input' => 15.00, 'output' => 75.00),
            
            // Claude Opus 4
            'claude-opus-4' => array('input' => 15.00, 'output' => 75.00),
            'claude-opus-4-20250514' => array('input' => 15.00, 'output' => 75.00),
            
            // Claude Haiku 4
            'claude-haiku-4' => array('input' => 0.80, 'output' => 4.00),
            'claude-haiku-4-20250514' => array('input' => 0.80, 'output' => 4.00),
            
            // Claude 3.5 models
            'claude-3-5-sonnet-20241022' => array('input' => 3.00, 'output' => 15.00),
            'claude-3-5-sonnet-20240620' => array('input' => 3.00, 'output' => 15.00),
            'claude-3-5-haiku-20241022' => array('input' => 0.80, 'output' => 4.00),
            
            // Claude 3 models
            'claude-3-opus-20240229' => array('input' => 15.00, 'output' => 75.00),
            'claude-3-sonnet-20240229' => array('input' => 3.00, 'output' => 15.00),
            'claude-3-haiku-20240307' => array('input' => 0.25, 'output' => 1.25),
        );
        
        // Google Gemini Pricing (October 2025)
        $this->pricing_data['gemini'] = array(
            // Gemini 2.5 Pro
            'gemini-2.5-pro' => array('input' => 1.25, 'input_long' => 2.50, 'output' => 10.00, 'output_long' => 15.00, 'threshold' => 200000),
            'gemini-2.5-pro-preview' => array('input' => 1.25, 'input_long' => 2.50, 'output' => 10.00, 'output_long' => 15.00, 'threshold' => 200000),
            
            // Gemini 2.5 Flash
            'gemini-2.5-flash' => array('input' => 0.30, 'output' => 2.50),
            'gemini-2.5-flash-preview' => array('input' => 0.30, 'output' => 2.50),
            'gemini-2.5-flash-preview-09-2025' => array('input' => 0.30, 'output' => 2.50),
            
            // Gemini 2.5 Flash-Lite
            'gemini-2.5-flash-lite' => array('input' => 0.10, 'output' => 0.40),
            'gemini-2.5-flash-lite-preview' => array('input' => 0.10, 'output' => 0.40),
            'gemini-2.5-flash-lite-preview-09-2025' => array('input' => 0.10, 'output' => 0.40),
            
            // Gemini 2.0 Flash
            'gemini-2.0-flash' => array('input' => 0.10, 'output' => 0.40),
            'gemini-2.0-flash-exp' => array('input' => 0.10, 'output' => 0.40),
            'gemini-2.0-flash-lite' => array('input' => 0.075, 'output' => 0.30),
            
            // Gemini 1.5 models
            'gemini-1.5-pro' => array('input' => 1.25, 'input_long' => 2.50, 'output' => 5.00, 'output_long' => 10.00, 'threshold' => 128000),
            'gemini-1.5-pro-002' => array('input' => 1.25, 'input_long' => 2.50, 'output' => 5.00, 'output_long' => 10.00, 'threshold' => 128000),
            'gemini-1.5-flash' => array('input' => 0.075, 'output' => 0.30),
            'gemini-1.5-flash-002' => array('input' => 0.075, 'output' => 0.30),
            'gemini-1.5-flash-8b' => array('input' => 0.0375, 'output' => 0.15),
            
            // Image generation models (per image)
            'gemini-2.5-flash-image' => array('per_image' => 0.039),
            'gemini-2.5-flash-image-preview' => array('per_image' => 0.039),
            'gemini-3.1-flash-image' => array('input' => 0.25, 'output' => 1.50, 'per_image' => 0.045),
            'gemini-3.6-flash' => array('input' => 1.50, 'output' => 7.50),
            'imagen-4.0-generate-001' => array('per_image' => 0.04),
            'imagen-4.0-ultra-generate-001' => array('per_image' => 0.06),
            'imagen-4.0-fast-generate-001' => array('per_image' => 0.02),
            'imagen-3.0-generate-002' => array('per_image' => 0.03),
        );
        
    }
    
    /**
     * Get pricing for a specific model
     * 
     * @param string $model Model identifier
     * @param string $provider Provider name (optional, will be detected from model name)
     * @return array|null Pricing data or null if not found
     */
    public function get_model_pricing($model, $provider = null) {
        // Detect provider from model name if not provided
        if (null === $provider) {
            $provider = $this->detect_provider($model);
        }
        
        if (!$provider) {
            return null;
        }

        $remote = $this->get_remote_pricing($model, $provider);
        if (is_array($remote)) {
            return $remote;
        }

        if (!isset($this->pricing_data[$provider])) {
            return null;
        }
        
        // Check for exact match
        if (isset($this->pricing_data[$provider][$model])) {
            return $this->with_provenance($this->pricing_data[$provider][$model], 'bundled', null);
        }
        
        // Never guess from a shared prefix: a newly released model can have a
        // different rate despite resembling an older family member.
        return null;
    }

    /**
     * Refresh one model from the public LiteLLM catalogue.
     *
     * Only provider and model identifiers are sent. API keys, prompts and
     * generated content never leave the site for this lookup.
     *
     * @param string $model Model identifier.
     * @param string $provider Provider identifier.
     * @param bool $force Ignore a cached result.
     * @return array|null Normalised pricing or null when unavailable.
     */
    public function get_remote_pricing($model, $provider, $force = false) {
        $model = trim((string) $model);
        $provider = sanitize_key($provider);
        if ('' === $model || '' === $provider) {
            return null;
        }

        $cache_key = $this->get_cache_key($provider, $model);
        if (!$force) {
            $cached = get_transient($cache_key);
            if (is_array($cached) && !empty($cached['pricing'])) {
                return $cached['pricing'];
            }
            if ('unavailable' === $cached) {
                return null;
            }
        }

        $url = add_query_arg(array(
            'provider' => $provider,
            'model' => $model,
            'page_size' => 20,
        ), self::CATALOGUE_URL);

        $response = wp_remote_get($url, array(
            'timeout' => 6,
            'redirection' => 2,
            'sslverify' => true,
            'user-agent' => 'Opace AI Hub/' . (defined('AI_CORE_VERSION') ? AI_CORE_VERSION : 'unknown'),
        ));

        if (is_wp_error($response) || 200 !== (int) wp_remote_retrieve_response_code($response)) {
            set_transient($cache_key, 'unavailable', self::FAILURE_CACHE_SECONDS);
            return null;
        }

        $body = json_decode(wp_remote_retrieve_body($response), true);
        $rows = isset($body['data']) && is_array($body['data']) ? $body['data'] : array();
        $expected_ids = array($model, $provider . '/' . $model);

        foreach ($rows as $row) {
            if (!is_array($row) || ($row['provider'] ?? '') !== $provider || !in_array(($row['id'] ?? ''), $expected_ids, true)) {
                continue;
            }

            $pricing = $this->normalise_catalogue_row($row);
            if (null === $pricing) {
                break;
            }

            set_transient($cache_key, array('pricing' => $pricing), self::CACHE_SECONDS);
            return $pricing;
        }

        set_transient($cache_key, 'unavailable', self::FAILURE_CACHE_SECONDS);
        return null;
    }

    /** Force-refresh pricing for models already present in usage statistics. */
    public function refresh_models($models) {
        $results = array();
        foreach ((array) $models as $model => $provider) {
            $results[$model] = $this->get_remote_pricing($model, $provider ?: $this->detect_provider($model), true);
        }
        return $results;
    }

    /** Return pricing provenance suitable for an admin explanation. */
    public function get_pricing_provenance($model, $provider = null) {
        $pricing = $this->get_model_pricing($model, $provider);
        if (!$pricing) {
            return array('status' => 'unavailable', 'source' => null, 'refreshed_at' => null, 'source_url' => null);
        }
        return array(
            'status' => 'estimated',
            'source' => $pricing['_source'] ?? 'bundled',
            'refreshed_at' => $pricing['_refreshed_at'] ?? null,
            'source_url' => $pricing['_source_url'] ?? null,
        );
    }

    private function get_cache_key($provider, $model) {
        return 'ai_core_pricing_' . md5($provider . '|' . $model);
    }

    private function normalise_catalogue_row($row) {
        $pricing = array();
        $input = $this->valid_non_negative_number($row['input_cost_per_token'] ?? null);
        $output = $this->valid_non_negative_number($row['output_cost_per_token'] ?? null);
        $image = $this->valid_non_negative_number($row['output_cost_per_image'] ?? null);

        if (null !== $input && null !== $output) {
            $pricing['input'] = $input * 1000000;
            $pricing['output'] = $output * 1000000;
        }
        if (null !== $image) {
            $pricing['per_image'] = $image;
        }
        if (empty($pricing)) {
            return null;
        }

        return $this->with_provenance(
            $pricing,
            'litellm',
            isset($row['source']) && is_string($row['source']) ? esc_url_raw($row['source']) : null
        );
    }

    private function valid_non_negative_number($value) {
        if (!is_int($value) && !is_float($value) && !is_numeric($value)) {
            return null;
        }
        $number = (float) $value;
        return is_finite($number) && $number >= 0 ? $number : null;
    }

    private function with_provenance($pricing, $source, $source_url) {
        $pricing['_source'] = $source;
        $pricing['_refreshed_at'] = 'litellm' === $source ? current_time('mysql', true) : null;
        $pricing['_source_url'] = $source_url;
        return $pricing;
    }
    
    /**
     * Calculate cost for a request
     * 
     * @param string $model Model identifier
     * @param int $input_tokens Input token count
     * @param int $output_tokens Output token count
     * @param string $provider Provider name (optional)
     * @return float|null Cost in USD or null if pricing not available
     */
    public function calculate_cost($model, $input_tokens, $output_tokens, $provider = null) {
        $pricing = $this->get_model_pricing($model, $provider);
        
        if (!$pricing) {
            return null;
        }
        
        $cost = 0;
        
        // Handle image generation models (per image pricing)
        if (isset($pricing['per_image'])) {
            // For image models, output_tokens represents number of images
            return $pricing['per_image'] * max(1, $output_tokens);
        }
        
        // Handle text models with input/output pricing
        if (isset($pricing['input']) && isset($pricing['output'])) {
            // Check for long context pricing
            if (isset($pricing['threshold']) && $input_tokens > $pricing['threshold']) {
                $input_cost = isset($pricing['input_long']) ? $pricing['input_long'] : $pricing['input'];
                $output_cost = isset($pricing['output_long']) ? $pricing['output_long'] : $pricing['output'];
            } else {
                $input_cost = $pricing['input'];
                $output_cost = $pricing['output'];
            }
            
            // Calculate cost (pricing is per million tokens)
            $cost = ($input_tokens / 1000000 * $input_cost) + ($output_tokens / 1000000 * $output_cost);
        }
        
        return $cost;
    }
    
    /**
     * Detect provider from model name
     * 
     * @param string $model Model identifier
     * @return string|null Provider name or null if not detected
     */
    private function detect_provider($model) {
        $model_lower = strtolower($model);
        
        if (strpos($model_lower, 'gpt') === 0 || strpos($model_lower, 'o1') === 0 || 
            strpos($model_lower, 'o3') === 0 || strpos($model_lower, 'dall-e') === 0) {
            return 'openai';
        }
        
        if (strpos($model_lower, 'claude') === 0) {
            return 'anthropic';
        }
        
        if (strpos($model_lower, 'gemini') === 0 || strpos($model_lower, 'imagen') === 0) {
            return 'gemini';
        }
        
        // Check for image- prefix (used in tracking)
        if (strpos($model_lower, 'image-') === 0) {
            $provider_part = substr($model_lower, 6);
            if (in_array($provider_part, array('openai', 'anthropic', 'gemini'))) {
                return $provider_part;
            }
        }
        
        return null;
    }
    
    /**
     * Get all pricing data
     * 
     * @return array All pricing data
     */
    public function get_all_pricing() {
        return $this->pricing_data;
    }
}
