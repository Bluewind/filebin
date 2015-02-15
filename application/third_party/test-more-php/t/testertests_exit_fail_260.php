<?php

    $lib = isset($_SERVER['TESTLIB']) ? $_SERVER['TESTLIB'] : 'Test-Simple.php';
    require_once($lib);

    plan(262);
    $failures = 260;

    ok(1);
    for ($x=1;$x<$failures;$x++){
        ok(0);
    }
    ok(1);
?>
