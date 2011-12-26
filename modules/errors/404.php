<?php

/**
 * @author Mohammad Milad Naseri (m.m.naseri at gmail.com)
 * @since 1.0 (9/12/11, 23:48)
 */

//@Entity
class Error404 extends Page {

	function initialize() {
		$this->setTitle("Not Found");
	}

	function getContent() {
		_h("Not Found");
		_p("No content is available at <em>%1</em>.", array(page_options_get('relative')));
		print_trace();
	}

}

?>