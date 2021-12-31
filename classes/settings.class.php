<?php
/*
+---------------------------------------------------------------------------
|
|   settings.class.php (php 5.x)
|
|   by Benjam Welker
|   http://iohelix.net
|
+---------------------------------------------------------------------------
|
|   > Settings module
|   > Date started: 2009-04-15
|
|   > Module Version Number: 0.8.0
|
+---------------------------------------------------------------------------
*/


class Settings
{

	/**
	 *		PROPERTIES
	 * * * * * * * * * * * * * * * * * * * * * * * * * * */

	/** const property SETTINGS_TABLE
	 *		Holds the settings table name
	 *
	 * @var string
	 */
	const SETTINGS_TABLE = T_SETTINGS;


	/** protected property _settings
	 *		Stores the site settings in an
	 *		associative array
	 *
	 * @param array
	 */
	protected $_settings = [];


	/** protected property _defaults
	 * 		The default settings in case
	 * 		any are missing from the DB
	 *		It's very restrictive by design
	 *
	 * @var array
	 */
	protected $_defaults = [
		'site_name' => 'This Website',
		'default_color' => 'c_yellow_black.css',
		'nav_links' => '',
		'from_email' => '',
		'to_email' => '',
		'new_users' => 0,
		'approve_users' => 1,
		'confirm_email' => 1,
		'max_users' => 0,
		'default_pass' => 'change!me',
		'expire_users' => 0,
		'save_games' => 1,
		'expire_finished_games' => 0,
		'expire_games' => 0,
		'nudge_flood_control' => 24,
		'timezone' => 'UTC',
		'long_date' => 'M j, Y g:i a',
		'short_date' => 'Y-m-d H:i:s',
		'debug_pass' => '',
		'DB_error_log' => 0,
		'DB_error_email' => 0,
	];


	/** protected property _notes
	 *		Stores the site settings notes in an
	 *		associative array
	 *
	 * @param array
	 */
	protected $_notes = [];


	/** protected property _delete_missing
	 *		Deletes missing settings from the database
	 *		when saving settings
	 *
	 * @param bool
	 */
	protected $_delete_missing = false;


	/** protected property _save_new
	 *		Saves new (previously unsaved) settings
	 *		to the database when saving settings
	 *
	 * @param bool
	 */
	protected $_save_new = false;


	/** static private property _instance
	 *		Holds the instance of this object
	 *
	 * @var Settings object
	 */
	static private $_instance;


	/** protected property _mysql
	 *		Stores a reference to the Mysql class object
	 *
	 * @param Mysql object
	 */
	protected $_mysql;



	/**
	 *		METHODS
	 * * * * * * * * * * * * * * * * * * * * * * * * * * */

	/** protected function __construct
	 *		Class constructor
	 *
	 * @param void
	 * @action instantiates object
	 * @action pulls settings from settings table
	 * @return Settings object
	 */
	protected function __construct( )
	{
		$this->_mysql = Mysql::get_instance( );

		$this->_settings = $this->_defaults;

		if ($this->test( )) {
			$this->_pull( );
		}
	}


	/** public function __get
	 *		Class getter
	 *		Returns the requested property if the
	 *		requested property is not _private
	 *
	 * @param string $property name
	 *
	 * @return mixed property value
	 * @throws MyException
	 */
	public function __get($property)
	{
		if ( ! isset($this->_settings[$property]) && ! property_exists($this, $property)) {
			throw new MyException(__METHOD__.': Trying to access non-existent property ('.$property.')', 2);
		}

		if ('_' === $property[0]) {
			throw new MyException(__METHOD__.': Trying to access _private property ('.$property.')', 2);
		}

		if (isset($this->_settings[$property])) {
			return $this->_settings[$property];
		}
		else {
			return $this->$property;
		}
	}


	/** public function __set
	 *		Class setter
	 *		Sets the requested property if the
	 *		requested property is not _private
	 *
	 * @param string $property name
	 * @param mixed $value
	 *
	 * @action optional validation
	 *
	 * @return void
	 * @throws MyException
	 */
	public function __set($property, $value)
	{
		if ('_' === $property[0]) {
			throw new MyException(__METHOD__.': Trying to access _private property ('.$property.')', 3);
		}

		if (property_exists($this, $property)) {
			$this->$property = $value;
		}
		else {
			$this->_settings[$property] = $value;
		}
	}


	/** protected function _pull
	 *		Pulls all settings data from the database
	 *
	 * @param void
	 * @action pulls the settings data
	 * @return void
	 */
	protected function _pull( )
	{
		$query = "
			SELECT *
			FROM `".self::SETTINGS_TABLE."`
			ORDER BY `sort`
		";
		$results = $this->_mysql->fetch_array($query);

		if ( ! $results) {
			die('Settings were not pulled properly');
		}

		foreach ((array) $results as $result) {
			$this->_settings[$result['setting']] = $result['value'];
			$this->_notes[$result['setting']] = $result['notes'];
		}
	}


	/** public function save
	 *		Saves all settings data to the database
	 *		if the settings are different
	 *
	 * @param void
	 * @action saves the settings data
	 * @return void
	 */
	public function save( )
	{
		call(__METHOD__);

		$query = "
			SELECT *
			FROM `".self::SETTINGS_TABLE."`
			ORDER BY `sort`
		";
		$results = $this->_mysql->fetch_array($query);

		$data = [];
		$settings = $this->_settings;
		foreach ($results as $result) {
			if (isset($settings[$result['setting']])) {
				if ($result['value'] != $settings[$result['setting']]) {
					$result['value'] = $settings[$result['setting']];
					$data[] = $result;
				}

				unset($settings[$result['setting']]);
			}
			elseif ($this->_delete_missing) {
				$this->_mysql->delete(self::SETTINGS_TABLE, " WHERE setting = {$result['setting']} ");
			}
		}

		if ($this->_save_new) {
			foreach ($settings as $setting => $value) {
				$addition['setting'] = $setting;
				$addition['value'] = $value;
				$data[] = $addition;
			}
		}

		$this->_mysql->multi_insert(self::SETTINGS_TABLE, $data, true);
	}


	/** protected function get_settings
	 *		Grabs the whole settings array and returns it
	 *
	 * @param void
	 * @return array settings
	 */
	protected function get_settings( )
	{
		return $this->_settings;
	}


	/** protected function put_settings
	 *		Merges the submitted settings into
	 *		the settings array
	 *
	 * @param array settings
	 * @return void
	 */
	protected function put_settings($settings)
	{
		if (is_array($settings)) {
			$this->_settings = array_merge($this->_settings, $settings);
		}
	}


	/** static public function get_instance
	 *		Returns the singleton instance
	 *		of the Settings Object as a reference
	 *
	 * @param void
	 * @action optionally creates the instance
	 * @return Settings Object reference
	 */
	static public function get_instance( )
	{
		if (is_null(self::$_instance) && self::test( )) {
			self::$_instance = new Settings( );
		}

		return self::$_instance;
	}


	/** static public function read
	 *		Gets the requested property if the
	 *		requested property is not _private
	 *
	 * @param string $property name
	 * @return mixed property value
	 * @see __get
	 */
	static public function read($property)
	{
		if (self::get_instance( )) {
			return self::get_instance( )->$property;
		}
		else {
			return false;
		}
	}


	/** static public function read_all
	 *		Gets all the properties
	 *
	 * @param void
	 * @return array property => value pairs
	 */
	static public function read_all( )
	{
		if (self::get_instance( )) {
			return self::get_instance( )->get_settings( );
		}
		else {
			return false;
		}
	}


	/** static public function write
	 *		Sets the requested property if the
	 *		requested property is not _private
	 *
	 * @param string $property name
	 * @param mixed $value
	 * @return void
	 * @see __set
	 */
	static public function write($property, $value)
	{
		if (self::get_instance( )) {
			self::get_instance( )->$property = $value;
			self::get_instance( )->save( );
		}
	}


	/** static public function write_all
	 *		Sets all the properties
	 *
	 * @param array $settings property => value pairs
	 * @return array or false on failure
	 * @see __set
	 */
	static public function write_all($settings)
	{
		if (self::get_instance( )) {
			self::get_instance( )->put_settings($settings);
			self::get_instance( )->save( );
			return self::get_instance( )->get_settings( );
		}
		else {
			return false;
		}
	}


	/** static public function read_setting_notes
	 *		Reads the notes associated with the settings
	 *
	 * @param void
	 * @return array settings notes
	 */
	static public function read_setting_notes( )
	{
		if ($_this = self::get_instance( )) { // single equals intended
			return $_this->_notes;
		}
		else {
			return false;
		}
	}


	/** static public function test
	 *		Test the MySQL connection
	 *
	 * @param void
	 * @return bool connection OK
	 */
	static public function test( )
	{
		if ( ! is_null(self::$_instance)) {
			return true;
		}

		if ( ! Mysql::test( )) {
			return false;
		}

		// look for anything in the settings table
		$query = "
			SELECT *
			FROM `".self::SETTINGS_TABLE."`
		";
		$return = Mysql::get_instance( )->query($query);

		return (bool) $return;
	}

} // end of Settings class


/*		schemas
// ===================================

--
-- Table structure for table `wr_settings`
--

DROP TABLE IF EXISTS `wr_settings`;
CREATE TABLE IF NOT EXISTS `wr_settings` (
  `setting` varchar(255) NOT NULL DEFAULT '',
  `value` text NOT NULL,
  `notes` text,
  `sort` smallint(5) unsigned NOT NULL DEFAULT '0',

  UNIQUE KEY `setting` (`setting`),
  KEY `sort` (`sort`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci ;

--
-- Dumping data for table `wr_settings`
--

INSERT INTO `wr_settings` (`setting`, `value`, `notes`, `sort`) VALUES
  ('site_name', 'Your Site Name', 'The name of your site', 10),
  ('default_color', 'c_green_white.css', 'The default theme color for the script pages', 20),
  ('nav_links', '<!-- your nav links here -->', 'HTML code for your site''s navigation links to display on the script pages', 30),
  ('from_email', 'your.mail@yoursite.com', 'The email address used to send game emails', 40),
  ('to_email', 'you@yoursite.com', 'The email address to send admin notices to (comma separated)', 50),
  ('new_users', '1', '(1/0) Allow new users to register (0 = off)', 60),
  ('approve_users', '0', '(1/0) Require admin approval for new users (0 = off)', 70),
  ('confirm_email', '0', '(1/0) Require email confirmation for new users (0 = off)', 80),
  ('max_users', '0', 'Max users allowed to register (0 = off)', 90),
  ('default_pass', 'change!me', 'The password to use when resetting a user''s password', 100),
  ('expire_users', '45', 'Number of days until untouched games are deleted (0 = off)', 110),
  ('save_games', '1', '(1/0) Save games in the ''games'' directory on the server (0 = off)', 120),
  ('expire_games', '30', 'Number of days until untouched user accounts are deleted (0 = off)', 130),
  ('nudge_flood_control', '24', 'Number of hours between nudges. (-1 = no nudging, 0 = no flood control)', 135),
  ('timezone', 'UTC', 'The timezone to use for dates (<a href="http://www.php.net/manual/en/timezones.php">List of Timezones</a>)', 140),
  ('long_date', 'M j, Y g:i a', 'The long format for dates (<a href="http://www.php.net/manual/en/function.date.php">Date Format Codes</a>)', 150),
  ('short_date', 'Y.m.d H:i', 'The short format for dates (<a href="http://www.php.net/manual/en/function.date.php">Date Format Codes</a>)', 160),
  ('debug_pass', '', 'The DEBUG password to use to set temporary DEBUG status for the script', 170),
  ('DB_error_log', '1', '(1/0) Log database errors to the ''logs'' directory on the server (0 = off)', 180),
  ('DB_error_email', '1', '(1/0) Email database errors to the admin email addresses given (0 = off)', 190);


*/

