jQuery(function($)
{
	function set_style_url(self)
	{
		if(self.is(':disabled'))
		{
			var arr_style_url = $('#style-css').attr('href').split("#");

			$('#style-css').attr({'href': arr_style_url[0] + "#r=" + Math.random()});
		}

		else
		{
			setTimeout(function()
			{
				set_style_url(self)
			}, 1000);
		}
	}

	$(parent.document).on('click', '#save', function()
	{
		set_style_url($(this));		
	});
});