(function () {
'use strict';
define(
	[
		'require',
		'util',
		'lexer-input',
		'tabwidth-input',
		'thumbnail-view',
		'uploader',
		'tablesorter',
		'jquery',
		'jquery.lazyload',
		'bootstrap',
		'jquery.checkboxes',
	],
	function (
		require,
		Util,
		LexerInput,
		TabwidthInput,
		ThumbnailView,
		Uploader,
		TableSorter,
		$
	) {
		var ui = {
			lazyLoadingImages: 'img.lazyload'
		};

		var App = {
			 // Gets called for every request (before page load)
			initialize: function () {
				this.setupLineHighlight();
			},

			/*
			 * Gets called for every request after page load
			 * config contains app config attributes passed from php
			 */
			onPageLoaded: function () {
				Util.highlightLineFromHash();
				Util.setTabwidthFromLocalStorage();
				TabwidthInput.initialize();
				LexerInput.initialize();
				ThumbnailView.initialize();
				Uploader.initialize();
				TableSorter.initialize();
				this.configureTooltips();
				this.setupHistoryPopovers();
				this.setupToggleSelectAllEvent();
				this.setupLineWrapToggle();
				this.setupLazyLoadingImages();
				this.setupTableRangeSelect();
				this.setupAsciinema();
			},

			setupTableRangeSelect: function () {
				$('#upload_history').checkboxes('range', true);
			},

			setupLineHighlight: function () {
				$(window).on('hashchange', Util.highlightLineFromHash);
			},
			
			configureTooltips: function () {
				$('[rel="tooltip"]').tooltip({
					placement: 'bottom',
					container: 'body',
				});
			},

			setupHistoryPopovers: function () {
				$('#upload_history a').popover({
					trigger: 'hover',
					placement: 'bottom',
					html: true,
					viewport: '#upload_history',
					template: '<div class="popover" role="tooltip"><div class="arrow"></div><h3 class="popover-title"></h3><pre class="popover-content"></pre></div>'
				});
			},

			setupToggleSelectAllEvent: function () {
				$('#history-all').on('click', function(event) {
					// Suppress click event on table heading
					event.stopImmediatePropagation();
				});
				$('#history-all').on('change', function(event) {
					var checked = $(event.target).prop('checked');
					$('.delete-history').prop('checked', checked);
				});
			},

			setupLineWrapToggle: function () {
				var linesWrapped = localStorage.getItem('lines_wrapped') || 'true';
				Util.setLineWrap(linesWrapped === 'true');
				
				$('.linewrap-toggle').on('click', _.bind(Util.toggleLineWrap, Util));
			},

			setupLazyLoadingImages: function () {
				if ($(ui.lazyLoadingImages).length > 0) {
					$(ui.lazyLoadingImages).lazyload({treshold: 200});
				}
			},

			setupAsciinema: function () {
				_.each($('.asciinema_player'), function (item) {
					item = $(item);
					asciinema.player.js.CreatePlayer(item.attr("id"), item.data("url"));
				});
			}
		};

		return App;
	}
);
})();
