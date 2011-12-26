<?php

/**
 * @author Mohammad Milad Naseri (m.m.naseri at gmail.com)
 * @since 1.0 (12/12/11, 21:54)
 */

include("component.text.php");

//@Entity
class PasswordFormComponent extends TextFormComponent {

	function __construct() {
		$this->setProperty('type', 'password');
		$this->setProperty('id', uniqid("password-"));
	}

	function getName() {
		return "password";
	}

}

?>