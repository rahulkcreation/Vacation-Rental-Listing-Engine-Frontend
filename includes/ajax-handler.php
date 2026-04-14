<?php
/**
 * AJAX Handler for Listing Engine Frontend.
 *
 * @package ListingEngineFrontend
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ─────────────────────────────────────────────────────────────
// Helper Functions
// ─────────────────────────────────────────────────────────────

/**
 * Robustly fetch user profile picture URL.
 * Handles both JSON-encoded data and plain URL strings.
 * Falls back to a predefined placeholder if no image exists.
 *
 * @param int $user_id User ID to fetch the picture for.
 * @return string Profile picture URL.
 */
function lef_get_user_profile_pic( $user_id ) {
	$plugin_url = rtrim( LEF_PLUGIN_URL, '/' );
	$placeholder = $plugin_url . '/global-assets/images/placeholder-avatar.png';
	
	if ( ! $user_id ) return esc_url( $placeholder );

	$pic_meta = get_user_meta( $user_id, 'profile_pic', true );
	$pic_url  = '';

	if ( ! empty( $pic_meta ) ) {
		// Attempt to decode as JSON if it looks like it
		if ( strpos( $pic_meta, '{' ) === 0 || strpos( $pic_meta, '[' ) === 0 ) {
			$pic_data = json_decode( $pic_meta, true );
			if ( is_array( $pic_data ) ) {
				// It was JSON, check for 'url' or 'path' keys
				$pic_url = isset( $pic_data['url'] ) ? $pic_data['url'] : ( isset( $pic_data['path'] ) ? $pic_data['path'] : '' );
			}
		}

		// If not JSON or pic_url still empty, trust the meta string directly
		if ( empty( $pic_url ) && is_string( $pic_meta ) ) {
			$pic_url = trim( $pic_meta );
		}
	}

	// Final Fallback: if empty, show placeholder. 
	// Otherwise, return the URL and let client-side 'onerror' handle 404s.
	if ( empty( $pic_url ) ) {
		$pic_url = $placeholder;
	}

	return esc_url( $pic_url );
}

/**
 * Handle location and address suggestions for the search bar.
 */
function lef_handle_search_suggestions() {
	check_ajax_referer('lef_search_nonce', 'nonce');

	global $wpdb;
	$query = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';

	if (empty($query)) {
		wp_send_json_success(array());
	}

	$results = array();

	// 1. Search in Locations
	$locations = $wpdb->get_results($wpdb->prepare(
		"SELECT name FROM {$wpdb->prefix}ls_location WHERE name LIKE %s LIMIT 5",
		'%' . $wpdb->esc_like($query) . '%'
	));

	foreach ($locations as $loc) {
		$results[] = array(
			'name' => $loc->name,
			'type' => 'Location',
			'subtitle' => 'Region'
		);
	}

	// 2. Search in Listing Addresses
	$addresses = $wpdb->get_results($wpdb->prepare(
		"SELECT l.address, loc.name as location_name 
		 FROM {$wpdb->prefix}ls_property l
		 LEFT JOIN {$wpdb->prefix}ls_location loc ON l.location = loc.id
		 WHERE l.address LIKE %s AND l.status = 'published' LIMIT 5",
		'%' . $wpdb->esc_like($query) . '%'
	));

	foreach ($addresses as $addr) {
		$display_name = $addr->address;
		if (! empty($addr->location_name)) {
			$display_name .= ', ' . $addr->location_name;
		}
		$results[] = array(
			'name' => $display_name,
			'address' => $addr->address,
			'location' => $addr->location_name,
			'type' => 'Property',
			'subtitle' => 'Street Address'
		);
	}

	// Deduplicate by name
	$unique_results = array();
	$seen_names = array();
	foreach ($results as $res) {
		$lower_name = strtolower($res['name']);
		if (! in_array($lower_name, $seen_names)) {
			$unique_results[] = $res;
			$seen_names[] = $lower_name;
		}
	}

	wp_send_json_success(array_slice($unique_results, 0, 10));
}
add_action('wp_ajax_lef_search_suggestions', 'lef_handle_search_suggestions');
add_action('wp_ajax_nopriv_lef_search_suggestions', 'lef_handle_search_suggestions');


// ─────────────────────────────────────────────────────────────
// Single Property View — AJAX Handlers
// ─────────────────────────────────────────────────────────────

/* ==================== WISHLIST: TOGGLE ==================== */
/**
 * Add or remove a property from the current user's wishlist.
 * Expects POST: nonce, property_id
 */
function lef_toggle_wishlist() {
	check_ajax_referer( 'lef_spv_nonce', 'nonce' );

	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => 'You must be logged in.' ) );
	}

	global $wpdb;
	$user_id     = get_current_user_id();
	$property_id = isset( $_POST['property_id'] ) ? intval( $_POST['property_id'] ) : 0;

	if ( ! $property_id ) {
		wp_send_json_error( array( 'message' => 'Invalid property ID.' ) );
	}

	$table = $wpdb->prefix . 'ls_wishlist';

	// Check if already wishlisted
	$existing = $wpdb->get_var( $wpdb->prepare(
		"SELECT id FROM $table WHERE user_id = %d AND property_id = %d",
		$user_id, $property_id
	) );

	if ( $existing ) {
		// Remove from wishlist
		$wpdb->delete( $table, array( 'id' => $existing ), array( '%d' ) );
		wp_send_json_success( array( 'status' => 'removed' ) );
	} else {
		// Add to wishlist
		$wpdb->insert( $table, array(
			'user_id'     => $user_id,
			'property_id' => $property_id,
			'created_at'  => current_time( 'mysql' ),
		), array( '%d', '%d', '%s' ) );
		wp_send_json_success( array( 'status' => 'added' ) );
	}
}
add_action( 'wp_ajax_lef_toggle_wishlist', 'lef_toggle_wishlist' );


/* ==================== WISHLIST: CHECK STATUS ==================== */
/**
 * Check if a property is in the current user's wishlist.
 * Expects POST: nonce, property_id
 */
function lef_check_wishlist() {
	check_ajax_referer( 'lef_spv_nonce', 'nonce' );

	if ( ! is_user_logged_in() ) {
		wp_send_json_success( array( 'wishlisted' => false ) );
		return;
	}

	global $wpdb;
	$user_id     = get_current_user_id();
	$property_id = isset( $_POST['property_id'] ) ? intval( $_POST['property_id'] ) : 0;

	$exists = $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(*) FROM {$wpdb->prefix}ls_wishlist WHERE user_id = %d AND property_id = %d",
		$user_id, $property_id
	) );

	wp_send_json_success( array( 'wishlisted' => ( $exists > 0 ) ) );
}
add_action( 'wp_ajax_lef_check_wishlist', 'lef_check_wishlist' );
add_action( 'wp_ajax_nopriv_lef_check_wishlist', 'lef_check_wishlist' );


/* ==================== REVIEWS: GET ==================== */
/**
 * Fetch all approved reviews for a property.
 * Expects POST: nonce, property_id
 */
function lef_get_property_reviews() {
	check_ajax_referer( 'lef_spv_nonce', 'nonce' );

	global $wpdb;
	$property_id = isset( $_POST['property_id'] ) ? intval( $_POST['property_id'] ) : 0;

	if ( ! $property_id ) {
		wp_send_json_error( array( 'message' => 'Invalid property ID.' ) );
	}

	$reviews = $wpdb->get_results( $wpdb->prepare(
		"SELECT r.*, u.display_name
		 FROM {$wpdb->prefix}ls_reviews r
		 LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID
		 WHERE r.property_id = %d AND r.status = 'approve'
		 ORDER BY r.created_at DESC",
		$property_id
	) );

	$result = array();
	foreach ( $reviews as $rev ) {
		// Fetch reviewer profile pic using helper
		$pic_url = lef_get_user_profile_pic( $rev->user_id );

		// Fetch reviewer full name
		$full_name = get_user_meta( $rev->user_id, 'full_name', true );
		if ( empty( $full_name ) ) {
			$full_name = $rev->display_name;
		}

		$result[] = array(
			'id'         => $rev->id,
			'user_id'    => $rev->user_id,
			'rating'     => floatval( $rev->rating ),
			'review'     => $rev->review,
			'name'       => $full_name,
			'avatar'     => $pic_url,
			'created_at' => $rev->created_at,
		);
	}

	wp_send_json_success( array( 'reviews' => $result ) );
}
add_action( 'wp_ajax_lef_get_property_reviews', 'lef_get_property_reviews' );
add_action( 'wp_ajax_nopriv_lef_get_property_reviews', 'lef_get_property_reviews' );


/* ==================== REVIEWS: SUBMIT / EDIT ==================== */
/**
 * Submit a new review or edit an existing one.
 * One review per user per property. Editing resets status to 'pending'.
 * Expects POST: nonce, property_id, rating, review
 */
function lef_submit_review() {
	check_ajax_referer( 'lef_spv_nonce', 'nonce' );

	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => 'You must be logged in.' ) );
	}

	global $wpdb;
	$user_id     = get_current_user_id();
	$property_id = isset( $_POST['property_id'] ) ? intval( $_POST['property_id'] ) : 0;
	$rating      = isset( $_POST['rating'] ) ? floatval( $_POST['rating'] ) : 0;
	$review_text = isset( $_POST['review'] ) ? sanitize_textarea_field( $_POST['review'] ) : '';

	if ( ! $property_id || $rating < 1 || $rating > 5 || empty( $review_text ) ) {
		wp_send_json_error( array( 'message' => 'Please provide a valid rating (1-5) and review text.' ) );
	}

	$table = $wpdb->prefix . 'ls_reviews';

	// Check if user already has a review for this property
	$existing_id = $wpdb->get_var( $wpdb->prepare(
		"SELECT id FROM $table WHERE user_id = %d AND property_id = %d",
		$user_id, $property_id
	) );

	if ( $existing_id ) {
		// Update existing review — status resets to pending
		$wpdb->update(
			$table,
			array(
				'rating'     => $rating,
				'review'     => $review_text,
				'status'     => 'pending',
				'updated_at' => current_time( 'mysql' ),
			),
			array( 'id' => $existing_id ),
			array( '%f', '%s', '%s', '%s' ),
			array( '%d' )
		);
		wp_send_json_success( array( 'message' => 'Your review has been updated and is pending approval.' ) );
	} else {
		// Insert new review
		$wpdb->insert(
			$table,
			array(
				'user_id'     => $user_id,
				'property_id' => $property_id,
				'rating'      => $rating,
				'review'      => $review_text,
				'status'      => 'pending',
				'created_at'  => current_time( 'mysql' ),
			),
			array( '%d', '%d', '%f', '%s', '%s', '%s' )
		);
		wp_send_json_success( array( 'message' => 'Your review has been submitted and is pending approval.' ) );
	}
}
add_action( 'wp_ajax_lef_submit_review', 'lef_submit_review' );


/* ==================== REVIEWS: CHECK ELIGIBILITY ==================== */
/**
 * Check if the current user has a completed reservation for this property
 * and whether they already have an existing review.
 * Expects POST: nonce, property_id
 */
function lef_check_review_eligibility() {
	check_ajax_referer( 'lef_spv_nonce', 'nonce' );

	if ( ! is_user_logged_in() ) {
		wp_send_json_success( array( 'eligible' => false, 'has_review' => false ) );
		return;
	}

	global $wpdb;
	$user_id     = get_current_user_id();
	$property_id = isset( $_POST['property_id'] ) ? intval( $_POST['property_id'] ) : 0;

	// Check for a completed reservation
	$has_completed = $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(*) FROM {$wpdb->prefix}ls_reservation
		 WHERE user_id = %d AND property_id = %d AND status = 'completed'",
		$user_id, $property_id
	) );

	// Check for existing review
	$existing_review = $wpdb->get_row( $wpdb->prepare(
		"SELECT id, rating, review FROM {$wpdb->prefix}ls_reviews
		 WHERE user_id = %d AND property_id = %d",
		$user_id, $property_id
	) );

	wp_send_json_success( array(
		'eligible'   => ( $has_completed > 0 ),
		'has_review' => ! empty( $existing_review ),
		'review'     => $existing_review ? array(
			'id'     => $existing_review->id,
			'rating' => floatval( $existing_review->rating ),
			'review' => $existing_review->review,
		) : null,
	) );
}
add_action( 'wp_ajax_lef_check_review_eligibility', 'lef_check_review_eligibility' );


/* ==================== SIMILAR PROPERTIES ==================== */
/**
 * Fetch similar properties based on location, type, price range.
 * Expects POST: nonce, property_id
 */
function lef_get_similar_properties() {
	check_ajax_referer( 'lef_spv_nonce', 'nonce' );

	global $wpdb;
	$property_id = isset( $_POST['property_id'] ) ? intval( $_POST['property_id'] ) : 0;

	if ( ! $property_id ) {
		wp_send_json_error( array( 'message' => 'Invalid property ID.' ) );
	}

	// Get current property details for comparison
	$current = $wpdb->get_row( $wpdb->prepare(
		"SELECT p.location, p.type, p.amenities, TRIM(LOWER(loc.name)) as location_name 
		 FROM {$wpdb->prefix}ls_property p 
		 LEFT JOIN {$wpdb->prefix}ls_location loc ON p.location = loc.id
		 WHERE p.id = %d",
		$property_id
	) );

	if ( ! $current ) {
		wp_send_json_error( array( 'message' => 'Property not found.' ) );
	}

	$loc_name = $current->location_name;

	// ── Build Amenity Clause ──
	$amenity_clause = "";
	$amenities_raw  = $current->amenities;
	if ( ! empty( $amenities_raw ) ) {
		$amenity_ids = json_decode( $amenities_raw, true );
		if ( ! is_array( $amenity_ids ) ) {
			$amenity_ids = array_map( 'intval', array_filter( explode( ',', $amenities_raw ) ) );
		}
		
		if ( ! empty( $amenity_ids ) ) {
			$parts = array();
			foreach ( $amenity_ids as $aid ) {
				$aid = intval( $aid );
				$parts[] = "p.amenities LIKE '%\"$aid\"%'";
				$parts[] = "FIND_IN_SET('$aid', p.amenities)";
			}
			if ( ! empty( $parts ) ) {
				$amenity_clause = "OR (" . implode( ' OR ', $parts ) . ")";
			}
		}
	}

	$similar = $wpdb->get_results( $wpdb->prepare(
		"SELECT p.id, p.title, p.price, p.guests, p.location,
		        loc.name as location_name, t.name as type_name
		 FROM {$wpdb->prefix}ls_property p
		 LEFT JOIN {$wpdb->prefix}ls_location loc ON p.location = loc.id
		 LEFT JOIN {$wpdb->prefix}ls_types t ON p.type = t.id
		 WHERE p.id != %d
		   AND p.status = 'published'
		   AND (
		       (loc.name IS NOT NULL AND TRIM(LOWER(loc.name)) = %s) OR 
		       (p.type IS NOT NULL AND p.type != '' AND p.type = %s)
		       $amenity_clause
		   )
		 LIMIT 8",
		$property_id,
		$loc_name, $current->type
	) );

	$result = array();
	if ( ! empty( $similar ) ) {
		foreach ( $similar as $prop ) {
			// Fetch property image (match property ID with property_id column)
			$img_data_json = $wpdb->get_var( $wpdb->prepare(
				"SELECT image FROM {$wpdb->prefix}ls_img WHERE property_id = %d",
				$prop->id
			) );

			$img_url = '';
			if ( $img_data_json ) {
				$img_data = json_decode( $img_data_json, true );
				if ( is_array( $img_data ) ) {
					// Check if it's a multidimensional array (list of image objects)
					$is_list = isset( $img_data[0] ) && is_array( $img_data[0] );

					if ( $is_list ) {
						// Strictly find sort_order == 0
						foreach ( $img_data as $img_obj ) {
							if ( isset( $img_obj['sort_order'] ) && (int) $img_obj['sort_order'] === 0 ) {
								$img_url = ! empty( $img_obj['url'] ) ? $img_obj['url'] : '';
								break;
							}
						}
						// Fallback: If no sort_order 0, take first available with URL
						if ( empty( $img_url ) ) {
							foreach ( $img_data as $img_obj ) {
								if ( ! empty( $img_obj['url'] ) ) {
									$img_url = $img_obj['url'];
									break;
								}
							}
						}
					} else {
						// Single object format
						$img_url = ! empty( $img_data['url'] ) ? $img_data['url'] : '';
					}
				}
			}

			// Fetch average rating
			$avg_rating = $wpdb->get_var( $wpdb->prepare(
				"SELECT AVG(rating) FROM {$wpdb->prefix}ls_reviews WHERE property_id = %d AND status = 'approve'",
				$prop->id
			) );
			$total_reviews = $wpdb->get_var( $wpdb->prepare(
				"SELECT COUNT(*) FROM {$wpdb->prefix}ls_reviews WHERE property_id = %d AND status = 'approve'",
				$prop->id
			) );

			// Build secure URL
			$detail_url = lef_get_secure_detail_url( $prop->id );

			$result[] = array(
				'id'            => $prop->id,
				'title'         => sprintf( "%s in %s", ($prop->type_name ? $prop->type_name : 'Property'), ($prop->location_name ? $prop->location_name : 'Nearby') ),
				'price'         => floatval( $prop->price ),
				'location_name' => $prop->location_name,
				'image'         => $img_url,
				'avg_rating'    => $avg_rating ? round( floatval( $avg_rating ), 1 ) : 0,
				'total_reviews' => intval( $total_reviews ),
				'url'           => $detail_url,
			);
		}
	}

	wp_send_json_success( array( 'properties' => $result ) );
}
add_action( 'wp_ajax_lef_get_similar_properties', 'lef_get_similar_properties' );
add_action( 'wp_ajax_nopriv_lef_get_similar_properties', 'lef_get_similar_properties' );


/* ==================== RESERVATION: SUBMIT ==================== */
/**
 * Save a new reservation to wp_ls_reservation and send email
 * notifications to both admin and user.
 *
 * Expects POST: nonce, property_id, check_in, check_out,
 *               adults, children, infants, total_price
 */
function lef_submit_reservation() {
	check_ajax_referer( 'lef_spv_nonce', 'nonce' );

	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => 'You must be logged in to reserve.' ) );
	}

	global $wpdb;
	$user_id     = get_current_user_id();
	$property_id = isset( $_POST['property_id'] ) ? intval( $_POST['property_id'] ) : 0;
	$check_in    = isset( $_POST['check_in'] ) ? sanitize_text_field( $_POST['check_in'] ) : '';
	$check_out   = isset( $_POST['check_out'] ) ? sanitize_text_field( $_POST['check_out'] ) : '';
	$adults      = isset( $_POST['adults'] ) ? intval( $_POST['adults'] ) : 1;
	$children    = isset( $_POST['children'] ) ? intval( $_POST['children'] ) : 0;
	$infants     = isset( $_POST['infants'] ) ? intval( $_POST['infants'] ) : 0;
	$total_price = isset( $_POST['total_price'] ) ? floatval( $_POST['total_price'] ) : 0;

	// ── Validation ──
	if ( ! $property_id || empty( $check_in ) || empty( $check_out ) || $total_price <= 0 ) {
		wp_send_json_error( array( 'message' => 'Please fill in all required fields.' ) );
	}

	// ── Build JSON fields ──
	$reserve_date_json = wp_json_encode( array(
		'check_in'  => $check_in,
		'check_out' => $check_out,
	) );
	$total_guests_json = wp_json_encode( array(
		'adults'   => $adults,
		'children' => $children,
		'infants'  => $infants,
	) );

	// ── Insert reservation ──
	$inserted = $wpdb->insert(
		$wpdb->prefix . 'ls_reservation',
		array(
			'user_id'      => $user_id,
			'property_id'  => $property_id,
			'reserve_date' => $reserve_date_json,
			'total_guests' => $total_guests_json,
			'total_price'  => $total_price,
			'status'       => 'pending',
			'created_at'   => current_time( 'mysql' ),
		),
		array( '%d', '%d', '%s', '%s', '%f', '%s', '%s' )
	);

	if ( ! $inserted ) {
		wp_send_json_error( array( 'message' => 'Failed to save reservation. Please try again.' ) );
	}

	// ── Gather data for emails ──
	$property = $wpdb->get_row( $wpdb->prepare(
		"SELECT title, host_id, price FROM {$wpdb->prefix}ls_property WHERE id = %d", $property_id
	) );

	$user_info   = get_userdata( $user_id );
	$user_name   = get_user_meta( $user_id, 'full_name', true );
	if ( empty( $user_name ) ) {
		$user_name = $user_info->display_name;
	}
	$user_email  = $user_info->user_email;
	$user_phone  = get_user_meta( $user_id, 'mobile', true );

	$host_id    = $property ? intval( $property->host_id ) : 0;
	$host_info  = $host_id ? get_userdata( $host_id ) : null;
	$host_name  = $host_id ? get_user_meta( $host_id, 'full_name', true ) : '';
	if ( empty( $host_name ) && $host_info ) {
		$host_name = $host_info->display_name;
	}
	$host_email = $host_info ? $host_info->user_email : '';
	$host_phone = $host_id ? get_user_meta( $host_id, 'mobile', true ) : '';

	// Build property view URL
	$property_view_url = lef_get_secure_detail_url( $property_id );
	$admin_url         = admin_url();
	$property_name     = $property ? $property->title : 'Unknown Property';
	$request_date      = current_time( 'F j, Y' );

	// ── Include email template function ──
	$email_template_path = LEF_PLUGIN_DIR . 'frontend/template/email-reservation.php';
	if ( file_exists( $email_template_path ) ) {
		require_once $email_template_path;
	}

	$email_data = array(
		'property_name' => $property_name,
		'property_url'  => $property_view_url,
		'request_url'   => $admin_url,
		'user_name'     => $user_name,
		'user_email'    => $user_email,
		'user_phone'    => $user_phone ? $user_phone : 'N/A',
		'host_name'     => $host_name ? $host_name : 'N/A',
		'host_email'    => $host_email ? $host_email : 'N/A',
		'host_phone'    => $host_phone ? $host_phone : 'N/A',
		'check_in'      => $check_in,
		'check_out'     => $check_out,
		'adults'        => $adults,
		'children'      => $children,
		'infants'       => $infants,
		'total_price'   => '₹' . number_format( $total_price, 2 ),
		'request_date'  => $request_date,
	);

	// ── Send Admin Email ──
	$admin_email   = get_option( 'admin_email' );
	$admin_subject = 'Reservation Request for ' . $property_name;
	$admin_body    = function_exists( 'lef_get_reservation_email_html' )
		? lef_get_reservation_email_html( $email_data, 'admin' )
		: 'New reservation request for ' . $property_name;

	$headers = array( 'Content-Type: text/html; charset=UTF-8' );
	wp_mail( $admin_email, $admin_subject, $admin_body, $headers );

	// ── Send User Email ──
	$user_subject = 'Reservation Request for ' . $property_name . ' Generated';
	$user_body    = function_exists( 'lef_get_reservation_email_html' )
		? lef_get_reservation_email_html( $email_data, 'user' )
		: 'Your reservation request for ' . $property_name . ' has been generated.';

	wp_mail( $user_email, $user_subject, $user_body, $headers );

	wp_send_json_success( array( 'message' => 'Reservation request sent successfully.' ) );
}
add_action( 'wp_ajax_lef_submit_reservation', 'lef_submit_reservation' );
