#!/bin/env perl
    use strict;
    use warnings;
    use Test::More ('no_plan');

    diag('Test of deprecated Test::More functions provided for compatibility completeness.');

    my $foo = [1,'B','third'];
    my $oof = ['third','B',1];
    my $foo_h = {0=>1,1=>'B',2=>'third'};
    my $oof_h = {0=>'third',1=>'B',2=>1};

    my $bar = [23,42,];
    my $rab = [42,23,];
    my $bar_h = {'q'=>23,'Y'=>42,};
    my $rab_h = {'Y'=>42,'q'=>23,};

    ok(eq_array($foo,$oof),'eq_array() with misordered array is ok');
    ok(eq_array($bar,$rab),'eq_array() with misordered assoc is ok');
    ok(eq_hash($foo_h,$oof_h),'eq_hash() with misordered array is ok');
    ok(eq_hash($bar_h,$rab_h),'eq_hash() with misordered assoc is ok');
    ok(eq_set($foo,$oof),'eq_set() with misordered array is ok');
    ok(eq_set($bar,$rab),'eq_set() with misordered assoc is ok');

    done_testing();
