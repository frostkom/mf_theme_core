<?php

function get_image_fallback()
{
	return "<img src='".get_site_url()."/wp-content/plugins/mf_theme_core/images/blank.svg' class='image_fallback'>";
}

/*function add_css_selectors($array = array())
{
	if(is_plugin_active('css-hero-ce/css-hero-main.php'))
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

function get_params_theme_core()
{
	$options_params = array();

	$theme_dir_name = get_theme_dir_name();

	$options_params[] = array('category' => __("Generic", 'lang_theme_core'), 'id' => 'mf_theme_body');
		$options_params[] = array('type' => 'url', 'id' => 'style_source', 'title' => __("Get Updates From", 'lang_theme_core'));
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

	$options_params[] = array('category_end' => "");

	$options_params[] = array('category' => " - ".__("Forms", 'lang_theme_core'), 'id' => 'mf_theme_generic_forms');
		$options_params[] = array('type' => 'text', 'id' => 'form_border_radius', 'title' => __("Border Radius", 'lang_theme_core')." (".__("Fields", 'lang_theme_core').")", 'default' => ".3em");
		$options_params[] = array('type' => 'text', 'id' => 'form_button_border_radius', 'title' => __("Border Radius", 'lang_theme_core')." (".__("Buttons", 'lang_theme_core').")", 'default' => ".3em");
		$options_params[] = array('type' => 'text', 'id' => 'form_button_padding', 'title' => __("Padding", 'lang_theme_core')." (".__("Buttons", 'lang_theme_core').")");

		$options_params[] = array('type' => 'text', 'id' => 'button_size', 'title' => __("Font Size", 'lang_theme_core'), 'default' => (function_exists('is_plugin_active') && is_plugin_active('mf_webshop/index.php') ? "1.3em" : ''));

		$options_params[] = array('type' => 'color', 'id' => 'button_color', 'title' => __("Button Color", 'lang_theme_core'), 'default' => "#000000");
			//$options_params[] = array('type' => 'color', 'id' => 'button_text_color', 'title' => " - ".__("Button Text Color", 'lang_theme_core'), 'default' => "#ffffff");
		$options_params[] = array('type' => 'color', 'id' => 'button_color_secondary', 'title' => __("Button Color", 'lang_theme_core')." (".__("Secondary", 'lang_theme_core').")", 'default' => "#c78e91");
			//$options_params[] = array('type' => 'color', 'id' => 'button_text_color_secondary', 'title' => " - ".__("Button Text Color", 'lang_theme_core')." (".__("Secondary", 'lang_theme_core').")", 'default' => "#ffffff");
		$options_params[] = array('type' => 'color', 'id' => 'button_color_negative', 'title' => __("Button Color", 'lang_theme_core')." (".__("Negative", 'lang_theme_core').")", 'default' => get_option('setting_color_button_negative', "#e47676"));
			//$options_params[] = array('type' => 'color', 'id' => 'button_text_color_negative', 'title' => " - ".__("Button Text Color", 'lang_theme_core')." (".__("Negative", 'lang_theme_core').")", 'default' => "#ffffff");
			//$options_params[] = array('type' => 'color', 'id' => 'button_color_hover', 'title' => " - ".__("Button Color", 'lang_theme_core')." (".__("Hover", 'lang_theme_core').")", 'show_if' => 'button_color');
		$options_params[] = array('type' => 'color', 'id' => 'button_color_negative', 'title' => __("Button Color", 'lang_theme_core')." (".__("Negative", 'lang_theme_core').")", 'default' => "#e47676");
	$options_params[] = array('category_end' => "");

	$options_params[] = array('category' => __("Header", 'lang_theme_core'), 'id' => 'mf_theme_header');
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

	if($theme_dir_name == 'mf_theme')
	{
		$options_params[] = array('category' => " - ".__("Search", 'lang_theme_core'), 'id' => 'mf_theme_header_search');
			$options_params[] = array('type' => 'color', 'id' => 'search_color', 'title' => __("Color", 'lang_theme_core'));
			$options_params[] = array('type' => 'text', 'id' => 'search_size', 'title' => __("Font Size", 'lang_theme_core'), 'default' => "1.4em");
		$options_params[] = array('category_end' => "");
	}

	$options_params[] = array('category' => " - ".__("Logo", 'lang_theme_core'), 'id' => 'mf_theme_logo');
		$options_params[] = array('type' => 'text', 'id' => 'logo_padding', 'title' => __("Padding", 'lang_theme_core')); //, 'default' => '.4em 0'
		$options_params[] = array('type' => 'image', 'id' => 'header_logo', 'title' => __("Image", 'lang_theme_core'));
		$options_params[] = array('type' => 'float', 'id' => 'logo_float', 'title' => __("Alignment", 'lang_theme_core'), 'default' => 'left');
		$options_params[] = array('type' => 'text', 'id' => 'logo_width', 'title' => __("Width", 'lang_theme_core'), 'default' => '14em');
		$options_params[] = array('type' => 'image', 'id' => 'header_mobile_logo', 'title' => __("Image", 'lang_theme_core')." (".__("Mobile", 'lang_theme_core').")", 'show_if' => 'mobile_breakpoint');
		$options_params[] = array('type' => 'text', 'id' => 'logo_width_mobile', 'title' => __("Width", 'lang_theme_core')." (".__("Mobile", 'lang_theme_core').")", 'default' => '20em');
		$options_params[] = array('type' => 'font', 'id' => 'logo_font', 'title' => __("Font", 'lang_theme_core'), 'hide_if' => 'header_logo');
		$options_params[] = array('type' => 'text', 'id' => 'logo_font_size', 'title' => __("Font Size", 'lang_theme_core'), 'default' => "3rem");
			$options_params[] = array('type' => 'text', 'id' => 'slogan_font_size', 'title' => __("Font Size", 'lang_theme_core')." (".__("Tagline", 'lang_theme_core').")", 'default' => ".4em");
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
		$options_params[] = array('type' => 'color', 'id' => 'nav_color', 'title' => __("Text Color", 'lang_theme_core'));
			$options_params[] = array('type' => 'color', 'id' => 'nav_color_hover', 'title' => __("Text Color", 'lang_theme_core')." (".__("Hover", 'lang_theme_core').")", 'show_if' => 'nav_color');
		$options_params[] = array('type' => 'text', 'id' => 'nav_link_padding', 'title' => __("Link Padding", 'lang_theme_core'), 'default' => "1em");

		if($theme_dir_name == 'mf_theme')
		{
			$options_params[] = array('type' => 'color', 'id' => 'nav_underline_color_hover', 'title' => " - ".__("Underline Color", 'lang_theme_core')." (".__("Hover", 'lang_theme_core').")", 'show_if' => 'nav_color');
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

			$options_params[] = array('type' => 'text', 'id' => 'slide_nav_link_padding', 'title' => __("Link Padding", 'lang_theme_core'), 'default' => "1.5em 1em 1em");
			$options_params[] = array('type' => 'color', 'id' => 'slide_nav_bg', 'title' => __("Background", 'lang_theme_core'), 'default' => "#fff");
				$options_params[] = array('type' => 'color', 'id' => 'slide_nav_bg_hover', 'title' => " - ".__("Background", 'lang_theme_core')." (".__("Hover", 'lang_theme_core').")", 'show_if' => 'slide_nav_bg');
			$options_params[] = array('type' => 'color', 'id' => 'slide_nav_color', 'title' => __("Text Color", 'lang_theme_core'));
				$options_params[] = array('type' => 'color', 'id' => 'slide_nav_color_hover', 'title' => " - ".__("Text Color", 'lang_theme_core')." (".__("Hover", 'lang_theme_core').")", 'show_if' => 'slide_nav_color');
				$options_params[] = array('type' => 'color', 'id' => 'slide_nav_color_current', 'title' => " - ".__("Text Color", 'lang_theme_core')." (".__("Current", 'lang_theme_core').")");

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
			$options_params[] = array('type' => 'text', 'id' => 'heading_margin', 'title' => __("Margin", 'lang_theme_core')." (H1)");
			$options_params[] = array('type' => 'text', 'id' => 'heading_padding', 'title' => __("Padding", 'lang_theme_core')." (H1)", 'default' => ".3em 0 .5em");
		}

		/* H2 */
		##################
		$options_params[] = array('type' => 'text', 'id' => 'heading_margin_h2', 'title' => __("Margin", 'lang_theme_core')." (H2)", 'default' => "0 0 .5em");
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
		##################

		/* H3 */
		##################
		$options_params[] = array('type' => 'text', 'id' => 'heading_margin_h3', 'title' => __("Margin", 'lang_theme_core')." (H3)");

		if($theme_dir_name == 'mf_theme')
		{
			$options_params[] = array('type' => 'font', 'id' => 'heading_font_h3', 'title' => __("Font", 'lang_theme_core')." (H3)");
			$options_params[] = array('type' => 'text', 'id' => 'heading_size_h3', 'title' => __("Font Size", 'lang_theme_core')." (H3)", 'default' => "1.2em");
		}

		if($theme_dir_name == 'mf_parallax')
		{
			$options_params[] = array('type' => 'text', 'id' => 'heading_font_size_h3', 'title' => __("Font Size", 'lang_theme_core')." (H3)");
		}

		$options_params[] = array('type' => 'weight', 'id' => 'heading_weight_h3', 'title' => __("Weight", 'lang_theme_core')." (H3)");
		##################

		/* H4 */
		##################
		$options_params[] = array('type' => 'text', 'id' => 'heading_margin_h4', 'title' => __("Margin", 'lang_theme_core')." (H4)", 'default' => ".5em 0");
		$options_params[] = array('type' => 'text', 'id' => 'heading_font_size_h4', 'title' => __("Font Size", 'lang_theme_core')." (H4)");
		$options_params[] = array('type' => 'weight', 'id' => 'heading_weight_h4', 'title' => __("Weight", 'lang_theme_core')." (H4)");
		##################

		/* H5 */
		##################
		$options_params[] = array('type' => 'text', 'id' => 'heading_margin_h5', 'title' => __("Margin", 'lang_theme_core')." (H5)");
		$options_params[] = array('type' => 'text', 'id' => 'heading_font_size_h5', 'title' => __("Font Size", 'lang_theme_core')." (H5)");
		$options_params[] = array('type' => 'weight', 'id' => 'heading_weight_h5', 'title' => __("Weight", 'lang_theme_core')." (H5)");
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
		$options_params[] = array('type' => 'text', 'id' => 'section_line_height', 'title' => __("Line Height", 'lang_theme_core'), 'default' => "1.5");
		$options_params[] = array('type' => 'text', 'id' => 'section_margin', 'title' => __("Margin", 'lang_theme_core'), 'default' => "0 0 2em");

		if($theme_dir_name == 'mf_parallax')
		{
			$options_params[] = array('type' => 'text', 'id' => 'quote_size', 'title' => __("Quote Size", 'lang_theme_core'));
		}

		if($theme_dir_name == 'mf_theme')
		{
			$options_params[] = array('type' => 'text', 'id' => 'section_padding', 'title' => __("Padding", 'lang_theme_core'));
			$options_params[] = array('type' => 'text', 'id' => 'section_margin_between', 'title' => __("Margin between Content", 'lang_theme_core'), 'default' => "1em");
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
		$options_params[] = array('type' => 'textarea', 'id' => 'custom_css_all', 'title' => __("Custom CSS", 'lang_theme_core'));
		$options_params[] = array('type' => 'textarea', 'id' => 'custom_css_mobile', 'title' => __("Custom CSS", 'lang_theme_core')." (".__("Mobile", 'lang_theme_core').")", 'show_if' => 'mobile_breakpoint');
	$options_params[] = array('category_end' => "");

	return $options_params;
}

function get_menu_type_for_select()
{
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

		$arr_data['both'] = __("Main & Secondary Menu", 'lang_theme_core');
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

function get_theme_dir_name()
{
	return str_replace(get_theme_root()."/", "", get_template_directory());
}

function get_theme_slug()
{
	$theme_name = wp_get_theme();

	return sanitize_title($theme_name);
}

function get_options_page_theme_core()
{
	global $done_text, $error_text;

	$out = "";

	$theme_dir_name = get_theme_dir_name();

	$strFileUrl = check_var('strFileUrl');
	$strFileName = check_var('strFileName');
	$strFileContent = isset($_REQUEST['strFileContent']) ? $_REQUEST['strFileContent'] : "";

	list($upload_path, $upload_url) = get_uploads_folder($theme_dir_name);

	$obj_theme_core = new mf_theme_core();
	$obj_theme_core->get_params();

	if(isset($_POST['btnThemeBackup']) && wp_verify_nonce($_POST['_wpnonce_theme_backup'], 'theme_backup'))
	{
		if(count($obj_theme_core->options) > 0)
		{
			$file_base = $theme_dir_name."_".str_replace(array(".", "/"), "_", get_site_url_clean(array('trim' => "/")));
			$file = prepare_file_name($file_base).".json";

			$success = set_file_content(array('file' => $upload_path.$file, 'mode' => 'a', 'content' => json_encode($obj_theme_core->options)));

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

				$done_text = __("I restored the theme backup for you", 'lang_theme_core');

				update_option('option_theme_saved', date("Y-m-d H:i:s"), 'no');
				delete_option('option_theme_source_style_url');

				$strFileContent = "";
			}

			else
			{
				$error_text = __("There is something wrong with the source to restore", 'lang_theme_core')." (".htmlspecialchars($strFileContent)." -> ".var_export($json, true).")";
			}
		}
	}

	else if(isset($_GET['btnThemeDelete']) && wp_verify_nonce($_GET['_wpnonce_theme_delete'], 'theme_delete_'.$strFileName))
	{
		unlink($upload_path.$strFileName);
		do_log("Removed Theme File: ".$upload_path.$strFileName);

		$done_text = __("The file was deleted successfully", 'lang_theme_core');
	}

	else
	{
		if($obj_theme_core->options['style_source'] != '')
		{
			$style_source = remove_protocol(array('url' => $obj_theme_core->options['style_source'], 'clean' => true, 'trim' => true));

			$option_theme_source_style_url = get_option('option_theme_source_style_url');

			if($option_theme_source_style_url != '')
			{
				$error_text = sprintf(__("The theme at %s has got a newer version of saved style which can be %srestored here%s", 'lang_theme_core'), $style_source, "<a href='".admin_url("themes.php?page=theme_options&btnThemeRestore&strFileUrl=".$option_theme_source_style_url)."'>", "</a>");
			}
		}
	}

	$out .= "<div class='wrap'>
		<h2>".__("Theme Backup", 'lang_theme_core')."</h2>"
		.get_notification();

		if($upload_path != '')
		{
			$style_source = trim($obj_theme_core->options['style_source'], "/");
			$is_allowed_to_backup = $style_source == '' || $style_source == get_site_url();

			$out .= "<div id='poststuff'>
				<div id='post-body' class='columns-2'>
					<div id='post-body-content'>";

						$arr_backups = get_previous_backups_list($upload_path);
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

										$out .= "<tr".($style_source != get_site_url() && $file_time > $option_theme_saved ? " class='green'" : "").">
											<td>"
												.$arr_backups[$i]['name']
												."<div class='row-actions'>
													<a href='".$upload_url.$file_name."'>".__("Download", 'lang_theme_core')."</a>
													 | <a href='".admin_url("themes.php?page=theme_options&btnThemeRestore&strFileName=".$file_name)."'>".__("Restore", 'lang_theme_core')."</a>";

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

function get_params_for_select()
{
	$arr_data = array();

	$options_params = get_params_theme_core();
	$arr_theme_mods = get_theme_mods();

	foreach($options_params as $param_key => $param)
	{
		if(isset($param['category']))
		{
			$arr_data['opt_start_'.$param['id']] = $param['category'];
		}

		else if(isset($param['category_end']))
		{
			$arr_data['opt_end'] = "";
		}

		else
		{
			$id = $param['id'];
			$title = $param['title'];

			if(isset($arr_theme_mods[$id]) && $arr_theme_mods[$id] != '')
			{
				$arr_data[$id] = $title;
			}
		}
	}

	return $arr_data;
}

function is_site_public()
{
	return (get_option('blog_public') == 1 && get_option('setting_no_public_pages') != 'yes' && get_option('setting_theme_core_login') != 'yes');
}

function get_post_types_for_metabox($data = array())
{
	if(!isset($data['public'])){		$data['public'] = true;}

	$arr_data = array();

	foreach(get_post_types($data, 'objects') as $post_type)
	{
		if(!in_array($post_type->name, array('attachment')))
		{
			$arr_data[] = $post_type->name;
		}
	}

	return $arr_data;
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

function get_search_theme_core($data = array())
{
	if(!isset($data['placeholder']) || $data['placeholder'] == ''){		$data['placeholder'] = __("Search for", 'lang_theme_core');}
	if(!isset($data['animate']) || $data['animate'] == ''){				$data['animate'] = 'yes';}

	return "<form action='".get_site_url()."' method='get' class='searchform mf_form".($data['animate'] == 'yes' ? " search_animate" : "")."'>"
		.show_textfield(array('type' => 'search', 'name' => 's', 'value' => check_var('s'), 'placeholder' => $data['placeholder'], 'xtra' => " autocomplete='off'"))
		."<i class='fa fa-search'></i>"
	."</form>";
}