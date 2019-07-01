<?php
if ( ! class_exists( 'WP_Image_Sources_Commands' ) ) {
	class WP_Image_Sources_Commands {
		/**
		 * Adds the `wp-image-ID` class to all images found in post content.
		 *
		 * ## EXAMPLES
		 *
		 * wp media add-image-class
		 *
		 * @when after_wp_load
		 */
		function __invoke( $args, $assoc_args ) {
			$test_image = '<img class="w-100" style="margin-top: -2rem;" src="http://localhost/wordpress/wp-content/uploads/2017/10/knightro_two_hands_point.png" alt="Knightro" />';

			$util = new WPIS_Image_Processer();
			$util->process_images();
			WP_CLI::success( $util->print_stats() );
		}
	}

	WP_CLI::add_command( 'images add-class', 'WP_Image_Sources_Commands' );
}
