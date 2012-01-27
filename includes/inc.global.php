<?php

$debug = false;

// set some ini stuff
ini_set('register_globals', 0); // you really should have this off anyways

date_default_timezone_set('UTC');

// deal with those lame magic quotes
if (get_magic_quotes_gpc( )) {
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

// MAKE SURE TO LOAD CLASS FILES BEFORE STARTING THE SESSION
// OR YOU END UP WITH INCOMPLETE OBJECTS PULLED FROM SESSION
spl_autoload_register('load_class');


/**
 *		GLOBAL DATA
 * * * * * * * * * * * * * * * * * * * * * * * * * * */

$GLOBALS['_&_DEBUG_QUERY'] = '';
$GLOBALS['_?_DEBUG_QUERY'] = '';

// make a list of all the color files available to use
$GLOBALS['_COLORS'] = array( );

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
}

if ('' == $GLOBALS['_DEFAULT_COLOR']) {
	if (in_array('blue_white', $GLOBALS['_COLORS'])) {
		$GLOBALS['_DEFAULT_COLOR'] = 'blue_white';
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

session_set_cookie_params(0, $path);
session_start( );

// make sure we don't cross site session steal in our own site
if ( ! isset($_SESSION['PWD']) || (__FILE__ != $_SESSION['PWD'])) {
	$_SESSION = array( );
}
$_SESSION['PWD'] = __FILE__;

// set a token, we'll be passing one around a lot
if ( ! isset($_SESSION['token'])) {
	$_SESSION['token'] = md5(uniqid(rand( ), true));
}
call($_SESSION['token']);

if ( ! defined('DEBUG')) {
	if (test_debug( )) {
		define('DEBUG', true); // DO NOT CHANGE THIS ONE
	}
	else {
		define('DEBUG', (bool) $debug); // set to true for output of debugging code
	}
}

$GLOBALS['_LOGGING'] = DEBUG; // do not change, rather, change debug value

if (Mysql::test( )) {
	$Mysql = Mysql::get_instance( );
	$Mysql->set_settings(array(
		'log_path' => LOG_DIR,
		'email_subject' => GAME_NAME.' Query Error',
	));

	if (class_exists('Settings') && Settings::test( )) {
		$Mysql->set_settings(array(
			'log_errors' => Settings::read('DB_error_log'),
			'email_errors' => Settings::read('DB_error_email'),
			'email_from' => Settings::read('from_email'),
			'email_to' => Settings::read('to_email'),
		));
	}
}

if (defined('DEBUG') && DEBUG) {
	ini_set('display_errors','On');
	error_reporting(E_ALL | E_STRICT); // all errors, notices, and strict warnings
	if (isset($Mysql)) {
		$Mysql->set_error(3);
	}
}
else { // do not edit the following
#	ini_set('display_errors','Off');
	error_reporting(E_ALL | E_STRICT);
#	error_reporting(E_ALL & ~ E_NOTICE); // show errors, but not notices
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
}

// grab the list of players
if (isset($Mysql)) {
	$GLOBALS['_PLAYERS'] = Player::get_array( );
}

