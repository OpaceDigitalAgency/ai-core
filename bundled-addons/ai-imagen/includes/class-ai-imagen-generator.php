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

        // Add the built prompt to the response so it can be displayed in the UI
        // This shows the exact formatted prompt that was sent to the AI API
        $response['prompt'] = $prompt;

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
     * New format:
     * Image type: [workflow selected]
     * Image needed: [prompt selected]
     * Rules: The canvas aspect ratio and resolution is [aspect ratio]...
     * Overlays: [scene elements]
     *
     * @param array $params Generation parameters
     * @return string Final prompt
     */
    private function build_prompt($params) {
        $sections = array();

        // 1. Image type: Determine the workflow context (use_case, role, or style)
        $image_type = $this->get_image_type($params);
        if ($image_type) {
            $sections[] = 'Image type: ' . $image_type . '.';
        }

        // 2. Image needed: Main prompt + additional details
        $image_needed = $params['prompt'];
        if (!empty($params['additional_details'])) {
            $image_needed .= '. ' . $params['additional_details'];
        }
        $sections[] = 'Image needed: ' . $image_needed . '.';

        // 3. Rules: Aspect ratio and general instructions
        $aspect_ratio = !empty($params['aspect_ratio']) ? $params['aspect_ratio'] : '1:1';
        $rules = 'The canvas aspect ratio and resolution is ' . $aspect_ratio . '. Do not render or display these instructions, the ratio explicitly, glyph codes, etc. on the image. Ensure overlays adapt to the aspect ratio. Always preserve balance and safe margins around the edges.';
        $sections[] = 'Rules: ' . $rules;

        // 4. Overlays: Scene builder elements
        if (!empty($params['scene_elements'])) {
            $overlays_text = $this->build_overlays_text($params['scene_elements']);
            if ($overlays_text) {
                $sections[] = 'Overlays: ' . $overlays_text;
            }
        }

        return implode(' ', $sections);
    }

    /**
     * Get image type from workflow context
     *
     * @param array $params Generation parameters
     * @return string Image type description
     */
    private function get_image_type($params) {
        // Priority: style > use_case > role
        if (!empty($params['style'])) {
            return $this->get_style_label($params['style']);
        }

        if (!empty($params['use_case'])) {
            return $this->get_use_case_label($params['use_case']);
        }

        if (!empty($params['role'])) {
            return $this->get_role_label($params['role']);
        }

        return '';
    }

    /**
     * Get human-readable style label
     *
     * @param string $style Style identifier
     * @return string Style label
     */
    private function get_style_label($style) {
        $labels = array(
            'photorealistic' => 'Photorealistic',
            'flat-minimalist' => 'Flat & Minimalist',
            'cartoon-anime' => 'Cartoon & Anime',
            'digital-painting' => 'Digital Painting',
            'retro-vintage' => 'Retro & Vintage',
            '3d-cgi' => '3D & CGI',
            'hand-drawn' => 'Hand-drawn',
            'brand-layouts' => 'Brand Layouts',
            'transparent-assets' => 'Transparent Assets',
        );

        return isset($labels[$style]) ? $labels[$style] : ucwords(str_replace('-', ' ', $style));
    }

    /**
     * Get human-readable use case label
     *
     * @param string $use_case Use case identifier
     * @return string Use case label
     */
    private function get_use_case_label($use_case) {
        $labels = array(
            'marketing-ads' => 'Marketing & Ads',
            'social-media' => 'Social Media',
            'product-photography' => 'Product Photography',
            'website-design' => 'Website Design',
            'publishing' => 'Publishing',
            'presentations' => 'Presentations',
            'game-development' => 'Game Development',
            'education' => 'Education',
            'print-on-demand' => 'Print-on-Demand',
        );

        return isset($labels[$use_case]) ? $labels[$use_case] : ucwords(str_replace('-', ' ', $use_case));
    }

    /**
     * Get human-readable role label
     *
     * @param string $role Role identifier
     * @return string Role label
     */
    private function get_role_label($role) {
        $labels = array(
            'marketing-manager' => 'Marketing Manager',
            'social-media-manager' => 'Social Media Manager',
            'small-business-owner' => 'Small Business Owner',
            'graphic-designer' => 'Graphic Designer',
            'content-publisher' => 'Content Publisher',
            'developer' => 'Developer',
            'educator' => 'Educator',
            'event-planner' => 'Event Planner',
        );

        return isset($labels[$role]) ? $labels[$role] : ucwords(str_replace('-', ' ', $role));
    }

    /**
     * Build overlays text from scene elements
     *
     * @param array $scene_elements Scene builder elements
     * @return string Overlays description
     */
    private function build_overlays_text($scene_elements) {
        if (empty($scene_elements) || !is_array($scene_elements)) {
            return '';
        }

        $overlays = array();

        foreach ($scene_elements as $element) {
            $type = isset($element['type']) ? $element['type'] : '';

            if ($type === 'text') {
                // Support both 'text' and 'content' keys (scene builder uses 'content')
                $text = isset($element['content']) ? $element['content'] : (isset($element['text']) ? $element['text'] : 'Your Text Here');

                // Support both 'x'/'y' and 'left'/'top' keys (scene builder now sends percentages)
                $left = isset($element['x']) ? intval($element['x']) : (isset($element['left']) ? intval($element['left']) : 0);
                $top = isset($element['y']) ? intval($element['y']) : (isset($element['top']) ? intval($element['top']) : 0);
                $width = isset($element['width']) ? intval($element['width']) : 40;
                $height = isset($element['height']) ? intval($element['height']) : 60;
                $color = isset($element['color']) ? $element['color'] : '#000000';
                $fontSize = isset($element['fontSize']) ? intval($element['fontSize']) : 17;
                $fontWeight = isset($element['fontWeight']) ? $element['fontWeight'] : 'normal';

                $overlays[] = sprintf(
                    'Add a text overlay with the text "%s" positioned %d%% from the left and %d%% from the top, taking up approximately %d%% of the canvas width and %d%% of the canvas height, in %s colour, %dpx font size, %s weight.',
                    $text, $left, $top, $width, $height, $color, $fontSize, $fontWeight
                );
            } elseif ($type === 'icon') {
                $iconName = isset($element['iconName']) ? $element['iconName'] : (isset($element['icon']) ? $element['icon'] : 'music');

                // Support both 'x'/'y' and 'left'/'top' keys (scene builder now sends percentages)
                $left = isset($element['x']) ? intval($element['x']) : (isset($element['left']) ? intval($element['left']) : 0);
                $top = isset($element['y']) ? intval($element['y']) : (isset($element['top']) ? intval($element['top']) : 0);

                // Support both 'width' and 'size' keys for icon size (now in percentages)
                $size = isset($element['width']) ? intval($element['width']) : (isset($element['size']) ? intval($element['size']) : 20);

                // Get icon colour
                $color = isset($element['color']) ? $element['color'] : '#000000';

                // Map icon names to Dashicons Unicode references for precise rendering
                // Format: 'icon-name' => ['unicode' => '\fXXX', 'description' => 'visual description']
                $iconDescriptions = array(
                    // People & User
                    'user' => array('unicode' => '\f110', 'desc' => 'user profile silhouette'),

                    // Shapes & Symbols
                    'heart' => array('unicode' => '\f487', 'desc' => 'heart shape'),
                    'star' => array('unicode' => '\f155', 'desc' => 'five-pointed star'),
                    'checkmark' => array('unicode' => '\f147', 'desc' => 'checkmark/tick'),
                    'check' => array('unicode' => '\f147', 'desc' => 'checkmark/tick'),
                    'cross' => array('unicode' => '\f153', 'desc' => 'X/cross'),
                    'close' => array('unicode' => '\f153', 'desc' => 'X/close'),
                    'plus' => array('unicode' => '\f132', 'desc' => 'plus sign'),
                    'minus' => array('unicode' => '\f460', 'desc' => 'minus sign'),

                    // Arrows
                    'arrow-up' => array('unicode' => '\f142', 'desc' => 'upward arrow'),
                    'arrow-down' => array('unicode' => '\f140', 'desc' => 'downward arrow'),
                    'arrow-left' => array('unicode' => '\f141', 'desc' => 'leftward arrow'),
                    'arrow-right' => array('unicode' => '\f139', 'desc' => 'rightward arrow'),

                    // Places & Navigation
                    'home' => array('unicode' => '\f102', 'desc' => 'house/home'),
                    'location' => array('unicode' => '\f230', 'desc' => 'map pin/location marker'),
                    'location-pin' => array('unicode' => '\f230', 'desc' => 'map pin/location marker'),
                    'search' => array('unicode' => '\f179', 'desc' => 'magnifying glass'),
                    'menu' => array('unicode' => '\f333', 'desc' => 'hamburger menu (three horizontal lines)'),

                    // Communication
                    'phone' => array('unicode' => '\f525', 'desc' => 'telephone handset'),
                    'mail' => array('unicode' => '\f465', 'desc' => 'envelope'),
                    'email' => array('unicode' => '\f465', 'desc' => 'envelope'),
                    'share' => array('unicode' => '\f237', 'desc' => 'share icon (three connected dots forming a network)'),

                    // Media & Files
                    'camera' => array('unicode' => '\f306', 'desc' => 'camera'),
                    'video' => array('unicode' => '\f219', 'desc' => 'video camera'),
                    'music' => array('unicode' => '\f488', 'desc' => 'musical note'),
                    'download' => array('unicode' => '\f316', 'desc' => 'download (downward arrow)'),
                    'upload' => array('unicode' => '\f317', 'desc' => 'upload (upward arrow)'),

                    // Time & Calendar
                    'calendar' => array('unicode' => '\f145', 'desc' => 'calendar'),
                    'clock' => array('unicode' => '\f469', 'desc' => 'clock face'),

                    // Settings & Tools
                    'settings' => array('unicode' => '\f108', 'desc' => 'gear/cog'),
                    'lock' => array('unicode' => '\f160', 'desc' => 'padlock (closed)'),
                    'unlock' => array('unicode' => '\f528', 'desc' => 'padlock (open)'),
                    'lightbulb' => array('unicode' => '\f504', 'desc' => 'lightbulb'),

                    // Status & Alerts
                    'warning' => array('unicode' => '\f534', 'desc' => 'warning triangle with exclamation mark'),
                    'info' => array('unicode' => '\f348', 'desc' => 'information circle with i'),

                    // Commerce
                    'cart' => array('unicode' => '\f174', 'desc' => 'shopping cart'),
                );

                // Build icon description with Dashicons Unicode reference
                if (isset($iconDescriptions[$iconName])) {
                    $unicode = $iconDescriptions[$iconName]['unicode'];
                    $desc = $iconDescriptions[$iconName]['desc'];
                    $iconDescription = sprintf(
                        'an icon from Dashicons font-family (glyph %s) and display this as a %s image',
                        $unicode,
                        $desc
                    );
                } else {
                    // Fallback for unknown icons
                    $iconDescription = 'a ' . str_replace('-', ' ', $iconName) . ' icon';
                }

                $overlays[] = sprintf(
                    'Add %s in %s colour, positioned %d%% from the left and %d%% from the top, sized at approximately %d%% of the canvas width.',
                    $iconDescription, $color, $left, $top, $size
                );
            } elseif ($type === 'image' || $type === 'logo') {
                $left = isset($element['left']) ? intval($element['left']) : 0;
                $top = isset($element['top']) ? intval($element['top']) : 0;
                $size = isset($element['size']) ? intval($element['size']) : 20;
                $label = $type === 'logo' ? 'logo' : 'image';

                $overlays[] = sprintf(
                    'Add a %s overlay positioned %d%% from the left and %d%% from the top, sized at approximately %d%% of the canvas width.',
                    $label, $left, $top, $size
                );
            }
        }

        if (empty($overlays)) {
            return '';
        }

        return implode(' ', $overlays) . ' Follow these coordinates exactly relative to the canvas size, not the image content.';
    }

    /**
     * Build scene text from elements (DEPRECATED - use build_overlays_text instead)
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

