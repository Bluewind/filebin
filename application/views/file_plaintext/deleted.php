<?php if (!empty($errors)) {
	echo implode("\n", $errors);
} ?>
<?php if (!empty($msgs)) {
	echo implode("\n", $msgs);
} ?>

<?php echo $deleted_count; ?> of <?php echo $total_count; ?> deleted.
