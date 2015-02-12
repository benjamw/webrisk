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
	$Review = new Review($_SESSION['game_file'], $_SESSION['step']);
	d($Review);
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

	<script type="text/javascript">//<![CDATA[
		var state = "watching";
		var game_file = '.json_encode($_SESSION['game_file']).';
		var step = '.json_encode($_SESSION['step']).';
		var steps = '.json_encode($Review->get_steps( )).';
		var steps_length = steps.length;
	/*]]>*/</script>
	<script type="text/javascript" src="scripts/review.js"></script>
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
				<?php
					$board = $Review->get_visible_board( );
					foreach ($board as $land_id => $data) {
						$id_box = '';
						if ( ! empty($_SESSION['admin_id']) || $GLOBALS['Player']->is_admin) {
							$id_box = ' ['.str_pad($land_id, 2, '0', STR_PAD_LEFT).']';
						}

						echo '
							<span class="'.substr($data['color'], 0, 3).$data['resigned'].'" id="sl'.str_pad($land_id, 2, '0', STR_PAD_LEFT).'" title="'.Risk::$TERRITORIES[$land_id][NAME].$id_box.'">'.$data['armies'].'</span>';
					}
				?>

				<div id="next"><?php echo $Review->get_trade_value( ); ?></div>
				<?php echo $Review->draw_players( ); ?>

				<div id="dice"></div>

			</div> <!-- #board -->

			<div id="controls">
				<div class="review">
					<span class="button prev player" title="Prev Player">&lt; P</span>
					<span class="button prev state" title="Prev State">&lt; S</span>
					<span class="button prev action" title="Prev Action">&lt; A</span>
					<div class="steps"><?php echo $_SESSION['step'].'/'.$Review->get_steps_count( ); ?></div>
					<span class="button next action" title="Next Action">A &gt;</span>
					<span class="button next state" title="Next State">S &gt;</span>
					<span class="button next player" title="Next Player">P &gt;</span>
				</div>
			</div> <!-- #controls -->

			<div id="history">
				<a href="history.php" data-fancybox-type="ajax" class="fancybox">Click for History</a>
			</div> <!-- #history -->

			<?php echo game_info($Review); ?>

			<script type="text/javascript">
				$('#game_info').hide( );
			</script>

		</div> <!-- #contents -->

		<?php echo game_map( ); ?>

<?php

call($GLOBALS);
echo get_footer( );

