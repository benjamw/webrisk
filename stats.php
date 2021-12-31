<?php

require_once 'includes/inc.global.php';

$meta['title'] = 'Statistics';
$meta['head_data'] = '
	<script>//<![CDATA[
		$(document).ready( function( ) {
			$("td.color, .color td").each( function(i, elem) {
				var $elem = $(elem);
				var text = parseFloat($elem.text( ));

				if (0 < text) {
					$elem.css("color", "green");
				}
				else if (0 > text) {
					$elem.css("color", "red");
				}
			});
		});
	//]]></script>
';

$hints = [
	'View '.GAME_NAME.' Player and Game statistics.' ,
	'A Kill is when you eradicate a player from the game.' ,
];

// grab the wins and losses for the players
$list = GamePlayer::get_list(true);

$table_meta = [
	'sortable' => true,
	'no_data' => '<p>There are no player stats to show</p>',
	'caption' => 'Player Stats',
	'init_sort_column' => [1 => 1],
];
$table_format = [
	['Player', 'username'],
	['Wins', 'wins'],
	['Kills', 'kills'],
	['Losses', 'losses'],
	['Win-Loss', '###([[[wins]]] - [[[losses]]])', null, ' class="color"'],
	['Win %', '###((0 != ([[[wins]]] + [[[losses]]])) ? perc([[[wins]]] / ([[[wins]]] + [[[losses]]]), 1) : 0)'],
	['Kill-Loss', '###([[[kills]]] - [[[losses]]])', null, ' class="color"'],
	['Kill-Win', '###([[[kills]]] - [[[wins]]])', null, ' class="color"'],
	['Kill %', '###((0 != ([[[wins]]] + [[[losses]]])) ? perc([[[kills]]] / ([[[wins]]] + [[[losses]]]), 1) : 0)'],
	['Last Online', '###ldate(Settings::read(\'long_date\'), strtotime(\'[[[last_online]]]\'))', null, ' class="date"'],
];
$contents = get_table($table_format, $list, $table_meta);

extract(Game::get_roll_stats( )); // extracts $actual, $theor, and $values arrays

// we can't use the table creator for this one, just build it by hand
$contents .= '
<table class="dicetable">
	<caption>Dice Percentages</caption>
	<thead>
		<tr>
			<th colspan="2" rowspan="2">&nbsp;</th>
			<th colspan="1" rowspan="2">Outcome</th>
			<th colspan="3" rowspan="1">Attack</th>
		</tr>
		<tr>
			<th>1</th>
			<th>2</th>
			<th>3</th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<th colspan="1" rowspan="5">Defend</th>
			<th colspan="1" rowspan="2">1</th>
			<th>Attack</th>
			<td>'.perc($actual['1v1']['attack']).'</td>
			<td>'.perc($actual['2v1']['attack']).'</td>
			<td>'.perc($actual['3v1']['attack']).'</td>
		</tr>
		<tr class="alt">
			<th class="lower">Defend</th>
			<td class="lower">'.perc($actual['1v1']['defend']).'</td>
			<td class="lower">'.perc($actual['2v1']['defend']).'</td>
			<td class="lower">'.perc($actual['3v1']['defend']).'</td>
		</tr>
		<tr>
			<th colspan="1" rowspan="3">2</th>
			<th>Attack</th>
			<td>'.perc($actual['1v2']['attack']).'</td>
			<td>'.perc($actual['2v2']['attack']).'</td>
			<td>'.perc($actual['3v2']['attack']).'</td>
		</tr>
		<tr class="alt">
			<th>Defend</th>
			<td>'.perc($actual['1v2']['defend']).'</td>
			<td>'.perc($actual['2v2']['defend']).'</td>
			<td>'.perc($actual['3v2']['defend']).'</td>
		</tr>
		<tr>
			<th>Both</th>
			<td> -- </td>
			<td>'.perc($actual['2v2']['both']).'</td>
			<td>'.perc($actual['3v2']['both']).'</td>
		</tr>
	</tbody>
</table>

<table class="dicetable">
	<caption>Theoretical Dice Percentages</caption>
	<thead>
		<tr>
			<th colspan="2" rowspan="2">&nbsp;</th>
			<th colspan="1" rowspan="2" class="header">Outcome</th>
			<th colspan="3" rowspan="1" class="header">Attack</th>
		</tr>
		<tr>
			<th>1</th>
			<th>2</th>
			<th>3</th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<th colspan="1" rowspan="5" class="header">Defend</th>
			<th colspan="1" rowspan="2">1</th>
			<th>Attack</th>
			<td>'.perc($theor['1v1']['attack']).'</td>
			<td>'.perc($theor['2v1']['attack']).'</td>
			<td>'.perc($theor['3v1']['attack']).'</td>
		</tr>
		<tr class="alt">
			<th class="lower">Defend</th>
			<td class="lower">'.perc($theor['1v1']['defend']).'</td>
			<td class="lower">'.perc($theor['2v1']['defend']).'</td>
			<td class="lower">'.perc($theor['3v1']['defend']).'</td>
		</tr>
		<tr>
			<th colspan="1" rowspan="3">2</th>
			<th>Attack</th>
			<td>'.perc($theor['1v2']['attack']).'</td>
			<td>'.perc($theor['2v2']['attack']).'</td>
			<td>'.perc($theor['3v2']['attack']).'</td>
		</tr>
		<tr class="alt">
			<th>Defend</th>
			<td>'.perc($theor['1v2']['defend']).'</td>
			<td>'.perc($theor['2v2']['defend']).'</td>
			<td>'.perc($theor['3v2']['defend']).'</td>
		</tr>
		<tr>
			<th>Both</th>
			<td> -- </td>
			<td>'.perc($theor['2v2']['both']).'</td>
			<td>'.perc($theor['3v2']['both']).'</td>
		</tr>
	</tbody>
</table>

<table class="dicetable">
	<caption>Fight Counts</caption>
	<thead>
		<tr>
			<th colspan="2" rowspan="2">&nbsp;</th>
			<th colspan="1" rowspan="2">Outcome</th>
			<th colspan="3" rowspan="1">Attack</th>
			<th colspan="1" rowspan="2">Total</th>
		</tr>
		<tr>
			<th>1</th>
			<th>2</th>
			<th>3</th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<th colspan="1" rowspan="7">Defend</th>
			<th colspan="1" rowspan="3">1</th>
			<th>Attack</th>
			<td>'.$values['1v1']['attack'].'</td>
			<td>'.$values['2v1']['attack'].'</td>
			<td>'.$values['3v1']['attack'].'</td>
			<td>'.($values['1v1']['attack'] + $values['2v1']['attack'] + $values['3v1']['attack']).'</td>
		</tr>
		<tr class="alt">
			<th>Defend</th>
			<td class="lower">'.$values['1v1']['defend'].'</td>
			<td class="lower">'.$values['2v1']['defend'].'</td>
			<td class="lower">'.$values['3v1']['defend'].'</td>
			<td class="lower">'.($values['1v1']['defend'] + $values['2v1']['defend'] + $values['3v1']['defend']).'</td>
		</tr>
		<tr>
			<th>Total</th>
			<td class="lower">'.$count['1v1'].'</td>
			<td class="lower">'.$count['2v1'].'</td>
			<td class="lower">'.$count['3v1'].'</td>
			<td class="lower">'.($count['1v1'] + $count['2v1'] + $count['3v1']).'</td>
		</tr>
		<tr class="alt">
			<th colspan="1" rowspan="4">2</th>
			<th>Attack</th>
			<td>'.$values['1v2']['attack'].'</td>
			<td>'.$values['2v2']['attack'].'</td>
			<td>'.$values['3v2']['attack'].'</td>
			<td>'.($values['1v2']['attack'] + $values['2v2']['attack'] + $values['3v2']['attack']).'</td>
		</tr>
		<tr>
			<th>Defend</th>
			<td>'.$values['1v2']['defend'].'</td>
			<td>'.$values['2v2']['defend'].'</td>
			<td>'.$values['3v2']['defend'].'</td>
			<td>'.($values['1v2']['defend'] + $values['2v2']['defend'] + $values['3v2']['defend']).'</td>
		</tr>
		<tr class="alt">
			<th>Both</th>
			<td class="lower"> -- </td>
			<td class="lower">'.$values['2v2']['both'].'</td>
			<td class="lower">'.$values['3v2']['both'].'</td>
			<td class="lower">'.($values['2v2']['both'] + $values['3v2']['both']).'</td>
		</tr>
		<tr>
			<th>Total</th>
			<td class="lower">'.$count['1v2'].'</td>
			<td class="lower">'.$count['2v2'].'</td>
			<td class="lower">'.$count['3v2'].'</td>
			<td class="lower">'.($count['1v2'] + $count['2v2'] + $count['3v2']).'</td>
		</tr>
		<tr class="alt">
			<th colspan="3" rowspan="1">Total</th>
			<td>'.($count['1v1'] + $count['1v2']).'</td>
			<td>'.($count['2v1'] + $count['2v2']).'</td>
			<td>'.($count['3v1'] + $count['3v2']).'</td>
			<th>'.$count['total'].'</th>
		</tr>
	</tbody>
</table>

<table class="dicetable">
	<caption>Dice Percentage Difference</caption>
	<thead>
		<tr>
			<th colspan="2" rowspan="2">&nbsp;</th>
			<th colspan="1" rowspan="2" class="header">Outcome</th>
			<th colspan="3" rowspan="1" class="header">Attack</th>
		</tr>
		<tr>
			<th>1</th>
			<th>2</th>
			<th>3</th>
		</tr>
	</thead>
	<tbody class="color">
		<tr>
			<th colspan="1" rowspan="5" class="header">Defend</th>
			<th colspan="1" rowspan="2">1</th>
			<th>Attack</th>
			<td>'.perc($actual['1v1']['attack'] - $theor['1v1']['attack']).'</td>
			<td>'.perc($actual['2v1']['attack'] - $theor['2v1']['attack']).'</td>
			<td>'.perc($actual['3v1']['attack'] - $theor['3v1']['attack']).'</td>
		</tr>
		<tr class="alt">
			<th class="lower">Defend</th>
			<td class="lower">'.perc($actual['1v1']['defend'] - $theor['1v1']['defend']).'</td>
			<td class="lower">'.perc($actual['2v1']['defend'] - $theor['2v1']['defend']).'</td>
			<td class="lower">'.perc($actual['3v1']['defend'] - $theor['3v1']['defend']).'</td>
		</tr>
		<tr>
			<th colspan="1" rowspan="3">2</th>
			<th>Attack</th>
			<td>'.perc($actual['1v2']['attack'] - $theor['1v2']['attack']).'</td>
			<td>'.perc($actual['2v2']['attack'] - $theor['2v2']['attack']).'</td>
			<td>'.perc($actual['3v2']['attack'] - $theor['3v2']['attack']).'</td>
		</tr>
		<tr class="alt">
			<th>Defend</th>
			<td>'.perc($actual['1v2']['defend'] - $theor['1v2']['defend']).'</td>
			<td>'.perc($actual['2v2']['defend'] - $theor['2v2']['defend']).'</td>
			<td>'.perc($actual['3v2']['defend'] - $theor['3v2']['defend']).'</td>
		</tr>
		<tr>
			<th>Both</th>
			<td> -- </td>
			<td>'.perc($actual['2v2']['both'] - $theor['2v2']['both']).'</td>
			<td>'.perc($actual['3v2']['both'] - $theor['3v2']['both']).'</td>
		</tr>
	</tbody>
</table>
';

// TODO: possibly add game stats ???

echo get_header($meta);
echo get_item($contents, $hints, $meta['title']);
call($GLOBALS);
echo get_footer( );

