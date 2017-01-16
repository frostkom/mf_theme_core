<?php

function gather_params($options_params)
{
	$options = array();

	$mods = get_theme_mods();

	foreach($options_params as $param)
	{
		if(!isset($param['category']) && !isset($param['category_end']))
		{
			if(isset($mods[$param['id']]))
			{
				$options[$param['id']] = apply_filters("theme_mod_".$param['id'], $mods[$param['id']]);
			}

			else
			{
				//$param['default'] = isset($param['default']) ? sprintf($param['default'], get_template_directory_uri(), get_stylesheet_directory_uri()) : false;
				$param['default'] = isset($param['default']) ? $param['default'] : false;

				$options[$param['id']] = apply_filters("theme_mod_".$param['id'], $param['default']);
			}
		}
	}

	//Old way (5-10x slower)
	/*foreach($options_params as $param)
	{
		if(!isset($param['category']) && !isset($param['category_end']))
		{
			$default = isset($param['default']) ? $param['default'] : "";

			$options[$param['id']] = get_theme_mod($param['id'], $default);
		}
	}*/

	return array($options_params, $options);
}

function get_404_page()
{
	global $wpdb;

	$setting_404_page = get_option('setting_404_page');

	$post_title = __("Not Found", 'lang_theme_core');
	$post_content = "<p>"
		.__("Apologies, but the page you requested could not be found. Perhaps searching will help", 'lang_theme_core')
		.get_search_form(false)
	."</p>";

	if($setting_404_page > 0)
	{
		$result = $wpdb->get_results($wpdb->prepare("SELECT post_title, post_content FROM ".$wpdb->posts." WHERE ID = '%d' AND post_type = 'page' AND post_status = 'publish'", $setting_404_page));

		foreach($result as $r)
		{
			$post_title = $r->post_title;
			$post_content = apply_filters('the_content', $r->post_content);
		}
	}

	echo "<article>
		<h1>".$post_title."</h1>
		<section>".$post_content."</section>
	</article>";
}

if(!function_exists('get_previous_backups'))
{
	function get_previous_backups($data)
	{
		global $globals;

		$globals['mf_theme_files'][] = array(
			'dir' => $data['file'],
			'name' => basename($data['file']), 
			'time' => filemtime($data['file'])
		);
	}
}

function get_options_page_theme_core($data = array())
{
	global $done_text, $error_text, $globals;

	$out = "";

	$strFileName = check_var('strFileName');
	$strFileContent = isset($_REQUEST['strFileContent']) ? $_REQUEST['strFileContent'] : "";

	list($upload_path, $upload_url) = get_uploads_folder($data['dir']);

	$dir_exists = true;

	if(!is_dir($upload_path))
	{
		if(!mkdir($upload_path, 0755, true))
		{
			$dir_exists = false;
		}
	}

	if($dir_exists == false)
	{
		$error_text = __("Could not create a folder in uploads. Please add the correct rights for the script to create a new subfolder", 'lang_theme_core');
	}

	else if(isset($_POST['btnThemeBackup']))
	{
		list($options_params, $options) = get_params();

		if(count($options) > 0)
		{
			$file = $data['dir']."_".date("YmdHi").".json";

			$success = set_file_content(array('file' => $upload_path.$file, 'mode' => 'a', 'content' => json_encode($options)));

			if($success == true)
			{
				$done_text = __("The theme settings were backed up", 'lang_theme_core');
			}

			else
			{
				$error_text = __("It was not possible to backup the theme settings", 'lang_theme_core');
			}
		}

		else
		{
			$error_text = __("There were no theme settings to save", 'lang_theme_core');
		}
	}

	else if(isset($_REQUEST['btnThemeRestore']))
	{
		if($strFileName != '')
		{
			$strFileContent = get_file_content(array('file' => $upload_path.$strFileName));
		}

		else
		{
			$strFileContent = stripslashes($strFileContent);
		}

		if($strFileContent != '')
		{
			$json = json_decode($strFileContent, true);

			if(is_array($json))
			{
				foreach($json as $key => $value)
				{
					if($value != '')
					{
						set_theme_mod($key, $value);
					}
				}

				$done_text = __("The restore was successful", 'lang_theme_core');

				$strFileContent = "";
			}

			else
			{
				$error_text = __("There is something wrong with the source to restore", 'lang_theme_core')." (".htmlspecialchars($strFileContent)." -> ".var_export($json, true).")";
			}
		}
	}

	else if(isset($_GET['btnThemeDelete']))
	{
		unlink($upload_path.$strFileName);

		$done_text = __("The file was deleted successfully", 'lang_parallax');
	}

	$out .= "<div class='wrap'>
		<h2>".__("Theme Options", 'lang_theme_core')."</h2>"
		.get_notification();

		if($dir_exists == true)
		{
			$out .= "<div id='poststuff'>
				<div id='post-body' class='columns-2'>
					<div id='post-body-content'>";

						$globals['mf_theme_files'] = array();

						get_file_info(array('path' => $upload_path, 'callback' => "get_previous_backups"));

						$count_temp = count($globals['mf_theme_files']);

						if($count_temp > 0)
						{
							$out .= "<table class='widefat striped'>";

								$arr_header[] = __("Existing", 'lang_theme_core');
								$arr_header[] = __("Date", 'lang_theme_core');

								$out .= show_table_header($arr_header)
								."<tbody>";

									for($i = 0; $i < $count_temp; $i++)
									{
										$out .= "<tr>
											<td>"
												.$globals['mf_theme_files'][$i]['name']
												."<div class='row-actions'>
													<a href='".$upload_url.$globals['mf_theme_files'][$i]['name']."'>".__("Download", 'lang_theme_core')."</a>
													 | <a href='".admin_url("themes.php?page=theme_options&btnThemeRestore&strFileName=".$globals['mf_theme_files'][$i]['name'])."'>".__("Restore", 'lang_theme_core')."</a>
													 | <a href='".admin_url("themes.php?page=theme_options&btnThemeDelete&strFileName=".$globals['mf_theme_files'][$i]['name'])."'>".__("Delete", 'lang_theme_core')."</a>
												</div>
											</td>
											<td>".format_date(date("Y-m-d H:i:s", $globals['mf_theme_files'][$i]['time']))."</td>
										</tr>";
									}

								$out .= "</tbody>
							</table>
							<br>";
						}

						$out .= "<div class='postbox'>
							<h3 class='hndle'><span>".__("External Backup", 'lang_theme_core')."</span></h3>
							<div class='inside'>
								<form method='post' action='' class='mf_form'>
									<div>"
										.show_textarea(array('name' => 'strFileContent', 'value' => stripslashes($strFileContent)))
										.show_button(array('name' => "btnThemeRestore", 'text' => __("Restore", 'lang_theme_core')))
									."</div>
								</form>
							</div>
						</div>
					</div>
					<div id='postbox-container-1'>
						<div class='postbox'>
							<h3 class='hndle'><span>".__("New Backup", 'lang_theme_core')."</span></h3>
							<div class='inside'>
								<form method='post' action='' class='mf_form'>"
									.show_button(array('name' => "btnThemeBackup", 'text' => __("Save", 'lang_theme_core')))
								."</form>
							</div>
						</div>
					</div>
				</div>
			</div>";
		}

	$out .= "</div>";

	return $out;
}

function nav_args_theme_core($args)
{
	if(isset($args['container_override']) && $args['container_override'] == false){}

	else if(!isset($args['container']) || $args['container'] == '' || $args['container'] == 'div')
	{
		$args['container'] = "nav";
	}

	return $args;
}

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

function get_wp_title()
{
	global $page, $paged;

	$out = wp_title('|', false, 'right');

	$out .= get_bloginfo('name');

	$site_description = get_bloginfo('description', 'display');

	if($site_description != '' && (is_home() || is_front_page()))
	{
		$out .= " | ".$site_description;
	}

	if($paged >= 2 || $page >= 2)
	{
		$out .= " | ".sprintf( __("Page %s", 'lang_theme_core'), max($paged, $page));
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

	if(get_option('setting_save_style') == 'yes')
	{
		$style_url = replace_stylesheet_url("php");

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
		$arr_settings['setting_no_public_pages'] = __("Always redirect visitors to the login page", 'lang_theme_core');

		if(get_option('setting_no_public_pages') != 'yes')
		{
			$arr_settings['setting_theme_core_login'] = __("Require login for public site", 'lang_theme_core');
		}
	}

	$arr_settings['setting_save_style'] = __("Save dynamic styles to static CSS file", 'lang_theme_core');
	$arr_settings['setting_scroll_to_top'] = __("Show scroll-to-top-link", 'lang_theme_core');

	$arr_settings['setting_compress'] = __("Compress output", 'lang_theme_core');
	$arr_settings['setting_responsiveness'] = __("Image responsiveness", 'lang_theme_core');

	if(function_exists('get_params'))
	{
		list($options_params, $options) = get_params();
	}

	else
	{
		$options = array();
	}

	if(isset($options['body_history']) && $options['body_history'] == 2)
	{
		//Relative URLs does not work in Chrome & IE when using pushState
		delete_option('setting_strip_domain');
	}

	else
	{
		$arr_settings['setting_strip_domain'] = __("Force relative URLs", 'lang_theme_core');
	}

	if(is_plugin_active("mf_analytics/index.php") && (get_option('setting_analytics_google') != '' || get_option('setting_analytics_clicky') != ''))
	{
		$arr_settings['setting_cookie_info'] = __("Cookie information", 'lang_theme_core');
	}

	else
	{
		delete_option('setting_cookie_info');
	}

	$arr_settings['setting_404_page'] = __("404 Page", 'lang_theme_core');

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

	echo show_select(array('data' => get_yes_no_for_select(), 'name' => $setting_key, 'value' => $option));
}

function setting_no_public_pages_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option_or_default($setting_key, 'no');

	echo show_select(array('data' => get_yes_no_for_select(), 'name' => $setting_key, 'value' => $option));
}

function setting_save_style_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option_or_default($setting_key, 'no');

	echo show_select(array('data' => get_yes_no_for_select(), 'name' => $setting_key, 'value' => $option, 'suffix' => __("May be good to disable when working on a development site and then enable when going live", 'lang_theme_core')));
}

function setting_scroll_to_top_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option_or_default($setting_key, 'no');

	echo show_select(array('data' => get_yes_no_for_select(), 'name' => $setting_key, 'value' => $option));
}

function setting_compress_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option_or_default($setting_key, get_option('eg_setting_compress'));

	echo show_select(array('data' => get_yes_no_for_select(array('return_integer' => true)), 'name' => $setting_key, 'value' => $option));
}

function setting_responsiveness_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option_or_default($setting_key, get_option('eg_setting_responsiveness'));

	echo show_select(array('data' => get_yes_no_for_select(array('return_integer' => true)), 'name' => $setting_key, 'value' => $option, 'suffix' => __("To strip all content tags from height and width to improve responsiveness", 'lang_theme_core')));
}

function setting_strip_domain_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option_or_default($setting_key, get_option('eg_setting_strip_domain'));

	echo show_select(array('data' => get_yes_no_for_select(array('return_integer' => true)), 'name' => $setting_key, 'value' => $option));
}

function setting_cookie_info_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option_or_default($setting_key);

	$arr_data = array();
	get_post_children(array('add_choose_here' => true, 'output_array' => true), $arr_data);

	echo show_select(array('data' => $arr_data, 'name' => $setting_key, 'value' => $option, 'suffix' => "<a href='".admin_url("post-new.php?post_type=page")."'><i class='fa fa-lg fa-plus'></i></a>", 'description' => __("The content from this page will be displayed on top of the page until the visitor clicks to accept the use of cookies", 'lang_theme_core')));
}

function setting_404_page_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option_or_default($setting_key);

	$arr_data = array();
	get_post_children(array('add_choose_here' => true, 'output_array' => true), $arr_data);

	echo show_select(array('data' => $arr_data, 'name' => $setting_key, 'value' => $option, 'suffix' => "<a href='".admin_url("post-new.php?post_type=page")."'><i class='fa fa-lg fa-plus'></i></a>", 'description' => __("This page will be displayed instead of the default 404 page", 'lang_theme_core')));
}

function require_user_login()
{
	$blog_public = get_option('blog_public');

	if($blog_public == 0)
	{
		if(get_option('setting_no_public_pages') == 'yes')
		{
			mf_redirect(get_site_url()."/wp-admin/");
		}

		else if(get_option('setting_theme_core_login') == 'yes' && !is_user_logged_in())
		{
			mf_redirect(get_site_url()."/wp-login.php?redirect_to=".$_SERVER['PHP_SELF']);
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
								'label' => $param['title'],
								'section' => $id_temp,
								'settings' => $param['id'],
								//'context' => 'your_setting_context'
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
							'label' => $param['title'],
							'section' => $id_temp,
							'settings' => $param['id'],
							'type' => 'select',
							'choices' => array(
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
							'label' => $param['title'],
							'section' => $id_temp,
							'settings' => $param['id'],
							'type' => 'select',
							'choices' => array(
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
							'label' => $param['title'],
							'section' => $id_temp,
							'settings' => $param['id'],
							'type' => 'select',
							'choices' => $choices,
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

	if(isset($options[$val]) && $options[$val] != '')
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

function head_theme_core()
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

function footer_theme_core()
{
	global $wpdb;

	if(!isset($_COOKIE['cookie_accepted']))
	{
		$setting_cookie_info = get_option('setting_cookie_info');

		if($setting_cookie_info > 0)
		{
			wp_enqueue_style('style_theme_core_cookies', plugin_dir_url(__FILE__)."style_cookies.css");
			mf_enqueue_script('script_theme_core_cookies', plugin_dir_url(__FILE__)."script_cookies.js", array('plugin_url' => plugin_dir_url(__FILE__)));

			$result = $wpdb->get_results($wpdb->prepare("SELECT ID, post_title, post_excerpt, post_content FROM ".$wpdb->posts." WHERE ID = '%d' AND post_type = 'page' AND post_status = 'publish'", $setting_cookie_info));

			foreach($result as $r)
			{
				$post_id = $r->ID;
				$post_title = $r->post_title;
				$post_excerpt = $r->post_excerpt;
				$post_content = apply_filters('the_content', $r->post_content);

				echo "<div id='accept_cookies'>
					<div>
						<i class='fa fa-legal red'></i>";

						$accept_link = "<a href='#accept_cookie' class='button'><i class='fa fa-check green'></i>".__("Accept", 'lang_theme_core')."</a>";

						if($post_excerpt != '')
						{
							echo "<p>"
								.$post_excerpt
							."</p>";

							if($post_content != '' && $post_content != $post_excerpt)
							{
								$post_url = get_permalink($post_id);

								echo "<a href='".$post_url."'>".__("Read more", 'lang_theme_core')."</a>";
							}

							echo $accept_link;
						}

						else
						{
							echo $post_content
							.$accept_link;
						}

					echo "</div>
				</div>";
			}
		}
	}
}

function admin_bar_theme_core()
{
	global $wp_admin_bar;

	if(IS_ADMIN)
	{
		$color = "color_red";

		if(get_option('setting_no_public_pages') == 'yes')
		{
			$wp_admin_bar->remove_menu('site-name');

			$title = __("No public pages", 'lang_theme_core');
		}

		else if(get_option('setting_theme_core_login') == 'yes')
		{
			$title = __("Requires login", 'lang_theme_core');
		}

		else if(get_option('blog_public') == 0)
		{
			$color = "color_yellow";
			$title = __("No index", 'lang_theme_core');
		}

		else
		{
			$color = "color_green";
			$title = __("Public", 'lang_theme_core');
		}

		$wp_admin_bar->add_node(array(
			'id' => 'live',
			'title' => "<span class='".$color."'>".$title."</span>",
			//'href' => '#',
			//'meta' => array('class' => 'red'),
		));
	}
}

function init_theme_core()
{
	if(is_admin())
	{
		//new recommend_plugin(array('path' => "mf_custom_login/index.php", 'name' => "MF Custom Login", 'text' => __("because you should add information about the use of cookies on the site", 'lang_theme_core'), 'url' => "//github.com/frostkom/mf_custom_login"));
	}

	else
	{
		if(get_option('setting_responsiveness') == 1)
		{
			add_filter('post_thumbnail_html', 'remove_width_height_attribute', 10);
			add_filter('image_send_to_editor', 'remove_width_height_attribute', 10);
			//add_filter('wp_insert_post_data', 'post_filter_handler', '99', 2);

			add_filter('the_content', 'remove_width_height_attribute');
		}
	}
}

function header_theme_core()
{
	require_user_login();

	if(get_option('setting_compress') == 1)
	{
		ob_start("compress_html");
	}

	if(!is_feed() && !get_query_var('sitemap') && get_option('setting_strip_domain') == 1)
	{
		ob_start("strip_domain_from_content");
	}
}

function init_style_theme_core()
{
	if(get_option('setting_strip_domain') == 1)
	{
		ob_start("strip_domain_from_content");
	}
}

function compress_html($html)
{
	$out = "";

	if(strlen($html) > 0)
	{
		$exkludera = array('!/\*[^*]*\*+([^/][^*]*\*+)*/!', '/>(\n|\r|\t|\r\n|  |	)+/', '/(\n|\r|\t|\r\n|  |	)+</', '<!--.*?-->');
		$inkludera = array('', '>', '<', '');

		$out = preg_replace($exkludera, $inkludera, $html);

		//If regexp fails, restore here
		if(strlen($out) == 0)
		{
			$out = $html;
		}
	}

	return $out;
}

/*function post_filter_handler($data, $postarr)
{
	$data['post_content'] = remove_width_height_attribute($data['post_content']);

	return $data;
}*/

function remove_width_height_attribute($html)
{
	return preg_replace('/(width|height)="\d*"\s/', "", $html);
}

function strip_domain_from_content($html)
{
	$site_url = get_option('siteurl');
	$site_url_alt = (substr($site_url, 0, 5) == "https" ? str_replace("https:", "http:", $site_url) : str_replace("http:", "https:", $site_url));

	return str_replace(array($site_url, $site_url_alt), "", $html);
}

/*function rw_relative_urls()
{
	// Don't do anything if:
	// - In feed
	// - In sitemap by WordPress SEO plugin
	if(is_feed() || get_query_var('sitemap'))
	{
		return;
	}

	else
	{
		$filters = array(
			'post_link',
			'post_type_link',
			'page_link',
			'attachment_link',
			'get_shortlink',
			'post_type_archive_link',
			'get_pagenum_link',
			'get_comments_pagenum_link',
			'term_link',
			'search_link',
			'day_link',
			'month_link',
			'year_link',
			//'author_link',
			//'edit_post_link',
			//'wp_get_attachment_image_src',
		);

		foreach($filters as $filter)
		{
			add_filter($filter, 'wp_make_link_relative');
		}
	}
}*/