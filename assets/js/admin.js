/**
 * AI-Core Admin JavaScript
 *
 * @package AI_Core
 * @version 0.0.6
 */

(function($) {
    'use strict';

    const AICoreAdmin = {
        modelsCache: {},
        providerLabels: {},
        defaultProviderPlaceholder: '',
        testProviderPlaceholder: '',
        testModelPlaceholder: '',

        /**
         * Initialize admin behaviours
         */
        init: function() {
            if (typeof aiCoreAdmin === 'undefined') {
                return;
            }

            this.modelsCache = {};
            this.providerLabels = (aiCoreAdmin.providers && aiCoreAdmin.providers.labels) || {};

            this.cachePlaceholders();
            this.bindEvents();
            this.bootstrapProviders();
            this.bootstrapPrompts();
        },

        /**
         * Cache placeholder strings so we can restore them later
         */
        cachePlaceholders: function() {
            const $defaultOption = $('#default_provider option[value=""]').first();
            this.defaultProviderPlaceholder = $defaultOption.length ? $defaultOption.text() : '-- No providers configured --';
            this.testProviderPlaceholder = $('#ai-core-test-provider option:first').text() || '-- Select Provider --';
            this.testModelPlaceholder = $('#ai-core-test-model option:first').text() || '-- Select Provider First --';
        },

        /**
         * Bind DOM events
         */
        bindEvents: function() {
            $(document).on('click', '.ai-core-test-key', this.testApiKey.bind(this));
            $(document).on('click', '.ai-core-clear-key', this.clearApiKey.bind(this));
            $(document).on('click', '#ai-core-reset-stats', this.resetStats.bind(this));
            $(document).on('click', '#ai-core-refresh-prompts', this.loadPromptsList.bind(this));
            $(document).on('change', '#ai-core-load-prompt', this.loadPromptContent.bind(this));
            $(document).on('click', '#ai-core-run-test-prompt', this.runTestPrompt.bind(this));
            $(document).on('change', '#ai-core-test-provider', (e) => {
                this.onTestProviderChange($(e.currentTarget).val(), { autoSelectFirst: true });
            });
        },

        /**
         * Load provider state from server-rendered markup
         */
        bootstrapProviders: function() {
            const configured = (aiCoreAdmin.providers && Array.isArray(aiCoreAdmin.providers.configured))
                ? aiCoreAdmin.providers.configured
                : [];

            configured.forEach((provider) => {
                this.ensureProviderOption(provider);
            });

            $('.ai-core-api-key-input').each((index, element) => {
                const $input = $(element);
                const provider = ($input.attr('id') || '').replace('_api_key', '');
                if (!provider) {
                    return;
                }

                const hasSavedKey = $input.data('has-saved') === 1 || $input.data('has-saved') === '1';
                if (hasSavedKey) {
                    this.ensureProviderOption(provider);
                    this.fetchAndDisplayModels(provider, { apiKey: this.getApiKeyForProvider(provider) });
                }
            });

            const defaultProvider = aiCoreAdmin.providers && aiCoreAdmin.providers.default
                ? aiCoreAdmin.providers.default
                : '';

            if (defaultProvider) {
                $('#default_provider').val(defaultProvider);
                $('#ai-core-test-provider').val(defaultProvider);
                this.onTestProviderChange(defaultProvider, { autoSelectFirst: true });
            } else if ($('#ai-core-test-provider').val()) {
                this.onTestProviderChange($('#ai-core-test-provider').val(), { autoSelectFirst: true });
            }
        },

        /**
         * Load prompts dropdown on page load when present
         */
        bootstrapPrompts: function() {
            if ($('#ai-core-load-prompt').length) {
                this.loadPromptsList();
            }
        },

        /**
         * Retrieve API key and validate provider
         */
        testApiKey: function(e) {
            e.preventDefault();

            const $button = $(e.currentTarget);
            const provider = $button.data('provider');
            const $status = $('#' + provider + '-status');
            const apiKey = this.getApiKeyForProvider(provider, true);

            if (!apiKey) {
                this.showStatus($status, 'error', aiCoreAdmin.strings.error + ': ' + aiCoreAdmin.strings.missingKey);
                return;
            }

            $button.prop('disabled', true).text(aiCoreAdmin.strings.testing);
            $status.html('<span class="ai-core-spinner"></span>');

            $.ajax({
                url: aiCoreAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ai_core_test_api_key',
                    nonce: aiCoreAdmin.nonce,
                    provider: provider,
                    api_key: apiKey
                }
            }).done((response) => {
                if (response.success) {
                    const message = `${aiCoreAdmin.strings.success}: ${response.data.message} ${aiCoreAdmin.strings.rememberToSave}`.trim();
                    this.showStatus($status, 'success', message);
                    this.ensureProviderOption(provider);
                    this.fetchAndDisplayModels(provider, {
                        apiKey: apiKey,
                        forceRefresh: true,
                        autoSelectTest: true
                    });
                } else {
                    const errorMessage = response.data && response.data.message ? response.data.message : aiCoreAdmin.strings.error;
                    this.showStatus($status, 'error', `${aiCoreAdmin.strings.error}: ${errorMessage}`);
                }
            }).fail((xhr, status, error) => {
                this.showStatus($status, 'error', `${aiCoreAdmin.strings.error}: ${error || status}`);
            }).always(() => {
                $button.prop('disabled', false).text('Test Key');
            });
        },

        /**
         * Fetch models and update inline list + test dropdowns
         */
        fetchAndDisplayModels: function(provider, options = {}) {
            const $container = this.ensureModelsContainer(provider);
            if (!$container) {
                return;
            }

            $container.html(`<p><span class="spinner is-active" style="float:none;margin:0 5px 0 0;"></span>${this.escapeHtml(aiCoreAdmin.strings.loadingModels)}</p>`);

            this.fetchModels(provider, options).done((models) => {
                if (models.length) {
                    let labelText = aiCoreAdmin.strings.availableModels.replace('%d', models.length);
                    if (labelText === aiCoreAdmin.strings.availableModels) {
                        labelText = `${aiCoreAdmin.strings.availableModels} ${models.length}`;
                    }

                    let html = `<h4 style="margin:0 0 10px 0;">${this.escapeHtml(labelText)}</h4>`;
                    html += '<ul style="margin:0;padding-left:20px;columns:2;column-gap:20px;">';
                    models.forEach((model) => {
                        html += `<li style="margin-bottom:5px;"><code>${this.escapeHtml(model)}</code></li>`;
                    });
                    html += '</ul>';
                    $container.html(html).slideDown(200);
                } else {
                    $container.html(`<p style="color:#646970;">${this.escapeHtml(aiCoreAdmin.strings.noModels)}</p>`);
                }

                this.ensureProviderOption(provider);

                if (options.autoSelectTest) {
                    const $defaultSelect = $('#default_provider');
                    if ($defaultSelect.length && !$defaultSelect.val()) {
                        $defaultSelect.val(provider);
                    }

                    $('#ai-core-test-provider').val(provider);
                    this.onTestProviderChange(provider, { forceRefresh: true, autoSelectFirst: true });
                } else if ($('#ai-core-test-provider').val() === provider) {
                    this.onTestProviderChange(provider, { forceRefresh: options.forceRefresh });
                }
            }).fail((error) => {
                const message = error || aiCoreAdmin.strings.errorLoadingModels;
                $container.html(`<p style="color:#d63638;">${this.escapeHtml(message)}</p>`);
            });
        },

        /**
         * Fetch models for a provider (promise)
         */
        fetchModels: function(provider, options = {}) {
            const deferred = $.Deferred();
            const apiKey = options.apiKey || this.getApiKeyForProvider(provider);

            if (!apiKey) {
                this.modelsCache[provider] = [];
                deferred.reject(aiCoreAdmin.strings.missingKey);
                return deferred.promise();
            }

            $.ajax({
                url: aiCoreAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ai_core_get_models',
                    nonce: aiCoreAdmin.nonce,
                    provider: provider,
                    api_key: apiKey,
                    force_refresh: options.forceRefresh ? 1 : 0
                }
            }).done((response) => {
                if (response.success) {
                    const models = response.data.models || [];
                    this.modelsCache[provider] = models;
                    deferred.resolve(models);
                } else {
                    this.modelsCache[provider] = [];
                    const message = response.data && response.data.message ? response.data.message : aiCoreAdmin.strings.errorLoadingModels;
                    deferred.reject(message);
                }
            }).fail((xhr, status, error) => {
                this.modelsCache[provider] = [];
                deferred.reject(error || status || aiCoreAdmin.strings.errorLoadingModels);
            });

            return deferred.promise();
        },

        /**
         * Ensure inline models container exists
         */
        ensureModelsContainer: function(provider) {
            if (!provider) {
                return null;
            }

            let $container = $('#' + provider + '-models-list');
            if (!$container.length) {
                const $field = $('#' + provider + '_api_key').closest('.ai-core-api-key-field');
                if (!$field.length) {
                    return null;
                }

                $container = $('<div/>', {
                    id: provider + '-models-list',
                    class: 'ai-core-models-list'
                }).css({
                    marginTop: '10px',
                    padding: '10px',
                    background: '#f0f0f1',
                    borderRadius: '4px'
                });

                $field.after($container);
            }

            return $container;
        },

        /**
         * Guarantee provider options are present in dropdowns
         */
        ensureProviderOption: function(provider) {
            if (!provider) {
                return;
            }

            const label = this.providerLabels[provider] || provider;
            const $defaultSelect = $('#default_provider');
            if ($defaultSelect.length && !$defaultSelect.find(`option[value="${provider}"]`).length) {
                const $option = $('<option></option>').val(provider).text(label);
                const $placeholder = $defaultSelect.find('option[value=""]').first();
                if ($placeholder.length) {
                    $placeholder.after($option);
                } else {
                    $defaultSelect.append($option);
                }
            }

            const $testSelect = $('#ai-core-test-provider');
            if ($testSelect.length && !$testSelect.find(`option[value="${provider}"]`).length) {
                $testSelect.append($('<option></option>').val(provider).text(label));
            }
        },

        /**
         * Remove provider from dropdowns when key cleared
         */
        removeProviderOption: function(provider) {
            const $defaultSelect = $('#default_provider');
            const $testSelect = $('#ai-core-test-provider');

            if ($defaultSelect.val() === provider) {
                $defaultSelect.val('');
            }

            $defaultSelect.find(`option[value="${provider}"]`).remove();
            if (!$defaultSelect.find('option[value!=""]').length && this.defaultProviderPlaceholder) {
                if (!$defaultSelect.find('option[value=""]').length) {
                    $defaultSelect.append($('<option></option>').val('').text(this.defaultProviderPlaceholder));
                }
                $defaultSelect.val('');
            }

            if ($testSelect.val() === provider) {
                $testSelect.val('');
                this.onTestProviderChange('');
            }

            $testSelect.find(`option[value="${provider}"]`).remove();
        },

        /**
         * Resolve API key for provider
         */
        getApiKeyForProvider: function(provider, preferVisible = false) {
            const $input = $('#' + provider + '_api_key');
            if (!$input.length) {
                return '';
            }

            const typed = $input.val();
            if (preferVisible && typed) {
                return typed;
            }

            if (typed) {
                return typed;
            }

            const $saved = $('#' + provider + '_api_key_saved');
            return $saved.length ? $saved.val() : '';
        },

        /**
         * Update models dropdown when provider changes
         */
        onTestProviderChange: function(provider, options = {}) {
            const $modelSelect = $('#ai-core-test-model');

            if (!provider) {
                $modelSelect.html(`<option value="">${this.escapeHtml(this.testModelPlaceholder)}</option>`).prop('disabled', true);
                return;
            }

            const cachedModels = this.modelsCache[provider];
            if (Array.isArray(cachedModels) && cachedModels.length && !options.forceRefresh) {
                this.populateTestModelSelect(provider, cachedModels, options);
                return;
            }

            $modelSelect.html(`<option value="">${this.escapeHtml(aiCoreAdmin.strings.loadingModels)}</option>`).prop('disabled', true);

            this.fetchModels(provider, { forceRefresh: !!options.forceRefresh }).done((models) => {
                this.populateTestModelSelect(provider, models, options);
            }).fail((error) => {
                const message = error || aiCoreAdmin.strings.errorLoadingModels;
                $modelSelect.html(`<option value="">${this.escapeHtml(message)}</option>`).prop('disabled', true);
            });
        },

        /**
         * Populate model select element
         */
        populateTestModelSelect: function(provider, models, options = {}) {
            const $modelSelect = $('#ai-core-test-model');

            if (!models || !models.length) {
                $modelSelect.html(`<option value="">${this.escapeHtml(aiCoreAdmin.strings.noModels)}</option>`).prop('disabled', true);
                return;
            }

            $modelSelect.empty().prop('disabled', false);
            $modelSelect.append($('<option></option>').val('').text(aiCoreAdmin.strings.placeholderSelectModel));
            models.forEach((model) => {
                $modelSelect.append($('<option></option>').val(model).text(model));
            });

            if (options.desiredModel) {
                $modelSelect.val(options.desiredModel);
            } else if (options.autoSelectFirst) {
                $modelSelect.val(models[0]);
            }
        },

        /**
         * Clear stored API key
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

            $input.val('').attr('data-has-saved', '0').attr('placeholder', 'Enter your API key');
            $savedInput.remove();
            $button.remove();
            $description.html('API key will be removed when you save settings.');

            $('#' + provider + '-models-list').slideUp(function() {
                $(this).remove();
            });

            this.modelsCache[provider] = [];
            this.removeProviderOption(provider);
        },

        /**
         * Reset usage statistics
         */
        resetStats: function(e) {
            e.preventDefault();

            if (!confirm('Are you sure you want to reset all statistics? This action cannot be undone.')) {
                return;
            }

            const $button = $(e.currentTarget);
            $button.prop('disabled', true).text('Resetting...');

            $.ajax({
                url: aiCoreAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ai_core_reset_stats',
                    nonce: aiCoreAdmin.nonce
                }
            }).done((response) => {
                if (response.success) {
                    alert(response.data.message);
                    location.reload();
                } else {
                    alert('Error: ' + response.data.message);
                }
            }).fail((xhr, status, error) => {
                alert('Error: ' + (error || status));
            }).always(() => {
                $button.prop('disabled', false).text('Reset Statistics');
            });
        },

        /**
         * Show transient status message next to API inputs
         */
        showStatus: function($element, type, message) {
            const icon = type === 'success' ? 'yes-alt' : 'dismiss';
            const className = type === 'success' ? 'success' : 'error';

            $element.html(
                `<span class="${className}"><span class="dashicons dashicons-${icon}"></span> ${message}</span>`
            );

            setTimeout(() => {
                $element.fadeOut(() => {
                    $element.html('').show();
                });
            }, 5000);
        },

        /**
         * Load prompt library select options
         */
        loadPromptsList: function(e) {
            if (e) {
                e.preventDefault();
            }

            const $select = $('#ai-core-load-prompt');

            $.ajax({
                url: aiCoreAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ai_core_get_prompts',
                    nonce: aiCoreAdmin.nonce
                }
            }).done((response) => {
                if (response.success) {
                    $select.empty().append('<option value="">-- Select a prompt --</option>');
                    response.data.prompts.forEach((prompt) => {
                        $select.append(`<option value="${prompt.id}">${this.escapeHtml(prompt.title)}</option>`);
                    });
                }
            }).fail((xhr, status, error) => {
                window.console && console.error('Error loading prompts:', error || status);
            });
        },

        /**
         * Load prompt content when option selected
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
                }
            }).done((response) => {
                if (response.success) {
                    const prompt = response.data.prompts.find((p) => p.id == promptId);
                    if (prompt) {
                        $('#ai-core-test-prompt-content').val(prompt.content);
                        $('#ai-core-test-provider').val(prompt.provider || '').trigger('change');
                        $('#ai-core-test-type').val(prompt.type || 'text');
                    }
                }
            });
        },

        /**
         * Run prompt against selected provider/model
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
                }
            }).done((response) => {
                if (response.success) {
                    if (response.data.type === 'image') {
                        $result.html(`<img src="${response.data.result}" alt="Generated image" style="max-width:100%;height:auto;" />`);
                    } else {
                        $result.html(`<pre style="white-space:pre-wrap;word-wrap:break-word;">${this.escapeHtml(response.data.result)}</pre>`);
                    }
                } else {
                    $result.html(`<div class="error" style="color:#d63638;padding:10px;background:#fcf0f1;border:1px solid #d63638;border-radius:4px;">Error: ${this.escapeHtml(response.data.message)}</div>`);
                }
            }).fail((xhr, status, error) => {
                $result.html(`<div class="error" style="color:#d63638;padding:10px;background:#fcf0f1;border:1px solid #d63638;border-radius:4px;">Error: ${this.escapeHtml(error || status)}</div>`);
            });
        },

        /**
         * Basic HTML escaping helper
         */
        escapeHtml: function(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return String(text || '').replace(/[&<>"']/g, (m) => map[m]);
        }
    };

    $(document).ready(() => {
        AICoreAdmin.init();
    });

})(jQuery);
