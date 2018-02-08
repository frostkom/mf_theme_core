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
});