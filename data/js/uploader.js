(function () {
'use strict';
define(['jquery', 'underscore'], function ($, _) {
	var ui = {
		uploadButton: '#upload_button',
		uploadInputs: 'input.file-upload',
		textAreas: '#textboxes textarea.text-upload',
		filenameInputs: '#textboxes input[name^=filename]',
		textAreaTabsContainer: '#textboxes .tab-content',
		textAreaTabs: '#textboxes .tab-content .tab-pane',
		tabNavigation: '#textboxes ul.nav',
		tabPane: '.tab-pane',
		panelBody: '.panel-body'
	};
	var PrivateFunctions = {
		filesForInput: function (input, callback) {
			var files = $(input).prop('files');
			for (var i = 0; i < files.length; i++) {
				callback(files[i]);
			}
		},

		filesForInputs: function (callback) {
			_.each($(ui.uploadInputs), function (input) {
				this.filesForInput(input, callback);
			}, this);
		},

		checkFileUpload: function (event) {
			var totalSize = 0;
			var filesCount = 0;
			this.filesForInputs(function (file) {
				filesCount++;
				totalSize += file.size;
			});

			var uploadButton = $(ui.uploadButton);
			if (filesCount > appConfig.maxFilesPerUpload) {
				uploadButton.html('Too many files').attr('disabled', true);
			} else if (totalSize > appConfig.maxUploadSize) {
				uploadButton.html('File(s) too big').attr('disabled', true);
			} else {
				uploadButton.html('Upload/Paste it!').attr('disabled', false);
			}
		},

		hasNoFiles: function (input) {
			return $(input).prop('files').length === 0;
		},

		appendUploadInput: function (event) {
			if (_.any($(ui.uploadInputs), this.hasNoFiles)) { return; }
			$(event.target).parent().append($(event.target).clone().val(""), $('<br>'));
		},

		hasNoText: function (textArea) {
			return !$(textArea).val(); 
		},

		setAttributeIndices: function (tab, index) {
			tab.attr('id', tab.attr('id').replace(/\d+$/, index));
			_.each(tab.find('input,textarea'), function (input) {
				var name = $(input).attr('name');
				$(input).attr('name', name.replace(/\[\d+\]/, '[' + index + ']'));
			});
		},

		clearValues: function (tab) {
			tab.find('input,textarea').val('');
		},

		tabNavigationItem: function (index) {
			var link = $('<a data-toggle="tab">').attr({
				href: '#text-upload-tab-' + index,
			}).html('Paste ' + index);

			return $('<li>').append(link);
		},

		appendTextField: function (event) {
			if (_.any($(ui.textAreas), this.hasNoText)) { return; }

			var newTab = $(ui.textAreaTabs).last().clone();
			var index = parseInt(newTab.attr('id').match(/\d+$/)[0]) + 1;
			this.setAttributeIndices(newTab, index);
			this.clearValues(newTab);
			newTab.toggleClass('active', false);
			$(ui.textAreaTabsContainer).append(newTab);
			$(ui.tabNavigation).append(this.tabNavigationItem(index));
		},

		setTabHeader: function (event) {
			var name = $(event.target).val();
			if (_.isEmpty(name)) {
				var tabPane = $(event.target).closest(ui.tabPane);
				var index = tabPane.attr('id').match(/\d+$/)[0];
				name = 'Paste ' + index;
			}
			$(ui.tabNavigation).find('li.active a').html(name);
		},

		setupEvents: function () {
			if (window.File && window.FileList) {
				$(document).on(
					'change', ui.uploadInputs,
					_.bind(this.checkFileUpload, this)
				);
			}
			$(document).on(
				'change', ui.uploadInputs,
				_.bind(this.appendUploadInput, this)
			);
			$(document).on(
				'input propertychange', ui.textAreas,
				_.bind(this.appendTextField, this)
			);
			$(document).on(
				'input propertychange', ui.filenameInputs,
				_.bind(this.setTabHeader, this)
			);
		}
	};

	var Uploader = {
		initialize: function () {
			PrivateFunctions.setupEvents();
		}
	};

	return Uploader;
});
})();
