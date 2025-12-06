<?php
/**
 * AI-Pulse AJAX Handlers
 *
 * @package AI_Pulse
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AJAX handler class
 */
class AI_Pulse_Ajax {

    /**
     * Initialise AJAX handlers
     */
    public static function init() {
        add_action('wp_ajax_ai_pulse_test_generate', array(__CLASS__, 'test_generate'));
        add_action('wp_ajax_ai_pulse_get_content', array(__CLASS__, 'get_content'));
        add_action('wp_ajax_ai_pulse_delete_content', array(__CLASS__, 'delete_content'));
        add_action('wp_ajax_ai_pulse_get_stats', array(__CLASS__, 'get_stats'));
    }

    /**
     * Test generation AJAX handler
     */
    public static function test_generate() {
        check_ajax_referer('ai_pulse_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorised'));
        }

        $keyword = sanitize_text_field($_POST['keyword']);
        $mode = sanitize_text_field($_POST['mode']);
        $period = sanitize_text_field($_POST['period']);
        $location = sanitize_text_field($_POST['location']);

        if (empty($keyword)) {
            wp_send_json_error(array('message' => 'Keyword is required'));
        }

        $generator = new AI_Pulse_Generator();

        $options = array();
        if (!empty($location)) {
            $options['location'] = $location;
        }

        $result = $generator->generate_content($keyword, $mode, $period, $options);

        if (is_wp_error($result)) {
            wp_send_json_error(array(
                'message' => $result->get_error_message(),
                'code' => $result->get_error_code()
            ));
        }

        // Store in database so it appears in statistics and content library
        $stored_id = AI_Pulse_Database::store_content($keyword, $mode, $period, $result);

        if (!$stored_id) {
            AI_Pulse_Logger::log(
                'Failed to store test-generated content in database',
                AI_Pulse_Logger::LOG_LEVEL_WARNING,
                array('keyword' => $keyword, 'mode' => $mode)
            );
        }

        wp_send_json_success(array(
            'html' => $result['html'],
            'json' => $result['json'],
            'sources' => $result['sources'],
            'tokens' => array(
                'input' => $result['input_tokens'],
                'output' => $result['output_tokens'],
                'total' => $result['input_tokens'] + $result['output_tokens']
            ),
            'cost' => number_format($result['cost'], 6),
            'stored_id' => $stored_id
        ));
    }

    /**
     * Get content AJAX handler
     */
    public static function get_content() {
        check_ajax_referer('ai_pulse_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorised'));
        }

        $keyword = sanitize_text_field($_POST['keyword']);
        $mode = isset($_POST['mode']) ? sanitize_text_field($_POST['mode']) : '';
        $limit = isset($_POST['limit']) ? intval($_POST['limit']) : 10;

        if (empty($keyword)) {
            wp_send_json_error(array('message' => 'Keyword is required'));
        }

        $content = AI_Pulse_Database::get_keyword_content($keyword, true);

        if (empty($content)) {
            wp_send_json_error(array('message' => 'No content found'));
        }

        wp_send_json_success(array('content' => $content));
    }

    /**
     * Delete content AJAX handler
     */
    public static function delete_content() {
        check_ajax_referer('ai_pulse_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorised'));
        }

        $id = intval($_POST['id']);

        if (empty($id)) {
            wp_send_json_error(array('message' => 'Content ID is required'));
        }

        $result = AI_Pulse_Database::delete_content($id);

        if ($result) {
            wp_send_json_success(array('message' => 'Content deleted'));
        } else {
            wp_send_json_error(array('message' => 'Failed to delete content'));
        }
    }

    /**
     * Get usage statistics AJAX handler
     */
    public static function get_stats() {
        check_ajax_referer('ai_pulse_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorised'));
        }

        $period = isset($_POST['period']) ? sanitize_text_field($_POST['period']) : 'month';

        $stats = AI_Pulse_Database::get_usage_stats($period);

        if (!$stats) {
            wp_send_json_error(array('message' => 'No statistics available'));
        }

        wp_send_json_success(array(
            'generations' => intval($stats->total_generations),
            'input_tokens' => intval($stats->total_input_tokens),
            'output_tokens' => intval($stats->total_output_tokens),
            'total_tokens' => intval($stats->total_input_tokens) + intval($stats->total_output_tokens),
            'cost' => number_format($stats->total_cost, 2)
        ));
    }
}

