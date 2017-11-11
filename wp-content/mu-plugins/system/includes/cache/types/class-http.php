<?php

namespace WAPaaS\MWP\Cache\Types;

use WAPaaS\MWP\Config;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

final class HTTP implements Type {

	/**
	 * Flush the HTTP cache.
	 *
	 * @since 1.0.0
	 *
	 * @return true
	 *
	 * @SuppressWarnings(PHPMD)
	 */
	public static function flush() {

		$args = [
			'method'   => 'PURGE',
			'blocking' => false,
			'timeout'  => 1,
			'headers'  => [
				'X-Cache-Purge' => Config::get( 'site_token' ),
			],
		];

		$url = sprintf( 'http://wp-cache-%s.wp-%s', Config::get( 'site_uid' ), Config::get( 'account_uid' ) );

		wp_remote_request( $url, $args ); // Cannot use `wp_safe_remote_request()`.

		return true;

	}

	/**
	 * Purge specific URLs from the HTTP cache.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $urls
	 *
	 * @return bool
	 *
	 * @SuppressWarnings(PHPMD)
	 */
	public static function purge( array $urls ) {

		foreach ( $urls as &$url ) {

			$url = sprintf( '(%s)$', wp_parse_url( trailingslashit( $url ), PHP_URL_PATH ) );

		}

		$url = sprintf(
			'http://wp-cache-%1$s.wp-%2$s/_cache/delete_regex?url=^https?://wp-web-%1$s\.wp-%2$s%3$s',
			Config::get( 'site_uid' ),
			Config::get( 'account_uid' ),
			implode( '|', $urls )
		);

		wp_remote_get( $url, [ 'blocking' => false, 'timeout' => 1 ] ); // Cannot use `wp_safe_remote_get()`.

		return ! empty( $urls );

	}

}
