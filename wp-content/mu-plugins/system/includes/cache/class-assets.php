<?php

namespace WAPaaS\MWP\Cache;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

final class Assets {

	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		add_action( 'wp_head', [ $this, 'empty_favicon_tag' ] );

		add_filter( 'script_loader_src', [ $this, 'nocache_query_arg' ], PHP_INT_MAX );
		add_filter( 'style_loader_src',  [ $this, 'nocache_query_arg' ], PHP_INT_MAX );

	}

	/**
	 * Print empty favicon tag when favicon is not set.
	 *
	 * If no favicon is set, printing an empty icon tag will save
	 * an HTTP request on every page load in most browsers.
	 *
	 * @action wp_head
	 * @since  1.0.0
	 */
	public function empty_favicon_tag() {

		/**
		 * Filter whether to print empty favicon tag.
		 *
		 * Disabled by default if WP_DEBUG is on.
		 *
		 * @since 1.0.0
		 *
		 * @var bool
		 */
		$empty = (bool) apply_filters( 'mwp_system_cache_empty_favicon_tag', ! WP_DEBUG );

		if ( $empty && ! is_user_logged_in() && ! file_exists( ABSPATH . 'favicon.ico' ) && ! get_option( 'site_icon' ) ) {

			echo '<link rel="icon" href="data:,">';

		}

	}

	/**
	 * Propogate `nocache` query arg to scripts and styles.
	 *
	 * When the `nocache` query arg is being used in the page
	 * request we need to ensure that enqueued scripts and styles
	 * from this domain also use it.
	 *
	 * @filter script_loader_src - PHP_INT_MAX
	 * @filter style_loader_src - PHP_INT_MAX
	 * @since  1.0.0
	 *
	 * @param  string $src
	 *
	 * @return string
	 */
	public function nocache_query_arg( $src ) {

		if ( isset( $_GET['nocache'] ) && ! self::is_external_host( $src ) ) { // csrf ok.

			$src = add_query_arg( 'nocache', '', $src );

		}

		return $src;

	}

	/**
	 * Check whether an asset URL is hosted externally.
	 *
	 * @param  string $src
	 *
	 * @return bool
	 */
	private static function is_external_host( $src ) {

		$host = filter_input( INPUT_SERVER, 'HTTP_HOST' );
		$host = ( $host ) ? $host : wp_parse_url( home_url(), PHP_URL_HOST );

		return ( wp_parse_url( $src, PHP_URL_HOST ) !== $host );

	}

}
