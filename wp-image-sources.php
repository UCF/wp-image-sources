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

if ( defined( 'WP_CLI' ) ) {
	require_once 'includes/class-image-utility.php';
	require_once 'includes/class-image-processer.php';
	require_once 'commands/wp-cli-add-image-class.php';
}
