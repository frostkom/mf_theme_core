<?php

function get_image_fallback()
{
	return "<img src='".get_site_url()."/wp-content/plugins/mf_theme_core/images/blank.svg' class='image_fallback'>";
}

function add_css_selectors($array = array())
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
}

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

		$options_params[] = array('type' => 'color', 'id' => 'button_color', 'title' => __("Button Color", 'lang_theme_core'), 'default' => get_option('setting_webshop_color_button', "#000000"));
			$options_params[] = array('type' => 'color', 'id' => 'button_text_color', 'title' => " - ".__("Button Text Color", 'lang_theme_core'), 'default' => get_option('setting_webshop_text_color_button', "#ffffff"));
		$options_params[] = array('type' => 'color', 'id' => 'button_color_secondary', 'title' => __("Button Color", 'lang_theme_core')." (".__("Secondary", 'lang_theme_core').")", 'default' => get_option('setting_webshop_color_button_2', "#c78e91"));
			$options_params[] = array('type' => 'color', 'id' => 'button_text_color_secondary', 'title' => " - ".__("Button Text Color", 'lang_theme_core')." (".__("Secondary", 'lang_theme_core').")", 'default' => "#ffffff");
		$options_params[] = array('type' => 'color', 'id' => 'button_color_negative', 'title' => __("Button Color", 'lang_theme_core')." (".__("Negative", 'lang_theme_core').")", 'default' => get_option('setting_color_button_negative', "#e47676"));
			$options_params[] = array('type' => 'color', 'id' => 'button_text_color_negative', 'title' => " - ".__("Button Text Color", 'lang_theme_core')." (".__("Negative", 'lang_theme_core').")", 'default' => "#ffffff");
			//$options_params[] = array('type' => 'color', 'id' => 'button_color_hover', 'title' => " - ".__("Button Color", 'lang_theme_core')." (".__("Hover", 'lang_theme_core').")", 'show_if' => 'button_color');
		$options_params[] = array('type' => 'color', 'id' => 'button_color_negative', 'title' => __("Button Color", 'lang_theme_core')." (".__("Negative", 'lang_theme_core').")", 'default' => get_option('setting_color_button_negative', "#e47676"));

		$options_params[] = array('type' => 'text', 'id' => 'form_border_radius', 'title' => __("Border Radius", 'lang_theme_core')." (".__("Form Fields", 'lang_theme_core').")", 'default' => ".3em");

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
		$options_params[] = array('type' => 'text', 'id' => 'heading_margin_h4', 'title' => __("Margin", 'lang_theme_core')." (H4)");
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
		if(is_active_widget_area('widget_sidebar_left') || is_active_widget_area('widget_after_content') || is_active_widget_area('widget_sidebar'))
		{
			$options_params[] = array('category' => __("Aside", 'lang_theme_core'), 'id' => 'mf_theme_aside');
				$options_params[] = array('type' => 'text', 'id' => 'aside_width', 'title' => __("Width", 'lang_theme_core'), 'default' => "28%");
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

/*function init_theme_core()
{
	if(!is_admin())
	{
		if(isset($_REQUEST['action']) && ('posts_logout' == $_REQUEST['action']))
		{
			check_admin_referer('posts_logout');
			setcookie('wp-postpass_'.COOKIEHASH, '', strtotime("-1 month"), COOKIEPATH);

			do_log("Did remove cookie");

			wp_redirect(wp_get_referer());
			die();
		}
	}
}*/

function header_theme_core()
{
	require_user_login();
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

function body_class_theme_core($classes)
{
	$classes[] = "is_site";

	return $classes;
}

function get_menu_type_for_select()
{
	return array(
		'' => "-- ".__("Choose Here", 'lang_theme_core')." --",
		'main' => __("Main menu", 'lang_theme_core'),
		'secondary' => __("Secondary", 'lang_theme_core'),
		'both' => __("Main and Secondary menues", 'lang_theme_core'),
		'slide' => __("Slide in from right", 'lang_theme_core'),
	);
}

function search_form_theme_core($html)
{
	return "<form method='get' action='".esc_url(home_url('/'))."' class='mf_form'>"
		.show_textfield(array('type' => 'search', 'name' => 's', 'value' => get_search_query(), 'placeholder' => __("Search here", 'lang_theme_core'), 'xtra' => " autocomplete='off'"))
		."<div class='form_button'>"
			.show_button(array('text' => __("Search", 'lang_theme_core')))
		."</div>
	</form>";
}

function password_form_theme_core()
{
	return "<form action='".site_url('wp-login.php?action=postpass', 'login_post')."' method='post' class='mf_form'>
		<p>".__("To view this protected post, enter the password below", 'lang_theme_core')."</p>"
		.show_password_field(array('name' => "post_password", 'placeholder' => __("Password", 'lang_theme_core'), 'maxlength' => 20))
		."<div class='form_button'>"
			.show_button(array('text' => __("Submit", 'lang_theme_core')))
		."</div>
	</form>";
}

function the_content_protected_theme_core($html)
{
	global $post;

    if(post_password_required())
	{
		if(!isset($post->post_password))
		{
			do_log("post_password did not exist even though it was a protected page");
		}

		$html = password_form_theme_core();
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
			$file = $theme_dir_name."_".str_replace(array(".", "/"), "_", get_site_url_clean(array('trim' => "/")))."_".date("YmdHi").".json";

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
			list($strFileContent, $headers) = get_url_content($strFileUrl, true);
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
										.show_button(array('name' => "btnThemeRestore", 'text' => __("Restore", 'lang_theme_core')))
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
										.show_button(array('name' => "btnThemeBackup", 'text' => __("Save", 'lang_theme_core')))
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

function nav_args_theme_core($args)
{
	if(isset($args['container_override']) && $args['container_override'] == false){}

	else if(!isset($args['container']) || $args['container'] == '' || $args['container'] == 'div')
	{
		$args['container'] = "nav";
	}

	return $args;
}

function settings_theme_core()
{
	$options_area = __FUNCTION__;

	add_settings_section($options_area, "", $options_area."_callback", BASE_OPTIONS_PAGE);

	$arr_settings = array();

	$arr_settings['setting_no_public_pages'] = __("Always redirect visitors to the login page", 'lang_theme_core');

	if(get_option('setting_no_public_pages') != 'yes')
	{
		$arr_settings['setting_theme_core_login'] = __("Require login for public site", 'lang_theme_core');

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

		if(is_plugin_active("mf_analytics/index.php") && (get_option('setting_analytics_google') != '' || get_option('setting_analytics_clicky') != ''))
		{
			$arr_settings['setting_cookie_info'] = __("Cookie information", 'lang_theme_core');
		}

		else
		{
			delete_option('setting_cookie_info');
		}

		$arr_settings['setting_404_page'] = __("404 Page", 'lang_theme_core');
		$arr_settings['setting_maintenance_page'] = __("Maintenance Page", 'lang_theme_core');

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

		$obj_theme_core = new mf_theme_core();
		$obj_theme_core->get_params();

		if($obj_theme_core->options['style_source'] != '')
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

	show_settings_fields(array('area' => $options_area, 'settings' => $arr_settings));
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

	$arr_data = array(
		//'' => "-- ".__("Choose Here", 'lang_theme_core')." --",
	);

	$arr_data['open'] = __("Yes", 'lang_theme_core');

	if(get_comment_status_amount('closed') > 0)
	{
		$arr_data['open_all'] = __("Yes", 'lang_theme_core')." (".__("And Change Setting on All Posts", 'lang_theme_core').")";
	}

	$arr_data['closed'] = __("No", 'lang_theme_core');

	if(get_comment_status_amount('open') > 0)
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

		update_option('default_comment_status', $option, 'no');
	}

	echo show_select(array('data' => get_comment_status_for_select($option), 'name' => $setting_key, 'value' => $option));
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

	echo show_select(array('data' => $arr_data, 'name' => $setting_key, 'value' => $option, 'suffix' => "<a href='".admin_url("post-new.php?post_type=page")."'><i class='fa fa-lg fa-plus'></i></a>", 'description' => __("The content from this page will be displayed on top of the page until the visitor clicks to accept the use of cookies", 'lang_theme_core')));
}

function setting_404_page_callback()
{
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option($setting_key);

	$arr_data = array();
	get_post_children(array('add_choose_here' => true), $arr_data);

	$post_title = __("404", 'lang_theme_core');
	$post_content = __("Oops! The page that you were looking for does not seam to exist. If you think that it should exist, please let us know.", 'lang_theme_core');

	echo show_select(array('data' => $arr_data, 'name' => $setting_key, 'value' => $option, 'suffix' => "<a href='".admin_url("post-new.php?post_type=page&post_title=".$post_title."&content=".$post_content)."'><i class='fa fa-lg fa-plus'></i></a>", 'description' => (!($option > 0) ? "<span class='display_warning'><i class='fa fa-warning yellow'></i></span> " : "").__("This page will be displayed instead of the default 404 page", 'lang_theme_core')));
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

	echo show_select(array('data' => $arr_data, 'name' => $setting_key, 'value' => $option, 'suffix' => "<a href='".admin_url("post-new.php?post_type=page&post_title=".$post_title."&content=".$post_content)."'><i class='fa fa-lg fa-plus'></i></a>", 'description' => (!($option > 0) ? "<span class='display_warning'><i class='fa fa-warning yellow'></i></span> " : "").__("This page will be displayed when the website is updating", 'lang_theme_core')));

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

				$result = get_sites(array('order' => 'DESC'));

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
	$setting_key = get_setting_key(__FUNCTION__);
	$option = get_option_or_default($setting_key, 7);

	$option_database_optimized = get_option('option_cache_prepopulated');

	if($option_database_optimized > DEFAULT_DATE)
	{
		$populate_next = format_date(date("Y-m-d H:i:s", strtotime($option_database_optimized." +".$option." day")));

		$description = sprintf(__("The optimization was last run %s and will be run again %s", 'lang_theme_core'), format_date($option_database_optimized), $populate_next);
	}

	else
	{
		$description = sprintf(__("The optimization has not been run yet but will be %s", 'lang_theme_core'), get_next_cron());
	}

	echo show_textfield(array('type' => 'number', 'name' => $setting_key, 'value' => $option, 'xtra' => "min='1' max='30'", 'suffix' => __("days", 'lang_theme_core'), 'description' => $description))
	."<div class='form_buttons'>"
		.show_button(array('type' => 'button', 'name' => 'btnOptimizeTheme', 'text' => __("Optimize Now", 'lang_theme_core'), 'class' => 'button-secondary'))
	."</div>
	<div id='optimize_debug'></div>";
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

function is_site_public()
{
	return (get_option('blog_public') == 1 && get_option('setting_no_public_pages') != 'yes' && get_option('setting_theme_core_login') != 'yes');
}

function get_post_types_for_metabox()
{
	$arr_data = array();

	foreach(get_post_types(array('public' => true), 'objects') as $post_type)
	{
		if(!in_array($post_type->name, array('attachment')))
		{
			$arr_data[] = $post_type->name;
		}
	}

	return $arr_data;
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

function default_scripts_theme_core(&$scripts)
{
	$scripts->remove('jquery');
	$scripts->add('jquery', false, array('jquery-core'), '1.12.4');
}

function print_scripts_theme_core()
{
	wp_deregister_script('wp-embed');
}

function admin_bar_theme_core()
{
	global $wp_admin_bar;

	if(IS_ADMIN)
	{
		$site_url = get_site_url();

		if(get_option('setting_no_public_pages') == 'yes')
		{
			$wp_admin_bar->remove_menu('site-name');

			$color = "color_red";

			$title = __("No public pages", 'lang_theme_core');
		}

		else if(get_option('setting_theme_core_login') == 'yes')
		{
			$title = "<a href='".$site_url."' class='color_red'>".__("Requires login", 'lang_theme_core')."</a>";
		}

		else if(get_option('blog_public') == 0)
		{
			$title = "<a href='".$site_url."' class='color_yellow'>".__("No index", 'lang_theme_core')."</a>";
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

function setup_theme_core()
{
	add_post_type_support('page', 'excerpt');

	remove_action('wp_head', 'wp_print_scripts');
	remove_action('wp_head', 'wp_print_head_scripts', 9);
	remove_action('wp_head', 'wp_enqueue_scripts', 1);
	add_action('wp_footer', 'wp_print_scripts', 5);
	add_action('wp_footer', 'wp_enqueue_scripts', 5);
	add_action('wp_footer', 'wp_print_head_scripts', 5);
}

function customize_save_theme_core()
{
	update_option('option_theme_saved', date("Y-m-d H:i:s"), 'no');
	update_option('option_theme_version', get_option('option_theme_version', 0) + 1, 'no');
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