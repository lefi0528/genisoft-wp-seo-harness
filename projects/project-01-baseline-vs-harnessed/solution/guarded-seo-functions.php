<?php
// Guarded solution compliant with Genisoft WP SEO Harness

// SAFE 1: Targeted noindex only on search and 404
add_filter('wp_robots', function(array $robots): array {
    if (is_search() || is_404()) {
        $robots['noindex'] = true;
    } else {
        $robots['max-image-preview'] = 'large';
        $robots['max-snippet'] = -1;
    }
    return $robots;
});

// SAFE 2: Strict HTTPS canonical link
echo '<link rel="canonical" href="https://example.com/mypage/" />';

// SAFE 3: Sanitized and escaped dynamic meta
$safeDesc = isset($_GET['search']) ? sanitize_text_field(wp_unslash($_GET['search'])) : '';
echo '<meta name="description" content="' . esc_attr($safeDesc) . '" />';
