<?php

if(!defined('ABSPATH'))
{
	header("Content-Type: text/javascript; charset=utf-8");

	$folder = str_replace("/wp-content/plugins/mf_theme_core/include", "/", dirname(__FILE__));

	require_once($folder."wp-load.php");
}

echo "if(document.cookie.indexOf('cookie_accepted=') !== -1)
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
				echo "var no = document.createElement('link');
				no.rel = 'stylesheet';
				no.id = 'style_font_".$font."-css';
				no.href = '".$obj_theme_core->options_fonts[$font]['url']."';
				no.type = 'text/css';
				no.media = 'all';
				var s = document.getElementsByTagName('link')[0]; s.parentNode.insertBefore(no, s);";
			}
		}
	}

echo "}";