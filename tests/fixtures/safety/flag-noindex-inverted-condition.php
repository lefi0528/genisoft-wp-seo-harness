<?php
// expect: SEO-NOINDEX
// Inverted condition: de-indexes the homepage, archives and pages (project-01 bug).
add_filter('wp_robots', function ($robots) {
    if (!is_single()) {
        $robots['noindex'] = true;
    }
    return $robots;
});
