<?php
// expect: SEO-NOINDEX
add_action('send_headers', function (): void {
    header('X-Robots-Tag: noindex, nofollow');
});
