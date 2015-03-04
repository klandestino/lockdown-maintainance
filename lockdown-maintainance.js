jQuery(document).ready( function($) {
	
	function send_popup( title, text, delay ) {
		
		// Initialize parameters
		title = title !== '' ? '<span class="title">' + title + '</span>' : '';
		text = text !== '' ? text : '';
		popup_class = 'update';
		delay = typeof delay === 'number' ? delay : 20000;
		
		var object = $('<div/>', {
			class: 'popup_notification ' + popup_class,
			html: title + text + '<span class="close">&times;</span>'
		});
		
		$('#popup_container').prepend(object);
		
		$(object).hide().fadeIn(500);
		
		setTimeout(function() {
			
			$(object).slideUp(500);
			
		}, delay);
	
	}
	
	$('<div/>', { id: 'popup_container' } ).appendTo('body');
	$('body').on('click', '.close', function () { $(this).parent().slideUp(200); });
	
	
	// Bum bum
	$(document).on( 'heartbeat-tick.my_tick', function( e, data ) {
		
		// To understand better how it works just uncomment following lines and give a look at browser console
		//send_popup('tik tak');
		console.log(data);
		
		if ( !data['message'] )
			return;

		send_popup( data['message']['title'], data['message']['content'] );
		
	});
	
});