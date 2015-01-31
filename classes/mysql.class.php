<?php

/*
+---------------------------------------------------------------------------
|
|   mysql.class.php (php 5.1+)
|
|   by Benjam Welker
|   http://iohelix.net
|
+---------------------------------------------------------------------------
|
|   > MySQL PDO module
|   > Date started: 2014-01-17
|
|   > Module Version Number: 0.9.0
|
+---------------------------------------------------------------------------
*/

/**
 * Class Mysql
 * Singleton
 */
class Mysql {

	/**
	 *		PROPERTIES
	 * * * * * * * * * * * * * * * * * * * * * * * * * * */

	/**
	 * @var Mysql instance
	 */
	public static $instance;

	/**
	 * Connection Object
	 *
	 * @var PDO
	 */
	public $conn;

	/**
	 * The connection settings string
	 *
	 * @var string
	 */
	protected $conn_config;

	/**
	 * Default settings
	 *
	 * @var array
	 */
	protected $defaults = array(
		'driver' => 'mysql',
		'hostname' => 'localhost',
		'port' => 3306,
		'log_path' => './',
	);

	/**
	 * Actual settings
	 *
	 * @var array
	 */
	protected $settings;

	/**
	 * The query
	 *
	 * @var string
	 */
	public $query;

	/**
	 * The query params
	 *
	 * @var array
	 */
	public $params;

	/**
	 * @var PDOStatement handler
	 */
	public $sth;

	/**
	 * Debug output for errors
	 *
	 * @var bool
	 */
	protected $debug_error = false;

	/**
	 * Debug output for all queries
	 *
	 * @var bool
	 */
	protected $debug_query = false;

	/**
	 * @var PDO error
	 */
	public $error;

	/**
	 * Error email settings
	 *
	 * @var array
	 */
	protected $email_settings = array(
		'errors' => false,
		'subject' => 'Query Error',
		'from' => 'example@example.com',
		'to' => 'example@example.com',
	);

	/**
	 * Error log settings
	 *
	 * @var array
	 */
	protected $log_settings = array(
		'errors' => false,
		'path' => './',
	);

	/**
	 * The number of queries run
	 *
	 * @var int
	 */
	public $query_count = 0;

	/**
	 * The time spent running queries
	 *
	 * @var float
	 */
	public $query_time = 0;


	/**
	 *		METHODS
	 * * * * * * * * * * * * * * * * * * * * * * * * * * */

	/**
	 * @param array $settings optional
	 *
	 * @return Mysql Object
	 * @throws Exception
	 * @throws MySQLException
	 */
	protected function __construct($settings = null) {
		if (empty($settings) && isset($GLOBALS['_DEFAULT_DATABASE'])) {
			$settings = $GLOBALS['_DEFAULT_DATABASE'];
		}

		if (empty($settings)) {
			throw new MySQLException(__METHOD__.': Missing MySQL configuration data');
		}

		$settings = array_merge($this->defaults, $settings);
		$this->settings = $settings;

		$this->conn_string = "{$settings['driver']}:host={$settings['hostname']};port={$settings['port']};dbname={$settings['database']};charset=utf8";

		$this->log_path = $settings['log_path'];

		try {
//			$this->_log(__METHOD__);
//			$this->_log('===============================');

			$this->connect( );
		}
		catch (MySQLException $up) {
			throw $up;
		}
	}


	/**
	 * Return a singleton instance
	 *
	 * @param null $config
	 *
	 * @return Mysql Singleton Reference
	 * @throws MySQLException
	 * @throws \Exception
	 */
	public static function get_instance($config = null) {
		try {
			if (is_null(self::$instance)) {
				self::$instance = new Mysql($config);
			}

//			self::$instance->test_connection( );
//			self::$instance->_log(__METHOD__.' --------------------------------------');
		}
		catch (MySQLException $e) {
			throw $e;
		}

		return self::$instance;
	}


	/**
	 * Connect to the database
	 *
	 * @param void
	 *
	 * @return void
	 * @throws MySQLException
	 */
	public function connect( ) {
		if (empty($this->settings['username']) || empty($this->settings['password'])) {
			throw new MySQLException(__METHOD__.': Missing MySQL user data');
		}

		$this->conn = new PDO($this->conn_string, $this->settings['username'], $this->settings['password']);

		if (empty($this->conn)) {
			throw new MySQLException(__METHOD__.': Unable to connect to database');
		}

		$this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		// set the DB server timezone to UTC
		$this->conn->query(" SET time_zone = '+00:00'; ");
	}


	/**
	 * Set the error settings
	 *
	 * @param array $settings
	 *
	 * @return void
	 */
	public function set_settings($settings) {
		$valid = array(
			'log_errors',
			'log_path',
			'email_errors',
			'email_subject',
			'email_from',
			'email_to',
		);

		foreach ($valid as $key) {
			if (isset($settings[$key])) {
				list($type, $idx) = explode('_', $key);
				$var = $type.'_settings';
				$this->$var[$idx] = $settings[$key];
			}
		}
	}


	/**
	 * Set a bitwise debugging level
	 * 	0- none
	 * 	1- errors only
	 * 	2- queries only
	 * 	3- all
	 *
	 * @param int $error_level
	 *
	 * @return void
	 */
	public function set_error($error_level) {
		$error_level = (int) $error_level;
		$this->debug_error = (0 != (1 & $error_level));
		$this->debug_query = (0 != (2 & $error_level));
	}


	/**
	 * Tests the MySQL connection and tries to reconnect
	 *
	 * @deprecated
	 *
	 * @param void
	 *
	 * @return void
	 */
	public function test_connection( ) {
		// do nothing
	}


	/**
	 * Run the query
	 * Include named parameters as second argument
	 *
	 * @param string $query optional
	 *
	 * @return bool|PDOStatement
	 * @throws MySQLException
	 */
	public function query($query = null) {
		$args = func_get_args( );
		unset($args[0]);

		if ( ! empty($query)) {
			$this->query = $query;
			$this->params = array( );
		}

		if ( ! empty($args)) {
			$this->params = $args;
		}

		if (empty($this->query)) {
			throw new MySQLException(__METHOD__.': No query found');
		}

		$backtrace_file = $this->_get_backtrace( );

		$this->_log(__METHOD__.' in '.basename($backtrace_file['file']).' on '.$backtrace_file['line'].' : '.$this->query);

		if ( ! $this->conn) {
			$this->connect( );
		}

		$done = true;

		try {
			$time = microtime(true);
			if ( ! empty($this->params)) {
				if ( ! empty($query)) {
					$this->sth = $this->conn->prepare($query);
				}

				$this->sth->execute($this->params);
			}
			else {
				$this->sth = $this->conn->query($query);
			}
			$query_time = microtime(true) - $time;
			$this->query_time += $query_time;

			if ($this->debug_query && empty($GLOBALS['AJAX'])) {
				$debug_query = trim(preg_replace('%\s+%', ' ', $this->query));
				if ( ! empty($this->params)) {
					$debug_query = str_replace(array_keys($this->params), $this->params, $debug_query);
				}

				if (('cli' == php_sapi_name( )) && empty($_SERVER['REMOTE_ADDR'])) {
					echo "\n\nMYSQL - ".basename($backtrace_file['file']).' on '.$backtrace_file['line']."- {$debug_query} - Aff(".$this->affected_rows( ).") (".number_format($query_time, 5)." s)\n\n";
				}
				else {
					echo "<div style='background:#FFF;color:#009;'><br /><strong>".basename($backtrace_file['file']).' on '.$backtrace_file['line']."</strong>- {$debug_query} - <strong>Aff(".$this->affected_rows( ).") (".number_format($query_time, 5)." s)</strong></div>";
				}
			}
		}
		catch (PDOException $poo) {
			if (empty($this->tries)) {
				$this->tries = 0;
			}

			if ((5 >= $this->tries) && ((2013 == $this->sth->errorCode( )) || (2006 == $this->sth->errorCode( )))) {
				// try reconnecting a couple of times
				$this->_log('RETRYING #'.$this->tries.': '.$this->sth->errorCode( ));
				$this->test_connection( );
				return $this->query(null, ++$this->tries);
			}

			$extra = '';
			if ($backtrace_file) {
				$line = $backtrace_file['line'];
				$file = $backtrace_file['file'];
				$file = substr($file, strlen(realpath($file.'../../../')));
				$extra = ' on line <strong>'.$line.'</strong> of <strong>'.$file.'</strong>';
			}

			$this->error = $this->sth->errorCode( ).': '.$this->sth->errorInfo( );
			$this->_error_report( );

			if ($this->debug_error) {
				if (('cli' == php_sapi_name( )) && empty($_SERVER['REMOTE_ADDR'])) {
					$extra = strip_tags($extra);
					echo "\n\nMYSQL ERROR - There was an error in your query{$extra}:\nERROR: {$this->error}\nQUERY: {$this->query}\n\n";
				}
				else {
					echo "<div style='background:#900;color:#FFF;'>There was an error in your query{$extra}:<br />ERROR: {$this->error}<br />QUERY: {$this->query}</div>";
				}
			}
			else {
				$this->error = 'There was a database error.';
			}

			$done = false;
		}

		if ($done) {
			$this->query_count++;

			// if we just performed an insert, grab the insert_id and return it
			if (preg_match('/^\s*(?:INSERT|REPLACE)\b/i', $this->query)) {
				return $this->fetch_insert_id( );
			}

			return $this->sth;
		}

		if (preg_match('/^\s*SELECT\b/i', $this->query)) {
			// no result found
			return false;
		}
		else {
			// query performed successfully
			return true;
		}
	}


	/**
	 * Returns the number of affected rows in the last query
	 *
	 * @param void
	 *
	 * @return int
	 */
	public function affected_rows( ) {
		return $this->sth->rowCount( );
	}


	/**
	 * Return the number of rows found in the last select
	 *
	 * @deprecated
	 *
	 * @param void
	 *
	 * @return void
	 * @throws MySQLException
	 */
	public function num_rows( ) {
		ini_set('display_errors', true);
		error_reporting(-1);
		throw new MySQLException(__METHOD__.': Method is deprecated');
	}


	/**
	 * Build a WHERE clause from a conditions array
	 *
	 * @param array $where conditions
	 * @param string $join method ('AND', 'OR')
	 *
	 * @return string WHERE clause
	 */
	protected function build_where($where, $join = 'AND') {
		if (empty($where)) {
			return ' 1 = 1 ';
		}

		$join = trim(strtoupper($join));
		$clauses = array( );
		foreach ($where as $clause => $value) {
			if (is_numeric($clause) && is_array($value)) {
				$clauses[] = '( '.$this->build_where($value).' )';
			}
			elseif (in_array(strtoupper(trim($clause)), array('AND', 'OR'))) {
				$clauses[] = '( '.$this->build_where($value, strtoupper(trim($clause))).' )';
			}
			else {
				if (is_null($value)) {
					$value = 'NULL';
				}
				elseif (is_bool($value)) {
					$value = $value ? 'TRUE' : 'FALSE';
				}

				if (false === strpos($clause, ' ')) {
					if (is_array($value)) {
						if (empty($value) && ('AND' === $join)) {
							return ' 1 = 1 ';
						}
						elseif ( ! empty($value)) {
							$clauses[] = $clause." IN ('".implode("','", $value)."')";
						}
					}
					else {
						if (is_numeric($clause)) {
							$clauses[] = $value;
						}
						else {
							if ( ! in_array($value, array('NULL', 'TRUE', 'FALSE')) && (0 !== strpos($value, ':')) && ('?' !== $value)) {
								$value = $this->conn->quote($value);
							}

							$clauses[] = $clause.' = '.$value;
						}
					}
				}
				else {
					if (is_array($value)) {
						if (empty($value) && ('AND' === $join)) {
							return ' 1 = 1 ';
						}
						elseif ( ! empty($value)) {
							$clauses[] = $clause." ANY ('".implode("','", $value)."')";
						}
					}
					else {
						if ( ! in_array($value, array('NULL', 'TRUE', 'FALSE')) && (0 !== strpos($value, ':')) && ('?' !== $value)) {
							$value = "'{$value}'";
						}

						$clauses[] = $clause.' '.$value;
					}
				}
			}
		}

		return implode(' '.$join.' ', $clauses);
	}


	/**
	 * Extract the query parameters and values
	 * from a conditions array
	 *
	 * @param array $where
	 *
	 * @return array params
	 */
	protected function get_params($where) {
// TODO: not quite sure how to build this
	}


	/**
	 * Insert|Update|Replace table entry
	 * 		$data['field_name'] = value
	 * 		$data['field_name2'] = value2
	 *
	 * If the field name has a trailing space: $data['field_name ']
	 * then the query will insert the data with no sanitation
	 * or wrapping quotes (good for function calls, like NOW( )).
	 *
	 * @param string $table
	 * @param array $data_array
	 * @param string|array $where
	 * @param bool $replace
	 *
	 * @return bool
	 * @throws MySQLException
	 */
	public function insert($table, $data_array, $where = '', $replace = false) {
		if (is_array($where) && ! empty($where)) {
			$where = " WHERE ". $this->build_where($where);
			$this->params = $this->get_params($where);
		}

		$replace = (bool) $replace;

		if (empty($where)) {
			$query  = (false == $replace) ? ' INSERT ' : ' REPLACE ';
			$query .= ' INTO ';
		}
		else {
			$query = ' UPDATE ';
		}

		$query .= '`'.$table.'`';

		if ( ! is_array($data_array)) {
			throw new MySQLException(__METHOD__.': Trying to insert non-array data');
		}
		else {
			$query .= ' SET ';
			foreach ($data_array as $field => $value) {
				if (is_null($value)) {
					$query .=  " `{$field}` = NULL , ";
				}
				elseif (is_bool($value)) {
					$query .= " `{$field}` = ".($value ? 'TRUE' : 'FALSE')." , ";
				}
				elseif (' ' == substr($field, -1, 1)) { // a trailing space was chosen because it's an illegal field name in MySQL
					$field = trim($field);
					$query .= " `{$field}` = {$value} , ";
				}
				else {
					$this->params[$field.'_val'] = $value;
					$query .= " `{$field}` = :{$field}_val , ";
				}
			}

			$query = substr($query, 0, -2).' '; // remove the last comma (but preserve the spaces)
		}

		$query .= ' '.$where.' ';
		$this->query = $query;
		$return = $this->query( );

		if (empty($where)) {
			return $this->fetch_insert_id( );
		}
		else {
			return $return;
		}
	}


	/**
	 * Perform insert on multiple entries at once
	 * 		$data[0]['field_name'] = value
	 * 		$data[0]['field_name2'] = value2
	 * 		$data[0]['DBWHERE'] = where clause [optional]
	 * 		$data[1]['field_name'] = value
	 * 		$data[1]['field_name2'] = value2
	 * 		$data[1]['DBWHERE'] = where clause [optional]
	 *
	 * @param string $table
	 * @param array $data_array
	 * @param bool $replace optional
	 *
	 * @return array
	 * @throws MySQLException
	 */
	public function multi_insert($table, $data_array, $replace = false) {
		if ( ! is_array($data_array)) {
			throw new MySQLException(__METHOD__.': Trying to multi-insert non-array data');
		}
		else {
			$result = array( );

			foreach ($data_array as $key => $row) {
				$where = (isset($row['DBWHERE'])) ? $row['DBWHERE'] : '';
				unset($row['DBWHERE']);
				$result[$key] = $this->insert($table, $row, $where, $replace);
			}
		}

		return $result;
	}


	/**
	 * Delete an entry from a table
	 *
	 * @param string $table
	 * @param string|array $where
	 *
	 * @return bool
	 * @throws Exception
	 * @throws MySQLException
	 */
	public function delete($table, $where) {
		if (is_array($where)) {
			$where = " WHERE ". $this->build_where($where);
			$this->params = $this->get_params($where);
		}

		$this->query = "
			DELETE
			FROM `{$table}`
			{$where}
		";

		try {
			return $this->query( );
		}
		catch (MySQLException $crap) {
			throw $crap;
		}
	}


	/**
	 * Delete the array of data from the table.
	 * 		$table[0] = table name
	 *		$table[1] = table name
	 *
	 *		$where[0] = where clause
	 *		$where[1] = where clause
	 *
	 * If recursive is true, all combinations of table name
	 * and where clauses will be executed.
	 *
	 * If only one table name is set, that table will
	 * be used for all of the queries, looping through
	 * the where array
	 *
	 * If only one where clause is set, that where clause
	 * will be used for all of the queries, looping through
	 * the table array
	 *
	 * @param array $table_array
	 * @param array $where_array
	 * @param bool $recursive optional
	 *
	 * @return array of results
	 * @throws Exception
	 * @throws MySQLException
	 */
	public function multi_delete($table_array, $where_array, $recursive = false) {
		if ( ! is_array($table_array)) {
			$recursive = false;
			$table_array = (array) $table_array;
		}

		if ( ! is_array($where_array)) {
			$recursive = false;
			$where_array = array($where_array);
		}

		$result = array( );

		if ($recursive) {
			foreach ($table_array as $table) {
				foreach ($where_array as $where) {
					$result[] = $this->delete($table, $where);
				}
			}
		}
		else {
			if (count($table_array) == count($where_array)) {
				for ($i = 0, $count = count($table_array); $i < $count; ++$i) {
					$result[] = $this->delete($table_array[$i], $where_array[$i]);
				}
			}
			elseif (1 == count($table_array)) {
				$table = $table_array[0];
				foreach ($where_array as $where) {
					$result[] = $this->delete($table, $where);
				}
			}
			elseif (1 == count($where_array)) {
				$where = $where_array[0];
				foreach ($table_array as $table) {
					$result[] = $this->delete($table, $where);
				}
			}
			else {
				throw new MySQLException(__METHOD__.': Trying to multi-delete with incompatible array sizes');
			}
		}

		return $result;
	}


	/**
	 * Select entries from a table
	 *
	 * @param string $table
	 * @param string|array $where
	 *
	 * @return PDOStatement
	 * @throws MySQLException
	 */
	public function fetch($table, $where = '') {
		$this->query = "
			SELECT *
			FROM `{$table}`
		";

		if ( ! empty($where)) {
			if (is_array($where)) {
				$this->query .= " WHERE ".$this->build_where($where);
				$this->params = $this->get_params($where);
			}
			else {
				$this->query .= " {$where} ";
			}
		}

		return $this->query( );
	}

	public function fetch_object($query = null) {
		$this->process_args(func_get_args( ));
	}

	public function fetch_row($query = null) {
		$this->process_args(func_get_args( ));
	}

	public function fetch_assoc($query = null) {
		$this->process_args(func_get_args( ));
	}

	public function fetch_both($query = null) {
		$this->process_args(func_get_args( ));
	}

// TODO: obviously, the second argument here needs to be changed
	public function fetch_array($result_type = MYSQL_ASSOC, $query = null) {
		$this->process_args(func_get_args( ), 2);
	}

	public function fetch_value($query = null) {
		$this->process_args(func_get_args( ));
	}

	public function fetch_value_array($query = null) {
		$this->process_args(func_get_args( ));
	}

	public function paginate($page = null, $num_per_page = null, $query = null) {
		$this->process_args(func_get_args( ), 3);
	}


	/**
	 * Process the incoming arguments into the
	 * various class properties
	 *
	 * @param array $args
	 * @param int $count of named arguments
	 *
	 * @return void
	 */
	protected function process_args($args, $count = 1) {
		while ($count) {
			if (1 === $count) {
				$query = $args[0];
			}

			unset($args[0]);
			$args = array_values($args);
			--$count;
		}

		if ( ! empty($query)) {
			$this->query = $query;
			$this->params = array( );
		}

		if ( ! empty($args)) {
			$this->params = $args;
		}
	}


	/**
	 * Get the insert ID for the last insert
	 *
	 * @param void
	 *
	 * @return string numeric
	 */
	public function fetch_insert_id( ) {
		return $this->conn->lastInsertId( );
	}


	/**
	 * Log a message to the Mysql log file
	 *
	 * @param string $msg
	 *
	 * @return void
	 */
	protected function _log($msg) {
		if (false && $this->log_settings['errors'] && class_exists('Log')) {
			Log::write($msg, __CLASS__);
		}
	}

	protected function _error_report( ) {

	}

	/**
	 * Grab a backtrace so the origin of any errors
	 * can be properly logged
	 *
	 * @param void
	 *
	 * @return bool
	 */
	protected function _get_backtrace( ) {
		// grab the debug_backtrace
		$debug = debug_backtrace(false);

		// parse through it, and find the first instance that isn't from this file
		$file = false;
		foreach ($debug as $file) {
			if (__FILE__ == $file['file']) {
				continue;
			}
			else {
				// $file will be the file that called the mysql function
				break;
			}
		}

		return $file;
	}


	/**
	 * Test the connection
	 *
	 * @param void
	 *
	 * @return bool
	 */
	public static function test( ) {
		try {
			self::get_instance( );
			return true;
		}
		catch (MySQLException $e) {
			return false;
		}
	}

}


/**
 * Class MySQLException
 *
 * @package Iohelix
 */
class MySQLException extends Exception {

	/**
	 *		PROPERTIES
	 * * * * * * * * * * * * * * * * * * * * * * * * * * */

	/**
	 * @var bool
	 */
	protected $backtrace = true;


	/**
	 *		METHODS
	 * * * * * * * * * * * * * * * * * * * * * * * * * * */

	/**
	 * Class constructor
	 * Sets all outside data
	 *
	 * @param string $message error message
	 * @param int $code optional error code
	 *
	 * @action instantiates object
	 * @action writes the exception to the log
	 *
	 * @return MySQLException
	 */
	public function __construct($message, $code = 1) {
		parent::__construct($message, $code);

		// our own exception handling stuff
		if ( ! empty($GLOBALS['_LOGGING'])) {
			$this->logError( );
		}
	}


	/**
	 * Cleans the message for use
	 *
	 * @param void
	 *
	 * @return string cleaned message
	 */
	public function outputMessage( ) {
		// strip off the __METHOD__ bit of the error, if possible
		$message = $this->message;
		$message = preg_replace('/(?:\\w+::)+\\w+:\\s+/', '', $message);

		return $message;
	}


	/**
	 * Writes the exception to the log file
	 *
	 * @param void
	 *
	 * @action writes the exception to the log
	 *
	 * @return void
	 */
	protected function logError( ) {
		// first, lets make sure we can actually open and write to directory
		// specified by the global variable... and lets also do daily logs for now
		$log_name = 'mysql_exception_'.date('Ymd', time( )).'.log'; // don't use ldate() here

		// okay, write our log message
		$str = date('Y/m/d H:i:s')." == ({$this->code}) {$this->message} : {$this->file} @ {$this->line}\n"; // don't use ldate() here

		if ($this->backtrace) {
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


/*
 +---------------------------------------------------------------------------
 |   > Extra SQL Functions
 +---------------------------------------------------------------------------
*/

if ( ! function_exists('microtime_float')) {
// TODO: convert this to ' microtime(true) ' anywhere it's used
	function microtime_float( ) {
die('microtime_float used')		;
		list($usec, $sec) = explode(' ', microtime( ));
		return ((float) $usec + (float) $sec);
	}
}

