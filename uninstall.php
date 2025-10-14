<?php
/**
 * AI-Core Uninstall Script
 * 
 * Handles cleanup when plugin is deleted from WordPress
 * 
 * @package AI_Core
 * @version 0.6.8
 */

// Exit if accessed directly or not via WordPress uninstall
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Check if user wants to persist data on uninstall
$settings = get_option('ai_core_settings', array());
$persist_data = isset($settings['persist_on_uninstall']) ? (bool) $settings['persist_on_uninstall'] : true;

// Only clean up if user hasn't opted to persist data
if (!$persist_data) {
    global $wpdb;

    // Delete database tables
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}ai_core_prompts");
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}ai_core_prompt_groups");

    // Delete options
    delete_option('ai_core_settings');
    delete_option('ai_core_stats');
    delete_option('ai_core_version');

    // Delete transients
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_ai_core_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_ai_core_%'");
}

// Clean up bundled addon options if they exist
// (These should be cleaned up by their own uninstall scripts, but we ensure cleanup here)
if (!$persist_data) {
    // AI-Stats cleanup
    delete_option('ai_stats_settings');
    delete_option('ai_stats_version');
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_ai_stats_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_ai_stats_%'");
    
    // AI-Imagen cleanup
    delete_option('ai_imagen_settings');
    delete_option('ai_imagen_stats');
    delete_option('ai_imagen_version');
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_ai_imagen_%'");
    $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_ai_imagen_%'");
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}ai_imagen_prompts");
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}ai_imagen_stats");
}

// Clear any scheduled cron jobs
wp_clear_scheduled_hook('ai_core_cleanup');
wp_clear_scheduled_hook('ai_stats_schedule');

// Flush rewrite rules one final time
flush_rewrite_rules();
