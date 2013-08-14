<div class="center">
	<?php if (!empty($errors)) {
		echo "<p>";
		foreach ($errors as $error) {
			echo "${error["id"]}: ${error["reason"]}<br>\n";
		}
		echo "</p>";
	} ?>

	<p><?php echo $deleted_count; ?> of <?php echo $total_count; ?> deleted.</p>
</div>
