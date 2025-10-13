<?php
/**
 * AI-Stats Source Registry Class
 *
 * Manages the registry of data sources (APIs, RSS feeds, etc.)
 * Loads from consolidated-ai-stats-plugin-sources.md
 *
 * @package AI_Stats
 * @version 0.2.6
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
                    array('type' => 'API', 'name' => 'Eurostat', 'url' => 'https://ec.europa.eu/eurostat/api/dissemination/statistics/1.0/data/', 'update' => 'weekly', 'tags' => array('eu_stats', 'statistics')),
                    array('type' => 'API', 'name' => 'World Bank', 'url' => 'https://api.worldbank.org/v2/', 'update' => 'monthly', 'tags' => array('global_stats', 'statistics')),
                    array('type' => 'API', 'name' => 'Companies House', 'url' => 'https://api.company-information.service.gov.uk/', 'update' => 'daily', 'tags' => array('uk_business', 'companies')),
                    array('type' => 'RSS', 'name' => 'Statista Studies', 'url' => 'https://www.statista.com/rss/studies', 'update' => 'daily', 'tags' => array('statistics', 'research')),
                    array('type' => 'RSS', 'name' => 'Statista Free Statistics', 'url' => 'https://www.statista.com/rss/free', 'update' => 'daily', 'tags' => array('statistics', 'free')),
                    array('type' => 'RSS', 'name' => 'Statista Economy & Politics', 'url' => 'https://www.statista.com/rss/statistics/239', 'update' => 'daily', 'tags' => array('statistics', 'economy')),
                    array('type' => 'RSS', 'name' => 'Statista Society', 'url' => 'https://www.statista.com/rss/statistics/485', 'update' => 'daily', 'tags' => array('statistics', 'society')),
                ),
            ),
            'birmingham' => array(
                'mode' => 'Birmingham Business Stats',
                'sources' => array(
                    array('type' => 'API', 'name' => 'WMCA Data', 'url' => 'https://data.wmca.org.uk/api/explore/v2.1/', 'update' => 'monthly', 'tags' => array('west_midlands', 'regional')),
                    array('type' => 'API', 'name' => 'ONS Regional', 'url' => 'https://api.ons.gov.uk/', 'update' => 'weekly', 'tags' => array('uk_macro', 'regional')),
                    array('type' => 'API', 'name' => 'Birmingham Open Data', 'url' => 'https://data.birmingham.gov.uk/api/explore/v2.1/', 'update' => 'weekly', 'tags' => array('birmingham', 'local')),
                    array('type' => 'RSS', 'name' => 'Tech City News', 'url' => 'https://techcitynews.com/feed/', 'update' => 'daily', 'tags' => array('uk', 'tech', 'startups')),
                    array('type' => 'RSS', 'name' => 'TechRound', 'url' => 'https://techround.co.uk/feed/', 'update' => 'daily', 'tags' => array('uk', 'tech', 'news')),
                    array('type' => 'RSS', 'name' => 'BusinessCloud', 'url' => 'https://businesscloud.co.uk/feed/', 'update' => 'daily', 'tags' => array('uk', 'cloud', 'saas')),
                    array('type' => 'RSS', 'name' => 'The Register', 'url' => 'https://www.theregister.com/headlines.atom', 'update' => 'daily', 'tags' => array('uk', 'tech', 'enterprise')),
                    array('type' => 'RSS', 'name' => 'BBC Business', 'url' => 'http://feeds.bbci.co.uk/news/business/rss.xml', 'update' => 'hourly', 'tags' => array('uk', 'business', 'news')),
                    array('type' => 'RSS', 'name' => 'City AM', 'url' => 'https://www.cityam.com/feed/', 'update' => 'daily', 'tags' => array('uk', 'business', 'finance')),
                    array('type' => 'RSS', 'name' => 'Retail Gazette', 'url' => 'https://www.retailgazette.co.uk/feed/', 'update' => 'daily', 'tags' => array('uk', 'retail', 'news')),
                    array('type' => 'RSS', 'name' => 'Internet Retailing', 'url' => 'https://internetretailing.net/feed/', 'update' => 'daily', 'tags' => array('uk', 'ecommerce', 'retail')),
                    array('type' => 'RSS', 'name' => 'Essential Retail', 'url' => 'https://www.essentialretail.com/feed/', 'update' => 'daily', 'tags' => array('uk', 'retail', 'trends')),
                ),
            ),
            'trends' => array(
                'mode' => 'Industry Trend Micro-Module',
                'sources' => array(
                    array('type' => 'API', 'name' => 'BigQuery Google Trends', 'url' => 'bigquery://bigquery-public-data.google_trends.top_terms', 'update' => 'daily', 'tags' => array('google_trends', 'trending', 'bigquery')),
                    array('type' => 'RSS', 'name' => 'Search Engine Land', 'url' => 'https://searchengineland.com/feed', 'update' => 'hourly', 'tags' => array('seo', 'news')),
                    array('type' => 'RSS', 'name' => 'Search Engine Journal', 'url' => 'https://www.searchenginejournal.com/feed/', 'update' => 'hourly', 'tags' => array('seo', 'news')),
                    array('type' => 'RSS', 'name' => 'Google Search Status', 'url' => 'https://status.search.google.com/feed.atom', 'update' => 'hourly', 'tags' => array('google', 'status')),
                    array('type' => 'RSS', 'name' => 'Google Search Central', 'url' => 'https://feeds.feedburner.com/blogspot/amDG', 'update' => 'daily', 'tags' => array('google', 'seo')),
                    array('type' => 'RSS', 'name' => 'Moz Blog', 'url' => 'https://feedpress.me/mozblog', 'update' => 'daily', 'tags' => array('seo', 'marketing')),
                    array('type' => 'RSS', 'name' => 'Smashing Magazine', 'url' => 'https://www.smashingmagazine.com/feed/', 'update' => 'daily', 'tags' => array('web_design', 'development')),
                    array('type' => 'RSS', 'name' => 'Ahrefs Blog', 'url' => 'https://ahrefs.com/blog/feed/', 'update' => 'weekly', 'tags' => array('seo', 'marketing')),
                    array('type' => 'RSS', 'name' => 'SEMrush Blog', 'url' => 'https://www.semrush.com/blog/feed/', 'update' => 'daily', 'tags' => array('seo', 'marketing')),
                    array('type' => 'RSS', 'name' => 'Yoast SEO Blog', 'url' => 'https://yoast.com/feed/', 'update' => 'weekly', 'tags' => array('seo', 'wordpress')),
                    array('type' => 'RSS', 'name' => 'Statista Internet', 'url' => 'https://www.statista.com/rss/statistics/146', 'update' => 'daily', 'tags' => array('statistics', 'internet')),
                    array('type' => 'RSS', 'name' => 'Statista Technology & Telecom', 'url' => 'https://www.statista.com/rss/statistics/155', 'update' => 'daily', 'tags' => array('statistics', 'technology')),
                    array('type' => 'RSS', 'name' => 'Statista Media', 'url' => 'https://www.statista.com/rss/statistics/480', 'update' => 'daily', 'tags' => array('statistics', 'media')),
                    array('type' => 'RSS', 'name' => 'CSS-Tricks', 'url' => 'https://css-tricks.com/feed/', 'update' => 'weekly', 'tags' => array('web_design', 'css', 'development')),
                    array('type' => 'RSS', 'name' => 'A List Apart', 'url' => 'https://alistapart.com/main/feed/', 'update' => 'monthly', 'tags' => array('web_design', 'standards')),
                    array('type' => 'RSS', 'name' => 'Codrops', 'url' => 'https://tympanus.net/codrops/feed/', 'update' => 'weekly', 'tags' => array('web_design', 'trends')),
                    array('type' => 'RSS', 'name' => 'Web Designer Depot', 'url' => 'https://www.webdesignerdepot.com/feed/', 'update' => 'daily', 'tags' => array('web_design', 'news')),
                    array('type' => 'RSS', 'name' => 'SitePoint', 'url' => 'https://www.sitepoint.com/feed/', 'update' => 'daily', 'tags' => array('web_development', 'design')),
                    array('type' => 'RSS', 'name' => 'Shopify Blog', 'url' => 'https://www.shopify.com/blog.atom', 'update' => 'daily', 'tags' => array('ecommerce', 'trends')),
                    array('type' => 'RSS', 'name' => 'Practical Ecommerce', 'url' => 'https://www.practicalecommerce.com/feed', 'update' => 'weekly', 'tags' => array('ecommerce', 'strategy')),
                    array('type' => 'RSS', 'name' => 'eCommerce Fuel', 'url' => 'https://www.ecommercefuel.com/feed/', 'update' => 'weekly', 'tags' => array('ecommerce', 'business')),
                    array('type' => 'RSS', 'name' => 'TechCrunch', 'url' => 'https://techcrunch.com/feed/', 'update' => 'daily', 'tags' => array('technology', 'startups')),
                    array('type' => 'RSS', 'name' => 'The Verge', 'url' => 'https://www.theverge.com/rss/index.xml', 'update' => 'daily', 'tags' => array('technology', 'news')),
                    array('type' => 'RSS', 'name' => 'Wired', 'url' => 'https://www.wired.com/feed/rss', 'update' => 'daily', 'tags' => array('technology', 'culture')),
                    array('type' => 'RSS', 'name' => 'WordPress.org News', 'url' => 'https://wordpress.org/news/feed/', 'update' => 'weekly', 'tags' => array('wordpress', 'updates')),
                    array('type' => 'RSS', 'name' => 'WPBeginner', 'url' => 'https://www.wpbeginner.com/feed/', 'update' => 'daily', 'tags' => array('wordpress', 'tutorials')),
                    array('type' => 'RSS', 'name' => 'WP Tavern', 'url' => 'https://wptavern.com/feed', 'update' => 'daily', 'tags' => array('wordpress', 'news')),
                    array('type' => 'RSS', 'name' => 'Torque', 'url' => 'https://torquemag.io/feed/', 'update' => 'weekly', 'tags' => array('wordpress', 'business')),
                    array('type' => 'RSS', 'name' => 'PPC Hero', 'url' => 'https://www.ppchero.com/feed/', 'update' => 'weekly', 'tags' => array('ppc', 'google_ads')),
                    array('type' => 'RSS', 'name' => 'WordStream Blog', 'url' => 'https://www.wordstream.com/blog/feed', 'update' => 'weekly', 'tags' => array('ppc', 'advertising')),
                    array('type' => 'RSS', 'name' => 'Google Ads Developer Blog', 'url' => 'https://ads-developers.googleblog.com/feeds/posts/default', 'update' => 'weekly', 'tags' => array('google', 'ads', 'developer')),
                    array('type' => 'RSS', 'name' => 'Google Developers', 'url' => 'https://developers.googleblog.com/feeds/posts/default', 'update' => 'weekly', 'tags' => array('google', 'developer', 'tools')),
                    array('type' => 'RSS', 'name' => 'Chrome Developers', 'url' => 'https://developer.chrome.com/feeds/blog.xml', 'update' => 'weekly', 'tags' => array('chrome', 'web_platform')),
                    array('type' => 'RSS', 'name' => 'WooCommerce Blog', 'url' => 'https://woocommerce.com/blog/feed/', 'update' => 'weekly', 'tags' => array('woocommerce', 'ecommerce')),
                    array('type' => 'RSS', 'name' => 'Laravel News', 'url' => 'https://laravel-news.com/feed', 'update' => 'daily', 'tags' => array('laravel', 'php')),
                    array('type' => 'RSS', 'name' => 'React Blog', 'url' => 'https://react.dev/rss.xml', 'update' => 'weekly', 'tags' => array('react', 'javascript')),
                    array('type' => 'RSS', 'name' => 'Cloudflare Blog', 'url' => 'https://blog.cloudflare.com/rss/', 'update' => 'weekly', 'tags' => array('cdn', 'security', 'performance')),
                    array('type' => 'RSS', 'name' => 'Netlify Blog', 'url' => 'https://www.netlify.com/blog/index.xml', 'update' => 'weekly', 'tags' => array('jamstack', 'hosting')),
                    array('type' => 'RSS', 'name' => 'Campaign Monitor Blog', 'url' => 'https://www.campaignmonitor.com/blog/feed/', 'update' => 'weekly', 'tags' => array('email', 'design')),
                    array('type' => 'RSS', 'name' => 'Litmus Blog', 'url' => 'https://www.litmus.com/blog/feed', 'update' => 'weekly', 'tags' => array('email', 'testing')),
                    array('type' => 'RSS', 'name' => 'Figma Blog', 'url' => 'https://www.figma.com/blog/rss/', 'update' => 'weekly', 'tags' => array('design', 'tools')),
                    array('type' => 'RSS', 'name' => 'Canva Design School', 'url' => 'https://www.canva.com/learn/feed/', 'update' => 'weekly', 'tags' => array('design', 'tutorials')),
                ),
            ),
            'benefits' => array(
                'mode' => 'Service + Benefit Semantic Expander',
                'sources' => array(
                    array('type' => 'RSS', 'name' => 'HubSpot Marketing', 'url' => 'https://blog.hubspot.com/marketing/rss.xml', 'update' => 'daily', 'tags' => array('marketing', 'benefits')),
                    array('type' => 'RSS', 'name' => 'Mailchimp Blog', 'url' => 'https://mailchimp.com/feed/', 'update' => 'weekly', 'tags' => array('email', 'marketing')),
                    array('type' => 'HTML', 'name' => 'WordStream Benchmarks', 'url' => 'https://www.wordstream.com/blog/ws/google-ads-industry-benchmarks', 'update' => 'monthly', 'tags' => array('benchmarks', 'ppc')),
                    array('type' => 'HTML', 'name' => 'Mailchimp Benchmarks', 'url' => 'https://mailchimp.com/resources/email-marketing-benchmarks/', 'update' => 'quarterly', 'tags' => array('benchmarks', 'email')),
                    array('type' => 'RSS', 'name' => 'Statista Advertising & Marketing', 'url' => 'https://www.statista.com/rss/statistics/479', 'update' => 'daily', 'tags' => array('statistics', 'marketing', 'advertising')),
                    array('type' => 'RSS', 'name' => 'Statista E-Commerce', 'url' => 'https://www.statista.com/rss/statistics/243', 'update' => 'daily', 'tags' => array('statistics', 'ecommerce')),
                    array('type' => 'RSS', 'name' => 'Statista Retail & Trade', 'url' => 'https://www.statista.com/rss/statistics/481', 'update' => 'daily', 'tags' => array('statistics', 'retail')),
                    array('type' => 'RSS', 'name' => 'Statista Services', 'url' => 'https://www.statista.com/rss/statistics/484', 'update' => 'daily', 'tags' => array('statistics', 'services')),
                    array('type' => 'RSS', 'name' => 'Statista Consumer Goods', 'url' => 'https://www.statista.com/rss/statistics/237', 'update' => 'daily', 'tags' => array('statistics', 'consumer')),
                    array('type' => 'RSS', 'name' => 'Statista Infographics', 'url' => 'https://www.statista.com/rss/infographics', 'update' => 'daily', 'tags' => array('statistics', 'infographics')),
                    array('type' => 'RSS', 'name' => 'Marketing Week', 'url' => 'https://www.marketingweek.com/feed/', 'update' => 'hourly', 'tags' => array('uk', 'marketing', 'news')),
                    array('type' => 'RSS', 'name' => 'Campaign UK', 'url' => 'https://www.campaignlive.co.uk/rss', 'update' => 'daily', 'tags' => array('uk', 'advertising', 'marketing')),
                    array('type' => 'RSS', 'name' => 'The Drum', 'url' => 'https://www.thedrum.com/feed', 'update' => 'daily', 'tags' => array('uk', 'marketing', 'advertising')),
                    array('type' => 'RSS', 'name' => 'Smart Insights', 'url' => 'https://www.smartinsights.com/feed/', 'update' => 'weekly', 'tags' => array('digital_marketing', 'strategy')),
                    array('type' => 'RSS', 'name' => 'Econsultancy', 'url' => 'https://econsultancy.com/feed/', 'update' => 'weekly', 'tags' => array('digital_marketing', 'ecommerce')),
                    array('type' => 'RSS', 'name' => 'Content Marketing Institute', 'url' => 'https://contentmarketinginstitute.com/feed/', 'update' => 'weekly', 'tags' => array('content', 'marketing')),
                    array('type' => 'RSS', 'name' => 'Copyblogger', 'url' => 'https://copyblogger.com/feed/', 'update' => 'weekly', 'tags' => array('copywriting', 'content')),
                    array('type' => 'RSS', 'name' => 'Neil Patel Blog', 'url' => 'https://neilpatel.com/feed/', 'update' => 'weekly', 'tags' => array('digital_marketing', 'seo')),
                    array('type' => 'RSS', 'name' => 'Social Media Examiner', 'url' => 'https://www.socialmediaexaminer.com/feed/', 'update' => 'weekly', 'tags' => array('social_media', 'marketing')),
                    array('type' => 'RSS', 'name' => 'Social Media Today', 'url' => 'https://www.socialmediatoday.com/rss.xml', 'update' => 'daily', 'tags' => array('social_media', 'news')),
                    array('type' => 'RSS', 'name' => 'Buffer Blog', 'url' => 'https://buffer.com/resources/feed/', 'update' => 'weekly', 'tags' => array('social_media', 'analytics')),
                    array('type' => 'RSS', 'name' => 'Hootsuite Blog', 'url' => 'https://blog.hootsuite.com/feed/', 'update' => 'weekly', 'tags' => array('social_media', 'management')),
                    array('type' => 'RSS', 'name' => 'Sprout Social Insights', 'url' => 'https://sproutsocial.com/insights/feed/', 'update' => 'weekly', 'tags' => array('social_media', 'analytics')),
                ),
            ),
            'seasonal' => array(
                'mode' => 'Seasonal Service Angle Rotator',
                'sources' => array(
                    array('type' => 'API', 'name' => 'BigQuery Google Trends', 'url' => 'bigquery://bigquery-public-data.google_trends.top_terms', 'update' => 'daily', 'tags' => array('google_trends', 'trending', 'bigquery')),
                    array('type' => 'API', 'name' => 'UK Bank Holidays', 'url' => 'https://www.gov.uk/bank-holidays.json', 'update' => 'monthly', 'tags' => array('uk', 'holidays')),
                    array('type' => 'RSS', 'name' => 'Google Trends Daily', 'url' => 'https://trends.google.com/trends/trendingsearches/daily/rss?geo=GB', 'update' => 'daily', 'tags' => array('trends', 'seasonal')),
                ),
            ),
            'process' => array(
                'mode' => 'Service Process Micro-Step Enhancer',
                'sources' => array(
                    array('type' => 'RSS', 'name' => 'Nielsen Norman Group', 'url' => 'https://www.nngroup.com/feed/rss/', 'update' => 'weekly', 'tags' => array('ux', 'process')),
                    array('type' => 'RSS', 'name' => 'UX Collective', 'url' => 'https://uxdesign.cc/feed', 'update' => 'daily', 'tags' => array('ux', 'design')),
                    array('type' => 'RSS', 'name' => 'Smashing Magazine UX', 'url' => 'https://www.smashingmagazine.com/category/ux-design/feed/', 'update' => 'daily', 'tags' => array('ux', 'design')),
                    array('type' => 'RSS', 'name' => 'Interaction Design Foundation', 'url' => 'https://www.interaction-design.org/literature/rss', 'update' => 'weekly', 'tags' => array('ux', 'interaction_design')),
                    array('type' => 'RSS', 'name' => 'Google Analytics Blog', 'url' => 'https://blog.google/products/marketingplatform/analytics/feed/', 'update' => 'monthly', 'tags' => array('analytics', 'google')),
                    array('type' => 'RSS', 'name' => 'Analytics Vidhya', 'url' => 'https://www.analyticsvidhya.com/feed/', 'update' => 'weekly', 'tags' => array('analytics', 'data_science')),
                    array('type' => 'RSS', 'name' => 'ConversionXL', 'url' => 'https://cxl.com/blog/feed/', 'update' => 'weekly', 'tags' => array('cro', 'optimization')),
                    array('type' => 'RSS', 'name' => 'Unbounce Blog', 'url' => 'https://unbounce.com/blog/feed/', 'update' => 'weekly', 'tags' => array('landing_pages', 'conversion')),
                    array('type' => 'RSS', 'name' => 'VWO Blog', 'url' => 'https://vwo.com/blog/feed/', 'update' => 'weekly', 'tags' => array('ab_testing', 'optimization')),
                    array('type' => 'RSS', 'name' => 'WebAIM Blog', 'url' => 'https://webaim.org/blog/feed', 'update' => 'weekly', 'tags' => array('accessibility', 'standards')),
                    array('type' => 'RSS', 'name' => 'W3C News', 'url' => 'https://www.w3.org/blog/news/feed', 'update' => 'weekly', 'tags' => array('web_standards', 'w3c')),
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

