<?php

namespace WAPaaS\MWP\Plugins;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

final class Worker extends Plugin {

	/**
	 * Plugin basename.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const BASENAME = 'worker/init.php';

	/**
	 * Whether the plugin should auto-update in the background.
	 *
	 * @since 1.0.0
	 *
	 * @var bool
	 */
	const AUTO_UPDATE = true;

	/**
	 * Special behavior to run at the very end of init.
	 *
	 * @action init - PHP_INT_MAX
	 * @since  1.0.0
	 */
	public function init() {

		global $mmb_core;

		if ( $mmb_core ) {

			$this->remove_hook(
				[ 'admin_notices',         [ $mmb_core, 'admin_notice' ] ],
				[ 'network_admin_notices', [ $mmb_core, 'network_admin_notice' ] ] // Multisite.
			);

		}

		add_filter( 'mwp_system_rest_checksums_plugins_count', function ( $count, $data ) {

			if ( $count > 0 && isset( $data[ self::BASENAME ] ) ) {

				$count = $count--;

			}

			return $count;

		}, 10, 2 );

		add_filter( 'mwp_system_rest_checksums_plugins_hash_exclude', function ( $plugins ) {

			$plugins[] = dirname( self::BASENAME );

			return $plugins;

		} );

		add_filter( 'mwp_system_rest_plugins_exclude', function ( $plugins ) {

			$plugins[] = self::BASENAME;

			return $plugins;

		} );

	}

}
