function fixedEncodeURIComponent (str) {
	return encodeURIComponent(str).replace(/[!'()]/g, escape).replace(/\*/g, "%2A");
}

(function($) {

	$(function() {

		$(window).bind('hashchange', function(e) {
			var hash = window.location.hash;

			$('#highlight_line').remove();

			if (hash.match(/^#n\d+$/) === null) {
				return;
			}

			var link = $(hash);

			$('<div id="highlight_line" />').prependTo('.highlight').css({
				top: link.get(0).offsetTop - 10 + parseInt(link.css("padding-top")) + 'px'
			});
		});

		$(window).trigger('hashchange');

		var lexer_source = [];
		for (var key in window.lexers) {
			lexer_source.push({ label: window.lexers[key], value: key });
		}

		$('#language').autocomplete({
			source: lexer_source,
			select: function(event, ui) {
				window.location = window.paste_base + '/' + fixedEncodeURIComponent(ui.item.value);
			}
		});

		$('#language-toggle').click(function() {
			setTimeout(function() {
				$('#language').focus();
			}, 0);
		});

		$('[rel="tooltip"]').tooltip({
			placement: 'bottom'
		});

		$('#history-all').bind('change', function() {
			$('.delete-history').prop('checked', $(this).is(':checked'));
		});

		$('.modal').on('shown', function(e) {
			var modal = $(this);

			modal.css('margin-top', (modal.outerHeight() / 2) * -1)
			.css('margin-left', (modal.outerWidth() / 2) * -1);

		return this;
		});

		window.lines_wrapped = false;
		$('#linewrap').click(function() {
			if (window.lines_wrapped == true) {
				$(".content .numbers").show();
				$(".content .code > .highlight > pre").css("white-space", "pre");
			} else {
				$(".content .numbers").hide();
				$(".content .code > .highlight > pre").css("white-space", "pre-wrap");
			}
			window.lines_wrapped = !window.lines_wrapped;
		});

		// check file size before uploading if browser support html5
		if (window.File && window.FileList) {
			function checkFileUpload(evt) {
			  var f = evt.target.files[0];
			  if (f.size > max_upload_size) {
				document.getElementById('upload_button').innerHTML = "File too big";
				document.getElementById('upload_button').disabled = true;
			  } else {
				document.getElementById('upload_button').innerHTML = "Upload it!";
				document.getElementById('upload_button').disabled = false;
			  }
			}

			document.getElementById('file').addEventListener('change', checkFileUpload, false);
		}
	});

})(jQuery);
