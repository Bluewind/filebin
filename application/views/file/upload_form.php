<?php if (isset($username) && $username) { ?>
<div class="well">
	<div class="row-fluid">
		<div class="span6">
			<div style="border-right:1px solid #ddd;padding-right:25px;">
			<?php echo form_open_multipart('file/do_paste'); ?>
				<h2>Text paste</h2>
				<textarea name="content" style="width:98%; height:300px;"></textarea>
				<button type="submit" class="btn btn-primary">Paste it!</button>
			</form>
			</div>
		</div>
		<div class="span6">
			<?php echo form_open_multipart('file/do_upload'); ?>
				<h2>File upload</h2>
				<input class="file-upload" type="file" name="file[]" multiple="multiple"><br>
				<button type="submit" id="upload_button" class="btn btn-primary">Upload it!</button>
			</form>
			<div class="alert alert-block alert-info">
				<h4 class="alert-heading">Notice!</h4>
				<p>
					Uploads/pastes are deleted after <?php echo $upload_max_age; ?> days
					<?php if($small_upload_size > 0) {
						echo "unless they are smaller than ".format_bytes($small_upload_size);
					} ?>. Maximum upload size is <?php echo format_bytes($max_upload_size); ?>.
					You can upload a maximum of <?php echo ini_get("max_file_uploads"); ?> files at once.
				</p>
			</div>
		</div>
	</div>
</div>

<script type="text/javascript">
    /* <![CDATA[ */
	var max_upload_size = "<?php echo $max_upload_size; ?>";
    /* ]]> */
</script>

<?php } else { ?>
	<?php echo form_open('user/login'); ?>
		<input type="text" name="username" placeholder="Username" />
		<input type="password" name="password" placeholder="Password" />
		<input type="submit" class="btn btn-primary" value="Login" name="process" style="margin-bottom: 9px" />
	</form>
<?php } ?>
<div class="row">
	<div class="span6">
		<div class="page-header"><h1>Features</h1></div>
		<p>For shell uploading/pasting and download information for the client go to <a href="<?php echo site_url("file/client"); ?>"><?php echo site_url("file/client"); ?></a></p>
		<p>You can use the <?php echo anchor("file/upload_history", "history"); ?> to find old uploads.</p>
		<h3>How to link your pastes:</h3>
		<dl class="dl-horizontal">
			<dt>/&lt;ID&gt;/</dt><dd>automatically highlight the paste</dd>
			<dt>/&lt;ID&gt;</dt><dd>set the detected MIME type and let the browser do the rest</dd>
			<dt>/&lt;ID&gt;/plain</dt><dd>force the MIME type to be text/plain</dd>
			<dt>/&lt;ID&gt;/&lt;file extension&gt;</dt><dd>override auto detection and use the supplied file extension or language name for highlighting</dd>
			<dt>/&lt;ID&gt;/qr</dt><dd>display a qr code containing a link to <span class="example">/&lt;ID&gt;/</span></dd>
			<dt>/&lt;ID&gt;/rmd</dt><dd>convert markdown to HTML</dd>
			<dt>/&lt;ID&gt;/ascii</dt><dd>convert text with ANSI (shell) escape codes to HTML</dd>
			<dt>/&lt;ID&gt;/info</dt><dd>display some information about the ID</dd>
		</dl>
		<p>If your upload is not detected as text, only <b>/&lt;ID&gt;/qr</b>, <b>/&lt;ID&gt;/plain</b> and <b>/&lt;ID&gt;/info</b> will work as above and all others will simply return the file with the detected MIME type.</p>
	</div>
	<div class="span6">
		<div class="page-header"><h1>Information</h1></div>
		<p>This website's primary goal is aiding developers, power users, students and alike in solving problems, debugging software, sharing their configuration, etc. It is not intended to distribute confidential or harmful information, scripts or software.</p>
		<p>If you believe you deserve an account, ask someone who is already using this service to <a href="<?php echo site_url("user/invite"); ?>">invite</a> you.</p>
		<?php if(isset($contact_me_url) && $contact_me_url) { ?>
			<p>If you experience any problems feel free to <a href="<?php echo $contact_me_url; ?>">contact me</a>.</p>
		<?php } ?>
	</div>
</div>
