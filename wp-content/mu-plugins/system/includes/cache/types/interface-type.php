<?php

namespace WAPaaS\MWP\Cache\Types;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

interface Type {

	public static function flush();

}
