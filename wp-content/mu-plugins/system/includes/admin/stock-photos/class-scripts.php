<?php

namespace WAPaaS\MWP\Admin;

use WAPaaS\MWP\Config;
use WAPaaS\MWP\System;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

final class Scripts {

	private $api;

	public function __construct( API $api ) {

		$this->api = $api;

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ], PHP_INT_MAX );
		add_action( 'wp_enqueue_scripts',    [ $this, 'enqueue_scripts' ], PHP_INT_MAX );

	}

	public function enqueue_scripts() {

		/**
		 * No need to enqueue stock photo is media-views dependency is not there
		 */
		if ( ! wp_script_is( 'media-views', 'enqueued' ) ) {

			if ( ! is_customize_preview() ) {

				return;

			} // @codingStandardsIgnoreLine

		}

		$suffix = SCRIPT_DEBUG ? '' : '.min';
		$rtl    = ! is_rtl() ? '' : '-rtl';

		wp_enqueue_script( 'wpaas-stock-photos', System::ASSETS_URL . "/js/stock-photos/stock-photos{$suffix}.js", [ 'media-views' ], System::VERSION, true );
		wp_enqueue_style( 'wpaas-stock-photos', System::ASSETS_URL . "/css/stock-photos{$rtl}{$suffix}.css", [ 'media-views' ], System::VERSION, 'all' );

		$choices = $this->api->get_d3_choices();

		if ( ! $this->api->is_d3_locale() || ! $choices ) {

			$choices = $this->api->get_d3_categories_fallback();

		}

		array_shift( $choices );

		$choices = [
			'generic' => __( 'Generic', 'mwp-system-plugin' ),
		] + $choices;

		$tos_url = Config::get( 'tos_url' );

		switch ( true ) {

			case empty( $tos_url ) :

				$image_license = __( 'Images available and licensed for use are intended for our hosted customers only and are subject to the terms and conditions of third-party intellectual property rights.', 'mwp-system-plugin' );

				break;

			case ( 1 === (int) Config::get( 'plid' ) ) :

				$image_license = sprintf(
					/* translators: URL to terms and conditions. */
					__( 'Images available and licensed for use are intended for GoDaddy hosted customers only and are subject to the terms and conditions of third-party intellectual property rights. <a href="%s" target="_blank">See Terms and Conditions</a> for additional details.', 'mwp-system-plugin' ),
					esc_url( $tos_url )
				);

				break;

			default :

				$image_license = sprintf(
					/* translators: URL to terms and conditions. */
					__( 'Images available and licensed for use are intended for our hosted customers only and are subject to the terms and conditions of third-party intellectual property rights. <a href="%s" target="_blank">See Terms and Conditions</a> for additional details.', 'mwp-system-plugin' ),
					esc_url( $tos_url )
				);

		}

		wp_localize_script(
			'wpaas-stock-photos',
			'wpaas_stock_photos',
			[
				'menu_title'        => __( 'Stock Photos', 'mwp-system-plugin' ),
				'filter_label'      => __( 'Change category', 'mwp-system-plugin' ),
				'cat_choices'       => $choices,
				'no_images'         => __( 'No stock photos found.', 'mwp-system-plugin' ),
				'preview_btn'       => __( 'Preview', 'mwp-system-plugin' ),
				'import_btn'        => __( 'Import', 'mwp-system-plugin' ),
				'back_btn'          => __( 'Back', 'mwp-system-plugin' ),
				'license_text'      => __( 'About Image Licenses', 'mwp-system-plugin' ),
				'no_results_filter' => __( 'No results found.' ),
				'license_details'   => $image_license,
			]
		);

	}

}
