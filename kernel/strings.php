<?php

/**
 * pongo PHP Framework Kernel Component
 * Package STRINGS
 * @author Mohammad Milad Naseri (m.m.naseri at gmail.com)
 * @since 1.0 (9/12/11, 20:53)
 * @package com.agileapes.pongo.kernel.strings
 */

if (!defined("KERNEL_COMPONENT_STRINGS")) {

	/***
	 * Kernel Component Registration
	 **/
	define('KERNEL_COMPONENT_STRINGS', "");

	/***
	 * Kernel Component Implementation
	 **/

	define('KCE_STRINGS', 8000);

	class StringsException extends KernelException {

		protected function getBase() {
			return KCE_STRINGS;
		}

		protected function getBaseCode() {
			return 1;
		}

	}

	define("STRINGS_ZIP_PREFIX", "!GZ");
	define("STRINGS_USE_COMPRESSION", false);
	define("GLOBAL_CONTEXT_STRINGS_GLOBALS", "strings_global");

	function strings_from_string($string) {
		$string = str_replace("'", "\\'", $string);
		$string = "'" . $string . "'";
		return $string;
	}

	/**
	 * @param array $array
	 * @param int $beautify
	 * @return string
	 */
	function strings_from_array($array, $beautify = -1) {
		$str = "array(";
		if ($beautify >= 0) {
			$str .= "\n";
		}
		foreach ($array as $key => $value) {
			if ($beautify >= 0) {
				$str .= str_repeat("\t", $beautify + 1);
			}
			if (is_string($key)) {
				//                $str .= "'" . str_replace("'", "\\'", $key) . "'";
				$str .= strings_from_string($key);
			} else {
				$str .= $key;
			}
			if ($beautify >= 0) {
				$str .= " => ";
			} else {
				$str .= "=>";
			}
			if (is_array($value)) {
				$str .= strings_from_array($value, $beautify >= 0 ? $beautify + 1 : -1);
			} else if (is_string($value)) {
				$str .= strings_from_string($value);
			} else if (is_null($value)) {
				$str .= "null";
			} else if (is_bool($value)) {
				$str .= $value ? "true" : "false";
			} else {
				$str .= strval($value);
			}
			$str .= ",";
			if ($beautify >= 0) {
				$str .= "\n";
			}
		}
		while (strlen($str) > 0 && $str[strlen($str) - 1] == "\n") {
			$str = substr($str, 0, strlen($str) - 1);
		}
		if (strlen($str) > 0 && $str[strlen($str) - 1] == ",") {
			$str = substr($str, 0, strlen($str) - 1);
		}
		if ($beautify >= 0 && count($array) > 0) {
			$str .= "\n";
		}
		if ($beautify >= 0) {
			$str .= str_repeat("\t", $beautify);
		}
		$str .= ")";
		return $str;
	}

	/**
	 * @param mixed $subject
	 * @param bool $compress
	 * @return string
	 */
	function strings_serialize($subject, $compress = STRINGS_USE_COMPRESSION) {
		if (is_null($subject)) {
			$result = "null";
		} else if (is_array($subject)) {
			$result = strings_from_array($subject);
		} else if (is_string($subject)) {
			$result = strings_from_string($subject);
		} else if (is_bool($subject)) {
			$result = $subject ? "true" : "false";
		} else if (is_numeric($subject)) {
			$result = strval($subject);
		} else {
			throw new StringsException("Unknown data type applied for serialization");
		}
		if ($compress && function_exists("gzcompress")) {
			$result = gzdeflate($result, 9);
			$result = STRINGS_ZIP_PREFIX . $result;
		}
		return $result;
	}

	/**
	 * @param string $string
	 * @param bool $decompress
	 * @return mixed
	 */
	function strings_deserialize($string, $decompress = STRINGS_USE_COMPRESSION) {
		//Removing BOM (courtesy of 'http://blog.philipp-michels.de/?p=32')
		if (substr($string, 0, 3) == pack("CCC", 0xef, 0xbb, 0xbf)) {
			$string = substr($string, 3);
		}
		if ($decompress && strlen($string) >= strlen(STRINGS_ZIP_PREFIX) && substr($string, 0, strlen(STRINGS_ZIP_PREFIX)) == STRINGS_ZIP_PREFIX
		) {
			if (function_exists("gzinflate")) {
				$string = substr($string, 3);
				$string = gzinflate($string);
			} else {
				throw new StringsException("Could not decompress previously compressed string");
			}
		}
		$string = trim($string);
		if (empty($string)) {
			$string = "null";
		}
		$string = "return " . $string . ";";
		return eval($string);
	}

	function strings_vformat($format, $arguments) {
		globals_context_register(GLOBAL_CONTEXT_STRINGS_GLOBALS, true);
		globals_set(GLOBAL_CONTEXT_STRINGS_GLOBALS, 'args', $arguments);
		$format = preg_replace_callback('/%([\da-z]+)/msi', function ($matches) {
			$args = globals_get(GLOBAL_CONTEXT_STRINGS_GLOBALS, "args");
			if (intval($matches[1]) > 0) {
				$matches = intval($matches[1]) - 1;
			} else {
				$matches = $matches[1];
			}
			return $args[$matches];
		}, $format);
		globals_context_unregister(GLOBAL_CONTEXT_STRINGS_GLOBALS, true);
		return $format;
	}

	function strings_format($format) {
		$args = array();
		for ($i = 1; $i < func_num_args(); $i++) {
			array_push($args, func_get_arg($i));
		}
		return strings_vformat($format, $args);
	}

	function strings_annotation_decode($contents) {
		$contents = explode("\n", $contents);
		$result = array();
		for ($i = 0; $i < count($contents); $i++) {
			if (!preg_match('/@(\S+)\s+(.*?)$/msi', $contents[$i], $matches)) {
				continue;
			}
			$result[$matches[1]] = strings_annotation_deserialize(trim($matches[2]));
		}
		return $result;
	}

	function strings_annotation_deserialize($value) {
		if (preg_match('/^\{(.*?)\}$/msi', $value, $matches)) {
			$value = $matches[1];
			$result = array();
			$item = "";
			$quote = "";
			for ($i = 0; $i < strlen($value); $i++) {
				if ($value[$i] == '{') {
					$x = 1;
					$i++;
					$item = trim($item);
					$key = null;
					if ($item != '') {
						if (strpos($item, '=') !== false) {
							$item = explode('=', $item, 2);
							$key = $item[0];
						} else {
							array_push($result, $item);
						}
					}
					$item = "{";
					while ($x > 0 && $i < strlen($value)) {
						if ($value[$i] == '{') {
							$x++;
						}
						if ($value[$i] == '}') {
							$x--;
						}
						$item .= $value[$i];
						$i++;
					}
					if (trim($item) != '') {
						$item = strings_annotation_deserialize(trim($item));
						if ($key === null) {
							array_push($result, $item);
						} else {
							$result[$key] = $item;
						}
						$item = "";
					}
				} else if ($value[$i] == ',' && empty($quote)) {
					$item = trim($item);
					if ($item != '') {
						if (strpos($item, '=') !== false) {
							$item = explode('=', $item, 2);
							$result[$item[0]] = $item[1];
						} else {
							array_push($result, $item);
						}
					}
					$item = "";
				} else {
					if (strpos("\"'`", $value[$i]) !== false) {
						if (empty($quote)) {
							$quote = $value[$i];
						} else {
							$quote = "";
						}
					} else {
						$item .= $value[$i];
					}
				}
			}
			$item = trim($item);
			if ($item != '') {
				if (strpos($item, '=') !== false) {
					$item = explode('=', $item, 2);
					$result[$item[0]] = $item[1];
				} else {
					array_push($result, $item);
				}
			}
			return $result;
		}
		return $value;
	}

}

?>