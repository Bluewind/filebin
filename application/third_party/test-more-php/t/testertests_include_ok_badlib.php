<?php



    $lib = isset($_SERVER['TESTLIB']) ? $_SERVER['TESTLIB'] : 'Test-Simple.php';
    require_once($lib);
    plan(5);

    diag('Should fail 3 of 5 tests');

    ok(1, "Pass one");

    include_ok('missing.php','Including a missing file should be not ok');

    include_ok('badlib.php','Including a file with bad syntax should be not ok');

    include_ok('borklib.php','Including a file with non-syntactical errors should be not ok');

    ok(1, 'Continue testing after failed include');

?>
