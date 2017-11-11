<?php

namespace WAPaaS\MWP\REST\Routes;

use WAPaaS\MWP\Cache\Control as Cache_Control;
use WAPaaS\MWP\REST\Controller;
use WAPaaS\MWP\REST\Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

final class Cache implements Route {

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

		$types = filter_input( INPUT_GET, 'types', FILTER_SANITIZE_STRING );
		$urls  = filter_input( INPUT_GET, 'urls', FILTER_SANITIZE_STRING );
		$urls  = ( $urls ) ? $urls : home_url();

		register_rest_route( Controller::$namespace, self::$route, [
			'methods'  => 'FLUSH',
			'callback' => function () use ( $types ) {
				return Cache_Control::flush( array_filter( explode( ',', $types ) ) );
			},
		] );

		register_rest_route( Controller::$namespace, self::$route, [
			'methods'  => 'PURGE',
			'callback' => function () use ( $urls ) {
				return Cache_Control::purge( array_filter( explode( ',', $urls ) ) );
			},
		] );

	}

}
