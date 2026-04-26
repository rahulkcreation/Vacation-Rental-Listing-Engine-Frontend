<?php

/**
 * View/Edit Reservation Standalone Template.
 *
 * @package ListingEngineFrontend
 */

if (! defined('ABSPATH')) {
    exit;
}

// Assuming $reserv is passed from lef_render_manage_reservations_page in registery-hooks.php
if (! isset($reserv)) {
    echo '<div class="wrap"><div class="error"><p>Reservation data not found.</p></div></div>';
    return;
}

$status_label = ucfirst($reserv['status']);
$property_url = lef_get_secure_detail_url($reserv['property_id']);
?>

<div class="wrap">
    <!-- This hidden h2 and the empty notice container catch WordPress admin notices before they get moved into our custom header. -->
    <h2 class="lef-admin-notice-placeholder"></h2>
    <div id="lef-reserv-edit-page-wrapper" class="lef-global-plugin-wrapper">
        <main class="lef-reserv-edit-page">
            <header class="lef-reserv-edit-topbar">
                <a class="lef-reserv-edit-back-btn" href="admin.php?page=lef-manage-reservations" aria-label="Back to reservations">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="m15 18-6-6 6-6"></path>
                    </svg>
                    Back
                </a>
                <h1 class="lef-reserv-edit-page-title">Reservation Details</h1>
                <span class="lef-reserv-edit-status-badge" id="lef-reserv-edit-current-status"
                    data-lef-reserv-edit-status="<?php echo esc_attr($reserv['status']); ?>">
                    <?php echo esc_html($status_label); ?>
                </span>
            </header>

            <section class="lef-reserv-edit-summary" aria-label="Reservation summary">
                <div class="lef-reserv-edit-field">
                    <span class="lef-reserv-edit-label">Property Name</span>
                    <span class="lef-reserv-edit-value lef-reserv-edit-property-name">
                        <?php echo esc_html($reserv['property_title']); ?>
                    </span>
                </div>
                <div class="lef-reserv-edit-field">
                    <span class="lef-reserv-edit-label">Last Updated</span>
                    <span class="lef-reserv-edit-value">
                        <?php echo esc_html(date('F j, Y g:i A', strtotime($reserv['updated_at']))); ?>
                    </span>
                </div>
                <div class="lef-reserv-edit-summary-action">
                    <a href="<?php echo esc_url($property_url); ?>" class="lef-reserv-edit-view-btn" target="_blank">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7-10-7-10-7z"></path>
                            <circle cx="12" cy="12" r="3"></circle>
                        </svg>
                        View Property
                    </a>
                </div>
            </section>

            <div class="lef-reserv-edit-content-grid">
                <!-- Request Details -->
                <section class="lef-reserv-edit-section" aria-labelledby="lef-reserv-edit-request-title">
                    <div class="lef-reserv-edit-section-head">
                        <span class="lef-reserv-edit-section-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path d="M8 2v4"></path>
                                <path d="M16 2v4"></path>
                                <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                                <path d="M3 10h18"></path>
                            </svg>
                        </span>
                        <h2 class="lef-reserv-edit-section-title" id="lef-reserv-edit-request-title">Request Details</h2>
                    </div>
                    <div class="lef-reserv-edit-detail-list">
                        <div class="lef-reserv-edit-detail-row">
                            <span class="lef-reserv-edit-detail-label">Reservation No</span>
                            <span class="lef-reserv-edit-detail-value"><?php echo esc_html($reserv['reservation_number']); ?></span>
                        </div>
                        <div class="lef-reserv-edit-detail-row">
                            <span class="lef-reserv-edit-detail-label">Check-in</span>
                            <span class="lef-reserv-edit-detail-value">
                                <?php echo esc_html(date('F j, Y', strtotime($reserv['dates']['check_in']))); ?>
                            </span>
                        </div>
                        <div class="lef-reserv-edit-detail-row">
                            <span class="lef-reserv-edit-detail-label">Check-out</span>
                            <span class="lef-reserv-edit-detail-value">
                                <?php echo esc_html(date('F j, Y', strtotime($reserv['dates']['check_out']))); ?>
                            </span>
                        </div>
                        <div class="lef-reserv-edit-detail-row">
                            <span class="lef-reserv-edit-detail-label">Total Guests</span>
                            <span class="lef-reserv-edit-detail-value">
                                <?php
                                $g = $reserv['guests'];
                                echo esc_html("{$g['adults']} adults, {$g['children']} children, {$g['infants']} infants");
                                ?>
                            </span>
                        </div>
                        <div class="lef-reserv-edit-detail-row">
                            <span class="lef-reserv-edit-detail-label">Total Price</span>
                            <span class="lef-reserv-edit-detail-value">₹<?php echo esc_html(number_format($reserv['total_price'], 2)); ?></span>
                        </div>
                        <div class="lef-reserv-edit-detail-row">
                            <span class="lef-reserv-edit-detail-label">Request Date</span>
                            <span class="lef-reserv-edit-detail-value">
                                <?php echo esc_html(date('F j, Y', strtotime($reserv['created_at']))); ?>
                            </span>
                        </div>
                    </div>
                </section>

                <!-- Traveller Details -->
                <section class="lef-reserv-edit-section" aria-labelledby="lef-reserv-edit-traveller-title">
                    <div class="lef-reserv-edit-section-head">
                        <span class="lef-reserv-edit-section-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21a8 8 0 0 0-16 0"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                        </span>
                        <h2 class="lef-reserv-edit-section-title" id="lef-reserv-edit-traveller-title">Traveller Details</h2>
                    </div>
                    <div class="lef-reserv-edit-detail-list">
                        <div class="lef-reserv-edit-detail-row">
                            <span class="lef-reserv-edit-detail-label">Name</span>
                            <span class="lef-reserv-edit-detail-value"><?php echo esc_html($reserv['traveller']['name']); ?></span>
                        </div>
                        <div class="lef-reserv-edit-detail-row">
                            <span class="lef-reserv-edit-detail-label">Email</span>
                            <span class="lef-reserv-edit-detail-value"><?php echo esc_html($reserv['traveller']['email']); ?></span>
                        </div>
                        <div class="lef-reserv-edit-detail-row">
                            <span class="lef-reserv-edit-detail-label">Phone Number</span>
                            <span class="lef-reserv-edit-detail-value"><?php echo esc_html($reserv['traveller']['phone']); ?></span>
                        </div>
                    </div>
                </section>

                <!-- Host Details -->
                <section class="lef-reserv-edit-section" aria-labelledby="lef-reserv-edit-host-title">
                    <div class="lef-reserv-edit-section-head">
                        <span class="lef-reserv-edit-section-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 21h18"></path>
                                <path d="M5 21V7l8-4v18"></path>
                                <path d="M19 21V11l-6-4"></path>
                                <path d="M9 9h.01"></path>
                                <path d="M9 13h.01"></path>
                                <path d="M9 17h.01"></path>
                            </svg>
                        </span>
                        <h2 class="lef-reserv-edit-section-title" id="lef-reserv-edit-host-title">Host Details</h2>
                    </div>
                    <div class="lef-reserv-edit-detail-list">
                        <div class="lef-reserv-edit-detail-row">
                            <span class="lef-reserv-edit-detail-label">Name</span>
                            <span class="lef-reserv-edit-detail-value"><?php echo esc_html($reserv['host']['name']); ?></span>
                        </div>
                        <div class="lef-reserv-edit-detail-row">
                            <span class="lef-reserv-edit-detail-label">Email</span>
                            <span class="lef-reserv-edit-detail-value"><?php echo esc_html($reserv['host']['email']); ?></span>
                        </div>
                        <div class="lef-reserv-edit-detail-row">
                            <span class="lef-reserv-edit-detail-label">Phone Number</span>
                            <span class="lef-reserv-edit-detail-value"><?php echo esc_html($reserv['host']['phone']); ?></span>
                        </div>
                    </div>
                </section>
            </div>

            <!-- Status Update Card -->
            <section class="lef-reserv-edit-status-card" aria-label="Change reservation status">
                <div>
                    <div class="lef-reserv-edit-status-control">
                        <select class="lef-reserv-edit-status-select" id="lef-reserv-edit-status-select">
                            <option value="pending" <?php selected($reserv['status'], 'pending'); ?>>Pending</option>
                            <option value="completed" <?php selected($reserv['status'], 'completed'); ?>>Completed</option>
                            <option value="rejected" <?php selected($reserv['status'], 'rejected'); ?>>Rejected</option>
                        </select>
                        <span class="lef-reserv-edit-select-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"
                                stroke-linecap="round" stroke-linejoin="round">
                                <path d="m6 9 6 6 6-6"></path>
                            </svg>
                        </span>
                    </div>
                    <p class="lef-reserv-edit-save-note" id="lef-reserv-edit-save-note">Status updated successfully!</p>
                </div>
                <button class="lef-reserv-edit-save-btn" id="lef-reserv-edit-save-btn" type="button" data-id="<?php echo intval($reserv['id']); ?>">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                        <path d="M17 21v-8H7v8"></path>
                        <path d="M7 3v5h8"></path>
                    </svg>
                    Save
                </button>
            </section>
        </main>
    </div>
</div>