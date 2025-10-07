/**
 * AI-Imagen Admin JavaScript
 * 
 * Main admin interface functionality
 * 
 * @package AI_Imagen
 * @version 0.3.8
 */

(function($) {
    'use strict';
    
    var AIImagen = {
        
        // Current state
        state: {
            workflow: 'just-start',
            useCase: '',
            role: '',
            style: '',
            provider: '',
            model: '',
            currentImageUrl: '',
            currentMetadata: {}
        },
        
        /**
         * Initialize
         */
        init: function() {
            this.bindEvents();
            this.loadProviders();
        },
        
        /**
         * Bind event handlers
         */
        bindEvents: function() {
            var self = this;
            
            // Workflow tabs
            $('.workflow-tab').on('click', function() {
                var workflow = $(this).data('workflow');
                self.switchWorkflow(workflow);
            });
            
            // Card selection
            $('.ai-imagen-card').on('click', function() {
                var $card = $(this);
                var value = $card.data('value');
                var panel = $card.closest('.workflow-panel').attr('id');
                
                // Toggle selection
                $card.siblings().removeClass('selected');
                $card.addClass('selected');
                
                // Update state
                if (panel === 'panel-use-case') {
                    self.state.useCase = value;
                } else if (panel === 'panel-role') {
                    self.state.role = value;
                } else if (panel === 'panel-style') {
                    self.state.style = value;
                }
            });
            
            // Quick idea buttons
            $('.quick-idea-btn').on('click', function() {
                var idea = $(this).text();
                $('#ai-imagen-prompt').val(idea);
            });
            
            // Provider change
            $('#ai-imagen-provider').on('change', function() {
                self.state.provider = $(this).val();
                self.loadModels(self.state.provider);
            });
            
            // Model change
            $('#ai-imagen-model').on('change', function() {
                self.state.model = $(this).val();
            });
            
            // Enhance prompt
            $('#ai-imagen-enhance-prompt').on('click', function() {
                self.enhancePrompt();
            });
            
            // Generate image
            $('#ai-imagen-generate-btn').on('click', function() {
                self.generateImage();
            });
            
            // Download image
            $('#ai-imagen-download-btn').on('click', function() {
                self.downloadImage();
            });
            
            // Save to library
            $('#ai-imagen-save-library-btn').on('click', function() {
                self.saveToLibrary();
            });
            
            // Regenerate
            $('#ai-imagen-regenerate-btn').on('click', function() {
                self.generateImage();
            });
            
            // History delete
            $('.history-delete-btn').on('click', function() {
                var attachmentId = $(this).data('id');
                self.deleteImage(attachmentId, $(this).closest('.history-item'));
            });
        },
        
        /**
         * Switch workflow
         */
        switchWorkflow: function(workflow) {
            this.state.workflow = workflow;
            
            // Update tabs
            $('.workflow-tab').removeClass('active');
            $('.workflow-tab[data-workflow="' + workflow + '"]').addClass('active');
            
            // Update panels
            $('.workflow-panel').removeClass('active');
            $('#panel-' + workflow).addClass('active');
        },
        
        /**
         * Load available providers
         */
        loadProviders: function() {
            var self = this;
            
            $.ajax({
                url: aiImagenData.ajax_url,
                type: 'POST',
                data: {
                    action: 'ai_imagen_get_providers',
                    nonce: aiImagenData.nonce
                },
                success: function(response) {
                    if (response.success && response.data.providers.length > 0) {
                        self.state.provider = response.data.providers[0];
                        $('#ai-imagen-provider').val(self.state.provider);
                        self.loadModels(self.state.provider);
                    }
                }
            });
        },
        
        /**
         * Load provider models
         */
        loadModels: function(provider) {
            var self = this;
            
            $('#ai-imagen-model').html('<option value="">Loading...</option>');
            
            $.ajax({
                url: aiImagenData.ajax_url,
                type: 'POST',
                data: {
                    action: 'ai_imagen_get_models',
                    provider: provider,
                    nonce: aiImagenData.nonce
                },
                success: function(response) {
                    if (response.success && response.data.models) {
                        var $select = $('#ai-imagen-model');
                        $select.empty();
                        
                        $.each(response.data.models, function(index, model) {
                            $select.append($('<option>', {
                                value: model,
                                text: model
                            }));
                        });
                        
                        self.state.model = response.data.models[0];
                    }
                }
            });
        },
        
        /**
         * Enhance prompt with AI
         */
        enhancePrompt: function() {
            var prompt = $('#ai-imagen-prompt').val().trim();
            
            if (!prompt) {
                alert('Please enter a prompt first.');
                return;
            }
            
            var $btn = $('#ai-imagen-enhance-prompt');
            $btn.prop('disabled', true).text('Enhancing...');
            
            $.ajax({
                url: aiImagenData.ajax_url,
                type: 'POST',
                data: {
                    action: 'ai_imagen_enhance_prompt',
                    prompt: prompt,
                    nonce: aiImagenData.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $('#ai-imagen-prompt').val(response.data.enhanced_prompt);
                    } else {
                        alert(response.data.message || 'Failed to enhance prompt.');
                    }
                },
                error: function() {
                    alert('An error occurred while enhancing the prompt.');
                },
                complete: function() {
                    $btn.prop('disabled', false).html('<span class="dashicons dashicons-lightbulb"></span> Enhance with AI');
                }
            });
        },
        
        /**
         * Generate image
         */
        generateImage: function() {
            var self = this;
            var prompt = $('#ai-imagen-prompt').val().trim();
            
            if (!prompt) {
                alert('Please enter a prompt.');
                return;
            }
            
            // Show loading state
            var $btn = $('#ai-imagen-generate-btn');
            $btn.prop('disabled', true).text('Generating...');
            $('#ai-imagen-preview-area').addClass('ai-imagen-loading');
            
            // Get scene builder data if available
            var sceneElements = [];
            var sceneDescription = '';

            if (window.AIImagenSceneBuilder && typeof window.AIImagenSceneBuilder.getSceneData === 'function') {
                sceneElements = window.AIImagenSceneBuilder.getSceneData();
                sceneDescription = window.AIImagenSceneBuilder.generateSceneDescription();
            }

            // Append scene description to prompt if elements exist
            var finalPrompt = prompt;
            if (sceneDescription) {
                finalPrompt += '. ' + sceneDescription;
            }

            // Prepare data
            var data = {
                action: 'ai_imagen_generate',
                nonce: aiImagenData.nonce,
                prompt: finalPrompt,
                additional_details: $('#ai-imagen-details').val(),
                provider: this.state.provider,
                model: this.state.model,
                use_case: this.state.useCase,
                role: this.state.role,
                style: this.state.style,
                quality: $('#ai-imagen-quality').val(),
                format: 'png',
                aspect_ratio: $('#ai-imagen-aspect-ratio').val(),
                background: 'opaque',
                scene_elements: JSON.stringify(sceneElements)
            };
            
            $.ajax({
                url: aiImagenData.ajax_url,
                type: 'POST',
                data: data,
                success: function(response) {
                    if (response.success) {
                        self.state.currentImageUrl = response.data.image_url;
                        self.state.currentMetadata = {
                            prompt: prompt,
                            provider: data.provider,
                            model: data.model,
                            use_case: data.use_case,
                            role: data.role,
                            style: data.style
                        };
                        
                        // Display image
                        $('#ai-imagen-preview-area').html('<img src="' + response.data.image_url + '" alt="Generated image">');
                        $('#ai-imagen-preview-actions').show();
                        
                        // Show success message
                        self.showNotice('success', response.data.message);
                    } else {
                        alert(response.data.message || 'Failed to generate image.');
                    }
                },
                error: function() {
                    alert('An error occurred while generating the image.');
                },
                complete: function() {
                    $btn.prop('disabled', false).html('<span class="dashicons dashicons-images-alt2"></span> Generate Image');
                    $('#ai-imagen-preview-area').removeClass('ai-imagen-loading');
                }
            });
        },
        
        /**
         * Download image
         */
        downloadImage: function() {
            if (this.state.currentImageUrl) {
                window.open(this.state.currentImageUrl, '_blank');
            }
        },
        
        /**
         * Save to library
         */
        saveToLibrary: function() {
            var self = this;
            
            if (!this.state.currentImageUrl) {
                return;
            }
            
            var $btn = $('#ai-imagen-save-library-btn');
            $btn.prop('disabled', true).text('Saving...');
            
            $.ajax({
                url: aiImagenData.ajax_url,
                type: 'POST',
                data: {
                    action: 'ai_imagen_save_to_library',
                    image_url: this.state.currentImageUrl,
                    metadata: JSON.stringify(this.state.currentMetadata),
                    nonce: aiImagenData.nonce
                },
                success: function(response) {
                    if (response.success) {
                        self.showNotice('success', response.data.message);
                    } else {
                        alert(response.data.message || 'Failed to save image.');
                    }
                },
                error: function() {
                    alert('An error occurred while saving the image.');
                },
                complete: function() {
                    $btn.prop('disabled', false).html('<span class="dashicons dashicons-admin-media"></span> Save to Library');
                }
            });
        },
        
        /**
         * Delete image
         */
        deleteImage: function(attachmentId, $item) {
            if (!confirm('Are you sure you want to delete this image?')) {
                return;
            }
            
            $.ajax({
                url: aiImagenData.ajax_url,
                type: 'POST',
                data: {
                    action: 'ai_imagen_delete_image',
                    attachment_id: attachmentId,
                    nonce: aiImagenData.nonce
                },
                success: function(response) {
                    if (response.success) {
                        $item.fadeOut(300, function() {
                            $(this).remove();
                        });
                    } else {
                        alert(response.data.message || 'Failed to delete image.');
                    }
                },
                error: function() {
                    alert('An error occurred while deleting the image.');
                }
            });
        },
        
        /**
         * Show admin notice
         */
        showNotice: function(type, message) {
            var $notice = $('<div class="notice notice-' + type + ' is-dismissible"><p>' + message + '</p></div>');
            $('.wrap h1').after($notice);
            
            setTimeout(function() {
                $notice.fadeOut(300, function() {
                    $(this).remove();
                });
            }, 3000);
        }
    };
    
    // Initialize on document ready
    $(document).ready(function() {
        AIImagen.init();
    });
    
})(jQuery);

