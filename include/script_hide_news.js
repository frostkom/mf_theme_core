jQuery(function($)
{
	$(document).on('click', ".widget.theme_news .hide_news", function()
	{
		var d = new Date();
		d.setTime(d.getTime() + (365 * 24 * 60 * 60 * 1000));

		document.cookie = "hide_news_" + $(this).data('hide_id') + "=true; expires=" + d.toUTCString();

		$(this).parent(".theme_news").fadeOut();
	});
});