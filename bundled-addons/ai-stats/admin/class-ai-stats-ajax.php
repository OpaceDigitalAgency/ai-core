<?php
/**
 * AI-Stats AJAX Class
 *
 * Handles AJAX requests
 *
 * @package AI_Stats
 * @version 0.7.2
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
        add_action('wp_ajax_ai_stats_refresh_registry', array($this, 'refresh_registry'));
        add_action('wp_ajax_ai_stats_debug_generation', array($this, 'debug_generation_test'));

        // Settings test handlers
        add_action('wp_ajax_ai_stats_test_bigquery', array($this, 'test_bigquery'));
        add_action('wp_ajax_ai_stats_get_models', array($this, 'get_models'));
        add_action('wp_ajax_ai_stats_test_prompt', array($this, 'test_prompt'));
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

        // Build prompt (strict numerical bullets)
        $system_prompt = "You generate 2–3 SHORT, numerical, evidence-based bullets for a UK digital agency page.\nRules:\n- EACH bullet MUST include at least one numeral (%, £, $, figure)\n- STRICT format: [NUMBER or %] - [concise context] [Source: {name}]\n- No generic summaries, no filler, UK English, ≤22 words per bullet.";

        $user_prompt = "Mode: {$mode}\n";
        $user_prompt .= "Audience: SME owners and marketing managers in the UK.\n";
        $user_prompt .= "Tone: concise, factual, helpful.\n\n";
        $user_prompt .= "Selected items (JSON):\n";
        $user_prompt .= wp_json_encode($items, JSON_PRETTY_PRINT) . "\n\n";
        $user_prompt .= "Write 2–3 bullets. STRICT rules:\n- Only include bullets that contain explicit numbers or percentages.\n- Do NOT summarise articles.\n- Each bullet MUST have a dash after the number, e.g. '67% - ...'.\n- Exclude dates-only, page numbers or IDs.\n- End each bullet with [Source: {name}].";

        $messages = array(
            array('role' => 'system', 'content' => $system_prompt),
            array('role' => 'user', 'content' => $user_prompt),
        );

        // Get preferred model from settings (respect user choice; fallback to provider default)
        $settings = get_option('ai_stats_settings', array());
        $provider = $settings['preferred_provider'] ?? (method_exists($api, 'get_default_provider') ? $api->get_default_provider() : 'openai');
        $preferred_model = $settings['preferred_model'] ?? '';
        $provider_config = $this->resolve_provider_configuration($provider, $preferred_model);

        $model = $provider_config['model'];
        if (empty($model)) {
            return new WP_Error('model_unavailable', __('No AI model available for the selected provider.', 'ai-stats'));
        }

        $options = $provider_config['options'];
        if (!isset($options['temperature'])) {
            $options['temperature'] = 0.2;
        }
        if (!isset($options['max_tokens'])) {
            $options['max_tokens'] = 300;
        }

        $usage_context = array('tool' => 'ai-stats', 'mode' => $mode);
        $response = $api->send_text_request($model, $messages, $options, $usage_context);

        if (is_wp_error($response)) {
            return $response;
        }

        // Extract content via normaliser first (supports Chat and Responses APIs)
        $content = '';
        if (class_exists('AICore\\AICore')) {
            $content = \AICore\AICore::extractContent($response);
        } elseif (isset($response['choices'][0]['message']['content'])) {
            $content = $response['choices'][0]['message']['content'];
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
            'provider' => $provider,
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

            // Only include lines that contain at least one digit to ensure numerical stats
            if (!empty($line) && preg_match('/\d/', $line)) {
                $html .= '<li>' . wp_kses_post($line) . '</li>';
            }
        }

        $html .= '</ul>';

        return $html;
    }

    /**
     * Resolve provider configuration using AI-Core defaults and settings.
     *
     * @param string $provider Provider key
     * @param string $preferred_model Optional preferred model id
     * @return array {
     *     @type string $model Selected model id
     *     @type array  $options Request options
     *     @type array  $available_models Available models for provider
     * }
     */
    private function resolve_provider_configuration($provider, $preferred_model = '') {
        $fallbacks = array(
            'openai' => 'gpt-4o-mini',
            'anthropic' => 'claude-sonnet-4-20250514',
            'gemini' => 'gemini-2.0-flash-exp',
            'grok' => 'grok-beta',
        );

        $available_models = array();
        $model = $preferred_model;
        $options = array();

        if (class_exists('AI_Core_API')) {
            $api = AI_Core_API::get_instance();

            if (method_exists($api, 'get_provider_settings')) {
                $provider_settings = $api->get_provider_settings($provider);
                $available_models = $provider_settings['models'] ?? array();
                if (empty($model)) {
                    $model = $provider_settings['model'] ?? '';
                }
                $options = $provider_settings['options'] ?? array();
            } else {
                $available_models = method_exists($api, 'get_available_models')
                    ? $api->get_available_models($provider)
                    : array();

                if (empty($model) && method_exists($api, 'get_default_model_for_provider')) {
                    $model = $api->get_default_model_for_provider($provider);
                }

                if (method_exists($api, 'get_provider_options')) {
                    $options = $api->get_provider_options($provider, $model);
                }
            }
        }

        if (empty($model)) {
            if (!empty($available_models)) {
                $model = $available_models[0];
            } elseif (isset($fallbacks[$provider])) {
                $model = $fallbacks[$provider];
            } else {
                $model = 'gpt-4o-mini';
            }
        }

        if (!in_array($model, $available_models, true)) {
            $available_models[] = $model;
        }

        if (empty($options) && class_exists('\\AICore\\Registry\\ModelRegistry') && !empty($model)) {
            $schema = \AICore\Registry\ModelRegistry::getParameterSchema($model);
            foreach ($schema as $key => $meta) {
                if (isset($meta['default'])) {
                    $options[$key] = $meta['default'];
                }
            }
        }

        return array(
            'model' => $model,
            'options' => $options,
            'available_models' => array_values(array_filter($available_models)),
        );
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

        // Get model from AI-Stats settings (user-selected provider and model)
        $ai_stats_settings = get_option('ai_stats_settings', array());
        $provider = $ai_stats_settings['preferred_provider'] ?? 'openai';
        $preferred_model = $ai_stats_settings['preferred_model'] ?? '';
        $provider_config = $this->resolve_provider_configuration($provider, $preferred_model);
        $model = $provider_config['model'];

        if (empty($model)) {
            return $candidates;
        }

        // Log AI usage for transparency
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf('AI-Stats: Using provider %s with model %s for content analysis', $provider, $model));
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

            // TWO-STAGE EXTRACTION: Stage 1 - Regex pre-filter to find sentences with numbers
            $sentences_with_numbers = $this->extract_sentences_with_numbers($content_to_analyze);

            // If no sentences with numbers found, skip AI processing
            if (empty($sentences_with_numbers)) {
                $candidate['ai_extracted'] = 'No numerical data found in content';
                $enhanced[] = $candidate;
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log(sprintf('AI-Stats: No numbers found in %s (skipped AI)', $candidate['title']));
                }
                continue;
            }

            // Limit to first 1000 chars of number-containing sentences to reduce tokens
            $filtered_content = substr($sentences_with_numbers, 0, 1000);

            // TWO-STAGE EXTRACTION: Stage 2 - AI validates and formats only the pre-filtered content
            $system_prompt = "You are a statistics extraction specialist. You will receive text that already contains numbers. Your job is to:\n1. Identify which numbers are actual STATISTICS (not dates, page numbers, or irrelevant figures)\n2. Format each statistic as: [NUMBER/PERCENTAGE] - [BRIEF CONTEXT]\n3. Return ONLY 2-3 most relevant statistics\n4. If none are actual statistics, return 'No quantifiable statistics found'";

            $user_prompt = "Source: {$candidate['source']}\n";
            $user_prompt .= "Keywords: " . implode(', ', $keywords) . "\n\n";
            $user_prompt .= "Pre-filtered content (already contains numbers):\n{$filtered_content}\n\n";
            $user_prompt .= "Extract 2-3 STATISTICS related to: " . implode(', ', $keywords) . "\n";
            $user_prompt .= "Format each as: [NUMBER] - [CONTEXT]\n";
            $user_prompt .= "Example: 67% - of UK SMEs increased digital marketing budgets in 2024\n";
            $user_prompt .= "CRITICAL: Only include actual statistics with business/industry relevance. Ignore dates, page numbers, article IDs.";

            $messages = array(
                array('role' => 'system', 'content' => $system_prompt),
                array('role' => 'user', 'content' => $user_prompt),
            );

            $options = array(
                'max_tokens' => 150, // Further reduced since we're only validating pre-filtered content
                'temperature' => 0.1, // Very low temperature for factual extraction
            );

            $usage_context = array('tool' => 'ai-stats', 'mode' => $mode, 'action' => 'extract');
            $response = $api->send_text_request($model, $messages, $options, $usage_context);

            if (!is_wp_error($response)) {
                // Extract AI-generated insights (normaliser first)
                $extracted = '';
                if (class_exists('AICore\\AICore')) {
                    $extracted = \AICore\AICore::extractContent($response);
                } elseif (isset($response['choices'][0]['message']['content'])) {
                    $extracted = $response['choices'][0]['message']['content'];
                }

                // Strict validation: Must contain numbers AND proper format
                if (!empty($extracted) &&
                    stripos($extracted, 'no quantifiable') === false &&
                    stripos($extracted, 'no statistics') === false &&
                    $this->validate_statistics_format($extracted)) {

                    $candidate['ai_extracted'] = $extracted;
                    $candidate['blurb_seed'] = $extracted; // Replace blurb with AI-extracted content
                    $candidate['confidence'] = 0.95; // Higher confidence for AI-verified content
                    $candidate['ai_model_used'] = $model; // Track which model was used
                    $candidate['ai_provider_used'] = $provider; // Track which provider was used
                    $processed_count++;

                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log(sprintf('AI-Stats: Extracted statistics: %s', substr($extracted, 0, 100)));
                    }
                } else {
                    // Mark as no useful data found
                    $candidate['ai_extracted'] = 'No quantifiable statistics found in content';
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log(sprintf('AI-Stats: No valid statistics in %s (failed validation)', $candidate['title']));
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

        // Check if AI-Core is available and compatible
        if (!function_exists('ai_core') || !class_exists('AI_Core_API')) {
            wp_send_json_error(array(
                'code' => 'ai_core_missing',
                'message' => __('AI-Core plugin is not active or not installed. Please activate AI-Core first.', 'ai-stats')
            ));
        }

        // Get adapters instance
        $adapters = AI_Stats_Adapters::get_instance();

        // Fetch with full pipeline debug
        $pipeline = $adapters->fetch_candidates_debug($mode, $keywords, $limit);

        // Check for errors
        if (is_wp_error($pipeline)) {
            wp_send_json_error(array(
                'code' => $pipeline->get_error_code(),
                'message' => $pipeline->get_error_message()
            ));
        }

        // Include AI generation configuration
        if (class_exists('AI_Stats_Generator')) {
            $generator = AI_Stats_Generator::get_instance();
            if (method_exists($generator, 'prepare_generation_config')) {
                $generation_config = $generator->prepare_generation_config($mode, array(
                    'keywords' => $keywords,
                    'limit' => $limit,
                    'debug' => true,
                ));

                if (!is_wp_error($generation_config)) {
                    $pipeline['generation'] = $generation_config;
                }
            }
        }

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

    /**
     * Test BigQuery connection AJAX handler
     *
     * @return void
     */
    public function test_bigquery() {
        try {
            check_ajax_referer('ai_stats_admin', 'nonce');

            if (!current_user_can('manage_options')) {
                wp_send_json_error(array('message' => __('Permission denied', 'ai-stats')));
            }

            $project_id = isset($_POST['project_id']) ? sanitize_text_field($_POST['project_id']) : '';
            $service_account_json = isset($_POST['service_account_json']) ? wp_unslash($_POST['service_account_json']) : '';
            $region = isset($_POST['region']) ? sanitize_text_field($_POST['region']) : 'GB';

            if (empty($project_id) || empty($service_account_json)) {
                wp_send_json_error(array('message' => __('Project ID and Service Account JSON are required', 'ai-stats')));
            }

            // Validate JSON
            $credentials = json_decode($service_account_json, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                wp_send_json_error(array('message' => __('Invalid JSON format: ' . json_last_error_msg(), 'ai-stats')));
            }

            // Ensure adapters class is loaded
            if (!class_exists('AI_Stats_Adapters')) {
                require_once AI_STATS_PLUGIN_DIR . 'includes/class-ai-stats-adapters.php';
            }

            // Create a temporary source configuration
            $test_source = array(
                'type' => 'API',
                'name' => 'BigQuery Google Trends',
                'url' => 'bigquery://bigquery-public-data.google_trends.top_terms',
                'tags' => array('google_trends', 'test')
            );

            // Temporarily set the credentials in settings
            $original_settings = get_option('ai_stats_settings', array());
            $test_settings = array_merge($original_settings, array(
                'gcp_project_id' => $project_id,
                'gcp_service_account_json' => $service_account_json,
                'bigquery_region' => $region,
                'enable_bigquery_trends' => true
            ));
            update_option('ai_stats_settings', $test_settings);

            // Try to fetch data
            $adapters = AI_Stats_Adapters::get_instance();
            $candidates = $adapters->fetch_from_source($test_source);

            // Restore original settings
            update_option('ai_stats_settings', $original_settings);

            if (is_wp_error($candidates)) {
                wp_send_json_error(array(
                    'message' => $candidates->get_error_message()
                ));
            }

            if (empty($candidates)) {
                wp_send_json_error(array(
                    'message' => __('No data returned from BigQuery. Check your credentials and permissions.', 'ai-stats')
                ));
            }

            // Success - return sample data
            $sample_trend = !empty($candidates[0]['metadata']['query']) ? $candidates[0]['metadata']['query'] : '';

            wp_send_json_success(array(
                'message' => __('Connection successful!', 'ai-stats'),
                'trends_count' => count($candidates),
                'region' => $region,
                'sample_trend' => $sample_trend
            ));
        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => 'Error: ' . $e->getMessage()
            ));
        }
    }

    /**
     * Refresh source registry AJAX handler
     *
     * @return void
     */
    public function refresh_registry() {
        check_ajax_referer('ai_stats_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'ai-stats')));
        }

        // Delete the cached registry
        delete_option('ai_stats_source_registry');

        // Clear all transient caches
        global $wpdb;
        $transients_deleted = $wpdb->query(
            "DELETE FROM {$wpdb->options}
             WHERE option_name LIKE '_transient_ai_stats_%'
             OR option_name LIKE '_transient_timeout_ai_stats_%'"
        );

        // Force reload the registry
        $registry = AI_Stats_Source_Registry::get_instance();
        $registry->refresh_registry();

        // Get the new source counts
        $all_sources = $registry->get_all_sources();
        $total_sources = 0;
        $mode_counts = array();

        foreach ($all_sources as $mode_key => $mode_data) {
            $count = count($mode_data['sources']);
            $total_sources += $count;
            $mode_counts[$mode_key] = array(
                'name' => $mode_data['mode'],
                'count' => $count
            );
        }

        wp_send_json_success(array(
            'message' => sprintf(
                __('Source registry refreshed! Loaded %d sources across %d modes. Cleared %d cached items.', 'ai-stats'),
                $total_sources,
                count($all_sources),
                $transients_deleted
            ),
            'total_sources' => $total_sources,
            'mode_counts' => $mode_counts,
            'transients_cleared' => $transients_deleted
        ));
    }

    /**
     * Extract sentences containing numbers (Stage 1 of two-stage extraction)
     *
     * @param string $text Text to analyze
     * @return string Sentences containing numbers
     */
    private function extract_sentences_with_numbers($text) {
        if (empty($text)) {
            return '';
        }

        // Limit text length for processing
        $text = substr($text, 0, 5000);

        $stats_sentences = array();

        // Split into sentences
        $sentences = preg_split('/[.!?]+/', $text);

        foreach ($sentences as $sentence) {
            $sentence = trim($sentence);

            if (empty($sentence)) {
                continue;
            }

            // Look for percentages (most common in statistics)
            if (preg_match('/\d+(?:\.\d+)?%/', $sentence)) {
                $stats_sentences[] = $sentence;
                continue;
            }

            // Look for large numbers with commas (e.g., 1,000 or 1,000,000)
            if (preg_match('/\d{1,3}(?:,\d{3})+/', $sentence)) {
                $stats_sentences[] = $sentence;
                continue;
            }

            // Look for monetary values (£, $, €)
            if (preg_match('/[£$€]\s*\d+(?:,\d{3})*(?:\.\d{2})?(?:\s*(?:million|billion|thousand|k|m|bn))?/i', $sentence)) {
                $stats_sentences[] = $sentence;
                continue;
            }

            // Look for growth/change patterns with numbers
            if (preg_match('/(increase|decrease|growth|decline|rise|fall|grew|dropped|up|down)\s+(?:by\s+)?(\d+)/i', $sentence)) {
                $stats_sentences[] = $sentence;
                continue;
            }

            // Look for "X in Y" patterns (e.g., "3 in 4 businesses")
            if (preg_match('/\d+\s+(?:in|out of)\s+\d+/i', $sentence)) {
                $stats_sentences[] = $sentence;
                continue;
            }

            // Look for year-over-year comparisons
            if (preg_match('/\d+%?\s+(?:year-over-year|YoY|compared to|vs\.?)/i', $sentence)) {
                $stats_sentences[] = $sentence;
                continue;
            }

            // Limit to 10 sentences to control token usage
            if (count($stats_sentences) >= 10) {
                break;
            }
        }

        return implode('. ', $stats_sentences);
    }

    /**
     * Validate that extracted content contains properly formatted statistics
     *
     * @param string $content Content to validate
     * @return bool True if valid statistics format
     */
    private function validate_statistics_format($content) {
        if (empty($content)) {
            return false;
        }

        // Must contain at least one number
        if (!preg_match('/\d+/', $content)) {
            return false;
        }

        // Check for proper format: [NUMBER] - [CONTEXT]
        // Look for patterns like "67% -" or "1,234 -" or "£5m -"
        $has_proper_format = preg_match('/(?:\d+(?:\.\d+)?%|\d{1,3}(?:,\d{3})+|[£$€]\s*\d+|\d+)\s*-\s*\w+/i', $content);

        // Alternative: Check if it's a list of statistics (bullet points or numbered)
        $has_list_format = preg_match('/^[\s]*[-•*\d]+[\s]*\d+/m', $content);

        return $has_proper_format || $has_list_format;
    }

    /**
     * Run AI generation test (AJAX handler for debug view)
     *
     * @return void
     */
    public function debug_generation_test() {
        check_ajax_referer('ai_stats_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'ai-stats')));
        }

        if (!class_exists('AI_Core_API')) {
            wp_send_json_error(array('message' => __('AI-Core is not available', 'ai-stats')));
        }

        $mode = isset($_POST['mode']) ? sanitize_text_field($_POST['mode']) : 'statistics';
        $provider = isset($_POST['provider']) ? sanitize_text_field($_POST['provider']) : '';
        $model = isset($_POST['model']) ? sanitize_text_field($_POST['model']) : '';

        $options = array();
        if (isset($_POST['options']) && is_array($_POST['options'])) {
            $options = array_map(array($this, 'sanitize_ai_option'), wp_unslash($_POST['options']));
        }

        $system_prompt = isset($_POST['system_prompt']) ? wp_unslash($_POST['system_prompt']) : '';
        $user_prompt = isset($_POST['user_prompt']) ? wp_unslash($_POST['user_prompt']) : '';

        $generator = AI_Stats_Generator::get_instance();

        $context = array();
        if (!empty($provider)) {
            $context['provider'] = $provider;
        }
        if (!empty($model)) {
            $context['model'] = $model;
        }

        $config = $generator->prepare_generation_config($mode, $context);
        if (is_wp_error($config)) {
            wp_send_json_error(array('message' => $config->get_error_message()));
        }

        // Override prompts if provided (allow manual tweaks from UI)
        if (!empty($system_prompt)) {
            $config['messages'][0]['content'] = $system_prompt;
            $config['system_prompt'] = $system_prompt;
        }

        if (!empty($user_prompt)) {
            if (isset($config['messages'][1])) {
                $config['messages'][1]['content'] = $user_prompt;
            } else {
                $config['messages'][] = array('role' => 'user', 'content' => $user_prompt);
            }
            $config['user_prompt'] = $user_prompt;
        }

        if (!empty($options)) {
            $filtered_options = array();
            foreach ($options as $key => $value) {
                if ($value === '' || $value === null) {
                    continue;
                }
                $filtered_options[$key] = $value;
            }

            if (!empty($filtered_options)) {
                $config['options'] = array_merge($config['options'], $filtered_options);
            }
        }

        $api = AI_Core_API::get_instance();
        $response = $api->send_text_request(
            $config['model'],
            $config['messages'],
            $config['options'],
            array('tool' => 'ai-stats-debug', 'mode' => $mode)
        );

        if (is_wp_error($response)) {
            wp_send_json_error(array('message' => $response->get_error_message()));
        }

        $content = '';
        if (class_exists('AICore\\AICore')) {
            $content = \AICore\AICore::extractContent($response);
        } elseif (isset($response['choices'][0]['message']['content'])) {
            $content = $response['choices'][0]['message']['content'];
        }

        $tokens = isset($response['usage']['total_tokens']) ? (int) $response['usage']['total_tokens'] : 0;

        wp_send_json_success(array(
            'content' => $content,
            'provider' => $config['provider'],
            'model' => $config['model'],
            'options' => $config['options'],
            'tokens' => $tokens,
        ));
    }

    /**
     * Sanitise AI option values from debug UI.
     *
     * @param mixed $value Raw value
     * @return mixed Sanitised value
     */
    private function sanitize_ai_option($value) {
        if (is_array($value)) {
            return array_map(array($this, 'sanitize_ai_option'), $value);
        }

        if (is_numeric($value)) {
            return strpos((string) $value, '.') !== false ? (float) $value : (int) $value;
        }

        return sanitize_text_field($value);
    }

    /**
     * Get available models for a provider (AJAX handler)
     *
     * @return void
     */
    public function get_models() {
        check_ajax_referer('ai_stats_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied', 'ai-stats')));
        }

        $provider = isset($_POST['provider']) ? sanitize_text_field($_POST['provider']) : '';

        if (empty($provider)) {
            wp_send_json_error(array('message' => __('Provider not specified', 'ai-stats')));
        }

        // Get models from AI-Core
        if (!class_exists('AI_Core_API')) {
            wp_send_json_error(array('message' => __('AI-Core not available', 'ai-stats')));
        }

        $api = AI_Core_API::get_instance();
        $models = $api->get_available_models($provider);

        $default_model = '';
        if (method_exists($api, 'get_provider_settings')) {
            $provider_settings = $api->get_provider_settings($provider);
            $default_model = $provider_settings['model'] ?? '';
            if (empty($models) && !empty($provider_settings['models'])) {
                $models = $provider_settings['models'];
            }
        }

        if (empty($default_model)) {
            $ai_core_settings = get_option('ai_core_settings', array());
            if (isset($ai_core_settings['provider_models'][$provider])) {
                $default_model = $ai_core_settings['provider_models'][$provider];
            }
        }

        wp_send_json_success(array(
            'models' => $models,
            'provider' => $provider,
            'default_model' => $default_model,
        ));
    }

    /**
     * Test AI prompt with custom system and user prompts
     *
     * @return void
     */
    public function test_prompt() {
        check_ajax_referer('ai_stats_admin', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Permission denied'));
        }

        $provider = isset($_POST['provider']) ? sanitize_text_field($_POST['provider']) : '';
        $model = isset($_POST['model']) ? sanitize_text_field($_POST['model']) : '';
        $system_prompt = isset($_POST['system_prompt']) ? wp_kses_post($_POST['system_prompt']) : '';
        $user_prompt = isset($_POST['user_prompt']) ? wp_kses_post($_POST['user_prompt']) : '';
        $options = isset($_POST['options']) ? $_POST['options'] : array();

        if (empty($model)) {
            wp_send_json_error(array('message' => 'Model is required'));
        }

        if (empty($system_prompt) || empty($user_prompt)) {
            wp_send_json_error(array('message' => 'Both system and user prompts are required'));
        }

        if (!class_exists('AI_Core_API')) {
            wp_send_json_error(array('message' => 'AI-Core plugin is not active'));
        }

        try {
            $api = new AI_Core_API();

            $messages = array(
                array('role' => 'system', 'content' => $system_prompt),
                array('role' => 'user', 'content' => $user_prompt),
            );

            // Sanitize options
            $sanitized_options = array();
            if (is_array($options)) {
                foreach ($options as $key => $value) {
                    $sanitized_key = sanitize_key($key);
                    if (is_numeric($value)) {
                        $sanitized_options[$sanitized_key] = floatval($value);
                    } else {
                        $sanitized_options[$sanitized_key] = sanitize_text_field($value);
                    }
                }
            }

            $usage_context = array('tool' => 'ai-stats-debug', 'action' => 'test_prompt');
            $response = $api->send_text_request($model, $messages, $sanitized_options, $usage_context);

            if (is_wp_error($response)) {
                wp_send_json_error(array(
                    'message' => $response->get_error_message(),
                    'code' => $response->get_error_code(),
                ));
            }

            // Extract content from response (normaliser first)
            $content = '';
            if (class_exists('AICore\\AICore')) {
                $content = \AICore\AICore::extractContent($response);
            } elseif (isset($response['choices'][0]['message']['content'])) {
                $content = $response['choices'][0]['message']['content'];
            }

            $usage = isset($response['usage']) ? $response['usage'] : null;

            wp_send_json_success(array(
                'content' => $content,
                'usage' => $usage,
                'model' => $model,
                'provider' => $provider,
            ));

        } catch (Exception $e) {
            wp_send_json_error(array(
                'message' => 'Exception: ' . $e->getMessage(),
            ));
        }
    }
}
