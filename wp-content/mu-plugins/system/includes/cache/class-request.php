<?php

namespace WAPaaS\MWP\Cache;

use WAPaaS\MWP\Admin\Growl;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

final class Request {

	/**
	 * Flush request action key.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const FLUSH_ACTION = 'cache_flush';

	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		add_action( 'init', [ $this, 'init' ], 0 );

	}

	/**
	 * Validate a cache flush web request, then redirect.
	 *
	 * @action init
	 * @since  1.0.0
	 */
	public function init() {

		$nonce = filter_input( INPUT_GET, '_wpnonce' );

		if ( ! Control::is_viewable() || false === wp_verify_nonce( $nonce, self::FLUSH_ACTION ) ) {

			return;

		}

		if ( Control::flush() ) {

			Growl::add( esc_html__( 'Cache flushed', 'mwp-system-plugin' ) );

		}

		wp_safe_redirect( esc_url_raw( remove_query_arg( [ 'mwp-action', '_wpnonce' ] ) ) );

		exit;

	}

	/**
	 * Return a nonced cache flush request URL.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $url (optional)
	 *
	 * @return string
	 */
	public static function get_flush_url( $url = '' ) {

		$args = [
			'mwp-action'   => self::FLUSH_ACTION,
			'_wpnonce' => wp_create_nonce( self::FLUSH_ACTION ),
		];

		return ( $url ) ? add_query_arg( $args, $url ) : add_query_arg( $args );

	}

}
