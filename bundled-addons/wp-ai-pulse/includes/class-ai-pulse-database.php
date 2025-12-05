<?php
/**
 * AI-Pulse Database Class
 *
 * @package AI_Pulse
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Database operations class
 */
class AI_Pulse_Database {

    /**
     * Content table name
     */
    const TABLE_CONTENT = 'ai_pulse_content';

    /**
     * Settings table name
     */
    const TABLE_SETTINGS = 'ai_pulse_settings';

    /**
     * Initialize database
     */
    public static function init() {
        // Nothing to do on init
    }

    /**
     * Create database tables
     */
    public static function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();
        $content_table = $wpdb->prefix . self::TABLE_CONTENT;
        $settings_table = $wpdb->prefix . self::TABLE_SETTINGS;

        // Content table
        $sql_content = "CREATE TABLE IF NOT EXISTS {$content_table} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            keyword VARCHAR(255) NOT NULL,
            mode VARCHAR(50) NOT NULL,
            period VARCHAR(20) NOT NULL,
            content_html LONGTEXT NOT NULL,
            content_json LONGTEXT NOT NULL,
            sources_json TEXT,
            date_range VARCHAR(100),
            input_tokens INT UNSIGNED DEFAULT 0,
            output_tokens INT UNSIGNED DEFAULT 0,
            cost_usd DECIMAL(10,6) DEFAULT 0,
            generated_at DATETIME NOT NULL,
            is_active TINYINT(1) DEFAULT 1,
            INDEX idx_keyword_mode (keyword, mode),
            INDEX idx_active (is_active),
            INDEX idx_generated (generated_at)
        ) {$charset_collate};";

        // Settings table
        $sql_settings = "CREATE TABLE IF NOT EXISTS {$settings_table} (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            setting_key VARCHAR(100) UNIQUE NOT NULL,
            setting_value LONGTEXT,
            updated_at DATETIME NOT NULL
        ) {$charset_collate};";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_content);
        dbDelta($sql_settings);
    }

    /**
     * Store generated content
     *
     * @param string $keyword Keyword
     * @param string $mode Analysis mode
     * @param string $period Time period
     * @param array $data Content data
     * @return int|false Insert ID or false on failure
     */
    public static function store_content($keyword, $mode, $period, $data) {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE_CONTENT;

        // Deactivate old content for this keyword/mode/period
        $wpdb->update(
            $table,
            array('is_active' => 0),
            array(
                'keyword' => $keyword,
                'mode' => $mode,
                'period' => $period
            ),
            array('%d'),
            array('%s', '%s', '%s')
        );

        // Insert new content
        $result = $wpdb->insert(
            $table,
            array(
                'keyword' => $keyword,
                'mode' => $mode,
                'period' => $period,
                'content_html' => $data['html'],
                'content_json' => $data['json'],
                'sources_json' => isset($data['sources']) ? json_encode($data['sources']) : null,
                'date_range' => isset($data['date_range']) ? $data['date_range'] : null,
                'input_tokens' => isset($data['input_tokens']) ? $data['input_tokens'] : 0,
                'output_tokens' => isset($data['output_tokens']) ? $data['output_tokens'] : 0,
                'cost_usd' => isset($data['cost']) ? $data['cost'] : 0,
                'generated_at' => current_time('mysql'),
                'is_active' => 1
            ),
            array('%s', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%d', '%f', '%s', '%d')
        );

        return $result ? $wpdb->insert_id : false;
    }

    /**
     * Get active content
     *
     * @param string $keyword Keyword
     * @param string $mode Analysis mode
     * @param string $period Time period
     * @return object|null Content object or null
     */
    public static function get_active_content($keyword, $mode, $period) {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE_CONTENT;

        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$table} 
            WHERE keyword = %s 
            AND mode = %s 
            AND period = %s 
            AND is_active = 1 
            ORDER BY generated_at DESC 
            LIMIT 1",
            $keyword,
            $mode,
            $period
        ));
    }

    /**
     * Get all content for a keyword
     *
     * @param string $keyword Keyword
     * @param bool $active_only Only active content
     * @return array Content array
     */
    public static function get_keyword_content($keyword, $active_only = true) {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE_CONTENT;

        $where = $wpdb->prepare("WHERE keyword = %s", $keyword);

        if ($active_only) {
            $where .= " AND is_active = 1";
        }

        return $wpdb->get_results(
            "SELECT * FROM {$table} {$where} ORDER BY generated_at DESC"
        );
    }

    /**
     * Delete content by ID
     *
     * @param int $id Content ID
     * @return bool
     */
    public static function delete_content($id) {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE_CONTENT;

        return $wpdb->delete($table, array('id' => $id), array('%d')) !== false;
    }

    /**
     * Delete all content for a keyword
     *
     * @param string $keyword Keyword
     * @return bool
     */
    public static function delete_keyword_content($keyword) {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE_CONTENT;

        return $wpdb->delete($table, array('keyword' => $keyword), array('%s')) !== false;
    }

    /**
     * Get usage statistics
     *
     * @param string $period Period (day, week, month, all)
     * @return array Statistics
     */
    public static function get_usage_stats($period = 'month') {
        global $wpdb;
        $table = $wpdb->prefix . self::TABLE_CONTENT;

        $where = '';

        switch ($period) {
            case 'day':
                $where = "WHERE generated_at >= DATE_SUB(NOW(), INTERVAL 1 DAY)";
                break;
            case 'week':
                $where = "WHERE generated_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
                break;
            case 'month':
                $where = "WHERE generated_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
                break;
        }

        return $wpdb->get_row(
            "SELECT
                COUNT(*) as total_generations,
                SUM(input_tokens) as total_input_tokens,
                SUM(output_tokens) as total_output_tokens,
                SUM(cost_usd) as total_cost
            FROM {$table} {$where}"
        );
    }

    /**
     * Drop all tables (for uninstall)
     */
    public static function drop_tables() {
        global $wpdb;

        $content_table = $wpdb->prefix . self::TABLE_CONTENT;
        $settings_table = $wpdb->prefix . self::TABLE_SETTINGS;

        $wpdb->query("DROP TABLE IF EXISTS {$content_table}");
        $wpdb->query("DROP TABLE IF EXISTS {$settings_table}");
    }
}

