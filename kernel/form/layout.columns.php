<?php

/**
 * @author Mohammad Milad Naseri (m.m.naseri at gmail.com)
 * @since 1.0 (12/12/11, 21:41)
 */

//@Entity
class ColumnsFormLayout extends FormLayout {

	function getName() {
		return "columns";
	}

	function render() {
		$src = "<table class='form-layout-columns'><tr>";
		for ($i = 0; $i < count($this->items); $i++) {
			$item = $this->items[$i];
			$src .= "<td class='form-layout-rows-" . ($i % 2 == 0 ? "odd" : "even") . " form-layout-columns-" . $i . "'>";
			/** @noinspection PhpUndefinedMethodInspection */
			$src .= $item->render();
			$src .= "</td>";
		}
		$src .= "</tr></table>";
		return $src;
	}

}

?>