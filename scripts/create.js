
// create javascript

var custom_trades_index = 1;

function test_fortify_radio( ) {
	var $input = $('input[name=fortify]:checked');
	$input.parent( ).parent( ).find('input[type=checkbox]').prop('disabled', ('no' == $input.val( )));
}

function check_fieldset_box( ) {
	$('input.fieldset_box').each( function(i, elem) {
		var $this = $(this);
		var id = $this.attr('id').slice(0,-4);

		if ($this.prop('checked')) {
			$('div#'+id).show( );
		}
		else {
			$('div#'+id).hide( );
		}
	});
}

// do the conquer limit table stuff
function build_conquer_table( ) {
	var output = '';
	var header = 'When';
	var equation = '';

	// grab the vars
	var type = $('#conquer_type option:selected').val( );
	var conquests_per = parseInt($('#conquer_conquests_per').val( ));
	var per_number = parseInt($('#conquer_per_number').val( ));
	var skip = parseInt($('#conquer_skip').val( ));
	var start_at = parseInt($('#conquer_start_at').val( ));
	var minimum = parseInt($('#conquer_minimum').val( ));
	var maximum = parseInt($('#conquer_maximum').val( ));
	var start_count = 1;

	conquests_per = isNaN(conquests_per) ? 1 : conquests_per;
	skip = isNaN(skip) ? 0 : skip;
	start_at = isNaN(start_at) ? 0 : start_at;
	minimum = isNaN(minimum) ? 1 : ((1 > minimum) ? 1 : minimum);
	maximum = isNaN(maximum) ? 42 : ((1 > maximum) ? 1 : maximum);

	if (isNaN(per_number)) {
		switch (type.toLowerCase( )) {
			case 'trade_value' : per_number = 10; break;
			case 'trade_count' : per_number =  2; break;
			case 'rounds'      : per_number =  1; break;
			case 'turns'       : per_number =  1; break;
			case 'land'        : per_number =  3; break;
			case 'continents'  : per_number =  1; break;
			case 'armies'      : per_number = 10; break;
			default            : per_number =  1; break;
		}
	}

	if ('None' == type) {
		output = '<tr><td>Always</td><td>Infinite</td></tr>';
	}
	else {
		// load the calculated values back into the form inputs
		$('#conquer_conquests_per').val(conquests_per);
		$('#conquer_per_number').val(per_number);
		$('#conquer_skip').val(skip);
		$('#conquer_start_at').val(start_at);
		$('#conquer_minimum').val(minimum);
		$('#conquer_maximum').val(maximum);

		// output to the table
		header = '# of '+((type.replace(/_/, ' ').replace(/^(.)|\s(.)/g, function($1) { return $1.toUpperCase( ); })+'s').replace(/ss$/, 's'));

		var zeros = ['trade_value', 'trade_count', 'Continents'];
		for (var key in zeros) {
			if (type == zeros[key]) {
				start_count = 0;
				break;
			}
		}

		// run the calculation
		var limit = 0;
		var repeats = 0;
		var classes;
		var class_text;
		for (var i = start_count; i <= 200; ++i) {
			classes = [];
			equation = 'max( ( ( ( floor( (x - '+start_count+') / <span class="per_number">'+per_number+'</span> ) + 1 ) - <span class="skip">'+skip+'</span> ) * <span class="conquests_per">'+conquests_per+'</span> ) , 0 ) + <span class="start_at">'+start_at+'</span>';

			limit = Math.max((((parseInt(Math.floor((i - start_count) / per_number)) + 1) - skip) * conquests_per), 0) + start_at;

			if (limit < minimum) { classes.push('min'); }

			limit = (limit < minimum) ? minimum : limit;
			limit = (limit > maximum) ? maximum : limit;

			if (0 == (i % 2)) { classes.push('alt'); }
			if ((i - start_count) < (skip * per_number)) { classes.push('skip'); }
			if (0 == ((i - start_count) % per_number)) { classes.push('group'); }

			if (limit == maximum) { classes.push('max'); }

			class_text = (classes.length) ? ' class="'+classes.join(' ')+'"' : '';

			if (limit === maximum) {
				repeats += 1;
			}

			if (3 <= repeats) {
				output += '<tr'+class_text+'><td>'+i+'</td><td>...</td></tr>';
				break;
			}

			output += '<tr'+class_text+'><td>'+i+'</td><td>'+limit+'</td></tr>';
		}
	}

	// add the things to the table
	$('#conquer_type_header').empty( ).append(header);
	$('#conquer_limit_table tbody').empty( ).append(output);
	$('#conquer_limit_table caption').empty( ).append(equation);
}

// do the custom trades table stuff
function build_custom_trades_table( ) {
	if (debug) {
		window.location = 'ajax_helper.php'+debug_query+'&'+$('#custom_trades input:visible').serialize( );
		return false;
	}

	// send the data
	$.ajax({
		type: 'POST',
		url: 'ajax_helper.php',
		data: $('#custom_trades input:visible').serialize( ),
		success: function(msg) {
			// add the info to the table
			if ('' != msg) {
				$('.custom_trades tbody').empty( ).append(msg);
			}
		}
	});
}


// now for the doc ready stuff
$(document).ready( function( ) {
	// do the fancybox things
	$("#show_conquer_limit_table").fancybox({
		beforeLoad : function( ) {
			build_conquer_table( );
			$('#conquer_limit_table').show( );
		},
		afterClose : function( ) {
			$('#conquer_limit_table').hide( );
		}
	});

	$("#show_custom_trades_table").fancybox({
		beforeLoad : function( ) {
			build_custom_trades_table( );
			$('#custom_trades_table').show( );
		},
		afterClose : function( ) {
			$('#custom_trades_table').hide( );
		}
	});

	// set the add button on custom trades
	$('img#add_trade_array').click( function( ) {
		// grab the clone row
		var $table = $(this).parent( ).find('table.form tbody');
		var clone = $table.find('tr.clone').html( );

		// replace all NNN with the index
		++custom_trades_index;
		clone = clone.replace(/NNN/g, custom_trades_index+'');

		// insert the new row into the table
		$table.append($('<tr/>').append(clone));
	}).css('cursor', 'pointer');

	// disable the fortify checkboxes
	$('input[name=fortify]').change( function( ) {
		test_fortify_radio( );
	});

	// hide the collapsable fieldsets
	$('input.fieldset_box').change( function( ) {
		check_fieldset_box( );
	});

	// hide the fancybox tables
	$('#conquer_limit_table').hide( );
	$('#custom_trades_table').hide( );

	test_fortify_radio( );
	check_fieldset_box( );
});

