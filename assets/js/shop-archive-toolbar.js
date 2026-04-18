/**
 * Shop toolbar: navigate with GET params when any filter/sort select changes.
 * Omits empty values to shorten the URL and reduce server/WAF issues.
 */
(function () {
	'use strict';

	function orderbyForNavigation(form, changed) {
		var priceSel = form.querySelector('.htoeau-shop-toolbar__select--price');
		var sortSel = form.querySelector('select[name="orderby"]');
		if (changed && changed.classList && changed.classList.contains('htoeau-shop-toolbar__select--price')) {
			return priceSel && priceSel.value !== '' ? priceSel.value : (sortSel ? sortSel.value : '');
		}
		if (changed && changed.name === 'orderby') {
			return sortSel ? sortSel.value : '';
		}
		if (priceSel && priceSel.value !== '') {
			return priceSel.value;
		}
		return sortSel ? sortSel.value : '';
	}

	function go(form, changed) {
		var params = new URLSearchParams();
		var ob = orderbyForNavigation(form, changed);
		if (ob !== '') {
			params.set('orderby', ob);
		}
		form.querySelectorAll('select.htoeau-shop-toolbar__select').forEach(function (sel) {
			if (sel.classList.contains('htoeau-shop-toolbar__select--price')) {
				return;
			}
			if (sel.name === 'orderby') {
				return;
			}
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
			sel.addEventListener('change', function (e) {
				go(form, e.target);
			});
		});
	});
})();
