<?php

class widget_theme_core_news extends WP_Widget
{
	function __construct()
	{
		$widget_ops = array(
			'classname' => 'theme_news',
			'description' => __("Display News/Posts", 'lang_theme_core')
		);

		$control_ops = array('id_base' => 'theme-news-widget');

		parent::__construct('theme-news-widget', __("News", 'lang_theme_core'), $widget_ops, $control_ops);
	}

	function widget($args, $instance)
	{
		global $wpdb;

		extract($args);

		$arr_news = array();

		if(!($instance['news_amount'] > 0))
		{
			$instance['news_amount'] = 3;
		}

		$result = $wpdb->get_results("SELECT ID, post_title FROM ".$wpdb->posts." WHERE post_type = 'post' AND post_status = 'publish' ORDER BY post_date DESC LIMIT 0, ".$instance['news_amount']);

		if($wpdb->num_rows > 0)
		{
			$post_thumbnail_size = 'large'; //$wpdb->num_rows > 2 ? 'medium' : 

			foreach($result as $post)
			{
				$post_id = $post->ID;
				$post_title = $post->post_title;

				$post_url = get_permalink($post_id);

				$post_url_start = "<a href='".$post_url."'>";
				$post_url_end = "</a>";

				$post_thumbnail = "";

				if(has_post_thumbnail($post_id))
				{
					$post_thumbnail = get_the_post_thumbnail($post_id, $post_thumbnail_size);
				}

				if($post_thumbnail != '')
				{
					$arr_news[$post_id] = array(
						'title' => $post_title,
						'url' => $post_url,
						'image' => $post_thumbnail,
					);
				}
			}
		}

		if(count($arr_news) > 0)
		{
			echo $before_widget;

				if($instance['news_title'] != '')
				{
					echo $before_title
						.$instance['news_title']
					.$after_title;
				}

				echo "<div class='section'>
					<ul".($arr_news > 2 ? "" : " class='allow_expand'").">";

						foreach($arr_news as $news)
						{
							echo "<li>
								<div class='image'><a href='".$news['url']."'>".$news['image']."</a></div>
								<h4>".$news['title']."</h4>
							</li>";
						}

					echo "</ul>
				</div>"
			.$after_widget;
		}
	}

	function update($new_instance, $old_instance)
	{
		$instance = $old_instance;

		$instance['news_title'] = strip_tags($new_instance['news_title']);
		$instance['news_amount'] = strip_tags($new_instance['news_amount']);

		return $instance;
	}

	function form($instance)
	{
		$defaults = array(
			'news_title' => "",
			'news_amount' => 3,
		);
		$instance = wp_parse_args((array)$instance, $defaults);

		echo "<div class='mf_form'>"
			.show_textfield(array('name' => $this->get_field_name('news_title'), 'text' => __("Title", 'lang_theme_core'), 'value' => $instance['news_title']))
			.show_textfield(array('type' => 'number', 'name' => $this->get_field_name('news_amount'), 'text' => __("Amount", 'lang_theme_core'), 'value' => $instance['news_amount']))
		."</div>";
	}
}

class widget_theme_core_search extends WP_Widget
{
	function __construct()
	{
		$widget_ops = array(
			'classname' => 'theme_search',
			'description' => __("Display Search Form", 'lang_theme_core')
		);

		$control_ops = array('id_base' => 'theme-search-widget');

		parent::__construct('theme-search-widget', __("Search", 'lang_theme_core'), $widget_ops, $control_ops);
	}

	function widget($args, $instance)
	{
		global $wpdb;

		extract($args);

		echo get_search_theme_core(array('placeholder' => $instance['search_placeholder']));
	}

	function update($new_instance, $old_instance)
	{
		$instance = $old_instance;

		$instance['search_placeholder'] = strip_tags($new_instance['search_placeholder']);

		return $instance;
	}

	function form($instance)
	{
		$defaults = array(
			'search_placeholder' => "",
		);
		$instance = wp_parse_args((array)$instance, $defaults);

		echo "<div class='mf_form'>"
			.show_textfield(array('name' => $this->get_field_name('search_placeholder'), 'text' => __("Placeholder", 'lang_theme_core'), 'value' => $instance['search_placeholder']))
		."</div>";
	}
}