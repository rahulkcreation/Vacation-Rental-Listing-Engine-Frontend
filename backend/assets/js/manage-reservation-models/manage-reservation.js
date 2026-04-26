/**
 * Reservation Management JS
 * Handles AJAX fetching, searching, tabs, and modals.
 */
(function($) {
    'use strict';

    // State Management
    let lefReservCurrentStatus = 'pending';
    let lefReservCurrentPage = 1;
    let lefReservSearchTerm = '';
    let lefReservFetching = false;

    /**
     * Cache Selectors
     */
    const $page = $('.lef-reserv-page');
    const $cardList = $('#lef-reserv-card-list');
    const $totalCount = $('#lef-reserv-total-count');
    const $searchInput = $('#lef-reserv-search-input');
    const $searchBox = $('#lef-reserv-search-box');
    const $clearSearch = $('#lef-reserv-search-clear');
    const $emptyState = $('#lef-reserv-empty');
    const $pagination = $('#lef-reserv-pagination');
    const $paginationText = $('#lef-reserv-pagination-text');
    const $paginationControls = $('#lef-reserv-pagination-controls');
    const $listTitle = $('#lef-reserv-list-title');

    /**
     * Initialization
     */
    function lefReservInit() {
        lefReservFetchData();

        // Bind Events
        $('.lef-reserv-tab').on('click', lefReservHandleTabSwitch);
        $searchInput.on('input', lefReservHandleSearch);
        $searchInput.on('focus', () => $searchBox.addClass('lef-reserv-search-focused'));
        $searchInput.on('blur', () => $searchBox.removeClass('lef-reserv-search-focused'));
        $clearSearch.on('click', lefReservClearSearch);
        $paginationControls.on('click', '.lef-reserv-page-btn', lefReservHandlePagination);
    }

    /**
     * Fetch Data via AJAX
     */
    function lefReservFetchData() {
        if (lefReservFetching) return;
        lefReservFetching = true;

        // Show loading state if needed (optional)
        $cardList.css('opacity', '0.5');

        $.ajax({
            url: lefReservData.ajax_url,
            type: 'POST',
            data: {
                action: 'lef_reserv_fetch_data',
                nonce: lefReservData.nonce,
                status: lefReservCurrentStatus,
                search: lefReservSearchTerm,
                page: lefReservCurrentPage
            },
            success: function(response) {
                if (response.success) {
                    lefReservRenderDashboard(response.data);
                } else {
                    window.LEF_Toast.show(response.data.message || 'Failed to fetch data', 'error');
                }
            },
            error: function() {
                if (window.LEF_Toast) {
                    window.LEF_Toast.show('Server error. Please try again.', 'error');
                }
            },
            complete: function() {
                lefReservFetching = false;
                $cardList.css('opacity', '1');
            }
        });
    }

    /**
     * Render the Dashboard list and controls
     */
    function lefReservRenderDashboard(data) {
        // Update Stats
        $totalCount.text(data.total_db);
        $('#lef-reserv-count-pending').text(data.counts.pending);
        $('#lef-reserv-count-completed').text(data.counts.completed);
        $('#lef-reserv-count-rejected').text(data.counts.rejected);

        // Update UI Text
        const statusLabel = lefReservCurrentStatus.charAt(0).toUpperCase() + lefReservCurrentStatus.slice(1);
        $listTitle.text(statusLabel + ' Reservations');

        // Handle Empty State
        if (data.items.length === 0) {
            $cardList.html('').hide();
            $emptyState.addClass('lef-reserv-empty-visible');
            $pagination.hide();
            return;
        }

        $emptyState.removeClass('lef-reserv-empty-visible');
        $cardList.show();
        $pagination.show();

        // Render Cards
        let cardsHtml = '';
        data.items.forEach((item, index) => {
            const serialNo = ((data.current_page - 1) * data.per_page) + index + 1;
            cardsHtml += `
            <article class="lef-reserv-card">
                <div class="lef-reserv-sno">${serialNo}</div>
                <div class="lef-reserv-card-info">
                    <div class="lef-reserv-field">
                        <span class="lef-reserv-field-label lef-reserv-field-label-title">Reservation Number</span>
                        <span class="lef-reserv-field-value lef-reserv-field-value-title">${item.reservation_number}</span>
                    </div>
                    <div class="lef-reserv-field lef-reserv-field-status">
                        <span class="lef-reserv-field-label lef-reserv-field-label-status">Status</span>
                        <span class="lef-reserv-status-badge lef-reserv-field-value-status" data-lef-reserv-status="${item.status}">
                            ${item.status.charAt(0).toUpperCase() + item.status.slice(1)}
                        </span>
                    </div>
                    <div class="lef-reserv-field">
                        <span class="lef-reserv-field-label lef-reserv-field-label-date">Date Requested</span>
                        <span class="lef-reserv-field-value lef-reserv-field-value-date">${item.created_at}</span>
                    </div>
                </div>
                <div class="lef-reserv-card-actions">
                    <a class="lef-reserv-view-btn" href="admin.php?page=lef-manage-reservations&action=view&id=${item.id}">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="width:16px;height:16px;">
                            <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                        View
                    </a>
                </div>

            </article>`;
        });
        $cardList.html(cardsHtml);

        // Render Pagination
        const totalItems = data.total_matching;
        const totalPages = Math.ceil(totalItems / data.per_page);
        const from = ((data.current_page - 1) * data.per_page) + 1;
        const to = Math.min(data.current_page * data.per_page, totalItems);

        $paginationText.text(`Showing ${from}-${to} of ${totalItems}`);

        let pagHtml = '';
        pagHtml += `<button class="lef-reserv-page-btn" data-page="prev" ${data.current_page === 1 ? 'disabled' : ''}>&laquo;</button>`;
        
        for (let i = 1; i <= totalPages; i++) {
            pagHtml += `<button class="lef-reserv-page-btn ${i === data.current_page ? 'lef-reserv-page-active' : ''}" data-page="${i}">${i}</button>`;
        }

        pagHtml += `<button class="lef-reserv-page-btn" data-page="next" ${data.current_page === totalPages ? 'disabled' : ''}>&raquo;</button>`;
        $paginationControls.html(pagHtml);
    }

    /**
     * Event Handlers
     */
    function lefReservHandleTabSwitch() {
        const $this = $(this);
        if ($this.hasClass('lef-reserv-tab-active')) return;

        $('.lef-reserv-tab').removeClass('lef-reserv-tab-active').attr('aria-pressed', 'false');
        $this.addClass('lef-reserv-tab-active').attr('aria-pressed', 'true');

        lefReservCurrentStatus = $this.data('lef-reserv-tab');
        lefReservCurrentPage = 1;
        lefReservFetchData();
    }

    function lefReservHandleSearch() {
        const val = $(this).val();
        $clearSearch.toggleClass('lef-reserv-search-clear-visible', val.length > 0);

        if (val.length >= 2 || val.length === 0) {
            lefReservSearchTerm = val;
            lefReservCurrentPage = 1;
            // Debounce maybe? for now direct
            clearTimeout(window.lefReservSearchTimer);
            window.lefReservSearchTimer = setTimeout(lefReservFetchData, 400);
        }
    }

    function lefReservClearSearch() {
        $searchInput.val('');
        lefReservSearchTerm = '';
        lefReservCurrentPage = 1;
        $clearSearch.removeClass('lef-reserv-search-clear-visible');
        lefReservFetchData();
    }

    function lefReservHandlePagination() {
        const $this = $(this);
        const pageAction = $this.data('page');

        if (pageAction === 'prev') {
            lefReservCurrentPage--;
        } else if (pageAction === 'next') {
            lefReservCurrentPage++;
        } else {
            lefReservCurrentPage = parseInt(pageAction);
        }

        lefReservFetchData();
    }

    // Run on Doc Ready
    $(document).ready(lefReservInit);

})(jQuery);
