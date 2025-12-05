<?php
/**
 * AI-Stats Content Generator Class
 *
 * Generates dynamic content using AI-Core and scraped data
 *
 * @package AI_Stats
 * @version 0.8.1
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Content generator class
 */
class AI_Stats_Generator {
    
    /**
     * Singleton instance
     * 
     * @var AI_Stats_Generator
     */
    private static $instance = null;
    
    /**
     * AI-Core API instance
     * 
     * @var AI_Core_API
     */
    private $ai_core = null;
    
    /**
     * Scraper instance
     * 
     * @var AI_Stats_Scraper
     */
    private $scraper = null;
    
    /**
     * Get instance
     * 
     * @return AI_Stats_Generator
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
        if (function_exists('ai_core')) {
            $this->ai_core = ai_core();
        }
        $this->scraper = AI_Stats_Scraper::get_instance();
    }
    
    /**
     * Generate content for specific mode
     * 
     * @param string $mode Content mode
     * @param array $context Additional context data
     * @return array|WP_Error Generated content or error
     */
    public function generate_content($mode, $context = array()) {
        $config = $this->prepare_generation_config($mode, $context);
        if (is_wp_error($config)) {
            return $config;
        }

        $usage_context = array('tool' => 'ai-stats', 'mode' => $mode);
        $response = $this->ai_core->send_text_request($config['model'], $config['messages'], $config['options'], $usage_context);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        // Extract content from response using the shared normaliser (supports Chat and Responses APIs)
        $content = '';
        if (class_exists('AICore\\AICore')) {
            $content = \AICore\AICore::extractContent($response);
        } elseif (isset($response['choices'][0]['message']['content'])) {
            $content = $response['choices'][0]['message']['content'];
        }

        if (empty($content)) {
            return new WP_Error('empty_response', __('AI returned empty response', 'ai-stats'));
        }
        
        return array(
            'content' => $content,
            'mode' => $mode,
            'sources' => $config['mode_data']['sources'] ?? array(),
            'metadata' => array(
                'generated_at' => current_time('mysql'),
                'model' => $config['model'],
                'provider' => $config['provider'],
                'mode_data' => $config['mode_data'],
                'options' => $config['options'],
                'prompts' => array(
                    'system' => $config['system_prompt'],
                    'user' => $config['user_prompt'],
                ),
            ),
        );
    }

    /**
     * Prepare generation configuration for a mode.
     *
     * @param string $mode Content mode
     * @param array  $context Additional context
     * @return array|WP_Error
     */
    public function prepare_generation_config($mode, $context = array()) {
        if (!$this->ai_core || !$this->ai_core->is_configured()) {
            return new WP_Error('not_configured', __('AI-Core is not configured', 'ai-stats'));
        }

        $mode_data = $this->get_mode_data($mode);
        if (is_wp_error($mode_data)) {
            return $mode_data;
        }

        $settings = get_option('ai_stats_settings', array());
        $default_provider = method_exists($this->ai_core, 'get_default_provider') ? $this->ai_core->get_default_provider() : 'openai';
        $provider = $context['provider'] ?? ($settings['preferred_provider'] ?? $default_provider);

        $provider_config = $this->build_provider_configuration($provider);
        if (is_wp_error($provider_config)) {
            return $provider_config;
        }

        $preferred_model = $context['model'] ?? ($settings['preferred_model'] ?? '');
        if (!empty($preferred_model)) {
            $provider_config = $this->apply_model_override_to_config($provider_config, $preferred_model);
        }

        $model = $provider_config['model'];
        if (empty($model)) {
            return new WP_Error('model_unavailable', sprintf(__('No AI model available for provider %s.', 'ai-stats'), $provider));
        }

        $system_prompt = $this->get_system_prompt($mode);
        $user_prompt = $this->build_prompt($mode, $mode_data, $context);

        $messages = array(
            array(
                'role' => 'system',
                'content' => $system_prompt,
            ),
            array(
                'role' => 'user',
                'content' => $user_prompt,
            ),
        );

        return array(
            'provider' => $provider,
            'model' => $model,
            'options' => $provider_config['options'],
            'available_models' => $provider_config['available_models'],
            'parameter_schema' => $provider_config['parameter_schema'],
            'messages' => $messages,
            'system_prompt' => $system_prompt,
            'user_prompt' => $user_prompt,
            'mode_data' => $mode_data,
            'context' => $context,
        );
    }

    /**
     * Build provider configuration from AI-Core settings.
     *
     * @param string $provider Provider key
     * @return array|WP_Error
     */
    private function build_provider_configuration($provider) {
        if (!$this->ai_core) {
            return new WP_Error('no_ai_core', __('AI-Core is not available', 'ai-stats'));
        }

        $config = array(
            'provider' => $provider,
            'available_models' => array(),
            'model' => null,
            'options' => array(),
            'parameter_schema' => array(),
        );

        if (method_exists($this->ai_core, 'get_provider_settings')) {
            $provider_settings = $this->ai_core->get_provider_settings($provider);
            $config['available_models'] = $provider_settings['models'] ?? array();
            $config['model'] = $provider_settings['model'] ?? null;
            $config['options'] = $provider_settings['options'] ?? array();
            $config['parameter_schema'] = $provider_settings['parameter_schema'] ?? array();
            return $config;
        }

        $available_models = method_exists($this->ai_core, 'get_available_models') ? $this->ai_core->get_available_models($provider) : array();
        if (empty($available_models) && class_exists('\\AICore\\Registry\\ModelRegistry')) {
            $available_models = \AICore\Registry\ModelRegistry::getModelsByProvider($provider);
        }

        // Filter out Responses API models - only use Chat Completions models
        $chat_models = $this->filter_chat_completion_models($available_models, $provider);
        if (!empty($chat_models)) {
            $available_models = $chat_models;
        }

        $config['available_models'] = $available_models;

        $default_model = method_exists($this->ai_core, 'get_default_model_for_provider')
            ? $this->ai_core->get_default_model_for_provider($provider)
            : null;

        // Ensure default model is a Chat Completions model
        if (!empty($default_model) && !in_array($default_model, $available_models)) {
            $default_model = null;
        }

        if (empty($default_model) && class_exists('\\AICore\\Registry\\ModelRegistry')) {
            $preferred = \AICore\Registry\ModelRegistry::getPreferredModel($provider, $available_models);
            if (!empty($preferred)) {
                $default_model = $preferred;
            }
        }

        if (empty($default_model) && !empty($available_models)) {
            $default_model = $available_models[0];
        }

        $config['model'] = $default_model;

        if (method_exists($this->ai_core, 'get_provider_options')) {
            $config['options'] = $this->ai_core->get_provider_options($provider, $default_model);
        }

        if (class_exists('\\AICore\\Registry\\ModelRegistry') && $default_model) {
            $config['parameter_schema'] = \AICore\Registry\ModelRegistry::getParameterSchema($default_model);
        }

        return $config;
    }

    /**
     * Apply model override to provider configuration.
     *
     * @param array  $config Provider configuration
     * @param string $model  Desired model id
     * @return array
     */
    private function apply_model_override_to_config(array $config, $model) {
        if (!in_array($model, $config['available_models'], true)) {
            $config['available_models'][] = $model;
        }

        $config['model'] = $model;

        if ($this->ai_core && method_exists($this->ai_core, 'get_provider_options')) {
            $config['options'] = $this->ai_core->get_provider_options($config['provider'], $model);
        }

        if (class_exists('\\AICore\\Registry\\ModelRegistry')) {
            $config['parameter_schema'] = \AICore\Registry\ModelRegistry::getParameterSchema($model);
        }

        return $config;
    }

    /**
     * Filter models to only include Chat Completions API models (exclude Responses API models)
     *
     * @param array  $models   Available models
     * @param string $provider Provider key
     * @return array Filtered models
     */
    private function filter_chat_completion_models($models, $provider) {
        // For OpenAI, exclude models that use Responses API
        if ($provider === 'openai') {
            $responses_api_models = array(
                'gpt-4o-2024-08-06',
                'gpt-4o-mini-2024-07-18',
            );

            return array_filter($models, function($model) use ($responses_api_models) {
                // Exclude exact matches and models containing these strings
                foreach ($responses_api_models as $excluded) {
                    if ($model === $excluded || strpos($model, $excluded) !== false) {
                        return false;
                    }
                }
                return true;
            });
        }

        // For other providers, return all models (they use Chat Completions)
        return $models;
    }

    /**
     * Get data for specific mode
     *
     * @param string $mode Content mode
     * @return array|WP_Error Mode data or error
     */
    private function get_mode_data($mode) {
        switch ($mode) {
            case 'statistics':
                return $this->get_statistics_data();

            case 'news_summary':
                return $this->get_news_summary_data();

            case 'birmingham':
                return $this->get_birmingham_data();

            case 'trends':
                return $this->get_trends_data();

            case 'benefits':
                return $this->get_benefits_data();

            case 'seasonal':
                return $this->get_seasonal_data();

            case 'process':
                return $this->get_process_data();

            default:
                return new WP_Error('invalid_mode', __('Invalid content mode', 'ai-stats'));
        }
    }
    
    /**
     * Get statistics mode data
     *
     * @return array Statistics data
     */
    private function get_statistics_data() {
        // Fetch candidates from adapters
        $adapters = AI_Stats_Adapters::get_instance();
        $candidates = $adapters->fetch_candidates('statistics', array(), array(), 12);

        if (is_wp_error($candidates)) {
            return array(
                'type' => 'statistics',
                'candidates' => array(),
                'sources' => array(),
                'error' => $candidates->get_error_message(),
            );
        }

        // Extract unique sources
        $sources = array();
        foreach ($candidates as $candidate) {
            if (!empty($candidate['source']) && !in_array($candidate['source'], $sources)) {
                $sources[] = $candidate['source'];
            }
        }

        return array(
            'type' => 'statistics',
            'candidates' => $candidates,
            'sources' => $sources,
        );
    }

    /**
     * Get news summary mode data
     *
     * @return array News summary data
     */
    private function get_news_summary_data() {
        // Fetch candidates from adapters
        $adapters = AI_Stats_Adapters::get_instance();
        $candidates = $adapters->fetch_candidates('news_summary', array(), array(), 12);

        if (is_wp_error($candidates)) {
            return array(
                'type' => 'news_summary',
                'candidates' => array(),
                'sources' => array(),
                'error' => $candidates->get_error_message(),
            );
        }

        // Extract unique sources
        $sources = array();
        foreach ($candidates as $candidate) {
            if (!empty($candidate['source']) && !in_array($candidate['source'], $sources)) {
                $sources[] = $candidate['source'];
            }
        }

        return array(
            'type' => 'news_summary',
            'candidates' => $candidates,
            'sources' => $sources,
        );
    }
    
    /**
     * Get Birmingham business data
     * 
     * @return array Birmingham data
     */
    private function get_birmingham_data() {
        $stats = $this->scraper->fetch_birmingham_stats();
        
        return array(
            'type' => 'birmingham',
            'data' => $stats,
            'sources' => array('Birmingham Chamber of Commerce', 'ONS', 'Birmingham.gov.uk'),
        );
    }
    
    /**
     * Get industry trends data
     * 
     * @return array Trends data
     */
    private function get_trends_data() {
        $trends = $this->scraper->fetch_industry_trends();
        
        return array(
            'type' => 'trends',
            'data' => $trends,
            'sources' => array('Search Engine Land', 'Moz', 'Google Search Blog', 'Smashing Magazine'),
        );
    }
    
    /**
     * Get benefits mode data
     * 
     * @return array Benefits data
     */
    private function get_benefits_data() {
        // Get site's service descriptions
        $services = $this->get_site_services();
        
        return array(
            'type' => 'benefits',
            'data' => $services,
            'sources' => array('Site Content'),
        );
    }
    
    /**
     * Get seasonal mode data
     * 
     * @return array Seasonal data
     */
    private function get_seasonal_data() {
        $current_quarter = ceil(date('n') / 3);
        $current_month = date('n');
        
        return array(
            'type' => 'seasonal',
            'data' => array(
                'quarter' => $current_quarter,
                'month' => $current_month,
                'season' => $this->get_season($current_month),
            ),
            'sources' => array('Calendar Data'),
        );
    }
    
    /**
     * Get process mode data
     * 
     * @return array Process data
     */
    private function get_process_data() {
        // Get site's service process descriptions
        $processes = $this->get_site_processes();
        
        return array(
            'type' => 'process',
            'data' => $processes,
            'sources' => array('Site Content'),
        );
    }
    
    /**
     * Build prompt for mode
     * 
     * @param string $mode Content mode
     * @param array $mode_data Mode-specific data
     * @param array $context Additional context
     * @return string Prompt text
     */
    private function build_prompt($mode, $mode_data, $context) {
        $site_name = get_bloginfo('name');
        $site_url = get_bloginfo('url');

        $base_context = "Website: $site_name ($site_url)\n";
        $base_context .= "Current Date: " . date('F j, Y') . "\n\n";

        switch ($mode) {
            case 'statistics':
                return $base_context . $this->build_statistics_prompt($mode_data, $context);

            case 'news_summary':
                return $base_context . $this->build_news_summary_prompt($mode_data, $context);

            case 'birmingham':
                return $base_context . $this->build_birmingham_prompt($mode_data, $context);

            case 'trends':
                return $base_context . $this->build_trends_prompt($mode_data, $context);

            case 'benefits':
                return $base_context . $this->build_benefits_prompt($mode_data, $context);

            case 'seasonal':
                return $base_context . $this->build_seasonal_prompt($mode_data, $context);

            case 'process':
                return $base_context . $this->build_process_prompt($mode_data, $context);

            default:
                return $base_context . "Generate engaging content for this website.";
        }
    }
    
    /**
     * Build statistics mode prompt
     *
     * @param array $mode_data Mode data
     * @param array $context Context
     * @return string Prompt
     */
    private function build_statistics_prompt($mode_data, $context) {
        $prompt = "You are generating 3 data-driven bullet points for a UK digital marketing agency website.\n\n";
        $prompt .= "**STRICT REQUIREMENTS:**\n";
        $prompt .= "1. Generate EXACTLY 3 bullet points\n";
        $prompt .= "2. Each bullet MUST start with an emoji (📊, 🚀, 💰, 📈, 🎯, or similar)\n";
        $prompt .= "3. Each bullet MUST contain a specific statistic (percentage, number, or monetary value)\n";
        $prompt .= "4. Each bullet MUST end with the source in parentheses: (Source Name, Month Year)\n";
        $prompt .= "5. Keep each bullet under 150 characters\n";
        $prompt .= "6. Use British English spellings (e.g., 'optimise' not 'optimize')\n";
        $prompt .= "7. Focus on SEO, web design, digital marketing, or business growth statistics\n\n";

        $prompt .= "**EXACT FORMAT:**\n";
        $prompt .= "<li>📊 67% of businesses increased organic traffic by 200% after implementing structured data (Search Engine Land, Dec 2025)</li>\n";
        $prompt .= "<li>🚀 Google's new AI Overviews now appear in 45% of search results, changing SEO strategy (Moz, Dec 2025)</li>\n";
        $prompt .= "<li>💰 Birmingham businesses investing in local SEO see average £12,500 monthly revenue increase (ONS, Nov 2025)</li>\n\n";

        $prompt .= "**IMPORTANT:**\n";
        $prompt .= "- Output ONLY the 3 <li> tags, nothing else\n";
        $prompt .= "- Do NOT add <ul> tags or any other HTML\n";
        $prompt .= "- Do NOT add explanations or notes\n";
        $prompt .= "- Extract real statistics from the articles provided below\n\n";

        if (!empty($mode_data['candidates'])) {
            $prompt .= "**ARTICLES TO EXTRACT STATISTICS FROM:**\n\n";
            foreach (array_slice($mode_data['candidates'], 0, 10) as $index => $candidate) {
                $prompt .= sprintf(
                    "%d. **%s** (%s)\n   %s\n   URL: %s\n\n",
                    $index + 1,
                    $candidate['title'] ?? 'Untitled',
                    $candidate['source'] ?? 'Unknown',
                    substr($candidate['blurb_seed'] ?? $candidate['full_content'] ?? '', 0, 300),
                    $candidate['url'] ?? ''
                );
            }
        } else {
            $prompt .= "**NO ARTICLES PROVIDED** - Generate generic but realistic statistics about SEO and web design.\n";
        }

        return $prompt;
    }

    /**
     * Build news summary mode prompt
     *
     * @param array $mode_data Mode data
     * @param array $context Context
     * @return string Prompt
     */
    private function build_news_summary_prompt($mode_data, $context) {
        $prompt = "You are generating a daily news summary for a UK digital marketing agency website.\n\n";
        $prompt .= "**TASK:**\n";
        $prompt .= "Write a 2-3 paragraph summary of the latest industry news from the articles provided below.\n\n";

        $prompt .= "**REQUIREMENTS:**\n";
        $prompt .= "1. Write in a professional, engaging tone\n";
        $prompt .= "2. Use British English spellings\n";
        $prompt .= "3. Mention specific sources by name (e.g., 'Moz reports...', 'HubSpot says...', 'According to Search Engine Land...')\n";
        $prompt .= "4. Focus on actionable insights relevant to SEO, web design, and digital marketing\n";
        $prompt .= "5. Keep the total summary under 300 words\n";
        $prompt .= "6. Use HTML paragraph tags: <p>...</p>\n";
        $prompt .= "7. Do NOT add a title or heading\n\n";

        $prompt .= "**EXAMPLE FORMAT:**\n";
        $prompt .= "<p>This week brings significant updates to the SEO landscape. Moz reports that Google's latest algorithm update prioritises user experience signals, with Core Web Vitals now accounting for 40% of ranking factors. Meanwhile, Search Engine Land highlights that businesses implementing structured data saw an average 67% increase in click-through rates.</p>\n\n";
        $prompt .= "<p>On the web design front, Smashing Magazine reveals that minimalist designs with bold typography are dominating 2025 trends. HubSpot's latest research shows that websites with faster load times (under 2 seconds) convert 3x better than slower competitors.</p>\n\n";

        $prompt .= "**IMPORTANT:**\n";
        $prompt .= "- Output ONLY the HTML paragraphs, nothing else\n";
        $prompt .= "- Do NOT add explanations or notes\n";
        $prompt .= "- Synthesise information from multiple articles into a cohesive narrative\n\n";

        if (!empty($mode_data['candidates'])) {
            $prompt .= "**ARTICLES TO SUMMARISE:**\n\n";
            foreach (array_slice($mode_data['candidates'], 0, 12) as $index => $candidate) {
                $prompt .= sprintf(
                    "%d. **%s** (%s)\n   %s\n   URL: %s\n\n",
                    $index + 1,
                    $candidate['title'] ?? 'Untitled',
                    $candidate['source'] ?? 'Unknown',
                    substr($candidate['blurb_seed'] ?? $candidate['full_content'] ?? '', 0, 400),
                    $candidate['url'] ?? ''
                );
            }
        } else {
            $prompt .= "**NO ARTICLES PROVIDED** - Generate a generic summary about recent SEO and web design trends.\n";
        }

        return $prompt;
    }

    /**
     * Build Birmingham mode prompt
     *
     * @param array $mode_data Mode data
     * @param array $context Context
     * @return string Prompt
     */
    private function build_birmingham_prompt($mode_data, $context) {
        $prompt = "Create a Birmingham-focused business statement that builds local authority.\n\n";
        $prompt .= "Use real Birmingham business statistics.\n";
        $prompt .= "Format: One engaging sentence highlighting Birmingham's business landscape.\n\n";
        $prompt .= "Examples:\n";
        $prompt .= "- \"Join 12,847 Birmingham businesses growing online\"\n";
        $prompt .= "- \"Birmingham's tech sector employs over 50,000 professionals\"\n\n";
        
        if (!empty($mode_data['data'])) {
            $prompt .= "Birmingham statistics:\n";
            $prompt .= wp_json_encode($mode_data['data'], JSON_PRETTY_PRINT);
        }
        
        return $prompt;
    }
    
    /**
     * Build trends mode prompt (will be extended)
     */
    private function build_trends_prompt($mode_data, $context) {
        return "Generate industry trend content based on latest SEO and web design news.";
    }
    
    /**
     * Build benefits mode prompt (will be extended)
     */
    private function build_benefits_prompt($mode_data, $context) {
        return "Generate benefit-focused service description.";
    }
    
    /**
     * Build seasonal mode prompt (will be extended)
     */
    private function build_seasonal_prompt($mode_data, $context) {
        return "Generate seasonal service angle.";
    }
    
    /**
     * Build process mode prompt (will be extended)
     */
    private function build_process_prompt($mode_data, $context) {
        return "Generate detailed process description.";
    }
    
    /**
     * Get system prompt for mode
     */
    private function get_system_prompt($mode) {
        switch ($mode) {
            case 'statistics':
                return "You are an expert data analyst and copywriter. Extract specific statistics from articles and format them as engaging bullet points. Always use British English spellings. Be precise with numbers and sources.";

            case 'news_summary':
                return "You are an expert industry news summariser and copywriter. Synthesise multiple articles into a cohesive narrative summary. Always use British English spellings. Be engaging and informative.";

            default:
                return "You are an expert SEO and marketing copywriter. Create compelling, data-driven content that builds authority and trust. Always use British English spellings. Be concise and impactful.";
        }
    }
    
    /**
     * Get site services (placeholder)
     */
    private function get_site_services() {
        return array();
    }
    
    /**
     * Get site processes (placeholder)
     */
    private function get_site_processes() {
        return array();
    }
    
    /**
     * Get season from month
     */
    private function get_season($month) {
        if ($month >= 3 && $month <= 5) return 'Spring';
        if ($month >= 6 && $month <= 8) return 'Summer';
        if ($month >= 9 && $month <= 11) return 'Autumn';
        return 'Winter';
    }
}
