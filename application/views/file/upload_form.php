<div style="margin-top: 100px; text-align:center">
  <?php echo form_open_multipart('file/do_upload'); ?>
    <p>
      File: <input type="file" id="file" name="file" size="30" />
      <input type="submit" value="Upload" id="upload_button" name="process" /><br />
      Optional password (for deletion): <input type="password" name="password" size="10" />
    </p>
  </form>
  <script type="text/javascript">
    /* <![CDATA[ */
    // check file size before uploading if browser support html5
    if (window.File && window.FileList) {
        function checkFileUpload(evt) {
          var f = evt.target.files[0]; // FileList object
          if (f.size > <?php echo $max_upload_size; ?>) {
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
    var url = "<?php echo site_url("file/do_upload/dumb"); ?>";
    var CRLF = "\r\n";
    var boundary = "--" + gen_boundary();
    var body = "--" + boundary + CRLF
      + 'Content-Disposition: form-data; name="file"; filename="stdin"' + CRLF
      + "Content-Type: text/plain" + CRLF
      + CRLF
      + document.getElementById("textarea").value + CRLF
      + "--" + boundary + "--" + CRLF + CRLF;
    http.open("POST", url, true);

    //Send the proper header information along with the request
    http.setRequestHeader("Content-type", "multipart/form-data; boundary=" + boundary);
    http.setRequestHeader("Authorization", "Basic " + encode64(":" + document.getElementById("textarea_password").value));

    http.onreadystatechange = function() {//Call a function when the state changes.
      if(http.readyState == 4 && http.status == 200) {
        window.location = http.responseText;
      }
    }
    http.send(body);
}

document.write('\
  <p><b>OR</b></p>\
  <form action="javascript: do_paste()">\
    <p>\
      <textarea id="textarea" name="content" cols="80" rows="20"></textarea><br />\
      <div style="display: none">Email: <input type="text" name="email" size="20" /></div>\
      Optional password (for deletion): <input id="textarea_password" type="password" name="password" size="10" /><br />\
      <input  type="submit" value="Paste" name="process" />\
    </p>\
    </form>\
');
    /* ]]> */
  </script>
</div>
<br />
<p>Uploads/pastes are deleted after <?php echo $upload_max_age; ?> days<?php if($small_upload_size > 0): ?>
  unless they are smaller than <?php echo format_bytes($small_upload_size); ?>
  <?php endif; ?>. Maximum upload size is <?php echo format_bytes($max_upload_size); ?></p>
<p>For shell uploading/pasting and download information for the client go to <a href="<?php echo site_url("file/client"); ?>"><?php echo site_url("file/client"); ?></a></p>
<br />
<p>If you experience any problems feel free to <a href="http://bluewind.at/?id=1">contact me</a>.</p>
<br />
<div class="small">
  <p>This service is provided without warranty of any kind and may not be used to distribute copyrighted content.</p>
</div>
