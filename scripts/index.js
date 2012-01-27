
// index javascript

var reload = true; // do not change this
var refresh_timer = false;
var refresh_timeout = 30001; // 30 seconds

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
	if (('#refresh' == document.location.hash) && turn_msg_count) {
		$('#sounds').jPlayer({
			ready: function ( ) {
				$(this).jPlayer('setMedia', {
					mp3: 'sounds/message.mp3',
					oga: 'sounds/message.ogg'
				}).jPlayer('play');
			},
			volume: 1,
			swfPath: 'scripts'
		});
	}

	// run the ajax refresher
	ajax_refresh( );

	// set some things that will halt the timer
	$('#chatbox form input').focus( function( ) {
		clearTimeout(refresh_timer);
	});

	$('#chatbox form input').blur( function( ) {
		if ('' != $(this).val( )) {
			refresh_timer = setTimeout('ajax_refresh( )', refresh_timeout);
		}
	});

});


var jqXHR = false;
function ajax_refresh( ) {
	// no debug redirect, just do it

	// only run this if the previous ajax call has completed
	if (false == jqXHR) {
		jqXHR = $.ajax({
			type: 'POST',
			url: 'ajax_helper.php',
			data: 'timer=1',
			success: function(msg) {
				if (('' != msg) && (msg != turn_msg_count)) {
					// we don't want to play sounds when they hit the page manually
					// so set a hash on the URL that we can test when we embed the sounds
					// we don't care what the hash is, just refresh if there is a hash
					// (the user may have silenced the sounds with #silent)
					if ('' != window.location.hash) {
						if (reload) { window.location.reload( ); }
					}
					else {
						// stick the hash on the end of the URL
						window.location = window.location.href+'#refresh'
						if (reload) { window.location.reload( ); }
					}
				}
			}
		}).always( function( ) {
			jqXHR = false;
		});
	}

	// successively increase the timeout time in case someone
	// leaves their window open, don't poll the server every
	// 30 seconds for the rest of time
	if (0 == (refresh_timeout % 5)) {
		refresh_timeout += Math.floor(refresh_timeout * 0.001) * 1000;
	}

	++refresh_timeout;

	refresh_timer = setTimeout('ajax_refresh( )', refresh_timeout);
}

