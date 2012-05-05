<div class="center">
	<?php if (!empty($errors)) {
		echo "<p>";
		echo implode("<br />\n", $errors);
		echo "</p>";
	} ?>
	<?php if (!empty($msgs)) {
		echo "<p>";
		echo implode("<br />\n", $msgs);
		echo "</p>";
	} ?>

	<p><?php echo $deleted_count; ?> of <?php echo $total_count; ?> deleted.</p>
</div>
