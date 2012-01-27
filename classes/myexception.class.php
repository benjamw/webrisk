<?php
/*
+---------------------------------------------------------------------------
|
|   myexception.class.php (php 5.x)
|
|   by Benjam Welker
|   http://iohelix.net
|
+---------------------------------------------------------------------------
|
|   > PHP Exception Extension module
|   > Date started: 2008-03-09
|
|   > Module Version Number: 1.0.0
|
+---------------------------------------------------------------------------
*/


class MyException
	extends Exception {

	/**
	 *		PROPERTIES
	 * * * * * * * * * * * * * * * * * * * * * * * * * * */

	/** private property _backtrace
	 *		should we show a backtrace in the log
	 *
	 * @param bool
	 */
	private $_backtrace = true;


	/**
	 *		METHODS
	 * * * * * * * * * * * * * * * * * * * * * * * * * * */

	/** public function __construct
	 *		Class constructor
	 *		Sets all outside data
	 *
	 * @param string error message
	 * @param int optional error code
	 * @action instantiates object
	 * @action writes the exception to the log
	 * @return void
	 */
	public function __construct($message = '', $code = 0)
	{
		$message = (string) $message;
		$code = (float) $code;

		parent::__construct($message, $code);

		// our own exception handling stuff
		if ( ! empty($GLOBALS['_LOGGING'])) {
			$this->_write_error( );
		}
	}


	/** public function outputMessage
	 *		cleans the message for use
	 *
	 * @param void
	 * @return cleaned message
	 */
	public function outputMessage( )
	{
		// strip off the __METHOD__ bit of the error, if possible
		$message = $this->message;
		$message = preg_replace('/(?:\\w+::)+\\w+:\\s+/', '', $message);

		return $message;
	}


	/** protected function _write_error
	 *		writes the exception to the log file
	 *
	 * @param void
	 * @action writes the exception to the log
	 * @return void
	 */
	protected function _write_error( )
	{
		// first, lets make sure we can actually open and write to directory
		// specified by the global variable... and lets also do daily logs for now
		$log_name = 'exception_'.date('Ymd', time( )).'.log';

		// okay, write our log message
		$str = date('Y/m/d H:i:s')." == ({$this->code}) {$this->message} : {$this->file} @ {$this->line}\n";

		if ($this->_backtrace) {
			$str .= "---------- [ BACKTRACE ] ----------\n";
			$str .= $this->getTraceAsString( )."\n";
			$str .= "-------- [ END BACKTRACE ] --------\n\n";
		}

		if ($fp = @fopen(LOG_DIR.$log_name, 'a')) {
			fwrite($fp, $str);
			fclose($fp);
		}

		call($str, $bypass = false, $show_from = true, $new_window = false, $error = true);
	}

}


/* PHP's built in Exception Class Reference ----

class Exception   {
	protected string $message ;
	protected int $code ;
	protected string $file ;
	protected int $line ;

	public __construct ([ string $message = "" [, int $code = 0 [, Exception $previous = NULL ]]] )

	final public string getMessage ( void )
	final public Exception getPrevious ( void )
	final public int getCode ( void )
	final public string getFile ( void )
	final public int getLine ( void )
	final public array getTrace ( void )
	final public string getTraceAsString ( void )
	final private void __clone ( void )

	public string __toString ( void )
}

*/

