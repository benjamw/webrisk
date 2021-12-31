<?php

/**
 *		ARRAY FUNCTIONS
 * * * * * * * * * * * * * * * * * * * * * * * * * * */


/** function array_trim [arrayTrim]
 *		Performs the trim function recursively on
 *		every element in an array and optionally typecasts
 *		the element to the given type
 *
 * @param mixed csv list or array by reference
 * @param string optional typecast type
 * @return array
 */
function array_trim( & $array, $type = null)
{
	$types = [
		'int' , 'integer' ,
		'bool' , 'boolean' ,
		'float' , 'double' ,
		'string' ,
		'array' ,
		'object' ,
		'null' ,
	];

	// if a non-empty string value comes through, don't erase it
	// this is specifically for '0', but may work for others
	$is_non_empty_string = (is_string($array) && strlen(trim($array)));
	if ( ! $array && ! $is_non_empty_string) {
		$array = [];
	}

	if ( ! in_array($type, $types)) {
		$type = null;
	}

	if ( ! is_array($array)) {
		$array = explode(',', $array);
	}

	if ( ! is_null($type)) {
		array_walk_recursive($array, function(& $v) use ($type) { $v = trim($v); settype($v, $type); });
	}
	else {
		array_walk_recursive($array, function(& $v) { $v = trim($v); });
	}

	return $array; // returns by reference as well
}
function arrayTrim( & $array, $type = null) { return array_trim($array, $type); }


/** function array_clean [arrayClean]
 *		Strips out the unnecessary bits from an array
 *		so it can be input into a database, or to just
 *		generally clean an array of fluff
 *
 * @param array $array data array to be cleaned
 * @param mixed $keys csv or array of allowed keys
 * @param mixed $required optional csv or array of required keys
 *
 * @return array
 * @throws MyException
 */
function array_clean($array, $keys, $required = [])
{
	if ( ! is_array($array)) {
		return [];
	}

	array_trim($keys);
	array_trim($required);

	$keys = array_unique(array_merge($keys, $required));

	if (empty($keys)) {
		return [];
	}

	$return = [];
	foreach ($keys as $key) {
		if (in_array($key, $required) && (empty($array[$key]))) {
			throw new MyException(__FUNCTION__.': Required element ('.$key.') missing');
		}

		if (isset($array[$key])) {
			$return[$key] = $array[$key];
		}
	}

	return $return;
}
function arrayClean($array, $keys, $required = []) { return array_clean($array, $keys, $required); }


/** function array_transpose [arrayTranspose]
 *		Transposes a 2-D array
 *		array[i][j] becomes array[j][i]
 *
 *		Not to be confused with the PHP function array_flip
 *
 * @param array $array
 *
 * @return mixed array (or bool false on failure)
 * @throws MyException
 */
function array_transpose($array)
{
	if ( ! is_array($array)) {
		throw new MyException(__FUNCTION__.': Data given was not an array');
	}

	$return = [];
	foreach ($array as $key1 => $value1) {
		if ( ! is_array($value1)) {
			continue;
		}

		foreach ($value1 as $key2 => $value2) {
			$return[$key2][$key1] = $value2;
		}
	}

	if (0 == count($return)) {
		return false;
	}

	return $return;
}
function arrayTranspose($array) { return array_transpose($array); }


/** function array_shrink [arrayShrink]
 *		Returns all elements with second level key $key
 *		from a 2-D array
 *		e.g.-
 *			$array[0]['foo'] = 'bar';
 *			$array[1]['foo'] = 'baz';
 *
 *		array_shrink($array, 'foo') returns
 *			array(
 *				[0] = 'bar'
 *				[1] = 'baz'
 *			)
 *
 *		This function returns the input if it is not
 *		an array, or returns false if the key is not
 *		present in the array
 *
 * @param array data array
 * @param mixed second level key
 * @return mixed array (or original input or bool false on failure)
 */
function array_shrink($array, $key)
{
	if ( ! is_array($array)) {
		return $array;
	}

	$array = array_transpose($array);

	if ( ! isset($array[$key])) {
		return false;
	}

	return $array[$key];
}
function arrayShrink($array, $key) { return array_shrink($array, $key); }


/** function array_sum_field [arraySumField]
 *		Returns the sum of all elements with key $key
 *		from a 2-D array, no matter if $key is a first level
 *		or second level key
 *
 * @param array
 * @param mixed element key
 * @return mixed float/int total (or bool false on failure)
 */
function array_sum_field($array, $key)
{
	if ( ! is_array($array)) {
		return false;
	}

	$total = 0;

	if (isset($array[$key]) && is_array($array[$key])) {
		$total = array_sum($array[$key]);
	}
	else {
		foreach ($array as $row) {
			if (is_array($row) && isset($row[$key])) {
				$total += $row[$key];
			}
		}
	}

	return $total;
}
function arraySumField($array, $key) { return array_sum_field($array, $key); }


/** function implode_full [implodeFull]
 *		Much like implode, but including the keys with an
 *		extra divider between key-value pairs
 *		Can be used to create URL GET strings from arrays
 *
 * @param string separator between elements (for URL GET, use '&')
 * @param string divider between key-value pairs (for URL GET, use '=')
 * @param array
 * @param bool optional URL encode flag
 * @return string
 */
function implode_full($separator, $divider, $array, $url = false)
{
	if ( ! is_array($array) || (0 == count($array))) {
		return $array;
	}

	$str = '';
	foreach ($array as $key => $val) {
		$str .= $key.$divider.$val.$separator;
	}

	$str = substr($str, 0, -(strlen($separator)));

	if ($url) {
		$str = url_encode($str);
	}

	return $str;
}
function implodeFull($separator, $divider, $array, $url = false) { return implode_full($separator, $divider, $array, $url); }


/** function explode_full [explodeFull]
 *		Much like explode, but including the keys with an
 *		extra divider between key-value pairs
 *		Can be used to create arrays from URL GET strings
 *
 * @param string separator between elements (for URL GET, use '&')
 * @param string divider between key-value pairs (for URL GET, use '=')
 * @param string
 * @param bool optional URL encode flag
 * @return array
 */
function explode_full($separator, $divider, $string, $url = false)
{
	// explode the string about the separator
	$first = explode($separator, $string);

	// now go through each element in the first array and explode each about the divider
	foreach ($first as $element) {
		list($key, $value) = explode($divider, $element);
		$array[$key] = $value;
	}

	return $array;
}
function explodeFull($separator, $divider, $string, $url = false) { return explode_full($separator, $divider, $string, $url); }


/** function kshuffle
 *		Exactly the same as shuffle except this function
 *		preserves the original keys of the array
 *
 * @param array the array to shuffle by reference
 * @return array
 */
function kshuffle( & $array)
{
	uasort($array, function($a, $b) { return rand(1, -1); });
}


/** function array_merge_plus
 *		Exactly the same as array_merge except this function
 *		allows entry of non-arrays without throwing errors
 *		If an empty argument is encountered, it removes it.
 *		If a non-empty, non-array value is encountered,
 *		it appends it to the array in the order received.
 *
 * @param mixed item to merge into array
 * @param ...
 *
 * @return array merged array
 */
function array_merge_plus($array1) {
	// grab the arguments of this function
	// and parse through them, removing any empty arguments
	// and converting non-empty, non-arrays to arrays
	$args = func_get_args( );
	foreach ($args as $key => $arg) {
		if ( ! is_array($arg)) {
			if (empty($arg)) {
				unset($args[$key]);
				continue;
			}
		}

		$args[$key] = (array) $arg;
	}

	$return = call_user_func_array('array_merge', $args);

	return $return;
}


/**
 * Runs array_diff recursively
 *
 * @param array $array1
 * @param array $array2
 *
 * @return array of elements in $array1 that are different from $array2
 */
function array_diff_recursive($array1, $array2) {
	$diff = [];

	foreach ($array1 as $key => $value) {
		if (array_key_exists($key, $array2)) {
			if (is_array($value)) {
				$recursive_diff = array_diff_recursive($value, $array2[$key]);

				if (count($recursive_diff)) {
					$diff[$key] = $recursive_diff;
				}
			}
			elseif ($value != $array2[$key]) { // change to !== to use exact match
				$diff[$key] = $value;
			}
		}
		else {
			$diff[$key] = $value;
		}
	}

	return $diff;
}
