<?php

/**
 * @author Mohammad Milad Naseri (m.m.naseri at gmail.com)
 * @since 1.0 (6/12/11, 13:05)
 */
//@Entity
class Login extends Page {

	function initialize() {
		$this->setTitle("Login");
		if (users_logged_in()) {
			//			url_relocate("system/test");
		}
	}

	function getContent() {
		forms_render('login');
	}

}

?>