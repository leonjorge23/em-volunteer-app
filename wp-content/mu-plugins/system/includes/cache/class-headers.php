<?php

namespace WAPaaS\MWP\Cache;

use WAPaaS\MWP\SSO;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

final class Headers {

	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		add_action( 'init', [ $this, 'remove_extra_cookies' ] );

		add_filter( 'wp_headers', [ $this, 'wp_headers' ], PHP_INT_MAX );

	}

	/**
	 * Remove extra cookies when not needed.
	 *
	 * Cookies cause the HTTP cache to miss, since the test cookie
	 * is only used by the login page we'll make sure it's removed
	 * when it isn't needed, along with a few others that can have
	 * a tendency to stick around even when logged out.
	 *
	 * @action init
	 * @since  1.0.0
	 */
	public function remove_extra_cookies() {

		if ( is_user_logged_in() ) {

			return;

		}

		$cookies  = array_keys( $_COOKIE );
		$is_login = ( 'wp-login.php' === basename( $_SERVER['SCRIPT_FILENAME'] ) );

		if ( in_array( TEST_COOKIE, $cookies, true ) && ! $is_login ) {

			setcookie( TEST_COOKIE, null, 0, SITECOOKIEPATH, COOKIE_DOMAIN, is_ssl() );

		}

		if ( $results = preg_grep( '/^wp-settings-\d+/', $cookies ) ) {

			setcookie( $results[0], null, 0, SITECOOKIEPATH, COOKIE_DOMAIN, is_ssl() );

		}

		if ( $results = preg_grep( '/^wp-settings-time-\d+/', $cookies ) ) {

			setcookie( $results[0], null, 0, SITECOOKIEPATH, COOKIE_DOMAIN, is_ssl() );

		}

	}

	/**
	 * Set custom headers for HTTP cache.
	 *
	 * @filter wp_headers - PHP_INT_MAX
	 * @since  1.0.0
	 *
	 * @param  array $headers
	 *
	 * @return array
	 */
	public function wp_headers( $headers ) {

		$nocache_headers = wp_get_nocache_headers();

		if ( isset( $_GET['nocache'] ) ) { // WPCS: CSRF ok.

			return array_merge( $headers, $nocache_headers );

		}

		$nocache_headers_present = array_filter( $headers, function ( $value, $header ) use ( $nocache_headers ) {

			return ( isset( $nocache_headers[ $header ] ) && $nocache_headers[ $header ] === $value );

		}, ARRAY_FILTER_USE_BOTH );

		if ( ! $nocache_headers_present ) {

			/**
			 * Max-age header value for HTTP cache (in seconds).
			 *
			 * @since 1.0.0
			 *
			 * @param array $headers
			 *
			 * @var int
			 */
			$max_age = (int) apply_filters( 'mwp_system_cache_headers_max_age', WEEK_IN_SECONDS, $headers );

			$headers['Expires']       = gmdate( 'D, d M Y H:i:s \G\M\T', time() + $max_age ); // Required to override `mod_expires` defaults in Apache.
			$headers['Cache-Control'] = sprintf( 'max-age=%d', $max_age );

		}

		return $headers;

	}

}
