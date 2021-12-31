<?php

require_once 'includes/inc.global.php';

// grab the game id
if (isset($_GET['id'])) {
	$_SESSION['game_id'] = (int) $_GET['id'];
}
elseif ( ! isset($_SESSION['game_id'])) {
	if ( ! defined('DEBUG') || ! DEBUG) {
		Flash::store('No Game Id Given !');
	}
	else {
		call('NO GAME ID GIVEN');
	}

	exit;
}

// ALL GAME FORM SUBMISSIONS ARE AJAXED THROUGH /scripts/game.js

// load the game
// always refresh the game data, there may be more than one person online
try {
	$Game = new Game((int) $_SESSION['game_id']);
	call($Game);

	if ('Waiting' == $Game->state) {
		if ( ! defined('DEBUG') || ! DEBUG) {
			session_write_close( );
			header('Location: join.php?id='.$_SESSION['game_id'].$GLOBALS['_&_DEBUG_QUERY']);
		}
		else {
			call('GAME IS WAITING, REDIRECTED TO JOIN AND QUIT');
		}

		exit;
	}
}
catch (MyException $e) {
	if ( ! defined('DEBUG') || ! DEBUG) {
		Flash::store('Error Accessing Game !');
	}
	else {
		call('ERROR ACCESSING GAME :'.$e->outputMessage( ));
	}

	exit;
}


if ( ! $Game->is_player($_SESSION['player_id'])) {
	$Game->watch_mode = true;
	$chat_html = '';
	unset($Chat);
}

if ( ! $Game->watch_mode || $GLOBALS['Player']->is_admin) {
	$players = $Game->get_players( );
	$Chat = new Chat($_SESSION['player_id'], $_SESSION['game_id']);
	$chat_data = $Chat->get_box_list( );

	$chat_html = '
			<div id="chatbox">
				<form action="'.$_SERVER['REQUEST_URI'].'" method="post"><div>
					<input id="chat" type="text" name="chat" />
					<!-- <label for="private" class="inline"><input type="checkbox" name="private" id="private" value="yes" /> Private</label> -->
				</div></form>
				<dl id="chats">';

	if (is_array($chat_data)) {
		foreach ($chat_data as $chat) {
			if ('' == $chat['username']) {
				$chat['username'] = '[deleted]';
			}

			$color = '';
			if (isset($players[$chat['player_id']]['color'])) {
				$color = substr($players[$chat['player_id']]['color'], 0, 3);
			}

			// preserve spaces in the chat text
			$chat['message'] = htmlentities($chat['message'], ENT_QUOTES, 'UTF-8', false);
			$chat['message'] = str_replace("\t", '    ', $chat['message']);
			$chat['message'] = str_replace('  ', ' &nbsp;', $chat['message']);

			$chat_html .= '
					<dt class="'.$color.'"><span>'.ldate(Settings::read('short_date'), strtotime($chat['create_date'])).'</span> '.$chat['username'].'</dt>
					<dd'.($chat['private'] ? ' class="private"' : '').'>'.$chat['message'].'</dd>';
		}
	}

	$chat_html .= '
				</dl> <!-- #chats -->
			</div> <!-- #chatbox -->';
}

$meta['title'] = htmlentities($Game->name, ENT_QUOTES, 'UTF-8', false).' - #'.$_SESSION['game_id'];
$meta['show_menu'] = false;
$meta['head_data'] = '
	<link rel="stylesheet" type="text/css" media="screen" href="css/game.css" />

	<script>//<![CDATA[
		var state = "'.(( ! $Game->watch_mode) ? (( ! $Game->paused) ? strtolower($Game->get_player_state($_SESSION['player_id'])) : 'paused') : 'watching').'";
	/*]]>*/</script>
	<script src="scripts/game.js"></script>
';

$player_state = $Game->get_player_state($_SESSION['player_id']);

$state_info = '';
switch ($player_state) {
	case 'Placing' :
		if (('Placing' == $Game->state) && $Game->get_placement_limit( )) {
			$state_info .= 'Placement Limit: <span class="number">'.$Game->get_placement_limit( ).'</span>';
		}
		break;

	case 'Trading' :
		$state_info .= ('Yes' == $Game->get_warmonger( )) ? 'Warmonger' : '';
		break;

	case 'Attacking' :
	case 'Occupying' :
		$state_info .= ('Yes' == $Game->get_kamikaze( )) ? 'Kamikaze' : '';
		if ('None' != $Game->get_conquer_limit( )) {
			if ('' != $state_info) {
				$state_info .= ' &ndash; ';
			}

			$state_info .= 'Conquests allowed this round: ';
			$state_info .= '<span class="number">'.$Game->get_round_conquer_limit( ).'</span>';
			$state_info .= ' Conquests remaining: ';
			$state_info .= '<span class="number">'.$Game->get_remaining_conquer_limit( ).'</span>';
		}
		break;

	case 'Fortifying' :
		$state_info .= $Game->get_fortify( ).' Fortification';
		break;

	default :
		// do nothing
		break;
}

echo get_header($meta);

?>

		<div id="contents">
			<ul id="buttons">
				<li><a href="index.php<?php echo $GLOBALS['_?_DEBUG_QUERY']; ?>">Main Page</a></li>
				<li><a href="game.php<?php echo $GLOBALS['_?_DEBUG_QUERY']; ?>">Reload Game Board</a></li>
			</ul>
			<h2>Game #<?php echo $_SESSION['game_id'].': '.htmlentities($Game->name, ENT_QUOTES, 'UTF-8', false); ?> <span class="type"><a href="#game_info" class="fancybox">Game Info</a> <?php echo $state_info; ?></span></h2>

			<div id="board">
				<div id="pathmarkers">
					<div id="pm01"></div><div id="pm02"></div><div id="pm03"></div><div id="pm04"></div><div id="pm05"></div>
					<div id="pm06"></div><div id="pm07"></div><div id="pm08"></div><div id="pm09"></div><div id="pm10"></div>
					<div id="pm11"></div><div id="pm12"></div><div id="pm13"></div><div id="pm14"></div><div id="pm15"></div>
					<div id="pm16"></div><div id="pm17"></div><div id="pm18"></div><div id="pm19"></div><div id="pm20"></div>
					<div id="pm21"></div><div id="pm22"></div><div id="pm23"></div><div id="pm24"></div><div id="pm25"></div>
					<div id="pm26"></div><div id="pm27"></div><div id="pm28"></div><div id="pm29"></div><div id="pm30"></div>
					<div id="pm31"></div><div id="pm32"></div><div id="pm33"></div><div id="pm34"></div><div id="pm35"></div>
					<div id="pm36"></div><div id="pm37"></div><div id="pm38"></div><div id="pm39"></div><div id="pm40"></div>
					<div id="pm41"></div><div id="pm42"></div><div id="pm43"></div><div id="pm44"></div>
				</div> <!-- #pathmarkers -->

				<img src="images/blank.gif" width="800" height="449" usemap="#gamemap" alt="" />

				<?php echo board($Game); ?>

				<div id="next"><?php echo $Game->get_trade_value( ); ?></div>

				<?php echo $Game->draw_players( ); ?>

				<div id="dice"></div>

			</div> <!-- #board -->

			<div id="controls">
				<?php echo $Game->draw_action( ); ?>
				<hr />
				<?php echo $chat_html; ?>
			</div> <!-- #controls -->

			<div id="history">
				<a href="history.php" data-fancybox-type="ajax" class="fancybox">Click for History</a>
			</div> <!-- #history -->

			<?php echo game_info($Game); ?>

			<script>
				$('#game_info').hide( );
			</script>

		</div> <!-- #contents -->

		<?php echo game_map( ); ?>

<?php

call($GLOBALS);
echo get_footer( );

