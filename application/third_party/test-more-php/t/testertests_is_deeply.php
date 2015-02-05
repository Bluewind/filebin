<?php



    $lib = isset($_SERVER['TESTLIB']) ? $_SERVER['TESTLIB'] : 'Test-More.php';
    require_once($lib);
    plan('no_plan');

    diag("Assertions:");

    is_deeply(NULL, NULL);
    is_deeply(TRUE, TRUE);
    is_deeply(FALSE, FALSE);
    is_deeply(42, 42);
    is_deeply('abcdef', 'abcdef');
    is_deeply(array(), array());
    is_deeply(array(1), array(1));
    is_deeply(array(array()), array(array()));
    is_deeply(array(array(123)), array(array(123)));
    is_deeply(array(1,'abc'), array(0=>1,1=>'abc'));

    diag("Denials:");

    isnt_deeply(NULL, TRUE,  'NULL !== TRUE');
    isnt_deeply(NULL, FALSE, 'NULL !== FALSE');
    isnt_deeply(NULL, 0,     'NULL !== 0');
    isnt_deeply(NULL, '',    "NULL !== ''");
    isnt_deeply(0, FALSE,     '0 !== FALSE');
    isnt_deeply(1, TRUE,     '1 !== TRUE');

?>
