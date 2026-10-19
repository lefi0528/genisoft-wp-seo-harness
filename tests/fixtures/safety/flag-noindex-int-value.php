<?php
// expect: SEO-NOINDEX
// A truthy value other than true still de-indexes every URL.
add_filter('wp_robots', function (array $robots): array {
    $robots['noindex'] = 1;
    return $robots;
});
