<?php

/**
 * @author Mohammad Milad Naseri (m.m.naseri at gmail.com)
 * @since 1.0 (23/12/11, 0:57)
 */

if (!class_exists('AuthenticationService')) {

	//@Entity
	class AuthenticationService extends Service {

		public function login($arguments) {
			return users_login($arguments['username'], $arguments['password'], array_get($arguments, 'remember', false));
		}

		public function logout() {
			users_logout();
			return true;
		}

		public function success($arguments) {
			if ($arguments[':submit-target'] == 'submit-login') {
				if (!users_logged_in()) {
					return null;
				}
				return "Page.navigate('system/test', '" . array_access(state_get(), 'user/locale') . "');";
			} else {
				if (users_logged_in()) {
					return null;
				}
				return "Page.navigate('home/home');";
			}
		}

		public function failure() {
			if (users_logged_in()) {
				return null;
			}
			throw new UsersException("Invalid username/password");
		}

	}

}

?>