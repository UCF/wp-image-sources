<?php
/**
 * All filters are handled here
 */
if ( ! class_exists( 'WPIS_Filters' ) ) {
	/**
	 * Class used for storing filters
	 */
	class WPIS_Filters {
		/**
		 * Filter that runs when attachment metadata is generated.
		 * @author Jim Barnes
		 * @since 1.0.0
		 * @param array $metadata The attachment metadata
		 * @param array $attachment_id The attachment id
		 */
		public static function wpis_generate_attachment_metadata( $metadata, $attachment_id ) {
			if ( wp_attachment_is_image( $attachment_id ) ) {
				$util = new WPIS_Convert_Attachment( $attachment_id, $metadata );
				$util->convert();
			}

			return $metadata;
		}
	}
}
