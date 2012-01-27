<?php
/*
+---------------------------------------------------------------------------
|
|   flash.class.php (php 5.x)
|
|   by Benjam Welker
|   http://iohelix.net
|
+---------------------------------------------------------------------------
|
|   > Message flash module
|   > Date started: 2008-07-10
|
|   > Module Version Number: 1.0.0
|
+---------------------------------------------------------------------------
*/


class Flash
{

	/**
	 *		PROPERTIES
	 * * * * * * * * * * * * * * * * * * * * * * * * * * */

	/** protected property _messages
	 *		Stores the messages we want to see
	 *
	 * @param array
	 */
	protected $_messages = array( );


	/** protected property _location
	 *		Stores the location we need to get to
	 *		in case the script doing the flashing
	 *		is AJAX powered and doesn't actually
	 *		have browser control
	 *
	 * @param string (or bool false if none)
	 */
	protected $_location = false;


	/** protected property _DEBUG
	 *		Holds the DEBUG state for the class
	 *
	 * @var bool
	 */
	protected $_DEBUG = false;


	/** static private property _instance
	 *		Holds the instance of this object
	 *
	 * @var Flash object
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
		$this->reset_debug( );
	}


	public function __toString( )
	{
		return 'FLASH- '.implode('; ', $this->_messages( )).' @ '.$this->_location;
	}


	/** public function reset_debug
	 *		Resets the debug value
	 *
	 * @param void
	 * @action sets the debug value based on DEBUG
	 * @return void
	 */
	public function reset_debug( )
	{
		$this->_DEBUG = false;

		if (defined('DEBUG')) {
			$this->_DEBUG = DEBUG;
		}
	}


	/** protected function _log
	 *		Report messages to a file
	 *
	 * @param string message
	 * @action log messages to file
	 * @return void
	 */
	protected function _log($msg)
	{
		// log the error
		if (false && class_exists('Log')) {
			Log::write($msg, __CLASS__);
		}
	}


	/** static public function get_instance
	 *		Returns the singleton instance
	 *		of the Flash Object as a reference
	 *
	 * @param void
	 * @action optionally creates the instance
	 * @return Flash Object reference
	 */
	static public function get_instance( )
	{
		if (is_null(self::$_instance)) {
			if ( ! empty($_SESSION['FLASH']) && $_SESSION['FLASH'] instanceof Flash) {
				$_SESSION['FLASH']->reset_debug( );
				self::$_instance = $_SESSION['FLASH'];
			}
			else {
				self::$_instance = new Flash( );
				$_SESSION['FLASH'] = self::$_instance;
			}
		}

		return self::$_instance;
	}


	/** static public function store
	 *		Save a message to be output to the
	 *		user via javascript alert, and redirect
	 *		based on location given:
	 *			false = no redirect
	 *			true = reload calling page
	 *			'string' = redirect to given URL [default index.php]
	 *
	 * @param string message text
	 * @param string optional redirect location or false to disable redirection
	 * @action optionally redirects to given location and exits script
	 * @return void
	 */
	static public function store($message, $location = 'index.php')
	{
		call(__METHOD__);
		call($message);
		call($location);

		$_this = self::get_instance( );

		$_this->_log(__METHOD__.'("'.$message.'", "'.$location.'")');

		$_this->_messages[] = $message;

		if (false != $location) {
			$orig_location = $location;

			if (true === $location) {
				$location = $_SERVER['REQUEST_URI'];
			}

			// don't allow a redirect if running through AJAX and
			// certainly don't allow a redirect to the ajax helper
			if ( ! empty($GLOBALS['AJAX'])) {
				if (true !== $orig_location) {
					$_this->_location = $location;
				}
			}
			else {
				if ( ! $_this->_DEBUG) {
					session_write_close( );
					$location .= (false !== strpos($location, '?')) ? $GLOBALS['_&_DEBUG_QUERY'] : $GLOBALS['_?_DEBUG_QUERY'];
					header('Location: '.$location);
				}
				else {
					call('FLASH REDIRECTED TO '.$location.' AND QUIT');
				}

				exit;
			}
		}
	}


	/** public function retrieve
	 *		If a location is stored, redirect; otherwise
	 *		output any flashes stored via javascript alert
	 *
	 * @param void
	 * @return string javascript
	 */
	static public function retrieve( )
	{
		call(__METHOD__);

		$_this = self::get_instance( );

		call($_this->_location);
		call($_this->_messages);

		$_this->_log(__METHOD__.'('.var_export($_this->_messages, true).', "'.$_this->_location.'")');

		if ( ! empty($_this->_location)) {
			$location = $_this->_location;
			$_this->_location = false;

			if ( ! $_this->_DEBUG) {
				session_write_close( );
				header('Location: '.$location.$GLOBALS['_?_DEBUG_QUERY']);
			}
			else {
				call('FLASH REDIRECTED TO '.$location.' AND QUIT');
			}

			exit;
		}

		$html = '';
		if (isset($_this->_messages) && is_array($_this->_messages) && count($_this->_messages)) {
			$html = '
				<script type="text/javascript">//<![CDATA[
					alert("';

			foreach ($_this->_messages as $message) {
				$message = preg_replace('/[\r\n]/', '\n', $message);
				$html .= $message.'\n\n --- --- --- \n\n';
			}
			$html = substr($html, 0, -21);

			$html .= '");
				//]]></script>';
		}

		$_this->_messages = array( );

		return $html;
	}

} // end of Flash class

