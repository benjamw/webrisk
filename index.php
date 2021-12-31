<?php

require_once 'includes/inc.global.php';

// remove any previous game sessions
unset($_SESSION['game_id']);

// grab the message and game counts
$message_count = (int) Message::check_new($_SESSION['player_id']);
$turn_count = (int) Game::check_turns($_SESSION['player_id']);
$turn_msg_count = $message_count + $turn_count;

$meta['title'] = 'Game List';
$meta['head_data'] = '
	<script src="scripts/jquery.jplayer.min.js"></script>
	<script src="scripts/index.js"></script>
	<script>//<![CDATA[
		var turn_msg_count = '.$turn_msg_count.';
	//]]></script>
';
$meta['foot_data'] = '
	<div id="sounds"></div>
';


// grab the list of games
$list = Game::get_list($_SESSION['player_id']);

$contents = '';

$table_meta = [
	'sortable' => true ,
	'no_data' => '<p>There are no games to show</p>' ,
	'caption' => 'Current Games' ,
];
$table_format = [
	['SPECIAL_HTML', 'true', 'id="g[[[game_id]]]"'],
	['SPECIAL_CLASS', '(1 == \'[[[highlight]]]\')', 'highlight'],

	['ID', 'game_id'],
	['Name', 'clean_name'],
	['State', '###(([[[paused]]]) ? \'Paused\' : ((\'Waiting\' == \'[[[state]]]\') ? ((\'\' != \'[[[password]]]\') ? \'<span class="highlight password">[[[state]]]</span>\' : \'<span class="highlight">[[[state]]]</span>\') : \'[[[state]]]\'))'],
	['Current Player', '###((\'\' == \'[[[username]]]\') ? \'[[[hostname]]]\' : \'[[[username]]]\')'],
//	['Game Type', 'game_type'] ,
	['Extra Info', '<abbr title="Fortify: [[[get_fortify]]] | Kamikaze: [[[get_kamikaze]]] | Warmonger: [[[get_warmonger]]] | Nuke: [[[get_nuke]]] | Turncoat: [[[get_turncoat]]] | FoW Armies: [[[get_fog_of_war_armies]]] | FoW Colors: [[[get_fog_of_war_colors]]] | Conquer Limit: [[[get_conquer_limit]]] | Custom Rules: [[[clean_custom_rules]]]">Hover</abbr>'],
	['Players', '[[[players]]] / [[[capacity]]]'],
	['Last Move', '###ldate(Settings::read(\'long_date\'), strtotime(\'[[[last_move]]]\'))', null, ' class="date"'],
];
$contents .= '
	<div class="tableholder">
		'.get_table($table_format, $list, $table_meta).'
	</div>';

// create the lobby
$Chat = new Chat($_SESSION['player_id'], 0);
$chat_data = $Chat->get_box_list( );

// temp storage for gravatar imgs
$gravatars = [];

$lobby = '
	<div id="lobby">
		<div class="caption">Lobby</div>
		<div id="chatbox">
			<form action="'.$_SERVER['REQUEST_URI'].'" method="post"><div>
				<input type="hidden" name="lobby" value="1" />
				<input id="chat" type="text" name="chat" />
			</div></form>';

	if (is_array($chat_data)) {
		$lobby .= '
			<dl id="chats">';

		foreach ($chat_data as $chat) {
			// preserve spaces in the chat text
			$chat['message'] = str_replace("\t", '    ', $chat['message']);
			$chat['message'] = str_replace('  ', ' &nbsp;', $chat['message']);

			if ( ! isset($gravatars[$chat['email']])) {
				$gravatars[$chat['email']] = Gravatar::src($chat['email']);
			}

			$grav_img = '<img src="'.$gravatars[$chat['email']].'" alt="" /> ';

			if ('' == $chat['username']) {
				$chat['username'] = '[deleted]';
			}

			$lobby .= '
				<dt>'.$grav_img.'<span>'.ldate(Settings::read('short_date'), strtotime($chat['create_date'])).'</span> '.$chat['username'].'</dt>
				<dd>'.htmlentities($chat['message'], ENT_QUOTES, 'UTF-8', false).'</dd>';
		}

		$lobby .= '
			</dl> <!-- #chats -->';
	}

	$lobby .= '
		</div> <!-- #chatbox -->
	</div> <!-- #lobby -->';

$contents .= $lobby;

$hints = [
	'Select a game from the list and resume play by clicking anywhere on the row.' ,
	'<span class="highlight">Colored entries</span> indicate that it is your turn.' ,
	'Games that are displayed: <span class="highlight password">Waiting</span>, are password protected' ,
	'<span class="warning">WARNING!</span><br />Games will be deleted after '.Settings::read('expire_games').' days of inactivity.' ,
	'Finished games will be deleted after '.Settings::read('expire_finished_games').' days.' ,
];

echo get_header($meta);
echo get_item($contents, $hints, $meta['title']);
call($GLOBALS);
echo get_footer($meta);

