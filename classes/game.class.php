<?php
/*
+---------------------------------------------------------------------------
|
|   game.class.php (php 5.x)
|
|   by Benjam Welker
|   http://iohelix.net
|
+---------------------------------------------------------------------------
|
|	This module is built to facilitate the game Risk, it doesn't really
|	care about how to play, or the deep goings on of the game, only about
|	database structure and how to allow players to interact with the game.
|
+---------------------------------------------------------------------------
|
|   > WebRisk Game module
|   > Date started: 2008-02-28
|
|   > Module Version Number: 0.8.0
|
+---------------------------------------------------------------------------
*/

// TODO: comments & organize better

require_once INCLUDE_DIR.'func.array.php';
require_once INCLUDE_DIR.'func.global.php';

define('LOG_TYPE', 0);
define('LOG_DATA', 1);

class Game
{

	/**
	 *		PROPERTIES
	 * * * * * * * * * * * * * * * * * * * * * * * * * * */

	/** const property GAME_TABLE
	 *		Holds the game table name
	 *
	 * @var string
	 */
	const GAME_TABLE = T_GAME;


	/** const property GAME_PLAYER_TABLE
	 *		Holds the game player glue table name
	 *
	 * @var string
	 */
	const GAME_PLAYER_TABLE = T_GAME_PLAYER;


	/** const property GAME_LAND_TABLE
	 *		Holds the game land glue table name
	 *
	 * @var string
	 */
	const GAME_LAND_TABLE = T_GAME_LAND;


	/** const property GAME_NUDGE_TABLE
	 *		Holds the game nudge table name
	 *
	 * @var string
	 */
	const GAME_NUDGE_TABLE = T_GAME_NUDGE;


	/** const property GAME_LOG_TABLE
	 *		Holds the game log table name
	 *
	 * @var string
	 */
	const GAME_LOG_TABLE = T_GAME_LOG;


	/** const property ROLL_LOG_TABLE
	 *		Holds the roll log table name
	 *
	 * @var string
	 */
	const ROLL_LOG_TABLE = T_ROLL_LOG;


	/** static protected property _PLAYER_DEFAULTS
	 *		Holds the default data for the players
	 *
	 * @var array
	 */
	static protected $_PLAYER_DEFAULTS = array(
			'order_num' => 0,
			'cards' => null,
			'armies' => 0,
			'state' => 'Waiting',
			'extra_info' => null,
			'move_date' => '0000-00-00 00:00:00',
		);


	/** static protected property _EXTRA_INFO_DEFAULTS
	 *		Holds the default extra info data
	 *
	 * @var array
	 */
	static protected $_EXTRA_INFO_DEFAULTS = array(
			'fortify' => true,
			'multiple_fortify' => false,
			'connected_fortify' => false,
			'place_initial_armies' => false,
			'initial_army_limit' => 0,
			'kamikaze' => false,
			'warmonger' => false,
			'nuke' => false,
			'turncoat' => false,
			'fog_of_war_armies' => 'all',
			'fog_of_war_colors' => 'all',
			'trade_number' => 0,
			'custom_trades' => array( ),
			'trade_card_bonus' => 2,
			'conquer_type' => 'none',
			'conquer_conquests_per' => 0,
			'conquer_per_number' => 0,
			'conquer_skip' => 0,
			'conquer_start_at' => 0,
			'conquer_minimum' => 1,
			'conquer_maximum' => 42,
			'custom_rules' => '',
		);


	/** static protected property _PLAYER_EXTRA_INFO_DEFAULTS
	 *		Holds the default extra info data for the players
	 *
	 * @var array
	 */
	static protected $_PLAYER_EXTRA_INFO_DEFAULTS = array(
			'conquered' => 0,
			'forced' => false,
			'get_card' => false,
			'occupy' => null,
			'round' => 1,
			'turn' => 1,
		);


	/** public property id
	 *		Holds the game's id
	 *
	 * @var int
	 */
	public $id;


	/** public property name
	 *		Holds the game's name
	 *
	 * @var string
	 */
	public $name;


	/** public property state
	 *		Holds the game's current state
	 *		can be one of 'Waiting', 'Placing', 'Playing', 'Finished'
	 *
	 * @var string (enum)
	 */
	public $state;


	/** public property paused
	 *		Holds the game's current pause state
	 *
	 * @var bool
	 */
	public $paused;


	/** public property passhash
	 *		Holds the game's password hash
	 *
	 * @var string
	 */
	public $passhash;


	/** public property create_date
	 *		Holds the game's create date
	 *
	 * @var int (unix timestamp)
	 */
	public $create_date;


	/** public property modify_date
	 *		Holds the game's modified date
	 *
	 * @var int (unix timestamp)
	 */
	public $modify_date;


	/** public property last_move
	 *		Holds the game's last move date
	 *
	 * @var int (unix timestamp)
	 */
	public $last_move;


	/** public property capacity
	 *		Holds the game's player capacity
	 *
	 * @var int
	 */
	public $capacity;


	/** public property watch_mode
	 *		Lets us know if we are just visiting this game
	 *
	 * @var bool
	 */
	public $watch_mode = false;


	/** protected property _host_id
	 *		Holds the game's host id
	 *
	 * @var int
	 */
	protected $_host_id;


	/** protected property _extra_info
	 *		Holds the extra game info
	 *
	 * @var array
	 */
	protected $_extra_info;


	/** protected property _players
	 *		Holds our player's object references
	 *		along with other game data
	 *
	 * @var array of player data
	 */
	protected $_players;


	/** protected property _risk
	 *		Holds the risk object reference
	 *
	 * @var array of Risk object reference
	 */
	protected $_risk;


	/**
	 * Flag to enable logging
	 *
	 * @var bool
	 */
	protected $_do_log = true;


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
	 * @param int $id optional game id
	 *
	 * @action instantiates object
	 *
	 * @return Game
	 * @throws MyException
	 */
	public function __construct($id = 0)
	{
		call(__METHOD__);

		ksort(self::$_PLAYER_EXTRA_INFO_DEFAULTS);

		$this->id = (int) $id;
		call($this->id);

		$this->_risk = new Risk($this->id);

		if (defined('DEBUG')) {
			$this->_DEBUG = DEBUG;
		}

		try {
			$this->_pull( );
		}
		catch (MyException $e) {
			throw $e;
		}
	}


	/** public function __destruct
	 *		Class destructor
	 *		Gets object ready for destruction
	 *
	 * @param void
	 *
	 * @action saves changed data
	 * @action destroys object
	 *
	 * @return void
	 */
	public function __destruct( )
	{
		// save anything changed to the database
		// BUT... only if PHP didn't die because of an error
		$error = error_get_last( );
		call($error);

		if (0 == ((E_ERROR | E_WARNING | E_PARSE) & $error['type'])) {
			try {
				$this->_save( );
			}
			catch (MyException $e) {
				// do nothing, it will be logged
			}
		}
	}


	/** public function __get
	 *		Class getter
	 *		Returns the requested property if the
	 *		requested property is not _private
	 *
	 * @param string $property property name
	 *
	 * @return mixed property value
	 * @throws MyException
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
	 * @param string $property property name
	 * @param mixed $value property value
	 *
	 * @action optional validation
	 *
	 * @return bool success
	 * @throws MyException
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


	/**
	 * @param bool $do_log flag
	 * @return void
	 */
	public function set_do_log($do_log) {
		$this->_do_log = (bool) $do_log;
	}


	/** public function create
	 *		Creates a new Risk game
	 *
	 * @param void
	 *
	 * @action inserts a new game into the database
	 *
	 * @return int insert id
	 * @throws MyException
	 */
	public function create( )
	{
		call(__METHOD__);
		call($_POST);

		$_P = $_POST;

		// make sure the game has a name
		$_P['name'] = ('' != $_P['name']) ? $_P['name'] : '<No Name>';

		// translate (filter/sanitize) the data
		$_P['name'] = htmlentities($_P['name'], ENT_QUOTES, 'UTF-8', false);
		$_P['host_id'] = (int) $_P['player_id'];
		$_P['time_limit'] = (int) @$_P['time_limit'];
		$_P['allow_kibitz'] = (int) (isset($_P['allow_kibitz']) && ('yes' == $_P['allow_kibitz']));
		$_P['fortify'] = (isset($_P['fortify']) && ('yes' == $_P['fortify']));
		$_P['multiple_fortify'] = (isset($_P['multiple_fortify']) && ('yes' == $_P['multiple_fortify']));
		$_P['connected_fortify'] = (isset($_P['connected_fortify']) && ('yes' == $_P['connected_fortify']));
		$_P['place_initial_armies'] = (isset($_P['place_initial_armies']) && ('yes' == $_P['place_initial_armies']));
		$_P['initial_army_limit'] = (int) $_P['initial_army_limit'];
		$_P['kamikaze'] = (isset($_P['kamikaze']) && ('yes' == $_P['kamikaze']));
		$_P['warmonger'] = (isset($_P['warmonger']) && ('yes' == $_P['warmonger']));
		$_P['nuke'] = (isset($_P['nuke']) && ('yes' == $_P['nuke']));
		$_P['turncoat'] = (isset($_P['turncoat']) && ('yes' == $_P['turncoat']));
		$_P['conquer_type'] = strtolower($_P['conquer_type']);

		// fog of war cleanup
		$allowed_fogs = array(
			'all',
			'adjacent',
			'none',
		);

		if ( ! in_array($_P['fog_of_war_armies'], $allowed_fogs)) {
			$_P['fog_of_war_armies'] = 'all';
		}

		if ( ! in_array($_P['fog_of_war_colors'], $allowed_fogs)) {
			$_P['fog_of_war_colors'] = 'all';
		}

		// custom trades cleanup
		$trades = array( );
		// only run this if the trade box was open
		if (isset($_P['custom_trades_box'])) {
			foreach ((array) $_P['custom_trades'] as $key => $trade) {
				if ('NNN' == $key) {
					continue;
				}

				if (('' == trim($trade['start'])) || (0 > (int) $trade['start'])) {
					continue;
				}

				$trade = array_trim($trade, 'int');

				if ($trade['end'] || $trade['step'] || ! $trade['times']) {
					unset($trade['times']);
				}

				$trade = array_values($trade);
				$trades[] = $trade;
			}
		}
		call($_P['custom_trades']);
		call($trades);

		// conquer limits cleanup
		$allowed_conquer_types = array(
			'none',
			'trade_value',
			'trade_count',
			'rounds',
			'turns',
			'land',
			'continents',
			'armies',
		);

		// if we don't have a conquer type set, or the box was closed
		if ( ! in_array($_P['conquer_type'], $allowed_conquer_types) || ! isset($_P['conquer_limits_box'])) {
			$_P['conquer_type'] = 'none';
		}

		// clear out conquer data if we aren't using it
		if ('none' == $_P['conquer_type']) {
			$_P['conquer_conquests_per'] = 0;
			$_P['conquer_per_number'] = 0;
			$_P['conquer_skip'] = 0;
			$_P['conquer_start_at'] = 0;
			$_P['conquer_minimum'] = 1;
			$_P['conquer_maximum'] = 42;
		}

		call($_P);

		$extra_info = array(
			'fortify' => (bool) $_P['fortify'],
			'multiple_fortify' => (bool) $_P['multiple_fortify'],
			'connected_fortify' => (bool) $_P['connected_fortify'],
			'place_initial_armies' => (bool) $_P['place_initial_armies'],
			'initial_army_limit' => (int) $_P['initial_army_limit'],
			'kamikaze' => (bool) $_P['kamikaze'],
			'warmonger' => (bool) $_P['warmonger'],
			'nuke' => (bool) $_P['nuke'],
			'turncoat' => (bool) $_P['turncoat'],
			'fog_of_war_armies' => $_P['fog_of_war_armies'],
			'fog_of_war_colors' => $_P['fog_of_war_colors'],
			'trade_number' => 0,
			'custom_trades' => $trades,
			'trade_card_bonus' => (int) $_P['trade_card_bonus'],
			'conquer_type' => $_P['conquer_type'],
			'conquer_conquests_per' => (int) $_P['conquer_conquests_per'],
			'conquer_per_number' => (int) $_P['conquer_per_number'],
			'conquer_skip' => (int) $_P['conquer_skip'],
			'conquer_start_at' => (int) $_P['conquer_start_at'],
			'conquer_minimum' => (int) $_P['conquer_minimum'],
			'conquer_maximum' => (int) $_P['conquer_maximum'],
			'custom_rules' => htmlentities($_P['custom_rules'], ENT_QUOTES, 'UTF-8', false),
		);
		call($extra_info);

		// don't allow the conquer minimum to drop below 1, else the game stops
		if (isset($extra_info['conquer_minimum']) && (1 > $extra_info['conquer_minimum'])) {
			$extra_info['conquer_minimum'] = 1;
		}

		$extra_info = array_diff_recursive($extra_info, self::$_EXTRA_INFO_DEFAULTS);
		ksort($extra_info);

		call($extra_info);
		if ( ! empty($extra_info)) {
			$_P['extra_info'] = json_encode($extra_info);
		}

		// create the game
		$required = array(
			'host_id' ,
			'name' ,
			'capacity' ,
		);

		$key_list = array_merge($required, array(
			'password' ,
			'time_limit' ,
			'allow_kibitz' ,
			'extra_info' ,
		));

		try {
			$_DATA = array_clean($_P, $key_list, $required);
		}
		catch (MyException $e) {
			throw $e;
		}

		$_DATA['state'] = 'Waiting';
		$_DATA['create_date '] = 'NOW( )'; // note the trailing space in the field name, this is not a typo
		$_DATA['modify_date '] = 'NOW( )'; // note the trailing space in the field name, this is not a typo

		if ('' != $_POST['password']) {
			$_DATA['password'] = $this->_hash_pass($_POST['password']);
		}
		else {
			$_DATA['password'] = null;
		}

		// THIS IS THE ONLY PLACE IN THE CLASS WHERE IT BREAKS THE _pull / _save MENTALITY
		// BECAUSE THE INSERT ID IS NEEDED FOR THE REST OF THE GAME FUNCTIONALITY

		$Mysql = Mysql::get_instance( );
		$insert_id = $Mysql->insert(self::GAME_TABLE, $_DATA);

		if (empty($insert_id)) {
			throw new MyException(__METHOD__.': Game could not be created');
		}

		$this->id = $insert_id;

		// set the modified date
		$Mysql->insert(self::GAME_TABLE, array('modify_date' => NULL), " WHERE game_id = '{$this->id}' ");

		// pull the fresh data
		$this->_pull( );

		// now add the host player to the game
		$this->join( );

		return $this->id;
	}


	/** public function get_avail_colors
	 *		Returns an array of the colors still available
	 *
	 * @param void
	 *
	 * @return array of colors
	 */
	public function get_avail_colors( )
	{
		call(__METHOD__);

		$colors = array(
			'red' ,
			'blue' ,
			'green' ,
			'yellow' ,
			'brown' ,
			'black' ,
//			'purple' ,
		);

		$used_colors = array( );
		if (is_array($this->_players)) {
			foreach ($this->_players as $player) {
				$used_colors[] = $player['color'];
			}
		}

		$avail_colors = array_diff($colors, $used_colors);

		return $avail_colors;
	}


	/** public function is_player
	 *		Tests if the given player is already in this game or not
	 *
	 * @param int $player_id player id
	 *
	 * @return bool is player in game
	 */
	public function is_player($player_id)
	{
		call(__METHOD__);

		$player_id = (int) $player_id;

		if (isset($this->_players[$player_id])) {
			return true;
		}

		return false;
	}


	/** public function is_host
	 *		Tests if the given player is the game host
	 *
	 * @param int $player_id player id
	 *
	 * @return bool is host
	 */
	public function is_host($player_id)
	{
		call(__METHOD__);

		$player_id = (int) $player_id;

		if ($player_id == $this->_host_id) {
			return true;
		}

		return false;
	}


	/** public function join
	 *		Joins a game that is waiting
	 *
	 * @param void
	 *
	 * @action inserts the join into the database
	 *
	 * @return void
	 * @throws MyException
	 */
	public function join( )
	{
		call(__METHOD__);

		$this->_test_capacity( );

		$Mysql = Mysql::get_instance( );

		// check the game state
		if ('Waiting' != $this->state) {
			throw new MyException(__METHOD__.': Player #'.$_POST['player_id'].' tried to join non-waiting game #'.$this->id, 211);
		}

		// check the password
		if ( ! empty($this->passhash) && ! isset($_POST['password'])) {
			throw new MyException(__METHOD__.': Game password was not included with POST data', 212);
		}
		elseif ( ! empty($this->passhash)) {
			// test the password
			if (0 != strcmp($this->_hash_pass($_POST['password']), $this->passhash)) {
				throw new MyException(__METHOD__.': Game password is incorrect', 213);
			}
		}

		$_P = $_POST;

		$_P['player_id'] = (int) $_P['player_id'];
		$_P['color'] = strtolower($_P['color']);


		// check the color
		// (two people may have tried to join at the same time, and used the same color)
		$query = "
			SELECT COUNT(*)
			FROM `".self::GAME_PLAYER_TABLE."` AS `GP`
			WHERE `GP`.`color` = :color
				AND `GP`.`game_id` = :game_id
		";
		$params = array(
			':color' => $_P['color'],
			':game_id' => $this->id,
		);
		$used = $Mysql->fetch_value($query, $params);

		if ($used) {
			throw new MyException(__METHOD__.': Game player color ('.$_P['color'].') already used', 214);
		}

		$required = array(
			'player_id' ,
			'color' ,
		);

		try {
			$_DATA = array_clean($_P, $required, $required);
		}
		catch (MyException $e) {
			throw $e;
		}

		$_DATA['game_id'] = $this->id;
		$_DATA['state'] = 'Waiting';

		if ( ! $this->is_player($_DATA['player_id'])) {
			$this->_set_player_data($_DATA);
		}

		$this->_test_capacity( );
	}


	/** public function invite
	 *		Invite players to a game that is waiting
	 *
	 * @param void
	 *
	 * @action send emails to the invited players
	 *
	 * @return array player ids the email was sent to
	 * @throws MyException
	 */
	public function invite( )
	{
		call(__METHOD__);

		$this->_test_capacity( );

		// check the game state
		if ('Waiting' != $this->state) {
			throw new MyException(__METHOD__.': Invitations sent to join closed game #'.$this->id);
		}

		$player_ids = array_trim($_POST['player_ids'], 'int');

		if ( ! count($player_ids)) {
			return false;
		}

		// send the emails
		Email::send('invite', $player_ids, array('game_id' => $this->id, 'name' => $this->name, 'extra_text' => htmlentities($_POST['extra_text'], ENT_QUOTES, 'UTF-8', false)));

		return $player_ids;
	}


	/** public function start
	 *		Starts a game that is waiting
	 *
	 * @param int $player_id (for verification)
	 *
	 * @action sets the game to start in the database
	 *
	 * @return bool success
	 * @throws MyException
	 */
	public function start($player_id)
	{
		call(__METHOD__);

		if ($this->paused) {
			throw new MyException(__METHOD__.': Trying to perform an action on a paused game');
		}

		if ('Waiting' != $this->state) {
			throw new MyException(__METHOD__.': Trying to start a game (#'.$this->id.') that is not \'Waiting\'');
		}

		if ($this->_host_id != (int) $player_id) {
			throw new MyException(__METHOD__.': Player (#'.$player_id.') attempting to start the game (#'.$this->id.') is not the game host');
		}

		// make sure there are at least two players
		if (2 > count($this->_players)) {
			throw new MyException(__METHOD__.': Player attempting to start the game (#'.$this->id.') without enough players.');
		}

		// get the data we need to start the game
		$num_armies = $this->_risk->get_start_armies( );

		// randomly order the players
		$player_ids = array_keys($this->_players);
		shuffle($player_ids);

		$i = 0;
		foreach ($player_ids as $player_id) {
			++$i;
			$this->_risk->players[$player_id]['state'] = 'Placing';
			$this->_risk->players[$player_id]['armies'] = $num_armies;
			$this->_risk->players[$player_id]['order_num'] = $i;
			$this->_risk->players[$player_id]['last_move'] = null;
		}

		$this->_risk->order_players( );

		try {
			$this->_risk->init_random_board( );
		}
		catch (MyException $e) {
			throw $e;
		}

		$land_count = array( );
		foreach ($player_ids as $player_id) {
			$land_count[$player_id] = count($this->_risk->get_players_territory($player_id));
		}
		call($land_count);

		// make sure our limit is high enough to allow placement of all armies
		$min_count = min($land_count);
		if (0 != $this->_extra_info['initial_army_limit']) {
			// we need to account for the armies already on the board (1 in each)
			// so add $min_count to $my_armies when testing
			while (($min_count * $this->_extra_info['initial_army_limit']) < ($num_armies + $min_count)) {
				++$this->_extra_info['initial_army_limit'];
			}

			// update the Risk initial army count
			$this->_calculate_trade_values( );
			$this->_risk->set_extra_info($this->_extra_info);
		}

		// set the game state
		$this->state = 'Placing';

		if ($this->_extra_info['place_initial_armies']) {
			$this->_risk->place_start_armies( );
			$this->_test_placing( );
		}
		else {
			Email::send('start', $player_ids, array('game_id' => $this->id, 'name' => $this->name));
		}

		return true;
	}


	/** public function trade_cards
	 *		Trades cards for more armies
	 *
	 * @param int $player_id player id
	 * @param array $card_ids card ids to trade
	 * @param int $bonus_card bonus land id
	 *
	 * @action saves the game
	 *
	 * @return void
	 * @throws MyException
	 */
	public function trade_cards($player_id, $card_ids, $bonus_card = null)
	{
		call(__METHOD__);

		if ($this->paused) {
			throw new MyException(__METHOD__.': Trying to perform an action on a paused game');
		}

		$player_id = (int) $player_id;

		if (empty($player_id)) {
			throw new MyException(__METHOD__.': Missing required argument');
		}

		try {
			if ($this->_risk->trade_cards($card_ids, $bonus_card)) {
				++$this->_extra_info['trade_number'];
			}
		}
		catch (MyException $e) {
			throw $e;
		}
	}


	/** public function get_cards
	 *		Returns the cards owned by the given player
	 *
	 * @param int $player_id player id
	 *
	 * @return string card values
	 * @throws MyException
	 */
	public function get_cards($player_id)
	{
		call(__METHOD__);

		$player_id = (int) $player_id;

		if (empty($player_id)) {
			throw new MyException(__METHOD__.': Missing required argument');
		}

		if ( ! isset($this->_risk->players[$player_id])) {
			throw new MyException(__METHOD__.': Player (#'.$player_id.') is not a part of game #'.$this->id);
		}

		$cards = $this->_risk->players[$player_id]['cards'];

		$output = '';
		foreach ($cards as $card) {
			$type = card_type(Risk::$CARDS[$card][CARD_TYPE]);

			if ('Wild' != $type) {
				$type .= ' - '.Risk::$TERRITORIES[$card][NAME];
			}

			$output .= $type."\n";
		}

		return ('' == $output) ? 'No cards' : $output;
	}


	/** public function place_armies
	 *		Places armies on a territory
	 *
	 * @param int $player_id
	 * @param int $num_armies
	 * @param int $land_id
	 * @param bool $skip_pause optional
	 *
	 * @action saves the game
	 *
	 * @return void
	 * @throws MyException
	 */
	public function place_armies($player_id, $num_armies, $land_id, $skip_pause = false)
	{
		call(__METHOD__);

		if ($this->paused && ! $skip_pause) {
			throw new MyException(__METHOD__.': Trying to perform an action on a paused game');
		}

		$player_id = (int) $player_id;
		$num_armies = (int) $num_armies;
		$land_id = (int) $land_id;

		if (0 === $num_armies) {
			return;
		}

		if (empty($player_id) || empty($num_armies) || empty($land_id)) {
			throw new MyException(__METHOD__.': Missing required argument');
		}

		try {
			$this->_risk->place_armies($player_id, $num_armies, $land_id, 'Placing' == $this->state);
			$this->_test_armies($player_id);
		}
		catch (MyException $e) {
			throw $e;
		}
	}


	/** public function attack
	 *		ATTACK !!!
	 *
	 * @param int $player_id player id
	 * @param int $num_armies number of armies to attack with
	 * @param int $attack_land_id attack from land id
	 * @param int $defend_land_id attack to (defend) land id
	 *
	 * @action saves the game
	 *
	 * @return bool defeated
	 * @throws MyException
	 */
	public function attack($player_id, $num_armies, $attack_land_id, $defend_land_id)
	{
		call(__METHOD__);

		if ($this->paused) {
			throw new MyException(__METHOD__.': Trying to perform an action on a paused game');
		}

		$player_id = (int) $player_id;
		$num_armies = (int) $num_armies;
		$attack_land_id = (int) $attack_land_id;
		$defend_land_id = (int) $defend_land_id;

		if (empty($player_id) || empty($num_armies) || empty($attack_land_id) || empty($defend_land_id)) {
			throw new MyException(__METHOD__.': Missing required argument ('.$player_id.', '.$num_armies.', '.$attack_land_id.', '.$defend_land_id.')');
		}

		if ($player_id != $this->_risk->current_player) {
			throw new MyException(__METHOD__.': It is not player #'.$player_id.'s turn', 221);
		}

		try {
			list($defend_id, $defeated) = $this->_risk->attack($num_armies, $attack_land_id, $defend_land_id);
		}
		catch (MyException $e) {
			throw $e;
		}

		// check to see if we killed our opponent
		if (in_array($this->_risk->players[$defend_id]['state'], array('Resigned', 'Dead'))) {
			if ('Dead' == $this->_risk->players[$defend_id]['state']) {
				$this->_players[$defend_id]['object']->add_loss( );
				$this->_players[$player_id]['object']->add_kill( );
				Email::send('defeated', $defend_id, array('game_id' => $this->id, 'name' => $this->name, 'player' => $this->_players[$player_id]['object']->username));
			}

			$this->_test_winner( );
		}

		return $defeated;
	}


	/** public function attack_till_dead
	 *		Runs the attack function until either the defender is dead
	 *		or the attacker has no more attackable armies left
	 *
	 * @param int $player_id player id
	 * @param int $num_armies number of armies to attack with
	 * @param int $attack_land_id attack from land id
	 * @param int $defend_land_id attack to (defend) land id
	 *
	 * @return bool defeated
	 * @throws MyException
	 */
	public function attack_till_dead($player_id, $num_armies, $attack_land_id, $defend_land_id)
	{
		call(__METHOD__);

		if ($this->paused) {
			throw new MyException(__METHOD__.': Trying to perform an action on a paused game');
		}

		do {
			try {
				$defeated = $this->attack($player_id, $num_armies, $attack_land_id, $defend_land_id);
			}
			catch (MyException $e) {
				// check the exceptions, if they are excusable,
				// just go on, else, throw a new one
				$defeated = false;
				$excused = array(
					201, // player is out of attacking armies
					222, // player is in an incorrect state (switched because they ran out of attackable armies)
				);
				if ( ! in_array($e->getCode( ), $excused)) {
					throw $e;
				}
				else {
					break;
				}
			}
		}
		while ( ! $defeated);

		return $defeated;
	}


	/** public function attack_path
	 *		Runs the attack function for multiple territories along the given path
	 *		using attack till dead on each and fortifying the maximum amount each time
	 *		until the path is complete or the attacker has no more attackable armies left
	 *
	 * @param int $player_id player id
	 * @param int $num_armies number of armies to attack with
	 * @param int $attack_land_id attack from land id
	 * @param array $defend_land_ids if int attack to (defend) land ids
	 *
	 * @return bool defeated
	 * @throws MyException
	 */
	public function attack_path($player_id, $num_armies, $attack_land_id, $defend_land_ids)
	{
		call(__METHOD__);

		$defend_land_ids = array_trim($defend_land_ids, 'int');

		if ($this->paused) {
			throw new MyException(__METHOD__.': Trying to perform an action on a paused game');
		}

		$defeated = false;

		try {
			foreach ($defend_land_ids as $defend_land_id) {
				$defeated = $this->attack_till_dead($player_id, $num_armies, $attack_land_id, $defend_land_id);

				if ('Finished' == $this->state) {
					Flash::store('You have won the game !', false);
					break;
				}

				if ($defeated) {
					$this->occupy($player_id, 999999);
					$attack_land_id = $defend_land_id;
				}
				else {
					break;
				}
			}
		}
		catch (MyException $e) {
			throw $e;
		}

		return $defeated;
	}


	/** public function occupy
	 *		Occupies a recently defeated territory with the
	 *		given number of armies
	 *
	 * @param int $player_id
	 * @param int $num_armies to move
	 *
	 * @action saves the game
	 *
	 * @return void
	 * @throws MyException
	 */
	public function occupy($player_id, $num_armies)
	{
		call(__METHOD__);

		if ($this->paused) {
			throw new MyException(__METHOD__.': Trying to perform an action on a paused game');
		}

		$player_id = (int) $player_id;
		$num_armies = (int) $num_armies;

		if (empty($player_id) || empty($num_armies)) {
			throw new MyException(__METHOD__.': Missing required argument ('.$player_id.', '.$num_armies.')');
		}

		if ($player_id != $this->_risk->current_player) {
			throw new MyException(__METHOD__.': It is not player #'.$player_id.'s turn');
		}

		try {
			$this->_risk->occupy($num_armies);
		}
		catch (MyException $e) {
			throw $e;
		}
	}


	/** public function fortify
	 *		Occupies a recently defeated territory with the
	 *		given number of armies
	 *
	 * @param int $player_id
	 * @param int $num_armies to move
	 * @param int $from_land_id
	 * @param int $to_land_id
	 *
	 * @action saves the game
	 *
	 * @return void
	 * @throws MyException
	 */
	public function fortify($player_id, $num_armies, $from_land_id, $to_land_id)
	{
		call(__METHOD__);

		if ($this->paused) {
			throw new MyException(__METHOD__.': Trying to perform an action on a paused game');
		}

		$player_id = (int) $player_id;
		$num_armies = (int) $num_armies;
		$from_land_id = (int) $from_land_id;
		$to_land_id = (int) $to_land_id;

		if (empty($player_id) || empty($from_land_id) || empty($to_land_id)) {
			throw new MyException(__METHOD__.': Missing required argument');
		}

		if ($player_id != $this->_risk->current_player) {
			throw new MyException(__METHOD__.': It is not player #'.$player_id.'s turn');
		}

		try {
			$this->_risk->fortify($num_armies, $from_land_id, $to_land_id);
		}
		catch (MyException $e) {
			throw $e;
		}
	}


	/** public function resign
	 *		Resigns from the game
	 *
	 * @param int $player_id
	 * @param bool $skip_pause optional allow resignations in paused games
	 *
	 * @return void
	 *
	 * @throws MyException
	 */
	public function resign($player_id, $skip_pause = false)
	{
		call(__METHOD__);

		if ($this->paused && ! $skip_pause) {
			throw new MyException(__METHOD__.': Trying to perform an action on a paused game');
		}

		$player_id = (int) $player_id;

		if (empty($player_id)) {
			throw new MyException(__METHOD__.': Missing required argument');
		}

		// resign the player and add a loss
		try {
			$this->_risk->set_player_state('Resigned', $player_id);
			$this->_players[$player_id]['object']->add_loss( );
		}
		catch (MyException $e) {
			throw $e;
		}

		$this->_test_winner( );
	}


	/** public function force_resign
	 *		Forces a resignation from the game
	 *		Regardless of current state
	 *
	 * @param int $player_id player id
	 *
	 * @return void
	 */
	public function force_resign($player_id)
	{
		call(__METHOD__);

		$player_id = (int) $player_id;

		call($this->_risk->players[$player_id]);

		// do some things before we resign the player
		switch ($this->_risk->players[$player_id]['state']) {
			case 'Resigned' :
			case 'Dead' :
				// we're already out of the game
				return;
				break;

			case 'Trading' :
			case 'Attacking' :
				// set a state manually to bypass any force check
				$this->_risk->players[$player_id]['state'] = 'Fortifying';
				$this->_risk->set_player_state('Waiting', $player_id);
				break;

			case 'Placing' :
				// randomly place the players armies
				$land = $this->_risk->get_players_land($player_id);
				while ($this->_risk->players[$player_id]['armies']) {
					// place an army (2 at a time, so we're not here forever)
					$this->place_armies($player_id, 2, array_rand($land), $skip_pause = true);
				}

				// if the game was Placing, everything was done
				// do some extra things for a game that is Playing
				if ('Playing' == $this->state) {
					$this->_risk->players[$player_id]['state'] = 'Fortifying';
					$this->_risk->set_player_state('Waiting', $player_id);
				}
				break;

			case 'Occupying' :
				// perform the occupation
				$this->occupy($player_id, 999999);
				$this->_risk->players[$player_id]['state'] = 'Fortifying';
				$this->_risk->set_player_state('Waiting', $player_id);
				break;

			case 'Fortifying' :
				$this->_risk->set_player_state('Waiting', $player_id);
				break;

			default :
			// Waiting
				// do nothing
				break;
		}

		try {
			$this->resign($player_id, $skip_pause = true);
		}
		catch (MyException $e) {
			call($e);
			// do nothing
		}
	}


	/** public function skip
	 *		Skip the given player to the next state
	 *
	 * @param string $cur_state players current state
	 * @param int $player_id
	 *
	 * @return void
	 * @throws MyException
	 */
	public function skip($cur_state, $player_id)
	{
		call(__METHOD__);

		if ($this->paused) {
			throw new MyException(__METHOD__.': Trying to perform an action on a paused game');
		}

		$player_id = (int) $player_id;

		try {
			$this->_risk->set_player_next_state($cur_state, $player_id);
		}
		catch (MyException $e) {
			throw $e;
		}
	}


	/** public function nudge
	 *		Nudges the inactive players to make their moves
	 *
	 * @param int $nudger_id player id who nudged
	 *
	 * @return bool success
	 * @throws MyException
	 */
	public function nudge($nudger_id)
	{
		call(__METHOD__);

		if ($this->paused) {
			throw new MyException(__METHOD__.': Trying to perform an action on a paused game');
		}

		$Mysql = Mysql::get_instance( );

		$nudger = $this->_players[(int) $nudger_id]['object']->username;

		// because we added the nudge button to the placing screen, we can't use current player
		// we must find all players who are not waiting or dead and send to all of them
		$nudgable = $this->_test_nudge( );
		if (count($nudgable)) {
			Email::send('nudge', $nudgable, array('game_id' => $this->id, 'name' => $this->name, 'player' => $nudger));
			$Mysql->delete(self::GAME_NUDGE_TABLE, " WHERE game_id = '{$this->id}' ");

			$data = array( );
			foreach ($nudgable as $id) {
				$data[] = array('game_id' => $this->id, 'player_id' => $id);
			}
			$Mysql->multi_insert(self::GAME_NUDGE_TABLE, $data);

			return true;
		}

		return false;
	}


	/** public function get_visible_board
	 *		Filters the board data based on what
	 *		the current player can see
	 *
	 * @param int $observer_id optional
	 *
	 * @return array board data
	 */
	public function get_visible_board($observer_id = null)
	{
		call(__METHOD__);

		$observer_id = (int) $observer_id;

		if ( ! $observer_id) {
			$observer_id = $_SESSION['player_id'];
		}

		$board = $this->_risk->board;

		if (('adjacent' == $this->_extra_info['fog_of_war_armies']) || ('adjacent' == $this->_extra_info['fog_of_war_colors'])) {
			$adjacent = $this->_risk->get_adjacent_territories($observer_id);
		}

		foreach ($board as $land_id => & $data) { // mind the reference
			// don't show where other people have placed their armies until we start
			if (('Placing' == $this->state) && ($data['player_id'] != $observer_id)) {
				$data['armies'] = 1;
			}

			$data['color'] = $this->_players[$data['player_id']]['color'];
			$data['resigned'] = ('Resigned' == $this->_risk->players[$data['player_id']]['state']) ? ' res' : '';

			if ( ! $this->_DEBUG && true) {
				// clear out for fog of war
				// IF this is not our land AND (
				//		the fog is everywhere
				//		OR the fog shows adjacent AND we are not adjacent
				// )
				if (($data['player_id'] != $observer_id) && (
					('none' == $this->_extra_info['fog_of_war_armies'])
					|| (('adjacent' == $this->_extra_info['fog_of_war_armies']) && ! in_array($land_id, $adjacent))
				)) {
					$data['armies'] = '?';
				}

				// and again for colors
				if (($data['player_id'] != $observer_id) && (
					('none' == $this->_extra_info['fog_of_war_colors'])
					|| (('adjacent' == $this->_extra_info['fog_of_war_colors']) && ! in_array($land_id, $adjacent))
				)) {
					$data['color'] = 'gray';
					$data['resigned'] = '';
				}
			}
		}
		unset($data); // kill the reference

		return $board;
	}


	/** public function get_players_visible_continents
	 *		Filters the continent data based on what
	 *		the current player can see
	 *
	 * @param int $observer_id optional
	 *
	 * @return array continent data
	 */
	public function get_players_visible_continents($player_id, $observer_id = null)
	{
		call(__METHOD__);

		$observer_id = (int) $observer_id;

		if ( ! $observer_id) {
			$observer_id = $_SESSION['player_id'];
		}

		$continents = $this->_risk->get_players_continents($player_id);

		if ( ! $continents) {
			return array( );
		}

		$visible_board = $this->get_visible_board($observer_id);

		foreach ($continents as $cont_id => $continent) {
			foreach ($continent[2] as $land_id) {
				if ('gray' == $visible_board[$land_id]['color']) {
					unset($continents[$cont_id]);
					continue 2;
				}
			}
		}

		return $continents;
	}


	/** public function draw_players
	 *		Generates the html for the game player list
	 *
	 * @param void
	 *
	 * @return string html
	 */
	public function draw_players( )
	{
		$players = array( );
		foreach ($this->_risk->players as $player_id => $player) {
			$players[$player_id]['player_id'] = $player_id;
			$players[$player_id]['color'] = $this->_players[$player_id]['color'];
			$players[$player_id]['username'] = $this->_players[$player_id]['object']->username;
			$players[$player_id]['num_cards'] = count($player['cards']);
			$players[$player_id]['state'] = $player['state'];
			$order[$player_id] = $player['order_num'];
		}

		// sort the players by order
		asort($order);
		$temp_players = array( );
		foreach ($order as $player_id => $order_num) {
			$temp_players[$player_id] = $players[$player_id];
		}
		$players = $temp_players;

		$html = '
			<div id="players">
				<ul>';

		foreach ($players as $player_id => $player) {
			$id_box = '';
			if ( ! empty($_SESSION['admin_id']) || $GLOBALS['Player']->is_admin) {
				$id_box = ' ['.$player_id.']';
			}

			$html .= '
					<li id="p_'.$player_id.'" class="'.substr($player['color'], 0, 3).(($player_id == $_SESSION['player_id']) ? ' me' : '').(($player_id == $this->_host_id) ? ' host' : '').' '.strtolower($player['state']).'" title="'.$player['state'].$id_box.'"><span class="cards">'.$player['num_cards'].'</span>'.$player['username'].'</li>';
		}

		$html .= '
				</ul>
			</div>';

		return $html;
	}


	/** public function draw_action
	 *		Generates the html for the game action box
	 *
	 * @param void
	 *
	 * @return string html
	 * @throws MyException
	 */
	public function draw_action( )
	{
		if ('Finished' == $this->state) {
			// TODO somehow this thing lost it's current player value...
			return '<div id="action">The game is over<br />'.$GLOBALS['_PLAYERS'][$this->_risk->current_player].' won</div>';
		}

		if ($this->watch_mode) {
			return '<div id="action">You are watching</div>';
		}

		if ($this->paused) {
			return '<div id="action">This game is paused</div>';
		}

		$html = '
			<div id="action">';

		$player_id = $_SESSION['player_id'];
		$state = $this->_risk->players[$player_id]['state'];

		$form_start = '
				<form method="post" action="'.$_SERVER['REQUEST_URI'].'" id="game_form"><div>
					<input type="hidden" name="token" id="token" value="'.$_SESSION['token'].'" />
					<input type="hidden" name="game_id" value="'.$this->id.'" />
					<input type="hidden" name="player_id" value="'.$player_id.'" />
					<input type="hidden" name="state" value="'.strtolower($state).'" />';

		$form_end = '
				</div></form>';

		$mine_select = '<option value="">Choose</option>';
		$players_territory = $this->_risk->get_players_territory($_SESSION['player_id']);
		foreach ($players_territory as $land_id => $land_name) {
			$mine_select .= '
					<option value="'.str_pad($land_id, 2, '0', STR_PAD_LEFT).'">'.$land_name.'</option>';
		}

		$theirs_select = '<option value="">Choose</option>';
		$others_territory = $this->_risk->get_others_territory($_SESSION['player_id']);
		foreach ($others_territory as $land_id => $land_name) {
			$theirs_select .= '
					<option value="'.str_pad($land_id, 2, '0', STR_PAD_LEFT).'">'.$land_name.'</option>';
		}

		$army_options = '<option>--</option>';
		for ($i = 1; $i <= 15; ++$i) {
			$army_options .= '<option>'.$i.'</option>';
		}

		for ($i = 20; $i <= 50; $i += 5) {
			$army_options .= '<option>'.$i.'</option>';
		}

		switch (strtolower($state)) {
			case 'waiting' :
				$nudge = '';
				if ($this->_test_nudge( )) {
					$nudge = '<input type="button" name="nudge" id="nudge" value="Nudge" />';
				}

				$html .= '
				<p>It is not your turn</p>
				'.$form_start;

				if ('Placing' == $this->state) {
					$html .= '
					<p>Wait while others finish placing their pieces</p>';
				}
				else {
					$html .= '
					<input class="resign" type="submit" name="submit" id="submit" value="Resign the Game" />';
				}

				$html .= '
					'.$nudge.'
				'.$form_end;

				break;

			case 'trading' :
				$html .= '
				<p>Trade matching cards</p>
				'.$form_start;

				$bonus_land = array( );
				$players_cards = (array) $this->_risk->get_players_cards($_SESSION['player_id']);

				// order the cards by type
				$order = array( );
				foreach ($players_cards as $card_id => $card) {
					$order[$card_id] = $card[CARD_TYPE];
				}
				asort($order);

				foreach ($order as $card_id => $null) {
					$card = $players_cards[$card_id];
					$type = card_type($card[CARD_TYPE]);

					if ('Wild' != $type) {
						$type = substr($type, 0, 3);
					}
					if ( $this->_extra_info['nuke'] || $this->_extra_info['turncoat']) {
                        		$turncoat_territory = $this->_risk->get_turncoat_territory($_SESSION['player_id']);
                 
                        			if (('Wild' != $type) && (array_key_exists($card_id, $turncoat_territory)) && (count($turncoat_territory) > 1)) {
                            			$bonus_land[$card_id] = Risk::$TERRITORIES[$card_id];
					    	} 
					}					
                    			if ( !$this->_extra_info['nuke'] && !$this->_extra_info['turncoat'] ) {
						if (('Wild' != $type) && (array_key_exists($card_id, $players_territory))) {
						$bonus_land[$card_id] = Risk::$TERRITORIES[$card_id];
					    	}
                    			}					

					$html .= '<div class="card"><label class="inline"><input type="checkbox" name="cards[]" value="'.$card_id.'" />'
								.$type.' - '.(('Wild' == $type) ? 'None' : shorten_territory_name(Risk::$TERRITORIES[$card_id][NAME])).'</label></div>';
				}

				if (0 != count($bonus_land)) {
					$html .= '
						<div><!-- <label for="bonus_card">Bonus Land</label> --><select name="bonus_card" id="bonus_card">';

					foreach ($bonus_land as $land_id => $land) {
						$html .= '
							<option value="'.$land_id.'">'.$land[NAME].'</option>';
					}

					$html .= '
						</select></div>';
				}

				$html .= '
					<div><input type="submit" name="submit" id="submit" value="Trade" />';

				if ( ! $this->_extra_info['warmonger'] && ! $this->_risk->players[$player_id]['extra_info']['forced']) {
					$html .= '
					<input class="placing" type="button" name="skip" id="skip" value="Skip to Placing" />';
				}

				$html .= '</div>';

				$html .= $form_end;
				break;

			case 'placing' :
				$html .= '
				<p>Place your armies</p>
				'.$form_start.'
					<p>You have <span id="armies">'.$this->_risk->players[$player_id]['armies'].'</span> armies.</p>
					<div><!-- <label for="num_armies">Armies</label> --><input type="text" name="num_armies" id="num_armies" size="5" /><select id="num_armies_options">'.$army_options.'</select></div>
					<div><!-- <label for="land_id">Territory</label> --><select name="land_id" id="land_id">'.$mine_select.'</select></div>
					<div><input type="submit" name="submit" id="submit" value="Place Armies" /></div>
				'.$form_end;
				break;

			case 'attacking' :
				$html .= '
				<p>Perform your attack</p>
				'.$form_start.'
					<div><label for="num_armies">Armies</label> <select name="num_armies" id="num_armies"><option>3</option><option>2</option><option>1</option></select><label class="inline"><input type="checkbox" name="till_dead" id="till_dead" /> Till dead</label></div>
					<div><!-- <label for="attack_id">Attack from</label> --><select name="attack_id" id="attack_id">'.$mine_select.'</select></div>
					<div><!-- <label for="defend_id">Attack to</label> --><select name="defend_id" id="defend_id">'.$theirs_select.'</select></div>
					<div><label class="inline"><input type="checkbox" name="use_attack_path" id="use_attack_path" /> Use Attack Path</label></div>
					<div><!-- <label for="attack_path">Attack Path</label> --><input type="text" name="attack_path" id="attack_path" value="" /></div>
					<div><input type="submit" name="submit" id="submit" value="Attack" />';

				if ( ! $this->_extra_info['kamikaze']) {
					$html .= '<input class="fortify" type="button" name="skip" id="skip" value="Skip to Fortify" />';
				}

				$html .= '</div>
				'.$form_end;
				break;

			case 'occupying' :
				$occupy_info = $this->_risk->players[$player_id]['extra_info']['occupy'];
				if (preg_match('/(\\d+):(\\d+)->(\\d+)/', $occupy_info, $matches)) {
					list($occupy_info, $num_required, $from_id, $to_id) = $matches;
					$num_available = $this->_risk->board[$from_id]['armies'] - 1;
				}
				else {
					throw new MyException(__METHOD__.': Occupy info was lost');
				}

				$num_armies_options = '';
				for ($i = $num_available; $i >= $num_required; --$i) {
					$num_armies_options .= '<option>'.$i.'</option>';
				}

				$html .= '
				<p>Move your armies</p>
				'.$form_start.'
					<div><label for="num_armies">Armies</label> <select name="num_armies" id="num_armies">'.$num_armies_options.'</select></div>
					<div><input type="submit" name="submit" id="submit" value="Occupy" /></div>
				'.$form_end;
				break;

			case 'fortifying' :
				$html .= '
				<p>Fortify your armies</p>
				'.$form_start.'
					<div><!-- <label for="num_armies">Armies</label> --><input type="text" name="num_armies" id="num_armies" size="5" /><select id="num_armies_options">'.$army_options.'</select></div>
					<div><!-- <label for="from_id">Move from</label> --><select name="from_id" id="from_id">'.$mine_select.'</select></div>
					<div><!-- <label for="to_id">Move to</label> --><select name="to_id" id="to_id">'.$mine_select.'</select></div>
					<div><input type="submit" name="submit" id="submit" value="Fortify" /><input class="finish" type="button" name="skip" id="skip" value="Finish Turn" /></div>
				'.$form_end;
				break;

			case 'dead' :
			default :
				$html .= '
				<p>You are dead</p>
				Better luck next time.';
				break;
		}

		$html .= '
			</div>';

		return $html;
	}


	/** public function get_players
	 *		Grabs the player array
	 *
	 * @param void
	 *
	 * @return array player data
	 */
	public function get_players( )
	{
		$players = $this->_players;
		foreach ($this->_risk->players as $player_id => $player) {
			$players[$player_id]['player_id'] = $player_id;
			$players[$player_id]['color'] = $this->_players[$player_id]['color'];
			$players[$player_id]['username'] = $this->_players[$player_id]['object']->username;
			$players[$player_id]['num_cards'] = count($player['cards']);
			$players[$player_id]['state'] = $player['state'];
			$players[$player_id]['order'] = $player['order_num'];
			unset($players[$player_id]['object']);
		}

		$players[$this->_host_id]['host'] = true;

		return $players;
	}


	/** public function get_players_visible_data
	 *		Grabs the visible player data
	 *
	 * @param int $observer_id optional
	 *
	 * @return array visible player data
	 */
	public function get_players_visible_data($observer_id = null)
	{
		call(__METHOD__);

		$observer_id = (int) $observer_id;

		if ( ! $observer_id) {
			$observer_id = $_SESSION['player_id'];
		}

		$visible_board = $this->get_visible_board($observer_id);

		// run through the visible board and count some things
		$visible_land = array( );
		foreach ($visible_board as $land) {
			if ( ! is_array($land)) {
				continue;
			}

			if ( ! isset($visible_land[$land['player_id']])) {
				$visible_land[$land['player_id']] = array(
					'player_id' => $land['player_id'],
					'resigned' => $land['resigned'],
					'land' => 0,
					'armies' => 0,
				);
			}

			$seen_gray = false;
			if ('gray' != $land['color']) {
				if ('?' != $land['armies']) {
					$visible_land[$land['player_id']]['armies'] += $land['armies'];
				}

				$visible_land[$land['player_id']]['land']++;
			}
			else {
				$seen_gray = true;
			}
		}

		$trade_value = $this->_risk->get_trade_value( );

		$temp_players = array( );
		$order = array( );
		foreach ($this->_players as $id => $player) {
			// make sure we have a board entry for everybody
			if ( ! isset($visible_land[$id])) {
				$visible_land[$id] = array(
					'player_id' => $id,
					'resigned' => '',
					'land' => '--',
					'armies' => '--',
				);
			}

			$temp_players[$id] = array(
				'player_id' => $player['player_id'],
				'username' => $player['object']->username,
				'color' => $player['color'],
				'move_date' => $player['move_date'],
				'order' => $this->_risk->players[$id]['order_num'],
				'state' => $this->_risk->players[$id]['state'],
				'round' => $this->_risk->players[$id]['extra_info']['round'],
				'turn' => $this->_risk->players[$id]['extra_info']['turn'],
			);

			$order[$id] = $temp_players[$id]['order'];
		}

		asort($order);

		$players = array( );
		foreach ($order as $id => $null) {
			$players[$id] = $temp_players[$id];
		}

		foreach ($players as $player_id => & $player) {
			// continents
			$player['conts'] = $this->get_players_visible_continents($player_id, $observer_id);

			$player['cont_names'] = array( );
			foreach ($player['conts'] as $cont) {
				$player['cont_names'][] = $cont[0];
			}
			$player['cont_list'] = count($player['cont_names']).((count($player['cont_names'])) ? ' - '.implode(', ', $player['cont_names']) : '');

			// territories
			$player['land'] = $visible_land[$player_id]['land'];

			// reinforcement armies
			$armies = floor($player['land'] / 3);
			$armies = (3 > $armies) ? 3 : $armies;

			foreach ($player['conts'] as $cont) {
				$armies += $cont[1];
			}

			$player['next_armies'] = $armies;

			// trade
			$player['next_trade'] = $trade_value;
			$player['next_armies_trade'] = $player['next_armies'] + $player['next_trade'];

			if (($player_id != $observer_id) && ('--' !== $player['land'])) {
				if ('none' == $this->_extra_info['fog_of_war_colors']) {
					$player['land'] = '???';
					$player['next_armies'] = '???';
					$player['next_armies_trade'] = '???';
					if ('0' == $player['cont_list']) {
						$player['cont_list'] = '???';
					}
				}
				elseif ( ! $seen_gray && ('adjacent' == $this->_extra_info['fog_of_war_colors'])) {
					$player['land'] .= ' + ?';
					$player['next_armies'] .= ' + ?';
					$player['next_armies_trade'] .= ' + ?';
					if ('0' == $player['cont_list']) {
						$player['cont_list'] = '0 + ? - ???';
					}
					else {
						$player['cont_list'] = preg_replace('/(\d) - ([a-z, ]+)/i', '$1 + ? - $2, ???', $player['cont_list']);
					}
				}
			}

			// cards
			$cards = $this->get_players_cards($player_id, $observer_id);

			if (false !== $cards) {
				$player['cards'] = $cards;
				$player['card_count'] = count($player['cards']);

				if (3 > $player['card_count']) {
					$player['trade_perc'] = '0.0 %';
					$player['next_armies_trade'] = '--';
				}
				elseif (3 == $player['card_count']) {
					$player['trade_perc'] = '42.3 %';
				}
				elseif (4 == $player['card_count']) {
					$player['trade_perc'] = '81.7 %';
				}
				else {
					$player['trade_perc'] = '100.0 %';
				}
			}
			else {
				$player['cards'] = array( );
				$player['card_count'] = '???';
				$player['trade_perc'] = '??? %';
				$player['next_armies_trade'] = '???';
			}

			// visible armies
			$player['armies'] = $visible_land[$player_id]['armies'];
			if (($player_id != $observer_id) && ('--' !== $player['armies'])) {
				if (('none' == $this->_extra_info['fog_of_war_armies']) || ('none' == $this->_extra_info['fog_of_war_colors'])) {
					$player['armies'] = '???';
				}
				elseif ( ! $seen_gray && (('adjacent' == $this->_extra_info['fog_of_war_armies']) || ('adjacent' == $this->_extra_info['fog_of_war_colors']))) {
					$player['armies'] .= ' + ?';
				}
			}

			if ('Dead' == $player['state']) {
				$player['cards'] = array( );
				$player['card_count'] = '--';
				$player['trade_perc'] = '--';
				$player['conts'] = array( );
				$player['cont_list'] = '--';
				$player['next_armies'] = '--';
				$player['next_armies_trade'] = '--';
			}
		}
		unset($player);

		$players[$this->_risk->current_player]['current_player'] = true;
		$players[$this->_host_id]['host'] = true;

		return $players;
	}


	/** public function get_player_state
	 *		Grabs the state of the given player from the risk class
	 *
	 * @param int $player_id
	 *
	 * @return string player state
	 */
	public function get_player_state($player_id)
	{
		if ( ! isset($this->_risk->players[(int) $player_id])) {
			return false;
		}

		return $this->_risk->players[(int) $player_id]['state'];
	}


	/** public function get_player_armies
	 *		Grabs the number of available armies for
	 *		the given player from the risk class
	 *
	 * @param int $player_id
	 *
	 * @return int available armies
	 */
	public function get_player_armies($player_id)
	{
		return (int) $this->_risk->players[(int) $player_id]['armies'];
	}


	/** public function get_land_armies
	 *		Grabs the number of armies on the given territory
	 *
	 * @param int $land_id
	 *
	 * @return int armies
	 */
	public function get_land_armies($land_id)
	{
		return (int) $this->_risk->board[(int) $land_id]['armies'];
	}


	/** public function get_players_cards
	 *		Grabs the cards for the given player
	 *		based on what the observer can see
	 *
	 * @param int $player_id
	 *
	 * @param int $observer_id optional
	 *
	 * @return array cards
	 */
	public function get_players_cards($player_id, $observer_id = null)
	{
		$observer_id = (int) $observer_id;

		if ( ! $observer_id) {
			$observer_id = $_SESSION['player_id'];
		}

		$cards = false;
		if ($player_id == $observer_id || true) { // TODO change this for fog on cards
			$cards = $this->_risk->get_players_cards($player_id);
		}

		return $cards;
	}


	/** public function get_dice
	 *		Grabs the values of the previous dice roll from the risk class
	 *
	 * @param void
	 *
	 * @return array dice values
	 */
	public function get_dice( )
	{
		return (array) $this->_risk->previous_dice;
	}


	/** public function get_trade_value
	 *		Grabs the next trade value from the risk class
	 *
	 * @param void
	 *
	 * @return int trade value
	 */
	public function get_trade_value( )
	{
		return $this->_risk->get_trade_value( );
	}


	/** public function get_type
	 *		Grabs the game type from the risk class
	 *
	 * @param void
	 *
	 * @return string game type
	 */
	public function get_type( )
	{
		return $this->_risk->get_type( );
	}


	/** public function get_extra_info
	 *		Grabs the game's extra info array
	 *
	 * @param void
	 *
	 * @return array extra info
	 */
	public function get_extra_info( )
	{
		return $this->_extra_info;
	}


	/** public function get_placement
	 *		Grabs the placement method
	 *
	 * @param void
	 *
	 * @return string placement method
	 */
	public function get_placement( )
	{
		return self::_get_placement($this->_extra_info);
	}


	/** static protected function _get_placement
	 *		Grabs the placement method
	 *
	 * @param array $data extra info
	 *
	 * @return string placement method
	 */
	static protected function _get_placement($data)
	{
		return ($data['place_initial_armies'] ? 'Random' : 'Manual');
	}


	/** public function get_placement_limit
	 *		Grabs the initial placement limit
	 *
	 * @param void
	 *
	 * @return int initial placement limit
	 */
	public function get_placement_limit( )
	{
		return self::_get_placement_limit($this->_extra_info);
	}


	/** static protected function _get_placement_limit
	 *		Grabs the initial placement limit
	 *
	 * @param array $data extra info
	 *
	 * @return int initial placement limit
	 */
	static protected function _get_placement_limit($data)
	{
		return (int) $data['initial_army_limit'];
	}


	/** public function get_fortify
	 *		Grabs the fortification method from the risk class and formats
	 *
	 * @param void
	 *
	 * @return string fortification method
	 */
	public function get_fortify( )
	{
		return self::_get_fortify($this->_extra_info);
	}


	/** static protected function _get_fortify
	 *		Grabs the fortification method from the risk class and formats
	 *
	 * @param array $data extra info
	 *
	 * @return string fortification method
	 */
	static protected function _get_fortify($data)
	{
		if ( ! $data['fortify']) {
			return 'None';
		}
		elseif ( ! $data['connected_fortify'] && ! $data['multiple_fortify']) {
			return 'Single';
		}
		elseif ($data['connected_fortify'] && ! $data['multiple_fortify']) {
			return 'Connected';
		}
		elseif ( ! $data['connected_fortify'] && $data['multiple_fortify']) {
			return 'Multiple';
		}
		elseif ($data['connected_fortify'] && $data['multiple_fortify']) {
			return 'Multiple Connected';
		}

		return 'Single';
	}


	/** public function get_kamikaze
	 *		Grabs the kamikaze method
	 *
	 * @param void
	 *
	 * @return string kamikaze method
	 */
	public function get_kamikaze( )
	{
		return self::_get_kamikaze($this->_extra_info);
	}


	/** static protected function _get_kamikaze
	 *		Grabs the kamikaze method
	 *
	 * @param array $data extra info
	 *
	 * @return string kamikaze method
	 */
	static protected function _get_kamikaze($data)
	{
		return ($data['kamikaze'] ? 'Yes' : 'No');
	}


	/** public function get_warmonger
	 *		Grabs the warmonger method
	 *
	 * @param void
	 *
	 * @return string warmonger method
	 */
	public function get_warmonger( )
	{
		return self::_get_warmonger($this->_extra_info);
	}


	/** static protected function _get_warmonger
	 *		Grabs the warmonger method
	 *
	 * @param array $data extra info
	 *
	 * @return string warmonger method
	 */
	static protected function _get_warmonger($data)
	{
		return ($data['warmonger'] ? 'Yes' : 'No');
	}



    /** public function get_nuke
	 *		Grabs the nuke method
	 *
	 * @param void
	 *
	 * @return string nuke method
	 */
	public function get_nuke( )
	{
		return self::_get_nuke($this->_extra_info);
	}
	
	
	 /** public function get_turncoat
	 *		Grabs the turncoat method
	 *
	 * @param void
	 *
	 * @return string turncoat method
	 */
	public function get_turncoat( )
	{
		return self::_get_turncoat($this->_extra_info);
	}
	

    /** static protected function _get_nuke
	 *		Grabs the nuke method
	 *
	 * @param array $data extra info
	 *
	 * @return string nuke method
	 */
	static protected function _get_nuke($data)
	{
		return ($data['nuke'] ? 'Yes' : 'No');
	}

	/** static protected function _get_turncoat
	 *		Grabs the turncoat method
	 *
	 * @param array $data extra info
	 *
	 * @return string turncoat method
	 */
	static protected function _get_turncoat($data)
	{
		return ($data['turncoat'] ? 'Yes' : 'No');
	}


	/** public function get_fog_of_war
	 *		Grabs the fog of war method
	 *
	 * @param void
	 *
	 * @return string fog of war method
	 */
	public function get_fog_of_war( )
	{
		return self::_get_fog_of_war($this->_extra_info);
	}


	/** static protected function _get_fog_of_war
	 *		Grabs the fog of war method
	 *
	 * @param array $data extra info
	 *
	 * @return string fog of war method
	 */
	static protected function _get_fog_of_war($data)
	{
		return array(
			'armies' => 'Show '.ucfirst($data['fog_of_war_armies']),
			'colors' => 'Show '.ucfirst($data['fog_of_war_colors']),
		);
	}


	/** public function get_round_conquer_limit
	 *		Grabs the conquer limit value
	 *		for the current player for this round
	 *
	 * @param void
	 *
	 * @return string conquer limit method
	 */
	public function get_round_conquer_limit( )
	{
		$extra_info = $this->_risk->get_extra_info( );
		return $extra_info['conquer_limit'];
	}


	/** public function get_remaining_conquer_limit
	 *		Grabs the conquer limit method
	 *
	 * @param void
	 *
	 * @return string conquer limit method
	 */
	public function get_remaining_conquer_limit( )
	{
		$conquer_limit = $this->get_round_conquer_limit( );
		$extra_info = $this->_risk->get_players_extra_info($_SESSION['player_id']);
		return $conquer_limit - $extra_info['conquered'];
	}


	/** public function get_conquer_limit
	 *		Grabs the conquer limit method
	 *
	 * @param void
	 *
	 * @return string conquer limit method
	 */
	public function get_conquer_limit( )
	{
		return self::_get_conquer_limit($this->_extra_info);
	}


	/** static protected function _get_conquer_limit
	 *		Grabs the conquer limit method
	 *
	 * @param array $data extra info
	 *
	 * @return string conquer limit method
	 */
	static protected function _get_conquer_limit($data)
	{
		if ('none' == $data['conquer_type']) {
			$return = 'None';
		}
		else {
			$return = $data['conquer_conquests_per'].' '.plural($data['conquer_conquests_per'], 'conquest');
			$return .= ' for every '.$data['conquer_per_number'].' '.plural($data['conquer_per_number'], human(singular($data['conquer_type'])));

			if ($data['conquer_skip']) {
				$return .= ' after '.$data['conquer_skip'].' multiples';
			}

			if ($data['conquer_start_at']) {
				$return .= ' starting from '.$data['conquer_start_at'];
			}

			if ($data['conquer_minimum']) {
				$return .= ', minimum '.$data['conquer_minimum'];
			}

			if ($data['conquer_maximum']) {
				$return .= ', maximum '.$data['conquer_maximum'];
			}
		}

		return $return;
	}


	/** public function get_custom_rules
	 *		Grabs the custom rules
	 *
	 * @param void
	 *
	 * @return string custom rules
	 */
	public function get_custom_rules( )
	{
		return self::_get_custom_rules($this->_extra_info);
	}


	/** static protected function _get_custom_rules
	 *		Grabs the custom rules
	 *
	 * @param void
	 *
	 * @return string custom rules
	 */
	static protected function _get_custom_rules($data)
	{
		return ('' != $data['custom_rules'] ? $data['custom_rules'] : 'None');
	}


	/** public function get_trade_card_bonus
	 *		Grabs the trade card bonus
	 *
	 * @param void
	 *
	 * @return int trade card bonus
	 */
	public function get_trade_card_bonus( )
	{
		return self::_get_trade_card_bonus($this->_extra_info);
	}


	/** static protected function _get_trade_card_bonus
	 *		Grabs the trade card bonus
	 *
	 * @param void
	 *
	 * @return int trade card bonus
	 */
	static protected function _get_trade_card_bonus($data)
	{
		return (isset($data['trade_card_bonus']) ? (int) $data['trade_card_bonus'] : 2);
	}


	/** public function get_trade_array
	 *		Grabs the custom trade array
	 *
	 * @param void
	 *
	 * @return array custom trade array
	 */
	public function get_trade_array( )
	{
		return self::_get_trade_array($this->_extra_info);
	}


	/** static protected function _get_trade_array
	 *		Grabs the custom trade array
	 *
	 * @param void
	 *
	 * @return array custom trade array
	 */
	static protected function _get_trade_array($data)
	{
		return (false != $data['custom_trades'] ? $data['custom_trades'] : array( ));
	}


	/** public function get_trade_count
	 *		Grabs the current trade count
	 *
	 * @param void
	 *
	 * @return int trade count
	 */
	public function get_trade_count( )
	{
		return self::_get_trade_count($this->_extra_info);
	}


	/** static protected function _get_trade_count
	 *		Grabs the current trade count
	 *
	 * @param void
	 *
	 * @return int trade count
	 */
	static protected function _get_trade_count($data)
	{
		return (int) $data['trade_number'];
	}


	/** protected function _pull
	 *		Pulls all game data from the database
	 *
	 * @param void
	 *
	 * @action pulls the game data
	 *
	 * @return void
	 * @throws MyException
	 */
	protected function _pull( )
	{
		call(__METHOD__);

		$Mysql = Mysql::get_instance( );

		$query = "
			SELECT G.*
			FROM ".self::GAME_TABLE." AS G
			WHERE G.game_id = '{$this->id}'
		";
		$result = $Mysql->fetch_assoc($query);
		call($result);

		if ((0 != $this->id) && ( ! $result)) {
			throw new MyException(__METHOD__.': Game data not found for game #'.$this->id);
		}

		if ($result) {
			$this->name = $result['name'];
			$this->state = $result['state'];
			$this->capacity = (int) $result['capacity'];
			$this->_host_id = (int) $result['host_id'];
			$this->create_date = strtotime($result['create_date']);
			$this->modify_date = strtotime($result['modify_date']);
			$this->paused = (bool) $result['paused'];
			$this->passhash = (string) $result['password'];

// temp fix for old serialized data
fix_extra_info($result['extra_info']);
			$this->_extra_info = array_merge_plus(self::$_EXTRA_INFO_DEFAULTS, json_decode($result['extra_info'], true));

			// pull the player data
			try {
				$this->_pull_players( );
			}
			catch (MyException $e) {
				throw $e;
			}
		}

		try {
			$this->_update_risk( );
		}
		catch (MyException $e) {
			throw $e;
		}
	}


	/** protected function _pull_players
	 *		Pulls all player data from the database
	 *
	 * @param void
	 * @action pulls the player data
	 * @return void
	 */
	protected function _pull_players( )
	{
		call(__METHOD__);

		$Mysql = Mysql::get_instance( );

		$query = "
			SELECT *
			FROM ".self::GAME_PLAYER_TABLE."
			WHERE game_id = '{$this->id}'
			ORDER BY move_date ASC
				, order_num ASC
		";
		$result = $Mysql->fetch_array($query);

		if ((0 != $this->id) && ! $result && ! isset($_POST['create'])) {
			throw new MyException(__METHOD__.': Player data not found for game #'.$this->id);
		}

		$last_move = 0;

		foreach ($result as $player) {
			// find out which one had the last move
			if (strtotime($last_move) < strtotime($player['move_date'])) {
				$last_move = $player['move_date'];
			}

			$this->_set_player_data($player);
		}

		$this->last_move = strtotime($last_move);
	}


	/** protected function _set_player_data
	 *		Adds a player to the game and risk data
	 *
	 * @param array $data player data
	 * @param int $count optional total player count
	 *
	 * @return void
	 * @throws MyException
	 */
	protected function _set_player_data($data, $count = null)
	{
		call(__METHOD__);

		$player = array_merge_plus(self::$_PLAYER_DEFAULTS, $data);

		if (empty($player['player_id'])) {
			throw new MyException(__METHOD__.': Missing player ID');
		}

// temp fix for old serialized data
fix_extra_info($player['extra_info']);
		$player['extra_info'] = array_merge_plus(self::$_PLAYER_EXTRA_INFO_DEFAULTS, json_decode($player['extra_info'], true));

		if ( ! empty($player['cards'])) {
			array_trim($player['cards'], 'int');
		}
		else {
			$player['cards'] = array( );
		}

		$player['game_id'] = $this->id;

		// if the player got deleted, show that
		try {
			$player['object'] = new GamePlayer($player['player_id']);
		}
		catch (MyException $e) {
			$player['object'] = new GamePlayer( );
			$player['object']->username = '[deleted]';
		}

		// move any data we need to over to the risk class player data
		$risk_player = $player;

		$player_keys = array(
			'player_id',
			'color',
			'move_date',
			'object',
		);

		$player = array_clean($player, $player_keys);

		$risk_player_keys = array(
			'player_id',
			'order_num',
			'cards',
			'armies',
			'state',
			'extra_info',
		);

		$risk_player = array_clean($risk_player, $risk_player_keys);

		$this->_players[$player['player_id']] = $player;
		$this->_risk->players[$player['player_id']] = $risk_player;
	}


	/** protected function _calculate_trade_values
	 *		Calculates the trade value array from the extra info
	 *
	 * @see self::calculate_trade_values
	 *
	 * @param void
	 *
	 * @action updates the Risk object
	 *
	 * @return void
	 */
	protected function _calculate_trade_values( )
	{
		call(__METHOD__);

		// parse the array
		$trades = self::calculate_trade_values($this->_extra_info['custom_trades']);

		// insert the trade values into the Risk class
		$this->_risk->set_trade_values($trades, $this->_extra_info['trade_card_bonus']);
	}


	/** protected function _update_risk
	 *		Updates the Risk object with the current game data
	 *
	 * @param void
	 *
	 * @action updates the Risk object
	 *
	 * @return void
	 * @throws MyException
	 */
	protected function _update_risk( )
	{
		call(__METHOD__);

		$Mysql = Mysql::get_instance( );

		if (0 == $this->id) {
			// no exception, just quit
			return false;
		}

		$this->_risk->new_player = false;

		// grab the game data
		$query = "
			SELECT game_type
			FROM ".self::GAME_TABLE."
			WHERE game_id = '{$this->id}'
		";
		$game_data = $Mysql->fetch_assoc($query);

		if ( ! $game_data) {
			throw new MyException(__METHOD__.': Game data not found for game #'.$this->id);
		}

		$this->_risk->set_game_type($game_data['game_type']);

		if ('Waiting' == $this->state) {
			// no exception, just quit
			return false;
		}

		// find out who the current player is
		if (('Playing' == $this->state) || ('Finished' == $this->state)) {
			foreach ($this->_risk->players as $player_id => $player) {
				if ( ! in_array($player['state'], array('Waiting', 'Resigned', 'Dead'))) {
					$this->_risk->current_player = $player_id;
					break;
				}
			}
		}
		else {
			$this->_risk->current_player = 0;
		}

		// grab the board data
		$query = "
			SELECT GL.*
			FROM ".self::GAME_LAND_TABLE." AS GL
			WHERE GL.game_id = '{$this->id}'
			ORDER BY land_id
		";
		$result = $Mysql->fetch_array($query);

		if ( ! $result) {
			throw new MyException(__METHOD__.': No Board data found for game #'.$this->id);
		}

		foreach ($result as $land) {
			$board[$land['land_id']] = array(
				'player_id' => $land['player_id'] ,
				'armies' => $land['armies'] ,
			);
		}

		$this->_risk->board = $board;

		$this->_calculate_trade_values( );
		$this->_risk->find_available_cards( );
		$this->_risk->set_extra_info($this->_extra_info);
	}


	/** protected function _save
	 *		Saves all changed data to the database
	 *
	 * @param void
	 *
	 * @action saves the game data
	 *
	 * @return void
	 * @throws MyException
	 */
	protected function _save( )
	{
		call(__METHOD__);

		if ( ! $this->id) {
			return;
		}

		$Mysql = Mysql::get_instance( );

		// send an email if we have to
		if ($this->_risk->new_player) {
			Email::send('turn', $this->_risk->current_player, array('game_id' => $this->id, 'name' => $this->name));
		}

		// make sure we don't have a MySQL error here, it may be causing the issues
		$run_once = false;
		do {
			if ($run_once) {
				// pause for 3 seconds, then try again
				sleep(3);
			}

			// update the game data
			$query = "
				SELECT extra_info
					, state
					, modify_date
				FROM ".self::GAME_TABLE."
				WHERE game_id = '{$this->id}'
			";
			$game = $Mysql->fetch_assoc($query);

			// make sure we don't have a MySQL error here, it may be causing the issues
			$error = $Mysql->error;
			$errno = preg_replace('/(\\d+)/', '$1', $error);

			$run_once = true;
		}
		while (2006 == $errno || 2013 == $errno);

		$update_modified = false;

		if ( ! $game) {
			throw new MyException(__METHOD__.': Game data not found for game #'.$this->id);
		}

		// test the modified date and make sure we still have valid data
		call($this->modify_date);
		call(strtotime($game['modify_date']));
		if ($this->modify_date != strtotime($game['modify_date'])) {
			$this->_log('== FAILED == DATA SAVE: #'.$this->id.' @ '.time( )."\n".' - '.$this->modify_date."\n".' - '.strtotime($game['modify_date']));
			throw new MyException(__METHOD__.': Trying to save game (#'.$this->id.') with out of sync data');
		}

		$update_game = false;
		if ($game['state'] != $this->state) {
			$update_game['state'] = $this->state;
		}

		$update_game['extra_info'] = array_diff_recursive($this->_extra_info, self::$_EXTRA_INFO_DEFAULTS);
		ksort($update_game['extra_info']);

		$update_game['extra_info'] = json_encode($update_game['extra_info']);

		if ('[]' == $update_game['extra_info']) {
			$update_game['extra_info'] = null;
		}

		if (0 === strcmp($game['extra_info'], $update_game['extra_info'])) {
			unset($update_game['extra_info']);
		}

		if ($update_game) {
			$update_modified = true;
			$Mysql->insert(self::GAME_TABLE, $update_game, " WHERE game_id = '{$this->id}' ");
		}

		// update the player's data
		$query = "
			SELECT *
			FROM ".self::GAME_PLAYER_TABLE."
			WHERE game_id = '{$this->id}'
		";
		$db_players = $Mysql->fetch_array($query);

		// add missing players
		$db_player_ids = array_shrink($db_players, 'player_id');

		if ( ! $db_player_ids) {
			$db_player_ids = array( );
		}

		$game_player_ids = array_keys($this->_players);
		$new_players = array_diff($game_player_ids, $db_player_ids);

		foreach ($new_players as $new_player_id) {
			$update_player = array(
				'game_id' => $this->id,
				'player_id' => $new_player_id,
				'color' => $this->_players[$new_player_id]['color'],
				'order_num' => $this->_risk->players[$new_player_id]['order_num'],
				'cards' => $this->_risk->players[$new_player_id]['cards'],
				'armies' => $this->_risk->players[$new_player_id]['armies'],
				'state' => $this->_risk->players[$new_player_id]['state'],
				'move_date' => null,
			);

			$update_player['cards'] = implode(',', $update_player['cards']);

			$update_player['extra_info'] = array_diff_assoc($this->_risk->players[$new_player_id]['extra_info'], self::$_PLAYER_EXTRA_INFO_DEFAULTS);
			ksort($update_player['extra_info']);
			$update_player['extra_info'] = json_encode($update_player['extra_info']);

			if ('[]' == $update_player['extra_info']) {
				$update_player['extra_info'] = null;
			}
			call($update_player);

			$Mysql->insert(self::GAME_PLAYER_TABLE, $update_player);

			$update_modified = true;
		}

		// check the player parts
		foreach ($db_players as $db_player) {
			$update_player = array( );
			$player_id = $db_player['player_id'];

			$risk_player = $this->_risk->players[$player_id];

			array_trim($db_player['cards'], 'int');

			foreach ($db_player['cards'] as $key => $card_id) {
				if (0 === $card_id) {
					unset($db_player['cards'][$key]);
				}
			}

			sort($db_player['cards']);

			$cards = $risk_player['cards'];
			sort($cards);

			if ((count($db_player['cards']) != count($cards)) || array_diff($db_player['cards'], $cards)) {
				$update_player['cards'] = implode(',', $cards);
			}

			if ($db_player['armies'] != $risk_player['armies']) {
				$update_player['armies'] = (int) $risk_player['armies'];
			}

			if ($db_player['state'] != $risk_player['state']) {
				$update_player['state'] = $risk_player['state'];
			}

			if ($db_player['order_num'] != $risk_player['order_num']) {
				$update_player['order_num'] = $risk_player['order_num'];
			}

			$risk_player['extra_info'] = array_diff_assoc($risk_player['extra_info'], self::$_PLAYER_EXTRA_INFO_DEFAULTS);
			ksort($risk_player['extra_info']);
			$risk_player['extra_info'] = json_encode($risk_player['extra_info']);

			if ('[]' == $risk_player['extra_info']) {
				$risk_player['extra_info'] = null;
			}

			if (0 !== strcmp($db_player['extra_info'], $risk_player['extra_info'])) {
				$update_player['extra_info'] = $risk_player['extra_info'];
			}

			// game was started, reset all player's move dates to now
			if (array_key_exists('move_date', $risk_player)) {
				$update_player['move_date'] = null;
			}

			if (count($update_player)) {
				$update_modified = true;
				$Mysql->insert(self::GAME_PLAYER_TABLE, $update_player, array('game_id' => $this->id, 'player_id' => $player_id));
			}
		}

		if ( ! $this->_extra_info['nuke'] || ! $this->_extra_info['turncoat'] ) {
			if ('Waiting' != $this->state) {
			// update the land data
			$query = "
				SELECT *
				FROM `".self::GAME_LAND_TABLE."`
				WHERE game_id = :game_id
			";
			$params = array(
				':game_id' => $this->id,
			);
			$db_lands = $Mysql->fetch_array($query, $params);

				if ( ! $db_lands) {
				$board = $this->_risk->board;

					foreach ($board as $land_id => $land) {
						$land['game_id'] = $this->id;
						$land['land_id'] = $land_id;
						$Mysql->insert(self::GAME_LAND_TABLE, $land);
					}

					$update_modified = true;
				}
				else {
					foreach ($db_lands as $db_land) {
						$update_land = array( );
						$land_id = $db_land['land_id'];

						$rland = $this->_risk->board[$land_id];

						if ($db_land['player_id'] != $rland['player_id']) {
							$update_land['player_id'] = $rland['player_id'];
						}

						if ($db_land['armies'] != $rland['armies']) {
							$update_land['armies'] = $rland['armies'];
						}

						if ($update_land) {
							$update_modified = true;
							$Mysql->insert(self::GAME_LAND_TABLE, $update_land, " WHERE game_id = '{$this->id}' AND land_id = '{$land_id}' ");
						}
					}
				}
			}
    	}
    else
    {
			// update the land data
			$query = "
				SELECT *
				FROM `".self::GAME_LAND_TABLE."`
				WHERE game_id = :game_id
			";
			$params = array(
				':game_id' => $this->id,
			);
			$db_lands = $Mysql->fetch_array($query, $params);

			if ( ! $db_lands) {
				$board = $this->_risk->board;

				foreach ($board as $land_id => $land) {
					$land['game_id'] = $this->id;
					$land['land_id'] = $land_id;
					$Mysql->insert(self::GAME_LAND_TABLE, $land);
				}

				$update_modified = true;
			}
			else {
				foreach ($db_lands as $db_land) {
					$update_land = array( );
					$land_id = $db_land['land_id'];

					$rland = $this->_risk->board[$land_id];

					if ($db_land['player_id'] != $rland['player_id']) {
						$update_land['player_id'] = $rland['player_id'];
					}

					if ($db_land['armies'] != $rland['armies']) {
						$update_land['armies'] = $rland['armies'];
					}

					if ($update_land) {
						$update_modified = true;
						$Mysql->insert(self::GAME_LAND_TABLE, $update_land, " WHERE game_id = '{$this->id}' AND land_id = '{$land_id}' ");
					}
				}
			}
		
    }

		// update the game modified date
		if ($update_modified) {
			$Mysql->insert(self::GAME_TABLE, array('modify_date' => NULL), " WHERE game_id = '{$this->id}' ");
		}
	}


	/** public static function log_deferred
	 *		defers a log to the database
	 *
	 * @param int $game_id
	 * @param string $log_data computer readable game message
	 *
	 * @return void
	 */
	public static function log_deferred($game_id, $log_data)
	{
		if (empty($GLOBALS['_log_messages'])) {
			$GLOBALS['_log_messages'] = array( );
		}

		if (empty($GLOBALS['_log_messages'][$game_id])) {
			$GLOBALS['_log_messages'][$game_id] = array( );
		}

		$GLOBALS['_log_messages'][$game_id][] = $log_data;
	}


	/** public static function log
	 *		logs the game message to the database
	 *
	 * @param int $game_id
	 * @param string $log_data computer readable game message
	 *
	 * @return void
	 */
	public static function log($game_id, $log_data) {
		if (0 === (int) $game_id) {
			return;
		}

		usleep(1000); // sleep for 1/1,000th of a second to prevent duplicate keys
		// because computers are just too fast now

		$Mysql = Mysql::get_instance( );

		$data = array(
			'game_id' => $game_id,
			'data' => $log_data,
		);

        // all this kerfuffle is because there is a very small but real
        // discrepancy between the values returned by date() and microtime()
        $now = microtime( );
        list($usec, $sec) = explode(' ', $now);
        $now = substr(bcadd($usec, $sec, strlen($usec) - 2), 0, -2);

		if (defined('SUPPORTS_MICROSECONDS') && SUPPORTS_MICROSECONDS) {
			$data['create_date'] = DateTime::createFromFormat('U.u', $now)->format('Y-m-d H:i:s.u');
		}
		$data['microsecond'] = $now;

		$Mysql->insert(self::GAME_LOG_TABLE, $data);
	}


	/** public static function process_deferred_log
	 *		processes the list of deferred log messages
	 *
	 * @param int $game_id
	 *
	 * @return void
	 */
	public static function process_deferred_log($game_id)
	{
		if (empty($GLOBALS['_log_messages'][$game_id])) {
			return;
		}

		foreach ($GLOBALS['_log_messages'][$game_id] as $log_message) {
			self::log($game_id, $log_message);
		}

		$GLOBALS['_log_messages'][$game_id] = array( );
	}


	/** static public function get_logs
	 *		Grabs the logs for this game from the database
	 *
	 * @param int $game_id
	 * @param bool $parse the logs into human readable form
	 *
	 * @return array log data
	 */
	static public function get_logs($game_id = 0, $parse = true)
	{
		$game_id = (int) $game_id;
		$parse = (bool) $parse;

		if (0 == $game_id) {
			return false;
		}

		$Mysql = Mysql::get_instance( );

		$query = "
			SELECT `extra_info`
			FROM `".self::GAME_TABLE."`
			WHERE `game_id` = '{$game_id}'
		";
		$extra_info = $Mysql->fetch_value($query);
		$extra_info = json_decode($extra_info, true);

		$trade_bonus = 2;
		if ( ! empty($extra_info['trade_card_bonus'])) {
			$trade_bonus = (int) $extra_info['trade_card_bonus'];
		}

		$query = "
			SELECT *
			FROM ".self::GAME_LOG_TABLE."
			WHERE game_id = '{$game_id}'
			ORDER BY create_date DESC
				, microsecond DESC
		";
		$return = $Mysql->fetch_array($query);

		// parse the logs
		if ($parse && $return) {
			$logs = array( );
			foreach ($return as $row) {
				$message = self::parse_move_info($row, $trade_bonus, $game_id, $logs);

#				call($message);
				$row['message'] = $message;

				$logs[] = $row;
			}

			$return = $logs;
		}

		return $return;
	}


	public static function parse_move_info($move, $trade_bonus = 2, $game_id = null, & $logs = false) {
		$move_data = (is_array($move) ? explode(' ', $move['data']) : explode(' ', $move));
		$data = explode(':', $move_data[LOG_DATA]);
#				call($data);

		$player = array( );
		for ($i = 0; $i < 3; ++$i) {
			if ( ! isset($data[$i])) {
				break;
			}

			if ( ! isset($GLOBALS['_PLAYERS'][$data[$i]])) {
				continue;
			}

			$player[$i] = htmlentities($GLOBALS['_PLAYERS'][$data[$i]], ENT_QUOTES, 'UTF-8', false);
			if ('' == $player[$i]) {
				$player[$i] = '[deleted]';
			}
		}

		$message = '';
		switch(strtoupper($move_data[LOG_TYPE])) {
			case 'A' : // Attack
//* TEMP FIX ----
// temp fix for what?   i forget... dammit
// guess it's not so temp anymore, is it...
if (isset($data[7])) {
	$data[2] = $data[3];
	$data[3] = $data[4];
	$data[4] = $data[5];
	$data[5] = $data[6];
	$data[6] = $data[7];
	unset($data[7]);
}
//*/
				list($attack_lost, $defend_lost) = explode(',', $data[5]);
				list($attack_roll, $defend_roll) = explode(',', $data[4]);

				if (is_array($logs)) {
					// we add a few log messages here, but make them in reverse
					// add the outcome
					$message = " - - OUTCOME: {$player[0]} [{$data[0]}] lost {$attack_lost}, {$player[2]} [{$data[2]}] lost {$defend_lost}";

					if ( ! empty($data[6])) {
						$message .= ' and was defeated';
					}

					$logs[] = array(
						'game_id' => $game_id,
						'message' => $message,
						'data' => null,
						'create_date' => ( ! empty($move['create_date']) ? $move['create_date'] : false),
					);

					// add the roll data
					$message = ' - - ROLL: attack = ' . implode(', ', str_split($attack_roll)) . '; defend = ' . implode(', ', str_split($defend_roll)) . ';';

					$logs[] = array(
						'game_id' => $game_id,
						'message' => $message,
						'data' => null,
						'create_date' => ( ! empty($move['create_date']) ? $move['create_date'] : false),
					);

					// make the attack announcement (gets saved below)
					$message = "ATTACK: {$player[0]} [{$data[0]}] with " . strlen($attack_roll) . " " . plural(strlen($attack_roll), 'army', 'armies') . " on " . shorten_territory_name(Risk::$TERRITORIES[$data[1]][NAME]) . " [{$data[1]}], attacked {$player[2]} [{$data[2]}] with " . strlen($defend_roll) . " " . plural(strlen($defend_roll), 'army', 'armies') . " on " . shorten_territory_name(Risk::$TERRITORIES[$data[3]][NAME]) . " [{$data[3]}]";
				}
				else {
					$message = "ATTACK: {$player[0]} [{$data[0]}] with " . strlen($attack_roll) . " " . plural(strlen($attack_roll), 'army', 'armies') . " on " . shorten_territory_name(Risk::$TERRITORIES[$data[1]][NAME]) . " [{$data[1]}], attacked {$player[2]} [{$data[2]}] with " . strlen($defend_roll) . " " . plural(strlen($defend_roll), 'army', 'armies') . " on " . shorten_territory_name(Risk::$TERRITORIES[$data[3]][NAME]) . " [{$data[3]}]";
					$message .= "\n\nROLL:\nattack = " . implode(', ', str_split($attack_roll)) . ";\ndefend = " . implode(', ', str_split($defend_roll)) . ';';
					$message .= "\n\n{$player[0]} [{$data[0]}] lost {$attack_lost},\n{$player[2]} [{$data[2]}] lost {$defend_lost}";

					if ( ! empty($data[6])) {
						$message .= ' and was defeated';
					}
				}
				break;

			case 'C' : // Card
				$message = "CARD: {$player[0]} [{$data[0]}] was given a card";
				if (is_null($game_id)) {
					$card = Risk::$CARDS[$data[1]];

					$card_type = card_type($card[CARD_TYPE]);
					$card_territory = ((0 !== $card[TERRITORY_ID]) ? ' - '.Risk::$TERRITORIES[$card[TERRITORY_ID]][NAME] : '');

					$message .= "\n({$card_type}{$card_territory})";
				}
				break;

			case 'D' : // Done (game over)
				$message = str_repeat('=', 10)." GAME OVER: {$player[0]} [{$data[0]}] wins !!! ".str_repeat('=', 10);
				break;

			case 'E' : // Eradicated (killed)
				$message = str_repeat('+ ', 5)."KILLED: {$player[0]} [{$data[0]}] eradicated {$player[1]} [{$data[1]}] from the board";

				if ('' != $data[2]) {
					$message .= ' and received '.count(explode(',', $data[2])).' cards';

					if (is_null($game_id)) {
						$message .= ":";

						$cards = array_trim($data[2]);

						foreach ($data[2] as $card_id) {
							$card = Risk::$CARDS[$card_id];

							$card_type = card_type($card[CARD_TYPE]);
							$card_territory = ((0 !== $card[TERRITORY_ID]) ? ' - '.Risk::$TERRITORIES[$card[TERRITORY_ID]][NAME] : '');

							$message .= "\n({$card_type}{$card_territory})";
						}
					}
				}
				break;

			case 'F' : // Fortify
				$message = "FORTIFY: {$player[0]} [{$data[0]}] moved {$data[1]} ".plural($data[1], 'army', 'armies')." from ".shorten_territory_name(Risk::$TERRITORIES[$data[2]][NAME])." [{$data[2]}] to ".shorten_territory_name(Risk::$TERRITORIES[$data[3]][NAME])." [{$data[3]}]";
				break;

			case 'I' : // Initialization
				$message = 'Board Initialized';
				break;

			case 'N' : // Next player
				$message = str_repeat('=', 5)." NEXT: {$player[0]} [{$data[0]}] is the next player ".str_repeat('=', 40);
				break;

			case 'O' : // Occupy
				$message = "OCCUPY: {$player[0]} [{$data[0]}] moved {$data[1]} ".plural($data[1], 'army', 'armies')." from ".shorten_territory_name(Risk::$TERRITORIES[$data[2]][NAME])." [{$data[2]}] to ".shorten_territory_name(Risk::$TERRITORIES[$data[3]][NAME])." [{$data[3]}]";
				break;

			case 'P' : // Placing
				$message = "PLACE: {$player[0]} [{$data[0]}] placed {$data[1]} ".plural($data[1], 'army', 'armies')." in ".shorten_territory_name(Risk::$TERRITORIES[$data[2]][NAME])." [{$data[2]}]";
				break;

			case 'Q' : // Quit (resign)
				$message = str_repeat('+ ', 5)."RESIGN: {$player[0]} [{$data[0]}] resigned the game";
				break;

			case 'R' : // Reinforcements
				$message = "REINFORCE: {$player[0]} [{$data[0]}] was given {$data[1]} ".plural($data[1], 'army', 'armies')." for {$data[2]} territories";
				if (isset($data[3])) {
					$data[3] = explode(',', $data[3]);

					foreach ($data[3] as $cont_id) {
						$message .= ', '.Risk::$CONTINENTS[$cont_id][NAME];
					}

					// if there were continents, use the word and just after the last comma,
					// unless there was only one continent, then replace the comma
					$one = (bool) (1 >= count($data[3]));
					$message = substr_replace($message, ' and', strrpos($message, ',') + (int) ! $one, (int) $one);
				}
				break;

			case 'T' : // Trade
		            	$message = "TRADE: {$player[0]} traded in cards for {$data[2]} ".plural($data[2], 'army', 'armies');
                
                		// add traded cards to message    
                		if (isset($data[1])) {
					$data[1] = explode(',', $data[1]);
					foreach ($data[1] as $card_id) {
		            			$message .= ' ( '.Risk::$TERRITORIES[$card_id][NAME] .' ) ';
					}
		
				}

			    	if ( 0 == (int) $data[4] && 0 == (int) $data[5] && (0 !== (int) $trade_bonus)) {	
			    		if ( ! empty($data[3]) && (0 !== (int) $trade_bonus)) {
						$message .= " and got {$trade_bonus} bonus armies on ".shorten_territory_name(Risk::$TERRITORIES[$data[3]][NAME])." [{$data[3]}]";
				    	}
				} else
				{
					if (1 == (int) $data[4] && 0 == (int) $data[5]){    
				 		$message .= " and nuked the armies on ".shorten_territory_name(Risk::$TERRITORIES[$data[3]][NAME])." [{$data[3]}]";
					}
					if (0 == (int) $data[4] && 1 == (int) $data[5]){    
			     			$message .= " and turned the armies on ".shorten_territory_name(Risk::$TERRITORIES[$data[3]][NAME])." ";
					}
					if (1 == (int) $data[4] && 1 == (int) $data[5]){    
			     			$message .= " and nuked and turned the armies on ".shorten_territory_name(Risk::$TERRITORIES[$data[3]][NAME])." ";
					}
				}
			   	
				break;

			case 'V' : // Value
				$message = "VALUE: The trade-in value was set to {$data[0]}";
				break;
		}

		return $message;
	}


	/** public static function log_roll
	 *		logs the roll to the database
	 *
	 * @param array $attack_roll
	 * @param array $defend_roll
	 *
	 * @return void
	 */
	public static function log_roll($attack_roll, $defend_roll)
	{
		$Mysql = Mysql::get_instance( );

		$insert = array( );
		foreach ($attack_roll as $i => $attack) {
			$insert['attack_'.($i + 1)] = $attack;
		}
		foreach ($defend_roll as $i => $defend) {
			$insert['defend_'.($i + 1)] = $defend;
		}

		$Mysql->insert(self::ROLL_LOG_TABLE, $insert);
	}


	/** static public function get_roll_stats
	 *		Grabs the roll stats from the database
	 *
	 * @param void
	 * @return array roll data
	 */
	static public function get_roll_stats( )
	{
		// for all variables with a 1v1, 3v2, etc.
		// the syntax is num_attack v num_defend

		$Mysql = Mysql::get_instance( );

		$WHERE['1v1'] = " (attack_2 IS NULL AND defend_2 IS NULL) ";
		$WHERE['2v1'] = " (attack_2 IS NOT NULL AND attack_3 IS NULL AND defend_2 IS NULL) ";
		$WHERE['3v1'] = " (attack_3 IS NOT NULL AND defend_2 IS NULL) ";
		$WHERE['1v2'] = " (attack_2 IS NULL AND defend_2 IS NOT NULL) ";
		$WHERE['2v2'] = " (attack_2 IS NOT NULL AND attack_3 IS NULL AND defend_2 IS NOT NULL) ";
		$WHERE['3v2'] = " (attack_3 IS NOT NULL AND defend_2 IS NOT NULL) ";

		// the theoretical probabilities
		// var syntax (dice_rolled)_(who_wins)
		// 1v1
		$theor['1v1']['attack'] = '0.4167'; // 41.67 %
		$theor['1v1']['defend'] = '0.5833'; // 58.33 %

		// 2v1
		$theor['2v1']['attack'] = '0.5787'; // 57.87 %
		$theor['2v1']['defend'] = '0.4213'; // 42.13 %

		// 3v1
		$theor['3v1']['attack'] = '0.6597'; // 65.97 %
		$theor['3v1']['defend'] = '0.3403'; // 34.03 %

		// 1v2
		$theor['1v2']['attack'] = '0.2546'; // 25.46 %
		$theor['1v2']['defend'] = '0.7454'; // 74.54 %

		// 2v2
		$theor['2v2']['attack'] = '0.2276'; // 22.76 %
		$theor['2v2']['defend'] = '0.4483'; // 44.83 %
		$theor['2v2']['both']   = '0.3241'; // 32.41 %

		// 3v2
		$theor['3v2']['attack'] = '0.3717'; // 37.17 %
		$theor['3v2']['defend'] = '0.2926'; // 29.26 %
		$theor['3v2']['both']   = '0.3358'; // 33.58 %

		$fights = array(
			'1v1', '2v1', '3v1',
			'1v2', '2v2', '3v2',
		);

		$wins = array('attack', 'defend', 'both');

		// grab our counts so we can run some stats
		$query = "
			SELECT COUNT(*)
			FROM `".self::ROLL_LOG_TABLE."`
		";
		$count['total'] = $Mysql->fetch_value($query);

		foreach ($fights as $fight) {
			$query = "
				SELECT COUNT(*)
				FROM `".self::ROLL_LOG_TABLE."`
				WHERE {$WHERE[$fight]}
			";
			$count[$fight] = $Mysql->fetch_value($query);
		}

		// now grab the actual percentages for wins and losses
		foreach ($fights as $fight) {
			foreach ($wins as $win) {
				// we only do 'both' on 2v2 and 3v2 fights
				if (('both' == $win) && ! in_array($fight, array('2v2', '3v2'))) {
					continue;
				}

				switch ($win) {
					case 'attack' :
						$query = "
							SELECT COUNT(*)
							FROM `".self::ROLL_LOG_TABLE."`
							WHERE {$WHERE[$fight]}
								AND attack_1 > defend_1
								AND (
									attack_2 > defend_2
									OR attack_2 IS NULL
									OR defend_2 IS NULL
								)
						";
						break;

					case 'defend' :
						$query = "
							SELECT COUNT(*)
							FROM `".self::ROLL_LOG_TABLE."`
							WHERE {$WHERE[$fight]}
								AND attack_1 <= defend_1
								AND (
									attack_2 <= defend_2
									OR attack_2 IS NULL
									OR defend_2 IS NULL
								)
						";
						break;

					case 'both' :
						$query = "
							SELECT COUNT(*)
							FROM `".self::ROLL_LOG_TABLE."`
							WHERE {$WHERE[$fight]}
								AND ((
										attack_1 > defend_1
										AND attack_2 <= defend_2
									)
									OR (
										attack_1 <= defend_1
										AND attack_2 > defend_2
									)
								)
						";
						break;
				}
				$value = $Mysql->fetch_value($query);

				$values[$fight][$win] = $value;
				$actual[$fight][$win] = (0 != $count[$fight]) ? $value / $count[$fight] : 0;
			}
		}

		return compact('count', 'values', 'theor', 'actual');
	}


	/** protected function _log
	 *		Report messages to a file
	 *
	 * @param string $message
	 *
	 * @action log messages to file
	 *
	 * @return void
	 */
	protected function _log($message)
	{
		// log the error
		if (false && class_exists('Log')) {
			Log::write($message, __CLASS__);
		}
	}


	/** protected function _test_capacity
	 *		Tests the capacity of the game
	 *		and starts the game if the game is full
	 *
	 * @param void
	 * @action optionally starts the game
	 * @return void
	 */
	protected function _test_capacity( )
	{
		call(__METHOD__);

		if ( ! $this->capacity) {
			throw new MyException(__METHOD__.': Capacity data not found for game #'.$this->id);
		}

		if ($this->capacity <= count($this->_players)) {
			$this->start($this->_host_id);
		}
	}


	/** protected function _test_armies
	 *		Tests the number of armies available to place for the given player
	 *		and sets the player to the next state based on game state
	 *
	 * @param int $player_id
	 *
	 * @action optionally sets the next state
	 * @action optionally saves the game
	 *
	 * @return void
	 * @throws MyException
	 */
	protected function _test_armies($player_id)
	{
		call(__METHOD__);

		$player_id = (int) $player_id;

		// grab the armies value from the class
		$armies = (int) $this->_risk->players[$player_id]['armies'];

		if (0 == $armies) {
			if ('Placing' == $this->state) {
				try {
					$this->_risk->set_player_state('Waiting', $player_id, true);
					$this->_test_placing( );
				}
				catch (MyException $e) {
					throw $e;
				}
			}
			elseif ('Playing' == $this->state) {
				try {
					$this->_risk->set_player_state('Attacking', $player_id);
				}
				catch (MyException $e) {
					throw $e;
				}
			}
			else {
				throw new MyException(__METHOD__.': Unsupported game state ('.$this->state.') encountered');
			}
		}
	}


	/** protected function _test_placing
	 *		Tests the number of players who are still placing
	 *		and sets the game state to the next state based on count
	 *
	 * @param void
	 *
	 * @action optionally sets the next state
	 *
	 * @return void
	 */
	protected function _test_placing( )
	{
		call(__METHOD__);

		if ('Placing' != $this->state) {
			return;
		}

		// grab the number of players still placing
		foreach ($this->_risk->players as $player) {
			if (0 != $player['armies']) {
				return;
			}
		}

		// if we got here, everybody is done placing

		// set all the players to waiting
		foreach ($this->_risk->players as & $player) {
			$player['state'] = 'Waiting';
		}
		unset($player);

		$this->_begin( );
	}


	/** protected function _test_winner
	 *		Tests the number of players who are still playing
	 *		and sets the winner if only one
	 *
	 * @param void
	 *
	 * @action optionally sets the winner
	 *
	 * @return void
	 */
	protected function _test_winner( )
	{
		call(__METHOD__);

		// check the players and see if there are any more alive
		$count = 0;
		foreach ($this->_risk->players as $player) {
			if ( ! in_array($player['state'], array('Resigned', 'Dead'))) {
				++$count;
				$alive[] = $player['player_id'];
			}
		}

		if (1 == $count) {
			$this->state = 'Finished';

			$this->_players[$alive[0]]['object']->add_win( );
			self::log($this->id, 'D ' . $alive[0]);

			Email::send('finished', array_keys($this->_players), array('game_id' => $this->id, 'name' => $this->name, 'winner' => $this->_players[$alive[0]]['object']->username));

			self::write_game_file($this->id);
		}
	}


	/** protected function _test_nudge
	 *		Tests if the current player can be nudged or not
	 *
	 * @param mixed $ids optional array or string csv of player ids
	 *
	 * @return array of nudgeable ids
	 */
	protected function _test_nudge($ids = null)
	{
		call(__METHOD__);
		call($ids);

		try {
			$nudge_time = Settings::read('nudge_flood_control');
		}
		catch (MyException $e) {
			return false;
		}

		if (-1 == $nudge_time) {
			return false;
		}
		elseif (0 == $nudge_time) {
			return true;
		}

		$Mysql = Mysql::get_instance( );

		if ($ids) {
			array_trim($ids, 'int');
		}
		else {
			// find all players who are not waiting or dead
			// in case we are in the placing state
			$query = "
				SELECT `GP`.`player_id`
				FROM `".self::GAME_PLAYER_TABLE."` AS `GP`
				WHERE `GP`.`game_id` = '{$this->id}'
					AND `GP`.`state` NOT IN ('Waiting', 'Resigned', 'Dead')
			";
			$ids = $Mysql->fetch_value_array($query);
		}
		array_trim($ids, 'int');

		$nudgable = array( );
		foreach ($ids as $id) {
			// check the nudge status for this game/player
			// 'now' is taken from the DB because it may
			// have a different time from the PHP server
			$query = "
				SELECT `GP`.`state`
					, `GP`.`move_date`
					, `GN`.`nudged`
					, NOW( ) AS `now`
				FROM `".self::GAME_PLAYER_TABLE."` AS `GP`
					LEFT JOIN `".self::GAME_NUDGE_TABLE."` AS `GN`
						ON (`GN`.`game_id` = `GP`.`game_id`
							AND `GN`.`player_id` = `GP`.`player_id`)
				WHERE `GP`.`game_id` = '{$this->id}'
					AND `GP`.`player_id` = '{$id}'
					AND `GP`.`state` NOT IN ('Waiting', 'Resigned', 'Dead')
			";
			$player = $Mysql->fetch_assoc($query);

			if ( ! $player) {
				continue;
			}

			// check the dates
			// if the move date is far enough in the past
			//  AND the player has not been nudged
			//   OR the nudge date is far enough in the past
			if ((strtotime($player['move_date']) <= strtotime('-'.$nudge_time.' hour', strtotime($player['now'])))
				&& ((empty($player['nudged']))
					|| (strtotime($player['nudged']) <= strtotime('-'.$nudge_time.' hour', strtotime($player['now'])))))
			{
				$nudgable[] = $id;
			}
		}

		return $nudgable;
	}


	/** protected function _begin
	 *		Begins the game proper
	 *
	 * @param void
	 *
	 * @action begins the game
	 * @action saves the game
	 *
	 * @return void
	 */
	protected function _begin( )
	{
		call(__METHOD__);

		// make sure the game is 'Playing'
		$this->state = 'Playing';

		$player_id = $this->_risk->begin( );

		Email::send('turn', $player_id, array('game_id' => $this->id, 'name' => $this->name));
	}


	/** protected function _hash_pass
	 *		Hashs the game password
	 *
	 * @param string $password password
	 *
	 * @return string salted password hash
	 */
	protected function _hash_pass($password)
	{
		return md5($password . 's41Ty!S7uFF');
	}


	/**
	 *		STATIC METHODS
	 * * * * * * * * * * * * * * * * * * * * * * * * * * */

	/** static public function calculate_trade_values
	 *		Calculates the trade value array from the input
	 *		Algorithm: (X = value, 0 = empty, ? = don't care) (value cannot be 0)
	 *		START END STEP TIMES
	 *			X X X 0 - START to END by STEP
	 *			X X 0 0 - START to END (one step, two values)
	 *			X 0 X 0 - START to infinity (or next array) by STEP
	 *			X 0 0 0 - START forever (or next array) (non changing trade value)
	 *			X 0 0 X - START for TIMES times (until next array)
	 *			0 ? ? ? - (skip this array, unless first array)
	 *
	 *		note that START is always required
	 *
	 *		e.g.- A normal game trade values is as follows:
	 *			4, 6, 8, 10, 12, 15, 20, 25, 30, 35, etc (+5)
	 *		this would equate to an array as follows:
	 *			array(
	 *				array(4, 12, 2),
	 *				array(15, 0, 5),
	 *			)
	 *		and would return the following array:
	 *			array(4, 6, 8, 10, 12, 15, '+5')
	 *
	 * @param array $input array of input arrays
	 *
	 * @return array of trade values
	 */
	static public function calculate_trade_values($input)
	{
		call(__METHOD__);
		call($input);

		// parse the array
		$prev_step = 0;
		$trades = array( );
		foreach ($input as $path_i => $path) {
			$allow_empty_start = false;

			if ( ! isset($path['times']) && ! isset($path[3])) {
				$path['times'] = $path[3] = 0;
			}

			if ( ! isset($path['start']) && isset($path[0])) {
				$path['start'] = $path[0];
				$path['end']   = $path[1];
				$path['step']  = $path[2];
				$path['times'] = $path[3];
			}

			if ( ! $trades && ('0' === (string) $path['start'])) {
				$allow_empty_start = true;
			}

			$path['start'] = (int) $path['start'];
			$path['end']   = (int) $path['end'];
			$path['step']  = (int) $path['step'];
			$path['times'] = (int) (isset($path['times'])) ? $path['times'] : 0;

			// make sure none of the values are negative (set to 0 if neg)
			// STEP can be negative
			$path['start'] = (0 > $path['start']) ? 0 : $path['start'];
			$path['end']   = (0 > $path['end'])   ? 0 : $path['end'];
			$path['times'] = (0 > $path['times']) ? 0 : $path['times'];

			// if we don't have a START, skip this one
			if ( ! $path['start'] && ! $allow_empty_start) {
				continue;
			}

			// if we have END or STEP, remove TIMES
			if ($path['end'] || $path['step']) {
				$path['times'] = 0;
			}

			// deal with any previous steps we might have
			if ($prev_step) {
				$end = $path['start'];
				$next = $trades[count($trades) - 1];

				// make sure we're heading in the right direction
				if (($next < $end) && (0 < $prev_step)) {
					while (($next + $prev_step) < $end) {
						$next += $prev_step;
						$trades[] = $next;
					}
				}
				elseif (($next > $end) && (0 > $prev_step)) {
					while (($next + $prev_step) > $end) {
						$next += $prev_step;
						$trades[] = $next;
					}
				}
				else {
					// STEP is going in the wrong direction
					// just skip it
				}

				// clear the prev_step so we don't use it elsewhere
				$prev_step = 0;
			}

			if ($path['times']) {
				for ($i = 0; $i < $path['times']; ++$i) {
					$trades[] = $path['start'];
				}

				// our work here is done
				continue;
			}

			// enter the stating point for the rest
			$trades[] = $path['start'];

			if (0 != $path['end']) {
				if ($path['step']) {
					$next = $path['start'];

					if (($next < $path['end']) && (0 < $path['step'])) {
						while (($next + $path['step']) < $path['end']) {
							$next += $path['step'];
							$trades[] = $next;
						}

						$trades[] = $path['end'];
					}
					elseif (($next > $path['end']) && (0 > $path['step'])) {
						while (($next + $path['step']) > $path['end']) {
							$next += $path['step'];
							$trades[] = $next;
						}

						$trades[] = $path['end'];
					}
					else {
						// STEP is going in the wrong direction
						// just skip it and go straight to END
						$trades[] = $path['end'];
					}
				}
				else {
					// jump to END
					$trades[] = $path['end'];
				}
			}
			elseif (0 != $path['step']) {
				// we don't have an END, so store STEP for the next round
				$prev_step = $path['step'];
				continue;
			}
			else { // no END and no STEP (and no TIMES)
				continue;
			}
		}

		// continue the last STEP if we need to
		if ($prev_step) {
			$trades[] = ((0 < $prev_step) ? '+' : '').$prev_step;
		}

		if ( ! count($trades)) {
			$trades = array(4,6,8,10,12,15,'+5');
		}
		call($trades);

		return $trades;
	}



	/** static public function get_list
	 *		Returns a list array of all games in the database
	 *		with games which need the users attention highlighted
	 *
	 * @param int $player_id optional player's id
	 *
	 * @return array game list (or bool false on failure)
	 */
	static public function get_list($player_id = 0)
	{
		$Mysql = Mysql::get_instance( );

		$player_id = (int) $player_id;

		$query = "
			SELECT `G`.*
				-- this stops the query from pulling 0 from the player table if no moves have been made
				-- or if there are no players in the game yet (don't know why that would be, but...)
				, IF((0 = MAX(`GP`.`move_date`)) OR MAX(`GP`.`move_date`) IS NULL, `G`.`create_date`, MAX(`GP`.`move_date`)) AS `last_move`
				, 0 AS `in_game`
				, 0 AS `highlight`
				, COUNT(DISTINCT `GP`.`player_id`) AS `players`
				, `P`.`username` AS `hostname`
				, `C`.`username` AS `username`
			FROM `".self::GAME_TABLE."` AS `G`
				LEFT JOIN `".self::GAME_PLAYER_TABLE."` AS `GP`
					ON `GP`.`game_id` = `G`.`game_id`
				LEFT JOIN `".self::GAME_PLAYER_TABLE."` AS `CP`
					ON (`CP`.`game_id` = `G`.`game_id`
						AND `CP`.`state` NOT IN ('Waiting', 'Resigned', 'Dead'))
				LEFT JOIN `".Player::PLAYER_TABLE."` AS `P`
					ON `P`.`player_id` = `G`.`host_id`
				LEFT JOIN `".Player::PLAYER_TABLE."` AS `C`
					ON `C`.`player_id` = `CP`.`player_id`
			GROUP BY `G`.`game_id`
			ORDER BY `G`.`state` ASC
				, `last_move` DESC
		";
		$list = $Mysql->fetch_array($query);

		// get player's state for games they are in
		$query = "
			SELECT `GP`.`game_id`
				, `GP`.`state`
			FROM `".self::GAME_PLAYER_TABLE."` AS `GP`
				LEFT JOIN `".self::GAME_TABLE."` AS `G`
					USING (`game_id`)
			WHERE `GP`.`player_id` = '{$player_id}'
		";
		$results = $Mysql->fetch_array($query);

		$states = array( );
		foreach ($results as $row) {
			$states[$row['game_id']] = $row['state'];
		}

		// run though the list and add extra data
		if ($list) {
			foreach ($list as $key => $game) {
				// remove current player if the game has not started yet
				if ( ! in_array($game['state'], array('Playing', 'Finished'))) {
					$game['username'] = '';
				}

// temp fix for old serialized data
fix_extra_info($game['extra_info']);
				$extra_info = array_merge_plus(self::$_EXTRA_INFO_DEFAULTS, json_decode($game['extra_info'], true));

				foreach ($extra_info as $field => $value) {
					$game[$field] = $value;
				}

				$game['get_fortify'] = self::_get_fortify($extra_info);
				$game['get_kamikaze'] = self::_get_kamikaze($extra_info);
				$game['get_warmonger'] = self::_get_warmonger($extra_info);
				$game['get_nuke'] = self::_get_nuke($extra_info);
				$game['get_turncoat'] = self::_get_turncoat($extra_info);
				$game['get_conquer_limit'] = self::_get_conquer_limit($extra_info);
				$game['get_custom_rules'] = self::_get_custom_rules($extra_info);
				$game['get_fog_of_war'] = self::_get_fog_of_war($extra_info);
				$game['get_fog_of_war_armies'] = $game['get_fog_of_war']['armies'];
				$game['get_fog_of_war_colors'] = $game['get_fog_of_war']['colors'];

				$game['clean_name'] = htmlentities($game['name'], ENT_QUOTES, 'UTF-8', false);
				$game['clean_custom_rules'] = htmlentities($game['get_custom_rules'], ENT_QUOTES, 'UTF-8', false);

				$game['in_game'] = isset($states[$game['game_id']]);
				$game['highlight'] = $game['in_game'] && ('Finished' != $game['state']) && ! in_array($states[$game['game_id']], array('Waiting', 'Resigned', 'Dead'));

				$list[$key] = $game;
			}
		}

		return $list;
	}


	/** static public function get_my_list
	 *		Returns a list array of all games in the database
	 *		that the player given is currently playing in
	 *
	 * @param int $player_id player's id
	 *
	 * @return array player's game list (or bool false on failure)
	 */
	static public function get_my_list($player_id)
	{
		$Mysql = Mysql::get_instance( );

		$player_id = (int) $player_id;

		$query = "
			SELECT `G`.*
				, `G`.`state` AS `game_state`
				, `GP`.*
				, `GP`.`state` AS `player_state`
			FROM `".self::GAME_TABLE."` AS `G`
				LEFT JOIN `".self::GAME_PLAYER_TABLE."` AS `GP`
					ON `GP`.`game_id` = `G`.`game_id`
			WHERE `GP`.`player_id` = '{$player_id}'
		";
		$list = $Mysql->fetch_array($query);

		// go through the list and figure out what the players true state is
		if ($list) {
			$playing = $placing = $waiting = $dead = array( );

			foreach ($list as $key => $game) {
				switch ($game['player_state']) {
					case 'Placing' :
					case 'Dead' :
					case 'Resigned' :
						$game['my_state'] = $game['player_state'];
						break;

					case 'Trading' :
					case 'Attacking' :
					case 'Occupying' :
					case 'Fortifying' :
						$game['my_state'] = 'Playing';
						break;

					case 'Waiting' :
					default :
						$game['my_state'] = 'Waiting';
						break;
				}

				switch ($game['game_state']) {
					case 'Finished' :
						$game['my_state'] = 'Dead';
						break;

					default :
						// do nothing
						break;
				}

				// Resigned = Dead
				$game['my_state'] = ('Resigned' == $game['my_state']) ? 'Dead' : $game['my_state'];

				// bin them so we can sort them
				${strtolower($game['my_state'])}[] = $game;
			}

			// merge the game arrays sorted
			$list = array_merge($playing, $placing, $waiting, $dead);
		}

		return $list;
	}


	/** static public function get_count
	 *		Returns a count of all games in the database,
	 *		the highest game id (the total number of games played),
	 *		the number of games the given player is currently playing,
	 *		the number of games where it is the current player's turn
	 *
	 * @param int $player_id optional player id
	 *
	 * @return array (int current game count, int total game count, int player game count, int player turn count)
	 */
	static public function get_count($player_id = 0)
	{
		$Mysql = Mysql::get_instance( );

		$player_id = (int) $player_id;

		// games in play
		$query = "
			SELECT COUNT(*)
			FROM `".self::GAME_TABLE."`
			WHERE `state` <> 'Finished'
		";
		$count = $Mysql->fetch_value($query);

		// total games
		$query = "
			SELECT MAX(`game_id`)
			FROM `".self::GAME_TABLE."`
		";
		$next = $Mysql->fetch_value($query);

		// my games
		$query = "
			SELECT COUNT(`GP`.`player_id`)
			FROM `".self::GAME_PLAYER_TABLE."` AS `GP`
				LEFT JOIN `".self::GAME_TABLE."` AS `G`
					USING (`game_id`)
			WHERE `GP`.`player_id` = '{$player_id}'
				AND `G`.`state` <> 'Finished'
		";
		$mine = $Mysql->fetch_value($query);

		// my turns
		$query = "
			SELECT COUNT(`GP`.`player_id`)
			FROM `".self::GAME_PLAYER_TABLE."` AS `GP`
				LEFT JOIN `".self::GAME_TABLE."` AS `G`
					USING (`game_id`)
			WHERE `GP`.`player_id` = '{$player_id}'
				AND `G`.`state` <> 'Finished'
				AND `GP`.`state` NOT IN ('Waiting', 'Resigned', 'Dead')
		";
		$turns = $Mysql->fetch_value($query);

		return array($count, $next, $mine, $turns);
	}


	/** static public function check_turns
	 *		Checks if it's the given player's turn in any games
	 *
	 * @param int $player_id player id
	 *
	 * @return number of games player has a turn in
	 */
	static public function check_turns($player_id)
	{
		call(__METHOD__);

		$player_id = (int) $player_id;

		if ( ! $player_id) {
			return false;
		}

		$Mysql = Mysql::get_instance( );

		$query = "
			SELECT COUNT(`GP`.`player_id`)
			FROM `".self::GAME_PLAYER_TABLE."` AS `GP`
				LEFT JOIN `".self::GAME_TABLE."` AS `G`
					USING (`game_id`)
			WHERE `GP`.`player_id` = '{$player_id}'
				AND `G`.`state` <> 'Finished'
				AND `GP`.`state` NOT IN ('Waiting', 'Resigned', 'Dead')
		";
		$turn = $Mysql->fetch_value($query);

		return $turn;
	}


	/** static public function delete
	 *		Deletes the given game and all related data
	 *
	 * @param mixed $ids array or csv of game ids
	 *
	 * @action deletes the game and all related data from the database
	 *
	 * @return void
	 */
	static public function delete($ids)
	{
		call(__METHOD__);
		call($ids);

		$Mysql = Mysql::get_instance( );

		array_trim($ids, 'int');

		if ( ! $ids) {
			return;
		}

		foreach ($ids as $id) {
			self::write_game_file($id);
		}

		$tables = array(
			self::GAME_LOG_TABLE ,
			self::GAME_LAND_TABLE ,
			self::GAME_PLAYER_TABLE ,
			self::GAME_TABLE ,
		);

		$Mysql->multi_delete($tables, array('game_id' => $ids));

		$query = "
			OPTIMIZE TABLE `".self::GAME_TABLE."`
				, `".self::GAME_PLAYER_TABLE."`
				, `".self::GAME_LAND_TABLE."`
				, `".self::GAME_LOG_TABLE."`
		";
		$Mysql->query($query);
	}


	/** static public function delete_inactive
	 *		Deletes the inactive games from the database
	 *
	 * @param int $age age in days
	 *
	 * @return bool
	 */
	static public function delete_inactive($age)
	{
		call(__METHOD__);
		call($age);

		$age = (int) abs($age);

		if (0 == $age) {
			return false;
		}

		$Mysql = Mysql::get_instance( );

		// don't auto delete paused games
		$query = "
			SELECT `game_id`
			FROM `".self::GAME_TABLE."`
			WHERE `modify_date` < DATE_SUB(NOW( ), INTERVAL {$age} DAY)
				AND `paused` = 0
		";
		$ids = $Mysql->fetch_value_array($query);

		self::delete($ids);

		return true;
	}


	/** static public function delete_finished
	 *		Deletes the finished games from the database
	 *
	 * @param int $age age in days
	 *
	 * @return bool
	 */
	static public function delete_finished($age)
	{
		call(__METHOD__);
		call($age);

		$age = (int) abs($age);

		if (0 == $age) {
			return false;
		}

		$Mysql = Mysql::get_instance( );

		$query = "
			SELECT `game_id`
			FROM `".self::GAME_TABLE."`
			WHERE `modify_date` < DATE_SUB(NOW( ), INTERVAL {$age} DAY)
				AND `state` = 'Finished'
		";
		$ids = $Mysql->fetch_value_array($query);

		self::delete($ids);

		return true;
	}


	/** static public function player_deleted
	 *		Resigns the given players from any games they are in
	 *
	 * @param mixed $player_ids array or csv of player ids
	 *
	 * @action resigns the players from the games
	 *
	 * @return void
	 * @throws MyException
	 */
	static public function player_deleted($player_ids)
	{
		call(__METHOD__);

		$Mysql = Mysql::get_instance( );

		array_trim($player_ids, 'int');

		if ( ! $player_ids) {
			throw new MyException(__METHOD__.': No player IDs given');
		}

		// parse through the given IDs and resign them from the games they are in
		foreach ($player_ids as $player_id) {
			// grab the games this player is in
			$query = "
				SELECT `GP`.`game_id`
					, `GP`.`state` AS `p_state`
					, `G`.`state` AS `g_state`
				FROM `".self::GAME_PLAYER_TABLE."` AS `GP`
					LEFT JOIN `".self::GAME_TABLE."` AS `G`
						USING (`game_id`)
				WHERE `GP`.`player_id` = '{$player_id}'
			";
			$games = $Mysql->fetch_array($query);

			// now run through the games and resign the player from those games
			foreach ($games as $game) {
				switch ($game['g_state']) {
					case 'Finished' :
						// doesn't matter, the game is over
						continue;
						break;

					case 'Waiting' :
						// just remove them from the game completely
						$query = "
							DELETE
							FROM `".self::GAME_PLAYER_TABLE."`
							WHERE `player_id` = '{$player_id}'
								AND `game_id` = '{$game['game_id']}'
						";
						$Mysql->query($query);
						continue;
						break;

					case 'Placing' :
					case 'Playing' :
					default :
						// test the player state
						if ( ! in_array($game['p_state'], array('Resigned', 'Dead'))) {
							$Game = new Game($game['game_id']);
							$Game->_risk->halt_redirect = true;
							$Game->force_resign($player_id);
						}
						break;
				}
			}
		}

		// TODO: build this function
		// what it needs to do is basically, set the deleted player as resigned
		// in all the games they are in, and move to next player if it was their turn
		// and set it up so if a game pulls a player id with no match, use the name
		// [deleted] or something similar
	}


	/** static public function pause
	 *		Pauses the given games
	 *
	 * @param mixed $ids array or csv of game ids
	 * @param bool $pause optional pause game (false = unpause)
	 *
	 * @action pauses the games
	 *
	 * @return void
	 * @throws MyException
	 */
	static public function pause($ids, $pause = true)
	{
		$Mysql = Mysql::get_instance( );

		array_trim($ids, 'int');

		$pause = (int) (bool) $pause;

		if (empty($ids)) {
			throw new MyException(__METHOD__.': No game ids given');
		}

		$Mysql->insert(self::GAME_TABLE, array('paused' => $pause), " WHERE `game_id` IN (".implode(',', $ids).") ");
	}


	/** static public function write_game_file
	 *		Writes the game logs to a file for storage
	 *
	 * @param int $game_id game id
	 *
	 * @action writes the game data to a file
	 *
	 * @return bool success
	 */
	static public function write_game_file($game_id)
	{
		$game_id = (int) $game_id;

		if ( ! Settings::read('save_games')) {
			return false;
		}

		if (0 == $game_id) {
			return false;
		}

		$Mysql = Mysql::get_instance( );

		$query = "
			SELECT *
			FROM `".self::GAME_TABLE."`
			WHERE `game_id` = '{$game_id}'
		";
		$game = $Mysql->fetch_assoc($query);

		if ( ! $game) {
			return false;
		}

		if ( ! in_array($game['state'], array('Playing', 'Finished'))) {
			return false;
		}

		$query = "
			SELECT `P`.`player_id`
				, `P`.`username`
				, `GP`.`color`
				, `GP`.`order_num`
			FROM `".self::GAME_PLAYER_TABLE."` AS `GP`
				JOIN `".Player::PLAYER_TABLE."` AS `P`
					ON (`P`.`player_id` = `GP`.`player_id`)
			WHERE `GP`.`game_id` = '{$game_id}'
			ORDER BY `GP`.`order_num` ASC
		";
		$results = $Mysql->fetch_array($query);

		if ( ! $results) {
			return false;
		}

		$players = array( );
		foreach ($results as $result) {
			$players[$result['player_id']] = $result;
		}

		$logs = self::get_logs($game_id, false);

		if (empty($logs)) {
			return false;
		}

		$winner = 'Unknown';
		if ('D' === $logs[0]['data']{0}) {
			$winner = (int) trim($logs[0]['data'], 'D ');
			$winner = "{$winner} - {$players[$winner]['username']}";
		}

		// open the file for writing
		$filename = GAMES_DIR.GAME_NAME.'_'.$game_id.'_'.date('Ymd', strtotime($game['create_date'])).'.dat'; // don't use ldate() here
		$file = fopen($filename, 'w');

		if (false === $file) {
			return false;
		}

		fwrite($file, "{$game['game_id']} - {$game['name']} - {$game['game_type']}\n");
		fwrite($file, date('Y-m-d', strtotime($game['create_date']))."\n"); // don't use ldate() here
		fwrite($file, date('Y-m-d', strtotime($game['modify_date']))."\n"); // don't use ldate() here
		fwrite($file, "{$winner}\n");
		fwrite($file, $GLOBALS['_ROOT_URI']."\n");
		fwrite($file, "=================================\n");
		fwrite($file, $game['extra_info']."\n");
		fwrite($file, "=================================\n");

		foreach ($players as $player) {
			fwrite($file, "{$player['player_id']} - {$player['color']} - {$player['username']}\n");
		}

		fwrite($file, "=================================\n");

		$logs = array_reverse($logs);

		foreach ($logs as $log) {
			fwrite($file, $log['data']."\n");
		}

		fwrite($file, "=================================\n");

		fwrite($file, "KEY--- (plid = player_id, trid = territory_id, cid = continent_id, atk = attack, dfd = defend)\n");
		fwrite($file, "A - Attack - [atk_plid]:[atk_trid]:[dfd_plid]:[dfd_trid]:[atk_rolls],[dfd_rolls]:[atk_lost],[dfd_lost]:[defeated]\n");
		fwrite($file, "C - Card - [plid]:[card_id]\n");
		fwrite($file, "D - Done (Game Over) - [winner_plid]\n");
		fwrite($file, "E - Eradicated - [plid]:[killed_plid]:[cards_received (if any)]\n");
		fwrite($file, "F - Fortify - [plid]:[armies_moved]:[from_trid]:[to_trid]\n");
		fwrite($file, "I - Board Initialization - Ordered comma-separated list of plids ordered by trids (1-index).\n");
		fwrite($file, "N - Next Player - [plid]\n");
		fwrite($file, "O - Occupy - [plid]:[armies_moved]:[from_trid]:[to_trid]\n");
		fwrite($file, "P - Placement - [plid]:[armies_placed]:[trid]\n");
		fwrite($file, "Q - Quit - [plid]\n");
		fwrite($file, "R - Reinforce - [plid]:[armies_given]:[num_territories_controlled]:[csv_cids_controlled (if any)]\n");
		fwrite($file, "T - Trade - [plid]:[csv_card_list]:[armies_given]:[bonus_trid (if any)]\n");
		fwrite($file, "V - Value for Trade - [next_trade_value]\n");

		fwrite($file, "\n");

		return fclose($file);
	}


} // end of Game class


/*		schemas
// ===================================

--
-- Table structure for table `wr_game`
--

DROP TABLE IF EXISTS `wr_game`;
CREATE TABLE IF NOT EXISTS `wr_game` (
  `game_id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `host_id` int(11) unsigned NOT NULL DEFAULT '0',
  `name` varchar(255) NOT NULL DEFAULT '',
  `password` varchar(32) DEFAULT NULL,
  `capacity` tinyint(1) unsigned NOT NULL DEFAULT '2',
  `time_limit` tinyint(2) unsigned DEFAULT NULL,
  `allow_kibitz` tinyint(1) NOT NULL DEFAULT '0',
  `game_type` enum('Original','Secret Mission','Capital') NOT NULL DEFAULT 'Original',
  `state` enum('Waiting','Placing','Playing','Finished') NOT NULL DEFAULT 'Waiting',
  `extra_info` text DEFAULT NULL,
  `paused` tinyint(1) NOT NULL DEFAULT '0',
  `create_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modify_date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,

  PRIMARY KEY (`game_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci ;

-- --------------------------------------------------------

--
-- Table structure for table `wr_game_land`
--

DROP TABLE IF EXISTS `wr_game_land`;
CREATE TABLE IF NOT EXISTS `wr_game_land` (
  `game_id` int(10) unsigned NOT NULL DEFAULT '0',
  `land_id` int(10) unsigned NOT NULL DEFAULT '0',
  `player_id` int(10) unsigned NOT NULL DEFAULT '0',
  `armies` smallint(5) unsigned NOT NULL DEFAULT '0',

  UNIQUE KEY `game_land` (`game_id`,`land_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci ;

-- --------------------------------------------------------

--
-- Table structure for table `wr_game_nudge`
--

DROP TABLE IF EXISTS `wr_game_nudge`;
CREATE TABLE IF NOT EXISTS `wr_game_nudge` (
  `game_id` int(10) unsigned NOT NULL DEFAULT '0',
  `player_id` int(10) unsigned NOT NULL DEFAULT '0',
  `nudged` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,

  UNIQUE KEY `game_player` (`game_id`,`player_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci ;

-- --------------------------------------------------------

--
-- Table structure for table `wr_game_player`
--

DROP TABLE IF EXISTS `wr_game_player`;
CREATE TABLE IF NOT EXISTS `wr_game_player` (
  `game_id` int(11) unsigned NOT NULL DEFAULT '0',
  `player_id` int(11) unsigned NOT NULL DEFAULT '0',
  `order_num` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `color` varchar(10) NOT NULL DEFAULT '',
  `cards` varchar(255) DEFAULT NULL,
  `armies` smallint(5) unsigned NOT NULL DEFAULT '0',
  `state` enum('Waiting','Trading','Placing','Attacking','Occupying','Fortifying','Resigned','Dead') NOT NULL DEFAULT 'Waiting',
  `extra_info` text DEFAULT NULL,
  `move_date` timestamp NOT NULL DEFAULT '0000-00-00 00:00:00' ON UPDATE CURRENT_TIMESTAMP,

  UNIQUE KEY `game_player` (`game_id`,`player_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci ;


-- --------------------------------------------------------

--
-- Table structure for table `wr_game_log`
--

DROP TABLE IF EXISTS `wr_game_log`;
CREATE TABLE IF NOT EXISTS `wr_game_log` (
  `game_id` int(11) unsigned NOT NULL DEFAULT '0',
  `data` varchar(255) DEFAULT NULL,
  `create_date` datetime(6) NOT NULL DEFAULT '0000-00-00 00:00:00.000000',
  `microsecond` decimal(18,8) NOT NULL DEFAULT '0'

  KEY `game_id` (`game_id`,`create_date`,`microsecond`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci ;

-- --------------------------------------------------------

--
-- Table structure for table `wr_roll_log`
--

DROP TABLE IF EXISTS `wr_roll_log`;
CREATE TABLE IF NOT EXISTS `wr_roll_log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `attack_1` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `attack_2` tinyint(1) unsigned DEFAULT NULL,
  `attack_3` tinyint(1) unsigned DEFAULT NULL,
  `defend_1` tinyint(1) unsigned NOT NULL DEFAULT '0',
  `defend_2` tinyint(1) unsigned DEFAULT NULL,

  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 COLLATE=utf8_general_ci ;

*/


