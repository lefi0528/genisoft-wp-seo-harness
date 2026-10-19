<?php
// expect: SEO-META-UNESCAPED
// project-01 bug: raw request data concatenated into a meta tag.
echo '<meta name="description" content="' . $_GET['search'] . '" />';
