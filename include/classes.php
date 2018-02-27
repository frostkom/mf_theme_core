<?php

class mf_theme_core
{
	function __construct()
	{
		$this->meta_prefix = "mf_theme_core_";

		$this->options_params = $this->options = $this->options_fonts = array();
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

	// Style
	#########################
	function get_params()
	{
		if(count($this->options_params) == 0)
		{
			$this->options_params = get_params_theme_core();

			list($this->options_params, $this->options) = gather_params($this->options_params);
		}
	}

	function get_media_fonts()
	{
		global $wpdb;

		$arr_allowed_extensions = array('.eot', 'otf', '.svg', '.ttf', '.woff');
		$arr_media_fonts = array();

		$result = $wpdb->get_results("SELECT post_title, guid FROM ".$wpdb->posts." WHERE post_type = 'attachment' AND guid REGEXP '".implode("|", $arr_allowed_extensions)."' ORDER BY post_title ASC, post_date ASC");

		foreach($result as $r)
		{
			$media_title = $r->post_title;
			$media_name = sanitize_title($media_title);
			$media_guid = $r->guid;
			$media_extension = pathinfo($media_guid, PATHINFO_EXTENSION);

			if(in_array(".".$media_extension, $arr_allowed_extensions))
			{
				$arr_media_fonts[$media_name]['title'] = $media_title;
				$arr_media_fonts[$media_name]['guid'] = str_replace(".".$media_extension, "", $media_guid);
				$arr_media_fonts[$media_name]['extensions'][] = $media_extension;
			}
		}

		return $arr_media_fonts;
	}

	function get_theme_fonts()
	{
		$arr_media_fonts = $this->get_media_fonts();

		foreach($arr_media_fonts as $media_key => $media_font)
		{
			$this->options_fonts[$media_key] = array(
				'title' => $media_font['title'],
				'style' => "'".$media_font['title']."'",
				'file' => remove_protocol(array('url' => $media_font['guid'])),
				'extensions' => $media_font['extensions'],
			);
		}

		$this->options_fonts[2] = array(
			'title' => "Arial",
			'style' => "Arial, sans-serif",
			'url' => ""
		);

		$this->options_fonts[3] = array(
			'title' => "Droid Sans",
			'style' => "'Droid Sans', sans-serif",
			'url' => "//fonts.googleapis.com/css?family=Droid+Sans"
		);

		$this->options_fonts[5] = array(
			'title' => "Droid Serif",
			'style' => "'Droid Serif', serif",
			'url' => "//fonts.googleapis.com/css?family=Droid+Serif"
		);

		$this->options_fonts[1] = array(
			'title' => "Courgette",
			'style' => "'Courgette', cursive",
			'url' => "//fonts.googleapis.com/css?family=Courgette"
		);

		$this->options_fonts[6] = array(
			'title' => "Garamond",
			'style' => "'EB Garamond', serif",
			'url' => "//fonts.googleapis.com/css?family=EB+Garamond"
		);

		$this->options_fonts['lato'] = array(
			'title' => "Lato",
			'style' => "'Lato', sans-serif",
			'url' => "//fonts.googleapis.com/css?family=Lato"
		);

		$this->options_fonts['montserrat'] = array(
			'title' => "Montserrat",
			'style' => "'Montserrat', sans-serif",
			'url' => "//fonts.googleapis.com/css?family=Montserrat:400,700"
		);

		$this->options_fonts[2] = array(
			'title' => "Helvetica",
			'style' => "Helvetica, sans-serif",
			'url' => ""
		);

		$this->options_fonts[4] = array(
			'title' => "Open Sans",
			'style' => "'Open Sans', sans-serif",
			'url' => "//fonts.googleapis.com/css?family=Open+Sans"
		);

		$this->options_fonts['oswald'] = array(
			'title' => "Oswald",
			'style' => "'Oswald', sans-serif",
			'url' => "//fonts.googleapis.com/css?family=Oswald"
		);

		$this->options_fonts['oxygen'] = array(
			'title' => "Oxygen",
			'style' => "'Oxygen', sans-serif",
			'url' => "//fonts.googleapis.com/css?family=Oxygen"
		);

		$this->options_fonts['playfair_display'] = array(
			'title' => "Playfair Display",
			'style' => "'Playfair Display', serif",
			'url' => "//fonts.googleapis.com/css?family=Playfair+Display"
		);

		$this->options_fonts['roboto'] = array(
			'title' => "Roboto",
			'style' => "'Roboto', sans-serif",
			'url' => "//fonts.googleapis.com/css?family=Roboto"
		);

		$this->options_fonts['roboto_condensed'] = array(
			'title' => "Roboto Condensed",
			'style' => "'Roboto Condensed', sans-serif",
			'url' => "//fonts.googleapis.com/css?family=Roboto+Condensed"
		);

		$this->options_fonts['roboto_mono'] = array(
			'title' => "Roboto Mono",
			'style' => "'Roboto Mono', sans-serif",
			'url' => "//fonts.googleapis.com/css?family=Roboto+Mono"
		);

		$this->options_fonts['sorts_mill_goudy'] = array(
			'title' => "Sorts Mill Goudy",
			'style' => "'sorts-mill-goudy',serif",
			'url' => "//fonts.googleapis.com/css?family=Sorts+Mill+Goudy"
		);

		$this->options_fonts['source_sans_pro'] = array(
			'title' => "Source Sans Pro",
			'style' => "'Source Sans Pro', sans-serif",
			'url' => "//fonts.googleapis.com/css?family=Source+Sans+Pro"
		);
	}

	function show_font_face()
	{
		if(count($this->options_fonts) == 0)
		{
			$this->get_theme_fonts();
		}

		$out = "";

		foreach($this->options_params as $param)
		{
			if(isset($param['type']) && $param['type'] == 'font')
			{
				$font = $this->options[$param['id']];

				if($font != '' && isset($this->options_fonts[$font]['file']) && $this->options_fonts[$font]['file'] != '')
				{
					$font_file = $this->options_fonts[$font]['file'];

					$font_src = "";

					foreach($this->options_fonts[$font]['extensions'] as $font_extension)
					{
						$font_src .= ($font_src != '' ? "," : "");

						switch($font_extension)
						{
							case 'eot':		$font_src .= "url('".$font_file.".eot?#iefix') format('embedded-opentype')";	break;
							case 'otf':		$font_src .= "url('".$font_file.".otf') format('opentype')";					break;
							case 'woff':	$font_src .= "url('".$font_file.".woff') format('woff')";						break;
							case 'ttf':		$font_src .= "url('".$font_file.".ttf') format('truetype')";					break;
							case 'svg':		$font_src .= "url('".$font_file.".svg#".$font."') format('svg')";				break;
						}
					}

					if($font_src != '')
					{
						$out .= "@font-face
						{
							font-family: '".$this->options_fonts[$font]['title']."';
							src: ".$font_src.";
							font-weight: normal;
							font-style: normal;
						}";
					}
				}
			}
		}

		return $out;
	}

	function get_common_style()
	{
		$out = "";

		$out .= "p a, a .read_more
		{"
			.$this->render_css(array('property' => 'color', 'value' => 'body_link_color'))
			.$this->render_css(array('property' => 'text-decoration', 'value' => 'body_link_underline'))
			."text-decoration-skip: ink;
		}

			.read_more
			{
				margin-top: .5em;
			}

		.form_textfield input, .mf_form textarea, .mf_form select, .form_button button, .form_button .button
		{"
			.$this->render_css(array('property' => 'border-radius', 'value' => 'form_border_radius'))
		."}

		#wrapper .mf_form button, #wrapper .button, .color_button, #wrapper .mf_form .button-primary
		{"
			.$this->render_css(array('property' => 'background', 'value' => array('button_color', 'nav_color_hover')))
			.$this->render_css(array('property' => 'color', 'value' => 'button_text_color'))
		."}

		#wrapper .button-secondary, .color_button_2
		{"
			.$this->render_css(array('property' => 'background', 'value' => 'button_color_secondary', 'suffix' => " !important"))
			.$this->render_css(array('property' => 'color', 'value' => 'button_text_color_secondary'))
		."}

		.color_button_negative
		{"
			.$this->render_css(array('property' => 'background', 'value' => 'button_color_negative', 'suffix' => " !important"))
			.$this->render_css(array('property' => 'color', 'value' => 'button_text_color_negative'))
		."}

			#wrapper .mf_form button:hover, #wrapper .button:hover, #wrapper .mf_form .button-primary:hover, #wrapper .button-secondary:hover, .color_button_2:hover, .color_button_negative:hover
			{
				box-shadow: inset 0 0 10em rgba(0, 0, 0, .1);
			}

		html
		{
			font-size: .625em;"
			.$this->render_css(array('property' => 'font-size', 'value' => 'body_font_size'))
			.$this->render_css(array('property' => 'overflow-y', 'value' => 'body_scroll'))
		."}

			body
			{"
				.$this->render_css(array('property' => 'background', 'value' => 'footer_bg', 'suffix' => "; min-height: 100vh"))
				.$this->render_css(array('property' => 'background-color', 'value' => 'footer_bg_color'))
				.$this->render_css(array('property' => 'background-image', 'prefix' => 'url(', 'value' => 'footer_bg_image', 'suffix' => '); background-size: cover'))
				.$this->render_css(array('property' => 'font-family', 'value' => 'body_font'))
				.$this->render_css(array('property' => 'color', 'value' => 'body_color'))
			."}

				#wrapper
				{"
					.$this->render_css(array('property' => 'background', 'value' => 'body_bg'))
					.$this->render_css(array('property' => 'background-color', 'value' => 'body_bg_color'))
					.$this->render_css(array('property' => 'background-image', 'prefix' => 'url(', 'value' => 'body_bg_image', 'suffix' => '); background-size: cover'))
				."}";

		return $out;
	}

	function render_css($data)
	{
		$prop = isset($data['property']) ? $data['property'] : '';
		$pre = isset($data['prefix']) ? $data['prefix'] : '';
		$suf = isset($data['suffix']) ? $data['suffix'] : '';
		$val = isset($data['value']) ? $data['value'] : '';

		if(is_array($val) && count($val) > 1)
		{
			$arr_val = $val;
			$val = $arr_val[0];
		}

		$out = '';

		if($prop == 'font-family' && (!isset($this->options[$val]) || !isset($this->options_fonts[$this->options[$val]]['style'])))
		{
			$this->options[$val] = '';
		}

		if($prop == 'float' && $this->options[$val] == 'center')
		{
			$prop = 'margin';
			$this->options[$val] = '0 auto';
		}

		if(isset($this->options[$val]) && $this->options[$val] != '')
		{
			if($prop != '')
			{
				$out .= $prop.": ";
			}

			if($pre != '')
			{
				$out .= $pre;
			}

				if($prop == 'font-family')
				{
					$out .= $this->options_fonts[$this->options[$val]]['style'];
				}

				else
				{
					$out .= $this->options[$val];
				}

			if($suf != '')
			{
				$out .= $suf;
			}

			if($prop != '' || $pre != '' || $suf != '')
			{
				$out .= ";";
			}
		}

		else if(isset($arr_val) && count($arr_val) > 1)
		{
			array_splice($arr_val, 0, 1);

			$data['value'] = count($arr_val) > 1 ? $arr_val : $arr_val[0];

			$out .= $this->render_css($data);
		}

		return $out;
	}

	function enqueue_theme_fonts()
	{
		$this->get_theme_fonts();

		$arr_fonts2insert = array();

		$this->get_params();

		foreach($this->options_params as $param)
		{
			if(isset($param['type']) && $param['type'] == 'font' && isset($this->options[$param['id']]))
			{
				$font = $this->options[$param['id']];

				if(isset($this->options_fonts[$font]['url']) && $this->options_fonts[$font]['url'] != '')
				{
					mf_enqueue_style('style_font_'.$font, $this->options_fonts[$font]['url']);
				}
			}
		}
	}
	#################################

	/* Public */
	#################################
	function add_page_index()
	{
		global $post;

		if(isset($post) && $post->ID > 0)
		{
			$page_index = get_post_meta($post->ID, $this->meta_prefix.'page_index', true);

			if($page_index != '')
			{
				switch($page_index)
				{
					case 'nofollow':
					case 'noindex':
						echo "<meta name='robots' content='".$page_index."'>";
					break;

					case 'none':
						echo "<meta name='robots' content='noindex, nofollow'>";
					break;
				}
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

	function get_logo($data = array())
	{
		if(!isset($data['display'])){			$data['display'] = 'all';}
		if(!isset($data['title'])){				$data['title'] = '';}
		if(!isset($data['image'])){				$data['image'] = '';}
		if(!isset($data['description'])){		$data['description'] = '';}

		$this->get_params();

		$has_logo = $data['image'] != '' || isset($this->options['header_logo']) && $this->options['header_logo'] != '' || isset($this->options['header_mobile_logo']) && $this->options['header_mobile_logo'] != '';

		$out = "<a href='".get_site_url()."/' id='site_logo'>";

			if($has_logo && $data['title'] == '')
			{
				if($data['display'] != 'tagline')
				{
					$site_name = get_bloginfo('name');
					$site_description = get_bloginfo('description');

					if($data['image'] != '')
					{
						$out .= "<img src='".$data['image']."' alt='".sprintf(__("Logo for %s | %s", 'lang_theme_core'), $site_name, $site_description)."'>";
					}

					else
					{
						if($this->options['header_logo'] != '')
						{
							$out .= "<img src='".$this->options['header_logo']."'".($this->options['header_mobile_logo'] != '' ? " class='hide_if_mobile'" : "")." alt='".sprintf(__("Logo for %s | %s", 'lang_theme_core'), $site_name, $site_description)."'>";
						}

						if($this->options['header_mobile_logo'] != '')
						{
							$out .= "<img src='".$this->options['header_mobile_logo']."'".($this->options['header_logo'] != '' ? " class='show_if_mobile'" : "")." alt='".sprintf(__("Mobile Logo for %s | %s", 'lang_theme_core'), $site_name, $site_description)."'>";
						}
					}
				}

				if($data['display'] != 'title' && $data['description'] != '')
				{
					$out .= "<span>".$data['description']."</span>";
				}
			}

			else
			{
				if($data['display'] != 'tagline')
				{
					$logo_title = $data['title'] != '' ? $data['title'] : get_bloginfo('name');

					$out .= "<div>".$logo_title."</div>";
				}

				if($data['display'] != 'title')
				{
					$logo_description = $data['description'] != '' ? $data['description'] : get_bloginfo('description');

					if($logo_description != '')
					{
						$out .= "<span>".$logo_description."</span>";
					}
				}
			}

		$out .= "</a>";

		return $out;
	}

	function content_meta($html, $post)
	{
		$setting_display_post_meta = get_option_or_default('setting_display_post_meta', array('time'));

		if($post->post_type == 'post' && in_array('time', $setting_display_post_meta))
		{
			$html .= "<time datetime='".$post->post_date."'>".format_date($post->post_date)."</time>";
		}

		if(in_array('author', $setting_display_post_meta))
		{
			$html .= "<span>".sprintf(__("by %s", 'lang_theme_core'), get_user_info(array('id' => $post->post_author)))."</span>";
		}

		if(in_array('category', $setting_display_post_meta))
		{
			$arr_categories = get_the_category($post->ID);

			if(is_array($arr_categories) && count($arr_categories) > 0)
			{
				$category_base_url = get_site_url()."/category/";

				foreach($arr_categories as $category)
				{
					$html .= "<a href='".$category_base_url.$category->slug."'>".$category->name."</a>";
				}
			}
		}

		return $html;
	}
	#################################

	/* Admin */
	#################################
	function column_header($cols)
	{
		unset($cols['comments']);

		if(is_site_public())
		{
			$cols['seo'] = __("SEO", 'lang_theme_core');
		}

		return $cols;
	}

	function column_cell($col, $id)
	{
		global $wpdb;

		switch($col)
		{
			case 'seo':
				$title_limit = 64;
				$excerpt_limit = 156;

				$result = $wpdb->get_results($wpdb->prepare("SELECT post_title, post_excerpt, post_type, post_name FROM ".$wpdb->posts." WHERE ID = '%d' LIMIT 0, 1", $id));

				foreach($result as $r)
				{
					$post_title = $r->post_title;
					$post_excerpt = $r->post_excerpt;
					$post_type = $r->post_type;
					$post_name = $r->post_name;

					$seo_type = '';

					if($seo_type == '')
					{
						$page_index = get_post_meta($id, $this->meta_prefix.'page_index', true);

						if(in_array($page_index, array('noindex', 'none')))
						{
							$seo_type = 'not_indexed';
						}
					}

					if($seo_type == '')
					{
						if(post_password_required($id))
						{
							$seo_type = 'password_protected';
						}
					}

					if($seo_type == '')
					{
						if($post_excerpt != '')
						{
							$post_id_duplicate = $wpdb->get_var($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." WHERE post_excerpt = %s AND post_status = 'publish' AND post_type = %s AND ID != '%d' LIMIT 0, 1", $post_excerpt, $post_type, $id));

							if($post_id_duplicate > 0)
							{
								$seo_type = 'duplicate_excerpt';
							}

							else if(strlen($post_excerpt) > $excerpt_limit)
							{
								$seo_type = 'long_excerpt';
							}
						}

						else
						{
							$seo_type = 'no_excerpt';
						}
					}

					if($seo_type == '')
					{
						if($post_title != '')
						{
							$post_id_duplicate = $wpdb->get_var($wpdb->prepare("SELECT ID FROM ".$wpdb->posts." WHERE post_title = %s AND post_status = 'publish' AND post_type = %s AND ID != '%d' LIMIT 0, 1", $post_title, $post_type, $id));

							if($post_id_duplicate > 0)
							{
								$seo_type = 'duplicate_title';
							}
						}

						else
						{
							$seo_type = 'no_title';
						}
					}

					if($seo_type == '')
					{
						if($post_name != '')
						{
							if(sanitize_title_with_dashes(sanitize_title($post_title)) != $post_name)
							{
								$seo_type = 'inconsistent_url';
							}
						}
					}

					if($seo_type == '')
					{
						$site_title = $post_title." | ".get_wp_title();

						if(strlen($site_title) > $title_limit)
						{
							$seo_type = 'long_title';
						}
					}

					switch($seo_type)
					{
						case 'duplicate_title':
							echo "<i class='fa fa-lg fa-close red'></i>
							<div class='row-actions'>
								<a href='".admin_url("post.php?post=".$post_id_duplicate."&action=edit")."'>"
									.sprintf(__("The page %s have the exact same title. Please, try to not have duplicates because that will hurt your SEO.", 'lang_theme_core'), get_post_title($post_id_duplicate))
								."</a>
							</div>";
						break;

						case 'no_title':
							echo "<i class='fa fa-lg fa-close red'></i>
							<div class='row-actions'>"
								.__("You have not set a title for this page", 'lang_theme_core')
							."</div>";
						break;

						case 'duplicate_excerpt':
							echo "<i class='fa fa-lg fa-close red'></i>
							<div class='row-actions'>
								<a href='".admin_url("post.php?post=".$post_id_duplicate."&action=edit")."'>"
									.sprintf(__("The page %s have the exact same excerpt", 'lang_theme_core'), get_post_title($post_id_duplicate))
								."</a>
							</div>";
						break;

						case 'no_excerpt':
							echo "<i class='fa fa-lg fa-close red'></i>
							<div class='row-actions'>"
								.__("You have not set an excerpt for this page", 'lang_theme_core')
							."</div>";
						break;

						case 'inconsistent_url':
							echo "<i class='fa fa-lg fa-warning yellow'></i>
							<div class='row-actions'>"
								.__("The URL is not correlated to the title", 'lang_theme_core')
							."</div>";
						break;

						case 'long_title':
							echo "<i class='fa fa-lg fa-warning yellow'></i>
							<div class='row-actions'>"
								.__("The title might be too long to show in search engines", 'lang_theme_core')." (".strlen($site_title)." > ".$title_limit.")"
							."</div>";
						break;

						case 'long_excerpt':
							echo "<i class='fa fa-lg fa-warning yellow'></i>
							<div class='row-actions'>"
								.__("The excerpt (meta description) might be too long to show in search engines", 'lang_theme_core')." (".strlen($post_excerpt)." > ".$excerpt_limit.")"
							."</div>";
						break;

						case 'not_indexed':
							echo "<i class='fa fa-lg fa-check grey'></i>";
						break;

						case 'password_protected':
							echo "<i class='fa fa-lg fa-lock'></i>";
						break;

						default:
							echo "<i class='fa fa-lg fa-check green'></i>";
						break;
					}
				}
			break;
		}
	}

	function meta_boxes($meta_boxes)
	{
		if(is_site_public())
		{
			$meta_boxes[] = array(
				'id' => 'theme_core',
				'title' => __("Publish Settings", 'lang_theme_core'),
				'post_types' => get_post_types_for_metabox(),
				'context' => 'side',
				'priority' => 'low',
				'fields' => array(
					array(
						'name' => __("Index", 'lang_theme_core'),
						'id' => $this->meta_prefix.'page_index',
						'type' => 'select',
						'options' => array(
							'' => "-- ".__("Choose here", 'lang_theme_core')." --",
							'noindex' => __("Don't Index", 'lang_theme_core'),
							'nofollow' => __("Don't Follow Links", 'lang_theme_core'),
							'none' => __("Don't Index & don't follow links", 'lang_theme_core'),
						),
					),
					array(
						'name' => __("Unpublish", 'lang_theme_core'),
						'id' => $this->meta_prefix.'unpublish_date',
						'type' => 'datetime',
					),
				)
			);
		}

		return $meta_boxes;
	}

	/* Send e-mail to all editors if it is a draft and the user saving the draft is an author, but not an editor */
	function save_post($post_id)
	{
		global $post;

		if(isset($post->post_status) && $post->post_status == 'draft' && IS_AUTHOR && !IS_EDITOR && get_option('setting_send_email_on_draft') == 'yes')
		{
			$post_title = get_the_title($post);
			$post_url = get_permalink($post);

			$mail_subject = sprintf(__("The draft '%s' has been saved", 'lang_theme_core'), $post_title);
			$mail_content = sprintf(__("The draft '%s' has been saved and might be ready for publishing", 'lang_theme_core'), "<a href='".$post_url."'>".$post_title."</a>");

			$users = get_users(array(
				'fields' => array('user_email'),
				'role__in' => array('editor'),
			));

			foreach($users as $user)
			{
				$mail_to = $user->user_email;

				$sent = send_email(array('to' => $mail_to, 'subject' => $mail_subject, 'content' => $mail_content));
			}
		}
	}
	#################################

	//Customizer
	#################################
	function add_select($data = array())
	{
		global $wp_customize;

		$wp_customize->add_control(
			$this->param['id'],
			array(
				'label' => $this->param['title'],
				'section' => $this->id_temp,
				'settings' => $this->param['id'],
				'type' => 'select',
				'choices' => $data['choices'],
			)
		);
	}

	function customize_theme($wp_customize)
	{
		$plugin_include_url = plugin_dir_url(__FILE__);
		$plugin_version = get_plugin_version(__FILE__);

		mf_enqueue_style('style_theme_core_customizer', $plugin_include_url."style_customizer.css", $plugin_version);

		$this->get_params();
		$this->get_theme_fonts();

		$this->id_temp = "";
		$this->param = array();

		$wp_customize->remove_section('themes');
		$wp_customize->remove_section('title_tagline');
		$wp_customize->remove_section('static_front_page');
		//$wp_customize->remove_section('nav_menus');
		//$wp_customize->remove_section('widgets');
		$wp_customize->remove_section('custom_css');

		foreach($this->options_params as $this->param)
		{
			if(isset($this->param['show_if']) && $this->param['show_if'] != '' && $this->options[$this->param['show_if']] == ''){}

			else if(isset($this->param['hide_if']) && $this->param['hide_if'] != '' && $this->options[$this->param['hide_if']] != ''){}

			else
			{
				if(isset($this->param['category']))
				{
					$this->id_temp = $this->param['id'];

					$wp_customize->add_section(
						$this->id_temp,
						array(
							'title' => $this->param['category'],
							//'description' => '',
							//'priority' => 1,
						)
					);
				}

				else if(isset($this->param['category_end'])){}

				else
				{
					/*if(isset($this->param['ignore_default_if']) && $this->param['ignore_default_if'] != '' && $this->options[$this->param['ignore_default_if']] != '')
					{
						$default_value = '';
					}

					else */if(isset($this->param['default']))
					{
						$default_value = $this->param['default'];
					}

					else
					{
						$default_value = '';
					}

					$wp_customize->add_setting(
						$this->param['id'],
						array(
							'default' => $default_value,
							'transport' => "postMessage"
						)
					);

					switch($this->param['type'])
					{
						case 'align':
							$arr_data = array(
								'' => "-- ".__("Choose here", 'lang_theme_core')." --",
								'left' => __("Left", 'lang_theme_core'),
								'center' => __("Center", 'lang_theme_core'),
								'right' => __("Right", 'lang_theme_core'),
							);

							$this->add_select(array('choices' => $arr_data));
						break;

						case 'color':
							$wp_customize->add_control(
								new WP_Customize_Color_Control(
									$wp_customize,
									$this->param['id'],
									array(
										'label' => $this->param['title'],
										'section' => $this->id_temp,
										'settings' => $this->param['id'],
									)
								)
							);
						break;

						case 'checkbox':
							$arr_data = array(
								2 => __("Yes", 'lang_theme_core'),
								1 => __("No", 'lang_theme_core'),
							);

							$this->add_select(array('choices' => $arr_data));
						break;

						case 'clear':
							$arr_data = array(
								'' => "-- ".__("Choose here", 'lang_theme_core')." --",
								'left' => __("Left", 'lang_theme_core'),
								'right' => __("Right", 'lang_theme_core'),
								'both' => __("Both", 'lang_theme_core'),
								'none' => __("None", 'lang_theme_core'),
							);

							$this->add_select(array('choices' => $arr_data));
						break;

						case 'date':
						case 'email':
						case 'hidden':
						case 'number':
						case 'text':
						case 'textarea':
						case 'url':
							$wp_customize->add_control(
								$this->param['id'],
								array(
									'label' => $this->param['title'],
									'section' => $this->id_temp,
									'type' => $this->param['type'],
								)
							);
						break;

						case 'float':
							$arr_data = array(
								'' => "-- ".__("Choose here", 'lang_theme_core')." --",
								'none' => __("None", 'lang_theme_core'),
								'left' => __("Left", 'lang_theme_core'),
								'center' => __("Center", 'lang_theme_core'),
								'right' => __("Right", 'lang_theme_core'),
								'initial' => __("Initial", 'lang_theme_core'),
								'inherit' => __("Inherit", 'lang_theme_core'),
							);

							$this->add_select(array('choices' => $arr_data));
						break;

						case 'font':
							$arr_data = array(
								'' => "-- ".__("Choose here", 'lang_theme_core')." --"
							);

							if(count($this->options_fonts) > 0)
							{
								foreach($this->options_fonts as $key => $value)
								{
									$arr_data[$key] = $value['title'];
								}
							}

							$this->add_select(array('choices' => $arr_data));
						break;

						case 'image':
							$wp_customize->add_control(
								new WP_Customize_Image_Control(
									$wp_customize,
									$this->param['id'],
									array(
										'label' => $this->param['title'],
										'section' => $this->id_temp,
										'settings' => $this->param['id'],
										//'context' => 'your_setting_context'
									)
								)
							);
						break;

						case 'overflow':
							$arr_data = array(
								'' => "-- ".__("Choose here", 'lang_theme_core')." --",
								'visible' => __("Visible", 'lang_theme_core'),
								'hidden' => __("Hidden", 'lang_theme_core'),
								'scroll' => __("Scroll", 'lang_theme_core'),
								'auto' => __("Auto", 'lang_theme_core'),
								'initial' => __("Initial", 'lang_theme_core'),
								'inherit' => __("Inherit", 'lang_theme_core'),
							);

							$this->add_select(array('choices' => $arr_data));
						break;

						case 'position':
							$arr_data = array(
								'' => "-- ".__("Choose here", 'lang_theme_core')." --",
								'absolute' => __("Absolute", 'lang_theme_core'),
								'fixed' => __("Fixed", 'lang_theme_core'),
							);

							$this->add_select(array('choices' => $arr_data));
						break;

						case 'text_decoration':
							$arr_data = array(
								'' => "-- ".__("Choose here", 'lang_theme_core')." --",
								'none' => __("None", 'lang_theme_core'),
								'underline' => __("Underline", 'lang_theme_core'),
							);

							$this->add_select(array('choices' => $arr_data));
						break;

						case 'text_transform':
							$arr_data = array(
								'' => "-- ".__("Choose here", 'lang_theme_core')." --",
								'uppercase' => __("Uppercase", 'lang_theme_core'),
								'lowercase' => __("Lowercase", 'lang_theme_core'),
							);

							$this->add_select(array('choices' => $arr_data));
						break;

						case 'weight':
							$arr_data = array(
								'' => "-- ".__("Choose here", 'lang_theme_core')." --",
								'lighter' => __("Lighter", 'lang_theme_core'),
								'normal' => __("Normal", 'lang_theme_core'),
								'bold' => __("Bold", 'lang_theme_core'),
								'bolder' => __("Bolder", 'lang_theme_core'),
								'initial' => __("Initial", 'lang_theme_core'),
								'inherit' => __("Inherit", 'lang_theme_core'),
							);

							$this->add_select(array('choices' => $arr_data));
						break;
					}
				}
			}
		}
	}
	#################################

	// This is a WP v4.9 fix for sites that have had files in uploads/{year}/{month} and are expected to have the files in uploads/sites/{id}/{year}/{month}
	#################################
	function copy_file()
	{
		if(file_exists($this->file_dir_to))
		{
			if(get_option('option_uploads_fixed') < date("Y-m-d", strtotime("-1 month")))
			{
				if(file_exists($this->file_dir_from) && is_file($this->file_dir_from))
				{
					//error_log(sprintf(__("The file %s already exists so %s can be deleted now", 'lang_theme_core'), $this->file_dir_to, $this->file_dir_from));

					/* Some files are still in use in the old hierarchy */
					/*unlink($this->file_dir_from);
					do_log("Removed File: ".$upload_path.$strFileName);*/
				}

				/*else
				{
					error_log("File has already been deleted: ".$this->file_dir_from);
				}*/
			}
		}

		else
		{
			if(file_exists($this->file_dir_from))
			{
				@mkdir(dirname($this->file_dir_to), 0755, true);

				if(copy($this->file_dir_from, $this->file_dir_to))
				{
					//error_log("File was copied: ".$this->file_dir_from." -> ".$this->file_dir_to);
				}

				else
				{
					error_log("File was NOT copied: ".$this->file_dir_from." -> ".$this->file_dir_to);
				}
			}
		}
	}

	function do_fix()
	{
		global $wpdb;

		$upload_path_from = WP_CONTENT_DIR."/uploads/";
		$upload_url_from = WP_CONTENT_URL."/uploads/";

		list($upload_path_to, $upload_url_to) = get_uploads_folder('', true);

		if(!preg_match("/\/sites\//", $upload_path_to)){	$upload_path_to .= "sites/".$wpdb->blogid."/";}
		if(!preg_match("/\/sites\//", $upload_url_to)){		$upload_url_to .= "sites/".$wpdb->blogid."/";}

		$arr_sizes = array('thumbnail', 'medium', 'large');

		$result = $wpdb->get_results($wpdb->prepare("SELECT ID, post_title FROM ".$wpdb->posts." WHERE post_type = %s", 'attachment'));

		foreach($result as $r)
		{
			$post_id = $r->ID;
			$post_url = wp_get_attachment_url($post_id);

			$this->file_dir_from = str_replace(array($upload_url_to, $upload_url_from), $upload_path_from, $post_url);
			$this->file_dir_to = str_replace(array($upload_url_to, $upload_url_from), $upload_path_to, $post_url);

			$this->copy_file();

			if(wp_attachment_is_image($post_id))
			{
				foreach($arr_sizes as $size)
				{
					$arr_image = wp_get_attachment_image_src($post_id, $size);
					$post_url = $arr_image[0];

					$this->file_dir_from = str_replace(array($upload_url_to, $upload_url_from), $upload_path_from, $post_url);
					$this->file_dir_to = str_replace(array($upload_url_to, $upload_url_from), $upload_path_to, $post_url);

					$this->copy_file();
				}
			}
		}

		/* Some files are still in use in the old hierarchy */
		/*if(!(get_option('option_uploads_fixed') > DEFAULT_DATE))
		{
			update_option('option_uploads_fixed', date("Y-m-d H:i:s"), 'no');
		}

		if(get_option('option_uploads_fixed') < date("Y-m-d", strtotime("-1 month")))
		{
			if(file_exists($upload_path_from.date("Y")))
			{
				error_log(sprintf(__("You can now safely remove all year folders in %s, but just to be on the safe side you can move them to a temporary folder or make a backup before you do this just in case"), $upload_path_from));

				update_option('option_uploads_done', date("Y-m-d H:i:s"), 'no');
				delete_option('option_uploads_fixed');
			}
		}*/
	}
	#################################

	// Cron
	#################################
	function unpublish_posts()
	{
		global $wpdb;

		$result = $wpdb->get_results("SELECT ID, meta_value FROM ".$wpdb->posts." INNER JOIN ".$wpdb->postmeta." ON ".$wpdb->posts.".ID = ".$wpdb->postmeta.".post_id AND meta_key = '".$this->meta_prefix."unpublish_date' WHERE post_status = 'publish' AND meta_value != ''");

		if($wpdb->num_rows > 0)
		{
			foreach($result as $r)
			{
				$post_id = $r->ID;
				$post_unpublish = $r->meta_value;

				if($post_unpublish <= date("Y-m-d H:i:s"))
				{
					$post_data = array(
						'ID' => $post_id,
						'post_status' => 'draft',
						'meta_input' => array(
							$this->meta_prefix.'unpublish_date' => '',
						),
					);

					wp_update_post($post_data);
				}
			}
		}
	}

	function check_style_source()
	{
		$this->get_params();

		if(isset($this->options['style_source']) && $this->options['style_source'] != '')
		{
			$style_source = trim($this->options['style_source'], "/");

			if($style_source != get_site_url())
			{
				if(filter_var($style_source, FILTER_VALIDATE_URL))
				{
					list($content, $headers) = get_url_content($style_source."/wp-content/plugins/mf_theme_core/include/api/?type=get_style_source", true);

					if(isset($headers['http_code']) && $headers['http_code'] == 200)
					{
						$json = json_decode($content, true);

						if(isset($json['success']) && $json['success'] == true)
						{
							$style_changed = $json['response']['style_changed'];
							$style_url = $json['response']['style_url'];

							update_option('option_theme_source_style_url', ($style_changed > get_option('option_theme_saved') ? $style_url : ""), 'no');
						}

						else
						{
							error_log(sprintf(__("The feed from %s returned an error", 'lang_theme'), $style_source));
						}
					}

					else
					{
						error_log(sprintf(__("The response from %s had an error (%s)", 'lang_theme'), $style_source, $headers['http_code']));
					}
				}

				else
				{
					error_log(sprintf(__("I could not process the feed from %s since the URL was not a valid one", 'lang_theme'), $style_source));
				}
			}
		}
	}

	function run_cron()
	{
		global $wpdb;

		$this->unpublish_posts();

		/* Optimize */
		#########################
		$setting_theme_optimize = get_option_or_default('setting_theme_optimize', 7);

		if(get_option('option_database_optimized') < date("Y-m-d H:i:s", strtotime("-".$setting_theme_optimize." day")))
		{
			$this->do_optimize();
		}
		#########################

		$this->check_style_source();

		/* Delete old uploads */
		#######################
		$theme_dir_name = get_theme_dir_name();

		list($upload_path, $upload_url) = get_uploads_folder($theme_dir_name);

		get_file_info(array('path' => $upload_path, 'callback' => 'delete_files', 'time_limit' => (60 * 60 * 24 * 60))); //60 days
		#######################

		/* Set default meta boxes */
		#######################
		$page = 'post';

		$users = get_users(array(
			'fields' => array('ID'),
		));

		foreach($users as $user)
		{
			$hidden_default = $hidden = get_user_option('metaboxhidden_'.$page, $user->ID);

			if(is_array($hidden))
			{
				$hidden = array_diff($hidden, array('postexcerpt')); // postboxes that are always shown

				if($hidden != $hidden_default)
				{
					update_user_option($user->ID, 'metaboxhidden_'.$page, $hidden, true);
				}
			}

			else
			{
				$hidden = array('slugdiv', 'trackbacksdiv', 'postcustom', 'commentstatusdiv', 'commentsdiv', 'authordiv', 'revisionsdiv');
				update_user_option($user->ID, 'metaboxhidden_'.$page, $hidden, true);
			}
		}
		#######################
	}

	function delete_folder($data)
	{
		$folder = $data['path']."/".$data['child'];

		if(is_dir($folder) && count(scandir($folder)) == 2)
		{
			rmdir($folder);
			//do_log("Removed Empty Folder: ".$folder);
		}
	}

	function do_optimize()
	{
		global $wpdb;

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
			error_log("Remove orphan relations: ".$wpdb->last_query);

			//$wpdb->query("DELETE FROM ".$wpdb->term_relationships." WHERE term_taxonomy_id = 1 AND object_id NOT IN (SELECT id FROM ".$wpdb->posts.")");
			//"SELECT COUNT(object_id) FROM ".$wpdb->term_relationships." AS tr INNER JOIN ".$wpdb->term_taxonomy." AS tt ON tr.term_taxonomy_id = tt.term_taxonomy_id WHERE tt.taxonomy != 'link_category' AND tr.object_id NOT IN (SELECT ID FROM ".$wpdb->posts.")"
		}

		//Remove orphan usermeta
		$wpdb->get_results("SELECT user_id FROM ".$wpdb->usermeta." WHERE user_id NOT IN (SELECT ID FROM ".$wpdb->users.") LIMIT 0, 1");

		if($wpdb->num_rows > 0)
		{
			error_log("Remove orphan usermeta: ".$wpdb->last_query);

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
			error_log("Remove expired transients: ".$wpdb->last_query);
		}*/

		/* Optimize Tables */
		$result = $wpdb->get_results("SHOW TABLE STATUS");

		foreach($result as $r)
		{
			$strTableName = $r->Name;

			$wpdb->query("OPTIMIZE TABLE ".$strTableName);
		}

		// Remove empty folders in uploads
		list($upload_path, $upload_url) = get_uploads_folder();
		get_file_info(array('path' => $upload_path, 'folder_callback' => array($this, 'delete_folder')));

		if(is_multisite() && !(get_option('option_uploads_done') > DEFAULT_DATE))
		{
			$this->do_fix();
		}

		//Remove unused tables
		if(is_plugin_active('email-log/email-log.php') == false)
		{
			mf_uninstall_plugin(array(
				'options' => array('email-log-db'),
				'tables' => array('email_log'),
			));
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

		header('Content-Type: application/json');
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
			$url = admin_url("edit.php?post_type=".$post_type);
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

		$obj_theme_core = new mf_theme_core();

		echo $before_widget
			.$obj_theme_core->get_logo(array('display' => $instance['logo_display'], 'title' => $instance['logo_title'], 'image' => $instance['logo_image'], 'description' => $instance['logo_description']))
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
					//echo get_file_button(array('name' => $this->get_field_name('logo_image'), 'value' => $instance['logo_image']));
					echo get_media_library(array('name' => $this->get_field_name('logo_image'), 'value' => $instance['logo_image'], 'type' => 'image'));
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

		$instance['search_placeholder'] = sanitize_text_field($new_instance['search_placeholder']);
		$instance['search_animate'] = sanitize_text_field($new_instance['search_animate']);

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
			'classname' => 'theme_news',
			'description' => __("Display News/Posts", 'lang_theme_core')
		);

		$control_ops = array('id_base' => 'theme-news-widget');

		$this->arr_default = array(
			'news_title' => "",
			'news_type' => 'original',
			'news_categories' => array(),
			'news_amount' => 1,
			'news_display_arrows' => 'no',
			'news_autoscroll_time' => 5,
			//'news_display_excerpt' => 'no',
			'news_page' => 0,
		);

		parent::__construct('theme-news-widget', __("News", 'lang_theme_core'), $widget_ops, $control_ops);
	}

	function get_posts($instance)
	{
		global $wpdb;

		$this->arr_news = array();

		if(!($instance['news_amount'] > 0)){	$instance['news_amount'] = 3;}

		$query_join = $query_where = "";

		if(count($instance['news_categories']) > 0)
		{
			$query_join .= " INNER JOIN ".$wpdb->term_relationships." ON ".$wpdb->posts.".ID = ".$wpdb->term_relationships.".object_id INNER JOIN ".$wpdb->term_taxonomy." USING (term_taxonomy_id)";
			$query_where .= " AND term_id IN('".implode("','", $instance['news_categories'])."')";
		}

		$result = $wpdb->get_results("SELECT ID, post_title, post_excerpt FROM ".$wpdb->posts.$query_join." WHERE post_type = 'post' AND post_status = 'publish'".$query_where." ORDER BY post_date DESC LIMIT 0, ".$instance['news_amount']);

		if($wpdb->num_rows > 0)
		{
			$post_thumbnail_size = 'large'; //$wpdb->num_rows > 2 ? 'medium' :

			foreach($result as $post)
			{
				$post_id = $post->ID;
				$post_title = $post->post_title;
				$post_excerpt = $post->post_excerpt;

				$post_thumbnail = '';

				if(has_post_thumbnail($post_id))
				{
					$post_thumbnail = get_the_post_thumbnail($post_id, $post_thumbnail_size);
				}

				if($post_thumbnail == '' && $instance['news_amount'] > 1)
				{
					$post_thumbnail = get_image_fallback();
				}

				$this->arr_news[$post_id] = array(
					'title' => $post_title,
					'url' => get_permalink($post_id),
					'image' => $post_thumbnail,
					'excerpt' => $post_excerpt,
				);
			}
		}
	}

	function widget($args, $instance)
	{
		extract($args);

		$instance = wp_parse_args((array)$instance, $this->arr_default);

		$this->get_posts($instance);

		$rows = count($this->arr_news);

		if($rows > 0)
		{
			$display_news_scroll = $rows > 3 && $instance['news_display_arrows'] == 'yes';

			if($display_news_scroll)
			{
				$plugin_include_url = plugin_dir_url(__FILE__);
				$plugin_version = get_plugin_version(__FILE__);

				mf_enqueue_style('style_theme_news_scroll', $plugin_include_url."style_news_scroll.css", $plugin_version);
				mf_enqueue_script('script_theme_news_scroll', $plugin_include_url."script_news_scroll.js", $plugin_version);
			}

			echo $before_widget;

				if($instance['news_title'] != '')
				{
					echo $before_title
						.$instance['news_title']
					.$after_title;
				}

				echo "<div class='section ".$instance['news_type']." ".($rows > 1 ? "news_multiple" : "news_single").($display_news_scroll ? " news_scroll" : "")."'".($instance['news_autoscroll_time'] > 0 ? " data-autoscroll='".$instance['news_autoscroll_time']."'" : "").">";

					if($rows > 1)
					{
						echo "<ul class='text_columns ".($rows % 3 == 0 || $rows > 6 ? "columns_3" : "columns_2")."'>";

							foreach($this->arr_news as $page)
							{
								echo "<li>
									<a href='".$page['url']."'>
										<div class='image'>".$page['image']."</div>
										<h4>".$page['title']."</h4>"
										.apply_filters('the_content', $page['excerpt'])
									."</a>
								</li>";
							}

						echo "</ul>";

						if($instance['news_page'] > 0)
						{
							echo "<p class='read_more'><a href='".get_permalink($instance['news_page'])."'>".__("Read More", 'lang_theme_core')."</a></p>";
						}
					}

					else
					{
						foreach($this->arr_news as $page)
						{
							echo "<a href='".$page['url']."'>";

								if($page['image'] != '')
								{
									echo "<div class='image'>".$page['image']."</div>";
								}

								echo "<h4>".$page['title']."</h4>"
								.apply_filters('the_content', $page['excerpt'])
								."<p class='read_more'>".__("Read More", 'lang_theme_core')."</p>"
							."</a>";
						}
					}

				echo "</div>"
			.$after_widget;
		}
	}

	function update($new_instance, $old_instance)
	{
		$instance = $old_instance;

		$new_instance = wp_parse_args((array)$new_instance, $this->arr_default);

		$instance['news_title'] = sanitize_text_field($new_instance['news_title']);
		$instance['news_type'] = sanitize_text_field($new_instance['news_type']);
		$instance['news_categories'] = is_array($new_instance['news_categories']) ? $new_instance['news_categories'] : array();
		$instance['news_amount'] = sanitize_text_field($new_instance['news_amount']);
		$instance['news_display_arrows'] = sanitize_text_field($new_instance['news_display_arrows']);
		$instance['news_autoscroll_time'] = $new_instance['news_autoscroll_time'] >= 5 ? sanitize_text_field($new_instance['news_autoscroll_time']) : 0;
		//$instance['news_display_excerpt'] = sanitize_text_field($new_instance['news_display_excerpt']);
		$instance['news_page'] = sanitize_text_field($new_instance['news_page']);

		return $instance;
	}

	function get_categories_for_select($data = array())
	{
		if(!isset($data['hierarchical'])){		$data['hierarchical'] = true;}

		$arr_data = array();

		$arr_categories = get_categories(array(
			'hierarchical' => $data['hierarchical'],
			'hide_empty' => 1,
		));

		foreach($arr_categories as $category)
		{
			$arr_data[$category->cat_ID] = ($data['hierarchical'] && $category->parent > 0 ? "&nbsp;&nbsp;&nbsp;" : "").$category->name;
		}

		return $arr_data;
	}

	function form($instance)
	{
		$instance = wp_parse_args((array)$instance, $this->arr_default);

		$instance_temp = $instance;
		$instance_temp['news_amount'] = 9;
		$this->get_posts($instance_temp);

		echo "<div class='mf_form'>";

			$count_temp = count($this->arr_news);

			if($count_temp > 0)
			{
				$arr_news_types = array(
					'original' => __("Original", 'lang_theme_core'),
					'postit' => __("Post It", 'lang_theme_core'),
				);

				$arr_data_pages = array();
				get_post_children(array('add_choose_here' => true), $arr_data_pages);

				echo show_textfield(array('name' => $this->get_field_name('news_title'), 'text' => __("Title", 'lang_theme_core'), 'value' => $instance['news_title']))
				.show_select(array('data' => $arr_news_types, 'name' => $this->get_field_name('news_type'), 'text' => __("Design", 'lang_theme_core'), 'value' => $instance['news_type']))
				.show_select(array('data' => $this->get_categories_for_select(), 'name' => $this->get_field_name('news_categories')."[]", 'text' => __("Categories", 'lang_theme_core'), 'value' => $instance['news_categories']))
				."<div class='flex_flow'>"
					.show_textfield(array('type' => 'number', 'name' => $this->get_field_name('news_amount'), 'text' => __("Amount", 'lang_theme_core'), 'value' => $instance['news_amount'], 'xtra' => " min='0' max='".$count_temp."'"))
					.show_select(array('data' => get_yes_no_for_select(), 'name' => $this->get_field_name('news_display_arrows'), 'text' => __("Display Arrows", 'lang_theme_core'), 'value' => $instance['news_display_arrows']));

					if($instance['news_display_arrows'] == 'yes')
					{
						echo show_textfield(array('type' => 'number', 'name' => $this->get_field_name('news_autoscroll_time'), 'text' => __("Autoscroll", 'lang_theme_core'), 'value' => $instance['news_autoscroll_time'], 'xtra' => " min='0' max='60'"));
					}

				echo "</div>
				<div class='flex_flow'>"
					//.show_select(array('data' => get_yes_no_for_select(), 'name' => $this->get_field_name('news_display_excerpt'), 'text' => __("Display Excerpt", 'lang_theme_core'), 'value' => $instance['news_display_excerpt']))
					.show_select(array('data' => $arr_data_pages, 'name' => $this->get_field_name('news_page'), 'text' => __("Read More", 'lang_theme_core'), 'value' => $instance['news_page']))
				."</div>";
			}

			else
			{
				echo __("There are no posts to display in this widget", 'lang_theme_core');
			}

		echo "</div>";
	}
}

class widget_theme_core_promo extends WP_Widget
{
	function __construct()
	{
		$widget_ops = array(
			'classname' => 'theme_promo theme_news',
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

			$result = $wpdb->get_results("SELECT ID, post_title, post_content FROM ".$wpdb->posts." WHERE post_type = 'page' AND post_status = 'publish' AND ID IN('".implode("','", $instance['promo_include'])."') ORDER BY menu_order ASC");

			if($wpdb->num_rows > 0)
			{
				$post_thumbnail_size = 'large';

				foreach($result as $post)
				{
					$post_id = $post->ID;
					$post_title = $post->post_title;
					$post_content = $post->post_content;

					if(strlen($post_content) < 60 && preg_match("/youtube\.com|youtu\.be/i", $post_content))
					{
						$arr_pages[$post_id] = array(
							'content' => $post_content,
						);
					}

					else
					{
						$post_thumbnail = "";

						if(has_post_thumbnail($post_id))
						{
							$post_thumbnail = get_the_post_thumbnail($post_id, $post_thumbnail_size);
						}

						if($post_thumbnail == '')
						{
							$post_thumbnail = get_image_fallback();
						}

						$post_url = get_permalink($post_id);

						$arr_pages[$post_id] = array(
							'title' => $post_title,
							'url' => $post_url,
							'image' => $post_thumbnail,
						);
					}
				}
			}

			$rows = count($arr_pages);

			if($rows > 0)
			{
				echo $before_widget;

					if($instance['promo_title'] != '')
					{
						echo $before_title
							.$instance['promo_title']
						.$after_title;
					}

					echo "<div class='section original'>
						<ul class='text_columns ".($rows % 3 == 0 || $rows > 6 ? "columns_3" : "columns_2")."'>";

							foreach($arr_pages as $page)
							{
								if(isset($page['image']))
								{
									echo "<li>
										<a href='".$page['url']."'>
											<div class='image'>".$page['image']."</div>
											<h4>".$page['title']."</h4>
										</a>
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

		$instance['promo_title'] = sanitize_text_field($new_instance['promo_title']);
		$instance['promo_include'] = is_array($new_instance['promo_include']) ? $new_instance['promo_include'] : array();

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