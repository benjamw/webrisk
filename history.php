<?php

// this page should only be accessed via AJAX
// as it contains invalid markup otherwise

require_once 'includes/inc.global.php';

if (empty($_SESSION['game_id'])) {
	exit;
}

try {
	$Game = new Game((int) $_SESSION['game_id']);
	$fog_of_war = $Game->get_fog_of_war( );

	$table_format = [
		['SPECIAL_CLASS', true, '[[[class]]]'],
		['Date', '###ldate(Settings::read(\'long_date\'), strtotime(\'[[[create_date]]]\'))', false, '', 'class="date"'],
		['Message', 'message'],
	];
	$table_meta = [
		'no_data' => '<p>There is nothing to show yet</p>' ,
		'caption' => 'Game History &nbsp; &nbsp; <span class="info">Newest entries on top</span>' ,
		'class' => 'history' ,
		'alt_class' => '' ,
	];

	if (('Finished' != $Game->state) && (('Show All' != $fog_of_war['armies']) || ('Show All' != $fog_of_war['colors']))) {
		$history = '<p>The fog overcomes you...</p>';
	}

	if ('Placing' == $Game->state) {
		$history = '<p>History hidden until game start</p>';
	}

	if ( ! isset($history)) {
		$logs = Game::get_logs($_SESSION['game_id']);
		$players = $Game->get_players( );

		$colors = [];
		foreach ($players as $key => $player) {
			$colors[$player['color']] = htmlentities($GLOBALS['_PLAYERS'][$key]).' ['.$key.']';
		}

		foreach ($logs as & $log) {
			// wrap the first all uppercase word in a class of the same name
			$log['message'] = preg_replace_callback('/^([ -+=]*)([A-Z]+)/', 'make_class', $log['message']);

			// add outcome class to attack outcome
			if (' - - ' == substr($log['message'], 0, 5)) {
				$log['message'] = str_replace('">', ' outcome">', $log['message']);
				$log['message'] = str_replace('and was defeated', '<span class="defeat">and was defeated</span>', $log['message']);

				$log['message'] = str_replace('">', ' attack">', $log['message']);
				$log['message'] = str_replace('nuked', '<span class="trade">nuked</span>', $log['message']);
				$log['message'] = str_replace('turned', '<span class="trade">turned</span>', $log['message']);
			}

			// test the data or the message and add a class to the message
			$class = '';
			switch ($log['data'][0]) {
#				case 'A' : $class = 'attack'; break;
#				case 'C' : $class = 'card'; break;
				case 'D' : $class = 'winner'; break;
				case 'E' : $class = 'killed'; break;
#				case 'F' : $class = 'fortify'; break;
				case 'I' : $class = 'init'; break;
				case 'N' : $class = 'next'; break;
#				case 'O' : $class = 'occupy'; break;
#				case 'P' : $class = 'place'; break;
				case 'Q' : $class = 'resign'; break;
#				case 'R' : $class = 'reinforce'; break;
#				case 'T' : $class = 'trade'; break;
#				case 'V' : $class = 'value'; break;
				default :
#					switch (substr($log['message'], 5, 4)) {
#						case 'ATTA' :
#							$class = 'outcome';
#							if (' - -' == substr($log['message'], -4)) {
#								$class .= ' defeated';
#							}
#							break;
#						case 'ROLL' : $class = 'roll'; break;
#						default: $class = ''; break;
#					}
					break;
			}

			$log['class'] = $class;

			// wrap the player name in a class of the players color
			foreach ($colors as $color => $player) {
				if (false !== strpos($log['message'], $player)) {
					$log['message'] = str_replace($player, '<span class="'.substr($color, 0, 3).'">'.$player.'</span>', $log['message']);
				}
			}
		}
		unset($log); // kill the reference

		$history = get_table($table_format, $logs, $table_meta);
	}
}
catch (MyExecption $e) {
	$history = 'ERROR: '.$e->outputMessage( );
}

#echo get_header(array('show_menu' => false, 'title' => 'history', 'head_data' => '	<link rel="stylesheet" type="text/css" media="screen" href="css/board.css" />'));
echo $history;
#echo get_footer( );


function make_class($matches) {
	// '(\'$1<span class="\'.strtolower(\'$2\').\'">$2</span>\')'
	return $matches[1].'<span class="'.strtolower($matches[2]).'">'.$matches[2].'</span>';
}
