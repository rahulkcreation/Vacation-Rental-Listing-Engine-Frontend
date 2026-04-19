<?php if ( ! defined( 'ABSPATH' ) ) exit; ?>
<div class="lef-host-list-page">
    <header class="lef-host-list-header">
        <div class="lef-host-list-header-left">
            <div class="lef-host-list-header-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M3 21h18"></path>
                    <path d="M5 21V7l8-4v18"></path>
                    <path d="M19 21V11l-6-4"></path>
                    <path d="M9 9h.01"></path>
                    <path d="M9 13h.01"></path>
                    <path d="M9 17h.01"></path>
                </svg>
            </div>
            <h1 class="lef-host-list-title">My Listings</h1>
        </div>

        <button class="lef-host-list-add-btn" type="button" id="lef-host-list-add-btn">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M12 5v14"></path>
                <path d="M5 12h14"></path>
            </svg>
            Add New Property
        </button>
    </header>

    <section class="lef-host-list-search-panel" id="lef-host-list-search-panel" aria-label="Property search">
        <div class="lef-host-list-search-box">
            <span class="lef-host-list-search-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"></circle>
                    <path d="m21 21-4.35-4.35"></path>
                </svg>
            </span>
            <input class="lef-host-list-search-input" id="lef-host-list-search-input" type="search"
                placeholder="Search by title..." autocomplete="off">
            <button class="lef-host-list-clear-btn" id="lef-host-list-clear-btn" type="button"
                aria-label="Clear search">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                    stroke-linecap="round" stroke-linejoin="round">
                    <path d="M18 6 6 18"></path>
                    <path d="m6 6 12 12"></path>
                </svg>
            </button>
        </div>
    </section>

    <nav class="lef-host-list-tabs" aria-label="Property status tabs">
        <button class="lef-host-list-tab lef-host-list-tab-active" type="button" data-lef-host-list-status-tab="published"
            aria-pressed="true">
            Published <span class="lef-host-list-tab-count" id="lef-host-list-count-published">0</span>
        </button>
        <button class="lef-host-list-tab" type="button" data-lef-host-list-status-tab="pending" aria-pressed="false">
            Pending <span class="lef-host-list-tab-count" id="lef-host-list-count-pending">0</span>
        </button>
        <button class="lef-host-list-tab" type="button" data-lef-host-list-status-tab="draft" aria-pressed="false">
            Draft <span class="lef-host-list-tab-count" id="lef-host-list-count-draft">0</span>
        </button>
        <button class="lef-host-list-tab" type="button" data-lef-host-list-status-tab="rejected" aria-pressed="false">
            Rejected <span class="lef-host-list-tab-count" id="lef-host-list-count-rejected">0</span>
        </button>
    </nav>

    <section class="lef-host-list-list-panel" aria-label="Property list">
        <div class="lef-host-list-select-row-wrap">
            <label class="lef-host-list-select-row" for="lef-host-list-select-all">
                <input class="lef-host-list-checkbox" id="lef-host-list-select-all" type="checkbox">
                <span class="lef-host-list-select-label">Select All</span>
            </label>
            <div class="lef-host-list-bulk-actions" id="lef-host-list-bulk-actions" style="display: none;">
                <select id="lef-host-list-bulk-status" class="lef-host-list-bulk-select">
                    <option value="">Bulk Status</option>
                    <option value="pending">Set Pending</option>
                    <option value="draft">Set Draft</option>
                </select>
                <button type="button" class="lef-host-list-bulk-btn" id="lef-host-list-bulk-apply-status">Apply Status</button>
                <button type="button" class="lef-host-list-bulk-btn lef-host-list-bulk-delete" id="lef-host-list-bulk-delete">Delete Selected</button>
            </div>
        </div>

        <div class="lef-host-list-card-list" id="lef-host-list-card-list"></div>

        <div class="lef-host-list-empty" id="lef-host-list-empty">
            <p class="lef-host-list-empty-title">No properties found</p>
            <p class="lef-host-list-empty-text">Try another status tab or search keyword.</p>
        </div>

        <div class="lef-host-list-pagination" id="lef-host-list-pagination">
            <span class="lef-host-list-pagination-text" id="lef-host-list-pagination-text">Showing 0 of 0</span>
            <div class="lef-host-list-pagination-controls" id="lef-host-list-pagination-controls"></div>
        </div>
    </section>
</div>
