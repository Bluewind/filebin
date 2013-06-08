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

		if (typeof $.tablesorter !== 'undefined') {
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
			$.tablesorter.addParser({
				// set a unique id
				id: 'mydate',
				re: /t=([0-9]+)$/,
				is: function(s) {
					// return false so this parser is not auto detected
					return false;
				},
				format: function(s) {
					var matches = this.re.exec(s);
					if (!matches) {
						return 0;
					}
					//console.log(s, matches);
					return matches[1];
				},
				type: 'numeric'
			});
			$("#upload_history:has(tbody tr)").tablesorter({
				headers: {
					0: {sorter: false},
					4: {sorter: "mydate"},
				},
				sortList: [[4,1]],
			});
		}

	});
})(jQuery);
