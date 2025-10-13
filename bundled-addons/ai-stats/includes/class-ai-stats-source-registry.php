<?php
/**
 * AI-Stats Source Registry Class
 *
 * Manages the registry of data sources (APIs, RSS feeds, etc.)
 * Loads from consolidated-ai-stats-plugin-sources.md
 *
 * @package AI_Stats
 * @version 0.2.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Source registry class
 */
class AI_Stats_Source_Registry {
    
    /**
     * Singleton instance
     * 
     * @var AI_Stats_Source_Registry
     */
    private static $instance = null;
    
    /**
     * Registered sources
     * 
     * @var array
     */
    private $sources = array();
    
    /**
     * Get instance
     * 
     * @return AI_Stats_Source_Registry
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
        $this->load_sources();
    }
    
    /**
     * Load sources from configuration
     * 
     * @return void
     */
    private function load_sources() {
        // Check if sources are cached in options
        $cached_sources = get_option('ai_stats_source_registry', null);
        
        if ($cached_sources !== null) {
            $this->sources = $cached_sources;
            return;
        }
        
        // Build default source registry
        $this->sources = $this->build_default_registry();
        
        // Cache the registry
        update_option('ai_stats_source_registry', $this->sources);
    }
    
    /**
     * Build default source registry
     * 
     * @return array Source registry
     */
    private function build_default_registry() {
        return array(
            'statistics' => array(
                'mode' => 'Statistical Authority Injector',
                'sources' => array(
                    array('type' => 'API', 'name' => 'ONS API', 'url' => 'https://api.ons.gov.uk/', 'update' => 'weekly', 'tags' => array('uk_macro', 'statistics')),
                    array('type' => 'API', 'name' => 'Eurostat', 'url' => 'https://ec.europa.eu/eurostat/api/', 'update' => 'weekly', 'tags' => array('eu_stats', 'statistics')),
                    array('type' => 'API', 'name' => 'World Bank', 'url' => 'https://api.worldbank.org/v2/', 'update' => 'monthly', 'tags' => array('global_stats', 'statistics')),
                    array('type' => 'API', 'name' => 'Companies House', 'url' => 'https://api.company-information.service.gov.uk/', 'update' => 'daily', 'tags' => array('uk_business', 'companies')),
                ),
            ),
            'birmingham' => array(
                'mode' => 'Birmingham Business Stats',
                'sources' => array(
                    array('type' => 'API', 'name' => 'Birmingham City Observatory', 'url' => 'https://data.birmingham.gov.uk/', 'update' => 'weekly', 'tags' => array('birmingham', 'local')),
                    array('type' => 'RSS', 'name' => 'Birmingham.gov.uk News', 'url' => 'https://www.birmingham.gov.uk/rss/news', 'update' => 'daily', 'tags' => array('birmingham', 'news')),
                    array('type' => 'API', 'name' => 'WMCA Data', 'url' => 'https://www.wmca.org.uk/what-we-do/economy-and-innovation/economic-data/', 'update' => 'monthly', 'tags' => array('west_midlands', 'regional')),
                    array('type' => 'API', 'name' => 'ONS Regional', 'url' => 'https://api.ons.gov.uk/', 'update' => 'weekly', 'tags' => array('uk_macro', 'regional')),
                ),
            ),
            'trends' => array(
                'mode' => 'Industry Trend Micro-Module',
                'sources' => array(
                    array('type' => 'RSS', 'name' => 'Search Engine Land', 'url' => 'https://feeds.searchengineland.com/searchengineland', 'update' => 'hourly', 'tags' => array('seo', 'news')),
                    array('type' => 'RSS', 'name' => 'Search Engine Journal', 'url' => 'https://www.searchenginejournal.com/feed/', 'update' => 'hourly', 'tags' => array('seo', 'news')),
                    array('type' => 'RSS', 'name' => 'Google Search Status', 'url' => 'https://status.search.google.com/feed.atom', 'update' => 'hourly', 'tags' => array('google', 'status')),
                    array('type' => 'RSS', 'name' => 'Google Search Central', 'url' => 'https://developers.google.com/search/blog/rss.xml', 'update' => 'daily', 'tags' => array('google', 'seo')),
                    array('type' => 'RSS', 'name' => 'Moz Blog', 'url' => 'https://moz.com/blog/rss', 'update' => 'daily', 'tags' => array('seo', 'marketing')),
                    array('type' => 'RSS', 'name' => 'Smashing Magazine', 'url' => 'https://www.smashingmagazine.com/feed/', 'update' => 'daily', 'tags' => array('web_design', 'development')),
                    array('type' => 'API', 'name' => 'CrUX API', 'url' => 'https://chromeuxreport.googleapis.com/v1/records:queryRecord', 'update' => 'daily', 'tags' => array('performance', 'web_vitals')),
                ),
            ),
            'benefits' => array(
                'mode' => 'Service + Benefit Semantic Expander',
                'sources' => array(
                    array('type' => 'RSS', 'name' => 'HubSpot Marketing', 'url' => 'https://blog.hubspot.com/marketing/rss.xml', 'update' => 'daily', 'tags' => array('marketing', 'benefits')),
                    array('type' => 'RSS', 'name' => 'Think with Google', 'url' => 'https://www.thinkwithgoogle.com/intl/en-gb/feed/', 'update' => 'weekly', 'tags' => array('marketing', 'insights')),
                    array('type' => 'HTML', 'name' => 'WordStream Benchmarks', 'url' => 'https://www.wordstream.com/blog/ws/google-ads-industry-benchmarks', 'update' => 'monthly', 'tags' => array('benchmarks', 'ppc')),
                    array('type' => 'HTML', 'name' => 'Mailchimp Benchmarks', 'url' => 'https://mailchimp.com/resources/email-marketing-benchmarks/', 'update' => 'quarterly', 'tags' => array('benchmarks', 'email')),
                ),
            ),
            'seasonal' => array(
                'mode' => 'Seasonal Service Angle Rotator',
                'sources' => array(
                    array('type' => 'API', 'name' => 'UK Bank Holidays', 'url' => 'https://www.gov.uk/bank-holidays.json', 'update' => 'monthly', 'tags' => array('uk', 'holidays')),
                    array('type' => 'API', 'name' => 'Calendarific', 'url' => 'https://calendarific.com/api/v2/', 'update' => 'monthly', 'tags' => array('global', 'holidays')),
                    array('type' => 'RSS', 'name' => 'Google Trends Daily', 'url' => 'https://trends.google.com/trends/trendingsearches/daily/rss?geo=GB', 'update' => 'daily', 'tags' => array('trends', 'seasonal')),
                ),
            ),
            'process' => array(
                'mode' => 'Service Process Micro-Step Enhancer',
                'sources' => array(
                    array('type' => 'RSS', 'name' => 'Nielsen Norman Group', 'url' => 'https://www.nngroup.com/feed/', 'update' => 'weekly', 'tags' => array('ux', 'process')),
                    array('type' => 'RSS', 'name' => 'UX Collective', 'url' => 'https://uxdesign.cc/feed', 'update' => 'daily', 'tags' => array('ux', 'design')),
                    array('type' => 'RSS', 'name' => 'Smashing Magazine UX', 'url' => 'https://www.smashingmagazine.com/category/ux-design/feed/', 'update' => 'daily', 'tags' => array('ux', 'design')),
                ),
            ),
        );
    }
    
    /**
     * Get sources for a specific mode
     * 
     * @param string $mode Mode key
     * @return array Sources for the mode
     */
    public function get_sources_for_mode($mode) {
        if (!isset($this->sources[$mode])) {
            return array();
        }
        
        return $this->sources[$mode]['sources'];
    }
    
    /**
     * Get all sources
     * 
     * @return array All sources
     */
    public function get_all_sources() {
        return $this->sources;
    }
    
    /**
     * Get sources by type
     * 
     * @param string $type Source type (RSS, API, HTML)
     * @return array Filtered sources
     */
    public function get_sources_by_type($type) {
        $filtered = array();
        
        foreach ($this->sources as $mode_key => $mode_data) {
            foreach ($mode_data['sources'] as $source) {
                if ($source['type'] === $type) {
                    $filtered[] = array_merge($source, array('mode' => $mode_key));
                }
            }
        }
        
        return $filtered;
    }
    
    /**
     * Get sources by tags
     * 
     * @param array $tags Tags to filter by
     * @return array Filtered sources
     */
    public function get_sources_by_tags($tags) {
        $filtered = array();
        
        foreach ($this->sources as $mode_key => $mode_data) {
            foreach ($mode_data['sources'] as $source) {
                $source_tags = $source['tags'] ?? array();
                if (array_intersect($tags, $source_tags)) {
                    $filtered[] = array_merge($source, array('mode' => $mode_key));
                }
            }
        }
        
        return $filtered;
    }
    
    /**
     * Refresh registry (re-build from defaults)
     * 
     * @return void
     */
    public function refresh_registry() {
        $this->sources = $this->build_default_registry();
        update_option('ai_stats_source_registry', $this->sources);
    }
    
    /**
     * Add custom source
     * 
     * @param string $mode Mode key
     * @param array $source Source data
     * @return bool Success status
     */
    public function add_source($mode, $source) {
        if (!isset($this->sources[$mode])) {
            return false;
        }
        
        $this->sources[$mode]['sources'][] = $source;
        update_option('ai_stats_source_registry', $this->sources);
        
        return true;
    }
    
    /**
     * Remove source
     * 
     * @param string $mode Mode key
     * @param int $index Source index
     * @return bool Success status
     */
    public function remove_source($mode, $index) {
        if (!isset($this->sources[$mode]['sources'][$index])) {
            return false;
        }
        
        unset($this->sources[$mode]['sources'][$index]);
        $this->sources[$mode]['sources'] = array_values($this->sources[$mode]['sources']);
        update_option('ai_stats_source_registry', $this->sources);
        
        return true;
    }
}

