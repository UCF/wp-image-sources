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
			$metadata = array(),
			$custom_meta = array();

		private
			$images = array();

		/**
		 * Constructs a new instance of WPIS_Convert_Attachment
		 * @author Jim Barnes
		 * @since 1.0.0
		 * @param int $attachment_id The ID of the attachment to convert
		 * @param array $metadata The array of metadata for the attachment
		 */
		public function __construct( $attachment_id, $metadata = array() ) {
			$this->attachment_id = $attachment_id;
			$this->metadata = $metadata;

			$this->fill_images_array();
		}

		/**
		 * Converts the attachment images to the webp format.
		 * @author Jim Barnes
		 * @since 1.0.0
		 * @return bool|WPIS_Convert_Error Returns true if all attachments were converted, or an error if not.
		 */
		public function convert() {
			$error = new WPIS_Convert_Error();

			$pattern = '/(.*)(\.jpg|\.jpeg|\.png)$/';
			$replacement = '$1.webp';

			$this->custom_meta = $this->metadata;
			$this->custom_meta['sizes'] = array();
			$this->custom_meta['file'] = preg_replace( $pattern, $replacement, $this->metadata['file'] );

			foreach( $this->images as $size => $detail ) {

				$destination = preg_replace( $pattern, $replacement, $detail['path'] );

				// If the path and destination is the same
				// it's not a compatible file type.
				if ( $detail['path'] === $destination ) continue;

				try {
					WebPConvert::convert( $detail['path'], $destination, array(
						'quality' => 70
					) );

					$this->custom_meta['sizes'][$size] = array(
						'width'     => $detail['width'],
						'height'    => $detail['height'],
						'mime-type' => 'image/webp',
						'file'      => basename( $destination )
					);
				} catch ( Exception $e ) {
					$filename = basename( $destination );
					$error->add_error( "Unable to convert $filename to webp." );
				}
			}

			update_post_meta( $this->attachment_id, 'has_webp', true );
			update_post_meta( $this->attachment_id, '_wp_webp_metadata', $this->custom_meta );

			// If the error count is greater than one, return the error object.
			if ( $error->error_count > 0 ) {
				return $error;
			}

			// Return true if there weren't any errors.
			return true;
		}

		/**
		 * Deletes additional files
		 * @author Jim Barnes
		 * @since 1.0.0
		 */
		public function delete_attachment() {
			$pattern = '/(.*)(\.jpg|\.jpeg|\.png)$/';
			$replacement = '$1.webp';

			if ( isset( $this->images['fullsize'] ) && isset( $this->images['fullsize']['path'] ) ) {
				$full_path = preg_replace( $pattern, $replacement, $this->images['fullsize']['path'] );
				wp_delete_file( $full_path );
			}

			foreach( $this->images as $size => $detail ) {
				$file_path = preg_replace( $pattern, $replacement, $detail['path'] );
				wp_delete_file( $file_path );
			}

			delete_post_meta( $this->attachment_id, '_wp_webp_metadata' );
		}

		private function fill_images_array() {
			$this->metadata = ! empty( $this->metadata ) ? $this->metadata : wp_get_attachment_metadata( $this->attachment_id );
			$full_path = get_attached_file( $this->attachment_id );

			$dir = dirname( $full_path );

			$this->images['fullsize'] = array(
				'path'   => $full_path,
				'width'  => $this->metadata['width'],
				'height' => $this->metadata['height']
			);

			foreach( $this->metadata['sizes'] as $size => $details ) {
				$this->images[$size] = array(
					'path'   => $dir . '/' . $details['file'],
					'width'  => $details['width'],
					'height' => $details['height']
				);
			}
		}
	}
}
