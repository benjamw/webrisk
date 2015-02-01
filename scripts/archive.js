
// archive javascript

$(document).ready( function( ) {
	// make the table row clicks work
	$('.datatable tbody tr').css('cursor', 'pointer').click( function( ) {
		var id = $(this).attr('id').substr(1);

		window.location = 'game.php?id='+id+debug_query_;
	});

	// blinky menu items
	$('.blink').fadeOut( ).fadeIn( ).fadeOut( ).fadeIn( ).fadeOut( ).fadeIn( );
});

