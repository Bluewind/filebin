// check file size before uploading if browser support html5
if (window.File && window.FileList) {
	function checkFileUpload(evt) {
	  var f = evt.target.files[0];
	  if (f.size > max_upload_size) {
		document.getElementById('upload_button').value = "File too big";
		document.getElementById('upload_button').disabled = true;
	  } else {
		document.getElementById('upload_button').value = "Upload";
		document.getElementById('upload_button').disabled = false;
	  }
	}

	document.getElementById('file').addEventListener('change', checkFileUpload, false);
}

