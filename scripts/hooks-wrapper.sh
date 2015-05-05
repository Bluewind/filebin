#!/bin/bash
if [ -x $0.local ]; then
    $0.local "$@" || exit $?
fi

basename=$(basename "$0")
hooks_dir="$GIT_DIR/../git-hooks"
hook="$hooks_dir/$basename"

if [ -x "$hook" ]; then
    "$hook" "$@" || exit $?
fi
