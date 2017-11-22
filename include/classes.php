<?php

class mf_theme_core
{
	function __construct()
	{
		$this->meta_prefix = "mf_theme_core_";
	}

	function has_noindex($post_id)
	{
		$page_index = get_post_meta($post_id, $this->meta_prefix.'page_index', true);

		return $page_index != '' && in_array($page_index, array('noindex', 'none'));
	}

	function get_public_post_types()
	{
		$this->arr_post_types = array();

		foreach(get_post_types(array('public' => true, 'exclude_from_search' => false), 'names') as $post_type)
		{
			if($post_type != 'attachment')
			{
				get_post_children(array(
					'post_type' => $post_type,
					'where' => "post_password = ''",
					//'debug' => true,
				), $this->arr_post_types);
			}
		}
	}

	function get_public_posts($data = array())
	{
		if(!isset($data['allow_noindex'])){		$data['allow_noindex'] = false;}

		$this->arr_public_posts = array();

		if(!isset($this->arr_post_types) || count($this->arr_post_types) == 0)
		{
			$this->get_public_post_types();
		}

		foreach($this->arr_post_types as $post_id => $post_title)
		{
			if($data['allow_noindex'] == false && $this->has_noindex($post_id) || post_password_required($post_id))
			{}

			else
			{
				$this->arr_public_posts[$post_id] = $post_title;
			}
		}
	}

	function do_robots()
	{
		echo "\nSitemap: ".get_site_url()."/sitemap.xml\n";
	}

	function do_sitemap()
	{
		global $wp_query;

		if(isset($wp_query->query['name']) && $wp_query->query['name'] == 'sitemap.xml')
		{
			header("Content-type: text/xml; charset=".get_option('blog_charset'));

			echo "<?xml version='1.0' encoding='UTF-8'?>
			<?xml-stylesheet type='text/xsl' href='".plugin_dir_url(__FILE__)."/sitemap-xsl.php'?>
			<urlset xmlns='http://www.sitemaps.org/schemas/sitemap/0.9'>";

				$this->get_public_posts();

				foreach($this->arr_public_posts as $post_id => $post_title)
				{
					$post_modified = get_post_modified_time("Y-m-d H:i:s", true, $post_id);

					$post_url = get_permalink($post_id);

					echo "<url>
						<loc>".$post_url."</loc>
						<title>".htmlspecialchars($post_title)."</title>
						<lastmod>".$post_modified."</lastmod>
					</url>";

					/*<changefreq>monthly</changefreq>
					<priority>0.8</priority>*/
				}

			echo "</urlset>";
			exit;
		}
	}

	// This is a WP v4.9 fix for sites that have had files in uploads/{year}/{month} and are expected to have the files in uploads/sites/{id}/{year}/{month}
	#################################
	function copy_file()
	{
		if(file_exists($this->file_dir_to))
		{
			if(get_option('option_uploads_fixed') > date("Y-m-d", strtotime("+1 month")))
			{
				if(file_exists($this->file_dir_from))
				{
					do_log(sprintf(__("The file %s already exists so %s can be deleted now", 'lang_theme_core'), $this->file_dir_to, $this->file_dir_from));

					//unlink($this->file_dir_from);
				}

				else
				{
					//do_log("File has already been deleted: ".$this->file_dir_from);
				}
			}
		}

		else
		{
			if(file_exists($this->file_dir_from))
			{
				@mkdir(dirname($this->file_dir_to), 0755, true);

				if(copy($this->file_dir_from, $this->file_dir_to))
				{
					//do_log("File was copied: ".$this->file_dir_from." -> ".$this->file_dir_to);
				}

				else
				{
					do_log("File was NOT copied: ".$this->file_dir_from." -> ".$this->file_dir_to);
				}
			}

			/*else
			{
				do_log("File does not exist: ".$this->file_dir_from);
			}*/
		}

		//do_log("Attachment: ".$post_url.", ".$upload_path_global." -> ".$upload_path.", ".$this->file_dir_from." -> ".$this->file_dir_to.""); //, ".$upload_url_global." -> ".$upload_url."
	}

	function do_fix()
	{
		global $wpdb;

		$arr_sizes = array('thumbnail', 'medium', 'large');

		$result = $wpdb->get_results($wpdb->prepare("SELECT ID, post_title FROM ".$wpdb->posts." WHERE post_type = %s", 'attachment'));

		foreach($result as $r)
		{
			$post_id = $r->ID;
			//$post_url = get_permalink($post_id);
			$post_url = wp_get_attachment_url($post_id);
			//$post_guid = $wpdb->get_var($wpdb->prepare("SELECT guid FROM ".$wpdb->posts." WHERE ID = '%d'", $post_id)); //$post_url
			//get_attached_file($post_id) //wp-content/uploads/sites/11/2017/09/logo_blue_small.png

			$upload_path_global = WP_CONTENT_DIR."/uploads/";
			$upload_url_global = WP_CONTENT_URL."/uploads/";

			list($upload_path, $upload_url) = get_uploads_folder('', true);

			if(!preg_match("/\/sites\//", $upload_path)){		$upload_path .= "sites/".$wpdb->blogid."/";}
			if(!preg_match("/\/sites\//", $upload_url)){		$upload_url .= "sites/".$wpdb->blogid."/";}

			$this->file_dir_from = str_replace(array($upload_url, $upload_url_global), $upload_path_global, $post_url);
			$this->file_dir_to = str_replace(array($upload_url, $upload_url_global), $upload_path, $post_url);

			$this->copy_file();

			$is_image = wp_attachment_is_image($post_id);

			if($is_image)
			{
				//do_log("Is image: ".$post_url);

				foreach($arr_sizes as $size)
				{
					$arr_image = wp_get_attachment_image_src($post_id, $size);

					$post_url = $arr_image[0];

					//do_log("Is smaller: ".$post_url);

					$this->file_dir_from = str_replace(array($upload_url, $upload_url_global), $upload_path_global, $post_url);
					$this->file_dir_to = str_replace(array($upload_url, $upload_url_global), $upload_path, $post_url);

					$this->copy_file();
				}
			}
		}

		if(!(get_option('option_uploads_fixed') > DEFAULT_DATE))
		{
			update_option('option_uploads_fixed', date("Y-m-d H:i:s"), 'no');
		}
	}
	#################################

	// Optimize
	#################################
	function remove_empty_folder($data)
	{
		$folder = $data['path']."/".$data['child'];

		if(count(scandir($folder)) == 2)
		{
			//do_log("Remove folder ".$folder." since it is empty");

			rmdir($folder);
		}
	}

	function do_optimize()
	{
		global $wpdb;

		//$setting_theme_optimize_age = get_option_or_default('setting_theme_optimize_age', 12);
		$setting_theme_optimize_age = 12;

		//Remove old revisions and auto-drafts
		$wpdb->query("DELETE FROM ".$wpdb->posts." WHERE post_type IN ('revision', 'auto-draft') AND post_modified < DATE_SUB(NOW(), INTERVAL ".$setting_theme_optimize_age." MONTH)");

		//Remove orphan postmeta
		$wpdb->get_results("SELECT post_id FROM ".$wpdb->postmeta." LEFT JOIN ".$wpdb->posts." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id WHERE ".$wpdb->posts.".ID IS NULL LIMIT 0, 1");

		if($wpdb->num_rows > 0)
		{
			$wpdb->query("DELETE ".$wpdb->postmeta." FROM ".$wpdb->postmeta." LEFT JOIN ".$wpdb->posts." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id WHERE ".$wpdb->posts.".ID IS NULL");
		}

		//Remove duplicate postmeta
		$result = $wpdb->get_results("SELECT meta_id, COUNT(meta_id) AS count FROM ".$wpdb->postmeta." GROUP BY post_id, meta_key, meta_value HAVING count > 1");

		if($wpdb->num_rows > 0)
		{
			foreach($result as $r)
			{
				$intMetaID = $r->meta_id;

				$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->postmeta." WHERE meta_id = %d", $intMetaID));
			}
		}

		//Remove orphan relations
		$wpdb->get_results("SELECT term_taxonomy_id FROM ".$wpdb->term_relationships." WHERE term_taxonomy_id = 1 AND object_id NOT IN (SELECT ID FROM ".$wpdb->posts.") LIMIT 0, 1");

		if($wpdb->num_rows > 0)
		{
			do_log("Remove orphan relations: ".$wpdb->last_query);

			//$wpdb->query("DELETE FROM ".$wpdb->term_relationships." WHERE term_taxonomy_id = 1 AND object_id NOT IN (SELECT id FROM ".$wpdb->posts.")");
			//"SELECT COUNT(object_id) FROM ".$wpdb->term_relationships." AS tr INNER JOIN ".$wpdb->term_taxonomy." AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id WHERE tt.taxonomy != 'link_category' AND tr.object_id NOT IN (SELECT ID FROM ".$wpdb->posts.")"
		}

		//Remove orphan usermeta
		$wpdb->get_results("SELECT user_id FROM ".$wpdb->usermeta." WHERE user_id NOT IN (SELECT ID FROM ".$wpdb->users.") LIMIT 0, 1");

		if($wpdb->num_rows > 0)
		{
			do_log("Remove orphan usermeta: ".$wpdb->last_query);

			//$wpdb->query("DELETE FROM ".$wpdb->usermeta." WHERE user_id NOT IN (SELECT ID FROM ".$wpdb->users.")");
		}

		//Remove duplicate usermeta
		$result = $wpdb->get_results("SELECT umeta_id, COUNT(umeta_id) AS count FROM ".$wpdb->usermeta." GROUP BY user_id, meta_key, meta_value HAVING count > 1");

		if($wpdb->num_rows > 0)
		{
			foreach($result as $r)
			{
				$intMetaID = $r->umeta_id;

				$wpdb->query($wpdb->prepare("DELETE FROM ".$wpdb->usermeta." WHERE umeta_id = %d", $intMetaID));
			}
		}

		//Unused tags
		//do_log unused tags ready for deletion

		//Pingbacks
		//"SELECT COUNT(*) FROM ".$wpdb->comments." WHERE comment_type = 'pingback'"

		//Trackbacks
		//"SELECT COUNT(*) FROM ".$wpdb->comments." WHERE comment_type = 'trackback'"

		//Spam comments
		//"SELECT COUNT(*) FROM ".$wpdb->comments." WHERE comment_approved = %s", "spam"

		//Duplicate comments
		//"SELECT COUNT(meta_id) AS count FROM ".$wpdb->commentmeta." GROUP BY comment_id, meta_key, meta_value HAVING count > %d", 1

		//oEmbed caches
		//"SELECT COUNT(meta_id) FROM ".$wpdb->postmeta." WHERE meta_key LIKE(%s)", "%_oembed_%"

		/*$wpdb->get_results("SELECT COUNT(*) as total, COUNT(case when option_value < NOW() then 1 end) as expired FROM ".$wpdb->options." WHERE (option_name LIKE '\_transient\_timeout\_%' OR option_name like '\_site\_transient\_timeout\_%') LIMIT 0, 1");

		if($wpdb->num_rows > 0)
		{
			do_log("Remove expired transients: ".$wpdb->last_query);
		}*/

		$result = $wpdb->get_results("SHOW TABLE STATUS");

		foreach($result as $r)
		{
			$strTableName = $r->Name;

			$wpdb->query("OPTIMIZE TABLE ".$strTableName);
		}

		// Can be removed later because the folder is not in use anymore
		list($upload_path, $upload_url) = get_uploads_folder('mf_theme_core');
		get_file_info(array('path' => $upload_path, 'callback' => "delete_files"));

		// Remove empty folders in uploads
		list($upload_path, $upload_url) = get_uploads_folder();
		get_file_info(array('path' => $upload_path, 'folder_callback' => array($this, 'remove_empty_folder')));

		if(is_multisite())
		{
			$this->do_fix();
		}

		update_option('option_database_optimized', date("Y-m-d H:i:s"), 'no');

		return __("I have optimized the site for you", 'lang_theme_core');
	}

	function run_optimize()
	{
		global $done_text, $error_text;

		$result = array();

		$done_text = $this->do_optimize();

		$out = get_notification();

		if($out != '')
		{
			$result['success'] = true;
			$result['message'] = $out;
		}

		else
		{
			$result['error'] = $out;
		}

		echo json_encode($result);
		die();
	}
	#################################
}

class mf_clone_posts
{
	function __construct()
	{
		add_filter('post_row_actions', 		array(&$this, 'row_actions'), 10, 2);
		add_filter('page_row_actions', 		array(&$this, 'row_actions'), 10, 2);
		add_action('wp_loaded', 			array(&$this, 'wp_loaded'));
	}

	function clone_single_post()
	{
    	$p = get_post($this->post_id_old);

		if($p == null)
		{
			return false;
		}

		$newpost = array(
			'post_name'				=> $p->post_name,
			'post_type'				=> $p->post_type,
			'ping_status'			=> $p->ping_status,
			'post_parent'			=> $p->post_parent,
			'menu_order'			=> $p->menu_order,
			'post_password'			=> $p->post_password,
			'post_excerpt'			=> $p->post_excerpt,
			'comment_status'		=> $p->comment_status,
			'post_title'			=> $p->post_title." (".__("copy", 'lang_theme_core').")",
			'post_content'			=> $p->post_content,
			'post_author'			=> $p->post_author,
			'to_ping'				=> $p->to_ping,
			'pinged'				=> $p->pinged,
			'post_content_filtered' => $p->post_content_filtered,
			'post_category'			=> $p->post_category,
			'tags_input'			=> $p->tags_input,
			'tax_input'				=> $p->tax_input,
			'page_template'			=> $p->page_template
			// 'post_date'			=> $p->post_date,				// default: current date
			// 'post_date_gmt'  	=> $p->post_date_gmt, 			// default: current gmt date
			// 'post_status'    	=> $p->post_status 				// default: draft
		);

		$this->post_id_new = wp_insert_post($newpost);

		$format = get_post_format($this->post_id_old);
		set_post_format($this->post_id_new, $format);

		$arr_meta = get_post_meta($this->post_id_old);

		foreach($arr_meta as $key => $value)
		{
			if(substr($key, 0, 1) != '_')
			{
				if(is_array($value))
				{
					if(!(count($value) > 1))
					{
						$value = $value[0];
					}
				}

				update_post_meta($this->post_id_new, $key, $value);
			}
		}

		do_action('clone_page', $this->post_id_old, $this->post_id_new);

		return true;
	}

	function row_actions($actions, $post)
	{
		global $post_type;

		$url = remove_query_arg(array('cloned', 'untrashed', 'deleted', 'ids'), "");

		if(!$url)
		{
			$url = admin_url("?post_type=".$post_type);
		}

		$url = remove_query_arg(array('action', 'action2', 'tags_input', 'post_author', 'comment_status', 'ping_status', '_status',  'post', 'bulk_edit', 'post_view'), $url);
		$url = add_query_arg(array('action' => 'clone-single', 'post' => $post->ID, 'redirect' => $_SERVER['REQUEST_URI']), $url);

		$actions['clone'] = "<a href='".$url."'>".__("Clone", 'lang_theme_core')."</a>";

		return $actions;
	}

	function wp_loaded()
	{
		global $post_type;

		if(!isset($_GET['action']) || $_GET['action'] !== "clone-single")
		{
			return;
		}

		$this->post_id_old = check_var('post');

		if(!current_user_can('edit_post', $this->post_id_old))
		{
			wp_die(__("You are not allowed to clone this post", 'lang_theme_core'));
		}

		else if(!$this->clone_single_post())
		{
			wp_die(__("Error cloning post", 'lang_theme_core'));
		}

		else
		{
			mf_redirect(admin_url("post.php?post=".$this->post_id_new."&action=edit"));
		}
	}
}

class widget_theme_core_logo extends WP_Widget
{
	function __construct()
	{
		$widget_ops = array(
			'classname' => 'theme_logo',
			'description' => __("Display logo", 'lang_theme_core')
		);

		$control_ops = array('id_base' => 'theme-logo-widget');

		$this->arr_default = array(
			'logo_display' => 'all',
			'logo_title' => '',
			'logo_image' => '',
			'logo_description' => '',
		);

		parent::__construct('theme-logo-widget', __("Logo", 'lang_theme_core'), $widget_ops, $control_ops);
	}

	function widget($args, $instance)
	{
		extract($args);

		$instance = wp_parse_args((array)$instance, $this->arr_default);

		echo $before_widget
			.get_logo(array('display' => $instance['logo_display'], 'title' => $instance['logo_title'], 'image' => $instance['logo_image'], 'description' => $instance['logo_description']))
		.$after_widget;
	}

	function update($new_instance, $old_instance)
	{
		$instance = $old_instance;

		$new_instance = wp_parse_args((array)$new_instance, $this->arr_default);

		$instance['logo_display'] = sanitize_text_field($new_instance['logo_display']);
		$instance['logo_title'] = sanitize_text_field($new_instance['logo_title']);
		$instance['logo_image'] = sanitize_text_field($new_instance['logo_image']);
		$instance['logo_description'] = sanitize_text_field($new_instance['logo_description']);

		return $instance;
	}

	function form($instance)
	{
		$instance = wp_parse_args((array)$instance, $this->arr_default);

		$arr_data = array(
			'all' => __("Logo and Tagline", 'lang_theme_core'),
			'title' => __("Logo", 'lang_theme_core'),
			'tagline' => __("Tagline", 'lang_theme_core'),
		);

		echo "<div class='mf_form'>
			<p>".__("If these are left empty, the chosen logo for the site will be displayed. If there is no chosen logo the site name will be displayed instead.", 'lang_theme_core')."</p>"
			.show_select(array('data' => $arr_data, 'name' => $this->get_field_name('logo_display'), 'text' => __("What to Display", 'lang_theme_core'), 'value' => $instance['logo_display']));

			if($instance['logo_display'] != 'tagline')
			{
				if($instance['logo_image'] == '')
				{
					echo show_textfield(array('name' => $this->get_field_name('logo_title'), 'text' => __("Logo", 'lang_theme_core'), 'value' => $instance['logo_title']));
				}

				if($instance['logo_title'] == '')
				{
					echo get_file_button(array('name' => $this->get_field_name('logo_image'), 'value' => $instance['logo_image']));
				}
			}

			if($instance['logo_display'] != 'title')
			{
				echo show_textfield(array('name' => $this->get_field_name('logo_description'), 'text' => __("Tagline", 'lang_theme_core'), 'value' => $instance['logo_description']));
			}

		echo "</div>";
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

		$this->arr_default = array(
			'search_placeholder' => "",
			'search_animate' => 'yes',
		);

		parent::__construct('theme-search-widget', __("Search", 'lang_theme_core'), $widget_ops, $control_ops);
	}

	function widget($args, $instance)
	{
		extract($args);

		$instance = wp_parse_args((array)$instance, $this->arr_default);

		echo get_search_theme_core(array('placeholder' => $instance['search_placeholder'], 'animate' => (isset($instance['search_animate']) ? $instance['search_animate'] : 'yes')));
	}

	function update($new_instance, $old_instance)
	{
		$instance = $old_instance;

		$new_instance = wp_parse_args((array)$new_instance, $this->arr_default);

		$instance['search_placeholder'] = strip_tags($new_instance['search_placeholder']);
		$instance['search_animate'] = strip_tags($new_instance['search_animate']);

		return $instance;
	}

	function form($instance)
	{
		$instance = wp_parse_args((array)$instance, $this->arr_default);

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

		$this->arr_default = array(
			'news_title' => "",
			'news_amount' => 3,
		);

		parent::__construct('theme-news-widget', __("News", 'lang_theme_core'), $widget_ops, $control_ops);
	}

	function widget($args, $instance)
	{
		global $wpdb;

		extract($args);

		$instance = wp_parse_args((array)$instance, $this->arr_default);

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
					<ul".(count($arr_news) > 2 ? "" : " class='allow_expand'").">";

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

		$new_instance = wp_parse_args((array)$new_instance, $this->arr_default);

		$instance['news_title'] = strip_tags($new_instance['news_title']);
		$instance['news_amount'] = strip_tags($new_instance['news_amount']);

		return $instance;
	}

	function form($instance)
	{
		$instance = wp_parse_args((array)$instance, $this->arr_default);

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

		$this->arr_default = array(
			'promo_title' => "",
			'promo_include' => array(),
		);

		parent::__construct('theme-promo-widget', __("Promotion", 'lang_theme_core'), $widget_ops);
	}

	function widget($args, $instance)
	{
		global $wpdb;

		extract($args);

		$instance = wp_parse_args((array)$instance, $this->arr_default);

		if(count($instance['promo_include']) > 0)
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
						<ul".(count($arr_pages) > 2 ? "" : " class='allow_expand'").">";

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

		$new_instance = wp_parse_args((array)$new_instance, $this->arr_default);

		$instance['promo_title'] = strip_tags($new_instance['promo_title']);
		$instance['promo_include'] = $new_instance['promo_include'];

		return $instance;
	}

	function form($instance)
	{
		$instance = wp_parse_args((array)$instance, $this->arr_default);

		$arr_data = array();
		get_post_children(array('post_type' => 'page', 'order_by' => 'post_title'), $arr_data);

		echo "<div class='mf_form'>"
			.show_textfield(array('name' => $this->get_field_name('promo_title'), 'text' => __("Title", 'lang_theme_core'), 'value' => $instance['promo_title']))
			.show_select(array('data' => $arr_data, 'name' => $this->get_field_name('promo_include')."[]", 'text' => __("Pages", 'lang_theme_core'), 'value' => $instance['promo_include']))
		."</div>";
	}
}