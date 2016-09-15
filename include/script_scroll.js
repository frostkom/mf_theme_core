jQuery(function($)
{
	function show_or_hide_scroll()
	{
		if($(this).scrollTop() > 300)
		{
			$('#scroll_to_top').fadeIn();
		}

		else
		{
			$('#scroll_to_top').fadeOut();
		}
	}

	$('body').append("<a id='scroll_to_top'><i class='fa fa-lg fa-arrow-up'></i></a>");

	show_or_hide_scroll();

	$(window).scroll(function()
	{
		show_or_hide_scroll();
	});

	$('body').on('click', '#scroll_to_top', function()
	{
		$('html, body').animate({scrollTop: 0}, 800);

		return false;
	});
});