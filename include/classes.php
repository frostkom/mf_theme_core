<?php

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

		echo get_search_theme_core(array('placeholder' => $instance['search_placeholder'], 'animate' => (isset($instance['search_animate']) ? $instance['search_animate'] : 'yes')));
	}

	function update($new_instance, $old_instance)
	{
		$instance = $old_instance;

		$instance['search_placeholder'] = strip_tags($new_instance['search_placeholder']);
		$instance['search_animate'] = isset($new_instance['search_animate']) ? strip_tags($new_instance['search_animate']) : 'yes';

		return $instance;
	}

	function form($instance)
	{
		$defaults = array(
			'search_placeholder' => "",
			'search_animate' => 'yes',
		);
		$instance = wp_parse_args((array)$instance, $defaults);

		echo "<div class='mf_form'>"
			.show_textfield(array('name' => $this->get_field_name('search_placeholder'), 'text' => __("Placeholder", 'lang_theme_core'), 'value' => $instance['search_placeholder']))
			.show_select(array('data' => get_yes_no_for_select(), 'name' => $this->get_field_name('search_animate'), 'text' => __("Animate", 'lang_theme_core'), 'value' => $instance['search_animate']))
		."</div>";
	}
}

class widget_theme_core_news extends WP_Widget
{
	function __construct()
	{
		$widget_ops = array(
			'classname' => 'theme_promo',
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

				$post_thumbnail = "";

				if(has_post_thumbnail($post_id))
				{
					$post_thumbnail = get_the_post_thumbnail($post_id, $post_thumbnail_size);
				}

				if($post_thumbnail != '')
				{
					$post_url = get_permalink($post_id);

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

						foreach($arr_news as $page)
						{
							echo "<li>
								<div class='image'><a href='".$page['url']."'>".$page['image']."</a></div>
								<h4>".$page['title']."</h4>
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

class widget_theme_core_promo extends WP_Widget
{
	function __construct()
	{
		$widget_ops = array(
			'classname' => 'theme_promo',
			'description' => __("Promote Pages", 'lang_theme_core')
		);

		$control_ops = array('id_base' => 'theme-promo-widget');

		parent::__construct('theme-promo-widget', __("Promotion", 'lang_theme_core'), $widget_ops, $control_ops);
	}

	function widget($args, $instance)
	{
		global $wpdb;

		extract($args);

		if(isset($instance['promo_include']) && count($instance['promo_include']) > 0)
		{
			$arr_pages = array();

			$result = $wpdb->get_results("SELECT ID, post_title, post_content FROM ".$wpdb->posts." WHERE post_type = 'page' AND post_status = 'publish' AND ID IN('".implode("','", $instance['promo_include'])."') ORDER BY post_date DESC");

			if($wpdb->num_rows > 0)
			{
				$post_thumbnail_size = 'large';

				foreach($result as $post)
				{
					$post_id = $post->ID;
					$post_title = $post->post_title;
					$post_content = $post->post_content;

					$post_thumbnail = "";

					if(has_post_thumbnail($post_id))
					{
						$post_thumbnail = get_the_post_thumbnail($post_id, $post_thumbnail_size);
					}

					if($post_thumbnail != '')
					{
						$post_url = get_permalink($post_id);

						$arr_pages[$post_id] = array(
							'title' => $post_title,
							'url' => $post_url,
							'image' => $post_thumbnail,
						);
					}

					else if(strlen($post_content) < 60 && preg_match("/youtube\.com|youtu\.be/i", $post_content))
					{
						$arr_pages[$post_id] = array(
							'content' => $post_content,
						);
					}
				}
			}

			if(count($arr_pages) > 0)
			{
				echo $before_widget;

					if($instance['promo_title'] != '')
					{
						echo $before_title
							.$instance['promo_title']
						.$after_title;
					}

					echo "<div class='section'>
						<ul".($arr_pages > 2 ? "" : " class='allow_expand'").">";

							foreach($arr_pages as $page)
							{
								if(isset($page['image']))
								{
									echo "<li>
										<div class='image'><a href='".$page['url']."'>".$page['image']."</a></div>
										<h4>".$page['title']."</h4>
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

		$instance['promo_title'] = strip_tags($new_instance['promo_title']);
		$instance['promo_include'] = isset($new_instance['promo_include']) ? $new_instance['promo_include'] : array();

		return $instance;
	}

	function form($instance)
	{
		$defaults = array(
			'promo_title' => "",
			'promo_include' => array(),
		);
		$instance = wp_parse_args((array)$instance, $defaults);

		$arr_data = array();
		get_post_children(array('post_type' => 'page', 'order_by' => 'post_title'), $arr_data);

		echo "<div class='mf_form'>"
			.show_textfield(array('name' => $this->get_field_name('promo_title'), 'text' => __("Title", 'lang_theme_core'), 'value' => $instance['promo_title']))
			.show_select(array('data' => $arr_data, 'name' => $this->get_field_name('promo_include')."[]", 'text' => __("Pages", 'lang_theme_core'), 'value' => $instance['promo_include']))
		."</div>";
	}
}