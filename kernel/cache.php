<?php

/**
 * pongo PHP Framework Kernel Component
 * Package CACHE_INTEGRATION
 * @author Mohammad Milad Naseri (m.m.naseri at gmail.com)
 * @since 1.0 (10/12/11, 0:25)
 * @package com.agileapes.pongo.kernel.cache
 */

if (!defined("KERNEL_COMPONENT_CACHE_INTEGRATION")) {

	/***
	 * Kernel Component Registration
	 **/
	define('KERNEL_COMPONENT_CACHE_INTEGRATION', "");

	/***
	 * Kernel Component Implementation
	 **/

	define('KCE_CACHE', 11000);

	/**
	 * Determines if the cache is present or if the cache requests are being
	 * handled by the database
	 * @return bool
	 */
	function cache_present() {
		global $kernel_base_dir;
		/** @noinspection PhpIncludeInspection */
		require_once($kernel_base_dir . "/../contents/library/Rediska.php");
		try {
			$rediska = @(new Rediska());
		} catch (Exception $e) {
			return false;
		}
		return true;
	}

	/**
	 * Writes the given value into the cache
	 * @param $key
	 * @param $value
	 */
	function cache_set($key, $value) {
		global $kernel_base_dir;
		/** @noinspection PhpIncludeInspection */
		require_once($kernel_base_dir . "/../contents/library/Rediska.php");
		$value = strings_serialize($value);
		try {
			$rediska = @(new Rediska());
			$rediska->set($key, $value);
		} catch (Exception $e) {
			if (!db_table_exists('cache_domain')) {
				db_table_create('cache_domain', array('key' => db_create_column(CT_TEXT), 'value' => db_create_column(CT_TEXT),), DATABASE_TABLE_FAST);
			}
			if (db_exists('cache_domain', array('key' => db_quote($key)))
			) {
				db_update('cache_domain', array('value' => db_quote($value)), array('key' => db_quote($key)));
			} else {
				db_insert('cache_domain', array('key' => db_quote($key), 'value' => db_quote($value),));
			}
		}
	}

	/**
	 * Returns the value stored in cache
	 * @param $key
	 * @param mixed $default
	 * @return mixed
	 */
	function cache_get($key, $default = null) {
		global $kernel_base_dir;
		$value = null;
		/** @noinspection PhpIncludeInspection */
		require_once($kernel_base_dir . "/../contents/library/Rediska.php");
		try {
			$rediska = @(new Rediska());
			$value = strings_deserialize($rediska->get($key));
		} catch (Exception $e) {
			if (!db_table_exists('cache_domain')) {
				db_table_create('cache_domain', array('key' => db_create_column(CT_TEXT), 'value' => db_create_column(CT_TEXT),), DATABASE_TABLE_FAST);
			}
			$result = db_load(array('cache_domain' => array('value')), array('key' => db_quote($key)));
			if (count($result) > 0) {
				$value = strings_deserialize($result[0]['value']);
			} else {
				$value = null;
			}
		}
		if ($value == null) {
			$value = $default;
		}
		return $value;
	}

	/**
	 * Returns the keys matching the given pattern
	 * @param $pattern
	 * @return array|mixed|null
	 */
	function cache_keys($pattern) {
		global $kernel_base_dir;
		/** @noinspection PhpIncludeInspection */
		require_once($kernel_base_dir . "/../contents/library/Rediska.php");
		$result = null;
		try {
			$rediska = @(new Rediska());
			$result = $rediska->getKeysByPattern($pattern);
		} catch (Exception $e) {
			if (!db_table_exists('cache_domain')) {
				db_table_create('cache_domain', array(
					'key' => db_create_column(CT_TEXT),
					'value' => db_create_column(CT_TEXT)
				), DATABASE_TABLE_FAST);
			}
			$pattern = str_replace('*', '%', $pattern);
			$pattern = str_replace('?', '%', $pattern);
			$result = db_load('cache_domain', array('key' => array("LIKE", db_quote($pattern))));
			for ($i = 0; $i < count($result); $i++) {
				$result[$i] = $result[$i]['key'];
			}
		}
		return $result;
	}

	/**
	 * Deletes the specified entry from cache
	 * @param $key
	 */
	function cache_delete($key) {
		global $kernel_base_dir;
		/** @noinspection PhpIncludeInspection */
		require_once($kernel_base_dir . "/../contents/library/Rediska.php");
		try {
			$rediska = @(new Rediska());
			$rediska->delete($key);
		} catch (Exception $e) {
			if (!db_table_exists('cache_domain')) {
				db_table_create('cache_domain', array(
					'key' => db_create_column(CT_TEXT),
					'value' => db_create_column(CT_TEXT)
				), DATABASE_TABLE_FAST);
			}
			db_delete('cache_domain', array('key' => db_quote($key)));
		}
	}

}

?>