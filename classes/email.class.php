<?php
/*
+---------------------------------------------------------------------------
|
|   email.class.php (php 5.x)
|
|   by Benjam Welker
|   http://iohelix.net
|
+---------------------------------------------------------------------------
|
|   > Email module
|   > Date started: 2009-04-27
|
|   > Module Version Number: 0.9.0
|
+---------------------------------------------------------------------------
*/

/*

Requires
----------------------------------------------------------------------------
	Settings class:
		Settings::read('site_name')
		Settings::read('from_email')

	Log class:
		Log::write(

	Mysql class:
		Mysql::get_instance( )->fetch_row(

	MyException class
*/


class Email
{

	/**
	 *		PROPERTIES
	 * * * * * * * * * * * * * * * * * * * * * * * * * * */

	/** static private property _instance
	 *		Holds the instance of this object
	 *
	 * @var Email object
	 */
	static private $_instance;


	/** protected property email_data
	 *		Holds the message data
	 *
	 * @var array
	 */
	protected $email_data = array( );



	/**
	 *		METHODS
	 * * * * * * * * * * * * * * * * * * * * * * * * * * */

	/** protected function __construct
	 *		Class constructor
	 *
	 * @param void
	 * @action instantiates object
	 * @return void
	 */
	protected function __construct( )
	{
		if ( ! $this->email_data && defined('INCLUDE_DIR')) {
			require_once INCLUDE_DIR.'inc.email.php';
			$this->email_data = $GLOBALS['__EMAIL_DATA'];
			unset($GLOBALS['__EMAIL_DATA']);
		}
	}


	/** protected function _send
	 *		Sends email messages of various types [optional data contents]
	 *
	 * @param string message type
	 * @param mixed player id OR email address OR mixed array of both
	 * @param array optional message data
	 * @action send emails
	 * @return bool success
	 */
	protected function _send($type, $to, $data = array( ))
	{
		call(__METHOD__);
		call($type);
		call($to);
		call($data);

		if (is_array($to)) {
			$return = true;

			foreach ($to as $player) {
				$return = ($this->_send($type, trim($player), $data) && $return);
			}

			return $return;
		}
		// $to is an email address (or comma seperated email addresses)
		elseif (preg_match('/^[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,4}$/i', $to)) {
			$email = $to;
		}
		else { // $to is a single player id
			$player_id = (int) $to;

			// test if this user accepts emails
			$query = "
				SELECT P.email
					, PE.allow_email
				FROM ".Player::PLAYER_TABLE." AS P
					LEFT JOIN ".GamePlayer::EXTEND_TABLE." AS PE
						ON P.player_id = PE.player_id
				WHERE P.player_id = '{$player_id}'
			";
			list($email, $allow) = Mysql::get_instance( )->fetch_row($query);
			call($email);call($allow);

			if (empty($allow) || empty($email)) {
				// no exception, just quit
				return false;
			}
		}
		call($email);

		$site_name = Settings::read('site_name');

		if ( ! in_array($type, array_keys($this->email_data))) {
			throw new MyException(__METHOD__.': Trying to send email with unsupprted type ('.$type.')');
		}

		$subject = $this->email_data[$type]['subject'];
		$message = $this->email_data[$type]['message'];

		// replace the meta vars
		$replace = array(
			'/\[\[\[GAME_NAME\]\]\]/' => GAME_NAME,
			'/\[\[\[game_name\]\]\]/' => @$data['name'],
			'/\[\[\[site_name\]\]\]/' => $site_name,
			'/\[\[\[extra_text\]\]\]/' => $this->_strip(@$_POST['extra_text']),
			'/\[\[\[sender\]\]\]/' => @$data['player'],
			'/\[\[\[winner\]\]\]/' => @$data['winner'],
			'/\[\[\[export_data\]\]\]/' => var_export($data, true),
		);

		$message = preg_replace(array_keys($replace), $replace, $message);

		$subject = GAME_NAME.' - '.$subject;
		$message .= '

=============================================
This message was automatically sent by
'.$site_name.'
and should not be replied to.
=============================================
'.$GLOBALS['_ROOT_URI'];

		$from_email = Settings::read('from_email');

		// send the email
		$headers = "From: ".GAME_NAME." <{$from_email}>\r\n";

		$message = html_entity_decode($message);

		$this->_log($email."\n".$headers."\n".$subject."\n".$message);
		call($subject);call($message);call($headers);
		if ($GLOBALS['_USEEMAIL']) {
			return mail($email, $subject, $message, $headers);
		}

		return false;
	}


	/** protected function _strip
	 *		Strips out the bad stuff from the email
	 *
	 * @param string the string to clean
	 * @param bool optional this is a message string
	 * @return string clean string
	 */
	protected function _strip($value, $message = false) {
		$search  = '%0a|%0d|Content-(?:Type|Transfer-Encoding)\:';
		$search .= '|charset\=|mime-version\:|multipart/mixed|(?:[^a-z]to|b?cc)\:.*';

		if ( ! (bool) $message) {
			$search .= '|\r|\n';
		}

		$search = '#(?:' . $search . ')#i';
		while (preg_match($search, $value)) {
			$value = preg_replace($search, '', $value);
		}

		return htmlentities($value, ENT_QUOTES, 'ISO-8859-1', false);
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


	/** static public function get_instance
	 *		Returns the singleton instance
	 *		of the Email Object as a reference
	 *
	 * @param void
	 * @action optionally creates the instance
	 * @return Email Object reference
	 */
	static public function get_instance( )
	{
		if (is_null(self::$_instance)) {
			self::$_instance = new Email( );
		}

		return self::$_instance;
	}


	/** static public function send
	 *		Static access for _send
	 *
	 * @param string message type
	 * @param mixed player id OR email address OR mixed array of both
	 * @param array optional message data
	 * @action send emails
	 * @return bool success
	 * @see _send
	 */
	static public function send($type, $to, $data = array( ))
	{
		call(__METHOD__);
		call($type);

		$_this = self::get_instance( );

		return $_this->_send($type, $to, $data);
	}

} // end of Email class

