<?php
/**
 * General plugin functions
 *
 * @package safe-redirect-manager
 */

/**
 * Get valid HTTP status codes
 *
 * @return array
 * @since  1.8
 */
function teamone_redirect_srm_get_valid_status_codes() {
	return apply_filters( 'srm_valid_status_codes',
		teamone_redirect_srm_var_to_string( teamone_redirect_srm_get_valid_status_codes_data() ) );
}

/**
 * Get valid HTTP status codes and their labels.
 *
 * @return array
 * @since  2.0.0
 */
function teamone_redirect_srm_get_valid_status_codes_data() {
	$status_codes = array(
		301 => esc_html__( 'Moved Permanently', 'safe-redirect-manager' ),
		3001 => esc_html__( 'Url Rewriting', 'safe-redirect-manager' ),
//		302 => esc_html__( 'Found', 'safe-redirect-manager' ),
//		303 => esc_html__( 'See Other', 'safe-redirect-manager' ),
//		307 => esc_html__( 'Temporary Redirect', 'safe-redirect-manager' ),
//		403 => esc_html__( 'Forbidden', 'safe-redirect-manager' ),
//		404 => esc_html__( 'Not Found', 'safe-redirect-manager' ),
//		410 => esc_html__( 'Gone', 'safe-redirect-manager' ),
	);

	$additional_status_codes = apply_filters(
		'srm_additional_status_codes',
		array()
	);

	return $status_codes + $additional_status_codes;
}


/**
 * Sanitize redirect to path
 *
 * The only difference between this function and just calling esc_url_raw is
 * esc_url_raw( 'test' ) == 'http://test', whereas sanitize_redirect_path( 'test' ) == '/test'
 *
 * @param string $path Path to sanitize
 *
 * @return string
 * @since 1.8
 */
function teamone_redirect_srm_sanitize_redirect_to( $path ) {
	$path = trim( $path );

	if ( preg_match( '/^www\./i', $path ) ) {
		$path = 'http://' . $path;
	}

	if ( ! preg_match( '/^https?:\/\//i', $path ) ) {
		if ( strpos( $path, '/' ) !== 0 ) {
			$path = '/' . $path;
		}
	}

	return esc_url_raw( $path );
}

/**
 * Sanitize redirect from path
 *
 * @param string $path Path to sanitize
 * @param boolean $allow_regex Whether to allow regex
 *
 * @return string
 * @since 1.8
 */
function teamone_redirect_srm_sanitize_redirect_from( $path, $allow_regex = false ) {
	$path = trim( $path );

	if ( empty( $path ) ) {
		return '';
	}

	// dont accept paths starting with a .
	if ( ! $allow_regex && strpos( $path, '.' ) === 0 ) {
		return '';
	}

	// turn path in to absolute
	if ( preg_match( '/https?:\/\//i', $path ) ) {
		$path = preg_replace( '/^(http:\/\/|https:\/\/)(www\.)?[^\/?]+\/?(.*)/i', '/$3', $path );
	} elseif ( ! $allow_regex && strpos( $path, '/' ) !== 0 ) {
		$path = '/' . $path;
	}

	// the @ symbol will break our regex engine
	$path = str_replace( '@', '', $path );

	return $path;
}


/**
 * 变量类型转换（string）
 *
 * @param $var
 *
 * @return array|string
 */
function teamone_redirect_srm_var_to_string( $var ) {
	if ( is_array( $var ) || is_object( $var ) ) {
		$string = array();
		foreach ( $var as $key => $item ) {
			array_push( $string, strval( $key ) );
		}
	} else {
		$string = strval( $var );
	}

	return $string;
}