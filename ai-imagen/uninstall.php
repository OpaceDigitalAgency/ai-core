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

// Optional: Delete prompt library entries
// Uncomment the following code if you want to delete all AI-Imagen prompts on uninstall
/*
global $wpdb;

// Get AI-Imagen prompt groups
$groups_table = $wpdb->prefix . 'ai_core_prompt_groups';
$prompts_table = $wpdb->prefix . 'ai_core_prompts';

if ($wpdb->get_var("SHOW TABLES LIKE '{$groups_table}'") === $groups_table) {
    // Get group IDs for AI-Imagen groups
    $group_names = array(
        'Marketing & Advertising',
        'Social Media Content',
        'Product Photography',
        'Website Design Elements',
        'Publishing & Editorial',
        'Presentation Graphics',
        'Game Development',
        'Educational Content',
        'Print-on-Demand'
    );
    
    foreach ($group_names as $group_name) {
        $group_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$groups_table} WHERE name = %s",
                $group_name
            )
        );
        
        if ($group_id) {
            // Delete prompts in this group
            $wpdb->delete($prompts_table, array('group_id' => $group_id), array('%d'));
            
            // Delete the group
            $wpdb->delete($groups_table, array('id' => $group_id), array('%d'));
        }
    }
}
*/

