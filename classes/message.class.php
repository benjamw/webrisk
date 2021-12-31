<?php
/*
+---------------------------------------------------------------------------
|
|   message.class.php (php 5.x)
|
|   by Benjam Welker
|   http://iohelix.net
|
+---------------------------------------------------------------------------
|
|   > Messaging module
|   > Date started: 2008-01-04
|
|   > Module Version Number: 1.0.1
|
+---------------------------------------------------------------------------
*/

// TODO: comments & organize better
// TODO: exceptions

class Message
{

	/**
	 *		PROPERTIES
	 * * * * * * * * * * * * * * * * * * * * * * * * * * */

	/** const property MESSAGE_TABLE
	 *		Stores the name of the message table
	 *
	 * @param string
	 */
	const MESSAGE_TABLE = T_MESSAGE;


	/** const property GLUE_TABLE
	 *		Stores the name of the glue table
	 *		that joins users to messages
	 *
	 * @param string
	 */
	const GLUE_TABLE = T_MSG_GLUE;


	/** protected property _user_id
	 *		Stores the id of the user
	 *
	 * @param int
	 */
	protected $_user_id;


	/** protected property _can_send_global
	 *		Stores a flag letting us know if
	 *		the user can send global messages or not
	 *
	 * @param bool
	 */
	protected $_can_send_global;


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
	 * @param bool optional global flag, if set, user can send global messages
	 * @action instantiates object
	 * @action deletes expired messages and glue table entries from the database
	 * @return void
	 */
	public function __construct($user_id, $global = false)
	{
		call(__METHOD__);

		if (empty($user_id)) {
			throw new MyException(__METHOD__.': No user id given');
		}

		$Mysql = Mysql::get_instance( );

		$this->_user_id = (int) $user_id;
		$this->_can_send_global = (bool) $global;
		$this->_mysql = $Mysql;

		if (defined('DEBUG')) {
			$this->_DEBUG = DEBUG;
		}

		// remove any expired messages
		$query = "
			SELECT DISTINCT `message_id`
			FROM `".self::GLUE_TABLE."`
			WHERE expire_date < NOW( )
				AND expire_date IS NOT NULL
		";
		$message_ids = $this->_mysql->fetch_value_array($query);

		if ($message_ids) {
			$this->_mysql->multi_delete([self::GLUE_TABLE, self::MESSAGE_TABLE], " WHERE message_id IN (".implode(',', $message_ids).") ");
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


	/** public function get_inbox_list
	 *		Retrieves the list of messages in the inbox
	 *		both unread and total
	 *
	 * @param void
	 * @return array inbox data
	 */
	public function get_inbox_list( )
	{
		call(__METHOD__);

		$query = "
			SELECT G.*
				, IF('' <> M.subject, M.subject, '<No Subject>') AS subject
				, P.player_id AS sender_id
				, P.username AS sender
				, IF(G.send_date IS NOT NULL, G.send_date, G.create_date) AS order_date
				, IF(GT.from_id IS NOT NULL, 1, 0) AS global
			FROM ".self::GLUE_TABLE." AS G
				LEFT JOIN ".self::MESSAGE_TABLE." AS M
					ON M.message_id = G.message_id
				LEFT JOIN ".Player::PLAYER_TABLE." AS P
					ON P.player_id = G.from_id
				LEFT JOIN ".self::GLUE_TABLE." AS GT
					ON (GT.message_id = G.message_id
						AND GT.to_id = 0)
			WHERE G.to_id = {$this->_user_id}
				AND G.from_id <> {$this->_user_id}
				AND (G.send_date < NOW( )
					OR G.send_date IS NULL
				)
				AND G.deleted = 0
			ORDER BY order_date DESC
		";
		$results = $this->_mysql->fetch_array($query);

		return $results;
	}


	/** public function get_outbox_list
	 *		Retrieves the list of messages in the outbox
	 *
	 * @param void
	 * @return array outbox data
	 */
	public function get_outbox_list( )
	{
		call(__METHOD__);

		// NOTE: DO NOT LOOK FOR SEND DATE, we sent it, we want to see it, ALWAYS...
		// that way, we can delete it before it gets sent, if we so desire

		// look for our own entry, and if found, run the query to find the recipients
		$query = "
			SELECT G.*
				, IF('' <> M.subject, M.subject, '<No Subject>') AS subject
				, ((G.send_date < NOW( )) || G.send_date IS NULL) AS sent
			FROM ".self::GLUE_TABLE." AS G
				LEFT JOIN ".self::MESSAGE_TABLE." AS M
					ON M.message_id = G.message_id
			WHERE G.from_id = {$this->_user_id}
				AND G.to_id = {$this->_user_id}
				AND G.deleted = 0
			ORDER BY M.create_date DESC
				, M.message_id DESC
				, G.to_id ASC
		";
		$result = $this->_mysql->fetch_array($query);

		if ($result) {
			foreach ($result as $key => $row) {
				// grab all the recipients of this message
				$query = "
					SELECT *
					FROM ".self::GLUE_TABLE."
					WHERE message_id = '{$row['message_id']}'
						AND to_id <> {$this->_user_id}
					ORDER BY to_id ASC
				";
				$recipients = $this->_mysql->fetch_array($query);

				// add the recipients to the message
				if ($recipients) {
					foreach ($recipients as $recipient) {
						if (0 == $recipient['to_id']) {
							$row['recipient_data'][] = [
								'id' => $recipient['to_id'] ,
								'name' => 'GLOBAL' ,
								'viewed' => true
							];

							break;
						}

						$row['recipient_data'][] = [
							'id' => $recipient['to_id'] ,
							'name' => $GLOBALS['_PLAYERS'][$recipient['to_id']] ,
							'viewed' => ( ! is_null($recipient['view_date']))
						];

						$result[$key] = $row;
					}
				}

				// now convert the recipients for each message to a string
				$recip_string = '';
				if (is_array($row['recipient_data'])) {
					foreach ($row['recipient_data'] as $recipient) {
						if (empty($recipient['name'])) {
							continue;
						}

						$recip_string .= ( ! $recipient['viewed']) ? '<span class="highlight">'.$recipient['name'].'</span>, ' : $recipient['name'].', ';
					}
				}

				$result[$key]['recipients'] = substr($recip_string, 0, -2);
			}
		}

		return $result;
	}


	/** public function get_admin_list
	 *		Retrieves the list of messages that were
	 *		neither sent from or to the current admin
	 *
	 * @param void
	 * @return array message box data
	 */
	public function get_admin_list( )
	{
		call(__METHOD__);

		// NOTE: DO NOT LOOK FOR SEND DATE

		// grab all entries that were neither sent nor received by the current admin
		$query = "
			SELECT G.*
				, IF('' <> M.subject, M.subject, '<No Subject>') AS subject
				, ((G.send_date < NOW( )) || G.send_date IS NULL) AS sent
				, P.username AS sender
			FROM ".self::GLUE_TABLE." AS G
				LEFT JOIN ".self::MESSAGE_TABLE." AS M
					USING (message_id)
				LEFT JOIN ".Player::PLAYER_TABLE." AS P
					ON (P.player_id = G.from_id)
			WHERE G.message_id IN (
					SELECT DISTINCT(message_id)
					FROM ".self::GLUE_TABLE."
					WHERE message_id NOT IN (
						SELECT DISTINCT(message_id)
						FROM ".self::GLUE_TABLE."
						WHERE from_id = {$this->_user_id}
							OR to_id = {$this->_user_id}
					)
				)
				AND G.to_id = G.from_id
			ORDER BY M.create_date DESC
				, M.message_id DESC
		";
		$result = $this->_mysql->fetch_array($query);

		if ($result) {
			foreach ($result as $key => $row) {
				// if it's an invitation message, don't show it
				if (preg_match('/^Invitation to ("|&quot;).+?\1$/i', $row['subject'])) {
					unset($result[$key]);
					continue;
				}

				// grab all the recipients of this message
				$query = "
					SELECT *
					FROM ".self::GLUE_TABLE."
					WHERE message_id = '{$row['message_id']}'
						AND to_id <> {$row['from_id']}
					ORDER BY to_id ASC
				";
				$recipients = $this->_mysql->fetch_array($query);

				// add the recipients to the message
				if ($recipients) {
					foreach ($recipients as $recipient) {
						if (0 == $recipient['to_id']) {
							$row['recipient_data'][] = [
								'id' => $recipient['to_id'] ,
								'name' => 'GLOBAL' ,
								'viewed' => true
							];

							break;
						}

						$row['recipient_data'][] = [
							'id' => $recipient['to_id'] ,
							'name' => $GLOBALS['_PLAYERS'][$recipient['to_id']] ,
							'viewed' => ( ! is_null($recipient['view_date']))
						];

						$result[$key] = $row;
					}
				}

				// now convert the recipients for each message to a string
				$recip_string = '';
				if (is_array($row['recipient_data'])) {
					foreach ($row['recipient_data'] as $recipient) {
						if (empty($recipient['name'])) {
							continue;
						}

						$recip_string .= ( ! $recipient['viewed']) ? '<span class="highlight">'.$recipient['name'].'</span>, ' : $recipient['name'].', ';
					}
				}

				$result[$key]['recipients'] = substr($recip_string, 0, -2);
			}
		}

		return $result;
	}


	/** public function get_message
	 *		Retrieves the message from the database
	 *		but makes sure this user can see this message first
	 *
	 * @param int message id
	 * @param bool is admin
	 * @action tests to make sure this user can see this message
	 * @return array message data
	 */
	public function get_message($message_id, $admin = false)
	{
		call(__METHOD__);

		$message_id = (int) $message_id;
		$admin = (bool) $admin;

		if (empty($message_id)) {
			throw new MyException(__METHOD__.': No message id given');
		}

		$query = "
			SELECT M.*
			FROM ".self::MESSAGE_TABLE." AS M
			WHERE M.message_id = {$message_id}
		";
		$message = $this->_mysql->fetch_assoc($query);

		if ( ! $message) {
			throw new MyException(__METHOD__.': Message not found');
		}

		// find out who this message was sent by
		$query = "
			SELECT G.*
				, P.username AS recipient
				, S.username AS sender
			FROM ".self::GLUE_TABLE." AS G
				LEFT JOIN ".GamePlayer::EXTEND_TABLE." AS R
					ON (R.player_id = G.to_id)
				LEFT JOIN ".Player::PLAYER_TABLE." AS P
					ON (P.player_id = R.player_id)
				LEFT JOIN ".Player::PLAYER_TABLE." AS S
					ON (S.player_id = G.from_id)
			WHERE G.message_id = '{$message['message_id']}'
			ORDER BY recipient
		";
		$message['recipients'] = $this->_mysql->fetch_array($query);

		// parse through the recipients and find out
		// if we are allowed to view this message
		// and set some various message flags
		$message['allowed'] = false;
		$message['inbox'] = false;
		$message['global'] = false;
		foreach ($message['recipients'] as $recipient) {
			if ($recipient['from_id'] == $this->_user_id) {
				$message['allowed'] = true;
				$message['inbox'] = true;
			}

			if ($recipient['to_id'] == $this->_user_id) {
				$message['allowed'] = true;
			}

			if (0 == $recipient['to_id']) {
				$message['global'] = true;
			}
		}

		if ( ! $message['allowed'] && ! $admin) {
			throw new MyException(__METHOD__.': Not allowed to view this message');
		}
		else {
			$this->set_message_read($message_id);
		}

		if ('' == $message['subject']) {
			$message['subject'] = '<No Subject>';
		}

		return $message;
	}


	/** public function get_message_reply
	 *		Retrieves the message from the database
	 *		but makes sure this user can see this message first
	 *		and then appends data to the message so it can be replied to
	 *
	 * @param int message id
	 * @action tests to make sure this user can see this message
	 * @action appends data to the subject and message so it can be replied to
	 * @return array [subject, message, to]
	 */
	public function get_message_reply($message_id)
	{
		call(__METHOD__);

		try {
			$message = $this->_get_message_data($message_id, true);
			$message['subject'] = (0 === strpos($message['subject'], 'RE')) ? $message['subject'] : 'RE: '.$message['subject'];
		}
		catch (MyExeption $e) {
			throw $e;
		}

		return $message;
	}


	/** public function get_message_forward
	 *		Retrieves the message from the database
	 *		but makes sure this user can see this message first
	 *		and then appends data to the message so it can be forwarded
	 *
	 * @param int message id
	 * @action tests to make sure this user can see this message
	 * @action appends data to the subject and message so it can be forwarded
	 * @return array [subject, message, to]
	 */
	public function get_message_forward($message_id)
	{
		call(__METHOD__);

		try {
			$message = $this->_get_message_data($message_id, false);
			$message['subject'] = (0 === strpos($message['subject'], 'FW')) ? $message['subject'] : 'FW: '.$message['subject'];
		}
		catch (MyExeption $e) {
			throw $e;
		}

		return $message;
	}


	/** public function set_message_read
	 *		Sets the given messages as read by this user
	 *
	 * @param array or csv string message id(s)
	 * @action sets read date for these messages
	 * @return void
	 */
	public function set_message_read($message_ids)
	{
		call(__METHOD__);

		// if we are admin logged in as another player
		// don't mark it as read if we view the message
		if ( ! empty($_SESSION['admin_id'])) {
			return;
		}

		array_trim($message_ids, 'int');

		if (0 != count($message_ids)) {
			$WHERE = "
				WHERE to_id = '{$this->_user_id}'
					AND message_id IN (".implode(',', $message_ids).")
			";
			$this->_mysql->insert(self::GLUE_TABLE, ['view_date ' => 'NOW( )'], $WHERE);
		}
	}


	/** public function set_message_unread
	 *		Sets the given messages as unread by this user
	 *
	 * @param array or csv string message id(s)
	 * @action removes read date for these messages
	 * @return void
	 */
	public function set_message_unread($message_ids)
	{
		call(__METHOD__);

		array_trim($message_ids, 'int');

		if (0 != count($message_ids)) {
			$WHERE = "
				WHERE to_id = '{$this->_user_id}'
					AND message_id IN (".implode(',', $message_ids).")
			";
			$this->_mysql->insert(self::GLUE_TABLE, ['view_date' => NULL], $WHERE);
		}
	}


	/** public function delete_message
	 *		Deletes the glue table entry for these messages for this user
	 *
	 * @param array or csv string message id(s)
	 * @action deletes the glue table entries
	 * @return void
	 */
	public function delete_message($message_ids)
	{
		call(__METHOD__);

		array_trim($message_ids, 'int');

	 	if (0 != count($message_ids)) {
	 		foreach ($message_ids as $message_id) {
			 	$query = "
			 		SELECT *
			 		FROM ".self::GLUE_TABLE."
			 		WHERE to_id = '{$this->_user_id}'
			 			AND message_id = '{$message_id}'
			 	";
			 	$result = $this->_mysql->fetch_assoc($query);

			 	// test and see if the message is from this user
			 	if ($result['from_id'] == $this->_user_id) {
					// in case the DB server has a different time
					$now = strtotime($this->_mysql->fetch_value(" SELECT NOW( ); "));

					if (strtotime($result['send_date']) > $now) {
			 			// the message has not been sent yet, delete them all
			 			// (use actual deletions here)
						$this->_mysql->multi_delete([self::GLUE_TABLE, self::MESSAGE_TABLE], " WHERE message_id = '{$message_id}' ");
			 		}

			 		// check for global message and delete if found
		 			// (use actual deletion here)
			 		if ($this->_can_send_global) {
			 			$WHERE = "
			 				WHERE to_id = 0
			 					AND message_id = '{$message_id}'
			 			";
			 			$this->_mysql->delete(self::GLUE_TABLE, $WHERE);
				 	}
			 	}

			 	// delete our own entry
			 	$WHERE = "
			 		WHERE to_id = '{$this->_user_id}'
			 			AND message_id = '{$message_id}'
			 	";
			 	$this->_mysql->insert(self::GLUE_TABLE, ['deleted' => 1], $WHERE);
			}
		}
	}


	/** static public function player_deleted
	 *		Deletes the given players messages
	 *
	 * @param mixed array or csv of player ids
	 * @action deletes the players messages
	 * @return void
	 */
	static public function player_deleted($player_ids)
	{
		call(__METHOD__);

		$Mysql = Mysql::get_instance( );

		array_trim($player_ids, 'int');

		if ( ! $player_ids) {
			throw new MyException(__METHOD__.': No player IDs given');
		}

		$player_ids = implode(',', $player_ids);

		$Mysql->delete(Message::GLUE_TABLE, " WHERE from_id IN ({$player_ids}) OR to_id IN ({$player_ids}) ");
	}


	/** public function send_message
	 *		Deletes the glue table entry for this message for this user
	 *
	 * @param string message subject
	 * @param string message body
	 * @param array (or csv string) message recipient user ids
	 * @param int optional message send date as unix timestamp (default: now)
	 * @param int optional message expire date as unix timestamp (default: never)
	 * @action saves all relevant data to database
	 * @return void
	 */
	public function send_message($subject, $message, $user_ids, $send_date = false, $expire_date = false)
	{
		call(__METHOD__);

		array_trim($user_ids, 'int');

		// check for a global message
		if ($this->_can_send_global) {
			// just replace the user_ids, everybody's gonna get it anyway
			if (in_array(0, $user_ids)) {
				$query = "
					SELECT player_id
					FROM `".Player::PLAYER_TABLE."`
				";
				$user_ids = $this->_mysql->fetch_value_array($query);
				$user_ids[] = 0;
			}
		}
		else { // this is not an admin
			// remove all instances of 0 from the id list
			$user_ids = array_diff(array_unique($user_ids), [0]);
		}

		if ( ! is_array($user_ids) || (0 == count($user_ids))) {
			throw new MyException(__METHOD__.': Trying to send a message to nobody');
		}

		// clean the message bits
		$subject = htmlentities($subject, ENT_QUOTES, 'UTF-8', false);
		$message = htmlentities($message, ENT_QUOTES, 'UTF-8', false);

		// save the message so we can grab the id
		$message_id = $this->_mysql->insert(self::MESSAGE_TABLE, ['subject' => $subject, 'message' => $message]);

		// add ourselves to the recipient list and clean it up
		$user_ids[] = $this->_user_id;
		$user_ids = array_unique($user_ids);

		// convert 04/24/2008 -> 2008-04-24
		$send_date = ( ! preg_match('%^(\\d+)/(\\d+)/(\\d+)$%', $send_date)) ? NULL : preg_replace('%^(\\d+)/(\\d+)/(\\d+)$%', '$3-$1-$2', $send_date);
		$expire_date = ( ! preg_match('%^(\\d+)/(\\d+)/(\\d+)$%', $expire_date)) ? NULL : preg_replace('%^(\\d+)/(\\d+)/(\\d+)$%', '$3-$1-$2', $expire_date);

		foreach($user_ids as $user_id) {
			$data = [
				'message_id' => $message_id,
				'from_id' => $this->_user_id,
				'to_id' => $user_id,
				'send_date' => $send_date,
				'expire_date' => $expire_date,
			];
			$this->_mysql->insert(self::GLUE_TABLE, $data);
		}
	}


	/** public function grab_global_messages
	 *		Searches the glue table for global messages and copies
	 *		those entries to this user
	 *
	 * @param void
	 * @action copies global messages to this users inbox
	 * @return void
	 */
	public function grab_global_messages( )
	{
		call(__METHOD__);

		$query = "
			SELECT *
			FROM `".self::GLUE_TABLE."`
			WHERE to_id = 0
				AND deleted = 0
		";
		$result = $this->_mysql->fetch_array($query);

		if ($result) {
			foreach ($result as $row) {
				unset($row['message_glue_id']);
				unset($row['view_date']);
				unset($row['create_date']);
				unset($row['deleted']);
				$row['to_id'] = $this->_user_id;

				$this->_mysql->insert(self::GLUE_TABLE, $row);
			}
		}
	}


	/** protected function _get_message_data
	 *		Retrieves the message from the database for sending
	 *		but makes sure this user can see this message first
	 *		and then appends data to the message so it can be sent
	 *
	 * @param int message id
	 * @action tests to make sure this user can see this message
	 * @action appends data to the subject and message so it can be sent
	 * @return array [subject, message, to]
	 */
	protected function _get_message_data($message_id)
	{
		call(__METHOD__);

		try {
			$message = $this->get_message($message_id);
		}
		catch (MyExeption $e) {
			throw $e;
		}

		$message['from'] = Player::get_username($message['recipients'][0]['from_id']);
		$message['date'] = (empty($message['send_date']) ? $message['create_date'] : $message['send_date']);
		$message['date'] = ldate(Settings::read('long_date'), strtotime($message['date']));
		$message['subject'] = ('' == $message['subject']) ? '<No Subject>' : $message['subject'];
		$message['message'] = "\n\n\n".str_repeat('=', 50)."\n\n{$message['from']} said: ({$message['date']})\n".str_repeat('-', 50)."\n{$message['message']}";

		return $message;
	}


	/** static public function get_count
	 *		Grab the inbox count of new and total messages
	 *		for the given player
	 *
	 * @param int player id
	 * @return array (int total messages, int new messages)
	 */
	static public function get_count($player_id)
	{
		call(__METHOD__);

		$player_id = (int) $player_id;

		$Mysql = Mysql::get_instance( );

		$query = "
			SELECT COUNT(*)
			FROM ".self::GLUE_TABLE."
			WHERE to_id = '{$player_id}'
				AND from_id <> '{$player_id}'
				AND (send_date <= NOW( )
					OR send_date IS NULL)
				AND deleted = 0
		";
		$msgs = $Mysql->fetch_value($query);

		$query .= "
				AND view_date IS NULL
		";
		$new_msgs = $Mysql->fetch_value($query);

		return [$msgs, $new_msgs];
	}


	/** static public function check_new
	 *		Checks if the given player has any new messages
	 *
	 * @param int player id
	 * @return number of new messages
	 */
	static public function check_new($player_id)
	{
		call(__METHOD__);

		$player_id = (int) $player_id;

		if ( ! $player_id) {
			return false;
		}

		$Mysql = Mysql::get_instance( );

		$query = "
			SELECT COUNT(*)
			FROM ".self::GLUE_TABLE."
			WHERE to_id = '{$player_id}'
				AND from_id <> '{$player_id}'
				AND (send_date <= NOW( )
					OR send_date IS NULL)
				AND deleted = 0
				AND view_date IS NULL
		";
		$new = $Mysql->fetch_value($query);

		return $new;
	}

} // end of Message class


/*		schemas
// ===================================

--
-- Table structure for table `wr_message`
--

DROP TABLE IF EXISTS `wr_message`;
CREATE TABLE IF NOT EXISTS `wr_message` (
  `message_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `subject` varchar(255) NOT NULL DEFAULT '',
  `message` text NOT NULL,
  `create_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`message_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci ;

-- --------------------------------------------------------

--
-- Table structure for table `wr_message_glue`
--

DROP TABLE IF EXISTS `wr_message_glue`;
CREATE TABLE IF NOT EXISTS `wr_message_glue` (
  `message_glue_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `message_id` int(10) unsigned NOT NULL DEFAULT '0',
  `from_id` int(10) unsigned NOT NULL DEFAULT '0',
  `to_id` int(10) unsigned NOT NULL DEFAULT '0',
  `send_date` datetime DEFAULT NULL,
  `expire_date` datetime DEFAULT NULL,
  `view_date` datetime DEFAULT NULL,
  `create_date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `deleted` tinyint(1) unsigned NOT NULL DEFAULT '0',

  PRIMARY KEY (`message_glue_id`),
  KEY `outbox` (`from_id`,`message_id`),
  KEY `inbox` (`to_id`,`message_id`),
  KEY `created` (`create_date`),
  KEY `expire_date` (`expire_date`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci ;

*/

