<div class="container paste-container container-wide">
	<div style="border:1px solid #ccc;">
		<div class="navbar navbar-default navbar-static-top navbar-paste">
			<ul class="nav navbar-nav navbar-left dont-float">
				<li><a href="<?=site_url($id)."/"; ?>" class="navbar-brand" data-toggle="modal"><?php echo $title ?></a></li>
				<li class="divider"></li>
				<li class="dropdown">
					<a href="#" class="dropdown-toggle lexer-toggle" data-toggle="dropdown">
						Language: <?php echo htmlspecialchars($current_highlight); ?>
						<b class="caret"></b>
					</a>
					<div class="dropdown-menu" style="padding: 15px;">
						<form class="lexer-form">
						<input data-base-url="<?=site_url($id); ?>" type="text" id="language-<?=$id; ?>" placeholder="Language" class="form-control">
						</form>
					</div>
				</li>
				<li class="divider"></li>
				<li>
					<a href="#file-info-<?=$id; ?>" role="button" data-toggle="modal">Info</a>
				</li>
				<?php if (isset($user_logged_in) && $user_logged_in) { ?>
				<li class="divider"></li>
				<li><a href="<?php echo site_url('file/index?repaste='.$id); ?>" role="button">Repaste</a></li>
				<?php } ?>
			</ul>
			<div class="btn-group navbar-right" style="margin: 8px;">
				<a class="btn btn-default linewrap-toggle" rel="tooltip" title="Toggle wrapping of long lines">Linewrap</a>
				<div class="btn-group">
					<a class="btn btn-default dropdown-toggle tabwidth-toggle" rel="tooltip" title="Set tab width in spaces" data-toggle="dropdown" href="#">Tab width: <span class="tabwidth-value"></span> <span class="caret"></span></a>
					<div class="dropdown-menu tabwidth-dropdown">
						<form class="tabwidth-form">
							<input type="number" class="form-control" min="0">
						</form>
					</div>
				</div>
				<a href="<?php echo site_url($id."/plain") ?>" class="btn btn-default" rel="tooltip" title="View as plain text">Plain</a>
				<a href="<?php echo site_url($id) ?>" class="btn btn-default" rel="tooltip" title="View as raw file (org. mime type)">Raw</a>
				<?php if ($current_highlight === 'rmd') { ?>
				<a href="<?php echo site_url($id)."/" ?>" class="btn btn-default" rel="tooltip" title="Render as Code">Code</a>
				<?php } else { ?>
				<a href="<?php echo site_url($id."/rmd") ?>" class="btn btn-default" rel="tooltip" title="Render as Markdown">Markdown</a>
				<?php } ?>
			</div>
		</div> <!-- .navbar -->
		<div id="file-info-<?=$id; ?>" class="modal fade" role="dialog" aria-labelledby="file-info-<?=$id; ?>" aria-hidden="true">
			<div class="modal-dialog">
				<div class="modal-content">
					<div class="modal-header">
						<button type="button" class="close" data-dismiss="modal">&times;</button>
						<h3 class="modal-title">Paste Information</h3>
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
						<?php echo form_open("file/do_delete/", array("style" => "display: inline")); ?>
							<input type="hidden" name="ids[<?php echo $id; ?>]" value="<?php echo $id; ?>">
							<button class="btn btn-danger pull-left" aria-hidden="true">Delete</button>
						</form>
						<button class="btn btn-default" data-dismiss="modal" aria-hidden="true">Close</button>
					</div>
				</div>
			</div>
		</div> <!-- .modal -->
	</div>
