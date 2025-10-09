/**
 * AI-Imagen Scene Builder JavaScript
 *
 * Scene builder functionality for adding elements to images
 *
 * @package AI_Imagen
 * @version 0.6.2
 */

(function($) {
    'use strict';

    /**
     * Scene Builder
     */
    var SceneBuilder = {

        elements: [],
        selectedElement: null,
        elementCounter: 0,
        isDragging: false,
        isResizing: false,
        dragStartX: 0,
        dragStartY: 0,
        elementStartX: 0,
        elementStartY: 0,

        /**
         * Initialize
         */
        init: function() {
            if (!$('#ai-imagen-scene-builder').length) {
                return; // Scene builder not on this page
            }

            this.createSceneBuilder();
            this.bindEvents();
        },

        /**
         * Create scene builder interface
         */
        createSceneBuilder: function() {
            var html = `
                <div class="ai-imagen-scene-builder">
                    <div class="scene-builder-header">
                        <h3>Scene Builder</h3>
                        <button type="button" class="button button-small" id="scene-builder-toggle">
                            <span class="dashicons dashicons-arrow-down-alt2"></span>
                            Toggle
                        </button>
                    </div>
                    <div class="scene-builder-content">
                        <div class="scene-builder-toolbar">
                            <button type="button" class="button scene-add-btn" data-type="text">
                                <span class="dashicons dashicons-editor-textcolor"></span>
                                Add Text
                            </button>
                            <button type="button" class="button scene-add-btn" data-type="logo">
                                <span class="dashicons dashicons-format-image"></span>
                                Add Logo
                            </button>
                            <button type="button" class="button scene-add-btn" data-type="icon">
                                <span class="dashicons dashicons-star-filled"></span>
                                Add Icon
                            </button>
                            <button type="button" class="button scene-add-btn" data-type="image">
                                <span class="dashicons dashicons-format-gallery"></span>
                                Add Image
                            </button>
                            <button type="button" class="button button-link-delete" id="scene-clear-all">
                                <span class="dashicons dashicons-trash"></span>
                                Clear All
                            </button>
                        </div>
                        <div class="scene-builder-canvas" id="scene-canvas" data-aspect="1:1">
                            <div class="scene-canvas-placeholder">
                                <span class="dashicons dashicons-images-alt2"></span>
                                <p>Add elements to build your scene</p>
                            </div>
                        </div>
                        <div class="scene-builder-properties" id="scene-properties" style="display: none;">
                            <div class="properties-header">
                                <h4>Element Properties</h4>
                                <button type="button" class="button button-small properties-toggle" id="properties-toggle">
                                    <span class="dashicons dashicons-arrow-down-alt2"></span>
                                    <span class="toggle-text">Expand</span>
                                </button>
                            </div>
                            <div class="properties-content" id="properties-content" style="display: none;">
                                <div class="property-group">
                                    <label>Content:</label>
                                    <input type="text" id="element-content" class="regular-text">
                                </div>
                                <div class="property-group">
                                    <label>Font Size:</label>
                                    <input type="number" id="element-font-size" min="8" max="200" value="16">
                                </div>
                                <div class="property-group">
                                    <label>Color:</label>
                                    <input type="color" id="element-color" value="#000000">
                                </div>
                                <div class="property-group">
                                    <label>Font Weight:</label>
                                    <select id="element-font-weight">
                                        <option value="normal">Normal</option>
                                        <option value="bold">Bold</option>
                                        <option value="lighter">Light</option>
                                    </select>
                                </div>
                                <div class="property-group">
                                    <label>Position X:</label>
                                    <input type="number" id="element-x" min="0" value="0">
                                </div>
                                <div class="property-group">
                                    <label>Position Y:</label>
                                    <input type="number" id="element-y" min="0" value="0">
                                </div>
                                <div class="property-group">
                                    <label>Width:</label>
                                    <input type="number" id="element-width" min="10" value="100">
                                </div>
                                <div class="property-group">
                                    <label>Height:</label>
                                    <input type="number" id="element-height" min="10" value="30">
                                </div>
                                <div class="property-actions">
                                    <button type="button" class="button button-primary" id="apply-properties">Apply</button>
                                    <button type="button" class="button button-link-delete" id="delete-element">Delete</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            // Insert after generation settings
            $('.ai-imagen-generate-section').before(html);
        },

        /**
         * Bind events
         */
        bindEvents: function() {
            var self = this;

            // Toggle scene builder
            $(document).on('click', '#scene-builder-toggle', function() {
                $('.scene-builder-content').slideToggle();
            });

            // Toggle properties panel
            $(document).on('click', '#properties-toggle', function() {
                var $content = $('#properties-content');
                var $toggle = $(this);

                if ($content.is(':visible')) {
                    $content.slideUp(300);
                    $toggle.find('.dashicons')
                        .removeClass('dashicons-arrow-up-alt2')
                        .addClass('dashicons-arrow-down-alt2');
                    $toggle.find('.toggle-text').text('Expand');
                } else {
                    $content.slideDown(300);
                    $toggle.find('.dashicons')
                        .removeClass('dashicons-arrow-down-alt2')
                        .addClass('dashicons-arrow-up-alt2');
                    $toggle.find('.toggle-text').text('Collapse');
                }
            });

            // Add element buttons
            $(document).on('click', '.scene-add-btn', function() {
                var type = $(this).data('type');
                self.addElement(type);
            });

            // Clear all
            $(document).on('click', '#scene-clear-all', function() {
                if (confirm('Are you sure you want to clear all elements?')) {
                    self.clearAll();
                }
            });

            // Element selection
            $(document).on('click', '.scene-element', function(e) {
                e.stopPropagation();
                self.selectElement($(this).data('id'));
            });

            // Canvas click (deselect)
            $(document).on('click', '#scene-canvas', function(e) {
                if (e.target === this) {
                    self.deselectAll();
                }
            });

            // Element dragging
            $(document).on('mousedown', '.scene-element', function(e) {
                if ($(e.target).hasClass('element-resize-handle')) {
                    return; // Let resize handle it
                }

                e.preventDefault();
                self.startDrag($(this), e);
            });

            $(document).on('mousemove', function(e) {
                if (self.isDragging) {
                    self.drag(e);
                } else if (self.isResizing) {
                    self.resize(e);
                }
            });

            $(document).on('mouseup', function() {
                self.stopDrag();
                self.stopResize();
            });

            // Resize handle
            $(document).on('mousedown', '.element-resize-handle', function(e) {
                e.preventDefault();
                e.stopPropagation();
                self.startResize($(this).closest('.scene-element'), e);
            });

            // Property changes
            $(document).on('change', '#element-content', function() {
                self.updateElementContent($(this).val());
            });

            $(document).on('change', '#element-font-size', function() {
                self.updateElementStyle('fontSize', $(this).val() + 'px');
            });

            $(document).on('change', '#element-color', function() {
                self.updateElementStyle('color', $(this).val());
            });

            $(document).on('change', '#element-font-weight', function() {
                self.updateElementStyle('fontWeight', $(this).val());
            });

            // Apply properties button
            $(document).on('click', '#apply-properties', function() {
                self.applyProperties();
            });

            // Delete element button
            $(document).on('click', '#delete-element', function() {
                self.deleteSelectedElement();
            });

            // Element delete button
            $(document).on('click', '.element-delete', function(e) {
                e.stopPropagation();
                var elementId = $(this).closest('.scene-element').data('id');
                self.deleteElement(elementId);
            });

            // Keyboard shortcuts
            $(document).on('keydown', function(e) {
                if (self.selectedElement) {
                    // Delete key
                    if (e.key === 'Delete' || e.key === 'Backspace') {
                        e.preventDefault();
                        self.deleteSelectedElement();
                    }
                    // Arrow keys for positioning
                    if (e.key.startsWith('Arrow')) {
                        e.preventDefault();
                        self.moveElementWithKeys(e.key);
                    }
                }
            });
        },

        /**
         * Add element to canvas
         */
        addElement: function(type) {
            var self = this;
            var elementId = 'element-' + (++this.elementCounter);

            var element = {
                id: elementId,
                type: type,
                x: 50,
                y: 50,
                width: type === 'text' ? 200 : 100,
                height: type === 'text' ? 40 : 100,
                content: type === 'text' ? 'Your Text Here' : '',
                fontSize: 16,
                color: '#000000',
                fontWeight: 'normal',
                imageUrl: '',
                imageFile: null,
                iconName: ''
            };

            // Handle different element types
            if (type === 'text') {
                var text = prompt('Enter text:', 'Your Text Here');
                if (text) {
                    element.content = text;
                    this.elements.push(element);
                    this.renderElement(element);
                    this.selectElement(elementId);
                }
            } else if (type === 'logo' || type === 'image') {
                // Open WordPress media uploader
                this.openMediaUploader(function(attachment) {
                    element.imageUrl = attachment.url;
                    element.imageFile = attachment;
                    self.elements.push(element);
                    self.renderElement(element);
                    self.selectElement(elementId);
                });
            } else if (type === 'icon') {
                // Open icon picker modal
                this.openIconPicker(function(iconData) {
                    element.iconName = iconData.name;
                    element.iconClass = iconData.iconClass;
                    element.content = iconData.name;
                    self.elements.push(element);
                    self.renderElement(element);
                    self.selectElement(elementId);
                });
            }
        },

        /**
         * Render element on canvas
         */
        renderElement: function(element) {
            var $canvas = $('#scene-canvas');
            var $placeholder = $canvas.find('.scene-canvas-placeholder');

            if ($placeholder.length) {
                $placeholder.remove();
            }

            // Update scene prompt preview
            this.updateScenePromptPreview();

            var html = '';

            if (element.type === 'text') {
                html = `
                    <div class="scene-element scene-element-text" data-id="${element.id}"
                         style="left: ${element.x}px; top: ${element.y}px; width: ${element.width}px; height: ${element.height}px;
                                font-size: ${element.fontSize}px; color: ${element.color}; font-weight: ${element.fontWeight};">
                        <div class="element-content">${element.content}</div>
                        <div class="element-controls">
                            <button type="button" class="element-control-btn element-delete" title="Delete">
                                <span class="dashicons dashicons-no"></span>
                            </button>
                        </div>
                        <div class="element-resize-handle"></div>
                    </div>
                `;
            } else if (element.type === 'icon') {
                // Render icon with actual icon class and colour
                var iconClass = element.iconClass || 'dashicons-star-filled';
                var iconColor = element.color || '#000000';
                html = `
                    <div class="scene-element scene-element-icon" data-id="${element.id}"
                         style="left: ${element.x}px; top: ${element.y}px; width: ${element.width}px; height: ${element.height}px;">
                        <div class="element-content">
                            <div class="icon-display">
                                <span class="dashicons ${iconClass}" style="color: ${iconColor};"></span>
                                <div class="icon-label">${element.iconName}</div>
                            </div>
                        </div>
                        <div class="element-controls">
                            <button type="button" class="element-control-btn element-delete" title="Delete">
                                <span class="dashicons dashicons-no"></span>
                            </button>
                        </div>
                        <div class="element-resize-handle"></div>
                    </div>
                `;
            } else if (element.type === 'logo' || element.type === 'image') {
                // Render image/logo with uploaded file
                html = `
                    <div class="scene-element scene-element-image" data-id="${element.id}"
                         style="left: ${element.x}px; top: ${element.y}px; width: ${element.width}px; height: ${element.height}px;">
                        <div class="element-content">
                            ${element.imageUrl ? `<img src="${element.imageUrl}" alt="${element.type}">` : `<span class="dashicons dashicons-format-image"></span>`}
                        </div>
                        <div class="element-controls">
                            <button type="button" class="element-control-btn element-delete" title="Delete">
                                <span class="dashicons dashicons-no"></span>
                            </button>
                        </div>
                        <div class="element-resize-handle"></div>
                    </div>
                `;
            }

            $canvas.append(html);
        },

        /**
         * Select element
         */
        selectElement: function(elementId) {
            // Only deselect if selecting a different element
            if (this.selectedElement && this.selectedElement.id !== elementId) {
                this.deselectAll();
            }

            var $element = $('.scene-element[data-id="' + elementId + '"]');
            $element.addClass('selected');

            this.selectedElement = this.elements.find(function(el) {
                return el.id === elementId;
            });

            this.showProperties();
        },

        /**
         * Deselect all elements
         */
        deselectAll: function() {
            $('.scene-element').removeClass('selected');
            this.selectedElement = null;
            this.hideProperties();
        },

        /**
         * Show properties panel
         */
        showProperties: function() {
            if (!this.selectedElement) return;

            var $props = $('#scene-properties');
            var $content = $('#properties-content');

            // Populate properties
            $('#element-content').val(this.selectedElement.content || '');
            $('#element-font-size').val(this.selectedElement.fontSize || 16);
            $('#element-color').val(this.selectedElement.color || '#000000');
            $('#element-font-weight').val(this.selectedElement.fontWeight || 'normal');
            $('#element-x').val(Math.round(this.selectedElement.x) || 0);
            $('#element-y').val(Math.round(this.selectedElement.y) || 0);
            $('#element-width').val(Math.round(this.selectedElement.width) || 100);
            $('#element-height').val(Math.round(this.selectedElement.height) || 100);

            // Show/hide relevant fields based on element type
            if (this.selectedElement.type === 'text') {
                $('#element-content, #element-font-size, #element-color, #element-font-weight').closest('.property-group').show();
            } else if (this.selectedElement.type === 'icon') {
                // For icons: show colour picker but hide text-specific fields
                $('#element-color').closest('.property-group').show();
                $('#element-content, #element-font-size, #element-font-weight').closest('.property-group').hide();
            } else {
                // For logos and images: hide all text/style fields
                $('#element-content, #element-font-size, #element-color, #element-font-weight').closest('.property-group').hide();
            }

            // Show properties panel
            if (!$props.is(':visible')) {
                $props.slideDown();
            }

            // Auto-expand content when element is selected for better UX
            if (!$content.is(':visible')) {
                $content.slideDown();
                $('#properties-toggle').find('.dashicons')
                    .removeClass('dashicons-arrow-down-alt2')
                    .addClass('dashicons-arrow-up-alt2');
                $('#properties-toggle').find('.toggle-text').text('Collapse');
            }
        },

        /**
         * Hide properties panel
         */
        hideProperties: function() {
            $('#scene-properties').slideUp();
        },

        /**
         * Start dragging element
         */
        startDrag: function($element, e) {
            this.isDragging = true;
            this.dragStartX = e.pageX;
            this.dragStartY = e.pageY;

            var position = $element.position();
            this.elementStartX = position.left;
            this.elementStartY = position.top;

            $element.addClass('dragging');
            this.selectElement($element.data('id'));
        },

        /**
         * Drag element
         */
        drag: function(e) {
            if (!this.isDragging || !this.selectedElement) return;

            var deltaX = e.pageX - this.dragStartX;
            var deltaY = e.pageY - this.dragStartY;

            var newX = this.elementStartX + deltaX;
            var newY = this.elementStartY + deltaY;

            // Get canvas dimensions
            var $canvas = $('#scene-canvas');
            var canvasWidth = $canvas.width();
            var canvasHeight = $canvas.height();

            // Get element dimensions
            var elementWidth = this.selectedElement.width || 100;
            var elementHeight = this.selectedElement.height || 30;

            // Keep within canvas bounds (all sides)
            newX = Math.max(0, Math.min(newX, canvasWidth - elementWidth));
            newY = Math.max(0, Math.min(newY, canvasHeight - elementHeight));

            this.selectedElement.x = newX;
            this.selectedElement.y = newY;

            var $element = $('.scene-element[data-id="' + this.selectedElement.id + '"]');
            $element.css({
                left: newX + 'px',
                top: newY + 'px'
            });

            // Update properties panel
            $('#element-x').val(Math.round(newX));
            $('#element-y').val(Math.round(newY));
        },

        /**
         * Stop dragging
         */
        stopDrag: function() {
            if (this.isDragging) {
                $('.scene-element').removeClass('dragging');
                this.isDragging = false;
                // Update scene prompt preview after drag
                this.updateScenePromptPreview();
            }
        },

        /**
         * Start resizing element
         */
        startResize: function($element, e) {
            this.isResizing = true;
            this.dragStartX = e.pageX;
            this.dragStartY = e.pageY;

            this.elementStartX = $element.width();
            this.elementStartY = $element.height();

            // Store initial font size for text elements
            if (this.selectedElement && this.selectedElement.type === 'text') {
                this.elementStartFontSize = this.selectedElement.fontSize;
            }

            this.selectElement($element.data('id'));
        },

        /**
         * Resize element
         */
        resize: function(e) {
            if (!this.isResizing || !this.selectedElement) return;

            var deltaX = e.pageX - this.dragStartX;
            var deltaY = e.pageY - this.dragStartY;

            var newWidth = Math.max(20, this.elementStartX + deltaX);
            var newHeight = Math.max(20, this.elementStartY + deltaY);

            // Get canvas dimensions
            var $canvas = $('#scene-canvas');
            var canvasWidth = $canvas.width();
            var canvasHeight = $canvas.height();

            // Ensure element doesn't exceed canvas bounds when resizing
            var maxWidth = canvasWidth - this.selectedElement.x;
            var maxHeight = canvasHeight - this.selectedElement.y;

            newWidth = Math.min(newWidth, maxWidth);
            newHeight = Math.min(newHeight, maxHeight);

            this.selectedElement.width = newWidth;
            this.selectedElement.height = newHeight;

            var $element = $('.scene-element[data-id="' + this.selectedElement.id + '"]');
            $element.css({
                width: newWidth + 'px',
                height: newHeight + 'px'
            });

            // Scale font size for text elements proportionally
            if (this.selectedElement.type === 'text' && this.elementStartFontSize) {
                var scaleFactor = Math.min(newWidth / this.elementStartX, newHeight / this.elementStartY);
                var newFontSize = Math.max(8, Math.round(this.elementStartFontSize * scaleFactor));
                this.selectedElement.fontSize = newFontSize;
                $element.css('font-size', newFontSize + 'px');
                $('#element-font-size').val(newFontSize);
            }

            // Scale icon/logo/image elements proportionally
            if (this.selectedElement.type === 'icon' || this.selectedElement.type === 'logo' || this.selectedElement.type === 'image') {
                var $content = $element.find('.element-content');
                if ($content.length) {
                    // For icons (dashicons), scale the font-size
                    if (this.selectedElement.type === 'icon') {
                        var iconSize = Math.min(newWidth, newHeight) * 0.8; // 80% of container
                        $content.css('font-size', iconSize + 'px');
                    }
                    // For images and logos, the CSS will handle the sizing via width/height 100%
                }
            }

            // Update properties panel
            $('#element-width').val(Math.round(newWidth));
            $('#element-height').val(Math.round(newHeight));
        },

        /**
         * Stop resizing
         */
        stopResize: function() {
            if (this.isResizing) {
                this.isResizing = false;
                // Update scene prompt preview after resize
                this.updateScenePromptPreview();
            }
        },

        /**
         * Update element content
         */
        updateElementContent: function(content) {
            if (!this.selectedElement) return;

            this.selectedElement.content = content;

            var $element = $('.scene-element[data-id="' + this.selectedElement.id + '"]');
            $element.find('.element-content').text(content);
        },

        /**
         * Update element style
         */
        updateElementStyle: function(property, value) {
            if (!this.selectedElement) return;

            this.selectedElement[property] = value;

            var $element = $('.scene-element[data-id="' + this.selectedElement.id + '"]');

            // For icons, apply colour to the inner dashicons span
            if (this.selectedElement.type === 'icon' && property === 'color') {
                $element.find('.dashicons').css('color', value);
            } else {
                $element.css(property, value);
            }
        },

        /**
         * Apply properties from panel
         */
        applyProperties: function() {
            if (!this.selectedElement) return;

            // Get values from properties panel
            var x = parseInt($('#element-x').val());
            var y = parseInt($('#element-y').val());
            var width = parseInt($('#element-width').val());
            var height = parseInt($('#element-height').val());

            // Update element
            this.selectedElement.x = x;
            this.selectedElement.y = y;
            this.selectedElement.width = width;
            this.selectedElement.height = height;

            if (this.selectedElement.type === 'text') {
                this.selectedElement.content = $('#element-content').val();
                this.selectedElement.fontSize = parseInt($('#element-font-size').val());
                this.selectedElement.color = $('#element-color').val();
                this.selectedElement.fontWeight = $('#element-font-weight').val();
            } else if (this.selectedElement.type === 'icon') {
                // For icons: update colour
                this.selectedElement.color = $('#element-color').val();
            }

            // Re-render element
            var $element = $('.scene-element[data-id="' + this.selectedElement.id + '"]');
            $element.remove();
            this.renderElement(this.selectedElement);
            this.selectElement(this.selectedElement.id);
        },

        /**
         * Delete selected element
         */
        deleteSelectedElement: function() {
            if (!this.selectedElement) return;

            this.deleteElement(this.selectedElement.id);
        },

        /**
         * Delete element by ID
         */
        deleteElement: function(elementId) {
            // Remove from elements array
            this.elements = this.elements.filter(function(el) {
                return el.id !== elementId;
            });

            // Remove from DOM
            $('.scene-element[data-id="' + elementId + '"]').remove();

            // Deselect
            if (this.selectedElement && this.selectedElement.id === elementId) {
                this.deselectAll();
            }

            // Show placeholder if no elements
            if (this.elements.length === 0) {
                $('#scene-canvas').html(`
                    <div class="scene-canvas-placeholder">
                        <span class="dashicons dashicons-images-alt2"></span>
                        <p>Add elements to build your scene</p>
                    </div>
                `);
            }

            // Update scene prompt preview
            this.updateScenePromptPreview();
        },

        /**
         * Clear all elements
         */
        clearAll: function() {
            this.elements = [];
            this.selectedElement = null;
            this.elementCounter = 0;

            $('#scene-canvas').html(`
                <div class="scene-canvas-placeholder">
                    <span class="dashicons dashicons-images-alt2"></span>
                    <p>Add elements to build your scene</p>
                </div>
            `);

            this.hideProperties();
            this.updateScenePromptPreview();
        },

        /**
         * Move element with keyboard
         */
        moveElementWithKeys: function(key) {
            if (!this.selectedElement) return;

            var step = 1;

            switch(key) {
                case 'ArrowUp':
                    this.selectedElement.y = Math.max(0, this.selectedElement.y - step);
                    break;
                case 'ArrowDown':
                    this.selectedElement.y += step;
                    break;
                case 'ArrowLeft':
                    this.selectedElement.x = Math.max(0, this.selectedElement.x - step);
                    break;
                case 'ArrowRight':
                    this.selectedElement.x += step;
                    break;
            }

            var $element = $('.scene-element[data-id="' + this.selectedElement.id + '"]');
            $element.css({
                left: this.selectedElement.x + 'px',
                top: this.selectedElement.y + 'px'
            });

            // Update properties panel
            $('#element-x').val(this.selectedElement.x);
            $('#element-y').val(this.selectedElement.y);
        },

        /**
         * Update scene prompt preview
         * Now updates the main prompt preview section instead of separate scene preview
         */
        updateScenePromptPreview: function() {
            // Trigger the main prompt preview update if AIImagen is available
            if (typeof AIImagen !== 'undefined' && typeof AIImagen.updatePromptPreview === 'function') {
                AIImagen.updatePromptPreview();
            }
        },

        /**
         * Get scene elements data for generation
         * Converts pixel positions to percentages for the backend
         */
        getSceneData: function() {
            var canvasWidth = $('#scene-canvas').width() || 800;
            var canvasHeight = $('#scene-canvas').height() || 600;

            return this.elements.map(function(el) {
                // Convert pixel positions to percentages
                var xPercent = Math.round((el.x / canvasWidth) * 100);
                var yPercent = Math.round((el.y / canvasHeight) * 100);
                var widthPercent = Math.round((el.width / canvasWidth) * 100);
                var heightPercent = Math.round((el.height / canvasHeight) * 100);

                return {
                    type: el.type,
                    content: el.content,
                    x: xPercent,  // Send as percentage
                    y: yPercent,  // Send as percentage
                    width: widthPercent,  // Send as percentage
                    height: heightPercent,  // Send as percentage
                    fontSize: el.fontSize,
                    color: el.color,
                    fontWeight: el.fontWeight,
                    imageUrl: el.imageUrl,
                    imageFile: el.imageFile,
                    iconName: el.iconName
                };
            });
        },

        /**
         * Generate prompt description from scene
         */
        generateSceneDescription: function() {
            if (this.elements.length === 0) {
                return '';
            }

            var overlays = [];
            var canvasWidth = $('#scene-canvas').width() || 800;
            var canvasHeight = $('#scene-canvas').height() || 600;

            this.elements.forEach(function(el) {
                // Calculate position as percentages
                var xPercent = Math.round((el.x / canvasWidth) * 100);
                var yPercent = Math.round((el.y / canvasHeight) * 100);
                var widthPercent = Math.round((el.width / canvasWidth) * 100);
                var heightPercent = Math.round((el.height / canvasHeight) * 100);

                if (el.type === 'text') {
                    var textColor = el.color || '#000000';
                    var fontSize = (el.fontSize || 24) + 'px';
                    var fontWeight = el.fontWeight || 'normal';

                    overlays.push(
                        'Add a text overlay with the text "' + el.content + '" positioned ' +
                        xPercent + '% from the left and ' + yPercent + '% from the top, taking up approximately ' +
                        widthPercent + '% of the canvas width and ' + heightPercent + '% of the canvas height, ' +
                        'in ' + textColor + ' colour, ' + fontSize + ' font size, ' + fontWeight + ' weight'
                    );
                } else if (el.type === 'logo') {
                    overlays.push(
                        'Add a logo overlay positioned ' + xPercent + '% from the left and ' + yPercent + '% from the top, ' +
                        'sized at approximately ' + widthPercent + '% of the canvas width'
                    );
                } else if (el.type === 'icon') {
                    var iconName = el.iconName || 'icon';
                    overlays.push(
                        'Add a ' + iconName + ' icon overlay positioned ' + xPercent + '% from the left and ' + yPercent + '% from the top, ' +
                        'sized at approximately ' + widthPercent + '% of the canvas width'
                    );
                } else if (el.type === 'image') {
                    overlays.push(
                        'Add an image overlay positioned ' + xPercent + '% from the left and ' + yPercent + '% from the top, ' +
                        'sized at approximately ' + widthPercent + '% of the canvas width'
                    );
                }
            });

            // Build the complete prompt with resolution handling instructions
            var prompt = 'Canvas ratio and resolution are defined by the selected aspect ratio in the generation settings (do not infer or override). ';

            if (overlays.length > 0) {
                prompt += overlays.join('. ') + '. ';
            }

            prompt += 'Follow these coordinates exactly relative to the canvas size, not the image content. ' +
                     'Do not render or display these layout instructions or ratio text.';

            return prompt;
        },

        /**
         * Open WordPress media uploader
         */
        openMediaUploader: function(callback) {
            // Check if wp.media is available
            if (typeof wp === 'undefined' || typeof wp.media === 'undefined') {
                alert('WordPress media uploader is not available. Please refresh the page.');
                return;
            }

            // Create media frame
            var frame = wp.media({
                title: 'Select or Upload Image',
                button: {
                    text: 'Use this image'
                },
                multiple: false,
                library: {
                    type: 'image'
                }
            });

            // When an image is selected
            frame.on('select', function() {
                var attachment = frame.state().get('selection').first().toJSON();
                callback(attachment);
            });

            // Open the modal
            frame.open();
        },

        /**
         * Open icon picker modal
         */
        openIconPicker: function(callback) {
            // Common icons with their names and descriptions
            var icons = [
                { name: 'star', icon: 'dashicons-star-filled', desc: 'Star' },
                { name: 'heart', icon: 'dashicons-heart', desc: 'Heart' },
                { name: 'checkmark', icon: 'dashicons-yes', desc: 'Checkmark' },
                { name: 'cross', icon: 'dashicons-no', desc: 'Cross/X' },
                { name: 'arrow-right', icon: 'dashicons-arrow-right-alt', desc: 'Arrow Right' },
                { name: 'arrow-left', icon: 'dashicons-arrow-left-alt', desc: 'Arrow Left' },
                { name: 'arrow-up', icon: 'dashicons-arrow-up-alt', desc: 'Arrow Up' },
                { name: 'arrow-down', icon: 'dashicons-arrow-down-alt', desc: 'Arrow Down' },
                { name: 'location-pin', icon: 'dashicons-location', desc: 'Location Pin' },
                { name: 'phone', icon: 'dashicons-phone', desc: 'Phone' },
                { name: 'email', icon: 'dashicons-email', desc: 'Email' },
                { name: 'cart', icon: 'dashicons-cart', desc: 'Shopping Cart' },
                { name: 'search', icon: 'dashicons-search', desc: 'Search/Magnifying Glass' },
                { name: 'menu', icon: 'dashicons-menu', desc: 'Menu/Hamburger' },
                { name: 'home', icon: 'dashicons-admin-home', desc: 'Home' },
                { name: 'user', icon: 'dashicons-admin-users', desc: 'User/Person' },
                { name: 'settings', icon: 'dashicons-admin-settings', desc: 'Settings/Gear' },
                { name: 'calendar', icon: 'dashicons-calendar', desc: 'Calendar' },
                { name: 'clock', icon: 'dashicons-clock', desc: 'Clock/Time' },
                { name: 'camera', icon: 'dashicons-camera', desc: 'Camera' },
                { name: 'video', icon: 'dashicons-video-alt3', desc: 'Video' },
                { name: 'music', icon: 'dashicons-format-audio', desc: 'Music/Audio' },
                { name: 'download', icon: 'dashicons-download', desc: 'Download' },
                { name: 'upload', icon: 'dashicons-upload', desc: 'Upload' },
                { name: 'share', icon: 'dashicons-share', desc: 'Share' },
                { name: 'lock', icon: 'dashicons-lock', desc: 'Lock/Secure' },
                { name: 'unlock', icon: 'dashicons-unlock', desc: 'Unlock' },
                { name: 'lightbulb', icon: 'dashicons-lightbulb', desc: 'Lightbulb/Idea' },
                { name: 'warning', icon: 'dashicons-warning', desc: 'Warning/Alert' },
                { name: 'info', icon: 'dashicons-info', desc: 'Information' },
                { name: 'plus', icon: 'dashicons-plus', desc: 'Plus/Add' },
                { name: 'minus', icon: 'dashicons-minus', desc: 'Minus/Subtract' }
            ];

            // Create modal HTML
            var modalHtml = `
                <div class="ai-imagen-icon-picker-modal" id="icon-picker-modal">
                    <div class="icon-picker-overlay"></div>
                    <div class="icon-picker-content">
                        <div class="icon-picker-header">
                            <h3>Select an Icon</h3>
                            <button type="button" class="icon-picker-close">
                                <span class="dashicons dashicons-no"></span>
                            </button>
                        </div>
                        <div class="icon-picker-body">
                            <div class="icon-picker-grid">
                                ${icons.map(function(icon) {
                                    return `
                                        <button type="button" class="icon-picker-item" data-icon-name="${icon.name}" data-icon-class="${icon.icon}">
                                            <span class="dashicons ${icon.icon}"></span>
                                            <span class="icon-name">${icon.desc}</span>
                                        </button>
                                    `;
                                }).join('')}
                            </div>
                        </div>
                    </div>
                </div>
            `;

            // Add modal to page
            $('body').append(modalHtml);

            // Handle icon selection
            $(document).on('click', '.icon-picker-item', function() {
                var iconData = {
                    name: $(this).data('icon-name'),
                    iconClass: $(this).data('icon-class')
                };
                $('#icon-picker-modal').remove();
                callback(iconData);
            });

            // Handle close button
            $(document).on('click', '.icon-picker-close, .icon-picker-overlay', function() {
                $('#icon-picker-modal').remove();
            });
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        SceneBuilder.init();
    });

    // Expose to global scope for integration with main generator
    window.AIImagenSceneBuilder = SceneBuilder;

})(jQuery);
