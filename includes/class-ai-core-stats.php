<?php
/**
 * Opace AI Hub Stats Class
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
 * Opace AI Hub Stats Class
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
        return $this->reconcile_pricing(false);
    }

    /**
     * Recalculate estimates from recorded usage and the current price source.
     * This repairs legacy rows that were incorrectly stored as $0 when a new
     * model was absent from the old bundled catalogue.
     *
     * @param bool $force Force a fresh remote lookup.
     * @return array Reconciled statistics.
     */
    public function reconcile_pricing($force = false) {
        $stats = $this->normalize_stats(get_option('ai_core_stats', array()));
        if (!class_exists('AI_Core_Pricing')) {
            return $stats;
        }

        $pricing = AI_Core_Pricing::get_instance();
        $changed = false;
        foreach ($stats['models'] as $model => &$model_stats) {
            $provider = $model_stats['provider'] ?? $this->detect_provider($model);
            $price = $force
                ? $pricing->get_remote_pricing($model, $provider, true)
                : $pricing->get_model_pricing($model, $provider);
            if (!$price && $force) {
                $price = $pricing->get_model_pricing($model, $provider);
            }

            $input = (int) ($model_stats['input_tokens'] ?? 0);
            $output = (int) ($model_stats['output_tokens'] ?? 0);
            $estimate = $price ? $pricing->calculate_cost($model, $input, $output, $provider) : null;
            $new_status = null === $estimate ? 'unavailable' : 'estimated';
            $new_cost = null === $estimate ? null : (float) $estimate;
            $source = $price['_source'] ?? null;
            $refreshed = $price['_refreshed_at'] ?? null;

            if (($model_stats['cost_status'] ?? null) !== $new_status
                || ($model_stats['estimated_cost'] ?? null) !== $new_cost
                || ($model_stats['pricing_source'] ?? null) !== $source
                || ($model_stats['pricing_refreshed_at'] ?? null) !== $refreshed) {
                $model_stats['cost_status'] = $new_status;
                $model_stats['estimated_cost'] = $new_cost;
                $model_stats['total_cost'] = null === $new_cost ? 0 : $new_cost;
                $model_stats['pricing_source'] = $source;
                $model_stats['pricing_refreshed_at'] = $refreshed;
                $changed = true;
            }
        }
        unset($model_stats);

        if ($changed) {
            update_option('ai_core_stats', $stats);
        }
        return $stats;
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
            'tools_used' => count($tools),
            'cost_unavailable_models' => 0
        );

        foreach ($models as $model_stats) {
            $total['requests'] += $model_stats['requests'] ?? 0;
            $total['input_tokens'] += $model_stats['input_tokens'] ?? 0;
            $total['output_tokens'] += $model_stats['output_tokens'] ?? 0;
            $total['total_tokens'] += $model_stats['total_tokens'] ?? ($model_stats['tokens'] ?? 0);
            $total['total_cost'] += $model_stats['total_cost'] ?? 0;
            $total['errors'] += $model_stats['errors'] ?? 0;
            if ('unavailable' === ($model_stats['cost_status'] ?? 'unavailable')) {
                $total['cost_unavailable_models']++;
            }
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
            'settings_page' => __('Settings Page', 'opace-ai-prompt-library-api-hub'),
            'prompt_library' => __('Prompt Library', 'opace-ai-prompt-library-api-hub'),
            'ai_imagen' => __('AI Imagen', 'opace-ai-prompt-library-api-hub'),
            'ai_scribe' => __('AI Scribe', 'opace-ai-prompt-library-api-hub'),
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
            return '<p>' . esc_html__('No usage statistics available yet.', 'opace-ai-prompt-library-api-hub') . '</p>';
        }

        // Total Usage Summary
        $html = '<div class="ai-core-stats-summary">';
        $html .= '<h3>' . esc_html__('Total Usage', 'opace-ai-prompt-library-api-hub') . '</h3>';
        $html .= '<div class="ai-core-stats-grid">';
        $html .= '<div class="stat-box"><span class="stat-label">' . esc_html__('Total Requests', 'opace-ai-prompt-library-api-hub') . '</span><span class="stat-value">' . number_format($total['requests']) . '</span></div>';
        $html .= '<div class="stat-box"><span class="stat-label">' . esc_html__('Input Tokens', 'opace-ai-prompt-library-api-hub') . '</span><span class="stat-value">' . number_format($total['input_tokens']) . '</span></div>';
        $html .= '<div class="stat-box"><span class="stat-label">' . esc_html__('Output Tokens', 'opace-ai-prompt-library-api-hub') . '</span><span class="stat-value">' . number_format($total['output_tokens']) . '</span></div>';
        $html .= '<div class="stat-box"><span class="stat-label">' . esc_html__('Total Tokens', 'opace-ai-prompt-library-api-hub') . '</span><span class="stat-value">' . number_format($total['total_tokens']) . '</span></div>';
        $cost_value = '$' . number_format($total['total_cost'], 4);
        if ($total['cost_unavailable_models'] > 0) {
            /* translators: %d: Number of models without available pricing data. */
            $cost_value .= '<small>' . sprintf(esc_html__('%d model(s) unavailable', 'opace-ai-prompt-library-api-hub'), (int) $total['cost_unavailable_models']) . '</small>';
        }
        $html .= '<div class="stat-box"><span class="stat-label">' . esc_html__('Estimated Cost', 'opace-ai-prompt-library-api-hub') . '</span><span class="stat-value">' . $cost_value . '</span></div>';
        $html .= '<div class="stat-box"><span class="stat-label">' . esc_html__('Errors', 'opace-ai-prompt-library-api-hub') . '</span><span class="stat-value">' . number_format($total['errors']) . '</span></div>';
        $html .= '<div class="stat-box"><span class="stat-label">' . esc_html__('Models Used', 'opace-ai-prompt-library-api-hub') . '</span><span class="stat-value">' . number_format($total['models_used']) . '</span></div>';
        $html .= '<div class="stat-box"><span class="stat-label">' . esc_html__('Providers', 'opace-ai-prompt-library-api-hub') . '</span><span class="stat-value">' . count($provider_stats) . '</span></div>';
        $html .= '<div class="stat-box"><span class="stat-label">' . esc_html__('Tools', 'opace-ai-prompt-library-api-hub') . '</span><span class="stat-value">' . number_format($total['tools_used']) . '</span></div>';
        $html .= '</div>';
        $html .= '<div class="notice notice-info inline ai-core-pricing-note"><p><strong>' . esc_html__('Published-rate estimate:', 'opace-ai-prompt-library-api-hub') . '</strong> ' . esc_html__('Opace AI Hub refreshes model prices from the public LiteLLM catalogue every 12 hours and falls back to its bundled catalogue when offline. This is not your provider invoice; free tiers, cached tokens, batches and negotiated rates can differ.', 'opace-ai-prompt-library-api-hub') . '</p></div>';
        $html .= '</div>';

        // Usage by Provider
        if (!empty($provider_stats)) {
            $html .= '<div class="ai-core-stats-providers">';
            $html .= '<h3>' . esc_html__('Usage by Provider', 'opace-ai-prompt-library-api-hub') . '</h3>';
            $html .= '<table class="widefat">';
            $html .= '<thead><tr>';
            $html .= '<th>' . esc_html__('Provider', 'opace-ai-prompt-library-api-hub') . '</th>';
            $html .= '<th>' . esc_html__('Requests', 'opace-ai-prompt-library-api-hub') . '</th>';
            $html .= '</tr></thead>';
            $html .= '<tbody>';

            $provider_names = array(
                'openai' => 'OpenAI',
                'anthropic' => 'Anthropic',
                'gemini' => 'Google Gemini'
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
            $html .= '<h3>' . esc_html__('Usage by Tool', 'opace-ai-prompt-library-api-hub') . '</h3>';
            $html .= '<table class="widefat">';
            $html .= '<thead><tr>';
            $html .= '<th>' . esc_html__('Tool', 'opace-ai-prompt-library-api-hub') . '</th>';
            $html .= '<th>' . esc_html__('Requests', 'opace-ai-prompt-library-api-hub') . '</th>';
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
            $html .= '<h3>' . esc_html__('Usage by Model', 'opace-ai-prompt-library-api-hub') . '</h3>';
            $html .= '<table class="widefat">';
            $html .= '<thead><tr>';
            $html .= '<th>' . esc_html__('Model', 'opace-ai-prompt-library-api-hub') . '</th>';
            $html .= '<th>' . esc_html__('Provider', 'opace-ai-prompt-library-api-hub') . '</th>';
            $html .= '<th>' . esc_html__('Requests', 'opace-ai-prompt-library-api-hub') . '</th>';
            $html .= '<th>' . esc_html__('Input Tokens', 'opace-ai-prompt-library-api-hub') . '</th>';
            $html .= '<th>' . esc_html__('Output Tokens', 'opace-ai-prompt-library-api-hub') . '</th>';
            $html .= '<th>' . esc_html__('Total Tokens', 'opace-ai-prompt-library-api-hub') . '</th>';
            $html .= '<th>' . esc_html__('Cost', 'opace-ai-prompt-library-api-hub') . '</th>';
            $html .= '<th>' . esc_html__('Errors', 'opace-ai-prompt-library-api-hub') . '</th>';
            $html .= '<th>' . esc_html__('Last Used', 'opace-ai-prompt-library-api-hub') . '</th>';
            $html .= '</tr></thead>';
            $html .= '<tbody>';

            $provider_names = array(
                'openai' => 'OpenAI',
                'anthropic' => 'Anthropic',
                'gemini' => 'Gemini'
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
                if ('unavailable' === ($model_stats['cost_status'] ?? 'unavailable')) {
                    $html .= '<td><strong>' . esc_html__('Cost unavailable', 'opace-ai-prompt-library-api-hub') . '</strong></td>';
                } else {
                    $source = 'litellm' === ($model_stats['pricing_source'] ?? '') ? __('live catalogue', 'opace-ai-prompt-library-api-hub') : __('bundled fallback', 'opace-ai-prompt-library-api-hub');
                    $html .= '<td>$' . number_format($model_stats['estimated_cost'] ?? ($model_stats['total_cost'] ?? 0), 4) . '<br><small>' . esc_html($source) . '</small></td>';
                }
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
