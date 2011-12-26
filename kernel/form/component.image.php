<?php

/**
 * @author Mohammad Milad Naseri (m.m.naseri at gmail.com)
 * @since 1.0 (12/12/11, 21:54)
 */
//@Entity
class ImageFormComponent extends FormComponent {

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
		array_push($properties, 'source');
		array_push($properties, 'alt');
		array_push($properties, 'width');
		array_push($properties, 'height');
		return $properties;
	}

	public function setProperty($property, $value) {
		if ($property == 'source') {
			$this->setProperty('src', url_relative($value));
		}
		parent::setProperty($property, $value);
	}

	function getName() {
		return "image";
	}

	function render() {
		$src = "<span class='" . $this->getClassName() . "'>";
		$src .= "<img" . forms_attribute_render($this->properties, FORMS_ATTR_SET . ",src,alt,width,height") . " /></span>";
		return $src;
	}

}

?>