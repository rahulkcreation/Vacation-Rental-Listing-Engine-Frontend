(function($) {
    'use strict';

    const LEF_HostListings = {
        data: [],
        perPage: 10,
        activeStatus: 'published',
        currentPage: 1,
        searchValue: '',
        
        statusLabels: {
            published: 'Published',
            pending: 'Pending',
            draft: 'Draft',
            rejected: 'Rejected'
        },

        init() {
            this.cacheDOM();
            this.bindEvents();
            this.fetchData();
        },

        cacheDOM() {
            this.$wrapper       = $('.lef-host-list-page');
            this.$searchPanel   = $('#lef-host-list-search-panel');
            this.$searchInput   = $('#lef-host-list-search-input');
            this.$clearBtn      = $('#lef-host-list-clear-btn');
            this.$cardList      = $('#lef-host-list-card-list');
            this.$emptyState    = $('#lef-host-list-empty');
            this.$pagination    = $('#lef-host-list-pagination');
            this.$pageText      = $('#lef-host-list-pagination-text');
            this.$pageControls  = $('#lef-host-list-pagination-controls');
            this.$selectAll     = $('#lef-host-list-select-all');
            this.$tabs          = $('.lef-host-list-tab');
            
            this.$bulkActions   = $('#lef-host-list-bulk-actions');
            this.$bulkStatusSel = $('#lef-host-list-bulk-status');
            this.$bulkApplyBtn  = $('#lef-host-list-bulk-apply-status');
            this.$bulkDeleteBtn = $('#lef-host-list-bulk-delete');
            this.$addBtn        = $('#lef-host-list-add-btn');
        },

        bindEvents() {
            // Search Input
            this.$searchInput.on('input', (e) => {
                const val = $(e.currentTarget).val();
                if (val.length >= 2 || val.length === 0) {
                    this.searchValue = val;
                    this.currentPage = 1;
                    this.renderDashboard();
                }
            });

            this.$searchInput.on('focus', () => this.$searchPanel.addClass('lef-host-list-search-focused'));
            this.$searchInput.on('blur', () => this.$searchPanel.removeClass('lef-host-list-search-focused'));

            this.$clearBtn.on('click', () => {
                this.searchValue = '';
                this.$searchInput.val('').focus();
                this.currentPage = 1;
                this.renderDashboard();
            });

            // Status Tabs
            this.$tabs.on('click', (e) => {
                const status = $(e.currentTarget).data('lef-host-list-status-tab');
                this.activeStatus = status;
                this.currentPage = 1;
                
                this.$tabs.removeClass('lef-host-list-tab-active').attr('aria-pressed', 'false');
                $(e.currentTarget).addClass('lef-host-list-tab-active').attr('aria-pressed', 'true');
                
                this.renderDashboard();
            });

            // Pagination Controls
            this.$pageControls.on('click', '[data-lef-host-list-page]', (e) => {
                const $btn = $(e.currentTarget);
                if ($btn.prop('disabled')) return;

                const filtered = this.getFilteredData();
                const totalPages = Math.max(1, Math.ceil(filtered.length / this.perPage));
                const action = $btn.data('lef-host-list-page');

                if (action === 'prev') {
                    this.currentPage = Math.max(1, this.currentPage - 1);
                } else if (action === 'next') {
                    this.currentPage = Math.min(totalPages, this.currentPage + 1);
                } else {
                    this.currentPage = Number(action);
                }

                this.renderDashboard();
            });

            // Select All Checkbox
            this.$selectAll.on('change', (e) => {
                const isChecked = $(e.currentTarget).is(':checked');
                $('.lef-host-list-row-checkbox').prop('checked', isChecked);
                this.updateBulkActionsVisibility();
            });

            // Row Checkboxes
            this.$cardList.on('change', '.lef-host-list-row-checkbox', () => {
                const total = $('.lef-host-list-row-checkbox').length;
                const checked = $('.lef-host-list-row-checkbox:checked').length;
                this.$selectAll.prop('checked', total > 0 && total === checked);
                this.updateBulkActionsVisibility();
            });

            // Actions - Single
            this.$cardList.on('click', '.lef-host-list-action-edit', (e) => {
                const id = $(e.currentTarget).closest('.lef-host-list-card').data('id');
                // Could integrate with view-edit modal dynamically here
                if(window.LEF_Toast) window.LEF_Toast.show('Loading edit view...', 'info');
            });

            this.$cardList.on('click', '.lef-host-list-action-duplicate', (e) => {
                const id = $(e.currentTarget).closest('.lef-host-list-card').data('id');
                this.handleAction('duplicate', [id]);
            });

            this.$cardList.on('click', '.lef-host-list-action-delete', (e) => {
                const id = $(e.currentTarget).closest('.lef-host-list-card').data('id');
                if(window.LEF_Confirm) {
                    window.LEF_Confirm.open({ title: 'Delete Property', message: 'Are you sure you want to delete this property? This cannot be undone.' }, (confirmed) => {
                        if (confirmed) this.handleAction('delete', [id]);
                    });
                }
            });

            // Actions - Bulk
            this.$bulkApplyBtn.on('click', () => {
                const ids = this.getSelectedIds();
                const status = this.$bulkStatusSel.val();
                if (ids.length === 0 || !status) return;
                
                if(window.LEF_Confirm) {
                    window.LEF_Confirm.open({ title: 'Change Status', message: 'Change status of selected properties?' }, (confirmed) => {
                        if (confirmed) this.handleAction('change_status', ids, { status });
                    });
                }
            });

            this.$bulkDeleteBtn.on('click', () => {
                const ids = this.getSelectedIds();
                if (ids.length === 0) return;

                if(window.LEF_Confirm) {
                    window.LEF_Confirm.open({ title: 'Delete Properties', message: 'Delete selected properties? This cannot be undone.' }, (confirmed) => {
                        if (confirmed) this.handleAction('delete', ids);
                    });
                }
            });

            // Escape to clear search
            $(document).on('keydown', (e) => {
                if (e.key === 'Escape' && this.searchValue.length > 0) {
                    this.$clearBtn.click();
                }
            });
            
            // Add Property Placeholder
            this.$addBtn.on('click', () => {
                if(window.LEF_Toast) window.LEF_Toast.show('Loading Add Property view...', 'info');
            });
        },

        fetchData() {
            this.$cardList.html('<div style="padding:40px; text-align:center;"><div class="lef-spinner" style="margin: 0 auto;"></div></div>');
            
            $.ajax({
                url: lefMyProfileData.ajax_url,
                type: 'POST',
                data: {
                    action: 'lef_get_host_listings',
                    nonce: lefMyProfileData.nonce
                },
                success: (response) => {
                    if (response.success) {
                        this.data = response.data.listings || [];
                        this.renderDashboard();
                        this.updateCounts();
                    } else {
                        if (window.LEF_Toast) window.LEF_Toast.show(response.data.message || 'Failed to load listings', 'error');
                        this.$cardList.html('<div style="padding:20px; color: var(--leb-error-color);">Error loading listings.</div>');
                    }
                },
                error: () => {
                    if (window.LEF_Toast) window.LEF_Toast.show('Network error', 'error');
                }
            });
        },

        handleAction(actionType, ids, extraData = {}) {
            if (window.LEF_Toast) window.LEF_Toast.show('Processing...', 'info');
            
            $.ajax({
                url: lefMyProfileData.ajax_url,
                type: 'POST',
                data: {
                    action: 'lef_host_list_action',
                    nonce: lefMyProfileData.nonce,
                    type: actionType,
                    ids: ids,
                    ...extraData
                },
                success: (response) => {
                    if (response.success) {
                        if (window.LEF_Toast) window.LEF_Toast.show(response.data.message, 'success');
                        this.fetchData(); // Reload data
                        this.$selectAll.prop('checked', false);
                        this.updateBulkActionsVisibility();
                    } else {
                        if (window.LEF_Toast) window.LEF_Toast.show(response.data.message || 'Action failed', 'error');
                    }
                },
                error: () => {
                    if (window.LEF_Toast) window.LEF_Toast.show('Network error', 'error');
                }
            });
        },

        escapeHtml(val) {
            if (val === null || val === undefined) return '';
            return String(val)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        },

        getFilteredData() {
            const term = this.searchValue.trim().toLowerCase();

            return this.data.filter((item) => {
                const matchesStatus = item.status === this.activeStatus;
                const haystack = [
                    item.title,
                    item.type,
                    item.price,
                    this.statusLabels[item.status]
                ].join(' ').toLowerCase();

                return matchesStatus && (!term || haystack.includes(term));
            });
        },

        updateCounts() {
            Object.keys(this.statusLabels).forEach((status) => {
                const count = this.data.filter(item => item.status === status).length;
                $('#lef-host-list-count-' + status).text(count);
            });
        },

        updateBulkActionsVisibility() {
            const checked = $('.lef-host-list-row-checkbox:checked').length;
            if (checked > 0) {
                this.$bulkActions.show();
            } else {
                this.$bulkActions.hide();
            }
        },

        getSelectedIds() {
            const ids = [];
            $('.lef-host-list-row-checkbox:checked').each(function() {
                ids.push($(this).closest('.lef-host-list-card').data('id'));
            });
            return ids;
        },

        renderCards(pageItems, startIndex) {
            const html = pageItems.map((item, index) => {
                const serial = startIndex + index + 1;
                const status = this.escapeHtml(item.status);
                const statusText = this.escapeHtml(this.statusLabels[item.status] || item.status);
                const imgUrl = item.image ? this.escapeHtml(item.image) : ''; // Add default if needed

                return `
                    <article class="lef-host-list-card" data-id="${this.escapeHtml(item.id)}">
                        <div class="lef-host-list-card-top">
                            <input class="lef-host-list-checkbox lef-host-list-row-checkbox" type="checkbox" aria-label="Select ${this.escapeHtml(item.title)}">
                            <img class="lef-host-list-thumb" src="${imgUrl}" alt="${this.escapeHtml(item.title)}">
                        </div>
                        <div class="lef-host-list-card-body">
                            <div class="lef-host-list-labels" aria-hidden="true">
                                <span class="lef-host-list-sno">${serial}</span>
                                <span class="lef-host-list-label">Title</span>
                                <span class="lef-host-list-label">Type</span>
                                <span class="lef-host-list-label">Price</span>
                                <span class="lef-host-list-label">Status</span>
                            </div>
                            <div class="lef-host-list-values">
                                <span class="lef-host-list-value lef-host-list-mobile-sno" data-lef-host-list-label="S.No">${serial}</span>
                                <span class="lef-host-list-value" data-lef-host-list-label="Title">${this.escapeHtml(item.title)}</span>
                                <span class="lef-host-list-value" data-lef-host-list-label="Type">${this.escapeHtml(item.type)}</span>
                                <span class="lef-host-list-value" data-lef-host-list-label="Price">${this.escapeHtml(item.price)}</span>
                                <span class="lef-host-list-value" data-lef-host-list-label="Status">
                                    <span class="lef-host-list-status" data-lef-host-list-status="${status}">${statusText}</span>
                                </span>
                            </div>
                        </div>
                        <div class="lef-host-list-actions">
                            <button class="lef-host-list-action-btn lef-host-list-action-edit" type="button">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M12 20h9"></path>
                                    <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"></path>
                                </svg>
                                Edit
                            </button>
                            <button class="lef-host-list-action-btn lef-host-list-action-duplicate" type="button">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <rect x="9" y="9" width="13" height="13" rx="2"></rect>
                                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                                </svg>
                                Duplicate
                            </button>
                            <button class="lef-host-list-action-btn lef-host-list-action-delete" type="button">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M3 6h18"></path>
                                    <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"></path>
                                    <path d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path>
                                    <path d="M10 11v6"></path>
                                    <path d="M14 11v6"></path>
                                </svg>
                                Delete
                            </button>
                        </div>
                    </article>
                `;
            }).join('');

            this.$cardList.html(html);
        },

        renderPagination(totalItems) {
            const totalPages = Math.max(1, Math.ceil(totalItems / this.perPage));
            const controls = [];

            controls.push(`
                <button class="lef-host-list-page-btn" type="button" data-lef-host-list-page="prev" ${this.currentPage === 1 ? 'disabled' : ''} aria-label="Previous page">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="m15 18-6-6 6-6"></path>
                    </svg>
                </button>
            `);

            for (let page = 1; page <= totalPages; page++) {
                // If many pages, logic could be expanded to truncate to ..., but simple loop is fine for realistic host properties
                controls.push(`
                    <button class="lef-host-list-page-btn ${page === this.currentPage ? 'lef-host-list-page-active' : ''}"
                        type="button" data-lef-host-list-page="${page}" aria-label="Page ${page}">
                        ${page}
                    </button>
                `);
            }

            controls.push(`
                <button class="lef-host-list-page-btn" type="button" data-lef-host-list-page="next" ${this.currentPage === totalPages ? 'disabled' : ''} aria-label="Next page">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="m9 18 6-6-6-6"></path>
                    </svg>
                </button>
            `);

            this.$pageControls.html(controls.join(''));
        },

        renderDashboard() {
            const filteredData = this.getFilteredData();
            const totalItems = filteredData.length;
            const totalPages = Math.max(1, Math.ceil(totalItems / this.perPage));

            if (this.currentPage > totalPages) {
                this.currentPage = totalPages;
            }

            const startIndex = (this.currentPage - 1) * this.perPage;
            const endIndex = startIndex + this.perPage;
            const pageItems = filteredData.slice(startIndex, endIndex);

            this.$clearBtn.toggleClass('lef-host-list-clear-visible', this.searchValue.length > 0);
            this.$selectAll.prop('checked', false);
            this.updateBulkActionsVisibility();

            if (totalItems === 0) {
                this.$cardList.html('');
                this.$emptyState.addClass('lef-host-list-empty-visible');
                this.$pagination.hide();
                return;
            }

            this.$emptyState.removeClass('lef-host-list-empty-visible');
            this.$pagination.css('display', 'flex');
            
            this.renderCards(pageItems, startIndex);

            const showingFrom = startIndex + 1;
            const showingTo = Math.min(endIndex, totalItems);
            this.$pageText.text(`Showing ${showingFrom}-${showingTo} of ${totalItems}`);
            this.renderPagination(totalItems);
        }
    };

    $(document).ready(() => {
        // Trigger initialization if container exists
        if ($('.lef-host-list-page').length) {
            LEF_HostListings.init();
        }

        // Listener for dashboard AJAX loads
        $(document).on('lef_sidebar_screen_loaded', (e, screen) => {
            if (screen === 'my-listings') {
                LEF_HostListings.init();
            }
        });
    });

})(jQuery);
