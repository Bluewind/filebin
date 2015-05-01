<?php
$force_full_html = true;
include(FCPATH."application/views/header.php"); ?>

</div><!-- .container -->
<script type="text/javascript">
	/* <![CDATA[ */
	window.appConfig.lexers = <?php echo json_encode($lexers); ?>;
	/* ]]> */
</script>

<?php if (isset($error_message)) {
	include 'framgents/alert-wide.php';
} ?>
