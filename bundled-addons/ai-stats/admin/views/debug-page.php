<?php
/**
 * AI-Stats Debug Page
 *
 * @package AI_Stats
 * @version 0.2.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get registry and adapters
$registry = AI_Stats_Source_Registry::get_instance();
$adapters = AI_Stats_Adapters::get_instance();
$all_sources = $registry->get_all_sources();

// Test fetch if requested
$test_results = array();
if (isset($_GET['test_mode']) && isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce'], 'ai_stats_debug')) {
    $test_mode = sanitize_text_field($_GET['test_mode']);
    $test_results = $adapters->fetch_candidates($test_mode, array(), array(), 20);
}
?>

<div class="wrap ai-stats-debug">
    <h1><?php esc_html_e('AI-Stats Debug & Diagnostics', 'ai-stats'); ?></h1>
    
    <div class="ai-stats-debug-tabs">
        <nav class="nav-tab-wrapper">
            <a href="#sources" class="nav-tab nav-tab-active"><?php esc_html_e('Data Sources', 'ai-stats'); ?></a>
            <a href="#test" class="nav-tab"><?php esc_html_e('Test Fetch', 'ai-stats'); ?></a>
            <a href="#settings" class="nav-tab"><?php esc_html_e('Configuration', 'ai-stats'); ?></a>
            <a href="#cache" class="nav-tab"><?php esc_html_e('Cache Status', 'ai-stats'); ?></a>
        </nav>
        
        <!-- Sources Tab -->
        <div id="sources" class="tab-content active">
            <h2><?php esc_html_e('Registered Data Sources', 'ai-stats'); ?></h2>
            
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
                                <th><?php esc_html_e('Update', 'ai-stats'); ?></th>
                                <th><?php esc_html_e('Tags', 'ai-stats'); ?></th>
                                <th><?php esc_html_e('Status', 'ai-stats'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($mode_data['sources'] as $index => $source): ?>
                                <tr>
                                    <td><?php echo $index + 1; ?></td>
                                    <td><strong><?php echo esc_html($source['name']); ?></strong></td>
                                    <td>
                                        <span class="badge badge-<?php echo strtolower($source['type']); ?>">
                                            <?php echo esc_html($source['type']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="<?php echo esc_url($source['url']); ?>" target="_blank" class="source-url">
                                            <?php echo esc_html(substr($source['url'], 0, 60)); ?>...
                                        </a>
                                    </td>
                                    <td><?php echo esc_html($source['update']); ?></td>
                                    <td>
                                        <?php foreach ($source['tags'] as $tag): ?>
                                            <span class="tag"><?php echo esc_html($tag); ?></span>
                                        <?php endforeach; ?>
                                    </td>
                                    <td>
                                        <button type="button" class="button button-small test-source" 
                                                data-mode="<?php echo esc_attr($mode_key); ?>" 
                                                data-source="<?php echo esc_attr($index); ?>">
                                            Test
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Test Fetch Tab -->
        <div id="test" class="tab-content">
            <h2><?php esc_html_e('Test Data Fetching', 'ai-stats'); ?></h2>
            
            <div class="ai-stats-test-controls">
                <form method="get" action="">
                    <input type="hidden" name="page" value="ai-stats-debug">
                    <?php wp_nonce_field('ai_stats_debug', '_wpnonce'); ?>
                    
                    <table class="form-table">
                        <tr>
                            <th><label for="test_mode"><?php esc_html_e('Mode', 'ai-stats'); ?></label></th>
                            <td>
                                <select name="test_mode" id="test_mode" class="regular-text">
                                    <option value="">-- Select Mode --</option>
                                    <?php foreach ($all_sources as $mode_key => $mode_data): ?>
                                        <option value="<?php echo esc_attr($mode_key); ?>" 
                                                <?php selected(isset($_GET['test_mode']) ? $_GET['test_mode'] : '', $mode_key); ?>>
                                            <?php echo esc_html($mode_data['mode']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </td>
                        </tr>
                    </table>
                    
                    <?php submit_button(__('Fetch Data', 'ai-stats'), 'primary', 'submit', false); ?>
                </form>
            </div>
            
            <?php if (!empty($test_results)): ?>
                <div class="ai-stats-test-results">
                    <h3><?php esc_html_e('Fetch Results', 'ai-stats'); ?></h3>
                    
                    <?php if (is_wp_error($test_results)): ?>
                        <div class="notice notice-error">
                            <p><strong><?php esc_html_e('Error:', 'ai-stats'); ?></strong> <?php echo esc_html($test_results->get_error_message()); ?></p>
                        </div>
                    <?php elseif (empty($test_results)): ?>
                        <div class="notice notice-warning">
                            <p><?php esc_html_e('No candidates found. This could mean:', 'ai-stats'); ?></p>
                            <ul>
                                <li><?php esc_html_e('Data sources are not returning data', 'ai-stats'); ?></li>
                                <li><?php esc_html_e('Network connectivity issues', 'ai-stats'); ?></li>
                                <li><?php esc_html_e('API keys not configured', 'ai-stats'); ?></li>
                                <li><?php esc_html_e('Sources are rate-limited', 'ai-stats'); ?></li>
                            </ul>
                        </div>
                    <?php else: ?>
                        <div class="notice notice-success">
                            <p><strong><?php echo count($test_results); ?></strong> <?php esc_html_e('candidates fetched successfully', 'ai-stats'); ?></p>
                        </div>
                        
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th style="width: 30px;">#</th>
                                    <th><?php esc_html_e('Title', 'ai-stats'); ?></th>
                                    <th><?php esc_html_e('Source', 'ai-stats'); ?></th>
                                    <th><?php esc_html_e('Published', 'ai-stats'); ?></th>
                                    <th><?php esc_html_e('Score', 'ai-stats'); ?></th>
                                    <th><?php esc_html_e('Tags', 'ai-stats'); ?></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($test_results as $index => $candidate): ?>
                                    <tr>
                                        <td><?php echo $index + 1; ?></td>
                                        <td>
                                            <strong><?php echo esc_html($candidate['title']); ?></strong>
                                            <br>
                                            <small><?php echo esc_html(substr($candidate['blurb_seed'], 0, 100)); ?>...</small>
                                            <br>
                                            <a href="<?php echo esc_url($candidate['url']); ?>" target="_blank">View Source</a>
                                        </td>
                                        <td><?php echo esc_html($candidate['source']); ?></td>
                                        <td><?php echo esc_html(date('Y-m-d H:i', strtotime($candidate['published_at']))); ?></td>
                                        <td><strong><?php echo round($candidate['score']); ?></strong></td>
                                        <td>
                                            <?php foreach ($candidate['tags'] as $tag): ?>
                                                <span class="tag"><?php echo esc_html($tag); ?></span>
                                            <?php endforeach; ?>
                                        </td>
                                    </tr>
                                    <tr class="candidate-details">
                                        <td colspan="6">
                                            <details>
                                                <summary><?php esc_html_e('Raw Data', 'ai-stats'); ?></summary>
                                                <pre><?php echo esc_html(print_r($candidate, true)); ?></pre>
                                            </details>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Configuration Tab -->
        <div id="settings" class="tab-content">
            <h2><?php esc_html_e('Current Configuration', 'ai-stats'); ?></h2>
            
            <?php
            $settings = get_option('ai_stats_settings', array());
            ?>
            
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
        </div>
        
        <!-- Cache Tab -->
        <div id="cache" class="tab-content">
            <h2><?php esc_html_e('Cache Status', 'ai-stats'); ?></h2>
            
            <p><?php esc_html_e('Cache entries are stored as WordPress transients with a 10-minute TTL for manual testing.', 'ai-stats'); ?></p>
            
            <form method="post" action="">
                <?php wp_nonce_field('ai_stats_clear_cache', 'cache_nonce'); ?>
                <input type="hidden" name="action" value="clear_cache">
                <?php submit_button(__('Clear All Cache', 'ai-stats'), 'secondary', 'submit', false); ?>
            </form>
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
.tab-content { display: none; padding: 20px 0; }
.tab-content.active { display: block; }
.candidate-details pre {
    background: #f0f0f1;
    padding: 10px;
    overflow-x: auto;
    font-size: 11px;
}
</style>

<script>
jQuery(document).ready(function($) {
    // Tab switching
    $('.nav-tab').on('click', function(e) {
        e.preventDefault();
        var target = $(this).attr('href');
        
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        
        $('.tab-content').removeClass('active');
        $(target).addClass('active');
    });
});
</script>

