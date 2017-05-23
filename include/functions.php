<?php

function search_form_theme_core($html)
{
	$html = "<form method='get' action='".esc_url(home_url('/'))."' class='mf_form'>"
		.show_textfield(array('type' => 'search', 'name' => 's', 'value' => get_search_query(), 'placeholder' => __("Search here", 'lang_theme_core')))
		."<div class='form_button'>"
			.show_button(array('text' => __("Search", 'lang_theme_core')))
		."</div>
	</form>";

	return $html;
}

function gather_params($options_params)
{
	$options = array();

	$mods = get_theme_mods();

	foreach($options_params as $param)
	{
		if(!isset($param['category']) && !isset($param['category_end']))
		{
			$id = $param['id'];
			$default = isset($param['default']) ? $param['default'] : false;
			$force_default = isset($param['force_default']) ? $param['force_default'] : false;
			$value_old = isset($mods[$id]) ? $mods[$id] : false;

			if(isset($mods[$id]))
			{
				if($value_old == '' && $force_default == true)
				{
					$value_new = $default;

					set_theme_mod($id, $value_new);
				}

				else
				{
					$value_new = $value_old;
				}
			}

			else
			{
				$value_new = $default;
			}

			$options[$id] = apply_filters("theme_mod_".$id, $value_new);
		}
	}

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

	return "<article>
		<h1>".$post_title."</h1>
		<section>".$post_content."</section>
	</article>";
}

function get_previous_backups($data)
{
	global $globals;

	$globals['mf_theme_files'][] = array(
		'dir' => $data['file'],
		'name' => basename($data['file']),
		'time' => filemtime($data['file'])
	);
}

function get_options_page_theme_core($data = array())
{
	global $done_text, $error_text, $globals;

	$out = "";

	$strFileName = check_var('strFileName');
	$strFileContent = isset($_REQUEST['strFileContent']) ? $_REQUEST['strFileContent'] : "";

	list($upload_path, $upload_url) = get_uploads_folder($data['dir']);

	/*$dir_exists = true;

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

	else */if(isset($_POST['btnThemeBackup']))
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
													 | <a href='".admin_url("themes.php?page=theme_options&btnThemeDelete&strFileName=".$globals['mf_theme_files'][$i]['name'])."' rel='confirm'>".__("Delete", 'lang_theme_core')."</a>
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

function get_wp_title()
{
	global $page, $paged;

	$out = wp_title('|', false, 'right')
	.get_bloginfo('name');

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

/*function customize_preview_theme_core()
{
	mf_enqueue_script('script_theme_core_customizer_preview', plugin_dir_url(__FILE__)."theme-customizer.js", array('jquery', 'customize-preview'), get_plugin_version(__FILE__));
}*/

function check_htaccess($data)
{
	if(basename($data['file']) == ".htaccess")
	{
		$content = get_file_content(array('file' => $data['file']));

		if(!preg_match("/(BEGIN Theme Core)/", $content)) //|FileETag None
		{
			/*$recommend_htaccess = "ExpiresActive On
ExpiresByType text/javascript 'A604800'
ExpiresByType text/css 'A604800'
ExpiresByType image/x-icon 'A31536000'
ExpiresByType image/gif 'A604800'
ExpiresByType image/jpg 'A604800'
ExpiresByType image/jpeg 'A604800'
ExpiresByType image/png 'A604800'

FileETag None

Header set Cache-Control 'must-revalidate'";*/

			$recommend_htaccess = "# BEGIN Theme Core
<IfModule mod_gzip.c>
	mod_gzip_on Yes
	mod_gzip_dechunk Yes
	mod_gzip_item_include file .(html?|txt|css|js|php|pl)$
	mod_gzip_item_include handler ^cgi-script$
	mod_gzip_item_include mime ^text/.*
	mod_gzip_item_include mime ^application/x-javascript.*
	mod_gzip_item_exclude mime ^image/.*
	mod_gzip_item_exclude rspheader ^Content-Encoding:.*gzip.*
</IfModule>

<IfModule mod_expires.c>
	ExpiresActive On
	ExpiresDefault 'access plus 1 month'

	Header unset ETag
</IfModule>

FileETag None

<filesMatch '\.(css|jpe?g|png|gif|js|ico)$'>
	Header set Cache-Control 'max-age=2592000, public'

	SetOutputFilter DEFLATE
</filesMatch>
# END Theme Core";

			echo "<div class='mf_form'>"
				."<h3>".sprintf(__("Copy this to %s", 'lang_theme_core'), ".htaccess")."</h3>"
				.show_textarea(array('value' => $recommend_htaccess, 'xtra' => "rows='12' readonly"))
			."</div>";
		}

		else
		{
			echo __("I have no recommendations for you at this moment", 'lang_theme_core');
		}
	}
}

function settings_theme_core()
{
	$options_area = __FUNCTION__;

	add_settings_section($options_area, "", $options_area."_callback", BASE_OPTIONS_PAGE);

	$arr_settings = array();

	$blog_public = get_option('blog_public');

	if($blog_public == 0)
	{
		$arr_settings['setting_no_public_pages'] = __("Always redirect visitors to the login page", 'lang_theme_core');
	}

	if(get_option('setting_no_public_pages') != 'yes')
	{
		if($blog_public == 0)
		{
			$arr_settings['setting_theme_core_login'] = __("Require login for public site", 'lang_theme_core');
		}

		$arr_settings['setting_html5_history'] = __("Use HTML5 History", 'lang_theme_core');

		$setting_html5_history = get_option('setting_html5_history');

		if($setting_html5_history == 'yes')
		{
			$arr_settings['setting_splash_screen'] = __("Show Splash Screen", 'lang_theme_core');
		}

		else
		{
			delete_option('setting_splash_screen');
		}

		$arr_settings['setting_scroll_to_top'] = __("Show scroll-to-top-link", 'lang_theme_core');
		$arr_settings['setting_responsiveness'] = __("Image responsiveness", 'lang_theme_core');

		if($setting_html5_history == 'yes')
		{
			//Relative URLs does not work in Chrome or IE when using pushState
			delete_option('setting_strip_domain');
		}

		else
		{
			$arr_settings['setting_strip_domain'] = __("Force relative URLs", 'lang_theme_core');
		}

		if(is_plugin_active('wp-super-cache/wp-cache.php') || is_plugin_active('wp-fastest-cache/wpFastestCache.php'))
		{
			$arr_settings['setting_merge_css'] = __("Merge & Compress CSS", 'lang_theme_core');
			$arr_settings['setting_merge_js'] = __("Merge & Compress Javascript", 'lang_theme_core');
		}

		else
		{
			delete_option('setting_merge_css');
			delete_option('setting_merge_js');
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
		$arr_settings['setting_theme_recommendation'] = __("Recommendations", 'lang_theme_core');
	}

	show_settings_fields(array('area' => $options_area, 'settings' => $arr_settings));
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

function setting_html5_history_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option_or_default($setting_key, 'no');

	echo show_select(array('data' => get_yes_no_for_select(), 'name' => $setting_key, 'value' => $option));
}

function setting_splash_screen_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option_or_default($setting_key, 'no');

	echo show_select(array('data' => get_yes_no_for_select(), 'name' => $setting_key, 'value' => $option));
}

function setting_scroll_to_top_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option_or_default($setting_key, 'no');

	echo show_select(array('data' => get_yes_no_for_select(), 'name' => $setting_key, 'value' => $option));
}

function setting_responsiveness_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option_or_default($setting_key, 0);

	echo show_select(array('data' => get_yes_no_for_select(array('return_integer' => true)), 'name' => $setting_key, 'value' => $option, 'suffix' => __("To strip all content tags from height and width to improve responsiveness", 'lang_theme_core')));
}

function setting_strip_domain_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option_or_default($setting_key, 0);

	echo show_select(array('data' => get_yes_no_for_select(array('return_integer' => true)), 'name' => $setting_key, 'value' => $option));
}

function setting_merge_css_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option_or_default($setting_key, 'yes');

	echo show_select(array('data' => get_yes_no_for_select(), 'name' => $setting_key, 'value' => $option));
}

function setting_merge_js_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option_or_default($setting_key, 'yes');

	echo show_select(array('data' => get_yes_no_for_select(), 'name' => $setting_key, 'value' => $option));
}

function setting_cookie_info_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option_or_default($setting_key);

	$arr_data = array();
	get_post_children(array('add_choose_here' => true), $arr_data);

	echo show_select(array('data' => $arr_data, 'name' => $setting_key, 'value' => $option, 'suffix' => "<a href='".admin_url("post-new.php?post_type=page")."'><i class='fa fa-lg fa-plus'></i></a>", 'description' => __("The content from this page will be displayed on top of the page until the visitor clicks to accept the use of cookies", 'lang_theme_core')));
}

function setting_404_page_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option_or_default($setting_key);

	$arr_data = array();
	get_post_children(array('add_choose_here' => true), $arr_data);

	echo show_select(array('data' => $arr_data, 'name' => $setting_key, 'value' => $option, 'suffix' => "<a href='".admin_url("post-new.php?post_type=page")."'><i class='fa fa-lg fa-plus'></i></a>", 'description' => __("This page will be displayed instead of the default 404 page", 'lang_theme_core')));
}

function setting_theme_recommendation_callback()
{
	get_file_info(array('path' => get_home_path(), 'callback' => "check_htaccess", 'allow_depth' => false));
}

function column_header_theme_core($cols)
{
	unset($cols['comments']);

	$cols['seo'] = __("SEO", 'lang_theme_core');

	return $cols;
}

function column_cell_theme_core($col, $id)
{
	global $wpdb;

	switch($col)
	{
		case 'seo':
			$post_excerpt = mf_get_post_content($id, 'post_excerpt');

			if($post_excerpt != '')
			{
				$post_id_duplicate = $wpdb->get_var($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." WHERE post_excerpt = %s AND post_status = 'publish' AND ID != '%d' LIMIT 0, 1", $post_excerpt, $id));

				if($post_id_duplicate > 0)
				{
					echo "<i class='fa fa-lg fa-close red'></i>
					<div class='row-actions'>
						<a href='".admin_url("post.php?post=".$post_id_duplicate."&action=edit")."'>"
							.sprintf(__("The page %s have the exact same excerpt. Please, try to not have duplicates because that will hurt your SEO.", 'lang_theme_core'), get_post_title($post_id_duplicate))
						."</a>
					</div>";
				}

				else
				{
					echo "<i class='fa fa-lg fa-check green'></i>";
				}
			}

			else
			{
				echo "<i class='fa fa-lg fa-close red'></i>
				<div class='row-actions'>"
					.__("You have not set an excerpt for this page", 'lang_theme_core')
				."</div>";
			}
		break;
	}
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

	$options_fonts[2] = array(
		'title' => "Helvetica",
		'style' => "Helvetica, sans-serif",
		'url' => ""
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
	//$wp_customize->remove_section('nav_menus');
	//$wp_customize->remove_section('widgets');
	$wp_customize->remove_section('custom_css');

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

				if($param['type'] == "align")
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
								'left' => __("Left", 'lang_theme_core'),
								'center' => __("Center", 'lang_theme_core'),
								'right' => __("Right", 'lang_theme_core'),
							),
						)
					);
				}

				else if($param['type'] == "color")
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

				else if($param['type'] == "clear")
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
								'left' => __("Left", 'lang_theme_core'),
								'right' => __("Right", 'lang_theme_core'),
								'both' => __("Both", 'lang_theme_core'),
								'none' => __("None", 'lang_theme_core'),
							),
						)
					);
				}

				else if(in_array($param['type'], array("date", "email", "hidden", "number", "text", "textarea", "url")))
				{
					$wp_customize->add_control(
						$param['id'],
						array(
							'label' => $param['title'],
							'section' => $id_temp,
							'type' => $param['type'],
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
					$choices[''] = "-- ".__("Choose here", 'lang_theme_core')." --";

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

				else if($param['type'] == "overflow")
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
								'visible' => __("Visible", 'lang_theme_core'),
								'hidden' => __("Hidden", 'lang_theme_core'),
								'scroll' => __("Scroll", 'lang_theme_core'),
								'auto' => __("Auto", 'lang_theme_core'),
								'initial' => __("Initial", 'lang_theme_core'),
								'inherit' => __("Inherit", 'lang_theme_core'),
							),
						)
					);
				}

				else if($param['type'] == "position")
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
								'absolute' => __("Absolute", 'lang_theme_core'),
								'fixed' => __("Fixed", 'lang_theme_core'),
							),
						)
					);
				}

				else if($param['type'] == "text_transform")
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
								'uppercase' => __("Uppercase", 'lang_theme_core'),
								'lowercase' => __("Lowercase", 'lang_theme_core'),
							),
						)
					);
				}

				else if($param['type'] == "weight")
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
								'lighter' => __("Lighter", 'lang_theme_core'),
								'normal' => __("Normal", 'lang_theme_core'),
								'bold' => __("Bold", 'lang_theme_core'),
								'bolder' => __("Bolder", 'lang_theme_core'),
								'initial' => __("Initial", 'lang_theme_core'),
								'inherit' => __("Inherit", 'lang_theme_core'),
							),
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

	$plugin_include_url = plugin_dir_url(__FILE__);

	mf_enqueue_script('script_theme_core', $plugin_include_url."script.js", get_plugin_version(__FILE__));

	if(get_option('setting_scroll_to_top') == 'yes')
	{
		mf_enqueue_style('style_theme_scroll', $plugin_include_url."style_scroll.css", get_plugin_version(__FILE__));
		mf_enqueue_script('script_theme_scroll', $plugin_include_url."script_scroll.js", get_plugin_version(__FILE__));
	}

	if(get_option('setting_html5_history') == 'yes')
	{
		mf_enqueue_style('style_theme_history', $plugin_include_url."style_history.css", get_plugin_version(__FILE__));
		mf_enqueue_script('script_theme_history', $plugin_include_url."script_history.js", array('site_url' => get_site_url()), get_plugin_version(__FILE__));
	}

	$meta_description = get_the_excerpt();

	if($meta_description != '')
	{
		echo "<meta name='description' content='".esc_attr($meta_description)."'>";
	}

	echo "<link rel='alternate' type='application/rss+xml' title='".get_bloginfo('name')."' href='".get_bloginfo('rss2_url')."'>";
}

function get_logo()
{
	if(function_exists('get_logo_theme'))
	{
		return get_logo_theme();
	}

	else if(function_exists('get_logo_parallax'))
	{
		return get_logo_parallax();
	}
}

function print_styles_theme_core()
{
	if(isset($GLOBALS['mf_styles']) && count($GLOBALS['mf_styles']) > 0 && get_option_or_default('setting_merge_css', 'yes') == 'yes')
	{
		$file_url_base = site_url()."/wp-content";
		$file_dir_base = WP_CONTENT_DIR;

		$version = 0;
		$output = "";

		foreach($GLOBALS['mf_styles'] as $handle => $arr_style)
		{
			$version += point2int($arr_style['version']);

			//$output .= "\n\n/* ".$handle." */\n";

			if(get_file_suffix($arr_style['file']) == 'php')
			{
				list($content, $headers) = get_url_content($arr_style['file'], true);

				if(isset($headers['http_code']) && $headers['http_code'] == 200)
				{
					$output .= $content;
				}

				else
				{
					unset($GLOBALS['mf_styles'][$handle]);

					do_log(sprintf(__("Could not load %s", 'lang_theme_core'), $arr_style['file']));
				}
			}

			else
			{
				$output .= get_file_content(array('file' => str_replace($file_url_base, $file_dir_base, $arr_style['file'])));
			}
		}

		if($output != '')
		{
			list($upload_path, $upload_url) = get_uploads_folder("mf_theme_core/styles");
			$file = "style.css";

			$output = compress_css($output);

			$success = set_file_content(array('file' => $upload_path.$file, 'mode' => 'w', 'content' => $output));

			if($success == true)
			{
				foreach($GLOBALS['mf_styles'] as $handle => $arr_style)
				{
					wp_deregister_style($handle);
				}

				$version = int2point($version);

				wp_enqueue_style('mf_styles', $upload_url.$file, array(), $version);
			}
		}
	}
}

function print_scripts_theme_core()
{
	if(isset($GLOBALS['mf_scripts']) && count($GLOBALS['mf_scripts']) > 0 && get_option_or_default('setting_merge_js', 'yes') == 'yes')
	{
		$file_url_base = site_url()."/wp-content";
		$file_dir_base = WP_CONTENT_DIR;

		$version = 0;
		$output = $translation = "";
		$error = false;

		foreach($GLOBALS['mf_scripts'] as $handle => $arr_script)
		{
			$version += point2int($arr_script['version']);

			//$output .= "\n\n/* ".$handle." */\n";

			$count_temp = count($arr_script['translation']);

			if(is_array($arr_script['translation']) && $count_temp > 0)
			{
				$translation .= "var ".$handle." = {";

					$i = 1;

					foreach($arr_script['translation'] as $key => $value)
					{
						$translation .= "'".$key."': '".$value."'";

						if($i < $count_temp)
						{
							$translation .= ",";
						}

						$i++;
					}
				
				$translation .= "};";
			}

			$output .= get_file_content(array('file' => str_replace($file_url_base, $file_dir_base, $arr_script['file'])));
		}

		if($output != '' && $error == false)
		{
			list($upload_path, $upload_url) = get_uploads_folder("mf_theme_core/scripts");
			$file = "script.js";

			$output = compress_js($output);

			$success = set_file_content(array('file' => $upload_path.$file, 'mode' => 'w', 'content' => $output));

			if($success == true)
			{
				foreach($GLOBALS['mf_scripts'] as $handle => $arr_script)
				{
					wp_deregister_script($handle);
				}

				$version = int2point($version);

				wp_enqueue_script('mf_scripts', $upload_url.$file, array('jquery'), $version, true);

				if($translation != '')
				{
					echo "<script>".$translation."</script>";
				}
			}
		}
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
			mf_enqueue_style('style_theme_core_cookies', plugin_dir_url(__FILE__)."style_cookies.css", get_plugin_version(__FILE__));
			mf_enqueue_script('script_theme_core_cookies', plugin_dir_url(__FILE__)."script_cookies.js", array('plugin_url' => plugin_dir_url(__FILE__)), get_plugin_version(__FILE__));

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

	if(get_option('setting_splash_screen') == 'yes')
	{
		echo "<div id='overlay_splash'>
			<div>"
				.get_logo()
				."<div><i class='fa fa-spinner fa-spin'></i></div>"
			."</div>
			<i class='fa fa-arrow-circle-down'></i>
		</div>";
	}
}

function admin_bar_theme_core()
{
	global $wp_admin_bar;

	if(IS_ADMIN)
	{
		if(get_option('setting_no_public_pages') == 'yes')
		{
			$wp_admin_bar->remove_menu('site-name');

			$color = "color_red";

			$title = __("No public pages", 'lang_theme_core');
		}

		else if(get_option('setting_theme_core_login') == 'yes')
		{
			$title = "<a href='/' class='color_red'>".__("Requires login", 'lang_theme_core')."</a>";
		}

		else if(get_option('blog_public') == 0)
		{
			$title = "<a href='/' class='color_yellow'>".__("No index", 'lang_theme_core')."</a>";
		}

		else
		{
			$title = "<a href='/' class='color_green'>".__("Public", 'lang_theme_core')."</a>";
		}

		$wp_admin_bar->add_node(array(
			'id' => 'live',
			'title' => "<span".(isset($color) && $color != '' ? " class='".$color."'" : "").">".$title."</span>",
		));
	}
}

function init_theme_core()
{
	if(get_option('setting_responsiveness') == 1)
	{
		add_filter('post_thumbnail_html', 'remove_width_height_attribute', 10);
		add_filter('image_send_to_editor', 'remove_width_height_attribute', 10);

		add_filter('the_content', 'remove_width_height_attribute');
	}
}

function header_theme_core()
{
	require_user_login();

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

function mf_unregister_widget($id)
{
	/*$arr_exclude = array("WP_Widget_", "_");
	$arr_include = array("", "-");
	$id_check = strtolower(str_replace($arr_exclude, $arr_include, $id));

	$arr_sidebars = wp_get_sidebars_widgets();

	$is_used = false;

	foreach($arr_sidebars as $sidebar)
	{
		foreach($sidebar as $widget)
		{
			if(substr($widget, 0, (strlen($id_check) + 1)) == $id_check."-")
			{
				$is_used = true;
			}
		}
	}

	//if(is_active_widget(false, false, 'WP_Widget_Text', true) == false)
	if($is_used == false) //!in_array($id_check, $arr_sidebars)
	{*/
		unregister_widget($id);
	//}
}

function setup_theme_core()
{
	add_post_type_support('page', 'excerpt');

	remove_action('wp_head', 'wp_print_scripts');
	remove_action('wp_head', 'wp_print_head_scripts', 9);
	remove_action('wp_head', 'wp_enqueue_scripts', 1);
	add_action('wp_footer', 'wp_print_scripts', 5);
	add_action('wp_footer', 'wp_enqueue_scripts', 5);
	add_action('wp_footer', 'wp_print_head_scripts', 5);
}

function widgets_theme_core()
{
	register_widget('widget_theme_core_search');
	register_widget('widget_theme_core_news');
	mf_unregister_widget('WP_Widget_Recent_Posts');

	mf_unregister_widget('WP_Widget_Archives');
	mf_unregister_widget('WP_Widget_Calendar');
	mf_unregister_widget('WP_Widget_Categories');
	mf_unregister_widget('WP_Nav_Menu_Widget');
	mf_unregister_widget('WP_Widget_Links');
	mf_unregister_widget('WP_Widget_Meta');
	mf_unregister_widget('WP_Widget_Pages');
	mf_unregister_widget('WP_Widget_Recent_Comments');
	mf_unregister_widget('WP_Widget_RSS');
	mf_unregister_widget('WP_Widget_Search');
	mf_unregister_widget('WP_Widget_Tag_Cloud');

	if(function_exists('is_plugin_active') && is_plugin_active('black-studio-tinymce-widget/black-studio-tinymce-widget.php'))
	{
		mf_unregister_widget('WP_Widget_Text');
	}
}

function get_search_theme_core($data = array())
{
	if(!isset($data['placeholder']) || $data['placeholder'] == ''){		$data['placeholder'] = __("Search for", 'lang_theme_core');}

	return "<form action='".get_site_url()."' method='get' class='searchform mf_form'>"
		.show_textfield(array('name' => 's', 'value' => check_var('s'), 'placeholder' => $data['placeholder']))
		."<i class='fa fa-search'></i>"
	."</form>";
}

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