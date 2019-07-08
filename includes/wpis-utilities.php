<?php
/**
 * Utility functions
 */
if ( ! function_exists( 'wpis_is_convert_error' ) ) {
	function wpis_is_convert_error( $obj ) {
		if ( is_bool( $obj ) ) {
			return false;
		} else if ( get_class( $obj ) === 'WPIS_Convert_Error' ) {
			return true;
		}

		return false;
	}
}
