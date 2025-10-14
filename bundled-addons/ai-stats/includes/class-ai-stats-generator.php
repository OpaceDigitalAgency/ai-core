<?php
/**
 * AI-Stats Content Generator Class
 *
 * Generates dynamic content using AI-Core and scraped data
 *
 * @package AI_Stats
 * @version 0.3.4
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
        if (!$this->ai_core || !$this->ai_core->is_configured()) {
            return new WP_Error('not_configured', __('AI-Core is not configured', 'ai-stats'));
        }
        
        // Get mode-specific data
        $mode_data = $this->get_mode_data($mode);
        if (is_wp_error($mode_data)) {
            return $mode_data;
        }
        
        // Build prompt based on mode
        $prompt = $this->build_prompt($mode, $mode_data, $context);
        
        // Get settings
        $settings = get_option('ai_stats_settings', array());
        $provider = $settings['preferred_provider'] ?? (method_exists($this->ai_core, 'get_default_provider') ? $this->ai_core->get_default_provider() : 'openai');
        $preferred_model = $settings['preferred_model'] ?? '';
        if (empty($preferred_model) && method_exists($this->ai_core, 'get_default_model_for_provider')) {
            $preferred_model = $this->ai_core->get_default_model_for_provider($provider);
        }
        $model = !empty($preferred_model) ? $preferred_model : $this->get_model_for_provider($provider);

        // Generate content using AI-Core
        $messages = array(
            array(
                'role' => 'system',
                'content' => $this->get_system_prompt($mode),
            ),
            array(
                'role' => 'user',
                'content' => $prompt,
            ),
        );
        
        $options = array(
            'max_tokens' => 500,
            'temperature' => 0.7,
        );
        
        $usage_context = array('tool' => 'ai-stats', 'mode' => $mode);
        $response = $this->ai_core->send_text_request($model, $messages, $options, $usage_context);
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        // Extract content from response
        $content = '';
        if (isset($response['choices'][0]['message']['content'])) {
            $content = $response['choices'][0]['message']['content'];
        } elseif (class_exists('AICore\\AICore')) {
            $content = \AICore\AICore::extractContent($response);
        }
        
        if (empty($content)) {
            return new WP_Error('empty_response', __('AI returned empty response', 'ai-stats'));
        }
        
        return array(
            'content' => $content,
            'mode' => $mode,
            'sources' => $mode_data['sources'] ?? array(),
            'metadata' => array(
                'generated_at' => current_time('mysql'),
                'model' => $model,
                'provider' => $provider,
                'mode_data' => $mode_data,
            ),
        );
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
        $stats = $this->scraper->fetch_business_stats();
        
        return array(
            'type' => 'statistics',
            'data' => $stats,
            'sources' => array('HubSpot', 'Statista'),
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
        $prompt = "Create a compelling, statistic-driven statement for a web design and SEO agency.\n\n";
        $prompt .= "Use real business statistics to demonstrate authority and build trust.\n";
        $prompt .= "Format: One concise sentence (max 150 characters) with a relevant statistic.\n";
        $prompt .= "Include source citation in parentheses.\n\n";
        $prompt .= "Examples:\n";
        $prompt .= "- \"90% of businesses see 200% ROI from SEO within 12 months (Source: HubSpot)\"\n";
        $prompt .= "- \"Birmingham's digital economy grew 23% in 2024 (Source: ONS)\"\n\n";
        
        if (!empty($mode_data['data'])) {
            $prompt .= "Available statistics:\n";
            $prompt .= wp_json_encode($mode_data['data'], JSON_PRETTY_PRINT);
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
        return "You are an expert SEO and marketing copywriter. Create compelling, data-driven content that builds authority and trust. Always use British English spellings. Be concise and impactful.";
    }
    
    /**
     * Get model for provider
     */
    private function get_model_for_provider($provider) {
        $models = array(
            'openai' => 'gpt-4o',
            'anthropic' => 'claude-sonnet-4-20250514',
            'gemini' => 'gemini-2.0-flash-exp',
            'grok' => 'grok-beta',
        );
        
        return $models[$provider] ?? 'gpt-4o';
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

