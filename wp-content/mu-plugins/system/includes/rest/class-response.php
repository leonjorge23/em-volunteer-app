<?php

namespace WAPaaS\MWP\REST;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

final class Response {

	/**
	 * Return a custom plugin response array.
	 *
	 * @since 1.0.0
	 *
	 * @param  string   $basename
	 * @param  stdClass $plugin   (optional)
	 *
	 * @return array
	 */
	public static function plugin_data( $basename, $plugin = null ) {

		$plugin = (object) $plugin;
		$plugin = isset( $plugin->Name ) ? $plugin : (object) get_plugin_data( $basename, false ); // @codingStandardsIgnoreLine

		preg_match( '/^.+?(?=\/|\.php$)/', $basename, $matches );

		$response = [
			'name'     => $plugin->Name,   // @codingStandardsIgnoreLine
			'author'   => $plugin->Author, // @codingStandardsIgnoreLine
			'slug'     => ! empty( $matches[0] ) ? $matches[0] : $basename,
			'basename' => $basename,
			'version'  => $plugin->Version, // @codingStandardsIgnoreLine
			'status'   => array_key_exists( $basename, (array) get_option( 'active_plugins', [] ) ) ? 'active' : 'inactive',
			'type'     => 'plugin',
		];

		if ( isset( $plugin->update->new_version ) ) {

			$response['new_version'] = $plugin->update->new_version;

		}

		ksort( $response );

		return $response;

	}

	/**
	 * Return a custom theme response array.
	 *
	 * @since 1.0.0
	 *
	 * @param  string   $stylesheet (optional)
	 * @param  WP_Theme $theme      (optional)
	 *
	 * @return array
	 */
	public static function theme_data( $stylesheet = null, $theme = null ) {

		$theme = is_a( $theme, 'WP_Theme' ) ? $theme : wp_get_theme( $stylesheet );

		$response = [
			'name'    => $theme->get( 'Name' ),
			'author'  => $theme->get( 'Author' ),
			'slug'    => $theme->get_stylesheet(),
			'parent'  => $theme->get_template(),
			'version' => $theme->get( 'Version' ),
			'status'  => ( $theme->get_stylesheet() === get_stylesheet() ) ? 'active' : ( $theme->get_template() === get_template() ? 'parent' : 'inactive' ),
			'type'    => 'theme',
		];

		if ( isset( $theme->update['new_version'] ) ) {

			$response['new_version'] = $theme->update['new_version'];

		}

		ksort( $response );

		return $response;

	}

	/**
	 * Remove certain fields from a response.
	 *
	 * @since 1.0.0
	 *
	 * @param  array|object $response
	 * @param  array        $fields
	 *
	 * @return array
	 */
	public static function filter_fields( $response, array $fields ) {

		$array = isset( $response[0] ) ? $response : [ $response ];

		$excluded = array_keys( array_filter( $fields, function ( $value ) {

			return ( ! $value );

		} ) );

		foreach ( $array as &$_array ) {

			foreach ( $excluded as $field ) {

				unset( $_array[ $field ] );

			}

		}

		return isset( $response[0] ) ? $array : $array[0];

	}

}
