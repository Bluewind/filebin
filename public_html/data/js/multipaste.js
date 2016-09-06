(function () {
'use strict';
define(['underscore', 'util', 'jquery', 'jquery-ui'], function (_, Util, $) {
	var ui = {
		itemsContainer: ".multipasteQueue .items",
		queueDeleteButton: ".multipaste_queue_delete",
		submitButton: ".multipasteQueue button[type=submit]",
		itemImages: ".multipasteQueue .items img",
		form: ".multipasteQueue form",
		csrfToken: "form input[name=csrf_test_name]",
		ajaxFeedback: "form .ajaxFeedback",
	};

	var timer = 0;

	var PrivateFunctions = {
		setupQueueDeleteButtons: function() {
			$(ui.queueDeleteButton).on('click', function(event) {
				event.stopImmediatePropagation();
				var id = $(event.target).data('id');
				$(event.target).parent().remove();
				PrivateFunctions.saveQueue();
			});
		},
		setupTooltips: function() {
			$(ui.itemImages).popover({
				trigger: 'hover',
				placement: 'auto bottom',
				html: true
			});
		},
		setupButtons: function() {
			this.setupQueueDeleteButtons();
		},
		setupSortable: function() {
			$(ui.itemsContainer).sortable({
				revert: 100,
				placeholder: "ui-state-highlight",
				tolerance: "pointer",
				stop: function(e, u) {
					u.item.find("img").first().popover("show");
				},
				start: function(e, u) {
					u.item.find("img").first().popover("show");
				},
				update: function(e, u) {
					PrivateFunctions.saveQueue();
				},
			});

			$(ui.itemsContainer).disableSelection();
		},
		saveQueue: function() {
			var queue = $(ui.itemsContainer).sortable("toArray", {attribute: "data-id"});
			console.log("queue changed ", queue);
			clearTimeout(timer);
			timer = setTimeout(function() {
				var url = $(ui.form).data("ajax_url");
				var csrf_token = $(ui.csrfToken).attr("value");
				$(ui.ajaxFeedback).show();
				$.ajax({
					method: "POST",
					url: url,
					data: {
						csrf_test_name: csrf_token,
						ids: queue
					},
					complete: function() {
						$(ui.ajaxFeedback).hide();
					},
				});
			}, 2000);
		},
	};

	var Multipaste = {
		initialize: function () {
			PrivateFunctions.setupButtons();
			PrivateFunctions.setupSortable();
			PrivateFunctions.setupTooltips();
		},
	};

	return Multipaste;
});
})();
