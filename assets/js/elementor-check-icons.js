/**
 * Replace Elementor Icon List Font Awesome check marks with HtoEAU circular check SVG.
 */
(function ( $ ) {
	'use strict';

	function buildIcon() {
		var span = document.createElement( 'span' );
		span.className = 'htoeau-check-icon';
		span.style.display = 'inline-flex';
		span.style.alignItems = 'center';
		span.style.justifyContent = 'center';
		span.style.flexShrink = '0';
		span.style.width = '24px';
		span.style.height = '24px';
		span.style.marginInlineEnd = '8px';
		span.setAttribute( 'aria-hidden', 'true' );
		span.innerHTML =
			'<svg width="24" height="24" viewBox="0 0 30 30" fill="none" xmlns="http://www.w3.org/2000/svg">' +
			'<circle cx="15" cy="15" r="15" fill="#008fa3"></circle>' +
			'<path d="M9 15 L14 19 L21 11" stroke="white" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"></path>' +
			'</svg>';
		return span;
	}

	function isFaCheckSpan( span ) {
		if ( ! span || ! span.classList || ! span.classList.contains( 'elementor-icon-list-icon' ) ) {
			return false;
		}
		var svg = span.querySelector( 'svg' );
		if ( ! svg ) {
			return false;
		}
		if ( svg.classList.contains( 'e-fas-check' ) ) {
			return true;
		}
		var path = svg.querySelector( 'path' );
		if ( path ) {
			var d = path.getAttribute( 'd' ) || '';
			if ( d.indexOf( '173.898' ) === 0 ) {
				return true;
			}
		}
		return false;
	}

	function replaceInRoot( root ) {
		var scope = root && root.querySelectorAll ? root : document;
		var nodes = scope.querySelectorAll( '.elementor-icon-list-icon' );
		for ( var i = 0; i < nodes.length; i++ ) {
			var span = nodes[ i ];
			if ( ! isFaCheckSpan( span ) ) {
				continue;
			}
			span.parentNode.replaceChild( buildIcon(), span );
		}
	}

	function runAll() {
		if ( document.body ) {
			replaceInRoot( document.body );
		}
	}

	function bindElementor() {
		if ( typeof elementorFrontend === 'undefined' || ! elementorFrontend.hooks ) {
			return;
		}
		elementorFrontend.hooks.addAction( 'frontend/element_ready/icon-list.default', function ( $scope ) {
			replaceInRoot( $scope[ 0 ] );
		} );
	}

	$( function () {
		runAll();
		if ( typeof elementorFrontend !== 'undefined' && elementorFrontend.hooks ) {
			bindElementor();
		} else {
			$( window ).on( 'elementor/frontend/init', bindElementor );
		}

		if ( typeof MutationObserver !== 'undefined' && document.body ) {
			var t;
			var obs = new MutationObserver( function () {
				window.clearTimeout( t );
				t = window.setTimeout( runAll, 80 );
			} );
			obs.observe( document.body, { childList: true, subtree: true } );
		}
	} );
})( jQuery );
