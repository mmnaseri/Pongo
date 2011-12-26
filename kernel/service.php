<?php

/**
 * pongo PHP Framework Kernel Component
 * Package SERVICE
 * @author Mohammad Milad Naseri (m.m.naseri at gmail.com)
 * @since 1.0 (6/12/11, 19:13)
 * @package com.agileapes.pongo.kernel.service
 */

if (!defined("KERNEL_COMPONENT_SERVICE")) {

	/***
	 * Kernel Component Registration
	 **/
	define('KERNEL_COMPONENT_SERVICE', "");

	/***
	 * Kernel Component Implementation
	 **/

	define('KCE_SERVICE', 5000);
	define('DEFAULT_NULLABLE_VALUE', false);
	define('GLOBAL_CONTEXT_SERVICES', 'GLOBAL_CONTEXT_SERVICES');
	globals_context_register(GLOBAL_CONTEXT_SERVICES);

	class ServiceException extends KernelException {

		protected function getBase() {
			return KCE_BASE;
		}

	}

	class ServiceNotFoundException extends ServiceException {

		protected function getBaseCode() {
			return 2;
		}

	}

	class ServiceDefinitionException extends ServiceException {

		protected function getBaseCode() {
			return 3;
		}

	}

	class ServiceAccessException extends ServiceException {

		protected function getBaseCode() {
			return 3;
		}

	}

	abstract class Service {

		function getInstance() {
			return $this;
		}

		function ___call($name, $arguments) {
			$name = str_replace(".", "_", $name);
			$methods = get_class_methods(get_class($this->getInstance()));
			//Removing hidden functions (starting with three underscores)
			for ($i = 0; $i < count($methods); $i++) {
				$method = $methods[$i];
				if ($method == "getInstance" || strpos($method, "___") === 0) {
					unset($methods[$i]);
				}
			}
			sort($methods);
			if (array_search($name, $methods) === false) {
				throw new ServiceException("No such service gateway: %1", null, array($name));
			}
			$name .= "(\$arguments);";
			$name = "return \$this->" . $name;
			return eval($name);
		}

	}

	function service_load($file) {
		$path = url_local($file);
		if (!file_exists($path)) {
			throw new ServiceNotFoundException("Service not found: %1", null, array($file));
		}
		$xml = xmlnode_from_file($path);
		$namespace = array_get(array_get(xmlnode_path($xml, 'services//$attributes("xmlns")'), 0, array()), '@xmlns', "");
		if ($namespace != 'http://www.agileapes.com/pongo/services') {
			throw new ServiceDefinitionException("Invalid namespace specified: %1 (file=%2)", null, array($namespace, $file));
		}
		$services = xmlnode_path($xml, "services//service");
		for ($i = 0; $i < count($services); $i++) {
			$service = $services[$i];
			foreach ($service as $key => $gateway) {
				if (xmlnode_get_node_name($key) != 'gateway') {
					continue;
				}
				if (!is_array($gateway)) {
					continue;
				}
				$location = array_get($service, '@location');
				$location = dirname($file) . "/" . $location;
				$location = url_clean($location);
				$definition = service_define(array_get($service, '@name'), array_get($gateway, '@id'), $location);
				$definition['encode'] = boolval(array_get($gateway, '@json'));
				$definition['stateful'] = boolval(array_get($gateway, '@stateful', false));
				$definition['name'] = array_get($gateway, '@name', array_get($gateway, '@id'));
				$definition['return'] = array_get($gateway, '@returns', 'any');
				$arguments = xmlnode_path($gateway, '/argument');
				for ($j = 0; $j < count($arguments); $j++) {
					$arguments[$j]['@name'] = array_get($arguments[$j], '@name', $j);
					$arguments[$j]['@optional'] = boolval(array_get($arguments[$j], '@optional', DEFAULT_NULLABLE_VALUE));
					$arguments[$j]['@type'] = array_get($arguments[$j], '@type', 'any');
					if ($arguments[$j]['@optional'] === true && ($j < count($arguments) - 1) && boolval(array_get($arguments[$j + 1], '@optional', DEFAULT_NULLABLE_VALUE)) == false) {
						throw new ServiceDefinitionException("Optional arguments cannot come before required arguments (service=" . $definition['service'] . ",gateway=" . $definition['gateway'] . ")");
					}
					array_push($definition['arguments'], array('name' => $arguments[$j]['@name'], 'optional' => $arguments[$j]['@optional'], 'type' => $arguments[$j]['@type']));
				}
				service_register($definition['service'], $definition['gateway'], $definition);
			}
		}
	}

	function service_register($service, $gateway, $definition) {
		if (array_key_exists('service', $definition)) {
			unset($definition['service']);
		}
		if (array_key_exists('gateway', $definition)) {
			unset($definition['gateway']);
		}
		$name = $service . "." . $gateway;
		cache_set("service:" . $name, $definition);
	}

	function service_define($service, $gateway, $location) {
		if (empty($service)) {
			throw new ServiceDefinitionException("Service must have a name");
		}
		if (empty($gateway)) {
			throw new ServiceDefinitionException("Gateway must have a name (service=%1)", null, array($service));
		}
		if (empty($location)) {
			throw new ServiceDefinitionException("Service must have a location (service=%1,gateway=%2)", null, array($service, $gateway));
		}
		return array('service' => $service, 'gateway' => $gateway, 'name' => $gateway, 'location' => $location, 'encode' => true, 'stateful' => false, 'return' => null, 'arguments' => array());
	}

	function service_bind($values, $arguments, $match = true) {
		$result = array();
		for ($i = 0; $i < count($arguments); $i++) {
			$name = $arguments[$i]['name'];
			if ($i >= count($values)) {
				if (!$arguments[$i]['optional']) {
					throw new ServiceAccessException("Missing value for argument %1", null, array($name));
				} else {
					continue;
				}
			}
			$type = $arguments[$i]['type'];
			$input = $values[$i];
			$value = null;
			if ($type == 'any' || !$match) {
				$value = $input;
			} else {
				$value = service_match_type($type, $input, $name);
			}
			$result[$name] = $value;
		}
		return $result;
	}

	function service_match_type($type, $input, $name) {
		$value = null;
		switch ($type) {
			case "bool":
				$value = boolval($input);
				break;
			case "string":
				$value = strval($input);
				break;
			case "numeric":
				if (!is_numeric($input)) {
					throw new ServiceAccessException("Expected a numberic value while got %1 for %2", null, array($input, $name));
				}
				$value = $input;
				break;
			case "array":
				if (!is_numeric($input)) {
					throw new ServiceAccessException("Expected a numberic value while got %1 for %2", null, array($input, $name));
				}
				$value = $input;
				break;
		}
		return $value;
	}

	function service_callv($service, $gateway, $arguments, $match = true) {
		if (!array_key_exists('currentUrl', $_POST)) {
			$_POST['currentUrl'] = array_get($_GET, ':url', '');
		} else {
			$_POST['currentUrl'] = json_decode($_POST['currentUrl']);
		}
		$name = "$service.$gateway";
		//		$definition = globals_get(GLOBAL_CONTEXT_GATEWAYS, $name, null);
		$definition = service_definition($service, $gateway);
		if ($definition === null) {
			throw new ServiceNotFoundException("Undefined service accessed: %1", null, array($name));
		}
		$values = service_bind($arguments, $definition['arguments'], $match);
		$instance = globals_get(GLOBAL_CONTEXT_SERVICES, $name, null);
		if ($instance === null) {
			/** @noinspection PhpIncludeInspection */
			$instance = entity_load($definition['location'], 'Service');
			globals_set(GLOBAL_CONTEXT_SERVICES, $name, $instance);
		}
		if ($instance === null) {
			throw new ServiceAccessException("Could not instantiate the service: %1", null, array($service));
		}
		/** @noinspection PhpUndefinedMethodInspection */
		$result = $instance->___call($definition['name'], $values);
		if (!$match || array_get($definition, 'returns', 'any') == 'any') {
			return $result;
		}
		return service_match_type($definition['returns'], $result, $name);
	}

	function service_call($service, $gateway) {
		$arguments = array();
		for ($i = 2; $i < func_num_args(); $i++) {
			array_push($arguments, func_get_arg($i));
		}
		return service_callv($service, $gateway, $arguments, true);
	}

	function service_callx($service, $gateway) {
		$arguments = array();
		for ($i = 2; $i < func_num_args(); $i++) {
			array_push($arguments, func_get_arg($i));
		}
		return service_callv($service, $gateway, $arguments, false);
	}

	function service_discover($module) {
		$file = modules_file($module, "services.xml");
		if (!file_exists(url_local($file))) {
			return;
		}
		service_load($file);
	}

	function service_discover_all() {
		$list = modules_list();
		for ($i = 0; $i < count($list); $i++) {
			service_discover($list[$i]);
		}
		cache_set('init:service', true);
	}

	function service_unload($service) {
		$keys = cache_keys("service:$service.*");
		for ($i = 0; $i < count($keys); $i++) {
			cache_delete($keys[$i]);
		}
		cache_delete('init:service');
	}

	function service_unload_all() {
		$keys = cache_keys("service:*");
		for ($i = 0; $i < count($keys); $i++) {
			cache_delete($keys[$i]);
		}
	}

	function service_definition($service, $gateway) {
		$name = "$service.$gateway";
		$definition = cache_get("service:" . $name); //globals_get(GLOBAL_CONTEXT_GATEWAYS, $name, null);
		if ($definition === null) {
			throw new ServiceNotFoundException("Undefined service accessed: %1", null, array($name));
		}
		return $definition;
	}

}

?>