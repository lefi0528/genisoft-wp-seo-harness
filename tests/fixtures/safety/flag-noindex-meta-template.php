<?php // expect: SEO-NOINDEX ?>
<!doctype html>
<html>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="robots" content="noindex,nofollow">
    <?php wp_head(); ?>
</head>
