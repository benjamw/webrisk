<?php
/*
+---------------------------------------------------------------------------
|
|   risk.class.php (php 5.x)
|
|   by Benjam Welker
|   http://iohelix.net
|
+---------------------------------------------------------------------------
|
|	This module is built to play the game of Risk, it cares not about
|	database structure or the goings on of the website, only about Risk
|
+---------------------------------------------------------------------------
|
|   > Risk module
|   > Date started: 2008-02-28
|
|   > Module Version Number: 0.8.0
|
+---------------------------------------------------------------------------
*/

// TODO: organize better
// TODO: the logging system should really be wholly contained within the game class
// with the Risk class returning everything that the Game class might need to write the log
// or making it available so that the game class can get at it easily.

require_once INCLUDE_DIR.'html.general.php';
require_once INCLUDE_DIR.'func.array.php';

// set some constant keys for the static arrays below
define('NAME', 0); // used in both continent and territory arrays

define('BONUS', 1);
define('TERRITORIES', 2);

define('ADJACENT', 1);
define('CONTINENT_ID', 2);

define('TERRITORY_ID', 0);
define('CARD_TYPE', 1);

define('WILD', 0);
define('INFANTRY', 1);
define('CAVALRY', 10);
define('ARTILLERY', 100);

class Risk
{

	/**
	 *		PROPERTIES
	 * * * * * * * * * * * * * * * * * * * * * * * * * * */

	/** static protected property CONTINENTS
	 *		Holds the game continents data
	 *		data format:
	 *			0- continent name
	 *			1- bonus value
	 *			2- territory index array
	 *
	 * @var array (index starts at 1)
	 */
	static public $CONTINENTS = [ 1 =>
	/* 1*/	['North America', 5, [1, 2, 3, 4, 5, 6, 7, 8, 9]],
			['South America', 2, [10, 11, 12, 13]],
			['Europe', 5, [14, 15, 16, 17, 18, 19, 20]],
			['Africa', 3, [21, 22, 23, 24, 25, 26]],
	/* 5*/	['Asia', 7, [27, 28, 29, 30, 31, 32, 33, 34, 35, 36, 37, 38]],
			['Australia', 2, [39, 40, 41, 42]],
	];


	/** static protected property TERRITORIES
	 *		Holds the game territories data
	 *		data format:
	 *			0- territory name
	 *			1- adjacent territories index array
	 *			2- continent id
	 *
	 * @var array (index starts at 1)
	 */
	static public $TERRITORIES = [ 1 =>
			// North America
	/* 1*/	['Alaska', [2, 6, 32], 1],
			['Alberta', [1, 6, 7, 9], 1],
			['Central America', [4, 9, 13], 1],
			['Eastern United States', [3, 7, 8, 9], 1],
	/* 5*/	['Greenland', [6, 7, 8, 15], 1],
			['Northwest Territory', [1, 2, 5, 7], 1],
			['Ontario', [2, 4, 5, 6, 8, 9], 1],
			['Quebec', [4, 5, 7], 1],
	/* 9*/	['Western United States', [2, 3, 4, 7], 1],

		// South America
	/*10*/	['Argentina', [11, 12], 2],
			['Brazil', [10, 12, 13, 25], 2],
			['Peru', [10, 11, 13], 2],
	/*13*/	['Venezuela', [3, 11, 12], 2],

		// Europe
	/*14*/	['Great Britain', [15, 16, 17, 20], 3],// officially 'Great Britain and Ireland', but it's too long
	/*15*/	['Iceland', [5, 14, 17], 3],
			['Northern Europe', [14, 17, 18, 19, 20], 3],
			['Scandinavia', [14, 15, 16, 19], 3],
			['Southern Europe', [16, 19, 20, 23, 25, 33], 3],
			['Ukraine', [16, 17, 18, 27, 33, 37], 3],
	/*20*/	['Western Europe', [14, 16, 18, 25], 3],

		// Africa
	/*21*/	['Congo', [22, 25, 26], 4],
			['East Africa', [21, 23, 24, 25, 26, 33], 4],
			['Egypt', [18, 22, 25, 33], 4],
			['Madagascar', [22, 26], 4],
	/*25*/	['North Africa', [11, 18, 20, 21, 22, 23], 4],
	/*26*/	['South Africa', [21, 22, 24], 4],

		// Asia
	/*27*/	['Afghanistan', [19, 28, 29, 33, 37], 5],
			['China', [27, 29, 34, 35, 36, 37], 5],
			['India', [27, 28, 33, 35], 5],
	/*30*/	['Irkutsk', [32, 34, 36, 38], 5],
			['Japan', [32, 34], 5],
			['Kamchatka', [1, 30, 31, 34, 38], 5],
			['Middle East', [18, 19, 22, 23, 27, 29], 5],
			['Mongolia', [28, 30, 31, 32, 36], 5],
	/*35*/	['Siam', [28, 29, 40], 5],
			['Siberia', [28, 30, 34, 37, 38], 5],
			['Ural', [19, 27, 28, 36], 5],
	/*38*/	['Yakutsk', [30, 32, 36], 5],

		// Australia
	/*39*/	['Eastern Australia', [41, 42], 6],
	/*40*/	['Indonesia', [35, 41, 42], 6],
			['New Guinea', [39, 40, 42], 6],
	/*42*/	['Western Australia', [39, 40, 41], 6],
	];


	/** static protected property CARDS
	 *		Holds the game cards data
	 *		data format:
	 *			0- territory index (0 = wild)
	 *			1- type (0 = wild, 1 = infantry, 2 = cavalry, 3 = artillery)
	 *
	 *		NOTE: card index matches territory index (except for wild)
	 *
	 * @var array (index starts at 1)
	 */
	static public $CARDS = [ 1 =>
		[1, INFANTRY],
		[2, CAVALRY],
		[3, ARTILLERY],
		[4, ARTILLERY],
		[5, CAVALRY],
		[6, ARTILLERY],
		[7, CAVALRY],
		[8, CAVALRY],
		[9, ARTILLERY],
		[10, INFANTRY],
		[11, ARTILLERY],
		[12, INFANTRY],
		[13, INFANTRY],
		[14, ARTILLERY],
		[15, INFANTRY],
		[16, ARTILLERY],
		[17, CAVALRY],
		[18, ARTILLERY],
		[19, CAVALRY],
		[20, ARTILLERY],
		[21, INFANTRY],
		[22, INFANTRY],
		[23, INFANTRY],
		[24, CAVALRY],
		[25, CAVALRY],
		[26, ARTILLERY],
		[27, CAVALRY],
		[28, INFANTRY],
		[29, ARTILLERY],
		[30, CAVALRY],
		[31, CAVALRY],
		[32, INFANTRY],
		[33, INFANTRY],
		[34, INFANTRY],
		[35, INFANTRY],
		[36, CAVALRY],
		[37, CAVALRY],
		[38, CAVALRY],
		[39, ARTILLERY],
		[40, ARTILLERY],
		[41, INFANTRY],
		[42, ARTILLERY],

		[0, WILD],
		[0, WILD],
	];


	/** static public property EXTRA_INFO_DEFAULTS
	 *		Holds the default extra info data
	 *		Values:
	 *		- fortify: If the game allows for fortifications
	 *			If this is set to false, all other fortification
	 *			settings are moot.
	 *		- multiple_fortify: If the game allows for multiple fortifications
	 *			Only allow any given group to go one space
	 *			but allow any number of groups
	 *			Groups are not additive, if a group moves
	 *			into a territory, only the armies originally in
	 *			that territory can fortify further
	 *			This setting can be joined with connected fortify to allow
	 *			any fortifications possible
	 *		- connected_fortify: If the game allows for connected fortifications
	 *			Allow the fortifying group to travel as far as possible
	 *			with the one caveat that it must travel through friendly
	 *			territories.
	 *			This setting can be joined with multiple fortify to allow
	 *			any fortifications possible
	 *		- kamikaze: If you can attack, you must attack
	 *		- warmonger: If you can trade, you must trade
	 *		- nuke: Use trade card bonus to DEDUCT from ENEMY land!
	 *		- turncoat: Use trade card to shift enemy allegiance to your army
	 *		- initial_army_limit: Set a limit on the number of armies
	 *			that can be placed in any single territory during
	 *			the initial game placement
	 *		- trade_number: The current number of times that a trade
	 *			has been made
	 *
	 * @var array
	 */
	static public $EXTRA_INFO_DEFAULTS = [
			'fortify' => true,
			'multiple_fortify' => false,
			'connected_fortify' => false,
			'kamikaze' => false,
			'warmonger' => false,
			'nuke' => false,
			'turncoat' => false,
			'initial_army_limit' => 0,
			'trade_number' => 0,
			'conquer_type' => 'none',
			'conquer_conquests_per' => 0,
			'conquer_per_number' => 0,
			'conquer_skip' => 0,
			'conquer_start_at' => 0,
			'conquer_minimum' => 1,
			'conquer_maximum' => 42,
	];


	/** public property board
	 *		Holds the game board data
	 *		format:
	 *		array(
	 *			territory_id => array('player_id', 'armies') ,
	 *			territory_id => array('player_id', 'armies') ,
	 *			territory_id => array('player_id', 'armies') ,
	 *		)
	 *
	 * @var array
	 */
	public $board;


	/** public property players
	 *		Holds our player's data
	 *		format: (indexed by player_id then associative)
	 *		array(
	 *			player_id => array('player_id', 'armies', 'order_num', 'state', 'cards' => array(1, 2, 3), 'extra_info' => array( ... )) ,
	 *			player_id => array('player_id', 'armies', 'order_num', 'state', 'cards' => array(4, 5, 6), 'extra_info' => array( ... )) ,
	 *		)
	 *
	 *		extra_info is an array that holds information about the current player state
	 *		such as where the player is occupying to and how many armies
	 *		and how many territories conquered this round, if they get a card, or forced trade, etc.
	 *
	 * @var array of player data
	 */
	public $players;


	/** public property current_player
	 *		The current player's id
	 *
	 * @var int
	 */
	public $current_player;


	/** public property new_player
	 *		Holds a flag letting the parent class know
	 *		that a new player has started their turn
	 *
	 * @var bool
	 */
	public $new_player;


	/** public property previous_dice
	 *		The dice from the most recent battle
	 *		format:
	 *		array(
	 *			'attack' => array(int[, int[, int]]) ,
	 *			'defend' => array(int[, int])
	 *		)
	 *
	 * @var array
	 */
	public $previous_dice;


	/** public property halt_redirect
	 *		Stops the script from redirecting
	 *
	 * @var bool
	 */
	public $halt_redirect = false;


	/** protected property _available_cards
	 *		The card ids still in the draw pile
	 *
	 * @var array of ints
	 */
	protected $_available_cards;


	/** protected property _game_id
	 *		The database id for the current game
	 *
	 * @var int
	 */
	protected $_game_id;


	/** protected property _trade_values
	 *		The trade value array to use when
	 *		selecting the next trade value
	 *
	 * @var array
	 */
	protected $_trade_values;


	/** protected property _trade_bonus
	 *		The trade bonus value to use when
	 *		player trades occupied card
	 *
	 * @var int
	 */
	protected $_trade_bonus;


	/** protected property _next_trade
	 *		The number of armies on next card trade in
	 *
	 * @var int
	 */
	protected $_next_trade;


	/** protected property _game_type
	 *		Holds the type of game this is
	 *		Can be one of: Original, Secret Mission, Capital
	 *		NOTE: not used yet
	 *
	 * @var string
	 */
	protected $_game_type = 'Original';


	/** protected property _extra_info
	 *		Holds the extra info for the game
	 *
	 * @see $EXTRA_INFO_DEFAULTS
	 * @var array
	 */
	protected $_extra_info;


	/**
	 * The game is fully controlled.
	 * Do minimal automatic actions (give card, etc)
	 *
	 * @var bool
	 */
	protected $_controlled = false;


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
	 * @param int $game_id optional
	 *
	 * @return Risk object reference
	 */
	public function __construct($game_id = 0) {
		call(__METHOD__);

		try {
			self::check_adjacencies( );
		}
		catch (MyException $e) {
			return false;
		}

		$this->_game_id = (int) $game_id;
		call($this->_game_id);

		$this->players = [];
		$this->new_player = false;

		if (defined('DEBUG')) {
			$this->_DEBUG = DEBUG;
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
	public function __get($property) {
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
	 * @param string $property
	 * @param mixed $value
	 *
	 * @action optional validation
	 *
	 * @return bool success
	 * @throws MyException
	 */
	public function __set($property, $value) {
		if ( ! property_exists($this, $property)) {
			throw new MyException(__METHOD__.': Trying to access non-existent property ('.$property.')', 3);
		}

		if ('_' === $property[0]) {
			throw new MyException(__METHOD__.': Trying to access _private property ('.$property.')', 3);
		}

		switch ($property) {
			case 'board' :
				try {
					$this->_test_board($value);
				}
				catch (MyException $e) {
					throw $e;
				}
				break;

			default :
				// do nothing
				break;
		}

		$this->$property = $value;
	}


	/**
	 * Setter for _controlled
	 *
	 * @param boolean $controlled
	 *
	 * @return void
	 */
	public function is_controlled($controlled) {
		$this->_controlled = $controlled;
	}


	/** public function init_random_board
	 *		Initializes a board by giving each
	 *		player a random territory in turn until
	 *		all territories are occupied
	 *
	 * @param void
	 *
	 * @action randomly inits the game board
	 *
	 * @return void
	 * @throws MyException
	 */
	public function init_random_board( ) {
		call(__METHOD__);

		if ( ! is_null($this->board)) {
			throw new MyException(__METHOD__.': Trying to initialize a non-empty board in game #'.$this->_game_id);
		}

		$land_ids = array_keys(self::$TERRITORIES);
		call($land_ids);
		$player_ids = array_keys($this->players);
		call($player_ids);
		$num_players = count($this->players);
		call($num_players);

		shuffle($land_ids);
		call($land_ids);

		$i = 0;
		foreach ($land_ids as $land_id) {
			$player_id = (int) $player_ids[($i % $num_players)];
			$board[$land_id] = [
				'player_id' => $player_id ,
				'armies' => 1 ,
			];
			++$i;

			--$this->players[$player_id]['armies'];

			call($board[$land_id]);
		}

		ksort($board);

		$this->board = $board;

		foreach ($board as $land_id => $data) {
			$log_data[$land_id] = $data['player_id'];
		}

		ksort($log_data);

		Game::log($this->_game_id, 'I '.implode(',', $log_data));
	}


	/**
	 * Initialize the board with data from the I log
	 * @param string $board
	 */
	public function init_board($board) {
		call(__METHOD__);
		call($board);

		$land_ids = array_keys(self::$TERRITORIES);

		$board = explode(' ', $board);
		$board = explode(',', ( ! empty($board[1])) ? $board[1] : $board[0]);

		$this_board = [];
		foreach ($land_ids as $land_id) {
			$this_board[$land_id] = [
				'player_id' => $board[$land_id - 1],
				'armies' => 1,
			];

			--$this->players[$board[$land_id - 1]]['armies'];
		}

		ksort($this_board);

		$this->board = $this_board;
		call($this->board);
	}


	/** public function place_start_armies
	 *		Randomly places the start armies
	 *		when the game starts
	 *
	 * @param void
	 *
	 * @return void
	 */
	public function place_start_armies( ) {
		call(__METHOD__);

		$placed = [];

		// place the start armies randomly
		foreach ($this->players as $player_id => $player) {
			$my_armies = $player['armies'];
			$territories = $this->get_players_territory($player_id);

			$territory_ids = array_keys($territories);

			// make sure our limit is high enough to allow placement of all armies
			if (0 != $this->_extra_info['initial_army_limit']) {
				$count = count($territory_ids);
				// we need to account for the armies already on the board (1 in each)
				// so add $count to $my_armies when testing
				while (($count * $this->_extra_info['initial_army_limit']) < ($my_armies + $count)) {
					++$this->_extra_info['initial_army_limit'];
				}
			}

			shuffle($territory_ids);

			call($territory_ids);
			while ($my_armies) {
				$land_id = $territory_ids[array_rand($territory_ids)];

				if (isset($placed[$player_id][$land_id])) {
					++$placed[$player_id][$land_id];
				}
				else {
					$placed[$player_id][$land_id] = 1;
				}

				--$my_armies;

				if (0 != $this->_extra_info['initial_army_limit']) {
					// account for the armies already on the board by subtracting 1 from the limit
					if ($placed[$player_id][$land_id] > ($this->_extra_info['initial_army_limit'] - 1)) {
						--$placed[$player_id][$land_id];
						continue;
					}
				}
			}
		}

		foreach ($placed as $player_id => $land) {
			foreach ($land as $land_id => $num_armies) {
				$this->place_armies($player_id, $num_armies, $land_id, $is_initial_placing = true);
			}
		}
	}


	/** public function set_game_type
	 *		Sets the type of game this is
	 *		Can be one of: Original, Secret mission, Capital
	 *
	 * @param string game type
	 *
	 * @return void
	 */
	public function set_game_type($value) {
		call(__METHOD__);

		$allowed = [
			'Original',
			'Secret Mission',
			'Capital',
		];

		if ( ! in_array($value, $allowed)) {
			$value = 'Original';
		}

		$this->_game_type = $value;
	}


	/** public function set_extra_info
	 *		Sets the extra info for the game
	 *
	 * @param array $extra_info
	 *
	 * @return void
	 */
	public function set_extra_info($extra_info) {
		call(__METHOD__);

		$extra_info = array_clean($extra_info, array_keys(self::$EXTRA_INFO_DEFAULTS));

		$this->_extra_info = array_merge_plus(self::$EXTRA_INFO_DEFAULTS, $extra_info);

		// the update trade value function depends on the extra info
		$this->_update_trade_value($log = false);

		if ('none' != $this->_extra_info['conquer_type']) {
			// the conquer limit calculation depends on the trade value info and extra info
			$this->calculate_conquer_limit( );
		}
	}


	/** public function calculate_conquest_limit
	 *		Calculates the conquer limit for the current player
	 *
	 * @param void
	 *
	 * @return void
	 */
	public function calculate_conquer_limit( ) {
		call(__METHOD__);

		if ( ! $this->current_player) {
			return;
		}

		// pull our variables out to use them here
		$type = false;
		foreach ($this->_extra_info as $key => $value) {
			if ('conquer_' == substr($key, 0, 8)) {
				$key = substr($key, 8);
				${$key} = $value;
			}
		}

		$land = $this->get_players_land( );

		// grab the base amount of [type] we are using for our calculation
		switch($type) {
			case 'trade_value' :
				$amount = $this->_next_trade;
				break;

			case 'trade_count' :
				$amount = $this->_extra_info['trade_number'];
				break;

			case 'rounds' :
				$amount = $this->players[$this->current_player]['extra_info']['round'];
				break;

			case 'turns' :
				$amount = $this->players[$this->current_player]['extra_info']['turn'];
				break;

			case 'land' :
				$amount = count($land);
				break;

			case 'continents' :
				$continents = $this->get_players_continents( );
				$amount = count($continents);
				break;

			case 'armies' :
				$amount = array_sum($land);
				break;

			default :
				$amount = 1;
				break;
		}
		$amount = (int) $amount;

		// the number of multipliers to skip before incrementing
		// e.g.- if it's 1 conquest per 10 armies, and skip is 1
		// the conquest value won't increase until army count reaches 20
		// which is 10 armies past when it would increase at 10
		if (empty($skip) || ! (int) $skip) {
			$skip = 0;
		}

		// the number of conquests to start from
		if (empty($start_at) || ! (int) $start_at) {
			$start_at = 0;
		}

		// the number of conquests to allow per multiplier
		// e.g.- if it's conquests per 10 armies, and conquests_per
		// is 1, you will gain 1 conquest for every 10 armies
		// so for 35 armies, you will get 3 (3 * 1) conquests
		if (empty($conquests_per) || ! (int) $conquests_per) {
			$conquests_per = 1;
		}

		// the number of items to bypass before increasing the multiplier
		// e.g.- if it is 2 conquests based on armies, and per_number
		// is set to 5, you will get 2 conquests for every 5 armies
		// so when you have 5-9 armies, you will get 2 conquests
		// and from 10-14 armies, 4 conquests
		if (empty($per_number) || ! (int) $per_number) {
			switch ($type) {
				case 'trade_value' : $per_number = 10; break;
				case 'trade_count' : $per_number =  2; break;
				case 'rounds'      : $per_number =  1; break;
				case 'turns'       : $per_number =  1; break;
				case 'land'        : $per_number =  3; break;
				case 'continents'  : $per_number =  1; break;
				case 'armies'      : $per_number = 10; break;
				default            : $per_number =  1; break;
			}
		}

		// set the default minimum to 1
		if (empty($minimum) || (1 > $minimum) || ! (int) $minimum) {
			$minimum = 1;
		}

		// set the default start to 0
		if (empty($start_at) || (0 > $start_at) || ! (int) $start_at) {
			$start_at = 0;
		}

		// set the default maximum to 42 (the number of territories)
		if (empty($maximum) || ! (int) $maximum) {
			$maximum = 42;
		}

		// if we are calculating based on trade_value, trade_count, or continents
		// the 1 point buffer needs to be added
		$start_count = 1;
		if (in_array($type, ['trade_value', 'trade_count', 'continents'])) {
			$start_count = 0;
		}

		$limit = max((((((int) floor(($amount - $start_count) / $per_number)) + 1) - $skip) * $conquests_per), 0) + $start_at;
		$limit = ($limit < $minimum) ? $minimum : $limit;
		$limit = ($limit > $maximum) ? $maximum : $limit;

		$this->_extra_info['conquer_limit'] = (int) $limit;
	}


	/** public function get_extra_info
	 *		Returns the extra info for the game
	 *
	 * @param void
	 *
	 * @return array
	 */
	public function get_extra_info( ) {
		call(__METHOD__);

		return $this->_extra_info;
	}


	/** public function get_type
	 *		Returns the game type
	 *
	 * @param void
	 *
	 * @return string game type
	 */
	public function get_type( ) {
		call(__METHOD__);

		return $this->_game_type;
	}


	/** public function set_trade_values
	 *		Sets the trade value array
	 *		to be used when setting the trade values
	 *
	 * @param array $trades values
	 * @param int $bonus value optional
	 *
	 * @return void
	 */
	public function set_trade_values($trades, $bonus = 2) {
		call(__METHOD__);

		$this->_trade_values = $trades;
		$this->_trade_bonus = (int) $bonus;
	}


	/** public function get_trade_value
	 *		Returns the next available card trade value
	 *
	 * @param void
	 *
	 * @return int next trade value
	 */
	public function get_trade_value( ) {
		return $this->_next_trade;
	}


	/** public function set_trade_value
	 *		Sets the next available card trade value
	 *
	 * @param int $value
	 *
	 * @return void
	 */
	public function set_trade_value($value) {
		$this->_next_trade = (int) $value;
	}


	/** public function get_start_armies
	 *		Returns the number of armies each player
	 *		has to place at the start of the game
	 *
	 * @param int $count optional player count
	 *
	 * @return int number of start armies
	 */
	public function get_start_armies($count = null) {
		call(__METHOD__);

		$count = (int) $count;

		$start_armies = [2 => 40, 35, 30, 25, 20];

		if ( ! empty($count)) {
			return $start_armies[$count];
		}

		return $start_armies[count($this->players)];
	}


	/** public function find_available_cards
	 *		Searches the player data and finds out which
	 *		cards are still available for drawing
	 *
	 * @param void
	 *
	 * @action sets $_available_cards
	 *
	 * @return void
	 * @throws MyException
	 */
	public function find_available_cards( ) {
		call(__METHOD__);

		$avail_cards = array_keys(self::$CARDS);
		$used_cards = [];

		if (is_array($this->players)) {
			foreach ($this->players as $player_id => $player) {
				if (is_array($player['cards'])) {
					foreach ($player['cards'] as $card_id) {
						if (in_array($card_id, $used_cards)) {
							throw new MyException(__METHOD__.': Duplicate card (#'.$card_id.') found');
						}
						else {
							$used_cards[] = $card_id;
						}
					}
				}
				else {
					$this->players[$player_id]['cards'] = [];
				}
			}
		}

		$avail_cards = array_diff($avail_cards, $used_cards);

		$this->_available_cards = $avail_cards;
		shuffle($this->_available_cards);
	}


	/** public function begin
	 *		Finds the first player, gives them some armies
	 *		and 'starts' the game
	 *
	 * @param void
	 *
	 * @return int first player id
	 */
	public function begin( ) {
		call(__METHOD__);

		// grab the first player's id and give them some armies to place
		foreach ($this->players as $player) {
			if (1 == $player['order_num']) {
				$this->current_player = $player['player_id'];
				break;
			}
		}

		Game::log($this->_game_id, 'N '.$this->current_player);
		$this->_add_armies($this->current_player);

		$this->set_player_state('Placing');

		return $this->current_player;
	}


	/** public function trade_cards
	 *		Trades the given cards for more armies
	 *
	 * @param array $card_ids
	 * @param int $bonus_card id
	 *
	 * @action tests and updates player data
	 *
	 * @return bool traded
	 * @throws MyException
	 */
	public function trade_cards($card_ids, $bonus_card = null) {
		call(__METHOD__);

		$player_id = $this->current_player;

		// make sure the player is in the proper state
		if ('Trading' != $this->players[$player_id]['state']) {
			throw new MyException(__METHOD__.': Player is in an incorrect state ('.$this->players[$player_id]['state'].')');
		}

		if (empty($card_ids)) {
			// the player didn't want to trade
			$this->set_player_state('Placing');
			return false;
		}

		// grab the cards
		array_trim($card_ids);

		// test the number of cards
		if (3 != count($card_ids)) {
			throw new MyException(__METHOD__.': Incorrect number of cards given to trade in');
		}

		// test the cards and make sure they are all valid cards
		$diff = array_diff($card_ids, array_keys(self::$CARDS));
		if ( ! empty($diff)) {
			throw new MyException(__METHOD__.': Trying to trade in cards that do not exist');
		}

		// test the cards and make sure we have them all
		$diff = array_diff($card_ids, $this->players[$player_id]['cards']);
		if ( ! empty($diff)) {
			throw new MyException(__METHOD__.': Player (#'.$player_id.') trying to trade in cards they do not have');
		}

		// test the bonus card
		if ( ! in_array($bonus_card, $card_ids)) {
			// if the bonus card is not one of the ones traded in, just disregard
			$bonus_card = 0;
		}

		// make sure the cards are a set
		try {
			$valid = $this->_test_card_set($card_ids);
		}
		catch (MyException $e) {
			throw new MyException($e->getMessage( ), $e->getCode( ));
		}

		if ( ! $valid) {
			return false;
		}

		// if we got past that gauntlet, give the player their armies
		$this->players[$player_id]['armies'] += $this->_next_trade;
		++$this->_extra_info['trade_number'];

		// remove the cards from the player
		$this->players[$player_id]['cards'] = array_diff($this->players[$player_id]['cards'], $card_ids);

		// shuffle the cards back in the pile
		$this->_available_cards = array_merge($this->_available_cards, $card_ids);
		shuffle($this->_available_cards);

		// check the bonus card ownership
		$is_nuke = 0;
		$is_turncoat = 0;

		if ($this->_extra_info['nuke'] || $this->_extra_info['turncoat']) {
			if ( ! empty($bonus_card) && $this->_extra_info['nuke']) {
				$nuked_land_armies = ($this->board[$bonus_card]['armies'] -= $this->_trade_bonus);
				if (1 > $nuked_land_armies) {
					$this->board[$bonus_card]['armies'] = 1;
				}
				$is_nuke = 1;
			}

			// playing turncoat card
			if ( ! empty($bonus_card) && $this->_extra_info['turncoat']) {
				$is_turncoat = 1;
			}
			else {
				// if the bonus card is not owned by the player, just disregard
				$bonus_card = 0;
			}
		}
		else { // not using nuke or turncoat game config
			if ( ! empty($bonus_card) && ($player_id == $this->board[$bonus_card]['player_id'])) {
				$this->board[$bonus_card]['armies'] += $this->_trade_bonus;
			}
			else {
				// if the bonus card is not owned by the player, just disregard
				$bonus_card = 0;
			}
		}

		Game::log($this->_game_id, 'T '.$player_id.':'.implode(',', $card_ids).':'.$this->_next_trade.':'.$bonus_card.':'.$is_nuke.':'.$is_turncoat);

		// update the next trade in value
		$this->_update_trade_value( );

		// test the players forced state
		$this->players[$player_id]['extra_info']['forced'] = (4 >= count($this->players[$player_id]['cards'])) ? false : true;

		// place the player into an appropriate state based
		// on the number of cards they are holding, and if there
		// is a match in those cards
		if ($this->_player_can_trade($player_id)) {
			$this->set_player_state('Trading');
		}
		else {
			$this->set_player_state('Placing');
		}

		return true;
	}


	/** public function place_armies
	 *		Places $num_armies armies onto $land_id
	 *		for player $player_id
	 *
	 * @param int $player_id
	 * @param int $num_armies
	 * @param int $land_id
	 * @param bool $is_initial_placing optional test initial placement limit
	 *
	 * @action tests and updates board and player data
	 *
	 * @return int number of armies placed
	 * @throws MyException
	 */
	public function place_armies($player_id, $num_armies, $land_id, $is_initial_placing = false) {
		call(__METHOD__);

		// make sure this player exists
		if (empty($this->players[$player_id])) {
			throw new MyException(__METHOD__.': Player #'.$player_id.' was not found in game');
		}

		// make sure the player is in the proper state
		if ('Placing' != $this->players[$player_id]['state']) {
			throw new MyException(__METHOD__.': Player is in an incorrect state ('.$this->players[$player_id]['state'].')');
		}

		// make sure this player occupies this bit of land
		if ($player_id != $this->board[$land_id]['player_id']) {
			throw new MyException(__METHOD__.': Player #'.$player_id.' does not occupy territory #'.$land_id.' ('.self::$TERRITORIES[$land_id][NAME].')');
		}

		// make sure this player is placing armies
		if (0 === $num_armies) {
			return $num_armies;
		}

		// make sure this player has enough armies to place
		if ($num_armies > $this->players[$player_id]['armies']) {
			$num_armies = $this->players[$player_id]['armies'];
		}

		// make sure the place limit hasn't been reached for this territory
		if ($is_initial_placing && (0 != $this->_extra_info['initial_army_limit'])) {
			if (($this->board[$land_id]['armies'] + $num_armies) > $this->_extra_info['initial_army_limit']) {
				$num_armies = $this->_extra_info['initial_army_limit'] - $this->board[$land_id]['armies'];
			}
		}

		// all good ? continue...
		// place the armies on the board
		$this->board[$land_id]['armies'] += $num_armies;

		// remove those armies from the stockpile
		$this->players[$player_id]['armies'] -= $num_armies;

		Game::log($this->_game_id, 'P '.$player_id.':'.$num_armies.':'.$land_id);

		return $num_armies;
	}


	/** public function attack
	 *		ATTACK !!!
	 *
	 * @param int $num_armies number of attacking armies
	 * @param int $attack_land_id
	 * @param int $defend_land_id
	 * @param int $attack_roll optional
	 * @param int $defend_roll optional
	 *
	 * @action tests and updates board and player data
	 *
	 * @return array (string outcome, int armies involved)
	 * @throws MyException
	 */
	public function attack($num_armies, $attack_land_id, $defend_land_id, $attack_roll = null, $defend_roll = null) {
		call(__METHOD__);

		$attack_id = $this->current_player;

		// make sure the player is in the proper state
		if ('Attacking' != $this->players[$attack_id]['state']) {
			throw new MyException(__METHOD__.': Player is in an incorrect state ('.$this->players[$attack_id]['state'].')', 222);
		}

		// make sure we haven't passed the conquer limit
		if (isset($this->_extra_info['conquer_limit']) && ($this->players[$attack_id]['extra_info']['conquered'] >= $this->_extra_info['conquer_limit'])) {
			$this->set_player_next_state($this->players[$attack_id]['state'], $attack_id);
			throw new MyException(__METHOD__.': Attacking player (#'.$attack_id.') cannot attack any more territories this round. (Only '.$this->_extra_info['conquer_limit'].' allowed)');
		}

		// test and make sure this player occupies the attacking land
		if ($attack_id != $this->board[$attack_land_id]['player_id']) {
			throw new MyException(__METHOD__.': Attacking player (#'.$attack_id.') does not occupy the attacking territory (#'.$attack_land_id.') ('.self::$TERRITORIES[$attack_land_id][NAME].')');
		}

		// test and make sure the attacking player does not occupy the defending land
		if ($attack_id == $this->board[$defend_land_id]['player_id']) {
			throw new MyException(__METHOD__.': Attacking player (#'.$attack_id.') occupies the defending territory (#'.$defend_land_id.') ('.self::$TERRITORIES[$defend_land_id][NAME].')');
		}

		// test and make sure the two lands are adjacent
		if ( ! in_array($defend_land_id, self::$TERRITORIES[$attack_land_id][ADJACENT])) {
			throw new MyException(__METHOD__.': Attacking territory (#'.$attack_land_id.') ('.self::$TERRITORIES[$attack_land_id][NAME].') is not adjacent to the defending territory (#'.$defend_land_id.') ('.self::$TERRITORIES[$defend_land_id][NAME].')');
		}

		// make sure the player has enough armies
		if (1 >= $this->board[$attack_land_id]['armies']) {
			throw new MyException(__METHOD__.': Attacking player (#'.$attack_id.') does not have enough armies to attack ('.$this->board[$attack_land_id]['armies'].')', 201);
		}

		// we done with errors yet? geez...

		// adjust the number of attacking armies to the max available if lower
		// there MUST be at least one army remaining in the attacking territory
		if ($num_armies >= $this->board[$attack_land_id]['armies']) {
			$num_armies = ($this->board[$attack_land_id]['armies'] - 1);
		}

		// grab the number of defending armies and the defender's id
		$defend_id = $this->board[$defend_land_id]['player_id'];
		$defend_armies = $this->board[$defend_land_id]['armies'];

		$attack_armies = $num_armies;

		// normalize the army numbers
		if (3 <= $attack_armies) {
			$attack_armies = 3;
		}

		if (2 <= $defend_armies) {
			$defend_armies = 2;
		}

		// roll the dice
		list($attack_dead, $defend_dead) = $this->_roll($attack_armies, $defend_armies, $attack_roll, $defend_roll);

		// make the changes to the board
		$this->board[$attack_land_id]['armies'] -= $attack_dead;
		$this->board[$defend_land_id]['armies'] -= $defend_dead;

		// find out the outcome
		$defeated = false;
		if (0 == $this->board[$defend_land_id]['armies']) {
			$defeated = true;
			$this->board[$defend_land_id]['player_id'] = $attack_id;

			// increase our conquered count if we need to
			if (isset($this->_extra_info['conquer_limit'])) {
				$this->players[$attack_id]['extra_info']['conquered']++;
			}

			// test if we completely eradicated the defender
			$this->_test_killed($attack_id, $defend_id);

			$this->set_player_state('Occupying');
			$this->players[$attack_id]['extra_info']['get_card'] = true;

			// i had originally had this as $num_armies - $attack_dead as the number of armies forced to occupy
			// but upon further reflection, i noted that in order for the defending territory to be defeated
			// there must be NO $attack_dead armies, so i changed it to merely $num_armies
			// ---
			// if there are 2 defending armies (the most one can defeat in one turn), then both must be killed = no dead attackers
			// if there are 2 defending armies and it wins one and losses one, there is one left on the territory = no defeat
			// if there is 1 defending army, and it wins, the attack is over and must be started again fresh = no defeat
			// if there is 1 defending army, and it loses, it did so on the first roll, and therefore...   no dead attackers
			$this->players[$attack_id]['extra_info']['occupy'] = $attack_armies.':'.$attack_land_id.'->'.$defend_land_id;
		}

		Game::log($this->_game_id, 'A '.$attack_id.':'.$attack_land_id.':'.$defend_id.':'.$defend_land_id.':'.implode('', $this->previous_dice['attack']).','.implode('', $this->previous_dice['defend']).':'.$attack_dead.','.$defend_dead.':'.(int) $defeated);
		Game::process_deferred_log($this->_game_id); // because the killed log message should come after the attack message

		// this makes more sense in the _test_killed function, but i needed the occupy info
		$this->_test_win( );

		// if only single armies found, skip occupy and fortify and go to next player
		// if we still have at least one fighting army here, don't bother
		if ($attack_armies == $attack_dead) {
			$this->_test_attack( );
		}

		return [$defend_id, $defeated];
	}


	/** public function occupy
	 *		uses the data set up by the attack function
	 *		to determine which land to occupy and how many
	 *		armies MUST be moved, and moves the given number
	 *		of armies into the defeated land
	 *
	 * @param int $num_armies
	 *
	 * @action tests and updates board and player data
	 *
	 * @return int occupied land id
	 * @throws MyException
	 */
	public function occupy($num_armies) {
		call(__METHOD__);

		$player_id = $this->current_player;

		// make sure the player is in the proper state
		if ('Occupying' != $this->players[$player_id]['state']) {
			throw new MyException(__METHOD__.': Player is in an incorrect state ('.$this->players[$player_id]['state'].')');
		}

		// check the player extra info and see if we are moving enough armies
		if (preg_match('/(\\d+):(\\d+)->(\\d+)/', $this->players[$player_id]['extra_info']['occupy'], $matches)) {
			list($null, $move_armies, $from_land_id, $to_land_id) = $matches;
		}
		else {
			throw new MyException(__METHOD__.': Occupation data lost from extra_info');
		}

		if ($num_armies < $move_armies) {
			throw new MyException(__METHOD__.': Player needs to occupy with at least '.$move_armies.' armies, trying to occupy with only '.$num_armies.' armies');
		}

		// test and make sure this player occupies the FROM land
		if ($player_id != $this->board[$from_land_id]['player_id']) {
			throw new MyException(__METHOD__.': Occupying player (#'.$player_id.') does not control the FROM territory (#'.$from_land_id.') ('.self::$TERRITORIES[$from_land_id][NAME].')');
		}

		// test and make sure this player occupies the TO land
		if ($player_id != $this->board[$to_land_id]['player_id']) {
			throw new MyException(__METHOD__.': Occupying player (#'.$player_id.') does not control the TO territory (#'.$to_land_id.') ('.self::$TERRITORIES[$to_land_id][NAME].')');
		}

		// test and make sure the two lands are adjacent
		if ( ! in_array($to_land_id, self::$TERRITORIES[$from_land_id][ADJACENT])) {
			throw new MyException(__METHOD__.': FROM territory (#'.$from_land_id.') ('.self::$TERRITORIES[$from_land_id][NAME].') is not adjacent to the TO territory (#'.$to_land_id.') ('.self::$TERRITORIES[$to_land_id][NAME].')');
		}

		// make sure the player has enough armies
		if (1 >= $this->board[$from_land_id]['armies']) {
			throw new MyException(__METHOD__.': Player (#'.$player_id.') does not have enough armies to occupy ('.$this->board[$from_land_id]['armies'].')');
		}

		// make sure the player retains enough armies in the FROM land
		if (1 > ($this->board[$from_land_id]['armies'] - $num_armies)) {
			$num_armies = ($this->board[$from_land_id]['armies'] - 1);
		}

		// move the armies from the FROM land, to the TO land
		// (such a simple little thing after all those tests and exceptions)
		$this->board[$from_land_id]['armies'] -= $num_armies;
		$this->board[$to_land_id]['armies'] += $num_armies;

		Game::log($this->_game_id, 'O '.$player_id.':'.$num_armies.':'.$from_land_id.':'.$to_land_id);

		// erase the occupy data and return to an Attacking state
		$this->players[$player_id]['extra_info']['occupy'] = null;
		$this->set_player_state('Attacking');

		return $to_land_id;
	}


	/** public function fortify
	 *		moves $num_armies armies from $from_land_id
	 *		into $to_land_id, if possible
	 *
	 * @param int $num_armies
	 * @param int $from_land_id
	 * @param int $to_land_id
	 *
	 * @action tests and updates board and player data
	 *
	 * @return int number of armies moved
	 * @throws MyException
	 */
	public function fortify($num_armies, $from_land_id, $to_land_id) {
		call(__METHOD__);

		$player_id = $this->current_player;

		// make sure the player is in the proper state
		if ('Fortifying' != $this->players[$player_id]['state']) {
			throw new MyException(__METHOD__.': Player is in an incorrect state ('.$this->players[$player_id]['state'].')');
		}

		if (0 == $num_armies) {
			// the player is forfeiting the fortify move
			$this->set_player_state('Waiting');
			return;
		}

		// test and make sure this player occupies the FROM land
		if ($player_id != $this->board[$from_land_id]['player_id']) {
			throw new MyException(__METHOD__.': Occupying player (#'.$player_id.') does not control the FROM territory (#'.$from_land_id.') ('.self::$TERRITORIES[$from_land_id][NAME].')');
		}

		// test and make sure this player occupies the TO land
		if ($player_id != $this->board[$to_land_id]['player_id']) {
			throw new MyException(__METHOD__.': Occupying player (#'.$player_id.') does not control the TO territory (#'.$to_land_id.') ('.self::$TERRITORIES[$to_land_id][NAME].')');
		}

		if ( ! $this->_extra_info['connected_fortify']) {
			// test and make sure the two lands are adjacent
			if ( ! in_array($to_land_id, self::$TERRITORIES[$from_land_id][ADJACENT])) {
				throw new MyException(__METHOD__.': FROM territory (#'.$from_land_id.') ('.self::$TERRITORIES[$from_land_id][NAME].') is not adjacent to the TO territory (#'.$to_land_id.') ('.self::$TERRITORIES[$to_land_id][NAME].')');
			}
		}
		else {
			// test and make sure the two lands are connected by friendly territories
			if ( ! $this->_is_connected($from_land_id, $to_land_id)) {
				throw new MyException(__METHOD__.': FROM territory (#'.$from_land_id.') ('.self::$TERRITORIES[$from_land_id][NAME].') is not connected to the TO territory (#'.$to_land_id.') ('.self::$TERRITORIES[$to_land_id][NAME].')');
			}
		}

		// make sure the player has enough armies
		if (1 >= $this->board[$from_land_id]['armies']) {
			throw new MyException(__METHOD__.': Player (#'.$player_id.') does not have enough armies to fortify ('.$this->board[$from_land_id]['armies'].')');
		}

		// make sure player had enough armies in the original board
		if ($this->_extra_info['multiple_fortify'] && ! $this->_extra_info['connected_fortify']) {
			if ( ! isset($_SESSION['board'])) {
				// something happened...  logged off and back on maybe ???
				// just skip fortifying
				$this->set_player_state('Waiting');
				throw new MyException(__METHOD__.': Original army data was not found, skipping fortification for player #'.$player_id);
			}

			if (1 >= $_SESSION['board'][$from_land_id]['armies']) {
				throw new MyException(__METHOD__.': Player (#'.$player_id.') did not have enough armies in the original setup to fortify ('.$_SESSION['board'][$from_land_id]['armies'].')');
			}

			// make sure the player retains enough armies in the original FROM land
			if (1 > ($_SESSION['board'][$from_land_id]['armies'] - $num_armies)) {
				$num_armies = ($_SESSION['board'][$from_land_id]['armies'] - 1);
			}
		}
		else {
			// make sure the player retains enough armies in the FROM land
			if (1 > ($this->board[$from_land_id]['armies'] - $num_armies)) {
				$num_armies = ($this->board[$from_land_id]['armies'] - 1);
			}
		}

		// move the armies from the FROM land, to the TO land
		// (such a simple little thing after all those tests and exceptions)
		$this->board[$from_land_id]['armies'] -= $num_armies;
		$this->board[$to_land_id]['armies'] += $num_armies;

		Game::log($this->_game_id, 'F '.$player_id.':'.$num_armies.':'.$from_land_id.':'.$to_land_id);

		if ( ! $this->_extra_info['multiple_fortify']) {
			$this->set_player_state('Waiting');
		}
		elseif ( ! $this->_extra_info['connected_fortify']) {
			// keep a record of when the original armies were moved
			$_SESSION['board'][$from_land_id]['armies'] = $_SESSION['board'][$from_land_id]['armies'] - $num_armies;
			$this->_session_board_test_fortify($player_id);
		}

		return $num_armies;
	}


	/** public function set_player_state
	 *		places the given player into the given state,
	 *		if possible
	 *		if placing is set to true, will not try to update the
	 *		next player on 'Waiting'
	 *
	 * @param string $state
	 * @param int $player_id optional
	 * @param bool $placing optional placing flag
	 *
	 * @action tests and updates player data
	 *
	 * @return void
	 * @throws MyException
	 */
	public function set_player_state($state, $player_id = 0, $placing = false) {
		call(__METHOD__);
		call($state);

		$player_id = (int) $player_id;

		if (empty($player_id)) {
			$player_id = $this->current_player;
		}

		// the array of states that we can be in
		$allowed_states = [
			'Waiting' ,
			'Trading' ,
			'Placing' ,
			'Attacking' ,
			'Occupying' ,
			'Fortifying' ,
			'Resigned' ,
			'Dead' ,
		];

		// so we don't place ourselves into a state that does not directly follow
		// another state, use this array to test our current state
		// this stops state changes such as going from Trading to Fortifying
		// NOTE the array_combine with the above array
		$allowed_from_states = array_combine($allowed_states, [
			/* Waiting */		['Fortifying', 'Placing'],
			/* Trading */		['Waiting', 'Trading'],
			/* Placing */		['Waiting', 'Trading'],
			/* Attacking */		['Placing', 'Occupying'],
			/* Occupying */		['Attacking'],
			/* Fortifying */	['Attacking'],
			/* Resigned */		['Waiting'],
			/* Dead */			['Waiting'],
		]);

		// if the given state does not exist
		if ( ! in_array($state, $allowed_states)) {
			throw new MyException(__METHOD__.': Trying to put a player (#'.$player_id.') into an unsupported state ('.$state.')');
		}

		// if the player is already in this state
		if ($this->players[$player_id]['state'] === $state) {
			return;
		}

		// if the given state does not follow our current state
		if ( ! in_array($this->players[$player_id]['state'], $allowed_from_states[$state])) {
			throw new MyException(__METHOD__.': Trying to put a player (#'.$player_id.') into a state ('.$state.') that does not correctly follow their current state ('.$this->players[$player_id]['state'].')', 191);
		}

		// don't fortify if the rules disallow it
		if ( ! $this->_extra_info['fortify'] && ('Fortifying' == $state)) {
			$state = 'Waiting';
		}

		// do some other things that go with the state change
		switch ($state) {
			case 'Waiting' :
				unset($_SESSION['board']);

				// check if we get a card for this round
				$this->_award_card( );

				// reset our conquered count
				$this->players[$player_id]['extra_info']['conquered'] = 0;

				// our turn is over, find the next player
				try {
					if ( ! $placing) {
						$this->_next_player($player_id);
						$this->new_player = true;

						// increment our round count
						$this->players[$player_id]['extra_info']['round']++;

						// increment our turn count
						$this->players[$this->current_player]['extra_info']['turn'] = $this->players[$player_id]['extra_info']['turn'] + 1;
					}
				}
				catch (MyException $e) {
					// do nothing, yet...
				}
				break;

			case 'Trading' :
				// don't give a card if it's a forced trade
				if ('Waiting' == $this->players[$player_id]['state']) {
					// check if we forgot to get a card for a previous round
					$this->_award_card( );
				}
				break;

			case 'Placing' :
				// check for a forced trade, but don't
				// force trading on 'Occupying', it will
				// become very confusing.  let the player
				// move the occupying armies, THEN force the trade
				if ($this->players[$player_id]['extra_info']['forced']) {
					$state = 'Trading';
				}
				break;

			case 'Attacking' :
				// check for a forced trade, but don't
				// force trading on 'Occupying', it will
				// become very confusing.  let the player
				// move the occupying armies, THEN force the trade
				if ($this->players[$player_id]['extra_info']['forced']) {
					$state = 'Trading';
				}
				elseif ( ! $this->_test_attack($player_id)) {
					// don't continue on, if this fails, the player is in the correct state
					// having been set that way in the _test_attack function
					return;
				}
				else {
					// check if we are only allowed so many conquests this round
					if (isset($this->_extra_info['conquer_limit']) && ($this->players[$player_id]['extra_info']['conquered'] >= $this->_extra_info['conquer_limit'])) {
						if ( ! $this->halt_redirect) {
							Flash::store('You have conquered your limit for this round ('.$this->_extra_info['conquer_limit'].')', false);
						}

						// fake our state and go to fortifying if we have conquered all we can this round
						$this->players[$player_id]['state'] = 'Attacking';
						$this->set_player_state('Fortifying', $player_id);
						return;
					}
				}
				break;

			case 'Fortifying' :
				// save a copy of the board so we can check it against the fortifications
				// people might try to do...   only original armies are allowed to move to
				// adjacent territories
				if ($this->_extra_info['multiple_fortify'] && ! $this->_extra_info['connected_fortify']) {
					$_SESSION['board'] = $this->board;
				}
				break;

			case 'Resigned' :
				Game::log($this->_game_id, 'Q '.$player_id);
				break;

			case 'Dead' :
				// check if this player has armies
				if (count($this->get_players_land($player_id))) {
					throw new MyException(__METHOD__.': Trying to put a player (#'.$player_id.') into an dead state while they still have armies');
				}

				// put all of this players cards back into the deck
				// (we don't have to actually put them back in the deck,
				// when the game auto-saves, it will remove them from the
				// player and then the next time it loads, all will be well)
				$this->players[$player_id]['cards'] = [];
				break;

			default :
				// do nothing
				break;
		}

		$this->players[$player_id]['state'] = $state;
	}


	/** public function set_player_next_state
	 *		places the given player into the next state,
	 *		if possible
	 *
	 * @param string $cur_state players current state
	 * @param int $player_id
	 *
	 * @action tests and updates player data
	 *
	 * @return void
	 * @throws MyException
	 */
	public function set_player_next_state($cur_state, $player_id) {
		call(__METHOD__);
		call($cur_state);
		call($player_id);

		$player_id = (int) $player_id;

		if (strtolower($cur_state) != strtolower($this->players[$player_id]['state'])) {
			throw new MyException(__METHOD__.': Submitted state does not match player\'s current state');
		}

		if (('Attacking' == $cur_state) && $this->_extra_info['kamikaze']) {
			throw new MyException(__METHOD__.': Trying to skip attack in a kamikaze game');
		}

		if (('Trading' == $cur_state) && $this->_extra_info['warmonger']) {
			throw new MyException(__METHOD__.': Trying to skip trade in a warmonger game');
		}

		$next_states = [
			'Trading' => 'Placing' ,
			'Placing' => 'Attacking' ,
			'Attacking' => 'Fortifying' ,
			'Fortifying' => 'Waiting' ,
		];

		try {
			$this->set_player_state($next_states[$this->players[$player_id]['state']], $player_id);
		}
		catch (MyException $e) {
			throw $e;
		}
	}


	/** public function get_players_territory
	 *		Grab all the land owned by the current player
	 *		and return it as an array where the land_id is the key
	 *		and the land_name is the value
	 *
	 * @param int $player_id optional
	 *
	 * @return array players land
	 */
	public function get_players_territory($player_id = 0) {
		call(__METHOD__);

		$player_id = (int) $player_id;

		if (empty($player_id)) {
			$player_id = $this->current_player;
		}

		// grab all the players land
		$land = [];
		foreach ($this->board as $land_id => $territory) {
			if ($player_id == $territory['player_id']) {
				$land[$land_id] = self::$TERRITORIES[$land_id][NAME];
			}
		}

		asort($land);

		return $land;
	}


	/** public function get_adjacent_territories
	 *		Grabs the ids for all emeny territories adjacent to the player
	 *
	 * @param int $player_id optional
	 *
	 * @return string player state
	 */
	public function get_adjacent_territories($player_id = null) {
		call(__METHOD__);

		$player_id = (int) $player_id;

		if (empty($player_id)) {
			$player_id = $this->current_player;
		}

		// grab all the players land
		$land = $this->get_players_territory($player_id);
		$land_ids = array_keys($land);

		// grab all the adjacent lands
		$adjacent = [];
		if (is_array($land_ids)) {
			foreach ($land_ids as $land_id) {
				$adjacent = array_merge($adjacent, self::$TERRITORIES[$land_id][ADJACENT]);
			}

			// remove any adjacent lands that we occupy
			$adjacent = array_unique($adjacent);
			$adjacent = array_diff($adjacent, $land_ids);
		}
		call($adjacent);

		return $adjacent;
	}


	/** public function get_others_territory
	 *		Grab all the land NOT owned by the current player
	 *		and return it as an array where the land_id is the key
	 *		and the land_name is the value
	 *
	 * @param int $player_id optional
	 *
	 * @return array players land
	 */
	public function get_others_territory($player_id = 0) {
		call(__METHOD__);

		$player_id = (int) $player_id;

		if (empty($player_id)) {
			$player_id = $this->current_player;
		}

		// grab all the players land
		$land = [];
		foreach ($this->board as $land_id => $territory) {
			if ($player_id != $territory['player_id']) {
				$land[$land_id] = self::$TERRITORIES[$land_id][NAME];
			}
		}

		asort($land);

		return $land;
	}

	/** public function get_turncoat_territory
	 *		Grab all the land NOT owned by the current player
	 *		and return it as an array where the land_id is the key
	 *		and the land_name is the value
	 *		and opponent land count greater than one
	 *
	 * @param int $player_id optional
	 *
	 * @return array players land
	 */
	public function get_turncoat_territory($player_id = 0) {
		call(__METHOD__);

		$player_id = (int) $player_id;

		if (empty($player_id)) {
			$player_id = $this->current_player;
		}

		// grab all the players land
		$land = [];
		foreach ($this->board as $land_id => $territory) {

			$ids = [];
			foreach ($this->board as $data) {

				if (count($this->get_players_territory($data['player_id'])) > 1) {
					$ids[] = $data['player_id'];
				}
			}

			$opponent = $ids;

			if ($player_id != $territory['player_id'] && in_array($territory['player_id'], ($opponent))) {
				$land[$land_id] = self::$TERRITORIES[$land_id][NAME];
			}
		}

		asort($land);

		return $land;
	}

	/** public function get_players_cards
	 *		Grab all the cards owned by the current player
	 *		and return it as a 2-D array where the card_id is the key
	 *		and the card data array is the value
	 *
	 * @param int $player_id optional
	 *
	 * @return array players cards
	 */
	public function get_players_cards($player_id = 0) {
		call(__METHOD__);

		$player_id = (int) $player_id;

		if (empty($player_id)) {
			$player_id = $this->current_player;
		}

		// grab all the players cards
		$cards = [];
		foreach ($this->players[$player_id]['cards'] as $card_id) {
			$cards[$card_id] = self::$CARDS[$card_id];
		}

		return $cards;
	}


	/** public function get_players_extra_info
	 *		Returns the extra info for the given player
	 *
	 * @param int $player_id optional
	 *
	 * @return array players extra info
	 */
	public function get_players_extra_info($player_id = 0) {
		call(__METHOD__);

		$player_id = (int) $player_id;

		if (empty($player_id)) {
			$player_id = $this->current_player;
		}

		return $this->players[$player_id]['extra_info'];
	}


	/** public function get_players_land
	 *		Grab all the land owned by the current player
	 *		and return it as an array where the land_id is the key
	 *		and the armies is the value
	 *
	 * @param int $player_id optional
	 *
	 * @return array players land
	 */
	public function get_players_land($player_id = 0) {
		call(__METHOD__);

		$player_id = (int) $player_id;

		if (empty($player_id)) {
			$player_id = $this->current_player;
		}

		// grab all the players land
		$land = [];
		if (is_array($this->board)) {
			foreach ($this->board as $land_id => $territory) {
				if ($player_id == $territory['player_id']) {
					$land[$land_id] = $territory['armies'];
				}
			}
		}

		return $land;
	}


	/** public function get_players_continents
	 *		Grab all the continents owned by the current player
	 *		and return it as an array where the cont_id is the key
	 *		and the cont array is the value
	 *
	 * @param int $player_id optional
	 *
	 * @return array players continents
	 */
	public function get_players_continents($player_id = 0) {
		call(__METHOD__);

		$player_id = (int) $player_id;

		if (empty($player_id)) {
			$player_id = $this->current_player;
		}

		$land = $this->get_players_land($player_id);
		$land_ids = array_keys($land);

		// calculate if the player controls any continents
		$continents = [];
		foreach (self::$CONTINENTS as $cont_id => $cont) {
			$diff = array_diff($cont[TERRITORIES], $land_ids);

			// the diff is empty if all the land in the continent is occupied
			if (empty($diff)) {
				$continents[$cont_id] = $cont;
			}
		}

		return $continents;
	}


	/** protected function _test_killed
	 *		Check to see if we completely eradicated one player
	 *		from the game, and if so, transfer all their cards
	 *
	 * @param int $attack_id
	 * @param int $defend_id
	 *
	 * @action tests and updates player data
	 *
	 * @return bool defender is dead
	 */
	protected function _test_killed($attack_id, $defend_id) {
		call(__METHOD__);

		$not_found = true;
		foreach ($this->board as $land_id => $land) {
			if (($defend_id == $land['player_id']) && (0 < $land['armies'])) {
				$not_found = false;
				break;
			}
		}

		if ($not_found) {
			if ('Resigned' != $this->players[$defend_id]['state']) {
				$this->players[$defend_id]['state'] = 'Dead'; // set the player to dead
			}

			$this->players[$attack_id]['cards'] = array_merge($this->players[$attack_id]['cards'], $this->players[$defend_id]['cards']); // give the attacker the defender's cards

			Game::log_deferred($this->_game_id, 'E '.$attack_id.':'.$defend_id.':'.implode(',', $this->players[$defend_id]['cards']));

			$this->players[$defend_id]['cards'] = [];

// TODO: if I want to make the forced value optional between 5 and 6, here is where I can do that
			if (6 <= count($this->players[$attack_id]['cards'])) {
				$this->players[$attack_id]['extra_info']['forced'] = true;
			}
		}

		return $not_found;
	}


	/** protected function _test_win
	 *		Check to see if we won the game
	 *		and perform our occupy if we have
	 *
	 * @param void
	 *
	 * @action tests and updates player data
	 *
	 * @return bool someone won the game
	 */
	protected function _test_win( ) {
		call(__METHOD__);

		$alive = [];

		// check the board for any other viable players
		foreach ($this->players as $player_id => $player) {
			if ( ! in_array($player['state'], ['Resigned', 'Dead'])) {
				$alive[] = $player_id;
			}
		}

		if (1 !== count($alive)) {
			return false;
		}

		$winner = $alive[0];

		// perform the winner's occupy
		if ( ! $this->_controlled) {
			$this->occupy(9999);
		}

		Game::log($this->_game_id, 'D ' . $winner);

		return true;
	}


	/** protected function _test_attack
	 *		Check to see if we can attack at all
	 *		(we have at least one territory with more than
	 *		one army on it), if not, skip everything and go
	 *		directly to next player
	 *
	 * @param int $player_id optional
	 *
	 * @action tests and updates player data
	 *
	 * @return bool player can attack
	 */
	protected function _test_attack($player_id = 0) {
		call(__METHOD__);

		$player_id = (int) $player_id;

		if (empty($player_id)) {
			$player_id = $this->current_player;
		}

		$land = $this->get_players_land($player_id);

		// check for attackable sized armies
		$has_armies = false;
		$can_attack = false;
		foreach ($land as $land_id => $armies) {
			if (1 < $armies) {
				$has_armies = true;

				// test the adjacent territories for opponents
				foreach (self::$TERRITORIES[$land_id][ADJACENT] as $adjacent) {
					if ($this->current_player != $this->board[$adjacent]['player_id']) {
						$can_attack = true;
						break 2;
					}
				}
			}
		}

		if ( ! $can_attack) {
			if ( ! $this->halt_redirect) {
				Flash::store('You can no longer attack', false);
			}

			// we are switching to another state and we need to be in an
			// appropriate state to get there, so set the
			// appropriate state and then set our official state
			if ( ! $has_armies) {
				if ( ! $this->halt_redirect) {
					Flash::store('You can no longer fortify', true);
				}

				$this->players[$player_id]['state'] = 'Fortifying';
				$this->set_player_state('Waiting', $player_id);
			}
			else {
				$this->players[$player_id]['state'] = 'Attacking';
				$this->set_player_state('Fortifying', $player_id);
			}
		}

		return $can_attack;
	}


	/** protected function _session_board_test_fortify
	 *		Check to see if we can fortify at all
	 *
	 * @param int $player_id
	 *
	 * @action tests and updates player data
	 *
	 * @return bool player can fortify
	 * @throws MyException
	 */
	protected function _session_board_test_fortify($player_id) {
		call(__METHOD__);

		$player_id = (int) $player_id;

		if ( ! $player_id) {
			throw new MyException(__METHOD__.': Missing required player id');
		}

		$land = [];
		foreach ($_SESSION['board'] as $land_id => $data) {
			if ($data['player_id'] == $player_id) {
				$land[$land_id] = $data['armies'];
			}
		}

		// check for fortifiable sized armies
		$has_armies = false;
		$can_fortify = false;
		foreach ($land as $land_id => $armies) {
			if (1 < $armies) {
				$has_armies = true;

				// test the adjacent territories for our lands
				foreach (self::$TERRITORIES[$land_id][ADJACENT] as $adjacent) {
					if ($player_id == $_SESSION['board'][$adjacent]['player_id']) {
						$can_fortify = true;
						break 2;
					}
				}
			}
		}

		if ( ! $can_fortify) {
			$this->set_player_state('Waiting', $player_id);

			if ( ! $this->halt_redirect) {
				Flash::store('You can no longer fortify', true);
			}
		}

		return $can_fortify;
	}


	/** protected function _is_connected
	 *		Check to see if the two given territories are
	 *		connected via a path of the player's territories
	 *
	 * @param int $from_land_id
	 * @param int $to_land_id
	 * @param int $player_id optional
	 *
	 * @return bool valid path
	 * @throws MyException
	 */
	protected function _is_connected($from_land_id, $to_land_id, $player_id = 0) {
		call(__METHOD__);

		$from_land_id = (int) $from_land_id;
		$to_land_id = (int) $to_land_id;
		$player_id = (int) $player_id;

		if (empty($player_id)) {
			$player_id = $this->current_player;
		}

		$land = $this->get_players_land($player_id);
		$land = array_keys($land);

		// make sure we control the from and to lands
		if ( ! in_array($from_land_id, $land)) {
			throw new MyException(__METHOD__.': Player (#'.$player_id.') does not control the FROM land (#'.$from_land_id.') ('.self::$TERRITORIES[$from_land_id][NAME].')');
		}

		if ( ! in_array($to_land_id, $land)) {
			throw new MyException(__METHOD__.': Player (#'.$player_id.') does not control the TO land (#'.$to_land_id.') ('.self::$TERRITORIES[$to_land_id][NAME].')');
		}

		// this is a little tricky (and there may be better ways of
		// doing it out there, I just don't know of any)
		// loop through the adjacent territories, add them to the list,
		// remove any that aren't controlled by the player, and if we
		// find the TO land at some point, we have a success
		$used = [];
		$adjacencies = [$from_land_id];
		do {
			$new_adj = [];

			foreach ($adjacencies as $adj) {
				// skip if we've already used this land
				if (in_array($adj, $used)) {
					continue;
				}
				$used[] = $adj;

				// grab the adjacent territories and merge the
				// territories we control into the new list
				$new_adj = array_merge($new_adj, array_intersect($land, self::$TERRITORIES[$adj][ADJACENT]));

				// did we find the TO land
				if (in_array($to_land_id, $new_adj)) {
					return true;
				}
			}

			// merge the new list into the old list, and remove the ones we've used already
			$adjacencies = array_diff(array_unique(array_merge($adjacencies, $new_adj)), $used);
		}
		// if there are none left, we hit a dead end
		while (0 != count($adjacencies));

		return false;
	}


	/** protected function _roll
	 *		Performs the dice rolls and checks them against
	 *		each other to see who lost armies in the attack
	 *		and returns the number of dead for each side
	 *
	 *		NOTE: use the given switch statement to add your own
	 *		dice roll method. there are two built in, but you
	 *		can easily add your own
	 *
	 * @param int $attack_armies number of attacking armies
	 * @param int $defend_armies number of defending armies
	 * @param int $attack_roll optional
	 * @param int $defend_roll optional
	 *
	 * @action rolls dice and performs attack
	 *
	 * @return array (int number of dead attackers, int number of dead defenders)
	 */
	protected function _roll($attack_armies, $defend_armies, $attack_roll = null, $defend_roll = null) {
		call(__METHOD__);

		// here you can switch the dice roll method to be one of:
		// random -  uses random.org to generate truly random dice rolls
		// builtin - uses the built-in php mt_rand function for faster dice rolls
		// or feel free to add your own...

		$roll_method = 'builtin';

		if (is_numeric($attack_roll) && is_numeric($defend_roll)) {
			$roll_method = 'submitted';
		}

		switch ($roll_method) {
			// if you build your own dice roll method
			// add it to the list below
			// (if it's good, let me know about it as well =) )
			// i made it easy, and roll 5 dice every time i roll
			// and then just grab the dice i need below
			// so i would recommend you do the same

			// use random.orgs truly random number generator
			case 'random' :
				$rolls = [];

				$fp_random_org = fopen('http://www.random.org/integers/?num=5&min=1&max=6&col=5&base=8&format=plain&rnd=new', 'r');
				$text_random_org = fread($fp_random_org, 20);
				fclose($fp_random_org);
				$rolls = explode("\t", trim($text_random_org));

				// if this method didn't work, use the default
				if (5 > count($rolls)) {
					$rolls = [];
					for ($i = 0; $i < 5; ++$i) {
						$rolls[] = (int) mt_rand(1, 6);
					}
				}

				array_trim($rolls, 'int');
				break;

			case 'hotbits' :
				$rolls = [];

				// TODO: https://www.fourmilab.ch/cgi-bin/uncgi/Hotbits?nbytes=2048&fmt=bin
				// then convert hex triples to base 8, and disregard any 0s or 7s, using only 1-6

				break;

			case 'submitted' :
				$attack_roll = str_split($attack_roll);
				$defend_roll = str_split($defend_roll);

				// sort them both, highest to lowest
				rsort($attack_roll);
				rsort($defend_roll);
				break;

			// quick and easy built-in pseudo-random method
			// ...many people have complained about 'anomalies'
			// with this method, but it may just be the crazy
			// inner workings of human perception...
			case 'builtin' :
			default :
				$rolls = [];
				for ($i = 0; $i < 5; ++$i) {
					$rolls[] = (int) mt_rand(1, 6);
				}
				break;
		}

		// now pass out random dice rolls to the attacker
		if ('submitted' !== $roll_method) {
			$attack_roll[] = reset($rolls);
			$defend_roll[] = next($rolls);

			if (2 <= $attack_armies) {
				$attack_roll[] = next($rolls);
			}

			if (2 == $defend_armies) {
				$defend_roll[] = next($rolls);
			}

			if (3 == $attack_armies) {
				$attack_roll[] = next($rolls);
			}

			// sort them both, highest to lowest
			rsort($attack_roll);
			rsort($defend_roll);

			Game::log_roll($attack_roll, $defend_roll);
		}

		$this->previous_dice = ['attack' => $attack_roll, 'defend' => $defend_roll];

		// now FIGHT !!
		$attack_dead = 0;
		$defend_dead = 0;
		for ($i = 0; $i < 2; ++$i) { // only two fights, MAX, ever
			if (isset($attack_roll[$i]) && isset($defend_roll[$i])) {
				if ($attack_roll[$i] > $defend_roll[$i]) {
					++$defend_dead;
				}
				else { // tie goes to the defender
					++$attack_dead;
				}
			}
		}

		return [$attack_dead, $defend_dead];
	}


	/** protected function _test_board
	 *		Tests the given board for validity
	 *		making sure all territories are accounted for
	 *		and no armies are less than 1
	 *
	 * @param array $board
	 *
	 * @return void
	 * @throws MyException
	 */
	protected function _test_board($board) {
		call(__METHOD__);

		$lands = [];

		if ( ! is_array($board)) {
			throw new MyException(__METHOD__.': No board data given');
		}

		foreach ($board as $land_id => $land) {
			if (0 == $land['player_id']) {
				throw new MyException(__METHOD__.': Uncontrolled territory #'.$land_id.' ('.self::$TERRITORIES[$land_id][NAME].') found');
			}

			// only throw this error if the current player is not occupying
			// they could have killed the armies in there, in which case, the
			// number of armies is 0
			if ((0 >= $land['armies']) && (0 != $this->current_player) && ('Occupying' != $this->players[$this->current_player]['state'])) {
				throw new MyException(__METHOD__.': Not enough armies ('.$land['armies'].') found for player #'.$land['player_id'].' in territory #'.$land_id.' ('.self::$TERRITORIES[$land_id][NAME].')', 102);
			}

			if (in_array($land_id, $lands)) {
				throw new MyException(__METHOD__.': Duplicate territory found: #'.$land_id.' ('.self::$TERRITORIES[$land_id][NAME].')', 103);
			}

			$lands[] = $land_id;
		}

		// test for missing territories
		$territory_ids = array_keys(self::$TERRITORIES);
		$missing = array_diff($territory_ids, $lands);

		if (0 != count($missing)) {
			throw new MyException(__METHOD__.': Board data missing the following territories: '.implode(', ', $missing));
		}
	}


	/** public function calculate_armies
	 *		Returns the number of armies the given player
	 *		has to place at the start of their next turn
	 *
	 * @param int $player_id
	 *
	 * @return array [avail armies, land, continents]
	 * @throws MyException
	 */
	public function calculate_armies($player_id) {
		call(__METHOD__);

		$player_id = (int) $player_id;

		if (empty($player_id)) {
			throw new MyException(__METHOD__.': Missing required arguments');
		}

		$land = $this->get_players_land($player_id);

		if (empty($land)) {
			return false;
		}

		$armies = floor(count($land) / 3);

		$armies = (3 > $armies) ? 3 : $armies;

		$continents = $this->get_players_continents($player_id);

		foreach ($continents as $cont_id => $cont) {
			$armies += $cont[BONUS];
		}

		return $armies;
	}


	/** protected function _add_armies
	 *		Adds the number of armies the given player
	 *		has to place at the start of their next turn
	 *
	 * @param int $player_id
	 *
	 * @return void
	 * @throws MyException
	 */
	protected function _add_armies($player_id) {
		call(__METHOD__);

		$player_id = (int) $player_id;

		if (empty($player_id)) {
			throw new MyException(__METHOD__.': Missing required arguments');
		}

		$armies = $this->calculate_armies($player_id);
		$land = $this->get_players_land($player_id);
		$cont_ids = array_keys($this->get_players_continents($player_id));

		$this->players[$player_id]['armies'] += $armies;

		Game::log($this->_game_id, 'R '.$player_id.':'.$armies.':'.count($land).(count($cont_ids) ? ':'.implode(',', $cont_ids) : ''));
	}


	/** protected function _update_trade_value
	 *		Updates the number of armies
	 *		available for the next turn in
	 *
	 * @param bool $log optional log the next value
	 *
	 * @return void
	 * @throws MyException
	 */
	protected function _update_trade_value($log = true) {
		call(__METHOD__);
		call($log);

		if ( ! $this->_trade_values) {
			throw new MyException(__METHOD__.': Missing trade values');
		}

		$value = $prev_value = $this->_next_trade;
		$trades = $this->_trade_values;
		$count = count($trades);
		call($trades);

		// grab the key we need
		call($this->_extra_info['trade_number']);
		$key = $this->_extra_info['trade_number'];
		call($key);

		// test our key and if found, use that
		// else, calculate by extrapolating from our current value
		if (isset($trades[$key]) && ! in_array(((string) $trades[$key])[0], ['+', '-'])) {
			$value = $trades[$key];
		}
		elseif (in_array($trades[$count - 1][0], ['+', '-'])) {
			// grab the second to last value
			$value = $trades[$count - 2];
			$increment = $trades[$count - 1];

			for ($i = ($count - 2); $i < $key; ++$i) {
				$value += $increment;
			}

			// make sure we didn't go below 0
			if (0 > $value) {
				$value = 0;
			}
		}
		else {
			// the trade value is no longer changing
			$value = end($trades);
		}
		call($value);

		$this->_next_trade = (int) $value;

		if ($log) {
			Game::log($this->_game_id, 'V '.$this->_next_trade);
		}
	}


	/** protected function _award_card
	 *		if the player gets a card this round...
	 *		give them one
	 *
	 * @param int $card_id optional
	 * @action tests and updates player data
	 *
	 * @return void
	 * @throws MyException
	 */
	protected function _award_card($card_id = null) {
		call(__METHOD__);

		$player_id = $this->current_player;

		// make sure this player gets a card
		if ( ! $player_id || ! $this->players[$player_id]['extra_info']['get_card']) {
			// no exception, just quit
			return false;
		}

		if ( ! $card_id) {
			// don't give a card in a controlled game
			// we'll manually award the card later
			if ($this->_controlled) {
				return;
			}

			// remove a random card from the deck
			shuffle($this->_available_cards);
			$card_index = array_rand($this->_available_cards);

			$card_id = $this->_available_cards[$card_index];
		}
		else {
			$card_index = array_search($card_id, $this->_available_cards);

			if (false === $card_index) {
				throw new MyException(__METHOD__.': Given card ('.$card_id.') is not available to give');
			}
		}

		$card_id = (int) $card_id;

		unset($this->_available_cards[$card_index]);

		// and give it to the player
		$this->players[$player_id]['cards'][] = $card_id;
		$this->players[$player_id]['extra_info']['get_card'] = false;

		Game::log($this->_game_id, 'C '.$player_id.':'.$card_id);
	}


	/**
	 * Give the given player the given card
	 *
	 * @note This method for reviews only, do not use this method in normal gameplay
	 *
	 * @param $player_id
	 * @param $card_id
	 *
	 * @return void
	 * @throws MyException
	 */
	public function give_card($player_id, $card_id) {
		$orig_current_player = $this->current_player;

		$this->current_player = (int) $player_id;
		try {
			$this->_award_card($card_id);
		}
		catch (MyException $e) {
			throw $e;
		}

		$this->current_player = $orig_current_player;
	}


	/**
	 * Orders the player array in turn order
	 *
	 * @param void
	 *
	 * @return void
	 */
	public function order_players( ) {
		$players = $this->players;

		$order = [];
		foreach ($players as $player) {
			$order[$player['order_num']] = $player['player_id'];
		}

		ksort($order);

		$this->players = [];
		foreach ($order as $player_id) {
			$this->players[$player_id] = $players[$player_id];
		}
	}


	/** protected function _next_player
	 *		finds out who the next player is, and
	 *		gets them ready to go
	 *
	 * @param void
	 *
	 * @action tests and updates player data
	 *
	 * @return void
	 * @throws MyException
	 */
	protected function _next_player( ) {
		call(__METHOD__);

		// kill the pesky infinite loop
		#ini_set('max_execution_time', '3');
		$cur_player = $this->current_player;

		if (0 == $cur_player) {
			throw new MyException(__METHOD__.': Current player not set');
		}

		$cur_order = $this->players[$cur_player]['order_num'];
		$next_order = (1 + $cur_order);

		// this bit gets a little confusing...
		// do the following until we loop back to the original order number
		// then something is broken...
		do {
			// if the next order number is greater than
			// the number of players we have, reset next order
			if (count($this->players) < $next_order) {
				$next_order = 1;
			}

			// run through each player and test their order number against
			// our current order number, if the player is found, but dead,
			// increment the order number and run again, if the player is found
			// and NOT dead, set them as the current player, and break out of the loop
			foreach ($this->players as $player) {
				if ($player['order_num'] == $next_order) {
					if (in_array($player['state'], ['Resigned', 'Dead'])) {
						++$next_order;
						break; // just start the foreach over
					}
					else {
						$this->current_player = $player['player_id'];
						break 2; // break out of all, we found them
					}
				}
			}
		}
		while ($next_order != $cur_order);

		// make sure we didn't grab the same player
		if ($cur_player == $this->current_player) {
			throw new MyException(__METHOD__.': Next player not found');
		}

		$prev_player = $cur_player;

		Game::log($this->_game_id, 'N '.$this->current_player);
		$this->_add_armies($this->current_player);

		// place the next player into an appropriate state based
		// on the number of cards they are holding, and if there
		// is a match in those cards
		if ($this->_player_can_trade($this->current_player)) {
			$this->set_player_state('Trading');
		}
		else {
			$this->set_player_state('Placing');
		}
	}


	/**
	 * @param $player_id
	 *
	 * @return int next player id
	 * @throws MyException
	 */
	public function set_next_player($player_id) {
		if ((int) $player_id === (int) $this->current_player) {
			return $player_id;
		}

		$orig_current_player = $this->current_player;

		do {
			$cur_state = $this->players[$this->current_player]['state'];
			$this->set_player_next_state($cur_state, $this->current_player);
		}
		while ($orig_current_player === $this->current_player);

		if ((int) $player_id !== (int) $this->current_player) {
			throw new MyException(__METHOD__.': Next player was not the correct next player');
		}

		return $this->current_player;
	}


	/** protected function _player_can_trade
	 *		finds out if the given player can make a trade
	 *
	 * @param int $player_id
	 *
	 * @return bool player can trade
	 */
	protected function _player_can_trade($player_id) {
		call(__METHOD__);

		// if the player doesn't have enough cards, they can't trade
		$cards = $this->players[$player_id]['cards'];

		$count = count($cards);

		// force a trade with 5 or more cards
		if (5 <= $count) {
			$this->players[$player_id]['extra_info']['forced'] = true;
			return true;
		}

		try {
			$can_trade = $this->_test_card_set($cards);
		}
		catch (MyException $e) {
			return false;
		}

		return $can_trade;
	}


	/** protected function _test_card_set
	 *		Tests the given cards for a valid set
	 *
	 * @param array $cards card ids
	 *
	 * @return bool has valid set
	 * @throws MyException
	 */
	protected function _test_card_set($cards) {
		call(__METHOD__);
		call($cards);

		$count = count($cards);
		$cards = array_values($cards);
		call($count);

		// if they don't have 3 cards, they can't trade
		if (3 > $count) {
			return false;
		}

		// if they have 5 cards, they have a set, no question
		if (5 <= $count) {
			return true;
		}

		// if we're testing more than one set
		// don't throw an exception on the first failure
		$single = (3 == $count);

		// build all possible sets
		$sets = [];
		for ($i = 0; $i < ($count - 2); ++$i) {
			for ($j = ($i + 1); $j < ($count - 1); ++$j) {
				for ($k = ($j + 1); $k < $count; ++$k) {
					$sets[] = [$i, $j, $k];
				}
			}
		}
		call($sets);

		// test the sets and see if any are tradeable
		foreach ($sets as $set) {
			call( );
			call($set);

			$card_types = [];
			// make sure the cards are a set
			foreach ($set as $index) {
				$card_types[] = self::$CARDS[$cards[$index]][CARD_TYPE];
			}
			call($card_types);

			$total = array_sum($card_types);
			call($total);

			// it's better than a bazillion if statements...
			// or one incredibly long if statement...
			switch ((int) $total) {
				// -- VALID TURN INS --

				// matched
				case (2 * INFANTRY) + WILD : // 2
				case (3 * INFANTRY) :        // 3

				case (2 * CAVALRY) + WILD : // 20
				case (3 * CAVALRY) :        // 30

				case (2 * ARTILLERY) + WILD : // 200
				case (3 * ARTILLERY) :        // 300

				// mixed
				case INFANTRY +   CAVALRY + WILD :      //  11
				case INFANTRY + ARTILLERY + WILD :      // 101
				case  CAVALRY + ARTILLERY + WILD :      // 110
				case INFANTRY +   CAVALRY + ARTILLERY : // 111
					return true;
					break;


				// -- INVALID TURN INS --

				// something weird happened...
				// also could be 3 wilds, if there were 3 wilds in the deck...
				// which there aren't
				case 0 :
					if ($single) {
						throw new MyException(__METHOD__.': Unknown occurrence with card types');
					}
					break;

				// too many wilds
				case  INFANTRY + (2 * WILD) : //   1
				case   CAVALRY + (2 * WILD) : //  10
				case ARTILLERY + (2 * WILD) : // 100
					if ($single) {
						throw new MyException(__METHOD__.': Only one wild card is allowed per trade in');
					}
					break;

				// all others are invalid
				default :
					if ($single) {
						throw new MyException(__METHOD__.': Non-matching set of cards');
					}
					break;
			}
		}

		return false;
	}



	/**
	 *		STATIC METHODS
	 * * * * * * * * * * * * * * * * * * * * * * * * * * */

	/** static public function check_adjacencies
	 *		Checks the territory adjacencies for validity
	 *		in case they were changed by somebody else
	 *
	 * @param void
	 *
	 * @return bool success
	 * @throws MyException
	 */
	static public function check_adjacencies( ) {
		foreach (self::$TERRITORIES as $id => $territory) {
			foreach ($territory[ADJACENT] as $adj) {
				if ( ! in_array($id, self::$TERRITORIES[$adj][ADJACENT])) {
					throw new MyException(__METHOD__.': Territory Adjacency Check failed on territory #'.$adj.' ('.self::$TERRITORIES[$adj][NAME].'): #'.$id.' ('.self::$TERRITORIES[$id][NAME].') not found', 101);
				}
			}
		}
	}

} // end of Risk class


/**
 * Return a human readable version of the card type
 *
 * @param int $input card type id
 *
 * @return string card name or false on failure
 */
function card_type($input) {
	switch ($input) {
		case WILD :
			return 'Wild';
			break;

		case INFANTRY :
			return 'Infantry';
			break;

		case CAVALRY :
			return 'Cavalry';
			break;

		case ARTILLERY :
			return 'Artillery';
			break;

		default :
			return false;
			break;
	}
}


/**
 * Get an abbreviated version of the territory name
 *
 * @param string $name normal territory name
 *
 * @return string short territory name
 */
function shorten_territory_name($name) {
	$short_names = [
			// North America
//		'Alaska' => 'Alaska' ,
//		'Alberta' => 'Alberta' ,
		'Central America' => 'Cent. America' ,
		'Eastern United States' => 'Eastern U.S.' ,
//		'Greenland' => 'Greenland' ,
		'Northwest Territory' => 'N.W. Territory' ,
//		'Ontario' => 'Ontario' ,
//		'Quebec' => 'Quebec' ,
		'Western United States' => 'Western U.S.' ,

			// South America
//		'Argentina' => 'Argentina' ,
//		'Brazil' => 'Brazil' ,
//		'Peru' => 'Peru' ,
//		'Venezuela' => 'Venezuela' ,

			// Europe
//		'Great Britain' => 'Great Britain' ,
//		'Iceland' => 'Iceland' ,
		'Northern Europe' => 'N. Europe' ,
//		'Scandinavia' => 'Scandinavia' ,
		'Southern Europe' => 'S. Europe' ,
//		'Ukraine' => 'Ukraine' ,
		'Western Europe' => 'W. Europe' ,

			// Africa
//		'Congo' => 'Congo' ,
		'East Africa' => 'E. Africa' ,
//		'Egypt' => 'Egypt' ,
//		'Madagascar' => 'Madagascar' ,
		'North Africa' => 'N. Africa' ,
		'South Africa' => 'S. Africa' ,

			// Asia
//		'Afghanistan' => 'Afghanistan' ,
//		'China' => 'China' ,
//		'India' => 'India' ,
//		'Irkutsk' => 'Irkutsk' ,
//		'Japan' => 'Japan' ,
//		'Kamchatka' => 'Kamchatka' ,
		'Middle East' => 'Mid. East' ,
//		'Mongolia' => 'Mongolia' ,
//		'Siam' => 'Siam' ,
//		'Siberia' => 'Siberia' ,
//		'Ural' => 'Ural' ,
//		'Yakutsk' => 'Yakutsk' ,

			// Australia
		'Eastern Australia' => 'E. Australia' ,
//		'Indonesia' => 'Indonesia' ,
//		'New Guinea' => 'New Guinea' ,
		'Western Australia' => 'W. Australia' ,
	];

	if (array_key_exists($name, $short_names)) {
		return $short_names[$name];
	}

	return $name;
}

