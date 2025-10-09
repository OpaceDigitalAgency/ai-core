<?php
/**
 * Force Update AI-Imagen Prompts
 * 
 * Run this file once to force update all prompts to the latest version.
 * This will add all 26 categories with 130 prompts.
 * 
 * Usage: Navigate to this file in your browser while logged in as admin
 * URL: /wp-content/plugins/ai-core/bundled-addons/ai-imagen/force-update-prompts.php
 * 
 * @package AI_Imagen
 * @version 0.5.2
 */

// Load WordPress
require_once('../../../../../wp-load.php');

// Security check
if (!current_user_can('manage_options')) {
    wp_die('You do not have permission to access this page.');
}

// Check if AI-Imagen is loaded
if (!class_exists('AI_Imagen_Prompts')) {
    wp_die('AI-Imagen is not loaded. Please ensure the plugin is active.');
}

echo '<h1>Force Update AI-Imagen Prompts</h1>';
echo '<p>This will update all prompts to the latest version (26 categories, 130 prompts).</p>';
echo '<hr>';

// Get current version info
$installed_version = get_option('ai_imagen_version', 'Not set');
$prompts_installed = get_option('ai_imagen_prompts_installed', false);

echo '<h2>Current Status</h2>';
echo '<ul>';
echo '<li><strong>Installed Version:</strong> ' . esc_html($installed_version) . '</li>';
echo '<li><strong>Current Version:</strong> ' . AI_IMAGEN_VERSION . '</li>';
echo '<li><strong>Prompts Installed Flag:</strong> ' . ($prompts_installed ? 'Yes' : 'No') . '</li>';
echo '</ul>';
echo '<hr>';

// Delete the old flag to force reinstall
echo '<h2>Step 1: Deleting old installation flag...</h2>';
delete_option('ai_imagen_prompts_installed');
echo '<p style="color: green;">✓ Flag deleted</p>';

// Run the installation
echo '<h2>Step 2: Installing/Updating prompts...</h2>';
AI_Imagen_Prompts::install_templates();
echo '<p style="color: green;">✓ Installation complete</p>';

// Update the version
echo '<h2>Step 3: Updating version...</h2>';
update_option('ai_imagen_version', AI_IMAGEN_VERSION);
update_option('ai_imagen_prompts_installed', true);
echo '<p style="color: green;">✓ Version updated to ' . AI_IMAGEN_VERSION . '</p>';

// Get statistics
global $wpdb;
$groups_table = $wpdb->prefix . 'ai_core_prompt_groups';
$prompts_table = $wpdb->prefix . 'ai_core_prompts';

$ai_imagen_groups = $wpdb->get_results("
    SELECT g.id, g.name, COUNT(p.id) as prompt_count
    FROM {$groups_table} g
    LEFT JOIN {$prompts_table} p ON g.id = p.group_id AND p.type = 'image'
    WHERE g.name LIKE 'AI-Imagen:%'
    GROUP BY g.id, g.name
    ORDER BY g.name
", ARRAY_A);

echo '<hr>';
echo '<h2>Results</h2>';
echo '<p><strong>Total AI-Imagen Groups:</strong> ' . count($ai_imagen_groups) . '</p>';

if (!empty($ai_imagen_groups)) {
    echo '<table border="1" cellpadding="10" style="border-collapse: collapse; width: 100%;">';
    echo '<thead><tr><th>Group Name</th><th>Prompt Count</th></tr></thead>';
    echo '<tbody>';
    foreach ($ai_imagen_groups as $group) {
        echo '<tr>';
        echo '<td>' . esc_html($group['name']) . '</td>';
        echo '<td>' . esc_html($group['prompt_count']) . '</td>';
        echo '</tr>';
    }
    echo '</tbody>';
    echo '</table>';
} else {
    echo '<p style="color: red;">No AI-Imagen groups found!</p>';
}

echo '<hr>';
echo '<h2>✅ Update Complete!</h2>';
echo '<p>You can now:</p>';
echo '<ul>';
echo '<li><a href="' . admin_url('admin.php?page=ai-core-prompt-library') . '">View Prompt Library</a></li>';
echo '<li><a href="' . admin_url('admin.php?page=ai-imagen-generator') . '">Open AI-Imagen Generator</a></li>';
echo '</ul>';

echo '<hr>';
echo '<p style="color: #666; font-size: 12px;">You can safely delete this file after use: bundled-addons/ai-imagen/force-update-prompts.php</p>';

