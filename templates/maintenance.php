<?php

/*header("HTTP/1.1 503 Service Temporarily Unavailable");
header("Status: 503 Service Temporarily Unavailable");
header("Retry-After: 60");*/

DEFINE('CURRENT_URL', strtolower("//".$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI']));

/* Loop Template */
/*#########################
if("[site_url]" == substr(CURRENT_URL, 0, strlen("[site_url]")))
{
	$file_path = realpath("[post_dir]");

	if(file_exists($file_path))
	{
		readfile($file_path);
	}

	else
	{
		<article class='post_type_page'>
			<section>
				<h1>[post_title]</h1>
				[post_content]
			</section>
		</article>
	}

	exit;
}
#########################*/