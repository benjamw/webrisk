<?php

define('LOGIN', false);
require_once 'includes/inc.global.php';

// i don't care who they are, or where they come from
// if they hit this page, log them out
$GLOBALS['Player'] = new GamePlayer( );
$GLOBALS['Player']->log_out(false, true);

$meta['title'] = 'Login';
$meta['show_menu'] = false;
$meta['head_data'] = '
	<script src="scripts/jquery.overlabel.js"></script>
	<script src="scripts/jquery.showpass.js"></script>
	<script>//<![CDATA[
		jQuery(document).ready( function($) {
			$("div.formdiv label").not(".inline").overlabel( );
			$("div.formdiv input").showpass( );
		});
	//]]></script>

	<style>
		.formdiv div {
			position: relative;
		}
		label.overlabel {
			color: #999;
		}
		label.overlabel-apply {
			position: absolute;
			top: 2px;
			left: 5px;
			z-index: 1;
			color: #999;
		}
	</style>
';

$date_format = 'D, M j, Y g:i a';
$approve_users = false;
$new_users = true;
$max_users = 0;
if (class_exists('Settings') && Settings::test( )) {
	$date_format = Settings::read('long_date');
	$approve_users = Settings::read('approve_users');
	$new_users = Settings::read('new_users');
	$max_users = Settings::read('max_users');
}

echo get_header($meta);

?>
		<div id="notes">
			<div id="date"><?php echo ldate($date_format); ?></div>
			<p><strong>Welcome to <?php echo GAME_NAME; ?>!</strong></p>
			<p>Please enter a valid username and password to enter.</p>
			<?php if ($approve_users) { ?>
			<p><span class="notice">NOTE</span>: You will be unable to log in if your account has not been approved yet.</p>
			<p>You should receive an email when your account has been approved.</p>
			<?php } ?>
		</div>

		<div id="content">
			<h2>Login</h2>
			<noscript class="notice ctr">
				<p>Warning! Javascript must be enabled for proper operation of <?php echo GAME_NAME; ?>.</p>
			</noscript>

			<form method="post" action="index.php<?php echo $GLOBALS['_?_DEBUG_QUERY']; ?>"><div class="formdiv">
				<div><label for="username">Username</label><input type="text" id="username" name="username" size="15" maxlength="20" tabindex="1" /></div>
				<div><label for="password">Password</label><input type="password" id="password" name="password" class="inputbox" size="15" tabindex="2" /></div>
				<label for="remember" class="inline"><input type="checkbox" id="remember" name="remember" checked="checked" tabindex="3" />Remember me</label><br />
				<input type="submit" name="login" value="Log in" tabindex="4" />
				<?php
				if ((true == $new_users) && ((0 == $max_users) || (GamePlayer::get_count( ) < $max_users))) {
					?><input type="button" value="Register" onclick="window.open('register.php<?php echo $GLOBALS['_?_DEBUG_QUERY']; ?>', '_self')" tabindex="5" /><?php
				}
				?>
			</div></form>

			<noscript class="notice ctr">
				<p>Warning! Javascript must be enabled for proper operation of <?php echo GAME_NAME; ?>.</p>
			</noscript>
		</div>

		<?php call($GLOBALS); ?>

		<div id="footerspacer">&nbsp;</div>

		<div id="footer">
			<p><?php echo GAME_NAME; ?> <?php echo $GLOBALS['_VERSION']; ?>, last updated <?php echo ldate('F j, Y', strtotime($GLOBALS['_UPDATED'])); ?></p>
			<p><?php echo GAME_NAME; ?> is Free Software released under the GNU General Public License (GPL).</p>
		</div>

	</div>
</body>
</html>
