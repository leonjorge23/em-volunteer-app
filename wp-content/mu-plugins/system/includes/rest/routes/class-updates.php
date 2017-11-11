<?php

namespace WAPaaS\MWP\REST\Routes;

use WAPaaS\MWP\REST\Controller;
use WAPaaS\MWP\REST\Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

final class Updates implements Route {

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
				return $this->get_updates();
			},
		] );

	}

	/**
	 * Return an array of available plugin and theme updates.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $fields (optional)
	 *
	 * @return array
	 */
	private function get_updates( array $fields = [] ) {

		$updates = array_merge(
			$this->get_plugin_updates( $fields ),
			$this->get_theme_updates( $fields )
		);

		array_multisort( $updates, wp_list_pluck( $updates, 'slug' ), SORT_NATURAL );

		return $updates;

	}

	/**
	 * Return an array of available plugin updates.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $fields (optional)
	 *
	 * @return array
	 */
	private function get_plugin_updates( array $fields = [] ) {

		$plugins = ( WP_DEBUG ) ? false : get_site_transient( 'update_plugins' );

		// We need to check for updates.
		if ( false === $plugins ) {

			wp_update_plugins();

		}

		if ( ! function_exists( 'get_plugin_updates' ) ) {

			require_once ABSPATH . 'wp-admin/includes/update.php';

		}

		// Used by `get_plugin_updates()`.
		if ( ! function_exists( 'get_plugins' ) ) {

			require_once ABSPATH . 'wp-admin/includes/plugin.php';

		}

		$plugins = get_plugin_updates();

		// Everything is up-to-date.
		if ( ! $plugins ) {

			return [];

		}

		foreach ( $plugins as $basename => &$plugin ) {

			$plugin = Response::plugin_data( $basename, $plugin );

		}

		return Response::filter_fields( array_values( $plugins ), $fields );

	}

	/**
	 * Return an array of available theme updates.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $fields (optional)
	 *
	 * @return array
	 */
	private function get_theme_updates( array $fields = [] ) {

		$themes = ( WP_DEBUG ) ? false : get_site_transient( 'update_themes' );

		// We need to check for updates.
		if ( false === $themes ) {

			wp_update_themes();

		}

		if ( ! function_exists( 'get_theme_updates' ) ) {

			require_once ABSPATH . 'wp-admin/includes/update.php';

		}

		$themes = (array) get_theme_updates();

		// Everything is up-to-date.
		if ( ! $themes ) {

			return [];

		}

		foreach ( $themes as $stylesheet => &$theme ) {

			$theme = Response::theme_data( $stylesheet, $theme );

		}

		return Response::filter_fields( array_values( $themes ), $fields );

	}

}
