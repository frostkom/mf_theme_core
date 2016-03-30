jQuery(function($)
{
	var breakpoint = {};

	breakpoint.refreshValue = function()
	{
		this.value = window.getComputedStyle(document.querySelector('body'), ':before').getPropertyValue('content').replace(/\"/g, '');

		$('body').removeClass('is_unknown is_phone is_tablet is_desktop').addClass('is_' + breakpoint.value);
	};

	$(window).resize(function()
	{
		breakpoint.refreshValue();
	}).resize();
});