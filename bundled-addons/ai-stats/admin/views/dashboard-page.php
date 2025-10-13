<?php
/**
 * AI-Stats Dashboard Page
 *
 * @package AI_Stats
 * @version 0.2.3
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap ai-stats-dashboard">
    <h1><?php esc_html_e('AI-Stats Dashboard', 'ai-stats'); ?></h1>
    
    <div class="ai-stats-dashboard-grid">
        <!-- Current Mode Card -->
        <div class="ai-stats-card">
            <h2><?php esc_html_e('Current Mode', 'ai-stats'); ?></h2>
            <div class="ai-stats-current-mode">
                <?php
                $mode_info = AI_Stats_Modes::get_mode($active_mode);
                if ($mode_info):
                ?>
                    <div class="mode-icon">
                        <span class="dashicons <?php echo esc_attr($mode_info['icon']); ?>"></span>
                    </div>
                    <div class="mode-details">
                        <h3><?php echo esc_html($mode_info['name']); ?></h3>
                        <p><?php echo esc_html($mode_info['description']); ?></p>
                        <p class="mode-frequency">
                            <?php
                            printf(
                                esc_html__('Updates: %s', 'ai-stats'),
                                esc_html($mode_info['update_frequency'])
                            );
                            ?>
                        </p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="ai-stats-actions">
                <button type="button" class="button button-primary" id="ai-stats-generate-now">
                    <span class="dashicons dashicons-update"></span>
                    <?php esc_html_e('Generate Now', 'ai-stats'); ?>
                </button>
                <a href="<?php echo esc_url(admin_url('admin.php?page=ai-stats-settings')); ?>" class="button">
                    <?php esc_html_e('Change Mode', 'ai-stats'); ?>
                </a>
            </div>
        </div>
        
        <!-- Current Content Card -->
        <div class="ai-stats-card">
            <h2><?php esc_html_e('Current Content', 'ai-stats'); ?></h2>
            <?php if ($current_content): ?>
                <div class="ai-stats-content-preview">
                    <div class="content-text">
                        <?php echo wp_kses_post($current_content->content); ?>
                    </div>
                    <div class="content-meta">
                        <p>
                            <strong><?php esc_html_e('Generated:', 'ai-stats'); ?></strong>
                            <?php echo esc_html(human_time_diff(strtotime($current_content->generated_at), current_time('timestamp'))); ?>
                            <?php esc_html_e('ago', 'ai-stats'); ?>
                        </p>
                        <?php if (!empty($current_content->sources)): ?>
                            <p>
                                <strong><?php esc_html_e('Sources:', 'ai-stats'); ?></strong>
                                <?php echo esc_html(implode(', ', $current_content->sources)); ?>
                            </p>
                        <?php endif; ?>
                    </div>
                </div>
            <?php else: ?>
                <div class="ai-stats-empty-state">
                    <p><?php esc_html_e('No content generated yet.', 'ai-stats'); ?></p>
                    <button type="button" class="button button-primary" id="ai-stats-generate-first">
                        <?php esc_html_e('Generate First Content', 'ai-stats'); ?>
                    </button>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Available Modes Card -->
        <div class="ai-stats-card ai-stats-modes-card">
            <h2><?php esc_html_e('Available Modes', 'ai-stats'); ?></h2>
            <div class="ai-stats-modes-grid">
                <?php foreach ($modes as $mode_key => $mode): ?>
                    <div class="mode-item <?php echo $mode_key === $active_mode ? 'active' : ''; ?>" data-mode="<?php echo esc_attr($mode_key); ?>">
                        <span class="dashicons <?php echo esc_attr($mode['icon']); ?>"></span>
                        <h4><?php echo esc_html($mode['name']); ?></h4>
                        <p><?php echo esc_html($mode['description']); ?></p>
                        <?php if ($mode_key === $active_mode): ?>
                            <span class="mode-badge"><?php esc_html_e('Active', 'ai-stats'); ?></span>
                        <?php else: ?>
                            <button type="button" class="button button-small ai-stats-switch-mode" data-mode="<?php echo esc_attr($mode_key); ?>">
                                <?php esc_html_e('Switch', 'ai-stats'); ?>
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <!-- Quick Stats Card -->
        <div class="ai-stats-card">
            <h2><?php esc_html_e('Quick Stats', 'ai-stats'); ?></h2>
            <div class="ai-stats-quick-stats">
                <?php
                global $wpdb;
                $content_table = $wpdb->prefix . 'ai_stats_content';
                $total_content = $wpdb->get_var("SELECT COUNT(*) FROM $content_table");
                $active_content = $wpdb->get_var("SELECT COUNT(*) FROM $content_table WHERE is_active = 1");
                ?>
                <div class="stat-item">
                    <span class="stat-value"><?php echo esc_html($total_content); ?></span>
                    <span class="stat-label"><?php esc_html_e('Total Content', 'ai-stats'); ?></span>
                </div>
                <div class="stat-item">
                    <span class="stat-value"><?php echo esc_html($active_content); ?></span>
                    <span class="stat-label"><?php esc_html_e('Active Content', 'ai-stats'); ?></span>
                </div>
            </div>
        </div>
        
        <!-- Shortcode Usage Card -->
        <div class="ai-stats-card">
            <h2><?php esc_html_e('Shortcode Usage', 'ai-stats'); ?></h2>
            <p><?php esc_html_e('Use this shortcode to display dynamic content on your pages:', 'ai-stats'); ?></p>
            <div class="ai-stats-shortcode-box">
                <code>[ai_stats_module]</code>
                <button type="button" class="button button-small ai-stats-copy-shortcode" data-clipboard-text="[ai_stats_module]">
                    <span class="dashicons dashicons-clipboard"></span>
                    <?php esc_html_e('Copy', 'ai-stats'); ?>
                </button>
            </div>
            <p class="description">
                <?php esc_html_e('You can also specify a mode:', 'ai-stats'); ?>
                <code>[ai_stats_module mode="statistics"]</code>
            </p>
            <p class="description">
                <?php esc_html_e('Or change the style:', 'ai-stats'); ?>
                <code>[ai_stats_module style="inline"]</code>
            </p>
        </div>
    </div>
</div>

