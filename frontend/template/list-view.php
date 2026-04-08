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

// 1. Fetch Location from Query Params for Dynamic Header.
$location_id = isset($_GET['location']) ? intval($_GET['location']) : 0;
$location_name = '';
$count_text = 'Premium Property';

if ($location_id > 0) {
    $location_name = $wpdb->get_var($wpdb->prepare(
        "SELECT name FROM {$wpdb->prefix}ls_location WHERE id = %d",
        $location_id
    ));

    if ($location_name) {
        $total_count = $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$wpdb->prefix}ls_listings WHERE location = %d AND status = 'published'",
            $location_id
        ));
        $count_text = sprintf("Over %d homes in %s", number_format($total_count), $location_name);
    }
}

// 2. Fetch Listings.
$query = "
	SELECT l.*, t.name as type_name, loc.name as location_name 
	FROM {$wpdb->prefix}ls_listings l
	LEFT JOIN {$wpdb->prefix}ls_types t ON l.type = t.id
	LEFT JOIN {$wpdb->prefix}ls_location loc ON l.location = loc.id
	WHERE l.status = 'published'
";

if ($location_id > 0) {
    $query .= $wpdb->prepare(" AND l.location = %d", $location_id);
}

$listings = $wpdb->get_results($query);

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
                $fallback_svg = LEF_PLUGIN_URL . 'global-assets/images/no-image.svg';
                if (empty($images)) {
                    $images = array($fallback_svg);
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
                             onerror="this.src='<?php echo esc_url($fallback_svg); ?>'; this.classList.add('lef-is-placeholder');">

                        <button class="lef-favorite-btn" aria-label="Add to wishlist">
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
                            <span class="lef-price-period">/ Night</span>
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