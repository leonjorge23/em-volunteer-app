<?php

namespace WAPaaS\MWP\Cache\Types;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

final class Transient implements Type {

	/**
	 * Flush the transient cache.
	 *
	 * @since 1.0.0
	 *
	 * @return int|WP_Error
	 */
	public static function flush() {

		global $wpdb;

		$result = $wpdb->query( "DELETE FROM `{$wpdb->options}` WHERE `option_name` LIKE '%_transient_%';" );

		if ( false === $result ) {

			return new WP_Error( 'transient_cache_flush_error', esc_html__( 'The transient cache could not be flushed.', 'mwp-system-plugin' ) );

		}

		return (int) $result;

	}

}
