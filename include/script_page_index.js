jQuery(function($)
{
	var dom_widget = $(".widget.theme_page_index");

	if(dom_widget.length > 0)
	{
		var output = "",
			dom_article = $("article"),
			dom_headings = dom_article.find("h2:visible, h3:visible"),
			i = 0;

		if(dom_headings.length > 1)
		{
			var positions = [],
				build_toc = function()
				{
					var output = "";

					dom_headings.each(function(i)
					{
						var dom_obj = $(this),
							dom_title = dom_obj.text();

						if(dom_obj.is("h2"))
						{
							var dom_depth = 2;
						}

						else if(dom_obj.is("h3"))
						{
							var dom_depth = 3;
						}

						if(typeof dom_obj.attr('id') === 'undefined')
						{
							dom_obj.attr({'id': 'page_index_' + i});
						}

						var dom_url = "#" + dom_obj.attr('id');

						if(dom_depth == 3)
						{
							output += "<ul>";
						}

							output += "<li>"
								+ "<a href='" + dom_url + "' class='toc-page_index_" + i + "'>"
									+ dom_title
								+ "</a>"
							+ "</li>";

						if(dom_depth == 3)
						{
							output += "</ul>";
						}
					});

					return output;
				},
				get_bottom_off_content = function()
				{
					var offset = dom_article.offset();

					return dom_article.outerHeight() + offset.top;
				},
				get_positions = function()
				{
					dom_headings.each(function(i)
					{
						offset = $(this).offset();
						positions['page_index_' + i] = offset.top - 20;
					});

					return positions;
				},
				set_toc_reading = function()
				{
					var st = $(document).scrollTop(),
						count = 0;

					for(var k in positions)
					{
						var n = parseInt( k.replace('page_index_', '') );

						has_next = typeof positions['page_index_' + ( n + 1 ) ] !== 'undefined',
						not_next = has_next && st < positions['page_index_' + ( n + 1 ) ] ? true : false,
						diff = 0,
						$link = $(".toc-" + k);

						if(has_next)
						{
							diff = ( st - positions[k] ) / ( positions[ 'page_index_' + ( n + 1 ) ] - positions[k] ) * 100;
						}

						else
						{
							diff = ( st - positions[k] ) / ( get_bottom_off_content() - positions[k] ) * 100;
						}

						$link.find('circle').attr('stroke-dashoffset', Math.round( 100 - diff ));

						if(st >= positions[k] && not_next && has_next)
						{
							$(".toc-" + k).addClass('toc-reading');
						}

						else if(st >= positions[k] && ! not_next && has_next)
						{
							$(".toc-" + k).removeClass('toc-reading');
						}

						else if(st >= positions[k] && ! not_next && ! has_next)
						{
							$(".toc-" + k).addClass('toc-reading');
						}

						if(st >= positions[k])
						{
							$(".toc-" + k).addClass('toc-already-read');
						}

						else
						{
							$(".toc-" + k).removeClass('toc-already-read');
						}

						if(st < positions[k])
						{
							$(".toc-" + k).removeClass('toc-already-read toc-reading');
						}

						count++;
					}
				};

			dom_widget.find("div > ul").html(build_toc());

			get_positions();

			$(window).on('resize', function()
			{
				get_positions();
			});

			$(document).on('scroll', function()
			{
				set_toc_reading();
			});

			$(document).on('click', ".theme_page_index a", function()
			{
				var dom_href = $(this).attr('href');

				jQuery("html, body").animate(
				{
					scrollTop: $(dom_href).offset().top
				}, 800);

				return false;
			});
		}
	}
});