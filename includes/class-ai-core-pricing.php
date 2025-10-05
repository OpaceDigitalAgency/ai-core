<?php
/**
 * AI-Core Pricing Class
 * 
 * Manages pricing data for all AI providers and models
 * Pricing data updated: October 2025
 * 
 * @package AI_Core
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AI-Core Pricing Class
 * 
 * Provides pricing information for cost calculations
 */
class AI_Core_Pricing {
    
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
            'imagen-4.0-generate-001' => array('per_image' => 0.04),
            'imagen-4.0-ultra-generate-001' => array('per_image' => 0.06),
            'imagen-4.0-fast-generate-001' => array('per_image' => 0.02),
            'imagen-3.0-generate-002' => array('per_image' => 0.03),
        );
        
        // xAI Grok Pricing (October 2025)
        $this->pricing_data['grok'] = array(
            // Grok 4 models
            'grok-4' => array('input' => 0.20, 'output' => 0.50),
            'grok-4-fast' => array('input' => 0.20, 'output' => 0.50),
            'grok-4-fast-reasoning' => array('input' => 0.20, 'output' => 0.50),
            'grok-4-fast-non-reasoning' => array('input' => 0.20, 'output' => 0.50),
            'grok-4-0709' => array('input' => 3.00, 'output' => 15.00),
            
            // Grok 3 models
            'grok-3' => array('input' => 3.00, 'output' => 15.00),
            'grok-3-mini' => array('input' => 0.30, 'output' => 0.50),
            
            // Grok 2 models
            'grok-2' => array('input' => 2.00, 'output' => 10.00),
            'grok-2-1212' => array('input' => 2.00, 'output' => 10.00),
            'grok-2-vision' => array('input' => 2.00, 'output' => 10.00),
            'grok-2-vision-1212' => array('input' => 2.00, 'output' => 10.00),
            
            // Grok Code models
            'grok-code-fast-1' => array('input' => 0.20, 'output' => 1.50),
            
            // Image generation models (per image)
            'grok-2-image-1212' => array('per_image' => 0.07),
            'grok-image-1' => array('per_image' => 0.07),
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
        
        if (!$provider || !isset($this->pricing_data[$provider])) {
            return null;
        }
        
        // Check for exact match
        if (isset($this->pricing_data[$provider][$model])) {
            return $this->pricing_data[$provider][$model];
        }
        
        // Check for partial match (for versioned models)
        foreach ($this->pricing_data[$provider] as $model_key => $pricing) {
            if (strpos($model, $model_key) === 0) {
                return $pricing;
            }
        }
        
        return null;
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
        
        if (strpos($model_lower, 'grok') === 0) {
            return 'grok';
        }
        
        // Check for image- prefix (used in tracking)
        if (strpos($model_lower, 'image-') === 0) {
            $provider_part = substr($model_lower, 6);
            if (in_array($provider_part, array('openai', 'anthropic', 'gemini', 'grok'))) {
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

