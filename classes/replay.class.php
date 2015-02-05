<?php
/*
+---------------------------------------------------------------------------
|
|   replay.class.php (php 5.x)
|
|   by Benjam Welker
|   http://iohelix.net
|
+---------------------------------------------------------------------------
|
|	This module is built to replay the game Risk, it doesn't really
|	care about how to play, or the deep goings on of the game, only about
|	dat file structure and how to allow players to interact with the game.
|
+---------------------------------------------------------------------------
|
|   > WebRisk Game Replay module
|   > Date started: 2015-02-02
|
|   > Module Version Number: 0.8.0
|
+---------------------------------------------------------------------------
*/

// TODO: comments & organize better

// define some file array indexes
define('FILE_GAME_INFO', 0);
define('FILE_EXTRA_INFO', 1);
define('FILE_PLAYER_INFO', 2);
define('FILE_GAME_LOG', 3);
define('FILE_LOG_KEY', 4);

// define some faux table indexes
define('TABLE_GAME', 0);
define('TABLE_PLAYERS', 1);
define('TABLE_LAND', 2);

require_once INCLUDE_DIR.'func.array.php';

class Replay extends Game {

	/**
	 *		PROPERTIES
	 * * * * * * * * * * * * * * * * * * * * * * * * * * */


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


	/** public property step
	 *		Holds the game's current step
	 *
	 * @var int
	 */
	public $step;


	/** public property type
	 *		Holds the game type (Original)
	 *
	 * @var string
	 */
	public $type;


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


	/** protected property _filename
	 *		Holds the name of the save game file
	 *
	 * @var string
	 */
	protected $_filename;


	/** protected property _file
	 *		Holds the contents of the save game file
	 *		parsed out into sections
	 *
	 * @var array
	 */
	protected $_file;


	/** protected property $_faux_db
	 *		Fake DB array to hold the game data
	 * @var array
	 */
	protected $_faux_db;


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
	 * @param string $file the name of the file to open
	 * @param int $step current move step
	 *
	 * @action instantiates object
	 *
	 * @return Replay Object reference
	 * @throws MyException
	 */
	public function __construct($file, $step = 0) {
		call(__METHOD__);

		ksort(self::$_PLAYER_EXTRA_INFO_DEFAULTS);

		$this->_filename = $file;
		$this->step = (int) $step;

		$this->_risk = new Risk( );
		$this->_risk->set_do_log(false);

		try {
			$id = $this->_read( );
		}
		catch (MyException $e) {
			throw $e;
		}

		$this->id = (int) $id;
		call($this->id);

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
	 * @param string $property name
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


	/** public function trade_cards
	 *		Trades cards for more armies
	 *
	 * @param int player id
	 * @param array card ids to trade
	 * @param int bonus land id
	 * @action saves the game
	 * @return void
	 */
	public function trade_cards($player_id, $card_ids, $bonus_card = null)
	{
		call(__METHOD__);

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
	 * @param int player id
	 * @return string card values
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
	 * @return bool success
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

		if (empty($player_id) || empty($num_armies) || empty($land_id)) {
			throw new MyException(__METHOD__.': Missing required argument');
		}

		try {
			$placed_armies = $this->_risk->place_armies($player_id, $num_armies, $land_id, 'Placing' == $this->state);
		}
		catch (MyException $e) {
			throw $e;
		}

		try {
			$this->_test_armies($player_id);
		}
		catch (MyException $e) {
			throw $e;
		}
	}


	/** public function attack
	 *		ATTACK !!!
	 *
	 * @param int player id
	 * @param int number of armies to attack with
	 * @param int attack from land id
	 * @param int attack to (defend) land id
	 * @param int attack roll
	 * @param int defend roll
	 *
	 * @action saves the game
	 *
	 * @return bool defeated
	 * @throws MyException
	 */
	public function attack($player_id, $num_armies, $attack_land_id, $defend_land_id)
	{
		call(__METHOD__);

		$args = func_get_args( );
		$attack_roll = $args[4];
		$defend_roll = $args[5];

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
			list($defend_id, $defeated) = $this->_risk->attack($num_armies, $attack_land_id, $defend_land_id, $attack_roll, $defend_roll);
		}
		catch (MyException $e) {
			throw $e;
		}

		// check to see if we killed our opponent
		if (in_array($this->_risk->players[$defend_id]['state'], array('Resigned', 'Dead'))) {
			$this->_test_winner( );
		}

		return $defeated;
	}


	/** public function occupy
	 *		Occupies a recently defeated territory with the
	 *		given number of armies
	 *
	 * @param int player id
	 * @param int number of armies to move
	 * @action saves the game
	 * @return void
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
			$to_land_id = $this->_risk->occupy($num_armies);
		}
		catch (MyException $e) {
			throw $e;
		}
	}


	/** public function fortify
	 *		Occupies a recently defeated territory with the
	 *		given number of armies
	 *
	 * @param int player id
	 * @param int number of armies to move
	 * @param int from land id
	 * @param int to land id
	 * @action saves the game
	 * @return void
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
			$moved_armies = $this->_risk->fortify($num_armies, $from_land_id, $to_land_id);
		}
		catch (MyException $e) {
			throw $e;
		}
	}


	/** public function resign
	 *		Resigns from the game
	 *
	 * @param int player id
	 * @param bool false
	 *
	 * @return void
	 *
	 * @throws MyException
	 */
	public function resign($player_id, $skip_pause = false)
	{
		call(__METHOD__);

		$player_id = (int) $player_id;

		if (empty($player_id)) {
			throw new MyException(__METHOD__.': Missing required argument');
		}

		// resign the player and add a loss
		try {
			$this->_risk->set_player_state('Resigned', $player_id);
		}
		catch (MyException $e) {
			throw $e;
		}

		$this->_test_winner( );
	}


	/** public function skip
	 *		Skip the given player to the next state
	 *
	 * @param string players current state
	 * @param int player id
	 * @return void
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


	/** public function get_visible_board
	 *		Filters the board data based on what
	 *		the current player can see
	 *
	 * @param int optional observer id
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

		foreach ($board as $land_id => & $data) { // mind the reference
			$data['color'] = $this->_players[$data['player_id']]['color'];
			$data['resigned'] = ('Resigned' == $this->_risk->players[$data['player_id']]['state']) ? ' res' : '';
		}
		unset($data); // kill the reference

		return $board;
	}


	/** public function get_players_visible_continents
	 *		Filters the continent data based on what
	 *		the current player can see
	 *
	 * @param int optional observer id
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
			$players[$player_id]['username'] = $this->_players[$player_id]['name'];
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
		if ($this->watch_mode) {
			return '<div id="action">You are watching</div>';
		}

		// TODO: modify this to show what happened during the current turn

		return '';
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
			$players[$player_id]['username'] = $this->_players[$player_id]['name'];
			$players[$player_id]['num_cards'] = count($player['cards']);
			$players[$player_id]['state'] = $player['state'];
			$players[$player_id]['order'] = $player['order_num'];
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

			$visible_land[$land['player_id']]['armies'] += $land['armies'];
			$visible_land[$land['player_id']]['land']++;
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
				'username' => $player['name'],
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

			// visible armies
			$player['armies'] = $visible_land[$player_id]['armies'];

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
	 * @param int player id
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
	 * @param int player id
	 * @return int available armies
	 */
	public function get_player_armies($player_id)
	{
		return (int) $this->_risk->players[(int) $player_id]['armies'];
	}


	/** public function get_land_armies
	 *		Grabs the number of armies on the given territory
	 *
	 * @param int land id
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
	 * @param int player id
	 * @param int optional observer id
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
	 * @return string placement method
	 */
	public function get_placement( )
	{
		return self::_get_placement($this->_extra_info);
	}


	/** static protected function _get_placement
	 *		Grabs the placement method
	 *
	 * @param array extra info
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
	 * @return int initial placement limit
	 */
	public function get_placement_limit( )
	{
		return self::_get_placement_limit($this->_extra_info);
	}


	/** static protected function _get_placement_limit
	 *		Grabs the initial placement limit
	 *
	 * @param array extra info
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
	 * @return string fortification method
	 */
	public function get_fortify( )
	{
		return self::_get_fortify($this->_extra_info);
	}


	/** static protected function _get_fortify
	 *		Grabs the fortification method from the risk class and formats
	 *
	 * @param array extra info
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
	}


	/** public function get_kamikaze
	 *		Grabs the kamikaze method
	 *
	 * @param void
	 * @return string kamikaze method
	 */
	public function get_kamikaze( )
	{
		return self::_get_kamikaze($this->_extra_info);
	}


	/** static protected function _get_kamikaze
	 *		Grabs the kamikaze method
	 *
	 * @param array extra info
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
	 * @return string warmonger method
	 */
	public function get_warmonger( )
	{
		return self::_get_warmonger($this->_extra_info);
	}


	/** static protected function _get_warmonger
	 *		Grabs the warmonger method
	 *
	 * @param array extra info
	 * @return string warmonger method
	 */
	static protected function _get_warmonger($data)
	{
		return ($data['warmonger'] ? 'Yes' : 'No');
	}


	/** public function get_fog_of_war
	 *		Grabs the fog of war method
	 *
	 * @param void
	 * @return string fog of war method
	 */
	public function get_fog_of_war( )
	{
		return self::_get_fog_of_war($this->_extra_info);
	}


	/** static protected function _get_fog_of_war
	 *		Grabs the fog of war method
	 *
	 * @param array extra info
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
	 * @return string conquer limit method
	 */
	public function get_conquer_limit( )
	{
		return self::_get_conquer_limit($this->_extra_info);
	}


	/** static protected function _get_conquer_limit
	 *		Grabs the conquer limit method
	 *
	 * @param array extra info
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
	 * @return int trade count
	 */
	static protected function _get_trade_count($data)
	{
		return (int) $data['trade_number'];
	}


	/** protected function _read
	 *		Reads all game data from the save file
	 *
	 * @param void
	 *
	 * @action reads the game data
	 *
	 * @return int game id
	 * @throws MyException
	 */
	protected function _read( )
	{
		call(__METHOD__);

		$this->_file = file_get_contents(GAMES_DIR.'/'.$this->_filename.'.dat');
		$this->_file = preg_split('%\R=================================\R%', $this->_file);

		$this->_file[FILE_GAME_LOG] = preg_split('%\R%', $this->_file[FILE_GAME_LOG]);

		$info = preg_split('%\R%', $this->_file[FILE_GAME_INFO]);
		list($id, $name, $type) = explode(' - ', $info[0]);

		$this->name = $name;
		$this->type = $type;
		$this->state = 'Placing';
		$this->capacity = count(preg_split('%\R%', $this->_file[FILE_PLAYER_INFO]));
		$this->create_date = strtotime($info[1]);
		$this->modify_date = $this->create_date;
		$this->paused = false;
		$this->passhash = '';

// temp fix for old serialized data
fix_extra_info($this->_file[FILE_EXTRA_INFO]);
		$this->_extra_info = array_merge_plus(self::$_EXTRA_INFO_DEFAULTS, json_decode($this->_file[FILE_EXTRA_INFO], true));

		// reset some things that shouldn't be set by the file
		$this->_extra_info['trade_number'] = 0;

		// pull the player data
		try {
			$this->_read_players( );
			$this->_write_faux_db( );
			$this->_update_risk( );
		}
		catch (MyException $e) {
			throw $e;
		}

		return $id;
	}


	protected function _write_faux_db( ) {
		$this->_faux_db[TABLE_GAME] = array(
			'game_id' => $this->id,
			'name' => $this->name,
			'capacity' => $this->capacity,
			'game_type' => $this->type,
			'next_bonus' => 4,
			'state' => $this->state,
			'extra_info' => $this->_extra_info,
		);

		$this->_faux_db[TABLE_PLAYERS] = $this->_players;

		$land = array( );
		foreach (Risk::$TERRITORIES as $id => $null) {

		}
		$this->_faux_db[TABLE_LAND] = $land;

	}


	/** protected function _read_players
	 *		Reads all player data from the file
	 *
	 * @param void
	 *
	 * @action reads the player data
	 *
	 * @return void
	 */
	protected function _read_players( )
	{
		call(__METHOD__);

		$players = explode("\n", $this->_file[FILE_PLAYER_INFO]);

		$last_move = 0;

		$n = 1;
		foreach ($players as $player) {
			$player = explode(' - ', $player);
			$player = array_combine(array('player_id', 'color', 'name'), $player);
			$player['order_num'] = $n;

			$this->_set_player_data($player);

			++$n;
		}

		$this->last_move = strtotime($last_move);
	}


	/** protected function _set_player_data
	 *		Adds a player to the game and risk data
	 *
	 * @param array $data player data
	 *
	 * @return void
	 * @throws MyException
	 */
	protected function _set_player_data($data)
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

		// move any data we need to over to the risk class player data
		$risk_player = $player;

		$player_keys = array(
			'player_id',
			'color',
			'name',
		);

		$player = array_clean($player, $player_keys);

		$risk_player['armies'] = $this->_risk->get_start_armies( );
		$risk_player['state'] = 'Placing';

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
	 * @param void
	 * @action updates the Risk object
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

		$this->_risk->new_player = false;

		$this->_risk->set_game_type($this->type);

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

		$board = array( );
		foreach ($this->_faux_db[TABLE_LAND] as $land) {
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
	 *		Replaces Game::_save
	 *
	 * @param void
	 *
	 * @action nothing
	 *
	 * @return void
	 */
	protected function _save( )
	{
		call(__METHOD__);
		return;
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


	/** protected function _test_armies
	 *		Tests the number of armies available to place for the given player
	 *		and sets the player to the next state based on game state
	 *
	 * @param int player id
	 * @action optionally sets the next state
	 * @action optionally saves the game
	 * @return void
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
	 * @action optionally sets the next state
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
	 * @action optionally sets the winner
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
		}
	}


	/** protected function _begin
	 *		Begins the game proper
	 *
	 * @param void
	 * @action begins the game
	 * @action saves the game
	 * @return void
	 */
	protected function _begin( )
	{
		call(__METHOD__);

		// make sure the game is 'Playing'
		$this->state = 'Playing';

		$player_id = $this->_risk->begin( );
	}


	/**
	 * Plays the game to the given step
	 *
	 * @param $step
	 *
	 * @return void
	 * @throws MyException
	 */
	public function play_to($step) {
		for ($i = 0; $i < $step; ++$i) {
			$this->do_action($this->_file[FILE_GAME_LOG][$i]);
		}
	}


	protected function do_action($action) {
		list($type, $action) = explode(' ', $action);
		$action = explode(':', $action);

		// TODO: need to test the current state for the current player
		// and skip if they are in the wrong state until they get to the right state

		switch (strtoupper($type)) {
			case 'A' : // Attack
				$rolls = explode(',', $action[4]);

				$player_id = $action[0];
				$attack_land_id = $action[1];
				$defend_land_id = $action[3];
				$attack_roll = $rolls[0];
				$defend_roll = $rolls[1];
				$num_armies = str_len($attack_roll);

				$this->_risk->set_player_state('Attacking', $player_id);
				$this->attack($player_id, $num_armies, $attack_land_id, $defend_land_id, $attack_roll, $defend_roll);
				break;

			case 'C' : // Card
				$this->_risk->give_card($action[0], $action[1]);
				break;

			case 'D' : // Done (game over)
				// do nothing
				break;

			case 'E' : // Eradicated (killed)
				// do nothing
				break;

			case 'F' : // Fortify
				$player_id = $action[0];
				$num_armies = $action[1];
				$from_land_id = $action[2];
				$to_land_id = $action[3];

				$this->_risk->set_player_state('Fortifying', $player_id);
				$this->fortify($player_id, $num_armies, $from_land_id, $to_land_id);
				break;

			case 'I' : // Initialization
				$board = $action[0];

				$this->_risk->init_board($board);
				break;

			case 'N' : // Next player
				$player_id = $action[0];

				$this->_risk->set_next_player($player_id);
				break;

			case 'O' : // Occupy
				$player_id = $action[0];
				$num_armies = $action[1];

				$this->_risk->set_player_state('Occupying', $player_id);
				$this->occupy($player_id, $num_armies);
				break;

			case 'P' : // Placing
				$player_id = $action[0];
				$num_armies = $action[1];
				$land_id = $action[2];

				$this->_risk->set_player_state('Placing', $player_id);
				$this->place_armies($player_id, $num_armies, $land_id);
				break;

			case 'Q' : // Quit (resign)
				$player_id = $action[0];

				$this->resign($player_id);
				break;

			case 'R' : // Reinforcements
				break;

			case 'T' : // Trade
				break;

			case 'V' : // Value
				break;

		}
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
	 * @param array of input arrays
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

} // end of Replay class
