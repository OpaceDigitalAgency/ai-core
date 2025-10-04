/**
 * AI-Core Admin JavaScript
 *
 * @package AI_Core
 * @version 0.0.5
 */

(function($) {
    'use strict';

    /**
     * AI-Core Admin Object
     */
    const AICoreAdmin = {

        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
        },

        /**
         * Bind events
         */
        bindEvents: function() {

            // Test API key buttons
            $(document).on('click', '.ai-core-test-key', this.testApiKey.bind(this));

            // Clear API key buttons
            $(document).on('click', '.ai-core-clear-key', this.clearApiKey.bind(this));

            // Reset stats button
            $(document).on('click', '#ai-core-reset-stats', this.resetStats.bind(this));

            // Test prompt functionality
            $(document).on('click', '#ai-core-refresh-prompts', this.loadPromptsList.bind(this));
            $(document).on('change', '#ai-core-load-prompt', this.loadPromptContent.bind(this));
            $(document).on('change', '#ai-core-test-provider', this.loadModelsForProvider.bind(this));
            $(document).on('click', '#ai-core-run-test-prompt', this.runTestPrompt.bind(this));

            // Load prompts list on page load if element exists
            if ($('#ai-core-load-prompt').length) {
                this.loadPromptsList();
            }
        },

        /**
         * Test API key
         */
        testApiKey: function(e) {
            e.preventDefault();

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

            // Check if aiCoreAdmin is defined
            if (typeof aiCoreAdmin === 'undefined') {
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
                    if (response.success) {
                        this.showStatus($status, 'success', aiCoreAdmin.strings.success + ': ' + response.data.message);
                        // Fetch and display available models after successful API key test
                        this.fetchAndDisplayModels(provider);
                    } else {
                        this.showStatus($status, 'error', aiCoreAdmin.strings.error + ': ' + response.data.message);
                    }
                },
                error: (xhr, status, error) => {
                    this.showStatus($status, 'error', aiCoreAdmin.strings.error + ': ' + error);
                },
                complete: () => {
                    $button.prop('disabled', false).text('Test Key');
                }
            });
        },
        
        /**
         * Fetch and display available models for a provider
         */
        fetchAndDisplayModels: function(provider) {
            const $modelsContainer = $('#' + provider + '-models-list');

            // Create container if it doesn't exist
            if (!$modelsContainer.length) {
                const $apiKeyField = $('#' + provider + '_api_key').closest('.ai-core-api-key-field');
                $apiKeyField.after('<div id="' + provider + '-models-list" class="ai-core-models-list" style="margin-top: 10px; padding: 10px; background: #f0f0f1; border-radius: 4px;"></div>');
            }

            const $container = $('#' + provider + '-models-list');
            $container.html('<p><span class="spinner is-active" style="float: none; margin: 0 5px 0 0;"></span>Loading available models...</p>');

            $.ajax({
                url: aiCoreAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ai_core_get_models',
                    nonce: aiCoreAdmin.nonce,
                    provider: provider
                },
                success: (response) => {
                    if (response.success && response.data.models.length > 0) {
                        let html = '<h4 style="margin: 0 0 10px 0;">Available Models (' + response.data.count + '):</h4>';
                        html += '<ul style="margin: 0; padding-left: 20px; columns: 2; column-gap: 20px;">';
                        response.data.models.forEach(model => {
                            html += '<li style="margin-bottom: 5px;"><code>' + this.escapeHtml(model) + '</code></li>';
                        });
                        html += '</ul>';
                        $container.html(html).slideDown();
                    } else {
                        $container.html('<p style="color: #d63638;">No models available for this provider.</p>');
                    }
                },
                error: () => {
                    $container.html('<p style="color: #d63638;">Failed to load models.</p>');
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
            const provider = fieldName.replace('_api_key', '');

            if (!confirm('Are you sure you want to clear this API key?')) {
                return;
            }

            // Clear the inputs
            $input.val('').attr('data-has-saved', '0').attr('placeholder', 'Enter your API key');
            $savedInput.remove();
            $button.remove();

            // Update description
            $description.html('API key will be removed when you save settings.');

            // Remove models list
            $('#' + provider + '-models-list').slideUp(function() {
                $(this).remove();
            });
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
                            $('#ai-core-test-provider').val(prompt.provider || '').trigger('change');
                            $('#ai-core-test-type').val(prompt.type || 'text');
                        }
                    }
                }
            });
        },

        /**
         * Load models for selected provider
         */
        loadModelsForProvider: function(e) {
            const provider = $(e.currentTarget).val();
            const $modelSelect = $('#ai-core-test-model');

            if (!provider) {
                $modelSelect.html('<option value="">-- Select Provider First --</option>').prop('disabled', true);
                return;
            }

            $modelSelect.html('<option value="">Loading models...</option>').prop('disabled', true);

            $.ajax({
                url: aiCoreAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ai_core_get_models',
                    nonce: aiCoreAdmin.nonce,
                    provider: provider
                },
                success: (response) => {
                    if (response.success && response.data.models.length > 0) {
                        let options = '<option value="">-- Select Model --</option>';
                        response.data.models.forEach(model => {
                            options += `<option value="${this.escapeHtml(model)}">${this.escapeHtml(model)}</option>`;
                        });
                        $modelSelect.html(options).prop('disabled', false);
                    } else {
                        $modelSelect.html('<option value="">No models available</option>');
                    }
                },
                error: () => {
                    $modelSelect.html('<option value="">Error loading models</option>');
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
            const model = $('#ai-core-test-model').val();
            const type = $('#ai-core-test-type').val();
            const $result = $('#ai-core-test-prompt-result');

            if (!content) {
                alert('Please enter a prompt');
                return;
            }

            if (!provider) {
                alert('Please select a provider');
                return;
            }

            if (!model && type === 'text') {
                alert('Please select a model');
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
                    model: model,
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

