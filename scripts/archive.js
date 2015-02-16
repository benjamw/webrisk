
// archive javascript

$(document).ready( function( ) {
	// make the table row clicks work
	$('.datatable tbody tr').css('cursor', 'pointer').click( function( ) {
		var file = $(this).attr('id');
		window.location = 'review.php?file='+file+'&step=0'+debug_query_;
	});

	// blinky menu items
	$('.blink').fadeOut( ).fadeIn( ).fadeOut( ).fadeIn( ).fadeOut( ).fadeIn( );
});

