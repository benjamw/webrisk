(function($) {

	// plugin definition
	$.fn.showpass = function(options) {

		// build main options before element iteration
		var opts = $.extend({ }, $.fn.showpass.defaults, options);

		var selection = this.filter('input[type="password"]').map( function(val) {
			var $input = $(this);

			//insert link to show plain-text
			$('<a>')
				.text(opts.show_text)
				.addClass(opts.show_class)
				.attr({
					title: opts.show_title,
					href: '#'
				})
				.insertAfter($input);
		});

		// a list of valid input attributes for type="text" and "password"
		var attributes = [
			'id',
			'size',
			'maxlength',
			'disabled',
			'readonly',
			'accesskey',
			'dir',
			'lang',
			'style',
			'tabindex',
			'title',
			'name'
		];


		//add click handler for show-plain link(s)
		$('.'+opts.show_class).on('click', function( ) {
			//cache selector
			var $input = $(this).prev( );

			// create the input options map
			var input_opts = {
				type : 'text'
			};

			// go through the old input and grab all the attributes we are using
			for (var i in attributes) {
				if ( !! $input.attr(attributes[i]) && ! (('maxlength' == attributes[i]) && (-1 == $input.attr(attributes[i])))) {
					input_opts[attributes[i]] = $input.attr(attributes[i]);
				}
			}

			//create new text input
			var $new_input = $('<input>')
				.attr(input_opts)
				.val($input.val( ))
				.addClass($input.attr('class'));

			cloneCopyEvent($input, $new_input);

			$new_input.insertAfter($input.prev( ));

			$input.remove( );

			//change link text and attributes
			$(this)
				.text(opts.hide_text)
				.removeClass(opts.show_class)
				.addClass(opts.hide_class)
				.attr({
					title: opts.hide_title
				});

			//stop link being followed
			return false;
		});

		//add click handler for show-plain link(s)
		$('.'+opts.hide_class).on('click', function( ) {
			//cache selector
			var $input = $(this).prev();

			// create the input options map
			var input_opts = {
				type : 'password'
			};

			// go through the old input and grab all the attributes we are using
			for (var i in attributes) {
				if ( !! $input.attr(attributes[i]) && ! (('maxlength' == attributes[i]) && (-1 == $input.attr(attributes[i])))) {
					input_opts[attributes[i]] = $input.attr(attributes[i]);
				}
			}

			//create new password input
			var $new_input = $('<input>')
				.attr(input_opts)
				.val($input.val( ))
				.addClass($input.attr('class'));

			cloneCopyEvent($input, $new_input);

			$new_input.insertAfter($input.prev( ));

			$input.remove( );

			//change link text and attributes
			$(this)
				.text(opts.show_text)
				.removeClass(opts.hide_class)
				.addClass(opts.show_class)
				.attr({
					title: opts.show_title
				});

			//stop link being followed
			return false;
		});


		return opts.filter ? selection : selection.end( );
	};

	// publicly accessible defaults
	$.fn.showpass.defaults = {

		show_class : 'show-plain',
		hide_class : 'show-hidden',
		show_text : 'Show password',
		hide_text : 'Hide password',
		show_title : 'Show the password in plain text',
		hide_title : 'Obscure the text',
		filter : false

	};


	// this function was stolen as-is from the jQuery v1.4.1 source code
	function cloneCopyEvent(orig, ret) {
		var i = 0;

		ret.each(function() {
			if ( this.nodeName !== (orig[i] && orig[i].nodeName) ) {
				return;
			}

			var oldData = jQuery.data( orig[i++] ), curData = jQuery.data( this, oldData ), events = oldData && oldData.events;

			if ( events ) {
				delete curData.handle;
				curData.events = {};

				for ( var type in events ) {
					for ( var handler in events[ type ] ) {
						jQuery.event.add( this, type, events[ type ][ handler ], events[ type ][ handler ].data );
					}
				}
			}
		});
	}

})(jQuery);
