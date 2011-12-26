<?php

/**
 * pongo PHP Framework Kernel Component
 * Package ITREE
 * @author Mohammad Milad Naseri (m.m.naseri at gmail.com)
 * @since 1.0 (22/12/11, 14:20)
 * @package com.agileapes.pongo.kernel.itree
 */

if (!defined("KERNEL_COMPONENT_ITREE")) {

	/***
	 * Kernel Component Registration
	 **/
	define('KERNEL_COMPONENT_ITREE', "");

	/***
	 * Kernel Component Implementation
	 **/

	define('KCE_ITREE', 14000);

	class ITreeException extends KernelException {

		protected function getBase() {
			return KCE_ITREE;
		}

		protected function getBaseCode() {
			return 1;
		}

	}

	/**
	 * Constants
	 */

	define("ITREE_DEFAULT_ESCAPE", "::");
	define("ITREE_DEFAULT_LEVEL", ".");
	define("ITREE_ALL_INCLUSIVE", "*");
	define("ITREE_ALL_EXCLUSIVE", "-");

	/**
	 * Checks whether the specified argument is a valid iTree structure
	 * @param  mixed $var
	 * @return bool
	 */
	function is_itree($var) {
		return is_array($var) && array_key_exists('escape', $var) && array_key_exists('level', $var) && array_key_exists('data', $var) && (is_string($var['data']));
	}

	/**
	 * Creates an iTree structure using given parameters
	 * @param string $data
	 * @param string $escape
	 * @param string $level
	 * @return array
	 */
	function itree_create($data = "", $escape = ITREE_DEFAULT_ESCAPE, $level = ITREE_DEFAULT_LEVEL) {
		$tree = array("escape" => $escape, "level" => $level, "data" => "");
		$tree['data'] = itree_flatten($tree, $data);
		return $tree;
	}

	/**
	 * Returns the data for iTree as an array
	 * @param array $tree
	 * @return array
	 */
	function itree_get_data($tree) {
		if (!is_itree($tree)) {
			throw new ITreeException("Given data structure does not conform to iTree standard");
		}
		$data = $tree['data'];
		$data = explode($tree['escape'], $data);
		sort($data);
		return $data;
	}

	/**
	 * Flattens array data into string data to be used as a property of an iTree
	 * @param  array $tree
	 * @param  array|string $data
	 * @return string
	 */
	function itree_flatten($tree, $data) {
		if (!is_itree($tree)) {
			throw new ITreeException("Given data structure does not conform to iTree standard");
		}
		if (is_null($data)) {
			$data = "";
		}
		if (!is_array($data)) {
			if (is_string($data)) {
				return $data;
			}
			throw new ITreeException("Invalid argument. Expected array or string.");
		}
		return implode($tree['escape'], $data);
	}

	/**
	 * Returns an array interpretation of string <code>$data</code> as if it were a component of <code>tree</code>
	 * @param  array $tree
	 * @param  string|array $data
	 * @return array
	 */
	function itree_as_data($tree, $data) {
		if (!is_itree($tree)) {
			throw new ITreeException("Given data structure does not conform to iTree standard");
		}
		if (is_array($data)) {
			$data = itree_flatten($tree, $data);
		} else if (!is_string($data)) {
			throw new ITreeException("Invalid argument. Expected either string or array.");
		}
		$tree['data'] = $data;
		return itree_get_data($tree);
	}

	/**
	 * Checks whether the specified tree includes the item
	 * @param  array $tree
	 * @param  array|string $item
	 * @return bool
	 */
	function itree_includes($tree, $item) {
		if (!is_itree($tree)) {
			throw new ITreeException("Given data structure does not conform to iTree standard");
		}
		$data = itree_get_data($tree);
		$item = itree_as_data($tree, $item);
		for ($i = 0; $i < count($item); $i++) {
			$item = $item[$i];
			$includes = false;
			for ($j = 0; $j < count($data); $j++) {
				if ($data[$j] == ITREE_ALL_INCLUSIVE) {
					$includes = $item != ITREE_ALL_EXCLUSIVE;
				} else {
					if (strlen($data[$j]) > strlen($item)) {
						continue;
					}
					if (substr($item . $tree['level'], 0, strlen($data[$j] . $tree['level'])) == $data[$j] . $tree['level']) {
						$includes = true;
					}
				}
				if ($includes) {
					break;
				}
			}
			if (!$includes) {
				return false;
			}
		}
		return true;
	}

	/**
	 * Adds the item and restructures the tree
	 * @param  array $tree
	 * @param  string|array $item
	 * @return void
	 */
	function itree_add(&$tree, $item) {
		if (!is_itree($tree)) {
			throw new ITreeException("Given data structure does not conform to iTree standard");
		}
		$item = itree_as_data($tree, $item);
		$data = itree_get_data($tree);
		for ($i = 0; $i < count($item); $i++) {
			$value = $item[$i];
			array_push($data, $value);
		}
		sort($data);
		$raw = $data;
		$data = array();
		for ($i = 0; $i < count($raw); $i++) {
			$item = $raw[$i];
			if ($item == ITREE_ALL_INCLUSIVE) {
				$tree['data'] = $item;
				return;
			}
			if (!itree_includes(itree_create(itree_flatten($tree, $data), $tree['escape'], $tree['level']), $item)) {
				array_push($data, $item);
			}
		}
		$tree['data'] = itree_flatten($tree, $data);
	}

	/**
	 * Removes the item (and all of its children) from the tree
	 * @param  array $tree
	 * @param  string|array $item
	 * @return void
	 */
	function itree_remove(&$tree, $item) {
		if (!is_itree($tree)) {
			throw new ITreeException("Given data structure does not conform to iTree standard");
		}
		$raw = itree_get_data($tree);
		$data = array();
		$item = itree_as_data($tree, $item);
		for ($i = 0; $i < count($item); $i++) {
			$value = $item[$i];
			if ($value == ITREE_ALL_INCLUSIVE) {
				$tree['data'] = "";
				return;
			}
		}
		for ($i = 0; $i < count($raw); $i++) {
			$value = $raw[$i];
			if (!itree_includes(itree_create($item), $value)) {
				array_push($data, $value);
			}
		}
		$tree['data'] = itree_flatten($tree, $data);
	}

}

?>