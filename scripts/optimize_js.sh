#!/bin/bash

script_dir=$(dirname "$0")
js_dir="$script_dir/../data/js"
outfile="$js_dir/main.min.js"
node "$script_dir/r.js" -o mainConfigFile="$js_dir/main.js" name=main out=$outfile
