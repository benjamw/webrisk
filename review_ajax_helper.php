<?php

$GLOBALS['NODEBUG'] = true;
$GLOBALS['AJAX'] = true;


require_once 'includes/inc.global.php';


// if we are debugging, change some things for us
// (although REQUEST_METHOD may not always be valid)
if (('GET' === $_SERVER['REQUEST_METHOD']) && DEBUG) {
	$GLOBALS['NODEBUG'] = false;
	$GLOBALS['AJAX'] = false;
	$_POST = $_GET;
	$DEBUG = true;
	call('REVIEW AJAX HELPER');
	call($_POST);
}

if (array_key_exists('file', $_REQUEST)) {
	$_SESSION['game_file'] = $_REQUEST['file'];
}

if (empty($_SESSION['game_file'])) {
	echo json_encode(['msg' => 'RELOAD']);
	exit;
}

if (array_key_exists('step', $_REQUEST)) {
	$_SESSION['step'] = (int) $_REQUEST['step'];
}

if (empty($_SESSION['step'])) {
	$_SESSION['step'] = 0;
}

// init our game
$Review = new Review($_SESSION['game_file'], $_SESSION['step']);


// run the card clicks
if (isset($_POST['cardcheck'])) {
	try {
		echo $Review->get_cards($_POST['id']);
	}
	catch (MyException $e) {
		echo 'ERROR';
	}

	exit;
}


// run the game review button clicks
// actually... if this is hit, they've already been handled
// just return stuff to display
$players = $Review->get_players( );

$colors = [];
foreach ($players as $key => $player) {
	$colors[$player['color']] = htmlentities($GLOBALS['_PLAYERS'][$key]).' ['.$key.']';
}

try {

	$board = strip_whitespace(board($Review));
	call($board);

	$players = strip_whitespace($Review->draw_players( ));
	call($players);

	$game_info = game_info($Review); // don't strip_whitespace, it breaks the JS included
	call($game_info);

	$move_info = nl2br(trim(trim($Review->get_move_info( ), " -=+")));
	// wrap the player name in a class of the players color
	foreach ($colors as $color => $player) {
		if (false !== strpos($move_info, $player)) {
			$move_info = str_replace($player, '<span class="'.substr($color, 0, 3).'">'.$player.'</span>', $move_info);
		}
	}
	call($move_info);

	$dice = '';
	$move = $Review->get_step( );
	if ('A' === $move[0]) {
		list($type, $action) = explode(' ', $move);
		$action = explode(':', $action);
		$rolls = explode(',', $action[4]);

		$players_array = $Review->get_players( );

		$attack_class = substr($players_array[$action[0]]['color'], 0, 3);
		$defend_class = substr($players_array[$action[2]]['color'], 0, 3);

		$dice .= '<div class="attack">';

		foreach (str_split($rolls[0]) as $die) {
			$dice .= '<div class="'.$attack_class.' dc'.$die.'">'.$die.'</div>';
		}

		$dice .= '</div><div class="defend">';

		foreach (str_split($rolls[1]) as $die) {
			$dice .= '<div class="'.$defend_class.' dc'.$die.'">'.$die.'</div>';
		}

		$dice .= '</div>';
	}
	call($dice);

	$trade = $Review->get_trade_value( );
	call($trade);

	echo json_encode(compact('board', 'dice', 'game_info', 'move_info', 'players', 'trade'));
}
catch (MyException $e) {
	echo 'ERROR: '.$e->outputMessage( );
}


function strip_whitespace($string) {
	return preg_replace('%[\s]+%', ' ', $string);
}
