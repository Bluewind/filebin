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

function encode64(inp){
    var key="ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
    var chr1,chr2,chr3,enc3,enc4,i=0,out="";
    while(i<inp.length){
        chr1=inp.charCodeAt(i++);if(chr1>127) chr1=88;
        chr2=inp.charCodeAt(i++);if(chr2>127) chr2=88;
        chr3=inp.charCodeAt(i++);if(chr3>127) chr3=88;
        if(isNaN(chr3)) {enc4=64;chr3=0;} else enc4=chr3&63
        if(isNaN(chr2)) {enc3=64;chr2=0;} else enc3=((chr2<<2)|(chr3>>6))&63
        out+=key.charAt((chr1>>2)&63)+key.charAt(((chr1<<4)|(chr2>>4))&63)+key.charAt(enc3)+key.charAt(enc4);
    }
    return encodeURIComponent(out);
}

function gen_boundary() {
  var chars = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXTZabcdefghiklmnopqrstuvwxyz";
  var string_length = 40;
  var randomstring = '';
  for (var i=0; i<string_length; i++) {
    var rnum = Math.floor(Math.random() * chars.length);
    randomstring += chars.substring(rnum,rnum+1);
  }
  return randomstring;
}
function do_paste() {
    var http = new XMLHttpRequest();
    var CRLF = "\r\n";
    var boundary = "--" + gen_boundary();
    var body = "--" + boundary + CRLF
      + 'Content-Disposition: form-data; name="file"; filename="stdin"' + CRLF
      + "Content-Type: text/plain" + CRLF
      + CRLF
      + document.getElementById("textarea").value + CRLF
      + "--" + boundary + "--" + CRLF + CRLF;
    http.open("POST", upload_url, true);

    //Send the proper header information along with the request
    http.setRequestHeader("Content-type", "multipart/form-data; boundary=" + boundary);

    http.onreadystatechange = function() {
      if(http.readyState == 4 && http.status == 200) {
        window.location = http.responseText;
      }
    }
    http.send(body);
}

