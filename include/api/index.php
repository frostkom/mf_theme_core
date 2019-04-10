<?php

if(!defined('ABSPATH'))
{
	header('Content-Type: application/json');

	$folder = str_replace("/wp-content/plugins/mf_theme_core/include/api", "/", dirname(__FILE__));

	require_once($folder."wp-load.php");
}

if(function_exists('is_plugin_active') && is_plugin_active('mf_cache/index.php'))
{
	$obj_cache = new mf_cache();
	$obj_cache->fetch_request();
	$obj_cache->get_or_set_file_content('json');
}

$json_output = array();

$type = check_var('type', 'char');

switch($type)
{
	case 'get_style_source':
		$theme = wp_get_theme();

		if($theme->exists())
		{
			$obj_theme_core = new mf_theme_core();

			$theme_dir_name = $obj_theme_core->get_theme_dir_name();

			list($upload_path, $upload_url) = get_uploads_folder($theme_dir_name);

			$arr_backups = $obj_theme_core->get_previous_backups_list($upload_path);
			$count_temp = count($arr_backups);

			if($count_temp > 0)
			{
				$style_url = $upload_url.$arr_backups[0]['name'];

				$style_path = str_replace($upload_url, $upload_path, $style_url);
				$style_changed = date("Y-m-d H:i:s", filemtime($style_path));
			}

			else
			{
				$style_url = "";
				$style_changed = DEFAULT_DATE;
			}

			$json_output['success'] = ($style_changed >= DEFAULT_DATE);
			$json_output['response'] = array(
				'theme_version' => $theme->get('Version'), //Deprecated
				'style_changed' => $style_changed,
				'style_url' => $style_url,
			);
		}
	break;
}

echo json_encode($json_output);