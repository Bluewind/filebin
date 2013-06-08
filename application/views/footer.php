<?php
if (is_cli_client() && !isset($force_full_html)) {
	return;
}
?>
	</div>
<?php echo include_js("/data/js/jquery-2.0.2.min.js"); ?>
<?php echo include_js("/data/js/jquery-ui-1.10.3.custom.min.js"); ?>
<?php echo include_js("/data/js/bootstrap-2.3.2.min.js"); ?>
<?php echo include_js("/data/js/script.js"); ?>
<?php echo include_registered_js(); ?>
</body>
</html>
