<?php
/*
Plugin Name: MF Theme Core
Plugin URI: https://github.com/frostkom/mf_theme_core
Description: 
Version: 5.2.8
Author: Martin Fors
Author URI: http://frostkom.se
Text Domain: lang_theme_core
Domain Path: /lang

GitHub Plugin URI: frostkom/mf_theme_core
*/

define('DISALLOW_FILE_EDIT', true);

include_once("include/functions.php");

add_action('init', 'init_theme_core');

if(is_admin())
{
	register_activation_hook(__FILE__, 'activate_theme_core');
	register_uninstall_hook(__FILE__, 'uninstall_theme_core');

	add_action('wp_before_admin_bar_render', 'admin_bar_theme_core');
	add_action('admin_init', 'settings_theme_core');

	//add_action('customize_save_after', 'customize_save_theme_core');
}

else
{
	add_action('customize_preview_init', 'customize_preview_theme_core');

	add_action('get_header', 'header_theme_core', 0);
	add_action('wp_head', 'head_theme_core', 0);

	remove_action('wp_head', 'rest_output_link_wp_head');
	remove_action('wp_head', 'wlwmanifest_link');
	remove_action('wp_head', 'rsd_link');
	remove_action('wp_head', 'wp_oembed_add_discovery_links');
	remove_action('wp_head', 'wp_generator');
	remove_action('wp_head', 'rel_canonical');
	remove_action('wp_head', 'print_emoji_detection_script', 7);
	remove_action('wp_print_styles', 'print_emoji_styles');
	//remove_action('template_redirect', 'rest_output_link_header', 11, 0);

	add_filter('wp_nav_menu_args', 'nav_args_theme_core');

	add_action('wp_footer', 'footer_theme_core');
}

add_action('init_style', 'init_style_theme_core');

load_plugin_textdomain('lang_theme_core', false, dirname(plugin_basename(__FILE__)).'/lang/');

function activate_theme_core()
{
	mf_uninstall_plugin(array(
		'options' => array('eg_setting_responsiveness', 'eg_setting_strip_domain', 'eg_setting_compress'),
	));
}

function uninstall_theme_core()
{
	mf_uninstall_plugin(array(
		'options' => array('setting_theme_core_login', 'setting_html5_history', 'setting_save_style', 'setting_scroll_to_top', 'setting_compress', 'setting_responsiveness', 'setting_strip_domain', 'setting_cookie_info', 'setting_404_page'),
	));
}