// Array Remove - By John Resig (MIT Licensed)
Array.prototype.remove = function(from, to) {
	var rest = this.slice((to || from) + 1 || this.length);
	this.length = from < 0 ? this.length + from : from;

	return this.push.apply(this, rest);
};

jQuery(document).ready(function($){
	$context = $('#cdn_integration_dashboard_flush');
	$('form', $context).submit(function(e) {
		e.preventDefault();
		var urls = $('#urls-to-flush', $context).val().split("\n");
		for(i=0; i<urls.length; i++) {
			var url = urls[i];
			if( !url ) {
				urls.remove(i);
				break;
			}
		}
		var data = {
			action: 'cdn_integration_dashboard_flush',
			urls: urls.join(',')
		};

		$('p input', $context).hide().siblings('img').show();
		$.post(ajaxurl, data, function(response) {
			$('p img', $context).hide().siblings('input').show();
		});
	});
});
