<?php
/**
 * AI-Stats Debug Page
 *
 * @package AI_Stats
 * @version 0.3.1
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get registry
$registry = AI_Stats_Source_Registry::get_instance();
$all_sources = $registry->get_all_sources();
$settings = get_option('ai_stats_settings', array());
?>

<div class="wrap ai-stats-debug">
    <h1><?php esc_html_e('AI-Stats Debug & Diagnostics', 'ai-stats'); ?></h1>

    <div class="ai-stats-debug-tabs">
        <nav class="nav-tab-wrapper">
            <a href="#google-trends" class="nav-tab nav-tab-active"><?php esc_html_e('Google Trends Demo', 'ai-stats'); ?></a>
            <a href="#pipeline" class="nav-tab"><?php esc_html_e('Pipeline Debug', 'ai-stats'); ?></a>
            <a href="#sources" class="nav-tab"><?php esc_html_e('Data Sources', 'ai-stats'); ?></a>
            <a href="#settings" class="nav-tab"><?php esc_html_e('Configuration', 'ai-stats'); ?></a>
        </nav>

        <!-- Google Trends Demo Tab -->
        <div id="google-trends" class="tab-content active">
            <h2><?php esc_html_e('Google Trends Live Demo', 'ai-stats'); ?></h2>

            <?php
            $bigquery_enabled = !empty($settings['enable_bigquery_trends']);
            $has_credentials = !empty($settings['gcp_project_id']) && !empty($settings['gcp_service_account_json']);
            ?>

            <?php if (!$bigquery_enabled || !$has_credentials): ?>
                <div class="notice notice-warning">
                    <p><strong><?php esc_html_e('Google Trends Not Configured', 'ai-stats'); ?></strong></p>
                    <p><?php esc_html_e('To see live Google Trends data, you need to:', 'ai-stats'); ?></p>
                    <ol>
                        <li><?php esc_html_e('Set up a Google Cloud Project (free tier available)', 'ai-stats'); ?></li>
                        <li><?php esc_html_e('Enable BigQuery API', 'ai-stats'); ?></li>
                        <li><?php esc_html_e('Create a Service Account with BigQuery permissions', 'ai-stats'); ?></li>
                        <li><?php esc_html_e('Configure credentials in AI-Stats Settings', 'ai-stats'); ?></li>
                    </ol>
                    <p>
                        <a href="<?php echo admin_url('admin.php?page=ai-stats-settings#google-cloud'); ?>" class="button button-primary">
                            <?php esc_html_e('Go to Settings', 'ai-stats'); ?>
                        </a>
                        <a href="<?php echo AI_STATS_PLUGIN_URL; ?>GOOGLE_CLOUD_SETUP_GUIDE.md" class="button" target="_blank">
                            <?php esc_html_e('View Setup Guide', 'ai-stats'); ?>
                        </a>
                    </p>
                </div>
            <?php endif; ?>

            <div class="ai-stats-trends-demo" style="margin-top: 20px;">
                <div style="display: flex; gap: 20px; align-items: flex-start; margin-bottom: 20px;">
                    <div>
                        <label for="trends-region" style="display: block; margin-bottom: 5px; font-weight: 600;">
                            <?php esc_html_e('Region:', 'ai-stats'); ?>
                        </label>
                        <select id="trends-region" class="regular-text">
                            <option value="GB" <?php selected($settings['bigquery_region'] ?? 'GB', 'GB'); ?>>United Kingdom</option>
                            <option value="US" <?php selected($settings['bigquery_region'] ?? 'GB', 'US'); ?>>United States</option>
                            <option value="EU" <?php selected($settings['bigquery_region'] ?? 'GB', 'EU'); ?>>Europe</option>
                        </select>
                    </div>
                    <div style="padding-top: 28px;">
                        <button type="button" id="fetch-google-trends" class="button button-primary" <?php echo (!$bigquery_enabled || !$has_credentials) ? 'disabled' : ''; ?>>
                            <span class="dashicons dashicons-update"></span>
                            <?php esc_html_e('Fetch Live Google Trends', 'ai-stats'); ?>
                        </button>
                    </div>
                </div>

                <div id="trends-results" style="display: none;">
                    <h3><?php esc_html_e('Trending Searches', 'ai-stats'); ?></h3>
                    <p class="description" id="trends-meta"></p>
                    <div id="trends-list" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 10px; margin-top: 15px;"></div>
                </div>

                <div id="trends-error" style="display: none;" class="notice notice-error inline">
                    <p id="trends-error-message"></p>
                </div>

                <div style="margin-top: 30px; padding: 20px; background: #f9f9f9; border-left: 4px solid #2271b1;">
                    <h3><?php esc_html_e('How This Helps Your Service Pages', 'ai-stats'); ?></h3>
                    <p><?php esc_html_e('Google Trends shows what people are actively searching for RIGHT NOW. Here\'s how AI-Stats uses this data:', 'ai-stats'); ?></p>
                    <ul style="list-style: disc; margin-left: 20px;">
                        <li><strong><?php esc_html_e('Seasonal Service Angle Rotator:', 'ai-stats'); ?></strong> <?php esc_html_e('Automatically updates your service pages with trending topics relevant to your industry', 'ai-stats'); ?></li>
                        <li><strong><?php esc_html_e('Industry Trend Micro-Module:', 'ai-stats'); ?></strong> <?php esc_html_e('Adds "What\'s Trending" sections to show you\'re current and relevant', 'ai-stats'); ?></li>
                        <li><strong><?php esc_html_e('SEO Boost:', 'ai-stats'); ?></strong> <?php esc_html_e('Content featuring trending searches ranks better because it matches what people are looking for', 'ai-stats'); ?></li>
                        <li><strong><?php esc_html_e('Fresh Content:', 'ai-stats'); ?></strong> <?php esc_html_e('Google loves fresh, relevant content - trends update daily', 'ai-stats'); ?></li>
                    </ul>

                    <h4 style="margin-top: 20px;"><?php esc_html_e('Example Use Case:', 'ai-stats'); ?></h4>
                    <div style="background: white; padding: 15px; border-radius: 4px; margin-top: 10px;">
                        <p style="margin: 0;"><em><?php esc_html_e('If "AI automation" is trending and you offer web development services, AI-Stats can automatically add a section like:', 'ai-stats'); ?></em></p>
                        <blockquote style="margin: 15px 0; padding-left: 15px; border-left: 3px solid #ccc; font-style: italic;">
                            <?php esc_html_e('"With AI automation trending in 2025, our web development services now include AI-powered features to keep your business ahead of the curve..."', 'ai-stats'); ?>
                        </blockquote>
                    </div>
                </div>
            </div>
        </div>

        <!-- Pipeline Debug Tab -->
        <div id="pipeline" class="tab-content">
            <h2><?php esc_html_e('Fetch Pipeline Debug', 'ai-stats'); ?></h2>
            <p><?php esc_html_e('Test the complete pipeline: Fetch → Normalise → Filter → Rank → Cache', 'ai-stats'); ?></p>

            <div class="ai-stats-pipeline-controls">
                <table class="form-table">
                    <tr>
                        <th><label for="pipeline-mode"><?php esc_html_e('Mode', 'ai-stats'); ?></label></th>
                        <td>
                            <select id="pipeline-mode" class="regular-text">
                                <?php foreach ($all_sources as $mode_key => $mode_data): ?>
                                    <option value="<?php echo esc_attr($mode_key); ?>">
                                        <?php echo esc_html($mode_data['mode']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="pipeline-keywords"><?php esc_html_e('Keywords', 'ai-stats'); ?></label></th>
                        <td>
                            <input type="text" id="pipeline-keywords" class="regular-text" placeholder="seo, web design, birmingham">
                            <p class="description"><?php esc_html_e('Comma-separated keywords (optional)', 'ai-stats'); ?></p>
                        </td>
                    </tr>
                </table>

                <button type="button" id="run-pipeline-test" class="button button-primary">
                    <?php esc_html_e('Run Pipeline Test', 'ai-stats'); ?>
                </button>
                <button type="button" id="clear-cache-btn" class="button">
                    <?php esc_html_e('Clear Cache First', 'ai-stats'); ?>
                </button>
            </div>

            <div id="pipeline-results" style="display:none; margin-top: 20px;">
                <h3><?php esc_html_e('Pipeline Results', 'ai-stats'); ?></h3>

                <div class="pipeline-stage">
                    <h4>1️⃣ Fetch from Sources</h4>
                    <div id="fetch-results"></div>
                </div>

                <div class="pipeline-stage">
                    <h4>2️⃣ Normalised Data</h4>
                    <div id="normalised-results"></div>
                </div>

                <div class="pipeline-stage">
                    <h4>3️⃣ Filtered by Keywords</h4>
                    <div id="filtered-results"></div>
                </div>

                <div class="pipeline-stage">
                    <h4>4️⃣ Ranked by Score</h4>
                    <div id="ranked-results"></div>
                </div>

                <div class="pipeline-stage">
                    <h4>5️⃣ Final Candidates</h4>
                    <div id="final-results"></div>
                </div>
            </div>
        </div>
        
        <!-- Sources Tab -->
        <div id="sources" class="tab-content">
            <h2><?php esc_html_e('Registered Data Sources', 'ai-stats'); ?></h2>
            <p>
                <button type="button" id="test-all-sources" class="button button-primary">
                    <?php esc_html_e('Test All Sources', 'ai-stats'); ?>
                </button>
                <button type="button" id="refresh-source-registry" class="button" style="margin-left: 10px;">
                    <?php esc_html_e('🔄 Refresh Source Registry', 'ai-stats'); ?>
                </button>
                <span id="test-progress" style="margin-left: 10px;"></span>
            </p>
            <div id="refresh-message" style="display:none; padding: 10px; margin: 10px 0; border-left: 4px solid #00a32a; background: #f0f6fc;"></div>

            <?php foreach ($all_sources as $mode_key => $mode_data): ?>
                <div class="ai-stats-debug-mode">
                    <h3>
                        <?php echo esc_html($mode_data['mode']); ?>
                        <span class="badge"><?php echo count($mode_data['sources']); ?> sources</span>
                    </h3>

                    <table class="wp-list-table widefat fixed striped">
                        <thead>
                            <tr>
                                <th style="width: 30px;">#</th>
                                <th><?php esc_html_e('Name', 'ai-stats'); ?></th>
                                <th><?php esc_html_e('Type', 'ai-stats'); ?></th>
                                <th><?php esc_html_e('URL', 'ai-stats'); ?></th>
                                <th><?php esc_html_e('Tags', 'ai-stats'); ?></th>
                                <th style="width: 120px;"><?php esc_html_e('Status', 'ai-stats'); ?></th>
                                <th style="width: 80px;"><?php esc_html_e('Count', 'ai-stats'); ?></th>
                                <th style="width: 80px;"><?php esc_html_e('Time', 'ai-stats'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($mode_data['sources'] as $index => $source): ?>
                                <tr data-mode="<?php echo esc_attr($mode_key); ?>" data-source="<?php echo esc_attr($index); ?>">
                                    <td><?php echo $index + 1; ?></td>
                                    <td><strong><?php echo esc_html($source['name']); ?></strong></td>
                                    <td>
                                        <span class="badge badge-<?php echo strtolower($source['type']); ?>">
                                            <?php echo esc_html($source['type']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="<?php echo esc_url($source['url']); ?>" target="_blank" class="source-url">
                                            <?php echo esc_html(substr($source['url'], 0, 50)); ?>...
                                        </a>
                                    </td>
                                    <td>
                                        <?php foreach ($source['tags'] as $tag): ?>
                                            <span class="tag"><?php echo esc_html($tag); ?></span>
                                        <?php endforeach; ?>
                                    </td>
                                    <td class="source-status">
                                        <span class="status-badge status-pending">⏳ Pending</span>
                                    </td>
                                    <td class="source-count">-</td>
                                    <td class="source-time">-</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Configuration Tab -->
        <div id="settings" class="tab-content">
            <h2><?php esc_html_e('Current Configuration', 'ai-stats'); ?></h2>

            <table class="wp-list-table widefat fixed">
                <thead>
                    <tr>
                        <th><?php esc_html_e('Setting', 'ai-stats'); ?></th>
                        <th><?php esc_html_e('Value', 'ai-stats'); ?></th>
                        <th><?php esc_html_e('Status', 'ai-stats'); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td><strong><?php esc_html_e('Google API Key', 'ai-stats'); ?></strong></td>
                        <td><?php echo !empty($settings['google_api_key']) ? '••••••••' . substr($settings['google_api_key'], -4) : '<em>Not set</em>'; ?></td>
                        <td><?php echo !empty($settings['google_api_key']) ? '<span class="status-ok">✓ Configured</span>' : '<span class="status-warning">⚠ Missing</span>'; ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e('Companies House API Key', 'ai-stats'); ?></strong></td>
                        <td><?php echo !empty($settings['companies_house_api_key']) ? '••••••••' . substr($settings['companies_house_api_key'], -4) : '<em>Not set</em>'; ?></td>
                        <td><?php echo !empty($settings['companies_house_api_key']) ? '<span class="status-ok">✓ Configured</span>' : '<span class="status-warning">⚠ Missing</span>'; ?></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e('Preferred AI Provider', 'ai-stats'); ?></strong></td>
                        <td><?php echo esc_html($settings['preferred_provider'] ?? 'openai'); ?></td>
                        <td><span class="status-ok">✓ Set</span></td>
                    </tr>
                    <tr>
                        <td><strong><?php esc_html_e('AI-Core Status', 'ai-stats'); ?></strong></td>
                        <td><?php echo class_exists('AI_Core_API') ? 'Installed' : 'Not found'; ?></td>
                        <td><?php echo class_exists('AI_Core_API') ? '<span class="status-ok">✓ Available</span>' : '<span class="status-error">✗ Missing</span>'; ?></td>
                    </tr>
                </tbody>
            </table>

            <p style="margin-top: 20px;">
                <button type="button" id="clear-all-cache" class="button">
                    <?php esc_html_e('Clear All Cache', 'ai-stats'); ?>
                </button>
            </p>
        </div>
    </div>
</div>

<style>
.ai-stats-debug-mode {
    margin: 20px 0;
    background: #fff;
    padding: 20px;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
}
.badge {
    display: inline-block;
    padding: 3px 8px;
    background: #0073aa;
    color: #fff;
    border-radius: 3px;
    font-size: 12px;
    margin-left: 10px;
}
.badge-rss { background: #46b450; }
.badge-api { background: #00a0d2; }
.badge-html { background: #826eb4; }
.tag {
    display: inline-block;
    padding: 2px 6px;
    background: #f0f0f1;
    border-radius: 2px;
    font-size: 11px;
    margin: 2px;
}
.source-url {
    font-family: monospace;
    font-size: 11px;
}
.status-ok { color: #46b450; font-weight: bold; }
.status-warning { color: #ffb900; font-weight: bold; }
.status-error { color: #dc3232; font-weight: bold; }
.status-badge {
    display: inline-block;
    padding: 3px 8px;
    border-radius: 3px;
    font-size: 11px;
    font-weight: bold;
}
.status-badge.status-ok { background: #d4edda; color: #155724; }
.status-badge.status-warning { background: #fff3cd; color: #856404; }
.status-badge.status-error { background: #f8d7da; color: #721c24; }
.status-badge.status-pending { background: #e7f3ff; color: #004085; }
.status-badge.status-testing { background: #fff4e5; color: #663c00; }
.tab-content { display: none; padding: 20px 0; }
.tab-content.active { display: block; }
.pipeline-stage {
    margin: 20px 0;
    padding: 15px;
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
}
.pipeline-stage h4 {
    margin-top: 0;
    padding-bottom: 10px;
    border-bottom: 2px solid #0073aa;
}
.ai-stats-pipeline-controls {
    background: #f9f9f9;
    padding: 20px;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    margin-bottom: 20px;
}
pre {
    background: #f0f0f1;
    padding: 10px;
    overflow-x: auto;
    font-size: 11px;
    max-height: 400px;
}
</style>

<script>
var aiStatsAdmin = {
    ajaxUrl: '<?php echo admin_url('admin-ajax.php'); ?>',
    nonce: '<?php echo wp_create_nonce('ai_stats_admin'); ?>'
};
</script>
<?php wp_enqueue_script('ai-stats-debug', AI_STATS_PLUGIN_URL . 'assets/js/debug.js', array('jquery'), AI_STATS_VERSION, true); ?>

