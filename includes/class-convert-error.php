<?php
/**
 * Class for storing conversion errors
 */
if ( ! class_exists( 'WPIS_Convert_Error' ) ) {
	class WPIS_Convert_Error {
		public
			$message,
			$errors,
			$error_count;

		/**
		 * Constructs a new instance of WPIS_Convert_Error
		 * @author Jim Barnes
		 * @since 1.0.0
		 * @param string $message The error message to display
		 * @param array $error An array of errors to display
		 */
		public function __construct( $message = 'There was an error converting the attachment images.', $errors=array() ) {
			// Set general error message
			$this->message = $message;
			$this->errors = $errors;
			$this->error_count = count( $this->errors );
		}

		/**
		 * Adds an error to display for debugging
		 * @author Jim Barnes
		 * @since 1.0.0
		 * @param string $message The error message to display
		 */
		public function add_error( $message ) {
			if ( ! empty( $message ) ) {
				$this->errors[] = $message;
				$this->error_count++;
			}
		}

		/**
		 * Removes an error based on error index
		 * @author Jim Barnes
		 * @since 1.0.0
		 * @param int $i The index of the error to remove.
		 */
		public function remove_error( $i ) {
			if ( is_int( $i ) ) {
				unset( $this->errors[$i] );
				$this->error_count--;
			}
		}

		/**
		 * Public function used for printing the error array
		 * @author Jim Barnes
		 * @since 1.0.0
		 */
		public function get_error_string() {
			$message = $this->message;

			foreach( $this->errors as $i => $error ) {
				$message .= "[$i]: $error\n";
			}

			return $message;
		}
	}
}
