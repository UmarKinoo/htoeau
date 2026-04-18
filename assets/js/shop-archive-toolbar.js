/**
 * Shop toolbar: submit GET form when any filter/sort select changes.
 */
(function () {
	'use strict';

	document.querySelectorAll('.htoeau-shop-toolbar__form').forEach(function (form) {
		form.querySelectorAll('select.htoeau-shop-toolbar__select').forEach(function (sel) {
			sel.addEventListener('change', function () {
				form.submit();
			});
		});
	});
})();
