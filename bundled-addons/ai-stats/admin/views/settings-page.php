<?php
/**
 * AI-Stats Settings Page
 *
 * @package AI_Stats
 * @version 0.3.3
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap ai-stats-settings">
    <h1><?php esc_html_e('AI-Stats Settings', 'ai-stats'); ?></h1>
    
    <?php settings_errors('ai_stats_settings'); ?>
    
    <form method="post" action="">
        <?php wp_nonce_field('ai_stats_settings', 'ai_stats_settings_nonce'); ?>
        
        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="active_mode"><?php esc_html_e('Active Mode', 'ai-stats'); ?></label>
                </th>
                <td>
                    <select name="active_mode" id="active_mode" class="regular-text">
                        <?php foreach ($modes as $mode_key => $mode): ?>
                            <option value="<?php echo esc_attr($mode_key); ?>" <?php selected($settings['active_mode'] ?? 'statistics', $mode_key); ?>>
                                <?php echo esc_html($mode['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description"><?php esc_html_e('Select which content mode to use', 'ai-stats'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="update_frequency"><?php esc_html_e('Update Frequency', 'ai-stats'); ?></label>
                </th>
                <td>
                    <select name="update_frequency" id="update_frequency" class="regular-text">
                        <option value="daily" <?php selected($settings['update_frequency'] ?? 'daily', 'daily'); ?>>
                            <?php esc_html_e('Daily', 'ai-stats'); ?>
                        </option>
                        <option value="weekly" <?php selected($settings['update_frequency'] ?? 'daily', 'weekly'); ?>>
                            <?php esc_html_e('Weekly', 'ai-stats'); ?>
                        </option>
                        <option value="manual" <?php selected($settings['update_frequency'] ?? 'daily', 'manual'); ?>>
                            <?php esc_html_e('Manual Only', 'ai-stats'); ?>
                        </option>
                    </select>
                    <p class="description"><?php esc_html_e('How often to automatically update content', 'ai-stats'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <?php esc_html_e('Automation', 'ai-stats'); ?>
                </th>
                <td>
                    <fieldset>
                        <label>
                            <input type="checkbox" name="auto_update" value="1" <?php checked(!empty($settings['auto_update'])); ?>>
                            <?php esc_html_e('Enable automatic content updates', 'ai-stats'); ?>
                        </label>
                        <p class="description"><?php esc_html_e('Automatically generate new content based on update frequency', 'ai-stats'); ?></p>
                    </fieldset>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <label for="default_style"><?php esc_html_e('Default Style', 'ai-stats'); ?></label>
                </th>
                <td>
                    <select name="default_style" id="default_style" class="regular-text">
                        <option value="box" <?php selected($settings['default_style'] ?? 'box', 'box'); ?>>
                            <?php esc_html_e('Box', 'ai-stats'); ?>
                        </option>
                        <option value="inline" <?php selected($settings['default_style'] ?? 'box', 'inline'); ?>>
                            <?php esc_html_e('Inline', 'ai-stats'); ?>
                        </option>
                        <option value="sidebar" <?php selected($settings['default_style'] ?? 'box', 'sidebar'); ?>>
                            <?php esc_html_e('Sidebar Widget', 'ai-stats'); ?>
                        </option>
                    </select>
                    <p class="description"><?php esc_html_e('Default display style for shortcode', 'ai-stats'); ?></p>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <?php esc_html_e('Caching', 'ai-stats'); ?>
                </th>
                <td>
                    <fieldset>
                        <label>
                            <input type="checkbox" name="enable_caching" value="1" <?php checked(!empty($settings['enable_caching'])); ?>>
                            <?php esc_html_e('Enable data caching', 'ai-stats'); ?>
                        </label>
                        <p class="description"><?php esc_html_e('Cache scraped data to reduce external requests', 'ai-stats'); ?></p>
                        
                        <label style="margin-top: 10px; display: block;">
                            <?php esc_html_e('Cache Duration (seconds):', 'ai-stats'); ?>
                            <input type="number" name="cache_duration" value="<?php echo esc_attr($settings['cache_duration'] ?? 86400); ?>" class="small-text">
                        </label>
                        <p class="description"><?php esc_html_e('Default: 86400 (24 hours)', 'ai-stats'); ?></p>
                    </fieldset>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <?php esc_html_e('Performance Tracking', 'ai-stats'); ?>
                </th>
                <td>
                    <fieldset>
                        <label>
                            <input type="checkbox" name="enable_tracking" value="1" <?php checked(!empty($settings['enable_tracking'])); ?>>
                            <?php esc_html_e('Enable performance tracking', 'ai-stats'); ?>
                        </label>
                        <p class="description"><?php esc_html_e('Track impressions and clicks (Coming Soon)', 'ai-stats'); ?></p>
                    </fieldset>
                </td>
            </tr>
            
            <tr>
                <th scope="row">
                    <?php esc_html_e('Birmingham Focus', 'ai-stats'); ?>
                </th>
                <td>
                    <fieldset>
                        <label>
                            <input type="checkbox" name="birmingham_focus" value="1" <?php checked(!empty($settings['birmingham_focus'])); ?>>
                            <?php esc_html_e('Prioritise Birmingham-specific data', 'ai-stats'); ?>
                        </label>
                        <p class="description"><?php esc_html_e('Focus on Birmingham business statistics when available', 'ai-stats'); ?></p>
                    </fieldset>
                </td>
            </tr>
        </table>

        <h2><?php esc_html_e('Google Cloud Integration', 'ai-stats'); ?></h2>
        <p><?php esc_html_e('Connect to Google Cloud to access BigQuery for Google Trends data and other Google services.', 'ai-stats'); ?></p>

        <table class="form-table">
            <tr>
                <th scope="row">
                    <label for="gcp_project_id"><?php esc_html_e('Google Cloud Project ID', 'ai-stats'); ?></label>
                </th>
                <td>
                    <input type="text" name="gcp_project_id" id="gcp_project_id" value="<?php echo esc_attr($settings['gcp_project_id'] ?? ''); ?>" class="regular-text" placeholder="gen-lang-client-0688797223">
                    <p class="description">
                        <?php esc_html_e('Your Google Cloud Project ID (e.g., gen-lang-client-0688797223). Find this in your', 'ai-stats'); ?>
                        <a href="https://console.cloud.google.com/home/dashboard" target="_blank"><?php esc_html_e('Google Cloud Console', 'ai-stats'); ?></a>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="gcp_service_account_json"><?php esc_html_e('Service Account JSON', 'ai-stats'); ?></label>
                </th>
                <td>
                    <textarea name="gcp_service_account_json" id="gcp_service_account_json" rows="8" class="large-text code" placeholder='{"type": "service_account", "project_id": "...", ...}'><?php echo esc_textarea($settings['gcp_service_account_json'] ?? ''); ?></textarea>
                    <p class="description">
                        <?php esc_html_e('Paste your Google Cloud Service Account JSON credentials here.', 'ai-stats'); ?><br>
                        <strong><?php esc_html_e('How to get this:', 'ai-stats'); ?></strong><br>
                        1. <?php esc_html_e('Go to', 'ai-stats'); ?> <a href="https://console.cloud.google.com/iam-admin/serviceaccounts" target="_blank"><?php esc_html_e('IAM & Admin > Service Accounts', 'ai-stats'); ?></a><br>
                        2. <?php esc_html_e('Create a service account or select an existing one', 'ai-stats'); ?><br>
                        3. <?php esc_html_e('Click "Keys" > "Add Key" > "Create new key" > "JSON"', 'ai-stats'); ?><br>
                        4. <?php esc_html_e('Copy the entire JSON file contents here', 'ai-stats'); ?><br>
                        5. <?php esc_html_e('Required permissions: BigQuery Data Viewer, BigQuery Job User', 'ai-stats'); ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <?php esc_html_e('BigQuery Features', 'ai-stats'); ?>
                </th>
                <td>
                    <fieldset>
                        <label>
                            <input type="checkbox" name="enable_bigquery_trends" value="1" <?php checked(!empty($settings['enable_bigquery_trends'])); ?>>
                            <?php esc_html_e('Enable Google Trends data via BigQuery', 'ai-stats'); ?>
                        </label>
                        <p class="description">
                            <?php esc_html_e('Access Google Trends public dataset for Top 25 trending searches (last 30 days). Free tier covers light usage with no scraping required.', 'ai-stats'); ?>
                        </p>
                        <div style="margin-top: 10px;">
                            <button type="button" id="test-bigquery-connection" class="button button-secondary">
                                <?php esc_html_e('Test BigQuery Connection', 'ai-stats'); ?>
                            </button>
                            <span id="bigquery-test-result" style="margin-left: 10px;"></span>
                        </div>
                    </fieldset>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="bigquery_region"><?php esc_html_e('BigQuery Region', 'ai-stats'); ?></label>
                </th>
                <td>
                    <select name="bigquery_region" id="bigquery_region" class="regular-text">
                        <option value="US" <?php selected($settings['bigquery_region'] ?? 'US', 'US'); ?>>United States (US)</option>
                        <option value="EU" <?php selected($settings['bigquery_region'] ?? 'US', 'EU'); ?>>European Union (EU)</option>
                        <option value="GB" <?php selected($settings['bigquery_region'] ?? 'US', 'GB'); ?>>United Kingdom (GB)</option>
                    </select>
                    <p class="description"><?php esc_html_e('Geographic region for Google Trends data. Choose GB for UK-specific trends.', 'ai-stats'); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="google_api_key"><?php esc_html_e('Google API Key (Optional)', 'ai-stats'); ?></label>
                </th>
                <td>
                    <input type="text" name="google_api_key" id="google_api_key" value="<?php echo esc_attr($settings['google_api_key'] ?? ''); ?>" class="regular-text">
                    <p class="description"><?php esc_html_e('For additional Google services like CrUX API (Core Web Vitals data)', 'ai-stats'); ?></p>
                </td>
            </tr>
        </table>

        <h2><?php esc_html_e('Other API Keys', 'ai-stats'); ?></h2>
        <table class="form-table">

            <tr>
                <th scope="row">
                    <label for="companies_house_api_key"><?php esc_html_e('Companies House API Key', 'ai-stats'); ?></label>
                </th>
                <td>
                    <input type="text" name="companies_house_api_key" id="companies_house_api_key" value="<?php echo esc_attr($settings['companies_house_api_key'] ?? ''); ?>" class="regular-text">
                    <p class="description"><?php esc_html_e('For UK company registration data', 'ai-stats'); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="crux_test_url"><?php esc_html_e('CrUX Test URL', 'ai-stats'); ?></label>
                </th>
                <td>
                    <input type="url" name="crux_test_url" id="crux_test_url" value="<?php echo esc_attr($settings['crux_test_url'] ?? get_site_url()); ?>" class="regular-text">
                    <p class="description"><?php esc_html_e('URL to test with CrUX API (defaults to site URL)', 'ai-stats'); ?></p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="preferred_provider"><?php esc_html_e('Preferred AI Provider', 'ai-stats'); ?></label>
                </th>
                <td>
                    <?php
                    // Get configured providers from AI-Core
                    $configured_providers = array();
                    $ai_core_settings = get_option('ai_core_settings', array());
                    $default_provider = $ai_core_settings['default_provider'] ?? 'openai';

                    if (class_exists('AI_Core_API')) {
                        $api = AI_Core_API::get_instance();
                        $configured_providers = $api->get_configured_providers();
                    }

                    // Fallback to all providers if none configured
                    if (empty($configured_providers)) {
                        $configured_providers = array('openai', 'anthropic', 'gemini', 'grok');
                    }

                    $provider_names = array(
                        'openai' => 'OpenAI',
                        'anthropic' => 'Anthropic',
                        'gemini' => 'Google Gemini',
                        'grok' => 'xAI Grok',
                    );

                    $current_provider = $settings['preferred_provider'] ?? $default_provider;
                    ?>
                    <select name="preferred_provider" id="preferred_provider" class="regular-text">
                        <?php foreach ($configured_providers as $provider): ?>
                            <option value="<?php echo esc_attr($provider); ?>" <?php selected($current_provider, $provider); ?>>
                                <?php echo esc_html($provider_names[$provider] ?? ucfirst($provider)); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description">
                        <?php
                        if (count($configured_providers) < 4) {
                            esc_html_e('Showing only configured providers from AI-Core. ', 'ai-stats');
                            echo '<a href="' . esc_url(admin_url('admin.php?page=ai-core-settings')) . '">' . esc_html__('Configure more providers', 'ai-stats') . '</a>';
                        } else {
                            esc_html_e('Select AI provider for content generation', 'ai-stats');
                        }
                        ?>
                    </p>
                </td>
            </tr>

            <tr>
                <th scope="row">
                    <label for="preferred_model"><?php esc_html_e('Preferred AI Model', 'ai-stats'); ?></label>
                </th>
                <td>
                    <?php
                    // Get available models for current provider
                    $available_models = array();
                    if (class_exists('AI_Core_API') && !empty($current_provider)) {
                        $api = AI_Core_API::get_instance();
                        $available_models = $api->get_available_models($current_provider);
                    }

                    $current_model = $settings['preferred_model'] ?? '';
                    ?>
                    <select name="preferred_model" id="preferred_model" class="regular-text">
                        <?php if (empty($available_models)): ?>
                            <option value=""><?php esc_html_e('Default model for provider', 'ai-stats'); ?></option>
                        <?php else: ?>
                            <option value=""><?php esc_html_e('Auto-select (recommended)', 'ai-stats'); ?></option>
                            <?php foreach ($available_models as $model): ?>
                                <option value="<?php echo esc_attr($model); ?>" <?php selected($current_model, $model); ?>>
                                    <?php echo esc_html($model); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <p class="description">
                        <?php esc_html_e('Select specific model or leave as auto-select. Change provider above to see different models.', 'ai-stats'); ?>
                        <span id="ai-stats-model-loading" style="display:none;"> <?php esc_html_e('Loading models...', 'ai-stats'); ?></span>
                    </p>
                </td>
            </tr>
        </table>

        <?php submit_button(__('Save Settings', 'ai-stats')); ?>
    </form>
    
    <hr>
    
    <h2><?php esc_html_e('Mode Information', 'ai-stats'); ?></h2>
    <div class="ai-stats-modes-info">
        <?php foreach ($modes as $mode_key => $mode): ?>
            <div class="mode-info-card">
                <h3>
                    <span class="dashicons <?php echo esc_attr($mode['icon']); ?>"></span>
                    <?php echo esc_html($mode['name']); ?>
                </h3>
                <p><?php echo esc_html($mode['description']); ?></p>
                <p class="mode-frequency">
                    <strong><?php esc_html_e('Recommended Update Frequency:', 'ai-stats'); ?></strong>
                    <?php echo esc_html(ucfirst($mode['update_frequency'])); ?>
                </p>
            </div>
        <?php endforeach; ?>
    </div>
</div>

