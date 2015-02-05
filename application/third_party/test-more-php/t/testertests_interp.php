<?php

    $lib = 'Test-More.php';
    require_once($lib);
    $t = new TestMore();
    $t->plan(1);

    $t->is( $t->interp(),'php',"interp defaults to php");

?>
