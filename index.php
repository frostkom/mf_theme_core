<?php
/*
Plugin Name: MF Theme Core
Plugin URI: https://github.com/frostkom/mf_theme_core
Description: 
Version: 7.8.37
Licence: GPLv2 or later
Author: Martin Fors
Author URI: https://frostkom.se
Text Domain: lang_theme_core
Domain Path: /lang

Depends: MF Base
GitHub Plugin URI: frostkom/mf_theme_core
*/

define('DISALLOW_FILE_EDIT', true);

include_once("include/classes.php");
include_once("include/functions.php");

$obj_theme_core = new mf_theme_core();

add_action('cron_base', 'activate_theme_core', mt_rand(1, 10));
add_action('cron_base', array($obj_theme_core, 'cron_base'), mt_rand(1, 10));

add_action('init', array($obj_theme_core, 'init'));

if(is_admin())
{
	register_activation_hook(__FILE__, 'activate_theme_core');
	register_uninstall_hook(__FILE__, 'uninstall_theme_core');

	add_action('wp_before_admin_bar_render', array($obj_theme_core, 'wp_before_admin_bar_render'));

	add_action('admin_init', array($obj_theme_core, 'settings_theme_core'));
	add_action('admin_init', array($obj_theme_core, 'admin_init'), 0);

	add_filter('upload_mimes', array($obj_theme_core, 'upload_mimes'));

	if($obj_theme_core->is_theme_active())
	{
		add_action('admin_menu', array($obj_theme_core, 'admin_menu'));
	}

	if(is_multisite())
	{
		add_filter('manage_sites-network_columns', array($obj_theme_core, 'sites_column_header'), 5);
		add_action('manage_sites_custom_column', array($obj_theme_core, 'sites_column_cell'), 5, 2);
	}

	add_filter('wp_get_default_privacy_policy_content', array($obj_theme_core, 'add_policy'));

	add_action('wp_loaded', array($obj_theme_core, 'wp_loaded'));
	add_filter('post_row_actions', array($obj_theme_core, 'row_actions'), 10, 2);
	add_filter('page_row_actions', array($obj_theme_core, 'row_actions'), 10, 2);

	add_filter('manage_page_posts_columns', array($obj_theme_core, 'column_header'), 5);
	add_action('manage_page_posts_custom_column', array($obj_theme_core, 'column_cell'), 5, 2);
	add_filter('manage_post_posts_columns', array($obj_theme_core, 'column_header'), 5);
	add_action('manage_post_posts_custom_column', array($obj_theme_core, 'column_cell'), 5, 2);

	add_filter('map_meta_cap', array($obj_theme_core, 'map_meta_cap'), 10, 2);

	add_filter('hidden_meta_boxes', array($obj_theme_core, 'hidden_meta_boxes'), 10, 2);

	add_action('rwmb_meta_boxes', array($obj_theme_core, 'rwmb_meta_boxes'));

	add_action('save_post', array($obj_theme_core, 'save_post'), 10, 3);

	add_filter('count_shortcode_button', array($obj_theme_core, 'count_shortcode_button'));
	add_filter('get_shortcode_output', array($obj_theme_core, 'get_shortcode_output'));

	remove_action('admin_print_styles', 'print_emoji_styles');
	remove_action('admin_print_scripts', 'print_emoji_detection_script');
}

else
{
	add_action('do_robots', array($obj_theme_core, 'do_robots'), 100, 0);
	add_filter('template_redirect', array($obj_theme_core, 'do_sitemap'), 1, 0);

	add_action('get_header', array($obj_theme_core, 'get_header'), 0);

	if($obj_theme_core->is_theme_active())
	{
		add_action('wp_head', array($obj_theme_core, 'wp_head'), 0);
		add_filter('body_class', array($obj_theme_core, 'body_class'));
	}

	add_filter('embed_oembed_html', array($obj_theme_core, 'embed_oembed_html'), 99, 4);

	remove_action('wp_head', 'rest_output_link_wp_head'); // Disable REST API link tag
	remove_action('template_redirect', 'rest_output_link_header', 11, 0); // Disable REST API link in HTTP headers
	remove_action('wp_head', 'wlwmanifest_link');
	remove_action('wp_head', 'rsd_link');
	remove_action('wp_head', 'wp_shortlink_wp_head', 10, 0);
	remove_action('wp_head', 'wp_oembed_add_discovery_links'); // Disable oEmbed Discovery Links

	remove_action('wp_head', 'wp_generator'); // Remove WP versions

	remove_action('wp_head', 'feed_links', 2);
	remove_action('wp_head', 'feed_links_extra', 3);

	remove_action('wp_print_styles', 'print_emoji_styles');
	remove_action('wp_head', 'print_emoji_detection_script', 7);
	add_filter('emoji_svg_url', '__return_false');

	if(get_site_option('setting_theme_enable_wp_api', get_option('setting_theme_enable_wp_api')) != 'yes')
	{
		add_filter('xmlrpc_enabled', '__return_false');
	}

	if($obj_theme_core->is_theme_active())
	{
		add_filter('wp_nav_menu_args', array($obj_theme_core, 'wp_nav_menu_args'));

		add_filter('get_search_form', array($obj_theme_core, 'get_search_form'));

		add_filter('the_password_form', array($obj_theme_core, 'the_password_form'));
		add_filter('the_content', array($obj_theme_core, 'the_content'));

		add_filter('the_content_meta', array($obj_theme_core, 'the_content_meta'), 1, 2);

		add_filter('widget_title', array($obj_theme_core, 'widget_title'));
	}

	add_filter('wp_default_scripts', array($obj_theme_core, 'wp_default_scripts'));
	add_action('wp_print_scripts', array($obj_theme_core, 'wp_print_scripts'), 1);
	add_action('wp_footer', array($obj_theme_core, 'wp_footer'));
}

add_filter('is_theme_active', array($obj_theme_core, 'is_theme_active'));

add_action('after_setup_theme', array($obj_theme_core, 'after_setup_theme'));

if($obj_theme_core->is_theme_active())
{
	add_action('widgets_init', array($obj_theme_core, 'widgets_init'));

	add_action('customize_register', array($obj_theme_core, 'customize_register'), 11);
	add_action('customize_save', array($obj_theme_core, 'customize_save'));
}

add_action('wp_ajax_optimize_theme', array($obj_theme_core, 'optimize_theme'));

add_shortcode('redirect', array($obj_theme_core, 'shortcode_redirect'));

load_plugin_textdomain('lang_theme_core', false, dirname(plugin_basename(__FILE__)).'/lang/');

function activate_theme_core()
{
	mf_uninstall_plugin(array(
		'options' => array('setting_save_style', 'setting_compress', 'setting_responsiveness', 'setting_theme_recommendation', 'setting_html5_history', 'setting_splash_screen', 'setting_theme_disable_functionality', 'setting_theme_optimize', 'option_uploads_done'),
	));
}

function uninstall_theme_core()
{
	mf_uninstall_plugin(array(
		'uploads' => 'mf_theme_core',
		'options' => array('setting_no_public_pages', 'setting_theme_core_login', 'setting_theme_core_hidden_meta_boxes', 'setting_display_post_meta', 'setting_scroll_to_top', 'setting_cookie_info', 'setting_404_page', 'setting_maintenance_page', 'setting_maintenance_page_temp', 'setting_activate_maintenance', 'setting_send_email_on_draft', 'setting_theme_enable_wp_api', 'option_theme_saved', 'option_theme_version', 'theme_source_version', 'option_theme_source_style_url', 'option_database_optimized', 'option_uploads_fixed'),
	));
}