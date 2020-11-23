jQuery(function($)
{
	function set_breakpoint()
	{
		var dom_obj = $("body"),
			value = window.getComputedStyle(document.querySelector("body"), ':before').getPropertyValue('content').replace(/\"/g, '');

		dom_obj.removeClass('is_mobile is_tablet is_desktop');

		if(typeof value !== 'undefined' && value != '')
		{
			dom_obj.addClass(value);
		}
	};

	set_breakpoint();

	$(window).resize(function()
	{
		set_breakpoint();
	});

	$(".widget.theme_news .news_expand_content .read_more a").on('click', function()
	{
		$(this).parent(".read_more").addClass('hide').siblings(".excerpt").addClass('hide').siblings(".content").removeClass('hide');

		return false;
	});
});