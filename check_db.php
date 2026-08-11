<?php
// Quick database check script
define('WP_USE_THEMES', false);
require_once('../../../../../wp-load.php');

global $wpdb;
$table = $wpdb->prefix . 'ai_pulse_content';

// Check if table exists
$table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table}'");
echo "Table exists: " . ($table_exists ? "YES" : "NO") . "\n";

if ($table_exists) {
    // Check table structure
    $columns = $wpdb->get_results("DESCRIBE {$table}");
    echo "\nTable structure:\n";
    foreach ($columns as $col) {
        echo "  - {$col->Field} ({$col->Type})\n";
    }
    
    // Check row count
    $count = $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
    echo "\nTotal rows: {$count}\n";
    
    // Show recent entries
    if ($count > 0) {
        $recent = $wpdb->get_results("SELECT id, keyword, mode, period, generated_at, is_active FROM {$table} ORDER BY generated_at DESC LIMIT 5");
        echo "\nRecent entries:\n";
        foreach ($recent as $row) {
            echo "  ID: {$row->id}, Keyword: {$row->keyword}, Mode: {$row->mode}, Active: {$row->is_active}, Generated: {$row->generated_at}\n";
        }
    }
}
