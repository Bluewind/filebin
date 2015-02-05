#!/bin/env perl
    use strict;
    use warnings;

    my $lib = defined($ENV{'TESTLIB'}) ? $ENV{'TESTLIB'} : 'Test::Simple';
    eval "use $lib;";

    print "# OK\n";
    print "# (No message for next test)\n";
    ok(1);
    ok(1,"1 is ok");
    ok(  !0,"TRUE is ok");
    ok('string',"'string' is ok");

    print "# Not OK\n";
    print "# (No message for next test)\n";
    ok(0);
    ok(0,"0 is not ok");
    ok(   !1,"FALSE is not ok");
    ok('',"'' is not ok");
    ok(undef,"undef is not ok");

    done_testing();

