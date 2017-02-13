var arr_functions = [];

function collect_on_load(str_function)
{
	arr_functions.push(str_function);
}

jQuery(function($)
{
	"use strict";

	if(typeof history.pushState != 'undefined')
	{
		$('body').append("<div id='body_history'><i class='fa fa-spinner fa-spin fa-2x'></i></div>");

		var dom_element = "#wrapper",
			dom_obj = $(dom_element),
			dom_overlay = $('#body_history');

		String.prototype.decodeHTML = function()
		{
			return $("<div>", {html: "" + this}).html();
		};

		function showOverlay()
		{
			dom_overlay.fadeIn();
		}

		function hideOverlay()
		{
			dom_overlay.fadeOut();
		}

		function run_on_load()
		{
			$.each(arr_functions, function(index, value)
			{
				eval(value + "();");
			});
		}

		function loadCallback(html, status, xhr)
		{
			if(status == "error")
			{
				console.log(xhr.status + " " + xhr.statusText);
			}

			else
			{
				$('html, body').animate({scrollTop: 0}, 800);

				var new_title = html.match(/<title>(.*?)<\/title>/)[1].trim().decodeHTML(),
					new_class = html.match(/<body.*?class\=[\'\"](.*?)[\'\"].*>/)[1].trim();

				document.title = new_title;

				$('body').attr({'class': new_class});

				hideOverlay();

				run_on_load();
			}
		}

		function requestContent(url)
		{
			showOverlay();

			dom_obj.load(url + ' ' + dom_element + ">*", loadCallback);
		}

		$(window).on('popstate', function(e)
		{
			if(e.originalEvent.state !== null)
			{
				var url = location.href;

				requestContent(url);
			}

			else
			{
				location.reload();
			}
		});

		$(document).on('click', 'a', function(e)
		{
			var url = $(this).attr("href");

			if(url.indexOf('#') > -1 || url.indexOf('wp-admin') > -1){}

			else if(url.indexOf(document.domain) > -1)
			{
				e.preventDefault();

				history.pushState({}, null, url);

				requestContent(url);

				return false;
			}
		});

		$(document).on('submit', '.searchform', function()
		{
			var url = script_theme_history.site_url + "?s=" + $(this).children('input[name=s]').val();

			requestContent(url);

			return false;
		});

		dom_overlay.on('click', function()
		{
			hideOverlay();
		});
	}
});