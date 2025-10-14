<?php
/**
 * AI-Stats Database Class
 *
 * Manages database tables for content storage, history, and performance tracking
 *
 * @package AI_Stats
 * @version 0.3.3
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Database management class
 */
class AI_Stats_Database {
    
    /**
     * Create plugin database tables
     * 
     * @return void
     */
    public static function create_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Table for storing generated content
        $table_content = $wpdb->prefix . 'ai_stats_content';
        
        $sql_content = "CREATE TABLE IF NOT EXISTS $table_content (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            mode varchar(50) NOT NULL,
            content_type varchar(50) NOT NULL,
            content longtext NOT NULL,
            metadata longtext,
            sources longtext,
            generated_at datetime NOT NULL,
            expires_at datetime,
            is_active tinyint(1) DEFAULT 1,
            PRIMARY KEY  (id),
            KEY mode (mode),
            KEY is_active (is_active),
            KEY generated_at (generated_at)
        ) $charset_collate;";
        
        // Table for content history
        $table_history = $wpdb->prefix . 'ai_stats_history';
        
        $sql_history = "CREATE TABLE IF NOT EXISTS $table_history (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            content_id bigint(20) UNSIGNED NOT NULL,
            mode varchar(50) NOT NULL,
            content longtext NOT NULL,
            displayed_from datetime NOT NULL,
            displayed_until datetime,
            impressions bigint(20) DEFAULT 0,
            clicks bigint(20) DEFAULT 0,
            PRIMARY KEY  (id),
            KEY content_id (content_id),
            KEY mode (mode),
            KEY displayed_from (displayed_from)
        ) $charset_collate;";
        
        // Table for performance metrics
        $table_performance = $wpdb->prefix . 'ai_stats_performance';
        
        $sql_performance = "CREATE TABLE IF NOT EXISTS $table_performance (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            content_id bigint(20) UNSIGNED NOT NULL,
            page_url varchar(255) NOT NULL,
            event_type varchar(50) NOT NULL,
            event_data longtext,
            user_agent varchar(255),
            ip_address varchar(45),
            created_at datetime NOT NULL,
            PRIMARY KEY  (id),
            KEY content_id (content_id),
            KEY event_type (event_type),
            KEY created_at (created_at),
            KEY page_url (page_url)
        ) $charset_collate;";
        
        // Table for scraped data cache
        $table_cache = $wpdb->prefix . 'ai_stats_cache';
        
        $sql_cache = "CREATE TABLE IF NOT EXISTS $table_cache (
            id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            cache_key varchar(255) NOT NULL,
            cache_type varchar(50) NOT NULL,
            data longtext NOT NULL,
            source_url varchar(500),
            created_at datetime NOT NULL,
            expires_at datetime NOT NULL,
            PRIMARY KEY  (id),
            UNIQUE KEY cache_key (cache_key),
            KEY cache_type (cache_type),
            KEY expires_at (expires_at)
        ) $charset_collate;";
        
        // Execute table creation
        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql_content);
        dbDelta($sql_history);
        dbDelta($sql_performance);
        dbDelta($sql_cache);
    }
    
    /**
     * Store generated content
     *
     * @param string $mode Content mode
     * @param string $content_type Type of content
     * @param string $content Generated content
     * @param array $metadata Additional metadata (now includes llm, model, items, sources_used)
     * @param array $sources Data sources used (legacy, now in metadata['sources_used'])
     * @param int $cache_duration Cache duration in seconds
     * @return int|false Content ID or false on failure
     */
    public static function store_content($mode, $content_type, $content, $metadata = array(), $sources = array(), $cache_duration = 86400) {
        global $wpdb;

        $table = $wpdb->prefix . 'ai_stats_content';

        // Merge sources into metadata for new structure
        if (!empty($sources) && !isset($metadata['sources_used'])) {
            $metadata['sources_used'] = $sources;
        }

        $result = $wpdb->insert(
            $table,
            array(
                'mode' => sanitize_text_field($mode),
                'content_type' => sanitize_text_field($content_type),
                'content' => wp_kses_post($content),
                'metadata' => wp_json_encode($metadata),
                'sources' => wp_json_encode($metadata['sources_used'] ?? $sources),
                'generated_at' => current_time('mysql'),
                'expires_at' => date('Y-m-d H:i:s', time() + $cache_duration),
                'is_active' => 1,
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d')
        );

        if ($result === false) {
            return false;
        }

        return $wpdb->insert_id;
    }

    /**
     * Insert module (new method for manual workflow)
     *
     * @param string $mode Content mode
     * @param string $html Generated HTML
     * @param array $meta Metadata including sources_used, llm, model, items
     * @return int|false Module ID or false on failure
     */
    public static function insert_module($mode, $html, $meta = array()) {
        return self::store_content($mode, 'module', $html, $meta, array(), 86400);
    }
    
    /**
     * Get active content for mode
     * 
     * @param string $mode Content mode
     * @param string $content_type Optional content type filter
     * @return object|null Content object or null
     */
    public static function get_active_content($mode, $content_type = null) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ai_stats_content';
        
        $sql = $wpdb->prepare(
            "SELECT * FROM $table 
            WHERE mode = %s 
            AND is_active = 1 
            AND (expires_at IS NULL OR expires_at > NOW())
            ORDER BY generated_at DESC 
            LIMIT 1",
            $mode
        );
        
        if ($content_type) {
            $sql = $wpdb->prepare(
                "SELECT * FROM $table 
                WHERE mode = %s 
                AND content_type = %s
                AND is_active = 1 
                AND (expires_at IS NULL OR expires_at > NOW())
                ORDER BY generated_at DESC 
                LIMIT 1",
                $mode,
                $content_type
            );
        }
        
        $content = $wpdb->get_row($sql);
        
        if ($content && !empty($content->metadata)) {
            $content->metadata = json_decode($content->metadata, true);
        }
        
        if ($content && !empty($content->sources)) {
            $content->sources = json_decode($content->sources, true);
        }
        
        return $content;
    }
    
    /**
     * Deactivate old content
     * 
     * @param string $mode Content mode
     * @return bool Success status
     */
    public static function deactivate_old_content($mode) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ai_stats_content';
        
        return $wpdb->update(
            $table,
            array('is_active' => 0),
            array('mode' => $mode),
            array('%d'),
            array('%s')
        ) !== false;
    }
    
    /**
     * Store content in history
     * 
     * @param int $content_id Content ID
     * @param string $mode Content mode
     * @param string $content Content text
     * @return int|false History ID or false on failure
     */
    public static function store_history($content_id, $mode, $content) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ai_stats_history';
        
        $result = $wpdb->insert(
            $table,
            array(
                'content_id' => absint($content_id),
                'mode' => sanitize_text_field($mode),
                'content' => wp_kses_post($content),
                'displayed_from' => current_time('mysql'),
            ),
            array('%d', '%s', '%s', '%s')
        );
        
        return $result ? $wpdb->insert_id : false;
    }
    
    /**
     * Track performance event
     * 
     * @param int $content_id Content ID
     * @param string $page_url Page URL
     * @param string $event_type Event type (impression, click, etc.)
     * @param array $event_data Additional event data
     * @return bool Success status
     */
    public static function track_event($content_id, $page_url, $event_type, $event_data = array()) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ai_stats_performance';
        
        $result = $wpdb->insert(
            $table,
            array(
                'content_id' => absint($content_id),
                'page_url' => esc_url_raw($page_url),
                'event_type' => sanitize_text_field($event_type),
                'event_data' => wp_json_encode($event_data),
                'user_agent' => isset($_SERVER['HTTP_USER_AGENT']) ? sanitize_text_field($_SERVER['HTTP_USER_AGENT']) : '',
                'ip_address' => self::get_client_ip(),
                'created_at' => current_time('mysql'),
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        return $result !== false;
    }
    
    /**
     * Get client IP address
     * 
     * @return string IP address
     */
    private static function get_client_ip() {
        $ip = '';
        
        if (isset($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        
        return sanitize_text_field($ip);
    }
    
    /**
     * Store scraped data in cache
     * 
     * @param string $cache_key Unique cache key
     * @param string $cache_type Type of cached data
     * @param mixed $data Data to cache
     * @param string $source_url Source URL
     * @param int $duration Cache duration in seconds
     * @return bool Success status
     */
    public static function set_cache($cache_key, $cache_type, $data, $source_url = '', $duration = 86400) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ai_stats_cache';
        
        // Delete existing cache with same key
        $wpdb->delete($table, array('cache_key' => $cache_key), array('%s'));
        
        $result = $wpdb->insert(
            $table,
            array(
                'cache_key' => sanitize_text_field($cache_key),
                'cache_type' => sanitize_text_field($cache_type),
                'data' => wp_json_encode($data),
                'source_url' => esc_url_raw($source_url),
                'created_at' => current_time('mysql'),
                'expires_at' => date('Y-m-d H:i:s', time() + $duration),
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s')
        );
        
        return $result !== false;
    }
    
    /**
     * Get cached data
     * 
     * @param string $cache_key Cache key
     * @return mixed|null Cached data or null if not found/expired
     */
    public static function get_cache($cache_key) {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ai_stats_cache';
        
        $cache = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM $table WHERE cache_key = %s AND expires_at > NOW()",
            $cache_key
        ));
        
        if (!$cache) {
            return null;
        }
        
        return json_decode($cache->data, true);
    }
    
    /**
     * Clear expired cache entries
     * 
     * @return int Number of deleted entries
     */
    public static function clear_expired_cache() {
        global $wpdb;
        
        $table = $wpdb->prefix . 'ai_stats_cache';
        
        return $wpdb->query("DELETE FROM $table WHERE expires_at < NOW()");
    }
}

