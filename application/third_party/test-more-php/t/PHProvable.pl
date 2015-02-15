#!/bin/env perl

# PHProveable.pl
#
# A wrapper/dummy for 
#
# This script allows you to use the prove program with PHP test scripts
# that output TAP, such as those written with Test-Simple or Test-More,
# without requiring that the php test script be writen with a UNIX style
# shebang line pointing to the processor:
# 
# #!/bin/env php
#
# USAGE:
#   Your PHP test script should be named like this: TESTSCRIPTNAME.t.php.
#   You can either copy this file and name it TESTSCRIPTNAME.t or call it
#   explicitly as the first and only argument:
#       PHProvable.pl TESTSCRIPTNAME.t.php
#   The first method means you end up with a stub for each PHP script,
#   although on a system with symlinks you can use a symlink instead of
#   copying PHProveable:
#       ln -s PHPRoveable.pl TESTSCRIPTNAME.t
#   The stub method allows you to just run `prove` in a directory and have
#   it look for a /t directory, then find your *.t stubs and run them as
#   usual.
#
# NOTES:
#   Yeah, there are many ways to skin a cat. You could just leave the .php
#   off your test script and add the shebang line, but then you can't just
#   run the script via CGI without the shebang showing up as extra content,
#   and it won't work on windows via the CLI.

my $script = $ARGV[0] ? $ARGV[0] : "$0.php";
my $php_interp = $ENV{'PHP'} ? $ENV{'PHP'} : 'php';
exec("$php_interp $script");
