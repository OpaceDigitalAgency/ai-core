<?php
/**
 * AI-Imagen Uninstall
 * 
 * Fired when the plugin is uninstalled
 * 
 * @package AI_Imagen
 * @version 1.0.0
 */

// If uninstall not called from WordPress, exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete plugin options
delete_option('ai_imagen_settings');
delete_option('ai_imagen_version');
delete_option('ai_imagen_prompts_installed');
delete_option('ai_imagen_stats');

// Optional: Delete generated images from media library
// Uncomment the following code if you want to delete all AI-generated images on uninstall
/*
global $wpdb;

// Get all AI-generated image attachments
$attachments = $wpdb->get_col(
    "SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key = '_ai_imagen_generated' AND meta_value = '1'"
);

// Delete each attachment
foreach ($attachments as $attachment_id) {
    wp_delete_attachment($attachment_id, true);
}
*/

// Delete AI-Imagen prompt library entries
global $wpdb;

// Get AI-Imagen prompt groups
$groups_table = $wpdb->prefix . 'ai_core_prompt_groups';
$prompts_table = $wpdb->prefix . 'ai_core_prompts';

if ($wpdb->get_var("SHOW TABLES LIKE '{$groups_table}'") === $groups_table) {
    // Delete all groups that start with "AI-Imagen: "
    // This is more reliable than listing all group names
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

