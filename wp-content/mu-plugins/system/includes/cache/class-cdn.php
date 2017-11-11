<?php

namespace WAPaaS\MWP\Cache;

use WAPaaS\MWP\Config;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

final class CDN {

	/**
	 * The base CDN URL.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private $url;

	/**
	 * Regex pattern for URL replacements.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private $pattern;

	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		$this->url = Config::get( 'cdn_url' );

		$enabled = ( empty( $this->url ) || WP_DEBUG ) ? false : (bool) Config::get( 'cdn' );

		/**
		 * Filter whether the CDN cache is enabled.
		 *
		 * Disabled by default if a CDN does not exist or WP_DEBUG is enabled.
		 *
		 * @since 1.0.0
		 *
		 * @param bool   $enabled
		 * @param string $url
		 *
		 * @var bool
		 */
		$enabled = (bool) apply_filters( 'mwp_system_cdn_enabled', $enabled, $this->url );

		if ( ! $enabled || is_admin() ) {

			return;

		}

		$parts = explode( wp_parse_url( Config::get( 'default_site_url' ), PHP_URL_HOST ), $this->url );

		if ( ! isset( $parts[1] ) ) {

			return;

		}

		$host = filter_input( INPUT_SERVER, 'HTTP_HOST' );
		$host = ( $host ) ? $host : wp_parse_url( home_url(), PHP_URL_HOST );

		// File types reference: https://codex.wordpress.org/Uploading_Files#About_Uploading_Files_on_Dashboard
		$this->pattern = sprintf(
			'~https?://%s/(.+?\.(?:gif|ico|jpeg|jpg|png))~i',
			preg_quote( untrailingslashit( $host ) . $parts[1] )
		);

		add_action( 'template_redirect', [ $this, 'template_redirect' ] );
		add_action( 'wp_head',           [ $this, 'wp_head' ], 2 );

		add_filter( 'wp_get_attachment_url', [ $this, 'attachment_url' ] );

	}

	/**
	 * Rewrite URLs in output buffer.
	 *
	 * @action template_redirect
	 * @since  1.0.0
	 */
	public function template_redirect() {

		ob_start( function ( $content ) {

			return preg_replace( $this->pattern, "{$this->url}/$1", $content );

		} );

	}

	/**
	 * Preconnect to CDN host in document head.
	 *
	 * @action wp_head
	 * @since  1.0.0
	 */
	public function wp_head() {

		$url = wp_parse_url( $this->url );

		if ( ! empty( $url['scheme'] ) && ! empty( $url['host'] ) ) {

			printf( // xss ok.
				"<link rel='preconnect' href='%s://%s' />\n",
				$url['scheme'],
				$url['host']
			);

		}

	}

	/**
	 * Filter the attachment URL.
	 *
	 * @filter wp_get_attachment_url
	 * @since  1.0.0
	 *
	 * @param  string $url
	 *
	 * @return string
	 */
	public function attachment_url( $url ) {

		return preg_replace( $this->pattern, "{$this->url}/$1", $url );

	}

}
