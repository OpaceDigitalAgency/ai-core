<?php
/**
 * AI-Imagen Media Class
 * 
 * Handles WordPress media library integration
 * 
 * @package AI_Imagen
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AI-Imagen Media Class
 */
class AI_Imagen_Media {
    
    /**
     * Class instance
     * 
     * @var AI_Imagen_Media
     */
    private static $instance = null;
    
    /**
     * Get class instance
     * 
     * @return AI_Imagen_Media
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
     * Save image to media library
     * 
     * @param string $image_url URL of the image to save
     * @param array $metadata Image metadata
     * @return int|WP_Error Attachment ID or error
     */
    public function save_to_library($image_url, $metadata = array()) {
        // Download image
        $temp_file = $this->download_image($image_url);
        
        if (is_wp_error($temp_file)) {
            return $temp_file;
        }
        
        // Prepare file array
        $file_array = array(
            'name' => $this->generate_filename($metadata),
            'tmp_name' => $temp_file,
        );
        
        // Upload to media library
        $attachment_id = media_handle_sideload($file_array, 0);
        
        // Clean up temp file
        if (file_exists($temp_file)) {
            @unlink($temp_file);
        }
        
        if (is_wp_error($attachment_id)) {
            return $attachment_id;
        }
        
        // Add metadata
        $this->add_metadata($attachment_id, $metadata);
        
        return $attachment_id;
    }
    
    /**
     * Download image from URL
     * 
     * @param string $url Image URL
     * @return string|WP_Error Path to temp file or error
     */
    private function download_image($url) {
        // Download file
        $response = wp_remote_get($url, array(
            'timeout' => 60,
            'sslverify' => false,
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = wp_remote_retrieve_body($response);
        
        if (empty($body)) {
            return new WP_Error(
                'empty_response',
                __('Failed to download image: empty response.', 'ai-imagen')
            );
        }
        
        // Save to temp file
        $temp_file = wp_tempnam();
        
        if (!file_put_contents($temp_file, $body)) {
            return new WP_Error(
                'save_failed',
                __('Failed to save image to temporary file.', 'ai-imagen')
            );
        }
        
        return $temp_file;
    }
    
    /**
     * Generate filename for image
     * 
     * @param array $metadata Image metadata
     * @return string Filename
     */
    private function generate_filename($metadata) {
        $prefix = 'ai-imagen';
        $timestamp = time();
        
        // Add provider to filename
        if (!empty($metadata['provider'])) {
            $prefix .= '-' . sanitize_file_name($metadata['provider']);
        }
        
        // Add model to filename
        if (!empty($metadata['model'])) {
            $model_name = sanitize_file_name($metadata['model']);
            $prefix .= '-' . $model_name;
        }
        
        // Determine extension
        $extension = 'png';
        if (!empty($metadata['format'])) {
            $extension = $metadata['format'];
        }
        
        return $prefix . '-' . $timestamp . '.' . $extension;
    }
    
    /**
     * Add metadata to attachment
     * 
     * @param int $attachment_id Attachment ID
     * @param array $metadata Metadata to add
     * @return void
     */
    private function add_metadata($attachment_id, $metadata) {
        // Set alt text from prompt
        if (!empty($metadata['prompt'])) {
            $alt_text = $this->generate_alt_text($metadata['prompt']);
            update_post_meta($attachment_id, '_wp_attachment_image_alt', $alt_text);
        }
        
        // Set caption
        if (!empty($metadata['prompt'])) {
            wp_update_post(array(
                'ID' => $attachment_id,
                'post_excerpt' => wp_trim_words($metadata['prompt'], 20),
            ));
        }
        
        // Add custom metadata
        update_post_meta($attachment_id, '_ai_imagen_generated', true);
        update_post_meta($attachment_id, '_ai_imagen_provider', isset($metadata['provider']) ? $metadata['provider'] : '');
        update_post_meta($attachment_id, '_ai_imagen_model', isset($metadata['model']) ? $metadata['model'] : '');
        update_post_meta($attachment_id, '_ai_imagen_prompt', isset($metadata['prompt']) ? $metadata['prompt'] : '');
        update_post_meta($attachment_id, '_ai_imagen_timestamp', time());
        
        // Add use case, role, style if provided
        if (!empty($metadata['use_case'])) {
            update_post_meta($attachment_id, '_ai_imagen_use_case', $metadata['use_case']);
        }
        
        if (!empty($metadata['role'])) {
            update_post_meta($attachment_id, '_ai_imagen_role', $metadata['role']);
        }
        
        if (!empty($metadata['style'])) {
            update_post_meta($attachment_id, '_ai_imagen_style', $metadata['style']);
        }
    }
    
    /**
     * Generate alt text from prompt
     * 
     * @param string $prompt Image prompt
     * @return string Alt text
     */
    private function generate_alt_text($prompt) {
        // Clean and truncate prompt for alt text
        $alt_text = wp_strip_all_tags($prompt);
        $alt_text = wp_trim_words($alt_text, 15, '');
        
        return $alt_text;
    }
    
    /**
     * Get generated images
     * 
     * @param array $args Query arguments
     * @return array List of attachments
     */
    public function get_generated_images($args = array()) {
        $defaults = array(
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'posts_per_page' => 20,
            'meta_key' => '_ai_imagen_generated',
            'meta_value' => true,
            'orderby' => 'date',
            'order' => 'DESC',
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $query = new WP_Query($args);
        
        return $query->posts;
    }
    
    /**
     * Get image metadata
     * 
     * @param int $attachment_id Attachment ID
     * @return array Image metadata
     */
    public function get_image_metadata($attachment_id) {
        return array(
            'provider' => get_post_meta($attachment_id, '_ai_imagen_provider', true),
            'model' => get_post_meta($attachment_id, '_ai_imagen_model', true),
            'prompt' => get_post_meta($attachment_id, '_ai_imagen_prompt', true),
            'use_case' => get_post_meta($attachment_id, '_ai_imagen_use_case', true),
            'role' => get_post_meta($attachment_id, '_ai_imagen_role', true),
            'style' => get_post_meta($attachment_id, '_ai_imagen_style', true),
            'timestamp' => get_post_meta($attachment_id, '_ai_imagen_timestamp', true),
        );
    }
    
    /**
     * Delete generated image
     * 
     * @param int $attachment_id Attachment ID
     * @return bool True on success, false on failure
     */
    public function delete_image($attachment_id) {
        // Verify it's an AI-generated image
        if (!get_post_meta($attachment_id, '_ai_imagen_generated', true)) {
            return false;
        }
        
        return wp_delete_attachment($attachment_id, true) !== false;
    }
    
    /**
     * Get generation count for today
     * 
     * @return int Number of images generated today
     */
    public function get_today_count() {
        $today_start = strtotime('today midnight');
        
        $query = new WP_Query(array(
            'post_type' => 'attachment',
            'post_status' => 'inherit',
            'posts_per_page' => -1,
            'meta_query' => array(
                array(
                    'key' => '_ai_imagen_generated',
                    'value' => true,
                ),
                array(
                    'key' => '_ai_imagen_timestamp',
                    'value' => $today_start,
                    'compare' => '>=',
                    'type' => 'NUMERIC',
                ),
            ),
            'fields' => 'ids',
        ));
        
        return $query->found_posts;
    }
    
    /**
     * Check if generation limit reached
     * 
     * @return bool True if limit reached, false otherwise
     */
    public function is_limit_reached() {
        $settings = AI_Imagen_Settings::get_instance();
        $limit = $settings->get('generation_limit', 0);
        
        // 0 means unlimited
        if ($limit === 0) {
            return false;
        }
        
        $today_count = $this->get_today_count();
        
        return $today_count >= $limit;
    }
    
    /**
     * Get remaining generations for today
     * 
     * @return int|string Number of remaining generations or 'unlimited'
     */
    public function get_remaining_count() {
        $settings = AI_Imagen_Settings::get_instance();
        $limit = $settings->get('generation_limit', 0);
        
        // 0 means unlimited
        if ($limit === 0) {
            return 'unlimited';
        }
        
        $today_count = $this->get_today_count();
        $remaining = $limit - $today_count;
        
        return max(0, $remaining);
    }
}

