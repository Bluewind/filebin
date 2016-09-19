#!/usr/bin/php
<?php

if (version_compare(PHP_VERSION, '5.5.0') < 0) {
	echo "Filebin will most certainly not work with php older than 5.5. Use at your own risk!\n\n";
}

$errors = "";

define('SELF', pathinfo(__FILE__, PATHINFO_BASENAME));
define('FCPATH', str_replace(SELF, "", __FILE__));
if (getenv("HOME") == "") {
	putenv('HOME='.FCPATH);
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

// test imagemagick
ob_start();
passthru("convert --version 2>&1", $buf);
ob_end_clean();
if ($buf != "0") {
	$errors .= " - Error when testing imagemagick (convert): Return code was \"$buf\".\n";
}

// test composer
ob_start();
passthru("composer --version 2>&1", $buf);
ob_end_clean();
if ($buf != "0") {
	$errors .= " - Error when testing composer: Return code was \"$buf\".\n";
}

// test PHP modules
$mod_groups = array(
	"thumbnail generation - GD" => array("gd"),
	"thumbnail generation - EXIF" => array("exif"),
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
	echo "Errors occured:\n";
	echo $errors;
	exit(1);
} else {
	echo "Dependency checks completed sucessfully.\n";
}
