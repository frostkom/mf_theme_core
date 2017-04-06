function set_breakpoint()
{
	var value = window.getComputedStyle(document.querySelector('body'), ':before').getPropertyValue('content').replace(/\"/g, '');

	jQuery('body').removeClass('is_unknown is_mobile is_tablet is_desktop').addClass('is_' + value);
};

function on_load_theme_core()
{
	set_breakpoint();
}

jQuery(function($)
{
	on_load_theme_core();

	if(typeof collect_on_load == 'function')
	{
		collect_on_load('on_load_theme_core');
	}

	$(window).resize(function()
	{
		set_breakpoint();
	});
});