<?php

if(!defined('ABSPATH'))
{
	$folder = str_replace("/wp-content/plugins/mf_theme_core/include", "/", dirname(__FILE__));

	require_once($folder."wp-load.php");
}

$obj_theme_core = new mf_theme_core();

$site_name = get_bloginfo('name');
$site_description = get_bloginfo('description');

header("Content-type: text/xsl; charset=".get_option('blog_charset'));

echo "<?xml version='1.0' encoding='UTF-8'?>
<xsl:stylesheet version='1.0' xmlns:html='http://www.w3.org/TR/REC-html40' xmlns:sitemap='http://www.sitemaps.org/schemas/sitemap/0.9' xmlns:xsl='http://www.w3.org/1999/XSL/Transform'>
	<xsl:output method='html' version='1.0' encoding='UTF-8' indent='yes'/>
	<xsl:template match='/'>
		<html xmlns='http://www.w3.org/1999/xhtml'>
			<head>
				<title>".sprintf(__("XML Sitemap for %s", $obj_theme_core->lang_key), $site_name.($site_description != '' ? " | ".$site_description : ''))."</title>
				<meta http-equiv='Content-Type' content='text/html; charset=utf-8' />
				<meta name='robots' content='noindex,follow' />
				<style>
					body
					{
						font-family: 'Lucida Grande', 'Lucida Sans Unicode', Tahoma, Verdana;
						font-size: .625em;
					}

					a
					{
						color: #000;
					}

					table
					{
						border-spacing: 0;
					}

						th
						{
							text-align: left;
							padding: .5em 2em .5em .5em;
							font-size: 1.1em;
						}

						tr:nth-child(2n+1)
						{
							background: #eee;
						}

							td
							{
								font-size: 1.1em;
								padding: .5em;
							}
				</style>
			</head>
			<body>
				<xsl:apply-templates/>
			</body>
		</html>
	</xsl:template>

	<xsl:template match='sitemap:urlset'>
		<h1>".sprintf(__("XML Sitemap for %s | %s", $obj_theme_core->lang_key), $site_name, $site_description)."</h1>
		<table>
			<tr>
				<th>".__("Title", $obj_theme_core->lang_key)."</th>
				<th>".__("URL", $obj_theme_core->lang_key)."</th>
				<th>".__("Last Modified", $obj_theme_core->lang_key)." (GMT)</th>
			</tr>
			<xsl:for-each select='./sitemap:url'>
				<tr>
					<td>
						<xsl:value-of select='sitemap:title'/>
					</td>
					<td>
						<xsl:variable name='itemURL'>
							<xsl:value-of select='sitemap:loc'/>
						</xsl:variable>
						<a href='{\$itemURL}'>
							<xsl:value-of select='sitemap:loc'/>
						</a>
					</td>
					<td>
						<xsl:value-of select='sitemap:lastmod'/>
					</td>
				</tr>
			</xsl:for-each>
		</table>
	</xsl:template>
</xsl:stylesheet>";