(function () {
'use strict';
define(['jquery'], function () {
	var PrivateFunctions = {
		highlightLine: function (id) {
			this.clearLineHighlights();
			var line = $(id).parents('.table-row');
			line.addClass("highlight_line");
		},
		clearLineHighlights: function () {
			$('.highlight_line').removeClass('highlight_line');
		}
	};
	var Util = {
		fixedEncodeURIComponent: function (string) {
			var encodedString =  encodeURIComponent(string);
			encodedString = encodedString.replace(/[!'()]/g, escape);
			encodedString = encodedString.replace(/\*/g, "%2A");

			return encodedString;
		},
		highlightLineFromHash: function () {
			var hash = window.location.hash;
			if (hash.match(/^#n(?:-.+-)?\d+$/) === null) {
				PrivateFunctions.clearLineHighlights();
				return;
			}

			PrivateFunctions.highlightLine(hash);
		},
		focusDropdownInput: function (target) {
			setTimeout(function () {
				var dropDown = $(target).siblings('.dropdown-menu');
				dropDown.find('input').trigger('focus');
			}, 0);
		},
		setTabwidth: function (value) {
			value = value || 8;
			$('span.tabwidth-value').html(value);
			$('.tabwidth-form input').val(value);
			$('.highlight pre').css('tab-size', value);
			localStorage.setItem('tabwidth', value);
		},
		setTabwidthFromLocalStorage: function () {
			this.setTabwidth(localStorage.getItem('tabwidth'));
		}
	};
	return Util;
});
})();
