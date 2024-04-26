<?php

class mf_theme_core
{
	var $post_type = 'mf_theme_core';
	var $meta_prefix;
	var $options_params = array();
	var $options = array();
	var $options_fonts = array();
	var $title_format = "[page_title][site_title][site_description][page_number]";
	var $arr_sensitive_data_types = array();
	var $arr_public_posts = array();
	var $arr_post_types = array();
	var $is_theme_active = '';
	var $custom_widget_area = array();
	var $site_url = "";
	var $footer_output = '';
	var $id_temp = '';
	var $param = array();
	var $post_id_old;
	var $post_id_new;
	var $file_dir_from;
	var $file_dir_to;

	function __construct()
	{
		$this->meta_prefix = $this->post_type.'_';
	}

	function is_site_public()
	{
		return (get_option('blog_public') == 1 && get_option('setting_no_public_pages') != 'yes' && get_option('setting_theme_core_login') != 'yes');
	}

	function is_post_password_protected($post_id = 0)
	{
		$out = false;

		if(!is_user_logged_in())
		{
			if($out == false)
			{
				if($post_id > 0)
				{
					$out = post_password_required($post_id);
				}

				else
				{
					$out = post_password_required();
				}
			}

			if($out == false)
			{
				if($post_id == 0)
				{
					global $post;

					if(isset($post->ID))
					{
						$post_id = $post->ID;
					}
				}

				$out = apply_filters('filter_is_password_protected', $out, array('post_id' => $post_id, 'check_login' => true));
			}
		}

		return $out;
	}

	function get_theme_dir_name($data = array())
	{
		if(!isset($data['type'])){	$data['type'] = 'parent';}

		switch($data['type'])
		{
			case 'child':
				$theme_path = get_stylesheet_directory();
			break;

			default:
			case 'parent':
				$theme_path = get_template_directory();
			break;
		}

		return str_replace(get_theme_root()."/", "", $theme_path);
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

		foreach($options_params as $param_key => $arr_param)
		{
			if(isset($arr_param['category']))
			{
				$arr_data['opt_start_'.$arr_param['id']] = $arr_param['category'];

				$last_category = $arr_param['id'];
				$has_children = false;
			}

			else if(isset($arr_param['category_end']))
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
				$id = $arr_param['id'];
				$title = $arr_param['title'];

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
			'' => "-- ".__("Choose Here", 'lang_theme_core')." --",
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
		); //'formatdiv', 'tagsdiv',
	}

	function set_noindex_on_page($option)
	{
		if(is_array($option))
		{
			if(count($option) > 0)
			{
				foreach($option as $option_value)
				{
					update_post_meta($option_value, $this->meta_prefix.'page_index', 'noindex');
				}
			}
		}

		else if($option > 0)
		{
			update_post_meta($option, $this->meta_prefix.'page_index', 'noindex');
		}
	}

	// Can be replaced delete_empty_folder_callback in MF Base
	function delete_empty_folder_callback($data)
	{
		$folder = $data['path']."/".$data['child'];

		if(file_exists($folder) && is_dir($folder))
		{
			$folder_scan = scandir($folder);

			if(is_array($folder_scan) && count($folder_scan) == 2)
			{
				rmdir($folder);
			}
		}
	}

	function cron_base()
	{
		global $wpdb;

		$obj_cron = new mf_cron();
		$obj_cron->start(__CLASS__);

		if($obj_cron->is_running == false)
		{
			$this->publish_posts();

			// Optimize
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

					get_file_info(array('path' => $upload_path, 'callback' => 'delete_files', 'time_limit' => (DAY_IN_SECONDS * 60)));
					get_file_info(array('path' => $upload_path, 'folder_callback' => array($this, 'delete_empty_folder_callback')));
				}
				#######################
			}

			// Change comment status on posts
			#########################
			$option = 'closed';

			if(get_option('default_comment_status') == $option)
			{
				$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->posts." SET comment_status = %s, ping_status = %s WHERE (comment_status != %s OR ping_status != %s)", $option, $option, $option, $option));
			}
			#########################

			// Delete old uploads
			#######################
			list($upload_path, $upload_url) = get_uploads_folder($this->post_type);

			get_file_info(array('path' => $upload_path, 'callback' => 'delete_files_callback', 'time_limit' => MONTH_IN_SECONDS));
			get_file_info(array('path' => $upload_path, 'folder_callback' => 'delete_empty_folder_callback'));
			#######################
		}

		$obj_cron->end();
	}

	/*function init()
	{
		if(!is_admin())
		{
			if(isset($_REQUEST['action']) && ('posts_logout' == $_REQUEST['action']))
			{
				check_admin_referer('posts_logout');
				setcookie('wp-postpass_'.COOKIEHASH, '', strtotime("-1 month"), COOKIEPATH);

				wp_redirect(wp_get_referer());
				die();
			}
		}
	}*/

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
		$arr_out = array(
			'url' => "",
			'color' => "",
			'icon' => "",
			'text' => "",
		);

		switch($this->get_site_status())
		{
			case 'maintenance_mode':
				$arr_out['url'] = admin_url("options-general.php?page=settings_mf_base#settings_theme_core_public");
				$arr_out['color'] = "color_red";
				$arr_out['icon'] = "fas fa-hard-hat";
				$arr_out['text'] = __("Maintenance Mode Activated", 'lang_theme_core');
			break;

			case 'not_public':
				if($data['type'] == 'admin_bar')
				{
					global $wp_admin_bar;

					$wp_admin_bar->remove_menu('site-name');
				}

				$arr_out['color'] = "color_red";
				$arr_out['icon'] = "fas fa-eye-slash";
				$arr_out['text'] = __("No Public Pages", 'lang_theme_core');
			break;

			case 'requires_login':
				$arr_out['url'] = get_home_url();
				$arr_out['color'] = "color_red";
				$arr_out['icon'] = "fas fa-user-lock";
				$arr_out['text'] = __("Requires Login", 'lang_theme_core');
			break;

			case 'no_index':
				$arr_out['url'] = get_home_url();
				$arr_out['color'] = "color_yellow";
				$arr_out['icon'] = "fas fa-robot";
				$arr_out['text'] = __("No Index", 'lang_theme_core');
			break;

			default:
			case 'public':
				$arr_out['url'] = get_home_url();
				$arr_out['color'] = "color_green";
				$arr_out['icon'] = "fas fa-eye";
				$arr_out['text'] = __("Public", 'lang_theme_core');
			break;
		}

		return $arr_out;
	}

	function wp_before_admin_bar_render()
	{
		global $wp_admin_bar;

		if(IS_ADMINISTRATOR)
		{
			$arr_site_status = $this->get_site_status_data(array('type' => 'admin_bar'));

			$flag_image = $this->get_flag_image();

			$title = "";

			if($arr_site_status['url'] != '')
			{
				$title .= "<a href='".$arr_site_status['url']."' class='".$arr_site_status['color']."'>";
			}

			else
			{
				$title .= "<span class='".$arr_site_status['color']."'>";
			}

				if($flag_image != '')
				{
					$title .= "<div class='flex_flow tight'>
						<img src='".$flag_image."'>&nbsp;
						<span>";
				}

					// "#wpadminbar *" overrides style for FA icons
					/*if($arr_site_status['icon'] != '')
					{
						$title .= "<i class='".$arr_site_status['icon']."' title='".$arr_site_status['text']."'></i>";
					}

					else
					{*/
						$title .= $arr_site_status['text'];
					//}

				if($flag_image != '')
				{
						$title .= "</span>
					</div>";
				}

			if($arr_site_status['url'] != '')
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

		// Generic
		############################
		add_settings_section($options_area, "", array($this, $options_area."_callback"), BASE_OPTIONS_PAGE);

		$arr_settings = array();

		if(get_option('blog_public') == 0 || get_option('setting_no_public_pages') == 'yes' || get_option('setting_theme_core_login') == 'yes')
		{
			$arr_settings['setting_no_public_pages'] = __("Always redirect visitors to the login page", 'lang_theme_core');

			if(get_option('setting_no_public_pages') != 'yes')
			{
				$arr_settings['setting_theme_core_login'] = __("Require login for public site", 'lang_theme_core');
			}
		}

		if($this->is_theme_active())
		{
			$arr_settings['setting_theme_core_templates'] = __("Templates", 'lang_theme_core');
		}

		$arr_settings['setting_theme_core_hidden_meta_boxes'] = __("Hidden Meta Boxes", 'lang_theme_core');

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

			$setting_base_template_site = get_option('setting_base_template_site');

			if($setting_base_template_site != '')
			{
				$arr_settings['setting_theme_ignore_style_on_restore'] = __("Ignore Style on Restore", 'lang_theme_core');
			}

			else
			{
				delete_option('setting_theme_ignore_style_on_restore');
			}
		}

		if(IS_SUPER_ADMIN)
		{
			$arr_settings['setting_theme_enable_wp_api'] = __("Enable XML-RPC", 'lang_theme_core');
			$arr_settings['setting_theme_optimize'] = __("Optimize", 'lang_theme_core');
		}

		show_settings_fields(array('area' => $options_area, 'object' => $this, 'settings' => $arr_settings));
		############################

		// Public Site
		############################
		if(get_option('setting_no_public_pages') != 'yes')
		{
			$options_area = $options_area_orig."_public";

			add_settings_section($options_area, "", array($this, $options_area."_callback"), BASE_OPTIONS_PAGE);

			$arr_settings = array();

			if($this->is_theme_active())
			{
				$arr_settings['setting_theme_core_enable_edit_mode'] = __("Enable Edit Mode", 'lang_theme_core');
			}

			$arr_settings['setting_theme_core_display_author_pages'] = __("Display Author Pages", 'lang_theme_core');
			$arr_settings['setting_theme_core_title_format'] = __("Title Format", 'lang_theme_core');
			$arr_settings['setting_display_post_meta'] = __("Display Post Meta", 'lang_theme_core');
			$arr_settings['setting_scroll_to_top'] = __("Display scroll-to-top-link", 'lang_theme_core');

			if(get_option('setting_scroll_to_top') == 'yes')
			{
				$arr_settings['setting_scroll_to_top_text'] = __("Scroll-to-top Text", 'lang_theme_core');
			}

			if(get_option('setting_no_public_pages') != 'yes')
			{
				$arr_settings['setting_theme_core_search_redirect_single_result'] = __("Redirect Single Result in Search", 'lang_theme_core');
				$arr_settings['setting_404_page'] = __("404 Page", 'lang_theme_core');
			}

			$arr_settings['setting_maintenance_page'] = __("Maintenance Page", 'lang_theme_core');

			if(IS_SUPER_ADMIN && get_option('setting_maintenance_page') > 0)
			{
				$arr_settings['setting_activate_maintenance'] = __("Activate Maintenance Mode", 'lang_theme_core');
			}

			show_settings_fields(array('area' => $options_area, 'object' => $this, 'settings' => $arr_settings));
		}
		############################

		// Cookies
		############################
		if(get_option('setting_no_public_pages') != 'yes')
		{
			$options_area = $options_area_orig."_cookies";

			add_settings_section($options_area, "", array($this, $options_area."_callback"), BASE_OPTIONS_PAGE);

			$arr_settings = array();
			$arr_settings['setting_cookie_exists'] = __("Sensitive Data on This Site", 'lang_theme_core');
			$arr_settings['setting_cookie_info'] = __("Information Page", 'lang_theme_core');

			if(get_option('setting_cookie_info') > 0)
			{
				$arr_settings['setting_cookie_deactivate_until_allowed'] = __("Deactivate Until Allowed", 'lang_theme_core');
			}

			show_settings_fields(array('area' => $options_area, 'object' => $this, 'settings' => $arr_settings));
		}
		############################
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

		function setting_theme_core_templates_callback()
		{
			$setting_key = get_setting_key(__FUNCTION__);
			$option = get_option($setting_key);

			$arr_data = array();
			get_post_children(array('add_choose_here' => true), $arr_data);

			echo show_select(array('data' => $arr_data, 'name' => $setting_key."[]", 'value' => $option));

			$this->set_noindex_on_page($option);
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

			echo show_select(array('data' => get_yes_no_for_select(), 'name' => $setting_key, 'value' => $option, 'suffix' => sprintf(__("This will send an e-mail to all editors (%s) when an author saves a draft", 'lang_theme_core'), $editors)));
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

				$description = sprintf(__("The optimization was last run %s and will be run again %s", 'lang_theme_core'), format_date($option_database_optimized), $populate_next);
			}

			else
			{
				$description = sprintf(__("The optimization has not been run yet but will be %s", 'lang_theme_core'), get_next_cron());
			}

			echo "<div class='form_button'>"
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

			$arr_params_for_select = $this->get_params_for_select();

			if(count($arr_params_for_select) > 0)
			{
				echo show_select(array('data' => $arr_params_for_select, 'name' => $setting_key."[]", 'value' => $option));
			}

			else
			{
				echo "<em>".__("There are no styles to restore", 'lang_theme_core')."</em>";
			}
		}

	function settings_theme_core_public_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);

		echo settings_header($setting_key, __("Theme", 'lang_theme_core')." - ".__("Public", 'lang_theme_core'));
	}

		function setting_theme_core_enable_edit_mode_callback()
		{
			$setting_key = get_setting_key(__FUNCTION__);
			$option = get_option_or_default($setting_key, 'yes');

			echo show_select(array('data' => get_yes_no_for_select(), 'name' => $setting_key, 'value' => $option));
		}

		function setting_theme_core_display_author_pages_callback()
		{
			$setting_key = get_setting_key(__FUNCTION__);
			$option = get_option_or_default($setting_key, 'no');

			echo show_select(array('data' => get_yes_no_for_select(), 'name' => $setting_key, 'value' => $option));
		}

		function setting_theme_core_title_format_callback()
		{
			$setting_key = get_setting_key(__FUNCTION__);
			$option = get_option($setting_key);

			echo show_textfield(array('name' => $setting_key, 'value' => $option, 'placeholder' => $this->title_format));
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

			$wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." WHERE comment_status = %s LIMIT 0, 1", $status));

			return $wpdb->num_rows;
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
			$post_content = __("Oops! The page that you were looking for does not seam to exist. If you think that it should exist, please let us know.", 'lang_theme_core');

			echo show_select(array('data' => $arr_data, 'name' => $setting_key, 'value' => $option, 'suffix' => get_option_page_suffix(array('value' => $option, 'title' => $post_title, 'content' => $post_content)), 'description' => (!($option > 0) ? "<span class='display_warning'><i class='fa fa-exclamation-triangle yellow'></i></span> " : "").__("This page will be displayed instead of the default 404 page", 'lang_theme_core')));

			$this->set_noindex_on_page($option);
		}

		function setting_maintenance_page_callback()
		{
			global $done_text, $error_text;

			$setting_key = get_setting_key(__FUNCTION__);
			$option = get_option($setting_key);
			$option_temp = get_option($setting_key.'_temp');

			$arr_data = array();
			get_post_children(array('add_choose_here' => true), $arr_data);

			$post_title_orig = $post_title = __("Temporary Maintenance", 'lang_theme_core');
			$post_content_orig = $post_content = __("This site is undergoing maintenance. This usually takes less than a minute so you have been unfortunate to come to the site at this moment. If you reload the page in just a while it will surely be back as usual.", 'lang_theme_core');

			echo show_select(array('data' => $arr_data, 'name' => $setting_key, 'value' => $option, 'suffix' => get_option_page_suffix(array('value' => $option, 'title' => $post_title, 'content' => $post_content)), 'description' => (!($option > 0) ? "<span class='display_warning'><i class='fa fa-exclamation-triangle yellow'></i></span> " : "").__("This page will be displayed when the website is updating", 'lang_theme_core')));

			if($option > 0 && $option != $option_temp)
			{
				// Save HTML for this page
				###########################################
				if(get_option('setting_activate_maintenance') == 'no' && get_option('setting_no_public_pages') != 'yes' && get_option('setting_theme_core_login') != 'yes')
				{
					delete_option('setting_maintenance_page_html');

					$setting_maintenance_page = get_option('setting_maintenance_page');

					if($setting_maintenance_page > 0)
					{
						list($content, $headers) = get_url_content(array(
							'url' => get_permalink($setting_maintenance_page),
							'catch_head' => true,
						));

						switch($headers['http_code'])
						{
							case 200:
							case 201:
								update_option('setting_maintenance_page_html', $content, 'no');

								$done_text = __("I saved the maintenance page as HTML", 'lang_theme_core');

								echo get_notification();
							break;
						}
					}
				}
				###########################################

				// Save maintenance file
				###########################################
				$maintenance_file = ABSPATH."wp-content/maintenance.php";

				if(touch($maintenance_file))
				{
					list($upload_path, $upload_url) = get_uploads_folder('mf_cache', true);
					$maintenance_template = str_replace($this->post_type."/include", $this->post_type."/templates/", dirname(__FILE__))."maintenance.php";

					$recommend_maintenance = get_file_content(array('file' => $maintenance_template));
					$loop_template = get_match("/\#\#\#\#\#\#\#\#\#\#\#\#\#\#\#\#\#\#\#\#\#\#\#\#\#(.*)\#\#\#\#\#\#\#\#\#\#\#\#\#\#\#\#\#\#\#\#\#\#\#\#\#/s", $recommend_maintenance, false);

					if(is_multisite())
					{
						$result = get_sites(array('deleted' => 0, 'order' => 'DESC'));

						foreach($result as $r)
						{
							$blog_id = $r->blog_id;

							switch_to_blog($blog_id);

							$loop_template_temp = $loop_template;

							$setting_maintenance_page_html = get_option('setting_maintenance_page_html');

							$site_url = get_site_url();
							$site_url_clean = remove_protocol(array('url' => $site_url));

							$setting_maintenance_page = get_option('setting_maintenance_page');

							if($setting_maintenance_page > 0)
							{
								$post_title = get_the_title($setting_maintenance_page);
								$post_content = mf_get_post_content($setting_maintenance_page);
								$post_url_clean = remove_protocol(array('url' => get_permalink($setting_maintenance_page), 'clean' => true));
							}

							else
							{
								$post_title = $post_title_orig;
								$post_content = $post_content_orig;
								$post_url_clean = remove_protocol(array('url' => get_permalink(get_option('page_on_front')), 'clean' => true));
							}

							if($post_url_clean != '' && $post_content != '')
							{
								$loop_template_temp = str_replace("[site_url]", $site_url_clean, $loop_template_temp);
								$loop_template_temp = str_replace("[post_dir]", $upload_path.$post_url_clean."index.html", $loop_template_temp);

								if($setting_maintenance_page_html != '')
								{
									$loop_template_temp = preg_replace("/(\<article.*\>.*\<\/article\>)/is", str_replace('"', "'", $setting_maintenance_page_html), $loop_template_temp);

									$recommend_maintenance .= "\n".$loop_template_temp;
								}

								else
								{
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
						$loop_template_temp = $loop_template;

						$site_url = get_site_url();
						$site_url_clean = remove_protocol(array('url' => $site_url));
						$post_url_clean = remove_protocol(array('url' => get_permalink($option), 'clean' => true));
						$post_title = get_the_title($option);
						$post_content = mf_get_post_content($option);

						if($post_url_clean != '' && $post_content != '')
						{
							$loop_template_temp = str_replace("[site_url]", $site_url_clean, $loop_template_temp);
							$loop_template_temp = str_replace("[post_dir]", $upload_path.$post_url_clean."index.html", $loop_template_temp);
							$loop_template_temp = str_replace("[post_title]", $post_title, $loop_template_temp);
							$loop_template_temp = str_replace("[post_content]", trim(apply_filters('the_content', $post_content)), $loop_template_temp);

							$recommend_maintenance .= "\n".$loop_template_temp;
						}
					}

					if(strlen($recommend_maintenance) > 0)
					{
						$success = set_file_content(array('file' => $maintenance_file, 'mode' => 'w', 'content' => trim($recommend_maintenance)));

						if($success == true)
						{
							$done_text = sprintf(__("%s was saved", 'lang_theme_core'), "<span title='".$maintenance_file."'>".basename($maintenance_file)."</span>");
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
				###########################################

				update_option($setting_key.'_temp', $option, 'no');
			}

			$this->set_noindex_on_page($option);
		}

		function setting_activate_maintenance_callback()
		{
			$setting_key = get_setting_key(__FUNCTION__);
			$option = get_option_or_default($setting_key, 'no');

			echo show_select(array('data' => get_yes_no_for_select(), 'name' => $setting_key, 'value' => $option, 'description' => __("This will display the maintenance message to everyone except you as a superadmin, until you inactivate this mode again", 'lang_theme_core')));
		}

	function settings_theme_core_cookies_callback()
	{
		$setting_key = get_setting_key(__FUNCTION__);

		echo settings_header($setting_key, __("Theme", 'lang_theme_core')." - ".__("Sensitive Data", 'lang_theme_core'));
	}

		function get_post_password_amount()
		{
			global $wpdb;

			$arr_include = get_post_types(array('public' => true, 'exclude_from_search' => false), 'names');

			$wpdb->get_results($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." WHERE post_status = %s AND post_type IN('".implode("','", $arr_include)."') AND post_password != ''", 'public'));

			return $wpdb->num_rows;
		}

		function get_cookie_types()
		{
			global $wpdb;

			if(count($this->arr_sensitive_data_types) == 0)
			{
				$arr_sensitive_data_types = array(
					'login' => array(),
					'public' => array(),
				);

				$arr_sensitive_data_types['login']['wordpress_sec_'] = array('label' => __("Account details", 'lang_theme_core'), 'used' => false, 'lifetime' => "2 day");
				$arr_sensitive_data_types['login']['wordpress_logged_in_'] = array('label' => __("Indicates whether you are logged in", 'lang_theme_core'), 'used' => false, 'lifetime' => "2 day");

				$arr_sensitive_data_types['login']['wordpress_test_cookie'] = array('label' => __("Test if it is possible to set cookies", 'lang_theme_core'), 'used' => false, 'lifetime' => "2 day", 'personal_data' => false);
				$arr_sensitive_data_types['login']['wordpress_'] = array('label' => __("Authentication details", 'lang_theme_core'), 'used' => false, 'lifetime' => "2 day");

				$arr_sensitive_data_types['login']['wp-settings-time-'] = array('label' => __("Time when user settings was last saved", 'lang_theme_core'), 'used' => false, 'lifetime' => "", 'personal_data' => false);
				$arr_sensitive_data_types['login']['wp-settings-'] = array('label' => __("Customization for admin interface", 'lang_theme_core'), 'used' => false, 'lifetime' => "2 day", 'personal_data' => false);

				if($this->get_post_password_amount() > 0)
				{
					$arr_sensitive_data_types['public']['wp-postpass_'] = array('label' => __("Maintain session if a post is password protected", 'lang_theme_core'), 'used' => false, 'lifetime' => "2 day");
				}

				if(get_option('default_comment_status') == 'open')
				{
					$arr_sensitive_data_types['public']['comment_author_'] = array('label' => __("Remember comment author details", 'lang_theme_core'), 'used' => false, 'lifetime' => "1 year");
				}

				if(get_option('setting_cookie_info') > 0)
				{
					$arr_sensitive_data_types['public']['cookie_accepted'] = array('label' => __("Remember if visitor accepts sensitive data on the site", 'lang_theme_core'), 'used' => false, 'lifetime' => "1 year", 'personal_data' => false);
				}

				if(apply_filters('get_widget_search', 'theme-news-widget') > 0)
				{
					$wpdb->get_results($wpdb->prepare("SELECT option_id FROM ".$wpdb->options." WHERE option_name = %s AND option_value LIKE %s", 'widget_theme-news-widget', "\"news_hide_button\";s:3:\"yes\""));

					if($wpdb->num_rows > 0)
					{
						$arr_sensitive_data_types['public']['hide_news_'] = array('label' => __("Remember if a visitor has hidden the header news post", 'lang_theme_core'), 'used' => false, 'lifetime' => "1 year", 'personal_data' => false);
					}
				}

				if(apply_filters('get_widget_search', 'theme-info-widget') > 0)
				{
					$wpdb->get_results($wpdb->prepare("SELECT option_id FROM ".$wpdb->options." WHERE option_name = %s AND option_value NOT LIKE %s", 'widget_theme-info-widget', "\"info_time_limit\";s:3:\"0\""));

					if($wpdb->num_rows > 0)
					{
						$arr_sensitive_data_types['public']['cookie_theme_core_info_time_limit'] = array('label' => __("Remember if the visitor has seen the info", 'lang_theme_core')." (".__("Time Limit", 'lang_theme_core').")", 'used' => false, 'lifetime' => "1 year", 'personal_data' => false);
					}

					$wpdb->get_results($wpdb->prepare("SELECT option_id FROM ".$wpdb->options." WHERE option_name = %s AND option_value NOT LIKE %s", 'widget_theme-info-widget', "\"info_visit_limit\";s:3:\"0\""));

					if($wpdb->num_rows > 0)
					{
						$arr_sensitive_data_types['public']['cookie_theme_core_info_visit_limit'] = array('label' => __("Remember if the visitor has seen the info", 'lang_theme_core')." (".__("Visit Limit", 'lang_theme_core').")", 'used' => false, 'lifetime' => "1 year", 'personal_data' => false);
					}
				}

				// Check if external fonts are used on the site
				##############################
				$this->get_theme_fonts();
				$this->get_params();

				$arr_external_font_ids = array();

				foreach($this->options_fonts as $font_key => $arr_fonts)
				{
					if(isset($arr_fonts['url']) && $arr_fonts['url'] != '')
					{
						$arr_external_font_ids[$font_key] = $arr_fonts['title'];
					}
				}

				foreach($this->options_params as $arr_param)
				{
					if(isset($arr_param['type']) && $arr_param['type'] == 'font')
					{
						if(isset($arr_external_font_ids[$this->options[$arr_param['id']]]))
						{
							//$arr_sensitive_data_types['public']['font_'.$this->options[$arr_param['id']]] = array('label' => sprintf(__("Load the font %s", 'lang_theme_core'), $arr_external_font_ids[$this->options[$arr_param['id']]]), 'used' => false, 'lifetime' => "1 year", 'personal_data' => true);
							$arr_sensitive_data_types['font'][$this->options[$arr_param['id']]] = array('label' => sprintf(__("Load the font %s", 'lang_theme_core'), "<em>".$arr_external_font_ids[$this->options[$arr_param['id']]]."</em>"), 'used' => false, 'lifetime' => "1 year", 'personal_data' => true);
						}
					}
				}
				##############################

				$this->arr_sensitive_data_types = apply_filters('filter_cookie_types', $arr_sensitive_data_types);
			}
		}

		function get_cookie_list($data = array())
		{
			$out = "";

			if(isset($_COOKIE) && count($_COOKIE) > 0)
			{
				$this->get_cookie_types();

				$out .= "<ul>";

					foreach($_COOKIE as $cookie_key => $cookie_value)
					{
						$out .= "<li>";

							$cookie_icon = $cookie_explanation = "";

							foreach($this->arr_sensitive_data_types as $type => $arr_sensitive_data_types)
							{
								foreach($arr_sensitive_data_types as $key => $arr_value)
								{
									if(substr($cookie_key, 0, strlen($key)) == $key)
									{
										switch($type)
										{
											default:
											case 'public':
												$cookie_icon = "fas fa-users";
												$type_title = __("Public", 'lang_theme_core');
											break;

											case 'login':
												$cookie_icon = "fas fa-lock";
												$type_title = __("Login", 'lang_theme_core');
											break;
										}

										if(!isset($arr_value['personal_data']) || $arr_value['personal_data'] == true)
										{
											$cookie_icon .= " green";
										}

										else
										{
											$cookie_icon .= " grey";
											$type_title .= " (".__("without personal data", 'lang_theme_core').")";
										}

										$cookie_explanation = "<span title='".$type_title."'>".$arr_value['label']."</span>";

										$this->arr_sensitive_data_types[$type][$key]['used'] = true;

										break;
									}
								}
							}

							if($cookie_explanation != '')
							{
								$out .= "<i class='".$cookie_icon."' title='".shorten_text(array('string' => $cookie_key, 'limit' => 30, 'add_title' => false)).": ".shorten_text(array('string' => $cookie_value, 'limit' => 20, 'add_title' => false))."'></i> ".$cookie_explanation;
							}

							else
							{
								$out .= "<i class='fa fa-question-circle blue'></i> <strong>".shorten_text(array('string' => $cookie_key, 'limit' => 30, 'add_title' => true))."</strong>: ";

								if(is_array($cookie_value))
								{
									$cookie_value = var_export($cookie_value, true);
								}

								$out .= shorten_text(array('string' => $cookie_value, 'limit' => 10, 'add_title' => true));
							}

						$out .= "</li>";
					}

					foreach($this->arr_sensitive_data_types as $type => $arr_sensitive_data_types)
					{
						foreach($arr_sensitive_data_types as $key => $arr_value)
						{
							if($arr_value['used'] == false)
							{
								$cookie_title = sprintf(__("%s was not saved in your browser but can be saved by the site", 'lang_theme_core'), $key);

								switch($type)
								{
									default:
									case 'public':
										$cookie_icon = "fas fa-users red";
										$type_title = __("Public", 'lang_theme_core');
									break;

									case 'login':
										$cookie_icon = "fas fa-lock red";
										$type_title = __("Login", 'lang_theme_core');
									break;

									case 'font':
										$cookie_icon = "fas fa-font green";
										$cookie_title = __("The font is loaded somewhere on the site", 'lang_theme_core');
										$type_title = __("Font", 'lang_theme_core');
									break;

									case 'ip':
										$cookie_icon = "fas fa-receipt green";
										$cookie_title = __("The IP address is collected somewhere on the site", 'lang_theme_core');
										$type_title = __("IP", 'lang_theme_core');
									break;
								}

								$cookie_explanation = "<span title='".$type_title."'>".$arr_value['label']."</span>";

								$out .= "<li>
									<i class='".$cookie_icon."' title='".$cookie_title."'></i> ".$cookie_explanation
								."</li>";
							}
						}
					}

				$out .= "</ul>";
			}

			// Like this would ever happen...
			else
			{
				$out .= "<p>".__("There is no sensitive data saved on this site", 'lang_theme_core')."</p>";
			}

			if($data['return'] == 'html')
			{
				return $out;
			}
		}

		function setting_cookie_exists_callback()
		{
			echo $this->get_cookie_list(array('return' => 'html'));
		}

		function setting_cookie_info_callback()
		{
			$setting_key = get_setting_key(__FUNCTION__);
			$option = get_option($setting_key);

			$arr_data = array();
			get_post_children(array('add_choose_here' => true, 'where' => "(post_excerpt != '' || post_content != '')"), $arr_data);

			$description = "";

			// Not needed right now but just in case we switch this function and setting_cookie_exists_callback() around...
			$this->get_cookie_types();

			if(isset($this->arr_sensitive_data_types['public']) && is_array($this->arr_sensitive_data_types['public']) && count($this->arr_sensitive_data_types['public']) > 0)
			{
				if(!($option > 0))
				{
					$description .= "<i class='fa fa-exclamation-triangle yellow display_warning'></i> ";
				}

				$description .= __("There is sensitive information on the public site that is saved for visitors.", 'lang_theme_core')." ";
			}

			else if(isset($this->arr_sensitive_data_types['login']) && count($this->arr_sensitive_data_types['login']) > 0)
			{
				$description .= __("There is only sensitive information on this site that is saved when logging in so it is not necessary to add a page for this.", 'lang_theme_core')." ";
			}

			$description .= __("The content from this page will be displayed on the site until the visitor clicks to accept the collection of sensitive data.", 'lang_theme_core');

			echo show_select(array('data' => $arr_data, 'name' => $setting_key, 'value' => $option, 'suffix' => get_option_page_suffix(array('value' => $option)), 'description' => $description));
		}

		function setting_cookie_deactivate_until_allowed_callback()
		{
			$setting_key = get_setting_key(__FUNCTION__);
			$option = get_option($setting_key, 'no');

			echo show_select(array('data' => get_yes_no_for_select(), 'name' => $setting_key, 'value' => $option, 'description' => __("This will try to prevent sensitive information to be saved in the visitor's browser until the visitor have accepted your information from the page above", 'lang_theme_core')));
		}

	function admin_init()
	{
		global $pagenow;

		if($pagenow == 'options-general.php' && check_var('page') == 'settings_mf_base')
		{
			$plugin_include_url = plugin_dir_url(__FILE__);
			$plugin_version = get_plugin_version(__FILE__);

			mf_enqueue_script('script_theme_core', $plugin_include_url."script_wp.js", array('ajax_url' => admin_url('admin-ajax.php')), $plugin_version);
		}

		if(function_exists('wp_add_privacy_policy_content'))
		{
			if(get_option('setting_cookie_info') > 0)
			{
				$content = __("A cookie is saved when the visitor accepts the collection of sensitive data on the site, to make sure that the message asking for permission does not appear again.", 'lang_theme_core');

				wp_add_privacy_policy_content(__("Theme", 'lang_theme_core'), $content);
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
			$title_format = str_replace("[page_number]", $separator_full.sprintf( __("Page %s", 'lang_theme_core'), max($paged, $page)), $title_format);
		}

		$title_format = str_replace(array("[page_title]", "[site_title]", "[site_description]", "[page_number]", $separator_full.$separator_full), "", $title_format);
		$title_format = trim($title_format, $separator_full);

		return $title_format;
	}

	function wp_head()
	{
		global $wpdb, $post;

		/*if(IS_ADMINISTRATOR || is_admin() || strpos($_SERVER['REQUEST_URI'], "/include/api/") || in_array($GLOBALS['pagenow'], array('wp-login.php'))) //, 'wp-register.php'
		{
			// Do nothing
		}

		else*/
		if(!is_user_logged_in())
		{
			$setting_maintenance_page = get_option('setting_maintenance_page');

			if($setting_maintenance_page > 0 && get_option('setting_activate_maintenance') == 'yes')
			{
				$setting_maintenance_page_html = get_option('setting_maintenance_page_html');

				if($setting_maintenance_page_html != '')
				{
					echo $setting_maintenance_page_html;
				}

				else
				{
					$post_title = get_the_title($setting_maintenance_page);
					$post_content = mf_get_post_content($setting_maintenance_page);

					//get_header();

						echo "<article class='post_type_page'>
							<section>
								<h1>".$post_title."</h1>"
								.$post_content
							."</section>
						</article>";

					//get_footer();
				}

				exit;
			}

			else if(get_option('setting_no_public_pages') == 'yes')
			{
				mf_redirect(get_site_url()."/wp-admin/");
			}

			else if(get_option('setting_theme_core_login') == 'yes' && apply_filters('is_public_page', true))
			{
				mf_redirect(wp_login_url()."?redirect_to=".$_SERVER['REQUEST_URI']);
			}
		}

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

		$setting_cookie_info = get_option('setting_cookie_info');

		if($setting_cookie_info > 0)
		{
			mf_enqueue_style('style_theme_core_cookies', $plugin_include_url."style_cookies.css", $plugin_version);
			mf_enqueue_script('script_theme_core_cookies', $plugin_include_url."script_cookies.js", array('plugin_url' => $plugin_include_url), $plugin_version);

			$result = $wpdb->get_results($wpdb->prepare("SELECT ID, post_title, post_excerpt, post_content FROM ".$wpdb->posts." WHERE ID = '%d' AND post_type = %s AND post_status = %s", $setting_cookie_info, 'page', 'publish'));

			foreach($result as $r)
			{
				$post_id = $r->ID;
				$post_title = $r->post_title;
				$post_excerpt = apply_filters('the_content', $r->post_excerpt);
				$post_content = apply_filters('the_content', $r->post_content);

				$this->footer_output .= "<div id='accept_cookies'>
					<div>";

						//$this->footer_output .= "<i class='fa fa-gavel red fa-2x'></i>";

						$buttons = "<a href='#accept_cookie' class='button color_button'><i class='fa fa-check green'></i>".__("Accept", 'lang_theme_core')."</a>";

						if($post_excerpt != '')
						{
							$this->footer_output .= $post_excerpt;

							if($post_content != '' && $post_content != $post_excerpt)
							{
								$buttons .= " <a href='".get_permalink($post_id)."' class='button color_button_border' rel='external'>".__("Read More", 'lang_theme_core')."</a>";
							}
						}

						else
						{
							$this->footer_output .= $post_content;
						}

						$this->footer_output .= "<div class='form_button'>".$buttons."</div>";

					$this->footer_output .= "</div>
				</div>
				<div id='accepted_cookies'>
					<span class='fa-stack fa-2x' title='".__("You have accepted that we collect sensitive data. Do you wish to remove this acceptance?", 'lang_theme_core')."'>
						<i class='fas fa-cookie-bite fa-stack-1x'></i>
						<i class='fas fa-ban fa-stack-2x red'></i>
					</span>
				</div>";
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

		if(get_current_user_id() > 0 && get_option('setting_theme_core_enable_edit_mode', 'yes') == 'yes')
		{
			mf_enqueue_style('style_theme_core_locked', $plugin_include_url."style_locked.css", $plugin_version);

			$this->footer_output .= "<div id='site_locked'>
				<a href='".admin_url()."'><i class='fa fa-lock' title='".__("Go to Admin", 'lang_theme_core')."'></i></a>";

				if(isset($post->ID) && IS_EDITOR)
				{
					$this->footer_output .= "<a href='".admin_url("post.php?post=".$post->ID."&action=edit")."'><i class='fa fa-wrench' title='".__("Edit Page", 'lang_theme_core')."'></i></a>";
				}

			$this->footer_output .= "</div>";
		}
	}

	function body_class($classes)
	{
		$classes[] = "is_site";

		if(is_user_logged_in())
		{
			$classes[] = "is_logged_in";
		}

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

	function wp_nav_menu_objects($items, $args)
	{
		foreach($items as $key => $value)
		{
			if(!is_user_logged_in() && in_array(get_post_status($value->object_id), array('draft', 'private')))
			{
				unset($items[$key]);
			}
		}

		return $items;
	}

	function get_search_form($html)
	{
		return "<form method='get' action='".esc_url(home_url('/'))."' class='mf_form'>"
			.show_textfield(array('type' => 'search', 'name' => 's', 'value' => check_var('s'), 'placeholder' => __("Search here", 'lang_theme_core'), 'xtra' => " autocomplete='off'")) //get_search_query()
			."<div class='form_button'>"
				.show_button(array('text' => __("Search", 'lang_theme_core')))
			."</div>
		</form>";
	}

	/*function pre_get_posts($query)
	{
		if($query->is_main_query() && $query->is_search)
		{
			$strSearch = check_var('s');

			$dteSearchDate = date("Y-m-d", strtotime($strSearch));

			if($dteSearchDate > DEFAULT_DATE)
			{
				list($intYear, $intMonth, $intDay) = explode("-", $dteSearchDate);

				$query->set('date_query', array(
					array(
						'column' => 'post_date_gmt',
						'year' => $intYear,
						'month' => $intMonth,
						'day' => $intDay,
						'inclusive' => true,
					)
				));
			}
		}

		return $query;
	}*/

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
		if($this->is_theme_active === '')
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

		if(count($this->arr_post_types) == 0)
		{
			$this->get_public_post_types(array('allow_password_protected' => $data['allow_password_protected']));
		}

		foreach($this->arr_post_types as $post_id => $post_title)
		{
			if($data['allow_noindex'] == false && $this->has_noindex($post_id) || $data['allow_password_protected'] == false && $this->is_post_password_protected($post_id))
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

		foreach($options_params as $param_key => $arr_param)
		{
			if(!isset($arr_param['category']) && !isset($arr_param['category_end']))
			{
				$id = $arr_param['id'];
				$default = (isset($arr_param['default']) ? $arr_param['default'] : false);
				$force_default = (isset($arr_param['force_default']) ? $arr_param['force_default'] : false);
				$value_old = (isset($arr_theme_mods[$id]) ? $arr_theme_mods[$id] : false);

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

		$options_params[] = array('category' => __("Generic", 'lang_theme_core'), 'id' => 'mf_theme_body');
			$options_params[] = array('type' => 'text', 'id' => 'body_bg', 'title' => __("Background", 'lang_theme_core'));
				$options_params[] = array('type' => 'color', 'id' => 'body_bg_color', 'title' => " - ".__("Color", 'lang_theme_core'), 'default' => '#ffffff');
				$options_params[] = array('type' => 'image', 'id' => 'body_bg_image', 'title' => " - ".__("Image", 'lang_theme_core'));
			$options_params[] = array('type' => 'text', 'id' => 'main_padding', 'title' => __("Padding", 'lang_theme_core'), 'default' => "1em 2em");
			$options_params[] = array('type' => 'font', 'id' => 'body_font', 'title' => __("Font", 'lang_theme_core'));

			$options_params[] = array('type' => 'color', 'id' => 'body_color', 'title' => __("Text Color", 'lang_theme_core'));
				$options_params[] = array('type' => 'color', 'id' => 'body_link_color', 'title' => " - ".__("Link Color", 'lang_theme_core'));
				$options_params[] = array('type' => 'text_decoration', 'id' => 'body_link_underline', 'title' => " - ".__("Link Underline", 'lang_theme_core'), 'default' => 'underline');

			$options_params[] = array('type' => 'number', 'id' => 'website_max_width', 'title' => __("Breakpoint", 'lang_theme_core')." (".__("Tablet", 'lang_theme_core').")", 'default' => "1100");
			$options_params[] = array('type' => 'text', 'id' => 'body_desktop_font_size', 'title' => __("Font Size", 'lang_theme_core'), 'default' => ".625em");
			$options_params[] = array('type' => 'number', 'id' => 'mobile_breakpoint', 'title' => __("Breakpoint", 'lang_theme_core')." (".__("Mobile", 'lang_theme_core').")", 'default' => "600");
			$options_params[] = array('type' => 'text', 'id' => 'body_font_size', 'title' => __("Font Size", 'lang_theme_core')." (".__("Mobile", 'lang_theme_core').")", 'default' => "2.4vw", 'show_if' => 'mobile_breakpoint');

			$options_params[] = array('type' => 'overflow', 'id' => 'body_scroll', 'title' => __("Scroll Bar", 'lang_theme_core'), 'default' => 'scroll');

				if($theme_dir_name == 'mf_parallax')
				{
					$options_params[] = array('type' => 'text', 'id' => 'mobile_aside_img_max_width', 'title' => __("Aside Image Width", 'lang_theme_core')." (".__("Mobile", 'lang_theme_core').")", 'show_if' => "mobile_breakpoint");
				}

			$options_params[] = array('type' => 'text', 'id' => 'body_print_font_size', 'title' => __("Font Size", 'lang_theme_core')." (".__("Print", 'lang_theme_core').")");

		$options_params[] = array('category_end' => "");

		$options_params[] = array('category' => " - ".__("Forms", 'lang_theme_core'), 'id' => 'mf_theme_generic_forms');
			$options_params[] = array('type' => 'color', 'id' => 'form_container_background_color', 'title' => __("Background Color", 'lang_theme_core')." (".__("Container", 'lang_theme_core').")");
			$options_params[] = array('type' => 'text', 'id' => 'form_container_border', 'title' => __("Border", 'lang_theme_core'));
			$options_params[] = array('type' => 'text', 'id' => 'form_container_border_radius', 'title' => __("Border Radius", 'lang_theme_core'));
			$options_params[] = array('type' => 'text', 'id' => 'form_container_padding', 'title' => __("Padding", 'lang_theme_core'));
			$options_params[] = array('type' => 'checkbox', 'id' => 'form_join_fields', 'title' => __("Join Fields", 'lang_theme_core'), 'default' => 1);
			$options_params[] = array('type' => 'text', 'id' => 'form_border_radius', 'title' => __("Border Radius", 'lang_theme_core')." (".__("Fields", 'lang_theme_core').")", 'default' => ".3em");
			$options_params[] = array('type' => 'text', 'id' => 'form_button_border_radius', 'title' => __("Border Radius", 'lang_theme_core')." (".__("Buttons", 'lang_theme_core').")", 'default' => ".3em");
			$options_params[] = array('type' => 'text', 'id' => 'form_button_padding', 'title' => __("Padding", 'lang_theme_core'));

			$options_params[] = array('type' => 'text', 'id' => 'button_size', 'title' => __("Font Size", 'lang_theme_core'), 'default' => (function_exists('is_plugin_active') && is_plugin_active("mf_webshop/index.php") ? "1.3em" : ''));

			$options_params[] = array('type' => 'color', 'id' => 'button_color', 'title' => __("Color", 'lang_theme_core'), 'default' => "#000000");
			$options_params[] = array('type' => 'color', 'id' => 'button_color_secondary', 'title' => __("Color", 'lang_theme_core')." (".__("Secondary", 'lang_theme_core').")", 'default' => "#c78e91");
			$options_params[] = array('type' => 'color', 'id' => 'button_color_negative', 'title' => __("Color", 'lang_theme_core')." (".__("Negative", 'lang_theme_core').")", 'default' => get_option('setting_color_button_negative', "#e47676"));
			$options_params[] = array('type' => 'color', 'id' => 'button_color_negative', 'title' => __("Color", 'lang_theme_core')." (".__("Negative", 'lang_theme_core').")", 'default' => "#e47676");
		$options_params[] = array('category_end' => "");

		if($theme_dir_name == 'mf_theme')
		{
			if(is_active_widget_area('widget_pre_header'))
			{
				$options_params[] = array('category' => __("Before Header", 'lang_theme_core'), 'id' => 'mf_theme_pre_header');
					$options_params[] = array('type' => 'checkbox', 'id' => 'pre_header_full_width', 'title' => __("Full Width", 'lang_theme_core'), 'default' => 1);
					$options_params[] = array('type' => 'text', 'id' => 'pre_header_bg', 'title' => __("Background", 'lang_theme_core'));
						$options_params[] = array('type' => 'color', 'id' => 'pre_header_bg_color', 'title' => " - ".__("Color", 'lang_theme_core'));
					$options_params[] = array('type' => 'text', 'id' => 'pre_header_widget_font_size', 'title' => __("Font Size", 'lang_theme_core'));
					$options_params[] = array('type' => 'text', 'id' => 'pre_header_padding', 'title' => __("Padding", 'lang_theme_core'));
					$options_params[] = array('type' => 'color', 'id' => 'pre_header_color', 'title' => __("Text Color", 'lang_theme_core'));
					$options_params[] = array('type' => 'overflow', 'id' => 'pre_header_overflow', 'title' => __("Overflow", 'lang_theme_core'));
				$options_params[] = array('category_end' => "");
			}
		}

		$options_params[] = array('category' => __("Header", 'lang_theme_core'), 'id' => 'mf_theme_header');
			$options_params[] = array('type' => 'checkbox', 'id' => 'header_full_width', 'title' => __("Full Width", 'lang_theme_core'), 'default' => 1);
			$options_params[] = array('type' => 'position', 'id' => 'header_fixed', 'title' => __("Position", 'lang_theme_core'), 'default' => 'relative');
			$options_params[] = array('type' => 'text', 'id' => 'header_bg', 'title' => __("Background", 'lang_theme_core'));
				$options_params[] = array('type' => 'color', 'id' => 'header_bg_color', 'title' => " - ".__("Color", 'lang_theme_core')); //, 'ignore_default_if' => 'body_bg', 'default' => '#eeeeee'
				$options_params[] = array('type' => 'image', 'id' => 'header_bg_image', 'title' => " - ".__("Image", 'lang_theme_core'));

			if($theme_dir_name == 'mf_parallax')
			{
				$options_params[] = array('type' => 'checkbox', 'id' => 'header_override_bg_with_page_bg', 'title' => __("Override background with page background", 'lang_theme_core'), 'default' => 2);
			}

			$options_params[] = array('type' => 'text', 'id' => 'header_padding', 'title' => __("Padding", 'lang_theme_core'));
			$options_params[] = array('type' => 'overflow', 'id' => 'header_overflow', 'title' => __("Overflow", 'lang_theme_core'));
		$options_params[] = array('category_end' => "");

		$options_params[] = array('category' => " - ".__("Search", 'lang_theme_core'), 'id' => 'mf_theme_header_search');
			$options_params[] = array('type' => 'color', 'id' => 'search_color', 'title' => __("Color", 'lang_theme_core'));
			$options_params[] = array('type' => 'text', 'id' => 'search_size', 'title' => __("Font Size", 'lang_theme_core'), 'default' => "1.4em");
		$options_params[] = array('category_end' => "");

		$options_params[] = array('category' => " - ".__("Logo", 'lang_theme_core'), 'id' => 'mf_theme_logo');
			$options_params[] = array('type' => 'text', 'id' => 'logo_padding', 'title' => __("Padding", 'lang_theme_core')); //, 'default' => '.4em 0'
			$options_params[] = array('type' => 'image', 'id' => 'header_logo', 'title' => __("Image", 'lang_theme_core'));
				$options_params[] = array('type' => 'image', 'id' => 'header_logo_hover', 'title' => __("Image", 'lang_theme_core')." (".__("Hover", 'lang_theme_core').")", 'show_if' => 'header_logo');
			$options_params[] = array('type' => 'float', 'id' => 'logo_float', 'title' => __("Alignment", 'lang_theme_core'), 'default' => 'left');
			$options_params[] = array('type' => 'text', 'id' => 'logo_width', 'title' => __("Width", 'lang_theme_core'), 'default' => '14em');
			$options_params[] = array('type' => 'image', 'id' => 'header_mobile_logo', 'title' => __("Image", 'lang_theme_core')." (".__("Mobile", 'lang_theme_core').")", 'show_if' => 'mobile_breakpoint');
				$options_params[] = array('type' => 'image', 'id' => 'header_mobile_logo_hover', 'title' => __("Image", 'lang_theme_core')." (".__("Mobile", 'lang_theme_core')." - ".__("Hover", 'lang_theme_core').")", 'show_if' => 'header_mobile_logo');
			$options_params[] = array('type' => 'text', 'id' => 'logo_width_mobile', 'title' => __("Width", 'lang_theme_core')." (".__("Mobile", 'lang_theme_core').")", 'default' => '20em');
			$options_params[] = array('type' => 'font', 'id' => 'logo_font', 'title' => __("Font", 'lang_theme_core'), 'hide_if' => 'header_logo');
			$options_params[] = array('type' => 'text', 'id' => 'logo_font_size', 'title' => __("Font Size", 'lang_theme_core'), 'default' => "3rem");
				$options_params[] = array('type' => 'text', 'id' => 'slogan_font_size', 'title' => __("Font Size", 'lang_theme_core')." (".__("Tagline", 'lang_theme_core').")", 'default' => ".4em");
				$options_params[] = array('type' => 'text', 'id' => 'slogan_margin', 'title' => __("Margin", 'lang_theme_core')." (".__("Tagline", 'lang_theme_core').")", 'default' => "0 0 1em");
			$options_params[] = array('type' => 'color', 'id' => 'logo_color', 'title' => __("Color", 'lang_theme_core'));
		$options_params[] = array('category_end' => "");

		$options_params[] = array('category' => __("Navigation", 'lang_theme_core'), 'id' => 'mf_theme_navigation');

			if($theme_dir_name == 'mf_parallax')
			{
				$options_params[] = array('type' => 'checkbox', 'id' => 'nav_mobile', 'title' => __("Compressed", 'lang_theme_core')." (".__("Mobile", 'lang_theme_core').")", 'default' => 2);
					$options_params[] = array('type' => 'checkbox', 'id' => 'nav_click2expand', 'title' => __("Click to expand", 'lang_theme_core'), 'default' => 1);
				$options_params[] = array('type' => 'text', 'id' => 'nav_padding', 'title' => __("Padding", 'lang_theme_core'), 'default' => "0 1em");
					$options_params[] = array('type' => 'text', 'id' => 'nav_padding_mobile', 'title' => __("Padding", 'lang_theme_core')." (".__("Mobile", 'lang_theme_core').")", 'show_if' => 'nav_padding');
				$options_params[] = array('type' => 'float', 'id' => 'nav_float', 'title' => __("Alignment", 'lang_theme_core'), 'default' => "right");
					$options_params[] = array('type' => 'float', 'id' => 'nav_float_mobile', 'title' => __("Alignment", 'lang_theme_core')." (".__("Mobile", 'lang_theme_core').")", 'default' => "none", 'show_if' => 'nav_float');
			}

			$options_params[] = array('type' => 'checkbox', 'id' => 'nav_full_width', 'title' => __("Full Width", 'lang_theme_core'), 'default' => 1);
			$options_params[] = array('type' => 'align', 'id' => 'nav_align', 'title' => __("Alignment", 'lang_theme_core'), 'default' => "right");
			$options_params[] = array('type' => 'text', 'id' => 'nav_bg', 'title' => __("Background", 'lang_theme_core'));
				$options_params[] = array('type' => 'color', 'id' => 'nav_bg_color', 'title' => " - ".__("Color", 'lang_theme_core'));
				$options_params[] = array('type' => 'image', 'id' => 'nav_bg_image', 'title' => " - ".__("Image", 'lang_theme_core'));
			$options_params[] = array('type' => 'clear', 'id' => 'nav_clear', 'title' => __("Clear", 'lang_theme_core'), 'default' => "right");
			$options_params[] = array('type' => 'text', 'id' => 'nav_padding', 'title' => __("Padding", 'lang_theme_core'));
			$options_params[] = array('type' => 'font', 'id' => 'nav_font', 'title' => __("Font", 'lang_theme_core'));
			$options_params[] = array('type' => 'text', 'id' => 'nav_size', 'title' => __("Font Size", 'lang_theme_core'), 'default' => "2em");
			$options_params[] = array('type' => 'weight', 'id' => 'nav_font_weight', 'title' => __("Weight", 'lang_theme_core'));
			$options_params[] = array('type' => 'color', 'id' => 'nav_color', 'title' => __("Text Color", 'lang_theme_core'));
				$options_params[] = array('type' => 'color', 'id' => 'nav_color_hover', 'title' => __("Text Color", 'lang_theme_core')." (".__("Hover", 'lang_theme_core').")", 'show_if' => 'nav_color');
			$options_params[] = array('type' => 'text', 'id' => 'nav_link_padding', 'title' => __("Link Padding", 'lang_theme_core'), 'default' => "1em");

			if($theme_dir_name == 'mf_theme')
			{
				$options_params[] = array('type' => 'color', 'id' => 'nav_underline_color_hover', 'title' => " - ".__("Underline Color", 'lang_theme_core')." (".__("Hover", 'lang_theme_core').")");
				$options_params[] = array('type' => 'color', 'id' => 'nav_bg_current', 'title' => __("Background", 'lang_theme_core')." (".__("Current", 'lang_theme_core').")", 'show_if' => 'nav_color');
				$options_params[] = array('type' => 'color', 'id' => 'nav_color_current', 'title' => __("Text Color", 'lang_theme_core')." (".__("Current", 'lang_theme_core').")", 'show_if' => 'nav_color');
			}

		$options_params[] = array('category_end' => "");

		if($theme_dir_name == 'mf_theme')
		{
			$options_params[] = array('category' => " - ".__("Submenu", 'lang_theme_core'), 'id' => 'mf_theme_navigation_sub');
				$options_params[] = array('type' => 'direction', 'id' => 'sub_nav_direction', 'title' => __("Direction", 'lang_theme_core'), 'default' => 'horizontal');
				$options_params[] = array('type' => 'checkbox', 'id' => 'sub_nav_arrow', 'title' => __("Show Up Arrow", 'lang_theme_core'), 'default' => 2);
				$options_params[] = array('type' => 'color', 'id' => 'sub_nav_bg', 'title' => __("Background", 'lang_theme_core'), 'default' => "#ccc");
					$options_params[] = array('type' => 'color', 'id' => 'sub_nav_bg_hover', 'title' => " - ".__("Background", 'lang_theme_core')." (".__("Hover", 'lang_theme_core').")", 'show_if' => 'sub_nav_bg');
				$options_params[] = array('type' => 'color', 'id' => 'sub_nav_color', 'title' => __("Text Color", 'lang_theme_core'), 'default' => "#333");
					$options_params[] = array('type' => 'color', 'id' => 'sub_nav_color_hover', 'title' => " - ".__("Text Color", 'lang_theme_core')." (".__("Hover", 'lang_theme_core').")", 'show_if' => 'sub_nav_color');
				$options_params[] = array('type' => 'text', 'id' => 'sub_nav_link_padding', 'title' => __("Link Padding", 'lang_theme_core'), 'default' => ".8em");
			$options_params[] = array('category_end' => "");
		}

		$options_params[] = array('category' => " - ".__("Mobile Menu", 'lang_theme_core'), 'id' => 'mf_theme_navigation_hamburger');

			if($theme_dir_name == 'mf_theme')
			{
				$options_params[] = array('type' => 'checkbox', 'id' => 'hamburger_collapse_if_no_space', 'title' => __("Display when menu runs out of space", 'lang_theme_core'), 'default' => 1);
				$options_params[] = array('type' => 'text', 'id' => 'hamburger_menu_bg', 'title' => __("Background", 'lang_theme_core')." (".__("Menu", 'lang_theme_core').")");
			}

			if($theme_dir_name == 'mf_parallax')
			{
				$options_params[] = array('type' => 'float', 'id' => 'hamburger_position', 'title' => __("Alignment", 'lang_theme_core'), 'default' => "right");
				$options_params[] = array('type' => 'position', 'id' => 'hamburger_fixed', 'title' => __("Position", 'lang_theme_core'));
			}

			$options_params[] = array('type' => 'text', 'id' => 'hamburger_font_size', 'title' => __("Font Size", 'lang_theme_core'), 'default' => "1.5em");
			$options_params[] = array('type' => 'text', 'id' => 'hamburger_margin', 'title' => __("Padding", 'lang_theme_core'), 'default' => ".8em");

		$options_params[] = array('category_end' => "");

		if($theme_dir_name == 'mf_theme')
		{
			$options_params[] = array('category' => " - ".__("Secondary", 'lang_theme_core'), 'id' => 'mf_theme_navigation_secondary');
				$options_params[] = array('type' => 'text', 'id' => 'nav_secondary_bg', 'title' => __("Background", 'lang_theme_core'));
				$options_params[] = array('type' => 'text', 'id' => 'nav_secondary_link_padding', 'title' => __("Link Padding", 'lang_theme_core'));
				$options_params[] = array('type' => 'clear', 'id' => 'nav_secondary_clear', 'title' => __("Clear", 'lang_theme_core'), 'default' => "none");
				$options_params[] = array('type' => 'text', 'id' => 'nav_secondary_size', 'title' => __("Font Size", 'lang_theme_core'), 'default' => "1.4em");
				$options_params[] = array('type' => 'align', 'id' => 'nav_secondary_align', 'title' => __("Alignment", 'lang_theme_core'), 'default' => "right");
				$options_params[] = array('type' => 'color', 'id' => 'nav_secondary_color', 'title' => __("Text Color", 'lang_theme_core'));
					$options_params[] = array('type' => 'color', 'id' => 'nav_secondary_color_hover', 'title' => " - ".__("Text Color", 'lang_theme_core')." (".__("Hover", 'lang_theme_core').")");
				$options_params[] = array('type' => 'color', 'id' => 'nav_secondary_bg_current', 'title' => __("Background", 'lang_theme_core')." (".__("Current", 'lang_theme_core').")", 'show_if' => 'nav_color');
				$options_params[] = array('type' => 'color', 'id' => 'nav_secondary_color_current', 'title' => __("Text Color", 'lang_theme_core')." (".__("Current", 'lang_theme_core').")", 'show_if' => 'nav_color');
			$options_params[] = array('category_end' => "");
		}

		if(is_active_widget_area('widget_slide'))
		{
			$options_params[] = array('category' => " - ".__("Slide Menu", 'lang_theme_core'), 'id' => 'mf_theme_navigation_slide');

				if($theme_dir_name == 'mf_parallax')
				{
					$options_params[] = array('type' => 'float', 'id' => 'slide_nav_position', 'title' => __("Alignment", 'lang_theme_core'), 'default' => "right");
				}

				//$options_params[] = array('type' => 'text', 'id' => 'slide_nav_fade_bg', 'title' => __("Background", 'lang_theme_core')." (".__("Fade", 'lang_theme_core').")", 'default' => "rgba(0, 0, 0, .7)");

				if($theme_dir_name == 'mf_theme')
				{
					$options_params[] = array(
						'type' => 'number',
						'input_attrs' => array(
							'min' => .1,
							'max' => 2,
							'step' => .1,
						),
						'id' => 'slide_nav_animation_length',
						'title' => __("Animation Length", 'lang_theme_core'),
						'default' => .5,
					);

					$options_params[] = array(
						'type' => 'number',
						'input_attrs' => array(
							'min' => 0,
							'max' => 100,
						),
						'id' => 'slide_nav_content_offset',
						'title' => __("Content Offset", 'lang_theme_core'),
						'default' => 20,
					);
				}

				$options_params[] = array('type' => 'text', 'id' => 'slide_nav_bg_full', 'title' => __("Background", 'lang_theme_core'));
				$options_params[] = array('type' => 'color', 'id' => 'slide_nav_bg', 'title' => __("Background Color", 'lang_theme_core'), 'default' => "#fff");
				//$options_params[] = array('type' => 'text', 'id' => 'slide_nav_width', 'title' => __("Width", 'lang_theme_core'), 'default' => "90%");
				//$options_params[] = array('type' => 'text', 'id' => 'slide_nav_max_width', 'title' => __("Max Width", 'lang_theme_core'), 'default' => "300px");
				$options_params[] = array('type' => 'color', 'id' => 'slide_nav_color', 'title' => __("Text Color", 'lang_theme_core'));

				$options_params[] = array('type' => 'text', 'id' => 'slide_nav_letter_spacing', 'title' => __("Letter Spacing", 'lang_theme_core'), 'default' => ".2em");
				$options_params[] = array('type' => 'text', 'id' => 'slide_nav_link_padding', 'title' => __("Link Padding", 'lang_theme_core'), 'default' => "1.5em 1em 1em");
				$options_params[] = array('type' => 'color', 'id' => 'slide_nav_bg_hover', 'title' => __("Background", 'lang_theme_core')." (".__("Hover", 'lang_theme_core').")", 'show_if' => 'slide_nav_bg');
				$options_params[] = array('type' => 'color', 'id' => 'slide_nav_color_hover', 'title' => __("Text Color", 'lang_theme_core')." (".__("Hover", 'lang_theme_core').")", 'show_if' => 'slide_nav_color');
				$options_params[] = array('type' => 'text', 'id' => 'slide_nav_hover_indent', 'title' => __("Text Indent", 'lang_theme_core')." (".__("Hover", 'lang_theme_core').")", 'default' => ".3em");
				$options_params[] = array('type' => 'color', 'id' => 'slide_nav_color_current', 'title' => __("Text Color", 'lang_theme_core')." (".__("Current", 'lang_theme_core').")");
				$options_params[] = array('type' => 'color', 'id' => 'slide_nav_sub_bg', 'title' => __("Submenu", 'lang_theme_core')." - ".__("Background", 'lang_theme_core'));
					$options_params[] = array('type' => 'text', 'id' => 'slide_nav_sub_font_size', 'title' => " - ".__("Font Size", 'lang_theme_core'));
					$options_params[] = array('type' => 'weight', 'id' => 'slide_nav_sub_font_weight', 'title' => " - ".__("Weight", 'lang_theme_core'));
					$options_params[] = array('type' => 'color', 'id' => 'slide_nav_sub_bg_hover', 'title' => " - ".__("Background", 'lang_theme_core')." (".__("Hover", 'lang_theme_core').")", 'show_if' => 'slide_nav_bg');
				$options_params[] = array('type' => 'text', 'id' => 'slide_nav_sub_indent', 'title' => " - ".__("Text Indent", 'lang_theme_core'), 'default' => "1.4em");
					$options_params[] = array('type' => 'text', 'id' => 'slide_nav_sub_hover_indent', 'title' => " - ".__("Text Indent", 'lang_theme_core')." (".__("Hover", 'lang_theme_core').")", 'default' => "2em");

			$options_params[] = array('category_end' => "");
		}

		if($theme_dir_name == 'mf_parallax')
		{
			if(is_active_widget_area('widget_pre_content'))
			{
				$options_params[] = array('category' => __("Pre Content", 'lang_theme_core'), 'id' => 'mf_parallax_pre_content');
					$options_params[] = array('type' => 'checkbox', 'id' => 'pre_content_full_width', 'title' => __("Full Width", 'lang_theme_core'), 'default' => 1);
					$options_params[] = array('type' => 'text', 'id' => 'pre_content_bg', 'title' => __("Background", 'lang_theme_core'));
						$options_params[] = array('type' => 'color', 'id' => 'pre_content_bg_color', 'title' => " - ".__("Color", 'lang_theme_core'));
						$options_params[] = array('type' => 'image', 'id' => 'pre_content_bg_image', 'title' => " - ".__("Image", 'lang_theme_core'));
					$options_params[] = array('type' => 'text', 'id' => 'pre_content_padding', 'title' => __("Padding", 'lang_theme_core'));
				$options_params[] = array('category_end' => "");
			}
		}

		if($theme_dir_name == 'mf_theme')
		{
			if(is_active_widget_area('widget_after_header'))
			{
				$options_params[] = array('category' => __("After Header", 'lang_theme_core'), 'id' => 'mf_theme_after_header');
					$options_params[] = array('type' => 'checkbox', 'id' => 'after_header_full_width', 'title' => __("Full Width", 'lang_theme_core'), 'default' => 1);
					$options_params[] = array('type' => 'text', 'id' => 'after_header_bg', 'title' => __("Background", 'lang_theme_core'));
						$options_params[] = array('type' => 'color', 'id' => 'after_header_bg_color', 'title' => " - ".__("Color", 'lang_theme_core'));
						$options_params[] = array('type' => 'image', 'id' => 'after_header_bg_image', 'title' => " - ".__("Image", 'lang_theme_core'));
					$options_params[] = array('type' => 'text', 'id' => 'after_header_widget_font_size', 'title' => __("Font Size", 'lang_theme_core'));
					$options_params[] = array('type' => 'text', 'id' => 'after_header_padding', 'title' => __("Padding", 'lang_theme_core'));
						$options_params[] = array('type' => 'text', 'id' => 'after_header_widget_padding', 'title' => " - ".__("Widget Padding", 'lang_theme_core'), 'default' => "0 0 .5em");
					$options_params[] = array('type' => 'color', 'id' => 'after_header_color', 'title' => __("Text Color", 'lang_theme_core'));
					$options_params[] = array('type' => 'overflow', 'id' => 'after_header_overflow', 'title' => __("Overflow", 'lang_theme_core'));
					/*$options_params[] = array('type' => 'text', 'id' => 'after_header_widget_font_size', 'title' => __("Font Size", 'lang_theme_core'));
					$options_params[] = array('type' => 'text', 'id' => 'after_header_padding', 'title' => __("Padding", 'lang_theme_core'));
						$options_params[] = array('type' => 'text', 'id' => 'after_header_widget_padding', 'title' => " - ".__("Widget Padding", 'lang_theme_core'), 'default' => "0 0 .5em");*/
				$options_params[] = array('category_end' => "");
			}

			/* This does not work together with Hero */
			/*if(is_active_widget_area('widget_front'))
			{*/
				$options_params[] = array('category' => __("Pre Content", 'lang_theme_core'), 'id' => 'mf_theme_pre_content');
					$options_params[] = array('type' => 'checkbox', 'id' => 'pre_content_full_width', 'title' => __("Full Width", 'lang_theme_core'), 'default' => 1);
					$options_params[] = array('type' => 'text', 'id' => 'front_bg', 'title' => __("Background", 'lang_theme_core'));
						$options_params[] = array('type' => 'color', 'id' => 'pre_content_bg_color', 'title' => " - ".__("Color", 'lang_theme_core'));
						$options_params[] = array('type' => 'image', 'id' => 'pre_content_bg_image', 'title' => " - ".__("Image", 'lang_theme_core'));
					//$options_params[] = array('type' => 'text', 'id' => 'pre_content_widget_font_size', 'title' => __("Font Size", 'lang_theme_core'));
					$options_params[] = array('type' => 'text', 'id' => 'front_padding', 'title' => __("Padding", 'lang_theme_core'));
					$options_params[] = array('type' => 'color', 'id' => 'front_color', 'title' => __("Text Color", 'lang_theme_core'));
				$options_params[] = array('category_end' => "");
			//}
		}

		$options_params[] = array('category' => __("Content", 'lang_theme_core'), 'id' => 'mf_theme_content');

			if($theme_dir_name == 'mf_parallax')
			{
				$options_params[] = array('type' => 'checkbox', 'id' => 'content_stretch_height', 'title' => __("Match Height with Window Size", 'lang_theme_core'), 'default' => 2);
				$options_params[] = array('type' => 'float', 'id' => 'content_main_position', 'title' => __("Main Column Alignment", 'lang_theme_core'), 'default' => "right");
				$options_params[] = array('type' => 'number', 'id' => 'content_main_width', 'title' => __("Main Column Width", 'lang_theme_core')." (%)", 'default' => "60");
			}

			if($theme_dir_name == 'mf_theme')
			{
				$options_params[] = array('type' => 'text', 'id' => 'content_bg', 'title' => __("Background", 'lang_theme_core'));
					$options_params[] = array('type' => 'color', 'id' => 'content_bg_color', 'title' => " - ".__("Color", 'lang_theme_core'));
					$options_params[] = array('type' => 'image', 'id' => 'content_bg_image', 'title' => " - ".__("Image", 'lang_theme_core'));
			}

			$options_params[] = array('type' => 'overflow', 'id' => 'content_overflow', 'title' => __("Overflow", 'lang_theme_core'), 'default' => "hidden");
			$options_params[] = array('type' => 'text', 'id' => 'content_padding', 'title' => __("Padding", 'lang_theme_core')); //, 'default' => "30px 0 20px"

		$options_params[] = array('category_end' => "");

		$options_params[] = array('category' => " - ".__("Headings", 'lang_theme_core'), 'id' => 'mf_theme_content_heading');

			if($theme_dir_name == 'mf_theme')
			{
				$options_params[] = array('type' => 'text', 'id' => 'heading_bg', 'title' => __("Background", 'lang_theme_core')." (H1)");
				$options_params[] = array('type' => 'text', 'id' => 'heading_border_bottom', 'title' => __("Border Bottom", 'lang_theme_core')." (H1)");
				$options_params[] = array('type' => 'font', 'id' => 'heading_font', 'title' => __("Font", 'lang_theme_core')." (H1)");
				$options_params[] = array('type' => 'text', 'id' => 'heading_size', 'title' => __("Font Size", 'lang_theme_core')." (H1)", 'default' => "2.4em");
				$options_params[] = array('type' => 'weight', 'id' => 'heading_weight', 'title' => __("Weight", 'lang_theme_core')." (H1)");
				$options_params[] = array('type' => 'color', 'id' => 'heading_color', 'title' => __("Color", 'lang_theme_core')." (H1)");
				$options_params[] = array('type' => 'text', 'id' => 'heading_margin', 'title' => __("Margin", 'lang_theme_core')." (H1)");
				$options_params[] = array('type' => 'text', 'id' => 'heading_padding', 'title' => __("Padding", 'lang_theme_core')." (H1)", 'default' => ".3em 0 .5em");
			}

			/* H2 */
			##################
			$options_params[] = array('type' => 'font', 'id' => 'heading_font_h2', 'title' => __("Font", 'lang_theme_core')." (H2)");

			if($theme_dir_name == 'mf_theme')
			{
				$options_params[] = array('type' => 'text', 'id' => 'heading_size_h2', 'title' => __("Font Size", 'lang_theme_core')." (H2)", 'default' => "1.4em");
			}

			if($theme_dir_name == 'mf_parallax')
			{
				$options_params[] = array('type' => 'text', 'id' => 'heading_font_size_h2', 'title' => __("Font Size", 'lang_theme_core')." (H2)", 'default' => "2em");
			}

			$options_params[] = array('type' => 'weight', 'id' => 'heading_weight_h2', 'title' => __("Weight", 'lang_theme_core')." (H2)");
			$options_params[] = array('type' => 'color', 'id' => 'heading_color_h2', 'title' => __("Color", 'lang_theme_core')." (H2)");
			$options_params[] = array('type' => 'text', 'id' => 'heading_margin_h2', 'title' => __("Margin", 'lang_theme_core')." (H2)", 'default' => "0 0 .5em");
			##################

			/* H3 */
			##################
			if($theme_dir_name == 'mf_theme')
			{
				$options_params[] = array('type' => 'color', 'id' => 'heading_color_h3', 'title' => __("Color", 'lang_theme_core')." (H3)");
				$options_params[] = array('type' => 'font', 'id' => 'heading_font_h3', 'title' => __("Font", 'lang_theme_core')." (H3)");
				$options_params[] = array('type' => 'text', 'id' => 'heading_size_h3', 'title' => __("Font Size", 'lang_theme_core')." (H3)", 'default' => "1.2em");
			}

			if($theme_dir_name == 'mf_parallax')
			{
				$options_params[] = array('type' => 'text', 'id' => 'heading_font_size_h3', 'title' => __("Font Size", 'lang_theme_core')." (H3)");
			}

			$options_params[] = array('type' => 'weight', 'id' => 'heading_weight_h3', 'title' => __("Weight", 'lang_theme_core')." (H3)");
			$options_params[] = array('type' => 'text', 'id' => 'heading_margin_h3', 'title' => __("Margin", 'lang_theme_core')." (H3)");
			##################

			/* H4 */
			##################
			$options_params[] = array('type' => 'text', 'id' => 'heading_font_size_h4', 'title' => __("Font Size", 'lang_theme_core')." (H4)");
			$options_params[] = array('type' => 'weight', 'id' => 'heading_weight_h4', 'title' => __("Weight", 'lang_theme_core')." (H4)");
			$options_params[] = array('type' => 'text', 'id' => 'heading_margin_h4', 'title' => __("Margin", 'lang_theme_core')." (H4)", 'default' => ".5em 0");
			##################

			/* H5 */
			##################
			$options_params[] = array('type' => 'text', 'id' => 'heading_font_size_h5', 'title' => __("Font Size", 'lang_theme_core')." (H5)");
			$options_params[] = array('type' => 'weight', 'id' => 'heading_weight_h5', 'title' => __("Weight", 'lang_theme_core')." (H5)");
			$options_params[] = array('type' => 'text', 'id' => 'heading_margin_h5', 'title' => __("Margin", 'lang_theme_core')." (H5)");
			##################

			if($theme_dir_name == 'mf_parallax')
			{
				$options_params[] = array('type' => 'text', 'id' => 'section_heading_alignment_mobile', 'title' => __("Heading Alignment", 'lang_theme_core')." (".__("Mobile", 'lang_theme_core').")", 'default' => "center");
			}

		$options_params[] = array('category_end' => "");

		$options_params[] = array('category' => " - ".__("Text", 'lang_theme_core'), 'id' => 'mf_theme_content_text');

			if($theme_dir_name == 'mf_theme')
			{
				$options_params[] = array('type' => 'text', 'id' => 'section_bg', 'title' => __("Background", 'lang_theme_core'));
			}

			$options_params[] = array('type' => 'text', 'id' => 'section_size', 'title' => __("Font Size", 'lang_theme_core'), 'default' => "1.6em");
			$options_params[] = array('type' => 'text', 'id' => 'section_size_mobile', 'title' => " - ".__("Mobile", 'lang_theme_core'), 'default' => "1.6em");

			// Range does not display the value the user has chosen...
			//$options_params[] = array('type' => 'text', 'id' => 'section_line_height', 'title' => __("Line Height", 'lang_theme_core'), 'default' => "1.5");
			$options_params[] = array('type' => 'range', 'input_attrs' => array(
				'min' => 1,
				'max' => 5,
				'step' => .1,
				//'class' => '',
				//'style' => 'color: #',
			), 'id' => 'section_line_height', 'title' => __("Line Height", 'lang_theme_core'), 'default' => "1.5");

			$options_params[] = array('type' => 'text', 'id' => 'section_margin', 'title' => __("Margin", 'lang_theme_core'), 'default' => "0 0 2em");

			if($theme_dir_name == 'mf_parallax')
			{
				$options_params[] = array('type' => 'text', 'id' => 'quote_size', 'title' => __("Quote Size", 'lang_theme_core'));
			}

			if($theme_dir_name == 'mf_theme')
			{
				$options_params[] = array('type' => 'text', 'id' => 'section_padding', 'title' => __("Padding", 'lang_theme_core'));
				$options_params[] = array('type' => 'text', 'id' => 'section_margin_between', 'title' => __("Margin between Content", 'lang_theme_core'), 'default' => "1em");
				$options_params[] = array('type' => 'text', 'id' => 'paragraph_drop_cap_size', 'title' => __("Drop Cap Size", 'lang_theme_core'));
				$options_params[] = array('type' => 'text', 'id' => 'paragraph_indentation', 'title' => __("Paragraph Indentation", 'lang_theme_core'));
				$options_params[] = array('type' => 'color', 'id' => 'article_url_color', 'title' => __("Link Color", 'lang_theme_core'));
			}

		$options_params[] = array('category_end' => "");

		if($theme_dir_name == 'mf_parallax')
		{
			$options_params[] = array('category' => __("Aside", 'lang_theme_core'), 'id' => 'mf_parallax_aside');
				$options_params[] = array('type' => 'text', 'id' => 'aside_p', 'title' => __("Paragraph Size", 'lang_theme_core'));
			$options_params[] = array('category_end' => "");
		}

		if($theme_dir_name == 'mf_theme')
		{
			if(is_active_widget_area('widget_after_heading') || is_active_widget_area('widget_sidebar_left') || is_active_widget_area('widget_after_content') || is_active_widget_area('widget_sidebar') || is_active_widget_area('widget_below_content'))
			{
				$options_params[] = array('category' => __("Aside", 'lang_theme_core'), 'id' => 'mf_theme_aside');
					$options_params[] = array('type' => 'text', 'id' => 'aside_sticky_position', 'title' => __("Sticky Position", 'lang_theme_core'));
					$options_params[] = array('type' => 'text', 'id' => 'aside_left_width', 'title' => __("Width", 'lang_theme_core')." (".__("Left", 'lang_theme_core').")", 'default' => "28%");
					$options_params[] = array('type' => 'text', 'id' => 'aside_width', 'title' => __("Width", 'lang_theme_core')." (".__("Right", 'lang_theme_core').")", 'default' => "28%");
					$options_params[] = array('type' => 'text', 'id' => 'aside_container_margin', 'title' => __("Margin", 'lang_theme_core'));
					$options_params[] = array('type' => 'text', 'id' => 'aside_container_padding', 'title' => __("Padding", 'lang_theme_core'));
					$options_params[] = array('type' => 'text', 'id' => 'aside_widget_background', 'title' => __("Widget Background", 'lang_theme_core')); //, 'default' => "#f8f8f8"
					$options_params[] = array('type' => 'text', 'id' => 'aside_widget_border', 'title' => __("Widget Border", 'lang_theme_core')); //, 'default' => "1px solid #d8d8d8"
					$options_params[] = array('type' => 'text', 'id' => 'aside_widget_font_size', 'title' => __("Font Size", 'lang_theme_core'));
					$options_params[] = array('type' => 'text', 'id' => 'aside_heading_bg', 'title' => __("Background", 'lang_theme_core')." (H3)");
					$options_params[] = array('type' => 'text', 'id' => 'aside_heading_border_bottom', 'title' => __("Border Bottom", 'lang_theme_core')." (H3)");
					$options_params[] = array('type' => 'text', 'id' => 'aside_heading_size', 'title' => __("Size", 'lang_theme_core')." (H3)");
					$options_params[] = array('type' => 'text', 'id' => 'aside_heading_padding', 'title' => __("Padding", 'lang_theme_core')." (H3)", 'default' => ".5em");
					$options_params[] = array('type' => 'text', 'id' => 'aside_size', 'title' => __("Size", 'lang_theme_core')." (".__("Content", 'lang_theme_core').")");
					$options_params[] = array('type' => 'text', 'id' => 'aside_line_height', 'title' => __("Line Height", 'lang_theme_core')." (".__("Content", 'lang_theme_core').")");
					$options_params[] = array('type' => 'text', 'id' => 'aside_padding', 'title' => __("Padding", 'lang_theme_core')." (".__("Content", 'lang_theme_core').")", 'default' => ".5em");
					$options_params[] = array('type' => 'text', 'id' => 'aside_margin_between', 'title' => __("Margin between Content", 'lang_theme_core'));
				$options_params[] = array('category_end' => "");
			}

			if(is_active_widget_area('widget_pre_footer'))
			{
				$options_params[] = array('category' => __("Pre Footer", 'lang_theme_core'), 'id' => 'mf_theme_pre_footer');
					$options_params[] = array('type' => 'checkbox', 'id' => 'pre_footer_full_width', 'title' => __("Full Width", 'lang_theme_core'), 'default' => 1);
					$options_params[] = array('type' => 'text', 'id' => 'pre_footer_bg', 'title' => __("Background", 'lang_theme_core'));
						$options_params[] = array('type' => 'color', 'id' => 'pre_footer_bg_color', 'title' => " - ".__("Color", 'lang_theme_core'));
						$options_params[] = array('type' => 'image', 'id' => 'pre_footer_bg_image', 'title' => " - ".__("Image", 'lang_theme_core'));
					$options_params[] = array('type' => 'text', 'id' => 'pre_footer_widget_font_size', 'title' => __("Font Size", 'lang_theme_core'));
					$options_params[] = array('type' => 'text', 'id' => 'pre_footer_padding', 'title' => __("Padding", 'lang_theme_core'));
						$options_params[] = array('type' => 'text', 'id' => 'pre_footer_widget_padding', 'title' => " - ".__("Widget Padding", 'lang_theme_core'), 'default' => "0 0 .5em");
				$options_params[] = array('category_end' => "");
			}
		}

		$options_params[] = array('category' => __("Footer", 'lang_theme_core'), 'id' => 'mf_theme_footer');
			$options_params[] = array('type' => 'checkbox', 'id' => 'footer_full_width', 'title' => __("Full Width", 'lang_theme_core'), 'default' => 1);
			$options_params[] = array('type' => 'position', 'id' => 'footer_fixed', 'title' => __("Position", 'lang_theme_core'), 'default' => 'relative');
			$options_params[] = array('type' => 'text', 'id' => 'footer_bg', 'title' => __("Background", 'lang_theme_core')); //This is used as the default background on body to make the background go all the way down below the footer if present
				$options_params[] = array('type' => 'color', 'id' => 'footer_bg_color', 'title' => " - ".__("Color", 'lang_theme_core')); //, 'ignore_default_if' => 'body_bg', 'default' => '#eeeeee'
				$options_params[] = array('type' => 'image', 'id' => 'footer_bg_image', 'title' => " - ".__("Image", 'lang_theme_core'));

			if(is_active_widget_area('widget_footer'))
			{
				$options_params[] = array('type' => 'font', 'id' => 'footer_font', 'title' => __("Font", 'lang_theme_core'));
				$options_params[] = array('type' => 'text', 'id' => 'footer_font_size', 'title' => __("Font Size", 'lang_theme_core'), 'default' => "1.5em");
				$options_params[] = array('type' => 'color', 'id' => 'footer_color', 'title' => __("Text Color", 'lang_theme_core'));

					if($theme_dir_name == 'mf_theme')
					{
						$options_params[] = array('type' => 'color', 'id' => 'footer_color_hover', 'title' => " - ".__("Text Color", 'lang_theme_core')." (".__("Hover", 'lang_theme_core').")", 'show_if' => 'footer_color');
					}

				if($theme_dir_name == 'mf_parallax')
				{
					$options_params[] = array('type' => 'align', 'id' => 'footer_align', 'title' => __("Alignment", 'lang_theme_core'));
					$options_params[] = array('type' => 'text', 'id' => 'footer_margin', 'title' => __("Margin", 'lang_theme_core'));
				}

				$options_params[] = array('type' => 'text', 'id' => 'footer_padding', 'title' => __("Padding", 'lang_theme_core'));

				if($theme_dir_name == 'mf_theme')
				{
					$options_params[] = array('type' => 'checkbox', 'id' => 'footer_widget_flex', 'title' => __("Widget Flex", 'lang_theme_core'), 'default' => 2);
					$options_params[] = array('type' => 'overflow', 'id' => 'footer_widget_overflow', 'title' => __("Widget Overflow", 'lang_theme_core'), 'default' => 'hidden');
				}

				$options_params[] = array('type' => 'text', 'id' => 'footer_widget_padding', 'title' => __("Widget Padding", 'lang_theme_core'), 'default' => ".2em");

				if($theme_dir_name == 'mf_theme')
				{
					$options_params[] = array('type' => 'text', 'id' => 'footer_widget_heading_margin', 'title' => __("Widget Heading Margin", 'lang_theme_core'), 'default' => "0 0 .5em");
					$options_params[] = array('type' => 'text_transform', 'id' => 'footer_widget_heading_text_transform', 'title' => __("Widget Heading Text Transform", 'lang_theme_core'), 'default' => "uppercase");
					$options_params[] = array('type' => 'text', 'id' => 'footer_p_margin', 'title' => __("Paragraph/List Margin", 'lang_theme_core'), 'default' => "0 0 .5em");
					$options_params[] = array('type' => 'text', 'id' => 'footer_a_bg', 'title' => __("Link Background", 'lang_theme_core'));
					$options_params[] = array('type' => 'text', 'id' => 'footer_a_margin', 'title' => __("Link Margin", 'lang_theme_core'));
					$options_params[] = array('type' => 'text', 'id' => 'footer_a_padding', 'title' => __("Link Padding", 'lang_theme_core'), 'default' => ".4em .6em");
				}
			}

		$options_params[] = array('category_end' => "");

		$options_params[] = array('category' => __("Custom", 'lang_theme_core'), 'id' => 'mf_theme_generic');
			$options_params[] = array('type' => 'textarea', 'id' => 'external_css', 'title' => __("External CSS", 'lang_theme_core'));
			$options_params[] = array('type' => 'textarea', 'id' => 'custom_css_all', 'title' => __("Custom CSS", 'lang_theme_core'));
			$options_params[] = array('type' => 'textarea', 'id' => 'custom_css_mobile', 'title' => __("Custom CSS", 'lang_theme_core')." (".__("Mobile", 'lang_theme_core').")", 'show_if' => 'mobile_breakpoint');
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

		$this->options_fonts['acme'] = array(
			'title' => "Acme",
			'style' => "'Acme', sans-serif",
			'url' => "//fonts.googleapis.com/css2?family=Acme"
		);

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

		$this->options_fonts['dancing_script'] = array(
			'title' => "Dancing Script",
			'style' => "'Dancing Script', cursive",
			'url' => "//fonts.googleapis.com/css2?family=Dancing+Script:wght@600"
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

		$this->options_fonts['indie_flower'] = array(
			'title' => "Indie Flower",
			'style' => "'Indie Flower', cursive",
			'url' => "//fonts.googleapis.com/css2?family=Indie+Flower"
		);

		$this->options_fonts['inter'] = array(
			'title' => "Inter",
			'style' => "'Inter', sans-serif",
			'url' => "//fonts.googleapis.com/css2?family=Inter:wght@100..900"
		);

		$this->options_fonts['kalam'] = array(
			'title' => "Kalam",
			'style' => "'Kalam', cursive",
			'url' => "//fonts.googleapis.com/css2?family=Kalam:wght@700"
		);

		$this->options_fonts['lato'] = array(
			'title' => "Lato",
			'style' => "'Lato', sans-serif",
			'url' => "//fonts.googleapis.com/css?family=Lato"
		);

		$this->options_fonts['lobster'] = array(
			'title' => "Lobster",
			'style' => "'Lobster', cursive",
			'url' => "//fonts.googleapis.com/css2?family=Lobster"
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

		$this->options_fonts['nerko_one'] = array(
			'title' => "Nerko One",
			'style' => "'Nerko One', cursive",
			'url' => "//fonts.googleapis.com/css2?family=Nerko+One"
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

		$this->options_fonts['pacifico'] = array(
			'title' => "Pacifico",
			'style' => "'', cursive",
			'url' => "//fonts.googleapis.com/css2?family=Pacifico"
		);

		$this->options_fonts['patrick_hand'] = array(
			'title' => "Patrick Hand",
			'style' => "'Patrick Hand', cursive",
			'url' => "//fonts.googleapis.com/css2?family=Patrick+Hand"
		);

		$this->options_fonts['playfair_display'] = array(
			'title' => "Playfair Display",
			'style' => "'Playfair Display', serif",
			'url' => "//fonts.googleapis.com/css?family=Playfair+Display"
		);

		$this->options_fonts['poppins'] = array(
			'title' => "Poppins",
			'style' => "'Poppins', serif",
			'url' => "//fonts.googleapis.com/css2?family=Poppins"
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

		$this->options_fonts['roboto_slab'] = array(
			'title' => "Roboto Slab",
			'style' => "'Roboto Slab', sans-serif",
			'url' => "//fonts.googleapis.com/css?family=Roboto+Slab"
		);

		$this->options_fonts['ropa_sans'] = array(
			'title' => "Ropa Sans",
			'style' => "'Ropa Sans', sans-serif",
			'url' => "//fonts.googleapis.com/css2?family=Ropa+Sans&display=swap"
		);

		$this->options_fonts['rouge_script'] = array(
			'title' => "Rouge Script",
			'style' => "'Rouge Script', cursive",
			'url' => "//fonts.googleapis.com/css?family=Rouge+Script"
		);

		$this->options_fonts['rubik_distressed'] = array(
			'title' => "Rubik Distressed",
			'style' => "'Rubik Distressed', cursive",
			'url' => "//fonts.googleapis.com/css2?family=Rubik+Distressed"
		);

		$this->options_fonts['satisfy'] = array(
			'title' => "Satisfy",
			'style' => "'Satisfy', cursive",
			'url' => "//fonts.googleapis.com/css2?family=Satisfy"
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

		$this->options_fonts['titillium_web'] = array(
			'title' => "Titillium Web",
			'style' => "'Titillium Web', sans-serif",
			'url' => "//fonts.googleapis.com/css2?family=Titillium+Web"
		);
	}

	function show_font_face()
	{
		if(count($this->options_fonts) == 0)
		{
			$this->get_theme_fonts();
		}

		$out = "";

		foreach($this->options_params as $arr_param)
		{
			if(isset($arr_param['type']) && $arr_param['type'] == 'font')
			{
				$font_key = $this->options[$arr_param['id']];

				if($font_key != '' && isset($this->options_fonts[$font_key]['file']) && $this->options_fonts[$font_key]['file'] != '')
				{
					$font_file = $this->options_fonts[$font_key]['file'];

					$font_src = "";

					foreach($this->options_fonts[$font_key]['extensions'] as $font_extension)
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
						$out .= "@font-face
						{
							font-family: '".$this->options_fonts[$font_key]['title']."';
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

		.mf_form
		{"
			.$this->render_css(array('property' => 'background-color', 'value' => 'form_container_background_color'))
			.$this->render_css(array('property' => 'border', 'value' => 'form_container_border'))
			.$this->render_css(array('property' => 'border-radius', 'value' => 'form_container_border_radius'))
			.$this->render_css(array('property' => 'padding', 'value' => 'form_container_padding'))
		."}

			.mf_form_field, #comments #comment
			{"
				.$this->render_css(array('property' => 'border-radius', 'value' => 'form_border_radius'))
			."}";

			if(isset($this->options['form_join_fields']) && $this->options['form_join_fields'] == 2)
			{
				$out .= ".is_desktop .flex_flow > div:not(:last-of-type), .is_tablet .flex_flow > div:not(:last-of-type)
				{
					margin-right: 0;
				}

				.is_desktop .flex_flow > div:not(:last-of-type) > .mf_form_field, .is_tablet .flex_flow > div:not(:last-of-type) > .mf_form_field
				{
					border-right: 0;
					border-top-right-radius: 0;
					border-bottom-right-radius: 0;
				}

				.is_desktop .flex_flow > div:not(:first-of-type) > .mf_form_field, .is_tablet .flex_flow > div:not(:first-of-type) > .mf_form_field
				{
					border-top-left-radius: 0;
					border-bottom-left-radius: 0;
				}";
			}

			$out .= ".form_button button, .form_button .button, #comments #submit
			{"
				.$this->render_css(array('property' => 'border-radius', 'value' => 'form_button_border_radius'))
				.$this->render_css(array('property' => 'font-size', 'value' => 'button_size'))
				.$this->render_css(array('property' => 'padding', 'value' => 'form_button_padding'))
			."}

			#wrapper .mf_form button, #wrapper .button, .color_button, #wrapper .mf_form .button-primary, #comments #submit
			{"
				.$this->render_css(array('property' => 'background', 'value' => array('button_color', 'nav_color_hover')));

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

			.form_button .color_button_border
			{"
				.$this->render_css(array('property' => 'border-color', 'value' => array('button_color', 'nav_color_hover')))
				."border-style: solid;
				border-width: .1em;"
				.$this->render_css(array('property' => 'color', 'value' => array('button_color', 'nav_color_hover')))
			."}

				.form_button .color_button_border:hover
				{"
					.$this->render_css(array('property' => 'background', 'value' => array('button_color', 'nav_color_hover')))
					."color: #fff;
				}

				.color_text
				{"
					.$this->render_css(array('property' => 'color', 'value' => 'button_color'))
				."}

			#wrapper .button-secondary, .color_button_2
			{"
				.$this->render_css(array('property' => 'background', 'value' => 'button_color_secondary', 'suffix' => " !important"));

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

				if(isset($this->options['button_color_negative']) && $this->options['button_color_negative'] != '')
				{
					if(!isset($obj_base))
					{
						$obj_base = new mf_base();
					}

					$out .= "color: ".$obj_base->get_text_color_from_background($this->options['button_color_negative'])." !important";
				}

			$out .= "}

				#wrapper .mf_form button:hover, #wrapper .button:hover, .color_button:hover, #wrapper .mf_form .button-primary:hover, #comments #submit:hover, #wrapper .button-secondary:hover, .color_button_2:hover, .color_button_negative:hover
				{
					box-shadow: inset 0 0 10em rgba(0, 0, 0, .2);
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

				#mf-pre-header > div, header > div, #mf-after-header > div, #mf-pre-content > div, #mf-content > div, #mf-pre-footer > div, footer > div, .full_width > div > .widget .section, .full_width > div > .widget > div
				{"
					.$this->render_css(array('property' => 'padding', 'value' => 'main_padding'))
					."position: relative;
				}

				#wrapper
				{"
					.$this->render_css(array('property' => 'background', 'value' => 'body_bg'))
					.$this->render_css(array('property' => 'background-color', 'value' => 'body_bg_color'))
					.$this->render_css(array('property' => 'background-image', 'prefix' => 'url(', 'value' => 'body_bg_image', 'suffix' => '); background-size: cover'));
					//."min-height: 100vh;" /* This will override footer background below footer */

					if(!isset($this->options['header_fixed']) || $this->options['header_fixed'] != 'sticky')
					{
						$out .= "overflow: hidden;";
					}

				$out .= "}

					header
					{"
						.$this->render_css(array('property' => 'background', 'value' => 'header_bg'))
						.$this->render_css(array('property' => 'background-color', 'value' => 'header_bg_color'))
						.$this->render_css(array('property' => 'background-image', 'prefix' => 'url(', 'value' => 'header_bg_image', 'suffix' => '); background-size: cover'))
						.$this->render_css(array('property' => 'overflow', 'value' => 'header_overflow'))
						.$this->render_css(array('property' => 'position', 'value' => 'header_fixed'));

						if(isset($this->options['header_fixed']) && $this->options['header_fixed'] == 'sticky')
						{
							$out .= "box-shadow: 0 .1em 3em rgba(0, 0, 0, .1);";
						}

					$out .= "}

						header > div
						{"
							.$this->render_css(array('property' => 'padding', 'value' => 'header_padding'))
						."}

							#site_logo.has_logo_hover:hover .desktop_logo
							{
								display: none;
							}

						#site_logo.has_logo_hover .desktop_logo_hover
						{
							display: none;
						}

							#site_logo.has_logo_hover:hover .desktop_logo_hover
							{
								display: block;
							}

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

							.mf_form.searchform .form_textfield input
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
								.$this->render_css(array('property' => 'background', 'value' => 'slide_nav_bg_full'))
								.$this->render_css(array('property' => 'background-color', 'value' => 'slide_nav_bg'))
								."bottom: 0;"
								.$this->render_css(array('property' => 'color', 'value' => 'slide_nav_color'))
								.$this->render_css(array('property' => 'font-family', 'value' => 'nav_font'))
								."overflow: hidden;
								padding: 6em 0 1em;
								position: absolute;
								top: 0;"
								/*."width: 90%;
								max-width: 300px;"*/
								//.$this->render_css(array('property' => 'width', 'value' => 'slide_nav_width'))
								//.$this->render_css(array('property' => 'max-width', 'value' => 'slide_nav_max_width'))
								."width: 100%;
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
									padding: 3% 4%;
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
										.$this->render_css(array('property' => 'font-weight', 'value' => 'nav_font_weight'))
									."}

										#mf-slide-nav .theme_nav ul a
										{"
											.$this->render_css(array('property' => 'color', 'value' => 'slide_nav_color'))
											."display: block;"
											.$this->render_css(array('property' => 'letter-spacing', 'value' => 'slide_nav_letter_spacing'))
											."overflow: hidden;"
											.$this->render_css(array('property' => 'padding', 'value' => 'slide_nav_link_padding'))
											."text-overflow: ellipsis;
											transition: all .4s ease;
											white-space: nowrap;
										}

											#mf-slide-nav .theme_nav ul a:hover
											{"
												.$this->render_css(array('property' => 'background', 'value' => 'slide_nav_bg_hover'))
												.$this->render_css(array('property' => 'color', 'value' => 'slide_nav_color_hover'))
												.$this->render_css(array('property' => 'text-indent', 'value' => 'slide_nav_hover_indent'))
											."}

											#wrapper #mf-slide-nav .theme_nav li.current_page_item > a
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
											.$this->render_css(array('property' => 'font-size', 'value' => 'slide_nav_sub_font_size'))
											.$this->render_css(array('property' => 'font-weight', 'value' => 'slide_nav_sub_font_weight'))
											.$this->render_css(array('property' => 'text-indent', 'value' => 'slide_nav_sub_indent'))
										."}

											#mf-slide-nav .theme_nav li ul a:hover
											{"
												.$this->render_css(array('property' => 'background', 'value' => 'slide_nav_sub_bg_hover'))
												.$this->render_css(array('property' => 'text-indent', 'value' => 'slide_nav_sub_hover_indent'))
											."}";
					}

			$out .= ".aside ul a:hover, .aside ol a:hover
			{"
				.$this->render_css(array('property' => 'color', 'value' => 'body_link_color'))
			."}";

		return $out;
	}

	function render_css($data)
	{
		$property = (isset($data['property']) ? $data['property'] : '');
		$prefix = (isset($data['prefix']) ? $data['prefix'] : '');
		$suffix = (isset($data['suffix']) ? $data['suffix'] : '');
		$value = (isset($data['value']) ? $data['value'] : '');

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
		if($this->get_allow_cookies())
		{
			$this->get_theme_fonts();
			$this->get_params();

			foreach($this->options_params as $arr_param)
			{
				if(isset($arr_param['type']) && $arr_param['type'] == 'font' && isset($this->options[$arr_param['id']]))
				{
					$font_key = $this->options[$arr_param['id']];

					if(isset($this->options_fonts[$font_key]['url']) && $this->options_fonts[$font_key]['url'] != '')
					{
						mf_enqueue_style('style_font_'.$font_key, $this->options_fonts[$font_key]['url']);
					}
				}
			}
		}

		else
		{
			$plugin_include_url = plugin_dir_url(__FILE__);
			$plugin_version = get_plugin_version(__FILE__);

			mf_enqueue_script('script_theme_core_enqueue_theme_fonts', $plugin_include_url."script_enqueue_theme_fonts.php", $plugin_version);
		}
	}

	function get_external_css($theme_version)
	{
		if(isset($this->options['external_css']) && $this->options['external_css'] != '')
		{
			$arr_roles_check = array(
				'is_super_admin' => IS_SUPER_ADMIN,
				'is_admin' => IS_ADMINISTRATOR,
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
				if(isset($arr_custom['widget_area_id']) && $arr_custom['widget_area_id'] != '')
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
			if($this->has_noindex($post_id) || $this->is_post_password_protected($post_id))
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

		$header_logo = (isset($this->options['header_logo']) ? $this->options['header_logo'] : '');
		$header_logo_hover = (isset($this->options['header_logo_hover']) ? $this->options['header_logo_hover'] : '');
		$header_mobile_logo = (isset($this->options['header_mobile_logo']) ? $this->options['header_mobile_logo'] : '');
		$header_mobile_logo_hover = (isset($this->options['header_mobile_logo_hover']) ? $this->options['header_mobile_logo_hover'] : '');

		$has_logo = ($data['image'] != '' || $header_logo != '' || $header_mobile_logo != '');
		$has_logo_hover = ($header_logo_hover != '' || $header_mobile_logo_hover != '');

		$out = "<a href='".trim($data['url'], '/')."/' id='site_logo'".($has_logo_hover ? " class='has_logo_hover'" : "").">";

			if($has_logo && $data['title'] == '')
			{
				if($data['display'] != 'tagline')
				{
					$site_title = get_bloginfo('name');
					$site_description = get_bloginfo('description');

					if($data['image'] != '')
					{
						$out .= "<img src='".$data['image']."' alt='".sprintf(__("Logo for %s", 'lang_theme_core'), $site_title.($site_description != '' ? " | ".$site_description : ''))."'>";
					}

					else
					{
						if($header_logo != '')
						{
							$out .= "<img src='".$header_logo."' class='desktop_logo".($header_mobile_logo != '' ? " hide_if_mobile" : "")."' alt='".sprintf(__("Logo for %s", 'lang_theme_core'), $site_title.($site_description != '' ? " | ".$site_description : ''))."'>";

							if($header_logo_hover != '')
							{
								$out .= "<img src='".$header_logo_hover."' class='desktop_logo_hover".($header_mobile_logo != '' ? " hide_if_mobile" : "")."' alt='".sprintf(__("Logo for %s", 'lang_theme_core'), $site_title.($site_description != '' ? " | ".$site_description : ''))."'>";
							}
						}

						if($header_mobile_logo != '')
						{
							$out .= "<img src='".$header_mobile_logo."' class='mobile_logo".($header_logo != '' ? " show_if_mobile" : "")."' alt='".sprintf(__("Mobile Logo for %s", 'lang_theme_core'), $site_title.($site_description != '' ? " | ".$site_description : ''))."'>";

							if($header_mobile_logo_hover != '')
							{
								$out .= "<img src='".$header_mobile_logo_hover."' class='mobile_logo_hover".($header_logo != '' ? " show_if_mobile" : "")."' alt='".sprintf(__("Mobile Logo for %s", 'lang_theme_core'), $site_title.($site_description != '' ? " | ".$site_description : ''))."'>";
							}
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
					$logo_title = ($data['title'] != '' ? $data['title'] : get_bloginfo('name'));

					$out .= "<div>".apply_filters('filter_logo_title', $logo_title)."</div>";
				}

				if($data['display'] != 'title')
				{
					$logo_description = ($data['description'] != '' ? $data['description'] : get_bloginfo('description'));

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
		if(!isset($data['placeholder']) || $data['placeholder'] == ''){			$data['placeholder'] = __("Search for", 'lang_theme_core');}
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
			$post->post_title .= " (".__("copy", 'lang_theme_core').")";
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
			'ping_status' => $post->ping_status,
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
				}

				else
				{
					wp_die(__("Error cloning post", 'lang_theme_core'));
				}
			}
		}
	}

	function row_actions($actions, $post)
	{
		if(IS_EDITOR && $post->post_status == 'publish')
		{
			$actions['clone'] = "<a href='".admin_url("edit.php?post_type=".$post->post_type."&btnPostClone&post_id=".$post->ID)."'>".__("Clone", 'lang_theme_core')."</a>";
		}

		return $actions;
	}

	function column_header($cols)
	{
		unset($cols['comments']);

		if(check_var('post_status') != 'trash') //$this->is_site_public() && 
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

				$seo_type = apply_filters('filter_theme_core_seo_type', $seo_type);

				if($seo_type == '' && $post->post_status != 'publish')
				{
					$seo_type = 'not_published';
				}

				if($seo_type == '')
				{
					$page_index = get_post_meta($id, $this->meta_prefix.'page_index', true);

					if(in_array($page_index, array('noindex', 'none')))
					{
						$seo_type = 'not_indexed';
					}
				}

				if($seo_type == '' && $this->is_post_password_protected($id))
				{
					$seo_type = 'password_protected';
				}

				if($seo_type == '')
				{
					if($post->post_excerpt != '')
					{
						$post_id_duplicate = $wpdb->get_var($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." WHERE post_excerpt = %s AND post_status = %s AND post_type = %s AND ID != '%d' LIMIT 0, 1", $post->post_excerpt, 'publish', $post->post_type, $id));

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
						$post_id_duplicate = $wpdb->get_var($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." WHERE post_title = %s AND post_status = %s AND post_type = %s AND ID != '%d' LIMIT 0, 1", $post->post_title, 'publish', $post->post_type, $id));

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

				if($seo_type == '' && $post->post_name != '' && sanitize_title_with_dashes(sanitize_title($post->post_title)) != $post->post_name)
				{
					$seo_type = 'inconsistent_url';
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

			return in_array(get_post_type($post_id), $obj_base->get_post_types_for_metabox(array('exclude_from_search' => false)));
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
		if(IS_ADMINISTRATOR)
		{
			$post_id = check_var('post');

			$arr_fields = array();

			if($this->check_if_correct_post_type($post_id)) //$this->is_site_public() && 
			{
				$arr_fields[] = array(
					'name' => __("Index", 'lang_theme_core'),
					'id' => $this->meta_prefix.'page_index',
					'type' => 'select',
					'options' => array(
						'' => "-- ".__("Choose Here", 'lang_theme_core')." --",
						'noindex' => __("Do not Index", 'lang_theme_core'),
						'nofollow' => __("Do not Follow Links", 'lang_theme_core'),
						'none' => __("Do not Index and do not follow links", 'lang_theme_core'),
					),
				);
			}

			list($is_published, $is_not_published) = $this->check_if_published($post_id);

			if($is_not_published)
			{
				$arr_fields[] = array(
					'name' => __("Publish", 'lang_theme_core'),
					'id' => $this->meta_prefix.'publish_date',
					'type' => 'datetime',
				);
			}

			if($is_published)
			{
				$arr_fields[] = array(
					'name' => __("Unpublish", 'lang_theme_core'),
					'id' => $this->meta_prefix.'unpublish_date',
					'type' => 'datetime',
				);
			}

			if(count($arr_fields) > 0)
			{
				$obj_base = new mf_base();

				$meta_boxes[] = array(
					'id' => $this->meta_prefix.'publish',
					'title' => __("Publish Settings", 'lang_theme_core'),
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

			$mail_subject = sprintf(__("The draft (%s) has been saved", 'lang_theme_core'), $post_title);
			$mail_content = sprintf(__("The draft (%s) has been saved and might be ready for publishing", 'lang_theme_core'), "<a href='".$post_url."'>".$post_title."</a>");

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

	function after_setup_theme()
	{
		add_post_type_support('page', 'excerpt');

		if(apply_filters('filter_move_scripts_to_footer', true) == true)
		{
			remove_action('wp_head', 'wp_print_scripts');
			remove_action('wp_head', 'wp_print_head_scripts', 9);
			remove_action('wp_head', 'wp_enqueue_scripts', 1);
			add_action('wp_footer', 'wp_print_scripts', 5);
			add_action('wp_footer', 'wp_enqueue_scripts', 5);
			add_action('wp_footer', 'wp_print_head_scripts', 5);
		}
	}

	function recommend_config($data)
	{
		global $obj_base;

		if(!isset($data['file'])){		$data['file'] = '';}

		$update_with = "";

		/* Use instead of template_redirect -> is_author()? */
		/*switch($obj_base->get_server_type())
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
					."	RewriteEngine On\r\n"
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

	function get_allow_cookies()
	{
		return (get_option('setting_cookie_deactivate_until_allowed') != 'yes');
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
			'' => "-- ".__("Choose Here", 'lang_theme_core')." --",
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

		mf_enqueue_style('style_theme_core_customizer', $plugin_include_url."style_customizer.php", $plugin_version);
		mf_enqueue_script('script_theme_core_enqueue_theme_fonts', $plugin_include_url."script_enqueue_theme_fonts.php", $plugin_version);
		mf_enqueue_script('script_theme_core_customizer', $plugin_include_url."script_customizer.js", $plugin_version);

		$this->get_params();
		$this->get_theme_fonts();

		//$this->id_temp = "";
		//$this->param = array();

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
						case 'range':
						case 'text':
						case 'textarea':
						case 'url':
							$wp_customize->add_control(
								$this->param['id'],
								array(
									'label' => $this->param['title'],
									'section' => $this->id_temp,
									'type' => $this->param['type'],
									'input_attrs' => $this->param['input_attrs'],
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
								'lighter' => __("Lighter than parent element", 'lang_theme_core'),
								'100' => "100",
								'200' => "200",
								'300' => "300",
								'normal' => __("Regular", 'lang_theme_core')." (400)",
								'500' => "500",
								'600' => "600",
								'bold' => __("Bold", 'lang_theme_core')." (700)",
								'800' => "800",
								'900' => "900",
								'bolder' => __("Bolder than parent element", 'lang_theme_core'),
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

	#################################
	function copy_file()
	{
		if(file_exists($this->file_dir_to))
		{
			if(get_option('option_uploads_fixed') < date("Y-m-d", strtotime("-1 month")))
			{
				if(file_exists($this->file_dir_from)) // && is_file($this->file_dir_from)
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
				mkdir(dirname($this->file_dir_to), 0755, true);

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

		$result = $wpdb->get_results($wpdb->prepare("SELECT ID, meta_key, meta_value FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id WHERE (meta_key = %s OR meta_key = %s) AND meta_value > %s", $this->meta_prefix.'publish_date', $this->meta_prefix.'unpublish_date', DEFAULT_DATE));

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

				$url_clean = remove_protocol(array('url' => $url, 'clean' => true, 'trim' => true));

				$log_message_2 = sprintf("The style response from %s had an error", $url_clean);

				switch($headers['http_code'])
				{
					case 200:
						$arr_json = json_decode($content, true);

						$log_message_3 = sprintf("The feed from %s returned an error (%s)", $url_clean, $content);

						if(isset($arr_json['success']) && $arr_json['success'] == true)
						{
							$theme_name = $arr_json['response']['theme_name'];
							$style_changed = $arr_json['response']['style_changed'];
							$style_url = $arr_json['response']['style_url'];

							if($style_changed > get_option('option_theme_saved') && $theme_name == $this->get_theme_dir_name(array('type' => 'child')))
							{
								update_option('option_theme_source_style_url', $style_url, 'no');
							}

							else
							{
								delete_option('option_theme_source_style_url');
							}

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

		$out = false;

		if(does_table_exist($wpdb->comments))
		{
			$wpdb->get_results($wpdb->prepare("SELECT comment_ID FROM ".$wpdb->comments." WHERE comment_approved NOT IN('spam', 'trash') AND comment_type = %s LIMIT 0, 1", 'comment'));

			$out = ($wpdb->num_rows > 0);
		}

		return $out;
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
		$strFileContent = (isset($_REQUEST['strFileContent']) ? $_REQUEST['strFileContent'] : '');

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
				list($strFileContent, $headers) = get_url_content(array('url' => $strFileUrl, 'catch_head' => true));

				switch($headers['http_code'])
				{
					case 503:
						$strFileContent = "";

						$error_text = __("The file does not exist anymore. It might be out of date.", 'lang_theme_core');
					break;
				}
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
				$arr_json = json_decode($strFileContent, true);

				if(is_array($arr_json))
				{
					$setting_theme_ignore_style_on_restore = get_option('setting_theme_ignore_style_on_restore');

					if(!is_array($setting_theme_ignore_style_on_restore))
					{
						$setting_theme_ignore_style_on_restore = array_map('trim', explode(",", $setting_theme_ignore_style_on_restore));
					}

					foreach($arr_json as $key => $value)
					{
						if(!in_array($key, $setting_theme_ignore_style_on_restore))
						{
							set_theme_mod($key, $value);
						}
					}

					$done_text = __("I restored the theme backup for you", 'lang_theme_core');

					update_option('option_theme_saved', date("Y-m-d H:i:s"), 'no');
					delete_option('option_theme_source_style_url');

					$strFileContent = "";
				}

				else
				{
					$error_text = __("There is something wrong with the source to restore", 'lang_theme_core')." (".htmlspecialchars($strFileContent)." -> ".var_export($arr_json, true).")";
				}
			}
		}

		else if(isset($_GET['btnThemeDelete']) && wp_verify_nonce($_GET['_wpnonce_theme_delete'], 'theme_delete_'.$strFileName))
		{
			if(file_exists($upload_path.$strFileName))
			{
				if(unlink($upload_path.$strFileName))
				{
					$done_text = __("The file was deleted successfully", 'lang_theme_core');
				}

				else
				{
					$error_text = __("The file could not be deleted", 'lang_theme_core')." (".$upload_path.$strFileName.")";
				}
			}

			else
			{
				$error_text = __("The file could not be deleted because it was not found", 'lang_theme_core')." (".$upload_path.$strFileName.")";
			}
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
					$error_text = sprintf(__("The theme at %s has got a newer version of saved style which can be %srestored here%s", 'lang_theme_core'), $setting_base_template_site, "<a href='".admin_url("themes.php?page=theme_options&btnThemeRestore&strFileUrl=".$option_theme_source_style_url)."'>", "</a>");
				}
			}
		}

		$out .= "<div class='wrap'>
			<h2>".__("Theme Backup", 'lang_theme_core')."</h2>"
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

									$arr_header[] = __("Existing", 'lang_theme_core');
									$arr_header[] = __("Date", 'lang_theme_core');

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
														<a href='".$upload_url.$file_name."'>".__("Download", 'lang_theme_core')."</a>
														 | <a href='".admin_url("themes.php?page=theme_options&btnThemeRestore&strFileName=".$file_name)."' rel='confirm'>".__("Restore", 'lang_theme_core')."</a>";

														if($is_allowed_to_backup)
														{
															$out .= " | <a href='".wp_nonce_url(admin_url("themes.php?page=theme_options&btnThemeDelete&strFileName=".$file_name), 'theme_delete_'.$file_name, '_wpnonce_theme_delete')."' rel='confirm'>".__("Delete", 'lang_theme_core')."</a>";
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
											.show_button(array('name' => 'btnThemeRestore', 'text' => __("Restore", 'lang_theme_core')))
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
											.show_button(array('name' => 'btnThemeBackup', 'text' => __("Save", 'lang_theme_core')))
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
		if($this->is_theme_active())
		{
			$menu_title = __("Theme Backup", 'lang_theme_core');
			add_theme_page($menu_title, $menu_title.$this->get_theme_updates_message(), 'edit_theme_options', 'theme_options', array($this, 'get_options_page'));
		}

		if($this->has_comments() == false)
		{
			remove_menu_page("edit-comments.php");

			/*if(get_option('default_comment_status') == 'closed')
			{
				remove_submenu_page("options-general.php", "options-discussion.php");
			}*/
		}

		if($this->is_theme_active())
		{
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

						$menu_title = sprintf(__("New '%s'", 'lang_theme_core'), shorten_text(array('string' => $post_title, 'limit' => 15)));
						add_submenu_page("edit.php?post_type=page", $menu_title, " - ".$menu_title, $menu_capability, $menu_slug);
					}
				}
			}
		}
	}

	function filter_sites_table_settings($arr_settings)
	{
		$arr_settings['settings_theme_core'] = array(
			'setting_no_public_pages' => array(
				'type' => 'bool',
				'global' => false,
				'icon' => "fas fa-lock",
				'name' => __("Theme", 'lang_theme_core')." - ".__("Always redirect visitors to the login page", 'lang_theme_core'),
			),
			'setting_theme_core_login' => array(
				'type' => 'bool',
				'global' => false,
				'icon' => "fas fa-user-lock",
				'name' => __("Theme", 'lang_theme_core')." - ".__("Require login for public site", 'lang_theme_core'),
			),
			'setting_theme_enable_wp_api' => array(
				'type' => 'bool',
				'global' => true,
				'icon' => "fas fa-network-wired",
				'name' => __("Theme", 'lang_theme_core')." - ".__("Enable XML-RPC", 'lang_theme_core'),
			),
		);

		$arr_settings['settings_theme_core_cookies'] = array(
			'setting_cookie_info' => array(
				'type' => 'post',
				'global' => false,
				'icon' => "fas fa-cookie",
				'name' => __("Theme", 'lang_theme_core')." - ".__("Information Page", 'lang_theme_core'),
			),
			'setting_cookie_deactivate_until_allowed' => array(
				'type' => 'bool',
				'global' => false,
				'icon' => "fas fa-cookie-bite",
				'name' => __("Theme", 'lang_theme_core')." - ".__("Deactivate Until Allowed", 'lang_theme_core'),
			),
		);

		$arr_settings['settings_theme_core_public'] = array(
			'default_comment_status' => array(
				'type' => 'open',
				'global' => false,
				'icon' => "fas fa-comments",
				'name' => __("Theme", 'lang_theme_core')." - ".__("Allow Comments", 'lang_theme_core'),
			),
			'setting_404_page' => array(
				'type' => 'post',
				'global' => false,
				'icon' => "fas fa-exclamation-circle",
				'name' => __("Theme", 'lang_theme_core')." - ".__("404 Page", 'lang_theme_core'),
			),
			'setting_maintenance_page' => array(
				'type' => 'post',
				'global' => false,
				'icon' => "fas fa-hard-hat",
				'name' => __("Theme", 'lang_theme_core')." - ".__("Maintenance Page", 'lang_theme_core'),
			),
			'setting_activate_maintenance' => array(
				'type' => 'bool',
				'global' => false,
				'icon' => "fas fa-tools",
				'name' => __("Theme", 'lang_theme_core')." - ".__("Activate Maintenance Mode", 'lang_theme_core'),
			),
		);

		return $arr_settings;
	}

	function sites_column_header($cols)
	{
		unset($cols['registered']);
		unset($cols['lastupdated']);

		$cols['site_status'] = __("Status", 'lang_theme_core');
		$cols['theme'] = __("Theme", 'lang_theme_core');
		$cols['email'] = __("E-mail", 'lang_theme_core');
		$cols['last_updated'] = __("Updated", 'lang_theme_core');

		return $cols;
	}

	function sites_column_cell($col, $id)
	{
		global $wpdb, $obj_base;

		if(get_blog_status($id, 'deleted') == 0 && get_blog_status($id, 'archived') == 0)
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

					$arr_site_status = $this->get_site_status_data(array('type' => 'sites_column'));

					echo "<i class='".$arr_site_status['icon']." fa-2x ".$arr_site_status['color']."' title='".$arr_site_status['text']."'></i>";
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
								$restore_notice .= "&nbsp;<i class='fas fa-star fa-lg yellow' title='".__("This is the template theme design", 'lang_theme_core')."'></i>";
							}

							else
							{
								$option_theme_source_style_url = get_blog_option($id, 'option_theme_source_style_url');

								if($option_theme_source_style_url != '')
								{
									$restore_notice = "&nbsp;<span class='update-plugins' title='".__("Theme Updates", 'lang_theme_core')."'>
										<span>1</span>
									</span>";
									$restore_url = " | <a href='".get_admin_url($id, "themes.php?page=theme_options")."'>".__("Update", 'lang_theme_core')."</a>";
								}

								else
								{
									$option_theme_saved = get_blog_option($id, 'option_theme_saved');

									$restore_notice .= "&nbsp;<i class='fa fa-check fa-lg ".($option_theme_saved > date("Y-m-d H:i:s", strtotime("-1 month")) ? "green" : "grey")."' title='".__("The theme design is up to date", 'lang_theme_core')."'></i>";
								}
							}
						}

						else
						{
							$option_sync_sites = get_option('option_sync_sites', array());

							if(count($option_sync_sites) > 0)
							{
								$restore_notice .= "&nbsp;<i class='fas fa-star fa-lg yellow' title='".__("This is the template theme design", 'lang_theme_core')."'></i>";
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
					.$restore_notice;

					if(is_plugin_active("mf_site_manager/index.php"))
					{
						echo "<div class='row-actions'>"
							."<a href='".get_admin_url($id, "admin.php?page=mf_site_manager/theme/index.php")."'>".__("Change", 'lang_theme_core')."</a>"
							.$restore_url
						."</div>";
					}
				break;

				case 'email':
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
					$last_updated_manual_post_types = array_diff($arr_post_types, apply_filters('filter_last_updated_post_types', array(), 'manual'));

					$result = $wpdb->get_results("SELECT ID, post_title, post_modified FROM ".$wpdb->posts." WHERE post_type IN ('".implode("','", $last_updated_manual_post_types)."') AND post_status != 'auto-draft' ORDER BY post_modified DESC LIMIT 0, 1");

					foreach($result as $r)
					{
						$post_id_manual = $r->ID;
						$post_title = ($r->post_title != '' ? $r->post_title : "(".__("unknown", 'lang_theme_core').")");
						$post_modified_manual = $r->post_modified;

						if($post_modified_manual > DEFAULT_DATE)
						{
							$row_actions = "";

							echo format_date($post_modified_manual);

							$row_actions .= ($row_actions != '' ? " | " : "")."<a href='".admin_url("post.php?action=edit&post=".$post_id_manual)."'>".shorten_text(array('string' => get_post_title($post_id_manual), 'limit' => 10))."</a>";

							$last_updated_automatic_post_types = array_diff($arr_post_types, apply_filters('filter_last_updated_post_types', array('post', 'page'), 'auto'));

							$result_auto = $wpdb->get_results("SELECT ID, post_title, post_modified FROM ".$wpdb->posts." WHERE post_type IN ('".implode("','", $last_updated_automatic_post_types)."') ORDER BY post_modified DESC LIMIT 0, 1");

							foreach($result_auto as $r)
							{
								$post_id_auto = $r->ID;
								$post_title = ($r->post_title != '' ? $r->post_title : "(".__("unknown", 'lang_theme_core').")");
								$post_modified_auto = $r->post_modified;

								if($post_modified_auto > $post_modified_manual)
								{
									$row_actions .= ($row_actions != '' ? " | " : "").__("Background", 'lang_theme_core').": ".format_date($post_modified_auto)." (<a href='".admin_url("post.php?action=edit&post=".$post_id_auto)."'>".shorten_text(array('string' => $post_title, 'limit' => 10))."</a>)";
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

				$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->postmeta." WHERE meta_id = '%d'", $intMetaID));
			}
		}

		//Remove duplicate usermeta
		$result = $wpdb->get_results("SELECT umeta_id, COUNT(umeta_id) AS count FROM ".$wpdb->usermeta." GROUP BY user_id, meta_key, meta_value HAVING count > 1");

		if($wpdb->num_rows > 0)
		{
			foreach($result as $r)
			{
				$intMetaID = $r->umeta_id;

				$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->usermeta." WHERE umeta_id = '%d'", $intMetaID));
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
		get_file_info(array('path' => $upload_path, 'folder_callback' => array($this, 'delete_empty_folder_callback')));

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

class widget_theme_core_area extends WP_Widget
{
	var $obj_theme_core = "";

	var $widget_ops = array();

	var $arr_default = array(
		'widget_area_id' => '',
		'widget_area_name' => '',
		'widget_area_class' => "",
		'widget_area_columns' => 1,
		'widget_area_padding' => '',
	);

	function __construct()
	{
		$this->obj_theme_core = new mf_theme_core();

		$this->widget_ops = array(
			'classname' => 'theme_widget_area',
			'description' => __("Add Widget Area", 'lang_theme_core'),
		);

		/*$this->arr_default = array(
			'widget_area_id' => '',
			'widget_area_name' => '',
			'widget_area_class' => "",
			'widget_area_columns' => 1,
			'widget_area_padding' => '',
		);*/

		parent::__construct(str_replace("_", "-", $this->widget_ops['classname']).'-widget', __("Widget Area", 'lang_theme_core'), $this->widget_ops);
	}

	function widget($args, $instance)
	{
		extract($args);
		$instance = wp_parse_args((array)$instance, $this->arr_default);

		if(is_active_sidebar('widget_area_'.$instance['widget_area_id']))
		{
			echo apply_filters('filter_before_widget', $before_widget)
				."<div id='widget_area_".str_replace("-", "_", $instance['widget_area_id'])."' class='widget_columns columns_".$instance['widget_area_columns'].($instance['widget_area_class'] != '' ? " ".$instance['widget_area_class'] : "")."'>";

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
		$instance['widget_area_class'] = sanitize_text_field($new_instance['widget_area_class']);
		$instance['widget_area_columns'] = sanitize_text_field($new_instance['widget_area_columns']);
		$instance['widget_area_padding'] = sanitize_text_field($new_instance['widget_area_padding']);

		return $instance;
	}

	function form($instance)
	{
		$instance = wp_parse_args((array)$instance, $this->arr_default);

		echo "<div class='mf_form'>"
			.show_textfield(array('name' => $this->get_field_name('widget_area_id'), 'text' => __("ID (Has to be unique)", 'lang_theme_core'), 'value' => $instance['widget_area_id'], 'required' => true, 'xtra' => ($instance['widget_area_id'] != '' ? "readonly" : "")))
			.show_textfield(array('name' => $this->get_field_name('widget_area_name'), 'text' => __("Name", 'lang_theme_core'), 'value' => $instance['widget_area_name'], 'required' => true))
			.show_textfield(array('name' => $this->get_field_name('widget_area_class'), 'text' => __("Classes", 'lang_theme_core'), 'value' => $instance['widget_area_class'], 'placeholder' => "bold italic aligncenter alignleft alignright flex_flow"))
			.show_textfield(array('type' => 'number', 'name' => $this->get_field_name('widget_area_columns'), 'text' => __("Columns", 'lang_theme_core'), 'value' => $instance['widget_area_columns'], 'xtra' => "min='1' max='6'"));

			if($instance['widget_area_columns'] > 1)
			{
				echo show_textfield(array('name' => $this->get_field_name('widget_area_padding'), 'text' => __("Column Space", 'lang_theme_core'), 'value' => $instance['widget_area_padding'], 'placeholder' => ".5em"));
			}

		echo "</div>";
	}
}

class widget_theme_core_logo extends WP_Widget
{
	var $obj_theme_core = "";

	var $widget_ops = array();

	var $arr_default = array(
		'logo_url' => '',
		'logo_display' => 'all',
		'logo_title' => '',
		'logo_image' => '',
		'logo_description' => '',
	);

	function __construct()
	{
		$this->obj_theme_core = new mf_theme_core();

		$this->widget_ops = array(
			'classname' => 'theme_logo',
			'description' => __("Display Logo", 'lang_theme_core'),
		);

		/*$this->arr_default = array(
			'logo_url' => '',
			'logo_display' => 'all',
			'logo_title' => '',
			'logo_image' => '',
			'logo_description' => '',
		);*/

		parent::__construct(str_replace("_", "-", $this->widget_ops['classname']).'-widget', __("Logo", 'lang_theme_core'), $this->widget_ops);
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

		echo apply_filters('filter_before_widget', $before_widget)
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
					echo show_textfield(array('name' => $this->get_field_name('logo_title'), 'text' => __("Logo", 'lang_theme_core'), 'value' => $instance['logo_title'], 'xtra' => " id='".$this->widget_ops['classname']."-title'"));
				}

				if($instance['logo_title'] == '')
				{
					echo get_media_library(array('type' => 'image', 'name' => $this->get_field_name('logo_image'), 'value' => $instance['logo_image']));
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
	var $obj_theme_core = "";

	var $widget_ops = array();

	var $arr_default = array(
		'search_placeholder' => "",
		'search_hide_on_mobile' => 'no',
		'search_animate' => 'yes',
		'search_listen_to_keystroke' => 'no',
	);

	function __construct()
	{
		$this->obj_theme_core = new mf_theme_core();

		$this->widget_ops = array(
			'classname' => 'theme_search',
			'description' => __("Display Search Form", 'lang_theme_core'),
		);

		/*$this->arr_default = array(
			'search_placeholder' => "",
			'search_hide_on_mobile' => 'no',
			'search_animate' => 'yes',
			'search_listen_to_keystroke' => 'no',
		);*/

		parent::__construct(str_replace("_", "-", $this->widget_ops['classname']).'-widget', __("Search", 'lang_theme_core'), $this->widget_ops);
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

		echo apply_filters('filter_before_widget', $before_widget);

			echo $this->obj_theme_core->get_search_theme_core(array(
				'placeholder' => $instance['search_placeholder'],
				'hide_on_mobile' => (isset($instance['search_hide_on_mobile']) ? $instance['search_hide_on_mobile'] : ''),
				'animate' => (isset($instance['search_animate']) ? $instance['search_animate'] : ''),
			));

		echo $after_widget;
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
			.show_textfield(array('name' => $this->get_field_name('search_placeholder'), 'text' => __("Placeholder", 'lang_theme_core'), 'value' => $instance['search_placeholder']))
			."<div class='flex_flow'>"
				.show_select(array('data' => get_yes_no_for_select(), 'name' => $this->get_field_name('search_hide_on_mobile'), 'text' => __("Hide on Mobile", 'lang_theme_core'), 'value' => $instance['search_hide_on_mobile']))
				.show_select(array('data' => get_yes_no_for_select(), 'name' => $this->get_field_name('search_animate'), 'text' => __("Animate", 'lang_theme_core'), 'value' => $instance['search_animate']))
				.show_select(array('data' => get_yes_no_for_select(), 'name' => $this->get_field_name('search_listen_to_keystroke'), 'text' => __("Listen to Keystroke", 'lang_theme_core'), 'value' => $instance['search_listen_to_keystroke']))
			."</div>"
		."</div>";
	}
}

class widget_theme_core_news extends WP_Widget
{
	var $obj_theme_core = "";

	var $widget_ops = array();

	var $arr_default = array(
		'news_title' => "",
		'news_type' => 'original',
		'news_categories' => array(),
		'news_amount' => 1,
		'news_hide_button' => 'no',
		'news_columns' => 0,
		'news_time_limit' => 0,
		'news_expand_content' => 'no',
		'news_display_arrows' => 'no',
		'news_autoscroll_time' => 5,
		'news_display_title' => 'yes',
		'news_display_excerpt' => 'yes',
		'news_page' => 0,
	);

	function __construct()
	{
		$this->obj_theme_core = new mf_theme_core();

		$this->widget_ops = array(
			'classname' => 'theme_news',
			'description' => __("Display News/Posts", 'lang_theme_core'),
		);

		/*$this->arr_default = array(
			'news_title' => "",
			'news_type' => 'original',
			'news_categories' => array(),
			'news_amount' => 1,
			'news_hide_button' => 'no',
			'news_columns' => 0,
			'news_time_limit' => 0,
			'news_expand_content' => 'no',
			'news_display_arrows' => 'no',
			'news_autoscroll_time' => 5,
			'news_display_title' => 'yes',
			'news_display_excerpt' => 'yes',
			'news_page' => 0,
		);*/

		parent::__construct(str_replace("_", "-", $this->widget_ops['classname']).'-widget', __("News", 'lang_theme_core'), $this->widget_ops);
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

		$result = $wpdb->get_results($wpdb->prepare("SELECT ID, post_title, post_excerpt, post_date FROM ".$wpdb->posts.$query_join." WHERE post_type = %s AND post_status = %s".$query_where." ORDER BY post_date DESC LIMIT 0, ".$instance['news_amount'], 'post', 'publish'));

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
			$display_hide_news = ($rows == 1 && $instance['news_hide_button'] == 'yes');
			$news_id = (function_exists('array_key_first') ? array_key_first($this->arr_news) : $this->number);
			$display_news_scroll = ($rows > 3 && $instance['news_display_arrows'] == 'yes');

			if($display_news_scroll)
			{
				$plugin_include_url = plugin_dir_url(__FILE__);
				$plugin_version = get_plugin_version(__FILE__);

				mf_enqueue_style('style_theme_news_scroll', $plugin_include_url."style_news_scroll.css", $plugin_version); //Should be set in wp_head instead
				mf_enqueue_script('script_theme_news_scroll', $plugin_include_url."script_news_scroll.js", $plugin_version);
			}

			if($display_hide_news == false)
			{
				echo apply_filters('filter_before_widget', $before_widget);

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
								$instance['news_columns'] = ($rows % 3 == 0 || $rows > 4 || $instance['news_type'] == 'postit' ? 3 : 2);
							}

							echo "<ul class='text_columns columns_".$instance['news_columns']."' data-columns='".$instance['news_columns']."'>";

								foreach($this->arr_news as $news_id => $arr_news_item)
								{
									if($instance['news_type'] == 'postit')
									{
										$arr_news_item['excerpt'] = shorten_text(array('string' => $arr_news_item['excerpt'], 'limit' => (300 - $instance['news_columns'] * 60)));
									}

									echo "<li>";

										if(IS_EDITOR && get_option('setting_theme_core_enable_edit_mode', 'yes') == 'yes')
										{
											echo "<a href='".admin_url("post.php?post=".$news_id."&action=edit")."' class='edit_item'><i class='fa fa-wrench' title='".__("Edit Item", 'lang_theme_core')."'></i></a>";
										}

										echo "<a href='".$arr_news_item['url']."'>";

											switch($instance['news_type'])
											{
												case 'original':
												case 'simple':
													echo "<div class='image'>".$arr_news_item['image']."</div>";
												break;

												case 'compact':
													echo "<span>".format_date($arr_news_item['date'])."</span>";
												break;
											}

											if($instance['news_display_title'] == 'yes')
											{
												echo "<h4>".$arr_news_item['title']."</h4>";
											}

											switch($instance['news_type'])
											{
												case 'postit':
												case 'simple':
													if($instance['news_display_excerpt'] == 'yes')
													{
														echo apply_filters('the_content', $arr_news_item['excerpt']);
													}
												break;
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
							foreach($this->arr_news as $news_id => $arr_news_item)
							{
								if($instance['news_expand_content'] == 'yes')
								{
									$post_content = mf_get_post_content($news_id);

									echo "<div class='news_expand_content'>";

										if($arr_news_item['image'] != '')
										{
											echo "<div class='image'>".$arr_news_item['image']."</div>";
										}

										echo ($instance['news_title'] == '' ? $before_title : "<h4>")
											.$arr_news_item['title']
										.($instance['news_title'] == '' ? $after_title : "</h4>")
										."<div class='excerpt'>".apply_filters('the_content', stripslashes($arr_news_item['excerpt']))."</div>"
										."<p class='read_more'><a href='#'>".__("Read More", 'lang_theme_core')."</a></p>"
										."<div class='content hide'>".apply_filters('the_content', $post_content)."</div>
									</div>";
								}

								else
								{
									echo "<a href='".$arr_news_item['url']."'>";

										if($arr_news_item['image'] != '')
										{
											echo "<div class='image'>".$arr_news_item['image']."</div>";
										}

										echo ($instance['news_title'] == '' ? $before_title : "<h4>")
											.$arr_news_item['title']
										.($instance['news_title'] == '' ? $after_title : "</h4>")
										.apply_filters('the_content', $arr_news_item['excerpt'])
										."<p class='read_more'>".__("Read More", 'lang_theme_core')."</p>"
									."</a>";
								}
							}
						}

					echo "</div>";

					if($display_hide_news)
					{
						$plugin_include_url = plugin_dir_url(__FILE__);
						$plugin_version = get_plugin_version(__FILE__);

						mf_enqueue_style('style_theme_hide_news', $plugin_include_url."style_hide_news.css", $plugin_version); //Should be set in wp_head instead
						mf_enqueue_script('script_theme_hide_news', $plugin_include_url."script_hide_news.js", $plugin_version);

						echo "<i class='fa fa-times hide_news' data-news_id='".$news_id."'></i>";
					}

				echo $after_widget;
			}
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
		$instance['news_hide_button'] = sanitize_text_field($new_instance['news_hide_button']);
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
			'original' => __("Default", 'lang_theme_core'),
			'postit' => __("Post It", 'lang_theme_core'),
			'simple' => __("Simple", 'lang_theme_core'),
			'compact' => __("Compact", 'lang_theme_core'),
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
			.show_textfield(array('name' => $this->get_field_name('news_title'), 'text' => __("Title", 'lang_theme_core'), 'value' => $instance['news_title'], 'xtra' => " id='".$this->widget_ops['classname']."-title'"))
			.show_select(array('data' => $this->get_news_type_for_select(), 'name' => $this->get_field_name('news_type'), 'text' => __("Design", 'lang_theme_core'), 'value' => $instance['news_type']))
			.show_select(array('data' => get_categories_for_select(array('hide_empty' => false)), 'name' => $this->get_field_name('news_categories')."[]", 'text' => __("Categories", 'lang_theme_core'), 'value' => $instance['news_categories']))
			."<div class='flex_flow'>"
				.show_textfield(array('type' => 'number', 'name' => $this->get_field_name('news_amount'), 'text' => __("Amount", 'lang_theme_core'), 'value' => $instance['news_amount'], 'xtra' => " min='0' max='".($rows > 0 ? $rows : 1)."'"));

				if($instance['news_amount'] > 1 && $rows > 3 && $instance['news_type'] != 'compact')
				{
					echo show_textfield(array('type' => 'number', 'name' => $this->get_field_name('news_columns'), 'text' => __("Columns", 'lang_theme_core'), 'value' => $instance['news_columns'], 'xtra' => " min='0' max='4'"));
				}

			echo "</div>";

			if($instance['news_amount'] == 1)
			{
				echo "<div class='flex_flow'>"
					.show_textfield(array('type' => 'number', 'name' => $this->get_field_name('news_time_limit'), 'text' => __("Time Limit", 'lang_theme_core'), 'value' => $instance['news_time_limit'], 'xtra' => " min='0' max='240'", 'suffix' => __("h", 'lang_theme_core')))
					.show_select(array('data' => get_yes_no_for_select(), 'name' => $this->get_field_name('news_hide_button'), 'text' => __("Button to Hide", 'lang_theme_core'), 'value' => $instance['news_hide_button']))
				."</div>";
			}

			if($instance['news_type'] == 'postit')
			{
				echo "<div class='flex_flow'>"
					.show_select(array('data' => get_yes_no_for_select(), 'name' => $this->get_field_name('news_display_arrows'), 'text' => __("Display Arrows", 'lang_theme_core'), 'value' => $instance['news_display_arrows']));

					if($instance['news_display_arrows'] == 'yes')
					{
						echo show_textfield(array('type' => 'number', 'name' => $this->get_field_name('news_autoscroll_time'), 'text' => __("Autoscroll", 'lang_theme_core'), 'value' => $instance['news_autoscroll_time'], 'xtra' => " min='0' max='60'"));
					}

				echo "</div>";
			}

			if($instance['news_type'] != 'compact')
			{
				echo "<div class='flex_flow'>"
					.show_select(array('data' => get_yes_no_for_select(), 'name' => $this->get_field_name('news_display_title'), 'text' => __("Display Title", 'lang_theme_core'), 'value' => $instance['news_display_title']))
					.show_select(array('data' => get_yes_no_for_select(), 'name' => $this->get_field_name('news_display_excerpt'), 'text' => __("Display Excerpt", 'lang_theme_core'), 'value' => $instance['news_display_excerpt']))
				."</div>";
			}

			if($instance['news_amount'] == 1)
			{
				echo show_select(array('data' => get_yes_no_for_select(), 'name' => $this->get_field_name('news_expand_content'), 'text' => __("Expand Content on Current Page", 'lang_theme_core'), 'value' => $instance['news_expand_content']));
			}

			if($rows > 1 && $instance['news_amount'] > 1)
			{
				echo show_select(array('data' => $arr_data_pages, 'name' => $this->get_field_name('news_page'), 'text' => __("Read More", 'lang_theme_core'), 'value' => $instance['news_page']));
			}

		echo "</div>";
	}
}

class widget_theme_core_info extends WP_Widget
{
	var $obj_theme_core = "";

	var $widget_ops = array();

	var $arr_default = array(
		'info_image' => '',
		'info_title' => '',
		'info_content' => '',
		'info_button_text' => '',
		'info_page' => 0,
		'info_link' => '',
		'info_time_limit' => 0,
		'info_visit_limit' => 0,
	);

	function __construct()
	{
		$this->obj_theme_core = new mf_theme_core();

		$this->widget_ops = array(
			'classname' => 'theme_info',
			'description' => __("Display Info Module", 'lang_theme_core'),
		);

		/*$this->arr_default = array(
			'info_image' => '',
			'info_title' => '',
			'info_content' => '',
			'info_button_text' => '',
			'info_page' => 0,
			'info_link' => '',
			'info_time_limit' => 0,
			'info_visit_limit' => 0,
		);*/

		parent::__construct(str_replace("_", "-", $this->widget_ops['classname']).'-widget', __("Info Module", 'lang_theme_core'), $this->widget_ops);
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
				$cookie_name = 'cookie_theme_core_info_time_limit';

				$arr_ses_info_time_limit = (isset($_COOKIE[$cookie_name]) ? $_COOKIE[$cookie_name] : array());

				if(!isset($arr_ses_info_time_limit[$widget_md5]) || $arr_ses_info_time_limit[$widget_md5] < DEFAULT_DATE)
				{
					$arr_ses_info_time_limit[$widget_md5] = date("Y-m-d");

					setcookie($cookie_name, $arr_ses_info_time_limit, strtotime("+1 month"), COOKIEPATH);
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
				$arr_meta_info_visit_limit = (is_array($arr_meta_info_visit_limit) ? $arr_meta_info_visit_limit[0] : array());

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
				$cookie_name = 'cookie_theme_core_info_visit_limit';

				$arr_ses_info_visit_limit = (isset($_COOKIE[$cookie_name]) ? $_COOKIE[$cookie_name] : array());

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
					setcookie($cookie_name, $arr_ses_info_visit_limit, strtotime("+1 month"), COOKIEPATH);
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
			echo apply_filters('filter_before_widget', $before_widget)
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
			.show_textfield(array('name' => $this->get_field_name('info_title'), 'text' => __("Title", 'lang_theme_core'), 'value' => $instance['info_title'], 'xtra' => " id='".$this->widget_ops['classname']."-title'"))
			.show_textarea(array('name' => $this->get_field_name('info_content'), 'text' => __("Content", 'lang_theme_core'), 'value' => $instance['info_content']))
			.show_textfield(array('name' => $this->get_field_name('info_button_text'), 'text' => __("Button Text", 'lang_theme_core'), 'value' => $instance['info_button_text']));

			if($instance['info_button_text'] != '')
			{
				if($instance['info_link'] == '')
				{
					$arr_data = array();
					get_post_children(array('add_choose_here' => true), $arr_data);

					echo show_select(array('data' => $arr_data, 'name' => $this->get_field_name('info_page'), 'text' => __("Page", 'lang_theme_core'), 'value' => $instance['info_page']));
				}

				if(!($instance['info_page'] > 0))
				{
					echo show_textfield(array('type' => 'url', 'name' => $this->get_field_name('info_link'), 'text' => __("Link", 'lang_theme_core'), 'value' => $instance['info_link']));
				}
			}

			if(!($instance['info_visit_limit'] > 0))
			{
				echo show_textfield(array('type' => 'number', 'name' => $this->get_field_name('info_time_limit'), 'text' => __("Time Limit", 'lang_theme_core'), 'value' => $instance['info_time_limit'], 'suffix' => __("days", 'lang_theme_core')));
			}

			if(!($instance['info_time_limit'] > 0))
			{
				echo show_textfield(array('type' => 'number', 'name' => $this->get_field_name('info_visit_limit'), 'text' => __("Visit Limit", 'lang_theme_core'), 'value' => $instance['info_visit_limit'], 'suffix' => __("times", 'lang_theme_core')));
			}

		echo "</div>";
	}
}

class widget_theme_core_related extends WP_Widget
{
	var $obj_theme_core = "";

	var $widget_ops = array();

	var $arr_default = array(
		'news_title' => '',
		'news_post_type' => 'post',
		'news_categories' => array(),
		'news_amount' => 1,
		'news_columns' => 1,
	);

	function __construct()
	{
		$this->obj_theme_core = new mf_theme_core();

		$this->widget_ops = array(
			'classname' => 'theme_news',
			'description' => __("Display Related Posts", 'lang_theme_core'),
		);

		/*$this->arr_default = array(
			'news_title' => '',
			'news_post_type' => 'post',
			'news_categories' => array(),
			'news_amount' => 1,
			'news_columns' => 1,
		);*/

		parent::__construct('theme-related-news-widget', __("Related Posts", 'lang_theme_core'), $this->widget_ops);
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
			echo apply_filters('filter_before_widget', $before_widget);

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

						foreach($this->arr_news as $arr_news_item)
						{
							echo "<li>
								<a href='".$arr_news_item['url']."'>
									<div class='image'>".$arr_news_item['image']."</div>
									<h4>".$arr_news_item['title']."</h4>
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
			.show_textfield(array('name' => $this->get_field_name('news_title'), 'text' => __("Title", 'lang_theme_core'), 'value' => $instance['news_title'], 'xtra' => " id='".$this->widget_ops['classname']."-title'"))
			.show_select(array('data' => get_post_types_for_select(array('include' => array('types'), 'add_is' => false)), 'name' => $this->get_field_name('news_post_type'), 'value' => $instance['news_post_type']))
			.show_select(array('data' => get_categories_for_select(), 'name' => $this->get_field_name('news_categories')."[]", 'text' => __("Categories", 'lang_theme_core'), 'value' => $instance['news_categories']))
			."<div class='flex_flow'>"
				.show_textfield(array('type' => 'number', 'name' => $this->get_field_name('news_amount'), 'text' => __("Amount", 'lang_theme_core'), 'value' => $instance['news_amount'], 'xtra' => " min='1'"));

				if($instance['news_amount'] > 1)
				{
					echo show_textfield(array('type' => 'number', 'name' => $this->get_field_name('news_columns'), 'text' => __("Columns", 'lang_theme_core'), 'value' => $instance['news_columns'], 'xtra' => " min='1' max='4'"));
				}

			echo "</div>
		</div>";
	}
}

class widget_theme_core_promo extends WP_Widget
{
	var $obj_theme_core = "";

	var $widget_ops = array();

	var $arr_default = array(
		'promo_title' => "",
		'promo_include' => array(),
		'promo_page_titles' => 'yes',
	);

	function __construct()
	{
		$this->obj_theme_core = new mf_theme_core();

		$this->widget_ops = array(
			'classname' => 'theme_promo theme_news',
			'description' => __("Promote Pages", 'lang_theme_core'),
		);

		/*$this->arr_default = array(
			'promo_title' => "",
			'promo_include' => array(),
			'promo_page_titles' => 'yes',
		);*/

		parent::__construct('theme-promo-widget', __("Promotion", 'lang_theme_core'), $this->widget_ops);
	}

	function widget($args, $instance)
	{
		global $wpdb;

		extract($args);
		$instance = wp_parse_args((array)$instance, $this->arr_default);

		if(count($instance['promo_include']) > 0)
		{
			$arr_pages = array();

			$result = $wpdb->get_results($wpdb->prepare("SELECT ID, post_title, post_content FROM ".$wpdb->posts." WHERE post_type = %s AND post_status = %s AND ID IN('".implode("','", $instance['promo_include'])."') ORDER BY menu_order ASC", 'page', 'publish'));

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
				echo apply_filters('filter_before_widget', $before_widget);

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
		$instance['promo_include'] = (is_array($new_instance['promo_include']) ? $new_instance['promo_include'] : array());
		$instance['promo_page_titles'] = sanitize_text_field($new_instance['promo_page_titles']);

		return $instance;
	}

	function form($instance)
	{
		$instance = wp_parse_args((array)$instance, $this->arr_default);

		$arr_data = array();
		get_post_children(array('post_type' => 'page', 'order_by' => 'post_title'), $arr_data);

		echo "<div class='mf_form'>"
			.show_textfield(array('name' => $this->get_field_name('promo_title'), 'text' => __("Title", 'lang_theme_core'), 'value' => $instance['promo_title'], 'xtra' => " id='".$this->widget_ops['classname']."-title'"))
			.show_select(array('data' => $arr_data, 'name' => $this->get_field_name('promo_include')."[]", 'text' => __("Pages", 'lang_theme_core'), 'value' => $instance['promo_include']))
			.show_select(array('data' => get_yes_no_for_select(), 'name' => $this->get_field_name('promo_page_titles'), 'text' => __("Display Titles", 'lang_theme_core'), 'value' => $instance['promo_page_titles']))
		."</div>";
	}
}

class widget_theme_core_page_index extends WP_Widget
{
	var $obj_theme_core = "";

	var $widget_ops = array();

	var $arr_default = array(
		'widget_title' => "",
	);

	function __construct()
	{
		$this->obj_theme_core = new mf_theme_core();

		$this->widget_ops = array(
			'classname' => 'theme_page_index',
			'description' => __("Display Table of Contents", 'lang_theme_core'),
		);

		/*$this->arr_default = array(
			'widget_title' => "",
		);*/

		parent::__construct(str_replace("_", "-", $this->widget_ops['classname']).'-widget', __("Table of Contents", 'lang_theme_core'), $this->widget_ops);
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
				echo apply_filters('filter_before_widget', $before_widget);

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
			.show_textfield(array('name' => $this->get_field_name('widget_title'), 'text' => __("Title", 'lang_theme_core'), 'value' => $instance['widget_title'], 'xtra' => " id='".$this->widget_ops['classname']."-title'"))
		."</div>";
	}
}