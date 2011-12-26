<?php
/**
 * pongo PHP Framework Kernel Component
 * Package URL
 * @author Mohammad Milad Naseri (m.m.naseri at gmail.com)
 * @since 1.0 (15/11/11, 12:45)
 * @package com.agileapes.pongo.kernel.url
 */

if (!defined('KERNEL_COMPONENT_URL')) {

	/***
	 * Kernel Component Registration
	 **/
	define('KERNEL_COMPONENT_URL', "");

	/***
	 * Kernel Component Implementation
	 **/

	define('KERNEL_COMPONENT_URL_GLOBALS', "KERNEL_COMPONENT_URL_GLOBALS");
	define('CONFIG_URL_PRETTY', "url_pretty");

	$redirecting = null;
	$relocating = null;

	globals_context_register(KERNEL_COMPONENT_URL_GLOBALS);

	function url_config_set($config, $value) {
		globals_set(KERNEL_COMPONENT_URL_GLOBALS, $config, $value);
	}

	function url_config_get($config, $default = "") {
		return globals_get(KERNEL_COMPONENT_URL_GLOBALS, $config, $default);
	}

	function url_base() {
		return dirname($_SERVER['SCRIPT_NAME']);
	}

	function url_base_local() {
		return dirname($_SERVER["SCRIPT_FILENAME"]);
	}

	function url_relative($relative_path) {
		$path = url_base() . "/" . $relative_path;
		$path = url_clean($path);
		return $path;
	}

	function url_local($relative_path) {
		$path = url_base_local() . "/" . $relative_path;
		$path = url_clean($path);
		return $path;
	}

	function url_clean($path) {
		while (strpos($path, "//") !== false) {
			$path = str_replace("//", "/", $path);
		}
		return $path;
	}

	function url_link($target) {
		if (url_config_get(CONFIG_URL_PRETTY) === true) {
			return url_relative($target);
		}
		return url_relative("router.php?:url=" . urlencode($target));
	}

	function url_redirecting() {
		global $redirecting;
		return $redirecting;
	}

	function url_redirect($url) {
		global $redirecting;
		if ($redirecting !== null) {
			return;
		}
		$redirecting = $url;
	}

	function url_relocating() {
		global $relocating;
		return $relocating;
	}

	function url_relocate($url, $delay = 0) {
		global $relocating;
		if ($relocating !== null) {
			return;
		}
		if (page_options_get('context') == 'body') {
			if ($delay <= 0) {
				echo('<script type="text/javascript">Page.navigate("' . $url . '");</script>');
			} else {
				echo('<script type="text/javascript">setTimeout(function() {
					Page.navigate(' . $url . ');
				}, ' . ($delay * 1000) . ')</script>');
			}
		}
		$relocating = array('url' => $url, 'delay' => $delay);
	}

	/***
	 * Default Configuration
	 **/

	url_config_set(CONFIG_URL_PRETTY, true);

	define('DEFAULT_BEHAVIOUR_STATIC', false);

}

?>