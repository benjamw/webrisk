
var reload = true; // do not change this
var stage_1 = false;

$(document).ready( function( ) {

	// make the board clicks work
	switch (state) {
		case 'placing' :
			$('#board span').not('#players span').add('area').click( function( ) {
				var id = $(this).attr('id').substr(2);

				if (0 === $('#land_id option[value='+id+']').prop('selected', true).length) {
					alert('That is not your territory');
				}
				else if ( ! $('#num_armies').val( )) {
					$('#num_armies').val($('#armies').text( ));
				}
			});
			break;

		case 'attacking' :
			$('#use_attack_path').parent( ).css('display', $('#till_dead').prop('checked') ? 'block' : 'none');
			$('#attack_path').parent( ).css('display', $('#use_attack_path').prop('checked') ? 'block' : 'none');

			$('#till_dead').change( function( ) {
				$this = $(this);
				$('#use_attack_path').parent( ).css('display', $this.prop('checked') ? 'block' : 'none');

				if ( ! $this.prop('checked')) {
					$('#use_attack_path').prop('checked', false);
					$('#attack_path').val('').parent( ).css('display', 'none');
					$('div#pathmarkers div').text('').hide( )
				}
			});

			$('#use_attack_path').change( function( ) {
				$this = $(this);
				$('#attack_path').parent( ).css('display', $this.prop('checked') ? 'block' : 'none');

				if ($this.prop('checked')) {
					// fill the attack path with the selected defend id (if any)
					if ($('#defend_id').val( )) {
						$('#attack_path').val($('#defend_id').val( ));
						$('#pm'+$.trim($('#defend_id').val( ))).text(1).show( );
					}
				}
				else {
					// clear the attack path
					$('#attack_path').val('');
					$('div#pathmarkers div').text('').hide( )
				}
			});

			$('#board span').not('#players span').add('area').add('#pathmarkers div').click( function( ) {
				var id = $(this).attr('id').substr(2);

				if (0 == $('#attack_id option[value='+id+']').prop('selected', true).length) {
					$('#defend_id option[value='+id+']').prop('selected', true);

					if ($('#use_attack_path').prop('checked')) {
						var attack_path = $('#attack_path').val( );
						var regex = new RegExp('\\b'+id+'\\b', 'i');

						if (regex.test(attack_path)) {
							var del_regex = new RegExp('(?:,'+id+'\\b|\\b'+id+',?)', 'ig');
							attack_path = attack_path.replace(del_regex, '');
						}
						else {
							if ('' != attack_path) {
								attack_path += ',';
							}

							attack_path += id;
						}

						$('#attack_path').val(attack_path);

						// update the attack path markers
						update_path(attack_path);

						$('#attack_path').change( function( ) {
							var attack_path = $(this).val( );
							update_path(attack_path);
						});
					}
				}
				else {
					var armies = $('#board span[id=sl'+id+']').text( );
					armies = (armies > 3) ? 3 : (armies - 1);

					if (0 == armies) {
						alert('You cannot attack from this territory');
						$('#attack_id option').prop('selected', false);
					}

					$('#num_armies option[value='+armies+']').prop('selected', true);
				}
			}).css('cursor', 'pointer');
			break;

		case 'fortifying' :
			$('#board span').not('#players span').add('area').click( function( ) {
				var id = $(this).attr('id').substr(2);

				if ( ! stage_1) {
					if (0 === $('#from_id option[value='+id+']').prop('selected', true).length) {
						alert('That is not your territory');
					}
					else {
						stage_1 = true;
						var armies = $('#board span[id=sl'+id+']').text( ) - 1;

						if ( ! $('#num_armies').val( )) {
							$('#num_armies').val(armies);
						}

						if (0 === armies) {
							alert('You cannot fortify from this territory');
							$('#from_id option').prop('selected', false);
							$('#num_armies').val('')
							stage_1 = false
						}
					}
				}
				else { // stage_1
					// if we click the from territory again, switch back to stage 0 and unselect the from territory
					if ($('#from_id option:selected').val( ) == id) {
						stage_1 = false;
						$('#from_id option:selected').prop('selected', false);
						$('#from_id option[value=""]').prop('selected', true);
					}
					else if (0 === $('#to_id option[value='+id+']').prop('selected', true).length) {
						alert('That is not your territory');
					}
				}
			});
			break;

		case 'waiting' :
		case 'trading' :
		case 'occupying' :
		case 'resigned' :
		case 'dead' :
		default :
			// do nothing
			break;
	}

	// submit the form
	$(document).on('click', '#submit', function( ) {
		var go = true;
		var reenable = false;
		var clear_form = false;

		// check some things first
		switch (state) {
			case 'waiting' :
				// confirm resignation
				go = confirm('Are you sure you wish to resign the game?');
				break;

			case 'trading' :
				// make sure the bonus land is being traded
				var $cards = $('.card input:checked');

				if (3 != $cards.length) {
					alert('You must select exactly three (3) cards');
					return false;
				}

				if ($('#bonus_card').length) {
					var bonus = parseInt($('#bonus_card').val( ));
					var go_bonus = false;

					$.each($cards, function(i, elem) {
						if (parseInt(this.value) == bonus) {
							go_bonus = true;
						}
					});

					if ( ! go_bonus) {
						go = confirm('The bonus land you selected is not being traded.\nIf you continue, you will not recieve your bonus armies.\n\nDo you wish to continue?');
					}
				}
				break;

			case 'placing' :
				// do some validation
				if (('' == $('#num_armies').val( )) || (isNaN($('#num_armies').val( )))) {
					alert('You must enter the number of armies to place');
					return false;
				}

				if ('' == $('#land_id').val( )) {
					alert('You must select a territory to place your armies in');
					return false;
				}
				break;

			case 'attacking' :
				var msg_tail = "\n\nIf you do not wish to attack, click 'Skip'";
				// do some validation
				if ('' == $('#attack_id').val( )) {
					alert('You must select a territory to attack from'+msg_tail);
					return false;
				}

				if ('' == $('#defend_id').val( )) {
					alert('You must select a territory to attack'+msg_tail);
					return false;
				}

				// check the number of armies available
				var attack_id = $('#attack_id option:selected').val( );
				var avail_armies = $('#sl'+attack_id).text( );

				if (1 == avail_armies) {
					go = false;
					alert('You do not have enough armies to attack'+msg_tail);
					$('#attack_id option').prop('selected', false);
				}
				break;

			case 'fortifying' :
				var msg_tail = "\n\nIf you do not wish to fortify, click 'Skip'";
				// do some validation
				if (('' == $('#num_armies').val( )) || (isNaN($('#num_armies').val( )))) {
					alert('You must enter the number of armies to move'+msg_tail);
					return false;
				}

				if ('' == $('#from_id').val( )) {
					alert('You must select a territory to fortify from'+msg_tail);
					return false;
				}

				if ('' == $('#to_id').val( )) {
					alert('You must select a territory to fortify to'+msg_tail);
					return false;
				}
				break;
		}

		// if all checks are good...
		if (go) {
			if ('attacking' == state) {
				// show the waiting gif
				$('#board #dice').empty( ).append('<img src="images/wait.gif" alt="..." />');
			}

			// disable the form buttons
			$('#skip, #submit').attr('disabled', true);

			if (debug) {
				window.location = 'ajax_helper.php'+debug_query+'&'+$('#game_form').serialize( );
				return false;
			}

			$.ajax({
				type: 'POST',
				url: 'ajax_helper.php',
				data: $('#game_form').serialize( ),
				success: function(msg) {
					// if something happened, just reload
					if ('{' != msg[0]) {
						if (reload) { window.location.reload( ); } else { alert('Reload 1'); }
						return;
					}

					var reply = JSON.parse(msg);

					if (reply.error) {
						alert(reply.error);
						if (reload) { window.location.reload( ); } else { alert('Reload 2'); }
						return;
					}

					if ('RELOAD' == reply.action) {
						if (reload) { window.location.reload( ); } else { alert('Reload 3'); }
						return;
					}

					// update the token
					$('#token').val(reply.token);

					switch (state) {
						case 'placing' :
							reenable = true;

							if (0 == reply.armies) {
								reenable = false;
								if (reload) { window.location.reload( ); } else { alert('Reload 4'); }
								return;
							}

							reply.land_id = reply.land_id + '';
							if (1 == reply.land_id.length) {
								reply.land_id = '0'+reply.land_id;
							}

							// update the number of armies available
							$('#sl'+reply.land_id).text(reply.num_on_land);
							$('#armies').text(reply.armies);
							break;

						case 'attacking' :
							// update the attack armies
							reply.attack_id = reply.attack_id + '';
							if (1 == reply.attack_id.length) {
								reply.attack_id = '0'+reply.attack_id;
							}

							var attack_class = $('#sl'+reply.attack_id).text(reply.num_on_attack).attr('class');

							// update the defend armies
							if (undefined != reply.defend_id) {
								reply.defend_id = reply.defend_id + '';
								if (1 == reply.defend_id.length) {
									reply.defend_id = '0'+reply.defend_id;
								}

								var defend_class = $('#sl'+reply.defend_id).text(reply.num_on_defend).attr('class');
							}

							// show the dice
							reenable = true;
							var $dice = $('#dice').empty( );
							if (undefined != reply.dice) {
								$dice.append(
									'<div class="attack"></div><div class="defend"></div>'
								);

								$.each(reply.dice.attack, function(i, n) {
									$dice.find('.attack').append(
										'<div class="'+attack_class+' dc'+n+'">'+n+'</div>'
									);
								});

								$.each(reply.dice.defend, function(i, n) {
									$dice.find('.defend').append(
										'<div class="'+defend_class+' dc'+n+'">'+n+'</div>'
									);
								});
							}

							// switch to occupy if we need to
							if ('occupying' == reply.state) {
								state = 'occupying';
								$('#action').replaceWith(reply.action);
							}
							break;

						case 'fortifying' :
							// update the from armies
							reply.from_id = reply.from_id + '';
							if (1 == reply.from_id.length) {
								reply.from_id = '0'+reply.from_id;
							}

							$('#sl'+reply.from_id).text(reply.num_on_from);

							// update the to armies
							reply.to_id = reply.to_id + '';
							if (1 == reply.to_id.length) {
								reply.to_id = '0'+reply.to_id;
							}

							$('#sl'+reply.to_id).text(reply.num_on_to);

							stage_1 = false;
							reenable = true;
							clear_form = true;
							break;

						default :
							if (reload) { window.location.reload( ); } else { alert('Reload 5'); }
							return;
							break;
					}

					if (reenable) {
						// re-enable the form
						$('#skip, #submit').attr('disabled', false);
					}

					if (clear_form) {
						// clear the form
						$('#game_form *').not('[type=hidden], div, label, option, #submit, #skip').val('');
					}
				}
			});
		}

		return false;
	});

	// skip this action
	$('#skip').click( function( ) {
		var go = true;
		switch (state) {
			case 'trading' :
				var $cards = $('.card input:checked');

				if ($cards.length) {
					go = confirm('You have filled out the form,\nare you sure you wish to skip this action?');
				}
				break;

			case 'attacking' :
				// if the form is fully filled out (num_armies doesn't count, there is no empty value)
				if (('' != $('#attack_id').val( )) && ('' != $('#defend_id').val( ))) {
					go = confirm('You have filled out the form,\nare you sure you wish to skip this action?');
				}
				break;

			case 'fortifying' :
				// if the form is fully filled out
				if (('' != $('#num_armies').val( )) && ('' != $('#from_id').val( )) && ('' != $('#to_id').val( ))) {
					go = confirm('You have filled out the form,\nare you sure you wish to skip this action?');
				}
				break;

			case 'waiting' :
			case 'placing' :
			case 'occupying' :
			case 'resigned' :
			case 'dead' :
			default :
				go = false;
				// do nothing
				break;
		}

		if (go) {
			// disable the form buttons
			$('#skip, #submit').attr('disabled', true);

			if (debug) {
				window.location = 'ajax_helper.php'+debug_query+'&'+$('#game_form').serialize( )+'&skip='+state;
				return false;
			}

			$.ajax({
				type: 'POST',
				url: 'ajax_helper.php',
				data: $('#game_form').serialize( )+'&skip='+state,
				success: function(msg) {
					var reply = JSON.parse(msg);

					if (reply.error) {
						alert(reply.error);
					}

					if (reload) { window.location.reload( ); } else { alert('Reload 6'); }
					return;
				}
			});
		}

		return false;
	});

	// nudge button
	$('#nudge').click( function( ) {
		if (confirm('Are you sure you wish to nudge all inactive players?')) {
			if (debug) {
				window.location = 'ajax_helper.php'+debug_query+'&'+$('#game_form').serialize( )+'&nudge=1';
				return false;
			}

			$.ajax({
				type: 'POST',
				url: 'ajax_helper.php',
				data: $('#game_form').serialize( )+'&nudge=1',
				success: function(msg) {
					var reply = JSON.parse(msg);

					if (reply.error) {
						alert(reply.error);
					}
					else {
						alert('Nudge Sent');
					}

					if (reload) { window.location.reload( ); } else { alert('Reload 7'); }
					return;
				}
			});
		}
	});

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
					var entry = '<dt><span>'+reply.create_date+'</span> '+reply.username+'</dt>'+
						'<dd'+(('1' == reply.private) ? ' class="private"' : '')+'>'+reply.message+'</dd>';

					$('#chats').prepend(entry);
					$('#chatbox input#chat').val('');
				}
			}
		});

		return false;
	});

	// card click function
	$('#players li').css('cursor', 'pointer').click( function( ) {
		var id = $(this).attr('id').slice(2);

		if (debug) {
			window.location = 'ajax_helper.php'+debug_query+'&'+'cardcheck=1&id='+id;
			return false;
		}

		$.ajax({
			type: 'POST',
			url: 'ajax_helper.php',
			data: 'cardcheck=1&id='+id,
			success: function(msg) {
				alert(msg);
			}
		});

		return false;
	});

	// army select script
	$('#num_armies_options').change( function(event) {
		var $this = $(this);

		if ('--' != $this.val( )) {
			$('#num_armies').val( $this.val( ) );
			$this.val('--');
		}
	});

	// tha fancybox stuff
	$("a.fancybox").fancybox({
		autoSize : false,
		width : '80%',
		height : '80%',
		beforeLoad : function( ) {
			if ( ! this.element.parent( ).is('#history')) {
				$('#game_info').show( );
			}
		},
		afterClose : function( ) {
			$('#game_info').hide( );
		}
	});

});


function update_path(attack_path) {
	// hide all the path markers
	$('div#pathmarkers div').text('').hide( );

	// show the ones we need
	var attack_arr = attack_path.split(',');
	for (var i in attack_arr) {
		$('#pm'+$.trim(attack_arr[i])).text(parseInt(i) + 1).show( );
	}
}

