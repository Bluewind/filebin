<?php
if (is_cli_client() && !isset($force_full_html)) {
	return;
}
?>
	</div>
<?php echo include_js("/data/js/jquery-1.8.2.min.js"); ?>
<?php echo include_js("/data/js/jquery-ui-1.8.23.custom.min.js"); ?>
<?php echo include_js("/data/js/bootstrap-2.1.1.min.js"); ?>
<?php echo include_js("/data/js/script.js"); ?>
<?php echo include_registered_js(); ?>
</body>
</html>
