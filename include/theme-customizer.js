(function($)
{
	wp.customize('aside_size', function(value)
	{
		value.bind(function(newval)
		{
			console.log(newval);

			update_style_url()
		});
	});

	function update_style_url()
	{
		var arr_style_url = $('#style-css').attr('href').split("?"),
			new_style_url = arr_style_url[0] + "?r=" + Math.random();

		$('#style-css').attr({'href': new_style_url});

		console.log(new_style_url);
	}
})(jQuery);