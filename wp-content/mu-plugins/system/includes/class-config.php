<?php

namespace WAPaaS\MWP;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

final class Config {

	/**
	 * Object cache group name.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const CACHE_GROUP = 'mwp_system';

	/**
	 * Default list of keys that should never be cached.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	const NOCACHE = [ 'db_host', 'db_name', 'db_password', 'db_port', 'db_user', 'shopper_id', 'site_token' ];

	/**
	 * Return the config file path.
	 *
	 * @since 1.0.0
	 *
	 * @return string|false
	 */
	private static function path() {

		$paths = [
			'/site/private/site.json',
			WP_CONTENT_DIR . '/config-local.json',
		];

		foreach ( $paths as $path ) {

			if ( is_readable( $path ) ) {

				return $path;

			}

		}

		return false;

	}

	/**
	 * Fetch all config values (not cached).
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	private static function fetch() {

		return ( $path = self::path() ) ? (array) json_decode( file_get_contents( $path ), true ) : [];

	}

	/**
	 * Return the list of keys that (for security purposes) should never be cached.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	private static function nocache() {

		/**
		 * Filter the list of keys that (for security purposes) should never be cached.
		 *
		 * @since 1.0.0
		 *
		 * @var array
		 */
		$keys = (array) apply_filters( 'mwp_system_config_nocache', self::NOCACHE );

		return array_unique( $keys );

	}

	/**
	 * Check if the config exists.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $key
	 *
	 * @return bool
	 */
	public static function exists( $key ) {

		$values = self::fetch();

		return array_key_exists( $key, $values );

	}

	/**
	 * Return a config value (cached).
	 *
	 * @since 1.0.0
	 *
	 * @param  string $key
	 * @param  mixed  $default (optional)
	 * @param  bool   $force   (optional)
	 *
	 * @return mixed|false
	 */
	public static function get( $key, $default = false, $force = false ) {

		$nocache = in_array( $key, self::nocache(), true );

		if ( WP_DEBUG || $nocache || $force || false === ( $values = wp_cache_get( 'config', self::CACHE_GROUP ) ) ) {

			$values = self::fetch();

			if ( ! $nocache ) {

				$cacheable = array_diff_key( $values, array_flip( self::nocache() ) );

				wp_cache_add( 'config', $cacheable, self::CACHE_GROUP, DAY_IN_SECONDS );

			}

		}

		return array_key_exists( $key, $values ) ? $values[ $key ] : $default;

	}

}
