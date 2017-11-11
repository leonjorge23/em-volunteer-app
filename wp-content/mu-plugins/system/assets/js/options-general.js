/* global jQuery, mwp_system_options_general_vars */

jQuery( document ).ready( function( $ ) {

	'use strict';

	$( '#siteurl, #home' )
		.prop( 'readonly', true )
		.addClass( 'disabled' )
		.css( 'cursor', 'not-allowed' );

	var $notice = $( '<div class="mwp-system-inline-notice mwp-system-inline-notice-warning"></div>' );

	$notice.html( mwp_system_options_general_vars.inline_notice_text );

	$( '#home-description' )
		.after( $notice )
		.after( '<div class="clear"></div>' )
		.hide();

} );
