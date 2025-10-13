<?php
/**
 * AI-Stats Cache Clearing Script
 * 
 * Run this file ONCE to clear the source registry cache and force reload of new sources.
 * After running, DELETE this file for security.
 * 
 * Usage: Visit https://adwordsadvantage.com/wp-content/plugins/ai-core/bundled-addons/ai-stats/clear-cache.php
 */

// Load WordPress
require_once('../../../../../wp-load.php');

// Check if user is admin
if (!current_user_can('manage_options')) {
    die('Access denied. You must be logged in as an administrator.');
}

echo '<h1>AI-Stats Cache Clearing Script</h1>';
echo '<p>Starting cache clear process...</p>';

// Clear the source registry cache
$deleted = delete_option('ai_stats_source_registry');

if ($deleted) {
    echo '<p style="color: green;">✅ Successfully deleted source registry cache!</p>';
} else {
    echo '<p style="color: orange;">⚠️ Source registry cache was already empty or does not exist.</p>';
}

// Clear all transient caches for AI-Stats
global $wpdb;
$transients_deleted = $wpdb->query(
    "DELETE FROM {$wpdb->options} 
     WHERE option_name LIKE '_transient_ai_stats_%' 
     OR option_name LIKE '_transient_timeout_ai_stats_%'"
);

echo '<p style="color: green;">✅ Cleared ' . $transients_deleted . ' transient caches.</p>';

// Force reload the registry
require_once('includes/class-ai-stats-source-registry.php');
$registry = AI_Stats_Source_Registry::get_instance();
$registry->refresh_registry();

$all_sources = $registry->get_all_sources();
$total_sources = 0;
foreach ($all_sources as $mode => $data) {
    $count = count($data['sources']);
    $total_sources += $count;
    echo '<p>📊 <strong>' . $data['mode'] . ':</strong> ' . $count . ' sources</p>';
}

echo '<h2 style="color: green;">✅ Cache Cleared Successfully!</h2>';
echo '<p><strong>Total sources loaded: ' . $total_sources . '</strong></p>';
echo '<p>You should now see ' . $total_sources . ' sources on the debug page.</p>';
echo '<hr>';
echo '<p style="color: red;"><strong>IMPORTANT: DELETE THIS FILE NOW FOR SECURITY!</strong></p>';
echo '<p>File location: <code>bundled-addons/ai-stats/clear-cache.php</code></p>';
echo '<hr>';
echo '<p><a href="/wp-admin/admin.php?page=ai-stats-debug" style="background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 3px;">Go to Debug Page</a></p>';

