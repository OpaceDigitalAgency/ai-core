<?php
/**
 * AI-Pulse Uninstall
 *
 * @package AI_Pulse
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete all plugin options
delete_option('ai_pulse_settings');
delete_option('ai_pulse_keywords');
delete_option('ai_pulse_logs');

// Drop database tables
global $wpdb;

$content_table = $wpdb->prefix . 'ai_pulse_content';
$settings_table = $wpdb->prefix . 'ai_pulse_settings';

$wpdb->query("DROP TABLE IF EXISTS {$content_table}");
$wpdb->query("DROP TABLE IF EXISTS {$settings_table}");

// Clear scheduled events
wp_clear_scheduled_hook('ai_pulse_scheduled_generation');

// Clear any transients
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_ai_pulse_%'");
$wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_ai_pulse_%'");

