
$(document).ready( function( ) {

	// MESSAGES

	$('.datatable tbody td').not('.edit').click( function( ) {
		var id = $(this).parent( ).attr('id').substr(3);
		window.location = 'read.php?id='+id+debug_query_;
	}).css({cursor:'pointer'});

	$('#in_all').click( function( ) {
		$('.in_box').prop('checked', (true == $(this).prop('checked')) ? 'checked' : '');
	});

	$('#out_all').click( function( ) {
		$('.out_box').prop('checked', (true == $(this).prop('checked')) ? 'checked' : '');
	});

	$('#send').click( function( ) {
		window.location = 'send.php'+debug_query;
	});

	$('#in_action, #out_action').change( function( ) {
		var val = $(this).find('option:selected').val( );

		if (('delete' == val) && ! confirm('Do you wish to delete these messages?')) {
			$(this)
				.find('option[value=""]')
				.attr('selected', 'selected')
			return false;
		}

		$(this).parents('form').submit( )
	});


	// READ

	$('#reply').click( function( ) {
		window.location = 'send.php?id='+$('#message_id').val( )+debug_query_;
	});

	$('#forward').click( function( ) {
		window.location = 'send.php?id='+$('#message_id').val( )+'&type=fw'+debug_query_;
	});

	$('#resend').click( function( ) {
		window.location = 'send.php?id='+$('#message_id').val( )+'&type=rs'+debug_query_;
	});

	$('#delete').click( function( ) {
		if (confirm('Do you wish to delete this message?')) {
			$('#type').val('delete');
			$('form').submit( );
		}
	});


	// SEND

	if ($.datepicker) {
		$.datepicker.setDefaults({
			showOn: 'both',
			buttonImageOnly: true,
			buttonImage: 'images/calendar.png',
			buttonText: 'Calendar',
			changeFirstDay: true,
			minDate: 0,
			showOtherMonths: true,
			speed: 'fast'
		});

		$('#send_date').datepicker( );
		$('#expire_date').datepicker( );

		$('#send_date + img').add('#expire_date + img')
			.css({ position: 'relative', left: '-21px', top: '5px' });

		$('#message').focus( );
		setCaretTo($('#message')[0], 0);
	}

});

// http://parentnode.org/javascript/working-with-the-cursor-position/
function setCaretTo(obj, pos) {
	if(obj.createTextRange) {
		/* Create a TextRange, set the internal pointer to
		   a specified position and show the cursor at this
		   position
		*/
		var range = obj.createTextRange();
		range.move('character', pos);
		range.select();
	} else if (obj.selectionStart) {
		/* Gecko is a little bit shorter on that. Simply
		   focus the element and set the selection to a
		   specified position
		*/
		obj.focus();
		obj.setSelectionRange(pos, pos);
	}
}
