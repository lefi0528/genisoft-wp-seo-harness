<?php
// expect: SEO-META-UNESCAPED
$description = get_post_meta(get_the_ID(), '_seo_description', true);
echo "<meta name=\"description\" content=\"$description\" />";
