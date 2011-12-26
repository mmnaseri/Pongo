<?php
/**
 * pongo PHP Framework Kernel Component
 * Package SERVICE
 * @author Mohammad Milad Naseri (m.m.naseri at gmail.com)
 * @since 1.0 (6/12/11, 19:13)
 * @package com.agileapes.pongo.kernel.service
 */

if (!defined("KERNEL_COMPONENT_DATABASE_HANDLER")) {
	define('KERNEL_COMPONENT_DATABASE_HANDLER', "");

	db_set_handler("mysql");

	function dbh_identify($void) {
		return "mysql";
	}

	function dbh_get_default($key) {
		switch ($key) {
			case "username":
				return "root";
			case "port":
				return "3306";
			default:
				return "";
		}
	}

	function dbh_handle_parameter($arguments) {
		$parameter = $arguments['parameter'];
		$value = $arguments['value'];
		$parameter = $parameter . "=" . $value;
	}

	function dbh_select_db($db) {
		return @mysql_select_db($db);
	}

	function dbh_set_charset($arguments) {
		return @mysql_set_charset($arguments['charset'], $arguments['handle']);
	}

	function dbh_error_msg($link) {
		return @mysql_error($link);
	}

	function dbh_connect($connection) {
		$handle = @mysql_connect($connection['host'] . ":" . $connection['port'], $connection['username'], $connection['password'], true);
		return $handle;
	}

	function dbh_disconnect($arguments) {
		return @mysql_close($arguments['handle']);
	}

	function dbh_query($arguments) {
		$resource = @mysql_query($arguments['sql'], $arguments['handle']);
		if (db_error_msg() != "") {
			log_error(db_error_msg(), 'database');
			log_error($arguments['sql'], 'database');
		}
		return $resource;
	}

	function dbh_associate($arguments) {
		return @mysql_fetch_assoc($arguments['resource']);
	}

	function dbh_exists($arguments) {
		$sql = "SELECT COUNT(*) FROM `" . $arguments['table'] . "`";
		if (count($arguments['criteria']) != 0) {
			$sql .= " WHERE";
		}
		foreach ($arguments['criteria'] as $column => $value) {
			if (!is_array($value)) {
				$value = array("=", $value);
			}
			$sql .= " `" . $column . "` " . trim(strtoupper($value[0])) . " " . $value[1] . " AND";
		}
		if (substr($sql, strlen($sql) - 4, 4) == " AND") {
			$sql = substr($sql, 0, strlen($sql) - 4);
		}
		$sql .= ";";
		$resource = db_query($sql, $arguments['handle']);
		$row = @mysql_fetch_assoc($resource);
		return intval($row['COUNT(*)']) > 0;
	}

	function dbh_insert($arguments) {
		$sql = "INSERT INTO `" . $arguments['table'] . "` (";
		$values = "";
		foreach ($arguments['entity'] as $column => $value) {
			$sql .= "`" . $column . "`, ";
			$values .= $value . ", ";
		}
		if (!empty($values)) {
			$values = substr($values, 0, strlen($values) - 2);
			$sql = substr($sql, 0, strlen($sql) - 2);
		}
		$sql .= ") VALUES (" . $values . ");";
		db_query($sql, $arguments['handle']);
		return @mysql_affected_rows($arguments['handle']) > 0;
	}

	function dbh_update($arguments) {
		$sql = "UPDATE `" . $arguments['table'] . "` SET ";
		foreach ($arguments['entity'] as $column => $value) {
			$sql .= "`" . $column . "` = " . $value . ", ";
		}
		if (count($arguments['entity']) > 0) {
			$sql = substr($sql, 0, strlen($sql) - 2);
		}
		if (count($arguments['criteria']) != 0) {
			$sql .= " WHERE";
		}
		foreach ($arguments['criteria'] as $column => $value) {
			if (!is_array($value)) {
				$value = array("=", $value);
			}
			$sql .= " `" . $column . "` " . trim(strtoupper($value[0])) . " " . $value[1] . " AND";
		}
		if (substr($sql, strlen($sql) - 4, 4) == " AND") {
			$sql = substr($sql, 0, strlen($sql) - 4);
		}
		$sql .= ";";
		db_query($sql, $arguments['handle']);
		return @mysql_affected_rows($arguments['handle']) > 0;
	}

	function dbh_delete($arguments) {
		$sql = "DELETE FROM `" . $arguments['table'] . "`";
		if (count($arguments['entity']) != 0) {
			$sql .= " WHERE";
		}
		foreach ($arguments['entity'] as $column => $value) {
			if (!is_array($value)) {
				$value = array("=", $value);
			}
			$sql .= " `" . $column . "` " . trim(strtoupper($value[0])) . " " . $value[1] . " AND";
		}
		if (substr($sql, strlen($sql) - 4, 4) == " AND") {
			$sql = substr($sql, 0, strlen($sql) - 4);
		}
		$sql .= ";";
		db_query($sql, $arguments['handle']);
		return @mysql_affected_rows($arguments['handle']) > 0;
	}

	function dbh_load($arguments) {
		$tables = array();
		$sql = "SELECT ";
		if ($arguments['distinct']) {
			$sql .= "DISTINCT ";
		}
		foreach ($arguments['column_set'] as $table => $columns) {
			if (!is_string($table)) {
				$table = $columns;
				$columns = "*";
			}
			$table = db_get_option("prefix") . $table;
			if (!is_array($columns)) {
				$columns = array($columns);
			}
			array_push($tables, $table);
			for ($i = 0; $i < count($columns); $i++) {
				$column = $columns[$i];
				if (preg_match("/(\\(.*?\\)|\\*)/mi", $column)) {
					$sql .= $column . ", ";
					continue;
				}
				if (preg_match("/^(.*?)\\Was\\W(.*?)$/mi", $column, $matches)) {
					$column = $matches[1] . "` AS `" . $matches[2];
				}
				$sql .= "`" . $table . "`.`" . $column . "`, ";
			}
			if (count($columns) != 0) {
				$sql = substr($sql, 0, strlen($sql) - 2);
			}
		}
		$sql .= " FROM ";
		for ($i = 0; $i < count($tables); $i++) {
			$sql .= "`" . $tables[$i] . "`";
		}
		if (count($arguments['criteria']) != 0) {
			$sql .= " WHERE ";
			$conjunction = " " . $arguments['conjunction'];
			foreach ($arguments['criteria'] as $column => $value) {
				if (!is_string($column)) {
					$column = $value[2];
				}
				if (!is_array($value)) {
					$value = array("=", $value);
				}
				$sql .= " `" . $column . "` " . trim(strtoupper($value[0])) . " " . $value[1] . $conjunction;
			}
			if (substr($sql, strlen($sql) - strlen($conjunction), strlen($conjunction)) == $conjunction) {
				$sql = substr($sql, 0, strlen($sql) - strlen($conjunction));
			}
		}
		if (count($arguments['order']) != 0) {
			$sql .= " ORDER BY";
			foreach ($arguments['order'] as $column => $order) {
				$order = strtoupper($order);
				if ($order != "ASC" && $order != "DESC") {
					throw new DatabaseException("Invalid ordering for column '%1' (%2)", null, array($column, $order));
				}
				$sql .= " `" . $column . "` " . $order . ", ";
			}
			$sql = substr($sql, 0, strlen($sql) - 2);
		}
		$sql .= ";";
		$result_set = db_query($sql, $arguments['handle']);
		$result = array();
		while ($row = @mysql_fetch_assoc($result_set)) {
			array_push($result, $row);
		}
		return $result;
	}

	function dbh_database_create($arguments) {
		$sql = "CREATE DATABASE `" . $arguments['database'] . "`;";
		return db_query($sql, $arguments['handle']);
	}

	function dbh_database_drop($arguments) {
		$sql = "DROP DATABASE `" . $arguments['database'] . "`;";
		return db_query($sql, $arguments['handle']);
	}

	function dbh_table_exists($arguments) {
		$result = db_query("SHOW TABLES LIKE '$arguments[table]';", $arguments['handle']);
		return mysql_fetch_assoc($result) !== false;
	}

	function dbh_table_create($arguments) {
		$sql = "CREATE TABLE `" . $arguments['table'] . "` (\n";
		foreach ($arguments['columns'] as $column => $meta) {
			if (!array_key_exists("type", $meta)) {
				throw new DatabaseException("Column definition lacks data type (%1).", null, array($column));
			}
			if (!array_key_exists("length", $meta)) {
				$meta['length'] = DATABASE_DEFAULT_LENGTH;
			}
			if (!array_key_exists("nullable", $meta)) {
				$meta['nullable'] = DATABASE_DEFAULT_NULLABLE;
			}
			if (!array_key_exists("default", $meta)) {
				$meta['default'] = DATABASE_DEFAULT_VALUE;
			}
			$sql .= "\t`{$column}` " . $meta['type'];
			if (db_column_has_length($meta['type'])) {
				$sql .= "(" . $meta['length'] . ")";
			}
			if ($meta['nullable'] === false) {
				$sql .= " NOT NULL";
			}
			if ($meta['default'] !== null && !empty($meta['default'])) {
				$sql .= " DEFAULT " . $meta['default'];
			}
			$sql .= ",\n";
		}
		if (count($arguments['columns']) > 0) {
			$sql = substr($sql, 0, strlen($sql) - 2) . "\n";
		}
		$sql .= ") ENGINE = ";
		$sql .= dbh_get_engine_name($arguments['type']);
		$sql .= ";";
		return db_query($sql, $arguments['handle']);
	}

	function dbh_get_engine_name($type) {
		//MySQL specific
		switch ($type) {
			case DATABASE_TABLE_FAST:
				return "MyISAM";
				break;
			case DATABASE_TABLE_ROBUST:
				return "InnoDB";
				break;
			case DATABASE_TABLE_TEMPORARY:
				return "MEMORY";
				break;
		}
		throw new Exception("Invalid table type. No MySQL engine corresponds to table type '%1'", null, array($type));
	}

	function dbh_table_drop($arguments) {
		$sql = "DROP TABLE `" . $arguments['table'] . "`;";
		return db_query($sql, $arguments['handle']);
	}

	function dbh_table_truncate($arguments) {
		$sql = "TRUNCATE TABLE `" . $arguments['table'] . "`;";
		return db_query($sql, $arguments['handle']);
	}

	function dbh_table_collate($arguments) {
		$collation = $arguments['collation'];
		$sql = "ALTER TABLE `" . $arguments['table'] . "` COLLATE " . $collation['encoding'] . "_" . $collation['language'] . "_" . ($collation['ignore_case'] === true ? "ci" : "cs") . ";";
		return db_query($sql, $arguments['handle']);
	}

	function dbh_table_constraint($arguments) {
		$result = true;
		foreach ($arguments['constraints'] as $name => $constraint) {
			if (!is_string($name)) {
				if ($constraint['type'] == CC_UNIQUE) {
					$name = "uq_" . $name;
				} else if ($constraint['type'] == CC_PRIMARY_KEY) {
					$name = "pk_" . $name;
				} else if ($constraint['type'] == CC_FOREIGN_KEY) {
					$name = "fk_" . $name;
				}
				$name = $arguments['table'] . "_" . $name;
			}
			$sql = "ALTER TABLE `" . $arguments['table'] . "`\nADD CONSTRAINT `";
			$sql .= $name . "`\n";
			$sql .= $constraint['type'] . "(";
			for ($i = 0; $i < count($constraint['columns']); $i++) {
				$sql .= "`" . $constraint['columns'][$i] . "`, ";
			}
			$sql = substr($sql, 0, strlen($sql) - 2);
			$sql .= ")";
			if ($constraint['type'] == CC_FOREIGN_KEY) {
				$sql .= "\nREFERENCES `";
				$sql .= $constraint['reference']['table'];
				$sql .= "` (";
				for ($i = 0; $i < count($constraint['reference']['columns']); $i++) {
					$sql .= "`" . $constraint['reference']['columns'][$i] . "`, ";

				}
				$sql = substr($sql, 0, strlen($sql) - 2);
				$sql .= ")";
			}
			$sql .= ";\n";
			$result = $result && (db_query($sql, $arguments['handle']) !== false);
		}
		if (!empty($sql)) {
			$sql = substr($sql, 0, strlen($sql) - 1);
		}
		return $result;
	}

	function dbh_column_collate($arguments) {
		$result = db_query("SHOW COLUMNS FROM `" . $arguments['table'] . "` WHERE `field` = '" . $arguments['column'] . "'", $arguments['handle']);
		$meta = array("type" => "", "nullable" => true, "key" => false, "default" => "NULL",);
		$row = mysql_fetch_assoc($result);
		if ($row === false) {
			throw new DatabaseException("No such column found for table '%1' (%2)", null, array($arguments['table'], $arguments['column']));
		}
		foreach ($row as $key => $value) {
			$key = strtolower($key);
			if ($key == "type") {
				$meta['type'] = strtoupper($value);
			}
			if ($key == "null") {
				$meta['nullable'] = strtoupper($value) != "NO";
			}
			if ($key == "key") {
				$meta['key'] = !empty($value);
			}
			if ($key == "default") {
				$meta['default'] = $value;
			}
		}
		$type = $meta['type'];
		$type = explode("(", $type, 2);
		$type = trim(strtoupper($type[0]));
		$sql = "ALTER TABLE `" . $arguments['table'] . "` CHANGE `" . $arguments['column'] . "` ";
		$sql .= "`" . $arguments['column'] . "` " . $meta['type'];
		$sql .= " COLLATE " . $arguments['collation']['encoding'] . "_" . $arguments['collation']['language'] . "_";
		$sql .= $arguments['collation']['ignore_case'] ? "ci" : "cs";
		if (!$meta['nullable']) {
			$sql .= " NOT";
		}
		$sql .= " NULL";
		if (db_column_has_quotes($type)) {
			$meta['default'] = '"' . db_escape($meta['default']) . '"';
		}
		if (!empty($meta['default'])) {
			$sql .= " DEFAULT " . $meta['default'];
		}
		return db_query($sql, $arguments['handle']);
	}

	function dbh_sequence_create($arguments) {
		$result = db_query("SHOW COLUMNS FROM `" . $arguments['table'] . "` WHERE `field` = '" . $arguments['column'] . "'", $arguments['handle']);
		$meta = array("type" => "", "key" => false, "nullable" => true);
		$row = mysql_fetch_assoc($result);
		if ($row === false) {
			throw new DatabaseException("No such column found for table '%1' (%2)", null, array($arguments['table'], $arguments['column']));
		}
		foreach ($row as $key => $value) {
			$key = strtolower($key);
			$value = strtoupper($value);
			if ($key == "type") {
				$meta['type'] = $value;
			}
			if ($key == "null") {
				$meta['nullable'] = $value != "NO";
			}
			if ($key == "key") {
				$meta['key'] = $value == "PRI";
			}
		}
		if ($meta['type'] == "") {
			throw new DatabaseException("Could not discern column type for `%1`@`%2`.", null, array($arguments['column'], $arguments['table']));
		}
		if ($meta['nullable'] === true) {
			throw new DatabaseException("Cannot assign auto-generated values to nullable column `%1`@`%2`", null, array($arguments['column'], $arguments['table']));
		}
		if ($meta['key'] === false) {
			throw new DatabaseException("Cannot assign auto-generated values to non-key column `%1`@`%2`", null, array($arguments['column'], $arguments['table']));
		}
		$sql = "ALTER TABLE `" . $arguments['table'] . "` CHANGE `" . $arguments['column'] . "` ";
		$sql .= "`" . $arguments['column'] . "` " . $meta['type'] . " NOT NULL AUTO_INCREMENT";
		return db_query($sql, $arguments['handle']);
	}

}
?>