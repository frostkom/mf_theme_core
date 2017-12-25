<?php

header("HTTP/1.1 503 Service Temporarily Unavailable");
header("Status: 503 Service Temporarily Unavailable");
header("Retry-After: 60");

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