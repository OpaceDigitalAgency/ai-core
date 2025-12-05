<?php
/**
 * Scheduling Tab
 *
 * @package AI_Pulse
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$settings = AI_Pulse_Settings::get_all();
$next_scheduled = wp_next_scheduled('ai_pulse_scheduled_generation');
?>

<div class="ai-pulse-schedule">
    <div class="ai-pulse-card">
        <h2>Scheduled Generation Settings</h2>

        <form method="post" class="ai-pulse-form">
            <?php wp_nonce_field('ai_pulse_settings'); ?>
            <input type="hidden" name="ai_pulse_action" value="save_schedule">

            <div class="form-row">
                <div class="form-group">
                    <label for="update_interval">Update Frequency *</label>
                    <select id="update_interval" name="update_interval" required>
                        <option value="daily" <?php selected($settings['update_interval'], 'daily'); ?>>Daily</option>
                        <option value="twicedaily" <?php selected($settings['update_interval'], 'twicedaily'); ?>>Twice Daily</option>
                        <option value="weekly" <?php selected($settings['update_interval'], 'weekly'); ?>>Weekly</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="start_time">Start Time *</label>
                    <input type="time" id="start_time" name="start_time" value="<?php echo esc_attr($settings['start_time']); ?>" required>
                    <p class="description">Time to start scheduled generation (server time)</p>
                </div>
            </div>

            <div class="form-group">
                <label>
                    <input type="checkbox" name="gradual_rollout_enabled" value="1" <?php checked($settings['gradual_rollout_enabled']); ?>>
                    Enable Gradual Rollout
                </label>
                <p class="description">Spread generation across time to avoid rate limiting</p>
            </div>

            <div class="form-group">
                <label for="delay_between_requests">Delay Between Requests (seconds)</label>
                <input type="number" id="delay_between_requests" name="delay_between_requests" value="<?php echo esc_attr($settings['delay_between_requests']); ?>" min="0" max="60">
                <p class="description">Delay between API calls to prevent rate limiting</p>
            </div>

            <div class="form-actions">
                <button type="submit" class="button button-primary">Save Schedule Settings</button>
            </div>
        </form>
    </div>

    <div class="ai-pulse-card">
        <h2>Schedule Status</h2>

        <?php if ($next_scheduled): ?>
            <p><strong>Next Scheduled Run:</strong> <?php echo esc_html(date('j M Y, H:i:s', $next_scheduled)); ?></p>
            <p><strong>Time Until Next Run:</strong> <?php echo esc_html(human_time_diff($next_scheduled)); ?></p>
        <?php else: ?>
            <p class="ai-pulse-notice">No scheduled generation configured. Save settings above to enable.</p>
        <?php endif; ?>
    </div>
</div>

