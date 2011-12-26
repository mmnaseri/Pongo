<?php

/**
 * pongo PHP Framework Kernel Component
 * Package LOADER
 * @author Mohammad Milad Naseri (m.m.naseri at gmail.com)
 * @since 1.0 (9/12/11, 18:44)
 * @package com.agileapes.pongo.kernel.LOADER
 */

if (!defined("KERNEL_COMPONENT_LOADER")) {

	/***
	 * Kernel Component Registration
	 **/
	define('KERNEL_COMPONENT_LOADER', "");

	/***
	 * Kernel Component Implementation
	 **/

	$kernel_base_dir = "./kernel";

	$components = array("base", "globals", "kernel", "page", "entities", "url", "xml", "xmlnode", "service", "modules", "database", "log", "state", "strings", "cache", "i18n", "widgets", "theme", "forms", "yql", "itree", "users",);

	for ($i = 0; $i < count($components); $i++) {
		/** @noinspection PhpIncludeInspection */
		require($kernel_base_dir . "/" . $components[$i] . ".php");
	}
}

?>