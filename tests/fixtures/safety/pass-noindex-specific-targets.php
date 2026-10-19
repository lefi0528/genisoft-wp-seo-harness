<?php
// expect: none
add_filter('wp_robots', function (array $robots): array {
    if (is_singular('landing') && !is_paged()) {
        $robots['noindex'] = true;
    }
    if (is_attachment()) {
        $robots['noindex'] = true;
    }
    if (get_the_ID() === 42 || get_queried_object_id() === THANK_YOU_PAGE_ID) {
        $robots['noindex'] = true;
    }
    if (get_post_field('post_name') === 'thank-you' || get_queried_object()->term_id == 7) {
        $robots['noindex'] = true;
    }
    if (in_array(get_the_ID(), [12, 34], true)) {
        $robots['noindex'] = true;
    }
    return $robots;
});
