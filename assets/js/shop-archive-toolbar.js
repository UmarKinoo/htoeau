/**
 * Shop toolbar: navigate with GET params when any filter/sort select changes.
 * Omits empty values to shorten the URL and reduce server/WAF issues.
 */
(function () {
	'use strict';

	function go(form) {
		var params = new URLSearchParams();
		form.querySelectorAll('select.htoeau-shop-toolbar__select').forEach(function (sel) {
			if (sel.name && sel.value !== '') {
				params.set(sel.name, sel.value);
			}
		});
		var base = form.getAttribute('action') || '';
		var q = params.toString();
		window.location.href = q
			? base + (base.indexOf('?') !== -1 ? '&' : '?') + q
			: base;
	}

	document.querySelectorAll('.htoeau-shop-toolbar__form').forEach(function (form) {
		form.querySelectorAll('select.htoeau-shop-toolbar__select').forEach(function (sel) {
			sel.addEventListener('change', function () {
				go(form);
			});
		});
	});
})();
