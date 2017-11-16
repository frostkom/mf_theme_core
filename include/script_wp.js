jQuery(function($)
{
	function run_ajax(obj)
	{
		obj.selector.html("<i class='fa fa-spinner fa-spin fa-2x'></i>");

		$.ajax(
		{
			type: "post",
			dataType: "json",
			url: script_theme_core.ajax_url,
			data: {
				action: obj.action
			},
			success: function(data)
			{
				obj.selector.empty();

				obj.button.attr('disabled', true);

				if(data.success)
				{
					obj.selector.html(data.message);
				}

				else
				{
					obj.selector.html(data.error);
				}
			}
		});

		return false;
	}

	$(document).on('click', "button[name=btnOptimizeTheme]", function(e)
	{
		run_ajax(
		{
			'button': $(e.currentTarget),
			'action': 'optimize_theme',
			'selector': $('#optimize_debug')
		});
	});
});