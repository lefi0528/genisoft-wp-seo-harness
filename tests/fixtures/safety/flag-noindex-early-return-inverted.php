<?php
// expect: SEO-NOINDEX
// The early return keeps search pages indexable and de-indexes everything else.
add_filter('wp_robots', function (array $robots): array {
    if (is_search()) {
        return $robots;
    }
    $robots['noindex'] = true;
    return $robots;
});
