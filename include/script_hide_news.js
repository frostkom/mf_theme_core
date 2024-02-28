jQuery(function($)
{
	$(".widget.theme_news .hide_news").each(function()
	{
		var dom_obj = $(this),
			news_id = dom_obj.data('news_id');

		if(document.cookie.indexOf("hide_news_" + news_id + "=") !== -1)
		{
			$(this).parent(".theme_news").addClass('hide');
		}
	});

	$(document).on('click', ".widget.theme_news .hide_news", function()
	{
		var d = new Date();
		d.setTime(d.getTime() + (365 * 24 * 60 * 60 * 1000));

		document.cookie = "hide_news_" + $(this).data('news_id') + "=true; path=/; expires=" + d.toUTCString();

		$(this).parent(".theme_news").fadeOut();
	});
});