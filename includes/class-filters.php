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

		/**
		 * Filter that runs when an attachment is deleted
		 * @author Jim Barnes
		 * @since 1.0.0
		 * @param int $attachment_id The attachment id
		 */
		public static function wpis_delete_attachment( $attachment_id ) {
			if ( wp_attachment_is_image( $attachment_id ) ) {
				$util = new WPIS_Convert_Attachment( $attachment_id );
				$util->delete_attachment();
			}
		}

		/**
		 * Custom function for making responsive images
		 * @author Jim Barnes
		 * @since 1.0.0
		 * @param string $content The content
		 * @return string The filtered content
		 */
		public static function wpis_make_content_images_responsive( $content ) {
			preg_match_all( '/<img[\s]*[^<]*>(?!<noscript>|<\/noscript>)/is', $content, $matches );

			$selected_images = [];
			$attachment_ids = [];

			if ( count( $matches[0] ) ) {
				foreach( $matches[0] as $image ) {
					$escaped = preg_replace( '/([\\\^$.[\]|()?*+{}\/~-])/', '\\\\$0', $image );
					if ( preg_match( '~<picture[^>]*>(?:[\s]*<[\s]*[^<]*\/?>[\s]*)*(?:' . $escaped . ')(?:[\s]*<[\s]*[^<]*\/?>[\s]*)*[\s]*<\/picture>~', $content, $res ) ) {
						continue;
					}

					if ( false === strpos( $image, ' srcset=' ) && preg_match( '/wp-image-([0-9]+)/i', $image, $class_id ) ) {
						$attachment_id = absint( $class_id[1] );
						if ( $attachment_id ) {
							/*
							 * If exactly the same image tag is used more than once, overwrite it.
							 * All identical tags will be replaced later with 'str_replace()'.
							 */
							$selected_images[ $image ] = $attachment_id;
							// Overwrite the ID when the same image is included more than once.
							$attachment_ids[ $attachment_id ] = true;
						}
					}
				}

				if ( count( $attachment_ids ) > 1 ) {
					/*
					 * Warm the object cache with post and meta information for all found
					 * images to avoid making individual database calls.
					 */
					_prime_post_caches( array_keys( $attachment_ids ), false, true );
				}

				foreach ( $selected_images as $image => $attachment_id ) {
					$image_meta = wp_get_attachment_metadata( $attachment_id );
					$content    = str_replace( $image, wp_image_add_srcset_and_sizes( $image, $image_meta, $attachment_id ), $content );
				}
			}

			return $content;
		}
	}
}
