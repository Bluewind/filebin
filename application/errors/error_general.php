<?php

if (is_cli_client()) {
	echo $heading."\n";
	echo $message."\n";
	exit();
}

$title = "Error";
$is_error_page = true;

include 'application/views/file/header.php';

?>
	<div class="error">
		<h1><?php echo $heading; ?></h1>
		<?php echo $message; ?>
	</div>

<?php
include 'application/views/file/footer.php';
