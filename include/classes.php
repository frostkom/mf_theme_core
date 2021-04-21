<?php

class mf_theme_core
{
	function __construct()
	{
		$this->meta_prefix = 'mf_theme_core_';
		$this->lang_key = 'lang_theme_core';

		$this->options_params = $this->options = $this->options_fonts = array();

		$this->title_format = "[page_title][site_title][site_description][page_number]";
	}

	function is_site_public()
	{
		return (get_option('blog_public') == 1 && get_option('setting_no_public_pages') != 'yes' && get_option('setting_theme_core_login') != 'yes');
	}

	function is_login_page()
	{
		return in_array($GLOBALS['pagenow'], array('wp-login.php')); //, 'wp-register.php'
	}

	function get_theme_dir_name()
	{
		return str_replace(get_theme_root()."/", "", get_template_directory());
	}

	function get_theme_slug()
	{
		$theme_name = wp_get_theme();

		return sanitize_title($theme_name);
	}

	function get_params_for_select()
	{
		$arr_data = array();

		$options_params = $this->get_params_theme_core();
		$arr_theme_mods = get_theme_mods();

		$last_category = '';

		foreach($options_params as $param_key => $param)
		{
			if(isset($param['category']))
			{
				$arr_data['opt_start_'.$param['id']] = $param['category'];

				$last_category = $param['id'];
				$has_children = false;
			}

			else if(isset($param['category_end']))
			{
				if($has_children == true)
				{
					$arr_data['opt_end'] = "";
				}

				else if($last_category != '')
				{
					unset($arr_data['opt_start_'.$last_category]);

					$last_category = '';
				}
			}

			else
			{
				$id = $param['id'];
				$title = $param['title'];

				if(isset($arr_theme_mods[$id]) && $arr_theme_mods[$id] != '')
				{
					$arr_data[$id] = $title;

					$has_children = true;
				}
			}
		}

		return $arr_data;
	}

	function get_themes_for_select()
	{
		$arr_data = array(
			'' => "-- ".__("Choose Here", $this->lang_key)." --",
		);

		$arr_themes = wp_get_themes(array('errors' => false, 'allowed' => true));

		foreach($arr_themes as $key => $value)
		{
			$arr_data[$key] = $value['Name'];
		}

		return $arr_data;
	}

	function get_meta_boxes_for_select()
	{
		return array(
			'authordiv' => __("Author", $this->lang_key),
			'categorydiv' => __("Categories", $this->lang_key),
			'commentstatusdiv' => __("Discussion", $this->lang_key),
			'commentsdiv' => __("Comments", $this->lang_key),
			'pageparentdiv' => __("Page Attributes", $this->lang_key),
			'postcustom' => __("Custom Fields", $this->lang_key),
			'postexcerpt' => __("Excerpt", $this->lang_key),
			'postimagediv' => __("Featured Image", $this->lang_key),
			'revisionsdiv' => __("Revisions", $this->lang_key),
			'slugdiv' => __("Slug", $this->lang_key),
			'tagsdiv-post_tag' => __("Tags", $this->lang_key),
			'trackbacksdiv' => __("Trackbacks", $this->lang_key),
		); //'formatdiv', 'tagsdiv',
	}

	function cron_base()
	{
		$obj_cron = new mf_cron();
		$obj_cron->start(__CLASS__);

		if($obj_cron->is_running == false)
		{
			$this->publish_posts();

			/* Optimize */
			#########################
			if(get_option('option_database_optimized') < date("Y-m-d H:i:s", strtotime("-7 day")))
			{
				$this->do_optimize();
			}
			#########################

			if($this->is_theme_active())
			{
				$this->check_style_source();

				/* Delete old uploads */
				#######################
				$theme_dir_name = $this->get_theme_dir_name();

				if($theme_dir_name != '')
				{
					list($upload_path, $upload_url) = get_uploads_folder($theme_dir_name);

					get_file_info(array('path' => $upload_path, 'callback' => 'delete_files', 'time_limit' => (60 * 60 * 24 * 60))); //60 days
				}
				#######################
			}
		}

		$obj_cron->end();
	}

	function init()
	{
		if(get_option('setting_maintenance_page') > 0 && get_option('setting_activate_maintenance') == 'yes')
		{
			if(IS_SUPER_ADMIN || $this->is_login_page())
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

				wp_redirect(wp_get_referer());
				die();
			}
		}*/
	}

	function get_flag_image($id = 0)
	{
		global $wpdb, $obj_base;

		if($id > 0)
		{
			switch_to_blog($id);

			$blog_language = $wpdb->get_var($wpdb->prepare("SELECT option_value FROM ".$wpdb->options." WHERE option_name = %s", 'WPLANG'));

			restore_current_blog();
		}

		else
		{
			$blog_language = get_bloginfo('language');
		}

		if(!isset($obj_base))
		{
			$obj_base = new mf_base();
		}

		return $obj_base->get_flag_image($blog_language);
	}

	function get_site_status()
	{
		if(get_option('setting_maintenance_page') > 0 && get_option('setting_activate_maintenance') == 'yes')
		{
			return 'maintenance_mode';
		}

		else if(get_option('setting_no_public_pages') == 'yes')
		{
			return 'not_public';
		}

		else if(get_option('setting_theme_core_login') == 'yes')
		{
			return 'requires_login';
		}

		else if(get_option('blog_public') == 0)
		{
			return 'no_index';
		}

		else
		{
			return 'public';
		}
	}

	function get_site_status_data($data = array())
	{
		switch($this->get_site_status())
		{
			case 'maintenance_mode':
				$color = "color_red";
				$icon = "fas fa-hard-hat";
				$text = __("Maintenance Mode Activated", $this->lang_key);
			break;

			case 'not_public':
				if($data['type'] == 'admin_bar')
				{
					global $wp_admin_bar;

					$wp_admin_bar->remove_menu('site-name');
				}

				$color = "color_red";
				$icon = "fas fa-eye-slash";
				$text = __("No Public Pages", $this->lang_key);
			break;

			case 'requires_login':
				$this->site_url = get_home_url();

				$color = "color_red";
				$icon = "fas fa-user-lock";
				$text = __("Requires Login", $this->lang_key);
			break;

			case 'no_index':
				$this->site_url = get_home_url();

				$color = "color_yellow";
				$icon = "fas fa-robot";
				$text = __("No Index", $this->lang_key);
			break;

			default:
			case 'public':
				$this->site_url = get_home_url();

				$color = "color_green";
				$icon = "fas fa-eye";
				$text = __("Public", $this->lang_key);
			break;
		}

		return array($color, $icon, $text);
	}

	function wp_before_admin_bar_render()
	{
		global $wp_admin_bar;

		if(IS_ADMIN)
		{
			$this->site_url = $icon = "";

			list($color, $icon, $text) = $this->get_site_status_data(array('type' => 'admin_bar'));

			$flag_image = $this->get_flag_image();

			$title = "";

			if($this->site_url != '')
			{
				$title .= "<a href='".$this->site_url."' class='".$color."'>";
			}

			else
			{
				$title .= "<span".(isset($color) && $color != '' ? " class='".$color."'" : "").">";
			}

				if($flag_image != '')
				{
					$title .= "<div class='flex_flow tight'>
						<img src='".$flag_image."'>&nbsp;
						<span>";
				}

					// "#wpadminbar *" overrides style for FA icons
					/*if($icon != '')
					{
						$title .= "<i class='".$icon."' title='".$text."'></i>";
					}

					else
					{*/
						$title .= $text;
					//}

				if($flag_image != '')
				{
						$title .= "</span>
					</div>";
				}

			if($this->site_url != '')
			{
				$title .= "</a>";
			}

			else
			{
				$title .= "</span>";
			}

			$wp_admin_bar->add_node(array(
				'id' => 'live',
				'title' => $title,
			));
		}
	}

	function settings_theme_core()
	{
		$options_area_orig = $options_area = __FUNCTION__;

		add_settings_section($options_area, "", array($this, $options_area."_callback"), BASE_OPTIONS_PAGE);

		$arr_settings = array();

		if(get_option('blog_public') == 0 || get_option('setting_no_public_pages') == 'yes' || get_option('setting_theme_core_login') == 'yes')
		{
			$arr_settings['setting_no_public_pages'] = __("Always redirect visitors to the login page", $this->lang_key);

			if(get_option('setting_no_public_pages') != 'yes')
			{
				$arr_settings['setting_theme_core_login'] = __("Require login for public site", $this->lang_key);
			}
		}

		if($this->is_theme_active())
		{
			$arr_settings['setting_theme_core_templates'] = __("Templates", $this->lang_key);
		}

		$arr_settings['setting_theme_core_hidden_meta_boxes'] = __("Hidden Meta Boxes", $this->lang_key);

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
				$arr_settings['setting_send_email_on_draft'] = __("Send Email when Draft is Saved", $this->lang_key);
			}

			else
			{
				delete_option('setting_send_email_on_draft');
			}

			$setting_base_template_site = get_option('setting_base_template_site');

			if($setting_base_template_site != '')
			{
				$arr_settings['setting_theme_ignore_style_on_restore'] = __("Ignore Style on Restore", $this->lang_key);
			}

			else
			{
				delete_option('setting_theme_ignore_style_on_restore');
			}

			/*if(is_plugin_active("css-hero-ce/css-hero-main.php"))
			{
				$arr_settings['setting_theme_css_hero'] = __("CSS Hero Support", $this->lang_key);
			}

			else
			{
				delete_option('setting_theme_css_hero');
			}*/
		}

		if(IS_SUPER_ADMIN)
		{
			$arr_settings['setting_theme_enable_wp_api'] = __("Enable XML-RPC", $this->lang_key);
			$arr_settings['setting_theme_optimize'] = __("Optimize", $this->lang_key);
		}

		show_settings_fields(array('area' => $options_area, 'object' => $this, 'settings' => $arr_settings));

		// Public Site
		############################
		if(get_option('setting_no_public_pages') != 'yes')
		{
			$options_area = $options_area_orig."_public";

			add_settings_section($options_area, "", array($this, $options_area."_callback"), BASE_OPTIONS_PAGE);

			$arr_settings = array();
			$arr_settings['setting_theme_core_display_author_pages'] = __("Display Author Pages", $this->lang_key);
			$arr_settings['setting_theme_core_display_lock'] = __("Display Lock", $this->lang_key);
			$arr_settings['setting_theme_core_title_format'] = __("Title Format", $this->lang_key);

			$arr_data = array();
			get_post_children(array('post_type' => 'post'), $arr_data);

			if(count($arr_data) > 0)
			{
				$arr_settings['setting_display_post_meta'] = __("Display Post Meta", $this->lang_key);
				$arr_settings['default_comment_status'] = __("Allow Comments", $this->lang_key);
			}

			else
			{
				delete_option('setting_display_post_meta');
			}

			$arr_settings['setting_scroll_to_top'] = __("Display scroll-to-top-link", $this->lang_key);

			if(get_option('setting_scroll_to_top') == 'yes')
			{
				$arr_settings['setting_scroll_to_top_text'] = __("Scroll-to-top Text", $this->lang_key);
			}

			if(is_plugin_active("mf_analytics/index.php"))
			{
				$arr_settings['setting_cookie_info'] = __("Cookie information", $this->lang_key);
			}

			else
			{
				delete_option('setting_cookie_info');
			}

			if(get_option('setting_no_public_pages') != 'yes')
			{
				$arr_settings['setting_theme_core_search_redirect_single_result'] = __("Redirect Single Result in Search", $this->lang_key);
				$arr_settings['setting_404_page'] = __("404 Page", $this->lang_key);
			}

			$arr_settings['setting_maintenance_page'] = __("Maintenance Page", $this->lang_key);

			if(IS_SUPER_ADMIN && get_option('setting_maintenance_page') > 0)
			{
				$arr_settings['setting_activate_maintenance'] = __("Activate Maintenance Mode", $this->lang_key);
			}

			show_settings_fields(array('area' => $options_area, 'object' => $this, 'settings' => $arr_settings));
		}
		############################
	}

	function settings_theme_core_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);

		echo settings_header($setting_key, __("Theme", $this->lang_key));
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

		function setting_theme_core_templates_callback()
		{
			$setting_key = get_setting_key(__FUNCTION__);
			$option = get_option($setting_key);

			$arr_data = array();
			get_post_children(array('add_choose_here' => true), $arr_data);

			echo show_select(array('data' => $arr_data, 'name' => $setting_key."[]", 'value' => $option));
		}

		function setting_theme_core_hidden_meta_boxes_callback()
		{
			$setting_key = get_setting_key(__FUNCTION__);
			$option = get_option($setting_key, array('authordiv', 'commentstatusdiv', 'commentsdiv', 'postcustom', 'revisionsdiv', 'slugdiv', 'trackbacksdiv'));

			echo show_select(array('data' => $this->get_meta_boxes_for_select(), 'name' => $setting_key."[]", 'value' => $option));
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

			echo show_select(array('data' => get_yes_no_for_select(), 'name' => $setting_key, 'value' => $option, 'suffix' => sprintf(__("This will send an e-mail to all editors (%s) when an author saves a draft", $this->lang_key), $editors)));
		}

		function setting_theme_enable_wp_api_callback()
		{
			$setting_key = get_setting_key(__FUNCTION__);
			settings_save_site_wide($setting_key);
			$option = get_site_option($setting_key, 'no');

			echo show_select(array('data' => get_yes_no_for_select(), 'name' => $setting_key, 'value' => $option));
		}

		function setting_theme_optimize_callback()
		{
			$option_database_optimized = get_option('option_database_optimized');

			if($option_database_optimized > DEFAULT_DATE)
			{
				$populate_next = format_date(date("Y-m-d H:i:s", strtotime($option_database_optimized." +7 day")));

				$description = sprintf(__("The optimization was last run %s and will be run again %s", $this->lang_key), format_date($option_database_optimized), $populate_next);
			}

			else
			{
				$description = sprintf(__("The optimization has not been run yet but will be %s", $this->lang_key), get_next_cron());
			}

			echo "<div>"
				.show_button(array('type' => 'button', 'name' => 'btnOptimizeTheme', 'text' => __("Optimize Now", $this->lang_key), 'class' => 'button-secondary'))
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

			echo show_select(array('data' => $this->get_params_for_select(), 'name' => $setting_key."[]", 'value' => $option));
		}

		/*function setting_theme_css_hero_callback()
		{
			$css_hero_key = 'wpcss_quick_config_settings_'.$this->get_theme_slug();

			$setting_key = get_setting_key(__FUNCTION__);
			$option = get_option_or_default($setting_key, get_option($css_hero_key));

			if($option != '')
			{
				echo show_textarea(array('name' => $setting_key, 'value' => $option, 'placeholder' => "#site_logo, #main", 'description' => sprintf(__("By going to %sthe site%s you can edit any styling to your liking", $this->lang_key), "<a href='".get_site_url()."?csshero_action=edit_page'>", "</a>")));
			}

			update_option($css_hero_key, $option);
		}*/

	function settings_theme_core_public_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);

		echo settings_header($setting_key, __("Theme", $this->lang_key)." - ".__("Public", $this->lang_key));
	}

		function setting_theme_core_display_author_pages_callback()
		{
			$setting_key = get_setting_key(__FUNCTION__);
			$option = get_option_or_default($setting_key, 'yes');

			echo show_select(array('data' => get_yes_no_for_select(), 'name' => $setting_key, 'value' => $option));
		}

		function setting_theme_core_display_lock_callback()
		{
			$setting_key = get_setting_key(__FUNCTION__);
			$option = get_option($setting_key, ($this->is_theme_active() ? array('administrator', 'editor', 'author', 'contributor') : array()));
			//$option = get_option($setting_key, array('switch_themes', 'moderate_comments', 'upload_files', 'edit_posts'));

			echo show_select(array('data' => get_roles_for_select(array('add_choose_here' => false, 'use_capability' => false)), 'name' => $setting_key."[]", 'value' => $option));
		}

		function setting_theme_core_title_format_callback()
		{
			$setting_key = get_setting_key(__FUNCTION__);
			$option = get_option($setting_key);

			echo show_textfield(array('name' => $setting_key, 'value' => $option, 'placeholder' => $this->title_format)); //, 'description' => __("This will replace the default format", $this->lang_key)
		}

		function setting_display_post_meta_callback()
		{
			$setting_key = get_setting_key(__FUNCTION__);
			$option = get_option_or_default($setting_key, array('time'));

			$arr_data = array(
				'time' => __("Time", $this->lang_key),
				'author' => __("Author", $this->lang_key),
				'category' => __("Category", $this->lang_key),
			);

			echo show_select(array('data' => $arr_data, 'name' => $setting_key."[]", 'value' => $option));
		}

		function get_comment_status_amount($status)
		{
			global $wpdb;

			$wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." WHERE comment_status = %s LIMIT 0, 1", $status)); //post_type = 'post' AND 

			return $wpdb->num_rows;
		}

		function get_comment_status_for_select($option)
		{
			$arr_data = array();
			$arr_data['open'] = __("Yes", $this->lang_key);

			if($this->get_comment_status_amount('closed') > 0)
			{
				$arr_data['open_all'] = __("Yes", $this->lang_key)." (".__("And Change Setting on All Posts", $this->lang_key).")";
			}

			$arr_data['closed'] = __("No", $this->lang_key);

			if($this->get_comment_status_amount('open') > 0)
			{
				$arr_data['closed_all'] = __("No", $this->lang_key)." (".__("And Change Setting on All Posts", $this->lang_key).")";
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

				$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->posts." SET comment_status = %s WHERE comment_status != %s", $option, $option)); //post_type = 'post' AND 

				update_option($setting_key, $option, 'no');
			}

			echo show_select(array('data' => $this->get_comment_status_for_select($option), 'name' => $setting_key, 'value' => $option));
		}

		function setting_scroll_to_top_callback()
		{
			$setting_key = get_setting_key(__FUNCTION__);
			$option = get_option_or_default($setting_key, ($this->is_theme_active() ? 'yes' : 'no'));

			echo show_select(array('data' => get_yes_no_for_select(), 'name' => $setting_key, 'value' => $option));
		}

		function setting_scroll_to_top_text_callback()
		{
			$setting_key = get_setting_key(__FUNCTION__);
			$option = get_option($setting_key);

			echo show_textfield(array('name' => $setting_key, 'value' => $option));
		}

		function setting_cookie_info_callback()
		{
			$setting_key = get_setting_key(__FUNCTION__);
			$option = get_option($setting_key);

			$arr_data = array();
			get_post_children(array('add_choose_here' => true, 'where' => "(post_excerpt != '' || post_content != '')"), $arr_data);

			echo show_select(array('data' => $arr_data, 'name' => $setting_key, 'value' => $option, 'suffix' => get_option_page_suffix(array('value' => $option)), 'description' => __("The content from this page will be displayed on top of the page until the visitor clicks to accept the use of cookies", $this->lang_key)));
		}

		function setting_theme_core_search_redirect_single_result_callback()
		{
			$setting_key = get_setting_key(__FUNCTION__);
			$option = get_option_or_default($setting_key, 'no');

			echo show_select(array('data' => get_yes_no_for_select(), 'name' => $setting_key, 'value' => $option));
		}

		function setting_404_page_callback()
		{
			$setting_key = get_setting_key(__FUNCTION__);
			$option = get_option($setting_key);

			$arr_data = array();
			get_post_children(array('add_choose_here' => true), $arr_data);

			$post_title = "404";
			$post_content = __("Oops! The page that you were looking for does not seam to exist. If you think that it should exist, please let us know.", $this->lang_key);

			echo show_select(array('data' => $arr_data, 'name' => $setting_key, 'value' => $option, 'suffix' => get_option_page_suffix(array('value' => $option, 'title' => $post_title, 'content' => $post_content)), 'description' => (!($option > 0) ? "<span class='display_warning'><i class='fa fa-exclamation-triangle yellow'></i></span> " : "").__("This page will be displayed instead of the default 404 page", $this->lang_key)));
		}

		function setting_maintenance_page_callback()
		{
			global $done_text, $error_text;

			$setting_key = get_setting_key(__FUNCTION__);
			$option = get_option($setting_key);
			$option_temp = get_option($setting_key.'_temp');

			$arr_data = array();
			get_post_children(array('add_choose_here' => true), $arr_data);

			$post_title = __("Temporary Maintenance", $this->lang_key);
			$post_content = __("This site is undergoing maintenance. This usually takes less than a minute so you have been unfortunate to come to the site at this moment. If you reload the page in just a while it will surely be back as usual.", $this->lang_key);

			echo show_select(array('data' => $arr_data, 'name' => $setting_key, 'value' => $option, 'suffix' => get_option_page_suffix(array('value' => $option, 'title' => $post_title, 'content' => $post_content)), 'description' => (!($option > 0) ? "<span class='display_warning'><i class='fa fa-exclamation-triangle yellow'></i></span> " : "").__("This page will be displayed when the website is updating", $this->lang_key)));

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
					}

					if(strlen($recommend_maintenance) > 0)
					{
						$success = set_file_content(array('file' => $maintenance_file, 'mode' => 'w', 'content' => trim($recommend_maintenance)));

						if($success == true)
						{
							update_option($setting_key.'_temp', $option, 'no');
						}

						else
						{
							$error_text = sprintf(__("I could not write to %s. The file is writeable but the write was unsuccessful", $this->lang_key), $maintenance_file);
						}
					}

					else
					{
						$error_text = sprintf(__("The content that I was about to write to %s was empty and the template came from %s", $this->lang_key), $maintenance_file, $maintenance_template);
					}
				}

				else
				{
					$error_text = sprintf(__("I could not write to %s. Please, make sure that this is writeable if you want this functionality to work properly", $this->lang_key), $maintenance_file);
				}

				echo get_notification();
			}
		}

		function setting_activate_maintenance_callback()
		{
			$setting_key = get_setting_key(__FUNCTION__);
			$option = get_option_or_default($setting_key, 'no');

			echo show_select(array('data' => get_yes_no_for_select(), 'name' => $setting_key, 'value' => $option, 'description' => __("This will display the maintenance message to everyone except you as a superadmin, until you inactivate this mode again", $this->lang_key)));
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

		if(function_exists('wp_add_privacy_policy_content'))
		{
			if(get_option('setting_cookie_info') > 0)
			{
				$content = __("A cookie is saved when the visitor accepts the use of cookies on the site, to make sure that the message asking for permission does not appear again.", $this->lang_key);

				wp_add_privacy_policy_content(__("Theme", $this->lang_key), $content);
			}
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

		$title_format = get_option_or_default('setting_theme_core_title_format', $this->title_format);
		$separator = "|";
		$separator_full = " ".$separator." ";

		$page_title = trim(wp_title($separator, false), $separator);
		$site_title = get_bloginfo('name');
		$site_description = get_bloginfo('description', 'display');

		if($page_title != '')
		{
			$title_format = str_replace("[page_title]", $separator_full.$page_title, $title_format);
		}

		if($site_title != '')
		{
			$title_format = str_replace("[site_title]", $separator_full.$site_title, $title_format);
		}

		if($site_description != '' && (is_home() || is_front_page()))
		{
			$title_format = str_replace("[site_description]", $separator_full.$site_description, $title_format);
		}

		if($paged >= 2 || $page >= 2)
		{
			$title_format = str_replace("[page_number]", $separator_full.sprintf( __("Page %s", $this->lang_key), max($paged, $page)), $title_format);
		}

		$title_format = str_replace(array("[page_title]", "[site_title]", "[site_description]", "[page_number]", $separator_full.$separator_full), "", $title_format);
		$title_format = trim($title_format, $separator_full);

		return $title_format;
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

		mf_enqueue_style('style_theme_core', $plugin_include_url."style.php", $plugin_version);
		mf_enqueue_script('script_theme_core', $plugin_include_url."script.js", $plugin_version);

		if(get_option('setting_scroll_to_top') == 'yes')
		{
			mf_enqueue_style('style_theme_scroll', $plugin_include_url."style_scroll.css", $plugin_version);
			mf_enqueue_script('script_theme_scroll', $plugin_include_url."script_scroll.js", array('scroll_to_top_text' => get_option('setting_scroll_to_top_text')), $plugin_version);
		}

		if(!is_plugin_active("mf_widget_logic_select/index.php") || apply_filters('get_widget_search', 'theme-page-index-widget') > 0)
		{
			mf_enqueue_style('style_theme_page_index', $plugin_include_url."style_page_index.css", $plugin_version);
			mf_enqueue_script('script_theme_page_index', $plugin_include_url."script_page_index.js", $plugin_version);
		}

		echo "<meta charset='".get_bloginfo('charset')."'>"
		."<meta name='viewport' content='width=device-width, initial-scale=1, viewport-fit=cover'>"
		//."<meta name='author' content='frostkom.se'>"
		."<title>".$this->get_wp_title()."</title>";

		if(!is_user_logged_in())
		{
			wp_deregister_style('dashicons');
		}

		$this->add_page_index();

		$meta_description = get_the_excerpt();

		if($meta_description != '')
		{
			echo "<meta name='description' content='".esc_attr($meta_description)."'>";
		}

		echo "<link rel='alternate' type='application/rss+xml' title='".get_bloginfo('name')."' href='".get_bloginfo('rss2_url')."'>
		<meta property='og:site_name' content='".get_bloginfo('name')."'>";

		if(isset($post->ID))
		{
			$post_image = "";

			if(has_post_thumbnail($post->ID))
			{
				$post_image = get_the_post_thumbnail_url($post->ID, 'thumbnail');
			}

			else
			{
				$this->get_params();

				if($this->options['header_logo'] != '')
				{
					$post_image = $this->options['header_logo'];
				}
			}

			//echo "<meta property='og:type' content='article'>";

			echo "<meta property='og:title' content='".$post->post_title."'>
			<meta property='og:url' content='".get_permalink($post)."'>";

			if($post_image != '')
			{
				echo "<meta property='og:image' content='".$post_image."'>";
			}

			if(isset($post->post_excerpt) && $post->post_excerpt != '')
			{
				echo "<meta property='og:description' content='".$post->post_excerpt."'>";
			}
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
							<i class='fa fa-gavel red fa-2x'></i>";

							$buttons = "<a href='#accept_cookie' class='button'><i class='fa fa-check green'></i>".__("Accept", $this->lang_key)."</a>";

							if($post_excerpt != '')
							{
								$this->footer_output .= "<p>"
									.$post_excerpt
								."</p>";

								if($post_content != '' && $post_content != $post_excerpt)
								{
									$buttons .= " <a href='".get_permalink($post_id)."' class='button' rel='external'>".__("Read More", $this->lang_key)."</a>";
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

		$user_id = get_current_user_id();

		if($user_id > 0)
		{
			$setting_theme_core_display_lock = get_option('setting_theme_core_display_lock');

			if(is_array($setting_theme_core_display_lock) && in_array(get_current_user_role($user_id), $setting_theme_core_display_lock))
			{
				mf_enqueue_style('style_theme_core_locked', $plugin_include_url."style_locked.css", $plugin_version);

				$this->footer_output .= "<div id='site_locked'>
					<a href='".admin_url()."'><i class='fa fa-lock'></i></a>";

					if(isset($post->ID))
					{
						$this->footer_output .= "<a href='".admin_url("post.php?post=".$post->ID."&action=edit")."'><i class='fa fa-wrench'></i></a>";
					}

				$this->footer_output .= "</div>";
			}
		}

		if(get_option('setting_theme_core_login') == 'yes')
		{
			if(!($user_id > 0) && apply_filters('is_public_page', true))
			{
				do_log("A visitor accessed the public page without being logged in!");
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
			.show_textfield(array('type' => 'search', 'name' => 's', 'value' => get_search_query(), 'placeholder' => __("Search here", $this->lang_key), 'xtra' => " autocomplete='off'"))
			."<div class='form_button'>"
				.show_button(array('text' => __("Search", $this->lang_key)))
			."</div>
		</form>";
	}

	function the_password_form()
	{
		if(!isset($this->displayed_password_form) || $this->displayed_password_form == false)
		{
			$this->displayed_password_form = true;

			return "<form action='".site_url('wp-login.php?action=postpass', 'login_post')."' method='post' class='mf_form'>
				<p>".__("To view this protected post, enter the password below", $this->lang_key)."</p>"
				.show_password_field(array('name' => 'post_password', 'placeholder' => __("Password"), 'maxlength' => 20))
				."<div class='form_button'>"
					.show_button(array('text' => __("Submit", $this->lang_key)))
				."</div>
			</form>";
		}

		else
		{
			return "";
		}
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
						.show_button(array('text' => "Logout"))
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
				$html .= "<time itemprop='dateCreated pubdate datePublished' datetime='".$post->post_date."'>".format_date($post->post_date)."</time>";
			}

			if(in_array('author', $setting_display_post_meta))
			{
				$html .= "<span>".sprintf(__("by %s", $this->lang_key), get_user_info(array('id' => $post->post_author)))."</span>";
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

	function widget_title($title)
	{
		if($title != '')
		{
			$first_name = "";

			if(is_user_logged_in())
			{
				$user_data = get_userdata(get_current_user_id());

				$first_name = $user_data->first_name;
			}

			$title = str_replace("[name]", $first_name, $title);
		}

		return $title;
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

	function is_theme_active()
	{
		if(!isset($this->is_theme_active))
		{
			$theme_dir_name = $this->get_theme_dir_name();

			$this->is_theme_active = in_array($theme_dir_name, array('mf_parallax', 'mf_theme'));
		}

		return $this->is_theme_active;
	}

	function has_noindex($post_id)
	{
		$page_index = get_post_meta($post_id, $this->meta_prefix.'page_index', true);

		return $page_index != '' && in_array($page_index, array('noindex', 'none'));
	}

	function get_public_post_types($data = array())
	{
		if(!isset($data['allow_password_protected'])){	$data['allow_password_protected'] = false;}

		$this->arr_post_types = array();

		foreach(get_post_types(array('public' => true, 'exclude_from_search' => false), 'names') as $post_type)
		{
			if($post_type != 'attachment')
			{
				$data_temp = array(
					'post_type' => $post_type,
				);

				if($data['allow_password_protected'] == false)
				{
					$data_temp['where'] = "post_password = ''";
				}

				get_post_children($data_temp, $this->arr_post_types);
			}
		}
	}

	function get_public_posts($data = array())
	{
		if(!isset($data['allow_noindex'])){				$data['allow_noindex'] = false;}
		if(!isset($data['allow_password_protected'])){	$data['allow_password_protected'] = false;}

		$this->arr_public_posts = array();

		if(!isset($this->arr_post_types) || count($this->arr_post_types) == 0)
		{
			$this->get_public_post_types(array('allow_password_protected' => $data['allow_password_protected']));
		}

		foreach($this->arr_post_types as $post_id => $post_title)
		{
			if($data['allow_noindex'] == false && $this->has_noindex($post_id) || $data['allow_password_protected'] == false && post_password_required($post_id))
			{
				// Do nothing
			}

			else
			{
				$this->arr_public_posts[$post_id] = $post_title;
			}
		}
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

	function get_params_theme_core()
	{
		$options_params = array();

		$theme_dir_name = $this->get_theme_dir_name();

		$options_params[] = array('category' => __("Generic", $this->lang_key), 'id' => 'mf_theme_body');

			/*if(get_option('setting_base_template_site') == '')
			{
				$options_params[] = array('type' => 'url', 'id' => 'style_source', 'title' => __("Get Updates From", $this->lang_key));
			}*/

			$options_params[] = array('type' => 'text', 'id' => 'body_bg', 'title' => __("Background", $this->lang_key));
				$options_params[] = array('type' => 'color', 'id' => 'body_bg_color', 'title' => " - ".__("Color", $this->lang_key), 'default' => '#ffffff');
				$options_params[] = array('type' => 'image', 'id' => 'body_bg_image', 'title' => " - ".__("Image", $this->lang_key));
			$options_params[] = array('type' => 'text', 'id' => 'main_padding', 'title' => __("Padding", $this->lang_key), 'default' => "1em 2em");
			$options_params[] = array('type' => 'font', 'id' => 'body_font', 'title' => __("Font", $this->lang_key));

			$options_params[] = array('type' => 'color', 'id' => 'body_color', 'title' => __("Text Color", $this->lang_key));
				$options_params[] = array('type' => 'color', 'id' => 'body_link_color', 'title' => " - ".__("Link Color", $this->lang_key));
				$options_params[] = array('type' => 'text_decoration', 'id' => 'body_link_underline', 'title' => " - ".__("Link Underline", $this->lang_key), 'default' => 'underline');

			$options_params[] = array('type' => 'number', 'id' => 'website_max_width', 'title' => __("Breakpoint", $this->lang_key)." (".__("Tablet", $this->lang_key).")", 'default' => "1100");
			$options_params[] = array('type' => 'text', 'id' => 'body_desktop_font_size', 'title' => __("Font Size", $this->lang_key), 'default' => ".625em");
			$options_params[] = array('type' => 'number', 'id' => 'mobile_breakpoint', 'title' => __("Breakpoint", $this->lang_key)." (".__("Mobile", $this->lang_key).")", 'default' => "600");
			$options_params[] = array('type' => 'text', 'id' => 'body_font_size', 'title' => __("Font Size", $this->lang_key)." (".__("Mobile", $this->lang_key).")", 'default' => "2.4vw", 'show_if' => 'mobile_breakpoint');

			$options_params[] = array('type' => 'overflow', 'id' => 'body_scroll', 'title' => __("Scroll Bar", $this->lang_key), 'default' => 'scroll');

				if($theme_dir_name == 'mf_parallax')
				{
					$options_params[] = array('type' => 'text', 'id' => 'mobile_aside_img_max_width', 'title' => __("Aside Image Width", $this->lang_key)." (".__("Mobile", $this->lang_key).")", 'show_if' => "mobile_breakpoint");
				}

			$options_params[] = array('type' => 'text', 'id' => 'body_print_font_size', 'title' => __("Font Size", $this->lang_key)." (".__("Print", $this->lang_key).")");

		$options_params[] = array('category_end' => "");

		$options_params[] = array('category' => " - ".__("Forms", $this->lang_key), 'id' => 'mf_theme_generic_forms');
			$options_params[] = array('type' => 'text', 'id' => 'form_border_radius', 'title' => __("Border Radius", $this->lang_key)." (".__("Fields", $this->lang_key).")", 'default' => ".3em");
			$options_params[] = array('type' => 'text', 'id' => 'form_button_border_radius', 'title' => __("Border Radius", $this->lang_key)." (".__("Buttons", $this->lang_key).")", 'default' => ".3em");
			$options_params[] = array('type' => 'text', 'id' => 'form_button_padding', 'title' => __("Padding", $this->lang_key)." (".__("Buttons", $this->lang_key).")");

			$options_params[] = array('type' => 'text', 'id' => 'button_size', 'title' => __("Font Size", $this->lang_key), 'default' => (function_exists('is_plugin_active') && is_plugin_active("mf_webshop/index.php") ? "1.3em" : ''));

			$options_params[] = array('type' => 'color', 'id' => 'button_color', 'title' => __("Button Color", $this->lang_key), 'default' => "#000000");
				//$options_params[] = array('type' => 'color', 'id' => 'button_text_color', 'title' => " - ".__("Button Text Color", $this->lang_key), 'default' => "#ffffff");
			$options_params[] = array('type' => 'color', 'id' => 'button_color_secondary', 'title' => __("Button Color", $this->lang_key)." (".__("Secondary", $this->lang_key).")", 'default' => "#c78e91");
				//$options_params[] = array('type' => 'color', 'id' => 'button_text_color_secondary', 'title' => " - ".__("Button Text Color", $this->lang_key)." (".__("Secondary", $this->lang_key).")", 'default' => "#ffffff");
			$options_params[] = array('type' => 'color', 'id' => 'button_color_negative', 'title' => __("Button Color", $this->lang_key)." (".__("Negative", $this->lang_key).")", 'default' => get_option('setting_color_button_negative', "#e47676"));
				//$options_params[] = array('type' => 'color', 'id' => 'button_text_color_negative', 'title' => " - ".__("Button Text Color", $this->lang_key)." (".__("Negative", $this->lang_key).")", 'default' => "#ffffff");
				//$options_params[] = array('type' => 'color', 'id' => 'button_color_hover', 'title' => " - ".__("Button Color", $this->lang_key)." (".__("Hover", $this->lang_key).")", 'show_if' => 'button_color');
			$options_params[] = array('type' => 'color', 'id' => 'button_color_negative', 'title' => __("Button Color", $this->lang_key)." (".__("Negative", $this->lang_key).")", 'default' => "#e47676");
		$options_params[] = array('category_end' => "");

		$options_params[] = array('category' => __("Header", $this->lang_key), 'id' => 'mf_theme_header');
			$options_params[] = array('type' => 'position', 'id' => 'header_fixed', 'title' => __("Position", $this->lang_key), 'default' => 'relative');
			$options_params[] = array('type' => 'text', 'id' => 'header_bg', 'title' => __("Background", $this->lang_key));
				$options_params[] = array('type' => 'color', 'id' => 'header_bg_color', 'title' => " - ".__("Color", $this->lang_key)); //, 'ignore_default_if' => 'body_bg', 'default' => '#eeeeee'
				$options_params[] = array('type' => 'image', 'id' => 'header_bg_image', 'title' => " - ".__("Image", $this->lang_key));

			if($theme_dir_name == 'mf_parallax')
			{
				$options_params[] = array('type' => 'checkbox', 'id' => 'header_override_bg_with_page_bg', 'title' => __("Override background with page background", $this->lang_key), 'default' => 2);
			}

			$options_params[] = array('type' => 'text', 'id' => 'header_padding', 'title' => __("Padding", $this->lang_key));
			$options_params[] = array('type' => 'overflow', 'id' => 'header_overflow', 'title' => __("Overflow", $this->lang_key));
		$options_params[] = array('category_end' => "");

		$options_params[] = array('category' => " - ".__("Search", $this->lang_key), 'id' => 'mf_theme_header_search');
			$options_params[] = array('type' => 'color', 'id' => 'search_color', 'title' => __("Color", $this->lang_key));
			$options_params[] = array('type' => 'text', 'id' => 'search_size', 'title' => __("Font Size", $this->lang_key), 'default' => "1.4em");
		$options_params[] = array('category_end' => "");

		$options_params[] = array('category' => " - ".__("Logo", $this->lang_key), 'id' => 'mf_theme_logo');
			$options_params[] = array('type' => 'text', 'id' => 'logo_padding', 'title' => __("Padding", $this->lang_key)); //, 'default' => '.4em 0'
			$options_params[] = array('type' => 'image', 'id' => 'header_logo', 'title' => __("Image", $this->lang_key));
			$options_params[] = array('type' => 'float', 'id' => 'logo_float', 'title' => __("Alignment", $this->lang_key), 'default' => 'left');
			$options_params[] = array('type' => 'text', 'id' => 'logo_width', 'title' => __("Width", $this->lang_key), 'default' => '14em');
			$options_params[] = array('type' => 'image', 'id' => 'header_mobile_logo', 'title' => __("Image", $this->lang_key)." (".__("Mobile", $this->lang_key).")", 'show_if' => 'mobile_breakpoint');
			$options_params[] = array('type' => 'text', 'id' => 'logo_width_mobile', 'title' => __("Width", $this->lang_key)." (".__("Mobile", $this->lang_key).")", 'default' => '20em');
			$options_params[] = array('type' => 'font', 'id' => 'logo_font', 'title' => __("Font", $this->lang_key), 'hide_if' => 'header_logo');
			$options_params[] = array('type' => 'text', 'id' => 'logo_font_size', 'title' => __("Font Size", $this->lang_key), 'default' => "3rem");
				$options_params[] = array('type' => 'text', 'id' => 'slogan_font_size', 'title' => __("Font Size", $this->lang_key)." (".__("Tagline", $this->lang_key).")", 'default' => ".4em");
			$options_params[] = array('type' => 'color', 'id' => 'logo_color', 'title' => __("Color", $this->lang_key));
		$options_params[] = array('category_end' => "");

		$options_params[] = array('category' => __("Navigation", $this->lang_key), 'id' => 'mf_theme_navigation');

			if($theme_dir_name == 'mf_parallax')
			{
				$options_params[] = array('type' => 'checkbox', 'id' => 'nav_mobile', 'title' => __("Compressed", $this->lang_key)." (".__("Mobile", $this->lang_key).")", 'default' => 2);
					$options_params[] = array('type' => 'checkbox', 'id' => 'nav_click2expand', 'title' => __("Click to expand", $this->lang_key), 'default' => 1);
				$options_params[] = array('type' => 'text', 'id' => 'nav_padding', 'title' => __("Padding", $this->lang_key), 'default' => "0 1em");
					$options_params[] = array('type' => 'text', 'id' => 'nav_padding_mobile', 'title' => __("Padding", $this->lang_key)." (".__("Mobile", $this->lang_key).")", 'show_if' => 'nav_padding');
				$options_params[] = array('type' => 'float', 'id' => 'nav_float', 'title' => __("Alignment", $this->lang_key), 'default' => "right");
					$options_params[] = array('type' => 'float', 'id' => 'nav_float_mobile', 'title' => __("Alignment", $this->lang_key)." (".__("Mobile", $this->lang_key).")", 'default' => "none", 'show_if' => 'nav_float');
			}

			$options_params[] = array('type' => 'checkbox', 'id' => 'nav_full_width', 'title' => __("Full Width", $this->lang_key), 'default' => 1);
			$options_params[] = array('type' => 'align', 'id' => 'nav_align', 'title' => __("Alignment", $this->lang_key), 'default' => "right");
			$options_params[] = array('type' => 'text', 'id' => 'nav_bg', 'title' => __("Background", $this->lang_key));
				$options_params[] = array('type' => 'color', 'id' => 'nav_bg_color', 'title' => " - ".__("Color", $this->lang_key));
				$options_params[] = array('type' => 'image', 'id' => 'nav_bg_image', 'title' => " - ".__("Image", $this->lang_key));
			$options_params[] = array('type' => 'clear', 'id' => 'nav_clear', 'title' => __("Clear", $this->lang_key), 'default' => "right");
			$options_params[] = array('type' => 'text', 'id' => 'nav_padding', 'title' => __("Padding", $this->lang_key));
			$options_params[] = array('type' => 'font', 'id' => 'nav_font', 'title' => __("Font", $this->lang_key));
			$options_params[] = array('type' => 'text', 'id' => 'nav_size', 'title' => __("Font Size", $this->lang_key), 'default' => "2em");
			$options_params[] = array('type' => 'color', 'id' => 'nav_color', 'title' => __("Text Color", $this->lang_key));
				$options_params[] = array('type' => 'color', 'id' => 'nav_color_hover', 'title' => __("Text Color", $this->lang_key)." (".__("Hover", $this->lang_key).")", 'show_if' => 'nav_color');
			$options_params[] = array('type' => 'text', 'id' => 'nav_link_padding', 'title' => __("Link Padding", $this->lang_key), 'default' => "1em");

			if($theme_dir_name == 'mf_theme')
			{
				$options_params[] = array('type' => 'color', 'id' => 'nav_underline_color_hover', 'title' => " - ".__("Underline Color", $this->lang_key)." (".__("Hover", $this->lang_key).")", 'show_if' => 'nav_color');
				$options_params[] = array('type' => 'color', 'id' => 'nav_bg_current', 'title' => __("Background", $this->lang_key)." (".__("Current", $this->lang_key).")", 'show_if' => 'nav_color');
				$options_params[] = array('type' => 'color', 'id' => 'nav_color_current', 'title' => __("Text Color", $this->lang_key)." (".__("Current", $this->lang_key).")", 'show_if' => 'nav_color');
			}

		$options_params[] = array('category_end' => "");

		if($theme_dir_name == 'mf_theme')
		{
			$options_params[] = array('category' => " - ".__("Submenu", $this->lang_key), 'id' => 'mf_theme_navigation_sub');
				$options_params[] = array('type' => 'direction', 'id' => 'sub_nav_direction', 'title' => __("Direction", $this->lang_key), 'default' => 'horizontal');
				$options_params[] = array('type' => 'checkbox', 'id' => 'sub_nav_arrow', 'title' => __("Show Up Arrow", $this->lang_key), 'default' => 2);
				$options_params[] = array('type' => 'color', 'id' => 'sub_nav_bg', 'title' => __("Background", $this->lang_key), 'default' => "#ccc");
					$options_params[] = array('type' => 'color', 'id' => 'sub_nav_bg_hover', 'title' => " - ".__("Background", $this->lang_key)." (".__("Hover", $this->lang_key).")", 'show_if' => 'sub_nav_bg');
				$options_params[] = array('type' => 'color', 'id' => 'sub_nav_color', 'title' => __("Text Color", $this->lang_key), 'default' => "#333");
					$options_params[] = array('type' => 'color', 'id' => 'sub_nav_color_hover', 'title' => " - ".__("Text Color", $this->lang_key)." (".__("Hover", $this->lang_key).")", 'show_if' => 'sub_nav_color');
			$options_params[] = array('category_end' => "");
		}

		$options_params[] = array('category' => " - ".__("Mobile Menu", $this->lang_key), 'id' => 'mf_theme_navigation_hamburger');

			if($theme_dir_name == 'mf_theme')
			{
				$options_params[] = array('type' => 'checkbox', 'id' => 'hamburger_collapse_if_no_space', 'title' => __("Display when menu runs out of space", $this->lang_key), 'default' => 1);
				$options_params[] = array('type' => 'text', 'id' => 'hamburger_menu_bg', 'title' => __("Background", $this->lang_key)." (".__("Menu", $this->lang_key).")");
			}

			if($theme_dir_name == 'mf_parallax')
			{
				$options_params[] = array('type' => 'float', 'id' => 'hamburger_position', 'title' => __("Alignment", $this->lang_key), 'default' => "right");
				$options_params[] = array('type' => 'position', 'id' => 'hamburger_fixed', 'title' => __("Position", $this->lang_key));
			}

			$options_params[] = array('type' => 'text', 'id' => 'hamburger_font_size', 'title' => __("Font Size", $this->lang_key), 'default' => "1.5em");
			$options_params[] = array('type' => 'text', 'id' => 'hamburger_margin', 'title' => __("Padding", $this->lang_key), 'default' => ".8em");

		$options_params[] = array('category_end' => "");

		if($theme_dir_name == 'mf_theme')
		{
			$options_params[] = array('category' => " - ".__("Secondary", $this->lang_key), 'id' => 'mf_theme_navigation_secondary');
				$options_params[] = array('type' => 'text', 'id' => 'nav_secondary_bg', 'title' => __("Background", $this->lang_key));
				$options_params[] = array('type' => 'text', 'id' => 'nav_secondary_link_padding', 'title' => __("Link Padding", $this->lang_key));
				$options_params[] = array('type' => 'clear', 'id' => 'nav_secondary_clear', 'title' => __("Clear", $this->lang_key), 'default' => "none");
				$options_params[] = array('type' => 'text', 'id' => 'nav_secondary_size', 'title' => __("Font Size", $this->lang_key), 'default' => "1.4em");
				$options_params[] = array('type' => 'align', 'id' => 'nav_secondary_align', 'title' => __("Alignment", $this->lang_key), 'default' => "right");
				$options_params[] = array('type' => 'color', 'id' => 'nav_secondary_color', 'title' => __("Text Color", $this->lang_key));
					$options_params[] = array('type' => 'color', 'id' => 'nav_secondary_color_hover', 'title' => " - ".__("Text Color", $this->lang_key)." (".__("Hover", $this->lang_key).")");
				$options_params[] = array('type' => 'color', 'id' => 'nav_secondary_bg_current', 'title' => __("Background", $this->lang_key)." (".__("Current", $this->lang_key).")", 'show_if' => 'nav_color');
				$options_params[] = array('type' => 'color', 'id' => 'nav_secondary_color_current', 'title' => __("Text Color", $this->lang_key)." (".__("Current", $this->lang_key).")", 'show_if' => 'nav_color');
			$options_params[] = array('category_end' => "");
		}

		if(is_active_widget_area('widget_slide'))
		{
			$options_params[] = array('category' => " - ".__("Slide Menu", $this->lang_key), 'id' => 'mf_theme_navigation_slide');

				if($theme_dir_name == 'mf_parallax')
				{
					$options_params[] = array('type' => 'float', 'id' => 'slide_nav_position', 'title' => __("Alignment", $this->lang_key), 'default' => "right");
				}

				$options_params[] = array('type' => 'text', 'id' => 'slide_nav_link_padding', 'title' => __("Link Padding", $this->lang_key), 'default' => "1.5em 1em 1em");
				$options_params[] = array('type' => 'color', 'id' => 'slide_nav_bg', 'title' => __("Background", $this->lang_key), 'default' => "#fff");
					$options_params[] = array('type' => 'color', 'id' => 'slide_nav_bg_hover', 'title' => " - ".__("Background", $this->lang_key)." (".__("Hover", $this->lang_key).")", 'show_if' => 'slide_nav_bg');
				$options_params[] = array('type' => 'color', 'id' => 'slide_nav_color', 'title' => __("Text Color", $this->lang_key));
					$options_params[] = array('type' => 'color', 'id' => 'slide_nav_color_hover', 'title' => " - ".__("Text Color", $this->lang_key)." (".__("Hover", $this->lang_key).")", 'show_if' => 'slide_nav_color');
					$options_params[] = array('type' => 'color', 'id' => 'slide_nav_color_current', 'title' => " - ".__("Text Color", $this->lang_key)." (".__("Current", $this->lang_key).")");
				$options_params[] = array('type' => 'color', 'id' => 'slide_nav_sub_bg', 'title' => __("Submenu", $this->lang_key)." - ".__("Background", $this->lang_key));
					$options_params[] = array('type' => 'color', 'id' => 'slide_nav_sub_bg_hover', 'title' => " - ".__("Submenu", $this->lang_key)." - ".__("Background", $this->lang_key)." (".__("Hover", $this->lang_key).")", 'show_if' => 'slide_nav_bg');

			$options_params[] = array('category_end' => "");
		}

		if($theme_dir_name == 'mf_parallax')
		{
			if(is_active_widget_area('widget_pre_content'))
			{
				$options_params[] = array('category' => __("Pre Content", $this->lang_key), 'id' => 'mf_parallax_pre_content');
					$options_params[] = array('type' => 'checkbox', 'id' => 'pre_content_full_width', 'title' => __("Full Width", $this->lang_key), 'default' => 1);
					$options_params[] = array('type' => 'text', 'id' => 'pre_content_bg', 'title' => __("Background", $this->lang_key));
						$options_params[] = array('type' => 'color', 'id' => 'pre_content_bg_color', 'title' => " - ".__("Color", $this->lang_key));
						$options_params[] = array('type' => 'image', 'id' => 'pre_content_bg_image', 'title' => " - ".__("Image", $this->lang_key));
					$options_params[] = array('type' => 'text', 'id' => 'pre_content_padding', 'title' => __("Padding", $this->lang_key));
				$options_params[] = array('category_end' => "");
			}
		}

		if($theme_dir_name == 'mf_theme')
		{
			if(is_active_widget_area('widget_after_header'))
			{
				$options_params[] = array('category' => __("After Header", $this->lang_key), 'id' => 'mf_theme_after_header');
					$options_params[] = array('type' => 'checkbox', 'id' => 'after_header_full_width', 'title' => __("Full Width", $this->lang_key), 'default' => 1);
					$options_params[] = array('type' => 'text', 'id' => 'after_header_bg', 'title' => __("Background", $this->lang_key));
						$options_params[] = array('type' => 'color', 'id' => 'after_header_bg_color', 'title' => " - ".__("Color", $this->lang_key));
						$options_params[] = array('type' => 'image', 'id' => 'after_header_bg_image', 'title' => " - ".__("Image", $this->lang_key));
					$options_params[] = array('type' => 'text', 'id' => 'after_header_widget_font_size', 'title' => __("Font Size", $this->lang_key));
					$options_params[] = array('type' => 'text', 'id' => 'after_header_padding', 'title' => __("Padding", $this->lang_key));
						$options_params[] = array('type' => 'text', 'id' => 'after_header_widget_padding', 'title' => " - ".__("Widget Padding", $this->lang_key), 'default' => "0 0 .5em");
					$options_params[] = array('type' => 'color', 'id' => 'after_header_color', 'title' => __("Text Color", $this->lang_key));
					$options_params[] = array('type' => 'overflow', 'id' => 'after_header_overflow', 'title' => __("Overflow", $this->lang_key));
					/*$options_params[] = array('type' => 'text', 'id' => 'after_header_widget_font_size', 'title' => __("Font Size", $this->lang_key));
					$options_params[] = array('type' => 'text', 'id' => 'after_header_padding', 'title' => __("Padding", $this->lang_key));
						$options_params[] = array('type' => 'text', 'id' => 'after_header_widget_padding', 'title' => " - ".__("Widget Padding", $this->lang_key), 'default' => "0 0 .5em");*/
				$options_params[] = array('category_end' => "");
			}

			/* This does not work together with Hero */
			/*if(is_active_widget_area('widget_front'))
			{*/
				$options_params[] = array('category' => __("Pre Content", $this->lang_key), 'id' => 'mf_theme_pre_content');
					$options_params[] = array('type' => 'checkbox', 'id' => 'pre_content_full_width', 'title' => __("Full Width", $this->lang_key), 'default' => 1);
					$options_params[] = array('type' => 'text', 'id' => 'front_bg', 'title' => __("Background", $this->lang_key));
						$options_params[] = array('type' => 'color', 'id' => 'pre_content_bg_color', 'title' => " - ".__("Color", $this->lang_key));
						$options_params[] = array('type' => 'image', 'id' => 'pre_content_bg_image', 'title' => " - ".__("Image", $this->lang_key));
					//$options_params[] = array('type' => 'text', 'id' => 'pre_content_widget_font_size', 'title' => __("Font Size", $this->lang_key));
					$options_params[] = array('type' => 'text', 'id' => 'front_padding', 'title' => __("Padding", $this->lang_key));
					$options_params[] = array('type' => 'color', 'id' => 'front_color', 'title' => __("Text Color", $this->lang_key));
				$options_params[] = array('category_end' => "");
			//}
		}

		$options_params[] = array('category' => __("Content", $this->lang_key), 'id' => 'mf_theme_content');

			if($theme_dir_name == 'mf_parallax')
			{
				$options_params[] = array('type' => 'checkbox', 'id' => 'content_stretch_height', 'title' => __("Match Height with Window Size", $this->lang_key), 'default' => 2);
				$options_params[] = array('type' => 'float', 'id' => 'content_main_position', 'title' => __("Main Column Alignment", $this->lang_key), 'default' => "right");
				$options_params[] = array('type' => 'number', 'id' => 'content_main_width', 'title' => __("Main Column Width", $this->lang_key)." (%)", 'default' => "60");
			}

			if($theme_dir_name == 'mf_theme')
			{
				$options_params[] = array('type' => 'text', 'id' => 'content_bg', 'title' => __("Background", $this->lang_key));
					$options_params[] = array('type' => 'color', 'id' => 'content_bg_color', 'title' => " - ".__("Color", $this->lang_key));
					$options_params[] = array('type' => 'image', 'id' => 'content_bg_image', 'title' => " - ".__("Image", $this->lang_key));
			}

			$options_params[] = array('type' => 'text', 'id' => 'content_padding', 'title' => __("Padding", $this->lang_key)); //, 'default' => "30px 0 20px"

		$options_params[] = array('category_end' => "");

		$options_params[] = array('category' => " - ".__("Headings", $this->lang_key), 'id' => 'mf_theme_content_heading');

			if($theme_dir_name == 'mf_theme')
			{
				$options_params[] = array('type' => 'text', 'id' => 'heading_bg', 'title' => __("Background", $this->lang_key)." (H1)");
				$options_params[] = array('type' => 'text', 'id' => 'heading_border_bottom', 'title' => __("Border Bottom", $this->lang_key)." (H1)");
				$options_params[] = array('type' => 'font', 'id' => 'heading_font', 'title' => __("Font", $this->lang_key)." (H1)");
				$options_params[] = array('type' => 'text', 'id' => 'heading_size', 'title' => __("Font Size", $this->lang_key)." (H1)", 'default' => "2.4em");
				$options_params[] = array('type' => 'weight', 'id' => 'heading_weight', 'title' => __("Weight", $this->lang_key)." (H1)");
				$options_params[] = array('type' => 'text', 'id' => 'heading_margin', 'title' => __("Margin", $this->lang_key)." (H1)");
				$options_params[] = array('type' => 'text', 'id' => 'heading_padding', 'title' => __("Padding", $this->lang_key)." (H1)", 'default' => ".3em 0 .5em");
			}

			/* H2 */
			##################
			$options_params[] = array('type' => 'text', 'id' => 'heading_margin_h2', 'title' => __("Margin", $this->lang_key)." (H2)", 'default' => "0 0 .5em");
			$options_params[] = array('type' => 'font', 'id' => 'heading_font_h2', 'title' => __("Font", $this->lang_key)." (H2)");

			if($theme_dir_name == 'mf_theme')
			{
				$options_params[] = array('type' => 'text', 'id' => 'heading_size_h2', 'title' => __("Font Size", $this->lang_key)." (H2)", 'default' => "1.4em");
			}

			if($theme_dir_name == 'mf_parallax')
			{
				$options_params[] = array('type' => 'text', 'id' => 'heading_font_size_h2', 'title' => __("Font Size", $this->lang_key)." (H2)", 'default' => "2em");
			}

			$options_params[] = array('type' => 'weight', 'id' => 'heading_weight_h2', 'title' => __("Weight", $this->lang_key)." (H2)");
			##################

			/* H3 */
			##################
			$options_params[] = array('type' => 'text', 'id' => 'heading_margin_h3', 'title' => __("Margin", $this->lang_key)." (H3)");

			if($theme_dir_name == 'mf_theme')
			{
				$options_params[] = array('type' => 'font', 'id' => 'heading_font_h3', 'title' => __("Font", $this->lang_key)." (H3)");
				$options_params[] = array('type' => 'text', 'id' => 'heading_size_h3', 'title' => __("Font Size", $this->lang_key)." (H3)", 'default' => "1.2em");
			}

			if($theme_dir_name == 'mf_parallax')
			{
				$options_params[] = array('type' => 'text', 'id' => 'heading_font_size_h3', 'title' => __("Font Size", $this->lang_key)." (H3)");
			}

			$options_params[] = array('type' => 'weight', 'id' => 'heading_weight_h3', 'title' => __("Weight", $this->lang_key)." (H3)");
			##################

			/* H4 */
			##################
			$options_params[] = array('type' => 'text', 'id' => 'heading_margin_h4', 'title' => __("Margin", $this->lang_key)." (H4)", 'default' => ".5em 0");
			$options_params[] = array('type' => 'text', 'id' => 'heading_font_size_h4', 'title' => __("Font Size", $this->lang_key)." (H4)");
			$options_params[] = array('type' => 'weight', 'id' => 'heading_weight_h4', 'title' => __("Weight", $this->lang_key)." (H4)");
			##################

			/* H5 */
			##################
			$options_params[] = array('type' => 'text', 'id' => 'heading_margin_h5', 'title' => __("Margin", $this->lang_key)." (H5)");
			$options_params[] = array('type' => 'text', 'id' => 'heading_font_size_h5', 'title' => __("Font Size", $this->lang_key)." (H5)");
			$options_params[] = array('type' => 'weight', 'id' => 'heading_weight_h5', 'title' => __("Weight", $this->lang_key)." (H5)");
			##################

			if($theme_dir_name == 'mf_parallax')
			{
				$options_params[] = array('type' => 'text', 'id' => 'section_heading_alignment_mobile', 'title' => __("Heading Alignment", $this->lang_key)." (".__("Mobile", $this->lang_key).")", 'default' => "center");
			}

		$options_params[] = array('category_end' => "");

		$options_params[] = array('category' => " - ".__("Text", $this->lang_key), 'id' => 'mf_theme_content_text');

			if($theme_dir_name == 'mf_theme')
			{
				$options_params[] = array('type' => 'text', 'id' => 'section_bg', 'title' => __("Background", $this->lang_key));
			}

			$options_params[] = array('type' => 'text', 'id' => 'section_size', 'title' => __("Font Size", $this->lang_key), 'default' => "1.6em");

			// Range does not display the value the user has chosen...
			//$options_params[] = array('type' => 'text', 'id' => 'section_line_height', 'title' => __("Line Height", $this->lang_key), 'default' => "1.5");
			$options_params[] = array('type' => 'range', 'input_attrs' => array(
				'min' => 1,
				'max' => 5,
				'step' => .1,
				//'class' => '',
				//'style' => 'color: #',
			), 'id' => 'section_line_height', 'title' => __("Line Height", $this->lang_key), 'default' => "1.5");

			$options_params[] = array('type' => 'text', 'id' => 'section_margin', 'title' => __("Margin", $this->lang_key), 'default' => "0 0 2em");

			if($theme_dir_name == 'mf_parallax')
			{
				$options_params[] = array('type' => 'text', 'id' => 'quote_size', 'title' => __("Quote Size", $this->lang_key));
			}

			if($theme_dir_name == 'mf_theme')
			{
				$options_params[] = array('type' => 'text', 'id' => 'section_padding', 'title' => __("Padding", $this->lang_key));
				$options_params[] = array('type' => 'text', 'id' => 'section_margin_between', 'title' => __("Margin between Content", $this->lang_key), 'default' => "1em");
				$options_params[] = array('type' => 'color', 'id' => 'article_url_color', 'title' => __("Link Color", $this->lang_key));
			}

		$options_params[] = array('category_end' => "");

		if($theme_dir_name == 'mf_parallax')
		{
			$options_params[] = array('category' => __("Aside", $this->lang_key), 'id' => 'mf_parallax_aside');
				$options_params[] = array('type' => 'text', 'id' => 'aside_p', 'title' => __("Paragraph Size", $this->lang_key));
			$options_params[] = array('category_end' => "");
		}

		if($theme_dir_name == 'mf_theme')
		{
			if(is_active_widget_area('widget_after_heading') || is_active_widget_area('widget_sidebar_left') || is_active_widget_area('widget_after_content') || is_active_widget_area('widget_sidebar') || is_active_widget_area('widget_below_content'))
			{
				$options_params[] = array('category' => __("Aside", $this->lang_key), 'id' => 'mf_theme_aside');
					$options_params[] = array('type' => 'text', 'id' => 'aside_sticky_position', 'title' => __("Sticky Position", $this->lang_key));
					$options_params[] = array('type' => 'text', 'id' => 'aside_left_width', 'title' => __("Width", $this->lang_key)." (".__("Left", $this->lang_key).")", 'default' => "28%");
					$options_params[] = array('type' => 'text', 'id' => 'aside_width', 'title' => __("Width", $this->lang_key)." (".__("Right", $this->lang_key).")", 'default' => "28%");
					$options_params[] = array('type' => 'text', 'id' => 'aside_container_margin', 'title' => __("Margin", $this->lang_key));
					$options_params[] = array('type' => 'text', 'id' => 'aside_container_padding', 'title' => __("Padding", $this->lang_key));
					$options_params[] = array('type' => 'text', 'id' => 'aside_widget_background', 'title' => __("Widget Background", $this->lang_key)); //, 'default' => "#f8f8f8"
					$options_params[] = array('type' => 'text', 'id' => 'aside_widget_border', 'title' => __("Widget Border", $this->lang_key)); //, 'default' => "1px solid #d8d8d8"
					$options_params[] = array('type' => 'text', 'id' => 'aside_widget_font_size', 'title' => __("Font Size", $this->lang_key));
					$options_params[] = array('type' => 'text', 'id' => 'aside_heading_bg', 'title' => __("Background", $this->lang_key)." (H3)");
					$options_params[] = array('type' => 'text', 'id' => 'aside_heading_border_bottom', 'title' => __("Border Bottom", $this->lang_key)." (H3)");
					$options_params[] = array('type' => 'text', 'id' => 'aside_heading_size', 'title' => __("Size", $this->lang_key)." (H3)");
					$options_params[] = array('type' => 'text', 'id' => 'aside_heading_padding', 'title' => __("Padding", $this->lang_key)." (H3)", 'default' => ".5em");
					$options_params[] = array('type' => 'text', 'id' => 'aside_size', 'title' => __("Size", $this->lang_key)." (".__("Content", $this->lang_key).")");
					$options_params[] = array('type' => 'text', 'id' => 'aside_line_height', 'title' => __("Line Height", $this->lang_key)." (".__("Content", $this->lang_key).")");
					$options_params[] = array('type' => 'text', 'id' => 'aside_padding', 'title' => __("Padding", $this->lang_key)." (".__("Content", $this->lang_key).")", 'default' => ".5em");
					$options_params[] = array('type' => 'text', 'id' => 'aside_margin_between', 'title' => __("Margin between Content", $this->lang_key));
				$options_params[] = array('category_end' => "");
			}

			if(is_active_widget_area('widget_pre_footer'))
			{
				$options_params[] = array('category' => __("Pre Footer", $this->lang_key), 'id' => 'mf_theme_pre_footer');
					$options_params[] = array('type' => 'checkbox', 'id' => 'pre_footer_full_width', 'title' => __("Full Width", $this->lang_key), 'default' => 1);
					$options_params[] = array('type' => 'text', 'id' => 'pre_footer_bg', 'title' => __("Background", $this->lang_key));
						$options_params[] = array('type' => 'color', 'id' => 'pre_footer_bg_color', 'title' => " - ".__("Color", $this->lang_key));
						$options_params[] = array('type' => 'image', 'id' => 'pre_footer_bg_image', 'title' => " - ".__("Image", $this->lang_key));
					$options_params[] = array('type' => 'text', 'id' => 'pre_footer_widget_font_size', 'title' => __("Font Size", $this->lang_key));
					$options_params[] = array('type' => 'text', 'id' => 'pre_footer_padding', 'title' => __("Padding", $this->lang_key));
						$options_params[] = array('type' => 'text', 'id' => 'pre_footer_widget_padding', 'title' => " - ".__("Widget Padding", $this->lang_key), 'default' => "0 0 .5em");
				$options_params[] = array('category_end' => "");
			}
		}

		$options_params[] = array('category' => __("Footer", $this->lang_key), 'id' => 'mf_theme_footer');
			$options_params[] = array('type' => 'position', 'id' => 'footer_fixed', 'title' => __("Position", $this->lang_key), 'default' => 'relative');
			$options_params[] = array('type' => 'text', 'id' => 'footer_bg', 'title' => __("Background", $this->lang_key)); //This is used as the default background on body to make the background go all the way down below the footer if present
				$options_params[] = array('type' => 'color', 'id' => 'footer_bg_color', 'title' => " - ".__("Color", $this->lang_key)); //, 'ignore_default_if' => 'body_bg', 'default' => '#eeeeee'
				$options_params[] = array('type' => 'image', 'id' => 'footer_bg_image', 'title' => " - ".__("Image", $this->lang_key));

			if(is_active_widget_area('widget_footer'))
			{
				$options_params[] = array('type' => 'font', 'id' => 'footer_font', 'title' => __("Font", $this->lang_key));
				$options_params[] = array('type' => 'text', 'id' => 'footer_font_size', 'title' => __("Font Size", $this->lang_key), 'default' => "1.5em");
				$options_params[] = array('type' => 'color', 'id' => 'footer_color', 'title' => __("Text Color", $this->lang_key));

					if($theme_dir_name == 'mf_theme')
					{
						$options_params[] = array('type' => 'color', 'id' => 'footer_color_hover', 'title' => " - ".__("Text Color", $this->lang_key)." (".__("Hover", $this->lang_key).")", 'show_if' => 'footer_color');
					}

				if($theme_dir_name == 'mf_parallax')
				{
					$options_params[] = array('type' => 'align', 'id' => 'footer_align', 'title' => __("Alignment", $this->lang_key));
					$options_params[] = array('type' => 'text', 'id' => 'footer_margin', 'title' => __("Margin", $this->lang_key));
				}

				$options_params[] = array('type' => 'text', 'id' => 'footer_padding', 'title' => __("Padding", $this->lang_key));

				if($theme_dir_name == 'mf_theme')
				{
					$options_params[] = array('type' => 'checkbox', 'id' => 'footer_widget_flex', 'title' => __("Widget Flex", $this->lang_key), 'default' => 2);
					$options_params[] = array('type' => 'overflow', 'id' => 'footer_widget_overflow', 'title' => __("Widget Overflow", $this->lang_key), 'default' => 'hidden');
				}

				$options_params[] = array('type' => 'text', 'id' => 'footer_widget_padding', 'title' => __("Widget Padding", $this->lang_key), 'default' => ".2em");

				if($theme_dir_name == 'mf_theme')
				{
					$options_params[] = array('type' => 'text', 'id' => 'footer_widget_heading_margin', 'title' => __("Widget Heading Margin", $this->lang_key), 'default' => "0 0 .5em");
					$options_params[] = array('type' => 'text_transform', 'id' => 'footer_widget_heading_text_transform', 'title' => __("Widget Heading Text Transform", $this->lang_key), 'default' => "uppercase");
					$options_params[] = array('type' => 'text', 'id' => 'footer_p_margin', 'title' => __("Paragraph/List Margin", $this->lang_key), 'default' => "0 0 .5em");
					$options_params[] = array('type' => 'text', 'id' => 'footer_a_bg', 'title' => __("Link Background", $this->lang_key));
					$options_params[] = array('type' => 'text', 'id' => 'footer_a_margin', 'title' => __("Link Margin", $this->lang_key));
					$options_params[] = array('type' => 'text', 'id' => 'footer_a_padding', 'title' => __("Link Padding", $this->lang_key), 'default' => ".4em .6em");
				}
			}

		$options_params[] = array('category_end' => "");

		$options_params[] = array('category' => __("Custom", $this->lang_key), 'id' => 'mf_theme_generic');
			$options_params[] = array('type' => 'textarea', 'id' => 'external_css', 'title' => __("External CSS", $this->lang_key));
			$options_params[] = array('type' => 'textarea', 'id' => 'custom_css_all', 'title' => __("Custom CSS", $this->lang_key));
			$options_params[] = array('type' => 'textarea', 'id' => 'custom_css_mobile', 'title' => __("Custom CSS", $this->lang_key)." (".__("Mobile", $this->lang_key).")", 'show_if' => 'mobile_breakpoint');
		$options_params[] = array('category_end' => "");

		$options_params = apply_filters('filter_options_params', $options_params);

		return $options_params;
	}

	function get_params()
	{
		if(count($this->options_params) == 0)
		{
			$this->options_params = $this->get_params_theme_core();

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

		$this->options_fonts['rancho'] = array(
			'title' => "Rancho",
			'style' => "'Rancho', cursive",
			'url' => "//fonts.googleapis.com/css?family=Rancho"
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
		$out = "p a, td a, a .read_more
		{"
			.$this->render_css(array('property' => 'color', 'value' => 'body_link_color'))
			.$this->render_css(array('property' => 'text-decoration', 'value' => 'body_link_underline'))
			."text-decoration-skip: ink;
		}

			.read_more
			{
				margin-top: 1em;
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
			.$this->render_css(array('property' => 'background', 'value' => array('button_color', 'nav_color_hover')));
			//.$this->render_css(array('property' => 'color', 'value' => 'button_text_color'))

			if(isset($this->options['button_color']) && $this->options['button_color'] != '')
			{
				if(!isset($obj_base))
				{
					$obj_base = new mf_base();
				}

				$out .= "color: ".$obj_base->get_text_color_from_background($this->options['button_color']); // button_color_secondary //." !important" // Can't be important because it will override .webshop_events .calendar_header button
			}

			else if(isset($this->options['nav_color_hover']) && $this->options['nav_color_hover'] != '')
			{
				if(!isset($obj_base))
				{
					$obj_base = new mf_base();
				}

				$out .= "color: ".$obj_base->get_text_color_from_background($this->options['button_color']); // button_color_secondary //." !important" // Can't be important because it will override .webshop_events .calendar_header button
			}

		$out .= "}

			.color_text
			{"
				.$this->render_css(array('property' => 'color', 'value' => 'button_color'))
			."}

		#wrapper .button-secondary, .color_button_2
		{"
			.$this->render_css(array('property' => 'background', 'value' => 'button_color_secondary', 'suffix' => " !important"));
			//.$this->render_css(array('property' => 'color', 'value' => 'button_text_color_secondary', 'suffix' => " !important"))

			if(isset($this->options['button_color_secondary']) && $this->options['button_color_secondary'] != '')
			{
				if(!isset($obj_base))
				{
					$obj_base = new mf_base();
				}

				$out .= "color: ".$obj_base->get_text_color_from_background($this->options['button_color_secondary'])." !important";
			}

		$out .= "}

			.color_text_2
			{"
				.$this->render_css(array('property' => 'color', 'value' => 'button_color_secondary'))
			."}

		.color_button_negative
		{"
			.$this->render_css(array('property' => 'background', 'value' => 'button_color_negative', 'suffix' => " !important"));
			//.$this->render_css(array('property' => 'color', 'value' => 'button_text_color_negative'))

			if(isset($this->options['button_color_negative']) && $this->options['button_color_negative'] != '')
			{
				if(!isset($obj_base))
				{
					$obj_base = new mf_base();
				}

				$out .= "color: ".$obj_base->get_text_color_from_background($this->options['button_color_negative'])." !important";
			}

		$out .= "}

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

				header > div, #mf-after-header > div, #mf-pre-content > div, #mf-content > div, #mf-pre-footer > div, footer > div, .full_width > div > .widget .section, .full_width > div > .widget > div
				{"
					.$this->render_css(array('property' => 'padding', 'value' => 'main_padding'))
					."position: relative;
				}

				#wrapper
				{"
					.$this->render_css(array('property' => 'background', 'value' => 'body_bg'))
					.$this->render_css(array('property' => 'background-color', 'value' => 'body_bg_color'))
					.$this->render_css(array('property' => 'background-image', 'prefix' => 'url(', 'value' => 'body_bg_image', 'suffix' => '); background-size: cover'))
					//."min-height: 100vh;" /* This will override footer background below footer */
					."overflow: hidden;"
				."}

					header
					{"
						.$this->render_css(array('property' => 'background', 'value' => 'header_bg'))
						.$this->render_css(array('property' => 'background-color', 'value' => 'header_bg_color'))
						.$this->render_css(array('property' => 'background-image', 'prefix' => 'url(', 'value' => 'header_bg_image', 'suffix' => '); background-size: cover'))
						.$this->render_css(array('property' => 'overflow', 'value' => 'header_overflow'))
						.$this->render_css(array('property' => 'position', 'value' => 'header_fixed'))
					."}

						header > div
						{"
							.$this->render_css(array('property' => 'padding', 'value' => 'header_padding'))
						."}

					.searchform
					{"
						.$this->render_css(array('property' => 'color', 'value' => 'search_color'))
						.$this->render_css(array('property' => 'font-size', 'value' => 'search_size'))
						."padding: .3em;
						position: relative;
					}

						.searchform .form_textfield
						{
							display: inline-block;
							position: relative;
							z-index: 1;
						}

							.searchform .form_textfield input
							{
								background: none;"
								.$this->render_css(array('property' => 'color', 'value' => 'search_color'))
								."display: inline-block;
								float: right;
								margin: 0;
								padding: .5em 2.2em .5em .5em;
							}

								.searchform.search_animate .form_textfield input
								{
									border-color: transparent;
									transition: all .4s ease;
									width: 0;
								}

									.searchform.search_animate .form_textfield input:focus
									{
										border-color: #e1e1e1;
										width: 100%;
									}

						.searchform .fa
						{
							position: absolute;
							right: 1em;
							top: 1em;
						}

					header .searchform
					{
						float: right;
					}";

					if(is_active_widget_area('widget_slide'))
					{
						$out .= "#mf-slide-nav
						{
							background: rgba(0, 0, 0, .7);
							bottom: 0;
							display: none;
							left: 0;
							position: absolute;
							position: fixed;
							right: 0;
							top: 0;
							z-index: 1003;
						}

							#mf-slide-nav > div
							{"
								.$this->render_css(array('property' => 'background', 'value' => 'slide_nav_bg'))
								."bottom: 0;"
								.$this->render_css(array('property' => 'color', 'value' => 'slide_nav_color'))
								.$this->render_css(array('property' => 'font-family', 'value' => 'nav_font'))
								."overflow: hidden;
								padding: 3.5em 0 1em;
								position: absolute;
								top: 0;
								width: 90%;
								max-width: 300px;
							}

								#mf-slide-nav .searchform
								{
									background: #000;
									padding-left: 1.5em;
								}

									#mf-slide-nav .searchform #s
									{
										border: 0;
										color: #fff;
										padding-bottom: .1em;
										transition: all .4s ease;
									}

										#mf-slide-nav .searchform:hover #s
										{
											text-indent: .3em;
										}

									#mf-slide-nav .searchform .fa
									{
										right: .8em;
										top: .8em;
									}

								#mf-slide-nav .fa-times
								{
									font-size: 2em;
									margin: 3% 4% 0 0;
									position: absolute;
									right: 0;
									top: 0;
								}

								#mf-slide-nav ul, #mf-slide-nav p
								{
									margin-bottom: 1em;
								}

								#mf-slide-nav ul
								{
									list-style: none;
								}

									#mf-slide-nav .theme_nav
									{"
										.$this->render_css(array('property' => 'font-size', 'value' => 'nav_size'))
									."}

										#mf-slide-nav .theme_nav ul a
										{"
											.$this->render_css(array('property' => 'color', 'value' => 'slide_nav_color'))
											."display: block;
											letter-spacing: .2em;
											overflow: hidden;"
											.$this->render_css(array('property' => 'padding', 'value' => 'slide_nav_link_padding'))
											."text-overflow: ellipsis;
											transition: all .4s ease;
											white-space: nowrap;
										}

											#mf-slide-nav .theme_nav ul a:hover
											{"
												.$this->render_css(array('property' => 'background', 'value' => 'slide_nav_bg_hover'))
												.$this->render_css(array('property' => 'color', 'value' => 'slide_nav_color_hover'))
												."text-indent: .3em;
											}

											#mf-slide-nav .theme_nav li.current_page_item > a
											{"
												.$this->render_css(array('property' => 'background', 'value' => 'slide_nav_bg_hover'))
												.$this->render_css(array('property' => 'color', 'value' => 'slide_nav_color_current'))
											."}

										#mf-slide-nav .theme_nav li ul
										{
											margin-bottom: 0;
										}

										/* Hide children until hover or current page */
										#mf-slide-nav .theme_nav .sub-menu
										{
											display: block;
										}

										#mf-slide-nav .theme_nav.is_large .sub-menu
										{
											display: none;
										}

											#mf-slide-nav .theme_nav.is_large li:hover > .sub-menu, #mf-slide-nav .theme_nav.is_large li.current-menu-item > .sub-menu, #mf-slide-nav .theme_nav.is_large li.current-menu-ancestor > .sub-menu
											{
												display: block;
											}
										/* */

										#mf-slide-nav .theme_nav li ul a
										{"
											.$this->render_css(array('property' => 'background', 'value' => 'slide_nav_sub_bg'))
											."text-indent: 1.4em;
										}

											#mf-slide-nav .theme_nav li ul a:hover
											{"
												.$this->render_css(array('property' => 'background', 'value' => 'slide_nav_sub_bg_hover'))
												."text-indent: 2em;
											}";
					}

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

	function get_external_css($theme_version)
	{
		if(isset($this->options['external_css']) && $this->options['external_css'] != '')
		{
			$arr_roles_check = array(
				'is_super_admin' => IS_SUPER_ADMIN,
				'is_admin' => IS_ADMIN,
				'is_editor' => IS_EDITOR,
				'is_author' => IS_AUTHOR,
			);

			$arr_external_css = explode("\n", $this->options['external_css']);

			foreach($arr_external_css as $external_css)
			{
				$is_allowed = true;

				foreach($arr_roles_check as $key => $value)
				{
					if(substr($external_css, 0, 1) == "[")
					{
						if(substr($external_css, 0, (strlen($key) + 2)) == "[".$key."]")
						{
							if(is_user_logged_in() && $value == true)
							{
								$external_css = str_replace("[".$key."]", "", $external_css);
							}

							else
							{
								$is_allowed = false;
							}

							break;
						}
					}
				}

				if($is_allowed)
				{
					mf_enqueue_style('style_'.md5($external_css), $external_css, $theme_version);
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

	function template_redirect()
	{
		if(is_author() && get_option('setting_theme_core_display_author_pages') == 'no')
		{
			wp_redirect(get_option('home'), 301);
			exit; 
		}
	}

	function wp_sitemaps_add_provider($provider, $name)
	{
		if('users' === $name)
		{
			return false;
		}

		return $provider;
	}

	function wp_sitemaps_posts_query_args($args, $post_type)
	{
		if(!isset($args['post__not_in'])){	$args['post__not_in'] = array();}

		$this->get_public_posts(array('allow_noindex' => true, 'allow_password_protected' => true));

		foreach($this->arr_public_posts as $post_id => $post_title)
		{
			if($this->has_noindex($post_id) || post_password_required($post_id))
			{
				$args['post__not_in'][] = $post_id;
			}
		}

		return $args;
	}

	function wp_sitemaps_taxonomies($taxonomies)
	{
		unset($taxonomies['category']);

        return $taxonomies;
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
						$out .= "<img src='".$data['image']."' alt='".sprintf(__("Logo for %s", $this->lang_key), $site_name.($site_description != '' ? " | ".$site_description : ''))."'>";
					}

					else
					{
						if($header_logo != '')
						{
							$out .= "<img src='".$header_logo."'".($header_mobile_logo != '' ? " class='hide_if_mobile'" : "")." alt='".sprintf(__("Logo for %s", $this->lang_key), $site_name.($site_description != '' ? " | ".$site_description : ''))."'>";
						}

						if($header_mobile_logo != '')
						{
							$out .= "<img src='".$header_mobile_logo."'".($header_logo != '' ? " class='show_if_mobile'" : "")." alt='".sprintf(__("Mobile Logo for %s", $this->lang_key), $site_name.($site_description != '' ? " | ".$site_description : ''))."'>";
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

	function get_search_theme_core($data = array())
	{
		if(!isset($data['placeholder']) || $data['placeholder'] == ''){			$data['placeholder'] = __("Search for", $this->lang_key);}
		if(!isset($data['hide_on_mobile'])){									$data['hide_on_mobile'] = 'no';}
		if(!isset($data['animate']) || $data['animate'] == ''){					$data['animate'] = 'yes';}

		return "<form action='".get_site_url()."' method='get' class='searchform mf_form".($data['hide_on_mobile'] == 'yes' ? " hide_on_mobile" : "").($data['animate'] == 'yes' ? " search_animate" : "")."'>"
			.show_textfield(array('type' => 'search', 'name' => 's', 'value' => check_var('s'), 'placeholder' => $data['placeholder'], 'xtra' => " autocomplete='off'"))
			."<i class='fa fa-search'></i>"
		."</form>";
	}
	#################################

	/* Admin */
	#################################
	function clone_single_post($data = array())
	{
		if(!isset($data['go_deeper'])){				$data['go_deeper'] = true;}
		if(!isset($data['include_title_copy'])){	$data['include_title_copy'] = true;}
		if(!isset($data['include_status'])){		$data['include_status'] = false;}

		$post = get_post($this->post_id_old);

		if($post == null)
		{
			return false;
		}

		if($data['include_title_copy'])
		{
			$post->post_title .= " (".__("copy", $this->lang_key).")";
		}

		$new_post = array(
			'post_name' => $post->post_name,
			'post_type' => $post->post_type,
			'ping_status' => $post->ping_status,
			'post_parent' => $post->post_parent,
			'menu_order' => $post->menu_order,
			'post_password' => $post->post_password,
			'post_excerpt' => $post->post_excerpt,
			'comment_status' => $post->comment_status,
			'post_title' => $post->post_title,
			'post_content' => $post->post_content,
			'post_author' => $post->post_author,
			'to_ping' => $post->to_ping,
			'pinged' => $post->pinged,
			'post_content_filtered' => $post->post_content_filtered,
			'post_category' => $post->post_category,
			'tags_input' => $post->tags_input,
			'tax_input' => $post->tax_input,
			'page_template' => $post->page_template,
			//'post_date' => $post->post_date, // default: current date
			//'post_date_gmt' => $post->post_date_gmt, // default: current gmt date
			//'post_status' => $post->post_status, // default: draft
		);

		if($data['include_status'])
		{
			$new_post['post_status'] = $post->post_status;
		}

		$this->post_id_new = wp_insert_post($new_post);

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

		if($data['go_deeper'])
		{
			do_action('clone_page', $this->post_id_old, $this->post_id_new);
		}

		return true;
	}

	function wp_loaded()
	{
		if(isset($_REQUEST['btnPostClone']) && IS_EDITOR)
		{
			$post_id = check_var('post_id');

			if($post_id > 0)
			{
				$this->post_id_old = $post_id;

				if($this->clone_single_post())
				{
					mf_redirect(admin_url("edit.php?post_type=".get_post_type($post_id)."&s=".get_post_title($post_id)));
					//mf_redirect(admin_url("post.php?post=".$this->post_id."&action=edit"));
				}

				else
				{
					wp_die(__("Error cloning post", $this->lang_key));
				}
			}
		}
	}

	function row_actions($actions, $post)
	{
		if(IS_EDITOR && $post->post_status == 'publish')
		{
			$actions['clone'] = "<a href='".admin_url("edit.php?post_type=".$post->post_type."&btnPostClone&post_id=".$post->ID)."'>".__("Clone", $this->lang_key)."</a>";
		}

		return $actions;
	}

	function column_header($cols)
	{
		unset($cols['comments']);

		if($this->is_site_public() && check_var('post_status') != 'trash')
		{
			$cols['seo'] = __("SEO", $this->lang_key);
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
								.sprintf(__("The page %s have the exact same title. Please, try to not have duplicates because that will hurt your SEO.", $this->lang_key), get_post_title($post_id_duplicate))
							."</a>
						</div>";
					break;

					case 'no_title':
						echo "<i class='fa fa-times fa-lg red' title='".__("You have not set a title for this page", $this->lang_key)."'></i>";
					break;

					case 'duplicate_excerpt':
						echo "<i class='fa fa-times fa-lg red'></i>
						<div class='row-actions'>
							<a href='".admin_url("post.php?post=".$post_id_duplicate."&action=edit")."'>"
								.sprintf(__("The page %s have the exact same excerpt", $this->lang_key), get_post_title($post_id_duplicate))
							."</a>
						</div>";
					break;

					case 'no_excerpt':
						echo "<i class='fa fa-times fa-lg red' title='".__("You have not set an excerpt for this page", $this->lang_key)."'></i>";
					break;

					case 'inconsistent_url':
						echo "<i class='fa fa-exclamation-triangle fa-lg yellow' title='".__("The URL is not correlated to the title", $this->lang_key)."'></i>";
					break;

					case 'long_title':
						echo "<i class='fa fa-exclamation-triangle fa-lg yellow' title='".__("The title might be too long to show in search engines", $this->lang_key)." (".strlen($site_title)." > ".$title_limit.")'></i>";
					break;

					case 'long_excerpt':
						echo "<i class='fa fa-exclamation-triangle fa-lg yellow' title='".__("The excerpt (meta description) might be too long to show in search engines", $this->lang_key)." (".strlen($post->post_excerpt)." > ".$excerpt_limit.")'></i>";
					break;

					case 'short_content':
						echo "<i class='fa fa-exclamation-triangle fa-lg yellow' title='".__("The content should be longer", $this->lang_key)." (".strlen($post->post_content)." > ".$content_limit.")'></i>";
					break;

					case 'no_sub_heading':
						echo "<i class='fa fa-exclamation-triangle fa-lg yellow' title='".__("There should be an H2 in the content", $this->lang_key)."'></i>";
					break;

					case 'password_protected':
						echo "<i class='fa fa-lock fa-lg grey' title='".__("The page is password protected", $this->lang_key)."'></i>";
					break;

					case 'not_published':
					case 'not_indexed':
						echo "<i class='fa fa-eye-slash fa-lg grey' title='".__("The page is not published or indexed", $this->lang_key)."'></i>";
					break;

					default:
						echo "<i class='fa fa-check fa-lg green' title='".__("Well done! The page is SEO approved!", $this->lang_key)."'></i>";
					break;
				}
			break;
		}
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

	function check_if_correct_post_type($post_id)
	{
		if($post_id > 0)
		{
			$obj_base = new mf_base();

			return in_array(get_post_type($post_id), $obj_base->get_post_types_for_metabox(array('exclude_from_search' => false))); //'public' => true, 
		}

		else
		{
			return true;
		}
	}

	function check_if_published($post_id)
	{
		$is_published = $is_not_published = true;

		if($post_id > 0)
		{
			$post_status = get_post_status($post_id);

			if($post_status == 'publish')
			{
				$is_not_published = false;
			}

			else
			{
				$is_published = false;
			}
		}

		return array($is_published, $is_not_published);
	}

	function rwmb_meta_boxes($meta_boxes)
	{
		if(IS_ADMIN)
		{
			$post_id = check_var('post');
			/*$post_id = get_rwmb_post_id(array(
				'meta_key' => 'meta_theme_core_',
			));*/

			$arr_fields = array();

			if($this->is_site_public() && $this->check_if_correct_post_type($post_id))
			{
				$arr_fields[] = array(
					'name' => __("Index", $this->lang_key),
					'id' => $this->meta_prefix.'page_index',
					'type' => 'select',
					'options' => array(
						'' => "-- ".__("Choose Here", $this->lang_key)." --",
						'noindex' => __("Do not Index", $this->lang_key),
						'nofollow' => __("Do not Follow Links", $this->lang_key),
						'none' => __("Do not Index and do not follow links", $this->lang_key),
					),
				);
			}

			list($is_published, $is_not_published) = $this->check_if_published($post_id);

			if($is_not_published)
			{
				$arr_fields[] = array(
					'name' => __("Publish", $this->lang_key),
					'id' => $this->meta_prefix.'publish_date',
					'type' => 'datetime',
				);
			}

			if($is_published)
			{
				$arr_fields[] = array(
					'name' => __("Unpublish", $this->lang_key),
					'id' => $this->meta_prefix.'unpublish_date',
					'type' => 'datetime',
				);
			}

			if(count($arr_fields) > 0)
			{
				$obj_base = new mf_base();

				$meta_boxes[] = array(
					'id' => $this->meta_prefix.'publish',
					'title' => __("Publish Settings", $this->lang_key),
					'post_types' => $obj_base->get_post_types_for_metabox(),
					'context' => 'side',
					'priority' => 'low',
					'fields' => $arr_fields,
				);
			}
		}

		return $meta_boxes;
	}

	function save_post($post_id, $post, $update)
	{
		/*if(in_array($post->post_type, array('page', 'post')))
		{
			$field_id = $this->meta_prefix.'display_featured_image';
			$field_value = check_var($field_id);

			update_post_meta($post_id, $field_id, $field_value);
		}*/

		/* Send e-mail to all editors if it is a draft and the user saving the draft is an author, but not an editor */
		if(isset($post->post_status) && $post->post_status == 'draft' && IS_AUTHOR && !IS_EDITOR && get_option('setting_send_email_on_draft') == 'yes')
		{
			$post_title = get_the_title($post);
			$post_url = get_permalink($post);

			$mail_subject = sprintf(__("The draft (%s) has been saved", $this->lang_key), $post_title);
			$mail_content = sprintf(__("The draft (%s) has been saved and might be ready for publishing", $this->lang_key), "<a href='".$post_url."'>".$post_title."</a>");

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
			'' => __("No", $this->lang_key),
			'yes' => __("Yes", $this->lang_key)
		);

		$out .= "<h3>".__("Redirect", $this->lang_key)."</h3>"
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
			if(apply_filters('is_public_page', true))
			{
				mf_redirect(wp_login_url()."?redirect_to=".$_SERVER['REQUEST_URI']);
			}
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

	function recommend_config($data)
	{
		global $obj_base;

		if(!isset($data['file'])){		$data['file'] = '';}

		$update_with = "";

		/* Use instead of template_redirect -> is_author()? */
		/*if(1 == 1)
		{
			switch($obj_base->get_server_type())
			{
				default:
				case 'apache':
					$update_with .= "<IfModule mod_rewrite.c>\r\n"
					."	RewriteCond %{QUERY_STRING} ^author= [NC]\r\n"
					."	RewriteRule .* /404/? [L,R=301]\r\n"
					."	RewriteRule ^author/ /404/? [L,R=301]\r\n"
					."</IfModule>";
				break;

				case 'nginx':
					$update_with .= "location /author= {\r\n"
					."	deny all;\r\n"
					."}";
				break;
			}
		}*/

		if((!is_multisite() || is_main_site()) && get_site_option('setting_theme_enable_wp_api', get_option('setting_theme_enable_wp_api')) != 'yes')
		{
			if(!isset($obj_base))
			{
				$obj_base = new mf_base();
			}

			switch($obj_base->get_server_type())
			{
				default:
				case 'apache':
					$update_with .= "<IfModule mod_rewrite.c>\r\n"
					."	RewriteCond %{REQUEST_URI} ^/?(xmlrpc\.php)$\r\n"
					."	RewriteRule .* /404/ [L,NC]\r\n"
					."</IfModule>";
				break;

				case 'nginx':
					$update_with .= "location /xmlrpc.php {\r\n"
					."	deny all;\r\n"
					."}";
				break;
			}
		}

		if($update_with != '')
		{
			$data['html'] .= $obj_base->update_config(array(
				'plugin_name' => "MF Theme Core",
				'file' => $data['file'],
				'update_with' => $update_with,
				'auto_update' => true,
			));
		}

		return $data;
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

	function widgets_init()
	{
		register_widget('widget_theme_core_area');
		register_widget('widget_theme_core_logo');
		register_widget('widget_theme_core_search');
		register_widget('widget_theme_core_news');
		register_widget('widget_theme_core_info');
		register_widget('widget_theme_core_related');
		register_widget('widget_theme_core_promo');
		register_widget('widget_theme_core_page_index');
		//$this->mf_unregister_widget('WP_Widget_Recent_Posts');

		$this->mf_unregister_widget('WP_Widget_Archives');
		$this->mf_unregister_widget('WP_Widget_Calendar');
		$this->mf_unregister_widget('WP_Widget_Categories');
		//$this->mf_unregister_widget('WP_Nav_Menu_Widget');
		$this->mf_unregister_widget('WP_Widget_Links');
		$this->mf_unregister_widget('WP_Widget_Meta');
		$this->mf_unregister_widget('WP_Widget_Pages');
		$this->mf_unregister_widget('WP_Widget_Recent_Comments');
		$this->mf_unregister_widget('WP_Widget_RSS');
		$this->mf_unregister_widget('WP_Widget_Search');
		$this->mf_unregister_widget('WP_Widget_Tag_Cloud');
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

	function get_fonts_for_select()
	{
		$arr_data = array(
			'' => "-- ".__("Choose Here", $this->lang_key)." --",
		);

		if(count($this->options_fonts) > 0)
		{
			foreach($this->options_fonts as $key => $value)
			{
				$arr_data[$key] = $value['title'];
			}
		}

		return $arr_data;
	}

	function customize_register($wp_customize)
	{
		$plugin_include_url = plugin_dir_url(__FILE__);
		$plugin_version = get_plugin_version(__FILE__);

		mf_enqueue_style('style_theme_core_customizer', $plugin_include_url."style_customizer.css", $plugin_version);
		mf_enqueue_script('script_theme_core_customizer', $plugin_include_url."script_customizer.js", $plugin_version);

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
			if(!isset($this->param['input_attrs'])){		$this->param['input_attrs'] = array();}

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
								'' => "-- ".__("Choose Here", $this->lang_key)." --",
								'left' => __("Left", $this->lang_key),
								'center' => __("Center", $this->lang_key),
								'right' => __("Right", $this->lang_key),
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
								2 => __("Yes", $this->lang_key),
								1 => __("No", $this->lang_key),
							);

							$this->add_select(array('choices' => $arr_data));
						break;

						case 'clear':
							$arr_data = array(
								'' => "-- ".__("Choose Here", $this->lang_key)." --",
								'left' => __("Left", $this->lang_key),
								'right' => __("Right", $this->lang_key),
								'both' => __("Both", $this->lang_key),
								'none' => __("None", $this->lang_key),
							);

							$this->add_select(array('choices' => $arr_data));
						break;

						case 'direction':
							$arr_data = array(
								'horizontal' => __("Horizontal", $this->lang_key),
								'vertical' => __("Vertical", $this->lang_key),
							);

							$this->add_select(array('choices' => $arr_data));
						break;

						case 'date':
						case 'email':
						case 'hidden':
						case 'number':
						case 'range':
						case 'text':
						case 'textarea':
						case 'url':
							$wp_customize->add_control($this->param['id'], array(
								'label' => $this->param['title'],
								'section' => $this->id_temp,
								'type' => $this->param['type'],
								'input_attrs' => $this->param['input_attrs'],
							));
						break;

						case 'float':
							$arr_data = array(
								'' => "-- ".__("Choose Here", $this->lang_key)." --",
								'none' => __("None", $this->lang_key),
								'left' => __("Left", $this->lang_key),
								'center' => __("Center", $this->lang_key),
								'right' => __("Right", $this->lang_key),
								'initial' => __("Initial", $this->lang_key),
								'inherit' => __("Inherit", $this->lang_key),
							);

							$this->add_select(array('choices' => $arr_data));
						break;

						case 'font':
							$this->add_select(array('choices' => $this->get_fonts_for_select()));
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
								'' => "-- ".__("Choose Here", $this->lang_key)." --",
								'visible' => __("Visible", $this->lang_key),
								'hidden' => __("Hidden", $this->lang_key),
								'scroll' => __("Scroll", $this->lang_key),
								'auto' => __("Auto", $this->lang_key),
								'initial' => __("Initial", $this->lang_key),
								'inherit' => __("Inherit", $this->lang_key),
							);

							$this->add_select(array('choices' => $arr_data));
						break;

						case 'position':
							$arr_data = array(
								'' => "-- ".__("Choose Here", $this->lang_key)." --",
								'absolute' => __("Absolute", $this->lang_key),
								'fixed' => __("Fixed", $this->lang_key),
								'relative' => __("Relative", $this->lang_key),
								'sticky' => __("Sticky", $this->lang_key),
							);

							$this->add_select(array('choices' => $arr_data));
						break;

						case 'text_decoration':
							$arr_data = array(
								'' => "-- ".__("Choose Here", $this->lang_key)." --",
								'none' => __("None", $this->lang_key),
								'underline' => __("Underline", $this->lang_key),
							);

							$this->add_select(array('choices' => $arr_data));
						break;

						case 'text_transform':
							$arr_data = array(
								'' => "-- ".__("Choose Here", $this->lang_key)." --",
								'uppercase' => __("Uppercase", $this->lang_key),
								'lowercase' => __("Lowercase", $this->lang_key),
							);

							$this->add_select(array('choices' => $arr_data));
						break;

						case 'weight':
							$arr_data = array(
								'' => "-- ".__("Choose Here", $this->lang_key)." --",
								'lighter' => __("Lighter", $this->lang_key),
								'normal' => __("Normal", $this->lang_key),
								'bold' => __("Bold", $this->lang_key),
								'bolder' => __("Bolder", $this->lang_key),
								'initial' => __("Initial", $this->lang_key),
								'inherit' => __("Inherit", $this->lang_key),
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

	#################################
	function copy_file()
	{
		if(file_exists($this->file_dir_to))
		{
			if(get_option('option_uploads_fixed') < date("Y-m-d", strtotime("-1 month")))
			{
				if(file_exists($this->file_dir_from) && is_file($this->file_dir_from))
				{
					// Some files are still in use in the old hierarchy
					//unlink($this->file_dir_from);
				}
			}
		}

		else
		{
			if(file_exists($this->file_dir_from))
			{
				@mkdir(dirname($this->file_dir_to), 0755, true);

				if(!copy($this->file_dir_from, $this->file_dir_to))
				{
					do_log("File was NOT copied: ".$this->file_dir_from." -> ".$this->file_dir_to);
				}
			}
		}
	}
	#################################

	// Cron
	#################################
	function publish_posts()
	{
		global $wpdb;

		$result = $wpdb->get_results($wpdb->prepare("SELECT ID, meta_key, meta_value FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id WHERE (meta_key = %s OR meta_key = %s) AND meta_value > %s", $this->meta_prefix.'publish_date', $this->meta_prefix.'unpublish_date', DEFAULT_DATE)); //post_status = 'publish' AND 

		if($wpdb->num_rows > 0)
		{
			foreach($result as $r)
			{
				$post_id = $r->ID;
				$post_meta_key = $r->meta_key;
				$post_meta_value = $r->meta_value;

				if($post_meta_value <= date("Y-m-d H:i:s"))
				{
					switch($post_meta_key)
					{
						case $this->meta_prefix.'publish_date':
							$post_status = 'publish';
						break;

						case $this->meta_prefix.'unpublish_date':
							$post_status = 'draft';
						break;

						default:
							$post_status = '';

							do_log("publish_posts error: ".$wpdb->last_query);
						break;
					}

					if($post_status != '')
					{
						$post_data = array(
							'ID' => $post_id,
							'post_status' => $post_status,
							'meta_input' => array(
								$post_meta_key => '',
							),
						);

						wp_update_post($post_data);
					}
				}
			}
		}
	}

	function check_style_source()
	{
		delete_option('option_theme_source_style_url');

		$setting_base_template_site = get_option('setting_base_template_site');

		if($setting_base_template_site != '' && $setting_base_template_site != get_site_url())
		{
			$log_message_1 = sprintf("I could not process the feed from %s since the URL was not a valid one", $setting_base_template_site);

			if(filter_var($setting_base_template_site, FILTER_VALIDATE_URL))
			{
				$url = $setting_base_template_site."/wp-content/plugins/mf_theme_core/include/api/?type=get_style_source";

				list($content, $headers) = get_url_content(array(
					'url' => $url,
					'catch_head' => true,
				));

				$log_message_2 = sprintf("The response from %s had an error", $url);

				switch($headers['http_code'])
				{
					case 200:
						$json = json_decode($content, true);

						$log_message_3 = sprintf("The feed from %s returned an error (%s)", $url, $content);

						if(isset($json['success']) && $json['success'] == true)
						{
							$style_changed = $json['response']['style_changed'];
							$style_url = $json['response']['style_url'];

							update_option('option_theme_source_style_url', ($style_changed > get_option('option_theme_saved') ? $style_url : ""), 'no');

							do_log($log_message_3, 'trash');
						}

						else
						{
							do_log($log_message_3);
						}

						do_log($log_message_2, 'trash');
					break;

					default:
						do_log($log_message_2." (".$headers['http_code'].")");
					break;
				}

				do_log($log_message_1, 'trash');
			}

			else
			{
				do_log($log_message_1);
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

		$wpdb->get_results($wpdb->prepare("SELECT comment_ID FROM ".$wpdb->comments." WHERE comment_approved NOT IN('spam', 'trash') AND comment_type = %s LIMIT 0, 1", 'comment'));

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
			$count_message = "&nbsp;<span class='update-plugins' title='".__("Theme Updates", $this->lang_key)."'>
				<span>".$rows."</span>
			</span>";

			if(count($menu) > 0)
			{
				foreach($menu as $key => $item)
				{
					if($item[2] == 'themes.php')
					{
						$menu[$key][0] = strip_tags($item[0]).$count_message;

						break;
					}
				}
			}
		}

		return $count_message;
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
		global $globals, $obj_base;

		if(!isset($obj_base))
		{
			$obj_base = new mf_base();
		}

		$globals['mf_theme_files'] = array();

		get_file_info(array('path' => $upload_path, 'callback' => array($this, 'get_previous_backups')));

		$globals['mf_theme_files'] = $obj_base->array_sort(array('array' => $globals['mf_theme_files'], 'on' => 'time', 'order' => 'desc'));

		return $globals['mf_theme_files'];
	}

	function get_options_page()
	{
		global $done_text, $error_text;

		$out = "";

		$theme_dir_name = $this->get_theme_dir_name();

		$strFileUrl = check_var('strFileUrl');
		$strFileName = check_var('strFileName');
		$strFileContent = isset($_REQUEST['strFileContent']) ? $_REQUEST['strFileContent'] : "";

		list($upload_path, $upload_url) = get_uploads_folder($theme_dir_name);

		$this->get_params();

		if(isset($_POST['btnThemeBackup']) && wp_verify_nonce($_POST['_wpnonce_theme_backup'], 'theme_backup'))
		{
			if(count($this->options) > 0)
			{
				$file_base = $theme_dir_name."_".str_replace(array(".", "/"), "_", get_site_url_clean(array('trim' => "/")));
				$file = prepare_file_name($file_base).".json";

				$success = set_file_content(array('file' => $upload_path.$file, 'mode' => 'a', 'content' => json_encode($this->options)));

				if($success == true)
				{
					$done_text = __("The theme settings were backed up", $this->lang_key);
				}

				else
				{
					$error_text = __("It was not possible to backup the theme settings", $this->lang_key);
				}
			}

			else
			{
				$error_text = __("There were no theme settings to save", $this->lang_key);
			}
		}

		else if(isset($_REQUEST['btnThemeRestore']))
		{
			if($strFileUrl != '')
			{
				list($strFileContent, $headers) = get_url_content(array('url' => $strFileUrl, 'catch_head' => true));
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
					$setting_theme_ignore_style_on_restore = get_option('setting_theme_ignore_style_on_restore');

					if(!is_array($setting_theme_ignore_style_on_restore))
					{
						$setting_theme_ignore_style_on_restore = array_map('trim', explode(",", $setting_theme_ignore_style_on_restore));
					}

					foreach($json as $key => $value)
					{
						if(!in_array($key, $setting_theme_ignore_style_on_restore))
						{
							set_theme_mod($key, $value);
						}
					}

					$done_text = __("I restored the theme backup for you", $this->lang_key);

					update_option('option_theme_saved', date("Y-m-d H:i:s"), 'no');
					delete_option('option_theme_source_style_url');

					$strFileContent = "";
				}

				else
				{
					$error_text = __("There is something wrong with the source to restore", $this->lang_key)." (".htmlspecialchars($strFileContent)." -> ".var_export($json, true).")";
				}
			}
		}

		else if(isset($_GET['btnThemeDelete']) && wp_verify_nonce($_GET['_wpnonce_theme_delete'], 'theme_delete_'.$strFileName))
		{
			unlink($upload_path.$strFileName);

			$done_text = __("The file was deleted successfully", $this->lang_key);
		}

		else
		{
			$setting_base_template_site = get_option('setting_base_template_site');

			if($setting_base_template_site != '')
			{
				$setting_base_template_site = remove_protocol(array('url' => $setting_base_template_site, 'clean' => true, 'trim' => true));

				$option_theme_source_style_url = get_option('option_theme_source_style_url');

				if($option_theme_source_style_url != '')
				{
					$error_text = sprintf(__("The theme at %s has got a newer version of saved style which can be %srestored here%s", $this->lang_key), $setting_base_template_site, "<a href='".admin_url("themes.php?page=theme_options&btnThemeRestore&strFileUrl=".$option_theme_source_style_url)."'>", "</a>");
				}
			}
		}

		$out .= "<div class='wrap'>
			<h2>".__("Theme Backup", $this->lang_key)."</h2>"
			.get_notification();

			if($upload_path != '')
			{
				$setting_base_template_site = get_option('setting_base_template_site');
				$is_allowed_to_backup = $setting_base_template_site == '' || $setting_base_template_site == get_site_url();

				$out .= "<div id='poststuff'>
					<div id='post-body' class='columns-2'>
						<div id='post-body-content'>";

							$arr_backups = $this->get_previous_backups_list($upload_path);
							$count_temp = count($arr_backups);

							if($count_temp > 0)
							{
								$option_theme_saved = get_option('option_theme_saved');

								$out .= "<table class='widefat striped'>";

									$arr_header[] = __("Existing", $this->lang_key);
									$arr_header[] = __("Date", $this->lang_key);

									$out .= show_table_header($arr_header)
									."<tbody>";

										for($i = 0; $i < $count_temp; $i++)
										{
											$file_name = $arr_backups[$i]['name'];
											$file_time = date("Y-m-d H:i:s", $arr_backups[$i]['time']);

											$out .= "<tr".($setting_base_template_site != get_site_url() && $file_time > $option_theme_saved ? " class='green'" : "").">
												<td>"
													.$arr_backups[$i]['name']
													."<div class='row-actions'>
														<a href='".$upload_url.$file_name."'>".__("Download", $this->lang_key)."</a>
														 | <a href='".admin_url("themes.php?page=theme_options&btnThemeRestore&strFileName=".$file_name)."' rel='confirm'>".__("Restore", $this->lang_key)."</a>";

														if($is_allowed_to_backup)
														{
															$out .= " | <a href='".wp_nonce_url(admin_url("themes.php?page=theme_options&btnThemeDelete&strFileName=".$file_name), 'theme_delete_'.$file_name, '_wpnonce_theme_delete')."' rel='confirm'>".__("Delete", $this->lang_key)."</a>";
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
								<h3 class='hndle'><span>".__("External Backup", $this->lang_key)."</span></h3>
								<div class='inside'>
									<form method='post' action='' class='mf_form'>
										<div>"
											.show_textarea(array('name' => 'strFileContent', 'value' => stripslashes($strFileContent)))
											.show_button(array('name' => 'btnThemeRestore', 'text' => __("Restore", $this->lang_key)))
										."</div>
									</form>
								</div>
							</div>
						</div>";

						if($is_allowed_to_backup)
						{
							$out .= "<div id='postbox-container-1'>
								<div class='postbox'>
									<h3 class='hndle'><span>".__("New Backup", $this->lang_key)."</span></h3>
									<div class='inside'>
										<form method='post' action='' class='mf_form'>"
											.show_button(array('name' => 'btnThemeBackup', 'text' => __("Save", $this->lang_key)))
											.wp_nonce_field('theme_backup', '_wpnonce_theme_backup', true, false)
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

		echo $out;
	}

	function admin_menu()
	{
		$menu_title = __("Theme Backup", $this->lang_key);
		add_theme_page($menu_title, $menu_title.$this->get_theme_updates_message(), 'edit_theme_options', 'theme_options', array($this, 'get_options_page'));

		if($this->has_comments() == false)
		{
			remove_menu_page("edit-comments.php");

			if(get_option('default_comment_status') == 'closed')
			{
				remove_submenu_page("options-general.php", "options-discussion.php");
			}
		}

		$setting_theme_core_templates = get_option('setting_theme_core_templates');

		if(is_array($setting_theme_core_templates) && count($setting_theme_core_templates) > 0)
		{
			foreach($setting_theme_core_templates as $post_id)
			{
				$post_title = get_post_title($post_id);
				$post_content = mf_get_post_content($post_id);

				if($post_title != '' || $post_content != '')
				{
					$menu_capability = 'edit_posts';
					$menu_slug = "post-new.php?post_type=page&post_title=".$post_title;

					if($post_content != '')
					{
						$menu_slug .= "&content=".$post_content;
					}

					$menu_title = sprintf(__("New '%s'", $this->lang_key), shorten_text(array('string' => $post_title, 'limit' => 15)));
					add_submenu_page("edit.php?post_type=page", $menu_title, " - ".$menu_title, $menu_capability, $menu_slug);
				}
			}
		}
	}

	function sites_column_header($cols)
	{
		unset($cols['registered']);
		unset($cols['lastupdated']);

		$cols['site_status'] = __("Status", $this->lang_key);
		$cols['theme'] = __("Theme", $this->lang_key);
		$cols['email'] = __("E-mail", $this->lang_key);
		$cols['last_updated'] = __("Updated", $this->lang_key);

		return $cols;
	}

	function sites_column_cell($col, $id)
	{
		global $wpdb, $obj_base;

		if(get_blog_status($id, 'deleted') == 0)
		{
			if(!isset($obj_base))
			{
				$obj_base = new mf_base();
			}

			switch_to_blog($id);

			switch($col)
			{
				case 'site_status':
					$flag_image = $this->get_flag_image($id);

					if($flag_image != '')
					{
						echo "<img src='".$flag_image."' class='alignleft'>&nbsp;";
					}

					list($color, $icon, $text) = $this->get_site_status_data(array('type' => 'sites_column'));

					echo "<i class='".$icon." fa-2x ".$color."' title='".$text."'></i>";
				break;

				case 'theme':
					/* Get last parent update */
					$restore_notice = $restore_url = "";

					if(in_array(get_blog_option($id, 'template'), array('mf_parallax', 'mf_theme')))
					{
						$style_source = get_blog_option($id, 'setting_base_template_site');

						if($style_source != '')
						{
							if($style_source == get_site_url($id))
							{
								$restore_notice .= "&nbsp;<i class='fas fa-star fa-lg yellow' title='".__("This is the template theme design", $this->lang_key)."'></i>";
							}

							else
							{
								$option_theme_source_style_url = get_blog_option($id, 'option_theme_source_style_url');

								if($option_theme_source_style_url != '')
								{
									$restore_notice = "&nbsp;<span class='update-plugins' title='".__("Theme Updates", $this->lang_key)."'>
										<span>1</span>
									</span>";
									$restore_url = " | <a href='".get_admin_url($id, "themes.php?page=theme_options")."'>".__("Update", $this->lang_key)."</a>";
								}

								else
								{
									$option_theme_saved = get_blog_option($id, 'option_theme_saved');

									$restore_notice .= "&nbsp;<i class='fa fa-check fa-lg ".($option_theme_saved > date("Y-m-d H:i:s", strtotime("-1 month")) ? "green" : "grey")."' title='".__("The theme design is up to date", $this->lang_key)."'></i>";
								}
							}
						}

						else
						{
							$option_sync_sites = get_option('option_sync_sites', array());

							if(count($option_sync_sites) > 0)
							{
								$restore_notice .= "&nbsp;<i class='fas fa-star fa-lg yellow' title='".__("This is the template theme design", $this->lang_key)."'></i>";
							}
						}

						/*else
						{
							$restore_notice = "&nbsp;<span class='fa-stack'>
								<i class='fa fa-recycle fa-stack-1x'></i>
								<i class='fa fa-ban fa-stack-2x red'></i>
							</span>";
						}*/
					}

					echo get_blog_option($id, 'stylesheet')
					.$restore_notice
					."<div class='row-actions'>"
						."<a href='".get_admin_url($id, "admin.php?page=mf_site_manager/theme/index.php")."'>".__("Change", $this->lang_key)."</a>"
						.$restore_url
					."</div>";
				break;

				case 'email':
					//$admin_email = get_blog_option($id, 'admin_email');
					$admin_email = get_option('admin_email');

					if($admin_email != '')
					{
						list($prefix, $domain) = explode("@", $admin_email);

						echo "<a href='mailto:".$admin_email."'>".$prefix."</a>
						<div class='row-actions'>"
							."@".$domain
						."</div>";
					}
				break;

				case 'last_updated':
					$arr_post_types = $obj_base->get_post_types_for_metabox();
					$last_updated_manual_post_types = array_diff($arr_post_types, array('mf_custom_dashboard', 'int_page', 'mf_media_allowed', 'mf_social_feed', 'mf_social_feed_post', 'mf_calendar', 'mf_calendar_event'));

					$result = $wpdb->get_results("SELECT ID, post_title, post_modified FROM ".$wpdb->posts." WHERE post_type IN ('".implode("','", $last_updated_manual_post_types)."') AND post_status != 'auto-draft' ORDER BY post_modified DESC LIMIT 0, 1");
					//$last_updated_comments = $wpdb->get_var("SELECT comment_date FROM ".$wpdb->comments." ORDER BY comment_date LIMIT 0, 1");

					foreach($result as $r)
					{
						$post_id_manual = $r->ID;
						$post_title = ($r->post_title != '' ? $r->post_title : "(".__("unknown", $this->lang_key).")");
						$post_modified_manual = $r->post_modified;

						if($post_modified_manual > DEFAULT_DATE)
						{
							$row_actions = "";

							echo format_date($post_modified_manual);

							$row_actions .= ($row_actions != '' ? " | " : "")."<a href='".admin_url("post.php?action=edit&post=".$post_id_manual)."'>".shorten_text(array('string' => get_post_title($post_id_manual), 'limit' => 10))."</a>";

							$last_updated_automatic_post_types = array_diff($arr_post_types, array('post', 'page', 'mf_custom_dashboard', 'int_page', 'mf_media_allowed', 'mf_form', 'mf_custom_lists', 'mf_custom_item'));

							$result_auto = $wpdb->get_results("SELECT ID, post_title, post_modified FROM ".$wpdb->posts." WHERE post_type IN ('".implode("','", $last_updated_automatic_post_types)."') ORDER BY post_modified DESC LIMIT 0, 1");

							foreach($result_auto as $r)
							{
								$post_id_auto = $r->ID;
								$post_title = ($r->post_title != '' ? $r->post_title : "(".__("unknown", $this->lang_key).")");
								$post_modified_auto = $r->post_modified;

								if($post_modified_auto > $post_modified_manual)
								{
									$row_actions .= ($row_actions != '' ? " | " : "").__("Background", $this->lang_key).": ".format_date($post_modified_auto)." (<a href='".admin_url("post.php?action=edit&post=".$post_id_auto)."'>".shorten_text(array('string' => $post_title, 'limit' => 10))."</a>)";
								}

								if($row_actions != '')
								{
									echo "<div class='row-actions'>"
										.$row_actions
									."</div>";
								}
							}
						}

						/*else
						{
							do_log("last_updated: ".$wpdb->last_query);
						}*/
					}
				break;
			}

			restore_current_blog();
		}
	}

	/*function add_policy($content)
	{
		if(get_option('setting_cookie_info') > 0)
		{
			$content .= "<h3>".__("Theme", $this->lang_key)."</h3>
			<p>"
				.__("A cookie is saved when the visitor accepts the use of cookies on the site, to make sure that the message asking for permission does not appear again.", $this->lang_key)
			."</p>";
		}

		return $content;
	}*/

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

		update_option('option_database_optimized', date("Y-m-d H:i:s"), 'no');

		return __("I have optimized the site for you", $this->lang_key);
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

class widget_theme_core_area extends WP_Widget
{
	function __construct()
	{
		$this->obj_theme_core = new mf_theme_core();

		$this->widget_ops = array(
			'classname' => 'theme_widget_area',
			'description' => __("Add Widget Area", $this->obj_theme_core->lang_key)
		);

		$this->arr_default = array(
			'widget_area_id' => '',
			'widget_area_name' => '',
			'widget_area_columns' => 1,
			'widget_area_padding' => '',
		);

		parent::__construct(str_replace("_", "-", $this->widget_ops['classname']).'-widget', __("Widget Area", $this->obj_theme_core->lang_key), $this->widget_ops);
	}

	function widget($args, $instance)
	{
		extract($args);
		$instance = wp_parse_args((array)$instance, $this->arr_default);

		if(is_active_sidebar('widget_area_'.$instance['widget_area_id']))
		{
			echo $before_widget
				."<div id='widget_area_".str_replace("-", "_", $instance['widget_area_id'])."' class='widget_columns columns_".$instance['widget_area_columns']."'>";

					dynamic_sidebar('widget_area_'.$instance['widget_area_id']);

				echo "</div>"
			.$after_widget;
		}
	}

	function update($new_instance, $old_instance)
	{
		$instance = $old_instance;
		$new_instance = wp_parse_args((array)$new_instance, $this->arr_default);

		$instance['widget_area_id'] = strtolower(sanitize_text_field($new_instance['widget_area_id']));
		$instance['widget_area_name'] = sanitize_text_field($new_instance['widget_area_name']);
		$instance['widget_area_columns'] = sanitize_text_field($new_instance['widget_area_columns']);
		$instance['widget_area_padding'] = sanitize_text_field($new_instance['widget_area_padding']);

		return $instance;
	}

	function form($instance)
	{
		$instance = wp_parse_args((array)$instance, $this->arr_default);

		echo "<div class='mf_form'>"
			.show_textfield(array('name' => $this->get_field_name('widget_area_id'), 'text' => __("ID (Has to be unique)", $this->obj_theme_core->lang_key), 'value' => $instance['widget_area_id'], 'required' => true, 'xtra' => ($instance['widget_area_id'] != '' ? "readonly" : "")))
			.show_textfield(array('name' => $this->get_field_name('widget_area_name'), 'text' => __("Name", $this->obj_theme_core->lang_key), 'value' => $instance['widget_area_name'], 'required' => true))
			.show_textfield(array('type' => 'number', 'name' => $this->get_field_name('widget_area_columns'), 'text' => __("Columns", $this->obj_theme_core->lang_key), 'value' => $instance['widget_area_columns'], 'xtra' => "min='1' max='4'"));

			if($instance['widget_area_columns'] > 1)
			{
				echo show_textfield(array('name' => $this->get_field_name('widget_area_padding'), 'text' => __("Column Space", $this->obj_theme_core->lang_key), 'value' => $instance['widget_area_padding'], 'placeholder' => ".5em"));
			}

		echo "</div>";
	}
}

class widget_theme_core_logo extends WP_Widget
{
	function __construct()
	{
		$this->obj_theme_core = new mf_theme_core();

		$this->widget_ops = array(
			'classname' => 'theme_logo',
			'description' => __("Display Logo", $this->obj_theme_core->lang_key)
		);

		$this->arr_default = array(
			'logo_url' => '',
			'logo_display' => 'all',
			'logo_title' => '',
			'logo_image' => '',
			'logo_description' => '',
		);

		parent::__construct(str_replace("_", "-", $this->widget_ops['classname']).'-widget', __("Logo", $this->obj_theme_core->lang_key), $this->widget_ops);
	}

	function widget($args, $instance)
	{
		extract($args);
		$instance = wp_parse_args((array)$instance, $this->arr_default);

		$arr_data = array(
			'display' => $instance['logo_display'],
		);

		if($instance['logo_url'] != ''){			$arr_data['url'] = $instance['logo_url'];}
		if($instance['logo_title'] != ''){			$arr_data['title'] = $instance['logo_title'];}
		if($instance['logo_image'] != ''){			$arr_data['image'] = $instance['logo_image'];}
		if($instance['logo_description'] != ''){	$arr_data['description'] = $instance['logo_description'];}

		if(!isset($obj_theme_core))
		{
			$obj_theme_core = new mf_theme_core();
		}

		echo $before_widget
			.$this->obj_theme_core->get_logo($arr_data)
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
			'all' => __("Logo and Tagline", $this->obj_theme_core->lang_key),
			'title' => __("Logo", $this->obj_theme_core->lang_key),
			'tagline' => __("Tagline", $this->obj_theme_core->lang_key),
		);

		echo "<div class='mf_form'>
			<p>".__("If these are left empty, the chosen logo for the site will be displayed. If there is no chosen logo the site name will be displayed instead.", $this->obj_theme_core->lang_key)."</p>"
			.show_textfield(array('type' => 'url', 'name' => $this->get_field_name('logo_url'), 'text' => __("URL", $this->obj_theme_core->lang_key), 'value' => $instance['logo_url'], 'placeholder' => get_site_url()))
			.show_select(array('data' => $arr_data, 'name' => $this->get_field_name('logo_display'), 'text' => __("What to Display", $this->obj_theme_core->lang_key), 'value' => $instance['logo_display']));

			if($instance['logo_display'] != 'tagline')
			{
				if($instance['logo_image'] == '')
				{
					echo show_textfield(array('name' => $this->get_field_name('logo_title'), 'text' => __("Logo", $this->obj_theme_core->lang_key), 'value' => $instance['logo_title'], 'xtra' => " id='".$this->widget_ops['classname']."-title'"));
				}

				if($instance['logo_title'] == '')
				{
					echo get_media_library(array('type' => 'image', 'name' => $this->get_field_name('logo_image'), 'value' => $instance['logo_image']));
				}
			}

			if($instance['logo_display'] != 'title')
			{
				echo show_textfield(array('name' => $this->get_field_name('logo_description'), 'text' => __("Tagline", $this->obj_theme_core->lang_key), 'value' => $instance['logo_description']));
			}

		echo "</div>";
	}
}

class widget_theme_core_search extends WP_Widget
{
	function __construct()
	{
		$this->obj_theme_core = new mf_theme_core();

		$this->widget_ops = array(
			'classname' => 'theme_search',
			'description' => __("Display Search Form", $this->obj_theme_core->lang_key)
		);

		$this->arr_default = array(
			'search_placeholder' => "",
			'search_hide_on_mobile' => 'no',
			'search_animate' => 'yes',
			'search_listen_to_keystroke' => 'yes',
		);

		parent::__construct(str_replace("_", "-", $this->widget_ops['classname']).'-widget', __("Search", $this->obj_theme_core->lang_key), $this->widget_ops);
	}

	function widget($args, $instance)
	{
		extract($args);
		$instance = wp_parse_args((array)$instance, $this->arr_default);

		if($instance['search_listen_to_keystroke'] == 'yes')
		{
			$plugin_include_url = plugin_dir_url(__FILE__);
			$plugin_version = get_plugin_version(__FILE__);

			mf_enqueue_script('script_theme_core_search', $plugin_include_url."script_search.js", $plugin_version);
		}

		echo $this->obj_theme_core->get_search_theme_core(array(
			'placeholder' => $instance['search_placeholder'],
			'hide_on_mobile' => (isset($instance['search_hide_on_mobile']) ? $instance['search_hide_on_mobile'] : ''),
			'animate' => (isset($instance['search_animate']) ? $instance['search_animate'] : ''),
		));
	}

	function update($new_instance, $old_instance)
	{
		$instance = $old_instance;
		$new_instance = wp_parse_args((array)$new_instance, $this->arr_default);

		$instance['search_placeholder'] = sanitize_text_field($new_instance['search_placeholder']);
		$instance['search_hide_on_mobile'] = sanitize_text_field($new_instance['search_hide_on_mobile']);
		$instance['search_animate'] = sanitize_text_field($new_instance['search_animate']);
		$instance['search_listen_to_keystroke'] = sanitize_text_field($new_instance['search_listen_to_keystroke']);

		return $instance;
	}

	function form($instance)
	{
		$instance = wp_parse_args((array)$instance, $this->arr_default);

		echo "<div class='mf_form'>"
			.show_textfield(array('name' => $this->get_field_name('search_placeholder'), 'text' => __("Placeholder", $this->obj_theme_core->lang_key), 'value' => $instance['search_placeholder']))
			."<div class='flex_flow'>"
				.show_select(array('data' => get_yes_no_for_select(), 'name' => $this->get_field_name('search_hide_on_mobile'), 'text' => __("Hide on Mobile", $this->obj_theme_core->lang_key), 'value' => $instance['search_hide_on_mobile']))
				.show_select(array('data' => get_yes_no_for_select(), 'name' => $this->get_field_name('search_animate'), 'text' => __("Animate", $this->obj_theme_core->lang_key), 'value' => $instance['search_animate']))
				.show_select(array('data' => get_yes_no_for_select(), 'name' => $this->get_field_name('search_listen_to_keystroke'), 'text' => __("Listen to Keystroke", $this->obj_theme_core->lang_key), 'value' => $instance['search_listen_to_keystroke']))
			."</div>"
		."</div>";
	}
}

class widget_theme_core_news extends WP_Widget
{
	function __construct()
	{
		$this->obj_theme_core = new mf_theme_core();

		$this->widget_ops = array(
			'classname' => 'theme_news',
			'description' => __("Display News/Posts", $this->obj_theme_core->lang_key)
		);

		$this->arr_default = array(
			'news_title' => "",
			'news_type' => 'original',
			'news_categories' => array(),
			'news_amount' => 1,
			'news_columns' => 0,
			'news_time_limit' => 0,
			'news_expand_content' => 'no',
			'news_display_arrows' => 'no',
			'news_autoscroll_time' => 5,
			'news_display_title' => 'yes',
			'news_display_excerpt' => 'yes',
			'news_page' => 0,
		);

		parent::__construct(str_replace("_", "-", $this->widget_ops['classname']).'-widget', __("News", $this->obj_theme_core->lang_key), $this->widget_ops);
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

		$result = $wpdb->get_results("SELECT ID, post_title, post_excerpt, post_date FROM ".$wpdb->posts.$query_join." WHERE post_type = 'post' AND post_status = 'publish'".$query_where." ORDER BY post_date DESC LIMIT 0, ".$instance['news_amount']);

		if($wpdb->num_rows > 0)
		{
			$post_thumbnail_size = 'large'; //$wpdb->num_rows > 2 ? 'medium' :

			foreach($result as $r)
			{
				$post_id = $r->ID;

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
					'title' => $r->post_title,
					'date' => $r->post_date,
					'url' => get_permalink($post_id),
					'image' => $post_thumbnail,
					'excerpt' => $r->post_excerpt,
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
					$instance['news_title'] = apply_filters('widget_title', $instance['news_title'], $instance, $this->id_base);

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

				if($instance['news_display_title'] == 'yes')
				{
					$widget_class .= " display_page_titles";
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

										switch($instance['news_type'])
										{
											case 'original':
											case 'simple':
												echo "<div class='image'>".$page['image']."</div>";
											break;

											case 'compact':
												echo "<span>".format_date($page['date'])."</span>";
											break;
										}

										if($instance['news_display_title'] == 'yes')
										{
											echo "<h4>".$page['title']."</h4>";
										}

										switch($instance['news_type'])
										{
											case 'postit':
											case 'simple':
												if($instance['news_display_excerpt'] == 'yes')
												{
													echo apply_filters('the_content', $page['excerpt']);
												}
											break;
										}

									echo "</a>
								</li>";
							}

						echo "</ul>";

						if($instance['news_page'] > 0)
						{
							echo "<p class='read_more'><a href='".get_permalink($instance['news_page'])."'>".__("Read More", $this->obj_theme_core->lang_key)."</a></p>";
						}
					}

					else
					{
						foreach($this->arr_news as $page_id => $page)
						{
							if($instance['news_expand_content'] == 'yes')
							{
								$post_content = mf_get_post_content($page_id);

								echo "<div class='news_expand_content'>";

									if($page['image'] != '')
									{
										echo "<div class='image'>".$page['image']."</div>";
									}

									echo ($instance['news_title'] == '' ? $before_title : "<h4>")
										.$page['title']
									.($instance['news_title'] == '' ? $after_title : "</h4>")
									."<div class='excerpt'>".apply_filters('the_content', $page['excerpt'])."</div>"
									."<p class='read_more'><a href='#'>".__("Read More", $this->obj_theme_core->lang_key)."</a></p>"
									."<div class='content hide'>".apply_filters('the_content', $post_content)."</div>
								</div>";
							}

							else
							{
								echo "<a href='".$page['url']."'>";

									if($page['image'] != '')
									{
										echo "<div class='image'>".$page['image']."</div>";
									}

									echo ($instance['news_title'] == '' ? $before_title : "<h4>")
										.$page['title']
									.($instance['news_title'] == '' ? $after_title : "</h4>")
									.apply_filters('the_content', $page['excerpt'])
									."<p class='read_more'>".__("Read More", $this->obj_theme_core->lang_key)."</p>"
								."</a>";
							}
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
		$instance['news_expand_content'] = sanitize_text_field($new_instance['news_expand_content']);
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
			'original' => __("Default", $this->obj_theme_core->lang_key),
			'postit' => __("Post It", $this->obj_theme_core->lang_key),
			'simple' => __("Simple", $this->obj_theme_core->lang_key),
			'compact' => __("Compact", $this->obj_theme_core->lang_key),
		);
	}

	function form($instance)
	{
		$instance = wp_parse_args((array)$instance, $this->arr_default);

		$instance_temp = $instance;
		$instance_temp['news_amount'] = 9;
		$instance_temp['news_time_limit'] = 0;
		$this->get_posts($instance_temp);

		$rows = count($this->arr_news);

		$arr_data_pages = array();
		get_post_children(array('add_choose_here' => true), $arr_data_pages);

		echo "<div class='mf_form'>"
			.show_textfield(array('name' => $this->get_field_name('news_title'), 'text' => __("Title", $this->obj_theme_core->lang_key), 'value' => $instance['news_title'], 'xtra' => " id='".$this->widget_ops['classname']."-title'"))
			.show_select(array('data' => $this->get_news_type_for_select(), 'name' => $this->get_field_name('news_type'), 'text' => __("Design", $this->obj_theme_core->lang_key), 'value' => $instance['news_type']))
			.show_select(array('data' => get_categories_for_select(array('hide_empty' => false)), 'name' => $this->get_field_name('news_categories')."[]", 'text' => __("Categories", $this->obj_theme_core->lang_key), 'value' => $instance['news_categories']))
			."<div class='flex_flow'>"
				.show_textfield(array('type' => 'number', 'name' => $this->get_field_name('news_amount'), 'text' => __("Amount", $this->obj_theme_core->lang_key), 'value' => $instance['news_amount'], 'xtra' => " min='0' max='".($rows > 0 ? $rows : 1)."'"));

				if($instance['news_amount'] > 1 && $rows > 3 && $instance['news_type'] != 'compact')
				{
					echo show_textfield(array('type' => 'number', 'name' => $this->get_field_name('news_columns'), 'text' => __("Columns", $this->obj_theme_core->lang_key), 'value' => $instance['news_columns'], 'xtra' => " min='0' max='4'"));
				}

			echo "</div>";

			if($instance['news_amount'] == 1)
			{
				echo show_textfield(array('type' => 'number', 'name' => $this->get_field_name('news_time_limit'), 'text' => __("Time Limit", $this->obj_theme_core->lang_key), 'value' => $instance['news_time_limit'], 'xtra' => " min='0' max='240'", 'suffix' => __("h", $this->obj_theme_core->lang_key)));
			}

			if($instance['news_type'] == 'postit')
			{
				echo "<div class='flex_flow'>"
					.show_select(array('data' => get_yes_no_for_select(), 'name' => $this->get_field_name('news_display_arrows'), 'text' => __("Display Arrows", $this->obj_theme_core->lang_key), 'value' => $instance['news_display_arrows']));

					if($instance['news_display_arrows'] == 'yes')
					{
						echo show_textfield(array('type' => 'number', 'name' => $this->get_field_name('news_autoscroll_time'), 'text' => __("Autoscroll", $this->obj_theme_core->lang_key), 'value' => $instance['news_autoscroll_time'], 'xtra' => " min='0' max='60'"));
					}

				echo "</div>";
			}

			if($instance['news_type'] != 'compact')
			{
				echo "<div class='flex_flow'>"
					.show_select(array('data' => get_yes_no_for_select(), 'name' => $this->get_field_name('news_display_title'), 'text' => __("Display Title", $this->obj_theme_core->lang_key), 'value' => $instance['news_display_title']))
					.show_select(array('data' => get_yes_no_for_select(), 'name' => $this->get_field_name('news_display_excerpt'), 'text' => __("Display Excerpt", $this->obj_theme_core->lang_key), 'value' => $instance['news_display_excerpt']))
				."</div>";
			}

			if($instance['news_amount'] == 1)
			{
				echo show_select(array('data' => get_yes_no_for_select(), 'name' => $this->get_field_name('news_expand_content'), 'text' => __("Expand Content on Current Page", $this->obj_theme_core->lang_key), 'value' => $instance['news_expand_content']));
			}

			if($rows > 1 && $instance['news_amount'] > 1)
			{
				echo show_select(array('data' => $arr_data_pages, 'name' => $this->get_field_name('news_page'), 'text' => __("Read More", $this->obj_theme_core->lang_key), 'value' => $instance['news_page']));
			}

		echo "</div>";
	}
}

class widget_theme_core_info extends WP_Widget
{
	function __construct()
	{
		$this->obj_theme_core = new mf_theme_core();

		$this->widget_ops = array(
			'classname' => 'theme_info',
			'description' => __("Display Info Module", $this->obj_theme_core->lang_key)
		);

		$this->arr_default = array(
			'info_image' => '',
			'info_title' => '',
			'info_content' => '',
			'info_button_text' => '',
			'info_page' => 0,
			'info_link' => '',
			'info_time_limit' => 0,
			'info_visit_limit' => 0,
		);

		parent::__construct(str_replace("_", "-", $this->widget_ops['classname']).'-widget', __("Info Module", $this->obj_theme_core->lang_key), $this->widget_ops);
	}

	function check_limit($instance)
	{
		$widget_md5 = md5(var_export($instance, true));

		if($instance['info_time_limit'] > 0)
		{
			if(is_user_logged_in())
			{
				$arr_meta_time_visit_limit = get_user_meta(get_current_user_id(), 'meta_time_visit_limit', false);

				if(is_array($arr_meta_time_visit_limit))
				{
					if(isset($arr_meta_time_visit_limit[0]) && is_array($arr_meta_time_visit_limit[0]))
					{
						//do_log("check_limit(): Array exists but is not formatted correctly (".var_export($arr_meta_time_visit_limit, true).")");

						$arr_meta_time_visit_limit = $arr_meta_time_visit_limit[0];
					}

					else
					{
						$arr_meta_time_visit_limit = array();
					}
				}

				else
				{
					$arr_meta_time_visit_limit = array();
				}

				if(!isset($arr_meta_time_visit_limit[$widget_md5]) || $arr_meta_time_visit_limit[$widget_md5] < DEFAULT_DATE)
				{
					$arr_meta_time_visit_limit[$widget_md5] = date("Y-m-d");

					update_user_meta(get_current_user_id(), 'meta_time_visit_limit', $arr_meta_time_visit_limit);
				}

				else if($arr_meta_time_visit_limit[$widget_md5] < date("Y-m-d", strtotime("-".$instance['info_time_limit']." day")))
				{
					return false;
				}
			}

			else
			{
				if(!session_id())
				{
					@session_start();
				}

				$arr_ses_info_time_limit = check_var('ses_info_time_limit', 'array', true, '0');

				if(!isset($arr_ses_info_time_limit[$widget_md5]) || $arr_ses_info_time_limit[$widget_md5] < DEFAULT_DATE)
				{
					$arr_ses_info_time_limit[$widget_md5] = date("Y-m-d");

					$_SESSION['ses_info_time_limit'] = $arr_ses_info_time_limit;
				}

				else if($arr_ses_info_time_limit[$widget_md5] < date("Y-m-d", strtotime("-".$instance['info_time_limit']." day")))
				{
					return false;
				}
			}
		}

		if($instance['info_visit_limit'] > 0)
		{
			if(is_user_logged_in())
			{
				$arr_meta_info_visit_limit = get_user_meta(get_current_user_id(), 'meta_info_visit_limit', false);
				$arr_meta_info_visit_limit = is_array($arr_meta_info_visit_limit) ? $arr_meta_info_visit_limit[0] : array();

				if(!isset($arr_meta_info_visit_limit[$widget_md5]))
				{
					$arr_meta_info_visit_limit[$widget_md5] = 1;
				}

				else
				{
					$arr_meta_info_visit_limit[$widget_md5] += 1;
				}

				if($arr_meta_info_visit_limit[$widget_md5] > $instance['info_visit_limit'])
				{
					return false;
				}

				else
				{
					update_user_meta(get_current_user_id(), 'meta_info_visit_limit', $arr_meta_info_visit_limit);
				}
			}

			else
			{
				if(!session_id())
				{
					@session_start();
				}

				$arr_ses_info_visit_limit = check_var('ses_info_visit_limit', 'array', true, '0');

				if(!isset($arr_ses_info_visit_limit[$widget_md5]))
				{
					$arr_ses_info_visit_limit[$widget_md5] = 1;
				}

				else
				{
					$arr_ses_info_visit_limit[$widget_md5]++;
				}

				if($arr_ses_info_visit_limit[$widget_md5] > $instance['info_visit_limit'])
				{
					return false;
				}

				else
				{
					$_SESSION['ses_info_visit_limit'] = $arr_ses_info_visit_limit;
				}
			}
		}

		return true;
	}

	function widget($args, $instance)
	{
		extract($args);
		$instance = wp_parse_args((array)$instance, $this->arr_default);

		if($instance['info_page'] > 0){			$button_link = get_permalink($instance['info_page']);}
		else if($instance['info_link'] != ''){	$button_link = $instance['info_link'];}
		else{									$button_link = apply_filters('get_theme_core_info_button_link', "#");}

		if($this->check_limit($instance))
		{
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
								$instance['info_title'] = apply_filters('widget_title', $instance['info_title'], $instance, $this->id_base);

								echo $before_title
									.apply_filters('get_theme_core_info_title', $instance['info_title'])
								.$after_title;
							}

							if($instance['info_content'] != '')
							{
								echo apply_filters('the_content', apply_filters('get_theme_core_info_text', $instance['info_content']));
							}

							if($instance['info_button_text'] != '')
							{
								echo "<div class='form_button'>"
									.apply_filters('the_content', "<a href='".$button_link."' class='button'>"
										.$instance['info_button_text']
									."</a>")
								."</div>";
							}

						echo "</div>
					</div>
				</div>"
			.$after_widget;
		}
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
		$instance['info_visit_limit'] = sanitize_text_field($new_instance['info_visit_limit']);
		$instance['info_time_limit'] = sanitize_text_field($new_instance['info_time_limit']);

		return $instance;
	}

	function form($instance)
	{
		$instance = wp_parse_args((array)$instance, $this->arr_default);

		echo "<div class='mf_form'>"
			.get_media_library(array('type' => 'image', 'name' => $this->get_field_name('info_image'), 'value' => $instance['info_image']))
			.show_textfield(array('name' => $this->get_field_name('info_title'), 'text' => __("Title", $this->obj_theme_core->lang_key), 'value' => $instance['info_title'], 'xtra' => " id='".$this->widget_ops['classname']."-title'"))
			.show_textarea(array('name' => $this->get_field_name('info_content'), 'text' => __("Content", $this->obj_theme_core->lang_key), 'value' => $instance['info_content']))
			.show_textfield(array('name' => $this->get_field_name('info_button_text'), 'text' => __("Button Text", $this->obj_theme_core->lang_key), 'value' => $instance['info_button_text']));

			if($instance['info_button_text'] != '')
			{
				if($instance['info_link'] == '')
				{
					$arr_data = array();
					get_post_children(array('add_choose_here' => true), $arr_data);

					echo show_select(array('data' => $arr_data, 'name' => $this->get_field_name('info_page'), 'text' => __("Page", $this->obj_theme_core->lang_key), 'value' => $instance['info_page']));
				}

				if(!($instance['info_page'] > 0))
				{
					echo show_textfield(array('type' => 'url', 'name' => $this->get_field_name('info_link'), 'text' => __("Link", $this->obj_theme_core->lang_key), 'value' => $instance['info_link']));
				}
			}

			if(!($instance['info_visit_limit'] > 0))
			{
				echo show_textfield(array('type' => 'number', 'name' => $this->get_field_name('info_time_limit'), 'text' => __("Time Limit", $this->obj_theme_core->lang_key), 'value' => $instance['info_time_limit'], 'suffix' => __("days", $this->obj_theme_core->lang_key)));
			}

			if(!($instance['info_time_limit'] > 0))
			{
				echo show_textfield(array('type' => 'number', 'name' => $this->get_field_name('info_visit_limit'), 'text' => __("Visit Limit", $this->obj_theme_core->lang_key), 'value' => $instance['info_visit_limit'], 'suffix' => __("times", $this->obj_theme_core->lang_key)));
			}

		echo "</div>";
	}
}

class widget_theme_core_related extends WP_Widget
{
	function __construct()
	{
		$this->obj_theme_core = new mf_theme_core();

		$this->widget_ops = array(
			'classname' => 'theme_news',
			'description' => __("Display Related Posts", $this->obj_theme_core->lang_key)
		);

		$this->arr_default = array(
			'news_title' => '',
			'news_post_type' => 'post',
			'news_categories' => array(),
			'news_amount' => 1,
			'news_columns' => 1,
		);

		parent::__construct('theme-related-news-widget', __("Related Posts", $this->obj_theme_core->lang_key), $this->widget_ops);
	}

	function get_posts($instance)
	{
		global $wpdb, $post;

		$this->arr_news = array();

		if(isset($post) && isset($post->ID))
		{
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

			if($wpdb->num_rows > 0)
			{
				$post_thumbnail_size = 'large'; //$wpdb->num_rows > 2 ? 'medium' :

				foreach($result as $r)
				{
					$post_id = $r->ID;

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
						'title' => $r->post_title,
						'url' => get_permalink($post_id),
						'image' => $post_thumbnail,
						'excerpt' => $r->post_excerpt,
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
					$instance['news_title'] = apply_filters('widget_title', $instance['news_title'], $instance, $this->id_base);

					echo $before_title
						.$instance['news_title']
					.$after_title;
				}

				echo "<div class='section original display_page_titles'>
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

		echo "<div class='mf_form'>"
			.show_textfield(array('name' => $this->get_field_name('news_title'), 'text' => __("Title", $this->obj_theme_core->lang_key), 'value' => $instance['news_title'], 'xtra' => " id='".$this->widget_ops['classname']."-title'"))
			.show_select(array('data' => get_post_types_for_select(array('include' => array('types'), 'add_is' => false)), 'name' => $this->get_field_name('news_post_type'), 'value' => $instance['news_post_type']))
			.show_select(array('data' => get_categories_for_select(), 'name' => $this->get_field_name('news_categories')."[]", 'text' => __("Categories", $this->obj_theme_core->lang_key), 'value' => $instance['news_categories']))
			."<div class='flex_flow'>"
				.show_textfield(array('type' => 'number', 'name' => $this->get_field_name('news_amount'), 'text' => __("Amount", $this->obj_theme_core->lang_key), 'value' => $instance['news_amount'], 'xtra' => " min='1'"));

				if($instance['news_amount'] > 1)
				{
					echo show_textfield(array('type' => 'number', 'name' => $this->get_field_name('news_columns'), 'text' => __("Columns", $this->obj_theme_core->lang_key), 'value' => $instance['news_columns'], 'xtra' => " min='1' max='4'"));
				}

			echo "</div>
		</div>";
	}
}

class widget_theme_core_promo extends WP_Widget
{
	function __construct()
	{
		$this->obj_theme_core = new mf_theme_core();

		$this->widget_ops = array(
			'classname' => 'theme_promo theme_news',
			'description' => __("Promote Pages", $this->obj_theme_core->lang_key)
		);

		$this->arr_default = array(
			'promo_title' => "",
			'promo_include' => array(),
			'promo_page_titles' => 'yes',
		);

		parent::__construct('theme-promo-widget', __("Promotion", $this->obj_theme_core->lang_key), $this->widget_ops);
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
						$instance['promo_title'] = apply_filters('widget_title', $instance['promo_title'], $instance, $this->id_base);

						echo $before_title
							.$instance['promo_title']
						.$after_title;
					}

					echo "<div class='section original".($instance['promo_page_titles'] == 'yes' ? " display_page_titles" : "")."'>
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
			.show_textfield(array('name' => $this->get_field_name('promo_title'), 'text' => __("Title", $this->obj_theme_core->lang_key), 'value' => $instance['promo_title'], 'xtra' => " id='".$this->widget_ops['classname']."-title'"))
			.show_select(array('data' => $arr_data, 'name' => $this->get_field_name('promo_include')."[]", 'text' => __("Pages", $this->obj_theme_core->lang_key), 'value' => $instance['promo_include']))
			.show_select(array('data' => get_yes_no_for_select(), 'name' => $this->get_field_name('promo_page_titles'), 'text' => __("Display Titles", $this->obj_theme_core->lang_key), 'value' => $instance['promo_page_titles']))
		."</div>";
	}
}

class widget_theme_core_page_index extends WP_Widget
{
	function __construct()
	{
		$this->obj_theme_core = new mf_theme_core();

		$this->widget_ops = array(
			'classname' => 'theme_page_index',
			'description' => __("Display Table of Contents", $this->obj_theme_core->lang_key)
		);

		$this->arr_default = array(
			'widget_title' => "",
		);

		parent::__construct(str_replace("_", "-", $this->widget_ops['classname']).'-widget', __("Table of Contents", $this->obj_theme_core->lang_key), $this->widget_ops);
	}

	function widget($args, $instance)
	{
		global $post;

		extract($args);
		$instance = wp_parse_args((array)$instance, $this->arr_default);

		if(isset($post->ID) && $post->ID > 0)
		{
			$post_content = $post->post_content;

			$arr_tags = get_match_all('/\<h(.*?)>(.*?)\<\/h/is', $post_content, false);

			if(count($arr_tags) > 1)
			{
				echo $before_widget;

					if($instance['widget_title'] != '')
					{
						$instance['widget_title'] = apply_filters('widget_title', $instance['widget_title'], $instance, $this->id_base);

						echo $before_title
							.$instance['widget_title']
						.$after_title;
					}

					echo "<div>
						<i class='fa fa-bars'></i>
						<ul></ul>
					</div>"
				.$after_widget;
			}
		}
	}

	function update($new_instance, $old_instance)
	{
		$instance = $old_instance;
		$new_instance = wp_parse_args((array)$new_instance, $this->arr_default);

		$instance['widget_title'] = sanitize_text_field($new_instance['widget_title']);

		return $instance;
	}

	function form($instance)
	{
		$instance = wp_parse_args((array)$instance, $this->arr_default);

		echo "<div class='mf_form'>"
			.show_textfield(array('name' => $this->get_field_name('widget_title'), 'text' => __("Title", $this->obj_theme_core->lang_key), 'value' => $instance['widget_title'], 'xtra' => " id='".$this->widget_ops['classname']."-title'"))
		."</div>";
	}
}