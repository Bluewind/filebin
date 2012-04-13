<? if ($username) { ?>
<div class="center">
  <?php echo form_open_multipart('file/do_upload'); ?>
    <p>
      File: <input type="file" id="file" name="file" size="30" />
      <input type="submit" value="Upload" id="upload_button" name="process" />
    </p>
  </form>
  <p><b>OR</b></p>
  <?php echo form_open_multipart('file/do_paste'); ?>
    <p>
      <textarea id="textarea" name="content" cols="80" rows="20"></textarea><br />
      <input  type="submit" value="Paste" name="process" />
    </p>
  </form>
  <script type="text/javascript">
    /* <![CDATA[ */
  var max_upload_size = "<?php echo $max_upload_size; ?>";
    /* ]]> */
  </script>
  <script type="text/javascript" src="<?php echo base_url(); ?>data/js/upload_form.js?<?php echo filemtime(FCPATH."/data/js/upload_form.js"); ?>"></script>
</div>
<? } else { ?>
You have to <?=anchor("user/login", "log in"); ?> to be able to upload/paste.
<div id="login-form">
	<?=form_open("user/login"); ?>
		<input type="text" name="username" />
		<input type="password" name="password" />
		<input type="submit" value="Login" name="process" />
	</form>
</div>
<? } ?>
<br />
<p>Uploads/pastes are deleted after <?php echo $upload_max_age; ?> days<?php if($small_upload_size > 0): ?>
  unless they are smaller than <?php echo format_bytes($small_upload_size); ?>
  <?php endif; ?>. Maximum upload size is <?php echo format_bytes($max_upload_size); ?></p>
<h2>Features</h2>
<p>For shell uploading/pasting and download information for the client go to <a href="<?php echo site_url("file/client"); ?>"><?php echo site_url("file/client"); ?></a></p>
<p>You can use the <?php echo anchor("file/upload_history", "history"); ?> to find old uploads.</p>
<p>How to link your pastes:</p>
<ul>
	<li><span class="example">/&lt;ID&gt;/</span> automatically highlight the paste</li>
	<li><span class="example">/&lt;ID&gt;</span> set the detected MIME type and let the browser do the rest</li>
	<li><span class="example">/&lt;ID&gt;/plain</span> force the MIME type to be text/plain</li>
	<li><span class="example">/&lt;ID&gt;/&lt;file extension&gt;</span> override auto detection and use the supplied file extension or language name for highlighting</li>
	<li><span class="example">/&lt;ID&gt;/qr</span> display a qr code containing a link to <span class="example">/&lt;ID&gt;/</span></li>
	<li><span class="example">/&lt;ID&gt;/rmd</span> convert markdown to HTML</li>
	<li><span class="example">/&lt;ID&gt;/ascii</span> convert text with ANSI (shell) escape codes to HTML</li>
</ul>
<p>If your upload is not detected as text, only <span class="example">/&lt;ID&gt;/qr</span> and <span class="example">/&lt;ID&gt;/plain</span> will work as above and all others will simply return the file with the detected MIME type.</p>

<h2>Information</h2>
<p>This website's primary goal is aiding developers, users, <a href="http://www.catb.org/~esr/faqs/hacker-howto.html">hackers</a>, students and alike in solving problems, debugging software, sharing their configuration, etc. It is not intended to distribute confidential or harmful information, scripts or software.</p>

<p>If you believe you deserve an account, ask someone who is already using this service to <?=anchor("user/invite", "invite"); ?> you.</p>

<?php if($contact_me_url) {?><p>If you experience any problems feel free to <a href="<?php echo $contact_me_url; ?>">contact me</a>.</p>
<br /><?php }; ?>
<div class="small">
  <p>Icons by <a href="http://p.yusukekamiyamane.com/">Yusuke Kamiyamane</a></p>
  <p>This service is provided without warranty of any kind and may not be used to distribute copyrighted content.</p>
</div>
