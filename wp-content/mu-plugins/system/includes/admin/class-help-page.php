<?php

namespace WAPaaS\MWP\Admin;

use WAPaaS\MWP\Config;
use WAPaaS\MWP\System;

if ( ! defined( 'ABSPATH' ) ) {

	exit;

}

final class Help_Page {

	/**
	 * Page slug.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	const PAGE_SLUG = 'mwp-system-help';

	/**
	 * Class constructor.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {

		add_action( 'init', [ $this, 'init' ] );

	}

	/**
	 * Check if the current user can view the Help Page.
	 *
	 * @since 1.0.0
	 *
	 * @return bool
	 */
	public static function is_viewable() {

		$iframe_url = self::get_iframe_url();
		$user_cap   = Config::get( 'admin_help_page_user_cap', 'install_plugins' );

		return ( $iframe_url && current_user_can( $user_cap ) );

	}

	/**
	 * Initialize the page.
	 *
	 * @action init
	 * @since  1.0.0
	 */
	public function init() {

		if ( ! self::is_viewable() ) {

			return;

		}

		add_action( 'admin_menu', [ $this, 'register_page' ] );

		$uri = filter_input( INPUT_SERVER, 'REQUEST_URI', FILTER_SANITIZE_STRING );

		if ( self::get_url() === home_url( $uri ) ) {

			add_action( 'admin_enqueue_scripts', [ $this, 'admin_enqueue_scripts' ] );

		}

	}

	/**
	 * Register menu page.
	 *
	 * @action admin_menu
	 * @since  1.0.0
	 */
	public function register_page() {

		add_menu_page(
			null,
			null,
			'install_plugins',
			self::PAGE_SLUG,
			[ $this, 'page_contents' ],
			'dashicons-sos'
		);

		// Don't add it to the menu.
		remove_menu_page( self::PAGE_SLUG );

	}

	/**
	 * Display the page contents.
	 *
	 * @since 1.0.0
	 */
	public function page_contents() {

		?>
		<div class="wrap">

			<h1><?php esc_html_e( 'Help &amp; Support', 'mwp-system-plugin' ); ?></h1>

			<iframe src="<?php echo esc_url( self::get_iframe_url() ); ?>" frameborder="0" scrolling="no" style="width:100%; min-height:800px; margin-top:20px;"></iframe>

			<script type="text/javascript">
			iFrameResize( {
				bodyBackground: 'transparent',
				checkOrigin: false,
				heightCalculationMethod: 'taggedElement'
			} );
			</script>

		</div>
		<?php

	}

	/**
	 * Return the Help Page URL.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public static function get_url() {

		return admin_url( 'admin.php?page=' . self::PAGE_SLUG );

	}

	/**
	 * Return the Help Page iframe source URL.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	private static function get_iframe_url() {

		$default    = Config::get( 'admin_help_page_default_subdomain', 'www' );
		$subdomain  = self::get_market_subdomain( $default );
		$iframe_url = Config::get( 'admin_help_page_iframe_url', '' );

		return str_replace( '{market_subdomain}', $subdomain, $iframe_url );

	}

	/**
	 * Return a market subdomain.
	 *
	 * @since 1.0.0
	 *
	 * @param  string $default (optional) The subdomain to use when the locale is `en_US`.
	 *
	 * @return string
	 */
	private static function get_market_subdomain( $default = 'www' ) {

		$lang      = get_option( 'WPLANG', $default );
		$parts     = explode( '_', $lang );
		$subdomain = ! empty( $parts[1] ) ? strtolower( $parts[1] ) : strtolower( $lang );

		// Special overrides.
		switch ( $subdomain ) {

			case '' :

				$subdomain = $default; // Default

				break;

			case 'uk' :

				$subdomain = 'ua'; // Ukrainian (Українська)

				break;

			case 'el' :

				$subdomain = 'gr'; // Greek (Ελληνικά)

				break;

		}

		return $subdomain;

	}

	/**
	 * Enqueue admin styles.
	 *
	 * @action admin_enqueue_scripts
	 * @since  1.0.0
	 *
	 * @param string $hook
	 */
	public function admin_enqueue_scripts( $hook ) {

		if ( 'toplevel_page_' . self::PAGE_SLUG !== $hook ) {

			return;

		}

		wp_enqueue_script(
			'mwp-system-iframeresizer',
			System::ASSETS_URL . 'js/iframeResizer.min.js',
			[],
			'3.5.1',
			false
		);

		wp_enqueue_script(
			'mwp-system-iframeresizer-ie8',
			System::ASSETS_URL . 'js/iframeResizer.ie8.polyfils.min.js',
			[],
			'3.5.1',
			false
		);

		wp_script_add_data( 'mwp-system-iframeresizer-ie8', 'conditional', 'lte IE 8' );

	}

}
