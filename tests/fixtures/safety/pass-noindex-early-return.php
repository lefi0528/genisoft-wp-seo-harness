<?php
// expect: none
add_filter('wp_robots', 'theme_search_robots');

function theme_search_robots(array $robots): array {
    if (!is_search()) {
        return $robots;
    }
    $robots['noindex'] = true;
    return $robots;
}
