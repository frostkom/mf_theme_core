jQuery(function($)
{
	function set_breakpoint()
	{
		var value = window.getComputedStyle(document.querySelector('body'), ':before').getPropertyValue('content').replace(/\"/g, '');

		$('body').removeClass('is_unknown is_mobile is_tablet is_desktop').addClass(value);
	};

	set_breakpoint();

	$(window).resize(function()
	{
		set_breakpoint();
	});

	$.fn.news_arrows = function()
	{
		var dom_obj = this,
			news_amount = dom_obj.find("ul li").length,
			news_current = 0,
			news_display = 3;

		function change_news()
		{
			var i = 0;

			if(news_current <= 0)
			{
				dom_obj.find(".arrow_left").addClass('hide');
			}

			else
			{
				dom_obj.find(".arrow_left").removeClass('hide');
			}

			if(news_current >= (news_amount - news_display))
			{
				dom_obj.find(".arrow_right").addClass('hide');
			}

			else
			{
				dom_obj.find(".arrow_right").removeClass('hide');
			}

			dom_obj.find("ul li").each(function()
			{
				var dom_news = $(this);

				if(i >= news_current && i < news_current + news_display)
				{
					dom_news.removeClass('hide');
				}

				else
				{
					dom_news.addClass('hide');
				}

				i++;
			});
		}

		if(news_amount > 3)
		{
			dom_obj.prepend("<i class='fa fa-chevron-left controls arrow_left'></i>");
			dom_obj.append("<i class='fa fa-chevron-right controls arrow_right'></i>");

			change_news();
		}

		dom_obj.on('click', ".arrow_left, .arrow_right", function()
		{
			if($(this).hasClass('arrow_left'))
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

	$(".widget.theme_news .news_arrows").news_arrows();
});