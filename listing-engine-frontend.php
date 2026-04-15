<?php
/**
 * Plugin Name: Listing Engine Frontend
 * Plugin URI: https://arttechfuzion.com
 * Description: Replicates property listing engine UI with dynamic data.
 * Version:     1.9.19
 * Author:      Art-Tech Fuzion
 * Author URI:  https://arttechfuzion.com
 * Text Domain: listing-engine-frontend
 * Last Modified: 2026-04-16 - Implemented review character limits and a "Show more" functionality with a dedicated Detail Modal for reading long reviews.
 *
 * @package ListingEngineFrontend
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define Constants.
define( 'LEF_VERSION', '1.9.19' );
define( 'LEF_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'LEF_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

/**
 * Initialize Plugin.
 */
function lef_initialize_plugin() {
	// Include DB Schema.
	require_once LEF_PLUGIN_DIR . 'includes/db-schema.php';

	// Include Registry Hooks.
	require_once LEF_PLUGIN_DIR . 'includes/registery-hooks.php';

	// Include Asset Loader.
	require_once LEF_PLUGIN_DIR . 'includes/assets-loader.php';

	// Include URL Router.
	require_once LEF_PLUGIN_DIR . 'includes/url-router.php';

	// Include Shortcode Handler.
	require_once LEF_PLUGIN_DIR . 'includes/shortcode-handler.php';

	// Include AJAX Handler.
	require_once LEF_PLUGIN_DIR . 'includes/ajax-handler.php';

	// Include DB Handler.
	require_once LEF_PLUGIN_DIR . 'includes/class-db-handler.php';
}
add_action( 'plugins_loaded', 'lef_initialize_plugin' );

// ─────────────────────────────────────────────────────────────
// Plugin Setup
// ─────────────────────────────────────────────────────────────

/* ==================== ACTION LINKS ==================== */

/**
 * Add Settings link on the plugins page.
 *
 * @param array $links Array of plugin action links.
 * @return array
 */
function lef_add_plugin_action_links( $links ) {
	$settings_link = '<a href="' . admin_url( 'admin.php?page=lef-dashboard' ) . '">' . __( 'Settings', 'listing-engine-frontend' ) . '</a>';
	array_unshift( $links, $settings_link );
	return $links;
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'lef_add_plugin_action_links' );
