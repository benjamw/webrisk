<?php

/** function call [dump] [debug]
 *		This function is for debugging only
 *		Outputs given var to screen
 *		or, if no var given, outputs stars to note position
 *
 * @param mixed optional var to output
 * @param bool optional bypass debug value and output anyway
 * @action ouptuts var to screen
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
		ob_start( );

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
			$contents = htmlentities(ob_get_contents( ));
		}
		else {
			$contents = ob_get_contents( );
		}

		ob_end_clean( );
	}

	$j = 0;
	$html = '';
	$debug_funcs = array('dump', 'debug');
	if ((bool) $show_from) {
		$called_from = debug_backtrace( );

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
			$called = $called_from[$j + 1]['class'].$called_from[$j + 1]['type'].$called_from[$j + 1]['function'].' called ';
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
			myRef = window.open('','debugWindow');
			myRef.document.write('\n\n<pre style="background:#FFF;color:#000;font-size:larger;">');
			myRef.document.write('<?php echo str_replace("'", "\'", str_replace("\n", "<br />", "{$html}{$contents}")); ?>');
			myRef.document.write('\n<hr /></pre>\n\n');
		</script>
	<?php }
}
function dump($var = 'Th&F=xUFucreSp2*ezAhe=ApuPR*$axe', $bypass = false, $show_from = true, $new_window = false, $error = false) { call($var, $bypass, $show_from, $new_window, $error); }
function debug($var = 'Th&F=xUFucreSp2*ezAhe=ApuPR*$axe', $bypass = true, $show_from = true, $new_window = false, $error = false) { call($var, $bypass, $show_from, $new_window, $error); }



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

	if (file_exists($class_file)) {
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
	call($_POST['token']);

	if (DEBUG) {
		return;
	}

	if ( ! isset($_SESSION['token']) || ! isset($_POST['token'])
		|| (0 !== strcmp($_SESSION['token'], $_POST['token'])))
	{
		die('Hacking attempt detected.<br /><br />If you have reached this page in error, please go back,<br />clear your cache, refresh the page, and try again.');
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

