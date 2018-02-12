jQuery(function($)
{
	$.fn.news_scroll = function()
	{
		var dom_obj = this,
			dom_list = dom_obj.find("ul"),
			dom_items = dom_obj.find("ul li"),
			news_amount = dom_items.length,
			news_current = 0,
			news_display = 3,
			news_autoscroll_time = parseInt(dom_obj.attr('data-autoscroll')) || 0;

		if(news_amount <= 3)
		{
			return false;
		}

		function change_news()
		{
			var i = 0;

			if(news_current < 0)
			{
				news_current = news_amount - news_display;
			}

			if(news_current > (news_amount - news_display))
			{
				news_current = 0;
			}

			dom_list.removeClass('translate_0 translate_1 translate_2 translate_3 translate_4 translate_5 translate_6 translate_7 translate_8').addClass('translate_' + news_current);

			dom_items.each(function()
			{
				var dom_news = $(this);

				if(i >= news_current && i < (news_current + news_display))
				{
					dom_news.removeClass('inactive');
				}

				else
				{
					dom_news.addClass('inactive');
				}

				i++;
			});

			display_arrows();
		}

		function display_arrows()
		{
			if(news_current <= 0)
			{
				dom_obj.find(".arrow_left").fadeOut();
			}

			else
			{
				dom_obj.find(".arrow_left").fadeIn();
			}

			if(news_current >= (news_amount - news_display))
			{
				dom_obj.find(".arrow_right").fadeOut();
			}

			else
			{
				dom_obj.find(".arrow_right").fadeIn();
			}
		}

		if(news_autoscroll_time > 0)
		{
			var news_interval = setInterval(function()
			{
				news_current++;

				change_news();

			}, news_autoscroll_time * 1000);
		}

		dom_obj.prepend("<div class='controls arrow_left'><i class='fa fa-chevron-left'></i></div>");
		dom_obj.append("<div class='controls arrow_right'><i class='fa fa-chevron-right'></i></div>");

		change_news();

		dom_obj.on('click', ".arrow_left .fa, .arrow_right .fa", function()
		{
			if(news_interval)
			{
				clearInterval(news_interval);
			}

			if($(this).parent(".controls").hasClass('arrow_left'))
			{
				news_current--;
			}

			else
			{
				news_current++;
			}

			change_news();
		});
	};

	$(".widget.theme_news .news_scroll").news_scroll();
});