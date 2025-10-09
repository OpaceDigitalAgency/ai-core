/**
 * AI-Imagen Admin JavaScript
 * 
 * Main admin interface functionality
 * 
 * @package AI_Imagen
 * @version 0.6.2
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
            currentMetadata: {},
            manualEditEnabled: false,
            manualPrompt: ''
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
            $('#ai-imagen-prompt').on('input', function() {
                self.updatePromptPreview();
            });

            // Copy prompt button
            $('#ai-imagen-copy-prompt').on('click', function() {
                self.copyPromptToClipboard();
            });

            // Manual edit toggle
            $('#ai-imagen-manual-edit-toggle').on('change', function() {
                self.toggleManualEdit($(this).is(':checked'));
            });

            // Manual prompt textarea
            $('#ai-imagen-manual-prompt').on('input', function() {
                // Store manual prompt
                self.state.manualPrompt = $(this).val();
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

            // Aspect ratio change - update scene builder canvas
            $('#ai-imagen-aspect-ratio').on('change', function() {
                var aspectRatio = $(this).val();
                $('#scene-canvas').attr('data-aspect', aspectRatio);
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
                // Mark that this is a regeneration (not first generation)
                self.state.isRegenerating = true;
                self.generateImage();
            });
            
            // History delete
            $('.history-delete-btn').on('click', function() {
                var attachmentId = $(this).data('id');
                self.deleteImage(attachmentId, $(this).closest('.history-item'));
            });

            // Preview dock/undock toggle
            $('#ai-imagen-preview-dock-toggle').on('click', function() {
                self.togglePreviewModal();
            });

            // Modal close buttons
            $('#ai-imagen-preview-modal-close, #ai-imagen-preview-modal-overlay').on('click', function() {
                self.closePreviewModal();
            });

            // Modal action buttons
            $('#ai-imagen-modal-download-btn').on('click', function() {
                self.downloadImage();
            });

            $('#ai-imagen-modal-save-library-btn').on('click', function() {
                self.saveToLibrary();
            });

            $('#ai-imagen-modal-regenerate-btn').on('click', function() {
                self.closePreviewModal();
                self.generateImage();
            });

            // Keyboard shortcut for modal (Escape key)
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape' && $('#ai-imagen-preview-modal').hasClass('active')) {
                    self.closePreviewModal();
                }
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
         * This should match the PHP build_prompt() format exactly:
         * Image type: [workflow]
         * Image needed: [prompt]
         * Rules: [aspect ratio rules]
         * Overlays: [scene elements]
         */
        updatePromptPreview: function() {
            // Skip if manual edit is enabled
            if (this.state.manualEditEnabled) {
                return;
            }

            var prompt = $('#ai-imagen-prompt').val().trim();
            var sections = [];

            // 1. Image type: Determine the workflow context (style > use_case > role)
            var imageType = '';
            if (this.state.style) {
                imageType = this.getStyleLabel(this.state.style);
            } else if (this.state.useCase) {
                imageType = this.getUseCaseLabel(this.state.useCase);
            } else if (this.state.role) {
                imageType = this.getRoleLabel(this.state.role);
            }

            if (imageType) {
                sections.push('Image type: ' + imageType + '.');
            }

            // 2. Image needed: Main prompt
            if (prompt) {
                sections.push('Image needed: ' + prompt + '.');
            }

            // 3. Rules: Aspect ratio and general instructions
            var aspectRatio = $('#ai-imagen-aspect-ratio').val() || '1:1';
            sections.push('Rules: The canvas aspect ratio and resolution is ' + aspectRatio + '. Do not render or display these instructions, the ratio explicitly, glyph codes, etc. on the image. Ensure overlays adapt to the aspect ratio. Always preserve balance and safe margins around the edges.');

            // 4. Overlays: Scene builder elements
            if (window.AIImagenSceneBuilder && typeof window.AIImagenSceneBuilder.generateSceneDescription === 'function') {
                var sceneDesc = window.AIImagenSceneBuilder.generateSceneDescription();
                if (sceneDesc) {
                    sections.push('Overlays: ' + sceneDesc);
                }
            }

            // Update preview
            var $preview = $('#ai-imagen-prompt-preview-text');
            if (sections.length > 0) {
                $preview.text(sections.join(' '));
            } else {
                $preview.html('<em>Your final prompt will appear here as you make selections...</em>');
            }
        },

        /**
         * Get human-readable label for style
         */
        getStyleLabel: function(style) {
            var labels = {
                'product-photo': 'Product Photography',
                'social-media': 'Social Media Graphics',
                'blog-header': 'Blog Headers',
                'brand-layouts': 'Brand Layouts'
            };
            return labels[style] || style.replace(/-/g, ' ');
        },

        /**
         * Get human-readable label for use case
         */
        getUseCaseLabel: function(useCase) {
            var labels = {
                'marketing': 'Marketing Materials',
                'social-media': 'Social Media Content',
                'blog-content': 'Blog Content',
                'product-showcase': 'Product Showcase'
            };
            return labels[useCase] || useCase.replace(/-/g, ' ');
        },

        /**
         * Get human-readable label for role
         */
        getRoleLabel: function(role) {
            var labels = {
                'marketer': 'Marketer',
                'designer': 'Designer',
                'content-creator': 'Content Creator',
                'developer': 'Developer'
            };
            return labels[role] || role.replace(/-/g, ' ');
        },

        /**
         * Load related prompts from AI-Core Prompt Library
         */
        loadRelatedPrompts: function(category) {
            var self = this;

            // Map category to group name
            var groupName = this.getCategoryGroupName(category);

            console.log('Loading prompts for category:', category, 'Group name:', groupName);

            if (!groupName) {
                console.error('No group name found for category:', category);
                return;
            }

            // Show loading state immediately
            var $container = $('#ai-imagen-prompt-suggestions');
            var $list = $('#ai-imagen-prompt-suggestions-list');

            $list.html('<div class="prompt-suggestions-loading"><span class="dashicons dashicons-update"></span> Loading example prompts...</div>');
            $container.slideDown(300);

            // Use AI-Core AJAX URL and nonce (required for ai_core_get_prompts action)
            var ajaxUrl = (typeof aiCoreAdmin !== 'undefined') ? aiCoreAdmin.ajaxUrl : window.ajaxurl;
            var nonce = (typeof aiCoreAdmin !== 'undefined') ? aiCoreAdmin.nonce : '';

            console.log('aiCoreAdmin available:', typeof aiCoreAdmin !== 'undefined');
            console.log('AJAX URL:', ajaxUrl);
            console.log('Nonce available:', !!nonce);

            if (!nonce) {
                console.error('AI-Core nonce not available. Cannot load prompts.');
                console.error('aiCoreAdmin object:', typeof aiCoreAdmin !== 'undefined' ? aiCoreAdmin : 'undefined');
                $list.html('<div class="prompt-suggestions-error">Unable to load prompts. Please try again.</div>');
                return;
            }

            console.log('Sending AJAX request to load prompts...');

            $.ajax({
                url: ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ai_core_get_prompts',
                    group_name: groupName,
                    nonce: nonce
                },
                success: function(response) {
                    console.log('AJAX response:', response);

                    if (response.success && response.data.prompts && response.data.prompts.length > 0) {
                        console.log('Total prompts received:', response.data.prompts.length);
                        self.showPromptSuggestions(response.data.prompts);
                    } else {
                        console.warn('No prompts in response or response failed');
                        $list.html('<div class="prompt-suggestions-error">No example prompts found for this category.</div>');
                    }
                },
                error: function(xhr, status, error) {
                    console.error('AJAX error - Status:', status, 'Error:', error);
                    console.error('Response:', xhr.responseText);
                    $list.html('<div class="prompt-suggestions-error">Error loading prompts. Please try again.</div>');
                }
            });
        },

        /**
         * Get group name for category
         */
        getCategoryGroupName: function(category) {
            // Map workflow categories to search terms for AI-Core Prompt Library
            // All AI-Imagen groups have the "AI-Imagen: " prefix
            var mapping = {
                // Use Cases (9)
                'marketing-ads': 'AI-Imagen: Marketing & Ads',
                'social-media': 'AI-Imagen: Social Media',
                'product-photography': 'AI-Imagen: Product Photography',
                'website-design': 'AI-Imagen: Website Design',
                'publishing': 'AI-Imagen: Publishing',
                'presentations': 'AI-Imagen: Presentations',
                'game-development': 'AI-Imagen: Game Development',
                'education': 'AI-Imagen: Education',
                'print-on-demand': 'AI-Imagen: Print-on-Demand',

                // Roles (8)
                'marketing-manager': 'AI-Imagen: Marketing Manager',
                'social-media-manager': 'AI-Imagen: Social Media Manager',
                'small-business-owner': 'AI-Imagen: Small Business Owner',
                'graphic-designer': 'AI-Imagen: Graphic Designer',
                'content-publisher': 'AI-Imagen: Content Publisher',
                'developer': 'AI-Imagen: Developer',
                'educator': 'AI-Imagen: Educator',
                'event-planner': 'AI-Imagen: Event Planner',

                // Styles (9)
                'photorealistic': 'AI-Imagen: Photorealistic',
                'flat-minimalist': 'AI-Imagen: Flat & Minimalist',
                'cartoon-anime': 'AI-Imagen: Cartoon & Anime',
                'digital-painting': 'AI-Imagen: Digital Painting',
                'retro-vintage': 'AI-Imagen: Retro & Vintage',
                '3d-cgi': 'AI-Imagen: 3D & CGI',
                'hand-drawn': 'AI-Imagen: Hand-drawn',
                'brand-layouts': 'AI-Imagen: Brand Layouts',
                'transparent-assets': 'AI-Imagen: Transparent Assets'
            };

            return mapping[category] || category;
        },

        /**
         * Show prompt suggestions
         */
        showPromptSuggestions: function(prompts) {
            var $container = $('#ai-imagen-prompt-suggestions');
            var $list = $('#ai-imagen-prompt-suggestions-list');

            if (prompts.length === 0) {
                $container.hide();
                return;
            }

            var html = '';

            prompts.slice(0, 5).forEach(function(prompt) {
                // Use 'content' field which is the actual prompt text
                var promptContent = prompt.content || prompt.prompt || '';
                var promptTitle = prompt.title || prompt.name || 'Untitled';

                html += '<div class="prompt-suggestion-item" data-prompt="' +
                        promptContent.replace(/"/g, '&quot;').replace(/'/g, '&#39;') + '">' +
                        '<span class="dashicons dashicons-lightbulb"></span>' +
                        '<div class="prompt-suggestion-content">' +
                        '<div class="prompt-suggestion-title">' + promptTitle + '</div>' +
                        '<div class="prompt-suggestion-text">' + promptContent.substring(0, 120) +
                        (promptContent.length > 120 ? '...' : '') + '</div>' +
                        '</div>' +
                        '</div>';
            });

            $list.html(html);
            $container.slideDown(300);

            // Bind click events
            $('.prompt-suggestion-item').off('click').on('click', function() {
                var prompt = $(this).data('prompt');
                $('#ai-imagen-prompt').val(prompt);
                AIImagen.updatePromptPreview();
                // Scroll to prompt input
                $('html, body').animate({
                    scrollTop: $('#ai-imagen-prompt').offset().top - 100
                }, 500);
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

            // Bind search event
            var self = this;
            $('#prompt-library-search').on('input', function() {
                self.filterPromptLibrary($(this).val());
            });
        },

        /**
         * Load prompt library
         */
        loadPromptLibrary: function() {
            var self = this;

            // Use WordPress AJAX URL and nonce
            var ajaxUrl = (typeof aiCoreAdmin !== 'undefined') ? aiCoreAdmin.ajaxUrl : (typeof aiImagenData !== 'undefined' ? aiImagenData.ajax_url : window.ajaxurl);
            var nonce = (typeof aiCoreAdmin !== 'undefined') ? aiCoreAdmin.nonce : (typeof aiImagenData !== 'undefined' ? aiImagenData.nonce : '');

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
         * Filter prompt library
         */
        filterPromptLibrary: function(searchTerm) {
            searchTerm = searchTerm.toLowerCase().trim();

            if (!searchTerm) {
                // Show all groups and items
                $('.prompt-library-group').show();
                $('.prompt-library-item').show();
                return;
            }

            // Filter items
            $('.prompt-library-item').each(function() {
                var $item = $(this);
                var promptText = $item.data('prompt').toLowerCase();
                var promptTitle = $item.find('.prompt-name').text().toLowerCase();

                if (promptText.indexOf(searchTerm) !== -1 || promptTitle.indexOf(searchTerm) !== -1) {
                    $item.show();
                } else {
                    $item.hide();
                }
            });

            // Hide groups with no visible items
            $('.prompt-library-group').each(function() {
                var $group = $(this);
                var visibleItems = $group.find('.prompt-library-item:visible').length;

                if (visibleItems > 0) {
                    $group.show();
                    // Update count
                    $group.find('.group-count').text('(' + visibleItems + ')');
                } else {
                    $group.hide();
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

            // Clear previous workflow selections
            this.state.useCase = '';
            this.state.role = '';
            this.state.style = '';

            // Clear card selections
            $('.ai-imagen-card').removeClass('selected');

            // Hide prompt suggestions
            $('#ai-imagen-prompt-suggestions').hide();

            // Update tabs
            $('.workflow-tab').removeClass('active');
            $('.workflow-tab[data-workflow="' + workflow + '"]').addClass('active');

            // Update panels
            $('.workflow-panel').removeClass('active');
            $('#panel-' + workflow).addClass('active');

            // Update prompt preview
            this.updatePromptPreview();
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

            // Get button references
            var $generateBtn = $('#ai-imagen-generate-btn');
            var $regenerateBtn = $('#ai-imagen-regenerate-btn');

            // Prevent double-clicking
            if ($generateBtn.prop('disabled')) {
                console.log('AI-Imagen: Generation already in progress');
                return;
            }

            // Show loading state for both generate and regenerate buttons
            $generateBtn.prop('disabled', true).html('<span class="dashicons dashicons-update"></span> Generating...');
            $regenerateBtn.prop('disabled', true).html('<span class="dashicons dashicons-update"></span> Regenerating...');

            // Always hide the placeholder and show loading animation
            $('.preview-placeholder').hide();
            $('#ai-imagen-preview-actions').hide();

            // Handle loading state differently for regeneration vs first generation
            if (this.state.isRegenerating && $('#ai-imagen-preview-area img').length > 0) {
                // For regeneration: show loading overlay on top of existing image
                $('#ai-imagen-preview-area').addClass('ai-imagen-loading');
                $('#ai-imagen-preview-area img').show(); // Keep existing image visible
                $('#ai-imagen-preview-loading').show();
                console.log('AI-Imagen: Regenerating, showing loading overlay on existing image');
            } else {
                // For first generation: hide any existing image and show loading animation
                $('#ai-imagen-preview-area img').hide();
                $('#ai-imagen-preview-loading').show();
                console.log('AI-Imagen: First generation, showing loading indicator');
            }

            // Reset regeneration flag
            this.state.isRegenerating = false;

            console.log('AI-Imagen: Sending raw prompt to backend (will be formatted by PHP):', prompt);

            // Get scene builder data if available
            var sceneElements = [];

            if (window.AIImagenSceneBuilder && typeof window.AIImagenSceneBuilder.getSceneData === 'function') {
                sceneElements = window.AIImagenSceneBuilder.getSceneData();
                console.log('AI-Imagen: Scene elements being sent:', sceneElements);
            }

            // Send raw prompt to backend - let PHP build_prompt() handle formatting
            // The backend will construct the proper format:
            // Image type: [workflow]
            // Image needed: [prompt]
            // Rules: [aspect ratio rules]
            // Overlays: [scene elements]

            // Prepare data
            var data = {
                action: 'ai_imagen_generate',
                nonce: aiImagenData.nonce,
                prompt: prompt,  // Send raw prompt, not pre-formatted
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
                timeout: 180000, // 3 minutes timeout for HD image generation (can take 30-60 seconds)
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

                        // Display image (replace any existing content)
                        $('#ai-imagen-preview-area').html('<img src="' + response.data.image_url + '" alt="Generated image" style="display: block;">');
                        $('#ai-imagen-preview-actions').show();

                        // Enable expand button now that we have an image
                        $('#ai-imagen-preview-dock-toggle').prop('disabled', false);

                        console.log('AI-Imagen: Image displayed, loading indicator hidden, expand button enabled');

                        // Update prompt preview with the actual built prompt that was sent to API
                        if (response.data.built_prompt) {
                            $('#ai-imagen-prompt-preview-text').text(response.data.built_prompt);
                            console.log('Actual prompt sent to API:', response.data.built_prompt);
                        }

                        // Add to history
                        self.addToHistory(response.data.image_url, prompt);

                        // Show history carousel
                        $('#ai-imagen-preview-history').show();

                        // Show success message
                        self.showNotice('success', response.data.message);
                    } else {
                        // Hide loading animation on error
                        $('#ai-imagen-preview-loading').hide();
                        alert(response.data.message || 'Failed to generate image.');
                    }
                },
                error: function(xhr, status, error) {
                    // Hide loading animation on error
                    $('#ai-imagen-preview-loading').hide();
                    console.error('Generation error:', error, 'Status:', status);

                    // Provide specific error message for timeout
                    if (status === 'timeout') {
                        alert('Image generation timed out. HD quality images can take 30-60 seconds. Please try again or use Standard quality.');
                    } else {
                        alert('An error occurred while generating the image: ' + error);
                    }
                },
                complete: function() {
                    // Always re-enable both buttons and restore text
                    console.log('AI-Imagen: AJAX complete callback fired, re-enabling buttons');

                    try {
                        // Reset regeneration flag
                        self.state.isRegenerating = false;

                        // Re-enable buttons
                        $generateBtn.prop('disabled', false);
                        $generateBtn.html('<span class="dashicons dashicons-images-alt2"></span> Generate Image');
                        $regenerateBtn.prop('disabled', false);
                        $regenerateBtn.html('<span class="dashicons dashicons-update"></span> Regenerate');
                        $('#ai-imagen-preview-area').removeClass('ai-imagen-loading');

                        console.log('AI-Imagen: Buttons re-enabled successfully');
                    } catch (e) {
                        console.error('AI-Imagen: Error re-enabling buttons:', e);
                        // Force re-enable even if there's an error
                        $('#ai-imagen-generate-btn, #ai-imagen-regenerate-btn').prop('disabled', false);
                    }
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
            try {
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

                // Save to localStorage with quota handling
                try {
                    localStorage.setItem('ai_imagen_history', JSON.stringify(history));
                } catch (quotaError) {
                    // If quota exceeded, clear old history and try with just the new image
                    console.warn('AI-Imagen: localStorage quota exceeded, clearing old history');
                    history = history.slice(0, 5); // Keep only 5 most recent
                    try {
                        localStorage.setItem('ai_imagen_history', JSON.stringify(history));
                    } catch (e) {
                        // If still failing, just skip history storage
                        console.error('AI-Imagen: Cannot save to localStorage, skipping history');
                    }
                }

                // Update carousel
                this.updateHistoryCarousel();
            } catch (e) {
                // Don't let history errors break the generation flow
                console.error('AI-Imagen: Error adding to history:', e);
            }
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
        },

        /**
         * Toggle preview modal (dock/undock)
         */
        togglePreviewModal: function() {
            var $modal = $('#ai-imagen-preview-modal');
            var $modalBody = $('#ai-imagen-preview-modal-body');
            var currentImageUrl = this.state.currentImageUrl;

            if (!currentImageUrl) {
                return; // No image to show
            }

            // Copy image to modal
            $modalBody.html('<img src="' + currentImageUrl + '" alt="Generated Image">');

            // Show modal
            $modal.addClass('active');

            // Update button text
            $('#ai-imagen-preview-dock-toggle .dock-toggle-text').text('Collapse');
            $('#ai-imagen-preview-dock-toggle .dashicons')
                .removeClass('dashicons-editor-expand')
                .addClass('dashicons-editor-contract');
        },

        /**
         * Close preview modal
         */
        closePreviewModal: function() {
            var $modal = $('#ai-imagen-preview-modal');

            // Hide modal
            $modal.removeClass('active');

            // Update button text
            $('#ai-imagen-preview-dock-toggle .dock-toggle-text').text('Expand');
            $('#ai-imagen-preview-dock-toggle .dashicons')
                .removeClass('dashicons-editor-contract')
                .addClass('dashicons-editor-expand');
        },

        /**
         * Copy prompt to clipboard
         */
        copyPromptToClipboard: function() {
            var promptText = $('#ai-imagen-prompt-preview-text').text();

            if (!promptText || promptText.trim() === '') {
                this.showNotice('warning', 'No prompt to copy');
                return;
            }

            // Use modern clipboard API
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(promptText).then(function() {
                    // Show success feedback
                    var $button = $('#ai-imagen-copy-prompt');
                    var originalText = $button.find('span:last').text();
                    $button.find('span:last').text('Copied!');
                    $button.addClass('button-primary');

                    setTimeout(function() {
                        $button.find('span:last').text(originalText);
                        $button.removeClass('button-primary');
                    }, 2000);
                }).catch(function(err) {
                    console.error('Failed to copy:', err);
                    AIImagen.showNotice('error', 'Failed to copy prompt');
                });
            } else {
                // Fallback for older browsers
                var $temp = $('<textarea>');
                $('body').append($temp);
                $temp.val(promptText).select();
                document.execCommand('copy');
                $temp.remove();
                this.showNotice('success', 'Prompt copied to clipboard');
            }
        },

        /**
         * Toggle manual edit mode
         */
        toggleManualEdit: function(enabled) {
            var $editArea = $('#ai-imagen-manual-edit-area');
            var $manualPrompt = $('#ai-imagen-manual-prompt');

            if (enabled) {
                // Show manual edit area
                $editArea.slideDown(300);

                // Copy current auto-generated prompt to manual textarea
                var currentPrompt = $('#ai-imagen-prompt-preview-text').text();
                if (currentPrompt && !currentPrompt.includes('Your final prompt')) {
                    $manualPrompt.val(currentPrompt);
                    this.state.manualPrompt = currentPrompt;
                }

                // Disable auto-updates
                this.state.manualEditEnabled = true;
            } else {
                // Hide manual edit area
                $editArea.slideUp(300);

                // Re-enable auto-updates
                this.state.manualEditEnabled = false;
                this.state.manualPrompt = '';

                // Update preview
                this.updatePromptPreview();
            }
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

