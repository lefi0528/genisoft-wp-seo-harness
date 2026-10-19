<?php
// expect: none
add_filter('wp_robots', fn(array $robots): array => array_merge($robots, ['noindex' => is_404()]));

add_filter('wp_robots', function (array $robots): array {
    $robots['noindex'] = is_search() ? true : false;
    return $robots;
});
