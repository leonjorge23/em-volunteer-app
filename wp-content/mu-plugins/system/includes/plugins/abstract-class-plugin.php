<?php

namespace WAPaaS\MWP\Plugins;

use WAPaaS\MWP\Option;
use WAPaaS\MWP\System;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

abstract class Plugin {

	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		add_action( 'init', [ $this, 'init' ], PHP_INT_MAX );

		if ( static::AUTO_UPDATE ) {

			add_filter( 'auto_update_plugin', function ( $update, $item ) {

				return ( static::BASENAME === $item->plugin ) ? true : $update;

			}, PHP_INT_MAX, 2 );

		}

	}

	/**
	 * Special behavior to run at the very end of init.
	 *
	 * @action init - PHP_INT_MAX
	 * @since  1.0.0
	 */
	public function init() {}

	/**
	 * Remove one or more hooked action or filter.
	 *
	 * @since 1.0.0
	 *
	 * @param array $... Variable list of param arrays to pass through `remove_filter()`.
	 */
	protected function remove_hook( $array ) {

		foreach ( func_get_args() as $params ) {

			if ( isset( $params[1] ) && is_callable( $params[1] ) ) {

				remove_filter( ...$params );

			}

		}

	}

}
