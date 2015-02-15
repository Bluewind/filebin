<?php
    $lib = isset($_SERVER['TESTLIB']) ? $_SERVER['TESTLIB'] : 'Test-Simple.php';
    require_once($lib);
    plan('no_plan');

    diag('Test of deprecated Test::More functions provided for compatibility completeness.');

    $foo = array(0=>1,1=>'B',2=>'third');
    $oof = array(0=>'third',1=>'B',2=>1);



    $bar = array('q'=>23,'Y'=>42,);
    $rab = array('Y'=>42,'q'=>23,);



    ok(eq_array($foo,$oof),'eq_array() with misordered array is ok');
    ok(eq_array($bar,$rab),'eq_array() with misordered assoc is ok');
    ok(eq_hash($foo,$oof),'eq_hash() with misordered array is ok');
    ok(eq_hash($bar,$rab),'eq_hash() with misordered assoc is ok');
    ok(eq_set($foo,$oof),'eq_set() with misordered array is ok');
    ok(eq_set($bar,$rab),'eq_set() with misordered assoc is ok');

    done_testing();

?>
