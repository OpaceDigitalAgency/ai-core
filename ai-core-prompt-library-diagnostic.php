<?php
/**
 * AI-Core Prompt Library Diagnostic Script
 * 
 * This script helps diagnose issues with the Prompt Library page
 * 
 * Access: /wp-content/plugins/ai-core/ai-core-prompt-library-diagnostic.php
 * 
 * @package AI_Core
 * @version 0.5.3
 */

// Load WordPress
require_once('../../../wp-load.php');

// Check if user is admin
if (!current_user_can('manage_options')) {
    wp_die('Access denied. You must be an administrator to view this page.');
}

// Set headers
header('Content-Type: text/html; charset=utf-8');

?>
<!DOCTYPE html>
<html>
<head>
    <title>AI-Core Prompt Library Diagnostic</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
            padding: 20px;
            max-width: 1200px;
            margin: 0 auto;
            background: #f0f0f1;
        }
        .diagnostic-section {
            background: white;
            padding: 20px;
            margin-bottom: 20px;
            border-radius: 4px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        h1 {
            color: #1d2327;
            border-bottom: 2px solid #2271b1;
            padding-bottom: 10px;
        }
        h2 {
            color: #2271b1;
            margin-top: 0;
        }
        .success {
            color: #00a32a;
            font-weight: bold;
        }
        .error {
            color: #d63638;
            font-weight: bold;
        }
        .warning {
            color: #dba617;
            font-weight: bold;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #f6f7f7;
            font-weight: 600;
        }
        .code {
            background: #f6f7f7;
            padding: 10px;
            border-radius: 4px;
            font-family: monospace;
            overflow-x: auto;
        }
        .button {
            display: inline-block;
            padding: 10px 20px;
            background: #2271b1;
            color: white;
            text-decoration: none;
            border-radius: 4px;
            margin-top: 10px;
        }
        .button:hover {
            background: #135e96;
        }
    </style>
</head>
<body>
    <h1>🔍 AI-Core Prompt Library Diagnostic</h1>
    
    <?php
    global $wpdb;
    
    // Test 1: Check if tables exist
    echo '<div class="diagnostic-section">';
    echo '<h2>1. Database Tables</h2>';
    
    $groups_table = $wpdb->prefix . 'ai_core_prompt_groups';
    $prompts_table = $wpdb->prefix . 'ai_core_prompts';
    
    $groups_exists = $wpdb->get_var("SHOW TABLES LIKE '{$groups_table}'");
    $prompts_exists = $wpdb->get_var("SHOW TABLES LIKE '{$prompts_table}'");
    
    echo '<table>';
    echo '<tr><th>Table</th><th>Status</th></tr>';
    echo '<tr><td>' . esc_html($groups_table) . '</td><td>' . ($groups_exists ? '<span class="success">✓ Exists</span>' : '<span class="error">✗ Missing</span>') . '</td></tr>';
    echo '<tr><td>' . esc_html($prompts_table) . '</td><td>' . ($prompts_exists ? '<span class="success">✓ Exists</span>' : '<span class="error">✗ Missing</span>') . '</td></tr>';
    echo '</table>';
    echo '</div>';
    
    // Test 2: Count records
    if ($groups_exists && $prompts_exists) {
        echo '<div class="diagnostic-section">';
        echo '<h2>2. Record Counts</h2>';
        
        $groups_count = $wpdb->get_var("SELECT COUNT(*) FROM {$groups_table}");
        $prompts_count = $wpdb->get_var("SELECT COUNT(*) FROM {$prompts_table}");
        
        echo '<table>';
        echo '<tr><th>Table</th><th>Count</th></tr>';
        echo '<tr><td>Groups</td><td>' . esc_html($groups_count) . '</td></tr>';
        echo '<tr><td>Prompts</td><td>' . esc_html($prompts_count) . '</td></tr>';
        echo '</table>';
        echo '</div>';
        
        // Test 3: Sample groups
        echo '<div class="diagnostic-section">';
        echo '<h2>3. Sample Groups (First 10)</h2>';
        
        $start_time = microtime(true);
        $groups = $wpdb->get_results("SELECT * FROM {$groups_table} ORDER BY name ASC LIMIT 10", ARRAY_A);
        $query_time = microtime(true) - $start_time;
        
        if ($wpdb->last_error) {
            echo '<p class="error">Database Error: ' . esc_html($wpdb->last_error) . '</p>';
        } else {
            echo '<p>Query time: ' . number_format($query_time * 1000, 2) . ' ms</p>';
            echo '<table>';
            echo '<tr><th>ID</th><th>Name</th><th>Description</th></tr>';
            foreach ($groups as $group) {
                echo '<tr>';
                echo '<td>' . esc_html($group['id']) . '</td>';
                echo '<td>' . esc_html($group['name']) . '</td>';
                echo '<td>' . esc_html(substr($group['description'] ?? '', 0, 50)) . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        }
        echo '</div>';
        
        // Test 4: Sample prompts
        echo '<div class="diagnostic-section">';
        echo '<h2>4. Sample Prompts (First 10)</h2>';
        
        $start_time = microtime(true);
        $prompts = $wpdb->get_results("SELECT * FROM {$prompts_table} ORDER BY created_at DESC LIMIT 10", ARRAY_A);
        $query_time = microtime(true) - $start_time;
        
        if ($wpdb->last_error) {
            echo '<p class="error">Database Error: ' . esc_html($wpdb->last_error) . '</p>';
        } else {
            echo '<p>Query time: ' . number_format($query_time * 1000, 2) . ' ms</p>';
            echo '<table>';
            echo '<tr><th>ID</th><th>Title</th><th>Group ID</th><th>Type</th></tr>';
            foreach ($prompts as $prompt) {
                echo '<tr>';
                echo '<td>' . esc_html($prompt['id']) . '</td>';
                echo '<td>' . esc_html($prompt['title']) . '</td>';
                echo '<td>' . esc_html($prompt['group_id'] ?? 'NULL') . '</td>';
                echo '<td>' . esc_html($prompt['type'] ?? 'text') . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        }
        echo '</div>';
        
        // Test 5: Optimised query (the one used in get_groups())
        echo '<div class="diagnostic-section">';
        echo '<h2>5. Optimised Groups Query (With Counts)</h2>';
        
        $start_time = microtime(true);
        $groups_with_counts = $wpdb->get_results(
            "SELECT g.*, COUNT(p.id) as count
             FROM {$groups_table} g
             LEFT JOIN {$prompts_table} p ON g.id = p.group_id
             GROUP BY g.id
             ORDER BY g.name ASC
             LIMIT 10",
            ARRAY_A
        );
        $query_time = microtime(true) - $start_time;
        
        if ($wpdb->last_error) {
            echo '<p class="error">Database Error: ' . esc_html($wpdb->last_error) . '</p>';
        } else {
            echo '<p>Query time: ' . number_format($query_time * 1000, 2) . ' ms</p>';
            echo '<table>';
            echo '<tr><th>ID</th><th>Name</th><th>Prompt Count</th></tr>';
            foreach ($groups_with_counts as $group) {
                echo '<tr>';
                echo '<td>' . esc_html($group['id']) . '</td>';
                echo '<td>' . esc_html($group['name']) . '</td>';
                echo '<td>' . esc_html($group['count']) . '</td>';
                echo '</tr>';
            }
            echo '</table>';
        }
        echo '</div>';
        
        // Test 6: Full page load simulation
        echo '<div class="diagnostic-section">';
        echo '<h2>6. Full Page Load Simulation</h2>';
        
        $start_time = microtime(true);
        
        // Simulate what the render_page() method does
        $all_groups = $wpdb->get_results(
            "SELECT g.*, COUNT(p.id) as count
             FROM {$groups_table} g
             LEFT JOIN {$prompts_table} p ON g.id = p.group_id
             GROUP BY g.id
             ORDER BY g.name ASC",
            ARRAY_A
        );
        
        $all_prompts = $wpdb->get_results(
            "SELECT p.*, g.name as group_name
             FROM {$prompts_table} p
             LEFT JOIN {$groups_table} g ON p.group_id = g.id
             ORDER BY p.created_at DESC",
            ARRAY_A
        );
        
        $total_time = microtime(true) - $start_time;
        
        if ($wpdb->last_error) {
            echo '<p class="error">Database Error: ' . esc_html($wpdb->last_error) . '</p>';
        } else {
            echo '<p><strong>Total query time:</strong> ' . number_format($total_time * 1000, 2) . ' ms</p>';
            echo '<p><strong>Groups loaded:</strong> ' . count($all_groups) . '</p>';
            echo '<p><strong>Prompts loaded:</strong> ' . count($all_prompts) . '</p>';
            
            if ($total_time > 2) {
                echo '<p class="error">⚠️ Query time is very slow (> 2 seconds). This could cause the page to hang.</p>';
            } elseif ($total_time > 1) {
                echo '<p class="warning">⚠️ Query time is slow (> 1 second). Consider optimising.</p>';
            } else {
                echo '<p class="success">✓ Query time is acceptable.</p>';
            }
        }
        echo '</div>';
    }
    
    // Test 7: PHP Configuration
    echo '<div class="diagnostic-section">';
    echo '<h2>7. PHP Configuration</h2>';
    echo '<table>';
    echo '<tr><th>Setting</th><th>Value</th></tr>';
    echo '<tr><td>PHP Version</td><td>' . esc_html(PHP_VERSION) . '</td></tr>';
    echo '<tr><td>Max Execution Time</td><td>' . esc_html(ini_get('max_execution_time')) . ' seconds</td></tr>';
    echo '<tr><td>Memory Limit</td><td>' . esc_html(ini_get('memory_limit')) . '</td></tr>';
    echo '<tr><td>WordPress Debug</td><td>' . (WP_DEBUG ? '<span class="success">Enabled</span>' : '<span class="warning">Disabled</span>') . '</td></tr>';
    echo '</table>';
    echo '</div>';
    
    // Test 8: Class availability
    echo '<div class="diagnostic-section">';
    echo '<h2>8. Class Availability</h2>';
    echo '<table>';
    echo '<tr><th>Class</th><th>Status</th></tr>';
    echo '<tr><td>AI_Core_Prompt_Library</td><td>' . (class_exists('AI_Core_Prompt_Library') ? '<span class="success">✓ Available</span>' : '<span class="error">✗ Not found</span>') . '</td></tr>';
    echo '<tr><td>AI_Core_Admin</td><td>' . (class_exists('AI_Core_Admin') ? '<span class="success">✓ Available</span>' : '<span class="error">✗ Not found</span>') . '</td></tr>';
    echo '</table>';
    echo '</div>';
    ?>
    
    <div class="diagnostic-section">
        <h2>9. Next Steps</h2>
        <p>Based on the diagnostic results above:</p>
        <ul>
            <li>If tables are missing, the plugin needs to be reactivated</li>
            <li>If query times are slow, database optimisation may be needed</li>
            <li>If classes are not available, there may be a file loading issue</li>
        </ul>
        <a href="<?php echo admin_url('admin.php?page=ai-core-prompt-library'); ?>" class="button">Try Loading Prompt Library Page</a>
    </div>
    
    <div class="diagnostic-section">
        <p><em>Diagnostic completed at <?php echo current_time('Y-m-d H:i:s'); ?></em></p>
    </div>
</body>
</html>

