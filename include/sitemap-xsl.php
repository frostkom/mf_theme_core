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

$site_name = get_bloginfo('name');
$site_description = get_bloginfo('description');

header("Content-type: text/xsl; charset=".get_option('blog_charset'));

echo "<?xml version='1.0' encoding='UTF-8'?>
<xsl:stylesheet version='1.0' xmlns:html='http://www.w3.org/TR/REC-html40' xmlns:sitemap='http://www.sitemaps.org/schemas/sitemap/0.9' xmlns:xsl='http://www.w3.org/1999/XSL/Transform'>
	<xsl:output method='html' version='1.0' encoding='UTF-8' indent='yes'/>
	<xsl:template match='/'>
		<html xmlns='http://www.w3.org/1999/xhtml'>
			<head>
				<title>".sprintf(__("XML Sitemap for %s | %s", 'lang_theme_core'), $site_name, $site_description)."</title>
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
					}";
					
					/*echo "#intro {
						background-color:#CFEBF7;
						border:1px #2580B2 solid;
						padding:5px 13px 5px 13px;
						margin:10px;
					}
					
					#intro p {
						line-height:	16.8667px;
					}
					#intro strong {
						font-weight:normal;
					}";*/
					
					echo "table
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
							}";
					
					/*echo "#footer {
						padding:2px;
						margin-top:10px;
						font-size:8pt;
						color:gray;
					}
					
					#footer a {
						color:gray;
					}";*/
					
				echo "</style>
			</head>
			<body>
				<xsl:apply-templates/>";

				/*echo "<div id='footer'></div>";*/

			echo "</body>
		</html>
	</xsl:template>
	
	
	<xsl:template match='sitemap:urlset'>
        <h1>".sprintf(__("XML Sitemap for %s | %s", 'lang_theme_core'), $site_name, $site_description)."</h1>";

        /*echo "<div id='intro'><p></p></div>";*/

		echo "<table>
			<tr>
				<th>".__("URL", 'lang_theme_core')."</th>";

				/*echo "<th>Priority</th>
				<th>Change frequency</th>";*/

				echo "<th>".__("Last Modified", 'lang_theme_core')." (GMT)</th>
			</tr>
			<xsl:variable name='lower' select=\"'abcdefghijklmnopqrstuvwxyz'\"/>
			<xsl:variable name='upper' select=\"'ABCDEFGHIJKLMNOPQRSTUVWXYZ'\"/>
			<xsl:for-each select='./sitemap:url'>
				<tr>
					<td>
						<xsl:variable name='itemURL'>
							<xsl:value-of select='sitemap:loc'/>
						</xsl:variable>
						<a href='{\$itemURL}'>
							<xsl:value-of select='sitemap:loc'/>
						</a>
					</td>";

					/*echo "<td>
						<xsl:value-of select=\"concat(sitemap:priority*100,'%')\"/>
					</td>
					<td>
						<xsl:value-of select='concat(translate(substring(sitemap:changefreq, 1, 1),concat(\$lower, \$upper),concat(\$upper, \$lower)),substring(sitemap:changefreq, 2))'/>
					</td>";*/

					echo "<td>
						<xsl:value-of select=\"concat(substring(sitemap:lastmod,0,11),concat(' ', substring(sitemap:lastmod,12,5)))\"/>
					</td>
				</tr>
			</xsl:for-each>
		</table>
	</xsl:template>
</xsl:stylesheet>";