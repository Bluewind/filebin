<div class="container-wide">
<div class='panel panel-default'>
	<div class='panel-heading'>
		<?php echo anchor(site_url($filedata['id']), htmlspecialchars($filedata["filename"])); ?>
	</div>
	<div id="player-container-<?php echo $filedata['id']; ?>" class="asciinema_player" data-url="<?php echo site_url($filedata['id']); ?>"></div>
</div>
</div>
