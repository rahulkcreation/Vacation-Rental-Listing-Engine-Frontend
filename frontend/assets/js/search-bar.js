/**
 * search-bar.js
 * 
 * Exact replica of search.html logic with WordPress AJAX integration.
 */

window.SearchBar = (function($) {
    'use strict';

    const state = {
        location: '',
        locationType: 'Location', // 'Location' or 'Property'
        selectedAddress: '',
        selectedLocation: '',
        checkin: '',
        checkout: '',
        adults: 0,
        children: 0,
        infants: 0,
        activeSection: null,
        calendarMonth: new Date(),
        mobileCalendarMonth: new Date(),
        archiveUrl: $('#lefSearchBar').data('archive-url') || window.location.origin,
        lat: null,
        lng: null,
        geoPermission: 'prompt' // 'prompt', 'granted', 'denied'
    };

    function init() {
        $(document).on('click', handleOutsideClick);
        renderCalendar();
        updateGuestButtons();
        updateGuestDisplay();
    }

    function requestGeolocation(callback) {
        if (!navigator.geolocation) {
            state.geoPermission = 'denied';
            if (callback) callback();
            return;
        }

        navigator.geolocation.getCurrentPosition(
            (position) => {
                state.lat = position.coords.latitude;
                state.lng = position.coords.longitude;
                state.geoPermission = 'granted';
                if (callback) callback();
            },
            (error) => {
                state.geoPermission = 'denied';
                if (callback) callback();
            }
        );
    }

    // ==================== CORE UI ACTIONS ====================

    function openSection(sectionId) {
        state.activeSection = sectionId;
        
        // For location, we only show the popup if there's something to show (suggestions)
        // This will be handled by showSuggestions() which is triggered on input/click
        if (sectionId !== 'location') {
            $('#mainPopup').addClass('active');
        }
        
        $('.popup-section').removeClass('active');
        $('#' + sectionId + 'Section').addClass('active');

        // Apply active class to trigger fields for visual feedback
        $('.search-field').removeClass('field-active');
        
        const specificFieldId = (sectionId === 'location') ? 'locationField' : 
                                (sectionId === 'date') ? 'dateField' : 'guestField';
        $('#' + specificFieldId).addClass('field-active');

        positionPopup(specificFieldId);
        
        if (sectionId === 'location') {
            const $mainInput = $('#locationDisplay');
            if (!$mainInput.is(':focus')) {
                $mainInput.focus();
            }

            // Trigger suggestion check on click
            if (state.geoPermission === 'prompt') {
                requestGeolocation(() => {
                    showSuggestions($mainInput.val());
                });
            } else {
                showSuggestions($mainInput.val());
            }
        }
    }

    function closePopup() {
        $('#mainPopup').removeClass('active');
        $('.search-field').removeClass('field-active');
        state.activeSection = null;
    }

    function positionPopup(fieldId) {
        const $trigger = $('#' + fieldId);
        if (!$trigger.length) return;
        
        const triggerRect = $trigger[0].getBoundingClientRect();
        const containerRect = $('#lefSearchBar')[0].getBoundingClientRect();
        
        let left = (triggerRect.left - containerRect.left);
        const popupWidth = $('#mainPopup').outerWidth();
        const containerWidth = $('#lefSearchBar').width();

        // Adjust for boundaries
        if (left + popupWidth > containerWidth) {
            left = containerWidth - popupWidth;
        }
        if (left < 0) left = 0;

        $('#mainPopup').css({ 'left': left + 'px' });
    }

    function handleOutsideClick(e) {
        // If the clicked element is no longer in the document (e.g., it was re-rendered),
        // we skip the outside click check to avoid accidentally closing the popup.
        if (!document.body.contains(e.target)) return;

        if (!$(e.target).closest('.search-bar, #mainPopup, .mobile-search-trigger, #mobileModal').length) {
            closePopup();
        }
    }

    // ==================== LOCATION (WP AJAX) ====================

    function showSuggestions(query) {
        const $list = $('#suggestionsList');
        
        // If query is empty and no GPS, hide and don't show popup
        if (!query.trim() && state.geoPermission !== 'granted') {
            $list.empty().hide();
            if (state.activeSection === 'location') {
                $('#mainPopup').removeClass('active');
            }
            return;
        }

        $.ajax({
            url: lef_ajax_obj.ajax_url,
            type: 'POST',
            data: {
                action: 'lef_search_suggestions',
                query: query,
                lat: state.lat,
                lng: state.lng,
                nonce: lef_ajax_obj.nonce
            },
            success: function(response) {
                if (response.success && response.data.length > 0) {
                    let html = '';
                    response.data.forEach(item => {
                        html += `
                            <div class="suggestion-item" onclick="SearchBar.selectLocation('${item.name.replace(/'/g, "\\'")}', '${item.type}', '${(item.address || "").replace(/'/g, "\\'")}', '${(item.location || "").replace(/'/g, "\\'")}')">
                                <div class="suggestion-icon">
                                    <svg viewBox="0 0 24 24" fill="none"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                </div>
                                <div class="suggestion-text">
                                    <strong>${item.name}</strong>
                                    <span>${item.subtitle || ''}</span>
                                </div>
                            </div>`;
                    });
                    $list.html(html).show();
                    // Show popup since we have results
                    if (state.activeSection === 'location') {
                        $('#mainPopup').addClass('active');
                    }
                } else {
                    $list.empty().hide();
                    // Hide popup if no results found
                    if (state.activeSection === 'location') {
                        $('#mainPopup').removeClass('active');
                    }
                }
            },
            error: function() {
                // Hide on error too
                if (state.activeSection === 'location') {
                    $('#mainPopup').removeClass('active');
                }
            }
        });
    }

    function showMobileSuggestions(query) {
        const $list = $('#mobileSuggestions');
        
        if (!query.trim() && state.geoPermission !== 'granted') {
            $list.empty().hide();
            return;
        }

        $.ajax({
            url: lef_ajax_obj.ajax_url,
            type: 'POST',
            data: {
                action: 'lef_search_suggestions',
                query: query,
                lat: state.lat,
                lng: state.lng,
                nonce: lef_ajax_obj.nonce
            },
            success: function(response) {
                if (response.success && response.data.length > 0) {
                    let html = '';
                    response.data.forEach(item => {
                        html += `
                            <div class="suggestion-item" onclick="SearchBar.selectLocation('${item.name.replace(/'/g, "\\'")}', '${item.type}', '${(item.address || "").replace(/'/g, "\\'")}', '${(item.location || "").replace(/'/g, "\\'")}')">
                                <div class="suggestion-icon">
                                    <svg viewBox="0 0 24 24" fill="none"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                                </div>
                                <div class="suggestion-text">
                                    <strong>${item.name}</strong>
                                    <span>${item.subtitle || ''}</span>
                                </div>
                            </div>`;
                    });
                    $list.html(html).show();
                } else {
                    $list.empty().hide();
                }
            }
        });
    }

    function selectLocation(name, type, address, location) {
        state.location = name;
        state.locationType = type || 'Location';
        state.selectedAddress = address || '';
        state.selectedLocation = location || '';
        
        // Update Desktop UI
        $('#locationDisplay').val(name).addClass('has-value');
        $('#locationWrapper').addClass('has-value');
        
        // Update Mobile UI
        $('#mobileLocationInput').val(name);
        $('#mobileSuggestions').empty().hide();
        updateMobileTrigger();
        
        // Close popup after selection
        closePopup();
    }

    function clearLocation(e) {
        e.stopPropagation();
        state.location = '';
        state.locationType = 'Location';
        state.selectedAddress = '';
        state.selectedLocation = '';
        $('#locationDisplay').val('').removeClass('has-value');
        $('#locationWrapper').removeClass('has-value');
        // Remove redundant popup input ref
    }

    // ==================== DATES ====================

    function renderCalendar() {
        const $grid = $('#calendarGrid');
        const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
        const days = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'];
        
        $('#calendarMonthYear').text(`${months[state.calendarMonth.getMonth()]} ${state.calendarMonth.getFullYear()}`);
        
        let html = '';
        days.forEach(d => html += `<div class="calendar-day-header">${d}</div>`);
        
        const firstDay = new Date(state.calendarMonth.getFullYear(), state.calendarMonth.getMonth(), 1).getDay();
        for (let i = 0; i < firstDay; i++) html += '<div></div>';
        
        const daysInMonth = new Date(state.calendarMonth.getFullYear(), state.calendarMonth.getMonth() + 1, 0).getDate();
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        for (let day = 1; day <= daysInMonth; day++) {
            const date = new Date(state.calendarMonth.getFullYear(), state.calendarMonth.getMonth(), day);
            const dateStr = date.toISOString().split('T')[0];
            const isPast = date < today;
            const isToday = date.getTime() === today.getTime();
            const isCheckin = dateStr === state.checkin;
            const isCheckout = dateStr === state.checkout;
            
            let inRange = false;
            if (state.checkin && state.checkout) {
                const cin = new Date(state.checkin);
                const cout = new Date(state.checkout);
                inRange = date > cin && date < cout;
            }
            
            let classes = 'calendar-day';
            if (isPast) classes += ' disabled';
            if (isToday && !isCheckin && !isCheckout) classes += ' today';
            if (isCheckin || isCheckout) classes += ' selected';
            if (inRange) classes += ' in-range';
            
            html += `<button class="${classes}" ${isPast ? 'disabled' : ''} onclick="SearchBar.selectDate('${dateStr}')">${day}</button>`;
        }
        
        $grid.html(html);
    }

    function selectDate(dateStr) {
        if (!state.checkin || (state.checkin && state.checkout)) {
            state.checkin = dateStr;
            state.checkout = '';
        } else {
            if (new Date(dateStr) <= new Date(state.checkin)) {
                state.checkin = dateStr;
                state.checkout = '';
            } else {
                state.checkout = dateStr;
            }
        }
        renderCalendar();
    }

    function applyDates() {
        if (state.checkin) {
            let text = formatDate(state.checkin);
            if (state.checkout) text += ' - ' + formatDate(state.checkout);
            $('#dateDisplay').val(text).addClass('has-value');
            $('#dateWrapper').addClass('has-value');
        }
        closePopup();
    }

    function clearDates(e) {
        if(e) e.stopPropagation();
        state.checkin = '';
        state.checkout = '';
        $('#dateDisplay').val('').removeClass('has-value');
        $('#dateWrapper').removeClass('has-value');
        renderCalendar();
    }

    function formatDate(dateStr) {
        const date = new Date(dateStr);
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
        return `${months[date.getMonth()]} ${date.getDate()}`;
    }

    function prevMonth() { state.calendarMonth.setMonth(state.calendarMonth.getMonth() - 1); renderCalendar(); }
    function nextMonth() { state.calendarMonth.setMonth(state.calendarMonth.getMonth() + 1); renderCalendar(); }

    // ==================== GUESTS ====================

    function updateGuests(type, delta) {
        const min = (type === 'adults') ? 1 : 0;
        state[type] = Math.max(min, state[type] + delta);
        $('#' + type + 'Count').text(state[type]);
        updateGuestButtons();
        updateGuestDisplay();
    }

    function updateGuestButtons() {
        $('#adultsMinus').prop('disabled', state.adults <= 0);
        $('#childrenMinus').prop('disabled', state.children <= 0);
        $('#infantsMinus').prop('disabled', state.infants <= 0);
        
        // Mobile buttons
        $('#mobileAdultsMinus').prop('disabled', state.adults <= 0);
        $('#mobileChildrenMinus').prop('disabled', state.children <= 0);
        $('#mobileInfantsMinus').prop('disabled', state.infants <= 0);
    }

    function updateGuestDisplay() {
        const total = state.adults + state.children;
        if (total > 0) {
            let text = `${total} guest${total > 1 ? 's' : ''}`;
            if (state.infants > 0) text += `, ${state.infants} infant${state.infants > 1 ? 's' : ''}`;
            $('#guestDisplay').val(text).addClass('has-value');
            $('#guestWrapper').addClass('has-value');
        } else {
            $('#guestDisplay').val('').removeClass('has-value');
            $('#guestWrapper').removeClass('has-value');
        }
    }

    function clearGuests(e) {
        e.stopPropagation();
        state.adults = 0; state.children = 0; state.infants = 0;
        $('#adultsCount').text('0'); $('#childrenCount').text('0'); $('#infantsCount').text('0');
        $('#mobileAdultsCount').text('0'); $('#mobileChildrenCount').text('0'); $('#mobileInfantsCount').text('0');
        updateGuestButtons(); updateGuestDisplay();
    }

    // ==================== MOBILE MODAL ====================

    function openMobileModal() {
        $('#mobileModal').addClass('active');
        $('body').css('overflow', 'hidden');
        switchMobileTab('location');
        renderMobileCalendar();
    }

    function closeMobileModal() {
        $('#mobileModal').removeClass('active');
        $('body').css('overflow', '');
        updateMobileTrigger();
    }

    function switchMobileTab(tab) {
        $('.mobile-tab').removeClass('active');
        $('.mobile-tab-content').removeClass('active');
        $(`.mobile-tab[data-tab="${tab}"]`).addClass('active');
        $('#mobile' + tab.charAt(0).toUpperCase() + tab.slice(1) + 'Tab').addClass('active');

        if (tab === 'location') {
            const $input = $('#mobileLocationInput');
            if (state.geoPermission === 'prompt') {
                requestGeolocation(() => {
                    showMobileSuggestions($input.val());
                });
            } else {
                showMobileSuggestions($input.val());
            }
        }
    }

    function renderMobileCalendar() {
        const $grid = $('#mobileCalendarGrid');
        const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
        $('#mobileCalendarMonth').text(`${months[state.mobileCalendarMonth.getMonth()]} ${state.mobileCalendarMonth.getFullYear()}`);
        
        let html = '';
        const days = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'];
        days.forEach(d => html += `<div class="calendar-day-header">${d}</div>`);
        
        const firstDay = new Date(state.mobileCalendarMonth.getFullYear(), state.mobileCalendarMonth.getMonth(), 1).getDay();
        for (let i = 0; i < firstDay; i++) html += '<div></div>';
        
        const daysInMonth = new Date(state.mobileCalendarMonth.getFullYear(), state.mobileCalendarMonth.getMonth() + 1, 0).getDate();
        const today = new Date();
        today.setHours(0, 0, 0, 0);

        for (let day = 1; day <= daysInMonth; day++) {
            const date = new Date(state.mobileCalendarMonth.getFullYear(), state.mobileCalendarMonth.getMonth(), day);
            const dateStr = date.toISOString().split('T')[0];
            const isPast = date < today;
            const isToday = date.getTime() === today.getTime();
            const isCheckin = dateStr === state.checkin;
            const isCheckout = dateStr === state.checkout;
            
            let inRange = false;
            if (state.checkin && state.checkout) {
                const cin = new Date(state.checkin);
                const cout = new Date(state.checkout);
                inRange = date > cin && date < cout;
            }
            
            let classes = 'calendar-day';
            if (isPast) classes += ' disabled';
            if (isToday && !isCheckin && !isCheckout) classes += ' today';
            if (isCheckin || isCheckout) classes += ' selected';
            if (inRange) classes += ' in-range';
            
            html += `<button class="${classes}" ${isPast ? 'disabled' : ''} onclick="SearchBar.mobileSelectDate('${dateStr}')">${day}</button>`;
        }
        $grid.html(html);
    }

    function mobileSelectDate(dateStr) {
        selectDate(dateStr);
        renderMobileCalendar();
        updateMobileTrigger();
    }

    function updateMobileTrigger() {
        $('#mobileLocationText').text(state.location || 'Anywhere');
        let dText = (state.checkin) ? formatDate(state.checkin) : 'Any week';
        if (state.checkout) dText += ' - ' + formatDate(state.checkout);
        const gText = (state.adults + state.children > 0) ? `${state.adults + state.children} guests` : 'Add guests';
        $('#mobileDetailText').text(`${dText} · ${gText}`);
    }

    function resetMobile() {
        state.location = ''; state.checkin = ''; state.checkout = ''; state.adults = 0; state.children = 0; state.infants = 0;
        $('#mobileLocationInput').val('');
        updateMobileTrigger();
        renderMobileCalendar();
    }

    // ==================== SEARCH HANDLING ====================

    function handleSearch() {
        // Validation: At least one field must be filled
        const hasLocation = state.location.trim() !== '';
        const hasDates = state.checkin !== '';
        const hasGuests = (state.adults + state.children + state.infants) > 0;

        if (!hasLocation && !hasDates && !hasGuests) {
            if (window.LEF_Toast) {
                window.LEF_Toast.show('Please select a destination, date, or guests to search.', 'error');
            } else {
                alert('Please select a destination, date, or guests to search.');
            }
            return;
        }

        const query = new URLSearchParams();
        
        if (state.location) {
            if (state.locationType === 'Property') {
                query.set('address', state.selectedAddress);
                query.set('location', state.selectedLocation);
            } else {
                query.set('location', state.location);
            }
        }
        
        if (state.checkin) query.set('checkin', state.checkin);
        if (state.checkout) query.set('checkout', state.checkout);
        
        // Unified guests parameter (Excludes infants as per requirements)
        const totalGuests = state.adults + state.children;
        query.set('guests', totalGuests);
        
        // Pass infants as separate parameter
        if (state.infants > 0) {
            query.set('infant', state.infants);
        }

        const target = state.archiveUrl + (state.archiveUrl.includes('?') ? '&' : '?') + query.toString();
        window.location.href = target;
    }

    function handleMobileSearch() {
        const mobLoc = $('#mobileLocationInput').val();
        if (mobLoc) state.location = mobLoc;
        handleSearch();
    }

    $(document).ready(init);

    return {
        openSection,
        closePopup,
        showSuggestions,
        showMobileSuggestions,
        selectLocation,
        clearLocation,
        prevMonth,
        nextMonth,
        selectDate,
        applyDates,
        clearDates,
        updateGuests,
        clearGuests,
        openMobileModal,
        closeMobileModal,
        switchMobileTab,
        mobilePrevMonth: () => { state.mobileCalendarMonth.setMonth(state.mobileCalendarMonth.getMonth() - 1); renderMobileCalendar(); },
        mobileNextMonth: () => { state.mobileCalendarMonth.setMonth(state.mobileCalendarMonth.getMonth() + 1); renderMobileCalendar(); },
        mobileSelectDate,
        updateMobileGuests: (t, d) => { updateGuests(t, d); $('#mobile' + t.charAt(0).toUpperCase() + t.slice(1) + 'Count').text(state[t]); },
        resetMobile,
        handleSearch,
        handleMobileSearch
    };

})(jQuery);
