<?php
// expect: none
// Dangerous code inside comments and string fixtures is never executed:
// add_filter('wp_robots', function ($r) { $r['noindex'] = true; return $r; });
/* <link rel="canonical" href="http://example.com/" /> */
$lesson = "add_filter('wp_robots', function (\$r) { \$r['noindex'] = true; return \$r; });";
$legacy_pattern = '#<link[^>]+rel=canonical#i';
