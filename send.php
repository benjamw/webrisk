<?php

require_once 'includes/inc.global.php';

if (isset($_POST['submit'])) {
	test_token( );

	// clean the data
	$subject = $_POST['subject'];
	$message = $_POST['message'];
	$user_ids = (array) ife($_POST['user_ids'], [], false);
	$send_date = ife($_POST['send_date'], false, false);
	$expire_date = ife($_POST['expire_date'], false, false);

	try {
		$Message->send_message($subject, $message, $user_ids, $send_date, $expire_date);
		$sent = true;
	}
	catch (MyException $e) {
		if ( ! defined('DEBUG') || ! DEBUG) {
			Flash::store('Error Sending Message !', false);
		}
		else {
			call('ERROR SENDING MESSAGE');
		}

		exit;
	}

	if (isset($_GET['id'])) {
		Flash::store('Message Sent Successfully !', 'messages.php');
	}
}

$message = [
	'subject' => '',
	'message' => '',
];

if (isset($_GET['id'])) {
	try {
		if (isset($_GET['type']) && ('fw' == $_GET['type'])) { // forward
			$message = $Message->get_message_forward((int) $_GET['id']);
		}
		elseif (isset($_GET['type']) && ('rs' == $_GET['type'])) { // resend
			$message = $Message->get_message((int) $_GET['id']);
		}
		else { // reply
			$message = $Message->get_message_reply((int) $_GET['id']);
			$reply_flag = true;
		}
	}
	catch (MyException $e) {
		Flash::store('Error Retrieving Message !', 'messages.php');
	}
}

$meta['title'] = 'Message Writer';
$meta['show_menu'] = false;
$meta['head_data'] = '
	<style>@import url(css/ui.datepicker.css);</style>
	<script src="scripts/ui.datepicker.js"></script>
	<script src="scripts/messages.js"></script>
';

if (isset($sent)) {
	Flash::store('Message Sent Successfully !', false);
}

// grab a list of the players
$list = GamePlayer::get_list(true);

$recipient_options = '';
if (is_array($list)) {
	// send global messages if we can
	if ($GLOBALS['Player']->is_admin) {
		$recipient_options .= '<option value="0">GLOBAL</option>';
	}

	$recipient_id = (isset($message['recipients'][0]['from_id']) && ! empty($reply_flag)) ? $message['recipients'][0]['from_id'] : 0;

	foreach ($list as $player) {
		// remove ourselves from the list
		if ($player['player_id'] == $_SESSION['player_id']) {
			continue;
		}

		$recipient_options .= '<option value="'.$player['player_id'].'"'.get_selected($recipient_id, $player['player_id']).'>'.$player['username'].'</option>';
	}
}

echo get_header($meta);

?>

	<div id="content" class="msg">
		<div class="link_date">
			<a href="messages.php<?php echo $GLOBALS['_?_DEBUG_QUERY']; ?>">Return to Inbox</a>
			<?php echo ldate(Settings::read('long_date')); ?>
		</div>
		<form method="post" action="<?php echo $_SERVER['REQUEST_URI']; ?>"><div class="formdiv">
			<input type="hidden" name="token" value="<?php echo $_SESSION['token']; ?>" />
			<ol>
				<li>
					<div class="info">Press and hold CTRL while selecting to select multiple recipients</div>
					<label for="user_ids">Recipients</label><select name="user_ids[]" id="user_ids" multiple="multiple" size="5">
					<?php echo $recipient_options; ?>
					</select>
				</li>
				<li><label for="send_date">Send Date</label><input type="text" name="send_date" id="send_date" /> <span class="info">Leave blank to send now</span></li>
				<li><label for="expire_date">Expiration Date</label><input type="text" name="expire_date" id="expire_date" /> <span class="info">Leave blank to never expire</span></li>
				<li><label for="subject">Subject</label><input type="text" name="subject" id="subject" value="<?php echo htmlentities($message['subject'], ENT_QUOTES, 'UTF-8', false); ?>" size="50" maxlength="255" /></li>
				<li><label for="message">Message</label><textarea name="message" id="message" rows="15" cols="50"><?php echo htmlentities($message['message'], ENT_QUOTES, 'UTF-8', false); ?></textarea></li>
				<li><label>&nbsp;</label><input type="submit" name="submit" value="Send Message" /></li>
			</ol>
		</div></form>
	</div>

<?php

call($GLOBALS);
echo get_footer( );

