<?php
/**
 * AI-Pulse Content Generator
 *
 * @package AI_Pulse
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Content generation class
 */
class AI_Pulse_Generator {

    /**
     * Generate content for a keyword/mode/period combination
     *
     * @param string $keyword Keyword
     * @param string $mode Analysis mode
     * @param string $period Time period (daily, weekly, monthly)
     * @param array $options Additional options
     * @return array|WP_Error Generated content or error
     */
    public function generate_content($keyword, $mode, $period, $options = array()) {
        // Get AI-Core instance
        $ai_core = ai_pulse()->get_ai_core();
        
        if (!$ai_core || !$ai_core->is_configured()) {
            return new WP_Error('not_configured', 'AI-Core is not configured');
        }

        // Check if Gemini is configured
        $providers = $ai_core->get_configured_providers();
        if (!in_array('gemini', $providers)) {
            return new WP_Error('gemini_required', 'Google Gemini API key required');
        }

        // Build prompts
        $system_instruction = $this->build_system_instruction($keyword, $period, $options);
        $user_prompt = $this->build_user_prompt($keyword, $mode, $period, $options);

        if (!$user_prompt) {
            return new WP_Error('invalid_mode', 'Invalid analysis mode: ' . $mode);
        }

        // Prepare messages
        $messages = array(
            array('role' => 'system', 'content' => $system_instruction),
            array('role' => 'user', 'content' => $user_prompt)
        );

        // API options
        $api_options = array(
            'temperature' => 0.3,
            'tools' => array(array('googleSearch' => array()))  // Enable Search Grounding
        );

        // Usage context for tracking
        $usage_context = array(
            'tool' => 'ai-pulse',
            'mode' => $mode,
            'keyword' => $keyword
        );

        // Make API call
        try {
            $response = $ai_core->send_text_request(
                'gemini-2.0-flash-exp',
                $messages,
                $api_options,
                $usage_context
            );

            if (is_wp_error($response)) {
                AI_Pulse_Logger::log(
                    'API request failed: ' . $response->get_error_message(),
                    AI_Pulse_Logger::LOG_LEVEL_ERROR,
                    array('keyword' => $keyword, 'mode' => $mode)
                );
                return $response;
            }

            // Extract content and metadata (AI-Core returns OpenAI-compatible format)
            $content_text = '';
            if (isset($response['choices'][0]['message']['content'])) {
                $content_text = $response['choices'][0]['message']['content'];
            } elseif (isset($response['text'])) {
                // Fallback for legacy format
                $content_text = $response['text'];
            }

            $sources = $this->extract_sources($response);
            $tokens = isset($response['usage']) ? $response['usage'] : array();

            // Parse and validate JSON
            $parsed_data = $this->parse_and_validate($content_text, $mode);

            if (is_wp_error($parsed_data)) {
                AI_Pulse_Logger::log(
                    'JSON parsing failed: ' . $parsed_data->get_error_message(),
                    AI_Pulse_Logger::LOG_LEVEL_ERROR,
                    array('keyword' => $keyword, 'mode' => $mode, 'response' => $content_text)
                );
                return $parsed_data;
            }

            // Convert JSON to HTML
            $html = $this->json_to_html($parsed_data, $mode, $keyword, $period);

            // Calculate cost (AI-Core uses OpenAI token naming)
            $input_tokens = isset($tokens['prompt_tokens']) ? $tokens['prompt_tokens'] :
                           (isset($tokens['input_tokens']) ? $tokens['input_tokens'] : 0);
            $output_tokens = isset($tokens['completion_tokens']) ? $tokens['completion_tokens'] :
                            (isset($tokens['output_tokens']) ? $tokens['output_tokens'] : 0);
            $cost = $this->calculate_cost($input_tokens, $output_tokens);

            // Return complete data package
            return array(
                'html' => $html,
                'json' => json_encode($parsed_data),
                'sources' => $sources,
                'date_range' => $this->get_date_range($period),
                'input_tokens' => $input_tokens,
                'output_tokens' => $output_tokens,
                'cost' => $cost,
                'raw_response' => $content_text
            );

        } catch (Exception $e) {
            AI_Pulse_Logger::log(
                'Exception during generation: ' . $e->getMessage(),
                AI_Pulse_Logger::LOG_LEVEL_ERROR,
                array('keyword' => $keyword, 'mode' => $mode, 'exception' => $e)
            );
            return new WP_Error('generation_failed', $e->getMessage());
        }
    }

    /**
     * Build system instruction with variable substitution
     *
     * @param string $keyword Keyword
     * @param string $period Time period
     * @param array $options Additional options
     * @return string System instruction
     */
    private function build_system_instruction($keyword, $period, $options) {
        $template = AI_Pulse_Modes::get_system_instruction();
        $location = isset($options['location']) ? $options['location'] : AI_Pulse_Settings::get('default_location');

        $date_range = $this->get_date_range($period);
        $period_description = $this->get_period_description($period);

        $replacements = array(
            '{current_date}' => date('j M Y'),
            '{date_range}' => $date_range,
            '{period_description}' => $period_description,
            '{keyword}' => $keyword,
            '{location}' => $location
        );

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    /**
     * Build user prompt with variable substitution
     *
     * @param string $keyword Keyword
     * @param string $mode Analysis mode
     * @param string $period Time period
     * @param array $options Additional options
     * @return string|false User prompt or false
     */
    private function build_user_prompt($keyword, $mode, $period, $options) {
        $template = AI_Pulse_Modes::get_prompt($mode);

        if (!$template) {
            return false;
        }

        $location = isset($options['location']) ? $options['location'] : AI_Pulse_Settings::get('default_location');
        $date_range = $this->get_date_range($period);
        $period_description = $this->get_period_description($period);

        $replacements = array(
            '{keyword}' => $keyword,
            '{date_range}' => $date_range,
            '{period_description}' => $period_description,
            '{location}' => $location
        );

        return str_replace(array_keys($replacements), array_values($replacements), $template);
    }

    /**
     * Get date range for a period
     *
     * @param string $period Period (daily, weekly, monthly)
     * @return string Date range string
     */
    private function get_date_range($period) {
        $end_date = current_time('timestamp');

        switch ($period) {
            case 'daily':
                $start_date = strtotime('-1 day', $end_date);
                break;
            case 'weekly':
                $start_date = strtotime('-7 days', $end_date);
                break;
            case 'monthly':
                $start_date = strtotime('-30 days', $end_date);
                break;
            default:
                $start_date = strtotime('-7 days', $end_date);
        }

        return date('j M Y', $start_date) . ' to ' . date('j M Y', $end_date);
    }

    /**
     * Get period description
     *
     * @param string $period Period
     * @return string Description
     */
    private function get_period_description($period) {
        $descriptions = array(
            'daily' => 'LAST 24 HOURS',
            'weekly' => 'LAST 7 DAYS',
            'monthly' => 'LAST 30 DAYS'
        );

        return isset($descriptions[$period]) ? $descriptions[$period] : 'LAST 7 DAYS';
    }

    /**
     * Extract sources from API response
     *
     * @param array $response API response
     * @return array Sources
     */
    private function extract_sources($response) {
        $sources = array();

        if (isset($response['candidates'][0]['groundingMetadata']['groundingChunks'])) {
            foreach ($response['candidates'][0]['groundingMetadata']['groundingChunks'] as $chunk) {
                if (isset($chunk['web'])) {
                    $sources[] = array(
                        'uri' => $chunk['web']['uri'],
                        'title' => isset($chunk['web']['title']) ? $chunk['web']['title'] : $chunk['web']['uri']
                    );
                }
            }
        }

        return $sources;
    }

    /**
     * Parse and validate JSON response
     *
     * @param string $json_string JSON string
     * @param string $mode Analysis mode
     * @return array|WP_Error Parsed data or error
     */
    private function parse_and_validate($json_string, $mode) {
        // Clean Gemini response (remove markdown code blocks)
        $clean = preg_replace('/```json\s*/', '', $json_string);
        $clean = preg_replace('/```\s*/', '', $clean);

        // Extract JSON object
        $first_open = strpos($clean, '{');
        $last_close = strrpos($clean, '}');

        if ($first_open !== false && $last_close !== false) {
            $clean = substr($clean, $first_open, $last_close - $first_open + 1);
        }

        $data = json_decode(trim($clean), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new WP_Error('invalid_json', 'Failed to parse JSON: ' . json_last_error_msg());
        }

        // Validate structure
        $validator = new AI_Pulse_Validator();
        if (!$validator->validate_mode_structure($data, $mode)) {
            return new WP_Error('invalid_structure', 'JSON structure does not match expected format for mode: ' . $mode);
        }

        return $data;
    }

    /**
     * Calculate cost based on token usage
     *
     * @param int $input_tokens Input tokens
     * @param int $output_tokens Output tokens
     * @return float Cost in USD
     */
    private function calculate_cost($input_tokens, $output_tokens) {
        // Gemini 2.0 Flash pricing (as of Dec 2025)
        $input_cost = ($input_tokens / 1000000) * 0.075;  // $0.075 per 1M tokens
        $output_cost = ($output_tokens / 1000000) * 0.30;  // $0.30 per 1M tokens

        return $input_cost + $output_cost;
    }

    /**
     * Convert JSON data to HTML
     *
     * @param array $data Parsed JSON data
     * @param string $mode Analysis mode
     * @param string $keyword Keyword
     * @param string $period Time period
     * @return string HTML output
     */
    private function json_to_html($data, $mode, $keyword, $period) {
        $html = '<div class="ai-pulse-content ai-pulse-mode-' . esc_attr(strtolower($mode)) . '">';

        // Header
        $html .= '<div class="ai-pulse-header">';
        $html .= '<h3>' . esc_html(ucfirst($period)) . ' ' . esc_html($this->get_mode_name($mode)) . ': ' . esc_html($keyword) . '</h3>';
        $html .= '<span class="ai-pulse-date">' . esc_html($this->get_date_range($period)) . '</span>';
        $html .= '</div>';

        // Summary
        if (isset($data['summary'])) {
            $html .= '<div class="ai-pulse-summary">';
            $html .= '<p>' . esc_html($data['summary']) . '</p>';
            $html .= '</div>';
        }

        // Mode-specific content
        switch ($mode) {
            case 'SUMMARY':
                $html .= $this->render_summary($data);
                break;
            case 'FAQS':
                $html .= $this->render_faqs($data);
                break;
            case 'STATS':
                $html .= $this->render_stats($data);
                break;
            case 'FORECAST':
                $html .= $this->render_forecast($data);
                break;
            case 'GAPS':
                $html .= $this->render_gaps($data);
                break;
            case 'LOCAL':
                $html .= $this->render_local($data);
                break;
            case 'WINS':
                $html .= $this->render_wins($data);
                break;
            case 'GLOSSARY':
                $html .= $this->render_glossary($data);
                break;
            case 'PLATFORMS':
                $html .= $this->render_platforms($data);
                break;
            case 'PULSE':
                $html .= $this->render_pulse($data);
                break;
            case 'EXPLORER':
                $html .= $this->render_explorer($data);
                break;
            case 'ALL':
                $html .= $this->render_all($data);
                break;
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * Get mode display name
     *
     * @param string $mode Mode identifier
     * @return string Display name
     */
    private function get_mode_name($mode) {
        $modes = AI_Pulse_Modes::get_all_modes();
        return isset($modes[$mode]['name']) ? $modes[$mode]['name'] : $mode;
    }

    /**
     * Render SUMMARY mode
     */
    private function render_summary($data) {
        if (!isset($data['trends']) || !is_array($data['trends'])) {
            return '';
        }

        $html = '<ul class="ai-pulse-trends">';
        foreach ($data['trends'] as $trend) {
            $html .= '<li class="ai-pulse-trend-item">';
            $html .= '<h4>' . esc_html($trend['term']) . '</h4>';
            $html .= '<p class="insight">' . esc_html($trend['insight']) . '</p>';
            $html .= '<p class="implication"><strong>Why it matters:</strong> ' . esc_html($trend['implication']) . '</p>';
            $html .= '<p class="action"><strong>What we do:</strong> ' . esc_html($trend['action']) . '</p>';
            $html .= '</li>';
        }
        $html .= '</ul>';

        return $html;
    }

    /**
     * Render FAQS mode
     */
    private function render_faqs($data) {
        if (!isset($data['faqs']) || !is_array($data['faqs'])) {
            return '';
        }

        $html = '<div class="ai-pulse-faqs">';
        foreach ($data['faqs'] as $faq) {
            $html .= '<div class="ai-pulse-faq-item">';
            $html .= '<h4>' . esc_html($faq['question']) . '</h4>';
            $html .= '<p>' . esc_html($faq['answer']) . '</p>';
            $html .= '</div>';
        }
        $html .= '</div>';

        return $html;
    }

    /**
     * Render STATS mode
     */
    private function render_stats($data) {
        if (!isset($data['stats']) || !is_array($data['stats'])) {
            return '';
        }

        $html = '<ul class="ai-pulse-stats">';
        foreach ($data['stats'] as $stat) {
            $html .= '<li class="ai-pulse-stat-item">';
            $html .= '<div class="stat">' . esc_html($stat['stat']) . '</div>';
            $html .= '<div class="source"><em>Source: ' . esc_html($stat['source']) . '</em></div>';
            $html .= '<p class="context">' . esc_html($stat['context']) . '</p>';
            $html .= '</li>';
        }
        $html .= '</ul>';

        return $html;
    }

    // Additional render methods for other modes would follow the same pattern
    private function render_forecast($data) { return $this->render_generic_list($data, 'periods'); }
    private function render_gaps($data) { return $this->render_generic_list($data, 'gaps'); }
    private function render_local($data) { return $this->render_generic_list($data, 'trends'); }
    private function render_wins($data) { return $this->render_generic_list($data, 'cases'); }
    private function render_glossary($data) { return $this->render_generic_list($data, 'terms'); }
    private function render_platforms($data) { return $this->render_generic_list($data, 'platforms'); }
    private function render_pulse($data) { return $this->render_generic_list($data, 'signals'); }
    private function render_explorer($data) { return $this->render_generic_list($data, 'themes'); }

    /**
     * Render ALL mode (mega dashboard)
     */
    private function render_all($data) {
        $html = '<div class="ai-pulse-mega-dashboard">';

        // Render each section if it exists
        if (isset($data['trends'])) {
            $html .= '<div class="ai-pulse-section"><h4>Trends</h4>' . $this->render_summary(array('trends' => $data['trends'])) . '</div>';
        }
        if (isset($data['faqs'])) {
            $html .= '<div class="ai-pulse-section"><h4>FAQs</h4>' . $this->render_faqs(array('faqs' => $data['faqs'])) . '</div>';
        }
        if (isset($data['stats'])) {
            $html .= '<div class="ai-pulse-section"><h4>Statistics</h4>' . $this->render_stats(array('stats' => $data['stats'])) . '</div>';
        }

        $html .= '</div>';
        return $html;
    }

    /**
     * Generic list renderer for simple modes
     */
    private function render_generic_list($data, $key) {
        if (!isset($data[$key]) || !is_array($data[$key])) {
            return '';
        }

        $html = '<ul class="ai-pulse-list">';
        foreach ($data[$key] as $item) {
            $html .= '<li class="ai-pulse-list-item">';
            foreach ($item as $field => $value) {
                $html .= '<div class="' . esc_attr($field) . '">' . esc_html($value) . '</div>';
            }
            $html .= '</li>';
        }
        $html .= '</ul>';

        return $html;
    }
}

