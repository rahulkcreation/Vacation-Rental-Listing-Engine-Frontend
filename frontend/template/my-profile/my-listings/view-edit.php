<?php
/**
 * View / Edit Property Form Template.
 *
 * Rendered inside the My Listings bucket as a hidden panel.
 * Shown when user clicks "Add New Property" (mode=new) or "Edit" (mode=edit).
 * All class names and IDs are scoped with the `lef-ve-` prefix.
 *
 * @package ListingEngineFrontend
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div class="lef-ve-panel" id="lef-ve-panel" style="display:none;" role="region" aria-label="Property Form">

    <!-- Hidden state fields -->
    <input type="hidden" id="lef-ve-property-id" value="0">
    <input type="hidden" id="lef-ve-mode" value="new">
    <input type="hidden" id="lef-ve-original-status" value="">

    <form id="lef-ve-form" novalidate autocomplete="off">

        <!-- ===== SECTION 1: NAV BAR ===== -->
        <nav class="lef-ve-nav" aria-label="Form navigation">
            <button type="button" class="lef-ve-back-btn" id="lef-ve-back-btn" aria-label="Back to listings">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/>
                </svg>
            </button>
            <span class="lef-ve-nav-heading" id="lef-ve-nav-heading">Back to Listings</span>
        </nav>

        <!-- ===== SECTION 2: PROPERTY TITLE ===== -->
        <div class="lef-ve-section">
            <label for="lef-ve-title" class="lef-ve-label">
                Property Title <span class="lef-ve-required" aria-hidden="true">*</span>
            </label>
            <input
                type="text"
                id="lef-ve-title"
                class="lef-ve-input"
                placeholder="Enter property title..."
                aria-required="true"
                maxlength="255"
            >
        </div>

        <!-- ===== SECTION 3: PROPERTY IMAGES ===== -->
        <div class="lef-ve-section">
            <label class="lef-ve-label">
                Property Images <span class="lef-ve-required" aria-hidden="true">*</span>
            </label>
            <!-- Upload trigger area -->
            <div
                class="lef-ve-upload-area"
                id="lef-ve-upload-area"
                role="button"
                tabindex="0"
                aria-label="Click to select images from media library"
            >
                <div class="lef-ve-upload-icon" aria-hidden="true">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 16.5V9.75m0 0 3 3m-3-3-3 3M6.75 19.5a4.5 4.5 0 0 1-1.41-8.775 5.25 5.25 0 0 1 10.233-2.33 3 3 0 0 1 3.758 3.848A3.752 3.752 0 0 1 18 19.5H6.75Z"/>
                    </svg>
                </div>
                <div class="lef-ve-upload-text">Click to upload or drag &amp; drop</div>
                <div class="lef-ve-upload-subtext">JPG, WEBP, AVIF &middot; Min 5, Max 10 images &middot; Max 1MB each</div>
            </div>
            <!-- Image preview grid — populated by JS -->
            <div class="lef-ve-image-grid" id="lef-ve-image-grid" aria-live="polite"></div>
        </div>

        <!-- ===== SECTION 4: DESCRIPTION ===== -->
        <div class="lef-ve-section">
            <label for="lef-ve-description" class="lef-ve-label">
                Description <span class="lef-ve-required" aria-hidden="true">*</span>
            </label>
            <textarea
                id="lef-ve-description"
                class="lef-ve-textarea"
                placeholder="Describe your property, amenities, nearby attractions..."
                aria-required="true"
                rows="6"
            ></textarea>
        </div>

        <!-- ===== SECTION 5: PROPERTY DETAILS GRID ===== -->
        <div class="lef-ve-section">
            <div class="lef-ve-section-heading">
                Property Details <span class="lef-ve-required" aria-hidden="true">*</span>
            </div>
            <div class="lef-ve-details-grid">
                <div class="lef-ve-detail-field">
                    <label for="lef-ve-guests" class="lef-ve-field-label">Guests <span class="lef-ve-required">*</span></label>
                    <input type="number" id="lef-ve-guests" class="lef-ve-number-input" min="1" step="1" placeholder="0" aria-required="true">
                </div>
                <div class="lef-ve-detail-field">
                    <label for="lef-ve-bedrooms" class="lef-ve-field-label">Bedrooms <span class="lef-ve-required">*</span></label>
                    <input type="number" id="lef-ve-bedrooms" class="lef-ve-number-input" min="0" step="1" placeholder="0" aria-required="true">
                </div>
                <div class="lef-ve-detail-field">
                    <label for="lef-ve-beds" class="lef-ve-field-label">Beds <span class="lef-ve-required">*</span></label>
                    <input type="number" id="lef-ve-beds" class="lef-ve-number-input" min="0" step="1" placeholder="0" aria-required="true">
                </div>
                <div class="lef-ve-detail-field">
                    <label for="lef-ve-bathrooms" class="lef-ve-field-label">Bathrooms <span class="lef-ve-required">*</span></label>
                    <input type="number" id="lef-ve-bathrooms" class="lef-ve-number-input" min="0" step="1" placeholder="0" aria-required="true">
                </div>
                <div class="lef-ve-detail-field">
                    <label for="lef-ve-price" class="lef-ve-field-label">Price (&#8377;/night) <span class="lef-ve-required">*</span></label>
                    <input type="number" id="lef-ve-price" class="lef-ve-number-input" min="0" step="1" placeholder="0" aria-required="true">
                </div>
            </div>
        </div>

        <!-- ===== SECTION 6: AMENITIES (multi-select) ===== -->
        <div class="lef-ve-section">
            <label class="lef-ve-label">
                Amenities <span class="lef-ve-required" aria-hidden="true">*</span>
            </label>
            <div class="lef-ve-custom-select-wrap" id="lef-ve-amenities-wrap">
                <div
                    class="lef-ve-select-trigger"
                    id="lef-ve-amenities-trigger"
                    tabindex="0"
                    role="combobox"
                    aria-expanded="false"
                    aria-haspopup="listbox"
                    aria-label="Select amenities"
                >
                    <span class="lef-ve-selected-tags" id="lef-ve-amenities-tags">
                        <span class="lef-ve-placeholder">Select amenities...</span>
                    </span>
                    <svg class="lef-ve-arrow" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
                    </svg>
                </div>
                <div class="lef-ve-dropdown" id="lef-ve-amenities-dropdown" role="listbox" aria-label="Amenities options">
                    <!-- Populated by JS via AJAX -->
                </div>
            </div>
        </div>

        <!-- ===== SECTION 7: LOCATION + PROPERTY TYPE (two-column) ===== -->
        <div class="lef-ve-section lef-ve-two-col-section">

            <!-- Location -->
            <div>
                <label class="lef-ve-label">
                    Location <span class="lef-ve-required" aria-hidden="true">*</span>
                </label>
                <div class="lef-ve-custom-select-wrap" id="lef-ve-location-wrap">
                    <div
                        class="lef-ve-select-trigger"
                        id="lef-ve-location-trigger"
                        tabindex="0"
                        role="combobox"
                        aria-expanded="false"
                        aria-haspopup="listbox"
                        aria-label="Select location"
                    >
                        <span id="lef-ve-location-display">
                            <span class="lef-ve-placeholder">Select location...</span>
                        </span>
                        <svg class="lef-ve-arrow" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
                        </svg>
                    </div>
                    <div class="lef-ve-dropdown" id="lef-ve-location-dropdown" role="listbox"></div>
                </div>
            </div>

            <!-- Property Type -->
            <div>
                <label class="lef-ve-label">
                    Property Type <span class="lef-ve-required" aria-hidden="true">*</span>
                </label>
                <div class="lef-ve-custom-select-wrap" id="lef-ve-type-wrap">
                    <div
                        class="lef-ve-select-trigger"
                        id="lef-ve-type-trigger"
                        tabindex="0"
                        role="combobox"
                        aria-expanded="false"
                        aria-haspopup="listbox"
                        aria-label="Select property type"
                    >
                        <span id="lef-ve-type-display">
                            <span class="lef-ve-placeholder">Select type...</span>
                        </span>
                        <svg class="lef-ve-arrow" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
                        </svg>
                    </div>
                    <div class="lef-ve-dropdown" id="lef-ve-type-dropdown" role="listbox"></div>
                </div>
            </div>

        </div>

        <!-- ===== SECTION 8: PROPERTY ADDRESS ===== -->
        <div class="lef-ve-section">
            <label for="lef-ve-address" class="lef-ve-label">
                Property Address <span class="lef-ve-required" aria-hidden="true">*</span>
            </label>
            <input
                type="text"
                id="lef-ve-address"
                class="lef-ve-input"
                placeholder="e.g. Sector 10A, Gurugram"
                aria-required="true"
                maxlength="500"
            >
        </div>

        <!-- ===== SECTION 9: BLOCK DATES CALENDAR ===== -->
        <div class="lef-ve-section">
            <label class="lef-ve-label">
                Block Dates
                <span class="lef-ve-label-hint">(Click dates to mark unavailable)</span>
            </label>
            <div class="lef-ve-calendar-container">

                <!-- Desktop dual-month nav -->
                <div class="lef-ve-cal-nav" id="lef-ve-cal-nav">
                    <button type="button" class="lef-ve-cal-btn" id="lef-ve-cal-prev" aria-label="Previous month">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" width="18" height="18">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/>
                        </svg>
                    </button>
                    <span class="lef-ve-cal-nav-title" id="lef-ve-cal-title"></span>
                    <button type="button" class="lef-ve-cal-btn" id="lef-ve-cal-next" aria-label="Next month">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" width="18" height="18">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                        </svg>
                    </button>
                </div>

                <!-- Mobile single-month nav -->
                <div class="lef-ve-cal-mobile-nav" id="lef-ve-cal-mobile-nav">
                    <button type="button" class="lef-ve-cal-btn" id="lef-ve-cal-mob-prev" aria-label="Previous month">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" width="18" height="18">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5"/>
                        </svg>
                    </button>
                    <span class="lef-ve-cal-mobile-title" id="lef-ve-cal-mob-title"></span>
                    <button type="button" class="lef-ve-cal-btn" id="lef-ve-cal-mob-next" aria-label="Next month">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" width="18" height="18">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5"/>
                        </svg>
                    </button>
                </div>

                <!-- Calendar months — populated by JS -->
                <div class="lef-ve-cal-months-wrap" id="lef-ve-cal-months" role="grid" aria-label="Block dates calendar"></div>

                <!-- Selected/blocked dates summary -->
                <div class="lef-ve-dates-summary" id="lef-ve-dates-summary" style="display:none;">
                    <div class="lef-ve-dates-count" id="lef-ve-dates-count">0 date(s) blocked</div>
                    <div class="lef-ve-dates-chips" id="lef-ve-dates-chips" aria-live="polite"></div>
                </div>

            </div>
        </div>

        <!-- ===== SECTION 10: LISTING STATUS ===== -->
        <div class="lef-ve-section">
            <label class="lef-ve-label">
                Listing Status <span class="lef-ve-required" aria-hidden="true">*</span>
            </label>
            <div class="lef-ve-custom-select-wrap" id="lef-ve-status-wrap">
                <div
                    class="lef-ve-select-trigger"
                    id="lef-ve-status-trigger"
                    tabindex="0"
                    role="combobox"
                    aria-expanded="false"
                    aria-haspopup="listbox"
                    aria-label="Select listing status"
                >
                    <span id="lef-ve-status-display">
                        <span class="lef-ve-placeholder">Select status...</span>
                    </span>
                    <svg class="lef-ve-arrow" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/>
                    </svg>
                </div>
                <div class="lef-ve-dropdown" id="lef-ve-status-dropdown" role="listbox">
                    <div class="lef-ve-status-option" data-value="draft" role="option">
                        <span class="lef-ve-status-dot lef-ve-dot-draft" aria-hidden="true"></span>
                        <span>Draft</span>
                    </div>
                    <div class="lef-ve-status-option" data-value="pending" role="option">
                        <span class="lef-ve-status-dot lef-ve-dot-pending" aria-hidden="true"></span>
                        <span>Pending Review</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- ===== SECTION 11: SUBMIT BUTTON ===== -->
        <button type="submit" class="lef-ve-submit-btn" id="lef-ve-submit-btn">
            ADD LISTING
        </button>

    </form><!-- end #lef-ve-form -->

    <!-- Form loading overlay (shown during AJAX) -->
    <div class="lef-ve-loader-overlay" id="lef-ve-loader-overlay" style="display:none;" aria-hidden="true">
        <div class="lef-spinner"></div>
    </div>

</div><!-- end #lef-ve-panel -->
