<?php

if (version_compare(PHP_VERSION, '5.3.0') < 0) {
	echo "Just a heads up: Filebin has not been tested with php older than 5.3. You might run into problems.";
}

$errors = "";

define('SELF', pathinfo(__FILE__, PATHINFO_BASENAME));
define('FCPATH', str_replace(SELF, "", __FILE__));

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

// test perl HTML::FromANSI
ob_start();
passthru("perl ".FCPATH."/scripts/install_helper.pl");
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
ob_start();
passthru("qrencode -V 2>&1", $buf);
ob_end_clean();
if ($buf != "0") {
	$errors .= " - Error when testing qrencode: Return code was \"$buf\".\n";
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
		." - the database will be set up automatically\n"
	);
}
