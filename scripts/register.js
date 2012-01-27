
// registration/profile javascript

// globals
var inblur = false;

if (undefined == profile) {
	profile = 0;
}

$(document).ready( function( ) {
	$('#first_name').focus( );

	// simple form validation
	$('form').submit( function( ) {
		var errors = [];

		if ('' == $.trim($('#username').val( )) && ('Update Profile' != $('#submit').val( ))) {
			errors[errors.length] = 'Username is required and cannot be empty';
		}

		if ('' == $('#email').val( )) {
			errors[errors.length] = 'Email is required';
		}

		if ('' != $.trim($('#errors').val( ))) {
			errors[errors.length] = 'The username or email address you have entered is already in use';
		}

		if ( ! profile && ('' == $('#password').val( ))) {
			errors[errors.length] = 'Password is required';
		}

		if (profile && ('' != $('#password').val( )) && ('' == $('#curpass').val( ))) {
			errors[errors.length] = 'Current password is required when changing password';
		}

		if ($('#password').val( ) != $('#passworda').val( )) {
			errors[errors.length] = 'The two passwords entered do not match';
		}

		if (0 != errors.length) {
			var string = 'There were problems with your submission:\n\n';

			for (var i = 0; i < errors.length; ++i) {
				string += ' - ' + errors[i] + '\n';
			}

			alert(string);

			return false;
		}

		return true;
	});

	// run an ajax test and if username or email is used, show an error
	$('#username').add('#email').blur( function( ) {
		if (inblur) {
			return;
		}

		var $this = $(this);
		var type = $this.attr('id');
		var value = '';

		if ('' == $this.val( )) {
			return;
		}

		inblur = true;

		// don't redirect on debug here

		$.ajax({
			type: 'POST',
			url: 'ajax_helper.php',
			data: 'keep_token=1&validity_test='+type+'&value='+$this.val( )+'&token='+$('#token').val( )+'&profile='+profile,
			success: function(msg) {
				if ('OK' == msg) {
					// display a check mark next to the input box
					$('#'+type+'_check').empty( ).append(' <img src="images/tick.png" alt="OK" />');

					// remove any previous errors from the errors field
					var regx = new RegExp(type, 'gi');
					$('#errors').val($('#errors').val( ).replace(regx, ''));
				}
				else {
					// display an error message and a red X
					if (303 == msg) {
						$('#'+type+'_check').empty( ).append(' <img src="images/cross.png" alt="INVALID" /> Invalid email format');
					}
					else {
						$('#'+type+'_check').empty( ).append(' <img src="images/cross.png" alt="TAKEN" /> This '+type+' is taken');
					}

					// add the error to the errors field
					var error = $('#errors').val( ) + type;
					$('#errors').val(error);
				}
			}
		});

		inblur = false;
	});
});
