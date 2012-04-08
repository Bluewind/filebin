<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">

<head>
	<title><?php echo isset($title) ? $title : ''; ?></title>
	<link rel="stylesheet" type="text/css" href="<?php echo base_url(); ?>data/default.css?<?php echo filemtime(FCPATH."/data/default.css"); ?>" media="screen" />
	<meta http-equiv="Content-type" content="text/html; charset=utf-8" />
	<meta name="robots" content="noindex,nofollow" />
</head>

<body>
	<div class="top">
		<?php echo anchor('file/index', 'New'); ?>

		<?php if (!isset($username)) { ?>
			<div style="float: right">
				<?=form_open("user/login"); ?>
					<input type="text" name="username" />
					<input type="password" name="password" />
					<input type="submit" value="Login" name="process" />
				</form>
			</div>
		<?php } ?>
	</div>

	<div class="content">
