<?php
/**
 * AI-Core Uninstall Script
 *
 * Handles plugin uninstallation and cleanup
 * Respects the "persist_on_uninstall" setting
 *
 * @package AI_Core
 * @version 0.0.1
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Load plugin.php for is_plugin_active function
if (!function_exists('is_plugin_active')) {
    require_once ABSPATH . 'wp-admin/includes/plugin.php';
}

// Get plugin settings
$settings = get_option('ai_core_settings', array());

// Check if user wants to persist settings (default: true)
$persist_on_uninstall = isset($settings['persist_on_uninstall']) ? $settings['persist_on_uninstall'] : true;

// If persist is disabled, delete all plugin data
if (!$persist_on_uninstall) {
    // Delete plugin options
    delete_option('ai_core_settings');
    delete_option('ai_core_stats');
    delete_option('ai_core_version');
    delete_option('ai_core_cache');

    // Delete transients
    global $wpdb;
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
            $wpdb->esc_like('_transient_ai_core_') . '%'
        )
    );
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
            $wpdb->esc_like('_transient_timeout_ai_core_') . '%'
        )
    );

    // Delete prompt library data if tables exist
    $prompts_table = $wpdb->prefix . 'ai_core_prompts';
    $groups_table = $wpdb->prefix . 'ai_core_prompt_groups';

    // Check if tables exist before dropping
    $prompts_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $prompts_table)) === $prompts_table;
    $groups_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $groups_table)) === $groups_table;

    if ($prompts_exists) {
        $wpdb->query("DROP TABLE IF EXISTS {$prompts_table}");
    }

    if ($groups_exists) {
        $wpdb->query("DROP TABLE IF EXISTS {$groups_table}");
    }

    // Clear any cached data
    wp_cache_flush();
} else {
    // Even if persist is enabled, clean up AI-Imagen prompts if AI-Imagen is not installed
    // This handles the case where user uninstalls AI-Core but AI-Imagen was already uninstalled
    if (!is_plugin_active('ai-imagen/ai-imagen.php') && !file_exists(WP_PLUGIN_DIR . '/ai-imagen/ai-imagen.php')) {
        $prompts_table = $wpdb->prefix . 'ai_core_prompts';
        $groups_table = $wpdb->prefix . 'ai_core_prompt_groups';

        // Check if tables exist
        $groups_exists = $wpdb->get_var($wpdb->prepare("SHOW TABLES LIKE %s", $groups_table)) === $groups_table;

        if ($groups_exists) {
            // Delete all AI-Imagen prompt groups and their prompts
            $ai_imagen_groups = $wpdb->get_col(
                $wpdb->prepare(
                    "SELECT id FROM {$groups_table} WHERE name LIKE %s",
                    $wpdb->esc_like('AI-Imagen: ') . '%'
                )
            );

            if (!empty($ai_imagen_groups)) {
                // Delete prompts in these groups
                $group_ids_placeholder = implode(',', array_fill(0, count($ai_imagen_groups), '%d'));
                $wpdb->query(
                    $wpdb->prepare(
                        "DELETE FROM {$prompts_table} WHERE group_id IN ({$group_ids_placeholder})",
                        $ai_imagen_groups
                    )
                );

                // Delete the groups
                $wpdb->query(
                    $wpdb->prepare(
                        "DELETE FROM {$groups_table} WHERE name LIKE %s",
                        $wpdb->esc_like('AI-Imagen: ') . '%'
                    )
                );
            }
        }
    }
}
// If persist is enabled (default), keep all settings and data
// This allows users to reinstall without losing their API keys and prompts

