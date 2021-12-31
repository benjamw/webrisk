<?php

$GLOBALS['NODEBUG'] = true;
$GLOBALS['AJAX'] = true;


// don't require log in when testing for used usernames and emails
if (isset($_POST['validity_test']) || (isset($_GET['validity_test']) && isset($_GET['DEBUG']))) {
	define('LOGIN', false);
}


require_once 'includes/inc.global.php';


// if we are debugging, change some things for us
// (although REQUEST_METHOD may not always be valid)
if (('GET' === $_SERVER['REQUEST_METHOD']) && DEBUG) {
	$GLOBALS['NODEBUG'] = false;
	$GLOBALS['AJAX'] = false;
	$_GET['token'] = $_SESSION['token'];
	$_GET['keep_token'] = true;
	$_POST = $_GET;
	$DEBUG = true;
	call('AJAX HELPER');
	call($_POST);
}


// run the index page refresh checks
if (isset($_POST['timer'])) {
	$message_count = (int) Message::check_new($_SESSION['player_id']);
	$turn_count = (int) Game::check_turns($_SESSION['player_id']);
	echo $message_count + $turn_count;
	exit;
}


// run registration checks
if (isset($_POST['validity_test'])) {
#	if (('email' == $_POST['type']) && ('' == $_POST['value'])) {
#		echo 'OK';
#		exit;
#	}

	$player_id = 0;
	if ( ! empty($_POST['profile'])) {
		$player_id = (int) $_SESSION['player_id'];
	}

	switch ($_POST['validity_test']) {
		case 'username' :
		case 'email' :
			$username = '';
			$email = '';
			${$_POST['validity_test']} = $_POST['value'];

			$player_id = (isset($_POST['player_id']) ? (int) $_POST['player_id'] : 0);

			try {
				Player::check_database($username, $email, $player_id);
			}
			catch (MyException $e) {
				echo $e->getCode( );
				exit;
			}
			break;

		default :
			break;
	}

	echo 'OK';
	exit;
}


// run the custom trade table builder
if (isset($_POST['custom_trades'])) {
	echo trade_value_table(Game::calculate_trade_values($_POST['custom_trades']));
	exit;
}


// run the in game chat
if (isset($_POST['chat'])) {
	try {
		if ( ! isset($_SESSION['game_id'])) {
			$_SESSION['game_id'] = 0;
		}

		$Chat = new Chat((int) $_SESSION['player_id'], (int) $_SESSION['game_id']);
		$Chat->send_message($_POST['chat'], isset($_POST['private']), isset($_POST['lobby']));
		$return = $Chat->get_box_list(1);
		$return = $return[0];
	}
	catch (MyException $e) {
		$return['error'] = 'ERROR: '.$e->outputMessage( );
	}

	echo json_encode($return);
	exit;
}


// init our game
$Game = new Game((int) $_SESSION['game_id']);


// run the card clicks
if (isset($_POST['cardcheck'])) {
	// easter egg?  maybe...
	$notice = [
		'Nice Try',
		'Keep trying to cheat, and we\'ll gang up on you',
		'I\'m telling your parents or legal guardians that you\'re trying to cheat',
		'CHEATER',
		'What? You actually thought that would work?',
		'They have cards, but I\'m not telling you what they are',
		'I\'m sure the cards they have are the ones you need',
		'Stop that',
		'Not tellin\'',
		'It would be easier to ask them what their cards are',
		'I don\'t think they would appreciate you trying to cheat',
		'I\'ve just sucked one year of your life away',
	];

	if ($_POST['id'] != $_SESSION['player_id']) {
		echo $notice[mt_rand(0, count($notice) - 1)];
		exit;
	}

	try {
		echo $Game->get_cards($_POST['id']);
	}
	catch (MyException $e) {
		echo 'ERROR';
	}

	exit;
}


// do some more validity checking for the rest of the functions

if (empty($DEBUG) && empty($_POST['notoken'])) {
	test_token( ! empty($_POST['keep_token']));
}

if ($_POST['game_id'] != $_SESSION['game_id']) {
	echo 'ERROR: Incorrect game id given';
	exit;
}


// make sure we are the player we say we are
// unless we're an admin, then it's ok
$player_id = (int) $_POST['player_id'];
if (($player_id != $_SESSION['player_id']) && ! $GLOBALS['Player']->is_admin) {
	echo 'ERROR: Incorrect player id given';
	exit;
}


// run the 'Nudge' button
if (isset($_POST['nudge'])) {
	$return = [];
	$return['token'] = $_SESSION['token'];

	try {
		$Game->nudge($player_id);
	}
	catch (MyException $e) {
		$return['error'] = 'ERROR: '.$e->outputMessage( );
	}

	echo json_encode($return);
	exit;
}


// run the 'Skip' button
if (isset($_POST['skip'])) {
	$return = [];
	$return['token'] = $_SESSION['token'];

	try {
		$Game->skip($_POST['skip'], $player_id);
	}
	catch (MyException $e) {
		$return['error'] = 'ERROR: '.$e->outputMessage( );
	}

	$return['action'] = 'RELOAD';
	echo json_encode($return);
	exit;
}


// run the game actions
if (isset($_POST['state'])) {
	$return = [];
	$return['token'] = $_SESSION['token'];

	switch ($_POST['state']) {
		case 'waiting' : // we are resigning
			try {
				$Game->resign($player_id);
				$return['action'] = 'RELOAD';
			}
			catch (MyException $e) {
				$return['error'] = 'ERROR: '.$e->outputMessage( );
			}
			break;

		case 'trading' :
			try {
				$Game->trade_cards($player_id, $_POST['cards'], $_POST['bonus_card'] ?? null);
				$return['action'] = 'RELOAD';
			}
			catch (MyException $e) {
				$return['error'] = 'ERROR: '.$e->outputMessage( );
			}
			break;

		case 'placing' :
			try {
				$curState = $Game->state;
				$Game->place_armies($player_id, (int) $_POST['num_armies'], (int) $_POST['land_id']);
				$return['state'] = $Game->get_player_state($player_id);
				$return['armies'] = $Game->get_player_armies($player_id);
				$return['land_id'] = (int) $_POST['land_id'];
				$return['num_on_land'] = $Game->get_land_armies((int) $_POST['land_id']);

				// if the game switches from Placing to Playing, and it happens to be
				// this players turn, it will return more pieces to place, without
				// reloading and showing the true board
				if ((0 == $return['armies']) || ($curState != $Game->state)) {
					$return['action'] = 'RELOAD';
				}
			}
			catch (MyException $e) {
				$return['error'] = 'ERROR: '.$e->outputMessage( );
			}
			break;

		case 'attacking' :
			try {
				if (isset($_POST['use_attack_path'])) {
					$defeated = $Game->attack_path($player_id, $_POST['num_armies'], $_POST['attack_id'], $_POST['attack_path']);
					$return['action'] = 'RELOAD';
				}
				elseif (isset($_POST['till_dead'])) {
					$defeated = $Game->attack_till_dead($player_id, $_POST['num_armies'], $_POST['attack_id'], $_POST['defend_id']);
				}
				else {
					$defeated = $Game->attack($player_id, $_POST['num_armies'], $_POST['attack_id'], $_POST['defend_id']);
					$return['dice'] = $Game->get_dice( );
				}

				$fog = $Game->get_fog_of_war( );

				$return['attack_id'] = (int) $_POST['attack_id'];
				$return['num_on_attack'] = $Game->get_land_armies((int) $_POST['attack_id']);
				$return['defend_id'] = (int) $_POST['defend_id'];
				$return['num_on_defend'] = $Game->get_land_armies((int) $_POST['defend_id']);

				if ( ! $defeated && 'Show None' == $fog['armies']) {
					$return['num_on_defend'] = '?';

					if (isset($return['dice']['defend'])) {
						$return['dice']['defend'] = ['?', '?', '?'];
					}
				}

				if ('Attacking' != $Game->get_player_state($player_id)) {
					if ('Occupying' == $Game->get_player_state($player_id)) {
						$return['state'] = 'occupying';
						$return['action'] = $Game->draw_action( );
					}
					else {
						$return['action'] = 'RELOAD';
					}
				}

				if ('Finished' == $Game->state) {
					$return['action'] = 'RELOAD';
				}
			}
			catch (MyException $e) {
				// player may have run out of attackable armies
				// while attacking, so don't pass the error
				if (221 != $e->getCode( )) {
					$return['error'] = 'ERROR: '.$e->outputMessage( );
				}
				else {
					$return['action'] = 'RELOAD';
				}
			}
			break;

		case 'occupying' :
			try {
				$Game->occupy($player_id, $_POST['num_armies']);
				$return['action'] = 'RELOAD';
			}
			catch (MyException $e) {
				$return['error'] = 'ERROR: '.$e->outputMessage( );
			}
			break;

		case 'fortifying' :
			try {
				$Game->fortify($player_id, $_POST['num_armies'], $_POST['from_id'], $_POST['to_id']);

				$return['from_id'] = (int) $_POST['from_id'];
				$return['num_on_from'] = $Game->get_land_armies((int) $_POST['from_id']);
				$return['to_id'] = (int) $_POST['to_id'];
				$return['num_on_to'] = $Game->get_land_armies((int) $_POST['to_id']);

				if ('Fortifying' != $Game->get_player_state($player_id)) {
					$return['action'] = 'RELOAD';
				}
			}
			catch (MyException $e) {
				$return['error'] = 'ERROR: '.$e->outputMessage( );
				$return['action'] = 'RELOAD';
			}
			break;

		default :
			break;
	}

	echo json_encode($return);
	exit;
}

