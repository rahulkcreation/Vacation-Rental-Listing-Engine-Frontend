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

// NOTE: lef_get_user_profile_pic moved to includes/helpers.php


/**
 * Handle location and address suggestions for the search bar.
 */
function lef_handle_search_suggestions() {
	check_ajax_referer('lef_search_nonce', 'nonce');

	global $wpdb;
	$query = isset($_POST['query']) ? sanitize_text_field($_POST['query']) : '';
	$lat   = isset($_POST['lat']) ? sanitize_text_field($_POST['lat']) : '';
	$lng   = isset($_POST['lng']) ? sanitize_text_field($_POST['lng']) : '';

	$results = array();

	// If query is empty but GPS is provided, try to find nearby suggestions
	if (empty($query) && !empty($lat) && !empty($lng)) {
		// Attempt reverse geocoding via Nominatim (OpenStreetMap) to get city name
		$url = sprintf("https://nominatim.openstreetmap.org/reverse?format=json&lat=%s&lon=%s&zoom=10", $lat, $lng);
		
		$args = array(
			'timeout'    => 5,
			'user-agent' => 'ListingEngineFrontend/1.0 (' . home_url() . ')'
		);
		
		$response = wp_remote_get($url, $args);
		
		if (!is_wp_error($response)) {
			$body = wp_remote_retrieve_body($response);
			$data = json_decode($body, true);
			
			if (!empty($data['address'])) {
				$city = '';
				if (!empty($data['address']['city'])) $city = $data['address']['city'];
				elseif (!empty($data['address']['town'])) $city = $data['address']['town'];
				elseif (!empty($data['address']['village'])) $city = $data['address']['village'];
				elseif (!empty($data['address']['county'])) $city = $data['address']['county'];
				
				if ($city) {
					$query = $city; // Use the city name as the query to find nearby locations
				}
			}
		}
		
		// If geocoding failed, we could return "Nearby" properties directly, but for now we'll search by the detected city
	}

	if (empty($query)) {
		wp_send_json_success(array());
	}

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

	wp_send_json_success(array_slice($unique_results, 0, (empty($_POST['query']) ? 4 : 10)));
}
add_action('wp_ajax_lef_search_suggestions', 'lef_handle_search_suggestions');
add_action('wp_ajax_nopriv_lef_search_suggestions', 'lef_handle_search_suggestions');
/**
 * Wishlist Toggle handler.
 * Adds/Removes a property from user's wishlist.
 */
function lef_handle_toggle_wishlist() {
	check_ajax_referer( 'lef_wishlist_nonce', 'nonce' );

	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => 'Please login to add in wishlist.' ) );
	}

	global $wpdb;
	$user_id     = get_current_user_id();
	$property_id = isset( $_POST['property_id'] ) ? intval( $_POST['property_id'] ) : 0;

	if ( ! $property_id ) {
		wp_send_json_error( array( 'message' => 'Invalid property ID.' ) );
	}

	$table_name = $wpdb->prefix . 'ls_wishlist';

	// Check if already in wishlist
	$exists = $wpdb->get_var( $wpdb->prepare(
		"SELECT id FROM $table_name WHERE user_id = %d AND property_id = %d",
		$user_id,
		$property_id
	) );

	if ( $exists ) {
		// Remove
		$wpdb->delete( $table_name, array( 'id' => $exists ) );
		wp_send_json_success( array( 
			'status'  => 'removed',
			'message' => 'Removed from wishlist' 
		) );
	} else {
		// Add
		$wpdb->insert( $table_name, array(
			'user_id'     => $user_id,
			'property_id' => $property_id,
			'created_at'  => current_time( 'mysql' )
		) );
		wp_send_json_success( array( 
			'status'  => 'added',
			'message' => 'Added to wishlist!' 
		) );
	}
}
add_action( 'wp_ajax_lef_toggle_wishlist', 'lef_handle_toggle_wishlist' );

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

	// Generate Unique Reservation Number
	$res_number = lef_generate_reservation_number();

	// ── Insert reservation ──
	$inserted = $wpdb->insert(
		$wpdb->prefix . 'ls_reservation',
		array(
			'user_id'            => $user_id,
			'property_id'        => $property_id,
			'reservation_number' => $res_number,
			'reserve_date'       => $reserve_date_json,
			'total_guests'       => $total_guests_json,
			'total_price'        => $total_price,
			'status'             => 'pending',
			'created_at'         => current_time( 'mysql' ),
		),
		array( '%d', '%d', '%s', '%s', '%s', '%f', '%s', '%s' )
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
	$user_phone  = get_user_meta( $user_id, 'mobile_number', true );

	$host_id    = $property ? intval( $property->host_id ) : 0;
	$host_info  = $host_id ? get_userdata( $host_id ) : null;
	$host_name  = $host_id ? get_user_meta( $host_id, 'full_name', true ) : '';
	if ( empty( $host_name ) && $host_info ) {
		$host_name = $host_info->display_name;
	}
	$host_email = $host_info ? $host_info->user_email : '';
	$host_phone = $host_id ? get_user_meta( $host_id, 'mobile_number', true ) : '';

	// Build property view URL
	$property_view_url = lef_get_secure_detail_url( $property_id );
	$admin_url         = admin_url();
	$property_name     = $property ? $property->title : 'Unknown Property';
	$request_date      = current_time( 'F j, Y' );

	// ── Include email template function ──
	$email_template_path = LEF_PLUGIN_DIR . 'mails/email-reservation.php';
	if ( file_exists( $email_template_path ) ) {
		require_once $email_template_path;
	}

	$email_data = array(
		'reservation_number' => $res_number,
		'property_name'      => $property_name,
		'property_url'       => $property_view_url,
		'request_url'        => $admin_url,

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

/* ==================== RESERVATION: FETCH DATA (BACKEND) ==================== */
/**
 * Fetch reservations for the admin management screen.
 * Handles status filtering, search terms, and pagination.
 */
function lef_reserv_fetch_data() {
	check_ajax_referer( 'lef_reserv_nonce', 'nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Unauthorized access.' ) );
	}

	global $wpdb;
	$status      = isset( $_POST['status'] ) ? sanitize_text_field( $_POST['status'] ) : 'pending';
	$search      = isset( $_POST['search'] ) ? sanitize_text_field( $_POST['search'] ) : '';
	$page        = isset( $_POST['page'] ) ? intval( $_POST['page'] ) : 1;
	$per_page    = 10;
	$offset      = ( $page - 1 ) * $per_page;
	$reserv_table = $wpdb->prefix . 'ls_reservation';
	$prop_table   = $wpdb->prefix . 'ls_property';

	// ── Build Query ──
	$where_clauses = array( $wpdb->prepare( "r.status = %s", $status ) );
	if ( ! empty( $search ) ) {
		$search_wildcard = '%' . $wpdb->esc_like( $search ) . '%';
		$where_clauses[] = $wpdb->prepare( 
			"(r.reservation_number LIKE %s OR p.title LIKE %s)", 
			$search_wildcard, 
			$search_wildcard 
		);
	}
	$where_sql = 'WHERE ' . implode( ' AND ', $where_clauses );

	// ── Get Results ──
	$results = $wpdb->get_results( $wpdb->prepare(
		"SELECT r.*, p.title as property_title 
		 FROM $reserv_table r
		 LEFT JOIN $prop_table p ON r.property_id = p.id
		 $where_sql
		 ORDER BY r.created_at DESC
		 LIMIT %d OFFSET %d",
		$per_page,
		$offset
	) );

	// ── Get Total Matching ──
	$total_matching = $wpdb->get_var( "SELECT COUNT(*) FROM $reserv_table r LEFT JOIN $prop_table p ON r.property_id = p.id $where_sql" );

	// ── Get Counts per Status (for tabs) ──
	$counts = array(
		'pending'   => $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $reserv_table WHERE status = %s", 'pending' ) ),
		'completed' => $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $reserv_table WHERE status = %s", 'completed' ) ),
		'rejected'  => $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM $reserv_table WHERE status = %s", 'rejected' ) ),
	);
	$total_db = array_sum( $counts );

	// ── Format Output ──
	$formatted = array();
	foreach ( $results as $row ) {
		$formatted[] = array(
			'id'                 => $row->id,
			'reservation_number' => $row->reservation_number,
			'property_title'     => $row->property_title ? $row->property_title : 'N/A',
			'status'             => $row->status,
			'created_at'         => date( 'F j, Y, g:i a', strtotime( $row->created_at ) ),
		);
	}

	wp_send_json_success( array(
		'items'          => $formatted,
		'total_matching' => intval( $total_matching ),
		'counts'         => $counts,
		'total_db'       => $total_db,
		'per_page'       => $per_page,
		'current_page'   => $page,
	) );
}
add_action( 'wp_ajax_lef_reserv_fetch_data', 'lef_reserv_fetch_data' );

/* ==================== RESERVATION: GET DETAILS (BACKEND) ==================== */
/**
 * Get full details of a reservation for the view-edit modal.
 */
function lef_reserv_get_details() {
	check_ajax_referer( 'lef_reserv_nonce', 'nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Unauthorized access.' ) );
	}

	$id = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;
	if ( ! $id ) {
		wp_send_json_error( array( 'message' => 'Invalid ID.' ) );
	}

	global $wpdb;
	$reserv_table = $wpdb->prefix . 'ls_reservation';
	$prop_table   = $wpdb->prefix . 'ls_property';

	$row = $wpdb->get_row( $wpdb->prepare(
		"SELECT r.*, p.title as property_title, p.price as prop_price, p.location as prop_location
		 FROM $reserv_table r
		 LEFT JOIN $prop_table p ON r.property_id = p.id
		 WHERE r.id = %d",
		$id
	) );

	if ( ! $row ) {
		wp_send_json_error( array( 'message' => 'Reservation not found.' ) );
	}

	$user_info = get_userdata( $row->user_id );
	$user_name = $user_info ? $user_info->display_name : 'Unknown';
	$user_email = $user_info ? $user_info->user_email : 'N/A';

	$reserve_date = json_decode( $row->reserve_date, true );
	$total_guests = json_decode( $row->total_guests, true );

	$reserv = array(
		'id'                 => $row->id,
		'reservation_number' => $row->reservation_number,
		'property_title'     => $row->property_title ? $row->property_title : 'N/A',
		'prop_location'      => $row->prop_location ? $row->prop_location : 'N/A',
		'user_name'          => $user_name,
		'user_email'         => $user_email,
		'check_in'           => isset( $reserve_date['check_in'] ) ? $reserve_date['check_in'] : 'N/A',
		'check_out'          => isset( $reserve_date['check_out'] ) ? $reserve_date['check_out'] : 'N/A',
		'guests'             => $total_guests,
		'total_price'        => $row->total_price,
		'status'             => $row->status,
		'created_at'         => date( 'F j, Y, g:i a', strtotime( $row->created_at ) ),
	);

	ob_start();
	$template_path = LEF_PLUGIN_DIR . 'backend/template/manage-reservation-models/view-edit.php';
	if ( file_exists( $template_path ) ) {
		include $template_path;
	} else {
		echo '<p>Template not found.</p>';
	}
	$html = ob_get_clean();

	wp_send_json_success( array( 'html' => $html ) );
}
add_action( 'wp_ajax_lef_reserv_get_details', 'lef_reserv_get_details' );

/**
 * Update reservation status.
 * Expects POST: nonce, id, status
 */
function lef_reserv_update_status() {
	check_ajax_referer( 'lef_reserv_nonce', 'nonce' );

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_send_json_error( array( 'message' => 'Unauthorized access.' ) );
	}

	$id     = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;
	$status = isset( $_POST['status'] ) ? sanitize_text_field( $_POST['status'] ) : '';

	if ( ! $id || ! in_array( $status, array( 'pending', 'completed', 'rejected' ) ) ) {
		wp_send_json_error( array( 'message' => 'Invalid data provided.' ) );
	}

	global $wpdb;
	$table = $wpdb->prefix . 'ls_reservation';

	$updated = $wpdb->update(
		$table,
		array(
			'status'     => $status,
			'updated_at' => current_time( 'mysql' ),
		),
		array( 'id' => $id ),
		array( '%s', '%s' ),
		array( '%d' )
	);

	if ( $updated !== false ) {
		wp_send_json_success( array( 'message' => 'Reservation status updated successfully' ) );
	} else {
		wp_send_json_error( array( 'message' => 'Failed to update status.' ) );
	}
}
add_action( 'wp_ajax_lef_reserv_update_status', 'lef_reserv_update_status' );

/* ==================== MY PROFILE: LOAD SCREEN ==================== */
/**
 * AJAX handler to load dashboard sub-screens.
 */
function lef_myprofile_load_screen() {
	check_ajax_referer( 'lef_myprofile_nonce', 'nonce' );

	if ( ! is_user_logged_in() ) {
		wp_send_json_error( array( 'message' => 'Unauthorized' ) );
	}

	$screen = isset( $_POST['screen'] ) ? sanitize_key( $_POST['screen'] ) : 'edit-profile';
	
	$allowed_screens = array( 'edit-profile', 'pay-out', 'my-bookings', 'my-listings' );
	if ( ! in_array( $screen, $allowed_screens ) ) {
		wp_send_json_error( array( 'message' => 'Invalid screen' ) );
	}

	$template_path = LEF_PLUGIN_DIR . 'frontend/template/my-profile/' . $screen . '.php';

	if ( file_exists( $template_path ) ) {
		ob_start();
		include $template_path;
		$html = ob_get_clean();
		wp_send_json_success( array( 'html' => $html ) );
	} else {
		wp_send_json_error( array( 'message' => 'Template not found' ) );
	}
}
add_action( 'wp_ajax_lef_myprofile_load_screen', 'lef_myprofile_load_screen' );

/* ==================== MY PROFILE: LOGOUT URL ==================== */
/**
 * AJAX handler to fetch the logout URL from wp_admin_management.
 */
function lef_myprofile_get_logout_url() {
	check_ajax_referer( 'lef_myprofile_nonce', 'nonce' );

	global $wpdb;
	
	$page_id = $wpdb->get_var( $wpdb->prepare(
		"SELECT page_id FROM wp_admin_management WHERE name = %s",
		'Logout'
	) );

	if ( $page_id ) {
		$logout_url = get_permalink( $page_id );
		wp_send_json_success( array( 'url' => $logout_url ) );
	} else {
		wp_send_json_success( array( 'url' => wp_logout_url( home_url() ) ) );
	}
}
add_action( 'wp_ajax_lef_myprofile_get_logout_url', 'lef_myprofile_get_logout_url' );


