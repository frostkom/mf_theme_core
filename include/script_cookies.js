jQuery(function($)
{
	$(document).on('click', "#accept_cookies .button:first-of-type", function()
	{
		var d = new Date();
		d.setTime(d.getTime() + (365 * 24 * 60 * 60 * 1000));

		document.cookie = "cookie_accepted=true; expires=" + d.toUTCString();

		$(this).parents("#accept_cookies").fadeOut();
	});
});