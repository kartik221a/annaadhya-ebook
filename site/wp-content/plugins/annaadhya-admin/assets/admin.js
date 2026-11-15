/* Annaadhya Admin — confirm dialogs + niceties */
document.addEventListener('DOMContentLoaded', function () {
	document.querySelectorAll('form[data-confirm]').forEach(function (form) {
		form.addEventListener('submit', function (e) {
			if (!window.confirm(form.getAttribute('data-confirm'))) e.preventDefault();
		});
	});
});
