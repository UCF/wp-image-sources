<?php
/**
 * Image utility for pulling images from content
 * and adding/removing classes.
 */
if ( ! class_exists( 'WPIS_Image_Utility' ) ) {
	class WPIS_Image_Utility {
		public
			$attachment_id,
			$original;

		private
			$class_match = '',
			$classes = array(),
			$img = '',
			$src = '';

		/**
		 * Constructs a new instance of WPIS_Image_Utility
		 * @author Jim Barnes
		 * @since 1.0.0
		 * @param string $content The post content.
		 */
		public function __construct( $img ) {
			$this->img      = $img;
			$this->original = $img;

			$this->parse_image();
			$this->find_attachment();
		}

		/**
		 * Returns the modified image
		 * @author Jim Barnes
		 * @since 1.0.0
		 * @return string
		 */
		public function get_modified() {
			if ( $this->attachment_id ) {
				$this->classes[] = "wp-image-{$this->attachment_id}";
			}

			$class_string = implode( ' ', array_unique( $this->classes ) );

			if ( ! empty( $this->class_match ) ) {
				$this->img = str_replace( $this->class_match, $class_string, $this->img );
			} else {
				if ( count( $this->classes ) > 0 ) {
					$prepend_string = '<img class="' . $class_string . '"';
					$tmp_img = str_replace( '<img ', $prepend_string, $this->img );
					$this->img = $tmp_img;
				}
			}

			return $this->img;
		}

		public function can_update() {
			if ( $this->attachment_id ) {
				return true;
			} else {
				return false;
			}
		}

		/**
		 * Parses the image string.
		 * @author Jim Barnes
		 * @since 1.0.0
		 */
		private function parse_image() {
			$class_pattern = '/class="([\s\w\d-_]*)"/';
			$src_pattern = '/src="([\s\w\d-_:\/\.]*)"/';

			$class = '';
			$src = '';

			preg_match( $class_pattern, $this->img, $class );
			preg_match( $src_pattern, $this->img, $src );

			if ( count( $class ) > 1 ) {
				$this->class_match = $class[1];
				$this->classes = explode( ' ', $class[1] );
			}

			if ( count( $src ) > 1 ) {
				$this->src = $src[1];

				if ( substr( $this->src, 0, 2 ) === '//' ) {
					$this->src = 'https:' . $this->src;
				}
			}
		}

		/**
		 * Searches attachments for the src
		 * @author Jim Barnes
		 * @since 1.0.0
		 */
		private function find_attachment() {
			if ( $this->src ) {
				$attachment_id = attachment_url_to_postid( $this->src );
			} else {
				$attachment_id = null;
			}

			if ( $attachment_id ) {
				$this->attachment_id = $attachment_id;
			} else {
				$this->attachment_id = null;
			}
		}
	}
}
