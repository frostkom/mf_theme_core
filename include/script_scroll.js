jQuery(function($)
{
	$('body').append("<a id='scroll_to_top' style='display: none'><i class='fa fa-lg fa-arrow-up'></i></a>");

	$(window).scroll(function()
	{
		if($(this).scrollTop() > 300)
		{
			$('#scroll_to_top').fadeIn();
		}
		
		else
		{
			$('#scroll_to_top').fadeOut();
		}
	});
	
	$('body').on('click', '#scroll_to_top', function()
	{
		$('html, body').animate({scrollTop: 0}, 800);

		return false;
	});
});