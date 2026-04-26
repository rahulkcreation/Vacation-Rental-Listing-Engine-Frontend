<?php
/**
 * Admin Dashboard Template
 *
 * Displays the main plugin dashboard with quick navigation cards
 * and a comprehensive shortcode reference section.
 *
 * @package ListingEngineFrontend
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$current_user = wp_get_current_user();
$display_name = $current_user->display_name ?: $current_user->user_login;
?>

<div class="wrap">
    <!-- Capture any WP admin notices before our custom layout -->
    <h2 class="lef-admin-notice-placeholder"></h2>

    <div id="lef-dash-root" class="lef-global-plugin-wrapper">

        <!-- ═══════════════════════════════════════════
             HEADER
        ═══════════════════════════════════════════ -->
        <header class="lef-dash-header">
            <div class="lef-dash-header-brand">
                <div class="lef-dash-header-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                        <polyline points="9 22 9 12 15 12 15 22"></polyline>
                    </svg>
                </div>
                <div>
                    <h1 class="lef-dash-title">Listing Engine Frontend</h1>
                    <p class="lef-dash-subtitle">Welcome back, <strong><?php echo esc_html( $display_name ); ?></strong> — your plugin control centre.</p>
                </div>
            </div>
            <div class="lef-dash-header-meta">
                <span class="lef-dash-version-badge">
                    v<?php echo esc_html( defined( 'LEF_VERSION' ) ? LEF_VERSION : '1.0.0' ); ?>
                </span>
            </div>
        </header>

        <!-- ═══════════════════════════════════════════
             QUICK NAVIGATION CARDS
        ═══════════════════════════════════════════ -->
        <section class="lef-dash-section" aria-labelledby="lef-dash-nav-heading">
            <div class="lef-dash-section-head">
                <h2 class="lef-dash-section-title" id="lef-dash-nav-heading">Quick Navigation</h2>
                <p class="lef-dash-section-desc">Jump directly to any plugin module.</p>
            </div>

            <div class="lef-dash-nav-grid">

                <!-- Database -->
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=lef-database' ) ); ?>" class="lef-dash-nav-card" id="lef-dash-card-database">
                    <div class="lef-dash-nav-card-icon lef-dash-nav-card-icon--primary" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <ellipse cx="12" cy="5" rx="9" ry="3"></ellipse>
                            <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"></path>
                            <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path>
                        </svg>
                    </div>
                    <div class="lef-dash-nav-card-body">
                        <h3 class="lef-dash-nav-card-title">Database</h3>
                        <p class="lef-dash-nav-card-desc">Check table status, create or repair plugin database tables.</p>
                    </div>
                    <div class="lef-dash-nav-card-arrow" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m9 18 6-6-6-6"></path>
                        </svg>
                    </div>
                </a>

                <!-- Manage Reservations -->
                <a href="<?php echo esc_url( admin_url( 'admin.php?page=lef-manage-reservations' ) ); ?>" class="lef-dash-nav-card" id="lef-dash-card-reservations">
                    <div class="lef-dash-nav-card-icon lef-dash-nav-card-icon--success" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M8 2v4"></path>
                            <path d="M16 2v4"></path>
                            <rect x="3" y="4" width="18" height="18" rx="2"></rect>
                            <path d="M3 10h18"></path>
                            <path d="M8 14h.01"></path>
                            <path d="M12 14h.01"></path>
                            <path d="M16 14h.01"></path>
                            <path d="M8 18h.01"></path>
                            <path d="M12 18h.01"></path>
                        </svg>
                    </div>
                    <div class="lef-dash-nav-card-body">
                        <h3 class="lef-dash-nav-card-title">Manage Reservations</h3>
                        <p class="lef-dash-nav-card-desc">View, filter, and update guest reservation requests across all properties.</p>
                    </div>
                    <div class="lef-dash-nav-card-arrow" aria-hidden="true">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m9 18 6-6-6-6"></path>
                        </svg>
                    </div>
                </a>

            </div>
        </section>

        <!-- ═══════════════════════════════════════════
             SHORTCODE REFERENCE DOCUMENTATION
        ═══════════════════════════════════════════ -->
        <section class="lef-dash-section" aria-labelledby="lef-dash-docs-heading">
            <div class="lef-dash-section-head">
                <h2 class="lef-dash-section-title" id="lef-dash-docs-heading">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <polyline points="10 9 9 9 8 9"></polyline>
                    </svg>
                    Shortcode Reference
                </h2>
                <p class="lef-dash-section-desc">Copy and paste these shortcodes into any page or post to embed plugin features.</p>
            </div>

            <div class="lef-dash-docs-grid">

                <!-- ── Shortcode: listing_engine_view ── -->
                <article class="lef-dash-doc-card" id="lef-dash-doc-listing-engine-view">
                    <div class="lef-dash-doc-card-header">
                        <div class="lef-dash-doc-icon lef-dash-doc-icon--primary" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                                <line x1="3" y1="9" x2="21" y2="9"></line>
                                <line x1="3" y1="15" x2="21" y2="15"></line>
                                <line x1="9" y1="9" x2="9" y2="21"></line>
                            </svg>
                        </div>
                        <h3 class="lef-dash-doc-title">Full Listing View</h3>
                    </div>
                    <div class="lef-dash-doc-body">
                        <p class="lef-dash-doc-desc">Renders the complete property listing browser with search filters, tabs (grid/list), location filters, and pagination. This is the primary frontend for users to explore all available properties.</p>

                        <div class="lef-dash-doc-code-block">
                            <div class="lef-dash-doc-code-label">Basic Usage</div>
                            <div class="lef-dash-doc-code-wrap">
                                <code class="lef-dash-doc-code" id="lef-dash-code-lev">[listing_engine_view]</code>
                                <button class="lef-dash-copy-btn" data-lef-copy="lef-dash-code-lev" aria-label="Copy shortcode">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                                        <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div class="lef-dash-doc-note">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                            </svg>
                            No parameters required. The listing view auto-fetches all published properties.
                        </div>
                    </div>
                </article>

                <!-- ── Shortcode: selected_list_view ── -->
                <article class="lef-dash-doc-card" id="lef-dash-doc-selected-list-view">
                    <div class="lef-dash-doc-card-header">
                        <div class="lef-dash-doc-icon lef-dash-doc-icon--info" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                            </svg>
                        </div>
                        <h3 class="lef-dash-doc-title">Selected / Curated List View</h3>
                    </div>
                    <div class="lef-dash-doc-body">
                        <p class="lef-dash-doc-desc">Renders a filtered, curated subset of properties. Ideal for landing pages, location-specific pages, or homepage sections. Supports grid and carousel display modes with optional filters.</p>

                        <div class="lef-dash-doc-params">
                            <h4 class="lef-dash-doc-params-title">Available Parameters</h4>
                            <div class="lef-dash-doc-param-table">
                                <div class="lef-dash-doc-param-row lef-dash-doc-param-row--head">
                                    <span>Parameter</span><span>Type</span><span>Default</span><span>Description</span>
                                </div>
                                <div class="lef-dash-doc-param-row">
                                    <span><code>count</code></span>
                                    <span>number</span>
                                    <span>6</span>
                                    <span>Number of properties to display.</span>
                                </div>
                                <div class="lef-dash-doc-param-row">
                                    <span><code>view</code></span>
                                    <span>string</span>
                                    <span>grid</span>
                                    <span><code>grid</code> or <code>carousel</code> layout mode.</span>
                                </div>
                                <div class="lef-dash-doc-param-row">
                                    <span><code>location</code></span>
                                    <span>string</span>
                                    <span>—</span>
                                    <span>Filter by city / location name (e.g. <code>Goa</code>).</span>
                                </div>
                                <div class="lef-dash-doc-param-row">
                                    <span><code>type</code></span>
                                    <span>string</span>
                                    <span>—</span>
                                    <span>Filter by property type (e.g. <code>Villa</code>, <code>Apartment</code>).</span>
                                </div>
                            </div>
                        </div>

                        <div class="lef-dash-doc-examples">
                            <div class="lef-dash-doc-code-block">
                                <div class="lef-dash-doc-code-label">Latest 6 properties — grid</div>
                                <div class="lef-dash-doc-code-wrap">
                                    <code class="lef-dash-doc-code" id="lef-dash-code-slv-1">[selected_list_view count="6"]</code>
                                    <button class="lef-dash-copy-btn" data-lef-copy="lef-dash-code-slv-1" aria-label="Copy shortcode">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                                    </button>
                                </div>
                            </div>
                            <div class="lef-dash-doc-code-block">
                                <div class="lef-dash-doc-code-label">Goa properties — carousel</div>
                                <div class="lef-dash-doc-code-wrap">
                                    <code class="lef-dash-doc-code" id="lef-dash-code-slv-2">[selected_list_view view="carousel" location="Goa"]</code>
                                    <button class="lef-dash-copy-btn" data-lef-copy="lef-dash-code-slv-2" aria-label="Copy shortcode">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                                    </button>
                                </div>
                            </div>
                            <div class="lef-dash-doc-code-block">
                                <div class="lef-dash-doc-code-label">Mumbai Villas — grid</div>
                                <div class="lef-dash-doc-code-wrap">
                                    <code class="lef-dash-doc-code" id="lef-dash-code-slv-3">[selected_list_view view="grid" location="Mumbai" type="Villa"]</code>
                                    <button class="lef-dash-copy-btn" data-lef-copy="lef-dash-code-slv-3" aria-label="Copy shortcode">
                                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </article>

                <!-- ── Shortcode: premium_search_bar ── -->
                <article class="lef-dash-doc-card" id="lef-dash-doc-premium-search-bar">
                    <div class="lef-dash-doc-card-header">
                        <div class="lef-dash-doc-icon lef-dash-doc-icon--warning" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="11" cy="11" r="8"></circle>
                                <path d="m21 21-4.35-4.35"></path>
                            </svg>
                        </div>
                        <h3 class="lef-dash-doc-title">Premium Search Bar</h3>
                    </div>
                    <div class="lef-dash-doc-body">
                        <p class="lef-dash-doc-desc">Embeds a standalone, stylised property search bar with location, date range (check-in / check-out), and guest count fields. Best placed on a homepage hero section or a dedicated search landing page.</p>

                        <div class="lef-dash-doc-code-block">
                            <div class="lef-dash-doc-code-label">Basic Usage</div>
                            <div class="lef-dash-doc-code-wrap">
                                <code class="lef-dash-doc-code" id="lef-dash-code-psb">[premium_search_bar]</code>
                                <button class="lef-dash-copy-btn" data-lef-copy="lef-dash-code-psb" aria-label="Copy shortcode">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                                </button>
                            </div>
                        </div>

                        <div class="lef-dash-doc-note">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                            </svg>
                            On submit, the search bar redirects the user to the Listing View page with the selected filters pre-applied.
                        </div>
                    </div>
                </article>

                <!-- ── Shortcode: single_property_view ── -->
                <article class="lef-dash-doc-card" id="lef-dash-doc-single-property-view">
                    <div class="lef-dash-doc-card-header">
                        <div class="lef-dash-doc-icon lef-dash-doc-icon--primary" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                                <polyline points="9 22 9 12 15 12 15 22"></polyline>
                            </svg>
                        </div>
                        <h3 class="lef-dash-doc-title">Single Property View</h3>
                    </div>
                    <div class="lef-dash-doc-body">
                        <p class="lef-dash-doc-desc">Renders the detailed single property page including image gallery, description, amenities, location map, host info, availability calendar, guest review section, and booking form. Add this shortcode to your dedicated property detail page.</p>

                        <div class="lef-dash-doc-code-block">
                            <div class="lef-dash-doc-code-label">Basic Usage</div>
                            <div class="lef-dash-doc-code-wrap">
                                <code class="lef-dash-doc-code" id="lef-dash-code-spv">[single_property_view]</code>
                                <button class="lef-dash-copy-btn" data-lef-copy="lef-dash-code-spv" aria-label="Copy shortcode">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                                </button>
                            </div>
                        </div>

                        <div class="lef-dash-doc-note">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                            </svg>
                            The property ID is automatically read from the page URL parameter (<code>?property_id=123</code>). No manual ID is required in the shortcode.
                        </div>
                    </div>
                </article>

                <!-- ── Shortcode: lef_my_profile ── -->
                <article class="lef-dash-doc-card" id="lef-dash-doc-my-profile">
                    <div class="lef-dash-doc-card-header">
                        <div class="lef-dash-doc-icon lef-dash-doc-icon--success" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                <circle cx="12" cy="7" r="4"></circle>
                            </svg>
                        </div>
                        <h3 class="lef-dash-doc-title">My Profile Dashboard</h3>
                    </div>
                    <div class="lef-dash-doc-body">
                        <p class="lef-dash-doc-desc">Renders the complete user profile dashboard with sidebar navigation. Includes modules for: <strong>Edit Profile</strong>, <strong>My Listings</strong> (host view with create/edit/delete), <strong>My Bookings</strong> (traveller reservation history), and <strong>Payout</strong> settings. Requires the user to be logged in.</p>

                        <div class="lef-dash-doc-code-block">
                            <div class="lef-dash-doc-code-label">Basic Usage</div>
                            <div class="lef-dash-doc-code-wrap">
                                <code class="lef-dash-doc-code" id="lef-dash-code-profile">[lef_my_profile]</code>
                                <button class="lef-dash-copy-btn" data-lef-copy="lef-dash-code-profile" aria-label="Copy shortcode">
                                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path></svg>
                                </button>
                            </div>
                        </div>

                        <div class="lef-dash-doc-note lef-dash-doc-note--warning">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"></path>
                                <line x1="12" y1="9" x2="12" y2="13"></line>
                                <line x1="12" y1="17" x2="12.01" y2="17"></line>
                            </svg>
                            This shortcode should only be placed on a page that is protected or intended for logged-in users. Guest users will see a login prompt.
                        </div>
                    </div>
                </article>

            </div><!-- /.lef-dash-docs-grid -->
        </section>

    </div><!-- /#lef-dash-root -->
</div><!-- /.wrap -->
