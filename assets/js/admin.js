/**
 * AI-Core Admin JavaScript
 *
 * @package AI_Core
 * @version 0.0.1
 */

(function($) {
    'use strict';

    console.log('AI-Core Admin JS loaded');
    console.log('jQuery version:', $.fn.jquery);
    console.log('aiCoreAdmin object:', typeof aiCoreAdmin !== 'undefined' ? aiCoreAdmin : 'NOT DEFINED');

    /**
     * AI-Core Admin Object
     */
    const AICoreAdmin = {

        /**
         * Initialize
         */
        init: function() {
            console.log('AICoreAdmin.init() called');
            console.log('Test buttons found:', $('.ai-core-test-key').length);
            this.bindEvents();
        },

        /**
         * Bind events
         */
        bindEvents: function() {
            console.log('Binding events...');

            // Test API key buttons
            $(document).on('click', '.ai-core-test-key', this.testApiKey.bind(this));

            // Clear API key buttons
            $(document).on('click', '.ai-core-clear-key', this.clearApiKey.bind(this));

            // Reset stats button
            $(document).on('click', '#ai-core-reset-stats', this.resetStats.bind(this));

            // Test prompt functionality
            $(document).on('click', '#ai-core-refresh-prompts', this.loadPromptsList.bind(this));
            $(document).on('change', '#ai-core-load-prompt', this.loadPromptContent.bind(this));
            $(document).on('click', '#ai-core-run-test-prompt', this.runTestPrompt.bind(this));

            // Load prompts list on page load if element exists
            if ($('#ai-core-load-prompt').length) {
                this.loadPromptsList();
            }

            console.log('Events bound successfully');
        },
        
        /**
         * Test API key
         */
        testApiKey: function(e) {
            e.preventDefault();
            console.log('testApiKey() called');

            const $button = $(e.currentTarget);
            const provider = $button.data('provider');
            const $input = $('#' + provider + '_api_key');
            const $savedInput = $('#' + provider + '_api_key_saved');
            const $status = $('#' + provider + '-status');

            // Get API key from visible input or saved hidden input
            let apiKey = $input.val();
            if (!apiKey && $savedInput.length) {
                apiKey = $savedInput.val();
            }

            console.log('Provider:', provider);
            console.log('API Key length:', apiKey ? apiKey.length : 0);
            console.log('Input found:', $input.length);
            console.log('Saved input found:', $savedInput.length);
            console.log('Status element found:', $status.length);

            // Check if aiCoreAdmin is defined
            if (typeof aiCoreAdmin === 'undefined') {
                console.error('aiCoreAdmin is not defined!');
                alert('Error: Admin configuration not loaded. Please refresh the page.');
                return;
            }

            if (!apiKey) {
                this.showStatus($status, 'error', aiCoreAdmin.strings.error + ': API key is empty');
                return;
            }

            // Show loading state
            $button.prop('disabled', true).text(aiCoreAdmin.strings.testing);
            $status.html('<span class="ai-core-spinner"></span>');

            console.log('Sending AJAX request to:', aiCoreAdmin.ajaxUrl);

            // Send AJAX request
            $.ajax({
                url: aiCoreAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ai_core_test_api_key',
                    nonce: aiCoreAdmin.nonce,
                    provider: provider,
                    api_key: apiKey
                },
                success: (response) => {
                    console.log('AJAX success:', response);
                    if (response.success) {
                        this.showStatus($status, 'success', aiCoreAdmin.strings.success + ': ' + response.data.message);
                    } else {
                        this.showStatus($status, 'error', aiCoreAdmin.strings.error + ': ' + response.data.message);
                    }
                },
                error: (xhr, status, error) => {
                    console.error('AJAX error:', status, error);
                    console.error('Response:', xhr.responseText);
                    this.showStatus($status, 'error', aiCoreAdmin.strings.error + ': ' + error);
                },
                complete: () => {
                    $button.prop('disabled', false).text('Test Key');
                }
            });
        },
        
        /**
         * Clear API key
         */
        clearApiKey: function(e) {
            e.preventDefault();

            const $button = $(e.currentTarget);
            const fieldName = $button.data('field');
            const $input = $('#' + fieldName);
            const $savedInput = $('#' + fieldName + '_saved');
            const $description = $button.closest('.ai-core-api-key-field').next('p.description');

            if (!confirm('Are you sure you want to clear this API key?')) {
                return;
            }

            // Clear the inputs
            $input.val('').attr('data-has-saved', '0').attr('placeholder', 'Enter your API key');
            $savedInput.remove();
            $button.remove();

            // Update description
            $description.html('API key will be removed when you save settings.');
        },

        /**
         * Reset statistics
         */
        resetStats: function(e) {
            e.preventDefault();

            if (!confirm('Are you sure you want to reset all statistics? This action cannot be undone.')) {
                return;
            }

            const $button = $(e.currentTarget);

            // Show loading state
            $button.prop('disabled', true).text('Resetting...');

            // Send AJAX request
            $.ajax({
                url: aiCoreAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ai_core_reset_stats',
                    nonce: aiCoreAdmin.nonce
                },
                success: (response) => {
                    if (response.success) {
                        alert(response.data.message);
                        location.reload();
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                },
                error: (xhr, status, error) => {
                    alert('Error: ' + error);
                },
                complete: () => {
                    $button.prop('disabled', false).text('Reset Statistics');
                }
            });
        },
        
        /**
         * Show status message
         */
        showStatus: function($element, type, message) {
            const icon = type === 'success' ? 'yes-alt' : 'dismiss';
            const className = type === 'success' ? 'success' : 'error';

            $element.html(
                '<span class="' + className + '">' +
                '<span class="dashicons dashicons-' + icon + '"></span> ' +
                message +
                '</span>'
            );

            // Auto-hide after 5 seconds
            setTimeout(() => {
                $element.fadeOut(() => {
                    $element.html('').show();
                });
            }, 5000);
        },

        /**
         * Load prompts list
         */
        loadPromptsList: function(e) {
            if (e) e.preventDefault();

            const $select = $('#ai-core-load-prompt');

            $.ajax({
                url: aiCoreAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ai_core_get_prompts',
                    nonce: aiCoreAdmin.nonce
                },
                success: (response) => {
                    if (response.success) {
                        $select.empty().append('<option value="">-- Select a prompt --</option>');

                        response.data.prompts.forEach(prompt => {
                            $select.append(`<option value="${prompt.id}">${this.escapeHtml(prompt.title)}</option>`);
                        });
                    }
                },
                error: (xhr, status, error) => {
                    console.error('Error loading prompts:', error);
                }
            });
        },

        /**
         * Load prompt content
         */
        loadPromptContent: function(e) {
            const promptId = $(e.currentTarget).val();

            if (!promptId) {
                return;
            }

            $.ajax({
                url: aiCoreAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ai_core_get_prompts',
                    nonce: aiCoreAdmin.nonce
                },
                success: (response) => {
                    if (response.success) {
                        const prompt = response.data.prompts.find(p => p.id == promptId);
                        if (prompt) {
                            $('#ai-core-test-prompt-content').val(prompt.content);
                            $('#ai-core-test-provider').val(prompt.provider || '');
                            $('#ai-core-test-type').val(prompt.type || 'text');
                        }
                    }
                }
            });
        },

        /**
         * Run test prompt
         */
        runTestPrompt: function(e) {
            e.preventDefault();

            const content = $('#ai-core-test-prompt-content').val();
            const provider = $('#ai-core-test-provider').val();
            const type = $('#ai-core-test-type').val();
            const $result = $('#ai-core-test-prompt-result');

            if (!content) {
                alert('Please enter a prompt');
                return;
            }

            $result.show().html('<div class="loading"><span class="ai-core-spinner"></span> Running prompt...</div>');

            $.ajax({
                url: aiCoreAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ai_core_run_prompt',
                    nonce: aiCoreAdmin.nonce,
                    prompt: content,
                    provider: provider,
                    type: type
                },
                success: (response) => {
                    if (response.success) {
                        if (response.data.type === 'image') {
                            $result.html(`<img src="${response.data.result}" alt="Generated image" style="max-width: 100%; height: auto;" />`);
                        } else {
                            $result.html(`<pre style="white-space: pre-wrap; word-wrap: break-word;">${this.escapeHtml(response.data.result)}</pre>`);
                        }
                    } else {
                        $result.html(`<div class="error" style="color: #d63638; padding: 10px; background: #fcf0f1; border: 1px solid #d63638; border-radius: 4px;">Error: ${this.escapeHtml(response.data.message)}</div>`);
                    }
                },
                error: (xhr, status, error) => {
                    $result.html(`<div class="error" style="color: #d63638; padding: 10px; background: #fcf0f1; border: 1px solid #d63638; border-radius: 4px;">Error: ${this.escapeHtml(error)}</div>`);
                }
            });
        },

        /**
         * Escape HTML
         */
        escapeHtml: function(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return String(text).replace(/[&<>"']/g, m => map[m]);
        }
    };
    
    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        console.log('Document ready, initializing AICoreAdmin...');
        AICoreAdmin.init();
    });

})(jQuery);

