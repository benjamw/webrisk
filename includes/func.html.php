<?php

/**
 *		HTML FUNCTIONS
 * * * * * * * * * * * * * * * * * * * * * * * * * * */


/** function is_checked [isChecked]
 *		Checks the value for a 'true' value
 *		and returns true if found
 *
 *		Good for use in database inserts
 *		$data['field'] = is_checked($_POST['field']);
 *
 * @param string field value
 * @return bool checked
 */
function is_checked( & $value) {
	if (empty($value)) {
		return false;
	}

	switch (strtolower($value)) {
		case 'checked':
		case 'true':
		case 'yes':
		case 'on':
		case 'y':
		case '1':
			return true;
			break;
	}

	return false;
}
function isChecked($value) { return is_checked($value); }


/** function get_selected [getSelected] [printable]
 *		Checks the two given values to see if
 *		they are the same and returns the HTML input
 *		attribute to either check the box, or select
 *		the option
 *
 *		Good for prepopulating an html form
 *		<input type="checkbox" name="foo" value="bar" <?php print_selected('bar', $_POST['foo'], false); ?> />
 *
 * @param mixed value one
 * @param mixed value two
 * @param bool select box
 * @return string html attribute
 */
function get_selected($one, $two, $selected = true) {
	if ( ($one === (int) $two) || ($one === (string) $two) || ($one === $two) ) {
		if ( $selected ) {
			return ' selected="selected" ';
		} else {
			return ' checked="checked" ';
		}
	} else {
		return ' ';
	}
}
function getSelected($one, $two, $selected = true) { return get_selected($one, $two, $selected); }
function print_selected($one, $two, $selected = true) { echo get_selected($one, $two, $selected); }
function printSelected($one, $two, $selected = true) { echo get_selected($one, $two, $selected); }


/** function get_selected_bitwise [getSelectedBitwise] [printable]
 *		Checks the two given values to see if they
 *		have a common bit and returns the HTML input
 *		attribute to either check the box, or select
 *		the option
 *
 *		Good for prepopulating an html form
 *		<input type="checkbox" name="foo" value="2" <?php print_selected_bitwise(2, $_POST['foo'], false); ?> />
 *
 * @param int value one
 * @param int value two
 * @param bool select box
 * @return string html attribute
 */
function get_selected_bitwise($one, $two, $selected = true) {
	if (0 != ($one & $two)) {
		if ( $selected ) {
			return ' selected="selected" ';
		} else {
			return ' checked="checked" ';
		}
	} else {
		return ' ';
	}
}
function getSelectedBitwise($one, $two, $selected = true) { return get_selected_bitwise($one, $two, $selected); }
function print_selected_bitwise($one, $two, $selected = true) { echo get_selected_bitwise($one, $two, $selected); }
function printSelectedBitwise($one, $two, $selected = true) { echo get_selected_bitwise($one, $two, $selected); }


/** function perc
 *		Returns a number formatted as a percentage
 *
 * @param float value (should be less than 1)
 * @param int number of decimal digits to show
 * @return string formatted percentage
 */
function perc($num, $digits = 2) {
	return number_format($num * 100, $digits).' %';
}


/** function humanize
 *		Returns a variable string as human readable
 *
 * @param string variable string
 * @return string formatted as human readable
 */
function humanize($string) {
	// convert camelCase to under_scored
	$string = strtolower(preg_replace('/([A-Z])/', '_$1', $string));

	// convert under_scored to Human Readable
	$string = ucwords(trim(preg_replace('/_+/', ' ', $string)));

	return $string;
}


/** function plural
 *		Returns a plural version of a string if needed
 *
 * @param int number of items
 * @param string singular version of item name
 * @param string plural version of item name
 * @return string formatted as needed
 */
function plural($items, $singular, $plural = null) {
	if (is_null($plural)) {
		// if there are lowercase letters in the singular string
		if (preg_match('/[a-z]/', $singular)) {
			$plural = $singular.'s';
		}
		else {
			$plural = $singular.'S';
		}
	}

	return (1 == abs($items)) ? $singular : $plural;
}


/** function singular
 *		Returns a singular version of a string if needed
 *		basically removes a trailing "s" if found
 *
 * @param string input string
 * @return string formatted as needed
 */
function singular($string) {
	if ('s' == strtolower($string[strlen($string) - 1])) {
		$string = substr($string, 0, -1);
	}

	return $string;
}


/** function human
 *		Returns a human version of a string if needed
 *
 * @param string input string
 * @return string with _ replaced with space
 */
function human($string) {
	return str_replace('_', ' ', $string);
}

