<?php
/**
 * Content Library Tab
 *
 * @package AI_Pulse
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

global $wpdb;
$table = $wpdb->prefix . 'ai_pulse_content';
$content_items = $wpdb->get_results("SELECT * FROM {$table} WHERE is_active = 1 ORDER BY generated_at DESC LIMIT 50");
?>

<div class="ai-pulse-library">
    <div class="ai-pulse-card">
        <h2>Generated Content Library</h2>

        <?php if (empty($content_items)): ?>
            <p class="ai-pulse-notice">No content generated yet. Use the Test Interface or wait for scheduled generation.</p>
        <?php else: ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Keyword</th>
                        <th>Mode</th>
                        <th>Period</th>
                        <th>Generated</th>
                        <th>Tokens</th>
                        <th>Cost</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($content_items as $item): ?>
                        <tr>
                            <td><strong><?php echo esc_html($item->keyword); ?></strong></td>
                            <td><?php echo esc_html($item->mode); ?></td>
                            <td><?php echo esc_html(ucfirst($item->period)); ?></td>
                            <td><?php echo esc_html(human_time_diff(strtotime($item->generated_at), current_time('timestamp')) . ' ago'); ?></td>
                            <td><?php echo esc_html(number_format($item->input_tokens + $item->output_tokens)); ?></td>
                            <td>$<?php echo esc_html(number_format($item->cost_usd, 4)); ?></td>
                            <td>
                                <button class="button button-small view-content" data-id="<?php echo esc_attr($item->id); ?>">View</button>
                                <button class="button button-small delete-content" data-id="<?php echo esc_attr($item->id); ?>">Delete</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <div id="content-modal" class="ai-pulse-modal" style="display: none;">
        <div class="ai-pulse-modal-content">
            <span class="ai-pulse-modal-close">&times;</span>
            <div id="modal-content-display"></div>
        </div>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    $('.view-content').on('click', function() {
        const id = $(this).data('id');
        const row = $(this).closest('tr');
        const html = row.find('td').eq(0).data('html');
        
        $('#modal-content-display').html('<p>Content preview functionality coming soon...</p>');
        $('#content-modal').show();
    });

    $('.ai-pulse-modal-close').on('click', function() {
        $('#content-modal').hide();
    });

    $('.delete-content').on('click', function() {
        if (!confirm('Delete this content?')) return;

        const id = $(this).data('id');
        const row = $(this).closest('tr');

        $.ajax({
            url: aiPulseAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'ai_pulse_delete_content',
                nonce: aiPulseAdmin.nonce,
                id: id
            },
            success: function(response) {
                if (response.success) {
                    row.fadeOut(function() { $(this).remove(); });
                } else {
                    alert('Failed to delete content');
                }
            }
        });
    });
});
</script>

