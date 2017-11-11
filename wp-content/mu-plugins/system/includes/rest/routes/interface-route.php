<?php

namespace WAPaaS\MWP\REST\Routes;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

interface Route {

	public function __construct( $route );

}
