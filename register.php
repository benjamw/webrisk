<?php

define('LOGIN', false);
require_once 'includes/inc.global.php';

// if we have a player_id in session, log them in, and check for admin
if ( ! empty($_SESSION['player_id'])) {
	$GLOBALS['Player'] = new GamePlayer( );
	// this will redirect to login if failed
	$GLOBALS['Player']->log_in( );
}

$no_new_users = (false == Settings::read('new_users'));
$max_users_set = (0 != Settings::read('max_users'));
$max_users_reached = (GamePlayer::get_count( ) >= Settings::read('max_users'));
$not_admin = empty($GLOBALS['Player']) || ! $GLOBALS['Player']->is_admin;

if ($not_admin && ($no_new_users || ($max_users_set && $max_users_reached))) {
	Flash::store('Sorry, but we are not accepting new registrations at this time.');
}

if ($not_admin && isset($_SESSION['player_id'])) {
	$GLOBALS['Player'] = [];
	$_SESSION['player_id'] = false;
	unset($_SESSION['player_id']);
	unset($GLOBALS['Player']);
}

if (isset($_POST['register'])) {
	test_token( );

	// die spammers
	if ('' != $_POST['website']) {
		header('Location: http://www.searchbliss.com/spambot/spambot-stopper.asp');
		exit;
	}

	try {
		$GLOBALS['Player'] = new GamePlayer( );
		$GLOBALS['Player']->register( );
		$Message = new Message($GLOBALS['Player']->id, $GLOBALS['Player']->is_admin);
		$Message->grab_global_messages( );

		Flash::store('Registration Successful !', 'login.php');
	}
	catch (MyException $e) {
		if ( ! defined('DEBUG') || ! DEBUG) {
			Flash::store('Registration Failed !\n\n'.$e->outputMessage( ), true);
		}
		else {
			call('REGISTRATION ATTEMPT REDIRECTED TO REGISTER AND QUIT');
			call($e->getMessage( ));
		}
	}

	exit;
}

$meta['title'] = 'Registration';
$meta['head_data'] = '
	<script>//<![CDATA[
		var profile = 0;
	//]]></script>
	<script src="scripts/register.js"></script>
';
$meta['show_menu'] = false;
echo get_header($meta);

$hints = [
	'Please Register' ,
	'You must remember your username and password to be able to gain access to '.GAME_NAME.' later.' ,
];

if (Settings::read('approve_users')) {
	$hints[] = '<span class="notice">NOTE</span>: You will be unable to log in if your account has not been approved yet.';
	$hints[] = 'You should receive an email when your account has been approved.';
}

if (Settings::read('expire_users')) {
	$hints[] = '<span class="warning">WARNING!</span><br />Inactive accounts will be deleted after '.Settings::read('expire_users').' days.';
}

$contents = <<< EOF

	<form method="post" action="{$_SERVER['REQUEST_URI']}"><div class="formdiv">
		<input type="hidden" name="token" id="token" value="{$_SESSION['token']}" />
		<input type="hidden" name="errors" id="errors" />
		<ul>
			<li><label for="first_name">First Name</label><input type="text" id="first_name" name="first_name" maxlength="20" tabindex="1" /></li>
			<li><label for="last_name">Last Name</label><input type="text" id="last_name" name="last_name" maxlength="20" tabindex="2" /></li>

			<li><label for="username" class="req">Username</label><input type="text" id="username" name="username" maxlength="20" tabindex="3" /><span id="username_check" class="test"></span></li>
			<li><label for="email" class="req">Email</label><input type="text" id="email" name="email" tabindex="4" /><span id="email_check" class="test"></span></li>

			<li style="text-indent:-9999em;">Leave the next field blank (anti-spam).</li>
			<li style="text-indent:-9999em;"><label for="website">Leave Blank</label><input type="text" id="website" name="website" /></li>

			<li><label for="password" class="req">Password</label><input type="password" id="password" name="password" tabindex="5" /></li>
			<li><label for="passworda" class="req">Confirmation</label><input type="password" id="passworda" name="passworda" tabindex="6" /></li>

			<li><input type="submit" name="register" value="Submit" tabindex="7" /></li>
		</ul>

	</div></form>

	<a href="login.php{$GLOBALS['_?_DEBUG_QUERY']}">Return to login</a>

EOF;

echo get_item($contents, $hints, $meta['title']);

call($GLOBALS);
// don't use the usual footer

?>

		<div id="footerspacer">&nbsp;</div>

		<div id="footer">&nbsp;</div>

	</div>
</body>
</html>
