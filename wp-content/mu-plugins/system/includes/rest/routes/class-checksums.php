<?php

namespace WAPaaS\MWP\REST\Routes;

use WAPaaS\MWP\Option;
use WAPaaS\MWP\REST\Controller;
use WAPaaS\MWP\REST\Response;
use WAPaaS\MWP\REST\Transient;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

final class Checksums implements Route {

	/**
	 * Directories in wp-content allowed to report a checksum.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	const ALLOWED_DIRS = [ 'plugins', 'themes', 'uploads' ];

	/**
	 * REST route.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private static $route;

	/**
	 * Route constructor.
	 *
	 * @since 1.0.0
	 *
	 * @param string $route
	 */
	public function __construct( $route ) {

		self::$route = $route;

		register_rest_route( Controller::$namespace, self::$route, [
			'methods'  => WP_REST_Server::READABLE,
			'callback' => function () {
				return $this->get_checksums();
			},
		] );

	}

	/**
	 * Return an array of wp-content checksum data.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $fields (optional)
	 *
	 * @return array
	 */
	private function get_checksums( array $fields = [] ) {

		$checksums = ( WP_DEBUG ) ? [] : array_filter( (array) Transient::get( self::$route ) );
		$defaults  = array_fill_keys( self::ALLOWED_DIRS, [ 'count' => null, 'hash' => null ] );
		$checksums = array_replace( $defaults, $checksums );
		$missing   = array_filter( $checksums, function ( $data ) {

			return ( ! isset( $data['count'] ) || empty( $data['hash'] ) );

		} );

		if ( ! $missing ) {

			return Response::filter_fields( $checksums, $fields );

		}

		foreach ( $missing as $type => &$data ) {

			$data = [
				'count' => $this->get_count( $type ),
				'hash'  => $this->get_hash( $type ),
			];

		}

		$response = array_merge( $defaults, $checksums, $missing );

		ksort( $response );

		Transient::set( $response, self::$route );

		return Response::filter_fields( $response, $fields );

	}

	/**
	 * Return the item count for a checksum type.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $type
	 *
	 * @return int
	 */
	private function get_count( $type ) {

		switch ( $type ) {

			case 'plugins' :

				if ( ! function_exists( 'get_plugins' ) ) {

					require_once ABSPATH . 'wp-admin/includes/plugin.php';

				}

				$data  = get_plugins();
				$count = count( $data );

				break;

			case 'themes' :

				$data  = wp_get_themes();
				$count = count( $data );

				break;

			case 'uploads' :

				$data  = (array) wp_count_attachments();
				$count = array_sum( $data );

				break;

			default :

				$data  = [];
				$count = 0;

		}

		/**
		 * Filter the item count for a given type.
		 *
		 * @since 1.0.0
		 *
		 * @param array $data
		 *
		 * @var int
		 */
		return (int) apply_filters( "mwp_system_rest_checksums_{$type}_count", $count, $data );

	}

	/**
	 * Return the checksum hash of a directory.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $type
	 *
	 * @return string
	 */
	private function get_hash( $type ) {

		$path = trailingslashit( WP_CONTENT_DIR ) . $type;

		if ( ! is_dir( $path ) ) {

			return;

		}

		/**
		 * Filter paths to be excluded from the hash for a given type.
		 *
		 * @since 1.0.0
		 *
		 * @var array
		 */
		$excluded = (array) apply_filters( "mwp_system_rest_checksums_{$type}_hash_exclude", [] );

		if ( $excluded ) {

			foreach ( $excluded as &$exclude ) {

				$exclude = trailingslashit( $path ) . untrailingslashit( $exclude );
				$exclude = file_exists( $exclude ) ? $exclude : null;

			}

		}

		$excluded = array_filter( $excluded );

		$command = sprintf(
			"%s -ab %s %s | md5sum | awk '{ print \$1 }'",
			( 'Darwin' === PHP_OS ) ? 'gdu' : 'du',
			$path,
			( $excluded ) ? sprintf( '--exclude=%s', implode( ',', $excluded ) ) : null
		);

		return exec( $command );

	}

}
