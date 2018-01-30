<?php if (isset($user_logged_in) && $user_logged_in) { ?>
<?php echo form_open_multipart('file/do_websubmit'); ?>
	<div class="row">
		<div class="col-lg-12 col-md-12 col-sm-12 col-xs-12 text-upload-form">
				<div class="panel panel-default">
					<div class="panel-heading">
						<h3 class="panel-title">Text paste</h3>
					</div>
					<div class="panel-body" id="textboxes">
						<ul class="nav nav-tabs">
							<li class="active"><a href="#text-upload-tab-1" data-toggle="tab">Paste 1 </a></li>
						</ul>
						<div class="tab-content">
							 <div class="tab-pane active" id="text-upload-tab-1">
								<div class="panel panel-default">
									<div class="panel-heading">
										<input type="text" name="filename[1]" class="form-control" placeholder="Filename/title (default: stdin)">
									</div>
									<textarea name="content[1]" class="form-control text-upload" placeholder="Paste content"><?php
										if (isset($textarea_content)) {
											echo $textarea_content;
										}
									?></textarea>
								</div>
							</div>
						</div>
					</div>
			</div>
		</div>
	</div>
	<div class="row">
		<div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
			<div class="panel panel-default">
				<div class="panel-heading">
					<h3 class="panel-title">File upload</h3>
				</div>
				<div class="panel-body">
					<div>
						<input class="file-upload" type="file" name="file[]" multiple="multiple"><br>
					</div>
				</div>
			</div>
		</div>
		<div class="col-lg-6 col-md-6 col-sm-6 col-xs-12">
			<div class="panel panel-info">
				<div class="panel-heading">
					<h3 class="panel-title">Notice!</h3>
				</div>
				<div class="panel-body">
					<p>
						You can upload files and paste text at the same time. Empty text or file inputs will be ignored.
					</p>
					<p><button type="submit" id="upload_button" class="btn btn-primary">Upload/Paste it!</button></p>
					<p>
						Uploads/pastes are <?php if ($upload_max_age > 0) {
							echo "deleted after ".expiration_duration($upload_max_age);
							if ($small_upload_size > 0) {
								echo " unless they are smaller than ".format_bytes($small_upload_size);
							}
						} else {
							echo "stored forever";
						} ?>. Maximum upload size is <?php echo format_bytes($max_upload_size); ?>.
						You can upload a maximum of <?php echo ini_get("max_file_uploads"); ?> files at once.
					</p>
				</div>
			</div>
		</div>
	</div>
</form>

<script type="text/javascript">
    /* <![CDATA[ */
	window.appConfig.maxUploadSize = "<?php echo $max_upload_size; ?>";
	window.appConfig.maxFilesPerUpload = "<?php echo ini_get("max_file_uploads"); ?>";
    /* ]]> */
</script>

<?php } else { ?>
	<?php echo form_open('user/login', array('class' => 'form-inline')); ?>
		<input type="text" name="username" placeholder="Username" autofocus class="form-control inline-input"/>
		<input type="password" name="password" placeholder="Password" class="form-control inline-input"/>
		<input type="submit" class="btn btn-primary" value="Login" name="process" />
		<?php if(auth_driver_function_implemented("can_reset_password")) { ?>
			<p class="help-block"><?php echo anchor("user/reset_password", "Forgot your password?"); ?></p>
		<?php } ?>
	</form>
<?php } ?>
<div class="row">
	<div class="col-lg-6">
		<div class="page-header"><h1>Features</h1></div>
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
			<dt>/file/thumbnail/&lt;ID&gt;</dt><dd>return a JPEG thumbnail for the ID (only works for some file types)</dd>
		</dl>
		<p>
			If your upload is not detected as text, only <b>/&lt;ID&gt;/qr</b>,
			<b>/&lt;ID&gt;/plain</b>, <b>/&lt;ID&gt;/info</b> and
			<b>/file/thumbnail/&lt;ID&gt;</b> will work as above and all others will simply
			return the file with the detected MIME type.
		</p>

		<h3>How to link your multipastes:</h3>
		<p>Multipaste IDs begin with <code>m-</code> and only support the following features.</p>
		<dl class="dl-horizontal">
			<dt>/&lt;ID&gt;/</dt><dd>automatically display everything in a sensible way</dd>
			<dt>/&lt;ID&gt;/qr</dt><dd>display a qr code containing a link to <span class="example">/&lt;ID&gt;/</span></dd>
			<dt>/&lt;ID&gt;/info</dt><dd>display some information about the multipaste</dd>
			<dt>/&lt;ID&gt;/tar</dt><dd>download a tarball of all files in the multipaste (files may be renamed to avoid conflicts)</dd>
			<dt>/&lt;ID&gt;/pls</dt><dd>download a PLS playlist of all audio/video files in the multipaste</dd>
		</dl>

		<h3>Special filenames:</h3>
		<dl class="dl-horizontal">
			<dt>*.asciinema.json</dt><dd>treat the file as an <a href="https://asciinema.org/">asciinema screencast</a> and display a videoplayer for it</dd>
		</dl>
	</div>

	<div class="col-lg-6">
		<div class="page-header"><h1>Information</h1></div>
		<p>
			This website's primary goal is aiding developers, power users, students and
			alike in solving problems, debugging software, sharing their configuration,
			etc. It is not intended to distribute confidential or harmful information,
			scripts or software or copyrighted content for which you do not have a
			distribution license.
		</p>
		<?php if(auth_driver_function_implemented("can_register_new_users")) { ?>
			<p>
				If you want an account, ask someone who is already using this
				service to <a href="<?php echo site_url("user/invite"); ?>">invite</a> you.
			</p>
			<p>
				Invitations are used to control abuse and encourage users to "be nice". They
				are not intended as a means of exclusivity. In case of abuse reports, involved
				accounts may be banned and the user who invited them may also be banned. The
				invitation tree will be followed upwards if necessary.
			</p>
		<?php } ?>

		<h3>Clients</h3>
			<h4>Linux</h4>
				<p>
					Development: <?php echo anchor("https://git.server-speed.net/users/flo/fb/"); ?><br />
					Latest release: <?php echo $client_link ? anchor($client_link) : "unknown"; ?><br />
					GPG sigs, older versions: <?php echo anchor("https://paste.xinu.at/data/client"); ?>
				</p>

				<p>
					Arch Linux: <code>pacman -S fb-client</code><br />
					Gentoo: Add <a href="https://git.holgersson.xyz/holgersson-overlay/tree/README">this overlay</a> and run <code>emerge -a fb-client</code><br />
					FreeBSD: <code>pkg install fb</code><br />
				</p>

			<h4>Android</h4>
				<p>
					Development: <a href="https://github.com/sebastianrakel/fb-client-android">sebastianrakel/fb-client-android @ Github</a><br>
					F-Droid Store: <a href="https://f-droid.org/repository/browse/?fdid=eu.devunit.fb_client">fb-client Android @ F-Droid</a><br>
				</p>
	</div>
</div>
