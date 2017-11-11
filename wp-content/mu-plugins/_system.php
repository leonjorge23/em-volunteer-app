<?php
/**
 * Plugin Name: System Plugin
 * Version: 1.0.0
 * Description: Must-use system plugin for Managed WordPress hosting.
 */

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

$path = WPMU_PLUGIN_DIR . '/system/system.php';

if ( is_readable( $path ) ) {

	require_once $path;

}
