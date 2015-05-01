(function () {
'use strict';
define(['jquery', 'jquery.tablesorter'], function ($) {
	var PrivateFunctions = {
		setupParser: function () {
			// source: https://projects.archlinux.org/archweb.git/tree/sitestatic/archweb.js
			$.tablesorter.addParser({
				id: 'filesize',
				re: /^(\d+(?:\.\d+)?)(bytes?|[KMGTPEZY]i?B|B)$/,
				is: function(s) {
					return this.re.test(s);
				},
				format: function(s) {
					var matches = this.re.exec(s);
					if (!matches) {
						return 0;
					}
					var size = parseFloat(matches[1]),
						suffix = matches[2];

					switch(suffix) {
						/* intentional fall-through at each level */
						case 'YB':
						case 'YiB':
							size *= 1024;
						case 'ZB':
						case 'ZiB':
							size *= 1024;
						case 'EB':
						case 'EiB':
							size *= 1024;
						case 'PB':
						case 'PiB':
							size *= 1024;
						case 'TB':
						case 'TiB':
							size *= 1024;
						case 'GB':
						case 'GiB':
							size *= 1024;
						case 'MB':
						case 'MiB':
							size *= 1024;
						case 'KB':
						case 'KiB':
							size *= 1024;
					}
					return size;
				},
				type: 'numeric'
			});
		},

		textExtraction: function (node) {
			var attr = $(node).attr('data-sort-value');
			if (!_.isUndefined(attr) && attr !== false) {
				var intAttr = parseInt(attr);
				if (!_.isNaN(intAttr)) {
					return intAttr;
				}
				return attr;
			}
			return $(node).text();
		},

		setupTableSorter: function () {
			$(".tablesorter").tablesorter({
				textExtraction: this.textExtraction
			});
		}
	};

	var TableSorter = {
		initialize: function () {
			PrivateFunctions.setupParser();
			PrivateFunctions.setupTableSorter();
		}
	};

	return TableSorter;
});
})();
