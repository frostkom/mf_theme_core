jQuery(function($)
{
	if(document.cookie.indexOf("cookie_accepted=") !== -1)
	{
		$("#accepted_cookies").fadeIn();
	}

	else
	{
		$("#accept_cookies").fadeIn();
	}

	$(document).on('click', "#accept_cookies .button:first-of-type", function()
	{
		var d = new Date();
		d.setTime(d.getTime() + (365 * 24 * 60 * 60 * 1000));

		document.cookie = "cookie_accepted=true; path=/; expires=" + d.toUTCString();

		$("#accept_cookies").fadeOut();
		$("#accepted_cookies").fadeIn();
	});

	$(document).on('click', "#accepted_cookies > span", function()
	{
		document.cookie = "cookie_accepted=true; path=/; max-age=0";
		document.cookie = "cookie_accepted=true; max-age=0";

		$("#accepted_cookies").fadeOut();
		$("#accept_cookies").fadeIn();
	});
});