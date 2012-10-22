<?php
if (is_cli_client() && !isset($force_full_html)) {
	return;
}
?>
	</div>

	<script src="<?php echo link_with_mtime("/data/js/jquery-1.8.2.min.js"); ?>"></script>
	<script src="<?php echo link_with_mtime("/data/js/jquery-ui-1.8.23.custom.min.js"); ?>"></script>
	<script src="<?php echo link_with_mtime("/data/js/bootstrap-2.1.1.min.js"); ?>"></script>
	<script src="<?php echo link_with_mtime("/data/js/script.js"); ?>"></script>
</body>
</html>
