<?php

namespace WAPaaS\MWP\REST\Routes;

use WAPaaS\MWP\REST\Controller;
use WAPaaS\MWP\REST\Response;
use WAPaaS\MWP\REST\Transient;
use WP_Error;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

final class Storage implements Route {

	/**
	 * List of valid block size units.
	 *
	 * @link  https://www.gnu.org/software/coreutils/manual/html_node/Block-size.html
	 * @since 1.0.0
	 *
	 * @var array
	 */
	const UNITS = [ 'MB', 'GB', 'TB', 'PB', 'MiB', 'GiB', 'TiB', 'PiB' ];

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
				return $this->get_storage_stats( [], (array) filter_input_array( INPUT_GET, [
					'dir'       => FILTER_SANITIZE_STRING,
					'precision' => FILTER_SANITIZE_NUMBER_INT,
					'unit'      => FILTER_SANITIZE_STRING,
				] ) );
			},
		] );

	}

	/**
	 * Return an array of wp-content checksum data.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $fields (optional)
	 * @param  array $args   (optional)
	 *
	 * @return array
	 */
	private function get_storage_stats( array $fields = [], array $args = [] ) {

		$defaults = [
			'dir'       => '/',
			'unit'      => 'GiB',
			'precision' => 3,
		];

		$args      = wp_parse_args( $args, $defaults );
		$path      = $this->get_abspath( $args['dir'] );
		$unit      = in_array( $args['unit'], self::UNITS, true ) ? $args['unit'] : 'GiB';
		$precision = is_numeric( $args['precision'] ) ? absint( $args['precision'] ) : 3;

		if ( ! is_dir( $path ) ) {

			return new WP_Error( 'rest_storage_no_path', 'Path not found', [ 'status' => 404 ] );

		}

		$key   = sprintf( '%s_%s', self::$route, md5( $path . $unit . $precision ) );
		$stats = ( WP_DEBUG ) ? [] : array_filter( (array) Transient::get( $key ) );

		if ( $stats ) {

			return Response::filter_fields( $stats, $fields );

		}

		$response = [
			'path' => $path,
			'size' => $this->get_size( $path, $unit, $precision ),
			'unit' => $unit,
		];

		Transient::set( $response, $key );

		return Response::filter_fields( $response, $fields );

	}

	/**
	 * Return the absolute path of a relative path.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $rel_path
	 *
	 * @return string
	 */
	private function get_abspath( $rel_path ) {

		return untrailingslashit( ABSPATH . preg_replace( '~^/~', '', (string) $rel_path ) );

	}

	/**
	 * Return the size of a directory.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $path
	 * @param  string $unit
	 * @param  string $precision
	 *
	 * @return int|null
	 */
	private function get_size( $path, $unit, $precision ) {

		$size = (int) exec(
			sprintf(
				"%s -B%s -s %s | awk '{ print \$1 }'",
				( 'Darwin' === PHP_OS ) ? 'gdu' : 'du',
				escapeshellcmd( $this->get_sub_unit( $unit ) ),
				escapeshellcmd( $path )
			)
		);

		$power = strpos( $unit, 'iB' ) ? 1024 : 1000;

		return ( $size ) ? round( $size / $power, absint( $precision ) ) : null;

	}

	/**
	 * Return the unit that is one step below the given unit.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $unit
	 *
	 * @return string
	 */
	private function get_sub_unit( $unit ) {

		$unit  = in_array( $unit, self::UNITS, true ) ? $unit : 'GiB';
		$index = array_search( $unit, self::UNITS, true );

		return ( 'MB' === $unit ) ? 'kB' : ( 'MiB' === $unit ? 'KiB' : self::UNITS[ $index - 1 ] );

	}

}
