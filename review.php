<?php

require_once 'includes/inc.global.php';

// grab the game file name
if (isset($_GET['file'])) {
	$_SESSION['game_file'] = $_GET['file'];
}
elseif ( ! isset($_SESSION['game_file'])) {
	if ( ! defined('DEBUG') || ! DEBUG) {
		Flash::store('No Game File Given !');
	}
	else {
		call('NO GAME FILE GIVEN');
	}

	exit;
}

if (isset($_GET['step'])) {
	$_SESSION['step'] = (int) $_GET['step'];
}

if (empty($_SESSION['step'])) {
	$_SESSION['step'] = 0;
}

// load the game
// always refresh the game data, there may be more than one person online
try {
	$file_name = explode('_', $_SESSION['game_file']);

	try {
		$Game = new Game($file_name[1]); // check and see if the game is finished

		if ('Finished' !== $Game->state) {
			throw new MyException('Game is not finished', 999);
		}
	}
	catch (MyException $e) {
		if (999 === $e->getCode( )) {
			throw $e;
		}
	}

	$Review = new Review($_SESSION['game_file'], $_SESSION['step']);
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

$chat_html = '';
unset($Chat);

$meta['title'] = htmlentities($Review->name, ENT_QUOTES, 'UTF-8', false).' - #'.$_SESSION['game_file'];
$meta['show_menu'] = false;
$meta['head_data'] = '
	<link rel="stylesheet" type="text/css" media="screen" href="css/game.css" />

	<script>//<![CDATA[
		var state = "watching";
		var game_file = '.json_encode($_SESSION['game_file']).';
		var step = '.json_encode($Review->step).';
		var steps = '.json_encode($Review->get_steps( )).';
	/*]]>*/</script>
	<script src="scripts/review.js"></script>
';

echo get_header($meta);

?>

		<div id="contents">
			<ul id="buttons">
				<li><a href="index.php<?php echo $GLOBALS['_?_DEBUG_QUERY']; ?>">Main Page</a></li>
				<li><a href="game.php<?php echo $GLOBALS['_?_DEBUG_QUERY']; ?>">Reload Game Board</a></li>
			</ul>
			<h2>Game #<?php echo $Review->id.': '.htmlentities($Review->name, ENT_QUOTES, 'UTF-8', false); ?> <span class="type"><a href="#game_info" class="fancybox">Game Info</a></span></h2>

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

				<?php echo board($Review); ?>

				<div id="next"><?php echo $Review->get_trade_value( ); ?></div>

				<?php echo $Review->draw_players( ); ?>

				<div id="dice"><?php
					$move = $Review->get_step( );

					if ('A' === $move[0]) {
						list($type, $action) = explode(' ', $move);
						$action = explode(':', $action);
						$rolls = explode(',', $action[4]);

						$players = $Review->get_players( );

						$attack_class = substr($players[$action[0]]['color'], 0, 3);
						$defend_class = substr($players[$action[2]]['color'], 0, 3);

						echo '<div class="attack">';

						foreach (str_split($rolls[0]) as $die) {
							echo '<div class="'.$attack_class.' dc'.$die.'">'.$die.'</div>';
						}

						echo '</div><div class="defend">';

						foreach (str_split($rolls[1]) as $die) {
							echo '<div class="'.$defend_class.' dc'.$die.'">'.$die.'</div>';
						}

						echo '</div>';
					}
				?></div>

			</div> <!-- #board -->

			<div id="controls">
				<div class="review">
					<div class="steps"><?php echo ($Review->step + 1).' / '.$Review->get_steps_count( ); ?></div>
					<span class="button prev player" title="Prev Player">&lt; P</span>
					<span class="button prev state" title="Prev State">&lt; S</span>
					<span class="button prev action" title="Prev Action">&lt; A</span>
					<span class="button next action" title="Next Action">A &gt;</span>
					<span class="button next state" title="Next State">S &gt;</span>
					<span class="button next player" title="Next Player">P &gt;</span>
				</div>

				<?php
					$players = $Review->get_players( );

					$colors = [];
					foreach ($players as $key => $player) {
						$colors[$player['color']] = htmlentities($GLOBALS['_PLAYERS'][$key]).' ['.$key.']';
					}

					$move_info = nl2br(trim(trim($Review->get_move_info( ), " -=+")));
					// wrap the player name in a class of the players color
					foreach ($colors as $color => $player) {
						if (false !== strpos($move_info, $player)) {
							$move_info = str_replace($player, '<span class="'.substr($color, 0, 3).'">'.$player.'</span>', $move_info);
						}
					}
				?>

				<div id="move_info"><?php echo $move_info; ?></div>
			</div> <!-- #controls -->

			<div id="history">
				<a href="review_history.php" data-fancybox-type="ajax" class="fancybox">Click for History</a>
			</div> <!-- #history -->

			<?php echo game_info($Review); ?>

			<script>
				$('#game_info').hide( );
			</script>

		</div> <!-- #contents -->

		<?php echo game_map( ); ?>

<?php

call($GLOBALS);
echo get_footer( );

