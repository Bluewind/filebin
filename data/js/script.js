(function($) {
	$(function() {
		if ($("img.lazyload").length) {
			$("img.lazyload").show().lazyload({treshold: 200});
		}
	});
})(jQuery);
