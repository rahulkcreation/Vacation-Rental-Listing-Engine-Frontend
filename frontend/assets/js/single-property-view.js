/**
 * single-property-view.js
 *
 * All interactive logic for the Single Property View template.
 * Handles: view toggling, share, wishlist, photo modal, description/amenity
 * popups, calendar, reservation form, guest dropdown, reviews, and similar
 * properties.
 *
 * Dependencies: jQuery (WordPress default), LEF_Toast (global toaster)
 * Localized data: lef_spv_data { ajax_url, nonce, property_id, price,
 *                                 max_guests, blocked_dates, is_logged_in }
 *
 * @package ListingEngineFrontend
 */

(function ($) {
    'use strict';

    /**
     * Early Device Detection
     * Checks window width and sets a cookie to ensure the server renders the correct view.
     * If the cookie is missing or incorrect, it reloads the page.
     * SKIP logic if in Elementor Preview/Editor to avoid breaking the tool.
     */
    (function () {
        // Skip for Elementor Preview
        if (window.location.href.indexOf('elementor-preview') !== -1) {
            return;
        }

        var width = window.innerWidth;
        var currentMode = width > 800 ? 'desktop' : 'mobile';
        var cookieValue = document.cookie.split('; ').find(row => row.startsWith('lef_device_view='));
        var storedMode = cookieValue ? cookieValue.split('=')[1] : null;

        if (storedMode !== currentMode) {
            document.cookie = "lef_device_view=" + currentMode + "; path=/; max-age=" + (60 * 60 * 24 * 30);
            window.location.reload();
        }
    })();

    // ─────────────────────────────────────────────────────────────
    // Constants & State
    // ─────────────────────────────────────────────────────────────

    const DATA      = window.lef_spv_data || {};
    const AJAX_URL  = DATA.ajax_url;
    const NONCE     = DATA.nonce;
    const PROP_ID   = parseInt(DATA.property_id, 10);
    const PRICE     = parseFloat(DATA.price) || 0;
    const MAX_GUEST = parseInt(DATA.max_guests, 10) || 10;
    const BLOCKED   = Array.isArray(DATA.blocked_dates) ? DATA.blocked_dates : [];
    const LOGGED_IN = DATA.is_logged_in === '1';

    /** Shared calendar state (synced across all calendar instances) */
    const calState = {
        checkIn: null,
        checkOut: null,
        currentMonth: new Date().getMonth(),
        currentYear: new Date().getFullYear(),
    };

    /** Guest counters */
    const guests = { adults: 1, children: 0, infants: 0 };

    // ─────────────────────────────────────────────────────────────
    // Initialization
    // ─────────────────────────────────────────────────────────────
    $(document).ready(function () {
        // Watch for resize crossing (requires reload for mode switch)
        $(window).on('resize', debounce(checkResizeCrossing, 200));

        initShareButtons();
        initWishlistButtons();
        initPhotoModal();
        initDescriptionPopup();
        initAmenityPopup();
        initCalendars();
        initGuestDropdowns();
        initReserveButtons();
        initReviewPopups();
        initReviewDetailPopup();
        initModalCloseButtons();
        loadSimilarProperties();
        initMobileSlider();
        initMobileBackButton();
    });


    /* ==================== VIEW & RELOAD CONTROLLER ==================== */
    /**
     * Enforces page reloads when crossing the 800px breakpoint.
     * Since the server only renders one view (Desktop or Mobile) based on cookies,
     * a resize across the boundary requires a fresh server render.
     */
    const BREAKPOINT = 800;
    const initialMode = window.innerWidth > BREAKPOINT ? 'desktop' : 'mobile';

    function checkResizeCrossing() {
        // Skip if in Elementor Preview
        if (window.location.href.indexOf('elementor-preview') !== -1) {
            return;
        }

        const currentMode = window.innerWidth > BREAKPOINT ? 'desktop' : 'mobile';
        if (currentMode !== initialMode) {
            // Crossed the breakpoint! Blank the screen and request reload as requested.
            $('body').css('background', '#fff').html('<div id="lef-spv-reload-overlay" style="display:flex; flex-direction:column; align-items:center; justify-content:center; height:100vh; text-align:center; font-family:var(--leb-font-main); background:#fff; color:var(--leb-text-color);"><h1 style="font-size:2.5rem; margin-bottom:1rem; font-weight:600;">Screen Resize Detected</h1><p style="font-size:1.6rem; margin-bottom:2rem; color:var(--leb-text-muted);">Please refresh the page to switch to ' + currentMode + ' view.</p><button onclick="window.location.reload();" style="padding:12px 28px; border-radius:12px; border:none; background:var(--leb-text-color); color:#fff; font-size:1.4rem; font-weight:500; cursor:pointer; transition: opacity 0.2s;">Refresh Page</button></div>');
        }
    }

    /**
     * Simple debounce to avoid excessive resize calls.
     */
    function debounce(fn, delay) {
        let timer;
        return function () {
            clearTimeout(timer);
            timer = setTimeout(fn, delay);
        };
    }


    /* ==================== SHARE ==================== */
    function initShareButtons() {
        $('#lef-spv-share-btn, #lef-spv-share-btn-mb').on('click', function () {
            const url = window.location.href;
            if (navigator.clipboard) {
                navigator.clipboard.writeText(url).then(function () {
                    window.LEF_Toast && LEF_Toast.show('Link copied!', 'success');
                });
            } else {
                // Fallback
                const input = document.createElement('input');
                input.value = url;
                document.body.appendChild(input);
                input.select();
                document.execCommand('copy');
                document.body.removeChild(input);
                window.LEF_Toast && LEF_Toast.show('Link copied!', 'success');
            }
        });
    }


    /* ==================== WISHLIST ==================== */
    function initWishlistButtons() {
        $('#lef-spv-wishlist-btn, #lef-spv-wishlist-btn-mb').on('click', function () {
            if (!LOGGED_IN) {
                window.LEF_Toast && LEF_Toast.show('Please log in to save properties.', 'error');
                return;
            }

            $.post(AJAX_URL, {
                action: 'lef_toggle_wishlist',
                nonce: NONCE,
                property_id: PROP_ID,
            }, function (res) {
                if (res.success) {
                    const isSaved = res.data.status === 'added';
                    // Update ALL heart icons on the page
                    $('.lef-spv-heart-icon').html(isSaved ? heartFilledSVG() : heartEmptySVG());
                    window.LEF_Toast && LEF_Toast.show(isSaved ? 'Saved!' : 'Removed from saved.', 'success');
                }
            });
        });
    }

    /** Helper SVG generators */
    function heartEmptySVG() {
        return '<svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg" style="display:block;fill:none;height:16px;width:16px;stroke:currentcolor;stroke-width:2;overflow:visible;"><path d="m15.9998 28.6668c7.1667-4.8847 14.3334-10.8844 14.3334-18.1088 0-1.84951-.6993-3.69794-2.0988-5.10877-1.3996-1.4098-3.2332-2.11573-5.0679-2.11573-1.8336 0-3.6683.70593-5.0668 2.11573l-2.0999 2.11677-2.0988-2.11677c-1.3995-1.4098-3.2332-2.11573-5.06783-2.11573-1.83364 0-3.66831.70593-5.06683 2.11573-1.39955 1.41083-2.09984 3.25926-2.09984 5.10877 0 7.2244 7.16667 13.2241 14.3333 18.1088z"></path></svg>';
    }
    function heartFilledSVG() {
        return '<svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg" style="display:block;fill:var(--leb-primary-color);height:16px;width:16px;stroke:var(--leb-primary-color);stroke-width:2;overflow:visible;"><path d="m15.9998 28.6668c7.1667-4.8847 14.3334-10.8844 14.3334-18.1088 0-1.84951-.6993-3.69794-2.0988-5.10877-1.3996-1.4098-3.2332-2.11573-5.0679-2.11573-1.8336 0-3.6683.70593-5.0668 2.11573l-2.0999 2.11677-2.0988-2.11677c-1.3995-1.4098-3.2332-2.11573-5.06783-2.11573-1.83364 0-3.66831.70593-5.06683 2.11573-1.39955 1.41083-2.09984 3.25926-2.09984 5.10877 0 7.2244 7.16667 13.2241 14.3333 18.1088z"></path></svg>';
    }


    /* ==================== PHOTO MODAL ==================== */
    function initPhotoModal() {
        $('#lef-spv-show-photos, .lefdk-img-cont img, .lefmb-img-cont img').on('click', function () {
            $('#lefg-photo-modal').css('display', 'flex');
            $('body').css('overflow', 'hidden');
        });

        // Close via close button
        $('#lefg-close-photo-modal').on('click', function() {
            $('#lefg-photo-modal').css('display', 'none');
            $('body').css('overflow', '');
        });

    }


    /* ==================== DESCRIPTION POPUP ==================== */
    function initDescriptionPopup() {
        $('#lef-spv-desc-more, #lef-spv-desc-more-mb').on('click', function () {
            showModal('lef-spv-desc-modal');
        });
    }


    /* ==================== AMENITY POPUP ==================== */
    function initAmenityPopup() {
        $('#lef-spv-amenity-more, #lef-spv-amenity-more-mb').on('click', function () {
            showModal('lef-spv-amenity-modal');
        });
    }


    /* ==================== REVIEW DETAIL POPUP ==================== */
    function initReviewDetailPopup() {
        $(document).on('click', '.lef-spv-see-more', function () {
            const avatar = $(this).data('avatar');
            const name   = $(this).data('name');
            const date   = $(this).data('date');
            const rating = $(this).data('rating');
            const full   = $(this).data('full-review');

            $('#lef-review-detail-avatar').attr('src', avatar);
            $('#lef-review-detail-name').text(name);
            $('#lef-review-detail-date').text(date);
            $('#lef-review-detail-rating').html(rating);
            $('#lef-review-detail-text').text(full);

            showModal('lef-spv-review-detail-modal');
        });
    }


    /* ==================== REVIEW POPUPS ==================== */
    function initReviewPopups() {
        // Show all reviews
        $('#lef-spv-reviews-more, #lef-spv-reviews-more-mb').on('click', function () {
            showModal('lef-spv-reviews-modal');
        });

        // Write / Edit review
        $('#lef-spv-write-review-btn, #lef-spv-write-review-btn-mb').on('click', function () {
            showModal('lef-spv-review-form-modal');
        });

        // Star selection
        $(document).on('click', '.lef-spv-star', function () {
            const val = parseInt($(this).data('value'), 10);
            $('.lef-spv-star').each(function () {
                $(this).toggleClass('active', parseInt($(this).data('value'), 10) <= val);
            });
        });

        // Pre-fill stars if editing
        const existingRating = parseInt($('#lef-spv-root').data('existing-rating') || 0, 10);
        if (existingRating > 0) {
            $('.lef-spv-star').each(function () {
                $(this).toggleClass('active', parseInt($(this).data('value'), 10) <= existingRating);
            });
        }

        // Submit review
        $('#lef-spv-submit-review-btn').on('click', function () {
            const rating = $('.lef-spv-star.active').length;
            const review = $('#lef-spv-review-text').val().trim();

            if (!rating || !review) {
                window.LEF_Toast && LEF_Toast.show('Please select a rating and write a review.', 'error');
                return;
            }

            const $btn = $(this);
            $btn.prop('disabled', true).text('Submitting...');

            $.post(AJAX_URL, {
                action: 'lef_submit_review',
                nonce: NONCE,
                property_id: PROP_ID,
                rating: rating,
                review: review,
            }, function (res) {
                $btn.prop('disabled', false).text('Submit Review');
                if (res.success) {
                    window.LEF_Toast && LEF_Toast.show(res.data.message, 'success');
                    hideModal('lef-spv-review-form-modal');
                } else {
                    window.LEF_Toast && LEF_Toast.show(res.data.message || 'Error submitting review.', 'error');
                }
            });
        });
    }


    /* ==================== MODAL HELPERS ==================== */
    function showModal(id) {
        $('#' + id).css('display', 'flex');
        $('body').css('overflow', 'hidden');
    }

    function hideModal(id) {
        $('#' + id).css('display', 'none');
        $('body').css('overflow', '');
    }

    function initModalCloseButtons() {
        // Close via element with [data-close] attribute
        $(document).on('click', '[data-close]', function () {
            const id = $(this).data('close');
            if (id) hideModal(id);
        });


    }


    /* ==================== CALENDAR ==================== */
    // Calendar IDs for desktop, mobile page, and mobile reservation modal
    const calendarConfigs = [
        { grid: '#lef-spv-calendarGrid',     month: '#lef-spv-currentMonth',     prev: '#lef-spv-prevMonth',     next: '#lef-spv-nextMonth',     clear: '#lef-spv-clearDates', offset: 0 },
        { grid: '#lef-spv-calendarGrid-mb',   month: '#lef-spv-currentMonth-mb',  prev: '#lef-spv-prevMonth-mb',  next: '#lef-spv-nextMonth-mb',  clear: '#lef-spv-clearDates-mb', offset: 0 },
        { grid: '#lef-spv-calendarGrid-mbr',  month: '#lef-spv-currentMonth-mbr', prev: '#lef-spv-prevMonth-mbr', next: '#lef-spv-nextMonth-mbr', clear: null, offset: 0 },
        // Dual-month modal (Desktop)
        { grid: '#lef-spv-cal-modal-grid1', month: '#lef-spv-cal-modal-month1', prev: '#lef-spv-cal-modal-prev', next: null, clear: null, offset: 0 },
        { grid: '#lef-spv-cal-modal-grid2', month: '#lef-spv-cal-modal-month2', prev: null, next: '#lef-spv-cal-modal-next', clear: null, offset: 1 },
    ];

    function initCalendars() {
        // Desktop date field triggers
        $('#lef-spv-checkin-field, #lef-spv-checkout-field').on('click', function () {
            showModal('lef-spv-calendar-modal');
            renderAllCalendars();
        });

        // Modal Clear dates
        $('#lef-spv-cal-modal-clear').on('click', function () {
            calState.checkIn  = null;
            calState.checkOut = null;
            renderAllCalendars();
            syncDatesToForm();
        });

        calendarConfigs.forEach(function (cfg) {
            renderCalendar(cfg);

            if (cfg.prev) {
                $(cfg.prev).on('click', function (e) {
                    e.stopPropagation();
                    calState.currentMonth--;
                    if (calState.currentMonth < 0) {
                        calState.currentMonth = 11;
                        calState.currentYear--;
                    }
                    renderAllCalendars();
                });
            }

            if (cfg.next) {
                $(cfg.next).on('click', function (e) {
                    e.stopPropagation();
                    calState.currentMonth++;
                    if (calState.currentMonth > 11) {
                        calState.currentMonth = 0;
                        calState.currentYear++;
                    }
                    renderAllCalendars();
                });
            }

            if (cfg.clear) {
                $(cfg.clear).on('click', function () {
                    calState.checkIn  = null;
                    calState.checkOut = null;
                    renderAllCalendars();
                    syncDatesToForm();
                });
            }
        });
    }

    function renderAllCalendars() {
        calendarConfigs.forEach(function (cfg) {
            renderCalendar(cfg);
        });
    }

    function renderCalendar(cfg) {
        const $grid  = $(cfg.grid);
        const $month = $(cfg.month);
        if (!$grid.length) return;

        let year  = calState.currentYear;
        let month = calState.currentMonth + (cfg.offset || 0);

        // Handle overflow for dual-month (e.g. Dec -> Jan next year)
        if (month > 11) {
            month = 0;
            year++;
        }
        if (month < 0) {
            month = 11;
            year--;
        }

        const today = new Date();
        today.setHours(0, 0, 0, 0);

        const monthNames = ['January', 'February', 'March', 'April', 'May', 'June',
                            'July', 'August', 'September', 'October', 'November', 'December'];
        $month.text(monthNames[month] + ' ' + year);

        $grid.empty();

        // Day headers
        const dayNames = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'];
        dayNames.forEach(function (d) {
            $grid.append('<div class="lefdk-cal-day-name">' + d + '</div>');
        });

        const firstDay   = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();

        // Blank cells for offset
        for (let i = 0; i < firstDay; i++) {
            $grid.append('<div class="lefdk-cal-day lefdk-cal-day-unav"></div>');
        }

        for (let day = 1; day <= daysInMonth; day++) {
            const dateObj   = new Date(year, month, day);
            const dateStr   = formatDate(dateObj);
            const isPast    = dateObj < today;
            const isBlocked = BLOCKED.includes(dateStr);
            const isToday   = dateObj.getTime() === today.getTime();

            let classes = 'lefdk-cal-day';
            if (isPast)    classes += ' lefdk-cal-day-disabled';
            if (isBlocked) classes += ' lefdk-cal-day-blocked';
            if (isToday)   classes += ' lefdk-cal-day-today';

            // Check-in / Check-out selection highlighting
            if (calState.checkIn && dateStr === formatDate(calState.checkIn)) {
                classes += ' lefdk-cal-day-selected';
            }
            if (calState.checkOut && dateStr === formatDate(calState.checkOut)) {
                classes += ' lefdk-cal-day-selected';
            }
            // In-range highlighting
            if (calState.checkIn && calState.checkOut) {
                if (dateObj > calState.checkIn && dateObj < calState.checkOut) {
                    classes += ' lefdk-cal-day-in-range';
                }
            }

            const $day = $('<div class="' + classes + '" data-date="' + dateStr + '">' + day + '</div>');

            if (!isPast && !isBlocked) {
                $day.on('click', function () {
                    handleDateClick(dateStr);
                });
            }

            $grid.append($day);
        }

        // Fill trailing empty cells to complete the week (7 columns)
        const totalCells = firstDay + daysInMonth;
        const trailing   = (7 - (totalCells % 7)) % 7;
        for (let j = 0; j < trailing; j++) {
            $grid.append('<div class="lefdk-cal-day lefdk-cal-day-unav"></div>');
        }
    }

    function handleDateClick(dateStr) {
        const clicked = parseDate(dateStr);

        if (!calState.checkIn || (calState.checkIn && calState.checkOut)) {
            // Fresh selection or reset
            calState.checkIn  = clicked;
            calState.checkOut = null;
        } else {
            // Second click
            if (clicked <= calState.checkIn) {
                calState.checkIn  = clicked;
                calState.checkOut = null;
            } else {
                // Validate no blocked dates in range
                const hasBlocked = BLOCKED.some(function (bd) {
                    const d = parseDate(bd);
                    return d > calState.checkIn && d < clicked;
                });
                if (hasBlocked) {
                    window.LEF_Toast && LEF_Toast.show('Selected range contains blocked dates.', 'error');
                    calState.checkIn  = clicked;
                    calState.checkOut = null;
                } else {
                    calState.checkOut = clicked;
                }
            }
        }

        renderAllCalendars();
        syncDatesToForm();
    }

    /** Sync calendar dates → all form fields */
    function syncDatesToForm() {
        const ciStr = calState.checkIn  ? formatDisplayDate(calState.checkIn)  : 'Add date';
        const coStr = calState.checkOut ? formatDisplayDate(calState.checkOut) : 'Add date';

        // Desktop main form
        $('#lef-spv-checkin-label').text(ciStr).toggleClass('has-date', !!calState.checkIn);
        $('#lef-spv-checkout-label').text(coStr).toggleClass('has-date', !!calState.checkOut);

        // Desktop modal header
        $('#lef-spv-cmh-checkin').text(ciStr).toggleClass('has-date', !!calState.checkIn);
        $('#lef-spv-cmh-checkout').text(coStr).toggleClass('has-date', !!calState.checkOut);

        // Update modal title (e.g. "2 nights")
        if (calState.checkIn && calState.checkOut) {
            const nights = Math.round((calState.checkOut - calState.checkIn) / (1000 * 60 * 60 * 24));
            $('#lef-spv-cal-modal-title').text(nights + ' night' + (nights > 1 ? 's' : ''));
            $('#lef-spv-cal-modal-subtitle').text(formatDisplayDate(calState.checkIn) + ' – ' + formatDisplayDate(calState.checkOut));
        } else {
            $('#lef-spv-cal-modal-title').text('Select dates');
            $('#lef-spv-cal-modal-subtitle').text('Add your travel dates for exact pricing');
        }

        // Mobile reservation modal
        $('#lef-spv-mb-checkin-label').text(ciStr);
        $('#lef-spv-mb-checkout-label').text(coStr);

        // Price display
        updatePriceDisplay();
    }

    function updatePriceDisplay() {
        if (calState.checkIn && calState.checkOut) {
            const nights = Math.round((calState.checkOut - calState.checkIn) / (1000 * 60 * 60 * 24));
            const total  = PRICE * nights;
            const priceHtml = '₹' + total.toLocaleString('en-IN') + ' <span class="lefdk-lf-price-unit">for ' + nights + ' night' + (nights > 1 ? 's' : '') + '</span>';

            $('#lef-spv-price-display').html(priceHtml);
            $('#lef-spv-mb-price').text('₹' + total.toLocaleString('en-IN'));
            $('#lef-spv-mb-price-info').text('(For ' + nights + ' night' + (nights > 1 ? 's' : '') + ' × ₹' + PRICE.toLocaleString('en-IN') + ')');
        } else {
            $('#lef-spv-price-display').text('Add dates for prices');
            $('#lef-spv-mb-price').text('Add dates for prices');
            $('#lef-spv-mb-price-info').text('');
        }
    }


    /* ==================== GUEST DROPDOWN ==================== */
    function initGuestDropdowns() {
        // Desktop toggle
        $('#lef-spv-guests-trigger').on('click', function () {
            $('#lef-spv-guests-dropdown').toggle();
        });

        // Mobile modal toggle
        $('#lef-spv-mb-guests-trigger').on('click', function () {
            $('#lef-spv-mb-guests-dropdown').toggle();
        });

        // +/- buttons (unified handler via data attributes)
        $(document).on('click', '.lef-spv-gd-btn', function () {
            const action = $(this).data('action');
            const type   = $(this).data('type');
            const ctx    = $(this).data('ctx') || 'dk'; // dk = desktop, mb = mobile

            if (action === 'plus') {
                const totalNow = guests.adults + guests.children; // infants don't count toward max
                if (type !== 'infants' && totalNow >= MAX_GUEST) {
                    window.LEF_Toast && LEF_Toast.show('Maximum ' + MAX_GUEST + ' guests allowed.', 'info');
                    return;
                }
                guests[type]++;
            } else {
                if (type === 'adults' && guests[type] <= 1) return; // min 1 adult
                if (guests[type] <= 0) return;
                guests[type]--;
            }

            // Update ALL counters (desktop + mobile)
            syncGuestCounters();
        });

        // Close dropdown when clicking outside
        $(document).on('click', function (e) {
            if (!$(e.target).closest('#lef-spv-guests-trigger, #lef-spv-guests-dropdown').length) {
                $('#lef-spv-guests-dropdown').hide();
            }
            if (!$(e.target).closest('#lef-spv-mb-guests-trigger, #lef-spv-mb-guests-dropdown').length) {
                $('#lef-spv-mb-guests-dropdown').hide();
            }
        });
    }

    function syncGuestCounters() {
        // Desktop counters
        $('#lef-spv-adults-count').text(guests.adults);
        $('#lef-spv-children-count').text(guests.children);
        $('#lef-spv-infants-count').text(guests.infants);

        // Mobile counters
        $('#lef-spv-mb-adults-count').text(guests.adults);
        $('#lef-spv-mb-children-count').text(guests.children);
        $('#lef-spv-mb-infants-count').text(guests.infants);

        // Summary labels
        const total = guests.adults + guests.children;
        let label   = total + ' guest' + (total > 1 ? 's' : '');
        if (guests.infants > 0) label += ', ' + guests.infants + ' infant' + (guests.infants > 1 ? 's' : '');

        $('#lef-spv-guests-label, #lef-spv-mb-guests-label').text(label);
    }


    /* ==================== RESERVE ==================== */
    function initReserveButtons() {
        // Desktop Reserve button
        $('#lef-spv-reserve-btn').on('click', function () {
            submitReservation($(this));
        });

        // Mobile sticky bar → open reservation modal
        $('#lef-spv-mb-reserve-btn').on('click', function () {
            showModal('lef-spv-mb-reserve-modal');
            // Render calendar inside the mobile modal
            renderAllCalendars();
        });

        // Mobile confirm reserve
        $('#lef-spv-mb-confirm-reserve').on('click', function () {
            submitReservation($(this));
        });
    }

    function submitReservation($btn) {
        if (!LOGGED_IN) {
            window.LEF_Toast && LEF_Toast.show('Please log in to make a reservation.', 'error');
            return;
        }
        if (!calState.checkIn || !calState.checkOut) {
            window.LEF_Toast && LEF_Toast.show('Please select check-in and check-out dates.', 'error');
            return;
        }

        const nights = Math.round((calState.checkOut - calState.checkIn) / (1000 * 60 * 60 * 24));
        if (nights <= 0) {
            window.LEF_Toast && LEF_Toast.show('Invalid date range.', 'error');
            return;
        }

        const totalPrice = PRICE * nights;

        $btn.prop('disabled', true).text('Reserving...');

        $.post(AJAX_URL, {
            action: 'lef_submit_reservation',
            nonce: NONCE,
            property_id: PROP_ID,
            check_in: formatDate(calState.checkIn),
            check_out: formatDate(calState.checkOut),
            adults: guests.adults,
            children: guests.children,
            infants: guests.infants,
            total_price: totalPrice,
        }, function (res) {
            $btn.prop('disabled', false).text('Reserve');
            if (res.success) {
                window.LEF_Toast && LEF_Toast.show(res.data.message, 'success');
                // Reset form
                calState.checkIn  = null;
                calState.checkOut = null;
                guests.adults   = 1;
                guests.children = 0;
                guests.infants  = 0;
                syncGuestCounters();
                renderAllCalendars();
                syncDatesToForm();
                hideModal('lef-spv-mb-reserve-modal');
            } else {
                window.LEF_Toast && LEF_Toast.show(res.data.message || 'Reservation failed.', 'error');
            }
        }).fail(function () {
            $btn.prop('disabled', false).text('Reserve');
            window.LEF_Toast && LEF_Toast.show('Network error. Please try again.', 'error');
        });
    }


    /* ==================== SIMILAR PROPERTIES ==================== */
    function loadSimilarProperties() {
        $.post(AJAX_URL, {
            action: 'lef_get_similar_properties',
            nonce: NONCE,
            property_id: PROP_ID,
        }, function (res) {
            if (res.success && res.data.properties.length > 0) {
                const html = res.data.properties.map(buildSimilarCard).join('');
                $('#lef-spv-similar-dk').html(html);
                $('#lef-spv-similar-mb').html(html);
                $('.lefdk-similar-results, .lefmb-similar-results').show();
            } else {
                $('.lefdk-similar-results, .lefmb-similar-results').hide();
            }
        });
    }

    function buildSimilarCard(p) {
        const starSvg = '<svg viewBox="0 0 32 32" style="display:block;height:12px;width:12px;fill:currentcolor;"><path d="m15.1 1.58-4.13 8.88-9.86 1.27a1 1 0 0 0-.54 1.74l7.3 6.57-1.97 9.85a1 1 0 0 0 1.48 1.06l8.62-5 8.63 5a1 1 0 0 0 1.48-1.06l-1.97-9.85 7.3-6.57a1 1 0 0 0-.55-1.73l-9.86-1.28-4.12-8.88a1 1 0 0 0-1.82 0z"></path></svg>';
        const pluginUrl = DATA.plugin_url || '';
        const placeholderImg = pluginUrl + 'global-assets/images/placeholder.png';
        const imgSrc  = p.image || placeholderImg;
        const ratingHtml = p.avg_rating > 0 ? '<div class="lefdk-sm-rc-rating">' + starSvg + ' ' + p.avg_rating + '</div>' : '';

        return '<a class="lefdk-sm-rc-item" href="' + escHtml(p.url) + '">'
             + '<div class="lefdk-sm-rc-img">' 
             + '<img src="' + escHtml(imgSrc) + '" alt="' + escHtml(p.title) + '" onerror="this.src=\'' + placeholderImg + '\';">' 
             + '</div>'
             + '<div class="lefdk-sm-rc-details">'
             + '<span class="lefdk-sm-rc-title">' + escHtml(p.title) + '</span>'
             + '<span class="lefdk-sm-rc-price">₹' + Number(p.price).toLocaleString('en-IN') +  '<span>/ night</span>' + ratingHtml + '</span>' 
             + '</div></a>';
    }


    /* ==================== MOBILE IMAGE SLIDER ==================== */
    function initMobileSlider() {
        const $slider = $('#lef-spv-mb-slider');
        if (!$slider.length) return;

        const imgCount = $slider.find('img').length;
        if (imgCount <= 1) {
            $slider.find('.lefmb-slider-nav, .lefmb-img-count').hide();
            return;
        }

        // ── Scroll Listener for Counter Sync ──
        $slider.on('scroll', function () {
            const scrollLeft  = $slider.scrollLeft();
            const itemWidth   = $slider.find('img').first().outerWidth(true);
            const currentIdx  = Math.round(scrollLeft / itemWidth) + 1;
            $('#lef-spv-img-counter').text(currentIdx + '/' + imgCount);
        });

        // ── Navigation Logic ──
        $('#lef-spv-slider-prev').on('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            const itemWidth = $slider.find('img').first().outerWidth();
            $slider[0].scrollBy({ left: -itemWidth, behavior: 'smooth' });
        });

        $('#lef-spv-slider-next').on('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            const itemWidth = $slider.find('img').first().outerWidth();
            $slider[0].scrollBy({ left: itemWidth, behavior: 'smooth' });
        });
    }



    /* ==================== MOBILE BACK BUTTON ==================== */
    function initMobileBackButton() {
        $('#lef-spv-back-btn').on('click', function () {
            if (window.history.length > 1) {
                window.history.back();
            } else {
                window.location.href = '/';
            }
        });
    }


    // ─────────────────────────────────────────────────────────────
    // Utility Functions
    // ─────────────────────────────────────────────────────────────

    /** Format Date object → 'YYYY-MM-DD' */
    function formatDate(d) {
        const yyyy = d.getFullYear();
        const mm   = String(d.getMonth() + 1).padStart(2, '0');
        const dd   = String(d.getDate()).padStart(2, '0');
        return yyyy + '-' + mm + '-' + dd;
    }

    /** Parse 'YYYY-MM-DD' → Date */
    function parseDate(str) {
        const parts = str.split('-');
        return new Date(parseInt(parts[0]), parseInt(parts[1]) - 1, parseInt(parts[2]));
    }

    /** Format Date → 'D MMM YYYY' for display */
    function formatDisplayDate(d) {
        const dd = d.getDate();
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        return dd + ' ' + months[d.getMonth()] + ' ' + d.getFullYear();
    }

    /** Basic HTML escape */
    function escHtml(str) {
        if (!str) return '';
        return str.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }

})(jQuery);
