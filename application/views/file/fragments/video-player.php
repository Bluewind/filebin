<div class="container-wide">
<div class='panel panel-default'>
	<div class='panel-heading'>
		<?php echo anchor(site_url($filedata['id']), htmlspecialchars($filedata["filename"])); ?>
	</div>
	<div>
		<video controls="controls">
			<source src="<?php echo site_url($filedata["id"]); ?>">
		</video>
	</div>
</div>
</div>
