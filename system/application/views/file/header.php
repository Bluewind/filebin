<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
	<title><?php echo isset($title) ? $title : ''; ?></title>
	<link rel="stylesheet" type="text/css" href="<?php echo base_url(); ?>data/default.css" media="screen" />
	<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
</head>

<body>
	<div class="top">
		<?php echo anchor('file/index', 'New'); ?>
	</div>
	
	<div class="clearer" ></div>

	<div class="content">
