<?php
// expect: none
// Explicit, reviewable opt-out: reported as a warning, not a violation.
add_filter('wp_robots', function (array $robots): array {
    // seo-harness: allow-noindex members-only intranet, never public
    $robots['noindex'] = true;
    return $robots;
});
