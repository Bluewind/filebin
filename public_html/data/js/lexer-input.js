(function () {
'use strict';
define(['util', 'underscore', 'jquery', 'jquery-ui'], function (Util, _, $) {
	var PrivateFunctions = {
		switchLexer: function (lexer, baseUrl) {
			var url = baseUrl + '/' + Util.fixedEncodeURIComponent(lexer);
			window.location = url;
		},
		lexerSelected: function (event, ui) {
			event.preventDefault();
			var baseUrl = $(event.target).data('base-url');
			this.switchLexer(ui.item.value, baseUrl);
		},
		setupAutocomplete: function () {
			var lexerSource = [];
			for (var key in appConfig.lexers) {
				lexerSource.push({ label: appConfig.lexers[key], value: key });
			}

			$('.lexer-form input').autocomplete({
				source: lexerSource,
				select: _.bind(PrivateFunctions.lexerSelected, PrivateFunctions)
			});
		},
		setupEvents: function () {
			$('.lexer-form').on('submit', _.bind(function (event) {
				event.preventDefault();
				var input = $(event.target).find('input');
				var lexer = input.val();
				var baseUrl = input.data('base-url');
				this.switchLexer(lexer, baseUrl);
			}, this));

			$('.lexer-toggle').on('click', _.bind(function(event) {
				Util.focusDropdownInput(event.target);
			}, Util));
		}
	};
	var LexerInput = {
		initialize: function () {
			PrivateFunctions.setupAutocomplete();
			PrivateFunctions.setupEvents();
		}
	};

	return LexerInput;
});
})();
