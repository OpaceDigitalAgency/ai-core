<?php
/**
 * AI-Core uninstall routine.
 *
 * Runs only when the plugin is deleted, not on deactivation.
 *
 * Every query below is a direct one. That is deliberate and not a lapse:
 * uninstall runs once, at deletion, against rows no cache will outlive, and
 * there is no WordPress API for dropping a table or for sweeping transients by
 * prefix. Caching a result set we are in the middle of deleting would be
 * meaningless, so the phpcs:ignore comments record the reasoning rather than
 * suppressing a problem.
 *
 * @package AI_Core
 * @version 0.7.7
 */

// Exit if accessed directly or not via the WordPress uninstall handler.
if (!defined('ABSPATH')) {
    exit;
}

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

/*
 * Keeping data is the documented default.
 *
 * AI-Core exists to hold credentials for other plugins, so deleting it while
 * AI-Scribe is still installed would otherwise take that plugin's provider
 * configuration with it. A site owner who wants a clean removal turns
 * "Persist Settings on Uninstall" off first. The default here matches the
 * default in AI_Core_Settings::get_default_settings() and in ai-core.php; if
 * one of those three changes, all three must.
 */
$ai_core_settings = get_option('ai_core_settings', array());
$ai_core_persist  = isset($ai_core_settings['persist_on_uninstall'])
    ? (bool) $ai_core_settings['persist_on_uninstall']
    : true;

if (!$ai_core_persist) {
    global $wpdb;

    // Drop AI-Core's own tables. Table names are built from $wpdb->prefix,
    // which is server configuration rather than user input, and cannot be
    // passed as a bound parameter.
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- DROP TABLE cannot be prepared and has no API equivalent; uninstall is not cached.
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}ai_core_prompts");
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared -- as above.
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}ai_core_prompt_groups");

    // Options. delete_option() handles its own cache invalidation.
    delete_option('ai_core_settings');
    delete_option('ai_core_stats');
    delete_option('ai_core_version');

    /*
     * Transients are swept by prefix because their full names are generated at
     * runtime and are not knowable here. esc_like() escapes the % and _ that
     * would otherwise be LIKE wildcards, and the pattern is bound rather than
     * interpolated.
     */
    $ai_core_transient = $wpdb->esc_like('_transient_ai_core_') . '%';
    $ai_core_timeout   = $wpdb->esc_like('_transient_timeout_ai_core_') . '%';

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- no core API deletes transients by prefix; uninstall is not cached.
    $wpdb->query(
        $wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s OR option_name LIKE %s",
            $ai_core_transient,
            $ai_core_timeout
        )
    );

    // Object caches hold their own copies of options and transients, and the
    // rows have just gone out from under them.
    wp_cache_flush();
}

/*
 * Only AI-Core's own scheduled hook is cleared.
 *
 * Earlier versions of this file also deleted ai_stats_* and ai_imagen_* options
 * and dropped those plugins' tables. That was wrong on two counts: those
 * plugins own their data and ship their own uninstall routines, and deleting
 * AI-Core would have destroyed the data of a separately installed plugin that
 * happened to still be active. Since neither is bundled in this release, the
 * code could only ever have hit a third party's data.
 */
wp_clear_scheduled_hook('ai_core_cleanup');
