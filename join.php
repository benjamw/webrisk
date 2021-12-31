<?php

require_once 'includes/inc.global.php';

try {
	$Game = new Game((int) $_GET['id']);

	if ('Waiting' != $Game->state) {
		if ( ! defined('DEBUG') || ! DEBUG) {
			header('Location: game.php?id='.(int) $_GET['id'].$GLOBALS['_&_DEBUG_QUERY']);
		}
		else {
			call('GAME IS PLAYING, REDIRECTED TO GAME AND QUIT');
		}

		exit;
	}
}
catch (MyException $e) {
	if ( ! defined('DEBUG') || ! DEBUG) {
		Flash::store('Error Accessing Game !');
	}
	else {
		call('ERROR ACCESSING GAME');
	}
}

if (isset($_POST['join'])) {
	// make sure this user is not full
	if ($GLOBALS['Player']->max_games && ($GLOBALS['Player']->max_games <= $GLOBALS['Player']->current_games)) {
		Flash::store('You have reached your maximum allowed games !');
	}

	test_token( );

	try {
		$Game->join( );
		Flash::store('Game Joined Successfully');
	}
	catch (MyException $e) {
		if (214 == $e->getCode( )) {
			Flash::store('That color is already in use, try again', true);
		}
		else {
			Flash::store('Game Join FAILED !');
		}
	}
}

if (isset($_POST['invite'])) {
	test_token( );

	try {
		$player_ids = $Game->invite( );

		// send the messages
		$message = 'You have been invited to join the game "'.htmlentities($Game->name, ENT_QUOTES, 'UTF-8', false).'".'."\n\n".'If you wish to play in this game, please join it from the home page.';
		$message .= "\n\n==== Message ==============================\n\n".htmlentities($_POST['extra_text'], ENT_QUOTES, 'UTF-8', false);
		$Message->send_message('Invitation to "'.htmlentities($Game->name, ENT_QUOTES, 'UTF-8', false).'"', $message, $player_ids, false, ldate('m/d/Y', strtotime('1 week')));

		Flash::store('Game Invitations Sent Successfully');
	}
	catch (MyException $e) {
		Flash::store('Game Invite FAILED !');
	}
}

if (isset($_POST['start'])) {
	test_token( );

	try {
		$Game->start((int) $_POST['player_id']);
		Flash::store('Game Started Successfully', 'game.php?id='.$_POST['game_id']);
	}
	catch (MyException $e) {
		Flash::store('Game Start FAILED !', true);
	}
}

// test if we are already in this game or not
$joined = $Game->is_player($_SESSION['player_id']);

$color_selection = '';
foreach ($Game->get_avail_colors( ) as $color) {
	$color_selection .= '<option class="'.strtolower(substr($color, 0, 3)).'">'.ucfirst($color).'</option>';
}

$password_box = '';
if ('' != $Game->passhash) {
	$password_box = '<li><label for="password">Password</label><input type="password" id="password" name="password" /></li>';
}

$meta['title'] = 'Join Game';
$meta['head_data'] = '
	<script>//<![CDATA[
		$(document).ready( function( ) {
			$("#show_conquer_limit_table").fancybox({
				title: null,
				beforeLoad: function( ) {
					$("#conquer_limit_table").show( );
				},
				afterClose: function( ) {
					$("#conquer_limit_table").hide( );
				}
			});

			$("#show_custom_trades_table").fancybox({
				title: null,
				beforeLoad: function( ) {
					$("#custom_trades_table").show( );
				},
				afterClose: function( ) {
					$("#custom_trades_table").hide( );
				}
			});

			// hide the fancybox tables
			$("#conquer_limit_table").hide( );
			$("#custom_trades_table").hide( );
		});
	/*]]>*/</script>
';
$hints = [
	'Join a game by filling out your desired game options.' ,
	'WARNING!<br />Games will be deleted after '.Settings::read('expire_games').' days of inactivity.' ,
	'If the password field is displayed, this game is password protected, and requires a password to join.' ,
];

$fog_of_war = $Game->get_fog_of_war( );

$extra_info = $Game->get_extra_info( );
if ('none' != $extra_info['conquer_type']) {
	// pull our variables out to use them here
	foreach ($extra_info as $key => $value) {
		if ('conquer_' == substr($key, 0, 8)) {
			$key = substr($key, 8);
			${$key} = $value;
		}
	}

	// the number of multipliers to skip before incrementing
	// e.g.- if it's 1 conquest per 10 trades, and skip is 1
	// the conquest value won't increase until trade value reaches 20
	// which is 10 trades past when it would increase at 10
	if (empty($skip) || ! (int) $skip) {
		$skip = 0;
	}

	// the number of conquests to start from
	if (empty($start_at) || ! (int) $start_at) {
		$start_at = 0;
	}

	// the number of conquests to allow per multiplier
	// e.g.- if it's conquests per 10 trades, and conquests_per
	// is 1, you will gain 1 conquest for every 10 trade value
	// so for 35 trade value, you will get 3 (3 * 1) conquests
	if (empty($conquests_per) || ! (int) $conquests_per) {
		$conquests_per = 1;
	}

	// the number of items to bypass before increasing the multiplier
	// e.g.- if it is 2 conquests based on armies, and per_number
	// is set to 5, you will get 2 conquests for every 5 armies
	// so when you have 5-9 armies, you will get 2 conquests
	// and from 10-14 armies, 4 conquests
	if (empty($per_number) || ! (int) $per_number) {
		switch ($type) {
			case 'trade_value' : $per_number = 10; break;
			case 'trade_count' : $per_number =  2; break;
			case 'rounds'      : $per_number =  1; break;
			case 'turns'       : $per_number =  1; break;
			case 'land'        : $per_number =  3; break;
			case 'continents'  : $per_number =  1; break;
			case 'armies'      : $per_number = 10; break;
			default            : $per_number =  1; break;
		}
	}

	// set the default minimum to 1
	if (empty($minimum) || (1 > $minimum) || ! (int) $minimum) {
		$minimum = 1;
	}

	// set the default start to 0
	if (empty($start_at) || (0 > $start_at) || ! (int) $start_at) {
		$start_at = 0;
	}

	// set the default maximum to 42 (the number of territories)
	if (empty($maximum) || ! (int) $maximum) {
		$maximum = 42;
	}

	// if we are calculating based on trade_value, trade_count, or continents
	// the 1 point buffer needs to be added
	$start_count = 1;
	if (in_array($type, ['trade_value', 'trade_count', 'continents'])) {
		$start_count = 0;
	}

	$conquests = [];
	$repeats = 0;
	for ($n = 0; $n <= 200; ++$n) {
		$limit = max((((((int) floor(($n - $start_count) / $per_number)) + 1) - $skip) * $conquests_per), 0) + $start_at;
		$limit = ($limit < $minimum) ? $minimum : $limit;
		$limit = ($limit > $maximum) ? $maximum : $limit;

		if ($limit === $maximum) {
			++$repeats;
		}

		$conquests[$n] = $limit;

		if (3 <= $repeats) {
			$conquests[$n + 1] = '...';
			break;
		}
	}

	// don't show 0 count for certain types
	if ( ! in_array($type, ['trade_value', 'trade_count', 'continents'])) {
		unset($conquests[0]);
	}

	$equation = "max( ( ( ( floor( (x - {$start_count}) / <span class=\"per_number\">{$per_number}</span> ) + 1 ) - <span class=\"skip\">{$skip}</span> ) * <span class=\"conquests_per\">{$conquests_per}</span> ) , 0 ) + <span class=\"start_at\">{$start_at}</span>";
	$plural_type = plural(2, $type);
	$conquer_type = ucwords(human(('s' === substr($type, -1)) ? $type : $plural_type));
	$conquer_table = '';
	foreach ($conquests as $n => $value) {
		$conquer_table .= '
			<tr'.((0 === ($n % 2)) ? ' class="alt"' : '').'>
				<td>'.$n.'</td>
				<td>'.$value.'</td>
			</tr>';
	}
}
else {
	$equation = '';
	$conquer_type = 'When';
	$conquer_table = '
				<tr>
					<td>Always</td>
					<td>Infinite</td>
				</tr>';
}

$trades = $Game->get_trade_array( );
$trade_array_table = 'None';
if ($trades) {
	$trade_array_table = '<table>';
	$trade_array_table .= '<thead><tr><th>Start</th><th>End</th><th>Step</th><th>Times</th></tr></thead>';
	$trade_array_table .= '<tbody>';

	$n = 0;
	foreach ($trades as $trade) {
		if ( ! isset($trade[3])) {
			$trade[3] = 0;
		}

		++$n;
		$trade_array_table .= '<tr'.((0 === ($n % 2)) ? ' class="alt"' : '').'><td>'.$trade[0].'</td><td>'.$trade[1].'</td><td>'.$trade[2].'</td><td>'.$trade[3].'</td></tr>';
	}

	$trade_array_table .= '</tbody></table>';
}

$trade_value_table = trade_value_table(Game::calculate_trade_values($trades));

$contents = '

	<h2>Game #'.$_GET['id'].': '.htmlentities($Game->name, ENT_QUOTES, 'UTF-8', false).'</h2>
	<table class="game_info">
		<tbody>
			<tr>
				<th>Game Type</th>
				<td>'.$Game->get_type( ).'</td>
			</tr><tr>
				<th>Max Players</th>
				<td>'.$Game->capacity.'</td>
			</tr><tr>
				<th>Placement Type</th>
				<td>'.$Game->get_placement( ).'</td>
			</tr><tr>
				<th>Placement Limit</th>
				<td>'.$Game->get_placement_limit( ).'</td>
			</tr><tr>
				<th>Fortification Type</th>
				<td>'.$Game->get_fortify( ).'</td>
			</tr><tr>
				<th>Kamikaze</th>
				<td>'.$Game->get_kamikaze( ).'</td>
			</tr><tr>
				<th>Warmonger</th>
				<td>'.$Game->get_warmonger( ).'</td>
			</tr><tr>
				<th>Nuke</th>
				<td>'.$Game->get_nuke( ).'</td>
			</tr><tr>
			<th>Turncoat</th>
				<td>'.$Game->get_turncoat( ).'</td>
			</tr><tr>
				<th>Fog of War</th>
				<td>Armies: '.$fog_of_war['armies'].'<br />Colors: '.$fog_of_war['colors'].'</td>
			</tr><tr>
				<th><a id="show_custom_trades_table" href="#custom_trades_table" title="show table">Custom Trade Array</a></th>
				<td>'.$trade_array_table.'</td>
			</tr><tr>
				<th>Trade Card Bonus</th>
				<td>'.$Game->get_trade_card_bonus( ).'</td>
			</tr><tr>
				<th><a id="show_conquer_limit_table" href="#conquer_limit_table" title="show table">Conquer Limit</a></th>
				<td>'.$Game->get_conquer_limit( ).'</td>
			</tr><tr>
				<th>Custom Rules</th>
				<td>'.htmlentities($Game->get_custom_rules( ), ENT_QUOTES, 'UTF-8', false).'</td>
			</tr>
		</tbody>
	</table> <!-- .game_info -->

	<div id="conquer_limit_table">
		<table class="datatable conquer_limits">
			<caption>'.$equation.'</caption>
			<thead>
				<tr>
					<th>'.$conquer_type.'</th>
					<th>Conquer Limit</th>
				</tr>
			</thead>
			<tbody>
				'.$conquer_table.'
			</tbody>
		</table>
	</div>

	<div id="custom_trades_table">
		<table class="datatable custom_trades">
			<thead>
				<tr>
					<th>Trade #</th>
					<th>Value</th>
				</tr>
			</thead>
			<tbody>
				'.$trade_value_table.'
			</tbody>
		</table>
	</div>
';

// make sure this user is not full
$warning = '';
$join_form = '<ul>
			<li><label for="color">Your Color</label><select name="color">'.$color_selection.'</select></li>
			'.$password_box.'

			<li><input type="submit" name="join" value="Join Game" /></li>
		</ul>';
if ($GLOBALS['Player']->max_games && ($GLOBALS['Player']->max_games <= $GLOBALS['Player']->current_games)) {
	$warning = '<p class="warning">You have reached your maximum allowed games, you can not join this game !</p>';
	$join_form = '';
}

$game_players = $Game->get_players( );

$invitation_form = '';
if ( ! $joined) {
	// join form
	$contents .= <<< EOT
	{$warning}
	<form method="post" action="{$_SERVER['REQUEST_URI']}"><div class="formdiv">
		<input type="hidden" name="token" value="{$_SESSION['token']}" />
		<input type="hidden" name="game_id" value="{$_GET['id']}" />
		<input type="hidden" name="player_id" value="{$_SESSION['player_id']}" />

		{$join_form}

	</div></form>
EOT;
}
else {
	// start game button
	if ($Game->is_host($_SESSION['player_id'])) {
		$contents .= <<< EOT
		<form method="post" action="{$_SERVER['REQUEST_URI']}"><div class="formdiv">
			<input type="hidden" name="token" value="{$_SESSION['token']}" />
			<input type="hidden" name="game_id" value="{$_GET['id']}" />
			<input type="hidden" name="player_id" value="{$_SESSION['player_id']}" />

			<ul>
				<li><input type="submit" name="start" value="Start Game" /></li>
			</ul>

		</div></form>
EOT;

	}

	// invitation form
	// grab our current player id list
	$players_joined = array_keys($game_players);

	// grab the full list of players
	$players_full = GamePlayer::get_list( );
	$invite_players = array_shrink($players_full, 'player_id');

	// grab the players who's max game count has been reached
	$players_maxed = GamePlayer::get_maxed( );

	// grab the players who have opted out of the invite list
	$players_opt_out = GamePlayer::get_opt_out( );

	// remove the joined and maxed players from the invite list
	$players = array_diff($invite_players, array_merge($players_joined, $players_maxed, $players_opt_out));

	// create the form
	$invitation_form = '
		<form action="'.$_SERVER['REQUEST_URI'].'" method="post"><div class="formdiv">
			<p>Invite other players to this game:</p>
			<input type="hidden" name="token" value="'.$_SESSION['token'].'" />
			<input type="hidden" name="game_id" value="'.$_GET['id'].'" />
			<div><label for="player_ids">Players</label><select name="player_ids[]" id="player_ids" multiple="multiple" size="5">';

	foreach ($players_full as $player) {
		if (in_array($player['player_id'], $players)) {
			$invitation_form .= '
				<option value="'.$player['player_id'].'">'.$player['username'].'</option>';
		}
	}

	$invitation_form .= '
			</select></div>
			<div><label for="extra_text">Your Message</label><textarea name="extra_text" id="extra_text" rows="10" cols="30"></textarea></div>
			<div><input type="submit" name="invite" value="Invite" /></div>
		</div></form>';
}

$contents .= '
	<hr class="fancy" />';

// joined list
if (is_array($game_players) && count($game_players)) {
	$contents .= '
			<div id="joined">
				<ul>';

	foreach ($game_players as $player_id => $player) {
		if (0 == $player_id) {
			continue;
		}

		$contents .= '
					<li class="'.substr($player['color'], 0, 3).'">'.( ! empty($player['host']) ? '&ndash; ' : '').$player['username'].'</li>';
	}

	$contents .= '
				</ul>
			</div>';
}

// invitation form
$contents .= $invitation_form;

echo get_header($meta);
echo get_item($contents, $hints, $meta['title']);
call($GLOBALS);
echo get_footer( );

