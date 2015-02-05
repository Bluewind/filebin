<?php

    $lib = isset($_SERVER['TESTLIB']) ? $_SERVER['TESTLIB'] : 'Test-Simple.php';
    require_once($lib);

    plan(7);
    $failures = 5;

    ok(1);
    for ($x=0;$x<$failures;$x++){
        ok(0);
    }
    ok(1);
?>
