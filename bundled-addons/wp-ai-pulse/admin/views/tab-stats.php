<?php
/**
 * Statistics Tab
 *
 * @package AI_Pulse
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$stats_day = AI_Pulse_Database::get_usage_stats('day');
$stats_week = AI_Pulse_Database::get_usage_stats('week');
$stats_month = AI_Pulse_Database::get_usage_stats('month');
$stats_all = AI_Pulse_Database::get_usage_stats('all');
?>

<div class="ai-pulse-stats">
    <div class="ai-pulse-stats-grid">
        <div class="ai-pulse-stat-card">
            <h3>Last 24 Hours</h3>
            <div class="stat-value"><?php echo esc_html(number_format($stats_day->total_generations ?? 0)); ?></div>
            <div class="stat-label">Generations</div>
            <div class="stat-detail">
                <p>Tokens: <?php echo esc_html(number_format(($stats_day->total_input_tokens ?? 0) + ($stats_day->total_output_tokens ?? 0))); ?></p>
                <p>Cost: $<?php echo esc_html(number_format($stats_day->total_cost ?? 0, 4)); ?></p>
            </div>
        </div>

        <div class="ai-pulse-stat-card">
            <h3>Last 7 Days</h3>
            <div class="stat-value"><?php echo esc_html(number_format($stats_week->total_generations ?? 0)); ?></div>
            <div class="stat-label">Generations</div>
            <div class="stat-detail">
                <p>Tokens: <?php echo esc_html(number_format(($stats_week->total_input_tokens ?? 0) + ($stats_week->total_output_tokens ?? 0))); ?></p>
                <p>Cost: $<?php echo esc_html(number_format($stats_week->total_cost ?? 0, 4)); ?></p>
            </div>
        </div>

        <div class="ai-pulse-stat-card">
            <h3>Last 30 Days</h3>
            <div class="stat-value"><?php echo esc_html(number_format($stats_month->total_generations ?? 0)); ?></div>
            <div class="stat-label">Generations</div>
            <div class="stat-detail">
                <p>Tokens: <?php echo esc_html(number_format(($stats_month->total_input_tokens ?? 0) + ($stats_month->total_output_tokens ?? 0))); ?></p>
                <p>Cost: $<?php echo esc_html(number_format($stats_month->total_cost ?? 0, 4)); ?></p>
            </div>
        </div>

        <div class="ai-pulse-stat-card">
            <h3>All Time</h3>
            <div class="stat-value"><?php echo esc_html(number_format($stats_all->total_generations ?? 0)); ?></div>
            <div class="stat-label">Generations</div>
            <div class="stat-detail">
                <p>Tokens: <?php echo esc_html(number_format(($stats_all->total_input_tokens ?? 0) + ($stats_all->total_output_tokens ?? 0))); ?></p>
                <p>Cost: $<?php echo esc_html(number_format($stats_all->total_cost ?? 0, 2)); ?></p>
            </div>
        </div>
    </div>

    <div class="ai-pulse-card">
        <h2>Recent Activity</h2>
        <?php
        $logs = AI_Pulse_Logger::get_logs(20);
        if (empty($logs)): ?>
            <p class="ai-pulse-notice">No activity logged yet.</p>
        <?php else: ?>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th>Time</th>
                        <th>Level</th>
                        <th>Message</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                        <tr>
                            <td><?php echo esc_html(human_time_diff(strtotime($log['timestamp']), current_time('timestamp')) . ' ago'); ?></td>
                            <td><span class="log-level log-level-<?php echo esc_attr($log['level']); ?>"><?php echo esc_html(ucfirst($log['level'])); ?></span></td>
                            <td><?php echo esc_html($log['message']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

