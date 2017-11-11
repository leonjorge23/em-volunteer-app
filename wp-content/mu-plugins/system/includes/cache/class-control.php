<?php

namespace WAPaaS\MWP\Cache;

use WAPaaS\MWP\Config;
use WAPaaS\MWP\Option;
use WAPaaS\MWP\REST\Controller as REST_Controller;
use WP_Comment;
use WP_Post;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

final class Control {

	/**
	 * Array of cache types.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	const TYPES = [
		'http'      => __NAMESPACE__ . '\Types\HTTP',
		'object'    => __NAMESPACE__ . '\Types\Object',
		'opcode'    => __NAMESPACE__ . '\Types\Opcode',
		'transient' => __NAMESPACE__ . '\Types\Transient',
	];

	/**
	 * Check if the current user can control the cache.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public static function is_viewable() {

		return current_user_can( 'install_plugins' );

	}

	/**
	 * Flush the cache.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $types (optional)
	 *
	 * @return array
	 */
	public static function flush( array $types = [ 'http', 'object', 'opcode', 'transient' ] ) {

		static $already_flushed = [];

		$all   = array_keys( self::TYPES );
		$types = array_filter( array_unique( $types ) );
		$types = ( $types ) ? array_intersect( $types, $all ) : $all;
		$types = array_diff( $types, $already_flushed ); // Limit one flush per type per PHP request.

		$results = [];

		foreach ( $types as $type ) {

			$class = self::TYPES[ $type ];

			if ( is_callable( $class, 'flush' ) ) {

				$results[ $type ] = $class::flush();

				// Repeat the flush call again on shutdown.
				add_action( 'shutdown', function () use ( $class ) {

					$class::flush();

				}, PHP_INT_MAX );

			}

			// Add a REST call if object/opcode flush is initiated from WP-CLI.
			if ( defined( 'WP_CLI' ) && WP_CLI && in_array( $type, [ 'object', 'opcode' ], true ) ) {

				self::rest_request( 'FLUSH', [ 'types' => [ $type ] ] );

			}

		}

		$results = array_filter( $results, function ( $result ) {

			return ( true === $result || is_int( $result ) );

		} );

		if ( $results ) {

			$already_flushed = array_merge( $already_flushed, array_keys( $results ) );

			/**
			 * Fires after the cache has been flushed.
			 *
			 * @since 1.0.0
			 *
			 * @param array $types Array of cache types flushed and the result for each.
			 */
			do_action( 'mwp_system_cache_flush', $results );

		}

		return $results;

	}

	/**
	 * Purge specific URLs from the HTTP cache.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $urls
	 *
	 * @return array
	 */
	public static function purge( array $urls ) {

		static $already_purged = [];

		$urls = array_filter( array_unique( $urls ) );
		$urls = array_diff( $urls, $already_purged ); // Limit one purge per URL per PHP request.

		$class = isset( self::TYPES['http'] ) ? self::TYPES['http'] : null;

		if ( ! $urls || ! is_callable( $class, 'purge' ) ) {

			return [];

		}

		$class::purge( $urls );

		$already_purged = array_merge( $already_purged, $urls );

		/**
		 * Fires after specific URLs have been purged from the HTTP cache.
		 *
		 * @since 1.0.0
		 *
		 * @param array $results Array of URLs purged from the HTTP cache.
		 */
		do_action( 'mwp_system_http_cache_purge', $urls );

		return $urls;

	}

	/**
	 * Call the WP REST API for cache actions.
	 *
	 * @param  string $method
	 * @param  array  $args   (optional)
	 *
	 * @return array|false
	 */
	private static function rest_request( $method, array $args = [] ) {

		$url = sprintf(
			'%s/?rest_route=/%s/v%d/cache',
			Config::get( 'default_site_url' ),
			Config::get( 'site_uid' ),
			REST_Controller::VERSION
		);

		$args = array_filter( $args );

		if ( $args ) {

			foreach ( $args as &$arg ) {

				$arg = is_array( $arg ) ? implode( ',', $arg ) : $arg;

			}

			$url = add_query_arg( $args, $url );

		}

		$response = wp_safe_remote_request( esc_url_raw( $url ), [
			'method'    => $method,
			'blocking'  => true,
			'sslverify' => false, // This is a safe internal request.
			'headers'   => [
				'Authorization' => 'Token ' . Config::get( 'site_token' ),
			],
		] );

		if ( 200 === wp_remote_retrieve_response_code( $response ) ) {

			return json_decode( wp_remote_retrieve_body( $response ), true );

		}

		return false;

	}

	/**
	 * Return an array of URLs associated with a post.
	 *
	 * @since 1.0.0
	 *
	 * @param  WP_Post $post
	 *
	 * @return array
	 */
	public static function get_urls_for_post( $post ) {

		if ( ! is_a( $post, 'WP_Post' ) || wp_is_post_revision( $post ) ) {

			return [];

		}

		$urls = [];

		$time  = strtotime( $post->post_date_gmt );
		$year  = gmdate( 'Y', $time );
		$month = gmdate( 'm', $time );
		$day   = gmdate( 'd', $time );

		/**
		 * Purge all URLs where a post might appear (best guess).
		 */
		$urls[] = untrailingslashit( home_url() );
		$urls[] = get_permalink( $post->ID );
		$urls[] = get_post_type_archive_link( $post->post_type );
		$urls[] = get_year_link( $year );
		$urls[] = get_month_link( $year, $month );
		$urls[] = get_day_link( $year, $month, $day );
		$urls[] = get_author_posts_url( (int) $post->post_author );

		// Taxonomy-related URLs.
		foreach ( get_post_taxonomies( $post ) as $tax ) {

			$post_terms = wp_get_post_terms( $post->ID, $tax );

			if ( is_wp_error( $post_terms ) ) {

				continue;

			}

			foreach ( $post_terms as $term ) {

				$urls[] = get_term_link( $term );

			}

		}

		// Archive page might return `false`.
		$urls = array_filter( $urls, function ( $url ) {

			return ( ! empty( $url ) && ! is_wp_error( $url ) );

		} );

		return array_values( array_unique( $urls ) );

	}

}
