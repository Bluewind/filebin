#!/bin/bash
#
# This runs the testsuite. Arguments are passed to prove.
#

export ENVIRONMENT="testsuite"

startdir="$(dirname "$0")"

die() {
	echo "$@" >&2
	echo "Aborting..." >&2
	exit 1
}


cd "$startdir"

# some sanity checks
test -d system || die 'Required dir not found.'
test -d application || die 'Required dir not found.'
test -f run-tests.sh || die 'Required file not found.'
grep -qF 'getenv("ENVIRONMENT")' application/config/database.php || die "database config doesn't honor ENVIRONMENT."

# prepare
trap cleanup EXIT INT
cleanup() {
	pkill -P $$
	php index.php tools drop_all_tables
}

mkdir -p test-coverage-data

#  run tests
phpdbg -qrr index.php tools drop_all_tables || exit 1
phpdbg -qrr index.php tools update_database || exit 1

prove --ext .php --state=failed,save --timer --comments --exec 'phpdbg -qrr index.php tools test' --recurse "${@:-application/test/tests/}" || exit 1

php index.php tools generate_coverage_report
rm -rf test-coverage-data

