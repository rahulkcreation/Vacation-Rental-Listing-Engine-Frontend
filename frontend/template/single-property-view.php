<?php
/**
 * Template: Single Property View
 *
 * Renders the complete single-property detail page — desktop and mobile.
 * All data is fetched server-side; interactive behaviours are handled
 * by single-property-view.js via AJAX.
 *
 * @package ListingEngineFrontend
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// ─────────────────────────────────────────────────────────────
// Data Fetching
// ─────────────────────────────────────────────────────────────

global $wpdb;
$property_id = get_query_var( 'lef_property_id', 0 );

/* ── 1. Property Row ── */
$property = $wpdb->get_row( $wpdb->prepare(
	"SELECT * FROM {$wpdb->prefix}ls_property WHERE id = %d", $property_id
) );

if ( ! $property ) {
	echo '<script>window.location.href = "' . esc_url( home_url( '/' ) ) . '";</script>';
	return;
}

/* ── 2. Images (single row, JSON array of all images) ── */
$image_data = $wpdb->get_var( $wpdb->prepare(
	"SELECT image FROM {$wpdb->prefix}ls_img WHERE property_id = %d",
	$property_id
) );

$images = array();
if ( $image_data ) {
	$decoded_images = json_decode( $image_data, true );
	if ( is_array( $decoded_images ) ) {
		// Sort by sort_order (same logic as list-view.php)
		usort( $decoded_images, function ( $a, $b ) {
			return ( isset( $a['sort_order'] ) ? $a['sort_order'] : 0 ) - ( isset( $b['sort_order'] ) ? $b['sort_order'] : 0 );
		} );
		$images = array_column( $decoded_images, 'url' );
		// Remove any empty/null values
		$images = array_values( array_filter( $images ) );
	}
}

/* ── 3. Location Name ── */
$location_name = '';
if ( ! empty( $property->location ) ) {
	$location_name = $wpdb->get_var( $wpdb->prepare(
		"SELECT name FROM {$wpdb->prefix}ls_location WHERE id = %d", $property->location
	) );
}

/* ── 4. Host Info ── */
$host_id   = intval( $property->host_id );
$host_name = '';
$host_pic  = '';
if ( $host_id ) {
	$host_user = get_userdata( $host_id );
	$host_name_meta = get_user_meta( $host_id, 'full_name', true );
	$host_name      = ! empty( $host_name_meta ) ? $host_name_meta : ( $host_user ? $host_user->display_name : '' );

	$host_pic_meta  = get_user_meta( $host_id, 'profile_pic', true );
	if ( $host_pic_meta ) {
		$pic_data = json_decode( $host_pic_meta, true );
		// Check both 'url' and 'path' keys (varies by plugin version)
		$host_pic = isset( $pic_data['url'] ) ? $pic_data['url'] : ( isset( $pic_data['path'] ) ? $pic_data['path'] : '' );
	}
}

/* ── 5. Reviews (approved only) ── */
$reviews_data = $wpdb->get_results( $wpdb->prepare(
	"SELECT r.*, u.display_name
	 FROM {$wpdb->prefix}ls_reviews r
	 LEFT JOIN {$wpdb->users} u ON r.user_id = u.ID
	 WHERE r.property_id = %d AND r.status = 'approve'
	 ORDER BY r.created_at DESC",
	$property_id
) );

$reviews       = array();
$total_rating  = 0;
$review_count  = count( $reviews_data );

foreach ( $reviews_data as $rev ) {
	$rev_name = get_user_meta( $rev->user_id, 'full_name', true );
	if ( empty( $rev_name ) ) {
		$rev_name = $rev->display_name;
	}
	$rev_pic_meta = get_user_meta( $rev->user_id, 'profile_pic', true );
	$rev_pic      = '';
	if ( $rev_pic_meta ) {
		$rev_pic_data = json_decode( $rev_pic_meta, true );
		$rev_pic      = isset( $rev_pic_data['url'] ) ? $rev_pic_data['url'] : '';
	}

	$total_rating += floatval( $rev->rating );
	$reviews[]     = array(
		'name'       => $rev_name,
		'avatar'     => $rev_pic,
		'rating'     => floatval( $rev->rating ),
		'review'     => $rev->review,
		'created_at' => $rev->created_at,
	);
}

$avg_rating = $review_count > 0 ? round( $total_rating / $review_count, 1 ) : 0;

/* ── 6. Amenities ── */
$amenities_list = array();
$amenities_raw = $property->amenities;
if ( ! empty( $amenities_raw ) ) {
	// Try JSON first, fall back to comma-separated (FIND_IN_SET format)
	$amenity_ids = json_decode( $amenities_raw, true );
	if ( ! is_array( $amenity_ids ) ) {
		$amenity_ids = array_map( 'intval', array_filter( explode( ',', $amenities_raw ) ) );
	}
	$amenity_ids = array_map( 'intval', $amenity_ids );
	$amenity_ids = array_filter( $amenity_ids );

	if ( count( $amenity_ids ) > 0 ) {
		$placeholders = implode( ',', array_fill( 0, count( $amenity_ids ), '%d' ) );
		$amenities_rows = $wpdb->get_results( $wpdb->prepare(
			"SELECT id, name, svg_path FROM {$wpdb->prefix}ls_amenities WHERE id IN ($placeholders)",
			...$amenity_ids
		) );
		foreach ( $amenities_rows as $am ) {
			$svg_data = json_decode( $am->svg_path, true );
			// Support both 'url' and 'path' keys in the JSON
			$svg_path = '';
			if ( is_array( $svg_data ) ) {
				$svg_path = isset( $svg_data['url'] ) ? $svg_data['url'] : ( isset( $svg_data['path'] ) ? $svg_data['path'] : '' );
			} elseif ( is_string( $am->svg_path ) && ! empty( $am->svg_path ) ) {
				// If it's a plain URL string (not JSON)
				$svg_path = $am->svg_path;
			}
			$amenities_list[] = array(
				'name'     => $am->name,
				'svg_path' => $svg_path,
			);
		}
	}
}

/* ── 7. Blocked Dates ── */
$blocked_dates = array();
$block_rows = $wpdb->get_results( $wpdb->prepare(
	"SELECT dates FROM {$wpdb->prefix}ls_block_date WHERE property_id = %d", $property_id
) );
foreach ( $block_rows as $br ) {
	$dates_arr = json_decode( $br->dates, true );
	if ( is_array( $dates_arr ) ) {
		$blocked_dates = array_merge( $blocked_dates, $dates_arr );
	}
}
$blocked_dates = array_unique( $blocked_dates );

/* ── 8. Wishlist Status ── */
$is_wishlisted = false;
if ( is_user_logged_in() ) {
	$wl_count = $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(*) FROM {$wpdb->prefix}ls_wishlist WHERE user_id = %d AND property_id = %d",
		get_current_user_id(), $property_id
	) );
	$is_wishlisted = ( $wl_count > 0 );
}

/* ── 9. Review Eligibility ── */
$can_review    = false;
$has_review    = false;
$existing_review = null;
if ( is_user_logged_in() ) {
	$completed_res = $wpdb->get_var( $wpdb->prepare(
		"SELECT COUNT(*) FROM {$wpdb->prefix}ls_reservation
		 WHERE user_id = %d AND property_id = %d AND status = 'completed'",
		get_current_user_id(), $property_id
	) );
	$can_review = ( $completed_res > 0 );

	$existing_review = $wpdb->get_row( $wpdb->prepare(
		"SELECT id, rating, review FROM {$wpdb->prefix}ls_reviews
		 WHERE user_id = %d AND property_id = %d",
		get_current_user_id(), $property_id
	) );
	$has_review = ! empty( $existing_review );
}

/* ── 10. Property Details ── */
$title       = esc_html( $property->title );
$description = $property->description;
$price       = floatval( $property->price );
$guests      = intval( $property->guests );
$bedrooms    = intval( $property->bedroom );
$beds        = intval( $property->bed );
$bathrooms   = intval( $property->bathroom );

// ─────────────────────────────────────────────────────────────
// SVG Icons (reused across desktop & mobile)
// ─────────────────────────────────────────────────────────────
$star_svg_sm = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" aria-hidden="true" role="presentation" focusable="false" style="display: block; height: 8px; width: 8px; fill: currentcolor;"><path fill-rule="evenodd" d="m15.1 1.58-4.13 8.88-9.86 1.27a1 1 0 0 0-.54 1.74l7.3 6.57-1.97 9.85a1 1 0 0 0 1.48 1.06l8.62-5 8.63 5a1 1 0 0 0 1.48-1.06l-1.97-9.85 7.3-6.57a1 1 0 0 0-.55-1.73l-9.86-1.28-4.12-8.88a1 1 0 0 0-1.82 0z"></path></svg>';

$star_svg_review = '<svg viewBox="0 0 32 32"><path d="m15.1 1.58-4.13 8.88-9.86 1.27a1 1 0 0 0-.54 1.74l7.3 6.57-1.97 9.85a1 1 0 0 0 1.48 1.06l8.62-5 8.63 5a1 1 0 0 0 1.48-1.06l-1.97-9.85 7.3-6.57a1 1 0 0 0-.55-1.73l-9.86-1.28-4.12-8.88a1 1 0 0 0-1.82 0z" /></svg>';

$share_svg = '<svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" role="presentation" focusable="false" style="display: block; fill: none; height: 16px; width: 16px; stroke: currentcolor; stroke-width: 2; overflow: visible;"><path d="m27 18v9c0 1.1046-.8954 2-2 2h-18c-1.10457 0-2-.8954-2-2v-9m11-15v21m-10-11 9.2929-9.29289c.3905-.39053 1.0237-.39053 1.4142 0l9.2929 9.29289" fill="none"></path></svg>';

$heart_svg_empty = '<svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" role="presentation" focusable="false" style="display: block; fill: none; height: 16px; width: 16px; stroke: currentcolor; stroke-width: 2; overflow: visible;"><path d="m15.9998 28.6668c7.1667-4.8847 14.3334-10.8844 14.3334-18.1088 0-1.84951-.6993-3.69794-2.0988-5.10877-1.3996-1.4098-3.2332-2.11573-5.0679-2.11573-1.8336 0-3.6683.70593-5.0668 2.11573l-2.0999 2.11677-2.0988-2.11677c-1.3995-1.4098-3.2332-2.11573-5.06783-2.11573-1.83364 0-3.66831.70593-5.06683 2.11573-1.39955 1.41083-2.09984 3.25926-2.09984 5.10877 0 7.2244 7.16667 13.2241 14.3333 18.1088z"></path></svg>';

$heart_svg_filled = '<svg viewBox="0 0 32 32" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" role="presentation" focusable="false" style="display: block; fill: var(--leb-primary-color); height: 16px; width: 16px; stroke: var(--leb-primary-color); stroke-width: 2; overflow: visible;"><path d="m15.9998 28.6668c7.1667-4.8847 14.3334-10.8844 14.3334-18.1088 0-1.84951-.6993-3.69794-2.0988-5.10877-1.3996-1.4098-3.2332-2.11573-5.0679-2.11573-1.8336 0-3.6683.70593-5.0668 2.11573l-2.0999 2.11677-2.0988-2.11677c-1.3995-1.4098-3.2332-2.11573-5.06783-2.11573-1.83364 0-3.66831.70593-5.06683 2.11573-1.39955 1.41083-2.09984 3.25926-2.09984 5.10877 0 7.2244 7.16667 13.2241 14.3333 18.1088z"></path></svg>';

$back_arrow_svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 32 32" aria-label="Back" role="img" focusable="false" style="display: block; fill: none; height: 16px; width: 16px; stroke: currentcolor; stroke-width: 4; overflow: visible;"><g fill="none"><path d="M4 16h26M15 28 3.7 16.7a1 1 0 0 1 0-1.4L15 4"></path></g></svg>';

$grid_icon_svg = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" aria-hidden="true" role="presentation" focusable="false" style="display: block; height: 16px; width: 16px; fill: currentcolor;"><path fill-rule="evenodd" d="M3 11.5a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3zm5 0a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3zm5 0a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3zm-10-5a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3zm5 0a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3zm5 0a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3zm-10-5a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3zm5 0a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3zm5 0a1.5 1.5 0 1 1 0 3 1.5 1.5 0 0 1 0-3z"></path></svg>';

// Helper: Truncate description
$desc_truncated = mb_strlen( $description ) > 200 ? mb_substr( $description, 0, 200 ) . '...' : $description;
$show_desc_more = mb_strlen( $description ) > 200;

// Helper: Amenities display (max 6 on page, rest in popup)
$amenities_visible = array_slice( $amenities_list, 0, 6 );
$amenities_extra   = count( $amenities_list ) > 6;

// Helper: Reviews display (max 6 on page)
$reviews_visible = array_slice( $reviews, 0, 6 );
$reviews_extra   = $review_count > 6;

// Helper: Format month for review dates
function lef_format_review_date( $date_str ) {
	$ts = strtotime( $date_str );
	return $ts ? date( 'F Y', $ts ) : '';
}

// ─────────────────────────────────────────────────────────────
// RENDER — Hidden data attributes for JS consumption
// ─────────────────────────────────────────────────────────────
?>

<div id="lef-spv-root"
     data-property-id="<?php echo esc_attr( $property_id ); ?>"
     data-price="<?php echo esc_attr( $price ); ?>"
     data-max-guests="<?php echo esc_attr( $guests ); ?>"
     data-is-wishlisted="<?php echo $is_wishlisted ? '1' : '0'; ?>"
     data-is-logged-in="<?php echo is_user_logged_in() ? '1' : '0'; ?>">

<!-- ==============================
     DESKTOP VIEW
     ============================== -->
<div class="lef-dk-main-cont" id="lef-spv-desktop">

    <!-- ── Title Bar ── -->
    <div class="lefdk-title-cont">
        <h1 class="lefdk-title"><?php echo $title; ?></h1>
        <div class="lefdk-extra-cont">
            <div class="lefdk-h-btn" id="lef-spv-share-btn"><?php echo $share_svg; ?>Share</div>
            <div class="lefdk-h-btn" id="lef-spv-wishlist-btn">
                <span class="lef-spv-heart-icon"><?php echo $is_wishlisted ? $heart_svg_filled : $heart_svg_empty; ?></span>Save
            </div>
        </div>
    </div>

    <!-- ── Image Grid ── -->
    <div class="lefdk-img-cont">
        <?php if ( isset( $images[0] ) ) : ?>
        <div class="lefdk-img-1">
            <img src="<?php echo esc_url( $images[0] ); ?>" alt="<?php echo $title; ?>">
        </div>
        <?php endif; ?>
        <div class="lefdk-img-2">
            <?php for ( $i = 1; $i <= 4; $i++ ) : ?>
                <?php if ( isset( $images[ $i ] ) ) : ?>
                <img src="<?php echo esc_url( $images[ $i ] ); ?>" alt="<?php echo $title; ?> photo <?php echo $i + 1; ?>">
                <?php endif; ?>
            <?php endfor; ?>
        </div>

        <?php if ( count( $images ) > 0 ) : ?>
        <div class="lefdk-all-photo-btn" id="lef-spv-show-photos">
            <?php echo $grid_icon_svg; ?>
            Show all photos
        </div>
        <?php endif; ?>
    </div>

    <!-- ── Details Container (Left: content, Right: form) ── -->
    <div class="lefdk-details-cont">
        <div class="lefdk-d-right">
            <!-- Title & specs -->
            <div class="lefdk-d-title-cont">
                <h1 class="lefdk-d-title">Entire rental unit in <?php echo esc_html( $location_name ); ?></h1>
                <ol class="lefdk-list-order">
                    <li class="lefdk-list"><?php echo $guests; ?> guests</li>
                    <li class="lefdk-list"><span class="lefdk-dot"> · </span><?php echo $bedrooms; ?> bedroom</li>
                    <li class="lefdk-list"><span class="lefdk-dot"> · </span><?php echo $beds; ?> bed</li>
                    <li class="lefdk-list"><span class="lefdk-dot"> · </span><?php echo $bathrooms; ?> bathroom</li>
                </ol>
                <div class="lefdk-review">
                    <?php if ( $review_count > 0 ) : ?>
                    <div class="lefdk-r-number"><?php echo $star_svg_sm; ?><?php echo $avg_rating; ?></div>
                    <div class="lefdk-r-total-revi"><span class="lefdk-dot"> · </span><?php echo $review_count; ?> reviews</div>
                    <?php else : ?>
                    <div class="lefdk-r-number">No reviews</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Host -->
            <div class="lefdk-host-details">
                <div class="lefdk-hd-photo">
                    <?php if ( $host_pic ) : ?>
                    <img src="<?php echo esc_url( $host_pic ); ?>" alt="<?php echo esc_attr( $host_name ); ?>">
                    <?php else : ?>
                    <img src="<?php echo esc_url( LEF_PLUGIN_URL . 'global-assets/images/placeholder-avatar.png' ); ?>" alt="Host">
                    <?php endif; ?>
                </div>
                <div class="lefdk-hd-info">
                    <span class="lefdk-hd-i-name">Hosted by <?php echo esc_html( $host_name ); ?></span>
                    <span class="lefdk-hd-i-tag">Verified Host</span>
                </div>
            </div>

            <!-- Description -->
            <div class="lefdk-description">
                <p><?php echo nl2br( esc_html( $desc_truncated ) ); ?></p>
                <?php if ( $show_desc_more ) : ?>
                <div class="lefdk-desc-btn" id="lef-spv-desc-more">Show more</div>
                <?php endif; ?>
            </div>

            <!-- Amenities -->
            <div class="lefdk-amenity-cont">
                <h1 class="lefdk-am-heading">What this place offers</h1>
                <ol class="lefdk-am-lists">
                    <?php foreach ( $amenities_visible as $am ) : ?>
                    <list class="lefdk-am-li-list">
                        <span class="lefdk-am-li-svg">
                            <?php if ( $am['svg_path'] ) : ?>
                            <img src="<?php echo esc_url( $am['svg_path'] ); ?>" alt="" style="display:block; height:24px; width:24px;">
                            <?php endif; ?>
                        </span>
                        <span class="lefdk-am-li-title"><?php echo esc_html( $am['name'] ); ?></span>
                    </list>
                    <?php endforeach; ?>
                </ol>
                <?php if ( $amenities_extra ) : ?>
                <div class="lefdk-show-amenity-btn" id="lef-spv-amenity-more">Show all <?php echo count( $amenities_list ); ?> amenities</div>
                <?php endif; ?>
            </div>

            <!-- Calendar -->
            <div class="lefdk-calender">
                <h1 class="lefdk-cal-heading">Select check-in date</h1>
                <div class="lefdk-cal-wrapper">
                    <div class="lefdk-cal-month-container">
                        <div class="lefdk-cal-header">
                            <button class="lefdk-cal-nav-btn" id="lef-spv-prevMonth">
                                <svg viewBox="0 0 24 24"><path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z" /></svg>
                            </button>
                            <span class="lefdk-cal-month" id="lef-spv-currentMonth"></span>
                            <button class="lefdk-cal-nav-btn" id="lef-spv-nextMonth">
                                <svg viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z" /></svg>
                            </button>
                        </div>
                        <div class="lefdk-cal-grid" id="lef-spv-calendarGrid"></div>
                    </div>
                    <span class="lefdk-cal-clear-btn" id="lef-spv-clearDates">clear dates</span>
                </div>
            </div>

        </div>

        <!-- ── RIGHT: Reservation Form ── -->
        <div class="lefdk-d-left">
            <div class="lefdk-l-form-cont" id="lef-spv-reserve-form">
                <div class="lefdk-lf-price-header">
                    <span class="lefdk-lf-price-amount" id="lef-spv-price-display">Add dates for prices</span>
                </div>
                <div class="lefdk-lf-dates">
                    <div class="lefdk-lf-date-row">
                        <div class="lefdk-lf-date-field" id="lef-spv-checkin-field">
                            <label>Check-in</label>
                            <span id="lef-spv-checkin-label">Add date</span>
                        </div>
                        <div class="lefdk-lf-date-field" id="lef-spv-checkout-field">
                            <label>Check-out</label>
                            <span id="lef-spv-checkout-label">Add date</span>
                        </div>
                    </div>
                </div>
                <div class="lefdk-lf-guests" id="lef-spv-guests-trigger">
                    <label>Guests</label>
                    <span id="lef-spv-guests-label">1 guest</span>
                </div>

                <!-- Guest Dropdown -->
                <div class="lef-spv-guests-dropdown" id="lef-spv-guests-dropdown" style="display:none;">
                    <div class="lef-spv-gd-row">
                        <div class="lef-spv-gd-info"><span class="lef-spv-gd-title">Adults</span><span class="lef-spv-gd-sub">Age 13+</span></div>
                        <div class="lef-spv-gd-controls">
                            <button class="lef-spv-gd-btn" data-action="minus" data-type="adults">−</button>
                            <span class="lef-spv-gd-count" id="lef-spv-adults-count">1</span>
                            <button class="lef-spv-gd-btn" data-action="plus" data-type="adults">+</button>
                        </div>
                    </div>
                    <div class="lef-spv-gd-row">
                        <div class="lef-spv-gd-info"><span class="lef-spv-gd-title">Children</span><span class="lef-spv-gd-sub">Age 2–12</span></div>
                        <div class="lef-spv-gd-controls">
                            <button class="lef-spv-gd-btn" data-action="minus" data-type="children">−</button>
                            <span class="lef-spv-gd-count" id="lef-spv-children-count">0</span>
                            <button class="lef-spv-gd-btn" data-action="plus" data-type="children">+</button>
                        </div>
                    </div>
                    <div class="lef-spv-gd-row">
                        <div class="lef-spv-gd-info"><span class="lef-spv-gd-title">Infants</span><span class="lef-spv-gd-sub">Under 2</span></div>
                        <div class="lef-spv-gd-controls">
                            <button class="lef-spv-gd-btn" data-action="minus" data-type="infants">−</button>
                            <span class="lef-spv-gd-count" id="lef-spv-infants-count">0</span>
                            <button class="lef-spv-gd-btn" data-action="plus" data-type="infants">+</button>
                        </div>
                    </div>
                </div>

                <button class="lefdk-lf-btn" id="lef-spv-reserve-btn">Reserve</button>
                <p class="lefdk-lf-info">You won't be charged yet</p>
            </div>
        </div>
    </div>

    <!-- ── Reviews Section ── -->
    <div class="lefdk-all-review">
        <div class="lefdk-ar-review">
            <?php if ( $review_count > 0 ) : ?>
            <div class="lefdk-ar-r-number"><?php echo $star_svg_sm; ?><?php echo $avg_rating; ?></div>
            <div class="lefdk-ar-r-total-revi"><span class="lefdk-dot"> · </span><?php echo $review_count; ?> reviews</div>
            <?php else : ?>
            <div class="lefdk-ar-r-number">No Reviews</div>
            <?php endif; ?>
        </div>

        <?php if ( $review_count > 0 ) : ?>
        <div class="lefdk-review-cards">
            <?php foreach ( $reviews_visible as $rev ) : ?>
            <div class="lefdk-rc-card">
                <div class="lefdk-rc-header">
                    <img src="<?php echo esc_url( $rev['avatar'] ? $rev['avatar'] : LEF_PLUGIN_URL . 'global-assets/images/placeholder-avatar.png' ); ?>" alt="Avatar" class="lefdk-rc-avatar">
                    <div class="lefdk-rc-info">
                        <span class="lefdk-rc-name"><?php echo esc_html( $rev['name'] ); ?></span>
                        <div class="lefdk-rc-date"><?php echo lef_format_review_date( $rev['created_at'] ); ?> <span class="lefdk-rc-rating"><?php echo $star_svg_review; ?><?php echo $rev['rating']; ?></span></div>
                    </div>
                </div>
                <p class="lefdk-rc-text"><?php echo esc_html( $rev['review'] ); ?></p>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ( $reviews_extra ) : ?>
        <div class="lefdk-show-amenity-btn" id="lef-spv-reviews-more">Show all <?php echo $review_count; ?> reviews</div>
        <?php endif; ?>

        <?php if ( $can_review && is_user_logged_in() ) : ?>
        <div class="lefdk-show-amenity-btn" id="lef-spv-write-review-btn">
            ✍️ <?php echo $has_review ? 'Edit your review' : 'Write a review'; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- ── Similar Properties ── -->
    <div class="lefdk-similar-results">
        <h1 class="lefdk-sm-heading">More stays nearby</h1>
        <div class="lefdk-sm-r-cards" id="lef-spv-similar-dk"></div>
    </div>

</div>


<!-- ==============================
     MOBILE VIEW
     ============================== -->
<div class="lef-mb-main-cont" id="lef-spv-mobile">

    <!-- ── Header ── -->
    <div class="lefmb-header-cont">
        <span class="lefmb-bck-btn" id="lef-spv-back-btn"><?php echo $back_arrow_svg; ?></span>
        <div class="lefmb-extra-btn-cont">
            <span class="lefmb-e-btn" id="lef-spv-share-btn-mb"><?php echo $share_svg; ?></span>
            <span class="lefmb-e-btn" id="lef-spv-wishlist-btn-mb">
                <span class="lef-spv-heart-icon"><?php echo $is_wishlisted ? $heart_svg_filled : $heart_svg_empty; ?></span>
            </span>
        </div>
    </div>

    <!-- ── Image Slider ── -->
    <div class="lefmb-img-cont" id="lef-spv-mb-slider">
        <?php foreach ( $images as $img_url ) : ?>
        <img src="<?php echo esc_url( $img_url ); ?>" alt="<?php echo $title; ?>">
        <?php endforeach; ?>
        <span class="lefmb-img-count" id="lef-spv-img-counter">1/<?php echo count( $images ); ?></span>
    </div>

    <!-- ── Property Details ── -->
    <div class="lefmb-prop-details-cont">

        <div class="lefmb-prop-heading-cont">
            <h1 class="lefmb-prop-d-heading"><?php echo $title; ?></h1>
            <div class="lefmb-prop-title-cont">
                <h1 class="lefmb-prop-title">Entire rental unit in <?php echo esc_html( $location_name ); ?></h1>
                <ol class="lefmb-list-order">
                    <li class="lefmb-list"><?php echo $guests; ?> guests</li>
                    <li class="lefmb-list"><span class="lefmb-dot"> · </span><?php echo $bedrooms; ?> bedroom</li>
                    <li class="lefmb-list"><span class="lefmb-dot"> · </span><?php echo $beds; ?> bed</li>
                    <li class="lefmb-list"><span class="lefmb-dot"> · </span><?php echo $bathrooms; ?> bathroom</li>
                </ol>
                <div class="lefmb-review">
                    <?php if ( $review_count > 0 ) : ?>
                    <div class="lefmb-r-number"><?php echo $star_svg_sm; ?><?php echo $avg_rating; ?></div>
                    <div class="lefmb-r-total-revi"><span class="lefmb-dot"> · </span><?php echo $review_count; ?> reviews</div>
                    <?php else : ?>
                    <div class="lefmb-r-number">No reviews</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Host -->
        <div class="lefmb-host-details">
            <div class="lefmb-hd-photo">
                <?php if ( $host_pic ) : ?>
                <img src="<?php echo esc_url( $host_pic ); ?>" alt="<?php echo esc_attr( $host_name ); ?>">
                <?php else : ?>
                <img src="<?php echo esc_url( LEF_PLUGIN_URL . 'global-assets/images/placeholder-avatar.png' ); ?>" alt="Host">
                <?php endif; ?>
            </div>
            <div class="lefmb-hd-info">
                <span class="lefmb-hd-i-name">Hosted by <?php echo esc_html( $host_name ); ?></span>
                <span class="lefmb-hd-i-tag">Verified Host</span>
            </div>
        </div>

        <!-- Description -->
        <div class="lefmb-description">
            <p><?php echo nl2br( esc_html( $desc_truncated ) ); ?></p>
            <?php if ( $show_desc_more ) : ?>
            <div class="lefmb-desc-btn" id="lef-spv-desc-more-mb">Show more</div>
            <?php endif; ?>
        </div>

        <!-- Amenities -->
        <div class="lefmb-amenity-cont">
            <h1 class="lefmb-am-heading">What this place offers</h1>
            <ol class="lefmb-am-lists">
                <?php foreach ( $amenities_visible as $am ) : ?>
                <list class="lefmb-am-li-list">
                    <span class="lefmb-am-li-svg">
                        <?php if ( $am['svg_path'] ) : ?>
                        <img src="<?php echo esc_url( $am['svg_path'] ); ?>" alt="" style="display:block; height:24px; width:24px;">
                        <?php endif; ?>
                    </span>
                    <span class="lefmb-am-li-title"><?php echo esc_html( $am['name'] ); ?></span>
                </list>
                <?php endforeach; ?>
            </ol>
            <?php if ( $amenities_extra ) : ?>
            <div class="lefmb-show-amenity-btn" id="lef-spv-amenity-more-mb">Show all <?php echo count( $amenities_list ); ?> amenities</div>
            <?php endif; ?>
        </div>

        <!-- Calendar (shared classes with desktop) -->
        <div class="lefdk-calender">
            <h1 class="lefdk-cal-heading">Select check-in date</h1>
            <div class="lefdk-cal-wrapper">
                <div class="lefdk-cal-month-container">
                    <div class="lefdk-cal-header">
                        <button class="lefdk-cal-nav-btn" id="lef-spv-prevMonth-mb">
                            <svg viewBox="0 0 24 24"><path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"></path></svg>
                        </button>
                        <span class="lefdk-cal-month" id="lef-spv-currentMonth-mb"></span>
                        <button class="lefdk-cal-nav-btn" id="lef-spv-nextMonth-mb">
                            <svg viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"></path></svg>
                        </button>
                    </div>
                    <div class="lefdk-cal-grid" id="lef-spv-calendarGrid-mb"></div>
                </div>
                <span class="lefdk-cal-clear-btn" id="lef-spv-clearDates-mb">clear dates</span>
            </div>
        </div>

        <!-- Reviews -->
        <div class="lefmb-all-review">
            <div class="lefmb-ar-review">
                <?php if ( $review_count > 0 ) : ?>
                <div class="lefmb-ar-r-number"><?php echo $star_svg_sm; ?><?php echo $avg_rating; ?></div>
                <div class="lefmb-ar-r-total-revi"><span class="lefmb-dot"> · </span><?php echo $review_count; ?> reviews</div>
                <?php else : ?>
                <div class="lefmb-ar-r-number">No Reviews</div>
                <?php endif; ?>
            </div>

            <?php if ( $review_count > 0 ) : ?>
            <div class="lefmb-review-cards">
                <?php foreach ( $reviews_visible as $rev ) : ?>
                <div class="lefmb-rc-card">
                    <div class="lefmb-rc-header">
                        <img src="<?php echo esc_url( $rev['avatar'] ? $rev['avatar'] : LEF_PLUGIN_URL . 'global-assets/images/placeholder-avatar.png' ); ?>" alt="Avatar" class="lefdk-rc-avatar">
                        <div class="lefmb-rc-info">
                            <span class="lefmb-rc-name"><?php echo esc_html( $rev['name'] ); ?></span>
                            <div class="lefmb-rc-date"><?php echo lef_format_review_date( $rev['created_at'] ); ?> <span class="lefmb-rc-rating"><?php echo $star_svg_review; ?><?php echo $rev['rating']; ?></span></div>
                        </div>
                    </div>
                    <p class="lefmb-rc-text"><?php echo esc_html( $rev['review'] ); ?></p>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <?php if ( $reviews_extra ) : ?>
            <div class="lefmb-show-amenity-btn" id="lef-spv-reviews-more-mb">Show all <?php echo $review_count; ?> reviews</div>
            <?php endif; ?>

            <?php if ( $can_review && is_user_logged_in() ) : ?>
            <div class="lefmb-show-amenity-btn" id="lef-spv-write-review-btn-mb">
                ✍️ <?php echo $has_review ? 'Edit your review' : 'Write a review'; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- Similar Properties -->
        <div class="lefmb-similar-results">
            <h1 class="lefmb-sm-heading">More stays nearby</h1>
            <div class="lefmb-sm-r-cards" id="lef-spv-similar-mb"></div>
        </div>

        <!-- Sticky Bottom Bar -->
        <div class="lefmb-nav" id="lef-spv-mb-nav">
            <div class="lefmb-reserv-right-cont">
                <span class="lefmb-night-price" id="lef-spv-mb-price">Add dates for prices</span>
                <span class="lefmb-night-info" id="lef-spv-mb-price-info"></span>
            </div>
            <button class="lefmb-reserv-btn" id="lef-spv-mb-reserve-btn">Reserve</button>
        </div>

    </div>

</div>


<!-- ==============================
     POPUPS / MODALS (shared between desktop & mobile)
     ============================== -->

<!-- Photo Gallery Modal -->
<div class="lef-spv-modal" id="lef-spv-photo-modal" style="display:none;">
    <div class="lef-spv-modal-header">
        <span class="lef-spv-modal-close" data-close="lef-spv-photo-modal">✕</span>
    </div>
    <div class="lef-spv-modal-body lef-spv-photo-gallery" id="lef-spv-photo-gallery">
        <?php foreach ( $images as $img_url ) : ?>
        <img src="<?php echo esc_url( $img_url ); ?>" alt="<?php echo $title; ?>">
        <?php endforeach; ?>
    </div>
</div>

<!-- Description Popup -->
<div class="lef-spv-modal" id="lef-spv-desc-modal" style="display:none;">
    <div class="lef-spv-modal-header">
        <span class="lef-spv-modal-close" data-close="lef-spv-desc-modal">✕</span>
        <h2>About this place</h2>
    </div>
    <div class="lef-spv-modal-body">
        <p class="lef-spv-desc-full"><?php echo nl2br( esc_html( $description ) ); ?></p>
    </div>
</div>

<!-- Amenities Popup -->
<div class="lef-spv-modal" id="lef-spv-amenity-modal" style="display:none;">
    <div class="lef-spv-modal-header">
        <span class="lef-spv-modal-close" data-close="lef-spv-amenity-modal">✕</span>
        <h2>What this place offers</h2>
    </div>
    <div class="lef-spv-modal-body">
        <ol class="lef-spv-amenity-full-list">
            <?php foreach ( $amenities_list as $am ) : ?>
            <list class="lefdk-am-li-list">
                <span class="lefdk-am-li-svg">
                    <?php if ( $am['svg_path'] ) : ?>
                    <img src="<?php echo esc_url( $am['svg_path'] ); ?>" alt="" style="display:block; height:24px; width:24px;">
                    <?php endif; ?>
                </span>
                <span class="lefdk-am-li-title"><?php echo esc_html( $am['name'] ); ?></span>
            </list>
            <?php endforeach; ?>
        </ol>
    </div>
</div>

<!-- Reviews Popup -->
<div class="lef-spv-modal" id="lef-spv-reviews-modal" style="display:none;">
    <div class="lef-spv-modal-header">
        <span class="lef-spv-modal-close" data-close="lef-spv-reviews-modal">✕</span>
        <h2><?php echo $review_count > 0 ? $star_svg_sm . ' ' . $avg_rating . ' · ' . $review_count . ' reviews' : 'Reviews'; ?></h2>
    </div>
    <div class="lef-spv-modal-body lef-spv-reviews-full">
        <?php foreach ( $reviews as $rev ) : ?>
        <div class="lefdk-rc-card">
            <div class="lefdk-rc-header">
                <img src="<?php echo esc_url( $rev['avatar'] ? $rev['avatar'] : LEF_PLUGIN_URL . 'global-assets/images/placeholder-avatar.png' ); ?>" alt="Avatar" class="lefdk-rc-avatar">
                <div class="lefdk-rc-info">
                    <span class="lefdk-rc-name"><?php echo esc_html( $rev['name'] ); ?></span>
                    <div class="lefdk-rc-date"><?php echo lef_format_review_date( $rev['created_at'] ); ?> <span class="lefdk-rc-rating"><?php echo $star_svg_review; ?><?php echo $rev['rating']; ?></span></div>
                </div>
            </div>
            <p class="lefdk-rc-text"><?php echo esc_html( $rev['review'] ); ?></p>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- Write / Edit Review Popup -->
<div class="lef-spv-modal" id="lef-spv-review-form-modal" style="display:none;">
    <div class="lef-spv-modal-header">
        <span class="lef-spv-modal-close" data-close="lef-spv-review-form-modal">✕</span>
        <h2><?php echo $has_review ? 'Edit Your Review' : 'Write a Review'; ?></h2>
    </div>
    <div class="lef-spv-modal-body">
        <div class="lef-spv-rating-select" id="lef-spv-rating-select">
            <span>Select Rating:</span>
            <div class="lef-spv-stars">
                <?php for ( $s = 1; $s <= 5; $s++ ) : ?>
                <span class="lef-spv-star" data-value="<?php echo $s; ?>">★</span>
                <?php endfor; ?>
            </div>
        </div>
        <textarea id="lef-spv-review-text" placeholder="Write your review here..." rows="5"><?php echo $has_review ? esc_textarea( $existing_review->review ) : ''; ?></textarea>
        <button class="lefdk-lf-btn" id="lef-spv-submit-review-btn">Submit Review</button>
    </div>
</div>

<!-- Mobile Reservation Full-screen Modal -->
<div class="lef-spv-modal lef-spv-fullscreen" id="lef-spv-mb-reserve-modal" style="display:none;">
    <div class="lef-spv-modal-header">
        <span class="lef-spv-modal-close" data-close="lef-spv-mb-reserve-modal">✕</span>
        <h2>Reserve</h2>
    </div>
    <div class="lef-spv-modal-body">
        <div class="lef-spv-mbr-dates">
            <div class="lefdk-lf-dates">
                <div class="lefdk-lf-date-row">
                    <div class="lefdk-lf-date-field" id="lef-spv-mb-checkin-field">
                        <label>Check-in</label>
                        <span id="lef-spv-mb-checkin-label">Add date</span>
                    </div>
                    <div class="lefdk-lf-date-field" id="lef-spv-mb-checkout-field">
                        <label>Check-out</label>
                        <span id="lef-spv-mb-checkout-label">Add date</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Inline calendar for mobile reservation -->
        <div class="lefdk-calender" style="padding-top:0;">
            <div class="lefdk-cal-wrapper">
                <div class="lefdk-cal-month-container">
                    <div class="lefdk-cal-header">
                        <button class="lefdk-cal-nav-btn" id="lef-spv-prevMonth-mbr">
                            <svg viewBox="0 0 24 24"><path d="M15.41 7.41L14 6l-6 6 6 6 1.41-1.41L10.83 12z"></path></svg>
                        </button>
                        <span class="lefdk-cal-month" id="lef-spv-currentMonth-mbr"></span>
                        <button class="lefdk-cal-nav-btn" id="lef-spv-nextMonth-mbr">
                            <svg viewBox="0 0 24 24"><path d="M10 6L8.59 7.41 13.17 12l-4.58 4.59L10 18l6-6z"></path></svg>
                        </button>
                    </div>
                    <div class="lefdk-cal-grid" id="lef-spv-calendarGrid-mbr"></div>
                </div>
            </div>
        </div>

        <!-- Guest selector -->
        <div class="lefdk-lf-guests" id="lef-spv-mb-guests-trigger">
            <label>Guests</label>
            <span id="lef-spv-mb-guests-label">1 guest</span>
        </div>
        <div class="lef-spv-guests-dropdown" id="lef-spv-mb-guests-dropdown" style="display:none;">
            <div class="lef-spv-gd-row">
                <div class="lef-spv-gd-info"><span class="lef-spv-gd-title">Adults</span><span class="lef-spv-gd-sub">Age 13+</span></div>
                <div class="lef-spv-gd-controls">
                    <button class="lef-spv-gd-btn" data-action="minus" data-type="adults" data-ctx="mb">−</button>
                    <span class="lef-spv-gd-count" id="lef-spv-mb-adults-count">1</span>
                    <button class="lef-spv-gd-btn" data-action="plus" data-type="adults" data-ctx="mb">+</button>
                </div>
            </div>
            <div class="lef-spv-gd-row">
                <div class="lef-spv-gd-info"><span class="lef-spv-gd-title">Children</span><span class="lef-spv-gd-sub">Age 2–12</span></div>
                <div class="lef-spv-gd-controls">
                    <button class="lef-spv-gd-btn" data-action="minus" data-type="children" data-ctx="mb">−</button>
                    <span class="lef-spv-gd-count" id="lef-spv-mb-children-count">0</span>
                    <button class="lef-spv-gd-btn" data-action="plus" data-type="children" data-ctx="mb">+</button>
                </div>
            </div>
            <div class="lef-spv-gd-row">
                <div class="lef-spv-gd-info"><span class="lef-spv-gd-title">Infants</span><span class="lef-spv-gd-sub">Under 2</span></div>
                <div class="lef-spv-gd-controls">
                    <button class="lef-spv-gd-btn" data-action="minus" data-type="infants" data-ctx="mb">−</button>
                    <span class="lef-spv-gd-count" id="lef-spv-mb-infants-count">0</span>
                    <button class="lef-spv-gd-btn" data-action="plus" data-type="infants" data-ctx="mb">+</button>
                </div>
            </div>
        </div>

        <button class="lefdk-lf-btn" id="lef-spv-mb-confirm-reserve">Reserve</button>
        <p class="lefdk-lf-info">You won't be charged yet</p>
    </div>
</div>

</div><!-- /#lef-spv-root -->
