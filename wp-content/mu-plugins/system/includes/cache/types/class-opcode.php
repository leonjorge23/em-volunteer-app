<?php

namespace WAPaaS\MWP\Cache\Types;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

final class Opcode implements Type {

	/**
	 * Flush the opcode cache.
	 *
	 * @since 1.0.0
	 *
	 * @return true
	 */
	public static function flush() {

		if ( function_exists( 'opcache_reset' ) ) {

			opcache_reset();

		}

		return true;

	}

}
