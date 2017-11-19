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

header("Content-type: text/xml; charset=".get_option('blog_charset'));

echo "<?xml version='1.0' encoding='UTF-8'?>
<?xml-stylesheet type='text/xsl' href='".get_site_url()."/wp-content/plugins/mf_theme_core/include/sitemap-xsl.php'?>
<urlset xmlns='http://www.sitemaps.org/schemas/sitemap/0.9'>";

	$meta_prefix = "mf_theme_core_";

	$arr_data = array();
	get_post_children(array(), $arr_data);

	foreach($arr_data as $post_id => $post_title)
	{
		$page_index = get_post_meta($post_id, $meta_prefix.'page_index', true);

		if($page_index != '' && in_array($page_index, array('noindex', 'none'))){}
		else if(post_password_required($post_id)){}

		else
		{
			$post_modified = get_post_modified_time("Y-m-d H:i:s", true, $post_id);

			$post_url = get_permalink($post_id);

			echo "<url>
				<loc>".$post_url."</loc>
				<title>".$post_title."</title>
				<lastmod>".$post_modified."</lastmod>
			</url>";

			/*<changefreq>monthly</changefreq>
			<priority>0.8</priority>*/
		}
	}

echo "</urlset>";