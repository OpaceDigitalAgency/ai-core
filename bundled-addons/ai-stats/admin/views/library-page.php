<?php
/**
 * AI-Stats Content Library Page
 *
 * @package AI_Stats
 * @version 0.2.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$table = $wpdb->prefix . 'ai_stats_content';
$content_items = $wpdb->get_results("SELECT * FROM $table ORDER BY generated_at DESC LIMIT 50");
?>

<div class="wrap ai-stats-library">
    <h1><?php esc_html_e('Content Library', 'ai-stats'); ?></h1>
    
    <p class="description">
        <?php esc_html_e('Browse and manage all generated content.', 'ai-stats'); ?>
    </p>
    
    <?php if (empty($content_items)): ?>
        <div class="ai-stats-empty-state">
            <p><?php esc_html_e('No content in library yet.', 'ai-stats'); ?></p>
            <a href="<?php echo esc_url(admin_url('admin.php?page=ai-stats')); ?>" class="button button-primary">
                <?php esc_html_e('Generate Content', 'ai-stats'); ?>
            </a>
        </div>
    <?php else: ?>
        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php esc_html_e('Mode', 'ai-stats'); ?></th>
                    <th><?php esc_html_e('Content', 'ai-stats'); ?></th>
                    <th><?php esc_html_e('Generated', 'ai-stats'); ?></th>
                    <th><?php esc_html_e('Status', 'ai-stats'); ?></th>
                    <th><?php esc_html_e('Actions', 'ai-stats'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($content_items as $item): ?>
                    <tr>
                        <td>
                            <?php
                            $mode = AI_Stats_Modes::get_mode($item->mode);
                            echo esc_html($mode ? $mode['name'] : $item->mode);
                            ?>
                        </td>
                        <td>
                            <div class="content-preview">
                                <?php echo esc_html(wp_trim_words($item->content, 15)); ?>
                            </div>
                        </td>
                        <td>
                            <?php echo esc_html(human_time_diff(strtotime($item->generated_at), current_time('timestamp'))); ?>
                            <?php esc_html_e('ago', 'ai-stats'); ?>
                        </td>
                        <td>
                            <?php if ($item->is_active): ?>
                                <span class="status-badge status-active"><?php esc_html_e('Active', 'ai-stats'); ?></span>
                            <?php else: ?>
                                <span class="status-badge status-inactive"><?php esc_html_e('Inactive', 'ai-stats'); ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button type="button" class="button button-small ai-stats-preview-content" data-content-id="<?php echo esc_attr($item->id); ?>">
                                <?php esc_html_e('Preview', 'ai-stats'); ?>
                            </button>
                            <button type="button" class="button button-small button-link-delete ai-stats-delete-content" data-content-id="<?php echo esc_attr($item->id); ?>">
                                <?php esc_html_e('Delete', 'ai-stats'); ?>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

