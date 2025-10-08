<?php
/**
 * AI-Core Stats Class
 * 
 * Handles usage statistics tracking and display
 * 
 * @package AI_Core
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AI-Core Stats Class
 * 
 * Manages usage statistics
 */
class AI_Core_Stats {
    
    /**
     * Class instance
     * 
     * @var AI_Core_Stats
     */
    private static $instance = null;
    
    /**
     * Get class instance
     * 
     * @return AI_Core_Stats
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
     * Normalize stats structure to models/tools format
     *
     * @param mixed $stats Raw stats option value
     * @return array Normalized stats data
     */
    private function normalize_stats($stats) {
        if (!is_array($stats)) {
            $stats = array();
        }

        if (!isset($stats['models']) || !is_array($stats['models'])) {
            $legacy = $stats;
            $stats = array(
                'models' => array(),
                'tools' => array(),
            );

            if (!isset($legacy['models'])) {
                foreach ($legacy as $key => $value) {
                    if (is_array($value) && isset($value['requests'])) {
                        $stats['models'][$key] = $value;
                    }
                }
            }
        }

        if (!isset($stats['tools']) || !is_array($stats['tools'])) {
            $stats['tools'] = array();
        }

        return $stats;
    }

    /**
     * Get all statistics
     *
     * @return array Statistics data
     */
    public function get_stats() {
        return $this->normalize_stats(get_option('ai_core_stats', array()));
    }

    /**
     * Get statistics for a specific model
     *
     * @param string $model Model identifier
     * @return array Model statistics
     */
    public function get_model_stats($model) {
        $stats = $this->get_stats();
        $models = $stats['models'] ?? array();

        return $models[$model] ?? array(
            'requests' => 0,
            'input_tokens' => 0,
            'output_tokens' => 0,
            'total_tokens' => 0,
            'total_cost' => 0,
            'errors' => 0,
            'last_used' => null,
            'provider' => null
        );
    }

    /**
     * Get statistics grouped by tool
     *
     * @return array Tool statistics
     */
    public function get_tool_stats() {
        $stats = $this->get_stats();
        return $stats['tools'] ?? array();
    }

    /**
     * Get total statistics across all models
     *
     * @return array Total statistics
     */
    public function get_total_stats() {
        $stats = $this->get_stats();
        $models = $stats['models'] ?? array();
        $tools = $stats['tools'] ?? array();
        $total = array(
            'requests' => 0,
            'input_tokens' => 0,
            'output_tokens' => 0,
            'total_tokens' => 0,
            'total_cost' => 0,
            'errors' => 0,
            'models_used' => count($models),
            'tools_used' => count($tools)
        );

        foreach ($models as $model_stats) {
            $total['requests'] += $model_stats['requests'] ?? 0;
            $total['input_tokens'] += $model_stats['input_tokens'] ?? 0;
            $total['output_tokens'] += $model_stats['output_tokens'] ?? 0;
            $total['total_tokens'] += $model_stats['total_tokens'] ?? ($model_stats['tokens'] ?? 0);
            $total['total_cost'] += $model_stats['total_cost'] ?? 0;
            $total['errors'] += $model_stats['errors'] ?? 0;
        }

        return $total;
    }

    /**
     * Get statistics grouped by provider
     *
     * @return array Provider statistics
     */
    public function get_provider_stats() {
        $stats = $this->get_stats();
        $models = $stats['models'] ?? array();
        $providers = array();

        foreach ($models as $model => $model_stats) {
            $provider = $model_stats['provider'] ?? $this->detect_provider($model);

            if (!$provider) {
                continue;
            }

            if (!isset($providers[$provider])) {
                $providers[$provider] = array(
                    'requests' => 0,
                    'input_tokens' => 0,
                    'output_tokens' => 0,
                    'total_tokens' => 0,
                    'total_cost' => 0,
                    'errors' => 0,
                    'models' => array()
                );
            }

            $providers[$provider]['requests'] += $model_stats['requests'] ?? 0;
            $providers[$provider]['input_tokens'] += $model_stats['input_tokens'] ?? 0;
            $providers[$provider]['output_tokens'] += $model_stats['output_tokens'] ?? 0;
            $providers[$provider]['total_tokens'] += $model_stats['total_tokens'] ?? ($model_stats['tokens'] ?? 0);
            $providers[$provider]['total_cost'] += $model_stats['total_cost'] ?? 0;
            $providers[$provider]['errors'] += $model_stats['errors'] ?? 0;
            $providers[$provider]['models'][] = $model;
        }

        return $providers;
    }

    /**
     * Detect provider from model name
     *
     * @param string $model Model identifier
     * @return string|null Provider name
     */
    private function detect_provider($model) {
        $model_lower = strtolower($model);

        if (strpos($model_lower, 'gpt') === 0 || strpos($model_lower, 'o1') === 0 ||
            strpos($model_lower, 'o3') === 0 || strpos($model_lower, 'dall-e') === 0 ||
            strpos($model_lower, 'image-openai') === 0) {
            return 'openai';
        }

        if (strpos($model_lower, 'claude') === 0 || strpos($model_lower, 'image-anthropic') === 0) {
            return 'anthropic';
        }

        if (strpos($model_lower, 'gemini') === 0 || strpos($model_lower, 'imagen') === 0 ||
            strpos($model_lower, 'image-gemini') === 0) {
            return 'gemini';
        }

        if (strpos($model_lower, 'grok') === 0 || strpos($model_lower, 'image-grok') === 0) {
            return 'grok';
        }

        return null;
    }

    /**
     * Get a display label for a tool key
     *
     * @param string $tool Tool identifier
     * @return string Translated label
     */
    private function get_tool_label($tool) {
        $labels = array(
            'settings_page' => __('Settings Page', 'ai-core'),
            'prompt_library' => __('Prompt Library', 'ai-core'),
            'ai_imagen' => __('AI Imagen', 'ai-core'),
            'ai_scribe' => __('AI Scribe', 'ai-core'),
        );

        $fallback = ucwords(str_replace(array('-', '_'), ' ', $tool));
        $label = $labels[$tool] ?? $fallback;

        return apply_filters('ai_core_tool_label', $label, $tool);
    }
    
    /**
     * Reset all statistics
     * 
     * @return bool Success status
     */
    public function reset_stats() {
        return update_option('ai_core_stats', array(
            'models' => array(),
            'tools' => array(),
        ));
    }
    
    /**
     * Format statistics for display
     *
     * @return string HTML formatted statistics
     */
    public function format_stats_html() {
        $stats = $this->get_stats();
        $models = $stats['models'] ?? array();
        $tool_stats = $stats['tools'] ?? array();
        $total = $this->get_total_stats();
        $provider_stats = $this->get_provider_stats();

        if (empty($models) && empty($tool_stats)) {
            return '<p>' . esc_html__('No usage statistics available yet.', 'ai-core') . '</p>';
        }

        // Total Usage Summary
        $html = '<div class="ai-core-stats-summary">';
        $html .= '<h3>' . esc_html__('Total Usage', 'ai-core') . '</h3>';
        $html .= '<div class="ai-core-stats-grid">';
        $html .= '<div class="stat-box"><span class="stat-label">' . esc_html__('Total Requests', 'ai-core') . '</span><span class="stat-value">' . number_format($total['requests']) . '</span></div>';
        $html .= '<div class="stat-box"><span class="stat-label">' . esc_html__('Input Tokens', 'ai-core') . '</span><span class="stat-value">' . number_format($total['input_tokens']) . '</span></div>';
        $html .= '<div class="stat-box"><span class="stat-label">' . esc_html__('Output Tokens', 'ai-core') . '</span><span class="stat-value">' . number_format($total['output_tokens']) . '</span></div>';
        $html .= '<div class="stat-box"><span class="stat-label">' . esc_html__('Total Tokens', 'ai-core') . '</span><span class="stat-value">' . number_format($total['total_tokens']) . '</span></div>';
        $html .= '<div class="stat-box"><span class="stat-label">' . esc_html__('Total Cost', 'ai-core') . '</span><span class="stat-value">$' . number_format($total['total_cost'], 4) . '</span></div>';
        $html .= '<div class="stat-box"><span class="stat-label">' . esc_html__('Errors', 'ai-core') . '</span><span class="stat-value">' . number_format($total['errors']) . '</span></div>';
        $html .= '<div class="stat-box"><span class="stat-label">' . esc_html__('Models Used', 'ai-core') . '</span><span class="stat-value">' . number_format($total['models_used']) . '</span></div>';
        $html .= '<div class="stat-box"><span class="stat-label">' . esc_html__('Providers', 'ai-core') . '</span><span class="stat-value">' . count($provider_stats) . '</span></div>';
        $html .= '<div class="stat-box"><span class="stat-label">' . esc_html__('Tools', 'ai-core') . '</span><span class="stat-value">' . number_format($total['tools_used']) . '</span></div>';
        $html .= '</div>';
        $html .= '</div>';

        // Usage by Provider
        if (!empty($provider_stats)) {
            $html .= '<div class="ai-core-stats-providers">';
            $html .= '<h3>' . esc_html__('Usage by Provider', 'ai-core') . '</h3>';
            $html .= '<table class="widefat">';
            $html .= '<thead><tr>';
            $html .= '<th>' . esc_html__('Provider', 'ai-core') . '</th>';
            $html .= '<th>' . esc_html__('Requests', 'ai-core') . '</th>';
            $html .= '</tr></thead>';
            $html .= '<tbody>';

            $provider_names = array(
                'openai' => 'OpenAI',
                'anthropic' => 'Anthropic',
                'gemini' => 'Google Gemini',
                'grok' => 'xAI Grok'
            );

            foreach ($provider_stats as $provider => $prov_stats) {
                $html .= '<tr>';
                $html .= '<td><strong>' . esc_html($provider_names[$provider] ?? ucfirst($provider)) . '</strong></td>';
                $html .= '<td>' . number_format($prov_stats['requests']) . '</td>';
                $html .= '</tr>';
            }

            $html .= '</tbody>';
            $html .= '</table>';
            $html .= '</div>';
        }

        // Usage by Tool
        if (!empty($tool_stats)) {
            $html .= '<div class="ai-core-stats-providers">';
            $html .= '<h3>' . esc_html__('Usage by Tool', 'ai-core') . '</h3>';
            $html .= '<table class="widefat">';
            $html .= '<thead><tr>';
            $html .= '<th>' . esc_html__('Tool', 'ai-core') . '</th>';
            $html .= '<th>' . esc_html__('Requests', 'ai-core') . '</th>';
            $html .= '</tr></thead>';
            $html .= '<tbody>';

            foreach ($tool_stats as $tool_key => $tool) {
                $html .= '<tr>';
                $html .= '<td><strong>' . esc_html($this->get_tool_label($tool_key)) . '</strong></td>';
                $html .= '<td>' . number_format($tool['requests'] ?? 0) . '</td>';
                $html .= '</tr>';
            }

            $html .= '</tbody>';
            $html .= '</table>';
            $html .= '</div>';
        }

        // Usage by Model
        if (!empty($models)) {
            $html .= '<div class="ai-core-stats-details">';
            $html .= '<h3>' . esc_html__('Usage by Model', 'ai-core') . '</h3>';
            $html .= '<table class="widefat">';
            $html .= '<thead><tr>';
            $html .= '<th>' . esc_html__('Model', 'ai-core') . '</th>';
            $html .= '<th>' . esc_html__('Provider', 'ai-core') . '</th>';
            $html .= '<th>' . esc_html__('Requests', 'ai-core') . '</th>';
            $html .= '<th>' . esc_html__('Input Tokens', 'ai-core') . '</th>';
            $html .= '<th>' . esc_html__('Output Tokens', 'ai-core') . '</th>';
            $html .= '<th>' . esc_html__('Total Tokens', 'ai-core') . '</th>';
            $html .= '<th>' . esc_html__('Cost', 'ai-core') . '</th>';
            $html .= '<th>' . esc_html__('Errors', 'ai-core') . '</th>';
            $html .= '<th>' . esc_html__('Last Used', 'ai-core') . '</th>';
            $html .= '</tr></thead>';
            $html .= '<tbody>';

            $provider_names = array(
                'openai' => 'OpenAI',
                'anthropic' => 'Anthropic',
                'gemini' => 'Gemini',
                'grok' => 'Grok'
            );

            foreach ($models as $model => $model_stats) {
                $provider = $model_stats['provider'] ?? $this->detect_provider($model);
                $html .= '<tr>';
                $html .= '<td><strong>' . esc_html($model) . '</strong></td>';
                $html .= '<td>' . esc_html($provider_names[$provider] ?? ucfirst($provider ?? 'Unknown')) . '</td>';
                $html .= '<td>' . number_format($model_stats['requests'] ?? 0) . '</td>';
                $html .= '<td>' . number_format($model_stats['input_tokens'] ?? 0) . '</td>';
                $html .= '<td>' . number_format($model_stats['output_tokens'] ?? 0) . '</td>';
                $html .= '<td>' . number_format($model_stats['total_tokens'] ?? ($model_stats['tokens'] ?? 0)) . '</td>';
                $html .= '<td>$' . number_format($model_stats['total_cost'] ?? 0, 4) . '</td>';
                $html .= '<td>' . number_format($model_stats['errors'] ?? 0) . '</td>';
                $last_used = $model_stats['last_used'] ?? null;
                $html .= '<td>' . ($last_used ? esc_html(date_i18n(get_option('date_format') . ' ' . get_option('time_format'), strtotime($last_used))) : '-') . '</td>';
                $html .= '</tr>';
            }

            $html .= '</tbody>';
            $html .= '</table>';
            $html .= '</div>';
        }

        return $html;
    }
}
