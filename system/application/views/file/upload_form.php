<div style="text-align:center">
  <?php echo form_open_multipart('file/do_upload'); ?>
    <p>
      File: <input type="file" name="file" size="30" />
      <input type="submit" value="Upload" name="process" />
    </p>
  </form>
  <br />
  <p>OR</p>
  <br />
  <?php echo form_open_multipart('file/do_paste'); ?>
    <p>
      <textarea name="content" cols="80" rows="20"></textarea><br />
      <input type="submit" value="Paste" name="process" />
    </p>
  </form>
</div>
<br /><br />
<p>Uploads/pastes are deleted after 5 days<?php if($small_upload_size > 0): ?>
  unless they are smaller than <?php echo format_bytes($small_upload_size); ?>
  <?php endif; ?>. Maximum upload size is <?php echo format_bytes($max_upload_size); ?></p>
<br />
<p>For shell uploading/pasting use:</p>
<pre>
curl -n -F "content=&lt;-" <?php echo base_url(); ?> &lt; file      (not binary safe)
cat file | curl -n -F "content=&lt;-" <?php echo base_url(); ?>  (not binary safe)
curl -n -F "file=@/home/user/foo" <?php echo base_url(); ?>   (binary safe)
</pre>
<br />
<p>If you want to use authentication add the following to your ~/.netrc:</p>
<pre>
machine paste.xinu.at password my_secret_password
</pre>
<br />
<p>If you want to you can use this script to upload files, paste text or delete your uploads:<br />
<a href="<?php echo $client_link; ?>"><?php echo $client_link; ?></a></p>
<p>Arch Linux users: pacman -S fb-client<br />
Debian users: <a href="<?php echo $client_link_deb; ?>"><?php echo $client_link_deb; ?></a><br />
Slackware users: <a href="<?php echo $client_link_slackware; ?>"><?php echo $client_link_slackware; ?></a></p>

<br />
<p>If you experience any problems feel free to <a href="http://bluewind.at/?id=1">contact me</a>.</p>
<br />
<br />
<div class="small">
  <p>This service is provided without warranty of any kind and may not be used to distribute copyrighted content.</p>
</div>
