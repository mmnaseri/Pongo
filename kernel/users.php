<?php

/**
 * pongo PHP Framework Kernel Component
 * Package USERS
 * @author Mohammad Milad Naseri (m.m.naseri at gmail.com)
 * @since 1.0 (22/12/11, 14:27)
 * @package com.agileapes.pongo.kernel.USERS
 */

if (!defined("KERNEL_COMPONENT_USERS")) {

	/***
	 * Kernel Component Registration
	 **/
	define('KERNEL_COMPONENT_USERS', "");

	/***
	 * Kernel Component Implementation
	 **/

	define('KCE_USERS', 15000);

	class UsersException extends KernelException {

		protected function getBase() {
			return KCE_USERS;
		}

		protected function getBaseCode() {
			return 1;
		}

	}

	define("USERS_COOKIE_NAME_TOKEN", "pongo_USER_TOKEN");
	define("USERS_COOKIE_NAME_ID", "pongo_USER_ID");
	define("USERS_REMEMBER_PERIOD", 2592000); //A month
	define("USERS_ACCOUNT_ROOT", 1);

	function users_exists($username) {
		return db_exists('users', array('username' => "'" . db_escape($username) . "'"));
	}

	function users_hash($password) {
		$password = hash(kernel_config_get('hash'), $password);
		$password = hash(kernel_config_get('hash'), $password . kernel_config_get('cipher'));
		return $password;
	}

	function users_authenticate($username, $password) {
		return users_authenticate_raw($username, users_hash($password));
	}

	function users_is_locked($id) {
		return $id == USERS_ACCOUNT_ROOT;
	}

	function users_authenticate_raw($username, $password) {
		$user = db_load('users', array('username' => array('LIKE', "'" . db_escape($username) . "'"), 'password' => "'" . db_escape($password, true) . "'"));
		if (count($user) == 0) {
			$user = db_load('users', array('email' => array('LIKE', "'" . db_escape($username) . "'"), 'password' => "'" . db_escape($password) . "'"));
			if (count($user) == 0) {
				return false;
			}
		}
		return $user[0];
	}

	function users_generate_token() {
		return uniqid("TOKEN_") . uniqid();
	}

	function users_login($username, $password, $remember = false) {
		return users_login_raw($username, users_hash($password), $remember);
	}

	function users_login_raw($username, $password, $remember = false) {
		if (state_get('user_id', false) === false) {
			$user = users_authenticate_raw($username, $password);
			if ($user === false) {
				return false;
			}
			if (kernel_option_get('com.agileapes.pongo.security.singlesignon', 'false') == 'true') {
				users_logout($user['id']);
			}
			db_update('users', array('last_seen' => time()), array('id' => $user['id']));
			state_set('user_id', $user['id']);
			state_set('user', $user);
			$_GET['bf:locale'] = $user['locale'];
			kernel_locale_init();
		}
		if ($remember && kernel_option_get('com.agileapes.pongo.state.remember.enabled', 'true') != 'false') {
			if (array_key_exists(USERS_COOKIE_NAME_TOKEN, $_COOKIE)) {
				$token = $_COOKIE[USERS_COOKIE_NAME_TOKEN];
			} else {
				$token = users_generate_token();
				db_update('users', array('token' => "'" . db_escape($token) . "'"), array('id' => state_get('user_id')));
			}
			setcookie(USERS_COOKIE_NAME_ID, state_get('user_id'), time() + intval(kernel_option_get('com.agileapes.pongo.state.remember.period', USERS_REMEMBER_PERIOD)), url_base(), null, null, true);
			setcookie(USERS_COOKIE_NAME_TOKEN, $token, time() + intval(kernel_option_get('com.agileapes.pongo.state.remember.period', USERS_REMEMBER_PERIOD)), url_base(), null, null, true);
			if (isset($user)) {
				$user['token'] = $token;
				state_set('user', $user);
			}
		}
		return true;
	}

	function users_login_from_cookie() {
		if (!array_key_exists(USERS_COOKIE_NAME_TOKEN, $_COOKIE)) {
			return false;
		}
		if (!array_key_exists(USERS_COOKIE_NAME_ID, $_COOKIE)) {
			return false;
		}
		$user = db_load('users', array('id' => $_COOKIE[USERS_COOKIE_NAME_ID], 'token' => "'" . db_escape($_COOKIE[USERS_COOKIE_NAME_TOKEN]) . "'"));
		if (count($user) == 0) {
			return false;
		}
		$user = $user[0];
		return users_login_raw($user['username'], $user['password'], true);
	}

	function users_logout($user = null) {
		if ($user === null && users_logged_in()) {
			db_update('users', array('token' => "''"), array('id' => state_get('user_id', -1)));
			state_destroy();
			unset($_COOKIE[USERS_COOKIE_NAME_ID]);
			unset($_COOKIE[USERS_COOKIE_NAME_TOKEN]);
			setcookie(USERS_COOKIE_NAME_ID, false, time() - intval(kernel_option_get('options.state.remember.period', USERS_REMEMBER_PERIOD)), url_base(), null, null, true);
			setcookie(USERS_COOKIE_NAME_TOKEN, false, time() - intval(kernel_option_get('options.state.remember.period', USERS_REMEMBER_PERIOD)), url_base(), null, null, true);
		} else {
			state_visit_all(function ($item, $user) {
				if (array_key_exists('user_id', $item['data'])) {
					if ($item['data']['user_id'] == $user) {
						db_delete(STATE_TABLE_NAME, array('id' => "'" . db_escape($item['id']) . "'"));
					}
				}
			}, $user);
		}
	}

	function users_is_authenticated() {
		$user = state_get('user', false);
		if ($user === false) {
			return false;
		}
		if (!empty($user['activation'])) {
			return false;
		}
		return true;
	}

	function users_is_disallowed($action) {
		$user = state_get('user', false);
		if ($user === false) {
			return false; //groups_is_disallowed(GROUPS_GUESTS, $action);
		}
		//	    if (users_is_member_of(GROUPS_SUPERUSERS)) {
		//	        return false;
		//	    }
		$tree = itree_create($user['disallowed']);
		if (itree_includes($tree, $action)) {
			return true;
		}
		if (array_key_exists('groups', $user)) {
			$groups = explode(',', $user['groups']);
			for ($i = 0; $i < count($groups); $i++) {
				//	            if (groups_is_disallowed($groups[$i], $action)) {
				//	                return true;
				//	            }
			}
		}
		return false;
	}

	function users_is_allowed($action) {
		if (!state_initialized()) {
			return true;
		}
		$allowed = true;
		//	    actions_do('users.is_allowed.' . $action, $allowed);
		if (!$allowed) {
			return false;
		}
		if (users_is_disallowed($action)) {
			return false;
		}
		$user = users_is_authenticated();
		if ($user === false) {
			//	        return groups_is_allowed(GROUPS_GUESTS, $action);
		} else {
			$user = state_get('user');
		}
		$tree = itree_create($user['allowed']);
		if (itree_includes($tree, ITREE_ALL_EXCLUSIVE)) {
			return false;
		}
		if (array_key_exists('username', $user) && state_get('user', false) !== false) {
			if (is_string($action) && preg_match('/\.' . $user['username'] . '$/msi', $action)) {
				return true;
			}
		}
		if (array_key_exists('id', $user) && state_get('user', false) !== false) {
			if (is_string($action) && !preg_match('/^admin./msi', $action) && preg_match('/\.u' . $user['id'] . '$/msi', $action)) {
				return true;
			}
		}
		if (itree_includes($tree, $action)) {
			return true;
		}
		//	    if (users_is_member_of(GROUPS_SUPERUSERS)) {
		//	        return true;
		//	    }
		if (array_key_exists('groups', $user)) {
			$groups = explode(',', $user['groups']);
			for ($i = 0; $i < count($groups); $i++) {
				//	            if (groups_is_allowed($groups[$i], $action)) {
				//	                return true;
				//	            }
			}
		}
		return false;
	}

	function users_get($username) {
		$user = db_load('users', array('username' => array('LIKE', "'" . db_escape($username) . "'"), 'email' => array('LIKE', "'" . db_escape($username) . "'"),), array(), false, " OR");
		if (count($user) == 0) {
			return false;
		} else {
			return $user[0];
		}
	}

	function users_get_by_id($id) {
		$user = db_load('users', array('id' => $id));
		if (count($user) == 0) {
			return false;
		} else {
			return $user[0];
		}
	}

	function users_register($username, $password, $first_name, $last_name, $email, $birthday, $gender = "u") {
		$password = users_hash($password);
		if (db_insert('users', array('username' => "'" . db_escape($username) . "'", 'password' => "'" . db_escape($password) . "'", 'first_name' => "'" . db_escape($first_name) . "'", 'last_name' => "'" . db_escape($last_name) . "'", 'email' => "'" . db_escape($email) . "'", 'activation' => "''", 'url' => "''", 'registered' => time(), 'birthday' => $birthday, 'status' => "0", 'hidden_fields' => "'" . db_escape(kernel_option_get('com.agileapes.pongo.defaults.user.hidden_fields', "email|birthday")) . "'", 'hide_profile' => "0", 'allowed' => "'page::widget'", 'locale' => "'" . $_GET['bf:locale'] . "'", //	        'groups' => "'" . GROUPS_USERS . "'",
			'gender' => "'" . db_escape($gender) . "'")) === false
		) {
			throw new UsersException("User registration failed: %1", null, array(db_error_msg()));
		}
		$user = users_get($username);
		//	    service_call('users', 'sendActivationEmail', $user['id']);
	}

	function users_activate($id, $key) {
		db_update('users', array('activation' => "''"), array('id' => $id, 'activation' => "'" . db_escape($key) . "'"));
	}

	function users_delete($id) {
		$user = users_get_by_id($id);
		//	    actions_do('users.discontinue.u' . $user['username'], $user);
		db_delete('user_meta', array('user_id' => $id));
		db_delete('users', array('id' => $id));
	}

	function users_is_member_of($group, $id = null) {
		$user = false;
		if ($id === null) {
			$user = state_get('user', false);
		} else {
			$user = users_get_by_id($id);
		}
		if ($user === false) {
			return false;
		}
		$groups = explode(',', $user['groups']);
		return array_search($group, $groups) !== false;
	}

	function users_logged_in() {
		return state_get('user_id', null) !== null;
	}

	function users_is_online($id = null, $show_hidden = false) {
		if ($id === null) {
			$id = state_get('user_id', null);
		}
		if ($id === null) {
			return false;
		}
		$list = state_list();
		for ($i = 0; $i < count($list); $i++) {
			if (!array_key_exists('user_id', $list[$i]['data'])) {
				continue;
			}
			if ($list[$i]['data']['user_id'] == $id) {
				return $show_hidden || !array_key_exists('hide', $list[$i]['data']) || kernel_option_get('com.agileapes.pongo.state.hide.enabled', 'true') == 'false';
			}
		}
		return false;
	}

	function users_nicename($id) {
		$user = users_get_by_id($id);
		if (!$user) {
			return null;
		}
		if (!empty($user['first_name']) || !empty($user['last_name'])) {
			$user = $user['first_name'] . " " . $user['last_name'];
		} else {
			$user = $user['username'];
		}
		$user = trim($user);
		return $user;
	}

}

//		db_table_create('users', array(
//			'id' => db_create_column(CT_BIGINT, 0, false),
//			'username' => db_create_column(CT_VARCHAR, 128, false),
//			'password' => db_create_column(CT_VARCHAR, 128, false),
//			'token' => db_create_column(CT_VARCHAR, 128, false),
//			'activation' => db_create_column(CT_VARCHAR, 128, false),
//			'first_name' => db_create_column(CT_VARCHAR, 128, false),
//			'last_name' => db_create_column(CT_VARCHAR, 128, false),
//			'email' => db_create_column(CT_VARCHAR, 256, false),
//			'url' => db_create_column(CT_VARCHAR, 256, false),
//			'allowed' => db_create_column(CT_VARCHAR, 256, false),
//			'disallowed' => db_create_column(CT_VARCHAR, 256, false),
//			'groups' => db_create_column(CT_VARCHAR, 256, false),
//			'hidden_fields' => db_create_column(CT_VARCHAR, 256, false, ""),
//			'hide_profile' => db_create_column(CT_BOOLEAN, 0, false, "FALSE"),
//			'last_seen' => db_create_column(CT_INT, 0, false),
//			'locale' => db_create_column(CT_VARCHAR, 5, false),
//			'registered' => db_create_column(CT_INT, 0, false),
//			'status' => db_create_column(CT_INT, 0, false),
//			'birthday' => db_create_column(CT_INT, 0, false),
//			'gender' => db_create_column(CT_CHAR, 1, false, db_quote('U')),
//		));
//db_table_constraint('users', array(
//	db_create_constraint(CC_PRIMARY_KEY, 'id'),
//	db_create_constraint(CC_UNIQUE, 'username'),
//	db_create_constraint(CC_UNIQUE, 'email')
//));
//db_sequence_create('users', 'id', 'seq_user_id');
?>