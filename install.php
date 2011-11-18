<?php

if (version_compare(PHP_VERSION, '5.3.0') < 0) {
	echo "Just a heads up: Filebin has not been tested with php older than 5.3. You might run into problems.";
}

$errors = "";

define('SELF', pathinfo(__FILE__, PATHINFO_BASENAME));
define('FCPATH', str_replace(SELF, "", __FILE__));

// test exec()
exec("echo -n works") == "works" || $errors .= "exec() failed\n";

// test passthru()
ob_start();
passthru("echo -n works");
$buf = ob_get_contents();
ob_end_clean();
$buf == "works" || $errors .= "passthru() failed\n";

// test perl HTML::FromANSI
ob_start();
passthru("/usr/bin/perl ".FCPATH."/scripts/install_helper.pl");
$buf = ob_get_contents();
ob_end_clean();
if ($buf != "works") {
	$errors .= " - Error when running perl tests.\n";
	$errors .= nl2br($buf);
}

// test memcache
if (!class_exists("Memcache")) {
	$errors .= " - Missing \"Memcache\" php class. Please install your distribution's package of http://pecl.php.net/package/memcache\n";
}

// test qrencode
$expected = base64_decode("iVBORw0KGgoAAAANSUhEUgAAAB0AAAAdAQAAAAB+6FqiAAAAbklEQVQImWP4DwQMaMQHWYd6hu/34+sZvoReBBLxgUAiCkh8v3G/nuGDKFD2/1eguo+ssv8ZftWsq2f4e6+jnuGrkhqQe60LKPvxNkhdEVDH5Xv/Gb4EBwENiFkHZAX1AsWuKAHtEOqpR7cXRAAANwpWESFdK+4AAAAASUVORK5CYII=");
ob_start();
passthru("/usr/bin/qrencode -s 1 -o - \"test\"");
$buf = ob_get_contents();
ob_end_clean();
if ($buf != $expected) {
	$errors .= " - Error when testing qrencode: Didn't get expected output when encoding string \"test\".\n";
}


if ($errors != "") {
	echo nl2br("\n\n");
	echo nl2br("Errors occured:\n");
	echo nl2br($errors);
} else {
// TODO: Make this an actual installer
	echo nl2br("Tests completed.\n"
		."The following steps remain:\n"
		." - copy the files from ./application/config/example/ to ./application/config/ and edit them to suit your setup\n"
		." - import ./db.sql into your database\n"
	);
}
