<?php

if(!defined('ABSPATH'))
{
	header('Content-Type: application/json');

	$folder = str_replace("/wp-content/plugins/mf_theme_core/include/api", "/", dirname(__FILE__));

	require_once($folder."wp-load.php");
}

do_action('run_cache', array('suffix' => 'json'));

$json_output = array();

$type = check_var('type', 'char');

switch($type)
{
	case 'get_style_source':
		$arr_theme_data = wp_get_theme();

		if($arr_theme_data->exists())
		{
			$obj_theme_core = new mf_theme_core();

			$theme_dir_name = $obj_theme_core->get_theme_dir_name();
			$child_dir_name = $obj_theme_core->get_theme_dir_name(array('type' => 'child'));

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
				//'theme_name' => $theme_dir_name, // Parent Theme
				'theme_name' => $child_dir_name,
				//'theme_version' => $arr_theme_data->get('Version'), //Deprecated
				'style_changed' => $style_changed,
				'style_url' => $style_url,
			);
		}
	break;
}

echo json_encode($json_output);