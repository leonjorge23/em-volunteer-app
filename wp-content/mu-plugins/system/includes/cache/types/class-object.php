<?php

namespace WAPaaS\MWP\Cache\Types;

use WAPaaS\MWP\Option;
use WP_Comment_Query;
use WP_Error;
use WP_Query;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

final class Object implements Type {

	/**
	 * Flush the object cache.
	 *
	 * @since 1.0.0
	 *
	 * @return true
	 */
	public static function flush() {

		if ( function_exists( 'wp_cache_flush' ) ) {

			wp_cache_flush();

		}

		if ( function_exists( 'apcu_clear_cache' ) ) {

			apcu_clear_cache();

		}

		update_option( Option::NAME . '_last_object_cache_flush', time() );

		return true;

	}

}
