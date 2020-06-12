(function () {
'use strict';
define(['jquery', 'underscore', 'multipaste', 'jquery.colorbox'], function ($, _, Multipaste) {
	var ui = {
		thumbnailLinks: '.upload_thumbnails a',
		formButtons: '#submit_form button[type=submit]',
		submitForm: '#submit_form',
		markedThumbnails: '.upload_thumbnails .marked',
		colorbox: '.colorbox',
		thumbnails: '.upload_thumbnails',
		toggleSelectModeButton: '#toggle_select_mode',
	};

	var PrivateFunctions = {
		inSelectMode: false,

		setupEvents: function () {
			$(ui.toggleSelectModeButton).on('click', _.bind(this.toggleSelectMode, this));
			$(ui.thumbnailLinks).on('click', _.bind(this.thumbnailClick, this));
			$(window).resize(_.bind(this.onResize, this));
		},

		browserHandlesImageOrientation: function () {
			var testImg = $('<img>');
			$('body').append(testImg);
			var style = window.getComputedStyle(testImg.get(0));
			var result = style.getPropertyValue('image-orientation')
			console.log('Browser default image-orientation: ', result)
			testImg.remove();
			return result == 'from-image';
		},

		setupColorbox: function () {
			var browserHandlesImageOrientation = PrivateFunctions.browserHandlesImageOrientation();

			$(ui.colorbox).colorbox({
				transistion: "none",
				speed: 0,
				initialWidth: "100%",
				initialHeight: "100%",
				photo: true,
				retinaImage: true,
				maxHeight: "100%",
				maxWidth: "100%",
				current: 'Image {current} of {total}. Use h/l or right/left arrow keys or these buttons:',
				next: '<span class="glyphicon glyphicon-chevron-right"></span>',
				previous: '<span class="glyphicon glyphicon-chevron-left"></span>',
				close: '<span class="glyphicon glyphicon-remove"></span>',
				loop: false,
				orientation: function() {
					if (browserHandlesImageOrientation) {
						return 1;
					} else {
						return $(this).data('orientation');
					}
				},
			});
		},

		removeColorbox: function () {
			$.colorbox.remove();
		},

		setupPopovers: function () {
			$(ui.thumbnailLinks).popover({
				trigger: 'hover',
				placement: 'auto bottom',
				html: true
			});
		},

		toggleSelectMode: function () {
			if (this.inSelectMode) {
				$(ui.formButtons).hide();
				$(ui.submitForm).find('input').remove();
				$(ui.markedThumbnails).removeClass('marked');
				this.setupColorbox();
			} else {
				$(ui.formButtons).show();
				this.removeColorbox();
			}
			this.inSelectMode = !this.inSelectMode;
		},

		submitInput: function (id) {
			return $('<input>').attr({
				type: 'hidden',
				name: 'ids[' + id + ']',
				value: id,
				id: 'submit_' +id
			});
		},

		thumbnailClick: function (event) {
			if (!this.inSelectMode) { return; }
			event.preventDefault();
			var id = $(event.target).closest('a').data('id');

			var submitInput = $(ui.submitForm).find('input#submit_' + id);

			if (submitInput.length === 0) {
				$(ui.submitForm).append(this.submitInput(id));
			} else {
				submitInput.remove();
			}
			$(event.target).closest('a').toggleClass('marked');
		},

		needMultipleLines: function (thumbnailContainer) {
			var containerWidth, thumbsWidth, thumbs, thumbsCount;
			containerWidth = thumbnailContainer.parent().width();
			thumbs = thumbnailContainer.find('a');
			thumbsCount = thumbs.length;
			thumbsWidth = thumbs.outerWidth(true) * thumbsCount;

			return containerWidth < thumbsWidth;
		},

		thumbnailsWidth: function (thumbnailContainer) {
			var containerWidth, thumbs, thumbWidth;
			containerWidth = thumbnailContainer.parent().width();
			thumbs = thumbnailContainer.find('a');
			thumbWidth = thumbs.outerWidth(true);
			return containerWidth - (containerWidth % thumbWidth);
		},

		onResize: function () {
			_.each($(ui.thumbnails), function (thumbnailContainer) {
				thumbnailContainer = $(thumbnailContainer);
				var margin = this.needMultipleLines(thumbnailContainer) ? 'auto' : '0';
				thumbnailContainer.css('margin-left', margin);
				thumbnailContainer.width(this.thumbnailsWidth(thumbnailContainer));
			}, this);
		}
	};

	var ThumbnailView = {
		initialize: function () {
			PrivateFunctions.setupEvents();
			PrivateFunctions.onResize();
			PrivateFunctions.setupColorbox();
			PrivateFunctions.setupPopovers();
		}
	};

	return ThumbnailView;
});
})();
