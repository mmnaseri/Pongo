<?php

/**
 * pongo PHP Framework Kernel Component
 * Package KERNEL
 * @author Mohammad Milad Naseri (m.m.naseri at gmail.com)
 * @since 1.0 (9/12/11, 18:29)
 * @package com.agileapes.pongo.kernel.kernel
 */

if (!defined("KERNEL_COMPONENT_KERNEL")) {

	/***
	 * Kernel Component Registration
	 **/
	define('KERNEL_COMPONENT_KERNEL', "");

	/***
	 * Kernel Component Implementation
	 **/

	define("GLOBAL_CONTEXT_KERNEL_OPTIONS", "kernel_options");

	globals_context_register(GLOBAL_CONTEXT_KERNEL_OPTIONS);

	/***
	 * Kernel Component Implementation
	 **/

	/**
	 * @param string $option
	 * @param mixed $value
	 * @return void
	 */
	function kernel_option_set($option, $value) {
		globals_set(GLOBAL_CONTEXT_KERNEL_OPTIONS, $option, $value);
	}

	/**
	 * @param string $option
	 * @param mixed $default
	 * @return mixed
	 */
	function kernel_option_get($option, $default = "") {
		return globals_get(GLOBAL_CONTEXT_KERNEL_OPTIONS, $option, $default);
	}

	/**
	 * @param string $option
	 * @return mixed
	 */
	function kernel_option_delete($option) {
		return globals_delete(GLOBAL_CONTEXT_KERNEL_OPTIONS, $option);
	}

	/**
	 * Loads the options from cache
	 */
	function kernel_option_load() {
		$keys = cache_keys("option:*");
		for ($i = 0; $i < count($keys); $i++) {
			$key = $keys[$i];
			$key = substr($key, 7);
			kernel_option_set($key, cache_get($key));
		}
	}

	/**
	 * Save the options to the cache
	 */
	function kernel_option_save() {
		$keys = cache_keys("option:");
		for ($i = 0; $i < count($keys); $i++) {
			cache_delete($keys[$i]);
		}
		$list = kernel_option_list();
		foreach ($list as $key => $value) {
			cache_set('option:' . $key, $value);
		}
	}

	/**
	 * @return array
	 */
	function kernel_option_list() {
		return globals_context_get(GLOBAL_CONTEXT_KERNEL_OPTIONS);
	}

	/**
	 * @param string $key
	 * @param mixed $default
	 * @return mixed
	 */
	function kernel_config_get($key = null, $default = "") {
		preg_match("/^(.*)\\/[^\\/]+$/mi", __FILE__, $dir);
		$dir = $dir[1];
		$dir .= "/" . kernel_option_get('com.agileapes.pongo.config.dir', './config');
		if ($dir[strlen($dir) - 1] != '/') {
			$dir .= "/";
		}
		$config = array();
		if (file_exists($dir . "config.php")) {
			/** @noinspection PhpIncludeInspection */
			include($dir . "config.php");
		}
		if ($key === null) {
			return $config;
		} else {
			$value = $config;
			$key = array("", $key);
			do {
				if (count($key) != 2) {
					return $default;
				}
				$key = explode("/", $key[1], 2);
				if (array_key_exists($key[0], $value)) {
					$value = $value[$key[0]];
				} else {
					return $default;
				}
				if (count($key) == 1) {
					return $value;
				}
			} while (true);
			return $value;
		}
	}

	/**
	 * @param string $key
	 * @param mixed $value
	 * @return void
	 */
	function kernel_config_set($key, $value) {
		preg_match("/^(.*)\\/[^\\/]+$/mi", __FILE__, $dir);
		$dir = $dir[1];
		$dir .= "/" . kernel_option_get('com.agileapes.pongo.config.dir', './config');
		if ($dir[strlen($dir) - 1] != '/') {
			$dir .= "/";
		}
		$config = array();
		if (file_exists($dir . "config.php")) {
			/** @noinspection PhpIncludeInspection */
			include($dir . "config.php");
		}
		$config[$key] = $value;
		if ($value === null) {
			unset($config[$key]);
			sort($config);
		}
		$src = strings_from_array($config, 0);
		$src = "<" . "?php\n\$config = " . $src . ";\n?>";
		file_set_contents($dir . "config.php", $src);
	}

	/**
	 * @param string $out
	 * @param bool $echo
	 * @param bool $decorate
	 * @param string $dir
	 * @return string
	 */
	function kernel_out($out = "", $echo = true, $decorate = true, $dir = "ltr") {
		if (is_bool($out)) {
			$out = $out ? "TRUE" : "FALSE";
		}
		$out = htmlspecialchars(print_r($out, true));
		$src = "";
		if ($decorate) {
			$src .= "<pre style='";
			$src .= "border: 1px dashed black; background-color: #fefefe; ";
			$src .= "max-height: 500px; padding: 5px; overflow: auto; direction: $dir;'>";
		}
		if ($out == "") {
			$out = "(empty)";
		}
		$src .= $out;
		if ($decorate) {
			$src .= "</pre>";
		}
		if ($echo) {
			echo($src);
		}
		return $src;
	}

	function kernel_locale_init() {
		if (empty($_GET['bf:locale'])) {
			$_GET['bf:locale'] = kernel_option_get('com.agileapes.pongo.defaults.locale.language', 'en') . "_" . kernel_option_get('settings.locale.country', 'US');
		}
		$locale = explode('_', $_GET['bf:locale']);
		if (count($locale) != 2) {
			$locale = array(kernel_option_get('com.agileapes.pongo.defaults.locale.language', 'en'), kernel_option_get('settings.locale.country', 'US'));
		}
		$_GET['bf:language'] = $locale[0];
		$_GET['bf:country'] = $locale[1];
		if (i18n_region_path($_GET['bf:language'], $_GET['bf:country']) === false) {
			$_GET['bf:language'] = kernel_option_get('com.agileapes.pongo.defaults.locale.language', 'en');
			$_GET['bf:country'] = kernel_option_get('com.agileapes.pongo.defaults.locale.country', 'US');
		}
	}

}

?>