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
    /* ]]> */
  </script>
<?php if (false): ?>
  <p><b>OR</b></p>
  <?php echo form_open_multipart('file/do_paste'); ?>
    <p>
      <textarea name="content" cols="80" rows="20"></textarea><br />
      <div style="display: none">Email: <input type="text" name="email" size="20" /></div>
      Optional password (for deletion): <input type="password" name="password" size="10" /><br />
      <input type="submit" value="Paste" name="process" />
    </p>
  </form>
<?php endif; ?>
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
