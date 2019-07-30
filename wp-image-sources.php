<?php
/*
Plugin Name: WP Image Sources
Description: WP Image Sources handles inserting image sources and sizes into image tags to optimize image delivery for mobile devices.
Version: 1.0.0
Author: UCF Web Communications
License: GPL3
GitHub Plugin URI: UCF/wp-image-sources
*/

if ( ! defined( 'WPINC' ) ) {
    die;
}

define( 'WPIS__PLUGIN_FILE', __FILE__ );
define( 'WPIS__PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPIS__VENDOR_DIR', WPIS__PLUGIN_DIR . '/vendor' );

/**
 * General includes required for all functionality
 */
require_once 'includes/class-image-utility.php';
require_once 'includes/wpis-utilities.php';
require_once 'includes/class-convert-error.php';
require_once 'includes/class-convert-attachment.php';
require_once 'includes/class-filters.php';

if ( defined( 'WP_CLI' ) ) {
	/**
	 * Specific includes for WP_CLI
	 */
	require_once 'includes/class-image-processor.php';
	require_once 'commands/wp-cli-add-image-class.php';

	require_once 'includes/class-image-webp-converter.php';
	require_once 'commands/wp-cli-webp-convert.php';
}

if ( ! function_exists( 'wpis_init' ) ) {
	/**
	 * Function that runs when all plugins are loaded
	 * @author Jim Barnes
	 * @since 1.0.0
	 */
	function wpis_init() {
		add_filter( 'wp_generate_attachment_metadata', array( 'WPIS_Filters', 'wpis_generate_attachment_metadata' ), 10, 2 );
		add_filter( 'delete_attachment', array( 'WPIS_Filters', 'wpis_delete_attachment' ), 10, 1 );

		remove_filter( 'the_content', 'wp_make_content_images_responsive' );
		add_filter( 'the_content', array( 'WPIS_Filters', 'wpis_make_content_images_responsive' ), 10, 1 );
	}

	add_action( 'plugins_loaded', 'wpis_init' );
}
