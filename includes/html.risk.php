<?php

/**
 * Generates and returns the game info box html
 *
 * @param $Game Game object
 *
 * @return string game info html
 */
function game_info($Game) {
	ob_start( );

	?>

	<div id="game_info">

		<h2>Risk Analysis</h2>

		<?php
			$player_data = $Game->get_players_visible_data( );
			unset($player_data[0]); // current player for un-started review games
			unset($player_data['']); // host for review games

			$ra_table_meta = [
				'sortable' => true ,
				'no_data' => '<p>There are no players to show</p>' ,
				'class' => 'datatable users',
			];

			$ra_table_format = [
				['SPECIAL_CLASS', 'true', '###substr(\'[[[color]]]\', 0, 3).((\'Dead\' == \'[[[state]]]\') ? \' dead\' : \'\')'],

				['Order', 'order', 'digit'],
				['Player', 'username', 'text'],
				['State', 'state', 'text'],
				['Round', 'round', 'digit'],
				['Turn', 'turn', 'digit'],
				['Armies', 'armies', 'digitmissing'],
				['Land', 'land', 'digitmissing'],
				['Conts', 'cont_list', 'digitmissing'],
				['Cards', 'card_count', 'digitmissing'],
			//	['% trade', 'trade_perc', 'percentmissing'],
				['Next turn', '[[[next_armies]]] / [[[next_armies_trade]]]', 'nextturn'],
			];

			echo get_table($ra_table_format, $player_data, $ra_table_meta);

		?>

		<table class="game_info">
			<tbody>
			<tr>
				<th>Game Type</th>
				<td><?php echo $Game->get_type( ); ?></td>
			</tr><tr>
				<th>Placement Type</th>
				<td><?php echo $Game->get_placement( ); ?></td>
			</tr><tr>
				<th>Placement Limit</th>
				<td><?php echo $Game->get_placement_limit( ); ?></td>
			</tr><tr>
				<th>Fortification Type</th>
				<td><?php echo $Game->get_fortify( ); ?></td>
			</tr><tr>
				<th>Kamikaze</th>
				<td><?php echo $Game->get_kamikaze( ); ?></td>
			</tr><tr>
				<th>Warmonger</th>
				<td><?php echo $Game->get_warmonger( ); ?></td>
			</tr><tr>
				<th>Nuke</th>
				<td><?php echo $Game->get_nuke( ); ?></td>	
			</tr><tr>
			 	<th>Turncoat</th>
				<td><?php echo $Game->get_turncoat( ); ?></td>		
			</tr><tr>
				<th>Fog of War</th>
				<td><?php $fog_of_war = $Game->get_fog_of_war( );
					echo 'Armies: '.$fog_of_war['armies'].'<br />Colors: '.$fog_of_war['colors']; ?></td>
			</tr><tr>
				<th>Trade Count</th>
				<td><?php echo $Game->get_trade_count( ); ?></td>
			</tr><tr>
				<th>Custom Trade Array</th>
				<td><?php
					$trades = $Game->get_trade_array( );

					if ( ! $trades) {
						echo 'None';
					}
					else {
						echo '<table>';
						echo '<thead><tr><th>Start</th><th>End</th><th>Step</th><th>Times</th></tr></thead>';
						echo '<tbody>';

						$n = 0;
						foreach ($trades as $trade) {
							if ( ! isset($trade[3])) {
								$trade[3] = 0;
							}

							++$n;
							echo '<tr'.((0 === ($n % 2)) ? ' class="alt"' : '').'><td>'.$trade[0].'</td><td>'.$trade[1].'</td><td>'.$trade[2].'</td><td>'.$trade[3].'</td></tr>';
						}

						echo '</tbody></table>';
					}
					?></td>
			</tr><tr>
				<th>Trade Card Bonus</th>
				<td><?php echo $Game->get_trade_card_bonus( ); ?></td>
			</tr><tr>
				<th>Conquer Limit</th>
				<td><?php echo $Game->get_conquer_limit( ); ?></td>
			</tr><tr>
				<th>Custom Rules</th>
				<td><?php echo htmlentities($Game->get_custom_rules( ), ENT_QUOTES, 'UTF-8', false); ?></td>
			</tr>
			</tbody>
		</table> <!-- .game_info -->

		<?php echo conquer_limit_table($Game); ?>

		<table class="datatable custom_trades">
			<caption>Trade Values</caption>
			<thead>
			<tr>
				<th>Trade #</th>
				<th>Value</th>
			</tr>
			</thead>
			<tbody>
				<?php echo trade_value_table($Game, $Game->get_trade_count( )); ?>
			</tbody>
		</table> <!-- .custom_trades -->

	</div> <!-- #game_info -->

	<?php

	$html = ob_get_clean( );

	return $html;
}


/**
 * Return the class string for the table row
 * based on the current idx and count
 *
 * @param int $idx
 * @param int $count
 *
 * @return string
 */
function get_html_class($idx, $count) {
	$classes = [];

	if (0 === ($idx % 2)) {
		$classes[] = 'alt';
	}

	if ($idx === $count) {
		$classes[] = 'highlight';
	}

	if ($classes) {
		return ' class="' . implode(' ', $classes) . '"';
	}

	return '';
}


/**
 * Generate and return the conquer limit table
 *
 * @param array|Game $extra_info
 *
 * @return string conquer limit table
 */
function conquer_limit_table($extra_info) {
	if (is_a($extra_info, 'Game')) {
		$extra_info = $extra_info->get_extra_info( );
	}

	ob_start( );

	if ('none' != $extra_info['conquer_type']) {
		// pull our variables out to use them here
		$type = '';
		foreach ($extra_info as $key => $value) {
			if ('conquer_' == substr($key, 0, 8)) {
				$key = substr($key, 8);
				${$key} = $value;
			}
		}

		// the number of multipliers to skip before incrementing
		// e.g.- if it's 1 conquest per 10 trades, and skip is 1
		// the conquest value won't increase until trade value reaches 20
		// which is 10 trades past when it would increase at 10
		if (empty($skip) || ! (int) $skip) {
			$skip = 0;
		}

		// the number of conquests to start from
		if (empty($start_at) || ! (int) $start_at) {
			$start_at = 0;
		}

		// the number of conquests to allow per multiplier
		// e.g.- if it's conquests per 10 trades, and conquests_per
		// is 1, you will gain 1 conquest for every 10 trade value
		// so for 35 trade value, you will get 3 (3 * 1) conquests
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

		$conquests = [];
		$repeats = 0;
		$prev_limit = 0;
		for ($n = 0; $n <= 200; ++$n) {
			$limit = max((((((int) floor(($n - $start_count) / $per_number)) + 1) - $skip) * $conquests_per), 0) + $start_at;
			$limit = ($limit < $minimum) ? $minimum : $limit;
			$limit = ($limit > $maximum) ? $maximum : $limit;

			if ($limit !== $prev_limit) {
				$prev_limit = $limit;
			}
			elseif ($limit === $maximum) {
				++$repeats;
			}

			$conquests[$n] = $limit;

			// stop after 3 repeated max values
			if (3 <= $repeats) {
				$conquests['...'] = $limit;
				break;
			}
		}

		// don't show 0 count for certain types
		if ( ! in_array($type, ['trade_value', 'trade_count', 'continents'])) {
			unset($conquests[0]);
		}
		?>

		<table class="datatable conquer_limits">
			<caption>max( ( ( ( floor( (x - <?php echo $start_count; ?>) / <span class="per_number"><?php echo $per_number; ?></span> ) + 1 ) - <span class="skip"><?php echo $skip; ?></span> ) * <span class="conquests_per"><?php echo $conquests_per; ?></span> ) , 0 ) + <span class="start_at"><?php echo $start_at; ?></span></caption>
			<thead>
			<tr>
				<th id="conquer_type_header"><?php echo ucwords(str_replace('_', ' ', $type)); ?></th>
				<th>Conquer Limit</th>
			</tr>
			</thead>
			<tbody>
			<?php foreach ($conquests as $n => $value) { ?>

				<tr<?php echo ((0 !== ($n % 2)) ? ' class="alt"' : ''); ?>>
					<td><?php echo $n; ?></td>
					<td><?php echo $value; ?></td>
				</tr>
			<?php } ?>

			</tbody>
		</table> <!-- .conquer_limits -->

	<?php } // end conquer type table

	$html = ob_get_clean( );

	return $html;
}


/**
 * Generate and return the trade table rows
 *
 * @param array|Game $trade_values
 *
 * @return string trade table rows
 */
function trade_value_table($trade_values, $trade_count = 0) {
	if (is_a($trade_values, 'Game')) {
		$trade_values = Review::calculate_trade_values($trade_values->get_trade_array( ));
	}

	$trade_count++; // highlight the _next_ trade

	$table = $classes = '';
	$prev_value = 0;
	foreach ($trade_values as $trade => $value) {
		$idx = $trade + 1;

		if ('-' == $value[0]) {
			// if minus, go till 0, then show 0 three times
			$next_value = $prev_value;
			while (0 < $next_value) {
				$next_value += (int) $value;
				if (0 >= $next_value) {
					--$idx;
					break;
				}
				$table .= "<tr".get_html_class($idx, $trade_count)."><td>{$idx}</td><td>{$next_value}</td></tr>\n";
				++$idx;
			}

			while ($idx <= $trade_count) {
				$table .= "<tr" . get_html_class($idx, $trade_count) . "><td>{$idx}</td><td>0</td></tr>\n";
				++$idx;
			}

			$table .= "<tr".get_html_class($idx, $trade_count)."><td>{$idx}</td><td>0</td></tr>\n";
			++$idx;

			$table .= "<tr".get_html_class($idx, $trade_count)."><td>{$idx}</td><td>0</td></tr>\n";
			++$idx;

			$table .= "<tr".get_html_class($idx, $trade_count)."><td>{$idx}</td><td>...</td></tr>\n";
		}
		elseif('+' == $value[0]) {
			// if plus, go for three then append plus value
			$next_value = $prev_value;

			while ($idx <= $trade_count) {
				$next_value += (int) $value;
				$table .= "<tr" . get_html_class($idx, $trade_count) . "><td>{$idx}</td><td>{$next_value}</td></tr>\n";
				++$idx;
			}

			for ($i = 1; $i <= 3; ++$i) {
				$next_value += (int) $value;
				$table .= "<tr".get_html_class($idx, $trade_count)."><td>{$idx}</td><td>{$next_value}</td></tr>\n";
				++$idx;
			}

			$value = '('.$value.')';
			$table .= "<tr".get_html_class($idx, $trade_count)."><td>{$idx}</td><td>{$value}</td></tr>\n";
		}
		else {
			// nothing special, just append the value
			$table .= "<tr".get_html_class($idx, $trade_count)."><td>{$idx}</td><td>{$value}</td></tr>\n";
			$prev_value = $value;
		}
	}

	// if the last value was not a changer
	// show the last value three times
	$extended = false;
	if ( ! in_array($trade_values[count($trade_values) - 1][0], ['+', '-'])) {
		$idx = count($trade_values) + 1;
		$value = $trade_values[count($trade_values) - 1];

		while ($idx <= $trade_count) {
			$extended = true;
			$table .= "<tr" . get_html_class($idx, $trade_count) . "><td>{$idx}</td><td>{$value}</td></tr>\n";
			++$idx;
		}

		if ($extended) {
			$table .= "<tr" . get_html_class($idx, $trade_count) . "><td>{$idx}</td><td>{$value}</td></tr>\n";
			++$idx;
		}

		$table .= "<tr".get_html_class($idx, $trade_count)."><td>{$idx}</td><td>{$value}</td></tr>\n";
		++$idx;

		$table .= "<tr".get_html_class($idx, $trade_count)."><td>{$idx}</td><td>...</td></tr>\n";
	}

	return $table;
}


/**
 * Gets the board html
 *
 * @param void
 *
 * @return string board html
 */
function board($Game) {
	$board = $Game->get_visible_board( );

	$html = '';
	foreach ($board as $land_id => $data) {
		$id_box = '';
		if ( ! empty($_SESSION['admin_id']) || $GLOBALS['Player']->is_admin) {
			$id_box = ' ['.str_pad($land_id, 2, '0', STR_PAD_LEFT).']';
		}

		$html .= '
							<span class="'.substr($data['color'], 0, 3).$data['resigned'].'" id="sl'.str_pad($land_id, 2, '0', STR_PAD_LEFT).'" title="'.Risk::$TERRITORIES[$land_id][NAME].$id_box.'">'.$data['armies'].'</span>';
	}

	return $html;
}


/**
 * Gets the gamemap html
 *
 * @param void
 *
 * @return string gamemap html
 */
function game_map( ) {

$html = <<< EOHTML

	<map id="gamemap" name="gamemap">
		<area nohref="nohref" id="ma01" alt="Alaska [01]" title="Alaska [01]" shape="poly" coords="48,40,72,48,72,80,81,80,89,99,85,108,71,88,43,82,19,98,27,86,13,77,18,68,22,55,27,46" />
		<area nohref="nohref" id="ma02" alt="Alberta [02]" title="Alberta [02]" shape="poly" coords="89,99,85,108,96,129,150,129,151,82,81,80" />
		<area nohref="nohref" id="ma03" alt="Central America [03]" title="Central America [03]" shape="poly" coords="99,183,127,225,141,229,149,244,159,253,159,247,156,244,163,233,158,227,165,215,157,214,150,218,141,215,140,205,144,200,139,194" />
		<area nohref="nohref" id="ma04" alt="Eastern United States [04]" title="Eastern United States [04]" shape="poly" coords="165,129,173,129,192,153,211,144,233,138,235,146,212,173,199,192,200,205,194,208,190,193,169,191,152,194,144,200,139,194,153,165,165,164" />
		<area nohref="nohref" id="ma05" alt="Greenland [05]" title="Greenland [05]" shape="poly" coords="220,40,229,27,261,15,276,5,326,13,317,29,314,56,287,74,279,75,272,97,256,88,252,73,256,64,244,45,235,41,223,43" />
		<area nohref="nohref" id="ma06" alt="Northwest Territory [06]" title="Northwest Territory [06]" shape="poly" coords="72,48,116,46,144,56,178,51,179,37,192,30,185,39,188,51,204,52,205,61,187,71,177,78,171,87,168,82,81,80,72,80" />
		<area nohref="nohref" id="ma07" alt="Ontario [07]" title="Ontario [07]" shape="poly" coords="151,82,168,82,171,87,172,94,197,109,201,123,201,141,210,141,211,144,192,153,173,129,150,129" />
		<area nohref="nohref" id="ma08" alt="Quebec [08]" title="Quebec [08]" shape="poly" coords="201,123,213,101,211,92,217,80,226,81,234,96,240,95,242,89,246,89,245,95,260,113,260,121,239,130,240,141,243,143,247,142,236,154,238,146,235,146,233,138,211,144,210,141,201,141" />
		<area nohref="nohref" id="ma09" alt="Western United States [09]" title="Western United States [09]" shape="poly" coords="96,129,90,159,99,183,139,194,153,165,165,164,165,129" />

		<area nohref="nohref" id="ma10" alt="Argentina [10]" title="Argentina [10]" shape="poly" coords="192,323,202,334,206,334,207,330,220,332,234,339,231,347,240,346,245,341,248,343,239,351,250,362,247,367,239,376,227,378,215,401,214,416,221,428,234,432,224,436,200,426,188,406" />
		<area nohref="nohref" id="ma11" alt="Brazil [11]" title="Brazil [11]" shape="poly" coords="252,260,245,267,237,267,223,268,221,260,208,264,209,267,203,271,195,267,191,273,192,277,187,281,184,279,175,291,187,302,194,303,204,299,223,308,235,324,235,329,242,332,245,341,248,343,239,351,250,362,260,351,260,341,281,333,290,321,291,308,304,289,295,281,265,272" />
		<area nohref="nohref" id="ma12" alt="Peru [12]" title="Peru [12]" shape="poly" coords="161,269,176,277,184,279,175,291,187,302,194,303,204,299,223,308,235,324,235,329,242,332,245,341,240,346,231,347,234,339,220,332,207,330,206,334,202,334,192,323,174,313,155,288,156,276" />
		<area nohref="nohref" id="ma13" alt="Venezuela [13]" title="Venezuela [13]" shape="poly" coords="159,247,165,246,169,251,181,241,218,243,234,255,252,260,245,267,237,267,223,268,221,260,208,264,209,267,203,271,195,267,191,273,192,278,187,281,184,279,176,277,161,269,165,258,164,253,159,253" />

		<area nohref="nohref" id="ma14" alt="Great Britain [14]" title="Great Britain [14]" shape="poly" coords="332,111,355,152,352,163,325,167,304,161,312,136,328,135,317,119" />
		<area nohref="nohref" id="ma15" alt="Iceland [15]" title="Iceland [15]" shape="poly" coords="325,79,356,78,368,88,362,97,346,104,329,100" />
		<area nohref="nohref" id="ma16" alt="Northern Europe [16]" title="Northern Europe [16]" shape="poly" coords="367,164,378,179,413,171,413,181,423,181,423,173,430,166,427,163,441,150,436,132,428,129,390,131" />
		<area nohref="nohref" id="ma17" alt="Scandinavia [17]" title="Scandinavia [17]" shape="poly" coords="381,113,378,92,406,64,429,54,445,58,444,99,425,101,418,103,406,124,391,109" />
		<area nohref="nohref" id="ma18" alt="Southern Europe [18]" title="Southern Europe [18]" shape="poly" coords="375,203,378,179,413,171,413,181,423,181,423,173,430,166,441,173,443,188,439,201,434,212,426,215,429,228,421,229,412,211,394,228,390,228,385,222,396,222,396,212" />
		<area nohref="nohref" id="ma19" alt="Ukraine [19]" title="Ukraine [19]" shape="poly" coords="443,188,441,173,430,166,427,163,441,150,436,132,428,129,430,113,444,99,445,58,499,62,525,54,542,58,542,68,534,76,537,102,534,111,542,116,541,129,527,136,502,140,499,152,504,166,498,176,508,188,506,200,497,205,487,205,491,201,471,184" />
		<area nohref="nohref" id="ma20" alt="Western Europe [20]" title="Western Europe [20]" shape="poly" coords="367,164,378,179,375,203,361,204,362,222,350,237,345,239,336,232,326,232,320,225,325,214,318,204,325,198,340,202,344,191,332,177,346,178" />

		<area nohref="nohref" id="ma21" alt="Congo [21]" title="Congo [21]" shape="poly" coords="408,349,433,355,435,364,452,368,458,357,456,347,472,329,469,325,452,324,437,305,417,318,417,331,401,330,398,339" />
		<area nohref="nohref" id="ma22" alt="East Africa [22]" title="East Africa [22]" shape="poly" coords="437,305,452,324,469,325,472,329,456,347,458,357,467,365,467,377,472,379,473,368,484,364,481,350,510,324,519,306,497,308,474,275,443,276,439,282,435,298" />
		<area nohref="nohref" id="ma23" alt="Egypt [23]" title="Egypt [23]" shape="poly" coords="474,275,443,276,439,282,432,282,426,275,400,267,400,247,407,242,431,244,468,248,470,256,465,260" />
		<area nohref="nohref" id="ma24" alt="Madagascar [24]" title="Madagascar [24]" shape="poly" coords="497,385,511,383,525,369,528,380,507,418,494,418,492,406,500,391" />
		<area nohref="nohref" id="ma25" alt="North Africa [25]" title="North Africa [25]" shape="poly" coords="407,242,400,247,400,267,426,275,432,282,439,282,435,298,437,305,417,318,417,331,401,330,401,321,392,322,388,316,353,320,328,296,332,284,329,274,345,239,350,237,354,240,390,228,394,228" />
		<area nohref="nohref" id="ma26" alt="South Africa [26]" title="South Africa [26]" shape="poly" coords="408,349,433,355,435,364,452,368,458,357,467,365,467,377,472,379,473,368,484,364,487,378,472,391,472,401,457,425,425,433,414,395,405,382,413,369" />

		<area nohref="nohref" id="ma27" alt="Afghanistan [27]" title="Afghanistan [27]" shape="poly" coords="504,166,499,152,502,140,527,136,541,129,567,151,580,152,586,158,587,168,580,179,573,180,578,194,570,201,566,197,552,202,544,209,533,200,524,201,519,180,512,177,513,170,518,168,516,159,507,161" />
		<area nohref="nohref" id="ma28" alt="China [28]" title="China [28]" shape="poly" coords="671,225,663,217,653,226,637,213,633,216,631,210,617,211,606,204,592,208,591,198,578,194,573,180,580,179,587,168,586,158,600,138,609,134,614,139,613,145,620,146,624,155,639,167,687,170,690,173,699,183,699,202,693,213,677,228" />
		<area nohref="nohref" id="ma29" alt="India [29]" title="India [29]" shape="poly" coords="551,241,544,220,544,209,552,202,566,197,570,201,578,194,591,198,592,208,606,204,617,211,631,210,633,216,623,231,611,237,605,254,600,285,594,302,589,297,577,271,576,258,558,241" />
		<area nohref="nohref" id="ma30" alt="Irkutsk [30]" title="Irkutsk [30]" shape="poly" coords="700,125,695,125,684,110,671,112,667,128,621,126,614,122,616,106,629,96,636,103,641,99,642,80,652,77,654,71,674,69,673,91,688,102,697,102,703,111" />
		<area nohref="nohref" id="ma31" alt="Japan [31]" title="Japan [31]" shape="poly" coords="716,175,722,182,728,181,731,174,760,153,757,134,763,113,741,110,741,136,743,145" />
		<area nohref="nohref" id="ma32" alt="Kamchatka [32]" title="Kamchatka [32]" shape="poly" coords="711,32,700,39,699,48,706,48,703,59,693,59,693,54,687,54,674,69,673,91,688,102,697,102,703,111,700,125,697,130,710,140,718,128,717,113,712,95,695,91,703,77,729,72,742,74,736,88,747,104,751,81,747,73,760,70,773,57,781,52,788,52,789,44,782,40,774,38,755,28,744,28,743,32,715,27" />
		<area nohref="nohref" id="ma33" alt="Middle East [33]" title="Middle East [33]" shape="poly" coords="440,226,434,212,439,201,487,205,497,205,506,200,519,207,524,201,533,200,544,209,544,220,551,241,535,254,544,259,543,271,533,286,520,295,501,298,470,256,468,248,472,244,470,226" />
		<area nohref="nohref" id="ma34" alt="Mongolia [34]" title="Mongolia [34]" shape="poly" coords="621,126,620,146,624,155,639,167,687,170,690,173,696,168,709,171,715,164,703,152,710,140,697,130,700,125,695,125,684,110,671,112,667,128" />
		<area nohref="nohref" id="ma35" alt="Siam [35]" title="Siam [35]" shape="poly" coords="633,216,623,231,628,241,637,249,641,246,649,255,649,266,659,277,662,275,659,271,655,256,671,268,679,259,679,252,674,243,663,232,671,225,663,217,653,226,637,213" />
		<area nohref="nohref" id="ma36" alt="Siberia [36]" title="Siberia [36]" shape="poly" coords="568,66,574,82,585,93,584,99,589,107,580,120,588,121,589,127,600,138,609,134,614,139,613,145,620,146,621,126,614,122,616,106,629,96,636,103,641,99,642,80,637,57,630,52,637,46,647,47,648,38,651,32,647,24,629,22,620,11,609,8,576,23,554,42,557,53,566,54" />
		<area nohref="nohref" id="ma37" alt="Ural [37]" title="Ural [37]" shape="poly" coords="600,138,586,158,580,152,567,151,541,129,542,116,534,111,537,102,534,76,542,68,542,58,544,56,537,48,539,35,550,40,554,59,562,57,568,66,574,82,585,93,584,99,589,107,580,120,588,121,589,127" />
		<area nohref="nohref" id="ma38" alt="Yakutsk [38]" title="Yakutsk [38]" shape="poly" coords="647,24,651,32,648,38,647,47,637,46,630,52,637,57,642,80,652,77,654,71,674,69,687,54,693,54,693,59,703,59,706,48,699,48,700,39,711,32,710,27,698,22,682,23,668,30,659,20,651,20" />

		<area nohref="nohref" id="ma39" alt="Eastern Australia [39]" title="Eastern Australia [39]" shape="poly" coords="714,335,714,365,747,365,745,409,770,405,787,374,786,362,769,345,758,321,752,338,736,333,743,324,718,327" />
		<area nohref="nohref" id="ma40" alt="Indonesia [40]" title="Indonesia [40]" shape="poly" coords="617,293,648,306,674,284,683,291,679,302,698,303,692,327,685,336,651,335" />
		<area nohref="nohref" id="ma41" alt="New Guinea [41]" title="New Guinea [41]" shape="poly" coords="700,282,708,292,719,294,719,305,738,310,762,312,757,298,735,280,705,277" />
		<area nohref="nohref" id="ma42" alt="Western Australia [42]" title="Western Australia [42]" shape="poly" coords="664,356,710,331,714,335,714,365,747,365,745,409,724,386,701,389,677,399,665,396" />
	</map> <!-- #gamemap -->

EOHTML;

	return $html;
}

