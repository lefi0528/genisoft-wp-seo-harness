<?php
// expect: SEO-NOINDEX
// is_404() elsewhere in the file must not hide an unconditional noindex.
add_filter('wp_robots', function (array $robots): array {
    $robots['noindex'] = true;
    return $robots;
});

function theme_is_missing_page(): bool {
    return is_404();
}
