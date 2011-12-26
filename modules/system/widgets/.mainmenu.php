<?php

/**
 * @author Mohammad Milad Naseri (m.m.naseri at gmail.com)
 * @since 1.0 (10/12/11, 15:10)
 */
//@Entity
class MainMenuWidget extends Widget {

	function getContent($options) {
		$pages = array('Login' => 'users/login', 'Test' => 'system/test',);
		$current = page_options_get('relative');
		if (count($pages) > 0) {
			echo("<ul>");
			foreach ($pages as $title => $url) {
				echo(strings_format("<li class='%3'><a href='pongo:%1'>%2</a></li>", $url, __($title), str_replace('/', '-', $url) . ($url == $current ? " current" : "")));
			}
			echo("</ul>");
		}
	}
}

?>