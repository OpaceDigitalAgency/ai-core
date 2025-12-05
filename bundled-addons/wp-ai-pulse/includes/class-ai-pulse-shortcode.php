<?php
/**
 * AI-Pulse Shortcode
 *
 * @package AI_Pulse
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Shortcode handler class
 */
class AI_Pulse_Shortcode {

    /**
     * Initialise shortcode
     */
    public static function init() {
        add_shortcode('ai_pulse', array(__CLASS__, 'render_shortcode'));
    }

    /**
     * Render shortcode
     *
     * @param array $atts Shortcode attributes
     * @return string HTML output
     */
    public static function render_shortcode($atts) {
        $atts = shortcode_atts(array(
            'keyword' => '',
            'mode' => 'SUMMARY',
            'period' => 'weekly',
            'location' => '',
            'update_interval' => '',
            'generate' => 'false'
        ), $atts);

        // Validate keyword
        if (empty($atts['keyword'])) {
            return '<div class="ai-pulse-error">Error: keyword attribute is required</div>';
        }

        // Normalise mode
        $mode = strtoupper($atts['mode']);
        $valid_modes = array_keys(AI_Pulse_Modes::get_all_modes());
        
        if (!in_array($mode, $valid_modes)) {
            return '<div class="ai-pulse-error">Error: Invalid mode "' . esc_html($atts['mode']) . '"</div>';
        }

        // Check if we should generate on-demand
        if ($atts['generate'] === 'true') {
            return self::generate_on_demand($atts['keyword'], $mode, $atts['period'], $atts);
        }

        // Retrieve stored content
        $content = AI_Pulse_Database::get_active_content($atts['keyword'], $mode, $atts['period']);

        if (!$content) {
            // No content found, optionally generate
            if (AI_Pulse_Settings::get('auto_generate_missing', false)) {
                return self::generate_on_demand($atts['keyword'], $mode, $atts['period'], $atts);
            }
            
            return '<div class="ai-pulse-notice">No content available for this keyword/mode combination. Content will be generated during the next scheduled update.</div>';
        }

        // Return stored HTML
        return $content->content_html;
    }

    /**
     * Generate content on-demand
     *
     * @param string $keyword Keyword
     * @param string $mode Mode
     * @param string $period Period
     * @param array $options Additional options
     * @return string HTML output
     */
    private static function generate_on_demand($keyword, $mode, $period, $options) {
        $generator = new AI_Pulse_Generator();
        
        $gen_options = array();
        if (!empty($options['location'])) {
            $gen_options['location'] = $options['location'];
        }

        $result = $generator->generate_content($keyword, $mode, $period, $gen_options);

        if (is_wp_error($result)) {
            AI_Pulse_Logger::log(
                'On-demand generation failed: ' . $result->get_error_message(),
                AI_Pulse_Logger::LOG_LEVEL_ERROR,
                array('keyword' => $keyword, 'mode' => $mode)
            );
            return '<div class="ai-pulse-error">Error generating content: ' . esc_html($result->get_error_message()) . '</div>';
        }

        // Store the generated content
        AI_Pulse_Database::store_content($keyword, $mode, $period, $result);

        return $result['html'];
    }
}

