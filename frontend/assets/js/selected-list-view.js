/**
 * selected-list-view.js
 *
 * Handles carousel navigation and card clicks for the [selected_list_view] shortcode.
 */

(function ($) {
  "use strict";

  const SelectedListView = {
    init: function () {
      // Only bind events once on document level
      if (!window.lefEventsBound) {
        this.bindEvents();
        window.lefEventsBound = true;
      }
      this.initCarousels();
    },

    /**
     * Detect if we are currently inside the Elementor Editor
     */
    isEditorMode: function () {
      return (
        (window.elementorFrontend && window.elementorFrontend.isEditMode()) ||
        $("body").hasClass("elementor-editor-active") ||
        $("body").hasClass("wp-admin")
      );
    },

    bindEvents: function () {
      const self = this;

      // 1. Universal Card Redirection
      $(document).on("click", ".lef-property-card", function (e) {
        // Prevent redirect if clicking on a UI button (like favorite)
        if ($(e.target).closest("button").length) return;

        // SPECIAL GUARD: Disable redirection in Elementor Editor to allow widget editing
        if (self.isEditorMode()) {
          console.log("LEF: Redirection disabled in Editor Mode.");
          e.preventDefault();
          return false;
        }

        const url = $(this).data("redirect");
        if (url && url !== "#") {
          window.location.href = url;
        }
      });

      // 2. Favorite Toggle (Database Driven)
      $(document).on("click", ".lef-favorite-btn", function (e) {
        e.preventDefault();
        e.stopPropagation();

        if (self.isEditorMode()) return;

        const $btn = $(this);
        const propertyId = $btn.data("id");

        // 1. Check Login Status
        if (!lefData || lefData.isLoggedIn !== "1") {
          if (window.LEF_Toast) {
            LEF_Toast.show("Please login to add in wishlist", "error");
          }
          return;
        }

        // 2. Trigger AJAX Toggle
        $btn.addClass("is-loading"); // Optional loading state

        $.ajax({
          url: lefData.ajaxUrl,
          type: "POST",
          data: {
            action: "lef_toggle_wishlist",
            property_id: propertyId,
            nonce: lefData.wishlistNonce,
          },
          success: function (res) {
            $btn.removeClass("is-loading");
            if (res.success) {
              const status = res.data.status;
              $btn.toggleClass("is-active", status === "added");

              if (window.LEF_Toast) {
                LEF_Toast.show(res.data.message, "success");
              }
            } else {
              if (window.LEF_Toast) {
                LEF_Toast.show(
                  res.data.message || "Failed to update wishlist",
                  "error",
                );
              }
            }
          },
          error: function () {
            $btn.removeClass("is-loading");
            if (window.LEF_Toast) {
              LEF_Toast.show("Network error. Please try again.", "error");
            }
          },
        });
      });
    },

    initCarousels: function () {
      $(".lef-view-carousel").each(function () {
        const $container = $(this);
        // Prevent duplicate initialization
        if ($container.data("lef-initialized")) return;
        $container.data("lef-initialized", true);

        const $track = $container.find(".lef-carousel-track");
        const $btnPrev = $container.find(".lef-nav-prev");
        const $btnNext = $container.find(".lef-nav-next");

        if (!$track.length) return;

        // Scroll Logic
        const getScrollAmount = () => {
          const $cards = $track.find(".lef-property-card");
          if (!$cards.length) return 0;

          const cardWidth = $cards.first().outerWidth();
          // Get gap from CSS variable or computed style
          const gap = parseInt($track.css("gap")) || 12;

          // Scroll by N cards based on view mode (usually we scroll by 1 card or full view)
          // Let's scroll by the width of visible cards or a single card.
          // To fix "list pura last tak chala ja rha", we ensure we scroll exactly one card width + gap.
          return cardWidth + gap;
        };

        $btnPrev.on("click", function () {
          const amount = getScrollAmount();
          $track[0].scrollBy({
            left: -amount,
            behavior: "smooth",
          });
        });

        $btnNext.on("click", function () {
          const amount = getScrollAmount();
          $track[0].scrollBy({
            left: amount,
            behavior: "smooth",
          });
        });

        // Update Button Visibility
        const updateButtons = () => {
          const scrollLeft = $track.scrollLeft();
          const maxScroll = $track[0].scrollWidth - $track[0].clientWidth;

          $btnPrev.toggleClass("is-hidden", scrollLeft <= 10);
          $btnNext.toggleClass("is-hidden", scrollLeft >= maxScroll - 10);
        };

        $track.on("scroll", updateButtons);
        $(window).on("resize", updateButtons);
        updateButtons(); // Initial check
      });
    },
  };

  // ── Initialization Logic ──

  // 1. Standard Document Ready
  $(document).ready(function () {
    SelectedListView.init();
  });

  // 2. Elementor AJAX Loading Support
  $(window).on("elementor/frontend/init", function () {
    elementorFrontend.hooks.addAction(
      "frontend/element_ready/global",
      function ($scope) {
        // Check if our shortcode is inside this scope
        if ($scope.find(".lef-selected-container").length) {
          // Force re-init by clearing the flag on the container
          $scope.find(".lef-view-carousel").data("lef-initialized", false);
          SelectedListView.init();
        }
      },
    );
  });
})(jQuery);
