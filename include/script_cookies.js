jQuery(function($)
{
	$('#accept_cookies .button').on('click', function()
	{
		var d = new Date();
		d.setTime(d.getTime() + (365 * 24 * 60 * 60 * 1000));

		document.cookie = "cookie_accepted=true; expires=" + d.toUTCString();

		$(this).parents('#accept_cookies').fadeOut();
	});
});