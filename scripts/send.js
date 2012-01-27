$(document).ready( function( ) {
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
		.css({ position: 'relative', left: '-21px' });
		
	$('#message').focus( );
	setCaretTo($('#message')[0], 0);
});


// http://parentnode.org/javascript/working-with-the-cursor-position/
function setCaretTo(obj, pos) {
    if(obj.createTextRange) {
        /* Create a TextRange, set the internal pointer to
           a specified position and show the cursor at this
           position
        */
        var range = obj.createTextRange();
        range.move("character", pos);
        range.select();
    } else if(obj.selectionStart) {
        /* Gecko is a little bit shorter on that. Simply
           focus the element and set the selection to a
           specified position
        */
        obj.focus();
        obj.setSelectionRange(pos, pos);
    }
}