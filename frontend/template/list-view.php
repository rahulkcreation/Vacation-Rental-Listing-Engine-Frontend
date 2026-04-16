<?php

/**
 * list-view.php Template
 *
 * @package ListingEngineFrontend
 */

if (! defined('ABSPATH')) {
    exit;
}

global $wpdb;

// Fetch current user wishlist if logged in
$wishlist_ids = array();
if ( is_user_logged_in() ) {
    $current_user_id = get_current_user_id();
    $wishlist_data = $wpdb->get_results( $wpdb->prepare(
        "SELECT property_id FROM {$wpdb->prefix}ls_wishlist WHERE user_id = %d",
        $current_user_id
    ) );
    if ( $wishlist_data ) {
        $wishlist_ids = array_map( function( $item ) { return (int) $item->property_id; }, $wishlist_data );
    }
}

// 1. Extract and Sanitize Parameters.
$location_param = isset($_GET['location']) ? sanitize_text_field($_GET['location']) : '';
$address_param  = isset($_GET['address'])  ? sanitize_text_field($_GET['address'])  : '';
$type_param     = isset($_GET['type'])     ? sanitize_text_field($_GET['type'])     : '';
$guests_param   = isset($_GET['guests'])   ? intval($_GET['guests'])                : 0;
$checkin_param  = isset($_GET['checkin'])  ? sanitize_text_field($_GET['checkin'])  : '';
$checkout_param = isset($_GET['checkout']) ? sanitize_text_field($_GET['checkout']) : '';
$amenities_raw  = isset($_GET['amenities']) ? sanitize_text_field($_GET['amenities']) : '';
$min_price      = isset($_GET['min-price']) ? floatval($_GET['min-price'])           : 0;
$max_price      = isset($_GET['max-price']) ? floatval($_GET['max-price'])           : 0;
$sort           = isset($_GET['sort'])      ? sanitize_text_field($_GET['sort'])      : '';

// 2. Availability Check (Date Blocking Logic).
$blocked_property_ids = array();
if ($checkin_param && $checkout_param) {
    // Generate dates in the requested range.
    $start_date = new DateTime($checkin_param);
    $end_date   = new DateTime($checkout_param);
    $requested_dates = array();
    
    $interval = new DateInterval('P1D');
    $date_period = new DatePeriod($start_date, $interval, $end_date->modify('+1 day'));
    foreach ($date_period as $date) {
        $requested_dates[] = $date->format('Y-m-d');
    }

    // Fetch blocked dates from DB.
    $blocks = $wpdb->get_results("SELECT property_id, dates FROM {$wpdb->prefix}ls_block_date");
    foreach ($blocks as $block) {
        $blocked_json = json_decode($block->dates, true);
        if (is_array($blocked_json)) {
            // Check for intersection between requested dates and blocked dates.
            $intersection = array_intersect($requested_dates, $blocked_json);
            if (! empty($intersection)) {
                $blocked_property_ids[] = intval($block->property_id);
            }
        }
    }
}

// 3. Map Name-Based Parameters to IDs.
$location_id = 0;
if ($location_param) {
    if (is_numeric($location_param)) {
        $location_id = intval($location_param);
    } else {
        $location_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}ls_location WHERE name LIKE %s",
            '%' . $wpdb->esc_like($location_param) . '%'
        ));
    }
}

$type_id = 0;
if ($type_param) {
    $type_id = $wpdb->get_var($wpdb->prepare(
        "SELECT id FROM {$wpdb->prefix}ls_types WHERE name LIKE %s",
        '%' . $wpdb->esc_like($type_param) . '%'
    ));
}

$amenity_ids = array();
if ($amenities_raw) {
    $amenity_names = array_map('trim', explode(',', $amenities_raw));
    foreach ($amenity_names as $a_name) {
        $a_id = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}ls_amenities WHERE name LIKE %s",
            '%' . $wpdb->esc_like($a_name) . '%'
        ));
        if ($a_id) $amenity_ids[] = $a_id;
    }
}

// 4. Build Dynamic Query.
$query = "
	SELECT l.*, t.name as type_name, loc.name as location_name 
	FROM {$wpdb->prefix}ls_property l
	LEFT JOIN {$wpdb->prefix}ls_types t ON l.type = t.id
	LEFT JOIN {$wpdb->prefix}ls_location loc ON l.location = loc.id
	WHERE l.status = 'published'
";

// Location/Address Filtering.
if ($location_id > 0) {
    $query .= $wpdb->prepare(" AND l.location = %d", $location_id);
} elseif ($location_param) {
    $query .= $wpdb->prepare(" AND loc.name LIKE %s", 
        '%' . $wpdb->esc_like($location_param) . '%'
    );
}

if ($address_param) {
    $query .= $wpdb->prepare(" AND l.address LIKE %s", 
        '%' . $wpdb->esc_like($address_param) . '%'
    );
}

if ($type_id > 0) {
    $query .= $wpdb->prepare(" AND l.type = %d", $type_id);
}

if ($guests_param > 0) {
    $query .= $wpdb->prepare(" AND l.guests >= %d", $guests_param);
}

if ($min_price > 0) {
    $query .= $wpdb->prepare(" AND l.price >= %f", $min_price);
}

if ($max_price > 0) {
    $query .= $wpdb->prepare(" AND l.price <= %f", $max_price);
}

// Amenities (Must match ALL requested amenities).
if (! empty($amenity_ids)) {
    foreach ($amenity_ids as $id) {
        $query .= $wpdb->prepare(" AND FIND_IN_SET(%d, l.amenities)", $id);
    }
}

// Exclude Blocked Properties.
if (! empty($blocked_property_ids)) {
    $blocked_ids_str = implode(',', array_map('intval', array_unique($blocked_property_ids)));
    $query .= " AND l.id NOT IN ($blocked_ids_str)";
}

// 5. Sorting & Execution.
if ($sort === 'price_low_to_high') {
    $query .= " ORDER BY l.price ASC";
} elseif ($sort === 'price_high_to_low') {
    $query .= " ORDER BY l.price DESC";
} else {
    // Default: Latest entries first
    $query .= " ORDER BY l.id DESC";
}

$listings = $wpdb->get_results($query);

// 5. Update Header Text based on parameters.
if ( empty($_GET) ) {
    $count_text = 'Premium Property';
} else {
    $total_count = count($listings);
    
    // Determine the property type display (e.g., 'Apartments' or 'homes')
    $display_type = ! empty($type_param) ? esc_html($type_param) . 's' : 'homes';
    
    // Determine location/address display
    $display_location = '';
    if (! empty($location_param)) {
        $display_location = ' in ' . esc_html($location_param);
    } elseif (! empty($address_param)) {
        $display_location = ' at ' . esc_html($address_param);
    }
    
    // Final output: e.g., "Over 5 Apartments in Jaipur" or "Over 5 homes at Street 1"
    $count_text = sprintf("Over %d %s%s", $total_count, $display_type, $display_location);
}

?>

<main class="lef-main">
    <div class="lef-section-header">
        <h1 class="lef-section-title"><?php echo esc_html($count_text); ?></h1>
    </div>

    <?php if ($listings) : ?>
        <div class="lef-property-grid">
            <?php foreach ($listings as $listing) :
                // Fetch images for this listing.
                $image_data = $wpdb->get_var($wpdb->prepare(
                    "SELECT image FROM {$wpdb->prefix}ls_img WHERE property_id = %d",
                    $listing->id
                ));

                $images = array();
                if ($image_data) {
                    $decoded_images = json_decode($image_data, true);
                    if (is_array($decoded_images)) {
                        // Sort by sort_order.
                        usort($decoded_images, function ($a, $b) {
                            return $a['sort_order'] - $b['sort_order'];
                        });
                        $images = array_column($decoded_images, 'url');
                    }
                }

                // Fallback image (SVG) if none found in DB.
                $fallback_img = LEF_PLUGIN_URL . 'global-assets/images/placeholder.png';
                if (empty($images)) {
                    $images = array($fallback_img);
                }

                $type_display = $listing->type_name ? $listing->type_name : 'Property';
                $loc_display = $listing->location_name ? $listing->location_name : 'Premium Location';
                $title = sprintf("%s in %s", $type_display, $loc_display);

                // Configurable Title Character Limit.
                $title_limit = 35;
                if (mb_strlen($title) > $title_limit) {
                    $title = mb_substr($title, 0, $title_limit) . '..';
                }

                $summary = sprintf("%d bedroom, %d bed", $listing->bedroom, $listing->bed);
                $redirect_url = lef_get_secure_detail_url($listing->id);
            ?>
                <div class="lef-property-card" data-redirect="<?php echo esc_attr($redirect_url); ?>">
                    <div class="lef-card-image-container"
                        data-images='<?php echo json_encode($images); ?>'
                        data-current="0">
                        <img src="<?php echo esc_url($images[0]); ?>" 
                             alt="<?php echo esc_attr($title); ?>" 
                             class="lef-card-image"
                             onerror="this.src='<?php echo esc_url($fallback_img); ?>'; this.classList.add('lef-is-placeholder');">

                        <?php $is_wishlisted = in_array( (int) $listing->id, $wishlist_ids ); ?>
                        <button class="lef-favorite-btn <?php echo $is_wishlisted ? 'is-active' : ''; ?>" 
                                aria-label="Add to wishlist"
                                data-id="<?php echo esc_attr( $listing->id ); ?>">
                            <svg viewBox="0 0 24 24">
                                <path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path>
                            </svg>
                        </button>

                        <?php if (count($images) > 1) : ?>
                            <button class="lef-image-nav lef-nav-prev">❮</button>
                            <button class="lef-image-nav lef-nav-next">❯</button>
                        <?php endif; ?>
                    </div>

                    <div class="lef-card-body">
                        <div class="lef-card-header">
                            <h3 class="lef-property-title"><?php echo esc_html($title); ?></h3>
                        </div>
                        <p class="lef-property-summary"><?php echo esc_html($summary); ?></p>
                        <div class="lef-property-price">
                            <span class="lef-price-amount">₹<?php echo esc_html($listing->price); ?></span>
                            <span class="lef-price-period">/ night</span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else : ?>
        <div class="lef-no-properties">
            <p>No properties found.</p>
        </div>
    <?php endif; ?>
</main>