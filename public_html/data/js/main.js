(function () {
'use strict';
requirejs.config({
	shim: {
		'jquery-ui': ['jquery'],
		'bootstrap': ['jquery'],
		'jquery.tablesorter': ['jquery'],
		'jquery.lazyload': ['jquery'],
		'jquery.colorbox': ['jquery'],
		'jquery.checkboxes': ['jquery']
	},
	paths: {
		'jquery': 'vendor/jquery-2.0.3.min',
		'jquery-ui': 'vendor/jquery-ui-1.10.3.custom.min',
		'bootstrap': 'vendor/bootstrap.min',
		'jquery.tablesorter': 'vendor/jquery.tablesorter.min',
		'jquery.lazyload': 'vendor/jquery.lazyload',
		'jquery.colorbox': 'vendor/jquery.colorbox',
		'jquery.checkboxes': 'vendor/jquery.checkboxes-1.0.6.min',
		'underscore': 'vendor/underscore'
	}
});

require(['application', 'jquery'], function (App, $) {
	App.initialize();
	$(document).ready(function () {
		App.onPageLoaded();
	});
});
})();
