/* global jQuery, mwp_system_options_reading_vars */

jQuery( document ).ready( function( $ ) {

	'use strict';

	$( '#blog_public').prop( 'disabled', true ).css( 'cursor', 'not-allowed' );

	var $notice = $( '<div class="mwp-system-inline-notice mwp-system-inline-notice-warning"></div>' );

	$notice.html( mwp_system_options_reading_vars.blog_public_notice_text );

	$( '.option-site-visibility p.description' )
		.after( $notice )
		.after( '<div class="clear"></div>' )
		.hide();

} );
