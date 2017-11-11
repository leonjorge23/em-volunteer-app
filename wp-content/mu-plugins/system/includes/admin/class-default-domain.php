<?php

namespace WAPaaS\MWP\Admin;

use WAPaaS\MWP\Config;
use WAPaaS\MWP\System;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

final class Default_Domain {

	/**
	 * Account domains URL.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private $hui_domains_url;

	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		$this->hui_domains_url = Config::get( 'hui_domains_url' );

		$uri = filter_input( INPUT_SERVER, 'REQUEST_URI', FILTER_SANITIZE_STRING );

		if ( admin_url( 'options-general.php' ) === home_url( $uri ) ) {

			add_action( 'admin_enqueue_scripts', [ $this, 'options_general_enqueue_scripts' ] );

		}

		if ( admin_url( 'options-reading.php' ) === home_url( $uri ) ) {

			add_action( 'admin_enqueue_scripts', [ $this, 'options_reading_enqueue_scripts' ] );

		}

		if ( ! System::has_custom_domain() ) {

			add_action( 'admin_notices', [ $this, 'admin_notice' ] );

		}

	}

	/**
	 * Disable the `siteurl` and `home` fields in Settings > General.
	 *
	 * @action admin_enqueue_scripts
	 * @since  1.0.0
	 *
	 * @param string $hook
	 */
	public function options_general_enqueue_scripts( $hook ) {

		if ( 'options-general.php' !== $hook ) {

			return;

		}

		$rtl    = is_rtl() ? '-rtl' : '';
		$suffix = SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_style(
			'mwp-system-inline-notice',
			System::ASSETS_URL . "css/inline-notice{$rtl}{$suffix}.css",
			[],
			System::VERSION
		);

		wp_enqueue_script(
			'mwp-system-options-general',
			System::ASSETS_URL . "js/options-general{$suffix}.js",
			[ 'jquery' ],
			System::VERSION,
			true
		);

		$notice = sprintf(
			/* translators: Title of admin notice in bold */
			__( "%s Your site domain can't be changed here.", 'mwp-system-plugin' ),
			sprintf( '<strong>%s</strong>', esc_html__( 'Note:', 'mwp-system-plugin' ) )
		);

		if ( $this->hui_domains_url ) {

			$notice .= sprintf(
				' <a href="%s" target="_blank">%s<span class="dashicons dashicons-external" style="width: 16px; height: 16px; margin: 1px 0 0 3px; font-size: 16px; text-decoration: none;"></span>',
				esc_url( $this->hui_domains_url ),
				esc_html__( 'Change domain', 'mwp-system-plugin' )
			);

		}

		if ( System::is_child_site() ) {

			$notice = sprintf(
				/* translators: Title of admin notice in bold */
				__( "%s Your staging site domain can't be changed.", 'mwp-system-plugin' ),
				sprintf( '<strong>%s</strong>', esc_html__( 'Note:', 'mwp-system-plugin' ) )
			);

		}

		wp_localize_script(
			'mwp-system-options-general',
			'mwp_system_options_general_vars',
			[
				'inline_notice_text' => esc_html( $notice ),
			]
		);

	}

	/**
	 * Disable the `blog_public` field in Settings > Reading.
	 *
	 * @action admin_enqueue_scripts
	 * @since  1.0.0
	 *
	 * @param string $hook
	 */
	public function options_reading_enqueue_scripts( $hook ) {

		if ( 'options-reading.php' !== $hook ) {

			return;

		}

		$notice = null;

		if ( System::is_child_site() ) {

			$notice = sprintf(
				/* translators: 'Note' wrapped in <strong> tags. */
				__( "%s Your staging site can't be indexed by search engines.", 'mwp-system-plugin' ),
				sprintf( '<strong>%s</strong>', esc_html__( 'Note:', 'mwp-system-plugin' ) )
			);

		}

		if ( $this->hui_domains_url ) {

			$change_domain_link = sprintf(
				' <a href="%s" target="_blank">%s<span class="dashicons dashicons-external" style="width: 16px; height: 16px; margin: 1px 0 0 3px; font-size: 16px; text-decoration: none;"></span>',
				esc_url( $this->hui_domains_url ),
				esc_html__( 'Change domain', 'mwp-system-plugin' )
			);

		}

		if ( ! System::has_custom_domain() ) {

			$notice = sprintf(
				/* translators: 1. 'Note' wrapped in <strong> tags. 2. Link to external site where domain can be changed. */
				__( "%1\$s Temporary domains can't be indexed by search engines. %2\$s", 'mwp-system-plugin' ),
				sprintf( '<strong>%s</strong>', esc_html__( 'Note:', 'mwp-system-plugin' ) ),
				! empty( $change_domain_link ) ? $change_domain_link : ''
			);

		}

		if ( ! $notice ) {

			return;

		}

		$rtl    = is_rtl() ? '-rtl' : '';
		$suffix = SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_style(
			'mwp-system-inline-notice',
			System::ASSETS_URL . "css/inline-notice{$rtl}{$suffix}.css",
			[],
			System::VERSION
		);

		wp_enqueue_script(
			'mwp-system-options-reading',
			System::ASSETS_URL . "js/options-reading{$suffix}.js",
			[ 'jquery' ]
		);

		wp_localize_script(
			'mwp-system-options-reading',
			'mwp_system_options_reading_vars',
			[
				'blog_public_notice_text' => esc_html( $notice ),
			]
		);

	}

	/**
	 * Display an admin notice when the default domain is set as primary.
	 *
	 * @action admin_notices
	 * @since  1.0.0
	 */
	public function admin_notice() {

		if ( $this->hui_domains_url ) {

			$change_domain_link = sprintf(
				' <a href="%1$s">%2$s<span class="dashicons dashicons-external" style="width: 16px; height: 16px; margin: 1px 0 0 3px; font-size: 16px; text-decoration: none;"></span></a>',
				esc_url( $this->hui_domains_url ),
				esc_html__( 'Change domain', 'mwp-system-plugin' )
			);

		}

		printf(
			'<div class="notice notice-warning">
				<p>%s</p>
			</div>',
			wp_kses_post( sprintf(
				/* translators: 1. 'Note' wrapped in <strong> tags. 2. Link to external site where domain can be changed. */
				__( '%1$s Your site is still using a temporary domain: %2$s %3$s', 'mwp-system-plugin' ),
				sprintf( '<strong>%s</strong>', esc_html__( 'Note:', 'mwp-system-plugin' ) ),
				esc_url( Config::get( 'default_site_url' ) ),
				! empty( $change_domain_link ) ? $change_domain_link : ''
			) )
		);

	}

}
