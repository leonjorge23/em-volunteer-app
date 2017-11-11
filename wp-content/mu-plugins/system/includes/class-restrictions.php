<?php

namespace WAPaaS\MWP;

use WP_CLI;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

final class Restrictions {

	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		$this->core_updates();

		$this->file_editor();

		$this->multisite();

		$this->phpmailer();

		$this->protect_options( [ 'siteurl', 'home' ] );

		if ( ! System::has_custom_domain() || System::is_child_site() || System::is_default_url() ) {

			add_filter( 'option_blog_public', '__return_zero', PHP_INT_MAX );

			$this->protect_options( [ 'blog_public' ] );

		}

		$this->wp_cron();

	}

	/**
	 * Disable WP Core updates and notifications.
	 *
	 * @since 1.0.0
	 */
	private function core_updates() {

		// Disable automatic core updates.
		add_filter( 'auto_update_core', '__return_false', PHP_INT_MAX );

		// Redefine constant if removed from wp-config.php.
		if ( ! defined( 'WP_AUTO_UPDATE_CORE' ) ) {

			define( 'WP_AUTO_UPDATE_CORE', false );

		}

		// Disable core update emails.
		add_filter( 'automatic_updates_send_email',        '__return_false', PHP_INT_MAX );
		add_filter( 'enable_auto_upgrade_email',           '__return_false', PHP_INT_MAX );
		add_filter( 'automatic_updates_send_debug_email',  '__return_false', PHP_INT_MAX );
		add_filter( 'auto_core_update_send_email',         '__return_false', PHP_INT_MAX );
		add_filter( 'send_core_update_notification_email', '__return_false', PHP_INT_MAX );

		// Remove `update_core` user capability.
		add_filter( 'user_has_cap', [ $this, 'remove_update_core_cap' ], PHP_INT_MAX );

		// Spoof the core update transient.
		add_filter( 'pre_site_transient_update_core', [ $this, 'spoof_update_core_object' ], PHP_INT_MAX );

		// Disable core update nags.
		$this->unhook_core_update_nags();

	}

	/**
	 * Prevent users from having the `update_core` capability.
	 *
	 * @filter user_has_cap
	 * @since  1.0.0
	 *
	 * @param  array $allcaps
	 *
	 * @return array
	 */
	public function remove_update_core_cap( array $allcaps ) {

		$allcaps['update_core'] = false;

		return $allcaps;

	}

	/**
	 * Prevent update core nags and notifications.
	 *
	 * @filter pre_site_transient_update_core
	 * @since  1.0.0
	 *
	 * @return object
	 */
	public function spoof_update_core_object() {

		return (object) [
			'last_checked'    => time(),
			'version_checked' => get_bloginfo( 'version' ),
		];

	}

	/**
	 * Prevent all nags related to core updates.
	 *
	 * 1. Loop through every possible nag on every possible admin notice hook.
	 * 2. Dynamically add a hook that unhooks a nag from itself (hookception).
	 * 3. Unhook the dynamically-added hook.
	 * 4. Close the closure pointer reference after each iteration.
	 *
	 * @since 1.0.0
	 */
	private function unhook_core_update_nags() {

		$hooks = [
			'network_admin_notices', // Multisite.
			'user_admin_notices',
			'admin_notices',
			'all_admin_notices',
		];

		$callbacks = [
			'update_nag',
			'maintenance_nag',
			'site_admin_notice', // Multisite.
		];

		foreach ( $hooks as $hook ) {

			foreach ( $callbacks as $callback ) {

				$closure = function () use ( $hook, $callback, &$closure ) {

					$priority = has_action( $hook, $callback );

					if ( false !== $priority ) {

						remove_action( $hook, $callback, $priority );

					}

					remove_action( $hook, $closure, -PHP_INT_MAX );

				};

				add_action( $hook, $closure, -PHP_INT_MAX );

				unset( $closure );

			} // @codingStandardsIgnoreLine

		}

	}

	/**
	 * Disable the File Editor by default.
	 *
	 * @since 1.0.0
	 */
	private function file_editor() {

		if ( ! defined( 'DISALLOW_FILE_EDIT' ) ) {

			define( 'DISALLOW_FILE_EDIT', true );

		}

	}

	/**
	 * Disable WP multisite by default.
	 *
	 * @since 1.0.0
	 */
	private function multisite() {

		if ( ! defined( 'WP_ALLOW_MULTISITE' ) || ! WP_ALLOW_MULTISITE ) {

			return;

		}

		add_action( 'admin_menu',     [ $this, 'remove_network_submenu' ], PHP_INT_MAX );
		add_action( 'current_screen', [ $this, 'network_setup_redirect' ], PHP_INT_MAX );
		add_action( 'admin_notices',  [ $this, 'multisite_admin_notice' ], PHP_INT_MAX );

	}

	/**
	 * Remove the `Tools > Network Setup` submenu.
	 *
	 * @action admin_menu
	 * @since  1.0.0
	 */
	public function remove_network_submenu() {

		remove_submenu_page( 'tools.php', 'network.php' );

	}

	/**
	 * Redirect away from the `Network Setup` screen.
	 *
	 * @action current_screen
	 * @since  1.0.0
	 */
	public function network_setup_redirect() {

		$screen = get_current_screen();

		if ( ! isset( $screen->base ) || 'network' !== $screen->base ) {

			return;

		}

		$this->call_redirect( admin_url() );

	}

	/**
	 * Display an admin notice about multisite.
	 *
	 * @action admin_notices
	 * @since  1.0.0
	 */
	public function multisite_admin_notice() {

		if ( ! defined( 'WP_ALLOW_MULTISITE' ) || WP_ALLOW_MULTISITE ) {

			return;

		}

		printf(
			'<div class="notice notice-warning">
				<p>%s</p>
			</div>',
			wp_kses_post(
				sprintf(
					/* translators: 1. String wrapped in <code> tags. 2. wp-config.php wrapped in <strong> tags. */
					esc_html__( 'Multisite is disabled on the Managed WordPress platform. Please remove %1$s from your %2$s file.', 'mwp-system-plugin' ),
					"<code>define( 'WP_ALLOW_MULTISITE', true );</code>",
					'<strong>wp-config.php</strong>'
				)
			)
		);

	}

	/**
	 * Configue PHPMailer to use the SMTP relay.
	 *
	 * @since 1.0.0
	 */
	private function phpmailer() {

		add_action( 'phpmailer_init', function ( $phpmailer ) {

			$phpmailer->isSMTP();
			$phpmailer->Host = 'relay-hosting.secureserver.net';
			$phpmailer->addCustomHeader( 'X-MWP2-Site-Uid', Config::get( 'site_uid', null ) );
			$phpmailer->addCustomHeader( 'X-MWP2-Account-Uid', Config::get( 'account_uid', null ) );
			$phpmailer->addCustomHeader( 'X-MWP2-Shopper-Id', Config::get( 'shopper_id', null ) );

		}, PHP_INT_MAX );

	}

	/**
	 * Prevent existing options from being changed or deleted.
	 *
	 * @since 1.0.0
	 *
	 * @param array $protected
	 */
	private function protect_options( array $protected ) {

		// Don't restrict users in CLI mode.
		if ( System::is_wp_cli() ) {

			return;

		}

		// Prevent existing options from being changed.
		add_filter( 'pre_update_option', function ( $new_value, $option, $old_value ) use ( $protected ) {

			return ( ! in_array( $option, $protected, true ) || ! $old_value ) ? $new_value : $old_value;

		}, PHP_INT_MAX, 3 );

		// Prevent existing options from being deleted.
		add_action( 'delete_option', function ( $option ) use ( $protected ) {

			if ( ! in_array( $option, $protected, true ) ) {

				return;

			}

			$value = get_option( $option );

			add_action( "delete_option_{$option}", function () use ( $value ) {

				update_option( $option, $value );

			}, PHP_INT_MAX );

		}, PHP_INT_MAX );

	}

	/**
	 * Disable WP Cron cron by default.
	 *
	 * @since 1.0.0
	 */
	private function wp_cron() {

		// @TODO: Disable WP Cron once MCMS-411 is complete.

		/*
		if ( ! defined( 'DISABLE_WP_CRON' ) ) {

			define( 'DISABLE_WP_CRON', true );

		}
		*/

		$blacklist = [ 'wp_maybe_auto_update', 'wp_version_check' ];

		// Immediately unschedule a blacklisted event should it ever appear as scheduled.
		foreach ( $blacklist as $event ) {

			if ( false !== wp_next_scheduled( $event ) ) {

				wp_clear_scheduled_hook( $event );

			}

		}

		// Prevent blacklisted events from being scheduled.
		add_filter( 'schedule_event', function( $event ) use ( $blacklist ) {

			return ( ! isset( $event->hook ) || in_array( $event->hook, $blacklist, true ) ) ? false : $event;

		}, PHP_INT_MAX );

		// Make sure auto-updates are still triggered since `wp_version_check` is blacklisted.
		add_action( 'wp_update_plugins', [ $this, 'wp_maybe_auto_update' ], 11 );
		add_action( 'wp_update_themes',  [ $this, 'wp_maybe_auto_update' ], 11 );

	}

	/**
	 * Trigger auto-updates during WP Cron.
	 *
	 * WordPress will only trigger auto-updates during the `wp_version_check`
	 * event, which we disable because core version checking is irrelevant on
	 * our managed platform.
	 *
	 * @action wp_update_plugins
	 * @action wp_update_themes
	 * @link   https://github.com/WordPress/WordPress/blob/4.8-branch/wp-includes/update.php#L183
	 * @since  1.0.0
	 */
	public function wp_maybe_auto_update() {

		if ( wp_doing_cron() && ! doing_action( 'wp_maybe_auto_update' ) ) {

			do_action( 'wp_maybe_auto_update' );

		}

	}

	/**
	 * Redirect method.
	 *
	 * @param string $url URL to redirect user to.
	 *
	 * @since 1.0.0
	 *
	 * @codeCoverageIgnore
	 */
	protected function call_redirect( $url ) {

		wp_safe_redirect( $url );

		exit;

	}

}
