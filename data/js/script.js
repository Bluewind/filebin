function fixedEncodeURIComponent (str) {
	return encodeURIComponent(str).replace(/[!'()]/g, escape).replace(/\*/g, "%2A");
}

(function($) {

	$(function() {

		$(window).bind('hashchange', function(e) {
			var hash = window.location.hash;

			$('.highlight_line').removeClass("highlight_line");

			if (hash.match(/^#n\d+$/) === null) {
				return;
			}

			var line = $(hash).parent().parent();
			line.addClass("highlight_line");
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

		window.lines_wrapped = true;
		$('#linewrap').click(function() {
			if (window.lines_wrapped == true) {
				$(".highlight > pre").css("white-space", "pre");
			} else {
				$(".highlight > pre").css("white-space", "pre-wrap");
			}
			window.lines_wrapped = !window.lines_wrapped;
		});

		// check file size before uploading if browser support html5
		if (window.File && window.FileList) {
			function checkFileUpload(evt) {
				var sum = 0;
				var files = evt.target.files;

				// TODO: check all forms, not only the one we are called from
				for (var i = 0; i < files.length; i++) {
					var f = evt.target.files[i];
					sum += f.size;
				}

				if (sum > max_upload_size) {
					document.getElementById('upload_button').innerHTML = "File(s) too big";
					document.getElementById('upload_button').disabled = true;
				} else {
					document.getElementById('upload_button').innerHTML = "Upload it!";
					document.getElementById('upload_button').disabled = false;
				}
			}

			$('.file-upload').bind('change', checkFileUpload);
		}
		$('.text-upload').css('height', '100px');
		$('.text-upload').bind('blur', function() {
			$('.text-upload').animate({height: "100px"}, 300);
		});
		$('.text-upload').bind('focus', function() {
			$('.text-upload').animate({height: "300px"}, 300);
		});

		// work around to submit the form if the click causes the
		// textarea to shrink and you can't relase the mouse fast enough
		// so mouseup will be outside the button area and not trigger
		// submission properly
		$('.text-upload-form :submit').bind('mousedown', function() {
			$(this).click();
		});
	});

})(jQuery);
