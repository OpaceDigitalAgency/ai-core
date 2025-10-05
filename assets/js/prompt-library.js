/**
 * AI-Core Prompt Library JavaScript
 *
 * @package AI_Core
 * @version 0.1.8
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
            $(document).on('click', '#ai-core-new-group', this.showGroupModal.bind(this));
            $(document).on('click', '#ai-core-new-group-empty', this.showGroupModal.bind(this));
            $(document).on('click', '.edit-group', this.editGroup.bind(this));
            $(document).on('click', '.delete-group', this.deleteGroup.bind(this));
            $(document).on('click', '.add-prompt-to-group', this.addPromptToGroup.bind(this));
            $(document).on('click', '#ai-core-save-group', this.saveGroup.bind(this));
            $(document).on('click', '#ai-core-cancel-group', this.hideGroupModal.bind(this));

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
            $(document).on('change', '#ai-core-filter-group', this.filterPrompts.bind(this));

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
            if (typeof $.fn.sortable === 'undefined') {
                console.warn('jQuery UI Sortable not available. Drag and drop disabled.');
                return;
            }

            // Make group cards sortable (reorder groups)
            $('.ai-core-groups-container').sortable({
                items: '.ai-core-group-card',
                handle: '.group-card-header',
                placeholder: 'group-card-placeholder',
                cursor: 'move',
                opacity: 0.8,
                tolerance: 'pointer',
                helper: 'clone',
                zIndex: 10000,
                start: (event, ui) => {
                    ui.item.addClass('dragging');
                    ui.helper.addClass('dragging-helper');
                },
                stop: (event, ui) => {
                    ui.item.removeClass('dragging');
                },
                update: (event, ui) => {
                    console.log('Group reordered');
                    // Could save group order here if needed
                }
            });

            // Make prompts sortable within each group card body
            $('.group-card-body').sortable({
                items: '.ai-core-prompt-card',
                placeholder: 'prompt-card-placeholder',
                cursor: 'move',
                opacity: 0.7,
                tolerance: 'pointer',
                handle: '.prompt-card-header',
                helper: 'clone',
                connectWith: '.group-card-body',
                appendTo: 'body',
                zIndex: 10000,
                start: (event, ui) => {
                    ui.item.addClass('dragging');
                    ui.helper.addClass('dragging-helper');
                    $('.group-card-body').addClass('drop-target-active');
                },
                stop: (event, ui) => {
                    ui.item.removeClass('dragging');
                    $('.group-card-body').removeClass('drop-target-active');
                },
                over: (event, ui) => {
                    $(event.target).addClass('drop-hover');
                },
                out: (event, ui) => {
                    $(event.target).removeClass('drop-hover');
                },
                receive: (event, ui) => {
                    // Prompt moved to a different group
                    const $target = $(event.target);
                    const newGroupId = $target.data('group-id');
                    const $dragged = ui.item;
                    const promptId = $dragged.data('prompt-id');

                    console.log('Moving prompt', promptId, 'to group', newGroupId);

                    if (promptId && newGroupId !== undefined) {
                        this.movePromptToGroup(promptId, newGroupId);
                    }
                },
                update: (event, ui) => {
                    // Only handle if item wasn't received from another list
                    if (!ui.sender) {
                        console.log('Prompt reordered within same group');
                    }
                }
            });
        },

        /**
         * Reinitialize drag and drop after content update
         */
        reinitDragDrop: function() {
            // Destroy existing sortables
            if ($('.ai-core-groups-container').hasClass('ui-sortable')) {
                $('.ai-core-groups-container').sortable('destroy');
            }

            $('.group-card-body').each(function() {
                if ($(this).hasClass('ui-sortable')) {
                    $(this).sortable('destroy');
                }
            });

            // Reinitialize
            this.initDragDrop();
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
                const groupName = prompt.group_name || 'Ungrouped';

                $grid.append(`
                    <div class="ai-core-prompt-card" data-prompt-id="${prompt.id}" data-group-id="${prompt.group_id || ''}">
                        <div class="prompt-card-header">
                            <div class="prompt-card-title-group">
                                <h4>${this.escapeHtml(prompt.title)}</h4>
                                <span class="prompt-group-badge">${this.escapeHtml(groupName)}</span>
                            </div>
                            <div class="prompt-card-actions">
                                <button type="button" class="button-link edit-prompt" data-prompt-id="${prompt.id}" title="Edit">
                                    <span class="dashicons dashicons-edit"></span>
                                </button>
                                <button type="button" class="button-link delete-prompt" data-prompt-id="${prompt.id}" title="Delete">
                                    <span class="dashicons dashicons-trash"></span>
                                </button>
                            </div>
                        </div>
                        <div class="prompt-card-body">
                            <p>${this.escapeHtml(excerpt)}</p>
                        </div>
                        <div class="prompt-card-footer">
                            <div class="prompt-card-meta">
                                <span class="prompt-type">
                                    <span class="dashicons dashicons-${typeIcon}"></span>
                                    ${this.escapeHtml(prompt.type || 'text')}
                                </span>
                                <span class="prompt-provider">${this.escapeHtml(prompt.provider || 'default')}</span>
                            </div>
                            <button type="button" class="button button-small run-prompt" data-prompt-id="${prompt.id}">
                                <span class="dashicons dashicons-controls-play"></span>
                                Run
                            </button>
                        </div>
                    </div>
                `);
            });

            // Reinitialize drag and drop after rendering
            this.reinitDragDrop();
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
         * Show group modal
         */
        showGroupModal: function(e) {
            if (e) e.preventDefault();

            const $modal = $('#ai-core-group-modal');
            if (!$modal.length) {
                console.error('Group modal not found');
                return;
            }

            // Reset form
            $('#group-id').val('');
            $('#group-name').val('');
            $('#group-description').val('');
            $('#ai-core-group-modal-title').text('New Group');

            $modal.show().addClass('active');
            $('#group-name').focus();
        },

        /**
         * Add prompt to specific group
         */
        addPromptToGroup: function(e) {
            e.preventDefault();
            e.stopPropagation();

            const $button = $(e.currentTarget);
            const $groupCard = $button.closest('.ai-core-group-card');
            const groupId = $groupCard.data('group-id');

            // Store the target group ID and show prompt modal
            this.currentGroupId = groupId;
            this.showPromptModal(e);
        },

        /**
         * Filter prompts
         */
        filterPrompts: function() {
            const searchTerm = $('#ai-core-search-prompts').val().toLowerCase();
            const filterGroup = $('#ai-core-filter-group').val();

            $('.ai-core-group-card').each(function() {
                const $groupCard = $(this);
                const groupId = $groupCard.data('group-id');

                // Filter by group
                if (filterGroup && filterGroup != groupId) {
                    $groupCard.hide();
                    return;
                }

                // Filter prompts within group by search term
                let visibleCount = 0;
                $groupCard.find('.ai-core-prompt-card').each(function() {
                    const $card = $(this);
                    const title = $card.find('.prompt-card-title').text().toLowerCase();
                    const content = $card.find('.prompt-card-content').text().toLowerCase();

                    if (!searchTerm || title.includes(searchTerm) || content.includes(searchTerm)) {
                        $card.show();
                        visibleCount++;
                    } else {
                        $card.hide();
                    }
                });

                // Show/hide group based on visible prompts
                if (visibleCount > 0 || !searchTerm) {
                    $groupCard.show();
                    $groupCard.find('.group-count').text(visibleCount);
                } else {
                    $groupCard.hide();
                }
            });
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

            const $button = $(e.currentTarget);
            const $groupCard = $button.closest('.ai-core-group-card');
            const groupId = $groupCard.data('group-id');

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
                        this.showSuccess('Group saved successfully');
                        setTimeout(() => {
                            window.location.reload();
                        }, 500);
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

            const $button = $(e.currentTarget);
            const $groupCard = $button.closest('.ai-core-group-card');
            const groupId = $groupCard.data('group-id');

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
                        this.showSuccess('Group deleted successfully');
                        setTimeout(() => {
                            window.location.reload();
                        }, 500);
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
                        this.showSuccess('Prompt saved successfully');
                        setTimeout(() => {
                            window.location.reload();
                        }, 500);
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
            if (promptId === undefined || newGroupId === undefined) {
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
                        // Reload page to show updated layout
                        setTimeout(() => {
                            window.location.reload();
                        }, 500);
                    } else {
                        this.showError(response.data.message || 'Failed to move prompt');
                        // Reload anyway to reset UI
                        setTimeout(() => {
                            window.location.reload();
                        }, 1500);
                    }
                },
                error: (xhr, status, error) => {
                    console.error('Error moving prompt:', error);
                    this.showError('Network error moving prompt');
                    // Reload anyway to reset UI
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
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
                        this.showSuccess('Prompt deleted successfully');
                        setTimeout(() => {
                            window.location.reload();
                        }, 500);
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

            const $button = $(e.currentTarget);
            const $card = $button.closest('.ai-core-prompt-card');
            const promptId = $button.data('prompt-id');

            // Show immediate loading feedback
            $button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Running...');

            // Remove any existing result
            $card.find('.prompt-card-result').remove();

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
                            this.runPromptInCard($card, prompt.content, prompt.provider, prompt.type, $button);
                        } else {
                            this.showCardError($card, 'Prompt not found', $button);
                        }
                    } else {
                        this.showCardError($card, response.data?.message || 'Failed to load prompt', $button);
                    }
                },
                error: (xhr, status, error) => {
                    this.showCardError($card, 'Network error: ' + error, $button);
                }
            });
        },

        /**
         * Run prompt in card
         */
        runPromptInCard: function($card, content, provider, type, $button) {
            // Create result container
            const $result = $('<div class="prompt-card-result"><div class="loading"><span class="ai-core-spinner"></span> Generating response...</div></div>');
            $card.find('.prompt-card-footer').after($result);

            // If no provider specified, try to get default from settings
            if (!provider || provider === 'default' || provider === '') {
                provider = 'openai'; // Default fallback
            }

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
                            $result.html(`
                                <div class="result-success">
                                    <img src="${response.data.result}" alt="Generated image" style="max-width: 100%; height: auto; border-radius: 4px;" />
                                </div>
                            `);
                        } else {
                            $result.html(`
                                <div class="result-success">
                                    <pre>${this.escapeHtml(response.data.result)}</pre>
                                </div>
                            `);
                        }
                    } else {
                        $result.html(`
                            <div class="result-error">
                                <span class="dashicons dashicons-warning"></span>
                                <strong>Error:</strong> ${this.escapeHtml(response.data?.message || 'Unknown error')}
                            </div>
                        `);
                    }

                    // Reset button
                    $button.prop('disabled', false).html('<span class="dashicons dashicons-controls-play"></span> Run');
                },
                error: (xhr, status, error) => {
                    let errorMsg = 'Network error';
                    if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                        errorMsg = xhr.responseJSON.data.message;
                    } else if (error) {
                        errorMsg = error;
                    }

                    $result.html(`
                        <div class="result-error">
                            <span class="dashicons dashicons-warning"></span>
                            <strong>Error:</strong> ${this.escapeHtml(errorMsg)}
                        </div>
                    `);

                    // Reset button
                    $button.prop('disabled', false).html('<span class="dashicons dashicons-controls-play"></span> Run');
                }
            });
        },

        /**
         * Show error in card
         */
        showCardError: function($card, message, $button) {
            const $result = $('<div class="prompt-card-result"><div class="result-error"><span class="dashicons dashicons-warning"></span> <strong>Error:</strong> ' + this.escapeHtml(message) + '</div></div>');
            $card.find('.prompt-card-footer').after($result);

            // Reset button
            $button.prop('disabled', false).html('<span class="dashicons dashicons-controls-play"></span> Run');
        },

        /**
         * Run prompt from modal
         */
        runPromptFromModal: function(e) {
            e.preventDefault();

            const content = $('#prompt-content').val();
            const provider = $('#prompt-provider').val() || 'openai';
            const type = $('#prompt-type').val();

            if (!content) {
                alert('Please enter prompt content');
                return;
            }

            this.runPrompt(content, provider, type);
        },

        /**
         * Run prompt in modal
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
                            $result.html(`<img src="${response.data.result}" alt="Generated image" style="max-width: 100%; height: auto;" />`);
                        } else {
                            $result.html(`<pre>${this.escapeHtml(response.data.result)}</pre>`);
                        }
                    } else {
                        $result.html(`<div class="error"><span class="dashicons dashicons-warning"></span> Error: ${this.escapeHtml(response.data?.message || 'Unknown error')}</div>`);
                    }
                },
                error: (xhr, status, error) => {
                    let errorMsg = 'Network error';
                    if (xhr.responseJSON && xhr.responseJSON.data && xhr.responseJSON.data.message) {
                        errorMsg = xhr.responseJSON.data.message;
                    } else if (error) {
                        errorMsg = error;
                    }
                    $result.html(`<div class="error"><span class="dashicons dashicons-warning"></span> Error: ${this.escapeHtml(errorMsg)}</div>`);
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

