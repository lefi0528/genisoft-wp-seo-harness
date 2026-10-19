<?php
// expect: SEO-NOINDEX
add_filter('wp_robots', 'theme_robots');

function theme_robots(array $robots): array {
    $robots['noindex'] = true;
    return $robots;
}
