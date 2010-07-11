<?php

// Source: http://ibnuyahya.com/format-bytes-to-kb-mb-gb/
function format_bytes($size, $precision = 2){
    $base = log($size) / log(1024);
    $suffixes = array('B', 'kB', 'MB', 'GB', 'TB' , 'PB' , 'EB');
    return round(pow(1024, $base - floor($base)), $precision) . $suffixes[floor($base)];
}

