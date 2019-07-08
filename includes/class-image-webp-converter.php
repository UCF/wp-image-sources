<?php
/**
 * WebP Converter
 */
if ( ! class_exists( 'WPIS_WebP_Converter' ) ) {
	class WPIS_WebP_Converter {
		public
			$total_attachments = 0,
			$image_attachments = 0,
			$images_converted = 0,
			$images_failed = 0;

		private
			$attachments = array();

		/**
		 * Runs the converter
		 * @author Jim Barnes
		 * @since 1.0.0
		 */
		public function process_images() {
			$args = array(
				'post_type'   => 'attachment',
				'numberposts' => -1,
				'post_status' => 'any'
			);

			$attachments = get_posts( $args );

			$this->total_attachments = count( $attachments );

			if ( $attachments ) {
				foreach( $attachments as $attachment ) {
					if ( wp_attachment_is_image( $attachment->ID ) ) {
						$this->attachments[] = $attachment->ID;
					}
				}
			}

			$this->image_attachments = count( $this->attachments );

			$progress = \WP_CLI\Utils\make_progress_bar( 'Converting images...', $this->image_attachments);

			foreach( $this->attachments as $a_id ) {
				$util = new WPIS_Convert_Attachment( $a_id );
				$status = $util->convert();

				if ( wpis_is_convert_error( $status ) ) {
					if ( WP_DEBUG === true ) {
						error_log( $status->get_error_string() );
					}

					$this->images_failed++;
				} else {
					$this->images_converted++;
				}

				$progress->tick();
			}
		}

		public function print_stats() {
			return
"
Total Attachments Found      : $this->total_attachments

Image Attachments Found      : $this->image_attachments
Successful Image Convertions : $this->images_converted
Failed Image Conversions     : $this->images_failed
";
		}
	}
}
