<?php
/*
Plugin Name: MF Theme Core
Plugin URI: https://github.com/frostkom/mf_theme_core
Description: 
Version: 7.2.23
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

add_action('cron_base', array($obj_theme_core, 'run_cron'), mt_rand(1, 10));

//add_action('init', 'init_theme_core');

if(is_admin())
{
	register_activation_hook(__FILE__, 'activate_theme_core');
	register_uninstall_hook(__FILE__, 'uninstall_theme_core');

	new mf_clone_posts();

	add_action('wp_before_admin_bar_render', 'admin_bar_theme_core');
	add_action('admin_init', 'settings_theme_core');
	add_action('admin_init', array($obj_theme_core, 'admin_init'), 0);
	add_filter('upload_mimes', array($obj_theme_core, 'upload_mimes'));
	add_action('admin_menu', array($obj_theme_core, 'admin_menu'));

	add_filter('wp_get_default_privacy_policy_content', array($obj_theme_core, 'add_policy'));

	add_filter('manage_page_posts_columns', array($obj_theme_core, 'column_header'), 5);
	add_action('manage_page_posts_custom_column', array($obj_theme_core, 'column_cell'), 5, 2);
	add_filter('manage_post_posts_columns', array($obj_theme_core, 'column_header'), 5);
	add_action('manage_post_posts_custom_column', array($obj_theme_core, 'column_cell'), 5, 2);

	add_filter('map_meta_cap', array($obj_theme_core, 'map_meta_cap'), 10, 2);

	add_filter('admin_post_thumbnail_html', array($obj_theme_core, 'admin_post_thumbnail_html'), 10, 2);

	add_action('rwmb_meta_boxes', array($obj_theme_core, 'meta_boxes'));

	add_action('save_post', array($obj_theme_core, 'save_post'), 10, 3);

	remove_action('admin_print_styles', 'print_emoji_styles');
	remove_action('admin_print_scripts', 'print_emoji_detection_script');
}

else
{
	add_action('do_robots', array($obj_theme_core, 'do_robots'), 100, 0);
	add_filter('template_redirect', array($obj_theme_core, 'do_sitemap'), 1, 0);

	add_action('get_header', 'header_theme_core', 0);
	add_action('wp_head', array($obj_theme_core, 'wp_head'), 0);
	add_filter('body_class', 'body_class_theme_core');

	add_filter('embed_oembed_html', array($obj_theme_core, 'embed_oembed_html'), 99, 4);

	remove_action('wp_head', 'rest_output_link_wp_head');
	remove_action('wp_head', 'wlwmanifest_link');
	remove_action('wp_head', 'rsd_link');
	remove_action('wp_head', 'wp_shortlink_wp_head', 10, 0);
	remove_action('wp_head', 'wp_oembed_add_discovery_links');
	remove_action('wp_head', 'wp_generator');
	//remove_action('wp_head', 'rel_canonical');
	remove_action('wp_head', 'feed_links', 2);
	remove_action('wp_head', 'feed_links_extra', 3);

	remove_action('wp_print_styles', 'print_emoji_styles');
	remove_action('wp_head', 'print_emoji_detection_script', 7);

	//remove_action('template_redirect', 'rest_output_link_header', 11, 0);

	//Disable oEmbed
	/*remove_action('wp_head', 'rest_output_link_wp_head', 10);
	remove_action('template_redirect', 'rest_output_link_header', 11, 0);
	remove_action('wp_head', 'wp_oembed_add_host_js');
	remove_action('rest_api_init', 'wp_oembed_register_route');
	remove_filter('oembed_dataparse', 'wp_filter_oembed_result', 10);

	//Disable more oEmbed
	remove_action('wp_head', 'wp_oembed_add_discovery_links');
	add_filter('rewrite_rules_array', 'disable_embeds_rewrites');*/

	add_filter('wp_nav_menu_args', 'nav_args_theme_core');

	add_filter('get_search_form', 'search_form_theme_core');

	add_filter('the_password_form', 'password_form_theme_core');
	add_filter('the_content', 'the_content_protected_theme_core');

	add_filter('the_content_meta', array($obj_theme_core, 'the_content_meta'), 1, 2);

	add_filter('wp_default_scripts', 'default_scripts_theme_core');
	add_action('wp_print_scripts', 'print_scripts_theme_core', 1);
	add_action('wp_footer', array($obj_theme_core, 'wp_footer'));
}

add_action('after_setup_theme', 'setup_theme_core');
add_action('widgets_init', array($obj_theme_core, 'widgets_init'));

add_action('customize_register', array($obj_theme_core, 'customize_theme'), 11);
add_action('customize_save', 'customize_save_theme_core');

$obj_theme_core = new mf_theme_core();

add_action('wp_ajax_optimize_theme', array($obj_theme_core, 'run_optimize'));

load_plugin_textdomain('lang_theme_core', false, dirname(plugin_basename(__FILE__)).'/lang/');

function activate_theme_core()
{
	replace_option(array('old' => 'mf_theme_saved', 'new' => 'option_theme_saved'));
	replace_option(array('old' => 'theme_source_style_url', 'new' => 'option_theme_source_style_url'));
	replace_option(array('old' => 'mf_database_optimized', 'new' => 'option_database_optimized'));

	mf_uninstall_plugin(array(
		'options' => array('setting_save_style', 'setting_compress', 'setting_responsiveness', 'setting_theme_recommendation', 'setting_html5_history', 'setting_splash_screen', 'setting_theme_disable_functionality'),
	));
}

function uninstall_theme_core()
{
	mf_uninstall_plugin(array(
		'uploads' => 'mf_theme_core',
		'options' => array('setting_theme_core_login', 'setting_display_post_meta', 'setting_scroll_to_top', 'setting_cookie_info', 'setting_404_page', 'setting_maintenance_page', 'setting_send_email_on_draft', 'option_theme_saved', 'option_theme_version', 'theme_source_version', 'option_theme_source_style_url', 'option_database_optimized', 'option_uploads_fixed', 'option_uploads_done', 'setting_maintenance_page_temp'),
	));
}