(function () {
'use strict';
define(['require', 'util', 'vendor'], function (require, Util) {
	require(['script']);
	var App = {
		// Gets called for every request
		initialize: function () {
			this.setupLineHighlight();
		},
		// Gets called for every request on page load
		onPageLoaded: function () {
			Util.highlightLineFromHash();
		},

		setupLineHighlight: function () {
			$(window).on('hashchange', Util.highlightLineFromHash);
		}
	};

	return App;
});
})();
