<?php
/*
+---------------------------------------------------------------------------
|
|   gameplayer.class.php (php 5.x)
|
|   by Benjam Welker
|   http://iohelix.net
|
+---------------------------------------------------------------------------
|
|   > Game Player Extension module for WebRisk
|   > Date started: 2008-02-28
|
|   > Module Version Number: 0.8.0
|
+---------------------------------------------------------------------------
*/

// TODO: comments & organize better

class GamePlayer
	extends Player
{

	/**
	 *		PROPERTIES
	 * * * * * * * * * * * * * * * * * * * * * * * * * * */

	/** const property EXTEND_TABLE
	 *		Holds the player extend table name
	 *
	 * @var string
	 */
	const EXTEND_TABLE = T_WEBRISK;


	/** protected property allow_email
	 *		Flag shows whether or not to send emails to this player
	 *
	 * @var bool
	 */
	protected $allow_email;


	/** protected property invite_opt_out
	 *		Flag shows whether or not this player allows invites
	 *
	 * @var bool
	 */
	protected $invite_opt_out;


	/** protected property max_games
	 *		Number of games player can be in at one time
	 *
	 * @var int
	 */
	protected $max_games;


	/** protected property current_games
	 *		Number of games player is currently playing in
	 *
	 * @var int
	 */
	protected $current_games;


	/** protected property color
	 *		Holds the players skin color preference
	 *
	 * @var string
	 */
	protected $color;


	/** protected property wins
	 *		Holds the players win count
	 *
	 * @var int
	 */
	protected $wins;


	/** protected property kills
	 *		Holds the players kill count
	 *
	 * @var int
	 */
	protected $kills;


	/** protected property losses
	 *		Holds the players loss count
	 *
	 * @var int
	 */
	protected $losses;


	/** protected property last_online
	 *		Holds the date the player was last online
	 *
	 * @var int (unix timestamp)
	 */
	protected $last_online;



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

		// check and make sure we have logged into this game before
		if (0 != (int) $id) {
			$query = "
				SELECT COUNT(*)
				FROM ".self::EXTEND_TABLE."
				WHERE player_id = '{$id}'
			";
			$count = (int) $this->_mysql->fetch_value($query);

			if (0 == $count) {
				throw new MyException(__METHOD__.': '.GAME_NAME.' Player (#'.$id.') not found in database');
			}
		}

	 	parent::__construct($id);
	}


	/** public function __destruct
	 *		Class destructor
	 *		Gets object ready for destruction
	 *
	 * @param void
	 * @action destroys object
	 * @return void
	 */
	public function __destruct( )
	{
		return; // until i can figure out WTF?

		// save anything changed to the database
		// BUT... only if PHP didn't die because of an error
		$error = error_get_last( );

		if (0 == ((E_ERROR | E_WARNING | E_PARSE) & $error['type'])) {
			try {
				$this->_save( );
			}
			catch (MyException $e) {
				// do nothing, it will be logged
			}
		}
	}


	/** public function log_in
	 *		Runs the parent's log_in function
	 *		then, if success, tests game player
	 *		database to see if this player has been
	 *		here before, if not, it adds then to the
	 *		database, and if so, refreshes the last_online value
	 *
	 * @param void
	 * @action logs the player in
	 * @action optionally adds new game player data to the database
	 * @return bool success
	 */
	public function log_in( )
	{
		// this will redirect and exit upon failure
		parent::log_in( );

		// test an arbitrary property for existence, so we don't _pull twice unnecessarily
		// but don't test color, because it might actually be null when valid
		if (is_null($this->last_online)) {
			$this->_mysql->insert(self::EXTEND_TABLE, ['player_id' => $this->id]);

			$this->_pull( );
		}

		// don't update the last online time if we logged in as an admin
		if ( ! isset($_SESSION['admin_id'])) {
			$this->_mysql->insert(self::EXTEND_TABLE, ['last_online' => NULL], " WHERE player_id = '{$this->id}' ");
		}

		return true;
	}


	/** public function register
	 *		Registers a new player in the extend table
	 *		also calls the parent register function
	 *		which performs some validity checks
	 *
	 * @param void
	 * @action creates a new player in the database
	 * @return bool success
	 */
	public function register( )
	{
		call(__METHOD__);

		try {
			parent::register( );
		}
		catch (MyException $e) {
			call('Exception Thrown: '.$e->getMessage( ));
			throw $e;
		}

		if ($this->id) {
			// add the user to the table
			$this->_mysql->insert(self::EXTEND_TABLE, ['player_id' => $this->id]);

			// update the last_online time so we don't break things later
			$this->_mysql->insert(self::EXTEND_TABLE, ['last_online' => NULL], " WHERE player_id = '{$this->id}' ");
		}
	}


	/** public function add_win
	 *		Adds a win to this player's stats
	 *		both here, and in the database
	 *
	 * @param void
	 * @action adds a win in the database
	 * @return void
	 */
	public function add_win( )
	{
		$this->wins++;

		// note the trailing space on the field name, it's not a typo
		$this->_mysql->insert(self::EXTEND_TABLE, ['wins ' => 'wins + 1'], " WHERE player_id = '{$this->id}' ");
	}


	/** public function add_kill
	 *		Adds a kill to this player's stats
	 *		both here, and in the database
	 *
	 * @param void
	 * @action adds a kill in the database
	 * @return void
	 */
	public function add_kill( )
	{
		$this->kills++;

		// note the trailing space on the field name, it's not a typo
		$this->_mysql->insert(self::EXTEND_TABLE, ['kills ' => 'kills + 1'], " WHERE player_id = '{$this->id}' ");
	}


	/** public function add_loss
	 *		Adds a loss to this player's stats
	 *		both here, and in the database
	 *
	 * @param void
	 * @action adds a loss in the database
	 * @return void
	 */
	public function add_loss( )
	{
		$this->losses++;

		// note the trailing space on the field name, it's not a typo
		$this->_mysql->insert(self::EXTEND_TABLE, ['losses ' => 'losses + 1'], " WHERE player_id = '{$this->id}' ");
	}


	/** public function admin_delete
	 *		Deletes the given players from the players database
	 *
	 * @param mixed csv or array of player IDs
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

		$player_ids = Player::clean_deleted($player_ids);

		if ( ! $player_ids) {
			throw new MyException(__METHOD__.': No player IDs given');
		}

		$this->_mysql->delete(self::EXTEND_TABLE, " WHERE player_id IN (".implode(',', $player_ids).") ");
	}


	/** public function admin_add_admin
	 *		Gives the given players admin status
	 *
	 * @param mixed csv or array of player IDs
	 * @action gives the given players admin status
	 * @return void
	 */
	public function admin_add_admin($player_ids)
	{
		// make sure the user doing this is an admin
		if ( ! $this->is_admin) {
			throw new MyException(__METHOD__.': Player is not an admin');
		}

		array_trim($player_ids, 'int');
		$player_ids[] = 0; // make sure we have at least one entry

		// add the root admin, just for funsies
		if (isset($GLOBALS['_ROOT_ADMIN'])) {
			$query = "
				SELECT player_id
				FROM ".Player::PLAYER_TABLE."
				WHERE username = '{$GLOBALS['_ROOT_ADMIN']}'
			";
			$root_admin = (int) $this->_mysql->fetch_value($query);

			$player_ids[] = $root_admin;
		}

		$this->_mysql->insert(self::EXTEND_TABLE, ['is_admin' => 1], " WHERE player_id IN (".implode(',', $player_ids).") ");
	}


	/** public function admin_remove_admin
	 *		Removes admin status from the given players
	 *
	 * @param mixed csv or array of player IDs
	 * @action removes the given players admin status
	 * @return void
	 */
	public function admin_remove_admin($player_ids)
	{
		// make sure the user doing this is an admin
		if ( ! $this->is_admin) {
			throw new MyException(__METHOD__.': Player is not an admin');
		}

		array_trim($player_ids, 'int');
		$player_ids[] = 0; // make sure we have at least one entry

		// remove the root admin
		if (isset($GLOBALS['_ROOT_ADMIN'])) {
			$query = "
				SELECT player_id
				FROM ".Player::PLAYER_TABLE."
				WHERE username = '{$GLOBALS['_ROOT_ADMIN']}'
			";
			$root_admin = (int) $this->_mysql->fetch_value($query);

			if (in_array($root_admin, $player_ids)) {
				unset($player_ids[array_search($root_admin, $player_ids)]);
			}
		}

		// remove the player doing the removing
		if (array_key_exists('player_id', $_SESSION) && in_array($_SESSION['player_id'], $player_ids)) {
			unset($player_ids[array_search($_SESSION['player_id'], $player_ids)]);
		}

		// remove the admin doing the removing
		if (array_key_exists('admin_id', $_SESSION) && in_array($_SESSION['admin_id'], $player_ids)) {
			unset($player_ids[array_search($_SESSION['admin_id'], $player_ids)]);
		}

		$this->_mysql->insert(self::EXTEND_TABLE, ['is_admin' => 0], " WHERE player_id IN (".implode(',', $player_ids).") ");
	}


	/** public function save
	 *		Saves all changed data to the database
	 *
	 * @param void
	 * @action saves the player data
	 * @return void
	 */
	public function save( )
	{
		// update the player data
		$query = "
			SELECT allow_email
				, invite_opt_out
				, max_games
				, color
			FROM ".self::EXTEND_TABLE."
			WHERE player_id = '{$this->id}'
		";
		$player = $this->_mysql->fetch_assoc($query);

		if ( ! $player) {
			throw new MyException(__METHOD__.': Player data not found for player #'.$this->id);
		}

		// TODO: test the last online date and make sure we still have valid data

		$update_player = false;
		if ((bool) $player['allow_email'] != $this->allow_email) {
			$update_player['allow_email'] = (int) $this->allow_email;
		}

		if ((bool) $player['invite_opt_out'] != $this->invite_opt_out) {
			$update_player['invite_opt_out'] = (int) $this->invite_opt_out;
		}

		if ($player['max_games'] != $this->max_games) {
			$update_player['max_games'] = (int) $this->max_games;
		}

		if ($player['color'] != $this->color) {
			$update_player['color'] = $this->color;
		}

		if ($update_player) {
			$this->_mysql->insert(self::EXTEND_TABLE, $update_player, " WHERE player_id = '{$this->id}' ");
		}
	}


	/** protected function _pull
	 *		Pulls all game player data from the database
	 *		as well as the parent's data
	 *
	 * @param void
	 * @action pulls the player data
	 * @action pulls the game player data
	 * @return void
	 */
	protected function _pull( )
	{
		parent::_pull( );

		$query = "
			SELECT *
			FROM ".self::EXTEND_TABLE."
			WHERE player_id = '{$this->id}'
		";
		$result = $this->_mysql->fetch_assoc($query);

		if ( ! $result) {
// TODO: find out what is going on here and fix.
#			throw new MyException(__METHOD__.': Data not found in database (#'.$this->id.')');
return false;
		}

		$this->is_admin = ( ! $this->is_admin) ? (bool) $result['is_admin'] : true;

		$this->allow_email = (bool) $result['allow_email'];
		$this->invite_opt_out = (bool) $result['invite_opt_out'];
		$this->max_games = (int) $result['max_games'];
		$this->color = $result['color'];
		$this->wins = (int) $result['wins'];
		$this->kills = (int) $result['kills'];
		$this->losses = (int) $result['losses'];
		$this->last_online = strtotime($result['last_online']);

		// grab the player's current game count
		$query = "
			SELECT COUNT(*)
			FROM ".Game::GAME_PLAYER_TABLE." AS GP
				LEFT JOIN ".Game::GAME_TABLE." AS G
					USING (game_id)
			WHERE GP.player_id = '{$this->id}'
				AND GP.state NOT IN ('Resigned', 'Dead')
				AND G.state <> 'Finished'
		";
		$this->current_games = $this->_mysql->fetch_value($query);
	}



	/**
	 *		STATIC METHODS
	 * * * * * * * * * * * * * * * * * * * * * * * * * * */

	/** static public function get_list
	 *		Returns a list array of all game players
	 *		in the database
	 *		This function supersedes the parent's function and
	 *		just grabs the whole lot in one query
	 *
	 * @param bool $only_approved restrict to approved players
	 *
	 * @return array game player list (or bool false on failure)
	 */
	static public function get_list($only_approved = false)
	{
		$Mysql = Mysql::get_instance( );

		$WHERE = ($only_approved) ? " WHERE P.is_approved = 1 " : '';

		$query = "
			SELECT *
				, P.is_admin AS full_admin
				, E.is_admin AS half_admin
			FROM ".Player::PLAYER_TABLE." AS P
				INNER JOIN ".self::EXTEND_TABLE." AS E
					USING (player_id)
			{$WHERE}
			ORDER BY P.username
		";
		$list = $Mysql->fetch_array($query);

		return $list;
	}


	/** static public function get_count
	 *		Returns a count of all game players
	 *		in the database
	 *
	 * @param void
	 * @return int game player count
	 */
	static public function get_count( )
	{
		$Mysql = Mysql::get_instance( );

		$query = "
			SELECT COUNT(*)
			FROM ".self::EXTEND_TABLE." AS E
				JOIN ".Player::PLAYER_TABLE." AS P
					USING (player_id)
			WHERE P.is_approved = 1
			-- TODO: AND E.is_approved = 1
		";
		$count = $Mysql->fetch_value($query);

		return $count;
	}


	/** static public function get_maxed
	 *		Returns an array of all player IDs
	 *		who have reached their max games count
	 *
	 * @param void
	 * @return array of int player IDs
	 */
	static public function get_maxed( )
	{
		$Mysql = Mysql::get_instance( );

		$query = "
			SELECT COUNT(GP.game_id) AS game_count
				, GP.player_id
				, PE.max_games
			FROM ".Game::GAME_PLAYER_TABLE." AS GP
				LEFT JOIN ".Game::GAME_TABLE." AS G
					ON (G.game_id = GP.game_id)
				LEFT JOIN ".self::EXTEND_TABLE." AS PE
					ON (PE.player_id = GP.player_id)
			WHERE GP.state NOT IN ('Resigned', 'Dead')
				AND G.state <> 'Finished'
				AND PE.max_games <> 0
			GROUP BY GP.player_id
		";
		$maxed_players = $Mysql->fetch_array($query);

		$player_ids = [];
		foreach ($maxed_players as $data) {
			if ($data['game_count'] >= $data['max_games']) {
				$player_ids[] = $data['player_id'];
			}
		}

		return $player_ids;
	}


	/** static public function get_opt_out
	 *		Returns an array of all player ids
	 *		who have opted out of the invites
	 *
	 * @param void
	 * @return array of int player ids
	 */
	static public function get_opt_out( )
	{
		$Mysql = Mysql::get_instance( );

		$query = "
			SELECT player_id
			FROM ".self::EXTEND_TABLE."
			WHERE invite_opt_out = 1
		";
		$player_ids = $Mysql->fetch_value_array($query);

		return $player_ids;
	}


	/** static public function delete_inactive
	 *		Deletes the inactive users from the database
	 *
	 * @param int $age age in days
	 *
	 * @return void
	 */
	static public function delete_inactive($age)
	{
		call(__METHOD__);

		$Mysql = Mysql::get_instance( );

		$age = (int) abs($age);

		if (0 == $age) {
			return;
		}

		$exception_ids = [];

		// make sure the 'unused' player is not an admin
		$query = "
			SELECT EP.player_id
			FROM ".self::EXTEND_TABLE." AS EP
				JOIN ".Player::PLAYER_TABLE." AS P
					USING (player_id)
			WHERE P.is_admin = 1
				OR EP.is_admin = 1
		";
		$results = $Mysql->fetch_value_array($query);
		$exception_ids = array_merge($exception_ids, $results);

		// make sure the 'unused' player is not currently in a game
		$query = "
			SELECT DISTINCT player_id
			FROM ".Game::GAME_PLAYER_TABLE."
		";
		$results = $Mysql->fetch_value_array($query);
		$exception_ids = array_merge($exception_ids, $results);

		// make sure the 'unused' player isn't awaiting approval
		$query = "
			SELECT player_id
			FROM ".Player::PLAYER_TABLE."
			WHERE is_approved = 0
		";
		$results = $Mysql->fetch_value_array($query);
		$exception_ids = array_merge($exception_ids, $results);

		$exception_ids[] = 0; // don't break the IN clause
		$exception_id_list = implode(',', $exception_ids);

		// select unused accounts
		$query = "
			SELECT player_id
			FROM ".self::EXTEND_TABLE."
			WHERE wins + losses <= 2
				AND player_id NOT IN ({$exception_id_list})
				AND last_online < DATE_SUB(NOW( ), INTERVAL {$age} DAY)
		";
		$player_ids = $Mysql->fetch_value_array($query);
		call($player_ids);

		if ($player_ids) {
			Game::player_deleted($player_ids);
			$Mysql->delete(self::EXTEND_TABLE, " WHERE player_id IN (".implode(',', $player_ids).") ");
		}
	}

} // end of GamePlayer class


/*		schemas
// ===================================

--
-- Table structure for table `wr_wr_player`
--

DROP TABLE IF EXISTS `wr_wr_player`;
CREATE TABLE IF NOT EXISTS `wr_wr_player` (
  `player_id` int(11) unsigned NOT NULL DEFAULT '0',
  `is_admin` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `allow_email` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `invite_opt_out` tinyint(1) unsigned NOT NULL DEFAULT '1',
  `max_games` tinyint(2) unsigned NOT NULL DEFAULT '0',
  `color` varchar(25) NULL DEFAULT NULL,
  `wins` smallint(5) unsigned NOT NULL DEFAULT '0',
  `kills` smallint(5) unsigned NOT NULL DEFAULT '0',
  `losses` smallint(5) unsigned NOT NULL DEFAULT '0',
  `last_online` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,

  UNIQUE KEY `id` (`player_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci ;

*/

