(function () {
'use strict';
define(['jquery', 'underscore', 'util'], function ($, _, Util) {
	var PrivateFunctions = {
		setupEvents: function () {
			$('.tabwidth-toggle').on('click', _.bind(function (event) {
				Util.focusDropdownInput(event.target);
			}, Util));

			$('form.tabwidth-form input').on('click', function (event) {
				// Suppress blur event on dropdown toggle
				event.stopImmediatePropagation();
			});

			$('form.tabwidth-form').on('submit', function (event) {
				var value = $(event.target).find('input').val();
				Util.setTabwidth(value);
				$(event.target).parents('.open').removeClass('open');
				event.preventDefault();
			});

			$('form.tabwidth-form input').on('change', function (event) {
				var value = $(event.target).val();
				Util.setTabwidth(value);
				event.preventDefault();
			});
		}
	};
	var TabwidthInput = {
		initialize: function () {
			PrivateFunctions.setupEvents();
		}
	};

	return TabwidthInput;
});
})();
