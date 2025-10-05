/**
 * AI-Core Admin JavaScript
 *
 * @package AI_Core
 * @version 0.0.8
 */

(function($) {
    'use strict';

    const initialSelectedModels = (aiCoreAdmin.providers && aiCoreAdmin.providers.selectedModels) || {};
    const providerModelsMap = {};
    Object.keys(initialSelectedModels).forEach((provider) => {
        providerModelsMap[provider] = { selected: initialSelectedModels[provider] };
    });

    const state = {
        debounceTimers: {},
        models: (aiCoreAdmin.providers && aiCoreAdmin.providers.models) || {},
        configured: new Set(aiCoreAdmin.providers && aiCoreAdmin.providers.configured ? aiCoreAdmin.providers.configured : []),
        defaultProvider: aiCoreAdmin.providers && aiCoreAdmin.providers.default ? aiCoreAdmin.providers.default : '',
        saving: {},
        providerModels: providerModelsMap,
        providerOptions: $.extend(true, {}, (aiCoreAdmin.providers && aiCoreAdmin.providers.options) || {}),
        modelMeta: $.extend(true, {}, (aiCoreAdmin.providers && aiCoreAdmin.providers.meta) || {})
    };

    const Admin = {
        init: function() {
            if (typeof aiCoreAdmin === 'undefined') {
                return;
            }

            this.bindEvents();
            this.bootstrapProviders();
            this.bootstrapTestPrompt();
        },

        bindEvents: function() {
            $(document).on('input', '.ai-core-api-key-input', this.onKeyInput.bind(this));
            $(document).on('blur', '.ai-core-api-key-input', this.onKeyBlur.bind(this));
            $(document).on('click', '.ai-core-test-key', this.testApiKey.bind(this));
            $(document).on('click', '.ai-core-refresh-models', this.onRefreshModels.bind(this));
            $(document).on('click', '.ai-core-provider-refresh', this.onRefreshModels.bind(this));
            $(document).on('click', '.ai-core-clear-key', this.onClearKey.bind(this));
           $(document).on('change', '.ai-core-provider-model', this.onProviderModelChange.bind(this));
           $(document).on('change', '#default_provider', this.onDefaultProviderChange.bind(this));
            $(document).on('change', '#ai-core-test-provider', (event) => {
                this.onTestProviderChange($(event.currentTarget).val(), { initialise: true });
                this.updateTypeDropdown();
            });
            $(document).on('input change', '.ai-core-param-input', this.onParameterChange.bind(this));
            $(document).on('click', '#ai-core-refresh-prompts', this.loadPromptsList.bind(this));
            $(document).on('change', '#ai-core-load-prompt', this.loadPromptContent.bind(this));
            $(document).on('click', '#ai-core-run-test-prompt', this.runTestPrompt.bind(this));
        },

        bootstrapProviders: function() {
            const $cards = $('.ai-core-provider-card');
            $cards.each((_, card) => {
                const provider = $(card).data('provider');
                if (!provider) {
                    return;
                }

                if (state.configured.has(provider)) {
                    this.markProviderConnected(provider);
                } else {
                    this.markProviderDisconnected(provider);
                }

                const storedModels = state.models[provider];
                if (storedModels && storedModels.length) {
                    this.populateProviderModels(provider, storedModels);
                } else if (state.configured.has(provider)) {
                    this.fetchModels(provider, { showStatus: false });
                }
            });

            if (!state.defaultProvider && state.configured.size) {
                state.defaultProvider = Array.from(state.configured)[0];
            }

            if (state.defaultProvider) {
                $('#default_provider').val(state.defaultProvider);
                $('#ai-core-test-provider').val(state.defaultProvider);
            }
        },

        bootstrapTestPrompt: function() {
            if ($('#ai-core-load-prompt').length) {
                this.loadPromptsList();
            }

            const provider = $('#ai-core-test-provider').val();
            if (provider) {
                this.onTestProviderChange(provider, { initialise: true });
            }

            // Initialize type dropdown based on current provider
            this.updateTypeDropdown();
        },

        onKeyInput: function(event) {
            const $input = $(event.currentTarget);
            const provider = $input.data('provider');
            if (!provider) {
                return;
            }

            const value = $.trim($input.val());
            const $status = $('#' + provider + '-status');

            if (!value) {
                this.showStatus($status, 'notice', aiCoreAdmin.strings.awaitingKey);
                return;
            }

            if (value.length < 12) {
                this.showStatus($status, 'notice', aiCoreAdmin.strings.keyTooShort);
                return;
            }

            if (state.debounceTimers[provider]) {
                clearTimeout(state.debounceTimers[provider]);
            }

            this.showStatus($status, 'notice', aiCoreAdmin.strings.saving);

            state.debounceTimers[provider] = setTimeout(() => {
                this.saveApiKey(provider, value, $input, $status);
            }, 600);
        },

        onKeyBlur: function(event) {
            const $input = $(event.currentTarget);
            const provider = $input.data('provider');
            if (!provider) {
                return;
            }

            if (state.debounceTimers[provider]) {
                clearTimeout(state.debounceTimers[provider]);
                delete state.debounceTimers[provider];
            }

            const value = $.trim($input.val());
            const $status = $('#' + provider + '-status');

            if (value && value.length >= 12) {
                this.saveApiKey(provider, value, $input, $status);
            }
        },

        onRefreshModels: function(event) {
            event.preventDefault();
            const provider = $(event.currentTarget).data('provider');
            if (!provider) {
                return;
            }
            this.fetchModels(provider, { force: true, showStatus: true });
        },

        onClearKey: function(event) {
            event.preventDefault();
            const $button = $(event.currentTarget);
            const fieldName = $button.data('field');
            const provider = fieldName.replace('_api_key', '');

            if (!provider || !confirm(aiCoreAdmin.strings.confirmClear)) {
                return;
            }

            this.clearApiKey(provider);
        },

        onProviderModelChange: function(event) {
            const $select = $(event.currentTarget);
            const provider = $select.data('provider');
            const model = $select.val();

            if (!provider) {
                return;
            }

            if (!state.providerModels[provider]) {
                state.providerModels[provider] = {};
            }

            state.providerModels[provider].selected = model;

            this.renderProviderParameters(provider, model);

            const currentTestProvider = $('#ai-core-test-provider').val();
            if (currentTestProvider === provider) {
                $('#ai-core-test-model').val(model);
            }
        },

        onDefaultProviderChange: function(event) {
            const provider = $(event.currentTarget).val();
            if (!provider) {
                return;
            }

            state.defaultProvider = provider;
            this.onTestProviderChange(provider, { initialise: true });
        },

        saveApiKey: function(provider, apiKey, $input, $status) {
            if (state.saving[provider]) {
                return;
            }

            const $savedInput = $('#' + provider + '_api_key_saved');
            if ($savedInput.length && $savedInput.val() === apiKey) {
                this.showStatus($status, 'success', aiCoreAdmin.strings.alreadySaved);
                $input.val('');
                return;
            }

            state.saving[provider] = true;

            $.ajax({
                url: aiCoreAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ai_core_save_api_key',
                    nonce: aiCoreAdmin.nonce,
                    provider: provider,
                    api_key: apiKey
                }
            }).done((response) => {
                if (!response || !response.success) {
                    const message = response && response.data && response.data.message ? response.data.message : aiCoreAdmin.strings.error;
                    this.showStatus($status, 'error', message);
                    return;
                }

                this.onKeySaved(provider, apiKey, response.data, $input, $status);
            }).fail((xhr, status, error) => {
                this.showStatus($status, 'error', error || status || aiCoreAdmin.strings.error);
            }).always(() => {
                delete state.saving[provider];
            });
        },

        onKeySaved: function(provider, apiKey, data, $input, $status) {
            const $savedInput = $('#' + provider + '_api_key_saved');
            if ($savedInput.length) {
                $savedInput.val(apiKey);
            } else {
                $('<input>', {
                    type: 'hidden',
                    id: provider + '_api_key_saved',
                    value: apiKey
                }).insertAfter($input);
            }

            $input.val('').attr('placeholder', data.masked_key || aiCoreAdmin.strings.savedPlaceholder);
            $input.attr('data-has-saved', '1');

            this.showStatus($status, 'success', aiCoreAdmin.strings.saved);

            const $refreshButton = $('.ai-core-refresh-models[data-provider="' + provider + '"]');
            $refreshButton.prop('disabled', false);

            let $clearButton = $('.ai-core-clear-key[data-field="' + provider + '_api_key"]').first();
            if (!$clearButton.length) {
                $clearButton = $('<button></button>', {
                    type: 'button',
                    class: 'button ai-core-clear-key',
                    'data-field': provider + '_api_key'
                }).text(aiCoreAdmin.strings.clearKey);
                $refreshButton.after($clearButton);
            }

            $clearButton.prop('disabled', false);

            state.configured.add(provider);
            this.markProviderConnected(provider);

            if (data.model_meta) {
                this.updateModelMeta(provider, data.model_meta);
            }

            if (data.parameters) {
                state.providerOptions[provider] = state.providerOptions[provider] || {};
                Object.keys(data.parameters).forEach((paramKey) => {
                    if (state.providerOptions[provider][paramKey] === undefined && data.parameters[paramKey].default !== undefined) {
                        state.providerOptions[provider][paramKey] = data.parameters[paramKey].default;
                    }
                });
            }

            if (Array.isArray(data.models)) {
                const preferred = data.preferred_model || data.selected_model || (data.models.length ? data.models[0] : '');
                state.models[provider] = data.models;
                this.populateProviderModels(provider, data.models, { selected: preferred });
            } else {
                this.fetchModels(provider, { force: true, showStatus: false });
            }

            if (data.default_provider) {
                state.defaultProvider = data.default_provider;
                $('#default_provider').val(state.defaultProvider);
                $('#ai-core-test-provider').val(state.defaultProvider);
                this.onTestProviderChange(state.defaultProvider, { initialise: true });
            }

            this.ensureProviderOptionExists(provider);
        },

        fetchModels: function(provider, options = {}) {
            const hasSavedKey = $('#' + provider + '_api_key_saved').length > 0;
            if (!hasSavedKey) {
                this.markProviderDisconnected(provider);
                return;
            }

            const $status = $('#' + provider + '-status');
            if (options.showStatus) {
                this.showStatus($status, 'notice', aiCoreAdmin.strings.refreshing);
            }

            $.ajax({
                url: aiCoreAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ai_core_get_models',
                    nonce: aiCoreAdmin.nonce,
                    provider: provider,
                    api_key: $('#' + provider + '_api_key_saved').val(),
                    force_refresh: options.force ? 1 : 0
                }
            }).done((response) => {
                if (response && response.success) {
                    state.models[provider] = response.data.models;
                    if (response.data.model_meta) {
                        this.updateModelMeta(provider, response.data.model_meta);
                    }

                    if (response.data.parameters) {
                        state.providerOptions[provider] = state.providerOptions[provider] || {};
                        Object.keys(response.data.parameters).forEach((paramKey) => {
                            if (state.providerOptions[provider][paramKey] === undefined && response.data.parameters[paramKey].default !== undefined) {
                                state.providerOptions[provider][paramKey] = response.data.parameters[paramKey].default;
                            }
                        });
                    }

                    this.populateProviderModels(provider, response.data.models, { selected: response.data.preferred_model });

                    if (options.showStatus) {
                        this.showStatus($status, 'success', aiCoreAdmin.strings.modelsLoaded);
                    }
                } else if (options.showStatus) {
                    const message = response && response.data && response.data.message ? response.data.message : aiCoreAdmin.strings.errorLoadingModels;
                    this.showStatus($status, 'error', message);
                }
            }).fail((xhr, status, error) => {
                if (options.showStatus) {
                    this.showStatus($status, 'error', error || status || aiCoreAdmin.strings.errorLoadingModels);
                }
            });
        },

        clearApiKey: function(provider) {
            $.ajax({
                url: aiCoreAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ai_core_clear_api_key',
                    nonce: aiCoreAdmin.nonce,
                    provider: provider
                }
            }).done((response) => {
                if (!response || !response.success) {
                    alert(aiCoreAdmin.strings.error);
                    return;
                }

                const fieldName = provider + '_api_key';
                const $input = $('#' + fieldName);
                const $saved = $('#' + fieldName + '_saved');
                const $status = $('#' + provider + '-status');

                $input.val('').attr('data-has-saved', '0').attr('placeholder', aiCoreAdmin.strings.enterKeyPlaceholder);
                $saved.remove();

                this.showStatus($status, 'notice', aiCoreAdmin.strings.cleared);

                state.configured.delete(provider);
                delete state.models[provider];
                delete state.providerModels[provider];
                this.markProviderDisconnected(provider);
                this.removeProviderOption(provider);

                if (response.data && response.data.default_provider) {
                    state.defaultProvider = response.data.default_provider;
                    $('#default_provider').val(state.defaultProvider);
                    $('#ai-core-test-provider').val(state.defaultProvider);
                    this.onTestProviderChange(state.defaultProvider, { initialise: true });
                }
            });
        },

        populateProviderModels: function(provider, models, options = {}) {
            const $select = $('.ai-core-provider-model[data-provider="' + provider + '"]');
            if (!$select.length) {
                return;
            }

            if (!Array.isArray(models) || !models.length) {
                $select.html('<option value="">' + aiCoreAdmin.strings.noModels + '</option>').prop('disabled', true);
                return;
            }

            $select.prop('disabled', false).empty();
            $select.append($('<option></option>').val('').text(aiCoreAdmin.strings.placeholderSelectModel));

            models.forEach((model) => {
                const meta = this.getModelMeta(provider, model);
                const text = meta && meta.display_name ? meta.display_name + ' (' + model + ')' : model;
                $select.append($('<option></option>').val(model).text(text));
            });

            const desired = options.selected || (state.providerModels[provider] && state.providerModels[provider].selected) || '';
            if (desired && models.indexOf(desired) !== -1) {
                $select.val(desired);
            } else {
                const fallback = models[0];
                $select.val(fallback);
                state.providerModels[provider] = state.providerModels[provider] || {};
                state.providerModels[provider].selected = fallback;
            }

            const activeModel = $select.val();
            this.renderProviderParameters(provider, activeModel);

            const currentTestProvider = $('#ai-core-test-provider').val();
            if (currentTestProvider === provider) {
                this.onTestProviderChange(provider, { initialise: true });
            }
        },

        markProviderConnected: function(provider) {
            const $card = $('.ai-core-provider-card[data-provider="' + provider + '"]');
            $card.attr('data-has-key', '1').addClass('is-active');
            $card.find('.ai-core-provider-status').text(aiCoreAdmin.strings.connected).removeClass('is-inactive').addClass('is-active');
            $card.find('.ai-core-provider-model').prop('disabled', false);
            $card.find('.ai-core-provider-refresh').prop('disabled', false);
            this.ensureProviderOptionExists(provider);
            const activeModel = state.providerModels[provider] && state.providerModels[provider].selected ? state.providerModels[provider].selected : null;
            this.renderProviderParameters(provider, activeModel);
        },

        markProviderDisconnected: function(provider) {
            const $card = $('.ai-core-provider-card[data-provider="' + provider + '"]');
            $card.attr('data-has-key', '0').removeClass('is-active');
            $card.find('.ai-core-provider-status').text(aiCoreAdmin.strings.awaiting).removeClass('is-active').addClass('is-inactive');
            $card.find('.ai-core-provider-model').prop('disabled', true).html('<option value="">' + aiCoreAdmin.strings.addKeyFirst + '</option>');
            $card.find('.ai-core-provider-refresh').prop('disabled', true);
            $card.find('.ai-core-provider-params').html('<p class="description">' + aiCoreAdmin.strings.addKeyFirst + '</p>');
            delete state.providerOptions[provider];
            delete state.providerModels[provider];
        },

        ensureProviderOptionExists: function(provider) {
            const label = aiCoreAdmin.providers && aiCoreAdmin.providers.labels && aiCoreAdmin.providers.labels[provider] ? aiCoreAdmin.providers.labels[provider] : provider;
            const $defaultSelect = $('#default_provider');
            const $testSelect = $('#ai-core-test-provider');

            if (!$defaultSelect.find('option[value="' + provider + '"]').length) {
                $defaultSelect.append($('<option></option>').val(provider).text(label));
            }

            if (!$testSelect.find('option[value="' + provider + '"]').length) {
                $testSelect.append($('<option></option>').val(provider).text(label));
            }
        },

        removeProviderOption: function(provider) {
            $('#default_provider').find('option[value="' + provider + '"]').remove();
            $('#ai-core-test-provider').find('option[value="' + provider + '"]').remove();
        },

        updateModelMeta: function(provider, meta) {
            if (!meta) {
                return;
            }
            state.modelMeta[provider] = state.modelMeta[provider] || {};
            Object.keys(meta).forEach((model) => {
                state.modelMeta[provider][model] = meta[model];
            });
        },

        getModelMeta: function(provider, model) {
            if (!provider || !model) {
                return null;
            }
            const providerMeta = state.modelMeta[provider] || {};
            return providerMeta[model] || null;
        },

        renderProviderParameters: function(provider, model) {
            const $container = $('.ai-core-provider-params[data-provider="' + provider + '"]');
            if (!$container.length) {
                return;
            }

            $container.empty();

            if (!model) {
                $container.html('<p class="description">' + aiCoreAdmin.strings.selectModelFirst + '</p>');
                return;
            }

            const meta = this.getModelMeta(provider, model);
            const parameters = meta && meta.parameters ? meta.parameters : {};

            state.providerOptions[provider] = state.providerOptions[provider] || {};

            const keys = Object.keys(parameters);
            if (!keys.length) {
                $container.html('<p class="description">' + aiCoreAdmin.strings.noTuningParameters + '</p>');
                state.providerOptions[provider] = {};
                return;
            }

            Object.keys(state.providerOptions[provider]).forEach((existingKey) => {
                if (keys.indexOf(existingKey) === -1) {
                    delete state.providerOptions[provider][existingKey];
                }
            });

            keys.forEach((paramKey) => {
                const definition = parameters[paramKey];
                if (typeof definition !== 'object') {
                    return;
                }

                if (state.providerOptions[provider][paramKey] === undefined && definition.default !== undefined) {
                    state.providerOptions[provider][paramKey] = definition.default;
                }

                const $control = this.createParameterControl(provider, paramKey, definition, state.providerOptions[provider][paramKey]);
                $container.append($control);
            });
        },

        createParameterControl: function(provider, key, definition, value) {
            const $wrapper = $('<div/>', { 'class': 'ai-core-param-control' });
            const labelText = definition.label || key;
            const inputName = 'ai_core_settings[provider_options][' + provider + '][' + key + ']';

            $wrapper.append($('<label/>').attr('for', provider + '-' + key).text(labelText));

            let $input;
            if (definition.type === 'select') {
                $input = $('<select/>', {
                    id: provider + '-' + key,
                    name: inputName,
                    class: 'ai-core-param-input',
                    'data-provider': provider,
                    'data-param': key,
                });

                const options = definition.options || [];
                options.forEach((opt) => {
                    const optionValue = opt.value !== undefined ? opt.value : opt;
                    const optionLabel = opt.label !== undefined ? opt.label : opt;
                    const $option = $('<option></option>').val(optionValue).text(optionLabel);
                    $input.append($option);
                });

                if (value !== undefined) {
                    $input.val(value);
                }
            } else {
                $input = $('<input/>', {
                    id: provider + '-' + key,
                    name: inputName,
                    type: 'number',
                    class: 'small-text ai-core-param-input',
                    'data-provider': provider,
                    'data-param': key,
                });

                if (definition.min !== undefined) {
                    $input.attr('min', definition.min);
                }
                if (definition.max !== undefined) {
                    $input.attr('max', definition.max);
                }
                if (definition.step !== undefined) {
                    $input.attr('step', definition.step);
                }

                if (value !== undefined) {
                    $input.val(value);
                } else if (definition.default !== undefined) {
                    $input.val(definition.default);
                }
            }

            $wrapper.append($input);

            if (definition.help) {
                $wrapper.append($('<p/>', {
                    'class': 'description'
                }).text(definition.help));
            }

            return $wrapper;
        },

        onParameterChange: function(event) {
            const $input = $(event.currentTarget);
            const provider = $input.data('provider');
            const param = $input.data('param');
            if (!provider || !param) {
                return;
            }

            state.providerOptions[provider] = state.providerOptions[provider] || {};
            state.providerOptions[provider][param] = $input.val();
        },

        testApiKey: function(event) {
            event.preventDefault();

            const $button = $(event.currentTarget);
            const provider = $button.data('provider');
            const $input = $('#' + provider + '_api_key');
            const $saved = $('#' + provider + '_api_key_saved');
            const $status = $('#' + provider + '-status');

            let apiKey = $input.val();
            if (!apiKey && $saved.length) {
                apiKey = $saved.val();
            }

            if (!apiKey) {
                this.showStatus($status, 'error', aiCoreAdmin.strings.missingKey);
                return;
            }

            $button.prop('disabled', true).text(aiCoreAdmin.strings.testing);
            this.showStatus($status, 'notice', aiCoreAdmin.strings.testing);

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
                if (response && response.success) {
                    this.showStatus($status, 'success', aiCoreAdmin.strings.success + ': ' + response.data.message);
                } else {
                    const message = response && response.data && response.data.message ? response.data.message : aiCoreAdmin.strings.error;
                    this.showStatus($status, 'error', aiCoreAdmin.strings.error + ': ' + message);
                }
            }).fail((xhr, status, error) => {
                this.showStatus($status, 'error', aiCoreAdmin.strings.error + ': ' + (error || status));
            }).always(() => {
                $button.prop('disabled', false).text(aiCoreAdmin.strings.testKey);
            });
        },

        onTestProviderChange: function(provider, options = {}) {
            if (!provider) {
                $('#ai-core-test-model').html('<option value="">' + aiCoreAdmin.strings.testSelectProvider + '</option>').prop('disabled', true);
                return;
            }

            $('#ai-core-test-provider').val(provider);

            const models = state.models[provider];
            const $modelSelect = $('#ai-core-test-model');

            if (!Array.isArray(models) || !models.length) {
                $modelSelect.html('<option value="">' + aiCoreAdmin.strings.loadingModels + '</option>').prop('disabled', true);
                this.fetchModels(provider, { force: false, showStatus: false });
                return;
            }

            $modelSelect.empty().prop('disabled', false);
            $modelSelect.append($('<option></option>').val('').text(aiCoreAdmin.strings.placeholderSelectModel));
            models.forEach((model) => {
                const meta = this.getModelMeta(provider, model);
                const text = meta && meta.display_name ? meta.display_name + ' (' + model + ')' : model;
                $modelSelect.append($('<option></option>').val(model).text(text));
            });

            const desired = (state.providerModels[provider] && state.providerModels[provider].selected) || models[0];
            if (desired) {
                $modelSelect.val(desired);
            }

            if (options.initialise) {
                const $providerCardSelect = $('.ai-core-provider-model[data-provider="' + provider + '"]');
                if ($providerCardSelect.length && $providerCardSelect.val()) {
                    $modelSelect.val($providerCardSelect.val());
                }
            }
        },

        showStatus: function($element, type, message) {
            const classes = {
                success: 'success',
                error: 'error',
                notice: 'notice'
            };

            const iconMap = {
                success: 'yes-alt',
                error: 'dismiss',
                notice: 'info'
            };

            $element.removeClass('success error notice');
            $element.addClass(classes[type] || 'notice');
            $element.html('<span class="dashicons dashicons-' + (iconMap[type] || 'info') + '"></span> ' + message);

            clearTimeout($element.data('hideTimeout'));
            const timeout = setTimeout(() => {
                $element.fadeOut(200, function() {
                    $(this).empty().show().removeClass('success error notice');
                });
            }, 4000);
            $element.data('hideTimeout', timeout);
        },

        /* Existing prompt library + test prompt logic preserved with minor tweaks */
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
                if (response && response.success) {
                    $select.empty().append('<option value="">-- Select a prompt --</option>');
                    response.data.prompts.forEach((prompt) => {
                        $select.append('<option value="' + prompt.id + '">' + this.escapeHtml(prompt.title) + '</option>');
                    });
                }
            });
        },

        loadPromptContent: function(event) {
            const promptId = $(event.currentTarget).val();
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
                if (response && response.success) {
                    const prompt = response.data.prompts.find((p) => p.id == promptId);
                    if (prompt) {
                        $('#ai-core-test-prompt-content').val(prompt.content);
                        if (prompt.provider) {
                            $('#ai-core-test-provider').val(prompt.provider);
                            this.onTestProviderChange(prompt.provider, { initialise: true });
                        }
                        if (prompt.type) {
                            $('#ai-core-test-type').val(prompt.type);
                        }
                    }
                }
            });
        },

        runTestPrompt: function(event) {
            event.preventDefault();

            const content = $('#ai-core-test-prompt-content').val();
            const provider = $('#ai-core-test-provider').val();
            const model = $('#ai-core-test-model').val();
            const type = $('#ai-core-test-type').val();
            const $result = $('#ai-core-test-prompt-result');

            if (!content) {
                alert(aiCoreAdmin.strings.promptRequired);
                return;
            }

            if (!provider) {
                alert(aiCoreAdmin.strings.providerRequired);
                return;
            }

            if (!model && type === 'text') {
                alert(aiCoreAdmin.strings.modelRequired);
                return;
            }

            $result.show().html('<div class="loading"><span class="ai-core-spinner"></span> ' + aiCoreAdmin.strings.runningPrompt + '</div>');

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
                if (response && response.success) {
                    if (response.data.type === 'image') {
                        $result.html('<img src="' + response.data.result + '" alt="Generated image" style="max-width:100%;height:auto;" />');
                    } else {
                        $result.html('<pre style="white-space:pre-wrap;word-break:break-word;">' + this.escapeHtml(response.data.result) + '</pre>');
                    }
                } else {
                    const message = response && response.data && response.data.message ? response.data.message : aiCoreAdmin.strings.error;
                    $result.html('<div class="error" style="color:#d63638;padding:10px;background:#fcf0f1;border:1px solid #d63638;border-radius:4px;">Error: ' + this.escapeHtml(message) + '</div>');
                }
            }).fail((xhr, status, error) => {
                $result.html('<div class="error" style="color:#d63638;padding:10px;background:#fcf0f1;border:1px solid #d63638;border-radius:4px;">Error: ' + this.escapeHtml(error || status) + '</div>');
            });
        },

        updateTypeDropdown: function() {
            const provider = $('#ai-core-test-provider').val();
            const $typeSelect = $('#ai-core-test-type');
            const $imageOption = $typeSelect.find('option[value="image"]');

            if (!provider) {
                // No provider selected, disable image option
                $imageOption.prop('disabled', true);
                if ($typeSelect.val() === 'image') {
                    $typeSelect.val('text');
                }
                return;
            }

            // Only OpenAI supports image generation currently
            const supportsImageGeneration = provider === 'openai';

            $imageOption.prop('disabled', !supportsImageGeneration);

            // If image is selected but not supported, switch to text
            if ($typeSelect.val() === 'image' && !supportsImageGeneration) {
                $typeSelect.val('text');
            }

            // Add visual indicator for disabled option
            if (!supportsImageGeneration) {
                $imageOption.text('Image Generation (Not supported by ' + provider + ')');
            } else {
                $imageOption.text('Image Generation');
            }
        },

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
        Admin.init();
    });

})(jQuery);
