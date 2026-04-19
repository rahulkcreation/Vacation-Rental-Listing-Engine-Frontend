<?php
/**
 * Asset Loader for Listing Engine Frontend.
 *
 * @package ListingEngineFrontend
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enqueue Global and Conditional Assets.
 */
function lef_enqueue_assets() {
	// 1. Enqueue Global CSS (variables only).
	wp_enqueue_style(
		'lef-global-styles',
		LEF_PLUGIN_URL . 'global-assets/css/global.css',
		array(),
		filemtime( LEF_PLUGIN_DIR . 'global-assets/css/global.css' )
	);

	// 2. Enqueue Global Component Assets (Toaster & Confirmation).
	lef_enqueue_global_components();

	// 3. Conditional Assets for Shortcodes.
	global $post;
	if ( is_a( $post, 'WP_Post' ) ) {
		// Assets for [listing_engine_view]
		if ( has_shortcode( $post->post_content, 'listing_engine_view' ) ) {
			wp_enqueue_style(
				'lef-list-view',
				LEF_PLUGIN_URL . 'frontend/assets/css/list-view.css',
				array( 'lef-global-styles' ),
				filemtime( LEF_PLUGIN_DIR . 'frontend/assets/css/list-view.css' )
			);

			wp_enqueue_script(
				'lef-list-view-js',
				LEF_PLUGIN_URL . 'frontend/assets/js/list-view.js',
				array( 'jquery' ),
				filemtime( LEF_PLUGIN_DIR . 'frontend/assets/js/list-view.js' ),
				true
			);
		}

		// Assets for [selected_list_view]
		if ( has_shortcode( $post->post_content, 'selected_list_view' ) ) {
			wp_enqueue_style(
				'lef-selected-view',
				LEF_PLUGIN_URL . 'frontend/assets/css/selected-list-view.css',
				array( 'lef-global-styles' ),
				filemtime( LEF_PLUGIN_DIR . 'frontend/assets/css/selected-list-view.css' )
			);

			wp_enqueue_script(
				'lef-selected-view-js',
				LEF_PLUGIN_URL . 'frontend/assets/js/selected-list-view.js',
				array( 'jquery' ),
				filemtime( LEF_PLUGIN_DIR . 'frontend/assets/js/selected-list-view.js' ),
				true
			);
		}

		// Assets for [premium_search_bar]
		if ( has_shortcode( $post->post_content, 'premium_search_bar' ) ) {
			wp_enqueue_style(
				'lef-search-bar',
				LEF_PLUGIN_URL . 'frontend/assets/css/search-bar.css',
				array( 'lef-global-styles' ),
				filemtime( LEF_PLUGIN_DIR . 'frontend/assets/css/search-bar.css' )
			);

			wp_enqueue_script(
				'lef-search-bar-js',
				LEF_PLUGIN_URL . 'frontend/assets/js/search-bar.js',
				array( 'jquery' ),
				filemtime( LEF_PLUGIN_DIR . 'frontend/assets/js/search-bar.js' ),
				true
			);

			// Localize search bar data
			global $wpdb;
			$archive_page_id = $wpdb->get_var( $wpdb->prepare(
				"SELECT page_id FROM {$wpdb->prefix}admin_management WHERE name = %s",
				'Listing Archive'
			) );
			$archive_url = $archive_page_id ? get_permalink( $archive_page_id ) : home_url( '/' );

			wp_localize_script( 'lef-search-bar-js', 'lef_ajax_obj', array(
				'ajax_url'    => admin_url( 'admin-ajax.php' ),
				'archiveUrl' => $archive_url,
				'nonce'      => wp_create_nonce( 'lef_search_nonce' )
			) );
		}

		// ─────────────────────────────────────────────────────────────
		// Assets for [single_property_view]
		// ─────────────────────────────────────────────────────────────
		if ( has_shortcode( $post->post_content, 'single_property_view' ) ) {

			/* ── CSS ── */
			wp_enqueue_style(
				'lef-single-property-view',
				LEF_PLUGIN_URL . 'frontend/assets/css/single-property-view.css',
				array( 'lef-global-styles' ),
				filemtime( LEF_PLUGIN_DIR . 'frontend/assets/css/single-property-view.css' )
			);

			/* ── JS ── */
			wp_enqueue_script(
				'lef-single-property-view-js',
				LEF_PLUGIN_URL . 'frontend/assets/js/single-property-view.js',
				array( 'jquery' ),
				filemtime( LEF_PLUGIN_DIR . 'frontend/assets/js/single-property-view.js' ),
				true
			);

			/**
			 * Localize property-specific data so JS can consume it
			 * without additional AJAX calls.
			 */
			$spv_property_id = lef_get_decoded_listing_id();
			$spv_price       = 0;
			$spv_max_guests  = 10;
			$spv_blocked     = array();

			if ( $spv_property_id ) {
				global $wpdb;

				$spv_prop = $wpdb->get_row( $wpdb->prepare(
					"SELECT price, guests FROM {$wpdb->prefix}ls_property WHERE id = %d",
					$spv_property_id
				) );

				if ( $spv_prop ) {
					$spv_price      = floatval( $spv_prop->price );
					$spv_max_guests = intval( $spv_prop->guests );
				}

				// Blocked dates
				$block_rows = $wpdb->get_results( $wpdb->prepare(
					"SELECT dates FROM {$wpdb->prefix}ls_block_date WHERE property_id = %d",
					$spv_property_id
				) );
				foreach ( $block_rows as $br ) {
					$dates_arr = json_decode( $br->dates, true );
					if ( is_array( $dates_arr ) ) {
						$spv_blocked = array_merge( $spv_blocked, $dates_arr );
					}
				}
				$spv_blocked = array_values( array_unique( $spv_blocked ) );
			}

			wp_localize_script( 'lef-single-property-view-js', 'lef_spv_data', array(
				'ajax_url'      => admin_url( 'admin-ajax.php' ),
				'nonce'         => wp_create_nonce( 'lef_spv_nonce' ),
				'property_id'   => $spv_property_id ? $spv_property_id : 0,
				'price'         => $spv_price,
				'max_guests'    => $spv_max_guests,
				'blocked_dates' => $spv_blocked,
				'is_logged_in'  => is_user_logged_in() ? '1' : '0',
				'plugin_url'    => LEF_PLUGIN_URL,
			) );
		}

		// Assets for [lef_my_profile]
		if ( has_shortcode( $post->post_content, 'lef_my_profile' ) ) {
			wp_enqueue_style(
				'lef-my-profile-css',
				LEF_PLUGIN_URL . 'frontend/assets/css/my-profile.css',
				array( 'lef-global-styles' ),
				filemtime( LEF_PLUGIN_DIR . 'frontend/assets/css/my-profile.css' )
			);

			wp_enqueue_script(
				'lef-my-profile-js',
				LEF_PLUGIN_URL . 'frontend/assets/js/my-profile.js',
				array( 'jquery' ),
				filemtime( LEF_PLUGIN_DIR . 'frontend/assets/js/my-profile.js' ),
				true
			);

			wp_enqueue_script(
				'lef-edit-profile-js',
				LEF_PLUGIN_URL . 'frontend/assets/js/my-profile/edit-profile.js',
				array( 'jquery' ),
				LEF_VERSION . '.' . filemtime( LEF_PLUGIN_DIR . 'frontend/assets/js/my-profile/edit-profile.js' ),
				true
			);

			wp_enqueue_style(
				'lef-edit-profile-css',
				LEF_PLUGIN_URL . 'frontend/assets/css/my-profile/edit-profile.css',
				array( 'lef-my-profile-css' ),
				filemtime( LEF_PLUGIN_DIR . 'frontend/assets/css/my-profile/edit-profile.css' )
			);

			// Payout Assets
			wp_enqueue_script(
				'lef-payout-js',
				LEF_PLUGIN_URL . 'frontend/assets/js/my-profile/pay-out.js',
				array( 'jquery' ),
				LEF_VERSION . '.' . filemtime( LEF_PLUGIN_DIR . 'frontend/assets/js/my-profile/pay-out.js' ),
				true
			);

			wp_enqueue_style(
				'lef-payout-css',
				LEF_PLUGIN_URL . 'frontend/assets/css/my-profile/pay-out.css',
				array( 'lef-my-profile-css' ),
				filemtime( LEF_PLUGIN_DIR . 'frontend/assets/css/my-profile/pay-out.css' )
			);

			// My Bookings Assets
			wp_enqueue_script(
				'lef-my-bookings-js',
				LEF_PLUGIN_URL . 'frontend/assets/js/my-profile/my-bookings/my-bookings.js',
				array( 'jquery', 'lef-my-profile-js' ),
				LEF_VERSION . '.' . filemtime( LEF_PLUGIN_DIR . 'frontend/assets/js/my-profile/my-bookings/my-bookings.js' ),
				true
			);

			wp_enqueue_style(
				'lef-my-bookings-css',
				LEF_PLUGIN_URL . 'frontend/assets/css/my-profile/my-bookings/my-bookings.css',
				array( 'lef-my-profile-css' ),
				filemtime( LEF_PLUGIN_DIR . 'frontend/assets/css/my-profile/my-bookings/my-bookings.css' )
			);

			// View Detail Assets
			wp_enqueue_style(
				'lef-my-book-view-css',
				LEF_PLUGIN_URL . 'frontend/assets/css/my-profile/my-bookings/view.css',
				array( 'lef-my-profile-css' ),
				filemtime( LEF_PLUGIN_DIR . 'frontend/assets/css/my-profile/my-bookings/view.css' )
			);

			wp_enqueue_script(
				'lef-my-book-view-js',
				LEF_PLUGIN_URL . 'frontend/assets/js/my-profile/my-bookings/view.js',
				array( 'jquery' ),
				LEF_VERSION . '.' . filemtime( LEF_PLUGIN_DIR . 'frontend/assets/js/my-profile/my-bookings/view.js' ),
				true
			);

			// My Listings Assets
			wp_enqueue_script(
				'lef-my-listings-js',
				LEF_PLUGIN_URL . 'frontend/assets/js/my-profile/my-listings/my-listings.js',
				array( 'jquery', 'lef-my-profile-js' ),
				LEF_VERSION . '.' . filemtime( LEF_PLUGIN_DIR . 'frontend/assets/js/my-profile/my-listings/my-listings.js' ),
				true
			);

			wp_enqueue_style(
				'lef-my-listings-css',
				LEF_PLUGIN_URL . 'frontend/assets/css/my-profile/my-listings/my-listings.css',
				array( 'lef-my-profile-css' ),
				filemtime( LEF_PLUGIN_DIR . 'frontend/assets/css/my-profile/my-listings/my-listings.css' )
			);

			wp_localize_script( 'lef-my-profile-js', 'lefMyProfileData', array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'lef_myprofile_nonce' ),
			) );

			wp_localize_script( 'lef-my-bookings-js', 'lefMyProfileData', array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'lef_myprofile_nonce' ),
			) );

			wp_localize_script( 'lef-edit-profile-js', 'lefMyProfileData', array(
				'ajax_url'  => admin_url( 'admin-ajax.php' ),
				'nonce'     => wp_create_nonce( 'lef_myprofile_nonce' ),
				'countries' => lef_get_country_data(),
			) );
		}

		// Pass localized data to JS (if either script is enqueued)
		if ( wp_script_is( 'lef-list-view-js', 'enqueued' ) || wp_script_is( 'lef-selected-view-js', 'enqueued' ) ) {
			wp_localize_script( 
				wp_script_is( 'lef-list-view-js', 'enqueued' ) ? 'lef-list-view-js' : 'lef-selected-view-js', 
				'lefData', 
				array(
					'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
					'pluginUrl'     => LEF_PLUGIN_URL,
					'isLoggedIn'    => is_user_logged_in() ? '1' : '0',
					'wishlistNonce' => wp_create_nonce( 'lef_wishlist_nonce' )
				)
			);
		}
	}
}

/**
 * Enqueue Admin Assets.
 */
function lef_admin_enqueue_assets( $hook ) {
	// Register and enqueue global styles for backend as well
	wp_enqueue_style(
		'lef-global-styles',
		LEF_PLUGIN_URL . 'global-assets/css/global.css',
		array(),
		filemtime( LEF_PLUGIN_DIR . 'global-assets/css/global.css' )
	);

	// Enqueue global components for admin area
	lef_enqueue_global_components();

	// Enqueue dashboard screen assets
	wp_enqueue_style(
		'lef-dashboard-css',
		LEF_PLUGIN_URL . 'backend/assets/css/dashboard.css',
		array( 'lef-global-styles' ),
		filemtime( LEF_PLUGIN_DIR . 'backend/assets/css/dashboard.css' )
	);

	wp_enqueue_script(
		'lef-dashboard-js',
		LEF_PLUGIN_URL . 'backend/assets/js/dashboard.js',
		array( 'jquery' ),
		filemtime( LEF_PLUGIN_DIR . 'backend/assets/js/dashboard.js' ),
		true
	);

	// Enqueue database management screen assets
	wp_enqueue_style(
		'lef-database-css',
		LEF_PLUGIN_URL . 'backend/assets/css/database.css',
		array( 'lef-global-styles' ),
		filemtime( LEF_PLUGIN_DIR . 'backend/assets/css/database.css' )
	);

	wp_enqueue_script(
		'lef-database-js',
		LEF_PLUGIN_URL . 'backend/assets/js/database.js',
		array( 'jquery' ),
		filemtime( LEF_PLUGIN_DIR . 'backend/assets/js/database.js' ),
		true
	);

	// Enqueue Manage Reservations screen assets
	if ( isset( $_GET['page'] ) && $_GET['page'] === 'lef-manage-reservations' ) {
		wp_enqueue_style(
			'lef-manage-reservations-css',
			LEF_PLUGIN_URL . 'backend/assets/css/manage-reservation-models/manage-reservation.css',
			array( 'lef-global-styles' ),
			filemtime( LEF_PLUGIN_DIR . 'backend/assets/css/manage-reservation-models/manage-reservation.css' )
		);

		wp_enqueue_script(
			'lef-manage-reservations-js',
			LEF_PLUGIN_URL . 'backend/assets/js/manage-reservation-models/manage-reservation.js',
			array( 'jquery' ),
			filemtime( LEF_PLUGIN_DIR . 'backend/assets/js/manage-reservation-models/manage-reservation.js' ),
			true
		);

		wp_enqueue_style(
			'lef-view-edit-css',
			LEF_PLUGIN_URL . 'backend/assets/css/manage-reservation-models/view-edit.css',
			array( 'lef-manage-reservations-css' ),
			filemtime( LEF_PLUGIN_DIR . 'backend/assets/css/manage-reservation-models/view-edit.css' )
		);

		wp_enqueue_script(
			'lef-view-edit-js',
			LEF_PLUGIN_URL . 'backend/assets/js/manage-reservation-models/view-edit.js',
			array( 'lef-manage-reservations-js' ),
			filemtime( LEF_PLUGIN_DIR . 'backend/assets/js/manage-reservation-models/view-edit.js' ),
			true
		);

		wp_localize_script( 'lef-manage-reservations-js', 'lefReservData', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'lef_reserv_nonce' )
		) );
	}

	// Localize admin ajax URL if not exist
	wp_localize_script( 'lef_database-js', 'lef_admin_obj', array(
		'ajax_url' => admin_url( 'admin-ajax.php' )
	) );
}
add_action( 'admin_enqueue_scripts', 'lef_admin_enqueue_assets' );
add_action( 'wp_enqueue_scripts', 'lef_enqueue_assets' );

/**
 * Enqueue Global Components.
 */
function lef_enqueue_global_components() {
	// Toaster.
	wp_enqueue_style(
		'lef-toaster',
		LEF_PLUGIN_URL . 'global-assets/css/toaster.css',
		array(),
		filemtime( LEF_PLUGIN_DIR . 'global-assets/css/toaster.css' )
	);
	wp_enqueue_script(
		'lef-toaster-js',
		LEF_PLUGIN_URL . 'global-assets/js/toaster.js',
		array(),
		filemtime( LEF_PLUGIN_DIR . 'global-assets/js/toaster.js' ),
		true
	);

	// Confirmation.
	wp_enqueue_style(
		'lef-confirmation',
		LEF_PLUGIN_URL . 'global-assets/css/confirmation.css',
		array(),
		filemtime( LEF_PLUGIN_DIR . 'global-assets/css/confirmation.css' )
	);
	wp_enqueue_script(
		'lef-confirmation-js',
		LEF_PLUGIN_URL . 'global-assets/js/confirmation.js',
		array(),
		filemtime( LEF_PLUGIN_DIR . 'global-assets/js/confirmation.js' ),
		true
	);
}

/**
 * Get the full URL for a plugin asset.
 *
 * @param string $relative_path Path relative to the plugin root (e.g., 'global-assets/images/placeholder.png').
 * @return string Full asset URL.
 */
function lef_get_asset_url( $relative_path ) {
	return esc_url( rtrim( LEF_PLUGIN_URL, '/' ) . '/' . ltrim( $relative_path, '/' ) );
}

/**
 * Render Global Components (Toaster & Confirmation) in footer.
 */
function lef_render_global_components() {
	include LEF_PLUGIN_DIR . 'global-assets/template/toaster.php';
	include LEF_PLUGIN_DIR . 'global-assets/template/confirmation.php';
}
add_action( 'wp_footer', 'lef_render_global_components' );
add_action( 'admin_footer', 'lef_render_global_components' );
