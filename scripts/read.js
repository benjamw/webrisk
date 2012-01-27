$(document).ready( function( ) {
	$('#reply').click( function( ) {
		window.location = 'send.php?id='+$('#message_id').val( );
	});

	$('#delete').click( function( ) {
		if (confirm('Do you wish to delete this message?')) {
			$('#type').val('delete');
			$('form').submit( );
		}
	});
});