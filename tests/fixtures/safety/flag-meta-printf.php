<?php
// expect: SEO-META-UNESCAPED
printf('<meta name="description" content="%s" />', get_the_excerpt());
