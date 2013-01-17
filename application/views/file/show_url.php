<div class="center">
	<p>You can get your file(s) here:</p>
	<p>
	<?php foreach ($urls as $key => $url) { ?>
		<a href="<?php echo $url; ?>"><?php echo $url; ?></a><br />
	<?php } ?>
	</p>
</div>
