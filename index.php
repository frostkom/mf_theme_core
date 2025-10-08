<?php
/*
Plugin Name: MF Theme Core
Plugin URI: https://github.com/frostkom/mf_theme_core
Description:
Version: 8.9.57
Licence: GPLv2 or later
Author: Martin Fors
Author URI: https://martinfors.se
Text Domain: lang_theme_core
Domain Path: /lang
*/

if(!function_exists('is_plugin_active') || function_exists('is_plugin_active') && is_plugin_active("mf_base/index.php"))
{
	include_once("include/classes.php");
	include_once("include/functions.php");

	load_plugin_textdomain('lang_theme_core', false, dirname(plugin_basename(__FILE__))."/lang/");

	$obj_theme_core = new mf_theme_core();

	add_action('cron_base', array($obj_theme_core, 'cron_base'), mt_rand(1, 10));

	if(is_admin())
	{
		register_uninstall_hook(__FILE__, 'uninstall_theme_core');

		add_action('admin_init', array($obj_theme_core, 'settings_theme_core'));
		add_action('admin_menu', array($obj_theme_core, 'admin_menu'));

		add_filter('filter_sites_table_settings', array($obj_theme_core, 'filter_sites_table_settings'));

		add_filter('upload_mimes', array($obj_theme_core, 'upload_mimes'));

		add_action('wp_loaded', array($obj_theme_core, 'wp_loaded'));

		add_filter('post_row_actions', array($obj_theme_core, 'post_row_actions'), 10, 2);

		add_filter('map_meta_cap', array($obj_theme_core, 'map_meta_cap'), 10, 2);

		add_filter('hidden_meta_boxes', array($obj_theme_core, 'hidden_meta_boxes'), 10, 2);

		add_action('save_post', array($obj_theme_core, 'save_post'), 10, 3);
	}

	else
	{
		add_action('wp_head', array($obj_theme_core, 'wp_head'), 0);

		if($obj_theme_core->is_theme_active())
		{
			add_filter('body_class', array($obj_theme_core, 'body_class'));
		}

		add_filter('embed_oembed_html', array($obj_theme_core, 'embed_oembed_html'), 99, 4);

		if($obj_theme_core->is_theme_active())
		{
			add_filter('wp_nav_menu_args', array($obj_theme_core, 'wp_nav_menu_args'));
			add_filter('wp_nav_menu_objects', array($obj_theme_core, 'wp_nav_menu_objects'), 10, 2);

			add_filter('get_search_form', array($obj_theme_core, 'get_search_form'));

			add_filter('the_content_meta', array($obj_theme_core, 'the_content_meta'), 1, 2);

			add_filter('widget_title', array($obj_theme_core, 'widget_title'));
		}

		add_filter('wp_default_scripts', array($obj_theme_core, 'wp_default_scripts'));
		add_action('wp_print_scripts', array($obj_theme_core, 'wp_print_scripts'), 1);
		add_action('wp_footer', array($obj_theme_core, 'wp_footer'));
	}

	add_filter('is_theme_active', array($obj_theme_core, 'is_theme_active'));

	if($obj_theme_core->is_theme_active())
	{
		add_action('after_setup_theme', array($obj_theme_core, 'after_setup_theme'));
	}

	add_filter('get_allow_cookies', array($obj_theme_core, 'get_allow_cookies'));

	if($obj_theme_core->is_theme_active())
	{
		add_action('widgets_init', array($obj_theme_core, 'widgets_init'));

		add_action('customize_register', array($obj_theme_core, 'customize_register'), 11);
		add_action('customize_save', array($obj_theme_core, 'customize_save'));
	}

	add_shortcode('redirect', array($obj_theme_core, 'shortcode_redirect'));

	function uninstall_theme_core()
	{
		include_once("include/classes.php");

		$obj_theme_core = new mf_theme_core();

		mf_uninstall_plugin(array(
			'uploads' => $obj_theme_core->post_type,
			'options' => array('setting_no_public_pages', 'setting_theme_core_login', 'setting_theme_core_templates', 'setting_theme_core_hidden_meta_boxes', 'setting_send_email_on_draft', 'setting_theme_ignore_style_on_restore', 'setting_theme_optimize', 'setting_theme_core_enable_edit_mode', 'setting_theme_core_title_format', 'setting_display_post_meta', 'setting_scroll_to_top', 'setting_scroll_to_top_text', 'setting_theme_core_search_redirect_single_result', 'setting_404_page', 'setting_maintenance_page', 'setting_maintenance_page_html', 'setting_maintenance_page_temp', 'setting_activate_maintenance', 'option_theme_saved', 'option_theme_version', 'theme_source_version', 'option_theme_source_style_url', 'option_database_optimized'),
			'user_meta' => array('meta_info_time_limit', 'meta_info_visit_limit', 'meta_time_visit_limit'),
		));
	}
}