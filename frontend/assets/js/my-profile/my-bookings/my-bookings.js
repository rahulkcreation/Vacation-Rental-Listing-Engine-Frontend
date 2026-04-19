/**
 * my-bookings.js
 *
 * Handles My Bookings dashboard logic:
 * - Tab switching with active title update
 * - Debounced search
 * - AJAX list rendering and pagination
 * - Detail view integration via LefMbView (view.js)
 *
 * Prefix: lef-my-book-
 *
 * @package ListingEngineFrontend
 */

(function ($) {
  "use strict";

  const LefMyBook = {

    /**
     * Internal state for the current list view.
     */
    state: {
      status: "pending",
      search: "",
      page: 1,
      isLoading: false,
      searchTimer: null,
    },

    /**
     * Initialize the module.
     * Called each time the "my-bookings" screen is loaded by my-profile.js.
     */
    init() {
      if (this.state.isLoading) return;

      this.cacheDOM();

      // Bail if panel not in DOM
      if (this.$panel.length === 0) return;

      // Unbind before rebinding to prevent duplicate handlers on re-init
      this.$panel.off(".lef-my-book");
      this.$searchInput.off(".lef-my-book");

      this.bindEvents();
      this.fetchData();
    },

    /**
     * Cache frequently used DOM references.
     */
    cacheDOM() {
      this.$panel             = $("#lef-my-book-panel");
      this.$listContainer     = $("#lef-my-book-list-container");
      this.$searchInput       = $("#lef-my-book-search-input");
      this.$totalCount        = $("#lef-my-book-total-count");
      this.$countPending      = $("#lef-my-book-count-pending");
      this.$countCompleted    = $("#lef-my-book-count-completed");
      this.$countRejected     = $("#lef-my-book-count-rejected");
      this.$paginationInfo    = $("#lef-my-book-pagination-info");
      this.$pageControls      = $("#lef-my-book-page-numbers");
      this.$emptyState        = $("#lef-my-book-empty-state");
      this.$paginationContainer = $("#lef-my-book-pagination-container");
      this.$activeTitle       = $("#lef-my-book-active-title");
    },

    /**
     * Bind all UI events using namespaced handlers to allow safe unbinding.
     */
    bindEvents() {
      const self = this;

      // --- Tab switching ---
      this.$panel.on("click.lef-my-book", ".lef-my-book-tab", function () {
        const $tab = $(this);
        if ($tab.hasClass("is-active")) return;

        $(".lef-my-book-tab").removeClass("is-active");
        $tab.addClass("is-active");

        self.state.status = $tab.data("status");
        self.state.page   = 1;

        // Extract tab label text, ignoring the count badge child element
        const tabLabel = $tab.contents().filter(function () {
          return this.nodeType === 3; // Text node only
        }).text().trim();

        self.$activeTitle.text(tabLabel + " Reservations");
        self.fetchData();
      });

      // --- Debounced search ---
      this.$searchInput.on("input.lef-my-book", function () {
        clearTimeout(self.state.searchTimer);
        const val = $(this).val().trim();
        self.state.searchTimer = setTimeout(() => {
          self.state.search = val;
          self.state.page   = 1;
          self.fetchData();
        }, 400);
      });

      // --- Search box focus highlight ---
      this.$searchInput.on("focus.lef-my-book blur.lef-my-book", function (e) {
        $("#lef-my-book-search-container").toggleClass("is-focused", e.type === "focus");
      });

      // --- Pagination ---
      this.$panel.on("click.lef-my-book", ".lef-my-book-page-btn", function () {
        const page = parseInt($(this).data("page"), 10);
        if (!page || page === self.state.page) return;
        self.state.page = page;
        self.fetchData();
      });

      // --- View button -> open detail view ---
      this.$panel.on("click.lef-my-book", ".lef-my-book-view-btn", function () {
        const id = $(this).data("id");
        self.openDetailView(id);
      });
    },

    /**
     * Fetch reservations list via AJAX and render results.
     */
    fetchData() {
      if (this.state.isLoading) return;
      this.state.isLoading = true;

      this.$listContainer.html('<div class="lef-my-book-loader"><span></span> Loading...</div>');
      this.$emptyState.hide();

      $.ajax({
        url:  lefMyProfileData.ajax_url,
        type: "POST",
        data: {
          action: "lef_get_my_bookings",
          nonce:  lefMyProfileData.nonce,
          status: this.state.status,
          search: this.state.search,
          page:   this.state.page,
        },
        success: (response) => {
          if (response.success) {
            this.renderList(response.data.list);
            this.updateUi(response.data);
          }
        },
        error: () => {
          this.$listContainer.html('<p class="lef-my-book-error">Failed to load reservations. Please try again.</p>');
        },
        complete: () => {
          this.state.isLoading = false;
        },
      });
    },

    /**
     * Render the reservation card list.
     *
     * @param {Array} list - Array of reservation objects from AJAX.
     */
    renderList(list) {
      if (!list || list.length === 0) {
        this.$listContainer.empty();
        this.$emptyState.show();
        this.$paginationContainer.hide();
        return;
      }

      this.$emptyState.hide();
      this.$paginationContainer.show();

      let html = "";
      list.forEach((item, index) => {
        const sNo  = (this.state.page - 1) * 10 + (index + 1);
        const date = this.formatDate(item.updated_at);

        html += `
          <div class="lef-my-book-card">
            <div class="lef-my-book-sno">${sNo}</div>
            <div class="lef-my-book-card-info">
              <div class="lef-my-book-field">
                <span class="lef-my-book-field-label">Reservation #</span>
                <span class="lef-my-book-field-value">${item.reservation_number}</span>
              </div>
              <div class="lef-my-book-field">
                <span class="lef-my-book-field-label">Status</span>
                <span class="lef-my-book-status-badge" data-status="${item.status}">${this.capitalize(item.status)}</span>
              </div>
              <div class="lef-my-book-field">
                <span class="lef-my-book-field-label">Last Update</span>
                <span class="lef-my-book-field-value">${date}</span>
              </div>
            </div>
            <div class="lef-my-book-card-actions">
              <button class="lef-my-book-view-btn" data-id="${item.id}">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" width="16" height="16">
                  <path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0z"></path>
                  <circle cx="12" cy="12" r="3"></circle>
                </svg>
                View
              </button>
            </div>
          </div>
        `;
      });

      this.$listContainer.html(html);
    },

    /**
     * Update counts, pagination text, and pagination controls.
     *
     * @param {object} data - Response data from AJAX.
     */
    updateUi(data) {
      this.$totalCount.text(data.counts.total);
      this.$countPending.text(data.counts.pending);
      this.$countCompleted.text(data.counts.completed);
      this.$countRejected.text(data.counts.rejected);

      const start = (this.state.page - 1) * 10 + 1;
      const end   = Math.min(this.state.page * 10, data.total);
      this.$paginationInfo.text(
        `Showing ${data.total > 0 ? start : 0} to ${end} of ${data.total} entries`
      );

      this.renderPagination(data.total);
    },

    /**
     * Render pagination button controls.
     *
     * @param {number} total - Total number of records for current filter.
     */
    renderPagination(total) {
      const totalPages = Math.ceil(total / 10);

      if (totalPages <= 1) {
        this.$pageControls.empty();
        return;
      }

      let html = "";

      // Prev button
      html += `<button class="lef-my-book-page-btn" ${this.state.page === 1 ? "disabled" : ""} data-page="${this.state.page - 1}">Prev</button>`;

      // Page number buttons with ellipsis for large ranges
      for (let i = 1; i <= totalPages; i++) {
        if (totalPages > 5) {
          if (i === 1 || i === totalPages || (i >= this.state.page - 1 && i <= this.state.page + 1)) {
            html += `<button class="lef-my-book-page-btn ${this.state.page === i ? "is-active" : ""}" data-page="${i}">${i}</button>`;
          } else if (i === this.state.page - 2 || i === this.state.page + 2) {
            html += `<span class="lef-my-book-dots">…</span>`;
          }
        } else {
          html += `<button class="lef-my-book-page-btn ${this.state.page === i ? "is-active" : ""}" data-page="${i}">${i}</button>`;
        }
      }

      // Next button
      html += `<button class="lef-my-book-page-btn" ${this.state.page === totalPages ? "disabled" : ""} data-page="${this.state.page + 1}">Next</button>`;

      this.$pageControls.html(html);
    },

    /**
     * Fetch a single reservation's detail and hand off to LefMbView.
     *
     * @param {number} id - Reservation database ID.
     */
    openDetailView(id) {
      $.ajax({
        url:  lefMyProfileData.ajax_url,
        type: "POST",
        data: {
          action: "lef_get_booking_details",
          nonce:  lefMyProfileData.nonce,
          id:     id,
        },
        success: (response) => {
          if (response.success) {
            if (window.LefMbView) {
              window.LefMbView.show(response.data);
            }
          } else {
            if (window.LEF_Toast) {
              window.LEF_Toast.show(response.data.message || "Could not load reservation details.", "error");
            }
          }
        },
        error: () => {
          if (window.LEF_Toast) window.LEF_Toast.show("Network error. Please try again.", "error");
        },
      });
    },

    /**
     * Format a MySQL datetime string to a human-readable short date.
     *
     * @param {string} dateStr - MySQL datetime string.
     * @returns {string}
     */
    formatDate(dateStr) {
      if (!dateStr) return "-";
      const date = new Date(dateStr);
      return date.toLocaleDateString("en-US", {
        month:  "short",
        day:    "numeric",
        year:   "numeric",
        hour:   "2-digit",
        minute: "2-digit",
      });
    },

    /**
     * Capitalize the first letter of a string.
     *
     * @param {string} str
     * @returns {string}
     */
    capitalize(str) {
      if (!str) return "";
      return str.charAt(0).toUpperCase() + str.slice(1);
    },
  };

  /**
   * Listen for the dashboard screen-load event fired by my-profile.js.
   * Re-initializes the module each time the "my-bookings" section is activated.
   */
  $(document).on("lef_sidebar_screen_loaded", (e, screen) => {
    if (screen === "my-bookings") {
      LefMyBook.init();
    }
  });

})(jQuery);
