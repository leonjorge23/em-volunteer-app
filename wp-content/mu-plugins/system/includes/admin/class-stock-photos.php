<?php

namespace WAPaaS\MWP\Admin;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

use WAPaaS\MWP\Config;

final class Stock_Photos {

	public function __construct() {

		if ( ! is_admin() || ( ! Config::get( 'stock_photos_d3_api_url' ) || ! Config::get( 'stock_photos_d3_api_key' ) ) ) {

			return;

		}

		require __DIR__ . '/stock-photos/class-api.php';
		require __DIR__ . '/stock-photos/class-scripts.php';
		require __DIR__ . '/stock-photos/class-import.php';
		require __DIR__ . '/stock-photos/class-ajax.php';

		$api      = new API();
		$scripts  = new Scripts( $api );
		$import   = new Import();
		$ajax     = new Ajax( $api, $import );

	}

}
