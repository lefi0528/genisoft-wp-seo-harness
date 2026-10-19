<?php
// expect: SEO-NOINDEX
// One broad branch (is_front_page) is enough to make the whole condition dangerous.
add_filter('wp_robots', function (array $robots): array {
    if (is_search() || is_front_page()) {
        $robots['noindex'] = true;
    }
    return $robots;
});
