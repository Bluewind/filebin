<?php include(FCPATH."application/views/header.php"); ?>

	</div>

	<script type="text/javascript">
		/* <![CDATA[ */
		window.lexers = <?php echo json_encode($lexers); ?>;
		window.paste_base = '<?php echo site_url($id) ?>';
		/* ]]> */
	</script>

	<?php if (isset($error_message)) { ?>
		<div class="alert alert-block alert-error" style="text-align: center">
			<?php echo $error_message; ?>
		</div>
	<?php } ?>

	<div class="container" style="padding-top:40px;background:#eee;padding:3px;">
		<div style="border:1px solid #ccc;">
		<div class="navbar navbar-static-top">
			<div class="navbar-inner" style="box-shadow: none;">
				<ul class="nav">
					<li><a href="#file-info" class="brand" data-toggle="modal"><?php echo $title ?></a></li>
					<li class="divider-vertical"></li>
					<li class="dropdown">
						<a href="#" class="dropdown-toggle" data-toggle="dropdown" id="language-toggle">
							Language: <?php echo $current_highlight; ?>
							<b class="caret"></b>
						</a>
						<div class="dropdown-menu" style="padding: 15px; padding-bottom: 0px;">
							<form>
								<input type="text" id="language" placeholder="Language" class="input-medium">
							</form>
						</div>
					</li>
					<li class="divider-vertical"></li>
					<li>
						<a href="#file-info" role="button" data-toggle="modal">Info</a>
						<div id="file-info" class="modal hide fade">
							<div class="modal-header">
								<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
								<h3>Paste Information</h3>
							</div>
							<div class="modal-body">
								<table class="table">
									<tr>
										<td style="border:0;">Filename:</td>
										<td style="border:0;"><?php echo htmlspecialchars($filedata["filename"]) ?></td>
									</tr>
									<tr>
										<td>Size:</td>
										<td><?php echo format_bytes($filedata["filesize"]) ?></td>
									</tr>
									<tr>
										<td>Mimetype:</td>
										<td><?php echo $filedata["mimetype"] ?></td>
									</tr>
									<tr>
										<td>Uploaded:</td>
										<td><?php echo date("r", $filedata["date"]) ?></td>
									</tr>
									<tr>
										<td>Removal:</td>
										<td><?php echo $timeout ?></td>
									</tr>
								</table>
							</div>
							<div class="modal-footer">
								<button class="btn" data-dismiss="modal" aria-hidden="true">Close</button>
							</div>
						</div>
					</li>
				</ul>
				<div class="btn-group pull-right" style="margin-top: 7px; margin-right:-10px;">
					<a href="<?php echo site_url($id."/plain") ?>" class="btn btn-small" rel="tooltip" title="View as plain text">Plain</a>
					<a href="<?php echo site_url($id) ?>" class="btn btn-small" rel="tooltip" title="View as raw file (org. mime type)">Raw</a>
					<?php if ($current_highlight === 'rmd') { ?>
						<a href="<?php echo site_url($id)."/" ?>" class="btn btn-small" rel="tooltip" title="Render as Code">Code</a>
					<?php } else { ?>
						<a href="<?php echo site_url($id."/rmd") ?>" class="btn btn-small" rel="tooltip" title="Render as Markdown">Markdown</a>
					<?php } ?>
				</div>
			</div>
		</div>
		<div id="paste-container">
