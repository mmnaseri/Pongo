<?php

/**
 * @author Mohammad Milad Naseri (m.m.naseri at gmail.com)
 * @since 1.0 (23/12/11, 0:20)
 */

if (!class_exists('SystemServices')) {

	//@Entity
	class SystemServices extends Service {

		public function talkBack($arguments) {
			return $arguments['input'];
		}

	}

}

?>