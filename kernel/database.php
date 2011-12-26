<?php
/**
 * pongo PHP Framework Kernel Component
 * Package SERVICE
 * @author Mohammad Milad Naseri (m.m.naseri at gmail.com)
 * @since 1.0 (6/12/11, 19:13)
 * @package com.agileapes.pongo.kernel.database
 */
if (!defined("KERNEL_COMPONENT_DATABASE")) {

	/***
	 * Kernel Component Registration
	 **/
	define('KERNEL_COMPONENT_DATABASE', "");

	/***
	 * Kernel Component Implementation
	 **/

	define('KCE_DATABASE', 6000);

	class DatabaseException extends KernelException {

		protected function getBase() {
			return KCE_DATABASE;
		}

	}

	/**
	 * Constants
	 */

	define("GLOBALS_CONTEXT_DB_CALLBACK", "db_callback");
	define("GLOBALS_CONTEXT_DB_OPTIONS", "db_options");

	//Handler
	define("DATABASE_HANDLER_DIR", "dbh");
	define("DATABASE_HANDLER_FUNCTION_PREFIX", "dbh_");

	//Table Types
	define("DATABASE_TABLE_FAST", "fast");
	define("DATABASE_TABLE_ROBUST", "robust");
	define("DATABASE_TABLE_TEMPORARY", "temporary");

	//Column Types
	define("DATABASE_CT_CHAR", "CHAR");
	define("DATABASE_CT_VARCHAR", "VARCHAR");
	define("DATABASE_CT_TEXT", "TEXT");
	define("DATABASE_CT_DATE", "DATE");
	define("DATABASE_CT_TIME", "TIME");
	define("DATABASE_CT_DATETIME", "DATETIME");
	define("DATABASE_CT_TINYINT", "TINYINT");
	define("DATABASE_CT_BYTE", "BYTE");
	define("DATABASE_CT_INT", "INT");
	define("DATABASE_CT_BIGINT", "BIGINT");
	define("DATABASE_CT_FLOAT", "FLOAT");
	define("DATABASE_CT_BOOLEAN", "BOOLEAN");

	//Column Types Shorthands
	define("CT_CHAR", DATABASE_CT_CHAR);
	define("CT_VARCHAR", DATABASE_CT_VARCHAR);
	define("CT_TEXT", DATABASE_CT_TEXT);
	define("CT_DATE", DATABASE_CT_DATE);
	define("CT_TIME", DATABASE_CT_TIME);
	define("CT_DATETIME", DATABASE_CT_DATETIME);
	define("CT_TINYINT", DATABASE_CT_TINYINT);
	define("CT_BYTE", DATABASE_CT_BYTE);
	define("CT_INT", DATABASE_CT_INT);
	define("CT_BIGINT", DATABASE_CT_BIGINT);
	define("CT_FLOAT", DATABASE_CT_FLOAT);
	define("CT_BOOLEAN", DATABASE_CT_BOOLEAN);

	//Column Definition Defaults
	define("DATABASE_DEFAULT_LENGTH", 255);
	define("DATABASE_DEFAULT_NULLABLE", true);
	define("DATABASE_DEFAULT_VALUE", null);

	//Column constraints
	define("DATABASE_CC_UNIQUE", "UNIQUE");
	define("DATABASE_CC_PRIMARY_KEY", "PRIMARY KEY");
	define("DATABASE_CC_FOREIGN_KEY", "FOREIGN KEY");

	//Column constraint shorthands
	define("CC_UNIQUE", DATABASE_CC_UNIQUE);
	define("CC_PRIMARY_KEY", DATABASE_CC_PRIMARY_KEY);
	define("CC_FOREIGN_KEY", DATABASE_CC_FOREIGN_KEY);

	//Event states
	define("DATABASE_ON_BEFORE_ACTION", 0);
	define("DATABASE_ON_AFTER_ACTION", 1);

	//Collation
	define("DATABASE_DEFAULT_CHARSET", "utf8");
	define("DATABASE_DEFAULT_LANGUAGE", "general");
	define("DATABASE_DEFAULT_CASE_SENSITIVITY", false);

	/**
	 * Global Variables
	 */
	$_db_handler = "";
	$_db_connection_handle = null;

	globals_context_register(GLOBALS_CONTEXT_DB_CALLBACK);
	globals_context_register(GLOBALS_CONTEXT_DB_OPTIONS);

	/**
	 * @param  string $option
	 * @param  string$value
	 * @return void
	 */
	function db_set_option($option, $value) {
		globals_set(GLOBALS_CONTEXT_DB_OPTIONS, $option, $value);
	}

	/**
	 * @param  string $option
	 * @return mixed
	 */
	function db_get_option($option) {
		$option = globals_get(GLOBALS_CONTEXT_DB_OPTIONS, $option);
		return $option;
	}

	/**
	 * Registers a new callback hook for action <code>$action</code>
	 * <strong>NB: </strong>Only one callback can be registered for each action during a complete
	 * application life-cycle
	 * @param  string $action Callback's target trigger
	 * @param  string $callback Name of the callback function
	 * @return void
	 */
	function db_register_callback($action, $callback) {
		globals_set(GLOBALS_CONTEXT_DB_CALLBACK, $action, $callback);
	}

	/**
	 * Removes the callback - if registered - from <code>$action</code>
	 * @param  string $action
	 * @return void
	 */
	function db_unregister_callback($action) {
		globals_set(GLOBALS_CONTEXT_DB_CALLBACK, $action, null);
	}

	/**
	 * Loads the requested database handler
	 * The location for the handler is expected to be <code>&lt;this files path&gt;/DATABASE_HANDLER_DIR/$handle.php</code>
	 * @param  string $handler
	 * @return void
	 */
	function db_load_handler($handler) {
		$file = __FILE__;
		if (!preg_match("/(^.*?)(\/[^\/]*?)?$/mi", $file, $matches)) {
			throw new DatabaseException("Could not discern file path");
		}
		$file = $matches[1];
		$file = $file . "/" . DATABASE_HANDLER_DIR . "/" . $handler . ".php";
		if (!file_exists($file)) {
			throw new DatabaseException("Requested handler does not exist (%1)", null, array($handler));
		} else {
			/** @noinspection PhpIncludeInspection */
			require($file);
		}
	}

	/**
	 * Parses the given connection string and returns a parameterized array,
	 * providing the same information.<br/>
	 * @param  string $connection_string
	 * @return array
	 * <strong>Connection Strings</strong>
	 * Connection strings are matched against the following pattern:<br/>
	 * <code>db:{handler name}://[{database server username}@]{database server host}[:{database port}][/{initial database}][?{config parameters}]</code><br/>
	 * The only mandatory arguments are <code>host</code> and <code>handler</code>.
	 * If any of the other values are missing, the handler is queried for a default value by calling to
	 * <code>dbh_get_default</code> upon establishing a connection using <code>db_connect</code>.
	 * <strong>Config Parameters</strong>
	 * Currently recognized config parameters include:
	 * <ul>
	 * <li>characterEncoding: triggers a call to <code>db_set_charset</code></li>
	 * </ul>
	 */
	function db_parse_string($connection_string) {
		$matches = array();
		if (!preg_match("|^db:([^:]+)://(?:([^@:]+)(?::([^@]+))@)?([^/:]+)(?::(\\d+))(?:/([^\\?]+))?(?:\\?(.*))$|mi", $connection_string, $matches)) {
			throw new DatabaseException("Connection string is not valid");
		}
		$parameters = array();
		if (array_key_exists(7, $matches)) {
			$parameters_list = explode("&", $matches[7]);
			for ($i = 0; $i < count($parameters_list); $i++) {
				$parameters_list[$i] = explode("=", $parameters_list[$i]);
				if (count($parameters_list[$i]) != 2) {
					throw new DatabaseException("Malformed parameter string: %1", null, array($matches[7]));
				}
				$parameters[urldecode($parameters_list[$i][0])] = urldecode($parameters_list[$i][1]);
			}
		}
		$result = array("handler" => $matches[1], "username" => array_get($matches, 2, 'root'), "password" => array_get($matches, 3, ''), "host" => $matches[4], "port" => array_get($matches, 5, ""), "database" => array_get($matches, 6, ""), "parameters" => $parameters);
		return $result;
	}

	/**
	 * Creates a connection string using given parameters.
	 * @param  string $handler
	 * @param  string $host
	 * @param string $port
	 * @param string $username
	 * @param string $password
	 * @param string $database
	 * @param array $parameters
	 * @return string
	 */
	function db_create_string($handler, $host, $port = "", $username = "", $password = "", $database = "", $parameters = array()) {
		$str = "db:" . $handler . "://";
		if (!empty($username)) {
			$str .= $username;
			if (!empty($password)) {
				$str .= ":" . $password;
			}
			$str .= "@";
		}
		if (empty($host)) {
			throw new DatabaseException("Database host cannot be empty.");
		}
		$str .= $host;
		if (!empty($port)) {
			$str .= ":" . $port;
		}
		if (!empty($database)) {
			$str .= "/" . $database;
		}
		if (is_array($parameters) && count($parameters) > 0) {
			$str .= "?";
			foreach ($parameters as $parameter => $value) {
				$str .= urlencode($parameter) . "=" . urlencode($value) . "&";
			}
			$str = substr($str, 0, strlen($str) - 1);
		}
		return $str;
	}

	/**
	 * Returns the name of the current handler
	 * @return string
	 */
	function db_current_handler() {
		global $_db_handler;
		return $_db_handler;
	}

	/**
	 * Used by handlers to identify themselves globally
	 * @param  string $handler
	 * @return void
	 */
	function db_set_handler($handler) {
		global $_db_handler;
		$_db_handler = $handler;
	}

	/**
	 * Calls requested function from current handler using given arguments.
	 * @param  string $function
	 * @param  array $arguments
	 * @param bool $report
	 * @return mixed
	 * It is calling this method that causes a trigger for callbacks.
	 */
	function db_call_handler($function, $arguments, $report = false) {
		$action = $function;
		$callback = globals_get(GLOBALS_CONTEXT_DB_CALLBACK, $function);
		if ($callback == null || ((is_string($callback) && !function_exists($callback)) && !is_object($callback))) {
			$callback = null;
		}
		$function = DATABASE_HANDLER_FUNCTION_PREFIX . $function;
		if (!function_exists($function)) {
			if ($report) {
				return -1;
			}
			throw new DatabaseException("Call to requested handler function (%1) failed.", null, array($function));
		}
		if ($callback != null) {
			if (is_array($arguments)) {
				$arguments['_action'] = $action;
				$arguments['_event'] = DATABASE_ON_BEFORE_ACTION;
			}
			$act = $callback($action, DATABASE_ON_BEFORE_ACTION, $arguments);
			if (!is_bool($act)) {
				$act = true;
			}
			if (!$act) {
				return false;
			}
		}
		$result = $function($arguments);
		if ($callback != null) {
			if (is_array($arguments)) {
				$arguments['_event'] = DATABASE_ON_AFTER_ACTION;
				$arguments['_result'] = $result;
			}
			$callback($action, DATABASE_ON_AFTER_ACTION, $arguments);
			$result = $arguments['_result'];
		}
		return $result;
	}

	/**
	 * Establishes a connection to the database server following the rules specified by the given connection string
	 * @param  string $connection_string
	 * @param bool $default If <code>true</code> the handle to this connection will be made available globally as the
	 * default database connection.
	 * @param bool $report
	 * @internal param string $password Password for connection (matching the username provided via the connection string)
	 * @return int The handle to established connection
	 */
	function db_connect($connection_string, $default = true, $report = false) {
		$connection = db_parse_string($connection_string);
		db_load_handler($connection['handler']);
		$identification = db_call_handler("identify", null, $report);
		if ($report && $identification == -1) {
			return -3;
		}
		if (db_current_handler() != $connection['handler'] || $identification != $connection['handler']
		) {
			/*
			* A correct implementation of handler would configure the system so that
			* it is acknowledged as a handler by calling "db_set_handler()"
			*/
			throw new DatabaseException("There was a problem loading database handler");
		}
		if (empty($connection['username'])) {
			$connection['username'] = db_call_handler("get_default", "username");
		}
		if (empty($connection['port'])) {
			$connection['port'] = db_call_handler("get_default", "port");
		}
		$link = db_call_handler("connect", $connection);
		if ($link === false) {
			if ($report) {
				return -1;
			}
			throw new DatabaseException("There was a problem connecting to database server: " . db_error_msg());
		}
		if ($default) {
			global $_db_connection_handle;
			$_db_connection_handle = $link;
		}
		foreach ($connection['parameters'] as $parameter => $value) {
			switch ($parameter) {
				case "characterEncoding":
					db_set_charset($value, $link);
					break;
				default:
					db_call_handler("handle_parameter", array("parameter" => $parameter, "value" => $value));
			}
		}
		if (!empty($connection['database'])) {
			if (db_switch($connection['database']) === false) {
				if ($report) {
					return -2;
				}
				throw new DatabaseException("Error selecting database (%1): %2", null, array($connection['database'], db_error_msg()));
			}
		}
		return $link;
	}

	/**
	 * Returns error message reported by the handler for specified connection
	 * @param  int $handle
	 * @return string
	 */
	function db_error_msg($handle = null) {
		if ($handle === null) {
			$handle = db_get_default_handle();
		}
		return db_call_handler("error_msg", $handle);
	}

	/**
	 * Switches to given database on the server
	 * @param string $db Database name
	 * @return bool Operation success
	 */
	function db_switch($db) {
		return db_call_handler("select_db", $db);
	}

	function db_set_charset($charset = DATABASE_DEFAULT_CHARSET, $handle = null) {
		if ($handle === null) {
			$handle = db_get_default_handle();
		}
		return db_call_handler("set_charset", array("handle" => $handle, "charset" => $charset));
	}

	/**
	 * Returns the handle to default database connection
	 * @return string
	 */
	function db_get_default_handle() {
		global $_db_connection_handle;
		return $_db_connection_handle;
	}

	/**
	 * Disconnects the specified connection
	 * @param  int $handle
	 * @return bool
	 */
	function db_disconnect($handle = null) {
		if ($handle === null) {
			$handle = db_get_default_handle();
		}
		return db_call_handler("disconnect", array('handle' => $handle));
	}

	//Data-oriented functions

	/**
	 * Checks whether any item matching given criteria exists in the specified table or not.
	 * @param  string $table
	 * @param array $criteria
	 * @param  int $handle
	 * @return bool
	 */
	function db_exists($table, $criteria = array(), $handle = null) {
		if (!is_array($criteria)) {
			throw new DatabaseException("Criteria must be an array");
		}
		if ($handle === null) {
			$handle = db_get_default_handle();
		}
		$table = db_get_option("prefix") . $table;
		return db_call_handler("exists", array('handle' => $handle, 'table' => $table, 'criteria' => $criteria));
	}

	/**
	 * Inserts a new item unto table.
	 * @param  string $table
	 * @param  array $entity
	 * @param  int $handle
	 * @return mixed
	 */
	function db_insert($table, $entity, $handle = null) {
		if (!is_array($entity)) {
			throw new DatabaseException("Entity must be an array");
		}
		if ($handle === null) {
			$handle = db_get_default_handle();
		}
		$table = db_get_option("prefix") . $table;
		return db_call_handler("insert", array('handle' => $handle, 'table' => $table, 'entity' => $entity));
	}

	/**
	 * Updates any item matching <code>$criteria</code> to values specified in <code>$entity</code>
	 * @param  string $table
	 * @param  array $entity
	 * @param array $criteria
	 * @param  int $handle
	 * @return mixed
	 */
	function db_update($table, $entity, $criteria = array(), $handle = null) {
		if (!is_array($entity)) {
			throw new DatabaseException("Entity must be an array");
		}
		if (!is_array($criteria)) {
			throw new DatabaseException("Criteria must be an array");
		}
		if ($handle === null) {
			$handle = db_get_default_handle();
		}
		$table = db_get_option("prefix") . $table;
		return db_call_handler("update", array('handle' => $handle, 'table' => $table, 'entity' => $entity, 'criteria' => $criteria));
	}

	/**
	 * Executes the given SQL command over the specified database connection
	 * @param string $sql
	 * @param  int $handle
	 * @return mixed
	 */
	function db_query($sql, $handle = null) {
		if ($handle === null) {
			$handle = db_get_default_handle();
		}
		$sql = str_replace('#prefix#', db_get_option('prefix'), $sql);
		return db_call_handler("query", array("handle" => $handle, "sql" => $sql));
	}

	/**
	 * Returns an associative array representing the current row of given resource
	 * @param resource $resource
	 * @return mixed
	 */
	function db_associate($resource) {
		return db_call_handler("associate", array("resource" => $resource));
	}

	/**
	 * Deletes any records matching <code>$entity</code> from <code>$table</code>
	 * @param  string $table
	 * @param  array $entity
	 * @param  int $handle
	 * @return mixed
	 */
	function db_delete($table, $entity, $handle = null) {
		if (!is_array($entity)) {
			throw new DatabaseException("Entity must be an array");
		}
		if ($handle === null) {
			$handle = db_get_default_handle();
		}
		$table = db_get_option("prefix") . $table;
		return db_call_handler("delete", array('handle' => $handle, 'table' => $table, 'entity' => $entity,));
	}

	/**
	 * Loads <code>$column_set</code> for any records matching <code>$criteria</code>, ordered by <code>$order</code>
	 * @param  array $column_set
	 * @param array $criteria
	 * @param array $order
	 * @param bool $distinct
	 * @param string $conjunction
	 * @param int $handle
	 * @return mixed
	 */
	function db_load($column_set, $criteria = array(), $order = array(), $distinct = false, $conjunction = "AND", $handle = null) {
		if ($handle === null) {
			$handle = db_get_default_handle();
		}
		if (!is_array($column_set)) {
			$column_set = array($column_set);
		}
		return db_call_handler("load", array("column_set" => $column_set, "criteria" => $criteria, "order" => $order, "distinct" => $distinct, "conjunction" => $conjunction, "handle" => $handle));
	}

	//Structural functions

	/**
	 * Creates new database <code>$database</code>, if the user is privilleged to.
	 * @param  string $database
	 * @param  int $handle
	 * @return mixed
	 */
	function db_database_create($database, $handle = null) {
		if ($handle === null) {
			$handle = db_get_default_handle();
		}
		return db_call_handler("database_create", array('handle' => $handle, 'database' => $database));
	}

	/**
	 * Drops specified <code>$database</code>
	 * @param  string $database
	 * @param  int $handle
	 * @return mixed
	 */
	function db_database_drop($database, $handle = null) {
		if ($handle === null) {
			$handle = db_get_default_handle();
		}
		return db_call_handler("database_drop", array('handle' => $handle, 'database' => $database));
	}

	/**
	 * Determines whether a specified table exists within the selected database or not
	 * @param string $table
	 * @param int $handle
	 * @return bool
	 */
	function db_table_exists($table, $handle = null) {
		if ($handle === null) {
			$handle = db_get_default_handle();
		}
		$table = db_get_option("prefix") . $table;
		return db_call_handler("table_exists", array("table" => $table, "handle" => $handle));
	}

	/**
	 * Creates the table over currently active database over connection <code>$handle</code>
	 * @param  string $table
	 * @param  array $columns
	 * @param string $type
	 * @param  int $handle
	 * @return mixed
	 */
	function db_table_create($table, $columns, $type = DATABASE_TABLE_ROBUST, $handle = null) {
		if ($handle === null) {
			$handle = db_get_default_handle();
		}
		if (!is_array($columns)) {
			throw new DatabaseException("Columns must be given as an array");
		}
		if ($type != DATABASE_TABLE_ROBUST && $type != DATABASE_TABLE_FAST && $type != DATABASE_TABLE_TEMPORARY) {
			throw new DatabaseException("Table type is unknown (%1). Refer to DATABASE_TABLE_* constants", null, array($type));
		}
		$table = db_get_option("prefix") . $table;
		return db_call_handler("table_create", array("table" => $table, "columns" => $columns, "type" => $type, "handle" => $handle));
	}

	/**
	 * Sets the collation for specified table to <code>$collation</code>
	 * @param  string $table
	 * @param  array $collation
	 * @param  int $handle
	 * @return mixed
	 */
	function db_table_collate($table, $collation, $handle = null) {
		if ($handle === null) {
			$handle = db_get_default_handle();
		}
		$table = db_get_option("prefix") . $table;
		return db_call_handler("table_collate", array("handle" => $handle, "table" => $table, "collation" => $collation));
	}

	/**
	 * Adds the constraints specified in <code>$constraints</code> to <code>$table</code>
	 * @param  string $table
	 * @param  array $constraints
	 * @param  int $handle
	 * @return mixed
	 */
	function db_table_constraint($table, $constraints, $handle = null) {
		if ($handle === null) {
			$handle = db_get_default_handle();
		}
		if (!is_array($constraints)) {
			throw new DatabaseException("Constraints must be given as an array");
		}
		$table = db_get_option("prefix") . $table;
		return db_call_handler("table_constraint", array('table' => $table, 'handle' => $handle, 'constraints' => $constraints));
	}

	/**
	 * Drops the specified table
	 * @param  string $table
	 * @param  int $handle
	 * @return mixed
	 */
	function db_table_drop($table, $handle = null) {
		if ($handle === null) {
			$handle = db_get_default_handle();
		}
		$table = db_get_option("prefix") . $table;
		return db_call_handler("table_drop", array("handle" => $handle, "table" => $table));
	}

	/**
	 * Truncates (deletes all data from) the specified table
	 * @param  string $table
	 * @param  int $handle
	 * @return mixed
	 */
	function db_table_truncate($table, $handle = null) {
		if ($handle === null) {
			$handle = db_get_default_handle();
		}
		$table = db_get_option("prefix") . $table;
		return db_call_handler("table_truncate", array("handle" => $handle, "table" => $table));
	}

	/**
	 * Sets collation for specified column
	 * @param  string $table
	 * @param  string $column
	 * @param  array $collation
	 * @param  int $handle
	 * @return mixed
	 */
	function db_column_collate($table, $column, $collation, $handle = null) {
		if ($handle === null) {
			$handle = db_get_default_handle();
		}
		$table = db_get_option("prefix") . $table;
		return db_call_handler("column_collate", array("handle" => $handle, "table" => $table, "column" => $column, "collation" => $collation));
	}

	/**
	 * Creates a sequence and assigns it to table.column
	 * @param  string $table
	 * @param  string $column
	 * @param  string $sequence
	 * @param  int $handle
	 * @return mixed
	 */
	function db_sequence_create($table, $column, $sequence, $handle = null) {
		if ($handle === null) {
			$handle = db_get_default_handle();
		}
		$table = db_get_option("prefix") . $table;
		return db_call_handler("sequence_create", array("handle" => $handle, "table" => $table, "column" => $column, "sequence" => $sequence));
	}

	/**
	 * Generates valid collation meta-data for given parameters.
	 * @param  string $encoding
	 * @param  string $language
	 * @param bool $case_sensitive
	 * @return array
	 */
	function db_create_collation($encoding, $language, $case_sensitive = DATABASE_DEFAULT_CASE_SENSITIVITY) {
		return array("encoding" => $encoding, "language" => $language, "ignore_case" => $case_sensitive === false);
	}

	/**
	 * Generates valid column definition meta-data.
	 * @param string $type
	 * @param int $length
	 * @param bool $nullable
	 * @param mixed $default
	 * @return array
	 */
	function db_create_column($type, $length = DATABASE_DEFAULT_LENGTH, $nullable = DATABASE_DEFAULT_NULLABLE, $default = DATABASE_DEFAULT_VALUE) {
		return array("type" => $type, "length" => $length, "nullable" => $nullable, "default" => $default);
	}

	/**
	 * Generates a valid set of constraint definition meta-data.
	 * @param  string $type
	 * @param  array $columns
	 * @param  array $reference
	 * @return array
	 */
	function db_create_constraint($type, $columns, $reference = null) {
		if (!is_array($columns)) {
			$columns = array($columns);
		}
		if (count($columns) == 0) {
			throw new DatabaseException("Constraints must target at least one column");
		}
		sort($columns);
		$type = strtoupper($type);
		if ($type != CC_UNIQUE && $type != CC_PRIMARY_KEY && $type != CC_FOREIGN_KEY) {
			throw new DatabaseException("Unknown constraint type specified (%1)", null, array($type));
		}
		if ($type == CC_FOREIGN_KEY && ($reference === null || !is_array($reference))) {
			throw new DatabaseException("Foriegn key constraint requires a valid reference. Non given.");
		}
		if ($type == CC_FOREIGN_KEY && count($columns) > 1) {
			throw new DatabaseException("Foreign key constraints must target exactly one column, %1 specified.", null, array(count($columns)));
		}
		return array('type' => $type, 'columns' => $columns, 'reference' => $reference);
	}

	/**
	 * Generates necessary meta-data for a foreign key reference to used in <code>db_create_constraint</code>
	 * @param  string $table
	 * @param  array $columns
	 * @return array
	 */
	function db_create_fk_reference($table, $columns) {
		if (!is_array($columns)) {
			$columns = array($columns);
		}
		if (count($columns) == 0) {
			throw new DatabaseException("References must target at least one column");
		}
		sort($columns);
		$table = db_get_option("prefix") . $table;
		return array('table' => $table, 'columns' => $columns);
	}

	/**
	 * Determines whether a certain SQL data type requires LENGTH as a mandatory parameter
	 * @param  $type
	 * @return bool
	 */
	function db_column_has_length($type) {
		$type = strtoupper($type);
		return $type == CT_VARCHAR || $type == CT_CHAR;
	}

	/**
	 * Determines whether a certain SQL data type requires quoted values
	 * @param  string $type
	 * @return bool
	 */
	function db_column_has_quotes($type) {
		$type = strtoupper($type);
		return $type == CT_TEXT || $type == CT_VARCHAR || $type == CT_CHAR;
	}

	/**
	 * Escapes string input to avoid SQL injection
	 * @param  string $value
	 * @param bool $update
	 * @return string
	 */
	function db_escape($value, $update = false) {
		$value = str_replace("\\", "\\\\", $value);
		$value = str_replace("'", "\\'", $value);
		$value = str_replace("\"", "\\\"", $value);
		if ($update) {
			$value = str_replace("%", "\\%", $value);
		}
		return $value;
	}

	/**
	 * Returns a quoted, escaped version of the given input
	 * @param $value
	 * @return string
	 */
	function db_quote($value) {
		$value = db_escape($value);
		return '"' . $value . '"';
	}

}
?>