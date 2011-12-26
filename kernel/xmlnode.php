<?php
/**
 * User: Mohammad Milad Naseri
 * Date: Dec 3, 2010
 * Time: 12:16:33 PM
 * @package com.bowfin.lib.xmlnode
 */
if (!defined("COM_BOWFIN_LIB_XMLNODE")) {

	define("COM_BOWFIN_LIB_XMLNODE", "Bowfin Enhanced XML Functions");

	/**
	 * Constants
	 */

	/**
	 * @param string $string
	 * @param bool $ignore_white_space
	 * @return array
	 */
	function xmlnode_from_string($string, $ignore_white_space = true, $number_text_nodes = false, $no_empty_nodes = false) {
		return xmlnode_from_xml(xml_from_string($string, $ignore_white_space), true, $number_text_nodes, $no_empty_nodes);
	}

	/**
	 * @param string $file
	 * @param bool $ignore_white_space
	 * @return array
	 */
	function xmlnode_from_file($file, $ignore_white_space = true, $number_text_nodes = false, $no_empty_nodes = false) {
		return xmlnode_from_xml(xml_from_file($file, $ignore_white_space), true, $number_text_nodes, $no_empty_nodes);
	}

	/**
	 * @param array $tree
	 * @param bool $include_root
	 * @return array
	 */
	function xmlnode_from_xml($tree, $include_root = true, $number_text_nodes = false, $no_empty_nodes = false) {
		if (!is_array($tree) || !array_key_exists('name', $tree)) {
			if (is_array($tree)) {
				foreach ($tree as $key => $tag) {
					$tree[$key] = xmlnode_from_xml($tag, $include_root, $number_text_nodes);
				}
				return $tree;
			} else {
				trigger_error("Invalid XML node given", E_USER_ERROR);
			}
		}
		$name = xml_get_node_name($tree);
		if ($name[0] != '#') {
			$result = array();
			foreach ($tree['attributes'] as $attribute => $value) {
				$result['@' . $attribute] = $value;
			}
			for ($i = 0; $i < count($tree['children']); $i++) {
				$child = $tree['children'][$i];
				$child_name = xml_get_node_name($child);
				$x = 0;
				if ($number_text_nodes || $child_name[0] != '#') {
					while (array_key_exists($child_name . "[$x]", $result)) {
						$x++;
					}
					$child_name .= '[' . $x . ']';
				}
				$result[$child_name] = xmlnode_from_xml($child, false, $number_text_nodes);
				if ($no_empty_nodes && is_array($result[$child_name]) && count($result[$child_name]) == 0) {
					if ($number_text_nodes) {
						$result[$child_name] = array('#text[0]' => '');
					} else {
						$result[$child_name] = array('#text' => '');
					}
				}
			}
		} else {
			$result = xml_get_node_value($tree);
		}
		if ($include_root) {
			if ($no_empty_nodes && is_array($result) && count($result) == 0) {
				if ($number_text_nodes) {
					$result = array('#text[0]' => '');
				} else {
					$result = array('#text' => '');
				}
			}
			return array($name . '[0]' => $result);
		} else {
			return $result;
		}
	}

	/**
	 * @param array $node
	 * @return array
	 */
	function xmlnode_to_xml($node) {
	}

	/**
	 * @param array $node
	 * @param int $level
	 * @param bool $no_ws
	 * @return string
	 */
	function xmlnode_to_string($node, $level = 0, $no_ws = false) {
		$src = "";
		$nl = "\n";
		$tab = "\t";
		$ind = str_repeat($tab, $level);
		if ($no_ws) {
			$nl = "";
			$tab = "";
			$ind = "";
		}
		foreach ($node as $key => $tag) {
			if (is_numeric($key)) {
				$src .= xmlnode_to_string($tag, $level, $no_ws) . $nl;
			} else if ($key[0] != '@') {
				$name = xmlnode_get_node_name($key);
				if ($name[0] == '#') {
					if ($name == XML_NT_TEXT) {
						$src .= $ind . $tag;
					} else if ($name == XML_NT_CDATA) {
						$src .= $ind . "<![CDATA[" . $tag . "]]>";
					} else if ($name == XML_NT_COMMENT) {
						$src .= $ind . "<!--" . $tag . "-->";
					}
					continue;
				}
				$src .= $ind;
				$src .= "<" . $name;
				if (is_array($tag)) {
					$count = 0;
					foreach ($tag as $attribute => $value) {
						if ($attribute[0] == '@') {
							$count++;
							$src .= " " . substr($attribute, 1) . "=";
							$q = "'";
							if (strpos("'", $value) !== false) {
								$q = '"';
							}
							$src .= $q . $value . $q;
						} else {
							break;
						}
					}
					if ($count == count($tag)) {
						$src .= " />" . $nl;
						$name = null;
					} else {
						$src .= ">" . $nl;
					}
					if ($name !== null) {
						foreach ($tag as $attribute => $value) {
							if ($attribute[0] != '@') {
								$src .= xmlnode_to_string(array($attribute => $value), $level + 1, $no_ws) . $nl;
							}
						}
						$src .= $ind . "</" . $name . ">" . $nl;
					}
				}
			}
		}
		return $src;
	}

	/**
	 * @param string $path
	 * @return array
	 */
	function xmlnode_path_tokenize($path) {
		$first = "";
		$rest = "";
		$quotes = "";
		$saw_equals = false;
		$arguments = "";
		$quoted = false;
		for ($i = 0; $i < strlen($path); $i++) {
			if ($arguments != "") {
				$arguments .= $path[$i];
				if ($path[$i] == ")") {
					$arguments = "";
				}
			} else if ($quotes != "") {
				if ($path[$i] == $quotes) {
					$quotes = "";
					$quoted = true;
				}
			} else {
				if ($quoted && $path[$i] != "/") {
					trigger_error("Unexpected character ($path[$i]) at position $i of path `$path`", E_USER_ERROR);
				}
				if ($path[$i] == "/") {
					if ($saw_equals) {
						trigger_error("Unexpected '$path[$i]' at position $i of path `$path`", E_USER_ERROR);
					}
					$rest = substr($path, $i + 1);
					break;
				} else if ($path[$i] == "=") {
					if (strlen($first) == 0 || $first[0] != "@") {
						trigger_error("Unexpected assignment at position $i of path `$path`", E_USER_ERROR);
					}
					if (!$saw_equals) {
						$saw_equals = true;
					} else {
						trigger_error("Unexpected '$path[$i]' at position $i of path `$path`", E_USER_ERROR);
					}
				} else if (strpos("'\"`", $path[$i]) !== false) {
					if ($saw_equals) {
						$saw_equals = false;
						$quotes = $path[$i];
					} else {
						trigger_error("Unexpected <$path[$i]> at position $i of path `$path`", E_USER_ERROR);
					}
				} else if ($path[$i] == "(") {
					if (strlen($first) == 0 || $first[0] != "$") {
						trigger_error("Unexpected argument specification at position $i of path `$path`", E_USER_ERROR);
					}
					if ($arguments == "") {
						$arguments = "(";
					} else {
						trigger_error("Unexpected '$path[$i]' at position $i of path `$path`", E_USER_ERROR);
					}
				}
			}
			$first .= $path[$i];
		}
		$first = trim($first);
		$rest = trim($rest);
		if ($quotes != "") {
			trigger_error("Unterminated string constant in path `$path`", E_USER_ERROR);
		}
		if ($arguments != "") {
			trigger_error("Unterminated argument specification in path `$path`", E_USER_ERROR);
		}
		if (empty($first)) {
			$first = xmlnode_path_tokenize($rest);
			$rest = $first['rest'];
			$first = "/" . $first['first'];
			if ($first[1] == "/") {
				trigger_error("Invalid path syntax near token `$first`.", E_USER_ERROR);
			}
		}
		return array('first' => trim($first), 'rest' => trim($rest));
	}

	/**
	 * @param string $path
	 * @return array
	 */
	function xmlnode_path_parameters($path) {
		//It's probably a multi-node path, and anyway, it can't hurt to recompile it.
		$path = xmlnode_path_tokenize($path);
		$path = $path['first'];
		$parameters = array( //            'path' => $path,
			'type' => '', //node, numeric, indexed, semantic, attribute
			'absolute' => false, //true, false
			'name' => "", //for "node", "indexed", "attribute", and "semantic" types
			'index' => -1, //for "numeric" and "indexed"
			'value' => "", //for "attribute" type
			'arguments' => "", //for "semantic" types
		);
		if (empty($path)) {
			trigger_error("Cannot discern parameters for an empty path", E_USER_ERROR);
		}
		if ($path[0] == "/") {
			$parameters['absolute'] = true;
			$path = substr($path, 1);
		}
		if (preg_match('/^\$(.*)$/mi', $path, $matches)) {
			$parameters['type'] = "semantic";
			$parameters['name'] = trim($matches[1]);
			if (!$parameters['absolute']) {
				trigger_error("Semantic path elements must be specified as an absolute node.", E_USER_ERROR);
			}
			if (!preg_match('/(.+)\((.*)\)/mi', $parameters['name'], $matches)) {
				trigger_error("Invalid semantic argument ($parameters[name])", E_USER_ERROR);
			}
			$parameters['name'] = trim($matches[1]);
			$parameters['arguments'] = trim($matches[2]);
			//Extracting arguments
			$arguments = array();
			$saw_comma = strlen(trim($parameters['arguments'])) > 0;
			$quote = "";
			$argument = "";
			for ($i = 0; $i < strlen($parameters['arguments']); $i++) {
				if ($quote != "") {
					if ($parameters['arguments'][$i] == $quote) {
						$quote = "";
						$saw_comma = false;
						array_push($arguments, trim($argument));
						$argument = "";
						continue;
					}
				} else {
					if (strpos("\"'`", $parameters['arguments'][$i]) !== false) {
						if (!$saw_comma) {
							trigger_error("Unexpected quotation at position $i of argument declaration: " . $parameters['arguments'], E_USER_ERROR);
						}
						$quote = $parameters['arguments'][$i];
						continue;
					} else if ($parameters['arguments'][$i] == ",") {
						if ($saw_comma) {
							array_push($arguments, trim($argument));
							$argument = "";
						}
						$saw_comma = true;
						continue;
					}
				}
				$argument .= $parameters['arguments'][$i];
			}
			if ($quote != "") {
				trigger_error("Unterminated string constant.", E_USER_ERROR);
			}
			$argument = trim($argument);
			if ($argument != "") {
				array_push($arguments, $argument);
			} else if ($saw_comma) {
				trigger_error("Expected argument after comma", E_USER_ERROR);
			}
			$parameters['arguments'] = $arguments;
			if (empty($parameters['name'])) {
				trigger_error("Semantic path elements must have a valid name", E_USER_ERROR);
			}
		} else if (preg_match('/^\%(.*)$/mi', $path, $matches)) {
			$parameters['type'] = "numeric";
			$parameters['index'] = $matches[1];
			if (!$parameters['absolute']) {
				trigger_error("Numeric path elements must be specified as an absolute node.", E_USER_ERROR);
			}
			if (!preg_match('/^\+?\d+$/', $parameters['index'])) {
				trigger_error("Numeric element references must have a valid index ($parameters[index] given)", E_USER_ERROR);
			}
			$parameters['index'] = intval($parameters['index']);
			if ($parameters['index'] < 0) {
				trigger_error("Numeric reference indices must be positive integers ($parameters[index] given)", E_USER_ERROR);
			}
		} else if (preg_match('/^@([^=]+)(=(\'|"|`)(.*)\3)?$/mi', $path, $matches)) {
			$parameters['type'] = "attribute";
			$parameters['name'] = $matches[1];
			if (count($matches) < 5) {
				$matches[4] = ".*";
			}
			$parameters['value'] = $matches[4];
		} else if (preg_match('/^([a-z]+.*)\[(.+)\]$/mi', $path, $matches)) {
			$parameters['type'] = "indexed";
			$parameters['name'] = $matches[1];
			$parameters['index'] = $matches[2];
			if (!preg_match('/^\+?\d+$/', $parameters['index'])) {
				trigger_error("Numeric element references must have a valid index ($parameters[index] given)", E_USER_ERROR);
			}
			$parameters['index'] = intval($parameters['index']);
			if ($parameters['index'] < 0) {
				trigger_error("Numeric reference indices must be positive integers ($parameters[index] given)", E_USER_ERROR);
			}
		} else if (preg_match('/^([#a-z]+[^\[\]]*)$/mi', $path, $matches)) {
			$parameters['type'] = "node";
			$parameters['name'] = $matches[1];
		} else {
			trigger_error("Invalid path node ($path)");
		}
		return $parameters;
	}

	/**
	 * @param array $tree
	 * @param string $path
	 * @return array
	 */
	function xmlnode_path($tree, $path) {
		$path = xmlnode_path_tokenize($path);
		if (empty($path['first'])) {
			trigger_error("Path cannot be empty.", E_USER_ERROR);
		}
		$parameters = xmlnode_path_parameters($path['first']);
		$agenda = array();
		switch ($parameters['type']) {
			case "node":
				$parameters['index'] = '\d+';
			case "indexed":
				foreach ($tree as $element => $children) {
					if (preg_match('/^' . $parameters['name'] . '\[' . $parameters['index'] . '\]' . '$/mi', $element)) {
						array_push($agenda, $children);
					} else if (!$parameters['absolute'] && is_array($children)) {
						$exploration = xmlnode_path($children, $path['first']);
						if (is_array($exploration) && count($exploration) > 0) {
							for ($i = 0; $i < count($exploration); $i++) {
								array_push($agenda, $exploration[$i]);
							}
						}
					}
				}
				break;
			case "numeric":
				$i = 0;
				foreach ($tree as $element => $children) {
					if ($element[0] != '@' && $i == $parameters['index']) {
						array_push($agenda, $children);
						$i = -1;
						break;
					}
					if ($element[0] != '@') {
						$i++;
					}
				}
				if ($i != -1) {
					//It's not been found = Index out of bounds
					trigger_error("Index out of bounds ($parameters[index])", E_USER_ERROR);
				}
				break;
			case "attribute":
				if (!is_array($tree)) {
					break;
				}
				foreach ($tree as $element => $children) {
					if ($element[0] != '@' || !is_string($children)) {
						//We're not concerned with anything but attributes
						if (!$parameters['absolute']) {
							$exploration = xmlnode_path($children, $path['first']);
							if (is_array($exploration) && count($exploration) > 0) {
								for ($i = 0; $i < count($exploration); $i++) {
									array_push($agenda, $exploration[$i]);
								}
							}
						}
					} else if (preg_match('/^@' . $parameters['name'] . '$/mi', $element)) {
						if (empty($parameters['value']) || preg_match('/^' . $parameters['value'] . '$/mi', $children)) {
							array_push($agenda, $tree);
						}
					}
				}
				break;
			case "semantic":
				switch ($parameters['name']) {
					case "first":
						foreach ($tree as $element => $children) {
							if ($element[0] != '@') {
								//it's the first non-attribute node.
								array_push($agenda, $children);
								break;
							}
						}
						break;
					case "last":
						$item = null;
						foreach ($tree as $element => $children) {
							if ($element[0] != '@') {
								$item = $children;
							}
						}
						if ($item !== null) {
							array_push($agenda, $item);
						}
						break;
					case "attributes":
						if (count($parameters['arguments']) < 1) {
							$parameters['arguments'][0] = ".*";
						}
						if (count($parameters['arguments']) < 2) {
							$parameters['arguments'][1] = ".*";
						}
						$attributes = array();
						foreach ($tree as $attribute => $value) {
							if ($attribute[0] == '@' && preg_match('/^@' . $parameters['arguments'][0] . '$/mi', $attribute) && preg_match('/^' . $parameters['arguments'][1] . '$/mi', $value)
							) {
								$attributes[$attribute] = $value;
							}
						}
						if (count($attributes) > 0) {
							array_push($agenda, $attributes);
						}
						break;
					default:
						trigger_error("Unknown semantic function called ($parameters[name])", E_USER_ERROR);
						break;
				}
				break;
			default:
				trigger_error("Invalid parameter type ($parameters[type])", E_USER_ERROR);
				break;
		}
		$result = array();
		if (empty($path['rest'])) {
			//We've hit it
			$result = $agenda;
		} else {
			// ... or there's still work to do
			for ($i = 0; $i < count($agenda); $i++) {
				if (!is_array($agenda[$i])) {
					//We've hit either an attribute or a text node, none of which can be further explored
					continue;
				}
				$exploration = xmlnode_path($agenda[$i], $path['rest']);
				if (is_array($exploration) && count($exploration) > 0) {
					for ($j = 0; $j < count($exploration); $j++) {
						array_push($result, $exploration[$j]);
					}
				}
				unset($exploration); //A good boy takes care of his garbage
			}
		}
		return $result;
	}

	/**
	 * @param array $node
	 * @param bool $remove_indices
	 * @param string|Closure $callback
	 * @return array
	 */
	function xmlnode_to_array($node, $remove_indices = true, $callback = null) {
		$result = array();
		foreach ($node as $element => $children) {
			if ($callback !== null && (is_string($callback) && function_exists($callback)) || (is_object($callback))
			) {
				$act = $callback($element, $children);
				if (is_bool($act) && $act === false) {
					continue;
				}
			}
			if ($remove_indices && strpos($element, '[') !== false) {
				$element = substr($element, 0, strpos($element, '['));
			}
			if (is_array($children)) {
				$children = xmlnode_to_array($children, $remove_indices, $callback);
				if (count($children) == 1) {
					$str = null;
					foreach ($children as $text => $value) {
						if ($text[0] == '#') {
							$str = $value;
						}
					}
					if ($str !== null) {
						$children = $str;
					}
				}
			}
			$result[$element] = $children;
		}
		return $result;
	}

	/**
	 * @param array $node
	 * @return array
	 */
	function xmlnode_flatten($node) {
		$result = array();
		foreach ($node as $element => $children) {
			if (!is_array($children)) {
				$result[$element] = $children;
			} else {
				$children = xmlnode_flatten($children);
				foreach ($children as $path => $value) {
					$result[$element . "/" . $path] = $value;
				}
			}
		}
		return $result;
	}

	/**
	 * @param array $tree
	 * @param string|Closure $callback
	 * @param mixed $arguments
	 * @return array
	 */
	function xmlnode_traverse($tree, $callback, &$arguments = null) {
		if (!is_array($tree)) {
			trigger_error("XMLNode tree does not conform to expected syntax");
		}
		if (is_string($callback)) {
			if (!function_exists($callback)) {
				trigger_error("Callback not found ($callback)");
			}
		} else if (!is_object($callback)) {
			trigger_error("Invalid callback specified.");
		}
		$names = array();
		$i = 0;
		foreach ($tree as $name => $tag) {
			if ($name[0] == '@') {
				continue;
			}
			$names[$i] = $name;
			$i++;
		}
		$i = 0;
		foreach ($tree as $name => $tag) {
			if ($name[0] == '@') {
				continue;
			}
			$continue = $callback($name, $tag, $arguments);
			$original_name = $name;
			$tree[$original_name] = $tag;
			$names[$i] = $name;
			if (!is_bool($continue)) {
				$continue = true;
			}
			if ($continue && is_array($tag)) {
				$tag = xmlnode_traverse($tag, $callback, $arguments);
				$tree[$original_name] = $tag;
			}
			$i++;
		}
		$i = 0;
		$new_tree = array();
		foreach ($tree as $name => $tag) {
			if ($name[0] == '@') {
				$new_tree[$name] = $tag;
			} else {
				$new_tree[$names[$i]] = $tag;
				$i++;
			}
		}
		return $new_tree;
	}

	/**
	 * @param string $name
	 * @return string
	 */
	function xmlnode_get_node_name($name) {
		if (strpos($name, "[") === false) {
			return $name;
		} else {
			return substr($name, 0, strpos($name, '['));
		}
	}

	/**
	 * @param array $array
	 * @return array
	 */
	function xmlnode_expand($array) {
		if (!is_array($array)) {
			return $array;
		}
		$result = array();
		foreach ($array as $name => $value) {
			$new_name = explode("/", $name, 2);
			if (!array_key_exists($new_name[0], $result) || !is_array($result[$new_name[0]])) {
				$result[$new_name[0]] = array();
			}
			if (count($new_name) == 2) {
				$result[$new_name[0]][$new_name[1]] = $value;
			} else {
				$result[$new_name[0]] = $value;
			}
			$result[$new_name[0]] = xmlnode_expand($result[$new_name[0]]);
		}
		return $result;
	}

}
?>