<?php
/*
Plugin Name: WP Locate-It
Description: Provides developers a lightweight and easy to use toolset to utilize the Google Maps API to generate location data for standard and custom post types
Version: 0.9.0
Author: Ninesphere
Author URI: http://www.ninesphere.com
Text Domain: wp-locate-it
License: GPLv2 or later
*/

define( 'WPLI_PLUGIN_URL', untrailingslashit( plugin_dir_url( __FILE__ ) ) );
define( 'WPLI_PLUGIN_PATH', untrailingslashit( plugin_dir_path( __FILE__ ) ) );
define( 'WPLI_PLUGIN_VERSION', '0.9.0' );

/**
 * Load the required WPLI class
 */
require WPLI_PLUGIN_PATH . '/includes/wpli.class.php';

/**
 * Globalize and initialize WP Locatie-It
 */
global $WPLocateIt;
$WPLocateIt = new WPLocateIt();

/**
 * Register Wordpress hooks. This should only ever be run once.
 */
$WPLocateIt->register_wp_hooks();

/**
 * Load additional functions that can be used within the theme
 */
require WPLI_PLUGIN_PATH . '/includes/wpli.functions.php';
require WPLI_PLUGIN_PATH . '/includes/wpli.shortcodes.php';

?>