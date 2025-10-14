/**
 * AI-Stats Debug Page JavaScript
 *
 * @package AI_Stats
 * @version 0.6.9
 */

jQuery(document).ready(function($) {
    'use strict';

    const AIStatsDebug = {
        pipelineData: null, // Store full pipeline data
        generationConfig: null,
        generationOverrides: {},

        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            // Main tab switching
            $('.nav-tab:not(.pipeline-tab)').on('click', this.switchTab);

            // Pipeline stage tab switching
            $(document).on('click', '.pipeline-tab', this.switchPipelineTab);

            // Google Trends fetch
            $('#fetch-google-trends').on('click', this.fetchGoogleTrends.bind(this));

            // Pipeline test
            $('#run-pipeline-test').on('click', this.runPipelineTest.bind(this));

            // Re-run filter
            $(document).on('click', '#rerun-filter', this.rerunFilter.bind(this));

            // Re-run scoring
            $(document).on('click', '#rerun-scoring', this.rerunScoring.bind(this));

            // Test all sources
            $('#test-all-sources').on('click', this.testAllSources);

            // Clear cache
            $('#clear-cache-btn, #clear-all-cache').on('click', this.clearCache.bind(this));

            // Refresh source registry
            $('#refresh-source-registry').on('click', this.refreshRegistry.bind(this));

            // Score slider updates
            $(document).on('input', '.score-slider', function() {
                $(this).next('.score-value').text($(this).val());
            });

            // AI generation controls
            $(document).on('change', '#ai-provider', this.handleProviderChange.bind(this));
            $(document).on('change', '#ai-model', this.handleModelChange.bind(this));
            $(document).on('click', '#test-ai-generation', this.runAIGenerationTest.bind(this));
            $(document).on('click', '#test-ai-with-edited-prompts', this.testWithEditedPrompts.bind(this));
            $(document).on('click', '#reset-prompts', this.resetPrompts.bind(this));
        },

        switchTab: function(e) {
            e.preventDefault();
            const target = $(this).attr('href');

            $('.nav-tab:not(.pipeline-tab)').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');

            $('.tab-content').removeClass('active');
            $(target).addClass('active');
        },

        switchPipelineTab: function(e) {
            e.preventDefault();
            const target = $(this).attr('href');

            $('.pipeline-tab').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');

            $('.pipeline-stage-content').removeClass('active');
            $(target).addClass('active');
        },

        fetchGoogleTrends: function(e) {
            e.preventDefault();

            const $button = $('#fetch-google-trends');
            const region = $('#trends-region').val();
            const $results = $('#trends-results');
            const $error = $('#trends-error');
            const $list = $('#trends-list');
            const $meta = $('#trends-meta');

            $button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Fetching...');
            $results.hide();
            $error.hide();

            $.ajax({
                url: aiStatsAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ai_stats_fetch_google_trends_demo',
                    nonce: aiStatsAdmin.nonce,
                    region: region
                },
                success: function(response) {
                    $button.prop('disabled', false).html('<span class="dashicons dashicons-update"></span> Fetch Live Google Trends');

                    if (response.success && response.data.trends) {
                        const trends = response.data.trends;
                        const regionName = region === 'GB' ? 'United Kingdom' : (region === 'US' ? 'United States' : 'Europe');

                        $meta.html('<strong>' + trends.length + ' trending searches</strong> in ' + regionName + ' (last 30 days)');

                        $list.empty();
                        trends.forEach(function(trend, index) {
                            const $item = $('<div>')
                                .css({
                                    'padding': '12px 15px',
                                    'background': '#fff',
                                    'border': '1px solid #ddd',
                                    'border-radius': '4px',
                                    'box-shadow': '0 1px 3px rgba(0,0,0,0.05)'
                                })
                                .html(
                                    '<div style="display: flex; align-items: center; gap: 10px;">' +
                                    '<span style="font-size: 18px; font-weight: 600; color: #2271b1; min-width: 30px;">#' + (index + 1) + '</span>' +
                                    '<span style="font-size: 14px; font-weight: 500;">' + trend.query + '</span>' +
                                    '</div>' +
                                    (trend.rank ? '<div style="margin-top: 5px; font-size: 12px; color: #666;">Rank: ' + trend.rank + '</div>' : '')
                                );
                            $list.append($item);
                        });

                        $results.slideDown();
                    } else {
                        $('#trends-error-message').text(response.data.message || 'Failed to fetch trends');
                        $error.slideDown();
                    }
                },
                error: function(xhr, status, error) {
                    $button.prop('disabled', false).html('<span class="dashicons dashicons-update"></span> Fetch Live Google Trends');
                    $('#trends-error-message').text('AJAX Error: ' + error);
                    $error.slideDown();
                }
            });
        },

        runPipelineTest: function(e) {
            e.preventDefault();

            const $button = $(this);
            const mode = $('#pipeline-mode').val();

            // Get keywords - support both single keyword and comma-separated
            const keywordInput = $('#pipeline-keywords').val().trim();
            const keywords = keywordInput ? keywordInput.split(',').map(k => k.trim()).filter(k => k) : [];

            $button.prop('disabled', true).html('<span class="dashicons dashicons-update spin" style="animation: rotation 2s infinite linear;"></span> Running Pipeline Test...');
            $('#pipeline-results').hide();

            // Show loading indicator
            const $loadingIndicator = $('<div class="pipeline-loading" style="text-align: center; padding: 40px; background: #f0f6fc; border: 2px dashed #0969da; margin: 20px 0; border-radius: 4px;">' +
                '<div class="dashicons dashicons-update" style="font-size: 48px; width: 48px; height: 48px; animation: rotation 2s infinite linear; color: #0969da;"></div>' +
                '<p style="margin: 15px 0 5px; font-size: 16px; font-weight: 600; color: #0969da;">Running Pipeline Test...</p>' +
                '<p style="margin: 0; color: #666; font-size: 14px;">Fetching data, expanding keywords, filtering, and ranking candidates</p>' +
                '</div>');
            $('#pipeline-results').before($loadingIndicator);

            $.ajax({
                url: aiStatsAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ai_stats_debug_pipeline',
                    nonce: aiStatsAdmin.nonce,
                    mode: mode,
                    keywords: keywords,
                    limit: 20
                },
                success: function(response) {
                    $('.pipeline-loading').remove();
                    $('.notice-error').remove(); // Remove any previous errors
                    $button.prop('disabled', false).text('Run Pipeline Test');

                    if (response.success) {
                        AIStatsDebug.pipelineData = response.data; // Store full data
                        AIStatsDebug.displayPipelineResults(response.data);
                    } else {
                        const errorMsg = response.data && response.data.message ? response.data.message : 'Unknown error';
                        $('#pipeline-results').before('<div class="notice notice-error" style="margin: 20px 0;"><p><strong>Error:</strong> ' + AIStatsDebug.escapeHtml(errorMsg) + '</p></div>');
                    }
                },
                error: function(xhr, status, error) {
                    $('.pipeline-loading').remove();
                    $('.notice-error').remove(); // Remove any previous errors
                    $button.prop('disabled', false).text('Run Pipeline Test');

                    let errorDetails = '<div class="notice notice-error" style="margin: 20px 0;"><p><strong>AJAX Error:</strong> ' + AIStatsDebug.escapeHtml(error) + '</p>';

                    if (xhr.responseText) {
                        errorDetails += '<details style="margin-top: 10px;"><summary style="cursor: pointer; font-weight: bold;">📋 View Full Response (Click to expand)</summary>';
                        errorDetails += '<pre style="background: #f6f7f7; padding: 10px; overflow-x: auto; max-height: 300px; border: 1px solid #ddd; margin-top: 5px;">' + AIStatsDebug.escapeHtml(xhr.responseText) + '</pre>';
                        errorDetails += '</details>';
                    }

                    errorDetails += '</div>';
                    $('#pipeline-results').before(errorDetails);
                }
            });
        },

        rerunFilter: function(e) {
            e.preventDefault();

            if (!this.pipelineData || !this.pipelineData.fetch_results) {
                alert('Please run the pipeline test first');
                return;
            }

            const $button = $(e.currentTarget);
            $button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Re-running...');

            // Get filter parameters
            const filterParams = {
                method: $('#filter-method').val(),
                fields: $('#filter-fields').val(),
                threshold: parseInt($('#filter-threshold').val())
            };

            // Apply filter locally (we'll implement server-side later)
            const keywords = this.pipelineData.keywords;
            let filtered = this.filterCandidatesLocally(this.pipelineData.fetch_results, keywords, filterParams);

            // Update pipeline data
            this.pipelineData.filtered_candidates = filtered;
            this.pipelineData.filtered_count = filtered.length;
            this.pipelineData.filter_removed = this.pipelineData.normalised_count - filtered.length;

            // Re-score
            filtered = this.scoreCandidatesLocally(filtered);
            this.pipelineData.ranked_candidates = filtered;
            this.pipelineData.final_candidates = filtered.slice(0, 20);

            // Update displays
            this.updateFilteredDisplay();
            this.updateRankedDisplay();

            $button.prop('disabled', false).html('<span class="dashicons dashicons-update"></span> Re-run Filter');
        },

        rerunScoring: function(e) {
            e.preventDefault();

            if (!this.pipelineData || !this.pipelineData.filtered_candidates) {
                alert('Please run the pipeline test and filter first');
                return;
            }

            const $button = $(e.currentTarget);
            $button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Re-running...');

            // Get scoring parameters
            const scoringParams = {
                freshnessWeight: parseInt($('#score-freshness').val()),
                authorityWeight: parseInt($('#score-authority').val()),
                confidenceWeight: parseInt($('#score-confidence').val()),
                authSources: $('#score-auth-sources').val().split(',').map(s => s.trim())
            };

            // Re-score candidates
            let scored = this.scoreCandidatesLocally(this.pipelineData.filtered_candidates, scoringParams);

            // Update pipeline data
            this.pipelineData.ranked_candidates = scored;
            this.pipelineData.final_candidates = scored.slice(0, 20);

            // Update displays
            this.updateRankedDisplay();

            $button.prop('disabled', false).html('<span class="dashicons dashicons-update"></span> Re-run Scoring');
        },

        displayPipelineResults: function(pipeline) {
            // Store the complete pipeline data including ALL candidates
            this.pipelineData = pipeline;
            this.generationOverrides = {};
            this.generationConfig = null;

            // Ensure we have all_candidates array (fallback to fetch_results if not present)
            if (!this.pipelineData.all_candidates) {
                this.pipelineData.all_candidates = pipeline.fetch_results || [];
            }

            // Ensure we have filtered_candidates array
            if (!this.pipelineData.filtered_candidates) {
                this.pipelineData.filtered_candidates = this.pipelineData.all_candidates;
            }

            // Display each stage
            this.updateFetchDisplay();
            this.updateNormalisedDisplay();
            this.updateFilteredDisplay();
            this.updateRankedDisplay();
            this.updateAIGenerationDisplay();

            $('#pipeline-results').show();
        },

        updateFetchDisplay: function() {
            const pipeline = this.pipelineData;

            // Statistics cards
            let statsHtml = '<div class="pipeline-stats">';
            statsHtml += '<div class="stat-card"><div class="stat-label">Total Sources</div><div class="stat-value">' + pipeline.sources.length + '</div></div>';

            const successCount = pipeline.sources.filter(s => s.status === 'success').length;
            statsHtml += '<div class="stat-card"><div class="stat-label">Successful</div><div class="stat-value">' + successCount + '</div></div>';

            const errorCount = pipeline.sources.filter(s => s.status === 'error').length;
            statsHtml += '<div class="stat-card"><div class="stat-label">Errors</div><div class="stat-value">' + errorCount + '</div></div>';

            const totalCandidates = pipeline.sources.reduce((sum, s) => sum + s.candidates_count, 0);
            statsHtml += '<div class="stat-card"><div class="stat-label">Total Fetched</div><div class="stat-value">' + totalCandidates + '</div></div>';
            statsHtml += '</div>';

            // Sources table
            let fetchHtml = statsHtml;
            fetchHtml += '<table class="wp-list-table widefat fixed striped"><thead><tr>';
            fetchHtml += '<th>Source</th><th>Type</th><th>Status</th><th>Count</th><th>Time</th><th>Error</th></tr></thead><tbody>';

            pipeline.sources.forEach(function(source) {
                const statusClass = source.status === 'success' ? 'status-ok' : (source.status === 'error' ? 'status-error' : 'status-warning');
                fetchHtml += '<tr>';
                fetchHtml += '<td><strong>' + source.name + '</strong></td>';
                fetchHtml += '<td><span class="badge badge-' + source.type.toLowerCase() + '">' + source.type + '</span></td>';
                fetchHtml += '<td><span class="' + statusClass + '">' + source.status + '</span></td>';
                fetchHtml += '<td>' + source.candidates_count + '</td>';
                fetchHtml += '<td>' + source.fetch_time + 'ms</td>';
                fetchHtml += '<td>' + (source.error || '-') + '</td>';
                fetchHtml += '</tr>';
            });
            fetchHtml += '</tbody></table>';

            if (pipeline.errors.length > 0) {
                fetchHtml += '<div class="notice notice-error" style="margin-top:10px;"><p><strong>Errors:</strong></p><ul>';
                pipeline.errors.forEach(function(error) {
                    fetchHtml += '<li>' + error + '</li>';
                });
                fetchHtml += '</ul></div>';
            }

            $('#fetch-results').html(fetchHtml);
        },

        updateNormalisedDisplay: function() {
            const pipeline = this.pipelineData;

            // Statistics
            let statsHtml = '<div class="pipeline-stats">';
            statsHtml += '<div class="stat-card"><div class="stat-label">Total Candidates</div><div class="stat-value">' + pipeline.normalised_count + '</div></div>';
            statsHtml += '</div>';

            let normHtml = statsHtml;

            // Show performance metrics if available
            if (pipeline.performance) {
                const perf = pipeline.performance;
                normHtml += '<div style="margin: 15px 0; padding: 10px; background: #f0f6fc; border-left: 4px solid #0969da;">';
                normHtml += '<h5 style="margin-top: 0;">⚡ Performance Metrics</h5>';
                if (perf.data_size_estimate_kb) {
                    normHtml += '<p><strong>Data Size:</strong> ~' + perf.data_size_estimate_kb + ' KB</p>';
                }
                if (perf.all_candidates_truncated) {
                    normHtml += '<div style="padding: 8px; background: #fff3cd; border: 1px solid #ffc107; margin-top: 8px;">';
                    normHtml += '<strong>⚠️ Data Optimisation Applied:</strong> Showing first 100 of ' + perf.total_candidates + ' candidates to improve browser performance. ';
                    normHtml += 'Full data is processed server-side but limited here for display.';
                    normHtml += '</div>';
                }
                normHtml += '</div>';
            }

            if (pipeline.normalised_count > 0) {
                // Add JSON viewer for ALL candidates (or truncated if optimised)
                const displayCount = pipeline.all_candidates ? pipeline.all_candidates.length : 0;
                const label = (pipeline.performance && pipeline.performance.all_candidates_truncated)
                    ? 'View First ' + displayCount + ' of ' + pipeline.normalised_count + ' Normalised Candidates (JSON - Optimised)'
                    : 'View All ' + pipeline.normalised_count + ' Normalised Candidates (JSON)';
                normHtml += this.createJsonViewer('normalised', this.pipelineData.all_candidates, label);
            } else {
                normHtml += '<p class="status-warning">⚠ No data fetched from any source</p>';
            }

            $('#normalised-results').html(normHtml);
        },

        updateFilteredDisplay: function() {
            const pipeline = this.pipelineData;

            // Statistics
            let statsHtml = '<div class="pipeline-stats">';
            statsHtml += '<div class="stat-card"><div class="stat-label">After Filtering</div><div class="stat-value">' + pipeline.filtered_count + '</div></div>';
            statsHtml += '<div class="stat-card"><div class="stat-label">Removed</div><div class="stat-value">' + pipeline.filter_removed + '</div><div class="stat-change negative">-' + Math.round((pipeline.filter_removed / pipeline.normalised_count) * 100) + '%</div></div>';
            statsHtml += '</div>';

            let filterHtml = statsHtml;

            // Enhanced filter debugging information
            filterHtml += '<div class="filter-debug-info" style="background: #f9f9f9; padding: 15px; margin: 15px 0; border-left: 4px solid #2271b1;">';
            filterHtml += '<h5 style="margin-top: 0;">Filter Configuration & Baseline</h5>';

            if (pipeline.keywords && pipeline.keywords.length > 0) {
                filterHtml += '<p><strong>Original keyword(s):</strong> <code>' + pipeline.keywords.join(', ') + '</code></p>';

                // Show AI-expanded keywords if available
                if (pipeline.expanded_keywords && pipeline.expanded_keywords.length > 0) {
                    filterHtml += '<div style="margin: 10px 0; padding: 10px; background: #d4edda; border: 1px solid #28a745;">';
                    filterHtml += '<strong>✅ AI-Expanded Keywords:</strong><br>';
                    filterHtml += '<p style="margin: 5px 0;">Searching for <strong>' + pipeline.expanded_keywords.length + '</strong> terms including synonyms and related phrases:</p>';
                    filterHtml += '<div style="max-height: 100px; overflow-y: auto; padding: 5px; background: #fff; border: 1px solid #ddd; margin-top: 5px;">';
                    filterHtml += '<code>' + pipeline.expanded_keywords.join(', ') + '</code>';
                    filterHtml += '</div>';

                    // Show expansion metadata if available
                    if (pipeline.keyword_expansion) {
                        const exp = pipeline.keyword_expansion;
                        filterHtml += '<div style="margin-top: 10px; padding: 8px; background: #f0f8ff; border: 1px solid #0073aa; font-size: 12px;">';
                        filterHtml += '<strong>🔍 Expansion Details:</strong><br>';
                        if (exp.provider && exp.model) {
                            filterHtml += '• <strong>AI Provider:</strong> ' + exp.provider + ' (' + exp.model + ')<br>';
                        }
                        if (exp.synonyms_added !== undefined) {
                            filterHtml += '• <strong>Synonyms Added:</strong> ' + exp.synonyms_added + ' terms<br>';
                        }
                        if (exp.execution_time_ms !== undefined) {
                            filterHtml += '• <strong>Execution Time:</strong> ' + exp.execution_time_ms + 'ms<br>';
                        }
                        if (exp.success === false && exp.error) {
                            filterHtml += '• <strong>Error:</strong> <span style="color: #d63638;">' + exp.error + '</span><br>';
                        }

                        // Add expandable prompt viewer
                        if (exp.prompt) {
                            filterHtml += '<details style="margin-top: 8px;"><summary style="cursor: pointer; font-weight: bold;">View Expansion Prompt</summary>';
                            filterHtml += '<pre style="margin: 5px 0; padding: 8px; background: #fff; border: 1px solid #ddd; overflow-x: auto; font-size: 11px; white-space: pre-wrap;">' + this.escapeHtml(exp.prompt) + '</pre>';
                            filterHtml += '</details>';
                        }
                        filterHtml += '</div>';
                    }

                    filterHtml += '<p style="margin: 5px 0; font-size: 12px; color: #666;"><em>AI automatically found these related terms to improve matching accuracy</em></p>';
                    filterHtml += '</div>';
                } else {
                    filterHtml += '<div style="margin: 10px 0; padding: 10px; background: #fff3cd; border: 1px solid #ffc107;">';
                    filterHtml += '<strong>⚠️ AI Expansion Not Available:</strong> Filtering by exact keyword matches only.<br>';
                    filterHtml += '<em>Configure AI-Core to enable automatic synonym expansion</em>';
                    if (pipeline.keyword_expansion && pipeline.keyword_expansion.error) {
                        filterHtml += '<br><span style="color: #d63638;">Error: ' + pipeline.keyword_expansion.error + '</span>';
                    }
                    filterHtml += '</div>';
                }

                filterHtml += '<p><strong>Filter method:</strong> Contains (case-insensitive)</p>';
                filterHtml += '<p><strong>Search fields:</strong> Title + Content</p>';
                filterHtml += '<p><strong>Match threshold:</strong> At least 1 keyword must match</p>';

                // Show what was filtered out
                filterHtml += '<div style="margin-top: 10px; padding: 10px; background: #fff; border: 1px solid #ddd;">';
                filterHtml += '<strong>Filtering Logic:</strong><br>';
                filterHtml += '• Started with <strong>' + pipeline.normalised_count + '</strong> total candidates<br>';
                filterHtml += '• Searched for keywords in both title and content fields<br>';
                if (pipeline.expanded_keywords && pipeline.expanded_keywords.length > 0) {
                    filterHtml += '• Used <strong>' + pipeline.expanded_keywords.length + '</strong> search terms (original + AI synonyms)<br>';
                }
                filterHtml += '• Kept candidates that contain at least one keyword<br>';
                filterHtml += '• Removed <strong>' + pipeline.filter_removed + '</strong> candidates that did not match<br>';
                filterHtml += '• Result: <strong>' + pipeline.filtered_count + '</strong> candidates remaining';
                filterHtml += '</div>';
            } else {
                filterHtml += '<p><em>No keyword filtering applied - all candidates passed through</em></p>';
                filterHtml += '<p><strong>Baseline:</strong> ' + pipeline.normalised_count + ' candidates from all sources</p>';
            }

            filterHtml += '</div>';

            if (pipeline.filtered_count > 0) {
                filterHtml += this.createJsonViewer('filtered', this.pipelineData.filtered_candidates, 'View All ' + pipeline.filtered_count + ' Filtered Candidates (JSON)');
            } else if (pipeline.keywords && pipeline.keywords.length > 0) {
                filterHtml += '<div class="notice notice-warning inline"><p><strong>No candidates matched your keywords.</strong> Try:</p>';
                filterHtml += '<ul><li>Using broader keywords</li><li>Checking spelling</li><li>Using fewer keywords</li></ul></div>';
            }

            $('#filtered-results').html(filterHtml);
        },

        updateRankedDisplay: function() {
            const pipeline = this.pipelineData;

            // Statistics
            let statsHtml = '<div class="pipeline-stats">';
            statsHtml += '<div class="stat-card"><div class="stat-label">Ranked Candidates</div><div class="stat-value">' + pipeline.ranked_candidates.length + '</div></div>';

            if (pipeline.ranked_candidates.length > 0) {
                const avgScore = pipeline.ranked_candidates.reduce((sum, c) => sum + c.score, 0) / pipeline.ranked_candidates.length;
                statsHtml += '<div class="stat-card"><div class="stat-label">Average Score</div><div class="stat-value">' + Math.round(avgScore) + '</div></div>';
            }
            statsHtml += '</div>';

            let rankHtml = statsHtml;

            if (pipeline.ranked_candidates.length > 0) {
                rankHtml += '<table class="wp-list-table widefat fixed striped"><thead><tr>';
                rankHtml += '<th style="width:50px;">Rank</th><th>Title</th><th>Source</th><th style="width:150px;">Score Breakdown</th><th style="width:120px;">Published</th></tr></thead><tbody>';

                const keywords = pipeline.expanded_keywords || pipeline.keywords || [];

                pipeline.ranked_candidates.slice(0, 20).forEach(function(candidate, index) {
                    // Calculate keyword density for display
                    const text = ((candidate.title || '') + ' ' + (candidate.blurb_seed || '') + ' ' + (candidate.full_content || '')).toLowerCase();
                    let keywordMatches = 0;
                    let keywordOccurrences = 0;

                    keywords.forEach(function(keyword) {
                        const keywordLower = keyword.toLowerCase();
                        const count = (text.match(new RegExp(keywordLower, 'g')) || []).length;
                        if (count > 0) {
                            keywordMatches++;
                            keywordOccurrences += count;
                        }
                    });

                    const keywordDensityScore = Math.min(50, Math.round((keywordMatches / Math.max(1, keywords.length)) * 25 + keywordOccurrences * 2));

                    rankHtml += '<tr>';
                    rankHtml += '<td>' + (index + 1) + '</td>';
                    rankHtml += '<td><strong>' + candidate.title + '</strong><br><small>' + candidate.blurb_seed.substring(0, 100) + '...</small></td>';
                    rankHtml += '<td>' + candidate.source + '</td>';
                    rankHtml += '<td>';
                    rankHtml += '<strong>Total: ' + Math.round(candidate.score) + '</strong><br>';
                    rankHtml += '<small style="color: #2271b1;">🔑 Keywords: ' + keywordDensityScore + '/50</small><br>';
                    rankHtml += '<small style="color: #666;">(' + keywordMatches + '/' + keywords.length + ' terms, ' + keywordOccurrences + ' hits)</small>';
                    rankHtml += '</td>';
                    rankHtml += '<td>' + candidate.published_at + '</td>';
                    rankHtml += '</tr>';
                });
                rankHtml += '</tbody></table>';

                rankHtml += this.createJsonViewer('ranked', pipeline.ranked_candidates, 'View All ' + pipeline.ranked_candidates.length + ' Ranked Candidates (JSON)');
            } else {
                rankHtml += '<p class="status-error">✗ No candidates to rank</p>';
            }

            $('#ranked-results').html(rankHtml);
        },



        updateAIGenerationDisplay: function(overrides) {
            const overridesObj = overrides || {};
            if (overridesObj && Object.keys(overridesObj).length) {
                this.generationOverrides = Object.assign({}, this.generationOverrides, overridesObj);
            }

            const baseGeneration = (this.pipelineData && this.pipelineData.generation) ? this.pipelineData.generation : {};
            const mergedConfig = Object.assign({}, baseGeneration, this.generationOverrides);

            this.generationConfig = this.normalizeGenerationConfig(mergedConfig);

            const html = this.renderGenerationUI(this.generationConfig);
            $('#ai-generation-test')
                .html(html)
                .data('generation-config', this.generationConfig);
        },

        normalizeGenerationConfig: function(config) {
            const debugData = window.aiStatsDebugData || {};
            const providersData = debugData.providers || {};
            const providerLabels = providersData.labels || {};
            const providerModels = providersData.models || {};
            const providerOptions = providersData.options || {};
            const providerMeta = providersData.meta || {};
            const settings = debugData.aiStatsSettings || {};

            const fallbackProvider = providersData.default || 'openai';
            const provider = config.provider || settings.preferred_provider || fallbackProvider;

            const combinedModels = this.uniqueArray(
                [].concat(config.available_models || [], providerModels[provider] || [])
            );

            let model = config.model;
            if ((!model || combinedModels.indexOf(model) === -1) && settings.preferred_provider === provider && settings.preferred_model) {
                if (combinedModels.indexOf(settings.preferred_model) === -1) {
                    combinedModels.push(settings.preferred_model);
                }
                model = settings.preferred_model;
            }

            if ((!model || combinedModels.indexOf(model) === -1) && combinedModels.length > 0) {
                model = combinedModels[0];
            }

            if (model && combinedModels.indexOf(model) === -1) {
                combinedModels.push(model);
            }

            let schema = {};
            if (config.parameter_schema && config.model === model) {
                schema = config.parameter_schema;
            } else if (providerMeta[provider] && providerMeta[provider][model] && providerMeta[provider][model].parameters) {
                schema = providerMeta[provider][model].parameters;
            }

            const schemaDefaults = {};
            Object.keys(schema).forEach(function(key) {
                const meta = schema[key];
                if (meta && meta.default !== undefined) {
                    schemaDefaults[key] = meta.default;
                }
            });

            const baseOptions = providerOptions[provider] || {};
            const mergedOptions = Object.assign({}, schemaDefaults, baseOptions, config.options || {});

            return {
                provider: provider,
                model: model,
                available_models: combinedModels,
                schema: schema,
                options: mergedOptions,
                system_prompt: config.system_prompt || '',
                user_prompt: config.user_prompt || '',
                mode_data: config.mode_data || {},
                messages: config.messages || [],
                provider_labels: providerLabels,
                configured_providers: providersData.configured || Object.keys(providerLabels),
            };
        },

        renderGenerationUI: function(config) {
            const providerLabels = config.provider_labels || {};
            const configuredProviders = config.configured_providers || Object.keys(providerLabels);
            const debugData = window.aiStatsDebugData || {};
            const providersData = debugData.providers || {};

            let providerOptionsHtml = '';
            Object.keys(providerLabels).forEach((key) => {
                const isConfigured = configuredProviders.indexOf(key) !== -1;
                const selected = key === config.provider ? ' selected' : '';
                const disabled = isConfigured ? '' : ' disabled';
                providerOptionsHtml += '<option value="' + this.escapeAttribute(key) + '"' + selected + disabled + '>' + this.escapeHtml(providerLabels[key]) + '</option>';
            });

            if (!providerLabels[config.provider]) {
                providerOptionsHtml += '<option value="' + this.escapeAttribute(config.provider) + '" selected>' + this.escapeHtml(config.provider) + '</option>';
            }

            let modelOptionsHtml = '';
            if (config.available_models.length > 0) {
                config.available_models.forEach((modelId) => {
                    const selected = modelId === config.model ? ' selected' : '';
                    modelOptionsHtml += '<option value="' + this.escapeAttribute(modelId) + '"' + selected + '>' + this.escapeHtml(this.getModelLabel(config.provider, modelId)) + '</option>';
                });
            } else {
                modelOptionsHtml = '<option value="">No models available</option>';
            }

            const optionsHtml = this.buildOptionFields(config.schema, config.options);

            let aiHtml = '<div class="pipeline-controls">';
            aiHtml += '<h5>AI Generation Settings</h5>';
            aiHtml += '<table class="form-table">';
            aiHtml += '<tr><th><label for="ai-provider">Provider</label></th><td><select id="ai-provider" class="regular-text">' + providerOptionsHtml + '</select>';
            if (configuredProviders.indexOf(config.provider) === -1) {
                aiHtml += '<p class="description">Provider not configured in AI-Core.</p>';
            }
            aiHtml += '</td></tr>';
            aiHtml += '<tr><th><label for="ai-model">Model</label></th><td><select id="ai-model" class="regular-text">' + modelOptionsHtml + '</select></td></tr>';
            aiHtml += '</table>';
            aiHtml += '<div id="ai-parameter-fields">' + optionsHtml + '</div>';
            aiHtml += '<button type="button" id="test-ai-generation" class="button button-primary"><span class="dashicons dashicons-admin-generic"></span> Test AI Generation</button>';
            aiHtml += '</div>';

            // Show ACTUAL prompts used in production with variables populated
            const pipeline = this.pipelineData || {};
            const keywords = pipeline.keywords || [];
            const mode = pipeline.mode || 'industry_trends';
            const firstCandidate = (pipeline.ranked_candidates && pipeline.ranked_candidates.length > 0) ? pipeline.ranked_candidates[0] : null;

            // Build actual system prompt used in production
            const actualSystemPrompt = "You are a statistics extraction specialist. You will receive text that already contains numbers. Your job is to:\n1. Identify which numbers are actual STATISTICS (not dates, page numbers, or irrelevant figures)\n2. Format each statistic as: [NUMBER/PERCENTAGE] - [BRIEF CONTEXT]\n3. Return ONLY 2-3 most relevant statistics\n4. If none are actual statistics, return 'No quantifiable statistics found'";

            // Build actual user prompt with real data
            let actualUserPrompt = '';
            if (firstCandidate) {
                const source = firstCandidate.source || 'Unknown Source';
                const content = (firstCandidate.full_content || firstCandidate.blurb_seed || '').substring(0, 1000);

                actualUserPrompt = "Source: " + source + "\n";
                actualUserPrompt += "Keywords: " + keywords.join(', ') + "\n\n";
                actualUserPrompt += "Pre-filtered content (already contains numbers):\n" + content + "\n\n";
                actualUserPrompt += "Extract 2-3 STATISTICS related to: " + keywords.join(', ') + "\n";
                actualUserPrompt += "Format each as: [NUMBER] - [CONTEXT]\n";
                actualUserPrompt += "Example: 67% - of UK SMEs increased digital marketing budgets in 2024\n";
                actualUserPrompt += "CRITICAL: Only include actual statistics with business/industry relevance. Ignore dates, page numbers, article IDs.";
            } else {
                actualUserPrompt = "No candidates available to test. Run 'Fetch & Preview' first to generate candidates.";
            }

            aiHtml += '<div class="ai-prompt-preview" style="background: #f9f9f9; padding: 15px; margin: 15px 0; border-left: 4px solid #2271b1;">';
            aiHtml += '<h5 style="margin-top: 0;">🔍 ACTUAL Production Prompts (Used in AI API Requests)</h5>';
            aiHtml += '<p class="description">These are the exact prompts sent to the AI model when processing candidates. Variables are populated with real data from the first ranked candidate.</p>';

            aiHtml += '<div style="margin: 15px 0;">';
            aiHtml += '<h6 style="margin-bottom: 5px;">System Prompt:</h6>';
            aiHtml += '<textarea id="ai-system-prompt-edit" class="large-text code" rows="6" style="width: 100%; font-family: monospace; font-size: 12px;">' + this.escapeHtml(actualSystemPrompt) + '</textarea>';
            aiHtml += '</div>';

            aiHtml += '<div style="margin: 15px 0;">';
            aiHtml += '<h6 style="margin-bottom: 5px;">User Prompt (with variables populated):</h6>';
            aiHtml += '<textarea id="ai-user-prompt-edit" class="large-text code" rows="12" style="width: 100%; font-family: monospace; font-size: 12px;">' + this.escapeHtml(actualUserPrompt) + '</textarea>';
            aiHtml += '</div>';

            aiHtml += '<p class="description"><strong>Variables used:</strong></p>';
            aiHtml += '<ul style="margin: 5px 0 15px 20px; font-size: 12px;">';
            aiHtml += '<li><code>{source}</code> = ' + (firstCandidate ? firstCandidate.source : 'N/A') + '</li>';
            aiHtml += '<li><code>{keywords}</code> = ' + keywords.join(', ') + '</li>';
            aiHtml += '<li><code>{content}</code> = First 1000 chars of candidate content (shown above)</li>';
            aiHtml += '<li><code>{mode}</code> = ' + mode + '</li>';
            aiHtml += '</ul>';

            aiHtml += '<button type="button" id="test-ai-with-edited-prompts" class="button button-secondary" style="margin-right: 10px;"><span class="dashicons dashicons-admin-generic"></span> Test with Edited Prompts</button>';
            aiHtml += '<button type="button" id="reset-prompts" class="button button-secondary"><span class="dashicons dashicons-update"></span> Reset to Default</button>';
            aiHtml += '</div>';

            aiHtml += '<div id="ai-output-preview" class="ai-output-preview" style="margin-top:20px;"></div>';

            if (config.mode_data && Object.keys(config.mode_data).length) {
                aiHtml += this.createJsonViewer('generation-mode-data', config.mode_data, 'View Mode Data (JSON)');
            }

            return aiHtml;
        },

        getModelLabel: function(provider, modelId) {
            const debugData = window.aiStatsDebugData || {};
            const meta = debugData.providers && debugData.providers.meta;
            if (meta && meta[provider] && meta[provider][modelId] && meta[provider][modelId].display_name) {
                const display = meta[provider][modelId].display_name;
                return display ? display + ' (' + modelId + ')' : modelId;
            }
            return modelId;
        },

        buildOptionFields: function(schema, options) {
            const schemaKeys = schema ? Object.keys(schema) : [];
            const optionKeys = options ? Object.keys(options) : [];

            if (!schemaKeys.length && !optionKeys.length) {
                return '<p class="description">No adjustable parameters for this model.</p>';
            }

            let html = '<table class="form-table"><tbody>';
            if (schemaKeys.length) {
                schemaKeys.forEach((key) => {
                    const meta = schema[key] || {};
                    const label = meta.label || key;
                    const help = meta.help || '';
                    const value = (options && options.hasOwnProperty(key)) ? options[key] : (meta.default !== undefined ? meta.default : '');

                    if (meta.type === 'select' && Array.isArray(meta.options)) {
                        html += '<tr><th><label for="ai-option-' + this.escapeAttribute(key) + '">' + this.escapeHtml(label) + '</label></th><td>';
                        html += '<select id="ai-option-' + this.escapeAttribute(key) + '" class="ai-option-input" data-param="' + this.escapeAttribute(key) + '">';
                        meta.options.forEach((opt) => {
                            const optValue = opt.value !== undefined ? opt.value : opt;
                            const optLabel = opt.label || optValue;
                            const selected = optValue == value ? ' selected' : '';
                            html += '<option value="' + this.escapeAttribute(optValue) + '"' + selected + '>' + this.escapeHtml(optLabel) + '</option>';
                        });
                        html += '</select>';
                    } else {
                        const inputType = meta.type === 'number' ? 'number' : 'text';
                        const attrs = [];
                        if (meta.min !== undefined) {
                            attrs.push('min="' + this.escapeAttribute(meta.min) + '"');
                        }
                        if (meta.max !== undefined) {
                            attrs.push('max="' + this.escapeAttribute(meta.max) + '"');
                        }
                        if (meta.step !== undefined) {
                            attrs.push('step="' + this.escapeAttribute(meta.step) + '"');
                        }
                        html += '<tr><th><label for="ai-option-' + this.escapeAttribute(key) + '">' + this.escapeHtml(label) + '</label></th><td>';
                        html += '<input type="' + inputType + '" id="ai-option-' + this.escapeAttribute(key) + '" class="ai-option-input regular-text" data-param="' + this.escapeAttribute(key) + '" value="' + this.escapeAttribute(value) + '" ' + attrs.join(' ') + ' />';
                    }
                    if (help) {
                        html += '<p class="description">' + this.escapeHtml(help) + '</p>';
                    }
                    html += '</td></tr>';
                });
            } else {
                optionKeys.forEach((key) => {
                    html += '<tr><th>' + this.escapeHtml(key) + '</th><td>';
                    html += '<input type="text" class="ai-option-input regular-text" data-param="' + this.escapeAttribute(key) + '" value="' + this.escapeAttribute(options[key]) + '" />';
                    html += '</td></tr>';
                });
            }
            html += '</tbody></table>';

            return html;
        },

        handleProviderChange: function(e) {
            const provider = jQuery(e.currentTarget).val();
            this.generationOverrides.provider = provider;
            delete this.generationOverrides.model;
            delete this.generationOverrides.options;
            this.updateAIGenerationDisplay({ provider: provider });
        },

        handleModelChange: function(e) {
            const model = jQuery(e.currentTarget).val();
            const provider = jQuery('#ai-provider').val();
            this.generationOverrides.provider = provider;
            this.generationOverrides.model = model;
            delete this.generationOverrides.options;
            this.updateAIGenerationDisplay({ provider: provider, model: model });
        },

        testWithEditedPrompts: function(e) {
            e.preventDefault();

            const systemPrompt = jQuery('#ai-system-prompt-edit').val();
            const userPrompt = jQuery('#ai-user-prompt-edit').val();
            const provider = jQuery('#ai-provider').val();
            const model = jQuery('#ai-model').val();

            if (!model) {
                jQuery('#ai-output-preview').html('<div class="notice notice-error"><p>Select a model to run the test.</p></div>');
                return;
            }

            if (!systemPrompt || !userPrompt) {
                jQuery('#ai-output-preview').html('<div class="notice notice-error"><p>Both system and user prompts are required.</p></div>');
                return;
            }

            const $button = jQuery('#test-ai-with-edited-prompts');
            const originalHtml = $button.html();
            $button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Testing...');

            const options = {};
            jQuery('.ai-option-input').each(function() {
                const $input = jQuery(this);
                const key = $input.data('param');
                if (key) {
                    let val = $input.val();
                    if ($input.attr('type') === 'number') {
                        val = parseFloat(val);
                    }
                    options[key] = val;
                }
            });

            jQuery.ajax({
                url: aiStatsAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ai_stats_test_prompt',
                    nonce: aiStatsAdmin.nonce,
                    provider: provider,
                    model: model,
                    system_prompt: systemPrompt,
                    user_prompt: userPrompt,
                    options: options
                },
                success: function(response) {
                    $button.prop('disabled', false).html(originalHtml);

                    if (response.success) {
                        let output = '<div class="notice notice-success inline"><p><strong>✅ AI Response:</strong></p></div>';
                        output += '<div style="background: #fff; padding: 15px; border: 1px solid #ddd; margin: 10px 0; white-space: pre-wrap; font-family: monospace; font-size: 13px;">';
                        output += AIStatsDebug.escapeHtml(response.data.content || 'No content returned');
                        output += '</div>';

                        if (response.data.usage) {
                            output += '<div style="margin: 10px 0; padding: 10px; background: #f0f6fc; border-left: 4px solid #0969da; font-size: 12px;">';
                            output += '<strong>📊 Token Usage:</strong><br>';
                            output += '• Prompt tokens: ' + (response.data.usage.prompt_tokens || 0) + '<br>';
                            output += '• Completion tokens: ' + (response.data.usage.completion_tokens || 0) + '<br>';
                            output += '• Total tokens: ' + (response.data.usage.total_tokens || 0);
                            output += '</div>';
                        }

                        jQuery('#ai-output-preview').html(output);
                    } else {
                        jQuery('#ai-output-preview').html('<div class="notice notice-error"><p>' + AIStatsDebug.escapeHtml(response.data.message || 'Test failed.') + '</p></div>');
                    }
                },
                error: function(xhr, status, error) {
                    $button.prop('disabled', false).html(originalHtml);
                    jQuery('#ai-output-preview').html('<div class="notice notice-error"><p>AJAX error: ' + AIStatsDebug.escapeHtml(error) + '</p></div>');
                }
            });
        },

        resetPrompts: function(e) {
            e.preventDefault();
            this.updateAIGenerationDisplay(); // Regenerate with default prompts
        },

        runAIGenerationTest: function(e) {
            e.preventDefault();

            if (!this.generationConfig) {
                this.updateAIGenerationDisplay();
            }

            const config = this.generationConfig || {};
            const provider = jQuery('#ai-provider').val() || config.provider;
            const model = jQuery('#ai-model').val() || config.model;

            if (!model) {
                jQuery('#ai-output-preview').html('<div class="notice notice-error"><p>' + this.escapeHtml('Select a model to run the test.') + '</p></div>');
                return;
            }

            const options = {};
            jQuery('.ai-option-input').each(function() {
                const $input = jQuery(this);
                const key = $input.data('param');

                if (!key) {
                    return;
                }

                let value = $input.val();
                if ($input.attr('type') === 'number' && value !== '') {
                    const numeric = Number(value);
                    if (!Number.isNaN(numeric)) {
                        value = numeric;
                    }
                }

                options[key] = value;
            });

            const $button = jQuery(e.currentTarget);
            const originalHtml = $button.html();
            $button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Testing...');
            jQuery('#ai-output-preview').html('');

            const mode = (this.pipelineData && this.pipelineData.mode) ? this.pipelineData.mode : jQuery('#pipeline-mode').val();

            jQuery.ajax({
                url: aiStatsAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ai_stats_debug_generation',
                    nonce: aiStatsAdmin.nonce,
                    provider: provider,
                    model: model,
                    mode: mode,
                    options: options,
                    system_prompt: config.system_prompt || '',
                    user_prompt: config.user_prompt || ''
                },
                success: function(response) {
                    $button.prop('disabled', false).html(originalHtml);

                    if (response.success) {
                        const result = response.data;
                        let outputHtml = '<div class="notice notice-success"><p><strong>Generation completed successfully.</strong></p></div>';
                        outputHtml += '<pre class="ai-output-block">' + AIStatsDebug.escapeHtml(result.content || '') + '</pre>';
                        if (result.tokens) {
                            outputHtml += '<p><strong>Tokens Used:</strong> ' + result.tokens + '</p>';
                        }
                        jQuery('#ai-output-preview').html(outputHtml);
                    } else {
                        jQuery('#ai-output-preview').html('<div class="notice notice-error"><p>' + AIStatsDebug.escapeHtml(response.data.message || 'Generation failed.') + '</p></div>');
                    }
                },
                error: function(xhr, status, error) {
                    $button.prop('disabled', false).html(originalHtml);
                    jQuery('#ai-output-preview').html('<div class="notice notice-error"><p>AJAX error: ' + AIStatsDebug.escapeHtml(error) + '</p></div>');
                }
            });
        },

        uniqueArray: function(items) {
            const result = [];
            const seen = new Set();
            (items || []).forEach(function(item) {
                if (item !== undefined && item !== null && !seen.has(item)) {
                    seen.add(item);
                    result.push(item);
                }
            });
            return result;
        },

        escapeHtml: function(str) {
            if (str === null || str === undefined) {
                return '';
            }
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#39;');
        },

        escapeAttribute: function(str) {
            return this.escapeHtml(str);
        },

        createJsonViewer: function(id, data, label) {
            const viewerId = 'json-viewer-' + id;
            let html = '<div class="json-viewer-container">';
            html += '<button type="button" class="json-viewer-toggle" data-target="' + viewerId + '">';
            html += '<span class="dashicons dashicons-visibility"></span> ' + label;
            html += '</button>';
            html += '<div id="' + viewerId + '" class="json-viewer-content">';
            html += '<div class="json-viewer-search">';
            html += '<input type="text" placeholder="Search JSON..." class="json-search-input" data-target="' + viewerId + '-content">';
            html += '</div>';
            html += '<pre id="' + viewerId + '-content">' + this.syntaxHighlight(JSON.stringify(data, null, 2)) + '</pre>';
            html += '</div>';
            html += '</div>';

            // Bind toggle event
            setTimeout(function() {
                $('.json-viewer-toggle[data-target="' + viewerId + '"]').off('click').on('click', function() {
                    $('#' + viewerId).toggleClass('expanded');
                    const $icon = $(this).find('.dashicons');
                    if ($('#' + viewerId).hasClass('expanded')) {
                        $icon.removeClass('dashicons-visibility').addClass('dashicons-hidden');
                    } else {
                        $icon.removeClass('dashicons-hidden').addClass('dashicons-visibility');
                    }
                });

                $('.json-search-input[data-target="' + viewerId + '-content"]').off('input').on('input', function() {
                    const searchTerm = $(this).val().toLowerCase();
                    const $content = $('#' + viewerId + '-content');
                    const $searchInput = $(this);
                    const originalText = JSON.stringify(data, null, 2);

                    if (searchTerm) {
                        const result = AIStatsDebug.highlightSearchTerm(originalText, searchTerm);
                        $content.html(AIStatsDebug.syntaxHighlight(result.text));

                        // Show match count and stats
                        let statsHtml = '<div style="background: #fff3cd; padding: 8px 12px; margin-bottom: 10px; border-radius: 4px; border-left: 4px solid #ffc107;">';
                        statsHtml += '<strong>🔍 Search Results:</strong> ';
                        statsHtml += '<span style="color: #856404;">' + result.matchCount + ' matches found for "' + AIStatsDebug.escapeHtml(searchTerm) + '"</span>';

                        // Add context about what's being searched
                        const dataSize = JSON.stringify(data).length;
                        const itemCount = Array.isArray(data) ? data.length : Object.keys(data).length;
                        statsHtml += ' <span style="margin-left: 15px; color: #666;">|</span> ';
                        statsHtml += '<span style="color: #666;">Searching ' + itemCount + ' items (' + Math.round(dataSize / 1024) + ' KB)</span>';
                        statsHtml += '</div>';

                        // Insert stats before content
                        if ($content.prev('.search-stats').length) {
                            $content.prev('.search-stats').html(statsHtml);
                        } else {
                            $content.before('<div class="search-stats">' + statsHtml + '</div>');
                        }
                    } else {
                        $content.html(AIStatsDebug.syntaxHighlight(originalText));
                        $content.prev('.search-stats').remove();
                    }
                });
            }, 100);

            return html;
        },

        syntaxHighlight: function(json) {
            json = json.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
            return json.replace(/("(\\u[a-zA-Z0-9]{4}|\\[^u]|[^\\"])*"(\s*:)?|\b(true|false|null)\b|-?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?)/g, function (match) {
                let cls = 'number';
                if (/^"/.test(match)) {
                    if (/:$/.test(match)) {
                        cls = 'key';
                        return '<span style="color: #9cdcfe;">' + match + '</span>';
                    } else {
                        cls = 'string';
                        return '<span style="color: #ce9178;">' + match + '</span>';
                    }
                } else if (/true|false/.test(match)) {
                    cls = 'boolean';
                    return '<span style="color: #569cd6;">' + match + '</span>';
                } else if (/null/.test(match)) {
                    cls = 'null';
                    return '<span style="color: #569cd6;">' + match + '</span>';
                }
                return '<span style="color: #b5cea8;">' + match + '</span>';
            });
        },

        highlightSearchTerm: function(text, term) {
            const regex = new RegExp('(' + term.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
            const matches = (text.match(regex) || []).length;
            return {
                text: text.replace(regex, '⚡Search⚡ $1'),
                matchCount: matches
            };
        },

        filterCandidatesLocally: function(candidates, keywords, params) {
            if (!keywords || keywords.length === 0) {
                return candidates;
            }

            return candidates.filter(function(candidate) {
                let text = '';
                if (params.fields === 'title') {
                    text = candidate.title || '';
                } else if (params.fields === 'content') {
                    text = candidate.blurb_seed || '';
                } else {
                    text = (candidate.title || '') + ' ' + (candidate.blurb_seed || '');
                }

                if (params.method === 'contains-case') {
                    // Case-sensitive
                    let matches = 0;
                    keywords.forEach(function(keyword) {
                        if (text.indexOf(keyword) !== -1) matches++;
                    });
                    return matches >= params.threshold;
                } else {
                    // Case-insensitive (default)
                    text = text.toLowerCase();
                    let matches = 0;
                    keywords.forEach(function(keyword) {
                        if (text.indexOf(keyword.toLowerCase()) !== -1) matches++;
                    });
                    return matches >= params.threshold;
                }
            });
        },

        scoreCandidatesLocally: function(candidates, params) {
            params = params || {
                freshnessWeight: 30,
                authorityWeight: 20,
                confidenceWeight: 10,
                authSources: ['ONS', 'GOV.UK', 'Google', 'Eurostat', 'Companies House']
            };

            const keywords = this.pipelineData.expanded_keywords || this.pipelineData.keywords || [];

            candidates.forEach(function(candidate) {
                let score = 0;

                // Keyword density score (0-50 points) - highest priority
                if (keywords.length > 0) {
                    const text = ((candidate.title || '') + ' ' + (candidate.blurb_seed || '') + ' ' + (candidate.full_content || '')).toLowerCase();
                    let matches = 0;
                    let totalOccurrences = 0;

                    keywords.forEach(function(keyword) {
                        const keywordLower = keyword.toLowerCase();
                        const count = (text.match(new RegExp(keywordLower, 'g')) || []).length;
                        if (count > 0) {
                            matches++;
                            totalOccurrences += count;
                        }
                    });

                    // Score based on keyword variety (0-25) and frequency (0-25)
                    const varietyScore = Math.min(25, (matches / Math.max(1, keywords.length)) * 25);
                    const frequencyScore = Math.min(25, totalOccurrences * 2);
                    score += Math.round(varietyScore + frequencyScore);
                }

                // Freshness score (0-30 points)
                const ageDays = (Date.now() / 1000 - new Date(candidate.published_at).getTime() / 1000) / 86400;
                if (ageDays < 1) {
                    score += params.freshnessWeight;
                } else if (ageDays < 7) {
                    score += params.freshnessWeight * 0.67;
                } else if (ageDays < 30) {
                    score += params.freshnessWeight * 0.33;
                }

                // Authority score (0-20 points)
                params.authSources.forEach(function(authSource) {
                    if (candidate.source.toLowerCase().indexOf(authSource.toLowerCase()) !== -1) {
                        score += params.authorityWeight;
                        return false; // break
                    }
                });

                // Confidence score (0-10 points)
                score += (candidate.confidence || 0.5) * params.confidenceWeight;

                candidate.score = Math.round(score);
            });

            // Sort by score descending
            candidates.sort(function(a, b) {
                return b.score - a.score;
            });

            return candidates;
        },

        testAllSources: function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const $rows = $('tr[data-mode][data-source]');
            let tested = 0;
            const total = $rows.length;
            const BATCH_SIZE = 10; // Test 10 sources in parallel
            
            $button.prop('disabled', true).text('Testing...');
            $('#test-progress').text('0 / ' + total);
            
            // Reset all statuses
            $rows.find('.source-status').html('<span class="status-badge status-pending">⏳ Pending</span>');
            $rows.find('.source-count').text('-');
            $rows.find('.source-time').text('-');
            
            // Process sources in parallel batches
            const testBatch = function(startIndex) {
                if (startIndex >= $rows.length) {
                    $button.prop('disabled', false).text('Test All Sources');
                    $('#test-progress').text('Complete: ' + tested + ' / ' + total);
                    return;
                }
                
                const endIndex = Math.min(startIndex + BATCH_SIZE, $rows.length);
                const batchPromises = [];
                
                // Start all tests in this batch simultaneously
                for (let i = startIndex; i < endIndex; i++) {
                    const $row = $rows.eq(i);
                    const mode = $row.data('mode');
                    const sourceIndex = $row.data('source');
                    
                    $row.find('.source-status').html('<span class="status-badge status-testing">🔄 Testing...</span>');
                    
                    // Create a promise for this test
                    const promise = new Promise(function(resolve) {
                        AIStatsDebug.testSingleSource(mode, sourceIndex, function(result) {
                            tested++;
                            $('#test-progress').text(tested + ' / ' + total);
                            
                            if (result.success) {
                                const data = result.data;
                                let statusHtml = '';
                                
                                if (data.status === 'success') {
                                    statusHtml = '<span class="status-badge status-ok">✓ Success</span>';
                                    $row.find('.source-count').text(data.candidates_count);
                                } else if (data.status === 'empty') {
                                    statusHtml = '<span class="status-badge status-warning">⚠ Empty</span>';
                                    $row.find('.source-count').text('0');
                                } else {
                                    statusHtml = '<span class="status-badge status-error">✗ Error</span>';
                                    $row.find('.source-count').html('<span title="' + data.error + '">Error</span>');
                                }
                                
                                $row.find('.source-status').html(statusHtml);
                                $row.find('.source-time').text(data.fetch_time + 'ms');
                            } else {
                                $row.find('.source-status').html('<span class="status-badge status-error">✗ Failed</span>');
                            }
                            
                            resolve();
                        });
                    });
                    
                    batchPromises.push(promise);
                }
                
                // Wait for all tests in this batch to complete, then start next batch
                Promise.all(batchPromises).then(function() {
                    testBatch(endIndex);
                });
            };
            
            testBatch(0);
        },

        testSingleSource: function(mode, sourceIndex, callback) {
            $.ajax({
                url: aiStatsAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ai_stats_test_source',
                    nonce: aiStatsAdmin.nonce,
                    mode: mode,
                    source_index: sourceIndex
                },
                success: callback,
                error: function() {
                    callback({success: false});
                }
            });
        },

        clearCache: function(e) {
            e.preventDefault();

            if (!confirm('Clear all cached data? This will force fresh fetches.')) {
                return;
            }

            const $button = $(this);
            $button.prop('disabled', true).text('Clearing...');

            $.ajax({
                url: aiStatsAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ai_stats_clear_cache',
                    nonce: aiStatsAdmin.nonce
                },
                success: function(response) {
                    $button.prop('disabled', false).text($button.attr('id') === 'clear-cache-btn' ? 'Clear Cache First' : 'Clear All Cache');
                    alert(response.success ? 'Cache cleared!' : 'Error clearing cache');
                },
                error: function() {
                    $button.prop('disabled', false).text($button.attr('id') === 'clear-cache-btn' ? 'Clear Cache First' : 'Clear All Cache');
                    alert('Error clearing cache');
                }
            });
        },

        refreshRegistry: function(e) {
            e.preventDefault();

            if (!confirm('Refresh the source registry? This will reload all 110+ sources and clear all caches.')) {
                return;
            }

            const $button = $(this);
            const $message = $('#refresh-message');

            $button.prop('disabled', true).text('🔄 Refreshing...');
            $message.hide();

            $.ajax({
                url: aiStatsAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ai_stats_refresh_registry',
                    nonce: aiStatsAdmin.nonce
                },
                success: function(response) {
                    $button.prop('disabled', false).text('🔄 Refresh Source Registry');

                    if (response.success) {
                        let messageHtml = '<strong>✅ ' + response.data.message + '</strong><br><br>';
                        messageHtml += '<strong>Sources by Mode:</strong><ul style="margin: 10px 0;">';

                        $.each(response.data.mode_counts, function(key, mode) {
                            messageHtml += '<li><strong>' + mode.name + ':</strong> ' + mode.count + ' sources</li>';
                        });

                        messageHtml += '</ul>';
                        messageHtml += '<p><em>Please reload this page to see the updated source list.</em></p>';

                        $message.html(messageHtml).show();

                        // Reload page after 2 seconds
                        setTimeout(function() {
                            location.reload();
                        }, 2000);
                    } else {
                        $message.html('<strong>❌ Error:</strong> ' + (response.data.message || 'Unknown error')).css('border-color', '#dc3232').show();
                    }
                },
                error: function(xhr, status, error) {
                    $button.prop('disabled', false).text('🔄 Refresh Source Registry');
                    $message.html('<strong>❌ AJAX Error:</strong> ' + error).css('border-color', '#dc3232').show();
                }
            });
        }
    };

    AIStatsDebug.init();
});
