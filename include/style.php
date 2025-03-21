<?php

if(!defined('ABSPATH'))
{
	header("Content-Type: text/css; charset=utf-8");

	$folder = str_replace("/wp-content/plugins/mf_theme_core/include", "/", dirname(__FILE__));

	require_once($folder."wp-load.php");
}

echo "@media all
{
	body:before
	{
		display: none;
	}

	html, body, div, h1, h2, h3, h4, h5, h6, p, ul, li, ol, button, form, blockquote, header, nav, #mf-after-header, #mf-pre-content, #mf-content, article, section, .aside, #mf-pre-footer, footer
	{
		margin: 0;
		padding: 0;
	}

	body, div, a, p, ol, ul, li, form, label, input, select, textarea, button, blockquote, iframe, h1, h2, h3, h4, h5, h6, header, nav, #mf-after-header, #mf-pre-content, #mf-content, article, section, .aside, #mf-pre-footer, footer
	{
		box-sizing: border-box;
	}

	a
	{
		color: inherit;
		text-decoration: none;
	}

	/* Images */
	img
	{
		border: 0;
		height: auto;
		max-width: 100%;
	}

		.size-full, *:not(.is-resized) > img[class*='align'], *:not(.is-resized) > img[class*='wp-image-']
		{
			width: auto;
		}

		.image_fallback
		{
			background-image: url('data:image/svg+xml;base64,PHN2ZyB4bWxucz0naHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmcnIHhtbG5zOnhsaW5rPSdodHRwOi8vd3d3LnczLm9yZy8xOTk5L3hsaW5rJyB2aWV3Qm94PScwIDAge3t3fX0ge3tofX0nPjxkZWZzPjxzeW1ib2wgaWQ9J2EnIHZpZXdCb3g9JzAgMCA5MCA2Nicgb3BhY2l0eT0nMC4zJz48cGF0aCBkPSdNODUgNXY1Nkg1VjVoODBtNS01SDB2NjZoOTBWMHonLz48Y2lyY2xlIGN4PScxOCcgY3k9JzIwJyByPSc2Jy8+PHBhdGggZD0nTTU2IDE0TDM3IDM5bC04LTYtMTcgMjNoNjd6Jy8+PC9zeW1ib2w+PC9kZWZzPjx1c2UgeGxpbms6aHJlZj0nI2EnIHdpZHRoPScyMCUnIHg9JzQwJScvPjwvc3ZnPg==');
		}

	.clear
	{
		clear: both;
	}

	.aligncenter
	{
		margin: .5em 0;
		text-align: center;
	}

	.alignleft
	{
		float: left;
		margin: .5em 1em .5em 0;
	}

	.alignright
	{
		float: right;
		margin: .5em 0 .5em 1em;
	}

	.is_desktop .hide_on_desktop, .is_tablet .hide_on_tablet, .is_mobile .hide_on_mobile
	{
		display: none !important;
	}

	/* Content */
	article > .meta > *
	{
		color: #808080;
		display: inline-block;
		font-style: italic;
		margin-bottom: 1em;
	}

		article > .meta > * + *
		{
			margin-left: .5em;
		}

		article > .meta > span
		{
			font-weight: bold;
		}

	.embed_content
	{
		position: relative;
	}

		.embed_content:before
		{
			display: block;
			content: '';
			width: 100%;
			padding-top: 56.25%;
		}

		.embed_content iframe
		{
			position: absolute;
			top: 0;
			left: 0;
			height: 100%;
			width: 100%;
		}

	/* Full Width */
	body:not(.is_mobile) nav.full_width:not(.is_hamburger), .full_width > div > .widget, .widget.full_width
	{
		left: 50%;
		margin-left: -50vw;
		margin-right: -50vw;
		position: relative;
		right: 50%;
		width: 100vw;
	}

		.full_width > div > .widget.widget_media_image
		{
			text-align: center;
		}

	.widget .section .text_columns
	{
		display: flex;
		flex-wrap: wrap;
	}

		.is_mobile .widget .section .text_columns, .aside.left .widget .section .text_columns, .aside.right .widget .section .text_columns
		{
			display: block;
		}

		.widget .section .columns_2 > li
		{
			flex: 1 0 50%;
			min-width: 50%;
		}

		.widget .section .columns_3 > li
		{
			flex: 1 0 33%;
			min-width: 33%;
		}

		.widget .section .columns_4 > li
		{
			flex: 1 0 25%;
			min-width: 25%;
		}

			.is_tablet .widget .section .columns_4 > li
			{
				flex: 1 0 50%;
				min-width: 50%;
			}";

	if((int)apply_filters('get_widget_search', 'theme-widget-area-widget') > 0)
	{
		echo ".widget.theme_widget_area .widget_columns, header .widget_columns
		{
			clear: both;
			display: flex;
			flex-wrap: wrap;
		}

			.is_mobile .widget.theme_widget_area .widget_columns, .is_mobile header .widget_columns
			{
				flex-direction: column;
			}

				.is_mobile .widget.theme_widget_area .widget_columns .widget + .widget, .is_mobile header .widget_columns .widget + .widget
				{
					margin-top: 1em;
				}";

			for($i = 2; $i <= 6; $i++)
			{
				$width = (100 / $i);

				echo ".widget.theme_widget_area .widget_columns.columns_".$i." .widget, header .widget_columns.columns_".$i." .widget
				{
					flex: 1 0 ".$width."%;
					min-width: ".$width."%;
				}";

				if($i >= 4)
				{
					echo ".is_tablet .widget.theme_widget_area .widget_columns.columns_".$i." .widget, .is_tablet header .widget_columns.columns_".$i." .widget
					{
						flex: 1 0 50%;
						min-width: 50%;
					}";
				}
			}

			$widget_area_widget = get_option('widget_theme-widget-area-widget');

			if(is_array($widget_area_widget))
			{
				foreach($widget_area_widget as $widget)
				{
					if(isset($widget['widget_area_padding']) && $widget['widget_area_padding'] != '')
					{
						echo "#widget_area_".str_replace("-", "_", $widget['widget_area_id'])." .widget:nth-child(2n + 1)
						{
							padding-right: ".$widget['widget_area_padding'].";
						}

						#widget_area_".str_replace("-", "_", $widget['widget_area_id'])." .widget:nth-child(2n)
						{
							padding-left: ".$widget['widget_area_padding'].";
						}

							.is_mobile #widget_area_".str_replace("-", "_", $widget['widget_area_id'])." .widget
							{
								padding-right: 0;
								padding-left: 0;
							}";
					}
				}
			}
	}

	if((int)apply_filters('get_widget_search', 'theme-news-widget') > 0 || (int)apply_filters('get_widget_search', 'theme-related-news-widget') > 0 || (int)apply_filters('get_widget_search', 'theme-promo-widget') > 0)
	{
		/* If > 1 */
		echo "#mf-pre-header .widget.theme_news
		{
			text-align: center;
		}

			#mf-pre-header .widget.theme_news h3
			{
				display: inline-block;
				font-size: 1.2em;
			}

			#mf-pre-header .widget.theme_news .section.news_single
			{
				display: inline-block;
				overflow: unset;
				white-space: nowrap;
			}

				#mf-pre-header .widget.theme_news .section.news_single h4
				{
					display: inline-block;
					float: none;
					font-size: 1.2em;
					width: auto;
				}

				#mf-pre-header .widget.theme_news .section.news_single .read_more
				{
					display: inline-block;
					float: none;
					font-size: 1.2em;
					margin-top: .5em;
				}

		.aside.after_content .widget.theme_news .section
		{
			padding-left: 0;
			padding-right: 0;
		}

			.aside .widget.theme_news ul
			{
				list-style: none;
				margin: 0 -.5em -.8em;

				/* Fix for iOS */
				justify-content: flex-start;
				align-items: stretch;
			}

				.widget.theme_news li
				{
					margin-bottom: .8em;
					overflow: hidden;
					padding: 0 .5%;
					position: relative;
				}

					.widget.theme_news ul:not(.text_columns) li + li
					{
						padding-top: .5em;
					}

					.widget.theme_news li .image
					{
						height: 100%; /* Does not work properly in older Safari */
						overflow: hidden;
						position: relative;
					}

						.widget.theme_news li .image img
						{
							display: block;
							height: 100%;
							object-fit: cover;
							transition: all 1s ease;
							width: 100%;
						}

							.widget.theme_news li:hover .image img
							{
								transform: scale(1.1);
							}

					.widget.theme_news li .video iframe
					{
						width: 100%;
					}

			.is_mobile .widget.theme_news ul
			{
				display: block;
			}

				.is_mobile .widget.theme_news li
				{
					max-width: 100%;
				}";

			/* Style Specific */
			echo "

			#wrapper .widget.theme_news .original.display_page_titles li h4
			{
				bottom: 0;
				color: #fff;
				left: 0;
				position: absolute;
				right: 0;
			}";

				echo "#wrapper .widget.theme_news .original.display_page_titles li h4
				{
					background: rgba(0, 0, 0, .7);
					border-radius: .3em;
					margin: 6% 12%;
					padding: 1.4em 1.4em 1.7em;
					text-align: center;
				}";

					/*echo ".widget.theme_news .original li .image
					{
						background: rgba(0, 0, 0, .1);
					}

						.widget.theme_news .original.display_page_titles li .image:after
						{
							background: linear-gradient(to top, rgba(0, 0, 0, .5) 0, transparent 100%);
							bottom: 0;
							content: '';
							height: 50%;
							left: 0;
							position: absolute;
							right: 0;
							transition: all .5s ease;
						}

							.widget.theme_news .original.display_page_titles li:hover .image:after
							{
								height: 100%;
							}

					#wrapper .widget.theme_news .original.display_page_titles li h4
					{
						border-bottom: .1em solid #fff;
						margin: 1.5em 1em 0;
						padding: 0 0 1.4em;
						transition: all .5s ease;
					}

						#wrapper .widget.theme_news .original.display_page_titles li:hover h4
						{
							margin-bottom: 2.5em;
							padding-bottom: .2em;
						}";*/

			echo ".widget.theme_news .postit ul
			{
				padding-top: 1em;
			}

				.widget.theme_news .postit li > a
				{
					background-color: #f4f39e;
					border-color: #dee184;
					box-shadow: 0 1px 3px rgba(0, 0, 0, .25);
					display: block;
					height: 18em;
					margin: 1em;
					padding: 1.5em 1em;
					-webkit-tap-highlight-color: transparent;
					text-align: center;
					transform: rotate(1deg);
					max-width: 20em;
				}

					.widget.theme_news .postit .columns_4 li > a
					{
						height: 12em;
					}

					.widget.theme_news .postit li:nth-child(2n+1) > a
					{
						transform: rotate(-3deg);
					}

					.widget.theme_news .postit li:nth-child(3n+2) > a
					{
						transform: rotate(2deg);
					}

					.widget.theme_news .postit li:nth-child(5n+3) > a
					{
						transform: rotate(3deg);
					}

					.widget.theme_news .postit li:nth-child(7n+5) > a
					{
						transform: rotate(-1deg);
					}

					.widget.theme_news .postit li:nth-child(11n+7) > a
					{
						transform: rotate(-2deg);
					}

					.widget.theme_news .postit li > a:after
					{
						background: rgba(254, 254, 254, .6);
						border: 1px solid #fff;
						box-shadow: 0 0 .3em rgba(0, 0, 0, .1);
						content: '';
						display: block;
						height: 2em;
						left: 50%;
						position: absolute;
						transform: translateX(-50%);
						top: -1.2em;
						width: 8em;
					}

						.widget.theme_news .postit li h4
						{
							overflow: hidden;
						}

			.widget.theme_news .simple li h4, .widget.theme_news .simple li p
			{
				color: #333;
			}";

			/* If == 1 */
			echo ".widget.theme_news .section.news_single
			{
				overflow: hidden;
			}

				.widget.theme_news .section.news_single > a > *
				{
					float: left;
					width: 38%;
				}

				.widget.theme_news .section.news_single .image
				{
					float: right;
					width: 60%;
				}

				.widget.theme_news .section.news_single h3
				{
					padding-left: 0;
				}

					.aside.right .widget.theme_news .section.news_single > a > *, .aside.left .widget.theme_news .section.news_single > a > *
					{
						width: 100%;
					}"; // .is_mobile .widget.theme_news .section.news_single > a > *, // This does not work with single news with .hide_news in #mf-pre-header
	}

	if((int)apply_filters('get_widget_search', 'theme-info-widget') > 0)
	{
		echo ".widget_columns .widget.theme_info, .is_mobile .widget.theme_info
		{
			text-align: center;
		}

		.widget.theme_info.postit .section > div
		{
			background: none;
		}

			.widget.theme_info.postit .content
			{
				background-color: #f4f39e;
				border-color: #dee184;
				box-shadow: 0 1px 3px rgba(0, 0, 0, .25);
				display: block;
				height: 18em;
				margin: 1em;
				padding: 1.5em 1em;
				-webkit-tap-highlight-color: transparent;
				transform: rotate(1deg);
				max-width: 20em;
			}

				.widget.theme_info.postit .content h3, .widget.theme_info.postit .content p
				{
					float: none;
					width: 100%;
				}

		.theme_widget_area .widget.theme_info .section
		{
			height: 100%;
			padding: 0;
		}

			.widget.theme_info .section > div
			{
				background: linear-gradient(to bottom, #f9f9fa 0, #eee 100%);
				height: 100%;
				position: relative;
			}

				.theme_widget_area .widget.theme_info:nth-child(2n+1) .section > div
				{
					background: linear-gradient(to bottom, #f9f9fa 0, #fafafa 100%);
				}

		.widget.theme_info .section .image img
		{
			display: block;
		}

		.widget.theme_info .section .content
		{
			overflow: hidden;
			padding: .5em 1em 1em;
		}

			.theme_widget_area .widget.theme_info .section .content
			{
				padding-bottom: 4em;
			}

			.widget.theme_info h3
			{
				overflow: hidden;
				padding-left: 0;
				padding-right: 0;
				white-space: nowrap;
				text-overflow: ellipsis;
			}

				.is_tablet .widget.theme_info h3, .is_tablet .widget.theme_info p, .is_desktop .widget.theme_info h3, .is_desktop .widget.theme_info p
				{
					float: left;
					width: 70%;
				}

					.theme_widget_area .widget.theme_info h3, .theme_widget_area .widget.theme_info p
					{
						float: none;
						width: 100%;
					}

			.is_tablet .widget:not(.theme_widget_area) .widget.theme_info .form_button, .is_desktop .widget:not(.theme_widget_area) .widget.theme_info .form_button
			{
				position: absolute;
				right: 1.5em;
				top: 50%;
				transform: translateY(-50%);
			}

				.theme_widget_area .widget.theme_info .form_button
				{
					font-size: .7em;
					position: absolute;
					bottom: 1em;
					left: 0;
					right: 0;
					top: unset;
				}

					.widget.theme_info .button
					{
						margin: 0;
					}

						.is_mobile .widget.theme_info .button
						{
							margin-top: .5em;
						}

						.theme_widget_area .widget.theme_info .form_button .button
						{
							font-size: 1.6em;
							overflow: hidden;
							text-align: center;
							text-overflow: ellipsis;
							white-space: nowrap;
						}";
	}

echo "}

@media print
{
	body:before
	{
		content: 'is_print';
	}

	body, article
	{
		background: none;
	}

		header, #mf-after-header, #mf-slide-nav, #mf-pre-content, #aside_left, #aside_right, #mf-pre-footer, footer, #window_side
		{
			display: none !important;
		}

			#mf-content > div
			{
				display: block !important; /* Prevents flexbox */
				width: auto;
			}

				article
				{
					min-height: auto;
				}

				article a:after
				{
					content: ' (' attr(href) ') ';
				}
}";