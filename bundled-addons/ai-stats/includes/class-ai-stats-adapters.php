<?php
/**
 * AI-Stats Adapters Class
 *
 * Handles fetching and normalising data from various source types
 * Returns uniform candidate schema for all sources
 *
 * @package AI_Stats
 * @version 0.7.2
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

        // Filter by keywords if provided (with AI expansion for better matching)
        $scoring_keywords = $keywords; // Default to original keywords
        if (!empty($keywords) && !empty($all_candidates)) {
            // Expand keywords with AI for better matching (same as debug pipeline)
            $expansion_result = $this->expand_keywords_with_ai($keywords);
            $expanded_keywords = $expansion_result['keywords'];

            // Filter using expanded keywords
            $all_candidates = $this->filter_by_keywords($all_candidates, $expanded_keywords, false);

            // Use expanded keywords for scoring
            $scoring_keywords = $expanded_keywords;

            // Log expansion for debugging
            if (defined('WP_DEBUG') && WP_DEBUG && $expansion_result['success']) {
                error_log(sprintf(
                    'AI-Stats Production: Expanded %d keywords to %d terms for mode %s',
                    count($keywords),
                    count($expanded_keywords),
                    $mode
                ));
            }
        } elseif (!empty($keywords)) {
            // No candidates to filter, just use original keywords
            $all_candidates = $this->filter_by_keywords($all_candidates, $keywords);
        }

        // Score and sort candidates (pass expanded keywords and mode for better scoring)
        $all_candidates = $this->score_candidates($all_candidates, $scoring_keywords, $mode);

        // Return top N
        return array_slice($all_candidates, 0, $limit);
    }

    /**
     * Fetch candidates with full pipeline debug data
     *
     * @param string $mode Mode key
     * @param array $keywords Optional keyword filters
     * @param int $limit Maximum number of candidates to return
     * @return array Pipeline data with debug info
     */
    public function fetch_candidates_debug($mode, $keywords = array(), $limit = 12) {
        $registry = AI_Stats_Source_Registry::get_instance();
        $sources = $registry->get_sources_for_mode($mode);

        $pipeline = array(
            'mode' => $mode,
            'keywords' => $keywords,
            'expanded_keywords' => array(), // AI-expanded keywords
            'keyword_expansion' => array(), // Keyword expansion metadata (prompt, provider, model, etc.)
            'sources' => array(),
            'fetch_results' => array(),
            'all_candidates' => array(), // Store ALL candidates for accurate counting
            'normalised_count' => 0,
            'filtered_candidates' => array(), // Store ALL filtered candidates
            'filtered_count' => 0,
            'filter_removed' => 0,
            'ranked_candidates' => array(),
            'final_candidates' => array(),
            'errors' => array(),
            'performance' => array(), // Performance metrics
        );

        // Step 1: Fetch from all sources in parallel batches
        $batch_results = $this->fetch_sources_parallel($sources);

        $all_candidates = array();
        foreach ($batch_results as $result) {
            $source_debug = array(
                'name' => $result['source']['name'],
                'type' => $result['source']['type'],
                'url' => $result['source']['url'],
                'status' => $result['status'],
                'candidates_count' => $result['candidates_count'],
                'error' => $result['error'],
                'fetch_time' => $result['fetch_time'],
            );

            if ($result['status'] === 'success' && !empty($result['candidates'])) {
                $all_candidates = array_merge($all_candidates, $result['candidates']);
            } elseif ($result['status'] === 'error') {
                $pipeline['errors'][] = $result['source']['name'] . ': ' . $result['error'];
            }

            $pipeline['sources'][] = $source_debug;
        }

        $pipeline['normalised_count'] = count($all_candidates);
        $pipeline['all_candidates'] = $all_candidates; // Store ALL for JavaScript
        $pipeline['fetch_results'] = array_slice($all_candidates, 0, 20); // First 20 for preview

        // Step 2: Filter by keywords (with AI expansion)
        $before_filter = count($all_candidates);
        if (!empty($keywords) && !empty($all_candidates)) {
            // Expand keywords with AI and capture metadata
            $expansion_start = microtime(true);
            $expansion_result = $this->expand_keywords_with_ai($keywords);
            $expansion_time = microtime(true) - $expansion_start;

            $pipeline['expanded_keywords'] = $expansion_result['keywords'];
            $pipeline['keyword_expansion'] = array(
                'prompt' => $expansion_result['prompt'],
                'provider' => $expansion_result['provider'],
                'model' => $expansion_result['model'],
                'original_count' => count($keywords),
                'expanded_count' => count($expansion_result['keywords']),
                'synonyms_added' => count($expansion_result['keywords']) - count($keywords),
                'execution_time_ms' => round($expansion_time * 1000, 2),
                'success' => $expansion_result['success'],
                'error' => $expansion_result['error'] ?? null,
            );

            // Filter using expanded keywords (pass false to avoid double expansion)
            $all_candidates = $this->filter_by_keywords($all_candidates, $expansion_result['keywords'], false);
        } elseif (!empty($keywords) && empty($all_candidates)) {
            // Log that we skipped AI expansion because there are no candidates
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('AI-Stats: Skipped keyword expansion - no candidates to filter');
            }
        }
        $pipeline['filtered_count'] = count($all_candidates);
        $pipeline['filter_removed'] = $before_filter - count($all_candidates);
        $pipeline['filtered_candidates'] = $all_candidates; // Store ALL filtered for JavaScript

        // Step 3: Score and rank (pass expanded keywords and mode for better scoring)
        $scoring_keywords = !empty($pipeline['expanded_keywords']) ? $pipeline['expanded_keywords'] : $keywords;
        $all_candidates = $this->score_candidates($all_candidates, $scoring_keywords, $mode);
        $pipeline['ranked_candidates'] = array_slice($all_candidates, 0, 20); // Top 20 for inspection

        // Step 4: Final selection
        $pipeline['final_candidates'] = array_slice($all_candidates, 0, $limit);

        // Performance metrics
        $pipeline['performance']['total_candidates'] = count($pipeline['all_candidates']);
        $pipeline['performance']['filtered_candidates'] = count($pipeline['filtered_candidates']);
        $pipeline['performance']['data_size_estimate_kb'] = round(
            (strlen(json_encode($pipeline['all_candidates'])) + strlen(json_encode($pipeline['filtered_candidates']))) / 1024,
            2
        );

        // Optimize for browser: Limit data sent if too large (>500 candidates)
        // Store counts but send only preview slices to avoid large JSON payloads
        if (count($pipeline['all_candidates']) > 500) {
            $pipeline['performance']['optimization_applied'] = true;
            $pipeline['performance']['all_candidates_truncated'] = true;
            $pipeline['all_candidates'] = array_slice($pipeline['all_candidates'], 0, 100); // First 100 only
        }

        if (count($pipeline['filtered_candidates']) > 500) {
            $pipeline['performance']['filtered_candidates_truncated'] = true;
            $pipeline['filtered_candidates'] = array_slice($pipeline['filtered_candidates'], 0, 100); // First 100 only
        }

        return $pipeline;
    }

    /**
     * Fetch from multiple sources in parallel batches
     *
     * @param array $sources Array of source configurations
     * @param int $batch_size Number of sources to fetch simultaneously
     * @return array Results for all sources
     */
    private function fetch_sources_parallel($sources, $batch_size = 10) {
        $results = array();
        $total = count($sources);
        
        // Process sources in batches
        for ($i = 0; $i < $total; $i += $batch_size) {
            $batch = array_slice($sources, $i, $batch_size);
            $batch_results = $this->fetch_batch_parallel($batch);
            $results = array_merge($results, $batch_results);
        }
        
        return $results;
    }

    /**
     * Fetch a batch of sources in parallel using cURL multi-handle
     *
     * @param array $batch Array of source configurations
     * @return array Results for this batch
     */
    private function fetch_batch_parallel($batch) {
        $results = array();
        $mh = curl_multi_init();
        $handles = array();
        $start_times = array();
        
        // Initialize all cURL handles for this batch
        foreach ($batch as $index => $source) {
            $start_times[$index] = microtime(true);
            
            // For RSS and HTML sources, we can fetch in parallel
            // API sources might need authentication, so we'll still use the normal method
            if ($source['type'] === 'API') {
                // API sources use custom logic, fetch individually
                $start_time = microtime(true);
                $candidates = $this->fetch_from_source($source);
                $fetch_time = round((microtime(true) - $start_time) * 1000, 2);
                
                $results[] = array(
                    'source' => $source,
                    'candidates' => is_wp_error($candidates) ? array() : $candidates,
                    'status' => is_wp_error($candidates) ? 'error' : (empty($candidates) ? 'empty' : 'success'),
                    'error' => is_wp_error($candidates) ? $candidates->get_error_message() : null,
                    'candidates_count' => is_wp_error($candidates) ? 0 : count($candidates),
                    'fetch_time' => $fetch_time,
                );
                continue;
            }
            
            // Check cache first
            $cache_key = 'ai_stats_fetch_' . md5($source['url']);
            $cached = get_transient($cache_key);
            
            if ($cached !== false) {
                $results[] = array(
                    'source' => $source,
                    'candidates' => $cached,
                    'status' => empty($cached) ? 'empty' : 'success',
                    'error' => null,
                    'candidates_count' => count($cached),
                    'fetch_time' => 0,
                );
                continue;
            }
            
            // Create cURL handle for parallel fetch
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $source['url']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; AI-Stats/1.0)');
            
            curl_multi_add_handle($mh, $ch);
            $handles[$index] = array(
                'handle' => $ch,
                'source' => $source,
                'start_time' => $start_times[$index],
            );
        }
        
        // Execute all handles
        $running = null;
        do {
            curl_multi_exec($mh, $running);
            curl_multi_select($mh);
        } while ($running > 0);
        
        // Collect results
        foreach ($handles as $index => $handle_data) {
            $ch = $handle_data['handle'];
            $source = $handle_data['source'];
            $fetch_time = round((microtime(true) - $handle_data['start_time']) * 1000, 2);
            
            $content = curl_multi_getcontent($ch);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            
            curl_multi_remove_handle($mh, $ch);
            curl_close($ch);
            
            // Parse the fetched content based on source type
            $candidates = array();
            $status = 'success';
            $error_msg = null;
            
            if (!empty($error) || $http_code !== 200) {
                $status = 'error';
                $error_msg = !empty($error) ? $error : "HTTP $http_code";
            } else {
                // Parse content based on type
                if ($source['type'] === 'RSS') {
                    $candidates = $this->parse_rss_content($content, $source);
                } elseif ($source['type'] === 'HTML') {
                    $candidates = $this->parse_html_content($content, $source);
                }
                
                if (empty($candidates)) {
                    $status = 'empty';
                }
                
                // Cache successful results
                if ($status === 'success') {
                    $cache_key = 'ai_stats_fetch_' . md5($source['url']);
                    set_transient($cache_key, $candidates, 3600);
                }
            }
            
            $results[] = array(
                'source' => $source,
                'candidates' => $candidates,
                'status' => $status,
                'error' => $error_msg,
                'candidates_count' => count($candidates),
                'fetch_time' => $fetch_time,
            );
        }
        
        curl_multi_close($mh);
        
        return $results;
    }

    /**
     * Parse RSS content into candidates
     *
     * @param string $content RSS XML content
     * @param array $source Source configuration
     * @return array Candidates
     */
    private function parse_rss_content($content, $source) {
        $candidates = array();
        
        libxml_use_internal_errors(true);
        $xml = simplexml_load_string($content);
        libxml_clear_errors();
        
        if (!$xml) {
            return $candidates;
        }
        
        $items = isset($xml->channel->item) ? $xml->channel->item : $xml->entry;
        
        foreach ($items as $item) {
            $title = isset($item->title) ? (string) $item->title : '';
            $description = isset($item->description) ? (string) $item->description :
                          (isset($item->summary) ? (string) $item->summary : '');
            $link = isset($item->link) ? (string) $item->link : '';
            $pubDate = isset($item->pubDate) ? (string) $item->pubDate :
                       (isset($item->published) ? (string) $item->published : '');
            
            if (!empty($title)) {
                $candidates[] = array(
                    'title' => strip_tags($title),
                    'blurb_seed' => strip_tags($description),
                    'url' => $link,
                    'source' => $source['name'],
                    'published_at' => $pubDate,
                    'score' => 50,
                );
            }
        }
        
        return $candidates;
    }

    /**
     * Parse HTML content into candidates
     *
     * @param string $content HTML content
     * @param array $source Source configuration
     * @return array Candidates
     */
    private function parse_html_content($content, $source) {
        $candidates = array();
        
        if (preg_match_all('/<h[1-3][^>]*>(.*?)<\/h[1-3]>/is', $content, $matches)) {
            foreach ($matches[1] as $title) {
                $candidates[] = array(
                    'title' => strip_tags($title),
                    'blurb_seed' => strip_tags($title),
                    'url' => $source['url'],
                    'source' => $source['name'],
                    'published_at' => date('Y-m-d'),
                    'score' => 50,
                );
            }
        }
        
        return array_slice($candidates, 0, 20);
    }
    
    /**
     * Fetch from a single source
     *
     * @param array $source Source configuration
     * @return array|WP_Error Candidates or error
     */
    public function fetch_from_source($source) {
        // Check cache first (short TTL for manual testing)
        $cache_key = 'ai_stats_fetch_' . md5($source['url']);
        $cached = get_transient($cache_key);

        if ($cached !== false) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log(sprintf('AI-Stats: Using cached data for %s (%d items)', $source['name'], count($cached)));
            }
            return $cached;
        }

        $candidates = array();

        try {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log(sprintf('AI-Stats: Fetching from %s (type: %s, url: %s)', $source['name'], $source['type'], $source['url']));
            }

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

                default:
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log(sprintf('AI-Stats: Unknown source type %s for %s', $source['type'], $source['name']));
                    }
                    return array();
            }

            // Log for debugging
            if (defined('WP_DEBUG') && WP_DEBUG) {
                if (empty($candidates)) {
                    error_log(sprintf(
                        'AI-Stats: ⚠️ EMPTY RESULT from %s (%s) - URL: %s',
                        $source['name'],
                        $source['type'],
                        $source['url']
                    ));
                } else {
                    error_log(sprintf(
                        'AI-Stats: ✓ Fetched %d candidates from %s (%s)',
                        count($candidates),
                        $source['name'],
                        $source['type']
                    ));
                }
            }

        } catch (Exception $e) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log(sprintf('AI-Stats: ❌ Exception fetching %s: %s', $source['name'], $e->getMessage()));
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
        // Prefer direct fetch with custom User-Agent to avoid 403/404 from some feeds
        $response = wp_remote_get($source['url'], array(
            'timeout' => 20,
            'headers' => array(
                'User-Agent' => 'Mozilla/5.0 (compatible; AI-Stats/1.0; +https://opace.co.uk)',
                'Accept' => 'application/rss+xml, application/xml;q=0.9, */*;q=0.8',
            ),
        ));

        $candidates = array();

        if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
            $body = wp_remote_retrieve_body($response);
            if (!empty($body)) {
                $candidates = $this->parse_rss_content($body, $source);
            }
        }

        // Fallback to fetch_feed if direct fetch failed or parsing yielded nothing
        if (empty($candidates)) {
            $feed = fetch_feed($source['url']);
            if (!is_wp_error($feed)) {
                $items = $feed->get_items(0, 10);

                if (!empty($items)) {
                    foreach ($items as $item) {
                        $published = $item->get_date('c');
                        if (empty($published)) {
                            $published = date('c');
                        }

                        $description = $item->get_description() ?: $item->get_title();
                        $content = $item->get_content() ?: $description;

                        $content = wp_strip_all_tags($content);
                        $content = preg_replace('/\s+/', ' ', $content);

                        $blurb_seed = $this->extract_blurb($content);
                        $stats_from_rss = $this->extract_statistics_from_text($content);
                        if (!empty($stats_from_rss)) {
                            $blurb_seed = $stats_from_rss;
                            $content = $stats_from_rss;
                        }

                        $candidates[] = array(
                            'title' => $item->get_title() ?: 'Untitled',
                            'source' => $source['name'],
                            'url' => $item->get_permalink() ?: $source['url'],
                            'published_at' => $published,
                            'tags' => $source['tags'] ?? array(),
                            'blurb_seed' => $blurb_seed,
                            'full_content' => $content,
                            'geo' => $this->extract_geo($source),
                            'confidence' => !empty($stats_from_rss) ? 0.90 : 0.75,
                        );
                    }
                }
            } else {
                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log(sprintf('AI-Stats RSS error for %s: %s', $source['name'], $feed->get_error_message()));
                }
            }
        }

        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf('AI-Stats: Fetched %d items from RSS feed %s', count($candidates), $source['name']));
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
        if (strpos($source['name'], 'BigQuery') !== false || strpos($source['name'], 'Google Trends') !== false) {
            $candidates = $this->fetch_bigquery_trends_api($source);
        } elseif (strpos($source['name'], 'Nomis') !== false) {
            $candidates = $this->fetch_nomis_api($source);
        } elseif (strpos($source['name'], 'ONS') !== false) {
            $candidates = $this->fetch_ons_api($source);
        } elseif (strpos($source['name'], 'Eurostat') !== false) {
            $candidates = $this->fetch_eurostat_api($source);
        } elseif (strpos($source['name'], 'World Bank') !== false) {
            $candidates = $this->fetch_worldbank_api($source);
        } elseif (strpos($source['name'], 'Birmingham City Observatory') !== false) {
            $candidates = $this->fetch_birmingham_observatory_api($source);
        } elseif (strpos($source['name'], 'WMCA') !== false || strpos($source['name'], 'Birmingham Open Data') !== false) {
            $candidates = $this->fetch_opendatasoft_api($source);
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
     * Fetch from BigQuery Google Trends API
     * 
     * @param array $source Source configuration
     * @return array Normalised candidates
     */
    private function fetch_bigquery_trends_api($source) {
        $settings = AI_Stats_Settings::get_instance()->get_all();

        if (empty($settings['enable_bigquery_trends'])) {
            return array();
        }

        if (empty($settings['gcp_service_account_json']) || empty($settings['gcp_project_id'])) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('AI-Stats: BigQuery credentials not configured');
            }
            return array();
        }

        $credentials = json_decode($settings['gcp_service_account_json'], true);
        if (!$credentials) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('AI-Stats: Invalid BigQuery credentials JSON');
            }
            return array();
        }

        $region_code = $settings['bigquery_region'] ?? 'GB';
        $project_id = $settings['gcp_project_id'];
        $dataset_location = 'US'; // Google Trends public dataset is hosted in the US multi-region

        // Build appropriate SQL based on region
        // International tables use country_name, US tables use dma_name
        $query_config = $this->get_bigquery_query_for_region($region_code);

        if (is_wp_error($query_config)) {
            return $query_config;
        }

        $sql = $query_config['sql'];
        $param_value = $query_config['param_value'];
        
        // Get OAuth token from service account
        $token = $this->get_bigquery_access_token($credentials);
        if (is_wp_error($token)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('AI-Stats: Failed to get BigQuery access token: ' . $token->get_error_message());
            }
            return $token;
        }
        
        // Execute BigQuery job
        $query_config_data = array(
            'query' => $sql,
            'useLegacySql' => false
        );

        // Add query parameters only if param_value is set
        if ($param_value !== null) {
            $query_config_data['queryParameters'] = array(
                array(
                    'name' => 'region',
                    'parameterType' => array('type' => 'STRING'),
                    'parameterValue' => array('value' => $param_value)
                )
            );
        }

        $job_data = array(
            'configuration' => array(
                'query' => $query_config_data
            )
        );
        $job_data['jobReference'] = array(
            'projectId' => $project_id,
            'location' => $dataset_location
        );
        
        $response = wp_remote_post(
            "https://bigquery.googleapis.com/bigquery/v2/projects/{$project_id}/jobs",
            array(
                'headers' => array(
                    'Authorization' => 'Bearer ' . $token,
                    'Content-Type' => 'application/json',
                ),
                'body' => wp_json_encode($job_data),
                'timeout' => 30,
            )
        );
        
        if (is_wp_error($response)) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('AI-Stats: BigQuery request failed: ' . $response->get_error_message());
            }
            return $response;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);

        if (!isset($body['jobReference']['jobId'])) {
            // Extract error message from BigQuery response
            $error_message = 'Failed to create BigQuery job.';

            if (isset($body['error']['message'])) {
                $error_message = $body['error']['message'];
            } elseif (isset($body['error']['errors'][0]['message'])) {
                $error_message = $body['error']['errors'][0]['message'];
            }

            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('AI-Stats: BigQuery job creation failed: ' . wp_json_encode($body));
            }

            return new WP_Error(
                'bigquery_job_creation_failed',
                $error_message
            );
        }
        
        $job_id = $body['jobReference']['jobId'];
        
        // Wait for job completion and get results
        $results = $this->get_bigquery_results($project_id, $job_id, $token, $dataset_location);
        
        if (is_wp_error($results)) {
            return $results;
        }

        // Check for empty results before normalising
        if (empty($results['rows'])) {
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log(sprintf(
                    'AI-Stats: BigQuery returned empty result for region "%s" (%s). This may be normal if the region has no trending data in the last 30 days.',
                    $region_code,
                    $param_value
                ));
            }
            return array(); // Return empty array, not error
        }

        // Normalise results to candidate format
        return $this->normalise_bigquery_trends($results, $source, $region_code);
    }

    /**
     * Get BigQuery SQL query configuration for a specific region
     *
     * The Google Trends BigQuery dataset has different schemas:
     * - International: Uses country_name and region_name columns
     * - US: Uses dma_name column (Designated Market Area)
     *
     * @param string $region_code Region code (GB, US, EU, etc.)
     * @return array|WP_Error Query configuration with 'sql' and 'param_value' keys
     */
    private function get_bigquery_query_for_region($region_code) {
        // Map region codes to query configurations
        $region_map = array(
            'GB' => array(
                'type' => 'international',
                'country_name' => 'United Kingdom',
            ),
            'US' => array(
                'type' => 'us',
                'dma_name' => null, // Will query all US DMAs
            ),
            'EU' => array(
                'type' => 'international',
                'country_name' => 'Germany', // Default to Germany for EU
            ),
        );

        if (!isset($region_map[$region_code])) {
            return new WP_Error(
                'invalid_region',
                sprintf(__('Invalid BigQuery region code: %s', 'ai-stats'), $region_code)
            );
        }

        $config = $region_map[$region_code];

        if ($config['type'] === 'international') {
            // International query using country_name
            $sql = "SELECT
                term AS query_name,
                rank,
                refresh_date,
                country_name,
                region_name,
                score
            FROM `bigquery-public-data.google_trends.top_terms`
            WHERE
                refresh_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)
                AND country_name = @region
                AND rank <= 25
            ORDER BY refresh_date DESC, rank ASC
            LIMIT 25";

            return array(
                'sql' => $sql,
                'param_value' => $config['country_name'],
                'type' => 'international',
            );
        } else {
            // US query using dma_name (or all DMAs if not specified)
            if ($config['dma_name']) {
                $sql = "SELECT
                    term AS query_name,
                    rank,
                    refresh_date,
                    dma_name,
                    score
                FROM `bigquery-public-data.google_trends.top_terms`
                WHERE
                    refresh_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)
                    AND dma_name = @region
                    AND rank <= 25
                ORDER BY refresh_date DESC, rank ASC
                LIMIT 25";

                return array(
                    'sql' => $sql,
                    'param_value' => $config['dma_name'],
                    'type' => 'us',
                );
            } else {
                // Query all US DMAs (no filter)
                $sql = "SELECT
                    term AS query_name,
                    rank,
                    refresh_date,
                    dma_name,
                    score
                FROM `bigquery-public-data.google_trends.top_terms`
                WHERE
                    refresh_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 30 DAY)
                    AND dma_name IS NOT NULL
                    AND rank <= 25
                ORDER BY refresh_date DESC, rank ASC
                LIMIT 25";

                return array(
                    'sql' => $sql,
                    'param_value' => null, // No parameter needed
                    'type' => 'us',
                );
            }
        }
    }

    /**
     * Get BigQuery access token from service account
     * 
     * @param array $credentials Service account credentials
     * @return string|WP_Error Access token or error
     */
    private function get_bigquery_access_token($credentials) {
        $now = time();
        if (!extension_loaded('openssl')) {
            return new WP_Error(
                'missing_openssl',
                __('PHP OpenSSL extension is required to authenticate with Google Cloud. Please enable it on the server.', 'ai-stats')
            );
        }

        if (empty($credentials['private_key'])) {
            return new WP_Error(
                'missing_private_key',
                __('Service account credentials are missing the private_key field.', 'ai-stats')
            );
        }

        $jwt_header = array('alg' => 'RS256', 'typ' => 'JWT');
        $jwt_claim = array(
            'iss' => $credentials['client_email'],
            'scope' => 'https://www.googleapis.com/auth/bigquery',
            'aud' => 'https://oauth2.googleapis.com/token',
            'exp' => $now + 3600,
            'iat' => $now,
        );
        
        $segments = array(
            $this->base64url_encode(wp_json_encode($jwt_header)),
            $this->base64url_encode(wp_json_encode($jwt_claim))
        );
        
        $signing_input = implode('.', $segments);
        
        $private_key = $credentials['private_key'];
        $signature = '';
        $signed = openssl_sign($signing_input, $signature, $private_key, 'SHA256');

        if (!$signed || empty($signature)) {
            return new WP_Error(
                'sign_failed',
                __('Failed to sign Google Cloud JWT. Verify the OpenSSL extension and service account private key.', 'ai-stats')
            );
        }

        $segments[] = $this->base64url_encode($signature);
        $jwt = implode('.', $segments);
        
        $response = wp_remote_post(
            'https://oauth2.googleapis.com/token',
            array(
                'body' => array(
                    'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                    'assertion' => $jwt,
                ),
                'timeout' => 10,
            )
        );
        
        if (is_wp_error($response)) {
            return $response;
        }
        
        $body = json_decode(wp_remote_retrieve_body($response), true);
        
        if (!isset($body['access_token'])) {
            return new WP_Error('auth_failed', 'Failed to get access token');
        }
        
        return $body['access_token'];
    }
    
    /**
     * Get BigQuery job results
     * 
     * @param string $project_id GCP project ID
     * @param string $job_id BigQuery job ID
     * @param string $token Access token
     * @return array|WP_Error Results or error
     */
    private function get_bigquery_results($project_id, $job_id, $token, $location = '') {
        $max_attempts = 10;
        $attempt = 0;
        
        while ($attempt < $max_attempts) {
            $query_url = "https://bigquery.googleapis.com/bigquery/v2/projects/{$project_id}/queries/{$job_id}";
            if (!empty($location)) {
                $query_url .= '?location=' . rawurlencode($location);
            }

            $response = wp_remote_get(
                $query_url,
                array(
                    'headers' => array(
                        'Authorization' => 'Bearer ' . $token,
                    ),
                    'timeout' => 10,
                )
            );
            
            if (is_wp_error($response)) {
                return $response;
            }
            
            $body = json_decode(wp_remote_retrieve_body($response), true);

            if (isset($body['error'])) {
                $message = isset($body['error']['message']) ? $body['error']['message'] : __('Unknown BigQuery error', 'ai-stats');
                return new WP_Error('bigquery_error', $message, $body['error']);
            }
            
            if (isset($body['jobComplete']) && $body['jobComplete']) {
                return $body;
            }
            
            $attempt++;
            sleep(1);
        }
        
        return new WP_Error('timeout', 'BigQuery job timeout');
    }
    
    /**
     * Normalise BigQuery Trends results to candidate format
     * 
     * @param array $results BigQuery results
     * @param array $source Source configuration
     * @param string $region Region code
     * @return array Normalised candidates
     */
    private function normalise_bigquery_trends($results, $source, $region) {
        $candidates = array();
        
        if (!isset($results['rows']) || empty($results['rows'])) {
            return $candidates;
        }
        
        foreach ($results['rows'] as $row) {
            if (!isset($row['f']) || count($row['f']) < 3) {
                continue;
            }
            
            $query = $row['f'][0]['v'] ?? '';
            $rank = $row['f'][1]['v'] ?? 0;
            $date = $row['f'][2]['v'] ?? '';
            
            if (empty($query)) {
                continue;
            }
            
            $candidates[] = array(
                'title' => sprintf('Trending: %s (#%d)', $query, $rank),
                'source' => $source['name'],
                'url' => 'https://trends.google.com/trends/explore?q=' . urlencode($query) . '&geo=' . $region,
                'published_at' => strtotime($date),
                'tags' => array_merge($source['tags'] ?? array(), array('google_trends', 'trending')),
                'blurb_seed' => sprintf('"%s" is currently ranked #%d in Google Trends for %s', $query, $rank, $region),
                'full_content' => sprintf('The search term "%s" is trending at position #%d in Google Trends for %s region as of %s.', $query, $rank, $region, $date),
                'geo' => $region,
                'confidence' => 0.95,
                'metadata' => array(
                    'rank' => $rank,
                    'region' => $region,
                    'query' => $query,
                    'date' => $date,
                )
            );
        }
        
        if (defined('WP_DEBUG') && WP_DEBUG) {
            error_log(sprintf('AI-Stats: Fetched %d Google Trends from BigQuery for %s', count($candidates), $region));
        }
        
        return $candidates;
    }
    
    /**
     * Base64 URL encode
     * 
     * @param string $data Data to encode
     * @return string Encoded data
     */
    private function base64url_encode($data) {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
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
        $candidates = array();

        // Try multiple ONS datasets for broader coverage using beta API
        $datasets = array(
            array(
                'id' => 'J4MC',
                'dataset' => 'DRSI',
                'title' => 'UK Retail Sales',
                'url' => 'https://www.ons.gov.uk/businessindustryandtrade/retailindustry',
                'tags' => array('uk_macro', 'retail', 'statistics'),
            ),
            array(
                'id' => 'LF24',
                'dataset' => 'LMS',
                'title' => 'UK Employment Rate',
                'url' => 'https://www.ons.gov.uk/employmentandlabourmarket',
                'tags' => array('uk_macro', 'employment', 'statistics'),
            ),
            array(
                'id' => 'D7BT',
                'dataset' => 'MQ5',
                'title' => 'UK GDP',
                'url' => 'https://www.ons.gov.uk/economy/grossdomesticproductgdp',
                'tags' => array('uk_macro', 'gdp', 'statistics'),
            ),
        );

        foreach ($datasets as $dataset) {
            // Use beta API endpoint
            $endpoint = sprintf(
                'https://api.beta.ons.gov.uk/v1/timeseries/%s/dataset/%s/data',
                $dataset['id'],
                $dataset['dataset']
            );

            $response = wp_remote_get($endpoint, array(
                'timeout' => 15,
            ));

            if (is_wp_error($response)) {
                continue;
            }

            $data = json_decode(wp_remote_retrieve_body($response), true);

            if (empty($data)) {
                continue;
            }

            // Parse ONS response structure
            if (isset($data['months']) && is_array($data['months'])) {
                $recent = array_slice($data['months'], -2); // Last 2 data points

                foreach ($recent as $month) {
                    $candidates[] = array(
                        'title' => $dataset['title'] . ': ' . ($month['value'] ?? 'N/A'),
                        'source' => 'ONS',
                        'url' => $dataset['url'],
                        'published_at' => $month['date'] ?? date('c'),
                        'tags' => $dataset['tags'],
                        'blurb_seed' => sprintf(
                            '%s at %s for %s',
                            $dataset['title'],
                            $month['value'] ?? 'N/A',
                            $month['date'] ?? 'recent period'
                        ),
                        'full_content' => sprintf(
                            'Official statistics from the Office for National Statistics show %s at %s for the period %s.',
                            $dataset['title'],
                            $month['value'] ?? 'N/A',
                            $month['date'] ?? 'recent period'
                        ),
                        'geo' => 'GB',
                        'confidence' => 0.95,
                    );
                }
            }
        }

        return $candidates;
    }

    /**
     * Fetch from Nomis API
     *
     * @param array $source Source configuration
     * @return array Normalised candidates
     */
    private function fetch_nomis_api($source) {
        $candidates = array();

        // Example Nomis datasets for UK labour market statistics
        $datasets = array(
            array(
                'id' => 'NM_1_1',
                'title' => 'UK Employment Statistics',
                'geography' => '2092957697',
                'url' => 'https://www.nomisweb.co.uk/datasets/nm_1_1',
            ),
        );

        foreach ($datasets as $dataset) {
            $endpoint = sprintf(
                'https://www.nomisweb.co.uk/api/v01/dataset/%s.data.json?geography=%s&date=latest&measures=20100',
                $dataset['id'],
                $dataset['geography']
            );

            $response = wp_remote_get($endpoint, array(
                'timeout' => 15,
            ));

            if (is_wp_error($response)) {
                continue;
            }

            $data = json_decode(wp_remote_retrieve_body($response), true);

            if (empty($data) || !isset($data['obs'])) {
                continue;
            }

            // Parse Nomis response structure
            foreach (array_slice($data['obs'], 0, 3) as $obs) {
                $candidates[] = array(
                    'title' => $dataset['title'],
                    'source' => 'Nomis',
                    'url' => $dataset['url'],
                    'published_at' => $obs['date']['value'] ?? date('c'),
                    'tags' => array('uk_labour', 'statistics', 'employment'),
                    'blurb_seed' => sprintf(
                        '%s: %s',
                        $dataset['title'],
                        $obs['obs_value']['value'] ?? 'N/A'
                    ),
                    'full_content' => sprintf(
                        'Official labour market statistics from Nomis show %s at %s for %s.',
                        $dataset['title'],
                        $obs['obs_value']['value'] ?? 'N/A',
                        $obs['date']['value'] ?? 'recent period'
                    ),
                    'geo' => 'GB',
                    'confidence' => 0.95,
                );
            }
        }

        return $candidates;
    }

    /**
     * Fetch from Birmingham City Observatory API
     *
     * @param array $source Source configuration
     * @return array Normalised candidates
     */
    private function fetch_birmingham_observatory_api($source) {
        $candidates = array();

        // Get catalog of datasets from Birmingham City Observatory
        $catalog_url = 'https://www.cityobservatory.birmingham.gov.uk/api/explore/v2.1/catalog/datasets?limit=10';

        $response = wp_remote_get($catalog_url, array(
            'timeout' => 30,
        ));

        if (is_wp_error($response)) {
            return array();
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);

        if (empty($data) || !isset($data['results'])) {
            return array();
        }

        foreach (array_slice($data['results'], 0, 5) as $dataset) {
            $candidates[] = array(
                'title' => $dataset['dataset']['metas']['default']['title'] ?? 'Birmingham Dataset',
                'source' => 'Birmingham City Observatory',
                'url' => 'https://www.cityobservatory.birmingham.gov.uk/',
                'published_at' => $dataset['dataset']['metas']['default']['modified'] ?? date('c'),
                'tags' => array('birmingham', 'west_midlands', 'regional', 'local'),
                'blurb_seed' => $dataset['dataset']['metas']['default']['description'] ?? 'Birmingham local data',
                'full_content' => sprintf(
                    'Birmingham City Observatory data: %s',
                    $dataset['dataset']['metas']['default']['description'] ?? 'Local statistics and insights'
                ),
                'geo' => 'Birmingham, UK',
                'confidence' => 0.90,
            );
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
     * Fetch from Eurostat API
     *
     * @param array $source Source configuration
     * @return array Normalised candidates
     */
    private function fetch_eurostat_api($source) {
        $candidates = array();

        // Example datasets for UK/EU statistics
        $datasets = array(
            array(
                'code' => 'nama_10_gdp',
                'title' => 'EU GDP Statistics',
                'url' => 'https://ec.europa.eu/eurostat/databrowser/view/nama_10_gdp/default/table',
            ),
            array(
                'code' => 'isoc_ec_ibuy',
                'title' => 'EU E-Commerce Statistics',
                'url' => 'https://ec.europa.eu/eurostat/databrowser/view/isoc_ec_ibuy/default/table',
            ),
        );

        foreach ($datasets as $dataset) {
            $endpoint = sprintf(
                'https://ec.europa.eu/eurostat/api/dissemination/statistics/1.0/data/%s?format=JSON&geo=UK&time=%d',
                $dataset['code'],
                date('Y')
            );

            $response = wp_remote_get($endpoint, array(
                'timeout' => 30,
            ));

            if (is_wp_error($response)) {
                continue;
            }

            $data = json_decode(wp_remote_retrieve_body($response), true);

            if (!empty($data['value'])) {
                $candidates[] = array(
                    'title' => $dataset['title'],
                    'source' => 'Eurostat',
                    'url' => $dataset['url'],
                    'published_at' => date('c'),
                    'tags' => array('eu_stats', 'statistics', 'eurostat'),
                    'blurb_seed' => sprintf('Latest %s from Eurostat', $dataset['title']),
                    'full_content' => sprintf('Official statistics from Eurostat: %s', $dataset['title']),
                    'geo' => 'EU',
                    'confidence' => 0.90,
                );
            }
        }

        return $candidates;
    }

    /**
     * Fetch from World Bank API
     *
     * @param array $source Source configuration
     * @return array Normalised candidates
     */
    private function fetch_worldbank_api($source) {
        $candidates = array();

        // Example indicators for UK
        $indicators = array(
            array(
                'code' => 'NY.GDP.MKTP.CD',
                'title' => 'UK GDP (current US$)',
                'url' => 'https://data.worldbank.org/indicator/NY.GDP.MKTP.CD?locations=GB',
            ),
            array(
                'code' => 'IT.NET.USER.ZS',
                'title' => 'UK Internet Users (% of population)',
                'url' => 'https://data.worldbank.org/indicator/IT.NET.USER.ZS?locations=GB',
            ),
        );

        foreach ($indicators as $indicator) {
            $endpoint = sprintf(
                'https://api.worldbank.org/v2/country/GBR/indicator/%s?format=json&date=%d:%d',
                $indicator['code'],
                date('Y') - 2,
                date('Y')
            );

            $response = wp_remote_get($endpoint, array(
                'timeout' => 30,
            ));

            if (is_wp_error($response)) {
                continue;
            }

            $data = json_decode(wp_remote_retrieve_body($response), true);

            if (isset($data[1]) && is_array($data[1]) && !empty($data[1])) {
                $latest = $data[1][0];

                if (isset($latest['value']) && $latest['value'] !== null) {
                    $candidates[] = array(
                        'title' => $indicator['title'],
                        'source' => 'World Bank',
                        'url' => $indicator['url'],
                        'published_at' => $latest['date'] ?? date('Y'),
                        'tags' => array('global_stats', 'statistics', 'world_bank'),
                        'blurb_seed' => sprintf('%s: %s (%s)', $indicator['title'], number_format($latest['value'], 2), $latest['date'] ?? date('Y')),
                        'full_content' => sprintf('World Bank data shows %s at %s for %s', $indicator['title'], number_format($latest['value'], 2), $latest['date'] ?? date('Y')),
                        'geo' => 'GB',
                        'confidence' => 0.90,
                    );
                }
            }
        }

        return $candidates;
    }

    /**
     * Fetch from OpenDataSoft API (WMCA, Birmingham)
     *
     * @param array $source Source configuration
     * @return array Normalised candidates
     */
    private function fetch_opendatasoft_api($source) {
        $candidates = array();

        // Get catalog of datasets
        $catalog_url = $source['url'] . 'catalog/datasets?limit=10';

        $response = wp_remote_get($catalog_url, array(
            'timeout' => 30,
        ));

        if (is_wp_error($response)) {
            return array();
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);

        if (isset($data['results']) && is_array($data['results'])) {
            foreach (array_slice($data['results'], 0, 5) as $dataset) {
                $dataset_id = $dataset['dataset_id'] ?? '';
                $title = $dataset['metas']['default']['title'] ?? $dataset['dataset_id'] ?? 'Dataset';
                $description = $dataset['metas']['default']['description'] ?? '';

                if (!empty($dataset_id)) {
                    $candidates[] = array(
                        'title' => $title,
                        'source' => $source['name'],
                        'url' => str_replace('/api/explore/v2.1/', '/explore/dataset/' . $dataset_id . '/', $source['url']),
                        'published_at' => $dataset['metas']['default']['modified'] ?? date('c'),
                        'tags' => $source['tags'] ?? array('regional', 'open_data'),
                        'blurb_seed' => substr($description, 0, 200),
                        'full_content' => $description,
                        'geo' => 'GB',
                        'confidence' => 0.85,
                    );
                }
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

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        // If JSON parsing failed, try to extract from HTML
        if (empty($data)) {
            return $this->extract_from_html($body, $source);
        }

        // Try to extract useful data from generic JSON structure
        $candidates = array();

        // Look for common data structures
        if (isset($data['data']) && is_array($data['data'])) {
            $items = array_slice($data['data'], 0, 5);
            foreach ($items as $item) {
                if (is_array($item)) {
                    $candidates[] = $this->normalise_api_item($item, $source);
                }
            }
        } elseif (isset($data['results']) && is_array($data['results'])) {
            $items = array_slice($data['results'], 0, 5);
            foreach ($items as $item) {
                if (is_array($item)) {
                    $candidates[] = $this->normalise_api_item($item, $source);
                }
            }
        }

        return $candidates;
    }

    /**
     * Normalise a generic API item
     *
     * @param array $item API item
     * @param array $source Source configuration
     * @return array Normalised candidate
     */
    private function normalise_api_item($item, $source) {
        // Try to extract title
        $title = $item['title'] ?? $item['name'] ?? $item['label'] ?? 'Data Point';

        // Try to extract value/description
        $value = $item['value'] ?? $item['description'] ?? $item['summary'] ?? '';

        // Try to extract URL
        $url = $item['url'] ?? $item['link'] ?? $source['url'];

        // Try to extract date
        $date = $item['date'] ?? $item['published'] ?? $item['updated'] ?? date('c');

        return array(
            'title' => $title,
            'source' => $source['name'],
            'url' => $url,
            'published_at' => $date,
            'tags' => $source['tags'] ?? array(),
            'blurb_seed' => substr($title . ' ' . $value, 0, 200),
            'full_content' => $value,
            'geo' => $this->extract_geo($source),
            'confidence' => 0.70,
        );
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
     * Expand keywords using AI to include synonyms and related terms
     *
     * @param array $keywords Original keywords
     * @return array {
     *     @type array  $keywords Expanded keywords including originals and AI-generated synonyms
     *     @type string $prompt   The prompt used for expansion
     *     @type string $provider AI provider used
     *     @type string $model    AI model used
     *     @type bool   $success  Whether expansion was successful
     *     @type string $error    Error message if failed
     * }
     */
    private function expand_keywords_with_ai($keywords) {
        $result = array(
            'keywords' => $keywords,
            'prompt' => '',
            'provider' => '',
            'model' => '',
            'success' => false,
            'error' => null,
        );

        if (empty($keywords)) {
            $result['error'] = 'No keywords provided';
            return $result;
        }

        // Check if AI-Core is available
        if (!function_exists('ai_core')) {
            $result['error'] = 'AI-Core plugin not active';
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('AI-Stats: AI-Core plugin not active - keyword expansion skipped');
            }
            return $result;
        }

        $ai_core = ai_core();

        if (!$ai_core || !$ai_core->is_configured()) {
            $result['error'] = 'AI-Core not configured (no API keys)';
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log('AI-Stats: AI-Core not configured - keyword expansion skipped');
            }
            return $result;
        }

        $expanded = array();

        // Get provider and model info directly from AI_Core_API
        if (method_exists($ai_core, 'get_default_provider')) {
            $result['provider'] = $ai_core->get_default_provider();

            if (method_exists($ai_core, 'get_provider_settings')) {
                try {
                    $provider_settings = $ai_core->get_provider_settings($result['provider']);
                    $result['model'] = $provider_settings['model'] ?? 'default';
                } catch (Exception $e) {
                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('AI-Stats: Failed to get provider settings: ' . $e->getMessage());
                    }
                }
            }
        }

        foreach ($keywords as $keyword) {
            // Always include the original keyword
            $expanded[] = $keyword;

            // Build the expansion prompt
            $prompt = 'You are a smart filter and SEO analyst that takes a keyword the user types and expands the keyword into the top 10 synonyms and similar phrases. For example, if the keyword is SEO, include search engine optimisation, search engine optimization, Google, ranking, organic search, SERP, meta tags, indexing, crawl budget, and so on. Rank these in order of most relevant first. Just output a comma separated list of the top 10 suggestions with no notes, explanation or formatting. Keywords only separated by commas and nothing else. The user keyword is "' . esc_html($keyword) . '".';

            // Store the prompt (use first keyword's prompt as representative)
            if (empty($result['prompt'])) {
                $result['prompt'] = $prompt;
            }

            try {
                // Use send_text_request with proper message format
                $messages = array(
                    array('role' => 'user', 'content' => $prompt)
                );

                $options = array(
                    'max_tokens' => 150,
                    'temperature' => 0.3, // Lower temperature for more consistent results
                );

                // Get the model to use
                $model = $result['model'] ?? 'gpt-4o-mini';

                $response = $ai_core->send_text_request($model, $messages, $options);

                if (!is_wp_error($response)) {
                    // Use shared extractor to support both Chat and Responses APIs
                    $content = class_exists('AICore\\AICore') ? \AICore\AICore::extractContent($response) : '';

                    if (!empty($content)) {
                        // Parse the comma-separated response
                        $synonyms = array_map('trim', explode(',', $content));
                        $synonyms = array_filter($synonyms); // Remove empty values

                        // Add synonyms to expanded list
                        $expanded = array_merge($expanded, $synonyms);
                        $result['success'] = true;

                        if (defined('WP_DEBUG') && WP_DEBUG) {
                            error_log(sprintf('AI-Stats: Expanded keyword "%s" to %d terms', $keyword, count($synonyms)));
                        }
                    } else {
                        $result['error'] = 'Empty response';
                        if (defined('WP_DEBUG') && WP_DEBUG) {
                            error_log('AI-Stats: Keyword expansion failed: Empty response');
                        }
                    }
                } else {
                    $error_msg = $response->get_error_message();
                    $result['error'] = $error_msg;

                    if (defined('WP_DEBUG') && WP_DEBUG) {
                        error_log('AI-Stats: Keyword expansion failed: ' . $error_msg);
                    }
                }
            } catch (Exception $e) {
                $result['error'] = $e->getMessage();

                if (defined('WP_DEBUG') && WP_DEBUG) {
                    error_log('AI-Stats: Keyword expansion exception: ' . $e->getMessage());
                }
            }
        }

        $result['keywords'] = array_unique($expanded);
        return $result;
    }

    /**
     * Filter candidates by keywords (with optional AI expansion)
     *
     * @param array $candidates Candidates
     * @param array $keywords Keywords to filter by
     * @param bool $use_ai_expansion Whether to use AI to expand keywords (default: true)
     * @return array Filtered candidates
     */
    private function filter_by_keywords($candidates, $keywords, $use_ai_expansion = true) {
        if (empty($keywords)) {
            return $candidates;
        }

        // Expand keywords with AI if enabled
        $search_keywords = $keywords;
        if ($use_ai_expansion) {
            $search_keywords = $this->expand_keywords_with_ai($keywords);

            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log(sprintf('AI-Stats: Filtering with %d keywords (expanded from %d)', count($search_keywords), count($keywords)));
            }
        }

        return array_filter($candidates, function($candidate) use ($search_keywords) {
            $text = strtolower($candidate['title'] . ' ' . $candidate['blurb_seed']);

            foreach ($search_keywords as $keyword) {
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
     * @param array $keywords Optional keywords for keyword density scoring
     * @param string $mode Optional mode for mode-specific scoring
     * @return array Scored and sorted candidates
     */
    private function score_candidates($candidates, $keywords = array(), $mode = '') {
        // Determine authoritative sources based on mode
        $authoritative_sources = $this->get_authoritative_sources_for_mode($mode);

        foreach ($candidates as &$candidate) {
            $score = 0;

            // Keyword density score (highest priority - 0-50 points)
            if (!empty($keywords)) {
                $keyword_score = $this->calculate_keyword_density_score($candidate, $keywords);
                $score += $keyword_score;
            }

            // Freshness score (0-30 points)
            $age_days = (time() - strtotime($candidate['published_at'])) / 86400;
            if ($age_days < 1) {
                $score += 30;
            } elseif ($age_days < 7) {
                $score += 20;
            } elseif ($age_days < 30) {
                $score += 10;
            }

            // Source authority weight (0-20 points)
            foreach ($authoritative_sources as $auth_source) {
                if (stripos($candidate['source'], $auth_source) !== false) {
                    $score += 20;
                    break;
                }
            }

            // Confidence score (0-10 points)
            $score += $candidate['confidence'] * 10;

            $candidate['score'] = $score;
        }

        // Sort by score descending
        usort($candidates, function($a, $b) {
            return $b['score'] - $a['score'];
        });

        return $candidates;
    }

    /**
     * Get authoritative sources for a specific mode
     *
     * @param string $mode Mode key
     * @return array Authoritative source names
     */
    private function get_authoritative_sources_for_mode($mode) {
        $mode_sources = array(
            'statistics' => array('ONS', 'GOV.UK', 'Eurostat', 'World Bank', 'OECD'),
            'birmingham' => array('Birmingham City Observatory', 'WMCA', 'ONS', 'GOV.UK'),
            'trends' => array('Google Trends', 'TechCrunch', 'The Verge', 'Wired', 'MIT Technology Review'),
            'benefits' => array('HubSpot', 'Neil Patel', 'Moz', 'Search Engine Journal'),
            'seasonal' => array('Google Trends', 'GOV.UK', 'Retail Gazette'),
            'process' => array('McKinsey', 'Harvard Business Review', 'Forbes', 'Inc.com'),
        );

        return $mode_sources[$mode] ?? array('Google', 'GOV.UK', 'Reuters', 'BBC');
    }

    /**
     * Calculate keyword density score for a candidate
     *
     * @param array $candidate Candidate data
     * @param array $keywords Keywords to match
     * @return int Score (0-50)
     */
    private function calculate_keyword_density_score($candidate, $keywords) {
        $text = strtolower(($candidate['title'] ?? '') . ' ' . ($candidate['blurb_seed'] ?? '') . ' ' . ($candidate['full_content'] ?? ''));

        if (empty($text)) {
            return 0;
        }

        $matches = 0;
        $total_keyword_occurrences = 0;

        foreach ($keywords as $keyword) {
            $keyword_lower = strtolower($keyword);
            $count = substr_count($text, $keyword_lower);

            if ($count > 0) {
                $matches++;
                $total_keyword_occurrences += $count;
            }
        }

        // Score based on:
        // - Number of different keywords matched (0-25 points)
        // - Total keyword occurrences (0-25 points)
        $keyword_variety_score = min(25, ($matches / max(1, count($keywords))) * 25);
        $keyword_frequency_score = min(25, $total_keyword_occurrences * 2);

        return round($keyword_variety_score + $keyword_frequency_score);
    }

    /**
     * Extract article content from URL
     *
     * @param string $url Article URL
     * @param array $source Source configuration
     * @return string Extracted content
     */
    private function extract_article_content($url, $source) {
        if (empty($url)) {
            return '';
        }

        // Check cache first
        $cache_key = 'ai_stats_article_' . md5($url);
        $cached = get_transient($cache_key);

        if ($cached !== false) {
            return $cached;
        }

        // Fetch article
        $response = wp_remote_get($url, array(
            'timeout' => 15,
            'user-agent' => 'Mozilla/5.0 (compatible; AI-Stats/0.2.3)',
        ));

        if (is_wp_error($response)) {
            return '';
        }

        $html = wp_remote_retrieve_body($response);

        if (empty($html)) {
            return '';
        }

        // Extract main content using DOMDocument
        libxml_use_internal_errors(true);
        $dom = new DOMDocument();
        $dom->loadHTML($html);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);

        // Try to find main content area
        $content_selectors = array(
            '//article',
            '//main',
            '//*[@class="content"]',
            '//*[@class="post-content"]',
            '//*[@class="entry-content"]',
            '//body',
        );

        $content = '';
        foreach ($content_selectors as $selector) {
            $nodes = $xpath->query($selector);
            if ($nodes->length > 0) {
                $content = $nodes->item(0)->textContent;
                break;
            }
        }

        // Clean up content
        $content = wp_strip_all_tags($content);
        $content = preg_replace('/\s+/', ' ', $content);
        $content = trim($content);

        // Extract statistics and key data points
        $extracted = $this->extract_statistics_from_text($content);

        // Cache for 1 hour
        if (!empty($extracted)) {
            set_transient($cache_key, $extracted, 3600);
        }

        return $extracted;
    }

    /**
     * Extract statistics from text content
     *
     * @param string $text Text content
     * @return string Extracted statistics
     */
    private function extract_statistics_from_text($text) {
        if (empty($text)) {
            return '';
        }

        // Limit text length for processing
        $text = substr($text, 0, 5000);

        $stats = array();

        // Extract sentences containing percentages
        $sentences = preg_split('/[.!?]+/', $text);
        foreach ($sentences as $sentence) {
            $sentence = trim($sentence);

            // Look for percentages
            if (preg_match('/\d+(?:\.\d+)?%/', $sentence)) {
                $stats[] = $sentence;
            }

            // Look for large numbers (statistics)
            if (preg_match('/\d{1,3}(?:,\d{3})+/', $sentence)) {
                $stats[] = $sentence;
            }

            // Look for growth/increase/decrease patterns
            if (preg_match('/(increase|decrease|growth|decline|rise|fall|up|down)\s+(?:by\s+)?(\d+)/i', $sentence)) {
                $stats[] = $sentence;
            }

            // Limit to 5 statistics
            if (count($stats) >= 5) {
                break;
            }
        }

        return implode(' ', $stats);
    }
}
