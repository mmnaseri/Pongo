<?php

/**
 * pongo PHP Framework Kernel Component
 * Package MODULES
 * @author Mohammad Milad Naseri (m.m.naseri at gmail.com)
 * @since 1.0 (7/12/11, 16:33)
 * @package com.agileapes.pongo.kernel.modules
 */

if (!defined("KERNEL_COMPONENT_MODULES")) {

	/***
	 * Kernel Component Registration
	 **/
	define('KERNEL_COMPONENT_MODULES', "");

	/***
	 * Kernel Component Implementation
	 **/

	define('KCE_MODULES', 4000);

	class ModulesException extends KernelException {

		protected function getBase() {
			return KCE_MODULES;
		}

		protected function getBaseCode() {
			return 1;
		}
	}

	function modules_root($module) {
		return "/modules/" . $module;
	}

	function modules_file($module, $file) {
		return url_clean(modules_root($module) . "/" . $file);
	}

	function modules_list() {
		$dir = opendir(url_local(modules_root("")));
		$result = array();
		while (($file = readdir($dir)) != null) {
			if ($file[0] == '.') {
				continue;
			}
			array_push($result, $file);
		}
		closedir($dir);
		return $result;
	}

}

?>