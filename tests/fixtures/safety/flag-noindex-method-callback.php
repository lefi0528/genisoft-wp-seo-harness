<?php
// expect: SEO-NOINDEX
class Theme_Robots {
    public function __construct() {
        add_filter('wp_robots', [$this, 'robots']);
    }

    public function robots(array $robots): array {
        return array_merge($robots, ['noindex' => true, 'nofollow' => true]);
    }
}
