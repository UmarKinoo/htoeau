/**
 * Set htoeau_display_ccy from URL hash (avoids ?query= which some hosts block with 503).
 */
( function () {
	'use strict';

	var match = ( window.location.hash || '' ).match( /^#htoeau_ccy=(GBP|EUR)$/i );
	if ( ! match ) {
		return;
	}

	var code = match[1].toUpperCase();
	var name = 'htoeau_display_ccy';
	var existing = document.cookie.split( ';' ).some( function ( part ) {
		return part.trim().indexOf( name + '=' ) === 0;
	} );

	if ( existing ) {
		var current = document.cookie
			.split( ';' )
			.map( function ( p ) {
				return p.trim();
			} )
			.filter( function ( p ) {
				return p.indexOf( name + '=' ) === 0;
			} )[0];
		if ( current && current.split( '=' )[1] === code ) {
			if ( window.history && window.history.replaceState ) {
				window.history.replaceState( null, '', window.location.pathname + window.location.search );
			} else {
				window.location.hash = '';
			}
			return;
		}
	}

	var secure = window.location.protocol === 'https:' ? '; Secure' : '';
	var clear = '; path=/; max-age=0; SameSite=Lax' + secure;
	document.cookie = 'yay_currency_widget=' + clear;
	document.cookie = 'yay_currency=' + clear;
	document.cookie =
		name + '=' + code + '; path=/; max-age=31536000; SameSite=Lax' + secure;

	if ( window.history && window.history.replaceState ) {
		window.history.replaceState( null, '', window.location.pathname + window.location.search );
	} else {
		window.location.hash = '';
	}

	window.location.reload();
} )();
