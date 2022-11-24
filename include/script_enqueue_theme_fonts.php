<?php

if(!defined('ABSPATH'))
{
	header("Content-Type: text/javascript; charset=utf-8");

	$folder = str_replace("/wp-content/plugins/mf_theme_core/include", "/", dirname(__FILE__));

	require_once($folder."wp-load.php");
}

echo "if(document.cookie.indexOf('cookie_accepted=') !== -1)
{
	jQuery(function($)
	{";

		$obj_theme_core = new mf_theme_core();

		$obj_theme_core->get_theme_fonts();
		$obj_theme_core->get_params();

		foreach($obj_theme_core->options_params as $arr_param)
		{
			if(isset($arr_param['type']) && $arr_param['type'] == 'font' && isset($obj_theme_core->options[$arr_param['id']]))
			{
				$font = $obj_theme_core->options[$arr_param['id']];

				if(isset($obj_theme_core->options_fonts[$font]['url']) && $obj_theme_core->options_fonts[$font]['url'] != '')
				{
					/*echo "var style_tag = document.createElement('link');
					style_tag.rel = 'stylesheet';
					style_tag.id = 'style_font_".$font."-css';
					style_tag.href = '".$obj_theme_core->options_fonts[$font]['url']."';
					style_tag.type = 'text/css';
					style_tag.media = 'all';
					
					var first_sibling_tag = document.getElementsByTagName('link')[0];
					first_sibling_tag.parentNode.insertBefore(style_tag, first_sibling_tag);";*/

					echo "$('head').append(\"<link rel='stylesheet' href='".$obj_theme_core->options_fonts[$font]['url']."'>\");";
				}
			}
		}

	echo "});
}";