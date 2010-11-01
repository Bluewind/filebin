<div style="margin-top: 100px; text-align:center">
  <?php echo form_open_multipart('file/do_upload'); ?>
    <p>
      File: <input type="file" name="file" size="30" />
      <input type="submit" value="Upload" name="process" />
    </p>
  </form>
  <p><b>OR</b></p>
  <?php echo form_open_multipart('file/do_paste'); ?>
    <p>
      <textarea name="content" cols="80" rows="20"></textarea><br />
      <input type="submit" value="Paste" name="process" />
    </p>
  </form>
</div>
<br />
<p>Uploads/pastes are deleted after 5 days<?php if($small_upload_size > 0): ?>
  unless they are smaller than <?php echo format_bytes($small_upload_size); ?>
  <?php endif; ?>. Maximum upload size is <?php echo format_bytes($max_upload_size); ?></p>
<p>For shell uploading/pasting and download information for the client go to <a href="<?php echo site_url("file/client"); ?>"><?php echo site_url("file/client"); ?></a></p>
<br />
<p>If you experience any problems feel free to <a href="http://bluewind.at/?id=1">contact me</a>.</p>
<br />
<div class="small">
  <p>This service is provided without warranty of any kind and may not be used to distribute copyrighted content.</p>
</div>
