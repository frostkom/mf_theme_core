<?php

$wp_root = '../../../..';

if(file_exists($wp_root.'/wp-load.php'))
{
	require_once($wp_root.'/wp-load.php');
}

else
{
	require_once($wp_root.'/wp-config.php');
}

header("Content-type: text/plain; charset=".get_option('blog_charset'));

echo "User-agent: *

Sitemap: ".get_site_url()."/sitemap.xml";

//Disallow: /uploads/backups/