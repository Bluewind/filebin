<?php



    $lib = isset($_SERVER['TESTLIB']) ? $_SERVER['TESTLIB'] : 'Test-Simple.php';
    require_once($lib);

    print "# OK tests\n";
    print "# (No message for next test)\n";
    ok(1);
    ok(1,"1 is ok");
    ok(TRUE,"TRUE is ok");
    ok('string',"'string' is ok");

    print "# Not OK tests\n";
    print "# (No message for next test)\n";
    ok(0);
    ok(0,"0 is not ok");
    ok(FALSE,"FALSE is not ok");
    ok('',"'' is not ok");
    ok( NULL,"NULL is not ok");

    done_testing();
?>
