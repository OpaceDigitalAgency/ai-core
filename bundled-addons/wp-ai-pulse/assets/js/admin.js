/**
 * AI-Pulse Admin JavaScript
 *
 * @package AI_Pulse
 * @version 1.0.0
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Tab switching
        $('.ai-pulse-tab-btn').on('click', function() {
            const tab = $(this).data('tab');
            $('.ai-pulse-tab-btn').removeClass('active');
            $('.ai-pulse-tab-panel').removeClass('active');
            $(this).addClass('active');
            $('#tab-' + tab).addClass('active');
        });

        // Mode description update
        $('#test-mode').on('change', function() {
            const mode = $(this).val();
            if (aiPulseAdmin.modes && aiPulseAdmin.modes[mode]) {
                $('#mode-description').text(aiPulseAdmin.modes[mode].description);
            }
        }).trigger('change');

        // Test form submission
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
                        displayResults(response.data);
                        $('#test-results').show();
                    } else {
                        $('#error-message').text(response.data.message || 'An error occurred');
                        $('#test-error').show();
                    }
                },
                error: function(xhr, status, error) {
                    $('#test-loading').hide();
                    $('#error-message').text('Network error: ' + error);
                    $('#test-error').show();
                }
            });
        });

        function displayResults(data) {
            // Preview tab
            $('#content-preview').html(data.html);

            // JSON tab
            try {
                const jsonData = JSON.parse(data.json);
                $('#content-json').text(JSON.stringify(jsonData, null, 2));
            } catch (e) {
                $('#content-json').text(data.json);
            }

            // Sources tab
            let sourcesHtml = '<h4>Search Grounding Sources</h4>';
            if (data.sources && data.sources.length > 0) {
                sourcesHtml += '<ul class="ai-pulse-sources-list">';
                data.sources.forEach(function(source) {
                    sourcesHtml += '<li><a href="' + escapeHtml(source.uri) + '" target="_blank" rel="noopener">' + 
                                   escapeHtml(source.title) + '</a></li>';
                });
                sourcesHtml += '</ul>';
            } else {
                sourcesHtml += '<p>No sources available</p>';
            }
            $('#content-sources').html(sourcesHtml);

            // Usage tab
            const usageHtml = '<div class="ai-pulse-usage-stats">' +
                '<div class="usage-stat"><strong>Input Tokens:</strong> ' + formatNumber(data.tokens.input) + '</div>' +
                '<div class="usage-stat"><strong>Output Tokens:</strong> ' + formatNumber(data.tokens.output) + '</div>' +
                '<div class="usage-stat"><strong>Total Tokens:</strong> ' + formatNumber(data.tokens.total) + '</div>' +
                '<div class="usage-stat"><strong>Cost:</strong> $' + data.cost + ' USD</div>' +
                '</div>';
            $('#content-usage').html(usageHtml);
        }

        function formatNumber(num) {
            return parseInt(num).toLocaleString();
        }

        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        }

        // Content library actions
        $('.view-content').on('click', function() {
            const id = $(this).data('id');
            // Modal functionality would go here
            alert('View content ID: ' + id);
        });

        $('.delete-content').on('click', function() {
            if (!confirm('Are you sure you want to delete this content?')) {
                return;
            }

            const id = $(this).data('id');
            const row = $(this).closest('tr');

            $.ajax({
                url: aiPulseAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ai_pulse_delete_content',
                    nonce: aiPulseAdmin.nonce,
                    id: id
                },
                success: function(response) {
                    if (response.success) {
                        row.fadeOut(function() {
                            $(this).remove();
                        });
                    } else {
                        alert('Failed to delete content: ' + (response.data.message || 'Unknown error'));
                    }
                },
                error: function() {
                    alert('Network error occurred');
                }
            });
        });
    });

})(jQuery);

