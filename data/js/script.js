(function($) {
	$(function() {
		// check file size before uploading if browser support html5
		if (window.File && window.FileList) {
			function checkFileUpload(evt) {
				var sum = 0;
				var filenum = 0;
				var files = [];

				$('.file-upload').each(function() {
					for (var i = 0; i < this.files.length; i++) {
						var file = this.files[i];
						files.push(file);
					}
				});

				for (var i = 0; i < files.length; i++) {
					var f = files[i];
					sum += f.size;
					filenum++;
				}

				if (filenum > max_files_per_upload) {
					document.getElementById('upload_button').innerHTML = "Too many files";
					document.getElementById('upload_button').disabled = true;
				} else if (sum > max_upload_size) {
					document.getElementById('upload_button').innerHTML = "File(s) too big";
					document.getElementById('upload_button').disabled = true;
				} else {
					document.getElementById('upload_button').innerHTML = "Upload/Paste it!";
					document.getElementById('upload_button').disabled = false;
				}
			}

			$(document).on('change', '.file-upload', checkFileUpload);
		}

		$(document).on("change", '.file-upload', function() {
			var need_new = true;

			$('.file-upload').each(function() {
				if ($(this).prop("files").length == 0) {
					need_new = false;
					return;
				}
			});

			if (need_new) {
				$(this).parent().append('<input class="file-upload" type="file" name="file[]" multiple="multiple"><br>');
			}

		});

		$(document).on("input propertychange", '.text-upload', function() {
			var need_new = true;

			$('.text-upload').each(function() {
				if (!$(this).val()) {
					need_new = false;
					return;
				}
			});

			if (need_new) {
				var i = $('#textboxes .tab-content .tab-pane').length + 1;
				var new_tab = $('#text-upload-tab-1')
					.clone()
					.attr("id", "text-upload-tab-"+i)
					.toggleClass("active", false)
					.appendTo('#textboxes .tab-content');
				new_tab.find("[name^=filename]").attr("name", "filename["+i+"]").val("");
				new_tab.find("[name^=content]").attr("name", "content["+i+"]").val("");
				$('#textboxes ul.nav')
					.append('<li><a href="#text-upload-tab-'+i+'" data-toggle="tab">Paste '+i+' </a></li>');
			}
		});

		$(document).on("input propertychange", '#textboxes input[name^=filename]', function() {
			var name = $(this).val();
			var tabId = $(this).closest("[id^=text-upload-tab-]").attr("id");
			var id = tabId.match(/-(\d)$/)[1];
			var tab = $('#textboxes .nav a[href="#'+tabId+'"]');

			if (name != "") {
				tab.text(name);
			} else {
				tab.text("Paste " + id);
			}
		});

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

			$(".tablesorter").tablesorter({
				textExtraction: function(node) {
					var attr = $(node).attr('data-sort-value');
					if (typeof attr !== 'undefined' && attr !== false) {
						var intAttr = parseInt(attr);
						if (!isNaN(intAttr)) {
							return intAttr;
						}
						return attr;
					}
					return $(node).text();
				}
			});
		}

		if ($("img.lazyload").length) {
			$("img.lazyload").show().lazyload({treshold: 200});
		}
	});
})(jQuery);
