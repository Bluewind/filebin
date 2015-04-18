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
		}
	};
	return Util;
});
})();
