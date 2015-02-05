#!/usr/bin/env php
<?php
    $lib = isset($_SERVER['TESTLIB']) ? $_SERVER['TESTLIB'] : 'Test-Simple.php';
    require_once($lib);
    plan(1);
    ok(1);
?>
