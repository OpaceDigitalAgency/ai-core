<?php
/**
 * AI-Stats Debug Page
 *
 * @package AI_Stats
 * @version 0.7.3
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get registry
$registry = AI_Stats_Source_Registry::get_instance();
$all_sources = $registry->get_all_sources();
$settings = get_option('ai_stats_settings', array());
$ai_core_settings = get_option('ai_core_settings', array());

$provider_labels = array(
    'openai' => __('OpenAI', 'ai-stats'),
    'anthropic' => __('Anthropic Claude', 'ai-stats'),
    'gemini' => __('Google Gemini', 'ai-stats'),
    'grok' => __('xAI Grok', 'ai-stats'),
);

$configured_providers = array();
$provider_models = array();
$provider_options = isset($ai_core_settings['provider_options']) && is_array($ai_core_settings['provider_options'])
    ? $ai_core_settings['provider_options']
    : array();
$provider_metadata = array();
$default_provider = $ai_core_settings['default_provider'] ?? 'openai';

if (class_exists('AI_Core_API')) {
    $ai_core_api = AI_Core_API::get_instance();
    $configured_providers = $ai_core_api->get_configured_providers();

    foreach ($provider_labels as $provider_key => $label) {
        $models = $ai_core_api->get_available_models($provider_key);

        if (empty($models) && class_exists('\\AICore\\Registry\\ModelRegistry')) {
            $models = \AICore\Registry\ModelRegistry::getModelsByProvider($provider_key);
        }

        $provider_models[$provider_key] = $models;

        if (method_exists($ai_core_api, 'get_provider_settings')) {
            $provider_settings = $ai_core_api->get_provider_settings($provider_key);
            if (!empty($provider_settings['options'])) {
                $provider_options[$provider_key] = $provider_settings['options'];
            }
        }
    }

    if (method_exists($ai_core_api, 'get_default_provider')) {
        $default_provider = $ai_core_api->get_default_provider();
    }
} else {
    $configured_providers = array_keys($provider_labels);

    if (class_exists('\\AICore\\Registry\\ModelRegistry')) {
        foreach ($provider_labels as $provider_key => $label) {
            $provider_models[$provider_key] = \AICore\Registry\ModelRegistry::getModelsByProvider($provider_key);
        }
    } else {
        foreach ($provider_labels as $provider_key => $label) {
            $provider_models[$provider_key] = array();
        }
    }
}

if (class_exists('\\AICore\\Registry\\ModelRegistry')) {
    $provider_metadata = \AICore\Registry\ModelRegistry::exportProviderMetadata();
}

foreach ($provider_labels as $provider_key => $label) {
    if (!isset($provider_models[$provider_key])) {
        $provider_models[$provider_key] = array();
    }
    if (!isset($provider_options[$provider_key])) {
        $provider_options[$provider_key] = array();
    }
}
$debug_script_data = array(
    'providers' => array(
        'labels' => $provider_labels,
        'configured' => array_values($configured_providers),
        'models' => $provider_models,
        'default' => $default_provider,
        'options' => $provider_options,
        'meta' => $provider_metadata,
    ),
    'aiStatsSettings' => array(
        'preferred_provider' => $settings['preferred_provider'] ?? '',
        'preferred_model' => $settings['preferred_model'] ?? '',
    ),
);
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
                        <th><label for="pipeline-keywords"><?php esc_html_e('Keyword', 'ai-stats'); ?></label></th>
                        <td>
                            <input type="text" id="pipeline-keywords" class="regular-text" placeholder="e.g. SEO">
                            <p class="description">
                                <?php esc_html_e('Enter a single keyword (optional). AI will automatically expand it to include synonyms and related terms.', 'ai-stats'); ?>
                                <br>
                                <em><?php esc_html_e('Example: "SEO" will also search for "search engine optimisation", "Google ranking", "organic search", etc.', 'ai-stats'); ?></em>
                            </p>
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

                <!-- Pipeline Stage Tabs -->
                <div class="pipeline-tabs">
                    <nav class="nav-tab-wrapper" style="margin-bottom: 0;">
                        <a href="#stage-fetch" class="pipeline-tab nav-tab nav-tab-active" data-stage="1">1️⃣ Fetch from Sources</a>
                        <a href="#stage-normalised" class="pipeline-tab nav-tab" data-stage="2">2️⃣ Normalised Data</a>
                        <a href="#stage-filtered" class="pipeline-tab nav-tab" data-stage="3">3️⃣ Filtered by Keywords</a>
                        <a href="#stage-ranked" class="pipeline-tab nav-tab" data-stage="4">4️⃣ Ranked by Score (Final)</a>
                        <a href="#stage-ai" class="pipeline-tab nav-tab" data-stage="5">5️⃣ AI Generation Test</a>
                    </nav>

                    <!-- Stage 1: Fetch from Sources -->
                    <div id="stage-fetch" class="pipeline-stage-content active">
                        <div class="pipeline-stage-header">
                            <h4>1️⃣ Fetch from Sources</h4>
                            <p class="description">Raw data fetched from all configured sources</p>
                        </div>
                        <div id="fetch-results"></div>
                    </div>

                    <!-- Stage 2: Normalised Data -->
                    <div id="stage-normalised" class="pipeline-stage-content">
                        <div class="pipeline-stage-header">
                            <h4>2️⃣ Normalised Data</h4>
                            <p class="description">All candidates in standardised format</p>
                        </div>
                        <div id="normalised-results"></div>
                        <div id="normalised-json-viewer" class="json-viewer-container"></div>
                    </div>

                    <!-- Stage 3: Filtered by Keywords -->
                    <div id="stage-filtered" class="pipeline-stage-content">
                        <div class="pipeline-stage-header">
                            <h4>3️⃣ Filtered by Keywords</h4>
                            <p class="description">Candidates matching keyword criteria</p>
                        </div>

                        <!-- Filter Controls -->
                        <div class="pipeline-controls">
                            <h5>Filter Configuration</h5>
                            <table class="form-table">
                                <tr>
                                    <th><label for="filter-method">Filter Method</label></th>
                                    <td>
                                        <select id="filter-method" class="regular-text">
                                            <option value="contains">Contains (case-insensitive)</option>
                                            <option value="contains-case">Contains (case-sensitive)</option>
                                            <option value="regex">Regular Expression</option>
                                            <option value="fuzzy">Fuzzy Match</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="filter-fields">Search In</label></th>
                                    <td>
                                        <select id="filter-fields" class="regular-text">
                                            <option value="both">Title + Content</option>
                                            <option value="title">Title Only</option>
                                            <option value="content">Content Only</option>
                                        </select>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="filter-threshold">Match Threshold</label></th>
                                    <td>
                                        <input type="number" id="filter-threshold" class="small-text" value="1" min="1" max="10">
                                        <p class="description">Minimum number of keywords that must match</p>
                                    </td>
                                </tr>
                            </table>
                            <button type="button" id="rerun-filter" class="button button-secondary">
                                <span class="dashicons dashicons-update"></span> Re-run Filter
                            </button>
                        </div>

                        <div id="filtered-results"></div>
                        <div id="filtered-json-viewer" class="json-viewer-container"></div>
                    </div>

                    <!-- Stage 4: Ranked by Score (Final) -->
                    <div id="stage-ranked" class="pipeline-stage-content">
                        <div class="pipeline-stage-header">
                            <h4>4️⃣ Ranked by Score (Final Candidates)</h4>
                            <p class="description">Candidates scored and sorted by relevance - these are the final candidates used for content generation</p>
                        </div>

                        <!-- Scoring Controls -->
                        <div class="pipeline-controls">
                            <h5>Scoring Configuration</h5>
                            <p class="description" style="margin-bottom: 15px;">
                                <strong>Scoring Priority:</strong>
                                1️⃣ Keyword Density (0-50 pts) - How well content matches your keywords<br>
                                2️⃣ Freshness (0-30 pts) - How recent the content is<br>
                                3️⃣ Source Authority (0-20 pts) - Reputation of the source (mode-specific)<br>
                                4️⃣ Confidence (0-10 pts) - Data quality score
                            </p>
                            <table class="form-table">
                                <tr>
                                    <th><label for="score-freshness">Freshness Weight</label></th>
                                    <td>
                                        <input type="range" id="score-freshness" min="0" max="100" value="30" class="score-slider">
                                        <span class="score-value">30</span>
                                        <p class="description">Weight for content recency (0-100)</p>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="score-authority">Source Authority Weight</label></th>
                                    <td>
                                        <input type="range" id="score-authority" min="0" max="100" value="20" class="score-slider">
                                        <span class="score-value">20</span>
                                        <p class="description">Weight for authoritative sources (0-100) - varies by mode</p>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label for="score-confidence">Confidence Weight</label></th>
                                    <td>
                                        <input type="range" id="score-confidence" min="0" max="100" value="10" class="score-slider">
                                        <span class="score-value">10</span>
                                        <p class="description">Weight for data confidence (0-100)</p>
                                    </td>
                                </tr>
                                <tr>
                                    <th><label>Keyword Density</label></th>
                                    <td>
                                        <p class="description">
                                            <strong>Automatically calculated (0-50 points)</strong><br>
                                            Based on: keyword variety (0-25 pts) + keyword frequency (0-25 pts)<br>
                                            <em>This is the primary ranking factor for relevance</em>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                            <button type="button" id="rerun-scoring" class="button button-secondary">
                                <span class="dashicons dashicons-update"></span> Re-run Scoring
                            </button>
                        </div>

                        <div id="ranked-results"></div>
                        <div id="ranked-json-viewer" class="json-viewer-container"></div>
                    </div>

                    <!-- Stage 5: AI Generation Test -->
                    <div id="stage-ai" class="pipeline-stage-content">
                        <div class="pipeline-stage-header">
                            <h4>5️⃣ AI Generation Test</h4>
                            <p class="description">Test AI content generation with current candidates</p>
                        </div>
                        <div id="ai-generation-test"></div>
                    </div>
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

/* Pipeline Tabs */
.pipeline-tabs {
    background: #fff;
    border: 1px solid #ccd0d4;
    border-radius: 4px;
    margin-top: 15px;
}
.pipeline-tabs .nav-tab-wrapper {
    border-bottom: 1px solid #ccd0d4;
    padding: 0;
    margin: 0;
}
.pipeline-stage-content {
    display: none;
    padding: 20px;
}
.pipeline-stage-content.active {
    display: block;
}
.pipeline-stage-header {
    margin-bottom: 20px;
    padding-bottom: 15px;
    border-bottom: 2px solid #0073aa;
}
.pipeline-stage-header h4 {
    margin: 0 0 5px 0;
    padding: 0;
    border: none;
}
.pipeline-stage-header .description {
    margin: 0;
    color: #666;
}

/* Pipeline Controls */
.pipeline-controls {
    background: #f9f9f9;
    padding: 20px;
    border: 1px solid #ddd;
    border-radius: 4px;
    margin-bottom: 20px;
}
.pipeline-controls h5 {
    margin-top: 0;
    margin-bottom: 15px;
}
.pipeline-controls .form-table th {
    width: 200px;
}
.score-slider {
    width: 300px;
    vertical-align: middle;
}
.score-value {
    display: inline-block;
    min-width: 40px;
    font-weight: bold;
    margin-left: 10px;
}

/* JSON Viewer */
.json-viewer-container {
    margin-top: 20px;
}
.json-viewer-toggle {
    background: #0073aa;
    color: #fff;
    border: none;
    padding: 8px 15px;
    border-radius: 4px;
    cursor: pointer;
    font-size: 13px;
    margin-bottom: 10px;
}
.json-viewer-toggle:hover {
    background: #005a87;
}
.json-viewer-toggle .dashicons {
    vertical-align: middle;
    margin-right: 5px;
}
.json-viewer-content {
    display: none;
    background: #1e1e1e;
    color: #d4d4d4;
    padding: 15px;
    border-radius: 4px;
    max-height: 600px;
    overflow: auto;
    font-family: 'Courier New', monospace;
    font-size: 12px;
    line-height: 1.5;
}
.json-viewer-content.expanded {
    display: block;
}
.json-viewer-search {
    margin-bottom: 10px;
}
.json-viewer-search input {
    width: 300px;
    padding: 5px 10px;
}
.json-highlight {
    background-color: #ffd700;
    color: #000;
}

/* Statistics Cards */
.pipeline-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin-bottom: 20px;
}
.stat-card {
    background: #fff;
    border: 1px solid #ddd;
    border-radius: 4px;
    padding: 15px;
    text-align: center;
}
.stat-card .stat-value {
    font-size: 32px;
    font-weight: bold;
    color: #0073aa;
    margin: 10px 0;
}
.stat-card .stat-label {
    font-size: 13px;
    color: #666;
    text-transform: uppercase;
}
.stat-card .stat-change {
    font-size: 12px;
    margin-top: 5px;
}
.stat-change.positive {
    color: #46b450;
}
.stat-change.negative {
    color: #dc3232;
}
</style>

<script>
var aiStatsAdmin = Object.assign({}, window.aiStatsAdmin || {}, {
    ajaxUrl: '<?php echo esc_js(admin_url('admin-ajax.php')); ?>',
    nonce: '<?php echo esc_js(wp_create_nonce('ai_stats_admin')); ?>'
});
var aiStatsDebugData = <?php echo wp_json_encode($debug_script_data); ?>;
</script>
<?php wp_enqueue_script('ai-stats-debug', AI_STATS_PLUGIN_URL . 'assets/js/debug.js', array('jquery'), AI_STATS_VERSION, true); ?>
