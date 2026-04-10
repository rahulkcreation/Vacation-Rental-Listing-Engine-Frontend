<?php
/**
 * search-bar.php Template
 *
 * Exact replica of search.html structure adapted for WordPress.
 *
 * @package ListingEngineFrontend
 */

if (! defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Fetch Archive URL
$archive_page_id = $wpdb->get_var($wpdb->prepare(
    "SELECT page_id FROM {$wpdb->prefix}admin_management WHERE name = %s",
    'Listing Archive'
));
$archive_url = $archive_page_id ? get_permalink($archive_page_id) : home_url('/');
?>

<div class="lef-search-container" id="lefSearchBar" data-archive-url="<?php echo esc_url($archive_url); ?>">
    <!-- Desktop Search Bar -->
    <section class="search-section desktop-only">
        <div class="search-bar">
            <!-- Location -->
            <div class="search-field" id="locationField">
                <label for="locationDisplay">Where</label>
                <div class="search-field-wrapper" id="locationWrapper">
                    <input type="text" id="locationDisplay" placeholder="Search destination" autocomplete="off" 
                           onclick="SearchBar.openSection('location')" 
                           oninput="SearchBar.showSuggestions(this.value)">
                    <button class="field-clear-btn" type="button" onclick="SearchBar.clearLocation(event)">
                        <svg viewBox="0 0 24 24" fill="none"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    </button>
                </div>
            </div>

            <!-- When -->
            <div class="search-field" id="dateField" onclick="SearchBar.openSection('date')">
                <label>When</label>
                <div class="search-field-wrapper" id="dateWrapper">
                    <input type="text" id="dateDisplay" placeholder="Add dates" readonly>
                    <button class="field-clear-btn" type="button" onclick="SearchBar.clearDates(event)">
                        <svg viewBox="0 0 24 24" fill="none"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    </button>
                </div>
            </div>

            <!-- Who -->
            <div class="search-field" id="guestField" onclick="SearchBar.openSection('guests')">
                <label>Who</label>
                <div class="search-field-wrapper" id="guestWrapper">
                    <input type="text" id="guestDisplay" placeholder="Add guests" readonly>
                    <button class="field-clear-btn" type="button" onclick="SearchBar.clearGuests(event)">
                        <svg viewBox="0 0 24 24" fill="none"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                    </button>
                </div>
            </div>

            <!-- Search Button -->
            <button class="search-btn" type="button" onclick="SearchBar.handleSearch()">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                </svg>
            </button>
        </div>
    </section>

    <!-- Mobile Search Bar Trigger -->
    <section class="mobile-search-section mobile-only">
        <div class="mobile-search-trigger" onclick="SearchBar.openMobileModal()">
            <svg viewBox="0 0 24 24" fill="none"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
            <div class="mobile-search-text">
                <span id="mobileLocationText">Anywhere</span>
                <span id="mobileDetailText">Any week · Add guests</span>
            </div>
            <div class="mobile-search-icon">
                <svg viewBox="0 0 24 24" fill="none"><path d="M4 21v-7m0-4V3m8 18v-11m0-4V3m8 18v-3m0-4V3M1 14h6m2-6h6m2 10h6"/></svg>
            </div>
        </div>
    </section>

    <!-- Shared Popup (Desktop) -->
    <div class="search-popup" id="mainPopup">

        <!-- Location Section -->
        <div class="popup-section location-section" id="locationSection">
            <!-- Redundant input removed as requested -->
            <div class="suggestions-list" id="suggestionsList"></div>
        </div>

        <!-- Date Section -->
        <div class="popup-section date-section" id="dateSection">
            <div class="calendar-wrapper">
                <div class="calendar-header">
                    <button class="calendar-nav-btn" type="button" onclick="SearchBar.prevMonth()">
                        <svg viewBox="0 0 24 24" fill="none"><path d="M15 18l-6-6 6-6"/></svg>
                    </button>
                    <div class="calendar-month-year" id="calendarMonthYear"></div>
                    <button class="calendar-nav-btn" type="button" onclick="SearchBar.nextMonth()">
                        <svg viewBox="0 0 24 24" fill="none"><path d="M9 18l6-6-6-6"/></svg>
                    </button>
                </div>
                <div class="calendar-grid" id="calendarGrid"></div>
                <div class="calendar-footer">
                    <button class="calendar-btn clear" type="button" onclick="SearchBar.clearDates(event)">Clear</button>
                    <button class="calendar-btn apply" type="button" onclick="SearchBar.applyDates()">Apply</button>
                </div>
            </div>
        </div>

        <!-- Guests Section -->
        <div class="popup-section guests-section" id="guestsSection">
            <div class="guest-counter">
                <div class="guest-row">
                    <div class="guest-info"><h4>Adults</h4><p>Ages 13 or above</p></div>
                    <div class="guest-controls">
                        <button class="guest-btn" type="button" id="adultsMinus" onclick="SearchBar.updateGuests('adults', -1)">−</button>
                        <span class="guest-count" id="adultsCount">0</span>
                        <button class="guest-btn" type="button" id="adultsPlus" onclick="SearchBar.updateGuests('adults', 1)">+</button>
                    </div>
                </div>
                <div class="guest-row">
                    <div class="guest-info"><h4>Children</h4><p>Ages 2 – 12</p></div>
                    <div class="guest-controls">
                        <button class="guest-btn" type="button" id="childrenMinus" onclick="SearchBar.updateGuests('children', -1)">−</button>
                        <span class="guest-count" id="childrenCount">0</span>
                        <button class="guest-btn" type="button" id="childrenPlus" onclick="SearchBar.updateGuests('children', 1)">+</button>
                    </div>
                </div>
                <div class="guest-row">
                    <div class="guest-info"><h4>Infants</h4><p>Under 2</p></div>
                    <div class="guest-controls">
                        <button class="guest-btn" type="button" id="infantsMinus" onclick="SearchBar.updateGuests('infants', -1)">−</button>
                        <span class="guest-count" id="infantsCount">0</span>
                        <button class="guest-btn" type="button" id="infantsPlus" onclick="SearchBar.updateGuests('infants', 1)">+</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Mobile Modal -->
    <div class="mobile-modal-overlay" id="mobileModal">
        <div class="mobile-modal-header">
            <button class="mobile-modal-back" type="button" onclick="SearchBar.closeMobileModal()">
                <svg viewBox="0 0 24 24" fill="none"><path d="M15 18l-6-6 6-6"/></svg>
            </button>
            <div class="mobile-modal-title">Search</div>
            <button class="mobile-modal-clear" type="button" onclick="SearchBar.resetMobile()">Clear all</button>
        </div>
        <div class="mobile-tabs">
            <div class="mobile-tab active" data-tab="location" onclick="SearchBar.switchMobileTab('location')">Where</div>
            <div class="mobile-tab" data-tab="date" onclick="SearchBar.switchMobileTab('date')">When</div>
            <div class="mobile-tab" data-tab="guests" onclick="SearchBar.switchMobileTab('guests')">Who</div>
        </div>
        <div class="mobile-content">
            <!-- Location Tab -->
            <div class="mobile-tab-content active" id="mobileLocationTab">
                <div class="mobile-location-input">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                    <input type="text" id="mobileLocationInput" placeholder="Search destinations" oninput="SearchBar.showMobileSuggestions(this.value)">
                </div>
                <div class="mobile-suggestions" id="mobileSuggestions"></div>
            </div>
            <!-- Date Tab -->
            <div class="mobile-tab-content" id="mobileDateTab">
                <div class="mobile-date-picker">
                    <div class="mobile-calendar-header">
                        <div class="mobile-calendar-month" id="mobileCalendarMonth"></div>
                    </div>
                    <div class="mobile-calendar-nav">
                        <button class="mobile-calendar-nav-btn" type="button" onclick="SearchBar.mobilePrevMonth()">
                            <svg viewBox="0 0 24 24" fill="none"><path d="M15 18l-6-6 6-6"/></svg>
                        </button>
                        <button class="mobile-calendar-nav-btn" type="button" onclick="SearchBar.mobileNextMonth()">
                            <svg viewBox="0 0 24 24" fill="none"><path d="M9 18l6-6-6-6"/></svg>
                        </button>
                    </div>
                    <div class="calendar-grid" id="mobileCalendarGrid"></div>
                </div>
            </div>
            <!-- Guests Tab -->
            <div class="mobile-tab-content" id="mobileGuestsTab">
                <div class="guest-counter">
                    <div class="guest-row">
                        <div class="guest-info"><h4>Adults</h4><p>Ages 13 or above</p></div>
                        <div class="guest-controls">
                            <button class="guest-btn" type="button" id="mobileAdultsMinus" onclick="SearchBar.updateMobileGuests('adults', -1)">−</button>
                            <span class="guest-count" id="mobileAdultsCount">0</span>
                            <button class="guest-btn" type="button" id="mobileAdultsPlus" onclick="SearchBar.updateMobileGuests('adults', 1)">+</button>
                        </div>
                    </div>
                    <div class="guest-row">
                        <div class="guest-info"><h4>Children</h4><p>Ages 2 – 12</p></div>
                        <div class="guest-controls">
                            <button class="guest-btn" type="button" id="mobileChildrenMinus" onclick="SearchBar.updateMobileGuests('children', -1)">−</button>
                            <span class="guest-count" id="mobileChildrenCount">0</span>
                            <button class="guest-btn" type="button" id="mobileChildrenPlus" onclick="SearchBar.updateMobileGuests('children', 1)">+</button>
                        </div>
                    </div>
                    <div class="guest-row">
                        <div class="guest-info"><h4>Infants</h4><p>Under 2</p></div>
                        <div class="guest-controls">
                            <button class="guest-btn" type="button" id="mobileInfantsMinus" onclick="SearchBar.updateMobileGuests('infants', -1)">−</button>
                            <span class="guest-count" id="mobileInfantsCount">0</span>
                            <button class="guest-btn" type="button" id="mobileInfantsPlus" onclick="SearchBar.updateMobileGuests('infants', 1)">+</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="mobile-footer">
            <button class="mobile-search-btn" type="button" onclick="SearchBar.handleMobileSearch()">Search</button>
        </div>
    </div>
</div>
