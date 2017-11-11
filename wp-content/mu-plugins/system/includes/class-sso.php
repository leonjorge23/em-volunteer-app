<?php

namespace WAPaaS\MWP;

use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

final class SSO {

	/**
	 * Name of error cookie.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const ERROR_COOKIE = 'mwp_system_sso_error';

	/**
	 * Action name in the request.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private $action;

	/**
	 * SSO token in the request.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private $token;

	/**
	 * The request URI.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	private $uri;

	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		add_filter( 'wp_login_errors',   [ $this, 'wp_login_errors' ] );
		add_filter( 'shake_error_codes', [ $this, 'shake_error_codes' ] );

		$this->action = filter_input( INPUT_GET, 'mwp-action', FILTER_SANITIZE_STRING );
		$this->token  = filter_input( INPUT_GET, 'mwp-token', FILTER_SANITIZE_STRING );
		$this->token  = ( $this->token ) ? $this->token : filter_input( INPUT_SERVER, 'HTTP_AUTHORIZATION', FILTER_SANITIZE_STRING );
		$this->uri    = filter_input( INPUT_SERVER, 'REQUEST_URI', FILTER_SANITIZE_STRING );

		if ( 'sso' === $this->action && $this->token && preg_match( '~^/wp-(?:admin/|login\.php)~', $this->uri ) ) {

			add_action( 'init', [ $this, 'init' ], 0 );

		}

	}

	/**
	 * Initialize script.
	 *
	 * @action init
	 * @since  1.0.0
	 */
	public function init() {

		$redirect = remove_query_arg( [ 'mwp-action', 'mwp-token' ], home_url( $this->uri ) );
		$redirect = (bool) preg_match( '~^/wp-login\.php~', $this->uri ) ? self_admin_url() : $redirect;

		if ( current_user_can( 'administrator' ) ) {

			wp_safe_redirect( esc_url_raw( $redirect ) );

			exit;

		}

		$user_id = $this->get_user_id();

		if ( $user_id && self::is_valid_token( $this->token ) ) {

			wp_set_auth_cookie( $user_id );

			wp_safe_redirect( esc_url_raw( $redirect ) );

			exit;

		}

		setcookie( self::ERROR_COOKIE, time(), 0, '/wp-login.php', COOKIE_DOMAIN, is_ssl() );

		$redirect = ( self_admin_url() === $redirect ) ? '' : $redirect;

		wp_safe_redirect( esc_url_raw( wp_login_url( $redirect ) ) );

		exit;

	}

	/**
	 * Return the ID of the user to be logged in.
	 *
	 * @since 1.0.0
	 *
	 * @return int|false
	 */
	private function get_user_id() {

		$primary_user_id = (int) Option::get( 'primary_user_id', 1 );

		$user = get_user_by( 'ID', $primary_user_id );

		if ( $user && in_array( 'administrator', $user->roles, true ) ) {

			return $user->ID;

		}

		// Get the oldest administrator as a fallback.
		$user = get_users( [
			'number'  => 1,
			'role'    => 'administrator',
			'orderby' => 'registered',
			'order'   => 'ASC',
		] );

		return ! empty( $user[0]->ID ) ? $user[0]->ID : false;

	}

	/**
	 * Check whether a SSO token is valid.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $token
	 *
	 * @return bool
	 */
	public static function is_valid_token( $token ) {

		// Support for tokens passed through an Authorization header.
		$token = trim( preg_replace( '/^Token\s/i', '', $token ) );

		if ( 36 !== strlen( $token ) ) {

			error_log( 'mwp_system_invalid_sso_token_length' );

			return false;

		}

		$site_uid = Config::get( 'site_uid' );

		if ( ! preg_match( '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/', $site_uid ) ) {

			error_log( 'mwp_system_invalid_site_uid_format' );

			return false;

		}

		$response = API::post( "sites/{$site_uid}/sso/{$token}" );

		if ( is_wp_error( $response ) ) {

			error_log( sprintf( '%s:%s', $response->get_error_code(), $response->get_error_data() ) );

			return false;

		}

		if ( empty( $response['successful'] ) ) {

			error_log( 'mwp_system_invalid_sso_token' );

			return false;

		}

		return true;

	}

	/**
	 * Check if there were any SSO errors.
	 *
	 * @filter wp_login_errors
	 * @since  1.0.0
	 *
	 * @param  WP_Error $errors
	 *
	 * @return WP_Error
	 */
	public function wp_login_errors( $errors ) {

		if ( isset( $_COOKIE[ self::ERROR_COOKIE ] ) ) {

			$errors->add( 'mwp_sso_error', esc_html__( 'We were unable to log you in automatically. Please enter your WordPress username and password.', 'mwp-system-plugin' ) );

			setcookie( self::ERROR_COOKIE, null, 0, '/wp-login.php', COOKIE_DOMAIN, is_ssl() );

		}

		return $errors;

	}

	/**
	 * Add our custom error message to the shaking messages.
	 *
	 * @filter shake_error_codes
	 * @since  1.0.0
	 *
	 * @param  array $codes
	 *
	 * @return array
	 */
	public function shake_error_codes( $codes ) {

		$codes[] = 'mwp_sso_error';

		return $codes;

	}

}
