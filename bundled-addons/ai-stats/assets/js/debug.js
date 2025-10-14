/**
 * AI-Stats Debug Page JavaScript
 *
 * @package AI_Stats
 * @version 0.3.4
 */

jQuery(document).ready(function($) {
    'use strict';

    const AIStatsDebug = {
        pipelineData: null, // Store full pipeline data

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

            $button.prop('disabled', true).text('Running...');
            $('#pipeline-results').hide();

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
                    $button.prop('disabled', false).text('Run Pipeline Test');

                    if (response.success) {
                        AIStatsDebug.pipelineData = response.data; // Store full data
                        AIStatsDebug.displayPipelineResults(response.data);
                    } else {
                        alert('Error: ' + (response.data.message || 'Unknown error'));
                    }
                },
                error: function(xhr, status, error) {
                    $button.prop('disabled', false).text('Run Pipeline Test');
                    alert('AJAX Error: ' + error);
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
            this.updateFinalDisplay();

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
            this.updateFinalDisplay();

            $button.prop('disabled', false).html('<span class="dashicons dashicons-update"></span> Re-run Scoring');
        },

        displayPipelineResults: function(pipeline) {
            // Store the complete pipeline data including ALL candidates
            this.pipelineData = pipeline;

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
            this.updateFinalDisplay();
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

            if (pipeline.normalised_count > 0) {
                // Add JSON viewer for ALL candidates
                normHtml += this.createJsonViewer('normalised', this.pipelineData.all_candidates, 'View All ' + pipeline.normalised_count + ' Normalised Candidates (JSON)');
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
                    filterHtml += '<p style="margin: 5px 0; font-size: 12px; color: #666;"><em>AI automatically found these related terms to improve matching accuracy</em></p>';
                    filterHtml += '</div>';
                } else {
                    filterHtml += '<div style="margin: 10px 0; padding: 10px; background: #fff3cd; border: 1px solid #ffc107;">';
                    filterHtml += '<strong>⚠️ AI Expansion Not Available:</strong> Filtering by exact keyword matches only.<br>';
                    filterHtml += '<em>Configure AI-Core to enable automatic synonym expansion</em>';
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
                rankHtml += '<th style="width:50px;">Rank</th><th>Title</th><th>Source</th><th style="width:80px;">Score</th><th style="width:120px;">Published</th></tr></thead><tbody>';

                pipeline.ranked_candidates.slice(0, 20).forEach(function(candidate, index) {
                    rankHtml += '<tr>';
                    rankHtml += '<td>' + (index + 1) + '</td>';
                    rankHtml += '<td><strong>' + candidate.title + '</strong><br><small>' + candidate.blurb_seed.substring(0, 100) + '...</small></td>';
                    rankHtml += '<td>' + candidate.source + '</td>';
                    rankHtml += '<td><strong>' + Math.round(candidate.score) + '</strong></td>';
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

        updateFinalDisplay: function() {
            const pipeline = this.pipelineData;

            // Statistics
            let statsHtml = '<div class="pipeline-stats">';
            statsHtml += '<div class="stat-card"><div class="stat-label">Final Selection</div><div class="stat-value">' + pipeline.final_candidates.length + '</div></div>';
            statsHtml += '</div>';

            let finalHtml = statsHtml;

            if (pipeline.final_candidates.length > 0) {
                finalHtml += '<table class="wp-list-table widefat fixed striped"><thead><tr>';
                finalHtml += '<th style="width:50px;">#</th><th>Title</th><th>Source</th><th style="width:80px;">Score</th></tr></thead><tbody>';

                pipeline.final_candidates.forEach(function(candidate, index) {
                    finalHtml += '<tr>';
                    finalHtml += '<td>' + (index + 1) + '</td>';
                    finalHtml += '<td><strong>' + candidate.title + '</strong><br><small>' + candidate.blurb_seed.substring(0, 100) + '...</small></td>';
                    finalHtml += '<td>' + candidate.source + '</td>';
                    finalHtml += '<td><strong>' + Math.round(candidate.score) + '</strong></td>';
                    finalHtml += '</tr>';
                });
                finalHtml += '</tbody></table>';

                finalHtml += this.createJsonViewer('final', pipeline.final_candidates, 'View All ' + pipeline.final_candidates.length + ' Final Candidates (JSON)');
            } else {
                finalHtml += '<div class="notice notice-error"><p><strong>✗ No final candidates!</strong></p>';
                finalHtml += '<p>This means the pipeline failed. Check the stages above to see where it broke.</p></div>';
            }

            $('#final-results').html(finalHtml);
        },

        updateAIGenerationDisplay: function() {
            const pipeline = this.pipelineData;

            let aiHtml = '<div class="pipeline-controls">';
            aiHtml += '<h5>AI Generation Settings</h5>';
            aiHtml += '<table class="form-table">';
            aiHtml += '<tr><th><label>Provider</label></th><td><select id="ai-provider" class="regular-text"><option value="openai">OpenAI</option><option value="anthropic">Anthropic</option><option value="google">Google Gemini</option></select></td></tr>';
            aiHtml += '<tr><th><label>Model</label></th><td><input type="text" id="ai-model" class="regular-text" value="gpt-4o-mini" placeholder="e.g., gpt-4o-mini"></td></tr>';
            aiHtml += '<tr><th><label>Temperature</label></th><td><input type="number" id="ai-temperature" class="small-text" value="0.2" min="0" max="2" step="0.1"></td></tr>';
            aiHtml += '<tr><th><label>Max Tokens</label></th><td><input type="number" id="ai-max-tokens" class="small-text" value="300" min="50" max="4000"></td></tr>';
            aiHtml += '</table>';
            aiHtml += '<button type="button" id="test-ai-generation" class="button button-primary"><span class="dashicons dashicons-admin-generic"></span> Test AI Generation</button>';
            aiHtml += '</div>';

            aiHtml += '<div id="ai-prompt-preview" style="margin-top:20px;"></div>';
            aiHtml += '<div id="ai-output-preview" style="margin-top:20px;"></div>';

            $('#ai-generation-test').html(aiHtml);
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
                    const originalText = JSON.stringify(data, null, 2);

                    if (searchTerm) {
                        const highlighted = AIStatsDebug.highlightSearchTerm(originalText, searchTerm);
                        $content.html(AIStatsDebug.syntaxHighlight(highlighted));
                    } else {
                        $content.html(AIStatsDebug.syntaxHighlight(originalText));
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
            return text.replace(regex, '⚡$1⚡');
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
                freshnessWeight: 50,
                authorityWeight: 30,
                confidenceWeight: 20,
                authSources: ['ONS', 'GOV.UK', 'Google', 'Eurostat', 'Companies House']
            };

            candidates.forEach(function(candidate) {
                let score = 0;

                // Freshness score
                const ageDays = (Date.now() / 1000 - new Date(candidate.published_at).getTime() / 1000) / 86400;
                if (ageDays < 1) {
                    score += params.freshnessWeight;
                } else if (ageDays < 7) {
                    score += params.freshnessWeight * 0.6;
                } else if (ageDays < 30) {
                    score += params.freshnessWeight * 0.2;
                }

                // Authority score
                params.authSources.forEach(function(authSource) {
                    if (candidate.source.toLowerCase().indexOf(authSource.toLowerCase()) !== -1) {
                        score += params.authorityWeight;
                    }
                });

                // Confidence score
                score += (candidate.confidence || 0.5) * params.confidenceWeight;

                candidate.score = score;
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

