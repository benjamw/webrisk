<?php

require_once 'includes/inc.global.php';

// make sure we are an admin
if ( ! isset($GLOBALS['Player']) || (true !== $GLOBALS['Player']->is_admin)) {
	Flash::store('Nice try');
}

if (isset($_POST['player_action'])) {
	test_token( );

	try {
		switch ($_POST['player_action']) {
			case 'approve' :
				$GLOBALS['Player']->admin_approve($_POST['ids']);
				break;

			case 'reset' :
				$GLOBALS['Player']->admin_reset_pass($_POST['ids']);
				break;

			case 'admin' :
				$GLOBALS['Player']->admin_add_admin($_POST['ids']);
				break;

			case 'unadmin' :
				$GLOBALS['Player']->admin_remove_admin($_POST['ids']);
				break;

			case 'delete' :
				$player_ids = Player::clean_deleted($_POST['ids']);
				$GLOBALS['Player']->admin_delete($player_ids);
				Game::player_deleted($player_ids);
				Message::player_deleted($player_ids);
				break;

			default :
				break;
		}

		Flash::store('Admin Update Successful', true); // redirect kills form resubmission
	}
	catch (MyException $e) {
		Flash::store('Admin Update FAILED !', true); // redirect kills form resubmission
	}
}

if (isset($_POST['game_action'])) {
	test_token( );

	try {
		switch ($_POST['game_action']) {
			case 'pause' :
				Game::pause($_POST['ids']);
				break;

			case 'unpause' :
				Game::pause($_POST['ids'], false);
				break;

			case 'delete' :
				Game::delete($_POST['ids']);
				break;

			default :
				break;
		}

		Flash::store('Admin Update Successful', true); // redirect kills form resubmission
	}
	catch (MyException $e) {
		Flash::store('Admin Update FAILED !', true); // redirect kills form resubmission
	}
}

if (isset($_POST['submit'])) {
	test_token( );

	try {
		// clear the submit and token fields
		$POST = $_POST;
		unset($POST['submit']);
		unset($POST['token']);

		Settings::write_all($POST);

		Flash::store('Admin Update Successful', true); // redirect kills form resubmission
	}
	catch (MyException $e) {
		Flash::store('Admin Update FAILED !', true); // redirect kills form resubmission
	}
}

$meta['title'] = GAME_NAME.' Administration';
$meta['head_data'] = '
	<script type="text/javascript" src="scripts/admin.js"></script>
';

$hints = array(
	'Here you can administrate your '.GAME_NAME.' installation.' ,
	'Click anywhere on a row to mark that row for action.' ,
);

$contents = '';

// get the players
$player_list = GamePlayer::get_list( );

// go through the player list and remove the root admin and ourselves
foreach ($player_list as $key => $player) {
	if ($GLOBALS['_ROOT_ADMIN'] == $player['username']) {
		unset($player_list[$key]);
		continue;
	}

	if ($_SESSION['player_id'] == $player['player_id']) {
		unset($player_list[$key]);
		continue;
	}

	list( , , $player['games'], $player['turn']) = Game::get_count($player['player_id']);

	$player['played'] = $player['wins'] + $player['losses'];

	$player_list[$key] = $player;
}

$table_meta = array(
	'sortable' => true ,
	'no_data' => '<p>There are no players to show</p><!-- NO_PLAYERS -->' ,
	'caption' => 'Players' ,
);
$table_format = array(
	array('ID', 'player_id') ,
	array('Player', 'username') ,
	array('First Name', 'first_name') ,
	array('Last Name', 'last_name') ,
	array('Email', 'email') ,
	array(array('Games', '(Total | Current | Turn)'), '[[[played]]]&nbsp;|&nbsp;[[[games]]]&nbsp;|&nbsp;[[[turn]]]') ,
	array('Admin', '###(([[[full_admin]]] | [[[half_admin]]]) ? \'<span class="notice">Yes</span>\' : \'No\')') ,
	array('Approved', '###(([[[is_approved]]]) ? \'Yes\' : \'<span class="notice">No</span>\')') ,
	array('Last Online', '###ldate(Settings::read(\'long_date\'), strtotime(\'[[[last_online]]]\'))', null, ' class="date"') ,
	array('<input type="checkbox" id="player_all" />', '<input type="checkbox" name="ids[]" value="[[[player_id]]]" class="player_box" />', 'false', 'class="edit"') ,
);
$table = get_table($table_format, $player_list, $table_meta);

if (false === strpos($table, 'NO_PLAYERS')) {
	$contents .= '
		<form method="post" action="'.$_SERVER['REQUEST_URI'].'"><div class="action">
			<input type="hidden" name="token" value="'.$_SESSION['token'].'" />
			'.$table.'
			<select name="player_action" id="player_action">
				<option value="">With Selected:</option>
				<option value="approve">Approve</option>
				<option value="reset">Reset Pass</option>
				<option value="admin">Make Admin</option>
				<option value="unadmin">Remove Admin</option>
				<option value="delete">Delete</option>
			</select>
		</div></form>';
}
else {
	$contents = $table;
}

// get the games
$game_list = Game::get_list( );

$table_meta = array(
	'sortable' => true ,
	'no_data' => '<p>There are no games to show</p><!-- NO_GAMES -->' ,
	'caption' => 'Games' ,
);
$table_format = array(
	array('ID', 'game_id') ,
	array('Name', 'name') ,
	array('Host', 'hostname') ,
	array('Current', 'username') ,
	array('State', '###(([[[paused]]]) ? \'<span class="notice">Paused</span>\' : \'[[[state]]]\')') ,
	array('Players', '[[[players]]] / [[[capacity]]]') ,
	array('Created', '###ldate(Settings::read(\'long_date\'), strtotime(\'[[[create_date]]]\'))', null, ' class="date"') ,
	array('Last Move', '###ldate(Settings::read(\'long_date\'), strtotime(\'[[[last_move]]]\'))', null, ' class="date"') ,
	array('<input type="checkbox" id="game_all" />', '<input type="checkbox" name="ids[]" value="[[[game_id]]]" class="game_box" />', 'false', 'class="edit"') ,
);
$table = get_table($table_format, $game_list, $table_meta);

if (false === strpos($table, 'NO_GAMES')) {
	$contents .= '
		<form method="post" action="'.$_SERVER['REQUEST_URI'].'"><div class="action">
			<input type="hidden" name="token" value="'.$_SESSION['token'].'" />
			'.$table.'
			<select name="game_action" id="game_action">
				<option value="">With Selected:</option>
				<option value="pause">Pause</option>
				<option value="unpause">Unpause</option>
				<option value="delete">Delete</option>
			</select>
		</div></form>';
}
else {
	$contents .= $table;
}


// make the settings form
$settings = Settings::read_all( );
$notes = Settings::read_setting_notes( );

$form = <<< EOF

	<form method="post" action="{$_SERVER['REQUEST_URI']}"><div class="action" style="clear:both;">
		<input type="hidden" name="token" value="{$_SESSION['token']}" />
		<ul class="settings">
EOF;

foreach ($settings as $setting => $value) {
	$human_setting = humanize($setting);
	$value = htmlentities($value, ENT_QUOTES);
	$form .= <<< EOF

			<li><label for="{$setting}">{$human_setting}</label><input type="text" id="{$setting}" name="{$setting}" maxlength="255" value="{$value}" /> <span class="note">{$notes[$setting]}</span></li>
EOF;
}

$form .= <<< EOF

			<li><input type="submit" id="submit" name="submit" value="Update Settings" /></li>
		</ul>
	</div></form>
EOF;

$contents .= $form;


echo get_header($meta);
echo get_item($contents, $hints, $meta['title']);
call($GLOBALS);
echo get_footer( );

