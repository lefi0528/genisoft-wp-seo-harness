<?php // expect: none ?>
<meta charset="<?php bloginfo('charset'); ?>">
<meta name="description" content="<?php echo esc_attr(get_the_excerpt()); ?>">
<meta property="og:image" content="<?= esc_url(get_the_post_thumbnail_url()) ?>">
<link rel="canonical" href="https://example.com/mypage/" />
<?php
echo '<link rel="canonical" href="' . esc_url(wp_get_canonical_url()) . '" />';
echo '<meta property="og:title" content="' . esc_attr(get_the_title()) . '" />';
printf('<meta name="description" content="%s" />', esc_attr(get_the_excerpt()));
printf('<meta property="og:image:width" content="%d" />', $width);
