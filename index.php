<?php
/*
Plugin Name: MF Theme Core
Plugin URI: https://github.com/frostkom/mf_theme_core
Description: 
Version: 6.4.1
Author: Martin Fors
Author URI: http://frostkom.se
Text Domain: lang_theme_core
Domain Path: /lang

GitHub Plugin URI: frostkom/mf_theme_core
*/

define('DISALLOW_FILE_EDIT', true);

include_once("include/classes.php");
include_once("include/functions.php");

if(is_admin())
{
	register_activation_hook(__FILE__, 'activate_theme_core');
	register_uninstall_hook(__FILE__, 'uninstall_theme_core');

	add_action('wp_before_admin_bar_render', 'admin_bar_theme_core');
	add_action('admin_init', 'settings_theme_core');

	add_filter('manage_page_posts_columns', 'column_header_theme_core', 5);
	add_action('manage_page_posts_custom_column', 'column_cell_theme_core', 5, 2);
	add_filter('manage_post_posts_columns', 'column_header_theme_core', 5);
	add_action('manage_post_posts_custom_column', 'column_cell_theme_core', 5, 2);
}

else
{
	add_action('init', 'init_theme_core');

	//add_action('customize_preview_init', 'customize_preview_theme_core');

	add_action('get_header', 'header_theme_core', 0);
	add_action('wp_head', 'head_theme_core', 0);

	remove_action('wp_head', 'rest_output_link_wp_head');
	remove_action('wp_head', 'wlwmanifest_link');
	remove_action('wp_head', 'rsd_link');
	remove_action('wp_head', 'wp_oembed_add_discovery_links');
	remove_action('wp_head', 'wp_generator');
	remove_action('wp_head', 'rel_canonical');
	remove_action('wp_head', 'feed_links', 2);
	remove_action('wp_head', 'feed_links_extra', 3);
	remove_action('wp_head', 'print_emoji_detection_script', 7);
	remove_action('wp_print_styles', 'print_emoji_styles');
	//remove_action('template_redirect', 'rest_output_link_header', 11, 0);

	//Disable oEmbed
	/*remove_action('wp_head', 'rest_output_link_wp_head', 10);
	remove_action('template_redirect', 'rest_output_link_header', 11, 0);
	remove_action('wp_head', 'wp_oembed_add_host_js');
	remove_action('rest_api_init', 'wp_oembed_register_route');
	remove_filter('oembed_dataparse', 'wp_filter_oembed_result', 10);

	//Disbale more oEmbed
	remove_action('wp_head', 'wp_oembed_add_discovery_links');
	add_filter('rewrite_rules_array', 'disable_embeds_rewrites');*/

	add_filter('wp_nav_menu_args', 'nav_args_theme_core');

	add_filter('get_search_form', 'search_form_theme_core');

	add_action('wp_print_styles', 'print_styles_theme_core', 1);
	add_filter('wp_default_scripts', 'default_scripts_theme_core');
	add_action('wp_print_scripts', 'print_scripts_theme_core', 1);
	add_action('wp_footer', 'footer_theme_core');
}

add_action('init_style', 'init_style_theme_core');
add_action('after_setup_theme', 'setup_theme_core');
add_action('widgets_init', 'widgets_theme_core');

load_plugin_textdomain('lang_theme_core', false, dirname(plugin_basename(__FILE__)).'/lang/');

function activate_theme_core()
{
	global $wpdb;

	if(is_plugin_active('meta-description/meta-description.php'))
	{
		$i = 0;

		$arr_data = array();
		get_post_children(array('post_type' => 'page'), $arr_data);

		foreach($arr_data as $post_id => $post_title)
		{
			$meta_description = get_post_meta($post_id, 'meta_description', true);

			if($meta_description != '')
			{
				$post_excerpt = $wpdb->get_results($wpdb->prepare("SELECT post_excerpt FROM ".$wpdb->posts." WHERE ID = '%d'", $post_id));

				if($post_excerpt == '')
				{
					$wpdb->query($wpdb->prepare("UPDATE ".$wpdb->posts." SET post_excerpt = %s WHERE ID = '%d'", $meta_description, $post_id));

					$i++;
				}
			}
		}

		if($i == 0)
		{
			do_log(__("All Meta Descriptions have been moved to Excerpt so you can remove the plugin Meta Description", 'lang_theme_core'));
		}

		else
		{
			do_log(sprintf(__("I moved %d Meta Descriptions to excerpt", 'lang_theme_core'), $i));
		}
	}

	mf_uninstall_plugin(array(
		'options' => array('eg_setting_responsiveness', 'eg_setting_strip_domain', 'eg_setting_compress', 'setting_save_style', 'setting_compress'),
	));
}

function uninstall_theme_core()
{
	mf_uninstall_plugin(array(
		'options' => array('setting_theme_core_login', 'setting_html5_history', 'setting_scroll_to_top', 'setting_responsiveness', 'setting_strip_domain', 'setting_cookie_info', 'setting_404_page', 'setting_merge_css', 'setting_merge_js'),
	));
}