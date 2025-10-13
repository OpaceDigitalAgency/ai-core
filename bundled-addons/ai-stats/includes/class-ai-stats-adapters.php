<?php
/**
 * AI-Stats Adapters Class
 *
 * Handles fetching and normalising data from various source types
 * Returns uniform candidate schema for all sources
 *
 * @package AI_Stats
 * @version 0.2.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Adapters class
 */
class AI_Stats_Adapters {
    
    /**
     * Singleton instance
     * 
     * @var AI_Stats_Adapters
     */
    private static $instance = null;
    
    /**
     * Get instance
     * 
     * @return AI_Stats_Adapters
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Fetch candidates from multiple sources
     * 
     * @param string $mode Mode key
     * @param array $tags Optional tag filters
     * @param array $keywords Optional keyword filters
     * @param int $limit Maximum number of candidates to return
     * @return array Normalised candidates
     */
    public function fetch_candidates($mode, $tags = array(), $keywords = array(), $limit = 12) {
        $registry = AI_Stats_Source_Registry::get_instance();
        $sources = $registry->get_sources_for_mode($mode);
        
        $all_candidates = array();
        
        foreach ($sources as $source) {
            $candidates = $this->fetch_from_source($source);
            
            if (!is_wp_error($candidates) && !empty($candidates)) {
                $all_candidates = array_merge($all_candidates, $candidates);
            }
        }
        
        // Filter by keywords if provided
        if (!empty($keywords)) {
            $all_candidates = $this->filter_by_keywords($all_candidates, $keywords);
        }
        
        // Score and sort candidates
        $all_candidates = $this->score_candidates($all_candidates);
        
        // Return top N
        return array_slice($all_candidates, 0, $limit);
    }
    
    /**
     * Fetch from a single source
     *
     * @param array $source Source configuration
     * @return array|WP_Error Candidates or error
     */
    private function fetch_from_source($source) {
        // Check cache first (short TTL for manual testing)
        $cache_key = 'ai_stats_fetch_' . md5($source['url']);
        $cached = get_transient($cache_key);

        if ($cached !== false) {
            return $cached;
        }

        $candidates = array();

        try {
            switch ($source['type']) {
                case 'RSS':
                    $candidates = $this->fetch_rss($source);
                    break;

                case 'API':
                    $candidates = $this->fetch_api($source);
                    break;

                case 'HTML':
                    $candidates = $this->fetch_html($source);
                    break;
            }

            // Log for debugging
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log(sprintf(
                    'AI-Stats: Fetched %d candidates from %s (%s)',
                    count($candidates),
                    $source['name'],
                    $source['type']
                ));
            }

        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('AI-Stats fetch error: ' . $e->getMessage());
            }
            return new WP_Error('fetch_failed', $e->getMessage());
        }

        // Cache for 10 minutes (manual testing mode)
        if (!is_wp_error($candidates) && !empty($candidates)) {
            set_transient($cache_key, $candidates, 600);
        }

        return $candidates;
    }
    
    /**
     * Fetch from RSS feed
     *
     * @param array $source Source configuration
     * @return array Normalised candidates
     */
    private function fetch_rss($source) {
        $feed = fetch_feed($source['url']);

        if (is_wp_error($feed)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log(sprintf(
                    'AI-Stats RSS error for %s: %s',
                    $source['name'],
                    $feed->get_error_message()
                ));
            }
            return array();
        }

        $candidates = array();
        $items = $feed->get_items(0, 10);

        if (empty($items)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log(sprintf('AI-Stats: No items in RSS feed for %s', $source['name']));
            }
            return array();
        }

        foreach ($items as $item) {
            $published = $item->get_date('c');
            if (empty($published)) {
                $published = date('c');
            }

            $candidates[] = array(
                'title' => $item->get_title() ?: 'Untitled',
                'source' => $source['name'],
                'url' => $item->get_permalink() ?: $source['url'],
                'published_at' => $published,
                'tags' => $source['tags'] ?? array(),
                'blurb_seed' => $this->extract_blurb($item->get_description() ?: $item->get_title()),
                'geo' => $this->extract_geo($source),
                'confidence' => 0.85,
            );
        }

        return $candidates;
    }
    
    /**
     * Fetch from API
     * 
     * @param array $source Source configuration
     * @return array Normalised candidates
     */
    private function fetch_api($source) {
        $candidates = array();
        
        // Route to specific API handler based on source name
        if (strpos($source['name'], 'ONS') !== false) {
            $candidates = $this->fetch_ons_api($source);
        } elseif (strpos($source['name'], 'Companies House') !== false) {
            $candidates = $this->fetch_companies_house_api($source);
        } elseif (strpos($source['name'], 'CrUX') !== false) {
            $candidates = $this->fetch_crux_api($source);
        } elseif (strpos($source['name'], 'Bank Holidays') !== false) {
            $candidates = $this->fetch_bank_holidays_api($source);
        } else {
            // Generic API fetch
            $candidates = $this->fetch_generic_api($source);
        }
        
        return $candidates;
    }
    
    /**
     * Fetch from HTML page
     * 
     * @param array $source Source configuration
     * @return array Normalised candidates
     */
    private function fetch_html($source) {
        $response = wp_remote_get($source['url'], array(
            'timeout' => 30,
            'user-agent' => 'Mozilla/5.0 (compatible; AI-Stats/0.2.0)',
        ));
        
        if (is_wp_error($response)) {
            return array();
        }
        
        $html = wp_remote_retrieve_body($response);
        
        // Extract statistics from HTML
        return $this->extract_from_html($html, $source);
    }
    
    /**
     * Fetch from ONS API
     * 
     * @param array $source Source configuration
     * @return array Normalised candidates
     */
    private function fetch_ons_api($source) {
        // Example: Fetch retail sales data
        $endpoint = 'https://api.ons.gov.uk/timeseries/J4MC/dataset/DRSI/data';
        
        $response = wp_remote_get($endpoint, array(
            'timeout' => 30,
        ));
        
        if (is_wp_error($response)) {
            return array();
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        if (empty($data)) {
            return array();
        }
        
        $candidates = array();
        
        // Parse ONS response structure
        if (isset($data['months']) && is_array($data['months'])) {
            $recent = array_slice($data['months'], -3);
            
            foreach ($recent as $month) {
                $candidates[] = array(
                    'title' => 'UK Retail Sales: ' . ($month['value'] ?? 'N/A'),
                    'source' => 'ONS',
                    'url' => 'https://www.ons.gov.uk/businessindustryandtrade/retailindustry',
                    'published_at' => $month['date'] ?? date('c'),
                    'tags' => array('uk_macro', 'retail', 'statistics'),
                    'blurb_seed' => sprintf('UK retail sales index at %s for %s', $month['value'] ?? 'N/A', $month['date'] ?? 'recent period'),
                    'geo' => 'GB',
                    'confidence' => 0.95,
                );
            }
        }
        
        return $candidates;
    }
    
    /**
     * Fetch from Companies House API
     * 
     * @param array $source Source configuration
     * @return array Normalised candidates
     */
    private function fetch_companies_house_api($source) {
        // Note: Requires API key - check settings
        $settings = get_option('ai_stats_settings', array());
        $api_key = $settings['companies_house_api_key'] ?? '';
        
        if (empty($api_key)) {
            return array();
        }
        
        // Example: Search for recent incorporations
        $endpoint = 'https://api.company-information.service.gov.uk/search/companies';
        
        $response = wp_remote_get($endpoint, array(
            'timeout' => 30,
            'headers' => array(
                'Authorization' => 'Basic ' . base64_encode($api_key . ':'),
            ),
        ));
        
        if (is_wp_error($response)) {
            return array();
        }
        
        // Parse and return candidates
        return array();
    }
    
    /**
     * Fetch from CrUX API
     * 
     * @param array $source Source configuration
     * @return array Normalised candidates
     */
    private function fetch_crux_api($source) {
        $settings = get_option('ai_stats_settings', array());
        $api_key = $settings['google_api_key'] ?? '';
        $test_url = $settings['crux_test_url'] ?? get_site_url();
        
        if (empty($api_key)) {
            return array();
        }
        
        $endpoint = 'https://chromeuxreport.googleapis.com/v1/records:queryRecord?key=' . $api_key;
        
        $response = wp_remote_post($endpoint, array(
            'timeout' => 30,
            'headers' => array('Content-Type' => 'application/json'),
            'body' => wp_json_encode(array(
                'origin' => $test_url,
                'formFactor' => 'DESKTOP',
            )),
        ));
        
        if (is_wp_error($response)) {
            return array();
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        
        // Parse CrUX metrics
        $candidates = array();
        
        if (isset($data['record']['metrics'])) {
            $candidates[] = array(
                'title' => 'Core Web Vitals Performance',
                'source' => 'Chrome UX Report',
                'url' => 'https://developers.google.com/web/tools/chrome-user-experience-report',
                'published_at' => date('c'),
                'tags' => array('performance', 'web_vitals'),
                'blurb_seed' => 'Real-user performance data from Chrome UX Report',
                'geo' => 'GB',
                'confidence' => 0.90,
            );
        }
        
        return $candidates;
    }
    
    /**
     * Fetch UK Bank Holidays
     * 
     * @param array $source Source configuration
     * @return array Normalised candidates
     */
    private function fetch_bank_holidays_api($source) {
        $response = wp_remote_get('https://www.gov.uk/bank-holidays.json', array(
            'timeout' => 30,
        ));
        
        if (is_wp_error($response)) {
            return array();
        }
        
        $data = json_decode(wp_remote_retrieve_body($response), true);
        $candidates = array();
        
        if (isset($data['england-and-wales']['events'])) {
            $upcoming = array_filter($data['england-and-wales']['events'], function($event) {
                return strtotime($event['date']) > time();
            });
            
            $upcoming = array_slice($upcoming, 0, 3);
            
            foreach ($upcoming as $event) {
                $candidates[] = array(
                    'title' => $event['title'],
                    'source' => 'GOV.UK',
                    'url' => 'https://www.gov.uk/bank-holidays',
                    'published_at' => $event['date'],
                    'tags' => array('uk', 'holidays', 'seasonal'),
                    'blurb_seed' => sprintf('Upcoming UK bank holiday: %s on %s', $event['title'], date('j F Y', strtotime($event['date']))),
                    'geo' => 'GB',
                    'confidence' => 1.0,
                );
            }
        }
        
        return $candidates;
    }

    /**
     * Fetch from generic API
     *
     * @param array $source Source configuration
     * @return array Normalised candidates
     */
    private function fetch_generic_api($source) {
        $response = wp_remote_get($source['url'], array(
            'timeout' => 30,
        ));

        if (is_wp_error($response)) {
            return array();
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);

        // Generic parsing - override for specific APIs
        return array();
    }

    /**
     * Extract statistics from HTML
     *
     * @param string $html HTML content
     * @param array $source Source configuration
     * @return array Normalised candidates
     */
    private function extract_from_html($html, $source) {
        $candidates = array();

        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML($html);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);
        $text_nodes = $xpath->query('//text()');

        foreach ($text_nodes as $node) {
            $text = trim($node->nodeValue);

            // Look for percentage patterns
            if (preg_match('/(\d+(?:\.\d+)?%)/i', $text, $matches)) {
                $candidates[] = array(
                    'title' => substr($text, 0, 100),
                    'source' => $source['name'],
                    'url' => $source['url'],
                    'published_at' => date('c'),
                    'tags' => $source['tags'] ?? array(),
                    'blurb_seed' => substr($text, 0, 200),
                    'geo' => $this->extract_geo($source),
                    'confidence' => 0.70,
                );
            }
        }

        return array_slice($candidates, 0, 5);
    }

    /**
     * Extract blurb from description
     *
     * @param string $description Description text
     * @return string Blurb
     */
    private function extract_blurb($description) {
        $text = wp_strip_all_tags($description);
        $text = preg_replace('/\s+/', ' ', $text);
        return substr($text, 0, 200);
    }

    /**
     * Extract geo from source
     *
     * @param array $source Source configuration
     * @return string Geo code
     */
    private function extract_geo($source) {
        $tags = $source['tags'] ?? array();

        if (in_array('uk', $tags) || in_array('uk_macro', $tags) || in_array('birmingham', $tags)) {
            return 'GB';
        }

        if (in_array('eu_stats', $tags)) {
            return 'EU';
        }

        return 'GLOBAL';
    }

    /**
     * Filter candidates by keywords
     *
     * @param array $candidates Candidates
     * @param array $keywords Keywords to filter by
     * @return array Filtered candidates
     */
    private function filter_by_keywords($candidates, $keywords) {
        if (empty($keywords)) {
            return $candidates;
        }

        return array_filter($candidates, function($candidate) use ($keywords) {
            $text = strtolower($candidate['title'] . ' ' . $candidate['blurb_seed']);

            foreach ($keywords as $keyword) {
                if (stripos($text, strtolower($keyword)) !== false) {
                    return true;
                }
            }

            return false;
        });
    }

    /**
     * Score candidates
     *
     * @param array $candidates Candidates
     * @return array Scored and sorted candidates
     */
    private function score_candidates($candidates) {
        foreach ($candidates as &$candidate) {
            $score = 0;

            // Freshness score (newer = higher)
            $age_days = (time() - strtotime($candidate['published_at'])) / 86400;
            if ($age_days < 1) {
                $score += 50;
            } elseif ($age_days < 7) {
                $score += 30;
            } elseif ($age_days < 30) {
                $score += 10;
            }

            // Source weight (authoritative sources score higher)
            $authoritative_sources = array('ONS', 'GOV.UK', 'Google', 'Eurostat', 'Companies House');
            foreach ($authoritative_sources as $auth_source) {
                if (stripos($candidate['source'], $auth_source) !== false) {
                    $score += 30;
                    break;
                }
            }

            // Confidence score
            $score += $candidate['confidence'] * 20;

            $candidate['score'] = $score;
        }

        // Sort by score descending
        usort($candidates, function($a, $b) {
            return $b['score'] - $a['score'];
        });

        return $candidates;
    }
}
