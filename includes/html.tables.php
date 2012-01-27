<?php

// build table structure

/*

	this file contains a function that will build an html table given some basic
	information about that table, such as formatting, and data array

	the format array may be a little confusing at first, because the format
	layout has been overloaded a bit...

	a column consists of 2 elements (2 more optional) which are:
		- 'Header Text' - which gets printed in the table header as is (html allowed)
		- 'column data' - which can contain several included markups: (parse in the following order)
			- 'field_name' - where the data with key 'field_name' for this row gets inserted
			- 'contents [[[field_name]]] contents' - where the [[[field_name]]] will be replaced by the data
			- '###code to eval' - where the given code will be evaluated and output
			- 'contents' - where, after all the above, will be output as is
			NOTE: these types can be mixed together to form complex types, such as the following:
				'###substr("[[[message]]]", 0, 50)."..."' - note the quotes inside the function to wrap the data
		- 'sort_type' - OPTIONAL which denotes the sort type for this column
		- 'extra_html' - OPTIONAL which gets placed in the td tag as html

	there are two special cases for the format array
		- if 'Header Text' contains 'SPECIAL_CLASS', then the second row contains a
	snippet of code that should eval to true or false, this snippet of code can contain
	anything that is allowed in the 'column data' field above (but does not need the leading ###
	as it will always be eval'd).  if the code evals to true, the tr tag gets the class name contained
	in the third array element, and if false, gets the class name contained in the fourth element
	both the third and fourth elements can contain [[[field_name]]].

		- if 'Header Text' contains 'SPECIAL_HTML', then everything is the same as above, except
	that instead of getting a class, the tr tag gets the html contained in the third and fourth elements
	of the array, much like extra_html.

	the data array is simply a 2-dimensional array, where each row is the first array
	and each column is the second, as follows:

		$table_data = array(
			0 => array(
				'field_name1' => 'field data' ,
				'field_name2' => 'other field data' ,
			) ,
			1 => array(
				'field_name1' => 'second row field data' ,
				'field_name2' => 'second row other field data' ,
			) ,
		);

	an example of the format array:

		$table_format = array(
			array('SPECIAL_CLASS', 'eval [[[field_name]]] code to bool', 'true_class', 'false_class') , // optional
			array('SPECIAL_CLASS', 'eval [[[field_name]]] code to bool', 'other_true_class', 'other_false_class') , // optional
			array('SPECIAL_HTML', 'eval [[[field_name]]] code to bool', 'id="true_html"', 'id="false_html"') , // optional

			array('Header Text', 'field_name', 'sort_type', 'extra_html', 'count_field_name') ,
			array('Header Text', 'contents [[[field_name]]] contents') ,
			array('Header Text', '###code to [[[eval]]]') ,
		);

	the count_field_name is for columns that may have other data included, we can still get a total out of it
	for instance, a column may have the format like '<a href="read.php?id=[[[message_id]]]">[[[message_count]]]</a>'
	if we put 'message_count' in the count_field_name section, it will sum that field, instead of the link (which will error out)
	###eval code and [[[meta]]] vars are also acceptable


// special
array( TYPE, CODE, TRU, FALS)

// data
array( HEADER, FIELD, SORT, EXTRA, TOTAL)

*/

// data format columns
define('HEADER', 0);
define('FIELD', 1);
define('SORT', 2);
define('EXTRA', 3);
define('TOTAL', 4);

// special format columns
define('TYPE', 0);
define('CODE', 1);
define('TRU', 2);
define('FALS', 3);

// table_format is as seen above, table_data is a two dimensional array of data
// meta vars are:
// alt_class, caption, class, extra_html, init_sort_column, no_data, sortable, totals
// init_sort_column is an array of the format col => dir where dir is 0 for ASC and 1 for DESC
function get_table($table_format, $table_data, $meta = null)
{
	$meta_defaults = array(
		'alt_class' => 'alt',
		'caption' => '',
		'class' => 'datatable',
		'extra_html' => '',
		'init_sort_column' => null,
		'no_data' => 'There is no data',
		'sortable' => false,
		'totals' => false,
	);

	$opts = array_merge($meta_defaults, $meta);

	if ( ! is_array($table_data) || (0 == count($table_data))) {
		return $opts['no_data'];
	}

	$opts['caption'] = ('' != $opts['caption']) ? '<caption>'.$opts['caption'].'</caption>' : '';

	// start building the header
	$headhtml = '
			<thead>
			<tr>';

	$total_cols = array( );
	foreach ($table_format as $col) {
		// test for SPECIAL data first
		if ( ! is_array($col[TYPE]) && ('SPECIAL_' == substr($col[TYPE], 0, 8))) {
			${$col[TYPE]}[] = $col; // will be named either SPECIAL_CLASS or SPECIAL_HTML
		}
		else {
			$sort_types[] = (isset($col[SORT])) ? $col[SORT] : null;

			if ( ! is_array($col[HEADER])) {
				$headhtml .= '
				<th>'.$col[HEADER].'</th>';
			}
			else {
				$headhtml .= '
				<th title="'.$col[HEADER][1].'">'.$col[HEADER][0].'</th>';
			}

			// do some stuff for the totals row
			if ($opts['totals'] && isset($col[TOTAL])) {
				if ((false != $col[TOTAL]) && ('total' != strtolower($col[TOTAL]))) {
					// test if we have any [[[meta_vars]]]
					// and make a total entry for those matches if any VALID ones are found
					// if the code is eval'd, we'll do that when we put the total row on
					if (preg_match_all('/\\[\\[\\[(\\w+)\\]\\]\\]/i', $col[TOTAL], $matches, PREG_PATTERN_ORDER)) {
						foreach ($matches[1] as $match) {
							if (in_array($match, array_keys(reset($table_data))) && ! in_array($match, $total_cols)) {
								$total_cols[] = $match;
							}
						}
					}
					else {
						$total_cols[] = $col[TOTAL];
					}
				}
			}
		}
	}

	$total_cols = array_unique($total_cols);

	$headhtml .= '
			</tr>
			</thead>
			';

	// start building the body
	$bodyhtml = '<tbody>
			';

	// start placing the data in the table
	$i = 0;
	foreach ($table_data as $rkey => $row) {
		if ( ! is_array($row)) {
			continue;
		}

		// clear out previous data
		$classes = false;

		// run our special class code
		// this code adds a class (or not) to the table row based on field contents
		if (isset($SPECIAL_CLASS)) {
			foreach ($SPECIAL_CLASS as $SP_CLASS_USE) {
				$SP_CLASS_USE[CODE] = replace_meta($row, $SP_CLASS_USE[CODE]);

#				call('$do_it = (bool) ('.$SP_CLASS_USE[CODE].');');
				eval('$do_it = (bool) ('.$SP_CLASS_USE[CODE].');');

				if ($do_it && ! empty($SP_CLASS_USE[TRU])) {
					$classes[] = massage_data($row, $SP_CLASS_USE[TRU]);
				}

				if ( ! $do_it && ! empty($SP_CLASS_USE[FALS])) {
					$classes[] = massage_data($row, $SP_CLASS_USE[FALS]);
				}
			}
		}

		// run our special html code
		// this code adds html (or not) to the table row based on field contents
		$spec_html = '';
		if (isset($SPECIAL_HTML)) {
			foreach ($SPECIAL_HTML as $SP_HTML_USE) {
				foreach ($SP_HTML_USE as $key => $col) {
					$SP_HTML_USE[$key] = replace_meta($row, $col);
				}

#				call('$do_it = (bool) ('.$SP_HTML_USE[CODE].');');
				eval('$do_it = (bool) ('.$SP_HTML_USE[CODE].');');

				if ($do_it && ! empty($SP_HTML_USE[TRU])) {
					$spec_html .= ' '.massage_data($row, $SP_HTML_USE[TRU]);
				}

				if ( ! $do_it && ! empty($SP_HTML_USE[FALS])) {
					$spec_html .= ' '.massage_data($row, $SP_HTML_USE[FALS]);
				}
			}
		}

		if (0 == ($i % 2) && ! empty($opts['alt_class'])) {
			$classes[] = $opts['alt_class'];
		}

		$class = (is_array($classes)) ? ' class="'.implode(' ', $classes).'"' : '';

		$bodyhtml .= '<tr'.$class.$spec_html.'>';

		// don't just start outputting the data
		// output it in the order specified by the table_format
		foreach ($table_format as $ckey => $col) {
			if ( ! is_array($col)) {
				continue;
			}

			if ( ! is_array($col[TYPE]) && ('SPECIAL_' == substr($col[TYPE], 0, 8))) {
				continue;
			}

			$col[EXTRA] = (isset($col[EXTRA])) ? ' '.trim($col[EXTRA]) : '';

			$bodyhtml .= '
				<td'.$col[EXTRA].'>';
			if (is_null($col[FIELD])) {
				// we don't want to show anything in this column
				// do nothing
			}
			elseif (isset($row[$col[FIELD]])) {
				// we have normal data
				$bodyhtml .= $row[$col[FIELD]];
			}
			else {
				$bodyhtml .= massage_data($row, $col[FIELD]);
			}

			$bodyhtml .= '</td>';
		}

		// grab the totals
		if ($opts['totals'] && (0 != count($total_cols))) {
			foreach ($total_cols as $total_col) {
				if ('__total' == $total_col) {
					$totals[$total_col] = 'Total';
				}
				else {
					$totals[$total_col] += $row[$total_col];
				}
			}
		}

		$bodyhtml .= '
			</tr>';

		++$i;
	}

	$bodyhtml .= '
			</tbody>';

	// start building the footer
	if ($opts['totals'] && (0 != count($totals))) {
		$foothtml = '
			<tfoot>
			<tr>';

		foreach ($table_format as $ckey => $col) {
			if ( ! is_array($col)) {
				continue;
			}

			if ('SPECIAL_' == substr($col[TYPE], 0, 8)) {
				continue;
			}

			$foothtml .= '
				<td>';
			if (is_null($col[TOTAL])) {
				$foothtml .= '--';
			}
			elseif (isset($totals[$col[TOTAL]])) {
				// we have normal data
				$foothtml .= $totals[$col[TOTAL]];
			}
			else {
				$foothtml .= massage_data($totals, $col[TOTAL]);
			}

			$foothtml .= '</td>';
		}

		$foothtml .= '
			</tr>
			</tfoot>';
	}
	else {
		$foothtml = '';
	}

	// build the sortable script
	if ($opts['sortable']) {
		$table_id = get_table_id( );
		$opts['extra_html'] .= ' id="'.$table_id.'"';

		$sort_script = get_sort_script($table_id, $sort_types, $opts['alt_class'], $opts['init_sort_column']);
	}
	else {
		$sort_script = '';
	}

	$html = '
		<table class="'.$opts['class'].'" '.$opts['extra_html'].'>
			'.$opts['caption']
			.$headhtml
			.$foothtml
			.$bodyhtml.'
		</table>'
		.$sort_script;

	return $html;
}
function print_table($table_format, $table_data, $meta = null) {
	echo get_table($table_format, $table_data, $meta);
}


// sort_types can be a comma separated list or an array of sort types
function get_sort_script($table_id, $sort_types = '', $alt_class = 'alt', $init_sort_column = null)
{
	if ( ! is_array($init_sort_column) || (0 == count($init_sort_column))) {
		$init_sort_column = null;
	}

	$html = '
		<script type="text/javascript">//<![CDATA[
			$(document).ready(function( ) {';

	if ( ! is_null($init_sort_column)) {
		$html .= '
				var c = [';

		$sort = '';
		foreach ($init_sort_column as $col => $dir) {
			$sort .= '['.$col.','.$dir.'],';
		}

		$html .= substr($sort, 0, -1).']
				';
	}

	$html .='
				$("#'.$table_id.'").tablesorter({
					textExtraction: "complex",
					widgets: ["zebra"],
					widgetZebra: {css: ["","'.$alt_class.'"]},
					headers: {';

	if ('' != $sort_types) {
		array_trim($sort_types);

		$keys = array_keys($sort_types);
		$last_key = end($keys);
		foreach ($sort_types as $key => $type) {
			if ('' !== $type) {
				if ('false' != $type) {
					$type = "'{$type}'";
				}

				$html .= '
						'.$key.': { sorter: '.$type.' }';

				if ($last_key != $key){
					$html .= ',';
				}
			}
		}
	}

	$html .= '
					}
				});';

	if ( ! is_null($init_sort_column)) {
		$html .= '
				$("#'.$table_id.'").trigger("sorton",[c]);';
	}

	$html .= '
			});
		//]]></script>';

	return $html;
}
function print_sort_script($table_id, $sort_types = '', $alt_class = 'alt', $init_sort_column = null) {
	echo get_sort_script($table_id, $sort_types, $alt_class, $init_sort_column);
}


function get_table_id($length = 8) {
	--$length;
	return 't' . substr(md5(substr(md5(uniqid(rand( ), true)), rand(0, 25), 7)), rand(0, (32 - $length)), $length);
}
function print_table_id($length = 8) {
	echo get_table_id($length);
}


function replace_meta($row, $data) {
	if (preg_match_all('/\\[\\[\\[(\\w+)\\]\\]\\]/i', $data, $matches, PREG_PATTERN_ORDER)) {
		foreach ($matches[1] as $match) {
			if (in_array($match, array_keys($row))) {
				// clean up the data coming from the database, so we don't get more [[[meta]]] and ###code things
				$row[$match] = htmlentities($row[$match], ENT_QUOTES, 'ISO-8859-1', false); // do this first, so we don't convert the &'s below
				$row[$match] = str_replace('###', '&#35;&#35;&#35;', $row[$match]);
				$row[$match] = str_replace('[', '&#91;', $row[$match]);
				$row[$match] = str_replace(']', '&#93;', $row[$match]);
				$data = str_replace("[[[{$match}]]]", $row[$match], $data);
			}
		}

		$data = trim($data);
	}

	return $data;
}


function massage_data($row, $data) {
	$col_data = replace_meta($row, $data);

	// test for '###eval code;'
	if ('###' == substr($col_data, 0, 3)) {
#		call('$col_data = '.substr($col_data, 3).';');
		eval('$col_data = '.substr($col_data, 3).';');
#		call($col_data);
	}

	return $col_data;
}


if ( ! function_exists('my_empty')) {
	function my_empty($val = null) {
		return empty($val);
	}
}


if ( ! function_exists('ifsetor')) {
	function ifsetor(& $param, $or) {
		if ( ! isset($param)) {
			$param = $or;
		}

		return $param;
	}
}


if ( ! function_exists('ifemptyor')) {
	function ifemptyor(& $param, $or) {
		if (empty($param)) {
			$param = $or;
		}

		return $param;
	}
}


if ( ! function_exists('ifenr')) {
	// if-else non-reference
	function ifenr($param, $or = null) {
		if (empty($param)) {
			return $or;
		}

		return $param;
	}
}


if ( ! function_exists('ifdateor')) {
	function ifdateor($date_format, & $if, $or) {
		$date = (isset($if) && is_int($if)) ? $if : $or;

		if (is_int($date)) {
			$date = date($date_format, $date);
		}

		return $date;
	}
}


if ( ! function_exists('strmaxlen')) {
	function strmaxlen($string, $length) {
		if (strlen($string) > $length) {
			return substr($string, 0, ($length - 3)).'...';
		}

		return $string;
	}
}

