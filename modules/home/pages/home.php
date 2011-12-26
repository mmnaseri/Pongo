<?php

/**
 * @author Mohammad Milad Naseri (m.m.naseri at gmail.com)
 * @since 1.0 (22/12/11, 18:06)
 */

if (!class_exists('HomePage')) {

	//@Entity
	class HomePage extends Page {

		public function initialize() {
			$this->setTitle("Home Page");
		}

		function getContent() {
			_h("Welcome!");
			_p("This is the home page!");
		}
	}

}

?>