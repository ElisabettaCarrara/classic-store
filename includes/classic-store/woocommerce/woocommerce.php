<?php
/**
 * Plugin Name:  Classic Store Compatibility for Woo Addons
 * Description:  Compatibility plugin for some WooCommerce addons to work with Classic Store.
 * Author:       Elisabetta Carrara and ClassicPress Community
 * Version:      1.0.0
 * Requires CP:  2.0
 * Requires PHP: 7.4
 * Update URI:   false
 * License:      GPL2
 *
 * @package      ClassicStore/Compat
 */

defined( 'ABSPATH' ) || exit;

if ( ! defined( 'CS_WOOADDONSCOMPAT_PLUGIN_BASE' ) ) {
	define( 'CS_WOOADDONSCOMPAT_PLUGIN_BASE', plugin_basename( __FILE__ ) );
}

if ( ! defined( 'CS_WOOADDONSCOMPAT_VERSION' ) ) {
	define( 'CS_WOOADDONSCOMPAT_VERSION', '1.0.0' );
}

/**
 * Filter plugin row meta to remove "View details".
 *
 * @param array $plugin_meta Plugin row meta.
 * @param array $plugin_file Plugin file.
 * @return array
 */
function cs_wooaddonscompat_hide_view_details( $plugin_meta, $plugin_file ) {
	if ( CS_WOOADDONSCOMPAT_PLUGIN_BASE === $plugin_file ) {
		unset( $plugin_meta[2] );
	}
	return $plugin_meta;
}
add_filter( 'plugin_row_meta', 'cs_wooaddonscompat_hide_view_details', 10, 2 );
