<?php

/**
 * pongo PHP Framework Kernel Component
 * Package FORMS
 * @author Mohammad Milad Naseri (m.m.naseri at gmail.com)
 * @since 1.0 (12/12/11, 21:04)
 * @package com.agileapes.pongo.kernel.forms
 */

if (!defined("KERNEL_COMPONENT_FORMS")) {

	/***
	 * Kernel Component Registration
	 **/
	define('KERNEL_COMPONENT_FORMS', "");

	/***
	 * Kernel Component Implementation
	 **/

	define('KCE_FORMS', 12000);

	class FormsException extends KernelException {

		protected function getBase() {
			return KCE_FORMS;
		}

		protected function getBaseCode() {
			return 1;
		}

	}

	abstract class FormObject {

		private $scripts = array();
		private $styles = array();
		private $form = null;

		public abstract function render();

		public function setScripts($scripts) {
			$this->scripts = $scripts;
		}

		public function getScripts() {
			return $this->scripts;
		}

		public function setStyles($styles) {
			$this->styles = $styles;
		}

		public function getStyles() {
			return $this->styles;
		}

		public function setForm($form) {
			$this->form = $form;
		}

		/**
		 * @return Form
		 */
		public function getForm() {
			return $this->form;
		}

	}

	class Form extends FormObject {

		private $name;
		private $target;
		private $method = 'post';
		private $enctype = null;
		private $layout = null;
		private $components = array();

		function __construct() {
		}

		public function setName($name) {
			$this->name = $name;
		}

		public function getName() {
			return $this->name;
		}

		public function setTarget($target) {
			$this->target = url_relative(page_options_get('locale', 'en_US') . "/" . $target);
		}

		public function getTarget() {
			return $this->target;
		}

		public function setLayout(FormLayout $layout) {
			$layout->setForm($this);
			$this->layout = $layout;
		}

		public function getLayout() {
			return $this->layout;
		}

		public function setEnctype($enctype) {
			$this->enctype = $enctype;
		}

		public function getEnctype() {
			return $this->enctype;
		}

		public function setMethod($method) {
			$this->method = $method;
		}

		public function getMethod() {
			return $this->method;
		}

		public function getComponents() {
			return $this->components;
		}

		public function addComponent(FormComponent $component) {
			array_push($this->components, $component);
		}

		public function render() {
			if ($this->getLayout() == null) {
				throw new FormsException("Form does not have any layout");
			}
			$src = '<form method="' . $this->getMethod() . '" name="' . $this->getName() . '" id="' . $this->getName() . '" action="' . $this->getTarget() . '"';
			if ($this->getEnctype() != null) {
				$src .= ' enctype="' . $this->getEnctype() . '"';
			}
			$src .= ">";
			$src .= "<input type='hidden' name='" . $this->getName() . "-form-action' id='" . $this->getName() . "-form-action' value='" . $this->getTarget() . "' />";
			/** @noinspection PhpUndefinedMethodInspection */
			$src .= $this->layout->render();
			$src .= "</form>";
			$src .= '<script type="text/javascript"><!--' . "\n";
			for ($i = 0; $i < count($this->components); $i++) {
				$attribute = $this->components[$i]->getValueAttribute();
				$id = $this->components[$i]->getProperty('id', null);
				if ($this->components[$i]->getProperty('name', '') == '') {
					continue;
				}
				if ($id === null) {
					continue;
				}
				if ($attribute === null || !is_string($attribute)) {
					continue;
				}
				$src .= "Form.registerComponent('" . $this->getName() . "', '$id', '$attribute');\n";
			}
			$src .= "//--></script>\n";
			return $src;
		}

	}

	abstract class FormLayout extends FormObject {

		protected $items = array();
		protected $properties = array();

		public function addLayout(FormLayout $layout) {
			$layout->setForm($this->getForm());
			array_push($this->items, $layout);
		}

		public function addComponent(FormComponent $component) {
			$form = $this->getForm();
			$component->setForm($form);
			$form->addComponent($component);
			array_push($this->items, $component);
		}

		abstract function getName();

		public function setProperty($property, $value) {
			$this->properties[$property] = $value;
		}

		public function getProperty($property, $default = "") {
			return array_get($this->properties, $property, $default);
		}

		public function getItems() {
			return $this->items;
		}

	}

	abstract class FormComponent extends FormObject {

		protected $properties = array();

		public function setProperty($property, $value) {
			if (array_search($property, array('title', 'label', 'caption', 'value', 'text')) !== false && !empty($value)) {
				$value = __($value);
			}
			$this->properties[$property] = $value;
		}

		public function getProperty($property, $default = null) {
			return array_get($this->properties, $property, $default);
		}

		public function getClassName() {
			return trim("component " . $this->getName() . " " . $this->getProperty("class", ""));
		}

		/**
		 * @abstract
		 * It will return a set of properties accepted by the component
		 * If it should return null, all properties will be assumed to be acceptable.
		 * @return array
		 */
		public function getProperties() {
			$attributes = explode(',', FORMS_ATTR_EVENTS);
			$temp = explode(',', FORMS_ATTR_I18N);
			for ($i = 0; $i < count($temp); $i++) {
				array_push($attributes, $temp[$i]);
			}
			return $attributes;
		}

		public function getValueAttribute() {
			return null;
		}

		public abstract function getName();

	}

	define("FORMS_ATTR_CORE_ATTRS", 'name,id,class,style,title');
	define("FORMS_ATTR_I18N", 'lang,dir');
	define("FORMS_ATTR_EVENTS", 'onclick,ondblclick,onmousedown,onmouseup,onmouseover,' . 'onmouseup,onmousemove,onmouseout,onkeypress,onkeydown,onkeyup,onfocus,onblur');
	define("FORMS_ATTR_SET", FORMS_ATTR_CORE_ATTRS . "," . FORMS_ATTR_I18N . "," . FORMS_ATTR_EVENTS);

	define('GLOBAL_CONTEXT_FORM_HANDLERS', 'GLOBAL_CONTEXT_FORM_HANDLERS');
	globals_context_register(GLOBAL_CONTEXT_FORM_HANDLERS);

	define('GLOBAL_CONTEXT_FORM_LAYOUTS', 'GLOBAL_CONTEXT_FORM_LAYOUTS');
	globals_context_register(GLOBAL_CONTEXT_FORM_LAYOUTS);

	/**
	 * @param string $name
	 * @return array
	 * @throws FormsException
	 */
	function forms_load($name) {
		$path = page_options_get('module');
		$path = "/modules/" . $path . "/forms.xml";
		$path = url_local($path);
		if (!file_exists($path)) {
			log_info($path);
			throw new FormsException("Could not locate `forms.xml`");
		}
		$form = xmlnode_path(xmlnode_from_file($path), 'forms//form/@name="' . $name . '"');
		if (count($form) == 0) {
			throw new FormsException("Form not found: %1", null, array($name));
		}
		$form = $form[0];
		return $form;
	}

	/**
	 * @param string $name
	 * @param string $layout
	 */
	function forms_layout_register($name, $layout) {
		globals_set(GLOBAL_CONTEXT_FORM_LAYOUTS, $name, $layout);
	}

	/**
	 * @param string $name
	 */
	function forms_layout_unregister($name) {
		globals_delete(GLOBAL_CONTEXT_FORM_LAYOUTS, $name);
	}

	/**
	 * @param string $name
	 * @return FormLayout
	 * @throws FormsException
	 */
	function forms_layout_get($name) {
		$layout = globals_get(GLOBAL_CONTEXT_FORM_LAYOUTS, $name);
		if (empty($layout)) {
			throw new FormsException("Unknown form layout: %1", null, array($name));
		}
		$layout = entity_load($layout, 'FormLayout');
		return $layout;
	}

	/**
	 * @param string $name
	 * @param string $component
	 */
	function forms_handler_register($name, $component) {
		globals_set(GLOBAL_CONTEXT_FORM_HANDLERS, $name, $component);
	}

	/**
	 * @param string $name
	 */
	function forms_handler_unregister($name) {
		globals_delete(GLOBAL_CONTEXT_FORM_HANDLERS, $name);
	}

	/**
	 * @param string $name
	 * @return FormComponent
	 * @throws FormsException
	 */
	function forms_handler_get($name) {
		$handler = globals_get(GLOBAL_CONTEXT_FORM_HANDLERS, $name);
		if (empty($handler)) {
			throw new FormsException("Unknown form component handler: %1", null, array($name));
		}
		$handler = entity_load($handler, 'FormComponent');
		return $handler;
	}

	/**
	 * @param array $values
	 * @param string|array $attributes
	 * @return string
	 * @throws FormsException
	 */
	function forms_attribute_render($values, $attributes) {
		if (!is_array($attributes)) {
			if (is_string($attributes)) {
				$attributes = explode(',', $attributes);
			} else {
				throw new FormsException("Illegal argument");
			}
		}
		$src = "";
		for ($i = 0; $i < count($attributes); $i++) {
			if (!array_key_exists($attributes[$i], $values)) {
				continue;
			}
			$value = $values[$attributes[$i]];
			$q = strpos($value, '"') !== false ? "'" : '"';
			$src .= " " . $attributes[$i] . "=";
			$src .= $q . $value . $q;
		}
		return $src;
	}

	/**
	 * @param string $name
	 * @return Form
	 * @throws FormsException
	 */
	function forms_build($name) {
		$form = forms_load($name);
		$target = new Form();
		$target->setForm($target);
		foreach ($form as $key => $value) {
			if ($key == '#comment') {
				continue;
			}
			if ($key == '@name') {
				$target->setName($value);
			} else if ($key == '@target') {
				$target->setTarget($value);
			} else if ($key[0] == '@') {
				throw new FormsException('Unexpected attribute (%1 at %2)', null, array($key, $name));
			} else if (xmlnode_get_node_name($key) != 'layout') {
				throw new FormsException('Unexpected member: %1. Expected to find a layout description', null, array(xmlnode_get_node_name($key)));
			} else {
				$target->setLayout(forms_build_layout($value, $target));
			}
		}
		return $target;
	}

	/**
	 * @param array $layout
	 * @param \Form $form
	 * @return FormLayout
	 * @throws FormsException
	 */
	function forms_build_layout($layout, Form $form) {
		$layout_name = array_get($layout, '@name', '');
		if (empty($layout_name)) {
			throw new FormsException("Cannot resolve a layout without a name");
		}
		$target = forms_layout_get($layout_name);
		$target->setForm($form);
		foreach ($layout as $key => $value) {
			if ($key == '#comment') {
				continue;
			}
			if ($key == '@name') {
				continue;
			}
			if ($key[0] == '@') {
				throw new FormsException("Unexpected attribute: %1", null, array($key));
			}
			if (xmlnode_get_node_name($key) == 'layout') {
				$target->addLayout(forms_build_layout($value, $form));
			} else if (xmlnode_get_node_name($key) == 'component') {
				$target->addComponent(forms_build_component($value, $form));
			} else if (xmlnode_get_node_name($key) == 'property') {
				$target->setProperty(array_get($value, '@name'), forms_build_property($value));
			} else {
				throw new FormsException("Unexpected member: %1", null, array($key));
			}
		}
		return $target;
	}

	/**
	 * @param $element
	 * @param \Form $form
	 * @return FormComponent
	 * @throws FormsException
	 */
	function forms_build_component($element, Form $form) {
		$element_type = array_get($element, '@type', '');
		if (empty($element_type)) {
			throw new FormsException('Element type unspecified');
		}
		$component = forms_handler_get($element_type);
		$component->setForm($form);
		foreach ($element as $key => $value) {
			if ($key == '#comment') {
				continue;
			}
			if ($key == '@type') {
				continue;
			}
			if ($key[0] == '@' && array_search(substr($key, 1), array('name', 'id', 'style', 'class', 'title')) !== false) {
				$component->setProperty(substr($key, 1), $value);
			} else if ($key[0] == '@') {
				throw new FormsException('Unexpected attribute %1 for form element of type %2', null, array($key, $element_type));
			} else if (xmlnode_get_node_name($key) != 'property') {
				throw new FormsException('Unexpected member %1 for form element. Expected `property`.', null, array(xmlnode_get_node_name($key)));
			} else if (!is_array($value) || array_get($value, '@name', '') == '') {
				throw new FormsException('Malformed property definition');
			} else {
				$component->setProperty(array_get($value, '@name'), forms_build_property($value));
			}
		}
		return $component;
	}

	/**
	 * @param $property
	 * @return array|string
	 * @throws FormsException
	 */
	function forms_build_property($property) {
		$processing = boolval(array_get($property, '@processContents', 'false'));
		$found = false;
		foreach ($property as $key => $value) {
			if ($key == '@name') {
				continue;
			}
			if ($key == '#comment') {
				continue;
			}
			if ($key[0] == '@' && $key != 'name') {
				throw new FormsException('Unexpected attribute %1 for property %2', null, array($key, array_get($property, '@name')));
			}
			if ($processing && array_search(xmlnode_get_node_name($key), array('map', 'set', 'list', '#text')) === false) {
				throw new FormsException('Invalid member found: %1', null, array($key));
			} else if ($processing) {
				if ($found) {
					throw new FormsException('You cannot assign more than one kind of value to a property');
				}
				$found = true;
			}
		}
		if (!$processing) {
			return xmlnode_to_string($property);
		}
		foreach ($property as $key => $value) {
			if ($key == '@name') {
				continue;
			}
			if ($key == '#comment') {
				continue;
			}
			return forms_resolve_property($key, $value);
		}
		return "";
	}

	/**
	 * @param $type
	 * @param $value
	 * @return array
	 * @throws FormsException
	 */
	function forms_resolve_property($type, $value) {
		$type = xmlnode_get_node_name($type);
		$result = array();
		if ($type == '#text') {
			return $value;
		} else if ($type == 'list' || $type == 'set') {
			foreach ($value as $key => $item) {
				if ($key != 'item') {
					throw new FormsException('Unexpected member for %1 at %2', array($type, $key));
				}
				$resolved = null;
				foreach ($item as $name => $inline) {
					if (array_search(xmlnode_get_node_name($name), array('map', 'set', 'list', '#text')) === false) {
						throw new FormsException('Unexpected member or attribute for %1 (%2)', null, array($type, $name));
					} else {
						if ($resolved !== null) {
							throw new FormsException('You cannot specify more than one kind of value for a property element');
						}
						$resolved = forms_resolve_property($name, $inline);
					}
				}
				if ($resolved === null) {
					throw new FormsException('Could not resolve element value for %1', null, array($type));
				}
				if ($type == 'list' || array_search($result, $result) === false) {
					array_push($result, $resolved);
				}
			}
		} else {
			foreach ($value as $key => $item) {
				if ($key != 'entry') {
					throw new FormsException('Unexpected member for %1 at %2', null, array($type, $key));
				}
				$resolved = null;
				$entry = null;
				foreach ($item as $name => $inline) {
					if ($name == '@key') {
						$entry = $inline;
					}
					if (array_search(xmlnode_get_node_name($name), array('map', 'set', 'list', '#text')) === false) {
						throw new FormsException('Unexpected member or attribute for %1 (%2)', null, array($type, $name));
					} else {
						if ($resolved !== null) {
							throw new FormsException('You cannot specify more than one kind of value for a property element');
						}
						$resolved = forms_resolve_property($name, $inline);
					}
				}
				if ($entry === null) {
					throw new FormsException('Could not resolve a key for map element');
				}
				if ($resolved === null) {
					throw new FormsException('Could not resolve element value for %1', null, array($type));
				}
				$result[$entry] = $resolved;
			}
		}
		return $result;
	}

	/**
	 * Registers all components within '~/form/*'
	 */
	function forms_init() {
		$path = dirname(__FILE__) . "/form/";
		$dir = opendir($path);
		while (($file = readdir($dir)) != null) {
			$file = $path . $file;
			$file = substr($file, strlen(url_base_local()));
			if (is_file(url_local($file))) {
				forms_handler_file($file);
			}
		}
	}

	/**
	 * Loads a form handler/layout from a file
	 * @param $file
	 */
	function forms_handler_file($file) {
		$entity = entity_load($file, 'FormObject');
		if (is_a($entity, 'FormLayout')) {
			/** @noinspection PhpUndefinedMethodInspection */
			forms_layout_register($entity->getName(), $file);
		} else if (is_a($entity, 'FormComponent')) {
			/** @noinspection PhpUndefinedMethodInspection */
			forms_handler_register($entity->getName(), $file);
		}
	}

	function forms_submitted($name) {
		return array_key_exists($name . "-form-action", $_GET) ? $_GET : (array_key_exists($name . "-form-action", $_POST) ? $_POST : false);
	}

	/**
	 * Renders the form onto the screen
	 * @param $name
	 * @param mixed $data
	 */
	function forms_render($name, $data = null) {
		$id = uniqid('form-');
		if (page_options_get('static', false)) {
			$form = forms_build($name);
			echo("<div id='$id'>");
			echo($form->render());
			echo("</div>");
		}
		echo("<div id='$id'>&nbsp;</div>\n");
		echo('<script type="text/javascript">
			Form.render("' . $name . '", "' . $id . '", ' . json_encode($data) . ');
		</script>');
	}

	forms_init();

}

?>