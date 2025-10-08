/**
 * AI-Imagen Admin JavaScript
 * 
 * Main admin interface functionality
 * 
 * @package AI_Imagen
 * @version 0.4.9
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

            // Prompt preview toggle
            $('#ai-imagen-prompt-preview-toggle').on('click', function() {
                self.togglePromptPreview();
            });

            // Update prompt preview on input changes
            $('#ai-imagen-prompt, #ai-imagen-details').on('input', function() {
                self.updatePromptPreview();
            });

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

                // Load related prompts from library
                self.loadRelatedPrompts(value);

                // Update prompt preview
                self.updatePromptPreview();
            });

            // Load from library button
            $('#ai-imagen-load-from-library').on('click', function() {
                self.showPromptLibraryModal();
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
         * Toggle prompt preview
         */
        togglePromptPreview: function() {
            var $toggle = $('#ai-imagen-prompt-preview-toggle');
            var $content = $('#ai-imagen-prompt-preview-content');

            if ($content.is(':visible')) {
                $content.slideUp(300);
                $toggle.removeClass('active');
            } else {
                $content.slideDown(300);
                $toggle.addClass('active');
                this.updatePromptPreview();
            }
        },

        /**
         * Update prompt preview
         */
        updatePromptPreview: function() {
            var prompt = $('#ai-imagen-prompt').val().trim();
            var details = $('#ai-imagen-details').val().trim();
            var parts = [];

            // Add main prompt
            if (prompt) {
                parts.push(prompt);
            }

            // Add additional details
            if (details) {
                parts.push(details);
            }

            // Add workflow selections
            if (this.state.useCase) {
                parts.push('Use case: ' + this.state.useCase.replace(/-/g, ' '));
            }
            if (this.state.role) {
                parts.push('Role: ' + this.state.role.replace(/-/g, ' '));
            }
            if (this.state.style) {
                parts.push('Style: ' + this.state.style.replace(/-/g, ' '));
            }

            // Add scene builder description if available
            if (window.AIImagenSceneBuilder && typeof window.AIImagenSceneBuilder.generateSceneDescription === 'function') {
                var sceneDesc = window.AIImagenSceneBuilder.generateSceneDescription();
                if (sceneDesc) {
                    parts.push(sceneDesc);
                }
            }

            // Update preview
            var $preview = $('#ai-imagen-prompt-preview-text');
            if (parts.length > 0) {
                $preview.text(parts.join('. '));
            } else {
                $preview.html('<em>Your final prompt will appear here as you make selections...</em>');
            }
        },

        /**
         * Load related prompts from AI-Core Prompt Library
         */
        loadRelatedPrompts: function(category) {
            var self = this;

            // Map category to group name
            var groupName = this.getCategoryGroupName(category);

            if (!groupName) {
                return;
            }

            // Use aiCoreAdmin if available, fallback to aiImagenData
            var ajaxUrl = (typeof aiCoreAdmin !== 'undefined') ? aiCoreAdmin.ajaxUrl : aiImagenData.ajax_url;
            var nonce = (typeof aiCoreAdmin !== 'undefined') ? aiCoreAdmin.nonce : aiImagenData.nonce;

            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ai_core_get_prompts',
                    search: groupName,
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success && response.data.prompts && response.data.prompts.length > 0) {
                        // Filter prompts by group name
                        var filteredPrompts = response.data.prompts.filter(function(prompt) {
                            return prompt.group_name && prompt.group_name.toLowerCase().indexOf(groupName.toLowerCase()) !== -1;
                        });

                        if (filteredPrompts.length > 0) {
                            self.showPromptSuggestions(filteredPrompts);
                        }
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Failed to load prompts:', error);
                }
            });
        },

        /**
         * Get group name for category
         */
        getCategoryGroupName: function(category) {
            // Map workflow categories to search terms for AI-Core Prompt Library
            // Using simpler search terms that will match group names
            var mapping = {
                // Use Cases
                'marketing-ads': 'marketing',
                'social-media': 'social',
                'product-photography': 'product',
                'website-design': 'website',
                'publishing': 'publishing',
                'presentations': 'presentation',

                // Roles
                'marketing-manager': 'marketing',
                'social-media-manager': 'social',
                'small-business-owner': 'business',
                'graphic-designer': 'design',
                'content-publisher': 'content',
                'developer': 'developer',
                'educator': 'education',
                'event-planner': 'event',

                // Styles
                'photorealistic': 'photorealistic',
                'flat-minimalist': 'minimalist',
                'cartoon-anime': 'cartoon',
                'digital-painting': 'painting',
                'abstract-modern': 'abstract',
                'vintage-retro': 'vintage'
            };

            return mapping[category] || category;
        },

        /**
         * Show prompt suggestions
         */
        showPromptSuggestions: function(prompts) {
            var $panel = $('.workflow-panel.active');
            var $existing = $panel.find('.prompt-suggestions');

            if ($existing.length) {
                $existing.remove();
            }

            if (prompts.length === 0) {
                return;
            }

            var html = '<div class="prompt-suggestions">';
            html += '<h4>💡 Suggested Prompts</h4>';
            html += '<div class="prompt-suggestions-list">';

            prompts.slice(0, 5).forEach(function(prompt) {
                // Use 'content' field which is the actual prompt text
                var promptContent = prompt.content || prompt.prompt || '';
                var promptTitle = prompt.title || prompt.name || 'Untitled';

                html += '<button type="button" class="prompt-suggestion-btn" data-prompt="' +
                        promptContent.replace(/"/g, '&quot;').replace(/'/g, '&#39;') + '" title="' + promptTitle + '">' +
                        '<span class="dashicons dashicons-lightbulb"></span>' +
                        '<span class="prompt-text">' + promptContent.substring(0, 80) +
                        (promptContent.length > 80 ? '...' : '') + '</span>' +
                        '</button>';
            });

            html += '</div></div>';

            $panel.find('.description').after(html);

            // Bind click events
            $('.prompt-suggestion-btn').on('click', function() {
                var prompt = $(this).data('prompt');
                $('#ai-imagen-prompt').val(prompt);
                AIImagen.updatePromptPreview();
            });
        },

        /**
         * Show prompt library modal
         */
        showPromptLibraryModal: function() {
            // Create modal if it doesn't exist
            if ($('#ai-imagen-prompt-library-modal').length === 0) {
                this.createPromptLibraryModal();
            }

            // Load prompts
            this.loadPromptLibrary();

            // Show modal
            $('#ai-imagen-prompt-library-modal').fadeIn(300);
        },

        /**
         * Create prompt library modal
         */
        createPromptLibraryModal: function() {
            var html = `
                <div id="ai-imagen-prompt-library-modal" class="ai-imagen-modal" style="display: none;">
                    <div class="modal-overlay"></div>
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2>Prompt Library</h2>
                            <button type="button" class="modal-close">&times;</button>
                        </div>
                        <div class="modal-body">
                            <div class="prompt-library-search">
                                <input type="text" id="prompt-library-search" placeholder="Search prompts..." />
                            </div>
                            <div class="prompt-library-groups" id="prompt-library-groups">
                                <p class="loading">Loading prompts...</p>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            $('body').append(html);

            // Bind close events
            $('.modal-close, .modal-overlay').on('click', function() {
                $('#ai-imagen-prompt-library-modal').fadeOut(300);
            });
        },

        /**
         * Load prompt library
         */
        loadPromptLibrary: function() {
            var self = this;

            // Use aiCoreAdmin if available, fallback to aiImagenData
            var ajaxUrl = (typeof aiCoreAdmin !== 'undefined') ? aiCoreAdmin.ajaxUrl : aiImagenData.ajax_url;
            var nonce = (typeof aiCoreAdmin !== 'undefined') ? aiCoreAdmin.nonce : aiImagenData.nonce;

            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ai_core_get_prompts',
                    nonce: nonce
                },
                success: function(response) {
                    if (response.success && response.data.prompts) {
                        self.renderPromptLibrary(response.data.prompts);
                    } else {
                        $('#prompt-library-groups').html('<p class="no-prompts">No prompts found in library. <a href="' + (typeof aiCoreAdmin !== 'undefined' ? aiCoreAdmin.promptLibraryUrl : '') + '">Create prompts in the Prompt Library</a></p>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Failed to load prompt library:', error);
                    $('#prompt-library-groups').html('<p class="no-prompts error">Failed to load prompts. Please try again.</p>');
                }
            });
        },

        /**
         * Render prompt library
         */
        renderPromptLibrary: function(prompts) {
            var $container = $('#prompt-library-groups');
            $container.empty();

            if (prompts.length === 0) {
                $container.html('<p class="no-prompts">No prompts found in library. <a href="' + (typeof aiCoreAdmin !== 'undefined' ? aiCoreAdmin.promptLibraryUrl : '') + '">Create prompts in the Prompt Library</a></p>');
                return;
            }

            // Group prompts by group
            var groups = {};
            prompts.forEach(function(prompt) {
                var groupName = prompt.group_name || 'Uncategorised';
                if (!groups[groupName]) {
                    groups[groupName] = [];
                }
                groups[groupName].push(prompt);
            });

            // Render groups
            Object.keys(groups).sort().forEach(function(groupName) {
                var html = '<div class="prompt-library-group">';
                html += '<h3>' + groupName + ' <span class="group-count">(' + groups[groupName].length + ')</span></h3>';
                html += '<div class="prompt-library-items">';

                groups[groupName].forEach(function(prompt) {
                    // Use 'content' field which is the actual prompt text
                    var promptContent = prompt.content || prompt.prompt || '';
                    var promptTitle = prompt.title || prompt.name || 'Untitled';

                    html += '<button type="button" class="prompt-library-item" data-prompt="' +
                            promptContent.replace(/"/g, '&quot;').replace(/'/g, '&#39;') + '">' +
                            '<span class="prompt-name">' + promptTitle + '</span>' +
                            '<span class="prompt-preview">' + promptContent.substring(0, 100) +
                            (promptContent.length > 100 ? '...' : '') + '</span>' +
                            '</button>';
                });

                html += '</div></div>';
                $container.append(html);
            });

            // Bind click events
            $('.prompt-library-item').on('click', function() {
                var prompt = $(this).data('prompt');
                $('#ai-imagen-prompt').val(prompt);
                $('#ai-imagen-prompt-library-modal').fadeOut(300);
                AIImagen.updatePromptPreview();
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
            $btn.prop('disabled', true).html('<span class="dashicons dashicons-update"></span> Generating...');

            // Hide placeholder and show loading animation
            $('.preview-placeholder').hide();
            $('#ai-imagen-preview-loading').show();
            $('#ai-imagen-preview-actions').hide();

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

                        // Hide loading animation
                        $('#ai-imagen-preview-loading').hide();

                        // Display image
                        $('#ai-imagen-preview-area').html('<img src="' + response.data.image_url + '" alt="Generated image">');
                        $('#ai-imagen-preview-actions').show();

                        // Add to history
                        self.addToHistory(response.data.image_url, prompt);

                        // Show history carousel
                        $('#ai-imagen-preview-history').show();
                        
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
            if (!this.state.currentImageUrl) {
                alert('No image to download.');
                return;
            }

            // Create a temporary link element and trigger download
            var link = document.createElement('a');
            link.href = this.state.currentImageUrl;
            link.download = 'ai-imagen-' + Date.now() + '.png';
            link.target = '_blank';

            // For cross-origin images, we need to fetch and create a blob
            fetch(this.state.currentImageUrl)
                .then(response => response.blob())
                .then(blob => {
                    var url = window.URL.createObjectURL(blob);
                    link.href = url;
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    window.URL.revokeObjectURL(url);
                })
                .catch(error => {
                    // Fallback: just open in new tab if fetch fails
                    console.error('Download error:', error);
                    window.open(this.state.currentImageUrl, '_blank');
                });
        },
        
        /**
         * Save to library
         */
        saveToLibrary: function() {
            var self = this;

            if (!this.state.currentImageUrl) {
                alert('No image to save. Please generate an image first.');
                return;
            }

            var $btn = $('#ai-imagen-save-library-btn');
            $btn.prop('disabled', true).text('Saving...');

            // Debug: log the image URL being sent
            console.log('Saving image URL:', this.state.currentImageUrl);
            console.log('Metadata:', this.state.currentMetadata);

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
                        console.error('Save error:', response.data);
                        alert(response.data.message || 'Failed to save image.');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error:', status, error);
                    alert('An error occurred while saving the image: ' + error);
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
         * Add image to history
         */
        addToHistory: function(imageUrl, prompt) {
            // Get existing history from localStorage
            var history = JSON.parse(localStorage.getItem('ai_imagen_history') || '[]');

            // Add new image to beginning
            history.unshift({
                url: imageUrl,
                prompt: prompt,
                timestamp: Date.now()
            });

            // Keep only last 10 images
            if (history.length > 10) {
                history = history.slice(0, 10);
            }

            // Save to localStorage
            localStorage.setItem('ai_imagen_history', JSON.stringify(history));

            // Update carousel
            this.updateHistoryCarousel();
        },

        /**
         * Update history carousel
         */
        updateHistoryCarousel: function() {
            var history = JSON.parse(localStorage.getItem('ai_imagen_history') || '[]');
            var $carousel = $('#ai-imagen-history-carousel');

            $carousel.empty();

            if (history.length === 0) {
                $('#ai-imagen-preview-history').hide();
                return;
            }

            var self = this;
            history.forEach(function(item, index) {
                var $thumb = $('<div class="history-thumbnail">')
                    .attr('data-index', index)
                    .attr('title', item.prompt)
                    .html('<img src="' + item.url + '" alt="' + item.prompt + '">' +
                          '<button class="history-thumbnail-remove" data-index="' + index + '">×</button>');

                if (index === 0) {
                    $thumb.addClass('active');
                }

                $carousel.append($thumb);
            });

            // Bind click events
            $('.history-thumbnail').on('click', function(e) {
                if (!$(e.target).hasClass('history-thumbnail-remove')) {
                    var index = $(this).data('index');
                    self.loadHistoryImage(index);
                }
            });

            $('.history-thumbnail-remove').on('click', function(e) {
                e.stopPropagation();
                var index = $(this).data('index');
                self.removeFromHistory(index);
            });
        },

        /**
         * Load image from history
         */
        loadHistoryImage: function(index) {
            var history = JSON.parse(localStorage.getItem('ai_imagen_history') || '[]');

            if (history[index]) {
                var item = history[index];
                this.state.currentImageUrl = item.url;

                // Display image
                $('#ai-imagen-preview-area').html('<img src="' + item.url + '" alt="' + item.prompt + '">');
                $('#ai-imagen-preview-actions').show();

                // Update active thumbnail
                $('.history-thumbnail').removeClass('active');
                $('.history-thumbnail[data-index="' + index + '"]').addClass('active');
            }
        },

        /**
         * Remove image from history
         */
        removeFromHistory: function(index) {
            var history = JSON.parse(localStorage.getItem('ai_imagen_history') || '[]');
            history.splice(index, 1);
            localStorage.setItem('ai_imagen_history', JSON.stringify(history));
            this.updateHistoryCarousel();
        },

        /**
         * Clear history
         */
        clearHistory: function() {
            if (confirm('Are you sure you want to clear all history?')) {
                localStorage.removeItem('ai_imagen_history');
                $('#ai-imagen-preview-history').hide();
                $('#ai-imagen-history-carousel').empty();
            }
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

        // Load history on page load
        AIImagen.updateHistoryCarousel();

        // Bind clear history button
        $(document).on('click', '#ai-imagen-clear-history', function() {
            AIImagen.clearHistory();
        });
    });

})(jQuery);

