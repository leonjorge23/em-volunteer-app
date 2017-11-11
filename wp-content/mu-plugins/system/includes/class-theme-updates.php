<?php

namespace WAPaaS\MWP;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

final class Theme_Updates {

	/**
	 * URL for fetching JSON theme data.
	 *
	 * @var string
	 */
	const URL = 'https://raw.githubusercontent.com/godaddy/wp-themes/master/manifest.min.json';

	/**
	 * Array of themes to check.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	private static $themes;

	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		if ( ! is_admin() && ! System::is_wp_cli() ) {

			return;

		}

		add_action( 'init', [ $this, 'init' ] );

	}

	/**
	 * Initialize the page.
	 *
	 * @action init
	 * @since  1.0.0
	 */
	public function init() {

		if ( ! current_user_can( 'update_themes' ) ) {

			return;

		}

		self::$themes = $this->get_themes();

		add_filter( 'pre_set_site_transient_update_themes', [ $this, 'update_themes' ], PHP_INT_MAX, 2 );

	}

	/**
	 * Return an array of updatable themes.
	 *
	 * @since 1.0.0
	 *
	 * @return array
	 */
	public function get_themes() {

		$themes = get_site_transient( 'mwp_system_theme_updates' );

		if ( false !== $themes ) {

			return (array) $themes;

		}

		$response = wp_remote_get( esc_url_raw( self::URL ) );

		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {

			return [];

		}

		$response = (array) json_decode( trim( wp_remote_retrieve_body( $response ) ), true );

		$themes = wp_list_pluck( $response, 'theme' );

		set_site_transient( 'mwp_system_theme_updates', $themes, MONTH_IN_SECONDS );

		return $themes;

	}

	/**
	 * Intercept the transient that holds available theme updates.
	 *
	 * @filter pre_set_site_transient_update_themes
	 * @since  1.0.0
	 *
	 * @param stdClass $value
	 */
	public function update_themes( $value ) {

		if ( ! is_a( $value, 'stdClass' ) || ! property_exists( $value, 'checked' ) || ! is_array( $value->checked ) ) {

			return $value;

		}

		// We only care about checking themes if they are installed.
		$installed = array_intersect( self::$themes, array_keys( $value->checked ) );

		if ( ! $installed ) {

			return $value;

		}

		static $theme_data;

		if ( ! $theme_data ) {

			// Ensure data is only fetched once per page load.
			$theme_data = $this->fetch_theme_data( $installed );

		}

		foreach ( $theme_data as $data ) {

			list( $theme, $new_version ) = array_values( $data );

			$wp_org_new_version = $this->check_wp_org_version( $value->response, $theme );

			// If a dot org update is the same or newer than ours, skip and use that.
			if ( version_compare( $wp_org_new_version, $new_version, '>=' ) ) {

				continue;

			}

			if ( version_compare( $new_version, $value->checked[ $theme ], '>' ) ) {

				$value->response[ $theme ] = $data;

			} // @codingStandardsIgnoreLine

		}

		return $value;

	}

	/**
	 * Return an array of fetched theme data for specific themes.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $themes
	 *
	 * @return array
	 */
	private function fetch_theme_data( array $themes ) {

		$response = wp_remote_get( add_query_arg( 'ver', time(), esc_url_raw( self::URL ) ) );

		if ( 200 !== wp_remote_retrieve_response_code( $response ) ) {

			return [];

		}

		$response = (array) json_decode( trim( wp_remote_retrieve_body( $response ) ), true );

		return array_filter( $response, function ( $data ) use ( $themes ) {

			return ( $this->is_valid_theme_data( $data ) && in_array( $data['theme'], $themes, true ) );

		} );

	}

	/**
	 * Check if theme data is valid.
	 *
	 * @since 1.0.0
	 *
	 * @param  array $data
	 *
	 * @return bool
	 */
	private function is_valid_theme_data( $data ) {

		return ( ! empty( $data['theme'] ) && ! empty( $data['new_version'] ) && ! empty( $data['url'] ) && ! empty( $data['package'] ) );

	}

	/**
	 * Check if wordpress.org has already reported an update available.
	 *
	 * @since 1.0.0
	 *
	 * @param  array  $response
	 * @param  string $theme
	 *
	 * @return string
	 */
	private function check_wp_org_version( $response, $theme ) {

		if (
			! empty( $response[ $theme ]['new_version'] )
			&&
			! empty( $response[ $theme ]['package'] )
			&&
			false !== strpos( $response[ $theme ]['package'], 'wordpress.org' )
		) {

			return $response[ $theme ]['new_version'];

		}

		return '0';

	}

}
