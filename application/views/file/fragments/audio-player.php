<div class="container-wide">
<p>
	<audio controls="controls">
		<source src="<?php echo site_url($filedata["id"]); ?>">
	</audio>
	<?php echo anchor(site_url($filedata['id']), htmlspecialchars($filedata["filename"])); ?>
</p>
</div>
