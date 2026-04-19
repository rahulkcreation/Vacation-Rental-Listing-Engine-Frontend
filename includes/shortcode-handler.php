<?php
/**
 * Shortcode Handler for Listing Engine Frontend.
 *
 * @package ListingEngineFrontend
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the shortcodes.
 */
function lef_register_shortcodes() {
	add_shortcode( 'listing_engine_view', 'lef_render_list_view' );
	add_shortcode( 'selected_list_view', 'lef_render_selected_list_view' );
	add_shortcode( 'premium_search_bar', 'lef_render_premium_search_bar' );
	add_shortcode( 'single_property_view', 'lef_render_single_property_view' );
	add_shortcode( 'lef_my_profile', 'lef_render_my_profile' );
}
add_action( 'init', 'lef_register_shortcodes' );

/**
 * Render the Premium Search Bar.
 * @return string The rendered HTML.
 */
function lef_render_premium_search_bar() {
	ob_start();
	
	$template_path = LEF_PLUGIN_DIR . 'frontend/template/search-bar.php';
	
	if ( file_exists( $template_path ) ) {
		include $template_path;
	} else {
		echo '<p>Search bar template not found.</p>';
	}
	
	return ob_get_clean();
}

/**
 * Render the List View.
 *
 * @return string The rendered HTML.
 */
function lef_render_list_view() {
	ob_start();
	
	// Include the template.
	$template_path = LEF_PLUGIN_DIR . 'frontend/template/list-view.php';
	
	if ( file_exists( $template_path ) ) {
		include $template_path;
	} else {
		echo '<p>Template not found.</p>';
	}
	
	return ob_get_clean();
}

/**
 * Render the Selected List View.
 * 
 * @param array $atts Shortcode attributes.
 * @return string The rendered HTML.
 */
function lef_render_selected_list_view( $atts ) {
	$atts = shortcode_atts( array(
		'view'     => 'grid',
		'count'    => 9,
		'location' => '',
		'type'     => '',
	), $atts, 'selected_list_view' );

	ob_start();
	
	// Include the template.
	$template_path = LEF_PLUGIN_DIR . 'frontend/template/selected-list-view.php';
	
	if ( file_exists( $template_path ) ) {
		// Pass attributes to the template.
		set_query_var( 'lef_selected_atts', $atts );
		include $template_path;
	} else {
		echo '<p>Template not found: ' . esc_html( $template_path ) . '</p>';
	}
	
	return ob_get_clean();
}

/**
 * Render the Single Property View.
 *
 * Decodes the property ID from the URL query parameter. If no valid ID
 * is found, the visitor is redirected to the homepage. Otherwise the
 * single-property-view template is loaded with the resolved property data.
 *
 * @return string The rendered HTML.
 */
function lef_render_single_property_view() {

	// ── Skip redirect inside page-builder / preview contexts ──
	// Elementor editor, WordPress preview, and Customizer should never
	// be redirected — they don't carry query params during editing.
	$is_editor = (
		( isset( $_GET['elementor-preview'] ) ) ||            // Elementor preview panel
		( isset( $_GET['action'] ) && $_GET['action'] === 'elementor' ) || // Elementor iframe
		( class_exists( '\Elementor\Plugin' ) && \Elementor\Plugin::$instance->editor->is_edit_mode() ) || // Elementor edit mode
		( is_preview() ) ||                                    // WordPress native preview
		( is_customize_preview() ) ||                          // Customizer
		( isset( $_GET['fl_builder'] ) ) ||                    // Beaver Builder
		( isset( $_GET['et_fb'] ) )                            // Divi Builder
	);

	if ( $is_editor ) {
		// Render a placeholder so the editor can display the shortcode block.
		return '<div style="padding:3rem 2rem;text-align:center;border:2px dashed #e2e8f0;border-radius:12px;color:#5a6e7c;font-size:0.95rem;">'
			 . '<strong>[single_property_view]</strong><br>'
			 . 'This block renders the property detail page. It requires a <code>property_ref</code> URL parameter to display live data.'
			 . '</div>';
	}

	// ── Guard: Redirect to home if no valid property ref in URL ──
	$property_id = lef_get_decoded_listing_id();

	if ( ! $property_id ) {
		// Cannot redirect inside a shortcode (headers already sent)
		// so we output a JS redirect instead.
		return '<script>window.location.href = "' . esc_url( home_url( '/' ) ) . '";</script>';
	}

	ob_start();

	$template_path = LEF_PLUGIN_DIR . 'frontend/template/single-property-view.php';

	if ( file_exists( $template_path ) ) {
		// Make property_id available to the template.
		set_query_var( 'lef_property_id', $property_id );
		include $template_path;
	} else {
		echo '<p>Single property view template not found.</p>';
	}

	return ob_get_clean();
}
/**
 * Render the My Profile Dashboard.
 *
 * @return string The rendered HTML.
 */
function lef_render_my_profile() {
	if ( ! is_user_logged_in() ) {
		return '<div class="lef-myprofile-login-required" style="padding: 40px; text-align: center;">
					<h2 style="margin-bottom: 20px;">Please Login</h2>
					<p>You must be logged in to access your profile dashboard.</p>
				</div>';
	}

	ob_start();

	$template_path = LEF_PLUGIN_DIR . 'frontend/template/my-profile.php';

	if ( file_exists( $template_path ) ) {
		include $template_path;
	} else {
		echo '<p>My Profile template not found.</p>';
	}

	return ob_get_clean();
}

