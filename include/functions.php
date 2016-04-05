<?php

function mf_compress($data)
{
	$out = $data['content'];

	switch($data['type'])
	{
		case 'css':
			$exkludera = array('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '/(\n|\r|\t|\r\n|  |	)+/', '/(:|,) /', '/;}/');
			$inkludera = array('', '', '$1', '}');
			$out = preg_replace($exkludera, $inkludera, $out);
		break;
	}

	if($out == '')
	{
		$out = $data['content'];
	}

	return $out;
}

function enqueue_theme_fonts()
{
	$options_fonts = get_theme_fonts();

	$arr_fonts2insert = array();

	list($options_params, $options) = get_params();

	foreach($options_params as $param)
	{
		if(isset($param['type']) && $param['type'] == 'font' && isset($options[$param['id']]))
		{
			$font = $options[$param['id']];

			if(isset($options_fonts[$font]['url']) && $options_fonts[$font]['url'] != '')
			{
				wp_enqueue_style('style_font_'.$font, $options_fonts[$font]['url']);
			}
		}
	}
}

function replace_stylesheet_url($suffix = "css")
{
	global $wpdb;

	$style_base_url = get_bloginfo('stylesheet_directory');
	$style_base_dir = get_stylesheet_directory()."/include/";

	$arr_files = array(
		"style_".$wpdb->blogid.".".$suffix,
		"style.".$suffix
	);

	$style_file = "style.php";

	foreach($arr_files as $file)
	{
		if(file_exists($style_base_dir.$file))
		{
			$style_file = $file;

			break;
		}
	}

	return $style_base_url."/include/".$style_file;
}

function customize_preview_theme_core()
{
	wp_enqueue_script('script_theme_core_customizer', plugin_dir_url(__FILE__)."theme-customizer.js", array('jquery', 'customize-preview'));
}

function customize_save_theme_core()
{
	global $wpdb;

	$style_base_dir = get_stylesheet_directory()."/include/";
	$style_output_file = $style_base_dir."style".($wpdb->blogid > 0 ? "_".$wpdb->blogid : "").".css";

	$setting_save_style = get_option_or_default('setting_save_style');

	if($setting_save_style == 'yes')
	{
		$style_url = replace_stylesheet_url("php");

		/*$style_url = str_replace("https:", "http:", $style_url);
		$content = get_url_content($style_url);*/

		$response = wp_remote_get($style_url);
		$content = $response['body'];

		if($content != '')
		{
			$content = mf_compress(array('content' => $content, 'type' => 'css'));

			$success = set_file_content(array('file' => $style_output_file, 'mode' => 'w', 'content' => $content));

			if($success == false)
			{
				do_log(sprintf(__("Couldn't save content to %s", 'lang_theme_core'), $style_output_file));
			}
		}

		else
		{
			do_log(sprintf(__("Couldn't get any data from %s", 'lang_theme_core'), $style_url));
		}
	}

	else if(file_exists($style_output_file))
	{
		unlink($style_output_file);
	}
}

function settings_theme_core()
{
	$options_area = __FUNCTION__;

	add_settings_section($options_area, "", $options_area."_callback", BASE_OPTIONS_PAGE);

	$arr_settings = array();

	if(get_option('blog_public') == 0)
	{
		$arr_settings["setting_no_public_pages"] = __("Always redirect visitors to the login page", 'lang_theme_core');

		if(get_option('setting_no_public_pages') != 'yes')
		{
			$arr_settings["setting_theme_core_login"] = __("Require login for public site", 'lang_theme_core');
		}
	}

	$arr_settings["setting_save_style"] = __("Save dynamic styles to static CSS file", 'lang_theme_core');
	$arr_settings["setting_scroll_to_top"] = __("Show scroll-to-top-link", 'lang_theme_core');

	foreach($arr_settings as $handle => $text)
	{
		add_settings_field($handle, $text, $handle."_callback", BASE_OPTIONS_PAGE, $options_area);

		register_setting(BASE_OPTIONS_PAGE, $handle);
	}
}

function settings_theme_core_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);

	echo settings_header($setting_key, __("Theme", 'lang_theme_core'));
}

function setting_theme_core_login_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option_or_default($setting_key, 'no');

	echo show_select(array('data' => get_yes_no_for_select(), 'name' => $setting_key, 'compare' => $option));
}

function setting_no_public_pages_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option_or_default($setting_key, 'no');

	echo show_select(array('data' => get_yes_no_for_select(), 'name' => $setting_key, 'compare' => $option));
}

function setting_save_style_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option_or_default($setting_key, 'no');

	echo show_select(array('data' => get_yes_no_for_select(), 'name' => $setting_key, 'compare' => $option, 'description' => __("May be good to disable when working on a development site and then enable when going live", 'lang_theme_core')));
}

function setting_scroll_to_top_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option_or_default($setting_key, 'no');

	echo show_select(array('data' => get_yes_no_for_select(), 'name' => $setting_key, 'compare' => $option));
}

function require_user_login()
{
	$blog_public = get_option('blog_public');

	if($blog_public == 0)
	{
		if(get_option('setting_no_public_pages') == 'yes')
		{
			wp_redirect(get_site_url()."/wp-admin/");
			exit;
		}

		else if(get_option('setting_theme_core_login') == 'yes' && !is_user_logged_in())
		{
			wp_redirect(get_site_url()."/wp-login.php?redirect_to=".$_SERVER['PHP_SELF']);
			exit;
		}
	}
}

function get_media_fonts()
{
	global $wpdb;

	$arr_allowed_extensions = array('.eot', 'otf', '.svg', '.ttf', '.woff');
	$arr_media_fonts = array();

	$result = $wpdb->get_results("SELECT post_title, guid FROM ".$wpdb->posts." WHERE post_type = 'attachment' AND guid REGEXP '".implode("|", $arr_allowed_extensions)."' ORDER BY post_title ASC, post_date ASC");

	foreach($result as $r)
	{
		$media_title = $r->post_title;
		$media_name = sanitize_title($media_title);
		$media_guid = $r->guid;
		$media_extension = pathinfo($media_guid, PATHINFO_EXTENSION);

		if(in_array(".".$media_extension, $arr_allowed_extensions))
		{
			$arr_media_fonts[$media_name]['title'] = $media_title;
			$arr_media_fonts[$media_name]['guid'] = str_replace(".".$media_extension, "", $media_guid);
			$arr_media_fonts[$media_name]['extensions'][] = $media_extension;
		}
	}

	return $arr_media_fonts;
}

function get_theme_fonts()
{
	$options_fonts = array();

	$arr_media_fonts = get_media_fonts();

	foreach($arr_media_fonts as $media_key => $media_font)
	{
		$options_fonts[$media_key] = array(
			'title' => $media_font['title'],
			'style' => "'".$media_font['title']."'",
			'file' => $media_font['guid'],
			'extensions' => $media_font['extensions'],
		);
	}

	$options_fonts[2] = array(
		'title' => "Arial",
		'style' => "Arial, sans-serif",
		'url' => ""
	);

	$options_fonts[3] = array(
		'title' => "Droid Sans",
		'style' => "'Droid Sans', sans-serif",
		'url' => "//fonts.googleapis.com/css?family=Droid+Sans"
	);

	$options_fonts[5] = array(
		'title' => "Droid Serif",
		'style' => "'Droid Serif', serif",
		'url' => "//fonts.googleapis.com/css?family=Droid+Serif"
	);

	$options_fonts[1] = array(
		'title' => "Courgette",
		'style' => "'Courgette', cursive",
		'url' => "//fonts.googleapis.com/css?family=Courgette"
	);

	$options_fonts[6] = array(
		'title' => "Garamond",
		'style' => "'EB Garamond', serif",
		'url' => "//fonts.googleapis.com/css?family=EB+Garamond"
	);

	$options_fonts['lato'] = array(
		'title' => "Lato",
		'style' => "'Lato', sans-serif",
		'url' => "//fonts.googleapis.com/css?family=Lato"
	);

	$options_fonts['montserrat'] = array(
		'title' => "Montserrat",
		'style' => "'Montserrat', sans-serif",
		'url' => "//fonts.googleapis.com/css?family=Montserrat:400,700"
	);

	$options_fonts[4] = array(
		'title' => "Open Sans",
		'style' => "'Open Sans', sans-serif",
		'url' => "//fonts.googleapis.com/css?family=Open+Sans"
	);

	$options_fonts['oswald'] = array(
		'title' => "Oswald",
		'style' => "'Oswald', sans-serif",
		'url' => "//fonts.googleapis.com/css?family=Oswald"
	);

	$options_fonts['roboto'] = array(
		'title' => "Roboto",
		'style' => "'Roboto', sans-serif",
		'url' => "//fonts.googleapis.com/css?family=Roboto"
	);

	$options_fonts['roboto_condensed'] = array(
		'title' => "Roboto Condensed",
		'style' => "'Roboto Condensed', sans-serif",
		'url' => "//fonts.googleapis.com/css?family=Roboto+Condensed"
	);

	$options_fonts['roboto_mono'] = array(
		'title' => "Roboto Mono",
		'style' => "'Roboto Mono', sans-serif",
		'url' => "//fonts.googleapis.com/css?family=Roboto+Mono"
	);

	$options_fonts['sorts_mill_goudy'] = array(
		'title' => "Sorts Mill Goudy",
		'style' => "'sorts-mill-goudy',serif",
		'url' => "//fonts.googleapis.com/css?family=Sorts+Mill+Goudy"
	);

	$options_fonts['source_sans_pro'] = array(
		'title' => "Source Sans Pro",
		'style' => "'Source Sans Pro', sans-serif",
		'url' => "//fonts.googleapis.com/css?family=Source+Sans+Pro"
	);

	return $options_fonts;
}

function show_font_face($options_params, $options_fonts, $options)
{
	$out = "";

	foreach($options_params as $param)
	{
		if(isset($param['type']) && $param['type'] == 'font')
		{
			$font = $options[$param['id']];

			if($font != '' && isset($options_fonts[$font]['file']) && $options_fonts[$font]['file'] != '')
			{
				$font_file = $options_fonts[$font]['file'];

				$font_src = "";

				foreach($options_fonts[$font]['extensions'] as $font_extension)
				{
					$font_src .= ($font_src != '' ? "," : "");

					switch($font_extension)
					{
						case 'eot':		$font_src .= "url('".$font_file.".eot?#iefix') format('embedded-opentype')";	break;
						case 'otf':		$font_src .= "url('".$font_file.".otf') format('opentype')";					break;
						case 'woff':	$font_src .= "url('".$font_file.".woff') format('woff')";						break;
						case 'ttf':		$font_src .= "url('".$font_file.".ttf') format('truetype')";					break;
						case 'svg':		$font_src .= "url('".$font_file.".svg#".$font."') format('svg')";				break;
					}
				}

				if($font_src != '')
				{
					$out .= "@font-face
					{
						font-family: '".$options_fonts[$font]['title']."';
						src: ".$font_src.";
						font-weight: normal;
						font-style: normal;
					}";
				}
			}
		}
	}

	return $out;
}

function customize_theme($wp_customize)
{
	list($options_params, $options) = get_params();
	$options_fonts = get_theme_fonts();

	$id_temp = "";

	$wp_customize->remove_section('themes');
	$wp_customize->remove_section('title_tagline');
	$wp_customize->remove_section('static_front_page');

	foreach($options_params as $param)
	{
		if(isset($param['show_if']) && $param['show_if'] != '' && $options[$param['show_if']] == ''){}

		else if(isset($param['hide_if']) && $param['hide_if'] != '' && $options[$param['hide_if']] != ''){}

		else
		{
			if(isset($param['category']))
			{
				$id_temp = $param['id'];

				$wp_customize->add_section(
					$id_temp,
					array(
						'title' => $param['category'],
						//'description' => '',
						//'priority' => 1,
					)
				);
			}

			else if(isset($param['category_end'])){}

			else
			{
				$wp_customize->add_setting(
					$param['id'],
					array(
						'default' => isset($param['default']) ? $param['default'] : "",
						'transport' => "postMessage"
					)
				);

				if($param['type'] == "color")
				{
					$wp_customize->add_control(
						new WP_Customize_Color_Control(
							$wp_customize,
							$param['id'],
							array(
								'label' => $param['title'],
								'section' => $id_temp,
								'settings' => $param['id'],
							)
						)
					);
				}

				else if($param['type'] == "image")
				{
					$wp_customize->add_control(
						new WP_Customize_Image_Control(
							$wp_customize,
							$param['id'],
							array(
								'label'      => $param['title'],
								'section'    => $id_temp,
								'settings'   => $param['id'],
								//'context'    => 'your_setting_context'
							)
						)
					);
				}

				if(in_array($param['type'], array("date", "email", "hidden", "number", "text", "textarea", "url")))
				{
					$wp_customize->add_control(
						$param['id'],
						array(
							'label' => $param['title'],
							'section' => $id_temp,
							'type' => $param['type'],
							//'placeholder' => (isset($param['placeholder']) ? $param['placeholder'] : "")
						)
					);
				}

				else if($param['type'] == "checkbox")
				{
					$wp_customize->add_control(
						$param['id'], 
						array(
							'label'    => $param['title'],
							'section'  => $id_temp,
							'settings' => $param['id'],
							'type'     => 'select',
							'choices'  => array(
								2 => __("Yes", 'lang_theme_core'),
								1 => __("No", 'lang_theme_core'),
							),
						)
					);
				}

				else if($param['type'] == "float")
				{
					$wp_customize->add_control(
						$param['id'], 
						array(
							'label'    => $param['title'],
							'section'  => $id_temp,
							'settings' => $param['id'],
							'type'     => 'select',
							'choices'  => array(
								'' => "-- ".__("Choose here", 'lang_theme_core')." --",
								'none' => __("None", 'lang_theme_core'),
								'left' => __("Left", 'lang_theme_core'),
								'right' => __("Right", 'lang_theme_core'),
								'initial' => __("Initial", 'lang_theme_core'),
								'inherit' => __("Inherit", 'lang_theme_core'),
							),
						)
					);
				}

				else if($param['type'] == "font")
				{
					$choices = array();

					$choices[0] = "-- ".__("Choose here", 'lang_theme_core')." --";

					foreach($options_fonts as $key => $value)
					{
						$choices[$key] = $value['title'];
					}

					$wp_customize->add_control(
						$param['id'], 
						array(
							'label'    => $param['title'],
							'section'  => $id_temp,
							'settings' => $param['id'],
							'type'     => 'select',
							'choices'  => $choices,
						)
					);
				}
			}
		}
	}
}

function render_css($data)
{
	global $options, $options_fonts;

	if(!isset($data['property'])){	$data['property'] = "";}
	if(!isset($data['prefix'])){	$data['prefix'] = "";}
	if(!isset($data['suffix'])){	$data['suffix'] = "";}

	$prop = $data['property'];
	$pre = $data['prefix'];
	$suf = $data['suffix'];
	$val = $data['value'];

	$out = "";

	if($prop == "font-family" && (!isset($options[$val]) || !isset($options_fonts[$options[$val]]['style'])))
	{
		$options[$val] = "";
	}

	if(isset($options[$val]) && $options[$val] != '') // && ($prop != "font-family" || $options[$val] != 0)
	{
		if($prop != '')
		{
			$out .= $prop.": ";
		}

		else if($pre != '')
		{
			$out .= $pre;
		}

			if($prop == "font-family")
			{
				$out .= $options_fonts[$options[$val]]['style'];
			}

			else
			{
				$out .= $options[$val];
			}

		if($suf != '')
		{
			$out .= $suf;
		}

		if($prop != '' || $pre != '' || $suf != '')
		{
			$out .= ";";
		}
	}

	return $out;
}

function head_theme()
{
	if(!(get_current_user_id() > 0))
	{
		wp_deregister_style('dashicons');
	}

	mf_enqueue_script('script_theme_core', plugin_dir_url(__FILE__)."script.js");

	if(get_option('setting_scroll_to_top') == 'yes')
	{
		wp_enqueue_style('style_theme_scroll', plugin_dir_url(__FILE__)."style_scroll.css");
		mf_enqueue_script('script_theme_scroll', plugin_dir_url(__FILE__)."script_scroll.js");
	}
}