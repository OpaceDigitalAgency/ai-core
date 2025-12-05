<?php
/**
 * Settings Tab
 *
 * @package AI_Pulse
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$settings = AI_Pulse_Settings::get_all();
?>

<div class="ai-pulse-settings">
    <div class="ai-pulse-card">
        <h2>General Settings</h2>

        <form method="post" class="ai-pulse-form">
            <?php wp_nonce_field('ai_pulse_settings'); ?>
            <input type="hidden" name="ai_pulse_action" value="save_settings">

            <div class="form-group">
                <label for="default_period">Default Time Period</label>
                <select id="default_period" name="default_period">
                    <option value="daily" <?php selected($settings['default_period'], 'daily'); ?>>Daily</option>
                    <option value="weekly" <?php selected($settings['default_period'], 'weekly'); ?>>Weekly</option>
                    <option value="monthly" <?php selected($settings['default_period'], 'monthly'); ?>>Monthly</option>
                </select>
            </div>

            <div class="form-group">
                <label for="default_location">Default Location</label>
                <input type="text" id="default_location" name="default_location" value="<?php echo esc_attr($settings['default_location']); ?>" placeholder="Birmingham, West Midlands, UK">
                <p class="description">Default location for local trend analysis</p>
            </div>

            <div class="form-group">
                <label for="cache_duration">Cache Duration (hours)</label>
                <input type="number" id="cache_duration" name="cache_duration" value="<?php echo esc_attr($settings['cache_duration']); ?>" min="1" max="168">
                <p class="description">How long to cache generated content before regenerating</p>
            </div>

            <div class="form-group">
                <label>
                    <input type="checkbox" name="enable_debug" value="1" <?php checked($settings['enable_debug']); ?>>
                    Enable Debug Logging
                </label>
                <p class="description">Log detailed information for troubleshooting</p>
            </div>

            <div class="form-actions">
                <button type="submit" class="button button-primary">Save Settings</button>
            </div>
        </form>
    </div>

    <div class="ai-pulse-card">
        <h2>System Information</h2>
        
        <table class="widefat">
            <tr>
                <td><strong>Plugin Version:</strong></td>
                <td><?php echo esc_html(AI_PULSE_VERSION); ?></td>
            </tr>
            <tr>
                <td><strong>AI-Core Version:</strong></td>
                <td><?php echo esc_html(defined('AI_CORE_VERSION') ? AI_CORE_VERSION : 'Not detected'); ?></td>
            </tr>
            <tr>
                <td><strong>Gemini Configured:</strong></td>
                <td><?php echo ai_core() && in_array('gemini', ai_core()->get_configured_providers()) ? '✅ Yes' : '❌ No'; ?></td>
            </tr>
            <tr>
                <td><strong>WordPress Version:</strong></td>
                <td><?php echo esc_html(get_bloginfo('version')); ?></td>
            </tr>
            <tr>
                <td><strong>PHP Version:</strong></td>
                <td><?php echo esc_html(PHP_VERSION); ?></td>
            </tr>
        </table>
    </div>
</div>

