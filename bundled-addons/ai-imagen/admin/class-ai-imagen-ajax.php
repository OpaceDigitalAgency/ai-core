<?php
/**
 * AI-Imagen AJAX Class
 * 
 * Handles AJAX requests for image generation and management
 * 
 * @package AI_Imagen
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AI-Imagen AJAX Class
 */
class AI_Imagen_AJAX {
    
    /**
     * Class instance
     * 
     * @var AI_Imagen_AJAX
     */
    private static $instance = null;
    
    /**
     * Get class instance
     * 
     * @return AI_Imagen_AJAX
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
        // Image generation
        add_action('wp_ajax_ai_imagen_generate', array($this, 'ajax_generate_image'));
        
        // Provider and model management
        add_action('wp_ajax_ai_imagen_get_providers', array($this, 'ajax_get_providers'));
        add_action('wp_ajax_ai_imagen_get_models', array($this, 'ajax_get_models'));
        
        // Media library
        add_action('wp_ajax_ai_imagen_save_to_library', array($this, 'ajax_save_to_library'));
        add_action('wp_ajax_ai_imagen_delete_image', array($this, 'ajax_delete_image'));
        
        // Prompt enhancement
        add_action('wp_ajax_ai_imagen_enhance_prompt', array($this, 'ajax_enhance_prompt'));
        
        // Quick start ideas
        add_action('wp_ajax_ai_imagen_get_ideas', array($this, 'ajax_get_ideas'));
    }
    
    /**
     * AJAX handler for image generation
     * 
     * @return void
     */
    public function ajax_generate_image() {
        // Verify nonce
        check_ajax_referer('ai_imagen_admin', 'nonce');
        
        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('You do not have permission to generate images.', 'ai-imagen'),
            ));
        }
        
        // Check generation limit
        $media = AI_Imagen_Media::get_instance();
        if ($media->is_limit_reached()) {
            wp_send_json_error(array(
                'message' => __('Daily generation limit reached. Please try again tomorrow.', 'ai-imagen'),
            ));
        }
        
        // Get parameters
        $params = array(
            'prompt' => isset($_POST['prompt']) ? sanitize_textarea_field($_POST['prompt']) : '',
            'provider' => isset($_POST['provider']) ? sanitize_text_field($_POST['provider']) : '',
            'model' => isset($_POST['model']) ? sanitize_text_field($_POST['model']) : '',
            'use_case' => isset($_POST['use_case']) ? sanitize_text_field($_POST['use_case']) : '',
            'role' => isset($_POST['role']) ? sanitize_text_field($_POST['role']) : '',
            'style' => isset($_POST['style']) ? sanitize_text_field($_POST['style']) : '',
            'quality' => isset($_POST['quality']) ? sanitize_text_field($_POST['quality']) : 'standard',
            'format' => isset($_POST['format']) ? sanitize_text_field($_POST['format']) : 'png',
            'aspect_ratio' => isset($_POST['aspect_ratio']) ? sanitize_text_field($_POST['aspect_ratio']) : '1:1',
            'background' => isset($_POST['background']) ? sanitize_text_field($_POST['background']) : 'opaque',
            'scene_elements' => isset($_POST['scene_elements']) ? json_decode(stripslashes($_POST['scene_elements']), true) : array(),
        );
        
        // Generate image
        $generator = AI_Imagen_Generator::get_instance();
        $response = $generator->generate_image($params);

        if (is_wp_error($response)) {
            wp_send_json_error(array(
                'message' => $response->get_error_message(),
            ));
        }

        // Auto-save to library if enabled
        $settings = AI_Imagen_Settings::get_instance();
        $attachment_id = null;

        // Get image URL or Base64 data
        $image_url = '';
        $image_data = '';

        if (isset($response['data'][0]['url'])) {
            $image_url = $response['data'][0]['url'];
        } elseif (isset($response['data'][0]['b64_json'])) {
            // Convert Base64 to data URL for display
            $image_data = $response['data'][0]['b64_json'];
            $image_url = 'data:image/png;base64,' . $image_data;
        }

        if ($settings->get('auto_save_to_library', true) && $image_url) {
            $metadata = array(
                'prompt' => $params['prompt'],
                'provider' => $params['provider'],
                'model' => $params['model'],
                'use_case' => $params['use_case'],
                'role' => $params['role'],
                'style' => $params['style'],
                'format' => $params['format'],
            );

            $attachment_id = $media->save_to_library($image_url, $metadata);
        }

        // Get the actual built prompt that was sent to the API
        // This includes the formatted structure with Image type, Image needed, Rules, and Overlays
        $built_prompt = isset($response['prompt']) ? $response['prompt'] : '';

        wp_send_json_success(array(
            'image_url' => $image_url,
            'attachment_id' => $attachment_id,
            'message' => __('Image generated successfully!', 'ai-imagen'),
            'built_prompt' => $built_prompt, // Return the actual formatted prompt sent to API
        ));
    }
    
    /**
     * AJAX handler for getting available providers
     * 
     * @return void
     */
    public function ajax_get_providers() {
        // Verify nonce
        check_ajax_referer('ai_imagen_admin', 'nonce');
        
        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('You do not have permission to access this.', 'ai-imagen'),
            ));
        }
        
        $generator = AI_Imagen_Generator::get_instance();
        $providers = $generator->get_available_providers();
        
        wp_send_json_success(array(
            'providers' => $providers,
        ));
    }
    
    /**
     * AJAX handler for getting provider models
     * 
     * @return void
     */
    public function ajax_get_models() {
        // Verify nonce
        check_ajax_referer('ai_imagen_admin', 'nonce');
        
        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('You do not have permission to access this.', 'ai-imagen'),
            ));
        }
        
        $provider = isset($_POST['provider']) ? sanitize_text_field($_POST['provider']) : '';
        
        if (empty($provider)) {
            wp_send_json_error(array(
                'message' => __('Provider is required.', 'ai-imagen'),
            ));
        }
        
        $generator = AI_Imagen_Generator::get_instance();
        $models = $generator->get_provider_models($provider);
        
        wp_send_json_success(array(
            'models' => $models,
        ));
    }
    
    /**
     * AJAX handler for saving image to library
     * 
     * @return void
     */
    public function ajax_save_to_library() {
        // Verify nonce
        check_ajax_referer('ai_imagen_admin', 'nonce');

        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('You do not have permission to save images.', 'ai-imagen'),
            ));
        }

        // Get image URL - don't use esc_url_raw() as it strips Base64 data URLs
        $image_url = isset($_POST['image_url']) ? sanitize_text_field(wp_unslash($_POST['image_url'])) : '';
        $metadata = isset($_POST['metadata']) ? json_decode(stripslashes($_POST['metadata']), true) : array();

        if (empty($image_url)) {
            wp_send_json_error(array(
                'message' => __('Image URL is required.', 'ai-imagen'),
            ));
        }
        
        $media = AI_Imagen_Media::get_instance();
        $attachment_id = $media->save_to_library($image_url, $metadata);
        
        if (is_wp_error($attachment_id)) {
            wp_send_json_error(array(
                'message' => $attachment_id->get_error_message(),
            ));
        }
        
        wp_send_json_success(array(
            'attachment_id' => $attachment_id,
            'message' => __('Image saved to media library!', 'ai-imagen'),
        ));
    }
    
    /**
     * AJAX handler for deleting image
     * 
     * @return void
     */
    public function ajax_delete_image() {
        // Verify nonce
        check_ajax_referer('ai_imagen_admin', 'nonce');
        
        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('You do not have permission to delete images.', 'ai-imagen'),
            ));
        }
        
        $attachment_id = isset($_POST['attachment_id']) ? absint($_POST['attachment_id']) : 0;
        
        if (empty($attachment_id)) {
            wp_send_json_error(array(
                'message' => __('Attachment ID is required.', 'ai-imagen'),
            ));
        }
        
        $media = AI_Imagen_Media::get_instance();
        $deleted = $media->delete_image($attachment_id);
        
        if (!$deleted) {
            wp_send_json_error(array(
                'message' => __('Failed to delete image.', 'ai-imagen'),
            ));
        }
        
        wp_send_json_success(array(
            'message' => __('Image deleted successfully!', 'ai-imagen'),
        ));
    }
    
    /**
     * AJAX handler for enhancing prompt
     * 
     * @return void
     */
    public function ajax_enhance_prompt() {
        // Verify nonce
        check_ajax_referer('ai_imagen_admin', 'nonce');
        
        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('You do not have permission to enhance prompts.', 'ai-imagen'),
            ));
        }
        
        // Check if feature is enabled
        $settings = AI_Imagen_Settings::get_instance();
        if (!$settings->get('enable_prompt_enhancement', true)) {
            wp_send_json_error(array(
                'message' => __('Prompt enhancement is disabled.', 'ai-imagen'),
            ));
        }
        
        $prompt = isset($_POST['prompt']) ? sanitize_textarea_field($_POST['prompt']) : '';
        
        if (empty($prompt)) {
            wp_send_json_error(array(
                'message' => __('Prompt is required.', 'ai-imagen'),
            ));
        }
        
        // Use AI-Core to enhance prompt
        if (!function_exists('ai_core')) {
            wp_send_json_error(array(
                'message' => __('AI-Core is not available.', 'ai-imagen'),
            ));
        }
        
        $ai_core = ai_core();
        
        $enhancement_prompt = "Enhance this image generation prompt to be more detailed and effective. Keep it concise but add relevant visual details, style, and quality descriptors. Original prompt: {$prompt}";
        
        $response = $ai_core->send_text_request(
            'gpt-4o-mini',
            array(
                array('role' => 'user', 'content' => $enhancement_prompt)
            ),
            array('max_tokens' => 200)
        );
        
        if (is_wp_error($response)) {
            wp_send_json_error(array(
                'message' => $response->get_error_message(),
            ));
        }
        
        $enhanced_prompt = isset($response['choices'][0]['message']['content']) ? $response['choices'][0]['message']['content'] : $prompt;
        
        wp_send_json_success(array(
            'enhanced_prompt' => $enhanced_prompt,
        ));
    }
    
    /**
     * AJAX handler for getting quick start ideas
     * 
     * @return void
     */
    public function ajax_get_ideas() {
        // Verify nonce
        check_ajax_referer('ai_imagen_admin', 'nonce');
        
        // Check capabilities
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array(
                'message' => __('You do not have permission to access this.', 'ai-imagen'),
            ));
        }
        
        $category = isset($_POST['category']) ? sanitize_text_field($_POST['category']) : '';
        
        $ideas = AI_Imagen_Prompts::get_quick_start_ideas($category);
        
        wp_send_json_success(array(
            'ideas' => $ideas,
        ));
    }
}

// Initialize AJAX handlers
AI_Imagen_AJAX::get_instance();

