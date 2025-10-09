<?php
/**
 * AI-Imagen Generator Class
 * 
 * Handles image generation logic and AI provider integration
 * 
 * @package AI_Imagen
 * @version 0.5.5
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AI-Imagen Generator Class
 */
class AI_Imagen_Generator {
    
    /**
     * Class instance
     * 
     * @var AI_Imagen_Generator
     */
    private static $instance = null;
    
    /**
     * AI-Core API instance
     * 
     * @var AI_Core_API
     */
    private $ai_core = null;
    
    /**
     * Get class instance
     * 
     * @return AI_Imagen_Generator
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
        if (function_exists('ai_core')) {
            $this->ai_core = ai_core();
        }
    }
    
    /**
     * Get available image generation providers
     * 
     * @return array List of providers with image generation capability
     */
    public function get_available_providers() {
        if (!$this->ai_core || !$this->ai_core->is_configured()) {
            return array();
        }
        
        $configured_providers = $this->ai_core->get_configured_providers();
        $image_providers = array();
        
        // Check which providers support image generation
        foreach ($configured_providers as $provider) {
            if ($this->provider_supports_images($provider)) {
                $image_providers[] = $provider;
            }
        }
        
        return $image_providers;
    }
    
    /**
     * Check if provider supports image generation
     * 
     * @param string $provider Provider name
     * @return bool True if provider supports images
     */
    private function provider_supports_images($provider) {
        $image_providers = array('openai', 'gemini', 'grok');
        return in_array($provider, $image_providers, true);
    }
    
    /**
     * Get available models for provider
     *
     * @param string $provider Provider name
     * @return array List of image generation models
     */
    public function get_provider_models($provider) {
        if (!$this->ai_core) {
            return array();
        }

        // Define image models for each provider
        // These are the only models that can generate images
        $image_models = array();

        if ($provider === 'openai') {
            // OpenAI image generation models
            $image_models = array(
                'gpt-image-1',
                'dall-e-3',
                'dall-e-2',
            );
        } elseif ($provider === 'gemini') {
            // Gemini image generation models (only models with '-image' suffix)
            // Note: Only gemini-2.5-flash-image models are currently working
            // The imagen-3.0-* models may require different API endpoints or may not be available
            $image_models = array(
                'gemini-2.5-flash-image',
                'gemini-2.5-flash-image-preview',
            );
        } elseif ($provider === 'grok') {
            // Grok image generation models
            $image_models = array(
                'grok-2-image-1212',
            );
        }

        return $image_models;
    }

    /**
     * Generate image
     * 
     * @param array $params Generation parameters
     * @return array|WP_Error Generation result or error
     */
    public function generate_image($params) {
        if (!$this->ai_core || !$this->ai_core->is_configured()) {
            return new WP_Error(
                'not_configured',
                __('AI-Core is not configured. Please configure API keys first.', 'ai-imagen')
            );
        }
        
        // Validate parameters
        $validated = $this->validate_params($params);
        if (is_wp_error($validated)) {
            return $validated;
        }
        
        // Build prompt
        $prompt = $this->build_prompt($params);
        
        // Prepare options
        $options = $this->prepare_options($params);
        
        // Generate image with tool tracking
        $response = $this->ai_core->generate_image(
            $prompt,
            $options,
            $params['provider'],
            array('tool' => 'ai_imagen') // Track usage under AI-Imagen tool
        );

        if (is_wp_error($response)) {
            return $response;
        }

        // Track statistics
        $this->track_generation($params, $response);

        return $response;
    }
    
    /**
     * Validate generation parameters
     * 
     * @param array $params Parameters to validate
     * @return true|WP_Error True if valid, WP_Error otherwise
     */
    private function validate_params($params) {
        // Check required fields
        if (empty($params['prompt'])) {
            return new WP_Error(
                'missing_prompt',
                __('Prompt is required.', 'ai-imagen')
            );
        }
        
        if (empty($params['provider'])) {
            return new WP_Error(
                'missing_provider',
                __('Provider is required.', 'ai-imagen')
            );
        }
        
        // Check provider is available
        $available_providers = $this->get_available_providers();
        if (!in_array($params['provider'], $available_providers, true)) {
            return new WP_Error(
                'invalid_provider',
                __('Selected provider is not available or does not support image generation.', 'ai-imagen')
            );
        }
        
        return true;
    }
    
    /**
     * Build final prompt from parameters
     * 
     * @param array $params Generation parameters
     * @return string Final prompt
     */
    private function build_prompt($params) {
        $prompt_parts = array();
        
        // Main prompt
        $prompt_parts[] = $params['prompt'];
        
        // Additional details
        if (!empty($params['additional_details'])) {
            $prompt_parts[] = $params['additional_details'];
        }
        
        // Style preset
        if (!empty($params['style'])) {
            $style_text = $this->get_style_text($params['style']);
            if ($style_text) {
                $prompt_parts[] = $style_text;
            }
        }
        
        // Use case context
        if (!empty($params['use_case'])) {
            $use_case_text = $this->get_use_case_text($params['use_case']);
            if ($use_case_text) {
                $prompt_parts[] = $use_case_text;
            }
        }
        
        // Role optimization
        if (!empty($params['role'])) {
            $role_text = $this->get_role_text($params['role']);
            if ($role_text) {
                $prompt_parts[] = $role_text;
            }
        }
        
        // Scene builder elements
        if (!empty($params['scene_elements'])) {
            $scene_text = $this->build_scene_text($params['scene_elements']);
            if ($scene_text) {
                $prompt_parts[] = $scene_text;
            }
        }
        
        return implode('. ', $prompt_parts);
    }
    
    /**
     * Get style text for prompt
     * 
     * @param string $style Style identifier
     * @return string Style description
     */
    private function get_style_text($style) {
        $styles = array(
            'photorealistic' => 'photorealistic, high quality DSLR photography',
            'flat-minimalist' => 'flat design, minimalist style, clean lines',
            'cartoon-anime' => 'cartoon style, anime aesthetic',
            'digital-painting' => 'digital painting, artistic illustration',
            'retro-vintage' => 'retro style, vintage aesthetic',
            '3d-cgi' => '3D rendered, CGI, high quality render',
            'hand-drawn' => 'hand-drawn, traditional art style',
            'brand-layouts' => 'professional layout, brand-focused design',
            'transparent' => 'transparent background, clean cutout',
        );
        
        return isset($styles[$style]) ? $styles[$style] : '';
    }
    
    /**
     * Get use case text for prompt
     * 
     * @param string $use_case Use case identifier
     * @return string Use case description
     */
    private function get_use_case_text($use_case) {
        $use_cases = array(
            'marketing-ads' => 'professional marketing material, advertising quality',
            'social-media' => 'social media optimized, engaging visual',
            'product-photography' => 'product photography, commercial quality',
            'website-design' => 'web design element, modern interface',
            'publishing' => 'editorial quality, publication-ready',
            'presentations' => 'presentation slide, professional business visual',
            'game-development' => 'game art, concept design',
            'education' => 'educational diagram, clear and informative',
            'print-on-demand' => 'print-ready design, high resolution',
        );
        
        return isset($use_cases[$use_case]) ? $use_cases[$use_case] : '';
    }
    
    /**
     * Get role text for prompt
     * 
     * @param string $role Role identifier
     * @return string Role optimization
     */
    private function get_role_text($role) {
        // Role-specific optimizations can be added here
        return '';
    }
    
    /**
     * Build scene text from elements
     * 
     * @param array $elements Scene elements
     * @return string Scene description
     */
    private function build_scene_text($elements) {
        $scene_parts = array();
        
        foreach ($elements as $element) {
            if ($element['type'] === 'text' && !empty($element['content'])) {
                $scene_parts[] = 'with text: "' . $element['content'] . '"';
            }
        }
        
        return implode(', ', $scene_parts);
    }
    
    /**
     * Prepare options for AI provider
     *
     * @param array $params Generation parameters
     * @return array Provider options
     */
    private function prepare_options($params) {
        $options = array();

        // Model
        if (!empty($params['model'])) {
            $options['model'] = $params['model'];
        }

        // Size/aspect ratio - pass provider and model for correct size mapping
        if (!empty($params['aspect_ratio'])) {
            $provider = !empty($params['provider']) ? $params['provider'] : 'openai';
            $model = !empty($params['model']) ? $params['model'] : '';
            $options['size'] = $this->get_size_from_aspect_ratio($params['aspect_ratio'], $provider, $model);
        }

        // Quality
        if (!empty($params['quality'])) {
            $options['quality'] = $params['quality'];
        }

        // Number of images
        $options['n'] = isset($params['n']) ? intval($params['n']) : 1;

        return $options;
    }
    
    /**
     * Convert aspect ratio to size based on provider and model
     *
     * @param string $aspect_ratio Aspect ratio (e.g., '1:1', '16:9')
     * @param string $provider Provider name (default: 'openai')
     * @param string $model Model name (default: '')
     * @return string Size string (e.g., '1024x1024')
     */
    private function get_size_from_aspect_ratio($aspect_ratio, $provider = 'openai', $model = '') {
        // Provider-specific size mappings
        if ($provider === 'openai') {
            if ($model === 'gpt-image-1') {
                // GPT-Image-1 supported sizes
                $sizes = array(
                    '1:1' => '1024x1024',
                    '4:3' => '1536x1024',  // Closest to 4:3 (actually 3:2)
                    '16:9' => '1536x1024', // Closest landscape option
                    '9:16' => '1024x1536', // Portrait
                );
            } else {
                // DALL-E 3 supported sizes
                $sizes = array(
                    '1:1' => '1024x1024',
                    '4:3' => '1792x1024',  // Closest to 4:3 (actually 16:9)
                    '16:9' => '1792x1024', // Landscape
                    '9:16' => '1024x1792', // Portrait
                );
            }
        } else {
            // Default mapping for other providers (Gemini, Grok)
            $sizes = array(
                '1:1' => '1024x1024',
                '4:3' => '1024x768',
                '16:9' => '1792x1024',
                '9:16' => '1024x1792',
            );
        }

        return isset($sizes[$aspect_ratio]) ? $sizes[$aspect_ratio] : '1024x1024';
    }
    
    /**
     * Track generation statistics
     * 
     * @param array $params Generation parameters
     * @param array $response API response
     * @return void
     */
    private function track_generation($params, $response) {
        AI_Imagen_Stats::track_generation($params, $response);
    }
}

