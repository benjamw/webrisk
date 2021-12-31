
$(document).ready( function( ) {
	$('#player_all').click( function( ) {
		$('.player_box').prop('checked', $(this).prop('checked'));
	});

	$('#game_all').click( function( ) {
		$('.game_box').prop('checked', $(this).prop('checked'));
	});

	$('tbody tr').click( function(event) {
		if ($(event.target).is('input')) {
			return;
		}

		$input = $(this).find('input');

		if ($input.length) {
			$input.prop('checked', ! $input.prop('checked'));
		}
	});

	$('#player_action, #game_action').change( function( ) {
		var val = $(this).find('option:selected').val( );

		if (('delete' == val) && ! confirm('Are you sure?')) {
			$(this)
				.find('option[value=""]')
				.prop('selected', true);
			return false;
		}

		$(this).parents('form').submit( )
	});
});
