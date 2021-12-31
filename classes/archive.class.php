<?php
/*
+---------------------------------------------------------------------------
|
|   archive.class.php (php 5.x)
|
|   by Benjam Welker
|   http://iohelix.net
|
+---------------------------------------------------------------------------
|
|	This module is built to pull the archive files
|
+---------------------------------------------------------------------------
|
|   > WebRisk Game Archive module
|   > Date started: 2015-02-15
|
|   > Module Version Number: 0.8.0
|
+---------------------------------------------------------------------------
*/

class Archive extends Game {

	/**
	 * Get the filename list
	 *
	 * @param void
	 *
	 * @return array of valid archive file names
	 */
	public static function get_files( ) {
		chdir(GAMES_DIR);

		$files = glob(GAME_NAME.'_*.dat');

		usort($files, function($a, $b) {
			$a = explode('.', $a);
			$a = explode('_', $a[0]);
			$a = (int) $a[2];

			$b = explode('.', $b);
			$b = explode('_', $b[0]);
			$b = (int) $b[2];

			return $b - $a; // reverse order
		});

		return $files;
	}


	/**
	 * Get the file count
	 *
	 * @param int $player_id not used
	 *
	 * @return int
	 */
	public static function get_count($player_id = 0) {
		return count(self::get_files( ));
	}


	/**
	 * Get the list of games with parsed info
	 *
	 * @param int $player_id not used
	 *
	 * @return array games list
	 */
	public static function get_list($player_id = 0) {
		$games = self::get_files( );

		$return = [];

		foreach ($games as & $game) {
			$entry = [];
			$entry['file'] = $game;
			$entry['short_file'] = substr($game, 0, -4); // remove the extension

			$file = fopen($game, 'r');

			// read the first 3 sections of each file and parse it
			$n = 0;
			$section = 0;
			do {
				$line = trim(fgets($file));

				if ('=====' === substr($line, 0, 5)) {
					++$section;
					$n = 0;
					continue;
				}

				switch ($section) {
					case 0 : // game info section section
						switch ($n) {
							case 0 : // game info
								$data = explode(' - ', $line);

								if (3 < count($data)) {
									$data[0] = substr($line, 0, strpos($line, ' - '));
									$data[1] = substr($line, strpos($line, ' - ') + 3, strrpos($line, ' - ') - 6);
									$data[2] = substr($line, strrpos($line, ' - ') + 3);
								}

								list($entry['game_id'], $entry['name'], $entry['game_type']) = $data;
								break;

							case 1 : // game start date
								$entry['start_date'] = $line;
								break;

							case 2 : // game end date
								$entry['end_date'] = $line;
								break;

							case 3 : // game winner
								$winner = explode(' - ', $line, 2);
								$entry['winner'] = $winner[0];

								if (2 === count($winner)) {
									list($entry['winner_id'], $entry['winner']) = $winner;
								}

								break;

							default :
								break;
						}

					case 1 : // extra info section
						$entry['extra_info'] = array_merge_plus(self::$_EXTRA_INFO_DEFAULTS, json_decode($line, true));
						break;

					case 2 : // player data section
						$player = [
							'order' => $n + 1,
						];

						list($player['id'], $player['color'], $player['name']) = explode(' - ', $line, 3);

						$entry['player_data'][$player['id']] = $player;
						break;
				}

				++$n;
			}
			while (3 > $section);

			fclose($file);

			$entry['get_fortify'] = self::_get_fortify($entry['extra_info']);
			$entry['get_kamikaze'] = self::_get_kamikaze($entry['extra_info']);
			$entry['get_warmonger'] = self::_get_warmonger($entry['extra_info']);
			$entry['get_conquer_limit'] = self::_get_conquer_limit($entry['extra_info']);
			$entry['get_custom_rules'] = self::_get_custom_rules($entry['extra_info']);
			$entry['get_fog_of_war'] = self::_get_fog_of_war($entry['extra_info']);
			$entry['get_fog_of_war_armies'] = $entry['get_fog_of_war']['armies'];
			$entry['get_fog_of_war_colors'] = $entry['get_fog_of_war']['colors'];

			$entry['clean_name'] = htmlentities($entry['name'], ENT_QUOTES, 'UTF-8', false);
			$entry['clean_custom_rules'] = htmlentities($entry['get_custom_rules'], ENT_QUOTES, 'UTF-8', false);
			$entry['players'] = count($entry['player_data']);

			$return[] = $entry;
		}

		return $return;
	}

}
