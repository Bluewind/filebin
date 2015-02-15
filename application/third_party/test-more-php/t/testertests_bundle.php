<?php

    $lib = isset($_SERVER['TESTLIB']) ? $_SERVER['TESTLIB'] : 'Test-More.php';
    require_once($lib);
    #plan(3);

    diag('Test of various functions not otherwise broken out.');

    pass("pass() is ok");
    fail("fail() is not ok");

    is('Ab3','Ab3','is() is ok');
    isnt('Ab3',123,'isnt() is ok');
    like('yackowackodot','/wacko/',"like() is ok");
    unlike('yackowackodot','/boing/',"unlike() is ok");

    cmp_ok(12, '>', 10, 'cmp_ok() is ok');
    can_ok($__Test, 'plan' );
    isa_ok($__Test, 'TestMore', 'Default Testing object');
    include_ok('t/goodlib.php');
    require_ok('t/goodpage.php');

    $foo = array(1,'B','third');
    $oof = array('third','B',1);

    $bar = array('q'=>23,'Y'=>42,);
    $rab = array('Y'=>42,'q'=>23,);

    is_deeply($foo,$foo,'is_deeply() is ok');
    isnt_deeply($foo,$bar,'isnt_deeply() is ok');

    /*
    function skip($SkipReason, $num) {
    function todo ($why, $howmany) {
    function todo_skip ($why, $howmany) {
    function todo_start ($why) {
    function todo_end () {
    */

    diag("Should fail 1 test, testing fail()");
    done_testing();
?>
