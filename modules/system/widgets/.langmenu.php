<?php

/**
 * @author Mohammad Milad Naseri (m.m.naseri at gmail.com)
 * @since 1.0 (10/12/11, 15:10)
 */
//@Entity
class LanguageMenuWidget extends Widget {

	function getContent($options) {
		$regions = i18n_region_list();
		echo("<ul>");
		for ($i = 0; $i < count($regions); $i++) {
			echo("<li><a href='javascript:void(0);' onclick='Page.navigate(null, \"" . $regions[$i]['name'] . "\");'>" . i18n_meta('name/local', $regions[$i]['language'], $regions[$i]['country']) . "</a></li>");
		}
		echo("</ul>");
	}
}

?>