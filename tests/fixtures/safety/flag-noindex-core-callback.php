<?php
// expect: SEO-NOINDEX
// The core helper adds noindex + nofollow on every URL when registered unconditionally.
add_filter('wp_robots', 'wp_robots_no_robots');
