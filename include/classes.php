<?php

class mf_theme_core
{
	function __construct()
	{
		$this->meta_prefix = "mf_theme_core_";

		$this->options_params = $this->options = $this->options_fonts = array();
	}

	function is_login_page()
	{
		return in_array($GLOBALS['pagenow'], array('wp-login.php', 'wp-register.php'));
	}

	function cron_base()
	{
		global $wpdb;

		$this->unpublish_posts();

		/* Optimize */
		#########################
		//$setting_theme_optimize = get_option_or_default('setting_theme_optimize', 7);

		if(get_option('option_database_optimized') < date("Y-m-d H:i:s", strtotime("-7 day")))
		{
			$this->do_optimize();
		}
		#########################

		$this->check_style_source();

		/* Delete old uploads */
		#######################
		$theme_dir_name = get_theme_dir_name();

		if($theme_dir_name != '')
		{
			list($upload_path, $upload_url) = get_uploads_folder($theme_dir_name);

			get_file_info(array('path' => $upload_path, 'callback' => 'delete_files', 'time_limit' => (60 * 60 * 24 * 60))); //60 days
		}
		#######################

		/* Set default meta boxes */
		#######################
		/*$page = 'post';

		$users = get_users(array('fields' => array('ID')));

		foreach($users as $user)
		{
			$hidden_default = $hidden = get_user_option('metaboxhidden_'.$page, $user->ID);

			if(is_array($hidden))
			{
				$hidden = array_diff($hidden, array('postexcerpt'));

				if($hidden != $hidden_default)
				{
					update_user_option($user->ID, 'metaboxhidden_'.$page, $hidden, true);
				}
			}

			else
			{
				$hidden = array('slugdiv', 'trackbacksdiv', 'postcustom', 'commentstatusdiv', 'commentsdiv', 'authordiv', 'revisionsdiv');
				update_user_option($user->ID, 'metaboxhidden_'.$page, $hidden, true);
			}
		}*/
		#######################
	}

	function init()
	{
		if(get_option('setting_activate_maintenance') == 'yes')
		{
			if(IS_SUPER_ADMIN || $this->is_login_page()) //is_admin()
			{
				// Do nothing
			}

			else
			{
				$option_ms = get_option('setting_maintenance_page');

				if($option_ms > 0)
				{
					$post_title = get_the_title($option_ms);
					$post_content = mf_get_post_content($option_ms);

					$out = "";

					if($post_title != '')
					{
						$out .= "<h1>".$post_title."</h1>";
					}

					$out .= "<p>".$post_content."</p>";

					wp_die($out);
				}
			}
		}

		/*if(!is_admin())
		{
			if(isset($_REQUEST['action']) && ('posts_logout' == $_REQUEST['action']))
			{
				check_admin_referer('posts_logout');
				setcookie('wp-postpass_'.COOKIEHASH, '', strtotime("-1 month"), COOKIEPATH);

				do_log("Did remove cookie");

				wp_redirect(wp_get_referer());
				die();
			}
		}*/
	}

	function wp_before_admin_bar_render()
	{
		global $wp_admin_bar;

		if(IS_ADMIN)
		{
			$site_url = get_site_url();

			if(get_option('setting_activate_maintenance') == 'yes')
			{
				$color = "color_red";

				$title = __("Maintenance Mode Activated", 'lang_theme_core');
			}

			else if(get_option('setting_no_public_pages') == 'yes')
			{
				$wp_admin_bar->remove_menu('site-name');

				$color = "color_red";

				$title = __("No Public Pages", 'lang_theme_core');
			}

			else if(get_option('setting_theme_core_login') == 'yes')
			{
				$title = "<a href='".$site_url."' class='color_red'>".__("Requires Login", 'lang_theme_core')."</a>";
			}

			else if(get_option('blog_public') == 0)
			{
				$title = "<a href='".$site_url."' class='color_yellow'>".__("No Index", 'lang_theme_core')."</a>";
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

	function settings_theme_core()
	{
		$options_area = __FUNCTION__;

		add_settings_section($options_area, "", array($this, $options_area."_callback"), BASE_OPTIONS_PAGE);

		$arr_settings = array();

		$arr_settings['setting_no_public_pages'] = __("Always redirect visitors to the login page", 'lang_theme_core');

		if(get_option('setting_no_public_pages') != 'yes')
		{
			$arr_settings['setting_theme_core_login'] = __("Require login for public site", 'lang_theme_core');
		}

		$arr_settings['setting_theme_core_hidden_meta_boxes'] = __("Hidden Meta Boxes", 'lang_theme_core');

		if(get_option('setting_no_public_pages') != 'yes')
		{
			$arr_data = array();
			get_post_children(array('post_type' => 'post'), $arr_data);

			if(count($arr_data) > 0)
			{
				$arr_settings['setting_display_post_meta'] = __("Display Post Meta", 'lang_theme_core');
				$arr_settings['default_comment_status'] = __("Allow Comments", 'lang_theme_core');
			}

			else
			{
				delete_option('setting_display_post_meta');
			}

			$arr_settings['setting_scroll_to_top'] = __("Display scroll-to-top-link", 'lang_theme_core');

			if(is_plugin_active("mf_analytics/index.php"))
			{
				$arr_settings['setting_cookie_info'] = __("Cookie information", 'lang_theme_core');
			}

			else
			{
				delete_option('setting_cookie_info');
			}

			$arr_settings['setting_404_page'] = __("404 Page", 'lang_theme_core');
		}

		$arr_settings['setting_maintenance_page'] = __("Maintenance Page", 'lang_theme_core');

		if(IS_SUPER_ADMIN && get_option('setting_maintenance_page') > 0)
		{
			$arr_settings['setting_activate_maintenance'] = __("Activate Maintenance Mode", 'lang_theme_core');
		}

		if(get_option('setting_no_public_pages') != 'yes')
		{
			$users_editors = get_users(array(
				'fields' => array('ID'),
				'role__in' => array('editor'),
			));

			$users_authors = get_users(array(
				'fields' => array('ID'),
				'role__in' => array('author'),
			));

			if(count($users_editors) > 0 && count($users_authors) > 0)
			{
				$arr_settings['setting_send_email_on_draft'] = __("Send Email when Draft is Saved", 'lang_theme_core');
			}

			else
			{
				delete_option('setting_send_email_on_draft');
			}

			$this->get_params();

			if($this->options['style_source'] != '')
			{
				$arr_settings['setting_theme_ignore_style_on_restore'] = __("Ignore Style on Restore", 'lang_theme_core');
			}

			else
			{
				delete_option('setting_theme_ignore_style_on_restore');
			}

			if(is_plugin_active('css-hero-ce/css-hero-main.php'))
			{
				$arr_settings['setting_theme_css_hero'] = __("CSS Hero Support", 'lang_theme_core');
			}

			else
			{
				delete_option('setting_theme_css_hero');
			}
		}

		$arr_settings['setting_theme_optimize'] = __("Optimize", 'lang_theme_core');

		show_settings_fields(array('area' => $options_area, 'object' => $this, 'settings' => $arr_settings));
	}

	function settings_theme_core_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);

		echo settings_header($setting_key, __("Theme", 'lang_theme_core'));
	}

	function setting_no_public_pages_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);
		$option = get_option_or_default($setting_key, 'no');

		echo show_select(array('data' => get_yes_no_for_select(), 'name' => $setting_key, 'value' => $option));
	}

	function setting_theme_core_login_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);
		$option = get_option_or_default($setting_key, 'no');

		echo show_select(array('data' => get_yes_no_for_select(), 'name' => $setting_key, 'value' => $option));
	}

	function get_meta_boxes_for_select()
	{
		return array(
			'authordiv' => __("Author", 'lang_theme_core'),
			'categorydiv' => __("Categories", 'lang_theme_core'),
			'commentstatusdiv' => __("Discussion", 'lang_theme_core'),
			'commentsdiv' => __("Comments", 'lang_theme_core'),
			'pageparentdiv' => __("Page Attributes", 'lang_theme_core'),
			'postcustom' => __("Custom Fields", 'lang_theme_core'),
			'postexcerpt' => __("Excerpt", 'lang_theme_core'),
			'postimagediv' => __("Featured Image", 'lang_theme_core'),
			'revisionsdiv' => __("Revisions", 'lang_theme_core'),
			'slugdiv' => __("Slug", 'lang_theme_core'),
			'tagsdiv-post_tag' => __("Tags", 'lang_theme_core'),
			'trackbacksdiv' => __("Trackbacks", 'lang_theme_core'),
		); //'formatdiv',  'tagsdiv',
	}

	function setting_theme_core_hidden_meta_boxes_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);
		$option = get_option($setting_key, array('authordiv', 'commentstatusdiv', 'commentsdiv', 'postcustom', 'revisionsdiv', 'slugdiv', 'trackbacksdiv'));

		echo show_select(array('data' => $this->get_meta_boxes_for_select(), 'name' => $setting_key."[]", 'value' => $option));
	}

	function setting_display_post_meta_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);
		$option = get_option_or_default($setting_key, array('time'));

		$arr_data = array(
			'time' => __("Time", 'lang_theme_core'),
			'author' => __("Author", 'lang_theme_core'),
			'category' => __("Category", 'lang_theme_core'),
		);

		echo show_select(array('data' => $arr_data, 'name' => $setting_key."[]", 'value' => $option));
	}

	function get_comment_status_amount($status)
	{
		global $wpdb;

		$wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." WHERE post_type = 'post' AND comment_status = %s LIMIT 0, 1", $status));

		return $wpdb->num_rows;
	}

	function get_comment_status_for_select($option)
	{
		global $wpdb;

		$arr_data = array();

		$arr_data['open'] = __("Yes", 'lang_theme_core');

		if($this->get_comment_status_amount('closed') > 0)
		{
			$arr_data['open_all'] = __("Yes", 'lang_theme_core')." (".__("And Change Setting on All Posts", 'lang_theme_core').")";
		}

		$arr_data['closed'] = __("No", 'lang_theme_core');

		if($this->get_comment_status_amount('open') > 0)
		{
			$arr_data['closed_all'] = __("No", 'lang_theme_core')." (".__("And Change Setting on All Posts", 'lang_theme_core').")";
		}

		return $arr_data;
	}

	function default_comment_status_callback()
	{
		global $wpdb;

		$setting_key = get_setting_key(__FUNCTION__);
		$option = get_option($setting_key);

		if(in_array($option, array('open_all', 'closed_all')))
		{
			$option = str_replace('_all', '', $option);

			$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->posts." SET comment_status = %s WHERE post_type = 'post' AND comment_status != %s", $option, $option));

			update_option($setting_key, $option, 'no');
		}

		echo show_select(array('data' => $this->get_comment_status_for_select($option), 'name' => $setting_key, 'value' => $option));
	}

	function setting_scroll_to_top_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);
		$option = get_option_or_default($setting_key, 'yes');

		echo show_select(array('data' => get_yes_no_for_select(), 'name' => $setting_key, 'value' => $option));
	}

	function setting_cookie_info_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);
		$option = get_option($setting_key);

		$arr_data = array();
		get_post_children(array('add_choose_here' => true), $arr_data);

		echo show_select(array('data' => $arr_data, 'name' => $setting_key, 'value' => $option, 'suffix' => get_option_page_suffix(array('value' => $option)), 'description' => __("The content from this page will be displayed on top of the page until the visitor clicks to accept the use of cookies", 'lang_theme_core')));
	}

	function setting_404_page_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);
		$option = get_option($setting_key);

		$arr_data = array();
		get_post_children(array('add_choose_here' => true), $arr_data);

		$post_title = __("404", 'lang_theme_core');
		$post_content = __("Oops! The page that you were looking for does not seam to exist. If you think that it should exist, please let us know.", 'lang_theme_core');

		echo show_select(array('data' => $arr_data, 'name' => $setting_key, 'value' => $option, 'suffix' => get_option_page_suffix(array('value' => $option, 'title' => $post_title, 'content' => $post_content)), 'description' => (!($option > 0) ? "<span class='display_warning'><i class='fa fa-exclamation-triangle yellow'></i></span> " : "").__("This page will be displayed instead of the default 404 page", 'lang_theme_core')));
	}

	function setting_maintenance_page_callback()
	{
		global $wpdb, $done_text, $error_text;

		$setting_key = get_setting_key(__FUNCTION__);
		$option = get_option($setting_key);
		$option_temp = get_option($setting_key.'_temp');

		$arr_data = array();
		get_post_children(array('add_choose_here' => true), $arr_data);

		$post_title = __("Temporary Maintenance", 'lang_theme_core');
		$post_content = __("This site is undergoing maintenance. This usually takes less than a minute so you have been unfortunate to come to the site at this moment. If you reload the page in just a while it will surely be back as usual.", 'lang_theme_core');

		echo show_select(array('data' => $arr_data, 'name' => $setting_key, 'value' => $option, 'suffix' => get_option_page_suffix(array('value' => $option, 'title' => $post_title, 'content' => $post_content)), 'description' => (!($option > 0) ? "<span class='display_warning'><i class='fa fa-exclamation-triangle yellow'></i></span> " : "").__("This page will be displayed when the website is updating", 'lang_theme_core')));

		if($option > 0 && $option != $option_temp)
		{
			$maintenance_file = ABSPATH."wp-content/maintenance.php";

			if(!file_exists($maintenance_file) || is_writeable($maintenance_file))
			{
				list($upload_path, $upload_url) = get_uploads_folder('mf_cache', true);
				$maintenance_template = str_replace("mf_theme_core/include", "mf_theme_core/templates/", dirname(__FILE__))."maintenance.php";

				$recommend_maintenance = get_file_content(array('file' => $maintenance_template));

				if(is_multisite())
				{
					$loop_template = get_match("/\#\#\#\#\#\#\#\#\#\#\#\#\#\#\#\#\#\#\#\#\#\#\#\#\#(.*)\#\#\#\#\#\#\#\#\#\#\#\#\#\#\#\#\#\#\#\#\#\#\#\#\#/s", $recommend_maintenance, false);

					$result = get_sites(array('deleted' => 0, 'order' => 'DESC'));

					foreach($result as $r)
					{
						$blog_id = $r->blog_id;

						switch_to_blog($blog_id);

						$loop_template_temp = $loop_template;

						$option_ms = get_option('setting_maintenance_page');

						if($option_ms > 0)
						{
							$site_url = get_site_url();
							$site_url_clean = remove_protocol(array('url' => $site_url));
							$post_url_clean = remove_protocol(array('url' => get_permalink($option_ms), 'clean' => true));
							$post_title = get_the_title($option_ms);
							$post_content = mf_get_post_content($option_ms);

							if($post_url_clean != '' && $post_content != '')
							{
								$loop_template_temp = str_replace("[site_url]", $site_url_clean, $loop_template_temp);
								$loop_template_temp = str_replace("[post_dir]", $upload_path.$post_url_clean."index.html", $loop_template_temp);
								$loop_template_temp = str_replace("[post_title]", $post_title, $loop_template_temp);
								$loop_template_temp = str_replace("[post_content]", trim(apply_filters('the_content', $post_content)), $loop_template_temp);

								$recommend_maintenance .= "\n".$loop_template_temp;
							}
						}

						restore_current_blog();
					}
				}

				else
				{
					$site_url = get_site_url();
					$site_url_clean = remove_protocol(array('url' => $site_url));
					$post_url_clean = remove_protocol(array('url' => get_permalink($option), 'clean' => true));
					$post_title = get_the_title($option);
					$post_content = mf_get_post_content($option);

					if($post_url_clean != '' && $post_content != '')
					{
						$recommend_maintenance = str_replace("[site_url]", $site_url_clean, $recommend_maintenance);
						$recommend_maintenance = str_replace("[post_dir]", $upload_path.$post_url_clean."index.html", $recommend_maintenance);
						$recommend_maintenance = str_replace("[post_title]", $post_title, $recommend_maintenance);
						$recommend_maintenance = str_replace("[post_content]", apply_filters('the_content', $post_content), $recommend_maintenance);
					}

					/*else
					{
						$error_text = __("The page that you choose for Maintenance has to be published and contain a title and content", 'lang_theme_core');
					}*/
				}

				if(strlen($recommend_maintenance) > 0)
				{
					$success = set_file_content(array('file' => $maintenance_file, 'mode' => 'w', 'content' => trim($recommend_maintenance)));

					if($success == true)
					{
						//$done_text = __("I saved the maintenance page for you", 'lang_theme_core');

						update_option($setting_key.'_temp', $option, 'no');
					}

					else
					{
						$error_text = sprintf(__("I could not write to %s. The file is writeable but the write was unsuccessful", 'lang_theme_core'), $maintenance_file);
					}
				}

				else
				{
					$error_text = sprintf(__("The content that I was about to write to %s was empty and the template came from %s", 'lang_theme_core'), $maintenance_file, $maintenance_template);
				}
			}

			else
			{
				$error_text = sprintf(__("I could not write to %s. Please, make sure that this is writeable if you want this functionality to work properly", 'lang_theme_core'), $maintenance_file);
			}

			echo get_notification();
		}
	}

	function setting_activate_maintenance_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);
		$option = get_option_or_default($setting_key, 'no');

		echo show_select(array('data' => get_yes_no_for_select(), 'name' => $setting_key, 'value' => $option, 'description' => __("This will display the maintenance message to everyone except you as a superadmin, until you inactivate this mode again", 'lang_theme_core')));
	}

	function setting_send_email_on_draft_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);
		$option = get_option_or_default($setting_key, 'no');

		$editors = "";

		$users = get_users(array(
			'fields' => array('display_name'),
			'role__in' => array('editor'),
		));

		foreach($users as $user)
		{
			$editors .= ($editors != '' ? "" : "").$user->display_name;
		}

		echo show_select(array('data' => get_yes_no_for_select(), 'name' => $setting_key, 'value' => $option, 'suffix' => sprintf(__("This will send an e-mail to all editors (%s) when an author saves a draft", 'lang_theme_core'), $editors)));
	}

	function setting_theme_optimize_callback()
	{
		/*$setting_key = get_setting_key(__FUNCTION__);
		$option = get_option_or_default($setting_key, 7);*/

		$option_database_optimized = get_option('option_database_optimized');

		if($option_database_optimized > DEFAULT_DATE)
		{
			$populate_next = format_date(date("Y-m-d H:i:s", strtotime($option_database_optimized." +7 day")));

			$description = sprintf(__("The optimization was last run %s and will be run again %s", 'lang_theme_core'), format_date($option_database_optimized), $populate_next);
		}

		else
		{
			$description = sprintf(__("The optimization has not been run yet but will be %s", 'lang_theme_core'), get_next_cron());
		}

		//echo show_textfield(array('type' => 'number', 'name' => $setting_key, 'value' => $option, 'xtra' => "min='1' max='30'", 'suffix' => __("days", 'lang_theme_core'), 'description' => $description));

		echo "<div>"
			.show_button(array('type' => 'button', 'name' => 'btnOptimizeTheme', 'text' => __("Optimize Now", 'lang_theme_core'), 'class' => 'button-secondary'))
			."<p class='italic'>".$description."</p>"
		."</div>
		<div id='optimize_debug'></div>";
	}

	function setting_theme_ignore_style_on_restore_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);
		$option = get_option($setting_key);

		if(!is_array($option))
		{
			$option = array_map('trim', explode(",", $option));
		}

		echo show_select(array('data' => get_params_for_select(), 'name' => $setting_key."[]", 'value' => $option));
	}

	function setting_theme_css_hero_callback()
	{
		$css_hero_key = 'wpcss_quick_config_settings_'.get_theme_slug();

		$setting_key = get_setting_key(__FUNCTION__);
		$option = get_option_or_default($setting_key, get_option($css_hero_key));

		if($option != '')
		{
			echo show_textarea(array('name' => $setting_key, 'value' => $option, 'placeholder' => "#site_logo, #main", 'description' => sprintf(__("By going to %sthe site%s you can edit any styling to your liking", 'lang_theme_core'), "<a href='".get_site_url()."?csshero_action=edit_page'>", "</a>")));
		}

		else
		{
			$option = "";

			//echo __("I have generated a list of selectors to use. Please reload the page for further instructions", 'lang_theme_core');
		}

		update_option($css_hero_key, $option);
	}

	function admin_init()
	{
		global $pagenow;

		if($pagenow == 'options-general.php' && check_var('page') == 'settings_mf_base')
		{
			$plugin_include_url = plugin_dir_url(__FILE__);
			$plugin_version = get_plugin_version(__FILE__);

			mf_enqueue_script('script_theme_core', $plugin_include_url."script_wp.js", array('plugin_url' => $plugin_include_url, 'ajax_url' => admin_url('admin-ajax.php')), $plugin_version);
		}
	}

	function upload_mimes($existing_mimes = array())
	{
		$existing_mimes['eot'] = 'application/vnd.ms-fontobject';
		$existing_mimes['ttf'] = 'application/x-font-ttf';
		$existing_mimes['woff'] = 'application/octet-stream';
		$existing_mimes['svg'] = 'image/svg+xmln';

		return $existing_mimes;
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

	function get_header()
	{
		$this->require_user_login();
	}

	function wp_head()
	{
		global $wpdb, $post;

		$plugin_include_url = plugin_dir_url(__FILE__);
		$plugin_version = get_plugin_version(__FILE__);

		mf_enqueue_style('style_theme_core', $plugin_include_url."style.css", $plugin_version);
		mf_enqueue_script('script_theme_core', $plugin_include_url."script.js", $plugin_version);

		if(get_option('setting_scroll_to_top') == 'yes')
		{
			mf_enqueue_style('style_theme_scroll', $plugin_include_url."style_scroll.css", $plugin_version);
			mf_enqueue_script('script_theme_scroll', $plugin_include_url."script_scroll.js", $plugin_version);
		}

		echo "<meta charset='".get_bloginfo('charset')."'>
		<meta name='viewport' content='width=device-width, initial-scale=1, viewport-fit=cover'>
		<meta name='author' content='frostkom.se'>
		<title>".$this->get_wp_title()."</title>";

		if(!(get_current_user_id() > 0))
		{
			wp_deregister_style('dashicons');
		}

		$this->add_page_index();

		$meta_description = get_the_excerpt();

		if($meta_description != '')
		{
			echo "<meta name='description' content='".esc_attr($meta_description)."'>";
		}

		echo "<link rel='alternate' type='application/rss+xml' title='".get_bloginfo('name')."' href='".get_bloginfo('rss2_url')."'>";

		echo "<meta property='og:site_name' content='".get_bloginfo('name')."'>";

		if(isset($post->ID))
		{
			echo "<meta property='og:title' content='".$post->post_title."'>
			<meta property='og:url' content='".get_permalink($post)."'>";

			if(has_post_thumbnail($post->ID))
			{
				echo "<meta property='og:image' content='".get_the_post_thumbnail_url($post->ID, 'thumbnail')."'>";
			}

			if(isset($post->post_excerpt) && $post->post_excerpt != '')
			{
				echo "<meta property='og:description' content='".$post->post_excerpt."'>";
			}

			//echo "<meta property='og:type' content='article'>";
		}

		$this->footer_output = '';

		if(!isset($_COOKIE['cookie_accepted']))
		{
			$setting_cookie_info = get_option('setting_cookie_info');

			if($setting_cookie_info > 0)
			{
				mf_enqueue_style('style_theme_core_cookies', $plugin_include_url."style_cookies.css", $plugin_version);
				mf_enqueue_script('script_theme_core_cookies', $plugin_include_url."script_cookies.js", array('plugin_url' => $plugin_include_url), $plugin_version);

				$result = $wpdb->get_results($wpdb->prepare("SELECT ID, post_title, post_excerpt, post_content FROM ".$wpdb->posts." WHERE ID = '%d' AND post_type = 'page' AND post_status = 'publish'", $setting_cookie_info));

				foreach($result as $r)
				{
					$post_id = $r->ID;
					$post_title = $r->post_title;
					$post_excerpt = $r->post_excerpt;
					$post_content = apply_filters('the_content', $r->post_content);

					$this->footer_output .= "<div id='accept_cookies'>
						<div>
							<i class='fa fa-gavel red'></i>";

							$buttons = "<a href='#accept_cookie' class='button'><i class='fa fa-check green'></i>".__("Accept", 'lang_theme_core')."</a>";

							if($post_excerpt != '')
							{
								$this->footer_output .= "<p>"
									.$post_excerpt
								."</p>";

								if($post_content != '' && $post_content != $post_excerpt)
								{
									$buttons .= " <a href='".get_permalink($post_id)."' class='button' rel='external'>".__("Read More", 'lang_theme_core')."</a>";
								}

								$this->footer_output .= "<div class='form_button'>".$buttons."</div>";
							}

							else
							{
								$this->footer_output .= $post_content
								."<div class='form_button'>".$buttons."</div>";
							}

						$this->footer_output .= "</div>
					</div>";
				}
			}
		}

		/*if(get_option('setting_splash_screen') == 'yes')
		{
			$this->footer_output .= "<div id='overlay_splash'>
				<div>"
					.$this->get_logo()
					."<div><i class='fa fa-spinner fa-spin'></i></div>"
				."</div>
				<i class='fa fa-arrow-circle-down'></i>
			</div>";
		}*/

		if(get_option('setting_theme_core_login') == 'yes')
		{
			if(get_current_user_id() > 0)
			{
				mf_enqueue_style('style_theme_core_locked', $plugin_include_url."style_locked.css", $plugin_version);

				$this->footer_output .= "<a href='".admin_url()."' id='site_locked'><i class='fa fa-lock'></i></a>";
			}

			else
			{
				do_log(__("A visitor accessed the public page without being logged in!", 'lang_theme_core'));
			}
		}
	}

	function body_class($classes)
	{
		$classes[] = "is_site";

		return $classes;
	}

	function embed_oembed_html($cached_html, $url, $attr, $post_id)
	{
		return "<div class='embed_content'>".$cached_html."</div>";
	}

	function wp_nav_menu_args($args)
	{
		if(isset($args['container_override']) && $args['container_override'] == false){}

		else if(!isset($args['container']) || $args['container'] == '' || $args['container'] == 'div')
		{
			$args['container'] = "nav";
		}

		return $args;
	}

	function get_search_form($html)
	{
		return "<form method='get' action='".esc_url(home_url('/'))."' class='mf_form'>"
			.show_textfield(array('type' => 'search', 'name' => 's', 'value' => get_search_query(), 'placeholder' => __("Search here", 'lang_theme_core'), 'xtra' => " autocomplete='off'"))
			."<div class='form_button'>"
				.show_button(array('text' => __("Search", 'lang_theme_core')))
			."</div>
		</form>";
	}

	function the_password_form()
	{
		return "<form action='".site_url('wp-login.php?action=postpass', 'login_post')."' method='post' class='mf_form'>
			<p>".__("To view this protected post, enter the password below", 'lang_theme_core')."</p>"
			.show_password_field(array('name' => "post_password", 'placeholder' => __("Password", 'lang_theme_core'), 'maxlength' => 20))
			."<div class='form_button'>"
				.show_button(array('text' => __("Submit", 'lang_theme_core')))
			."</div>
		</form>";
	}

	function the_content($html)
	{
		global $post;

		if(post_password_required())
		{
			if(!isset($post->post_password))
			{
				do_log("post_password did not exist even though it was a protected page");
			}

			$html = $this->the_password_form();
		}

		/*global $done_text, $error_text;

		if(isset($post->post_password) && $post->post_password != '')
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

	function the_content_meta($html, $post)
	{
		if(isset($post->post_type) && $post->post_type == 'post')
		{
			$setting_display_post_meta = get_option_or_default('setting_display_post_meta', array('time'));

			if(in_array('time', $setting_display_post_meta))
			{
				$html .= "<time datetime='".$post->post_date."'>".format_date($post->post_date)."</time>";
			}

			if(in_array('author', $setting_display_post_meta))
			{
				$html .= "<span>".sprintf(__("by %s", 'lang_theme_core'), get_user_info(array('id' => $post->post_author)))."</span>";
			}

			if(in_array('category', $setting_display_post_meta))
			{
				$arr_categories = get_the_category($post->ID);

				if(is_array($arr_categories) && count($arr_categories) > 0)
				{
					$category_base_url = get_site_url()."/category/";

					foreach($arr_categories as $category)
					{
						$html .= "<a href='".$category_base_url.$category->slug."'>".$category->name."</a>";
					}
				}
			}
		}

		return $html;
	}

	function wp_default_scripts(&$scripts)
	{
		$scripts->remove('jquery');
		$scripts->add('jquery', false, array('jquery-core'), '1.12.4');
	}

	function wp_print_scripts()
	{
		wp_deregister_script('wp-embed');
	}

	function wp_footer()
	{
		if(isset($this->footer_output) && $this->footer_output != '')
		{
			echo $this->footer_output;
		}
	}

	function has_noindex($post_id)
	{
		$page_index = get_post_meta($post_id, $this->meta_prefix.'page_index', true);

		return $page_index != '' && in_array($page_index, array('noindex', 'none'));
	}

	function get_public_post_types()
	{
		$this->arr_post_types = array();

		foreach(get_post_types(array('public' => true, 'exclude_from_search' => false), 'names') as $post_type)
		{
			if($post_type != 'attachment')
			{
				get_post_children(array(
					'post_type' => $post_type,
					'where' => "post_password = ''",
					//'debug' => true,
				), $this->arr_post_types);
			}
		}
	}

	function get_public_posts($data = array())
	{
		if(!isset($data['allow_noindex'])){		$data['allow_noindex'] = false;}

		$this->arr_public_posts = array();

		if(!isset($this->arr_post_types) || count($this->arr_post_types) == 0)
		{
			$this->get_public_post_types();
		}

		foreach($this->arr_post_types as $post_id => $post_title)
		{
			if($data['allow_noindex'] == false && $this->has_noindex($post_id) || post_password_required($post_id)){}

			else
			{
				$this->arr_public_posts[$post_id] = $post_title;
			}
		}
	}

	function display_featured_image($post_id)
	{
		$post_meta = get_post_meta($post_id, $this->meta_prefix.'display_featured_image', true);

		return ($post_meta != 'no');
	}

	// Style
	#########################
	function gather_params($options_params)
	{
		$options = array();

		$arr_theme_mods = get_theme_mods();

		foreach($options_params as $param_key => $param)
		{
			if(!isset($param['category']) && !isset($param['category_end']))
			{
				$id = $param['id'];
				$default = isset($param['default']) ? $param['default'] : false;
				$force_default = isset($param['force_default']) ? $param['force_default'] : false;
				$value_old = isset($arr_theme_mods[$id]) ? $arr_theme_mods[$id] : false;

				if(isset($arr_theme_mods[$id]))
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

				//Unfortunately they inline their Custom CSS
				//Code to remove MF Custom CSS in favour of WP Custom CSS
				/*switch($id)
				{
					case 'custom_css_all':
					case 'custom_css_mobile':
						if($value_new == '')
						{
							unset($options_params[$param_key]);
						}
					break;
				}*/
			}
		}

		return array($options_params, $options);
	}

	function get_params()
	{
		if(count($this->options_params) == 0)
		{
			$this->options_params = get_params_theme_core();

			list($this->options_params, $this->options) = $this->gather_params($this->options_params);
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
		$arr_media_fonts = $this->get_media_fonts();

		foreach($arr_media_fonts as $media_key => $media_font)
		{
			$this->options_fonts[$media_key] = array(
				'title' => $media_font['title'],
				'style' => "'".$media_font['title']."'",
				'file' => remove_protocol(array('url' => $media_font['guid'])),
				'extensions' => $media_font['extensions'],
			);
		}

		$this->options_fonts[2] = array(
			'title' => "Arial",
			'style' => "Arial, sans-serif",
			'url' => ""
		);

		$this->options_fonts[1] = array(
			'title' => "Courgette",
			'style' => "'Courgette', cursive",
			'url' => "//fonts.googleapis.com/css?family=Courgette"
		);

		$this->options_fonts[3] = array(
			'title' => "Droid Sans",
			'style' => "'Droid Sans', sans-serif",
			'url' => "//fonts.googleapis.com/css?family=Droid+Sans"
		);

		$this->options_fonts[5] = array(
			'title' => "Droid Serif",
			'style' => "'Droid Serif', serif",
			'url' => "//fonts.googleapis.com/css?family=Droid+Serif"
		);

		$this->options_fonts[6] = array(
			'title' => "Garamond",
			'style' => "'EB Garamond', serif",
			'url' => "//fonts.googleapis.com/css?family=EB+Garamond"
		);

		$this->options_fonts[2] = array(
			'title' => "Helvetica",
			'style' => "Helvetica, sans-serif",
			'url' => ""
		);

		$this->options_fonts['lato'] = array(
			'title' => "Lato",
			'style' => "'Lato', sans-serif",
			'url' => "//fonts.googleapis.com/css?family=Lato"
		);

		$this->options_fonts['montserrat'] = array(
			'title' => "Montserrat",
			'style' => "'Montserrat', sans-serif",
			'url' => "//fonts.googleapis.com/css?family=Montserrat:400,700"
		);

		$this->options_fonts['muli'] = array(
			'title' => "Muli",
			'style' => "'Muli', sans-serif",
			'url' => "//fonts.googleapis.com/css?family=Muli:300,400"
		);

		$this->options_fonts[4] = array(
			'title' => "Open Sans",
			'style' => "'Open Sans', sans-serif",
			'url' => "//fonts.googleapis.com/css?family=Open+Sans"
		);

		$this->options_fonts['oswald'] = array(
			'title' => "Oswald",
			'style' => "'Oswald', sans-serif",
			'url' => "//fonts.googleapis.com/css?family=Oswald"
		);

		$this->options_fonts['oxygen'] = array(
			'title' => "Oxygen",
			'style' => "'Oxygen', sans-serif",
			'url' => "//fonts.googleapis.com/css?family=Oxygen"
		);

		$this->options_fonts['playfair_display'] = array(
			'title' => "Playfair Display",
			'style' => "'Playfair Display', serif",
			'url' => "//fonts.googleapis.com/css?family=Playfair+Display"
		);

		$this->options_fonts['roboto'] = array(
			'title' => "Roboto",
			'style' => "'Roboto', sans-serif",
			'url' => "//fonts.googleapis.com/css?family=Roboto"
		);

		$this->options_fonts['roboto_condensed'] = array(
			'title' => "Roboto Condensed",
			'style' => "'Roboto Condensed', sans-serif",
			'url' => "//fonts.googleapis.com/css?family=Roboto+Condensed"
		);

		$this->options_fonts['roboto_mono'] = array(
			'title' => "Roboto Mono",
			'style' => "'Roboto Mono', sans-serif",
			'url' => "//fonts.googleapis.com/css?family=Roboto+Mono"
		);

		$this->options_fonts['rouge_script'] = array(
			'title' => "Rouge Script",
			'style' => "'Rouge Script', cursive",
			'url' => "//fonts.googleapis.com/css?family=Rouge+Script"
		);

		$this->options_fonts['sorts_mill_goudy'] = array(
			'title' => "Sorts Mill Goudy",
			'style' => "'sorts-mill-goudy',serif",
			'url' => "//fonts.googleapis.com/css?family=Sorts+Mill+Goudy"
		);

		$this->options_fonts['source_sans_pro'] = array(
			'title' => "Source Sans Pro",
			'style' => "'Source Sans Pro', sans-serif",
			'url' => "//fonts.googleapis.com/css?family=Source+Sans+Pro"
		);
	}

	function show_font_face()
	{
		if(count($this->options_fonts) == 0)
		{
			$this->get_theme_fonts();
		}

		$out = "";

		foreach($this->options_params as $param)
		{
			if(isset($param['type']) && $param['type'] == 'font')
			{
				$font = $this->options[$param['id']];

				if($font != '' && isset($this->options_fonts[$font]['file']) && $this->options_fonts[$font]['file'] != '')
				{
					$font_file = $this->options_fonts[$font]['file'];

					$font_src = "";

					foreach($this->options_fonts[$font]['extensions'] as $font_extension)
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
							font-family: '".$this->options_fonts[$font]['title']."';
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

	function get_common_style()
	{
		$out = "";

		$out .= "p a, td a, a .read_more
		{"
			.$this->render_css(array('property' => 'color', 'value' => 'body_link_color'))
			.$this->render_css(array('property' => 'text-decoration', 'value' => 'body_link_underline'))
			."text-decoration-skip: ink;
		}

			.read_more
			{
				margin-top: .5em;
				position: relative;
			}

		.form_textfield input, .form_password input, .mf_form textarea, .mf_form select, #comments #comment
		{"
			.$this->render_css(array('property' => 'border-radius', 'value' => 'form_border_radius'))
		."}

		.form_button button, .form_button .button, #comments #submit
		{"
			.$this->render_css(array('property' => 'border-radius', 'value' => 'form_button_border_radius'))
			.$this->render_css(array('property' => 'font-size', 'value' => 'button_size'))
			.$this->render_css(array('property' => 'padding', 'value' => 'form_button_padding'))
		."}

		#wrapper .mf_form button, #wrapper .button, .color_button, #wrapper .mf_form .button-primary, #comments #submit
		{"
			.$this->render_css(array('property' => 'background', 'value' => array('button_color', 'nav_color_hover')))
			.$this->render_css(array('property' => 'color', 'value' => 'button_text_color'))
		."}

		#wrapper .button-secondary, .color_button_2
		{"
			.$this->render_css(array('property' => 'background', 'value' => 'button_color_secondary', 'suffix' => " !important"))
			.$this->render_css(array('property' => 'color', 'value' => 'button_text_color_secondary', 'suffix' => " !important"))
		."}

		.color_button_negative
		{"
			.$this->render_css(array('property' => 'background', 'value' => 'button_color_negative', 'suffix' => " !important"))
			.$this->render_css(array('property' => 'color', 'value' => 'button_text_color_negative'))
		."}

			#wrapper .mf_form button:hover, #wrapper .button:hover, #wrapper .mf_form .button-primary:hover, #comments #submit:hover, #wrapper .button-secondary:hover, .color_button_2:hover, .color_button_negative:hover
			{
				box-shadow: inset 0 0 10em rgba(0, 0, 0, .1);
			}

		html
		{
			font-size: .625em;"
			.$this->render_css(array('property' => 'font-size', 'value' => 'body_font_size'))
			.$this->render_css(array('property' => 'overflow-y', 'value' => 'body_scroll'))
		."}

			body
			{"
				.$this->render_css(array('property' => 'background', 'value' => 'footer_bg', 'suffix' => "; min-height: 100vh"))
				.$this->render_css(array('property' => 'background-color', 'value' => 'footer_bg_color'))
				.$this->render_css(array('property' => 'background-image', 'prefix' => 'url(', 'value' => 'footer_bg_image', 'suffix' => '); background-size: cover'))
				.$this->render_css(array('property' => 'font-family', 'value' => 'body_font'))
				.$this->render_css(array('property' => 'color', 'value' => 'body_color'))
			."}

				#wrapper
				{"
					.$this->render_css(array('property' => 'background', 'value' => 'body_bg'))
					.$this->render_css(array('property' => 'background-color', 'value' => 'body_bg_color'))
					.$this->render_css(array('property' => 'background-image', 'prefix' => 'url(', 'value' => 'body_bg_image', 'suffix' => '); background-size: cover'))
					//."min-height: 100vh;" /* This will override footer background below footer */
				."}";

		return $out;
	}

	function render_css($data)
	{
		$property = isset($data['property']) ? $data['property'] : '';
		$prefix = isset($data['prefix']) ? $data['prefix'] : '';
		$suffix = isset($data['suffix']) ? $data['suffix'] : '';
		$value = isset($data['value']) ? $data['value'] : '';

		if(is_array($value) && count($value) > 1)
		{
			$arr_val = $value;
			$value = $arr_val[0];
		}

		$out = '';

		switch($property)
		{
			case 'font-family':
				if(!isset($this->options[$value]) || !isset($this->options_fonts[$this->options[$value]]['style']))
				{
					$this->options[$value] = '';
				}
			break;

			case 'float':
				if($this->options[$value] == 'center')
				{
					$property = 'margin';
					$this->options[$value] = '0 auto';
				}
			break;

			case 'position':
				switch($this->options[$value])
				{
					case 'absolute':
					case 'fixed':
						if($value == 'footer_fixed')
						{
							$suffix .= ";
							bottom: 0;
							left: 0;
							right: 0";
						}

						else
						{
							$suffix .= ";
							left: 0;
							right: 0;
							z-index: 1001";
						}
					break;

					case 'sticky':
						if($value == 'footer_fixed')
						{
							$suffix = ";
							bottom: 0";
						}

						else
						{
							$suffix .= ";
							top: 0;
							z-index: 1001";
						}
					break;
				}
			break;
		}

		if(isset($this->options[$value]) && $this->options[$value] != '')
		{
			if($property != '')
			{
				$out .= $property.": ";
			}

			if($prefix != '')
			{
				$out .= $prefix;
			}

				if($property == 'font-family')
				{
					$out .= $this->options_fonts[$this->options[$value]]['style'];
				}

				else
				{
					$out .= $this->options[$value];
				}

			if($suffix != '')
			{
				$out .= $suffix;
			}

			if($property != '' || $prefix != '' || $suffix != '')
			{
				$out .= ";";
			}
		}

		else if(isset($arr_val) && count($arr_val) > 1)
		{
			array_splice($arr_val, 0, 1);

			$data['value'] = count($arr_val) > 1 ? $arr_val : $arr_val[0];

			$out .= $this->render_css($data);
		}

		return $out;
	}

	function enqueue_theme_fonts()
	{
		$this->get_theme_fonts();

		$arr_fonts2insert = array();

		$this->get_params();

		foreach($this->options_params as $param)
		{
			if(isset($param['type']) && $param['type'] == 'font' && isset($this->options[$param['id']]))
			{
				$font = $this->options[$param['id']];

				if(isset($this->options_fonts[$font]['url']) && $this->options_fonts[$font]['url'] != '')
				{
					mf_enqueue_style('style_font_'.$font, $this->options_fonts[$font]['url']);
				}
			}
		}
	}
	#################################

	/* Widgets */
	#################################
	function get_custom_widget_areas()
	{
		$this->custom_widget_area = array();

		$arr_custom_widget_area = get_option('widget_theme-widget-area-widget');

		if(is_array($arr_custom_widget_area) && count($arr_custom_widget_area) > 0)
		{
			$arr_widget_area = get_option('sidebars_widgets');

			foreach($arr_custom_widget_area as $key_custom => $arr_custom)
			{
				if($arr_custom['widget_area_id'] != '')
				{
					foreach($arr_widget_area as $key_area => $arr_area)
					{
						if(is_array($arr_area))
						{
							foreach($arr_area as $str_area)
							{
								if('theme-widget-area-widget-'.$key_custom == $str_area)
								{
									$this->custom_widget_area[$key_area][] = $arr_custom;
								}
							}
						}
					}
				}
			}
		}
	}

	function display_custom_widget_area($id)
	{
		if(isset($this->custom_widget_area[$id]) && is_array($this->custom_widget_area[$id]))
		{
			foreach($this->custom_widget_area[$id] as $arr_custom)
			{
				register_sidebar(array(
					'name' => " - ".$arr_custom['widget_area_name'],
					'id' => 'widget_area_'.$arr_custom['widget_area_id'],
					'before_widget' => "<div class='widget %s %s'>",
					'before_title' => "<h3>",
					'after_title' => "</h3>",
					'after_widget' => "</div>"
				));
			}
		}
	}
	#################################

	/* Public */
	#################################
	function add_page_index()
	{
		global $post;

		if(isset($post) && $post->ID > 0)
		{
			$page_index = get_post_meta($post->ID, $this->meta_prefix.'page_index', true);

			if($page_index != '')
			{
				switch($page_index)
				{
					case 'nofollow':
					case 'noindex':
						echo "<meta name='robots' content='".$page_index."'>";
					break;

					case 'none':
						echo "<meta name='robots' content='noindex, nofollow'>";
					break;
				}
			}
		}
	}
	function do_robots()
	{
		echo "\nSitemap: ".get_site_url()."/sitemap.xml\n";
	}

	function do_sitemap()
	{
		global $wp_query;

		if(isset($wp_query->query['name']) && $wp_query->query['name'] == 'sitemap.xml')
		{
			header("Content-type: text/xml; charset=".get_option('blog_charset'));

			echo "<?xml version='1.0' encoding='UTF-8'?>
			<?xml-stylesheet type='text/xsl' href='".plugin_dir_url(__FILE__)."/sitemap-xsl.php'?>
			<urlset xmlns='http://www.sitemaps.org/schemas/sitemap/0.9'>";

				$this->get_public_posts();

				foreach($this->arr_public_posts as $post_id => $post_title)
				{
					$post_modified = get_post_modified_time("Y-m-d H:i:s", true, $post_id);

					$post_url = get_permalink($post_id);

					echo "<url>
						<loc>".$post_url."</loc>
						<title>".htmlspecialchars($post_title)."</title>
						<lastmod>".$post_modified."</lastmod>
					</url>";

					/*<changefreq>monthly</changefreq>
					<priority>0.8</priority>*/
				}

			echo "</urlset>";
			exit;
		}
	}

	function get_logo($data = array())
	{
		if(!isset($data['url'])){				$data['url'] = get_site_url();}
		if(!isset($data['display'])){			$data['display'] = 'all';}
		if(!isset($data['title'])){				$data['title'] = '';}
		if(!isset($data['image'])){				$data['image'] = '';}
		if(!isset($data['description'])){		$data['description'] = '';}

		$this->get_params();

		$header_logo = isset($this->options['header_logo']) ? $this->options['header_logo'] : '';
		$header_mobile_logo = isset($this->options['header_mobile_logo']) ? $this->options['header_mobile_logo'] : '';

		$has_logo = $data['image'] != '' || $header_logo != '' || $header_mobile_logo != '';

		$out = "<a href='".trim($data['url'], '/')."/' id='site_logo'>";

			if($has_logo && $data['title'] == '')
			{
				if($data['display'] != 'tagline')
				{
					$site_name = get_bloginfo('name');
					$site_description = get_bloginfo('description');

					if($data['image'] != '')
					{
						$out .= "<img src='".$data['image']."' alt='".sprintf(__("Logo for %s", 'lang_theme_core'), $site_name.($site_description != '' ? " | ".$site_description : ''))."'>";
					}

					else
					{
						if($header_logo != '')
						{
							$out .= "<img src='".$header_logo."'".($header_mobile_logo != '' ? " class='hide_if_mobile'" : "")." alt='".sprintf(__("Logo for %s", 'lang_theme_core'), $site_name.($site_description != '' ? " | ".$site_description : ''))."'>";
						}

						if($header_mobile_logo != '')
						{
							$out .= "<img src='".$header_mobile_logo."'".($header_logo != '' ? " class='show_if_mobile'" : "")." alt='".sprintf(__("Mobile Logo for %s", 'lang_theme_core'), $site_name.($site_description != '' ? " | ".$site_description : ''))."'>";
						}
					}
				}

				if($data['display'] != 'title' && $data['description'] != '')
				{
					$out .= "<span>".$data['description']."</span>";
				}
			}

			else
			{
				if($data['display'] != 'tagline')
				{
					$logo_title = $data['title'] != '' ? $data['title'] : get_bloginfo('name');

					$out .= "<div>".$logo_title."</div>";
				}

				if($data['display'] != 'title')
				{
					$logo_description = $data['description'] != '' ? $data['description'] : get_bloginfo('description');

					if($logo_description != '')
					{
						$out .= "<span>".$logo_description."</span>";
					}
				}
			}

		$out .= "</a>";

		return $out;
	}
	#################################

	/* Admin */
	#################################
	function column_header($cols)
	{
		unset($cols['comments']);

		if(is_site_public())
		{
			$cols['seo'] = __("SEO", 'lang_theme_core');
		}

		return $cols;
	}

	function column_cell($col, $id)
	{
		global $wpdb, $post;

		switch($col)
		{
			case 'seo':
				$title_limit = 64;
				$excerpt_limit = 156;
				$content_limit = 400;

				$seo_type = '';

				if($seo_type == '')
				{
					if($post->post_status != 'publish')
					{
						$seo_type = 'not_published';
					}
				}

				if($seo_type == '')
				{
					$page_index = get_post_meta($id, $this->meta_prefix.'page_index', true);

					if(in_array($page_index, array('noindex', 'none')))
					{
						$seo_type = 'not_indexed';
					}
				}

				if($seo_type == '')
				{
					if(post_password_required($id))
					{
						$seo_type = 'password_protected';
					}
				}

				if($seo_type == '')
				{
					if($post->post_excerpt != '')
					{
						$post_id_duplicate = $wpdb->get_var($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." WHERE post_excerpt = %s AND post_status = 'publish' AND post_type = %s AND ID != '%d' LIMIT 0, 1", $post->post_excerpt, $post->post_type, $id));

						if($post_id_duplicate > 0)
						{
							$seo_type = 'duplicate_excerpt';
						}

						else if(strlen($post->post_excerpt) > $excerpt_limit)
						{
							$seo_type = 'long_excerpt';
						}
					}

					else
					{
						$seo_type = 'no_excerpt';
					}
				}

				if($seo_type == '')
				{
					if($post->post_title != '')
					{
						$post_id_duplicate = $wpdb->get_var($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." WHERE post_title = %s AND post_status = 'publish' AND post_type = %s AND ID != '%d' LIMIT 0, 1", $post->post_title, $post->post_type, $id));

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
					if($post->post_name != '')
					{
						if(sanitize_title_with_dashes(sanitize_title($post->post_title)) != $post->post_name)
						{
							$seo_type = 'inconsistent_url';
						}
					}
				}

				if($seo_type == '')
				{
					$site_title = $post->post_title." | ".$this->get_wp_title();

					if(strlen($site_title) > $title_limit)
					{
						$seo_type = 'long_title';
					}
				}

				if($seo_type == '')
				{
					if(strlen($post->post_content) < $content_limit)
					{
						$seo_type = 'short_content';
					}

					else if(strlen($post->post_content) > 0 && preg_match("/\<h2/", $post->post_content) == false)
					{
						$seo_type = 'no_sub_heading';
					}
				}

				switch($seo_type)
				{
					case 'duplicate_title':
						echo "<i class='fa fa-times fa-lg red'></i>
						<div class='row-actions'>
							<a href='".admin_url("post.php?post=".$post_id_duplicate."&action=edit")."'>"
								.sprintf(__("The page %s have the exact same title. Please, try to not have duplicates because that will hurt your SEO.", 'lang_theme_core'), get_post_title($post_id_duplicate))
							."</a>
						</div>";
					break;

					case 'no_title':
						echo "<i class='fa fa-times fa-lg red' title='".__("You have not set a title for this page", 'lang_theme_core')."'></i>";
					break;

					case 'duplicate_excerpt':
						echo "<i class='fa fa-times fa-lg red'></i>
						<div class='row-actions'>
							<a href='".admin_url("post.php?post=".$post_id_duplicate."&action=edit")."'>"
								.sprintf(__("The page %s have the exact same excerpt", 'lang_theme_core'), get_post_title($post_id_duplicate))
							."</a>
						</div>";
					break;

					case 'no_excerpt':
						echo "<i class='fa fa-times fa-lg red' title='".__("You have not set an excerpt for this page", 'lang_theme_core')."'></i>";
					break;

					case 'inconsistent_url':
						echo "<i class='fa fa-exclamation-triangle fa-lg yellow' title='".__("The URL is not correlated to the title", 'lang_theme_core')."'></i>";
					break;

					case 'long_title':
						echo "<i class='fa fa-exclamation-triangle fa-lg yellow' title='".__("The title might be too long to show in search engines", 'lang_theme_core')." (".strlen($site_title)." > ".$title_limit.")'></i>";
					break;

					case 'long_excerpt':
						echo "<i class='fa fa-exclamation-triangle fa-lg yellow' title='".__("The excerpt (meta description) might be too long to show in search engines", 'lang_theme_core')." (".strlen($post->post_excerpt)." > ".$excerpt_limit.")'></i>";
					break;

					case 'short_content':
						echo "<i class='fa fa-exclamation-triangle fa-lg yellow' title='".__("The content should be longer", 'lang_theme_core')." (".strlen($post->post_content)." > ".$content_limit.")'></i>";
					break;

					case 'no_sub_heading':
						echo "<i class='fa fa-exclamation-triangle fa-lg yellow' title='".__("There should be an H2 in the content", 'lang_theme_core')."'></i>";
					break;

					case 'password_protected':
						echo "<i class='fa fa-lock fa-lg grey' title='".__("The page is password protected", 'lang_theme_core')."'></i>";
					break;

					case 'not_published':
					case 'not_indexed':
						echo "<i class='fa fa-eye-slash fa-lg grey' title='".__("The page is not published or indexed", 'lang_theme_core')."'></i>";
					break;

					default:
						echo "<i class='fa fa-check fa-lg green' title='".__("Well done! The page is SEO approved!", 'lang_theme_core')."'></i>";
					break;
				}
			break;
		}
	}

	function admin_post_thumbnail_html($content, $post_id)
	{
		if(has_post_thumbnail($post_id))
		{
			$field_id = $this->meta_prefix.'display_featured_image';
			$field_value = get_post_meta($post_id, $field_id, true);

			$content .= "<div class='mf_form'>"
				.show_select(array('data' => get_yes_no_for_select(), 'name' => $field_id, 'text' => __("Display on Single Page", 'lang_theme_core'), 'compare' => $field_value))
			."</div>";
		}

		return $content;
	}

	function hidden_meta_boxes($hidden, $screen)
	{
		$setting_theme_core_hidden_meta_boxes = get_option('setting_theme_core_hidden_meta_boxes');

		if(is_array($setting_theme_core_hidden_meta_boxes))
		{
			$hidden = array_merge($hidden, $setting_theme_core_hidden_meta_boxes);
		}

		return $hidden;
	}

	function rwmb_meta_boxes($meta_boxes)
	{
		if(is_site_public() && IS_ADMIN)
		{
			$meta_boxes[] = array(
				'id' => 'theme_core_publish',
				'title' => __("Publish Settings", 'lang_theme_core'),
				'post_types' => get_post_types_for_metabox(array('public' => true, 'exclude_from_search' => false)),
				'context' => 'side',
				'priority' => 'low',
				'fields' => array(
					array(
						'name' => __("Index", 'lang_theme_core'),
						'id' => $this->meta_prefix.'page_index',
						'type' => 'select',
						'options' => array(
							'' => "-- ".__("Choose Here", 'lang_theme_core')." --",
							'noindex' => __("Don't Index", 'lang_theme_core'),
							'nofollow' => __("Don't Follow Links", 'lang_theme_core'),
							'none' => __("Don't Index & don't follow links", 'lang_theme_core'),
						),
					),
					array(
						'name' => __("Unpublish", 'lang_theme_core'),
						'id' => $this->meta_prefix.'unpublish_date',
						'type' => 'datetime',
					),
				)
			);
		}

		return $meta_boxes;
	}

	function save_post($post_id, $post, $update)
	{
		if(in_array($post->post_type, array('page', 'post')))
		{
			$field_id = $this->meta_prefix.'display_featured_image';
			$field_value = check_var($field_id);

			update_post_meta($post_id, $field_id, $field_value);
		}

		/* Send e-mail to all editors if it is a draft and the user saving the draft is an author, but not an editor */
		if(isset($post->post_status) && $post->post_status == 'draft' && IS_AUTHOR && !IS_EDITOR && get_option('setting_send_email_on_draft') == 'yes')
		{
			$post_title = get_the_title($post);
			$post_url = get_permalink($post);

			$mail_subject = sprintf(__("The draft '%s' has been saved", 'lang_theme_core'), $post_title);
			$mail_content = sprintf(__("The draft '%s' has been saved and might be ready for publishing", 'lang_theme_core'), "<a href='".$post_url."'>".$post_title."</a>");

			$users = get_users(array(
				'fields' => array('user_email'),
				'role__in' => array('editor'),
			));

			foreach($users as $user)
			{
				$mail_to = $user->user_email;

				$sent = send_email(array('to' => $mail_to, 'subject' => $mail_subject, 'content' => $mail_content));
			}
		}
	}
	#################################

	function count_shortcode_button($count)
	{
		if($count == 0)
		{
			$count++;
		}

		return $count;
	}

	function get_shortcode_output($out)
	{
		$arr_data = array(
			'' => __("No", 'lang_theme_core'),
			'yes' => __("Yes", 'lang_theme_core')
		);

		$out .= "<h3>".__("Redirect", 'lang_theme_core')."</h3>"
		.show_select(array('data' => $arr_data, 'xtra' => "rel='redirect url=https://domain.com sec=5'"));

		return $out;
	}

	function require_user_login()
	{
		if(get_option('setting_no_public_pages') == 'yes')
		{
			mf_redirect(get_site_url()."/wp-admin/");
		}

		else if(get_option('setting_theme_core_login') == 'yes' && !is_user_logged_in())
		{
			mf_redirect(get_site_url()."/wp-login.php?redirect_to=".$_SERVER['REQUEST_URI']);
		}
	}

	function after_setup_theme()
	{
		add_post_type_support('page', 'excerpt');

		remove_action('wp_head', 'wp_print_scripts');
		remove_action('wp_head', 'wp_print_head_scripts', 9);
		remove_action('wp_head', 'wp_enqueue_scripts', 1);
		add_action('wp_footer', 'wp_print_scripts', 5);
		add_action('wp_footer', 'wp_enqueue_scripts', 5);
		add_action('wp_footer', 'wp_print_head_scripts', 5);
	}

	function widgets_init()
	{
		register_widget('widget_theme_core_area');
		register_widget('widget_theme_core_logo');
		register_widget('widget_theme_core_search');
		register_widget('widget_theme_core_news');
		register_widget('widget_theme_core_info');
		register_widget('widget_theme_core_related');
		register_widget('widget_theme_core_promo');
		//mf_unregister_widget('WP_Widget_Recent_Posts');

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
	}

	//Customizer
	#################################
	function add_select($data = array())
	{
		global $wp_customize;

		$wp_customize->add_control(
			$this->param['id'],
			array(
				'label' => $this->param['title'],
				'section' => $this->id_temp,
				'settings' => $this->param['id'],
				'type' => 'select',
				'choices' => $data['choices'],
			)
		);
	}

	function customize_register($wp_customize)
	{
		$plugin_include_url = plugin_dir_url(__FILE__);
		$plugin_version = get_plugin_version(__FILE__);

		mf_enqueue_style('style_theme_core_customizer', $plugin_include_url."style_customizer.css", $plugin_version);

		$this->get_params();
		$this->get_theme_fonts();

		$this->id_temp = "";
		$this->param = array();

		$wp_customize->remove_section('themes');
		$wp_customize->remove_section('title_tagline');
		$wp_customize->remove_section('static_front_page');
		//$wp_customize->remove_section('nav_menus');
		//$wp_customize->remove_section('widgets');
		$wp_customize->remove_section('custom_css');

		foreach($this->options_params as $this->param)
		{
			if(isset($this->param['show_if']) && $this->param['show_if'] != '' && $this->options[$this->param['show_if']] == ''){}

			else if(isset($this->param['hide_if']) && $this->param['hide_if'] != '' && $this->options[$this->param['hide_if']] != ''){}

			else
			{
				if(isset($this->param['category']))
				{
					$this->id_temp = $this->param['id'];

					$wp_customize->add_section(
						$this->id_temp,
						array(
							'title' => $this->param['category'],
							//'description' => '',
							//'priority' => 1,
						)
					);
				}

				else if(isset($this->param['category_end'])){}

				else
				{
					if(isset($this->param['default']))
					{
						$default_value = $this->param['default'];
					}

					else
					{
						$default_value = '';
					}

					$wp_customize->add_setting(
						$this->param['id'],
						array(
							'default' => $default_value,
							'transport' => "postMessage"
						)
					);

					switch($this->param['type'])
					{
						case 'align':
							$arr_data = array(
								'' => "-- ".__("Choose Here", 'lang_theme_core')." --",
								'left' => __("Left", 'lang_theme_core'),
								'center' => __("Center", 'lang_theme_core'),
								'right' => __("Right", 'lang_theme_core'),
							);

							$this->add_select(array('choices' => $arr_data));
						break;

						case 'color':
							$wp_customize->add_control(
								new WP_Customize_Color_Control(
									$wp_customize,
									$this->param['id'],
									array(
										'label' => $this->param['title'],
										'section' => $this->id_temp,
										'settings' => $this->param['id'],
									)
								)
							);
						break;

						case 'checkbox':
							$arr_data = array(
								2 => __("Yes", 'lang_theme_core'),
								1 => __("No", 'lang_theme_core'),
							);

							$this->add_select(array('choices' => $arr_data));
						break;

						case 'clear':
							$arr_data = array(
								'' => "-- ".__("Choose Here", 'lang_theme_core')." --",
								'left' => __("Left", 'lang_theme_core'),
								'right' => __("Right", 'lang_theme_core'),
								'both' => __("Both", 'lang_theme_core'),
								'none' => __("None", 'lang_theme_core'),
							);

							$this->add_select(array('choices' => $arr_data));
						break;

						case 'direction':
							$arr_data = array(
								'horizontal' => __("Horizontal", 'lang_theme_core'),
								'vertical' => __("Vertical", 'lang_theme_core'),
							);

							$this->add_select(array('choices' => $arr_data));
						break;

						case 'date':
						case 'email':
						case 'hidden':
						case 'number':
						case 'text':
						case 'textarea':
						case 'url':
							$wp_customize->add_control(
								$this->param['id'],
								array(
									'label' => $this->param['title'],
									'section' => $this->id_temp,
									'type' => $this->param['type'],
								)
							);
						break;

						case 'float':
							$arr_data = array(
								'' => "-- ".__("Choose Here", 'lang_theme_core')." --",
								'none' => __("None", 'lang_theme_core'),
								'left' => __("Left", 'lang_theme_core'),
								'center' => __("Center", 'lang_theme_core'),
								'right' => __("Right", 'lang_theme_core'),
								'initial' => __("Initial", 'lang_theme_core'),
								'inherit' => __("Inherit", 'lang_theme_core'),
							);

							$this->add_select(array('choices' => $arr_data));
						break;

						case 'font':
							$arr_data = array(
								'' => "-- ".__("Choose Here", 'lang_theme_core')." --"
							);

							if(count($this->options_fonts) > 0)
							{
								foreach($this->options_fonts as $key => $value)
								{
									$arr_data[$key] = $value['title'];
								}
							}

							$this->add_select(array('choices' => $arr_data));
						break;

						case 'image':
							$wp_customize->add_control(
								new WP_Customize_Image_Control(
									$wp_customize,
									$this->param['id'],
									array(
										'label' => $this->param['title'],
										'section' => $this->id_temp,
										'settings' => $this->param['id'],
									)
								)
							);
						break;

						case 'overflow':
							$arr_data = array(
								'' => "-- ".__("Choose Here", 'lang_theme_core')." --",
								'visible' => __("Visible", 'lang_theme_core'),
								'hidden' => __("Hidden", 'lang_theme_core'),
								'scroll' => __("Scroll", 'lang_theme_core'),
								'auto' => __("Auto", 'lang_theme_core'),
								'initial' => __("Initial", 'lang_theme_core'),
								'inherit' => __("Inherit", 'lang_theme_core'),
							);

							$this->add_select(array('choices' => $arr_data));
						break;

						case 'position':
							$arr_data = array(
								'' => "-- ".__("Choose Here", 'lang_theme_core')." --",
								'absolute' => __("Absolute", 'lang_theme_core'),
								'fixed' => __("Fixed", 'lang_theme_core'),
								'relative' => __("Relative", 'lang_theme_core'),
								'sticky' => __("Sticky", 'lang_theme_core'),
							);

							$this->add_select(array('choices' => $arr_data));
						break;

						case 'text_decoration':
							$arr_data = array(
								'' => "-- ".__("Choose Here", 'lang_theme_core')." --",
								'none' => __("None", 'lang_theme_core'),
								'underline' => __("Underline", 'lang_theme_core'),
							);

							$this->add_select(array('choices' => $arr_data));
						break;

						case 'text_transform':
							$arr_data = array(
								'' => "-- ".__("Choose Here", 'lang_theme_core')." --",
								'uppercase' => __("Uppercase", 'lang_theme_core'),
								'lowercase' => __("Lowercase", 'lang_theme_core'),
							);

							$this->add_select(array('choices' => $arr_data));
						break;

						case 'weight':
							$arr_data = array(
								'' => "-- ".__("Choose Here", 'lang_theme_core')." --",
								'lighter' => __("Lighter", 'lang_theme_core'),
								'normal' => __("Normal", 'lang_theme_core'),
								'bold' => __("Bold", 'lang_theme_core'),
								'bolder' => __("Bolder", 'lang_theme_core'),
								'initial' => __("Initial", 'lang_theme_core'),
								'inherit' => __("Inherit", 'lang_theme_core'),
							);

							$this->add_select(array('choices' => $arr_data));
						break;
					}
				}
			}
		}
	}

	function customize_save()
	{
		update_option('option_theme_saved', date("Y-m-d H:i:s"), 'no');
		update_option('option_theme_version', get_option('option_theme_version', 0) + 1, 'no');
	}
	#################################

	// This is a WP v4.9 fix for sites that have had files in uploads/{year}/{month} and are expected to have the files in uploads/sites/{id}/{year}/{month}
	#################################
	function copy_file()
	{
		if(file_exists($this->file_dir_to))
		{
			if(get_option('option_uploads_fixed') < date("Y-m-d", strtotime("-1 month")))
			{
				if(file_exists($this->file_dir_from) && is_file($this->file_dir_from))
				{
					//do_log(sprintf(__("The file %s already exists so %s can be deleted now", 'lang_theme_core'), $this->file_dir_to, $this->file_dir_from));

					/* Some files are still in use in the old hierarchy */
					/*unlink($this->file_dir_from);
					do_log("Removed File: ".$upload_path.$strFileName);*/
				}

				/*else
				{
					do_log("File has already been deleted: ".$this->file_dir_from);
				}*/
			}
		}

		else
		{
			if(file_exists($this->file_dir_from))
			{
				@mkdir(dirname($this->file_dir_to), 0755, true);

				if(copy($this->file_dir_from, $this->file_dir_to))
				{
					//do_log("File was copied: ".$this->file_dir_from." -> ".$this->file_dir_to);
				}

				else
				{
					do_log("File was NOT copied: ".$this->file_dir_from." -> ".$this->file_dir_to);
				}
			}
		}
	}

	function do_fix()
	{
		global $wpdb;

		$upload_path_from = WP_CONTENT_DIR."/uploads/";
		$upload_url_from = WP_CONTENT_URL."/uploads/";

		list($upload_path_to, $upload_url_to) = get_uploads_folder('', true);

		if(!preg_match("/\/sites\//", $upload_path_to)){	$upload_path_to .= "sites/".$wpdb->blogid."/";}
		if(!preg_match("/\/sites\//", $upload_url_to)){		$upload_url_to .= "sites/".$wpdb->blogid."/";}

		$arr_sizes = array('thumbnail', 'medium', 'large');

		$result = $wpdb->get_results($wpdb->prepare("SELECT ID, post_title FROM ".$wpdb->posts." WHERE post_type = %s", 'attachment'));

		foreach($result as $r)
		{
			$post_id = $r->ID;
			$post_url = wp_get_attachment_url($post_id);

			$this->file_dir_from = str_replace(array($upload_url_to, $upload_url_from), $upload_path_from, $post_url);
			$this->file_dir_to = str_replace(array($upload_url_to, $upload_url_from), $upload_path_to, $post_url);

			$this->copy_file();

			if(wp_attachment_is_image($post_id))
			{
				foreach($arr_sizes as $size)
				{
					$arr_image = wp_get_attachment_image_src($post_id, $size);
					$post_url = $arr_image[0];

					$this->file_dir_from = str_replace(array($upload_url_to, $upload_url_from), $upload_path_from, $post_url);
					$this->file_dir_to = str_replace(array($upload_url_to, $upload_url_from), $upload_path_to, $post_url);

					$this->copy_file();
				}
			}
		}

		/* Some files are still in use in the old hierarchy */
		/*if(!(get_option('option_uploads_fixed') > DEFAULT_DATE))
		{
			update_option('option_uploads_fixed', date("Y-m-d H:i:s"), 'no');
		}

		if(get_option('option_uploads_fixed') < date("Y-m-d", strtotime("-1 month")))
		{
			if(file_exists($upload_path_from.date("Y")))
			{
				do_log(sprintf(__("You can now safely remove all year folders in %s, but just to be on the safe side you can move them to a temporary folder or make a backup before you do this just in case"), $upload_path_from));

				update_option('option_uploads_done', date("Y-m-d H:i:s"), 'no');
				delete_option('option_uploads_fixed');
			}
		}*/
	}
	#################################

	// Cron
	#################################
	function unpublish_posts()
	{
		global $wpdb;

		$result = $wpdb->get_results("SELECT ID, meta_value FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id AND meta_key = '".$this->meta_prefix."unpublish_date' WHERE post_status = 'publish' AND meta_value != ''");

		if($wpdb->num_rows > 0)
		{
			foreach($result as $r)
			{
				$post_id = $r->ID;
				$post_unpublish = $r->meta_value;

				if($post_unpublish <= date("Y-m-d H:i:s"))
				{
					$post_data = array(
						'ID' => $post_id,
						'post_status' => 'draft',
						'meta_input' => array(
							$this->meta_prefix.'unpublish_date' => '',
						),
					);

					wp_update_post($post_data);
				}
			}
		}
	}

	function check_style_source()
	{
		delete_option('option_theme_source_style_url');

		$this->get_params();

		if(isset($this->options['style_source']) && $this->options['style_source'] != '')
		{
			$style_source = trim($this->options['style_source'], "/");

			if($style_source != get_site_url())
			{
				if(filter_var($style_source, FILTER_VALIDATE_URL))
				{
					list($content, $headers) = get_url_content(array('url' => $style_source."/wp-content/plugins/mf_theme_core/include/api/?type=get_style_source", 'catch_head' => true));

					if(isset($headers['http_code']) && $headers['http_code'] == 200)
					{
						$json = json_decode($content, true);

						if(isset($json['success']) && $json['success'] == true)
						{
							$style_changed = $json['response']['style_changed'];
							$style_url = $json['response']['style_url'];

							update_option('option_theme_source_style_url', ($style_changed > get_option('option_theme_saved') ? $style_url : ""), 'no');
						}

						else
						{
							do_log(sprintf(__("The feed from %s returned an error (%s)", 'lang_theme_core'), $style_source, $content));
						}

						do_log(__("The response from", 'lang_theme_core'), 'trash');
					}

					else
					{
						do_log(sprintf(__("The response from %s had an error (%s)", 'lang_theme_core'), $style_source, $headers['http_code']));
					}
				}

				else
				{
					do_log(sprintf(__("I could not process the feed from %s since the URL was not a valid one", 'lang_theme_core'), $style_source));
				}
			}
		}
	}

	function map_meta_cap($caps, $cap)
	{
		switch($cap)
		{
			case 'manage_privacy_options':
				$caps = array('manage_options');
			break;
		}

		return $caps;
	}

	function has_comments()
	{
		global $wpdb;

		$wpdb->get_results("SELECT comment_ID FROM ".$wpdb->comments." WHERE comment_approved NOT IN('spam', 'trash') LIMIT 0, 1");

		return ($wpdb->num_rows > 0);
	}

	function get_theme_updates_message()
	{
		global $menu;

		$count_message = "";
		$rows = 0;

		if(get_option('option_theme_source_style_url') != ''){		$rows++;}

		if($rows > 0)
		{
			$count_message = "&nbsp;<span class='update-plugins' title='".__("Theme Updates", 'lang_theme_core')."'>
				<span>".$rows."</span>
			</span>";

			if(count($menu) > 0)
			{
				foreach($menu as $key => $item)
				{
					if($item[2] == 'themes.php')
					{
						$menu_name = $item[0];

						$menu[$key][0] = strip_tags($menu_name).$count_message;

						break;
					}
				}
			}
		}

		return $count_message;
	}

	function admin_menu()
	{
		$menu_title = __("Theme Backup", 'lang_theme_core');
		add_theme_page($menu_title, $menu_title.$this->get_theme_updates_message(), 'edit_theme_options', 'theme_options', 'get_options_page_theme_core');

		if($this->has_comments() == false)
		{
			remove_menu_page("edit-comments.php");
		}
	}

	function add_policy($content)
	{
		if(get_option('setting_cookie_info') > 0)
		{
			$content .= "<h3>".__("Theme", 'lang_theme_core')."</h3>
			<p>"
				.__("A cookie is saved when the visitor accepts the use of cookies on the site, to make sure that the message asking for permission does not appear again.", 'lang_theme_core')
			."</p>";
		}

		return $content;
	}

	function delete_folder($data)
	{
		$folder = $data['path']."/".$data['child'];

		if(is_dir($folder) && count(@scandir($folder)) == 2)
		{
			@rmdir($folder);
			//do_log("Removed Empty Folder: ".$folder);
		}
	}

	function do_optimize()
	{
		global $wpdb;

		//Remove old revisions and auto-drafts
		$wpdb->query("DELETE FROM ".$wpdb->posts." WHERE post_type IN ('revision', 'auto-draft') AND post_modified < DATE_SUB(NOW(), INTERVAL 12 MONTH)");

		//Remove orphan postmeta
		$wpdb->get_results("SELECT post_id FROM ".$wpdb->postmeta." LEFT JOIN ".$wpdb->posts." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id WHERE ".$wpdb->posts.".ID IS NULL LIMIT 0, 1");

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

		// Pingbacks / Trackbacks
		/*$arr_comment_types = array('pingback', 'trackback');

		foreach($arr_comment_types as $comment_type)
		{
			$wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->comments." WHERE comment_type = %s AND comment_date < DATE_SUB(NOW(), INTERVAL 12 MONTH)", $comment_type));

			if($wpdb->num_rows > 0)
			{
				do_log("Remove ".$comment_type.": ".$wpdb->last_query);

				//$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->comments." WHERE comment_type = %s AND comment_date < DATE_SUB(NOW(), INTERVAL 12 MONTH)", $comment_type));
			}
		}*/

		//Spam comments
		$wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->comments." WHERE comment_approved = %s AND comment_date < DATE_SUB(NOW(), INTERVAL 12 MONTH)", 'spam'));

		if($wpdb->num_rows > 0)
		{
			$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->comments." WHERE comment_approved = %s AND comment_date < DATE_SUB(NOW(), INTERVAL 12 MONTH)", 'spam'));
		}

		//Duplicate comments
		$wpdb->get_results($wpdb->prepare("SELECT *, COUNT(meta_id) AS count FROM ".$wpdb->commentmeta." GROUP BY comment_id, meta_key, meta_value HAVING count > %d", 1));

		if($wpdb->num_rows > 0)
		{
			do_log("Remove duplicate comments: ".$wpdb->last_query);
		}

		//oEmbed caches
		$wpdb->get_results($wpdb->prepare("SELECT * FROM ".$wpdb->postmeta." WHERE meta_key LIKE %s", "%_oembed_%"));

		if($wpdb->num_rows > 0)
		{
			//do_log("Remove oEmbed caches: ".$wpdb->last_query);

			$wpdb->get_results($wpdb->prepare("DELETE FROM ".$wpdb->postmeta." WHERE meta_key LIKE %s", "%_oembed_%"));
		}

		/* Optimize Tables */
		$result = $wpdb->get_results("SHOW TABLE STATUS");

		foreach($result as $r)
		{
			$strTableName = $r->Name;

			$wpdb->query("OPTIMIZE TABLE ".$strTableName);
		}

		// Remove empty folders in uploads
		list($upload_path, $upload_url) = get_uploads_folder();
		get_file_info(array('path' => $upload_path, 'folder_callback' => array($this, 'delete_folder')));

		if(is_multisite() && !(get_option('option_uploads_done') > DEFAULT_DATE))
		{
			$this->do_fix();
		}

		update_option('option_database_optimized', date("Y-m-d H:i:s"), 'no');

		return __("I have optimized the site for you", 'lang_theme_core');
	}

	function optimize_theme()
	{
		global $done_text, $error_text;

		$result = array();

		$done_text = $this->do_optimize();

		$out = get_notification();

		if($out != '')
		{
			$result['success'] = true;
			$result['message'] = $out;
		}

		else
		{
			$result['error'] = $out;
		}

		header('Content-Type: application/json');
		echo json_encode($result);
		die();
	}
	#################################

	function shortcode_redirect($atts)
	{
		extract(shortcode_atts(array(
			'url' => '',
			'sec' => 3,
		), $atts));

		$out = "";

		if($url != '')
		{
			$out .= "<meta http-equiv='refresh' content='".$sec."; url=".$url."'>";
		}

		return $out;
	}
}

class mf_clone_posts
{
	function __construct()
	{
		add_filter('post_row_actions', array(&$this, 'row_actions'), 10, 2);
		add_filter('page_row_actions', array(&$this, 'row_actions'), 10, 2);
		add_action('wp_loaded', array(&$this, 'wp_loaded'));
	}

	function clone_single_post()
	{
    	$p = get_post($this->post_id_old);

		if($p == null)
		{
			return false;
		}

		$newpost = array(
			'post_name'				=> $p->post_name,
			'post_type'				=> $p->post_type,
			'ping_status'			=> $p->ping_status,
			'post_parent'			=> $p->post_parent,
			'menu_order'			=> $p->menu_order,
			'post_password'			=> $p->post_password,
			'post_excerpt'			=> $p->post_excerpt,
			'comment_status'		=> $p->comment_status,
			'post_title'			=> $p->post_title." (".__("copy", 'lang_theme_core').")",
			'post_content'			=> $p->post_content,
			'post_author'			=> $p->post_author,
			'to_ping'				=> $p->to_ping,
			'pinged'				=> $p->pinged,
			'post_content_filtered' => $p->post_content_filtered,
			'post_category'			=> $p->post_category,
			'tags_input'			=> $p->tags_input,
			'tax_input'				=> $p->tax_input,
			'page_template'			=> $p->page_template
			// 'post_date'			=> $p->post_date,				// default: current date
			// 'post_date_gmt'  	=> $p->post_date_gmt, 			// default: current gmt date
			// 'post_status'    	=> $p->post_status 				// default: draft
		);

		$this->post_id_new = wp_insert_post($newpost);

		$format = get_post_format($this->post_id_old);
		set_post_format($this->post_id_new, $format);

		$arr_meta = get_post_meta($this->post_id_old);

		foreach($arr_meta as $key => $value)
		{
			if(substr($key, 0, 1) != '_')
			{
				if(is_array($value))
				{
					if(!(count($value) > 1))
					{
						$value = $value[0];
					}
				}

				update_post_meta($this->post_id_new, $key, $value);
			}
		}

		do_action('clone_page', $this->post_id_old, $this->post_id_new);

		return true;
	}

	function row_actions($actions, $post)
	{
		global $post_type;

		$url = remove_query_arg(array('cloned', 'untrashed', 'deleted', 'ids'), "");

		if(!$url)
		{
			$url = admin_url("edit.php?post_type=".$post_type);
		}

		$url = remove_query_arg(array('action', 'action2', 'tags_input', 'post_author', 'comment_status', 'ping_status', '_status',  'post', 'bulk_edit', 'post_view'), $url);
		$url = add_query_arg(array('action' => 'clone-single', 'post' => $post->ID, 'redirect' => $_SERVER['REQUEST_URI']), $url);

		if(IS_EDITOR)
		{
			$actions['clone'] = "<a href='".$url."'>".__("Clone", 'lang_theme_core')."</a>";
		}

		return $actions;
	}

	function wp_loaded()
	{
		global $post_type;

		if(!isset($_GET['action']) || $_GET['action'] !== "clone-single")
		{
			return;
		}

		$this->post_id_old = check_var('post');

		if(!current_user_can('edit_post', $this->post_id_old))
		{
			wp_die(__("You are not allowed to clone this post", 'lang_theme_core'));
		}

		else if(!$this->clone_single_post())
		{
			wp_die(__("Error cloning post", 'lang_theme_core'));
		}

		else
		{
			mf_redirect(admin_url("post.php?post=".$this->post_id_new."&action=edit"));
		}
	}
}

class widget_theme_core_area extends WP_Widget
{
	function __construct()
	{
		$widget_ops = array(
			'classname' => 'theme_widget_area',
			'description' => __("Add Widget Area", 'lang_theme_core')
		);

		$this->arr_default = array(
			'widget_area_id' => '',
			'widget_area_name' => '',
			'widget_area_columns' => 1,
		);

		parent::__construct('theme-widget-area-widget', __("Widget Area", 'lang_theme_core'), $widget_ops);
	}

	function widget($args, $instance)
	{
		extract($args);
		$instance = wp_parse_args((array)$instance, $this->arr_default);

		if(is_active_sidebar('widget_area_'.$instance['widget_area_id']))
		{
			echo $before_widget
				."<div class='widget_columns columns_".$instance['widget_area_columns']."'>";

					dynamic_sidebar('widget_area_'.$instance['widget_area_id']);

				echo "</div>"
			.$after_widget;
		}
	}

	function update($new_instance, $old_instance)
	{
		$instance = $old_instance;
		$new_instance = wp_parse_args((array)$new_instance, $this->arr_default);

		$instance['widget_area_id'] = sanitize_text_field($new_instance['widget_area_id']);
		$instance['widget_area_name'] = sanitize_text_field($new_instance['widget_area_name']);
		$instance['widget_area_columns'] = sanitize_text_field($new_instance['widget_area_columns']);

		return $instance;
	}

	function form($instance)
	{
		$instance = wp_parse_args((array)$instance, $this->arr_default);

		echo "<div class='mf_form'>"
			.show_textfield(array('name' => $this->get_field_name('widget_area_id'), 'text' => __("ID (Has to be unique)", 'lang_theme_core'), 'value' => $instance['widget_area_id'], 'required' => true, 'xtra' => ($instance['widget_area_id'] != '' ? "readonly" : "")))
			.show_textfield(array('name' => $this->get_field_name('widget_area_name'), 'text' => __("Name", 'lang_theme_core'), 'value' => $instance['widget_area_name'], 'required' => true))
			.show_textfield(array('type' => 'number', 'name' => $this->get_field_name('widget_area_columns'), 'text' => __("Columns", 'lang_theme_core'), 'value' => $instance['widget_area_columns'], 'xtra' => "min='1' max='4'"))
		."</div>";
	}
}

class widget_theme_core_logo extends WP_Widget
{
	function __construct()
	{
		$widget_ops = array(
			'classname' => 'theme_logo',
			'description' => __("Display Logo", 'lang_theme_core')
		);

		$this->arr_default = array(
			'logo_url' => '',
			'logo_display' => 'all',
			'logo_title' => '',
			'logo_image' => '',
			'logo_description' => '',
		);

		parent::__construct('theme-logo-widget', __("Logo", 'lang_theme_core'), $widget_ops);
	}

	function widget($args, $instance)
	{
		extract($args);
		$instance = wp_parse_args((array)$instance, $this->arr_default);

		$obj_theme_core = new mf_theme_core();

		echo $before_widget
			.$obj_theme_core->get_logo(array('url' => $instance['logo_url'], 'display' => $instance['logo_display'], 'title' => $instance['logo_title'], 'image' => $instance['logo_image'], 'description' => $instance['logo_description']))
		.$after_widget;
	}

	function update($new_instance, $old_instance)
	{
		$instance = $old_instance;
		$new_instance = wp_parse_args((array)$new_instance, $this->arr_default);

		$instance['logo_url'] = sanitize_text_field($new_instance['logo_url']);
		$instance['logo_display'] = sanitize_text_field($new_instance['logo_display']);
		$instance['logo_title'] = sanitize_text_field($new_instance['logo_title']);
		$instance['logo_image'] = sanitize_text_field($new_instance['logo_image']);
		$instance['logo_description'] = sanitize_text_field($new_instance['logo_description']);

		return $instance;
	}

	function form($instance)
	{
		$instance = wp_parse_args((array)$instance, $this->arr_default);

		$arr_data = array(
			'all' => __("Logo and Tagline", 'lang_theme_core'),
			'title' => __("Logo", 'lang_theme_core'),
			'tagline' => __("Tagline", 'lang_theme_core'),
		);

		echo "<div class='mf_form'>
			<p>".__("If these are left empty, the chosen logo for the site will be displayed. If there is no chosen logo the site name will be displayed instead.", 'lang_theme_core')."</p>"
			.show_textfield(array('type' => 'url', 'name' => $this->get_field_name('logo_url'), 'text' => __("URL", 'lang_theme_core'), 'value' => $instance['logo_url'], 'placeholder' => get_site_url()))
			.show_select(array('data' => $arr_data, 'name' => $this->get_field_name('logo_display'), 'text' => __("What to Display", 'lang_theme_core'), 'value' => $instance['logo_display']));

			if($instance['logo_display'] != 'tagline')
			{
				if($instance['logo_image'] == '')
				{
					echo show_textfield(array('name' => $this->get_field_name('logo_title'), 'text' => __("Logo", 'lang_theme_core'), 'value' => $instance['logo_title'], 'xtra' => " id='logo-title'"));
				}

				if($instance['logo_title'] == '')
				{
					echo get_media_library(array('name' => $this->get_field_name('logo_image'), 'value' => $instance['logo_image'], 'type' => 'image'));
				}
			}

			if($instance['logo_display'] != 'title')
			{
				echo show_textfield(array('name' => $this->get_field_name('logo_description'), 'text' => __("Tagline", 'lang_theme_core'), 'value' => $instance['logo_description']));
			}

		echo "</div>";
	}
}

class widget_theme_core_search extends WP_Widget
{
	function __construct()
	{
		$widget_ops = array(
			'classname' => 'theme_search',
			'description' => __("Display Search Form", 'lang_theme_core')
		);

		$this->arr_default = array(
			'search_placeholder' => "",
			'search_animate' => 'yes',
		);

		parent::__construct('theme-search-widget', __("Search", 'lang_theme_core'), $widget_ops);
	}

	function widget($args, $instance)
	{
		extract($args);
		$instance = wp_parse_args((array)$instance, $this->arr_default);

		echo get_search_theme_core(array('placeholder' => $instance['search_placeholder'], 'animate' => (isset($instance['search_animate']) ? $instance['search_animate'] : 'yes')));
	}

	function update($new_instance, $old_instance)
	{
		$instance = $old_instance;
		$new_instance = wp_parse_args((array)$new_instance, $this->arr_default);

		$instance['search_placeholder'] = sanitize_text_field($new_instance['search_placeholder']);
		$instance['search_animate'] = sanitize_text_field($new_instance['search_animate']);

		return $instance;
	}

	function form($instance)
	{
		$instance = wp_parse_args((array)$instance, $this->arr_default);

		echo "<div class='mf_form'>"
			.show_textfield(array('name' => $this->get_field_name('search_placeholder'), 'text' => __("Placeholder", 'lang_theme_core'), 'value' => $instance['search_placeholder']))
			.show_select(array('data' => get_yes_no_for_select(), 'name' => $this->get_field_name('search_animate'), 'text' => __("Animate", 'lang_theme_core'), 'value' => $instance['search_animate']))
		."</div>";
	}
}

class widget_theme_core_news extends WP_Widget
{
	function __construct()
	{
		$widget_ops = array(
			'classname' => 'theme_news',
			'description' => __("Display News/Posts", 'lang_theme_core')
		);

		$this->arr_default = array(
			'news_title' => "",
			'news_type' => 'original',
			'news_categories' => array(),
			'news_amount' => 1,
			'news_columns' => 0,
			'news_time_limit' => 0,
			'news_display_arrows' => 'no',
			'news_autoscroll_time' => 5,
			'news_display_title' => 'yes',
			'news_display_excerpt' => 'yes',
			'news_page' => 0,
		);

		parent::__construct('theme-news-widget', __("News", 'lang_theme_core'), $widget_ops);
	}

	function get_posts($instance)
	{
		global $wpdb;

		$this->arr_news = array();

		if(!($instance['news_amount'] > 0)){	$instance['news_amount'] = 3;}

		$query_join = $query_where = "";

		if(count($instance['news_categories']) > 0)
		{
			$query_join .= " INNER JOIN ".$wpdb->term_relationships." ON ".$wpdb->posts.".ID = ".$wpdb->term_relationships.".object_id INNER JOIN ".$wpdb->term_taxonomy." USING (term_taxonomy_id)";
			$query_where .= " AND term_id IN('".implode("','", $instance['news_categories'])."')";
		}

		if($instance['news_time_limit'] > 0)
		{
			$query_where .= " AND post_date > DATE_SUB(NOW(), INTERVAL ".esc_sql($instance['news_time_limit'])." HOUR)";
		}

		$result = $wpdb->get_results("SELECT ID, post_title, post_excerpt FROM ".$wpdb->posts.$query_join." WHERE post_type = 'post' AND post_status = 'publish'".$query_where." ORDER BY post_date DESC LIMIT 0, ".$instance['news_amount']);

		if($wpdb->num_rows > 0)
		{
			$post_thumbnail_size = 'large'; //$wpdb->num_rows > 2 ? 'medium' :

			foreach($result as $r)
			{
				$post_id = $r->ID;
				$post_title = $r->post_title;
				$post_excerpt = $r->post_excerpt;

				$post_thumbnail = '';

				if(has_post_thumbnail($post_id))
				{
					$post_thumbnail = get_the_post_thumbnail($post_id, $post_thumbnail_size);
				}

				if($post_thumbnail == '' && $instance['news_amount'] > 1)
				{
					$post_thumbnail = get_image_fallback();
				}

				$this->arr_news[$post_id] = array(
					'title' => $post_title,
					'url' => get_permalink($post_id),
					'image' => $post_thumbnail,
					'excerpt' => $post_excerpt,
				);
			}
		}
	}

	function widget($args, $instance)
	{
		extract($args);
		$instance = wp_parse_args((array)$instance, $this->arr_default);

		$this->get_posts($instance);

		$rows = count($this->arr_news);

		if($rows > 0)
		{
			$display_news_scroll = ($rows > 3 && $instance['news_display_arrows'] == 'yes');

			if($display_news_scroll)
			{
				$plugin_include_url = plugin_dir_url(__FILE__);
				$plugin_version = get_plugin_version(__FILE__);

				mf_enqueue_style('style_theme_news_scroll', $plugin_include_url."style_news_scroll.css", $plugin_version); //Should be set in wp_head instead
				mf_enqueue_script('script_theme_news_scroll', $plugin_include_url."script_news_scroll.js", $plugin_version);
			}

			echo $before_widget;

				if($instance['news_title'] != '')
				{
					echo $before_title
						.$instance['news_title']
					.$after_title;
				}

				$widget_class = "section ".$instance['news_type'];
				$widget_xtra = "";

				if($rows > 1)
				{
					$widget_class .= " news_multiple";

					if($display_news_scroll)
					{
						$widget_class .= " news_scroll";
					}

					if($instance['news_autoscroll_time'] > 0)
					{
						$widget_xtra .= " data-autoscroll='".$instance['news_autoscroll_time']."'";
					}
				}

				else
				{
					$widget_class .= " news_single";
				}

				echo "<div class='".$widget_class."'".$widget_xtra.">";

					if($rows > 1)
					{
						if(!($instance['news_columns'] > 0))
						{
							$instance['news_columns'] = $rows % 3 == 0 || $rows > 4 || $instance['news_type'] == 'postit' ? 3 : 2;
						}

						echo "<ul class='text_columns columns_".$instance['news_columns']."' data-columns='".$instance['news_columns']."'>";

							foreach($this->arr_news as $page)
							{
								if($instance['news_type'] == 'postit')
								{
									$page['excerpt'] = shorten_text(array('string' => $page['excerpt'], 'limit' => (300 - $instance['news_columns'] * 60)));
								}

								echo "<li>
									<a href='".$page['url']."'>";

										if(in_array($instance['news_type'], array('original', 'simple')))
										{
											echo "<div class='image'>".$page['image']."</div>";
										}

										if($instance['news_display_title'] == 'yes')
										{
											echo "<h4>".$page['title']."</h4>";
										}

										if($instance['news_display_excerpt'] == 'yes' && in_array($instance['news_type'], array('postit', 'simple')))
										{
											echo apply_filters('the_content', $page['excerpt']);
										}

									echo "</a>
								</li>";
							}

						echo "</ul>";

						if($instance['news_page'] > 0)
						{
							echo "<p class='read_more'><a href='".get_permalink($instance['news_page'])."'>".__("Read More", 'lang_theme_core')."</a></p>";
						}
					}

					else
					{
						foreach($this->arr_news as $page)
						{
							echo "<a href='".$page['url']."'>";

								if($page['image'] != '')
								{
									echo "<div class='image'>".$page['image']."</div>";
								}

								echo "<h4>".$page['title']."</h4>"
								.apply_filters('the_content', $page['excerpt'])
								."<p class='read_more'>".__("Read More", 'lang_theme_core')."</p>"
							."</a>";
						}
					}

				echo "</div>"
			.$after_widget;
		}
	}

	function update($new_instance, $old_instance)
	{
		$instance = $old_instance;
		$new_instance = wp_parse_args((array)$new_instance, $this->arr_default);

		$instance['news_title'] = sanitize_text_field($new_instance['news_title']);
		$instance['news_type'] = sanitize_text_field($new_instance['news_type']);
		$instance['news_categories'] = is_array($new_instance['news_categories']) ? $new_instance['news_categories'] : array();
		$instance['news_amount'] = sanitize_text_field($new_instance['news_amount']);
		$instance['news_columns'] = sanitize_text_field($new_instance['news_columns']);
		$instance['news_time_limit'] = sanitize_text_field($new_instance['news_time_limit']);
		$instance['news_display_arrows'] = sanitize_text_field($new_instance['news_display_arrows']);
		$instance['news_autoscroll_time'] = $new_instance['news_autoscroll_time'] >= 5 ? sanitize_text_field($new_instance['news_autoscroll_time']) : 0;
		$instance['news_display_title'] = sanitize_text_field($new_instance['news_display_title']);
		$instance['news_display_excerpt'] = sanitize_text_field($new_instance['news_display_excerpt']);
		$instance['news_page'] = sanitize_text_field($new_instance['news_page']);

		return $instance;
	}

	function get_news_type_for_select()
	{
		return array(
			'original' => __("Default", 'lang_theme_core'),
			'postit' => __("Post It", 'lang_theme_core'),
			'simple' => __("Simple", 'lang_theme_core'),
		);
	}

	function form($instance)
	{
		$instance = wp_parse_args((array)$instance, $this->arr_default);

		$instance_temp = $instance;
		$instance_temp['news_amount'] = 9;
		$instance_temp['news_time_limit'] = 0;
		$this->get_posts($instance_temp);

		echo "<div class='mf_form'>";

			$count_temp = count($this->arr_news);

			if($count_temp > 0)
			{
				$arr_data_pages = array();
				get_post_children(array('add_choose_here' => true), $arr_data_pages);

				echo show_textfield(array('name' => $this->get_field_name('news_title'), 'text' => __("Title", 'lang_theme_core'), 'value' => $instance['news_title'], 'xtra' => " id='news-title'"))
				.show_select(array('data' => $this->get_news_type_for_select(), 'name' => $this->get_field_name('news_type'), 'text' => __("Design", 'lang_theme_core'), 'value' => $instance['news_type']))
				.show_select(array('data' => get_categories_for_select(), 'name' => $this->get_field_name('news_categories')."[]", 'text' => __("Categories", 'lang_theme_core'), 'value' => $instance['news_categories']))
				."<div class='flex_flow'>"
					.show_textfield(array('type' => 'number', 'name' => $this->get_field_name('news_amount'), 'text' => __("Amount", 'lang_theme_core'), 'value' => $instance['news_amount'], 'xtra' => " min='0' max='".$count_temp."'"));

					if($count_temp > 3)
					{
						echo show_textfield(array('type' => 'number', 'name' => $this->get_field_name('news_columns'), 'text' => __("Columns", 'lang_theme_core'), 'value' => $instance['news_columns'], 'xtra' => " min='1' max='4'"));
					}

				echo "</div>";

				if($instance['news_amount'] == 1 && $instance['news_type'] == 'postit')
				{
					echo "<div class='flex_flow'>";

						if($instance['news_amount'] == 1)
						{
							echo show_textfield(array('type' => 'number', 'name' => $this->get_field_name('news_time_limit'), 'text' => __("Time Limit", 'lang_theme_core'), 'value' => $instance['news_time_limit'], 'xtra' => " min='0' max='240'", 'suffix' => __("h", 'lang_theme_core')));
						}

						if($instance['news_type'] == 'postit')
						{
							echo show_select(array('data' => get_yes_no_for_select(), 'name' => $this->get_field_name('news_display_arrows'), 'text' => __("Display Arrows", 'lang_theme_core'), 'value' => $instance['news_display_arrows']));

							if($instance['news_display_arrows'] == 'yes')
							{
								echo show_textfield(array('type' => 'number', 'name' => $this->get_field_name('news_autoscroll_time'), 'text' => __("Autoscroll", 'lang_theme_core'), 'value' => $instance['news_autoscroll_time'], 'xtra' => " min='0' max='60'"));
							}
						}

					echo "</div>";
				}

				echo "<div class='flex_flow'>"
					.show_select(array('data' => get_yes_no_for_select(), 'name' => $this->get_field_name('news_display_title'), 'text' => __("Display Title", 'lang_theme_core'), 'value' => $instance['news_display_title']))
					.show_select(array('data' => get_yes_no_for_select(), 'name' => $this->get_field_name('news_display_excerpt'), 'text' => __("Display Excerpt", 'lang_theme_core'), 'value' => $instance['news_display_excerpt']))
				."</div>"
				.show_select(array('data' => $arr_data_pages, 'name' => $this->get_field_name('news_page'), 'text' => __("Read More", 'lang_theme_core'), 'value' => $instance['news_page']));
			}

			else
			{
				echo __("There are no posts to display in this widget", 'lang_theme_core');
			}

		echo "</div>";
	}
}

class widget_theme_core_info extends WP_Widget
{
	function __construct()
	{
		$widget_ops = array(
			'classname' => 'theme_info',
			'description' => __("Display Info Module", 'lang_theme_core')
		);

		$this->arr_default = array(
			'info_image' => '',
			'info_title' => '',
			'info_content' => '',
			'info_button_text' => '',
			'info_page' => 0,
			'info_link' => '',
		);

		parent::__construct('theme-info-widget', __("Info Module", 'lang_theme_core'), $widget_ops);
	}

	function widget($args, $instance)
	{
		extract($args);
		$instance = wp_parse_args((array)$instance, $this->arr_default);

		if($instance['info_page'] > 0){			$button_link = get_permalink($instance['info_page']);}
		else if($instance['info_link'] != ''){	$button_link = $instance['info_link'];}
		else{									$button_link = "#";}

		echo $before_widget
			."<div class='section'>
				<div>";

					if($instance['info_image'] != '')
					{
						echo "<div class='image'><a href='".$button_link."'>".render_image_tag(array('src' => $instance['info_image']))."</a></div>";
					}

					echo "<div class='content'>";

						if($instance['info_title'] != '')
						{
							echo $before_title
								.$instance['info_title']
							.$after_title;
						}

						if($instance['info_content'] != '')
						{
							echo apply_filters('the_content', $instance['info_content']);
						}

						if($instance['info_button_text'] != '')
						{
							echo "<div class='form_button'>
								<a href='".$button_link."' class='button'>"
									.$instance['info_button_text']
								."</a>
							</div>";
						}

					echo "</div>
				</div>
			</div>"
		.$after_widget;
	}

	function update($new_instance, $old_instance)
	{
		$instance = $old_instance;
		$new_instance = wp_parse_args((array)$new_instance, $this->arr_default);

		$instance['info_image'] = sanitize_text_field($new_instance['info_image']);
		$instance['info_title'] = sanitize_text_field($new_instance['info_title']);
		$instance['info_content'] = sanitize_text_field($new_instance['info_content']);
		$instance['info_button_text'] = sanitize_text_field($new_instance['info_button_text']);
		$instance['info_page'] = sanitize_text_field($new_instance['info_page']);
		$instance['info_link'] = esc_url_raw($new_instance['info_link']);

		return $instance;
	}

	function form($instance)
	{
		$instance = wp_parse_args((array)$instance, $this->arr_default);

		echo "<div class='mf_form'>"
			.get_media_library(array('name' => $this->get_field_name('info_image'), 'value' => $instance['info_image'], 'type' => 'image'))
			.show_textfield(array('name' => $this->get_field_name('info_title'), 'text' => __("Title", 'lang_theme_core'), 'value' => $instance['info_title'], 'xtra' => " id='info-title'"))
			.show_textarea(array('name' => $this->get_field_name('info_content'), 'text' => __("Content", 'lang_theme_core'), 'value' => $instance['info_content']))
			.show_textfield(array('name' => $this->get_field_name('info_button_text'), 'text' => __("Button Text", 'lang_theme_core'), 'value' => $instance['info_button_text'])); //, 'xtra' => " condition_type='hide_if_empty' condition_field='info_button_links'"
			//echo "<div class='widgets-right .info_button_links'>";

				if($instance['info_button_text'] != '')
				{
					if($instance['info_link'] == '')
					{
						$arr_data = array();
						get_post_children(array('add_choose_here' => true), $arr_data);

						echo show_select(array('data' => $arr_data, 'name' => $this->get_field_name('info_page'), 'text' => __("Page", 'lang_theme_core'), 'value' => $instance['info_page'])); //, 'xtra' => " class='info_page' condition_type='show_if_empty' condition_field='widgets-right .info_link'"
					}

					if(!($instance['info_page'] > 0))
					{
						echo show_textfield(array('type' => 'url', 'name' => $this->get_field_name('info_link'), 'text' => __("Link", 'lang_theme_core'), 'value' => $instance['info_link'])); //, 'xtra' => " class='info_link' condition_type='show_if_empty' condition_field='widgets-right .info_page'"
					}
				}

			//echo "</div>";
		echo "</div>";
	}
}

class widget_theme_core_related extends WP_Widget
{
	function __construct()
	{
		$widget_ops = array(
			'classname' => 'theme_news',
			'description' => __("Display Related Posts", 'lang_theme_core')
		);

		$this->arr_default = array(
			'news_title' => '',
			'news_post_type' => 'post',
			'news_categories' => array(),
			'news_amount' => 1,
			'news_columns' => 1,
		);

		parent::__construct('theme-related-news-widget', __("Related Posts", 'lang_theme_core'), $widget_ops);
	}

	function get_posts($instance)
	{
		global $wpdb, $post;

		$this->arr_news = array();

		if(isset($post) && isset($post->ID))
		{
			//if(!($instance['news_amount'] > 0)){	$instance['news_amount'] = 3;}

			$post_id = $post->ID;

			$query_join = $query_where = "";

			$arr_related_categories = array();

			if(count($instance['news_categories']) > 0)
			{
				$arr_related_categories = $instance['news_categories'];
			}

			else
			{
				$arr_categories = get_the_category($post_id);

				if(count($arr_categories) > 0)
				{
					foreach($arr_categories as $category)
					{
						$arr_related_categories[] = $category->term_id;
					}
				}
			}

			if(count($arr_related_categories) > 0)
			{
				$query_join .= " INNER JOIN ".$wpdb->term_relationships." ON ".$wpdb->posts.".ID = ".$wpdb->term_relationships.".object_id INNER JOIN ".$wpdb->term_taxonomy." USING (term_taxonomy_id)";
				$query_where .= " AND term_id IN('".implode("','", $arr_related_categories)."')";
			}

			$result = $wpdb->get_results($wpdb->prepare("SELECT ID, post_title, post_excerpt FROM ".$wpdb->posts.$query_join." WHERE post_type = %s AND post_status = %s AND ID != '%d'".$query_where." GROUP BY post_title ORDER BY post_date DESC LIMIT 0, ".$instance['news_amount'], $instance['news_post_type'], 'publish', $post_id));

			//do_log("Related: ".$wpdb->last_query);

			if($wpdb->num_rows > 0)
			{
				$post_thumbnail_size = 'large'; //$wpdb->num_rows > 2 ? 'medium' :

				foreach($result as $r)
				{
					$post_id = $r->ID;
					$post_title = $r->post_title;
					$post_excerpt = $r->post_excerpt;

					$post_thumbnail = '';

					if(has_post_thumbnail($post_id))
					{
						$post_thumbnail = get_the_post_thumbnail($post_id, $post_thumbnail_size);
					}

					if($post_thumbnail == '' && $instance['news_amount'] > 1)
					{
						$post_thumbnail = get_image_fallback();
					}

					$this->arr_news[$post_id] = array(
						'title' => $post_title,
						'url' => get_permalink($post_id),
						'image' => $post_thumbnail,
						'excerpt' => $post_excerpt,
					);
				}
			}
		}
	}

	function widget($args, $instance)
	{
		extract($args);
		$instance = wp_parse_args((array)$instance, $this->arr_default);

		$this->get_posts($instance);

		if(count($this->arr_news) > 0)
		{
			echo $before_widget;

				if($instance['news_title'] != '')
				{
					echo $before_title
						.$instance['news_title']
					.$after_title;
				}

				echo "<div class='section original'>
					<ul class='text_columns columns_".$instance['news_columns']."'>";

						$i = 0;

						foreach($this->arr_news as $page)
						{
							echo "<li>
								<a href='".$page['url']."'>
									<div class='image'>".$page['image']."</div>
									<h4>".$page['title']."</h4>
								</a>
							</li>";

							$i++;
						}

						for($j = 0; $j <= ($i % $instance['news_columns']); $j++)
						{
							echo "<li></li>";
						}

					echo "</ul>
				</div>"
			.$after_widget;
		}
	}

	function update($new_instance, $old_instance)
	{
		$instance = $old_instance;
		$new_instance = wp_parse_args((array)$new_instance, $this->arr_default);

		$instance['news_title'] = sanitize_text_field($new_instance['news_title']);
		$instance['news_post_type'] = sanitize_text_field($new_instance['news_post_type']);
		$instance['news_categories'] = is_array($new_instance['news_categories']) ? $new_instance['news_categories'] : array();
		$instance['news_amount'] = sanitize_text_field($new_instance['news_amount']);
		$instance['news_columns'] = sanitize_text_field($new_instance['news_columns']);

		return $instance;
	}

	function form($instance)
	{
		$instance = wp_parse_args((array)$instance, $this->arr_default);

		/*$instance_temp = $instance;
		$instance_temp['news_amount'] = 9;
		$this->get_posts($instance_temp);*/

		echo "<div class='mf_form'>"
			.show_textfield(array('name' => $this->get_field_name('news_title'), 'text' => __("Title", 'lang_theme_core'), 'value' => $instance['news_title'], 'xtra' => " id='news-title'"))
			.show_select(array('data' => get_post_types_for_select(array('include' => array('types'), 'add_is' => false)), 'name' => $this->get_field_name('news_post_type'), 'value' => $instance['news_post_type']))
			.show_select(array('data' => get_categories_for_select(), 'name' => $this->get_field_name('news_categories')."[]", 'text' => __("Categories", 'lang_theme_core'), 'value' => $instance['news_categories']));

			/*$count_temp = count($this->arr_news);

			if($count_temp > 0)
			{*/
				echo "<div class='flex_flow'>"
					.show_textfield(array('type' => 'number', 'name' => $this->get_field_name('news_amount'), 'text' => __("Amount", 'lang_theme_core'), 'value' => $instance['news_amount'], 'xtra' => " min='1'")); // max='".$count_temp."'

					/*if($count_temp > 3)
					{*/
						echo show_textfield(array('type' => 'number', 'name' => $this->get_field_name('news_columns'), 'text' => __("Columns", 'lang_theme_core'), 'value' => $instance['news_columns'], 'xtra' => " min='1' max='4'"));
					//}

				echo "</div>";
			/*}

			else
			{
				echo __("There are no posts to display in this widget", 'lang_theme_core');
			}*/

		echo "</div>";
	}
}

class widget_theme_core_promo extends WP_Widget
{
	function __construct()
	{
		$widget_ops = array(
			'classname' => 'theme_promo theme_news',
			'description' => __("Promote Pages", 'lang_theme_core')
		);

		$this->arr_default = array(
			'promo_title' => "",
			'promo_include' => array(),
			'promo_page_titles' => 'yes',
		);

		parent::__construct('theme-promo-widget', __("Promotion", 'lang_theme_core'), $widget_ops);
	}

	function widget($args, $instance)
	{
		global $wpdb;

		extract($args);
		$instance = wp_parse_args((array)$instance, $this->arr_default);

		if(count($instance['promo_include']) > 0)
		{
			$arr_pages = array();

			$result = $wpdb->get_results("SELECT ID, post_title, post_content FROM ".$wpdb->posts." WHERE post_type = 'page' AND post_status = 'publish' AND ID IN('".implode("','", $instance['promo_include'])."') ORDER BY menu_order ASC");

			if($wpdb->num_rows > 0)
			{
				$post_thumbnail_size = 'large';

				foreach($result as $r)
				{
					$post_id = $r->ID;
					$post_title = $r->post_title;
					$post_content = $r->post_content;

					if(strlen($post_content) < 60 && preg_match("/youtube\.com|youtu\.be/i", $post_content))
					{
						$arr_pages[$post_id] = array(
							'content' => $post_content,
						);
					}

					else
					{
						$post_thumbnail = "";

						if(has_post_thumbnail($post_id))
						{
							$post_thumbnail = get_the_post_thumbnail($post_id, $post_thumbnail_size);
						}

						if($post_thumbnail == '')
						{
							$post_thumbnail = get_image_fallback();
						}

						$post_url = get_permalink($post_id);

						$arr_pages[$post_id] = array(
							'title' => $post_title,
							'url' => $post_url,
							'image' => $post_thumbnail,
						);
					}
				}
			}

			$rows = count($arr_pages);

			if($rows > 0)
			{
				echo $before_widget;

					if($instance['promo_title'] != '')
					{
						echo $before_title
							.$instance['promo_title']
						.$after_title;
					}

					echo "<div class='section original'>
						<ul class='text_columns ".($rows % 3 == 0 || $rows > 4 ? "columns_3" : "columns_2")."'>";

							foreach($arr_pages as $page)
							{
								if(isset($page['image']))
								{
									echo "<li>
										<a href='".$page['url']."'>
											<div class='image'>".$page['image']."</div>";

											if($instance['promo_page_titles'] == 'yes')
											{
												echo "<h4>".$page['title']."</h4>";
											}

										echo "</a>
									</li>";
								}

								else
								{
									echo "<li>
										<div class='video'>".apply_filters('the_content', $page['content'])."</div>
									</li>";
								}
							}

						echo "</ul>
					</div>"
				.$after_widget;
			}
		}
	}

	function update($new_instance, $old_instance)
	{
		$instance = $old_instance;
		$new_instance = wp_parse_args((array)$new_instance, $this->arr_default);

		$instance['promo_title'] = sanitize_text_field($new_instance['promo_title']);
		$instance['promo_include'] = is_array($new_instance['promo_include']) ? $new_instance['promo_include'] : array();
		$instance['promo_page_titles'] = sanitize_text_field($new_instance['promo_page_titles']);

		return $instance;
	}

	function form($instance)
	{
		$instance = wp_parse_args((array)$instance, $this->arr_default);

		$arr_data = array();
		get_post_children(array('post_type' => 'page', 'order_by' => 'post_title'), $arr_data);

		echo "<div class='mf_form'>"
			.show_textfield(array('name' => $this->get_field_name('promo_title'), 'text' => __("Title", 'lang_theme_core'), 'value' => $instance['promo_title'], 'xtra' => " id='promo-title'"))
			.show_select(array('data' => $arr_data, 'name' => $this->get_field_name('promo_include')."[]", 'text' => __("Pages", 'lang_theme_core'), 'value' => $instance['promo_include']))
			.show_select(array('data' => get_yes_no_for_select(), 'name' => $this->get_field_name('promo_page_titles'), 'text' => __("Display Titles", 'lang_theme_core'), 'value' => $instance['promo_page_titles']))
		."</div>";
	}
}