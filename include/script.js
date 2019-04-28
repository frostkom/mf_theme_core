jQuery(function($)
{
	function set_breakpoint()
	{
		var dom_obj = $("body"),
			value = window.getComputedStyle(document.querySelector("body"), ':before').getPropertyValue('content').replace(/\"/g, '');

		dom_obj.removeClass('is_size_palm is_size_lap is_size_desk is_mobile is_tablet is_desktop');

		if(typeof value !== 'undefined' && value != '')
		{
			/* Fallback just in case something old is left */
			switch(value)
			{
				case 'is_size_desk':
					value += " is_desktop";
				break;

				case 'is_size_lap':
					value += " is_tablet";
				break;

				case 'is_size_palm':
					value += " is_mobile";
				break;
			}

			dom_obj.addClass(value); /* is_unknown */
		}
	};

	set_breakpoint();

	$(window).resize(function()
	{
		set_breakpoint();
	});
});