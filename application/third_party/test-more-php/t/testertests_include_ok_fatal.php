<?php



    $lib = isset($_SERVER['TESTLIB']) ? $_SERVER['TESTLIB'] : 'Test-Simple.php';
    require_once($lib);
    plan(3);

    diag('If PHP throws a fatal error, bail as nicely as possible.');

    ok(1, "Pass one for good measure");

    include_ok($lib,'Including a library again should redefine a function and bail.');

    ok(1, 'This test will not be reached.');

?>
