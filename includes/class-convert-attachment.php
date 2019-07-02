<?php
/**
 * Class for converting a single attachment to WebP
 */

require WPIS__VENDOR_DIR . '/autoload.php';

use WebPConvert\WebPConvert;

if ( ! class_exists( 'WPIS_Convert_Attachment' ) ) {
	class WPIS_Convert_Attachment {
		public
			$attachment_id,
			$metadata = array();

		private
			$images = array();

		public function __construct( $attachment_id, $metadata = array() ) {
			$this->attachment_id = $attachment_id;
			$this->metadata = $metadata;

			$this->fill_images_array();
		}

		public function convert() {
			$pattern = '/(.*)(\.jpg|\.jpeg|\.png)/';
			$replacement = '$1.webp';

			foreach( $this->images as $size => $detail ) {
				$destination = preg_replace( $pattern, $replacement, $detail['path'] );

				try {
					WebPConvert::convert( $detail['path'], $destination, array(
						'quality' => 70
					) );

					update_post_meta( $this->attachment_id, 'has_webp', true );
				} catch ( Exception $e ) {
					throw new Exception("Failed to convert file.");
				}
			}
		}

		private function fill_images_array() {
			$meta = ! empty( $this->metadata ) ? $this->metadata : wp_get_attachment_metadata( $this->attachment_id );
			$full_path = get_attached_file( $this->attachment_id );

			$dir = dirname( $full_path );

			$this->images['fullsize'] = array(
				'path'   => $full_path,
				'width'  => $meta['width'],
				'height' => $meta['height']
			);

			foreach( $meta['sizes'] as $size => $details ) {
				$this->images[$size] = array(
					'path'   => $dir . '/' . $details['file'],
					'width'  => $details['width'],
					'height' => $details['height']
				);
			}
		}
	}
}
