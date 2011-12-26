<?php

/**
 * pongo PHP Framework Kernel Component
 * Package MODULES
 * @author Mohammad Milad Naseri (m.m.naseri at gmail.com)
 * @since 1.0 (7/12/11, 16:33)
 * @package com.agileapes.pongo.kernel.log
 */

if (!defined("KERNEL_COMPONENT_LOG")) {

	/***
	 * Kernel Component Registration
	 **/
	define('KERNEL_COMPONENT_LOG', "");

	/***
	 * Kernel Component Implementation
	 **/

	define("LOG_TYPE_ERROR", "ERROR");
	define("LOG_TYPE_WARNING", "WARNING");
	define("LOG_TYPE_INFO", "INFO");
	define("LOG_TYPE_AUTO", "AUTO");
	define("LOG_TYPE_OTHER", "OTHER");
	define("LOG_DEFAULT_FILE", "general");

	function log_message($category, $type, $message) {
		$str = "[" . $type . "] " . date("Y-m-d H:i:s P") . " " . $message . "\n";
		$handle = fopen(url_base_local() . "/contents/log/" . $category . ".log", "a");
		fwrite($handle, $str);
	}

	function log_error($message, $category = LOG_DEFAULT_FILE) {
		log_message($category, LOG_TYPE_ERROR, $message);
	}

	function log_warning($message, $category = LOG_DEFAULT_FILE) {
		log_message($category, LOG_TYPE_WARNING, $message);
	}

	function log_info($message, $category = LOG_DEFAULT_FILE) {
		log_message($category, LOG_TYPE_INFO, $message);
	}

	function log_auto($message, $category = LOG_DEFAULT_FILE) {
		log_message($category, LOG_TYPE_AUTO, $message);
	}

	function log_other($message, $category = LOG_DEFAULT_FILE) {
		log_message($category, LOG_TYPE_OTHER, $message);
	}

}


?>