<?php
/**
 * Load APCu Object Cache.
 */

if ( defined( 'ABSPATH' ) && ( ! defined( 'WP_OBJECT_CACHE' ) || WP_OBJECT_CACHE ) && is_readable( WP_CONTENT_DIR . '/mu-plugins/system/lib/apcu-object-cache.php' ) ) {

	require_once WP_CONTENT_DIR . '/mu-plugins/system/lib/apcu-object-cache.php';

}
