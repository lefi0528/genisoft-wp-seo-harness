<?php
// expect: SEO-NOINDEX
// A site-wide option is not a URL selector: when it matches, every URL is de-indexed.
add_filter('wp_robots', function (array $robots): array {
    if (in_array(get_option('theme_mode'), ['private', 'beta'], true)) {
        $robots['noindex'] = true;
    }
    return $robots;
});
