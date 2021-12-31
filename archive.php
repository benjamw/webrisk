<?php

require_once 'includes/inc.global.php';

// remove any previous game sessions
unset($_SESSION['game_file']);
unset($_SESSION['step']);

$meta['title'] = 'Archive List';
$meta['head_data'] = '
	<script src="scripts/archive.js"></script>
';


// grab the list of games
$list = Archive::get_list( );

$contents = '';

$table_meta = [
	'sortable' => true ,
	'no_data' => '<p>There are no games to show</p>' ,
	'caption' => 'Archived Games' ,
];
$table_format = [
	['SPECIAL_HTML', 'true', 'id="[[[short_file]]]"'],

	['ID', 'game_id'],
	['Name', 'clean_name'],
	['Winner', '[[[winner]]]'],
//	['Game Type', 'game_type'] ,
	['Extra Info', '<abbr title="Fortify: [[[get_fortify]]] | Kamikaze: [[[get_kamikaze]]] | Warmonger: [[[get_warmonger]]] | FoW Armies: [[[get_fog_of_war_armies]]] | FoW Colors: [[[get_fog_of_war_colors]]] | Conquer Limit: [[[get_conquer_limit]]] | Custom Rules: [[[clean_custom_rules]]]">Hover</abbr>'],
	['Players', '[[[players]]]'],
	['Start Date', '###ldate(Settings::read(\'long_date\'), strtotime(\'[[[start_date]]]\'))', null, ' class="date"'],
	['End Date', '###ldate(Settings::read(\'long_date\'), strtotime(\'[[[end_date]]]\'))', null, ' class="date"'],
];
$contents .= '
	<div class="tableholder">
		'.get_table($table_format, $list, $table_meta).'
	</div>';

$hints = [
	'Select a game from the list and review play by clicking anywhere on the row.' ,
];

echo get_header($meta);
echo get_item($contents, $hints, $meta['title']);
call($GLOBALS);
echo get_footer($meta);

