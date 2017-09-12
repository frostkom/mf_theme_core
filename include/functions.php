<?php

function cron_theme_core()
{
	global $wpdb;

	if(get_option('mf_database_optimized') < date("Y-m-d H:i:s", strtotime("-24 hour")))
	{
		$setting_theme_optimize = get_option_or_default('setting_theme_optimize', 12);

		//Remove old revisions and auto-drafts
		$wpdb->query("DELETE FROM ".$wpdb->posts." WHERE post_type IN ('revision', 'auto-draft') AND post_modified < DATE_SUB(NOW(), INTERVAL ".$setting_theme_optimize." MONTH)");

		//Remove orphan postmeta
		$wpdb->get_results("SELECT post_id FROM ".$wpdb->postmeta." LEFT JOIN ".$wpdb->posts." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id WHERE ".$wpdb->posts.".ID IS NULL");

		if($wpdb->num_rows > 0)
		{
			$wpdb->query("DELETE ".$wpdb->postmeta." FROM ".$wpdb->postmeta." LEFT JOIN ".$wpdb->posts." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id WHERE ".$wpdb->posts.".ID IS NULL");
		}

		//Remove duplicate postmeta
		$result = $wpdb->get_results("SELECT meta_id, COUNT(meta_id) AS count FROM ".$wpdb->postmeta." GROUP BY post_id, meta_key, meta_value HAVING count > 1");

		if($wpdb->num_rows > 0)
		{
			foreach($result as $r)
			{
				$intMetaID = $r->meta_id;

				$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->postmeta." WHERE meta_id = %d", $intMetaID));
			}
		}

		//Remove orphan relations
		$wpdb->get_results("SELECT * FROM ".$wpdb->term_relationships." WHERE term_taxonomy_id = 1 AND object_id NOT IN (SELECT ID FROM ".$wpdb->posts.")");

		if($wpdb->num_rows > 0)
		{
			do_log("Remove orphan relations: ".$wpdb->last_query);

			//$wpdb->query("DELETE FROM ".$wpdb->term_relationships." WHERE term_taxonomy_id = 1 AND object_id NOT IN (SELECT id FROM ".$wpdb->posts.")");
			//"SELECT COUNT(object_id) FROM ".$wpdb->term_relationships." AS tr INNER JOIN ".$wpdb->term_taxonomy." AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id WHERE tt.taxonomy != 'link_category' AND tr.object_id NOT IN (SELECT ID FROM ".$wpdb->posts.")"
		}

		//Remove orphan usermeta
		$wpdb->get_results("SELECT * FROM ".$wpdb->usermeta." WHERE user_id NOT IN (SELECT ID FROM ".$wpdb->users.")");

		if($wpdb->num_rows > 0)
		{
			do_log("Remove orphan usermeta: ".$wpdb->last_query);

			//$wpdb->query("DELETE FROM ".$wpdb->usermeta." WHERE user_id NOT IN (SELECT ID FROM ".$wpdb->users.")");
		}

		//Remove duplicate usermeta
		$result = $wpdb->get_results("SELECT umeta_id, COUNT(umeta_id) AS count FROM ".$wpdb->usermeta." GROUP BY user_id, meta_key, meta_value HAVING count > 1");

		if($wpdb->num_rows > 0)
		{
			foreach($result as $r)
			{
				$intMetaID = $r->umeta_id;

				$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->usermeta." WHERE umeta_id = %d", $intMetaID));
			}
		}

		//Pingbacks
		//"SELECT COUNT(*) FROM ".$wpdb->comments." WHERE comment_type = 'pingback'"

		//Trackbacks
		//"SELECT COUNT(*) FROM ".$wpdb->comments." WHERE comment_type = 'trackback'"

		//Spam comments
		//"SELECT COUNT(*) FROM ".$wpdb->comments." WHERE comment_approved = %s", "spam"

		//Duplicate comments
		//"SELECT COUNT(meta_id) AS count FROM ".$wpdb->commentmeta." GROUP BY comment_id, meta_key, meta_value HAVING count > %d", 1

		//oEmbed caches
		//"SELECT COUNT(meta_id) FROM ".$wpdb->postmeta." WHERE meta_key LIKE(%s)", "%_oembed_%"

		/*$wpdb->get_results("SELECT COUNT(*) as total, COUNT(case when option_value < NOW() then 1 end) as expired FROM ".$wpdb->options." WHERE (option_name LIKE '\_transient\_timeout\_%' OR option_name like '\_site\_transient\_timeout\_%')");

		if($wpdb->num_rows > 0)
		{
			do_log("Remove expired transients: ".$wpdb->last_query);
		}*/

		$result = $wpdb->get_results("SHOW TABLE STATUS");

		foreach($result as $r)
		{
			$strTableName = $r->Name;

			$wpdb->query("OPTIMIZE TABLE ".$strTableName);
		}

		//Can be removed later because the folder is not in use anymore
		list($upload_path, $upload_url) = get_uploads_folder('mf_theme_core');
		get_file_info(array('path' => $upload_path, 'callback' => "delete_files"));

		update_option('mf_database_optimized', date("Y-m-d H:i:s"));
	}
}

/*function init_theme_core()
{
	if(!is_admin())
	{
		if(isset($_REQUEST['action']) && ('posts_logout' == $_REQUEST['action']))
		{
			check_admin_referer('posts_logout');
			setcookie('wp-postpass_'.COOKIEHASH, '', strtotime("-1 month"), COOKIEPATH);

			do_log("Did remove cookie");

			wp_redirect(wp_get_referer());
			die();
		}
	}
}*/

function header_theme_core()
{
	require_user_login();

	if(!is_feed() && !get_query_var('sitemap') && get_option('setting_strip_domain') == 1)
	{
		ob_start("strip_domain_from_content");
	}
}

function head_theme_core()
{
	if(!(get_current_user_id() > 0))
	{
		wp_deregister_style('dashicons');
	}

	$plugin_include_url = plugin_dir_url(__FILE__);
	$plugin_version = get_plugin_version(__FILE__);

	mf_enqueue_style('style_theme_core', $plugin_include_url."style.css", $plugin_version);
	mf_enqueue_script('script_theme_core', $plugin_include_url."script.js", $plugin_version);

	if(get_option('setting_scroll_to_top') == 'yes')
	{
		mf_enqueue_style('style_theme_scroll', $plugin_include_url."style_scroll.css", $plugin_version);
		mf_enqueue_script('script_theme_scroll', $plugin_include_url."script_scroll.js", $plugin_version);
	}

	/*if(get_option('setting_html5_history') == 'yes')
	{
		mf_enqueue_style('style_theme_history', $plugin_include_url."style_history.css", $plugin_version);
		mf_enqueue_script('script_theme_history', $plugin_include_url."script_history.js", array('site_url' => get_site_url()), $plugin_version);
	}*/

	$meta_description = get_the_excerpt();

	if($meta_description != '')
	{
		echo "<meta name='description' content='".esc_attr($meta_description)."'>";
	}

	echo "<link rel='alternate' type='application/rss+xml' title='".get_bloginfo('name')."' href='".get_bloginfo('rss2_url')."'>";
}

function get_menu_type_for_select()
{
	return array(
		'' => "-- ".__("Choose here", 'lang_theme_core')." --",
		'main' => __("Main menu", 'lang_theme_core'),
		'secondary' => __("Secondary", 'lang_theme_core'),
		'both' => __("Main and Secondary menues", 'lang_theme_core'),
		'slide' => __("Slide in from right", 'lang_theme_core'),
	);
}

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

function password_form_theme_core()
{
	return "<form action='".site_url('wp-login.php?action=postpass', 'login_post')."' method='post' class='mf_form'>
		<p>".__("To view this protected post, enter the password below", 'lang_theme_core')."</p>"
		.show_password_field(array('name' => "post_password", 'placeholder' => __("Password", 'lang_theme_core'), 'maxlength' => 20))
		."<div class='form_button'>"
			.show_button(array('text' => __("Submit", 'lang_theme_core')))
		."</div>
	</form>";
}

function the_content_protected_theme_core($html)
{
	global $post, $done_text, $error_text;

    if(post_password_required())
	{
		if(!isset($post->post_password))
		{
			do_log("post_password did not exist even though it was a protected page");
		}

		$html = password_form_base();
	}

	/*if(isset($post->post_password) && $post->post_password != '')
	{
		$cookie_name = 'wp-postpass_'.COOKIEHASH;

		if(isset($_COOKIE[$cookie_name]) && wp_check_password($post->post_password, $_COOKIE[$cookie_name]))
		{
			$html .= "<form action='".wp_nonce_url(add_query_arg(array('action' => 'posts_logout'), site_url('wp-login.php', 'login')), 'posts_logout')."' method='post' class='mf_form'>
				<div class='form_button'>"
					.show_button(array('text' => __("Logout", 'lang_theme_core')))
				."</div>
			</form>";

			//$html .= var_export($_COOKIE, true).", ".$_COOKIE[$cookie_name];
		}
	}*/

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

function get_previous_backups_list($upload_path)
{
	global $globals;

	$globals['mf_theme_files'] = array();

	get_file_info(array('path' => $upload_path, 'callback' => "get_previous_backups"));

	$globals['mf_theme_files'] = array_sort(array('array' => $globals['mf_theme_files'], 'on' => 'time', 'order' => 'desc'));

	return $globals['mf_theme_files'];
}

function get_options_page_theme_core($data = array())
{
	global $done_text, $error_text;

	$out = "";

	$strFileUrl = check_var('strFileUrl');
	$strFileName = check_var('strFileName');
	$strFileContent = isset($_REQUEST['strFileContent']) ? $_REQUEST['strFileContent'] : "";

	list($upload_path, $upload_url) = get_uploads_folder($data['dir']);
	list($options_params, $options) = get_params();

	if(isset($_POST['btnThemeBackup']))
	{
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
		if($strFileUrl != '')
		{
			list($strFileContent, $headers) = get_url_content($strFileUrl, true);
		}

		else if($strFileName != '')
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
				$arr_ignore_key = explode_and_trim(",", get_option('setting_theme_ignore_style_on_restore'));

				foreach($json as $key => $value)
				{
					if(!in_array($key, $arr_ignore_key))
					{
						set_theme_mod($key, $value);
					}
				}

				$done_text = __("The restore was successful", 'lang_theme_core');

				update_option('mf_theme_saved', date("Y-m-d H:i:s"));
				update_option('theme_source_style_url', "");

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

		$done_text = __("The file was deleted successfully", 'lang_theme_core');
	}

	else
	{
		if($options['style_source'] != '')
		{
			$style_source = str_replace(array("http://", "https://"), "", trim($options['style_source'], "/"));

			$theme_source_style_url = get_option('theme_source_style_url');

			if($theme_source_style_url != '')
			{
				$error_text = sprintf(__("The theme at %s has got a newer version of saved style which can be %srestored here%s", 'lang_theme_core'), $style_source, "<a href='".admin_url("themes.php?page=theme_options&btnThemeRestore&strFileUrl=".$theme_source_style_url)."'>", "</a>");
			}
		}
	}

	$out .= "<div class='wrap'>
		<h2>".__("Theme Options", 'lang_theme_core')."</h2>"
		.get_notification();

		if($upload_path != '')
		{
			$style_source = trim($options['style_source'], "/");

			$out .= "<div id='poststuff'>
				<div id='post-body' class='columns-2'>
					<div id='post-body-content'>";

						$arr_backups = get_previous_backups_list($upload_path);
						$count_temp = count($arr_backups);

						if($count_temp > 0)
						{
							$style_source = trim($options['style_source'], "/");
							$is_allowed_to_backup = $style_source == '' || $style_source == get_site_url();

							$mf_theme_saved = get_option('mf_theme_saved');

							$out .= "<table class='widefat striped'>";

								$arr_header[] = __("Existing", 'lang_theme_core');
								$arr_header[] = __("Date", 'lang_theme_core');

								$out .= show_table_header($arr_header)
								."<tbody>";

									for($i = 0; $i < $count_temp; $i++)
									{
										$file_name = $arr_backups[$i]['name'];
										$file_time = date("Y-m-d H:i:s", $arr_backups[$i]['time']);

										$out .= "<tr".($style_source != get_site_url() && $file_time > $mf_theme_saved ? " class='green'" : "").">
											<td>"
												.$arr_backups[$i]['name']
												."<div class='row-actions'>
													<a href='".$upload_url.$file_name."'>".__("Download", 'lang_theme_core')."</a>
													 | <a href='".admin_url("themes.php?page=theme_options&btnThemeRestore&strFileName=".$file_name)."'>".__("Restore", 'lang_theme_core')."</a>";

													if($is_allowed_to_backup)
													{
														$out .= " | <a href='".admin_url("themes.php?page=theme_options&btnThemeDelete&strFileName=".$file_name)."' rel='confirm'>".__("Delete", 'lang_theme_core')."</a>";
													}

												$out .= "</div>
											</td>
											<td>".format_date($file_time)."</td>
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
					</div>";

					if($is_allowed_to_backup)
					{
						$out .= "<div id='postbox-container-1'>
							<div class='postbox'>
								<h3 class='hndle'><span>".__("New Backup", 'lang_theme_core')."</span></h3>
								<div class='inside'>
									<form method='post' action='' class='mf_form'>"
										.show_button(array('name' => "btnThemeBackup", 'text' => __("Save", 'lang_theme_core')))
									."</form>
								</div>
							</div>
						</div>";
					}

				$out .= "</div>
			</div>";
		}

		else if($error_text != '')
		{
			$out .= $error_text;
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
				mf_enqueue_style('style_font_'.$font, $options_fonts[$font]['url']);
			}
		}
	}
}

function check_htaccess_theme_core($data)
{
	if(basename($data['file']) == ".htaccess")
	{
		$content = get_file_content(array('file' => $data['file']));

		if(!preg_match("/BEGIN Theme Core/", $content) || !preg_match("/AddOutputFilterByType/", $content))
		{
			/*<IfModule mod_gzip.c>
				mod_gzip_on Yes
				mod_gzip_dechunk Yes
				mod_gzip_item_include file .(html?|txt|css|js|php|pl)$
				mod_gzip_item_include handler ^cgi-script$
				mod_gzip_item_include mime ^text/.*
				mod_gzip_item_include mime ^application/x-javascript.*
				mod_gzip_item_exclude mime ^image/.*
				mod_gzip_item_exclude rspheader ^Content-Encoding:.*gzip.*
			</IfModule>*/

			$recommend_htaccess = "# BEGIN Theme Core
<IfModule mod_expires.c>
	ExpiresActive On
	ExpiresDefault 'access plus 1 month'

	Header unset ETag
</IfModule>

FileETag None

AddOutputFilterByType DEFLATE text/html text/plain text/xml text/css text/javascript application/javascript image/jpeg image/gif image/png

<filesMatch '.(css|js|jpe?g|png|gif|ico)$'>
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

	$arr_settings['setting_no_public_pages'] = __("Always redirect visitors to the login page", 'lang_theme_core');

	if(get_option('setting_no_public_pages') != 'yes')
	{
		$arr_settings['setting_theme_core_login'] = __("Require login for public site", 'lang_theme_core');

		//$arr_settings['setting_html5_history'] = __("Use HTML5 History", 'lang_theme_core');

		/*$setting_html5_history = get_option('setting_html5_history');

		if($setting_html5_history == 'yes')
		{
			$arr_settings['setting_splash_screen'] = __("Show Splash Screen", 'lang_theme_core');
		}

		else
		{
			delete_option('setting_splash_screen');
		}*/

		$arr_settings['setting_scroll_to_top'] = __("Show scroll-to-top-link", 'lang_theme_core');

		/*if($setting_html5_history == 'yes')
		{
			//Relative URLs does not work in Chrome or IE when using pushState
			delete_option('setting_strip_domain');
		}

		else
		{*/
			$arr_settings['setting_strip_domain'] = __("Force relative URLs", 'lang_theme_core');
		//}

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
		$arr_settings['setting_theme_optimize'] = __("Optimize Database", 'lang_theme_core');
		$arr_settings['setting_theme_ignore_style_on_restore'] = __("Ignore Style on Restore", 'lang_theme_core');
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

/*function setting_html5_history_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option_or_default($setting_key, 'no');

	echo show_select(array('data' => get_yes_no_for_select(), 'name' => $setting_key, 'value' => $option));
}*/

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

function setting_strip_domain_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option_or_default($setting_key, 0);

	echo show_select(array('data' => get_yes_no_for_select(array('return_integer' => true)), 'name' => $setting_key, 'value' => $option));
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
	get_file_info(array('path' => get_home_path(), 'callback' => "check_htaccess_theme_core", 'allow_depth' => false));
}

function setting_theme_optimize_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option_or_default($setting_key, 12);

	echo show_textfield(array('type' => 'number', 'name' => $setting_key, 'value' => $option, 'suffix' => __("months", 'lang_theme_core')));
}

function setting_theme_ignore_style_on_restore_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	echo show_textfield(array('name' => $setting_key, 'value' => $option, 'placeholder' => "header_logo, header_mobile_logo, logo_color"));
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
			$result = $wpdb->get_results($wpdb->prepare("SELECT post_title, post_excerpt, post_type, post_name FROM ".$wpdb->posts." WHERE ID = '%d' LIMIT 0, 1", $id));

			foreach($result as $r)
			{
				$post_title = $r->post_title;
				$post_excerpt = $r->post_excerpt;
				$post_type = $r->post_type;
				$post_name = $r->post_name;

				$seo_type = '';

				if($seo_type == '')
				{
					if($post_excerpt != '')
					{
						$post_id_duplicate = $wpdb->get_var($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." WHERE post_excerpt = %s AND post_status = 'publish' AND post_type = %s AND ID != '%d' LIMIT 0, 1", $post_excerpt, $post_type, $id));

						if($post_id_duplicate > 0)
						{
							$seo_type = 'duplicate_excerpt';
						}
					}

					else
					{
						$seo_type = 'no_excerpt';
					}
				}

				if($seo_type == '')
				{
					if($post_title != '')
					{
						$post_id_duplicate = $wpdb->get_var($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." WHERE post_title = %s AND post_status = 'publish' AND post_type = %s AND ID != '%d' LIMIT 0, 1", $post_title, $post_type, $id));

						if($post_id_duplicate > 0)
						{
							$seo_type = 'duplicate_title';
						}
					}

					else
					{
						$seo_type = 'no_title';
					}
				}

				if($seo_type == '')
				{
					if($post_name != '')
					{
						if(sanitize_title_with_dashes(sanitize_title($post_title)) != $post_name)
						{
							$seo_type = 'inconsistent_url';
						}
					}
				}

				switch($seo_type)
				{
					case 'duplicate_title':
						echo "<i class='fa fa-lg fa-close red'></i>
						<div class='row-actions'>
							<a href='".admin_url("post.php?post=".$post_id_duplicate."&action=edit")."'>"
								.sprintf(__("The page %s have the exact same title. Please, try to not have duplicates because that will hurt your SEO.", 'lang_theme_core'), get_post_title($post_id_duplicate))
							."</a>
						</div>";
					break;

					case 'no_title':
						echo "<i class='fa fa-lg fa-close red'></i>
						<div class='row-actions'>"
							.__("You have not set a title for this page", 'lang_theme_core')
						."</div>";
					break;

					case 'duplicate_excerpt':
						echo "<i class='fa fa-lg fa-close red'></i>
						<div class='row-actions'>
							<a href='".admin_url("post.php?post=".$post_id_duplicate."&action=edit")."'>"
								.sprintf(__("The page %s have the exact same excerpt. Please, try to not have duplicates because that will hurt your SEO.", 'lang_theme_core'), get_post_title($post_id_duplicate))
							."</a>
						</div>";
					break;

					case 'no_excerpt':
						echo "<i class='fa fa-lg fa-close red'></i>
						<div class='row-actions'>"
							.__("You have not set an excerpt for this page", 'lang_theme_core')
						."</div>";
					break;

					case 'inconsistent_url':
						echo "<i class='fa fa-lg fa-warning yellow'></i>
						<div class='row-actions'>"
							.__("The URL is not directly correlated to the title. This might be due to a title change but the old URL has not been changed to relate to this change.", 'lang_theme_core')
						."</div>";
					break;

					default:
						echo "<i class='fa fa-lg fa-check green'></i>";
					break;
				}
			}
		break;
	}
}

function require_user_login()
{
	/*$blog_public = get_option('blog_public');

	if($blog_public == 0)
	{*/
		if(get_option('setting_no_public_pages') == 'yes')
		{
			mf_redirect(get_site_url()."/wp-admin/");
		}

		else if(get_option('setting_theme_core_login') == 'yes' && !is_user_logged_in())
		{
			mf_redirect(get_site_url()."/wp-login.php?redirect_to=".$_SERVER['REQUEST_URI']);
		}
	//}
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
		'url' => "http://fonts.googleapis.com/css?family=Droid+Sans"
	);

	$options_fonts[5] = array(
		'title' => "Droid Serif",
		'style' => "'Droid Serif', serif",
		'url' => "http://fonts.googleapis.com/css?family=Droid+Serif"
	);

	$options_fonts[1] = array(
		'title' => "Courgette",
		'style' => "'Courgette', cursive",
		'url' => "http://fonts.googleapis.com/css?family=Courgette"
	);

	$options_fonts[6] = array(
		'title' => "Garamond",
		'style' => "'EB Garamond', serif",
		'url' => "http://fonts.googleapis.com/css?family=EB+Garamond"
	);

	$options_fonts['lato'] = array(
		'title' => "Lato",
		'style' => "'Lato', sans-serif",
		'url' => "http://fonts.googleapis.com/css?family=Lato"
	);

	$options_fonts['montserrat'] = array(
		'title' => "Montserrat",
		'style' => "'Montserrat', sans-serif",
		'url' => "http://fonts.googleapis.com/css?family=Montserrat:400,700"
	);

	$options_fonts[2] = array(
		'title' => "Helvetica",
		'style' => "Helvetica, sans-serif",
		'url' => ""
	);

	$options_fonts[4] = array(
		'title' => "Open Sans",
		'style' => "'Open Sans', sans-serif",
		'url' => "http://fonts.googleapis.com/css?family=Open+Sans"
	);

	$options_fonts['oswald'] = array(
		'title' => "Oswald",
		'style' => "'Oswald', sans-serif",
		'url' => "http://fonts.googleapis.com/css?family=Oswald"
	);

	$options_fonts['oxygen'] = array(
		'title' => "Oxygen",
		'style' => "'Oxygen', sans-serif",
		'url' => "http://fonts.googleapis.com/css?family=Oxygen"
	);

	$options_fonts['roboto'] = array(
		'title' => "Roboto",
		'style' => "'Roboto', sans-serif",
		'url' => "http://fonts.googleapis.com/css?family=Roboto"
	);

	$options_fonts['roboto_condensed'] = array(
		'title' => "Roboto Condensed",
		'style' => "'Roboto Condensed', sans-serif",
		'url' => "http://fonts.googleapis.com/css?family=Roboto+Condensed"
	);

	$options_fonts['roboto_mono'] = array(
		'title' => "Roboto Mono",
		'style' => "'Roboto Mono', sans-serif",
		'url' => "http://fonts.googleapis.com/css?family=Roboto+Mono"
	);

	$options_fonts['sorts_mill_goudy'] = array(
		'title' => "Sorts Mill Goudy",
		'style' => "'sorts-mill-goudy',serif",
		'url' => "http://fonts.googleapis.com/css?family=Sorts+Mill+Goudy"
	);

	$options_fonts['source_sans_pro'] = array(
		'title' => "Source Sans Pro",
		'style' => "'Source Sans Pro', sans-serif",
		'url' => "http://fonts.googleapis.com/css?family=Source+Sans+Pro"
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

				if($param['type'] == 'align')
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

				else if($param['type'] == 'color')
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

				else if($param['type'] == 'checkbox')
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

				else if($param['type'] == 'clear')
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

				else if(in_array($param['type'], array('date', 'email', 'hidden', 'number', 'text', 'textarea', 'url')))
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

				else if($param['type'] == 'float')
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
								'center' => __("Center", 'lang_theme_core'),
								'right' => __("Right", 'lang_theme_core'),
								'initial' => __("Initial", 'lang_theme_core'),
								'inherit' => __("Inherit", 'lang_theme_core'),
							),
						)
					);
				}

				else if($param['type'] == 'font')
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

				else if($param['type'] == 'image')
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

				else if($param['type'] == 'overflow')
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

				else if($param['type'] == 'position')
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

				else if($param['type'] == 'text_transform')
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

				else if($param['type'] == 'weight')
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

	$prop = isset($data['property']) ? $data['property'] : '';
	$pre = isset($data['prefix']) ? $data['prefix'] : '';
	$suf = isset($data['suffix']) ? $data['suffix'] : '';
	$val = isset($data['value']) ? $data['value'] : '';
	$append = isset($data['append']) ? $data['append'] : '';

	if(is_array($val) && count($val) > 1)
	{
		$arr_val = $val;
		$val = $arr_val[0];
	}

	$out = '';

	if($prop == 'font-family' && (!isset($options[$val]) || !isset($options_fonts[$options[$val]]['style'])))
	{
		$options[$val] = '';
	}

	if($prop == 'float' && $options[$val] == 'center')
	{
		$prop = 'margin';
		$options[$val] = '0 auto';
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

			if($prop == 'font-family')
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

		if($append != '')
		{
			$out .= $append;
		}
	}

	else if(isset($arr_val) && count($arr_val) > 1)
	{
		array_splice($arr_val, 0, 1);

		$data['value'] = count($arr_val) > 1 ? $arr_val : $arr_val[0];

		$out .= render_css($data);
	}

	return $out;
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

function default_scripts_theme_core(&$scripts)
{
	$scripts->remove('jquery');
	$scripts->add('jquery', false, array('jquery-core'), '1.12.4');
}

function print_scripts_theme_core()
{
	wp_deregister_script('wp-embed');
}

function footer_theme_core()
{
	global $wpdb;

	if(!isset($_COOKIE['cookie_accepted']))
	{
		$setting_cookie_info = get_option('setting_cookie_info');

		if($setting_cookie_info > 0)
		{
			$plugin_include_url = plugin_dir_url(__FILE__);
			$plugin_version = get_plugin_version(__FILE__);

			mf_enqueue_style('style_theme_core_cookies', $plugin_include_url."style_cookies.css", $plugin_version);
			mf_enqueue_script('script_theme_core_cookies', $plugin_include_url."script_cookies.js", array('plugin_url' => $plugin_include_url), $plugin_version);

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
		$site_url = get_site_url();

		if(get_option('setting_no_public_pages') == 'yes')
		{
			$wp_admin_bar->remove_menu('site-name');

			$color = "color_red";

			$title = __("No public pages", 'lang_theme_core');
		}

		else if(get_option('setting_theme_core_login') == 'yes')
		{
			$title = "<a href='".$site_url."' class='color_red'>".__("Requires login", 'lang_theme_core')."</a>";
		}

		else if(get_option('blog_public') == 0)
		{
			$title = "<a href='".$site_url."' class='color_yellow'>".__("No index", 'lang_theme_core')."</a>";
		}

		else
		{
			$title = "<a href='".$site_url."' class='color_green'>".__("Public", 'lang_theme_core')."</a>";
		}

		$wp_admin_bar->add_node(array(
			'id' => 'live',
			'title' => "<span".(isset($color) && $color != '' ? " class='".$color."'" : "").">".$title."</span>",
		));
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
	register_widget('widget_theme_core_promo');
	mf_unregister_widget('WP_Widget_Recent_Posts');

	mf_unregister_widget('WP_Widget_Archives');
	mf_unregister_widget('WP_Widget_Calendar');
	mf_unregister_widget('WP_Widget_Categories');
	//mf_unregister_widget('WP_Nav_Menu_Widget');
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
	if(!isset($data['animate']) || $data['animate'] == ''){				$data['animate'] = 'yes';}

	return "<form action='".get_site_url()."' method='get' class='searchform mf_form".($data['animate'] == 'yes' ? " search_animate" : "")."'>"
		.show_textfield(array('name' => 's', 'value' => check_var('s'), 'placeholder' => $data['placeholder']))
		."<i class='fa fa-search'></i>"
	."</form>";
}

function strip_domain_from_content($html)
{
	$site_url = get_option('siteurl');
	$site_url_alt = (substr($site_url, 0, 5) == "https" ? str_replace("https:", "http:", $site_url) : str_replace("http:", "https:", $site_url));

	return str_replace(array($site_url, $site_url_alt), "", $html);
}