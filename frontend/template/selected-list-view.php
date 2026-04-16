<?php
/**
 * selected-list-view.php Template
 * 
 * Renders listings in Grid or Carousel based on shortcode attributes.
 *
 * @package ListingEngineFrontend
 */

if ( ! defined( 'ABSPATH' ) ) {
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

// 1. Get attributes passed from the handler.
$atts = get_query_var( 'lef_selected_atts' );
if ( ! $atts ) {
	$atts = array( 'view' => 'grid', 'count' => 9, 'location' => '', 'type' => '' );
}

$view_mode    = sanitize_text_field( $atts['view'] );
$count        = intval( $atts['count'] );
$location_slug = sanitize_text_field( $atts['location'] );
$type_slug     = sanitize_text_field( $atts['type'] );

// ── CONFIGURATION ──
$title_char_limit = 35; // Max characters for property title (Type in Location)

// 2. Lookup IDs from names if provided.
$location_id = 0;
if ( ! empty( $location_slug ) ) {
	$location_id = $wpdb->get_var( $wpdb->prepare(
		"SELECT id FROM {$wpdb->prefix}ls_location WHERE name = %s",
		$location_slug
	));
}

$type_id = 0;
if ( ! empty( $type_slug ) ) {
	$type_id = $wpdb->get_var( $wpdb->prepare(
		"SELECT id FROM {$wpdb->prefix}ls_types WHERE name = %s",
		$type_slug
	));
}

// 3. Build Listing Query.
$query = "
	SELECT l.*, t.name as type_name, loc.name as location_name 
	FROM {$wpdb->prefix}ls_property l
	LEFT JOIN {$wpdb->prefix}ls_types t ON l.type = t.id
	LEFT JOIN {$wpdb->prefix}ls_location loc ON l.location = loc.id
	WHERE l.status = 'published'
";

if ( $location_id > 0 ) {
	$query .= $wpdb->prepare( " AND l.location = %d", $location_id );
}
if ( $type_id > 0 ) {
	$query .= $wpdb->prepare( " AND l.type = %d", $type_id );
}

$query .= $wpdb->prepare( " LIMIT %d", $count );

// 3b. Check Total Count for 'See All' logic
$count_query = "SELECT COUNT(*) FROM {$wpdb->prefix}ls_property WHERE status = 'published'";
if ( $location_id > 0 ) {
	$count_query .= $wpdb->prepare( " AND location = %d", $location_id );
}
if ( $type_id > 0 ) {
	$count_query .= $wpdb->prepare( " AND type = %d", $type_id );
}
$total_count = intval( $wpdb->get_var( $count_query ) );
$has_more    = ( $total_count > $count );

$listings = $wpdb->get_results( $query );

// 4. Resolve "See All" URL.
$archive_page_id = $wpdb->get_var( $wpdb->prepare(
	"SELECT page_id FROM {$wpdb->prefix}admin_management WHERE name = %s",
	'Listing Archive'
));

$see_all_url = $archive_page_id ? get_permalink( $archive_page_id ) : '#';

if ( ! empty( $location_slug ) || ! empty( $type_slug ) ) {
	$params = array();
	if ( ! empty( $location_slug ) ) $params['location'] = $location_slug;
	if ( ! empty( $type_slug ) )     $params['type']     = $type_slug;
	$see_all_url = add_query_arg( $params, $see_all_url );
}

// 5. Rendering Logic Starts Below
?>

<div class="lef-selected-container <?php echo ($view_mode === 'carousel') ? 'lef-view-carousel' : 'lef-view-grid'; ?>">
    
    <?php if ( $view_mode === 'carousel' ) : ?>
        <div class="lef-carousel-wrapper">
            <button class="lef-carousel-nav lef-nav-prev" aria-label="Previous">❮</button>
            <div class="lef-carousel-track">
    <?php else : ?>
        <div class="lef-grid-wrapper">
    <?php endif; ?>

        <?php if ( $listings ) : ?>
            <?php foreach ( $listings as $listing ) : 
                // Image handling (JSON from wp_ls_img)
                $image_data = $wpdb->get_var( $wpdb->prepare(
                    "SELECT image FROM {$wpdb->prefix}ls_img WHERE property_id = %d",
                    $listing->id
                ));

                $images = array();
                if ( $image_data ) {
                    $decoded = json_decode( $image_data, true );
                    if ( is_array( $decoded ) ) {
                        usort( $decoded, function( $a, $b ) { return $a['sort_order'] - $b['sort_order']; } );
                        $images = array_column( $decoded, 'url' );
                    }
                }

                $fallback_img = LEF_PLUGIN_URL . 'global-assets/images/placeholder.png';
                $main_image = ! empty( $images ) ? $images[0] : $fallback_img;
                $is_placeholder = empty( $images );

                $type_display = $listing->type_name ? $listing->type_name : 'Property';
                $loc_display  = $listing->location_name ? $listing->location_name : 'Premium Location';
                $title        = sprintf( "%s in %s", $type_display, $loc_display );

                if ( mb_strlen( $title ) > $title_char_limit ) {
                    $title = mb_substr( $title, 0, $title_char_limit ) . '..';
                }

                $summary = sprintf( "%d bedroom, %d bed", $listing->bedroom, $listing->bed );
                $redirect_url = lef_get_secure_detail_url( $listing->id );
            ?>
                <div class="lef-property-card" data-redirect="<?php echo esc_url( $redirect_url ); ?>">
                    <div class="lef-card-image-container <?php echo $is_placeholder ? 'lef-has-placeholder' : ''; ?>">
                        <img src="<?php echo esc_url( $main_image ); ?>" 
                             alt="<?php echo esc_attr( $title ); ?>" 
                             class="lef-card-image <?php echo $is_placeholder ? 'lef-is-placeholder' : ''; ?>"
                             loading="lazy"
                             onerror="this.src='<?php echo esc_url($fallback_img); ?>'; this.classList.add('lef-is-placeholder'); this.parentElement.classList.add('lef-has-placeholder');">
                        
                        <?php $is_wishlisted = in_array( (int) $listing->id, $wishlist_ids ); ?>
                        <button class="lef-favorite-btn <?php echo $is_wishlisted ? 'is-active' : ''; ?>" 
                                aria-label="Add to wishlist"
                                data-id="<?php echo esc_attr( $listing->id ); ?>">
                            <svg viewBox="0 0 24 24"><path d="M20.84 4.61a5.5 5.5 0 0 0-7.78 0L12 5.67l-1.06-1.06a5.5 5.5 0 0 0-7.78 7.78l1.06 1.06L12 21.23l7.78-7.78 1.06-1.06a5.5 5.5 0 0 0 0-7.78z"></path></svg>
                        </button>
                    </div>

                    <div class="lef-card-body">
                        <h3 class="lef-property-title"><?php echo esc_html( $title ); ?></h3>
                        <p class="lef-property-summary"><?php echo esc_html( $summary ); ?></p>
                        <div class="lef-property-price">
                            <span class="lef-price-amount">₹<?php echo number_format($listing->price); ?></span>
                            <span class="lef-price-period">/ night</span>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php 
        // Collect up to 3 images for the 'See All' card collage
        $collage_images = array();
        if ( $listings ) {
            foreach ( $listings as $listing_item ) {
                if ( count( $collage_images ) >= 3 ) break;
                
                $img_data = $wpdb->get_var( $wpdb->prepare(
                    "SELECT image FROM {$wpdb->prefix}ls_img WHERE property_id = %d",
                    $listing_item->id
                ));
                if ( $img_data ) {
                    $decoded_img = json_decode( $img_data, true );
                    if ( is_array( $decoded_img ) && ! empty( $decoded_img ) ) {
                        usort( $decoded_img, function( $a, $b ) { return $a['sort_order'] - $b['sort_order']; } );
                        $img_url = ! empty( $decoded_img[0]['url'] ) ? $decoded_img[0]['url'] : '';
                        if ( $img_url ) {
                            $collage_images[] = $img_url;
                        }
                    }
                }
            }
        }
        // Fill defaults if less than 3
        $fallback_img = LEF_PLUGIN_URL . 'global-assets/images/placeholder.png';
        while ( count( $collage_images ) < 3 ) {
            $collage_images[] = $fallback_img;
        }
        ?>

        <?php if ( $has_more ) : ?>
        <!-- See All Card -->
        <div class="lef-property-card lef-see-all-card" data-redirect="<?php echo esc_url( $see_all_url ); ?>">
            <div class="lef-see-all-content">
                <div class="lef-see-all-collage">
                    <?php foreach ( $collage_images as $index => $img_url ) : ?>
                        <div class="lef-collage-item lef-collage-<?php echo $index + 1; ?>">
                            <img src="<?php echo esc_url( $img_url ); ?>" 
                                 alt="Preview"
                                 onerror="this.src='<?php echo esc_url($fallback_img); ?>';">
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="lef-see-all-footer">
                    <span class="lef-see-all-text">See all</span>
                    <div class="lef-see-all-icon">
                        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                            <polyline points="12 5 19 12 12 19"></polyline>
                        </svg>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

    <?php if ( $view_mode === 'carousel' ) : ?>
            </div> <!-- End track -->
            <button class="lef-carousel-nav lef-nav-next" aria-label="Next">❯</button>
        </div> <!-- End wrapper -->
    <?php else : ?>
        </div> <!-- End grid -->
    <?php endif; ?>

</div>
