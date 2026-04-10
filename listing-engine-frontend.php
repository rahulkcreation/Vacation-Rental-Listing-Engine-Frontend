<?php
/**
 * Plugin Name: Listing Engine Frontend
 * Plugin URI: https://arttechfuzion.com
 * Description: Replicates property listing engine UI with dynamic data.
 * Version: 1.4.0
 * Author: Art-Tech Fuzion
 * Author URI: https://arttechfuzion.com
 * Text Domain: listing-engine-frontend
 *
 * @package ListingEngineFrontend
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define Constants.
define( 'LEF_VERSION', '1.4.0' );
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
}
add_action( 'plugins_loaded', 'lef_initialize_plugin' );

// Registry Hooks.
require_once LEF_PLUGIN_DIR . 'includes/registery-hooks.php';
