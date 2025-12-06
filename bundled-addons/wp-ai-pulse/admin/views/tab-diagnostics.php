<?php
/**
 * Diagnostics Tab
 *
 * @package AI_Pulse
 * @version 1.0.8
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$table_content = $wpdb->prefix . 'ai_pulse_content';
$table_settings = $wpdb->prefix . 'ai_pulse_settings';

// Check table existence
$content_table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_content}'");
$settings_table_exists = $wpdb->get_var("SHOW TABLES LIKE '{$table_settings}'");

// Get table info
$content_count = 0;
$content_structure = array();
if ($content_table_exists) {
    $content_count = $wpdb->get_var("SELECT COUNT(*) FROM {$table_content}");
    $content_structure = $wpdb->get_results("DESCRIBE {$table_content}");
}

// Get recent entries
$recent_entries = array();
if ($content_table_exists && $content_count > 0) {
    $recent_entries = $wpdb->get_results("SELECT * FROM {$table_content} ORDER BY generated_at DESC LIMIT 10");
}

// Check AI-Core integration
$ai_core_active = function_exists('ai_core');
$ai_core_configured = false;
$gemini_configured = false;

if ($ai_core_active) {
    $ai_core = ai_core();
    $ai_core_configured = $ai_core->is_configured();
    $providers = $ai_core->get_configured_providers();
    $gemini_configured = in_array('gemini', $providers);
}
?>

<div class="ai-pulse-diagnostics">
    <div class="ai-pulse-card">
        <h2>🔍 System Diagnostics</h2>

        <!-- AI-Core Integration -->
        <div class="diagnostic-section">
            <h3>AI-Core Integration</h3>
            <table class="widefat">
                <tr>
                    <td><strong>AI-Core Plugin Active:</strong></td>
                    <td><?php echo $ai_core_active ? '<span style="color: #10b981;">✓ Yes</span>' : '<span style="color: #ef4444;">✗ No</span>'; ?></td>
                </tr>
                <tr>
                    <td><strong>AI-Core Configured:</strong></td>
                    <td><?php echo $ai_core_configured ? '<span style="color: #10b981;">✓ Yes</span>' : '<span style="color: #ef4444;">✗ No</span>'; ?></td>
                </tr>
                <tr>
                    <td><strong>Gemini API Configured:</strong></td>
                    <td><?php echo $gemini_configured ? '<span style="color: #10b981;">✓ Yes</span>' : '<span style="color: #ef4444;">✗ No</span>'; ?></td>
                </tr>
            </table>
        </div>

        <!-- Database Tables -->
        <div class="diagnostic-section">
            <h3>Database Tables</h3>
            <table class="widefat">
                <tr>
                    <td><strong>Content Table (<?php echo esc_html($table_content); ?>):</strong></td>
                    <td><?php echo $content_table_exists ? '<span style="color: #10b981;">✓ Exists</span>' : '<span style="color: #ef4444;">✗ Missing</span>'; ?></td>
                </tr>
                <tr>
                    <td><strong>Settings Table (<?php echo esc_html($table_settings); ?>):</strong></td>
                    <td><?php echo $settings_table_exists ? '<span style="color: #10b981;">✓ Exists</span>' : '<span style="color: #ef4444;">✗ Missing</span>'; ?></td>
                </tr>
                <tr>
                    <td><strong>Total Content Rows:</strong></td>
                    <td><strong><?php echo esc_html($content_count); ?></strong></td>
                </tr>
            </table>

            <?php if (!$content_table_exists): ?>
                <div class="notice notice-error" style="margin-top: 16px;">
                    <p><strong>Database tables are missing!</strong></p>
                    <p>Try deactivating and reactivating the AI-Pulse plugin to create the tables.</p>
                    <form method="post" style="margin-top: 12px;">
                        <?php wp_nonce_field('ai_pulse_create_tables'); ?>
                        <input type="hidden" name="ai_pulse_action" value="create_tables">
                        <button type="submit" class="button button-primary">Create Database Tables Now</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>

        <!-- Table Structure -->
        <?php if ($content_table_exists && !empty($content_structure)): ?>
        <div class="diagnostic-section">
            <h3>Content Table Structure</h3>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>Field</th>
                        <th>Type</th>
                        <th>Null</th>
                        <th>Key</th>
                        <th>Default</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($content_structure as $column): ?>
                    <tr>
                        <td><code><?php echo esc_html($column->Field); ?></code></td>
                        <td><?php echo esc_html($column->Type); ?></td>
                        <td><?php echo esc_html($column->Null); ?></td>
                        <td><?php echo esc_html($column->Key); ?></td>
                        <td><?php echo esc_html($column->Default); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <!-- Recent Entries -->
        <?php if (!empty($recent_entries)): ?>
        <div class="diagnostic-section">
            <h3>Recent Database Entries (Last 10)</h3>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Keyword</th>
                        <th>Mode</th>
                        <th>Period</th>
                        <th>Generated</th>
                        <th>Active</th>
                        <th>Tokens</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recent_entries as $entry): ?>
                    <tr>
                        <td><?php echo esc_html($entry->id); ?></td>
                        <td><strong><?php echo esc_html($entry->keyword); ?></strong></td>
                        <td><?php echo esc_html($entry->mode); ?></td>
                        <td><?php echo esc_html($entry->period); ?></td>
                        <td><?php echo esc_html($entry->generated_at); ?></td>
                        <td><?php echo $entry->is_active ? '<span style="color: #10b981;">✓</span>' : '<span style="color: #6b7280;">✗</span>'; ?></td>
                        <td><?php echo esc_html(number_format($entry->input_tokens + $entry->output_tokens)); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<style>
.diagnostic-section {
    margin-bottom: 32px;
}

.diagnostic-section h3 {
    margin-bottom: 12px;
    color: var(--ai-pulse-dark);
    font-size: 16px;
}

.diagnostic-section table {
    margin-top: 8px;
}

.diagnostic-section table td {
    padding: 12px;
}
</style>

