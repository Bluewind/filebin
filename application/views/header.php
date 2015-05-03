<?php
if (is_cli_client() && !isset($force_full_html)) {
	return;
}
?><!DOCTYPE html>
<html lang="en">

<head>
	<meta charset="utf-8">
	<title><?php echo isset($title) ? $title : 'FileBin'; ?></title>
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<meta name="robots" content="noindex,nofollow" />
	<meta name="description" content="">
	<meta name="author" content="">

	<link href="<?php echo link_with_mtime("/data/css/ui-lightness/jquery-ui-1.10.3.custom.min.css"); ?>" rel="stylesheet">
	<link href="<?php echo link_with_mtime("/data/css/bootstrap.min.css"); ?>" rel="stylesheet">
	<link href="<?php echo link_with_mtime("/data/css/style.css"); ?>" rel="stylesheet">
	<link href="<?php echo link_with_mtime("/data/css/colorbox.css"); ?>" rel="stylesheet">
	<?php
		if (file_exists(FCPATH."data/local/style.css")) {
			echo '<link href="'.link_with_mtime("/data/local/style.css").'" rel="stylesheet">';
		}

		if (file_exists(FCPATH."data/local/favicon.png")) {
			echo '<link href="'.link_with_mtime("/data/local/favicon.png").'" rel="shortcut icon">';
		}
	?>
	<script src="<?php echo link_with_mtime("/data/js/vendor/require.js"); ?>"></script>
	<script type="text/javascript">
		/* <![CDATA[ */
		window.appConfig = {};
		require.config({
			baseUrl: '/data/js',
			urlArgs: '<?php echo js_cache_buster(); ?>',
			paths: {
				'main': ['main.min', 'main']
			}
		});
		require(['main']);
		/* ]]> */
	</script>
</head>

<body>
<div id="wrap">
<?php if (file_exists(FCPATH."data/local/header.inc.php")) {
	include FCPATH."data/local/header.inc.php";
}?>
	<nav class="navbar navbar-fixed-top navbar-inverse" role="navigation">
		<div class="container">
			<div class="navbar-header">
			    <button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-ex1-collapse">
				  <span class="sr-only">Toggle navigation</span>
				  <span class="icon-bar"></span>
				  <span class="icon-bar"></span>
				  <span class="icon-bar"></span>
				</button>
				<a class="navbar-brand" href="<?php echo site_url(); ?>"><?php
					if (file_exists(FCPATH."data/local/logo.svg")) {
						echo '<img class="brand-icon" src="'.link_with_mtime("/data/local/logo.svg").'" style="height: 20px"> FileBin';
					} else {
						echo "FileBin";
					}
					?>
				</a>
			</div>
			<div class="collapse navbar-collapse navbar-ex1-collapse">
				<?php if(!isset($GLOBALS["is_error_page"])) { ?>
					<ul class="nav navbar-nav navbar-right">
						<?php if (isset($user_logged_in) && $user_logged_in) { ?>
							<li><a class="navbar-link" href="<?php echo site_url("/user/logout"); ?>">Logout</a></li>
						<?php } else { ?>
							<li class="dropdown">
										<a class="dropdown-toggle" href="#" data-toggle="dropdown">Login <b class="caret"></b></a>
										<div class="dropdown-menu" style="padding: 5px;">
										<?php if(auth_driver_function_implemented("can_reset_password")) { ?>
											<p><?php echo anchor("user/reset_password", "Forgot your password?"); ?></p>
										<?php } ?>
										<?php echo form_open("user/login", array("class" => "form-signin")); ?>
											<input type="text" name="username" placeholder="Username" class="form-control">
											<input type="password" name="password" placeholder="Password" class="form-control">
											<button type="submit" name="process" class="btn btn-default btn-block">Login</button>
										</form>
									</div>
							</li>
						<?php } ?>
					</ul>
					<?php }; ?>
					<ul class="nav navbar-nav">
						<?php if (isset($user_logged_in) && $user_logged_in) { ?>
							<li><a href="<?php echo site_url("file/index") ?>"><span class="glyphicon glyphicon-pencil"></span> New</a></li>
							<li><a href="<?php echo site_url("file/upload_history") ?>"><span class="glyphicon glyphicon-book"></span> History</a></li>
							<li class="dropdown">
								<a href="<?php echo site_url("user/index"); ?>" class="dropdown-toggle" data-toggle="dropdown">
									<span class="glyphicon glyphicon-user"></span> Account <b class="caret"></b>
								</a>
								<ul class="dropdown-menu">
									<?php include "user/nav.php"; ?>
								</ul>
							</li>
						<?php } ?>
					</ul>
			</div>
		</div>
	</nav>
	<div id="navbar-height"></div>

	<div class="container">
	<?php
	if (isset($alerts)) {
		foreach ($alerts as $alert) { ?>
			<div class="alert alert-dismissable alert-<?php echo $alert["type"]; ?>" style="text-align: center">
				<button type="button" class="close" data-dismiss="alert">&times;</button>
				<?php echo $alert["message"]; ?>
			</div>
			<?php
		}
	}
	?>
