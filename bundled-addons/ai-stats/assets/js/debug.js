/**
 * AI-Stats Debug Page JavaScript
 *
 * @package AI_Stats
 * @version 0.3.1
 */

jQuery(document).ready(function($) {
    'use strict';

    const AIStatsDebug = {
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            // Tab switching
            $('.nav-tab').on('click', this.switchTab);

            // Google Trends fetch
            $('#fetch-google-trends').on('click', this.fetchGoogleTrends.bind(this));

            // Pipeline test
            $('#run-pipeline-test').on('click', this.runPipelineTest);

            // Test all sources
            $('#test-all-sources').on('click', this.testAllSources);

            // Clear cache
            $('#clear-cache-btn, #clear-all-cache').on('click', this.clearCache.bind(this));

            // Refresh source registry
            $('#refresh-source-registry').on('click', this.refreshRegistry.bind(this));
        },

        switchTab: function(e) {
            e.preventDefault();
            const target = $(this).attr('href');

            $('.nav-tab').removeClass('nav-tab-active');
            $(this).addClass('nav-tab-active');

            $('.tab-content').removeClass('active');
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
            const keywords = $('#pipeline-keywords').val().split(',').map(k => k.trim()).filter(k => k);
            
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
                    limit: 12
                },
                success: function(response) {
                    $button.prop('disabled', false).text('Run Pipeline Test');
                    
                    if (response.success) {
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

        displayPipelineResults: function(pipeline) {
            // 1. Fetch Results
            let fetchHtml = '<table class="wp-list-table widefat fixed striped"><thead><tr>';
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
            
            // 2. Normalised Data
            let normHtml = '<p><strong>Total candidates fetched:</strong> ' + pipeline.normalised_count + '</p>';
            if (pipeline.fetch_results.length > 0) {
                normHtml += '<details><summary>View first 20 normalised candidates</summary>';
                normHtml += '<pre>' + JSON.stringify(pipeline.fetch_results, null, 2) + '</pre></details>';
            } else {
                normHtml += '<p class="status-warning">⚠ No data fetched from any source</p>';
            }
            $('#normalised-results').html(normHtml);
            
            // 3. Filtered Data
            let filterHtml = '<p><strong>After keyword filtering:</strong> ' + pipeline.filtered_count + ' candidates</p>';
            if (pipeline.filter_removed > 0) {
                filterHtml += '<p class="status-warning">⚠ Removed ' + pipeline.filter_removed + ' candidates that didn\'t match keywords</p>';
            }
            if (pipeline.keywords.length > 0) {
                filterHtml += '<p><strong>Keywords used:</strong> ' + pipeline.keywords.join(', ') + '</p>';
            } else {
                filterHtml += '<p><em>No keyword filtering applied</em></p>';
            }
            $('#filtered-results').html(filterHtml);
            
            // 4. Ranked Data
            let rankHtml = '<p><strong>Candidates after scoring:</strong> ' + pipeline.ranked_candidates.length + '</p>';
            if (pipeline.ranked_candidates.length > 0) {
                rankHtml += '<table class="wp-list-table widefat fixed striped"><thead><tr>';
                rankHtml += '<th>Rank</th><th>Title</th><th>Source</th><th>Score</th><th>Published</th></tr></thead><tbody>';
                
                pipeline.ranked_candidates.slice(0, 10).forEach(function(candidate, index) {
                    rankHtml += '<tr>';
                    rankHtml += '<td>' + (index + 1) + '</td>';
                    rankHtml += '<td><strong>' + candidate.title + '</strong><br><small>' + candidate.blurb_seed.substring(0, 100) + '...</small></td>';
                    rankHtml += '<td>' + candidate.source + '</td>';
                    rankHtml += '<td><strong>' + Math.round(candidate.score) + '</strong></td>';
                    rankHtml += '<td>' + candidate.published_at + '</td>';
                    rankHtml += '</tr>';
                });
                rankHtml += '</tbody></table>';
            } else {
                rankHtml += '<p class="status-error">✗ No candidates to rank</p>';
            }
            $('#ranked-results').html(rankHtml);
            
            // 5. Final Candidates
            let finalHtml = '<p><strong>Final selection:</strong> ' + pipeline.final_candidates.length + ' candidates</p>';
            if (pipeline.final_candidates.length > 0) {
                finalHtml += '<table class="wp-list-table widefat fixed striped"><thead><tr>';
                finalHtml += '<th>#</th><th>Title</th><th>Source</th><th>Score</th></tr></thead><tbody>';
                
                pipeline.final_candidates.forEach(function(candidate, index) {
                    finalHtml += '<tr>';
                    finalHtml += '<td>' + (index + 1) + '</td>';
                    finalHtml += '<td><strong>' + candidate.title + '</strong><br><small>' + candidate.blurb_seed.substring(0, 100) + '...</small></td>';
                    finalHtml += '<td>' + candidate.source + '</td>';
                    finalHtml += '<td><strong>' + Math.round(candidate.score) + '</strong></td>';
                    finalHtml += '</tr>';
                });
                finalHtml += '</tbody></table>';
                finalHtml += '<details style="margin-top:10px;"><summary>View raw JSON</summary>';
                finalHtml += '<pre>' + JSON.stringify(pipeline.final_candidates, null, 2) + '</pre></details>';
            } else {
                finalHtml += '<div class="notice notice-error"><p><strong>✗ No final candidates!</strong></p>';
                finalHtml += '<p>This means the pipeline failed. Check the stages above to see where it broke.</p></div>';
            }
            $('#final-results').html(finalHtml);
            
            $('#pipeline-results').show();
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

