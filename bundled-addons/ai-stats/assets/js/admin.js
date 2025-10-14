/**
 * AI-Stats Admin JavaScript
 *
 * @package AI_Stats
 * @version 0.3.3
 */

(function($) {
    'use strict';
    
    const AIStatsAdmin = {
        
        init: function() {
            this.bindEvents();
        },
        
        bindEvents: function() {
            // Legacy generate button (now opens modal)
            $(document).on('click', '#ai-stats-generate-now, #ai-stats-generate-first', this.openFetchModal.bind(this));

            // New manual workflow buttons
            $(document).on('click', '#ai-stats-fetch-preview', this.fetchCandidates.bind(this));
            $(document).on('click', '#ai-stats-generate-draft', this.generateDraft.bind(this));
            $(document).on('click', '#ai-stats-publish-module', this.publishModule.bind(this));
            $(document).on('click', '.ai-stats-toggle-candidate', this.toggleCandidate.bind(this));
            $(document).on('click', '#ai-stats-close-modal', this.closeModal.bind(this));

            // Settings page buttons
            $(document).on('click', '#test-bigquery-connection', this.testBigQueryConnection.bind(this));
            $(document).on('change', '#preferred_provider', this.updateModelDropdown.bind(this));

            // Legacy buttons
            $(document).on('click', '.ai-stats-switch-mode', this.switchMode.bind(this));
            $(document).on('click', '.ai-stats-copy-shortcode', this.copyShortcode.bind(this));
            $(document).on('click', '.ai-stats-delete-content', this.deleteContent.bind(this));
        },
        
        openFetchModal: function(e) {
            e.preventDefault();

            const mode = $(e.currentTarget).data('mode') || $('#ai-stats-mode-select').val() || 'statistics';

            // Create modal HTML
            const modalHtml = `
                <div id="ai-stats-fetch-modal" class="ai-stats-modal">
                    <div class="ai-stats-modal-content">
                        <div class="ai-stats-modal-header">
                            <h2>Fetch & Preview Content</h2>
                            <button type="button" id="ai-stats-close-modal" class="button-link">
                                <span class="dashicons dashicons-no-alt"></span>
                            </button>
                        </div>
                        <div class="ai-stats-modal-body">
                            <div class="ai-stats-fetch-controls">
                                <div class="control-group">
                                    <label>Mode:</label>
                                    <select id="ai-stats-mode-select">
                                        <option value="statistics">Statistical Authority Injector</option>
                                        <option value="birmingham">Birmingham Business Stats</option>
                                        <option value="trends">Industry Trend Micro-Module</option>
                                        <option value="benefits">Service + Benefit Semantic Expander</option>
                                        <option value="seasonal">Seasonal Service Angle Rotator</option>
                                        <option value="process">Service Process Micro-Step Enhancer</option>
                                    </select>
                                </div>
                                <div class="control-group">
                                    <label>Keywords (comma-separated):</label>
                                    <input type="text" id="ai-stats-keywords" placeholder="SEO, web design, Birmingham" />
                                </div>
                                <div class="control-group">
                                    <label>
                                        <input type="checkbox" id="ai-stats-llm-toggle" checked />
                                        Use AI to generate content (uncheck for raw bullets)
                                    </label>
                                </div>
                                <button type="button" id="ai-stats-fetch-preview" class="button button-primary">
                                    <span class="dashicons dashicons-download"></span> Fetch & Preview
                                </button>
                            </div>
                            <div id="ai-stats-candidates-container" style="display:none;">
                                <h3>Select Items to Include</h3>
                                <div id="ai-stats-candidates-list"></div>
                                <div class="ai-stats-modal-actions">
                                    <button type="button" id="ai-stats-generate-draft" class="button button-primary">
                                        <span class="dashicons dashicons-edit"></span> Generate Draft
                                    </button>
                                </div>
                            </div>
                            <div id="ai-stats-draft-container" style="display:none;">
                                <h3>Preview Draft</h3>
                                <div id="ai-stats-draft-preview"></div>
                                <div class="ai-stats-modal-actions">
                                    <button type="button" id="ai-stats-publish-module" class="button button-primary">
                                        <span class="dashicons dashicons-yes"></span> Publish
                                    </button>
                                    <button type="button" class="button" onclick="jQuery('#ai-stats-draft-container').hide(); jQuery('#ai-stats-candidates-container').show();">
                                        <span class="dashicons dashicons-arrow-left-alt"></span> Back to Selection
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            // Remove existing modal if any
            $('#ai-stats-fetch-modal').remove();

            // Append modal to body
            $('body').append(modalHtml);

            // Set mode
            $('#ai-stats-mode-select').val(mode);

            // Show modal
            $('#ai-stats-fetch-modal').fadeIn();
        },

        closeModal: function(e) {
            e.preventDefault();
            $('#ai-stats-fetch-modal').fadeOut(function() {
                $(this).remove();
            });
        },

        fetchCandidates: function(e) {
            e.preventDefault();

            const $button = $(e.currentTarget);
            const mode = $('#ai-stats-mode-select').val();
            const keywords = $('#ai-stats-keywords').val().split(',').map(k => k.trim()).filter(k => k);

            $button.prop('disabled', true);
            const originalText = $button.html();
            $button.html('<span class="dashicons dashicons-update spin"></span> Fetching...');

            $.ajax({
                url: aiStatsAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ai_stats_fetch_candidates',
                    nonce: aiStatsAdmin.nonce,
                    mode: mode,
                    keywords: keywords,
                    limit: 12
                },
                success: function(response) {
                    $button.prop('disabled', false);
                    $button.html(originalText);

                    if (response.success) {
                        AIStatsAdmin.displayCandidates(response.data.candidates);
                    } else {
                        let errorMsg = response.data.message || 'Failed to fetch candidates';
                        if (response.data.debug_url) {
                            errorMsg += ' <a href="' + response.data.debug_url + '" target="_blank">View Debug Info</a>';
                        }
                        if (response.data.sources_count !== undefined) {
                            errorMsg += ' (Checked ' + response.data.sources_count + ' sources)';
                        }
                        AIStatsAdmin.showNotice(errorMsg, 'error');
                    }
                },
                error: function(xhr, status, error) {
                    $button.prop('disabled', false);
                    $button.html(originalText);
                    AIStatsAdmin.showNotice('Error: ' + error, 'error');
                }
            });
        },

        displayCandidates: function(candidates) {
            const $container = $('#ai-stats-candidates-list');
            $container.empty();

            if (!candidates || candidates.length === 0) {
                $container.html('<p>No candidates found. Try different keywords or mode.</p>');
                $('#ai-stats-candidates-container').show();
                return;
            }

            let html = '<table class="wp-list-table widefat fixed striped"><thead><tr>';
            html += '<th style="width:40px;"><input type="checkbox" id="ai-stats-select-all" checked /></th>';
            html += '<th>Title</th><th>Source</th><th>Age</th><th>Score</th></tr></thead><tbody>';

            candidates.forEach(function(candidate, index) {
                const age = AIStatsAdmin.formatAge(candidate.published_at);
                html += '<tr>';
                html += '<td><input type="checkbox" class="ai-stats-toggle-candidate" data-index="' + index + '" checked /></td>';
                html += '<td><strong>' + candidate.title + '</strong><br/><small>' + candidate.blurb_seed.substring(0, 100) + '...</small></td>';
                html += '<td>' + candidate.source + '</td>';
                html += '<td>' + age + '</td>';
                html += '<td>' + Math.round(candidate.score) + '</td>';
                html += '</tr>';
            });

            html += '</tbody></table>';
            $container.html(html);
            $('#ai-stats-candidates-container').show();

            // Store candidates in data
            $('#ai-stats-candidates-container').data('candidates', candidates);

            // Select all toggle
            $('#ai-stats-select-all').on('change', function() {
                $('.ai-stats-toggle-candidate').prop('checked', $(this).is(':checked'));
            });
        },
        
        generateDraft: function(e) {
            e.preventDefault();

            const $button = $(e.currentTarget);
            const mode = $('#ai-stats-mode-select').val();
            const llmEnabled = $('#ai-stats-llm-toggle').is(':checked');
            const allCandidates = $('#ai-stats-candidates-container').data('candidates');

            // Get selected candidates
            const selectedItems = [];
            $('.ai-stats-toggle-candidate:checked').each(function() {
                const index = $(this).data('index');
                if (allCandidates[index]) {
                    selectedItems.push(allCandidates[index]);
                }
            });

            if (selectedItems.length === 0) {
                AIStatsAdmin.showNotice('Please select at least one item', 'warning');
                return;
            }

            $button.prop('disabled', true);
            const originalText = $button.html();
            $button.html('<span class="dashicons dashicons-update spin"></span> Generating...');

            $.ajax({
                url: aiStatsAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ai_stats_generate_draft',
                    nonce: aiStatsAdmin.nonce,
                    mode: mode,
                    selected_items: selectedItems,
                    llm: llmEnabled ? 'on' : 'off',
                    style: 'inline'
                },
                success: function(response) {
                    $button.prop('disabled', false);
                    $button.html(originalText);

                    if (response.success) {
                        AIStatsAdmin.displayDraft(response.data);
                    } else {
                        AIStatsAdmin.showNotice(response.data.message || 'Failed to generate draft', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    $button.prop('disabled', false);
                    $button.html(originalText);
                    AIStatsAdmin.showNotice('Error: ' + error, 'error');
                }
            });
        },

        displayDraft: function(data) {
            const $preview = $('#ai-stats-draft-preview');

            let html = '<div class="ai-stats-draft-box">';
            html += '<div class="draft-content">' + data.html + '</div>';

            if (data.sources_used && data.sources_used.length > 0) {
                html += '<div class="draft-meta">';
                html += '<p><strong>Sources:</strong> ' + data.sources_used.map(s => s.name).join(', ') + '</p>';
                if (data.model) {
                    html += '<p><strong>Model:</strong> ' + data.model + '</p>';
                }
                if (data.tokens) {
                    html += '<p><strong>Tokens:</strong> ' + data.tokens + '</p>';
                }
                html += '</div>';
            }

            html += '</div>';

            $preview.html(html);
            $('#ai-stats-candidates-container').hide();
            $('#ai-stats-draft-container').show();

            // Store draft data
            $('#ai-stats-draft-container').data('draft', data);
        },

        publishModule: function(e) {
            e.preventDefault();

            const $button = $(e.currentTarget);
            const mode = $('#ai-stats-mode-select').val();
            const draftData = $('#ai-stats-draft-container').data('draft');

            if (!draftData || !draftData.html) {
                AIStatsAdmin.showNotice('No draft to publish', 'error');
                return;
            }

            if (!confirm('Publish this module? It will replace the current active content.')) {
                return;
            }

            $button.prop('disabled', true);
            const originalText = $button.html();
            $button.html('<span class="dashicons dashicons-update spin"></span> Publishing...');

            $.ajax({
                url: aiStatsAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ai_stats_publish',
                    nonce: aiStatsAdmin.nonce,
                    mode: mode,
                    html: draftData.html,
                    sources_used: draftData.sources_used,
                    meta: {
                        llm: draftData.llm || 'off',
                        model: draftData.model || '',
                        tokens: draftData.tokens || 0,
                        items: draftData.items || []
                    }
                },
                success: function(response) {
                    $button.prop('disabled', false);
                    $button.html(originalText);

                    if (response.success) {
                        AIStatsAdmin.showNotice('Module published successfully!', 'success');

                        // Close modal and reload
                        setTimeout(function() {
                            $('#ai-stats-fetch-modal').fadeOut(function() {
                                $(this).remove();
                                location.reload();
                            });
                        }, 1000);
                    } else {
                        AIStatsAdmin.showNotice(response.data.message || 'Failed to publish', 'error');
                    }
                },
                error: function(xhr, status, error) {
                    $button.prop('disabled', false);
                    $button.html(originalText);
                    AIStatsAdmin.showNotice('Error: ' + error, 'error');
                }
            });
        },

        toggleCandidate: function(e) {
            // Update select all checkbox state
            const totalCheckboxes = $('.ai-stats-toggle-candidate').length;
            const checkedCheckboxes = $('.ai-stats-toggle-candidate:checked').length;
            $('#ai-stats-select-all').prop('checked', totalCheckboxes === checkedCheckboxes);
        },

        formatAge: function(dateString) {
            const date = new Date(dateString);
            const now = new Date();
            const diffMs = now - date;
            const diffDays = Math.floor(diffMs / 86400000);

            if (diffDays === 0) return 'Today';
            if (diffDays === 1) return 'Yesterday';
            if (diffDays < 7) return diffDays + ' days ago';
            if (diffDays < 30) return Math.floor(diffDays / 7) + ' weeks ago';
            return Math.floor(diffDays / 30) + ' months ago';
        },

        switchMode: function(e) {
            e.preventDefault();
            
            const $button = $(e.currentTarget);
            const mode = $button.data('mode');
            
            if (!mode) {
                return;
            }
            
            $button.prop('disabled', true);
            const originalText = $button.html();
            $button.html(aiStatsAdmin.strings.switching || 'Switching...');
            
            $.ajax({
                url: aiStatsAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ai_stats_switch_mode',
                    nonce: aiStatsAdmin.nonce,
                    mode: mode
                },
                success: function(response) {
                    if (response.success) {
                        AIStatsAdmin.showNotice(response.data.message || aiStatsAdmin.strings.success, 'success');
                        
                        // Reload page to show new mode
                        setTimeout(function() {
                            location.reload();
                        }, 1000);
                    } else {
                        AIStatsAdmin.showNotice(response.data.message || aiStatsAdmin.strings.error, 'error');
                        $button.prop('disabled', false);
                        $button.html(originalText);
                    }
                },
                error: function(xhr, status, error) {
                    AIStatsAdmin.showNotice(aiStatsAdmin.strings.error + ': ' + error, 'error');
                    $button.prop('disabled', false);
                    $button.html(originalText);
                }
            });
        },
        
        copyShortcode: function(e) {
            e.preventDefault();
            
            const $button = $(e.currentTarget);
            const text = $button.data('clipboard-text');
            
            // Create temporary textarea
            const $temp = $('<textarea>');
            $('body').append($temp);
            $temp.val(text).select();
            
            try {
                document.execCommand('copy');
                AIStatsAdmin.showNotice('Shortcode copied to clipboard!', 'success');
                
                // Change button text temporarily
                const originalText = $button.html();
                $button.html('<span class="dashicons dashicons-yes"></span> Copied!');
                setTimeout(function() {
                    $button.html(originalText);
                }, 2000);
            } catch (err) {
                AIStatsAdmin.showNotice('Failed to copy shortcode', 'error');
            }
            
            $temp.remove();
        },
        
        deleteContent: function(e) {
            e.preventDefault();

            const $button = $(e.currentTarget);
            const contentId = $button.data('content-id');

            if (!confirm(aiStatsAdmin.strings.confirmDelete)) {
                return;
            }

            $button.prop('disabled', true);

            $.ajax({
                url: aiStatsAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ai_stats_delete_content',
                    nonce: aiStatsAdmin.nonce,
                    content_id: contentId
                },
                success: function(response) {
                    if (response.success) {
                        AIStatsAdmin.showNotice(response.data.message || aiStatsAdmin.strings.success, 'success');

                        // Remove content row
                        $button.closest('tr').fadeOut(function() {
                            $(this).remove();
                        });
                    } else {
                        AIStatsAdmin.showNotice(response.data.message || aiStatsAdmin.strings.error, 'error');
                        $button.prop('disabled', false);
                    }
                },
                error: function(xhr, status, error) {
                    AIStatsAdmin.showNotice(aiStatsAdmin.strings.error + ': ' + error, 'error');
                    $button.prop('disabled', false);
                }
            });
        },

        testBigQueryConnection: function(e) {
            e.preventDefault();

            const $button = $(e.currentTarget);
            const $result = $('#bigquery-test-result');

            // Get current form values
            const projectId = $('#gcp_project_id').val();
            const serviceAccountJson = $('#gcp_service_account_json').val();
            const region = $('#bigquery_region').val();

            if (!projectId || !serviceAccountJson) {
                $result.html('<span style="color: #d63638;">⚠️ Please enter Project ID and Service Account JSON first</span>');
                return;
            }

            $button.prop('disabled', true);
            const originalText = $button.text();
            $button.text('Testing...');
            $result.html('<span style="color: #999;">⏳ Connecting to BigQuery...</span>');

            $.ajax({
                url: aiStatsAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ai_stats_test_bigquery',
                    nonce: aiStatsAdmin.nonce,
                    project_id: projectId,
                    service_account_json: serviceAccountJson,
                    region: region
                },
                success: function(response) {
                    $button.prop('disabled', false);
                    $button.text(originalText);

                    if (response.success) {
                        const data = response.data;
                        let message = '<span style="color: #00a32a;">✅ Connection successful!</span>';
                        if (data.trends_count) {
                            message += '<br><small>Retrieved ' + data.trends_count + ' trending searches for ' + data.region + '</small>';
                        }
                        if (data.sample_trend) {
                            message += '<br><small>Sample: "' + data.sample_trend + '"</small>';
                        }
                        $result.html(message);
                    } else {
                        let errorMsg = '<span style="color: #d63638;">❌ Connection failed</span>';
                        if (response.data && response.data.message) {
                            errorMsg += '<br><small>' + response.data.message + '</small>';
                        }
                        $result.html(errorMsg);
                    }
                },
                error: function(xhr, status, error) {
                    $button.prop('disabled', false);
                    $button.text(originalText);
                    $result.html('<span style="color: #d63638;">❌ Error: ' + error + '</span>');
                }
            });
        },

        showNotice: function(message, type) {
            type = type || 'info';
            
            const $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
            
            $('.wrap h1').after($notice);
            
            // Auto-dismiss after 5 seconds
            setTimeout(function() {
                $notice.fadeOut(function() {
                    $(this).remove();
                });
            }, 5000);

            // Make dismissible
            $notice.on('click', '.notice-dismiss', function() {
                $notice.fadeOut(function() {
                    $(this).remove();
                });
            });
        },

        updateModelDropdown: function(e) {
            const provider = $(e.currentTarget).val();
            const $modelSelect = $('#preferred_model');
            const $loadingSpan = $('#ai-stats-model-loading');

            if (!provider || !$modelSelect.length) {
                return;
            }

            // Show loading indicator
            $loadingSpan.show();
            $modelSelect.prop('disabled', true);

            $.ajax({
                url: aiStatsAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ai_stats_get_models',
                    nonce: aiStatsAdmin.nonce,
                    provider: provider
                },
                success: function(response) {
                    $loadingSpan.hide();
                    $modelSelect.prop('disabled', false);

                    if (response.success && response.data.models) {
                        const models = response.data.models;
                        $modelSelect.empty();

                        // Add default option
                        $modelSelect.append('<option value="">Auto-select (recommended)</option>');

                        // Add model options
                        models.forEach(function(model) {
                            $modelSelect.append(
                                $('<option></option>').val(model).text(model)
                            );
                        });
                    } else {
                        AIStatsAdmin.showNotice('Failed to load models for ' + provider, 'error');
                    }
                },
                error: function() {
                    $loadingSpan.hide();
                    $modelSelect.prop('disabled', false);
                    AIStatsAdmin.showNotice('Error loading models', 'error');
                }
            });
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        AIStatsAdmin.init();
    });

})(jQuery);

