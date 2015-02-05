#!/bin/env perl
    use strict;
    use warnings;
    use Test::More;
    plan(tests=>2);

    skip("Test: Skip one",1);
    fail("Gets skipped");
    pass("Gets run ok");
