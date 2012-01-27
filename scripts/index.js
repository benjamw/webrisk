
// index javascript

var timer = false;
var timeout = 30001; // 1 minute

$(document).ready( function( ) {
	// make the table row clicks work
	$('.datatable tbody tr').css('cursor', 'pointer').click( function( ) {
		var id = $(this).attr('id').substr(1);
		var state = $($(this).children( )[2]).text( );

		//* -- SWITCH --
		if (state == 'Waiting') {
			window.location = 'join.php?id='+id+debug_query_;
		}
		else {
			window.location = 'game.php?id='+id+debug_query_;
		}
		//*/
	});

	// blinky menu items
	$('.blink').fadeOut( ).fadeIn( ).fadeOut( ).fadeIn( ).fadeOut( ).fadeIn( );
//	var cur_background = $('.blink').css('backgroundColor');
//	var high_color = $('.active a').css('backgroundColor');
//	$('.blink')
//		.animate({ backgroundColor: high_color }, 400).animate({ backgroundColor: cur_background }, 400)
//		.animate({ backgroundColor: high_color }, 400).animate({ backgroundColor: cur_background }, 400)
//		.animate({ backgroundColor: high_color }, 400).animate({ backgroundColor: cur_background }, 400);

	// chat box functions
	$('#chatbox form').submit( function( ) {
		if ('' == $.trim($('#chatbox input#chat').val( ))) {
			return false;
		}

		if (debug) {
			window.location = 'ajax_helper.php'+debug_query+'&'+$('#chatbox form').serialize( );
			return false;
		}

		$.ajax({
			type: 'POST',
			url: 'ajax_helper.php',
			data: $('#chatbox form').serialize( ),
			success: function(msg) {
				var reply = JSON.parse(msg);

				if (reply.error) {
					alert(reply.error);
				}
				else {
					var entry = '<dt>'+reply.username+'</dt>'+
						'<dd>'+reply.message+'</dd>';
					$('#chats').prepend(entry);
					$('#chatbox input#chat').val('');
				}
			}
		});

		return false;
	});

	// run the sounds
	if (("#refresh" == document.location.hash) && turn_msg_count) {
		$("#sounds").jPlayer({
			ready: function ( ) {
				$(this).setFile('sounds/message.mp3', 'sounds/message.ogg').play( );
			},
			volume: 100,
			oggSupport: false,
			swfPath: "scripts"
		});
	}

	// run the ajax refresher
	ajax_refresh( );

	// set some things that will halt the timer
	$('#chatbox form input').focus( function( ) {
		clearTimeout(timer);
	});

	$('#chatbox form input').blur( function( ) {
		if ('' != $(this).val( )) {
			timer = setTimeout('ajax_refresh( )', timeout);
		}
	});

});


function ajax_refresh( ) {
	// no debug redirect, just do it
	$.ajax({
		type: 'POST',
		url: 'ajax_helper.php',
		data: 'timer=1',
		success: function(msg) {
			if (('' != msg) && (msg != turn_msg_count)) {
				// we don't want to play sounds when they hit the page manually
				// so set a hash on the URL that we can test when we embed the sounds
				// we don't care what the hash is, just refresh is there is a hash
				// (the user may have silenced the sounds with #silent)
				if ('' != window.location.hash) {
					window.location.reload( );
				}
				else {
					// stick the hash on the end of the URL
					window.location = window.location.href+'#refresh'
					window.location.reload( );
				}
			}
		}
	});

	// successively increase the timeout time in case someone
	// leaves their window open, don't poll the server every
	// 30 seconds for the rest of time
	if (0 == (timeout % 5)) {
		timeout += Math.floor(timeout * 0.001) * 1000;
	}

	++timeout;

	timer = setTimeout('ajax_refresh( )', timeout);
}