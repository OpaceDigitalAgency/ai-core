/**
 * AI-Core Prompt Library JavaScript
 *
 * @package AI_Core
 * @version 0.1.7
 */

(function($) {
    'use strict';

    /**
     * Prompt Library Object
     */
    const PromptLibrary = {

        currentGroupId: null,
        currentPromptId: null,

        /**
         * Initialize
         */
        init: function() {
            // Check if aiCoreAdmin is available
            if (typeof aiCoreAdmin === 'undefined') {
                console.error('AI-Core: aiCoreAdmin object not found. Cannot initialize Prompt Library.');
                this.showError('Configuration error. Please refresh the page.');
                return;
            }

            console.log('AI-Core Prompt Library initialized');
            this.bindEvents();
            this.initDragDrop();
            this.loadGroups();
            this.loadPrompts();
        },

        /**
         * Bind events
         */
        bindEvents: function() {
            // Group actions
            $(document).on('click', '.ai-core-group-item', this.selectGroup.bind(this));
            $(document).on('click', '#ai-core-new-group', this.showGroupInlineForm.bind(this));
            $(document).on('click', '.edit-group', this.editGroup.bind(this));
            $(document).on('click', '.delete-group', this.deleteGroup.bind(this));
            $(document).on('click', '#ai-core-save-group', this.saveGroup.bind(this));
            $(document).on('click', '#ai-core-cancel-group', this.hideGroupModal.bind(this));
            $(document).on('click', '#ai-core-save-group-inline', this.saveGroupInline.bind(this));
            $(document).on('click', '#ai-core-cancel-group-inline', this.cancelGroupInline.bind(this));


            // Prompt actions
            $(document).on('click', '#ai-core-new-prompt', this.showPromptModal.bind(this));
            $(document).on('click', '#ai-core-new-prompt-empty', this.showPromptModal.bind(this));
            $(document).on('click', '.edit-prompt', this.editPrompt.bind(this));
            $(document).on('click', '.delete-prompt', this.deletePrompt.bind(this));
            $(document).on('click', '#ai-core-save-prompt', this.savePrompt.bind(this));
            $(document).on('click', '#ai-core-cancel-prompt', this.hidePromptModal.bind(this));
            $(document).on('click', '.run-prompt', this.runPromptFromCard.bind(this));
            $(document).on('click', '#ai-core-test-prompt-modal', this.runPromptFromModal.bind(this));

            // Import/Export
            $(document).on('click', '#ai-core-export-prompts', this.exportPrompts.bind(this));
            $(document).on('click', '#ai-core-import-prompts', this.showImportModal.bind(this));
            $(document).on('click', '#ai-core-do-import', this.importPrompts.bind(this));
            $(document).on('click', '#ai-core-cancel-import', this.hideImportModal.bind(this));

            // Search and filters
            $(document).on('input', '#ai-core-search-prompts', this.filterPrompts.bind(this));
            $(document).on('change', '#ai-core-filter-type', this.filterPrompts.bind(this));
            $(document).on('change', '#ai-core-filter-provider', this.filterPrompts.bind(this));

            // Modal close
            $(document).on('click', '.ai-core-modal-close', this.closeModal.bind(this));
            $(document).on('click', '.ai-core-modal', function(e) {
                if ($(e.target).hasClass('ai-core-modal')) {
                    $(e.target).hide().removeClass('active');
                }
            });

            // Escape key to close modals
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape') {
                    $('.ai-core-modal').hide().removeClass('active');
                }
            });
        },

        /**
         * Initialize drag and drop
         */
        initDragDrop: function() {
            if (typeof $.fn.sortable === 'undefined' || typeof $.fn.droppable === 'undefined') {
                console.warn('jQuery UI Sortable/Droppable not available. Drag and drop disabled.');
                return;
            }

            // Make prompts draggable and sortable within grid
            $('.ai-core-prompts-grid').sortable({
                items: '.ai-core-prompt-card',
                placeholder: 'prompt-card-placeholder',
                cursor: 'move',
                opacity: 0.8,
                tolerance: 'pointer',
                connectWith: '.ai-core-group-item',
                helper: 'clone',
                start: (event, ui) => {
                    ui.item.addClass('dragging');
                    $('.ai-core-groups-list').addClass('drop-active');
                },
                stop: (event, ui) => {
                    ui.item.removeClass('dragging');
                    $('.ai-core-groups-list').removeClass('drop-active');
                },
                update: (event, ui) => {
                    // Only handle if dropped within the same grid (reordering)
                    if (ui.item.parent().hasClass('ai-core-prompts-grid')) {
                        const promptId = ui.item.data('prompt-id');
                        const newGroupId = this.currentGroupId;
                        this.movePrompt(promptId, newGroupId);
                    }
                }
            });

            // Make group items droppable
            $('.ai-core-groups-list').on('mouseenter', '.ai-core-group-item', function() {
                $(this).addClass('drop-hover');
            }).on('mouseleave', '.ai-core-group-item', function() {
                $(this).removeClass('drop-hover');
            });

            // Handle drop on group items
            $(document).on('drop', '.ai-core-group-item', (e) => {
                e.preventDefault();
                const $target = $(e.currentTarget);
                const newGroupId = $target.data('group-id');

                // Get the dragged prompt ID from the dragging element
                const $dragging = $('.ai-core-prompt-card.dragging');
                if ($dragging.length) {
                    const promptId = $dragging.data('prompt-id');
                    this.movePromptToGroup(promptId, newGroupId);
                }

                $target.removeClass('drop-hover');
            });

            $(document).on('dragover', '.ai-core-group-item', (e) => {
                e.preventDefault();
            });
        },

        /**
         * Show error message
         */
        showError: function(message) {
            const $notice = $('<div class="notice notice-error is-dismissible"><p>' + this.escapeHtml(message) + '</p></div>');
            $('.ai-core-prompt-library h1').after($notice);
            setTimeout(() => $notice.fadeOut(() => $notice.remove()), 5000);
        },

        /**
         * Show success message
         */
        showSuccess: function(message) {
            const $notice = $('<div class="notice notice-success is-dismissible"><p>' + this.escapeHtml(message) + '</p></div>');
            $('.ai-core-prompt-library h1').after($notice);
            setTimeout(() => $notice.fadeOut(() => $notice.remove()), 3000);
        },

        /**
         * Load groups
         */
        loadGroups: function() {
            $.ajax({
                url: aiCoreAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ai_core_get_groups',
                    nonce: aiCoreAdmin.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.renderGroups(response.data.groups);
                    } else {
                        this.showError(response.data.message || 'Failed to load groups');
                    }
                },
                error: (xhr, status, error) => {
                    console.error('Error loading groups:', error);
                    this.showError('Network error loading groups');
                }
            });
        },

        /**
         * Render groups
         */
        renderGroups: function(groups) {
            const $list = $('#ai-core-groups-list');
            if (!$list.length) {
                console.error('Groups list element not found');
                return;
            }

            $list.empty();

            // Add "All Prompts" option
            $list.append(`
                <li class="ai-core-group-item ${this.currentGroupId === null ? 'active' : ''}" data-group-id="">
                    <span class="group-name">All Prompts</span>
                </li>
            `);

            groups.forEach(group => {
                const isActive = this.currentGroupId === group.id;
                $list.append(`
                    <li class="ai-core-group-item ${isActive ? 'active' : ''}" data-group-id="${group.id}">
                        <span class="group-name">${this.escapeHtml(group.name)}</span>
                        <span class="group-count">${group.count || 0}</span>
                        <span class="group-actions">
                            <button type="button" class="button-link edit-group" data-group-id="${group.id}" title="Edit">
                                <span class="dashicons dashicons-edit"></span>
                            </button>
                            <button type="button" class="button-link delete-group" data-group-id="${group.id}" title="Delete">
                                <span class="dashicons dashicons-trash"></span>
                            </button>
                        </span>
                    </li>
                `);
            });
        },

        /**
         * Select group
         */
        selectGroup: function(e) {
            e.stopPropagation();
            const $item = $(e.currentTarget);
            const groupId = $item.data('group-id');

            this.currentGroupId = groupId || null;
            $('.ai-core-group-item').removeClass('active');
            $item.addClass('active');

            this.loadPrompts();
        },

        /**
         * Load prompts
         */
        loadPrompts: function() {
            const searchTerm = $('#ai-core-search-prompts').val();
            const filterType = $('#ai-core-filter-type').val();
            const filterProvider = $('#ai-core-filter-provider').val();

            const $grid = $('#ai-core-prompts-grid');
            $grid.html('<div class="loading-spinner"><span class="dashicons dashicons-update spin"></span> Loading prompts...</div>');

            $.ajax({
                url: aiCoreAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ai_core_get_prompts',
                    nonce: aiCoreAdmin.nonce,
                    group_id: this.currentGroupId,
                    search: searchTerm,
                    type: filterType,
                    provider: filterProvider
                },
                success: (response) => {
                    if (response.success) {
                        this.renderPrompts(response.data.prompts);
                    } else {
                        $grid.html('<div class="error-message">Failed to load prompts</div>');
                        this.showError(response.data.message || 'Failed to load prompts');
                    }
                },
                error: (xhr, status, error) => {
                    console.error('Error loading prompts:', error);
                    $grid.html('<div class="error-message">Network error loading prompts</div>');
                    this.showError('Network error loading prompts');
                }
            });
        },

        /**
         * Render prompts
         */
        renderPrompts: function(prompts) {
            const $grid = $('#ai-core-prompts-grid');
            if (!$grid.length) {
                console.error('Prompts grid element not found');
                return;
            }

            $grid.empty();

            if (prompts.length === 0) {
                $grid.append(`
                    <div class="ai-core-empty-state">
                        <span class="dashicons dashicons-admin-post"></span>
                        <h3>No prompts found</h3>
                        <p>Create your first prompt to get started.</p>
                        <button type="button" class="button button-primary" id="ai-core-new-prompt-empty">
                            <span class="dashicons dashicons-plus-alt"></span>
                            New Prompt
                        </button>
                    </div>
                `);
                return;
            }

            prompts.forEach(prompt => {
                const excerpt = this.truncateText(prompt.content, 100);
                const typeIcon = prompt.type === 'image' ? 'format-image' : 'text';

                $grid.append(`
                    <div class="ai-core-prompt-card" data-prompt-id="${prompt.id}">
                        <div class="prompt-card-header">
                            <h4>${this.escapeHtml(prompt.title)}</h4>
                            <div class="prompt-card-actions">
                                <button type="button" class="button-link edit-prompt" data-prompt-id="${prompt.id}">
                                    <span class="dashicons dashicons-edit"></span>
                                </button>
                                <button type="button" class="button-link delete-prompt" data-prompt-id="${prompt.id}">
                                    <span class="dashicons dashicons-trash"></span>
                                </button>
                            </div>
                        </div>
                        <div class="prompt-card-body">
                            <p>${this.escapeHtml(excerpt)}</p>
                        </div>
                        <div class="prompt-card-footer">
                            <span class="prompt-type">
                                <span class="dashicons dashicons-${typeIcon}"></span>
                                ${this.escapeHtml(prompt.type || 'text')}
                            </span>
                            <span class="prompt-provider">${this.escapeHtml(prompt.provider || 'default')}</span>
                            <button type="button" class="button button-small run-prompt" data-prompt-id="${prompt.id}">
                                <span class="dashicons dashicons-controls-play"></span>
                                Run
                            </button>
                        </div>
                        </div>
                    </div>
                `);
            });
        },


        /**
         * Show inline Group form
         */
        showGroupInlineForm: function(e) {
            if (e) e.preventDefault();

            const $list = $('#ai-core-groups-list');
            if (!$list.length) return;

            // If form already visible, focus it
            if ($list.find('.ai-core-group-inline-form').length) {
                $list.find('.ai-core-group-inline-form input[name="group-name"]').focus();
                return;
            }

            const $li = $(
                '<li class="ai-core-group-item ai-core-group-inline-form" aria-live="polite">' +
                    '<div style="display:flex; flex-direction:column; gap:6px; width:100%">' +
                        '<input type="text" name="group-name" class="regular-text" placeholder="Group name" />' +
                        '<textarea name="group-description" rows="2" class="large-text" placeholder="Description (optional)"></textarea>' +
                        '<div style="display:flex; gap:6px; justify-content:flex-end">' +
                            '<button type="button" class="button button-primary" id="ai-core-save-group-inline">Save Group</button>' +
                            '<button type="button" class="button" id="ai-core-cancel-group-inline">Cancel</button>' +
                        '</div>' +
                    '</div>' +
                '</li>'
            );

            $list.prepend($li);
            $li.find('input[name="group-name"]').focus();
        },

        /**
         * Handle inline group save/cancel
         */
        saveGroupInline: function(e) {
            e.preventDefault();
            const $form = $('.ai-core-group-inline-form');
            if (!$form.length) return;
            const name = $form.find('input[name="group-name"]').val().trim();
            const description = $form.find('textarea[name="group-description"]').val().trim();
            if (!name) {
                this.showError('Please enter a group name');
                return;
            }

            $.ajax({
                url: aiCoreAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ai_core_save_group',
                    nonce: aiCoreAdmin.nonce,
                    name: name,
                    description: description
                },
                success: (response) => {
                    if (response.success) {
                        this.showSuccess('Group created');
                        $form.remove();
                        this.loadGroups();
                    } else {
                        this.showError(response.data.message || 'Failed to save group');
                    }
                },
                error: () => this.showError('Network error saving group')
            });
        },

        cancelGroupInline: function(e) {
            if (e) e.preventDefault();
            $('.ai-core-group-inline-form').remove();
        },

        /**
         * Filter prompts
         */
        filterPrompts: function() {
            this.loadPrompts();
        },

        /**
         * Show group modal
         */
        showGroupModal: function(e) {
            if (e) e.preventDefault();

            $('#group-id').val('');
            $('#group-name').val('');
            $('#group-description').val('');
            $('#ai-core-group-modal-title').text('New Group');
            $('#ai-core-group-modal').show().addClass('active');
        },

        /**
         * Hide group modal
         */
        hideGroupModal: function(e) {
            if (e) e.preventDefault();
            $('#ai-core-group-modal').hide().removeClass('active');
        },

        /**
         * Edit group
         */
        editGroup: function(e) {
            e.preventDefault();
            e.stopPropagation();

            const groupId = $(e.currentTarget).data('group-id');

            // Get group data via AJAX
            $.ajax({
                url: aiCoreAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ai_core_get_groups',
                    nonce: aiCoreAdmin.nonce
                },
                success: (response) => {
                    if (response.success) {
                        const group = response.data.groups.find(g => g.id == groupId);
                        if (group) {
                            $('#group-id').val(group.id);
                            $('#group-name').val(group.name);
                            $('#group-description').val(group.description || '');
                            $('#ai-core-group-modal-title').text('Edit Group');
                            $('#ai-core-group-modal').show().addClass('active');
                        }
                    } else {
                        this.showError('Failed to load group data');
                    }
                },
                error: (xhr, status, error) => {
                    console.error('Error loading group:', error);
                    this.showError('Network error loading group');
                }
            });
        },

        /**
         * Save group
         */
        saveGroup: function(e) {
            e.preventDefault();

            const groupId = $('#group-id').val();
            const name = $('#group-name').val();
            const description = $('#group-description').val();

            if (!name) {
                alert('Please enter a group name');
                return;
            }

            $.ajax({
                url: aiCoreAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ai_core_save_group',
                    nonce: aiCoreAdmin.nonce,
                    group_id: groupId,
                    name: name,
                    description: description
                },
                success: (response) => {
                    if (response.success) {
                        this.hideGroupModal();
                        this.loadGroups();
                        this.loadPrompts();
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                },
                error: (xhr, status, error) => {
                    alert('Error saving group: ' + error);
                }
            });
        },

        /**
         * Delete group
         */
        deleteGroup: function(e) {
            e.preventDefault();
            e.stopPropagation();

            if (!confirm('Are you sure you want to delete this group? Prompts in this group will not be deleted.')) {
                return;
            }

            const groupId = $(e.currentTarget).data('group-id');

            $.ajax({
                url: aiCoreAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ai_core_delete_group',
                    nonce: aiCoreAdmin.nonce,
                    group_id: groupId
                },
                success: (response) => {
                    if (response.success) {
                        this.currentGroupId = null;
                        this.loadGroups();
                        this.loadPrompts();
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                },
                error: (xhr, status, error) => {
                    alert('Error deleting group: ' + error);
                }
            });
        },

        /**
         * Show prompt modal
         */
        showPromptModal: function(e) {
            console.log('showPromptModal called');
            if (e) e.preventDefault();

            $('#prompt-id').val('');
            $('#prompt-title').val('');
            $('#prompt-content').val('');
            $('#prompt-group').val(this.currentGroupId || '');
            $('#prompt-provider').val('');
            $('#prompt-type').val('text');
            $('#ai-core-modal-title').text('New Prompt');
            $('#ai-core-prompt-result').hide().html('');

            const $modal = $('#ai-core-prompt-modal');
            console.log('Modal found:', $modal.length);
            $modal.addClass('active');
            console.log('Modal active class added');
        },

        /**
         * Hide prompt modal
         */
        hidePromptModal: function(e) {
            if (e) e.preventDefault();
            $('#ai-core-prompt-modal').removeClass('active');
        },

        /**
         * Edit prompt
         */
        editPrompt: function(e) {
            e.preventDefault();
            e.stopPropagation();

            const promptId = $(e.currentTarget).data('prompt-id');

            // Get prompt data via AJAX
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
                            $('#prompt-id').val(prompt.id);
                            $('#prompt-title').val(prompt.title);
                            $('#prompt-content').val(prompt.content);
                            $('#prompt-group').val(prompt.group_id || '');
                            $('#prompt-provider').val(prompt.provider || '');
                            $('#prompt-type').val(prompt.type || 'text');
                            $('#ai-core-modal-title').text('Edit Prompt');
                            $('#ai-core-prompt-result').hide().html('');
                            $('#ai-core-prompt-modal').addClass('active');
                        }
                    }
                }
            });
        },

        /**
         * Save prompt
         */
        savePrompt: function(e) {
            e.preventDefault();

            const promptId = $('#prompt-id').val();
            const title = $('#prompt-title').val();
            const content = $('#prompt-content').val();
            const groupId = $('#prompt-group').val();
            const provider = $('#prompt-provider').val();
            const type = $('#prompt-type').val();

            if (!title || !content) {
                alert('Please enter a title and content');
                return;
            }

            $.ajax({
                url: aiCoreAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ai_core_save_prompt',
                    nonce: aiCoreAdmin.nonce,
                    prompt_id: promptId,
                    title: title,
                    content: content,
                    group_id: groupId,
                    provider: provider,
                    type: type
                },
                success: (response) => {
                    if (response.success) {
                        this.hidePromptModal();
                        this.loadGroups();
                        this.loadPrompts();
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                },
                error: (xhr, status, error) => {
                    alert('Error saving prompt: ' + error);
                }
            });
        },

        /**
         * Move prompt to a different group (via drag and drop)
         */
        movePromptToGroup: function(promptId, newGroupId) {
            if (!promptId || !newGroupId) {
                return;
            }

            $.ajax({
                url: aiCoreAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ai_core_move_prompt',
                    nonce: aiCoreAdmin.nonce,
                    prompt_id: promptId,
                    group_id: newGroupId
                },
                success: (response) => {
                    if (response.success) {
                        this.showSuccess('Prompt moved successfully');
                        this.loadPrompts();
                        this.loadGroups();
                    } else {
                        this.showError(response.data.message || 'Failed to move prompt');
                    }
                },
                error: (xhr, status, error) => {
                    console.error('Error moving prompt:', error);
                    this.showError('Network error moving prompt');
                }
            });
        },

        /**
         * Move prompt (reorder within same group)
         */
        movePrompt: function(promptId, groupId) {
            // This is for reordering within the same group
            // For now, we'll just reload to show the new order
            console.log('Reordering prompt', promptId, 'in group', groupId);
        },

        /**
         * Delete prompt
         */
        deletePrompt: function(e) {
            e.preventDefault();
            e.stopPropagation();

            if (!confirm('Are you sure you want to delete this prompt?')) {
                return;
            }

            const promptId = $(e.currentTarget).data('prompt-id');

            $.ajax({
                url: aiCoreAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ai_core_delete_prompt',
                    nonce: aiCoreAdmin.nonce,
                    prompt_id: promptId
                },
                success: (response) => {
                    if (response.success) {
                        this.loadGroups();
                        this.loadPrompts();
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                },
                error: (xhr, status, error) => {
                    alert('Error deleting prompt: ' + error);
                }
            });
        },

        /**
         * Run prompt from card
         */
        runPromptFromCard: function(e) {
            e.preventDefault();
            e.stopPropagation();

            const promptId = $(e.currentTarget).data('prompt-id');

            // Get prompt data and run it
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
                            this.runPrompt(prompt.content, prompt.provider, prompt.type);
                        }
                    }
                }
            });
        },

        /**
         * Run prompt from modal
         */
        runPromptFromModal: function(e) {
            e.preventDefault();

            const content = $('#prompt-content').val();
            const provider = $('#prompt-provider').val();
            const type = $('#prompt-type').val();

            if (!content) {
                alert('Please enter prompt content');
                return;
            }

            this.runPrompt(content, provider, type);
        },

        /**
         * Run prompt
         */
        runPrompt: function(content, provider, type) {
            const $result = $('#ai-core-prompt-result');
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
                            $result.html(`<img src="${response.data.result}" alt="Generated image" />`);
                        } else {
                            $result.html(`<pre>${this.escapeHtml(response.data.result)}</pre>`);
                        }
                    } else {
                        $result.html(`<div class="error">Error: ${this.escapeHtml(response.data.message)}</div>`);
                    }
                },
                error: (xhr, status, error) => {
                    $result.html(`<div class="error">Error: ${this.escapeHtml(error)}</div>`);
                }
            });
        },

        /**
         * Export prompts
         */
        exportPrompts: function(e) {
            console.log('exportPrompts called');
            e.preventDefault();

            $.ajax({
                url: aiCoreAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'ai_core_export_prompts',
                    nonce: aiCoreAdmin.nonce
                },
                success: (response) => {
                    console.log('Export response:', response);
                    if (response.success) {
                        const dataStr = JSON.stringify(response.data.data, null, 2);
                        const dataBlob = new Blob([dataStr], {type: 'application/json'});
                        const url = URL.createObjectURL(dataBlob);
                        const link = document.createElement('a');
                        link.href = url;
                        link.download = response.data.filename || 'ai-core-prompts-export.json';
                        link.click();
                        URL.revokeObjectURL(url);
                        console.log('Export download triggered');
                        this.showSuccess('Prompts exported successfully!');
                    } else {
                        this.showError(response.data.message || 'Error exporting prompts');
                    }
                },
                error: (xhr, status, error) => {
                    console.error('Export failed:', status, error);
                    this.showError('Network error exporting prompts: ' + error);
                }
            });
        },

        /**
         * Show import modal
         */
        showImportModal: function(e) {
            console.log('showImportModal called');
            e.preventDefault();
            $('#ai-core-import-file').val('');
            const $modal = $('#ai-core-import-modal');
            console.log('Import modal found:', $modal.length);
            $modal.addClass('active');
            console.log('Import modal active class added');
        },

        /**
         * Hide import modal
         */
        hideImportModal: function(e) {
            if (e) e.preventDefault();
            $('#ai-core-import-modal').removeClass('active');
        },

        /**
         * Import prompts
         */
        importPrompts: function(e) {
            e.preventDefault();

            const fileInput = document.getElementById('ai-core-import-file');
            const file = fileInput.files[0];

            if (!file) {
                this.showError('Please select a file');
                return;
            }

            const reader = new FileReader();
            reader.onload = (e) => {
                try {
                    const data = JSON.parse(e.target.result);

                    $.ajax({
                        url: aiCoreAdmin.ajaxUrl,
                        type: 'POST',
                        data: {
                            action: 'ai_core_import_prompts',
                            nonce: aiCoreAdmin.nonce,
                            import_data: JSON.stringify(data)
                        },
                        success: (response) => {
                            if (response.success) {
                                this.hideImportModal();
                                this.loadGroups();
                                this.loadPrompts();
                                this.showSuccess(response.data.message || 'Import successful!');
                            } else {
                                this.showError(response.data.message || 'Error importing prompts');
                            }
                        },
                        error: (xhr, status, error) => {
                            this.showError('Network error importing prompts: ' + error);
                        }
                    });
                } catch (error) {
                    this.showError('Invalid JSON file: ' + error.message);
                }
            };
            reader.readAsText(file);
        },

        /**
         * Close modal
         */
        closeModal: function(e) {
            $(e.currentTarget).closest('.ai-core-modal').removeClass('active');
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
            return text.replace(/[&<>"']/g, m => map[m]);
        },

        /**
         * Truncate text
         */
        truncateText: function(text, length) {
            if (text.length <= length) return text;
            return text.substr(0, length) + '...';
        }
    };

    /**
     * Initialize on document ready
     */
    $(document).ready(function() {
        console.log('Prompt Library script loaded');
        console.log('jQuery version:', $.fn.jquery);
        console.log('aiCoreAdmin available:', typeof aiCoreAdmin !== 'undefined');
        console.log('.ai-core-prompt-library found:', $('.ai-core-prompt-library').length);

        if ($('.ai-core-prompt-library').length) {
            console.log('Initializing Prompt Library...');
            PromptLibrary.init();
        } else {
            console.warn('Prompt Library container not found. Skipping initialization.');
        }
    });

})(jQuery);

