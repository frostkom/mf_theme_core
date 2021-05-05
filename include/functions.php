<?php

function get_image_fallback()
{
	return "<img src='".get_site_url()."/wp-content/plugins/mf_theme_core/images/blank.svg' class='image_fallback'>";
}

/*function add_css_selectors($array = array())
{
	if(is_plugin_active("css-hero-ce/css-hero-main.php"))
	{
		$setting_theme_css_hero = get_option('setting_theme_css_hero');
		$arr_setting_theme_css_hero = explode("\n", $setting_theme_css_hero);

		$added = false;

		foreach($array as $selector)
		{
			if(!in_array($selector, $arr_setting_theme_css_hero))
			{
				$arr_setting_theme_css_hero[] = $selector;

				$added = true;
			}
		}

		if(true == $added)
		{
			$setting_theme_css_hero = implode("\n", $arr_setting_theme_css_hero);

			update_option('setting_theme_css_hero', $setting_theme_css_hero);
		}
	}
}*/

function get_menu_type_for_select()
{
	global $obj_theme_core;

	$arr_data = array(
		'' => "-- ".__("Choose Here", 'lang_theme_core')." --",
	);

	$arr_menus = wp_get_nav_menus();

	if(count($arr_menus) > 0)
	{
		$arr_data['opt_start_menu'] = __("Regular", 'lang_theme_core');

			foreach($arr_menus as $menu)
			{
				if($menu->count > 0)
				{
					$arr_data[$menu->slug] = $menu->name;
				}
			}

		$arr_data['opt_end_menu'] = "";
	}

	$arr_data['opt_start_advanced'] = __("Advanced", 'lang_theme_core');

		if(!isset($arr_data['main-menu']))
		{
			$arr_data['main'] = __("Main", 'lang_theme_core');
		}

		if(!isset($arr_data['secondary-menu']))
		{
			$arr_data['secondary'] = __("Secondary", 'lang_theme_core');
		}

		$arr_data['both'] = __("Main and Secondary Menu", 'lang_theme_core');
		$arr_data['slide'] = __("Slide in From Right", 'lang_theme_core');

	$arr_data['opt_end_advanced'] = "";

	return $arr_data;
}

function is_active_widget_area($widget)
{
	$is_active = is_active_sidebar($widget);

	if($is_active == false)
	{
		$sidebars_widgets = get_option('sidebars_widgets', array());

		if(isset($sidebars_widgets[$widget]) && (!is_array($sidebars_widgets[$widget]) || count($sidebars_widgets[$widget]) > 0))
		{
			$is_active = true;
		}
	}

	return $is_active;
}

function get_404_page()
{
	global $wpdb, $obj_theme_core;

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

	return "<article".(IS_ADMIN ? " class='get_404_page'" : "").">
		<h1>".$post_title."</h1>
		<section>".$post_content."</section>
	</article>";
}