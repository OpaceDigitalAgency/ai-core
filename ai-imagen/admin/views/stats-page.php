<?php
/**
 * AI-Imagen Statistics Page
 * 
 * Usage statistics and analytics
 * 
 * @package AI_Imagen
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap ai-imagen-stats">
    <h1><?php esc_html_e('Image Generation Statistics', 'ai-imagen'); ?></h1>
    
    <?php if (empty($stats)): ?>
        <div class="notice notice-info">
            <p><?php esc_html_e('No statistics available yet. Start generating images to see your usage data!', 'ai-imagen'); ?></p>
        </div>
    <?php else: ?>
        
        <!-- Summary Cards -->
        <div class="ai-imagen-stats-summary">
            <div class="stat-card">
                <div class="stat-icon">
                    <span class="dashicons dashicons-images-alt2"></span>
                </div>
                <div class="stat-content">
                    <h3><?php echo esc_html($summary['total_generations']); ?></h3>
                    <p><?php esc_html_e('Total Generations', 'ai-imagen'); ?></p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <span class="dashicons dashicons-calendar-alt"></span>
                </div>
                <div class="stat-content">
                    <h3><?php echo esc_html($summary['today_generations']); ?></h3>
                    <p><?php esc_html_e('Today', 'ai-imagen'); ?></p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <span class="dashicons dashicons-chart-line"></span>
                </div>
                <div class="stat-content">
                    <h3><?php echo esc_html($summary['this_week_generations']); ?></h3>
                    <p><?php esc_html_e('This Week', 'ai-imagen'); ?></p>
                </div>
            </div>
            
            <div class="stat-card">
                <div class="stat-icon">
                    <span class="dashicons dashicons-chart-area"></span>
                </div>
                <div class="stat-content">
                    <h3><?php echo esc_html($summary['this_month_generations']); ?></h3>
                    <p><?php esc_html_e('This Month', 'ai-imagen'); ?></p>
                </div>
            </div>
        </div>
        
        <!-- Charts Section -->
        <div class="ai-imagen-stats-charts">
            
            <!-- Generation Trend Chart -->
            <div class="stats-chart-container">
                <h2><?php esc_html_e('Generation Trend (Last 30 Days)', 'ai-imagen'); ?></h2>
                <div class="chart-wrapper">
                    <canvas id="ai-imagen-trend-chart"></canvas>
                </div>
            </div>
            
            <!-- Provider Distribution -->
            <div class="stats-chart-container">
                <h2><?php esc_html_e('By Provider', 'ai-imagen'); ?></h2>
                <?php if (!empty($stats['by_provider'])): ?>
                    <table class="widefat">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Provider', 'ai-imagen'); ?></th>
                                <th><?php esc_html_e('Count', 'ai-imagen'); ?></th>
                                <th><?php esc_html_e('Percentage', 'ai-imagen'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $total = $summary['total_generations'];
                            foreach ($stats['by_provider'] as $provider => $count):
                                $percentage = $total > 0 ? round(($count / $total) * 100, 1) : 0;
                            ?>
                                <tr>
                                    <td><?php echo esc_html(ucfirst($provider)); ?></td>
                                    <td><?php echo esc_html($count); ?></td>
                                    <td>
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?php echo esc_attr($percentage); ?>%;"></div>
                                            <span class="progress-text"><?php echo esc_html($percentage); ?>%</span>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p><?php esc_html_e('No data available.', 'ai-imagen'); ?></p>
                <?php endif; ?>
            </div>
            
            <!-- Use Case Distribution -->
            <div class="stats-chart-container">
                <h2><?php esc_html_e('By Use Case', 'ai-imagen'); ?></h2>
                <?php if (!empty($stats['by_use_case'])): ?>
                    <table class="widefat">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Use Case', 'ai-imagen'); ?></th>
                                <th><?php esc_html_e('Count', 'ai-imagen'); ?></th>
                                <th><?php esc_html_e('Percentage', 'ai-imagen'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $total = $summary['total_generations'];
                            arsort($stats['by_use_case']);
                            foreach ($stats['by_use_case'] as $use_case => $count):
                                $percentage = $total > 0 ? round(($count / $total) * 100, 1) : 0;
                            ?>
                                <tr>
                                    <td><?php echo esc_html(ucwords(str_replace('-', ' ', $use_case))); ?></td>
                                    <td><?php echo esc_html($count); ?></td>
                                    <td>
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?php echo esc_attr($percentage); ?>%;"></div>
                                            <span class="progress-text"><?php echo esc_html($percentage); ?>%</span>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p><?php esc_html_e('No data available.', 'ai-imagen'); ?></p>
                <?php endif; ?>
            </div>
            
            <!-- Style Distribution -->
            <div class="stats-chart-container">
                <h2><?php esc_html_e('By Style', 'ai-imagen'); ?></h2>
                <?php if (!empty($stats['by_style'])): ?>
                    <table class="widefat">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Style', 'ai-imagen'); ?></th>
                                <th><?php esc_html_e('Count', 'ai-imagen'); ?></th>
                                <th><?php esc_html_e('Percentage', 'ai-imagen'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $total = $summary['total_generations'];
                            arsort($stats['by_style']);
                            foreach ($stats['by_style'] as $style => $count):
                                $percentage = $total > 0 ? round(($count / $total) * 100, 1) : 0;
                            ?>
                                <tr>
                                    <td><?php echo esc_html(ucwords(str_replace('-', ' ', $style))); ?></td>
                                    <td><?php echo esc_html($count); ?></td>
                                    <td>
                                        <div class="progress-bar">
                                            <div class="progress-fill" style="width: <?php echo esc_attr($percentage); ?>%;"></div>
                                            <span class="progress-text"><?php echo esc_html($percentage); ?>%</span>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p><?php esc_html_e('No data available.', 'ai-imagen'); ?></p>
                <?php endif; ?>
            </div>
            
        </div>
        
        <!-- Export Actions -->
        <div class="ai-imagen-stats-actions">
            <h2><?php esc_html_e('Export & Manage', 'ai-imagen'); ?></h2>
            <p>
                <a href="<?php echo esc_url(admin_url('admin-post.php?action=ai_imagen_export_stats')); ?>" class="button">
                    <span class="dashicons dashicons-download"></span>
                    <?php esc_html_e('Export as CSV', 'ai-imagen'); ?>
                </a>
                <button type="button" class="button button-link-delete" id="ai-imagen-reset-stats">
                    <span class="dashicons dashicons-trash"></span>
                    <?php esc_html_e('Reset Statistics', 'ai-imagen'); ?>
                </button>
            </p>
        </div>
        
    <?php endif; ?>
</div>

<script>
jQuery(document).ready(function($) {
    // Chart data
    var chartData = <?php echo json_encode($chart_data); ?>;
    
    if (chartData && chartData.length > 0) {
        var ctx = document.getElementById('ai-imagen-trend-chart');
        if (ctx) {
            var labels = chartData.map(function(item) { return item.date; });
            var data = chartData.map(function(item) { return item.count; });
            
            // Simple canvas-based chart (you can integrate Chart.js for better visuals)
            // For now, just display the data
            console.log('Chart data:', chartData);
        }
    }
    
    // Reset stats confirmation
    $('#ai-imagen-reset-stats').on('click', function() {
        if (confirm('<?php echo esc_js(__('Are you sure you want to reset all statistics? This action cannot be undone.', 'ai-imagen')); ?>')) {
            window.location.href = '<?php echo esc_url(admin_url('admin-post.php?action=ai_imagen_reset_stats')); ?>';
        }
    });
});
</script>

