<?php
/**
 * AI-Stats AJAX Class
 *
 * Handles AJAX requests
 *
 * @package AI_Stats
 * @version 0.2.3
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * AJAX handler class
 */
class AI_Stats_Ajax {
    
    /**
     * Singleton instance
     * 
     * @var AI_Stats_Ajax
     */
    private static $instance = null;
    
    /**
     * Get instance
     * 
     * @return AI_Stats_Ajax
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
        // Legacy handlers
        add_action('wp_ajax_ai_stats_generate_content', array($this, 'generate_content'));
        add_action('wp_ajax_ai_stats_switch_mode', array($this, 'switch_mode'));
        add_action('wp_ajax_ai_stats_preview_content', array($this, 'preview_content'));
        add_action('wp_ajax_ai_stats_delete_content', array($this, 'delete_content'));

        // New manual workflow handlers
        add_action('wp_ajax_ai_stats_fetch_candidates', array($this, 'fetch_candidates'));
        add_action('wp_ajax_ai_stats_generate_draft', array($this, 'generate_draft'));
        add_action('wp_ajax_ai_stats_publish', array($this, 'publish_module'));

        // Debug handlers
        add_action('wp_ajax_ai_stats_debug_pipeline', array($this, 'debug_pipeline'));
        add_action('wp_ajax_ai_stats_test_source', array($this, 'test_source'));
        add_action('wp_ajax_ai_stats_clear_cache', array($this, 'clear_cache'));
    }
    
    /**
     * Generate content AJAX handler
     * 
     * @return void
     */
    public function generate_content() {
        check_ajax_referer('ai_stats_admin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'ai-stats')));
        }
        
        $mode = isset($_POST['mode']) ? sanitize_text_field($_POST['mode']) : '';
        
        if (empty($mode) || !AI_Stats_Modes::mode_exists($mode)) {
            wp_send_json_error(array('message' => __('Invalid mode', 'ai-stats')));
        }
        
        $generator = AI_Stats_Generator::get_instance();
        $result = $generator->generate_content($mode);
        
        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }
        
        // Deactivate old content
        AI_Stats_Database::deactivate_old_content($mode);
        
        // Store new content
        $content_id = AI_Stats_Database::store_content(
            $mode,
            'module',
            $result['content'],
            $result['metadata'],
            $result['sources']
        );
        
        if (!$content_id) {
            wp_send_json_error(array('message' => __('Failed to store content', 'ai-stats')));
        }
        
        wp_send_json_success(array(
            'content' => $result['content'],
            'content_id' => $content_id,
            'message' => __('Content generated successfully', 'ai-stats'),
        ));
    }
    
    /**
     * Switch mode AJAX handler
     * 
     * @return void
     */
    public function switch_mode() {
        check_ajax_referer('ai_stats_admin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'ai-stats')));
        }
        
        $mode = isset($_POST['mode']) ? sanitize_text_field($_POST['mode']) : '';
        
        if (empty($mode) || !AI_Stats_Modes::mode_exists($mode)) {
            wp_send_json_error(array('message' => __('Invalid mode', 'ai-stats')));
        }
        
        $settings = AI_Stats_Settings::get_instance();
        $settings->set('active_mode', $mode);
        
        wp_send_json_success(array(
            'message' => __('Mode switched successfully', 'ai-stats'),
            'mode' => $mode,
        ));
    }
    
    /**
     * Preview content AJAX handler
     * 
     * @return void
     */
    public function preview_content() {
        check_ajax_referer('ai_stats_admin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'ai-stats')));
        }
        
        $content_id = isset($_POST['content_id']) ? absint($_POST['content_id']) : 0;
        
        if (!$content_id) {
            wp_send_json_error(array('message' => __('Invalid content ID', 'ai-stats')));
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'ai_stats_content';
        $content = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $content_id));
        
        if (!$content) {
            wp_send_json_error(array('message' => __('Content not found', 'ai-stats')));
        }
        
        wp_send_json_success(array(
            'content' => $content->content,
            'metadata' => json_decode($content->metadata, true),
        ));
    }
    
    /**
     * Delete content AJAX handler
     * 
     * @return void
     */
    public function delete_content() {
        check_ajax_referer('ai_stats_admin', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'ai-stats')));
        }
        
        $content_id = isset($_POST['content_id']) ? absint($_POST['content_id']) : 0;
        
        if (!$content_id) {
            wp_send_json_error(array('message' => __('Invalid content ID', 'ai-stats')));
        }
        
        global $wpdb;
        $table = $wpdb->prefix . 'ai_stats_content';
        $result = $wpdb->delete($table, array('id' => $content_id), array('%d'));
        
        if ($result === false) {
            wp_send_json_error(array('message' => __('Failed to delete content', 'ai-stats')));
        }
        
        wp_send_json_success(array('message' => __('Content deleted successfully', 'ai-stats')));
    }

    /**
     * Fetch candidates AJAX handler
     *
     * @return void
     */
    public function fetch_candidates() {
        check_ajax_referer('ai_stats_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'ai-stats')));
        }

        $mode = isset($_POST['mode']) ? sanitize_text_field($_POST['mode']) : 'statistics';
        $keywords = isset($_POST['keywords']) ? array_map('sanitize_text_field', (array) $_POST['keywords']) : array();
        $limit = isset($_POST['limit']) ? absint($_POST['limit']) : 12;
        $use_ai = isset($_POST['use_ai']) ? (bool) $_POST['use_ai'] : false;

        // Get registry to check sources
        $registry = AI_Stats_Source_Registry::get_instance();
        $sources = $registry->get_sources_for_mode($mode);

        // Get adapters instance
        $adapters = AI_Stats_Adapters::get_instance();

        // Fetch candidates
        $candidates = $adapters->fetch_candidates($mode, array(), $keywords, $limit);

        if (is_wp_error($candidates)) {
            wp_send_json_error(array(
                'message' => $candidates->get_error_message(),
                'sources_count' => count($sources),
                'mode' => $mode,
            ));
        }

        // If no candidates, provide debug info
        if (empty($candidates)) {
            wp_send_json_error(array(
                'message' => __('No candidates found. Check Debug page for details.', 'ai-stats'),
                'sources_count' => count($sources),
                'mode' => $mode,
                'keywords' => $keywords,
                'debug_url' => admin_url('admin.php?page=ai-stats-debug&test_mode=' . $mode),
            ));
        }

        // If AI analysis is requested, enhance candidates with AI-extracted insights
        if ($use_ai && class_exists('AI_Core_API')) {
            $candidates = $this->enhance_candidates_with_ai($candidates, $keywords, $mode);
        }

        wp_send_json_success(array(
            'candidates' => $candidates,
            'count' => count($candidates),
            'sources_count' => count($sources),
            'ai_enhanced' => $use_ai,
        ));
    }

    /**
     * Generate draft AJAX handler
     *
     * @return void
     */
    public function generate_draft() {
        check_ajax_referer('ai_stats_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'ai-stats')));
        }

        $mode = isset($_POST['mode']) ? sanitize_text_field($_POST['mode']) : 'statistics';
        $selected_items = isset($_POST['selected_items']) ? (array) $_POST['selected_items'] : array();
        $llm = isset($_POST['llm']) ? sanitize_text_field($_POST['llm']) : 'off';
        $style = isset($_POST['style']) ? sanitize_text_field($_POST['style']) : 'inline';

        if (empty($selected_items)) {
            wp_send_json_error(array('message' => __('No items selected', 'ai-stats')));
        }

        // Generate HTML based on LLM setting
        if ($llm === 'on') {
            $result = $this->generate_with_llm($mode, $selected_items, $style);
        } else {
            $result = $this->generate_without_llm($mode, $selected_items, $style);
        }

        if (is_wp_error($result)) {
            wp_send_json_error(array('message' => $result->get_error_message()));
        }

        wp_send_json_success($result);
    }

    /**
     * Publish module AJAX handler
     *
     * @return void
     */
    public function publish_module() {
        check_ajax_referer('ai_stats_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'ai-stats')));
        }

        $mode = isset($_POST['mode']) ? sanitize_text_field($_POST['mode']) : 'statistics';
        $html = isset($_POST['html']) ? wp_kses_post($_POST['html']) : '';
        $sources_used = isset($_POST['sources_used']) ? (array) $_POST['sources_used'] : array();
        $meta = isset($_POST['meta']) ? (array) $_POST['meta'] : array();

        if (empty($html)) {
            wp_send_json_error(array('message' => __('No content to publish', 'ai-stats')));
        }

        // Deactivate old content for this mode
        AI_Stats_Database::deactivate_old_content($mode);

        // Insert new module
        $meta['sources_used'] = $sources_used;
        $id = AI_Stats_Database::insert_module($mode, $html, $meta);

        if (!$id) {
            wp_send_json_error(array('message' => __('Failed to publish module', 'ai-stats')));
        }

        wp_send_json_success(array(
            'message' => __('Module published successfully', 'ai-stats'),
            'id' => $id,
        ));
    }

    /**
     * Generate content with LLM
     *
     * @param string $mode Mode
     * @param array $items Selected items
     * @param string $style Output style
     * @return array|WP_Error Result or error
     */
    private function generate_with_llm($mode, $items, $style) {
        // Check if AI-Core is available
        if (!class_exists('AI_Core_API')) {
            return new WP_Error('no_ai_core', __('AI-Core is not available', 'ai-stats'));
        }

        $api = AI_Core_API::get_instance();

        // Build prompt
        $system_prompt = "You are generating 2–3 short evidence-based bullets for a UK digital agency page. Each bullet must be fact-specific and end with [Source: {name}]. No hype. UK English.";

        $user_prompt = "Mode: {$mode}\n";
        $user_prompt .= "Audience: SME owners and marketing managers in the UK.\n";
        $user_prompt .= "Tone: concise, factual, helpful.\n\n";
        $user_prompt .= "Selected items (JSON):\n";
        $user_prompt .= wp_json_encode($items, JSON_PRETTY_PRINT) . "\n\n";
        $user_prompt .= "Write 2–3 bullets max (≤22 words each). Use different angles. Never invent numbers.\n";
        $user_prompt .= "If an item is weak or duplicate, drop it.";

        $messages = array(
            array('role' => 'system', 'content' => $system_prompt),
            array('role' => 'user', 'content' => $user_prompt),
        );

        $options = array(
            'max_tokens' => 500,
            'temperature' => 0.7,
        );

        // Get preferred model from settings
        $settings = get_option('ai_stats_settings', array());
        $provider = $settings['preferred_provider'] ?? 'openai';
        $model = $this->get_model_for_provider($provider);

        $usage_context = array('tool' => 'ai-stats', 'mode' => $mode);
        $response = $api->send_text_request($model, $messages, $options, $usage_context);

        if (is_wp_error($response)) {
            return $response;
        }

        // Extract content
        $content = '';
        if (isset($response['choices'][0]['message']['content'])) {
            $content = $response['choices'][0]['message']['content'];
        } elseif (class_exists('AICore\\AICore')) {
            $content = \AICore\AICore::extractContent($response);
        }

        if (empty($content)) {
            return new WP_Error('empty_response', __('AI returned empty response', 'ai-stats'));
        }

        // Format as HTML
        $html = $this->format_content($content, $style);

        // Extract sources
        $sources_used = array();
        foreach ($items as $item) {
            $sources_used[] = array(
                'name' => $item['source'],
                'url' => $item['url'],
            );
        }

        // Get token usage
        $tokens = 0;
        if (isset($response['usage']['total_tokens'])) {
            $tokens = $response['usage']['total_tokens'];
        }

        return array(
            'html' => $html,
            'sources_used' => array_values(array_unique($sources_used, SORT_REGULAR)),
            'llm' => 'on',
            'model' => $model,
            'tokens' => $tokens,
            'items' => $items,
        );
    }

    /**
     * Generate content without LLM (raw bullets)
     *
     * @param string $mode Mode
     * @param array $items Selected items
     * @param string $style Output style
     * @return array Result
     */
    private function generate_without_llm($mode, $items, $style) {
        $html = '<ul class="ai-stats-inline">';

        foreach ($items as $item) {
            $html .= '<li>';
            $html .= '<strong>' . esc_html($item['title']) . '</strong> ';
            $html .= esc_html(substr($item['blurb_seed'], 0, 150));
            $html .= ' <em>[' . esc_html($item['source']) . ']</em> ';
            $html .= '<a href="' . esc_url($item['url']) . '" target="_blank">Source</a>';
            $html .= '</li>';
        }

        $html .= '</ul>';

        // Extract sources
        $sources_used = array();
        foreach ($items as $item) {
            $sources_used[] = array(
                'name' => $item['source'],
                'url' => $item['url'],
            );
        }

        return array(
            'html' => $html,
            'sources_used' => array_values(array_unique($sources_used, SORT_REGULAR)),
            'llm' => 'off',
            'model' => '',
            'tokens' => 0,
            'items' => $items,
        );
    }

    /**
     * Format content as HTML
     *
     * @param string $content Content text
     * @param string $style Style (inline, cards, list)
     * @return string HTML
     */
    private function format_content($content, $style) {
        // Convert bullet points to HTML list
        $lines = explode("\n", $content);
        $html = '<ul class="ai-stats-' . esc_attr($style) . '">';

        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                continue;
            }

            // Remove leading bullet characters
            $line = preg_replace('/^[-*•]\s*/', '', $line);

            if (!empty($line)) {
                $html .= '<li>' . wp_kses_post($line) . '</li>';
            }
        }

        $html .= '</ul>';

        return $html;
    }

    /**
     * Get model for provider
     *
     * @param string $provider Provider name
     * @return string Model name
     */
    private function get_model_for_provider($provider) {
        $models = array(
            'openai' => 'gpt-4o-mini',
            'anthropic' => 'claude-sonnet-4-20250514',
            'gemini' => 'gemini-2.0-flash-exp',
            'grok' => 'grok-beta',
        );

        return $models[$provider] ?? 'gpt-4o-mini';
    }

    /**
     * Enhance candidates with AI analysis
     *
     * @param array $candidates Candidates to enhance
     * @param array $keywords Keywords for context
     * @param string $mode Mode for context
     * @return array Enhanced candidates
     */
    private function enhance_candidates_with_ai($candidates, $keywords, $mode) {
        if (empty($candidates) || !class_exists('AI_Core_API')) {
            return $candidates;
        }

        $api = AI_Core_API::get_instance();

        // Get default model from AI-Core settings (respects user's preference)
        $ai_core_settings = get_option('ai_core_settings', array());
        $model = $ai_core_settings['default_model'] ?? 'gpt-4o-mini';

        // Log AI usage for transparency
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf('AI-Stats: Using model %s for content analysis', $model));
        }

        // Process candidates in batches to extract relevant statistics
        $enhanced = array();
        $processed_count = 0;

        foreach ($candidates as $candidate) {
            // Skip if no content to analyze
            if (empty($candidate['full_content']) && empty($candidate['blurb_seed'])) {
                $enhanced[] = $candidate;
                continue;
            }

            // Limit AI processing to first 5 candidates to control costs
            if ($processed_count >= 5) {
                $enhanced[] = $candidate;
                continue;
            }

            $content_to_analyze = $candidate['full_content'] ?? $candidate['blurb_seed'];

            // Limit content length to reduce token usage
            $content_to_analyze = substr($content_to_analyze, 0, 2000);

            // Build AI prompt to extract ONLY numerical statistics
            $system_prompt = "You are a statistics extraction specialist. Extract ONLY quantifiable data: numbers, percentages, monetary values, dates, and measurements. Ignore opinions, predictions, or qualitative statements. Return only factual statistics with their context.";

            $user_prompt = "Source: {$candidate['source']}\n";
            $user_prompt .= "Keywords: " . implode(', ', $keywords) . "\n\n";
            $user_prompt .= "Content:\n{$content_to_analyze}\n\n";
            $user_prompt .= "Task: Extract 2-3 NUMERICAL statistics related to: " . implode(', ', $keywords) . "\n";
            $user_prompt .= "Format: [Number/Percentage] - [Brief context]\n";
            $user_prompt .= "Example: 45% - UK businesses increased digital marketing spend in 2024\n";
            $user_prompt .= "ONLY include statements with actual numbers. If no statistics found, return 'No quantifiable data found'.";

            $messages = array(
                array('role' => 'system', 'content' => $system_prompt),
                array('role' => 'user', 'content' => $user_prompt),
            );

            $options = array(
                'max_tokens' => 200, // Reduced to control costs
                'temperature' => 0.1, // Very low temperature for factual extraction
            );

            $usage_context = array('tool' => 'ai-stats', 'mode' => $mode, 'action' => 'extract');
            $response = $api->send_text_request($model, $messages, $options, $usage_context);

            if (!is_wp_error($response)) {
                // Extract AI-generated insights
                $extracted = '';
                if (isset($response['choices'][0]['message']['content'])) {
                    $extracted = $response['choices'][0]['message']['content'];
                } elseif (class_exists('AICore\\AICore')) {
                    $extracted = \AICore\AICore::extractContent($response);
                }

                // Only use AI extraction if it contains actual numbers
                if (!empty($extracted) && preg_match('/\d+/', $extracted) && stripos($extracted, 'no quantifiable') === false) {
                    $candidate['ai_extracted'] = $extracted;
                    $candidate['blurb_seed'] = $extracted; // Replace blurb with AI-extracted content
                    $candidate['confidence'] = 0.95; // Higher confidence for AI-verified content
                    $candidate['ai_model_used'] = $model; // Track which model was used
                    $processed_count++;

                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log(sprintf('AI-Stats: Extracted statistics: %s', substr($extracted, 0, 100)));
                    }
                } else {
                    // Mark as no useful data found
                    $candidate['ai_extracted'] = 'No quantifiable statistics found in content';
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log(sprintf('AI-Stats: No statistics found in %s', $candidate['title']));
                    }
                }
            } else {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log(sprintf('AI-Stats: AI extraction failed: %s', $response->get_error_message()));
                }
            }

            $enhanced[] = $candidate;

            // Add small delay to avoid rate limiting
            usleep(100000); // 100ms delay
        }

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf('AI-Stats: Processed %d candidates with AI, enhanced %d', count($candidates), $processed_count));
        }

        return $enhanced;
    }

    /**
     * Debug pipeline AJAX handler
     * Shows full Fetch → Normalise → Filter → Rank → Cache pipeline
     *
     * @return void
     */
    public function debug_pipeline() {
        check_ajax_referer('ai_stats_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'ai-stats')));
        }

        $mode = isset($_POST['mode']) ? sanitize_text_field($_POST['mode']) : 'statistics';
        $keywords = isset($_POST['keywords']) ? array_map('sanitize_text_field', (array) $_POST['keywords']) : array();
        $limit = isset($_POST['limit']) ? absint($_POST['limit']) : 12;

        // Get adapters instance
        $adapters = AI_Stats_Adapters::get_instance();

        // Fetch with full pipeline debug
        $pipeline = $adapters->fetch_candidates_debug($mode, $keywords, $limit);

        wp_send_json_success($pipeline);
    }

    /**
     * Test single source AJAX handler
     *
     * @return void
     */
    public function test_source() {
        check_ajax_referer('ai_stats_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'ai-stats')));
        }

        $mode = isset($_POST['mode']) ? sanitize_text_field($_POST['mode']) : '';
        $source_index = isset($_POST['source_index']) ? absint($_POST['source_index']) : -1;

        if (empty($mode) || $source_index < 0) {
            wp_send_json_error(array('message' => __('Invalid parameters', 'ai-stats')));
        }

        // Get source
        $registry = AI_Stats_Source_Registry::get_instance();
        $sources = $registry->get_sources_for_mode($mode);

        if (!isset($sources[$source_index])) {
            wp_send_json_error(array('message' => __('Source not found', 'ai-stats')));
        }

        $source = $sources[$source_index];

        // Get adapters instance
        $adapters = AI_Stats_Adapters::get_instance();

        // Use reflection to call private method
        $reflection = new ReflectionClass($adapters);
        $method = $reflection->getMethod('fetch_from_source');
        $method->setAccessible(true);

        $start_time = microtime(true);
        $candidates = $method->invoke($adapters, $source);
        $fetch_time = round((microtime(true) - $start_time) * 1000, 2);

        $result = array(
            'source' => $source,
            'fetch_time' => $fetch_time,
            'status' => 'success',
            'candidates_count' => 0,
            'candidates' => array(),
            'error' => null,
        );

        if (is_wp_error($candidates)) {
            $result['status'] = 'error';
            $result['error'] = $candidates->get_error_message();
        } elseif (empty($candidates)) {
            $result['status'] = 'empty';
        } else {
            $result['candidates_count'] = count($candidates);
            $result['candidates'] = $candidates;
        }

        wp_send_json_success($result);
    }

    /**
     * Clear cache AJAX handler
     *
     * @return void
     */
    public function clear_cache() {
        check_ajax_referer('ai_stats_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'ai-stats')));
        }

        global $wpdb;

        // Delete all AI-Stats transients
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_ai_stats_%' OR option_name LIKE '_transient_timeout_ai_stats_%'");

        wp_send_json_success(array('message' => __('Cache cleared successfully', 'ai-stats')));
    }
}

