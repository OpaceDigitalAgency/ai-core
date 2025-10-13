<?php
/**
 * AI-Stats Uninstall Script
 *
 * Fired when the plugin is uninstalled
 *
 * @package AI_Stats
 * @version 0.2.0
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete plugin options
delete_option('ai_stats_settings');

// Delete database tables
global $wpdb;

$tables = array(
    $wpdb->prefix . 'ai_stats_content',
    $wpdb->prefix . 'ai_stats_history',
    $wpdb->prefix . 'ai_stats_performance',
    $wpdb->prefix . 'ai_stats_cache',
);

foreach ($tables as $table) {
    $wpdb->query("DROP TABLE IF EXISTS $table");
}

// Clear scheduled cron jobs
wp_clear_scheduled_hook('ai_stats_daily_update');
wp_clear_scheduled_hook('ai_stats_weekly_update');

// Delete transients
delete_transient('ai_stats_scraper_cache');

