<?php

if(!defined('ABSPATH'))
{
	header("Content-Type: text/css; charset=utf-8");

	$folder = str_replace("/wp-content/plugins/mf_theme_core/include", "/", dirname(__FILE__));

	require_once($folder."wp-load.php");
}

$obj_theme_core = new mf_theme_core();

$obj_theme_core->get_theme_fonts();

foreach($obj_theme_core->options_fonts as $font_key => $arr_fonts)
{
	if(isset($arr_fonts['file']) && isset($arr_fonts['extensions']))
	{
		$font_file = $arr_fonts['file'];

		$font_src = "";

		foreach($arr_fonts['extensions'] as $font_extension)
		{
			$font_src .= ($font_src != '' ? "," : "");

			switch($font_extension)
			{
				case 'eot':		$font_src .= "url('".$font_file.".eot?#iefix') format('embedded-opentype')";	break;
				case 'otf':		$font_src .= "url('".$font_file.".otf') format('opentype')";					break;
				case 'woff':	$font_src .= "url('".$font_file.".woff') format('woff')";						break;
				case 'ttf':		$font_src .= "url('".$font_file.".ttf') format('truetype')";					break;
				case 'svg':		$font_src .= "url('".$font_file.".svg#".$font_key."') format('svg')";			break;
			}
		}

		if($font_src != '')
		{
			echo "@font-face
			{
				font-family: '".$arr_fonts['title']."';
				src: ".$font_src.";
				font-weight: normal;
				font-style: normal;
			}";
		}

		else
		{
			//echo "/* ".$font_key." -> ".var_export($arr_fonts, true)." */";
		}
	}
}

echo "#customize-info, #accordion-section-themes, #accordion-panel-nav_menus
{
	display: none !important;
}";

foreach($obj_theme_core->options_fonts as $font_key => $arr_fonts)
{
	echo "option[value='".$font_key."']
	{
		font-family: ".$arr_fonts['style'].";
	}";
}