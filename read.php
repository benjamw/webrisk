<?php

require_once 'includes/inc.global.php';

if (isset($_POST['type']) && ('' != $_POST['type'])) {
	test_token( );

	switch ($_POST['type']) {
		case 'delete' :
			$Message->delete_message((int) $_POST['message_id']);

			if ( ! defined('DEBUG') || ! DEBUG) {
				Flash::store('Message Deleted !', 'messages.php');
			}
			else {
				Flash::store('Message Deleted !', false);
				call('READ PAGE REDIRECTED TO MESSAGES AND QUIT');
			}

			exit;
			break;

		default :
			break;
	}
}

if ( ! isset($_GET['id'])) {
	session_write_close( );
	header('Location: messages.php'.$GLOBALS['_?_DEBUG_QUERY']);
	exit;
}

try {
	$message = $Message->get_message($_GET['id'], $GLOBALS['Player']->is_admin);
	$message['message'] = str_replace("\t", ' &nbsp; &nbsp;', $message['message']);
	$message['message'] = str_replace('  ', ' &nbsp;', $message['message']);
	$message['message'] = htmlentities($message['message'], ENT_QUOTES, 'ISO-8859-1', false);
	$message['message'] = nl2br($message['message']);

	$message['subject'] = htmlentities($message['subject'], ENT_QUOTES, 'ISO-8859-1', false);

	// find out if we're reading an inbox message, or an outbox message
	if ($message['inbox']) {
		$list = $Message->get_outbox_list( );
	}
	elseif ($message['allowed']) {
		$list = $Message->get_inbox_list( );
	}
	else {
		$list = $Message->get_admin_list( );
	}
}
catch (MyException $e) {
	if ( ! defined('DEBUG') || ! DEBUG) {
		Flash::store('Error Finding Message !', 'messages.php');
	}
	else {
		call('ERROR FINDING MESSAGE');
	}

	exit;
}

// grab data for our prev | next links
$prev = false;
$next = false;
$current = false;
$prev_item = false;
foreach ($list as $item) {
	if ($current) {
		$current = false;
		$next = $item['message_id'];
	}

	if ($item['message_id'] == $_GET['id']) {
		$current = true;
		$prev = $prev_item['message_id'];
	}

	$prev_item = $item;
}

$meta['title'] = 'Message Viewer';
$meta['show_menu'] = false;
$meta['head_data'] = '
	<script type="text/javascript" src="scripts/messages.js"></script>
';

echo get_header($meta);

?>

	<div id="content" class="msg">
		<div class="link_date">
			<a href="messages.php">Return to Inbox</a>
			Sent: <?php echo @ifdateor(Settings::read('long_date'), strtotime($message['send_date']), strtotime($message['create_date'])); ?>
		</div>
		<h2 class="subject"><?php echo $message['subject']; ?> <span class="sender">From: <?php echo $message['recipients'][0]['sender']; ?></span></h2>
		<div class="sidebar">
			<form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>"><div class="buttons">
				<div class="prevnext">
					<?php if ($prev) { echo '<a href="'.$_SERVER['SCRIPT_NAME'].'?id='.$prev.$GLOBALS['_&_DEBUG_QUERY'].'">&laquo; Newer</a>'; } ?>
					<?php if ($prev && $next) { echo ' <span>|</span> '; } ?>
					<?php if ($next) { echo '<a href="'.$_SERVER['SCRIPT_NAME'].'?id='.$next.$GLOBALS['_&_DEBUG_QUERY'].'">Older &raquo;</a>'; } ?>
				</div>

<?php if ($message['allowed']) { ?>

				<input type="hidden" name="token" value="<?php echo $_SESSION['token']; ?>" />
				<input type="hidden" name="message_id" id="message_id" value="<?php echo $message['message_id']; ?>" />
				<input type="hidden" name="type" id="type" />
				<input type="button" id="delete" value="Delete" />

	<?php if ($message['recipients'][0]['from_id'] != $_SESSION['player_id']) { ?>

				<input type="button" id="reply" value="Reply" />
				<input type="button" id="forward" value="Forward" />

	<?php } else { ?>

				<input type="button" id="resend" value="Resend" />

	<?php } ?>

<?php } ?>

			</div></form>
			<div id="recipient_list">
				<h4>Recipient List</h4>

				<?php if ($message['global']) { echo '<span class="highlight">GLOBAL</span>'; } ?>

<?php if ( ! $message['global'] || $GLOBALS['Player']->is_admin) { ?>

				<ul>

<?php

		foreach ($message['recipients'] as $recipient) {
			if ($recipient['from_id'] == $recipient['to_id']) {
				continue;
			}

			$classes = array( );
			if (is_null($recipient['view_date'])) {
				$classes[] = 'unread';
			}

			if ($recipient['deleted']) {
				$classes[] = 'deleted';
			}

			$class = '';
			if (count($classes)) {
				$class = ' class="'.implode(' ', $classes).'"';
			}

			echo "<li{$class}>{$recipient['recipient']}</li>\n";
		}

?>

				</ul>

<?php } ?>

			</div>
		</div>

		<p class="message"><?php echo $message['message']; ?></p>

	</div>

<?php

call($GLOBALS);
echo get_footer( );

