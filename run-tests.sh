#!/bin/bash
#
# This runs the testsuite. Arguments are passed to prove.
#

export ENVIRONMENT="testsuite"

startdir="$(dirname "$0")"
url=""
port=23115
ip='127.0.0.1'
url="http://$ip:$port/index.php"

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

php -S "$ip:$port" 2>/dev/null 1>&2 &

while ! curl -s "$url" >/dev/null; do
	sleep 0.1;
done

#  run tests
php index.php tools drop_all_tables || exit 1
php index.php tools update_database || exit 1
prove --ext .php --state=hot,slow,all,save --timer -o -e "php index.php tools test $url" -r "$@" application/test/tests/

