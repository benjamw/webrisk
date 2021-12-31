<?php
/*
+---------------------------------------------------------------------------
|
|   review.class.php (php 5.x)
|
|   by Benjam Welker
|   http://iohelix.net
|
+---------------------------------------------------------------------------
|
|	This module is built to review the game Risk, it doesn't really
|	care about how to play, or the deep goings on of the game, only about
|	dat file structure and how to allow players to interact with the game.
|
+---------------------------------------------------------------------------
|
|   > WebRisk Game Review module
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

class Review extends Game {

	/**
	 *		PROPERTIES
	 * * * * * * * * * * * * * * * * * * * * * * * * * * */


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
	 * @return Review Object reference
	 * @throws MyException
	 */
	public function __construct($file, $step = 0) {
		call(__METHOD__);

		ksort(self::$_PLAYER_EXTRA_INFO_DEFAULTS);

		$this->_filename = $file;

		$this->_risk = new Risk( );
		$this->_risk->is_controlled(true);
		$this->_risk->halt_redirect = true;
		$this->_do_log = false;

		if (defined('DEBUG')) {
			$this->_DEBUG = DEBUG;
		}

		try {
			$id = $this->_read( );
		}
		catch (MyException $e) {
			throw $e;
		}

		$this->id = (int) $id;
		call($this->id);

		$this->play_to((int) $step);
	}


	/**
	 * Class destructor, overrides the destructor in Game class
	 *
	 * @param void
	 *
	 * @return void
	 */
	public function __destruct( ) {
		// do nothing
	}


	/** public function attack
	 *		ATTACK !!!
	 *
	 * @param int $player_id
	 * @param int $num_armies to attack with
	 * @param int $attack_land_id
	 * @param int $defend_land_id
	 * @false_param int $attack_roll
	 * @false_param int $defend_roll
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
		if (in_array($this->_risk->players[$defend_id]['state'], ['Resigned', 'Dead'])) {
			$this->_test_winner( );
		}

		return $defeated;
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
		}
		catch (MyException $e) {
			throw $e;
		}

		$this->_test_winner( );
	}


	/** public function get_visible_board
	 *		Filters the board data based on what
	 *		the current player can see
	 *
	 * @param null $observer_id unused
	 *
	 * @return array board data
	 */
	public function get_visible_board($observer_id = null)
	{
		call(__METHOD__);

		$board = $this->_risk->board;

		foreach ($board as $land_id => & $data) { // mind the reference
			$data['color'] = $this->_players[$data['player_id']]['color'];
			$data['resigned'] = ('Resigned' == $this->_risk->players[$data['player_id']]['state']) ? ' res' : '';
		}
		unset($data); // kill the reference

		return $board;
	}


	/**
	 * Get the list of steps for this review
	 *
	 * @param bool $parsed optional
	 * @param int|bool $limit optional the index of the last step to pull or false for all
	 *
	 * @return array
	 */
	public function get_steps($parsed = false, $limit = false) {
		$game_log = $this->_file[FILE_GAME_LOG];

		if (false !== $limit) {
			$game_log = array_slice($game_log, 0, $limit + 1);
		}

		if ( ! $parsed) {
			return $game_log;
		}

		$game_log = array_reverse($game_log);
		$trade_bonus = $this->_extra_info['trade_card_bonus'];

		$logs = [];
		foreach ($game_log as $data) {
			$log = [
				'data' => $data,
				'message' => self::parse_move_info($data, $trade_bonus, $game_id = 0, $logs),
			];

			$logs[] = $log;
		}

		return $logs;
	}


	/**
	 * Get the given step string
	 *
	 * @param int|bool $step optional step index
	 *
	 * @return string step code
	 */
	public function get_step($step = false) {
		if (false === $step) {
			$step = $this->step;
		}

		return $this->_file[FILE_GAME_LOG][$step];
	}


	/**
	 * Get the total number of steps for this review
	 *
	 * @param void
	 *
	 * @return int
	 */
	public function get_steps_count( ) {
		return count($this->_file[FILE_GAME_LOG]);
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
			return [];
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
		$players = [];
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
		$temp_players = [];
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

		if ( ! empty($this->_host_id)) {
			$players[$this->_host_id]['host'] = true;
		}

		return $players;
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

		$player_id = (int) $player_id;
		$num_armies = (int) $num_armies;
		$land_id = (int) $land_id;

		if (0 === $num_armies) {
			return;
		}

		if (empty($player_id) || empty($num_armies) || empty($land_id)) {
			throw new MyException(__METHOD__.': Missing required argument');
		}

		// just do this raw, because there may have been some DB corruption
		$this->_risk->players[$player_id]['armies'] -= $num_armies;
		$this->_risk->board[$land_id]['armies'] += $num_armies;
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
		$visible_land = [];
		foreach ($visible_board as $land) {
			if ( ! is_array($land)) {
				continue;
			}

			if ( ! isset($visible_land[$land['player_id']])) {
				$visible_land[$land['player_id']] = [
					'player_id' => $land['player_id'],
					'resigned' => $land['resigned'],
					'land' => 0,
					'armies' => 0,
				];
			}

			$visible_land[$land['player_id']]['armies'] += $land['armies'];
			$visible_land[$land['player_id']]['land']++;
		}

		$trade_value = $this->_risk->get_trade_value( );

		$temp_players = [];
		$order = [];
		foreach ($this->_players as $id => $player) {
			// make sure we have a board entry for everybody
			if ( ! isset($visible_land[$id])) {
				$visible_land[$id] = [
					'player_id' => $id,
					'resigned' => '',
					'land' => '--',
					'armies' => '--',
				];
			}

			$temp_players[$id] = [
				'player_id' => $player['player_id'],
				'username' => $player['name'],
				'color' => $player['color'],
				'order' => $this->_risk->players[$id]['order_num'],
				'state' => $this->_risk->players[$id]['state'],
				'round' => $this->_risk->players[$id]['extra_info']['round'],
				'turn' => $this->_risk->players[$id]['extra_info']['turn'],
			];

			$order[$id] = $temp_players[$id]['order'];
		}

		asort($order);

		$players = [];
		foreach ($order as $id => $null) {
			$players[$id] = $temp_players[$id];
		}

		foreach ($players as $player_id => & $player) {
			// continents
			$player['conts'] = $this->get_players_visible_continents($player_id, $observer_id);

			$player['cont_names'] = [];
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
				$player['cards'] = [];
				$player['card_count'] = '--';
				$player['trade_perc'] = '--';
				$player['conts'] = [];
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
		$this->modify_date = strtotime($info[2]);
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


	/**
	 * @TODO: not sure if this method is really needed
	 */
	protected function _write_faux_db( ) {
		$this->_faux_db[TABLE_GAME] = [
			'game_id' => $this->id,
			'name' => $this->name,
			'capacity' => $this->capacity,
			'game_type' => $this->type,
			'next_bonus' => 4,
			'state' => $this->state,
			'extra_info' => $this->_extra_info,
		];

		$this->_faux_db[TABLE_PLAYERS] = $this->_players;

		$land = [];
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
		$count = count($players);
		foreach ($players as $player) {
			$player = explode(' - ', $player);
			$player = array_combine(['player_id', 'color', 'name'], $player);
			$player['order_num'] = $n;

			$this->_set_player_data($player, $count);

			++$n;
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
			$player['cards'] = [];
		}

		$player['game_id'] = $this->id;

		// move any data we need to over to the risk class player data
		$risk_player = $player;

		$player_keys = [
			'player_id',
			'color',
			'name',
		];

		$player = array_clean($player, $player_keys);

		$risk_player['armies'] = $this->_risk->get_start_armies($count);
		$risk_player['state'] = 'Placing';

		$risk_player_keys = [
			'player_id',
			'order_num',
			'cards',
			'armies',
			'state',
			'extra_info',
		];

		$risk_player = array_clean($risk_player, $risk_player_keys);

		$this->_players[$player['player_id']] = $player;
		$this->_risk->players[$player['player_id']] = $risk_player;
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
				if ( ! in_array($player['state'], ['Waiting', 'Resigned', 'Dead'])) {
					$this->_risk->current_player = $player_id;
					break;
				}
			}
		}
		else {
			$this->_risk->current_player = 0;
		}

		$board = [];
		foreach ($this->_faux_db[TABLE_LAND] as $land) {
			$board[$land['land_id']] = [
				'player_id' => $land['player_id'] ,
				'armies' => $land['armies'] ,
			];
		}

		$this->_risk->board = $board;

		$this->_calculate_trade_values( );
		$this->_risk->find_available_cards( );
		$this->_risk->set_extra_info($this->_extra_info);
	}


	/** protected function _save
	 *		Replaces Game::_save to prevent accidental DB writes
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
			if ( ! in_array($player['state'], ['Resigned', 'Dead'])) {
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
	 *
	 * @action begins the game
	 *
	 * @return void
	 */
	protected function _begin( )
	{
		call(__METHOD__);

		// make sure the game is 'Playing'
		$this->state = 'Playing';

		$this->_risk->begin( );
	}


	/**
	 * Plays the game to the given step (inclusive)
	 *
	 * @param int $step to play to (inclusive)
	 *
	 * @return void
	 * @throws MyException
	 */
	public function play_to($step) {
		call(__METHOD__);
		call($step);

		if ($step > (count($this->_file[FILE_GAME_LOG]) - 1)) {
			$step = count($this->_file[FILE_GAME_LOG]) - 1;
		}

		try {
			for ($i = 0; $i <= $step; ++$i) {
				call('--- STEP = ' . $i . ' ---');
				$this->step = $i;
				$this->do_action($this->_file[FILE_GAME_LOG][$i]);
			}
		}
		catch (MyException $e) {
			throw $e;
		}
	}


	/**
	 * Performs the action given on the current game
	 *
	 * @param $action
	 *
	 * @throws Exception
	 * @throws MyException
	 */
	protected function do_action($action) {
		call(__METHOD__);
		call($action);

		list($type, $action) = explode(' ', $action);
		$type = strtoupper($type);
		$action = explode(':', $action);

		try {
			// get the player into the correct state
			if (in_array($type, ['A', 'F', 'O']) && (0 !== $this->_risk->current_player)) {
				while ($type !== strtoupper($this->_risk->players[$this->_risk->current_player]['state'][0])) {
					$this->_risk->set_player_next_state($this->_risk->players[$this->_risk->current_player]['state'], $this->_risk->current_player);
				}
			}

			switch ($type) {
				case 'A' : // Attack
					$rolls = explode(',', $action[4]);

					$player_id = $action[0];
					$attack_land_id = $action[1];
					$defend_land_id = $action[3];
					$attack_roll = $rolls[0];
					$defend_roll = $rolls[1];
					$num_armies = strlen($attack_roll);

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
					// if the game was in 'Placing' state, and we get here
					// remove all armies from everybody, start fresh
					// there were bugs in the game previously that messed up
					// the army count for each player, so we just have to let
					// those invalid army counts through
					if ('Placing' === $this->state) {
						foreach ($this->_risk->players as & $player) { // mind the reference
							$player['armies'] = 0;

							// and set all players into a waiting state
							$player['state'] = 'Waiting';

							// also... the current player is not set, so do that as well
							if (1 == $player['order_num']) {
								$this->_risk->current_player = $player['player_id'];
							}
						}
						unset($player); // kill the reference

						$this->state = 'Playing';
					}

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
					$this->place_armies($player_id, $num_armies, $land_id, true, true);
					break;

				case 'Q' : // Quit (resign)
					$player_id = $action[0];

					$this->resign($player_id);
					break;

				case 'R' : // Reinforcements
					$player_id = $action[0];
					$armies = $action[1];

					$this->_risk->players[$player_id]['armies'] += $armies;
					call($this->_risk->players);
					break;

				case 'T' : // Trade
					$player_id = $action[0];
					$cards = array_trim($action[1], 'int');
					$bonus = $action[3];

					$this->trade_cards($player_id, $cards, $bonus);
					break;

				case 'V' : // Value
					$value = $action[0];

					// should be handled in the trade_cards method
					// but just in case...
					if ($value !== $this->_risk->get_trade_value()) {
						$this->_risk->set_trade_value($value);
					}
					break;

				default :
					throw new MyException(__METHOD__.': Invalid action type ('.$type.') encountered');
					break;
			}
		}
		catch (MyException $e) {
			throw $e;
		}
	}


	/**
	 * Get a human readable version of the current move
	 *
	 * @param void
	 *
	 * @return string
	 */
	public function get_move_info( ) {
		return self::parse_move_info($this->_file[FILE_GAME_LOG][(int) $this->step], $this->_extra_info['trade_card_bonus']);
	}

} // end of Review class
