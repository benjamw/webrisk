<?php
/*
+---------------------------------------------------------------------------
|
|   mysql.class.php (php 5.x)
|
|   by Benjam Welker
|   http://iohelix.net
|   based on works by W. Jason Gilmore
|   http://www.wjgilmore.com; http://www.apress.com
|
+---------------------------------------------------------------------------
|
|   > MySQL DB Queries module
|   > Date started: 2005-09-02
|
|   > Module Version Number: 0.9.2
|
+---------------------------------------------------------------------------
*/

// TODO: comments & organize better

class Mysql
{

	/**
	 *		PROPERTIES
	 * * * * * * * * * * * * * * * * * * * * * * * * * * */

	protected $link_id;      // MySQL Resource ID

	protected $query;       // MySQL query
	protected $result;      // Query result

	protected $query_time;   // Time it took to run the query
	protected $query_count;  // Total number of queries executed since class inception

	protected $error;       // Any error message encountered while running

	protected $_host;       // MySQL Host name
	protected $_user;       // MySQL Username
	protected $_pswd;       // MySQL password
	protected $_db;         // MySQL Database

	protected $_page_query;  // MySQL query for pagination
	protected $_page_result; // MySQL result for pagination
	protected $_num_results; // Number of total results found
	protected $_page;       // Current pagination page
	protected $_num_per_page; // Number of records per page
	protected $_num_pages;   // number of total pages

	protected $_error_debug = false; // Allows for error debug output
	protected $_query_debug = false; // Allows for output of all queries all the time

	protected $_log_errors = false; // write to log file when an error is encountered
	protected $_log_path = './';    // Path to the MySQL error log file

	protected $_email_errors = false; // send an email when an error is encountered
	protected $_email_subject = 'Query Error'; // the email subject for the email message
	protected $_email_from = 'example@example.com'; // the email address to send error reports from
	protected $_email_to = 'example@example.com'; // the email address to send error reports to

	static private $_instance; // Instance of the MySQL Object



	/**
	 *		METHODS
	 * * * * * * * * * * * * * * * * * * * * * * * * * * */

	/** protected function __construct
	 *		Class constructor.
	 *		Initializes the host, user, pswd, and db vars.
	 *
	 * @param array optional configuration array
	 * @return void
	 */
	protected function __construct($config = null)
	{
		if (empty($config) && isset($GLOBALS['_DEFAULT_DATABASE'])) {
			$config = $GLOBALS['_DEFAULT_DATABASE'];
		}

		// each of these can be set independently as needed
		$this->_error_debug = false; // set to true for output of errors
		$this->_query_debug = false; // set to true for output of every query

		if (empty($config)) {
			throw new MySQLException(__METHOD__.': Missing MySQL configuration data');
		}

		$this->_host = $config['hostname'];
		$this->_user = $config['username'];
		$this->_pswd = $config['password'];
		$this->_db   = $config['database'];

		$this->_log_path = (isset($config['log_path'])) ? $config['log_path'] : './';

		$this->query_time = 0;
		$this->query_count = 0;

		try {
			$this->_log(__METHOD__);
			$this->_log('===============================');

			$this->connect_select( );
		}
		catch (MySQLException $e) {
			throw $e;
		}
	}


	/** public function __destruct
	 *		Class destructor.
	 *		Closes the mysql connection.
	 *
	 * @param void
	 * @action close the mysql connection
	 * @return void
	 */
/*
	public function __destruct( )
	{
		$this->_log(__METHOD__.': '.$this->link_id);
		$this->_log('===============================');

		return; // just stop doing this

		@mysql_close($this->link_id);
		$this->link_id = null;
		self::$_instance = null;
	}
*/


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
			throw new MySQLException(__METHOD__.': Trying to access non-existent property ('.$property.')');
		}

		if ('_' === $property[0]) {
			throw new MySQLException(__METHOD__.': Trying to access _private property ('.$property.')');
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
			throw new MySQLException(__METHOD__.': Trying to access non-existent property ('.$property.')');
		}

		if ('_' === $property[0]) {
			throw new MySQLException(__METHOD__.': Trying to access _private property ('.$property.')');
		}

		$this->$property = $value;
	}


	/** public function set_settings
	 *		Sets the given settings for the object
	 *
	 * @param array settings array
	 * @action updates the settings
	 * @return void
	 */
	public function set_settings($settings)
	{
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
				$var = '_'.$key;
				$this->$var = $settings[$key];
			}
		}
	}


	/** public function test_connection
	 *		Tests the connection to the MySQL
	 *		server, and reconnects if needed
	 *
	 * @param void
	 * @action reconnects to the server
	 * @return void
	 */
	public function test_connection( )
	{
		if ( ! mysql_ping( )) {
			mysql_close($this->link_id);
			$this->connect_select( );
			$this->_log('RECONNECT ++++++++++++++++++++++++++++++++++++++ '.$this->link_id);
		}
	}


	/** public function connect
	 *		Connect to the MySQL server.
	 *
	 * @param void
	 * @action connect to the mysql server
	 * @return void
	 */
	public function connect( )
	{
		$this->link_id = @mysql_connect($this->_host, $this->_user, $this->_pswd);

		if ( ! $this->link_id) {
			$this->error = mysql_errno( ).': '.mysql_error( );
			throw new MySQLException(__METHOD__.': There was an error connecting to the server');
		}
	}


	/** public function select
	 *		Select the MySQL database.
	 *
	 * @param string [optional] database name
	 * @action select the mysql database
	 * @return void
	 */
	public function select($database = null)
	{
		if ( ! is_null($database)) {
			$this->_db = $database;
		}

		if ( ! @mysql_select_db($this->_db, $this->link_id)) {
			$this->error = mysql_errno($this->link_id).': '.mysql_error($this->link_id);
			throw new MySQLException(__METHOD__.': There was an error selecting the database');
		}
	}


	/** public function connect_select
	 *		Connects to the server AND selects the default database in one function.
	 *
	 * @param string [optional] database name
	 * @action connect to the mysql server
	 * @action select the mysql database
	 * @return void
	 */
	public function connect_select($database = null)
	{
		if ( ! is_null($database)) {
			$this->_db = $database;
		}

		try {
			$this->connect( );
			$this->select( );
		}
		catch (MySQLException $e) {
			throw $e;
		}

		$this->_log(__METHOD__.': '.$this->link_id);
		$this->_log('-------------------------------');
	}


	/** public function set_error
	 *		Set the error level based on a bitwise value.
	 *
	 * @param int value (0 = none, 3 = all)
	 * @action set the error level
	 * @return void
	 */
	public function set_error($val)
	{
		$this->_error_debug = (0 != (1 & $val));
		$this->_query_debug = (0 != (2 & $val));
	}


	/** public function query
	 *		Execute a database query
	 *		If no query is passed, it executes the last saved query.
	 *
	 * @param string [optional] SQL query string
	 * @param int [optional] number of tries
	 * @action execute a mysql query
	 * @return mysql result resource
	 */
	public function query($query = null, $tries = 0)
	{
		if ( ! is_null($query)) {
			$this->query = $query;
		}

		if (is_null($this->query)) {
			throw new MySQLException(__METHOD__.': No query found');
		}

		$backtrace_file = $this->_get_backtrace( );

		$this->_log(__METHOD__.' in '.basename($backtrace_file['file']).' on '.$backtrace_file['line'].' : '.$this->query);

		if (empty($this->link_id)) {
			$this->connect_select( );
		}

		$done = true; // innocent until proven guilty

		// start time logging
		$time = microtime_float( );
		$this->result = @mysql_query($this->query, $this->link_id);
		$this->query_time = microtime_float( ) - $time;

		if ($this->_query_debug && empty($GLOBALS['AJAX'])) {
			$this->query = trim(preg_replace('/\\s+/', ' ', $this->query));
			if (('cli' == php_sapi_name( )) && empty($_SERVER['REMOTE_ADDR'])) {
				echo "\n\nMYSQL - ".basename($backtrace_file['file']).' on '.$backtrace_file['line']."- {$this->query} - Aff(".$this->affected_rows( ).") (".number_format($this->query_time, 5)." s)\n\n";
			}
			else {
				echo "<div style='background:#FFF;color:#009;'><br /><strong>".basename($backtrace_file['file']).' on '.$backtrace_file['line']."</strong>- {$this->query} - <strong>Aff(".$this->affected_rows( ).") (".number_format($this->query_time, 5)." s)</strong></div>";
			}
		}

		if ( ! $this->result) {
			if ((5 >= $tries) && ((2013 == mysql_errno($this->link_id)) || (2006 == mysql_errno($this->link_id)))) {
				// try reconnecting a couple of times
				$this->_log('RETRYING #'.$tries.': '.mysql_errno($this->link_id));
				$this->test_connection( );
				return $this->query(null, ++$tries);
			}

			$extra = '';
			if ($backtrace_file) {
				$line = $backtrace_file['line'];
				$file = $backtrace_file['file'];
				$file = substr($file, strlen(realpath($file.'../../../')));
				$extra = ' on line <strong>'.$line.'</strong> of <strong>'.$file.'</strong>';
			}

			$this->error = mysql_errno($this->link_id).': '.mysql_error($this->link_id);
			$this->_error_report( );

			if ($this->_error_debug) {
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
			// if we just performed an insert, grab the insert_id and return it
			if (preg_match('/^\s*(?:INSERT|REPLACE)\b/i', $this->query)) {
				$this->result = $this->fetch_insert_id( );
			}

			$this->query_count++;
			return $this->result;
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


	/** public function affected_rows
	 *		Return the number of affected rows from the latest query.
	 *
	 * @param void
	 * @return int number of affected rows
	 */
	public function affected_rows( )
	{
		$count = @mysql_affected_rows($this->link_id);
		return $count;
	}


	/** public function num_rows
	 *		Return the number of returned rows from the latest query.
	 *
	 * @param void
	 * @return int number of returned rows
	 */
	public function num_rows( )
	{
		$count = @mysql_num_rows($this->result);

		if ( ! $count) {
			return 0;
		}

		return $count;
	}


	/** public function insert
	 *		Insert the associative data array into the table.
	 *			$data['field_name'] = value
	 *			$data['field_name2'] = value2
	 *		If the field name has a trailing space: $data['field_name ']
	 *		then the query will insert the data with no sanitation
	 *		or wrapping quotes (good for function calls, like NOW( )).
	 *
	 * @param string table name
	 * @param array associative data array
	 * @param string [optional] where clause (for updates)
	 * @param bool [optional] whether or not we should replace values (true / false)
	 * @action execute a mysql query
	 * @return int insert id for row
	 */
	public function insert($table, $data_array, $where = '', $replace = false)
	{
		$where = trim($where);
		$replace = (bool) $replace;

		if ('' == $where) {
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
				elseif (' ' == substr($field, -1, 1)) { // i picked a trailing space because it's an illegal field name in MySQL
					$field = trim($field);
					$query .= " `{$field}` = {$value} , ";
				}
				else {
					$query .= " `{$field}` = '".sani($value)."' , ";
				}
			}

			$query = substr($query, 0, -2).' '; // remove the last comma (but preserve those spaces)
		}

		$query .= ' '.$where.' ';
		$this->query = $query;
		$return = $this->query( );

		if ('' == $where) {
			return $this->fetch_insert_id( );
		}
		else {
			return $return;
		}
	}


	/** public function multi_insert
	 *		Insert the array of associative data arrays into the table.
	 *			$data[0]['field_name'] = value
	 *			$data[0]['field_name2'] = value2
	 *			$data[0]['DBWHERE'] = where clause [optional]
	 *			$data[1]['field_name'] = value
	 *			$data[1]['field_name2'] = value2
	 *			$data[1]['DBWHERE'] = where clause [optional]
	 *
	 * @param string table name
	 * @param array associative data array
	 * @param bool [optional] whether or not we should replace values (true / false)
	 * @action execute multiple mysql queries
	 * @return array insert IDs for rows (with original keys preserved)
	 */
	public function multi_insert($table, $data_array, $replace = false)
	{
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


	/** public function delete
	 *		Delete the row from the table
	 *
	 * @param string table name
	 * @param string where clause
	 * @action execute a mysql query
	 * @return result
	 */
	public function delete($table, $where)
	{
		$query = "
			DELETE
			FROM `{$table}`
			{$where}
		";

		$this->query = $query;

		try {
			return $this->query( );
		}
		catch (MySQLException $e) {
			throw $e;
		}
	}


	/** public function multi_delete
	 *		Delete the array of data from the table.
	 *			$table[0] = table name
	 *			$table[1] = table name
	 *
	 *			$where[0] = where clause
	 *			$where[1] = where clause
	 *
	 *		If recursive is true, all combinations of table name
	 *		and where clauses will be executed.
	 *
	 *		If only one table name is set, that table will
	 *		be used for all of the queries, looping through
	 *		the where array
	 *
	 *		If only one where clause is set, that where clause
	 *		will be used for all of the queries, looping through
	 *		the table array
	 *
	 * @param mixed table name array or single string
	 * @param mixed where clause array or single string
	 * @param bool optional recursive (default false)
	 * @action execute multiple mysql queries
	 * @return array results
	 */
	public function multi_delete($table_array, $where_array, $recursive = false)
	{
		if ( ! is_array($table_array)) {
			$recursive = false;
			$table_array = (array) $table_array;
		}

		if ( ! is_array($where_array)) {
			$recursive = false;
			$where_array = (array) $where_array;
		}

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


	/** public function fetch_object
	 *		Execute a database query and return the next result row as object.
	 *		Each subsequent call to this method returns the next result row.
	 *
	 * @param string [optional] SQL query string
	 * @action [optional] execute a mysql query
	 * @return mysql next result object row
	 */
	public function fetch_object($query = null)
	{
		if ( ! is_null($query)) {
			$this->query = $query;
			$this->query( );
		}

		$row = @mysql_fetch_object($this->result);
		return $row;
	}


	/** public function fetch_row
	 *		Execute a database query and return result as an indexed array.
	 *		Each subsequent call to this method returns the next result row.
	 *
	 * @param string [optional] SQL query string
	 * @action [optional] execute a mysql query
	 * @return array indexed mysql result array
	 */
	public function fetch_row($query = null)
	{
		if ( ! is_null($query)) {
			$this->query = $query;
			$this->query( );
		}

		$row = @mysql_fetch_row($this->result);

		if ( ! $row) {
			$row = array( );
		}

		return $row;
	}


	/** public function fetch_assoc
	 *		Execute a database query and return result as an associative array.
	 *		Each subsequent call to this method returns the next result row.
	 *
	 * @param string [optional] SQL query string
	 * @action [optional] execute a mysql query
	 * @return array associative mysql result array
	 */
	public function fetch_assoc($query = null)
	{
		if ( ! is_null($query)) {
			$this->query = $query;
			$this->query( );
		}

		$row = @mysql_fetch_assoc($this->result);

		if ( ! $row) {
			$row = array( );
		}

		return $row;
	}


	/** public function fetch_both
	 *		Execute a database query and return result as both
	 *		an associative array and indexed array.
	 *		Each subsequent call to this method returns the next result row.
	 *
	 * @param string [optional] SQL query string
	 * @action [optional] execute a mysql query
	 * @return array associative and indexed mysql result array
	 */
	public function fetch_both($query = null)
	{
		if ( ! is_null($query)) {
			$this->query = $query;
			$this->query( );
		}

		$row = @mysql_fetch_array($this->result, MYSQL_BOTH);

		if ( ! $row) {
			$row = array( );
		}

		return $row;
	}


	/** public function fetch_array
	 *		Execute a database query and return result as
	 *		an indexed array of both indexed and associative arrays.
	 *		This method returns the entire result set in a single call.
	 *
	 * @param string [optional] SQL query string
	 * @param int [optional] SQL result type ( One of: MYSQL_ASSOC, MYSQL_NUM, MYSQL_BOTH )
	 * @action [optional] execute a mysql query
	 * @return array indexed array of mysql result arrays of type $result_type
	 */
	public function fetch_array($query = null, $result_type = MYSQL_ASSOC)
	{
		if ( ! is_null($query)) {
			$this->query = $query;
			$this->query( );
		}

		$arr = array( );
		while ($row = @mysql_fetch_array($this->result, $result_type)) {
			$arr[] = $row;
		}

		return $arr;
	}


	/** public function fetch_value
	 *		Execute a database query and return result as
	 *		a single result value.
	 *		This method only returns the single value at index 0.
	 *		Each subsequent call to this method returns the next value.
	 *
	 * @param string [optional] SQL query string
	 * @action [optional] execute a mysql query
	 * @return mixed single mysql result value
	 */
	public function fetch_value($query = null)
	{
		if ( ! is_null($query)) {
			$this->query = $query;
			$this->query( );
		}

		$row = @mysql_fetch_row($this->result);

		if (false !== $row) {
			return $row[0];
		}
		else {
			// no data found
			return null;
		}
	}


	/** public function fetch_value_array
	 *		Execute a database query and return result as
	 *		an indexed array of single result values.
	 *		This method returns the entire result set in a single call.
	 *
	 * @param string [optional] SQL query string
	 * @action [optional] execute a mysql query
	 * @return array indexed array of single mysql result values
	 */
	public function fetch_value_array($query = null)
	{
		if ( ! is_null($query)) {
			$this->query = $query;
			$this->query( );
		}

		$arr = array( );
		while ($row = @mysql_fetch_row($this->result)) {
			$arr[] = $row[0];
		}

		return $arr;
	}


	/** public function paginate NOT TESTED
	 *		Paginates a query result set based on supplied information
	 *		NOTE: It is not necessary to include the SQL_CALC_FOUND_ROWS
	 *		nor the LIMIT clause in the query, in fact, including the
	 *		LIMIT clause in the query will probably break MySQL.
	 *
	 * @param int [optional] current page number
	 * @param int [optional] number of records per page
	 * @param string [optional] SQL query string
	 * @return array pagination result and data
	 */
	public function paginate($page = null, $num_per_page = null, $query = null)
	{
		if ( ! is_null($page)) {
			$this->_page = $page;
		}
		else { // we don't have a page, either increment, or set equal to 1
			$this->_page = (isset($this->_page)) ? ($this->_page + 1) : 1;
		}

		if ( ! is_null($num_per_page)) {
			$this->_num_per_page = $num_per_page;
		}
		else {
			$this->_num_per_page = (isset($this->_num_per_page)) ? $this->_num_per_page : 50;
		}

		if ( ! $this->_page || ! $this->_num_per_page) {
			throw new MySQLException(__METHOD__.': No pagination data given');
		}

		if ( ! is_null($query)) {
			$this->_page_query = $query;

			// add the SQL_CALC_FOUND_ROWS keyword to the query
			if (false === strpos($query, 'SQL_CALC_FOUND_ROWS')) {
				$query = preg_replace('/SELECT\\s+(?!SQL_)/i', 'SELECT SQL_CALC_FOUND_ROWS ', $query);
			}

			$start = ($this->_num_per_page * ($this->_page - 1));

			// add the LIMIT clause to the query
			$query .= "
				LIMIT {$start}, {$this->_num_per_page}
			";

			$this->_page_result = $this->fetch_array($query);

			if ( ! $this->_page_result) {
				// no data found
				return array( );
			}

			$query = "
				SELECT FOUND_ROWS( ) AS count
			";
			$this->_num_results = $this->fetch_value($query);

			$this->_num_pages = (int) ceil($this->_num_results / $this->_num_per_page);
		}
		else { // we are using the previous data
			if ($this->_num_results < ($this->_num_per_page * ($this->_page - 1))) {
				return array( );
			}

			$query = $this->_page_query;

			$start = $this->_num_per_page * ($this->_page - 1);

			// add the LIMIT clause to the query
			$query .= "
				LIMIT {$start}, {$this->_num_per_page}
			";

			$this->_page_result = $this->fetch_array($query);

			if ( ! $this->_page_result) {
				// no data found
				return array( );
			}
		}

		// clean up the data and output to user
		$output = array( );
		$output['num_rows'] = $this->_num_results;
		$output['num_per_page'] = $this->_num_per_page;
		$output['num_pages'] = $this->_num_pages;
		$output['cur_page'] = $this->_page;
		$output['data'] = $this->_page_result;

		return $output;
	}


	/** public function fetch_insert_id
	 *		Return the insert id for the most recent query.
	 *
	 * @param void
	 * @return int previous insert id
	 */
	public function fetch_insert_id( )
	{
		return @mysql_insert_id($this->link_id);
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
		if (false && $this->_log_errors && class_exists('Log')) {
			Log::write($msg, __CLASS__);
		}
	}


	/** protected function _error_report
	 *		Report the errors
	 *
	 * @param void
	 * @action log errors
	 * @return void
	 */
	protected function _error_report( )
	{
		$this->_log($this->error);

		// generate an error report and then act according to configuration
		$error_report  = date('Y-m-d H:i:s')."\n\tAn error has been generated by the server.\n\tFollowing is the debug information:\n\n";

		// we don't need this function in the error report, just delete it
		$debug_array = debug_backtrace( );
		unset($debug_array[0]);

		// if a database query caused the error, show the query
		if ('' != $this->query) {
			$error_report .= "\t*     Query: {$this->query}\n";
		}

		$error_report .= "\t*     Error: {$this->error}\n";
		$error_report .= "\t* Backtrace: ".print_r($debug_array, true)."\n\n";

		// send the error as email if set
		if ($this->_email_errors && ('' != $this->_email_to) && ('' != $this->_email_from)) {
			mail($this->_email_to, trim($this->_email_subject), $error_report, 'From: '.$this->_email_from."\r\n");
		}

		// log the error
		if ($this->_log_errors) {
			$log = $this->_log_path.'mysql.err';
			$fp = fopen($log, 'a+');
			fwrite($fp, $error_report);
			@chmod($log, 0777);
			fclose($fp);
		}
	}


	/** protected function _get_backtrace
	 *		Grab the data for the file that called the mysql function
	 *
	 * @param void
	 * @return mixed array backtrace data, or bool false on failure
	 */
	protected function _get_backtrace( )
	{
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


	/** static public function get_instance
	 *		Returns the singleton instance
	 *		of the MySQL Object as a reference
	 *
	 * @param array optional configuration array
	 * @action optionally creates the instance
	 * @return MySQL Object reference
	 */
	static public function get_instance($config = null)
	{
		try {
			if (is_null(self::$_instance)) {
				self::$_instance = new Mysql($config);
			}

			self::$_instance->test_connection( );
			self::$_instance->_log(__METHOD__.' --------------------------------------');
		}
		catch (MySQLException $e) {
			throw $e;
		}

		return self::$_instance;
	}


	/** static public function test
	 *		Test the MySQL connection
	 *
	 * @param void
	 * @return bool connection OK
	 */
	static public function test( )
	{
		try {
			self::get_instance( );
			return true;
		}
		catch (MySQLException $e) {
			return false;
		}
	}


} // end of Mysql class


class MySQLException
	extends Exception {

	protected $_backtrace = true;

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
	public function __construct($message, $code = 1)
	{
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
		$log_name = 'mysql_exception_'.date('Ymd', time( )).'.log';

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

} // end of MySQLException class


/*
 +---------------------------------------------------------------------------
 |   > Extra SQL Functions
 +---------------------------------------------------------------------------
*/


// escape the data before it gets queried into the database

// NOTE: this function does NOT take any magic quotes into account
// it is therefore recommended to run something like the following
// before anything else is done in the script
/*

if (get_magic_quotes_gpc( )) {
	function stripslashes_deep($value) {
		$value = is_array($value)
			? array_map('stripslashes_deep', $value)
			: stripslashes($value);

		return $value;
	}

	$_POST = array_map('stripslashes_deep', $_POST);
	$_GET = array_map('stripslashes_deep', $_GET);
	$_COOKIE = array_map('stripslashes_deep', $_COOKIE);
	$_REQUEST = array_map('stripslashes_deep', $_REQUEST);
}

*/
if ( ! function_exists('sani')) {
	function sani($data) {
		if (is_array($data)) {
			return array_map('sani', $data);
		}
		else {
			return mysql_real_escape_string($data);
		}
	}
}

if ( ! function_exists('microtime_float')) {
	function microtime_float( ) {
		list($usec, $sec) = explode(' ', microtime( ));
		return ((float) $usec + (float) $sec);
	}
}

