<?php

/**
 * @author Mohammad Milad Naseri (m.m.naseri at gmail.com)
 * @since 1.0 (12/12/11, 21:54)
 */
//@Entity
class TextFormComponent extends FormComponent {

	function __construct() {
		$this->setProperty('type', 'text');
		$this->setProperty('id', uniqid("text-"));
	}

	/**
	 * It will return a set of properties accepted by the component
	 * If it should return null, all properties will be assumed to be acceptable.
	 * @return array
	 */
	function getProperties() {
		$properties = parent::getProperties();
		array_push($properties, 'label', 'labelPosition');
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
		return "text";
	}

	function render() {
		$label = array_get($this->properties, 'label', '');
		$label_position = $this->getProperty('labelPosition', 'left');
		if ($label_position != 'left' && $label_position != 'above') {
			$label_position = 'left';
		}
		$src = "<span class='textbox " . $this->getClassName() . "'>";
		if (!empty($label)) {
			$src .= "<label for='" . $this->getProperty('id') . "' class='label lbl-$label_position'>" . $label . "</label>";
		} else {
			$src .= "<span class='label lbl-$label_position no-label'>&nbsp;</span>";
		}
		$src .= "<span class='container'><span class='holder'><input" . forms_attribute_render($this->properties, FORMS_ATTR_SET . ",value,disabled,type") . " /></span></span></span>";
		return $src;
	}

	function getValueAttribute() {
		return "value";
	}
}

?>