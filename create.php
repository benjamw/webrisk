<?php

require_once 'includes/inc.global.php';

// this has nothing to do with creating a game
// but I'm running it here to prevent long load
// times on other pages where it would be ran more often
GamePlayer::delete_inactive(Settings::read('expire_users'));
Game::delete_inactive(Settings::read('expire_games'));
Game::delete_finished(Settings::read('expire_finished_games'));

$Game = new Game( );

if (isset($_POST['create'])) {
	// make sure this user is not full
	if ($GLOBALS['Player']->max_games && ($GLOBALS['Player']->max_games <= $GLOBALS['Player']->current_games)) {
		Flash::store('You have reached your maximum allowed games !');
	}

	test_token( );

	try {
		$game_id = $Game->create( );
		Flash::store('Game Created Successfully');
	}
	catch (MyException $e) {
		Flash::store('Game Creation FAILED !', false);
	}
}

$color_selection = '';
foreach ($Game->get_avail_colors( ) as $color) {
	$color_selection .= '<option class="'.strtolower(substr($color, 0, 3)).'">'.ucfirst($color).'</option>';
}

$meta['title'] = 'Create Game';
$meta['head_data'] = '
	<script src="scripts/create.js"></script>
';

$hints = [
	'Create a game by filling out your desired game options.' ,
	'<span class="highlight">WARNING!</span><br />Games will be deleted after '.Settings::read('expire_games').' days of inactivity.' ,
];

// make sure this user is not full
$submit_button = '<div><input type="submit" name="create" value="Create Game" /></div>';
$warning = '';
if ($GLOBALS['Player']->max_games && ($GLOBALS['Player']->max_games <= $GLOBALS['Player']->current_games)) {
	$submit_button = $warning = '<p class="warning">You have reached your maximum allowed games, you can not create this game !</p>';
}

$contents = <<< EOF
	<form method="post" action="{$_SERVER['REQUEST_URI']}"><div class="formdiv">
		<input type="hidden" name="token" value="{$_SESSION['token']}" />
		<input type="hidden" name="player_id" value="{$_SESSION['player_id']}" />

		<div>
			{$warning}

			<div><label for="name">Game Name</label><input type="text" id="name" name="name" maxlength="255" /></div>

			<div><label for="capacity">Capacity</label><select id="capacity" name="capacity"><option>2</option><option>3</option><option>4</option><option>5</option><option selected="selected">6</option></select></div>

			<div><label>Fortifications</label><label class="inline"><input type="radio" name="fortify" value="no" /> No</label>
				<label class="inline"><input type="radio" name="fortify" value="yes" checked="checked" /> Yes</label> |
				<label class="inline"><input type="checkbox" name="multiple_fortify" value="yes" /> Multiple</label>
				<label class="inline"><input type="checkbox" name="connected_fortify" value="yes" /> Connected</label>
			</div>

			<div><label>Kamikaze</label><label class="inline"><input type="checkbox" name="kamikaze" value="yes" /> If you can attack, you must attack</label></div>
			<div><label>Warmonger</label><label class="inline"><input type="checkbox" name="warmonger" value="yes" /> If you can trade, you must trade</label></div>

			<div><label>Fog of War Armies</label><label class="inline"><input type="radio" name="fog_of_war_armies" value="all" checked="checked"/> Show All</label>
				<label class="inline"><input type="radio" name="fog_of_war_armies" value="adjacent" /> Show Adjacent</label>
				<label class="inline"><input type="radio" name="fog_of_war_armies" value="none" /> Show None</label>
			</div>

			<div><label>Fog of War Colors</label><label class="inline"><input type="radio" name="fog_of_war_colors" value="all" checked="checked"/> Show All</label>
				<label class="inline"><input type="radio" name="fog_of_war_colors" value="adjacent" /> Show Adjacent</label>
				<label class="inline"><input type="radio" name="fog_of_war_colors" value="none" /> Show None</label>
			</div>
			
			<div><label>Nuclear War</label><label class="inline"><input type="checkbox" name="nuke" value="yes" /> Trade card DEDUCTS from ENEMY land</label></div>
			<div><label>Turncoat</label><label class="inline"><input type="checkbox" name="turncoat" value="yes" /> Trade card turns enemy allegiance to your army</label></div>
			<div><label>Placement</label><label class="inline"><input type="checkbox" name="place_initial_armies" value="yes" />Randomly Place ALL starting armies</label></div>
			<div><label>Placement Limit</label><input type="text" name="initial_army_limit" value="0" size="5" maxlength="3" /></div>

			<fieldset>
				<legend><label class="inline"><input type="checkbox" name="conquer_limits_box" id="conquer_limits_box" class="fieldset_box" />Conquer Limits</label></legend>
				<div id="conquer_limits">

					<input type="text" name="conquer_conquests_per" id="conquer_conquests_per" size="4" class="conquests_per" />

					conquests for every

					<input type="text" name="conquer_per_number" id="conquer_per_number" size="4" class="per_number" />

					<select id="conquer_type" name="conquer_type">
						<option selected="selected">None</option>
						<option value="trade_value">Trade Value</option>
						<option value="trade_count">Trade Count</option>
						<option>Rounds</option>
						<option>Turns</option>
						<option>Land</option>
						<option>Continents</option>
						<option>Armies</option>
					</select>

					after the first

					<input type="text" name="conquer_skip" id="conquer_skip" size="4" class="skip" />

					multiples, starting from

					<input type="text" name="conquer_start_at" id="conquer_start_at" size="4" class="start_at" />

					<br /><br />
					<div><label for="conquer_minimum">Minimum</label><input type="text" id="conquer_minimum" name="conquer_minimum" size="4" /> (Default: 1)</div>
					<div><label for="conquer_maximum">Maximum</label><input type="text" id="conquer_maximum" name="conquer_maximum" size="4" /> (Default: 42 [infinite])</div>
					<br />
					<div><a href="#conquer_limit_table" id="show_conquer_limit_table">Show conquer limit table</a> Equation: max( ( ( ( floor( ( x - start_point ) / <span class="per_number">per_number</span> ) + 1 ) - <span class="skip">skip</span> ) * <span class="conquests_per">conquests_per</span> ) , 0 ) + <span class="start_at">start_at</span></div>

				</div> <!-- #conquer_limits -->
			</fieldset>

			<fieldset>
				<legend><label class="inline"><input type="checkbox" name="custom_trades_box" id="custom_trades_box" class="fieldset_box" />Custom Trades</label> <a href="help/custom_trades.html" class="help">?</a></legend>
				<div id="custom_trades">

					<div><label for="trade_card_bonus">Trade Card Bonus</label><input type="text" id="trade_card_bonus" name="trade_card_bonus" maxlength="4" size="4" value="2" /> <span class="info">The bonus armies received for trading an occupied territory card</span></div>

					<table class="form">
						<thead>
							<tr>
								<th>Start</th>
								<th>End</th>
								<th>Step</th>
								<th>Times</th>
							</tr>
						</thead>
						<tbody>
							<tr class="clone">
								<td><input type="text" name="custom_trades[NNN][start]" size="4" maxlength="4" /></td>
								<td><input type="text" name="custom_trades[NNN][end]" size="4" maxlength="4" /></td>
								<td><input type="text" name="custom_trades[NNN][step]" size="4" maxlength="4" /></td>
								<td><input type="text" name="custom_trades[NNN][times]" size="4" maxlength="4" /></td>
							</tr>
							<tr>
								<td><input type="text" name="custom_trades[1][start]" size="4" maxlength="4" /></td>
								<td><input type="text" name="custom_trades[1][end]" size="4" maxlength="4" /></td>
								<td><input type="text" name="custom_trades[1][step]" size="4" maxlength="4" /></td>
								<td><input type="text" name="custom_trades[1][times]" size="4" maxlength="4" /></td>
							</tr>
						</tbody>
					</table>
					<img src="images/add.png" id="add_trade_array" alt="add" />

					<div><a href="#custom_trades_table" id="show_custom_trades_table">Show custom trades table</a></div>

				</div> <!-- #custom_trades -->
			</fieldset>

			<div><label for="custom_rules">Custom Rules</label><textarea name="custom_rules" id="custom_rules" rows="5" cols="30"></textarea></div>

			<div class="color"><label for="color">Your Color</label><select id="color" name="color">{$color_selection}</select></div>

			<div class="info">Leave password field blank for no password.<br />NOTE: Password field is NOT hidden.</div>
			<div><label for="password">Password</label><input type="text" id="password" name="password" /></div>

			{$submit_button}
		</div>

	</div></form>

	<div id="conquer_limit_table">
		<table class="datatable conquer_limits">
			<caption>empty</caption>
			<thead>
				<tr>
					<th id="conquer_type_header">When</th>
					<th>Conquer Limit</th>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td>Always</td>
					<td>Infinite</td>
				</tr>
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
				<tr><td>1</td><td>4</td></tr>
				<tr><td>2</td><td>6</td></tr>
				<tr><td>3</td><td>8</td></tr>
				<tr><td>4</td><td>10</td></tr>
				<tr><td>5</td><td>12</td></tr>
				<tr><td>6</td><td>15</td></tr>
				<tr><td>7</td><td>20</td></tr>
				<tr><td>8</td><td>25</td></tr>
				<tr><td>9</td><td>30</td></tr>
				<tr><td>10</td><td>(+5) ...</td></tr>
			</tbody>
		</table>
	</div>
EOF;

echo get_header($meta);
echo get_item($contents, $hints, $meta['title']);
call($GLOBALS);
echo get_footer( );

