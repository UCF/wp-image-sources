<?php
/**
 * Helper class for adding image classes
 */
if ( ! class_exists( 'WPIS_Image_Processor' ) ) {
	class WPIS_Image_Processor {
		private
			$all_post_types = true,
			$post_types = array(),
			$posts_processed = 0,
			$images_processed = 0,
			$posts_updated = 0,
			$posts_skipped = 0,
			$images_updated = 0,
			$images_skipped = 0;

		/**
		 * Constructs a new instance of WPIS_Image_Processor
		 * @author Jim Barnes
		 * @since 1.0.0
		 */
		public function __construct( $post_types = array() ) {
			if ( is_array( $post_types ) && count( $post_types ) > 0 ) {
				$this->post_types = $post_types;
			} else {
				$this->post_types = array_keys( get_post_types( array(
					'public' => true
				), 'names' ) );
			}
		}

		public function print_stats() {
			return
"
Finished Processing Images

Posts Processed  : {$this->posts_processed}
Posts Updated    : {$this->posts_updated}
Posts Skipped    : {$this->posts_skipped}

Images Processed : {$this->images_processed}
Images Updated   : {$this->images_updated}
Images Skipped   : {$this->images_skipped}
";
		}

		/**
		 * Processes the images in all the posts
		 * of the specified post types
		 * @author Jim Barnes
		 * @since 1.0.0
		 */
		public function process_images() {
			global $wpdb;

			$args = array(
				'post_type'      => $this->post_types,
				'posts_per_page' => -1,
				'post_status'    => 'any',
				's'              => '<img'
			);

			$posts = get_posts( $args );

			$this->posts_processed = count( $posts );

			$progress = \WP_CLI\Utils\make_progress_bar( 'Processing post images...', $this->posts_processed );

			foreach( $posts as $post ) {
				$content = $post->post_content;
				preg_match_all( '/<img [^>]+>/', $post->post_content, $matches );

				foreach( $matches[0] as $image ) {
					$this->images_processed++;

					$util = new WPIS_Image_Utility( $image );

					if ( $util->can_update() ) {
						$mod = $util->get_modified();
						if ( $mod !== $image ) {
							$content = str_replace( $image, $mod, $content );
							$this->images_updated++;
						} else {
							$this->images_skipped++;
						}
					} else {
						$this->images_skipped++;
					}
				}

				if ( $post->post_content !== $content ) {
					$update_status = $wpdb->update( $wpdb->posts, array( 'post_content' => $content ), array( 'ID' => $post->ID ) );

					if ( $update_status !== false ) {
						$this->posts_updated++;
					} else {
						$this->posts_skipped++;
					}
				}

				$progress->tick();
			}
		}
	}
}
