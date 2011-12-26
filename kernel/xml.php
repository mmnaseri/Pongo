<?php
/**
 * User: Mohammad Milad Naseri
 * Date: Dec 3, 2010
 * Time: 12:16:33 PM
 * @package com.bowfin.lib.xml
 */
if (!defined("COM_BOWFIN_LIB_XML")) {

	define("COM_BOWFIN_LIB_XML", "Bowfin XML Library");

	/**
	 * Constants
	 */
	//Node Types
	define("XML_NT_TEXT", "#text");
	define("XML_NT_CDATA", "#cdata");
	define("XML_NT_COMMENT", "#comment");

	define("XML_TRAVERSE_CONTINUE", 1);
	define("XML_TRAVERSE_DISCONTINUE", 2);
	define("XML_TRAVERSE_REPEAT", 3);

	/**
	 * Creates an XML element
	 * @param string $name
	 * @return array
	 */
	function xml_create_element($name) {
		if (strlen($name) == "") {
			return null;
		}
		return array('name' => $name, 'attributes' => array(), 'children' => array(), '<tag>' => true);
	}

	/**
	 * Creates an XML element of type XML_NT_TEXT
	 * @param string $value
	 * @return array
	 */
	function xml_create_text($value = "") {
		return array('name' => XML_NT_TEXT, 'value' => $value);
	}

	/**
	 * Creates an XML element of type XML_NT_CDATA
	 * @param string $value
	 * @return array
	 */
	function xml_create_cdata($value = "") {
		return array('name' => XML_NT_CDATA, 'value' => $value);
	}

	/**
	 * Creates an XML element of type XML_NT_COMMENT
	 * @param string $value
	 * @return array
	 */
	function xml_create_comment($value = "") {
		return array('name' => XML_NT_COMMENT, 'value' => $value);
	}

	/**
	 * Checks whether given node has a particular attribute
	 * @param array $tag (XMLObject)
	 * @param string $attribute
	 * @return boolean
	 */
	function xml_has_attribute($tag, $attribute) {
		if (!is_array($tag)) {
			return false;
		}
		if (!array_key_exists('attributes', $tag)) {
			return false;
		}
		if (!is_array($tag['attributes'])) {
			return false;
		}
		return array_key_exists($attribute, $tag['attributes']);
	}

	/**
	 * Returns the value of given attribute or <code>null</code> if the node doesn't have such an attribute
	 * @param  array $tag
	 * @param  string $attribute
	 * @param  string $default
	 * @return string
	 */
	function xml_get_attribute($tag, $attribute, $default = "") {
		if (!xml_has_attribute($tag, $attribute)) {
			return $default;
		}
		return $tag['attributes'][$attribute];
	}

	/**
	 * Sets the value of given attribute
	 * @param  array $tag
	 * @param  string $attribute
	 * @param  string $value
	 * @return array
	 */
	function xml_set_attribute($tag, $attribute, $value) {
		if (!is_array($tag)) {
			return $tag;
		}
		if (!array_key_exists('attributes', $tag)) {
			return $tag;
		}
		if (!is_array($tag['attributes'])) {
			return $tag;
		}
		$tag['attributes'][$attribute] = strval($value);
		return $tag;
	}

	/**
	 * Returns node name
	 * @param  array $tag
	 * @return string
	 */
	function xml_get_node_name($tag) {
		if (!is_array($tag)) {
			return "";
		}
		if (!array_key_exists('name', $tag)) {
			return "";
		}
		return $tag['name'];
	}

	/**
	 * Returns node value, or node content if it is one of XML_NT_CDATA, XML_NT_COMMENT, or XML_NT_TEXT.
	 * @param  array $tag
	 * @return string
	 */
	function xml_get_node_value($tag) {
		if (!is_array($tag)) {
			return "";
		}
		if (!array_key_exists('value', $tag)) {
			return xml_to_string($tag);
		}
		return $tag['value'];
	}

	/**
	 * Sets node content, if it is an actual DOM node.
	 * @param  array $tag
	 * @param string $value
	 * @return array
	 */
	function xml_set_node_value($tag, $value = "") {
		if (!is_array($tag)) {
			return $tag;
		}
		if (!array_key_exists('value', $tag)) {
			return $tag;
		}
		$tag['value'] = $value;
		return $tag;
	}

	/**
	 * Appends a child to current tag
	 * @param  array $tag
	 * @param  array $child
	 * @return array
	 */
	function xml_append_child($tag, $child) {
		if (!is_array($tag)) {
			return $tag;
		}
		if (!is_array($child)) {
			return $tag;
		}
		if (!array_key_exists('children', $tag)) {
			return $tag;
		}
		if (!is_array($tag['children'])) {
			return $tag;
		}
		array_push($tag['children'], $child);
		return $tag;
	}

	/**
	 * Inserts a new child at the given position of the tag
	 * @param  array $tag
	 * @param  array $child
	 * @param  int $position
	 * @return array
	 */
	function xml_add_child_at($tag, $child, $position) {
		if (!is_array($tag)) {
			return $tag;
		}
		if (!is_array($child)) {
			return $tag;
		}
		if (!array_key_exists('children', $tag)) {
			return $tag;
		}
		if (!is_array($tag['children'])) {
			return $tag;
		}
		if ($position < 0 || $position > count($tag['children'])) {
			return $tag;
		}
		for ($i = count($tag['children']); $i > $position; $i--) {
			$tag['children'][$i] = $tag['children'][$i - 1];
		}
		$tag['children'][$position] = $child;
		return $tag;
	}

	/**
	 * Inserts or appends children for given node
	 * @param  array $tag
	 * @param  array $child
	 * @param  int $position
	 * @return array
	 */
	function xml_add_children($tag, $child, $position = -1) {
		if ($position == -1) {
			return xml_append_child($tag, $child);
		} else {
			return xml_add_child_at($tag, $child, $position);
		}
	}

	/**
	 * Checks if the node has any child nodes
	 * @param  array $tag
	 * @return bool
	 */
	function xml_has_children($tag) {
		if (!is_array($tag)) {
			return false;
		}
		if (!array_key_exists('name', $tag)) {
			return false;
		}
		if (!array_key_exists('children', $tag)) {
			return false;
		}
		return count($tag['children']) > 0;
	}

	/**
	 * Finds the very first child with the name specified
	 * @param  array $tag
	 * @param  string $name
	 * @return array
	 */
	function xml_get_child($tag, $name) {
		if (!is_array($tag)) {
			return false;
		}
		if (!array_key_exists('name', $tag)) {
			return false;
		}
		if (!array_key_exists('children', $tag)) {
			return false;
		}
		for ($i = 0; $i < count($tag['children']); $i++) {
			$item = $tag['children'][$i];
			if (xml_get_node_name($item) == $name) {
				return $item;
			}
		}
		return false;
	}

	/**
	 * Returns the source-code leading to current tag
	 * @param  array $tag
	 * @param int $offset
	 * @return string
	 */
	function xml_to_string($tag, $offset = 0) {
		if (!is_array($tag)) {
			return;
		}
		if (!array_key_exists('name', $tag)) {
			$str = "";
			for ($i = 0; $i < count($tag); $i++) {
				if (!isset($tag[$i])) {
					continue;
				}
				$str .= xml_to_string($tag[$i], $offset);
			}
			return $str;
		}
		$name = xml_get_node_name($tag);
		$str = str_repeat("\t", $offset);
		if ($name[0] != "#") {
			$str .= "<" . $name;
			foreach ($tag['attributes'] as $attribute => $value) {
				$str .= " " . $attribute . "=";
				if (strpos($value, "\"") !== false) {
					$str .= "'" . $value . "'";
				} else {
					$str .= "\"" . $value . "\"";
				}
			}
			if (count($tag['children']) == 0) {
				$str .= " />\n";
			}
			if (count($tag['children']) > 0) {
				if ((is_array($tag['children'][0]) && !array_key_exists('name', $tag['children'][0])) || $tag['children'][0]['name'][0] == '#') {
					$str .= ">";
					for ($i = 0; $i < count($tag['children']); $i++) {
						$str .= trim(xml_to_string($tag['children'][$i], $offset + 1));
					}
					$str .= "</" . $name . ">\n";
				} else {
					$str .= ">\n";
					for ($i = 0; $i < count($tag['children']); $i++) {
						$str .= xml_to_string($tag['children'][$i], $offset + 1);
					}
					$str .= str_repeat("\t", $offset) . "</" . $name . ">\n";
				}
			}
		} else {
			if ($name == XML_NT_TEXT) {
				$str .= xml_get_node_value($tag) . "\n";
			} else if ($name == XML_NT_CDATA) {
				$str .= "<![CDATA[";
				$str .= xml_get_node_value($tag);
				$str .= "]]>\n";
			} else if ($name == XML_NT_COMMENT) {
				$str .= "<!--";
				$str .= xml_get_node_value($tag);
				$str .= " -->\n";
			}
		}
		return $str;
	}

	/**
	 * Parses an XML document and returns a DOM tree representation for it
	 * @param  string $xml
	 * @param bool $ignore_white_space
	 * @return array
	 */
	function xml_from_string($xml, $ignore_white_space = true) {
		$tree = array();
		$i = 0;
		$text = "";
		while ($i < strlen($xml)) {
			if ($xml[$i] == "<") {
				//We have found the opening to an XML entity
				if ($ignore_white_space) {
					$text = trim($text);
				}
				if (strlen($text) != 0) {
					array_push($tree, xml_create_text($text));
				}
				$text = "";
				$name = "";
				$i++; //Skipping the '<'
				while ($i < strlen($xml) && strpos(" [\t\n/><", $xml[$i]) === false) {
					$name .= $xml[$i];
					$i++;
				}
				$name = trim($name);
				if (strlen($name) == 0) {
					continue;
				}
				if ($name[0] == "/") {
					while ($i < strlen($xml) && $xml[$i] != ">") {
						$i++;
					}
					$i++;
					continue;
				}
				if ($name == "!") {
					if (substr($xml, $i, 7) == "[CDATA[") {
						$name = "![CDATA[";
						$i += 7;
					}
				}
				if ($name == "![CDATA[") {
					//CDATA found, extracting contents
					while ($i < strlen($xml) && substr($xml, $i, 3) != "]]>") {
						$text .= $xml[$i];
						$i++;
					}
					$text = trim($text);
					$i += 3;
					array_push($tree, xml_create_cdata($text));
					$text = "";
					continue;
				} else if ($name == "!--") {
					//XML comment found, extracting contents
					while ($i < strlen($xml) && substr($xml, $i, 3) != "-->") {
						$text .= $xml[$i];
						$i++;
					}
					$i += 3;
					//					$text = trim($text);
					//                    kernel_out($text);
					array_push($tree, xml_create_comment($text));
					$text = "";
					continue;
				} else if ($name[0] == "!" || $name[0] == "?") {
					//Ignoring possible DOCTYPE and other meta tags
					while ($i < strlen($xml) && $xml[$i] != ">") {
						$i++;
					}
					$i++;
					continue;
				}
				//We are inside a valid XML tag
				while ($i < strlen($xml) && strpos(" \n\t", $xml[$i]) !== false) { //Skipping white spaces
					$i++;
				}
				$in_attribute = false;
				$attr_holder = "";
				//Extracting tag attributes
				while ($i < strlen($xml) && $xml[$i] != "<" && ($in_attribute || $xml[$i] != ">")) {
					if ($xml[$i] == "\"") {
						if (!$in_attribute) {
							$in_attribute = true;
							$attr_holder = "\"";
						} else if ($attr_holder == "\"") {
							$in_attribute = false;
						}
					}
					if ($xml[$i] == "'") {
						if (!$in_attribute) {
							$in_attribute = true;
							$attr_holder = "'";
						} else if ($attr_holder == "'") {
							$in_attribute = false;
						}
					}
					$text .= $xml[$i];
					$i++;
				}
				$open = strlen($text) == 0 || $text[strlen($text) - 1] != "/"; //Checking if we should seek a tag terminator or not?
				if ($i < strlen($xml) && $xml[$i] == ">") {
					$i++;
				}
				//Building the attributes' array
				$attributes = array();
				$j = 0;
				$in_attribute = false;
				$attr_holder = "";
				$attr = "";
				$value = "";
				while ($j < strlen($text)) {
					if (!$in_attribute) {
						if (strpos(" \n\t", $text[$j]) !== false) {
							$j++;
							continue;
						}
						if ($text[$j] == "=") {
							$j++;
							while ($j < strlen($text) && strpos(" \n\t", $text[$j]) !== false) {
								$j++;
							}
							if ($j >= strlen($text)) {
								break;
							}
							if ($text[$j] == "\"" || $text[$j] == "'") {
								$in_attribute = true;
								$attr_holder = $text[$j];
								$attributes[$attr] = "";
							}
						} else {
							$attr .= $text[$j];
						}
					} else {
						if ($text[$j] == $attr_holder) {
							$j++;
							$attributes[$attr] = $value;
							$attr = "";
							$value = "";
							$in_attribute = false;
						} else {
							$value .= $text[$j];
						}
					}
					$j++;
				}
				//Creating the element
				$tag = xml_create_element($name);
				$tag['attributes'] = $attributes;
				$text = "";
				if (!$open) { //If the element is a closed one, we have no more business with it
					array_push($tree, $tag);
					continue;
				}
				if (strpos($xml, "/" . $name) === false) { //Current tag has no valid ending and should therefore be ignored
					continue;
				}
				//Extracting the tag's contents
				$tags = 0;
				while ($i < strlen($xml)) {
					if ($xml[$i] == "<") {
						$tagname = "";
						$i++;
						while ($i < strlen($xml) && strpos(" \t\n>", $xml[$i]) === false) {
							$tagname .= $xml[$i];
							$i++;
						}
						$tagname = trim($tagname);
						if ($tagname == $name) {
							$tags++;
							//check if it's an empty tag
							$j = $i;
							while ($j < strlen($xml) && $xml[$j] != '>') {
								$j++;
							}
							if ($xml[$j - 1] == '/') {
								$tags--;
							}
						}
						if ($tagname == "/" . $name) {
							$tags--;
							if ($tags < 0) {
								break;
							}
						}
						$text .= "<" . $tagname;
					} else {
						$text .= $xml[$i];
						$i++;
					}
				}
				if ($tags >= 0) {
					trigger_error("Bad XML syntax (Closing tag not found for '$name')", E_USER_ERROR);
				}
				//Jumping to the end of the tag
				while ($i < strlen($xml) && $xml[$i] != ">") {
					$i++;
				}
				$i++;
				if ($ignore_white_space) {
					$text = trim($text);
				}
				//Parsing children
				$children = xml_from_string($text, $ignore_white_space);
				if (array_key_exists('name', $children)) {
					$children = array($children);
				}
				$tag['children'] = $children;
				//Appending the element to the list
				array_push($tree, $tag);
				$text = "";
			} else {
				//Collecting raw text data
				$text .= $xml[$i];
				$i++;
			}
		}
		if ($ignore_white_space) {
			$text = trim($text);
		}
		if (strlen($text) != 0) {
			array_push($tree, xml_create_text($text));
		}
		if (count($tree) == 1) {
			$tree = $tree[0];
		}
		return $tree;
	}

	/**
	 * Reads XML tree from an external XML file
	 * @param  string $file
	 * @param bool $ignore_white_space
	 * @param bool $replace_ampersands
	 * @return array
	 */
	function xml_from_file($file, $ignore_white_space = true, $replace_ampersands = false) {
		if (!file_exists($file)) {
			trigger_error("No such XML file ($file)", E_USER_ERROR);
		}
		$contents = file_get_contents($file);
		if ($replace_ampersands) {
			$contents = str_replace("&amp;", "&", $contents);
		}
		$contents = xml_from_string($contents, $ignore_white_space);
		return $contents;
	}

	/**
	 * Returns parameterized path data for given string input
	 * @param string $path
	 * @param int $limit
	 * @return array
	 */
	function xml_string_to_path($path, $limit = 0) {
		if (!is_string($path)) {
			trigger_error("Invalid argument, expected string.", E_USER_ERROR);
		}
		$result = array();
		if (strlen($path) == 0) {
			return array();
		}
		$i = 0;
		$in_quote = false;
		$quote = "";
		$query = "";
		while ($i < strlen($path)) {
			while (!$in_quote && $i < strlen($path) && strpos(" \n\t", $path[$i]) !== false) {
				$i++;
			}
			if (strpos("'\"", $path[$i]) !== false) {
				if ($in_quote) {
					if ($path[$i] == $quote) {
						$in_quote = false;
						if ($i < strlen($path) - 1 && strpos("/@", $path[$i + 1]) === false) {
							trigger_error("Syntax error at " . ($i + 1) . ": invalid attribute assignment", E_USER_ERROR);
						}
					}
				} else {
					if ($i > 0 && $query[strlen($query) - 1] == "=" && $query[0] == "@") {
						$quote = $path[$i];
						$in_quote = true;
					} else {
						trigger_error("Syntax error at character " . $i . ": unexpected value (" . $path[$i - 1] . ")", E_USER_ERROR);
					}
				}
			}
			if (strlen($query) > 1 && $query[strlen($query) - 2] == "=") {
				if ($query[0] != "@") {
					trigger_error("Invalid assingment at " . $i, E_USER_ERROR);
				} else if (strpos("'\"", $query[strlen($query) - 1]) === false) {
					trigger_error("Syntax error at " . $i . ": unquoted value assingment", E_USER_ERROR);
				}
			}
			if ($in_quote || strpos("/@", $path[$i]) === false) {
				$query .= $path[$i];
			} else {
				$query = trim($query);
				if ($query == "@") {
					trigger_error("Expected attribute name before " . $i, E_USER_ERROR);
				}
				if (strlen($query) > 0) {
					array_push($result, $query);
					if (count($result) == $limit) {
						return $result;
					}
				} else if (($i == 0 && $path[$i] == "/") || $path[$i] != "@") {
					trigger_error("Invalid nesting at " . $i, E_USER_ERROR);
				}
				if ($path[$i] == "@") {
					$query = "@";
				} else {
					$query = "";
				}
			}
			$i++;
		}
		if ($in_quote) {
			trigger_error("Unterminated string value", E_USER_ERROR);
		}
		if (strlen($query) > 0) {
			$query = trim($query);
			array_push($result, $query);
		} else {
			trigger_error("Unexpected nesting at the end of input path", E_USER_ERROR);
		}
		return $result;
	}

	/**
	 * Builds a string representing current path object
	 * @param  array $path
	 * @return string
	 */
	function xml_path_to_string($path) {
		if (!is_array($path)) {
			trigger_error("Invalid argument type, expected array.", E_USER_ERROR);
		}
		$result = "";
		for ($i = 0; $i < count($path); $i++) {
			if (array_key_exists($i, $path) && isset($path[$i]) && is_string($path[$i]) && trim($path[$i]) != "") {
				$result .= $path[$i] . "/";
			}
		}
		if (strlen($result) > 0) {
			$result = substr($result, 0, strlen($result) - 1);
		}
		return $result;
	}

	/**
	 * Returns query type/target for current path element
	 * @param  array|string $path
	 * @return string
	 */
	function xml_path_query_type($path) {
		if (!is_array($path)) {
			if (is_string($path)) {
				$path = xml_string_to_path($path, 1);
			} else {
				trigger_error("Invalid argument, expected string or array", E_USER_ERROR);
			}
		} else {
			$path = xml_string_to_path(xml_path_to_string($path), 1);
		}
		$path = $path[0];
		if ($path[0] != "@") {
			return "tag"; //querying for a tag name [filter]
		}
		if (strpos($path, "=") !== false) {
			return "attr+val"; //querying for an attribute+value pair [filter]
		}
		if ($path != "@\$" && strlen($path) > 1) {
			return "attr"; //querying for an attribute's value [target]
		}
		return "allattr"; //all attributes for the current tag(s) [target]
	}

	/**
	 * Extracts path parameters from given query string
	 * @param  array|string $query
	 * @return array
	 */
	function xml_path_parameters($query) {
		/**
		 * Standard modifiers:
		 * ]	Closed tags
		 * [	Open tags
		 * ^	Return outerXML
		 * |	Trim
		 */
		$result = array('type' => '', //Possible types are: tag, attr, attr+val, allattr
			'tag' => '', 'attribute' => '', 'value' => '', 'modifiers' => '');
		if (!is_array($query)) {
			if (is_string($query)) {
				$query = xml_string_to_path($query, 1);
			} else {
				trigger_error("Invalid argument, expected string or array", E_USER_ERROR);
			}
		} else {
			$query = xml_string_to_path(xml_path_to_string($query), 1);
		}
		if (count($query) == 0) {
			return $result;
		}
		$query = $query[0];
		$result['type'] = xml_path_query_type($query);
		if ($result['type'] == "tag" || $result['type'] == "attr+val") {
			if ($result['type'] == "attr+val") {
				$result['value'] = substr($query, strpos($query, "=") + 1);
				$result['value'] = substr($result['value'], 1, strlen($result['value']) - 2);
				$query = substr($query, 1, strpos($query, "=") - 1);
			}
			while (strpos("[]|^$&", $query[strlen($query) - 1]) !== false) {
				if (strlen($query) == 0) {
					trigger_error("Query must not only contain modifiers", E_USER_ERROR);
				}
				if (strpos($result['modifiers'], $query[strlen($query) - 1]) === false) {
					$result['modifiers'] .= $query[strlen($query) - 1];
					$query = substr($query, 0, strlen($query) - 1);
				}
			}
			if ($result['type'] == "tag") {
				$result['tag'] = $query;
			} else {
				$result['attribute'] = $query;
			}
			if (strpos($result['modifiers'], "[") === false && strpos($result['modifiers'], "]") === false) {
				$result['modifiers'] .= "[]";
			}
		} else if ($result['type'] == "attr") {
			if ($query[strlen($query) - 1] == "$") {
				$result['modifiers'] = "$";
				$query = substr($query, 0, strlen($query) - 1);
			}
			$result['attribute'] = substr($query, 1);
		} else if ($result['type'] == "allattr") {
			if ($query[strlen($query) - 1] == "$") {
				$result['modifiers'] = "$";
				$query = substr($query, 0, strlen($query) - 1);
			}
		}
		return $result;
	}

	/**
	 * Traverses along the DOM tree to extract requested nodes for a given path.
	 * Called by <code>xml_path</code>
	 * @param  array $tag
	 * @param  array $parameters
	 * @return bool
	 */
	function xml_path_traverse($tag, &$parameters) {
		if (!array_key_exists('attributes', $tag)) {
			return false;
		}
		if ($parameters['type'] == "tag") {
			if ($parameters['tag'] == "#") {
				array_push($parameters['acc'], array('$' => $tag['name'], '#' => count($tag['children'])));
				return false;
			}
			if ($parameters['tag'][0] == "!") {
				$index = intval(substr($parameters['tag'], 1));
				if ($parameters['tag'] != "!" && array_key_exists($index, $tag['children'])) {
					array_push($parameters['acc'], $tag['children'][$index]);
				} else {
					array_push($parameters['acc'], $tag['children']);
				}
				return false;
			}
			if (preg_match("/^" . $parameters['tag'] . "$/mi", $tag['name'])) {
				array_push($parameters['acc'], $tag);
				return false;
			} else {
				return true;
			}
		}
		if ($parameters['type'] == "attr") {
			$attributes = array();
			foreach ($tag['attributes'] as $attribute => $value) {
				if (preg_match("/^" . $parameters['attribute'] . "$/mi", $attribute)) {
					array_push($attributes, $value);
					$attributes['@'] = $attribute;
				}
			}
			if (count($attributes) == 0) {
				return true;
			}
			if (strpos($parameters['modifiers'], "$") !== false) {
				$attributes['$'] = $tag['name'];
			}
			array_push($parameters['acc'], array($parameters['attribute'] => $attributes));
			return false;
		}
		if ($parameters['type'] == "attr+val") {
			foreach ($tag['attributes'] as $attribute => $value) {
				if (preg_match("/^" . $parameters['attribute'] . "$/mi", $attribute) && preg_match("/^" . $parameters['value'] . "$/mi", $value)
				) {
					array_push($parameters['acc'], $tag);
					return false;
				}
			}
			return true;
		}
		if ($parameters['type'] == "allattr") {
			$attributes = $tag['attributes'];
			if (strpos($parameters['modifiers'], "$") !== false) {
				$attributes['$'] = $tag['name'];
			}
			array_push($parameters['acc'], $attributes);
			return false;
		}
		return true;
	}

	/**
	 * Extracts requested DOM elements from <code>$xml</code>
	 * @param  array|string $xml
	 * @param  string|array $path
	 * @return array
	 */
	function xml_path($xml, $path) {
		if (!is_array($xml)) {
			if (is_string($xml)) {
				$xml = xml_from_string($xml);
			} else {
				trigger_error("No XML data to probe", E_USER_ERROR);
			}
		}
		if (!is_array($path)) {
			if (is_string($path)) {
				$path = xml_string_to_path($path); //Converting the path, and checking the syntax
			} else {
				trigger_error("Invalid path supplied", E_USER_ERROR);
			}
		} else {
			$path = xml_string_to_path(xml_path_to_string($path)); //Checking path syntax
		}
		if (count($path) == 0) {
			return array($xml);
		}
		$parameters = xml_path_parameters($path);
		$parameters['acc'] = array();
		xml_traverse($xml, "xml_path_traverse", $parameters);
		$result = $parameters['acc'];
		if (count($path) > 1) {
			for ($i = 0; $i < count($path) - 1; $i++) {
				$path[$i] = $path[$i + 1];
			}
			unset($path[count($path) - 1]);
			$result = array();
			for ($i = 0; $i < count($parameters['acc']); $i++) {
				$temp = $parameters['acc'][$i];
				$temp = xml_path($temp, $path);
				for ($j = 0; $j < count($temp); $j++) {
					array_push($result, $temp[$j]);
				}
			}
		}
		$final = array();
		for ($i = 0; $i < count($result); $i++) {
			if (!is_array($result[$i]) || !array_key_exists('<tag>', $result[$i])) {
				if (is_array($result[$i])) {
					foreach ($result[$i] as $attribute => $value) {
						if (is_array($value) && array_key_exists(0, $value)) {
							if (array_key_exists('@', $value)) { //Replacing the actual 'attribute name' with the query
								unset($result[$i][$attribute]);
								$attribute = $value['@'];
							}
							$index = count($value) - 1;
							while ($index >= 0 && !array_key_exists($index, $value)) {
								$index--;
							}
							$result[$i][$attribute] = $value[$index];
							if (array_key_exists('$', $value)) { //Returning container tag's name
								$result[$i]['$'] = $value['$'];
							}
						}
					}
				}
				array_push($final, $result[$i]);
				continue;
			}
			if (strpos($parameters['modifiers'], "[") === false && xml_has_children($result[$i])) {
				continue;
			}
			if (strpos($parameters['modifiers'], "]") === false && !xml_has_children($result[$i])) {
				continue;
			}
			if (strpos($parameters['modifiers'], "^") !== false) {
				$result[$i] = xml_to_string($result[$i]);
			} else if (strpos($parameters['modifiers'], "&") !== false) {
				//Don't do anyting and let the actual tag be passed as result
			} else {
				$result[$i] = xml_to_string($result[$i]['children']);
			}
			if (is_string($result[$i]) && strpos($parameters['modifiers'], "|") !== false) {
				$result[$i] = trim($result[$i]);
			}
			if (empty($result[$i])) {
				continue;
			}
			array_push($final, $result[$i]);
		}
		return $final;
	}

	/**
	 * Traverses the DOM tree using callback function <code>$function</code>
	 * @param  string|array $xml
	 * @param  string $function
	 * @param  mixed $args
	 * @return void
	 */
	function xml_traverse(&$xml, $function, &$args = null) {
		if (!is_array($xml)) {
			if (is_string($xml)) {
				$xml = xml_from_string($xml);
			} else {
				trigger_error("Invalid argument given for XML", E_USER_ERROR);
			}
		}
		if (!(is_string($function) && function_exists($function)) && (!is_object($function))) {
			trigger_error("Invalid function name (" . $function . ")", E_USER_ERROR);
		}
		if (!array_key_exists('name', $xml)) {
			foreach ($xml as $key => $value) {
				xml_traverse($xml[$key], $function, $args);
			}
			return;
		}
		$down = $function($xml, $args);
		if (!is_int($down)) {
			$down = XML_TRAVERSE_CONTINUE;
		}
		if ($xml !== null && $down === XML_TRAVERSE_CONTINUE && array_key_exists('children', $xml)) {
			for ($i = 0; $i < count($xml['children']); $i++) {
				xml_traverse($xml['children'][$i], $function, $args);
			}
		} else if ($xml !== null && $down === XML_TRAVERSE_REPEAT) {
			xml_traverse($xml, $function, $args);
		}
	}

	/**
	 * Describes given <code>$path</code>
	 * @param string|array $path
	 * @return string
	 */
	function xml_path_describe($path) {
		if (is_string($path)) {
			$path = xml_path_parameters($path);
		}
		$description = "";
		if ($path['type'] == "tag") {
			if ($path['tag'] == "#") {
				$description = "Querying for number of children\n";
				$description .= "The name of the tag will be stored in cell '$'";
			} else if ($path['tag'][0] == "!") {
				if (strlen($path['tag']) > 1) {
					$description = "Jumping to child at " . substr($path['tag'], 1);
				} else {
					$description = "Skipping to child nodes";
				}
			} else {
				$description = "Looking for tag matching pattern '^" . $path['tag'] . "\$'";
				if (strpos($path['modifiers'], "^") !== false) {
					$description .= "\nIf this is the target, the result will be the outerXML for this node";
				}
				if (strpos($path['modifiers'], "[") !== false) {
					$description .= "\nNodes with children are included in the search";
				}
				if (strpos($path['modifiers'], "]") !== false) {
					$description .= "\nNodes without children are included in the search";
				}
				if (strpos($path['modifiers'], "|") !== false) {
					$description .= "\nIf this is the target node and source code is requested, the result will be trimmed";
				}
				if (strpos($path['modifiers'], "&") !== false) {
					$description .= "\nA pointer to the tag object will be passed from this level to the upper one";
				}
			}
		}
		if ($path['type'] == "attr") {
			$description = "Looking for attribute matching pattern '^" . $path['attribute'] . "\$'";
			if (strpos($path['modifiers'], "$") !== false) {
				$description .= "\nThe name of the target node is also requested and is stored in cell '$'";
			}
		}
		if ($path['type'] == "attr+val") {
			$description = "Looking for attribute matching pattern '^" . $path['attribute'] . "\$'\n";
			$description .= "The attribute must have a value matching pattern '^" . $path['value'] . "$'";
			if (strpos($path['modifiers'], "$") !== false) {
				$description .= "\nThe name of the target node is also requested and is stored in cell '$'";
			}
			if (strpos($path['modifiers'], "^") !== false) {
				$description .= "\nIf this is the target, the result will be the outerXML for this node";
			}
			if (strpos($path['modifiers'], "[") !== false) {
				$description .= "\nNodes with children are included in the search";
			}
			if (strpos($path['modifiers'], "]") !== false) {
				$description .= "\nNodes without children are included in the search";
			}
			if (strpos($path['modifiers'], "|") !== false) {
				$description .= "\nIf this is the target node and source code is requested, the result will be trimmed";
			}
			if (strpos($path['modifiers'], "&") !== false) {
				$description .= "\nA pointer to the tag object will be passed from this level to the upper one";
			}
		}
		if ($path['type'] == "allattr") {
			$description = 'A list of all the attributes for the current node has been requested';
			if (strpos($path['modifiers'], "$") !== false) {
				$description .= "\nThe name of the target node is also requested and is stored in cell '$'";
			}
		}
		return $description;
	}

	function xml_to_src($tag, $offset = 0, $palette = array('tag' => '#000080', 'attribute' => '#0000ff', 'value' => '#008000', 'text' => '#000000', 'comment' => '#808080')) {
		if (!is_array($tag)) {
			return;
		}
		if (!array_key_exists('name', $tag)) {
			$str = "";
			for ($i = 0; $i < count($tag); $i++) {
				if (!isset($tag[$i])) {
					continue;
				}
				$str .= xml_to_string($tag[$i], $offset);
			}
			return $str;
		}
		$str = "";
		$name = xml_get_node_name($tag);
		if ($name[0] != "#") {
			$str = str_repeat("&nbsp;&nbsp;&nbsp;&nbsp;", $offset);
			$str .= "<span style='color: $palette[tag];'>&lt;" . $name;
			foreach ($tag['attributes'] as $attribute => $value) {
				$str .= " <span style='color: $palette[attribute];'>" . $attribute . "=</span>";
				$str .= "<span style='color: $palette[value];'>";
				if (strpos($value, "\"") !== false) {
					$str .= "'" . htmlentities($value) . "'";
				} else {
					$str .= "\"" . htmlentities($value) . "\"";
				}
				$str .= "</span>";
			}
			if (count($tag['children']) == 0) {
				$str .= " /&gt;<br/>";
				$str .= "</span>";
			}
			if (count($tag['children']) > 0) {
				if ($tag['children'][0]['name'][0] == '#') {
					$str .= "&gt;";
					$str .= "</span><span style='color: $palette[text];'>";
					for ($i = 0; $i < count($tag['children']); $i++) {
						$str .= trim(xml_to_src($tag['children'][$i], $offset + 1));
					}
					$str .= "</span><span style='color: $palette[tag];'>";
					$str .= "&lt;/" . $name . "&gt;</span><br/>";
				} else {
					$str .= "&gt;<br/>";
					$str .= "</span><span style='color: $palette[text];'>";
					for ($i = 0; $i < count($tag['children']); $i++) {
						$str .= xml_to_src($tag['children'][$i], $offset + 1);
					}
					$str .= "</span><span style='color: $palette[tag];'>";
					$str .= str_repeat("&nbsp;&nbsp;&nbsp;&nbsp;", $offset) . "&lt;/" . $name . "&gt;</span><br/>";
				}
			}
		} else {
			$value = trim(xml_get_node_value($tag));
			if (strpos($value, "\n") !== false) {
				$value = "\n\t" . $value . "\n";
			}
			if ($name == XML_NT_TEXT) {
				if (strpos($value, "\n") !== false) {
					$value = "\n" . str_repeat("\t", $offset) . trim(str_replace("\n", "\n" . str_repeat("\t", $offset), $value)) . "\n";
				}
				$str .= htmlspecialchars($value);
				if (strpos($value, "\n") !== false) {
					$str .= str_repeat("&nbsp;&nbsp;&nbsp;&nbsp;", $offset - 1);
				}
			} else if ($name == XML_NT_CDATA) {
				$str .= "&lt;![CDATA[";
				$str .= htmlspecialchars($value);
				$str .= "]]&gt;";
			} else if ($name == XML_NT_COMMENT) {
				if (strpos($value, "\n") !== false) {
					$str = "\n";
				}
				$str .= "<span style='font-style: italic; color: $palette[comment];'>&lt;!--";
				$str .= htmlspecialchars($value);
				$str .= " --&gt;</span><br/>";
				if (strpos($value, "\n") !== false) {
					$str .= str_repeat("&nbsp;&nbsp;&nbsp;&nbsp;", $offset - 1);
				}
			}
		}
		$str = str_replace("\n", "<br/>", $str);
		$str = str_replace("\t", "&nbsp;&nbsp;&nbsp;&nbsp;", $str);
		return $str;
	}

}
?>