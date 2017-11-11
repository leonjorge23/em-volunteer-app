<?php

namespace WAPaaS\MWP;

use WP_Query;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

final class Integrations {

	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		add_filter( 'mwp_system_config_nocache', function ( $keys ) {

			$keys[] = 'gravity_forms_api_key';
			$keys[] = 'gravity_forms_api_url';
			$keys[] = 'gravity_forms_client_key';
			$keys[] = 'wp101_api_key';

			return $keys;

		} );

		add_action( 'plugins_loaded', function () {

			$this->gem();

			$this->gravity_forms();

			$this->jetpack();

			$this->wp101();

		}, 0 );

		if ( ! defined( 'GRAVITY_MANAGER_URL' ) && preg_match( '/\.(dev|test)-/', Config::get( 'api_url' ) ) ) {

			define( 'GRAVITY_MANAGER_URL', 'http://dev.gravityapi.com/wp-content/plugins/gravitymanager' );

		}

	}

	/**
	 * GoDaddy Email Marketing.
	 *
	 * @since 1.0.0
	 */
	private function gem() {

		if ( ! class_exists( 'GEM_Official' ) ) {

			return;

		}

		add_filter( 'gem_api_base_url', function( $url ) {

			preg_match( '/\.(\w+)-godaddy\.com/', wp_parse_url( Config::get( 'api_url' ), PHP_URL_HOST ), $matches );

			$env = ! empty( $matches[1] ) ? $matches[1] : null;

			return ( $env ) ? sprintf( 'https://gem.%s-godaddy.com/', $env ) : $url;

		} );

	}

	/**
	 * Gravity Forms.
	 *
	 * @since 1.0.0
	 */
	private function gravity_forms() {

		if ( ! class_exists( 'GFForms' ) ) {

			return;

		}

		$api_key    = Config::get( 'gravity_forms_api_key' );
		$client_key = Config::get( 'gravity_forms_client_key' );

		if ( ! $api_key || ! $client_key ) {

			return;

		}

		if ( ! defined( 'GF_LICENSE_KEY' ) ) {

			define( 'GF_LICENSE_KEY', $api_key );

		}

		// So Gravity Forms can detect we are the client.
		if ( ! defined( 'GF_CLIENT_KEY' ) ) {

			define( 'GF_CLIENT_KEY', $client_key );

		}

		add_filter( 'http_request_args', function ( $args, $url ) use ( $client_key ) {

			if ( defined( 'GRAVITY_MANAGER_URL' ) && 0 === strpos( $url, GRAVITY_MANAGER_URL ) ) {

				// @codingStandardsIgnoreStart
				$args['headers']['X-Gravity-Client'] = base64_encode( $client_key );
				// @codingStandardsIgnoreEnd

			}

			return $args;

		}, PHP_INT_MAX, 2 );

	}

	/**
	 * Jetpack.
	 *
	 * @since 1.0.0
	 */
	private function jetpack() {

		if ( ! class_exists( 'Jetpack' ) ) {

			return;

		}

		// Hide the Jetpack updates screen nag.
		add_filter( 'option_jetpack_options', function ( $options ) {

			if ( empty( $options['hide_jitm']['manage'] ) || 'hide' !== $options['hide_jitm']['manage'] ) {

				$options['hide_jitm']['manage'] = 'hide';

			}

			return $options;

		} );

		// Prevent child sites from going into "identity crisis" mode.
		if ( System::is_child_site() ) {

			add_filter( 'jetpack_has_identity_crisis', '__return_false' );

		}

	}

	/**
	 * WP101 Tutorial Videos.
	 *
	 * @since 1.0.0
	 */
	private function wp101() {

		if ( ! class_exists( 'WP101_Video_Tutorial' ) ) {

			return;

		}

		$api_key = Config::get( 'wp101_api_key' );

		if ( $api_key && ! defined( 'GD_WP101_API_KEY' ) ) {

			define( 'GD_WP101_API_KEY', $api_key );

		}

	}

}
