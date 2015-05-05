(function () {
'use strict';
define(['jquery', 'underscore', 'jquery.colorbox'], function ($, _) {
	var ui = {
		thumbnailLinks: '.upload_thumbnails a',
		deleteButton: '#delete_button',
		deleteForm: '#delete_form',
		markedThumbnails: '.upload_thumbnails marked',
		colorbox: '.colorbox',
		thumbnails: '.upload_thumbnails',
		toggleDeleteModeButton: '#toggle_delete_mode'
	};

	var PrivateFunctions = {
		inDeleteMode: false,

		setupEvents: function () {
			$(ui.toggleDeleteModeButton).on('click', _.bind(this.toggleDeleteMode, this));
			$(ui.thumbnailLinks).on('click', _.bind(this.toggleMarkForDeletion, this));
			$(window).resize(_.bind(this.onResize, this));
		},

		setupColorbox: function () {
			$(ui.colorbox).colorbox({
				transistion: "none",
				speed: 0,
				initialWidth: "100%",
				initialHeight: "100%",
				photo: true,
				retinaImage: true,
				maxHeight: "100%",
				maxWidth: "100%",
				next: '<span class="glyphicon glyphicon-chevron-right"></span>',
				previous: '<span class="glyphicon glyphicon-chevron-left"></span>',
				close: '<span class="glyphicon glyphicon-remove"></span>',
				loop: false,
				orientation: function() {
					return $(this).data('orientation');
				},
			});
		},

		removeColorbox: function () {
			$.colorbox.remove();
		},

		setupPopovers: function () {
			$(thumbnailLinks).popover({
				trigger: 'hover',
				placement: 'bottom',
				html: true
			});
		},

		toggleDeleteMode: function () {
			if (this.inDeleteMode) {
				$(ui.deleteButton).hide();
				$(ui.deleteForm).find('input').remove();
				$(ui.markedThumbnails).removeClass('marked');
				this.setupColorbox();
			} else {
				$(ui.deleteButton).show();
				this.removeColorbox();
			}
			this.inDeleteMode = !this.inDeleteMode;
		},

		deleteInput: function (id) {
			return $('<input>').attr({
				type: 'hidden',
				name: 'ids[' + id + ']',
				value: id,
				id: 'delete_' +id
			});
		},

		toggleMarkForDeletion: function (event) {
			if (!this.inDeleteMode) { return; }
			event.preventDefault();
			var id = $(event.target).closest('a').data('id');

			var deleteInput = $(ui.deleteForm).find('input#delete_' + id);

			if (deleteInput.length === 0) {
				$(ui.deleteForm).append(this.deleteInput(id));
			} else {
				deleteInput.remove();
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
		}
	};

	return ThumbnailView;
});
})();
