<?php
/**
 * AI-Stats Web Scraper Class
 *
 * Handles web scraping and data fetching from various sources
 * Uses WordPress HTTP API for all external requests
 *
 * @package AI_Stats
 * @version 0.2.2
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Web scraper class
 */
class AI_Stats_Scraper {
    
    /**
     * Singleton instance
     * 
     * @var AI_Stats_Scraper
     */
    private static $instance = null;
    
    /**
     * Get instance
     * 
     * @return AI_Stats_Scraper
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Fetch Birmingham business statistics
     * 
     * @return array|WP_Error Statistics data or error
     */
    public function fetch_birmingham_stats() {
        $cache_key = 'birmingham_stats_' . date('Y-m-d');
        
        // Check cache first
        $cached = AI_Stats_Database::get_cache($cache_key);
        if ($cached !== null) {
            return $cached;
        }
        
        $stats = array();
        
        // Fetch from multiple sources
        $sources = array(
            'chamber' => 'https://www.greaterbirminghamchambers.com/',
            'ons' => 'https://www.ons.gov.uk/',
            'birmingham_gov' => 'https://www.birmingham.gov.uk/info/50119/business',
        );
        
        foreach ($sources as $source_key => $url) {
            $data = $this->fetch_url($url);
            if (!is_wp_error($data)) {
                $stats[$source_key] = $this->extract_statistics($data, $source_key);
            }
        }
        
        // Cache for 24 hours
        if (!empty($stats)) {
            AI_Stats_Database::set_cache($cache_key, 'birmingham_stats', $stats, '', 86400);
        }
        
        return $stats;
    }
    
    /**
     * Fetch industry trends from RSS feeds
     * 
     * @return array|WP_Error Trends data or error
     */
    public function fetch_industry_trends() {
        $cache_key = 'industry_trends_' . date('Y-m-d');
        
        // Check cache first
        $cached = AI_Stats_Database::get_cache($cache_key);
        if ($cached !== null) {
            return $cached;
        }
        
        $trends = array();
        
        // RSS feed sources
        $feeds = array(
            'search_engine_land' => 'https://searchengineland.com/feed',
            'moz' => 'https://moz.com/blog/feed',
            'google_blog' => 'https://developers.google.com/search/blog/feed',
            'smashing_magazine' => 'https://www.smashingmagazine.com/feed/',
        );
        
        foreach ($feeds as $feed_key => $feed_url) {
            $feed_data = $this->fetch_rss_feed($feed_url);
            if (!is_wp_error($feed_data)) {
                $trends[$feed_key] = $feed_data;
            }
        }
        
        // Cache for 12 hours
        if (!empty($trends)) {
            AI_Stats_Database::set_cache($cache_key, 'industry_trends', $trends, '', 43200);
        }
        
        return $trends;
    }
    
    /**
     * Fetch general business statistics
     * 
     * @param string $industry Optional industry filter
     * @return array|WP_Error Statistics or error
     */
    public function fetch_business_stats($industry = '') {
        $cache_key = 'business_stats_' . md5($industry) . '_' . date('Y-m-d');
        
        // Check cache first
        $cached = AI_Stats_Database::get_cache($cache_key);
        if ($cached !== null) {
            return $cached;
        }
        
        $stats = array();
        
        // Fetch from various business statistics sources
        $sources = array(
            'hubspot' => 'https://www.hubspot.com/marketing-statistics',
            'statista' => 'https://www.statista.com/',
        );
        
        foreach ($sources as $source_key => $url) {
            $data = $this->fetch_url($url);
            if (!is_wp_error($data)) {
                $stats[$source_key] = $this->extract_statistics($data, $source_key);
            }
        }
        
        // Cache for 7 days
        if (!empty($stats)) {
            AI_Stats_Database::set_cache($cache_key, 'business_stats', $stats, '', 604800);
        }
        
        return $stats;
    }
    
    /**
     * Fetch URL content
     * 
     * @param string $url URL to fetch
     * @param int $timeout Request timeout in seconds
     * @return string|WP_Error Response body or error
     */
    private function fetch_url($url, $timeout = 30) {
        $response = wp_remote_get($url, array(
            'timeout' => $timeout,
            'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'sslverify' => true,
        ));
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            return new WP_Error('http_error', sprintf('HTTP %d error', $response_code));
        }
        
        return wp_remote_retrieve_body($response);
    }
    
    /**
     * Fetch and parse RSS feed
     * 
     * @param string $feed_url RSS feed URL
     * @param int $limit Number of items to return
     * @return array|WP_Error Feed items or error
     */
    private function fetch_rss_feed($feed_url, $limit = 10) {
        $feed = fetch_feed($feed_url);
        
        if (is_wp_error($feed)) {
            return $feed;
        }
        
        $items = array();
        $max_items = $feed->get_item_quantity($limit);
        $feed_items = $feed->get_items(0, $max_items);
        
        foreach ($feed_items as $item) {
            $items[] = array(
                'title' => $item->get_title(),
                'link' => $item->get_permalink(),
                'description' => $item->get_description(),
                'date' => $item->get_date('Y-m-d H:i:s'),
                'content' => $item->get_content(),
            );
        }
        
        return $items;
    }
    
    /**
     * Extract statistics from HTML content
     * 
     * @param string $html HTML content
     * @param string $source Source identifier
     * @return array Extracted statistics
     */
    private function extract_statistics($html, $source) {
        $stats = array();
        
        // Use DOMDocument to parse HTML
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML($html);
        libxml_clear_errors();
        
        // Extract based on source
        switch ($source) {
            case 'chamber':
                $stats = $this->extract_chamber_stats($dom);
                break;
            case 'ons':
                $stats = $this->extract_ons_stats($dom);
                break;
            case 'birmingham_gov':
                $stats = $this->extract_birmingham_gov_stats($dom);
                break;
            case 'hubspot':
                $stats = $this->extract_hubspot_stats($dom);
                break;
            default:
                $stats = $this->extract_generic_stats($dom);
        }
        
        return $stats;
    }
    
    /**
     * Extract statistics from Birmingham Chamber of Commerce
     * 
     * @param DOMDocument $dom DOM document
     * @return array Statistics
     */
    private function extract_chamber_stats($dom) {
        $stats = array();
        
        // Look for common statistical patterns
        $xpath = new DOMXPath($dom);
        
        // Find numbers followed by business-related keywords
        $patterns = array(
            'businesses',
            'companies',
            'enterprises',
            'members',
            'growth',
            'economy',
        );
        
        foreach ($patterns as $pattern) {
            $nodes = $xpath->query("//text()[contains(translate(., 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz'), '$pattern')]");
            
            foreach ($nodes as $node) {
                $text = $node->nodeValue;
                // Extract numbers with commas or percentages
                if (preg_match('/(\d{1,3}(?:,\d{3})*|\d+(?:\.\d+)?%?)/', $text, $matches)) {
                    $stats[] = array(
                        'value' => $matches[1],
                        'context' => trim($text),
                        'keyword' => $pattern,
                    );
                }
            }
        }
        
        return $stats;
    }
    
    /**
     * Extract statistics from ONS
     * 
     * @param DOMDocument $dom DOM document
     * @return array Statistics
     */
    private function extract_ons_stats($dom) {
        $stats = array();
        
        // ONS typically has structured data
        $xpath = new DOMXPath($dom);
        
        // Look for statistical tables and data points
        $tables = $xpath->query('//table');
        
        foreach ($tables as $table) {
            $rows = $xpath->query('.//tr', $table);
            foreach ($rows as $row) {
                $cells = $xpath->query('.//td', $row);
                if ($cells->length >= 2) {
                    $label = trim($cells->item(0)->nodeValue);
                    $value = trim($cells->item(1)->nodeValue);
                    
                    if (preg_match('/\d/', $value)) {
                        $stats[] = array(
                            'label' => $label,
                            'value' => $value,
                            'source' => 'ONS',
                        );
                    }
                }
            }
        }
        
        return $stats;
    }
    
    /**
     * Extract statistics from Birmingham.gov.uk
     * 
     * @param DOMDocument $dom DOM document
     * @return array Statistics
     */
    private function extract_birmingham_gov_stats($dom) {
        return $this->extract_generic_stats($dom);
    }
    
    /**
     * Extract statistics from HubSpot
     * 
     * @param DOMDocument $dom DOM document
     * @return array Statistics
     */
    private function extract_hubspot_stats($dom) {
        return $this->extract_generic_stats($dom);
    }
    
    /**
     * Extract generic statistics from any HTML
     * 
     * @param DOMDocument $dom DOM document
     * @return array Statistics
     */
    private function extract_generic_stats($dom) {
        $stats = array();
        $xpath = new DOMXPath($dom);
        
        // Find all text nodes
        $text_nodes = $xpath->query('//text()');
        
        foreach ($text_nodes as $node) {
            $text = trim($node->nodeValue);
            
            // Look for percentage patterns
            if (preg_match('/(\d+(?:\.\d+)?%)/i', $text, $matches)) {
                $stats[] = array(
                    'type' => 'percentage',
                    'value' => $matches[1],
                    'context' => substr($text, 0, 200),
                );
            }
            
            // Look for large numbers (with commas)
            if (preg_match('/(\d{1,3}(?:,\d{3})+)/', $text, $matches)) {
                $stats[] = array(
                    'type' => 'number',
                    'value' => $matches[1],
                    'context' => substr($text, 0, 200),
                );
            }
        }
        
        return array_slice($stats, 0, 20); // Limit to 20 stats
    }
}

