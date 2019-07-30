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
					$image_str = wp_image_add_srcset_and_sizes( $image, $image_meta, $attachment_id );

					$source = '<source>';
					$webp_meta = get_post_meta( $attachment_id, '_wp_webp_metadata', true );
					$webp_str = WPIS_Filters::wpis_source_add_srcset_and_sizes( $image, $webp_meta, $attachment_id );

					$full_string = $image_str;

					if ( $webp_str !== '' ) {
						$full_string = '<picture>' . $webp_str . $full_string . '</picture>';
					}

					$content    = str_replace( $image, $full_string, $content );
				}
			}

			return $content;
		}

		public static function wpis_source_add_srcset_and_sizes( $image, $image_meta, $attachment_id ) {
			// Ensure the image meta exists.
			if ( empty( $image_meta['sizes'] ) ) {
				return $image;
			}

			$image_src         = preg_match( '/src="([^"]+)"/', $image, $match_src ) ? $match_src[1] : '';
			list( $image_src ) = explode( '?', $image_src );

			// Return early if we couldn't get the image source.
			if ( ! $image_src ) {
				return $image;
			}

			// Bail early if an image has been inserted and later edited.
			if ( preg_match( '/-e[0-9]{13}/', $image_meta['file'], $img_edit_hash ) &&
				strpos( wp_basename( $image_src ), $img_edit_hash[0] ) === false ) {

				return $image;
			}

			$width  = preg_match( '/ width="([0-9]+)"/', $image, $match_width ) ? (int) $match_width[1] : 0;
			$height = preg_match( '/ height="([0-9]+)"/', $image, $match_height ) ? (int) $match_height[1] : 0;

			if ( ! $width || ! $height ) {
				/*
				* If attempts to parse the size value failed, attempt to use the image meta data to match
				* the image file name from 'src' against the available sizes for an attachment.
				*/
				$image_filename = str_replace( array( '.png', '.jpg', '.jpeg' ), '.webp', wp_basename( $image_src ) );

				if ( $image_filename === wp_basename( $image_meta['file'] ) ) {
					$width  = (int) $image_meta['width'];
					$height = (int) $image_meta['height'];
				} else {
					foreach ( $image_meta['sizes'] as $image_size_data ) {
						if ( $image_filename === $image_size_data['file'] ) {
							$width  = (int) $image_size_data['width'];
							$height = (int) $image_size_data['height'];
							break;
						}
					}
				}
			}

			if ( ! $width || ! $height ) {
				return $image;
			}

			$size_array = array( $width, $height );
			$image_src  = str_replace( array( '.png', '.jpg', 'jpeg' ), '.webp', $image_src );
			$srcset     = wp_calculate_image_srcset( $size_array, $image_src, $image_meta, $attachment_id );

			if ( $srcset ) {
				// Check if there is already a 'sizes' attribute.
				$sizes = strpos( $image, ' sizes=' );

				if ( ! $sizes ) {
					$sizes = wp_calculate_image_sizes( $size_array, $image_src, $image_meta, $attachment_id );
				}
			}

			if ( $srcset && $sizes ) {
				// Format the 'srcset' and 'sizes' string and escape attributes.
				$attr = sprintf( ' srcset="%s"', esc_attr( $srcset ) );

				if ( is_string( $sizes ) ) {
					$attr .= sprintf( ' sizes="%s"', esc_attr( $sizes ) );
				}

				// Add 'srcset' and 'sizes' attributes to the image markup.
				$image = preg_replace( '/<img ([^>]+?)[\/ ]*>/', '<source' . $attr . '>', $image );
			}

			return $image;
		}
	}
}
