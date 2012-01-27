<?php

// WebRisk install script

$LOGIN = false;

require_once 'includes/inc.global.php';

// check and make sure we're not locked out
if (is_file('install.lock')) {
//	die('If you wish to reinstall '.GAME_NAME.', please delete the install.lock file');
}

// check and make sure we have a config file
if ( ! is_file('includes/config.php')) {
	// lets require a few things...
	$GLOBALS['_DEFAULT_COLOR'] = 'yellow_black';
	require_once 'includes/html.general.php';
	require_once 'includes/func.html.php';
	require_once 'classes/flash.class.php';

	$message = '';
	$invalid = false;
	if (isset($_POST['create'])) {
		// test the validity
		if (0 != strcmp($_POST['password'], $_POST['passworda'])) {
			$invalid = true;
			$message = 'The Admin passwords entered do not match';
		}

		if ( ! $invalid) {
			create_config_file( );

			// now use the new config file and create the tables and admin
			require_once INCLUDE_DIR.'config.php';
debug($GLOBALS);

			if (Mysql::test( )) {
debug('TEST PASSED');
				create_tables( );
				create_admin( );
			}
			else {
debug('TEST FAILED');
				// delete the file and display an error
				unlink(INCLUDE_DIR.'config.php');

				if (class_exists('Flash')) {
					Flash::store('MySQL Error: '.Mysql::get_instance( )->error);
				}
				else {
					die('MySQL Error: '.Mysql::get_instance( )->error);
				}

				exit;
			}

			// create the lock file
			file_put_contents('install.lock', 'locked');

			session_write_close( );
			header('Location: '.$GLOBALS['_ROOT_URI']);
			exit;
		}
	}

	if ( ! isset($_POST['create']) || $invalid) {
		require_once INCLUDE_DIR.'config.php.sample';

		$fields = array(
			'db_hostname' => $GLOBALS['_DEFAULT_DATABASE']['hostname'],
			'db_username' => $GLOBALS['_DEFAULT_DATABASE']['username'],
			'db_password' => $GLOBALS['_DEFAULT_DATABASE']['password'],
			'db_database' => $GLOBALS['_DEFAULT_DATABASE']['database'],

			'master_prefix' => $master_prefix,
			'game_prefix' => $game_prefix,

			'root_uri' => 'http://'.$_SERVER['HTTP_HOST'].str_replace('install.php', '', $_SERVER['REQUEST_URI']),
			'use_email' => $GLOBALS['_USEEMAIL'] ? 'yes' : 'no',

			'first_name' => '',
			'last_name' => '',
			'username' => '',
			'email' => '',
			'password' => '',
			'passworda' => '',
		);

		foreach ($fields as $key => $default) {
			if (isset($_POST[$key])) {
				$fields[$key] = $_POST[$key];
			}
		}
		extract($fields);

		$contents = '';

		if ($message) {
			$contents .= '<h3 class="notice">'.$message.'</div>';
		}

		$contents .= '
			<form action="'.$_SERVER['REQUEST_URI'].'" method="post"><div>
				<fieldset>
					<legend>Database Info</legend>
					<ul>
						<li><label for="db_database" class="req">Database</label><input type="text" name="db_database" id="db_database" value="'.$db_database.'" /></li>
						<li><label for="db_hostname" class="req">Hostname</label><input type="text" name="db_hostname" id="db_hostname" value="'.$db_hostname.'" /></li>
						<li><label for="db_username" class="req">Username</label><input type="text" name="db_username" id="db_username" value="'.$db_username.'" /></li>
						<li><label for="db_password" class="req">Password</label><input type="text" name="db_password" id="db_password" value="'.$db_password.'" /></li>
					</ul>
				</fieldset>
				<fieldset>
					<legend>Table Info</legend>
					<ul>
						<li><label for="master_prefix">Master Prefix</label><input type="text" name="master_prefix" id="master_prefix" value="'.$master_prefix.'" /></li>
						<li><label for="game_prefix">Game Prefix</label><input type="text" name="game_prefix" id="game_prefix" value="'.$game_prefix.'" /></li>
					</ul>
				</fieldset>
				<fieldset>
					<legend>Server Info</legend>
					<ul>
						<li><label for="root_uri" class="req">Root URI</label><input type="text" name="root_uri" id="root_uri" value="'.$root_uri.'" /></li>
						<li><label for="use_email">Use Email</label><input type="checkbox" name="use_email" id="use_email" value="yes"'.get_selected(true, is_checked($use_email), $selected = false).' /></li>
					</ul>
				</fieldset>
				<fieldset>
					<legend>Admin Account</legend>
					<ul>
						<li><label for="first_name">First Name</label><input type="text" id="first_name" name="first_name" value="'.$first_name.'" maxlength="20" /></li>
						<li><label for="last_name">Last Name</label><input type="text" id="last_name" name="last_name" value="'.$last_name.'" maxlength="20" /></li>
						<li><label for="username" class="req">Username</label><input type="text" id="username" name="username" value="'.$username.'" maxlength="20" /></li>
						<li><label for="email" class="req">Email</label><input type="text" id="email" name="email" value="'.$email.'" /></li>
						<li><label for="password" class="req">Password</label><input type="password" id="password" name="password" value="'.$password.'" /></li>
						<li><label for="passworda" class="req">Confirm</label><input type="password" id="passworda" name="passworda" value="'.$passworda.'" /></li>
					</ul>
				</fieldset>
				<input type="submit" name="create" value="Create Config File" />
			</div></form>';

		$meta['title'] = 'Create Config File';
		$meta['show_menu'] = false;
		$meta['show_nav_links'] = false;
		$meta['head_data'] = '
			<!-- TODO: add some javascript validation here -->
		';

		$hints = array(
			'Make sure your database has been created and the user you are setting has the required permissions to create the tables.',
			'The master prefix allows you to have more than one game set installed at a time.',
			'The game prefix allows you to install more than one iohelix game and use the same player database, this should not be left blank.',
		);
	}

	echo get_header($meta);
	echo get_item($contents, $hints, $meta['title']);
	echo '
	</div>
</body>
</html>';
} // end config file creation


function create_config_file( ) {
debug(__FUNCTION__);
	// grab the sample config file and edit it
	$file = file_get_contents(INCLUDE_DIR.'config.php.sample');

	// fill in the values
	$find = array(
		'$GLOBALS[\'_DEFAULT_DATABASE\'][\'hostname\'] = \'localhost\';',
		'$GLOBALS[\'_DEFAULT_DATABASE\'][\'username\'] = \'username_here\';',
		'$GLOBALS[\'_DEFAULT_DATABASE\'][\'password\'] = \'password_here\';',
		'$GLOBALS[\'_DEFAULT_DATABASE\'][\'database\'] = \'database_here\';',

		'$GLOBALS[\'_ROOT_ADMIN\'] = \'yourname\';',
		'$GLOBALS[\'_ROOT_URI\']   = \'http://www.yoursite.com/webrisk/\';',
		'$GLOBALS[\'_USEEMAIL\']   = true;',

		'$master_prefix = \'\';',
		'$game_prefix   = \'wr_\';',
	);

	$root_uri = trim($_POST['root_uri']);
	if ('/' != $root_uri[strlen($root_uri) - 1]) {
		$root_uri .= '/';
	}

	$replace = array(
		'$GLOBALS[\'_DEFAULT_DATABASE\'][\'hostname\'] = \''.$_POST['db_hostname'].'\';',
		'$GLOBALS[\'_DEFAULT_DATABASE\'][\'username\'] = \''.$_POST['db_username'].'\';',
		'$GLOBALS[\'_DEFAULT_DATABASE\'][\'password\'] = \''.$_POST['db_password'].'\';',
		'$GLOBALS[\'_DEFAULT_DATABASE\'][\'database\'] = \''.$_POST['db_database'].'\';',

		'$GLOBALS[\'_ROOT_ADMIN\'] = \''.$_POST['username'].'\';',
		'$GLOBALS[\'_ROOT_URI\']   = \''.$root_uri.'\';',
		'$GLOBALS[\'_USEEMAIL\']   = '.(is_checked($_POST['use_email']) ? 'true' : 'false').';',

		'$master_prefix = \''.trim($_POST['master_prefix']).'\';',
		'$game_prefix   = \''.trim($_POST['game_prefix']).'\';',
	);

	$file = str_replace($find, $replace, $file);
debug($file);

	$return = file_put_contents(INCLUDE_DIR.'config.php', $file);
debug($return);
debug('FILE CREATED');
}


function create_tables( ) {
	try {
		$Mysql = Mysql::get_instance( );

		// grab the schema file and install it
		$schema = file('full_'.$GLOBALS['_VERSION'].'.sql');

		$buffer = '';
		$end_of_query = false;
		foreach ($schema as $line) {
			if (('' == trim($line)) || ('--' == substr(trim($line), 0, 2))) {
				continue;
			}

			$buffer .= ' '.$line;

			if (';' == substr(trim($line), -1)) {
				$Mysql->query($buffer);
				$buffer = '';
			}
		}
	}
	catch (MySQLException $e) {
		// not sure what to do here
	}
}


function create_admin($data) {
debug(__FUNCTION__);
	try {
		$Mysql = Mysql::get_instance( );
	}
	catch (MySQLException $e) {
		// not sure what to do here
	}
}
