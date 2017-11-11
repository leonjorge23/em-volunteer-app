<?php

namespace WAPaaS\MWP\Cache;

use Cache_Command;
use WP_CLI;
use WP_Comment_Query;
use WP_Query;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

final class Command extends Cache_Command {

	/**
	 * Flush the object cache.
	 *
	 * For WordPress multisite instances using a persistent object cache,
	 * flushing the object cache will typically flush the cache for all sites.
	 * Beware of the performance impact when flushing the object cache in
	 * production.
	 *
	 * Errors if a cache can't be flushed.
	 *
	 * ## OPTIONS
	 *
	 * [<type>...]
	 * : One or more types of cache to flush.
	 * ---
	 * default: object
	 * options:
	 *   - http
	 *   - object
	 *   - opcode
	 *   - transient
	 *
	 * [--all]
	 * : Flush all cache types.
	 *
	 * ## EXAMPLES
	 *
	 *     # Flush only the object cache.
	 *     $ wp cache flush
	 *     Success: The object cache was flushed.
	 *
	 *     # Flush the HTTP and object caches.
	 *     $ wp cache flush http object
	 *     Success: The HTTP cache was flushed.
	 *     Success: The object cache was flushed.
	 *
	 *     # Flush all cache types.
	 *     $ wp cache flush --all
	 *     Success: The HTTP cache was flushed.
	 *     Success: The object cache was flushed.
	 *     Success: The opcode cache was flushed.
	 *     Success: 12 transients deleted from the database.
	 *
	 * @SuppressWarnings(PHPMD)
	 */
	public function flush( $args, $assoc_args ) {

		$all = (bool) WP_CLI\Utils\get_flag_value( $assoc_args, 'all', false );

		$types = array_keys( Control::TYPES );
		$types = ( $all ) ? $types : array_values( array_intersect( $types, $args ) );
		$types = ( ! $types && ! $all ) ? [ 'object' ] : $types;

		$results = Control::flush( $types );

		if ( ! $results ) {

			WP_CLI::error( 'Unable to flush cache.' );

		}

		foreach ( $results as $type => $result ) {

			switch ( true ) {

				case is_wp_error( $result ) :

					WP_CLI::error( $result->get_error_message() );

					break;

				case ( 'transient' === $type && 0 === $result ) :

					WP_CLI::success( 'No transients found.' );

					break;

				case ( 'transient' === $type && $result > 0 ) :

					WP_CLI::success( "{$result} transient(s) deleted from the database." );

					break;

				default :

					$type = ( 'http' === $type ) ? strtoupper( $type ) : $type;

					WP_CLI::success( "The {$type} cache was flushed." );

			}

		}

	}

	/**
	 * Purge the HTTP cache.
	 *
	 * ## OPTIONS
	 *
	 * [<urls>...]
	 * : Purge specific URLs (comma separated).
	 *
	 * [--post_ids=<id>]
	 * : Purge URLs associated with specific post IDs (comma separated).
	 *
	 * [--comment_ids=<id>]
	 * : Purge URLs associated with specific comment IDs (comma separated).
	 *
	 * [--format=<format>]
	 * : Render list of purged URLs in a particular format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - csv
	 *   - json
	 *   - yaml
	 * ---
	 *
	 * @SuppressWarnings(PHPMD)
	 */
	public function purge( $args, $assoc_args ) {

		$urls        = array_filter( array_map( 'esc_url_raw', $args ) );
		$post_ids    = array_filter( array_map( 'absint', explode( ',', WP_CLI\Utils\get_flag_value( $assoc_args, 'post_ids' ) ) ) );
		$comment_ids = array_filter( array_map( 'absint', explode( ',', WP_CLI\Utils\get_flag_value( $assoc_args, 'comment_ids' ) ) ) );

		if ( ! $urls && ! $post_ids && ! $comment_ids ) {

			$urls[] = home_url();

		}

		$posts    = [];
		$comments = [];

		if ( $comment_ids ) {

			$comments = new WP_Comment_Query;
			$comments = $comments->query( [ 'comment__in' => array_unique( $comment_ids ), 'number' => 999 ] );
			$comments = ( $comments ) ? $comments : [];

		}

		if ( $comments ) {

			$post_ids = array_merge( $post_ids, wp_list_pluck( $comments, 'comment_post_ID' ) );

		}

		if ( $post_ids ) {

			$posts = new WP_Query( [ 'post__in' => array_unique( $post_ids ), 'posts_per_page' => 999 ] );
			$posts = $posts->have_posts() ? $posts->posts : [];

		}

		foreach ( $posts as $post ) {

			$urls = array_merge( $urls, Control::get_urls_for_post( $post ) );

		}

		$results = Control::purge( $urls );

		if ( ! $results ) {

			WP_CLI::error( 'There are no URLs to purge.' );

		}

		$items = [];

		foreach ( $results as $result ) {

			$items[] = [ 'url' => $result ];

		}

		$format = WP_CLI\Utils\get_flag_value( $assoc_args, 'format', 'table' );

		WP_CLI\Utils\format_items( $format, $items, [ 'url' ] );

	}

}
