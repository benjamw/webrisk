<?php

/**
 * Retrieves and creates the config.php file.
 *
 * The permissions for the base directory must allow for writing files in order
 * for the config.php to be created using this page.
 *
 * Taken from WordPress
 */

$LOGIN = false;

require_once 'includes/inc.global.php';


if ( ! file_exists(INCLUDE_DIR.'config.php.sample'))
	die('Sorry, I need a config.php.sample file to work from. Please re-upload this file from your installation.');

$config_file = file(INCLUDE_DIR.'config.php.sample');

// include the sample config file to get the default settings out of it
require_once INCLUDE_DIR.'config.php.sample';

// Check if config.php has been created
if (file_exists(INCLUDE_DIR.'config.php'))
	die('<p>The file \'config.php\' already exists. If you need to reset any of the configuration items in this file, please delete it first. You may try <a href="install.php">installing now</a>.</p>');

$step = isset($_GET['step']) ? $_GET['step'] : 0;

$meta['show_menu'] = false;
$meta['show_nav_links'] = false;

switch($step) {
	case 0:
		echo get_header($meta);
?>

<p>Welcome to <?php echo GAME_NAME; ?>. Before getting started, we need some information on the database. You will need to know the following items before proceeding.</p>
<ol>
	<li>Database name</li>
	<li>Database username</li>
	<li>Database password</li>
	<li>Database host</li>
	<li>Table prefix (if you want to run more than one set of iohelix games in a single database) </li>
</ol>
<p><strong>If for any reason this automatic file creation doesn't work, don't worry. All this does is fill in the database information to a configuration file. You may also simply open <code>config.php.sample</code> in a text editor, fill in your information, and save it as <code>config.php</code>.</strong></p>
<p>In all likelihood, these items were supplied to you by your Web Host. If you do not have this information, then you will need to contact them before you can continue. If you&#8217;re all ready&hellip;</p>

<p class="step"><a href="setup-config.php?step=1<?php if ( isset( $_GET['noapi'] ) ) echo '&amp;noapi'; ?>" class="button">Let&#8217;s go!</a></p>
<?php
	break;

	case 1:
		$root_uri = 'http://'.$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME']).'/';

		echo get_header($meta);
	?>
<form method="post" action="setup-config.php?step=2">
	<p>Below you should enter your database connection details. If you're not sure about these, contact your host. </p>
	<table class="form-table">
		<tr>
			<th scope="row"><label for="database">Database Name</label></th>
			<td><input name="database" id="database" type="text" size="25" value="<?php echo $GLOBALS['_DEFAULT_DATABASE']['database']; ?>" /></td>
			<td>The name of the database you want to run <?php echo GAME_NAME; ?> in. </td>
		</tr>
		<tr>
			<th scope="row"><label for="username">User Name</label></th>
			<td><input name="username" id="username" type="text" size="25" value="<?php echo $GLOBALS['_DEFAULT_DATABASE']['username']; ?>" /></td>
			<td>Your MySQL username</td>
		</tr>
		<tr>
			<th scope="row"><label for="password">Password</label></th>
			<td><input name="password" id="password" type="text" size="25" value="<?php echo $GLOBALS['_DEFAULT_DATABASE']['password']; ?>" /></td>
			<td>...and MySQL password.</td>
		</tr>
		<tr>
			<th scope="row"><label for="hostname">Database Host</label></th>
			<td><input name="hostname" id="hostname" type="text" size="25" value="<?php echo $GLOBALS['_DEFAULT_DATABASE']['hostname']; ?>" /></td>
			<td>You should be able to get this info from your web host, if <code>localhost</code> does not work.</td>
		</tr>
		<tr>
			<th scope="row"><label for="master_prefix">Master Prefix</label></th>
			<td><input name="master_prefix" id="master_prefix" type="text" value="<?php echo $master_prefix; ?>" size="25" /></td>
			<td>If you want to run multiple iohelix game sets in a single database, set this.</td>
		</tr>
		<tr>
			<th scope="row"><label for="game_prefix">Game Prefix</label></th>
			<td><input name="game_prefix" id="game_prefix" type="text" value="<?php echo $game_prefix; ?>" size="25" /></td>
			<td>If you want to run multiple iohelix games in a single database, set this.</td>
		</tr>
	</table>
	<p>Below you should enter your server settings details. If you're not sure about these, contact your host. </p>
	<table class="form-table">
		<tr>
			<th scope="row"><label for="mail_y">mail( ) Function</label></th>
			<td><label><input name="mail" id="mail_y" type="radio" value="1" checked="checked" /> Yes</label> <label><input name="mail" id="mail_n" type="radio" value="0" /> No</label></td>
			<td>The game should send out emails</td>
		</tr>
		<tr>
			<th scope="row"><label for="global_admin">Global Admin Name</label></th>
			<td><input name="global_admin" id="global_admin" type="text" size="25" value="<?php echo $GLOBALS['_ROOT_ADMIN']; ?>" /></td>
			<td>Your Permanent Admin username (Your username in game, you will have to enter this again on install, case sensitive)</td>
		</tr>
		<tr>
			<th scope="row"><label for="root_uri">Root URI</label></th>
			<td><input name="root_uri" id="root_uri" type="text" size="50" value="<?php echo $root_uri; ?>" /></td>
			<td>The root URI to the <?php echo GAME_NAME; ?> script (with closing /)</td>
		</tr>
	</table>
	<p class="step"><input name="submit" type="submit" value="Submit" class="button" /></p>
</form>
<?php
	break;

	case 2:
	$database = trim($_POST['database']);
	$username = trim($_POST['username']);
	$password = trim($_POST['password']);
	$hostname = trim($_POST['hostname']);
	$master_prefix = trim($_POST['master_prefix']);
	$game_prefix   = trim($_POST['game_prefix']);

	$mail = (bool) $_POST['mail'] ? 'true' : 'false';
	$global_admin = trim($_POST['global_admin']);
	$root_uri = trim($_POST['root_uri']);

	// Validate $master_prefix: it can only contain letters, numbers and underscores
	if (preg_match('|[^a-z0-9_]|i', $master_prefix)) {
		die('<strong>ERROR</strong>: "Master Prefix" can only contain numbers, letters, and underscores.');
	}

	// Validate $game_prefix: it can only contain letters, numbers and underscores
	if (preg_match('|[^a-z0-9_]|i', $game_prefix)) {
		die('<strong>ERROR</strong>: "Game Prefix" can only contain numbers, letters, and underscores.');
	}

	// Test the db connection.
	$mysql_config['hostname'] = $hostname; // the URI of the MySQL server host
	$mysql_config['username'] = $username; // the MySQL user's name
	$mysql_config['password'] = $password; // the MySQL user's password
	$mysql_config['database'] = $database; // the MySQL database name
	$mysql_config['log_path'] = LOG_DIR; // the MySQL log path

	try {
		Mysql::get_instance($mysql_config);
	}
	catch (Exception $e) {
		die($e->getMessage( ));
	}

	foreach ($config_file as $line_num => $line) {
		switch (substr($line, 0, 40)) {
			case '	$GLOBALS[\'_DEFAULT_DATABASE\'][\'database' :
				$config_file[$line_num] = str_replace("database_here", $database, $line);
				break;

			case '	$GLOBALS[\'_DEFAULT_DATABASE\'][\'username' :
				$config_file[$line_num] = str_replace('username_here', $username, $line);
				break;

			case '	$GLOBALS[\'_DEFAULT_DATABASE\'][\'password' :
				$config_file[$line_num] = str_replace('password_here', $password, $line);
				break;

			case '	$GLOBALS[\'_DEFAULT_DATABASE\'][\'hostname' :
				$config_file[$line_num] = str_replace('localhost', $hostname, $line);
				break;

			case '	$master_prefix = \'\'; // master database' :
				$config_file[$line_num] = str_replace('\'\'', '\''.$master_prefix.'\'', $line);
				break;

			case '	$game_prefix   = \'wr_\'; // game table n' :
				$config_file[$line_num] = str_replace('wp_', $game_prefix, $line);
				break;

			case '	$GLOBALS[\'_ROOT_ADMIN\'] = \'yourname\'; /' :
				$config_file[$line_num] = str_replace('yourname', $global_admin, $line);
				break;

			case '	$GLOBALS[\'_ROOT_URI\']   = \'http://www.y' :
				$config_file[$line_num] = str_replace('http://www.yoursite.com/webrisk/', $root_uri, $line);
				break;

			case '	$GLOBALS[\'_USEEMAIL\']   = true; // SMTP' :
				$config_file[$line_num] = str_replace('true', $mail, $line);
				break;
		}
	}

	if ( ! is_writable(INCLUDE_DIR.'config.php.sample')) :


		echo get_header($meta);
?>
<p>Sorry, but I can't write the <code>config.php</code> file.</p>
<p>You can create the <code>config.php</code> manually and paste the following text into it.</p>
<textarea cols="98" rows="15" class="code"><?php
		foreach ($config_file as $line) {
			echo htmlentities($line, ENT_COMPAT, 'UTF-8');
		}
?></textarea>
<p>After you've done that, click "Run the install."</p>
<p class="step"><a href="install.php" class="button">Run the install</a></p>
<?php


	else :


		$handle = fopen(INCLUDE_DIR.'config.php', 'w');
		foreach ($config_file as $line) {
			fwrite($handle, $line);
		}
		fclose($handle);
		chmod(INCLUDE_DIR.'config.php', 0666);

		echo get_header($meta);
?>
<p>All right sparky! You've made it through this part of the installation. <?php echo GAME_NAME; ?> can now communicate with your database. If you are ready, time now to&hellip;</p>

<p class="step"><a href="install.php" class="button">Run the install</a></p>
<?php


	endif;
	break;
}
?>
</body>
</html>
