<?php

if ( ! function_exists('call')) {
	/** function call [dump] [debug]
	 *        This function is for debugging only
	 *        Outputs given var to screen
	 *        or, if no var given, outputs stars to note position
	 *
	 * @param mixed optional var to output
	 * @param bool  optional bypass debug value and output anyway
	 *
	 * @action outputs var to screen
	 * @return void
	 */
	function call($var = 'Th&F=xUFucreSp2*ezAhe=ApuPR*$axe', $bypass = false, $show_from = true, $new_window = false, $error = false)
	{
		if ((( ! defined('DEBUG') || ! DEBUG) || ! empty($GLOBALS['NODEBUG'])) && ! (bool) $bypass) {
			return false;
		}

		if ('Th&F=xUFucreSp2*ezAhe=ApuPR*$axe' === $var) {
			$contents = '<span style="font-size:larger;font-weight:bold;color:red;">--==((OO88OO))==--</span>';
		}
		else {
			// begin output buffering so we can escape any html
			// and print_r is better at catching recursion than var_export
			ob_start();

			if ((is_string($var) && ! preg_match('/^\\s*$/', $var))) { // non-whitespace strings
				print_r($var);
			}
			else {
				if ( ! function_exists('xdebug_disable')) {
					if (is_array($var) || is_object($var)) {
						print_r($var);
					}
					else {
						var_dump($var);
					}
				}
				else {
					var_dump($var);
				}
			}

			// end output buffering and output the result
			if ( ! function_exists('xdebug_disable')) {
				$contents = htmlentities(ob_get_contents());
			}
			else {
				$contents = ob_get_contents();
			}

			ob_end_clean();
		}

		$j = 0;
		$html = '';
		$debug_funcs = ['dump', 'debug'];
		if ((bool) $show_from) {
			$called_from = debug_backtrace();

			if (isset($called_from[$j + 1]) && in_array($called_from[$j + 1]['function'], $debug_funcs)) {
				++$j;
			}

			$file0 = substr($called_from[$j]['file'], strlen($_SERVER['DOCUMENT_ROOT']));
			$line0 = $called_from[$j]['line'];

			$called = '';
			if (isset($called_from[$j + 1]['file'])) {
				$file1 = substr($called_from[$j + 1]['file'], strlen($_SERVER['DOCUMENT_ROOT']));
				$line1 = $called_from[$j + 1]['line'];
				$called = "{$file1} : {$line1} called ";
			}
			elseif (isset($called_from[$j + 1])) {
				$called = $called_from[$j + 1]['class'] . $called_from[$j + 1]['type'] . $called_from[$j + 1]['function'] . ' called ';
			}

			$html = "<strong>{$called}{$file0} : {$line0}</strong>\n";
		}

		if ( ! $new_window) {
			$color = '#000';
			if ($error) {
				$color = '#F00';
			}

			echo "\n\n<pre style=\"background:#FFF;color:{$color};font-size:larger;\">{$html}{$contents}\n<hr /></pre>\n\n";
		}
		else { ?>
			<script language="javascript">
				myRef = window.open('', 'debugWindow');
				myRef.document.write('\n\n<pre style="background:#FFF;color:#000;font-size:larger;">');
				myRef.document.write('<?php echo str_replace("'", "\'", str_replace("\n", "<br />", "{$html}{$contents}")); ?>');
				myRef.document.write('\n<hr /></pre>\n\n');
			</script>
		<?php }
	}

	function dump($var = 'Th&F=xUFucreSp2*ezAhe=ApuPR*$axe', $bypass = false, $show_from = true, $new_window = false, $error = false) { call($var, $bypass, $show_from, $new_window, $error); }
	function debug($var = 'Th&F=xUFucreSp2*ezAhe=ApuPR*$axe', $bypass = true, $show_from = true, $new_window = false, $error = false) { call($var, $bypass, $show_from, $new_window, $error); }
}


/** function load_class
 *		This function automagically loads the class
 *		via the spl_autoload_register function above
 *		as it is instantiated (jit).
 *
 * @param string class name
 * @action loads given class name if found
 * @return bool success
 */
function load_class($class_name) {
	$class_file = CLASSES_DIR.strtolower($class_name).'.class.php';

	if (class_exists($class_name)) {
		return true;
	}
	elseif (file_exists($class_file) && is_readable($class_file)) {
		require_once $class_file;
		return true;
	}
	else {
		throw new MyException(__FUNCTION__.': Class file ('.$class_file.') not found');
	}
}



/** function test_token
 *		This function tests the token given by the
 *		form and checks it against the session token
 *		and if they do not match, dies
 *
 * @param bool optional keep original token flag
 * @action tests tokens and dies if bad
 * @action optionally renews the session token
 * @return void
 */
function test_token($keep = false) {
	call($_SESSION['token']);
	call($_REQUEST['token']);

	if (DEBUG || ('games' == $_SERVER['HTTP_HOST']) || empty($GLOBALS['_DETECTHACKS'])) {
		return;
	}

	if ( ! isset($_SESSION['token']) || ! isset($_REQUEST['token'])
		|| (0 !== strcmp($_SESSION['token'], $_REQUEST['token'])))
	{
		die('Invalid request attempt detected.<br /><br />If you have reached this page in error, please go back,<br />clear your cache, refresh the page, and try again.');
	}

	// renew the token
	if ( ! $keep) {
		$_SESSION['token'] = md5(uniqid(rand( ), true));
	}
}



/** function test_debug
 *		This function tests the debug given by the
 *		URL and checks it against the globals debug password
 *		and if they do not match, doesn't debug
 *
 * @param void
 * @action tests debug pass
 * @return bool success
 */
function test_debug( ) {
	if ( ! isset($_GET['DEBUG'])) {
		return false;
	}

	if ( ! class_exists('Settings') || ! Settings::test( )) {
		return false;
	}

	if ('' == trim(Settings::read('debug_pass'))) {
		return false;
	}

	if (0 !== strcmp($_GET['DEBUG'], Settings::read('debug_pass'))) {
		return false;
	}

	$GLOBALS['_&_DEBUG_QUERY'] = '&DEBUG='.$_GET['DEBUG'];
	$GLOBALS['_?_DEBUG_QUERY'] = '?DEBUG='.$_GET['DEBUG'];
	return true;
}



/** function expandFEN
 *		This function expands a packed FEN into a
 *		string where each index is a valid location
 *
 * @param string packed FEN
 * @return string expanded FEN
 */
function expandFEN($FEN)
{
	$FEN = preg_replace('/\s+/', '', $FEN); // remove spaces
	$FEN = preg_replace_callback('/\d+/', 'expand_replace_callback', $FEN); // unpack the 0s
	$xFEN = str_replace('/', '', $FEN); // remove the row separators

	return $xFEN;
}
function expand_replace_callback($match) {
	return (((int) $match[0]) ? str_repeat('0', (int) $match[0]) : $match[0]);
}



/** function packFEN
 *		This function packs an expanded FEN into a
 *		string that takes up less space
 *
 * @param string expanded FEN
 * @param int [optional] length of rows
 * @return string packed FEN
 */
function packFEN($xFEN, $row_length = 10)
{
	$xFEN = preg_replace('/\s+/', '', $xFEN); // remove spaces
	$xFEN = preg_replace('/\//', '', $xFEN); // remove any row separators
	$xFEN = trim(chunk_split($xFEN, $row_length, '/'), '/'); // add the row separators
	$FEN = preg_replace_callback('/(0+)/', 'pack_replace_callback', $xFEN); // pack the 0s

	return $FEN;
}
function pack_replace_callback($match) {
	return strlen($match[1]);
}



/** function get_index
 *
 *	Gets the FEN string index for a 2D location
 *	of a square array of blocks each containing
 *	a square array of elements within those blocks
 *
 *	This was designed for use within foreach structures
 *	The first foreach ($i) is the outer blocks, and the
 *	second ($j)	is for the inner elements.
 *
 *	Example Structure:
 *		+----+----+----++----+----+----+
 *		|  0 |  1 |  2 ||  3 |  4 |  5 |
 *		+----+----+----++----+----+----+
 *		|  6 |  7 |  8 ||  9 | 10 | 11 |
 *		+----+----+----++----+----+----+
 *		| 12 | 13 | 14 || 15 | 16 | 17 |
 *		+====+====+====++====+====+====+
 *		| 18 | 19 | 20 || 21 | 22 | 23 |
 *		+----+----+----++----+----+----+
 *		| 24 | 25 | 26 || 27 | 28 | 29 |
 *		+----+----+----++----+----+----+
 *		| 30 | 31 | 32 || 33 | 34 | 35 |
 *		+----+----+----++----+----+----+
 *
 *	Where $i = 2 (bottom left big block)
 *	  and $j = 5 (center element)
 *	 $blocks = 2 (number of blocks per side)
 *	  $elems = 3 (numer of elements per side in each block)
 *	will return 25 (the index of the string)
 *
 * @param int the current block number
 * @param int the current element number
 * @param int the number of blocks per side
 * @param int the number of elements per side per block
 * @return int the FEN string index
 */
function get_index($i, $j, $blocks = 3, $elems = 3) {
	$bits = [
		($j % $elems), // across within block (across elems)
		((int) floor($j / $elems) * $blocks * $elems), // down within block (down elems)
		(($i % $blocks) * $elems), // across blocks
		((int) floor($i / $blocks) * $blocks * $elems * $elems), // down blocks
  ];

	return array_sum($bits);
}



/** function ife
 *		if-else
 *		This function returns the value if it exists (or is optionally not empty)
 *		or a default value if it does not exist (or is empty)
 *
 * @param mixed var to test
 * @param mixed optional default value
 * @param bool optional allow empty value
 * @param bool optional change the passed reference var
 * @return mixed $var if exists (and not empty) or default otherwise
 */
function ife( & $var, $default = null, $allow_empty = true, $change_reference = false) {
	if ( ! isset($var) || ( ! (bool) $allow_empty && empty($var))) {
		if ((bool) $change_reference) {
			$var = $default; // so it can also be used by reference
		}

		return $default;
	}

	return $var;
}



/** function ifer
 *		if-else reference
 *		This function returns the value if it exists (or is optionally not empty)
 *		or a default value if it does not exist (or is empty)
 *		It also changes the reference var
 *
 * @param mixed var to test
 * @param mixed optional default value
 * @param bool optional allow empty value
 * @action updates/sets the reference var if needed
 * @return mixed $var if exists (and not empty) or default otherwise
 */
function ifer( & $var, $default = null, $allow_empty = true) {
	return ife($var, $default, $allow_empty, true);
}



/** function ifenr
 *		if-else non-reference
 *		This function returns the value if it is not empty
 *		or a default value if it is empty
 *
 * @param mixed var to test
 * @param mixed optional default value
 * @return mixed $var if not empty or default otherwise
 */
function ifenr($var, $default = null) {
	if (empty($var)) {
		return $default;
	}

	return $var;
}


/**
 * Local formatting of dates using requested timezone
 *
 * @param string $format
 * @param int $timestamp optional defaults to time( )
 *
 * @return bool|string
 */
function ldate($format, $timestamp = null) {
	if (1 === func_num_args( )) {
		$timestamp = time( );
	}

	if ( ! is_numeric($timestamp)) {
		$timestamp = strtotime($timestamp);
	}

	date_default_timezone_set($GLOBALS['_TZ']);
	$date = date($format, $timestamp);
	date_default_timezone_set('UTC');

	return $date;
}


/**
 * Update old serialized arrays to use JSON encoding
 *
 * @param string $extra_info reference
 */
function fix_extra_info(& $extra_info) {
	if ( ! empty($extra_info) && is_string($extra_info) && ('a' === $extra_info[0])) {
		$extra_info = unserialize($extra_info);
		$extra_info = json_encode($extra_info);
	}
}
