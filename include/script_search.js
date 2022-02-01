jQuery(function($)
{
	var dom_search_obj = $(".searchform input"),
		dom_heading_obj = $("article h2, #main h2");

	if(dom_search_obj.length > 0)
	{
		$(document).on('keydown', function(e)
		{
			var dom_focus = $(":focus"),
				dom_focus_id = (dom_focus.length > 0 ? dom_focus[0].id : '');

			if(dom_focus_id == '' && dom_search_obj.is(':focus') == false)
			{
				/* Backspace or Delete */
				/*if(e.keyCode == 8 || e.keyCode == 46){}*/

				/* Shift / Ctrl / Alt */
				/*if(e.keyCode >= 16 && e.keyCode <= 18){}*/
				if(e.ctrlKey || e.shiftKey || e.altKey){}

				/* 0-9, A-Z or Numpad */
				else if(e.keyCode >= 48 && e.keyCode <= 57 || e.keyCode >= 65 && e.keyCode <= 90 || e.keyCode >= 96 && e.keyCode <= 105)
				{
					dom_search_obj.focus();
				}

				/*var inp = String.fromCharCode(e.keyCode);
				if(/[a-zA-Z0-9-_ ]/.test(inp)){}*/
			}
		});
	}

	if(dom_heading_obj.length > 1)
	{
		$(document).on('keyup', function(e)
		{
			if(dom_search_obj.is(':focus'))
			{
				if(dom_heading_obj.parent("article").length > 0)
				{
					dom_heading_obj.parent("article").removeClass('hide');
				}

				else if(dom_heading_obj.parents("li").length > 0)
				{
					dom_heading_obj.parents("li").removeClass('hide');
				}

				/*else
				{
					console.log("There was no parent to " , dom_heading_obj);
				}*/

				var search = dom_search_obj.val().toLowerCase();

				if(search != '')
				{
					dom_heading_obj.each(function()
					{
						var dom_heading_obj_temp = $(this);

						if(dom_heading_obj_temp.text().toLowerCase().indexOf(search) === -1)
						{
							if(dom_heading_obj_temp.parent("article").length > 0)
							{
								dom_heading_obj_temp.parent("article").addClass('hide');
							}

							else if(dom_heading_obj_temp.parents("li").length > 0)
							{
								dom_heading_obj_temp.parents("li").addClass('hide');
							}
						}
					});
				}
			}
		});
	}
});