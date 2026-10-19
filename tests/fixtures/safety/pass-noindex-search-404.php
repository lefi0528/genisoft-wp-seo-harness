<?php
// expect: none
add_filter('wp_robots', function (array $robots): array {
    if (is_search() || is_404()) {
        $robots['noindex'] = true;
    } else {
        $robots['max-image-preview'] = 'large';
    }
    return $robots;
});
