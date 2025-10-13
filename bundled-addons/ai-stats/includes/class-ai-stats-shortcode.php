<?php
/**
 * AI-Stats Shortcode Class
 *
 * Handles [ai_stats_module] shortcode
 *
 * @package AI_Stats
 * @version 0.2.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Shortcode handler class
 */
class AI_Stats_Shortcode {
    
    /**
     * Singleton instance
     * 
     * @var AI_Stats_Shortcode
     */
    private static $instance = null;
    
    /**
     * Get instance
     * 
     * @return AI_Stats_Shortcode
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
        add_shortcode('ai_stats_module', array($this, 'render_shortcode'));
    }
    
    /**
     * Render shortcode
     * 
     * @param array $atts Shortcode attributes
     * @return string Rendered content
     */
    public function render_shortcode($atts) {
        $atts = shortcode_atts(array(
            'mode' => '',
            'style' => 'box',
        ), $atts, 'ai_stats_module');
        
        $settings = get_option('ai_stats_settings', array());
        $mode = !empty($atts['mode']) ? $atts['mode'] : ($settings['active_mode'] ?? 'statistics');
        
        // Get active content for mode
        $content = AI_Stats_Database::get_active_content($mode);
        
        if (!$content) {
            return '';
        }
        
        $style_class = 'ai-stats-module ai-stats-style-' . esc_attr($atts['style']);
        
        ob_start();
        ?>
        <div class="<?php echo esc_attr($style_class); ?>" data-content-id="<?php echo esc_attr($content->id); ?>">
            <div class="ai-stats-content">
                <?php echo wp_kses_post($content->content); ?>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }
}

