<?php

header("HTTP/1.1 503 Service Temporarily Unavailable");
header("Status: 503 Service Temporarily Unavailable");
header("Retry-After: 60");

DEFINE('CURRENT_URL', $_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']);

/* Loop Template */
/*#########################
if(preg_match("/[site_url]/i", CURRENT_URL))
{
	$file_path = realpath("[post_dir]");

	if(file_exists($file_path))
	{
		readfile($file_path);
	}

	else
	{
		echo "<h1>[post_title]</h1>
		[post_content]";
	}

	exit;
}
#########################*/