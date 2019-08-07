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
			preg_match_all( '/<img[^<]*(?:wp-image-\d+)[^<]*\/?>(?!<noscript>|<\/noscript>)/is', $content, $matches );

			$selected_images = [];
			$attachment_ids = [];

			if ( count( $matches[0] ) ) {
				foreach( $matches[0] as $image ) {
					$escaped = preg_replace( '/([\\\^$.[\]|()?*+{}\/~-])/', '\\\\$0', $image );
					if ( preg_match( '~<picture[^>]*>(?:[\s]*<[\s]*[^<]*\/?>[\s]*)*(?:' . $escaped . ')(?:[\s]*<[\s]*[^<]*\/?>[\s]*)*[\s]*<\/picture>~', $content, $res ) ) {
						continue;
					}

					if ( false === strpos( $image, 'srcset=' ) && preg_match( '/wp-image-([0-9]+)/i', $image, $class_id ) ) {
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
					if ( ! wp_attachment_is_image( $attachment_id ) ) {
						continue;
					}


					$image_meta = wp_get_attachment_metadata( $attachment_id );
					$image_str = wp_image_add_srcset_and_sizes( $image, $image_meta, $attachment_id );

					$webp_meta = get_post_meta( $attachment_id, '_wp_webp_metadata', true );
					$webp_str = WPIS_Filters::wpis_source_add_srcset_and_sizes( $image, $webp_meta, $attachment_id );

					$full_string = $image_str;

					if ( $webp_str !== '' ) {
						$full_string = '<picture>' . $webp_str . $full_string . '</picture>';
					}

					$content = str_replace( $image, $full_string, $content );
				}
			}

			return $content;
		}

		/**
		 * Functions that adds srcset and sizes attributes to source elements
		 * @author Jim Barnes
		 * @since 1.0.0
		 * @param string $image The image markup
		 * @param array $image_meta The meta array
		 * @param int $attachment_id The attachment_id
		 * @return string
		 */
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

		/**
		 * Overrides for image_downsize function to return webp
		 * @author Jim Barnes
		 * @since 1.0.0
		 * @param bool         $downsize Whether to short-circuit the image downsize. Default false.
		 * @param int          $id       Attachment ID for image.
		 * @param array|string $size     Size of image. Image size or array of width and height values (in that order).
		 *                               Default 'medium'.
		 */
		public static function wpis_image_downsize( $downsize, $attachment_id, $size = 'thumbnail' ) {
			if ( $downsize ) {
				return false;
			}

			if ( ! strpos( $size, '-webp' ) ) {
				return $downsize;
			}

			$std_size         = str_replace( '-webp', '', $size );
			$is_image         = wp_attachment_is_image( $attachment_id );
			$img_url          = wp_get_attachment_url( $attachment_id );
			$meta             = get_post_meta( $attachment_id, '_wp_webp_metadata', true );
			$width            = $height = 0;
			$is_intermediate  = false;
			$img_url_basename = wp_basename( $img_url );

			if ( $intermediate = self::wpis_image_get_intermediate_size( null, $attachment_id, $size ) ) {
				$img_url = str_replace( $img_url_basename, $intermediate['file'], $img_url );
				$width   = $intermediate['width'];
				$height  = $intermediate['height'];
			}

			if ( ! $width && ! $height && isset( $meta['width'] ) && isset( $meta['height'] ) ) {
				$width  = $meta['width'];
				$height = $meta['height'];
			}

			if ( $img_url ) {
				list( $width, $height ) = image_constrain_size_for_editor( $width, $height, $std_size );
				return array( $img_url, $width, $height, $is_intermediate );
			}

			return false;

		}

		/**
		 * Custom filter for returning webp sources
		 * @author Jim Barnes
		 * @since 1.0.0
		 * @param array        $data    Array of file relative path, width, and height on success. May also include
		 *                              file absolute path and URL.
		 * @param int          $post_id The post_id of the image attachment
		 * @param string|array $size    Registered image size or flat array of initially-requested height and width
		 *                              dimensions (in that order).
		 * @return false|array Returns an array (url, width, height, is_intermediate), or false, if no image is available.
		 */
		public static function wpis_image_get_intermediate_size( $data, $post_id, $size = 'thumbnail' ) {
			// Return early if this isn't a webp image size being requested.
			if ( ! strpos( $size, '-webp' ) ) {
				return $data;
			}

			// Returns out if webp size doesn't exist.
			if ( ! $size || ! is_array( $image_meta = get_post_meta( $post_id, '_wp_webp_metadata', true ) ) || empty( $image_meta ) ) {
				return false;
			}

			$std_size = str_replace( '-webp', '', $size );
			$data     = array();

			if ( is_array( $size ) ) {
				$candidates = array();

				if ( ! isset( $image_meta['file'] ) && isset( $image_meta['sizes']['fullsize'] ) ) {
					$image_meta['height'] = $image_meta['sizes']['full']['height'];
					$image_meta['width'] = $image_meta['sizes']['full']['width'];
				}

				foreach( $image_meta['sizes'] as $_size => $data ) {
					if ( $data['width'] == $size[0] && $data['height'] == $size[1] ) {
						$candidates[ $data['width'] * $data['height'] ] = $data;
						break;
					}

					if ( $data['width'] >= $size[0] && $data['height'] >= $size[1] ) {
						if ( 0 === $size[0] || 0 === $size[1] ) {
							$same_ratio = wp_image_matches_ratio( $data['width'], $data['height'], $image_meta['width'], $image_meta['height'] );
						} else {
							$same_ratio = wp_image_matches_ratio( $data['width'], $data['height'], $size[0], $size[1] );
						}

						if ( $same_ratio ) {
							$candidates[ $data['width'] * $data['height'] ] = $data;
						}
					}
				}

				if ( ! empty( $candidates ) ) {
					if ( 1 < count( $candidates ) ) {
						ksort( $candidates );
					}

					$data = array_shift( $candidates );
				} elseif ( ! empty( $image_meta['sizes']['thumbnail'] ) && $image_meta['sizes']['thumbnail']['width'] >= $size[0] && $image_meta['sizes']['thumbnail']['width'] >= $size[1] ) {
					$data = $image_meta['sizes']['thumbnail'];
				} else {
					return false;
				}

				list( $data['width'], $data['height'] ) = image_constrain_size_for_editor( $data['width'], $data['height'], $size );
			} elseif ( ! empty( $image_meta['sizes'][$std_size] ) ) {
				$data = $image_meta['sizes'][$std_size];
			}

			if ( empty( $data ) ) {
				return false;
			}

			if ( empty( $data['path'] ) && ! empty( $data['file'] ) && ! empty( $image_meta['file'] ) ) {
				$file_url     = wp_get_attachment_url( $post_id );
				$data['path'] = path_join( dirname( $image_meta['file'] ), $data['file'] );
				$data['url']  = path_join( dirname( $file_url ), $data['file'] );
			}

			return $data;
		}
	}
}
