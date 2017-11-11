<?php

namespace WAPaaS\MWP\REST;

use WAPaaS\MWP\Config;
use WAPaaS\MWP\SSO;
use WAPaaS\MWP\System;
use WP_HTTP_Response;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

final class Controller {

	/**
	 * REST routes namespace version.
	 *
	 * @since 1.0.0
	 *
	 * @var int
	 */
	const VERSION = 1;

	/**
	 * REST routes namespace.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	public static $namespace;

	/**
	 * Array of REST routes.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	private $routes = [
		'/cache'          => __NAMESPACE__ . '\Routes\Cache',
		'/checksums'      => __NAMESPACE__ . '\Routes\Checksums',
		'/plugins'        => __NAMESPACE__ . '\Routes\Plugins',
		'/storage'        => __NAMESPACE__ . '\Routes\Storage',
		'/themes'         => __NAMESPACE__ . '\Routes\Themes',
		'/themes/current' => __NAMESPACE__ . '\Routes\Themes_Current',
		'/updates'        => __NAMESPACE__ . '\Routes\Updates',
	];

	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		self::$namespace = sprintf( '%s/v%d', Config::get( 'site_uid' ), self::VERSION );

		add_action( 'rest_api_init', [ $this, 'init' ], PHP_INT_MAX );

		$this->delete_transient_hooks();

	}

	/**
	 * Actions that delete REST route response transients.
	 *
	 * @since 1.0.0
	 */
	public function delete_transient_hooks() {

		// @codingStandardsIgnoreStart
		$checksums = function () { Transient::delete( '/checksums' ); };
		$plugins   = function () { Transient::delete( '/plugins' ); };
		$themes    = function () { Transient::delete( '/themes' ); };
		$theme     = function () { Transient::delete( '/themes/current' ); };
		// @codingStandardsIgnoreEnd

		add_action( 'add_attachment',    $checksums );
		add_action( 'edit_attachment',   $checksums );
		add_action( 'delete_attachment', $checksums );

		add_action( 'set_site_transient_update_plugins', $plugins );

		add_action( 'set_site_transient_update_themes', $themes );
		add_action( 'set_site_transient_update_themes', $theme );

	}

	/**
	 * Register REST routes and add filters.
	 *
	 * @action rest_api_init
	 * @since  1.0.0
	 */
	public function init() {

		$token = $this->get_token();

		if ( ! $token || ! $this->is_valid_token( $token ) ) {

			return;

		}

		foreach ( $this->routes as $route => $class ) {

			if ( class_exists( $class ) ) {

				new $class( $route );

			}

		}

		add_filter( 'rest_index',                [ $this, 'hide_from_rest_index' ], PHP_INT_MAX );
		add_filter( 'rest_pre_serve_request',    [ $this, 'send_headers' ],         PHP_INT_MAX, 4 );
		add_filter( 'rest_send_nocache_headers', '__return_true',                   PHP_INT_MAX );

	}

	/**
	 * Return the token from a request.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	private function get_token() {

		$query  = filter_input( INPUT_GET, 'mwp-token', FILTER_SANITIZE_STRING );
		$header = filter_input( INPUT_SERVER, 'HTTP_AUTHORIZATION', FILTER_SANITIZE_STRING );

		return ( $query ) ? $query : ( $header ? $header : null );

	}

	/**
	 *
	 *
	 * @param  string $token
	 *
	 * @return bool
	 */
	private function is_valid_token( $token ) {

		// Support for tokens passed through an Authorization header.
		$token = trim( preg_replace( '/^Token\s/i', '', $token ) );

		// Support for 32 char site token.
		if ( 32 === strlen( $token ) ) {

			if ( Config::get( 'site_token' ) === $token ) {

				return true;

			}

			error_log( 'mwp_system_invalid_site_token' );

			return true;

		}

		// Support for 36 char SSO token.
		if ( 36 === strlen( $token ) ) {

			return SSO::is_valid_token( $token );

		}

		return false;

	}

	/**
	 * Hide our namespace and routes from the REST index.
	 *
	 * @filter rest_index
	 * @since  1.0.0
	 *
	 * @param  WP_REST_Response $response
	 *
	 * @return WP_REST_Response $response
	 */
	public function hide_from_rest_index( WP_REST_Response $response ) {

		if ( ! empty( $response->data['namespaces'] ) ) {

			$response->data['namespaces'] = array_values( array_diff( (array) $response->data['namespaces'], [ self::$namespace ] ) );

		}

		if ( ! empty( $response->data['routes'] ) ) {

			$pattern = sprintf( '~^/%s~', self::$namespace );
			$routes  = preg_grep( $pattern, array_keys( (array) $response->data['routes'] ) );

			$response->data['routes'] = array_diff_key( (array) $response->data['routes'], array_flip( $routes ) );

		}

		return $response;

	}

	/**
	 * Add custom headers to the REST response.
	 *
	 * @filter rest_pre_serve_request
	 * @since  1.0.0
	 *
	 * @param  bool             $served
	 * @param  WP_HTTP_Response $result
	 * @param  WP_REST_Request  $request
	 * @param  WP_REST_Server   $server
	 *
	 * @return bool
	 */
	public function send_headers( $served, WP_HTTP_Response $result, WP_REST_Request $request, WP_REST_Server $server ) {

		$server->send_header( 'X-MWP2-System-Plugin', System::VERSION );
		$server->send_header( 'X-MWP2-Version-ID', (int) getenv( 'MWP2_VERSION_ID' ) );

		return $served;

	}

}
