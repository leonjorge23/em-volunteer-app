<?php

namespace WAPaaS\MWP\Admin;

use WAPaaS\MWP\Cache\Control as Cache_Control;
use WAPaaS\MWP\Cache\Request as Cache_Request;
use WAPaaS\MWP\Config;
use WP_Admin_Bar;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

final class Admin_Bar_Menu {

	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		add_action( 'init', [ $this, 'init' ] );

	}

	/**
	 * Check if the current user can view the Admin Bar Menu.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public static function is_viewable() {

		$user_cap = Config::get( 'admin_bar_menu_user_cap', 'install_plugins' );

		return current_user_can( $user_cap );

	}

	/**
	 * Initialize.
	 *
	 * @action init
	 * @since  1.0.0
	 */
	public function init() {

		if ( ! self::is_viewable() ) {

			return;

		}

		add_action( 'admin_bar_menu', [ $this, 'admin_bar_menu' ], 81 );

	}

	/**
	 * Admin bar menu.
	 *
	 * @action admin_bar_menu - 81
	 * @since  1.0.0
	 *
	 * @param WP_Admin_Bar $admin_bar
	 */
	public function admin_bar_menu( WP_Admin_Bar $admin_bar ) {

		$admin_bar->add_menu(
			[
				'id'    => 'mwp-system',
				'title' => sprintf(
					'<span class="ab-icon dashicons dashicons-%s" style="margin-top: 2px;"></span><span class="ab-label">%s</span>',
					esc_attr( Config::get( 'admin_bar_dashicon', 'admin-generic' ) ),
					esc_html__( 'Managed WordPress', 'mwp-system-plugin' )
				),
			]
		);

		$hui_settings_url = Config::get( 'hui_settings_url' );

		if ( $hui_settings_url ) {

			$admin_bar->add_menu(
				[
					'parent' => 'mwp-system',
					'id'     => 'mwp-system-settings',
					'href'   => esc_url( $hui_settings_url ),
					'title'  => sprintf(
						'%s <span class="dashicons dashicons-external" style="margin-top: 5px; font: 16px/1 \'dashicons\'; -webkit-font-smoothing: antialiased; -moz-osx-font-smoothing: grayscale;"></span>',
						esc_html__( 'Account Settings', 'mwp-system-plugin' )
					),
					'meta'   => [
						'target' => '_blank',
					],
				]
			);

		}

		if ( Help_Page::is_viewable() ) {

			$admin_bar->add_menu(
				[
					'parent' => 'mwp-system',
					'id'     => esc_attr( Help_Page::PAGE_SLUG ),
					'href'   => esc_url( Help_Page::get_url() ),
					'title'  => __( 'Help &amp; Support', 'mwp-system-plugin' ),
				]
			);

		}

		if ( Cache_Control::is_viewable() ) {

			$admin_bar->add_menu(
				[
					'parent' => 'mwp-system',
					'id'     => 'mwp-system-flush-cache',
					'href'   => esc_url( Cache_Request::get_flush_url() ),
					'title'  => esc_html__( 'Flush Cache', 'mwp-system-plugin' ),
				]
			);

		}

	}

}
