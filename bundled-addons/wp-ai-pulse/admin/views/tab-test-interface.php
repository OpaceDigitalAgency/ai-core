<?php
/**
 * Test Interface Tab
 *
 * @package AI_Pulse
 * @version 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

$modes = AI_Pulse_Modes::get_all_modes();
$default_location = AI_Pulse_Settings::get('default_location', 'Birmingham, West Midlands, UK');
?>

<div class="ai-pulse-test-interface">
    <div class="ai-pulse-card">
        <h2>Test Content Generation</h2>
        <p>Generate AI-Pulse content on-demand to test different keywords, modes, and settings.</p>

        <form id="ai-pulse-test-form" class="ai-pulse-form">
            <div class="form-row">
                <div class="form-group">
                    <label for="test-keyword">Keyword *</label>
                    <input type="text" id="test-keyword" name="keyword" placeholder="e.g., SEO, Web Design, Digital Marketing" required>
                </div>

                <div class="form-group">
                    <label for="test-mode">Analysis Mode *</label>
                    <select id="test-mode" name="mode" required>
                        <?php foreach ($modes as $mode_id => $mode_data): ?>
                            <option value="<?php echo esc_attr($mode_id); ?>">
                                <?php echo esc_html($mode_data['icon'] . ' ' . $mode_data['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <p class="description" id="mode-description"></p>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="test-period">Time Period *</label>
                    <select id="test-period" name="period" required>
                        <option value="daily">Daily (Last 24 hours)</option>
                        <option value="weekly" selected>Weekly (Last 7 days)</option>
                        <option value="monthly">Monthly (Last 30 days)</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="test-location">Location</label>
                    <input type="text" id="test-location" name="location" value="<?php echo esc_attr($default_location); ?>" placeholder="Birmingham, West Midlands, UK">
                </div>
            </div>

            <div class="form-actions">
                <button type="submit" class="button button-primary button-large">
                    <span class="dashicons dashicons-update"></span>
                    Generate Content
                </button>
            </div>
        </form>
    </div>

    <div id="test-results" class="ai-pulse-card" style="display: none;">
        <h2>Generated Content</h2>
        
        <div class="ai-pulse-tabs">
            <button class="ai-pulse-tab-btn active" data-tab="preview">Preview</button>
            <button class="ai-pulse-tab-btn" data-tab="json">JSON Data</button>
            <button class="ai-pulse-tab-btn" data-tab="sources">Sources</button>
            <button class="ai-pulse-tab-btn" data-tab="usage">Usage</button>
        </div>

        <div class="ai-pulse-tab-panel active" id="tab-preview">
            <div id="content-preview"></div>
        </div>

        <div class="ai-pulse-tab-panel" id="tab-json">
            <pre id="content-json"></pre>
        </div>

        <div class="ai-pulse-tab-panel" id="tab-sources">
            <div id="content-sources"></div>
        </div>

        <div class="ai-pulse-tab-panel" id="tab-usage">
            <div id="content-usage"></div>
        </div>
    </div>

    <div id="test-loading" class="ai-pulse-loading" style="display: none;">
        <div class="ai-pulse-spinner"></div>
        <p>Generating content with Google Gemini...</p>
    </div>

    <div id="test-error" class="notice notice-error" style="display: none;">
        <p id="error-message"></p>
    </div>
</div>

<script>
jQuery(document).ready(function($) {
    // Update mode description
    $('#test-mode').on('change', function() {
        const mode = $(this).val();
        const modes = <?php echo json_encode($modes); ?>;
        $('#mode-description').text(modes[mode].description);
    }).trigger('change');

    // Tab switching
    $('.ai-pulse-tab-btn').on('click', function() {
        const tab = $(this).data('tab');
        $('.ai-pulse-tab-btn').removeClass('active');
        $('.ai-pulse-tab-panel').removeClass('active');
        $(this).addClass('active');
        $('#tab-' + tab).addClass('active');
    });

    // Form submission
    $('#ai-pulse-test-form').on('submit', function(e) {
        e.preventDefault();

        $('#test-results').hide();
        $('#test-error').hide();
        $('#test-loading').show();

        $.ajax({
            url: aiPulseAdmin.ajaxUrl,
            type: 'POST',
            data: {
                action: 'ai_pulse_test_generate',
                nonce: aiPulseAdmin.nonce,
                keyword: $('#test-keyword').val(),
                mode: $('#test-mode').val(),
                period: $('#test-period').val(),
                location: $('#test-location').val()
            },
            success: function(response) {
                $('#test-loading').hide();

                if (response.success) {
                    $('#content-preview').html(response.data.html);
                    $('#content-json').text(JSON.stringify(JSON.parse(response.data.json), null, 2));

                    // Display sources with better UX
                    let sourcesHtml = '';
                    if (response.data.sources && response.data.sources.length > 0) {
                        sourcesHtml = '<h4>Search Grounding Sources (' + response.data.sources.length + ')</h4>';
                        sourcesHtml += '<ul>';
                        response.data.sources.forEach(function(source) {
                            sourcesHtml += '<li><a href="' + source.uri + '" target="_blank" rel="noopener">' + source.title + '</a></li>';
                        });
                        sourcesHtml += '</ul>';
                    } else {
                        sourcesHtml = '<div class="ai-pulse-no-sources">No sources available. Google Search Grounding may not have returned results for this query.</div>';
                    }
                    $('#content-sources').html(sourcesHtml);

                    // Display usage stats
                    $('#content-usage').html(
                        '<p><span>Input Tokens:</span> <strong>' + response.data.tokens.input.toLocaleString() + '</strong></p>' +
                        '<p><span>Output Tokens:</span> <strong>' + response.data.tokens.output.toLocaleString() + '</strong></p>' +
                        '<p><span>Total Tokens:</span> <strong>' + response.data.tokens.total.toLocaleString() + '</strong></p>' +
                        '<p><span>Cost:</span> <strong>$' + response.data.cost + '</strong></p>' +
                        (response.data.stored_id ? '<p><span>Saved to Library:</span> <strong>ID #' + response.data.stored_id + '</strong></p>' : '')
                    );

                    $('#test-results').show();

                    // Scroll to results
                    $('html, body').animate({
                        scrollTop: $('#test-results').offset().top - 100
                    }, 500);
                } else {
                    $('#error-message').text(response.data.message);
                    $('#test-error').show();
                }
            },
            error: function() {
                $('#test-loading').hide();
                $('#error-message').text('An unexpected error occurred');
                $('#test-error').show();
            }
        });
    });
});
</script>

