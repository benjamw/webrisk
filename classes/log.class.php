<?php
/*
+---------------------------------------------------------------------------
|
|   log.class.php (php 5.x)
|
|   by Benjam Welker
|   http://iohelix.net
|
+---------------------------------------------------------------------------
|
|   > Logging module
|   > Date started: 2009-04-15
|
|   > Module Version Number: 0.8.0
|
+---------------------------------------------------------------------------
*/


class Log
{

	/**
	 *		PROPERTIES
	 * * * * * * * * * * * * * * * * * * * * * * * * * * */

	/** public property backtrace
	 *		Should we show the backtrace or not
	 *
	 * @var bool backtrace flag
	 */
	public $backtrace = true;


	/** public property type
	 *		The type of log we are running
	 *
	 * @var string log type
	 */
	public $type = 'error';


	/** protected property _file
	 *		Stores the location of the log file
	 *		(with trailing slash)
	 *
	 * @param string
	 */
	protected $_file_path = './';


	/** static private property _instance
	 *		Holds the instance of this object
	 *
	 * @var Log object
	 */
	static private $_instance;



	/**
	 *		METHODS
	 * * * * * * * * * * * * * * * * * * * * * * * * * * */

	/** protected function __construct
	 *		Class constructor
	 *
	 * @param void
	 * @action instantiates object
	 * @return void
	 */
	protected function __construct( )
	{
		if (defined('LOG_DIR')) {
			$this->_file_path = LOG_DIR;
		}
	}


	/** public function __get
	 *		Class getter
	 *		Returns the requested property if the
	 *		requested property is not _private
	 *
	 * @param string property name
	 * @return mixed property value
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
	 * @param string property name
	 * @param mixed property value
	 * @action optional validation
	 * @return bool success
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


	/** protected function _get_backtrace_string
	 *		Emulates PHP's Exception::getTraceAsString( )
	 *
	 * @param void
	 * @return string backtrace string
	 */
	protected function _get_backtrace_string( )
	{
		$trace = debug_backtrace(false);

		$root_dir = (defined('ROOT_DIR')) ? ROOT_DIR : '';

		// ignore the first two entries of the backtrace array
		// (this method, and the self::write method)
		$output = '';
		for ($i = 2, $count = count($trace); $i < $count; ++$i) {
			$index = $i - 2;
			$cur = $trace[$i];

			$file = '';
			if ( ! empty($cur['file'])) {
				$file = ' '.DIRECTORY_SEPARATOR.str_replace($root_dir, '', $cur['file']);
			}

			$line = '';
			if ( ! empty($cur['line'])) {
				$line = ' ('.$cur['line'].')';
			}

			$func = $cur['function'];
			if ( ! empty($cur['class'])) {
				$func = $cur['class'].$cur['type'].$func;
			}

			$args = ' ';
			if ( ! empty($cur['args'])) {
				$args = var_export($cur['args'], true);
				$args = preg_replace('/\r?\n/', "\n\t\t\t\t", $args);
			}

			$output .= "\t\t\t#{$index}{$file}{$line}: {$func}({$args})\n";
		}

		return substr($output, 0, -1);
	}


	/** static public function get_instance
	 *		Returns the singleton instance
	 *		of the Log Object as a reference
	 *
	 * @param void
	 * @action optionally creates the instance
	 * @return Log Object reference
	 */
	static public function get_instance( )
	{
		if (is_null(self::$_instance)) {
			self::$_instance = new Log( );
		}

		return self::$_instance;
	}


	/** static public function write
	 *		Save a message to be logged to the file
	 *		If no message supplied, output backtrace
	 *
	 * @param string optional message text
	 * @param string optional log type (filename)
	 * @param bool optional include backtrace info
	 * @return void
	 */
	static public function write($message = '', $type = null, $backtrace = null)
	{
		if (empty($GLOBALS['_LOGGING'])) {
			return;
		}

		if (is_array($message)) {
			extract(array_merge(array('type' => null, 'backtrace' => null), $message));
		}

		$_this = self::get_instance( );

		if (is_null($type)) {
			$type = $_this->type;
		}

		if (is_null($backtrace)) {
			$backtrace = $_this->backtrace;
		}

		if (empty($message)) {
			$backtrace = true;
		}

		if (empty($type)) {
			$type = 'error';
		}

		call(__METHOD__);
		call($message);
		call($type);
		call($backtrace);

		// precede newlines in the message with tabs for indentation
		$message = preg_replace('/\r?\n/', "\n\t\t", $message);

		$msg = date('Y-m-d H:i:s').'- '.$message."\n";

		if ($backtrace) {
			$msg .= "\t\t---------- [ BACKTRACE ] ----------\n";
			$msg .= $_this->_get_backtrace_string( )."\n";
			$msg .= "\t\t-------- [ END BACKTRACE ] --------\n\n";
		}

		if ($fp = @fopen($_this->_file_path.$type.'_'.date('Ymd').'.log', 'a')) {
			fwrite($fp, $msg);
			fclose($fp);
		}
	}

} // end of Log class

