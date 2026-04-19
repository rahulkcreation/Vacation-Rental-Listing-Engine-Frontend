<?php

/**
 * My Profile Dashboard Template.
 *
 * This acts as the main container for the user profile dashboard.
 * Navigation is handled via sidebar links that load content into the bucket.
 *
 * @package ListingEngineFrontend
 */

if (! defined('ABSPATH')) {
    exit;
}

$current_user = wp_get_current_user();
$user_id      = $current_user->ID;
$display_name = $current_user->display_name;
$user_email   = $current_user->user_email;

// Get Initials for Avatar
$names    = explode(' ', $display_name);
$initials = '';
foreach ($names as $n) {
    $initials .= strtoupper(substr($n, 0, 1));
}
$initials = substr($initials, 0, 2);

// Get Profile Picture
$profile_pic = lef_get_user_profile_pic($user_id);
?>

<div class="lef-global-plugin-wrapper" id="lef-myprofile-wrapper">
    <main class="lef-prof-page">
        <!-- Dashboard Topbar (Mobile Only) -->
        <div class="lef-prof-topbar">
            <button class="lef-prof-menu-toggle" id="lef-prof-menu-toggle" type="button" aria-label="Open profile menu">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 6h16"></path>
                    <path d="M4 12h16"></path>
                    <path d="M4 18h16"></path>
                </svg>
            </button>
            <span class="lef-prof-topbar-title">Profile Dashboard</span>
        </div>

        <div class="lef-prof-shell">
            <!-- Sidebar Backdrop for Mobile -->
            <div class="lef-prof-sidebar-backdrop" id="lef-prof-sidebar-backdrop"></div>

            <!-- Sidebar Navigation -->
            <aside class="lef-prof-sidebar" id="lef-prof-sidebar">
                <div class="lef-prof-sidebar-head">
                    <div class="lef-prof-sidebar-user">
                        <div class="lef-prof-avatar"><?php echo esc_html($initials); ?></div>
                        <div>
                            <h1 class="lef-prof-sidebar-title"><?php echo esc_html($display_name); ?></h1>
                            <p class="lef-prof-sidebar-text">Manage account details</p>
                        </div>
                    </div>
                    <button class="lef-prof-sidebar-close" id="lef-prof-sidebar-close" type="button" aria-label="Close menu">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M18 6 6 18"></path>
                            <path d="m6 6 12 12"></path>
                        </svg>
                    </button>
                </div>

                <nav class="lef-prof-menu">
                    <button class="lef-prof-menu-btn lef-prof-menu-active" type="button" data-screen="edit-profile">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21a8 8 0 0 0-16 0"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        Edit Profile
                    </button>
                    <button class="lef-prof-menu-btn" type="button" data-screen="pay-out">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="5" width="20" height="14" rx="2"></rect>
                            <path d="M2 10h20"></path>
                        </svg>
                        Payout
                    </button>
                    <button class="lef-prof-menu-btn" type="button" data-screen="my-bookings">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M8 2v4"></path>
                            <path d="M16 2v4"></path>
                            <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                            <path d="M3 10h18"></path>
                        </svg>
                        My Booking
                    </button>
                    <button class="lef-prof-menu-btn" type="button" data-screen="my-listings">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 21h18"></path>
                            <path d="M5 21V7l8-4v18"></path>
                            <path d="M19 21V11l-6-4"></path>
                        </svg>
                        My Listing
                    </button>
                    <button class="lef-prof-menu-btn lef-prof-logout-trigger" type="button">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                            <path d="m16 17 5-5-5-5"></path>
                            <path d="M21 12H9"></path>
                        </svg>
                        Logout
                    </button>
                </nav>
            </aside>

            <!-- Content Panel (Bucket) -->
            <section class="lef-prof-panel" id="lef-myprofile-content-bucket">
                <!-- Screens are loaded here via AJAX -->
                <div class="lef-prof-loader">
                    <div class="lef-spinner"></div>
                </div>
            </section>
        </div>
    </main>
</div>