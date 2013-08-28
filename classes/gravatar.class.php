<?php
/*
+---------------------------------------------------------------------------
|
|   gravatar.class.php (php 5.x)
|
|   by Benjam Welker
|   http://iohelix.net
|
+---------------------------------------------------------------------------
|
|   > Gravatar module
|   > Date started: 2009-08-01
|
|   > Module Version Number: 0.8.0
|
+---------------------------------------------------------------------------
*/


class Gravatar
{

	/**
	 *		PROPERTIES
	 * * * * * * * * * * * * * * * * * * * * * * * * * * */

	/** const property GRAVATAR_SITE_URL
	 *		Stores the url of the gravatar access page
	 *
	 * @param string
	 */
	const GRAVATAR_SITE_URL = 'http://www.gravatar.com/avatar/%s?s=%d&amp;r=%s&amp;d=%s';


	/** protected property _default
	 *		Stores the default image string
	 *		Can be one of: identicon, monsterid, wavatar, [empty string]
	 *		or the url of the default image
	 *
	 * @param string
	 */
	protected $_default;


	/** protected property _email_hash
	 *		Stores the md5 hash of the email address
	 *
	 * @param string
	 */
	protected $_email_hash;


	/** protected property _rating
	 *		Stores the maximum rating of the returned image
	 *		Cane be one of: g, pg, r, x
	 *
	 * @param string
	 */
	protected $_rating;


	/** protected property _size
	 *		Stores the size of the returned image
	 *		Must be between 1 and 512
	 *
	 * @param int
	 */
	protected $_size;


	/** static private property _instance
	 *		Holds the instance of this object
	 *
	 * @var Gravatar object
	 */
	static private $_instance;


	/**
	 *		METHODS
	 * * * * * * * * * * * * * * * * * * * * * * * * * * */

	/** public function __construct
	 *		Class constructor
	 *		Sets all outside data
	 *
	 * @param array settings
	 * @action instantiates object
	 * @return void
	 */
	public function __construct($settings = array( ))
	{
		foreach ($settings as $key => $value) {
			switch ($key) {
				case 'size' :
					$value = (int) $value;

					if (0 == $value) {
						unset($settings[$key]);
						break;
					}

					if (1 > $value) {
						$value = 1;
					}

					if (512 < $value) {
						$value = 512;
					}
					break;

				case 'rating' :
					$value = strtolower($value);

					if ( ! in_array($value, array('g', 'pg', 'r', 'x'))) {
						unset($settings[$key]);
						break;
					}
					break;

				case 'default' :
					$value = strtolower($value);

					if ( ! in_array($value, array('identicon', 'monsterid', 'wavatar', ''))
						&& ! preg_match('%^http://%i', $value))
					{
						unset($settings[$key]);
						break;
					}
					break;
			}

			if (isset($settings[$key])) {
				$settings[$key] = $value;
			}
		}

		// you can set your own default settings here
		// or leave blank to use gravatar.com's defaults
		$defaults = array(
			'size' => 45,
			'rating' => 'pg',
			'default' => 'identicon',
		);

		$opts = array_merge($defaults, $settings);

		// set some defaults

		$this->_size = (int) $opts['size'];
		$this->_rating = strtolower($opts['rating']);
		$this->_default = $opts['default'];
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


	/** public function get_src
	 *		Returns the src for the image tag
	 *
	 * @param void
	 * @return string image src
	 */
	public function get_src( )
	{
		return (string) sprintf(
			self::GRAVATAR_SITE_URL,
			$this->_email_hash,
			$this->_size,
			strtolower($this->_rating),
			$this->_default
		);
	}


	/** public function set_email
	 *		Sets the email hash based on the given email
	 *
	 * @param string email address
	 * @action sets email hash
	 * @return void
	 */
	public function set_email($email)
	{
		$this->_email_hash = md5($email);
	}


	/** static public function get_instance
	 *		Returns the singleton instance
	 *		of the Gravatar Object as a reference
	 *
	 * @param array optional settings
	 * @action optionally creates the instance
	 * @return Log Object reference
	 */
	static public function get_instance($settings = array( ))
	{
		if (is_null(self::$_instance)) {
			self::$_instance = new Gravatar($settings);
		}

		return self::$_instance;
	}


	/** static public function src
	 *		Returns the src for the image tag
	 *
	 * @param string optional email address
	 * @param array optional settings
	 * @return string image src
	 */
	static public function src($email = null, $settings = array( ))
	{
		$_this = self::get_instance($settings);

		if ( ! empty($email)) {
			$_this->set_email($email);
		}

		return $_this->get_src( );
	}

} // end of Gravatar class

