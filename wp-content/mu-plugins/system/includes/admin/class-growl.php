<?php

namespace WAPaaS\MWP\Admin;

use WAPaaS\MWP\System;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

final class Growl {

	/**
	 * Name of cookie.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const COOKIE = 'mwp_system_growl';

	/**
	 * Array of messages to display.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	private static $messages = [];

	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		add_action( 'init', [ $this, 'init' ] );

	}

	/**
	 * Check for growl messages awaiting in the cookie.
	 *
	 * @action init
	 * @since  1.0.0
	 */
	public function init() {

		self::$messages = ! empty( $_COOKIE[ self::COOKIE ] ) ? wp_unslash( $_COOKIE[ self::COOKIE ] ) : maybe_serialize( [] );
		self::$messages = array_unique( array_filter( maybe_unserialize( self::$messages ) ) );

		if ( ! self::$messages ) {

			return;

		}

		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_scripts' ] );
		add_action( 'wp_enqueue_scripts',    [ $this, 'enqueue_scripts' ] );
		add_action( 'admin_bar_menu',        [ $this, 'display' ] );

		// Clear cookie.
		setcookie( self::COOKIE, null, 0, SITECOOKIEPATH, COOKIE_DOMAIN, is_ssl() );

	}

	/**
	 * Enqueue scripts and styles.
	 *
	 * @action wp_enqueue_scripts
	 * @since  1.0.0
	 */
	public function enqueue_scripts() {

		$rtl    = is_rtl() ? '-rtl' : '';
		$suffix = SCRIPT_DEBUG ? '' : '.min';

		wp_enqueue_script( 'mwp-system-growl', System::ASSETS_URL . "js/jquery-gritter{$suffix}.js", [ 'jquery' ], '1.7.4' );

		wp_enqueue_style( 'mwp-system-growl', System::ASSETS_URL . "css/jquery-gritter{$rtl}{$suffix}.css", [], '1.7.4' );

	}

	/**
	 * Display any system messages to the user.
	 *
	 * @action admin_bar_menu
	 * @since  1.0.0
	 */
	public function display() {

		?>
		<script type="text/javascript">
			jQuery( document ).ready( function( $ ) {
				<?php foreach ( self::$messages as $message ) : ?>
					$.gritter.add( {
						title: "<?php echo esc_js( __( 'Success', 'mwp-system-plugin' ) ); ?>",
						text: "<?php echo esc_js( $message ); ?>",
						time: <?php echo absint( 5 * 1000 ); ?>
					} );
				<?php endforeach; ?>
			} );
		</script>
		<?php

	}

	/**
	 * Add a message to be displayed to the user.
	 *
	 * @since 1.0.0
	 *
	 * @param string $message
	 */
	public static function add( $message ) {

		self::$messages[] = $message;

		setcookie( self::COOKIE, maybe_serialize( self::$messages ), 0, SITECOOKIEPATH, COOKIE_DOMAIN, is_ssl() );

	}

}
