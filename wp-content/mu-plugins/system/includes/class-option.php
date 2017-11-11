<?php

namespace WAPaaS\MWP;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

final class Option {

	/**
	 * System option name.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const NAME = 'mwp_system';

	/**
	 * List all system options.
	 *
	 * @since 1.0.0
	 *
	 * @param  bool   $default
	 *
	 * @return array
	 */
	public static function get_all() {

		$all = json_decode( get_option( self::NAME, '[]' ), true );

		return is_array( $all ) ? $all : [];

	}

	/**
	 * Return a system option.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $name
	 * @param  bool   $default (optional)
	 *
	 * @return mixed|false
	 */
	public static function get( $name, $default = false ) {

		$all = self::get_all();

		return array_key_exists( $name, $all ) ? $all[ $name ] : $default;

	}

	/**
	 * Update a system option.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $name
	 * @param  mixed  $value
	 *
	 * @return bool
	 */
	public static function update( $name, $value ) {

		$all = self::get_all();

		$all[ $name ] = $value;

		return update_option( self::NAME, wp_json_encode( $all ), true );

	}

	/**
	 * Delete a system option.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $name
	 *
	 * @return bool
	 */
	public static function delete( $name ) {

		$all = self::get_all();

		unset( $all[ $name ] );

		return update_option( self::NAME, wp_json_encode( $all ), true );

	}

}
