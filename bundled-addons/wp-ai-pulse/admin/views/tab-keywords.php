<?php
/**
 * Keywords Tab
 *
 * @package AI_Pulse
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$keywords = AI_Pulse_Settings::get_keywords();
$modes = AI_Pulse_Modes::get_all_modes();
?>

<div class="ai-pulse-keywords">
    <div class="ai-pulse-card">
        <h2>Add Keyword</h2>
        
        <form method="post" class="ai-pulse-form">
            <?php wp_nonce_field('ai_pulse_settings'); ?>
            <input type="hidden" name="ai_pulse_action" value="save_keyword">

            <div class="form-row">
                <div class="form-group">
                    <label for="keyword">Keyword *</label>
                    <input type="text" id="keyword" name="keyword" placeholder="e.g., SEO, Web Design" required>
                </div>

                <div class="form-group">
                    <label for="period">Default Period *</label>
                    <select id="period" name="period" required>
                        <option value="daily">Daily</option>
                        <option value="weekly" selected>Weekly</option>
                        <option value="monthly">Monthly</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label>Analysis Modes *</label>
                <div class="checkbox-grid">
                    <?php foreach ($modes as $mode_id => $mode_data): ?>
                        <label class="checkbox-label">
                            <input type="checkbox" name="modes[]" value="<?php echo esc_attr($mode_id); ?>" <?php echo $mode_id === 'SUMMARY' ? 'checked' : ''; ?>>
                            <?php echo esc_html($mode_data['icon'] . ' ' . $mode_data['name']); ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="button button-primary">Add Keyword</button>
            </div>
        </form>
    </div>

    <div class="ai-pulse-card">
        <h2>Configured Keywords</h2>

        <?php if (empty($keywords)): ?>
            <p class="ai-pulse-notice">No keywords configured yet. Add your first keyword above.</p>
        <?php else: ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Keyword</th>
                        <th>Modes</th>
                        <th>Period</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($keywords as $keyword_data): ?>
                        <tr>
                            <td><strong><?php echo esc_html($keyword_data['keyword']); ?></strong></td>
                            <td>
                                <?php 
                                $keyword_modes = isset($keyword_data['modes']) ? $keyword_data['modes'] : array('SUMMARY');
                                echo esc_html(count($keyword_modes) . ' mode' . (count($keyword_modes) > 1 ? 's' : ''));
                                ?>
                            </td>
                            <td><?php echo esc_html(ucfirst($keyword_data['period'])); ?></td>
                            <td>
                                <form method="post" style="display: inline;">
                                    <?php wp_nonce_field('ai_pulse_settings'); ?>
                                    <input type="hidden" name="ai_pulse_action" value="delete_keyword">
                                    <input type="hidden" name="keyword" value="<?php echo esc_attr($keyword_data['keyword']); ?>">
                                    <button type="submit" class="button button-small" onclick="return confirm('Delete this keyword?');">Delete</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

