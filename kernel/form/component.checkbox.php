<?php

/**
 * @author Mohammad Milad Naseri (m.m.naseri at gmail.com)
 * @since 1.0 (12/12/11, 21:54)
 */
//@Entity
class CheckboxFormComponent extends FormComponent {

	function __construct() {
		$this->setProperty('type', 'checkbox');
		$this->setProperty('id', uniqid("checkbox-"));
	}

	/**
	 * It will return a set of properties accepted by the component
	 * If it should return null, all properties will be assumed to be acceptable.
	 * @return array
	 */
	function getProperties() {
		$properties = parent::getProperties();
		array_push($properties, 'label');
		return $properties;
	}

	public function setProperty($property, $value) {
		if ($property == 'disabled') {
			if (boolval($value)) {
				$value = "disabled";
			} else {
				return;
			}
		}
		parent::setProperty($property, $value);
	}

	function getName() {
		return "checkbox";
	}

	function render() {
		$src = "<span class='" . $this->getClassName() . "'>";
		$src .= "<input " . forms_attribute_render($this->properties, FORMS_ATTR_SET . ",type,disabled,value") . " />";
		$src .= "<label for='" . $this->getProperty('id') . "'>" . $this->getProperty('label', "") . "</label>";
		$src .= "</span>";
		return $src;
	}

	function getValueAttribute() {
		return "checked";
	}
}

?>