jQuery(function($)
{
	/* Same as in mf_form/include/script.js */
	function update_range_text(selector)
	{
		if(selector.siblings("label").children("span").length == 0)
		{
			selector.siblings("label").append(" <span></span>");
		}

		selector.siblings("label").children("span").text(selector.val());
	}

	$(".customize-control-range input[type='range']").each(function()
	{
		update_range_text($(this));
	});

	$(document).on('change', ".customize-control-range input[type='range']", function()
	{
		update_range_text($(this));
	});
});