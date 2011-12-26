<?php

/**
 * @author Mohammad Milad Naseri (m.m.naseri at gmail.com)
 * @since 1.0 (9/12/11, 23:48)
 */
//@Entity
class Error403 extends Page {

	function initialize() {
		$this->setTitle("Access Denied");
	}

	function getContent() {
		_h("Access Denied");
		_p("You do not have sufficient privileges to access this resource");
	}

}

?>