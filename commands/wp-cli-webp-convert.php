<?php
if ( ! class_exists( 'WPIS_WebP_Convert_Command' ) ) {
	class WPIS_WebP_Convert_Command {
		/**
		 * Converts image attachments to webp.
		 *
		 * ## EXAMPLES
		 *
		 * wp media webp-convert
		 *
		 * @when after_wp_load
		 */
		function __invoke( $args, $assoc_args ) {
			$util = new WPIS_WebP_Converter();
			$util->process_images();
			WP_CLI::success( $util->print_stats() );
		}
	}

	WP_CLI::add_command( 'images webp-convert', 'WPIS_WebP_Convert_Command' );
}
