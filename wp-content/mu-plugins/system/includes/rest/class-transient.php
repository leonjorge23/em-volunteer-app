<?php

namespace WAPaaS\MWP\REST;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

final class Transient {

	/**
	 * Transient key for route responses.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const KEY = 'mwp_system_rest';

	/**
	 * Return a route response transient value.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $route
	 *
	 * @return array
	 */
	public static function get( $route ) {

		$data = get_site_transient( self::KEY );

		if ( false === $data || ! isset( $data[ $route ] ) ) {

			return false;

		}

		return (array) $data[ $route ];

	}

	/**
	 * Set a route response transient value.
	 *
	 * @since 1.0.0
	 *
	 * @param  array  $value
	 * @param  string $route
	 * @param  int    $expiration (optional)
	 *
	 * @return bool
	 */
	public static function set( $value, $route, $expiration = HOUR_IN_SECONDS ) {

		$data = array_filter( (array) get_site_transient( self::KEY ) );

		$data[ $route ] = $value;

		return set_site_transient( self::KEY, $data, $expiration );

	}

	/**
	 * Delete a route response transient value.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $route
	 *
	 * @return bool
	 */
	public static function delete( $route ) {

		$data = array_filter( (array) get_site_transient( self::KEY ) );

		if ( ! isset( $data[ $route ] ) ) {

			return false;

		}

		unset( $data[ $route ] );

		return set_site_transient( self::KEY, $data, self::get_expiration() );

	}

	/**
	 * Get the current transient expiration.
	 *
	 * @since 1.0.0
	 *
	 * @return int
	 */
	public static function get_expiration() {

		global $wpdb;

		$timeout = (int) $wpdb->get_var(
			$wpdb->prepare(
				"SELECT `option_value` FROM `{$wpdb->options}` WHERE `option_name` = %s;",
				'_site_transient_timeout_' . self::KEY
			)
		);

		return ( $timeout > time() ) ? $timeout - time() : HOUR_IN_SECONDS;

	}

}
