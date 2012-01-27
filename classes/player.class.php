<?php
/*
+---------------------------------------------------------------------------
|
|   player.class.php (php 5.x)
|
|   by Benjam Welker
|   http://iohelix.net
|
+---------------------------------------------------------------------------
|
|   > Player module
|   > Date started: 2008-01-13
|
|   > Module Version Number: 0.8.0
|
+---------------------------------------------------------------------------
*/

// TODO: comments & organize better

require_once INCLUDE_DIR.'func.array.php';
require_once INCLUDE_DIR.'func.html.php';

class Player
{

	/**
	 *		PROPERTIES
	 * * * * * * * * * * * * * * * * * * * * * * * * * * */

	/** const property PLAYER_TABLE
	 *		Holds the player table name
	 *
	 * @var string
	 */
	const PLAYER_TABLE = T_PLAYER;


	/** const property LOGIN_PAGE
	 *		Holds the relative URL for the login page
	 *
	 * @var string
	 */
	const LOGIN_PAGE = 'login.php';


	/** protected property id
	 *		Holds the player's id
	 *
	 * @var int
	 */
	protected $id;


	/** protected property username
	 *		Holds the player's username
	 *
	 * @var string
	 */
	protected $username;


	/** protected property firstname
	 *		Holds the player's firstname
	 *
	 * @var string
	 */
	protected $firstname;


	/** protected property lastname
	 *		Holds the player's lastname
	 *
	 * @var string
	 */
	protected $lastname;


	/** protected property email
	 *		Holds the player's email address
	 *
	 * @var string
	 */
	protected $email;


	/** protected property timezone
	 *		Holds the player's timezone info
	 *
	 * @var string
	 */
	protected $timezone;


	/** protected property is_admin
	 *		Holds the player's admin state
	 *
	 * @var bool
	 */
	protected $is_admin;


	/** protected property is_logged
	 *		Holds the player's logged state
	 *
	 * @var bool
	 */
	protected $is_logged;


	/** protected property _mysql
	 *		Stores a reference to the Mysql class object
	 *
	 * @param Mysql object
	 */
	protected $_mysql;


	/** protected property _DEBUG
	 *		Holds the DEBUG state for the class
	 *
	 * @var bool
	 */
	protected $_DEBUG = false;


	/** private property _ident
	 *		Holds the player's ident hash
	 *
	 * @var string
	 */
	private $_ident;


	/** private property _token
	 *		Holds the player's token hash
	 *
	 * @var string
	 */
	private $_token;



	/**
	 *		METHODS
	 * * * * * * * * * * * * * * * * * * * * * * * * * * */

	/** public function __construct
	 *		Class constructor
	 *		Sets all outside data
	 *
	 * @param int optional player id
	 * @action instantiates object
	 * @return void
	 */
	public function __construct($id = null)
	{
		$this->_mysql = Mysql::get_instance( );

		if (defined('DEBUG')) {
			$this->_DEBUG = DEBUG;
		}

		if ( ! empty($id)) {
			$this->id = (int) $id;
			$this->_pull( );
		}
	}


	/** public function __get
	 *		Class getter
	 *		Returns the requested property if the
	 *		requested property is not _private
	 *
	 * @param string property name
	 * @return mixed property value
	 */
	public function __get($property)
	{
		if ( ! property_exists($this, $property)) {
			throw new MyException(__METHOD__.': Trying to access non-existent property ('.$property.')', 2);
		}

		if ('_' === $property[0]) {
			throw new MyException(__METHOD__.': Trying to access _private property ('.$property.')', 2);
		}

		return $this->$property;
	}


	/** public function __set
	 *		Class setter
	 *		Sets the requested property if the
	 *		requested property is not _private
	 *
	 * @param string property name
	 * @param mixed property value
	 * @action optional validation
	 * @return bool success
	 */
	public function __set($property, $value)
	{
		if ( ! property_exists($this, $property)) {
			throw new MyException(__METHOD__.': Trying to access non-existent property ('.$property.')', 3);
		}

		if ('_' === $property[0]) {
			throw new MyException(__METHOD__.': Trying to access _private property ('.$property.')', 3);
		}

		$this->$property = $value;
	}


	/** public function log_in
	 *		Check the various methods for logging
	 *		a player in, and if any are successful
	 *		logs the player in with the relevant data
	 *
	 * @param void
	 * @action logs the player in
	 * @return bool success
	 */
	public function log_in( )
	{
		call(__METHOD__);

		// store our current location so we can redirect later
		if (empty($_SESSION['login_referrer'])
			&& (false === strpos($_SERVER['REQUEST_URI'], self::LOGIN_PAGE)) // don't redirect to the login page
			&& (false === strpos($_SERVER['REQUEST_URI'], 'index.php')) // don't redirect to the index page
			&& (false !== strpos($_SERVER['REQUEST_URI'], '.php'))) // the index page may also be like /game_name/
		{
			$_SESSION['login_referrer'] = $_SERVER['REQUEST_URI'];
		}

		try {
			$redirect = false;

			if ($this->_get_session( )) {
				call('SESSION LOGIN');
				$this->is_logged = true;
			}
			elseif ($this->_get_cookie( )) {
				call('COOKIE LOGIN');
				$this->is_logged = true;
				$this->_set_cookie( );
			}
			elseif ($this->_get_login( )) {
				call('REGULAR LOGIN');
				$this->is_logged = true;
				$redirect = true;

				if (isset($_POST['remember']) && is_checked($_POST['remember'])) {
					$this->_set_cookie( );
				}
			}

			if ($this->is_logged) {
				$GLOBALS['Player'] = $this;
				$this->_admin_log_in( );
				$_SESSION['player_id'] = (int) $this->id;

				if ($redirect) {
					if ( ! empty($_SESSION['login_referrer'])) {
						call($_SESSION['login_referrer']);
						$login_referrer = $_SESSION['login_referrer'];
						session_write_close( );
						header('Location: '.$login_referrer);
						exit;
					}
				}
				unset($_SESSION['login_referrer']);

				return true;
			}
		}
		catch (MyException $e) {
			// do nothing, it gets handled below
		}

		// if we made it here, then there must have been something wrong
		// even if there was no exception
		$this->log_out(isset($_POST['password'])); // just to be sure
		return false;
	}


	/** public function log_out
	 *		Logs the player out of the system
	 *		and deletes all the data, such as session vars,
	 *		cookies, objects, etc.
	 *
	 * @param bool optional login attempt flag
	 * @param bool optional coming from login page flag
	 * @action logs the player out, redirects, and exits
	 * @return void
	 */
	public function log_out($login_attempt = false, $login_page = false)
	{
		call(__METHOD__);

		$this->is_logged = false;
		$this->_delete_cookie( );

		// clear player session data, but...
		// keep the items that we need
		$kill = array(
			'player_id',
			'PID',
			'admin_id',
		);

		foreach (array_keys($_SESSION) as $key) {
			if (in_array($key, $kill)) {
				$_SESSION[$key] = false;
				$_SESSION[$key] = null;
				unset($_SESSION[$key]);
			}
		}

		if ($login_attempt) {
			Flash::store('Login FAILED !', false);
		}

		if ( ! $login_page) {
			if ( ! $this->_DEBUG) {
				session_write_close( );
				header('Location: '.self::LOGIN_PAGE.$GLOBALS['_?_DEBUG_QUERY']);
			}
			else {
				call('PLAYER CLASS REDIRECTED TO LOGIN AND QUIT');
			}

			exit;
		}
	}


	/** public function register
	 *		Registers a new player in the system
	 *
	 * @param void
	 * @action creates a new player in the database
	 * @return bool success
	 */
	public function register( )
	{
		call(__METHOD__);

		$required = array(
			'username' ,
			'email' ,
		);

		$key_list = array_merge($required, array(
			'first_name' ,
			'last_name' ,
			'timezone' ,
		));

		if ($_DATA = array_clean($_POST, $key_list, $required)) {
			// remove any html
			foreach ($_DATA as & $data) {
				$data = preg_replace('/<.+?>/', '', $data);
			}
			unset($data); // kill the reference

			// first, make sure this player is not already in the system
			try {
				self::check_database($_DATA['username'], $_DATA['email']);
			}
			catch (MyException $e) {
				// the data given is already in the database
				throw $e;
			}

			// fill the password fields with a temp password
			$_DATA['password'] = 'temp';
			$_DATA['alt_pass'] = 'temp';

			// fill the ident with a random string
			$_DATA['ident'] = md5(uniqid(mt_rand( ), true));

			$_DATA['is_approved'] = (int) ! (bool) (int) Settings::read('approve_users');

			// now check the password
			if (empty($_POST['password'])) {
				throw new MyException(__METHOD__.': No password given');
			}

			$this->id = $this->_mysql->insert(self::PLAYER_TABLE, $_DATA);

			if ($this->id) {
				if ((bool) (int) Settings::read('approve_users')) {
					Email::send('register', explode(',', Settings::read('to_email')), array_merge(array('id' => $this->id), $_DATA));
				}

				return $this->_set_password($_POST['password']);
			}
		}

		// if something went wrong with the array_clean function
		// such as missing required data...
		throw new MyException(__METHOD__.': Unable to register player');
	}


	/** public function update
	 *		Updates player info in the system
	 *
	 * @param void
	 * @action updates player in the database
	 * @return bool success
	 */
	public function update( )
	{
		call(__METHOD__);

		$required = array(
			'email' ,
		);

		$key_list = array_merge($required, array(
			'first_name' ,
			'last_name' ,
			'timezone' ,
		));

		if ($_DATA = array_clean($_POST, $key_list, $required)) {
			// remove any html
			foreach ($_DATA as & $data) {
				$data = preg_replace('/<.+?>/', '', $data);
			}
			unset($data); // kill the reference

			// first, make sure this player is not already in the system
			try {
				self::check_database('', $_DATA['email'], $this->id);
			}
			catch (MyException $e) {
				// the data given is already in the database
				throw $e;
			}

			$where = " WHERE player_id = '{$this->id}' ";

			$this->_mysql->insert(self::PLAYER_TABLE, $_DATA, $where);

			if ( ! empty($_POST['password'])) {
				try {
					return $this->update_password($_POST['curpass'], $_POST['password']);
				}
				catch (MyException $e) {
					throw $e;
				}
			}
			else {
				$this->_pull( );
				return true;
			}
		}

		// if something went wrong with the array_clean function
		// such as missing required data...
		throw new MyException(__METHOD__.': Unable to update player');
	}


	/** public function confirm
	 *		Validates the players email address via email confirmation
	 *
	 * @param string email confirmation token
	 * @action confirms the players email address
	 * @return bool success
	 */
	public function confirm($token) {
		// TODO: build this function
		return true;
	}


	/** public function admin_approve
	 *		Approves the given players registrations
	 *
	 * @param mixed csv or array of player ids
	 * @action approves the players registration
	 * @return void
	 */
	public function admin_approve($player_ids)
	{
		// make sure the user doing this is an admin
		if ( ! $this->is_admin) {
			throw new MyException(__METHOD__.': Player is not an admin');
		}

		array_trim($player_ids, 'int');
		$player_ids[] = 0;
		$player_ids = implode(',', $player_ids);

		$this->_mysql->insert(self::PLAYER_TABLE, array('is_approved' => 1), " WHERE player_id IN ({$player_ids}) ");

		Email::send('approved', $player_ids);
	}


	/** public function admin_delete
	 *		Deletes the given players from the players database
	 *
	 * @param mixed csv or array of player ids
	 * @action deletes the players from the database
	 * @return void
	 */
	public function admin_delete($player_ids)
	{
		call(__METHOD__);

		// make sure the user doing this is an admin
		if ( ! $this->is_admin) {
			throw new MyException(__METHOD__.': Player is not an admin');
		}

		array_trim($player_ids, 'int');

		$player_ids = self::clean_deleted($player_ids);

		if ( ! $player_ids) {
			throw new MyException(__METHOD__.': No player IDs given');
		}

		$this->_mysql->delete(self::PLAYER_TABLE, " WHERE player_id IN ({$player_ids}) ");
	}


	/** public function admin_reset_pass
	 *		Reset the password for the given players
	 *
	 * @param mixed csv or array of player ids
	 * @action resets the password for the given players
	 * @return void
	 */
	public function admin_reset_pass($player_ids)
	{
		// make sure the user doing this is an admin
		if ( ! $this->is_admin) {
			throw new MyException(__METHOD__.': Player is not an admin');
		}

		array_trim($player_ids, 'int');

		$data = array(
			'password' => self::hash_password(Settings::read('default_pass')),
			'alt_pass' => self::hash_alt_pass(Settings::read('default_pass')),
		);
		$this->_mysql->insert(self::PLAYER_TABLE, $data, " WHERE player_id IN (0,".implode(',', $player_ids).") ");
	}


	/** public function save
	 *		Stores the current state of the player
	 *		in the database
	 *
	 * @param void
	 * @action stores current player data in the database
	 * @return void
	 */
	public function save( )
	{
		$data = array( );
		$data['username'] = $this->username;
		$data['first_name'] = $this->firstname;
		$data['last_name'] = $this->lastname;
		$data['email'] = $this->email;
		$data['timezone'] = $this->timezone;
		$data['is_admin'] = ($this->is_admin) ? 1 : 0;

		$where = " WHERE player_id = '{$this->id}' ";

		$this->_mysql->insert(self::PLAYER_TABLE, $data, $where);
	}


	/** public function update_password
	 *		Updates the player's password in the database
	 *
	 * @param string old password
	 * @param string new password
	 * @action checks current password for validity
	 * @action stores new password hashes
	 * @return bool success
	 */
	public function update_password($old_password, $new_password)
	{
		// make sure we are logged in
		if ( ! $this->is_logged) {
			throw new MyException(__METHOD__.': Unlogged player trying to change password');
		}

		// check the players current password, and make sure they match
		$query = "
			SELECT password
				, alt_pass
			FROM ".self::PLAYER_TABLE."
			WHERE player_id = '{$this->id}'
		";
		$result = $this->_mysql->fetch_row($query);

		if ( ! $result) {
			throw new MyException(__METHOD__.': Old password does not match');
		}

		list($password, $alt_pass) = $result;

		if ((0 == strcmp(self::hash_password($old_password), $password))
			&& (0 == strcmp(self::hash_alt_pass($old_password), $alt_pass)))
		{
			return $this->_set_password($new_password);
		}
	}


	/** protected function _set_password
	 *		Sets the player's password in the database
	 *
	 * @param string password
	 * @action stores new password hashes
	 * @return bool success
	 */
	protected function _set_password($password)
	{
		$data = array(
			'password' => self::hash_password($password),
			'alt_pass' => self::hash_alt_pass($password),
		);
		return $this->_mysql->insert(self::PLAYER_TABLE, $data, " WHERE player_id = '{$this->id}' ");
	}


	/** protected function _get_session
	 *		Checks the session var for a stored copy of
	 *		the player object and loads that player if found
	 *
	 * @param void
	 * @action checks session var for data
	 * @action loads player if valid
	 * @return bool success
	 */
	protected function _get_session( )
	{
		call(__METHOD__);

		if (isset($_SESSION['player_id']) && is_int($_SESSION['player_id'])) {
			$this->id = $_SESSION['player_id'];
			$this->_pull( );
			return true;
		}

		// session value not found
		return false;
	}


	/** protected function _get_login
	 *		Checks the post var for a login attempt
	 *		and loads that player if found and valid
	 *
	 * @param void
	 * @action checks post var for data
	 * @action loads player if valid
	 * @return bool success
	 */
	protected function _get_login( )
	{
		call(__METHOD__);
		call($_POST);

		if (isset($_POST['login'])) {
			// check for a player with supplied username and password
			$query = "
				SELECT player_id
					, password
					, alt_pass
					, is_approved
				FROM ".self::PLAYER_TABLE."
				WHERE username = '".sani($_POST['username'])."'
			";
			$result = $this->_mysql->fetch_row($query);

			if ($result) {
				list($id, $password, $alt_pass, $is_approved) = $result;

				call($result);
				call(self::hash_password($_POST['password']));
				call(self::hash_alt_pass($_POST['password']));
				call($is_approved);

				if ( ! $is_approved) {
					return false;
				}

				if ((0 == strcmp(self::hash_password($_POST['password']), $password))
					&& (0 == strcmp(self::hash_alt_pass($_POST['password']), $alt_pass)))
				{
					$this->id = (int) $id;
					$this->_pull( );
					return true;
				}
			}
		}

		// login data incorrect or missing
		return false;
	}


	/** protected function _get_cookie
	 *		Checks the cookie var for stored data
	 *		and loads that player if found and valid
	 *
	 * @param void
	 * @action checks cookie var for data
	 * @action loads player if valid
	 * @return bool success
	 */
	protected function _get_cookie( )
	{
		call(__METHOD__);

		$this->_log('COOKIE GET: '.var_export($_COOKIE, true));

		if (isset($_COOKIE['ioGameData']) && ('DELETED!' != $_COOKIE['ioGameData']) && ! isset($_POST['login'])) {
			$data  = base64_decode($_COOKIE['ioGameData']);
			$token = substr($data, 0, 32);
			$ident = substr($data, 32);
			$this->_log('COOKIE TOKEN / IDENT: '.$token.' - '.$ident);
			$query = "
				SELECT player_id
					, token
				FROM ".self::PLAYER_TABLE."
				WHERE ident = '{$ident}'
			";

			$player = $this->_mysql->fetch_assoc($query);

			if ($player) {
				$this->_log('PLAYER GET: ('.$player['player_id'].')- '.$player['token'].' : '.$token);

				if (0 == strcmp($player['token'], $token)) {
					$this->id = (int) $player['player_id'];
					$this->_pull( );
					return true;
				}
			}
		}

		$this->_log('----- COOKIE FAILED -----');

		// cookie data not found
		return false;
	}


	/** protected function _set_cookie
	 *		Stores the players tokens in a cookie for later use
	 *
	 * @param void
	 * @action stores a cookie
	 * @return void
	 */
	protected function _set_cookie( )
	{
		// regenerate the security info
		session_regenerate_id(true);
		$this->_token = md5(uniqid(mt_rand( ), true));

		// save the new token to the database
		$this->_mysql->insert(self::PLAYER_TABLE, array('token' => $this->_token), " WHERE player_id = '{$this->id}' ");

		// submit the new cookie
		$data = base64_encode($this->_token . $this->_ident);
		setcookie('ioGameData', $data, time( ) + (60 * 60 * 24 * 7), '/'); // expires in 7 days
		$this->_log('COOKIE SET: '.$data);
	}


	/** protected function _delete_cookie
	 *		Deletes any cookies on this players machine
	 *
	 * @param void
	 * @action deletes the cookie
	 * @return void
	 */
	protected function _delete_cookie( )
	{
		setcookie('ioGameData', 'DELETED!', time( ) - 3600, '/'); // delete the cookie
		$this->_log('COOKIE DELETED');
	}


	/** protected function _admin_log_in
	 *		Log an admin in as a specific player
	 *
	 * @param void
	 * @action logs the admin in as a player
	 * @return bool success
	 */
	protected function _admin_log_in( )
	{
		call(__METHOD__);

		if ( ! isset($_GET['PID']) && ! isset($_SESSION['PID'])) {
			return false;
		}

		// refresh our original data, if we have it
		// this basically sets us back to square one, and if we don't pass
		// any of the following tests, we're not broken on the way out
		if (isset($_SESSION['admin_id']) && is_int($_SESSION['admin_id'])) {
			$this->id = $_SESSION['admin_id'];
			$this->_pull( );
			$this->is_logged = true;
		}

		// store the player id in session, so we don't
		// have to keep setting our query string
		if (isset($_GET['PID'])) {
			$_SESSION['PID'] = (int) $_GET['PID'];
		}

		// if the id passed is 0 or our own id, reset all, and go back to normal
		if ((0 == $_SESSION['PID']) || ($this->id == $_SESSION['PID'])) {
			unset($_SESSION['admin_id']);
			unset($_SESSION['PID']);
			return false;
		}

		if ( ! $this->is_admin) {
			unset($_SESSION['admin_id']);
			unset($_SESSION['PID']);
			throw new MyException(__METHOD__.': Non-admin (#'.$_SESSION['player_id'].') trying to log in as another player (#'.$_GET['PID'].')');
		}

		// store our admin id
		$_SESSION['admin_id'] = (int) $this->id;

		// and just grab ourselves new
		$this->id = $_SESSION['PID'];
		$this->_pull( );
		$this->is_logged = true;

		return true;
	}


	/** protected function _pull
	 *		Pulls all player data from the database
	 *
	 * @param void
	 * @action pulls the player data
	 * @return void
	 */
	protected function _pull( )
	{
		$query = "
			SELECT *
			FROM ".self::PLAYER_TABLE."
			WHERE player_id = '{$this->id}'
		";
		$result = $this->_mysql->fetch_assoc($query);

		if ($result) {
			$this->username = (string) $result['username'];
			$this->firstname = (string) $result['first_name'];
			$this->lastname = (string) $result['last_name'];
			$this->email = (string) $result['email'];
			$this->timezone = (string) $result['timezone'];
			$this->is_admin = (bool) $result['is_admin'];
			$this->_ident = (string) $result['ident'];
		}
	}


	/** protected function _log
	 *		Report messages to a file
	 *
	 * @param string message
	 * @action log messages to file
	 * @return void
	 */
	protected function _log($msg)
	{
		// log the error
		if (false && class_exists('Log')) {
			Log::write($msg, __CLASS__);
		}
	}



	/**
	 *		STATIC METHODS
	 * * * * * * * * * * * * * * * * * * * * * * * * * * */

	/** static public function check_database
	 *		Checks the database for the given username
	 *		and email to make sure they have not been used before
	 *
	 * @param string requested username
	 * @param string requested email
	 * @param int optional player id to exclude from search (ourselves)
	 * @action checks the database for existing data
	 * @return string state message
	 */
	static public function check_database($username, $email, $player_id = 0)
	{
		$Mysql = Mysql::get_instance( );

		// make sure our query is clean
		$username = sani($username);
		$email = sani($email);
		$player_id = (int) $player_id;

		$query = "
			SELECT COUNT(*)
			FROM ".self::PLAYER_TABLE."
			WHERE username = '{$username}'
		";
		$result = $Mysql->fetch_value($query);

		if ($result) {
			throw new MyException(__METHOD__.': The username ('.$username.') is taken', 301);
		}

		if ('' != $email) {
			// make sure it's a valid email address
			if ( ! preg_match('/^[A-Z0-9._%+-]+@(?:[A-Z0-9-]+\.)+[A-Z]{2,6}$/i', $email)) {
				throw new MyException(__METHOD__.': The email address ('.$username.') is not a valid email address', 303);
			}

			$query = "
				SELECT COUNT(*)
				FROM ".self::PLAYER_TABLE."
				WHERE email = '{$email}'
					AND player_id <> '{$player_id}'
			";
			$result = $Mysql->fetch_value($query);

			if ($result) {
				throw new MyException(__METHOD__.': The email address ('.$email.') has already been used', 302);
			}
		}
	}


	/** static public function hash_password
	 *		Hashes the given password
	 *
	 * @param string password
	 * @return string password hash
	 */
	static public function hash_password($password)
	{
		return md5($password.'NUTTY!SALT');
	}


	/** static public function hash_alt_pass
	 *		Hashes an alternate of the given password
	 *		to avoid hash collisions
	 *
	 * @param string password
	 * @return string alternate password hash
	 */
	static public function hash_alt_pass($password)
	{
		return md5(str_rot13($password).substr(md5(md5(strrev($password)).md5($password)), 10, 32).'SALTY!NUTS');
	}


	/** static public function get_list
	 *		Returns a list array of all players in the database
	 *
	 * @param bool restrict to approved players
	 * @return array player list (or bool false on failure)
	 */
	static public function get_list($only_approved = false)
	{
		$Mysql = Mysql::get_instance( );

		$WHERE = ($only_approved) ? " WHERE is_approved = 1 " : '';

		$query = "
			SELECT *
			FROM ".self::PLAYER_TABLE."
			{$WHERE}
			ORDER BY player_id
		";
		$list = $Mysql->fetch_array($query);

		return $list;
	}


	/** static public function get_username
	 *		Returns the username for the given player id
	 *
	 * @param int player id
	 * @return string player username
	 */
	static public function get_username($player_id)
	{
		$player_id = (int) $player_id;

		$Mysql = Mysql::get_instance( );

		$query = "
			SELECT username
			FROM ".self::PLAYER_TABLE."
			WHERE player_id = '{$player_id}'
		";
		$username = $Mysql->fetch_value($query);

		return $username;
	}


	/** static public function get_array
	 *		Returns an array of player names with ids as keys
	 *
	 * @param void
	 * @return array player usernames
	 */
	static public function get_array( )
	{
		$players = Player::get_list( );

		$array = array(0 => 'The Nothing');
		foreach ((array) $players as $player) {
			$array[$player['player_id']] = $player['username'];
		}

		return $array;
	}


	/** static public function clean_deleted
	 *		Cleans out any ids that shouldn't be deleted
	 *
	 * @param array of int player ids
	 * @return array of int valid player ids
	 */
	static public function clean_deleted($player_ids)
	{
		call(__METHOD__);

		$Mysql = Mysql::get_instance( );

		array_trim($player_ids, 'int');

		if (isset($GLOBALS['_ROOT_ADMIN'])) {
			$query = "
				SELECT player_id
				FROM ".self::PLAYER_TABLE."
				WHERE username = '{$GLOBALS['_ROOT_ADMIN']}'
			";
			$root_admin = (int) $Mysql->fetch_value($query);

			if (in_array($root_admin, $player_ids)) {
				unset($player_ids[array_search($root_admin, $player_ids)]);
			}
		}

		// remove the player doing the deleting
		unset($player_ids[array_search($_SESSION['player_id'], $player_ids)]);

		// remove the admin doing the deleting
		unset($player_ids[array_search($_SESSION['admin_id'], $player_ids)]);

		return $player_ids;
	}

} // end of Player class


/*		schemas
// ===================================

--
-- Table structure for table `player`
--

DROP TABLE IF EXISTS `player`;
CREATE TABLE IF NOT EXISTS `player` (
  `player_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `username` varchar(20) NOT NULL DEFAULT '',
  `first_name` varchar(20) DEFAULT NULL,
  `last_name` varchar(20) DEFAULT NULL,
  `email` varchar(100) NOT NULL DEFAULT '',
  `timezone` varchar(255) NOT NULL DEFAULT '',
  `is_admin` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `password` varchar(32) NOT NULL DEFAULT '',
  `alt_pass` varchar(32) NOT NULL DEFAULT '',
  `ident` varchar(32) DEFAULT NULL,
  `token` varchar(32) DEFAULT NULL,
  `create_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `is_approved` tinyint(1) unsigned NOT NULL DEFAULT '0',

  PRIMARY KEY (`player_id`),
  UNIQUE KEY `username` (`username`),
  UNIQUE KEY `email` (`email`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci ;

*/

