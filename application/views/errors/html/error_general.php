<?php
defined('BASEPATH') OR exit('No direct script access allowed');

// fancy error page only works if we can load helpers
if (class_exists("CI_Controller") && !isset($GLOBALS["is_error_page"]) && isset(get_instance()->load)) {
	if (!isset($title)) {
		$title = "Error";
	}
	$GLOBALS["is_error_page"] = true;

	$CI =& get_instance();
	$CI->load->helper("filebin");
	$CI->load->helper("url");

	if (is_cli()) {
		$message = str_replace("</p>", "</p>\n", $message);
		$message = strip_tags($message);
		echo "$heading: $message\n";
		exit();
	}

	include APPPATH.'views/header.php';

	?>
		<div class="error">
			<h1><?php echo $heading; ?></h1>
			<?php echo $message; ?>
		</div>

	<?php
	include APPPATH.'views/footer.php';
} elseif (php_sapi_name() === 'cli' OR defined('STDIN')) {
	echo "# $heading\n";
	$msg = strip_tags(str_replace("<br>", "\n", $message));
	foreach (explode("\n", $msg) as $line) {
		echo "# $line\n";
	}
	exit(255);
} else {
	// default CI error page
?><!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title>Error</title>
	<style type="text/css">

	::selection { background-color: #f07746; color: #fff; }
	::-moz-selection { background-color: #f07746; color: #fff; }

	body {
		background-color: #fff;
		margin: 40px auto;
		max-width: 1024px;
		font: 16px/24px normal "Helvetica Neue", Helvetica, Arial, sans-serif;
		color: #808080;
	}

	a {
		color: #dd4814;
		background-color: transparent;
		font-weight: normal;
		text-decoration: none;
	}

	a:hover {
		color: #97310e;
	}

	h1 {
		color: #fff;
		background-color: #dd4814;
		border-bottom: 1px solid #d0d0d0;
		font-size: 22px;
		font-weight: bold;
		margin: 0 0 14px 0;
		padding: 5px 15px;
		line-height: 40px;
	}

	h2 {
		color:#404040;
		margin:0;
		padding:0 0 10px 0;
	}

	code {
		font-family: Consolas, Monaco, Courier New, Courier, monospace;
		font-size: 13px;
		background-color: #f5f5f5;
		border: 1px solid #e3e3e3;
		border-radius: 4px;
		color: #002166;
		display: block;
		margin: 14px 0 14px 0;
		padding: 12px 10px 12px 10px;
	}

	#container {
		margin: 10px;
		border: 1px solid #d0d0d0;
		box-shadow: 0 0 8px #d0d0d0;
		border-radius: 4px;
	}

	p {
		margin: 0 0 10px;
		padding:0;
	}

	#body {
		margin: 0 15px 0 15px;
		min-height: 96px;
	}
	</style>
</head>
<body>
	<div id="container">
		<h1><?php echo $heading; ?></h1>
		<div id="body">
			<?php echo $message; ?>
		</div>
	</div>
</body>
</html>
<?php
}
