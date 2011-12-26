<?php

/**
 * @author Mohammad Milad Naseri (m.m.naseri at gmail.com)
 * @since 1.0 (12/12/11, 21:41)
 */

//@Entity
class RowsFormLayout extends FormLayout {

	function getName() {
		return "rows";
	}

	function render() {
		$src = "<div class='form-layout-rows'>";
		if (boolval($this->getProperty('border', true))) {
			$src .= "<fieldset>";
			if ($this->getProperty('caption', '') !== "") {
				$caption = __($this->getProperty('caption'));
				$id = uniqid("form-layout-rows-caption-");
				if (boolval($this->getProperty('checkbox'))) {
					$caption = "<input type='checkbox' " . (boolval($this->getProperty('selected', true)) ? "checked='checked' " : "") . "name='" . $this->getProperty('name', $id) . "' id='$id'' /><label for='$id'>$caption</label>";
				} else if (boolval($this->getProperty('select'))) {
					$caption = "<input type='radio' " . (boolval($this->getProperty('selected', true)) ? "checked='checked' " : "") . "name='" . $this->getProperty('group', 'form-layout-rows') . "' id='$id'' value='" . $this->getProperty('name', $id) . "' /><label for='$id'>$caption</label>";
				} else {
					$caption = "<label>$caption</label>";
				}
				$src .= "<legend>" . $caption . "</legend>";
			}
		}
		for ($i = 0; $i < count($this->items); $i++) {
			$item = $this->items[$i];
			$src .= "<div class='form-layout-rows-" . ($i % 2 == 0 ? "odd" : "even") . " form-layout-rows-" . $i . "'>";
			/** @noinspection PhpUndefinedMethodInspection */
			$src .= $item->render();
			$src .= "</div>";
		}
		if (boolval($this->getProperty('border', true))) {
			$src .= "</fieldset>";
		}
		$src .= "</div>";
		return $src;
	}

}

?>