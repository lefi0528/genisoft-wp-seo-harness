<?php
// expect: SEO-CANONICAL-RELATIVE
add_action('wp_head', function (): void {
    echo '<link rel="canonical" href="/mypage/" />';
});
