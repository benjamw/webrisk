<?php
/*
+---------------------------------------------------------------------------
|
|   chat.class.php (php 5.x)
|
|   by Benjam Welker
|   http://iohelix.net
|
+---------------------------------------------------------------------------
|
|   > In-game chat module
|   > Date started: 2008-06-15
|
|   > Module Version Number: 0.8.0
|
+---------------------------------------------------------------------------
*/

/*

Requires
----------------------------------------------------------------------------
	Mysql class:
		Mysql::get_instance(

	MyException class
*/

// TODO: comments & organize better
// TODO: exceptions

class Chat
{

	/**
	 *		PROPERTIES
	 * * * * * * * * * * * * * * * * * * * * * * * * * * */

	/** const property CHAT_TABLE
	 *		Stores the name of the chat table
	 *
	 * @param string
	 */
	const CHAT_TABLE = T_CHAT;


	/** protected property _user_id
	 *		Stores the id of the user
	 *
	 * @param int
	 */
	protected $_user_id;


	/** protected property _game_id
	 *		Stores the id of the game
	 *
	 * @param int
	 */
	protected $_game_id;


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


	/**
	 *		METHODS
	 * * * * * * * * * * * * * * * * * * * * * * * * * * */

	/** public function __construct
	 *		Class constructor
	 *		Sets all outside data
	 *
	 * @param int user id
	 * @param int game id
	 * @action instantiates object
	 * @return void
	 */
	public function __construct($user_id, $game_id)
	{
		if (empty($user_id)) {
			throw new MyException(__METHOD__.': No user id given');
		}

		$Mysql = Mysql::get_instance( );

		$this->_user_id = (int) $user_id;
		$this->_game_id = (int) $game_id;
		$this->_mysql = $Mysql;

		if (defined('DEBUG')) {
			$this->_DEBUG = DEBUG;
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


	/** public function get_box_list
	 *		Retrieves the list of chats in the box
	 *
	 * @param int optional number of chats to retrieve (pulls most recent)
	 * @return html list table
	 */
	public function get_box_list($num = null)
	{
		if ((0 == $this->_game_id) && is_null($num)) {
			$num = 30;
		}

		$LIMIT = (( ! is_null($num)) ? " LIMIT ".(int) $num." " : '');

		$query = "
			SELECT C.*
				, P.player_id
				, P.username
				, P.email
			FROM ".self::CHAT_TABLE." AS C
				LEFT JOIN ".Player::PLAYER_TABLE." AS P
					ON P.player_id = C.from_id
			WHERE C.game_id = {$this->_game_id}
				AND ((C.from_id = {$this->_user_id})
					OR (C.private = 0))
			ORDER BY C.create_date DESC
			{$LIMIT}
		";
		$result = $this->_mysql->fetch_array($query);

		return $result;
	}


	/** public function send_message
	 *		Adds a message to the in-game chat
	 *
	 * @param string message
	 * @param bool optional private message
	 * @action saves all relevant data to database
	 * @return void
	 */
	public function send_message($message, $private = false, $lobby = false)
	{
		// run through htmlentities first (no html allowed)
		$message = htmlentities($message, ENT_QUOTES, 'ISO-8859-1', false);

		// check the lobby
		if ( ! (bool) $lobby && ! $this->_game_id) {
			throw new MyException(__METHOD__.': Game ID missing from chat');
		}

		// grab the last message and make sure it isn't exactly the same
		$last = $this->get_box_list(1);
		if ($last) {
			$last = $last[0];
			$last_date = strtotime($last['create_date']);

			// because there may be a time difference between the DB server and the WebServer
			$date = strtotime($this->_mysql->fetch_value(" SELECT NOW( ) "));

			if (($message == $last['message']) && ($private == $last['private']) && ($date <= ($last_date + 60))) {
				throw new MyException(__METHOD__.': Duplicate message');
			}
		}

		// save the message
		$this->_mysql->insert(self::CHAT_TABLE, array('message' => $message, 'private' => (int) $private, 'from_id' => $this->_user_id, 'game_id' => $this->_game_id));
	}

} // end of Chat class


/*		schemas
// ===================================

--
-- Table structure for table `wr_chat`
--

DROP TABLE IF EXISTS `wr_chat`;
CREATE TABLE IF NOT EXISTS `wr_chat` (
  `chat_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `message` text NOT NULL,
  `from_id` int(10) unsigned NOT NULL DEFAULT '0',
  `game_id` int(10) unsigned NOT NULL DEFAULT '0',
  `private` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `create_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`chat_id`),
  KEY `game_id` (`game_id`),
  KEY `private` (`private`),
  KEY `from_id` (`from_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 COLLATE=latin1_general_ci ;

*/

