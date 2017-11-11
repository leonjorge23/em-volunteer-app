<?php

namespace WAPaaS\MWP\Admin;

use WAPaas\MWP\System;
use WAPaaS\MWP\Config;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

/**
 * Class API
 *
 * Handle fetching of image based on category from the D3 api
 */
final class API {

	/**
	 * Holds the API endpoints
	 *
	 * @var string
	 */
	private $image_cat_url;
	private $category_api_url;

	/**
	 * Hold transient base namespace
	 *
	 * @const string
	 */
	private $transient_base                  = 'wpaas_stock_photos_api_';
	private $transient_key_for_d3_categories = 'wpaas_stock_photos_d3_categories';

	/**
	 * Image_API constructor.
	 */
	public function __construct() {

		$d3_api_url = trailingslashit( Config::get( 'stock_photos_d3_api_url' ) );

		// Is this version 3 of the api?
		if ( false !== strpos( $d3_api_url, '/v3/' ) ) {

			$this->image_cat_url    = $d3_api_url . 'photos/en_us/latest/%s/';
			$this->category_api_url = $d3_api_url . 'categories/en_us/latest/';

			$this->transient_base                  .= 'v3_';
			$this->transient_key_for_d3_categories .= '_v3';

			return;

		}

		// Fallback to V2 of the API
		$this->image_cat_url    = $d3_api_url . 'stock_photos/category/%s/';
		$this->category_api_url = $d3_api_url . 'categories/';

	}

	/**
	 * Retrieve json response from one category and store it as a transient for later use
	 *
	 * @param string $cat
	 * @return object array of objects
	 */
	public function get_images_by_cat( $cat ) {

		if ( false === ( $category = $this->get_api_cat( $cat ) ) ) {

			return [];

		}

		// Check if we have a transient cached response for that call
		if ( $data = get_transient( $this->transient_base . $category ) ) {

			return $data;

		}

		if ( false === ( $data = $this->fetch_images( $category ) ) ) {

			return [];

		}

		shuffle( $data );

		set_transient( $this->transient_base . $category, $data, HOUR_IN_SECONDS );

		return $data;

	}

	public function get_d3_choices() {

		$categories = $this->get_d3_categories();

		if ( ! $categories ) {

			return [];

		}

		/* uncomment this if we ever want to filter out "top level categories"

		// to help ensure the user chooses the most relevant category to their business,
		// let's not include "top level categories"
		$categories = array_filter( $categories, function( $category ) {

			return count( $category['parents'] ) > 0;

		} );
		*/

		uasort( $categories, function( $a, $b ) {

			$pop_a = $a['popularity'];
			$pop_b = $b['popularity'];

			return ( $pop_a === $pop_b ) ? 0 : ( $pop_a > $pop_b ? -1 : 1 );

		} );

		$categories = wp_list_pluck( $categories, 'display_name' );
		$popular    = array_slice( $categories, 0, 50 );
		$others     = array_slice( $categories, 50 );

		natcasesort( $others );

		// Prepend an empty choice for Select2
		return [ '' => '' ] + $popular + $others;

	}



	/**
	 * Get and cache D3 categories from their API endpoint
	 * see https://d3.godaddy.com/api/v1/categories/
	 *
	 * @return false if api error, otherwise assoc array of category object's "str_id" => category object
	 */
	public function get_d3_categories() {

		// Check if we have a transient cached response for that call
		if ( $data = get_transient( $this->transient_key_for_d3_categories ) ) {

			return $data;

		}

		if ( $data = $this->fetch_d3_categories() ) {

			// can use slower cache expiry since the category api endpoint is updated very infrequently
			set_transient( $this->transient_key_for_d3_categories, $data, DAY_IN_SECONDS );

		}

		return $data;

	}

	public function get_d3_categories_fallback() {

		$list = [
			'professional'         => __( 'Business / Finance / Law', 'mwp-system-plugin' ),
			'graphicdesign'        => __( 'Design / Art / Portfolio', 'mwp-system-plugin' ),
			'education'            => __( 'Education', 'mwp-system-plugin' ),
			'health'               => __( 'Health / Beauty', 'mwp-system-plugin' ),
			'constructionservices' => __( 'Home Services / Construction', 'mwp-system-plugin' ),
			'massmedia'            => __( 'Music / Movies / Entertainment', 'mwp-system-plugin' ),
			'nonprofit'            => __( 'Non-profit / Causes / Religious', 'mwp-system-plugin' ),
			'generic_outdoors'     => __( 'Other', 'mwp-system-plugin' ),
			'pets'                 => __( 'Pets / Animals', 'mwp-system-plugin' ),
			'realestate'           => __( 'Real Estate', 'mwp-system-plugin' ),
			'restaurants'          => __( 'Restaurant / Food', 'mwp-system-plugin' ),
			'personal_sports'      => __( 'Sports / Recreation', 'mwp-system-plugin' ),
			'auto'                 => __( 'Transportation / Automotive', 'mwp-system-plugin' ),
			'travelservices'       => __( 'Travel / Hospitality / Leisure', 'mwp-system-plugin' ),
			'weddingphotographers' => __( 'Wedding', 'mwp-system-plugin' ),
		];

		return $list;

	}

	/**
	 * Helper to fetch categories from the API
	 *
	 * As an implementation detail, does some post processing of the raw API json response
	 *
	 * @return false if api error, otherwise assoc array of category object's "str_id" => category object
	 */
	private function fetch_d3_categories() {

		$categories = $this->fetch( $this->category_api_url );

		if ( ! $categories ) {

			return;

		}

		$output = [];

		foreach ( $categories as $i => $cat ) {

			$output[ $cat->str_id ] = [
				'display_name' => $cat->display_name,
				'popularity'   => $cat->popularity,
			];

		}

		return $output;

	}

	/**
	 * Check if the current locale can use d3.
	 *
	 * @return bool
	 */
	public function is_d3_locale() {

		return in_array( get_locale(), [ 'en_US', 'en_CA' ], true );

	}

	/**
	 * Get api category slug
	 *
	 * @param string $slug
	 *
	 * @return bool|string
	 */
	private function get_api_cat( $slug ) {

		$d3_categories = $this->get_d3_categories();

		if ( ! $d3_categories && array_key_exists( $slug, $this->get_d3_categories_fallback() ) ) {

			return $slug;

		}

		$d3_categories['generic'] = true;

		return isset( $d3_categories[ $slug ] ) ? $slug : false;

	}

	/**
	 * Helper to fetch infomation from the api
	 *
	 * @param string $url
	 * @return array|bool|mixed|object
	 */
	private function fetch( $url ) {

		$response = wp_remote_get(
			$url,
			[
				'headers' => [
					'Accept'        => 'application/json',
					'Authorization' => 'Token ' . Config::get( 'stock_photos_d3_api_key' ),
				],
			]
		);

		if ( is_wp_error( $response ) ) {

			return false;

		}

		return json_decode( wp_remote_retrieve_body( $response ) );

	}

	/**
	 * Helper function to fetch stock images from the API.
	 *
	 * When the given category has no stock photos, this function will be
	 * responsible for fetching the parent category's stock photo as a fallback.
	 *
	 * @param string $category a valid "str_id" slug from the category API
	 *
	 * @return false if api error, otherwise array of objects from the api
	 */
	private function fetch_images( $category ) {

		$json = $this->fetch( sprintf( $this->image_cat_url, $category ) );

		if ( false === $json ) {

			return false;

		}

		if ( $json->count > 0 ) {

			return $json->results;

		}

		if ( empty( $json->parent_category ) ) {

			return [];

		}

		return $this->fetch_images( $json->parent_category );

	}

}
