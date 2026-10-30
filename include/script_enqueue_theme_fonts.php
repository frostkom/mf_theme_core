<?php

if(!defined('ABSPATH'))
{
	header("Content-Type: text/javascript; charset=utf-8");

	$folder = str_replace("/wp-content/plugins/mf_theme_core/include", "/", dirname(__FILE__));

	require_once($folder."wp-load.php");
}

echo "if(document.cookie.split('; ').indexOf('cookie_accepted=true') !== -1)
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
					echo "$('head').append(\"<link rel='stylesheet' href='".$obj_theme_core->options_fonts[$font]['url']."'>\");";
				}
			}
		}

	echo "});
}";