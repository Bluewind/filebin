<?php

    $lib = isset($_SERVER['TESTLIB']) ? $_SERVER['TESTLIB'] : 'Test-Simple.php';
    require_once($lib);

    plan('xxx');

    ok(1);
?>
