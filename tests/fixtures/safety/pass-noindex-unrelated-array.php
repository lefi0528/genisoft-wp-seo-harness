<?php
// expect: none
add_filter('wp_robots', 'wp_robots_max_image_preview_large');

// Unrelated option array of another component: not a robots directive.
$theme_defaults = ['noindex' => true, 'columns' => 3];
