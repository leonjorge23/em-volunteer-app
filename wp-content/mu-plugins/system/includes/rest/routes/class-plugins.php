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

final class Plugins implements Route {

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
				return $this->get_plugins( [ 'type' => false ] );
			},
		] );

	}

	/**
	 * Return an array of all installed plugins.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $fields (optional)
	 *
	 * @return array
	 */
	private function get_plugins( array $fields = [] ) {

		$response = ( WP_DEBUG ) ? false : Transient::get( self::$route );

		if ( false !== $response ) {

			return Response::filter_fields( (array) $response, $fields );

		}

		if ( ! function_exists( 'get_plugins' ) ) {

			require_once ABSPATH . 'wp-admin/includes/plugin.php';

		}

		/**
		 * Filter plugin basenames to be excluded.
		 *
		 * @since 1.0.0
		 *
		 * @var array
		 */
		$excluded = (array) apply_filters( 'mwp_system_rest_plugins_exclude', [] );

		$plugins = array_diff_key( get_plugins(), array_flip( $excluded ) );

		foreach ( $plugins as $basename => &$plugin ) {

			$plugin = Response::plugin_data( $basename, $plugin );

		}

		$response = array_values( $plugins );

		Transient::set( $response, self::$route );

		return Response::filter_fields( $response, $fields );

	}

}
