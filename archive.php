<?php

require_once 'includes/inc.global.php';

// remove any previous game sessions
unset($_SESSION['game_id']);

// grab the message and game counts
$message_count = (int) Message::check_new($_SESSION['player_id']);
$turn_count = (int) Game::check_turns($_SESSION['player_id']);
$turn_msg_count = $message_count + $turn_count;

$meta['title'] = 'Archived Game List';
$meta['head_data'] = '
	<script type="text/javascript" src="scripts/archive.js"></script>
';

// grab the list of games
$list = Game::get_archived_list( );

$contents = '';

$table_meta = array(
	'sortable' => true ,
	'no_data' => '<p>There are no games to show</p>' ,
	'caption' => 'Archived Games' ,
);
$table_format = array(
	array('SPECIAL_HTML', 'true', 'id="g[[[game_id]]]"') ,
	array('SPECIAL_CLASS', '(1 == \'[[[highlight]]]\')', 'highlight') ,

	array('ID', 'game_id') ,
	array('Name', 'clean_name') ,
	array('Winner', '###((\'\' == \'[[[username]]]\') ? \'[[[hostname]]]\' : \'[[[username]]]\')') ,
	array('Extra Info', '<abbr title="Fortify: [[[get_fortify]]] | Kamikaze: [[[get_kamikaze]]] | Warmonger: [[[get_warmonger]]] | FoW Armies: [[[get_fog_of_war_armies]]] | FoW Colors: [[[get_fog_of_war_colors]]] | Conquer Limit: [[[get_conquer_limit]]] | Custom Rules: [[[clean_custom_rules]]]">Hover</abbr>') ,
	array('Players', '[[[players]]] / [[[capacity]]]') ,
	array('Last Move', '###ldate(Settings::read(\'long_date\'), strtotime(\'[[[last_move]]]\'))', null, ' class="date"') ,
);
$contents .= '
	<div class="tableholder">
		'.get_table($table_format, $list, $table_meta).'
	</div>';

$hints = array(
	'Select a game from the list to review by clicking anywhere on the row.' ,
);

echo get_header($meta);
echo get_item($contents, $hints, $meta['title']);
call($GLOBALS);
echo get_footer($meta);

