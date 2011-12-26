<?php

/**
 * @author Mohammad Milad Naseri (m.m.naseri at gmail.com)
 * @since 1.0 (12/12/11, 21:41)
 */

//@Entity
class EmphaticRowsFormLayout extends FormLayout {

	function getName() {
		return "rows-emphasis";
	}

	function render() {
		$src = "<div class='form-layout-rows-emphasis'>";
		for ($i = 0; $i < count($this->items); $i++) {
			$item = $this->items[$i];
			$src .= "<div class='form-layout-rows-emphasis-" . ($i % 2 == 0 ? "odd" : "even") . " form-layout-rows-emphasis-" . $i . "'>";
			/** @noinspection PhpUndefinedMethodInspection */
			$src .= $item->render();
			$src .= "</div>";
		}
		return $src . "</div>";
	}

}

?>