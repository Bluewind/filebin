<?php
$urls = array_map(function($a) {return $a."?cli_deprecated";}, $urls);
echo implode(" ", $urls)."\n";

