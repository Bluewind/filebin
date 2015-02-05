<?php



    $lib = isset($_SERVER['TESTLIB']) ? $_SERVER['TESTLIB'] : 'Test-Simple.php';
    require_once($lib);
    plan(2);

    require_ok('borklib.php','Requiring a file with non-syntactical errors should be not ok');

    ok(1, 'Continue testing after failed require');
?>
