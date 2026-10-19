<?php
// expect: none
add_filter('wp_robots', function (array $robots): array {
    if (is_singular('landing') && !is_paged()) {
        $robots['noindex'] = true;
    }
    if (is_attachment()) {
        $robots['noindex'] = true;
    }
    if (get_the_ID() === 42) {
        $robots['noindex'] = true;
    }
    return $robots;
});
