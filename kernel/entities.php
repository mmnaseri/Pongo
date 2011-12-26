<?php

/**
 * pongo PHP Framework Kernel Component
 * Package ENTITIES
 * @author Mohammad Milad Naseri (m.m.naseri at gmail.com)
 * @since 1.0 (6/12/11, 13:06)
 * @package com.agileapes.pongo.kernel.entities
 */

if (!defined("KERNEL_COMPONENT_ENTITIES")) {

	/***
	 * Kernel Component Registration
	 **/
	define('KERNEL_COMPONENT_ENTITIES', "");

	/***
	 * Kernel Component Implementation
	 **/

	define("KCE_ENTITIES", 3000);

	class EntityLoadingException extends KernelException {

		protected function getBase() {
			return KCE_ENTITIES;
		}

		protected function getBaseCode() {
			return 1;
		}

	}

	/**
	 * Returns the name of the entity as specified in the target file
	 * @param $file (local) file name
	 * @return string
	 * @throws EntityLoadingException
	 */
	function entity_name($file) {
		$path = url_local($file);
		if (!file_exists($path)) {
			throw new EntityLoadingException("File not found: %1", null, array($file));
		}
		$contents = @file_get_contents($path);
		if (!preg_match('/\/\/@Entity\n\s*class\s+([^\s]+)\s+extends\s+([^\s]+)\s*\{/msi', $contents, $matches)) {
			throw new EntityLoadingException("Invalid entity file format: %1", null, array($file));
		}
		$entity = trim($matches[1]);
		return $entity;
	}

	/**
	 * Returns the name of the entity as specified in the target file
	 * @param $file (local) file name
	 * @param $super_class
	 * @return object
	 * @throws EntityLoadingException
	 */
	function entity_load($file, $super_class) {
		$entity = entity_name($file);
		if (!class_exists($entity)) {
			$path = url_local($file);
			/** @noinspection PhpIncludeInspection */
			include($path);
		}
		$result = eval("return new " . $entity . "();");
		if (!is_subclass_of($result, $super_class)) {
			throw new EntityLoadingException("Expected %1 to extend %2 at %3", null, array($entity, $super_class, $file));
		}
		return $result;
	}

}

?>