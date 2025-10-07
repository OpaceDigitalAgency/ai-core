/**
 * AI-Imagen Scene Builder JavaScript
 *
 * Scene builder functionality for adding elements to images
 *
 * @package AI_Imagen
 * @version 0.3.7
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
                        <div class="scene-builder-canvas" id="scene-canvas">
                            <div class="scene-canvas-placeholder">
                                <span class="dashicons dashicons-images-alt2"></span>
                                <p>Add elements to build your scene</p>
                            </div>
                        </div>
                        <div class="scene-builder-properties" id="scene-properties" style="display: none;">
                            <h4>Element Properties</h4>
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
                imageUrl: ''
            };

            // Prompt for content based on type
            if (type === 'text') {
                var text = prompt('Enter text:', 'Your Text Here');
                if (text) {
                    element.content = text;
                }
            } else if (type === 'logo' || type === 'icon' || type === 'image') {
                var url = prompt('Enter image URL:', '');
                if (url) {
                    element.imageUrl = url;
                }
            }

            this.elements.push(element);
            this.renderElement(element);
            this.selectElement(elementId);
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
            } else if (element.type === 'logo' || element.type === 'icon' || element.type === 'image') {
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
            this.deselectAll();

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

            // Populate properties
            $('#element-content').val(this.selectedElement.content);
            $('#element-font-size').val(this.selectedElement.fontSize);
            $('#element-color').val(this.selectedElement.color);
            $('#element-font-weight').val(this.selectedElement.fontWeight);
            $('#element-x').val(this.selectedElement.x);
            $('#element-y').val(this.selectedElement.y);
            $('#element-width').val(this.selectedElement.width);
            $('#element-height').val(this.selectedElement.height);

            // Show/hide relevant fields
            if (this.selectedElement.type === 'text') {
                $('#element-content, #element-font-size, #element-color, #element-font-weight').closest('.property-group').show();
            } else {
                $('#element-content, #element-font-size, #element-color, #element-font-weight').closest('.property-group').hide();
            }

            $props.slideDown();
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

            // Keep within canvas bounds
            newX = Math.max(0, newX);
            newY = Math.max(0, newY);

            this.selectedElement.x = newX;
            this.selectedElement.y = newY;

            var $element = $('.scene-element[data-id="' + this.selectedElement.id + '"]');
            $element.css({
                left: newX + 'px',
                top: newY + 'px'
            });

            // Update properties panel
            $('#element-x').val(newX);
            $('#element-y').val(newY);
        },

        /**
         * Stop dragging
         */
        stopDrag: function() {
            if (this.isDragging) {
                $('.scene-element').removeClass('dragging');
                this.isDragging = false;
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

            this.selectedElement.width = newWidth;
            this.selectedElement.height = newHeight;

            var $element = $('.scene-element[data-id="' + this.selectedElement.id + '"]');
            $element.css({
                width: newWidth + 'px',
                height: newHeight + 'px'
            });

            // Update properties panel
            $('#element-width').val(newWidth);
            $('#element-height').val(newHeight);
        },

        /**
         * Stop resizing
         */
        stopResize: function() {
            this.isResizing = false;
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
            $element.css(property, value);
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
         * Get scene elements data for generation
         */
        getSceneData: function() {
            return this.elements.map(function(el) {
                return {
                    type: el.type,
                    content: el.content,
                    x: el.x,
                    y: el.y,
                    width: el.width,
                    height: el.height,
                    fontSize: el.fontSize,
                    color: el.color,
                    fontWeight: el.fontWeight,
                    imageUrl: el.imageUrl
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

            var descriptions = [];

            this.elements.forEach(function(el) {
                if (el.type === 'text') {
                    descriptions.push('text saying "' + el.content + '"');
                } else if (el.type === 'logo') {
                    descriptions.push('logo');
                } else if (el.type === 'icon') {
                    descriptions.push('icon');
                } else if (el.type === 'image') {
                    descriptions.push('image element');
                }
            });

            return 'Include ' + descriptions.join(', ');
        }
    };

    // Initialize on document ready
    $(document).ready(function() {
        SceneBuilder.init();
    });

    // Expose to global scope for integration with main generator
    window.AIImagenSceneBuilder = SceneBuilder;

})(jQuery);
