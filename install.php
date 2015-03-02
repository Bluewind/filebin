<?php

if (version_compare(PHP_VERSION, '5.3.0') < 0) {
	echo "Just a heads up: Filebin has not been tested with php older than 5.3. You might run into problems.";
}

$errors = "";

define('SELF', pathinfo(__FILE__, PATHINFO_BASENAME));
define('FCPATH', str_replace(SELF, "", __FILE__));
if (getenv("HOME") == "") {
	putenv('HOME='.FCPATH);
}

if (file_exists(FCPATH."is_installed")) {
	exit("already installed\n");
}

$old_path = getenv("PATH");
putenv("PATH=$old_path:/usr/local/bin:/usr/bin:/bin:/usr/local/sbin:/usr/sbin:/sbin");

// test exec()
exec("echo -n works") == "works" || $errors .= "exec() failed\n";

// test passthru()
ob_start();
passthru("echo -n works");
$buf = ob_get_contents();
ob_end_clean();
$buf == "works" || $errors .= "passthru() failed\n";

// test perl deps
$perldeps = array(
	"Text::Markdown"
);
foreach ($perldeps as $dep) {
	ob_start();
	passthru("perl 2>&1 -M'$dep' -e1");
	$buf = ob_get_contents();
	ob_end_clean();
	if ($buf != "") {
		$errors .= " - failed to find perl module: $dep.\n";
		$errors .= $buf;
	}
}

// test pygmentize
ob_start();
passthru("pygmentize -V 2>&1", $buf);
ob_end_clean();
if ($buf != "0") {
	$errors .= " - Error when testing pygmentize: Return code was \"$buf\".\n";
}

// test ansi2html
ob_start();
passthru("ansi2html -h 2>&1", $buf);
ob_end_clean();
if ($buf != "0") {
	$errors .= " - Error when testing ansi2html: Return code was \"$buf\".\n";
}

// test qrencode
ob_start();
passthru("qrencode -V 2>&1", $buf);
ob_end_clean();
if ($buf != "0") {
	$errors .= " - Error when testing qrencode: Return code was \"$buf\".\n";
}

// test imagemagick
ob_start();
passthru("convert --version 2>&1", $buf);
ob_end_clean();
if ($buf != "0") {
	$errors .= " - Error when testing imagemagick (convert): Return code was \"$buf\".\n";
}

// test PHP modules
$mod_groups = array(
	"thumbnail generation" => array("gd"),
	"thumbnail generation" => array("exif"),
	"database support" => array("mysql", "mysqli", "pgsql", "pdo_mysql", "pdo_pgsql"),
	"multipaste tarball support" => array("phar"),
);
foreach ($mod_groups as $function => $mods) {
	$found = 0;
	foreach ($mods as $module) {
		if (extension_loaded($module)) {
			$found++;
		}
	}
	if ($found == 0) {
		$errors .= " - none of the modules needed for $function are loaded. Make sure to load at least one of these: ".implode(", ", $mods)."\n";
	}
}


if ($errors != "") {
	echo nl2br("Errors occured:\n");
	echo nl2br($errors);
} else {
// TODO: Make this an actual installer
	file_put_contents(FCPATH."is_installed", "true");
	echo nl2br("Tests completed.\n"
		."The following steps remain:\n"
		." - copy the files from ./application/config/example/ to ./application/config/ and edit them to suit your setup\n"
		." - the database will be set up automatically\n"
	);
}
