<?php
// expect: none
add_action('template_redirect', function (): void {
    if (is_search()) {
        add_filter('wp_robots', 'wp_robots_no_robots');
    }
});
