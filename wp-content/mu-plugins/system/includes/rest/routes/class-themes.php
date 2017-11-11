<?php

namespace WAPaaS\MWP\REST\Routes;

use WAPaaS\MWP\REST\Controller;
use WAPaaS\MWP\REST\Response;
use WAPaaS\MWP\REST\Transient;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

final class Themes implements Route {

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
				return $this->get_themes( [ 'type' => false ] );
			},
		] );

	}

	/**
	 * Return an array of all installed themes.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $fields (optional)
	 *
	 * @return array
	 */
	private function get_themes( array $fields = [] ) {

		$response = ( WP_DEBUG ) ? false : Transient::get( self::$route );

		if ( false !== $response ) {

			return Response::filter_fields( (array) $response, $fields );

		}

		$themes = wp_get_themes();

		foreach ( $themes as $stylesheet => &$theme ) {

			$theme = Response::theme_data( $stylesheet, $theme );

		}

		$response = array_values( $themes );

		Transient::set( $response, self::$route );

		return Response::filter_fields( $response, $fields );

	}

}
