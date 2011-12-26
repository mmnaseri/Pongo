<?php

/**
 * pongo PHP Framework Kernel Component
 * Package GLOBALS
 * @author Mohammad Milad Naseri (m.m.naseri at gmail.com)
 * @since 1.0 (15/11/11, 15:52)
 * @package com.agileapes.pongo.kernel.GLOBALS
 */

if (!defined("KERNEL_COMPONENT_GLOBALS")) {

	/***
	 * Kernel Component Registration
	 **/
	define('KERNEL_COMPONENT_GLOBALS', "");

	/***
	 * Kernel Component Implementation
	 **/

	define("KCE_GLOBALS", 2000);

	class GlobalsException extends KernelException {

		protected function getBase() {
			return KCE_GLOBALS;
		}

	}

	class UnknownGlobalContextException extends GlobalsException {
		protected function getBaseCode() {
			return 1;
		}

	}

	;
	class DuplicateGlobalContextException extends GlobalsException {

		protected function getBaseCode() {
			return 2;
		}

	}

	;

	/**
	 * Constants
	 */

	$_GLOBALS = array();

	/**
	 * @param  string $context
	 * @return array
	 */
	function globals_context_get($context) {
		if (!globals_context_exists($context)) {
			throw new UnknownGlobalContextException("No such global context (%1)", null, array($context));
		}
		global $_GLOBALS;
		return $_GLOBALS[$context];
	}

	/**
	 * @param  string $context
	 * @return bool
	 */
	function globals_context_exists($context) {
		global $_GLOBALS;
		return array_key_exists($context, $_GLOBALS);
	}

	/**
	 * @param  string $context
	 * @param bool $overwrite
	 * @return void
	 */
	function globals_context_register($context, $overwrite = false) {
		global $_GLOBALS;
		if (globals_context_exists($context) && !$overwrite) {
			throw new DuplicateGlobalContextException("Duplicate global context (%1)", null, array($context));
		}
		$_GLOBALS[$context] = array();
	}

	/**
	 * @param  string $context
	 * @param bool $no_error
	 * @return void
	 */
	function globals_context_unregister($context, $no_error = false) {
		global $_GLOBALS;
		if (!globals_context_exists($context) && !$no_error) {
			throw new UnknownGlobalContextException("No such global context (%1)", null, array($context));
		}
		unset($_GLOBALS[$context]);
	}

	/**
	 * @param string $context
	 * @param string $key
	 * @param mixed $value
	 * @return void
	 */
	function globals_set($context, $key, $value) {
		if (!globals_context_exists($context)) {
			throw new UnknownGlobalContextException("No such context (%1)", null, array($context));
		}
		global $_GLOBALS;
		//		if ($value === null) {
		//			if (array_key_exists($key, $_GLOBALS[$context])) {
		//				unset($_GLOBALS[$context][$key]);
		//				array_values($_GLOBALS[$context]);
		//			}
		//		} else {
		//        }
		$_GLOBALS[$context][$key] = $value;
	}

	/**
	 * @param  $context
	 * @param  $key
	 * @param string $default
	 * @return string|mixed
	 */
	function globals_get($context, $key, $default = "") {
		if (!globals_context_exists($context)) {
			throw new UnknownGlobalContextException("No such context (%1)", null, array($context));
		}
		global $_GLOBALS;
		if (!array_key_exists($key, $_GLOBALS[$context])) {
			return $default;
		}
		return $_GLOBALS[$context][$key];
	}

	/**
	 * @param  $context
	 * @param  $key
	 * @return string|mixed
	 */
	function globals_delete($context, $key) {
		if (!globals_context_exists($context)) {
			throw new UnknownGlobalContextException("No such context (%1)", null, array($context));
		}
		global $_GLOBALS;
		if (!array_key_exists($key, $_GLOBALS[$context])) {
			return false;
		}
		unset($_GLOBALS[$context][$key]);
		return true;
	}

	/**
	 * @param  string $context
	 * @return int
	 */
	function globals_size($context) {
		if (!globals_context_exists($context)) {
			throw new UnknownGlobalContextException("No such context (%1)", null, array($context));
		}
		global $_GLOBALS;
		return count($_GLOBALS[$context]);
	}

}

?>