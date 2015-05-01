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
		'bootstrap'
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
				this.setupToggleSelectAllEvent();
				this.setupLineWrapToggle();
				this.setupLazyLoadingImages();
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
					$(ui.lazyLoadingImages).show().lazyload({treshold: 200});
				}
			}
		};

		return App;
	}
);
})();
