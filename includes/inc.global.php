<?php

$debug = false;

// set the base system time to UTC
// and only change the timezone for the user
// when displaying dates using ldate( )
date_default_timezone_set('UTC');

// set some ini stuff
ini_set('register_globals', 0); // you really should have this off anyways

// deal with those lame magic quotes
if (version_compare(PHP_VERSION, '7.4.0') < 0) {
	if (get_magic_quotes_gpc()) {
		function stripslashes_deep($value) {
			$value = is_array($value)
				? array_map('stripslashes_deep', $value)
				: stripslashes($value);

			return $value;
		}

		$_POST = array_map('stripslashes_deep', $_POST);
		$_GET = array_map('stripslashes_deep', $_GET);
		$_COOKIE = array_map('stripslashes_deep', $_COOKIE);
		$_REQUEST = array_map('stripslashes_deep', $_REQUEST);
	}
}


/**
 *		GLOBAL INCLUDES
 * * * * * * * * * * * * * * * * * * * * * * * * * * */

define('ROOT_DIR', dirname(dirname(__FILE__)).DIRECTORY_SEPARATOR);
define('INCLUDE_DIR', dirname(__FILE__).DIRECTORY_SEPARATOR);
define('CLASSES_DIR', ROOT_DIR.'classes'.DIRECTORY_SEPARATOR);
define('GAMES_DIR', ROOT_DIR.'games'.DIRECTORY_SEPARATOR);
define('LOG_DIR', ROOT_DIR.'logs'.DIRECTORY_SEPARATOR);

ini_set('error_log', LOG_DIR.'php.err');

if (is_file(INCLUDE_DIR.'config.php')) {
	require_once INCLUDE_DIR.'config.php';
}
#/*/
#elseif ('setup-config.php' != basename($_SERVER['PHP_SELF'])) {
#	header('Location: setup-config.php');
#/*/
#elseif ('install.php' != basename($_SERVER['PHP_SELF'])) {
#	header('Location: install.php');
#//*/
#	exit;
#}

require_once INCLUDE_DIR.'inc.settings.php';
require_once INCLUDE_DIR.'func.global.php';
require_once INCLUDE_DIR.'html.general.php';
require_once INCLUDE_DIR.'html.tables.php';
require_once INCLUDE_DIR.'html.risk.php';

// MAKE SURE TO LOAD CLASS FILES BEFORE STARTING THE SESSION
// OR YOU END UP WITH INCOMPLETE OBJECTS PULLED FROM SESSION
spl_autoload_register('load_class');

// store the default timezone
$GLOBALS['_TZ'] = $GLOBALS['_DEFAULT_TIMEZONE'];


/**
 *		GLOBAL DATA
 * * * * * * * * * * * * * * * * * * * * * * * * * * */

$Mysql = Mysql::get_instance( );
$Mysql->set_settings([
	'log_path' => LOG_DIR,
	'email_subject' => GAME_NAME.' Query Error',
]);

$GLOBALS['_&_DEBUG_QUERY'] = '';
$GLOBALS['_?_DEBUG_QUERY'] = '';

// make a list of all the color files available to use
$GLOBALS['_COLORS'] = [];

$dh = opendir(realpath(dirname(__FILE__).'/../css'));
while (false !== ($file = readdir($dh))) {
	if (preg_match('/^c_(.+)\\.css$/i', $file, $match)) { // scanning for color files only
		$GLOBALS['_COLORS'][] = $match[1];
	}
}

// convert the full color file name to just the color portion
$GLOBALS['_DEFAULT_COLOR'] = '';
if (class_exists('Settings') && Settings::test( )) {
	$GLOBALS['_DEFAULT_COLOR'] = preg_replace('/c_(.+)\\.css/i', '$1', Settings::read('default_color'));

	if (false != Settings::read('timezone')) {
		$GLOBALS['_TZ'] = Settings::read('timezone');
	}
}

if ('' == $GLOBALS['_DEFAULT_COLOR']) {
	if (in_array('yellow_black', $GLOBALS['_COLORS'])) {
		$GLOBALS['_DEFAULT_COLOR'] = 'yellow_black';
	}
	elseif ($GLOBALS['_COLORS']) {
		$GLOBALS['_DEFAULT_COLOR'] = $GLOBALS['_COLORS'][0];
	}
	else {
		$GLOBALS['_DEFAULT_COLOR'] = '';
	}
}

// set the session cookie parameters so the cookie is only valid for this game
$parts = pathinfo($_SERVER['REQUEST_URI']);

$path = $parts['dirname'];
if (empty($parts['extension'])) {
	$path .= $parts['basename'];
}
$path = str_replace('\\', '/', $path).'/';
$path = str_replace('//', '/', $path);

session_set_cookie_params(0, $path);
session_start( );

// make sure we don't cross site session steal in our own site
if ( ! isset($_SESSION['PWD']) || (__FILE__ != $_SESSION['PWD'])) {
	$_SESSION = [];
}
$_SESSION['PWD'] = __FILE__;

// set a token, we'll be passing one around a lot
if ( ! isset($_SESSION['token'])) {
	$_SESSION['token'] = md5(uniqid(rand( ), true));
}

if ( ! defined('DEBUG')) {
	if (test_debug( )) {
		define('DEBUG', true); // DO NOT CHANGE THIS ONE
	}
	else {
		define('DEBUG', (bool) $debug); // set to true for output of debugging code
	}

	if (DEBUG) {
		if (isset($_GET['DEBUG'])) {
			$GLOBALS['_&_DEBUG_QUERY'] = '&DEBUG='.$_GET['DEBUG'];
			$GLOBALS['_?_DEBUG_QUERY'] = '?DEBUG='.$_GET['DEBUG'];
		}
		else {
			$GLOBALS['_&_DEBUG_QUERY'] = '';
			$GLOBALS['_?_DEBUG_QUERY'] = '?z';
		}
	}
}

$GLOBALS['_LOGGING'] = DEBUG; // do not change, rather, change debug value

if (class_exists('Settings') && Settings::test( )) {
	$Mysql->set_settings([
		'log_errors' => Settings::read('DB_error_log'),
		'email_errors' => Settings::read('DB_error_email'),
		'email_from' => Settings::read('from_email'),
		'email_to' => Settings::read('to_email'),
	]);
}

if (defined('DEBUG') && DEBUG) {
	ini_set('display_errors','On');
	error_reporting(-1); // everything
	if (isset($Mysql)) {
		$Mysql->set_error(3);
	}
}

// log the player in
if (( ! defined('LOGIN') || LOGIN) && isset($Mysql)) {
	$GLOBALS['Player'] = new GamePlayer( );
	// this will redirect to login if failed
	$GLOBALS['Player']->log_in( );

	if (0 != $_SESSION['player_id']) {
		$Message = new Message($_SESSION['player_id'], $GLOBALS['Player']->is_admin);
	}

	// set the default color for the player
	if (('' != $GLOBALS['Player']->color) && (in_array($GLOBALS['Player']->color, $GLOBALS['_COLORS']))) {
		$GLOBALS['_DEFAULT_COLOR'] = $GLOBALS['Player']->color;
	}

	// set the default timezone for the player
	if (false != $GLOBALS['Player']->timezone) {
		$GLOBALS['_TZ'] = $GLOBALS['Player']->timezone;
	}
}

// grab the list of players
if (isset($Mysql)) {
	$GLOBALS['_PLAYERS'] = Player::get_array( );
}

// test for microseconds
if ( ! defined('SUPPORTS_MICROSECONDS') && isset($Mysql)) {
    define('SUPPORTS_MICROSECONDS', $Mysql->support_microseconds( ));
}
