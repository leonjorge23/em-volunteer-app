<?php
/**
 * Plugin Name: System
 * Version: 1.0.0
 * License: GPL-2.0
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: mwp-system-plugin
 * Domain Path: /languages
 *
 * This plugin, like WordPress, is licensed under the GPL.
 * Use it to make something cool, have fun, and share what you've learned with others.
 *
 * Copyright © 2017 GoDaddy Operating Company, LLC. All Rights Reserved.
 */

namespace WAPaaS\MWP;

use WP_CLI;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

require_once __DIR__ . '/includes/autoload.php';

final class System {

	/**
	 * Plugin version.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const VERSION = '1.0.0';

	/**
	 * Current directory path (no trailing slash).
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const DIR = __DIR__;

	/**
	 * Assets directory path (no trailing slash).
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const ASSETS_DIR = self::DIR . '/assets';

	/**
	 * Includes directory path (no trailing slash).
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const INC_DIR = self::DIR . '/includes';

	/**
	 * Library directory path (no trailing slash).
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const LIB_DIR = self::DIR . '/lib';

	/**
	 * Assets directory URL (with trailing slash).
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const ASSETS_URL = WPMU_PLUGIN_URL . '/system/assets/';

	/**
	 * System plugin instance.
	 *
	 * @since 1.0.0
	 *
	 * @var System
	 */
	private static $instance;

	/**
	 * Return the system plugin instance.
	 *
	 * @since 1.0.0
	 *
	 * @return System
	 */
	public static function load() {

		if ( ! self::$instance ) {

			self::$instance = new self();

		}

		return self::$instance;

	}

	/**
	 * Reset the system plugin instance.
	 *
	 * @since 1.0.0
	 */
	public static function reset() {

		self::$instance = null;

	}

	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 */
	private function __construct() {

		/**
		 * Skip loading this System plugin when WordPress database
		 * tables aren't installed, or are in the process of being
		 * installed.
		 *
		 * @since 1.0.0
		 */
		if ( wp_installing() || ! get_option( 'siteurl' ) ) {

			return;

		}

		/**
		 * If a site has been migrated away to a different host we
		 * will attempt to silently delete this System plugin from
		 * the filesystem.
		 *
		 * Never attempted if `WP_DEBUG` is on.
		 *
		 * @since 1.0.0
		 */
		if ( ! WP_DEBUG && ! Config::exists( 'api_url' ) ) {

			$this->self_destruct();

			return;

		}

		$this->load_textdomain();

		if ( self::is_wp_cli() ) {

			WP_CLI::add_command( 'cache', __NAMESPACE__ . '\Cache\Command' );

		}

		new Admin\Admin_Bar_Menu;
		new Admin\Default_Domain;
		new Admin\Growl;
		new Admin\Help_Page;
		new Admin\Stock_Photos;
		new Cache\Assets;
		new Cache\CDN;
		new Cache\Headers;
		new Cache\Hooks;
		new Cache\Request;
		new Integrations;
		new Plugins\Worker;
		new REST\Controller;
		new Restrictions;
		new SSO;
		new Theme_Updates;

	}

	/**
	 * Delete this plugin from the filesystem.
	 *
	 * @since 1.0.0
	 */
	private function self_destruct() {

		if ( ! class_exists( 'WP_Filesystem' ) ) {

			require_once ABSPATH . 'wp-admin/includes/file.php';

		}

		WP_Filesystem();

		global $wp_filesystem;

		$file_is_deleted = $wp_filesystem->delete( WPMU_PLUGIN_DIR . '/_system.php' );
		$dir_is_deleted  = $wp_filesystem->delete( __DIR__, true );

		// Perhaps we are owned by root?
		if ( ! $file_is_deleted || ! $dir_is_deleted ) {

			return; // Bail now to prevent infinite redirects.

		}

		add_action( 'plugins_loaded', function () {

			if ( function_exists( 'wp_cache_flush' ) ) {

				wp_cache_flush();

			}

			if ( $uri = filter_input( INPUT_SERVER, 'REQUEST_URI', FILTER_SANITIZE_STRING ) ) {

				wp_safe_redirect( home_url( $uri ), 302 );

				exit;

			}

		}, 0 );

	}

	/**
	 * Load translations for our textdomain.
	 *
	 * @since 1.0.0
	 */
	private function load_textdomain() {

		$locale = is_admin() ? get_user_locale() : get_locale();
		$path   = sprintf( '%s/languages/%s.mo', self::DIR, $locale );

		if ( is_readable( $path ) ) {

			load_textdomain( 'mwp-system-plugin', $path );

		}

	}

	/**
	 * Check if the site has a custom primary domain.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public static function has_custom_domain() {

		return ( wp_parse_url( Config::get( 'default_site_url' ), PHP_URL_HOST ) !== wp_parse_url( home_url(), PHP_URL_HOST ) );

	}

	/**
	 * Check if the current request is using the default site URL.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public static function is_default_url() {

		return ( wp_parse_url( Config::get( 'default_site_url' ), PHP_URL_HOST ) === filter_input( INPUT_SERVER, 'HTTP_HOST' ) );

	}

	/**
	 * Check if we are on a child site.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public static function is_child_site() {

		return ( Config::get( 'parent_site_uid' ) );

	}

	/**
	 * Check if we are in WP-CLI mode.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public static function is_wp_cli() {

		return ( defined( 'WP_CLI' ) && WP_CLI );

	}

}

System::load();
