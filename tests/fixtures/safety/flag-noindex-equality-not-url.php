<?php
// expect: SEO-NOINDEX
// An equality that does not select a URL is true on every front-end page.
add_filter('wp_robots', function (array $robots): array {
    $robots['noindex'] = is_admin() === false;
    return $robots;
});
