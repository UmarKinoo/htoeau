/**
 * Reads #htoeau-fx-debug-data (JSON) and logs currency/geo diagnostics.
 */
(function () {
	'use strict';

	var el = document.getElementById('htoeau-fx-debug-data');
	if (!el || !el.textContent) {
		console.warn(
			'[HtoEAU] Currency/geo debug missing — purge LiteSpeed cache or deploy theme v1.7.5+'
		);
		return;
	}

	var data;
	try {
		data = JSON.parse(el.textContent);
	} catch (err) {
		console.error('[HtoEAU] FX debug JSON parse error', err);
		return;
	}

	var cookieKey = 'htoeau_display_ccy';
	var manualKey = 'htoeau_display_ccy_manual';

	console.group('%cHtoEAU currency & geo', 'font-weight:bold;font-size:13px;color:#008fa3');
	console.log(
		'Assigned:',
		data.display_currency,
		'←',
		data.display_currency_source
	);
	console.log(
		'Country CF:',
		data.cloudflare_CF_IPCOUNTRY || '(none)',
		'| detected:',
		data.detected_country_code || '(none)',
		'| geo guess:',
		data.geo_guess_from_country
	);
	if (data.hints && data.hints.length) {
		console.warn('Hints:', data.hints);
	}
	console.table({
		display_currency: data.display_currency,
		source: data.display_currency_source,
		cf_country: data.cloudflare_CF_IPCOUNTRY,
		detected_country: data.detected_country_code,
		geo_guess: data.geo_guess_from_country,
		in_gbp_list: data.country_in_gbp_list,
		cookie: data.cookies && data.cookies[cookieKey],
		manual_test: data.cookies && data.cookies[manualKey],
		yay_widget: data.cookies && data.cookies.yay_currency_widget,
		yay_apply: data.yay_apply_currency
	});
	console.log('Full payload:', data);
	console.groupEnd();

	window.htoeauFxDebug = data;
})();
