<?php
/**
 * User: Mohammad Milad Naseri
 * Date: Dec 3, 2010
 * Time: 12:16:33 PM
 * @package com.bowfin.lib.dbxml
 */
if (!defined("COM_BOWFIN_LIB_DBXML")) {

	define("COM_BOWFIN_LIB_DBXML", "Bowfin Database XML Markup Support Library");

	/**
	 * Constants
	 */

	define("GLOBAL_CONTEXT_DBXML_HANDLES", "dbxml_handles");
	define("GLOBAL_CONTEXT_DBXML_HANDLERS", "dbxml_handlers");
	define("GLOBAL_CONTEXT_DBXML_CALLBACKS", "dbxml_callbacks");
	define("GLOBAL_CONTEXT_DBXML_PARAMETERS", "dbxml_parameters");
	define("GLOBAL_CONTEXT_DBXML_TABLES", "dbxml_tables");

	//Table scope
	define("DBXML_TABLE_LOCAL", 1);
	define("DBXML_TABLE_REFERENCE", 2);

	//Callback Action States
	define("DBXML_CALLBACK_BEFORE", "before");
	define("DBXML_CALLBACK_AFTER", "after");

	//Open options
	define("DBXML_OPEN_LAZY", 0);
	define("DBXML_OPEN_EAGER", 1);

	/**
	 * Global Variables
	 */

	globals_context_register(GLOBAL_CONTEXT_DBXML_HANDLES);
	globals_context_register(GLOBAL_CONTEXT_DBXML_HANDLERS);
	globals_context_register(GLOBAL_CONTEXT_DBXML_CALLBACKS);
	globals_context_register(GLOBAL_CONTEXT_DBXML_PARAMETERS);
	globals_context_register(GLOBAL_CONTEXT_DBXML_TABLES);

	function dbxml_register_handler($type, $handler) {
		globals_set(GLOBAL_CONTEXT_DBXML_HANDLERS, $type, $handler);
	}

	function dbxml_unregister_handler($type) {
		globals_set(GLOBAL_CONTEXT_DBXML_HANDLERS, $type, null);
	}

	function dbxml_call_handler($type, &$data) {
		$handler = globals_get(GLOBAL_CONTEXT_DBXML_HANDLERS, $type, null);
		if ($handler === null) {
			return null;
		}
		$handler($data);
	}

	/**
	 * @param string $file
	 * @param int $option
	 * @return int
	 */
	function dbxml_open($file, $option = DBXML_OPEN_EAGER) {
		if (!file_exists($file)) {
			trigger_error("File does not exist: " . $file, E_USER_ERROR);
		}
		$contents = file_get_contents($file);
		$contents = xml_from_string($contents);
		$handles = globals_context_get(GLOBAL_CONTEXT_DBXML_HANDLES);
		//        if (count($handles) == 0) {
		//            $handle = 0;
		//        } else {
		//            $handle = count($handles);
		//            $handle = $handles[$handle - 1] + 1;
		//        }
		$handle = count($handles);
		globals_set(GLOBAL_CONTEXT_DBXML_HANDLES, $handle, $contents);
		if ($option == DBXML_OPEN_EAGER) {
			$names = dbxml_table_names($handle);
			for ($i = 0; $i < count($names); $i++) {
				dbxml_table_definition($handle, $names[$i]['name']);
			}
		}
		return $handle;
	}

	/**
	 * @param  int $handle
	 * @return bool
	 */
	function dbxml_is_open($handle) {
		return globals_get(GLOBAL_CONTEXT_DBXML_HANDLES, $handle, null) !== null;
	}

	/**
	 * @param  int $handle
	 * @return void
	 */
	function dbxml_close($handle) {
		if (!dbxml_is_open($handle)) {
			trigger_error("Invalid handle: " . $handle, E_USER_ERROR);
		}
		globals_set(GLOBAL_CONTEXT_DBXML_HANDLES, $handle, null);
		globals_set(GLOBAL_CONTEXT_DBXML_TABLES, $handle, null);
	}

	/**
	 * @param int $handle
	 * @return array
	 */
	function dbxml_xml($handle) {
		if (!dbxml_is_open($handle)) {
			trigger_error("Invalid handle: " . $handle, E_USER_ERROR);
		}
		return globals_get(GLOBAL_CONTEXT_DBXML_HANDLES, $handle, null);
	}

	/**
	 * @param  int $handle
	 * @param int $switches
	 * @return array
	 */
	function dbxml_table_names($handle, $switches = DBXML_TABLE_LOCAL) {
		$local = (DBXML_TABLE_LOCAL & $switches) !== 0;
		$reference = (DBXML_TABLE_REFERENCE & $switches) !== 0;
		if ($local && !$reference) {
			$path = "database&/tables&/table&";
		} else if (!$local && $reference) {
			$path = "database&/tables&/reference&";
		} else if ($local && $reference) {
			$path = "database&/tables&/(table|reference)&";
		} else {
			trigger_error("Invalid table selection switch", E_USER_ERROR);
		}
		$data = xml_path(dbxml_xml($handle), $path);
		$result = array();
		for ($i = 0; $i < count($data); $i++) {
			array_push($result, array('type' => xml_get_node_name($data[$i]) == "table" ? DBXML_TABLE_LOCAL : DBXML_TABLE_REFERENCE, 'name' => xml_get_attribute($data[$i], 'name'), 'engine' => xml_has_attribute($data[$i], 'type') ? xml_get_attribute($data[$i], 'type') : ""));
		}
		return $result;
	}

	/**
	 * @param int $handle
	 * @param string $table
	 * @return array
	 */
	function dbxml_table_column_names($handle, $table) {
		$path = "database/tables/(table|reference)@name^='" . $table . "'";
		$names = xml_path(dbxml_xml($handle), $path);
		$names = xml_path($names, "columns^");
		$names = xml_path($names, "column@name");
		$result = array();
		for ($i = 0; $i < count($names); $i++) {
			array_push($result, $names[$i]['name']);
		}
		return $result;
	}

	/**
	 * @param int $handle
	 * @param string $table
	 * @param string $column
	 * @return array
	 */
	function dbxml_table_column_definition($handle, $table, $column) {
		$path = "database/tables/table@name^='" . $table . "'";
		$result = xml_path(dbxml_xml($handle), $path);
		$result = xml_path($result, "columns^");
		if (count($result) > 0) {
			$result = $result[0];
		}
		$result = xml_path($result, "columns&/column&@name&='$column'");
		if (count($result) == 0) {
			trigger_error("No such column defined ($column)", E_USER_ERROR);
		}
		if (count($result) > 1) {
			trigger_error("There are more than one columns with this name ($column). Please check the syntax of your XML file.", E_USER_ERROR);
		}
		$result = $result[0];
		$type = strtoupper(xml_get_attribute($result, 'type'));
		$default = xml_get_node_value($result['children']);
		if ($default == "") {
			$default = DATABASE_DEFAULT_VALUE;
		}
		if (xml_has_attribute($result, 'default')) {
			$default = xml_get_attribute($result, 'default', null);
		}
		if ($default !== null) {
			if (db_column_has_quotes($type)) {
				$default = db_escape($default);
				$default = "\"$default\"";
			}
		}
		$result = db_create_column($type, xml_has_attribute($result, 'length') ? xml_get_attribute($result, 'length') : DATABASE_DEFAULT_LENGTH, xml_has_attribute($result, 'nullable') ? xml_get_attribute($result, 'nullable') == "true" : DATABASE_DEFAULT_NULLABLE, $default);
		return $result;
	}

	/**
	 * @param int $handle
	 * @param string $table
	 * @param string $column
	 * @return array
	 */
	function dbxml_table_column_reference($handle, $table, $column) {
		$path = "database/tables/reference@name='" . $table . "'";
		$result = xml_path(dbxml_xml($handle), $path);
		$result = xml_path($result, "columns&/column&@name&='$column'");
		if (count($result) == 0) {
			trigger_error("No such column defined ($column)", E_USER_ERROR);
		}
		if (count($result) > 1) {
			trigger_error("There are more than one columns with this name ($column). Please check the syntax of your XML file.", E_USER_ERROR);
		}
		$result = $result[0];
		$result = array('name' => xml_get_attribute($result, 'name'), 'quoted' => xml_get_attribute($result, 'quoted') == "true");
		return $result;
	}

	/**
	 * @param int $handle
	 * @param string $table
	 * @return array
	 */
	function dbxml_table_collation($handle, $table) {
		$result = xml_path(dbxml_xml($handle), "tables/table@name='$table'");
		$result = xml_path($result, "collation@");
		$collation = db_create_collation(DATABASE_DEFAULT_CHARSET, DATABASE_DEFAULT_LANGUAGE, DATABASE_DEFAULT_CASE_SENSITIVITY);
		if (count($result) == 1) {
			$result = $result[0];
			$collation = db_create_collation($result['charset'], array_key_exists('language', $result) ? $result['language'] : DATABASE_DEFAULT_LANGUAGE, array_key_exists('caseSensitive', $result) ? ($result['caseSensitive'] == "true") : DATABASE_DEFAULT_CASE_SENSITIVITY);
		}
		return $collation;
	}

	/**
	 * @param  int $handle
	 * @param  string $table
	 * @return array
	 */
	function dbxml_table_constraints($handle, $table) {
		$result = xml_path(dbxml_xml($handle), "tables/table@name^='$table'");
		$result = xml_path($result, "constraint^");
		for ($i = 0; $i < count($result); $i++) {
			$attributes = xml_path($result[$i], "@");
			$attributes = $attributes[0];
			if (!array_key_exists('name', $attributes)) {
				$attributes['name'] = $i;
			}
			if ($attributes['type'] == "primaryKey") {
				$attributes['type'] = CC_PRIMARY_KEY;
			} else if ($attributes['type'] == "foreignKey") {
				$attributes['type'] = CC_FOREIGN_KEY;
			} else if ($attributes['type'] == "unique") {
				$attributes['type'] = CC_UNIQUE;
			} else {
				trigger_error("Unknown constraint type specified: " . $attributes['type'], E_USER_ERROR);
			}
			$columns = xml_path($result[$i], "columns&/column&");
			for ($j = 0; $j < count($columns); $j++) {
				$columns[$j] = xml_get_attribute($columns[$j], 'name');
			}
			$reference = null;
			if ($attributes['type'] == CC_FOREIGN_KEY) {
				$target = xml_path($result[$i], "reference@targetTable");
				$target = $target[0]['targetTable'];
				$reference = xml_path($result[$i], "reference/column@name");
				for ($j = 0; $j < count($reference); $j++) {
					$reference[$j] = $reference[$j]['name'];
				}
				$reference = db_create_fk_reference($target, $reference);
			}
			$result[$i] = db_create_constraint($attributes['type'], $columns, $reference);
		}
		return $result;
	}

	/**
	 * @param int $handle
	 * @param string $name
	 * @param bool $false_on_failure
	 * @return array|bool
	 */
	function dbxml_table_definition($handle, $name, $false_on_failure = false) {
		$tables = globals_get(GLOBAL_CONTEXT_DBXML_TABLES, $handle, null);
		if ($tables == null || !is_array($tables)) {
			$tables = array();
		}
		if (array_key_exists($name, $tables)) {
			return $tables[$name];
		}
		$table = xml_path(dbxml_xml($handle), "tables&/table&");
		$found = false;
		for ($i = 0; $i < count($table); $i++) {
			if (xml_get_node_name($table[$i]) == 'table' && xml_has_attribute($table[$i], 'name') && xml_get_attribute($table[$i], 'name') == $name
			) {
				$table = $table[$i];
				$found = true;
				break;
			}
		}
		if ($found === false) {
			if ($false_on_failure) {
				return false;
			}
			trigger_error("Cannot load table definition data for '$name'. Please check whether it " . "is an actual table or a reference you want to load", E_USER_ERROR);
		}
		$table = $table['attributes'];
		if ($table['type'] == "fast") {
			$table['type'] = DATABASE_TABLE_FAST;
		} else if ($table['type'] == "robust") {
			$table['type'] = DATABASE_TABLE_ROBUST;
		} else if ($table['type'] == "temporary") {
			$table['type'] = DATABASE_TABLE_TEMPORARY;
		} else {
			trigger_error("Invalid table type specified ($table[type]) for $table[name]");
		}
		$names = dbxml_table_column_names($handle, $name);
		for ($i = 0; $i < count($names); $i++) {
			$column = $names[$i];
			$table['columns'][$column] = dbxml_table_column_definition($handle, $name, $column);
		}
		$table['collation'] = dbxml_table_collation($handle, $name);
		$table['constraints'] = dbxml_table_constraints($handle, $name);
		$table['sequences'] = dbxml_table_sequences($handle, $name);
		$tables[$name] = $table;
		globals_set(GLOBAL_CONTEXT_DBXML_TABLES, $handle, $tables);
		return $table;
	}

	function dbxml_table_sequences($handle, $table) {
		$result = xml_path(dbxml_xml($handle), "tables&/table&/@name&='$table'");
		if (count($result) == 0) {
			return array();
		}
		$found = false;
		for ($i = 0; $i < count($result); $i++) {
			if (xml_get_node_name($result[$i]) == 'table') {
				$result = $result[$i];
				$found = true;
				break;
			}
		}
		if (!$found) {
			return array();
		}
		$result = xml_path($result, "sequence&/column&");
		$sequences = array();
		for ($i = 0; $i < count($result); $i++) {
			array_push($sequences, array('table' => $table, 'column' => xml_get_attribute($result[$i], 'name')));
		}
		return $sequences;
	}

	/**
	 * @param int $handle
	 * @param string $name
	 * @return array
	 */
	function dbxml_reference_definition($handle, $name) {
		$table = xml_path(dbxml_xml($handle), "tables/reference?@name^='$name'/columns/column@");
		if (count($table) == 0) {
			trigger_error("Cannot load table definition data for '$name'. Please check whether it " . "is an actual table or a reference you want to load", E_USER_ERROR);
		}
		$result = array();
		for ($i = 0; $i < count($table); $i++) {
			$result[$table[$i]['name']] = $table[$i]['quoted'] == "true";
		}
		return $result;
	}

	/**
	 * @param int $handle
	 * @return array
	 */
	function dbxml_procedure_names($handle) {
		$procedures = xml_path(dbxml_xml($handle), "procedures/procedure@name");
		for ($i = 0; $i < count($procedures); $i++) {
			$procedures[$i] = $procedures[$i]['name'];
		}
		return $procedures;
	}

	/**
	 * @param string $action
	 * @param int $state
	 * @param mixed $arguments
	 * @return bool
	 */
	function dbxml_procedure_callback($action, $state, &$arguments) {
		$callback = globals_get(GLOBAL_CONTEXT_DBXML_CALLBACKS, $action, null);
		$result = true;
		if ($callback != null && function_exists($callback)) {
			$result = $callback($action, $state, $arguments);
			if (!is_bool($result)) {
				$result = true;
			}
		}
		return $result;
	}

	/**
	 * @param string $action
	 * @param string|Closure $function
	 * @return void
	 */
	function dbxml_procedure_callback_register($action, $function) {
		globals_set(GLOBAL_CONTEXT_DBXML_CALLBACKS, $action, $function);
	}

	/**
	 * @param string $action
	 * @return void
	 */
	function dbxml_procedure_callback_unregister($action) {
		globals_set(GLOBAL_CONTEXT_DBXML_CALLBACKS, $action, null);
	}

	function dbxml_procedure_exists($handle, $procedure) {
		$steps = xml_path(dbxml_xml($handle), "procedures&/procedure&@name&='$procedure'");
		return count($steps) > 0;
	}

	/**
	 * @param int $handle
	 * @param string $procedure
	 * @param array $arguments
	 * @return void
	 */
	function dbxml_procedure_execute($handle, $procedure, &$arguments = array()) {
		$steps = xml_path(dbxml_xml($handle), "procedures&/procedure&@name&='$procedure'");
		if (count($steps) == 0) {
			trigger_error("Procedure does not exist ($procedure)");
		}
		$steps = $steps[0];
		$steps = $steps['children'];
		$parameters = array();
		$callback = globals_get(GLOBAL_CONTEXT_DBXML_CALLBACKS, "procedure", null);
		if (function_exists($callback)) {
			$callback($procedure, DBXML_CALLBACK_BEFORE, $arguments);
		}
		for ($i = 0; $i < count($steps); $i++) {
			$step = array('name' => $steps[$i]['name'], 'handle' => $handle, 'arguments' => $arguments, 'tag' => $steps[$i], 'parameters' => $parameters, 'break' => false);
			if (xml_has_attribute($steps[$i], 'id')) {
				$step['id'] = xml_get_attribute($steps[$i], 'id');
			}
			dbxml_procedure_execute_step($step);
			if ($step['break'] === true) {
				unset($step);
				break;
			}
			$parameters = $step['parameters'];
			unset($step);
		}
	}

	/**
	 * @param array $step
	 * @param string $code
	 * @param bool $safe
	 * @param bool $eval
	 * @return string
	 */
	function dbxml_handle_parameters($step, $code, $safe = false, $eval = false) {
		//Ensuring code safety
		if ($safe && !preg_match('/^\$\[[^\]]+\]$/mi', $code, $matches)) {
			return $code;
		}
		$parameters = $step['parameters'];
		if (!is_array($parameters)) {
			$parameters = array();
		}
		$parameters['__eval'] = $eval;
		//Stacking parameters to be used by the anonymous parser callback
		globals_set(GLOBAL_CONTEXT_DBXML_PARAMETERS, globals_size(GLOBAL_CONTEXT_DBXML_PARAMETERS), $parameters);
		//Mapping parameter names
		$code = preg_replace_callback('/\$\[([^\]]*?)\]/mi', function ($matches) {
			$parameters = globals_get(GLOBAL_CONTEXT_DBXML_PARAMETERS, globals_size(GLOBAL_CONTEXT_DBXML_PARAMETERS) - 1, null);
			$parameter = $matches[1];
			if (!array_key_exists($parameter, $parameters)) {
				trigger_error("No such parameter introduced ($matches[1])", E_USER_ERROR);
			}
			$parameter = str_replace("'", "\\'", $parameter);
			$value = "\$parameters['" . $parameter . "']";
			if ($parameters['__eval'] === true) {
				$value = eval("return " . $value . ";");
			}
			return $value;
		}, $code);
		//Unstacking parameters for current call
		globals_set(GLOBAL_CONTEXT_DBXML_PARAMETERS, globals_size(GLOBAL_CONTEXT_DBXML_PARAMETERS) - 1, null);
		return $code;
	}

	/**
	 * @param array $step
	 * @return bool
	 */
	function dbxml_procedure_execute_step(&$step) {
		$proceed = dbxml_procedure_callback($step['name'], DBXML_CALLBACK_BEFORE, $step);
		if (array_key_exists('id', $step)) {
			$step['parameters'][$step['id']] = null;
		}
		if (!$proceed) {
			return false;
		}
		$result = null;
		switch ($step['name']) {
			case "parameters":
				$tag = $step['tag'];
				$tag = $tag['children'];
				for ($i = 0; $i < count($tag); $i++) {
					if (xml_get_node_name($tag[$i]) == "parameter") {
						$value = null;
						if (xml_has_attribute($tag[$i], 'default')) {
							$value = xml_get_attribute($tag[$i], 'default');
						}
						$step['parameters'][xml_get_attribute($tag[$i], 'name')] = $value;
					}
				}
				break;
			case "break":
				$step['break'] = true;
				return;
			case "condition":
				$parameter = xml_get_attribute($step['tag'], 'parameter');
				$expected = xml_get_attribute($step['tag'], 'expect');
				$value = $step['parameters'][$parameter];
				if (is_bool($value)) {
					$value = $value ? "true" : "false";
				}
				if (strval($value) == strval($expected)) {
					$steps = $step['tag']['children'];
					for ($i = 0; $i < count($steps); $i++) {
						$substep = array('name' => $steps[$i]['name'], 'handle' => $step['handle'], 'arguments' => $step['arguments'], 'tag' => $steps[$i], 'parameters' => $step['parameters'], 'break' => false);
						if (xml_has_attribute($steps[$i], 'id')) {
							$substep['id'] = xml_get_attribute($steps[$i], 'id');
						}
						dbxml_procedure_execute_step($substep);
						if ($substep['break'] === true) {
							unset($substep);
							break;
						}
						$step['parameters'] = $substep['parameters'];
						unset($substep);
					}
				}
				break;
			case "php":
				$code = xml_get_child($step['tag'], 'code');
				if (!is_array($code)) {
					trigger_error('Invalid XML structure. PHP nodes should have CODE child.', E_USER_ERROR);
				}
				$code = xml_get_child($code, '#cdata');
				if (!is_array($code)) {
					trigger_error('Invalid XML structure. PHP code must be written as CDATA inside the XML.', E_USER_ERROR);
				}
				$code = xml_get_node_value($code);
				$code = dbxml_handle_parameters($step, $code, false);
				$parameters = $step['parameters'];
				$arguments = $step['arguments'];
				eval($code);
				$step['parameters'] = $parameters;
				$step['arguments'] = $arguments;
				break;
			case "execute":
				$procedure = xml_get_attribute($step['tag'], 'procedure');
				dbxml_procedure_execute($step['handle'], $procedure, $step);
				break;
			case "load":
				$columns = array();
				$criteria = array();
				$order = array();
				$distinct = xml_has_attribute($step['tag'], 'distinct') && xml_get_attribute($step['tag'], 'distinct') == "true";
				for ($i = 0; $i < count($step['tag']['children']); $i++) {
					$tag = $step['tag']['children'][$i];
					switch (xml_get_node_name($tag)) {
						case "columnSet":
							$table = xml_get_attribute($tag, 'targetTable');
							$selection = array();
							for ($j = 0; $j < count($tag['children']); $j++) {
								$column = $tag['children'][$j];
								if (xml_get_node_name($column) != "column") {
									trigger_error("Invalid XML syntax. Unexpected child node (" . xml_get_node_name($column) . ")", E_USER_ERROR);
								}
								$column = xml_get_attribute($column, 'name') . (xml_has_attribute($column, 'alias') ? " as " . xml_get_attribute($column, 'alias') : "");
								array_push($selection, $column);
							}
							$columns[$table] = $selection;
							unset($table);
							break;
						case "criteria":
							$criteria = array();
							for ($j = 0; $j < count($tag['children']); $j++) {
								$selector = $tag['children'][$j];
								if (xml_get_node_name($selector) != "selector") {
									trigger_error("Invalid XML syntax. Unexpected child node (" . xml_get_node_name($selector) . ")", E_USER_ERROR);
								}
								$table = dbxml_table_definition($step['handle'], xml_get_attribute($selector, 'targetTable'), true);
								$local = true;
								if ($table === false) {
									$local = false;
									$table = dbxml_reference_definition($step['handle'], xml_get_attribute($selector, 'targetTable'));
								}
								$operator = xml_has_attribute($selector, 'operator') ? xml_get_attribute($selector, 'operator') : "=";
								$operator = str_replace("&lt;", "<", $operator);
								$operator = str_replace("&gt;", ">", $operator);
								$value = trim(xml_get_node_value($selector['children']));
								$column = xml_get_attribute($selector, 'column');
								$value = dbxml_handle_parameters($step, $value, true, true);
								if ($local) {
									if (db_column_has_quotes($table['columns'][$column]['type'])) {
										$value = str_replace("'", "\\'", $value);
										$value = "'" . $value . "'";
									}
								} else {
									if ($table[$column]) {
										$value = str_replace("'", "\\'", $value);
										$value = "'" . $value . "'";
									}
								}
								$criteria[$column] = array($operator, $value);
							}
							break;
						case "order":
							$order = array();
							for ($j = 0; $j < count($tag['children']); $j++) {
								$ordering = $tag['children'][$j];
								if (xml_get_node_name($ordering) != "selector") {
									trigger_error("Invalid XML syntax. Unexpected child node (" . xml_get_node_name($ordering) . ")", E_USER_ERROR);
								}
								$order_name = xml_get_attribute($ordering, 'ordering');
								if ($order_name == "ascending") {
									$order_name = "ASC";
								} else if ($order_name == "descending") {
									$order_name = "DESC";
								} else {
									trigger_error("Invalid ordering specified (" . $order_name . ") for column '" . xml_get_attribute($ordering, 'column') . "'", E_USER_ERROR);
								}
								$order[xml_get_attribute($ordering, 'column')] = $order_name;
							}
							break;
						case "annotation":
							dbxml_procedure_callback("annotation", DBXML_CALLBACK_BEFORE, $step);
							break;
						default:
							trigger_error("Invalid XML syntax. Unexpected child node (" . xml_get_node_name($tag) . ")", E_USER_ERROR);
							break;
					}
				}
				$result = db_load($columns, $criteria, $order, $distinct);
				break;
			case "delete":
				$table_name = xml_get_attribute($step['tag'], 'targetTable');
				$table = dbxml_table_definition($step['handle'], $table_name, true);
				$local = true;
				if ($table === false) {
					$local = false;
					$table = dbxml_reference_definition($step['handle'], $table_name);
				}
				$criteria = array();
				for ($i = 0; $i < count($step['tag']['children']); $i++) {
					$tag = $step['tag']['children'][$i];
					if (xml_get_node_name($tag) == "criteria") {
						for ($j = 0; $j < count($tag['children']); $j++) {
							$selector = $tag['children'][$j];
							if (xml_get_node_name($selector) != "selector") {
								trigger_error("Invalid XML syntax. Unexpected child node (" . xml_get_node_name($selector) . ")", E_USER_ERROR);
							}
							$operator = xml_has_attribute($selector, 'operator') ? xml_get_attribute($selector, 'operator') : "=";
							$operator = str_replace("&lt;", "<", $operator);
							$operator = str_replace("&gt;", ">", $operator);
							$value = trim(xml_get_node_value($selector['children']));
							$column = xml_get_attribute($selector, 'column');
							$value = dbxml_handle_parameters($step, $value, true, true);
							if ($local) {
								if (db_column_has_quotes($table['columns'][$column]['type'])) {
									$value = str_replace("'", "\\'", $value);
									$value = "'" . $value . "'";
								}
							} else {
								if ($table[$column]) {
									$value = str_replace("'", "\\'", $value);
									$value = "'" . $value . "'";
								}
							}
							$criteria[$column] = array($operator, $value);
						}
					} else if (xml_get_node_name($tag) == 'annotation') {
						dbxml_procedure_callback("annotation", DBXML_CALLBACK_BEFORE, $step);
					} else {
						trigger_error("Invalid XML syntax. Unexpected child node (" . xml_get_node_name($tag) . ")", E_USER_ERROR);
					}
				}
				unset($table);
				$result = db_delete($table_name, $criteria);
				break;
			case "insert":
				$table_name = xml_get_attribute($step['tag'], 'targetTable');
				$values = array();
				$table = dbxml_table_definition($step['handle'], $table_name, true);
				$local = true;
				if ($table === false) {
					$local = false;
					$table = dbxml_reference_definition($step['handle'], $table_name);
				}
				for ($i = 0; $i < count($step['tag']['children']); $i++) {
					$tag = $step['tag']['children'][$i];
					if (xml_get_node_name($tag) == "value") {
						$value = trim(xml_get_node_value($tag['children']));
						$tag = xml_get_attribute($tag, 'column');
						$value = dbxml_handle_parameters($step, $value, true, true);
						if ($local) {
							if (db_column_has_quotes($table['columns'][$tag]['type'])) {
								$value = str_replace("'", "\\'", $value);
								$value = "'" . $value . "'";
							}
						} else {
							if ($table[$tag]) {
								$value = str_replace("'", "\\'", $value);
								$value = "'" . $value . "'";
							}
						}
						$values[$tag] = $value;
					} else if (xml_get_node_name($tag) == 'annotation') {
						dbxml_procedure_callback("annotation", DBXML_CALLBACK_BEFORE, $step);
					} else {
						trigger_error("Invalid XML syntax. Unexpected child node (" . xml_get_node_name($tag) . ")", E_USER_ERROR);
					}
				}
				unset($table);
				$result = db_insert($table_name, $values);
				break;
			case "update":
				$table_name = xml_get_attribute($step['tag'], 'targetTable');
				$values = array();
				$criteria = array();
				$table = dbxml_table_definition($step['handle'], $table_name, true);
				$local = true;
				if ($table === false) {
					$local = false;
					$table = dbxml_reference_definition($step['handle'], $table_name);
				}
				for ($i = 0; $i < count($step['tag']['children']); $i++) {
					$tag = $step['tag']['children'][$i];
					if (xml_get_node_name($tag) == "values") {
						for ($j = 0; $j < count($tag['children']); $j++) {
							$column = $tag['children'][$j];
							if (xml_get_node_name($column) != "column") {
								trigger_error("Invalid XML syntax. Unexpected child node (" . xml_get_node_name($column) . ")", E_USER_ERROR);
							}
							$value = trim(xml_get_node_value($column['children']));
							$column = xml_get_attribute($column, 'name');
							$value = dbxml_handle_parameters($step, $value, true, true);
							if ($local) {
								if (db_column_has_quotes($table['columns'][$column]['type'])) {
									$value = str_replace("'", "\\'", $value);
									$value = "'" . $value . "'";
								}
							} else {
								if ($table[$column]) {
									$value = str_replace("'", "\\'", $value);
									$value = "'" . $value . "'";
								}
							}
						}
						$values[$column] = $value;
					} else if (xml_get_node_name($tag) == 'criteria') {
						for ($j = 0; $j < count($tag['children']); $j++) {
							$selector = $tag['children'][$j];
							if (xml_get_node_name($selector) != "selector") {
								trigger_error("Invalid XML syntax. Unexpected child node (" . xml_get_node_name($selector) . ")", E_USER_ERROR);
							}
							$operator = xml_has_attribute($selector, 'operator') ? xml_get_attribute($selector, 'operator') : "=";
							$operator = str_replace("&lt;", "<", $operator);
							$operator = str_replace("&gt;", ">", $operator);
							$value = trim(xml_get_node_value($selector['children']));
							$column = xml_get_attribute($selector, 'column');
							$value = dbxml_handle_parameters($step, $value, true, true);
							if ($local) {
								if (db_column_has_quotes($table['columns'][$column]['type'])) {
									$value = str_replace("'", "\\'", $value);
									$value = "'" . $value . "'";
								}
							} else {
								if ($table[$column]) {
									$value = str_replace("'", "\\'", $value);
									$value = "'" . $value . "'";
								}
							}
							$criteria[$column] = array($operator, $value);
						}
					} else if (xml_get_node_name($tag) == 'annotation') {
						dbxml_procedure_callback("annotation", DBXML_CALLBACK_BEFORE, $step);
					} else {
						trigger_error("Invalid XML syntax. Unexpected child node (" . xml_get_node_name($tag) . ")", E_USER_ERROR);
					}
				}
				unset($table);
				$result = db_update($table_name, $values, $criteria);
				break;
			case "truncate":
				$table_name = xml_get_attribute($step['tag'], 'targetTable');
				$result = db_table_truncate($table_name);
				break;
			case "dropTable":
				$table_name = xml_get_attribute($step['tag'], 'targetTable');
				$result = db_table_drop($table_name);
				break;
			case "createTable":
				$table_name = xml_get_attribute($step['tag'], 'targetTable');
				$table = dbxml_table_definition($step['handle'], $table_name);
				$type = $table['type'];
				if ($type == "fast") {
					$type = DATABASE_TABLE_FAST;
				} else if ($type == "robust") {
					$type = DATABASE_TABLE_ROBUST;
				} else if ($type == "temporary") {
					$type = DATABASE_TABLE_TEMPORARY;
				} else {
					trigger_error("Invalid table type specified ($type)", E_USER_ERROR);
				}
				$result = array();
				$result['db_table_create'] = db_table_create($table_name, $table['columns'], $type);
				$result['db_table_constraint'] = db_table_constraint($table_name, $table['constraints']);
				$result['db_table_collate'] = db_table_collate($table_name, $table['collation']);
				$result['db_column_collate'] = array();
				foreach ($table['columns'] as $column => $definition) {
					$result['db_column_collate'][$column] = db_column_collate($table_name, $column, $table['collation']);
				}
				$result['db_table_sequences'] = array();
				for ($i = 0; $i < count($table['sequences']); $i++) {
					$sequence = $table['sequences'][$i];
					db_sequence_create($sequence['table'], $sequence['column'], "");
				}
				break;
			case "runQuery":
				$query = xml_get_child($step['tag'], 'query');
				$query = xml_get_node_value($query['children']);
				$query = dbxml_handle_parameters($step, $query, false, true);
				$result = db_query($query);
				break;
			case "call":
				$file = xml_get_attribute($step['tag'], 'location');
				if (!empty($file)) {
					require_once($file);
				}
				$function = xml_get_attribute($step['tag'], 'function');
				if (!function_exists($function)) {
					trigger_error("Call to non-existent function '$function'", E_USER_ERROR);
				}
				$parameters = $step['parameters'];
				$result = $function($parameters);
				$step['parameters'] = $parameters;
				break;
			case "assign":
				$step['parameters'][xml_get_attribute($step['tag'], 'parameter')] = xml_get_attribute($step['tag'], 'value');
				break;
			default:
				dbxml_call_handler($step['name'], $step);
		}
		if (array_key_exists('id', $step)) {
			$step['parameters'][$step['id']] = $result;
		}
		dbxml_procedure_callback($step['name'], DBXML_CALLBACK_AFTER, $step);
	}

}
?>