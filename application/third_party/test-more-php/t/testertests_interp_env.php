<?php

    $lib = 'Test-More.php';
    require_once($lib);
    $t = new TestMore();
    $t->plan(1);

    if (strpos(strtoupper($_SERVER['OS']),'WINDOWS') !== FALSE) {
        // Should also accept extension
        $newinterp = 'php.exe';
    } else {
        // Fair guess
        $newinterp = '/usr/local/bin/php';
    }

    $_SERVER['PHP'] = $newinterp;
    $t->is( $t->interp(),$newinterp,"set valid alternate interp via PHP environment variable ($newinterp)");

?>
