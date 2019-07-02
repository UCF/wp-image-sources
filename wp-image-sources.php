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

if ( defined( 'WP_CLI' ) ) {
	require_once 'includes/class-image-utility.php';
	require_once 'includes/class-image-processor.php';
	require_once 'commands/wp-cli-add-image-class.php';

	require_once 'includes/class-convert-attachment.php';
	require_once 'includes/class-image-webp-converter.php';
	require_once 'commands/wp-cli-webp-convert.php';
}
